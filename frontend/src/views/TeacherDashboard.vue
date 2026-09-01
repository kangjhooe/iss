<template>    <div class="dashboard">
      <!-- Welcome -->
      <div class="welcome-section">
        <div class="welcome-content">
          <h1>{{ greeting }}, {{ teacherName }}!</h1>
          <template v-if="displayInstitutionName">
            <span class="welcome-sep">·</span>
            <p class="welcome-inst">{{ displayInstitutionName }}</p>
          </template>
          <template v-else-if="loading">
            <span class="welcome-sep">·</span>
            <p class="loading">Memuat data...</p>
          </template>
          <template v-else-if="loadError">
            <span class="welcome-sep">·</span>
            <p class="loading">{{ loadError }}</p>
          </template>
          <div v-if="activeAcademicYear" class="academic-period">
            Tahun Ajaran {{ activeAcademicYear.code || activeAcademicYear.name }}
          </div>
        </div>
        <div class="welcome-actions">
          <router-link to="/teacher/profile" class="profile-link">Profil</router-link>
        </div>
      </div>

      <div v-if="loadError" class="error-banner" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" class="retry-btn" @click="loadDashboard">Coba lagi</button>
      </div>

      <!-- Piket alert -->
      <div v-if="piketToday?.is_on_duty" class="piket-card" role="status">
        <div class="piket-card-main">
          <div class="piket-badge">Piket Hari Ini</div>
          <h2 class="piket-title">Anda bertugas sebagai Guru Piket</h2>
          <p class="piket-meta">
            {{ piketToday.day_name }} · Shift {{ piketToday.schedule?.shift_label || piketToday.schedule?.shift || '-' }}
            <template v-if="piketToday.schedule?.start_time">
              · {{ piketToday.schedule.start_time }}{{ piketToday.schedule.end_time ? '–' + piketToday.schedule.end_time : '' }}
            </template>
          </p>
          <p v-if="!piketToday.has_log" class="piket-hint">Belum ada log kegiatan untuk hari ini.</p>
          <p v-else class="piket-hint ok">Log kegiatan: {{ piketLogStatusLabel }}</p>
        </div>
        <div class="piket-card-actions">
          <router-link
            v-if="canAccessPiket"
            to="/guru-piket?tab=monitor&action=new-incident"
            class="piket-btn primary"
          >
            Lapor Kejadian
          </router-link>
          <router-link
            v-if="canAccessPiket"
            to="/guru-piket"
            class="piket-btn"
          >
            Buka Guru Piket
          </router-link>
          <router-link
            v-if="canAccessModule('teacher_violation_report') || canAccessModule('teacher_appreciation')"
            to="/teacher-appreciation"
            class="piket-btn"
            title="Modul terpisah: poin apresiasi & pelanggaran guru"
          >
            Modul Poin Guru
          </router-link>
        </div>
      </div>

      <!-- Disposisi masuk (tanpa modul Persuratan penuh) -->
      <div v-if="pendingDispositions.length" class="disposition-inbox" role="status">
        <div class="disposition-inbox-head">
          <h2>Disposisi menunggu tindak lanjut</h2>
          <span class="disposition-inbox-count">{{ pendingDispositions.length }}</span>
        </div>
        <ul class="disposition-inbox-list">
          <li v-for="item in pendingDispositions" :key="item.id" class="disposition-inbox-item">
            <div class="disposition-inbox-body">
              <p class="disposition-inbox-subject">
                {{ item.correspondence?.subject || 'Surat' }}
                <span v-if="item.correspondence?.letter_number" class="disposition-inbox-no">
                  · {{ item.correspondence.letter_number }}
                </span>
              </p>
              <p class="disposition-inbox-meta">
                Dari {{ item.from_user?.name || '-' }}
              </p>
              <p class="disposition-inbox-instruction">{{ item.instruction }}</p>
            </div>
            <button
              type="button"
              class="disposition-inbox-btn"
              :disabled="completingDispositionId === item.id"
              @click="completeDisposition(item.id)"
            >
              {{ completingDispositionId === item.id ? 'Menyimpan…' : 'Selesai' }}
            </button>
          </li>
        </ul>
      </div>

      <!-- Stats -->
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
            <span class="stat-label">Kelas ajar + wali</span>
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
            <span class="stat-label">
              <template v-if="summary.homeroom_classes">{{ formatNumber(summary.homeroom_classes) }} kelas wali · </template>
              Semua kelas terkait
            </span>
          </div>
        </div>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M8 7H18M8 11H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Mata Pelajaran</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value subject-value">{{ teacher?.subject || '-' }}</p>
            <span class="stat-label">Bidang ajar</span>
          </div>
        </div>

        <router-link to="/teacher/poin" class="stat-card stat-card-points">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Poin Prestasi</h3>
            <p v-if="pointsLoading" class="stat-value loading-text">...</p>
            <p v-else class="stat-value">{{ formatNumber(myPoints?.total_points || 0) }}</p>
            <span class="stat-label">{{ myPoints?.rank ? `Peringkat #${myPoints.rank}` : 'Lihat detail →' }}</span>
          </div>
        </router-link>
      </div>

      <!-- Snapshot kelas wali -->
      <section v-if="homeroomClasses.length" class="wali-snapshot">
        <div class="section-header">
          <h2>Kelas Wali</h2>
          <div class="wali-snapshot-actions">
            <select
              v-if="homeroomClasses.length > 1"
              class="wali-class-select"
              :value="waliClassId"
              @change="onWaliClassChange($event.target.value)"
            >
              <option v-for="c in homeroomClasses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
            <router-link :to="waliHubLink" class="link-button">Buka hub wali</router-link>
          </div>
        </div>
        <p v-if="waliDashError" class="wali-snapshot-error" role="alert">
          {{ waliDashError }}
          <button type="button" class="retry-btn" @click="loadWaliSnapshot">Coba lagi</button>
        </p>
        <div v-else class="charts-grid">
          <AppChart
            title="Kehadiran hari ini"
            :subtitle="attendanceChartSubtitle"
            type="doughnut"
            :chart-data="attendanceChart"
            :options="attendanceChartOptions"
            :empty-text="waliDashLoading ? 'Memuat kehadiran…' : 'Belum ada data kehadiran.'"
          >
            <template #actions>
              <router-link :to="waliAbsensiLink" class="chart-link">Absensi →</router-link>
            </template>
          </AppChart>
          <AppChart
            title="Kondisi kelas"
            :subtitle="conditionChartSubtitle"
            type="bar"
            :chart-data="conditionChart"
            :options="conditionChartOptions"
            :empty-text="waliDashLoading ? 'Memuat kondisi kelas…' : 'Belum ada data kelas.'"
          >
            <template #actions>
              <router-link :to="waliHubLink" class="chart-link">Detail →</router-link>
            </template>
          </AppChart>
        </div>
      </section>

      <!-- Quick Actions -->
      <div v-if="quickActions.length" class="quick-actions">
        <div class="section-header">
          <h2>Aksi Cepat</h2>
          <span v-if="attentionCount" class="section-meta">{{ attentionCount }} perlu perhatian</span>
        </div>
        <div class="actions-grid">
          <router-link
            v-for="action in quickActions"
            :key="action.to + action.label"
            :to="action.to"
            class="action-card"
            :class="[`action-card-${action.tone || 'neutral'}`, { 'has-warning': action.warning }]"
          >
            <div
              class="action-icon"
              :class="`action-icon-${action.tone || 'neutral'}`"
              v-html="actionIconSvg(action.icon)"
            ></div>
            <div class="action-content">
              <h4>{{ action.label }}</h4>
              <span v-if="action.badge" class="action-badge" :class="{ warning: action.warning }">
                {{ action.badge }}
              </span>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Main content: classes + attention -->
      <div class="content-grid" :class="{ 'has-aside': showAttentionPanel }">
        <section class="classes-section">
          <div class="section-header">
            <h2>Kelas Saya</h2>
            <router-link v-if="canAccessModule('class')" to="/class" class="link-button">Kelola Kelas</router-link>
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

          <template v-else>
            <div v-if="homeroomClasses.length" class="classes-subsection">
              <h3 class="subsection-title">
                <span class="subsection-dot homeroom"></span>
                Kelas Wali
                <span class="subsection-count">{{ homeroomClasses.length }}</span>
              </h3>
              <div class="classes-grid">
                <div v-for="classItem in homeroomClasses" :key="'wali-' + classItem.id" class="class-card class-card-homeroom">
                  <div class="class-title-row">
                    <div class="class-title">{{ classItem.name }}</div>
                    <span class="homeroom-badge">Wali</span>
                  </div>
                  <div class="class-meta">
                    <span>Kelas {{ classItem.grade || '-' }}</span>
                    <span class="divider">·</span>
                    <span>{{ classItem.academic_year || '-' }}</span>
                  </div>
                  <div class="class-footer">
                    <div class="class-stats">
                      <strong>{{ formatNumber(classItem.students_count || 0) }}</strong> siswa
                    </div>
                    <div class="class-room" :title="classItem.room?.name || ''">
                      {{ classItem.room?.name || 'Tanpa ruangan' }}
                    </div>
                  </div>
                  <div class="class-card-actions">
                    <router-link
                      :to="{ path: '/teacher/wali', query: { class_id: classItem.id, panel: 'siswa' } }"
                      class="class-students-btn class-hub-btn"
                    >
                      Hub wali
                    </router-link>
                    <button
                      type="button"
                      class="class-students-btn"
                      @click="openStudentsModal(classItem)"
                    >
                      Lihat siswa
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="taughtOnlyClasses.length" class="classes-subsection">
              <h3 class="subsection-title">
                <span class="subsection-dot taught"></span>
                Kelas Ajar
                <span class="subsection-count">{{ taughtOnlyClasses.length }}</span>
              </h3>
              <div class="classes-grid">
                <div v-for="classItem in taughtOnlyClasses" :key="'ajar-' + classItem.id" class="class-card">
                  <div class="class-title-row">
                    <div class="class-title">{{ classItem.name }}</div>
                  </div>
                  <div class="class-meta">
                    <span>Kelas {{ classItem.grade || '-' }}</span>
                    <span class="divider">·</span>
                    <span>{{ classItem.academic_year || '-' }}</span>
                  </div>
                  <div class="class-footer">
                    <div class="class-stats">
                      <strong>{{ formatNumber(classItem.students_count || 0) }}</strong> siswa
                    </div>
                    <div class="class-room" :title="classItem.room?.name || ''">
                      {{ classItem.room?.name || 'Tanpa ruangan' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="!homeroomClasses.length && !taughtOnlyClasses.length" class="empty-state">
              <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h3>Belum ada kelas</h3>
              <p>Kelas wali atau jadwal mengajar akan tampil di sini</p>
            </div>
          </template>
        </section>

        <aside v-if="showAttentionPanel" class="attention-panel">
          <div class="section-header">
            <h2>Perlu Perhatian</h2>
          </div>

          <div v-if="canAccessModule('grade_book')" class="attention-block">
            <div class="attention-block-head">
              <h3>Nilai belum diisi</h3>
              <span class="attention-count" :class="{ warn: gradesPending.length }">{{ gradesPending.length }}</span>
            </div>
            <ul v-if="gradesPending.length" class="attention-list">
              <li v-for="p in gradesPending.slice(0, 6)" :key="p.class_id + '-' + p.subject_id">
                <router-link :to="`/grade-book?semester_id=${p.semester_id}&class_id=${p.class_id}&subject_id=${p.subject_id}`">
                  <span class="attention-main">{{ p.class_name }}</span>
                  <span class="attention-sub">{{ p.subject_name }}</span>
                </router-link>
              </li>
            </ul>
            <p v-else class="attention-empty">Semua nilai semester aktif sudah terisi.</p>
            <router-link v-if="gradesPending.length > 6" to="/grade-book" class="attention-more">
              Lihat semua ({{ gradesPending.length }}) →
            </router-link>
          </div>

          <div v-if="canAccessModule('teaching_journal')" class="attention-block">
            <div class="attention-block-head">
              <h3>Jurnal minggu ini</h3>
              <span class="attention-count">{{ jurnalThisWeekCount }}</span>
            </div>
            <p class="attention-desc">
              {{ jurnalThisWeekCount > 0
                ? `${jurnalThisWeekCount} jurnal sudah dicatat minggu ini.`
                : 'Belum ada jurnal mengajar minggu ini.' }}
            </p>
            <router-link to="/teaching-journal" class="attention-cta">
              {{ jurnalThisWeekCount > 0 ? 'Buka Jurnal' : 'Isi Jurnal' }} →
            </router-link>
          </div>

          <div
            v-if="piketToday?.is_on_duty && canAccessPiket"
            class="attention-block"
          >
            <div class="attention-block-head">
              <h3>Lapor kejadian piket</h3>
            </div>
            <p class="attention-desc">Lapor kelas kosong, keterlambatan, atau kejadian lain saat bertugas.</p>
            <router-link
              to="/guru-piket?tab=monitor&action=new-incident"
              class="attention-cta"
            >
              Lapor kejadian →
            </router-link>
          </div>

          <div v-if="piketToday?.is_on_duty && !piketToday.has_log" class="attention-block attention-block-alert">
            <div class="attention-block-head">
              <h3>Log kegiatan piket</h3>
              <span class="attention-count warn">!</span>
            </div>
            <p class="attention-desc">Anda piket hari ini tetapi belum mengisi log kegiatan.</p>
            <router-link
              v-if="canAccessPiket"
              to="/guru-piket?tab=logs&action=new-log"
              class="attention-cta"
            >
              Isi log kegiatan →
            </router-link>
          </div>
        </aside>
      </div>

      <!-- Students modal -->
      <div
        v-if="studentsModalOpen"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="homeroom-students-title"
        @click.self="closeStudentsModal"
      >
        <div class="modal-panel">
          <div class="modal-header">
            <div>
              <h3 id="homeroom-students-title">Siswa Kelas {{ selectedClass?.name || '' }}</h3>
              <p class="modal-subtitle">
                Kelas {{ selectedClass?.grade || '-' }}
                <template v-if="selectedClass?.academic_year"> · {{ selectedClass.academic_year }}</template>
              </p>
            </div>
            <button type="button" class="modal-close" aria-label="Tutup" @click="closeStudentsModal">×</button>
          </div>

          <div v-if="studentsLoading" class="modal-body loading-state compact">
            <p>Memuat daftar siswa...</p>
          </div>
          <div v-else-if="studentsError" class="modal-body">
            <p class="modal-error">{{ studentsError }}</p>
            <button type="button" class="retry-btn" @click="loadHomeroomStudents">Coba lagi</button>
          </div>
          <div v-else-if="!homeroomStudents.length" class="modal-body empty-state compact">
            <h3>Belum ada siswa</h3>
            <p>Kelas ini belum memiliki siswa aktif.</p>
          </div>
          <div v-else class="modal-body">
            <div class="students-table-wrap">
              <table class="students-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIS</th>
                    <th>NISN</th>
                    <th>JK</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(student, index) in homeroomStudents" :key="student.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ student.name }}</td>
                    <td>{{ student.nis || '-' }}</td>
                    <td>{{ student.nisn || '-' }}</td>
                    <td>{{ genderLabel(student.gender) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="students-count">{{ formatNumber(homeroomStudents.length) }} siswa aktif</p>
          </div>
        </div>
      </div>
    </div></template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppChart from '@/components/AppChart.vue'
import { teacherApi } from '@/api/teacher'
import { waliKelasApi } from '@/api/waliKelas'
import { myTeacherAppreciationApi } from '@/api/teacherAppreciation'
import correspondenceApi from '@/api/correspondence'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { doughnutFromEntries, ATTENDANCE_COLORS } from '@/composables/useChart'

const authStore = useAuthStore()
const toast = useToast()
const router = useRouter()

const loading = ref(true)
const pointsLoading = ref(true)
const myPoints = ref(null)
const loadError = ref('')
const pendingDispositions = ref([])
const completingDispositionId = ref(null)
const dashboardData = ref({
  teacher: null,
  summary: { total_classes: 0, total_students: 0, homeroom_classes: 0 },
  classes: [],
  active_academic_year: null,
  piket_today: null,
})

const studentsModalOpen = ref(false)
const selectedClass = ref(null)
const homeroomStudents = ref([])
const studentsLoading = ref(false)
const studentsError = ref('')

const waliClassId = ref('')
const waliDash = ref(null)
const waliDashLoading = ref(false)
const waliDashError = ref('')

const teacher = computed(() => dashboardData.value.teacher)
const summary = computed(() => dashboardData.value.summary || {})
const classes = computed(() => dashboardData.value.classes || [])
const homeroomClasses = computed(() => classes.value.filter((c) => c.is_homeroom))
const taughtOnlyClasses = computed(() => classes.value.filter((c) => !c.is_homeroom))
const activeAcademicYear = computed(() => dashboardData.value.active_academic_year)
const jurnalThisWeekCount = computed(() => dashboardData.value.jurnal_this_week_count ?? 0)
const gradesPending = computed(() => dashboardData.value.grades_pending || [])
const piketToday = computed(() => dashboardData.value.piket_today || null)
const displayInstitutionName = computed(() =>
  dashboardData.value.active_institution?.name
  || teacher.value?.institution?.name
  || authStore.activeInstitution?.name
  || ''
)

const waliClassName = computed(() => {
  const found = homeroomClasses.value.find((c) => String(c.id) === String(waliClassId.value))
  return found?.name || ''
})

const waliHubLink = computed(() => ({
  path: '/teacher/wali',
  query: { class_id: waliClassId.value || undefined, panel: 'siswa' },
}))

const waliAbsensiLink = computed(() => ({
  path: '/teacher/wali',
  query: { class_id: waliClassId.value || undefined, panel: 'absensi' },
}))

const waliConditionItems = computed(() => {
  const d = waliDash.value
  if (!d || !waliClassId.value) return []
  const q = (panel) => ({ path: '/teacher/wali', query: { class_id: waliClassId.value, panel } })
  return [
    { label: 'Alpa hari ini', value: Number(d.attendance_today?.alpha || 0), color: '#ef4444', to: q('absensi') },
    { label: 'Skor BK tinggi', value: Number(d.bk_high_scores?.count || 0), color: '#f59e0b', to: q('siswa') },
    { label: 'Nilai belum lengkap', value: Number(d.grades_incomplete || 0), color: '#0284c7', to: q('nilai') },
    { label: 'Belum punya akun', value: Number(d.accounts?.missing_account || 0), color: '#64748b', to: q('siswa') },
  ]
})

const attendanceChartSubtitle = computed(() => {
  if (waliDashLoading.value && !waliDash.value) return 'Memuat…'
  const d = waliDash.value
  if (!d) return ''
  const total = Number(d.students?.total || 0)
  const recorded = Number(d.attendance_today?.students_recorded || 0)
  const name = waliClassName.value
  if (!total) return name ? `${name} · belum ada siswa aktif` : 'Belum ada siswa aktif'
  return `${name ? name + ' · ' : ''}${formatNumber(recorded)} dari ${formatNumber(total)} tercatat hari ini`
})

const attendanceChart = computed(() => {
  const d = waliDash.value
  if (!d) return null
  const total = Number(d.students?.total || 0)
  const att = d.attendance_today || {}
  const recorded = Number(att.students_recorded || 0)
  const unrecorded = Math.max(0, total - recorded)
  return doughnutFromEntries([
    { label: `Hadir (${att.hadir || 0})`, value: att.hadir || 0, color: ATTENDANCE_COLORS.hadir },
    { label: `Izin (${att.izin || 0})`, value: att.izin || 0, color: ATTENDANCE_COLORS.izin },
    { label: `Sakit (${att.sakit || 0})`, value: att.sakit || 0, color: ATTENDANCE_COLORS.sakit },
    { label: `Alpa (${att.alpha || 0})`, value: att.alpha || 0, color: ATTENDANCE_COLORS.alpha },
    { label: `Dinas luar (${att.dinas_luar || 0})`, value: att.dinas_luar || 0, color: ATTENDANCE_COLORS.dinas_luar },
    { label: `Belum tercatat (${unrecorded})`, value: unrecorded, color: '#64748b' },
  ])
})

const attendanceChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
  onClick: () => {
    if (waliClassId.value) router.push(waliAbsensiLink.value)
  },
}))

