import api from './index'

export const employeeLeaveApi = {
  meta() {
    return api.get('/v1/employee-leaves/meta')
  },
  getAll(params) {
    return api.get('/v1/employee-leaves', { params })
  },
  getMy(params) {
    return api.get('/v1/employee-leaves/my', { params })
  },
  get(id) {
    return api.get(`/v1/employee-leaves/${id}`)
  },
  create(data) {
    return api.post('/v1/employee-leaves', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  createMy(data) {
    return api.post('/v1/employee-leaves/my', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  decide(id, data) {
    return api.post(`/v1/employee-leaves/${id}/decide`, data)
  },
  cancel(id) {
    return api.post(`/v1/employee-leaves/${id}/cancel`)
  },
}

export const employeeDecreeApi = {
  meta() {
    return api.get('/v1/employee-decrees/meta')
  },
  getAll(params) {
    return api.get('/v1/employee-decrees', { params })
  },
  get(id) {
    return api.get(`/v1/employee-decrees/${id}`)
  },
  create(data) {
    return api.post('/v1/employee-decrees', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  update(id, data) {
    return api.post(`/v1/employee-decrees/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  delete(id) {
    return api.delete(`/v1/employee-decrees/${id}`)
  },
  download(id) {
    return api.get(`/v1/employee-decrees/${id}/download`, { responseType: 'blob' })
  },
}

export const structuralPositionApi = {
  listMaster() {
    return api.get('/v1/structural-positions')
  },
  getAll(params) {
    return api.get('/v1/employee-structural-positions', { params })
  },
  get(id) {
    return api.get(`/v1/employee-structural-positions/${id}`)
  },
  assign(data) {
    return api.post('/v1/employee-structural-positions', data)
  },
  update(id, data) {
    return api.put(`/v1/employee-structural-positions/${id}`, data)
  },
  end(id, data) {
    return api.post(`/v1/employee-structural-positions/${id}/end`, data)
  },
  delete(id) {
    return api.delete(`/v1/employee-structural-positions/${id}`)
  },
}

export const careerHistoryApi = {
  get(employeeId) {
    return api.get(`/v1/employee-career-history/${employeeId}`)
  },
}
