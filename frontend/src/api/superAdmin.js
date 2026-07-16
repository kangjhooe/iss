import api from './index'

export const superAdminApi = {
  getDashboard() {
    return api.get('/v1/super-admin/dashboard')
  }
}
