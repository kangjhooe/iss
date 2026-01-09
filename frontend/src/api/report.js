import api from './index'

export const reportApi = {
  getStatistics(institutionId = null, params = {}) {
    const url = institutionId 
      ? `/v1/report/institution/${institutionId}`
      : '/v1/report/institution'
    return api.get(url, { params })
  }
}
