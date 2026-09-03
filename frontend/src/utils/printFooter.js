import { appName } from '@/config/app'

/**
 * Format tanggal cetak: "2 September 2026 05:18"
 */
export function formatPrintFooterDate(date = new Date()) {
  const d = date instanceof Date ? date : new Date(date)
  const datePart = d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')

  return `${datePart} ${hours}:${minutes}`
}

/**
 * Baris footer standar PDF/cetak browser.
 */
export function formatPrintFooterLine(printedBy, printedAt = new Date()) {
  const by = (printedBy && String(printedBy).trim()) || 'Pengguna'
  const at = formatPrintFooterDate(printedAt)

  return `Dicetak oleh ${by} melalui ${appName} pada tanggal ${at}`
}
