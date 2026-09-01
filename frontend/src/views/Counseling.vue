<template>    <div class="counseling-page">
      <div class="toolbar">
        <div class="main-tabs">
          <button :class="['main-tab', { active: activeTab === 'list' }]" @click="activeTab = 'list'; loadSessions()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 6H21M8 12H21M8 18H21M3 6H3.01M3 12H3.01M3 18H3.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Sesi Konseling</span>
          </button>
          <button :class="['main-tab', { active: activeTab === 'types' }]" @click="activeTab = 'types'; loadTypes()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Pengaturan</span>
          </button>
        </div>
        <div class="header-actions">
          <button v-if="activeTab === 'list'" @click="exportToCsv" :disabled="exporting" class="btn-secondary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
          </button>
          <button v-if="activeTab === 'list'" @click="openAddModal" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Sesi Konseling</span>
          </button>
          <button v-if="activeTab === 'types'" @click="openAddTypeModal" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Jenis Konseling</span>
          </button>
        </div>
      </div>

      <!-- Tab: Daftar Sesi Konseling -->
      <template v-if="activeTab === 'list'">
        <div class="stats-grid">
          <button
            type="button"
            class="stat-card"
            :class="{ active: activeStatFilter === 'month' }"
            title="Tampilkan sesi bulan ini di daftar"
            :aria-pressed="activeStatFilter === 'month'"
            @click="applyStatFilter('month')"
          >
            <span class="stat-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <div class="stat-body">
              <span class="stat-label">{{ monthStatLabel }}</span>
              <span class="stat-value">{{ statNumber(statsData?.total_this_month) }}</span>
              <span class="stat-hint">{{ monthStatHint }}</span>
            </div>
          </button>
          <button
            type="button"
            class="stat-card stat-upcoming"
            :class="{ active: activeStatFilter === 'upcoming' }"
            title="Tampilkan jadwal ke depan di daftar"
            :aria-pressed="activeStatFilter === 'upcoming'"
            @click="applyStatFilter('upcoming')"
          >
            <span class="stat-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <div class="stat-body">
              <span class="stat-label">Jadwal ke depan</span>
              <span class="stat-value">{{ upcomingStatCount }}</span>
              <span class="stat-hint">{{ upcomingStatHint }}</span>
            </div>
          </button>
          <button
            type="button"
            class="stat-card stat-open"
            :class="{ active: activeStatFilter === 'open' }"
            title="Tampilkan sesi yang masih terbuka"
            :aria-pressed="activeStatFilter === 'open'"
            @click="applyStatFilter('open')"
          >
            <span class="stat-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <div class="stat-body">
              <span class="stat-label">Belum selesai</span>
              <span class="stat-value">{{ statNumber(statsData?.open_count) }}</span>
              <span class="stat-hint">{{ openStatHint }}</span>
            </div>
          </button>
        </div>

        <div v-if="upcomingSessions.length" class="upcoming-block">
          <h4 class="upcoming-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Jadwal mendatang
          </h4>
          <div class="upcoming-list">
            <button
              v-for="u in upcomingSessions"
              :key="u.id"
              type="button"
              class="upcoming-item"
              @click="openEditModal(u)"
            >
              <span class="picker-avatar sm" :style="avatarStyle(u.student)">{{ studentInitials(u.student) }}</span>
              <span class="upcoming-main">
                <strong>{{ u.student?.name }}</strong>
                <span>{{ u.counseling_type?.name || 'Konseling' }} · {{ u.counselor?.name || '—' }}</span>
              </span>
              <span class="upcoming-date">{{ formatDate(u.session_date) }}</span>
            </button>
          </div>
        </div>

        <div class="filter-bar">
          <div class="search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M20 20L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Cari nama, NIS, atau NISN..."
              @input="debounceLoadSessions"
            />
          </div>
          <select v-model="filters.status" class="filter-select" @change="onManualFilterChange">
            <option value="">Semua status</option>
            <option value="jadwal">Jadwal</option>
            <option value="berlangsung">Berlangsung</option>
            <option value="selesai">Selesai</option>
            <option value="dibatalkan">Dibatalkan</option>
          </select>
          <select v-model="filters.counseling_type_id" class="filter-select" @change="onManualFilterChange">
            <option value="">Semua jenis</option>
            <option v-for="t in counselingTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
          <select v-model="filters.class_id" class="filter-select" @change="onManualFilterChange">
            <option value="">Semua kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <label class="filter-field">
            <span>Dari</span>
            <input v-model="filters.date_from" type="date" class="filter-select" @change="onManualFilterChange" />
          </label>
          <label class="filter-field">
            <span>Sampai</span>
            <input v-model="filters.date_to" type="date" class="filter-select" @change="onManualFilterChange" />
          </label>
          <button type="button" class="btn-ghost" :class="{ active: periodAdvanced }" @click="periodAdvanced = !periodAdvanced">
            Periode
          </button>
          <template v-if="periodAdvanced">
            <select v-model="filters.academic_year_id" class="filter-select" @change="onManualFilterChange">
              <option value="">Semua tahun ajaran</option>
              <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
            </select>
            <select v-model="filters.semester_id" class="filter-select" @change="onManualFilterChange">
              <option value="">Semua semester</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </template>
          <button v-if="filters.student_id" type="button" class="filter-chip" @click="clearStudentFilter">Filter siswa ×</button>
          <button v-if="hasActiveFilters" type="button" class="btn-secondary btn-compact" @click="resetFilters">Reset</button>
        </div>

        <div v-if="loading" class="table-panel">
          <LoadingSkeleton type="table" :rows="8" :columns="8" :cell-widths="['48px', '110px', '220px', '140px', '120px', '90px', '1fr', '110px']" />
        </div>

        <div v-else-if="sessions.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada sesi konseling</h3>
          <p class="empty-desc">Tambahkan sesi baru, atau ubah filter untuk melihat data.</p>
          <button type="button" class="btn-primary" @click="openAddModal">Tambah Sesi Konseling</button>
        </div>

        <div v-else class="table-panel">
          <div class="table-panel-head">
            <h3>Daftar sesi</h3>
            <span>{{ pagination.total }} sesi</span>
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th class="col-date">Tanggal</th>
                  <th>Siswa</th>
                  <th>Konselor</th>
                  <th>Jenis</th>
                  <th class="col-status">Status</th>
                  <th>Ringkasan</th>
                  <th class="col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(s, index) in sessions" :key="s.id">
                  <td class="col-no">{{ rowNumber(index) }}</td>
                  <td class="col-date">
                    <span class="date-primary">{{ formatDate(s.session_date) }}</span>
                    <span class="date-sub">{{ formatWeekday(s.session_date) }}</span>
                  </td>
                  <td>
                    <div class="student-cell">
                      <span class="picker-avatar sm" :style="avatarStyle(s.student)">{{ studentInitials(s.student) }}</span>
                      <span class="picker-student-meta">
                        <strong>{{ s.student?.name || '—' }}</strong>
                        <span>{{ studentIdLabel(s.student) }}<template v-if="sessionClassLabel(s)"> · {{ sessionClassLabel(s) }}</template></span>
                      </span>
                    </div>
                  </td>
                  <td class="counselor-cell">{{ s.counselor?.name || '—' }}</td>
                  <td>
                    <span class="type-pill">{{ s.counseling_type?.name || 'Tanpa jenis' }}</span>
                  </td>
                  <td class="col-status">
                    <span :class="['status-badge', 'status-' + s.status]">{{ getStatusLabel(s.status) }}</span>
                  </td>
                  <td class="summary-cell" :title="s.summary || ''">{{ truncate(s.summary, 72) }}</td>
                  <td class="col-actions">
                    <div class="action-buttons">
                      <TableAction kind="view" title="Riwayat" @click="openHistoryModal(s.student)" />
                      <TableAction kind="edit" @click="openEditModal(s)" />
                      <TableAction kind="delete" @click="confirmDelete(s)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <PaginationBar
            embedded
            :page="pagination.current_page"
            :last-page="pagination.last_page"
            :per-page="pagination.per_page"
            :total="pagination.total"
            item-label="sesi"
            @page-change="goToPage"
            @per-page-change="changePerPage"
          />
        </div>

        <details class="chart-details">
          <summary>Grafik sesi {{ statsData?.year || statsYear }}</summary>
          <div class="dashboard-charts">
            <div class="chart-box">
              <div class="chart-header">
                <h4>Per bulan</h4>
                <select v-model="statsYear" class="chart-year-select" @change="loadStats">
                  <option v-for="y in statsYears" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div v-if="sessionsByMonthChartData" class="chart-wrap">
                <Bar :data="sessionsByMonthChartData" :options="chartOptionsBar" />
              </div>
              <p v-else class="chart-empty">Belum ada data sesi di tahun ini.</p>
            </div>
            <div class="chart-box">
              <div class="chart-header">
                <h4>Per jenis konseling</h4>
              </div>
              <div v-if="sessionsByTypeChartData" class="chart-wrap chart-wrap-pie">
                <Doughnut :data="sessionsByTypeChartData" :options="chartOptionsDoughnut" />
              </div>
              <p v-else class="chart-empty">Belum ada sesi yang dikategorikan.</p>
            </div>
          </div>
        </details>
      </template>

      <!-- Tab: Pengaturan (jenis konseling) -->
      <template v-if="activeTab === 'types'">
        <p class="settings-hint">Kelola master jenis konseling. Jarang diubah saat pencatatan sesi harian.</p>
        <div v-if="typesLoading" class="loading-state"><div class="loading-spinner"></div><p>Memuat jenis konseling...</p></div>
        <div v-else-if="counselingTypes.length === 0" class="empty-state">
          <h3 class="empty-title">Belum ada jenis konseling</h3>
          <p class="empty-desc">Tambahkan jenis konseling (mis. Akademik, Pribadi, Karir) untuk mengkategorikan sesi.</p>
          <button @click="openAddTypeModal" class="btn-primary btn-empty-cta">Tambah Jenis Konseling</button>
        </div>
        <div v-else class="types-grid">
          <div v-for="t in counselingTypes" :key="t.id" class="type-card">
            <div class="type-header">
              <span class="type-name">{{ t.name }}</span>
              <span v-if="t.code" class="type-code">{{ t.code }}</span>
            </div>
            <div class="type-body" v-if="t.description">
              <p class="type-desc">{{ t.description }}</p>
            </div>
            <div class="type-actions">
              <TableAction kind="edit" @click="openEditTypeModal(t)" />
              <TableAction kind="delete" @click="confirmDeleteType(t)" />
            </div>
          </div>
        </div>
      </template>

      <!-- Modal: Tambah/Edit Sesi Konseling -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSession ? 'Edit Sesi Konseling' : 'Tambah Sesi Konseling' }}</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitSession" class="modal-body">
            <div v-if="editingSession" class="form-group student-picker-locked">
              <span class="field-label">Siswa</span>
              <div class="picker-student-card is-static">
                <span class="picker-avatar" :style="avatarStyle(editingSession.student)">{{ studentInitials(editingSession.student) }}</span>
                <div class="picker-student-meta">
                  <strong>{{ editingSession.student?.name || '—' }}</strong>
                  <span>{{ studentIdLabel(editingSession.student) }}<template v-if="studentClassLabel(editingSession.student)"> · {{ studentClassLabel(editingSession.student) }}</template></span>
                </div>
              </div>
            </div>
            <div v-else class="form-group student-picker">
              <div class="picker-heading">
                <span class="field-label">Siswa *</span>
                <span v-if="selectedPickerStudent" class="picker-selected-hint">1 dipilih</span>
              </div>

              <div v-if="selectedPickerStudent" class="picker-student-card is-selected">
                <span class="picker-avatar" :style="avatarStyle(selectedPickerStudent)">{{ studentInitials(selectedPickerStudent) }}</span>
                <div class="picker-student-meta">
                  <strong>{{ selectedPickerStudent.name }}</strong>
                  <span>{{ studentIdLabel(selectedPickerStudent) }}<template v-if="studentClassLabel(selectedPickerStudent)"> · {{ studentClassLabel(selectedPickerStudent) }}</template></span>
                </div>
                <button v-if="!pickerPanelOpen" type="button" class="picker-clear" @click="pickerPanelOpen = true">Ganti</button>
              </div>

              <div v-show="!selectedPickerStudent || pickerPanelOpen" class="picker-panel">
                <div class="picker-toolbar">
                  <label class="picker-field">
                    <span>Kelas</span>
                    <select v-model="pickerClassId" @change="onPickerClassChange">
                      <option value="">Semua kelas</option>
                      <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                  </label>
                  <label class="picker-field picker-field-search">
                    <span>Cari siswa</span>
                    <div class="picker-search-wrap">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M20 20L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                      <input
                        v-model="pickerStudentSearch"
                        type="text"
                        placeholder="Nama, NIS, NISN, atau NIK"
                        autocomplete="off"
                        @input="debouncePickerStudentSearch"
                      />
                    </div>
                  </label>
                </div>

                <div class="picker-list" role="listbox" aria-label="Daftar siswa" :aria-busy="loadingPickerStudents">
                  <div v-if="loadingPickerStudents" class="picker-state">
                    <span class="picker-spinner"></span>
                    Memuat siswa...
                  </div>
                  <div v-else-if="!pickerStudents.length && !pickerClassId && !pickerStudentSearch.trim()" class="picker-state">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>Pilih kelas atau ketik nama/NIS untuk menampilkan siswa.</p>
                  </div>
                  <div v-else-if="pickerStudentError" class="picker-state is-error">{{ pickerStudentError }}</div>
                  <div v-else-if="!pickerStudents.length" class="picker-state">Tidak ada siswa cocok.</div>
                  <template v-else>
                    <div class="picker-list-meta">{{ pickerStudents.length }} siswa — pilih satu</div>
                    <button
                      v-for="s in pickerStudents"
                      :key="s.id"
                      type="button"
                      role="option"
                      class="picker-option"
                      :aria-selected="isPickerStudentSelected(s)"
                      :class="{ active: isPickerStudentSelected(s) }"
                      @click="selectPickerStudent(s)"
                    >
                      <span class="picker-avatar" :style="avatarStyle(s)">{{ studentInitials(s) }}</span>
                      <span class="picker-student-meta">
                        <strong>{{ s.name }}</strong>
                        <span>{{ studentIdLabel(s) }}<template v-if="studentClassLabel(s)"> · {{ studentClassLabel(s) }}</template></span>
                      </span>
                      <svg v-if="isPickerStudentSelected(s)" class="picker-check" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" fill="#059669"/>
                        <path d="M8 12.5l2.5 2.5L16 9.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </template>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Konselor *</label>
              <input
                v-if="counselors.length > 8"
                v-model="counselorSearch"
                type="text"
                class="counselor-search"
                placeholder="Cari nama guru..."
              />
              <select v-model="form.counselor_id" required class="form-select">
                <option value="">Pilih konselor (guru)</option>
                <option v-for="c in filteredCounselors" :key="c.id" :value="c.id">{{ counselorLabel(c) }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jenis Konseling</label>
              <select v-model="form.counseling_type_id" class="form-select">
                <option value="">— Pilih (opsional) —</option>
                <option v-for="t in counselingTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal Sesi *</label>
              <input v-model="form.session_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-select">
                <option value="jadwal">Jadwal</option>
                <option value="berlangsung">Berlangsung</option>
                <option value="selesai">Selesai</option>
                <option value="dibatalkan">Dibatalkan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ringkasan</label>
              <textarea v-model="form.summary" rows="3" placeholder="Ringkasan sesi konseling (opsional)"></textarea>
            </div>
            <div class="form-group">
              <label>Catatan Tindak Lanjut</label>
              <textarea v-model="form.follow_up_notes" rows="2" placeholder="Catatan follow up (opsional)"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingSession ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Jenis Konseling -->
      <div v-if="showTypeModal" class="modal-overlay" @click="showTypeModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingType ? 'Edit Jenis Konseling' : 'Tambah Jenis Konseling' }}</h3>
            <button @click="showTypeModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitType" class="modal-body">
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="typeForm.name" type="text" required placeholder="Contoh: Akademik" />
            </div>
            <div class="form-group">
              <label>Kode (opsional)</label>
              <input v-model="typeForm.code" type="text" placeholder="Contoh: AK" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="typeForm.description" rows="2" placeholder="Deskripsi jenis konseling"></textarea>
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

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Sesi Konseling"
        :message="deleteSessionMessage"
        confirmText="Hapus"
        @confirm="doDeleteSession"
        @cancel="deleteTarget = null"
      />
      <ConfirmDialog
        v-if="deleteTypeTarget"
        :show="!!deleteTypeTarget"
        title="Hapus Jenis Konseling"
        :message="deleteTypeMessage"
        confirmText="Hapus"
        @confirm="doDeleteType"
        @cancel="deleteTypeTarget = null"
      />

      <!-- Modal: Riwayat konseling per siswa -->
      <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
        <div class="modal-content history-modal" @click.stop>
          <div class="modal-header">
            <h3>Riwayat konseling – {{ historyStudent?.name || '' }}</h3>
            <button @click="showHistoryModal = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div v-if="historyLoading" class="loading-state small">Memuat riwayat...</div>
            <div v-else-if="!historySessions.length" class="empty-state small">
              <p>Belum ada sesi konseling untuk siswa ini.</p>
              <button v-if="historyStudent" @click="openAddModalForStudent(historyStudent); showHistoryModal = false" class="btn-primary btn-sm">Tambah Sesi</button>
            </div>
            <div v-else class="history-table-wrap">
              <table class="data-table compact">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Konselor</th>
                    <th>Ringkasan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(h, index) in historySessions" :key="h.id">
                    <td class="col-no">{{ index + 1 }}</td>
                    <td>{{ formatDate(h.session_date) }}</td>
                    <td>{{ h.counseling_type?.name || '-' }}</td>
                    <td><span :class="['status-badge', 'status-' + h.status]">{{ getStatusLabel(h.status) }}</span></td>
                    <td>{{ h.counselor?.name }}</td>
                    <td class="summary-cell">{{ truncate(h.summary, 40) }}</td>
                  </tr>
                </tbody>
              </table>
              <button v-if="historyStudent" @click="openAddModalForStudent(historyStudent); showHistoryModal = false" class="btn-primary btn-sm mt-1">Tambah Sesi Konseling</button>
            </div>
          </div>
        </div>
      </div>
    </div></template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import { counselingApi, counselingTypeApi } from '@/api/counseling'
import { institutionApi } from '@/api/institution'
import { useAuthStore } from '@/stores/auth'
import { useReferenceDataStore } from '@/stores/referenceData'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const route = useRoute()
const authStore = useAuthStore()

const activeTab = ref('list')
const loading = ref(true)
const typesLoading = ref(false)
const sessions = ref([])
const counselingTypes = ref([])
const counselors = ref([])
const counselorSearch = ref('')
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const statsData = ref(null)
const statsError = ref(false)
const statsYear = ref(new Date().getFullYear())
const statsYears = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2]
})
const upcomingSessions = ref([])
const activeStatFilter = ref(null)

