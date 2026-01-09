import api from './index'

export const teacherApi = {
  getAll(params) {
    return api.get('/teacher', { params })
  },
  get(id) {
    return api.get(`/teacher/${id}`)
  },
  create(data) {
    return api.post('/teacher', data)
  },
  update(id, data) {
    return api.put(`/teacher/${id}`, data)
  },
  delete(id) {
    return api.delete(`/teacher/${id}`)
  }
}
