import api from '@/api'

export const asetTandaTanganService = {
  list(params = {}) {
    return api.get('/v1/aset-tanda-tangan', { params })
  },
  get(id) {
    return api.get(`/v1/aset-tanda-tangan/${id}`)
  },
  create(formData) {
    return api.post('/v1/aset-tanda-tangan', formData)
  },
  update(id, formData) {
    formData.append('_method', 'PUT')
    return api.post(`/v1/aset-tanda-tangan/${id}`, formData)
  },
  remove(id) {
    return api.delete(`/v1/aset-tanda-tangan/${id}`)
  }
}

export default asetTandaTanganService
