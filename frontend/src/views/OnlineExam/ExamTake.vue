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
      <div class="result-card result-card-wide">
        <h2>Anda sudah mengirimkan jawaban.</h2>
        <p v-if="attemptInfo.closedMessage">{{ attemptInfo.closedMessage }}</p>
        <p v-if="review && review.released && review.score != null">Nilai: {{ review.score }} / {{ review.score_max }}</p>
        <p v-else-if="attemptInfo.score != null">Nilai: {{ attemptInfo.score }} / {{ attemptInfo.score_max }}</p>
        <p v-else>Nilai akan diumumkan kemudian.</p>
        <p v-if="attemptInfo.attempt_number">Percobaan ke-{{ attemptInfo.attempt_number }}<span v-if="attemptInfo.max_attempts"> dari {{ attemptInfo.max_attempts }}</span></p>
        <button v-if="attemptInfo.can_retry" type="button" class="btn-primary" :disabled="retrying" @click="retryAttempt">{{ retrying ? 'Menyiapkan...' : 'Coba lagi' }}</button>
        <button type="button" class="btn-secondary" @click="leaveResult">Masuk ujian lain</button>
        <div v-if="reviewLoading" class="question-loading">Memuat pembahasan...</div>
        <div v-else-if="review && !review.released" class="review-note">{{ review.message }}</div>
        <div v-else-if="review && review.questions?.length" class="review-list">
          <h3>Pembahasan</h3>
          <article v-for="q in review.questions" :key="q.index" class="review-item">
            <p class="question-text"><strong>{{ q.index + 1 }}.</strong> <QuestionHtml tag="span" :html="q.body || ''" /></p>
            <p v-if="q.options?.length" class="review-options">
              <span
                v-for="(opt, oi) in q.options"
                :key="oi"
                class="review-opt"
                :class="{ selected: opt.selected, correct: opt.correct }"
              >
                {{ opt.option_key }}. <QuestionHtml tag="span" :html="opt.body || ''" />
              </span>
            </p>
            <p><strong>Jawaban Anda:</strong> {{ q.student_answer }}</p>
            <p v-if="q.correct_answer"><strong>Kunci:</strong> {{ q.correct_answer }}</p>
            <p class="review-score">Nilai {{ q.score != null ? q.score : '–' }} / {{ q.weight }}</p>
          </article>
        </div>
      </div>
    </template>

    <template v-else>
      <header class="exam-header">
        <div class="exam-title">{{ attemptInfo.exam?.name }}</div>
        <div class="exam-meta">Soal {{ currentIndex + 1 }} / {{ attemptInfo.total_questions }} — {{ attemptInfo.exam?.duration_minutes }} menit</div>
        <div v-if="timedExam" class="timer" :class="{ warning: timeLeft <= 300 }">{{ formatTime(timeLeft) }}</div>
      </header>

      <div class="exam-layout">
        <aside class="question-map">
          <div class="map-title">Nomor soal</div>
          <div class="map-grid">
            <button
              v-for="item in progressItems"
              :key="item.index"
              type="button"
              class="map-btn"
              :class="{ current: item.index === currentIndex, answered: item.answered, flagged: item.flagged }"
              @click="goTo(item.index)"
            >{{ item.index + 1 }}</button>
          </div>
          <p class="map-legend">Hijau = terjawab. Garis oranye = ragu-ragu.</p>
        </aside>
      <main class="exam-main">
        <div v-if="questionLoading" class="question-loading">Memuat soal...</div>
        <template v-else-if="currentQuestion">
          <div class="question-card">
            <div v-if="currentQuestion.stimulus && !currentQuestion.stimulus_continued" class="stimulus">
              <StimulusMediaView :media="currentQuestion.stimulus.media || []" />
              <QuestionHtml class="stimulus-content" :html="currentQuestion.stimulus.content || ''" />
            </div>
            <p v-else-if="currentQuestion.stimulus_continued" class="stimulus-continued-note">
              Lanjutan bacaan/stimulus yang sama (soal sebelumnya).
            </p>
            <div class="question-body">
              <p class="question-text"><strong>{{ currentIndex + 1 }}.</strong> <QuestionHtml tag="span" :html="currentQuestion.body || ''" /></p>
            </div>
            <div v-if="currentQuestion.type === 'pg'" class="options">
              <label v-for="opt in currentQuestion.options" :key="opt.id" class="option-label">
                <input v-model="selectedOptionId" type="radio" :name="'q' + currentQuestion.question_bank_id" :value="opt.id" @change="saveCurrentAnswer" />
                <span class="option-text"><span class="opt-key">{{ opt.option_key }}.</span> <QuestionHtml tag="span" :html="opt.body || ''" /></span>
              </label>
            </div>
            <div v-else-if="currentQuestion.type === 'pg_kompleks'" class="options">
              <p class="option-hint">Pilih semua jawaban yang benar (boleh lebih dari satu).</p>
              <label v-for="opt in currentQuestion.options" :key="opt.id" class="option-label">
                <input v-model="selectedOptionIds" type="checkbox" :value="opt.id" @change="saveCurrentAnswer" />
                <span class="option-text"><span class="opt-key">{{ opt.option_key }}.</span> <QuestionHtml tag="span" :html="opt.body || ''" /></span>
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
            <button type="button" class="btn-secondary" @click="toggleFlag">{{ currentFlagged ? 'Hapus ragu' : 'Ragu-ragu' }}</button>
            <button v-if="currentIndex < attemptInfo.total_questions - 1" type="button" class="btn-primary" @click="goTo(currentIndex + 1)">Selanjutnya</button>
            <button type="button" class="btn-submit" @click="submitExam">Kirim jawaban</button>
          </nav>
        </template>
      </main>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { examAttemptApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'
import StimulusMediaView from '@/components/StimulusMediaView.vue'
import QuestionHtml from '@/components/QuestionHtml.vue'

const toast = useToast()
const RESUME_KEY = 'iss_exam_resume'
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
const progressItems = ref([])
const review = ref(null)
const reviewLoading = ref(false)
const retrying = ref(false)
const timeLeft = ref(0)
const timedExam = ref(false)
let timerInterval = null
let progressTimer = null

const currentFlagged = computed(() => {
  const qid = currentQuestion.value?.question_bank_id
  return progressItems.value.some((item) => item.question_bank_id === qid && item.flagged)
})

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
      sessionStorage.setItem(RESUME_KEY, data.login_token)
      attemptInfo.value = data
      seedProgress(data.total_questions || 0)
      syncTime(data.seconds_remaining, data.started_at, data.exam?.duration_minutes)
      startTimer()
      startProgressPoll()
      loadQuestion(0)
      refreshProgress()
    } else if (data.message && data.participant) {
      if (data.login_token) {
        loginToken.value = data.login_token
        sessionStorage.setItem(RESUME_KEY, data.login_token)
      }
      showSubmitted('', data.participant)
    } else if (data.message) {
      error.value = data.message
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Kode ujian atau no. urut salah, atau sesi belum diaktifkan.'
  } finally {
    entering.value = false
  }
}

