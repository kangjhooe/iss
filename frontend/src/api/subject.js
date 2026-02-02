import api from './index'

export const subjectApi = {
  getAll(params) {
    return api.get('/v1/subjects', { params })
  },
  get(id) {
    return api.get(`/v1/subjects/${id}`)
  },
  create(data) {
    return api.post('/v1/subjects', data)
  },
  update(id, data) {
    return api.put(`/v1/subjects/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/subjects/${id}`)
  },
}
