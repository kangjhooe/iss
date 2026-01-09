import api from './index'

export const teacherApi = {
  getAll(params) {
    return api.get('/v1/teacher', { params })
  },
  get(id) {
    return api.get(`/v1/teacher/${id}`)
  },
  create(data) {
    return api.post('/v1/teacher', data)
  },
  update(id, data) {
    return api.put(`/v1/teacher/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/teacher/${id}`)
  }
}
