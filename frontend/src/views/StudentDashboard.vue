<template>
  <div class="dashboard-page">
    <div class="dashboard-main">
      <Layout>
        <div class="sp-page dashboard">
          <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
            <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah. Silakan hubungi operator sekolah atau admin.
          </div>

          <section class="sp-hero">
            <h1>{{ greeting }}, {{ studentName }}!</h1>
            <p v-if="institutionName">{{ institutionName }}</p>
            <p v-else>Memuat data sekolah...</p>
            <div v-if="classInfo" class="sp-hero-meta">{{ classInfo }}</div>
          </section>

          <div class="sp-stats">
            <router-link to="/student/poin" class="sp-stat sp-stat--primary">
              <div class="sp-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </div>
              <div>
                <span class="sp-stat-label">Poin Saat Ini</span>
                <span class="sp-stat-value">{{ loadingPoints ? '…' : (pointsSummary?.total_points ?? '-') }}</span>
                <span class="sp-stat-hint">Lihat ringkasan poin</span>
              </div>
            </router-link>

            <router-link to="/student/pelanggaran-prestasi" class="sp-stat sp-stat--warn">
              <div class="sp-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </div>
              <div>
                <span class="sp-stat-label">Pelanggaran</span>
                <span class="sp-stat-value">{{ loadingViolations ? '…' : violationsCount }}</span>
                <span class="sp-stat-hint">Semester ini</span>
              </div>
            </router-link>

            <router-link to="/student/pelanggaran-prestasi" class="sp-stat sp-stat--ok">
              <div class="sp-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <span class="sp-stat-label">Prestasi</span>
                <span class="sp-stat-value">{{ loadingAchievements ? '…' : achievementsCount }}</span>
                <span class="sp-stat-hint">Semester ini</span>
              </div>
            </router-link>
          </div>

          <div v-if="attendanceChart || gradesChart" class="charts-grid">
            <AppChart title="Kehadiran semester ini" type="doughnut" :chart-data="attendanceChart" />
            <AppChart title="Nilai vs KKM" type="bar" :chart-data="gradesChart" :options="gradesChartOptions" />
          </div>

          <section class="sp-panel">
            <div class="sp-panel-header">
              <h2 class="sp-panel-title">Aksi Cepat</h2>
            </div>
            <div class="sp-actions-grid">
              <router-link to="/student/jadwal" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Jadwal</span>
              </router-link>
              <router-link to="/student/absensi" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Absensi</span>
              </router-link>
              <router-link to="/student/nilai" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 12V19C21 20.1 20.1 21 19 21H5C3.9 21 3 20.1 3 19V5C3 3.9 3.9 3 5 3H16" stroke="currentColor" stroke-width="2"/></svg>
                <span>Nilai</span>
              </router-link>
              <router-link to="/ujian-ikuti" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12H15M9 16H15M17 21H7C5.9 21 5 20.1 5 19V5C5 3.9 5.9 3 7 3H13L19 9V19C19 20.1 18.1 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Ujian</span>
              </router-link>
              <router-link to="/student/keuangan" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 1V23M17 5H9.5C8.1 5 7 6.1 7 7.5C7 8.9 8.1 10 9.5 10H14.5C15.9 10 17 11.1 17 12.5C17 13.9 15.9 15 14.5 15H7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Tagihan</span>
              </router-link>
              <router-link v-if="isVocational" to="/student/pkl" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V8l6-3v16M11 21V11h4l4 3v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>PKL</span>
              </router-link>
              <router-link to="/student/ebooks" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2"/><path d="M6.5 2H20V22H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2Z" stroke="currentColor" stroke-width="2"/></svg>
                <span>Ebook</span>
              </router-link>
              <router-link to="/student/konseling" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 15C21 16.1 20.1 17 19 17H7L3 21V5C3 3.9 3.9 3 5 3H19C20.1 3 21 3.9 21 5V15Z" stroke="currentColor" stroke-width="2"/></svg>
                <span>Konseling</span>
              </router-link>
              <router-link to="/student/uks" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2"/></svg>
                <span>UKS</span>
              </router-link>
              <router-link to="/student/ekstrakurikuler" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M17 21V19C17 16.8 15.2 15 13 15H5C2.8 15 1 16.8 1 19V21" stroke="currentColor" stroke-width="2"/></svg>
                <span>Ekskul</span>
              </router-link>
              <router-link to="/student/profil" class="sp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20C4 16.7 7.1 14 12 14C16.9 14 20 16.7 20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Profil</span>
              </router-link>
            </div>
          </section>

          <section v-if="upcomingEvents.length" class="sp-panel">
            <div class="sp-panel-header">
              <h2 class="sp-panel-title">Event Mendatang</h2>
            </div>
            <div class="sp-list">
              <div v-for="ev in upcomingEvents" :key="ev.id" class="sp-list-item sp-list-item--info">
                <span class="sp-list-title">{{ ev.title }}</span>
                <span class="sp-list-meta">{{ formatDate(ev.start_date) }}</span>
                <span v-if="ev.event_type" class="sp-chip">{{ ev.event_type }}</span>
              </div>
            </div>
          </section>

          <div class="dashboard-grid">
            <section id="jadwal-hari-ini" class="sp-panel">
              <div class="sp-panel-header">
                <h2 class="sp-panel-title">Jadwal Hari Ini</h2>
                <router-link to="/student/jadwal" class="sp-panel-link">Lihat semua</router-link>
              </div>
              <div v-if="loadingSchedule" class="sp-loading"><p>Memuat jadwal...</p></div>
              <div v-else-if="scheduleToday.length" class="sp-list">
                <div v-for="slot in scheduleToday" :key="slot.id" class="sp-list-item schedule-row">
                  <span class="sp-badge sp-badge--ok">Jam {{ slot.period }}</span>
                  <span class="sp-list-title">{{ slot.subject?.name || '-' }}</span>
                  <span class="sp-list-meta">{{ slot.start_time }}–{{ slot.end_time }}</span>
                  <span class="sp-list-meta">{{ slot.room?.name || '-' }}</span>
                </div>
              </div>
              <div v-else class="sp-empty">
                <p class="sp-empty-desc">{{ scheduleTodayMessage }}</p>
              </div>
            </section>

            <section id="nilai" class="sp-panel">
              <div class="sp-panel-header">
                <h2 class="sp-panel-title">Nilai Semester Aktif</h2>
                <router-link to="/student/nilai" class="sp-panel-link">Lihat semua</router-link>
              </div>
              <div v-if="loadingGrades" class="sp-loading"><p>Memuat nilai...</p></div>
              <div v-else-if="grades.length" class="sp-table-wrap">
                <table class="sp-table">
                  <thead>
                    <tr>
                      <th>Mapel</th>
                      <th>Nilai</th>
                      <th>KKM</th>
                      <th>Predikat</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="g in grades" :key="g.id || g.subject_id">
                      <td>{{ g.subject?.name || '-' }}</td>
                      <td>
                        <strong :class="g.is_tuntas === false ? 'sp-score-warn' : (g.is_tuntas === true ? 'sp-score-ok' : '')">
                          {{ g.nilai_akhir ?? g.value ?? '-' }}
                        </strong>
                      </td>
                      <td>{{ g.kkm ?? '—' }}</td>
                      <td>{{ g.predicate || '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="sp-empty">
                <p class="sp-empty-desc">Belum ada nilai untuk semester aktif.</p>
              </div>
            </section>
          </div>

          <section id="pelanggaran-prestasi" class="sp-panel">
            <div class="sp-panel-header">
              <h2 class="sp-panel-title">Pelanggaran & Prestasi Terbaru</h2>
              <router-link to="/student/pelanggaran-prestasi" class="sp-panel-link">Lihat semua</router-link>
            </div>
            <div v-if="loadingViolations" class="sp-loading"><p>Memuat data...</p></div>
            <div v-else-if="recentViolations.length || recentAchievements.length" class="sp-list">
              <div v-for="v in recentViolations" :key="'v-' + v.id" class="sp-list-item sp-list-item--danger">
                <span class="sp-list-kicker">Pelanggaran</span>
                <span class="sp-list-title">{{ v.violation_type?.name || v.description || '-' }}</span>
                <span class="sp-list-meta">{{ formatDate(v.violation_date || v.date) }}</span>
              </div>
              <div v-for="a in recentAchievements" :key="'a-' + a.id" class="sp-list-item sp-list-item--ok">
                <span class="sp-list-kicker">Prestasi</span>
                <span class="sp-list-title">{{ a.achievement_type?.name || a.description || '-' }}</span>
                <span class="sp-list-meta">{{ formatDate(a.achievement_date || a.date) }}</span>
              </div>
            </div>
            <div v-else class="sp-empty">
              <p class="sp-empty-desc">Belum ada catatan pelanggaran atau prestasi.</p>
            </div>
          </section>

          <div class="dashboard-grid">
            <section id="konseling" class="sp-panel">
              <div class="sp-panel-header">
                <h2 class="sp-panel-title">Konseling</h2>
                <router-link to="/student/konseling" class="sp-panel-link">Lihat semua</router-link>
              </div>
              <div v-if="loadingCounseling" class="sp-loading"><p>Memuat data...</p></div>
              <div v-else-if="counselingSessions.length" class="sp-list">
                <div v-for="c in counselingSessions" :key="c.id" class="sp-list-item">
                  <span class="sp-list-title">{{ c.counseling_type?.name || 'Konseling' }}</span>
                  <span class="sp-list-meta">{{ formatDate(c.session_date || c.scheduled_at || c.date) }}</span>
                  <span class="sp-badge sp-badge--pending">{{ c.status || '-' }}</span>
                </div>
              </div>
              <div v-else class="sp-empty">
                <p class="sp-empty-desc">Belum ada jadwal konseling.</p>
              </div>
            </section>

            <section id="ekstrakurikuler" class="sp-panel">
              <div class="sp-panel-header">
                <h2 class="sp-panel-title">Ekstrakurikuler</h2>
                <router-link to="/student/ekstrakurikuler" class="sp-panel-link">Lihat semua</router-link>
              </div>
              <div v-if="loadingExtracurricular" class="sp-loading"><p>Memuat data...</p></div>
              <div v-else-if="extracurriculars.length" class="sp-list">
                <div v-for="e in extracurriculars" :key="e.id" class="sp-list-item">
                  <span class="sp-list-title">{{ e.name }}</span>
                  <span v-if="e.supervisor?.name" class="sp-list-meta">Pembina: {{ e.supervisor.name }}</span>
                </div>
              </div>
              <div v-else class="sp-empty">
                <p class="sp-empty-desc">Belum terdaftar di ekstrakurikuler.</p>
              </div>
            </section>
          </div>
        </div>
      </Layout>
    </div>
    <HelpSidebar />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import HelpSidebar from '@/components/HelpSidebar.vue'
import AppChart from '@/components/AppChart.vue'
import { useAuthStore } from '@/stores/auth'
import { getActiveInstitutionLevel, isVocationalLevel } from '@/utils/institution'
import { gradeBookApi } from '@/api/gradeBook'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { violationApi, achievementApi, studentPointApi } from '@/api/violation'
import { counselingApi } from '@/api/counseling'
import { extracurricularApi } from '@/api/extracurricular'
import { semesterApi } from '@/api/semester'
import { academicCalendarApi } from '@/api/academicCalendar'
import { studentAttendanceApi } from '@/api/attendance'
import { doughnutFromCounts, countStatuses, chartOptionsBar } from '@/composables/useChart'

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
const loadingViolations = ref(false)
const loadingAchievements = ref(false)
const loadingSchedule = ref(false)
const loadingGrades = ref(false)
const loadingCounseling = ref(false)
const loadingExtracurricular = ref(false)
const attendances = ref([])

const upcomingEvents = ref([])

const pointsSummary = ref(null)
const violationsList = ref([])
const achievementsList = ref([])
const scheduleAll = ref([])
const grades = ref([])
const counselingSessions = ref([])
const extracurriculars = ref([])
const activeSemester = ref(null)

const todayDayOfWeek = (() => {
  const d = new Date().getDay()
  return d >= 1 && d <= 5 ? d : null
})()

const scheduleToday = computed(() => {
  if (!scheduleAll.value.length || todayDayOfWeek == null) return []
  return scheduleAll.value
    .filter(s => s.day_of_week === todayDayOfWeek)
    .sort((a, b) => (a.period || 0) - (b.period || 0))
})

const scheduleTodayMessage = computed(() => {
  if (todayDayOfWeek == null) return 'Hari ini libur (akhir pekan).'
  return 'Tidak ada jadwal untuk hari ini.'
})

const violationsCount = computed(() => violationsList.value.length)
const achievementsCount = computed(() => achievementsList.value.length)
const recentViolations = computed(() => violationsList.value.slice(0, 5))
const recentAchievements = computed(() => achievementsList.value.slice(0, 5))

const attendanceChart = computed(() => doughnutFromCounts(countStatuses(attendances.value)))
const gradesChart = computed(() => {
  const list = (grades.value || []).filter((g) => g.nilai_akhir != null || g.value != null)
  if (!list.length) return null
  return {
    labels: list.map((g) => g.subject?.name || g.subject_name || '—'),
    datasets: [
      {
        label: 'Nilai',
        data: list.map((g) => Number(g.nilai_akhir ?? g.value ?? 0)),
        backgroundColor: '#059669',
        borderRadius: 4,
        maxBarThickness: 28,
      },
      {
        label: 'KKM',
        data: list.map((g) => Number(g.kkm ?? 0)),
        backgroundColor: '#94a3b8',
        borderRadius: 4,
        maxBarThickness: 28,
      },
    ],
  }
})
const gradesChartOptions = { ...chartOptionsBar, plugins: { legend: { position: 'bottom' } } }

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
  loadingViolations.value = true
  try {
    const res = await violationApi.getByStudent(studentId.value, { per_page: 50 })
    const list = res.data?.data ?? res.data ?? []
    violationsList.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    violationsList.value = []
  } finally {
    loadingViolations.value = false
  }
}

async function loadAchievements() {
  if (!studentId.value) return
  loadingAchievements.value = true
  try {
    const res = await achievementApi.getByStudent(studentId.value, { per_page: 50 })
    const list = res.data?.data ?? res.data ?? []
    achievementsList.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    achievementsList.value = []
  } finally {
    loadingAchievements.value = false
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
  try {
    const params = {}
    if (activeSemester.value?.id) params.semester_id = activeSemester.value.id
    const res = await studentAttendanceApi.getMy(params)
    const list = res.data?.data ?? res.data ?? []
    attendances.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    attendances.value = []
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

async function loadCounseling() {
  if (!studentId.value) return
  loadingCounseling.value = true
  try {
    const res = await counselingApi.getByStudent(studentId.value, { per_page: 10 })
    const list = res.data?.data ?? res.data ?? []
    counselingSessions.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    counselingSessions.value = []
  } finally {
    loadingCounseling.value = false
  }
}

async function loadExtracurricular() {
  if (!studentId.value) return
  loadingExtracurricular.value = true
  try {
    const res = await extracurricularApi.getByStudent(studentId.value, { per_page: 20 })
    const list = res.data?.data ?? res.data ?? []
    const raw = Array.isArray(list) ? list : (list?.data ?? [])
    extracurriculars.value = raw.map(e => e.extracurricular || e).filter(Boolean)
  } catch {
    extracurriculars.value = []
  } finally {
    loadingExtracurricular.value = false
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
  loadCounseling()
  loadExtracurricular()
  loadUpcomingEvents()
})

async function loadUpcomingEvents() {
  try {
    const res = await academicCalendarApi.getUpcoming({ days: 14 })
    const list = res.data?.data ?? res.data ?? []
    const arr = Array.isArray(list) ? list : (list?.data ?? [])
    upcomingEvents.value = arr.slice(0, 5)
  } catch {
    upcomingEvents.value = []
  }
}
</script>

<style scoped>
.dashboard-page {
  display: flex;
  width: 100%;
  min-height: 100vh;
}

.dashboard-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 900px) {
  .charts-grid { grid-template-columns: 1fr; }
}

.sp-panel:target {
  scroll-margin-top: 16px;
}

.sp-table {
  min-width: 0;
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
