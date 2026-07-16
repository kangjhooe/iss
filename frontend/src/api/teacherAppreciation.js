import api from './index'

function toFormData(data) {
  const form = new FormData()
  Object.entries(data || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return
    if (value instanceof File) {
      form.append(key, value)
    } else if (typeof value === 'boolean') {
      form.append(key, value ? '1' : '0')
    } else {
      form.append(key, value)
    }
  })
  return form
}

export const teacherAchievementTypeApi = {
  getAll(params) {
    return api.get('/v1/teacher-achievement-types', { params })
  },
  create(data) {
    return api.post('/v1/teacher-achievement-types', data)
  },
  update(id, data) {
    return api.put(`/v1/teacher-achievement-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/teacher-achievement-types/${id}`)
  },
}

export const teacherAchievementApi = {
  getAll(params) {
    return api.get('/v1/teacher-achievements', { params })
  },
  create(data) {
    const form = toFormData(data)
    return api.post('/v1/teacher-achievements', form)
  },
  update(id, data) {
    const form = toFormData({ ...data, _method: 'PUT' })
    return api.post(`/v1/teacher-achievements/${id}`, form)
  },
  delete(id) {
    return api.delete(`/v1/teacher-achievements/${id}`)
  },
  approve(id, data = {}) {
    return api.post(`/v1/teacher-achievements/${id}/approve`, data)
  },
  reject(id, data) {
    return api.post(`/v1/teacher-achievements/${id}/reject`, data)
  },
  getByEmployee(employeeId, params) {
    return api.get(`/v1/teacher-achievements/by-employee/${employeeId}`, { params })
  },
}

export const teacherPointRewardApi = {
  getAll(params) {
    return api.get('/v1/teacher-point-rewards', { params })
  },
  create(data) {
    return api.post('/v1/teacher-point-rewards', data)
  },
  update(id, data) {
    return api.put(`/v1/teacher-point-rewards/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/teacher-point-rewards/${id}`)
  },
}

export const teacherRewardLogApi = {
  getAll(params) {
    return api.get('/v1/teacher-reward-logs', { params })
  },
  create(data) {
    return api.post('/v1/teacher-reward-logs', data)
  },
  delete(id) {
    return api.delete(`/v1/teacher-reward-logs/${id}`)
  },
}

export const teacherPointApi = {
  getAll(params) {
    return api.get('/v1/teacher-points', { params })
  },
  getSummary(employeeId, params) {
    return api.get(`/v1/teacher-points/${employeeId}/summary`, { params })
  },
  getLeaderboard(params) {
    return api.get('/v1/teacher-points/leaderboard', { params })
  },
  getReportSummary(params) {
    return api.get('/v1/teacher-points/report-summary', { params })
  },
  getEmployees(params) {
    return api.get('/v1/teacher-appreciation/employees', { params })
  },
  getEmployeesLite(params) {
    return api.get('/v1/teacher-appreciation/employees-lite', { params })
  },
}

export const teacherViolationTypeApi = {
  getAll(params) {
    return api.get('/v1/teacher-violation-types', { params })
  },
  getActive(params) {
    return api.get('/v1/teacher-violation-types-active', { params: { ...params, active_only: true } })
  },
  create(data) {
    return api.post('/v1/teacher-violation-types', data)
  },
  update(id, data) {
    return api.put(`/v1/teacher-violation-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/teacher-violation-types/${id}`)
  },
}

export const teacherViolationApi = {
  getAll(params) {
    return api.get('/v1/teacher-violations', { params })
  },
  create(data) {
    const form = toFormData(data)
    return api.post('/v1/teacher-violations', form)
  },
  update(id, data) {
    const form = toFormData({ ...data, _method: 'PUT' })
    return api.post(`/v1/teacher-violations/${id}`, form)
  },
  delete(id) {
    return api.delete(`/v1/teacher-violations/${id}`)
  },
  approve(id, data = {}) {
    return api.post(`/v1/teacher-violations/${id}/approve`, data)
  },
  reject(id, data) {
    return api.post(`/v1/teacher-violations/${id}/reject`, data)
  },
}

export const myTeacherAppreciationApi = {
  getSummary(params) {
    return api.get('/v1/teacher/my-points/summary', { params })
  },
  getLeaderboard(params) {
    return api.get('/v1/teacher/my-points/leaderboard', { params })
  },
  getAchievements(params) {
    return api.get('/v1/teacher/my-achievements', { params })
  },
  submit(data) {
    const form = toFormData(data)
    return api.post('/v1/teacher/my-achievements', form)
  },
  getTypes() {
    return api.get('/v1/teacher/my-achievement-types')
  },
  get(id) {
    return api.get(`/v1/teacher/my-achievements/${id}`)
  },
  getViolations(params) {
    return api.get('/v1/teacher/my-violations', { params })
  },
}
