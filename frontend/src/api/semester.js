import api from './index'

export const semesterApi = {
  getAll(params) {
    return api.get('/v1/semesters', { params })
  },
  get(id) {
    return api.get(`/v1/semesters/${id}`)
  },
  create(data) {
    return api.post('/v1/semesters', data)
  },
  update(id, data) {
    return api.put(`/v1/semesters/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/semesters/${id}`)
  },
  getByAcademicYear(academicYearId) {
    return api.get(`/v1/semesters/academic-year/${academicYearId}`)
  },
  getActive() {
    return api.get('/v1/semesters/active')
  },
  getActiveForAcademicYear(academicYearId) {
    return api.get(`/v1/semesters/academic-year/${academicYearId}/active`)
  },
  activate(id) {
    return api.post(`/v1/semesters/${id}/activate`)
  },
  autoGenerate(academicYearId) {
    return api.post(`/v1/semesters/academic-year/${academicYearId}/auto-generate`)
  }
}
