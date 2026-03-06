<template>
  <div class="dashboard-page">
    <div class="dashboard-main">
      <Layout>
        <div class="dashboard">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="alert alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah. Silakan hubungi operator sekolah atau admin.
      </div>

      <div class="welcome-section">
        <div class="welcome-content">
          <h1>{{ greeting }}, {{ studentName }}!</h1>
          <p v-if="institutionName">{{ institutionName }}</p>
          <p v-else class="loading">Memuat data...</p>
          <div v-if="classInfo" class="welcome-meta">
            {{ classInfo }}
          </div>
        </div>
      </div>

      <div class="stats-grid">
        <router-link to="/student/poin" class="stat-card stat-card-primary stat-card-link">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Poin Saat Ini</h3>
            <p v-if="loadingPoints" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ pointsSummary?.total_points ?? '-' }}</p>
            <span class="stat-label">Total poin · Klik untuk detail</span>
          </div>
        </router-link>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Pelanggaran</h3>
            <p v-if="loadingViolations" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ violationsCount }}</p>
            <span class="stat-label">Semester ini</span>
          </div>
        </div>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Prestasi</h3>
            <p v-if="loadingAchievements" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ achievementsCount }}</p>
            <span class="stat-label">Semester ini</span>
          </div>
        </div>
      </div>

      <div class="quick-actions-section">
        <h2 class="section-title">Aksi Cepat</h2>
        <div class="quick-actions-grid">
          <router-link to="/student/jadwal" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Jadwal Pelajaran</span>
          </router-link>
          <router-link to="/student/nilai" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Nilai Saya</span>
          </router-link>
          <router-link to="/ujian-ikuti" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Ikuti Ujian</span>
          </router-link>
          <router-link to="/student/pelanggaran-prestasi" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Pelanggaran & Prestasi</span>
          </router-link>
          <router-link to="/student/konseling" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Konseling</span>
          </router-link>
          <router-link to="/student/ekstrakurikuler" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Ekstrakurikuler</span>
          </router-link>
        </div>
      </div>

      <section v-if="upcomingEvents.length" class="content-section">
        <div class="section-header">
          <h2>Event Mendatang</h2>
        </div>
        <div class="events-list">
          <div v-for="ev in upcomingEvents" :key="ev.id" class="event-item">
            <span class="event-title">{{ ev.title }}</span>
            <span class="event-date">{{ formatDate(ev.start_date) }}</span>
            <span v-if="ev.event_type" class="event-type">{{ ev.event_type }}</span>
          </div>
        </div>
      </section>

      <section id="jadwal-hari-ini" class="content-section">
        <div class="section-header">
          <h2>Jadwal Hari Ini</h2>
          <router-link to="/student/jadwal" class="section-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingSchedule" class="loading-state">
          <p>Memuat jadwal...</p>
        </div>
        <div v-else-if="scheduleToday.length" class="schedule-list">
          <div v-for="slot in scheduleToday" :key="slot.id" class="schedule-item">
            <span class="schedule-period">Jam {{ slot.period }}</span>
            <span class="schedule-subject">{{ slot.subject?.name || '-' }}</span>
            <span class="schedule-time">{{ slot.start_time }} - {{ slot.end_time }}</span>
            <span class="schedule-room">{{ slot.room?.name || '-' }}</span>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>{{ scheduleTodayMessage }}</p>
        </div>
      </section>

      <section id="nilai" class="content-section">
        <div class="section-header">
          <h2>Nilai Semester Aktif</h2>
          <router-link to="/student/nilai" class="section-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingGrades" class="loading-state">
          <p>Memuat nilai...</p>
        </div>
        <div v-else-if="grades.length" class="grades-table-wrap">
          <table class="grades-table">
            <thead>
              <tr>
                <th>Mata Pelajaran</th>
                <th>Nilai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in grades" :key="g.id || g.subject_id">
                <td>{{ g.subject?.name || '-' }}</td>
                <td>{{ g.nilai_akhir ?? g.value ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <p>Belum ada nilai untuk semester aktif.</p>
        </div>
      </section>

      <section id="pelanggaran-prestasi" class="content-section">
        <div class="section-header">
          <h2>Pelanggaran & Prestasi Terbaru</h2>
          <router-link to="/student/pelanggaran-prestasi" class="section-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingViolations" class="loading-state">
          <p>Memuat data...</p>
        </div>
        <div v-else-if="recentViolations.length || recentAchievements.length" class="violations-list">
          <div v-for="v in recentViolations" :key="'v-' + v.id" class="violation-item violation">
            <span class="violation-type">Pelanggaran</span>
            <span class="violation-desc">{{ v.violation_type?.name || v.description || '-' }}</span>
            <span class="violation-date">{{ formatDate(v.violation_date || v.date) }}</span>
          </div>
          <div v-for="a in recentAchievements" :key="'a-' + a.id" class="violation-item achievement">
            <span class="violation-type">Prestasi</span>
            <span class="violation-desc">{{ a.achievement_type?.name || a.description || '-' }}</span>
            <span class="violation-date">{{ formatDate(a.achievement_date || a.date) }}</span>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>Belum ada catatan pelanggaran atau prestasi.</p>
        </div>
      </section>

      <section id="konseling" class="content-section">
        <div class="section-header">
          <h2>Konseling</h2>
          <router-link to="/student/konseling" class="section-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingCounseling" class="loading-state">
          <p>Memuat data konseling...</p>
        </div>
        <div v-else-if="counselingSessions.length" class="counseling-list">
          <div v-for="c in counselingSessions" :key="c.id" class="counseling-item">
            <span class="counseling-type">{{ c.counseling_type?.name || 'Konseling' }}</span>
            <span class="counseling-date">{{ formatDate(c.session_date || c.scheduled_at || c.date) }}</span>
            <span class="counseling-status">{{ c.status || '-' }}</span>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>Belum ada jadwal konseling.</p>
        </div>
      </section>

      <section id="ekstrakurikuler" class="content-section">
        <div class="section-header">
          <h2>Ekstrakurikuler</h2>
          <router-link to="/student/ekstrakurikuler" class="section-link">Lihat semua</router-link>
        </div>
        <div v-if="loadingExtracurricular" class="loading-state">
          <p>Memuat data ekstrakurikuler...</p>
        </div>
        <div v-else-if="extracurriculars.length" class="extracurricular-list">
          <div v-for="e in extracurriculars" :key="e.id" class="extracurricular-item">
            <span class="extracurricular-name">{{ e.name }}</span>
            <span v-if="e.supervisor?.name" class="extracurricular-supervisor">Pembina: {{ e.supervisor.name }}</span>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>Belum terdaftar di ekstrakurikuler.</p>
        </div>
      </section>
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
import { useAuthStore } from '@/stores/auth'
import { gradeBookApi } from '@/api/gradeBook'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { violationApi, achievementApi, studentPointApi } from '@/api/violation'
import { counselingApi } from '@/api/counseling'
import { extracurricularApi } from '@/api/extracurricular'
import { semesterApi } from '@/api/semester'
import { academicCalendarApi } from '@/api/academicCalendar'

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
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 24%, #f1f5f9 100%);
}

.dashboard-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.dashboard {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.welcome-section {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 12px;
  padding: 24px 32px;
  margin-bottom: 24px;
  color: white;
}

.welcome-content h1 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.welcome-content p {
  font-size: 15px;
  opacity: 0.9;
  margin: 0;
  font-weight: 400;
}

.welcome-content p.loading {
  opacity: 0.7;
  font-style: italic;
}

.welcome-meta {
  font-size: 13px;
  margin-top: 10px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  display: inline-block;
  font-weight: 600;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.stat-card-link {
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.stat-card-link:hover {
  border-color: #059669;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.15);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-card-primary .stat-icon {
  background: rgba(5, 150, 105, 0.12);
  color: #059669;
}

.stat-card-warning .stat-icon {
  background: rgba(245, 158, 11, 0.12);
  color: #f59e0b;
}

.stat-card-success .stat-icon {
  background: rgba(22, 163, 74, 0.12);
  color: #16a34a;
}

.stat-body {
  flex: 1;
  min-width: 0;
}

.stat-title {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  margin: 0 0 8px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  color: #0f172a;
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 4px 0;
  line-height: 1.2;
}

.loading-text {
  font-size: 14px;
  color: #94a3b8;
  font-weight: 400;
  font-style: italic;
}

.stat-label {
  color: #94a3b8;
  font-size: 12px;
  font-weight: 400;
  display: block;
  margin-top: 2px;
}

.quick-actions-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.section-title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px 0;
}

.quick-actions-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.quick-action-card {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  text-decoration: none;
  color: #0f172a;
  font-weight: 600;
  font-size: 14px;
  transition: background 0.2s, border-color 0.2s;
}

.quick-action-card:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.quick-action-icon {
  flex-shrink: 0;
  color: #059669;
}

.content-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.content-section:target {
  scroll-margin-top: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.section-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.section-link {
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 8px;
  background: rgba(5, 150, 105, 0.1);
  transition: background 0.2s;
}

.section-link:hover {
  background: rgba(5, 150, 105, 0.2);
}

.alert {
  padding: 14px 18px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  line-height: 1.5;
}

.alert-warning {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
}

.loading-state {
  padding: 24px;
  text-align: center;
  color: #64748b;
  font-size: 14px;
}

.empty-state {
  padding: 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.schedule-item {
  display: grid;
  grid-template-columns: 80px 1fr auto auto;
  gap: 12px;
  align-items: center;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.schedule-period {
  font-weight: 600;
  color: #64748b;
  font-size: 13px;
}

.schedule-subject {
  font-weight: 600;
  color: #0f172a;
}

.schedule-time, .schedule-room {
  font-size: 13px;
  color: #64748b;
}

.grades-table-wrap {
  overflow-x: auto;
}

.grades-table {
  width: 100%;
  border-collapse: collapse;
}

.grades-table th,
.grades-table td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.grades-table th {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.grades-table td {
  font-size: 14px;
  color: #0f172a;
}

.violations-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.violation-item {
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.violation-item.violation {
  background: #fef2f2;
  border-color: #fecaca;
}

.violation-item.achievement {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.violation-type {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
}

.violation-desc {
  flex: 1;
  font-weight: 500;
  color: #0f172a;
}

.violation-date {
  font-size: 12px;
  color: #64748b;
}

.counseling-list, .extracurricular-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.counseling-item, .extracurricular-item {
  padding: 12px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.event-item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 8px;
}

.event-title {
  font-weight: 600;
  color: #0f172a;
  flex: 1;
  min-width: 0;
}

.event-date {
  font-size: 13px;
  color: #0369a1;
}

.event-type {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
}

.counseling-type, .extracurricular-name {
  font-weight: 600;
  color: #0f172a;
}

.counseling-date, .counseling-status, .extracurricular-supervisor {
  font-size: 13px;
  color: #64748b;
}

@media (max-width: 768px) {
  .welcome-section {
    padding: 20px 24px;
  }

  .welcome-content h1 {
    font-size: 20px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .schedule-item {
    grid-template-columns: 1fr;
    gap: 4px;
  }
}
</style>
