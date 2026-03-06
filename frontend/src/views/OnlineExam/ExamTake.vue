<template>
  <div class="exam-take-page">
    <template v-if="!attemptInfo">
      <div class="enter-card">
        <h1>Masuk ujian</h1>
        <p>Masukkan kode ujian 6 huruf dari pengawas dan no. urut peserta (angka di kartu).</p>
        <form @submit.prevent="enter">
          <input
            v-model="entryPin"
            type="text"
            placeholder="Kode ujian (6 huruf dari pengawas)"
            maxlength="6"
            class="token-input entry-pin-input"
            autocomplete="off"
            @input="entryPin = entryPin.replace(/[^A-Za-z]/g, '').toUpperCase()"
          />
          <input
            v-model.number="nomorUrut"
            type="number"
            min="1"
            max="999"
            placeholder="No. urut (angka di kartu)"
            class="token-input"
            autocomplete="off"
          />
          <button type="submit" class="btn-primary" :disabled="entering || entryPin.length !== 6 || !nomorUrut">{{ entering ? 'Memuat...' : 'Masuk' }}</button>
        </form>
        <p v-if="error" class="error">{{ error }}</p>
      </div>
    </template>

    <template v-else-if="attemptInfo.submitted">
      <div class="result-card">
        <h2>Anda sudah mengirimkan jawaban.</h2>
        <p v-if="attemptInfo.score != null">Nilai: {{ attemptInfo.score }} / {{ attemptInfo.score_max }}</p>
        <p v-else>Nilai akan diumumkan kemudian.</p>
      </div>
    </template>

    <template v-else>
      <header class="exam-header">
        <div class="exam-title">{{ attemptInfo.exam?.name }}</div>
        <div class="exam-meta">Soal {{ currentIndex + 1 }} / {{ attemptInfo.total_questions }} — {{ attemptInfo.exam?.duration_minutes }} menit</div>
        <div class="timer" :class="{ warning: timeLeft <= 300 }">{{ formatTime(timeLeft) }}</div>
      </header>

      <main class="exam-main">
        <div v-if="questionLoading" class="question-loading">Memuat soal...</div>
        <template v-else-if="currentQuestion">
          <div class="question-card">
            <div v-if="currentQuestion.stimulus" class="stimulus">
              <div class="stimulus-content" v-html="currentQuestion.stimulus.content || ''"></div>
            </div>
            <div class="question-body">
              <p class="question-text"><strong>{{ currentIndex + 1 }}.</strong> <span v-html="currentQuestion.body || ''"></span></p>
            </div>
            <div v-if="currentQuestion.type === 'pg'" class="options">
              <label v-for="opt in currentQuestion.options" :key="opt.id" class="option-label">
                <input v-model="selectedOptionId" type="radio" :name="'q' + currentQuestion.question_bank_id" :value="opt.id" @change="saveCurrentAnswer" />
                <span class="option-text"><span v-html="opt.option_key + '. ' + (opt.body || '')"></span></span>
              </label>
            </div>
            <div v-else-if="currentQuestion.type === 'pg_kompleks'" class="options">
              <p class="option-hint">Pilih semua jawaban yang benar (boleh lebih dari satu).</p>
              <label v-for="opt in currentQuestion.options" :key="opt.id" class="option-label">
                <input v-model="selectedOptionIds" type="checkbox" :value="opt.id" @change="saveCurrentAnswer" />
                <span class="option-text"><span v-html="opt.option_key + '. ' + (opt.body || '')"></span></span>
              </label>
            </div>
            <div v-else-if="currentQuestion.type === 'matching'" class="matching-answer">
              <p class="option-hint">Cocokkan kolom kiri dengan kolom kanan.</p>
              <div v-for="leftItem in currentQuestion.matching_left" :key="leftItem.id" class="matching-row">
                <span class="matching-left-text">{{ leftItem.text }}</span>
                <select :value="matchingAnswer[leftItem.id]" @change="onMatchingChange(leftItem.id, $event)">
                  <option value="">— Pilih —</option>
                  <option v-for="r in currentQuestion.matching_right" :key="r.id" :value="r.id">{{ r.text }}</option>
                </select>
              </div>
            </div>
            <div v-else-if="currentQuestion.type === 'isian' || currentQuestion.type === 'uraian'" class="text-answer">
              <textarea v-model="answerText" rows="4" placeholder="Tulis jawaban Anda" @blur="saveCurrentAnswer"></textarea>
            </div>
          </div>

          <nav class="question-nav">
            <button type="button" class="btn-secondary" :disabled="currentIndex === 0" @click="goTo(currentIndex - 1)">Sebelumnya</button>
            <button v-if="currentIndex < attemptInfo.total_questions - 1" type="button" class="btn-primary" @click="goTo(currentIndex + 1)">Selanjutnya</button>
            <button v-else type="button" class="btn-submit" @click="submitExam">Kirim jawaban</button>
          </nav>
        </template>
      </main>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { examAttemptApi } from '@/api/exam'

