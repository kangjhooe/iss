import api from './index'

export const counselingApi = {
  getAll(params) {
    return api.get('/v1/counseling', { params })
  },
  getCounselors() {
    return api.get('/v1/counseling/counselors')
  },
  get(id) {
    return api.get(`/v1/counseling/${id}`)
  },
  create(data) {
    return api.post('/v1/counseling', data)
  },
  update(id, data) {
    return api.put(`/v1/counseling/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/counseling/${id}`)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/counseling/by-student/${studentId}`, { params })
  },
  getStats(params) {
    return api.get('/v1/counseling/stats', { params })
  },
  getUpcoming(params) {
    return api.get('/v1/counseling/upcoming', { params })
  },
  export(params) {
    return api.get('/v1/counseling/export', { params, responseType: 'blob' })
  },
}

export const counselingTypeApi = {
  getAll(params) {
    return api.get('/v1/counseling-types', { params })
  },
  get(id) {
    return api.get(`/v1/counseling-types/${id}`)
  },
  create(data) {
    return api.post('/v1/counseling-types', data)
  },
  update(id, data) {
    return api.put(`/v1/counseling-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/counseling-types/${id}`)
  },
}
