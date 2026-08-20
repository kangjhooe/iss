import api from './index'

export const dashboardApi = {
  getCharts(params) {
    return api.get('/v1/dashboard/charts', { params })
  },
}
