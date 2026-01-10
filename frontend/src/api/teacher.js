import api from './index'

export const employeeApi = {
  getAll(params) {
    return api.get('/v1/employee', { params })
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
