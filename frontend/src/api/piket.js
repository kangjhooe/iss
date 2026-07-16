import api from './index'

export const piketApi = {
  dashboard(params) {
    return api.get('/v1/piket/dashboard', { params })
  },
  today(params) {
    return api.get('/v1/piket/today', { params })
  },
  getSettings() {
    return api.get('/v1/piket/settings')
  },
  updateSettings(data) {
    return api.put('/v1/piket/settings', data)
  },
  employeesLite(params) {
    return api.get('/v1/piket/employees-lite', { params })
  },
  studentsLite(params) {
    return api.get('/v1/piket/students-lite', { params })
  },
  classesLite(params) {
    return api.get('/v1/piket/classes-lite', { params })
  },

  getSchedules(params) {
    return api.get('/v1/piket-schedules', { params })
  },
  createSchedule(data) {
    return api.post('/v1/piket-schedules', data)
  },
  updateSchedule(id, data) {
    return api.put(`/v1/piket-schedules/${id}`, data)
  },
  deleteSchedule(id) {
    return api.delete(`/v1/piket-schedules/${id}`)
  },

  getLogs(params) {
    return api.get('/v1/piket-logs', { params })
  },
  createLog(data) {
    return api.post('/v1/piket-logs', data)
  },
  updateLog(id, data) {
    return api.put(`/v1/piket-logs/${id}`, data)
  },
  reviewLog(id, data = {}) {
    return api.post(`/v1/piket-logs/${id}/review`, data)
  },
  deleteLog(id) {
    return api.delete(`/v1/piket-logs/${id}`)
  },

  getIncidents(params) {
    return api.get('/v1/piket-incidents', { params })
  },
  createIncident(data) {
    return api.post('/v1/piket-incidents', data)
  },
  updateIncident(id, data) {
    return api.put(`/v1/piket-incidents/${id}`, data)
  },
  deleteIncident(id) {
    return api.delete(`/v1/piket-incidents/${id}`)
  },
  proposeViolation(id, data) {
    return api.post(`/v1/piket-incidents/${id}/propose-violation`, data)
  },
  proposeTeacherViolation(id, data) {
    return api.post(`/v1/piket-incidents/${id}/propose-teacher-violation`, data)
  },
  violationTypesActive() {
    return api.get('/v1/piket/violation-types-active')
  },
  teacherViolationTypesActive() {
    return api.get('/v1/piket/teacher-violation-types-active')
  },
  scanEmptyClasses(data = {}) {
    return api.post('/v1/piket/scan/empty-classes', data)
  },
  scanTeacherLateness(data = {}) {
    return api.post('/v1/piket/scan/teacher-lateness', data)
  },
  scanAll(data = {}) {
    return api.post('/v1/piket/scan', data)
  },
  weeklyReport(params) {
    return api.get('/v1/piket/weekly-report', { params, responseType: 'blob' })
  },
}
