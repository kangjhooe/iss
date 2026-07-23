import api from './index'

export const gradeBookApi = {
  getAll(params) {
    return api.get('/v1/grades', { params })
  },
  getByClassSubjectSemester(params) {
    return api.get('/v1/grades/by-class-subject-semester', { params })
  },
  getCompleteness(params) {
    return api.get('/v1/grades/completeness', { params })
  },
  getBelowKkm(params) {
    return api.get('/v1/grades/remedials/below-kkm', { params })
  },
  getRemedials(params) {
    return api.get('/v1/grades/remedials', { params })
  },
  createRemedial(data) {
    return api.post('/v1/grades/remedials', data)
  },
  completeRemedial(id, data) {
    return api.post(`/v1/grades/remedials/${id}/complete`, data)
  },
  cancelRemedial(id, data) {
    return api.post(`/v1/grades/remedials/${id}/cancel`, data || {})
  },
  getByClassSemester(params) {
    return api.get('/v1/grades/by-class-semester', { params })
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
  exportClassRaport(params) {
    return api.get('/v1/grades/export-class-raport', { params, responseType: 'blob' })
  },
  exportClassRaportPdf(params) {
    return api.get('/v1/grades/export-class-raport-pdf', { params, responseType: 'blob' })
  },
  bulkSave(data) {
    return api.post('/v1/grades/bulk', data)
  },
  upsertKkm(data) {
    return api.post('/v1/grades/kkm', data)
  },
  upsertWeights(data) {
    return api.post('/v1/grades/weights', data)
  },
  exportPdf(params) {
    return api.get('/v1/grades/export-pdf', { params, responseType: 'blob' })
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
