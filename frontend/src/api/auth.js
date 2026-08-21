import api from './index'

export const authApi = {
  register(data) {
    return api.post('/v1/register', data)
  },
  login(data) {
    return api.post('/v1/login', data)
  },
  logout() {
    return api.post('/v1/logout')
  },
  me() {
    return api.get('/v1/me')
  },
  updateProfile(data) {
    return api.put('/v1/me', data)
  },
  changePassword(data) {
    return api.put('/v1/me/password', data)
  },
  forgotPassword(email) {
    return api.post('/v1/forgot-password', { email })
  },
  requestPasswordReset(data) {
    return api.post('/v1/password-reset-requests', data)
  },
  resetPassword(data) {
    return api.post('/v1/reset-password', data)
  },
  verifyEmail(data) {
    return api.post('/v1/verify-email', data)
  },
  resendVerificationEmail(email) {
    return api.post('/v1/resend-verification', { email })
  },
  refreshToken() {
    return api.post('/v1/refresh-token')
  },
  startImpersonate(adminId) {
    return api.post(`/v1/super-admin/institution-admins/${adminId}/impersonate`)
  },
  stopImpersonate() {
    return api.post('/v1/super-admin/impersonate/stop')
  },
  switchInstitution(institutionId) {
    return api.post('/v1/me/switch-institution', { institution_id: institutionId })
  }
}
