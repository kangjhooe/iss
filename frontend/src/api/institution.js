import api from './index'

export const institutionApi = {
  getAll(params) {
    return api.get('/v1/institution', { params })
  },
  get(id) {
    return api.get(`/v1/institution/${id}`)
  },
  create(data) {
    return api.post('/v1/institution', data)
  },
  update(id, data) {
    // Pakai POST agar body terbaca di hosting yang tidak meneruskan body PUT (nginx/shared hosting)
    return api.post(`/v1/institution/${id}/update`, data)
  },
  delete(id) {
    return api.delete(`/v1/institution/${id}`)
  },
  getMy() {
    return api.get('/v1/institution/my')
  },
  updateActiveAcademicYear(id, data) {
    return api.put(`/v1/institution/${id}/active-academic-year`, data)
  },
  uploadLogo(id, file) {
    const formData = new FormData()
    formData.append('logo', file)
    return api.post(`/v1/institution/${id}/logo`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  uploadCoverImage(id, file) {
    const formData = new FormData()
    formData.append('cover_image', file)
    return api.post(`/v1/institution/${id}/cover-image`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  }
}
