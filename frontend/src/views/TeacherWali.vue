<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <div class="page-header-main">
          <router-link to="/teacher/dashboard" class="back-chip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Dashboard
          </router-link>
          <div>
            <h1>Wali Kelas</h1>
            <p class="page-subtitle">Profil siswa, monitoring kelas, usulan BK/mutasi, jadwal, dan cetak</p>
          </div>
        </div>
        <div v-if="homeroomClasses.length" class="header-tools">
          <div class="export-wrap" ref="exportWrapRef">
            <button type="button" class="btn-secondary" :disabled="!selectedClassId || exporting" @click="exportOpen = !exportOpen">
              {{ exporting ? 'Mengunduh…' : 'Cetak / Export' }}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <div v-if="exportOpen" class="export-menu" role="menu">
              <button type="button" role="menuitem" @click="runExport('roster')">Daftar siswa (PDF)</button>
              <button type="button" role="menuitem" @click="runExport('contacts-pdf')">Kontak ortu (PDF)</button>
              <button type="button" role="menuitem" @click="runExport('contacts-csv')">Kontak ortu (CSV)</button>
              <button type="button" role="menuitem" @click="runExport('attendance')">Rekap absen (PDF)</button>
              <button type="button" role="menuitem" @click="runExport('schedule')">Jadwal kelas (PDF)</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!homeroomClasses.length" class="state-card empty">
        <div class="state-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z" stroke="currentColor" stroke-width="2"/><path d="M9 22V12h6v10" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <h3>Anda belum diangkat sebagai wali kelas</h3>
        <p>Menu ini muncul setelah Anda ditetapkan sebagai wali pada kelas aktif di sekolah ini.</p>
        <router-link to="/teacher/dashboard" class="btn-primary link-btn">Kembali ke dashboard</router-link>
      </div>

      <template v-else>
        <section class="hero-card">
          <div class="hero-main">
            <span class="hero-badge">Kelas Wali</span>
            <h2 class="hero-title">{{ selectedClass?.name || '—' }}</h2>
            <div class="hero-meta">
              <span v-if="selectedClass?.grade">Tingkat {{ selectedClass.grade }}</span>
              <span v-if="selectedClass?.grade && selectedClass?.academic_year" class="dot">·</span>
              <span v-if="selectedClass?.academic_year">{{ selectedClass.academic_year }}</span>
            </div>
          </div>
          <div class="hero-stats">
            <div class="hero-stat">
              <span class="hero-stat-value">{{ dashLoading ? '…' : (dashboard?.students?.total ?? summary.total) }}</span>
              <span class="hero-stat-label">Siswa aktif</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value">{{ dashLoading ? '…' : (dashboard?.students?.male ?? summary.male) }}</span>
              <span class="hero-stat-label">Laki-laki</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value">{{ dashLoading ? '…' : (dashboard?.students?.female ?? summary.female) }}</span>
              <span class="hero-stat-label">Perempuan</span>
            </div>
          </div>
        </section>

        <div v-if="homeroomClasses.length > 1" class="class-switcher">
          <span class="class-switcher-label">Pilih kelas</span>
          <div class="class-chips" role="tablist">
            <button
              v-for="c in homeroomClasses"
              :key="c.id"
              type="button"
              class="class-chip"
              :class="{ active: String(c.id) === String(selectedClassId) }"
              @click="selectClass(c.id)"
            >{{ c.name }}</button>
          </div>
        </div>

        <section class="metrics-grid" aria-label="Ringkasan kelas">
          <button type="button" class="metric-card tone-teal metric-clickable" @click="setPanel('absensi')">
            <span class="metric-label">Absen hari ini</span>
            <div class="metric-row">
              <span><strong>{{ att.hadir }}</strong> hadir</span>
              <span><strong>{{ att.izin }}</strong> izin</span>
              <span><strong>{{ att.sakit }}</strong> sakit</span>
              <span><strong>{{ att.alpha }}</strong> alpa</span>
            </div>
            <span class="metric-hint">{{ att.students_recorded || 0 }} siswa tercatat · lihat rekap →</span>
          </button>
          <button type="button" class="metric-card tone-amber metric-clickable" @click="focusBkHigh">
            <span class="metric-label">Skor BK tinggi</span>
            <span class="metric-value">{{ dashboard?.bk_high_scores?.count ?? 0 }}</span>
            <span class="metric-hint">≥ {{ dashboard?.bk_high_scores?.threshold ?? 20 }} poin · filter siswa →</span>
            <ul v-if="dashboard?.bk_high_scores?.top?.length" class="metric-list">
              <li v-for="s in dashboard.bk_high_scores.top" :key="s.student_id">{{ s.name }} · {{ s.score }}</li>
            </ul>
          </button>
          <button type="button" class="metric-card tone-sky metric-clickable" @click="focusGradesIncomplete">
            <span class="metric-label">Nilai belum lengkap</span>
            <span class="metric-value">{{ dashboard?.grades_incomplete ?? 0 }}</span>
            <span class="metric-hint">siswa tanpa nilai · buka panel nilai →</span>
          </button>
          <button type="button" class="metric-card tone-violet metric-clickable" @click="setPanel('usulan')">
            <span class="metric-label">Usulan menunggu</span>
            <div class="metric-row">
              <span><strong>{{ pending.violations }}</strong> langgar</span>
              <span><strong>{{ pending.achievements }}</strong> prestasi</span>
              <span><strong>{{ pending.mutations }}</strong> mutasi</span>
            </div>
            <span class="metric-hint">Kelola usulan →</span>
          </button>
          <button type="button" class="metric-card tone-rose metric-clickable" @click="focusMissingAccounts">
            <span class="metric-label">Akun login</span>
            <div class="metric-row">
              <span><strong>{{ accounts.with_account }}</strong> siap</span>
              <span><strong>{{ accounts.missing_account }}</strong> belum</span>
              <span><strong>{{ accounts.incomplete_data }}</strong> kurang data</span>
            </div>
            <span class="metric-hint">Buat akun / lengkapi NIK →</span>
          </button>
        </section>

        <div class="charts-grid">
          <AppChart title="Absen hari ini" type="doughnut" :chart-data="attendanceChart" />
          <AppChart title="Komposisi kelas" type="doughnut" :chart-data="genderChart" />
        </div>

        <div class="tab-shell">
        <nav class="section-nav" role="tablist" aria-label="Panel wali">
          <button type="button" role="tab" class="sec-btn" :class="{ active: panel === 'siswa' }" :aria-selected="panel === 'siswa'" @click="setPanel('siswa')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-label">Data Siswa</span>
          </button>
          <button type="button" role="tab" class="sec-btn" :class="{ active: panel === 'absensi' }" :aria-selected="panel === 'absensi'" @click="setPanel('absensi')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-label">Absensi</span>
          </button>
          <button type="button" role="tab" class="sec-btn" :class="{ active: panel === 'nilai' }" :aria-selected="panel === 'nilai'" @click="setPanel('nilai')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19V5a1 1 0 0 1 1-1h10l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="2"/><path d="M14 4v5h5M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-label">Nilai</span>
          </button>
          <button type="button" role="tab" class="sec-btn" :class="{ active: panel === 'usulan' }" :aria-selected="panel === 'usulan'" @click="setPanel('usulan')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-label">Usulan</span>
          </button>
          <button type="button" role="tab" class="sec-btn" :class="{ active: panel === 'jadwal' }" :aria-selected="panel === 'jadwal'" @click="setPanel('jadwal')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="sec-label">Jadwal</span>
          </button>
        </nav>
        <div class="tab-main">

        <!-- Panel: Siswa -->
        <template v-if="panel === 'siswa'">
          <section class="actions-section">
            <div class="section-header">
              <h2>Aksi Wali Kelas</h2>
              <span class="section-meta">{{ visibleActions.length }} pintasan</span>
            </div>
            <div class="actions-grid">
              <router-link v-for="action in visibleActions" :key="action.to" :to="action.to" class="action-card" :class="`action-${action.tone}`">
                <div class="action-icon" aria-hidden="true" v-html="action.icon"></div>
                <div class="action-body">
                  <h4>{{ action.title }}</h4>
                  <p>{{ action.desc }}</p>
                </div>
              </router-link>
              <button type="button" class="action-card action-slate" @click="setPanel('usulan')">
                <div class="action-icon" aria-hidden="true" v-html="icons.usulan"></div>
                <div class="action-body"><h4>Usulan BK & Mutasi</h4><p>Ajukan pelanggaran, prestasi, atau mutasi</p></div>
              </button>
              <button type="button" class="action-card action-indigo" @click="setPanel('jadwal')">
                <div class="action-icon" aria-hidden="true" v-html="icons.jadwal"></div>
                <div class="action-body"><h4>Jadwal Kelas</h4><p>Lihat dan cetak jadwal pelajaran kelas</p></div>
              </button>
            </div>
          </section>

          <section class="panel-card">
            <div class="section-header students-header">
              <div>
                <h2>Data Siswa</h2>
                <p class="section-hint">Klik baris untuk melihat profil 360° (absensi, BK, nilai, usulan)</p>
              </div>
              <div class="students-toolbar">
                <div class="search-wrap">
                  <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  <input v-model="studentQuery" type="search" class="search-input" placeholder="Cari nama, NIS, atau NISN…" @input="onSearchInput">
                </div>
                <button
                  v-if="listFilter"
                  type="button"
                  class="filter-chip"
                  @click="clearListFilter"
                >Filter: {{ listFilterLabel }} ×</button>
                <button
                  v-if="accounts.missing_account > 0"
                  type="button"
                  class="btn-secondary link-btn-sm"
                  :disabled="bulkAccountLoading"
                  @click="bulkEnsureClassAccounts"
                >
                  {{ bulkAccountLoading ? 'Membuat akun…' : `Buat akun (${accounts.missing_account})` }}
                </button>
                <span v-if="!studentsLoading" class="section-meta">{{ filteredStudents.length }}{{ listFilter ? '' : ` / ${pagination.total}` }} siswa</span>
              </div>
            </div>

            <div v-if="studentsLoading" class="state-card soft"><p>Memuat daftar siswa…</p></div>
            <div v-else-if="studentsError" class="state-card empty soft">
              <h3>Gagal memuat siswa</h3>
              <p>{{ studentsError }}</p>
              <button type="button" class="btn-primary" @click="loadStudents()">Coba lagi</button>
            </div>
            <div v-else-if="!filteredStudents.length" class="state-card empty soft">
              <h3>{{ studentQuery.trim() || listFilter ? 'Tidak ada hasil' : 'Belum ada siswa aktif' }}</h3>
              <p v-if="studentQuery.trim()">Tidak ada siswa yang cocok dengan “{{ studentQuery.trim() }}”.</p>
              <p v-else-if="listFilter">Tidak ada siswa pada filter ini. <button type="button" class="chip-link" @click="clearListFilter">Hapus filter</button></p>
            </div>
            <template v-else>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-num">No</th>
                      <th><button type="button" class="th-sort" @click="setSort('name')">Nama <span class="sort-icon" :class="sortClass('name')"></span></button></th>
                      <th><button type="button" class="th-sort" @click="setSort('nis')">NIS <span class="sort-icon" :class="sortClass('nis')"></span></button></th>
                      <th><button type="button" class="th-sort" @click="setSort('nisn')">NISN <span class="sort-icon" :class="sortClass('nisn')"></span></button></th>
                      <th>Kontak</th>
                      <th>Akun</th>
                      <th class="col-gender"><button type="button" class="th-sort" @click="setSort('gender')">L/P <span class="sort-icon" :class="sortClass('gender')"></span></button></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(student, index) in filteredStudents" :key="student.id" class="row-click" @click="openProfile(student)">
                      <td class="col-num">{{ startIndex + index + 1 }}</td>
                      <td>
                        <div class="student-cell">
                          <span class="student-avatar" :class="genderTone(student.gender)">{{ initials(student.name) }}</span>
                          <span class="student-name">{{ student.name }}</span>
                        </div>
                      </td>
                      <td><span class="nis-chip">{{ student.nis || '—' }}</span></td>
                      <td><span class="nis-chip">{{ student.nisn || '—' }}</span></td>
                      <td>
                        <div class="contact-mini">
                          <span v-if="student.guardian_phone || student.phone">{{ student.guardian_phone || student.phone }}</span>
                          <span v-else class="muted">—</span>
                        </div>
                      </td>
                      <td>
                        <span class="account-pill" :class="accountTone(student)">{{ accountLabel(student) }}</span>
                      </td>
                      <td class="col-gender"><span class="gender-pill" :class="genderTone(student.gender)">{{ genderLabel(student.gender) }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-if="!listFilter && pagination.last_page > 1" class="pagination-bar">
                <span class="pagination-info">Menampilkan {{ startIndex + 1 }}–{{ Math.min(startIndex + students.length, pagination.total) }} dari {{ pagination.total }}</span>
                <div class="pagination-buttons">
                  <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
                  <span class="page-num">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                  <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
                </div>
              </div>
            </template>
          </section>
        </template>

        <!-- Panel: Absensi -->
        <section v-else-if="panel === 'absensi'" class="panel-card">
          <div class="section-header">
            <div>
              <h2>Rekap Absensi Kelas</h2>
              <p class="section-hint">Monitoring kehadiran — isi absen lewat Jam Mengajar Hari Ini; rekap lengkap di tautan ini</p>
            </div>
            <div class="period-switch">
              <button type="button" class="period-chip" :class="{ active: attendancePeriod === 'week' }" @click="setAttendancePeriod('week')">7 hari</button>
              <button type="button" class="period-chip" :class="{ active: attendancePeriod === 'month' }" @click="setAttendancePeriod('month')">30 hari</button>
              <router-link v-if="canAccessModule('teaching_journal')" :to="`/attendance/student?class_id=${selectedClassId}&tab=rekap`" class="btn-secondary link-btn-sm">Rekap lengkap →</router-link>
            </div>
          </div>
          <div v-if="attendanceLoading" class="state-card soft"><p>Memuat rekap absensi…</p></div>
          <div v-else-if="attendanceError" class="state-card empty soft"><h3>Gagal memuat</h3><p>{{ attendanceError }}</p></div>
          <template v-else-if="attendanceSummary">
            <div class="overview-stats">
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.hadir || 0 }}</strong><span>Hadir</span></div>
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.izin || 0 }}</strong><span>Izin</span></div>
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.sakit || 0 }}</strong><span>Sakit</span></div>
              <div class="ov-stat warn"><strong>{{ attendanceSummary.totals?.alpha || 0 }}</strong><span>Alpa</span></div>
            </div>
            <div v-if="attendanceSummary.repeat_alpha?.length" class="alert-box">
              <h3>Alpa berulang (≥ {{ attendanceSummary.alpha_threshold }} sesi)</h3>
              <ul>
                <li v-for="s in attendanceSummary.repeat_alpha" :key="s.student_id">
                  <button type="button" class="chip-link" @click="openProfileById(s.student_id, s.name)">{{ s.name }}</button>
                  · {{ s.alpha }} alpa
                </li>
              </ul>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Hadir</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpa</th>
                    <th>Tercatat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in attendanceSummary.rows" :key="r.student_id" class="row-click" @click="openProfileById(r.student_id, r.name)">
                    <td>{{ r.name }}</td>
                    <td>{{ r.counts?.hadir || 0 }}</td>
                    <td>{{ r.counts?.izin || 0 }}</td>
                    <td>{{ r.counts?.sakit || 0 }}</td>
                    <td :class="{ 'cell-warn': (r.counts?.alpha || 0) >= (attendanceSummary.alpha_threshold || 2) }">{{ r.counts?.alpha || 0 }}</td>
                    <td>{{ r.recorded || 0 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </section>

        <!-- Panel: Nilai -->
        <section v-else-if="panel === 'nilai'" class="panel-card">
          <div class="section-header">
            <div>
              <h2>Monitoring Nilai Kelas</h2>
              <p class="section-hint">Mapel kosong & di bawah KKM — input nilai lewat Buku Nilai / Raport</p>
            </div>
            <div class="period-switch">
              <router-link v-if="canAccessModule('grade_book')" :to="`/raport-kelas?class_id=${selectedClassId}`" class="btn-secondary link-btn-sm">Rekap nilai →</router-link>
            </div>
          </div>
          <div v-if="gradesLoading" class="state-card soft"><p>Memuat ringkasan nilai…</p></div>
          <div v-else-if="gradesError" class="state-card empty soft"><h3>Gagal memuat</h3><p>{{ gradesError }}</p></div>
          <template v-else-if="gradesOverview">
            <div class="overview-stats">
              <div class="ov-stat"><strong>{{ gradesOverview.summary?.subject_count || 0 }}</strong><span>Mapel</span></div>
              <div class="ov-stat warn"><strong>{{ gradesOverview.summary?.missing_any || 0 }}</strong><span>Tanpa nilai</span></div>
              <div class="ov-stat warn"><strong>{{ gradesOverview.summary?.below_kkm_any || 0 }}</strong><span>Di bawah KKM</span></div>
            </div>
            <div class="chart-solo">
              <AppChart title="Status nilai siswa" type="doughnut" :chart-data="gradesStatusChart" />
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Rata-rata</th>
                    <th>Kosong</th>
                    <th>Di bawah KKM</th>
                    <th>Detail</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="r in gradesOverview.rows" :key="r.student_id" class="row-click" @click="openProfileById(r.student_id, r.name)">
                    <td>{{ r.name }}</td>
                    <td>{{ r.average != null ? r.average : '—' }}</td>
                    <td :class="{ 'cell-warn': r.missing_count > 0 }">{{ r.missing_count }}</td>
                    <td :class="{ 'cell-warn': r.below_kkm_count > 0 }">{{ r.below_kkm_count }}</td>
                    <td class="detail-cell">
                      <span v-for="m in r.missing.slice(0, 3)" :key="'m'+m.subject_id" class="tag tag-miss">{{ m.subject_name }}</span>
                      <span v-for="b in r.below_kkm.slice(0, 3)" :key="'b'+b.subject_id" class="tag tag-kkm">{{ b.subject_name }} {{ b.nilai_akhir }}</span>
                      <span v-if="(r.missing_count + r.below_kkm_count) > 6" class="muted">…</span>
                      <span v-if="!r.missing_count && !r.below_kkm_count" class="muted">Lengkap</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </section>

        <!-- Panel: Usulan -->
        <section v-else-if="panel === 'usulan'" class="panel-card">
          <div class="section-header">
            <div>
              <h2>Usulan Wali</h2>
              <p class="section-hint">Diajukan ke BK (pelanggaran/prestasi) atau admin (mutasi) untuk disetujui</p>
            </div>
          </div>
          <div class="subtabs">
            <button type="button" class="subtab" :class="{ active: usulanTab === 'violation' }" @click="usulanTab = 'violation'">Pelanggaran</button>
            <button type="button" class="subtab" :class="{ active: usulanTab === 'achievement' }" @click="usulanTab = 'achievement'">Prestasi</button>
            <button type="button" class="subtab" :class="{ active: usulanTab === 'mutation' }" @click="usulanTab = 'mutation'">Mutasi</button>
          </div>

          <div v-if="usulanTab === 'violation'" class="usulan-grid">
            <form class="form-card" @submit.prevent="submitViolation">
              <h3>Ajukan pelanggaran</h3>
              <label>Siswa
                <select v-model="vioForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>Jenis
                <select v-model="vioForm.violation_type_id" required>
                  <option value="">Pilih jenis</option>
                  <option v-for="t in violationTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                </select>
              </label>
              <label>Tanggal <input v-model="vioForm.violation_date" type="date" required></label>
              <label>Keterangan <textarea v-model="vioForm.description" rows="2" placeholder="Opsional"></textarea></label>
              <p v-if="!violationTypes.length" class="form-warn">Jenis pelanggaran belum tersedia. Minta BK/admin menambahkan master jenis.</p>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting || !violationTypes.length">Kirim usulan</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan</h3>
              <div v-if="violationsLoading" class="muted">Memuat…</div>
              <div v-else-if="!violations.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="v in violations" :key="v.id">
                  <div>
                    <strong>{{ v.student?.name }}</strong>
                    <span class="muted"> · {{ v.violation_type?.name }} · {{ v.violation_date }}</span>
                  </div>
                  <span class="status-pill" :class="`st-${v.status}`">{{ v.status }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div v-else-if="usulanTab === 'achievement'" class="usulan-grid">
            <form class="form-card" @submit.prevent="submitAchievement">
              <h3>Ajukan prestasi</h3>
              <label>Siswa
                <select v-model="achForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>Jenis
                <select v-model="achForm.achievement_type_id" required>
                  <option value="">Pilih jenis</option>
                  <option v-for="t in achievementTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                </select>
              </label>
              <label>Tanggal <input v-model="achForm.achievement_date" type="date" required></label>
              <label>Catatan <textarea v-model="achForm.notes" rows="2" placeholder="Opsional"></textarea></label>
              <p v-if="!achievementTypes.length" class="form-warn">Jenis prestasi belum tersedia.</p>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting || !achievementTypes.length">Kirim usulan</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan</h3>
              <div v-if="achievementsLoading" class="muted">Memuat…</div>
              <div v-else-if="!achievements.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="a in achievements" :key="a.id">
                  <div>
                    <strong>{{ a.student?.name }}</strong>
                    <span class="muted"> · {{ a.achievement_type?.name }} · {{ a.achievement_date }}</span>
                  </div>
                  <span class="status-pill" :class="`st-${a.status}`">{{ a.status }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div v-else class="usulan-grid">
            <form class="form-card" @submit.prevent="submitMutation">
              <h3>Ajukan mutasi keluar</h3>
              <label>Siswa
                <select v-model="mutForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>NPSN tujuan <input v-model="mutForm.target_npsn" required placeholder="NPSN sekolah tujuan"></label>
              <label class="check-row">
                <input v-model="mutForm.external" type="checkbox"> Sekolah belum terdaftar di sistem
              </label>
              <label v-if="mutForm.external">Nama sekolah tujuan <input v-model="mutForm.target_school_name" :required="mutForm.external"></label>
              <label>Alasan / catatan <textarea v-model="mutForm.notes" rows="2"></textarea></label>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting">Kirim ke admin</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan mutasi</h3>
              <div v-if="mutationsLoading" class="muted">Memuat…</div>
              <div v-else-if="!mutations.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="m in mutations" :key="m.id">
                  <div>
                    <strong>{{ m.student?.name }}</strong>
                    <span class="muted"> · {{ m.target_institution?.name || m.target_school_name || m.target_npsn }}</span>
                  </div>
                  <span class="status-pill" :class="`st-${m.status}`">{{ m.status }}</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Panel: Jadwal -->
        <section v-else class="panel-card">
          <div class="section-header">
            <div>
              <h2>Jadwal Pelajaran Kelas</h2>
              <p class="section-hint">{{ schedule?.template?.name ? `Template: ${schedule.template.name}` : 'Jadwal kelas yang Anda waliki' }}</p>
            </div>
            <button type="button" class="btn-secondary" :disabled="scheduleLoading || exporting" @click="runExport('schedule')">Unduh PDF</button>
          </div>
          <div v-if="scheduleLoading" class="state-card soft"><p>Memuat jadwal…</p></div>
          <div v-else-if="scheduleError" class="state-card empty soft"><h3>Gagal memuat jadwal</h3><p>{{ scheduleError }}</p></div>
          <div v-else-if="!schedule?.matrix?.length" class="state-card empty soft"><h3>Belum ada jadwal</h3><p>Jadwal kelas belum diisi untuk semester aktif.</p></div>
          <div v-else class="schedule-scroll">
            <table class="schedule-table">
              <thead>
                <tr>
                  <th>Hari</th>
                  <th v-for="p in scheduleMaxPeriods" :key="p">JP {{ p }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="day in schedule.matrix" :key="day.day_of_week">
                  <th>{{ day.day_name }}</th>
                  <td v-for="p in scheduleMaxPeriods" :key="p" :class="{ holiday: day.is_holiday }">
                    <template v-if="day.is_holiday"><span class="muted">Libur</span></template>
                    <template v-else-if="day.slots?.[p]">
                      <div class="slot-subject">{{ day.slots[p].subject?.name || day.slots[p].subject_name || '—' }}</div>
                      <div class="slot-teacher">{{ day.slots[p].employee?.name || day.slots[p].teacher_name || '' }}</div>
                    </template>
                    <template v-else><span class="muted">—</span></template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
        </div>
        </div>
      </template>
    </div>

    <!-- Modal profil -->
    <div v-if="profileOpen" class="modal-backdrop" @click.self="closeProfile">
      <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="profile-title">
        <div class="modal-head">
          <div>
            <h2 id="profile-title">{{ profile?.name || 'Profil Siswa' }}</h2>
            <p class="muted">{{ profile?.nis ? `NIS ${profile.nis}` : '' }}{{ profile?.nisn ? ` · NISN ${profile.nisn}` : '' }}</p>
          </div>
          <button type="button" class="btn-ghost" @click="closeProfile">Tutup</button>
        </div>
        <div v-if="profileLoading" class="muted pad">Memuat profil…</div>
        <div v-else-if="profile" class="modal-body">
          <div class="profile-grid">
            <div class="info-block">
              <h4>Identitas</h4>
              <dl>
                <div><dt>L/P</dt><dd>{{ genderLabel(profile.gender) }}</dd></div>
                <div><dt>Alamat</dt><dd>{{ profile.address || '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ profile.email || '—' }}</dd></div>
              </dl>
            </div>
            <div class="info-block highlight account-block">
              <h4>Akun login portal siswa</h4>
              <p class="metric-hint">Login NIK · sandi awal tanggal lahir (DDMMYYYY)</p>
              <form class="login-fields-form" @submit.prevent="saveLoginFields">
                <label>
                  <span>NIK (16 digit)</span>
                  <input v-model="loginForm.nik" type="text" maxlength="16" inputmode="numeric" required pattern="\d{16}" />
                </label>
                <label>
                  <span>Tanggal lahir</span>
                  <input v-model="loginForm.birth_date" type="date" required />
                </label>
                <label>
                  <span>Tempat lahir</span>
                  <input v-model="loginForm.birth_place" type="text" maxlength="100" />
                </label>
                <div class="login-actions">
                  <button type="submit" class="btn-primary" :disabled="accountActionLoading">
                    {{ accountActionLoading ? 'Menyimpan…' : 'Simpan & sinkron akun' }}
                  </button>
                  <button
                    v-if="!profile.has_user_account"
                    type="button"
                    class="btn-secondary"
                    :disabled="accountActionLoading"
                    @click="ensureProfileAccount"
                  >
                    Buat akun
                  </button>
                  <button
                    v-else
                    type="button"
                    class="btn-secondary"
                    :disabled="accountActionLoading"
                    @click="resetProfilePassword"
                  >
                    Reset sandi
                  </button>
                </div>
              </form>
              <p class="metric-hint">
                Status:
                <strong>{{ profile.has_user_account ? 'Sudah punya akun' : 'Belum punya akun' }}</strong>
                <template v-if="profile.user_account?.must_change_password"> · wajib ganti sandi</template>
              </p>
            </div>
            <div class="info-block highlight">
              <h4>Kontak</h4>
              <div class="contact-line">
                <span>HP siswa</span>
                <strong>{{ profile.phone || '—' }}</strong>
                <template v-if="profile.phone">
                  <a class="chip-link" :href="`tel:${profile.phone}`">Telepon</a>
                  <button type="button" class="chip-link" @click="copyText(profile.phone)">Salin</button>
                </template>
              </div>
              <div class="contact-line">
                <span>Wali · {{ profile.guardian_name || '—' }}</span>
                <strong>{{ profile.guardian_phone || '—' }}</strong>
                <template v-if="profile.guardian_phone">
                  <a class="chip-link" :href="`tel:${profile.guardian_phone}`">Telepon</a>
                  <button type="button" class="chip-link" @click="copyText(profile.guardian_phone)">Salin</button>
                </template>
              </div>
            </div>
            <div class="info-block">
              <h4>Orang tua</h4>
              <dl>
                <div><dt>Ayah</dt><dd>{{ profile.father_name || '—' }}{{ profile.father_occupation ? ` · ${profile.father_occupation}` : '' }}</dd></div>
                <div><dt>Ibu</dt><dd>{{ profile.mother_name || '—' }}{{ profile.mother_occupation ? ` · ${profile.mother_occupation}` : '' }}</dd></div>
              </dl>
            </div>
          </div>

          <div v-if="snapshot" class="snapshot-grid">
            <div class="info-block">
              <h4>Absensi (7 hari)</h4>
              <div class="metric-row">
                <span><strong>{{ snapshot.attendance?.week?.hadir || 0 }}</strong> hadir</span>
                <span><strong>{{ snapshot.attendance?.week?.izin || 0 }}</strong> izin</span>
                <span><strong>{{ snapshot.attendance?.week?.sakit || 0 }}</strong> sakit</span>
                <span><strong>{{ snapshot.attendance?.week?.alpha || 0 }}</strong> alpa</span>
              </div>
              <p class="metric-hint">30 hari: {{ snapshot.attendance?.month?.alpha || 0 }} alpa · {{ snapshot.attendance?.month?.recorded || 0 }} sesi tercatat</p>
            </div>
            <div class="info-block">
              <h4>Poin BK</h4>
              <p class="snap-score">Skor {{ snapshot.bk?.total_points ?? 0 }}</p>
              <p class="metric-hint">Langgar {{ snapshot.bk?.violation_points ?? 0 }} · Prestasi {{ snapshot.bk?.achievement_points ?? 0 }}</p>
            </div>
            <div class="info-block">
              <h4>Nilai semester</h4>
              <p class="snap-score">Rata-rata {{ snapshot.grades?.average != null ? snapshot.grades.average : '—' }}</p>
              <p class="metric-hint">{{ snapshot.grades?.missing_count || 0 }} kosong · {{ snapshot.grades?.below_kkm_count || 0 }} di bawah KKM</p>
              <div v-if="snapshot.grades?.subjects?.length" class="tag-wrap">
                <span v-for="s in snapshot.grades.subjects.slice(0, 6)" :key="s.subject_id + s.status" class="tag" :class="s.status === 'missing' ? 'tag-miss' : 'tag-kkm'">
                  {{ s.subject_name }}<template v-if="s.nilai_akhir != null"> {{ s.nilai_akhir }}</template>
                </span>
              </div>
            </div>
          </div>

          <div v-if="snapshot?.recent_violations?.length || snapshot?.recent_achievements?.length || snapshot?.mutations?.length" class="snapshot-lists">
            <div v-if="snapshot.recent_violations?.length" class="info-block">
              <h4>Pelanggaran terkini</h4>
              <ul class="mini-list">
                <li v-for="v in snapshot.recent_violations" :key="'v'+v.id">{{ v.type || '—' }} · {{ v.date }} <span class="status-pill" :class="`st-${v.status}`">{{ v.status }}</span></li>
              </ul>
            </div>
            <div v-if="snapshot.recent_achievements?.length" class="info-block">
              <h4>Prestasi terkini</h4>
              <ul class="mini-list">
                <li v-for="a in snapshot.recent_achievements" :key="'a'+a.id">{{ a.type || '—' }} · {{ a.date }} <span class="status-pill" :class="`st-${a.status}`">{{ a.status }}</span></li>
              </ul>
            </div>
            <div v-if="snapshot.mutations?.length" class="info-block">
              <h4>Riwayat mutasi</h4>
              <ul class="mini-list">
                <li v-for="m in snapshot.mutations" :key="'m'+m.id">{{ m.target || '—' }} <span class="status-pill" :class="`st-${m.status}`">{{ m.status }}</span></li>
              </ul>
            </div>
          </div>

          <div class="notes-block">
            <h4>Catatan wali kelas</h4>
            <form class="note-form" @submit.prevent="saveNote">
              <textarea v-model="noteBody" rows="3" placeholder="Tulis catatan perkembangan / tindak lanjut…" required maxlength="5000"></textarea>
              <button type="submit" class="btn-primary" :disabled="noteSaving">{{ editingNoteId ? 'Simpan perubahan' : 'Tambah catatan' }}</button>
              <button v-if="editingNoteId" type="button" class="btn-ghost" @click="cancelEditNote">Batal</button>
            </form>
            <div v-if="notesLoading" class="muted">Memuat catatan…</div>
            <ul v-else class="notes-list">
              <li v-for="n in notes" :key="n.id">
                <div class="note-meta">
                  <strong>{{ n.author?.name || 'Wali' }}</strong>
                  <span class="muted">{{ formatDateTime(n.created_at) }}</span>
                </div>
                <p>{{ n.body }}</p>
                <div v-if="n.can_edit" class="note-actions">
                  <TableAction kind="edit" @click="startEditNote(n)" />
                  <TableAction kind="delete" @click="removeNote(n)" />
                </div>
              </li>
              <li v-if="!notes.length" class="muted">Belum ada catatan.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </Layout>
  <AccountCredentialsModal
    :show="!!accountCredentials"
    :title="accountCredentials?.title"
    :name="accountCredentials?.name"
    :login-label="accountCredentials?.loginLabel || 'NIK'"
    :login-value="accountCredentials?.loginValue"
    :password="accountCredentials?.password"
    :hint="accountCredentials?.hint"
    :items="accountCredentials?.items || []"
    @close="accountCredentials = null"
  />
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import AppChart from '@/components/AppChart.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import { useAuthStore } from '@/stores/auth'
import { doughnutFromCounts, doughnutFromEntries } from '@/composables/useChart'
import { teacherApi } from '@/api/teacher'
import { waliKelasApi } from '@/api/waliKelas'
import { useToast } from '@/composables/useToast'
import { studentLoginCredentials, mapStudentCreatedAccounts } from '@/utils/accountCredentials'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const accountCredentials = ref(null)

const selectedClassId = ref('')
const panel = ref('siswa')
const students = ref([])
const studentsLoading = ref(false)
const studentsError = ref('')
const studentQuery = ref('')
const sortBy = ref('name')
const sortDir = ref('asc')
const summary = ref({ total: 0, male: 0, female: 0 })
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const dashboard = ref(null)
const dashLoading = ref(false)
const listFilter = ref(null) // 'bk_high' | 'grades_incomplete' | 'missing_account' | 'incomplete_account' | null
const accountFilterMode = ref('') // '', 'missing', 'incomplete' — server-side via account_status
const bulkAccountLoading = ref(false)
const accountActionLoading = ref(false)
const loginForm = ref({ nik: '', birth_date: '', birth_place: '' })

const profileOpen = ref(false)
const profileLoading = ref(false)
const profile = ref(null)
const snapshot = ref(null)
const notes = ref([])
const notesLoading = ref(false)
const noteBody = ref('')
const noteSaving = ref(false)
const editingNoteId = ref(null)

const attendancePeriod = ref('week')
const attendanceSummary = ref(null)
const attendanceLoading = ref(false)
const attendanceError = ref('')

const gradesOverview = ref(null)
const gradesLoading = ref(false)
const gradesError = ref('')

const usulanTab = ref('violation')
const usulanSubmitting = ref(false)
const violationTypes = ref([])
const achievementTypes = ref([])
const violations = ref([])
const achievements = ref([])
const mutations = ref([])
const violationsLoading = ref(false)
const achievementsLoading = ref(false)
const mutationsLoading = ref(false)
const studentOptions = ref([])

const vioForm = ref({ student_id: '', violation_type_id: '', violation_date: new Date().toISOString().slice(0, 10), description: '' })
const achForm = ref({ student_id: '', achievement_type_id: '', achievement_date: new Date().toISOString().slice(0, 10), notes: '' })
const mutForm = ref({ student_id: '', target_npsn: '', target_school_name: '', external: false, notes: '' })

const schedule = ref(null)
const scheduleLoading = ref(false)
const scheduleError = ref('')

const exportOpen = ref(false)
const exporting = ref(false)
const exportWrapRef = ref(null)

let searchTimer = null

const icons = {
  attendance: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2"/></svg>`,
  violation: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2"/></svg>`,
  points: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2"/></svg>`,
  raport: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>`,
  rekap: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 3v18h18" stroke="currentColor" stroke-width="2"/><path d="M7 14l4-4 3 3 5-6" stroke="currentColor" stroke-width="2"/></svg>`,
  usulan: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="2"/></svg>`,
  jadwal: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" stroke="currentColor" stroke-width="2"/></svg>`,
}

const homeroomClasses = computed(() => authStore.user?.homeroom_classes || [])
const selectedClass = computed(() => homeroomClasses.value.find((c) => String(c.id) === String(selectedClassId.value)) || null)
const startIndex = computed(() => Math.max(0, (pagination.value.current_page - 1) * pagination.value.per_page))
const canAccessModule = (key) => (authStore.user?.permissions || []).includes(key)
const canAccessBk = computed(() => canAccessModule('bk_report') || canAccessModule('violation') || canAccessModule('counseling'))
const att = computed(() => dashboard.value?.attendance_today || { hadir: 0, izin: 0, sakit: 0, alpha: 0, students_recorded: 0 })
const attendanceChart = computed(() => doughnutFromCounts(att.value))
const genderChart = computed(() => doughnutFromEntries([
  { label: 'Laki-laki', value: dashboard.value?.students?.male ?? summary.value.male, color: '#0284c7' },
  { label: 'Perempuan', value: dashboard.value?.students?.female ?? summary.value.female, color: '#db2777' },
]))
const gradesStatusChart = computed(() => {
  const s = gradesOverview.value?.summary
  if (!s) return null
  const complete = Math.max(0, (s.students || 0) - (s.missing_any || 0))
  return doughnutFromEntries([
    { label: 'Lengkap', value: complete, color: '#059669' },
    { label: 'Tanpa nilai', value: s.missing_any || 0, color: '#f59e0b' },
    { label: 'Di bawah KKM', value: s.below_kkm_any || 0, color: '#ef4444' },
  ])
})
const pending = computed(() => dashboard.value?.pending || { violations: 0, achievements: 0, mutations: 0 })
const accounts = computed(() => dashboard.value?.accounts || {
  with_account: 0,
  missing_account: 0,
  incomplete_data: 0,
  missing_students: [],
  incomplete_students: [],
})
const scheduleMaxPeriods = computed(() => Math.max(1, Number(schedule.value?.template?.max_periods || 0)))
const listFilterLabel = computed(() => {
  if (listFilter.value === 'bk_high') return 'Skor BK tinggi'
  if (listFilter.value === 'grades_incomplete') return 'Nilai belum lengkap'
  if (listFilter.value === 'missing_account') return 'Belum punya akun'
  if (listFilter.value === 'incomplete_account') return 'Data login kurang'
  return ''
})
const filteredStudents = computed(() => {
  if (!listFilter.value) return students.value
  if (listFilter.value === 'bk_high') {
    const ids = new Set(
      (dashboard.value?.bk_high_scores?.students || dashboard.value?.bk_high_scores?.top || [])
        .map((s) => Number(s.student_id))
    )
    return students.value.filter((s) => ids.has(Number(s.id)))
  }
  if (listFilter.value === 'grades_incomplete') {
    const ids = new Set((dashboard.value?.grades_incomplete_students || []).map((s) => Number(s.student_id)))
    return students.value.filter((s) => ids.has(Number(s.id)))
  }
  // missing_account / incomplete_account sudah difilter server-side
  return students.value
})

const visibleActions = computed(() => {
  const items = []
  const q = selectedClassId.value ? `?class_id=${selectedClassId.value}` : ''
  items.push({ title: 'Monitoring Absen', desc: 'Rekap 7/30 hari & alpa berulang', to: `/teacher/wali${q}${q ? '&' : '?'}panel=absensi`, tone: 'teal', icon: icons.attendance })
  if (canAccessBk.value) {
    items.push({ title: 'Rekap Pelanggaran', desc: 'Laporan BK siswa kelas wali', to: `/laporan-bk${q}`, tone: 'amber', icon: icons.violation })
    items.push({ title: 'Poin & Prestasi', desc: 'Skor pelanggaran dan prestasi siswa', to: `/laporan-bk${q}${q ? '&' : '?'}tab=detail`, tone: 'violet', icon: icons.points })
  }
  items.push({ title: 'Monitoring Nilai', desc: 'Mapel kosong & di bawah KKM', to: `/teacher/wali${q}${q ? '&' : '?'}panel=nilai`, tone: 'sky', icon: icons.rekap })
  if (canAccessModule('grade_book')) {
    items.push({ title: 'Raport Siswa', desc: 'Lihat raport per siswa kelas wali', to: `/raport${q}`, tone: 'emerald', icon: icons.raport })
  }
  return items
})

function genderLabel(gender) {
  if (gender === 'L' || gender === 'male' || gender === 'laki-laki') return 'L'
  if (gender === 'P' || gender === 'female' || gender === 'perempuan') return 'P'
  return gender || '—'
}
function genderTone(gender) {
  const g = genderLabel(gender)
  if (g === 'L') return 'tone-l'
  if (g === 'P') return 'tone-p'
  return 'tone-n'
}
function initials(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}
function sortClass(column) {
  if (sortBy.value !== column) return 'is-idle'
  return sortDir.value === 'asc' ? 'is-asc' : 'is-desc'
}
function formatDateTime(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return iso
  }
}

function syncFromRoute() {
  const fromQuery = route.query.class_id ? String(route.query.class_id) : ''
  const allowed = new Set(homeroomClasses.value.map((c) => String(c.id)))
  if (fromQuery && allowed.has(fromQuery)) selectedClassId.value = fromQuery
  else if (homeroomClasses.value.length) selectedClassId.value = String(homeroomClasses.value[0].id)
  else selectedClassId.value = ''

  const p = String(route.query.panel || 'siswa')
  panel.value = ['siswa', 'absensi', 'nilai', 'usulan', 'jadwal'].includes(p) ? p : 'siswa'
}

function replaceQuery(extra = {}) {
  const query = { ...route.query, ...extra }
  if (!query.class_id && selectedClassId.value) query.class_id = selectedClassId.value
  if (!query.panel) query.panel = panel.value
  router.replace({ path: '/teacher/wali', query })
}

function selectClass(id) {
  selectedClassId.value = String(id)
  studentQuery.value = ''
  listFilter.value = null
  accountFilterMode.value = ''
  replaceQuery({ class_id: String(id) })
  refreshClassData()
}

function setPanel(next) {
  panel.value = next
  replaceQuery({ panel: next })
  if (next === 'usulan') loadUsulanData()
  if (next === 'jadwal') loadSchedule()
  if (next === 'absensi') loadAttendanceSummary()
  if (next === 'nilai') loadGradesOverview()
}

function focusBkHigh() {
  listFilter.value = 'bk_high'
  accountFilterMode.value = ''
  setPanel('siswa')
  // Load all active students so filter can match beyond current page
  loadStudentsForFilter()
}

function focusGradesIncomplete() {
  listFilter.value = 'grades_incomplete'
  accountFilterMode.value = ''
  setPanel('nilai')
  loadGradesOverview()
}

function focusMissingAccounts() {
  setPanel('siswa')
  if ((accounts.value.missing_account || 0) > 0) {
    listFilter.value = 'missing_account'
    accountFilterMode.value = 'missing'
  } else if ((accounts.value.incomplete_data || 0) > 0) {
    listFilter.value = 'incomplete_account'
    accountFilterMode.value = 'incomplete'
  } else {
    listFilter.value = null
    accountFilterMode.value = ''
    toast.success('Info', 'Semua siswa aktif di kelas ini sudah punya akun login')
  }
  loadStudents(1)
}

function clearListFilter() {
  listFilter.value = null
  accountFilterMode.value = ''
  loadStudents(1)
}

function accountLabel(student) {
  if (student?.has_user_account) return 'Ada akun'
  const nik = String(student?.nik || '').trim()
  const hasNik = /^\d{16}$/.test(nik)
  const hasBirth = !!student?.birth_date
  if (hasNik && hasBirth) return 'Belum akun'
  return 'Kurang data'
}

function accountTone(student) {
  if (student?.has_user_account) return 'ok'
  const nik = String(student?.nik || '').trim()
  if (/^\d{16}$/.test(nik) && student?.birth_date) return 'warn'
  return 'bad'
}

async function bulkEnsureClassAccounts() {
  if (!selectedClassId.value || bulkAccountLoading.value) return
  const count = accounts.value.missing_account || 0
  if (count <= 0) {
    toast.success('Info', 'Tidak ada siswa yang siap dibuatkan akun')
    return
  }
  if (!window.confirm(`Buat akun login untuk ${count} siswa di kelas ini?\nSandi awal = tanggal lahir (DDMMYYYY).`)) return
  bulkAccountLoading.value = true
  try {
    const res = await waliKelasApi.ensureAccountsBulk(selectedClassId.value, { only_missing: true, limit: 500 })
    const items = mapStudentCreatedAccounts(res.data?.data?.created_accounts || [])
    if (items.length) {
      accountCredentials.value = {
        title: 'Akun login siswa dibuat',
        loginLabel: 'NIK',
        hint: 'Siswa login dengan NIK. Sandi awal = tanggal lahir (DDMMYYYY). Kartu ini hanya hilang jika ditutup.',
        items,
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun massal selesai')
    }
    await Promise.all([loadDashboard(), loadStudents(1)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    bulkAccountLoading.value = false
  }
}

async function saveLoginFields() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.updateLoginFields(selectedClassId.value, profile.value.id, {
      nik: loginForm.value.nik,
      birth_date: loginForm.value.birth_date,
      birth_place: loginForm.value.birth_place || null,
    })
    profile.value = res.data?.data || profile.value
    syncLoginFormFromProfile()
    toast.success('Berhasil', res.data?.message || 'Data login disimpan')
    await Promise.all([loadDashboard(), loadStudents(pagination.value.current_page)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

async function ensureProfileAccount() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.ensureStudentAccount(selectedClassId.value, profile.value.id)
    profile.value = res.data?.data || profile.value
    syncLoginFormFromProfile()
    const creds = studentLoginCredentials(profile.value, res.data?.login_hint)
    if (creds) {
      creds.title = res.data?.user_created ? 'Akun login siswa dibuat' : 'Akun login siswa'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun dibuat')
    }
    await Promise.all([loadDashboard(), loadStudents(pagination.value.current_page)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

async function resetProfilePassword() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  if (!window.confirm('Reset sandi ke tanggal lahir (DDMMYYYY)? Siswa wajib ganti sandi saat login berikutnya.')) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.resetStudentPassword(selectedClassId.value, profile.value.id)
    profile.value = res.data?.data || profile.value
    const creds = studentLoginCredentials(profile.value, res.data?.login_hint)
    if (creds) {
      creds.title = 'Sandi siswa berhasil direset'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Sandi direset')
    }
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

function syncLoginFormFromProfile() {
  loginForm.value = {
    nik: profile.value?.nik || '',
    birth_date: profile.value?.birth_date || '',
    birth_place: profile.value?.birth_place || '',
  }
}

async function loadStudentsForFilter() {
  if (!selectedClassId.value) return
  try {
    const response = await teacherApi.getHomeroomClassStudents(selectedClassId.value, {
      per_page: 100,
      status: 'Aktif',
      sort_by: 'name',
      sort_dir: 'asc',
    })
    const payload = response.data || {}
    students.value = payload.data || []
    const meta = payload.meta || {}
    pagination.value = {
      current_page: 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 100,
      total: meta.total ?? students.value.length,
    }
  } catch {
    /* keep existing list */
  }
}

function setSort(column) {
  if (sortBy.value === column) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else {
    sortBy.value = column
    sortDir.value = 'asc'
  }
  loadStudents(1)
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return
  loadStudents(page)
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadStudents(1), 300)
}

async function loadStudents(page = pagination.value.current_page) {
  if (!selectedClassId.value) {
    students.value = []
    return
  }
  studentsLoading.value = true
  studentsError.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      status: 'Aktif',
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const q = studentQuery.value.trim()
    if (q) params.search = q
    if (accountFilterMode.value === 'missing') params.account_status = 'missing'
    if (accountFilterMode.value === 'incomplete') params.account_status = 'incomplete'
    const response = await teacherApi.getHomeroomClassStudents(selectedClassId.value, params)
    const payload = response.data || {}
    students.value = payload.data || []
    const meta = payload.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? page,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? students.value.length,
    }
    const s = payload.summary || {}
    summary.value = { total: s.total ?? pagination.value.total, male: s.male ?? 0, female: s.female ?? 0 }
  } catch (error) {
    students.value = []
    studentsError.value = error.formattedMessage || error.response?.data?.message || error.message || 'Gagal memuat siswa'
  } finally {
    studentsLoading.value = false
  }
}

async function loadDashboard() {
  if (!selectedClassId.value) {
    dashboard.value = null
    return
  }
  dashLoading.value = true
  try {
    const res = await waliKelasApi.getDashboard(selectedClassId.value)
    dashboard.value = res.data?.data || null
  } catch {
    dashboard.value = null
  } finally {
    dashLoading.value = false
  }
}

async function loadStudentOptions() {
  if (!selectedClassId.value) return
  try {
    const res = await teacherApi.getHomeroomClassStudents(selectedClassId.value, { per_page: 100, status: 'Aktif', sort_by: 'name' })
    studentOptions.value = res.data?.data || []
  } catch {
    studentOptions.value = []
  }
}

async function openProfile(student) {
  profileOpen.value = true
  profileLoading.value = true
  profile.value = student
  snapshot.value = null
  notes.value = []
  noteBody.value = ''
  editingNoteId.value = null
  try {
    const [stuRes, notesRes] = await Promise.all([
      waliKelasApi.getStudent(selectedClassId.value, student.id),
      waliKelasApi.getNotes(selectedClassId.value, student.id),
    ])
    const data = stuRes.data?.data || stuRes.data || student
    profile.value = data
    snapshot.value = data.snapshot || null
    notes.value = notesRes.data?.data || []
    syncLoginFormFromProfile()
  } catch (e) {
    toast.error('Gagal memuat profil', e.formattedMessage || e.message)
  } finally {
    profileLoading.value = false
    notesLoading.value = false
  }
}

function openProfileById(studentId, name) {
  openProfile({ id: studentId, name: name || 'Siswa' })
}

function closeProfile() {
  profileOpen.value = false
  profile.value = null
  snapshot.value = null
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Disalin', text)
  } catch {
    toast.error('Gagal menyalin')
  }
}

async function saveNote() {
  if (!profile.value || !noteBody.value.trim()) return
  noteSaving.value = true
  try {
    if (editingNoteId.value) {
      await waliKelasApi.updateNote(selectedClassId.value, profile.value.id, editingNoteId.value, { body: noteBody.value.trim() })
      toast.success('Catatan diperbarui')
    } else {
      await waliKelasApi.createNote(selectedClassId.value, profile.value.id, { body: noteBody.value.trim() })
      toast.success('Catatan ditambahkan')
    }
    noteBody.value = ''
    editingNoteId.value = null
    const notesRes = await waliKelasApi.getNotes(selectedClassId.value, profile.value.id)
    notes.value = notesRes.data?.data || []
  } catch (e) {
    toast.error('Gagal menyimpan catatan', e.formattedMessage || e.message)
  } finally {
    noteSaving.value = false
  }
}

function startEditNote(n) {
  editingNoteId.value = n.id
  noteBody.value = n.body
}

function cancelEditNote() {
  editingNoteId.value = null
  noteBody.value = ''
}

async function removeNote(n) {
  if (!confirm('Hapus catatan ini?')) return
  try {
    await waliKelasApi.deleteNote(selectedClassId.value, profile.value.id, n.id)
    notes.value = notes.value.filter((x) => x.id !== n.id)
    toast.success('Catatan dihapus')
  } catch (e) {
    toast.error('Gagal menghapus', e.formattedMessage || e.message)
  }
}

async function loadUsulanData() {
  await Promise.all([loadStudentOptions(), loadTypes(), loadViolations(), loadAchievements(), loadMutations()])
}

async function loadTypes() {
  try {
    const [v, a] = await Promise.all([
      waliKelasApi.getViolationTypes({ class_id: selectedClassId.value }),
      waliKelasApi.getAchievementTypes({ class_id: selectedClassId.value }),
    ])
    violationTypes.value = v.data?.data || []
    achievementTypes.value = a.data?.data || []
  } catch {
    violationTypes.value = []
    achievementTypes.value = []
  }
}

async function loadViolations() {
  if (!selectedClassId.value) return
  violationsLoading.value = true
  try {
    const res = await waliKelasApi.getViolations({ class_id: selectedClassId.value, per_page: 50 })
    violations.value = res.data?.data || []
  } catch {
    violations.value = []
  } finally {
    violationsLoading.value = false
  }
}

async function loadAchievements() {
  if (!selectedClassId.value) return
  achievementsLoading.value = true
  try {
    const res = await waliKelasApi.getAchievements({ class_id: selectedClassId.value, per_page: 50 })
    achievements.value = res.data?.data || []
  } catch {
    achievements.value = []
  } finally {
    achievementsLoading.value = false
  }
}

async function loadMutations() {
  if (!selectedClassId.value) return
  mutationsLoading.value = true
  try {
    const res = await waliKelasApi.getMutations({ class_id: selectedClassId.value, per_page: 50 })
    mutations.value = res.data?.data || []
  } catch {
    mutations.value = []
  } finally {
    mutationsLoading.value = false
  }
}

async function submitViolation() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeViolation({
      class_id: Number(selectedClassId.value),
      student_id: Number(vioForm.value.student_id),
      violation_type_id: Number(vioForm.value.violation_type_id),
      violation_date: vioForm.value.violation_date,
      description: vioForm.value.description || null,
    })
    toast.success('Usulan pelanggaran terkirim')
    vioForm.value.description = ''
    await Promise.all([loadViolations(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function submitAchievement() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeAchievement({
      class_id: Number(selectedClassId.value),
      student_id: Number(achForm.value.student_id),
      achievement_type_id: Number(achForm.value.achievement_type_id),
      achievement_date: achForm.value.achievement_date,
      notes: achForm.value.notes || null,
    })
    toast.success('Usulan prestasi terkirim')
    achForm.value.notes = ''
    await Promise.all([loadAchievements(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function submitMutation() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeMutation({
      class_id: Number(selectedClassId.value),
      student_id: Number(mutForm.value.student_id),
      target_npsn: mutForm.value.target_npsn,
      target_school_name: mutForm.value.target_school_name || null,
      external: !!mutForm.value.external,
      notes: mutForm.value.notes || null,
    })
    toast.success('Usulan mutasi terkirim ke admin')
    mutForm.value = { student_id: '', target_npsn: '', target_school_name: '', external: false, notes: '' }
    await Promise.all([loadMutations(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function loadSchedule() {
  if (!selectedClassId.value) return
  scheduleLoading.value = true
  scheduleError.value = ''
  try {
    const res = await waliKelasApi.getSchedule(selectedClassId.value)
    schedule.value = res.data?.data || null
  } catch (e) {
    schedule.value = null
    scheduleError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    scheduleLoading.value = false
  }
}

function setAttendancePeriod(period) {
  attendancePeriod.value = period
  loadAttendanceSummary()
}

async function loadAttendanceSummary() {
  if (!selectedClassId.value) return
  attendanceLoading.value = true
  attendanceError.value = ''
  try {
    const res = await waliKelasApi.getAttendanceSummary(selectedClassId.value, { period: attendancePeriod.value })
    attendanceSummary.value = res.data?.data || null
  } catch (e) {
    attendanceSummary.value = null
    attendanceError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    attendanceLoading.value = false
  }
}

async function loadGradesOverview() {
  if (!selectedClassId.value) return
  gradesLoading.value = true
  gradesError.value = ''
  try {
    const res = await waliKelasApi.getGradesOverview(selectedClassId.value)
    gradesOverview.value = res.data?.data || null
  } catch (e) {
    gradesOverview.value = null
    gradesError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    gradesLoading.value = false
  }
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

function filenameFromDisposition(headers, fallback) {
  const cd = headers?.['content-disposition'] || headers?.['Content-Disposition'] || ''
  const m = /filename="?([^"]+)"?/i.exec(cd)
  return m?.[1] || fallback
}

async function runExport(kind) {
  if (!selectedClassId.value) return
  exportOpen.value = false
  exporting.value = true
  try {
    let res
    let fallback = 'export.bin'
    if (kind === 'roster') {
      res = await waliKelasApi.exportRoster(selectedClassId.value)
      fallback = `Daftar_Siswa_${selectedClass.value?.name || 'kelas'}.pdf`
    } else if (kind === 'contacts-pdf') {
      res = await waliKelasApi.exportContacts(selectedClassId.value, { format: 'pdf' })
      fallback = `Kontak_Ortu_${selectedClass.value?.name || 'kelas'}.pdf`
    } else if (kind === 'contacts-csv') {
      res = await waliKelasApi.exportContacts(selectedClassId.value, { format: 'csv' })
      fallback = `Kontak_Ortu_${selectedClass.value?.name || 'kelas'}.csv`
    } else if (kind === 'attendance') {
      res = await waliKelasApi.exportAttendance(selectedClassId.value, { format: 'pdf' })
      fallback = `Rekap_Absensi_${selectedClass.value?.name || 'kelas'}.pdf`
    } else if (kind === 'schedule') {
      res = await waliKelasApi.exportSchedulePdf(selectedClassId.value)
      fallback = `Jadwal_${selectedClass.value?.name || 'kelas'}.pdf`
    }
    downloadBlob(res.data, filenameFromDisposition(res.headers, fallback))
    toast.success('Unduhan siap')
  } catch (e) {
    toast.error('Gagal export', e.formattedMessage || e.message)
  } finally {
    exporting.value = false
  }
}

function refreshClassData() {
  loadStudents(1)
  loadDashboard()
  if (panel.value === 'usulan') loadUsulanData()
  if (panel.value === 'jadwal') loadSchedule()
  if (panel.value === 'absensi') loadAttendanceSummary()
  if (panel.value === 'nilai') loadGradesOverview()
}

function onDocClick(e) {
  if (exportOpen.value && exportWrapRef.value && !exportWrapRef.value.contains(e.target)) {
    exportOpen.value = false
  }
}

watch(() => route.query, () => {
  const prevPanel = panel.value
  const prevClass = selectedClassId.value
  syncFromRoute()
  if (selectedClassId.value !== prevClass) {
    refreshClassData()
  } else if (panel.value !== prevPanel) {
    if (panel.value === 'usulan') loadUsulanData()
    if (panel.value === 'jadwal') loadSchedule()
    if (panel.value === 'absensi') loadAttendanceSummary()
    if (panel.value === 'nilai') loadGradesOverview()
  }
}, { deep: true })

watch(homeroomClasses, () => {
  syncFromRoute()
  if (selectedClassId.value) refreshClassData()
}, { deep: true })

onMounted(() => {
  syncFromRoute()
  if (selectedClassId.value) refreshClassData()
  document.addEventListener('click', onDocClick)
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
  document.removeEventListener('click', onDocClick)
})
</script>

<style scoped>
.page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  background:
    radial-gradient(ellipse 80% 50% at 0% -10%, rgba(16, 185, 129, 0.14), transparent 55%),
    linear-gradient(180deg, #ecfdf5 0%, #f8fafc 24%, #f1f5f9 100%);
}
.page-header { margin-bottom: 1.25rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: flex-start; }
.page-header-main { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.back-chip {
  display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; border-radius: 999px;
  background: rgba(255,255,255,.9); border: 1px solid #e2e8f0; color: #475569; text-decoration: none; font-size: .85rem;
}
.back-chip:hover { border-color: #34d399; color: #065f46; }
.page-header h1 { margin: 0 0 .25rem; font-size: 1.55rem; color: #0f172a; letter-spacing: -.02em; }
.page-subtitle { margin: 0; color: #64748b; font-size: .9rem; }
.header-tools { display: flex; gap: .5rem; }
.export-wrap { position: relative; }
.export-menu {
  position: absolute; right: 0; top: calc(100% + 6px); z-index: 20; min-width: 220px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 12px 28px rgba(15,23,42,.12); padding: .35rem;
}
.export-menu button {
  display: block; width: 100%; text-align: left; border: none; background: transparent; padding: .55rem .7rem;
  border-radius: 8px; cursor: pointer; color: #0f172a; font-size: .88rem;
}
.export-menu button:hover { background: #ecfdf5; color: #065f46; }

.hero-card {
  display: flex; align-items: stretch; justify-content: space-between; gap: 1.25rem; padding: 1.25rem 1.35rem;
  margin-bottom: 1rem; border-radius: 18px; background: linear-gradient(135deg, #065f46 0%, #059669 48%, #10b981 100%);
  color: #fff; box-shadow: 0 12px 28px rgba(5, 150, 105, 0.22);
}
.hero-badge { display: inline-flex; padding: .2rem .6rem; border-radius: 999px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.22); font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.hero-title { margin: .55rem 0 .35rem; font-size: 1.65rem; letter-spacing: -.02em; }
.hero-meta { display: flex; flex-wrap: wrap; gap: .35rem; color: rgba(236,253,245,.9); font-size: .88rem; }
.hero-meta .dot { opacity: .7; }
.hero-stats { display: flex; gap: .65rem; }
.hero-stat { min-width: 78px; padding: .7rem .85rem; border-radius: 14px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18); text-align: center; }
.hero-stat-value { display: block; font-size: 1.35rem; font-weight: 700; }
.hero-stat-label { display: block; margin-top: .2rem; font-size: .72rem; opacity: .88; }

.class-switcher { display: flex; flex-direction: column; gap: .45rem; margin-bottom: 1rem; }
.class-switcher-label { font-size: .78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.class-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
.class-chip { border: 1px solid #d1fae5; background: #fff; color: #065f46; padding: .45rem .9rem; border-radius: 999px; font-size: .88rem; font-weight: 600; cursor: pointer; }
.class-chip.active { background: #059669; border-color: #059669; color: #fff; }

.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.charts-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
.chart-solo { max-width: 360px; margin: 0 0 1rem; }
.metric-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .9rem 1rem; display: flex; flex-direction: column; gap: .35rem; }
.metric-clickable { cursor: pointer; text-align: left; font: inherit; width: 100%; transition: box-shadow .15s, transform .15s; }
.metric-clickable:hover { box-shadow: 0 8px 20px rgba(15,23,42,.08); transform: translateY(-1px); }
.metric-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
.metric-value { font-size: 1.6rem; font-weight: 750; color: #0f172a; line-height: 1; }
.metric-hint { font-size: .78rem; color: #94a3b8; }
.metric-row { display: flex; flex-wrap: wrap; gap: .55rem; font-size: .82rem; color: #334155; }
.metric-list { margin: .2rem 0 0; padding-left: 1rem; font-size: .8rem; color: #475569; }
.metric-link { align-self: flex-start; border: none; background: transparent; color: #059669; font-weight: 650; cursor: pointer; padding: 0; font-size: .82rem; }
.tone-teal { border-top: 3px solid #14b8a6; }
.tone-amber { border-top: 3px solid #f59e0b; }
.tone-sky { border-top: 3px solid #0ea5e9; }
.tone-violet { border-top: 3px solid #8b5cf6; }
.tone-rose { border-top: 3px solid #f43f5e; }
.account-pill {
  display: inline-flex; padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap;
}
.account-pill.ok { background: #ecfdf5; color: #047857; }
.account-pill.warn { background: #fffbeb; color: #b45309; }
.account-pill.bad { background: #fef2f2; color: #b91c1c; }
.login-fields-form { display: flex; flex-direction: column; gap: .55rem; margin-top: .55rem; }
.login-fields-form label { display: flex; flex-direction: column; gap: .25rem; font-size: .78rem; font-weight: 600; color: #475569; }
.login-fields-form input {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: .45rem .6rem; font: inherit; font-weight: 400; background: #fff; color: #0f172a;
}
.login-actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }

.tab-shell {
  display: grid;
  grid-template-columns: 188px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
}
.section-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: #f8fafc;
  border-right: 1px solid #eef2f7;
}
.sec-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: none;
  background: transparent;
  border-radius: 10px;
  cursor: pointer;
  color: #64748b;
  text-align: left;
}
.sec-btn:hover:not(.active) { background: #fff; color: #0f172a; }
.sec-btn.active { background: #fff; color: #065f46; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0; }
.sec-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; flex-shrink: 0;
}
.sec-btn.active .sec-icon { background: #d1fae5; color: #047857; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; }
.tab-main { min-width: 0; padding: 14px; }

@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
}

.panel-card, .students-section {
  background: transparent; border: none; border-radius: 0; padding: 0;
  box-shadow: none; margin-bottom: 0;
}
.section-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; flex-wrap: wrap; }
.section-header h2 { margin: 0; font-size: 1.08rem; color: #0f172a; }
.section-hint { margin: .2rem 0 0; color: #94a3b8; font-size: .82rem; }
.section-meta { color: #64748b; font-size: .85rem; }
.actions-section { margin-bottom: 1.15rem; }
.actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: .75rem; }
.action-card {
  display: flex; align-items: center; gap: .85rem; padding: 1rem 1.05rem; background: #fff; border: 1px solid #e2e8f0;
  border-radius: 14px; text-decoration: none; color: inherit; cursor: pointer; text-align: left; width: 100%;
}
.action-card:hover { box-shadow: 0 8px 20px rgba(15,23,42,.06); transform: translateY(-1px); }
.action-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.action-card h4 { margin: 0 0 .2rem; font-size: .98rem; }
.action-card p { margin: 0; color: #64748b; font-size: .82rem; }
.action-teal .action-icon { background: #ecfeff; color: #0e7490; }
.action-amber .action-icon { background: #fffbeb; color: #d97706; }
.action-violet .action-icon { background: #f5f3ff; color: #7c3aed; }
.action-emerald .action-icon { background: #ecfdf5; color: #059669; }
.action-sky .action-icon { background: #f0f9ff; color: #0284c7; }
.action-slate .action-icon { background: #f1f5f9; color: #475569; }
.action-indigo .action-icon { background: #eef2ff; color: #4f46e5; }

.students-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.search-wrap { position: relative; }
.search-icon { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.search-input {
  width: min(260px, 70vw); padding: .5rem .75rem .5rem 2.1rem; border: 1px solid #e2e8f0; border-radius: 10px;
  background: #f8fafc; font-size: .88rem;
}
.search-input:focus { outline: none; border-color: #34d399; background: #fff; box-shadow: 0 0 0 3px rgba(52,211,153,.18); }
.table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; }
.data-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.data-table th, .data-table td { padding: .75rem .9rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 650; background: #f0fdf4; color: #065f46; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
.row-click { cursor: pointer; }
.row-click:hover { background: #f0fdf4; }
.th-sort { display: inline-flex; align-items: center; gap: 6px; border: none; background: transparent; color: inherit; font: inherit; font-weight: 650; cursor: pointer; text-transform: uppercase; letter-spacing: .04em; }
.sort-icon { display: inline-block; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; opacity: .35; border-bottom: 5px solid currentColor; }
.sort-icon.is-asc { opacity: 1; }
.sort-icon.is-desc { opacity: 1; border-bottom: 0; border-top: 5px solid currentColor; }
.col-num { width: 3rem; color: #94a3b8; }
.col-gender { width: 4.5rem; }
.student-cell { display: flex; align-items: center; gap: .7rem; }
.student-avatar { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; }
.student-name { font-weight: 600; color: #0f172a; }
.nis-chip { display: inline-flex; padding: .15rem .5rem; border-radius: 6px; background: #f1f5f9; color: #475569; font-size: .82rem; }
.gender-pill { display: inline-flex; min-width: 1.7rem; justify-content: center; padding: .15rem .45rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
.tone-l { background: #eff6ff; color: #1d4ed8; }
.tone-p { background: #fdf2f8; color: #be185d; }
.tone-n { background: #f1f5f9; color: #64748b; }
.contact-mini { font-size: .82rem; color: #475569; }
.muted { color: #94a3b8; }
.pagination-bar { display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .9rem; padding-top: .85rem; border-top: 1px solid #e2e8f0; }
.pagination-info, .page-num { font-size: .85rem; color: #64748b; }
.pagination-buttons { display: flex; gap: .5rem; align-items: center; }
.btn-page, .btn-ghost, .btn-secondary {
  border: 1px solid #e2e8f0; background: #fff; color: #334155; padding: .45rem .85rem; border-radius: 8px; cursor: pointer; font-size: .85rem;
  display: inline-flex; align-items: center; gap: .35rem;
}
.btn-page:hover:not(:disabled), .btn-ghost:hover, .btn-secondary:hover { border-color: #34d399; color: #065f46; background: #ecfdf5; }
.btn-page:disabled, .btn-secondary:disabled { opacity: .45; cursor: not-allowed; }
.btn-primary, .link-btn {
  display: inline-flex; align-items: center; justify-content: center; border: none; background: #059669; color: #fff;
  padding: .55rem 1rem; border-radius: 10px; text-decoration: none; cursor: pointer; font-weight: 600;
}
.btn-primary:hover { background: #047857; }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; }
.state-card { text-align: center; padding: 2rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; }
.state-card.soft { background: #f8fafc; border-style: dashed; }
.state-icon { width: 56px; height: 56px; margin: 0 auto .85rem; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: #ecfdf5; color: #059669; }
.state-card.empty h3 { margin: 0 0 .45rem; }
.state-card.empty p { margin: 0 0 1rem; color: #64748b; }

.subtabs { display: flex; gap: .4rem; margin-bottom: 1rem; flex-wrap: wrap; }
.subtab { border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; padding: .4rem .85rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .85rem; }
.subtab.active { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
.usulan-grid { display: grid; grid-template-columns: minmax(280px, 1fr) minmax(280px, 1.2fr); gap: 1rem; }
.form-card, .list-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; background: #f8fafc; }
.form-card h3, .list-card h3 { margin: 0 0 .75rem; font-size: .98rem; }
.form-card label { display: flex; flex-direction: column; gap: .3rem; margin-bottom: .7rem; font-size: .82rem; font-weight: 600; color: #475569; }
.form-card input, .form-card select, .form-card textarea {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: .5rem .65rem; font: inherit; font-weight: 400; background: #fff; color: #0f172a;
}
.check-row { flex-direction: row !important; align-items: center; gap: .5rem !important; font-weight: 500 !important; }
.form-warn { color: #b45309; font-size: .8rem; margin: 0 0 .6rem; }
.proposal-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .55rem; }
.proposal-list li { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .75rem; }
.status-pill { font-size: .72rem; font-weight: 700; text-transform: uppercase; padding: .15rem .45rem; border-radius: 999px; background: #f1f5f9; color: #475569; white-space: nowrap; }
.st-pending { background: #fffbeb; color: #b45309; }
.st-dicatat, .st-approved { background: #ecfdf5; color: #047857; }
.st-ditolak, .st-rejected { background: #fef2f2; color: #b91c1c; }

.schedule-scroll { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; }
.schedule-table { width: 100%; border-collapse: collapse; min-width: 720px; font-size: .82rem; }
.schedule-table th, .schedule-table td { border: 1px solid #e2e8f0; padding: .55rem .5rem; vertical-align: top; }
.schedule-table thead th { background: #f0fdf4; color: #065f46; text-align: center; }
.schedule-table tbody th { background: #f8fafc; text-align: left; white-space: nowrap; }
.schedule-table td.holiday { background: #fef2f2; }
.slot-subject { font-weight: 650; color: #0f172a; }
.slot-teacher { color: #64748b; font-size: .75rem; margin-top: .15rem; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 80; display: flex; align-items: flex-end; justify-content: center; padding: 1rem; }
.modal-sheet {
  width: min(920px, 100%); max-height: min(92vh, 900px); overflow: auto; background: #fff; border-radius: 18px 18px 12px 12px;
  box-shadow: 0 24px 60px rgba(15,23,42,.28);
}
.modal-head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; padding: 1.1rem 1.2rem; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; background: #fff; z-index: 1; }
.modal-head h2 { margin: 0; font-size: 1.2rem; }
.modal-body { padding: 1.1rem 1.2rem 1.4rem; }
.pad { padding: 1.2rem; }
.profile-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1.1rem; }
.info-block { border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem; background: #f8fafc; }
.info-block.highlight { background: #ecfdf5; border-color: #a7f3d0; }
.info-block h4 { margin: 0 0 .55rem; font-size: .88rem; color: #065f46; }
.info-block dl { margin: 0; display: flex; flex-direction: column; gap: .4rem; }
.info-block dt { font-size: .72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .03em; }
.info-block dd { margin: 0; color: #0f172a; font-size: .9rem; }
.contact-line { display: flex; flex-wrap: wrap; gap: .4rem .55rem; align-items: center; margin-bottom: .55rem; font-size: .85rem; }
.contact-line span { color: #64748b; width: 100%; }
.chip-link { border: 1px solid #d1fae5; background: #fff; color: #065f46; border-radius: 999px; padding: .2rem .55rem; font-size: .75rem; font-weight: 650; text-decoration: none; cursor: pointer; }
.chip-link.danger { border-color: #fecaca; color: #b91c1c; }
.notes-block h4 { margin: 0 0 .65rem; }
.note-form { display: grid; grid-template-columns: 1fr auto auto; gap: .5rem; margin-bottom: .85rem; }
.note-form textarea { border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem; font: inherit; }
.notes-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .65rem; }
.notes-list li { border: 1px solid #e2e8f0; border-radius: 10px; padding: .7rem .8rem; background: #fff; }
.note-meta { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; font-size: .82rem; }
.notes-list p { margin: 0; white-space: pre-wrap; color: #334155; }
.note-actions { margin-top: .45rem; display: flex; gap: .4rem; }

.filter-chip {
  border: 1px solid #fcd34d; background: #fffbeb; color: #92400e; border-radius: 999px;
  padding: .35rem .75rem; font-size: .8rem; font-weight: 650; cursor: pointer;
}
.period-switch { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
.period-chip {
  border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: .4rem .75rem;
  border-radius: 999px; font-weight: 650; cursor: pointer; font-size: .82rem;
}
.period-chip.active { background: #059669; border-color: #059669; color: #fff; }
.link-btn-sm {
  display: inline-flex; align-items: center; padding: .4rem .75rem; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; font-size: .82rem;
}
.link-btn-sm:hover { border-color: #34d399; color: #065f46; background: #ecfdf5; }
.overview-stats { display: flex; flex-wrap: wrap; gap: .65rem; margin-bottom: 1rem; }
.ov-stat {
  min-width: 88px; padding: .65rem .85rem; border-radius: 12px; background: #f8fafc;
  border: 1px solid #e2e8f0; text-align: center;
}
.ov-stat strong { display: block; font-size: 1.25rem; color: #0f172a; }
.ov-stat span { font-size: .75rem; color: #64748b; }
.ov-stat.warn { background: #fffbeb; border-color: #fcd34d; }
.ov-stat.warn strong { color: #b45309; }
.alert-box {
  margin-bottom: 1rem; padding: .85rem 1rem; border-radius: 12px;
  background: #fef2f2; border: 1px solid #fecaca;
}
.alert-box h3 { margin: 0 0 .45rem; font-size: .9rem; color: #991b1b; }
.alert-box ul { margin: 0; padding-left: 1.1rem; color: #7f1d1d; font-size: .88rem; }
.cell-warn { color: #b45309; font-weight: 700; }
.detail-cell { display: flex; flex-wrap: wrap; gap: .3rem; max-width: 320px; }
.tag {
  display: inline-flex; padding: .12rem .4rem; border-radius: 6px; font-size: .72rem; font-weight: 650;
}
.tag-miss { background: #f1f5f9; color: #475569; }
.tag-kkm { background: #fff7ed; color: #c2410c; }
.tag-wrap { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
.snapshot-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1rem; }
.snapshot-lists { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1rem; }
.snap-score { margin: 0 0 .25rem; font-size: 1.15rem; font-weight: 750; color: #0f172a; }
.mini-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; font-size: .82rem; color: #334155; }
.mini-list li { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }

@media (max-width: 980px) {
  .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .charts-grid { grid-template-columns: 1fr; }
  .usulan-grid, .profile-grid, .snapshot-grid, .snapshot-lists { grid-template-columns: 1fr; }
  .note-form { grid-template-columns: 1fr; }
}
@media (max-width: 720px) {
  .page { padding: 1rem; }
  .hero-card { flex-direction: column; }
  .hero-stats { width: 100%; }
  .hero-stat { flex: 1; }
  .metrics-grid { grid-template-columns: 1fr; }
  .search-input { width: 100%; }
  .modal-backdrop { padding: 0; }
  .modal-sheet { border-radius: 16px 16px 0 0; max-height: 94vh; }
}
</style>
