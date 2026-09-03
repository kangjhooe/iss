/**
 * Format angka untuk tampilan di input uang (locale id-ID).
 * @param {number|string|null|undefined} value
 * @param {{ decimals?: number }} [options]
 */
export function formatMoneyInput(value, { decimals = 0 } = {}) {
  if (value === '' || value == null) return ''
  const n = Number(value)
  if (!Number.isFinite(n)) return ''
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: decimals,
  }).format(n)
}

/**
 * Parse teks input uang menjadi angka untuk model/API.
 * @param {string} text
 * @param {{ decimals?: number }} [options]
 * @returns {number|null}
 */
export function parseMoneyInput(text, { decimals = 0 } = {}) {
  const raw = String(text ?? '').trim()
  if (!raw) return null

  if (decimals === 0) {
    const digits = raw.replace(/\D/g, '')
    return digits === '' ? null : Number(digits)
  }

  const normalized = raw.replace(/\./g, '').replace(',', '.')
  const n = parseFloat(normalized)
  return Number.isFinite(n) ? n : null
}
