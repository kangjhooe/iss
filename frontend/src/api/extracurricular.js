import api from './index'

export const extracurricularApi = {
  getAll(params) {
    return api.get('/v1/extracurriculars', { params })
  },
  get(id, params = {}) {
    return api.get(`/v1/extracurriculars/${id}`, { params })
  },
  create(data) {
    return api.post('/v1/extracurriculars', data)
  },
  update(id, data) {
    return api.put(`/v1/extracurriculars/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/extracurriculars/${id}`)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/extracurriculars/by-student/${studentId}`, { params })
  },
  getStudents(extracurricularId, params) {
    return api.get(`/v1/extracurriculars/${extracurricularId}/students`, { params })
  },
  exportParticipants(extracurricularId, params) {
    return api.get(`/v1/extracurriculars/${extracurricularId}/students/export`, {
      params,
      responseType: 'blob',
    })
  },
  getAvailableStudents(extracurricularId, params) {
    return api.get(`/v1/extracurriculars/${extracurricularId}/available-students`, { params })
  },
  addStudents(extracurricularId, data) {
    return api.post(`/v1/extracurriculars/${extracurricularId}/students`, data)
  },
  updateEnrollment(extracurricularId, enrollmentId, data) {
    return api.put(`/v1/extracurriculars/${extracurricularId}/enrollments/${enrollmentId}`, data)
  },
  removeStudent(extracurricularId, studentId, params) {
    return api.delete(`/v1/extracurriculars/${extracurricularId}/students/${studentId}`, { params })
  },
}
