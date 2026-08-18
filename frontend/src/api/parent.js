import api from './index'

export const parentApi = {
  dashboard() {
    return api.get('/v1/parent/dashboard')
  },
  children() {
    return api.get('/v1/parent/children')
  },
  announcements(params = {}) {
    return api.get('/v1/parent/announcements', { params })
  },
  schedule(studentId) {
    return api.get(`/v1/parent/children/${studentId}/schedule`)
  },
  grades(studentId, params = {}) {
    return api.get(`/v1/parent/children/${studentId}/grades`, { params })
  },
  attendance(studentId, params = {}) {
    return api.get(`/v1/parent/children/${studentId}/attendance`, { params })
  },
  violations(studentId) {
    return api.get(`/v1/parent/children/${studentId}/violations`)
  },
}
