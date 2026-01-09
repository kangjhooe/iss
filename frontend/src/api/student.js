import api from './index'

export const studentApi = {
  getAll(params) {
    return api.get('/student', { params })
  },
  get(id) {
    return api.get(`/student/${id}`)
  },
  create(data) {
    return api.post('/student', data)
  },
  update(id, data) {
    return api.put(`/student/${id}`, data)
  },
  delete(id) {
    return api.delete(`/student/${id}`)
  }
}
