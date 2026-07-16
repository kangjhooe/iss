import api from '@/api'

export const kopService = {
  list(params = {}) {
    return api.get('/v1/kop-surat', { params })
  },
  get(id) {
    return api.get(`/v1/kop-surat/${id}`)
  },
  create(formData) {
    return api.post('/v1/kop-surat', formData)
  },
  update(id, formData) {
    formData.append('_method', 'PUT')
    return api.post(`/v1/kop-surat/${id}`, formData)
  },
  remove(id) {
    return api.delete(`/v1/kop-surat/${id}`)
  }
}

export default kopService