const entryPin = ref('')
const nomorUrut = ref('')
const loginToken = ref('')
const entering = ref(false)
const error = ref('')
const attemptInfo = ref(null)
const currentIndex = ref(0)
const currentQuestion = ref(null)
const questionLoading = ref(false)
const answerText = ref('')
const selectedOptionId = ref(null)
const selectedOptionIds = ref([])
const matchingAnswer = ref({}) // { [leftId]: rightId } for dropdowns
const timeLeft = ref(0)
let timerInterval = null

async function enter() {
  entering.value = true
  error.value = ''
  const pin = String(entryPin.value).trim().toUpperCase()
  if (pin.length !== 6) {
    error.value = 'Kode ujian harus 6 huruf.'
    entering.value = false
    return
  }
  const num = Number(nomorUrut.value)
  if (!Number.isInteger(num) || num < 1 || num > 999) {
    error.value = 'No. urut harus angka 1–999 (lihat di kartu).'
    entering.value = false
    return
  }
  try {
    const res = await examAttemptApi.enterWithPinAndNomorUrut(pin, num)
    const data = res.data
    if (data.login_token) {
      loginToken.value = data.login_token
      attemptInfo.value = data
      const started = new Date(data.started_at).getTime()
      const durationMs = (data.exam?.duration_minutes || 60) * 60 * 1000
      timeLeft.value = Math.max(0, Math.floor((started + durationMs - Date.now()) / 1000))
      startTimer()
      loadQuestion(0)
    } else if (data.message && data.participant) {
      attemptInfo.value = {
        submitted: true,
        score: data.participant.score,
        score_max: data.participant.score_max
      }
    } else if (data.message) {
      error.value = data.message
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Kode ujian atau no. urut salah, atau sesi belum diaktifkan.'
  } finally {
    entering.value = false
  }
}

function startTimer() {
  timerInterval = setInterval(() => {
    timeLeft.value = Math.max(0, timeLeft.value - 1)
    if (timeLeft.value === 0) {
      clearInterval(timerInterval)
      submitExam()
    }
  }, 1000)
}

function formatTime(seconds) {
  const m = Math.floor(seconds / 60)
  const s = seconds % 60
  return `${m}:${s.toString().padStart(2, '0')}`
}

async function loadQuestion(index) {
  questionLoading.value = true
  currentQuestion.value = null
  answerText.value = ''
  selectedOptionId.value = null
  selectedOptionIds.value = []
  matchingAnswer.value = {}
  try {
    const res = await examAttemptApi.getQuestion(loginToken.value, index)
    currentQuestion.value = res.data
    currentIndex.value = index
    answerText.value = res.data.saved_answer_text ?? ''
    selectedOptionId.value = res.data.saved_question_option_id ?? null
    selectedOptionIds.value = Array.isArray(res.data.saved_selected_option_ids) ? [...res.data.saved_selected_option_ids] : []
    if (res.data.saved_matching_answer && Array.isArray(res.data.saved_matching_answer)) {
      const map = {}
      res.data.saved_matching_answer.forEach(pair => {
        if (pair.left_id != null) map[pair.left_id] = pair.right_id
      })
      matchingAnswer.value = map
    } else if (res.data.matching_left) {
      matchingAnswer.value = res.data.matching_left.reduce((acc, l) => ({ ...acc, [l.id]: '' }), {})
    }
  } catch (e) {
    console.error(e)
  } finally {
    questionLoading.value = false
  }
}

function goTo(index) {
  if (index < 0 || index >= attemptInfo.value.total_questions) return
  saveCurrentAnswer().then(() => loadQuestion(index))
}

function onMatchingChange(leftId, event) {
  const rightId = event.target.value || null
  matchingAnswer.value = { ...matchingAnswer.value, [leftId]: rightId }
  saveCurrentAnswer()
}

async function saveCurrentAnswer() {
  if (!currentQuestion.value) return
  try {
    const payload = {
      question_bank_id: currentQuestion.value.question_bank_id,
      answer_text: answerText.value || null,
      question_option_id: selectedOptionId.value || null
    }
    if (currentQuestion.value.type === 'pg_kompleks') {
      payload.selected_option_ids = Array.isArray(selectedOptionIds.value) ? selectedOptionIds.value : []
    }
    if (currentQuestion.value.type === 'matching') {
      const left = currentQuestion.value.matching_left || []
      payload.matching_answer = left
        .map(l => ({ left_id: l.id, right_id: matchingAnswer.value[l.id] || null }))
        .filter(p => p.right_id)
    }
    await examAttemptApi.saveAnswer(loginToken.value, payload)
  } catch (e) {
    console.error(e)
  }
}

async function submitExam() {
  if (timerInterval) clearInterval(timerInterval)
  try {
    await examAttemptApi.submit(loginToken.value)
    attemptInfo.value = { ...attemptInfo.value, submitted: true }
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengirim.')
  }
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
})
</script>