const conditionChartSubtitle = computed(() => {
  if (waliDashLoading.value && !waliDash.value) return 'Memuat…'
  const items = waliConditionItems.value
  if (!items.length) return ''
  const flagged = items.reduce((sum, item) => sum + (item.value > 0 ? 1 : 0), 0)
  if (!flagged) return 'Tidak ada yang perlu ditindaklanjuti'
  return `${flagged} hal perlu ditindaklanjuti`
})

const conditionChart = computed(() => {
  const items = waliConditionItems.value
  if (!items.length) return null
  return {
    labels: items.map((item) => item.label),
    datasets: [{
      label: 'Siswa',
      data: items.map((item) => item.value),
      backgroundColor: items.map((item) => item.color),
      borderRadius: 6,
      maxBarThickness: 28,
    }],
  }
})

const conditionChartOptions = computed(() => ({
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { display: false } },
    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
  },
  onClick: (_evt, elements) => {
    if (!elements?.length) return
    const item = waliConditionItems.value[elements[0].index]
    if (item?.to) router.push(item.to)
  },
}))

function syncWaliClassId() {
  const list = homeroomClasses.value
  if (!list.length) {
    waliClassId.value = ''
    waliDash.value = null
    waliDashError.value = ''
    return
  }
  const ids = new Set(list.map((c) => String(c.id)))
  if (!ids.has(String(waliClassId.value))) {
    waliClassId.value = String(list[0].id)
  }
}

