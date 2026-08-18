<template>
  <Layout>
    <div class="page">
      <header class="page-header">
        <div>
          <span class="eyebrow">Manajemen Guru</span>
          <h1>Apresiasi Guru</h1>
          <p>Kelola prestasi, pelanggaran, dan perolehan poin guru dalam satu tempat.</p>
        </div>
        <div class="header-actions">
          <template v-if="mainTab === 'catatan' && canManageFull">
            <button type="button" class="btn-primary" @click="openAddFromToolbar('achievements')">
              <span class="btn-icon">+</span>
              Catat Prestasi
            </button>
            <button type="button" class="btn-secondary" @click="openAddFromToolbar('violations')">
              <span class="btn-icon">+</span>
              Catat Pelanggaran
            </button>
          </template>
          <button v-else-if="primaryActionLabel" type="button" class="btn-primary" @click="primaryActionClick">
            <span class="btn-icon">+</span>
            {{ primaryActionLabel }}
          </button>
        </div>
      </header>

      <nav class="nav-tabs" aria-label="Apresiasi Guru">
        <button type="button" :class="['nav-tab', { active: mainTab === 'catatan' }]" @click="switchMainTab('catatan')">Catatan</button>
        <button type="button" :class="['nav-tab', { active: mainTab === 'usulan' }]" @click="switchMainTab('usulan')">
          Usulan
          <span v-if="pendingTotal" class="badge">{{ pendingTotal }}</span>
        </button>
        <button v-if="canManageFull" type="button" :class="['nav-tab', { active: mainTab === 'poin' }]" @click="switchMainTab('poin')">Poin</button>
        <button v-if="canManageFull" type="button" :class="['nav-tab', { active: mainTab === 'laporan' }]" @click="switchMainTab('laporan')">Laporan</button>
        <button v-if="canManageFull" type="button" :class="['nav-tab', { active: mainTab === 'pengaturan' }]" @click="switchMainTab('pengaturan')">Pengaturan</button>
      </nav>

      <div v-if="mainTab === 'catatan' && canManageFull" class="sub-nav" role="tablist" aria-label="Jenis catatan">
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'achievements' }]" @click="switchTab('achievements')">Prestasi</button>
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'violations' }]" @click="switchTab('violations')">Pelanggaran</button>
      </div>
      <div v-else-if="mainTab === 'usulan'" class="sub-nav" role="tablist" aria-label="Jenis usulan">
        <button
          v-if="canManageFull"
          type="button"
          role="tab"
          :class="['sub-nav-btn', { active: tab === 'pending' }]"
          @click="switchTab('pending')"
        >
          Prestasi
          <span v-if="pendingCount" class="badge">{{ pendingCount }}</span>
        </button>
        <button
          type="button"
          role="tab"
          :class="['sub-nav-btn', { active: tab === 'pending_violations' }]"
          @click="switchTab('pending_violations')"
        >
          Pelanggaran
          <span v-if="pendingViolationCount" class="badge">{{ pendingViolationCount }}</span>
        </button>
      </div>
      <div v-else-if="mainTab === 'poin'" class="sub-nav" role="tablist" aria-label="Poin guru">
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'points' }]" @click="switchTab('points')">Poin Guru</button>
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'leaderboard' }]" @click="switchTab('leaderboard')">Peringkat</button>
      </div>
      <div v-else-if="mainTab === 'pengaturan'" class="sub-nav" role="tablist" aria-label="Pengaturan">
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'types' }]" @click="switchTab('types')">Jenis Prestasi</button>
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'violation_types' }]" @click="switchTab('violation_types')">Jenis Pelanggaran</button>
        <button type="button" role="tab" :class="['sub-nav-btn', { active: tab === 'rewards' }]" @click="switchTab('rewards')">Aturan Reward</button>
      </div>

      <div class="filters">
        <div class="filter-group">
          <label for="filter-year">Tahun Ajaran</label>
          <select id="filter-year" v-model="period.academic_year_id" class="filter-select" @change="onPeriodChange">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name || y.code }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="filter-semester">Semester</label>
          <select id="filter-semester" v-model="period.semester_id" class="filter-select" @change="onPeriodChange">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
        </div>
        <div v-if="['achievements','points','pending','violations','pending_violations'].includes(tab)" class="filter-group search-group">
          <label for="teacher-search">Pencarian</label>
          <div class="search-box">
            <span aria-hidden="true">⌕</span>
            <input
              id="teacher-search"
              v-model="search"
              type="text"
              class="search-input"
              placeholder="Cari nama atau NIP guru..."
              @input="debounceReload"
            />
          </div>
        </div>
      </div>

      <p v-if="error" class="error-banner">{{ error }}</p>
      <p v-if="success" class="success-banner">{{ success }}</p>

      <!-- Prestasi / Pending -->
      <template v-if="tab === 'achievements' || tab === 'pending'">
        <div v-if="loading" class="state">Memuat data...</div>
        <div v-else-if="!achievements.length" class="state empty">
          <h3 class="empty-title">Belum ada data prestasi</h3>
          <p class="empty-desc">
            {{ tab === 'pending' ? 'Tidak ada usulan prestasi yang menunggu persetujuan.' : 'Belum ada prestasi tercatat pada periode ini. Gunakan tombol + Catat Prestasi di atas untuk mulai.' }}
          </p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>Tanggal</th>
                <th>Guru</th>
                <th>Prestasi</th>
                <th>Kategori</th>
                <th>Level</th>
                <th>Poin</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in achievements" :key="item.id">
                <td class="col-number">{{ rowNumber(index) }}</td>
                <td>{{ formatDate(item.achievement_date) }}</td>
                <td>
                  <strong>{{ item.employee?.name }}</strong>
                  <div class="muted">{{ item.employee?.nip || '-' }}</div>
                </td>
                <td>
                  <div>{{ item.title || item.achievement_type?.name }}</div>
                  <a v-if="item.evidence_url" :href="item.evidence_url" target="_blank" rel="noopener" class="link">Bukti</a>
                </td>
                <td>{{ categoryLabel(item.achievement_type?.category) }}</td>
                <td>{{ levelLabel(item.level) }}</td>
                <td><span class="points">+{{ item.point_value }}</span></td>
                <td><span :class="['status', item.status]">{{ statusLabel(item.status) }}</span></td>
                <td class="actions">
                  <template v-if="item.status === 'pending'">
                    <button type="button" class="btn-sm btn-success" @click="approveItem(item)">Setujui</button>
                    <button type="button" class="btn-sm btn-danger" @click="openReject(item)">Tolak</button>
                  </template>
                  <template v-else>
                    <button type="button" class="btn-sm" @click="openEditAchievement(item)">Edit</button>
                    <button type="button" class="btn-sm btn-danger" @click="deleteAchievement(item)">Hapus</button>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="meta.total > 0" class="pagination">
          <p>Menampilkan <strong>{{ meta.from }}–{{ meta.to }}</strong> dari <strong>{{ meta.total }}</strong> data</p>
          <div class="pagination-controls" aria-label="Navigasi halaman">
            <button type="button" class="page-button page-nav" :disabled="meta.current_page <= 1" aria-label="Halaman sebelumnya" @click="loadAchievements(meta.current_page - 1)">‹</button>
            <button
              v-for="pageNumber in paginationPages"
              :key="pageNumber"
              type="button"
              :class="['page-button', { active: pageNumber === meta.current_page }]"
              :aria-current="pageNumber === meta.current_page ? 'page' : undefined"
              @click="loadAchievements(pageNumber)"
            >
              {{ pageNumber }}
            </button>
            <button type="button" class="page-button page-nav" :disabled="meta.current_page >= meta.last_page" aria-label="Halaman berikutnya" @click="loadAchievements(meta.current_page + 1)">›</button>
          </div>
        </div>
      </template>

      <!-- Pelanggaran / Pending pelanggaran -->
      <template v-else-if="tab === 'violations' || tab === 'pending_violations'">
        <div v-if="loading" class="state">Memuat data...</div>
        <div v-else-if="!violations.length" class="state empty">
          <h3 class="empty-title">Belum ada data pelanggaran</h3>
          <p class="empty-desc">
            {{ tab === 'pending_violations' ? 'Tidak ada laporan pelanggaran yang menunggu persetujuan KS.' : 'Belum ada pelanggaran tercatat pada periode ini. Gunakan tombol + Catat Pelanggaran di atas untuk mulai.' }}
          </p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>Tanggal</th>
                <th>Guru</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Poin</th>
                <th>Pelapor</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in violations" :key="item.id">
                <td class="col-number">{{ rowNumber(index) }}</td>
                <td>{{ formatDate(item.violation_date) }}</td>
                <td>
                  <strong>{{ item.employee?.name }}</strong>
                  <div class="muted">{{ item.employee?.nip || '-' }}</div>
                </td>
                <td>
                  <div>{{ item.violation_type?.name }}</div>
                  <div v-if="item.from_piket" class="muted piket-tag">Dari laporan guru piket</div>
                  <div v-if="item.notes" class="muted notes-clip">{{ item.notes }}</div>
                  <a v-if="item.evidence_url" :href="item.evidence_url" target="_blank" rel="noopener" class="link">Bukti</a>
                </td>
                <td>{{ violationCategoryLabel(item.violation_type?.category) }}</td>
                <td><span class="points-minus">−{{ item.point_value }}</span></td>
                <td>{{ item.reporter?.name || '-' }}</td>
                <td><span :class="['status', item.status]">{{ statusLabel(item.status) }}</span></td>
                <td class="actions">
                  <template v-if="item.status === 'pending' && canManageFull">
                    <button type="button" class="btn-sm btn-success" @click="approveViolation(item)">Setujui</button>
                    <button type="button" class="btn-sm btn-danger" @click="openRejectViolation(item)">Tolak</button>
                  </template>
                  <template v-else-if="canManageFull && item.status !== 'pending'">
                    <button type="button" class="btn-sm btn-danger" @click="deleteViolation(item)">Hapus</button>
                  </template>
                  <span v-else class="muted">Menunggu KS</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="meta.total > 0" class="pagination">
          <p>Menampilkan <strong>{{ meta.from }}–{{ meta.to }}</strong> dari <strong>{{ meta.total }}</strong> data</p>
          <div class="pagination-controls" aria-label="Navigasi halaman">
            <button type="button" class="page-button page-nav" :disabled="meta.current_page <= 1" aria-label="Halaman sebelumnya" @click="loadViolations(meta.current_page - 1)">‹</button>
            <button
              v-for="pageNumber in paginationPages"
              :key="pageNumber"
              type="button"
              :class="['page-button', { active: pageNumber === meta.current_page }]"
              :aria-current="pageNumber === meta.current_page ? 'page' : undefined"
              @click="loadViolations(pageNumber)"
            >
              {{ pageNumber }}
            </button>
            <button type="button" class="page-button page-nav" :disabled="meta.current_page >= meta.last_page" aria-label="Halaman berikutnya" @click="loadViolations(meta.current_page + 1)">›</button>
          </div>
        </div>
      </template>

      <!-- Poin Guru -->
      <template v-else-if="tab === 'points'">
        <label class="check-inline">
          <input v-model="withPointsOnly" type="checkbox" @change="loadPoints(1)" />
          Hanya yang punya poin
        </label>
        <div v-if="loading" class="state">Memuat data...</div>
        <div v-else-if="!pointRows.length" class="state empty">Belum ada data poin.</div>
        <div v-else class="cards">
          <div v-for="row in pointRows" :key="row.employee_id" class="card">
            <div class="card-head">
              <div>
                <strong>{{ row.employee?.name }}</strong>
                <div class="muted">{{ row.employee?.nip || '-' }} · {{ row.employee?.subject || row.employee?.type }}</div>
              </div>
              <div class="score">{{ row.total_points }} <span>neto</span></div>
            </div>
            <div class="card-meta">
              <span class="ok">+{{ row.achievement_points || 0 }} prestasi</span>
              <span class="warn">−{{ row.violation_points || 0 }} pelanggaran</span>
              <span v-if="row.pending_count">{{ row.pending_count }} prestasi menunggu</span>
              <span v-if="row.pending_violation_count" class="warn">{{ row.pending_violation_count }} pelanggaran menunggu</span>
              <span v-if="row.matched_reward" class="reward">{{ row.matched_reward.reward_name }}</span>
            </div>
            <ul v-if="row.achievements?.length" class="mini-list">
              <li v-for="a in row.achievements.slice(0, 3)" :key="a.id">
                {{ a.title || a.achievement_type?.name }} <em>+{{ a.point_value }}</em>
              </li>
            </ul>
            <button type="button" class="btn-sm" @click="openRewardLog(row)">Catat Reward</button>
          </div>
        </div>
        <div v-if="meta.total > 0" class="pagination">
          <p>Menampilkan <strong>{{ meta.from }}–{{ meta.to }}</strong> dari <strong>{{ meta.total }}</strong> data</p>
          <div class="pagination-controls" aria-label="Navigasi halaman">
            <button type="button" class="page-button page-nav" :disabled="meta.current_page <= 1" aria-label="Halaman sebelumnya" @click="loadPoints(meta.current_page - 1)">‹</button>
            <button
              v-for="pageNumber in paginationPages"
              :key="pageNumber"
              type="button"
              :class="['page-button', { active: pageNumber === meta.current_page }]"
              :aria-current="pageNumber === meta.current_page ? 'page' : undefined"
              @click="loadPoints(pageNumber)"
            >
              {{ pageNumber }}
            </button>
            <button type="button" class="page-button page-nav" :disabled="meta.current_page >= meta.last_page" aria-label="Halaman berikutnya" @click="loadPoints(meta.current_page + 1)">›</button>
          </div>
        </div>
      </template>

      <!-- Leaderboard / Peringkat -->
      <template v-else-if="tab === 'leaderboard'">
        <div class="leaderboard-settings">
          <div class="filter-group">
            <label for="leaderboard-mode">Mode peringkat</label>
            <select
              id="leaderboard-mode"
              v-model="leaderboardModeDraft"
              class="filter-select"
              :disabled="savingLeaderboardMode"
              @change="saveLeaderboardMode"
            >
              <option value="guru_only">Hanya Guru</option>
              <option value="combined">Guru &amp; Staff digabung</option>
              <option value="separated">Guru &amp; Staff dipisah</option>
            </select>
          </div>
          <p class="muted leaderboard-mode-hint">{{ leaderboardModeHint }}</p>
        </div>

        <div
          v-if="leaderboardMode === 'separated'"
          class="leaderboard-group-tabs"
          role="tablist"
          aria-label="Grup peringkat"
        >
          <button
            type="button"
            class="nav-tab"
            :class="{ active: leaderboardGroup === 'guru' }"
            @click="leaderboardGroup = 'guru'"
          >
            Guru
          </button>
          <button
            type="button"
            class="nav-tab"
            :class="{ active: leaderboardGroup === 'staff' }"
            @click="leaderboardGroup = 'staff'"
          >
            Staff / Non-Guru
          </button>
        </div>

        <div v-if="loading" class="state">Memuat peringkat...</div>
        <div v-else-if="!activeLeaderboardRows.length" class="state empty">
          <h3 class="empty-title">Belum ada ranking</h3>
          <p class="empty-desc">Belum ada pegawai aktif untuk ditampilkan pada mode/periode ini.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>{{ leaderboardPersonLabel }}</th>
                <th>Prestasi</th>
                <th>Pelanggaran</th>
                <th>Neto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in activeLeaderboardRows" :key="row.employee_id">
                <td><strong>{{ row.rank }}</strong></td>
                <td>
                  <strong>{{ row.employee?.name }}</strong>
                  <div class="muted">{{ row.employee?.nip || '-' }} · {{ row.employee?.subject || row.employee?.type || '-' }}</div>
                </td>
                <td><span class="points">+{{ row.achievement_points || 0 }}</span> <span class="muted">({{ row.achievements_count || 0 }})</span></td>
                <td><span class="points-minus">−{{ row.violation_points || 0 }}</span> <span class="muted">({{ row.violations_count || 0 }})</span></td>
                <td><strong>{{ row.total_points }}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Jenis Pelanggaran -->
      <template v-else-if="tab === 'violation_types'">
        <div v-if="loading" class="state">Memuat...</div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Bobot (−)</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, index) in violationTypes" :key="t.id">
                <td class="col-number">{{ index + 1 }}</td>
                <td>{{ t.code || '-' }}</td>
                <td>{{ t.name }}</td>
                <td>{{ violationCategoryLabel(t.category) }}</td>
                <td>{{ t.point_weight }}</td>
                <td>{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td class="actions">
                  <button type="button" class="btn-sm" @click="openEditViolationType(t)">Edit</button>
                  <button type="button" class="btn-sm btn-danger" @click="deleteViolationType(t)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Jenis -->
      <template v-else-if="tab === 'types'">
        <div v-if="loading" class="state">Memuat...</div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Poin</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(t, index) in types" :key="t.id">
                <td class="col-number">{{ index + 1 }}</td>
                <td>{{ t.code || '-' }}</td>
                <td>{{ t.name }}</td>
                <td>{{ categoryLabel(t.category) }}</td>
                <td>{{ t.point_value }}</td>
                <td>{{ t.is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td class="actions">
                  <button type="button" class="btn-sm" @click="openEditType(t)">Edit</button>
                  <button type="button" class="btn-sm btn-danger" @click="deleteType(t)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Rewards -->
      <template v-else-if="tab === 'rewards'">
        <div v-if="loading" class="state">Memuat...</div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-number">No.</th>
                <th>Rentang Poin</th>
                <th>Reward</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, index) in rewards" :key="r.id">
                <td class="col-number">{{ index + 1 }}</td>
                <td>{{ r.point_min }} – {{ r.point_max }}</td>
                <td>{{ r.reward_name }}</td>
                <td>{{ r.description || '-' }}</td>
                <td>{{ r.is_active ? 'Aktif' : 'Nonaktif' }}</td>
                <td class="actions">
                  <button type="button" class="btn-sm" @click="openEditReward(r)">Edit</button>
                  <button type="button" class="btn-sm btn-danger" @click="deleteReward(r)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Laporan -->
      <template v-else-if="tab === 'report'">
        <div class="report-toolbar">
          <button
            type="button"
            class="btn-secondary"
            :disabled="printing || loading || !report"
            @click="printReportPdf"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
        </div>
        <div v-if="loading" class="state">Memuat laporan...</div>
        <div v-else-if="report" class="report-grid">
          <div class="stat"><span>Poin Prestasi</span><strong class="ok">+{{ report.achievement_points || 0 }}</strong></div>
          <div class="stat"><span>Poin Pelanggaran</span><strong class="warn">−{{ report.violation_points || 0 }}</strong></div>
          <div class="stat"><span>Total Neto</span><strong>{{ report.total_points }}</strong></div>
          <div class="stat"><span>Guru Tercatat</span><strong>{{ report.unique_teachers }}</strong></div>

          <div class="panel">
            <h3>Distribusi Prestasi</h3>
            <ul v-if="report.by_category?.length">
              <li v-for="c in report.by_category" :key="'a-'+c.category">
                {{ categoryLabel(c.category) }} — {{ c.count }} ({{ c.points }} poin)
              </li>
            </ul>
            <p v-else class="muted">Belum ada data.</p>
          </div>

          <div class="panel">
            <h3>Distribusi Pelanggaran</h3>
            <ul v-if="report.violations_by_category?.length">
              <li v-for="c in report.violations_by_category" :key="'v-'+c.category">
                {{ violationCategoryLabel(c.category) }} — {{ c.count }} (−{{ c.points }} poin)
              </li>
            </ul>
            <p v-else class="muted">Belum ada data.</p>
          </div>

          <div class="panel" style="grid-column: 1 / -1">
            <h3>{{ reportLeaderboardTitle }}</h3>
            <template v-if="report.leaderboard?.mode === 'separated'">
              <h4 class="report-subheading">Guru</h4>
              <ol v-if="report.leaderboard?.guru?.length" class="leaderboard">
                <li v-for="row in report.leaderboard.guru" :key="'g-'+row.employee_id">
                  <span>#{{ row.rank }} {{ row.employee?.name }}
                    <em class="muted">(+{{ row.achievement_points || 0 }} / −{{ row.violation_points || 0 }})</em>
                  </span>
                  <strong>{{ row.total_points }} poin</strong>
                </li>
              </ol>
              <p v-else class="muted">Belum ada ranking guru.</p>
              <h4 class="report-subheading">Staff / Non-Guru</h4>
              <ol v-if="report.leaderboard?.staff?.length" class="leaderboard">
                <li v-for="row in report.leaderboard.staff" :key="'s-'+row.employee_id">
                  <span>#{{ row.rank }} {{ row.employee?.name }}
                    <em class="muted">(+{{ row.achievement_points || 0 }} / −{{ row.violation_points || 0 }})</em>
                  </span>
                  <strong>{{ row.total_points }} poin</strong>
                </li>
              </ol>
              <p v-else class="muted">Belum ada ranking staff.</p>
            </template>
            <template v-else>
              <ol v-if="reportLeaderboardRows.length" class="leaderboard">
                <li v-for="row in reportLeaderboardRows" :key="row.employee_id">
                  <span>#{{ row.rank }} {{ row.employee?.name }}
                    <em class="muted">(+{{ row.achievement_points || 0 }} / −{{ row.violation_points || 0 }})</em>
                  </span>
                  <strong>{{ row.total_points }} poin</strong>
                </li>
              </ol>
              <p v-else class="muted">Belum ada ranking.</p>
            </template>
          </div>

          <div class="panel report-wide">
            <h3>{{ reportRecapTitle }}</h3>
            <div class="report-table-wrap">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Nama / NIP</th>
                    <th class="num">Prestasi</th>
                    <th class="num">Pelanggaran</th>
                    <th class="num">Poin Neto</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in report.teacher_recap || []" :key="row.employee_id">
                    <td>{{ index + 1 }}</td>
                    <td>
                      <strong>{{ row.name }}</strong>
                      <span class="report-subtext">{{ row.nip || row.nuptk || 'Tanpa NIP/NUPTK' }}{{ row.type ? ` · ${row.type}` : '' }}</span>
                    </td>
                    <td class="num">+{{ row.achievement_points }} ({{ row.achievements_count }})</td>
                    <td class="num">−{{ row.violation_points }} ({{ row.violations_count }})</td>
                    <td class="num"><strong>{{ row.total_points }}</strong></td>
                  </tr>
                  <tr v-if="!report.teacher_recap?.length">
                    <td colspan="5" class="muted">Belum ada data guru aktif.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel report-wide">
            <h3>Lampiran Prestasi Disetujui ({{ report.achievement_details?.length || 0 }})</h3>
            <div class="report-table-wrap">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Guru</th>
                    <th>Prestasi</th>
                    <th>Level</th>
                    <th class="num">Poin</th>
                    <th>Pencatat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in report.achievement_details || []" :key="item.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(item.achievement_date) }}</td>
                    <td>
                      {{ item.employee_name || '-' }}
                      <span class="report-subtext">{{ item.employee_nip || item.employee_nuptk || 'Tanpa NIP/NUPTK' }}</span>
                    </td>
                    <td>
                      {{ item.title || item.type_name || '-' }}
                      <span class="report-subtext">{{ categoryLabel(item.category) }}</span>
                    </td>
                    <td>{{ levelLabel(item.level) }}</td>
                    <td class="num ok">+{{ item.point_value }}</td>
                    <td>{{ item.recorded_by || '-' }}</td>
                  </tr>
                  <tr v-if="!report.achievement_details?.length">
                    <td colspan="7" class="muted">Belum ada prestasi disetujui pada periode ini.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="panel report-wide">
            <h3>Lampiran Pelanggaran Disetujui ({{ report.violation_details?.length || 0 }})</h3>
            <div class="report-table-wrap">
              <table class="report-table">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Guru</th>
                    <th>Pelanggaran</th>
                    <th class="num">Poin</th>
                    <th>Sanksi</th>
                    <th>Pelapor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in report.violation_details || []" :key="item.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(item.violation_date) }}</td>
                    <td>
                      {{ item.employee_name || '-' }}
                      <span class="report-subtext">{{ item.employee_nip || item.employee_nuptk || 'Tanpa NIP/NUPTK' }}</span>
                    </td>
                    <td>
                      {{ item.type_name || '-' }}
                      <span class="report-subtext">{{ violationCategoryLabel(item.category) }}</span>
                    </td>
                    <td class="num warn">−{{ item.point_value }}</td>
                    <td>{{ item.sanction || '-' }}</td>
                    <td>{{ item.reported_by || '-' }}</td>
                  </tr>
                  <tr v-if="!report.violation_details?.length">
                    <td colspan="7" class="muted">Belum ada pelanggaran disetujui pada periode ini.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>

      <!-- Modal Pelanggaran -->
      <div v-if="showViolationModal" class="modal-overlay" @click.self="showViolationModal = false">
        <div class="modal">
          <h3>Catat Pelanggaran Guru</h3>
          <p v-if="!canManageFull" class="muted">Laporan dari piket menunggu persetujuan Kepala Sekolah sebelum mengurangi skor.</p>
          <p v-else class="muted">Sebagai KS/admin, catatan langsung disetujui dan mengurangi skor neto.</p>
          <form @submit.prevent="saveViolation">
            <label>Guru
              <select v-model="violationForm.employee_id" required>
                <option value="">Pilih guru</option>
                <option v-for="e in employees" :key="e.id" :value="String(e.id)">{{ e.name }} ({{ e.nip || e.type }})</option>
              </select>
            </label>
            <label>Jenis Pelanggaran
              <select v-model="violationForm.violation_type_id" required @change="onViolationTypeChange">
                <option value="">Pilih jenis</option>
                <option v-for="t in activeViolationTypes" :key="t.id" :value="String(t.id)">{{ t.name }} (−{{ t.point_weight }})</option>
              </select>
            </label>
            <label>Tanggal
              <input v-model="violationForm.violation_date" type="date" required />
            </label>
            <label>Poin Minus
              <input v-model.number="violationForm.point_value" type="number" min="0" />
            </label>
            <label>Sanksi
              <input v-model="violationForm.sanction" type="text" />
            </label>
            <label>Catatan
              <textarea v-model="violationForm.notes" rows="2"></textarea>
            </label>
            <label>Bukti (opsional)
              <input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onViolationEvidenceChange" />
            </label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showViolationModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Jenis Pelanggaran -->
      <div v-if="showViolationTypeModal" class="modal-overlay" @click.self="showViolationTypeModal = false">
        <div class="modal">
          <h3>{{ editingViolationType ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran' }}</h3>
          <form @submit.prevent="saveViolationType">
            <label>Nama <input v-model="violationTypeForm.name" required /></label>
            <label>Kode <input v-model="violationTypeForm.code" /></label>
            <label>Kategori
              <select v-model="violationTypeForm.category">
                <option value="kehadiran">Kehadiran</option>
                <option value="kedisiplinan">Kedisiplinan</option>
                <option value="administrasi">Administrasi</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </label>
            <label>Bobot Poin (−) <input v-model.number="violationTypeForm.point_weight" type="number" min="0" required /></label>
            <label>Sanksi default <input v-model="violationTypeForm.default_sanction" /></label>
            <label>Deskripsi <textarea v-model="violationTypeForm.description" rows="2"></textarea></label>
            <label class="check-inline"><input v-model="violationTypeForm.is_active" type="checkbox" /> Aktif</label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showViolationTypeModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Prestasi -->
      <div v-if="showAchievementModal" class="modal-overlay" @click.self="showAchievementModal = false">
        <div class="modal">
          <h3>{{ editingAchievement ? 'Edit Prestasi' : 'Catat Prestasi Guru' }}</h3>
          <form @submit.prevent="saveAchievement">
            <label>Guru
              <select v-model="achievementForm.employee_id" required :disabled="!!editingAchievement">
                <option value="">Pilih guru</option>
                <option v-for="e in employees" :key="e.id" :value="String(e.id)">{{ e.name }} ({{ e.nip || e.type }})</option>
              </select>
            </label>
            <label>Jenis Prestasi
              <select v-model="achievementForm.achievement_type_id" required @change="onTypeChange">
                <option value="">Pilih jenis</option>
                <option v-for="t in activeTypes" :key="t.id" :value="String(t.id)">{{ t.name }} ({{ t.point_value }})</option>
              </select>
            </label>
            <label>Judul
              <input v-model="achievementForm.title" type="text" placeholder="Opsional" />
            </label>
            <label>Tanggal
              <input v-model="achievementForm.achievement_date" type="date" required />
            </label>
            <label>Level
              <select v-model="achievementForm.level" @change="onTypeChange">
                <option value="">Tanpa multiplier</option>
                <option value="sekolah">Sekolah</option>
                <option value="kabupaten">Kabupaten</option>
                <option value="provinsi">Provinsi</option>
                <option value="nasional">Nasional</option>
                <option value="internasional">Internasional</option>
              </select>
            </label>
            <label>Poin
              <input v-model.number="achievementForm.point_value" type="number" min="0" />
            </label>
            <label>Catatan
              <textarea v-model="achievementForm.notes" rows="2"></textarea>
            </label>
            <label>Bukti (JPG/PNG/PDF, maks 5MB)
              <input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onEvidenceChange" />
            </label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showAchievementModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Jenis -->
      <div v-if="showTypeModal" class="modal-overlay" @click.self="showTypeModal = false">
        <div class="modal">
          <h3>{{ editingType ? 'Edit Jenis Prestasi' : 'Tambah Jenis Prestasi' }}</h3>
          <form @submit.prevent="saveType">
            <label>Nama <input v-model="typeForm.name" required /></label>
            <label>Kode <input v-model="typeForm.code" /></label>
            <label>Kategori
              <select v-model="typeForm.category">
                <option value="akademik">Akademik</option>
                <option value="pengembangan">Pengembangan</option>
                <option value="pengabdian">Pengabdian</option>
                <option value="inovasi">Inovasi</option>
                <option value="kedisiplinan">Kedisiplinan</option>
              </select>
            </label>
            <label>Poin Dasar <input v-model.number="typeForm.point_value" type="number" min="0" required /></label>
            <label>Deskripsi <textarea v-model="typeForm.description" rows="2"></textarea></label>
            <label class="check-inline"><input v-model="typeForm.is_active" type="checkbox" /> Aktif</label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showTypeModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Reward Rule -->
      <div v-if="showRewardModal" class="modal-overlay" @click.self="showRewardModal = false">
        <div class="modal">
          <h3>{{ editingReward ? 'Edit Aturan Reward' : 'Tambah Aturan Reward' }}</h3>
          <form @submit.prevent="saveReward">
            <label>Poin Min <input v-model.number="rewardForm.point_min" type="number" min="0" required /></label>
            <label>Poin Max <input v-model.number="rewardForm.point_max" type="number" min="0" required /></label>
            <label>Nama Reward <input v-model="rewardForm.reward_name" required /></label>
            <label>Deskripsi <textarea v-model="rewardForm.description" rows="2"></textarea></label>
            <label class="check-inline"><input v-model="rewardForm.is_active" type="checkbox" /> Aktif</label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showRewardModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Reject -->
      <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
        <div class="modal">
          <h3>Tolak Usulan</h3>
          <form @submit.prevent="rejectItem">
            <label>Alasan penolakan
              <textarea v-model="rejectNotes" rows="3" required></textarea>
            </label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showRejectModal = false">Batal</button>
              <button type="submit" class="btn-primary btn-danger" :disabled="saving">Tolak</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Reward Log -->
      <div v-if="showRewardLogModal" class="modal-overlay" @click.self="showRewardLogModal = false">
        <div class="modal">
          <h3>Catat Reward — {{ rewardLogTarget?.employee?.name }}</h3>
          <form @submit.prevent="saveRewardLog">
            <label>Aturan Reward
              <select v-model="rewardLogForm.teacher_point_reward_id">
                <option value="">Manual / lainnya</option>
                <option v-for="r in rewards" :key="r.id" :value="String(r.id)">{{ r.reward_name }} ({{ r.point_min }}–{{ r.point_max }})</option>
              </select>
            </label>
            <label>Nama Reward <input v-model="rewardLogForm.reward_name" :required="!rewardLogForm.teacher_point_reward_id" /></label>
            <label>Tanggal <input v-model="rewardLogForm.reward_date" type="date" required /></label>
            <label>Catatan <textarea v-model="rewardLogForm.notes" rows="2"></textarea></label>
            <div class="modal-actions">
              <button type="button" class="btn-sm" @click="showRewardLogModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { semesterApi } from '@/api/semester'
import { institutionApi } from '@/api/institution'
import {
  teacherAchievementApi,
  teacherAchievementTypeApi,
  teacherPointApi,
  teacherPointRewardApi,
  teacherRewardLogApi,
  teacherViolationApi,
  teacherViolationTypeApi,
} from '@/api/teacherAppreciation'
import { getPrincipalTitle, getNssLabel } from '@/utils/institution'

const authStore = useAuthStore()
const canManageFull = computed(() => {
  const role = authStore.user?.role
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  return (authStore.user?.permissions || []).includes('teacher_appreciation')
})

const tab = ref(canManageFull.value ? 'achievements' : 'violations')
const CATATAN_TABS = ['achievements', 'violations']
const USULAN_TABS = ['pending', 'pending_violations']
const POIN_TABS = ['points', 'leaderboard']
const PENGATURAN_TABS = ['types', 'violation_types', 'rewards']

const mainTab = computed(() => {
  if (CATATAN_TABS.includes(tab.value)) return 'catatan'
  if (USULAN_TABS.includes(tab.value)) return 'usulan'
  if (POIN_TABS.includes(tab.value)) return 'poin'
  if (tab.value === 'report') return 'laporan'
  if (PENGATURAN_TABS.includes(tab.value)) return 'pengaturan'
  return 'catatan'
})

const pendingTotal = computed(() => {
  if (canManageFull.value) return (pendingCount.value || 0) + (pendingViolationCount.value || 0)
  return pendingViolationCount.value || 0
})

const loading = ref(false)
const saving = ref(false)
const printing = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const withPointsOnly = ref(false)
const pendingCount = ref(0)
const pendingViolationCount = ref(0)

const academicYears = ref([])
const semesters = ref([])
const employees = ref([])
const types = ref([])
const violationTypes = ref([])
const rewards = ref([])
const achievements = ref([])
const violations = ref([])
const pointRows = ref([])
const leaderboardRows = ref([])
const leaderboardMode = ref('guru_only')
const leaderboardModeDraft = ref('guru_only')
const leaderboardGroup = ref('guru')
const leaderboardGuruRows = ref([])
const leaderboardStaffRows = ref([])
const savingLeaderboardMode = ref(false)
const report = ref(null)
const institution = ref(null)
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 })
const achievementsMeta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 })
const violationsMeta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 })
const period = reactive({ academic_year_id: '', semester_id: '' })
const listCache = reactive({
  achievementsPeriodKey: '',
  violationsPeriodKey: '',
})

