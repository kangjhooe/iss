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
  /** mode: 'stimulus' | 'random' — mengembalikan question_bank_ids (belum attach) */
  suggestQuestions(examId, payload) {
    return api.post(`${base}/exams/${examId}/questions/suggest`, payload)
  },

  // Gelombang ujian (UAS/UTS global)
  listPeriods(params = {}) {
    return api.get(`${base}/periods`, { params })
  },
  getPeriod(id) {
    return api.get(`${base}/periods/${id}`)
  },
  createPeriod(data) {
    return api.post(`${base}/periods`, data)
  },
  updatePeriod(id, data) {
    return api.put(`${base}/periods/${id}`, data)
  },
  deletePeriod(id) {
    return api.delete(`${base}/periods/${id}`)
  },
  addPeriodSubjects(periodId, subjectIds, durationMinutes = 90) {
    return api.post(`${base}/periods/${periodId}/subjects`, {
      subject_ids: subjectIds,
      duration_minutes: durationMinutes
    })
  },
  syncPeriodParticipants(periodId, classIds, examIds = null) {
    const payload = { class_ids: classIds }
    if (examIds?.length) payload.exam_ids = examIds
    return api.post(`${base}/periods/${periodId}/participants`, payload)
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
  beritaAcara(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/berita-acara`, { responseType: 'blob' })
  },
  releaseAllScores(sessionId) {
    return api.post(`${base}/sessions/${sessionId}/release-scores`)
  },
  itemAnalysis(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/item-analysis`)
  },
  essayBoard(sessionId) {
    return api.get(`${base}/sessions/${sessionId}/essay-board`)
  },
  scoreEssay(sessionId, payload) {
    return api.post(`${base}/sessions/${sessionId}/essay-score`, payload)
  },
  copyToGrades(sessionId, gradeType) {
    return api.post(`${base}/sessions/${sessionId}/copy-to-grades`, { grade_type: gradeType })
  },
  extraTime(participantId, minutes) {
    return api.post(`${base}/participants/${participantId}/extra-time`, { minutes })
  },
  forceSubmit(participantId) {
    return api.post(`${base}/participants/${participantId}/force-submit`)
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
  // Upload media untuk soal/opsi/stimulus (rich text atau blok media)
  // kind: 'image' | 'audio' | 'video'
  uploadQuestionImage(file, kind = 'image') {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('kind', kind || 'image')
    return api.post('/v1/exam/question-assets/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  uploadQuestionAsset(file, kind = 'image') {
    return this.uploadQuestionImage(file, kind)
  },

  // Question bank
  listQuestions(params = {}) {
    return api.get('/v1/question-bank', { params })
  },
  listQuestionTopics(bankSoalId) {
    return api.get('/v1/question-bank/topics', { params: { bank_soal_id: bankSoalId } })
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
  bulkDeleteQuestions(ids) {
    return api.post('/v1/question-bank/bulk-delete', { ids })
  },
  bulkDuplicateQuestions(ids) {
    return api.post('/v1/question-bank/bulk-duplicate', { ids })
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
  },
  progress(loginToken) {
    return api.get('/v1/exam/attempt/progress', { params: { login_token: loginToken } })
  },
  flagQuestion(loginToken, questionBankId, flagged) {
    return api.put('/v1/exam/attempt/flag', {
      login_token: loginToken,
      question_bank_id: questionBankId,
      flagged
    })
  },
  review(loginToken) {
    return api.get('/v1/exam/attempt/review', { params: { login_token: loginToken } })
  },
  retry(loginToken) {
    return api.post('/v1/exam/attempt/retry', { login_token: loginToken })
  }
}
