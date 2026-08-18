import api from './index'

export const programKeahlianApi = {
  getAll(params) {
    return api.get('/v1/program-keahlian', { params })
  },
  get(id) {
    return api.get(`/v1/program-keahlian/${id}`)
  },
  create(data) {
    return api.post('/v1/program-keahlian', data)
  },
  update(id, data) {
    return api.put(`/v1/program-keahlian/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/program-keahlian/${id}`)
  },
}
