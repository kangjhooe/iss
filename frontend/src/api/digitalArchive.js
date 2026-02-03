import api from './index'

export default {
  list(params) {
    return api.get('/v1/digital-archives', { params })
  },

  get(id) {
    return api.get(`/v1/digital-archives/${id}`)
  },

  create(data) {
    const formData = new FormData()
    Object.keys(data).forEach((key) => {
      if (key === 'file' && data[key]) {
        formData.append('file', data[key])
      } else if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key])
      }
    })
    return api.post('/v1/digital-archives', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  update(id, data) {
    const formData = new FormData()
    Object.keys(data).forEach((key) => {
      if (key === 'file' && data[key]) {
        formData.append('file', data[key])
      } else if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key])
      }
    })
    return api.post(`/v1/digital-archives/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  put(id, data) {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    Object.keys(data).forEach((key) => {
      if (key === 'file' && data[key]) {
        formData.append('file', data[key])
      } else if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key])
      }
    })
    return api.post(`/v1/digital-archives/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  delete(id) {
    return api.delete(`/v1/digital-archives/${id}`)
  },

  download(id) {
    return api.get(`/v1/digital-archives/${id}/download`, { responseType: 'blob' })
  },

  getCategories() {
    return api.get('/v1/digital-archives/categories')
  },

  createCategory(data) {
    return api.post('/v1/digital-archives/categories', data)
  }
}
