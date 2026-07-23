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
  cancel(id, data = {}) {
    return api.post(`/v1/student-mutations/${id}/cancel`, data)
  },
  decideCancel(id, data) {
    return api.post(`/v1/student-mutations/${id}/cancel-decision`, data)
  },
  searchTargetInstitutions(q) {
    return api.get('/v1/student-mutations/target-institutions', { params: { q } })
  },
  searchOriginInstitutions(q) {
    return api.get('/v1/student-mutations/origin-institutions', { params: { q } })
  },
  lookupStudent(nisn) {
    return api.get('/v1/student-mutations/lookup-student', { params: { nisn } })
  },
  lookupStudentAtOrigin(originNpsn, nisn) {
    return api.get('/v1/student-mutations/lookup-student-at-origin', {
      params: { origin_npsn: originNpsn, nisn }
    })
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
   * Returns blob; PDF sebaiknya di-preview di tab baru, CSV di-download.
   */
  exportBukuMutasi(params = {}) {
    return api.get('/v1/student-mutations/export', { params, responseType: 'blob' })
  }
}
