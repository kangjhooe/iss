import api from './index'

export const academicCalendarApi = {
  getAll(params) {
    return api.get('/v1/academic-calendar', { params })
  },
  get(id) {
    return api.get(`/v1/academic-calendar/${id}`)
  },
  getCalendar(params) {
    return api.get('/v1/academic-calendar/calendar', { params })
  },
  getUpcoming(params = {}) {
    return api.get('/v1/academic-calendar/upcoming', { params })
  },
  create(data) {
    return api.post('/v1/academic-calendar', data)
  },
  update(id, data) {
    return api.put(`/v1/academic-calendar/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/academic-calendar/${id}`)
  }
}
