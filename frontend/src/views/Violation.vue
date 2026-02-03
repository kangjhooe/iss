<template>
  <Layout>
    <div class="violation-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Pelanggaran</h1>
            <p class="page-subtitle">Catatan pelanggaran siswa dan master jenis pelanggaran</p>
          </div>
          <div class="header-actions">
            <button v-if="activeTab === 'list'" @click="openAddModal" class="btn-primary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Pelanggaran</span>
            </button>
            <button v-if="activeTab === 'types'" @click="openAddTypeModal" class="btn-primary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Jenis Pelanggaran</span>
            </button>
            <button v-if="activeTab === 'prestasi'" @click="openAddPrestasiModal" class="btn-primary btn-compact">Tambah Prestasi</button>
            <button v-if="activeTab === 'achievement_types'" @click="openAddAchievementTypeModal" class="btn-primary btn-compact">Tambah Jenis Prestasi</button>
            <button v-if="activeTab === 'thresholds'" @click="openAddThresholdModal" class="btn-primary btn-compact">Tambah Aturan Tindakan</button>
          </div>
        </div>
      </div>

      <!-- Section tabs: Pelanggaran | Prestasi & Aturan -->
      <div class="section-tabs">
        <button :class="['section-tab', { active: sectionTab === 'pelanggaran' }]" @click="setSection('pelanggaran')">
          <span>Pelanggaran</span>
        </button>
        <button :class="['section-tab', { active: sectionTab === 'prestasi' }]" @click="setSection('prestasi')">
          <span>Prestasi & Aturan</span>
        </button>
      </div>
      <!-- Sub-tabs -->
      <div v-show="sectionTab === 'pelanggaran'" class="main-tabs">
        <button :class="['main-tab', { active: activeTab === 'list' }]" @click="activeTab = 'list'; loadViolations()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Daftar Pelanggaran</span>
        </button>
        <button :class="['main-tab', { active: activeTab === 'types' }]" @click="activeTab = 'types'; loadTypes()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Jenis Pelanggaran</span>
        </button>
        <button :class="['main-tab', { active: activeTab === 'points' }]" @click="activeTab = 'points'; loadStudentPoints()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 19C9 20.1046 7.65685 21 6 21C4.34315 21 3 20.1046 3 19C3 17.8954 4.34315 17 6 17C7.65685 17 9 17.8954 9 19ZM9 19C9 17.8954 10.3431 17 12 17C13.6569 17 15 17.8954 15 19M9 5C9 6.10457 7.65685 7 6 7C4.34315 7 3 6.10457 3 5C3 3.89543 4.34315 3 6 3C7.65685 3 9 3.89543 9 5ZM9 5C9 6.10457 10.3431 7 12 7C13.6569 7 15 6.10457 15 5M15 19C15 20.1046 13.6569 21 12 21C10.3431 21 9 20.1046 9 19M15 5C15 3.89543 13.6569 3 12 3C10.3431 3 9 3.89543 9 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Poin Siswa</span>
        </button>
      </div>
      <div v-show="sectionTab === 'prestasi'" class="main-tabs">
        <button :class="['main-tab', { active: activeTab === 'prestasi' }]" @click="activeTab = 'prestasi'; loadAchievements()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Prestasi</span>
        </button>
        <button :class="['main-tab', { active: activeTab === 'achievement_types' }]" @click="activeTab = 'achievement_types'; loadAchievementTypes()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L14.4 7.2L20 8L16 12L17.2 17.6L12 15L6.8 17.6L8 12L4 8L9.6 7.2L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Jenis Prestasi</span>
        </button>
        <button :class="['main-tab', { active: activeTab === 'thresholds' }]" @click="activeTab = 'thresholds'; loadThresholds()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 15V3M12 15L8 11M12 15L16 11M2 21H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Aturan Tindakan</span>
        </button>
      </div>

      <!-- Tab: Daftar Pelanggaran -->
      <template v-if="activeTab === 'list'">
        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari nama, NIS, NISN siswa..."
            class="search-input"
            @input="debounceLoadViolations"
          />
          <select v-model="filters.status" @change="loadViolations" class="filter-select">
            <option value="">Semua Status</option>
            <option value="dicatat">Dicatat</option>
            <option value="sanksi_diberikan">Sanksi Diberikan</option>
            <option value="follow_up">Follow Up</option>
            <option value="selesai">Selesai</option>
          </select>
          <select v-model="filters.violation_type_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Jenis</option>
            <option v-for="t in violationTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.category }})</option>
          </select>
          <select v-model="filters.academic_year_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
          <select v-model="filters.semester_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadViolations" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadViolations" />
        </div>

        <div v-if="loading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="9" :cell-widths="['90px', '140px', '120px', '80px', '60px', '100px', '80px', '100px', '90px']" />
        </div>

        <div v-else-if="violations.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada catatan pelanggaran</h3>
          <p class="empty-desc">Tambahkan pelanggaran siswa atau atur filter untuk melihat data.</p>
          <button @click="openAddModal" class="btn-primary btn-empty-cta">Tambah Pelanggaran</button>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Point</th>
                <th>Sanksi</th>
                <th>Status</th>
                <th>Pelapor</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in violations" :key="v.id">
                <td>{{ formatDate(v.violation_date) }}</td>
                <td>
                  <span class="student-name">{{ v.student?.name }}</span>
                  <span class="student-meta">{{ v.student?.nisn || v.student?.nis || '-' }}</span>
                </td>
                <td>{{ v.violation_type?.name }}</td>
                <td><span :class="['category-badge', v.violation_type?.category]">{{ v.violation_type?.category }}</span></td>
                <td><span class="point-add">+{{ v.violation_type?.point_weight || 0 }}</span></td>
                <td>{{ v.sanction || '-' }}</td>
                <td><span :class="['status-badge', 'status-' + v.status]">{{ getStatusLabel(v.status) }}</span></td>
                <td>{{ v.reporter?.name }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="openEditModal(v)" class="btn-action btn-edit" title="Edit">✎</button>
                    <button @click="confirmDelete(v)" class="btn-action btn-delete" title="Hapus">🗑</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="activeTab === 'list' && pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Tab: Jenis Pelanggaran -->
      <template v-if="activeTab === 'types'">
        <div v-if="typesLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat jenis pelanggaran...</p></div>
        <div v-else-if="violationTypes.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada jenis pelanggaran</h3>
          <p class="empty-desc">Tambahkan jenis pelanggaran (mis. Terlambat, Tidak pakai atribut) beserta kategori dan bobot point.</p>
          <button @click="openAddTypeModal" class="btn-primary btn-empty-cta">Tambah Jenis Pelanggaran</button>
        </div>
        <div v-else class="types-grid">
          <div v-for="t in violationTypes" :key="t.id" class="type-card">
            <div class="type-header">
              <span class="type-name">{{ t.name }}</span>
              <span :class="['category-badge', t.category]">{{ t.category }}</span>
            </div>
            <div class="type-body">
              <p v-if="t.default_sanction" class="type-sanction">Sanksi default: {{ t.default_sanction }}</p>
              <p class="type-point">Menambah skor: <strong>+{{ t.point_weight ?? 0 }}</strong></p>
            </div>
            <div class="type-actions">
              <button @click="openEditTypeModal(t)" class="btn-action btn-edit">Edit</button>
              <button @click="confirmDeleteType(t)" class="btn-action btn-delete">Hapus</button>
            </div>
          </div>
        </div>
      </template>

      <!-- Tab: Poin Siswa (Skor pelanggaran: makin besar makin buruk; tabung prestasi) -->
      <template v-if="activeTab === 'points'">
        <div class="points-info-banner">
          <div class="points-info-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
              <path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="points-info-text">
            <strong>Skor pelanggaran</strong> = poin pelanggaran − poin prestasi. Makin besar skor = makin buruk. Prestasi mengurangi skor. <strong>Tabung prestasi</strong> = total poin prestasi yang ditabung (siswa berprestasi punya tabung tinggi).
          </div>
        </div>
        <div class="points-summary-cards">
          <div class="summary-card card-total">
            <div class="summary-icon summary-icon-total">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <span class="summary-value">{{ pointsPagination.total ?? studentPoints.length }}</span>
            <span class="summary-label">Total Siswa</span>
          </div>
          <div class="summary-card card-warning">
            <div class="summary-icon summary-icon-warning">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M5.07183 19H18.9282C20.4678 19 21.4301 17.3333 20.6603 16L13.7321 4C12.9623 2.66667 11.0378 2.66667 10.268 4L3.33978 16C2.56998 17.3333 3.53223 19 5.07183 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <span class="summary-value">{{ studentPoints.filter(r => r.required_action).length }}</span>
            <span class="summary-label">Perlu Tindakan</span>
          </div>
          <div class="summary-card card-good">
            <div class="summary-icon summary-icon-good">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <span class="summary-value">{{ studentPoints.filter(r => r.total_points <= 0 && (r.achievement_bank ?? r.achievement_points) > 0).length }}</span>
            <span class="summary-label">Siswa Berprestasi</span>
          </div>
        </div>
        <div class="filters">
          <input v-model="pointSearch" type="text" placeholder="Cari nama, NIS, NISN..." class="search-input" @input="debounceLoadStudentPoints" />
        </div>
        <div v-if="pointsLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat poin siswa...</p></div>
        <div v-else-if="studentPoints.length === 0" class="empty-state">
          <h3 class="empty-title">Tidak ada data</h3>
          <p class="empty-desc">Ubah filter atau pastikan ada siswa di institusi.</p>
        </div>
        <div v-else class="table-container table-points">
          <table class="data-table">
            <thead>
              <tr>
                <th>Siswa</th>
                <th class="th-num">+ Pelanggaran</th>
                <th class="th-num">− Prestasi</th>
                <th class="th-num">Skor</th>
                <th class="th-num">Tabung prestasi</th>
                <th>Tindakan wajib</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in studentPoints" :key="row.student_id" :class="{ 'row-good': row.total_points <= 0 && (row.achievement_bank ?? row.achievement_points) > 0, 'row-warning': row.required_action }">
                <td>
                  <span class="student-name">{{ row.student?.name }}</span>
                  <span class="student-meta">{{ row.student?.nisn || row.student?.nis || '-' }}</span>
                  <span v-if="row.total_points <= 0 && (row.achievement_bank ?? row.achievement_points) > 0" class="badge-prestasi">Siswa Berprestasi</span>
                </td>
                <td class="num-add">+{{ row.violation_points }}</td>
                <td class="num-sub">−{{ row.achievement_points }}</td>
                <td :class="['num-total', row.total_points > 40 ? 'score-bad' : row.total_points > 20 ? 'score-warn' : row.total_points <= 0 ? 'score-good' : 'score-normal']">
                  {{ row.total_points }}
                </td>
                <td class="num-bank">{{ row.achievement_bank ?? row.achievement_points }}</td>
                <td>
                  <span v-if="row.required_action" class="action-badge">{{ row.required_action.action_name }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td>
                  <button v-if="row.required_action" @click="openLogActionModal(row)" class="btn-action btn-edit">Catat Tindakan</button>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="activeTab === 'points' && pointsPagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">Halaman {{ pointsPagination.current_page }} / {{ pointsPagination.last_page }}</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pointsPagination.current_page <= 1" @click="goToPointsPage(pointsPagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="pointsPagination.current_page >= pointsPagination.last_page" @click="goToPointsPage(pointsPagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Tab: Prestasi -->
      <template v-if="activeTab === 'prestasi'">
        <div v-if="achievementsLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat prestasi...</p></div>
        <div v-else-if="achievements.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada prestasi</h3>
          <p class="empty-desc">Prestasi mengurangi poin pelanggaran. Tambahkan prestasi siswa.</p>
          <button @click="openAddPrestasiModal" class="btn-primary btn-empty-cta">Tambah Prestasi</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Jenis Prestasi</th>
                <th>Poin</th>
                <th>Pemberi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in achievements" :key="a.id">
                <td>{{ formatDate(a.achievement_date) }}</td>
                <td>{{ a.student?.name }}</td>
                <td>{{ a.achievement_type?.name }}</td>
                <td>+{{ a.point_value }}</td>
                <td>{{ a.giver?.name }}</td>
                <td>
                <button @click="openEditPrestasiModal(a)" class="btn-action btn-edit">Edit</button>
                <button @click="confirmDeleteAchievement(a)" class="btn-action btn-delete">Hapus</button>
              </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="activeTab === 'prestasi' && achievementsPagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">Halaman {{ achievementsPagination.current_page }} / {{ achievementsPagination.last_page }}</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="achievementsPagination.current_page <= 1" @click="goToAchievementsPage(achievementsPagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="achievementsPagination.current_page >= achievementsPagination.last_page" @click="goToAchievementsPage(achievementsPagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Tab: Jenis Prestasi -->
      <template v-if="activeTab === 'achievement_types'">
        <div v-if="achievementTypesLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat jenis prestasi...</p></div>
        <div v-else-if="achievementTypes.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada jenis prestasi</h3>
          <p class="empty-desc">Tambahkan jenis prestasi (mis. Juara kelas, Kerapian) beserta poin plus.</p>
          <button @click="openAddAchievementTypeModal" class="btn-primary btn-empty-cta">Tambah Jenis Prestasi</button>
        </div>
        <div v-else class="types-grid">
          <div v-for="t in achievementTypes" :key="t.id" class="type-card">
            <div class="type-header">
              <span class="type-name">{{ t.name }}</span>
              <span class="type-point">+{{ t.point_value }} poin</span>
            </div>
            <div class="type-actions">
              <button @click="openEditAchievementTypeModal(t)" class="btn-action btn-edit">Edit</button>
              <button @click="confirmDeleteAchievementType(t)" class="btn-action btn-delete">Hapus</button>
            </div>
          </div>
        </div>
      </template>

      <!-- Tab: Aturan Tindakan -->
      <template v-if="activeTab === 'thresholds'">
        <p class="form-hint thresholds-hint">Skor pelanggaran = poin pelanggaran − poin prestasi. <strong>Makin besar skor = makin buruk.</strong> Atur rentang skor dan tindakan wajib (mis. skor 40–999 = Panggilan orang tua, 20–39 = Peringatan tertulis).</p>
        <div v-if="thresholdsLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat aturan...</p></div>
        <div v-else-if="thresholds.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada aturan tindakan</h3>
          <p class="empty-desc">Tambahkan aturan: rentang poin dan nama tindakan (Peringatan, Panggilan orang tua, Skorsing, dll).</p>
          <button @click="openAddThresholdModal" class="btn-primary btn-empty-cta">Tambah Aturan Tindakan</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Poin Min</th>
                <th>Poin Max</th>
                <th>Tindakan</th>
                <th>Keterangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in thresholds" :key="t.id">
                <td>{{ t.point_min }}</td>
                <td>{{ t.point_max }}</td>
                <td><strong>{{ t.action_name }}</strong></td>
                <td>{{ t.description || '-' }}</td>
                <td>
                  <button @click="openEditThresholdModal(t)" class="btn-action btn-edit">Edit</button>
                  <button @click="confirmDeleteThreshold(t)" class="btn-action btn-delete">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Modal: Tambah/Edit Pelanggaran -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingViolation ? 'Edit Pelanggaran' : 'Tambah Pelanggaran' }}</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitViolation" class="modal-body">
            <div class="form-group">
              <label>Siswa *</label>
              <select v-model="form.student_id" required :disabled="!!editingViolation" class="form-select">
                <option value="">Pilih siswa</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jenis Pelanggaran *</label>
              <select v-model="form.violation_type_id" required class="form-select">
                <option value="">Pilih jenis</option>
                <option v-for="t in violationTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.category }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal Pelanggaran *</label>
              <input v-model="form.violation_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Sanksi</label>
              <input v-model="form.sanction" type="text" placeholder="Contoh: Peringatan lisan" />
            </div>
            <div v-if="editingViolation" class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-select">
                <option value="dicatat">Dicatat</option>
                <option value="sanksi_diberikan">Sanksi Diberikan</option>
                <option value="follow_up">Follow Up</option>
                <option value="selesai">Selesai</option>
              </select>
            </div>
            <div class="form-group">
              <label>Deskripsi / Catatan</label>
              <textarea v-model="form.description" rows="2" placeholder="Deskripsi singkat (opsional)"></textarea>
            </div>
            <div v-if="editingViolation" class="form-group">
              <label>Catatan Follow Up</label>
              <textarea v-model="form.follow_up_notes" rows="2" placeholder="Catatan tindak lanjut"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingViolation ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Jenis Pelanggaran -->
      <div v-if="showTypeModal" class="modal-overlay" @click="showTypeModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingType ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran' }}</h3>
            <button @click="showTypeModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitType" class="modal-body">
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="typeForm.name" type="text" required placeholder="Contoh: Terlambat" />
            </div>
            <div class="form-group">
              <label>Kode (opsional)</label>
              <input v-model="typeForm.code" type="text" placeholder="Contoh: TRB" />
            </div>
            <div class="form-group">
              <label>Kategori *</label>
              <select v-model="typeForm.category" required class="form-select">
                <option value="ringan">Ringan</option>
                <option value="sedang">Sedang</option>
                <option value="berat">Berat</option>
              </select>
            </div>
            <div class="form-group">
              <label>Bobot Point (minus)</label>
              <input v-model.number="typeForm.point_weight" type="number" min="0" max="100" placeholder="0" />
            </div>
            <div class="form-group">
              <label>Sanksi Default</label>
              <input v-model="typeForm.default_sanction" type="text" placeholder="Contoh: Peringatan lisan" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="typeForm.description" rows="2" placeholder="Deskripsi jenis pelanggaran"></textarea>
            </div>
            <div v-if="typeFormError" class="error-message">{{ typeFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showTypeModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="typeFormSubmitting" class="btn-primary">
                {{ typeFormSubmitting ? 'Menyimpan...' : (editingType ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Confirm delete violation -->
      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Pelanggaran"
        :message="deleteViolationMessage"
        confirmText="Hapus"
        @confirm="doDeleteViolation"
        @cancel="deleteTarget = null"
      />
      <!-- Confirm delete type -->
      <ConfirmDialog
        v-if="deleteTypeTarget"
        :show="!!deleteTypeTarget"
        title="Hapus Jenis Pelanggaran"
        :message="deleteTypeMessage"
        confirmText="Hapus"
        @confirm="doDeleteType"
        @cancel="deleteTypeTarget = null"
      />

      <!-- Modal: Tambah/Edit Prestasi -->
      <div v-if="showPrestasiModal" class="modal-overlay" @click="showPrestasiModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingPrestasi ? 'Edit Prestasi' : 'Tambah Prestasi' }}</h3>
            <button @click="showPrestasiModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitPrestasi" class="modal-body">
            <div class="form-group">
              <label>Siswa *</label>
              <select v-model="prestasiForm.student_id" required :disabled="!!editingPrestasi" class="form-select">
                <option value="">Pilih siswa</option>
                <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jenis Prestasi *</label>
              <select v-model="prestasiForm.achievement_type_id" required class="form-select">
                <option value="">Pilih jenis</option>
                <option v-for="t in achievementTypes" :key="t.id" :value="t.id">{{ t.name }} (+{{ t.point_value }})</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal *</label>
              <input v-model="prestasiForm.achievement_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="prestasiForm.notes" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="prestasiFormError" class="error-message">{{ prestasiFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showPrestasiModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="prestasiFormSubmitting" class="btn-primary">{{ prestasiFormSubmitting ? 'Menyimpan...' : (editingPrestasi ? 'Simpan' : 'Tambah') }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Jenis Prestasi -->
      <div v-if="showAchievementTypeModal" class="modal-overlay" @click="showAchievementTypeModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingAchievementType ? 'Edit' : 'Tambah' }} Jenis Prestasi</h3>
            <button @click="showAchievementTypeModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitAchievementType" class="modal-body">
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="achievementTypeForm.name" type="text" required placeholder="Contoh: Juara kelas" />
            </div>
            <div class="form-group">
              <label>Poin (plus) *</label>
              <input v-model.number="achievementTypeForm.point_value" type="number" min="0" max="100" required />
            </div>
            <div class="form-group">
              <label>Kategori</label>
              <select v-model="achievementTypeForm.category" class="form-select">
                <option value="">-</option>
                <option value="akademik">Akademik</option>
                <option value="non_akademik">Non Akademik</option>
                <option value="sikap">Sikap</option>
              </select>
            </div>
            <div v-if="achievementTypeFormError" class="error-message">{{ achievementTypeFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showAchievementTypeModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="achievementTypeFormSubmitting" class="btn-primary">{{ achievementTypeFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Aturan Tindakan -->
      <div v-if="showThresholdModal" class="modal-overlay" @click="showThresholdModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingThreshold ? 'Edit' : 'Tambah' }} Aturan Tindakan</h3>
            <button @click="showThresholdModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitThreshold" class="modal-body">
            <p class="form-hint threshold-form-hint">Rentang <strong>skor pelanggaran</strong> (positif = buruk). Contoh: 40–999 = Panggilan orang tua, 20–39 = Peringatan tertulis.</p>
            <div class="form-group">
              <label>Skor Min *</label>
              <input v-model.number="thresholdForm.point_min" type="number" required placeholder="40" />
            </div>
            <div class="form-group">
              <label>Skor Max *</label>
              <input v-model.number="thresholdForm.point_max" type="number" required placeholder="999" />
            </div>
            <div class="form-group">
              <label>Nama Tindakan *</label>
              <input v-model="thresholdForm.action_name" type="text" required placeholder="Panggilan orang tua" />
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="thresholdForm.description" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="thresholdFormError" class="error-message">{{ thresholdFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showThresholdModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="thresholdFormSubmitting" class="btn-primary">{{ thresholdFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Catat Tindakan -->
      <div v-if="showLogActionModal" class="modal-overlay" @click="showLogActionModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Catat Tindakan Dilaksanakan</h3>
            <button @click="showLogActionModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitLogAction" class="modal-body">
            <p v-if="logActionRow" class="form-hint">Siswa: <strong>{{ logActionRow.student?.name }}</strong>. Tindakan: <strong>{{ logActionRow.required_action?.action_name }}</strong>.</p>
            <div class="form-group">
              <label>Nama Tindakan *</label>
              <input v-model="logActionForm.action_name" type="text" required placeholder="Panggilan orang tua" />
            </div>
            <div class="form-group">
              <label>Tanggal Dilaksanakan *</label>
              <input v-model="logActionForm.action_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="logActionForm.notes" rows="2" placeholder="Hasil panggilan, dll."></textarea>
            </div>
            <div v-if="logActionFormError" class="error-message">{{ logActionFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showLogActionModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="logActionFormSubmitting" class="btn-primary">{{ logActionFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog v-if="deleteAchievementTarget" :show="!!deleteAchievementTarget" title="Hapus Prestasi" :message="'Yakin menghapus prestasi ini?'" confirmText="Hapus" @confirm="doDeleteAchievement" @cancel="deleteAchievementTarget = null" />
      <ConfirmDialog v-if="deleteAchievementTypeTarget" :show="!!deleteAchievementTypeTarget" title="Hapus Jenis Prestasi" :message="deleteAchievementTypeMessage" confirmText="Hapus" @confirm="doDeleteAchievementType" @cancel="deleteAchievementTypeTarget = null" />
      <ConfirmDialog v-if="deleteThresholdTarget" :show="!!deleteThresholdTarget" title="Hapus Aturan Tindakan" :message="deleteThresholdMessage" confirmText="Hapus" @confirm="doDeleteThreshold" @cancel="deleteThresholdTarget = null" />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { violationApi, violationTypeApi, achievementApi, achievementTypeApi, pointThresholdApi, studentActionLogApi, studentPointApi } from '@/api/violation'
import { studentApi } from '@/api/student'
import { institutionApi } from '@/api/institution'
import { academicYearApi } from '@/api/academicYear'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const activeTab = ref('list')

const PELANGGARAN_TABS = ['list', 'types', 'points']
const PRESTASI_TABS = ['prestasi', 'achievement_types', 'thresholds']

const sectionTab = computed(() =>
  PELANGGARAN_TABS.includes(activeTab.value) ? 'pelanggaran' : 'prestasi'
)

function setSection(section) {
  if (section === 'pelanggaran') {
    activeTab.value = 'list'
    loadViolations()
  } else {
    activeTab.value = 'prestasi'
    loadAchievements()
  }
}
const loading = ref(true)
const typesLoading = ref(false)
const violations = ref([])
const violationTypes = ref([])
const students = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const institution = ref(null)
const academicYears = ref([])
const semesters = ref([])
const filters = ref({
  search: '',
  status: '',
  violation_type_id: '',
  academic_year_id: '',
  semester_id: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const editingViolation = ref(null)
const form = ref({
  student_id: '',
  violation_type_id: '',
  violation_date: '',
  sanction: '',
  status: 'dicatat',
  description: '',
  follow_up_notes: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const showTypeModal = ref(false)
const editingType = ref(null)
const typeForm = ref({
  name: '',
  code: '',
  category: 'ringan',
  point_weight: 0,
  default_sanction: '',
  description: '',
})
const typeFormSubmitting = ref(false)
const typeFormError = ref('')

const deleteTarget = ref(null)
const deleteTypeTarget = ref(null)
const deleteAchievementTarget = ref(null)
const deleteAchievementTypeTarget = ref(null)
const deleteThresholdTarget = ref(null)

// Poin siswa
const pointSearch = ref('')
const studentPoints = ref([])
const pointsLoading = ref(false)
const pointsPagination = ref({ current_page: 1, last_page: 1 })
let pointsDebounceTimer = null
function debounceLoadStudentPoints() {
  clearTimeout(pointsDebounceTimer)
  pointsDebounceTimer = setTimeout(() => loadStudentPoints(), 300)
}

// Prestasi
const achievements = ref([])
const achievementsLoading = ref(false)
const achievementsPagination = ref({ current_page: 1, last_page: 1 })
const achievementTypes = ref([])
const achievementTypesLoading = ref(false)
const showPrestasiModal = ref(false)
const editingPrestasi = ref(null)
const prestasiForm = ref({ student_id: '', achievement_type_id: '', achievement_date: '', notes: '' })
const prestasiFormError = ref('')
const prestasiFormSubmitting = ref(false)
const showAchievementTypeModal = ref(false)
const editingAchievementType = ref(null)
const achievementTypeForm = ref({ name: '', point_value: 10, category: '' })
const achievementTypeFormError = ref('')
const achievementTypeFormSubmitting = ref(false)

// Aturan tindakan
const thresholds = ref([])
const thresholdsLoading = ref(false)
const showThresholdModal = ref(false)
const editingThreshold = ref(null)
const thresholdForm = ref({ point_min: 40, point_max: 999, action_name: '', description: '' })
const thresholdFormError = ref('')
const thresholdFormSubmitting = ref(false)

// Catat tindakan
const showLogActionModal = ref(false)
const logActionRow = ref(null)
const logActionForm = ref({ student_id: '', point_threshold_id: null, action_name: '', action_date: '', notes: '' })
const logActionFormError = ref('')
const logActionFormSubmitting = ref(false)

const deleteViolationMessage = computed(() => {
  const name = deleteTarget.value?.student?.name || ''
  return 'Yakin menghapus catatan pelanggaran untuk ' + name + '?'
})
const deleteTypeMessage = computed(() => {
  const name = deleteTypeTarget.value?.name || ''
  return 'Yakin menghapus jenis pelanggaran ' + name + '? Jenis yang sudah dipakai tidak dapat dihapus.'
})
const deleteAchievementTypeMessage = computed(() => {
  const name = deleteAchievementTypeTarget.value?.name || ''
  return 'Yakin menghapus jenis prestasi ' + name + '?'
})
const deleteThresholdMessage = computed(() => {
  const a = deleteThresholdTarget.value?.action_name || ''
  return 'Yakin menghapus aturan tindakan: ' + a + '?'
})

let debounceTimer = null
function debounceLoadViolations() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadViolations(), 300)
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const statusLabels = {
  dicatat: 'Dicatat',
  sanksi_diberikan: 'Sanksi Diberikan',
  follow_up: 'Follow Up',
  selesai: 'Selesai',
}
function getStatusLabel(status) {
  return statusLabels[status] || status
}

async function loadViolations() {
  loading.value = true
  formError.value = ''
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      ...filters.value,
    }
    if (!params.status) delete params.status
    if (!params.violation_type_id) delete params.violation_type_id
    if (!params.academic_year_id) delete params.academic_year_id
    if (!params.semester_id) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search

    const res = await violationApi.getAll(params)
    violations.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat pelanggaran')
  } finally {
    loading.value = false
  }
}

async function loadTypes() {
  typesLoading.value = true
  try {
    const res = await violationTypeApi.getAll({ active_only: false })
    violationTypes.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat jenis pelanggaran')
  } finally {
    typesLoading.value = false
  }
}

async function loadStudents() {
  try {
    const res = await studentApi.getAll({ per_page: 500 })
    students.value = res.data.data || []
  } catch {
    students.value = []
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadViolations()
}

function openAddModal() {
  editingViolation.value = null
  form.value = {
    student_id: '',
    violation_type_id: '',
    violation_date: new Date().toISOString().slice(0, 10),
    sanction: '',
    status: 'dicatat',
    description: '',
    follow_up_notes: '',
  }
  formError.value = ''
  if (students.value.length === 0) loadStudents()
  if (violationTypes.value.length === 0) loadTypes()
  showFormModal.value = true
}

function openEditModal(v) {
  editingViolation.value = v
  form.value = {
    student_id: v.student_id,
    violation_type_id: v.violation_type_id,
    violation_date: v.violation_date,
    sanction: v.sanction || '',
    status: v.status || 'dicatat',
    description: v.description || '',
    follow_up_notes: v.follow_up_notes || '',
  }
  formError.value = ''
  showFormModal.value = true
}

async function submitViolation() {
  formSubmitting.value = true
  formError.value = ''
  try {
    if (editingViolation.value) {
      await violationApi.update(editingViolation.value.id, {
        violation_type_id: form.value.violation_type_id,
        violation_date: form.value.violation_date,
        sanction: form.value.sanction || null,
        status: form.value.status,
        description: form.value.description || null,
        follow_up_notes: form.value.follow_up_notes || null,
      })
      toast.success('Pelanggaran berhasil diperbarui')
    } else {
      await violationApi.create({
        student_id: form.value.student_id,
        violation_type_id: form.value.violation_type_id,
        violation_date: form.value.violation_date,
        sanction: form.value.sanction || undefined,
        description: form.value.description || undefined,
      })
      toast.success('Pelanggaran berhasil dicatat')
    }
    showFormModal.value = false
    loadViolations()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    formSubmitting.value = false
  }
}

function confirmDelete(v) {
  deleteTarget.value = v
}

async function doDeleteViolation() {
  if (!deleteTarget.value) return
  try {
    await violationApi.delete(deleteTarget.value.id)
    toast.success('Pelanggaran dihapus')
    deleteTarget.value = null
    loadViolations()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openAddTypeModal() {
  editingType.value = null
  typeForm.value = {
    name: '',
    code: '',
    category: 'ringan',
    point_weight: 0,
    default_sanction: '',
    description: '',
  }
  typeFormError.value = ''
  showTypeModal.value = true
}

function openEditTypeModal(t) {
  editingType.value = t
  typeForm.value = {
    name: t.name,
    code: t.code || '',
    category: t.category,
    point_weight: t.point_weight || 0,
    default_sanction: t.default_sanction || '',
    description: t.description || '',
  }
  typeFormError.value = ''
  showTypeModal.value = true
}

async function submitType() {
  typeFormSubmitting.value = true
  typeFormError.value = ''
  try {
    if (editingType.value) {
      await violationTypeApi.update(editingType.value.id, {
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        category: typeForm.value.category,
        point_weight: typeForm.value.point_weight ?? 0,
        default_sanction: typeForm.value.default_sanction || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis pelanggaran berhasil diperbarui')
    } else {
      await violationTypeApi.create({
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        category: typeForm.value.category,
        point_weight: typeForm.value.point_weight ?? 0,
        default_sanction: typeForm.value.default_sanction || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis pelanggaran berhasil ditambahkan')
    }
    showTypeModal.value = false
    loadTypes()
    if (activeTab.value === 'list') violationTypes.value = (await violationTypeApi.getAll({ active_only: false })).data.data || []
  } catch (e) {
    typeFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    typeFormSubmitting.value = false
  }
}

function confirmDeleteType(t) {
  deleteTypeTarget.value = t
}

async function doDeleteType() {
  if (!deleteTypeTarget.value) return
  try {
    await violationTypeApi.delete(deleteTypeTarget.value.id)
    toast.success('Jenis pelanggaran dihapus')
    deleteTypeTarget.value = null
    loadTypes()
    if (activeTab.value === 'list') violationTypes.value = (await violationTypeApi.getAll({ active_only: false })).data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || e.response?.data?.message || 'Jenis sudah dipakai, tidak dapat dihapus')
  }
}

async function loadStudentPoints() {
  pointsLoading.value = true
  try {
    const res = await studentPointApi.getList({ page: pointsPagination.value.current_page, per_page: 15, search: pointSearch.value })
    studentPoints.value = res.data.data || []
    const meta = res.data.meta || {}
    pointsPagination.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat poin siswa')
  } finally {
    pointsLoading.value = false
  }
}
function goToPointsPage(page) {
  pointsPagination.value.current_page = page
  loadStudentPoints()
}

async function loadAchievements() {
  achievementsLoading.value = true
  try {
    const res = await achievementApi.getAll({ page: achievementsPagination.value.current_page, per_page: 15 })
    achievements.value = res.data.data || []
    const meta = res.data.meta || {}
    achievementsPagination.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat prestasi')
  } finally {
    achievementsLoading.value = false
  }
}
function goToAchievementsPage(page) {
  achievementsPagination.value.current_page = page
  loadAchievements()
}

async function loadAchievementTypes() {
  achievementTypesLoading.value = true
  try {
    const res = await achievementTypeApi.getAll({ active_only: false })
    achievementTypes.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat jenis prestasi')
  } finally {
    achievementTypesLoading.value = false
  }
}

async function loadThresholds() {
  thresholdsLoading.value = true
  try {
    const res = await pointThresholdApi.getAll({ active_only: false })
    thresholds.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat aturan tindakan')
  } finally {
    thresholdsLoading.value = false
  }
}

function openAddPrestasiModal() {
  editingPrestasi.value = null
  prestasiForm.value = { student_id: '', achievement_type_id: '', achievement_date: new Date().toISOString().slice(0, 10), notes: '' }
  prestasiFormError.value = ''
  if (students.value.length === 0) loadStudents()
  if (achievementTypes.value.length === 0) loadAchievementTypes()
  showPrestasiModal.value = true
}
function openEditPrestasiModal(a) {
  editingPrestasi.value = a
  prestasiForm.value = {
    student_id: String(a.student_id ?? a.student?.id ?? ''),
    achievement_type_id: String(a.achievement_type_id ?? a.achievement_type?.id ?? ''),
    achievement_date: a.achievement_date || '',
    notes: a.notes || '',
  }
  prestasiFormError.value = ''
  if (students.value.length === 0) loadStudents()
  if (achievementTypes.value.length === 0) loadAchievementTypes()
  showPrestasiModal.value = true
}
async function submitPrestasi() {
  prestasiFormSubmitting.value = true
  prestasiFormError.value = ''
  try {
    if (editingPrestasi.value) {
      await achievementApi.update(editingPrestasi.value.id, {
        achievement_type_id: prestasiForm.value.achievement_type_id,
        achievement_date: prestasiForm.value.achievement_date,
        notes: prestasiForm.value.notes,
      })
      toast.success('Prestasi diperbarui')
    } else {
      await achievementApi.create(prestasiForm.value)
      toast.success('Prestasi berhasil dicatat')
    }
    showPrestasiModal.value = false
    loadAchievements()
    if (activeTab.value === 'points') loadStudentPoints()
  } catch (e) {
    prestasiFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    prestasiFormSubmitting.value = false
  }
}

function openAddAchievementTypeModal() {
  editingAchievementType.value = null
  achievementTypeForm.value = { name: '', point_value: 10, category: '' }
  achievementTypeFormError.value = ''
  showAchievementTypeModal.value = true
}
function openEditAchievementTypeModal(t) {
  editingAchievementType.value = t
  achievementTypeForm.value = { name: t.name, point_value: t.point_value || 10, category: t.category || '' }
  achievementTypeFormError.value = ''
  showAchievementTypeModal.value = true
}
async function submitAchievementType() {
  achievementTypeFormSubmitting.value = true
  achievementTypeFormError.value = ''
  try {
    if (editingAchievementType.value) {
      await achievementTypeApi.update(editingAchievementType.value.id, achievementTypeForm.value)
      toast.success('Jenis prestasi diperbarui')
    } else {
      await achievementTypeApi.create(achievementTypeForm.value)
      toast.success('Jenis prestasi ditambahkan')
    }
    showAchievementTypeModal.value = false
    loadAchievementTypes()
    if (activeTab.value === 'prestasi') loadAchievements()
  } catch (e) {
    achievementTypeFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    achievementTypeFormSubmitting.value = false
  }
}
function confirmDeleteAchievementType(t) {
  deleteAchievementTypeTarget.value = t
}
async function doDeleteAchievementType() {
  if (!deleteAchievementTypeTarget.value) return
  try {
    await achievementTypeApi.delete(deleteAchievementTypeTarget.value.id)
    toast.success('Jenis prestasi dihapus')
    deleteAchievementTypeTarget.value = null
    loadAchievementTypes()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openAddThresholdModal() {
  editingThreshold.value = null
  thresholdForm.value = { point_min: 40, point_max: 999, action_name: '', description: '' }
  thresholdFormError.value = ''
  showThresholdModal.value = true
}
function openEditThresholdModal(t) {
  editingThreshold.value = t
  thresholdForm.value = { point_min: t.point_min, point_max: t.point_max, action_name: t.action_name || '', description: t.description || '' }
  thresholdFormError.value = ''
  showThresholdModal.value = true
}
async function submitThreshold() {
  thresholdFormSubmitting.value = true
  thresholdFormError.value = ''
  try {
    if (editingThreshold.value) {
      await pointThresholdApi.update(editingThreshold.value.id, thresholdForm.value)
      toast.success('Aturan tindakan diperbarui')
    } else {
      await pointThresholdApi.create(thresholdForm.value)
      toast.success('Aturan tindakan ditambahkan')
    }
    showThresholdModal.value = false
    loadThresholds()
    if (activeTab.value === 'points') loadStudentPoints()
  } catch (e) {
    thresholdFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    thresholdFormSubmitting.value = false
  }
}
function confirmDeleteThreshold(t) {
  deleteThresholdTarget.value = t
}
async function doDeleteThreshold() {
  if (!deleteThresholdTarget.value) return
  try {
    await pointThresholdApi.delete(deleteThresholdTarget.value.id)
    toast.success('Aturan tindakan dihapus')
    deleteThresholdTarget.value = null
    loadThresholds()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openLogActionModal(row) {
  logActionRow.value = row
  logActionForm.value = {
    student_id: row.student_id,
    point_threshold_id: row.required_action?.id || null,
    action_name: row.required_action?.action_name || '',
    action_date: new Date().toISOString().slice(0, 10),
    notes: '',
  }
  logActionFormError.value = ''
  showLogActionModal.value = true
}
async function submitLogAction() {
  logActionFormSubmitting.value = true
  logActionFormError.value = ''
  try {
    await studentActionLogApi.create(logActionForm.value)
    toast.success('Tindakan berhasil dicatat')
    showLogActionModal.value = false
  } catch (e) {
    logActionFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    logActionFormSubmitting.value = false
  }
}

function confirmDeleteAchievement(a) {
  deleteAchievementTarget.value = a
}
async function doDeleteAchievement() {
  if (!deleteAchievementTarget.value) return
  try {
    await achievementApi.delete(deleteAchievementTarget.value.id)
    toast.success('Prestasi dihapus')
    deleteAchievementTarget.value = null
    loadAchievements()
    if (activeTab.value === 'points') loadStudentPoints()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

async function loadInstitutionAndDefaults() {
  try {
    const [instRes, ayRes, semRes] = await Promise.all([
      institutionApi.getMy(),
      academicYearApi.getAll({ per_page: 50 }),
      semesterApi.getAll({ per_page: 100 }),
    ])
    institution.value = instRes.data?.data ?? instRes.data ?? null
    academicYears.value = ayRes.data?.data ?? ayRes.data ?? []
    semesters.value = semRes.data?.data ?? semRes.data ?? []
    if (institution.value?.active_academic_year_id) {
      filters.value.academic_year_id = String(institution.value.active_academic_year_id)
    }
    if (institution.value?.active_semester_id) {
      filters.value.semester_id = String(institution.value.active_semester_id)
    }
  } catch {
    institution.value = null
    academicYears.value = []
    semesters.value = []
  }
}

onMounted(async () => {
  await loadInstitutionAndDefaults()
  loadViolations()
  loadTypes()
})
</script>

<style scoped>
.violation-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
}
.page-header {
  margin-bottom: 1.5rem;
}
.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.header-icon {
  flex-shrink: 0;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}
.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}
.header-actions {
  margin-left: auto;
}
.section-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0;
}

.section-tab {
  padding: 0.6rem 1.25rem;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  cursor: pointer;
  transition: color 0.15s ease, border-color 0.15s ease;
}

.section-tab:hover {
  color: #1e293b;
}

.section-tab.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.main-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  align-items: center;
}
.main-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.main-tab:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.main-tab.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.35);
}
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  min-width: 140px;
}
.loading-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 0.75rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-state {
  text-align: center;
  padding: 2.5rem;
  background: #f8fafc;
  border-radius: 12px;
}
.empty-icon { margin-bottom: 1rem; color: #94a3b8; }
.empty-title { font-size: 1.1rem; margin: 0 0 0.5rem; }
.empty-desc { color: #64748b; margin: 0 0 1rem; font-size: 0.9rem; }
.btn-empty-cta { margin-top: 0.5rem; }
.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.85rem 1.1rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
.data-table tbody tr {
  transition: background 0.12s ease;
}
.data-table tbody tr:hover {
  background: #f8fafc;
}
.data-table th {
  background: #f8fafc;
  font-weight: 600;
  font-size: 0.85rem;
  color: #475569;
}
.student-name { display: block; font-weight: 500; }
.student-meta { font-size: 0.8rem; color: #64748b; }
.category-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}
.category-badge.ringan { background: #fef3c7; color: #92400e; }
.category-badge.sedang { background: #fed7aa; color: #c2410c; }
.category-badge.berat { background: #fecaca; color: #b91c1c; }
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
}
.status-badge.status-dicatat { background: #e0e7ff; color: #3730a3; }
.status-badge.status-sanksi_diberikan { background: #fef3c7; color: #92400e; }
.status-badge.status-follow_up { background: #d1fae5; color: #065f46; }
.status-badge.status-selesai { background: #d1fae5; color: #047857; }
.action-badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; background: #fef3c7; color: #92400e; }
.text-danger { color: #b91c1c; font-weight: 500; }
.text-muted { color: #64748b; }
.point-add { color: #b91c1c; font-weight: 600; }
.points-info-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
  border: 1px solid #c7d2fe;
  border-radius: 12px;
  font-size: 0.9rem;
  color: #3730a3;
  margin-bottom: 1.5rem;
  line-height: 1.55;
}
.points-info-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #4f46e5;
}
.points-info-text { flex: 1; min-width: 0; }
.points-summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}
.summary-card {
  padding: 1.25rem 1.25rem;
  border-radius: 14px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.summary-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.75rem;
}
.summary-icon-total { background: rgba(71, 85, 105, 0.12); color: #475569; }
.summary-icon-warning { background: rgba(146, 64, 14, 0.15); color: #b45309; }
.summary-icon-good { background: rgba(5, 150, 105, 0.15); color: #059669; }
.summary-card .summary-value { display: block; font-size: 1.85rem; font-weight: 700; line-height: 1.2; letter-spacing: -0.02em; }
.summary-card .summary-label { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; display: block; font-weight: 500; }
.card-total { background: #fff; color: #475569; border: 1px solid #e2e8f0; }
.card-warning { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); color: #92400e; border: 1px solid #fde68a; }
.card-good { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; border: 1px solid #a7f3d0; }
.table-points .th-num { text-align: center; }
.table-points .num-add { text-align: center; color: #b91c1c; font-weight: 600; }
.table-points .num-sub { text-align: center; color: #059669; font-weight: 600; }
.table-points .num-total { text-align: center; font-weight: 700; font-size: 1.05rem; }
.table-points .num-bank { text-align: center; color: #059669; font-weight: 600; }
.score-bad { color: #b91c1c; background: #fef2f2; }
.score-warn { color: #b45309; background: #fffbeb; }
.score-good { color: #047857; background: #ecfdf5; }
.score-normal { color: #475569; }
.badge-prestasi {
  display: inline-block;
  margin-top: 0.25rem;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 600;
  background: #059669;
  color: #fff;
}
.table-points tbody tr.row-good { background: #f0fdf4; }
.table-points tbody tr.row-warning { background: #fffbeb; }
.thresholds-hint { margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid #667eea; }
.action-buttons { display: flex; gap: 0.5rem; }
.btn-action {
  padding: 0.35rem 0.6rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-edit { background: #dbeafe; color: #1d4ed8; }
.btn-delete { background: #fee2e2; color: #b91c1c; }
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}
.pagination-info { font-size: 0.85rem; color: #64748b; }
.pagination-buttons { display: flex; align-items: center; gap: 0.5rem; }
.btn-page {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.page-num { font-size: 0.85rem; color: #64748b; }
.types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1rem;
}
.type-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  background: #fff;
}
.type-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.type-name { font-weight: 600; }
.type-body { margin-bottom: 0.75rem; }
.type-sanction, .type-point { margin: 0.25rem 0; font-size: 0.9rem; color: #64748b; }
.type-actions { display: flex; gap: 0.5rem; }
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 14px;
  max-width: 480px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.form-hint.threshold-form-hint {
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
  padding: 0.5rem 0;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
}
.modal-body { padding: 1.25rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 500; font-size: 0.9rem; }
.form-group input,
.form-group textarea,
.form-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.form-group textarea { min-height: 60px; resize: vertical; }
.error-message { color: #b91c1c; font-size: 0.85rem; margin-bottom: 0.75rem; }
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.btn-primary, .btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border: none;
  transition: opacity 0.15s ease, transform 0.1s ease;
}
.btn-primary:hover:not(:disabled) {
  opacity: 0.95;
  transform: translateY(-1px);
}
.btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.btn-secondary:hover { background: #e2e8f0; }
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1rem;
}
.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}
.type-card {
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.type-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  border-color: #cbd5e1;
}
</style>
