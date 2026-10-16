/**
 * Prerender halaman publik setelah `vite build`.
 * Menulis HTML yang sudah di-render Vue agar crawler mendapat konten penuh.
 *
 * Skip: PRERENDER=0
 * Port: PRERENDER_PORT (default 4173). Jika port itu sibuk, port berikutnya dipakai.
 */
import { spawn } from 'node:child_process'
import { mkdir, writeFile, access } from 'node:fs/promises'
import net from 'node:net'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import process from 'node:process'
import { ensureHidePrerenderBoot } from './hidePrerenderBoot.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '..')
const distDir = path.join(root, 'dist')
const viteBin = path.join(root, 'node_modules', 'vite', 'bin', 'vite.js')

const ROUTES = [
  { path: '/', out: 'index.html', ready: '.home-page h1#hero-heading, .home-page h1, [data-seo-shell] h1' },
  { path: '/catatan-rilis', out: path.join('catatan-rilis', 'index.html'), ready: '.release-page h1, .release-hero h1' },
]

const PORT_START = Number(process.env.PRERENDER_PORT || 4173)
let baseUrl = ''

function log(msg) {
  console.log(`[prerender] ${msg}`)
}

function fail(msg) {
  console.error(`[prerender] ${msg}`)
}

function canBind(port) {
  return new Promise((resolve) => {
    const server = net.createServer()
    server.unref()
    server.once('error', () => resolve(false))
    server.listen({ port, host: '127.0.0.1', exclusive: true }, () => {
      server.close(() => resolve(true))
    })
  })
}

async function findFreePort(start, attempts = 20) {
  for (let i = 0; i < attempts; i += 1) {
    const port = start + i
    if (await canBind(port)) return port
  }
  throw new Error(`Tidak ada port kosong mulai ${start} (${attempts} percobaan)`)
}

function waitForOwnServer(url, child, timeoutMs = 60000) {
  return new Promise((resolve, reject) => {
    const started = Date.now()
    const timer = setInterval(async () => {
      if (child.exitCode !== null || child.signalCode) {
        clearInterval(timer)
        reject(new Error((child.output || '').trim() || `Preview server berhenti (exit ${child.exitCode})`))
        return
      }
      if (!/Local:\s+https?:\/\//i.test(child.output || '')) {
        if (Date.now() - started > timeoutMs) {
          clearInterval(timer)
          reject(new Error(`Preview server tidak siap di ${url}`))
        }
        return
      }
      try {
        const res = await fetch(url, { redirect: 'manual' })
        if (res.ok || res.status === 304 || (res.status >= 300 && res.status < 400)) {
          clearInterval(timer)
          resolve()
        }
      } catch {
        if (Date.now() - started > timeoutMs) {
          clearInterval(timer)
          reject(new Error(`Preview server tidak siap di ${url}`))
        }
      }
    }, 200)
  })
}

function startPreview(port) {
  const child = spawn(
    process.execPath,
    [viteBin, 'preview', '--host', '127.0.0.1', '--port', String(port), '--strictPort'],
    {
      cwd: root,
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, BROWSER: 'none' },
      windowsHide: true,
    }
  )

  child.output = ''
  child.stopping = false
  const capture = (buf) => {
    child.output += buf.toString()
  }
  child.stdout.on('data', capture)
  child.stderr.on('data', capture)

  child.on('exit', (code) => {
    if (child.stopping || !code) return
    fail(`preview exit ${code}\n${child.output}`)
  })

  return child
}

function stopPreview(child) {
  if (!child || child.exitCode !== null) return Promise.resolve()
  child.stopping = true
  if (process.platform === 'win32') {
    return new Promise((resolve) => {
      let settled = false
      const killer = spawn('taskkill', ['/pid', String(child.pid), '/T', '/F'], {
        stdio: 'ignore',
        windowsHide: true,
      })
      const timer = setTimeout(finish, 4000)
      killer.on('exit', finish)
      killer.on('error', finish)
      function finish() {
        if (settled) return
        settled = true
        clearTimeout(timer)
        resolve()
      }
    })
  }
  try {
    child.kill('SIGTERM')
  } catch {
    // ignore
  }
  return Promise.resolve()
}

