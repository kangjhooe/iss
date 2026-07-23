<template>
  <Layout>
    <div class="attendance-student-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">Absensi Siswa</h1>
            <p class="page-subtitle">Isi kehadiran dari jadwal mengajar (jurnal dibuat otomatis) dan lihat rekap laporan</p>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-secondary btn-compact" :disabled="exporting || loadingRekap" @click="exportRekap('csv')">
              {{ exporting === 'csv' ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-primary btn-compact" :disabled="exporting || loadingRekap" @click="exportRekap('pdf')">
              {{ exporting === 'pdf' ? 'Mengekspor...' : 'Cetak Rekap PDF' }}
            </button>
          </div>
        </div>
      </div>

      <div class="section-tabs" role="tablist">
        <button type="button" role="tab" :class="['sec-btn', { active: activeTab === 'isi' }]" @click="activeTab = 'isi'">Isi Absensi</button>
        <button type="button" role="tab" :class="['sec-btn', { active: activeTab === 'rekap' }]" @click="switchToRekap">Rekap & Laporan</button>
      </div>

      <template v-if="activeTab === 'isi'">
        <div class="fill-panel">
          <div class="fill-filters">
            <select v-model="fillForm.semester_id" class="filter-select" @change="onSemesterChange">
              <option value="">Pilih Semester</option>
              <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
            </select>

            <select
              v-model="fillForm.pair_key"
              class="filter-select filter-wide"
              :disabled="!fillForm.semester_id || loadLoading"
              @change="onPairChange"
            >
              <option value="">Pilih Kelas + Mapel</option>
              <option v-for="p in teachingPairs" :key="`${p.class_id}-${p.subject_id}`" :value="`${p.class_id}-${p.subject_id}`">
                {{ p.class_name }} · {{ p.subject_name }}
              </option>
            </select>

            <select
              v-model="fillForm.session_key"
              class="filter-select filter-wide"
              :disabled="!fillForm.pair_key || !sessionOptions.length"
              @change="resetPreparedSession"
            >
              <option value="">Pilih Slot Jadwal</option>
              <option v-for="opt in sessionOptions" :key="opt.key" :value="opt.key">
                {{ opt.label }}
              </option>
            </select>

            <input
              v-model="fillForm.date"
              type="date"
              class="filter-select"
              :disabled="!fillForm.session_key"
              @change="onDateChange"
            />

            <button
              type="button"
              class="btn-primary btn-compact"
              :disabled="!canPrepare || attendanceLoading"
              @click="prepareSession"
            >
              {{ attendanceLoading ? 'Memuat...' : 'Muat Daftar Siswa' }}
            </button>
          </div>

          <p v-if="loadError" class="form-error">{{ loadError }}</p>
          <p v-else-if="fillForm.semester_id && !loadLoading && !teachingPairs.length" class="hint-text">
            Belum ada jadwal mengajar di semester ini. Pastikan jadwal pelajaran sudah diisi untuk akun guru Anda.
          </p>
          <p v-else-if="hasConsecutiveBlocks" class="hint-text">
            Slot berurutan tersedia sebagai isi sekali (mis. Jam ke-1–3). Opsi per jam tetap ada jika ingin diisi terpisah.
          </p>

          <div v-if="dayMismatchMessage" class="day-warning" role="alert">
            {{ dayMismatchMessage }}
          </div>

          <div v-if="preparedJournal" class="session-meta">
            <span>{{ formatDate(preparedJournal.journal_date) }}</span>
            <span>{{ preparedSchedule?.class_name || preparedJournal.school_class?.name }}</span>
            <span>{{ preparedSchedule?.subject_name || preparedJournal.subject?.name }}</span>
            <span>{{ preparedSchedule?.period_label || `Jam ke-${preparedJournal.period}` }}</span>
            <span v-if="preparedSchedule?.is_block" class="meta-block">Isi sekali untuk {{ (preparedSchedule.periods || []).length }} jam</span>
          </div>

          <div v-if="attendanceLoading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="6" :columns="4" />
          </div>

          <div v-else-if="attendanceRows.length" class="attendance-form-wrap">
            <div v-if="offlineIndicator" class="offline-indicator">
              <span>Mode Offline - Data dari penyimpanan lokal</span>
            </div>
            <form @submit.prevent="submitAttendance" class="attendance-form">
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>NIS / Nama</th>
                      <th>Status Kehadiran</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in attendanceRows" :key="row.student_id">
                      <td>{{ idx + 1 }}</td>
                      <td>{{ row.student?.nis || '-' }} / {{ row.student?.name }}</td>
                      <td>
                        <select v-model="row.status" class="form-select status-select">
                          <option v-for="(label, val) in studentStatusOptions" :key="val" :value="val">{{ label }}</option>
                        </select>
                      </td>
                      <td>
                        <input v-model="row.notes" type="text" class="form-input notes-input" placeholder="Opsional" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="attendanceFormError" class="form-error">{{ attendanceFormError }}</p>
              <div class="form-actions">
                <button type="submit" :disabled="attendanceSaving" class="btn-primary">
                  {{ attendanceSaving ? 'Menyimpan...' : 'Simpan Absensi' }}
                </button>
              </div>
            </form>
          </div>

          <div v-else-if="preparedJournal" class="empty-state">
            <h3 class="empty-title">Tidak ada siswa aktif</h3>
            <p class="empty-desc">Kelas pada slot jadwal ini belum memiliki siswa aktif.</p>
          </div>
        </div>
      </template>

      <template v-else>
        <div class="toolbar">
          <div class="filters filters-inline">
            <select v-model="filters.semester_id" @change="onRekapSemesterChange" class="filter-select">
              <option value="">Semua Semester</option>
              <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
            </select>
            <select v-model="filters.class_id" @change="onRekapClassChange" class="filter-select">
              <option value="">Semua Kelas</option>
              <option v-for="c in rekapClasses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
            <select v-model="filters.subject_id" @change="onRekapFilterChange" class="filter-select">
              <option value="">Semua Mapel</option>
              <option v-for="s in rekapSubjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
            </select>
            <input v-model="filters.date_from" type="date" class="filter-select" title="Dari tanggal" @change="onRekapFilterChange" />
            <input v-model="filters.date_to" type="date" class="filter-select" title="Sampai tanggal" @change="onRekapFilterChange" />
          </div>
        </div>

        <div v-if="rekapLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="10" />
        </div>
        <div v-else>
          <div v-if="rekapMeta" class="rekap-stats">
            <span v-if="rekapMeta.class_name">Kelas: <strong>{{ rekapMeta.class_name }}</strong></span>
            <span v-if="rekapMeta.subject_name">Mapel: <strong>{{ rekapMeta.subject_name }}</strong></span>
            <span>Pertemuan: <strong>{{ rekapMeta.meeting_days ?? 0 }}</strong> hari</span>
            <span>JP: <strong>{{ rekapMeta.jp_count ?? rekapMeta.session_count ?? 0 }}</strong></span>
            <span>Siswa: <strong>{{ rekapMeta.student_count ?? rekapRows.length }}</strong></span>
            <span>H: <strong>{{ rekapTotals.hadir ?? 0 }}</strong></span>
            <span>A: <strong>{{ rekapTotals.alpha ?? 0 }}</strong></span>
            <span>I: <strong>{{ rekapTotals.izin ?? 0 }}</strong></span>
            <span>S: <strong>{{ rekapTotals.sakit ?? 0 }}</strong></span>
            <span>% Hadir (JP): <strong>{{ rekapTotals.persentase_hadir_jp ?? 0 }}%</strong></span>
          </div>
          <div v-if="rekapRows.length === 0" class="empty-state">
            <h3 class="empty-title">Belum ada data rekap</h3>
            <p class="empty-desc">Pilih semester, kelas, dan mapel (mis. 8A · Penjaskes), lalu pastikan absensi sudah diisi dari tab Isi Absensi.</p>
          </div>
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>NIS</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th title="Hadir">H</th>
                  <th title="Alpha">A</th>
                  <th title="Izin">I</th>
                  <th title="Sakit">S</th>
                  <th title="Dinas Luar">DL</th>
                  <th>Tercatat</th>
                  <th title="Jam pelajaran">JP</th>
                  <th title="Jumlah hari pertemuan">Pertemuan</th>
                  <th title="Hadir ÷ tercatat">% (tercatat)</th>
                  <th title="Hadir ÷ JP">% (JP)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in rekapRows" :key="row.student_id">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ row.nis || '-' }}</td>
                  <td>{{ row.name }}</td>
                  <td>{{ row.class_name || '-' }}</td>
                  <td>{{ row.counts?.hadir ?? 0 }}</td>
                  <td>{{ row.counts?.alpha ?? 0 }}</td>
                  <td>{{ row.counts?.izin ?? 0 }}</td>
                  <td>{{ row.counts?.sakit ?? 0 }}</td>
                  <td>{{ row.counts?.dinas_luar ?? 0 }}</td>
                  <td>{{ row.tercatat ?? 0 }}</td>
                  <td>{{ row.jp_diharapkan ?? row.sesi_diharapkan ?? 0 }}</td>
                  <td>{{ row.pertemuan_diharapkan ?? 0 }}</td>
                  <td>{{ row.persentase_hadir ?? 0 }}%</td>
                  <td>{{ row.persentase_hadir_jp ?? 0 }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { useToast } from '@/composables/useToast'
import { studentAttendanceApi } from '@/api/attendance'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { semesterApi } from '@/api/semester'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { useAuthStore } from '@/stores/auth'
import { studentAttendanceStorage, isOnline, onNetworkStatusChange } from '@/utils/offlineStorage'
import { useOfflineSync } from '@/composables/useOfflineSync'

const toast = useToast()
const route = useRoute()
const authStore = useAuthStore()
const { syncPendingItems } = useOfflineSync()

const DAY_NAMES = {
  1: 'Senin',
  2: 'Selasa',
  3: 'Rabu',
  4: 'Kamis',
  5: 'Jumat',
  6: 'Sabtu',
  7: 'Minggu',
}

const isOffline = ref(!isOnline())
const offlineIndicator = ref(false)
const activeTab = ref('isi')
const exporting = ref('')
const loadingRekap = computed(() => rekapLoading.value)

onNetworkStatusChange((online) => {
  isOffline.value = !online
  if (online) {
    syncPendingItems()
  }
})

const studentStatusOptions = {
  hadir: 'Hadir',
  alpha: 'Alpha',
  izin: 'Izin',
  sakit: 'Sakit',
  dinas_luar: 'Dinas Luar',
}

const filters = ref({
  semester_id: '',
  class_id: '',
  subject_id: '',
  date_from: '',
  date_to: '',
})
const semesters = ref([])
const classes = ref([])
const allSubjects = ref([])

const fillForm = ref({
  semester_id: '',
  pair_key: '',
  session_key: '',
  date: new Date().toISOString().slice(0, 10),
})

const teachingPairs = ref([])
const teachingSchedules = ref([])
const rekapTeachingPairs = ref([])

const rekapClasses = computed(() => {
  if (!rekapTeachingPairs.value.length) return classes.value
  const byId = new Map()
  for (const p of rekapTeachingPairs.value) {
    if (p.class_id == null) continue
    byId.set(String(p.class_id), {
      id: p.class_id,
      name: p.class_name || `Kelas ${p.class_id}`,
    })
  }
  const list = Array.from(byId.values())
  return list.length ? list.sort((a, b) => String(a.name).localeCompare(String(b.name))) : classes.value
})

const rekapSubjects = computed(() => {
  const classId = filters.value.class_id
  const fromPairs = rekapTeachingPairs.value.filter((p) => {
    if (!classId) return true
    return String(p.class_id) === String(classId)
  })
  if (fromPairs.length) {
    const byId = new Map()
    for (const p of fromPairs) {
      if (p.subject_id == null) continue
      byId.set(String(p.subject_id), {
        id: p.subject_id,
        name: p.subject_name || `Mapel ${p.subject_id}`,
      })
    }
    return Array.from(byId.values()).sort((a, b) => String(a.name).localeCompare(String(b.name)))
  }
  return allSubjects.value
})
const loadLoading = ref(false)
const loadError = ref('')

const preparedJournal = ref(null)
const preparedSchedule = ref(null)
const preparedScheduleIds = ref([])
const dayMismatchMessage = ref('')
const attendanceRows = ref([])
const attendanceLoading = ref(false)
const attendanceSaving = ref(false)
const attendanceFormError = ref('')

const rekapRows = ref([])
const rekapTotals = ref({})
const rekapMeta = ref(null)
const rekapLoading = ref(false)
const rekapLoaded = ref(false)

const pairSlots = computed(() => {
  if (!fillForm.value.pair_key) return []
  const [classId, subjectId] = fillForm.value.pair_key.split('-')
  return teachingSchedules.value
    .filter((s) => String(s.class_id) === classId && String(s.subject_id) === subjectId)
    .slice()
    .sort((a, b) => (a.day_of_week - b.day_of_week) || (a.period - b.period) || (a.id - b.id))
})

function formatPeriodRange(periods) {
  if (!periods.length) return ''
  if (periods.length === 1) return `Jam ke-${periods[0]}`
  return `Jam ke-${periods[0]}–${periods[periods.length - 1]}`
}

function formatSlotTime(slot) {
  if (!slot?.start_time) return ''
  return ` (${slot.start_time}${slot.end_time ? `–${slot.end_time}` : ''})`
}

function buildConsecutiveBlocks(slots) {
  const blocks = []
  let current = []

  const flush = () => {
    if (!current.length) return
    blocks.push(current)
    current = []
  }

  for (const slot of slots) {
    if (!current.length) {
      current = [slot]
      continue
    }
    const prev = current[current.length - 1]
    const sameGroup =
      Number(prev.day_of_week) === Number(slot.day_of_week)
      && Number(prev.class_id) === Number(slot.class_id)
      && Number(prev.subject_id) === Number(slot.subject_id)
      && Number(prev.employee_id) === Number(slot.employee_id)
      && Number(slot.period) === Number(prev.period) + 1

    if (sameGroup) {
      current.push(slot)
    } else {
      flush()
      current = [slot]
    }
  }
  flush()
  return blocks
}

const sessionOptions = computed(() => {
  const slots = pairSlots.value
  if (!slots.length) return []

  const blocks = buildConsecutiveBlocks(slots)
  const options = []

  for (const block of blocks) {
    const periods = block.map((s) => Number(s.period))
    const day = dayName(block[0].day_of_week)
    if (block.length > 1) {
      options.push({
        key: `block:${block.map((s) => s.id).join('-')}`,
        label: `${day} · ${formatPeriodRange(periods)} (isi sekali untuk ${block.length} jam)`,
        schedule_ids: block.map((s) => Number(s.id)),
        day_of_week: Number(block[0].day_of_week),
        periods,
        is_block: true,
        representative: block[0],
      })
    }
  }

  const blockPeriodKeys = new Set(
    blocks
      .filter((b) => b.length > 1)
      .flatMap((b) => b.map((s) => `${s.day_of_week}:${s.period}`))
  )

  for (const slot of slots) {
    const inBlock = blockPeriodKeys.has(`${slot.day_of_week}:${slot.period}`)
    const suffix = inBlock ? ' saja' : ''
    options.push({
      key: `single:${slot.id}`,
      label: `${dayName(slot.day_of_week)} · Jam ke-${slot.period}${suffix}${formatSlotTime(slot)}`,
      schedule_ids: [Number(slot.id)],
      day_of_week: Number(slot.day_of_week),
      periods: [Number(slot.period)],
      is_block: false,
      representative: slot,
    })
  }

  return options
})

const hasConsecutiveBlocks = computed(() => sessionOptions.value.some((o) => o.is_block))

const selectedSession = computed(() => {
  const key = fillForm.value.session_key
  if (!key) return null
  return sessionOptions.value.find((o) => o.key === key) || null
})

const canPrepare = computed(() => {
  return !!(selectedSession.value?.schedule_ids?.length && fillForm.value.date && fillForm.value.semester_id)
})

function dayName(day) {
  return DAY_NAMES[Number(day)] || `Hari ${day}`
}

function formatDate(d) {
  if (!d) return '-'
  const date = typeof d === 'string' ? new Date(d) : d
  return date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })
}

function isoDayFromDate(dateStr) {
  if (!dateStr) return null
  const d = new Date(`${dateStr}T12:00:00`)
  const jsDay = d.getDay() // 0=Sun..6=Sat
  return jsDay === 0 ? 7 : jsDay
}

function buildMismatchMessage(session, dateStr) {
  const slot = session?.representative || session
  if (!slot || !dateStr) return ''
  const selectedDay = isoDayFromDate(dateStr)
  const scheduledDay = Number(slot.day_of_week)
  if (!selectedDay || selectedDay === scheduledDay) return ''
  const subjectName = slot.subject?.name || slot.subject_name || 'Mapel'
  const className = slot.school_class?.name || slot.class_name || 'kelas'
  return `${subjectName} kelas ${className} dijadwalkan hari ${dayName(scheduledDay)}, tanggal yang dipilih adalah ${dayName(selectedDay)}.`
}

function cleanParams(extra = {}) {
  const params = { ...filters.value, ...extra }
  Object.keys(params).forEach((key) => {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      delete params[key]
    }
  })
  return params
}

