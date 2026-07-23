import api from './index'

export const waliKelasApi = {
  getStudent(classId, studentId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/students/${studentId}`)
  },
  getDashboard(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/dashboard`)
  },
  getNotes(classId, studentId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes`)
  },
  createNote(classId, studentId, data) {
    return api.post(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes`, data)
  },
  updateNote(classId, studentId, noteId, data) {
    return api.put(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes/${noteId}`, data)
  },
  deleteNote(classId, studentId, noteId) {
    return api.delete(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes/${noteId}`)
  },
  getSchedule(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/schedule`, { params })
  },
  exportSchedulePdf(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/schedule/export-pdf`, {
      params,
      responseType: 'blob',
    })
  },
  getViolations(params) {
    return api.get('/v1/teacher/wali/violations', { params })
  },
  proposeViolation(data) {
    return api.post('/v1/teacher/wali/violations', data)
  },
  getViolationTypes(params) {
    return api.get('/v1/teacher/wali/violation-types', { params })
  },
  getAchievements(params) {
    return api.get('/v1/teacher/wali/achievements', { params })
  },
  proposeAchievement(data) {
    return api.post('/v1/teacher/wali/achievements', data)
  },
  getAchievementTypes(params) {
    return api.get('/v1/teacher/wali/achievement-types', { params })
  },
  getMutations(params) {
    return api.get('/v1/teacher/wali/mutations', { params })
  },
  proposeMutation(data) {
    return api.post('/v1/teacher/wali/mutations', data)
  },
  exportRoster(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/roster`, { responseType: 'blob' })
  },
  exportContacts(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/contacts`, {
      params,
      responseType: 'blob',
    })
  },
  exportAttendance(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/attendance`, {
      params,
      responseType: 'blob',
    })
  },
}
