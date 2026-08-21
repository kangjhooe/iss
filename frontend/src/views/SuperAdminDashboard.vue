<template>
  <Layout>
    <div class="super-admin-dashboard">
      <div class="welcome-section">
        <div class="welcome-content">
          <h1>Selamat Datang, {{ userName }}!</h1>
          <p>Kelola seluruh sistem {{ appName }} dari sini</p>
        </div>
        <div class="welcome-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="currentColor" opacity="0.2"/>
            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <div class="stats-grid">
        <router-link to="/institution" class="stat-card stat-card-primary">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Institusi</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.institutions) }}</p>
            <span class="stat-label">{{ formatNumber(counts.active_institutions) }} aktif · {{ formatNumber(counts.inactive_institutions) }} dibekukan</span>
          </div>
        </router-link>

        <router-link to="/institution?is_active=0" class="stat-card stat-card-danger">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
              <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Dibekukan</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.inactive_institutions) }}</p>
            <span class="stat-label">Perlu perhatian</span>
          </div>
        </router-link>

        <div class="stat-card stat-card-success">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Siswa</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.students) }}</p>
            <span class="stat-label">Siswa aktif seluruh institusi</span>
          </div>
        </div>

        <div class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Total Guru</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.teachers) }}</p>
            <span class="stat-label">Guru aktif seluruh institusi</span>
          </div>
        </div>

        <router-link to="/institution-change-requests" class="stat-card stat-card-info">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Pending Requests</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.pending_requests) }}</p>
            <span class="stat-label">Perlu review</span>
          </div>
        </router-link>

        <router-link to="/feedback" class="stat-card stat-card-warning">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Feedback Aktif</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.open_feedback) }}</p>
            <span class="stat-label">Bug & request fitur</span>
          </div>
        </router-link>

        <router-link to="/super-admin/institution-admins" class="stat-card stat-card-danger">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
              <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <h3 class="stat-title">Reset Sandi</h3>
            <p v-if="loading" class="stat-value loading-text">Memuat...</p>
            <p v-else class="stat-value">{{ formatNumber(counts.pending_password_resets) }}</p>
            <span class="stat-label">Menunggu diproses</span>
          </div>
        </router-link>
      </div>

      <div class="panels-grid">
        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Request Menunggu Review</h2>
              <p>Perubahan nama/NPSN dari admin sekolah</p>
            </div>
            <router-link to="/institution-change-requests" class="panel-link">Lihat semua</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="pendingRequests.length === 0" class="panel-empty">Tidak ada request pending</div>
          <ul v-else class="panel-list">
            <li v-for="req in pendingRequests" :key="req.id">
              <div class="panel-item-main">
                <strong>{{ req.institution?.name || 'Institusi' }}</strong>
                <span class="panel-meta">Ubah {{ fieldLabel(req.field_name) }} → {{ req.new_value }}</span>
              </div>
              <span class="panel-time">{{ formatRelative(req.created_at) }}</span>
            </li>
          </ul>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Feedback Aktif</h2>
              <p>Laporan bug & request fitur dari sekolah</p>
            </div>
            <router-link to="/feedback" class="panel-link">Lihat semua</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="openFeedbackTickets.length === 0" class="panel-empty">Tidak ada feedback aktif</div>
          <ul v-else class="panel-list">
            <li v-for="ticket in openFeedbackTickets" :key="ticket.id">
              <router-link to="/feedback" class="panel-item-main">
                <strong>{{ ticket.title }}</strong>
                <span class="panel-meta">{{ ticket.institution?.name || 'Institusi' }} · {{ ticket.type === 'bug' ? 'Bug' : 'Fitur' }}</span>
              </router-link>
              <span class="panel-time">{{ formatRelative(ticket.created_at) }}</span>
            </li>
          </ul>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Permintaan Reset Sandi</h2>
              <p>Admin sekolah yang lupa sandi</p>
            </div>
            <router-link to="/super-admin/institution-admins" class="panel-link">Proses</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="pendingPasswordResets.length === 0" class="panel-empty">Tidak ada permintaan reset</div>
          <ul v-else class="panel-list">
            <li v-for="item in pendingPasswordResets" :key="item.id">
              <router-link to="/super-admin/institution-admins" class="panel-item-main">
                <strong>{{ item.institution?.name || item.user?.name || item.email }}</strong>
                <span class="panel-meta">{{ item.user?.name || 'Admin' }} · {{ item.email }}</span>
              </router-link>
              <span class="panel-time">{{ formatRelative(item.created_at) }}</span>
            </li>
          </ul>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Institusi Dibekukan</h2>
              <p>User tidak dapat login sampai diaktifkan</p>
            </div>
            <router-link to="/institution?is_active=0" class="panel-link">Kelola</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="inactiveInstitutions.length === 0" class="panel-empty">Tidak ada institusi dibekukan</div>
          <ul v-else class="panel-list">
            <li v-for="inst in inactiveInstitutions" :key="inst.id">
              <div class="panel-item-main">
                <strong>{{ inst.name }}</strong>
                <span class="panel-meta">{{ inst.npsn || '—' }} · {{ inst.level || '—' }}</span>
              </div>
              <span class="badge-inactive">Dibekukan</span>
            </li>
          </ul>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Institusi Terbaru</h2>
              <p>Pendaftaran sekolah/madrasah terbaru</p>
            </div>
            <router-link to="/institution" class="panel-link">Lihat semua</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="recentInstitutions.length === 0" class="panel-empty">Belum ada institusi</div>
          <ul v-else class="panel-list">
            <li v-for="inst in recentInstitutions" :key="inst.id">
              <div class="panel-item-main">
                <strong>{{ inst.name }}</strong>
                <span class="panel-meta">{{ inst.npsn || '—' }} · {{ inst.level || '—' }}</span>
              </div>
              <span :class="inst.is_active ? 'badge-active' : 'badge-inactive'">
                {{ inst.is_active ? 'Aktif' : 'Dibekukan' }}
              </span>
            </li>
          </ul>
        </section>

        <section class="panel">
          <div class="panel-header">
            <div>
              <h2>Aktivitas Terbaru</h2>
              <p>Ringkasan audit log sistem</p>
            </div>
            <router-link to="/audit-log" class="panel-link">Audit Log</router-link>
          </div>
          <div v-if="loading" class="panel-empty">Memuat...</div>
          <div v-else-if="recentAuditLogs.length === 0" class="panel-empty">Belum ada aktivitas</div>
          <ul v-else class="panel-list">
            <li v-for="log in recentAuditLogs" :key="log.id">
              <div class="panel-item-main">
                <strong>{{ log.description || log.module }}</strong>
                <span class="panel-meta">{{ log.user_name || 'Sistem' }} · {{ log.action }}</span>
              </div>
              <span class="panel-time">{{ formatRelative(log.created_at) }}</span>
            </li>
          </ul>
        </section>
      </div>

      <div class="quick-actions">
        <div class="section-header">
          <h2>Aksi Cepat</h2>
          <p>Kelola sistem dengan cepat</p>
        </div>
        <div class="actions-grid">
          <router-link to="/academic-year" class="action-card action-card-primary">
            <div class="action-icon action-icon-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Kelola Tahun Ajaran</h4>
              <p>Atur tahun ajaran dan semester</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/institution-change-requests" class="action-card action-card-info">
            <div class="action-icon action-icon-info">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Review Permintaan</h4>
              <p>Tinjau perubahan data institusi</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/feedback" class="action-card">
            <div class="action-icon action-icon-warn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Inbox Feedback</h4>
              <p>Tinjau bug & request fitur sekolah</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/institution" class="action-card action-card-success">
            <div class="action-icon action-icon-success">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Kelola Institusi</h4>
              <p>Lihat, bekukan, atau aktifkan sekolah</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/onboard" class="action-card action-card-primary">
            <div class="action-icon action-icon-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Onboarding Sekolah</h4>
              <p>Buat institusi + admin pertama</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/institution-admins" class="action-card action-card-info">
            <div class="action-icon action-icon-info">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Admin Institusi</h4>
              <p>Buat, reset sandi, aktif/nonaktif</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/app-branding" class="action-card action-card-secondary">
            <div class="action-icon action-icon-secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Branding Aplikasi</h4>
              <p>Logo & favicon untuk halaman awal, login, register</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/adoption" class="action-card action-card-secondary">
            <div class="action-icon action-icon-secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 20V10M12 20V4M6 20V14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Monitoring Adopsi</h4>
              <p>Aktivitas & penggunaan modul sekolah</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/broadcasts" class="action-card action-card-info">
            <div class="action-icon action-icon-info">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Broadcast</h4>
              <p>Kirim pengumuman ke admin sekolah</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/catatan-rilis" class="action-card action-card-secondary">
            <div class="action-icon action-icon-secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Catatan Rilis</h4>
              <p>Kelola update yang tampil di halaman publik</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/reports" class="action-card action-card-success">
            <div class="action-icon action-icon-success">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 3V21H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 16L12 11L16 15L21 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Laporan Agregat</h4>
              <p>Ringkasan lintas institusi + export CSV</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/audit-log" class="action-card action-card-secondary">
            <div class="action-icon action-icon-secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2V8H20M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Audit Log</h4>
              <p>Riwayat aktivitas lintas institusi</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/system-settings" class="action-card action-card-warn">
            <div class="action-icon action-icon-warn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18A2 2 0 0 0 3.54 21H20.46A2 2 0 0 0 22.18 18L13.71 3.86A2 2 0 0 0 10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Pengaturan Sistem</h4>
              <p>Mode pemeliharaan & konfigurasi platform</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>

          <router-link to="/super-admin/monetisasi" class="action-card action-card-primary">
            <div class="action-icon action-icon-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="action-content">
              <h4>Monetisasi</h4>
              <p>Paket, add-on & tampilkan ke sekolah</p>
            </div>
            <div class="action-arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { appName } from '@/config/app'
