import api from './index'

export const gradeBookApi = {
  getAll(params) {
    return api.get('/v1/grades', { params })
  },
  getByClassSubjectSemester(params) {
    return api.get('/v1/grades/by-class-subject-semester', { params })
  },
  getByStudentSemester(params) {
    return api.get('/v1/grades/by-student-semester', { params })
  },
  export(params) {
    return api.get('/v1/grades/export', { params, responseType: 'blob' })
  },
  exportStudentRaport(params) {
    return api.get('/v1/grades/export-student-raport', { params, responseType: 'blob' })
  },
  bulkSave(data) {
    return api.post('/v1/grades/bulk', data)
  },
  get(id) {
    return api.get(`/v1/grades/${id}`)
  },
  create(data) {
    return api.post('/v1/grades', data)
  },
  update(id, data) {
    return api.put(`/v1/grades/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/grades/${id}`)
  },
}
