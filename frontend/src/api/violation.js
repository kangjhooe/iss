import api from './index'

export const violationApi = {
  getAll(params) {
    return api.get('/v1/violations', { params })
  },
  get(id) {
    return api.get(`/v1/violations/${id}`)
  },
  create(data) {
    return api.post('/v1/violations', data)
  },
  update(id, data) {
    return api.put(`/v1/violations/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/violations/${id}`)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/violations/by-student/${studentId}`, { params })
  },
}

export const violationTypeApi = {
  getAll(params) {
    return api.get('/v1/violation-types', { params })
  },
  get(id) {
    return api.get(`/v1/violation-types/${id}`)
  },
  create(data) {
    return api.post('/v1/violation-types', data)
  },
  update(id, data) {
    return api.put(`/v1/violation-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/violation-types/${id}`)
  },
}

// Prestasi (pengurangan poin)
export const achievementApi = {
  getAll(params) {
    return api.get('/v1/achievements', { params })
  },
  create(data) {
    return api.post('/v1/achievements', data)
  },
  get(id) {
    return api.get(`/v1/achievements/${id}`)
  },
  delete(id) {
    return api.delete(`/v1/achievements/${id}`)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/achievements/by-student/${studentId}`, { params })
  },
}

export const achievementTypeApi = {
  getAll(params) {
    return api.get('/v1/achievement-types', { params })
  },
  get(id) {
    return api.get(`/v1/achievement-types/${id}`)
  },
  create(data) {
    return api.post('/v1/achievement-types', data)
  },
  update(id, data) {
    return api.put(`/v1/achievement-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/achievement-types/${id}`)
  },
}

// Aturan tindakan (threshold poin)
export const pointThresholdApi = {
  getAll(params) {
    return api.get('/v1/point-thresholds', { params })
  },
  create(data) {
    return api.post('/v1/point-thresholds', data)
  },
  get(id) {
    return api.get(`/v1/point-thresholds/${id}`)
  },
  update(id, data) {
    return api.put(`/v1/point-thresholds/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/point-thresholds/${id}`)
  },
}

// Catatan tindakan (panggilan orang tua, dll.)
export const studentActionLogApi = {
  getAll(params) {
    return api.get('/v1/student-action-logs', { params })
  },
  create(data) {
    return api.post('/v1/student-action-logs', data)
  },
  getByStudent(studentId, params) {
    return api.get(`/v1/student-action-logs/by-student/${studentId}`, { params })
  },
}

// Ringkasan poin siswa
export const studentPointApi = {
  getList(params) {
    return api.get('/v1/student-points', { params })
  },
  getSummary(studentId) {
    return api.get(`/v1/student-points/${studentId}/summary`)
  },
}
