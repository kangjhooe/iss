import api from './index'

export const auditLogApi = {
  getList(params) {
    return api.get('/v1/audit-logs', { params })
  },
  getFilterOptions() {
    return api.get('/v1/audit-logs/filter-options')
  },
  exportCsv(params) {
    return api.get('/v1/audit-logs/export', {
      params,
      responseType: 'blob'
    })
  }
}
