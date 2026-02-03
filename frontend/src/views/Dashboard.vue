<template>
  <div class="dashboard-page">
    <div class="dashboard-main">
      <Layout>
        <div class="dashboard">
          <!-- Welcome Section -->
          <div class="welcome-section">
            <div class="welcome-content">
              <h1>{{ greeting }}{{ userName ? `, ${userName}` : '' }}!</h1>
              <p v-if="institution">{{ institution.name }}</p>
              <p v-else-if="loading" class="loading">Memuat data...</p>
              <p v-else class="loading">{{ institutionError || 'Instansi tidak ditemukan' }}</p>
              <div v-if="academicPeriodText" class="academic-period">{{ academicPeriodText }}</div>
            </div>
          </div>

          <!-- Statistics Cards -->
          <div class="stats-grid">
            <div class="stat-card stat-card-primary">
              <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              </div>
              <div class="stat-body">
                <h3 class="stat-title">Profil {{ institutionTypeLabel }}</h3>
            <p v-if="institution" class="stat-value">{{ institution.name }}</p>
            <p v-else-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value text-muted">—</p>
                <span class="stat-label">{{ institutionTypeLabel }} Terdaftar</span>
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
            <p v-else class="stat-value">{{ formatNumber(studentCount) }}</p>
            <span class="stat-label">Siswa Aktif</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Guru</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(teacherCount) }}</p>
            <span class="stat-label">Guru Aktif</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-info">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M8 7H18M8 11H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Kelas</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(classCount) }}</p>
            <span class="stat-label">Kelas Terdaftar</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-neutral">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M8 6H16M8 10H16M8 14H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Mata Pelajaran</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(subjectCount) }}</p>
            <span class="stat-label">Mapel Terdaftar</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-danger">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.64 18.3 1.55 18.64 1.55 19C1.55 19.36 1.64 19.7 1.82 20C2 20.3 2.26 20.56 2.58 20.73C2.9 20.9 3.26 20.97 3.63 20.97H20.37C20.74 20.97 21.1 20.9 21.42 20.73C21.74 20.56 22 20.3 22.18 20C22.36 19.7 22.45 19.36 22.45 19C22.45 18.64 22.36 18.3 22.18 18L13.71 3.86C13.53 3.57 13.27 3.31 12.95 3.14C12.63 2.97 12.27 2.9 11.9 2.9C11.53 2.9 11.17 2.97 10.85 3.14C10.53 3.31 10.27 3.57 10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Pelanggaran</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(violationCount) }}</p>
            <span class="stat-label">Catatan Pelanggaran</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-counseling">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Konseling</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counselingCount) }}</p>
            <span class="stat-label">Sesi Konseling</span>
          </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="quick-actions">
            <div class="section-header">
              <h2>Aksi Cepat</h2>
            </div>
            <div class="actions-grid">
          <router-link to="/institution" class="action-card action-card-primary">
            <div class="action-icon action-icon-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Profil {{ institutionTypeLabel }}</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
          
          <router-link to="/student" class="action-card action-card-success">
            <div class="action-icon action-icon-success">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 11V17M9 14H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Data Siswa</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
          
          <router-link to="/teacher" class="action-card action-card-warning">
            <div class="action-icon action-icon-warning">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13 11V17M10 14H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Data Guru</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/class" class="action-card action-card-info">
            <div class="action-icon action-icon-info">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6.5 2H20V22H6.5C5.83696 22 5.20107 21.7366 4.73223 21.2678C4.26339 20.7989 4 20.163 4 19.5V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Kelas</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/violation" class="action-card action-card-danger">
            <div class="action-icon action-icon-danger">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.64 18.3 1.55 18.64 1.55 19C1.55 19.36 1.64 19.7 1.82 20C2 20.3 2.26 20.56 2.58 20.73C2.9 20.9 3.26 20.97 3.63 20.97H20.37C20.74 20.97 21.1 20.9 21.42 20.73C21.74 20.56 22 20.3 22.18 20C22.36 19.7 22.45 19.36 22.45 19C22.45 18.64 22.36 18.3 22.18 18L13.71 3.86C13.53 3.57 13.27 3.31 12.95 3.14C12.63 2.97 12.27 2.9 11.9 2.9C11.53 2.9 11.17 2.97 10.85 3.14C10.53 3.31 10.27 3.57 10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Pelanggaran</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/counseling" class="action-card action-card-counseling">
            <div class="action-icon action-icon-counseling">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Konseling</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/lesson-schedule" class="action-card action-card-neutral">
            <div class="action-icon action-icon-neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8C3 5 4.5 3 8 3H16C19.5 3 21 5 21 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.995 13.7H12M12 13.7V16.7M8 13.7H8.01M16 13.7H16.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Jadwal Pelajaran</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/report" class="action-card action-card-neutral">
            <div class="action-icon action-icon-neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2V8H20M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Laporan</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Aktivitas terbaru (audit log ringkasan) -->
      <div v-if="authStore.user?.role === 'institution_admin' || authStore.user?.role === 'admin' || authStore.user?.role === 'super_admin'" class="audit-section">
        <div class="section-header">
          <h2>Aktivitas Terbaru</h2>
        </div>
        <div v-if="auditLogsLoading" class="audit-loading">Memuat...</div>
        <div v-else-if="auditLogs.length === 0" class="audit-empty">Belum ada aktivitas tercatat.</div>
        <ul v-else class="audit-list">
          <li v-for="log in auditLogs" :key="log.id" class="audit-item">
            <span class="audit-desc">{{ log.description }}</span>
            <span class="audit-meta">{{ log.user_name }} · {{ formatAuditDate(log.created_at) }}</span>
          </li>
        </ul>
      </div>
    </div>
      </Layout>
    </div>
    <HelpSidebar />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import HelpSidebar from '@/components/HelpSidebar.vue'