const showAchievementModal = ref(false)
const showViolationModal = ref(false)
const showTypeModal = ref(false)
const showViolationTypeModal = ref(false)
const showRewardModal = ref(false)
const showRejectModal = ref(false)
const showRewardLogModal = ref(false)
const editingAchievement = ref(null)
const editingType = ref(null)
const editingViolationType = ref(null)
const editingReward = ref(null)
const rejectTarget = ref(null)
const rejectViolationTarget = ref(null)
const rejectNotes = ref('')
const rewardLogTarget = ref(null)
const evidenceFile = ref(null)
const violationEvidenceFile = ref(null)

const achievementForm = reactive({
  employee_id: '',
  achievement_type_id: '',
  title: '',
  achievement_date: new Date().toISOString().slice(0, 10),
  level: '',
  point_value: 0,
  notes: '',
})

const violationForm = reactive({
  employee_id: '',
  violation_type_id: '',
  violation_date: new Date().toISOString().slice(0, 10),
  point_value: 0,
  sanction: '',
  notes: '',
})

const typeForm = reactive({
  name: '',
  code: '',
  category: 'akademik',
  point_value: 10,
  description: '',
  is_active: true,
})

const violationTypeForm = reactive({
  name: '',
  code: '',
  category: 'kedisiplinan',
  point_weight: 5,
  default_sanction: '',
  description: '',
  is_active: true,
})

