<template>
  <Layout>
    <div class="violation-page">
      <div class="toolbar" v-if="primaryActionLabel">
        <div class="toolbar-spacer"></div>
        <div class="header-actions">
          <button @click="primaryActionClick" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ primaryActionLabel }}</span>
          </button>
        </div>
      </div>

      <!-- Single-level tabs: semua dalam satu baris dengan pengelompokan visual -->
      <div class="nav-tabs-wrap">
        <nav class="nav-tabs" aria-label="Navigasi modul Pelanggaran">
          <div class="nav-tab-group">
            <button :class="['nav-tab', { active: activeTab === 'list' }]" @click="switchTab('list')">
              <span class="nav-tab-label">Daftar Pelanggaran</span>
              <span class="nav-tab-hint">Catat pelanggaran</span>
            </button>
            <button :class="['nav-tab', { active: activeTab === 'pending' }]" @click="switchTab('pending')">
              <span class="nav-tab-label">Usulan</span>
              <span class="nav-tab-hint">
                Menunggu BK
                <template v-if="pendingProposalCount"> ({{ pendingProposalCount }})</template>
              </span>
            </button>
            <button :class="['nav-tab', { active: activeTab === 'types' }]" @click="switchTab('types')">
              <span class="nav-tab-label">Jenis Pelanggaran</span>
              <span class="nav-tab-hint">Master jenis & bobot</span>
            </button>
            <button :class="['nav-tab', { active: activeTab === 'points' }]" @click="switchTab('points')">
              <span class="nav-tab-label">Poin Siswa</span>
              <span class="nav-tab-hint">Yang perlu tindakan</span>
            </button>
          </div>
          <span class="nav-tab-divider" aria-hidden="true"></span>
          <div class="nav-tab-group">
            <button :class="['nav-tab', { active: activeTab === 'prestasi' }]" @click="switchTab('prestasi')">
              <span class="nav-tab-label">Prestasi</span>
              <span class="nav-tab-hint">Poin pengurang</span>
            </button>
            <button :class="['nav-tab', { active: activeTab === 'achievement_types' }]" @click="switchTab('achievement_types')">
              <span class="nav-tab-label">Jenis Prestasi</span>
              <span class="nav-tab-hint">Master jenis</span>
            </button>
            <button :class="['nav-tab', { active: activeTab === 'thresholds' }]" @click="switchTab('thresholds')">
              <span class="nav-tab-label">Aturan Tindakan</span>
              <span class="nav-tab-hint">Skor → tindakan</span>
            </button>
          </div>
        </nav>
        <p v-if="tabDescription" class="tab-description">{{ tabDescription }}</p>
      </div>

      <main class="page-main">
      <!-- Tab: Daftar Pelanggaran / Usulan Piket -->
      <template v-if="activeTab === 'list' || activeTab === 'pending'">
        <div v-if="activeTab === 'pending'" class="pending-banner">
          Usulan dari guru piket atau wali kelas. Setujui agar poin masuk ke skor siswa, atau tolak jika tidak sesuai.
        </div>
        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari nama, NIS, NISN siswa..."
            class="search-input"
            @input="debounceLoadViolations"
          />
          <select
            v-if="activeTab === 'list'"
            v-model="filters.status"
            @change="loadViolations"
            class="filter-select"
          >
            <option value="">Semua Status</option>
            <option value="pending">Menunggu BK</option>
            <option value="dicatat">Dicatat</option>
            <option value="sanksi_diberikan">Sanksi Diberikan</option>
            <option value="follow_up">Follow Up</option>
            <option value="selesai">Selesai</option>
            <option value="ditolak">Ditolak</option>
          </select>
          <select v-model="filters.violation_type_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Jenis</option>
            <option v-for="t in violationTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.category }})</option>
          </select>
          <button type="button" class="filter-toggle" @click="showAdvancedFilters = !showAdvancedFilters" :aria-expanded="showAdvancedFilters">
            {{ showAdvancedFilters ? 'Sembunyikan filter' : 'Filter lanjutan' }}
            <span class="filter-toggle-icon">{{ showAdvancedFilters ? '▼' : '▶' }}</span>
          </button>
        </div>
        <div v-show="showAdvancedFilters" class="filters filters-advanced">
          <select v-model="filters.academic_year_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
          </select>
          <select v-model="filters.semester_id" @change="loadViolations" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <label class="filter-date-label"><span>Tgl mulai</span><input v-model="filters.date_from" type="date" class="filter-select" @change="loadViolations" /></label>
          <label class="filter-date-label"><span>Tgl akhir</span><input v-model="filters.date_to" type="date" class="filter-select" @change="loadViolations" /></label>
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
          <h3 class="empty-title">{{ activeTab === 'pending' ? 'Tidak ada usulan menunggu' : 'Belum ada catatan pelanggaran' }}</h3>
          <p class="empty-desc">
            {{ activeTab === 'pending'
              ? 'Guru piket belum mengajukan pelanggaran siswa, atau semua sudah diproses.'
              : 'Tambahkan pelanggaran siswa atau atur filter untuk melihat data.' }}
          </p>
          <button v-if="activeTab === 'list'" @click="openAddModal" class="btn-primary btn-empty-cta">Tambah Pelanggaran</button>
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
                  <span v-if="v.piket_incident" class="student-meta piket-source">
                    Dari piket: {{ v.piket_incident.type_label || v.piket_incident.incident_type }}
                    <template v-if="v.piket_incident.minutes_late"> · {{ v.piket_incident.minutes_late }} mnt</template>
                  </span>
                </td>
                <td>{{ v.violation_type?.name }}</td>
                <td><span :class="['category-badge', v.violation_type?.category]">{{ v.violation_type?.category }}</span></td>
                <td><span class="point-add">+{{ v.violation_type?.point_weight || 0 }}</span></td>
                <td>{{ v.sanction || '-' }}</td>
                <td><span :class="['status-badge', 'status-' + v.status]">{{ getStatusLabel(v.status) }}</span></td>
                <td>{{ v.reporter?.name }}</td>
                <td>
                  <div class="action-buttons">
                    <template v-if="v.status === 'pending'">
                      <button type="button" class="btn-sm-approve" @click="approveViolation(v)">Setujui</button>
                      <button type="button" class="btn-sm-reject" @click="openRejectModal(v)">Tolak</button>
                    </template>
                    <template v-else>
                      <button @click="openEditModal(v)" class="btn-action btn-edit" title="Edit" :disabled="v.status === 'ditolak'">✎</button>
                      <button @click="confirmDelete(v)" class="btn-action btn-delete" title="Hapus">🗑</button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="(activeTab === 'list' || activeTab === 'pending') && pagination.last_page > 1" class="pagination-bar">
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
            Skor = Σ pelanggaran − prestasi <strong>per periode</strong>. Setelah tindakan dicatat, pelanggaran baru atau skor yang naik akan membuka ulang status <strong>Menunggu</strong>.
          </div>
        </div>

        <div class="points-summary-cards">
          <div class="summary-card card-warning">
            <div class="summary-icon summary-icon-warning">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M5.07183 19H18.9282C20.4678 19 21.4301 17.3333 20.6603 16L13.7321 4C12.9623 2.66667 11.0378 2.66667 10.268 4L3.33978 16C2.56998 17.3333 3.53223 19 5.07183 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="summary-body">
              <span class="summary-value">{{ pointsPendingCount }}</span>
              <span class="summary-label">Menunggu tindakan</span>
            </div>
          </div>
          <div class="summary-card card-total">
            <div class="summary-icon summary-icon-total">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 16.7909 15.2091 15 13 15H5C2.79086 15 1 16.7909 1 19V21M23 21V19C22.9986 17.1771 21.765 15.5857 20 15.13M16 3.13C17.7699 3.58317 19.0078 5.17799 19.0078 7.005C19.0078 8.83201 17.7699 10.4268 16 10.88M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="summary-body">
              <span class="summary-value">{{ pointsPagination.total ?? studentPoints.length }}</span>
              <span class="summary-label">Siswa skor &gt; 0</span>
            </div>
          </div>
        </div>

        <div class="filters filters-inline points-filters">
          <input v-model="pointSearch" type="text" placeholder="Cari nama, NIS, NISN..." class="search-input" @input="debounceLoadStudentPoints" />
          <select v-model="pointFilters.academic_year_id" class="filter-select" @change="onPointPeriodChange">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
          </select>
          <select v-model="pointFilters.semester_id" class="filter-select" @change="onPointPeriodChange">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
          <label class="filter-chip" :class="{ active: pointFilters.pending_only }">
            <input v-model="pointFilters.pending_only" type="checkbox" @change="onPointPeriodChange" />
            Hanya menunggu
          </label>
        </div>

        <div v-if="pointsLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat poin siswa...</p></div>
        <div v-else-if="studentPoints.length === 0" class="empty-state">
          <h3 class="empty-title">Tidak ada siswa dengan skor &gt; 0</h3>
          <p class="empty-desc">Tidak ada data untuk periode ini, atau filter menyembunyikan semua baris. Catat pelanggaran di tab Daftar untuk melihat skor.</p>
        </div>
        <div v-else class="points-list">
          <article
            v-for="row in studentPoints"
            :key="row.student_id"
            class="points-row"
            :class="{
              'is-pending': row.action_pending,
              'is-done': row.action_fulfilled,
              'is-no-rule': !row.required_action && row.total_points > 0,
              'is-expanded': expandedPointStudentId === row.student_id,
            }"
          >
            <div class="points-row-top">
              <div class="points-row-main">
                <div class="points-student">
                  <span class="student-name">{{ row.student?.name }}</span>
                  <span class="student-meta">
                    {{ row.student?.nisn || row.student?.nis || '—' }}
                    · {{ row.violations_count || row.violations?.length || 0 }} pelanggaran
                  </span>
                  <p v-if="row.action_pending && row.reopen_reason" class="reopen-hint">
                    {{ reopenReasonLabel(row) }}
                  </p>
                </div>

                <div class="points-score-block">
                  <div
                    class="score-pill"
                    :class="row.total_points > 40 ? 'score-bad' : row.total_points > 20 ? 'score-warn' : 'score-normal'"
                  >
                    <span class="score-pill-label">Skor</span>
                    <span class="score-pill-value">{{ row.total_points }}</span>
                  </div>
                  <div class="score-breakdown" title="Pelanggaran − Prestasi">
                    <span class="bd-add">+{{ row.violation_points }}</span>
                    <span class="bd-sep">−</span>
                    <span class="bd-sub">{{ row.achievement_points }}</span>
                  </div>
                  <span v-if="row.new_points_since_action > 0" class="new-points-badge">
                    +{{ row.new_points_since_action }} sejak tindakan
                  </span>
                </div>

                <div class="points-action-info">
                  <template v-if="row.required_action">
                    <span class="action-name-line">{{ row.required_action.action_name }}</span>
                    <span class="action-range-line">Skor {{ row.required_action.point_min }}–{{ row.required_action.point_max }}</span>
                  </template>
                  <template v-else>
                    <span class="action-name-line muted">Belum ada aturan</span>
                    <span class="action-range-line">Atur di tab Aturan Tindakan</span>
                  </template>
                  <span
                    class="status-chip"
                    :class="{
                      pending: row.action_pending,
                      done: row.action_fulfilled,
                      none: !row.action_pending && !row.action_fulfilled,
                    }"
                  >
                    {{ row.action_pending ? 'Menunggu' : row.action_fulfilled ? 'Sudah ditindak' : '—' }}
                  </span>
                </div>
              </div>

              <div class="points-row-actions">
                <button
                  type="button"
                  class="btn-points-primary"
                  :class="{ secondary: row.action_fulfilled }"
                  @click="openLogActionModal(row, !!row.action_fulfilled)"
                >
                  {{ row.action_fulfilled ? 'Catat lagi' : 'Catat tindakan' }}
                </button>
                <button type="button" class="btn-points-ghost" @click="togglePointExpand(row.student_id)">
                  {{ expandedPointStudentId === row.student_id ? 'Sembunyikan' : 'Detail' }}
                </button>
                <button type="button" class="btn-points-ghost" @click="openActionHistory(row)">
                  Riwayat
                </button>
              </div>
            </div>

            <div v-if="expandedPointStudentId === row.student_id" class="points-row-detail">
              <h4 class="detail-title">Pelanggaran periode ini ({{ row.violations_count || row.violations?.length || 0 }})</h4>
              <div v-if="!(row.violations && row.violations.length)" class="detail-empty">
                Tidak ada detail pelanggaran.
              </div>
              <ul v-else class="violation-mini-list">
                <li v-for="v in row.violations" :key="v.id" class="violation-mini-item">
                  <div class="vmi-main">
                    <strong>{{ v.violation_type?.name || 'Pelanggaran' }}</strong>
                    <span class="vmi-date">{{ formatDate(v.violation_date) }}</span>
                  </div>
                  <div class="vmi-meta">
                    <span class="vmi-points">+{{ v.point_weight ?? v.violation_type?.point_weight ?? 0 }}</span>
                    <span :class="['status-badge', 'status-' + v.status]">{{ getStatusLabel(v.status) }}</span>
                  </div>
                </li>
              </ul>

              <h4 class="detail-title">Prestasi periode ini ({{ row.achievements_count || row.achievements?.length || 0 }})</h4>
              <div v-if="!(row.achievements && row.achievements.length)" class="detail-empty">
                Belum ada prestasi pada periode ini.
              </div>
              <ul v-else class="violation-mini-list">
                <li v-for="a in row.achievements" :key="'ach-' + a.id" class="violation-mini-item achievement-mini-item">
                  <div class="vmi-main">
                    <strong>{{ a.achievement_type?.name || 'Prestasi' }}</strong>
                    <span class="vmi-date">{{ formatDate(a.achievement_date) }}</span>
                  </div>
                  <div class="vmi-meta">
                    <span class="vmi-points vmi-points-good">−{{ a.point_value }}</span>
                  </div>
                </li>
              </ul>

              <p v-if="row.latest_action" class="detail-last-action">
                Tindakan terakhir: <strong>{{ row.latest_action.action_name }}</strong>
                ({{ formatDate(row.latest_action.action_date) }})
                <template v-if="row.score_at_action != null"> · skor saat itu {{ row.score_at_action }}</template>
              </p>
            </div>
          </article>
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
        <div class="filters filters-inline">
          <input
            v-model="achievementFilters.search"
            type="text"
            placeholder="Cari nama, NIS, NISN siswa..."
            class="search-input"
            @input="debounceLoadAchievements"
          />
          <select v-model="achievementFilters.status" class="filter-select" @change="onAchievementFilterChange">
            <option value="">Semua status</option>
            <option value="pending">Menunggu</option>
            <option value="dicatat">Dicatat</option>
            <option value="ditolak">Ditolak</option>
          </select>
          <select v-model="achievementFilters.achievement_type_id" class="filter-select" @change="onAchievementFilterChange">
            <option value="">Semua jenis</option>
            <option v-for="t in achievementTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
          </select>
          <select v-model="achievementFilters.academic_year_id" class="filter-select" @change="onAchievementFilterChange">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
          </select>
          <select v-model="achievementFilters.semester_id" class="filter-select" @change="onAchievementFilterChange">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
        </div>
        <div v-if="achievementsLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat prestasi...</p></div>
        <div v-else-if="achievements.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada prestasi</h3>
          <p class="empty-desc">
            Prestasi mengurangi skor pelanggaran pada periode terpilih.
            <template v-if="achievementTypes.length === 0"> Buat Jenis Prestasi dulu sebelum mencatat.</template>
          </p>
          <button
            v-if="achievementTypes.length === 0"
            @click="switchTab('achievement_types'); openAddAchievementTypeModal()"
            class="btn-primary btn-empty-cta"
          >Tambah Jenis Prestasi</button>
          <button v-else @click="openAddPrestasiModal" class="btn-primary btn-empty-cta">Tambah Prestasi</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Jenis Prestasi</th>
                <th>Poin</th>
                <th>Status</th>
                <th>Pemberi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in achievements" :key="a.id">
                <td>{{ formatDate(a.achievement_date) }}</td>
                <td>
                  <span class="student-name">{{ a.student?.name }}</span>
                  <span class="student-meta">{{ a.student?.nisn || a.student?.nis || '—' }}</span>
                </td>
                <td>{{ a.achievement_type?.name }}</td>
                <td class="num-sub">+{{ a.point_value }}</td>
                <td><span class="status-badge" :class="`status-${a.status || 'dicatat'}`">{{ a.status || 'dicatat' }}</span></td>
                <td>{{ a.giver?.name }}</td>
                <td>
                  <template v-if="a.status === 'pending'">
                    <button type="button" class="btn-action btn-edit" @click="approveAchievement(a)">Setujui</button>
                    <button type="button" class="btn-action btn-delete" @click="openRejectAchievement(a)">Tolak</button>
                  </template>
                  <template v-else>
                    <button @click="openEditPrestasiModal(a)" class="btn-action btn-edit">Edit</button>
                    <button @click="confirmDeleteAchievement(a)" class="btn-action btn-delete">Hapus</button>
                  </template>
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
      </main>

      <!-- Modal: Tolak usulan pelanggaran -->
      <div v-if="showRejectModal" class="modal-overlay" @click="showRejectModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Tolak Usulan</h3>
            <button @click="showRejectModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitReject" class="modal-body">
            <p class="reject-hint">
              {{ rejectTarget?.student?.name }} — {{ rejectTarget?.violation_type?.name }}
            </p>
            <div class="form-group">
              <label>Alasan penolakan *</label>
              <textarea v-model="rejectNotes" rows="3" required placeholder="Jelaskan alasan penolakan"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showRejectModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menolak...' : 'Tolak Usulan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tolak usulan prestasi -->
      <div v-if="showRejectAchievementModal" class="modal-overlay" @click="showRejectAchievementModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Tolak Usulan Prestasi</h3>
            <button @click="showRejectAchievementModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitRejectAchievement" class="modal-body">
            <p class="reject-hint">
              {{ rejectAchievementTarget?.student?.name }} — {{ rejectAchievementTarget?.achievement_type?.name }}
            </p>
            <div class="form-group">
              <label>Alasan penolakan *</label>
              <textarea v-model="rejectAchievementNotes" rows="3" required placeholder="Jelaskan alasan penolakan untuk wali kelas"></textarea>
            </div>
            <div v-if="rejectAchievementError" class="error-message">{{ rejectAchievementError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showRejectAchievementModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="rejectAchievementSubmitting" class="btn-primary">
                {{ rejectAchievementSubmitting ? 'Menolak...' : 'Tolak Usulan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

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
        <div class="modal-content form-modal action-form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ logActionRow?.action_fulfilled ? 'Catat Tindakan Lanjutan' : 'Catat Tindakan' }}</h3>
            <button @click="showLogActionModal = false" class="btn-close" type="button" aria-label="Tutup">×</button>
          </div>
          <form @submit.prevent="submitLogAction" class="modal-body">
            <div v-if="logActionRow" class="action-student-card">
              <div class="asc-top">
                <div>
                  <p class="asc-name">{{ logActionRow.student?.name }}</p>
                  <p class="asc-meta">{{ logActionRow.student?.nisn || logActionRow.student?.nis || '—' }}</p>
                </div>
                <div
                  class="asc-score"
                  :class="logActionRow.total_points > 40 ? 'score-bad' : logActionRow.total_points > 20 ? 'score-warn' : 'score-normal'"
                >
                  <span>Skor</span>
                  <strong>{{ logActionRow.total_points }}</strong>
                </div>
              </div>
              <div class="asc-breakdown">
                <span><em>+{{ logActionRow.violation_points }}</em> pelanggaran</span>
                <span><em>−{{ logActionRow.achievement_points }}</em> prestasi</span>
              </div>
              <div v-if="logActionRow.required_action" class="asc-required">
                <span class="asc-required-label">Tindakan wajib</span>
                <strong>{{ logActionRow.required_action.action_name }}</strong>
                <span class="asc-required-range">Rentang skor {{ logActionRow.required_action.point_min }}–{{ logActionRow.required_action.point_max }}</span>
              </div>
              <p v-else class="asc-no-rule">Tidak ada aturan threshold untuk skor ini — Anda tetap dapat mencatat tindakan manual.</p>
              <p v-if="logActionRow.action_pending && logActionRow.reopen_reason" class="asc-reopen">
                {{ reopenReasonLabel(logActionRow) }}
              </p>
              <p class="asc-close-hint">
                Menyimpan akan menutup pelanggaran dengan tanggal ≤ tanggal tindakan. Pelanggaran setelah tanggal itu tetap terbuka.
              </p>
            </div>

            <div class="form-group">
              <label for="log-action-name">Nama tindakan *</label>
              <input
                id="log-action-name"
                v-model="logActionForm.action_name"
                type="text"
                required
                placeholder="Contoh: Panggilan orang tua"
              />
            </div>
            <div class="form-group">
              <label for="log-action-date">Tanggal dilaksanakan *</label>
              <input id="log-action-date" v-model="logActionForm.action_date" type="date" required />
            </div>
            <div class="form-group">
              <label for="log-action-notes">Catatan hasil</label>
              <textarea
                id="log-action-notes"
                v-model="logActionForm.notes"
                rows="3"
                placeholder="Ringkasan hasil pertemuan, kesepakatan, atau tindak lanjut..."
              ></textarea>
            </div>

            <fieldset class="resolve-options">
              <legend>Status pelanggaran terkait</legend>
              <label class="resolve-option" :class="{ selected: logActionForm.resolution === 'follow_up' }">
                <input v-model="logActionForm.resolution" type="radio" value="follow_up" />
                <span class="resolve-option-body">
                  <strong>Follow Up</strong>
                  <small>Tindakan sudah dilakukan; pelanggaran masih dipantau.</small>
                </span>
              </label>
              <label class="resolve-option" :class="{ selected: logActionForm.resolution === 'selesai' }">
                <input v-model="logActionForm.resolution" type="radio" value="selesai" />
                <span class="resolve-option-body">
                  <strong>Selesai</strong>
                  <small>Tutup semua pelanggaran terbuka pada periode ini.</small>
                </span>
              </label>
            </fieldset>

            <div v-if="logActionFormError" class="error-message">{{ logActionFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showLogActionModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="logActionFormSubmitting" class="btn-primary">
                {{ logActionFormSubmitting ? 'Menyimpan...' : 'Simpan tindakan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Riwayat Tindakan -->
      <div v-if="showActionHistoryModal" class="modal-overlay" @click="showActionHistoryModal = false">
        <div class="modal-content form-modal modal-wide" @click.stop>
          <div class="modal-header">
            <h3>Riwayat Tindakan</h3>
            <button @click="showActionHistoryModal = false" class="btn-close" type="button" aria-label="Tutup">×</button>
          </div>
          <div class="modal-body">
            <div v-if="actionHistoryStudent" class="action-student-card">
              <p class="asc-name">{{ actionHistoryStudent.student?.name }}</p>
              <p class="asc-meta">{{ actionHistoryStudent.student?.nisn || actionHistoryStudent.student?.nis || '—' }} · Skor {{ actionHistoryStudent.total_points }}</p>
            </div>
            <div v-if="actionHistoryLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat riwayat...</p></div>
            <div v-else-if="actionHistory.length === 0" class="empty-state" style="padding: 1.5rem;">
              <p class="empty-desc" style="margin: 0;">Belum ada catatan tindakan untuk siswa ini pada periode terpilih.</p>
            </div>
            <ul v-else class="history-list">
              <li v-for="log in actionHistory" :key="log.id" class="history-item">
                <div class="history-item-top">
                  <strong>{{ log.action_name }}</strong>
                  <time>{{ formatDate(log.action_date) }}</time>
                </div>
                <p v-if="log.notes" class="history-notes">{{ log.notes }}</p>
                <p class="history-meta">Dicatat oleh {{ log.recorder?.name || '—' }}</p>
              </li>
            </ul>
            <div class="modal-footer">
              <button type="button" @click="showActionHistoryModal = false" class="btn-secondary">Tutup</button>
            </div>
          </div>
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
import { useReferenceDataStore } from '@/stores/referenceData'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const activeTab = ref('list')

const primaryActionLabel = computed(() => {
  const labels = {
    list: 'Tambah Pelanggaran',
    types: 'Tambah Jenis Pelanggaran',
    points: null,
    prestasi: 'Tambah Prestasi',
    achievement_types: 'Tambah Jenis Prestasi',
    thresholds: 'Tambah Aturan Tindakan',
  }
  return labels[activeTab.value] || null
})

function primaryActionClick() {
  if (activeTab.value === 'list') openAddModal()
  else if (activeTab.value === 'types') openAddTypeModal()
  else if (activeTab.value === 'prestasi') openAddPrestasiModal()
  else if (activeTab.value === 'achievement_types') openAddAchievementTypeModal()
  else if (activeTab.value === 'thresholds') openAddThresholdModal()
}

const tabDescription = computed(() => {
  const desc = {
    list: 'Catat setiap pelanggaran siswa di sini. Data dipakai untuk menghitung poin di tab Poin Siswa. Usulan piket menunggu tidak dihitung sampai disetujui.',
    pending: 'Usulan pelanggaran dari guru piket. Setujui agar poin masuk, atau tolak dengan alasan.',
    types: 'Atur jenis pelanggaran (mis. Terlambat, Tidak pakai atribut) beserta kategori dan bobot poin.',
    points: 'Skor = Σ pelanggaran − prestasi per periode. Pelanggaran baru setelah tindakan (atau skor naik) membuka ulang status Menunggu. Gunakan Detail untuk melihat tiap pelanggaran.',
    prestasi: 'Prestasi mengurangi skor pelanggaran pada periode yang sama. Catat di sini setelah Jenis Prestasi tersedia.',
    achievement_types: 'Atur jenis prestasi dan nilai poin pengurang.',
    thresholds: 'Atur rentang skor pelanggaran dan tindakan wajib (mis. skor 40–999 = Panggilan orang tua).',
  }
  return desc[activeTab.value] || ''
})

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'list' || tab === 'pending') {
    pagination.value.current_page = 1
    loadViolations()
  } else if (tab === 'types') loadTypes()
  else if (tab === 'points') loadStudentPoints()
  else if (tab === 'prestasi') loadAchievements()
  else if (tab === 'achievement_types') loadAchievementTypes()
  else if (tab === 'thresholds') loadThresholds()
}
const loading = ref(true)
const typesLoading = ref(false)
const violations = ref([])
const violationTypes = ref([])
const students = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const pendingProposalCount = ref(0)
const showRejectModal = ref(false)
const rejectTarget = ref(null)
const rejectNotes = ref('')

const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const showAdvancedFilters = ref(false)
const institution = ref(null)
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
const pointFilters = ref({
  academic_year_id: '',
  semester_id: '',
  pending_only: false,
})
const studentPoints = ref([])
const pointsLoading = ref(false)
const pointsPagination = ref({ current_page: 1, last_page: 1, total: 0, pending_count: 0 })
const pointsPendingCount = computed(() => pointsPagination.value.pending_count ?? studentPoints.value.filter((r) => r.action_pending).length)
const expandedPointStudentId = ref(null)
let pointsDebounceTimer = null
function debounceLoadStudentPoints() {
  clearTimeout(pointsDebounceTimer)
  pointsDebounceTimer = setTimeout(() => {
    pointsPagination.value.current_page = 1
    loadStudentPoints()
  }, 300)
}
function onPointPeriodChange() {
  pointsPagination.value.current_page = 1
  expandedPointStudentId.value = null
  loadStudentPoints()
}
function togglePointExpand(studentId) {
  expandedPointStudentId.value = expandedPointStudentId.value === studentId ? null : studentId
}
function reopenReasonLabel(row) {
  const reasons = {
    never_acted: 'Belum pernah dicatat tindakan untuk threshold ini.',
    score_increased: `Skor naik setelah tindakan terakhir${row.score_at_action != null ? ` (dari ${row.score_at_action} → ${row.total_points})` : ''}.`,
    new_violations: row.open_after_action_count
      ? `Ada ${row.open_after_action_count} pelanggaran baru setelah tindakan terakhir.`
      : 'Ada pelanggaran baru setelah tindakan terakhir.',
    no_threshold: 'Belum ada aturan threshold — tindakan manual diperlukan.',
  }
  return reasons[row.reopen_reason] || 'Perlu dicatat tindakan.'
}

// Prestasi
const achievements = ref([])
const achievementsLoading = ref(false)
const achievementsPagination = ref({ current_page: 1, last_page: 1 })
const achievementFilters = ref({
  search: '',
  status: '',
  achievement_type_id: '',
  academic_year_id: '',
  semester_id: '',
})
const achievementTypes = ref([])
const achievementTypesLoading = ref(false)
const showPrestasiModal = ref(false)
const editingPrestasi = ref(null)
const prestasiForm = ref({ student_id: '', achievement_type_id: '', achievement_date: '', notes: '' })
const prestasiFormError = ref('')
const prestasiFormSubmitting = ref(false)
const rejectAchievementTarget = ref(null)
const showRejectAchievementModal = ref(false)
const rejectAchievementNotes = ref('')
const rejectAchievementSubmitting = ref(false)
const rejectAchievementError = ref('')
const showAchievementTypeModal = ref(false)
const editingAchievementType = ref(null)
const achievementTypeForm = ref({ name: '', point_value: 10, category: '' })
const achievementTypeFormError = ref('')
const achievementTypeFormSubmitting = ref(false)
let achievementsDebounceTimer = null
function debounceLoadAchievements() {
  clearTimeout(achievementsDebounceTimer)
  achievementsDebounceTimer = setTimeout(() => {
    achievementsPagination.value.current_page = 1
    loadAchievements()
  }, 300)
}
function onAchievementFilterChange() {
  achievementsPagination.value.current_page = 1
  loadAchievements()
}

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
const logActionForm = ref({
  student_id: '',
  point_threshold_id: null,
  action_name: '',
  action_date: '',
  notes: '',
  resolution: 'follow_up',
})
const logActionFormError = ref('')
const logActionFormSubmitting = ref(false)

// Riwayat tindakan
const showActionHistoryModal = ref(false)
const actionHistoryStudent = ref(null)
const actionHistory = ref([])
const actionHistoryLoading = ref(false)

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
  pending: 'Menunggu BK',
  dicatat: 'Dicatat',
  sanksi_diberikan: 'Sanksi Diberikan',
  follow_up: 'Follow Up',
  selesai: 'Selesai',
  ditolak: 'Ditolak',
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
    if (activeTab.value === 'pending') {
      params.status = 'pending'
      delete params.academic_year_id
      delete params.semester_id
    } else if (!params.status) {
      delete params.status
    }
    if (!params.violation_type_id) delete params.violation_type_id
    if (!params.academic_year_id) delete params.academic_year_id
    if (!params.semester_id) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search

    const res = await violationApi.getAll(params)
    violations.value = res.data.data || []
    const meta = res.data.meta || {}
    const metaExtra = res.data.meta_extra || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
    if (typeof metaExtra.pending_count === 'number') {
      pendingProposalCount.value = metaExtra.pending_count
    }
  } catch (e) {
    toast.error('Gagal memuat pelanggaran', e.formattedMessage || 'Data pelanggaran tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function approveViolation(v) {
  try {
    await violationApi.approve(v.id)
    toast.success('Usulan disetujui — poin siswa diperbarui')
    await loadViolations()
  } catch (e) {
    toast.error('Gagal menyetujui', e.formattedMessage || e.response?.data?.message || 'Coba lagi.')
  }
}

function openRejectModal(v) {
  rejectTarget.value = v
  rejectNotes.value = ''
  formError.value = ''
  showRejectModal.value = true
}

async function submitReject() {
  if (!rejectTarget.value) return
  formSubmitting.value = true
  formError.value = ''
  try {
    await violationApi.reject(rejectTarget.value.id, { review_notes: rejectNotes.value })
    toast.success('Usulan ditolak')
    showRejectModal.value = false
    await loadViolations()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menolak usulan.'
  } finally {
    formSubmitting.value = false
  }
}

async function loadTypes() {
  typesLoading.value = true
  try {
    const res = await violationTypeApi.getAll({ active_only: false })
    violationTypes.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jenis pelanggaran', e.formattedMessage || 'Data jenis pelanggaran tidak dapat dimuat. Periksa koneksi dan coba lagi.')
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
  if (v.status === 'pending' || v.status === 'ditolak') {
    toast.error('Tidak dapat diedit', v.status === 'pending'
      ? 'Usulan menunggu harus disetujui atau ditolak terlebih dahulu.'
      : 'Pelanggaran yang ditolak tidak dapat diubah.')
    return
  }
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
    toast.error('Gagal menghapus pelanggaran', e.formattedMessage || 'Data tidak dapat dihapus. Coba lagi.')
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
    const params = {
      page: pointsPagination.value.current_page,
      per_page: 15,
      search: pointSearch.value || undefined,
      needs_action: 1,
      academic_year_id: pointFilters.value.academic_year_id,
      semester_id: pointFilters.value.semester_id,
    }
    if (pointFilters.value.pending_only) {
      params.pending_only = 1
    }
    const res = await studentPointApi.getList(params)
    studentPoints.value = res.data.data || []
    const meta = res.data.meta || {}
    pointsPagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      total: meta.total ?? 0,
      pending_count: meta.pending_count ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat poin siswa', e.formattedMessage || 'Data poin siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.')
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
    if (achievementTypes.value.length === 0) {
      loadAchievementTypes()
    }
    const params = {
      page: achievementsPagination.value.current_page,
      per_page: 15,
      search: achievementFilters.value.search || undefined,
      status: achievementFilters.value.status || undefined,
      achievement_type_id: achievementFilters.value.achievement_type_id || undefined,
      academic_year_id: achievementFilters.value.academic_year_id,
      semester_id: achievementFilters.value.semester_id,
    }
    const res = await achievementApi.getAll(params)
    achievements.value = res.data.data || []
    const meta = res.data.meta || {}
    achievementsPagination.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error('Gagal memuat prestasi', e.formattedMessage || 'Data prestasi tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    achievementsLoading.value = false
  }
}
function goToAchievementsPage(page) {
  achievementsPagination.value.current_page = page
  loadAchievements()
}

async function approveAchievement(a) {
  try {
    await achievementApi.approve(a.id)
    toast.success('Usulan prestasi disetujui')
    await loadAchievements()
  } catch (e) {
    toast.error('Gagal menyetujui', e.formattedMessage || e.response?.data?.message || 'Coba lagi.')
  }
}

function openRejectAchievement(a) {
  rejectAchievementTarget.value = a
  rejectAchievementNotes.value = ''
  rejectAchievementError.value = ''
  showRejectAchievementModal.value = true
}

async function submitRejectAchievement() {
  if (!rejectAchievementTarget.value) return
  rejectAchievementSubmitting.value = true
  rejectAchievementError.value = ''
  try {
    await achievementApi.reject(rejectAchievementTarget.value.id, {
      review_notes: rejectAchievementNotes.value,
    })
    toast.success('Usulan prestasi ditolak')
    showRejectAchievementModal.value = false
    await loadAchievements()
  } catch (e) {
    rejectAchievementError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menolak usulan.'
  } finally {
    rejectAchievementSubmitting.value = false
  }
}

async function loadAchievementTypes() {
  achievementTypesLoading.value = true
  try {
    const res = await achievementTypeApi.getAll({ active_only: false })
    achievementTypes.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jenis prestasi', e.formattedMessage || 'Data jenis prestasi tidak dapat dimuat. Periksa koneksi dan coba lagi.')
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
    toast.error('Gagal memuat aturan tindakan', e.formattedMessage || 'Data aturan tindakan tidak dapat dimuat. Periksa koneksi dan coba lagi.')
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
    toast.error('Gagal menghapus jenis prestasi', e.formattedMessage || 'Data tidak dapat dihapus. Coba lagi.')
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
    toast.error('Gagal menghapus aturan tindakan', e.formattedMessage || 'Data tidak dapat dihapus. Coba lagi.')
  }
}

function openLogActionModal(row, isAgain = false) {
  logActionRow.value = row
  logActionForm.value = {
    student_id: row.student_id,
    point_threshold_id: row.required_action?.id || null,
    action_name: row.required_action?.action_name || row.latest_action?.action_name || '',
    action_date: new Date().toISOString().slice(0, 10),
    notes: '',
    resolution: 'follow_up',
    academic_year_id: pointFilters.value.academic_year_id || undefined,
    semester_id: pointFilters.value.semester_id || undefined,
  }
  if (!logActionForm.value.action_name && !isAgain) {
    logActionForm.value.action_name = 'Tindak lanjut pembinaan'
  }
  logActionFormError.value = ''
  showLogActionModal.value = true
}
async function submitLogAction() {
  logActionFormSubmitting.value = true
  logActionFormError.value = ''
  const markResolved = logActionForm.value.resolution === 'selesai'
  try {
    await studentActionLogApi.create({
      student_id: logActionForm.value.student_id,
      point_threshold_id: logActionForm.value.point_threshold_id,
      action_name: logActionForm.value.action_name,
      action_date: logActionForm.value.action_date,
      notes: logActionForm.value.notes || undefined,
      mark_violations_resolved: markResolved,
      academic_year_id: pointFilters.value.academic_year_id || undefined,
      semester_id: pointFilters.value.semester_id || undefined,
    })
    toast.success(
      markResolved
        ? 'Tindakan dicatat; pelanggaran periode ini ditandai selesai'
        : 'Tindakan dicatat; pelanggaran terkait diubah ke Follow Up'
    )
    showLogActionModal.value = false
    loadStudentPoints()
    if (activeTab.value === 'list') loadViolations()
  } catch (e) {
    logActionFormError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    logActionFormSubmitting.value = false
  }
}

async function openActionHistory(row) {
  actionHistoryStudent.value = row
  actionHistory.value = []
  showActionHistoryModal.value = true
  actionHistoryLoading.value = true
  try {
    const params = { per_page: 50 }
    if (pointFilters.value.academic_year_id) params.academic_year_id = pointFilters.value.academic_year_id
    if (pointFilters.value.semester_id) params.semester_id = pointFilters.value.semester_id
    const res = await studentActionLogApi.getByStudent(row.student_id, params)
    const list = res.data?.data ?? res.data ?? []
    actionHistory.value = Array.isArray(list) ? list : []
  } catch (e) {
    toast.error('Gagal memuat riwayat tindakan', e.formattedMessage || 'Coba lagi.')
    actionHistory.value = []
  } finally {
    actionHistoryLoading.value = false
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
    toast.error('Gagal menghapus prestasi', e.formattedMessage || 'Data tidak dapat dihapus. Coba lagi.')
  }
}

async function loadInstitutionAndDefaults() {
  try {
    const [instRes, semRes] = await Promise.all([
      institutionApi.getMy(),
      semesterApi.getAll({ per_page: 100 }),
    ])
    institution.value = instRes.data?.data ?? instRes.data ?? null
    semesters.value = semRes.data?.data ?? semRes.data ?? []
    await referenceStore.getAcademicYears()
    if (institution.value?.active_academic_year_id) {
      const yearId = String(institution.value.active_academic_year_id)
      filters.value.academic_year_id = yearId
      pointFilters.value.academic_year_id = yearId
      achievementFilters.value.academic_year_id = yearId
    }
    if (institution.value?.active_semester_id) {
      const semId = String(institution.value.active_semester_id)
      filters.value.semester_id = semId
      pointFilters.value.semester_id = semId
      achievementFilters.value.semester_id = semId
    }
  } catch {
    institution.value = null
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
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-bottom: 0.75rem;
}
.toolbar-spacer { flex: 1; }
.page-header {
  margin-bottom: 1.25rem;
  padding: 1.25rem 1.5rem;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #e2e8f0;
}
.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
  flex-wrap: wrap;
}
.header-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
}
.header-icon {
  flex-shrink: 0;
}
.page-title {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 0 0 0.3rem 0;
  color: #0f172a;
  letter-spacing: -0.02em;
}
.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.4;
}
.header-actions {
  margin-left: auto;
}
.page-main {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #e2e8f0;
  padding: 1.25rem 1.5rem;
}
.nav-tabs-wrap {
  margin-bottom: 1.5rem;
}
.nav-tabs {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 2px solid #e2e8f0;
}
.nav-tab-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}
.nav-tab-divider {
  width: 1px;
  height: 28px;
  background: #cbd5e1;
  margin: 0 0.25rem;
  flex-shrink: 0;
}
.nav-tab {
  display: inline-flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 0.55rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
  text-align: left;
  color: #475569;
}
.nav-tab:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #0f172a;
}
.nav-tab.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
}
.nav-tab .nav-tab-label {
  font-size: 0.9rem;
  font-weight: 600;
  line-height: 1.3;
}
.nav-tab .nav-tab-hint {
  font-size: 0.7rem;
  opacity: 0.85;
  margin-top: 0.15rem;
  color: #64748b;
}
.nav-tab.active .nav-tab-hint {
  opacity: 0.92;
  color: rgba(255,255,255,0.95);
}
.tab-description {
  margin: 0.85rem 0 0;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #475569;
  background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 10px;
  border-left: 4px solid #059669;
  line-height: 1.5;
}
.filter-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
}
.filter-toggle:hover {
  background: #f1f5f9;
  color: #475569;
}
.filter-toggle-icon {
  font-size: 0.7rem;
}
.filters-advanced {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px dashed #e2e8f0;
}
.filter-date-label {
  display: inline-flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.75rem;
  color: #64748b;
}
.filter-date-label span { margin-right: 0.25rem; }
.filter-date-label .filter-select { min-width: 130px; }
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.55rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
}
.search-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.filter-select {
  padding: 0.55rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  min-width: 140px;
  background: #fff;
}
.loading-state {
  text-align: center;
  padding: 2.5rem 2rem;
  color: #64748b;
  background: #fafbfc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}
.loading-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 0.85rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 14px;
  border: 1px dashed #cbd5e1;
}
.empty-icon { margin-bottom: 1.25rem; color: #94a3b8; }
.empty-title { font-size: 1.2rem; font-weight: 600; margin: 0 0 0.5rem; color: #334155; }
.empty-desc { color: #64748b; margin: 0 0 1.25rem; font-size: 0.9rem; line-height: 1.5; max-width: 360px; margin-left: auto; margin-right: auto; }
.btn-empty-cta {
  margin-top: 0.5rem;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}
.btn-empty-cta:hover { box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4); }
.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.9rem 1.15rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
.data-table tbody tr {
  transition: background 0.15s ease;
}
.data-table tbody tr:nth-child(even) {
  background: #fafbfc;
}
.data-table tbody tr:hover {
  background: #f0f9ff !important;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
.data-table th {
  background: #f1f5f9;
  font-weight: 600;
  font-size: 0.8rem;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  white-space: nowrap;
}
.data-table thead {
  position: sticky;
  top: 0;
  z-index: 1;
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
.status-badge.status-pending { background: #fffbeb; color: #b45309; }
.status-badge.status-dicatat { background: #ecfdf5; color: #047857; }
.status-badge.status-sanksi_diberikan { background: #fef3c7; color: #92400e; }
.status-badge.status-follow_up { background: #ffedd5; color: #c2410c; }
.status-badge.status-selesai { background: #d1fae5; color: #047857; }
.status-badge.status-ditolak { background: #fef2f2; color: #b91c1c; }
.pending-banner {
  background: #fffbeb; border: 1px solid #fcd34d; color: #92400e;
  border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px;
}
.piket-source { display: block; color: #2563eb; }
.btn-sm-approve, .btn-sm-reject {
  border: none; border-radius: 6px; padding: 5px 10px; font-size: 12px; font-weight: 600; cursor: pointer;
}
.btn-sm-approve { background: #ecfdf5; color: #047857; }
.btn-sm-reject { background: #fef2f2; color: #b91c1c; }
.reject-hint { margin: 0 0 12px; font-size: 13px; color: #64748b; }
.action-badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; background: #fef3c7; color: #92400e; }
.text-danger { color: #b91c1c; font-weight: 500; }
.text-muted { color: #64748b; }
.text-warn-soft { color: #b45309; font-size: 0.8rem; }
.filter-check {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: #475569;
  cursor: pointer;
  white-space: nowrap;
}
.form-check-row { margin-top: 0.25rem; }
.form-check-row .form-hint { margin: 0.35rem 0 0; font-size: 0.8rem; }
.modal-wide { max-width: 640px; }
.point-add { color: #b91c1c; font-weight: 600; }
.points-info-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.1rem 1.35rem;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  border: 1px solid #c7d2fe;
  border-radius: 12px;
  font-size: 0.9rem;
  color: #047857;
  margin-bottom: 1.5rem;
  line-height: 1.55;
  box-shadow: 0 1px 4px rgba(5, 150, 105, 0.08);
}
.points-info-icon {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(5, 150, 105, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #059669;
}
.points-info-text { flex: 1; min-width: 0; }
.points-filter-hint { font-size: 0.85rem; color: #64748b; margin: 0.5rem 0 1rem; }
.points-summary-cards {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
  margin-bottom: 1rem;
}
@media (max-width: 640px) {
  .points-summary-cards { grid-template-columns: 1fr; }
}
.summary-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1rem 1.15rem;
  border-radius: 12px;
  text-align: left;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.04);
}
.summary-body { min-width: 0; }
.summary-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin: 0;
}
.summary-icon-total { background: rgba(71, 85, 105, 0.12); color: #475569; }
.summary-icon-warning { background: rgba(146, 64, 14, 0.18); color: #b45309; }
.summary-icon-good { background: rgba(5, 150, 105, 0.18); color: #059669; }
.summary-card .summary-value { display: block; font-size: 1.65rem; font-weight: 700; line-height: 1.15; letter-spacing: -0.02em; }
.summary-card .summary-label { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; display: block; font-weight: 500; }
.card-total { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; }
.card-warning { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); color: #92400e; border: 1px solid #fde68a; }
.card-warning .summary-label { color: #a16207; }
.card-good { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; border: 1px solid #a7f3d0; }

.points-filters { margin-bottom: 1rem; }
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 0.82rem;
  color: #475569;
  cursor: pointer;
  user-select: none;
}
.filter-chip input { accent-color: #059669; }
.filter-chip.active {
  background: #ecfdf5;
  border-color: #6ee7b7;
  color: #047857;
  font-weight: 600;
}

.points-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
.points-row {
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  border-left: 4px solid #cbd5e1;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  overflow: hidden;
}
.points-row:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}
.points-row.is-pending { border-left-color: #f59e0b; background: #fffbeb; }
.points-row.is-done { border-left-color: #10b981; background: #f0fdf4; }
.points-row.is-no-rule { border-left-color: #94a3b8; }
.points-row-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.95rem 1.1rem;
}
.points-row-main {
  display: grid;
  grid-template-columns: minmax(140px, 1.3fr) auto minmax(160px, 1.2fr);
  gap: 1rem;
  align-items: center;
  flex: 1;
  min-width: 0;
}
.points-student { min-width: 0; }
.points-student .student-name {
  display: block;
  font-weight: 600;
  color: #0f172a;
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.points-student .student-meta {
  display: block;
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.15rem;
}
.reopen-hint {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: #c2410c;
  line-height: 1.35;
}
.new-points-badge {
  display: inline-block;
  margin-top: 0.2rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 700;
  background: #fee2e2;
  color: #b91c1c;
}
.points-row-detail {
  padding: 0 1.1rem 1rem;
  border-top: 1px dashed #e2e8f0;
  background: rgba(255,255,255,0.55);
}
.detail-title {
  margin: 0.75rem 0 0.5rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.detail-empty { font-size: 0.85rem; color: #94a3b8; padding: 0.5rem 0; }
.violation-mini-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.violation-mini-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.55rem 0.7rem;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
}
.vmi-main { min-width: 0; display: flex; flex-direction: column; gap: 0.1rem; }
.vmi-main strong { font-size: 0.85rem; color: #0f172a; }
.vmi-date { font-size: 0.72rem; color: #64748b; }
.vmi-meta { display: flex; align-items: center; gap: 0.45rem; flex-shrink: 0; }
.vmi-points { font-size: 0.8rem; font-weight: 700; color: #b91c1c; }
.vmi-points-good { color: #059669; }
.achievement-mini-item { border-color: #bbf7d0; background: #f0fdf4; }
.detail-last-action {
  margin: 0.65rem 0 0;
  font-size: 0.78rem;
  color: #64748b;
}
.points-score-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
}
.score-pill {
  display: inline-flex;
  align-items: baseline;
  gap: 0.35rem;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  font-weight: 700;
}
.score-pill-label { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.8; }
.score-pill-value { font-size: 1.15rem; line-height: 1; }
.score-pill.score-bad { background: #fef2f2; color: #b91c1c; }
.score-pill.score-warn { background: #fff7ed; color: #c2410c; }
.score-pill.score-normal { background: #f1f5f9; color: #334155; }
.score-pill.score-good { background: #ecfdf5; color: #047857; }
.score-breakdown {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #64748b;
}
.score-breakdown .bd-add { color: #b91c1c; font-weight: 600; }
.score-breakdown .bd-sub { color: #059669; font-weight: 600; }
.score-breakdown .bd-sep { color: #94a3b8; }
.points-action-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}
.action-name-line {
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.action-name-line.muted { color: #64748b; font-weight: 500; }
.action-range-line { font-size: 0.75rem; color: #64748b; }
.status-chip {
  display: inline-flex;
  align-self: flex-start;
  margin-top: 0.25rem;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 600;
}
.status-chip.pending { background: #ffedd5; color: #c2410c; }
.status-chip.done { background: #d1fae5; color: #047857; }
.status-chip.none { background: #f1f5f9; color: #64748b; }
.points-row-actions {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex-shrink: 0;
  min-width: 132px;
}
.btn-points-primary {
  padding: 0.5rem 0.85rem;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
}
.btn-points-primary:hover { filter: brightness(1.05); }
.btn-points-primary.secondary {
  background: #fff;
  color: #047857;
  border: 1px solid #6ee7b7;
  box-shadow: none;
}
.btn-points-ghost {
  padding: 0.4rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
}
.btn-points-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }

@media (max-width: 900px) {
  .points-row-top {
    flex-direction: column;
    align-items: stretch;
  }
  .points-row-main {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  .points-score-block { align-items: flex-start; }
  .points-row-actions {
    flex-direction: row;
    flex-wrap: wrap;
    min-width: 0;
  }
  .btn-points-primary,
  .btn-points-ghost { flex: 1; }
}

/* Legacy table-points helpers (kept for safety) */
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
.filters-inline { align-items: center; }
.thresholds-hint { margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid #059669; }
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
  margin-top: 1.25rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.pagination-info { font-size: 0.85rem; color: #64748b; }
.pagination-buttons { display: flex; align-items: center; gap: 0.5rem; }
.btn-page {
  padding: 0.45rem 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.15s ease, border-color 0.15s ease;
}
.btn-page:hover:not(:disabled) { background: #f1f5f9; border-color: #cbd5e1; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.page-num { font-size: 0.85rem; color: #64748b; }
.types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}
.type-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.type-card:hover {
  box-shadow: 0 4px 14px rgba(0,0,0,0.06);
  border-color: #cbd5e1;
}
.type-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.85rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}
.type-name { font-weight: 600; font-size: 1.05rem; color: #0f172a; }
.type-body { margin-bottom: 0.85rem; }
.type-sanction, .type-point { margin: 0.3rem 0; font-size: 0.9rem; color: #64748b; }
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
  padding: 0.55rem 1.1rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 10px rgba(5, 150, 105, 0.35);
  transition: opacity 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
}
.btn-primary:hover:not(:disabled) {
  opacity: 0.95;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.45);
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
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.type-card {
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.type-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  border-color: #cbd5e1;
}

.action-form-modal { max-width: 520px; }
.action-student-card {
  margin-bottom: 1.15rem;
  padding: 0.95rem 1rem;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid #e2e8f0;
}
.asc-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}
.asc-name {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}
.asc-meta {
  margin: 0.2rem 0 0;
  font-size: 0.8rem;
  color: #64748b;
}
.asc-score {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.35rem 0.7rem;
  border-radius: 10px;
  min-width: 58px;
}
.asc-score span { font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.85; }
.asc-score strong { font-size: 1.25rem; line-height: 1.1; }
.asc-score.score-bad { background: #fef2f2; color: #b91c1c; }
.asc-score.score-warn { background: #fff7ed; color: #c2410c; }
.asc-score.score-normal { background: #e2e8f0; color: #334155; }
.asc-breakdown {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.65rem;
  font-size: 0.8rem;
  color: #64748b;
}
.asc-breakdown em { font-style: normal; font-weight: 700; }
.asc-breakdown span:first-child em { color: #b91c1c; }
.asc-breakdown span:last-child em { color: #059669; }
.asc-required {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #cbd5e1;
}
.asc-required-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}
.asc-required strong { color: #0f172a; font-size: 0.95rem; }
.asc-required-range { font-size: 0.78rem; color: #64748b; }
.asc-no-rule {
  margin: 0.75rem 0 0;
  padding-top: 0.65rem;
  border-top: 1px dashed #cbd5e1;
  font-size: 0.82rem;
  color: #b45309;
}
.asc-reopen {
  margin: 0.65rem 0 0;
  padding: 0.5rem 0.65rem;
  border-radius: 8px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  font-size: 0.8rem;
  color: #c2410c;
  line-height: 1.4;
}
.asc-close-hint {
  margin: 0.55rem 0 0;
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.4;
}

.resolve-options {
  margin: 0 0 1rem;
  padding: 0;
  border: none;
}
.resolve-options legend {
  font-size: 0.9rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.5rem;
  padding: 0;
}
.resolve-option {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  margin-bottom: 0.5rem;
  transition: border-color 0.15s ease, background 0.15s ease;
}
.resolve-option:last-child { margin-bottom: 0; }
.resolve-option input { margin-top: 0.2rem; accent-color: #059669; }
.resolve-option.selected {
  border-color: #6ee7b7;
  background: #ecfdf5;
}
.resolve-option-body { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.resolve-option-body strong { font-size: 0.9rem; color: #0f172a; }
.resolve-option-body small { font-size: 0.78rem; color: #64748b; line-height: 1.35; }

.history-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
.history-item {
  padding: 0.85rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
.history-item-top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.75rem;
}
.history-item-top strong { color: #0f172a; font-size: 0.92rem; }
.history-item-top time { font-size: 0.78rem; color: #64748b; white-space: nowrap; }
.history-notes {
  margin: 0.45rem 0 0;
  font-size: 0.85rem;
  color: #475569;
  line-height: 1.4;
}
.history-meta {
  margin: 0.4rem 0 0;
  font-size: 0.75rem;
  color: #94a3b8;
}
</style>
