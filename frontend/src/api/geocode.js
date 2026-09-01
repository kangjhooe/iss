import api from './index'

export const geocodeApi = {
  search(query, limit = 5) {
    return api.get('/v1/geocode/search', { params: { q: query, limit } })
  },
}
