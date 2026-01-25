import api from './index'

export const permissionApi = {
  getAll() {
    return api.get('/v1/permissions')
  },
  getTeachers(params) {
    return api.get('/v1/permissions/teachers', { params })
  },
  updateUserPermissions(userId, permissionKeys) {
    return api.put(`/v1/permissions/users/${userId}`, { permission_keys: permissionKeys })
  }
}