async function startReadyPreview() {
  let port = await findFreePort(PORT_START)
  let lastError
  for (let attempt = 0; attempt < 5; attempt += 1) {
    if (attempt > 0) port = await findFreePort(port + 1)
    if (port !== PORT_START) log(`port ${PORT_START} sibuk, memakai ${port}`)
    const preview = startPreview(port)
    const url = `http://127.0.0.1:${port}`
    try {
      await waitForOwnServer(url, preview)
      return { preview, url }
    } catch (err) {
      lastError = err
      await stopPreview(preview)
      const busy = /already in use|EADDRINUSE/i.test(`${preview.output || ''}\n${err?.message || ''}`)
      if (!busy || attempt === 4) break
      log(`port ${port} tertangkap proses lain, coba lagi`)
    }
  }
  throw lastError
}

async function loadPuppeteer() {
  try {
    const mod = await import('puppeteer')
    return mod.default
  } catch (err) {
    throw new Error(
      `Puppeteer belum terpasang. Jalankan: npm i -D puppeteer\n${err?.message || err}`
    )
  }
}

async function prerenderRoute(page, route) {
  const url = `${baseUrl}${route.path}`
  log(`render ${route.path}`)
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 })
  await page.waitForSelector(route.ready, { timeout: 45000 })
  // Beri waktu singkat untuk meta/JSON-LD client-side
  await new Promise((r) => setTimeout(r, 800))

  const html = await page.evaluate(() => {
    // Bersihkan atribut runtime yang tidak perlu di HTML statis
    document.querySelectorAll('[data-v-inspector], [data-v-inspector-relative-path]').forEach((el) => {
      el.removeAttribute('data-v-inspector')
      el.removeAttribute('data-v-inspector-relative-path')
    })
    // Prompt PWA / toast tidak relevan untuk crawler
    document.querySelectorAll('.pwa-install-prompt, .toast-container').forEach((el) => el.remove())
    // Hapus style shell sementara jika Vue sudah render
    if (document.querySelector('.home-page, .release-page')) {
      document.getElementById('seo-shell-style')?.remove()
      document.querySelectorAll('[data-seo-shell]').forEach((el) => el.remove())
    }
    return '<!DOCTYPE html>\n' + document.documentElement.outerHTML
  })

  // index.html dipakai SPA fallback semua rute — pastikan anti-FOUC boot tetap ada
  // agar hard-refresh /super-admin/* tidak sekilas menampilkan Home prerender.
  const finalHtml = route.out === 'index.html' ? ensureHidePrerenderBoot(html) : html

  const outPath = path.join(distDir, route.out)
  await mkdir(path.dirname(outPath), { recursive: true })
  await writeFile(outPath, finalHtml, 'utf8')
  log(`wrote ${path.relative(root, outPath)} (${Math.round(finalHtml.length / 1024)} KB)`)
}

async function main() {
  if (process.env.PRERENDER === '0') {
    log('dilewati (PRERENDER=0)')
    return
  }

  try {
    await access(path.join(distDir, 'index.html'))
  } catch {
    fail('dist/index.html tidak ada. Jalankan vite build terlebih dahulu.')
    process.exit(1)
  }

  const puppeteer = await loadPuppeteer()

  let browser
  let preview
  try {
    const ready = await startReadyPreview()
    preview = ready.preview
    baseUrl = ready.url
    log(`preview siap di ${baseUrl}`)

    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    })
    const page = await browser.newPage()
    await page.setViewport({ width: 1280, height: 800 })

    // Blok request yang tidak perlu untuk percepat (analytics, font eksternal opsional tetap diizinkan)
    await page.setRequestInterception(true)
    page.on('request', (req) => {
      const type = req.resourceType()
      if (type === 'media' || type === 'websocket') {
        req.abort()
        return
      }
      req.continue()
    })

    for (const route of ROUTES) {
      await prerenderRoute(page, route)
    }

    log('selesai')
  } catch (err) {
    fail(err?.stack || err?.message || String(err))
    fail('Build SPA tetap ada; HTML fallback SEO shell di index.html tetap aktif.')
    process.exitCode = 0 // jangan gagalkan deploy hanya karena prerender
  } finally {
    if (browser) {
      try {
        await browser.close()
      } catch {
        // ignore
      }
    }
    if (preview) await stopPreview(preview)
  }
}

main()
