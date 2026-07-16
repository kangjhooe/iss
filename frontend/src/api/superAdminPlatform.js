import api from './index'

export const superAdminPlatformApi = {
  getAdoption(params) {
    return api.get('/v1/super-admin/adoption', { params })
  },
  getBroadcasts(params) {
    return api.get('/v1/super-admin/broadcasts', { params })
  },
  createBroadcast(data) {
    return api.post('/v1/super-admin/broadcasts', data)
  },
  getBroadcast(id) {
    return api.get(`/v1/super-admin/broadcasts/${id}`)
  },
  getAggregateReport(params) {
    return api.get('/v1/super-admin/reports/aggregate', { params })
  },
  exportAggregateReport(params) {
    return api.get('/v1/super-admin/reports/aggregate/export', {
      params,
      responseType: 'blob'
    })
  }
}
