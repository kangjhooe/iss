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
  }
}

// Keep teacherApi for backward compatibility
export const teacherApi = employeeApi
