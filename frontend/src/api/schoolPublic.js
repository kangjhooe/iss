import api from './index'

/**
 * API publik untuk halaman sekolah (landing by NPSN): profil institusi, buku tamu.
 * submitGuestVisit: kirim FormData (sama dengan admin) agar bisa lampirkan foto opsional.
 */
export const schoolPublicApi = {
  getInstitution(npsn) {
    return api.get('/v1/public/school', { params: { npsn } })
  },
  submitGuestVisit(formData) {
    return api.post('/v1/public/guest-visit', formData)
  },
}
