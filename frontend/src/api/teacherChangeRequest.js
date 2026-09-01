import api from './index'

export const teacherChangeRequestApi = {
  getAllowedFields() {
    return api.get('/v1/teacher-change-requests/allowed-fields')
  },
  updateMyProfile(data) {
    return api.put('/v1/teacher/profile', data)
  },
  uploadMyPhoto(formData) {
    return api.post('/v1/teacher/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  deleteMyPhoto() {
    return api.delete('/v1/teacher/profile/photo')
  },
  getAll(params) {
    return api.get('/v1/teacher-change-requests', { params })
  },
  get(id) {
    return api.get(`/v1/teacher-change-requests/${id}`)
  },
  create(data) {
    return api.post('/v1/teacher-change-requests', data)
  },
  approve(id, data) {
    return api.post(`/v1/teacher-change-requests/${id}/approve`, data)
  },
  getPendingCount() {
    return api.get('/v1/teacher-change-requests/pending-count')
  }
}
