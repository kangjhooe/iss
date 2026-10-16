<template>
    <div class="exam-detail-page">
      <div class="page-bg">
        <div class="page-bg-orb page-bg-orb-1"></div>
        <div class="page-bg-orb page-bg-orb-2"></div>
      </div>

      <div class="page-inner">
        <router-link v-if="exam?.exam_period" :to="`/ujian-online/gelombang/${exam.exam_period.id}`" class="back-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
          </svg>
          {{ exam.exam_period.name }}
        </router-link>
        <router-link v-else to="/ujian-online/exams" class="back-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
          </svg>
          Daftar Ujian
        </router-link>

        <div v-if="loading" class="content-card loading-card">
          <p class="loading-text">Memuat...</p>
        </div>

        <template v-else-if="exam">
          <header class="detail-header">
            <div class="header-main">
              <span v-if="exam.code" class="exam-code-badge">{{ exam.code }}</span>
              <h1 class="exam-title">{{ exam.name }}</h1>
              <p v-if="exam.description" class="exam-desc">{{ exam.description }}</p>
              <p v-if="exam.subject" class="exam-subject">Mata pelajaran: {{ exam.subject.name }}</p>
              <p v-if="exam.exam_period" class="exam-subject">Gelombang: {{ exam.exam_period.name }}</p>
              <p v-if="exam.exam_period_id && !isExamAdmin" class="teacher-hint">Tugas Anda: pilih soal dari bank, lalu simpan paket. Jadwal & peserta diatur admin.</p>
            </div>
            <div class="header-actions">
              <router-link :to="`/ujian-online/exams/${encodeURIComponent(exam.code)}/edit`" class="btn-edit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>Edit Ujian</span>
              </router-link>
            </div>
          </header>

          <ol class="wizard-steps" aria-label="Langkah ujian">
            <li class="wizard-step" :class="{ 'is-done': true }"><span class="wizard-num">1</span> Identitas</li>
            <li class="wizard-step" :class="{ 'is-active': fokusPaket, 'is-done': !!attachedQuestions.length && !fokusPaket }"><span class="wizard-num">2</span> Paket soal</li>
            <li class="wizard-step" :class="{ 'is-active': !fokusPaket && !!attachedQuestions.length, 'is-done': !!(exam.sessions || []).length }"><span class="wizard-num">3</span> Sesi</li>
          </ol>

          <!-- Paket soal: bank besar → filter blok + keranjang -->
          <section ref="packageSectionRef" class="content-card section-card section-package" :class="{ 'section-focus': fokusPaket }">
            <h2 class="section-title">Paket soal ujian</h2>
            <p class="section-desc">
              Pilih per blok (stimulus / acak N), bukan scroll ratusan baris. Soal se-stimulus otomatis digandeng berurutan.
              Yang dipasang disalin ke paket; edit di bank tidak mengubah ujian.
              Bobot soal bersifat relatif — nilai akhir peserta dinormalisasi ke skala 0–100.
            </p>

            <p v-if="questionsLocked" class="lock-note">Paket terkunci karena sesi sudah berjalan atau selesai. Reset sesi ke draf untuk mengubah paket.</p>
            <p v-if="!exam.subject_id" class="lock-note">Ujian belum punya mata pelajaran. Edit ujian dan tetapkan mapel dulu.</p>

            <div class="package-layout">
              <div class="package-cart">
                <div class="cart-head">
                  <h3 class="cart-title">Keranjang paket</h3>
                  <span class="cart-count">{{ packageIds.length }} soal</span>
                </div>
                <div v-if="attachedQuestions.length" class="attached-list">
                  <div
                    v-for="(q, idx) in attachedQuestions"
                    :key="q.question_bank_id"
                    class="attached-row"
                    :class="{ 'is-set-start': isStimulusSetStart(idx), 'is-set-cont': isStimulusContinued(idx) }"
                  >
                    <div class="attached-reorder" v-if="!questionsLocked">
                      <button type="button" class="btn-move" :disabled="idx === 0" title="Naik" @click="movePackageItem(idx, -1)">↑</button>
                      <button type="button" class="btn-move" :disabled="idx === attachedQuestions.length - 1" title="Turun" @click="movePackageItem(idx, 1)">↓</button>
                    </div>
                    <span class="attached-num">{{ idx + 1 }}</span>
                    <div class="attached-main">
                      <p v-if="isStimulusSetStart(idx)" class="set-badge">Set: {{ q.stimulus_title || ('Stimulus #' + q.stimulus_id) }}</p>
                      <p v-else-if="isStimulusContinued(idx)" class="set-cont-label">lanjutan set</p>
                      <p class="attached-body">{{ q.body_preview || '—' }}</p>
                      <p class="attached-meta">
                        {{ typeLabel(q.type) }}
                        <template v-if="q.bank_code"> · {{ q.bank_name || q.bank_code }}</template>
                        <template v-if="q.bank_grade"> · rak kelas {{ q.bank_grade }}</template>
                        · bobot {{ q.weight }}
                      </p>
                    </div>
                    <button
                      v-if="!questionsLocked"
                      type="button"
                      class="btn-remove-q"
                      title="Lepas dari paket"
                      @click="removeFromPackage(q.question_bank_id)"
                    >×</button>
                  </div>
                </div>
                <p v-else class="empty-hint">Belum ada soal. Tambah dari panel kanan.</p>
                <div v-if="!questionsLocked" class="package-actions">
                  <button type="button" class="btn-primary btn-sm" :disabled="savingPackage || !packageIds.length" @click="savePackage">
                    {{ savingPackage ? 'Menyimpan…' : 'Simpan paket (' + packageIds.length + ' soal)' }}
                  </button>
                  <button v-if="packageIds.length" type="button" class="btn-secondary btn-sm" @click="clearPackage">Kosongkan</button>
                </div>
              </div>

              <div v-if="!questionsLocked" class="package-picker">
                <div class="quick-actions">
                  <div class="quick-row">
                    <select v-model="pickerStimulusId" class="field-input" @change="onStimulusFilterChange">
                      <option value="">Semua / tanpa filter stimulus</option>
                      <option value="none">Tanpa stimulus</option>
                      <option v-for="s in pickerStimuli" :key="s.id" :value="String(s.id)">
                        {{ s.title || ('Stimulus #' + s.id) }} ({{ s.questions_count ?? '?' }} soal)
                      </option>
                    </select>
                    <button
                      type="button"
                      class="btn-secondary btn-sm"
                      :disabled="!canAddStimulusSet || suggesting"
                      @click="addStimulusSet"
                    >
                      {{ suggesting === 'stimulus' ? '…' : 'Tambah set stimulus' }}
                    </button>
                  </div>
                  <div class="quick-row">
                    <label class="random-label">Acak</label>
                    <input v-model.number="randomCount" type="number" min="1" max="200" class="field-input field-narrow" />
                    <span class="random-hint">soal</span>
                    <button type="button" class="btn-secondary btn-sm" :disabled="suggesting || !exam.subject_id" @click="addRandomQuestions">
                      {{ suggesting === 'random' ? '…' : 'Tambah acak' }}
                    </button>
                  </div>
                  <p class="form-hint-inline">Acak menjaga set stimulus utuh (tidak memecah 1 bacaan jadi terpisah).</p>
                </div>

                <div class="picker-filters">
                  <input
                    v-model="pickerSearch"
                    type="search"
                    class="field-input"
                    placeholder="Cari teks soal…"
                    @keydown.enter.prevent="fetchPickerQuestions(1)"
                  />
                  <select v-model="pickerBankId" class="field-input" @change="onBankFilterChange">
                    <option value="">Semua rak</option>
                    <option v-for="b in pickerBanks" :key="b.id" :value="String(b.id)">
                      {{ b.name || b.code }}<template v-if="b.grade"> (kelas {{ b.grade }})</template>
                    </option>
                  </select>
                  <select v-if="gradeOptions.length" v-model="pickerGrade" class="field-input" @change="fetchPickerQuestions(1)">
                    <option value="">Semua tingkat</option>
                    <option v-for="g in gradeOptions" :key="g" :value="String(g)">Kelas {{ g }}</option>
                  </select>
                  <select v-model="pickerType" class="field-input" @change="fetchPickerQuestions(1)">
                    <option value="">Semua tipe</option>
                    <option value="pg">Pilihan ganda</option>
                    <option value="pg_kompleks">PG kompleks</option>
                    <option value="matching">Mencocokkan</option>
                    <option value="isian">Isian</option>
                    <option value="uraian">Uraian</option>
                  </select>
                  <select v-model="pickerDifficulty" class="field-input" @change="fetchPickerQuestions(1)">
                    <option value="">Semua kesulitan</option>
                    <option value="mudah">Mudah</option>
                    <option value="sedang">Sedang</option>
                    <option value="sulit">Sulit</option>
                  </select>
                  <select v-if="pickerBankId" v-model="pickerTopic" class="field-input" @change="fetchPickerQuestions(1)">
                    <option value="">Semua topik</option>
                    <option v-for="topic in pickerTopics" :key="topic" :value="topic">{{ topic }}</option>
                  </select>
                  <input
                    v-model="pickerTag"
                    type="search"
                    class="field-input"
                    placeholder="Tag"
                    @keydown.enter.prevent="fetchPickerQuestions(1)"
                  />
                  <button type="button" class="btn-secondary btn-sm" @click="fetchPickerQuestions(1)">Cari</button>
                </div>

                <div v-if="pickerLoading" class="loading-inline">Memuat soal bank…</div>
                <div v-else class="picker-table-wrap">
                  <table class="picker-table">
                    <thead>
                      <tr>
                        <th class="col-check"></th>
                        <th>Soal</th>
                        <th>Stimulus</th>
                        <th>Rak</th>
                        <th>Tipe</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="q in pickerQuestions" :key="q.id">
                        <td class="col-check">
                          <input
                            type="checkbox"
                            :checked="packageIds.includes(q.id)"
                            @change="togglePicker(q, $event.target.checked)"
                          />
                        </td>
                        <td>
                          {{ stripHtml(q.body || '').slice(0, 80) }}{{ stripHtml(q.body || '').length > 80 ? '…' : '' }}
                          <div v-if="q.difficulty_label || q.topic || q.tags?.length" class="picker-meta">
                            <span v-if="q.difficulty_label">{{ q.difficulty_label }}</span>
                            <span v-if="q.topic">{{ q.topic }}</span>
                            <span v-for="tag in (q.tags || [])" :key="tag">{{ tag }}</span>
                          </div>
                        </td>
                        <td class="col-stimulus">{{ q.stimulus?.title || (q.stimulus_id ? '#' + q.stimulus_id : '—') }}</td>
                        <td>
                          {{ q.bank?.name || q.bank?.code || '—' }}
                          <span v-if="q.bank?.grade" class="rak-grade">kelas {{ q.bank.grade }}</span>
                        </td>
                        <td>{{ typeLabel(q.type) }}</td>
                      </tr>
                      <tr v-if="!pickerQuestions.length">
                        <td colspan="5" class="empty-hint">Tidak ada soal yang cocok. Ubah filter atau isi bank mapel ini.</td>
                      </tr>
                    </tbody>
                  </table>
                  <PaginationBar
                    v-if="pickerMeta"
                    embedded
                    :page="pickerMeta.current_page"
                    :last-page="pickerMeta.last_page"
                    :per-page="pickerMeta.per_page"
                    :total="pickerMeta.total"
                    item-label="soal"
                    @page-change="fetchPickerQuestions"
                    @per-page-change="changePickerPerPage"
                  />
                </div>
                <router-link
                  :to="exam.subject_id ? `/ujian-online/bank-soal?subject_id=${exam.subject_id}` : '/ujian-online/bank-soal'"
                  class="btn-link-bank"
                >Kelola bank soal</router-link>
              </div>
            </div>
          </section>

          <div class="stats-row">
            <div class="stat-card">
              <span class="stat-value">{{ exam.duration_minutes ?? '–' }}</span>
              <span class="stat-label">Durasi (menit)</span>
            </div>
            <div class="stat-card">
              <span class="stat-value">{{ (exam.exam_questions || []).length }}</span>
              <span class="stat-label">Soal</span>
            </div>
            <div class="stat-card">
              <span class="stat-value">{{ (exam.sessions || []).length }}</span>
              <span class="stat-label">Sesi</span>
            </div>
          </div>

          <!-- Pengaturan ujian (disederhanakan) -->
          <section class="content-card section-card section-settings">
            <h2 class="section-title">Pengaturan ujian</h2>
            <div class="settings-inline">
              <div class="setting-item">
                <label>Durasi (menit)</label>
                <input v-model.number="settingsForm.duration_minutes" type="number" min="1" max="600" class="field-input field-narrow" />
              </div>
              <label class="checkbox-inline">
                <input v-model="settingsForm.shuffle_questions" type="checkbox" />
                <span>Acak urutan set (soal se-stimulus tetap berurutan)</span>
              </label>
              <label class="checkbox-inline">
                <input v-model="settingsForm.shuffle_options" type="checkbox" />
                <span>Acak opsi</span>
              </label>
              <label class="checkbox-inline">
                <input v-model="settingsForm.practice" type="checkbox" />
                <span>Mode latihan (nilai langsung tampil, boleh diulang, kode ujian tidak berganti)</span>
              </label>
              <div v-if="settingsForm.practice" class="setting-item">
                <label>Maksimal percobaan</label>
                <input v-model.number="settingsForm.max_attempts" type="number" min="1" max="20" class="field-input field-narrow" placeholder="Kosong = tanpa batas" />
              </div>
              <button type="button" class="btn-primary btn-sm" :disabled="savingSettings" @click="saveSettings">
                {{ savingSettings ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </section>

          <!-- Sesi: admin (atau ujian lepas tanpa gelombang) -->
          <section v-if="showSessionAdmin" class="content-card section-card section-sessions">
            <h2 class="section-title">Sesi ujian</h2>
            <p class="section-desc">Tambah sesi lalu kelola peserta di halaman sesi.</p>
            <div class="add-session-inline">
              <input
                v-model="newSessionName"
                type="text"
                placeholder="Nama sesi"
                maxlength="255"
                class="field-input"
                @keydown.enter.prevent="addSession"
              />
              <input v-model="newSessionStart" type="datetime-local" class="field-input field-datetime" title="Jadwal mulai" aria-label="Jadwal mulai" />
              <input v-model="newSessionEnd" type="datetime-local" class="field-input field-datetime" title="Jadwal selesai" aria-label="Jadwal selesai" />
              <button
                type="button"
                class="btn-primary btn-sm"
                :disabled="!newSessionName.trim() || addingSession"
                @click="addSession"
              >
                {{ addingSession ? '...' : 'Tambah sesi' }}
              </button>
            </div>
            <ul class="session-list">
              <li v-for="s in exam.sessions" :key="s.id">
                <router-link :to="`/ujian-online/sesi/${s.id}`" class="session-link">{{ s.name }}</router-link>
                <span v-if="s.scheduled_start_at" class="session-time">{{ formatSessionTime(s) }}</span>
                <span class="status-badge" :class="s.status">{{ statusBadgeLabel(s.status) }}</span>
              </li>
            </ul>
            <p v-if="!(exam.sessions && exam.sessions.length)" class="empty-hint">Belum ada sesi.</p>
          </section>
        </template>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { getValidGradesForLevel } from '@/utils/institution'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const exam = ref(null)
const loading = ref(true)
const newSessionName = ref('')
const newSessionStart = ref('')
const newSessionEnd = ref('')
const addingSession = ref(false)
const savingSettings = ref(false)
const settingsForm = ref({
  duration_minutes: 60,
  shuffle_questions: true,
  shuffle_options: true,
  practice: false,
  max_attempts: null
})

const packageIds = ref([])
const packageMeta = ref({}) // id -> { body_preview, type, weight, bank_*, stimulus_* }
const pickerBanks = ref([])
const pickerStimuli = ref([])
const pickerQuestions = ref([])
const pickerLoading = ref(false)
const pickerSearch = ref('')
const pickerBankId = ref('')
const pickerGrade = ref('')
const pickerType = ref('')
const pickerDifficulty = ref('')
const pickerTag = ref('')
const pickerTopic = ref('')
const pickerTopics = ref([])
const pickerStimulusId = ref('')
const pickerMeta = ref(null)
const pickerPerPage = ref(20)
const savingPackage = ref(false)
const suggesting = ref(null) // 'stimulus' | 'random' | null
const randomCount = ref(10)
const packageSectionRef = ref(null)
const fokusPaket = computed(() => route.query.fokus === 'paket' || !packageIds.value.length)
const isExamAdmin = computed(() => {
  const role = auth.user?.role
  return role === 'admin' || role === 'institution_admin' || role === 'super_admin'
})
const showSessionAdmin = computed(() => isExamAdmin.value || !exam.value?.exam_period_id)
const canAddStimulusSet = computed(() => {
  const v = pickerStimulusId.value
  return v && v !== 'none' && /^\d+$/.test(v)
})

function typeLabel(type) {
  const map = {
    pg: 'PG',
    pg_kompleks: 'PG kompleks',
    matching: 'Mencocokkan',
    isian: 'Isian',
    uraian: 'Uraian'
  }
  return map[type] || type || '—'
}

function stripHtml(html) {
  return String(html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
}

const questionsLocked = computed(() => !!exam.value?.questions_locked)
const gradeOptions = computed(() => {
  const level = auth.activeInstitution?.level || auth.user?.institution?.level || ''
  return getValidGradesForLevel(level) || []
})
const attachedQuestions = computed(() => {
  const byId = new Map()
  for (const q of exam.value?.exam_questions || []) {
    byId.set(q.question_bank_id, {
      question_bank_id: q.question_bank_id,
      body_preview: q.body_preview,
      type: q.type,
      weight: q.weight,
      bank_code: q.bank_code,
      bank_name: q.bank_name,
      bank_grade: q.bank_grade,
      stimulus_id: q.stimulus_id ?? null,
      stimulus_title: q.stimulus_title || null
    })
  }
  for (const [id, meta] of Object.entries(packageMeta.value)) {
    const nid = Number(id)
    if (!byId.has(nid)) byId.set(nid, { question_bank_id: nid, ...meta })
    else byId.set(nid, { ...byId.get(nid), ...meta })
  }
  for (const q of pickerQuestions.value) {
    const meta = metaFromPickerQuestion(q)
    if (!byId.has(q.id)) byId.set(q.id, meta)
    else byId.set(q.id, { ...byId.get(q.id), ...meta })
  }
  return packageIds.value.map((id) => byId.get(id)).filter(Boolean)
})

function metaFromPickerQuestion(q) {
  return {
    question_bank_id: q.id,
    body_preview: stripHtml(q.body || '').slice(0, 120),
    type: q.type,
    weight: q.weight,
    bank_code: q.bank?.code,
    bank_name: q.bank?.name,
    bank_grade: q.bank?.grade,
    stimulus_id: q.stimulus_id ?? q.stimulus?.id ?? null,
    stimulus_title: q.stimulus?.title || null
  }
}

function isStimulusSetStart(idx) {
  const list = attachedQuestions.value
  const q = list[idx]
  if (!q?.stimulus_id) return false
  if (idx === 0) return true
  return list[idx - 1]?.stimulus_id !== q.stimulus_id
}

function isStimulusContinued(idx) {
  const list = attachedQuestions.value
  const q = list[idx]
  if (!q?.stimulus_id || idx === 0) return false
  return list[idx - 1]?.stimulus_id === q.stimulus_id
}

function statusBadgeLabel(status) {
  const map = { draft: 'Draf', scheduled: 'Terjadwal', started: 'Berlangsung', ended: 'Selesai' }
  return map[status] || status
}

function formatSessionTime(s) {
  if (!s.scheduled_start_at || !s.scheduled_end_at) return ''
  const start = s.scheduled_start_at.slice(0, 16).replace('T', ' ')
  const end = s.scheduled_end_at.slice(0, 16).replace('T', ' ')
  return `${start} s/d ${end}`
}

async function fetchPickerBanks() {
  const subjectId = exam.value?.subject_id
  try {
    const params = { per_page: 100 }
    if (subjectId) params.subject_id = subjectId
    const res = await examApi.listBanks(params)
    pickerBanks.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    pickerBanks.value = []
  }
}

async function fetchPickerStimuli() {
  if (!exam.value?.subject_id && !pickerBankId.value) {
    pickerStimuli.value = []
    return
  }
  try {
    const params = { per_page: 100 }
    if (pickerBankId.value) params.bank_soal_id = pickerBankId.value
    else params.subject_id = exam.value.subject_id
    const res = await examApi.listStimuli(params)
    pickerStimuli.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    pickerStimuli.value = []
  }
}

async function fetchPickerQuestions(page = 1) {
  pickerLoading.value = true
  try {
    const params = { compact: 1, per_page: pickerPerPage.value, page, status: 'ready' }
    if (exam.value?.subject_id) params.subject_id = exam.value.subject_id
    if (pickerBankId.value) params.bank_soal_id = pickerBankId.value
    if (pickerGrade.value) params.grade = pickerGrade.value
    if (pickerType.value) params.type = pickerType.value
    if (pickerDifficulty.value) params.difficulty = pickerDifficulty.value
    if (pickerTopic.value) params.topic = pickerTopic.value
    if (pickerTag.value.trim()) params.tag = pickerTag.value.trim()
    if (pickerStimulusId.value) params.stimulus_id = pickerStimulusId.value
    if (pickerSearch.value.trim()) params.search = pickerSearch.value.trim()
    const res = await examApi.listQuestions(params)
    pickerQuestions.value = res.data?.data ?? []
    const meta = res.data?.meta
    pickerMeta.value = meta
      ? {
          current_page: meta.current_page,
          last_page: meta.last_page,
          per_page: meta.per_page ?? pickerPerPage.value,
          total: meta.total ?? 0,
        }
      : null
  } catch (e) {
    pickerQuestions.value = []
    pickerMeta.value = null
    toast.error('Gagal memuat soal', e.response?.data?.message || 'Coba lagi.')
  } finally {
    pickerLoading.value = false
  }
}

function changePickerPerPage(n) {
  pickerPerPage.value = n
  fetchPickerQuestions(1)
}

function onBankFilterChange() {
  pickerStimulusId.value = ''
  pickerTopic.value = ''
  fetchPickerStimuli()
  loadPickerTopics()
  fetchPickerQuestions(1)
}

async function loadPickerTopics() {
  if (!pickerBankId.value) {
    pickerTopics.value = []
    return
  }
  try {
    const res = await examApi.listQuestionTopics(pickerBankId.value)
    const list = res.data?.data ?? res.data ?? []
    pickerTopics.value = Array.isArray(list) ? list : []
  } catch (_) {
    pickerTopics.value = []
  }
}

function onStimulusFilterChange() {
  fetchPickerQuestions(1)
}

function syncPackageFromExam() {
  const rows = exam.value?.exam_questions || []
  packageIds.value = rows.map((q) => q.question_bank_id)
  const meta = { ...packageMeta.value }
  for (const q of rows) {
    meta[q.question_bank_id] = {
      body_preview: q.body_preview,
      type: q.type,
      weight: q.weight,
      bank_code: q.bank_code,
      bank_name: q.bank_name,
      bank_grade: q.bank_grade,
      stimulus_id: q.stimulus_id ?? null,
      stimulus_title: q.stimulus_title || null
    }
  }
  packageMeta.value = meta
}

function rememberMeta(q) {
  packageMeta.value = {
    ...packageMeta.value,
    [q.id]: metaFromPickerQuestion(q)
  }
}

function mergeIdsIntoPackage(ids) {
  const next = [...packageIds.value]
  const seen = new Set(next)
  for (const id of ids) {
    const nid = Number(id)
    if (!seen.has(nid)) {
      next.push(nid)
      seen.add(nid)
    }
  }
  packageIds.value = next
}

function togglePicker(q, checked) {
  rememberMeta(q)
  if (checked) {
    if (!packageIds.value.includes(q.id)) {
      packageIds.value = [...packageIds.value, q.id]
    }
  } else {
    removeFromPackage(q.id)
  }
}

function removeFromPackage(id) {
  packageIds.value = packageIds.value.filter((x) => x !== id)
}

function clearPackage() {
  packageIds.value = []
}

function movePackageItem(idx, delta) {
  const next = [...packageIds.value]
  const j = idx + delta
  if (j < 0 || j >= next.length) return
  const tmp = next[idx]
  next[idx] = next[j]
  next[j] = tmp
  packageIds.value = next
}

async function addStimulusSet() {
  if (!exam.value || !canAddStimulusSet.value) return
  suggesting.value = 'stimulus'
  try {
    const res = await examApi.suggestQuestions(exam.value.id, {
      mode: 'stimulus',
      stimulus_id: Number(pickerStimulusId.value),
      bank_soal_id: pickerBankId.value ? Number(pickerBankId.value) : undefined,
      exclude_ids: packageIds.value
    })
    const ids = res.data?.question_bank_ids ?? []
    if (!ids.length) {
      toast.error('Kosong', res.data?.message || 'Tidak ada soal baru untuk stimulus ini.')
      return
    }
    const stim = pickerStimuli.value.find((s) => String(s.id) === String(pickerStimulusId.value))
    for (const id of ids) {
      packageMeta.value = {
        ...packageMeta.value,
        [id]: {
          ...(packageMeta.value[id] || {}),
          question_bank_id: id,
          stimulus_id: Number(pickerStimulusId.value),
          stimulus_title: stim?.title || null,
          body_preview: packageMeta.value[id]?.body_preview || 'Soal set stimulus'
        }
      }
    }
    mergeIdsIntoPackage(ids)
    toast.success('Ditambahkan', `${ids.length} soal dari set stimulus masuk keranjang.`)
    fetchPickerQuestions(pickerMeta.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak dapat menambah set stimulus.')
  } finally {
    suggesting.value = null
  }
}

async function addRandomQuestions() {
  if (!exam.value) return
  const count = Math.max(1, Math.min(200, Number(randomCount.value) || 10))
  suggesting.value = 'random'
  try {
    const res = await examApi.suggestQuestions(exam.value.id, {
      mode: 'random',
      count,
      bank_soal_id: pickerBankId.value ? Number(pickerBankId.value) : undefined,
      type: pickerType.value || undefined,
      grade: pickerGrade.value ? Number(pickerGrade.value) : undefined,
      difficulty: pickerDifficulty.value || undefined,
      tag: pickerTag.value.trim() || undefined,
      topic: pickerTopic.value || undefined,
      exclude_ids: packageIds.value
    })
    const ids = res.data?.question_bank_ids ?? []
    if (!ids.length) {
      toast.error('Kosong', res.data?.message || 'Tidak ada soal untuk diacak.')
      return
    }
    mergeIdsIntoPackage(ids)
    toast.success('Ditambahkan', `${ids.length} soal acak masuk keranjang (set stimulus dijaga utuh).`)
    fetchPickerQuestions(pickerMeta.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak dapat mengacak soal.')
  } finally {
    suggesting.value = null
  }
}

async function savePackage() {
  if (!exam.value || !packageIds.value.length) return
  savingPackage.value = true
  try {
    const res = await examApi.attachQuestions(exam.value.id, packageIds.value)
    const updated = res.data?.exam?.data ?? res.data?.exam ?? res.data?.data
    if (updated) exam.value = updated
    else await fetchExam()
    toast.success('Berhasil', 'Paket soal disimpan. Edit di bank tidak mengubah ujian ini.')
    syncPackageFromExam()
  } catch (e) {
    toast.error('Gagal menyimpan paket', e.response?.data?.message || 'Coba lagi.')
  } finally {
    savingPackage.value = false
  }
}

async function fetchExam() {
  loading.value = true
  try {
    const res = await examApi.getExamByCode(route.params.code)
    exam.value = res.data?.data ?? res.data
    const e = exam.value
    settingsForm.value = {
      duration_minutes: e.duration_minutes ?? 60,
      shuffle_questions: e.shuffle_questions !== false,
      shuffle_options: e.shuffle_options !== false,
      practice: e.delivery_mode === 'practice',
      max_attempts: e.max_attempts ?? null
    }
    syncPackageFromExam()
    fetchPickerBanks()
    fetchPickerStimuli()
    if (!questionsLocked.value) fetchPickerQuestions(1)
    if (route.query.fokus === 'paket') {
      await nextTick()
      packageSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  } catch (e) {
    toast.error('Gagal memuat ujian', e.response?.data?.message || 'Data ujian tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  if (!exam.value) return
  savingSettings.value = true
  try {
    const payload = {
      code: exam.value.code,
      name: exam.value.name,
      description: exam.value.description ?? null,
      duration_minutes: settingsForm.value.duration_minutes ?? 60,
      shuffle_questions: settingsForm.value.shuffle_questions,
      shuffle_options: settingsForm.value.shuffle_options,
      delivery_mode: settingsForm.value.practice ? 'practice' : 'proctored',
      max_attempts: settingsForm.value.practice && settingsForm.value.max_attempts
        ? Number(settingsForm.value.max_attempts)
        : null,
      start_type: exam.value.start_type || 'manual',
      scheduled_start_at: exam.value.scheduled_start_at ?? null,
      scheduled_end_at: exam.value.scheduled_end_at ?? null
    }
    if (exam.value.subject_id != null && exam.value.subject_id !== '') payload.subject_id = exam.value.subject_id
    await examApi.updateExam(exam.value.id, payload)
    toast.success('Berhasil', 'Pengaturan disimpan.')
    await fetchExam()
  } catch (e) {
    toast.error('Gagal menyimpan pengaturan ujian', e.response?.data?.message || 'Pengaturan tidak dapat disimpan. Coba lagi.')
  } finally {
    savingSettings.value = false
  }
}

async function addSession() {
  const name = newSessionName.value.trim()
  if (!name) {
    toast.error('Validasi', 'Nama sesi wajib diisi.')
    return
  }
  addingSession.value = true
  try {
    const payload = {
      exam_id: exam.value.id,
      name,
      scheduled_start_at: newSessionStart.value || null,
      scheduled_end_at: newSessionEnd.value || null
    }
    await examApi.createSession(payload)
    toast.success('Berhasil', 'Sesi ditambahkan.')
    newSessionName.value = ''
    newSessionStart.value = ''
    newSessionEnd.value = ''
    await fetchExam()
  } catch (e) {
    const msg = e.response?.data?.message
    const errors = e.response?.data?.errors
    if (errors && typeof errors === 'object') {
      const firstMsg = Object.values(errors).flat().find(Boolean)
      toast.error('Gagal menambah sesi ujian', firstMsg || 'Sesi tidak dapat ditambahkan. Periksa data dan coba lagi.')
    } else {
      toast.error('Gagal menambah sesi ujian', msg || 'Sesi tidak dapat ditambahkan. Periksa data dan coba lagi.')
    }
  } finally {
    addingSession.value = false
  }
}

onMounted(() => {
  fetchExam()
})

watch(() => route.params.code, () => fetchExam())
</script>

<style scoped>
.exam-detail-page {
  min-height: 100%;
  position: relative;
}

.page-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  background: linear-gradient(160deg, #f0f9ff 0%, #ecfdf5 35%, #f0fdf4 60%, #f8fafc 100%);
  background-size: 400% 400%;
  animation: bgShift 14s ease infinite;
}

.page-bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.4;
  animation: orbFloat 18s ease-in-out infinite;
}

.page-bg-orb-1 {
  width: 320px;
  height: 320px;
  background: rgba(5, 150, 105, 0.2);
  top: -80px;
  right: -60px;
}

.page-bg-orb-2 {
  width: 280px;
  height: 280px;
  background: rgba(6, 182, 212, 0.15);
  bottom: -60px;
  left: -40px;
  animation-delay: -6s;
}

@keyframes bgShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

@keyframes orbFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(20px, -20px) scale(1.05); }
  66% { transform: translate(-15px, 15px) scale(0.95); }
}

.page-inner {
  position: relative;
  z-index: 1;
  padding: 1.5rem 1rem 2rem;
  max-width: 1100px;
  margin: 0 auto;
}

.wizard-steps {
  list-style: none;
  margin: 0 0 1.25rem;
  padding: 0;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.wizard-step {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  background: #f1f5f9;
}

.wizard-step.is-active {
  color: #047857;
  background: rgba(5, 150, 105, 0.12);
}

.wizard-step.is-done {
  color: #0f766e;
  background: rgba(13, 148, 136, 0.1);
}

.wizard-num {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  background: #e2e8f0;
  color: #64748b;
}

.wizard-step.is-active .wizard-num,
.wizard-step.is-done .wizard-num {
  background: #059669;
  color: #fff;
}

.section-focus {
  outline: 2px solid rgba(5, 150, 105, 0.35);
  outline-offset: 2px;
}

.package-layout {
  display: grid;
  grid-template-columns: minmax(260px, 1fr) minmax(320px, 1.35fr);
  gap: 1rem;
  margin-top: 0.75rem;
}

@media (max-width: 860px) {
  .package-layout {
    grid-template-columns: 1fr;
  }
}

.package-cart,
.package-picker {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem;
  background: #f8fafc;
}

.cart-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.65rem;
}

.cart-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.cart-count {
  font-size: 0.75rem;
  font-weight: 600;
  color: #047857;
  background: rgba(5, 150, 105, 0.12);
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
}

.attached-reorder {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.btn-move {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 4px;
  width: 1.5rem;
  height: 1.35rem;
  line-height: 1;
  cursor: pointer;
  color: #475569;
  padding: 0;
}

.btn-move:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.set-badge {
  margin: 0 0 0.2rem;
  font-size: 0.7rem;
  font-weight: 700;
  color: #0f766e;
  text-transform: none;
}

.set-cont-label {
  margin: 0 0 0.15rem;
  font-size: 0.68rem;
  color: #64748b;
  font-style: italic;
}

.attached-row.is-set-start {
  border-left: 3px solid #0d9488;
}

.attached-row.is-set-cont {
  border-left: 3px solid #99f6e4;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px dashed #cbd5e1;
}

.quick-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
}

.random-label,
.random-hint {
  font-size: 0.8rem;
  color: #475569;
  font-weight: 600;
}

.form-hint-inline {
  margin: 0;
  font-size: 0.72rem;
  color: #64748b;
  line-height: 1.4;
}

.col-stimulus {
  font-size: 0.78rem;
  color: #475569;
  max-width: 120px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: color 0.2s, transform 0.2s;
}

.back-link:hover {
  color: #059669;
  transform: translateX(-2px);
}

.loading-card {
  padding: 2rem;
  text-align: center;
}

.loading-text {
  margin: 0;
  color: #64748b;
}

.detail-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.header-main {
  flex: 1;
  min-width: 0;
}

.exam-code-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #047857;
  background: rgba(5, 150, 105, 0.12);
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.exam-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.35rem 0;
  line-height: 1.3;
  letter-spacing: -0.02em;
}

.exam-desc {
  font-size: 0.9rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.exam-subject {
  font-size: 0.875rem;
  color: #475569;
  margin: 0.25rem 0 0 0;
}

.teacher-hint {
  margin: 0.5rem 0 0;
  padding: 0.5rem 0.75rem;
  background: #ecfdf5;
  border-radius: 8px;
  color: #065f46;
  font-size: 0.85rem;
}

.btn-edit {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0369a1;
  background: rgba(14, 165, 233, 0.1);
  border: 1px solid rgba(14, 165, 233, 0.4);
  border-radius: 10px;
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s;
}

.btn-edit:hover {
  background: rgba(14, 165, 233, 0.18);
  border-color: #0ea5e9;
}

/* Section: Paket soal */
.section-package {
  margin-bottom: 1rem;
}

.attached-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 0.75rem 0 1rem;
}

.attached-row {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.55rem 0.7rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.attached-num {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 999px;
  background: #059669;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 0.1rem;
}

.attached-main {
  flex: 1;
  min-width: 0;
}

.attached-body {
  margin: 0;
  font-size: 0.875rem;
  color: #0f172a;
  line-height: 1.4;
}

.attached-meta {
  margin: 0.2rem 0 0;
  font-size: 0.75rem;
  color: #64748b;
}

.btn-remove-q {
  border: none;
  background: transparent;
  color: #94a3b8;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  padding: 0 0.2rem;
}

.btn-remove-q:hover {
  color: #dc2626;
}

.lock-note {
  font-size: 0.85rem;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin: 0.5rem 0 0;
}

.picker-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0.75rem 0;
}

.picker-filters .field-input {
  flex: 1 1 140px;
  min-width: 0;
}

.picker-table-wrap {
  overflow-x: auto;
  margin-bottom: 0.75rem;
}

.picker-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.picker-table th,
.picker-table td {
  text-align: left;
  padding: 0.45rem 0.5rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}

.picker-table th {
  color: #64748b;
  font-weight: 600;
  font-size: 0.75rem;
}

.picker-table .col-check {
  width: 32px;
}

.rak-grade {
  display: inline-block;
  margin-left: 0.25rem;
  font-size: 0.7rem;
  color: #0369a1;
}
.picker-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-top: 0.2rem;
  font-size: 0.7rem;
  color: #475569;
}
.picker-meta span {
  background: #f1f5f9;
  border-radius: 999px;
  padding: 0.05rem 0.4rem;
}

.picker-pager {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.5rem;
}

.package-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-link-bank {
  color: #059669;
  text-decoration: none;
  font-size: 0.875rem;
}

.btn-link-bank:hover {
  text-decoration: underline;
}

/* Section: Mapel siap diujikan */
.section-ready {
  margin-bottom: 1rem;
}

.ready-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.ready-card {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.ready-card.is-exam {
  border-color: #059669;
  background: rgba(5, 150, 105, 0.08);
}

.ready-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: #334155;
}

.ready-count {
  font-size: 0.8rem;
  color: #64748b;
}

.loading-inline {
  color: #64748b;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.empty-ready {
  font-size: 0.9rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.empty-ready a {
  color: #059669;
  text-decoration: none;
}

.empty-ready a:hover {
  text-decoration: underline;
}

.soal-managed-hint {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
  padding-top: 0.5rem;
  border-top: 1px solid #e2e8f0;
}

.soal-managed-hint a {
  color: #059669;
  text-decoration: none;
}

.soal-managed-hint a:hover {
  text-decoration: underline;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(8px);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #059669;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
  display: block;
}

.content-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.section-card .section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #334155;
  margin: 0 0 0.35rem 0;
}

.section-desc {
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.75rem;
  line-height: 1.5;
}

/* Pengaturan disederhanakan */
.settings-inline {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

.setting-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.setting-item label {
  font-size: 0.9rem;
  color: #475569;
}

.field-narrow {
  width: 80px;
}

.checkbox-inline {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #475569;
}

.field-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}

.btn-primary {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border: none;
  border-radius: 10px;
  cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.4rem 0.85rem;
  font-size: 0.85rem;
}

/* Sesi disederhanakan */
.add-session-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 1rem;
}

.add-session-inline .field-input {
  flex: 1;
  min-width: 120px;
}

.field-datetime {
  min-width: 160px;
}

.session-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.session-list li {
  padding: 0.6rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  border-bottom: 1px solid #f1f5f9;
}

.session-list li:last-child {
  border-bottom: none;
}

.session-link {
  color: #059669;
  text-decoration: none;
  font-weight: 500;
}

.session-link:hover {
  text-decoration: underline;
}

.session-time {
  font-size: 0.8rem;
  color: #64748b;
}

.status-badge {
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-weight: 500;
}

.status-badge.draft {
  background: #e2e8f0;
  color: #475569;
}

.status-badge.scheduled {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.started {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.ended {
  background: #fef3c7;
  color: #92400e;
}

.empty-hint {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

@media (max-width: 640px) {
  .page-inner {
    padding: 1rem 0.75rem;
  }

  .exam-title {
    font-size: 1.25rem;
  }

  .ready-grid {
    grid-template-columns: 1fr 1fr;
  }

  .settings-inline {
    flex-direction: column;
    align-items: flex-start;
  }

  .add-session-inline {
    flex-direction: column;
    align-items: stretch;
  }

  .add-session-inline .field-input,
  .field-datetime {
    min-width: 0;
  }
}
</style>
