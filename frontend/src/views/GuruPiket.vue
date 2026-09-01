<template>    <div class="piket-page">
      <div class="toolbar">
        <p class="toolbar-hint">
          Lapor kejadian piket, isi log kegiatan, dan pantau tindak lanjut — tanggal aktif:
          <strong>{{ formatDate(activeDate) }}</strong>
        </p>
        <div class="header-actions">
          <label class="date-field">
            <span>Tanggal</span>
            <input v-model="activeDate" type="date" class="filter-select" @change="onDateChange" />
          </label>
          <button type="button" class="btn-secondary btn-compact" :disabled="scanning" @click="runScan">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M21 3V8H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 8C19.5 5 16.5 3 12 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>{{ scanning ? 'Memindai...' : 'Pindai Hari Ini' }}</span>
          </button>
          <button type="button" class="btn-primary btn-compact" @click="goReportIncident">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Lapor Kejadian
          </button>
          <router-link
            v-if="canAccessModule('teacher_violation_report') || canAccessModule('teacher_appreciation')"
            to="/teacher-appreciation"
            class="btn-secondary btn-compact link-btn"
            title="Modul terpisah: poin apresiasi & pelanggaran guru (bukan laporan kejadian piket)"
          >
            Modul Poin Guru
          </router-link>
        </div>
      </div>

      <div class="tab-shell">
      <nav class="section-nav" aria-label="Navigasi modul Guru Piket">
          <button type="button" :class="['sec-btn', { active: tab === 'hub' }]" @click="tab = 'hub'">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-text"><span class="sec-label">Hari Ini</span><span class="sec-hint">Ringkasan & roster</span></span>
          </button>
          <button type="button" :class="['sec-btn', { active: tab === 'schedule' }]" @click="switchTab('schedule')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="sec-text"><span class="sec-label">Jadwal</span><span class="sec-hint">Roster mingguan</span></span>
          </button>
          <button type="button" :class="['sec-btn', { active: tab === 'logs' }]" @click="switchTab('logs')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            <span class="sec-text"><span class="sec-label">Log Kegiatan</span><span class="sec-hint">Ringkasan tugas piket</span></span>
          </button>
          <button type="button" :class="['sec-btn', { active: tab === 'monitor' }]" @click="switchTab('monitor')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></span>
            <span class="sec-text"><span class="sec-label">Kejadian</span><span class="sec-hint">Lapor & tindak lanjut</span></span>
            <span v-if="dashboard?.open_incidents" class="sec-badge">{{ dashboard.open_incidents }}</span>
          </button>
          <button v-if="canViewReport" type="button" :class="['sec-btn', { active: tab === 'report' }]" @click="tab = 'report'">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg></span>
            <span class="sec-text"><span class="sec-label">Laporan</span><span class="sec-hint">Mingguan & bulanan</span></span>
          </button>
          <button v-if="canManage" type="button" :class="['sec-btn', { active: tab === 'settings' }]" @click="switchTab('settings')">
            <span class="sec-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" stroke="currentColor" stroke-width="2"/></svg></span>
            <span class="sec-text"><span class="sec-label">Pengaturan</span><span class="sec-hint">Ambang & opsi</span></span>
          </button>
      </nav>
      <div class="tab-main">
        <p class="tab-description">{{ tabDescription }}</p>

      <p v-if="error" class="error-banner">{{ error }}</p>
      <p v-if="success" class="success-banner">{{ success }}</p>

      <main class="page-main">
        <!-- HUB -->
        <template v-if="tab === 'hub'">
          <div v-if="loadingHub" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat dashboard...</p>
          </div>
          <template v-else>
            <div v-if="today?.is_on_duty" class="on-duty-banner">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>
                Anda bertugas piket hari ini
                <template v-if="today.my_schedule"> · Shift {{ shiftLabel(today.my_schedule.shift) }}</template>
              </span>
            </div>

            <div class="stat-cards">
              <button type="button" class="stat-card stat-warn stat-card-btn" @click="goMonitorFilter('kelas_kosong')">
                <span class="stat-label">Kelas Kosong</span>
                <strong class="stat-value">{{ dashboard?.incident_counts?.kelas_kosong ?? 0 }}</strong>
              </button>
              <button type="button" class="stat-card stat-alert stat-card-btn" @click="goMonitorFilter('terlambat_guru')">
                <span class="stat-label">Terlambat Guru</span>
                <strong class="stat-value">{{ dashboard?.incident_counts?.terlambat_guru ?? 0 }}</strong>
              </button>
              <button type="button" class="stat-card stat-info stat-card-btn" @click="goMonitorFilter('terlambat_siswa')">
                <span class="stat-label">Terlambat Siswa</span>
                <strong class="stat-value">{{ dashboard?.incident_counts?.terlambat_siswa ?? 0 }}</strong>
              </button>
              <button type="button" class="stat-card stat-card-btn" @click="goMonitorFilter('lainnya')">
                <span class="stat-label">Lainnya</span>
                <strong class="stat-value">{{ dashboard?.incident_counts?.lainnya ?? 0 }}</strong>
              </button>
              <button type="button" class="stat-card stat-accent stat-card-btn" @click="goMonitorFilter('', 'pending_bk')">
                <span class="stat-label">Usulan ke BK</span>
                <strong class="stat-value">{{ dashboard?.pending_student_violations ?? 0 }}</strong>
              </button>
              <button type="button" class="stat-card stat-card-btn" @click="goMonitorFilter('', 'pending_ks')">
                <span class="stat-label">Usulan ke KS</span>
                <strong class="stat-value">{{ dashboard?.pending_teacher_violations ?? 0 }}</strong>
              </button>
            </div>

            <section class="panel-block">
              <div class="panel-head">
                <div>
                  <h3>Roster Piket</h3>
                  <p class="panel-sub">{{ rosterDayLabel }} · {{ formatDate(activeDate) }}</p>
                </div>
                <div class="panel-head-actions">
                  <button type="button" class="btn-secondary btn-compact" @click="goReportIncident">
                    Lapor Kejadian
                  </button>
                  <button type="button" class="btn-primary btn-compact" @click="openLogModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Isi Log Kegiatan
                  </button>
                </div>
              </div>
              <div v-if="!(dashboard?.roster?.schedules || []).length" class="empty-state empty-state-sm">
                <div class="empty-icon">
                  <svg width="56" height="56" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <h3 class="empty-title">Belum ada jadwal piket hari ini</h3>
                <p class="empty-desc">Atur jadwal di tab Jadwal (khusus pengelola).</p>
                <button v-if="canManage" type="button" class="btn-primary btn-empty-cta" @click="switchTab('schedule')">
                  Atur Jadwal
                </button>
              </div>
              <div v-else class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Guru</th>
                      <th>Shift</th>
                      <th>Jam</th>
                      <th>Log</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="s in dashboard.roster.schedules" :key="s.id">
                      <td>
                        <span class="cell-name">{{ s.employee?.name || '-' }}</span>
                        <span class="cell-meta">{{ s.employee?.nip || '-' }}</span>
                      </td>
                      <td><span class="shift-chip">{{ shiftLabel(s.shift) }}</span></td>
                      <td>{{ formatTimeRange(s.start_time, s.end_time) }}</td>
                      <td>
                        <span v-if="logForEmployee(s.employee_id)" :class="['status-badge', logForEmployee(s.employee_id).status]">
                          {{ statusLogLabel(logForEmployee(s.employee_id).status) }}
                        </span>
                        <span v-else class="muted">Belum ada</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
          </template>
        </template>

        <!-- SCHEDULE -->
        <template v-else-if="tab === 'schedule'">
          <div class="filters filters-inline">
            <select v-model="scheduleDayFilter" class="filter-select" @change="loadSchedules">
              <option value="">Semua Hari</option>
              <option v-for="(label, key) in days" :key="key" :value="String(key)">{{ label }}</option>
            </select>
            <span v-if="schedules.length" class="schedule-summary">
              {{ schedules.length }} penugasan · {{ groupedSchedules.length }} hari
            </span>
            <div class="filters-spacer"></div>
            <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openScheduleModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Tambah Jadwal
            </button>
          </div>
          <div v-if="loadingSchedules" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat jadwal...</p>
          </div>
          <div v-else-if="!schedules.length" class="empty-state">
            <div class="empty-icon">
              <svg width="72" height="72" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="empty-title">Belum ada jadwal piket</h3>
            <p class="empty-desc">Tambahkan guru piket per hari dan shift.</p>
            <button v-if="canManage" type="button" class="btn-primary btn-empty-cta" @click="openScheduleModal()">
              Tambah Jadwal
            </button>
          </div>
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Hari</th>
                  <th>Guru</th>
                  <th>Shift</th>
                  <th>Jam</th>
                  <th v-if="canManage">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in groupedSchedules" :key="group.day">
                  <tr
                    v-for="(s, index) in group.items"
                    :key="s.id"
                    :class="['schedule-row', { 'schedule-group-start': index === 0 }]"
                  >
                    <td v-if="index === 0" :rowspan="group.items.length" class="schedule-day-cell">
                      <span class="schedule-day-name">{{ group.label }}</span>
                      <span class="schedule-day-count">{{ group.teacherCount }} guru piket</span>
                      <button
                        v-if="canManage"
                        type="button"
                        class="btn-add-day"
                        @click="openScheduleModal(null, group.day)"
                      >
                        + Tambah guru
                      </button>
                    </td>
                    <td>
                      <span class="cell-name">{{ s.employee?.name }}</span>
                      <span class="cell-meta">{{ s.employee?.nip || '-' }}</span>
                    </td>
                    <td><span class="shift-chip">{{ s.shift_label || shiftLabel(s.shift) }}</span></td>
                    <td>{{ formatTimeRange(s.start_time, s.end_time) }}</td>
                    <td v-if="canManage">
                      <div class="action-buttons">
                        <TableAction kind="edit" @click="openScheduleModal(s)" />
                        <TableAction kind="delete" @click="removeSchedule(s)" />
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>

        <!-- LOGS -->
        <template v-else-if="tab === 'logs'">
          <div class="filters filters-inline">
            <label class="filter-date-label">
              <span>Dari</span>
              <input v-model="logFilters.date_from" type="date" class="filter-select" @change="loadLogs" />
            </label>
            <label class="filter-date-label">
              <span>Sampai</span>
              <input v-model="logFilters.date_to" type="date" class="filter-select" @change="loadLogs" />
            </label>
            <select v-model="logFilters.status" class="filter-select" @change="loadLogs">
              <option value="">Semua Status</option>
              <option value="draft">Draft</option>
              <option value="submitted">Dikirim</option>
              <option value="reviewed">Direview</option>
            </select>
            <div class="filters-spacer"></div>
            <button type="button" class="btn-primary btn-compact" @click="openLogModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Tambah Log
            </button>
          </div>
          <div v-if="loadingLogs" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat log...</p>
          </div>
          <div v-else-if="!logs.length" class="empty-state">
            <div class="empty-icon">
              <svg width="72" height="72" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="empty-title">Belum ada log harian</h3>
            <p class="empty-desc">Catat ringkasan kegiatan piket setiap hari.</p>
            <button type="button" class="btn-primary btn-empty-cta" @click="openLogModal()">Tambah Log</button>
          </div>
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Guru</th>
                  <th>Ringkasan</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td>{{ formatDate(log.duty_date) }}</td>
                  <td>{{ log.employee?.name || '-' }}</td>
                  <td class="summary-cell">
                    <div>{{ log.summary || '-' }}</div>
                    <div v-if="log.incidents_count" class="cell-meta">{{ log.incidents_count }} kejadian terkait</div>
                  </td>
                  <td>
                    <span :class="['status-badge', log.status]">
                      {{ log.status_label || statusLogLabel(log.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <TableAction kind="edit" @click="openLogModal(log)" />
                      <TableAction
                        v-if="canManage && log.status !== 'reviewed'"
                        kind="approve"
                        title="Review"
                        @click="reviewLog(log)"
                      />
                      <TableAction kind="delete" @click="removeLog(log)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- MONITOR -->
        <template v-else-if="tab === 'monitor'">
          <div class="filters filters-inline">
            <input v-model="incidentFilters.date" type="date" class="filter-select" @change="onIncidentFilterChange" />
            <select v-model="incidentFilters.incident_type" class="filter-select" @change="onIncidentFilterChange">
              <option value="">Semua Jenis</option>
              <option v-for="(label, key) in incidentTypes" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="incidentFilters.status" class="filter-select" @change="onIncidentFilterChange">
              <option value="">Semua Status</option>
              <option value="open">Baru</option>
              <option value="confirmed">Diproses</option>
              <option value="resolved">Selesai</option>
              <option value="dismissed">Diabaikan</option>
            </select>
            <div class="filters-spacer"></div>
            <button type="button" class="btn-secondary btn-compact" :disabled="scanning" @click="runScan">
              {{ scanning ? 'Memindai...' : 'Pindai Otomatis' }}
            </button>
            <button type="button" class="btn-primary btn-compact" @click="openIncidentModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Lapor Kejadian
            </button>
          </div>
          <div v-if="loadingIncidents" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat kejadian...</p>
          </div>
          <div v-else-if="!incidents.length" class="empty-state">
            <div class="empty-icon">
              <svg width="72" height="72" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M2.45801 12C3.73228 7.94288 7.52257 5 12.0002 5C16.4778 5 20.2681 7.94291 21.5424 12C20.2681 16.0571 16.4778 19 12.0002 19C7.52256 19 3.73226 16.0571 2.45801 12Z" stroke="currentColor" stroke-width="1.5"/>
              </svg>
            </div>
            <h3 class="empty-title">Belum ada kejadian tercatat</h3>
            <p class="empty-desc">Laporkan kelas kosong, keterlambatan, atau kejadian lain — atau jalankan pemindaian otomatis.</p>
            <div class="empty-actions">
              <button type="button" class="btn-secondary btn-empty-cta" :disabled="scanning" @click="runScan">
                Pindai Otomatis
              </button>
              <button type="button" class="btn-primary btn-empty-cta" @click="openIncidentModal()">
                Lapor Kejadian
              </button>
            </div>
          </div>
          <template v-else>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Detail</th>
                    <th>Sumber</th>
                    <th>Status</th>
                    <th class="col-aksi">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(inc, idx) in incidents" :key="inc.id">
                    <td class="col-no">{{ incidentRowNumber(idx) }}</td>
                    <td>{{ formatDate(inc.incident_date) }}</td>
                    <td>
                      <span :class="['type-chip', 'type-' + inc.incident_type]">
                        {{ inc.type_label || incidentTypes[inc.incident_type] || inc.incident_type }}
                      </span>
                    </td>
                    <td class="summary-cell">
                      <div>{{ inc.description || '-' }}</div>
                      <div v-if="inc.school_class" class="cell-meta">
                        {{ inc.school_class.name }}
                        <template v-if="inc.period"> · Jam ke-{{ inc.period }}</template>
                      </div>
                      <div v-if="inc.employee" class="cell-meta">Guru: {{ inc.employee.name }}</div>
                      <div v-if="inc.student" class="cell-meta">Siswa: {{ inc.student.name }} ({{ inc.student.nis }})</div>
                      <div v-if="inc.minutes_late" class="cell-meta">Terlambat {{ inc.minutes_late }} menit</div>
                      <div v-if="inc.violation" class="propose-badge" :class="'v-' + inc.violation.status">
                        Usulan BK: {{ violationStatusLabel(inc.violation.status) }}
                        <template v-if="inc.violation.violation_type">
                          · {{ inc.violation.violation_type.name }} (+{{ inc.violation.violation_type.point_weight }})
                        </template>
                      </div>
                      <div v-if="inc.teacher_violation" class="propose-badge" :class="'tv-' + inc.teacher_violation.status">
                        Usulan KM: {{ teacherViolationStatusLabel(inc.teacher_violation.status) }}
                        <template v-if="inc.teacher_violation.violation_type">
                          · {{ inc.teacher_violation.violation_type.name }}
                          (−{{ inc.teacher_violation.point_value || inc.teacher_violation.violation_type.point_weight }})
                        </template>
                      </div>
                    </td>
                    <td>{{ inc.source === 'auto' ? 'Otomatis' : 'Manual' }}</td>
                    <td>
                      <span :class="['status-badge', inc.status]">{{ incidentStatusLabel(inc.status) }}</span>
                    </td>
                    <td class="col-aksi">
                      <div class="action-icons">
                        <button
                          v-if="canProposeViolation(inc)"
                          type="button"
                          class="btn-icon btn-icon-primary"
                          title="Ajukan ke BK"
                          aria-label="Ajukan ke BK"
                          @click="openProposeModal(inc)"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3L4 6V11C4 16 7.5 20.5 12 22C16.5 20.5 20 16 20 11V6L12 3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                        </button>
                        <button
                          v-if="canProposeTeacherViolation(inc)"
                          type="button"
                          class="btn-icon btn-icon-primary"
                          title="Ajukan ke Kepala Madrasah"
                          aria-label="Ajukan ke Kepala Madrasah"
                          @click="openProposeTeacherModal(inc)"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 21H21M5 21V9L12 4L19 9V21M9 21V14H15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                        </button>
                        <button
                          v-if="canCloseIncident(inc)"
                          type="button"
                          class="btn-icon btn-icon-success"
                          title="Selesai"
                          aria-label="Selesai"
                          @click="setIncidentStatus(inc, 'resolved')"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                        </button>
                        <button
                          v-if="canCloseIncident(inc)"
                          type="button"
                          class="btn-icon"
                          title="Abaikan"
                          aria-label="Abaikan"
                          @click="setIncidentStatus(inc, 'dismissed')"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </button>
                        <button
                          v-if="canDeleteIncident(inc)"
                          type="button"
                          class="btn-icon btn-icon-danger"
                          title="Hapus"
                          aria-label="Hapus"
                          @click="removeIncident(inc)"
                        >
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 6H21M8 6V4H16V6M19 6V20C19 21 18 22 17 22H7C6 22 5 21 5 20V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <PaginationBar
              :page="incidentPagination.current_page"
              :last-page="incidentPagination.last_page"
              :per-page="incidentPagination.per_page"
              :total="incidentPagination.total"
              item-label="data"
              :per-page-options="[10, 15, 20, 25, 50]"
              @page-change="loadIncidents"
              @per-page-change="changeIncidentPerPage"
            />
          </template>
        </template>

        <!-- REPORT -->
        <template v-else-if="tab === 'report' && canViewReport">
          <div class="report-section">
            <form @submit.prevent="loadReport" class="report-form card-form">
              <div class="report-form-top">
                <div>
                  <h3 class="report-panel-title">Laporan Guru Piket</h3>
                  <p class="report-panel-desc">
                    Pilih periode mingguan atau bulanan, tampilkan ringkasan di layar, lalu Preview PDF untuk cetak/simpan.
                  </p>
                </div>
                <div class="report-export-actions">
                  <button
                    type="button"
                    class="btn-export-pdf"
                    :disabled="exporting || reportLoading || !reportData"
                    title="Preview laporan PDF"
                    @click="previewReportPdf"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>{{ exporting ? 'Menyiapkan...' : 'Preview PDF' }}</span>
                  </button>
                </div>
              </div>

              <div class="form-row report-filters">
                <div class="form-group form-group-type">
                  <label>Jenis laporan</label>
                  <div class="filter-tabs report-type-tabs">
                    <button
                      type="button"
                      :class="['tab', { active: reportPeriod === 'weekly' }]"
                      @click="setReportPeriod('weekly')"
                    >
                      Mingguan
                    </button>
                    <button
                      type="button"
                      :class="['tab', { active: reportPeriod === 'monthly' }]"
                      @click="setReportPeriod('monthly')"
                    >
                      Bulanan
                    </button>
                  </div>
                </div>
                <div v-if="reportPeriod === 'weekly'" class="form-group">
                  <label>Senin minggu</label>
                  <input v-model="reportWeekStart" type="date" @change="invalidateReport" />
                </div>
                <div v-else class="form-group">
                  <label>Bulan</label>
                  <input v-model="reportMonth" type="month" @change="invalidateReport" />
                </div>
                <button type="submit" class="btn-primary btn-search" :disabled="reportLoading">
                  <svg v-if="!reportLoading" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M9 17V7M15 17V12M21 21H3V3H21V21ZM5 19H19V5H5V19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span v-else class="mini-spinner"></span>
                  {{ reportLoading ? 'Memuat...' : 'Tampilkan' }}
                </button>
              </div>
            </form>

            <div v-if="reportLoading" class="loading-wrap">
              <p>Memuat laporan...</p>
            </div>

            <template v-else-if="reportLoaded && reportData">
              <div class="report-toolbar">
                <span class="report-period">{{ reportData.title }} · {{ reportData.period_label }}</span>
                <span class="report-count">
                  {{ reportData.summary?.total_logs || 0 }} log · {{ reportData.summary?.total_incidents || 0 }} insiden
                </span>
              </div>

              <div class="stat-cards report-summary-cards">
                <div class="stat-card">
                  <span class="stat-label">Log Piket</span>
                  <span class="stat-value">{{ reportData.summary?.total_logs || 0 }}</span>
                </div>
                <div class="stat-card stat-warn">
                  <span class="stat-label">Kelas Kosong</span>
                  <span class="stat-value">{{ reportData.summary?.kelas_kosong || 0 }}</span>
                </div>
                <div class="stat-card stat-alert">
                  <span class="stat-label">Terlambat Guru</span>
                  <span class="stat-value">{{ reportData.summary?.terlambat_guru || 0 }}</span>
                </div>
                <div class="stat-card stat-info">
                  <span class="stat-label">Terlambat Siswa</span>
                  <span class="stat-value">{{ reportData.summary?.terlambat_siswa || 0 }}</span>
                </div>
                <div class="stat-card">
                  <span class="stat-label">Lainnya</span>
                  <span class="stat-value">{{ reportData.summary?.lainnya || 0 }}</span>
                </div>
                <div class="stat-card stat-accent">
                  <span class="stat-label">Total Insiden</span>
                  <span class="stat-value">{{ reportData.summary?.total_incidents || 0 }}</span>
                </div>
              </div>

              <section class="panel-block report-block">
                <div class="panel-head">
                  <h3>1. Log Harian</h3>
                </div>
                <div v-if="!(reportData.logs || []).length" class="muted">Belum ada log pada periode ini.</div>
                <div v-else class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Guru Piket</th>
                        <th>Ringkasan</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="log in reportData.logs" :key="'rl-' + log.id">
                        <td>{{ formatDate(log.duty_date) }}</td>
                        <td>
                          <span class="cell-name">{{ log.employee?.name || '-' }}</span>
                          <span class="cell-meta">{{ log.employee?.nip || '-' }}</span>
                        </td>
                        <td>{{ log.summary || '-' }}</td>
                        <td>
                          <span :class="['status-badge', log.status]">
                            {{ log.status_label || statusLogLabel(log.status) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <section class="panel-block report-block">
                <div class="panel-head">
                  <h3>2. Monitoring Insiden</h3>
                </div>
                <div v-if="!(reportData.incidents || []).length" class="muted">Tidak ada insiden pada periode ini.</div>
                <div v-else class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Detail</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="inc in reportData.incidents" :key="'ri-' + inc.id">
                        <td>{{ formatDate(inc.incident_date) }}</td>
                        <td>{{ inc.type_label || incidentTypes[inc.incident_type] || inc.incident_type }}</td>
                        <td>
                          <div>{{ inc.description || '-' }}</div>
                          <div v-if="inc.school_class" class="cell-meta">
                            Kelas: {{ inc.school_class.name }}
                            <template v-if="inc.period"> · Jam ke-{{ inc.period }}</template>
                          </div>
                          <div v-if="inc.employee" class="cell-meta">
                            Guru: {{ inc.employee.name }}
                            <template v-if="inc.employee.nip"> ({{ inc.employee.nip }})</template>
                          </div>
                          <div v-if="inc.student" class="cell-meta">
                            Siswa: {{ inc.student.name }} ({{ inc.student.nis || '-' }})
                          </div>
                          <div v-if="inc.minutes_late" class="cell-meta">Terlambat {{ inc.minutes_late }} menit</div>
                        </td>
                        <td>
                          <span :class="['status-badge', 'inc-' + inc.status]">
                            {{ inc.status_label || incidentStatusLabel(inc.status) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </template>

            <div v-else-if="reportLoaded" class="empty-state report-empty">
              <div class="empty-icon">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M9 17V7M15 17V12M21 21H3V3H21V21ZM5 19H19V5H5V19Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <h3 class="empty-title">Tidak ada data laporan</h3>
              <p class="empty-desc">Tidak ada data untuk periode yang dipilih. Sesuaikan filter lalu tampilkan lagi.</p>
            </div>

            <div v-else class="empty-state report-empty">
              <div class="empty-icon">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 13H16M8 17H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <h3 class="empty-title">Siap menampilkan laporan</h3>
              <p class="empty-desc">
                Pilih Mingguan atau Bulanan, atur periode, lalu klik Tampilkan untuk melihat ringkasan sebelum mencetak PDF.
              </p>
            </div>
          </div>
        </template>

        <!-- SETTINGS -->
        <template v-else-if="tab === 'settings' && canManage">
          <div class="settings-card">
            <h3>Pengaturan Piket</h3>
            <p class="settings-hint">Ambang keterlambatan dan opsi pemindaian otomatis.</p>
            <div class="form-group">
              <label>Batas keterlambatan guru (check-in)</label>
              <input v-model="settingsForm.teacher_late_threshold" type="time" class="form-input" />
            </div>
            <div class="form-group">
              <label>Toleransi kelas kosong (menit setelah jam mulai)</label>
              <input v-model.number="settingsForm.empty_class_grace_minutes" type="number" min="0" max="120" class="form-input" />
            </div>
            <label class="check-inline">
              <input v-model="settingsForm.include_saturday" type="checkbox" />
              Sertakan Sabtu pada jadwal & laporan
            </label>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="settingsForm.notes" rows="3" class="form-input" />
            </div>
            <div class="settings-actions">
              <button type="button" class="btn-primary btn-compact" :disabled="savingSettings" @click="saveSettings">
                {{ savingSettings ? 'Menyimpan...' : 'Simpan Pengaturan' }}
              </button>
            </div>
          </div>
        </template>
      </main>
      </div>
      </div>
    </div>

    <!-- Schedule modal -->
    <div v-if="scheduleModal" class="modal-overlay" @click.self="scheduleModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal Piket' }}</h3>
          <button type="button" class="btn-close" aria-label="Tutup" @click="scheduleModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Guru</label>
            <select v-model="scheduleForm.employee_id" class="form-select">
              <option value="">Pilih guru</option>
              <option v-for="e in employees" :key="e.id" :value="String(e.id)">{{ e.name }} — {{ e.nip || '-' }}</option>
            </select>
          </div>
          <div v-if="editingSchedule" class="form-group">
            <label>Hari</label>
            <select v-model="scheduleForm.day_of_week" class="form-select">
              <option v-for="(label, key) in days" :key="key" :value="String(key)">{{ label }}</option>
            </select>
          </div>
          <div v-else class="day-picker">
            <div class="day-picker-head">
              <span>Hari piket</span>
              <button type="button" class="btn-link" @click="toggleAllScheduleDays">
                {{ allScheduleDaysSelected ? 'Kosongkan' : 'Pilih semua' }}
              </button>
            </div>
            <p class="muted day-picker-hint">Satu guru bisa dipilih untuk beberapa hari sekaligus.</p>
            <div class="day-checkboxes">
              <label v-for="(label, key) in days" :key="key" class="day-check">
                <input v-model="scheduleForm.days_of_week" type="checkbox" :value="String(key)" />
                <span>{{ label }}</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label>Shift</label>
            <select v-model="scheduleForm.shift" class="form-select">
              <option value="pagi">Pagi</option>
              <option value="siang">Siang</option>
              <option value="full">Seharian</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Mulai</label>
              <input v-model="scheduleForm.start_time" type="time" class="form-input" />
            </div>
            <div class="form-group">
              <label>Selesai</label>
              <input v-model="scheduleForm.end_time" type="time" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="scheduleForm.notes" rows="2" class="form-input" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="scheduleModal = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="savingSchedule" @click="saveSchedule">
            {{ savingSchedule ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Log modal -->
    <div v-if="logModal" class="modal-overlay" @click.self="logModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingLog ? 'Edit Log Kegiatan' : 'Log Kegiatan Piket' }}</h3>
          <button type="button" class="btn-close" aria-label="Tutup" @click="logModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Tanggal</label>
            <input v-model="logForm.duty_date" type="date" class="form-input" :disabled="!!editingLog" />
          </div>
          <div class="form-group">
            <label>Ringkasan kegiatan</label>
            <textarea v-model="logForm.summary" rows="4" class="form-input" placeholder="Kegiatan, temuan, tindak lanjut..." />
          </div>
          <div class="form-group">
            <label>Catatan serah terima</label>
            <textarea v-model="logForm.handoff_notes" rows="2" class="form-input" />
          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="logForm.status" class="form-select">
              <option value="draft">Draft</option>
              <option value="submitted">Kirim</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="logModal = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="savingLog" @click="saveLog">
            {{ savingLog ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Propose violation to BK -->
    <div v-if="proposeModal" class="modal-overlay" @click.self="proposeModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Ajukan Pelanggaran ke BK</h3>
          <button type="button" class="btn-close" aria-label="Tutup" @click="proposeModal = false">×</button>
        </div>
        <div class="modal-body">
          <p class="muted modal-hint">
            Usulan menunggu persetujuan BK. Poin siswa baru masuk setelah disetujui.
          </p>
          <div v-if="proposeTarget" class="target-card">
            <strong>{{ proposeTarget.student?.name || 'Siswa' }}</strong>
            <div class="cell-meta">
              {{ formatDate(proposeTarget.incident_date) }}
              · {{ incidentTypes[proposeTarget.incident_type] || proposeTarget.incident_type }}
              <template v-if="proposeTarget.minutes_late"> · {{ proposeTarget.minutes_late }} menit</template>
            </div>
            <div v-if="proposeTarget.description" class="cell-meta">{{ proposeTarget.description }}</div>
          </div>
          <div class="form-group">
            <label>Jenis pelanggaran *</label>
            <select v-model="proposeForm.violation_type_id" class="form-select">
              <option value="">Pilih jenis</option>
              <option v-for="t in violationTypes" :key="t.id" :value="String(t.id)">
                {{ t.name }} (+{{ t.point_weight }}) — {{ t.category }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Keterangan (opsional)</label>
            <textarea v-model="proposeForm.description" rows="3" class="form-input" placeholder="Tambahan untuk BK..." />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="proposeModal = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="proposing" @click="submitPropose">
            {{ proposing ? 'Mengirim...' : 'Kirim ke BK' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Propose teacher violation to KS -->
    <div v-if="proposeTeacherModal" class="modal-overlay" @click.self="proposeTeacherModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Ajukan Poin Minus ke Kepala Madrasah</h3>
          <button type="button" class="btn-close" aria-label="Tutup" @click="proposeTeacherModal = false">×</button>
        </div>
        <div class="modal-body">
          <p class="muted modal-hint">
            Usulan menunggu persetujuan Kepala Madrasah. Poin guru baru berkurang setelah disetujui.
          </p>
          <div v-if="proposeTeacherTarget" class="target-card">
            <strong>{{ proposeTeacherTarget.employee?.name || 'Guru' }}</strong>
            <div class="cell-meta">
              {{ formatDate(proposeTeacherTarget.incident_date) }}
              · {{ incidentTypes[proposeTeacherTarget.incident_type] || proposeTeacherTarget.incident_type }}
              <template v-if="proposeTeacherTarget.minutes_late"> · {{ proposeTeacherTarget.minutes_late }} menit</template>
            </div>
            <div v-if="proposeTeacherTarget.description" class="cell-meta">{{ proposeTeacherTarget.description }}</div>
          </div>
          <div class="form-group">
            <label>Jenis pelanggaran *</label>
            <select v-model="proposeTeacherForm.violation_type_id" class="form-select">
              <option value="">Pilih jenis</option>
              <option v-for="t in teacherViolationTypes" :key="t.id" :value="String(t.id)">
                {{ t.name }} (−{{ t.point_weight }}) — {{ t.category }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Keterangan (opsional)</label>
            <textarea v-model="proposeTeacherForm.notes" rows="3" class="form-input" placeholder="Tambahan untuk Kepala Madrasah..." />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="proposeTeacherModal = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="proposingTeacher" @click="submitProposeTeacher">
            {{ proposingTeacher ? 'Mengirim...' : 'Kirim ke Kepala Madrasah' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Incident wizard -->
    <div v-if="incidentModal" class="modal-overlay" @click.self="closeIncidentModal">
      <div class="modal-content modal-content-wide">
        <div class="modal-header">
          <div>
            <h3>Lapor Kejadian</h3>
            <p class="modal-step-hint">
              Langkah {{ incidentStep }} dari 2 —
              {{ incidentStep === 1 ? 'pilih jenis kejadian' : 'isi detail kejadian' }}
            </p>
          </div>
          <button type="button" class="btn-close" aria-label="Tutup" @click="closeIncidentModal">×</button>
        </div>
        <div class="modal-body">
          <!-- Step 1: jenis -->
          <div v-if="incidentStep === 1" class="type-picker">
            <p class="muted modal-hint">Pilih jenis kejadian — Anda langsung lanjut mengisi detail.</p>
            <div class="type-picker-grid">
              <button
                v-for="(label, key) in incidentTypes"
                :key="key"
                type="button"
                :class="['type-picker-card', { active: incidentForm.incident_type === key }]"
                @click="selectIncidentType(key)"
              >
                <strong>{{ label }}</strong>
                <span>{{ incidentTypeHint(key) }}</span>
              </button>
            </div>
          </div>

          <!-- Step 2: detail -->
          <template v-else>
            <div class="selected-type-bar">
              <span>{{ incidentTypes[incidentForm.incident_type] || incidentForm.incident_type }}</span>
              <button type="button" class="btn-link" @click="incidentStep = 1">Ganti jenis</button>
            </div>

            <div class="form-group">
              <label>Tanggal</label>
              <input v-model="incidentForm.incident_date" type="date" class="form-input" />
            </div>

            <div v-if="incidentForm.incident_type === 'kelas_kosong'" class="form-row">
              <div class="form-group">
                <label>Kelas *</label>
                <select v-model="incidentForm.class_id" class="form-select">
                  <option value="">Pilih kelas</option>
                  <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jam ke-</label>
                <input v-model.number="incidentForm.period" type="number" min="1" max="20" class="form-input" />
              </div>
            </div>

            <div v-if="['kelas_kosong', 'terlambat_guru'].includes(incidentForm.incident_type)" class="form-group">
              <label>{{ incidentForm.incident_type === 'kelas_kosong' ? 'Guru yang seharusnya mengajar *' : 'Guru yang terlambat *' }}</label>
              <select v-model="incidentForm.employee_id" class="form-select">
                <option value="">Pilih guru</option>
                <option v-for="e in employees" :key="e.id" :value="String(e.id)">{{ e.name }}</option>
              </select>
            </div>

            <div v-if="incidentForm.incident_type === 'terlambat_siswa'" class="student-picker">
              <div class="form-group">
                <label>Kelas (opsional, untuk mempersempit pencarian)</label>
                <select v-model="incidentForm.class_id" class="form-select" @change="onIncidentClassChange">
                  <option value="">Semua kelas</option>
                  <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Cari siswa *</label>
                <input
                  v-model="studentSearch"
                  type="text"
                  class="form-input"
                  placeholder="Ketik nama / NIS / NISN..."
                  @input="debounceStudentSearch"
                />
                <p class="field-hint">
                  <template v-if="loadingStudents">Mencari siswa...</template>
                  <template v-else-if="studentSearchError">{{ studentSearchError }}</template>
                  <template v-else-if="students.length">
                    {{ students.length }} siswa ditemukan — pilih di daftar bawah.
                  </template>
                  <template v-else>
                    Pilih kelas atau ketik nama/NIS. Daftar siswa aktif akan muncul di sini.
                  </template>
                </p>
                <select v-model="incidentForm.student_id" class="form-select" style="margin-top: 0.5rem" size="6">
                  <option value="">Pilih siswa</option>
                  <option v-for="s in students" :key="s.id" :value="String(s.id)">
                    {{ studentOptionLabel(s) }}
                  </option>
                </select>
              </div>
            </div>

            <div v-if="['terlambat_guru', 'terlambat_siswa'].includes(incidentForm.incident_type)" class="form-row">
              <div class="form-group">
                <label>Menit terlambat</label>
                <input v-model.number="incidentForm.minutes_late" type="number" min="0" max="600" class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam terdeteksi</label>
                <input v-model="incidentForm.detected_at" type="time" class="form-input" />
              </div>
            </div>
            <div v-else class="form-group">
              <label>Jam terdeteksi</label>
              <input v-model="incidentForm.detected_at" type="time" class="form-input" />
            </div>

            <div class="form-group">
              <label>
                Keterangan
                <template v-if="incidentForm.incident_type === 'lainnya'"> *</template>
              </label>
              <textarea
                v-model="incidentForm.description"
                rows="3"
                class="form-input"
                :placeholder="incidentForm.incident_type === 'lainnya' ? 'Jelaskan kejadian...' : 'Opsional'"
              />
            </div>
            <p class="field-hint">
              Setelah disimpan, Anda bisa mengajukan ke BK atau Kepala Madrasah dari kolom Aksi di daftar kejadian.
            </p>
          </template>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="closeIncidentModal">Batal</button>
          <button
            v-if="incidentStep === 2"
            type="button"
            class="btn-secondary"
            @click="incidentStep = 1"
          >
            Kembali
          </button>
          <button
            v-if="incidentStep === 1"
            type="button"
            class="btn-primary"
            @click="goIncidentStep2"
          >
            Lanjut
          </button>
          <button
            v-else
            type="button"
            class="btn-primary"
            :disabled="savingIncident"
            @click="saveIncident"
          >
            {{ savingIncident ? 'Menyimpan...' : 'Simpan Kejadian' }}
          </button>
        </div>
      </div>
    </div></template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { piketApi } from '@/api/piket'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const isSchoolAdmin = computed(() =>
  ['admin', 'super_admin', 'institution_admin'].includes(authStore.user?.role)
)
const canAccessModule = (key) =>
  isSchoolAdmin.value
  || (authStore.user?.permissions || []).includes(key)
  || (key === 'guru_piket' && (!!authStore.user?.is_piket_scheduled || !!authStore.user?.is_piket_on_duty))
const canManageByRoleOrPerm = computed(() =>
  isSchoolAdmin.value || (authStore.user?.permissions || []).includes('guru_piket_manage')
)

const tab = ref('hub')
const activeDate = ref(new Date().toISOString().slice(0, 10))
const error = ref('')
const success = ref('')
const canManageFromApi = ref(false)
const canViewReportFromApi = ref(false)
const canManage = computed(() => canManageFromApi.value || canManageByRoleOrPerm.value)
const canViewReport = computed(() => canViewReportFromApi.value || isSchoolAdmin.value)
const days = ref({ 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' })
const shifts = ref({ pagi: 'Pagi', siang: 'Siang', full: 'Seharian' })
const incidentTypes = ref({
  kelas_kosong: 'Kelas Kosong',
  terlambat_guru: 'Keterlambatan Guru',
  terlambat_siswa: 'Keterlambatan Siswa',
  lainnya: 'Lainnya',
})

const tabDescription = computed(() => {
  const map = {
    hub: 'Ringkasan kejadian hari ini dan roster guru yang bertugas piket.',
    schedule: 'Kelola jadwal piket per hari dan shift. Satu guru bisa ditugaskan ke beberapa hari.',
    logs: 'Isi ringkasan kegiatan piket dan catatan serah terima (bukan laporan kejadian).',
    monitor: 'Lapor kejadian, lalu ajukan ke BK atau Kepala Madrasah dari kolom Aksi bila perlu tindak lanjut poin.',
    report: 'Tampilkan laporan mingguan/bulanan di layar, lalu Preview PDF untuk cetak.',
    settings: 'Atur ambang keterlambatan guru dan toleransi deteksi kelas kosong.',
  }
  return map[tab.value] || ''
})

const dashboard = ref(null)
const today = ref(null)
const loadingHub = ref(false)
const scanning = ref(false)

const schedules = ref([])
const loadingSchedules = ref(false)
const scheduleDayFilter = ref('')
const scheduleModal = ref(false)
const editingSchedule = ref(null)
const savingSchedule = ref(false)
const scheduleForm = reactive({
  employee_id: '',
  day_of_week: '1',
  days_of_week: [],
  shift: 'pagi',
  start_time: '07:00',
  end_time: '14:00',
  notes: '',
})

const allScheduleDaysSelected = computed(() => {
  const keys = Object.keys(days.value)
  return keys.length > 0 && keys.every((k) => scheduleForm.days_of_week.includes(String(k)))
})

const groupedSchedules = computed(() => {
  const shiftOrder = { pagi: 1, siang: 2, full: 3 }
  const groups = new Map()

  schedules.value.forEach((schedule) => {
    const day = Number(schedule.day_of_week)
    if (!groups.has(day)) {
      groups.set(day, {
        day,
        label: schedule.day_name || dayName(day),
        items: [],
      })
    }
    groups.get(day).items.push(schedule)
  })

  return [...groups.values()]
    .sort((a, b) => a.day - b.day)
    .map((group) => ({
      ...group,
      teacherCount: new Set(group.items.map((item) => item.employee_id)).size,
      items: group.items.sort((a, b) => {
        const shiftDiff = (shiftOrder[a.shift] || 99) - (shiftOrder[b.shift] || 99)
        if (shiftDiff !== 0) return shiftDiff
        return (a.employee?.name || '').localeCompare(b.employee?.name || '', 'id')
      }),
    }))
})

const toggleAllScheduleDays = () => {
  if (allScheduleDaysSelected.value) {
    scheduleForm.days_of_week = []
  } else {
    scheduleForm.days_of_week = Object.keys(days.value).map(String)
  }
}

const logs = ref([])
const loadingLogs = ref(false)
const logFilters = reactive({ date_from: '', date_to: '', status: '' })
const logModal = ref(false)
const editingLog = ref(null)
const savingLog = ref(false)
const logForm = reactive({
  duty_date: activeDate.value,
  summary: '',
  handoff_notes: '',
  status: 'draft',
})

const incidents = ref([])
const loadingIncidents = ref(false)
const incidentFilters = reactive({
  date: activeDate.value,
  incident_type: '',
  status: '',
})
const incidentPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})
const incidentModal = ref(false)
const incidentStep = ref(1)
const savingIncident = ref(false)
const incidentForm = reactive({
  incident_date: activeDate.value,
  incident_type: '',
  class_id: '',
  period: null,
  employee_id: '',
  student_id: '',
  minutes_late: null,
  detected_at: '',
  description: '',
})

const employees = ref([])
const classes = ref([])
const students = ref([])
const studentSearch = ref('')
const loadingStudents = ref(false)
const studentSearchError = ref('')
let studentTimer = null

const proposeModal = ref(false)
const proposeTarget = ref(null)
const proposing = ref(false)
const violationTypes = ref([])
const proposeForm = reactive({
  violation_type_id: '',
  description: '',
})

const proposeTeacherModal = ref(false)
const proposeTeacherTarget = ref(null)
const proposingTeacher = ref(false)
const teacherViolationTypes = ref([])
const proposeTeacherForm = reactive({
  violation_type_id: '',
  notes: '',
})

const settingsForm = reactive({
  teacher_late_threshold: '07:15',
  include_saturday: false,
  empty_class_grace_minutes: 15,
  notes: '',
})
const savingSettings = ref(false)
const reportPeriod = ref('weekly')
const reportWeekStart = ref('')
const reportMonth = ref('')
const reportLoading = ref(false)
const reportLoaded = ref(false)
const reportData = ref(null)
const exporting = ref(false)

const flash = (msg, isError = false) => {
  if (isError) {
    error.value = msg
    success.value = ''
  } else {
    success.value = msg
    error.value = ''
  }
  setTimeout(() => {
    if (isError) error.value = ''
    else success.value = ''
  }, 4000)
}

const dayName = (d) => days.value[d] || days.value[String(d)] || '-'
const dayNameFromDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr + (String(dateStr).includes('T') ? '' : 'T00:00:00'))
    const jsDay = d.getDay() // 0 Sun .. 6 Sat
    const key = jsDay === 0 ? 7 : jsDay
    return days.value[key] || days.value[String(key)] || d.toLocaleDateString('id-ID', { weekday: 'long' })
  } catch {
    return '-'
  }
}
const rosterDayLabel = computed(() => {
  const fromApi = dayName(dashboard.value?.roster?.day_of_week)
  if (fromApi && fromApi !== '-') return fromApi
  return dayNameFromDate(activeDate.value)
})
const shiftLabel = (s) => shifts.value[s] || s
const statusLogLabel = (s) => ({ draft: 'Draft', submitted: 'Dikirim', reviewed: 'Direview' }[s] || s)
const incidentStatusLabel = (s) => ({
  open: 'Baru',
  confirmed: 'Diproses',
  resolved: 'Selesai',
  dismissed: 'Diabaikan',
}[s] || s)
const violationStatusLabel = (s) => ({
  pending: 'Menunggu BK',
  dicatat: 'Disetujui',
  sanksi_diberikan: 'Sanksi',
  follow_up: 'Follow up',
  selesai: 'Selesai',
  ditolak: 'Ditolak',
}[s] || s)
const teacherViolationStatusLabel = (s) => ({
  pending: 'Menunggu KM',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}[s] || s)
const canProposeViolation = (inc) => {
  if (!inc?.student_id && !inc?.student?.id) return false
  if (inc.can_propose_violation === true) return true
  if (inc.can_propose_violation === false) return false
  return !inc.violation || inc.violation.status === 'ditolak'
}
const canProposeTeacherViolation = (inc) => {
  const type = inc?.incident_type
  if (!['terlambat_guru', 'kelas_kosong'].includes(type)) return false
  if (!inc?.employee_id && !inc?.employee?.id) return false
  if (inc.can_propose_teacher_violation === true) return true
  if (inc.can_propose_teacher_violation === false) return false
  return !inc.teacher_violation || inc.teacher_violation.status === 'rejected'
}
const canCloseIncident = (inc) => ['open', 'confirmed'].includes(inc?.status)
const canDeleteIncident = (inc) => {
  if (inc?.source === 'auto' && !canManage.value) return false
  return true
}
const incidentTypeHint = (key) => ({
  kelas_kosong: 'Kelas tanpa guru pada jam pelajaran',
  terlambat_guru: 'Guru datang terlambat ke sekolah/kelas',
  terlambat_siswa: 'Siswa datang terlambat',
  lainnya: 'Kejadian lain yang perlu dicatat',
}[key] || '')
const incidentRowNumber = (index) =>
  (incidentPagination.value.current_page - 1) * incidentPagination.value.per_page + index + 1

const formatDate = (d) => {
  if (!d) return '-'
  try {
    return new Date(d + (String(d).includes('T') ? '' : 'T00:00:00')).toLocaleDateString('id-ID')
  } catch {
    return d
  }
}

const formatTimeRange = (start, end) => {
  if (!start && !end) return '-'
  return `${start || '?'}${end ? '–' + end : ''}`
}

const studentOptionLabel = (s) => {
  const id = s.nis || s.nisn || '-'
  const cls = s.class_name ? ` (${s.class_name})` : ''
  return `${s.name} — ${id}${cls}`
}

const logForEmployee = (employeeId) => {
  const list = dashboard.value?.roster?.logs || []
  return list.find((l) => Number(l.employee_id) === Number(employeeId))
}

const mondayOf = (dateStr) => {
  const d = new Date(dateStr + 'T00:00:00')
  const day = d.getDay()
  const diff = day === 0 ? -6 : 1 - day
  d.setDate(d.getDate() + diff)
  return d.toISOString().slice(0, 10)
}

const loadHub = async () => {
  loadingHub.value = true
  try {
    const [dashRes, todayRes] = await Promise.all([
      piketApi.dashboard({ date: activeDate.value }),
      piketApi.today({ date: activeDate.value }),
    ])
    dashboard.value = dashRes.data?.data || null
    today.value = todayRes.data?.data || null
    canManageFromApi.value = !!dashRes.data?.meta?.can_manage
    canViewReportFromApi.value = !!dashRes.data?.meta?.can_view_report
    if (dashRes.data?.meta?.days) days.value = dashRes.data.meta.days
    if (dashRes.data?.meta?.shifts) shifts.value = dashRes.data.meta.shifts
    if (dashRes.data?.meta?.incident_types) incidentTypes.value = dashRes.data.meta.incident_types
    if (tab.value === 'report' && !canViewReport.value) {
      tab.value = 'hub'
    }
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memuat dashboard piket', true)
  } finally {
    loadingHub.value = false
  }
}

const loadSchedules = async () => {
  loadingSchedules.value = true
  try {
    const params = {}
    if (scheduleDayFilter.value) params.day_of_week = scheduleDayFilter.value
    const res = await piketApi.getSchedules(params)
    schedules.value = res.data?.data || []
    if (res.data?.meta?.can_manage) canManageFromApi.value = true
    if (res.data?.meta?.days) days.value = res.data.meta.days
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memuat jadwal', true)
  } finally {
    loadingSchedules.value = false
  }
}

const loadLogs = async () => {
  loadingLogs.value = true
  try {
    const params = {}
    if (logFilters.date_from) params.date_from = logFilters.date_from
    if (logFilters.date_to) params.date_to = logFilters.date_to
    if (logFilters.status) params.status = logFilters.status
    const res = await piketApi.getLogs(params)
    logs.value = res.data?.data || []
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memuat log', true)
  } finally {
    loadingLogs.value = false
  }
}

const loadIncidents = async (page = incidentPagination.value.current_page) => {
  loadingIncidents.value = true
  try {
    const params = {
      page,
      per_page: incidentPagination.value.per_page,
    }
    if (incidentFilters.date) params.date = incidentFilters.date
    if (incidentFilters.incident_type) params.incident_type = incidentFilters.incident_type
    if (incidentFilters.status) params.status = incidentFilters.status
    const res = await piketApi.getIncidents(params)
    incidents.value = res.data?.data || []
    const meta = res.data?.meta || {}
    incidentPagination.value = {
      current_page: meta.current_page || page,
      last_page: meta.last_page || 1,
      per_page: meta.per_page ?? incidentPagination.value.per_page,
      total: meta.total || incidents.value.length,
    }
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memuat kejadian', true)
  } finally {
    loadingIncidents.value = false
  }
}

const onIncidentFilterChange = async () => {
  await loadIncidents(1)
}

const goIncidentPage = async (page) => {
  if (page < 1 || page > incidentPagination.value.last_page) return
  await loadIncidents(page)
}

function changeIncidentPerPage(n) {
  incidentPagination.value.per_page = n
  incidentPagination.value.current_page = 1
  loadIncidents(1)
}

const loadEmployees = async () => {
  try {
    const res = await piketApi.employeesLite()
    employees.value = res.data?.data || []
  } catch {
    employees.value = []
  }
}

const loadClasses = async () => {
  try {
    const res = await piketApi.classesLite()
    classes.value = res.data?.data || []
  } catch {
    classes.value = []
  }
}

const loadStudentsLite = async () => {
  loadingStudents.value = true
  studentSearchError.value = ''
  try {
    const params = {}
    const q = studentSearch.value.trim()
    if (q) params.q = q
    if (incidentForm.class_id) params.class_id = incidentForm.class_id
    const res = await piketApi.studentsLite(params)
    students.value = res.data?.data || []
    if (!students.value.length) {
      studentSearchError.value = q || incidentForm.class_id
        ? 'Tidak ada siswa cocok. Coba kata kunci lain atau ganti kelas.'
        : 'Belum ada siswa aktif.'
    }
  } catch (e) {
    students.value = []
    studentSearchError.value = e.response?.data?.message || 'Gagal memuat data siswa'
  } finally {
    loadingStudents.value = false
  }
}

const debounceStudentSearch = () => {
  clearTimeout(studentTimer)
  studentTimer = setTimeout(() => {
    loadStudentsLite()
  }, 300)
}

const onIncidentClassChange = () => {
  incidentForm.student_id = ''
  loadStudentsLite()
}

const loadSettings = async () => {
  try {
    const res = await piketApi.getSettings()
    const d = res.data?.data || {}
    settingsForm.teacher_late_threshold = d.teacher_late_threshold || '07:15'
    settingsForm.include_saturday = !!d.include_saturday
    settingsForm.empty_class_grace_minutes = d.empty_class_grace_minutes ?? 15
    settingsForm.notes = d.notes || ''
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memuat pengaturan', true)
  }
}

const switchTab = async (name) => {
  tab.value = name
  if (name === 'schedule') {
    await Promise.all([loadSchedules(), loadEmployees()])
  } else if (name === 'logs') {
    await loadLogs()
  } else if (name === 'monitor') {
    await Promise.all([loadIncidents(), loadEmployees(), loadClasses()])
  } else if (name === 'settings') {
    await loadSettings()
  }
}

const goReportIncident = async () => {
  await switchTab('monitor')
  openIncidentModal()
}

const goMonitorFilter = async (type = '', focus = '') => {
  incidentFilters.date = activeDate.value
  incidentFilters.incident_type = type || ''
  incidentFilters.status = focus === 'pending_bk' || focus === 'pending_ks' ? 'confirmed' : ''
  incidentPagination.value.current_page = 1
  await switchTab('monitor')
}

const clearDeepLinkQuery = async () => {
  if (!route.query.tab && !route.query.action) return
  const nextQuery = { ...route.query }
  delete nextQuery.tab
  delete nextQuery.action
  await router.replace({ path: route.path, query: nextQuery })
}

const applyDeepLink = async () => {
  const tabName = String(route.query.tab || '')
  const action = String(route.query.action || '')
  const allowed = ['hub', 'schedule', 'logs', 'monitor', 'report', 'settings']
  if (tabName && allowed.includes(tabName)
    && !(tabName === 'settings' && !canManage.value)
    && !(tabName === 'report' && !canViewReport.value)
  ) {
    await switchTab(tabName)
  }
  if (action === 'new-incident') {
    await switchTab('monitor')
    openIncidentModal()
  } else if (action === 'new-log') {
    await switchTab('logs')
    openLogModal()
  }
  if (tabName || action) {
    await clearDeepLinkQuery()
  }
}

const onDateChange = async () => {
  incidentFilters.date = activeDate.value
  await loadHub()
  if (tab.value === 'monitor') await loadIncidents(1)
}

const runScan = async () => {
  scanning.value = true
  try {
    const res = await piketApi.scanAll({ date: activeDate.value })
    const c = res.data?.counts || {}
    flash(res.data?.message || `Kelas kosong: ${c.kelas_kosong || 0}, terlambat guru: ${c.terlambat_guru || 0}`)
    await loadHub()
    if (tab.value === 'monitor') await loadIncidents()
  } catch (e) {
    flash(e.response?.data?.message || 'Pemindaian gagal', true)
  } finally {
    scanning.value = false
  }
}

const openScheduleModal = (item = null, presetDay = null) => {
  editingSchedule.value = item
  scheduleForm.employee_id = item ? String(item.employee_id) : ''
  scheduleForm.day_of_week = item ? String(item.day_of_week) : String(presetDay || '1')
  scheduleForm.days_of_week = item
    ? [String(item.day_of_week)]
    : (presetDay ? [String(presetDay)] : [])
  scheduleForm.shift = item?.shift || 'pagi'
  scheduleForm.start_time = item?.start_time || '07:00'
  scheduleForm.end_time = item?.end_time || '14:00'
  scheduleForm.notes = item?.notes || ''
  scheduleModal.value = true
  if (!employees.value.length) loadEmployees()
}

const saveSchedule = async () => {
  if (!scheduleForm.employee_id) {
    flash('Pilih guru terlebih dahulu', true)
    return
  }
  if (!editingSchedule.value && !scheduleForm.days_of_week.length) {
    flash('Pilih minimal satu hari piket', true)
    return
  }
  savingSchedule.value = true
  try {
    if (editingSchedule.value) {
      await piketApi.updateSchedule(editingSchedule.value.id, {
        employee_id: Number(scheduleForm.employee_id),
        day_of_week: Number(scheduleForm.day_of_week),
        shift: scheduleForm.shift,
        start_time: scheduleForm.start_time || null,
        end_time: scheduleForm.end_time || null,
        notes: scheduleForm.notes || null,
      })
      flash('Jadwal diperbarui')
    } else {
      const res = await piketApi.createSchedule({
        employee_id: Number(scheduleForm.employee_id),
        days_of_week: scheduleForm.days_of_week.map(Number),
        shift: scheduleForm.shift,
        start_time: scheduleForm.start_time || null,
        end_time: scheduleForm.end_time || null,
        notes: scheduleForm.notes || null,
      })
      flash(res.data?.message || 'Jadwal ditambahkan')
    }
    scheduleModal.value = false
    await loadSchedules()
    if (tab.value === 'hub') await loadHub()
  } catch (e) {
    flash(e.response?.data?.message || e.response?.data?.errors?.days_of_week?.[0] || 'Gagal menyimpan jadwal', true)
  } finally {
    savingSchedule.value = false
  }
}

const removeSchedule = async (item) => {
  if (!confirm(`Hapus jadwal ${item.employee?.name}?`)) return
  try {
    await piketApi.deleteSchedule(item.id)
    flash('Jadwal dihapus')
    await loadSchedules()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal menghapus jadwal', true)
  }
}

const openLogModal = (item = null) => {
  editingLog.value = item
  logForm.duty_date = item?.duty_date || activeDate.value
  logForm.summary = item?.summary || ''
  logForm.handoff_notes = item?.handoff_notes || ''
  logForm.status = item?.status === 'reviewed' ? 'submitted' : (item?.status || 'draft')
  logModal.value = true
}

const saveLog = async () => {
  savingLog.value = true
  try {
    const payload = {
      duty_date: logForm.duty_date,
      summary: logForm.summary || null,
      handoff_notes: logForm.handoff_notes || null,
      status: logForm.status,
    }
    if (editingLog.value) {
      await piketApi.updateLog(editingLog.value.id, payload)
      flash('Log diperbarui')
    } else {
      await piketApi.createLog(payload)
      flash('Log disimpan')
    }
    logModal.value = false
    await loadHub()
    if (tab.value === 'logs') await loadLogs()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal menyimpan log', true)
  } finally {
    savingLog.value = false
  }
}

const reviewLog = async (log) => {
  const notes = prompt('Catatan review (opsional):') ?? ''
  try {
    await piketApi.reviewLog(log.id, { review_notes: notes || null })
    flash('Log direview')
    await loadLogs()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal mereview log', true)
  }
}

const removeLog = async (log) => {
  if (!confirm('Hapus log ini?')) return
  try {
    await piketApi.deleteLog(log.id)
    flash('Log dihapus')
    await loadLogs()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal menghapus log', true)
  }
}

const openIncidentModal = () => {
  incidentStep.value = 1
  incidentForm.incident_date = activeDate.value
  incidentForm.incident_type = ''
  incidentForm.class_id = ''
  incidentForm.period = null
  incidentForm.employee_id = ''
  incidentForm.student_id = ''
  incidentForm.minutes_late = null
  incidentForm.detected_at = ''
  incidentForm.description = ''
  studentSearch.value = ''
  students.value = []
  studentSearchError.value = ''
  incidentModal.value = true
  if (!employees.value.length) loadEmployees()
  if (!classes.value.length) loadClasses()
}

const closeIncidentModal = () => {
  incidentModal.value = false
  incidentStep.value = 1
}

const selectIncidentType = async (key) => {
  incidentForm.incident_type = key
  await goIncidentStep2()
}

const goIncidentStep2 = async () => {
  if (!incidentForm.incident_type) {
    flash('Pilih jenis kejadian terlebih dahulu', true)
    return
  }
  incidentStep.value = 2
  if (incidentForm.incident_type === 'terlambat_siswa') {
    if (!classes.value.length) await loadClasses()
    loadStudentsLite()
  } else if (['kelas_kosong', 'terlambat_guru'].includes(incidentForm.incident_type)) {
    if (!employees.value.length) await loadEmployees()
    if (!classes.value.length && incidentForm.incident_type === 'kelas_kosong') await loadClasses()
  }
}

watch(
  () => incidentForm.incident_type,
  (type) => {
    if (!incidentModal.value || incidentStep.value !== 2) return
    if (type === 'terlambat_siswa') {
      if (!classes.value.length) loadClasses()
      loadStudentsLite()
    } else {
      students.value = []
      studentSearch.value = ''
      studentSearchError.value = ''
    }
  }
)

const validateIncidentForm = () => {
  const type = incidentForm.incident_type
  if (!type) return 'Pilih jenis kejadian'
  if (type === 'kelas_kosong') {
    if (!incidentForm.class_id) return 'Pilih kelas yang kosong'
    if (!incidentForm.employee_id) return 'Pilih guru yang seharusnya mengajar'
  }
  if (type === 'terlambat_guru' && !incidentForm.employee_id) {
    return 'Pilih guru yang terlambat'
  }
  if (type === 'terlambat_siswa' && !incidentForm.student_id) {
    return 'Pilih siswa yang terlambat'
  }
  if (type === 'lainnya' && !String(incidentForm.description || '').trim()) {
    return 'Isi keterangan kejadian'
  }
  return ''
}

const saveIncident = async () => {
  const validationError = validateIncidentForm()
  if (validationError) {
    flash(validationError, true)
    return
  }

  savingIncident.value = true
  try {
    const payload = {
      incident_date: incidentForm.incident_date,
      incident_type: incidentForm.incident_type,
      class_id: incidentForm.class_id ? Number(incidentForm.class_id) : null,
      period: incidentForm.period || null,
      employee_id: incidentForm.employee_id ? Number(incidentForm.employee_id) : null,
      student_id: incidentForm.student_id ? Number(incidentForm.student_id) : null,
      minutes_late: incidentForm.minutes_late || null,
      detected_at: incidentForm.detected_at || null,
      description: incidentForm.description || null,
    }
    await piketApi.createIncident(payload)
    flash('Kejadian dicatat. Ajukan ke BK/Kepala Madrasah dari kolom Aksi bila perlu.')
    closeIncidentModal()
    await loadIncidents(1)
    await loadHub()
  } catch (e) {
    const errors = e.response?.data?.errors
    const firstError = errors ? Object.values(errors).flat()[0] : null
    flash(firstError || e.response?.data?.message || 'Gagal mencatat kejadian', true)
  } finally {
    savingIncident.value = false
  }
}

const setIncidentStatus = async (inc, status) => {
  try {
    await piketApi.updateIncident(inc.id, { status })
    flash('Status diperbarui')
    await loadIncidents()
    await loadHub()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal memperbarui status', true)
  }
}

const removeIncident = async (inc) => {
  if (!confirm('Hapus kejadian ini?')) return
  try {
    await piketApi.deleteIncident(inc.id)
    flash('Kejadian dihapus')
    await loadIncidents()
    await loadHub()
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal menghapus', true)
  }
}

const loadViolationTypes = async () => {
  try {
    const res = await piketApi.violationTypesActive()
    violationTypes.value = res.data.data || res.data || []
  } catch {
    violationTypes.value = []
  }
}

const loadTeacherViolationTypes = async () => {
  try {
    const res = await piketApi.teacherViolationTypesActive()
    teacherViolationTypes.value = res.data.data || res.data || []
  } catch {
    teacherViolationTypes.value = []
  }
}

const suggestedTeacherTypeId = (inc) => {
  const code = inc?.incident_type === 'kelas_kosong' ? 'TV-HDR-02' : 'TV-HDR-01'
  const match = teacherViolationTypes.value.find((t) => t.code === code)
  return match ? String(match.id) : ''
}

const openProposeModal = async (inc) => {
  proposeTarget.value = inc
  proposeForm.violation_type_id = ''
  proposeForm.description = inc.description || ''
  proposeModal.value = true
  if (!violationTypes.value.length) await loadViolationTypes()
}

const openProposeTeacherModal = async (inc) => {
  proposeTeacherTarget.value = inc
  proposeTeacherForm.notes = inc.description || ''
  proposeTeacherModal.value = true
  if (!teacherViolationTypes.value.length) await loadTeacherViolationTypes()
  proposeTeacherForm.violation_type_id = suggestedTeacherTypeId(inc)
}

const submitPropose = async () => {
  if (!proposeTarget.value) return
  if (!proposeForm.violation_type_id) {
    flash('Pilih jenis pelanggaran', true)
    return
  }
  proposing.value = true
  try {
    await piketApi.proposeViolation(proposeTarget.value.id, {
      violation_type_id: Number(proposeForm.violation_type_id),
      description: proposeForm.description || null,
    })
    flash('Usulan dikirim ke BK — menunggu persetujuan')
    proposeModal.value = false
    await loadIncidents()
    await loadHub()
  } catch (e) {
    const msg = e.response?.data?.message
      || e.response?.data?.errors?.piket_incident_id?.[0]
      || e.response?.data?.errors?.student_id?.[0]
      || 'Gagal mengajukan ke BK'
    flash(msg, true)
  } finally {
    proposing.value = false
  }
}

const submitProposeTeacher = async () => {
  if (!proposeTeacherTarget.value) return
  if (!proposeTeacherForm.violation_type_id) {
    flash('Pilih jenis pelanggaran', true)
    return
  }
  proposingTeacher.value = true
  try {
    await piketApi.proposeTeacherViolation(proposeTeacherTarget.value.id, {
      violation_type_id: Number(proposeTeacherForm.violation_type_id),
      notes: proposeTeacherForm.notes || null,
    })
    flash('Usulan dikirim ke Kepala Madrasah — menunggu persetujuan poin minus')
    proposeTeacherModal.value = false
    await loadIncidents()
    await loadHub()
  } catch (e) {
    const msg = e.response?.data?.message
      || e.response?.data?.errors?.piket_incident_id?.[0]
      || e.response?.data?.errors?.employee_id?.[0]
      || e.response?.data?.errors?.incident_type?.[0]
      || 'Gagal mengajukan ke Kepala Madrasah'
    flash(msg, true)
  } finally {
    proposingTeacher.value = false
  }
}

const saveSettings = async () => {
  savingSettings.value = true
  try {
    await piketApi.updateSettings({
      teacher_late_threshold: settingsForm.teacher_late_threshold,
      include_saturday: !!settingsForm.include_saturday,
      empty_class_grace_minutes: Number(settingsForm.empty_class_grace_minutes) || 0,
      notes: settingsForm.notes || null,
    })
    flash('Pengaturan disimpan')
  } catch (e) {
    flash(e.response?.data?.message || 'Gagal menyimpan pengaturan', true)
  } finally {
    savingSettings.value = false
  }
}

const currentMonthKey = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const reportQueryParams = () => {
  const params = { period: reportPeriod.value }
  if (reportPeriod.value === 'monthly') {
    params.month = reportMonth.value || currentMonthKey()
  } else {
    params.week_start = reportWeekStart.value || mondayOf(activeDate.value)
  }
  return params
}

const setReportPeriod = (period) => {
  if (reportPeriod.value === period) return
  reportPeriod.value = period
  invalidateReport()
}

const invalidateReport = () => {
  reportLoaded.value = false
  reportData.value = null
}

const loadReport = async () => {
  reportLoading.value = true
  reportLoaded.value = false
  try {
    const res = await piketApi.report(reportQueryParams())
    reportData.value = res.data?.data ?? res.data ?? null
    reportLoaded.value = true
  } catch (e) {
    reportData.value = null
    reportLoaded.value = true
    flash(e.response?.data?.message || e.formattedMessage || 'Gagal memuat laporan', true)
  } finally {
    reportLoading.value = false
  }
}

const previewReportPdf = async () => {
  if (!reportData.value) {
    flash('Tampilkan laporan dulu sebelum Preview PDF', true)
    return
  }
  exporting.value = true
  try {
    const loaded = reportData.value
    const params = { period: loaded.period || reportPeriod.value }
    if (params.period === 'monthly') {
      params.month = (loaded.period_start || '').slice(0, 7) || reportMonth.value || currentMonthKey()
    } else {
      params.week_start = loaded.period_start || reportWeekStart.value || mondayOf(activeDate.value)
    }
    const res = await piketApi.reportPdf(params)
    const contentType = res.headers?.['content-type'] || ''
    if (res.status !== 200 || contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const parsed = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(parsed.message || 'Gagal menyiapkan laporan')
    }
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data], { type: 'application/pdf' })
    if (blob.type.includes('json') || (res.data?.type && String(res.data.type).includes('json'))) {
      const text = await blob.text()
      const parsed = JSON.parse(text)
      throw new Error(parsed.message || 'Gagal menyiapkan laporan')
    }
    const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
    const win = window.open('', '_blank')
    if (!win) {
      flash('Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.', true)
      URL.revokeObjectURL(url)
      return
    }
    const title = reportData.value?.title || 'Preview Laporan Guru Piket'
    const periodLabel = reportData.value?.period_label || ''
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        body{margin:0;font-family:system-ui,sans-serif;background:#0f172a}
        .toolbar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;color:#f8fafc;border-bottom:1px solid #1e293b}
        .toolbar h1{margin:0;font-size:14px;font-weight:600}
        .actions button{border:none;border-radius:8px;padding:8px 14px;font-weight:600;cursor:pointer}
        .btn-print{background:#059669;color:#fff}
        .btn-close{background:#334155;color:#e2e8f0;margin-left:8px}
        iframe{width:100%;height:calc(100vh - 52px);border:0;background:#525659}
      </style></head><body>
      <div class="toolbar">
        <h1>${title}${periodLabel ? ' · ' + periodLabel : ''}</h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus();document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}"></iframe>
    </body></html>`)
    win.document.close()
    flash('Preview PDF dibuka')
  } catch (e) {
    let msg = e.message || 'Gagal menyiapkan laporan'
    if (e.response?.data instanceof Blob) {
      try {
        const parsed = JSON.parse(await e.response.data.text())
        msg = parsed.message || msg
      } catch { /* ignore */ }
    } else if (e.response?.data?.message) {
      msg = e.response.data.message
    }
    flash(msg, true)
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  reportWeekStart.value = mondayOf(activeDate.value)
  reportMonth.value = currentMonthKey()
  await loadHub()
  await applyDeepLink()
})
</script>

<style scoped>
.piket-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  box-sizing: border-box;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 0.75rem;
}
.toolbar-hint {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
  line-height: 1.45;
  max-width: 520px;
}
.toolbar-hint strong { color: #0f172a; }
.header-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  align-items: flex-end;
}
.date-field {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.tab-shell {
  display: grid;
  grid-template-columns: 200px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
  margin-bottom: 1rem;
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
.sec-text { display: flex; flex-direction: column; gap: 1px; min-width: 0; flex: 1; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; }
.sec-hint { font-size: 11px; font-weight: 500; color: #94a3b8; }
.sec-btn.active .sec-hint { color: #059669; }
.sec-badge { background: #f97316; color: #fff; border-radius: 999px; font-size: 0.7rem; padding: 0.1rem 0.4rem; font-weight: 700; }
.tab-main { min-width: 0; padding: 14px 16px 16px; }
@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
  .sec-hint { display: none; }
}
.tab-description {
  margin: 0.75rem 0 0;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #475569;
  background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 10px;
  border-left: 4px solid #059669;
  line-height: 1.5;
}

.page-main {
  padding: 0;
}

.stat-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.stat-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  min-width: 0;
}
.stat-warn { border-color: #fde68a; background: #fffbeb; }
.stat-alert { border-color: #fecaca; background: #fef2f2; }
.stat-info { border-color: #bfdbfe; background: #eff6ff; }
.stat-accent { border-color: #a7f3d0; background: #ecfdf5; }
.stat-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.stat-value {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.1;
}

.on-duty-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: #ecfdf5;
  border: 1px solid #6ee7b7;
  color: #047857;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  font-weight: 600;
  font-size: 0.9rem;
}

.panel-block { margin-top: 0.25rem; }
.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}
.panel-head-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}
.panel-head h3,
.settings-card h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}
.panel-sub {
  margin: 0.2rem 0 0;
  font-size: 0.8rem;
  color: #64748b;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-bottom: 1rem;
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  align-items: flex-end;
}
.filters-spacer { flex: 1; min-width: 0.5rem; }
.schedule-summary {
  align-self: center;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 500;
}
.filter-select,
.form-input,
.form-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  color: #334155;
}
.filter-select:focus,
.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.filter-date-label {
  display: inline-flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.75rem;
  color: #64748b;
}

.btn-primary,
.btn-secondary {
  padding: 0.55rem 1.1rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  border: 1px solid transparent;
}
.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 2px 10px rgba(5, 150, 105, 0.3);
}
.btn-primary:hover:not(:disabled) {
  opacity: 0.95;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
}
.btn-secondary {
  background: #fff;
  color: #475569;
  border-color: #e2e8f0;
}
.btn-secondary:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.95rem;
  font-size: 0.85rem;
}
.link-btn { text-decoration: none; }
.btn-primary:disabled,
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-sm {
  padding: 0.35rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 7px;
  cursor: pointer;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
}
.btn-sm.btn-primary {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
  box-shadow: none;
}
.btn-sm.btn-success {
  background: #ecfdf5;
  border-color: #6ee7b7;
  color: #047857;
}
.btn-sm.btn-danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}

.action-buttons {
  display: flex;
  gap: 0.4rem;
  align-items: center;
}
.action-buttons-wrap { flex-wrap: wrap; }
.action-icons {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  align-items: center;
}
.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.btn-icon:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}
.btn-icon-primary {
  color: #1d4ed8;
  border-color: #bfdbfe;
  background: #eff6ff;
}
.btn-icon-primary:hover {
  background: #dbeafe;
  color: #1e40af;
}
.btn-icon-success {
  color: #047857;
  border-color: #a7f3d0;
  background: #ecfdf5;
}
.btn-icon-success:hover {
  background: #d1fae5;
  color: #065f46;
}
.btn-icon-danger {
  color: #b91c1c;
  border-color: #fecaca;
  background: #fef2f2;
}
.btn-icon-danger:hover {
  background: #fee2e2;
  color: #991b1b;
}
.col-no {
  width: 3rem;
  text-align: center !important;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}
.col-aksi {
  width: 1%;
  white-space: nowrap;
}
.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.85rem;
  padding: 0.65rem 0.25rem 0;
}
.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}
.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.btn-page {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  font-size: 0.85rem;
  cursor: pointer;
}
.btn-page:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.btn-page:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.page-num {
  font-size: 0.85rem;
  color: #475569;
  font-weight: 500;
}
.btn-action {
  padding: 0.35rem 0.55rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-edit { background: #dbeafe; color: #1d4ed8; }
.btn-delete { background: #fee2e2; color: #b91c1c; }

.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.data-table th,
.data-table td {
  padding: 0.85rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: top;
}
.data-table th {
  background: #f8fafc;
  font-weight: 600;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.data-table tbody tr:hover { background: #f8fafc; }
.data-table tbody tr:last-child td { border-bottom: none; }
.schedule-group-start:not(:first-child) td {
  border-top: 2px solid #e2e8f0;
}
.schedule-day-cell {
  width: 150px;
  min-width: 130px;
  vertical-align: top !important;
  background: #f8fafc;
  border-right: 1px solid #e2e8f0;
}
.schedule-day-name {
  display: block;
  color: #0f172a;
  font-size: 0.95rem;
  font-weight: 700;
}
.schedule-day-count {
  display: block;
  margin-top: 0.2rem;
  color: #64748b;
  font-size: 0.75rem;
}
.btn-add-day {
  margin-top: 0.65rem;
  padding: 0;
  border: 0;
  background: transparent;
  color: #059669;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 600;
}
.btn-add-day:hover { color: #047857; text-decoration: underline; }
.cell-name {
  display: block;
  font-weight: 600;
  color: #0f172a;
}
.cell-meta,
.muted {
  display: block;
  color: #94a3b8;
  font-size: 0.78rem;
  margin-top: 0.15rem;
}
.summary-cell { min-width: 180px; white-space: normal; }

.shift-chip,
.type-chip {
  display: inline-flex;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
}
.type-kelas_kosong { background: #fffbeb; color: #b45309; }
.type-terlambat_guru { background: #fef2f2; color: #b91c1c; }
.type-terlambat_siswa { background: #eff6ff; color: #1d4ed8; }

.status-badge {
  display: inline-flex;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}
.status-badge.draft { background: #f1f5f9; color: #475569; }
.status-badge.submitted,
.status-badge.confirmed { background: #fffbeb; color: #b45309; }
.status-badge.reviewed,
.status-badge.resolved { background: #ecfdf5; color: #047857; }
.status-badge.open { background: #eff6ff; color: #1d4ed8; }
.status-badge.dismissed { background: #f1f5f9; color: #64748b; }

.propose-badge {
  margin-top: 0.35rem;
  font-weight: 600;
  font-size: 0.78rem;
}
.propose-badge.v-pending { color: #b45309; }
.propose-badge.v-dicatat,
.propose-badge.v-sanksi_diberikan,
.propose-badge.v-follow_up,
.propose-badge.v-selesai { color: #047857; }
.propose-badge.v-ditolak { color: #b91c1c; }
.propose-badge.tv-pending { color: #b45309; }
.propose-badge.tv-approved { color: #047857; }
.propose-badge.tv-rejected { color: #b91c1c; }

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  color: #64748b;
}
.loading-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  text-align: center;
  padding: 2.5rem 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
}
.empty-state-sm { padding: 2rem 1rem; }
.empty-icon {
  color: #94a3b8;
  margin-bottom: 0.75rem;
  display: flex;
  justify-content: center;
}
.empty-title {
  margin: 0 0 0.4rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}
.empty-desc {
  margin: 0 auto 1rem;
  font-size: 0.875rem;
  color: #64748b;
  max-width: 420px;
  line-height: 1.45;
}
.empty-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  flex-wrap: wrap;
}
.btn-empty-cta { margin-top: 0.25rem; }

.report-section { margin-top: 0.25rem; }
.report-form.card-form {
  padding: 1.25rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
}
.report-form-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.report-panel-title {
  margin: 0 0 0.25rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}
.report-panel-desc {
  margin: 0;
  font-size: 0.8125rem;
  color: #64748b;
  line-height: 1.45;
  max-width: 42rem;
}
.report-export-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  align-items: center;
}
.report-export-actions .btn-export-pdf {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.875rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  border: 1px solid #059669;
  background: #eff6ff;
  color: #059669;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}
.report-export-actions .btn-export-pdf:hover:not(:disabled) {
  background: #059669;
  color: #fff;
}
.report-export-actions .btn-export-pdf:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.report-form .report-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
  margin: 0;
  padding: 0;
  background: transparent;
  border: none;
}
.report-form .report-filters .form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin: 0;
}
.report-form .report-filters .form-group label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}
.report-form .report-filters .form-group input {
  min-width: 160px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-size: 0.875rem;
  color: #334155;
}
.report-form .report-filters .form-group input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.report-type-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.report-type-tabs .tab {
  padding: 0.45rem 0.9rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}
.report-type-tabs .tab.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
}
.report-toolbar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 0.5rem 1rem;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.65rem 0.85rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.85rem;
}
.report-period { font-weight: 600; color: #0f172a; }
.report-count { color: #64748b; }
.report-summary-cards { margin-bottom: 1.25rem; }
.report-block { margin-top: 1.25rem; }
.loading-wrap {
  padding: 1.5rem;
  text-align: center;
  color: #64748b;
}
.btn-search {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.mini-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
.report-empty {
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
}

.settings-card {
  max-width: 520px;
}
.settings-hint {
  margin: 0.35rem 0 1.15rem;
  font-size: 0.85rem;
  color: #64748b;
}
.field-hint {
  margin: 0.35rem 0 0;
  font-size: 0.78rem;
  color: #64748b;
  line-height: 1.4;
}
.student-picker {
  margin-bottom: 0.5rem;
  padding: 0.85rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.student-picker .form-select[size] {
  min-height: 140px;
}
.form-group label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #475569;
}
.form-input,
.form-select {
  display: block;
  width: 100%;
  box-sizing: border-box;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.check-inline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #334155;
  cursor: pointer;
}
.check-inline input { width: auto; accent-color: #059669; }
.settings-actions { margin-top: 0.5rem; }

.error-banner,
.success-banner {
  padding: 0.75rem 1rem;
  border-radius: 10px;
  margin-bottom: 0.85rem;
  font-size: 0.875rem;
}
.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.success-banner {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #bbf7d0;
}

.stat-card-btn {
  cursor: pointer;
  text-align: left;
  font: inherit;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.stat-card-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.stat-card-btn:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.modal-content-wide {
  width: min(580px, 100%);
}
.modal-step-hint {
  margin: 0.25rem 0 0;
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 400;
}
.type-picker-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
}
.type-picker-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  padding: 0.9rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
  text-align: left;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}
.type-picker-card strong {
  font-size: 0.92rem;
  color: #0f172a;
}
.type-picker-card span {
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.35;
}
.type-picker-card:hover {
  border-color: #86efac;
  background: #f0fdf4;
}
.type-picker-card.active {
  border-color: #059669;
  background: #ecfdf5;
  box-shadow: 0 0 0 1px #059669;
}
.selected-type-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: 10px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #047857;
}
.btn-link {
  background: none;
  border: none;
  color: #059669;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
}

@media (max-width: 560px) {
  .type-picker-grid {
    grid-template-columns: 1fr;
  }
}

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
.modal-content {
  width: min(520px, 100%);
  background: #fff;
  border-radius: 14px;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 { margin: 0; font-size: 1.1rem; color: #0f172a; }
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  line-height: 1;
}
.modal-body { padding: 1.25rem; }
.modal-hint { margin: 0 0 1rem; }
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #e2e8f0;
}
.target-card {
  margin-bottom: 1rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
.target-card strong {
  display: block;
  color: #0f172a;
  margin-bottom: 0.15rem;
}

.day-picker { margin-bottom: 1rem; }
.day-picker-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
  color: #475569;
  font-weight: 500;
  margin-bottom: 0.25rem;
}
.day-picker-hint { margin: 0 0 0.65rem; }
.btn-link {
  border: none;
  background: none;
  color: #059669;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.day-checkboxes {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
}
.day-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  padding: 0.55rem 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #334155;
  background: #f8fafc;
  cursor: pointer;
}
.day-check input { width: auto; margin: 0; accent-color: #059669; }
.day-check:has(input:checked) {
  border-color: #6ee7b7;
  background: #ecfdf5;
  color: #047857;
  font-weight: 600;
}

@media (max-width: 720px) {
  .piket-page {
    padding: 0;
  }
  .page-main { padding: 0; }
  .form-row { grid-template-columns: 1fr; }
  .report-form-top { flex-direction: column; }
  .day-checkboxes { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .filters-spacer { display: none; }
  .stat-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .header-actions { width: 100%; }
  .header-actions .btn-compact { flex: 1 1 auto; justify-content: center; }
  .date-field { flex: 1 1 140px; }
  .date-field .filter-select { width: 100%; }
}
</style>
