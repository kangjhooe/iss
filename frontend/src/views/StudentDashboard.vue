<template>    <div class="sp-page dashboard">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah. Silakan hubungi operator sekolah atau admin.
      </div>

      <section class="sp-hero">
        <h1>{{ greeting }}, {{ studentName }}!</h1>
        <p v-if="institutionName">{{ institutionName }}</p>
        <p v-else>Memuat data sekolah...</p>
        <div v-if="classInfo" class="sp-hero-meta">{{ classInfo }}</div>
      </section>

      <router-link
        v-if="hasOutstanding"
        to="/student/keuangan"
        class="sp-banner-debt"
      >
        <span>Ada tagihan yang belum lunas</span>
        <strong>{{ outstandingLabel }}</strong>
      </router-link>

      <div class="sp-stats sp-stats--2">
        <router-link to="/student/poin" class="sp-stat sp-stat--primary">
          <div class="sp-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </div>
          <div>
            <span class="sp-stat-label">Poin</span>
            <span class="sp-stat-value">{{ loadingPoints ? '…' : (pointsSummary?.total_points ?? '-') }}</span>
          </div>
        </router-link>

        <router-link to="/student/absensi" class="sp-stat" :class="todayAlphaCount ? 'sp-stat--warn' : 'sp-stat--ok'">
          <div class="sp-stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </div>
          <div>
            <span class="sp-stat-label">Absen hari ini</span>
            <span class="sp-stat-value">{{ loadingAttendance ? '…' : todayAttendanceLabel }}</span>
            <span class="sp-stat-hint">{{ todayAttendanceHint }}</span>
          </div>
        </router-link>
      </div>

      <section v-if="nextSlot" class="sp-next">
        <div>
          <div class="sp-next-kicker">{{ nextSlot.isCurrent ? 'Sedang berlangsung' : 'Jam berikutnya' }}</div>
          <div class="sp-next-title">{{ nextSlot.subject?.name || 'Pelajaran' }}</div>
        </div>
        <div class="sp-next-meta">
          Jam {{ nextSlot.period }} · {{ nextSlot.start_time }}–{{ nextSlot.end_time }}
          <template v-if="nextSlot.room?.name"> · {{ nextSlot.room.name }}</template>
          <template v-if="nextSlot.employee?.name"> · {{ nextSlot.employee.name }}</template>
        </div>
      </section>

      <section class="sp-panel">
        <div class="sp-panel-header">
          <h2 class="sp-panel-title">Jadwal hari ini</h2>
          <router-link to="/student/jadwal" class="sp-panel-link">Minggu ini</router-link>
        </div>
        <div v-if="loadingSchedule" class="sp-loading"><p>Memuat jadwal...</p></div>
        <div v-else-if="scheduleToday.length" class="sp-slot-list">
          <div
            v-for="slot in scheduleToday"
            :key="slot.id || slot.period"
            class="sp-slot"
            :class="{ 'sp-now': isCurrentSlot(slot) }"
          >
            <div>
              <span class="sp-slot-period">Jam {{ slot.period }}</span>
              <span class="sp-slot-time">{{ slot.start_time }}–{{ slot.end_time }}</span>
            </div>
            <div>
              <div class="sp-list-title">{{ slot.subject?.name || '-' }}</div>
              <div class="sp-list-meta">
                <template v-if="slot.employee?.name">{{ slot.employee.name }}</template>
                <template v-if="slot.employee?.name && slot.room?.name"> · </template>
                <template v-if="slot.room?.name">{{ slot.room.name }}</template>
                <template v-if="!slot.employee?.name && !slot.room?.name">—</template>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="sp-empty">
          <p class="sp-empty-desc">{{ scheduleTodayMessage }}</p>
        </div>
      </section>

      <section v-if="upcomingEvents.length" class="sp-panel">
        <div class="sp-panel-header">
          <h2 class="sp-panel-title">Event mendatang</h2>
        </div>
        <div class="sp-list">
          <div v-for="ev in upcomingEvents" :key="ev.id" class="sp-list-item sp-list-item--info">
            <span class="sp-list-title">{{ ev.title }}</span>
            <span class="sp-list-meta">{{ formatDate(ev.start_date) }}</span>
          </div>
        </div>
      </section>

      <section v-if="untuntasGrades.length || (!loadingGrades && grades.length)" class="sp-panel">
        <div class="sp-panel-header">
          <h2 class="sp-panel-title">{{ untuntasGrades.length ? 'Nilai di bawah KKM' : 'Nilai semester ini' }}</h2>
          <router-link to="/student/nilai" class="sp-panel-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingGrades" class="sp-loading"><p>Memuat nilai...</p></div>
        <div v-else-if="previewGrades.length" class="sp-grade-grid">
          <article
            v-for="g in previewGrades"
            :key="g.id || g.subject_id"
            class="sp-grade-card"
            :class="{ 'is-warn': g.is_tuntas === false, 'is-ok': g.is_tuntas === true }"
          >
            <div class="sp-grade-card-top">
              <div class="sp-grade-name">{{ g.subject?.name || '-' }}</div>
              <div class="sp-grade-score">{{ g.nilai_akhir ?? g.value ?? '—' }}</div>
            </div>
            <div class="sp-grade-meta">
              <span>KKM {{ g.kkm ?? '—' }}</span>
              <span v-if="g.predicate">{{ g.predicate }}</span>
              <span v-if="g.is_tuntas === false" class="sp-badge sp-badge--danger">Belum tuntas</span>
            </div>
          </article>
        </div>
        <div v-else class="sp-empty">
          <p class="sp-empty-desc">Belum ada nilai untuk semester aktif.</p>
        </div>
      </section>

      <section v-if="recentNotes.length" class="sp-panel">
        <div class="sp-panel-header">
          <h2 class="sp-panel-title">Catatan terbaru</h2>
          <router-link to="/student/pelanggaran-prestasi" class="sp-panel-link">Lihat semua</router-link>
        </div>
        <div class="sp-list">
          <div
            v-for="item in recentNotes"
            :key="item.key"
            class="sp-list-item"
            :class="item.kind === 'v' ? 'sp-list-item--danger' : 'sp-list-item--ok'"
          >
            <span class="sp-list-kicker">{{ item.kind === 'v' ? 'Pelanggaran' : 'Prestasi' }}</span>
            <span class="sp-list-title">{{ item.title }}</span>
            <span class="sp-list-meta">{{ formatDate(item.date) }}</span>
          </div>
        </div>
      </section>

      <section class="sp-panel">
        <div class="sp-panel-header">
          <h2 class="sp-panel-title">Lainnya</h2>
        </div>
        <div class="sp-more-links">
          <router-link to="/ujian-ikuti" class="sp-more-link">Ujian</router-link>
          <router-link to="/student/ebooks" class="sp-more-link">Ebook</router-link>
          <router-link to="/student/ekstrakurikuler" class="sp-more-link">Ekskul</router-link>
          <router-link to="/student/konseling" class="sp-more-link">Konseling</router-link>
          <router-link to="/student/uks" class="sp-more-link">UKS</router-link>
          <router-link v-if="isVocational" to="/student/pkl" class="sp-more-link">PKL</router-link>
        </div>
      </section>
    </div></template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getActiveInstitutionLevel, isVocationalLevel } from '@/utils/institution'
