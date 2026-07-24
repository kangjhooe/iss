import api from './index'

export const superAdminPlatformApi = {
  getAdoption(params = {}) {
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
  getReleases(params) {
    return api.get('/v1/super-admin/releases', { params })
  },
  getRelease(id) {
    return api.get(`/v1/super-admin/releases/${id}`)
  },
  createRelease(data) {
    return api.post('/v1/super-admin/releases', data)
  },
  updateRelease(id, data) {
    return api.put(`/v1/super-admin/releases/${id}`, data)
  },
  deleteRelease(id) {
    return api.delete(`/v1/super-admin/releases/${id}`)
  },
  getAggregateReport(params) {
    return api.get('/v1/super-admin/reports/aggregate', { params })
  },
  exportAggregateReport(params) {
    return api.get('/v1/super-admin/reports/aggregate/export', {
      params,
      responseType: 'blob'
    })
  },
  listDatabaseBackups() {
    return api.get('/v1/super-admin/database-backups')
  },
  createDatabaseBackup() {
    return api.post('/v1/super-admin/database-backups', null, { timeout: 600000 })
  },
  downloadDatabaseBackup(filename) {
    return api.get(`/v1/super-admin/database-backups/${encodeURIComponent(filename)}/download`, {
      responseType: 'blob',
      timeout: 600000
    })
  },
  deleteDatabaseBackup(filename) {
    return api.delete(`/v1/super-admin/database-backups/${encodeURIComponent(filename)}`)
  }
}
