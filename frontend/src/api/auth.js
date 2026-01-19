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
  forgotPassword(email) {
    return api.post('/v1/forgot-password', { email })
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
  }
}