function onWaliClassChange(id) {
  waliClassId.value = String(id)
  loadWaliSnapshot()
}

async function loadWaliSnapshot() {
  if (!waliClassId.value) {
    waliDash.value = null
    waliDashError.value = ''
    return
  }
  waliDashLoading.value = true
  waliDashError.value = ''
  try {
    const res = await waliKelasApi.getDashboard(waliClassId.value)
    waliDash.value = res.data?.data || null
  } catch (error) {
    waliDash.value = null
    waliDashError.value =
      error.formattedMessage ||
      error.response?.data?.message ||
      'Gagal memuat snapshot kelas wali.'
  } finally {
    waliDashLoading.value = false
  }
}

const canAccessModule = (moduleKey) => {
  const role = authStore.user?.role
  if (!role) return false
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  if ((authStore.user?.permissions || []).includes(moduleKey)) return true
  if (moduleKey === 'guru_piket'
    && (authStore.user?.is_piket_scheduled || authStore.user?.is_piket_on_duty || piketToday.value?.is_on_duty)) {
    return true
  }
  return false
}

const canAccessPiket = computed(() =>
  canAccessModule('guru_piket')
  || canAccessModule('guru_piket_manage')
  || !!piketToday.value?.is_on_duty
  || !!authStore.user?.is_piket_scheduled
  || !!authStore.user?.is_piket_on_duty
)

