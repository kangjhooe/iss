<template>
  <Layout>
    <div class="today-page">
      <div class="welcome-section">
        <div class="welcome-content">
          <h1>Jam Mengajar Hari Ini</h1>
          <span class="welcome-sep">·</span>
          <p class="welcome-inst">{{ dayLabel }}</p>
        </div>
        <div class="welcome-actions">
          <input v-model="selectedDate" type="date" class="date-input" @change="loadSessions" />
          <router-link
            v-if="canAccessModule('teaching_journal')"
            :to="recapAttendanceTo"
            class="profile-link"
          >Rekap Absensi</router-link>
          <router-link to="/teacher/dashboard" class="profile-link">Dashboard</router-link>
        </div>
      </div>

      <div v-if="loadError" class="error-banner" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" class="btn-primary btn-sm" @click="loadSessions">Coba lagi</button>
      </div>

      <div v-if="loading" class="empty-inline"><p>Memuat jadwal...</p></div>

      <template v-else>
        <div class="summary-row" v-if="payload">
          <span>{{ summary.total }} sesi</span>
          <span>{{ summary.attendance_done }} absen selesai</span>
          <span>{{ summary.journal_done }} jurnal terisi</span>
          <span>{{ summary.complete }} lengkap</span>
        </div>

        <div v-if="!sessions.length" class="empty-panel">
          <h3>Tidak ada jadwal mengajar</h3>
          <p>Tidak ada slot jadwal untuk tanggal ini pada semester aktif.</p>
        </div>

        <div v-else class="layout-grid">
          <aside class="session-list">
            <button
              v-for="s in sessions"
              :key="s.key"
              type="button"
              class="session-card"
              :class="{ active: activeKey === s.key, done: s.status?.complete }"
              @click="selectSession(s)"
            >
              <div class="session-time">
                <strong>{{ s.period_label }}</strong>
                <span v-if="s.start_time">{{ s.start_time }}{{ s.end_time ? '–' + s.end_time : '' }}</span>
              </div>
              <div class="session-body">
                <h4>{{ s.subject_name }}</h4>
                <p>{{ s.class_name }}<template v-if="s.room_name"> · {{ s.room_name }}</template></p>
              </div>
              <div class="session-flags">
                <span :class="['dot', s.status?.attendance_filled ? 'ok' : '']" title="Absensi"></span>
                <span :class="['dot', s.status?.journal_filled ? 'ok' : '']" title="Jurnal"></span>
                <span :class="['dot', s.status?.daily_grade_filled ? 'ok' : '']" title="Nilai harian"></span>
              </div>
            </button>
          </aside>

          <section v-if="activeSession" class="wizard-panel">
            <div class="wizard-header">
              <div>
                <h2>{{ activeSession.subject_name }} · {{ activeSession.class_name }}</h2>
                <p>{{ activeSession.period_label }} · {{ formatDate(selectedDate) }}</p>
              </div>
              <router-link
                class="link-mapel"
                :to="`/teacher/mapel?class_id=${activeSession.class_id}&subject_id=${activeSession.subject_id}`"
              >
                Buka Hub Mapel
              </router-link>
            </div>

            <div class="steps" role="tablist">
              <button
                v-for="step in steps"
                :key="step.id"
                type="button"
                role="tab"
                class="step-btn"
                :class="{ active: currentStep === step.id, done: stepDone(step.id) }"
                :disabled="step.id === 'grade' && !canAccessModule('grade_book')"
                @click="currentStep = step.id"
              >
                {{ step.label }}
              </button>
            </div>

            <!-- ABSENSI -->
            <div v-if="currentStep === 'attendance'" class="step-body">
              <div v-if="!canAccessModule('teaching_journal')" class="empty-inline">
                <p>Modul jurnal/absensi belum diaktifkan untuk akun ini.</p>
              </div>
              <template v-else>
                <div class="step-toolbar">
                  <button type="button" class="btn-ghost btn-sm" :disabled="attLoading" @click="prepareAttendance">
                    {{ attLoading ? 'Memuat...' : 'Muat ulang daftar' }}
                  </button>
                  <button type="button" class="btn-ghost btn-sm" :disabled="!attRows.length" @click="markAllHadir">
                    Semua Hadir
                  </button>
                  <router-link :to="recapAttendanceTo" class="btn-ghost btn-sm toolbar-link">Tanggal lain / rekap</router-link>
                </div>
                <p v-if="dayMismatch" class="warn-text">{{ dayMismatch }}</p>
                <p v-if="attError" class="error-text">{{ attError }}</p>

                <div v-if="attLoading && !attRows.length" class="empty-inline"><p>Menyiapkan absensi...</p></div>
                <div v-else-if="attRows.length" class="table-wrap">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th>Status</th>
                        <th>Ket.</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, idx) in attRows" :key="row.student_id">
                        <td>{{ idx + 1 }}</td>
                        <td>{{ row.student?.nis || '—' }} / {{ row.student?.name }}</td>
                        <td>
                          <select v-model="row.status" class="form-select">
                            <option v-for="(label, val) in statusOptions" :key="val" :value="val">{{ label }}</option>
                          </select>
                        </td>
                        <td>
                          <input v-model="row.notes" type="text" class="form-input" placeholder="Opsional" />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="form-actions">
                    <button type="button" class="btn-primary" :disabled="attSaving" @click="saveAttendance">
                      {{ attSaving ? 'Menyimpan...' : 'Simpan Absensi' }}
                    </button>
                    <button type="button" class="btn-secondary" @click="currentStep = 'journal'">Lanjut ke Jurnal →</button>
                  </div>
                </div>
                <div v-else class="empty-inline"><p>Belum ada daftar siswa. Klik muat ulang.</p></div>
              </template>
            </div>

            <!-- JURNAL -->
            <div v-else-if="currentStep === 'journal'" class="step-body">
              <div v-if="!canAccessModule('teaching_journal')" class="empty-inline">
                <p>Modul jurnal belum diaktifkan.</p>
              </div>
              <template v-else>
                <p v-if="journalHint" class="hint-text">{{ journalHint }}</p>
                <label class="field-label">Materi yang diajarkan *</label>
                <textarea v-model="journalForm.material_taught" rows="4" class="form-textarea" placeholder="Ringkas materi pertemuan hari ini" />
                <label class="field-label">Catatan absensi (opsional)</label>
                <input v-model="journalForm.attendance_notes" type="text" class="form-input" />
                <label class="field-label">Catatan lain (opsional)</label>
                <textarea v-model="journalForm.notes" rows="2" class="form-textarea" />
                <p v-if="journalError" class="error-text">{{ journalError }}</p>
                <div class="form-actions">
                  <button type="button" class="btn-primary" :disabled="journalSaving" @click="saveJournal">
                    {{ journalSaving ? 'Menyimpan...' : 'Simpan Jurnal' }}
                  </button>
                  <button
                    v-if="canAccessModule('grade_book')"
                    type="button"
                    class="btn-secondary"
                    @click="currentStep = 'grade'"
                  >
                    Lanjut ke Nilai Harian →
                  </button>
                </div>
              </template>
            </div>

            <!-- NILAI HARIAN -->
            <div v-else class="step-body">
              <div v-if="!canAccessModule('grade_book')" class="empty-inline">
                <p>Modul buku nilai belum diaktifkan.</p>
              </div>
              <template v-else>
                <div class="step-toolbar">
                  <label class="field-inline">
                    Kolom penilaian
                    <select v-model="gradeIndex" class="form-select narrow">
                      <option v-for="n in assessmentOptions" :key="n" :value="n">P{{ n }}</option>
                    </select>
                  </label>
                  <button type="button" class="btn-ghost btn-sm" :disabled="gradeLoading" @click="loadGrades">
                    {{ gradeLoading ? 'Memuat...' : 'Muat ulang' }}
                  </button>
                </div>
                <p class="hint-text">Isi nilai harian cepat untuk pertemuan ini. Nilai akhir dihitung otomatis saat disimpan.</p>
                <p v-if="gradeError" class="error-text">{{ gradeError }}</p>
                <div v-if="gradeLoading && !gradeRows.length" class="empty-inline"><p>Memuat nilai...</p></div>
                <div v-else-if="gradeRows.length" class="table-wrap">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Siswa</th>
                        <th>Nilai P{{ gradeIndex }}</th>
                        <th>Nilai Akhir</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, idx) in gradeRows" :key="row.student_id">
                        <td>{{ idx + 1 }}</td>
                        <td>{{ row.student?.nis || '—' }} / {{ row.student?.name }}</td>
                        <td>
                          <input
                            v-model="row._edit"
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            class="form-input score-input"
                            placeholder="—"
                          />
                        </td>
                        <td>{{ formatScore(row.nilai_akhir) }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="form-actions">
                    <button type="button" class="btn-primary" :disabled="gradeSaving" @click="saveGrades">
                      {{ gradeSaving ? 'Menyimpan...' : 'Simpan Nilai Harian' }}
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </section>

          <section v-else class="wizard-panel empty-panel">
            <h3>Pilih sesi</h3>
            <p>Pilih salah satu jam mengajar di sebelah kiri untuk mengisi absensi, jurnal, dan nilai harian.</p>
          </section>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { teacherApi } from '@/api/teacher'
import { studentAttendanceApi } from '@/api/attendance'
import { teachingJournalApi } from '@/api/teachingJournal'
import { gradeBookApi } from '@/api/gradeBook'

const authStore = useAuthStore()
const route = useRoute()
const toast = useToast()

const canAccessModule = (key) => (authStore.user?.permissions || []).includes(key)

const todayStr = () => {
  const d = new Date()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${m}-${day}`
}

const selectedDate = ref(typeof route.query.date === 'string' ? route.query.date : todayStr())
const loading = ref(false)
const loadError = ref('')
const payload = ref(null)
const activeKey = ref('')
const currentStep = ref('attendance')

const sessions = computed(() => payload.value?.sessions || [])
const summary = computed(() => payload.value?.summary || { total: 0, attendance_done: 0, journal_done: 0, complete: 0 })
const dayLabel = computed(() => {
  const name = payload.value?.day_name || ''
  return name ? `${name}, ${formatDate(selectedDate.value)}` : formatDate(selectedDate.value)
})
const activeSession = computed(() => sessions.value.find((s) => s.key === activeKey.value) || null)

const recapAttendanceTo = computed(() => {
  const s = activeSession.value
  const params = new URLSearchParams()
  params.set('tab', 'rekap')
  if (s?.class_id) params.set('class_id', String(s.class_id))
  if (s?.subject_id) params.set('subject_id', String(s.subject_id))
  return `/attendance/student?${params.toString()}`
})

const steps = [
  { id: 'attendance', label: '1. Absensi' },
  { id: 'journal', label: '2. Jurnal' },
  { id: 'grade', label: '3. Nilai Harian' },
]

const statusOptions = {
  hadir: 'Hadir',
  alpha: 'Alpha',
  izin: 'Izin',
  sakit: 'Sakit',
  dinas_luar: 'Dinas Luar',
}

const attRows = ref([])
const attLoading = ref(false)
const attSaving = ref(false)
const attError = ref('')
const dayMismatch = ref('')
const journalIds = ref([])

const journalForm = ref({ material_taught: '', attendance_notes: '', notes: '' })
const journalSaving = ref(false)
const journalError = ref('')
const journalHint = ref('')

const gradeRows = ref([])
const gradeLoading = ref(false)
const gradeSaving = ref(false)
const gradeError = ref('')
const gradeIndex = ref(1)
const assessmentCount = ref(1)
const assessmentOptions = computed(() => {
  const n = Math.max(1, assessmentCount.value)
  return Array.from({ length: n }, (_, i) => i + 1)
})

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  } catch {
    return iso
  }
}

function formatScore(v) {
  if (v === null || v === undefined || v === '') return '—'
  const n = Number(v)
  if (!Number.isFinite(n)) return '—'
  return Number.isInteger(n) ? String(n) : n.toFixed(1)
}

function stepDone(id) {
  const s = activeSession.value?.status
  if (!s) return false
  if (id === 'attendance') return !!s.attendance_filled
  if (id === 'journal') return !!s.journal_filled
  if (id === 'grade') return !!s.daily_grade_filled
  return false
}

async function loadSessions() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await teacherApi.getTodaySessions({ date: selectedDate.value })
    payload.value = res.data?.data || null
    if (!sessions.value.find((s) => s.key === activeKey.value)) {
      activeKey.value = sessions.value[0]?.key || ''
    }
  } catch (e) {
    payload.value = null
    loadError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat jadwal hari ini.'
  } finally {
    loading.value = false
  }
}

async function selectSession(s) {
  activeKey.value = s.key
  currentStep.value = 'attendance'
  attRows.value = []
  journalForm.value = { material_taught: '', attendance_notes: '', notes: '' }
  gradeRows.value = []
  if (canAccessModule('teaching_journal')) {
    await prepareAttendance()
  }
}

async function prepareAttendance() {
  if (!activeSession.value) return
  attLoading.value = true
  attError.value = ''
  dayMismatch.value = ''
  try {
    const res = await studentAttendanceApi.prepareFromSchedule({
      lesson_schedule_ids: activeSession.value.lesson_schedule_ids,
      date: selectedDate.value,
    })
    const data = res.data?.data || res.data || {}
    dayMismatch.value = data.day_mismatch_message || ''
    const rows = Array.isArray(data.attendances) ? data.attendances : []
    attRows.value = rows.map((r) => ({
      student_id: r.student_id || r.student?.id,
      student: r.student || { id: r.student_id, name: r.name, nis: r.nis },
      status: r.status || 'hadir',
      notes: r.notes || '',
    }))
    const primary = data.teaching_journal || null
    journalIds.value = primary?.id ? [primary.id] : []
    if (primary) {
      journalForm.value = {
        material_taught: primary.material_taught || '',
        attendance_notes: primary.attendance_notes || '',
        notes: primary.notes || '',
      }
      journalHint.value = 'Jurnal terhubung ke slot ini. Lengkapi materi lalu simpan.'
    } else {
      journalHint.value = 'Jurnal akan dibuat otomatis saat absensi disiapkan.'
    }
  } catch (e) {
    attError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyiapkan absensi.'
    attRows.value = []
  } finally {
    attLoading.value = false
  }
}

function markAllHadir() {
  attRows.value.forEach((r) => { r.status = 'hadir' })
}

async function saveAttendance() {
  if (!activeSession.value) return
  attSaving.value = true
  attError.value = ''
  try {
    await studentAttendanceApi.saveFromSchedule(
      activeSession.value.lesson_schedule_ids,
      selectedDate.value,
      attRows.value.map((r) => ({
        student_id: r.student_id,
        status: r.status,
        notes: r.notes || null,
      }))
    )
    toast.success('Absensi disimpan')
    await loadSessions()
    await prepareAttendance()
  } catch (e) {
    attError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan absensi.'
  } finally {
    attSaving.value = false
  }
}

async function saveJournal() {
  journalError.value = ''
  const material = (journalForm.value.material_taught || '').trim()
  if (!material) {
    journalError.value = 'Materi yang diajarkan wajib diisi.'
    return
  }
  if (!activeSession.value) return

  journalSaving.value = true
  try {
    // Pastikan jurnal ada via prepare
    if (!journalIds.value.length) {
      await prepareAttendance()
    }
    const ids = [...journalIds.value]
    if (!ids.length) {
      throw new Error('Jurnal belum tersedia. Simpan absensi terlebih dahulu atau muat ulang.')
    }
    for (const id of ids) {
      await teachingJournalApi.update(id, {
        material_taught: material,
        attendance_notes: journalForm.value.attendance_notes || null,
        notes: journalForm.value.notes || null,
      })
    }
    toast.success('Jurnal disimpan')
    await loadSessions()
  } catch (e) {
    journalError.value = e.response?.data?.message || e.formattedMessage || e.message || 'Gagal menyimpan jurnal.'
  } finally {
    journalSaving.value = false
  }
}

async function loadGrades() {
  if (!activeSession.value || !canAccessModule('grade_book')) return
  gradeLoading.value = true
  gradeError.value = ''
  try {
    const res = await gradeBookApi.getByClassSubjectSemester({
      semester_id: activeSession.value.semester_id,
      class_id: activeSession.value.class_id,
      subject_id: activeSession.value.subject_id,
      page: 1,
      per_page: 100,
    })
    const meta = res.data?.meta || {}
    assessmentCount.value = Math.max(1, Number(meta.assessment_count) || 1)
    if (gradeIndex.value > assessmentCount.value) {
      gradeIndex.value = assessmentCount.value
    }
    const idx = String(gradeIndex.value)
    gradeRows.value = (Array.isArray(res.data?.data) ? res.data.data : []).map((r) => {
      const penilaian = r.penilaian || {}
      const current = penilaian[idx] ?? penilaian[gradeIndex.value] ?? ''
      return {
        ...r,
        _edit: current === null || current === undefined ? '' : String(current),
      }
    })
  } catch (e) {
    gradeRows.value = []
    gradeError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat nilai.'
  } finally {
    gradeLoading.value = false
  }
}

async function saveGrades() {
  if (!activeSession.value) return
  gradeSaving.value = true
  gradeError.value = ''
  try {
    const idx = String(gradeIndex.value)
    const grades = gradeRows.value
      .filter((r) => r._edit !== '' && r._edit !== null && r._edit !== undefined)
      .map((r) => ({
        student_id: r.student_id,
        penilaian: { [idx]: Number(r._edit) },
      }))
    if (!grades.length) {
      gradeError.value = 'Isi minimal satu nilai harian.'
      return
    }
    await gradeBookApi.bulkSave({
      semester_id: activeSession.value.semester_id,
      class_id: activeSession.value.class_id,
      subject_id: activeSession.value.subject_id,
      grades,
    })
    toast.success('Nilai harian disimpan')
    await loadGrades()
    await loadSessions()
  } catch (e) {
    gradeError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan nilai.'
  } finally {
    gradeSaving.value = false
  }
}

watch(currentStep, async (step) => {
  if (step === 'grade' && activeSession.value && !gradeRows.value.length) {
    await loadGrades()
  }
  if (step === 'journal' && activeSession.value && !journalIds.value.length && canAccessModule('teaching_journal')) {
    await prepareAttendance()
  }
})

watch(gradeIndex, () => {
  if (gradeRows.value.length) {
    const idx = String(gradeIndex.value)
    gradeRows.value.forEach((r) => {
      const penilaian = r.penilaian || {}
      const current = penilaian[idx] ?? ''
      r._edit = current === null || current === undefined ? '' : String(current)
    })
  }
})

watch(
  () => [selectedDate.value, authStore.activeInstitution?.id],
  () => { loadSessions() },
  { immediate: true }
)

watch(activeKey, async (key) => {
  if (!key) return
  if (canAccessModule('teaching_journal') && !attRows.value.length) {
    await prepareAttendance()
  }
})
</script>

<style scoped>
.today-page { width: 100%; padding: 0 0 12px; }
.welcome-section {
  background: linear-gradient(120deg, #0f766e 0%, #0d9488 55%, #0891b2 100%);
  border-radius: 14px; padding: 12px 20px; margin-bottom: 16px; color: #fff;
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.welcome-content { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; }
.welcome-content h1 { margin: 0; font-size: 17px; font-weight: 700; }
.welcome-sep { opacity: .7; }
.welcome-inst { margin: 0; font-size: 13px; font-weight: 500; }
.welcome-actions { display: flex; gap: 8px; align-items: center; }
.date-input {
  border: 1px solid rgba(255,255,255,.35); background: rgba(255,255,255,.15);
  color: #fff; border-radius: 8px; padding: 6px 10px; font-size: 13px;
}
.profile-link {
  color: #fff; text-decoration: none; font-size: 13px; font-weight: 600;
  padding: 7px 12px; border-radius: 8px; background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.28);
}
.summary-row {
  display: flex; flex-wrap: wrap; gap: 10px 16px; margin-bottom: 14px;
  font-size: 13px; color: #475569;
}
.summary-row span { background: #f1f5f9; padding: 6px 10px; border-radius: 999px; }
.layout-grid {
  display: grid; grid-template-columns: minmax(240px, 300px) 1fr; gap: 14px; align-items: start;
}
@media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }
.session-list { display: flex; flex-direction: column; gap: 8px; }
.session-card {
  text-align: left; border: 1px solid #e2e8f0; background: #fff; border-radius: 12px;
  padding: 12px; cursor: pointer; display: grid; grid-template-columns: auto 1fr auto; gap: 10px;
  transition: border-color .15s, box-shadow .15s;
}
.session-card:hover { border-color: #99f6e4; }
.session-card.active { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,.15); }
.session-card.done { background: #f0fdf4; }
.session-time { display: flex; flex-direction: column; gap: 2px; font-size: 12px; color: #64748b; min-width: 72px; }
.session-time strong { color: #0f172a; font-size: 13px; }
.session-body h4 { margin: 0 0 2px; font-size: 14px; color: #0f172a; }
.session-body p { margin: 0; font-size: 12px; color: #64748b; }
.session-flags { display: flex; flex-direction: column; gap: 4px; justify-content: center; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; }
.dot.ok { background: #10b981; }
.wizard-panel, .empty-panel {
  background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.empty-panel h3, .empty-inline p { margin: 0 0 6px; color: #334155; }
.empty-panel p { margin: 0; color: #64748b; font-size: 14px; }
.wizard-header { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 14px; }
.wizard-header h2 { margin: 0 0 4px; font-size: 16px; }
.wizard-header p { margin: 0; font-size: 13px; color: #64748b; }
.link-mapel { font-size: 13px; font-weight: 600; color: #0d9488; text-decoration: none; }
.steps { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
.step-btn {
  border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 999px; padding: 7px 12px;
  font-size: 13px; font-weight: 600; color: #475569; cursor: pointer;
}
.step-btn.active { background: #0d9488; border-color: #0d9488; color: #fff; }
.step-btn.done:not(.active) { border-color: #86efac; color: #166534; background: #f0fdf4; }
.step-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; }
.field-label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin: 10px 0 6px; }
.field-inline { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #475569; }
.form-input, .form-select, .form-textarea {
  width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px;
  background: #fff; color: #0f172a; box-sizing: border-box;
}
.form-select.narrow { width: auto; min-width: 72px; }
.score-input { max-width: 100px; }
.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th, .data-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
.data-table th { font-size: 12px; color: #64748b; font-weight: 600; }
.form-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
.btn-primary, .btn-secondary, .btn-ghost, .btn-sm {
  border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent;
}
.btn-primary { background: #0d9488; color: #fff; }
.btn-secondary { background: #fff; color: #0f766e; border-color: #99f6e4; }
.btn-ghost { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
.btn-sm { padding: 6px 10px; font-size: 12px; }
.toolbar-link { display: inline-flex; align-items: center; text-decoration: none; }
.btn-primary:disabled, .btn-ghost:disabled { opacity: .6; cursor: not-allowed; }
.hint-text { font-size: 13px; color: #64748b; margin: 0 0 10px; }
.warn-text { font-size: 13px; color: #b45309; background: #fffbeb; padding: 8px 10px; border-radius: 8px; }
.error-text, .error-banner { color: #b91c1c; font-size: 13px; }
.error-banner { background: #fef2f2; border-radius: 10px; padding: 12px; margin-bottom: 12px; display: flex; gap: 10px; align-items: center; justify-content: space-between; }
.empty-inline { padding: 18px 0; color: #64748b; }
</style>
