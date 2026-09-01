<template>    <div class="mapel-page">
      <div v-if="!assignment" class="empty-panel">
        <div class="empty-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <h3>Penugasan tidak ditemukan</h3>
        <p>
          Pair mapel–kelas ini tidak ada di jadwal Anda pada sekolah aktif.
          Pastikan semester aktif dan jadwal sudah diisi.
        </p>
        <router-link to="/teacher/dashboard" class="btn-primary">Kembali ke dashboard</router-link>
      </div>

      <template v-else>
        <div class="welcome-section">
          <div class="welcome-content">
            <h1>{{ assignment.subject_name || 'Mata Pelajaran' }}</h1>
            <template v-if="assignment.class_name">
              <span class="welcome-sep">·</span>
              <p class="welcome-inst">{{ assignment.class_name }}</p>
            </template>
          </div>
          <div class="welcome-actions">
            <router-link to="/teacher/dashboard" class="profile-link">Dashboard</router-link>
          </div>
        </div>

        <div class="primary-actions">
          <router-link
            v-if="canAccessModule('teaching_journal') || canAccessModule('grade_book')"
            :to="todayLink"
            class="primary-card primary-card-main"
          >
            <div class="primary-icon" aria-hidden="true" v-html="icons.today"></div>
            <div>
              <h3>Isi Absen, Jurnal & Nilai</h3>
              <p>Alur harian — bisa diisi kapan saja, tidak terkunci hari jadwal</p>
            </div>
          </router-link>
          <router-link
            v-if="canAccessModule('grade_book')"
            :to="gradeLink"
            class="primary-card"
          >
            <div class="primary-icon primary-icon-grade" aria-hidden="true" v-html="icons.grade"></div>
            <div>
              <h3>Buku Nilai</h3>
              <p>Input nilai lengkap, KKM & bobot</p>
            </div>
          </router-link>
          <router-link
            v-if="canAccessModule('teaching_journal')"
            :to="attendanceRekapLink"
            class="primary-card"
          >
            <div class="primary-icon primary-icon-attendance" aria-hidden="true" v-html="icons.attendance"></div>
            <div>
              <h3>Rekap Absensi</h3>
              <p>Cetak rekap PDF/CSV</p>
            </div>
          </router-link>
        </div>

        <div v-if="secondaryActions.length" class="secondary-actions">
          <router-link
            v-for="action in secondaryActions"
            :key="action.to"
            :to="action.to"
            class="secondary-link"
          >
            {{ action.title }}
          </router-link>
        </div>

        <div v-if="hasDetailTabs" class="detail-panel">
          <div class="tab-bar" role="tablist">
            <button
              v-for="tab in detailTabs"
              :key="tab.id"
              type="button"
              role="tab"
              class="tab-btn"
              :class="{ active: activeTab === tab.id }"
              @click="activeTab = tab.id"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- Ringkasan -->
          <div v-if="activeTab === 'summary' && canAccessModule('grade_book')" class="tab-content">
            <div class="tab-toolbar">
              <button type="button" class="btn-ghost" :disabled="completenessLoading" @click="loadCompleteness">
                {{ completenessLoading ? 'Memuat...' : 'Muat ulang' }}
              </button>
            </div>
            <div v-if="completenessError" class="empty-inline empty-error"><p>{{ completenessError }}</p></div>
            <div v-else-if="completeness" class="completeness-grid">
              <div class="metric-card">
                <span class="metric-label">Nilai akhir terisi</span>
                <strong>{{ completeness.percent?.nilai_akhir ?? 0 }}%</strong>
                <span class="metric-sub">{{ completeness.filled?.nilai_akhir ?? 0 }}/{{ completeness.student_count ?? 0 }} siswa</span>
              </div>
              <div class="metric-card">
                <span class="metric-label">Tuntas KKM</span>
                <strong>{{ completeness.percent?.tuntas ?? 0 }}%</strong>
                <span class="metric-sub">{{ completeness.tuntas_count ?? 0 }} tuntas · {{ completeness.belum_tuntas_count ?? 0 }} belum</span>
              </div>
              <div class="metric-card">
                <span class="metric-label">Penilaian</span>
                <strong>{{ completeness.percent?.penilaian ?? 0 }}%</strong>
                <span class="metric-sub">UTS {{ completeness.percent?.uts ?? 0 }}% · UAS {{ completeness.percent?.uas ?? 0 }}%</span>
              </div>
              <div class="deadline-list">
                <div v-for="(d, key) in completeness.deadlines || {}" :key="key" class="deadline-row">
                  <span class="deadline-key">{{ deadlineLabel(key) }}</span>
                  <span v-if="!d?.due_date" class="status-pill muted">Belum diatur</span>
                  <span v-else :class="['status-pill', deadlineTone(d.status)]">
                    {{ d.due_date }} · {{ deadlineStatusLabel(d) }}
                  </span>
                </div>
                <router-link :to="gradeLink" class="btn-secondary btn-sm deadline-link">Atur di Buku Nilai</router-link>
              </div>
            </div>
            <div v-else-if="!completenessLoading" class="empty-inline"><p>Belum ada ringkasan kelengkapan.</p></div>
          </div>

          <!-- Nilai -->
          <div v-if="activeTab === 'grades' && canAccessModule('grade_book')" class="tab-content">
            <div class="tab-toolbar">
              <span class="tab-meta">
                {{ gradeRows.length }} siswa
                <template v-if="gradeMeta.kkm != null"> · KKM {{ formatScore(gradeMeta.kkm) }}</template>
              </span>
              <div class="tab-toolbar-actions">
                <button type="button" class="btn-ghost" :disabled="gradesLoading" @click="loadGrades">
                  {{ gradesLoading ? 'Memuat...' : 'Muat ulang' }}
                </button>
                <router-link :to="gradeLink" class="btn-primary btn-sm">Buka Buku Nilai</router-link>
              </div>
            </div>
            <div v-if="gradesLoading && !gradeRows.length" class="empty-inline"><p>Memuat daftar siswa dan nilai...</p></div>
            <div v-else-if="gradesError" class="empty-inline empty-error">
              <p>{{ gradesError }}</p>
              <button type="button" class="btn-primary btn-sm" @click="loadGrades">Coba lagi</button>
            </div>
            <div v-else-if="!gradeRows.length" class="empty-inline"><p>Belum ada siswa di kelas ini.</p></div>
            <div v-else class="table-wrap">
              <div class="grades-stats">
                <span>{{ filledCount }} sudah punya nilai akhir</span>
                <span v-if="gradeMeta.kkm != null">{{ tuntasCount }} tuntas</span>
              </div>
              <table class="grades-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Rata P</th>
                    <th>UTS</th>
                    <th>UAS</th>
                    <th>Nilai Akhir</th>
                    <th>Peringkat</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in gradeRows" :key="row.student_id">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ row.student?.nis || '—' }}</td>
                    <td class="name-cell">{{ row.student?.name || '—' }}</td>
                    <td>{{ formatScore(row.rata_penilaian) }}</td>
                    <td>{{ formatScore(row.uts) }}</td>
                    <td>{{ formatScore(row.uas) }}</td>
                    <td><strong :class="scoreClass(row)">{{ formatScore(row.nilai_akhir) }}</strong></td>
                    <td>
                      <span v-if="row.rank != null" :class="['rank-pill', rankTone(row.rank)]">#{{ row.rank }}</span>
                      <span v-else class="muted-dash">—</span>
                    </td>
                    <td>
                      <span v-if="row.tuntas_label" :class="['status-pill', row.is_tuntas ? 'ok' : 'warn']">{{ row.tuntas_label }}</span>
                      <span v-else-if="hasAnyGrade(row)" class="status-pill muted">Terisi sebagian</span>
                      <span v-else class="status-pill muted">Belum diisi</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Kehadiran -->
          <div v-if="activeTab === 'attendance' && canAccessModule('teaching_journal')" class="tab-content">
            <div class="tab-toolbar">
              <span v-if="rekapMeta" class="tab-meta">
                {{ rekapMeta.meeting_days ?? 0 }} hari pertemuan ·
                {{ rekapMeta.jp_count ?? rekapMeta.session_count ?? 0 }} JP ·
                % Hadir: {{ rekapTotals.persentase_hadir_jp ?? 0 }}%
              </span>
              <div class="tab-toolbar-actions">
                <button type="button" class="btn-ghost" :disabled="rekapLoading" @click="loadRekap">
                  {{ rekapLoading ? 'Memuat...' : 'Muat ulang' }}
                </button>
                <router-link :to="attendanceRekapLink" class="btn-primary btn-sm">Rekap Lengkap</router-link>
              </div>
            </div>
            <div v-if="rekapError" class="empty-inline empty-error"><p>{{ rekapError }}</p></div>
            <div v-else-if="rekapRows.length" class="table-wrap">
              <table class="grades-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>H</th>
                    <th>A</th>
                    <th>I</th>
                    <th>S</th>
                    <th>% JP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in rekapPreview" :key="row.student_id">
                    <td>{{ idx + 1 }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td class="name-cell">{{ row.name }}</td>
                    <td>{{ row.counts?.hadir ?? 0 }}</td>
                    <td>{{ row.counts?.alpha ?? 0 }}</td>
                    <td>{{ row.counts?.izin ?? 0 }}</td>
                    <td>{{ row.counts?.sakit ?? 0 }}</td>
                    <td>{{ row.persentase_hadir_jp ?? 0 }}%</td>
                  </tr>
                </tbody>
              </table>
              <p v-if="rekapRows.length > 10" class="section-sub">
                Menampilkan 10 dari {{ rekapRows.length }} siswa.
              </p>
            </div>
            <div v-else-if="!rekapLoading" class="empty-inline">
              <p>Belum ada data rekap. Isi absensi dari Jam Mengajar.</p>
            </div>
          </div>

          <!-- Remidi -->
          <div v-if="activeTab === 'remedial' && canAccessModule('grade_book')" class="tab-content">
            <div class="tab-toolbar">
              <span class="tab-meta">
                Siswa di bawah KKM
                <template v-if="remedialMeta.kkm != null"> · KKM {{ formatScore(remedialMeta.kkm) }}</template>
              </span>
              <button type="button" class="btn-ghost" :disabled="remedialLoading" @click="loadRemedials">
                {{ remedialLoading ? 'Memuat...' : 'Muat ulang' }}
              </button>
            </div>
            <div v-if="remedialError" class="empty-inline empty-error"><p>{{ remedialError }}</p></div>
            <div v-else-if="!belowKkmRows.length" class="empty-inline">
              <p>{{ remedialLoading ? 'Memuat...' : 'Tidak ada siswa di bawah KKM.' }}</p>
            </div>
            <div v-else class="table-wrap">
              <table class="grades-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Nilai Akhir</th>
                    <th>Selisih</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in belowKkmRows" :key="row.student_id">
                    <td>{{ idx + 1 }}</td>
                    <td class="name-cell">{{ row.student?.name || '—' }}</td>
                    <td>{{ formatScore(row.nilai_akhir) }}</td>
                    <td>{{ row.gap != null ? `−${formatScore(row.gap)}` : '—' }}</td>
                    <td>
                      <span v-if="row.latest_remedial" :class="['status-pill', row.latest_remedial.status === 'completed' ? 'ok' : 'warn']">
                        {{ row.latest_remedial.status_label || row.latest_remedial.status }}
                      </span>
                      <span v-else class="status-pill muted">Belum dijadwalkan</span>
                    </td>
                    <td>
                      <div class="remedial-actions">
                        <button
                          v-if="!row.latest_remedial || row.latest_remedial.status === 'cancelled' || row.latest_remedial.status === 'completed'"
                          type="button"
                          class="btn-ghost btn-sm"
                          :disabled="remedialBusyId === row.student_id"
                          @click="scheduleRemedial(row)"
                        >
                          Jadwalkan
                        </button>
                        <template v-else-if="row.latest_remedial.status === 'planned'">
                          <button type="button" class="btn-primary btn-sm" :disabled="remedialBusyId === row.latest_remedial.id" @click="completeRemedial(row)">Catat Nilai</button>
                          <button type="button" class="btn-ghost btn-sm" :disabled="remedialBusyId === row.latest_remedial.id" @click="cancelRemedial(row)">Batal</button>
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>
    </div></template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { gradeBookApi } from '@/api/gradeBook'
import { studentAttendanceApi } from '@/api/attendance'

const authStore = useAuthStore()
const route = useRoute()
const toast = useToast()

const assignments = computed(() => authStore.user?.teaching_assignments || [])

const assignment = computed(() => {
  const classId = route.query.class_id ? String(route.query.class_id) : ''
  const subjectId = route.query.subject_id ? String(route.query.subject_id) : ''
  if (!classId || !subjectId) return null
  return assignments.value.find(
    (a) => String(a.class_id) === classId && String(a.subject_id) === subjectId
  ) || null
})

const canAccessModule = (key) => (authStore.user?.permissions || []).includes(key)

const semesterId = computed(() => (
  assignment.value?.semester_id
  || authStore.activeInstitution?.active_semester_id
  || authStore.user?.institution?.active_semester_id
  || null
))

function buildQuery(extra = {}) {
  const q = {
    class_id: assignment.value?.class_id,
    subject_id: assignment.value?.subject_id,
    ...(semesterId.value ? { semester_id: semesterId.value } : {}),
    ...extra,
  }
  const params = new URLSearchParams()
  Object.entries(q).forEach(([k, v]) => {
    if (v !== null && v !== undefined && v !== '') params.set(k, String(v))
  })
  return params.toString()
}

const gradeLink = computed(() => `/grade-book?${buildQuery()}`)
const journalLink = computed(() => `/teaching-journal?${buildQuery()}`)
const todayLink = computed(() => {
  const params = new URLSearchParams()
  if (assignment.value?.class_id) params.set('class_id', String(assignment.value.class_id))
  if (assignment.value?.subject_id) params.set('subject_id', String(assignment.value.subject_id))
  return `/teacher/today?${params.toString()}`
})
const attendanceRekapLink = computed(() => {
  const params = new URLSearchParams()
  params.set('tab', 'rekap')
  if (assignment.value?.class_id) params.set('class_id', String(assignment.value.class_id))
  if (assignment.value?.subject_id) params.set('subject_id', String(assignment.value.subject_id))
  if (semesterId.value) params.set('semester_id', String(semesterId.value))
  return `/attendance/student?${params.toString()}`
})
const examLink = computed(() => {
  const params = new URLSearchParams()
  if (assignment.value?.subject_id) params.set('subject_id', String(assignment.value.subject_id))
  const qs = params.toString()
  return qs ? `/ujian-online/exams?${qs}` : '/ujian-online/exams'
})
const bankSoalLink = computed(() => {
  const params = new URLSearchParams()
  if (assignment.value?.subject_id) params.set('subject_id', String(assignment.value.subject_id))
  const qs = params.toString()
  return qs ? `/ujian-online/bank-soal?${qs}` : '/ujian-online/bank-soal'
})

const icons = {
  grade: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/><path d="M8 7h8M8 11h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
  attendance: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
  today: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>`,
}

const secondaryActions = computed(() => {
  const items = []
  if (canAccessModule('teaching_journal')) {
    items.push({ title: 'Jurnal Mengajar', to: journalLink.value })
  }
  if (canAccessModule('online_exam')) {
    items.push({ title: 'Ujian Online', to: examLink.value })
    items.push({ title: 'Bank Soal', to: bankSoalLink.value })
  }
  return items
})

const detailTabs = computed(() => {
  const tabs = []
  if (canAccessModule('grade_book')) {
    tabs.push({ id: 'summary', label: 'Ringkasan' })
    tabs.push({ id: 'grades', label: 'Nilai' })
    tabs.push({ id: 'remedial', label: 'Remidi' })
  }
  if (canAccessModule('teaching_journal')) {
    tabs.push({ id: 'attendance', label: 'Kehadiran' })
  }
  return tabs
})

const hasDetailTabs = computed(() => detailTabs.value.length > 0)
const activeTab = ref('summary')

const gradeRows = ref([])
const gradeMeta = ref({ kkm: null })
const gradesLoading = ref(false)
const gradesError = ref('')

const completeness = ref(null)
const completenessLoading = ref(false)
const completenessError = ref('')

const rekapRows = ref([])
const rekapMeta = ref(null)
const rekapTotals = ref({})
const rekapLoading = ref(false)
const rekapError = ref('')
const rekapPreview = computed(() => rekapRows.value.slice(0, 10))

const belowKkmRows = ref([])
const remedialMeta = ref({ kkm: null })
const remedialLoading = ref(false)
const remedialError = ref('')
const remedialBusyId = ref(null)

const filledCount = computed(() => gradeRows.value.filter((r) => r.nilai_akhir != null).length)
const tuntasCount = computed(() => gradeRows.value.filter((r) => r.is_tuntas === true).length)

function toNum(v) {
  if (v === null || v === undefined || v === '') return null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
}

function formatScore(v) {
  const n = toNum(v)
  if (n == null) return '—'
  return Number.isInteger(n) ? String(n) : n.toFixed(1)
}

function hasAnyGrade(row) {
  if (toNum(row.rata_penilaian) != null) return true
  if (toNum(row.uts) != null) return true
  if (toNum(row.uas) != null) return true
  if (toNum(row.nilai_akhir) != null) return true
  const p = row.penilaian
  if (p && typeof p === 'object') {
    return Object.values(p).some((v) => toNum(v) != null)
  }
  return false
}

function scoreClass(row) {
  if (row.is_tuntas === true) return 'score-ok'
  if (row.is_tuntas === false) return 'score-warn'
  return ''
}

function rankTone(rank) {
  const n = Number(rank)
  if (n === 1) return 'gold'
  if (n === 2) return 'silver'
  if (n === 3) return 'bronze'
  return ''
}

function deadlineLabel(key) {
  const map = { penilaian: 'Penilaian', uts: 'UTS', uas: 'UAS', nilai_akhir: 'Nilai Akhir' }
  return map[key] || key
}

function deadlineTone(status) {
  if (status === 'overdue' || status === 'due_today') return 'warn'
  if (status === 'upcoming') return 'ok'
  return 'muted'
}

function deadlineStatusLabel(d) {
  if (!d) return ''
  if (d.status === 'overdue') return `terlambat ${Math.abs(d.days_left || 0)} hari`
  if (d.status === 'due_today') return 'hari ini'
  if (d.status === 'upcoming') return `${d.days_left ?? 0} hari lagi`
  return d.status || ''
}

async function loadGrades() {
  if (!assignment.value || !semesterId.value || !canAccessModule('grade_book')) {
    gradeRows.value = []
    return
  }
  gradesLoading.value = true
  gradesError.value = ''
  try {
    const res = await gradeBookApi.getByClassSubjectSemester({
      semester_id: semesterId.value,
      class_id: assignment.value.class_id,
      subject_id: assignment.value.subject_id,
      page: 1,
      per_page: 100,
    })
    const m = res.data?.meta || {}
    gradeMeta.value = { kkm: m.kkm != null ? Number(m.kkm) : null }
    gradeRows.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (e) {
    gradeRows.value = []
    gradesError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat nilai siswa.'
  } finally {
    gradesLoading.value = false
  }
}

async function loadCompleteness() {
  if (!assignment.value || !semesterId.value || !canAccessModule('grade_book')) {
    completeness.value = null
    return
  }
  completenessLoading.value = true
  completenessError.value = ''
  try {
    const res = await gradeBookApi.getCompleteness({
      semester_id: semesterId.value,
      class_id: assignment.value.class_id,
      subject_id: assignment.value.subject_id,
    })
    completeness.value = res.data?.data || null
  } catch (e) {
    completeness.value = null
    completenessError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat kelengkapan nilai.'
  } finally {
    completenessLoading.value = false
  }
}

async function loadRekap() {
  if (!assignment.value || !semesterId.value || !canAccessModule('teaching_journal')) {
    rekapRows.value = []
    rekapMeta.value = null
    return
  }
  rekapLoading.value = true
  rekapError.value = ''
  try {
    const res = await studentAttendanceApi.getRekap({
      semester_id: semesterId.value,
      class_id: assignment.value.class_id,
      subject_id: assignment.value.subject_id,
    })
    rekapRows.value = Array.isArray(res.data?.data) ? res.data.data : []
    rekapMeta.value = res.data?.meta || null
    rekapTotals.value = res.data?.totals || {}
  } catch (e) {
    rekapRows.value = []
    rekapMeta.value = null
    rekapTotals.value = {}
    rekapError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat rekap kehadiran.'
  } finally {
    rekapLoading.value = false
  }
}

async function loadRemedials() {
  if (!assignment.value || !semesterId.value || !canAccessModule('grade_book')) {
    belowKkmRows.value = []
    return
  }
  remedialLoading.value = true
  remedialError.value = ''
  try {
    const res = await gradeBookApi.getBelowKkm({
      semester_id: semesterId.value,
      class_id: assignment.value.class_id,
      subject_id: assignment.value.subject_id,
    })
    belowKkmRows.value = Array.isArray(res.data?.data) ? res.data.data : []
    remedialMeta.value = { kkm: res.data?.meta?.kkm != null ? Number(res.data.meta.kkm) : null }
  } catch (e) {
    belowKkmRows.value = []
    remedialError.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat daftar remidi.'
  } finally {
    remedialLoading.value = false
  }
}

async function scheduleRemedial(row) {
  const date = window.prompt('Tanggal remidi (YYYY-MM-DD), kosongkan jika belum ditentukan:', '')
  if (date === null) return
  remedialBusyId.value = row.student_id
  try {
    await gradeBookApi.createRemedial({
      semester_id: semesterId.value,
      class_id: assignment.value.class_id,
      subject_id: assignment.value.subject_id,
      student_id: row.student_id,
      type: 'remedial',
      source_grade_type: 'nilai_akhir',
      scheduled_date: date || null,
      apply_to_grade: true,
    })
    toast.success('Remidi dijadwalkan')
    await loadRemedials()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.formattedMessage || 'Tidak dapat menjadwalkan remidi.')
  } finally {
    remedialBusyId.value = null
  }
}

async function completeRemedial(row) {
  const rem = row.latest_remedial
  if (!rem?.id) return
  const raw = window.prompt(`Nilai remidi untuk ${row.student?.name || 'siswa'} (0–100):`, '')
  if (raw === null || raw === '') return
  const value = Number(raw)
  if (!Number.isFinite(value) || value < 0 || value > 100) {
    toast.error('Nilai tidak valid', 'Masukkan angka 0–100.')
    return
  }
  remedialBusyId.value = rem.id
  try {
    await gradeBookApi.completeRemedial(rem.id, { remedial_value: value, apply_to_grade: true })
    toast.success('Nilai remidi disimpan')
    await Promise.all([loadRemedials(), loadGrades(), loadCompleteness()])
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.formattedMessage || 'Tidak dapat menyimpan nilai remidi.')
  } finally {
    remedialBusyId.value = null
  }
}

async function cancelRemedial(row) {
  const rem = row.latest_remedial
  if (!rem?.id) return
  if (!window.confirm(`Batalkan remidi untuk ${row.student?.name || 'siswa'}?`)) return
  remedialBusyId.value = rem.id
  try {
    await gradeBookApi.cancelRemedial(rem.id)
    toast.success('Remidi dibatalkan')
    await loadRemedials()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.formattedMessage || 'Tidak dapat membatalkan remidi.')
  } finally {
    remedialBusyId.value = null
  }
}

function reloadAll() {
  loadGrades()
  loadCompleteness()
  loadRekap()
  loadRemedials()
}

watch(
  () => [
    assignment.value?.class_id,
    assignment.value?.subject_id,
    semesterId.value,
    authStore.user?.permissions,
  ],
  () => {
    const tabs = detailTabs.value
    if (tabs.length && !tabs.find((t) => t.id === activeTab.value)) {
      activeTab.value = tabs[0].id
    }
    reloadAll()
  },
  { immediate: true }
)
</script>

<style scoped>
.mapel-page {
  width: 100%;
  max-width: 100%;
  padding: 0 0 8px;
}

.welcome-section {
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  border-radius: 14px;
  padding: 12px 20px;
  margin-bottom: 16px;
  color: white;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px 16px;
}

.welcome-content {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px 12px;
  min-width: 0;
}

.welcome-content h1 {
  font-size: 17px;
  font-weight: 700;
  margin: 0;
}

.welcome-sep { opacity: 0.7; }

.welcome-inst {
  font-size: 13px;
  opacity: 0.95;
  margin: 0;
  font-weight: 500;
}

.welcome-actions { flex-shrink: 0; }

.profile-link {
  display: inline-flex;
  align-items: center;
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  text-decoration: none;
  padding: 7px 12px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 8px;
}

.primary-actions {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}

.primary-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 18px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.primary-card:hover {
  border-color: #99f6e4;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.primary-card-main {
  border-color: #5eead4;
  background: linear-gradient(135deg, #f0fdfa 0%, #fff 100%);
}

.primary-card h3 {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.primary-card p {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

.primary-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(13, 148, 136, 0.12);
  color: #0d9488;
}

.primary-icon-grade { background: rgba(5, 150, 105, 0.1); color: #059669; }
.primary-icon-attendance { background: rgba(14, 165, 233, 0.12); color: #0284c7; }

.secondary-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.secondary-link {
  font-size: 12px;
  font-weight: 600;
  color: #0d9488;
  text-decoration: none;
  padding: 6px 12px;
  border: 1px solid #99f6e4;
  border-radius: 999px;
  background: #fff;
}

.secondary-link:hover { background: #f0fdfa; }

.detail-panel {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.tab-bar {
  display: flex;
  gap: 0;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
}

.tab-btn {
  padding: 12px 18px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  white-space: nowrap;
}

.tab-btn.active {
  color: #0d9488;
  border-bottom-color: #0d9488;
  background: #f0fdfa;
}

.tab-content { padding: 18px 20px; }

.tab-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.tab-meta { font-size: 12px; color: #64748b; font-weight: 500; }
.tab-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.completeness-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr)) minmax(200px, 1.2fr);
  gap: 12px;
}

@media (max-width: 900px) {
  .completeness-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 640px) {
  .completeness-grid { grid-template-columns: 1fr; }
  .primary-actions { grid-template-columns: 1fr; }
}

.metric-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.metric-label { font-size: 12px; color: #64748b; font-weight: 600; }
.metric-card strong { font-size: 22px; color: #0f172a; }
.metric-sub { font-size: 12px; color: #64748b; }

.deadline-list {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.deadline-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.deadline-key { color: #475569; font-weight: 600; }
.deadline-link { align-self: flex-start; margin-top: 4px; }

.grades-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
  margin-bottom: 12px;
  font-size: 12px;
  color: #065f46;
}

.grades-stats span {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
}

.table-wrap { overflow-x: auto; }

.grades-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.grades-table th,
.grades-table td {
  padding: 0.65rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.grades-table th {
  font-weight: 600;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #065f46;
}

.name-cell {
  font-weight: 600;
  color: #0f172a;
  white-space: normal;
  min-width: 140px;
}

.score-ok { color: #047857; }
.score-warn { color: #b45309; }
.muted-dash { color: #94a3b8; }

.rank-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2rem;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: #f1f5f9;
  color: #334155;
}

.rank-pill.gold { background: #fef3c7; color: #92400e; }
.rank-pill.silver { background: #e2e8f0; color: #334155; }
.rank-pill.bronze { background: #ffedd5; color: #9a3412; }

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.status-pill.ok { background: #d1fae5; color: #065f46; }
.status-pill.warn { background: #ffedd5; color: #9a3412; }
.status-pill.muted { background: #f1f5f9; color: #64748b; }

.section-sub { margin: 8px 0 0; font-size: 12px; color: #64748b; }

.empty-panel {
  text-align: center;
  padding: 40px 24px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.empty-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 12px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
}

.empty-panel h3 { margin: 0 0 8px; font-size: 16px; color: #0f172a; }
.empty-panel p { margin: 0 0 16px; color: #64748b; font-size: 13px; }

.empty-inline {
  padding: 18px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  text-align: center;
}

.empty-inline p { margin: 0; color: #64748b; font-size: 13px; }
.empty-error { display: flex; flex-direction: column; align-items: center; gap: 10px; }

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 14px;
  border-radius: 8px;
  background: #059669;
  color: #fff;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.btn-sm { padding: 7px 12px; font-size: 12px; }

.btn-ghost {
  padding: 7px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn-ghost:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 8px;
  background: #fff;
  color: #0f766e;
  border: 1px solid #99f6e4;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
}

.remedial-actions { display: flex; gap: 6px; flex-wrap: wrap; }
</style>
