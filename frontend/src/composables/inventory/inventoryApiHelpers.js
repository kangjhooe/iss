/** Shared helpers for inventory API responses. */
export function safeArray(response) {
  if (response?.data?.data && Array.isArray(response.data.data)) return response.data.data
  if (Array.isArray(response?.data)) return response.data
  return []
}

export function parsePagination(
  response,
  fallback = { current_page: 1, last_page: 1, per_page: 15, total: 0 }
) {
  const meta = response?.data?.meta || response?.data?.data?.meta
  if (meta) {
    return {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      per_page: meta.per_page || fallback.per_page || 15,
      total: meta.total || 0
    }
  }
  return fallback
}

/** Nomor baris tabel inventaris (sesuai halaman pagination). */
export function inventoryRowNumber(meta, index) {
  const page = Number(meta?.current_page) || 1
  const perPage = Number(meta?.per_page) || 15
  return (page - 1) * perPage + index + 1
}
