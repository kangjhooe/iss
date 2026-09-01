const SCROLL_KEY = 'iss.sidebar.navScrollTop'
const EXPANDED_KEY = 'iss.sidebar.expandedGroups'

export function readSidebarScrollTop() {
  try {
    const raw = sessionStorage.getItem(SCROLL_KEY)
    const n = raw != null ? Number(raw) : 0
    return Number.isFinite(n) && n >= 0 ? n : 0
  } catch {
    return 0
  }
}

export function writeSidebarScrollTop(top) {
  try {
    sessionStorage.setItem(SCROLL_KEY, String(Math.max(0, Math.round(top))))
  } catch {
    /* ignore quota / private mode */
  }
}

export function readExpandedGroups() {
  try {
    const raw = sessionStorage.getItem(EXPANDED_KEY)
    if (!raw) return new Set()
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? new Set(parsed.filter((k) => typeof k === 'string')) : new Set()
  } catch {
    return new Set()
  }
}

export function writeExpandedGroups(keys) {
  try {
    sessionStorage.setItem(EXPANDED_KEY, JSON.stringify([...keys]))
  } catch {
    /* ignore */
  }
}

export function scrollActiveNavItemIntoView(navMenuEl) {
  if (!navMenuEl) return
  const active = navMenuEl.querySelector('.nav-subitem--active, .nav-item.router-link-active')
  if (!active || typeof active.scrollIntoView !== 'function') return
  active.scrollIntoView({ block: 'nearest', inline: 'nearest' })
}
