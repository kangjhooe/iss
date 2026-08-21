import api from './index'

/**
 * API PPDB publik (tanpa auth). Untuk halaman daftar PPDB oleh calon siswa.
 */
export const ppdbPublicApi = {
  getOpenPeriods(params) {
    return api.get('/v1/public/ppdb/periods', { params })
  },
  getOpenChannels(params) {
    return api.get('/v1/public/ppdb/channels', { params })
  },
  getPrefill(params) {
    return api.get('/v1/public/ppdb/prefill', { params })
  },
  register(data) {
    return api.post('/v1/public/ppdb/register', data)
  },
  checkResult(params) {
    return api.get('/v1/public/ppdb/check-result', { params })
  },
  confirmReRegistration(params) {
    return api.post('/v1/public/ppdb/confirm-re-registration', null, { params })
  },
  uploadDocument(formData) {
    return api.post('/v1/public/ppdb/documents', formData)
  },
  getDocumentChecklist(params) {
    return api.get('/v1/public/ppdb/document-checklist', { params })
  },
  downloadRegistrationSlip(params) {
    return api.get('/v1/public/ppdb/registration-slip', { params, responseType: 'blob' })
  },
}
