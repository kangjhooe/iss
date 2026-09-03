function toPdfBlob(source) {
  if (source instanceof Blob) {
    return source.type && source.type !== 'application/pdf'
      ? new Blob([source], { type: 'application/pdf' })
      : source
  }
  const data = source?.data ?? source
  if (data instanceof Blob) {
    return data.type && data.type !== 'application/pdf'
      ? new Blob([data], { type: 'application/pdf' })
      : data
  }
  return new Blob([data], { type: 'application/pdf' })
}

/**
 * Buka PDF di tab preview baru (bukan unduh langsung).
 * @param {Blob|{ data: Blob|ArrayBuffer }} source — Blob atau respons axios (responseType: blob)
 * @param {string} filename — judul tab / nama file
 * @returns {boolean} false jika bukan PDF atau popup diblokir
 */
export function openPdfBlob(source, filename = 'document.pdf') {
  const blob = toPdfBlob(source)

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