import { superAdminApi } from '@/api/superAdmin'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.name || authStore.user?.email || 'Super Admin')

const loading = ref(true)
const counts = ref({
  institutions: 0,
  active_institutions: 0,
  inactive_institutions: 0,
  students: 0,
  teachers: 0,
  pending_requests: 0,
  pending_password_resets: 0,
  open_feedback: 0
})
const pendingRequests = ref([])
const pendingPasswordResets = ref([])
const openFeedbackTickets = ref([])
const recentInstitutions = ref([])
const inactiveInstitutions = ref([])
const recentAuditLogs = ref([])

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0)

const fieldLabel = (field) => {
  if (field === 'name') return 'nama'
  if (field === 'npsn') return 'NPSN'
  return field || 'data'
}

const formatRelative = (iso) => {
  if (!iso) return ''
  const date = new Date(iso)
  const diffMs = Date.now() - date.getTime()
  const mins = Math.floor(diffMs / 60000)
  if (mins < 1) return 'Baru saja'
  if (mins < 60) return `${mins} mnt lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam lalu`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days} hari lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await superAdminApi.getDashboard()
    const data = res.data?.data || {}
    counts.value = {
      institutions: data.counts?.institutions || 0,
      active_institutions: data.counts?.active_institutions || 0,
      inactive_institutions: data.counts?.inactive_institutions || 0,
      students: data.counts?.students || 0,
      teachers: data.counts?.teachers || 0,
      pending_requests: data.counts?.pending_requests || 0,
      pending_password_resets: data.counts?.pending_password_resets || 0,
      open_feedback: data.counts?.open_feedback || 0
    }
    pendingRequests.value = data.pending_requests || []
    pendingPasswordResets.value = data.pending_password_resets || []
    openFeedbackTickets.value = data.open_feedback_tickets || []
    recentInstitutions.value = data.recent_institutions || []
    inactiveInstitutions.value = data.inactive_institutions || []
    recentAuditLogs.value = data.recent_audit_logs || []
  } catch (error) {
    console.error('Error loading dashboard:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.super-admin-dashboard {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.welcome-section {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 16px;
  padding: 32px 40px;
  margin-bottom: 32px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 8px 24px rgba(5, 150, 105, 0.25);
}

.welcome-content h1 {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
  color: #ffffff;
}

.welcome-content p {
  font-size: 16px;
  opacity: 0.95;
  margin: 0;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.95);
}

