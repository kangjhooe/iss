import api from './index'

export const institutionChangeRequestApi = {
  getAll(params) {
    return api.get('/v1/institution-change-requests', { params })
  },
  get(id) {
    return api.get(`/v1/institution-change-requests/${id}`)
  },
  create(data) {
    return api.post('/v1/institution-change-requests', data)
  },
  approve(id, data) {
    return api.post(`/v1/institution-change-requests/${id}/approve`, data)
  },
  getPendingCount() {
    return api.get('/v1/institution-change-requests/pending-count')
  }
}
