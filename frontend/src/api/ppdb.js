import api from './index'

export const ppdbApi = {
  getSummary(params) {
    return api.get('/v1/ppdb/summary', { params })
  },
}

export const ppdbPeriodApi = {
  getAll(params) {
    return api.get('/v1/ppdb-periods', { params })
  },
  get(id) {
    return api.get(`/v1/ppdb-periods/${id}`)
  },
  getStatistics(periodId) {
    return api.get(`/v1/ppdb-periods/${periodId}/statistics`)
  },
  create(data) {
    return api.post('/v1/ppdb-periods', data)
  },
  update(id, data) {
    return api.put(`/v1/ppdb-periods/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/ppdb-periods/${id}`)
  },
}

export const ppdbChannelApi = {
  getAll(params) {
    return api.get('/v1/ppdb-channels', { params })
  },
  get(id) {
    return api.get(`/v1/ppdb-channels/${id}`)
  },
  create(data) {
    return api.post('/v1/ppdb-channels', data)
  },
  update(id, data) {
    return api.put(`/v1/ppdb-channels/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/ppdb-channels/${id}`)
  },
}

export const ppdbApplicantApi = {
  getAll(params) {
    return api.get('/v1/ppdb-applicants', { params })
  },
  export(params) {
    return api.get('/v1/ppdb-applicants/export', { params, responseType: 'blob' })
  },
  bulkVerification(data) {
    return api.post('/v1/ppdb-applicants/bulk-verification', data)
  },
  bulkResult(data) {
    return api.post('/v1/ppdb-applicants/bulk-result', data)
  },
  get(id) {
    return api.get(`/v1/ppdb-applicants/${id}`)
  },
  create(data) {
    return api.post('/v1/ppdb-applicants', data)
  },
  update(id, data) {
    return api.put(`/v1/ppdb-applicants/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/ppdb-applicants/${id}`)
  },
  setVerification(id, data) {
    return api.post(`/v1/ppdb-applicants/${id}/verification`, data)
  },
  submit(id) {
    return api.post(`/v1/ppdb-applicants/${id}/submit`)
  },
  setResult(id, data) {
    return api.post(`/v1/ppdb-applicants/${id}/result`, data)
  },
  setPayment(id, data) {
    return api.post(`/v1/ppdb-applicants/${id}/payment`, data)
  },
  confirmReRegistration(id) {
    return api.post(`/v1/ppdb-applicants/${id}/confirm-re-registration`)
  },
  convertToStudent(id, data) {
    return api.post(`/v1/ppdb-applicants/${id}/convert-to-student`, data || {})
  },
  uploadDocument(id, formData) {
    return api.post(`/v1/ppdb-applicants/${id}/documents`, formData)
  },
  deleteDocument(applicantId, documentId) {
    return api.delete(`/v1/ppdb-applicants/${applicantId}/documents/${documentId}`)
  },
  downloadDocument(applicantId, documentId) {
    return api.get(`/v1/ppdb-applicants/${applicantId}/documents/${documentId}/download`, { responseType: 'blob' })
  },
  downloadRegistrationSlip(id) {
    return api.get(`/v1/ppdb-applicants/${id}/registration-slip`, { responseType: 'blob' })
  },
}
