import api from './index'

export const studentApi = {
  getAll(params) {
    return api.get('/v1/student', { params })
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
  }
}