.welcome-icon {
  opacity: 0.2;
  flex-shrink: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  text-decoration: none;
  color: inherit;
}

a.stat-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-color: #cbd5e1;
  transform: translateY(-2px);
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
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
  color: #2563eb;
}

.stat-card-danger .stat-icon {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
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
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 4px 0;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.loading-text {
  font-size: 16px;
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

.panels-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

.panel {
  background: white;
  border-radius: 16px;
  padding: 20px 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.panel-header h2 {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 2px 0;
}

.panel-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.panel-link {
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
  white-space: nowrap;
}

.panel-link:hover {
  text-decoration: underline;
}

.panel-empty {
  color: #94a3b8;
  font-size: 14px;
  padding: 12px 0;
}

.panel-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.panel-list li {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  border-top: 1px solid #f1f5f9;
}

.panel-list li:first-child {
  border-top: none;
  padding-top: 0;
}

.panel-item-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

a.panel-item-main {
  text-decoration: none;
  color: inherit;
}

a.panel-item-main:hover strong {
  color: #059669;
}

.panel-item-main strong {
  font-size: 14px;
  color: #0f172a;
  font-weight: 600;
}

.panel-meta {
  font-size: 12px;
  color: #64748b;
}

.panel-time {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
}

.badge-active,
.badge-inactive {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-active {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.badge-inactive {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.quick-actions {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.section-header {
  margin-bottom: 24px;
}

.section-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
  letter-spacing: -0.3px;
}

.section-header p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 16px;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: #f8fafc;
  border-radius: 12px;
  text-decoration: none;
  color: #1e293b;
  transition: all 0.3s ease;
  border: 1px solid #e2e8f0;
}

.action-card:hover {
  background: white;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.action-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
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

.action-icon-info {
  background: rgba(59, 130, 246, 0.1);
  color: #059669;
}

.action-icon-secondary {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.action-icon-warn {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.action-content {
  flex: 1;
  min-width: 0;
}

.action-content h4 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #0f172a;
}

.action-content p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.action-arrow {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #94a3b8;
  transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
  color: #64748b;
  transform: translateX(4px);
}

.action-card-primary:hover .action-arrow {
  color: #059669;
}

.action-card-success:hover .action-arrow {
  color: #10b981;
}

.action-card-info:hover .action-arrow {
  color: #059669;
}

.action-card-secondary:hover .action-arrow {
  color: #64748b;
}

@media (max-width: 900px) {
  .panels-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .actions-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .super-admin-dashboard {
    padding-bottom: 8px;
  }

  .welcome-section {
    padding: 14px 16px;
    margin-bottom: 14px;
    border-radius: 12px;
    flex-direction: column;
    text-align: left;
    gap: 0;
    box-shadow: 0 4px 16px rgba(5, 150, 105, 0.2);
  }

  .welcome-content h1 {
    font-size: 1.125rem;
    line-height: 1.35;
    margin-bottom: 4px;
    color: #ffffff;
  }

  .welcome-content p {
    font-size: 0.8125rem;
    line-height: 1.4;
  }

  .welcome-icon {
    display: none;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
  }

  .stat-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding: 14px;
    border-radius: 12px;
  }

  /* kartu ganjil terakhir full-width agar grid tidak bolong */
  .stat-card:last-child:nth-child(odd) {
    grid-column: 1 / -1;
    flex-direction: row;
    align-items: center;
  }

  .stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .stat-icon svg {
    width: 18px;
    height: 18px;
  }

  .stat-title {
    font-size: 10px;
    margin-bottom: 4px;
    letter-spacing: 0.4px;
  }

  .stat-value {
    font-size: 1.375rem;
    margin-bottom: 2px;
  }

  .stat-label {
    font-size: 11px;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .panels-grid {
    gap: 12px;
    margin-bottom: 16px;
  }

  .panel {
    padding: 14px;
    border-radius: 12px;
  }

  .panel-header {
    flex-direction: row;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 12px;
  }

  .panel-header h2 {
    font-size: 0.9375rem;
    line-height: 1.3;
  }

  .panel-header p {
    display: none;
  }

  .panel-link {
    font-size: 12px;
    padding: 6px 0 6px 8px;
    margin: -6px 0;
    min-height: 32px;
    display: inline-flex;
    align-items: center;
  }

  .panel-list li {
    flex-direction: row;
    align-items: center;
    gap: 8px;
    padding: 10px 0;
  }

  .panel-item-main {
    flex: 1;
    min-width: 0;
  }

  .panel-item-main strong {
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .panel-meta {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .panel-time,
  .badge-active,
  .badge-inactive {
    flex-shrink: 0;
  }

  .quick-actions {
    padding: 14px;
    border-radius: 12px;
  }

  .section-header {
    margin-bottom: 12px;
  }

  .section-header h2 {
    font-size: 1.0625rem;
  }

  .section-header p {
    font-size: 12px;
  }

  .actions-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .action-card {
    flex-direction: row;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 10px;
    min-height: 52px;
  }

  .action-card:last-child:nth-child(odd) {
    grid-column: 1 / -1;
  }

  .action-card:hover,
  .action-card:active {
    transform: none;
  }

  .action-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
  }

  .action-icon svg {
    width: 18px;
    height: 18px;
  }

  .action-content {
    min-width: 0;
  }

  .action-content h4 {
    font-size: 12.5px;
    line-height: 1.3;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .action-content p,
  .action-arrow {
    display: none;
  }
}

@media (max-width: 480px) {
  .welcome-section {
    padding: 14px;
    margin-bottom: 14px;
  }

  .welcome-content h1 {
    font-size: 1.0625rem;
  }

  .stats-grid {
    gap: 8px;
  }

  .stat-card {
    padding: 12px;
  }

  .stat-value {
    font-size: 1.25rem;
  }

  .loading-text {
    font-size: 13px;
  }

  .actions-grid {
    gap: 8px;
  }

  .action-card {
    min-height: 48px;
    padding: 10px;
  }

  .action-content h4 {
    font-size: 12px;
  }
}

@media (hover: none) and (pointer: coarse) {
  .stat-card:hover,
  .action-card:hover {
    transform: none;
  }

  a.stat-card:active,
  .action-card:active {
    background: #f8fafc;
  }
}
</style>
