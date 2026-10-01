import api from './index'

export const subjectCatalogApi = {
  getMeta() {
    return api.get('/v1/subject-catalog/meta')
  },
  getAll(params) {
    return api.get('/v1/subject-catalog', { params })
  },
  get(id) {
    return api.get(`/v1/subject-catalog/${id}`)
  },
  create(data) {
    return api.post('/v1/subject-catalog', data)
  },
  update(id, data) {
    return api.put(`/v1/subject-catalog/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/subject-catalog/${id}`)
  },
}