const piketLogStatusLabel = computed(() => {
  const s = piketToday.value?.log_status
  return ({ draft: 'Draft', submitted: 'Dikirim', reviewed: 'Direview' }[s] || s || 'Ada')
})

const teacherName = computed(() => {
  return teacher.value?.name || authStore.user?.name || 'Guru'
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat pagi'
  if (hour < 15) return 'Selamat siang'
  if (hour < 19) return 'Selamat sore'
  return 'Selamat malam'
})

const attentionCount = computed(() => {
  let n = gradesPending.value.length
  if (piketToday.value?.is_on_duty && !piketToday.value?.has_log) n += 1
  if (canAccessModule('teaching_journal') && jurnalThisWeekCount.value === 0) n += 1
  return n
})

const showAttentionPanel = computed(() => {
  return (
    canAccessModule('grade_book') ||
    canAccessModule('teaching_journal') ||
    (piketToday.value?.is_on_duty && canAccessPiket.value)
  )
})

const iconPaths = {
  journal: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  grade: '<path d="M9 11l3 3L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  mail: '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  star: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  alert: '<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  chat: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  exam: '<path d="M9 12h6M9 16h6M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  lab: '<path d="M9 3h6M10 3v5.5L4.5 18a1 1 0 0 0 .9 1.5h13.2a1 1 0 0 0 .9-1.5L14 8.5V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  calendar: '<path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
  report: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 13h8M8 17h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
  default: '<path d="M5 12h14M19 12l-7-7M19 12l-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
}