import { gradeBookApi } from '@/api/gradeBook'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { violationApi, achievementApi, studentPointApi } from '@/api/violation'
import { semesterApi } from '@/api/semester'
import { academicCalendarApi } from '@/api/academicCalendar'
import { studentAttendanceApi } from '@/api/attendance'
import { studentFinanceApi } from '@/api/finance'
import { formatRp } from '@/views/Keuangan/keuanganConstants'

const authStore = useAuthStore()

const studentId = computed(() => authStore.user?.student_profile?.id)
const studentName = computed(() => authStore.user?.name || 'Siswa')
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return 'Selamat pagi'
  if (hour >= 11 && hour < 15) return 'Selamat siang'
  if (hour >= 15 && hour < 18) return 'Selamat sore'
  return 'Selamat malam'
})
const institutionName = computed(() => authStore.user?.institution?.name || null)
const isVocational = computed(() => isVocationalLevel(getActiveInstitutionLevel(authStore)))
const classInfo = computed(() => {
  const sp = authStore.user?.student_profile
  if (!sp) return null
  const cls = sp.class_name || sp.class
  return cls ? `Kelas ${cls}` : null
})

const loadingPoints = ref(false)
const loadingSchedule = ref(false)
const loadingGrades = ref(false)
const loadingAttendance = ref(false)
const attendances = ref([])
const upcomingEvents = ref([])
const pointsSummary = ref(null)
const financeSummary = ref(null)
const violationsList = ref([])
const achievementsList = ref([])
const scheduleAll = ref([])
const grades = ref([])
const activeSemester = ref(null)

function todayScheduleDay() {
  const js = new Date().getDay()
  return js === 0 ? 7 : js
}

const scheduleToday = computed(() => {
  const day = todayScheduleDay()
  return scheduleAll.value
    .filter((s) => Number(s.day_of_week) === day)
    .sort((a, b) => (a.period || 0) - (b.period || 0))
})

const scheduleTodayMessage = 'Tidak ada jadwal untuk hari ini.'

function parseTodayTime(t) {
  if (!t) return null
  const [h, m] = String(t).split(':').map((n) => Number(n))
  if (Number.isNaN(h)) return null
  const d = new Date()
  d.setHours(h, Number.isNaN(m) ? 0 : m, 0, 0)
  return d
}

function isCurrentSlot(slot) {
  const start = parseTodayTime(slot.start_time)
  const end = parseTodayTime(slot.end_time)
  if (!start || !end) return false
  const now = new Date()
  return now >= start && now < end
}

const nextSlot = computed(() => {
  const current = scheduleToday.value.find((s) => isCurrentSlot(s))
  if (current) return { ...current, isCurrent: true }
  const now = new Date()
  const upcoming = scheduleToday.value.find((s) => {
    const start = parseTodayTime(s.start_time)
    return start && start > now
  })
  return upcoming ? { ...upcoming, isCurrent: false } : null
})

