import api from './index'

export const billingApi = {
  getOverview() {
    return api.get('/v1/billing/overview')
  }
}