function resetPreparedSession() {
  preparedJournal.value = null
  preparedSchedule.value = null
  preparedScheduleIds.value = []
  attendanceRows.value = []
  attendanceFormError.value = ''
  offlineIndicator.value = false
  dayMismatchMessage.value = buildMismatchMessage(selectedSession.value, fillForm.value.date)
}

function onDateChange() {
  dayMismatchMessage.value = buildMismatchMessage(selectedSession.value, fillForm.value.date)
  preparedJournal.value = null
  preparedSchedule.value = null
  preparedScheduleIds.value = []
  attendanceRows.value = []
  attendanceFormError.value = ''
}

function onPairChange() {
  fillForm.value.session_key = ''
  const options = sessionOptions.value
  const preferred = options.find((o) => o.is_block) || (options.length === 1 ? options[0] : null)
  if (preferred) {
    fillForm.value.session_key = preferred.key
  }
  resetPreparedSession()
}

async function loadTeachingLoad() {
  loadError.value = ''
  teachingPairs.value = []
  teachingSchedules.value = []
  if (!fillForm.value.semester_id) return

  loadLoading.value = true
  try {
    const res = await lessonScheduleApi.getMyTeachingLoad({ semester_id: fillForm.value.semester_id })
    const data = res.data?.data || {}
    teachingPairs.value = Array.isArray(data.pairs) ? data.pairs : []
    teachingSchedules.value = Array.isArray(data.schedules) ? data.schedules : []

    if (fillForm.value.pair_key) {
      const stillValid = teachingPairs.value.some(
        (p) => `${p.class_id}-${p.subject_id}` === fillForm.value.pair_key
      )
      if (!stillValid) {
        fillForm.value.pair_key = ''
        fillForm.value.session_key = ''
      } else if (fillForm.value.session_key) {
        const keyOk = sessionOptions.value.some((o) => o.key === fillForm.value.session_key)
        if (!keyOk) fillForm.value.session_key = ''
      }
    }
  } catch (e) {
    loadError.value = e.formattedMessage || 'Gagal memuat jadwal mengajar.'
    teachingPairs.value = []
    teachingSchedules.value = []
  } finally {
    loadLoading.value = false
  }
}

