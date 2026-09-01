<template>
    <div class="today-page">
      <div class="welcome-section">
        <div class="welcome-content">
          <h1>Jam Mengajar Hari Ini</h1>
          <span class="welcome-sep">·</span>
          <p class="welcome-inst">{{ dayLabel }}</p>
        </div>
        <div class="welcome-actions">
          <input v-model="selectedDate" type="date" class="date-input" @change="loadSessions" />
          <button
            type="button"
            class="profile-link profile-btn"
            :disabled="printingAll || !sessions.length"
            @click="printPdf()"
          >
            {{ printingAll ? 'Menyiapkan...' : 'Cetak semua' }}
          </button>
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
          <h3>Tidak ada penugasan mengajar</h3>
          <p>Belum ada jadwal pelajaran untuk Anda pada semester aktif.</p>
        </div>

        <div v-else class="layout-grid">
          <aside class="session-list">
            <div class="session-filter">
              <button
                type="button"
                class="filter-btn"
                :class="{ active: sessionFilter === 'all' }"
                @click="sessionFilter = 'all'"
              >
                Semua ({{ sessions.length }})
              </button>
              <button
                type="button"
                class="filter-btn"
                :class="{ active: sessionFilter === 'today' }"
                @click="sessionFilter = 'today'"
              >
                Jadwal hari ini ({{ onScheduleCount }})
              </button>
            </div>
            <button
              v-for="s in filteredSessions"
              :key="s.key"
              type="button"
              class="session-card"
              :class="{ active: activeKey === s.key, done: s.status?.complete }"
              @click="selectSession(s)"
            >
              <div class="session-time">
                <strong>{{ s.period_label }}</strong>
                <span v-if="s.start_time">{{ s.start_time }}{{ s.end_time ? '–' + s.end_time : '' }}</span>
                <span class="schedule-day">{{ s.scheduled_day_name || '—' }}</span>
              </div>
              <div class="session-body">
                <h4>{{ s.subject_name }}</h4>
                <p>{{ s.class_name }}<template v-if="s.room_name"> · {{ s.room_name }}</template></p>
                <span v-if="s.is_on_schedule" class="session-badge on-schedule">Jadwal hari ini</span>
                <span v-else class="session-badge catch-up">Jadwal {{ s.scheduled_day_name }}</span>
                <span v-if="s.penilaian_index" class="session-badge grade-col">P{{ s.penilaian_index }}</span>
                <span v-else-if="s.meeting_number" class="session-badge grade-col">P{{ s.meeting_number }}</span>
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
                <p v-if="meetingLabel" class="meeting-label">{{ meetingLabel }}</p>
              </div>
              <div class="wizard-header-actions">
                <button
                  type="button"
                  class="btn-secondary btn-sm"
                  :disabled="printingSession"
                  @click="printPdf(activeSession.key)"
                >
                  {{ printingSession ? 'Menyiapkan...' : 'Cetak PDF' }}
                </button>
                <router-link
                  class="link-mapel"
                  :to="`/teacher/mapel?class_id=${activeSession.class_id}&subject_id=${activeSession.subject_id}`"
                >
                  Buka Hub Mapel
                </router-link>
              </div>
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
                <p class="hint-text meeting-guide">{{ meetingGuideText }}</p>
                <div class="step-toolbar">
                  <button type="button" class="btn-ghost btn-sm" :disabled="attLoading" @click="prepareAttendance">
                    {{ attLoading ? 'Memuat...' : 'Muat ulang daftar' }}
                  </button>
                  <button type="button" class="btn-ghost btn-sm" :disabled="!attRows.length" @click="markAllHadir">
                    Semua Hadir
                  </button>
                  <router-link :to="recapAttendanceTo" class="btn-ghost btn-sm toolbar-link">Tanggal lain / rekap</router-link>
                </div>
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
                <p class="hint-text meeting-guide">{{ meetingGuideText }}</p>
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
                  <button
                    type="button"
                    class="btn-secondary"
                    :disabled="printingSession"
                    @click="printPdf(activeSession.key)"
                  >
                    {{ printingSession ? 'Menyiapkan...' : 'Cetak PDF' }}
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
                <p class="hint-text">
                  Setiap tanggal mengajar = satu pertemuan. Kolom nilai otomatis mengikuti urutan pertemuan
                  (pertemuan ke-1 → P1, ke-2 → P2, dan seterusnya). Ubah manual jika perlu.
                </p>
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
                    <button
                      type="button"
                      class="btn-secondary"
                      :disabled="printingSession"
                      @click="printPdf(activeSession.key)"
                    >
                      {{ printingSession ? 'Menyiapkan...' : 'Cetak PDF Pertemuan' }}
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
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
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
const sessionFilter = ref('all')
const onScheduleCount = computed(() => sessions.value.filter((s) => s.is_on_schedule).length)
const filteredSessions = computed(() => {
  if (sessionFilter.value === 'today') {
    return sessions.value.filter((s) => s.is_on_schedule)
  }
  return sessions.value
})
const summary = computed(() => payload.value?.summary || { total: 0, attendance_done: 0, journal_done: 0, complete: 0 })
const dayLabel = computed(() => {
  const name = payload.value?.day_name || ''
  return name ? `${name}, ${formatDate(selectedDate.value)}` : formatDate(selectedDate.value)
})
const activeSession = computed(() => sessions.value.find((s) => s.key === activeKey.value) || null)

