import api from './index'

export const teachingJournalApi = {
  getAll(params) {
    return api.get('/v1/teaching-journals', { params })
  },
  get(id) {
    return api.get(`/v1/teaching-journals/${id}`)
  },
  create(data) {
    return api.post('/v1/teaching-journals', data)
  },
  update(id, data) {
    return api.put(`/v1/teaching-journals/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/teaching-journals/${id}`)
  },
  export(params) {
    return api.get('/v1/teaching-journals/export', { params, responseType: 'blob' })
  },
}
