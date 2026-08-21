<template>
  <Layout>
    <div class="exam-detail-page">
      <div class="page-bg">
        <div class="page-bg-orb page-bg-orb-1"></div>
        <div class="page-bg-orb page-bg-orb-2"></div>
      </div>

      <div class="page-inner">
        <router-link to="/ujian-online/exams" class="back-link">
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

          <!-- Paket soal: seleksi dari gudang, salinan saat dipasang -->
          <section class="content-card section-card section-package">
            <h2 class="section-title">Paket soal ujian</h2>
            <p class="section-desc">
              Pilih soal dari semua rak mapel ini. Tingkat hanya filter — soal rak kelas 7 boleh dipakai di ujian kelas 9.
              Yang dipasang disalin ke paket ini; edit di bank tidak mengubah ujian.
            </p>

            <div v-if="attachedQuestions.length" class="attached-list">
              <div v-for="(q, idx) in attachedQuestions" :key="q.question_bank_id" class="attached-row">
                <span class="attached-num">{{ idx + 1 }}</span>
                <div class="attached-main">
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
            <p v-else class="empty-hint">Belum ada soal di paket ini.</p>

            <p v-if="questionsLocked" class="lock-note">Paket terkunci karena sesi sudah berjalan atau selesai. Reset sesi ke draf untuk mengubah paket.</p>

            <template v-else>
              <div class="picker-filters">
                <input
                  v-model="pickerSearch"
                  type="search"
                  class="field-input"
                  placeholder="Cari teks soal…"
                  @keydown.enter.prevent="fetchPickerQuestions(1)"
                />
                <select v-model="pickerBankId" class="field-input" @change="fetchPickerQuestions(1)">
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
                <button type="button" class="btn-secondary btn-sm" @click="fetchPickerQuestions(1)">Cari</button>
              </div>
              <p v-if="!exam.subject_id" class="lock-note">Tetapkan mata pelajaran ujian agar pilihan dibatasi ke mapel yang sama.</p>
              <div v-if="pickerLoading" class="loading-inline">Memuat soal bank…</div>
              <div v-else class="picker-table-wrap">
                <table class="picker-table">
                  <thead>
                    <tr>
                      <th class="col-check"></th>
                      <th>Soal</th>
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
                          :disabled="packageIds.includes(q.id)"
                          @change="togglePicker(q, $event.target.checked)"
                        />
                      </td>
                      <td>{{ stripHtml(q.body || '').slice(0, 90) }}{{ stripHtml(q.body || '').length > 90 ? '…' : '' }}</td>
                      <td>
                        {{ q.bank?.name || q.bank?.code || '—' }}
                        <span v-if="q.bank?.grade" class="rak-grade">kelas {{ q.bank.grade }}</span>
                      </td>
                      <td>{{ typeLabel(q.type) }}</td>
                    </tr>
                    <tr v-if="!pickerQuestions.length">
                      <td colspan="4" class="empty-hint">Tidak ada soal yang cocok. Ubah filter atau isi bank mapel ini.</td>
                    </tr>
                  </tbody>
                </table>
                <div v-if="pickerMeta && pickerMeta.last_page > 1" class="picker-pager">
                  <button type="button" class="btn-secondary btn-sm" :disabled="pickerMeta.current_page <= 1" @click="fetchPickerQuestions(pickerMeta.current_page - 1)">Sebelumnya</button>
                  <span>Halaman {{ pickerMeta.current_page }} / {{ pickerMeta.last_page }}</span>
                  <button type="button" class="btn-secondary btn-sm" :disabled="pickerMeta.current_page >= pickerMeta.last_page" @click="fetchPickerQuestions(pickerMeta.current_page + 1)">Selanjutnya</button>
                </div>
              </div>
              <div class="package-actions">
                <button type="button" class="btn-primary btn-sm" :disabled="savingPackage || !packageIds.length" @click="savePackage">
                  {{ savingPackage ? 'Menyimpan…' : 'Simpan paket (' + packageIds.length + ' soal)' }}
                </button>
                <router-link to="/ujian-online/bank-soal" class="btn-link-bank">Kelola bank soal</router-link>
              </div>
            </template>
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
                <span>Acak soal</span>
              </label>
              <label class="checkbox-inline">
                <input v-model="settingsForm.shuffle_options" type="checkbox" />
                <span>Acak opsi</span>
              </label>
              <button type="button" class="btn-primary btn-sm" :disabled="savingSettings" @click="saveSettings">
                {{ savingSettings ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </section>

          <!-- Sesi (disederhanakan) -->
          <section class="content-card section-card section-sessions">
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
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
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
  shuffle_options: true
})

const packageIds = ref([])
const pickerBanks = ref([])
const pickerQuestions = ref([])
const pickerLoading = ref(false)
const pickerSearch = ref('')
const pickerBankId = ref('')
const pickerGrade = ref('')
const pickerType = ref('')
const pickerMeta = ref(null)
const savingPackage = ref(false)

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
    byId.set(q.question_bank_id, q)
  }
  for (const q of pickerQuestions.value) {
    if (!byId.has(q.id)) {
      byId.set(q.id, {
        question_bank_id: q.id,
        body_preview: stripHtml(q.body || '').slice(0, 120),
        type: q.type,
        weight: q.weight,
        bank_code: q.bank?.code,
        bank_name: q.bank?.name,
        bank_grade: q.bank?.grade
      })
    }
  }
  return packageIds.value.map((id) => byId.get(id)).filter(Boolean)
})

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

async function fetchPickerQuestions(page = 1) {
  pickerLoading.value = true
  try {
    const params = { compact: 1, per_page: 20, page }
    if (exam.value?.subject_id) params.subject_id = exam.value.subject_id
    if (pickerBankId.value) params.bank_soal_id = pickerBankId.value
    if (pickerGrade.value) params.grade = pickerGrade.value
    if (pickerType.value) params.type = pickerType.value
    if (pickerSearch.value.trim()) params.search = pickerSearch.value.trim()
    const res = await examApi.listQuestions(params)
    pickerQuestions.value = res.data?.data ?? []
    const meta = res.data?.meta
    pickerMeta.value = meta
      ? { current_page: meta.current_page, last_page: meta.last_page, total: meta.total }
      : null
  } catch (e) {
    pickerQuestions.value = []
    pickerMeta.value = null
    toast.error('Gagal memuat soal', e.response?.data?.message || 'Coba lagi.')
  } finally {
    pickerLoading.value = false
  }
}

function syncPackageFromExam() {
  const rows = exam.value?.exam_questions || []
  packageIds.value = rows.map((q) => q.question_bank_id)
}

function togglePicker(q, checked) {
  if (!checked) return
  if (!packageIds.value.includes(q.id)) {
    packageIds.value = [...packageIds.value, q.id]
  }
}

function removeFromPackage(id) {
  packageIds.value = packageIds.value.filter((x) => x !== id)
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
      shuffle_options: e.shuffle_options !== false
    }
    syncPackageFromExam()
    fetchPickerBanks()
    if (!questionsLocked.value) fetchPickerQuestions(1)
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
  max-width: 880px;
  margin: 0 auto;
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