function localISODate(d = new Date()) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const todayRows = computed(() => {
  const today = localISODate()
  return attendances.value.filter((row) => String(row.date || '').slice(0, 10) === today)
})

const todayAlphaCount = computed(() => todayRows.value.filter((r) => String(r.status).toLowerCase() === 'alpha').length)

const todayAttendanceLabel = computed(() => {
  if (!todayRows.value.length) return '—'
  if (todayAlphaCount.value) return `${todayAlphaCount.value} alpha`
  const hadir = todayRows.value.filter((r) => String(r.status).toLowerCase() === 'hadir').length
  return `${hadir}/${todayRows.value.length} hadir`
})

const todayAttendanceHint = computed(() => {
  if (!todayRows.value.length) return 'Belum ada absen tercatat'
  return 'Dari jurnal mengajar hari ini'
})

const hasOutstanding = computed(() => Number(financeSummary.value?.outstanding || 0) > 0)
const outstandingLabel = computed(() => formatRp(financeSummary.value?.outstanding))

const untuntasGrades = computed(() => grades.value.filter((g) => g.is_tuntas === false))
const previewGrades = computed(() => {
  if (untuntasGrades.value.length) return untuntasGrades.value.slice(0, 6)
  return grades.value.slice(0, 4)
})

const recentNotes = computed(() => {
  const violations = violationsList.value.slice(0, 3).map((v) => ({
    key: `v-${v.id}`,
    kind: 'v',
    title: v.violation_type?.name || v.description || '-',
    date: v.violation_date || v.date,
  }))
  const achievements = achievementsList.value.slice(0, 3).map((a) => ({
    key: `a-${a.id}`,
    kind: 'a',
    title: a.achievement_type?.name || a.description || '-',
    date: a.achievement_date || a.date,
  }))
  return [...violations, ...achievements]
    .sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0))
    .slice(0, 4)
})

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadActiveSemester() {
  try {
    const res = await semesterApi.getActive()
    const data = res.data?.data ?? res.data
    activeSemester.value = Array.isArray(data) ? data[0] : data
  } catch {
    activeSemester.value = null
  }
}

async function loadPoints() {
  if (!studentId.value) return
  loadingPoints.value = true
  try {
    const res = await studentPointApi.getSummary(studentId.value)
    pointsSummary.value = res.data?.data ?? res.data ?? null
  } catch {
    pointsSummary.value = null
  } finally {
    loadingPoints.value = false
  }
}

async function loadViolations() {
  if (!studentId.value) return
  try {
    const res = await violationApi.getByStudent(studentId.value, { per_page: 8 })
    const list = res.data?.data ?? res.data ?? []
    violationsList.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    violationsList.value = []
  }
}

async function loadAchievements() {
  if (!studentId.value) return
  try {
    const res = await achievementApi.getByStudent(studentId.value, { per_page: 8 })
    const list = res.data?.data ?? res.data ?? []
    achievementsList.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    achievementsList.value = []
  }
}

async function loadSchedule() {
  const classId = authStore.user?.student_profile?.class_id
  if (!classId || !activeSemester.value?.id) {
    loadingSchedule.value = false
    return
  }
  loadingSchedule.value = true
  try {
    const res = await lessonScheduleApi.getByClass(classId, { semester_id: activeSemester.value.id })
    const raw = res.data?.data ?? res.data ?? {}
    const list = raw.schedules ?? (Array.isArray(raw) ? raw : [])
    scheduleAll.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    scheduleAll.value = []
  } finally {
    loadingSchedule.value = false
  }
}

async function loadAttendance() {
  if (!studentId.value) return
  loadingAttendance.value = true
  try {
    const params = {}
    if (activeSemester.value?.id) params.semester_id = activeSemester.value.id
    const res = await studentAttendanceApi.getMy(params)
    const list = res.data?.data ?? res.data ?? []
    attendances.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    attendances.value = []
  } finally {
    loadingAttendance.value = false
  }
}

async function loadGrades() {
  if (!studentId.value || !activeSemester.value?.id) return
  loadingGrades.value = true
  try {
    const res = await gradeBookApi.getByStudentSemester({
      student_id: studentId.value,
      semester_id: activeSemester.value.id
    })
    const list = res.data?.data ?? res.data ?? []
    grades.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    grades.value = []
  } finally {
    loadingGrades.value = false
  }
}

async function loadFinance() {
  try {
    const res = await studentFinanceApi.getSummary()
    financeSummary.value = res.data?.data ?? null
  } catch {
    financeSummary.value = null
  }
}

async function loadUpcomingEvents() {
  try {
    const res = await academicCalendarApi.getUpcoming({ days: 14 })
    const list = res.data?.data ?? res.data ?? []
    const arr = Array.isArray(list) ? list : (list?.data ?? [])
    upcomingEvents.value = arr.slice(0, 3)
  } catch {
    upcomingEvents.value = []
  }
}

onMounted(async () => {
  await loadActiveSemester()
  loadPoints()
  loadViolations()
  loadAchievements()
  loadSchedule()
  loadGrades()
  loadAttendance()
  loadFinance()
  loadUpcomingEvents()
})
</script>
