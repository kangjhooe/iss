import api from './index'

export const uksApi = {
  getAll(params) {
    return api.get('/v1/uks/visits', { params })
  },
  getMy(params) {
    return api.get('/v1/uks/visits/my', { params })
  },
  getRecorders() {
    return api.get('/v1/uks/visits/recorders')
  },
  get(id) {
    return api.get(`/v1/uks/visits/${id}`)
  },
  create(data) {
    return api.post('/v1/uks/visits', data)
  },
  update(id, data) {
    return api.put(`/v1/uks/visits/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/uks/visits/${id}`)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/uks/visits/by-student/${studentId}`, { params })
  },
  getStats(params) {
    return api.get('/v1/uks/visits/stats', { params })
  },
  export(params) {
    return api.get('/v1/uks/visits/export', { params, responseType: 'blob' })
  },
}

export const uksMedicineApi = {
  getAll(params) {
    return api.get('/v1/uks/medicines', { params })
  },
  getSummary() {
    return api.get('/v1/uks/medicines/summary')
  },
  get(id) {
    return api.get(`/v1/uks/medicines/${id}`)
  },
  create(data) {
    return api.post('/v1/uks/medicines', data)
  },
  update(id, data) {
    return api.put(`/v1/uks/medicines/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/uks/medicines/${id}`)
  },
  getTransactions(params) {
    return api.get('/v1/uks/medicines/transactions', { params })
  },
  createTransaction(data) {
    return api.post('/v1/uks/medicines/transactions', data)
  },
}

export const uksVisitTypeApi = {
  getAll(params) {
    return api.get('/v1/uks/visit-types', { params })
  },
  get(id) {
    return api.get(`/v1/uks/visit-types/${id}`)
  },
  create(data) {
    return api.post('/v1/uks/visit-types', data)
  },
  update(id, data) {
    return api.put(`/v1/uks/visit-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/uks/visit-types/${id}`)
  },
  seedDefaults() {
    return api.post('/v1/uks/visit-types/seed-defaults')
  },
}

export const uksReportApi = {
  getSummary(params) {
    return api.get('/v1/uks-reports/summary', { params })
  },
  getVisits(params) {
    return api.get('/v1/uks-reports/visits', { params })
  },
  export(params) {
    return api.get('/v1/uks-reports/export', { params, responseType: 'blob' })
  },
  exportVisits(params) {
    return api.get('/v1/uks-reports/export-visits', { params, responseType: 'blob' })
  },
}
