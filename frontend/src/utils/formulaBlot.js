import Quill from 'quill'
import katex from 'katex'

let registered = false

export function registerFormulaBlot() {
  if (registered) return
  window.katex = katex
  const Base = Quill.import('formats/formula')

  class FormulaBlot extends Base {
    static create(value) {
      const tex = typeof value === 'string' ? value : String(value?.tex ?? '')
      const display = typeof value === 'object' && value !== null && value.display === true
      const node = super.create(tex || '\\,')
      try {
        katex.render(tex || '\\,', node, {
          throwOnError: false,
          displayMode: display,
          output: 'html',
          trust: false
        })
      } catch (_) {
        node.textContent = tex
      }
      node.setAttribute('data-value', tex)
      node.setAttribute('data-math', tex)
      if (display) node.setAttribute('data-display', 'block')
      else node.removeAttribute('data-display')
      return node
    }

    static value(domNode) {
      const tex = domNode.getAttribute('data-math') || domNode.getAttribute('data-value') || ''
      if (domNode.getAttribute('data-display') === 'block') {
        return { tex, display: true }
      }
      return tex
    }

    html() {
      const tex = this.domNode.getAttribute('data-math') || ''
      const display = this.domNode.getAttribute('data-display') === 'block'
      const esc = tex
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
      const displayAttr = display ? ' data-display="block"' : ''
      return `<span class="ql-formula" data-math="${esc}"${displayAttr}>${esc}</span>`
    }
  }

  Quill.register('formats/formula', FormulaBlot, true)
  registered = true
}
