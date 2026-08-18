import api from './index'

export const bkkApi = {
  getVacancies(params) {
    return api.get('/v1/bkk/vacancies', { params })
  },
  createVacancy(data) {
    return api.post('/v1/bkk/vacancies', data)
  },
  updateVacancy(id, data) {
    return api.put(`/v1/bkk/vacancies/${id}`, data)
  },
  deleteVacancy(id) {
    return api.delete(`/v1/bkk/vacancies/${id}`)
  },
  getApplications(params) {
    return api.get('/v1/bkk/applications', { params })
  },
  exportApplications(params) {
    return api.get('/v1/bkk/applications/export', { params, responseType: 'blob' })
  },
  createApplication(data) {
    return api.post('/v1/bkk/applications', data)
  },
  updateApplication(id, data) {
    return api.put(`/v1/bkk/applications/${id}`, data)
  },
  deleteApplication(id) {
    return api.delete(`/v1/bkk/applications/${id}`)
  },
  myVacancies(params) {
    return api.get('/v1/bkk/my/vacancies', { params })
  },
  myApplications() {
    return api.get('/v1/bkk/my/applications')
  },
  applyMyself(data) {
    return api.post('/v1/bkk/my/applications', data)
  },
}
