import api from './index'

export default {
  list(params) {
    return api.get('/v1/guest-visits', { params })
  },

  get(id) {
    return api.get(`/v1/guest-visits/${id}`)
  },

  create(data) {
    const formData = new FormData()
    Object.keys(data).forEach((key) => {
      if (key === 'foto' && data[key]) {
        formData.append('foto', data[key])
      } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
        formData.append(key, data[key])
      }
    })
    return api.post('/v1/guest-visits', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  update(id, data) {
    const formData = new FormData()
    Object.keys(data).forEach((key) => {
      if (key === 'foto' && data[key]) {
        formData.append('foto', data[key])
      } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
        formData.append(key, data[key])
      }
    })
    return api.post(`/v1/guest-visits/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  delete(id) {
    return api.delete(`/v1/guest-visits/${id}`)
  },

  checkout(id) {
    return api.post(`/v1/guest-visits/${id}/checkout`)
  },

  exportData(params) {
    return api.get('/v1/guest-visits/export', { params })
  }
}
