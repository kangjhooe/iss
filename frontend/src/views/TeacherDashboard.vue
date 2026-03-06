<template>
  <Layout>
    <div class="dashboard">
      <div class="welcome-section">
        <div class="welcome-content">
          <h1>Selamat Datang, {{ teacherName }}!</h1>
          <p v-if="teacher?.institution?.name">{{ teacher.institution.name }}</p>
          <p v-else class="loading">Memuat data...</p>
          <router-link to="/teacher/profile" class="profile-link">Profil / Lengkapi data</router-link>
        </div>
        <div v-if="activeAcademicYear" class="welcome-meta">
          Tahun Ajaran Aktif: {{ activeAcademicYear.code || activeAcademicYear.name }}
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card stat-card-primary">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Kelas</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(summary.total_classes || 0) }}</p>
            <span class="stat-label">Kelas Diampu</span>
          </div>
        </div>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Siswa</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(summary.total_students || 0) }}</p>
            <span class="stat-label">Siswa Pada Kelas</span>
          </div>
        </div>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 20V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18 20V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 20V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Mata Pelajaran</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ teacher?.subject || '-' }}</p>
            <span class="stat-label">Bidang Ajar</span>
          </div>
        </div>
      </div>

      <div v-if="canAccessModule('teaching_journal') || canAccessModule('grade_book')" class="quick-actions-section">
        <h2 class="section-title">Aksi Cepat</h2>
        <div class="quick-actions-grid">
          <router-link v-if="canAccessModule('teaching_journal')" to="/teaching-journal" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 6.25278V19.2528M12 6.25278C10.8321 5.47686 9.24649 5 7.5 5C5.75351 5 4.16789 5.47686 3 6.25278V19.2528C4.16789 18.4769 5.75351 18 7.5 18C9.24649 18 10.8321 18.4769 12 19.2528" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Jurnal Mengajar</span>
            <span v-if="jurnalThisWeekCount !== undefined" class="quick-action-badge">{{ jurnalThisWeekCount }} minggu ini</span>
          </router-link>
          <router-link v-if="canAccessModule('grade_book')" to="/grade-book" class="quick-action-card">
            <svg class="quick-action-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Buku Nilai</span>
            <span v-if="gradesPending.length" class="quick-action-badge warning">{{ gradesPending.length }} belum diisi</span>
          </router-link>
        </div>
        <div v-if="gradesPending.length > 0" class="grades-pending-list">
          <h4 class="grades-pending-title">Nilai belum diisi (semester aktif)</h4>
          <ul>
            <li v-for="p in gradesPending" :key="p.class_id + '-' + p.subject_id">
              <router-link :to="`/grade-book?semester_id=${p.semester_id}&class_id=${p.class_id}&subject_id=${p.subject_id}`">
                {{ p.class_name }} – {{ p.subject_name }}
              </router-link>
            </li>
          </ul>
        </div>
      </div>

      <div class="classes-section">
        <div class="section-header">
          <h2>Kelas yang Diampu</h2>
          <router-link to="/class" class="link-button">Lihat Semua</router-link>
        </div>

        <div v-if="loading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat data kelas...</p>
        </div>

        <div v-else-if="classes.length" class="classes-grid">
          <div v-for="classItem in classes" :key="classItem.id" class="class-card">
            <div class="class-title">{{ classItem.name }}</div>
            <div class="class-meta">
              <span>Kelas {{ classItem.grade || '-' }}</span>
              <span class="divider">•</span>
              <span>{{ classItem.academic_year || '-' }}</span>
            </div>
            <div class="class-stats">
              {{ formatNumber(classItem.students_count || 0) }} siswa
            </div>
            <div class="class-room">
              Ruangan: {{ classItem.room?.name || '-' }}
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Belum ada kelas</h3>
          <p>Kelas yang diampu akan tampil di sini</p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { teacherApi } from '@/api/teacher'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const canAccessModule = (moduleKey) => {
  const role = authStore.user?.role
  if (!role) return false
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  return (authStore.user?.permissions || []).includes(moduleKey)
}
const loading = ref(true)
const dashboardData = ref({
  teacher: null,
  summary: { total_classes: 0, total_students: 0 },
  classes: [],
  active_academic_year: null
})

const teacher = computed(() => dashboardData.value.teacher)
const summary = computed(() => dashboardData.value.summary || {})
const classes = computed(() => dashboardData.value.classes || [])
const activeAcademicYear = computed(() => dashboardData.value.active_academic_year)
const jurnalThisWeekCount = computed(() => dashboardData.value.jurnal_this_week_count ?? 0)
const gradesPending = computed(() => dashboardData.value.grades_pending || [])

const teacherName = computed(() => {
  return teacher.value?.name || authStore.user?.name || 'Guru'
})

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num)

const loadDashboard = async () => {
  loading.value = true
  try {
    const response = await teacherApi.getDashboard()
    dashboardData.value = response.data?.data || dashboardData.value
  } catch (error) {
    console.error('Error loading teacher dashboard:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
.dashboard {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.welcome-section {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  border-radius: 12px;
  padding: 24px 32px;
  margin-bottom: 24px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
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

.welcome-content .profile-link {
  display: inline-block;
  margin-top: 10px;
  font-size: 14px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.95);
  text-decoration: none;
  padding: 8px 14px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  transition: background 0.2s;
}
.welcome-content .profile-link:hover {
  background: rgba(255, 255, 255, 0.3);
}

.welcome-meta {
  font-size: 13px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.16);
  border-radius: 10px;
  font-weight: 600;
  text-align: center;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(22, 163, 74, 0.12);
  color: #16a34a;
}

.stat-card-success .stat-icon {
  background: rgba(5, 150, 105, 0.12);
  color: #059669;
}

.stat-card-warning .stat-icon {
  background: rgba(245, 158, 11, 0.12);
  color: #f59e0b;
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
  letter-spacing: -0.5px;
  line-height: 1.2;
  word-break: break-word;
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
  color: #16a34a;
}

.quick-action-badge {
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
  margin-left: auto;
  padding-left: 8px;
}

.quick-action-badge.warning {
  color: #dc2626;
}

.grades-pending-list {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.grades-pending-title {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  margin: 0 0 8px 0;
}

.grades-pending-list ul {
  margin: 0;
  padding: 0;
  list-style: none;
}

.grades-pending-list li {
  margin-bottom: 6px;
}

.grades-pending-list a {
  color: #059669;
  text-decoration: none;
  font-size: 14px;
}

.grades-pending-list a:hover {
  text-decoration: underline;
}

.classes-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.link-button {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: #16a34a;
  background: rgba(22, 163, 74, 0.12);
  padding: 8px 12px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.link-button:hover {
  background: rgba(22, 163, 74, 0.2);
}

.classes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.class-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  background: #f8fafc;
}

.class-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 6px;
  color: #0f172a;
}

.class-meta {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}

.class-meta .divider {
  color: #cbd5e1;
}

.class-stats {
  font-size: 14px;
  font-weight: 600;
  color: #16a34a;
  margin-bottom: 6px;
}

.class-room {
  font-size: 12px;
  color: #94a3b8;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 24px;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 32px 16px;
  color: #94a3b8;
}

.empty-state h3 {
  margin: 12px 0 6px;
  font-size: 16px;
  color: #334155;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
}

@media (max-width: 768px) {
  .welcome-section {
    padding: 20px 24px;
    flex-direction: column;
    align-items: flex-start;
  }

  .welcome-content h1 {
    font-size: 20px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .classes-section {
    padding: 20px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>