const actionIconSvg = (name) => {
  const paths = iconPaths[name] || iconPaths.default
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">${paths}</svg>`
}

const quickActions = computed(() => {
  const actions = []
  if (homeroomClasses.value.length) {
    actions.push({
      to: '/teacher/wali',
      label: 'Kelas Wali',
      icon: 'users',
      tone: 'success',
      badge: waliClassName.value || homeroomClasses.value.map((c) => c.name).filter(Boolean).join(', ') || null,
    })
  }
  const hasTeachingAssignments = (authStore.user?.teaching_assignments || []).length > 0
  if (hasTeachingAssignments && (canAccessModule('teaching_journal') || canAccessModule('grade_book'))) {
    actions.push({
      to: '/teacher/today',
      label: 'Jam Mengajar Hari Ini',
      icon: 'journal',
      tone: 'primary',
      badge: 'Absen · Jurnal · Nilai',
    })
    actions.push({
      to: '/teacher/jadwal',
      label: 'Jadwal Mengajar',
      icon: 'calendar',
      tone: 'neutral',
      badge: 'Mingguan',
    })
  }
  if (canAccessModule('teaching_journal')) {
    actions.push({
      to: '/teaching-journal',
      label: 'Jurnal Mengajar',
      icon: 'journal',
      tone: 'primary',
      badge: `${jurnalThisWeekCount.value} minggu ini`,
    })
  }
  if (canAccessModule('grade_book')) {
    actions.push({
      to: '/grade-book',
      label: 'Buku Nilai',
      icon: 'grade',
      tone: 'success',
      badge: gradesPending.value.length ? `${gradesPending.value.length} belum diisi` : null,
      warning: gradesPending.value.length > 0,
    })
  }
  if (canAccessModule('correspondence')) {
    actions.push({ to: '/correspondence', label: 'Buat Surat', icon: 'mail', tone: 'info' })
  }
  actions.push({ to: '/teacher/poin', label: 'Poin & Prestasi', icon: 'star', tone: 'warning' })
  if (canAccessModule('teacher_appreciation')) {
    actions.push({ to: '/teacher-appreciation', label: 'Kelola Apresiasi', icon: 'star', tone: 'warning' })
  }
  if (canAccessPiket.value) {
    actions.push({
      to: piketToday.value?.is_on_duty
        ? '/guru-piket?tab=monitor&action=new-incident'
        : '/guru-piket',
      label: piketToday.value?.is_on_duty ? 'Lapor Kejadian' : 'Guru Piket',
      icon: 'shield',
      tone: 'primary',
      badge: piketToday.value?.is_on_duty ? 'Hari ini' : null,
      warning: !!piketToday.value?.is_on_duty,
    })
  }
  if (canAccessModule('teacher_violation_report') && !canAccessModule('teacher_appreciation')) {
    actions.push({ to: '/teacher-appreciation', label: 'Lapor Pelanggaran', icon: 'alert', tone: 'danger' })
  }
  if (canAccessModule('violation')) {
    actions.push({ to: '/violation', label: 'Pelanggaran & Poin', icon: 'alert', tone: 'danger' })
  }
  if (canAccessModule('violation') || canAccessModule('counseling') || canAccessModule('bk_report')) {
    actions.push({ to: '/laporan-bk', label: 'Laporan BK', icon: 'report', tone: 'neutral' })
  }
  if (canAccessModule('counseling')) {
    actions.push({ to: '/counseling', label: 'Konseling', icon: 'chat', tone: 'counseling' })
  }
  if (canAccessModule('online_exam')) {
    actions.push({ to: '/ujian-online/exams', label: 'Ujian Online', icon: 'exam', tone: 'info' })
  }
  if (canAccessModule('facility') || authStore.user?.is_lab_responsible) {
    actions.push({
      to: '/lab',
      label: authStore.user?.is_lab_responsible && !canAccessModule('facility') ? 'Lab Saya' : 'Manajemen Lab',
      icon: 'lab',
      tone: 'neutral',
    })
  }
  if (canAccessModule('extracurricular') || authStore.user?.is_extracurricular_supervisor) {
    actions.push({ to: '/extracurricular', label: 'Ekstrakurikuler', icon: 'users', tone: 'success' })
  }
  actions.push({ to: '/lab-booking', label: 'Booking Lab', icon: 'calendar', tone: 'neutral' })
  if (canAccessModule('attendance')) {
    actions.push({
      to: '/attendance/employee',
      label: 'Absensi Guru & Staff',
      icon: 'calendar',
      tone: 'primary',
    })
  }
  return actions
})

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num)

