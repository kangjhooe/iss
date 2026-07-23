<template>
  <div class="dashboard-page">
    <div class="dashboard-main">
      <Layout>
        <div class="dashboard">
          <!-- Welcome Section (ringkas, satu baris) -->
          <div class="welcome-section">
            <div class="welcome-content">
              <h1>{{ greeting }}{{ userName ? `, ${userName}` : '' }}!</h1>
              <template v-if="institution">
                <span class="welcome-sep">·</span>
                <p class="welcome-inst">{{ institution.name }}</p>
              </template>
              <template v-else-if="loading">
                <span class="welcome-sep">·</span>
                <p class="loading">Memuat data...</p>
              </template>
              <template v-else>
                <span class="welcome-sep">·</span>
                <p class="loading">{{ institutionError || 'Instansi tidak ditemukan' }}</p>
              </template>
              <div v-if="academicPeriodText" class="academic-period">{{ academicPeriodText }}</div>
            </div>
          </div>

          <!-- Statistics Cards -->
          <div class="stats-grid">
            <div class="stat-card stat-card-success" :class="{ 'stat-empty-state': studentCount === 0 && !loading }">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Siswa</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value" :class="{ 'stat-empty': studentCount === 0 }">{{ formatStatValue(studentCount) }}</p>
            <span class="stat-label">{{ studentCount === 0 && !loading ? 'Mulai dengan menambah data siswa' : 'Siswa Aktif' }}</span>
            <router-link v-if="!loading" to="/student" class="stat-action">Tambah Siswa →</router-link>
            </div>
          </div>
        
            <div class="stat-card stat-card-warning" :class="{ 'stat-empty-state': teacherCount === 0 && !loading }">
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
            <p v-else class="stat-value" :class="{ 'stat-empty': teacherCount === 0 }">{{ formatStatValue(teacherCount) }}</p>
            <span class="stat-label">{{ teacherCount === 0 && !loading ? 'Mulai dengan menambah data guru' : 'Guru Aktif' }}</span>
            <router-link v-if="!loading" to="/teacher" class="stat-action">Tambah Guru →</router-link>
            </div>
          </div>
        
            <div class="stat-card stat-card-info" :class="{ 'stat-empty-state': classCount === 0 && !loading }">
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
            <p v-else class="stat-value" :class="{ 'stat-empty': classCount === 0 }">{{ formatStatValue(classCount) }}</p>
            <span class="stat-label">{{ classCount === 0 && !loading ? 'Belum ada kelas terdaftar' : 'Kelas Terdaftar' }}</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-neutral" :class="{ 'stat-empty-state': subjectCount === 0 && !loading }">
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
            <p v-else class="stat-value" :class="{ 'stat-empty': subjectCount === 0 }">{{ formatStatValue(subjectCount) }}</p>
            <span class="stat-label">{{ subjectCount === 0 && !loading ? 'Belum ada mapel terdaftar' : 'Mapel Terdaftar' }}</span>
            </div>
          </div>
        
            <div class="stat-card stat-card-danger" :class="{ 'stat-empty-state': violationCount === 0 && !loading }">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.64 18.3 1.55 18.64 1.55 19C1.55 19.36 1.64 19.7 1.82 20C2 20.3 2.26 20.56 2.58 20.73C2.9 20.9 3.26 20.97 3.63 20.97H20.37C20.74 20.97 21.1 20.9 21.42 20.73C21.74 20.56 22 20.3 22.18 20C22.36 19.7 22.45 19.36 22.45 19C22.45 18.64 22.36 18.3 22.18 18L13.71 3.86C13.53 3.57 13.27 3.31 12.95 3.14C12.63 2.97 12.27 2.9 11.9 2.9C11.53 2.9 11.17 2.97 10.85 3.14C10.53 3.31 10.27 3.57 10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Pelanggaran</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value" :class="{ 'stat-empty': violationCount === 0 }">{{ formatStatValue(violationCount) }}</p>
            <span class="stat-label">{{ violationCount === 0 && !loading ? 'Belum ada catatan' : 'Catatan Pelanggaran' }}</span>
            </div>
          </div>

            <div class="stat-card stat-card-danger" :class="{ 'stat-empty-state': violationCountThisMonth === 0 && !loading }">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8C3 5 4.5 3 8 3H16C19.5 3 21 5 21 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 13V17M9 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Pelanggaran Bulan Ini</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value" :class="{ 'stat-empty': violationCountThisMonth === 0 }">{{ formatStatValue(violationCountThisMonth) }}</p>
            <span class="stat-label">{{ violationCountThisMonth === 0 && !loading ? 'Tidak ada di bulan ini' : 'Bulan berjalan' }}</span>
          </div>
          </div>
        
            <div class="stat-card stat-card-counseling" :class="{ 'stat-empty-state': counselingCount === 0 && !loading }">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Konseling</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value" :class="{ 'stat-empty': counselingCount === 0 }">{{ formatStatValue(counselingCount) }}</p>
            <span class="stat-label">{{ counselingCount === 0 && !loading ? 'Belum ada sesi' : 'Sesi Konseling' }}</span>
          </div>
            </div>

            <div class="stat-card stat-card-counseling" :class="{ 'stat-empty-state': counselingPendingCount === 0 && !loading }">
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Konseling Pending</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value" :class="{ 'stat-empty': counselingPendingCount === 0 }">{{ formatStatValue(counselingPendingCount) }}</p>
            <span class="stat-label">{{ counselingPendingCount === 0 && !loading ? 'Tidak ada antrean' : 'Menunggu / Jadwal' }}</span>
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

          <router-link to="/correspondence" class="action-card action-card-neutral">
            <div class="action-icon action-icon-neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Surat</h4>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/buku-tamu" class="action-card action-card-neutral">
            <div class="action-icon action-icon-neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Buku Tamu</h4>
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
        <div v-else-if="auditLogs.length === 0" class="audit-empty">Belum ada aktivitas. Aktivitas akan tercatat saat Anda mengubah data.</div>
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
import { counselingApi } from '@/api/counseling'
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
const violationCountThisMonth = ref(0)
const counselingCount = ref(0)
const counselingPendingCount = ref(0)
const loading = ref(true)
const auditLogs = ref([])
const auditLogsLoading = ref(false)

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatStatValue = (num) => formatNumber(num ?? 0)

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
    const now = new Date()
    const dateFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
    const dateTo = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)
    const [instRes, studentRes, teacherRes, classRes, subjectRes, violationRes, violationMonthRes, counselingRes, counselingPendingRes] = await Promise.all([
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
      violationApi.getAll({ per_page: 1, date_from: dateFrom, date_to: dateTo }).catch(() => ({ data: { meta: { total: 0 } } })),
      counselingApi.getAll({ per_page: 1 }).catch(() => ({ data: { meta: { total: 0 } } })),
      counselingApi.getAll({ per_page: 1, status: 'jadwal' }).catch(() => ({ data: { meta: { total: 0 } } }))
    ])

    let instData = instRes.data?.data ?? instRes.data ?? null
    if (!instData && (authStore.activeInstitutionId || authStore.user?.institution_id)) {
      try {
        const byId = await institutionApi.get(authStore.activeInstitutionId || authStore.user.institution_id)
        instData = byId.data?.data ?? byId.data ?? null
      } catch (_) {}
    }
    institution.value = instData ?? authStore.activeInstitution ?? authStore.user?.institution ?? null

    studentCount.value = studentRes.data?.meta?.total ?? studentRes.data?.data?.length ?? 0
    teacherCount.value = teacherRes.data?.meta?.total ?? teacherRes.data?.data?.length ?? 0
    classCount.value = classRes.data?.meta?.total ?? classRes.data?.data?.length ?? 0
    subjectCount.value = subjectRes.data?.meta?.total ?? subjectRes.data?.data?.length ?? 0
    violationCount.value = violationRes.data?.meta?.total ?? violationRes.data?.data?.length ?? 0
    violationCountThisMonth.value = violationMonthRes.data?.meta?.total ?? violationMonthRes.data?.data?.length ?? 0
    counselingCount.value = counselingRes.data?.meta?.total ?? counselingRes.data?.data?.length ?? 0
    counselingPendingCount.value = counselingPendingRes.data?.meta?.total ?? counselingPendingRes.data?.data?.length ?? 0
  } catch (error) {
    console.error('Error loading dashboard:', error)
    studentCount.value = 0
    teacherCount.value = 0
    classCount.value = 0
    subjectCount.value = 0
    violationCount.value = 0
    violationCountThisMonth.value = 0
    counselingCount.value = 0
    counselingPendingCount.value = 0
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

/* Welcome - bar ringkas dan ramah */
.welcome-section {
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  border-radius: 14px;
  padding: 12px 20px;
  margin-bottom: 20px;
  color: white;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px 16px;
}

.welcome-content {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px 14px;
}

.welcome-content h1 {
  font-size: 17px;
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
  font-size: 13px;
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
  border-left: 1px solid rgba(255,255,255,0.4);
}

/* Statistics Grid - card lebih hidup */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: all 0.25s ease;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.stat-card.stat-empty-state {
  background: linear-gradient(145deg, #fafafa 0%, #f8fafc 100%);
}

.stat-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border-color: #d1d5db;
}

.stat-card.stat-card-success { border-left: 4px solid #10b981; }
.stat-card.stat-card-warning { border-left: 4px solid #f59e0b; }
.stat-card.stat-card-info { border-left: 4px solid #059669; }
.stat-card.stat-card-neutral { border-left: 4px solid #64748b; }
.stat-card.stat-card-danger { border-left: 4px solid #ef4444; }
.stat-card.stat-card-counseling { border-left: 4px solid #059669; }

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.stat-card-success .stat-icon {
  background: rgba(16, 185, 129, 0.14);
  color: #059669;
}

.stat-card-warning .stat-icon {
  background: rgba(245, 158, 11, 0.14);
  color: #d97706;
}

.stat-card-info .stat-icon {
  background: rgba(59, 130, 246, 0.12);
  color: #059669;
}

.stat-card-neutral .stat-icon {
  background: rgba(100, 116, 139, 0.12);
  color: #475569;
}

.stat-card-danger .stat-icon {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.stat-card-counseling .stat-icon {
  background: rgba(5, 150, 105, 0.12);
  color: #047857;
}

.stat-body {
  flex: 1;
  min-width: 0;
}

.stat-title {
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  margin: 0 0 6px 0;
  text-transform: uppercase;
  letter-spacing: 0.6px;
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

.stat-value.stat-empty {
  color: #94a3b8;
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
  line-height: 1.4;
}

.stat-action {
  display: inline-block;
  margin-top: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
  padding: 6px 0;
}

.stat-action:hover {
  text-decoration: underline;
}

.stat-card-success .stat-action { color: #059669; }
.stat-card-warning .stat-action { color: #d97706; }

/* Aktivitas terbaru */
.audit-section {
  background: white;
  border-radius: 16px;
  padding: 22px 26px;
  margin-bottom: 24px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
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

/* Quick Actions - lebih ramah */
.quick-actions {
  background: white;
  border-radius: 16px;
  padding: 24px 28px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
  margin-bottom: 24px;
}

.section-header {
  margin-bottom: 20px;
}

.section-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.2px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 14px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: #f8fafc;
  border-radius: 14px;
  text-decoration: none;
  color: #1e293b;
  transition: all 0.25s ease;
  border: 1px solid #e5e7eb;
}

.action-card:hover {
  background: #fff;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-icon-primary {
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
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
  color: #059669;
}

.action-icon-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.action-icon-counseling {
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
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
  color: #059669;
}

.action-card-success:hover .action-arrow {
  color: #10b981;
}

.action-card-warning:hover .action-arrow {
  color: #f59e0b;
}

.action-card-info:hover .action-arrow {
  color: #059669;
}

.action-card-danger:hover .action-arrow {
  color: #ef4444;
}

.action-card-counseling:hover .action-arrow {
  color: #059669;
}

.action-card-neutral:hover .action-arrow {
  color: #64748b;
}

/* Tablet */
@media (max-width: 768px) {
  .welcome-section {
    padding: 14px 16px;
    margin-bottom: 16px;
    border-radius: 12px;
    flex-direction: column;
    align-items: flex-start;
  }

  .welcome-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .welcome-sep {
    display: none;
  }

  .welcome-content h1 {
    font-size: 16px;
  }

  .welcome-content p,
  .welcome-content .welcome-inst {
    font-size: 12px;
  }

  .academic-period {
    padding-left: 0;
    border-left: none;
    margin-top: 2px;
    font-size: 11px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 18px;
  }

  .stat-card {
    padding: 14px;
    gap: 12px;
    border-radius: 12px;
  }

  .stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .stat-icon svg {
    width: 16px;
    height: 16px;
  }

  .stat-title {
    font-size: 10px;
    margin-bottom: 4px;
  }

  .stat-value {
    font-size: 20px;
  }

  .stat-label {
    font-size: 11px;
  }

  .stat-action {
    margin-top: 8px;
    font-size: 12px;
  }

  .quick-actions {
    padding: 18px 16px;
    border-radius: 14px;
    margin-bottom: 16px;
  }

  .section-header {
    margin-bottom: 14px;
  }

  .section-header h2 {
    font-size: 16px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .action-card {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 16px 10px;
    min-height: 100px;
    border-radius: 12px;
    gap: 10px;
  }

  .action-icon {
    width: 40px;
    height: 40px;
  }

  .action-content h4 {
    font-size: 13px;
    line-height: 1.3;
  }

  .action-arrow {
    display: none;
  }

  .audit-section {
    padding: 16px;
    border-radius: 14px;
  }

  .audit-desc {
    font-size: 13px;
  }
}

/* Smartphone */
@media (max-width: 480px) {
  .dashboard {
    padding-bottom: 4px;
  }

  .welcome-section {
    padding: 12px 14px;
    margin-bottom: 14px;
    border-radius: 12px;
  }

  .welcome-content h1 {
    font-size: 15px;
    line-height: 1.3;
  }

  .welcome-content p,
  .welcome-content .welcome-inst {
    font-size: 12px;
    line-height: 1.35;
    word-break: break-word;
  }

  .academic-period {
    font-size: 11px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 14px;
  }

  .stat-card {
    flex-direction: column;
    align-items: flex-start;
    padding: 12px;
    gap: 8px;
    border-radius: 12px;
    border-left-width: 3px;
  }

  .stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
  }

  .stat-icon svg {
    width: 15px;
    height: 15px;
  }

  .stat-title {
    font-size: 9px;
    letter-spacing: 0.4px;
    margin-bottom: 2px;
  }

  .stat-value {
    font-size: 22px;
    margin-bottom: 2px;
  }

  .stat-value.stat-empty,
  .loading-text {
    font-size: 13px;
  }

  .stat-label {
    font-size: 10px;
    line-height: 1.3;
  }

  .stat-action {
    margin-top: 6px;
    font-size: 11px;
    padding: 4px 0;
  }

  .quick-actions {
    padding: 14px 12px;
    border-radius: 14px;
    margin-bottom: 14px;
  }

  .section-header {
    margin-bottom: 12px;
  }

  .section-header h2 {
    font-size: 15px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .action-card {
    padding: 14px 8px;
    min-height: 88px;
    border-radius: 12px;
    gap: 8px;
  }

  .action-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .action-icon svg {
    width: 16px;
    height: 16px;
  }

  .action-content h4 {
    font-size: 12px;
    line-height: 1.25;
  }

  .audit-section {
    padding: 14px 12px;
    border-radius: 14px;
    margin-bottom: 12px;
  }

  .audit-item {
    padding: 8px 0;
  }

  .audit-desc {
    font-size: 12px;
    line-height: 1.4;
    word-break: break-word;
  }

  .audit-meta {
    font-size: 11px;
  }

  .audit-loading,
  .audit-empty {
    font-size: 13px;
  }
}

/* Layar sangat sempit */
@media (max-width: 360px) {
  .stats-grid {
    gap: 8px;
  }

  .stat-card {
    padding: 10px;
  }

  .stat-value {
    font-size: 18px;
  }

  .action-card {
    min-height: 80px;
    padding: 12px 6px;
  }

  .action-content h4 {
    font-size: 11px;
  }
}

/* Kurangi hover berat di perangkat sentuh */
@media (hover: none) {
  .stat-card:hover,
  .action-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border-color: #e5e7eb;
    background: #f8fafc;
  }

  .action-card:active {
    background: #fff;
    border-color: #cbd5e1;
  }
}
</style>
