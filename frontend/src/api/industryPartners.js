import api from './index'

export const industryPartnersApi = {
  getAll(params) {
    return api.get('/v1/industry-partners', { params })
  },
  get(id) {
    return api.get(`/v1/industry-partners/${id}`)
  },
  create(data) {
    return api.post('/v1/industry-partners', data)
  },
  update(id, data) {
    return api.put(`/v1/industry-partners/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/industry-partners/${id}`)
  },
}
