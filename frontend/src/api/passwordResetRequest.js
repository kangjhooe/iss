import api from './index'

export const passwordResetRequestApi = {
  submit(data) {
    return api.post('/v1/password-reset-requests', data)
  },
  getAll(params) {
    return api.get('/v1/password-reset-requests', { params })
  },
  getPendingCount() {
    return api.get('/v1/password-reset-requests/pending-count')
  },
  process(id) {
    return api.post(`/v1/password-reset-requests/${id}/process`)
  },
  reject(id, data = {}) {
    return api.post(`/v1/password-reset-requests/${id}/reject`, data)
  },
}
