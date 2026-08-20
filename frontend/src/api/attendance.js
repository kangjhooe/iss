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
  prepareFromSchedule(params) {
    const query = { ...params }
    if (Array.isArray(query.lesson_schedule_ids)) {
      query.lesson_schedule_ids = query.lesson_schedule_ids.join(',')
    }
    return api.get('/v1/student-attendances/prepare-from-schedule', { params: query })
  },
  saveFromSchedule(lessonScheduleIds, date, attendances) {
    const ids = Array.isArray(lessonScheduleIds) ? lessonScheduleIds : [lessonScheduleIds]
    return api.post('/v1/student-attendances/from-schedule', {
      lesson_schedule_ids: ids,
      date,
      attendances,
    })
  },
  update(id, data) {
    return api.put(`/v1/student-attendances/${id}`, data)
  },
  /**
   * Riwayat absensi untuk siswa yang sedang login (portal siswa).
   * Filter opsional: semester_id, date_from, date_to (YYYY-MM-DD).
   */
  getMy(params) {
    return api.get('/v1/student-attendances/my', { params })
  },
  exportMy(params) {
    return api.get('/v1/student-attendances/my/export', { params, responseType: 'blob' })
  },
  getRekap(params) {
    return api.get('/v1/student-attendances/rekap', { params })
  },
  exportRekap(params) {
    return api.get('/v1/student-attendances/export-rekap', { params, responseType: 'blob' })
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
  getRekap(params) {
    return api.get('/v1/employee-attendances/rekap', { params })
  },
  exportRekap(params) {
    return api.get('/v1/employee-attendances/export-rekap', { params, responseType: 'blob' })
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
  generateStudentBulk(data) {
    return api.post('/v1/qr-attendance/students/generate-bulk', data)
  },
  generateEmployeeBulk(data) {
    return api.post('/v1/qr-attendance/employees/generate-bulk', data)
  },
  printStudentPdf(params) {
    return api.get('/v1/qr-attendance/students/print-pdf', { params, responseType: 'blob' })
  },
  printEmployeePdf(params) {
    return api.get('/v1/qr-attendance/employees/print-pdf', { params, responseType: 'blob' })
  },
  scanQr(data) {
    return api.post('/v1/qr-attendance/scan', data)
  },
}
