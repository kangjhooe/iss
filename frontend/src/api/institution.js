import api from './index'

export const institutionApi = {
  getAll(params) {
    return api.get('/institution', { params })
  },
  get(id) {
    return api.get(`/institution/${id}`)
  },
  create(data) {
    return api.post('/institution', data)
  },
  update(id, data) {
    return api.put(`/institution/${id}`, data)
  },
  delete(id) {
    return api.delete(`/institution/${id}`)
  },
  getMy() {
    return api.get('/institution/my')
  }
}
