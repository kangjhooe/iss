import api from './index'

export const studentApi = {
  getAll(params) {
    return api.get('/v1/student', { params })
  },
  export(params) {
    return api.get('/v1/student/export', { params })
  },
  get(id) {
    return api.get(`/v1/student/${id}`)
  },
  create(data) {
    return api.post('/v1/student', data)
  },
  update(id, data) {
    return api.put(`/v1/student/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/student/${id}`)
  },
  restore(id) {
    return api.post(`/v1/student/${id}/restore`)
  },
  forceDelete(id) {
    return api.delete(`/v1/student/${id}/force`)
  },
  import(data) {
    return api.post('/v1/student/import', { students: data })
  },
  uploadDocument(id, formData) {
    return api.post(`/v1/student/${id}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  deleteDocument(id, documentId) {
    return api.delete(`/v1/student/${id}/documents/${documentId}`)
  },
  downloadDocument(id, documentId) {
    return api.get(`/v1/student/${id}/documents/${documentId}/download`, {
      responseType: 'blob'
    })
  },
  getBukuInduk(id) {
    return api.get(`/v1/student/${id}/buku-induk`)
  },
  downloadBukuIndukPdf(id) {
    return api.get(`/v1/student/${id}/buku-induk/pdf`, {
      responseType: 'blob'
    })
  },
  promote(data) {
    return api.post('/v1/student/promote', data)
  },
  ensureAccount(id) {
    return api.post(`/v1/student/${id}/ensure-account`)
  },
  resetPassword(id) {
    return api.post(`/v1/student/${id}/reset-password`)
  },
  accountStatus(params) {
    return api.get('/v1/student/account-status', { params })
  },
  ensureAccountsBulk(data) {
    return api.post('/v1/student/ensure-accounts-bulk', data)
  },
  getNisNumbering(params) {
    return api.get('/v1/student/nis-numbering', { params })
  },
  updateNisNumbering(data) {
    return api.post('/v1/student/nis-numbering', data)
  },
  generateNisBulk(data) {
    return api.post('/v1/student/generate-nis', data)
  },
  previewGenerateNis(data) {
    return api.post('/v1/student/generate-nis/preview', data)
  },
  generateNis(id) {
    return api.post(`/v1/student/${id}/generate-nis`)
  },
  feederAlumni(params) {
    return api.get('/v1/student/feeder-alumni', { params })
  },
  pullFromFeeder(data) {
    return api.post('/v1/student/pull-from-feeder', data)
  }
}
