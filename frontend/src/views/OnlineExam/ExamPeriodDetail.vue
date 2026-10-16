<template>
  <div class="period-detail-page">
    <router-link to="/ujian-online/gelombang" class="back-link">← Gelombang ujian</router-link>

    <div v-if="loading" class="content-card"><p class="muted">Memuat…</p></div>
    <template v-else-if="period">
      <header class="detail-header content-card">
        <div>
          <span class="code-badge">{{ period.code }}</span>
          <h1>{{ period.name }}</h1>
          <p class="muted">{{ formatWindow(period) }} · {{ statusLabel(period.status) }}</p>
        </div>
      </header>

      <ol class="steps">
        <li :class="{ done: !!period.window_start_at }">1. Jadwal</li>
        <li :class="{ done: (period.exams || []).length > 0 }">2. Mapel</li>
        <li :class="{ done: hasAnyParticipant }">3. Peserta</li>
        <li :class="{ done: (period.exams_ready_count || 0) > 0 }">4. Guru isi soal</li>
      </ol>

      <!-- 1 Jadwal -->
      <section v-if="canManage" class="content-card">
        <h2>1. Jadwal gelombang</h2>
        <p class="hint">Satu jadwal untuk semua mapel di UAS/UTS ini. Simpan agar sesi mapel ikut terbarui.</p>
        <div class="row">
          <label>Mulai
            <input v-model="scheduleForm.window_start_at" type="datetime-local" />
          </label>
          <label>Selesai
            <input v-model="scheduleForm.window_end_at" type="datetime-local" />
          </label>
          <button type="button" class="btn-primary btn-sm" :disabled="savingSchedule" @click="saveSchedule">
            {{ savingSchedule ? '…' : 'Simpan jadwal' }}
          </button>
        </div>
      </section>

      <!-- 2 Mapel -->
      <section class="content-card">
        <h2>2. Mata pelajaran</h2>
        <p class="hint">Setiap mapel = satu ujian di dalam gelombang ini. Guru mapel yang mengisi soal.</p>

        <div v-if="canManage" class="add-subjects">
          <select v-model="selectedSubjectId" class="field">
            <option value="">Pilih mapel…</option>
            <option v-for="s in availableSubjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
          <button type="button" class="btn-secondary btn-sm" :disabled="!selectedSubjectId || addingSubject" @click="addOneSubject">
            {{ addingSubject ? '…' : '+ Tambah mapel' }}
          </button>
          <button type="button" class="btn-secondary btn-sm" :disabled="!availableSubjects.length || addingSubject" @click="addAllSubjects">
            Tambah semua mapel
          </button>
        </div>

        <table v-if="(period.exams || []).length" class="data-table">
          <thead>
            <tr>
              <th>Mapel</th>
              <th>Soal</th>
              <th>Peserta</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ex in period.exams" :key="ex.id">
              <td>
                <strong>{{ ex.subject?.name || ex.name }}</strong>
                <div class="code">{{ ex.code }}</div>
              </td>
              <td>
                <span :class="(ex.questions_count || 0) > 0 ? 'ok' : 'warn'">
                  {{ ex.questions_count || 0 }} soal
                </span>
              </td>
              <td>{{ participantsOf(ex) }}</td>
              <td class="col-actions">
                <router-link
                  :to="`/ujian-online/exams/${encodeURIComponent(ex.code)}?fokus=paket`"
                  class="btn-secondary btn-sm"
                >
                  {{ canManage ? 'Kelola / soal' : 'Isi soal' }}
                </router-link>
                <router-link
                  v-if="primarySession(ex)"
                  :to="`/ujian-online/sesi/${primarySession(ex).id}?fokus=peserta`"
                  class="btn-secondary btn-sm"
                >Peserta</router-link>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="muted">Belum ada mapel. Admin tambahkan dulu.</p>
      </section>

      <!-- 3 Peserta -->
      <section v-if="canManage" class="content-card">
        <h2>3. Peserta (dari kelas)</h2>
        <p class="hint">Pilih kelas — siswa di kelas itu dimasukkan ke sesi utama semua mapel di gelombang ini.</p>
        <div class="class-grid">
          <label v-for="c in classes" :key="c.id" class="class-chip">
            <input v-model="selectedClassIds" type="checkbox" :value="c.id" />
            {{ c.name }}
          </label>
        </div>
        <button
          type="button"
          class="btn-primary btn-sm"
          :disabled="!selectedClassIds.length || !period.exams?.length || syncing"
          @click="syncParticipants"
        >
          {{ syncing ? '…' : 'Terapkan ke semua mapel' }}
        </button>
      </section>

      <section class="content-card tip">
        <h2>Untuk guru mapel</h2>
        <p>Buka baris mapel Anda → <strong>Isi soal</strong> → pilih dari bank soal → simpan paket. Jadwal & peserta sudah diatur admin.</p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { examApi } from '@/api/exam'