function seedProgress(total) {
  if (progressItems.value.length === total && total > 0) return
  progressItems.value = Array.from({ length: total }, (_, index) => ({
    index,
    question_bank_id: null,
    answered: false,
    flagged: false
  }))
}

function syncTime(secondsRemaining, startedAt, durationMinutes) {
  if (secondsRemaining != null) {
    timedExam.value = true
    timeLeft.value = Math.max(0, Number(secondsRemaining))
    return
  }
  if (startedAt && durationMinutes) {
    timedExam.value = true
    const started = new Date(startedAt).getTime()
    const durationMs = Number(durationMinutes) * 60 * 1000
    timeLeft.value = Math.max(0, Math.floor((started + durationMs - Date.now()) / 1000))
    return
  }
  timedExam.value = false
}

function showSubmitted(message, participant) {
  if (timerInterval) clearInterval(timerInterval)
  if (progressTimer) clearInterval(progressTimer)
  attemptInfo.value = {
    submitted: true,
    closedMessage: message || '',
    score: participant?.score ?? null,
    score_max: participant?.score_max ?? null,
    score_released: !!participant?.score_released,
    can_retry: !!participant?.can_retry,
    attempt_number: participant?.attempt_number ?? null,
    max_attempts: participant?.max_attempts ?? null
  }
  if (loginToken.value) loadReview()
}