const meetingLabel = computed(() => {
  const s = activeSession.value
  if (!s) return ''
  const meetingNo = Number(s.meeting_number) || 1
  const pCol = Number(gradeIndex.value) || Number(s.penilaian_index) || meetingNo
  return `Pertemuan ke-${meetingNo} · Nilai P${pCol}`
})

const meetingGuideText = computed(() => {
  const s = activeSession.value
  const meetingNo = Number(s?.meeting_number) || 1
  if (meetingNo <= 1) {
    return 'Pertemuan pertama: isi absensi, jurnal, lalu nilai P1 untuk tanggal ini.'
  }
  return `Pertemuan ke-${meetingNo}: pilih tanggal mengajar yang benar di atas, isi absensi & jurnal baru, lalu nilai P${meetingNo}. Setiap tanggal = satu pertemuan terpisah.`
})

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
const printingSession = ref(false)
const printingAll = ref(false)
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

function applySessionGradeContext(session) {
  if (!session) return
  const idx = Number(session.penilaian_index ?? session.suggested_penilaian_index ?? session.meeting_number ?? 1)
  if (idx >= 1) gradeIndex.value = idx
  assessmentCount.value = Math.max(
    1,
    Number(session.assessment_count) || 1,
    idx,
    Number(session.meeting_number) || 1,
  )
}

async function loadSessions() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await teacherApi.getTodaySessions({ date: selectedDate.value })
    payload.value = res.data?.data || null
  } catch (e) {
    payload.value = null
    loadError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat jadwal hari ini.'
  } finally {
    loading.value = false
    pickActiveSession()
    if (activeSession.value) applySessionGradeContext(activeSession.value)
  }
}

function pickActiveSession() {
  const list = filteredSessions.value.length ? filteredSessions.value : sessions.value
  const classId = route.query.class_id ? String(route.query.class_id) : ''
  const subjectId = route.query.subject_id ? String(route.query.subject_id) : ''
  if (classId && subjectId) {
    const match = list.find(
      (s) => String(s.class_id) === classId && String(s.subject_id) === subjectId
    )
    if (match) {
      activeKey.value = match.key
      return
    }
  }
  if (!list.find((s) => s.key === activeKey.value)) {
    activeKey.value = list[0]?.key || ''
  }
}

async function selectSession(s) {
  activeKey.value = s.key
  currentStep.value = 'attendance'
  attRows.value = []
  journalForm.value = { material_taught: '', attendance_notes: '', notes: '' }
  gradeRows.value = []
  applySessionGradeContext(s)
  if (canAccessModule('teaching_journal')) {
    await prepareAttendance()
  }
}

async function prepareAttendance() {
  if (!activeSession.value) return
  attLoading.value = true
  attError.value = ''
  try {
    const res = await studentAttendanceApi.prepareFromSchedule({
      lesson_schedule_ids: activeSession.value.lesson_schedule_ids,
      date: selectedDate.value,
    })
    const data = res.data?.data || res.data || {}
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
      if (primary.penilaian_index && Number(primary.penilaian_index) >= 1) {
        gradeIndex.value = Number(primary.penilaian_index)
      } else if (activeSession.value) {
        applySessionGradeContext(activeSession.value)
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
        penilaian_index: Number(gradeIndex.value) >= 1 ? Number(gradeIndex.value) : null,
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
    const sessionCount = Math.max(
      1,
      Number(activeSession.value?.assessment_count) || 0,
      Number(activeSession.value?.meeting_number) || 0,
      Number(meta.assessment_count) || 1,
    )
    assessmentCount.value = sessionCount
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
      assessment_count: Math.max(assessmentCount.value, gradeIndex.value),
      grades,
    })
    if (!journalIds.value.length) {
      await prepareAttendance()
    }
    const penilaianIdx = Number(gradeIndex.value)
    for (const id of journalIds.value) {
      await teachingJournalApi.update(id, { penilaian_index: penilaianIdx })
    }
    toast.success('Nilai harian disimpan')
    await loadGrades()
    await loadSessions()
  } catch (e) {
    gradeError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan nilai.'
  } finally {
    gradeSaving.value = false
  }
}

