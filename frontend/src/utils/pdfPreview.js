/**
 * Buka PDF di tab preview baru (bukan unduh langsung).
 */
export function openPdfBlob(res, filename = 'document.pdf') {
  const blob = res?.data instanceof Blob
    ? res.data
    : new Blob([res?.data ?? res], { type: 'application/pdf' })

  if (blob.type && blob.type !== 'application/pdf') {
    return false
  }

  const url = window.URL.createObjectURL(blob)
  const opened = window.open(url, '_blank', 'noopener,noreferrer')
  if (opened) {
    try {
      opened.document.title = filename
    } catch {
      // cross-origin / blocked — abaikan
    }
  }
  setTimeout(() => window.URL.revokeObjectURL(url), 60000)
  return !!opened
}
