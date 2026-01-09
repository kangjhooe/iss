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
  }
}