function openPdfPreview(blob, title) {
  const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
  const win = window.open('', '_blank')
  if (!win) {
    toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
    URL.revokeObjectURL(url)
    return false
  }
  const safeTitle = String(title || 'Preview PDF').replace(/</g, '')
  win.document.write(`<!DOCTYPE html><html><head><title>${safeTitle}</title>
    <style>
      * { box-sizing: border-box; }
      body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
      .toolbar {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 10px 14px; background: #0f172a; color: #f8fafc;
        border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
      }
      .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
      .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
      .actions { display: flex; gap: 8px; flex-shrink: 0; }
      .actions button {
        border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
        cursor: pointer; font-size: 13px;
      }
      .btn-print { background: #059669; color: #fff; }
      .btn-close { background: #334155; color: #e2e8f0; }
      iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
    </style></head><body>
    <div class="toolbar">
      <h1>${safeTitle}<span class="hint">Preview cetak</span></h1>
      <div class="actions">
        <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
        <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
      </div>
    </div>
    <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
  </body></html>`)
  win.document.close()
  setTimeout(() => URL.revokeObjectURL(url), 120_000)
  return true
}

async function printPdf(sessionKey = null) {
  const printing = sessionKey ? printingSession : printingAll
  printing.value = true
  try {
    const params = { date: selectedDate.value }
    if (sessionKey) {
      params.session_key = sessionKey
      if (canAccessModule('grade_book') && gradeIndex.value >= 1) {
        params.penilaian_index = gradeIndex.value
      }
    }
    const res = await teacherApi.exportTodaySessionsPdf(params)
    const contentType = res.headers?.['content-type'] || ''
    if (res.status !== 200 || contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mencetak lembar jurnal mengajar.')
    }
    const blob = res.data instanceof Blob
      ? res.data
      : new Blob([res.data], { type: 'application/pdf' })
    const title = sessionKey
      ? `Lembar Jurnal — ${activeSession.value?.subject_name || ''} ${activeSession.value?.class_name || ''}`.trim()
      : `Lembar Jurnal — ${formatDate(selectedDate.value)}`
    if (openPdfPreview(blob, title)) {
      toast.success('Preview PDF dibuka')
    }
  } catch (e) {
    let message = e.response?.data?.message || e.formattedMessage || e.message || 'PDF tidak dapat dibuka.'
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const json = JSON.parse(await data.text())
        message = json.message || message
      } catch {
        // biarkan pesan sebelumnya
      }
    }
    toast.error('Gagal mencetak PDF', message)
  } finally {
    printing.value = false
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

watch(gradeIndex, async () => {
  if (gradeRows.value.length) {
    const idx = String(gradeIndex.value)
    gradeRows.value.forEach((r) => {
      const penilaian = r.penilaian || {}
      const current = penilaian[idx] ?? ''
      r._edit = current === null || current === undefined ? '' : String(current)
    })
  } else if (currentStep.value === 'grade' && activeSession.value) {
    await loadGrades()
  }
})

watch(sessionFilter, () => { pickActiveSession() })

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
@media (max-width: 1440px) {
  .layout-grid { grid-template-columns: minmax(200px, 240px) minmax(0, 1fr); }
  .welcome-section { padding: 10px 16px; }
  .wizard-panel, .empty-panel { padding: 14px 16px; }
}
@media (max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }
.session-list { display: flex; flex-direction: column; gap: 8px; }
.session-filter { display: flex; gap: 6px; margin-bottom: 4px; flex-wrap: wrap; }
.filter-btn {
  border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 999px; padding: 5px 10px;
  font-size: 11px; font-weight: 600; color: #64748b; cursor: pointer;
}
.filter-btn.active { background: #0d9488; border-color: #0d9488; color: #fff; }
.schedule-day { font-size: 11px; color: #0d9488; font-weight: 600; }
.session-badge {
  display: inline-block; margin-top: 4px; font-size: 10px; font-weight: 600;
  padding: 2px 7px; border-radius: 999px;
}
.session-badge.on-schedule { background: #d1fae5; color: #065f46; }
.session-badge.catch-up { background: #f1f5f9; color: #64748b; }
.session-badge.grade-col { background: #ede9fe; color: #5b21b6; }
.meeting-label { margin: 4px 0 0; font-size: 13px; font-weight: 600; color: #5b21b6; }
.meeting-guide { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 8px; padding: 8px 10px; }
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
.wizard-header-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.link-mapel { font-size: 13px; font-weight: 600; color: #0d9488; text-decoration: none; }
.profile-btn {
  font-family: inherit; cursor: pointer;
}
.profile-btn:disabled { opacity: .6; cursor: not-allowed; }
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
