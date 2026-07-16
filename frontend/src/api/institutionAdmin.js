import api from './index'

export const institutionAdminApi = {
  getAll(params) {
    return api.get('/v1/super-admin/institution-admins', { params })
  },
  create(data) {
    return api.post('/v1/super-admin/institution-admins', data)
  },
  resetPassword(id, data = {}) {
    return api.post(`/v1/super-admin/institution-admins/${id}/reset-password`, data)
  },
  updateStatus(id, isActive) {
    return api.post(`/v1/super-admin/institution-admins/${id}/status`, { is_active: isActive })
  },
  onboard(data) {
    return api.post('/v1/super-admin/onboard', data)
  }
}