import { useAuthStore } from '@/stores/auth'
import { institutionApi } from '@/api/institution'
import { studentApi } from '@/api/student'
import { teacherApi } from '@/api/teacher'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { violationApi } from '@/api/violation'
import { auditLogApi } from '@/api/auditLog'
import { getInstitutionTypeLabel } from '@/utils/institution'

const authStore = useAuthStore()
const institution = ref(null)
const institutionError = ref('')
const studentCount = ref(0)
const teacherCount = ref(0)
const classCount = ref(0)
const subjectCount = ref(0)
const violationCount = ref(0)
const counselingCount = ref(0)
const loading = ref(true)
const auditLogs = ref([])
const auditLogsLoading = ref(false)

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}

const institutionTypeLabel = computed(() => {
  return getInstitutionTypeLabel(institution.value?.level)
})

const userName = computed(() => authStore.user?.name || '')

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return 'Selamat pagi'
  if (hour >= 11 && hour < 15) return 'Selamat siang'
  if (hour >= 15 && hour < 18) return 'Selamat sore'
  return 'Selamat malam'
})

const academicPeriodText = computed(() => {
  const inst = institution.value
  if (!inst?.active_academic_year?.name && !inst?.active_semester?.name) return ''
  const parts = []
  if (inst.active_academic_year?.name) parts.push(inst.active_academic_year.name)
  if (inst.active_semester?.name) parts.push(inst.active_semester.name)
  return parts.join(' · ')
})

