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
  }
}