const rewardForm = reactive({
  point_min: 50,
  point_max: 99,
  reward_name: '',
  description: '',
  is_active: true,
})

const rewardLogForm = reactive({
  teacher_point_reward_id: '',
  reward_name: '',
  reward_date: new Date().toISOString().slice(0, 10),
  notes: '',
})

let debounceTimer = null

const activeTypes = computed(() => types.value.filter((t) => t.is_active !== false))
const activeViolationTypes = computed(() => violationTypes.value.filter((t) => t.is_active !== false))
const activeLeaderboardRows = computed(() => {
  if (leaderboardMode.value === 'separated') {
    return leaderboardGroup.value === 'staff'
      ? leaderboardStaffRows.value
      : leaderboardGuruRows.value
  }
  return leaderboardRows.value
})
const leaderboardPersonLabel = computed(() => {
  if (leaderboardMode.value === 'combined') return 'Pegawai'
  if (leaderboardMode.value === 'separated' && leaderboardGroup.value === 'staff') return 'Staff / Non-Guru'
  return 'Guru'
})
const leaderboardModeHint = computed(() => {
  if (leaderboardMode.value === 'combined') {
    return 'Semua pegawai aktif (guru dan staff) masuk satu ranking berdasarkan skor neto.'
  }
  if (leaderboardMode.value === 'separated') {
    return 'Ranking dipisah: Guru dan Staff/Non-Guru punya peringkat masing-masing.'
  }
  return 'Hanya guru aktif yang masuk ranking (default).'
})
const reportLeaderboardRows = computed(() => {
  const lb = report.value?.leaderboard
  if (!lb) return []
  if (Array.isArray(lb)) return lb
  return lb.rows || []
})
const reportLeaderboardTitle = computed(() => {
  const mode = report.value?.leaderboard_mode || report.value?.leaderboard?.mode || 'guru_only'
  if (mode === 'combined') return 'Top 10 Pegawai (neto)'
  if (mode === 'separated') return 'Top 10 Peringkat (dipisah)'
  return 'Top 10 Guru (neto)'
})
const reportRecapTitle = computed(() => {
  const mode = report.value?.leaderboard_mode || report.value?.leaderboard?.mode || 'guru_only'
  if (mode === 'guru_only') return 'Rekap Semua Guru'
  return 'Rekap Semua Pegawai'
})
const paginationPages = computed(() => {
  const total = Math.max(1, Number(meta.last_page) || 1)
  const current = Math.min(Math.max(1, Number(meta.current_page) || 1), total)
  const windowSize = 5
  let start = Math.max(1, current - Math.floor(windowSize / 2))
  const end = Math.min(total, start + windowSize - 1)
  start = Math.max(1, end - windowSize + 1)
  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function setMeta(pagination = {}, rowCount = 0, defaultPerPage = 15, target = meta) {
  target.current_page = Number(pagination.current_page) || 1
  target.last_page = Number(pagination.last_page) || 1
  target.per_page = Number(pagination.per_page) || defaultPerPage
  target.total = Number(pagination.total) || rowCount
  const offset = (target.current_page - 1) * target.per_page
  target.from = rowCount ? (Number(pagination.from) || offset + 1) : 0
  target.to = rowCount ? (Number(pagination.to) || offset + rowCount) : 0
}

function applyListMeta(source) {
  meta.current_page = source.current_page
  meta.last_page = source.last_page
  meta.per_page = source.per_page
  meta.total = source.total
  meta.from = source.from
  meta.to = source.to
}

function periodCacheKey() {
  return `${period.academic_year_id || 'all'}|${period.semester_id || 'all'}|${search.value || ''}`
}

function rowNumber(index) {
  return (meta.current_page - 1) * meta.per_page + index + 1
}

const primaryActionLabel = computed(() => {
  if (!canManageFull.value && (tab.value === 'violations' || tab.value === 'pending_violations' || mainTab.value === 'catatan' || mainTab.value === 'usulan')) {
    return 'Catat Pelanggaran'
  }
  if (tab.value === 'types' && canManageFull.value) return 'Tambah Jenis Prestasi'
  if (tab.value === 'violation_types' && canManageFull.value) return 'Tambah Jenis Pelanggaran'
  if (tab.value === 'rewards' && canManageFull.value) return 'Tambah Reward'
  return ''
})

function primaryActionClick() {
  if (!canManageFull.value || tab.value === 'violations' || tab.value === 'pending_violations') openAddViolation()
  else if (tab.value === 'types') openAddType()
  else if (tab.value === 'violation_types') openAddViolationType()
  else if (tab.value === 'rewards') openAddReward()
}

function openAddFromToolbar(kind) {
  if (kind === 'achievements') {
    if (tab.value !== 'achievements') switchTab('achievements')
    openAddAchievement()
  } else if (kind === 'violations') {
    if (tab.value !== 'violations') switchTab('violations')
    openAddViolation()
  }
}

function switchMainTab(next) {
  if (next === 'catatan') {
    switchTab(canManageFull.value
      ? (tab.value === 'violations' ? 'violations' : 'achievements')
      : 'violations')
  } else if (next === 'usulan') {
    if (canManageFull.value) {
      switchTab(tab.value === 'pending_violations' ? 'pending_violations' : 'pending')
    } else {
      switchTab('pending_violations')
    }
  } else if (next === 'poin') {
    switchTab(tab.value === 'leaderboard' ? 'leaderboard' : 'points')
  } else if (next === 'laporan') {
    switchTab('report')
  } else if (next === 'pengaturan') {
    switchTab(PENGATURAN_TABS.includes(tab.value) ? tab.value : 'types')
  }
}

function periodParams() {
  return {
    academic_year_id: period.academic_year_id,
    semester_id: period.semester_id,
  }
}

function categoryLabel(c) {
  const map = {
    akademik: 'Akademik',
    pengembangan: 'Pengembangan',
    pengabdian: 'Pengabdian',
    inovasi: 'Inovasi',
    kedisiplinan: 'Kedisiplinan',
  }
  return map[c] || c || '-'
}

function violationCategoryLabel(c) {
  const map = {
    kehadiran: 'Kehadiran',
    kedisiplinan: 'Kedisiplinan',
    administrasi: 'Administrasi',
    lainnya: 'Lainnya',
  }
  return map[c] || c || '-'
}

function levelLabel(l) {
  const map = {
    sekolah: 'Sekolah',
    kabupaten: 'Kabupaten',
    provinsi: 'Provinsi',
    nasional: 'Nasional',
    internasional: 'Internasional',
  }
  return map[l] || '-'
}

function statusLabel(s) {
  return { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }[s] || s
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID')
}

function flash(msg, isError = false) {
  if (isError) {
    error.value = msg
    success.value = ''
  } else {
    success.value = msg
    error.value = ''
  }
  setTimeout(() => {
    success.value = ''
    error.value = ''
  }, 3500)
}

async function switchTab(next) {
  tab.value = next
  const key = periodCacheKey()
  if (next === 'achievements' && achievements.value.length && listCache.achievementsPeriodKey === key) {
    applyListMeta(achievementsMeta)
    return
  }
  if (next === 'violations' && violations.value.length && listCache.violationsPeriodKey === key) {
    applyListMeta(violationsMeta)
    return
  }
  await reloadCurrent()
}

async function onPeriodChange() {
  listCache.achievementsPeriodKey = ''
  listCache.violationsPeriodKey = ''
  if (period.academic_year_id) {
    try {
      const res = await semesterApi.getByAcademicYear(period.academic_year_id)
      semesters.value = res.data?.data || res.data || []
    } catch {
      semesters.value = []
    }
  }
  await reloadCurrent()
}

function debounceReload() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    listCache.achievementsPeriodKey = ''
    listCache.violationsPeriodKey = ''
    reloadCurrent()
  }, 350)
}

