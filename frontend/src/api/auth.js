import api from './index'

export const authApi = {
  register(data) {
    return api.post('/register', data)
  },
  login(data) {
    return api.post('/login', data)
  },
  logout() {
    return api.post('/logout')
  },
  me() {
    return api.get('/me')
  }
}