import { classApi } from '@/api/class'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()

const period = ref(null)
const loading = ref(true)
const subjects = ref([])
const classes = ref([])
const selectedSubjectId = ref('')
const selectedClassIds = ref([])
const savingSchedule = ref(false)
const addingSubject = ref(false)
const syncing = ref(false)
const scheduleForm = ref({ window_start_at: '', window_end_at: '' })

const canManage = computed(() => {
  const role = auth.user?.role
  return role === 'admin' || role === 'institution_admin' || role === 'super_admin'
})

const existingSubjectIds = computed(() => new Set((period.value?.exams || []).map((e) => Number(e.subject_id))))
const availableSubjects = computed(() =>
  subjects.value.filter((s) => !existingSubjectIds.value.has(Number(s.id)))
)
const hasAnyParticipant = computed(() =>
  (period.value?.exams || []).some((ex) => participantsOf(ex) > 0)
)

function statusLabel(s) {
  return ({ draft: 'Draf', open: 'Dibuka', closed: 'Ditutup' })[s] || s
}

function formatWindow(p) {
  if (!p.window_start_at && !p.window_end_at) return 'Belum dijadwalkan'
  const a = p.window_start_at ? p.window_start_at.slice(0, 16).replace('T', ' ') : '—'
  const b = p.window_end_at ? p.window_end_at.slice(0, 16).replace('T', ' ') : '—'
  return `${a} → ${b}`
}

function toLocalInput(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function primarySession(ex) {
  const sessions = ex.sessions || []
  return sessions.slice().sort((a, b) => a.id - b.id)[0] || null
}

function participantsOf(ex) {
  const s = primarySession(ex)
  if (s?.participants_count != null) return s.participants_count
  return ex.participants_count ?? 0
}

async function fetchPeriod() {
  loading.value = true
  try {
    const res = await examApi.getPeriod(route.params.id)
    period.value = res.data?.data ?? res.data
    scheduleForm.value = {
      window_start_at: toLocalInput(period.value?.window_start_at),
      window_end_at: toLocalInput(period.value?.window_end_at)
    }
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gelombang tidak ditemukan.')
    period.value = null
  } finally {
    loading.value = false
  }
}

async function fetchSubjects() {
  try {
    const res = await examApi.listSubjects({ per_page: 'all', active_only: true })
    subjects.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    subjects.value = []
  }
}

async function fetchClasses() {
  try {
    const res = await classApi.getAll({ per_page: 'all' })
    classes.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    classes.value = []
  }
}

async function saveSchedule() {
  savingSchedule.value = true
  try {
    const res = await examApi.updatePeriod(period.value.id, {
      window_start_at: scheduleForm.value.window_start_at || null,
      window_end_at: scheduleForm.value.window_end_at || null,
      apply_schedule_to_exams: true
    })
    period.value = { ...period.value, ...(res.data?.data ?? res.data) }
    toast.success('Berhasil', 'Jadwal disimpan dan diterapkan ke mapel.')
    await fetchPeriod()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Jadwal tidak tersimpan.')
  } finally {
    savingSchedule.value = false
  }
}

async function addOneSubject() {
  if (!selectedSubjectId.value) return
  addingSubject.value = true
  try {
    const res = await examApi.addPeriodSubjects(period.value.id, [Number(selectedSubjectId.value)])
    period.value = res.data?.period?.data ?? res.data?.period ?? period.value
    selectedSubjectId.value = ''
    toast.success('Berhasil', res.data?.message || 'Mapel ditambah.')
    await fetchPeriod()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak bisa menambah mapel.')
  } finally {
    addingSubject.value = false
  }
}

async function addAllSubjects() {
  const ids = availableSubjects.value.map((s) => Number(s.id))
  if (!ids.length) return
  addingSubject.value = true
  try {
    const res = await examApi.addPeriodSubjects(period.value.id, ids)
    toast.success('Berhasil', res.data?.message || 'Mapel ditambah.')
    await fetchPeriod()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak bisa menambah mapel.')
  } finally {
    addingSubject.value = false
  }
}

async function syncParticipants() {
  syncing.value = true
  try {
    const res = await examApi.syncPeriodParticipants(period.value.id, selectedClassIds.value.map(Number))
    toast.success('Berhasil', res.data?.message || 'Peserta diterapkan.')
    await fetchPeriod()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menerapkan peserta.')
  } finally {
    syncing.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchPeriod(), fetchSubjects(), fetchClasses()])
})
</script>

