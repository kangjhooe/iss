import api from './index'

export const institutionApi = {
  getAll(params) {
    return api.get('/v1/institution', { params })
  },
  get(id) {
    return api.get(`/v1/institution/${id}`)
  },
  create(data) {
    return api.post('/v1/institution', data)
  },
  update(id, data) {
    return api.put(`/v1/institution/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/institution/${id}`)
  },
  getMy() {
    return api.get('/v1/institution/my')
  },
  updateActiveAcademicYear(id, data) {
    return api.put(`/v1/institution/${id}/active-academic-year`, data)
  }
}
