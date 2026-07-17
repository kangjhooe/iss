import api from './index'

export const releasesApi = {
  getPublicReleases(params = {}) {
    return api.get('/v1/public/releases', { params })
  },
}
