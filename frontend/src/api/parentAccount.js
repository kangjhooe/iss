import api from './index'

export const parentAccountApi = {
  list(params) {
    return api.get('/v1/parent-accounts', { params })
  },
  candidates(params) {
    return api.get('/v1/parent-accounts/candidates', { params })
  },
  get(id) {
    return api.get(`/v1/parent-accounts/${id}`)
  },
  create(data) {
    return api.post('/v1/parent-accounts', data)
  },
  update(id, data) {
    return api.put(`/v1/parent-accounts/${id}`, data)
  },
  link(id, data) {
    return api.post(`/v1/parent-accounts/${id}/link`, data)
  },
  unlink(id, studentId) {
    return api.delete(`/v1/parent-accounts/${id}/links/${studentId}`)
  },
  resetPassword(id, data = {}) {
    return api.post(`/v1/parent-accounts/${id}/reset-password`, data)
  },
}
