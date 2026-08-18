import api from './index'

export const studentChangeRequestApi = {
  getMyProfile() {
    return api.get('/v1/student/profile')
  },
  updateMyProfile(data) {
    return api.put('/v1/student/profile', data)
  },
  getAll(params) {
    return api.get('/v1/student-change-requests', { params })
  },
  get(id) {
    return api.get(`/v1/student-change-requests/${id}`)
  },
  create(data) {
    return api.post('/v1/student-change-requests', data)
  },
  approve(id, data) {
    return api.post(`/v1/student-change-requests/${id}/approve`, data)
  },
  getPendingCount() {
    return api.get('/v1/student-change-requests/pending-count')
  },
  getAllowedFields() {
    return api.get('/v1/student-change-requests/allowed-fields')
  }
}
