import api from './index'

export const classApi = {
  getAll(params) {
    return api.get('/v1/class', { params })
  },
  get(id) {
    return api.get(`/v1/class/${id}`)
  },
  create(data) {
    return api.post('/v1/class', data)
  },
  update(id, data) {
    return api.put(`/v1/class/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/class/${id}`)
  },
  addStudents(id, studentIds) {
    return api.post(`/v1/class/${id}/students`, { student_ids: studentIds })
  },
  getAvailableStudents(id, params) {
    return api.get(`/v1/class/${id}/available-students`, { params })
  },
  getStudents(id, params) {
    return api.get(`/v1/class/${id}/students`, { params })
  },
  removeStudent(classId, studentId) {
    return api.delete(`/v1/class/${classId}/students/${studentId}`)
  }
}
