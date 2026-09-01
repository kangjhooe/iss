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
    const json = JSON.parse(text)
    return json?.message
      || Object.values(json?.errors || {})?.[0]?.[0]
      || fallback
  } catch {
    return fallback
  }
}