async function reloadCurrent() {
  if (tab.value === 'achievements') await loadAchievements(1)
  else if (tab.value === 'pending') await loadAchievements(1, 'pending')
  else if (tab.value === 'violations') await loadViolations(1)
  else if (tab.value === 'pending_violations') await loadViolations(1, 'pending')
  else if (tab.value === 'points') await loadPoints(1)
  else if (tab.value === 'leaderboard') await loadLeaderboard()
  else if (tab.value === 'types') await loadTypes()
  else if (tab.value === 'violation_types') await loadViolationTypes()
  else if (tab.value === 'rewards') await loadRewards()
  else if (tab.value === 'report') await loadReport()
}

async function loadAchievements(page = 1, status = null) {
  loading.value = true
  try {
    const params = {
      ...periodParams(),
      page,
      per_page: 15,
      search: search.value || undefined,
      status: status || (tab.value === 'pending' ? 'pending' : undefined),
    }
    const res = await teacherAchievementApi.getAll(params)
    achievements.value = res.data?.data || []
    const m = res.data?.meta || {}
    setMeta(m, achievements.value.length, 15)
    setMeta(m, achievements.value.length, 15, achievementsMeta)
    if (!status && tab.value !== 'pending') {
      listCache.achievementsPeriodKey = periodCacheKey()
    }
    if (tab.value === 'pending' || status === 'pending') {
      pendingCount.value = meta.total
    }
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat prestasi', true)
  } finally {
    loading.value = false
  }
}

