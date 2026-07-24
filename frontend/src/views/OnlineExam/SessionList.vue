<template>
  <Layout>
    <div class="session-list-page">
      <header class="page-header">
        <router-link to="/ujian-online/exams" class="back-link">← Ujian</router-link>
        <div class="header-row">
          <div>
            <h2>{{ pageTitle }}</h2>
            <p v-if="pageDescription" class="page-description">{{ pageDescription }}</p>
          </div>
          <div class="header-actions">
            <router-link to="/ujian-online/exams" class="btn-secondary">Daftar Ujian</router-link>
            <button type="button" class="btn-primary" @click="showAddForm = true">
              Tambah sesi
            </button>
          </div>
        </div>
      </header>

      <!-- Form tambah sesi: pilih ujian + nama + jadwal -->
      <div v-if="showAddForm" class="content-card add-session-card">
        <h3>Tambah sesi baru</h3>
        <p class="form-desc">Pilih ujian yang sudah ada, lalu isi nama sesi dan (opsional) jadwal.</p>
        <div class="add-session-form">
          <div class="form-group">
            <label for="new-session-exam">Ujian <span class="required">*</span></label>
            <select id="new-session-exam" v-model="newSessionExamId" class="form-select">
              <option value="">– Pilih ujian –</option>
              <option v-for="ex in exams" :key="ex.id" :value="ex.id">
                {{ ex.name }}{{ ex.code ? ` (${ex.code})` : '' }}
              </option>
            </select>
            <p v-if="exams.length === 0 && !loadingExams" class="form-hint">Belum ada ujian. <router-link to="/ujian-online/exams/buat">Buat ujian dulu</router-link>.</p>
            <p v-else-if="loadingExams" class="form-hint">Memuat daftar ujian...</p>
          </div>
          <div class="form-group">
            <label for="new-session-name">Nama sesi <span class="required">*</span></label>
            <input id="new-session-name" v-model="newSessionName" type="text" placeholder="Mis: Sesi Pagi" maxlength="255" class="form-input" />
          </div>
          <div class="form-group">
            <label for="new-session-start">Jadwal mulai (opsional)</label>
            <input id="new-session-start" v-model="newSessionStart" type="datetime-local" class="form-input" />
          </div>
          <div class="form-group">
            <label for="new-session-end">Jadwal selesai (opsional)</label>
            <input id="new-session-end" v-model="newSessionEnd" type="datetime-local" class="form-input" />
          </div>
          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="closeAddForm">Batal</button>
            <button
              type="button"
              class="btn-primary"
              :disabled="!canSubmitAddSession || addingSession"
              @click="submitAddSession"
            >
              {{ addingSession ? 'Menambah...' : 'Tambah sesi' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="content-card"><p>Memuat...</p></div>
      <div v-else-if="sessions.length === 0 && !showAddForm" class="empty-state content-card">
        <h3>Belum ada sesi</h3>
        <p>Pilih ujian di atas lalu klik <strong>Tambah sesi</strong>, atau buat ujian dulu dari Daftar Ujian.</p>
        <router-link to="/ujian-online/exams" class="btn-primary">Daftar Ujian</router-link>
      </div>
      <div v-else-if="sessions.length > 0" class="table-wrap content-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Sesi</th>
              <th>Ujian</th>
              <th>Mapel</th>
              <th>Peserta</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sessions" :key="s.id">
              <td><strong>{{ s.name }}</strong></td>
              <td>{{ s.exam?.name }}</td>
              <td>{{ s.exam?.subject?.name }}</td>
              <td>{{ s.participants_count ?? 0 }}</td>
              <td><span :class="['status-badge', s.status]">{{ statusLabel(s.status) }}</span></td>
              <td>
                <router-link :to="sessionDetailLink(s.id)" class="btn-action btn-edit">{{ actionLabel }}</router-link>
              </td>
            </tr>
          </tbody>
        </table>
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

const route = useRoute()
const toast = useToast()
const sessions = ref([])
const loading = ref(true)
const showAddForm = ref(false)
const exams = ref([])
const loadingExams = ref(false)
const newSessionExamId = ref('')
const newSessionName = ref('')
const newSessionStart = ref('')
const newSessionEnd = ref('')
const addingSession = ref(false)

const fokus = computed(() => route.query.fokus || '')

const pageTitle = computed(() => {
  if (fokus.value === 'peserta') return 'Peserta Ujian'
  if (fokus.value === 'kontrol') return 'Kontrol Ujian'
  return 'Sesi Ujian'
})

const pageDescription = computed(() => {
  if (fokus.value === 'peserta') return 'Pilih sesi untuk mengatur peserta dan cetak kartu ujian.'
  if (fokus.value === 'kontrol') return 'Pilih sesi untuk mulai/akhiri ujian, pantau peserta live, koreksi, rilis nilai, dan export laporan.'
  return ''
})

const actionLabel = computed(() => {
  if (fokus.value === 'peserta') return 'Kelola peserta'
  if (fokus.value === 'kontrol') return 'Kontrol & monitoring'
  return 'Detail & Kendali'
})

const canSubmitAddSession = computed(() => {
  const id = newSessionExamId.value
  const name = newSessionName.value.trim()
  return id && name.length > 0
})

function sessionDetailLink(sessionId) {
  const q = fokus.value ? { fokus: fokus.value } : {}
  return { path: `/ujian-online/sesi/${sessionId}`, query: q }
}

async function fetchExams() {
  loadingExams.value = true
  try {
    const res = await examApi.listExams({ per_page: 100 })
    const data = res.data
    const list = data?.data ?? data ?? []
    exams.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch (e) {
    toast.error('Gagal memuat daftar sesi ujian', e.response?.data?.message || e.formattedMessage || 'Daftar sesi tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    exams.value = []
  } finally {
    loadingExams.value = false
  }
}

watch(showAddForm, (visible) => {
  if (visible && exams.value.length === 0 && !loadingExams.value) {
    fetchExams()
  }
})

function closeAddForm() {
  showAddForm.value = false
  newSessionExamId.value = ''
  newSessionName.value = ''
  newSessionStart.value = ''
  newSessionEnd.value = ''
}

async function submitAddSession() {
  if (!canSubmitAddSession.value) return
  const examId = Number(newSessionExamId.value)
  const name = newSessionName.value.trim()
  addingSession.value = true
  try {
    await examApi.createSession({
      exam_id: examId,
      name,
      scheduled_start_at: newSessionStart.value || null,
      scheduled_end_at: newSessionEnd.value || null
    })
    toast.success('Berhasil', 'Sesi ujian telah ditambahkan.')
    closeAddForm()
    await fetchSessions()
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

async function fetchSessions() {
  loading.value = true
  try {
    const res = await examApi.listSessions({ per_page: 50 })
    sessions.value = res.data?.data ?? res.data ?? []
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchSessions())

function statusLabel(s) {
  const map = { draft: 'Draf', scheduled: 'Terjadwal', started: 'Berlangsung', ended: 'Selesai', registered: 'Terdaftar', submitted: 'Selesai' }
  return map[s] || s
}
</script>

<style scoped>
.session-list-page { padding: 1rem; }
.page-header { margin-bottom: 1rem; }
.header-row { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.header-actions { display: flex; gap: 0.5rem; align-items: center; flex-shrink: 0; }
.page-description { margin: 0.25rem 0 0; font-size: 0.9375rem; color: #6b7280; }
.back-link { color: #059669; text-decoration: none; }
.btn-secondary { padding: 0.5rem 1rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #475569; text-decoration: none; font-size: 0.9375rem; }
.btn-secondary:hover { background: #f1f5f9; }
.btn-primary { padding: 0.5rem 1rem; background: #059669; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9375rem; }
.btn-primary:hover:not(:disabled) { background: #047857; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.add-session-card { margin-bottom: 1rem; }
.add-session-card h3 { margin: 0 0 0.25rem 0; font-size: 1.125rem; }
.form-desc { font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem 0; }
.add-session-form { display: grid; gap: 0.75rem; max-width: 420px; }
.add-session-form .form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.add-session-form label { font-size: 0.875rem; font-weight: 500; color: #374151; }
.add-session-form .required { color: #dc2626; }
.form-select, .form-input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9375rem; }
.form-hint { font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0; }
.form-hint a { color: #059669; }
.form-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }

.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.status-badge { font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
.btn-action { margin-right: 0.5rem; color: #059669; text-decoration: none; }
.empty-state { text-align: center; padding: 2rem; }
.empty-state .btn-primary { display: inline-block; margin-top: 0.5rem; padding: 0.5rem 1rem; background: #059669; color: #fff; border-radius: 6px; text-decoration: none; }
</style>
