import api from './index'

export const teacherMutationApi = {
  getAll(params) {
    return api.get('/v1/teacher-mutations', { params })
  },
  get(id) {
    return api.get(`/v1/teacher-mutations/${id}`)
  },
  create(data) {
    return api.post('/v1/teacher-mutations', data)
  },
  approve(id, data) {
    return api.post(`/v1/teacher-mutations/${id}/approve`, data)
  },
  cancel(id, data = {}) {
    return api.post(`/v1/teacher-mutations/${id}/cancel`, data)
  },
  decideCancel(id, data) {
    return api.post(`/v1/teacher-mutations/${id}/cancel-decision`, data)
  },
  searchTargetInstitutions(q) {
    return api.get('/v1/teacher-mutations/target-institutions', { params: { q } })
  },
  searchOriginInstitutions(q) {
    return api.get('/v1/teacher-mutations/origin-institutions', { params: { q } })
  },
  lookupTeacher(nuptk) {
    return api.get('/v1/teacher-mutations/lookup-teacher', { params: { nuptk } })
  },
  lookupTeacherAtOrigin(originNpsn, nuptk) {
    return api.get('/v1/teacher-mutations/lookup-teacher-at-origin', {
      params: { origin_npsn: originNpsn, nuptk }
    })
  },
  createPull(data) {
    return api.post('/v1/teacher-mutations/pull', data)
  },
  getReport(params) {
    return api.get('/v1/teacher-mutations/report', { params })
  },
  getHistoryByEmployee(employeeId) {
    return api.get(`/v1/teacher-mutations/by-employee/${employeeId}`)
  },
  getHistoryByNuptk(nuptk) {
    return api.get('/v1/teacher-mutations/history-by-nuptk', { params: { nuptk } })
  },
  /**
   * Export Buku Mutasi Guru (PDF atau CSV). Params: from, to, type ('all'|'in'|'out'), format ('pdf'|'csv').
   * Returns blob; PDF sebaiknya di-preview di tab baru, CSV di-download.
   */
  exportBukuMutasi(params = {}) {
    return api.get('/v1/teacher-mutations/export', { params, responseType: 'blob' })
  }
}