async function refreshPendingCount() {
  try {
    const res = await teacherPointApi.getPendingCounts()
    const data = res.data?.data || {}
    pendingCount.value = data.pending_achievements || 0
    pendingViolationCount.value = data.pending_violations || 0
  } catch {
    /* ignore */
  }
}

async function loadViolations(page = 1, status = null) {
  loading.value = true
  try {
    const params = {
      ...periodParams(),
      page,
      per_page: 15,
      search: search.value || undefined,
      status: status || (tab.value === 'pending_violations' ? 'pending' : undefined),
    }
    const res = await teacherViolationApi.getAll(params)
    violations.value = res.data?.data || []
    const m = res.data?.meta || {}
    setMeta(m, violations.value.length, 15)
    setMeta(m, violations.value.length, 15, violationsMeta)
    if (!status && tab.value !== 'pending_violations') {
      listCache.violationsPeriodKey = periodCacheKey()
    }
    if (tab.value === 'pending_violations' || status === 'pending') {
      pendingViolationCount.value = meta.total
    }
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat pelanggaran', true)
  } finally {
    loading.value = false
  }
}

async function loadViolationTypes() {
  loading.value = true
  try {
    const res = canManageFull.value
      ? await teacherViolationTypeApi.getAll({ active_only: false })
      : await teacherViolationTypeApi.getActive()
    violationTypes.value = res.data?.data || res.data || []
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat jenis pelanggaran', true)
  } finally {
    loading.value = false
  }
}

async function loadPoints(page = 1) {
  loading.value = true
  try {
    const res = await teacherPointApi.getAll({
      ...periodParams(),
      page,
      per_page: 12,
      search: search.value || undefined,
      with_points_only: withPointsOnly.value ? 1 : undefined,
    })
    pointRows.value = res.data?.data || []
    const m = res.data?.meta || {}
    setMeta(m, pointRows.value.length, 12)
    pendingCount.value = m.pending_count ?? pendingCount.value
    pendingViolationCount.value = m.pending_violation_count ?? pendingViolationCount.value
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat poin guru', true)
  } finally {
    loading.value = false
  }
}

async function loadLeaderboard() {
  loading.value = true
  try {
    await ensureInstitutionLoaded()
    const mode = institution.value?.teacher_appreciation_leaderboard_mode || 'guru_only'
    leaderboardMode.value = mode
    leaderboardModeDraft.value = mode

    const res = await teacherPointApi.getLeaderboard({
      ...periodParams(),
      limit: 100,
    })
    const payload = res.data?.data
    if (Array.isArray(payload)) {
      leaderboardRows.value = payload
      leaderboardGuruRows.value = []
      leaderboardStaffRows.value = []
      leaderboardMode.value = res.data?.meta?.mode || mode
    } else {
      leaderboardMode.value = payload?.mode || mode
      leaderboardModeDraft.value = leaderboardMode.value
      leaderboardRows.value = payload?.rows || []
      leaderboardGuruRows.value = payload?.guru || []
      leaderboardStaffRows.value = payload?.staff || []
    }
  } catch (e) {
    leaderboardRows.value = []
    leaderboardGuruRows.value = []
    leaderboardStaffRows.value = []
    flash(e.formattedMessage || 'Gagal memuat peringkat', true)
  } finally {
    loading.value = false
  }
}

async function saveLeaderboardMode() {
  if (!institution.value?.id) {
    await ensureInstitutionLoaded()
  }
  if (!institution.value?.id) {
    flash('Institusi tidak ditemukan.', true)
    leaderboardModeDraft.value = leaderboardMode.value
    return
  }
  if (leaderboardModeDraft.value === leaderboardMode.value) return

  savingLeaderboardMode.value = true
  try {
    const res = await institutionApi.update(institution.value.id, {
      teacher_appreciation_leaderboard_mode: leaderboardModeDraft.value,
    })
    institution.value = res.data?.data || {
      ...institution.value,
      teacher_appreciation_leaderboard_mode: leaderboardModeDraft.value,
    }
    leaderboardMode.value = leaderboardModeDraft.value
    flash('Mode peringkat disimpan.')
    await loadLeaderboard()
  } catch (e) {
    leaderboardModeDraft.value = leaderboardMode.value
    flash(e.formattedMessage || 'Gagal menyimpan mode peringkat', true)
  } finally {
    savingLeaderboardMode.value = false
  }
}

async function loadTypes() {
  loading.value = true
  try {
    const res = await teacherAchievementTypeApi.getAll({ active_only: false })
    types.value = res.data?.data || res.data || []
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat jenis prestasi', true)
  } finally {
    loading.value = false
  }
}

async function loadRewards() {
  loading.value = true
  try {
    const res = await teacherPointRewardApi.getAll({ active_only: false })
    rewards.value = res.data?.data || res.data || []
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat aturan reward', true)
  } finally {
    loading.value = false
  }
}

