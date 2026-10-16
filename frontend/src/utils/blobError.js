/**
 * Parse error message from axios blob response (e.g. failed PDF/Excel export).
 */
export async function parseBlobError(err, fallback = 'Terjadi kesalahan.') {
  const data = err?.response?.data
  if (!(data instanceof Blob)) {
    return err?.formattedMessage
      || err?.response?.data?.message
      || Object.values(err?.response?.data?.errors || {})?.[0]?.[0]
      || fallback
  }

  try {
    const text = await data.text()
    const trimmed = text.trimStart()
    if (trimmed.startsWith('<!DOCTYPE') || trimmed.startsWith('<html') || trimmed.startsWith('<HTML')) {
      return 'Server mengembalikan halaman HTML, bukan berkas. Periksa URL API / rewrite server.'
    }
    const json = JSON.parse(text)
    return json?.message
      || Object.values(json?.errors || {})?.[0]?.[0]
      || fallback
  } catch {
    return fallback
  }
}

/**
 * True jika blob responseType ternyata HTML/JSON (sering dari SPA fallback rewrite).
 */
export async function isNonFileBlobResponse(response) {
  if (!response || response.config?.responseType !== 'blob') return false
  if (!(response.data instanceof Blob)) return false

  const ct = String(response.headers?.['content-type'] || '').toLowerCase()
  if (ct.includes('text/html') || ct.includes('application/json')) {
    return true
  }

  // Content-Type menyesatkan / kosong: sniff awalan body
  if (!ct || ct.includes('octet-stream') || ct.includes('text/plain')) {
    const head = (await response.data.slice(0, 64).text()).trimStart()
    if (
      head.startsWith('<!DOCTYPE')
      || head.startsWith('<html')
      || head.startsWith('<HTML')
      || head.startsWith('{')
      || head.startsWith('{"')
    ) {
      return true
    }
  }

  return false
}
