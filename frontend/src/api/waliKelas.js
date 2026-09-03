import api from './index'
import { openPdfBlob } from '@/utils/pdfPreview'

export const waliKelasApi = {
  getStudent(classId, studentId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/students/${studentId}`)
  },
  updateLoginFields(classId, studentId, data) {
    return api.patch(`/v1/teacher/wali/classes/${classId}/students/${studentId}/login-fields`, data)
  },
  updateStudent(classId, studentId, data) {
    return api.patch(`/v1/teacher/wali/classes/${classId}/students/${studentId}`, data)
  },
  uploadPhoto(classId, studentId, formData) {
    return api.post(`/v1/teacher/wali/classes/${classId}/students/${studentId}/photo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  deletePhoto(classId, studentId) {
    return api.delete(`/v1/teacher/wali/classes/${classId}/students/${studentId}/photo`)
  },
  exportIdentitas(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/identitas`, {
      params,
      responseType: 'blob',
    })
  },
  ensureStudentAccount(classId, studentId) {
    return api.post(`/v1/teacher/wali/classes/${classId}/students/${studentId}/ensure-account`)
  },
  resetStudentPassword(classId, studentId) {
    return api.post(`/v1/teacher/wali/classes/${classId}/students/${studentId}/reset-password`)
  },
  ensureAccountsBulk(classId, data) {
    return api.post(`/v1/teacher/wali/classes/${classId}/ensure-accounts`, data || {})
  },
  getDashboard(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/dashboard`)
  },
  getAttendanceSummary(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/attendance-summary`, { params })
  },
  getGradesOverview(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/grades-overview`)
  },
  getNotes(classId, studentId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes`)
  },
  createNote(classId, studentId, data) {
    return api.post(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes`, data)
  },
  updateNote(classId, studentId, noteId, data) {
    return api.put(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes/${noteId}`, data)
  },
  deleteNote(classId, studentId, noteId) {
    return api.delete(`/v1/teacher/wali/classes/${classId}/students/${studentId}/notes/${noteId}`)
  },
  getSchedule(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/schedule`, { params })
  },
  exportSchedulePdf(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/schedule/export-pdf`, {
      params,
      responseType: 'blob',
    })
  },
  getViolations(params) {
    return api.get('/v1/teacher/wali/violations', { params })
  },
  proposeViolation(data) {
    return api.post('/v1/teacher/wali/violations', data)
  },
  getViolationTypes(params) {
    return api.get('/v1/teacher/wali/violation-types', { params })
  },
  getAchievements(params) {
    return api.get('/v1/teacher/wali/achievements', { params })
  },
  proposeAchievement(data) {
    return api.post('/v1/teacher/wali/achievements', data)
  },
  getAchievementTypes(params) {
    return api.get('/v1/teacher/wali/achievement-types', { params })
  },
  getMutations(params) {
    return api.get('/v1/teacher/wali/mutations', { params })
  },
  proposeMutation(data) {
    return api.post('/v1/teacher/wali/mutations', data)
  },
  exportRoster(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/roster`, { responseType: 'blob' })
  },
  exportContacts(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/contacts`, {
      params,
      responseType: 'blob',
    })
  },
  exportAttendance(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/export/attendance`, {
      params,
      responseType: 'blob',
    })
  },
  getFinanceSummary(classId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/finance/summary`)
  },
  getFinanceFeeTypes(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/finance/fee-types`, { params })
  },
  getFinanceInvoices(classId, params) {
    return api.get(`/v1/teacher/wali/classes/${classId}/finance/invoices`, { params })
  },
  generateFinanceInvoices(classId, data) {
    return api.post(`/v1/teacher/wali/classes/${classId}/finance/invoices/generate`, data)
  },
  storeFinancePayment(classId, data) {
    return api.post(`/v1/teacher/wali/classes/${classId}/finance/payments`, data)
  },
  openFinanceReceipt(classId, paymentId) {
    return api.get(`/v1/teacher/wali/classes/${classId}/finance/payments/${paymentId}/receipt`, {
      responseType: 'blob',
    }).then((res) => {
      openPdfBlob(res, `kwitansi-${paymentId}.pdf`)
      return res
    })
  },
}