function closedFromError(e) {
  const code = e.response?.data?.code
  if (code !== 'time_up' && code !== 'session_ended' && code !== 'already_submitted') return false
  showSubmitted(e.response?.data?.message || '')
  return true
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval)
  if (!timedExam.value) return
  timerInterval = setInterval(() => {
    if (timeLeft.value == null) return
    timeLeft.value = Math.max(0, timeLeft.value - 1)
    if (timeLeft.value === 0) {
      clearInterval(timerInterval)
      submitExam()
    }
  }, 1000)
}

function startProgressPoll() {
  if (progressTimer) clearInterval(progressTimer)
  progressTimer = setInterval(refreshProgress, 20000)
}

async function refreshProgress() {
  if (!loginToken.value || attemptInfo.value?.submitted) return
  try {
    const res = await examAttemptApi.progress(loginToken.value)
    const data = res.data
    if (data.submitted) {
      showSubmitted(data.score_released ? '' : 'Jawaban sudah dikumpulkan.', {
        score: data.score,
        score_max: data.score_max,
        score_released: data.score_released,
        can_retry: data.can_retry,
        attempt_number: data.attempt_number,
        max_attempts: data.max_attempts
      })
      return
    }
    attemptInfo.value = {
      ...(attemptInfo.value || {}),
      exam: data.exam || attemptInfo.value?.exam,
      session: data.session || attemptInfo.value?.session,
      total_questions: data.total_questions || attemptInfo.value?.total_questions || 0,
      status: data.status
    }
    if (Array.isArray(data.items) && data.items.length) {
      progressItems.value = data.items
    }
    if (data.seconds_remaining != null) {
      timedExam.value = true
      timeLeft.value = Math.max(0, Number(data.seconds_remaining))
    }
  } catch (e) {
    closedFromError(e)
  }
}

async function retryAttempt() {
  if (!loginToken.value) return
  retrying.value = true
  try {
    const res = await examAttemptApi.retry(loginToken.value)
    const data = res.data
    review.value = null
    sessionStorage.setItem(RESUME_KEY, data.login_token || loginToken.value)
    attemptInfo.value = data
    currentIndex.value = 0
    seedProgress(data.total_questions || 0)
    progressItems.value = []
    seedProgress(data.total_questions || 0)
    syncTime(data.seconds_remaining, data.started_at, data.exam?.duration_minutes)
    startTimer()
    startProgressPoll()
    await loadQuestion(0)
    refreshProgress()
  } catch (e) {
    toast.error('Tidak bisa mengulang', e.response?.data?.message || 'Coba lagi.')
  } finally {
    retrying.value = false
  }
}

function leaveResult() {
  sessionStorage.removeItem(RESUME_KEY)
  loginToken.value = ''
  review.value = null
  attemptInfo.value = null
  progressItems.value = []
}

async function loadReview() {
  if (!loginToken.value) return
  reviewLoading.value = true
  try {
    const res = await examAttemptApi.review(loginToken.value)
    review.value = res.data
    if (res.data?.released) {
      attemptInfo.value = {
        ...attemptInfo.value,
        score: res.data.score,
        score_max: res.data.score_max
      }
    }
  } catch (e) {
    review.value = null
  } finally {
    reviewLoading.value = false
  }
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
    if (res.data.seconds_remaining != null) {
      timedExam.value = true
      timeLeft.value = Math.max(0, Number(res.data.seconds_remaining))
    }
    const mapItem = progressItems.value[index]
    if (mapItem) mapItem.question_bank_id = res.data.question_bank_id
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
    if (!closedFromError(e)) console.error(e)
  } finally {
    questionLoading.value = false
  }
}

function currentIsAnswered() {
  const type = currentQuestion.value?.type
  if (type === 'pg') return !!selectedOptionId.value
  if (type === 'pg_kompleks') return selectedOptionIds.value.length > 0
  if (type === 'matching') return Object.values(matchingAnswer.value).some(Boolean)
  return String(answerText.value || '').trim() !== ''
}

function markCurrentAnswered(answered) {
  const qid = currentQuestion.value?.question_bank_id
  const item = progressItems.value.find((row) => row.question_bank_id === qid) || progressItems.value[currentIndex.value]
  if (item) item.answered = answered
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
    const res = await examAttemptApi.saveAnswer(loginToken.value, payload)
    markCurrentAnswered(res.data?.answered ?? currentIsAnswered())
    if (res.data?.seconds_remaining != null) {
      timedExam.value = true
      timeLeft.value = Math.max(0, Number(res.data.seconds_remaining))
    }
  } catch (e) {
    if (!closedFromError(e)) console.error(e)
  }
}

