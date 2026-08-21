import api from './index'

export const employeeApi = {
  getAll(params) {
    return api.get('/v1/employee', { params })
  },
  searchByNik(nik) {
    return api.get('/v1/employee/search', { params: { nik } })
  },
  getDashboard() {
    return api.get('/v1/teacher/dashboard')
  },
  getTodaySessions(params) {
    return api.get('/v1/teacher/today-sessions', { params })
  },
  exportTodaySessionsPdf(params = {}) {
    return api.get('/v1/teacher/today-sessions/export-pdf', { params, responseType: 'blob' })
  },
  getTeachingLoad(params) {
    return api.get('/v1/teacher/teaching-load', { params })
  },
  getHomeroomClassStudents(classId, params) {
    return api.get(`/v1/teacher/dashboard/classes/${classId}/students`, { params })
  },
  get(id) {
    return api.get(`/v1/employee/${id}`)
  },
  create(data) {
    return api.post('/v1/employee', data)
  },
  update(id, data) {
    return api.put(`/v1/employee/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/employee/${id}`)
  },
  restore(id) {
    return api.post(`/v1/employee/${id}/restore`)
  },
  forceDelete(id) {
    return api.delete(`/v1/employee/${id}/force`)
  },
  import(data) {
    return api.post('/v1/employee/import', { employees: data })
  },
  requestAssignment(employeeId, data) {
    return api.post(`/v1/employee/${employeeId}/assignments`, data)
  },
  getPendingAssignments(params) {
    return api.get('/v1/employee-assignments/pending', { params })
  },
  updateAssignment(assignmentId, data) {
    return api.put(`/v1/employee-assignments/${assignmentId}`, data)
  },
  approveAssignment(assignmentId, data) {
    return api.post(`/v1/employee-assignments/${assignmentId}/approve`, data)
  },
  rejectAssignment(assignmentId, data) {
    return api.post(`/v1/employee-assignments/${assignmentId}/reject`, data)
  },
  endAssignment(assignmentId, data) {
    return api.post(`/v1/employee-assignments/${assignmentId}/end`, data)
  },
  uploadDocument(employeeId, formData) {
    return api.post(`/v1/employee/${employeeId}/documents`, formData)
  },
  deleteDocument(employeeId, documentId) {
    return api.delete(`/v1/employee/${employeeId}/documents/${documentId}`)
  },
  downloadDocument(employeeId, documentId) {
    return api.get(`/v1/employee/${employeeId}/documents/${documentId}/download`, {
      responseType: 'blob'
    })
  },
  resetPasswordByAdmin(employeeId, data) {
    return api.post(`/v1/employee/${employeeId}/reset-password`, data)
  },
  export(params = {}) {
    return api.get('/v1/employee/export', { params })
  },
  exportPdf(params = {}) {
    return api.get('/v1/employee/export/pdf', {
      params,
      responseType: 'blob'
    })
  }
}

// Keep teacherApi for backward compatibility
export const teacherApi = employeeApi