const genderLabel = (gender) => {
  if (gender === 'L' || gender === 'male' || gender === 'laki-laki') return 'L'
  if (gender === 'P' || gender === 'female' || gender === 'perempuan') return 'P'
  return gender || '-'
}

const openStudentsModal = (classItem) => {
  selectedClass.value = classItem
  studentsModalOpen.value = true
  loadHomeroomStudents()
}

const closeStudentsModal = () => {
  studentsModalOpen.value = false
  selectedClass.value = null
  homeroomStudents.value = []
  studentsError.value = ''
}

const loadHomeroomStudents = async () => {
  if (!selectedClass.value?.id) return
  studentsLoading.value = true
  studentsError.value = ''
  try {
    const response = await teacherApi.getHomeroomClassStudents(selectedClass.value.id, {
      per_page: 100,
      status: 'Aktif',
    })
    const payload = response.data
    homeroomStudents.value = payload?.data || []
  } catch (error) {
    console.error('Error loading homeroom students:', error)
    studentsError.value =
      error.formattedMessage ||
      error.response?.data?.message ||
      'Gagal memuat daftar siswa.'
    homeroomStudents.value = []
  } finally {
    studentsLoading.value = false
  }
}

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const response = await teacherApi.getDashboard()
    dashboardData.value = response.data?.data || dashboardData.value
    syncWaliClassId()
    if (waliClassId.value) await loadWaliSnapshot()
  } catch (error) {
    console.error('Error loading teacher dashboard:', error)
    loadError.value =
      error.formattedMessage ||
      error.response?.data?.message ||
      'Gagal memuat dashboard. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

const loadPendingDispositions = async () => {
  try {
    const res = await correspondenceApi.getPendingDispositions()
    pendingDispositions.value = res.data?.data || []
  } catch {
    pendingDispositions.value = []
  }
}

const completeDisposition = async (id) => {
  completingDispositionId.value = id
  try {
    await correspondenceApi.completeDisposition(id)
    pendingDispositions.value = pendingDispositions.value.filter((d) => d.id !== id)
    toast.success('Berhasil', 'Disposisi ditandai selesai')
  } catch (error) {
    toast.error(
      'Gagal',
      error.formattedMessage || error.response?.data?.message || 'Gagal menyelesaikan disposisi'
    )
  } finally {
    completingDispositionId.value = null
  }
}

const loadMyPoints = async () => {
  pointsLoading.value = true
  try {
    const res = await myTeacherAppreciationApi.getSummary()
    myPoints.value = res.data?.data || null
  } catch {
    myPoints.value = null
  } finally {
    pointsLoading.value = false
  }
}

onMounted(() => {
  loadDashboard()
  loadMyPoints()
  loadPendingDispositions()
})
</script>

<style scoped>
.dashboard {
  width: 100%;
  max-width: 100%;
  padding: 0 0 8px;
}

.disposition-inbox {
  margin: 0 0 16px;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid #fcd34d;
  background: #fffbeb;
}

.disposition-inbox-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.disposition-inbox-head h2 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #92400e;
}

.disposition-inbox-count {
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  background: #f59e0b;
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.disposition-inbox-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.disposition-inbox-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #fff;
  border: 1px solid #fde68a;
}

.disposition-inbox-subject {
  margin: 0 0 2px;
  font-size: 0.92rem;
  font-weight: 600;
  color: #1f2937;
}

.disposition-inbox-no {
  font-weight: 500;
  color: #6b7280;
}

.disposition-inbox-meta {
  margin: 0 0 4px;
  font-size: 0.8rem;
  color: #6b7280;
}

.disposition-inbox-instruction {
  margin: 0;
  font-size: 0.85rem;
  color: #374151;
  white-space: pre-wrap;
}

.disposition-inbox-btn {
  flex-shrink: 0;
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  background: #0d9488;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.disposition-inbox-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Welcome - compact bar */
.welcome-section {
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  border-radius: 14px;
  padding: 12px 20px;
  margin-bottom: 20px;
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
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.2px;
}

.welcome-sep {
  opacity: 0.7;
  font-weight: 300;
}

.welcome-content p,
.welcome-content .welcome-inst {
  font-size: 12px;
  opacity: 0.95;
  margin: 0;
  font-weight: 500;
}

.welcome-content p.loading {
  opacity: 0.85;
  font-style: italic;
}

.academic-period {
  font-size: 12px;
  opacity: 0.9;
  font-weight: 500;
  padding-left: 12px;
  border-left: 1px solid rgba(255, 255, 255, 0.4);
}

.welcome-actions {
  flex-shrink: 0;
}

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
  transition: background 0.2s;
}

