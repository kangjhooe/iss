import api from '@/api'

export const templateService = {
  list(params = {}) {
    return api.get('/v1/templates', { params })
  },
  get(id) {
    return api.get(`/v1/templates/${id}`)
  },
  create(data) {
    return api.post('/v1/templates', data)
  },
  update(id, data) {
    return api.put(`/v1/templates/${id}`, data)
  },
  remove(id) {
    return api.delete(`/v1/templates/${id}`)
  },
  toggleStatus(id) {
    return api.post(`/v1/templates/${id}/toggle-status`)
  },
  fork(id, data = {}) {
    return api.post(`/v1/templates/${id}/fork`, data)
  },
  placeholders() {
    return api.get('/v1/templates/placeholders')
  }
}

export default templateService
