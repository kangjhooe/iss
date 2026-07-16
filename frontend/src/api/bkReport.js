import api from './index'

export const bkReportApi = {
  getSummary(params) {
    return api.get('/v1/bk-reports/summary', { params })
  },
  getViolationDetail(params) {
    return api.get('/v1/bk-reports/violations', { params })
  },
  export(params) {
    return api.get('/v1/bk-reports/export', { params, responseType: 'blob' })
  },
  exportViolations(params) {
    return api.get('/v1/bk-reports/export-violations', { params, responseType: 'blob' })
  },
}