.profile-link:hover {
  background: rgba(255, 255, 255, 0.28);
}

/* Piket */
.piket-card {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  padding: 18px 20px;
  border-radius: 14px;
  border: 1px solid #6ee7b7;
  background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 55%, #fff 100%);
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.08);
}

.piket-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #047857;
  background: #d1fae5;
  border-radius: 999px;
  padding: 4px 10px;
  margin-bottom: 8px;
}

.piket-title {
  margin: 0 0 6px;
  font-size: 15px;
  font-weight: 700;
  color: #065f46;
}

.piket-meta {
  margin: 0;
  font-size: 14px;
  color: #047857;
  font-weight: 600;
}

.piket-hint {
  margin: 8px 0 0;
  font-size: 13px;
  color: #b45309;
}

.piket-hint.ok {
  color: #047857;
}

.piket-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.piket-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid #a7f3d0;
  background: #fff;
  color: #047857;
  transition: filter 0.15s;
}

.piket-btn.primary {
  background: #059669;
  border-color: #059669;
  color: #fff;
}

.piket-btn:hover {
  filter: brightness(0.98);
}

.error-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  color: #991b1b;
}

.error-banner p {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

.retry-btn {
  flex-shrink: 0;
  border: none;
  background: #dc2626;
  color: white;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
}

.retry-btn:hover {
  background: #b91c1c;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 14px;
  padding: 16px 18px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  transition: box-shadow 0.2s, border-color 0.2s, transform 0.2s;
}

.stat-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.07);
  border-color: #d1d5db;
}