async function loadRekapTeachingPairs() {
  rekapTeachingPairs.value = []
  if (!filters.value.semester_id) return
  try {
    const res = await lessonScheduleApi.getMyTeachingLoad({ semester_id: filters.value.semester_id })
    const data = res.data?.data || {}
    rekapTeachingPairs.value = Array.isArray(data.pairs) ? data.pairs : []
  } catch {
    rekapTeachingPairs.value = []
  }
}

function syncSubjectAgainstOptions() {
  if (!filters.value.subject_id) return
  const ok = rekapSubjects.value.some((s) => String(s.id) === String(filters.value.subject_id))
  if (!ok) filters.value.subject_id = ''
}

function syncClassAgainstOptions() {
  if (!filters.value.class_id) return
  const ok = rekapClasses.value.some((c) => String(c.id) === String(filters.value.class_id))
  if (!ok) {
    filters.value.class_id = ''
    filters.value.subject_id = ''
  }
}

async function onSemesterChange() {
  fillForm.value.pair_key = ''
  fillForm.value.session_key = ''
  await loadTeachingLoad()
}

async function prepareSession() {
  if (!canPrepare.value) return
  const session = selectedSession.value
  if (!session) return

  const mismatch = buildMismatchMessage(session, fillForm.value.date)
  dayMismatchMessage.value = mismatch
  if (mismatch) {
    const ok = window.confirm(`${mismatch}\n\nTetap lanjut mengisi absensi?`)
    if (!ok) return
  }

  attendanceLoading.value = true
  attendanceFormError.value = ''
  attendanceRows.value = []
  preparedJournal.value = null
  preparedSchedule.value = null
  preparedScheduleIds.value = []
  offlineIndicator.value = false

  try {
    const res = await studentAttendanceApi.prepareFromSchedule({
      lesson_schedule_ids: session.schedule_ids,
      date: fillForm.value.date,
    })
    const payload = res.data?.data || {}
    preparedJournal.value = payload.teaching_journal || null
    preparedSchedule.value = payload.schedule || null
    preparedScheduleIds.value = Array.isArray(payload.schedule?.ids)
      ? payload.schedule.ids
      : session.schedule_ids
    if (payload.day_mismatch && payload.day_mismatch_message) {
      dayMismatchMessage.value = payload.day_mismatch_message
    }
    attendanceRows.value = (payload.attendances || []).map((row) => ({
      student_id: row.student_id,
      status: row.status || 'hadir',
      notes: row.notes || '',
      student: row.student,
    }))
  } catch (e) {
    toast.error('Gagal memuat daftar absensi', e.response?.data?.message || e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    attendanceLoading.value = false
  }
}

async function submitAttendance() {
  const ids = preparedScheduleIds.value.length
    ? preparedScheduleIds.value
    : (selectedSession.value?.schedule_ids || [])
  if (!ids.length || !fillForm.value.date || !attendanceRows.value.length) return
  attendanceSaving.value = true
  attendanceFormError.value = ''
  try {
    const attendances = attendanceRows.value.map((row) => ({
      student_id: row.student_id,
      status: row.status,
      notes: row.notes || null,
    }))
    const students = attendanceRows.value.map((row) => row.student).filter(Boolean)
    const journalId = preparedJournal.value?.id

    if (isOffline.value || !navigator.onLine) {
      if (!journalId) {
        attendanceFormError.value = 'Mode offline membutuhkan sesi yang sudah dimuat saat online.'
        return
      }
      await studentAttendanceStorage.save(journalId, attendances, students)
      toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
    } else {
      try {
        const res = await studentAttendanceApi.saveFromSchedule(
          ids,
          fillForm.value.date,
          attendances
        )
        const payload = res.data?.data || {}
        if (payload.teaching_journal) {
          preparedJournal.value = payload.teaching_journal
        }
        if (payload.schedule) {
          preparedSchedule.value = payload.schedule
        }
        const n = (payload.periods || ids).length
        toast.success(n > 1
          ? `Absensi berhasil disimpan untuk ${n} jam pelajaran`
          : 'Absensi siswa berhasil disimpan')
        rekapLoaded.value = false
      } catch (e) {
        if (journalId) {
          await studentAttendanceStorage.save(journalId, attendances, students)
          toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
        } else {
          throw e
        }
      }
    }
  } catch (e) {
    attendanceFormError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan absensi'
  } finally {
    attendanceSaving.value = false
  }
}

async function loadRekap() {
  rekapLoading.value = true
  try {
    const res = await studentAttendanceApi.getRekap(cleanParams())
    rekapRows.value = res.data.data || []
    rekapTotals.value = res.data.totals || {}
    rekapMeta.value = res.data.meta || null
    rekapLoaded.value = true
  } catch (e) {
    toast.error('Gagal memuat rekap', e.formattedMessage || 'Rekap absensi tidak dapat dimuat.')
    rekapRows.value = []
    rekapTotals.value = {}
    rekapMeta.value = null
  } finally {
    rekapLoading.value = false
  }
}

function onRekapFilterChange() {
  loadRekap()
}

async function onRekapSemesterChange() {
  await loadRekapTeachingPairs()
  syncClassAgainstOptions()
  syncSubjectAgainstOptions()
  loadRekap()
}

function onRekapClassChange() {
  syncSubjectAgainstOptions()
  loadRekap()
}

async function switchToRekap() {
  activeTab.value = 'rekap'
  // Prefill class/subject from tab isi absensi bila ada
  if (fillForm.value.pair_key) {
    const [classId, subjectId] = String(fillForm.value.pair_key).split('-')
    if (classId) filters.value.class_id = String(classId)
    if (subjectId) filters.value.subject_id = String(subjectId)
  }
  if (fillForm.value.semester_id && !filters.value.semester_id) {
    filters.value.semester_id = String(fillForm.value.semester_id)
  }
  await loadRekapTeachingPairs()
  syncClassAgainstOptions()
  syncSubjectAgainstOptions()
  loadRekap()
}

async function exportRekap(format) {
  exporting.value = format
  try {
    const res = await studentAttendanceApi.exportRekap(cleanParams({ format }))
    if (format === 'pdf') {
      const blob = res.data instanceof Blob
        ? res.data
        : new Blob([res.data], { type: 'application/pdf' })
      const url = URL.createObjectURL(blob)
      const win = window.open('', '_blank')
      if (!win) {
        toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
        URL.revokeObjectURL(url)
        return
      }
      const title = 'Preview Rekap Absensi Siswa'
      win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
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
          <h1>${title}<span class="hint">Preview cetak</span></h1>
          <div class="actions">
            <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
            <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
          </div>
        </div>
        <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
      </body></html>`)
      win.document.close()
      setTimeout(() => URL.revokeObjectURL(url), 120_000)
      toast.success('Berhasil', 'Preview PDF dibuka di tab baru')
      return
    }

    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `Rekap_Absensi_Siswa_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Rekap CSV berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor', e.formattedMessage || 'Rekap tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = ''
  }
}

onMounted(async () => {
  try {
    const [semRes, classRes] = await Promise.all([
      semesterApi.getAll({ per_page: 200 }),
      classApi.getAll({ per_page: 200 }),
    ])
    semesters.value = semRes.data.data || []
    classes.value = classRes.data.data || []
  } catch {
    // ignore
  }

  try {
    const subjectRes = await subjectApi.getAll({ per_page: 500 })
    allSubjects.value = subjectRes.data.data || []
  } catch {
    allSubjects.value = []
  }

  const q = route.query
  const activeSemester =
    q.semester_id
    || authStore.activeInstitution?.active_semester_id
    || authStore.user?.institution?.active_semester_id
    || (semesters.value[0]?.id ?? '')

  fillForm.value.semester_id = activeSemester ? String(activeSemester) : ''
  filters.value.semester_id = fillForm.value.semester_id

  if (q.class_id) filters.value.class_id = String(q.class_id)
  if (q.subject_id) filters.value.subject_id = String(q.subject_id)
  if (q.date_from) filters.value.date_from = String(q.date_from)
  if (q.date_to) filters.value.date_to = String(q.date_to)
  if (q.date) fillForm.value.date = String(q.date)

  await Promise.all([loadTeachingLoad(), loadRekapTeachingPairs()])
  syncClassAgainstOptions()
  syncSubjectAgainstOptions()

  if (q.class_id && q.subject_id) {
    const key = `${q.class_id}-${q.subject_id}`
    const exists = teachingPairs.value.some((p) => `${p.class_id}-${p.subject_id}` === key)
    if (exists) {
      fillForm.value.pair_key = key
      onPairChange()
    }
  } else if (q.class_id) {
    const match = teachingPairs.value.find((p) => String(p.class_id) === String(q.class_id))
    if (match) {
      fillForm.value.pair_key = `${match.class_id}-${match.subject_id}`
      onPairChange()
    }
  }

  if (q.tab === 'rekap') {
    switchToRekap()
  }
})
</script>

<style scoped>
.attendance-student-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.page-header { margin-bottom: 1rem; }
.header-content { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
.toolbar .filters { margin-bottom: 0; flex: 1; min-width: 200px; }
.section-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.sec-btn {
  padding: 0.45rem 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  cursor: pointer;
  font-size: 0.9rem;
}
.sec-btn.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
}
.fill-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.25rem 1.25rem;
}
.fill-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.75rem;
}
.hint-text { color: #64748b; font-size: 0.9rem; margin: 0.25rem 0 0.75rem; }
.day-warning {
  background: #fff7ed;
  border: 1px solid #fdba74;
  color: #9a3412;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}
.session-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
  color: #065f46;
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
}
.session-meta span {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
}
.session-meta .meta-block {
  background: #eff6ff;
  border-color: #93c5fd;
  color: #1d4ed8;
}
.rekap-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  padding: 0.75rem 1rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #065f46;
}
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center; }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; min-width: 140px; transition: border-color 0.2s, box-shadow 0.2s; }
.filter-wide { min-width: 220px; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.loading-wrap { width: 100%; margin: 1rem 0; }
.empty-state { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 12px; }
.empty-title { font-size: 1.25rem; margin: 0 0 0.5rem 0; }
.empty-desc { color: #64748b; margin: 0; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 600; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; }
.btn-primary.btn-compact, .btn-secondary.btn-compact { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.table-scroll { max-height: 55vh; overflow-y: auto; margin-bottom: 1rem; }
.status-select { min-width: 120px; padding: 0.4rem 0.5rem; }
.notes-input { width: 100%; max-width: 180px; padding: 0.4rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.form-error { color: #dc2626; font-size: 0.9rem; margin-bottom: 0.75rem; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem; }
.btn-secondary { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; }
.btn-primary { padding: 0.5rem 1rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-primary:disabled, .btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }
.offline-indicator {
  background: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  text-align: center;
}
</style>
