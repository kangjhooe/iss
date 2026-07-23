import api from './index'

export const lessonScheduleApi = {
  getAll(params) {
    return api.get('/v1/lesson-schedules', { params })
  },
  get(id) {
    return api.get(`/v1/lesson-schedules/${id}`)
  },
  create(data) {
    return api.post('/v1/lesson-schedules', data)
  },
  update(id, data) {
    return api.put(`/v1/lesson-schedules/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/lesson-schedules/${id}`)
  },
  getByClass(classId, params) {
    return api.get(`/v1/lesson-schedules/by-class/${classId}`, { params })
  },
  assignClassTemplate(classId, data) {
    return api.put(`/v1/lesson-schedules/by-class/${classId}/template`, data)
  },
  previewClassTemplate(classId, params) {
    return api.get(`/v1/lesson-schedules/by-class/${classId}/template/preview`, { params })
  },
  getByTeacher(employeeId, params) {
    return api.get(`/v1/lesson-schedules/by-teacher/${employeeId}`, { params })
  },
  getMyTeachingLoad(params) {
    return api.get('/v1/teacher/teaching-load', { params })
  },
  getByRoom(roomId, params) {
    return api.get(`/v1/lesson-schedules/by-room/${roomId}`, { params })
  },
  copySemester(data) {
    return api.post('/v1/lesson-schedules/copy-semester', data)
  },
  deleteByClass(classId, params) {
    return api.delete(`/v1/lesson-schedules/by-class/${classId}`, { params })
  },
  deleteBySemester(semesterId) {
    return api.delete(`/v1/lesson-schedules/by-semester/${semesterId}`)
  },
  listTemplates(params) {
    return api.get('/v1/lesson-schedule-templates', { params })
  },
  getTemplate(params) {
    return api.get('/v1/lesson-schedule-templates/default', { params })
  },
  createTemplate(data) {
    return api.post('/v1/lesson-schedule-templates', data)
  },
  updateTemplate(id, data) {
    return api.put(`/v1/lesson-schedule-templates/${id}`, data)
  },
  deleteTemplate(id) {
    return api.delete(`/v1/lesson-schedule-templates/${id}`)
  },
  saveTemplate(data) {
    return api.put('/v1/lesson-schedule-templates', data)
  },
  exportPdf(params) {
    return api.get('/v1/lesson-schedules/export-pdf', { params, responseType: 'blob' })
  },
}
