/**
 * Removes per-view <Layout> wrapper — layout is provided globally from App.vue.
 */
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const viewsDir = path.join(__dirname, '..', 'src', 'views')

function walk(dir, files = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) walk(full, files)
    else if (entry.name.endsWith('.vue')) files.push(full)
  }
  return files
}

function stripLayout(content) {
  if (!content.includes('<Layout') && !content.includes('Layout.vue')) {
    return { content, changed: false }
  }

  let next = content
  next = next.replace(/^import\s+Layout\s+from\s+['"]@\/components\/Layout\.vue['"]\s*\n/m, '')
  next = next.replace(/^import\s+Layout\s+from\s+['"]\.\.\/components\/Layout\.vue['"]\s*\n/m, '')
  next = next.replace(/^import\s+Layout\s+from\s+['"]\.\.\/\.\.\/components\/Layout\.vue['"]\s*\n/m, '')
  next = next.replace(/^\s*<Layout>\s*\n/gm, '')
  next = next.replace(/^\s*<\/Layout>\s*\n/gm, '')

  return { content: next, changed: next !== content }
}

const files = walk(viewsDir)
let changedCount = 0

for (const file of files) {
  const original = fs.readFileSync(file, 'utf8')
  const { content, changed } = stripLayout(original)
  if (changed) {
    fs.writeFileSync(file, content, 'utf8')
    changedCount++
    console.log('updated:', path.relative(viewsDir, file))
  }
}

console.log(`\nDone. ${changedCount} file(s) updated.`)
