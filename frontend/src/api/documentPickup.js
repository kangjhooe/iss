import api from './index'

const BOOLEAN_KEYS = new Set(['taken_ijazah', 'taken_raport', 'taken_skhun'])

function buildFormData(data) {
  const formData = new FormData()
  Object.keys(data).forEach((key) => {
    const value = data[key]
    if (key === 'foto') {
      if (value) formData.append('foto', value)
      return
    }
    if (BOOLEAN_KEYS.has(key)) {
      formData.append(key, value ? '1' : '0')
      return
    }
    if (value !== null && value !== undefined && value !== '') {
      formData.append(key, value)
    }
  })
  return formData
}

export default {
  list(params) {
    return api.get('/v1/document-pickups', { params })
  },

  get(id) {
    return api.get(`/v1/document-pickups/${id}`)
  },

  create(data) {
    const formData = buildFormData(data)
    return api.post('/v1/document-pickups', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  update(id, data) {
    const formData = buildFormData(data)
    return api.post(`/v1/document-pickups/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  delete(id) {
    return api.delete(`/v1/document-pickups/${id}`)
  }
}
