import api from './index'

export const alumniApi = {
  getList(params) {
    return api.get('/v1/alumni', { params })
  },
  getGraduationYears(params) {
    return api.get('/v1/alumni/graduation-years', { params })
  },
  graduate(id, data = {}) {
    return api.post(`/v1/student/${id}/graduate`, data)
  },
  graduateBulk(data) {
    return api.post('/v1/student/graduate-bulk', data)
  },
  revokeGraduation(id, data = {}) {
    return api.post(`/v1/student/${id}/revoke-graduation`, data)
  },
  revokeGraduationBulk(data) {
    return api.post('/v1/student/revoke-graduation-bulk', data)
  },
  // Tracking destinasi alumni (lanjut sekolah/kuliah/kerja/dll)
  getDestinationTypes() {
    return api.get('/v1/alumni/destination-types')
  },
  getDestinationsByStudent(studentId) {
    return api.get(`/v1/alumni/students/${studentId}/destinations`)
  },
  storeDestination(data) {
    return api.post('/v1/alumni-destinations', data)
  },
  updateDestination(id, data) {
    return api.put(`/v1/alumni-destinations/${id}`, data)
  },
  approveDestination(id) {
    return api.post(`/v1/alumni-destinations/${id}/approve`)
  },
  rejectDestination(id) {
    return api.post(`/v1/alumni-destinations/${id}/reject`)
  },
  destroyDestination(id) {
    return api.delete(`/v1/alumni-destinations/${id}`)
  }
}
