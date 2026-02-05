import api from './index'

/**
 * Absensi siswa per jam pelajaran (per teaching journal).
 */
export const studentAttendanceApi = {
  getByTeachingJournal(teachingJournalId) {
    return api.get(`/v1/teaching-journals/${teachingJournalId}/attendances`)
  },
  saveForTeachingJournal(teachingJournalId, attendances) {
    return api.post('/v1/teaching-journals/attendances', {
      teaching_journal_id: teachingJournalId,
      attendances,
    })
  },
  update(id, data) {
    return api.put(`/v1/student-attendances/${id}`, data)
  },
}

/**
 * Absensi guru & staff per hari.
 */
export const employeeAttendanceApi = {
  getAll(params) {
    return api.get('/v1/employee-attendances', { params })
  },
  create(data) {
    return api.post('/v1/employee-attendances', data)
  },
  update(id, data) {
    return api.put(`/v1/employee-attendances/${id}`, data)
  },
  bulkStore(date, attendances) {
    return api.post('/v1/employee-attendances/bulk', { date, attendances })
  },
  getStatusOptions() {
    return api.get('/v1/employee-attendances/status-options')
  },
}

/**
 * QR Code Attendance
 */
export const qrAttendanceApi = {
  generateStudentQr(studentId) {
    return api.get(`/v1/qr-attendance/student/${studentId}/generate`)
  },
  generateEmployeeQr(employeeId) {
    return api.get(`/v1/qr-attendance/employee/${employeeId}/generate`)
  },
  scanQr(data) {
    return api.post('/v1/qr-attendance/scan', data)
  },
}
