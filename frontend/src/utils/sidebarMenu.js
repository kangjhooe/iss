/** Flatten sidebar menu entries for search & pins. */

export const MAX_SIDEBAR_PINS = 5

/**
 * @param {Array<{ type: string, label?: string, to?: string, key?: string, maturity?: string, visibleChildren?: Array<{ label: string, to: string }> }>} entries
 * @returns {Array<{ id: string, pinId: string, label: string, to: string, groupLabel: string | null, maturity?: string }>}
 */
export function flattenMenuEntries(entries) {
  const items = []
  for (const entry of entries) {
    if (entry.type === 'link' && entry.to) {
      items.push({
        id: entry.to,
        pinId: entry.to,
        label: entry.label,
        to: entry.to,
        groupLabel: null,
        maturity: entry.maturity,
      })
    } else if (entry.type === 'group' && entry.visibleChildren?.length) {
      for (const child of entry.visibleChildren) {
        if (!child.to) continue
        items.push({
          id: child.to,
          pinId: child.to,
          label: child.label,
          to: child.to,
          groupLabel: entry.label,
          maturity: entry.maturity,
        })
      }
    }
  }
  return items
}

/**
 * @param {string} query
 * @param {ReturnType<typeof flattenMenuEntries>} items
 */
export function filterMenuItems(query, items) {
  const q = query.trim().toLowerCase()
  if (!q) return items.slice(0, 50)
  return items.filter((item) => {
    const hay = `${item.label} ${item.groupLabel || ''}`.toLowerCase()
    return hay.includes(q)
  }).slice(0, 30)
}
