/**
 * Salin hasil build FE (frontend/dist) ke document root shared hosting (public/).
 * Tidak menimpa: index.php, .htaccess, storage/, .gitignore
 */
import { cp, mkdir, readdir, rm, stat } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '..')
const repoRoot = path.resolve(root, '..')
const distDir = path.join(root, 'dist')
const publicDir = path.join(repoRoot, 'public')

const PRESERVE = new Set(['index.php', '.htaccess', 'storage', '.gitignore', '.gitkeep'])

async function exists(p) {
  try {
    await stat(p)
    return true
  } catch {
    return false
  }
}

async function main() {
  if (!(await exists(path.join(distDir, 'index.html')))) {
    console.error('[sync-public] frontend/dist/index.html tidak ada. Jalankan vite build dulu.')
    process.exit(1)
  }

  await mkdir(publicDir, { recursive: true })

  const entries = await readdir(distDir)
  for (const name of entries) {
    if (PRESERVE.has(name)) continue
    const from = path.join(distDir, name)
    const to = path.join(publicDir, name)
    // Hapus target lama agar hash asset lama tidak menumpuk
    if (await exists(to)) {
      await rm(to, { recursive: true, force: true })
    }
    await cp(from, to, { recursive: true })
  }

  console.log(`[sync-public] disalin ${entries.length} entri dari frontend/dist → public/`)
}

main().catch((err) => {
  console.error('[sync-public]', err)
  process.exit(1)
})
