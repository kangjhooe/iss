import katex from 'katex'
import 'katex/dist/katex.min.css'

export const MAX_FORMULA_LENGTH = 2000

export function renderFormula(el, tex, display = false) {
  if (!el) return
  const source = String(tex || '')
  if (!source || source.length > MAX_FORMULA_LENGTH) {
    el.textContent = source
    return
  }
  katex.render(source, el, {
    throwOnError: false,
    displayMode: !!display,
    output: 'html',
    trust: false
  })
}

export function renderMathIn(root) {
  if (!root) return
  root.querySelectorAll('[data-math], span.ql-formula').forEach((el) => {
    const tex = el.getAttribute('data-math') || el.getAttribute('data-value') || ''
    if (!tex) return
    const display = el.getAttribute('data-display') === 'block'
    renderFormula(el, tex, display)
    el.setAttribute('data-math', tex)
    if (display) el.setAttribute('data-display', 'block')
  })
}

export function collapseMathHtml(html) {
  if (!html || !html.includes('data-math')) return html || ''
  const doc = new DOMParser().parseFromString(`<div id="qhtml">${html}</div>`, 'text/html')
  const root = doc.getElementById('qhtml')
  if (!root) return html
  const nodes = [...root.querySelectorAll('[data-math]')]
  for (let i = nodes.length - 1; i >= 0; i -= 1) {
    const el = nodes[i]
    const tex = el.getAttribute('data-math') || ''
    const display = el.getAttribute('data-display') === 'block'
    el.replaceChildren(doc.createTextNode(tex))
    ;[...el.attributes].forEach((attr) => {
      if (attr.name !== 'class' && attr.name !== 'data-math' && attr.name !== 'data-display') {
        el.removeAttribute(attr.name)
      }
    })
    el.className = 'ql-formula'
    el.setAttribute('data-math', tex)
    if (display) el.setAttribute('data-display', 'block')
    else el.removeAttribute('data-display')
  }
  return root.innerHTML
}