<style scoped>
.period-detail-page { padding: 1.25rem 1rem 2.5rem; max-width: 960px; margin: 0 auto; }
.back-link { display: inline-block; margin-bottom: 0.75rem; color: #64748b; text-decoration: none; font-size: 0.9rem; }
.back-link:hover { color: #059669; }
.content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.1rem; margin-bottom: 0.85rem; }
.detail-header h1 { margin: 0.35rem 0 0.2rem; font-size: 1.4rem; }
.code-badge { font-size: 0.75rem; font-weight: 700; background: #ecfdf5; color: #047857; padding: 0.15rem 0.5rem; border-radius: 6px; }
.muted { color: #64748b; font-size: 0.88rem; margin: 0; }
.hint { color: #64748b; font-size: 0.85rem; margin: 0 0 0.75rem; }
.steps { list-style: none; display: flex; flex-wrap: wrap; gap: 0.4rem; padding: 0; margin: 0 0 1rem; }
.steps li { font-size: 0.75rem; font-weight: 600; color: #94a3b8; background: #f1f5f9; padding: 0.3rem 0.6rem; border-radius: 999px; }
.steps li.done { color: #047857; background: #d1fae5; }
.row { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: flex-end; }
.row label { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.82rem; font-weight: 600; }
.row input, .field { border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.4rem 0.55rem; font-weight: 400; }
.add-subjects { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.85rem; align-items: center; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { text-align: left; padding: 0.55rem 0.4rem; border-bottom: 1px solid #e2e8f0; font-size: 0.88rem; }
.code { font-size: 0.72rem; color: #64748b; }
.ok { color: #047857; font-weight: 600; }
.warn { color: #b45309; font-weight: 600; }
.col-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.class-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.75rem; }
.class-chip { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.82rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.35rem 0.55rem; cursor: pointer; }
.btn-primary, .btn-secondary { border: none; border-radius: 8px; padding: 0.4rem 0.75rem; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; font-size: 0.85rem; }
.btn-primary { background: #059669; color: #fff; }
.btn-secondary { background: #f1f5f9; color: #334155; }
.btn-sm { font-size: 0.8rem; }
.tip { background: #f0fdf4; border-color: #bbf7d0; }
.tip h2 { margin: 0 0 0.35rem; font-size: 1rem; }
.tip p { margin: 0; font-size: 0.88rem; color: #334155; }
h2 { margin: 0 0 0.35rem; font-size: 1.05rem; }
</style>
