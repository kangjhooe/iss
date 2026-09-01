import api from '@/api'

export const suratService = {
  list(params = {}) {
    return api.get('/v1/surat', { params })
  },
  get(id) {
    return api.get(`/v1/surat/${id}`)
  },
  create(data) {
    return api.post('/v1/surat', data)
  },
  update(id, data) {
    return api.put(`/v1/surat/${id}`, data)
  },
  remove(id) {
    return api.delete(`/v1/surat/${id}`)
  },
  generate(data) {
    return api.post('/v1/surat/generate', data)
  },
  exportPdf(id) {
    return api.get(`/v1/surat/${id}/pdf`, { responseType: 'blob' })
  },
  print(id) {
    return api.get(`/v1/surat/${id}/print`, { responseType: 'blob' })
  },
  uploadImage(file) {
    const formData = new FormData()
    formData.append('upload', file)
    return api.post('/v1/surat/upload-image', formData)
  },
  layout(id) {
    return api.get(`/v1/surat/${id}/layout`)
  },
  publish(id, data = {}) {
    return api.post(`/v1/surat/${id}/terbitkan`, data)
  },
  letterheadContext() {
    return api.get('/v1/surat/letterhead-context')
  },
  classes(params = {}) {
    return api.get('/v1/surat/classes', { params })
  },
  students(params = {}) {
    return api.get('/v1/surat/students', { params })
  },
  employees(params = {}) {
    return api.get('/v1/surat/employees', { params })
  }
}

export default suratService
