import api from './index'

/**
 * API publik untuk halaman sekolah (landing by NPSN): profil institusi, buku tamu.
 * Juga statistik & daftar instansi terbaru untuk halaman awal.
 * submitGuestVisit: kirim FormData (sama dengan admin) agar bisa lampirkan foto opsional.
 */
export const schoolPublicApi = {
  getInstitution(npsn) {
    return api.get('/v1/public/school', { params: { npsn } })
  },
  /**
   * Lookup NPSN ke data referensi Kemendikbud (untuk sekolah asal PPDB).
   * Mengembalikan { valid, name, address, in_system, institution? }.
   */
  lookupNpsnReferensi(npsn) {
    return api.get('/v1/public/npsn-lookup', { params: { npsn } })
  },
  submitGuestVisit(formData) {
    return api.post('/v1/public/guest-visit', formData)
  },
  /** Statistik untuk halaman awal: jumlah instansi bergabung */
  getStats() {
    return api.get('/v1/public/stats')
  },
  /** Daftar instansi baru bergabung (untuk slider), limit default 10, max 20 */
  getRecentInstitutions(limit = 10) {
    return api.get('/v1/public/institutions/recent', { params: { limit } })
  },
}
