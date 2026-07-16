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

  // Activity
  getSessions(id, params) {
    return api.get(`/v1/extracurriculars/${id}/sessions`, { params })
  },
  createSession(id, data) {
    return api.post(`/v1/extracurriculars/${id}/sessions`, data)
  },
  updateSession(id, sessionId, data) {
    return api.put(`/v1/extracurriculars/${id}/sessions/${sessionId}`, data)
  },
  deleteSession(id, sessionId) {
    return api.delete(`/v1/extracurriculars/${id}/sessions/${sessionId}`)
  },
  getAttendances(id, sessionId) {
    return api.get(`/v1/extracurriculars/${id}/sessions/${sessionId}/attendances`)
  },
  saveAttendances(id, sessionId, data) {
    return api.put(`/v1/extracurriculars/${id}/sessions/${sessionId}/attendances`, data)
  },
  getGrades(id, params) {
    return api.get(`/v1/extracurriculars/${id}/grades`, { params })
  },
  saveGrades(id, data) {
    return api.put(`/v1/extracurriculars/${id}/grades`, data)
  },
  getReport(id, params) {
    return api.get(`/v1/extracurriculars/${id}/report`, { params })
  },
  exportReport(id, params) {
    return api.get(`/v1/extracurriculars/${id}/report/export`, {
      params,
      responseType: 'blob',
    })
  },
  exportReportPdf(id, params) {
    return api.get(`/v1/extracurriculars/${id}/report/pdf`, {
      params,
      responseType: 'blob',
    })
  },
}
