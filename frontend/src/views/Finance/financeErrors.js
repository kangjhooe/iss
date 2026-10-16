export function apiError(e, fallback = 'Terjadi kesalahan.') {
  return e?.formattedMessage
    || e?.response?.data?.message
    || Object.values(e?.response?.data?.errors || {})?.[0]?.[0]
    || fallback
}
