import api from './index'

export const feedbackTicketApi = {
  getAll(params) {
    return api.get('/v1/feedback-tickets', { params })
  },
  get(id) {
    return api.get(`/v1/feedback-tickets/${id}`)
  },
  create(data) {
    return api.post('/v1/feedback-tickets', data)
  },
  update(id, data) {
    return api.post(`/v1/feedback-tickets/${id}/status`, data)
  },
  getOpenCount() {
    return api.get('/v1/feedback-tickets/open-count')
  }
}
