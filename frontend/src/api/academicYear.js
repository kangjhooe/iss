import api from './index'

export const academicYearApi = {
  getAll(params) {
    return api.get('/v1/academic-years', { params })
  },
  get(id) {
    return api.get(`/v1/academic-years/${id}`)
  },
  create(data) {
    return api.post('/v1/academic-years', data)
  },
  update(id, data) {
    return api.put(`/v1/academic-years/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/academic-years/${id}`)
  },
  getActive() {
    return api.get('/v1/academic-years/active')
  },
  getCurrent() {
    return api.get('/v1/academic-years/current')
  },
  activate(id) {
    return api.post(`/v1/academic-years/${id}/activate`)
  }
}