const showHistoryModal = ref(false)
const historyStudent = ref(null)
const historySessions = ref([])
const historyLoading = ref(false)

const filters = ref({
  search: '',
  status: '',
  student_id: '',
  counseling_type_id: '',
  class_id: '',
  academic_year_id: '',
  semester_id: '',
  date_from: '',
  date_to: '',
})
const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const institution = ref(null)
const classes = ref([])
const semesters = ref([])
const exporting = ref(false)
const periodAdvanced = ref(false)

const showFormModal = ref(false)
const editingSession = ref(null)
const form = ref({
  student_id: '',
  counselor_id: '',
  counseling_type_id: '',
  session_date: '',
  status: 'jadwal',
  summary: '',
  follow_up_notes: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const pickerClassId = ref('')
const pickerStudentSearch = ref('')
const pickerStudents = ref([])
const loadingPickerStudents = ref(false)
const pickerStudentError = ref('')
const pickerPanelOpen = ref(true)
const selectedPickerStudent = ref(null)
let pickerStudentTimer = null

const showTypeModal = ref(false)
const editingType = ref(null)
const typeForm = ref({
  name: '',
  code: '',
  description: '',
})
const typeFormSubmitting = ref(false)
const typeFormError = ref('')

const deleteTarget = ref(null)
const deleteTypeTarget = ref(null)

const statusLabels = {
  jadwal: 'Jadwal',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}
function getStatusLabel(status) {
  return statusLabels[status] || status
}

const AVATAR_COLORS = [
  { bg: '#d1fae5', fg: '#047857' },
  { bg: '#e0f2fe', fg: '#0369a1' },
  { bg: '#fef3c7', fg: '#b45309' },
  { bg: '#ede9fe', fg: '#6d28d9' },
  { bg: '#fce7f3', fg: '#be185d' },
  { bg: '#ffedd5', fg: '#c2410c' },
]

function studentInitials(s) {
  const parts = String(s?.name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function avatarStyle(s) {
  const name = s?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  const color = AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
  return { background: color.bg, color: color.fg }
}

function studentIdLabel(s) {
  return s?.nis || s?.nisn || s?.nik || '—'
}

function studentClassLabel(s) {
  return s?.class_name || s?.class?.name || ''
}

function sessionClassLabel(s) {
  return s?.school_class?.name || s?.student?.class?.name || ''
}

function isPickerStudentSelected(s) {
  return String(form.value.student_id) === String(s?.id)
}

function selectPickerStudent(s) {
  form.value.student_id = String(s.id)
  selectedPickerStudent.value = s
  pickerPanelOpen.value = false
}

function counselorLabel(c) {
  const role = c.role === 'staff' ? 'Staf' : 'Guru'
  return `${c.name} (${role})`
}

const filteredCounselors = computed(() => {
  const q = counselorSearch.value.trim().toLowerCase()
  if (!q) return counselors.value
  return counselors.value.filter((c) => {
    const name = (c.name || '').toLowerCase()
    const email = (c.email || '').toLowerCase()
    return name.includes(q) || email.includes(q)
  })
})

const deleteSessionMessage = computed(() => {
  const name = deleteTarget.value?.student?.name || ''
  return 'Yakin menghapus sesi konseling untuk ' + name + '?'
})
const deleteTypeMessage = computed(() => {
  const name = deleteTypeTarget.value?.name || ''
  return 'Yakin menghapus jenis konseling ' + name + '? Jenis yang sudah dipakai tidak dapat dihapus.'
})

let debounceTimer = null
function debounceLoadSessions() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    activeStatFilter.value = null
    loadSessions()
  }, 300)
}

function onManualFilterChange() {
  activeStatFilter.value = null
  loadSessions()
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatWeekday(val) {
  if (!val) return ''
  return new Date(val).toLocaleDateString('id-ID', { weekday: 'long' })
}

function truncate(str, len) {
  if (!str) return '—'
  return str.length <= len ? str : str.slice(0, len) + '…'
}

const totalThisYear = computed(() => {
  if (statsData.value?.total_year != null) return statsData.value.total_year
  const data = statsData.value?.by_month
  if (!data) return '—'
  return data.reduce((sum, d) => sum + (Number(d.count) || 0), 0)
})

function toIsoDate(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function emptyFilters() {
  return {
    search: '',
    status: '',
    student_id: '',
    counseling_type_id: '',
    class_id: '',
    academic_year_id: '',
    semester_id: '',
    date_from: '',
    date_to: '',
  }
}

function statNumber(val) {
  if (statsError.value) return '—'
  if (statsData.value == null && val == null) return '—'
  return val ?? 0
}

const monthStatLabel = computed(() => {
  const now = new Date()
  const label = now.toLocaleDateString('id-ID', { month: 'long' })
  return `Sesi ${label}`
})

const monthStatHint = computed(() => {
  if (statsError.value) return 'Gagal memuat ringkasan'
  if (statsData.value == null) return 'Memuat ringkasan...'
  const done = Number(statsData.value.completed_this_month || 0)
  const students = Number(statsData.value.students_this_month || 0)
  const yearTotal = Number(statsData.value.total_year || 0)
  if (!Number(statsData.value.total_this_month || 0)) {
    return yearTotal ? `Belum ada sesi · ${yearTotal} tahun ini` : 'Belum ada sesi bulan ini'
  }
  return `${done} selesai · ${students} siswa`
})

const upcomingStatCount = computed(() => {
  if (statsData.value?.upcoming_count != null) return statsData.value.upcoming_count
  if (upcomingSessions.value.length) return upcomingSessions.value.length
  if (statsData.value == null) return '—'
  return 0
})

const upcomingStatHint = computed(() => {
  if (statsError.value) return 'Gagal memuat jadwal'
  const next = statsData.value?.next_session_date
  if (next) return `Berikutnya ${formatDate(next)}`
  if (upcomingSessions.value[0]?.session_date) return `Berikutnya ${formatDate(upcomingSessions.value[0].session_date)}`
  if (statsData.value == null) return 'Memuat jadwal...'
  const overdue = Number(statsData.value.overdue_count || 0)
  if (overdue) return `${overdue} jadwal sudah lewat tanggal`
  return 'Tidak ada jadwal ke depan'
})

const openStatHint = computed(() => {
  if (statsError.value) return 'Gagal memuat status'
  if (statsData.value == null) return 'Memuat status...'
  const jadwal = Number(statsData.value.open_jadwal || 0)
  const live = Number(statsData.value.open_berlangsung || 0)
  const overdue = Number(statsData.value.overdue_count || 0)
  if (!Number(statsData.value.open_count || 0)) return 'Semua sesi sudah ditutup'
  const parts = []
  if (overdue) parts.push(`${overdue} terlewat`)
  else if (jadwal) parts.push(`${jadwal} jadwal`)
  if (live) parts.push(`${live} berlangsung`)
  return parts.join(' · ')
})

function applyStatFilter(kind) {
  if (activeStatFilter.value === kind) {
    resetFilters()
    return
  }
  const now = new Date()
  const today = toIsoDate(now)
  const monthStart = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`
  const monthEnd = toIsoDate(new Date(now.getFullYear(), now.getMonth() + 1, 0))
  const next = emptyFilters()

  if (kind === 'month') {
    next.date_from = monthStart
    next.date_to = monthEnd
  } else if (kind === 'upcoming') {
    next.status = 'jadwal'
    next.date_from = today
  } else {
    const overdue = Number(statsData.value?.overdue_count || 0)
    const live = Number(statsData.value?.open_berlangsung || 0)
    if (overdue && !live) {
      next.status = 'jadwal'
      const yesterday = new Date(now)
      yesterday.setDate(yesterday.getDate() - 1)
      next.date_to = toIsoDate(yesterday)
    } else if (live && !overdue) {
      next.status = 'berlangsung'
    } else if (live && overdue) {
      next.status = ''
      next.date_to = today
    } else {
      next.status = 'jadwal'
    }
  }

  filters.value = next
  activeStatFilter.value = kind
  pagination.value.current_page = 1
  loadSessions()
}

function rowNumber(index) {
  return (pagination.value.current_page - 1) * pagination.value.per_page + index + 1
}

const hasActiveFilters = computed(() => {
  const f = filters.value
  const ay = institution.value?.active_academic_year_id
  const sm = institution.value?.active_semester_id
  return !!(
    f.search
    || f.status
    || f.student_id
    || f.counseling_type_id
    || f.class_id
    || f.date_from
    || f.date_to
    || (f.academic_year_id && String(f.academic_year_id) !== String(ay || ''))
    || (f.semester_id && String(f.semester_id) !== String(sm || ''))
  )
})

function resetFilters() {
  const ay = institution.value?.active_academic_year_id
  const sm = institution.value?.active_semester_id
  filters.value = {
    ...emptyFilters(),
    academic_year_id: ay ? String(ay) : '',
    semester_id: sm ? String(sm) : '',
  }
  activeStatFilter.value = null
  pagination.value.current_page = 1
  loadSessions()
}

async function loadSessions() {
  loading.value = true
  formError.value = ''
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page || 15,
      ...filters.value,
    }
    if (!params.status) delete params.status
    if (!params.student_id) delete params.student_id
    if (!params.counseling_type_id) delete params.counseling_type_id
    if (!params.class_id) delete params.class_id
    if (!params.academic_year_id && !activeStatFilter.value) delete params.academic_year_id
    if (!params.semester_id && !activeStatFilter.value) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search

    const res = await counselingApi.getAll(params)
    sessions.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat sesi konseling', e.formattedMessage || 'Data sesi konseling tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function loadTypes() {
  typesLoading.value = true
  try {
    const res = await counselingTypeApi.getAll({ active_only: false })
    counselingTypes.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jenis konseling', e.formattedMessage || 'Data jenis konseling tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    typesLoading.value = false
  }
}

async function loadCounselors() {
  try {
    const res = await counselingApi.getCounselors()
    counselors.value = res.data.data || []
  } catch {
    counselors.value = []
  }
}

async function loadClasses() {
  try {
    const res = await counselingApi.classesLite()
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadPickerStudents() {
  const q = pickerStudentSearch.value.trim()
  if (!pickerClassId.value && !q) {
    pickerStudents.value = []
    pickerStudentError.value = ''
    return
  }
  loadingPickerStudents.value = true
  pickerStudentError.value = ''
  try {
    const params = {}
    if (pickerClassId.value) params.class_id = pickerClassId.value
    if (q) params.q = q
    const res = await counselingApi.studentsLite(params)
    pickerStudents.value = res.data.data || []
    if (!pickerStudents.value.length) {
      pickerStudentError.value = q
        ? 'Tidak ada siswa cocok. Coba kata kunci lain.'
        : 'Tidak ada siswa aktif di kelas ini.'
    }
  } catch (e) {
    pickerStudents.value = []
    pickerStudentError.value = e.formattedMessage || 'Gagal memuat data siswa.'
  } finally {
    loadingPickerStudents.value = false
  }
}

function debouncePickerStudentSearch() {
  clearTimeout(pickerStudentTimer)
  pickerStudentTimer = setTimeout(() => loadPickerStudents(), 300)
}

function onPickerClassChange() {
  form.value.student_id = ''
  selectedPickerStudent.value = null
  pickerStudentSearch.value = ''
  pickerPanelOpen.value = true
  loadPickerStudents()
}

function resetStudentPicker() {
  pickerClassId.value = ''
  pickerStudentSearch.value = ''
  pickerStudents.value = []
  pickerStudentError.value = ''
  pickerPanelOpen.value = true
  selectedPickerStudent.value = null
  counselorSearch.value = ''
  clearTimeout(pickerStudentTimer)
}

function defaultCounselorId() {
  const uid = authStore.user?.id
  if (uid && counselors.value.some((c) => String(c.id) === String(uid))) {
    return uid
  }
  return ''
}

function prefillPickerStudent(student) {
  if (!student?.id) return
  pickerStudents.value = [{
    id: student.id,
    name: student.name,
    nis: student.nis,
    nisn: student.nisn,
    nik: student.nik,
    class_id: student.class_id,
    class_name: student.class_name || student.class?.name,
  }]
  pickerStudentError.value = ''
  form.value.student_id = String(student.id)
  selectedPickerStudent.value = pickerStudents.value[0]
  pickerPanelOpen.value = false
}

async function loadStudentIntoPicker(studentId) {
  if (!studentId) return
  try {
    const res = await counselingApi.studentsLite({ student_id: studentId })
    const student = (res.data.data || [])[0]
    if (student) prefillPickerStudent(student)
  } catch {
    // picker stays empty; user can search manually
  }
}

function clearStudentFilter() {
  filters.value.student_id = ''
  loadSessions()
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 200 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

async function loadInstitutionAndSetFilterDefaults() {
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data ?? res.data ?? null
    if (institution.value?.active_academic_year_id) {
      filters.value.academic_year_id = String(institution.value.active_academic_year_id)
    }
    if (institution.value?.active_semester_id) {
      filters.value.semester_id = String(institution.value.active_semester_id)
    }
  } catch {
    institution.value = null
  }
}

async function exportToCsv() {
  exporting.value = true
  try {
    const params = { ...filters.value }
    if (!params.student_id) delete params.student_id
    if (!params.status) delete params.status
    if (!params.counseling_type_id) delete params.counseling_type_id
    if (!params.class_id) delete params.class_id
    if (!params.academic_year_id && !activeStatFilter.value) delete params.academic_year_id
    if (!params.semester_id && !activeStatFilter.value) delete params.semester_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    if (!params.search) delete params.search
    const res = await counselingApi.export(params)
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-konseling-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor data konseling', e.formattedMessage || 'Data tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = false
  }
}

function goToPage(page) {
  if (page === pagination.value.current_page) return
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  loadSessions()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadSessions()
}

function openAddModal() {
  editingSession.value = null
  resetStudentPicker()
  form.value = {
    student_id: '',
    counselor_id: defaultCounselorId(),
    counseling_type_id: '',
    session_date: new Date().toISOString().slice(0, 10),
    status: 'jadwal',
    summary: '',
    follow_up_notes: '',
  }
  formError.value = ''
  showFormModal.value = true
  if (counselors.value.length === 0) {
    loadCounselors().then(() => {
      if (!form.value.counselor_id) form.value.counselor_id = defaultCounselorId()
    })
  }
  if (counselingTypes.value.length === 0) loadTypes()
}

function openEditModal(s) {
  editingSession.value = s
  form.value = {
    student_id: s.student_id,
    counselor_id: s.counselor_id,
    counseling_type_id: s.counseling_type_id || '',
    session_date: s.session_date,
    status: s.status || 'jadwal',
    summary: s.summary || '',
    follow_up_notes: s.follow_up_notes || '',
  }
  formError.value = ''
  counselorSearch.value = ''
  if (counselors.value.length === 0) loadCounselors()
  showFormModal.value = true
}

async function submitSession() {
  formError.value = ''
  if (!editingSession.value && !form.value.student_id) {
    formError.value = 'Pilih siswa terlebih dahulu.'
    pickerPanelOpen.value = true
    return
  }
  formSubmitting.value = true
  try {
    const payload = {
      student_id: form.value.student_id,
      counselor_id: form.value.counselor_id,
      counseling_type_id: form.value.counseling_type_id || null,
      session_date: form.value.session_date,
      status: form.value.status,
      summary: form.value.summary || null,
      follow_up_notes: form.value.follow_up_notes || null,
    }
    if (editingSession.value) {
      await counselingApi.update(editingSession.value.id, {
        counselor_id: payload.counselor_id,
        counseling_type_id: payload.counseling_type_id,
        session_date: payload.session_date,
        status: payload.status,
        summary: payload.summary,
        follow_up_notes: payload.follow_up_notes,
      })
      toast.success('Sesi konseling berhasil diperbarui')
    } else {
      await counselingApi.create(payload)
      toast.success('Sesi konseling berhasil dicatat')
    }
    showFormModal.value = false
    loadSessions()
    loadStats()
    loadUpcoming()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    formSubmitting.value = false
  }
}

function confirmDelete(s) {
  deleteTarget.value = s
}

async function doDeleteSession() {
  if (!deleteTarget.value) return
  try {
    await counselingApi.delete(deleteTarget.value.id)
    toast.success('Sesi konseling dihapus')
    deleteTarget.value = null
    loadSessions()
    loadStats()
    loadUpcoming()
  } catch (e) {
    toast.error('Gagal menghapus sesi konseling', e.formattedMessage || 'Sesi tidak dapat dihapus. Coba lagi.')
  }
}

function openAddTypeModal() {
  editingType.value = null
  typeForm.value = { name: '', code: '', description: '' }
  typeFormError.value = ''
  showTypeModal.value = true
}

function openEditTypeModal(t) {
  editingType.value = t
  typeForm.value = {
    name: t.name || '',
    code: t.code || '',
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
      await counselingTypeApi.update(editingType.value.id, {
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis konseling berhasil diperbarui')
    } else {
      await counselingTypeApi.create({
        name: typeForm.value.name,
        code: typeForm.value.code || null,
        description: typeForm.value.description || null,
      })
      toast.success('Jenis konseling berhasil ditambahkan')
    }
    showTypeModal.value = false
    loadTypes()
    if (activeTab.value === 'list') counselingTypes.value = (await counselingTypeApi.getAll({ active_only: false })).data.data || []
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
    await counselingTypeApi.delete(deleteTypeTarget.value.id)
    toast.success('Jenis konseling dihapus')
    deleteTypeTarget.value = null
    loadTypes()
    if (activeTab.value === 'list') counselingTypes.value = (await counselingTypeApi.getAll({ active_only: false })).data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || e.response?.data?.message || 'Jenis sudah dipakai, tidak dapat dihapus')
  }
}

const sessionsByMonthChartData = computed(() => {
  const data = statsData.value?.by_month
  if (!data?.length || !data.some((d) => d.count > 0)) return null
  return {
    labels: data.map((d) => d.label),
    datasets: [
      {
        label: 'Jumlah sesi',
        data: data.map((d) => d.count),
        backgroundColor: 'rgba(5, 150, 105, 0.6)',
        borderColor: 'rgb(5, 150, 105)',
        borderWidth: 1,
      },
    ],
  }
})
const sessionsByTypeChartData = computed(() => {
  const data = statsData.value?.by_type
  if (!data?.length) return null
  const colors = ['#059669', '#047857', '#22d3ee', '#67e8f9', '#a5f3fc', '#cffafe']
  return {
    labels: data.map((d) => d.name),
    datasets: [
      {
        data: data.map((d) => d.count),
        backgroundColor: data.map((_, i) => colors[i % colors.length]),
        borderWidth: 0,
      },
    ],
  }
})
const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
}
const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}

async function loadStats() {
  try {
    const res = await counselingApi.getStats({ year: statsYear.value })
    statsData.value = res.data.data || null
    statsError.value = false
  } catch {
    statsData.value = null
    statsError.value = true
  }
}
async function loadUpcoming() {
  try {
    const res = await counselingApi.getUpcoming({ limit: 10 })
    upcomingSessions.value = res.data?.data ?? res.data ?? []
  } catch {
    upcomingSessions.value = []
  }
}

function openHistoryModal(student) {
  if (!student) return
  historyStudent.value = student
  historySessions.value = []
  showHistoryModal.value = true
  loadHistoryForStudent(student.id)
}
async function loadHistoryForStudent(studentId) {
  historyLoading.value = true
  try {
    const res = await counselingApi.getByStudent(studentId, { per_page: 50 })
    historySessions.value = res.data?.data ?? res.data ?? []
  } catch {
    historySessions.value = []
  } finally {
    historyLoading.value = false
  }
}
function openAddModalForStudent(student) {
  openAddModal()
  prefillPickerStudent(student)
}

watch(activeTab, (tab) => {
  if (tab === 'list') {
    loadStats()
    loadUpcoming()
  }
})

onMounted(async () => {
  const studentIdFromQuery = route.query.student_id
  if (studentIdFromQuery) {
    filters.value.student_id = String(studentIdFromQuery)
    activeTab.value = 'list'
  }
  await Promise.all([
    loadInstitutionAndSetFilterDefaults(),
    referenceStore.getAcademicYears(),
    loadSemesters(),
  ])
  loadSessions()
  loadTypes()
  loadStats()
  loadUpcoming()
  loadClasses()
  loadCounselors()
  if (studentIdFromQuery) {
    openAddModal()
    await loadStudentIntoPicker(studentIdFromQuery)
  }
})
</script>

<style scoped>
.counseling-page {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 100%;
  padding: 1.25rem 1.5rem 2rem;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-left: auto;
}
.main-tabs {
  display: flex;
  gap: 4px;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 10px;
}
.main-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  border: none;
  border-radius: 8px;
  background: transparent;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
}
.main-tab:hover { color: #0f172a; }
.main-tab.active {
  background: #fff;
  color: #0f172a;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}
@media (max-width: 1024px) {
  .toolbar { flex-direction: column; align-items: stretch; }
  .header-actions { width: 100%; margin-left: 0; }
  .header-actions .btn-primary,
  .header-actions .btn-secondary,
  .header-actions .btn-compact {
    flex: 1 1 auto;
    justify-content: center;
  }
}
.settings-hint {
  margin: 0;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #475569;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  border-left: 4px solid #059669;
  line-height: 1.45;
}
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  padding: 0.75rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.search-wrap {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex: 1 1 220px;
  min-width: 180px;
  max-width: 320px;
  padding: 0 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #94a3b8;
}
.search-wrap input {
  flex: 1;
  min-width: 0;
  border: none;
  padding: 0.5rem 0;
  font-size: 0.875rem;
  color: #0f172a;
  background: transparent;
}
.search-wrap input:focus { outline: none; }
.search-wrap:focus-within {
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.filter-select {
  padding: 0.5rem 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  min-width: 132px;
  background: #fff;
  font-size: 0.85rem;
  color: #0f172a;
}
.filter-field {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
}
.filter-field .filter-select { min-width: 0; }
.filter-field input[type="date"] { min-width: 138px; }
.filter-chip {
  padding: 0.4rem 0.7rem;
  border: 1px solid #059669;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.filter-chip:hover { background: #d1fae5; }
.loading-state {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 0.75rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
}
.empty-icon {
  margin-bottom: 1rem;
  color: #94a3b8;
}
.empty-title {
  font-size: 1.1rem;
  margin: 0 0 0.5rem 0;
}
.empty-desc {
  color: #64748b;
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
}
.btn-empty-cta {
  margin-top: 0.5rem;
}
.table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.table-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.table-panel-head h3 {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #0f172a;
}
.table-panel-head span {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
}
.table-container {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.data-table th {
  font-weight: 600;
  font-size: 0.75rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
  white-space: nowrap;
}
.data-table tbody tr:hover { background: #f8fafc; }
.data-table tbody tr:last-child td { border-bottom: none; }
.student-cell {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  min-width: 180px;
}
.picker-avatar.sm {
  width: 32px;
  height: 32px;
  font-size: 0.7rem;
}
.date-primary {
  display: block;
  font-weight: 600;
  color: #0f172a;
  white-space: nowrap;
}
.date-sub {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: capitalize;
}
.counselor-cell { color: #334155; white-space: nowrap; }
.type-pill {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  background: #f1f5f9;
  color: #334155;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}
.col-no {
  width: 48px;
  min-width: 48px;
  text-align: center;
  color: #94a3b8;
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.data-table th.col-no {
  text-align: center;
}
.col-date { width: 120px; }
.col-status { width: 110px; }
.col-actions { width: 108px; }
.col-actions .action-buttons { justify-content: flex-end; }
.summary-cell {
  max-width: 260px;
  color: #475569;
  line-height: 1.4;
}
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}
.status-jadwal {
  background: #e0f2fe;
  color: #0369a1;
}
.status-berlangsung {
  background: #fef3c7;
  color: #b45309;
}
.status-selesai {
  background: #d1fae5;
  color: #047857;
}
.status-dibatalkan {
  background: #f1f5f9;
  color: #64748b;
}
.action-buttons {
  display: flex;
  gap: 0.25rem;
}
.btn-action {
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-edit:hover {
  background: #eff6ff;
  border-color: #059669;
}
.btn-delete:hover {
  background: #fef2f2;
  border-color: #ef4444;
}
.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}
.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}
.pagination-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}
.per-page {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
}
.per-page select {
  width: auto;
  min-width: 64px;
  padding: 0.3rem 0.5rem;
}
.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.btn-page {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
  color: #334155;
}
.btn-page-num {
  min-width: 36px;
  padding: 0.4rem 0.5rem;
}
.btn-page.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
  font-weight: 700;
}
.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 0.75rem;
}
.type-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  background: #fff;
}
.type-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.type-name {
  font-weight: 600;
}
.type-code {
  font-size: 0.85rem;
  color: #64748b;
}
.type-desc {
  font-size: 0.9rem;
  color: #475569;
  margin: 0 0 0.75rem 0;
}
.type-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 12px;
  max-width: 580px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}
.form-modal .modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.form-modal .modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
}
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  padding: 0;
  line-height: 1;
}
.modal-body {
  padding: 1.25rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
}
.form-group textarea {
  resize: vertical;
  min-height: 60px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.error-message {
  color: #dc2626;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
}
.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
}
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.btn-ghost {
  padding: 0.45rem 0.75rem;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-ghost.active {
  border-style: solid;
  border-color: #059669;
  background: #ecfdf5;
  color: #047857;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}
.stat-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  width: 100%;
  padding: 0.95rem 1rem;
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  text-align: left;
  font: inherit;
  color: inherit;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}
.stat-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 8px 20px -16px rgba(15, 23, 42, 0.45);
}
.stat-card:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}
.stat-card.active {
  border-color: #059669;
  background: #f0fdf4;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.stat-card.stat-upcoming.active {
  border-color: #0284c7;
  background: #f0f9ff;
  box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}
.stat-card.stat-open.active {
  border-color: #ea580c;
  background: #fff7ed;
  box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.stat-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ecfdf5;
  color: #047857;
}
.stat-upcoming .stat-icon {
  background: #eff6ff;
  color: #0369a1;
}
.stat-open .stat-icon {
  background: #fff7ed;
  color: #c2410c;
}
.stat-body {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
}
.stat-card .stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
}
.stat-card .stat-value {
  font-size: 1.55rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.15;
  letter-spacing: -0.03em;
}
.stat-card.stat-upcoming .stat-value { color: #0369a1; }
.stat-card.stat-open .stat-value { color: #c2410c; }
.stat-hint {
  font-size: 0.75rem;
  color: #94a3b8;
  line-height: 1.35;
}
.chart-details {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.5rem 1rem 0.85rem;
}
.chart-details summary {
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  padding: 0.4rem 0;
}
.dashboard-charts {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
.chart-box { min-height: 160px; }
.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}
.chart-header h4 {
  margin: 0;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
}
.chart-year-select {
  padding: 0.25rem 0.45rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.8rem;
}
.chart-wrap {
  height: 160px;
  position: relative;
}
.chart-wrap-pie { height: 170px; }
.chart-empty {
  margin: 1.5rem 0;
  text-align: center;
  color: #94a3b8;
  font-size: 0.85rem;
}

.upcoming-block {
  padding: 0.85rem 1rem;
  background: #eff6ff;
  border-radius: 12px;
  border: 1px solid #bfdbfe;
}
.upcoming-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.65rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e40af;
}
.upcoming-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.upcoming-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  text-align: left;
  padding: 0.55rem 0.7rem;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #e0f2fe;
  cursor: pointer;
}
.upcoming-item:hover { border-color: #93c5fd; }
.upcoming-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.upcoming-main strong {
  font-size: 0.875rem;
  color: #0f172a;
}
.upcoming-main span {
  font-size: 0.75rem;
  color: #64748b;
}
.upcoming-date {
  margin-left: auto;
  font-size: 0.8rem;
  font-weight: 600;
  color: #0369a1;
  white-space: nowrap;
}

@media (max-width: 900px) {
  .dashboard-charts { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
  .stats-grid, .dashboard-charts { grid-template-columns: 1fr; }
  .search-wrap { max-width: none; flex: 1 1 100%; }
}

/* Riwayat per siswa */
.history-modal .modal-content { max-width: 640px; }
.history-modal .modal-body { max-height: 70vh; overflow-y: auto; }
.loading-state.small, .empty-state.small { padding: 1rem; text-align: center; }
.empty-state.small p { margin: 0 0 0.5rem 0; }
.history-table-wrap .data-table.compact th,
.history-table-wrap .data-table.compact td { padding: 0.5rem 0.75rem; font-size: 0.9rem; }
.mt-1 { margin-top: 0.5rem; }
.btn-sm { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.student-picker {
  padding: 0.9rem 1rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.picker-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}
.picker-heading .field-label {
  margin: 0;
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
}
.picker-selected-hint {
  font-size: 0.75rem;
  font-weight: 600;
  color: #047857;
  background: #d1fae5;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}
.picker-student-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.85rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.picker-student-card.is-selected {
  border-color: #059669;
  background: #ecfdf5;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.picker-student-card.is-static {
  background: #fff;
}
.picker-avatar {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}
.picker-student-meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.picker-student-meta strong,
.picker-student-meta span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.picker-student-meta strong {
  font-size: 0.92rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.25;
}
.picker-student-meta span {
  font-size: 0.78rem;
  color: #64748b;
  line-height: 1.3;
}
.picker-clear {
  margin-left: auto;
  flex-shrink: 0;
  padding: 0.3rem 0.7rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.picker-clear:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.picker-student-card + .picker-panel {
  margin-top: 0.7rem;
}
.picker-toolbar {
  display: grid;
  grid-template-columns: minmax(120px, 0.9fr) minmax(160px, 1.4fr);
  gap: 0.65rem;
}
.student-picker .picker-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin: 0;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
}
.picker-field select,
.picker-search-wrap {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
}
.picker-field select {
  padding: 0.5rem 0.7rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
}
.picker-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0 0.7rem;
  color: #94a3b8;
}
.student-picker .picker-search-wrap input {
  flex: 1;
  width: auto;
  min-width: 0;
  border: none;
  border-radius: 0;
  padding: 0.5rem 0;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
  background: transparent;
  box-shadow: none;
}
.picker-search-wrap input:focus {
  outline: none;
}
.picker-search-wrap:focus-within {
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.picker-list {
  margin-top: 0.65rem;
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
.picker-list-meta {
  padding: 0.45rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
}
.picker-option {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.75rem;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  cursor: pointer;
}
.picker-option:last-child {
  border-bottom: none;
}
.picker-option:hover {
  background: #f8fafc;
}
.picker-option.active {
  background: #ecfdf5;
}
.picker-option.active .picker-student-meta strong {
  color: #047857;
}
.picker-check {
  margin-left: auto;
  flex-shrink: 0;
}
.picker-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 132px;
  padding: 1rem;
  text-align: center;
  font-size: 0.85rem;
  color: #64748b;
}
.picker-state p {
  margin: 0;
  max-width: 16rem;
  line-height: 1.4;
}
.picker-state.is-error {
  color: #b45309;
}
.picker-spinner {
  width: 22px;
  height: 22px;
  border: 2px solid #d1fae5;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
.student-picker-locked {
  margin-bottom: 1rem;
  padding: 0.9rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.student-picker-locked .field-label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.55rem;
  font-size: 0.9rem;
  color: #0f172a;
}
.counselor-search { margin-bottom: 0.4rem; }
@media (max-width: 700px) {
  .picker-toolbar { grid-template-columns: 1fr; }
}
</style>
