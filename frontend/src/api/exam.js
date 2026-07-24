import api from './index'

const base = '/v1/exam'

export const examApi = {
  // Exams
  listExams(params = {}) {
    return api.get(`${base}/exams`, { params })
  },
  getExam(id) {
    return api.get(`${base}/exams/${id}`)
  },
  getExamByCode(code) {
    return api.get(`${base}/exams/by-code/${encodeURIComponent(code)}`)
  },
  createExam(data) {
    return api.post(`${base}/exams`, data)
  },
  updateExam(id, data) {
    return api.put(`${base}/exams/${id}`, data)
  },
  deleteExam(id) {
    return api.delete(`${base}/exams/${id}`)
  },
  attachQuestions(examId, questionBankIds) {
    return api.post(`${base}/exams/${examId}/questions`, { question_bank_ids: questionBankIds })
  },

  // Sessions
  listSessions(params = {}) {
    return api.get(`${base}/sessions`, { params })
  },
  getSession(sessionId) {
    return api.get(`${base}/sessions/${sessionId}`)
  },
  createSession(data) {
    return api.post(`${base}/sessions`, data)
  },
  updateSession(sessionId, data) {
    return api.put(`${base}/sessions/${sessionId}`, data)
  },
  deleteSession(sessionId) {
    return api.delete(`${base}/sessions/${sessionId}`)
  },
  startSession(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/start`)
  },
  endSession(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/end`)
  },
  resetSession(sessionId, resetParticipants = true) {
    return api.post(`${base}/sessions/${sessionId}/reset`, { reset_participants: resetParticipants })
  },
  computeScores(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/compute-scores`)
  },
  monitorSession(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/monitor`)
  },
  exportSessionResults(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/export-results`, { responseType: 'blob' })
  },
  releaseAllScores(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/release-scores`)
  },

  // Participants
  listParticipants(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/participants`)
  },
  addParticipants(sessionId, studentIds) {
    return api.post(`${base}/sessions/${sessionId}/participants`, { student_ids: studentIds })
  },
  removeParticipant(participantId) {
    return api.delete(`${base}/participants/${participantId}`)
  },
  generateParticipantNumbers(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/participants/generate-numbers`)
  },
  reorderParticipants(sessionId, participantIds) {
    return api.put(`${base}/sessions/${sessionId}/participants/reorder`, { participant_ids: participantIds })
  },
  updateParticipant(participantId, data) {
    return api.put(`${base}/participants/${participantId}`, data)
  },
  regenerateToken(participantId) {
    return api.post(`${base}/participants/${participantId}/regenerate-token`)
  },
  /** Kode ujian 6 huruf; diperbarui tiap 20 menit. Hanya saat sesi started. */
  regenerateEntryPin(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/regenerate-entry-pin`)
  },
  releaseScore(participantId) {
    return api.post(`${base}/participants/${participantId}/release-score`)
  },
  resetParticipant(participantId) {
    return api.post(`${base}/participants/${participantId}/reset`)
  },
  getParticipantAnswers(participantId) {
    return api.get(`${base}/participants/${participantId}/answers`)
  },
  updateAnswerScore(answerId, score) {
    return api.put(`${base}/answers/${answerId}/score`, { score })
  },
  recomputeParticipant(participantId) {
    return api.post(`${base}/participants/${participantId}/recompute`)
  },
  printCardUrl(participantId) {
    return `${import.meta.env.VITE_API_BASE_URL || '/api'}${base}/participants/${participantId}/print-card`
  },
  printSessionCardsUrl(sessionId) {
    return `${import.meta.env.VITE_API_BASE_URL || '/api'}${base}/sessions/${sessionId}/participants/print-cards`
  },

  // Question stimuli
  listStimuli(params = {}) {
    return api.get('/v1/question-stimuli', { params })
  },
  getStimulus(id) {
    return api.get(`/v1/question-stimuli/${id}`)
  },
  createStimulus(data) {
    return api.post('/v1/question-stimuli', data)
  },
  updateStimulus(id, data) {
    return api.put(`/v1/question-stimuli/${id}`, data)
  },
  deleteStimulus(id) {
    return api.delete(`/v1/question-stimuli/${id}`)
  },

  // Bank Soal (master)
  listBanks(params = {}) {
    return api.get('/v1/banks', { params })
  },
  getBank(id) {
    return api.get(`/v1/banks/${id}`)
  },
  createBank(data) {
    return api.post('/v1/banks', data)
  },
  updateBank(id, data) {
    return api.put(`/v1/banks/${id}`, data)
  },
  deleteBank(id) {
    return api.delete(`/v1/banks/${id}`)
  },
  backupBank(id) {
    return api.get(`/v1/banks/${id}/backup`, { responseType: 'blob' })
  },
  restoreBank(bankSoalId, file) {
    const form = new FormData()
    form.append('bank_soal_id', bankSoalId)
    form.append('file', file)
    return api.post('/v1/banks/restore', form)
  },
  listBankShares(bankSoalId) {
    return api.get(`/v1/banks/${bankSoalId}/shares`)
  },
  inviteBankShare(bankSoalId, nik) {
    return api.post(`/v1/banks/${bankSoalId}/share`, { nik })
  },
  revokeBankShare(bankSoalId, userId) {
    return api.delete(`/v1/banks/${bankSoalId}/share/${userId}`)
  },
  // Mata pelajaran untuk modul ujian (bisa diakses tanpa modul schedule)
  listSubjects(params = {}) {
    return api.get('/v1/exam/subjects', { params })
  },
  /** Mata pelajaran yang siap diujikan (minimal 1 soal di bank soal) */
  listSubjectsReady() {
    return api.get('/v1/exam/subjects-ready')
  },
  // Upload gambar untuk soal/opsi (rich text)
  uploadQuestionImage(file) {
    const formData = new FormData()
    formData.append('file', file)
    return api.post('/v1/exam/question-assets/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  // Question bank
  listQuestions(params = {}) {
    return api.get('/v1/question-bank', { params })
  },
  getQuestion(id) {
    return api.get(`/v1/question-bank/${id}`)
  },
  createQuestion(data) {
    return api.post('/v1/question-bank', data)
  },
  updateQuestion(id, data) {
    return api.put(`/v1/question-bank/${id}`, data)
  },
  deleteQuestion(id) {
    return api.delete(`/v1/question-bank/${id}`)
  },
  duplicateQuestion(id) {
    return api.post(`/v1/question-bank/${id}/duplicate`)
  },
  reorderQuestions(bankSoalId, questionIds) {
    return api.post('/v1/question-bank/reorder', {
      bank_soal_id: bankSoalId,
      question_ids: questionIds
    })
  },
  importQuestions(bankSoalId, file) {
    const form = new FormData()
    form.append('bank_soal_id', bankSoalId)
    form.append('file', file)
    return api.post('/v1/question-bank/import', form)
  },
  downloadImportTemplate() {
    return api.get('/v1/question-bank/import-template', { responseType: 'blob' })
  }
}

// Public attempt (no auth - token in body)
export const examAttemptApi = {
  enter(loginToken, entryPin) {
    return api.post('/v1/exam/attempt/enter', {
      login_token: loginToken,
      entry_pin: entryPin ? String(entryPin).trim().toUpperCase().slice(0, 6) : ''
    })
  },
  /** Masuk dengan kode ujian (dari pengawas) + no. urut peserta (angka di kartu). Backend mengembalikan login_token untuk request berikutnya. */
  enterWithPinAndNomorUrut(entryPin, nomorUrut) {
    return api.post('/v1/exam/attempt/enter', {
      entry_pin: entryPin ? String(entryPin).trim().toUpperCase().slice(0, 6) : '',
      nomor_urut: Number(nomorUrut) || 0
    })
  },
  getQuestion(loginToken, index) {
    return api.get('/v1/exam/attempt/question', { params: { login_token: loginToken, index } })
  },
  saveAnswer(loginToken, data) {
    return api.put('/v1/exam/attempt/answer', { login_token: loginToken, ...data })
  },
  submit(loginToken) {
    return api.post('/v1/exam/attempt/submit', { login_token: loginToken })
  }
}
