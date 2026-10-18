<script>
import { Comment, Fragment, Text, h } from 'vue'

function classTokens(value) {
  if (!value) return ''
  if (typeof value === 'string') return value
  if (Array.isArray(value)) return value.map(classTokens).join(' ')
  if (typeof value === 'object') {
    return Object.keys(value).filter((key) => value[key]).join(' ')
  }
  return ''
}

function textOf(node) {
  if (node == null || node === true || node === false) return ''
  if (typeof node === 'string' || typeof node === 'number') return String(node)
  if (node.type === Text) return String(node.children || '')
  if (typeof node.children === 'string' || typeof node.children === 'number') return String(node.children)
  if (Array.isArray(node.children)) return node.children.map(textOf).join(' ')
  return ''
}

function isSearchNode(node) {
  if (!node || typeof node.type === 'symbol') return false
  const tokens = classTokens(node.props?.class)
  if (/(?:^|\s)(search-wrap|search-box|search-input|search-field|search-group)(?:\s|$)/.test(tokens)) return true
  const placeholder = String(node.props?.placeholder || '')
  if (/^cari\b/i.test(placeholder)) return true
  if (/(?:^|\s)filter-field(?:\s|$)/.test(tokens)) {
    const label = textOf(node).replace(/\s+/g, ' ').trim()
    if (/^cari\b/i.test(label)) return true
  }
  const children = Array.isArray(node.children) ? node.children : []
  return children.some((child) => child && typeof child === 'object' && isSearchNode(child))
}

function isActionNode(node) {
  if (!node || node.type !== 'button') return false
  const tokens = classTokens(node.props?.class)
  if (/(?:^|\s)(filter-quick-btn|filter-toggle|filter-chip|filter-reset|btn-ghost)(?:\s|$)/.test(tokens)) return false
  return /(?:^|\s)btn-/.test(tokens)
}

function flatten(nodes) {
  const list = Array.isArray(nodes) ? nodes : (nodes == null ? [] : [nodes])
  const out = []
  for (const node of list) {
    if (node == null || node === true || node === false) continue
    if (typeof node === 'string' || typeof node === 'number') {
      if (String(node).trim()) out.push(node)
      continue
    }
    if (node.type === Comment) continue
    if (node.type === Text) {
      if (String(node.children || '').trim()) out.push(node)
      continue
    }
    if (node.type === Fragment) {
      out.push(...flatten(node.children))
      continue
    }
    out.push(node)
  }
  return out
}

export default {
  name: 'ListFilterBar',
  inheritAttrs: false,
  props: {
    collapse: {
      type: String,
      default: 'auto',
      validator: (value) => ['auto', 'always', 'never'].includes(value)
    }
  },
  data() {
    return {
      open: false,
      active: false
    }
  },
  mounted() {
    this.syncActive()
  },
  updated() {
    this.syncActive()
  },
  methods: {
    syncActive() {
      const root = this.$el
      if (!root || typeof root.querySelectorAll !== 'function') return
      const fields = root.querySelectorAll('.list-filter-bar__slot.is-filter select, .list-filter-bar__slot.is-filter input, .list-filter-bar__slot.is-filter textarea')
      const next = Array.from(fields).some((field) => {
        if (field.disabled || field.type === 'hidden') return false
        if (field.type === 'checkbox' || field.type === 'radio') return field.checked
        return String(field.value || '').trim() !== ''
      })
      if (next !== this.active) this.active = next
    }
  },
  render() {
    const nodes = flatten(this.$slots.default?.() || [])
    const kindOf = (node) => {
      if (isSearchNode(node)) return 'is-search'
      if (isActionNode(node)) return 'is-action'
      return 'is-filter'
    }
    const searchCount = nodes.filter((node) => kindOf(node) === 'is-search').length
    const filterCount = nodes.filter((node) => kindOf(node) === 'is-filter').length
    const collapsible = this.collapse === 'always'
      ? filterCount > 0
      : this.collapse === 'never'
        ? false
        : searchCount > 0 && filterCount > 0

    const children = nodes.map((node) => h('div', {
      class: ['list-filter-bar__slot', kindOf(node)]
    }, [node]))

    if (collapsible) {
      children.push(h('button', {
        type: 'button',
        class: ['list-filter-bar__toggle', { 'is-active': this.open || this.active }],
        'aria-expanded': this.open ? 'true' : 'false',
        onClick: () => {
          this.open = !this.open
        }
      }, [
        'Filter',
        this.active ? h('span', { class: 'list-filter-bar__dot', 'aria-hidden': 'true' }) : null,
        h('svg', {
          class: 'list-filter-bar__chev',
          width: 14,
          height: 14,
          viewBox: '0 0 24 24',
          fill: 'none',
          'aria-hidden': 'true'
        }, [
          h('path', {
            d: this.open ? 'M6 14l6-6 6 6' : 'M6 10l6 6 6-6',
            stroke: 'currentColor',
            'stroke-width': '2',
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round'
          })
        ])
      ]))
    }

    const { class: attrClass, ...rest } = this.$attrs
    return h('div', {
      ...rest,
      class: [
        'list-filter-bar',
        attrClass,
        {
          'is-open': this.open,
          'is-flat': !collapsible
        }
      ]
    }, children)
  }
}
</script>

<style>
.list-filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  min-width: 0;
  max-width: 100%;
}

/* Desktop: wrappers disappear so the original controls stay in one row. */
@media (min-width: 769px) {
  .list-filter-bar__slot {
    display: contents;
  }

  .list-filter-bar__toggle {
    display: none !important;
  }
}

@media (max-width: 768px) {
  div.list-filter-bar.list-filter-bar.list-filter-bar {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    overflow: visible !important;
    width: 100%;
    gap: 8px;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-search {
    order: 0;
    flex: 1 1 auto;
    min-width: 0;
    max-width: calc(100% - 7.5rem);
  }

  div.list-filter-bar.list-filter-bar.is-flat .list-filter-bar__slot.is-search {
    flex: 1 1 100%;
    max-width: 100%;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-search > * {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    box-sizing: border-box;
  }

  div.list-filter-bar.list-filter-bar:not(.is-flat):not(.is-open) .list-filter-bar__slot.is-filter {
    display: none !important;
  }

  div.list-filter-bar.list-filter-bar.is-open .list-filter-bar__slot.is-filter,
  div.list-filter-bar.list-filter-bar.is-flat .list-filter-bar__slot.is-filter {
    display: block !important;
    order: 2;
    flex: 1 1 calc(50% - 8px);
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter > *,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter select,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter input,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter textarea,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter .filter-select,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-filter .filter-input,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-search > input,
  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-search > .search-input {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    box-sizing: border-box;
    font-size: 14px !important;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-action {
    display: block !important;
    order: 3;
    flex: 0 0 auto;
    width: auto !important;
    max-width: none !important;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__slot.is-action > * {
    width: auto !important;
    min-width: 0 !important;
    max-width: none !important;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__toggle {
    order: 1;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex: 0 0 auto;
    height: 40px;
    padding: 0 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__toggle.is-active {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #047857;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #059669;
  }

  div.list-filter-bar.list-filter-bar .list-filter-bar__chev {
    display: block;
  }
}
</style>