async function loadReport() {
  loading.value = true
  try {
    await ensureInstitutionLoaded()
    const res = await teacherPointApi.getReportSummary(periodParams())
    report.value = res.data?.data || null
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat laporan', true)
  } finally {
    loading.value = false
  }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function reportPeriodLabel() {
  const year = academicYears.value.find((y) => String(y.id) === String(period.academic_year_id))
  const sem = semesters.value.find((s) => String(s.id) === String(period.semester_id))
  return [
    year?.name || year?.code || 'Semua Tahun Ajaran',
    sem?.name || 'Semua Semester',
  ].join(' · ')
}

function buildReportPrintBody() {
  const r = report.value || {}
  const achCats = (r.by_category || []).map((c) => `
    <tr>
      <td>${escapeHtml(categoryLabel(c.category))}</td>
      <td class="num">${escapeHtml(c.count)}</td>
      <td class="num">+${escapeHtml(c.points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="3">Belum ada data</td></tr>'

  const vioCats = (r.violations_by_category || []).map((c) => `
    <tr>
      <td>${escapeHtml(violationCategoryLabel(c.category))}</td>
      <td class="num">${escapeHtml(c.count)}</td>
      <td class="num">−${escapeHtml(c.points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="3">Belum ada data</td></tr>'

  const lb = r.leaderboard
  const mode = r.leaderboard_mode || lb?.mode || 'guru_only'
  const renderLeaderboardRows = (rows) => (rows || []).map((row) => `
    <tr>
      <td class="num">${escapeHtml(row.rank)}</td>
      <td>
        ${escapeHtml(row.employee?.name || '-')}
        <div class="cell-note">${escapeHtml(row.employee?.nip || row.employee?.nuptk || 'Tanpa NIP/NUPTK')}${row.employee?.type ? ` · ${escapeHtml(row.employee.type)}` : ''}</div>
      </td>
      <td class="num">+${escapeHtml(row.achievement_points || 0)}</td>
      <td class="num">−${escapeHtml(row.violation_points || 0)}</td>
      <td class="num"><strong>${escapeHtml(row.total_points)}</strong></td>
    </tr>
  `).join('') || '<tr><td colspan="5">Belum ada ranking</td></tr>'

  const leaderboardRowsPrint = Array.isArray(lb) ? lb : (lb?.rows || [])
  let leaderboardSectionHtml = ''
  if (mode === 'separated') {
    leaderboardSectionHtml = `
    <h2>3. Top 10 Peringkat (Dipisah)</h2>
    <h3>Guru</h3>
    <table>
      <thead>
        <tr>
          <th class="num">#</th>
          <th>Nama</th>
          <th class="num">Prestasi</th>
          <th class="num">Pelanggaran</th>
          <th class="num">Neto</th>
        </tr>
      </thead>
      <tbody>${renderLeaderboardRows(lb?.guru)}</tbody>
    </table>
    <h3>Staff / Non-Guru</h3>
    <table>
      <thead>
        <tr>
          <th class="num">#</th>
          <th>Nama</th>
          <th class="num">Prestasi</th>
          <th class="num">Pelanggaran</th>
          <th class="num">Neto</th>
        </tr>
      </thead>
      <tbody>${renderLeaderboardRows(lb?.staff)}</tbody>
    </table>`
  } else {
    const title = mode === 'combined' ? '3. Top 10 Pegawai (Poin Neto)' : '3. Top 10 Guru (Poin Neto)'
    leaderboardSectionHtml = `
    <h2>${title}</h2>
    <table>
      <thead>
        <tr>
          <th class="num">#</th>
          <th>Nama</th>
          <th class="num">Prestasi</th>
          <th class="num">Pelanggaran</th>
          <th class="num">Neto</th>
        </tr>
      </thead>
      <tbody>${renderLeaderboardRows(leaderboardRowsPrint)}</tbody>
    </table>`
  }

  const teacherRecap = (r.teacher_recap || []).map((row, index) => `
    <tr>
      <td class="num">${index + 1}</td>
      <td>
        ${escapeHtml(row.name || '-')}
        <div class="cell-note">${escapeHtml(row.nip || row.nuptk || 'Tanpa NIP/NUPTK')}${row.type ? ` · ${escapeHtml(row.type)}` : ''}</div>
      </td>
      <td class="num">${escapeHtml(row.achievements_count || 0)}</td>
      <td class="num">+${escapeHtml(row.achievement_points || 0)}</td>
      <td class="num">${escapeHtml(row.violations_count || 0)}</td>
      <td class="num">−${escapeHtml(row.violation_points || 0)}</td>
      <td class="num"><strong>${escapeHtml(row.total_points ?? 0)}</strong></td>
    </tr>
  `).join('') || '<tr><td colspan="7">Belum ada data aktif</td></tr>'

  const recapTitle = mode === 'guru_only' ? '4. Rekap Semua Guru Aktif' : '4. Rekap Semua Pegawai Aktif'

  const achievementDetails = (r.achievement_details || []).map((item, index) => `
    <tr>
      <td class="num">${index + 1}</td>
      <td>${escapeHtml(formatDate(item.achievement_date))}</td>
      <td>
        ${escapeHtml(item.employee_name || '-')}
        <div class="cell-note">${escapeHtml(item.employee_nip || item.employee_nuptk || 'Tanpa NIP/NUPTK')}</div>
      </td>
      <td>
        ${escapeHtml(item.title || item.type_name || '-')}
        <div class="cell-note">${escapeHtml(categoryLabel(item.category))}</div>
      </td>
      <td>${escapeHtml(levelLabel(item.level))}</td>
      <td class="num">+${escapeHtml(item.point_value || 0)}</td>
      <td>${escapeHtml(item.recorded_by || '-')}</td>
    </tr>
  `).join('') || '<tr><td colspan="7">Belum ada prestasi disetujui pada periode ini</td></tr>'

  const violationDetails = (r.violation_details || []).map((item, index) => `
    <tr>
      <td class="num">${index + 1}</td>
      <td>${escapeHtml(formatDate(item.violation_date))}</td>
      <td>
        ${escapeHtml(item.employee_name || '-')}
        <div class="cell-note">${escapeHtml(item.employee_nip || item.employee_nuptk || 'Tanpa NIP/NUPTK')}</div>
      </td>
      <td>
        ${escapeHtml(item.type_name || '-')}
        <div class="cell-note">${escapeHtml(violationCategoryLabel(item.category))}</div>
      </td>
      <td class="num">−${escapeHtml(item.point_value || 0)}</td>
      <td>${escapeHtml(item.sanction || '-')}</td>
      <td>${escapeHtml(item.reported_by || '-')}</td>
    </tr>
  `).join('') || '<tr><td colspan="7">Belum ada pelanggaran disetujui pada periode ini</td></tr>'

  return `
    <div class="stats">
      <div class="stat">
        <div class="stat-label">Poin Prestasi</div>
        <div class="stat-value">+${escapeHtml(r.achievement_points || 0)}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Poin Pelanggaran</div>
        <div class="stat-value">−${escapeHtml(r.violation_points || 0)}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Total Neto</div>
        <div class="stat-value">${escapeHtml(r.total_points ?? 0)}</div>
      </div>
      <div class="stat">
        <div class="stat-label">Guru Tercatat</div>
        <div class="stat-value">${escapeHtml(r.unique_teachers || 0)}</div>
      </div>
    </div>
    <p class="note">
      Prestasi disetujui: ${escapeHtml(r.total_achievements || 0)} ·
      Pelanggaran disetujui: ${escapeHtml(r.total_violations || 0)} ·
      Menunggu prestasi: ${escapeHtml(r.pending_count || 0)} ·
      Menunggu pelanggaran: ${escapeHtml(r.pending_violation_count || 0)}
    </p>
    <h2>1. Distribusi Prestasi</h2>
    <table>
      <thead><tr><th>Kategori</th><th class="num">Jumlah</th><th class="num">Poin</th></tr></thead>
      <tbody>${achCats}</tbody>
    </table>
    <h2>2. Distribusi Pelanggaran</h2>
    <table>
      <thead><tr><th>Kategori</th><th class="num">Jumlah</th><th class="num">Poin</th></tr></thead>
      <tbody>${vioCats}</tbody>
    </table>
    ${leaderboardSectionHtml}
    <section class="report-section page-break">
      <h2>${recapTitle}</h2>
      <table class="compact">
        <thead>
          <tr>
            <th class="num">No.</th>
            <th>Nama</th>
            <th class="num">Jml Prestasi</th>
            <th class="num">Poin Prestasi</th>
            <th class="num">Jml Pelanggaran</th>
            <th class="num">Poin Pelanggaran</th>
            <th class="num">Neto</th>
          </tr>
        </thead>
        <tbody>${teacherRecap}</tbody>
      </table>
    </section>
    <section class="report-section page-break">
      <h2>5. Lampiran Daftar Prestasi Disetujui</h2>
      <p class="note">Jumlah data: ${escapeHtml((r.achievement_details || []).length)}</p>
      <table class="compact">
        <thead>
          <tr>
            <th class="num">No.</th>
            <th>Tanggal</th>
            <th>Guru</th>
            <th>Prestasi</th>
            <th>Level</th>
            <th class="num">Poin</th>
            <th>Pencatat</th>
          </tr>
        </thead>
        <tbody>${achievementDetails}</tbody>
      </table>
    </section>
    <section class="report-section page-break">
      <h2>6. Lampiran Daftar Pelanggaran Disetujui</h2>
      <p class="note">Jumlah data: ${escapeHtml((r.violation_details || []).length)}</p>
      <table class="compact">
        <thead>
          <tr>
            <th class="num">No.</th>
            <th>Tanggal</th>
            <th>Guru</th>
            <th>Pelanggaran</th>
            <th class="num">Poin</th>
            <th>Sanksi</th>
            <th>Pelapor</th>
          </tr>
        </thead>
        <tbody>${violationDetails}</tbody>
      </table>
    </section>
  `
}

async function printReportPdf() {
  if (!report.value) {
    flash('Tidak ada data laporan untuk dicetak', true)
    return
  }

  printing.value = true
  try {
    await ensureInstitutionLoaded()
    const inst = institution.value || {}
    const instName = inst.name || 'Sekolah'
    const fullAddress = [
      inst.address,
      inst.village ? `Desa/Kel. ${inst.village}` : '',
      inst.sub_district ? `Kec. ${inst.sub_district}` : '',
      inst.district,
      inst.province,
      inst.postal_code,
    ].filter(Boolean).join(', ')
    const periodLabel = reportPeriodLabel()
    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const placeDate = `${inst.district || inst.city || '........................'}, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric',
    })}`
    const filename = `Laporan_Apresiasi_Guru_${new Date().toISOString().slice(0, 10)}.pdf`

    const content = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${escapeHtml(filename)}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; margin: 16px; }
    h1 { font-size: 16px; margin: 12px 0 4px; text-align: center; text-transform: uppercase; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .subtitle { text-align: center; color: #444; margin-bottom: 12px; }
    .period { text-align: center; margin-bottom: 16px; font-size: 11px; }
    h2 { font-size: 12px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; }
    td.num, th.num { text-align: right; }
    tr { page-break-inside: avoid; }
    table.compact th, table.compact td { padding: 3px 4px; font-size: 8.5px; }
    .cell-note { color: #555; font-size: 8px; margin-top: 2px; }
    .report-section { break-before: page; }
    .stats { display: flex; gap: 8px; margin-bottom: 12px; }
    .stat { flex: 1; border: 1px solid #333; padding: 8px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #555; }
    .stat-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .footer { display: flex; justify-content: space-between; margin-top: 28px; page-break-inside: avoid; }
    .footer-right { text-align: center; min-width: 220px; }
    .sig-space { height: 56px; }
    @media print {
      @page { size: A4 portrait; margin: 10mm 12mm 10mm 10mm; }
      body { margin: 0; padding-right: 1px; }
      .page-break { break-before: page; page-break-before: always; }
    }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${inst.logo ? `<img src="${escapeHtml(inst.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
      <div class="kop-text">
        ${inst.foundation_name ? `<div class="foundation">${escapeHtml(inst.foundation_name)}</div>` : ''}
        <div class="school">${escapeHtml(instName)}</div>
        <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
        <div class="school-info">
          NPSN: ${escapeHtml(inst.npsn || '-')}
          ${inst.nss ? ` · ${getNssLabel(inst.level)}: ${escapeHtml(inst.nss)}` : ''}
          ${inst.phone ? ` · Telp: ${escapeHtml(inst.phone)}` : ''}
          ${inst.email ? ` · Email: ${escapeHtml(inst.email)}` : ''}
          ${inst.website ? ` · ${escapeHtml(inst.website)}` : ''}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>Laporan Apresiasi Guru</h1>
  <div class="subtitle">Rekap Prestasi, Pelanggaran &amp; Poin Neto</div>
  <div class="period"><strong>Periode:</strong> ${escapeHtml(periodLabel)}</div>
  ${buildReportPrintBody()}
  <div class="footer">
    <div>
      <strong>Dibuat pada:</strong><br>${escapeHtml(createdAt)}
    </div>
    <div class="footer-right">
      ${escapeHtml(placeDate)}<br>
      ${escapeHtml(getPrincipalTitle(inst.level))}
      <div class="sig-space"></div>
      <strong>${escapeHtml(inst.principal_name || '___________________')}</strong>
      <br>NIP. ${escapeHtml(inst.principal_nip || '___________________')}
    </div>
  </div>
</body>
</html>`

    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      flash('Popup diblokir. Izinkan popup untuk mencetak PDF.', true)
      return
    }
    printWindow.document.write(content)
    printWindow.document.close()
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    if (import.meta.env.DEV) console.error('Error printing teacher appreciation report:', err)
    flash('Gagal menyiapkan cetak PDF', true)
  } finally {
    printing.value = false
  }
}

function onTypeChange() {
  const type = activeTypes.value.find((t) => String(t.id) === String(achievementForm.achievement_type_id))
  if (!type) return
  const multipliers = type.level_multipliers || {}
  const factor = achievementForm.level ? Number(multipliers[achievementForm.level] || 1) : 1
  achievementForm.point_value = Math.round(Number(type.point_value || 0) * factor)
  if (!achievementForm.title) achievementForm.title = type.name
}

function onEvidenceChange(e) {
  evidenceFile.value = e.target.files?.[0] || null
}

async function ensureInstitutionLoaded() {
  // Reload if missing identity fields used by the print letterhead / signature.
  const inst = institution.value
  if (inst?.name && Object.prototype.hasOwnProperty.call(inst, 'foundation_name') && inst.level) return
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data || res.data || null
  } catch {
    /* ignore */
  }
}

async function ensureEmployeesLoaded() {
  if (employees.value.length) return
  try {
    const res = canManageFull.value
      ? await teacherPointApi.getEmployees()
      : await teacherPointApi.getEmployeesLite()
    employees.value = res.data?.data || res.data || []
  } catch {
    employees.value = []
  }
}

async function ensureTypesLoaded() {
  if (types.value.length || !canManageFull.value) return
  try {
    const res = await teacherAchievementTypeApi.getAll({ active_only: false })
    types.value = res.data?.data || res.data || []
  } catch {
    types.value = []
  }
}

async function ensureViolationTypesLoaded() {
  if (violationTypes.value.length) return
  try {
    const res = canManageFull.value
      ? await teacherViolationTypeApi.getAll({ active_only: false })
      : await teacherViolationTypeApi.getActive()
    violationTypes.value = res.data?.data || res.data || []
  } catch {
    violationTypes.value = []
  }
}

async function ensureRewardsLoaded() {
  if (rewards.value.length || !canManageFull.value) return
  try {
    const res = await teacherPointRewardApi.getAll({ active_only: false })
    rewards.value = res.data?.data || res.data || []
  } catch {
    rewards.value = []
  }
}

async function reloadAndRefreshPending() {
  await Promise.all([reloadCurrent(), refreshPendingCount()])
}

function openAddAchievement() {
  Promise.all([ensureEmployeesLoaded(), ensureTypesLoaded()]).then(() => {
    editingAchievement.value = null
    Object.assign(achievementForm, {
      employee_id: '',
      achievement_type_id: '',
      title: '',
      achievement_date: new Date().toISOString().slice(0, 10),
      level: '',
      point_value: 0,
      notes: '',
    })
    evidenceFile.value = null
    showAchievementModal.value = true
  })
}

function openEditAchievement(item) {
  Promise.all([ensureEmployeesLoaded(), ensureTypesLoaded()]).then(() => {
    editingAchievement.value = item
    Object.assign(achievementForm, {
      employee_id: String(item.employee_id),
      achievement_type_id: String(item.achievement_type_id),
      title: item.title || '',
      achievement_date: item.achievement_date,
      level: item.level || '',
      point_value: item.point_value,
      notes: item.notes || '',
    })
    evidenceFile.value = null
    showAchievementModal.value = true
  })
}

async function saveAchievement() {
  saving.value = true
  try {
    const payload = {
      employee_id: achievementForm.employee_id,
      achievement_type_id: achievementForm.achievement_type_id,
      title: achievementForm.title,
      achievement_date: achievementForm.achievement_date,
      level: achievementForm.level || undefined,
      point_value: achievementForm.point_value,
      notes: achievementForm.notes,
      ...periodParams(),
    }
    if (evidenceFile.value) payload.evidence = evidenceFile.value
    if (editingAchievement.value) {
      await teacherAchievementApi.update(editingAchievement.value.id, payload)
      flash('Prestasi diperbarui')
    } else {
      await teacherAchievementApi.create(payload)
      flash('Prestasi dicatat')
    }
    showAchievementModal.value = false
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyimpan prestasi', true)
  } finally {
    saving.value = false
  }
}

async function deleteAchievement(item) {
  if (!confirm(`Hapus prestasi "${item.title || item.achievement_type?.name}"?`)) return
  try {
    await teacherAchievementApi.delete(item.id)
    flash('Prestasi dihapus')
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menghapus', true)
  }
}

async function approveItem(item) {
  try {
    await teacherAchievementApi.approve(item.id)
    flash('Usulan disetujui')
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyetujui', true)
  }
}

function openReject(item) {
  rejectTarget.value = item
  rejectViolationTarget.value = null
  rejectNotes.value = ''
  showRejectModal.value = true
}

function openRejectViolation(item) {
  rejectViolationTarget.value = item
  rejectTarget.value = null
  rejectNotes.value = ''
  showRejectModal.value = true
}

async function rejectItem() {
  saving.value = true
  try {
    if (rejectViolationTarget.value) {
      await teacherViolationApi.reject(rejectViolationTarget.value.id, { review_notes: rejectNotes.value })
      flash('Laporan pelanggaran ditolak')
    } else {
      await teacherAchievementApi.reject(rejectTarget.value.id, { review_notes: rejectNotes.value })
      flash('Usulan ditolak')
    }
    showRejectModal.value = false
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menolak', true)
  } finally {
    saving.value = false
  }
}

function openAddViolation() {
  Promise.all([ensureEmployeesLoaded(), ensureViolationTypesLoaded()]).then(() => {
    Object.assign(violationForm, {
      employee_id: '',
      violation_type_id: '',
      violation_date: new Date().toISOString().slice(0, 10),
      point_value: 0,
      sanction: '',
      notes: '',
    })
    violationEvidenceFile.value = null
    showViolationModal.value = true
  })
}

function onViolationTypeChange() {
  const type = activeViolationTypes.value.find((t) => String(t.id) === String(violationForm.violation_type_id))
  if (!type) return
  violationForm.point_value = Number(type.point_weight || 0)
  if (!violationForm.sanction) violationForm.sanction = type.default_sanction || ''
}

function onViolationEvidenceChange(e) {
  violationEvidenceFile.value = e.target.files?.[0] || null
}

async function saveViolation() {
  saving.value = true
  try {
    const payload = {
      employee_id: violationForm.employee_id,
      violation_type_id: violationForm.violation_type_id,
      violation_date: violationForm.violation_date,
      point_value: violationForm.point_value,
      sanction: violationForm.sanction,
      notes: violationForm.notes,
      ...periodParams(),
    }
    if (violationEvidenceFile.value) payload.evidence = violationEvidenceFile.value
    await teacherViolationApi.create(payload)
    flash(canManageFull.value ? 'Pelanggaran dicatat & disetujui' : 'Pelanggaran diajukan, menunggu persetujuan KS')
    showViolationModal.value = false
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal mencatat pelanggaran', true)
  } finally {
    saving.value = false
  }
}

async function approveViolation(item) {
  try {
    await teacherViolationApi.approve(item.id)
    flash('Pelanggaran disetujui — skor neto berkurang')
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyetujui', true)
  }
}

async function deleteViolation(item) {
  if (!confirm(`Hapus pelanggaran "${item.violation_type?.name}"?`)) return
  try {
    await teacherViolationApi.delete(item.id)
    flash('Pelanggaran dihapus')
    await reloadAndRefreshPending()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menghapus', true)
  }
}

function openAddViolationType() {
  editingViolationType.value = null
  Object.assign(violationTypeForm, {
    name: '', code: '', category: 'kedisiplinan', point_weight: 5,
    default_sanction: '', description: '', is_active: true,
  })
  showViolationTypeModal.value = true
}

function openEditViolationType(t) {
  editingViolationType.value = t
  Object.assign(violationTypeForm, {
    name: t.name,
    code: t.code || '',
    category: t.category || 'kedisiplinan',
    point_weight: t.point_weight,
    default_sanction: t.default_sanction || '',
    description: t.description || '',
    is_active: !!t.is_active,
  })
  showViolationTypeModal.value = true
}

async function saveViolationType() {
  saving.value = true
  try {
    if (editingViolationType.value) {
      await teacherViolationTypeApi.update(editingViolationType.value.id, { ...violationTypeForm })
    } else {
      await teacherViolationTypeApi.create({ ...violationTypeForm })
    }
    flash('Jenis pelanggaran disimpan')
    showViolationTypeModal.value = false
    await loadViolationTypes()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyimpan jenis pelanggaran', true)
  } finally {
    saving.value = false
  }
}

async function deleteViolationType(t) {
  if (!confirm(`Hapus jenis "${t.name}"?`)) return
  try {
    await teacherViolationTypeApi.delete(t.id)
    flash('Jenis pelanggaran dihapus')
    await loadViolationTypes()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menghapus jenis', true)
  }
}

function openAddType() {
  editingType.value = null
  Object.assign(typeForm, { name: '', code: '', category: 'akademik', point_value: 10, description: '', is_active: true })
  showTypeModal.value = true
}

function openEditType(t) {
  editingType.value = t
  Object.assign(typeForm, {
    name: t.name,
    code: t.code || '',
    category: t.category || 'akademik',
    point_value: t.point_value,
    description: t.description || '',
    is_active: !!t.is_active,
  })
  showTypeModal.value = true
}

async function saveType() {
  saving.value = true
  try {
    if (editingType.value) await teacherAchievementTypeApi.update(editingType.value.id, { ...typeForm })
    else await teacherAchievementTypeApi.create({ ...typeForm })
    flash('Jenis prestasi disimpan')
    showTypeModal.value = false
    await loadTypes()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyimpan jenis', true)
  } finally {
    saving.value = false
  }
}

async function deleteType(t) {
  if (!confirm(`Hapus jenis "${t.name}"?`)) return
  try {
    await teacherAchievementTypeApi.delete(t.id)
    flash('Jenis dihapus')
    await loadTypes()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menghapus jenis', true)
  }
}

function openAddReward() {
  editingReward.value = null
  Object.assign(rewardForm, { point_min: 50, point_max: 99, reward_name: '', description: '', is_active: true })
  showRewardModal.value = true
}

function openEditReward(r) {
  editingReward.value = r
  Object.assign(rewardForm, {
    point_min: r.point_min,
    point_max: r.point_max,
    reward_name: r.reward_name,
    description: r.description || '',
    is_active: !!r.is_active,
  })
  showRewardModal.value = true
}

async function saveReward() {
  saving.value = true
  try {
    if (editingReward.value) await teacherPointRewardApi.update(editingReward.value.id, { ...rewardForm })
    else await teacherPointRewardApi.create({ ...rewardForm })
    flash('Aturan reward disimpan')
    showRewardModal.value = false
    await loadRewards()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menyimpan reward', true)
  } finally {
    saving.value = false
  }
}

async function deleteReward(r) {
  if (!confirm(`Hapus reward "${r.reward_name}"?`)) return
  try {
    await teacherPointRewardApi.delete(r.id)
    flash('Reward dihapus')
    await loadRewards()
  } catch (e) {
    flash(e.formattedMessage || 'Gagal menghapus reward', true)
  }
}

function openRewardLog(row) {
  ensureRewardsLoaded().then(() => {
    rewardLogTarget.value = row
    Object.assign(rewardLogForm, {
      teacher_point_reward_id: row.matched_reward ? String(row.matched_reward.id) : '',
      reward_name: row.matched_reward?.reward_name || '',
      reward_date: new Date().toISOString().slice(0, 10),
      notes: '',
    })
    showRewardLogModal.value = true
  })
}

async function saveRewardLog() {
  saving.value = true
  try {
    await teacherRewardLogApi.create({
      employee_id: rewardLogTarget.value.employee_id,
      teacher_point_reward_id: rewardLogForm.teacher_point_reward_id || undefined,
      reward_name: rewardLogForm.reward_name || undefined,
      reward_date: rewardLogForm.reward_date,
      notes: rewardLogForm.notes,
      score_at_reward: rewardLogTarget.value.total_points,
      ...periodParams(),
    })
    flash('Reward dicatat')
    showRewardLogModal.value = false
  } catch (e) {
    flash(e.formattedMessage || 'Gagal mencatat reward', true)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    if (!canManageFull.value) tab.value = 'violations'
    const res = await teacherPointApi.bootstrap({
      tab: tab.value,
      per_page: 15,
    })
    const data = res.data?.data || {}

    academicYears.value = data.academic_years || []
    semesters.value = data.semesters || []
    institution.value = data.institution || null
    period.academic_year_id = data.period?.academic_year_id || ''
    period.semester_id = data.period?.semester_id || ''
    pendingCount.value = data.pending_achievements || 0
    pendingViolationCount.value = data.pending_violations || 0

    achievements.value = data.achievements || data.list || []
    violations.value = data.violations || []
    setMeta(data.achievements_meta || data.meta || {}, achievements.value.length, 15, achievementsMeta)
    setMeta(data.violations_meta || {}, violations.value.length, 15, violationsMeta)
    listCache.achievementsPeriodKey = periodCacheKey()
    listCache.violationsPeriodKey = periodCacheKey()

    if (tab.value === 'violations' || tab.value === 'pending_violations') {
      applyListMeta(violationsMeta)
    } else {
      applyListMeta(achievementsMeta)
    }
  } catch (e) {
    flash(e.formattedMessage || 'Gagal memuat halaman apresiasi guru', true)
    try {
      await Promise.all([reloadCurrent(), refreshPendingCount()])
    } catch {
      /* ignore */
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page {
  max-width: 100%;
  min-height: calc(100vh - 72px);
  background: #f8fafc;
  margin: -8px -12px;
  padding: 24px;
  border-radius: 0;
}
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 22px;
}
.header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}
.page-header h1 { margin: 4px 0 6px; color: #0f172a; font-size: 26px; line-height: 1.2; letter-spacing: -0.025em; }
.page-header p { margin: 0; color: #64748b; font-size: 13px; }
.eyebrow { color: #059669; font-size: 11px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; }
.btn-primary {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  background: #059669; color: #fff; border: none; border-radius: 10px;
  padding: 10px 16px; font-weight: 700; cursor: pointer;
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.18);
}
.btn-primary:hover:not(:disabled) { background: #047857; transform: translateY(-1px); }
.btn-icon { font-size: 18px; line-height: 1; font-weight: 400; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary {
  display: inline-flex; align-items: center; gap: 8px;
  border: 1px solid #cbd5e1; background: #fff; color: #334155;
  border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-secondary:hover:not(:disabled) { background: #f8fafc; border-color: #94a3b8; }
.btn-secondary:disabled { opacity: 0.55; cursor: not-allowed; }
.btn-sm {
  border: 1px solid #cbd5e1; background: #fff; border-radius: 6px;
  padding: 4px 10px; font-size: 12px; cursor: pointer; margin-right: 4px;
}
.report-toolbar { display: flex; justify-content: flex-end; margin-bottom: 10px; }
.btn-success { background: #ecfdf5; border-color: #6ee7b7; color: #047857; }
.btn-danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.nav-tabs {
  display: flex;
  flex-wrap: nowrap;
  gap: 4px;
  margin-bottom: 16px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 5px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}
.nav-tabs::-webkit-scrollbar { display: none; }
.nav-tab {
  border: 0;
  background: transparent;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
  flex-shrink: 0;
}
.nav-tab:hover { background: #f8fafc; color: #0f172a; }
.nav-tab.active { background: #ecfdf5; color: #047857; box-shadow: inset 0 0 0 1px #a7f3d0; }
.sub-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin: -4px 0 16px;
  padding: 5px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.sub-nav-btn {
  border: 0;
  background: transparent;
  border-radius: 8px;
  padding: 7px 12px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}
.sub-nav-btn:hover { background: #f8fafc; color: #0f172a; }
.sub-nav-btn.active { background: #f0fdf4; color: #047857; box-shadow: inset 0 0 0 1px #a7f3d0; }
.badge {
  display: inline-block; margin-left: 6px; background: #f59e0b; color: #fff;
  border-radius: 999px; padding: 0 6px; font-size: 11px;
}
.filters {
  display: flex; align-items: flex-end; flex-wrap: wrap; gap: 12px;
  margin-bottom: 16px; padding: 14px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
}
.filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 170px; }
.filter-group label { color: #475569; font-size: 11px; font-weight: 700; }
.search-group { flex: 1; min-width: 240px; }
.filter-select, .search-input {
  height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; background: #fff;
}
.filter-select:focus, .search-input:focus { border-color: #10b981; outline: 3px solid rgba(16, 185, 129, 0.1); }
.search-box { position: relative; }
.search-box > span { position: absolute; left: 11px; top: 50%; color: #94a3b8; font-size: 18px; transform: translateY(-54%); pointer-events: none; }
.search-input { width: 100%; padding-left: 34px; box-sizing: border-box; }
.state {
  padding: 20px 16px;
  text-align: center;
  color: #334155;
  background: #fff;
  border-radius: 12px;
  border: 1px dashed #94a3b8;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.state.empty .empty-title {
  margin: 0 0 6px;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.state.empty .empty-desc {
  margin: 0 auto 14px;
  font-size: 13px;
  color: #64748b;
  max-width: 420px;
  line-height: 1.45;
}
.table-wrap { overflow-x: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th, .data-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: middle; }
.data-table th { background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; letter-spacing: 0.035em; text-transform: uppercase; white-space: nowrap; }
.data-table tbody tr:last-child td { border-bottom: 0; }
.data-table tbody tr:hover { background: #f8fafc; }
.col-number { width: 48px; text-align: center !important; color: #64748b; }
.muted { color: #94a3b8; font-size: 12px; }
.piket-tag { color: #b45309; font-weight: 500; }
.notes-clip {
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.points { color: #047857; font-weight: 700; }
.points-minus { color: #b91c1c; font-weight: 700; }
.ok { color: #047857; }
.warn { color: #b45309; font-weight: 600; }
.status { font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
.status.approved { background: #ecfdf5; color: #047857; }
.status.pending { background: #fffbeb; color: #b45309; }
.status.rejected { background: #fef2f2; color: #b91c1c; }
.actions { white-space: nowrap; }
.link { color: #059669; font-size: 12px; }
.cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
.card-head { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
.score { font-size: 22px; font-weight: 800; color: #047857; text-align: right; }
.score span { display: block; font-size: 11px; color: #64748b; font-weight: 600; }
.card-meta { display: flex; flex-wrap: wrap; gap: 8px; font-size: 12px; color: #64748b; margin-bottom: 8px; }
.card-meta .warn { color: #b45309; font-weight: 600; }
.card-meta .reward { color: #0369a1; font-weight: 600; }
.mini-list { margin: 0 0 10px; padding-left: 16px; font-size: 12px; color: #475569; }
.mini-list em { color: #047857; font-style: normal; font-weight: 700; }
.check-inline { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 12px; color: #475569; }
.report-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.stat, .panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
.stat span { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.stat strong { font-size: 24px; color: #0f172a; }
.panel { grid-column: span 2; }
.panel h3 { margin: 0 0 10px; font-size: 15px; }
.report-wide { grid-column: 1 / -1; }
.report-table-wrap { width: 100%; overflow-x: auto; }
.report-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.report-table th,
.report-table td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; vertical-align: top; }
.report-table th { background: #f8fafc; color: #475569; font-size: 11px; text-transform: uppercase; white-space: nowrap; }
.report-table .num { text-align: right; white-space: nowrap; }
.report-subtext { display: block; margin-top: 2px; color: #64748b; font-size: 11px; font-weight: 400; }
.leaderboard { margin: 0; padding-left: 18px; }
.leaderboard li { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.leaderboard-settings {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 12px 20px;
  margin-bottom: 14px;
  padding: 12px 14px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.leaderboard-mode-hint { margin: 0; max-width: 520px; }
.leaderboard-group-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}
.report-subheading {
  margin: 14px 0 8px;
  font-size: 13px;
  color: #475569;
}
.pagination {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  margin-top: 14px; padding: 0 2px;
}
.pagination p { margin: 0; color: #64748b; font-size: 12px; }
.pagination-controls { display: flex; align-items: center; gap: 5px; }
.page-button {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 34px; height: 34px; padding: 0 8px;
  border: 1px solid #cbd5e1; background: #fff; color: #475569;
  border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;
}
.page-button:hover:not(:disabled):not(.active) { border-color: #6ee7b7; color: #047857; background: #f0fdf4; }
.page-button.active { border-color: #059669; background: #059669; color: #fff; }
.page-button:disabled { opacity: 0.4; cursor: not-allowed; }
.page-nav { font-size: 20px; font-weight: 400; }
.error-banner, .success-banner { padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
.error-banner { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.success-banner { background: #ecfdf5; color: #047857; border: 1px solid #bbf7d0; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
  display: flex; align-items: center; justify-content: center; z-index: 80; padding: 16px;
}
.modal {
  width: min(520px, 100%); background: #fff; border-radius: 14px; padding: 20px;
  max-height: 90vh; overflow: auto;
}
.modal h3 { margin: 0 0 14px; }
.modal label { display: block; font-size: 13px; color: #475569; margin-bottom: 10px; }
.modal input, .modal select, .modal textarea {
  display: block; width: 100%; margin-top: 4px; border: 1px solid #cbd5e1;
  border-radius: 8px; padding: 8px 10px; font-size: 13px; box-sizing: border-box;
}
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
@media (max-width: 900px) {
  .report-grid { grid-template-columns: 1fr 1fr; }
  .panel { grid-column: span 2; }
}
@media (max-width: 640px) {
  .page { padding: 16px 12px 24px; }
  .page-header { align-items: stretch; flex-direction: column; gap: 14px; }
  .page-header .btn-primary { width: 100%; }
  .filter-group, .search-group { width: 100%; min-width: 100%; }
  .pagination { align-items: flex-start; flex-direction: column; }
  .pagination-controls { width: 100%; justify-content: flex-end; }
  .report-grid { grid-template-columns: 1fr; }
  .panel { grid-column: span 1; }
}
</style>
