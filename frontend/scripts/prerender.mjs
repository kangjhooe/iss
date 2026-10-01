/**
 * Prerender halaman publik setelah `vite build`.
 * Menulis HTML yang sudah di-render Vue agar crawler mendapat konten penuh.
 *
 * Skip: PRERENDER=0
 * Port: PRERENDER_PORT (default 4173)
 */
import { spawn } from 'node:child_process'
import { mkdir, writeFile, access } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import process from 'node:process'
import { ensureHidePrerenderBoot } from './hidePrerenderBoot.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '..')
const distDir = path.join(root, 'dist')

const ROUTES = [
  { path: '/', out: 'index.html', ready: '.home-page h1#hero-heading, .home-page h1, [data-seo-shell] h1' },
  { path: '/catatan-rilis', out: path.join('catatan-rilis', 'index.html'), ready: '.release-page h1, .release-hero h1' },
]

const PORT = Number(process.env.PRERENDER_PORT || 4173)
const BASE = `http://127.0.0.1:${PORT}`

function log(msg) {
  console.log(`[prerender] ${msg}`)
}

function fail(msg) {
  console.error(`[prerender] ${msg}`)
}

async function waitForServer(url, timeoutMs = 60000) {
  const start = Date.now()
  while (Date.now() - start < timeoutMs) {
    try {
      const res = await fetch(url, { redirect: 'manual' })
      if (res.ok || res.status === 304 || (res.status >= 300 && res.status < 400)) return
    } catch {
      // retry
    }
    await new Promise((r) => setTimeout(r, 300))
  }
  throw new Error(`Preview server tidak siap di ${url}`)
}

function startPreview() {
  const child = spawn(
    process.platform === 'win32' ? 'npx.cmd' : 'npx',
    ['vite', 'preview', '--host', '127.0.0.1', '--port', String(PORT), '--strictPort'],
    {
      cwd: root,
      stdio: ['ignore', 'pipe', 'pipe'],
      env: { ...process.env, BROWSER: 'none' },
      shell: process.platform === 'win32',
    }
  )

  let output = ''
  child.stdout.on('data', (buf) => {
    output += buf.toString()
  })
  child.stderr.on('data', (buf) => {
    output += buf.toString()
  })

  child.on('exit', (code) => {
    if (code && code !== 0 && code !== null) {
      fail(`preview exit ${code}\n${output}`)
    }
  })

  return child
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
  const url = `${BASE}${route.path}`
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
  const preview = startPreview()

  let browser
  try {
    await waitForServer(BASE)
    log(`preview siap di ${BASE}`)

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
    try {
      preview.kill('SIGTERM')
    } catch {
      // ignore
    }
  }
}

main()
