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
  getByTeacher(employeeId, params) {
    return api.get(`/v1/lesson-schedules/by-teacher/${employeeId}`, { params })
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
}
