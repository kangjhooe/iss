import api from './index'

/**
 * API panduan publik (tanpa auth).
 */
export const guidesPublicApi = {
  downloadAdminPdf() {
    return api.get('/v1/public/guides/admin.pdf', { responseType: 'blob' })
  },
  downloadGuruPdf() {
    return api.get('/v1/public/guides/guru.pdf', { responseType: 'blob' })
  },
  downloadSiswaPdf() {
    return api.get('/v1/public/guides/siswa.pdf', { responseType: 'blob' })
  },
  downloadOrangTuaPdf() {
    return api.get('/v1/public/guides/orang-tua.pdf', { responseType: 'blob' })
  },
}