const formatAuditDate = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  const now = new Date()
  const diff = now - d
  if (diff < 3600000) return `${Math.floor(diff / 60000)} menit lalu`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} jam lalu`
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  loading.value = true
  try {
    institutionError.value = ''
    const [instRes, studentRes, teacherRes, classRes, subjectRes, violationRes, counselingRes] = await Promise.all([
      institutionApi.getMy().catch((err) => {
        console.error('Dashboard institution:', err)
        institutionError.value = err.response?.data?.message || 'Gagal memuat data instansi.'
        return { data: null }
      }),
      studentApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      teacherApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      classApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      subjectApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      violationApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      counselingApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } }))
    ])

    let instData = instRes.data?.data ?? instRes.data ?? null
    if (!instData && authStore.user?.institution_id) {
      try {
        const byId = await institutionApi.get(authStore.user.institution_id)
        instData = byId.data?.data ?? byId.data ?? null
      } catch (_) {}
    }
    institution.value = instData ?? authStore.user?.institution ?? null

    studentCount.value = studentRes.data?.meta?.total ?? studentRes.data?.data?.length ?? 0
    teacherCount.value = teacherRes.data?.meta?.total ?? teacherRes.data?.data?.length ?? 0
    classCount.value = classRes.data?.meta?.total ?? classRes.data?.data?.length ?? 0
    subjectCount.value = subjectRes.data?.meta?.total ?? subjectRes.data?.data?.length ?? 0
    violationCount.value = violationRes.data?.meta?.total ?? violationRes.data?.data?.length ?? 0
    counselingCount.value = counselingRes.data?.meta?.total ?? counselingRes.data?.data?.length ?? 0
  } catch (error) {
    console.error('Error loading dashboard:', error)
    studentCount.value = 0
    teacherCount.value = 0
    classCount.value = 0
    subjectCount.value = 0
    violationCount.value = 0
    counselingCount.value = 0
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.dashboard-page {
  display: flex;
  width: 100%;
  min-height: 100vh;
  background: #f8fafc;
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

/* Welcome Section */
.welcome-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.academic-period {
  margin-top: 10px;
  font-size: 13px;
  opacity: 0.9;
  font-weight: 500;
}

/* Statistics Grid */
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
  transition: all 0.2s ease;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border-color: #cbd5e1;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.stat-card-success .stat-icon {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-card-warning .stat-icon {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.stat-card-info .stat-icon {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.stat-card-neutral .stat-icon {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.stat-card-danger .stat-icon {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.stat-card-counseling .stat-icon {
  background: rgba(14, 165, 233, 0.1);
  color: #0ea5e9;
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
  font-size: 24px;
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

/* Aktivitas terbaru (audit log) */
.audit-section {
  background: white;
  border-radius: 12px;
  padding: 20px 24px;
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
}

.audit-section .section-header {
  margin-bottom: 16px;
}

.audit-section .section-header h2 {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.audit-loading,
.audit-empty {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.audit-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.audit-item {
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.audit-item:last-child {
  border-bottom: none;
}

.audit-desc {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
}

.audit-meta {
  font-size: 12px;
  color: #64748b;
}

/* Quick Actions */
.quick-actions {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.section-header {
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.3px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 10px;
  text-decoration: none;
  color: #1e293b;
  transition: all 0.2s ease;
  border: 1px solid #e2e8f0;
}

.action-card:hover {
  background: white;
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.action-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-icon-primary {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.action-icon-success {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.action-icon-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.action-icon-info {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.action-icon-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.action-icon-counseling {
  background: rgba(14, 165, 233, 0.1);
  color: #0ea5e9;
}

.action-icon-neutral {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.action-content {
  flex: 1;
  min-width: 0;
}

.action-content h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: #0f172a;
}

.action-arrow {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #94a3b8;
  transition: all 0.2s ease;
}

.action-card:hover .action-arrow {
  color: #64748b;
  transform: translateX(2px);
}

.action-card-primary:hover .action-arrow {
  color: #667eea;
}

.action-card-success:hover .action-arrow {
  color: #10b981;
}

.action-card-warning:hover .action-arrow {
  color: #f59e0b;
}

.action-card-info:hover .action-arrow {
  color: #3b82f6;
}

.action-card-danger:hover .action-arrow {
  color: #ef4444;
}

.action-card-counseling:hover .action-arrow {
  color: #0ea5e9;
}

.action-card-neutral:hover .action-arrow {
  color: #64748b;
}

/* Responsive Design - Tablet */
@media (max-width: 768px) {
  .welcome-section {
    padding: 20px 24px;
    border-radius: 16px;
  }
  
  .welcome-content h1 {
    font-size: 20px;
  }
  
  .welcome-content p {
    font-size: 14px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .stat-card {
    padding: 16px;
  }
  
  .quick-actions {
    padding: 20px;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }
  
  .action-card {
    padding: 14px;
  }
  
  .section-header h2 {
    font-size: 18px;
  }
}

/* Mobile - smartphone: grid 2 kolom aksi cepat, tampilan lebih menarik */
@media (max-width: 480px) {
  .dashboard {
    padding-bottom: 8px;
  }

  .welcome-section {
    padding: 20px 20px;
    margin-bottom: 20px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
  }

  .welcome-content h1 {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 6px;
  }

  .welcome-content p {
    font-size: 14px;
    opacity: 0.95;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 20px;
  }

  .stat-card {
    padding: 18px 20px;
    border-radius: 14px;
    min-height: auto;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  }

  .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
  }

  .stat-value {
    font-size: 26px;
  }

  .quick-actions {
    padding: 20px 16px;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  }

  .section-header {
    margin-bottom: 16px;
  }

  .section-header h2 {
    font-size: 18px;
    font-weight: 700;
  }

  /* Aksi cepat: 2 kolom seperti tombol shortcut */
  .actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .action-card {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 20px 12px;
    min-height: 120px;
    border-radius: 14px;
    gap: 12px;
  }

  .action-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
  }

  .action-content {
    order: 2;
  }

  .action-content h4 {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.3;
  }

  .action-arrow {
    display: none;
  }
}
</style>
