import api from './index'

export default {
  list(params) {
    return api.get('/v1/document-pickups', { params })
  },

  get(id) {
    return api.get(`/v1/document-pickups/${id}`)
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
    return api.post('/v1/document-pickups', formData, {
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
    return api.post(`/v1/document-pickups/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  delete(id) {
    return api.delete(`/v1/document-pickups/${id}`)
  }
}