async function toggleFlag() {
  const qid = currentQuestion.value?.question_bank_id
  if (!qid) return
  const item = progressItems.value.find((row) => row.question_bank_id === qid) || progressItems.value[currentIndex.value]
  const next = !(item && item.flagged)
  try {
    await examAttemptApi.flagQuestion(loginToken.value, qid, next)
    if (item) item.flagged = next
  } catch (e) {
    if (!closedFromError(e)) toast.error('Gagal menandai soal', e.response?.data?.message || 'Coba lagi.')
  }
}

async function submitExam() {
  if (timerInterval) clearInterval(timerInterval)
  try {
    await saveCurrentAnswer()
    const res = await examAttemptApi.submit(loginToken.value)
    showSubmitted(res.data?.message || 'Jawaban berhasil dikirim.', res.data)
  } catch (e) {
    if (closedFromError(e)) return
    const msg = e.response?.data?.message || e.formattedMessage || 'Jawaban ujian tidak dapat dikirim. Periksa koneksi dan coba lagi.'
    toast.error('Gagal mengirim jawaban ujian', msg)
    startTimer()
  }
}

onMounted(async () => {
  const saved = sessionStorage.getItem(RESUME_KEY)
  if (!saved) return
  loginToken.value = saved
  entering.value = true
  try {
    await refreshProgress()
    if (attemptInfo.value?.submitted) return
    if (progressItems.value.length) {
      attemptInfo.value = attemptInfo.value || { total_questions: progressItems.value.length, exam: {} }
      if (!attemptInfo.value.total_questions) {
        attemptInfo.value.total_questions = progressItems.value.length
      }
      startTimer()
      startProgressPoll()
      await loadQuestion(0)
    } else {
      sessionStorage.removeItem(RESUME_KEY)
      loginToken.value = ''
    }
  } catch (e) {
    sessionStorage.removeItem(RESUME_KEY)
    loginToken.value = ''
  } finally {
    entering.value = false
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  if (progressTimer) clearInterval(progressTimer)
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
.exam-layout {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  max-width: 980px;
  margin: 0 auto;
}
.question-map {
  width: 220px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 8px;
  padding: 0.75rem;
  position: sticky;
  top: 0.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.map-title { font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem; }
.map-grid { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.map-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #d1d5db;
  background: #fff;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
}
.map-btn.answered { background: #d1fae5; border-color: #059669; }
.map-btn.flagged { box-shadow: inset 0 -3px 0 #f59e0b; }
.map-btn.current { outline: 2px solid #059669; }
.map-legend { margin: 0.6rem 0 0; font-size: 0.75rem; color: #6b7280; }
.result-card-wide { max-width: 760px; text-align: left; }
.review-list { margin-top: 1.25rem; }
.review-item { padding: 0.75rem 0; border-top: 1px solid #e5e7eb; }
.review-note { color: #6b7280; }
.review-options { display: flex; flex-direction: column; gap: 0.25rem; }
.review-opt { padding: 0.15rem 0.35rem; border-radius: 4px; }
.review-opt.selected { background: #fee2e2; }
.review-opt.correct { background: #d1fae5; }
.review-opt.selected.correct { background: #d1fae5; }
.review-score { color: #374151; font-size: 0.9rem; }
.exam-main { flex: 1; min-width: 0; width: 100%; margin: 0; }
.question-card {
  background: #fff;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.stimulus { background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
.stimulus-continued-note {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
  font-style: italic;
  padding: 0.5rem 0.75rem;
  background: #f1f5f9;
  border-radius: 6px;
  border-left: 3px solid #94a3b8;
}
.stimulus :deep(img) { max-width: 100%; height: auto; }
.stimulus :deep(video),
.stimulus :deep(audio) { max-width: 100%; width: 100%; }
.stimulus :deep(iframe) { max-width: 100%; border: 0; border-radius: 6px; }
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
  .exam-layout { flex-direction: column; }
  .question-map { width: 100%; position: static; }
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
