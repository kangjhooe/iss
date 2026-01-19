import api from './index'

export default {
  // Get list of correspondence
  list(params) {
    return api.get('/v1/correspondence', { params })
  },

  // Get single correspondence
  get(id) {
    return api.get(`/v1/correspondence/${id}`)
  },

  // Create correspondence
  create(data) {
    const formData = new FormData()
    
    // Append all fields
    Object.keys(data).forEach(key => {
      if (key === 'file' && data[key]) {
        formData.append('file', data[key])
      } else if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key])
      }
    })

    return api.post('/v1/correspondence', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Update correspondence
  update(id, data) {
    const formData = new FormData()
    
    // Append all fields
    Object.keys(data).forEach(key => {
      if (key === 'file' && data[key]) {
        formData.append('file', data[key])
      } else if (data[key] !== null && data[key] !== undefined) {
        formData.append(key, data[key])
      }
    })

    return api.put(`/v1/correspondence/${id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  // Delete correspondence
  delete(id) {
    return api.delete(`/v1/correspondence/${id}`)
  },

  // Approve correspondence
  approve(id) {
    return api.post(`/v1/correspondence/${id}/approve`)
  },

  // Send correspondence
  send(id) {
    return api.post(`/v1/correspondence/${id}/send`)
  },

  // Archive correspondence
  archive(id) {
    return api.post(`/v1/correspondence/${id}/archive`)
  },

  // Get categories
  getCategories(type = null) {
    const params = type ? { type } : {}
    return api.get('/v1/correspondence/categories', { params })
  },

  // Get letter types (jenis surat standar)
  getLetterTypes() {
    return api.get('/v1/correspondence/letter-types')
  },

  // Print correspondence as PDF
  print(id) {
    return api.get(`/v1/correspondence/${id}/print`, {
      responseType: 'blob'
    })
  },

  // Get users for disposition dropdown
  getUsers(institutionId = null) {
    const params = institutionId ? { institution_id: institutionId } : {}
    return api.get('/v1/correspondence/users', { params })
  },

  // Disposition methods
  getDispositions(correspondenceId) {
    return api.get(`/v1/correspondence/${correspondenceId}/dispositions`)
  },

  createDisposition(correspondenceId, data) {
    return api.post(`/v1/correspondence/${correspondenceId}/dispositions`, data)
  },

  updateDisposition(id, data) {
    return api.put(`/v1/correspondence/dispositions/${id}`, data)
  },

  completeDisposition(id, notes = null) {
    return api.post(`/v1/correspondence/dispositions/${id}/complete`, { notes })
  },

  deleteDisposition(id) {
    return api.delete(`/v1/correspondence/dispositions/${id}`)
  },

  getPendingDispositions() {
    return api.get('/v1/dispositions/pending')
  },

  // Attachment methods
  getAttachments(correspondenceId) {
    return api.get(`/v1/correspondence/${correspondenceId}/attachments`)
  },

  uploadAttachments(correspondenceId, files) {
    const formData = new FormData()
    files.forEach(file => {
      formData.append('files[]', file)
    })
    return api.post(`/v1/correspondence/${correspondenceId}/attachments`, formData)
  },

  downloadAttachment(id) {
    return api.get(`/v1/correspondence/attachments/${id}/download`, {
      responseType: 'blob'
    })
  },

  updateAttachment(id, data) {
    return api.put(`/v1/correspondence/attachments/${id}`, data)
  },

  deleteAttachment(id) {
    return api.delete(`/v1/correspondence/attachments/${id}`)
  }
}