.stat-card-primary { border-left: 4px solid #059669; }
.stat-card-success { border-left: 4px solid #10b981; }
.stat-card-warning { border-left: 4px solid #f59e0b; }
.stat-card-points {
  border-left: 4px solid #0284c7;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}

.stat-card-points:hover {
  border-color: #7dd3fc;
  box-shadow: 0 8px 20px rgba(2, 132, 199, 0.12);
  transform: translateY(-1px);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(5, 150, 105, 0.12);
  color: #059669;
}

.stat-card-success .stat-icon {
  background: rgba(16, 185, 129, 0.12);
  color: #10b981;
}

.stat-card-warning .stat-icon {
  background: rgba(245, 158, 11, 0.12);
  color: #f59e0b;
}

.stat-card-points .stat-icon {
  background: rgba(14, 165, 233, 0.12);
  color: #0284c7;
}

.stat-body {
  flex: 1;
  min-width: 0;
}

.stat-title {
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  margin: 0 0 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  color: #0f172a;
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 2px;
  letter-spacing: -0.4px;
  line-height: 1.2;
  word-break: break-word;
}

.stat-value.subject-value {
  font-size: 16px;
  line-height: 1.35;
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
}

.wali-snapshot {
  margin-bottom: 20px;
}

.wali-snapshot-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.wali-class-select {
  font-size: 13px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 6px 10px;
  background: #fff;
  color: #334155;
}

.wali-snapshot-error {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin: 0;
  padding: 12px 14px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  color: #b91c1c;
  font-size: 13px;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.charts-grid :deep(.app-chart-wrap) {
  cursor: pointer;
}

.charts-grid :deep(.app-chart-wrap:not(.is-pie)) {
  height: 260px;
}

.chart-link {
  font-size: 12px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
  white-space: nowrap;
}

.chart-link:hover {
  text-decoration: underline;
}

/* Quick actions */
.quick-actions {
  background: white;
  border-radius: 12px;
  padding: 16px 18px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
  margin-bottom: 20px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.section-header h2 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.2px;
}

.section-meta {
  font-size: 12px;
  font-weight: 600;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  padding: 4px 10px;
  border-radius: 999px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #f8fafc;
  border-radius: 12px;
  text-decoration: none;
  color: #1e293b;
  transition: all 0.2s ease;
  border: 1px solid #e5e7eb;
  min-height: 68px;
}

.action-card:hover {
  background: #fff;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.action-card.has-warning {
  border-color: #fecaca;
  background: #fff7f7;
}

.action-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-icon-primary,
.action-icon-success,
.action-icon-counseling { background: rgba(5, 150, 105, 0.1); color: #059669; }
.action-icon-warning { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.action-icon-info { background: rgba(14, 165, 233, 0.12); color: #0284c7; }
.action-icon-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.action-icon-neutral { background: rgba(100, 116, 139, 0.1); color: #64748b; }

.action-content {
  flex: 1;
  min-width: 0;
}

.action-content h4 {
  font-size: 13px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
  line-height: 1.3;
}

.action-badge {
  display: block;
  margin-top: 3px;
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
}

.action-badge.warning {
  color: #dc2626;
  font-weight: 600;
}

.action-arrow {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #94a3b8;
  transition: transform 0.2s, color 0.2s;
}

.action-card:hover .action-arrow {
  color: #059669;
  transform: translateX(2px);
}

/* Content grid */
.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  align-items: start;
}

.content-grid.has-aside {
  grid-template-columns: minmax(0, 1fr) 300px;
}

.classes-section,
.attention-panel {
  background: white;
  border-radius: 12px;
  padding: 16px 18px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
}

.link-button {
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  background: rgba(5, 150, 105, 0.1);
  padding: 7px 12px;
  border-radius: 8px;
  transition: background 0.2s;
  white-space: nowrap;
}

.link-button:hover {
  background: rgba(5, 150, 105, 0.18);
}

.classes-subsection {
  margin-bottom: 22px;
}

.classes-subsection:last-child {
  margin-bottom: 0;
}

.subsection-title {
  margin: 0 0 12px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 8px;
}

.subsection-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.subsection-dot.homeroom { background: #059669; }
.subsection-dot.taught { background: #64748b; }

.subsection-count {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 7px;
  border-radius: 999px;
}

.classes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.class-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  background: #f8fafc;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.class-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
  background: #fff;
}

.class-card-homeroom {
  border-color: #bbf7d0;
  background: linear-gradient(160deg, #f0fdf4 0%, #fff 70%);
}

.class-card-homeroom:hover {
  border-color: #86efac;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.1);
}

.class-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 6px;
}

.class-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
}

.homeroom-badge {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #15803d;
  background: rgba(22, 163, 74, 0.12);
  padding: 3px 8px;
  border-radius: 999px;
}

.class-meta {
  font-size: 12px;
  color: #64748b;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px 6px;
  margin-bottom: 12px;
}

.class-meta .divider {
  color: #cbd5e1;
}

.class-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.class-stats {
  font-size: 13px;
  color: #334155;
}

.class-stats strong {
  color: #059669;
  font-weight: 700;
}

.class-room {
  font-size: 11px;
  color: #94a3b8;
  max-width: 50%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-align: right;
}

.class-students-btn {
  margin-top: 12px;
  width: 100%;
  border: 1px solid #bbf7d0;
  background: #fff;
  color: #15803d;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
  text-align: center;
  text-decoration: none;
}

.class-card-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-top: 12px;
}

.class-card-actions .class-students-btn {
  margin-top: 0;
}

.class-students-btn:hover {
  background: #f0fdf4;
  border-color: #86efac;
}

.class-hub-btn {
  background: #059669;
  border-color: #059669;
  color: #fff;
}

.class-hub-btn:hover {
  background: #047857;
  border-color: #047857;
  color: #fff;
}

/* Attention panel */
.attention-block {
  padding: 14px 0;
  border-bottom: 1px solid #f1f5f9;
}

.attention-block:first-of-type {
  padding-top: 0;
}

.attention-block:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.attention-block-alert {
  background: #fffbeb;
  margin: 0 -8px;
  padding: 14px 8px !important;
  border-radius: 10px;
  border: 1px solid #fde68a;
}

.attention-block-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.attention-block-head h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.attention-count {
  font-size: 12px;
  font-weight: 700;
  color: #059669;
  background: #ecfdf5;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.attention-count.warn {
  color: #b45309;
  background: #fef3c7;
}

.attention-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.attention-list li {
  margin-bottom: 6px;
}

.attention-list a {
  display: flex;
  flex-direction: column;
  gap: 1px;
  text-decoration: none;
  padding: 8px 10px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  transition: border-color 0.15s, background 0.15s;
}

.attention-list a:hover {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.attention-main {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
}

.attention-sub {
  font-size: 12px;
  color: #64748b;
}

.attention-empty,
.attention-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.45;
}

.attention-cta,
.attention-more {
  display: inline-block;
  margin-top: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
}

.attention-cta:hover,
.attention-more:hover {
  text-decoration: underline;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-panel {
  background: #fff;
  border-radius: 14px;
  width: min(720px, 100%);
  max-height: min(80vh, 720px);
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #0f172a;
}

.modal-subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #64748b;
}

.modal-close {
  border: none;
  background: transparent;
  font-size: 24px;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  padding: 0 4px;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-body {
  padding: 16px 20px 20px;
  overflow: auto;
}

.modal-body.compact {
  padding-top: 28px;
  padding-bottom: 28px;
}

.modal-error {
  color: #b91c1c;
  margin: 0 0 12px;
}

.students-table-wrap {
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.students-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.students-table th,
.students-table td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}

.students-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  white-space: nowrap;
}

.students-table tbody tr:last-child td {
  border-bottom: none;
}

.students-count {
  margin: 12px 0 0;
  font-size: 12px;
  color: #64748b;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 28px;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 36px 16px;
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

/* Laptop 1366x768: rapat tanpa membuang lebar untuk chart */
@media (max-width: 1440px) {
  .welcome-section {
    padding: 10px 16px;
    margin-bottom: 14px;
  }

  .piket-card {
    padding: 14px 16px;
    margin-bottom: 14px;
    gap: 12px;
  }

  .stats-grid {
    gap: 10px;
    margin-bottom: 14px;
  }

  .stat-card {
    padding: 12px 14px;
    gap: 10px;
  }

  .content-grid.has-aside {
    grid-template-columns: minmax(0, 1fr) minmax(220px, 260px);
  }

  .quick-actions,
  .classes-section,
  .attention-panel {
    padding: 14px 16px;
  }
}

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .welcome-section {
    padding: 14px 16px;
    flex-direction: column;
    align-items: flex-start;
  }

  .welcome-content h1 {
    font-size: 16px;
  }

  .welcome-sep {
    display: none;
  }

  .academic-period {
    padding-left: 0;
    border-left: none;
    width: 100%;
  }

  .welcome-actions {
    width: 100%;
  }

  .profile-link {
    width: 100%;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    padding: 14px;
    gap: 10px;
  }

  .stat-value {
    font-size: 18px;
  }

  .quick-actions,
  .classes-section,
  .attention-panel {
    padding: 16px;
    border-radius: 14px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .action-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    min-height: 0;
    padding: 14px;
  }

  .action-arrow {
    display: none;
  }

  .piket-card {
    padding: 16px;
  }

  .piket-card-actions {
    width: 100%;
  }

  .piket-btn {
    flex: 1;
  }

  .error-banner {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }

  .classes-grid {
    grid-template-columns: 1fr;
  }
}
</style>
