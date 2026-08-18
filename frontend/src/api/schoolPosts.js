import api from './index'

function toFormData(data) {
  const formData = new FormData()
  Object.keys(data).forEach((key) => {
    const val = data[key]
    if (key === 'gallery' && Array.isArray(val)) {
      val.forEach((file) => {
        if (file) formData.append('gallery[]', file)
      })
    } else if (key === 'cover' && val) {
      formData.append('cover', val)
    } else if (val !== null && val !== undefined && key !== 'gallery' && key !== 'cover') {
      if (typeof val === 'boolean') {
        formData.append(key, val ? '1' : '0')
      } else {
        formData.append(key, val)
      }
    }
  })
  return formData
}

export const schoolPostsApi = {
  list(params = {}) {
    return api.get('/v1/school-posts', { params })
  },
  get(id) {
    return api.get(`/v1/school-posts/${id}`)
  },
  create(data) {
    return api.post('/v1/school-posts', toFormData(data), {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  update(id, data) {
    const formData = toFormData(data)
    formData.append('_method', 'PUT')
    return api.post(`/v1/school-posts/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  remove(id) {
    return api.delete(`/v1/school-posts/${id}`)
  },
}
