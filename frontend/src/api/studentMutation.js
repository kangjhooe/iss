import api from './index'

export const studentMutationApi = {
  getAll(params) {
    return api.get('/v1/student-mutations', { params })
  },
  get(id) {
    return api.get(`/v1/student-mutations/${id}`)
  },
  create(data) {
    return api.post('/v1/student-mutations', data)
  },
  approve(id, data) {
    return api.post(`/v1/student-mutations/${id}/approve`, data)
  },
  searchTargetInstitutions(q) {
    return api.get('/v1/student-mutations/target-institutions', { params: { q } })
  },
  searchOriginInstitutions(q) {
    return api.get('/v1/student-mutations/origin-institutions', { params: { q } })
  },
  createPull(data) {
    return api.post('/v1/student-mutations/pull', data)
  },
  getReport(params) {
    return api.get('/v1/student-mutations/report', { params })
  },
  getHistoryByStudent(studentId) {
    return api.get(`/v1/student-mutations/by-student/${studentId}`)
  },
  getHistoryByNisn(nisn) {
    return api.get('/v1/student-mutations/history-by-nisn', { params: { nisn } })
  },
  /**
   * Export Buku Mutasi (PDF atau CSV). Params: from, to, type ('all'|'in'|'out'), format ('pdf'|'csv').
   * Returns blob; gunakan responseType: 'blob' dan trigger download di frontend.
   */
  exportBukuMutasi(params = {}) {
    return api.get('/v1/student-mutations/export', { params, responseType: 'blob' })
  }
}