<style scoped>
.exam-take-page {
  min-height: 100vh;
  background: #f3f4f6;
  padding: 1rem;
  padding-left: max(1rem, env(safe-area-inset-left));
  padding-right: max(1rem, env(safe-area-inset-right));
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  padding-top: max(1rem, env(safe-area-inset-top));
}
.enter-card,
.result-card {
  max-width: 400px;
  margin: 2rem auto;
  background: #fff;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  text-align: center;
}
.enter-card h1, .result-card h2 { margin-top: 0; }
.token-input {
  width: 100%;
  padding: 0.75rem;
  margin-bottom: 1rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
}
.entry-pin-input {
  font-family: ui-monospace, monospace;
  letter-spacing: 0.2em;
  text-transform: uppercase;
}
/* Prevent zoom on iOS when focusing inputs */
@media (max-width: 768px) {
  .token-input { font-size: 16px; }
}
.btn-primary {
  padding: 0.75rem 1.5rem;
  min-height: 44px;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  touch-action: manipulation;
}
.error { color: #dc2626; margin-top: 0.5rem; }
.exam-header {
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}
.exam-title { font-weight: 600; font-size: 1.125rem; }
.exam-meta { color: #6b7280; }
.timer { margin-left: auto; font-size: 1.25rem; font-weight: 600; }
.timer.warning { color: #dc2626; }
.exam-main { max-width: 720px; margin: 0 auto; width: 100%; }
.question-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.stimulus { background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
.stimulus :deep(img) { max-width: 100%; height: auto; }
.stimulus :deep(table) { border-collapse: collapse; width: 100%; overflow-x: auto; display: block; }
.stimulus :deep(th), .stimulus :deep(td) { border: 1px solid #d1d5db; padding: 0.5rem; }
.question-body { margin-bottom: 1rem; }
.question-body :deep(img) { max-width: 100%; height: auto; }
.question-body :deep(table) { border-collapse: collapse; width: 100%; overflow-x: auto; display: block; }
.question-body :deep(th), .question-body :deep(td) { border: 1px solid #d1d5db; padding: 0.5rem; }
.option-text :deep(img) { max-width: 100%; height: auto; }
.options { display: flex; flex-direction: column; gap: 0.5rem; }
.option-hint { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; }
.option-label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.5rem 0;
  min-height: 44px;
  margin: -0.5rem 0;
  padding: 0.75rem 0;
}
.option-label input { margin-top: 0.35rem; flex-shrink: 0; }
.matching-answer { display: flex; flex-direction: column; gap: 0.75rem; }
.matching-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.matching-left-text { flex: 1; min-width: 120px; }
.matching-row select {
  min-width: 200px;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
}
.text-answer textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  box-sizing: border-box;
}
@media (max-width: 768px) {
  .text-answer textarea { font-size: 16px; }
}
.question-nav {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}
.btn-secondary {
  padding: 0.5rem 1rem;
  min-height: 44px;
  background: #e5e7eb;
  color: #374151;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  touch-action: manipulation;
}
.btn-submit {
  padding: 0.5rem 1rem;
  min-height: 44px;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  touch-action: manipulation;
}
.question-loading { padding: 2rem; text-align: center; }

/* ========== Responsive: Tablet ========== */
@media (max-width: 768px) {
  .exam-take-page { padding: 12px; padding-left: max(12px, env(safe-area-inset-left)); padding-right: max(12px, env(safe-area-inset-right)); }
  .enter-card, .result-card { margin: 1.5rem auto; padding: 1.5rem; }
  .exam-header { padding: 12px; gap: 8px; }
  .exam-title { font-size: 1rem; }
  .exam-meta { font-size: 0.875rem; }
  .timer { font-size: 1.1rem; }
  .question-card { padding: 1rem; }
  .question-nav { gap: 8px; }
}

/* ========== Responsive: Smartphone ========== */
@media (max-width: 480px) {
  .exam-take-page { padding: 10px; padding-left: max(10px, env(safe-area-inset-left)); padding-right: max(10px, env(safe-area-inset-right)); }
  .enter-card, .result-card { margin: 1rem auto; padding: 1.25rem; }
  .exam-header { flex-direction: column; align-items: flex-start; }
  .timer { margin-left: 0; }
  .matching-row { flex-direction: column; align-items: stretch; gap: 0.5rem; }
  .matching-left-text { min-width: 0; }
  .matching-row select { min-width: 0; width: 100%; }
  .question-nav { flex-direction: column; }
  .question-nav button { width: 100%; }
}
</style>
