<template>    <div class="kepegawaian-page">
      <header class="page-header">
        <div class="header-text">
          <h1 class="page-title">Cuti, SK &amp; Jabatan</h1>
          <p class="page-subtitle">Arsip resmi kepegawaian: pengajuan cuti, surat keputusan, dan periode jabatan struktural.</p>
        </div>
        <button
          v-if="tab !== 'riwayat'"
          type="button"
          class="btn-primary btn-header"
          @click="openCurrentAction"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          {{ currentActionLabel }}
        </button>
      </header>

      <div class="stat-grid">
        <button type="button" class="stat-card" :class="{ active: tab === 'cuti' }" @click="switchTab('cuti')">
          <span class="stat-label">Cuti menunggu</span>
          <span class="stat-value">{{ summaries.pendingLeaves }}</span>
        </button>
        <button type="button" class="stat-card" :class="{ active: tab === 'sk' }" @click="switchTab('sk')">
          <span class="stat-label">SK tercatat</span>
          <span class="stat-value">{{ summaries.decrees }}</span>
        </button>
        <button type="button" class="stat-card" :class="{ active: tab === 'jabatan' }" @click="switchTab('jabatan')">
          <span class="stat-label">Jabatan aktif</span>
          <span class="stat-value">{{ summaries.activePositions }}</span>
        </button>
      </div>

      <div class="tab-shell">
      <nav class="section-nav" role="tablist">
        <button
          v-for="item in tabs"
          :key="item.id"
          type="button"
          role="tab"
          :class="['sec-btn', { active: tab === item.id }]"
          :aria-selected="tab === item.id"
          @click="switchTab(item.id)"
        >
          <span class="sec-icon" aria-hidden="true">
            <svg v-if="item.id === 'cuti'" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <svg v-else-if="item.id === 'sk'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
            <svg v-else-if="item.id === 'jabatan'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
          <span class="sec-label">{{ item.label }}</span>
        </button>
      </nav>
      <div class="tab-main">

      <!-- CUTI -->
      <template v-if="tab === 'cuti'">
        <div class="filters filters-inline">
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input v-model="leaveFilters.search" type="search" class="search-input" placeholder="Cari nama / NIP..." @input="debounceLoadLeaves" />
          </div>
          <select v-model="leaveFilters.status" class="filter-select" @change="loadLeaves(1)">
            <option value="">Semua status</option>
            <option v-for="(label, key) in leaveStatuses" :key="key" :value="key">{{ label }}</option>
          </select>
          <select v-model="leaveFilters.leave_type" class="filter-select" @change="loadLeaves(1)">
            <option value="">Semua jenis</option>
            <option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>

        <div v-if="loadingLeaves" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
        <div v-else-if="!leaves.length" class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
              <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M3 10H21M8 3V7M16 3V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada pengajuan cuti</h3>
          <p class="empty-desc">Catat cuti pegawai atau setujui pengajuan dari menu Cuti Saya.</p>
          <button type="button" class="btn-primary" @click="openLeaveModal()">Catat Cuti</button>
        </div>
        <template v-else>
          <div class="table-card table-desktop">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Pegawai</th>
                  <th>Jenis</th>
                  <th>Periode</th>
                  <th>Hari</th>
                  <th>Status</th>
                  <th class="col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in leaves" :key="item.id">
                  <td>
                    <div class="person-cell">
                      <span class="avatar">{{ initials(item.employee?.name) }}</span>
                      <div>
                        <div class="cell-title">{{ item.employee?.name }}</div>
                        <div class="cell-sub">{{ item.employee?.nip || item.employee?.nuptk || item.employee?.type || '—' }}</div>
                      </div>
                    </div>
                  </td>
                  <td><span class="type-chip">{{ item.leave_type_label }}</span></td>
                  <td>
                    <div class="cell-title">{{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</div>
                    <div v-if="item.reason" class="cell-sub">{{ item.reason }}</div>
                  </td>
                  <td>{{ item.duration_days }}</td>
                  <td><span :class="['status-badge', `status-${item.status}`]">{{ item.status_label }}</span></td>
                  <td class="col-actions">
                    <div class="actions">
                      <template v-if="item.status === 'pending'">
                        <TableAction kind="approve" @click="decideLeave(item, 'approve')" />
                        <TableAction kind="reject" @click="openRejectModal(item)" />
                      </template>
                      <TableAction
                        v-if="item.status === 'pending' || item.status === 'approved'"
                        kind="cancel"
                        @click="askCancelLeave(item)"
                      />
                      <span v-else class="muted">—</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mobile-cards">
            <article v-for="item in leaves" :key="'m-leave-' + item.id" class="item-card">
              <div class="item-card-top">
                <div class="person-cell">
                  <span class="avatar">{{ initials(item.employee?.name) }}</span>
                  <div>
                    <div class="cell-title">{{ item.employee?.name }}</div>
                    <div class="cell-sub">{{ item.leave_type_label }} · {{ item.duration_days }} hari</div>
                  </div>
                </div>
                <span :class="['status-badge', `status-${item.status}`]">{{ item.status_label }}</span>
              </div>
              <p class="item-card-meta">{{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</p>
              <div class="actions">
                <template v-if="item.status === 'pending'">
                  <TableAction kind="approve" @click="decideLeave(item, 'approve')" />
                  <TableAction kind="reject" @click="openRejectModal(item)" />
                </template>
                <TableAction
                  v-if="item.status === 'pending' || item.status === 'approved'"
                  kind="cancel"
                  @click="askCancelLeave(item)"
                />
              </div>
            </article>
          </div>
          <PaginationBar
            :page="leavePage.current_page"
            :last-page="leavePage.last_page"
            :per-page="leavePage.per_page"
            :total="leavePage.total"
            item-label="cuti"
            @page-change="loadLeaves"
            @per-page-change="changeLeavePerPage"
          />
        </template>
      </template>

      <!-- SK -->
      <template v-if="tab === 'sk'">
        <div class="filters filters-inline">
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input v-model="decreeFilters.search" type="search" class="search-input" placeholder="Cari nomor / judul / nama..." @input="debounceLoadDecrees" />
          </div>
          <select v-model="decreeFilters.decree_type" class="filter-select" @change="loadDecrees(1)">
            <option value="">Semua jenis SK</option>
            <option v-for="(label, key) in decreeTypes" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>

        <div v-if="loadingDecrees" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="5" /></div>
        <div v-else-if="!decrees.length" class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-width="1.5"/>
              <path d="M14 2v6h6M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada SK</h3>
          <p class="empty-desc">Catat Surat Keputusan pegawai beserta berkas PDF.</p>
          <button type="button" class="btn-primary" @click="openDecreeModal()">Tambah SK</button>
        </div>
        <template v-else>
          <div class="table-card table-desktop">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nomor / Judul</th>
                  <th>Pegawai</th>
                  <th>Jenis</th>
                  <th>Tanggal</th>
                  <th class="col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in decrees" :key="item.id">
                  <td>
                    <div class="cell-title">{{ item.number }}</div>
                    <div class="cell-sub">{{ item.title }}</div>
                  </td>
                  <td>
                    <div class="person-cell">
                      <span class="avatar avatar-sm">{{ initials(item.employee?.name) }}</span>
                      <span>{{ item.employee?.name }}</span>
                    </div>
                  </td>
                  <td><span class="type-chip">{{ item.decree_type_label }}</span></td>
                  <td>{{ formatDate(item.decree_date) }}</td>
                  <td class="col-actions">
                    <div class="actions">
                      <TableAction v-if="item.file_url" kind="download" @click="downloadDecree(item)" />
                      <TableAction kind="edit" @click="openDecreeModal(item)" />
                      <TableAction kind="delete" @click="askDeleteDecree(item)" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mobile-cards">
            <article v-for="item in decrees" :key="'m-sk-' + item.id" class="item-card">
              <div class="item-card-top">
                <div>
                  <div class="cell-title">{{ item.number }}</div>
                  <div class="cell-sub">{{ item.title }}</div>
                </div>
                <span class="type-chip">{{ item.decree_type_label }}</span>
              </div>
              <p class="item-card-meta">{{ item.employee?.name }} · {{ formatDate(item.decree_date) }}</p>
              <div class="actions">
                <TableAction v-if="item.file_url" kind="download" @click="downloadDecree(item)" />
                <TableAction kind="edit" @click="openDecreeModal(item)" />
                <TableAction kind="delete" @click="askDeleteDecree(item)" />
              </div>
            </article>
          </div>
          <PaginationBar
            :page="decreePage.current_page"
            :last-page="decreePage.last_page"
            :per-page="decreePage.per_page"
            :total="decreePage.total"
            item-label="SK"
            @page-change="loadDecrees"
            @per-page-change="changeDecreePerPage"
          />
        </template>
      </template>

      <!-- JABATAN -->
      <template v-if="tab === 'jabatan'">
        <p class="hint-banner">
          Menetapkan jabatan di sini juga mengisi tugas tambahan dan akses modul. Jangan dicentang lagi di Data Pegawai.
        </p>
        <div class="filters filters-inline">
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input v-model="positionFilters.search" type="search" class="search-input" placeholder="Cari nama pegawai..." @input="debounceLoadPositions" />
          </div>
          <select v-model="positionFilters.structural_position_id" class="filter-select" @change="loadPositions(1)">
            <option value="">Semua jabatan</option>
            <option v-for="p in positionMaster" :key="p.id" :value="p.id">{{ p.label }}</option>
          </select>
          <label class="check-inline">
            <input v-model="positionFilters.active_only" type="checkbox" @change="loadPositions(1)" />
            Aktif saja
          </label>
        </div>

        <div v-if="loadingPositions" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="5" /></div>
        <div v-else-if="!positions.length" class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
              <path d="M12 12a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="1.5"/>
              <path d="M4 20a8 8 0 0116 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Belum ada jabatan struktural</h3>
          <p class="empty-desc">Tetapkan pejabat struktural sekolah beserta periode menjabat.</p>
          <button type="button" class="btn-primary" @click="openPositionModal()">Tetapkan Jabatan</button>
        </div>
        <template v-else>
          <div class="table-card table-desktop">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Jabatan</th>
                  <th>Pegawai</th>
                  <th>Periode</th>
                  <th>SK</th>
                  <th class="col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in positions" :key="item.id">
                  <td class="cell-title">{{ item.position?.label }}</td>
                  <td>
                    <div class="person-cell">
                      <span class="avatar avatar-sm">{{ initials(item.employee?.name) }}</span>
                      <span>{{ item.employee?.name }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="cell-title">
                      {{ formatDate(item.started_at) }} – {{ item.ended_at ? formatDate(item.ended_at) : 'Sekarang' }}
                    </div>
                    <span v-if="item.is_active" class="status-badge status-approved">Aktif</span>
                    <span v-else class="status-badge status-cancelled">Selesai</span>
                  </td>
                  <td>{{ item.decree_number || item.decree?.number || '—' }}</td>
                  <td class="col-actions">
                    <button v-if="item.is_active" type="button" class="btn-sm btn-ghost" @click="openEndPositionModal(item)">Akhiri</button>
                    <span v-else class="muted">Selesai</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mobile-cards">
            <article v-for="item in positions" :key="'m-pos-' + item.id" class="item-card">
              <div class="item-card-top">
                <div>
                  <div class="cell-title">{{ item.position?.label }}</div>
                  <div class="cell-sub">{{ item.employee?.name }}</div>
                </div>
                <span :class="['status-badge', item.is_active ? 'status-approved' : 'status-cancelled']">
                  {{ item.is_active ? 'Aktif' : 'Selesai' }}
                </span>
              </div>
              <p class="item-card-meta">
                {{ formatDate(item.started_at) }} – {{ item.ended_at ? formatDate(item.ended_at) : 'Sekarang' }}
                <template v-if="item.decree_number || item.decree?.number"> · SK {{ item.decree_number || item.decree?.number }}</template>
              </p>
              <button v-if="item.is_active" type="button" class="btn-sm btn-ghost" @click="openEndPositionModal(item)">Akhiri</button>
            </article>
          </div>
          <PaginationBar
            :page="positionPage.current_page"
            :last-page="positionPage.last_page"
            :per-page="positionPage.per_page"
            :total="positionPage.total"
            item-label="jabatan"
            @page-change="loadPositions"
            @per-page-change="changePositionPerPage"
          />
        </template>
      </template>

      <!-- RIWAYAT -->
      <template v-if="tab === 'riwayat'">
        <div class="filters filters-inline">
          <select v-model="historyEmployeeId" class="filter-select filter-select-wide" @change="loadHistory">
            <option value="">Pilih pegawai...</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}{{ e.nip ? ` (${e.nip})` : '' }}</option>
          </select>
        </div>
        <div v-if="!historyEmployeeId" class="empty-state">
          <div class="empty-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/>
              <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </div>
          <h3 class="empty-title">Pilih pegawai</h3>
          <p class="empty-desc">Timeline cuti, SK, jabatan struktural, dan mutasi akan tampil di sini.</p>
        </div>
        <div v-else-if="loadingHistory" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="3" /></div>
        <div v-else>
          <div v-if="historyEmployee" class="history-header">
            <div class="history-title-row">
              <span class="avatar avatar-lg">{{ initials(historyEmployee.name) }}</span>
              <h3>{{ historyEmployee.name }}</h3>
            </div>
            <div class="meta-grid">
              <div class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">NIP</span>
                  <span class="meta-value">{{ historyEmployee.nip || historyEmployee.nuptk || '—' }}</span>
                </div>
              </div>
              <div class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Jenis</span>
                  <span class="meta-value">{{ historyEmployee.type || '—' }}</span>
                </div>
              </div>
              <div class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18" stroke="currentColor" stroke-width="2"/></svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Kepegawaian</span>
                  <span class="meta-value">{{ historyEmployee.employment_status || '—' }}</span>
                </div>
              </div>
              <div class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Status</span>
                  <span class="meta-value">{{ historyEmployee.status || '—' }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!timeline.length" class="empty-state">
            <h3 class="empty-title">Belum ada riwayat</h3>
            <p class="empty-desc">Cuti, SK, jabatan, atau mutasi pegawai ini belum tercatat.</p>
          </div>
          <ol v-else class="timeline">
            <li
              v-for="(event, idx) in timeline"
              :key="`${event.type}-${event.ref_id}-${idx}`"
              class="timeline-item"
              :class="`type-${event.type}`"
            >
              <span class="timeline-dot" aria-hidden="true"></span>
              <div class="timeline-date">
                {{ formatDate(event.date) }}
                <span v-if="event.end_date"> – {{ formatDate(event.end_date) }}</span>
              </div>
              <div class="timeline-body">
                <span class="timeline-type">{{ event.type_label }}</span>
                <div class="cell-title">{{ event.title }}</div>
                <div v-if="event.subtitle" class="cell-sub">{{ event.subtitle }}</div>
              </div>
            </li>
          </ol>
        </div>
      </template>

      </div>
      </div>

      <!-- Leave Modal -->
      <div v-if="showLeaveModal" class="modal-overlay" @click.self="showLeaveModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Catat / Ajukan Cuti</h3>
            <button type="button" class="btn-close" @click="showLeaveModal = false" aria-label="Tutup">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitLeave">
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="leaveForm.employee_id" required>
                <option value="">Pilih pegawai...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jenis *</label>
                <select v-model="leaveForm.leave_type" required>
                  <option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status awal</label>
                <select v-model="leaveForm.status">
                  <option value="pending">Menunggu persetujuan</option>
                  <option value="approved">Langsung disetujui</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai *</label>
                <input v-model="leaveForm.start_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Selesai *</label>
                <input v-model="leaveForm.end_date" type="date" required />
              </div>
            </div>
            <p v-if="leaveDurationDays" class="form-hint">Durasi: {{ leaveDurationDays }} hari kalender.</p>
            <div class="form-group">
              <label>Alasan</label>
              <textarea v-model="leaveForm.reason" rows="3" placeholder="Alasan pengajuan (opsional)"></textarea>
            </div>
            <div class="form-group">
              <label>Lampiran PDF</label>
              <label class="file-picker">
                <input type="file" accept="application/pdf" @change="onLeaveFile" />
                <span class="file-picker-btn">Pilih berkas</span>
                <span class="file-name">{{ leaveForm.attachment?.name || 'Belum ada berkas' }}</span>
              </label>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showLeaveModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Decree Modal -->
      <div v-if="showDecreeModal" class="modal-overlay" @click.self="showDecreeModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ decreeForm.id ? 'Edit SK' : 'Tambah SK' }}</h3>
            <button type="button" class="btn-close" @click="showDecreeModal = false" aria-label="Tutup">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitDecree">
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="decreeForm.employee_id" required>
                <option value="">Pilih pegawai...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jenis SK *</label>
                <select v-model="decreeForm.decree_type" required>
                  <option v-for="(label, key) in decreeTypes" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Nomor SK *</label>
                <input v-model="decreeForm.number" type="text" required placeholder="Contoh: 123/SK/2026" />
              </div>
            </div>
            <div class="form-group">
              <label>Judul *</label>
              <input v-model="decreeForm.title" type="text" required placeholder="Judul surat keputusan" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal SK *</label>
                <input v-model="decreeForm.decree_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Berlaku</label>
                <input v-model="decreeForm.effective_date" type="date" />
              </div>
              <div class="form-group">
                <label>Berakhir</label>
                <input v-model="decreeForm.end_date" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="decreeForm.description" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>File PDF</label>
              <label class="file-picker">
                <input type="file" accept="application/pdf" @change="onDecreeFile" />
                <span class="file-picker-btn">Pilih berkas</span>
                <span class="file-name">{{ decreeForm.file?.name || (decreeForm.id ? 'Biarkan kosong jika tidak diganti' : 'Belum ada berkas') }}</span>
              </label>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showDecreeModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Position Modal -->
      <div v-if="showPositionModal" class="modal-overlay" @click.self="showPositionModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tetapkan Jabatan Struktural</h3>
            <button type="button" class="btn-close" @click="showPositionModal = false" aria-label="Tutup">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitPosition">
            <p class="form-hint">Pemegang jabatan yang sama sebelumnya akan diakhiri otomatis. Tugas tambahan dan akses modul ikut berubah.</p>
            <div class="form-group">
              <label>Pegawai *</label>
              <select v-model="positionForm.employee_id" required @change="loadEmployeeDecrees">
                <option value="">Pilih pegawai...</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Jabatan *</label>
              <select v-model="positionForm.structural_position_id" required>
                <option value="">Pilih jabatan...</option>
                <option v-for="p in positionMaster" :key="p.id" :value="p.id">{{ p.label }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai menjabat *</label>
                <input v-model="positionForm.started_at" type="date" required />
              </div>
              <div class="form-group">
                <label>Berakhir (opsional)</label>
                <input v-model="positionForm.ended_at" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>SK terkait</label>
              <select v-model="positionForm.employee_decree_id">
                <option value="">— Tidak ada —</option>
                <option v-for="d in employeeDecrees" :key="d.id" :value="d.id">{{ d.number }} — {{ d.title }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nomor SK (teks)</label>
              <input v-model="positionForm.decree_number" type="text" placeholder="Jika belum terdaftar di modul SK" />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="positionForm.notes" rows="2"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showPositionModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reject leave -->
      <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
        <div class="modal-content modal-sm" @click.stop>
          <div class="modal-header">
            <h3>Tolak cuti</h3>
            <button type="button" class="btn-close" @click="showRejectModal = false" aria-label="Tutup">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitReject">
            <p class="form-hint">{{ rejectTarget?.employee?.name }} · {{ rejectTarget?.leave_type_label }}</p>
            <div class="form-group">
              <label>Alasan penolakan *</label>
              <textarea v-model="rejectReason" rows="3" required placeholder="Tuliskan alasan penolakan"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showRejectModal = false">Batal</button>
              <button type="submit" class="btn-danger" :disabled="saving">{{ saving ? 'Memproses...' : 'Tolak' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- End position -->
      <div v-if="showEndModal" class="modal-overlay" @click.self="showEndModal = false">
        <div class="modal-content modal-sm" @click.stop>
          <div class="modal-header">
            <h3>Akhiri jabatan</h3>
            <button type="button" class="btn-close" @click="showEndModal = false" aria-label="Tutup">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitEndPosition">
            <p class="form-hint">{{ endTarget?.position?.label }} · {{ endTarget?.employee?.name }}</p>
            <div class="form-group">
              <label>Tanggal berakhir *</label>
              <input v-model="endDate" type="date" required />
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showEndModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Akhiri' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="confirm.show"
        :title="confirm.title"
        :message="confirm.message"
        :warning="confirm.warning"
        :confirm-text="confirm.confirmText"
        :confirm-variant="confirm.variant"
        :loading="confirm.loading"
        @confirm="runConfirm"
        @cancel="confirm.show = false"
      />
    </div></template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { employeeApi } from '@/api/teacher'
import {
  employeeLeaveApi,
  employeeDecreeApi,
  structuralPositionApi,
  careerHistoryApi,
} from '@/api/kepegawaian'

const toast = useToast()

const tabs = [
  { id: 'cuti', label: 'Cuti' },
  { id: 'sk', label: 'SK' },
  { id: 'jabatan', label: 'Jabatan' },
  { id: 'riwayat', label: 'Riwayat' },
]

const tab = ref('cuti')
const saving = ref(false)
const formError = ref('')

const employees = ref([])
const leaveTypes = ref({})
const leaveStatuses = ref({})
const decreeTypes = ref({})
const positionMaster = ref([])

const leaves = ref([])
const decrees = ref([])
const positions = ref([])
const timeline = ref([])
const historyEmployee = ref(null)
const historyEmployeeId = ref('')
const employeeDecrees = ref([])

const loadingLeaves = ref(false)
const loadingDecrees = ref(false)
const loadingPositions = ref(false)
const loadingHistory = ref(false)

const leaveFilters = reactive({ search: '', status: 'pending', leave_type: '' })
const decreeFilters = reactive({ search: '', decree_type: '' })
const positionFilters = reactive({ search: '', structural_position_id: '', active_only: true })

const leavePage = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const decreePage = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const positionPage = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const summaries = reactive({ pendingLeaves: 0, decrees: 0, activePositions: 0 })

const showLeaveModal = ref(false)
const showDecreeModal = ref(false)
const showPositionModal = ref(false)
const showRejectModal = ref(false)
const showEndModal = ref(false)

const rejectTarget = ref(null)
const rejectReason = ref('')
const endTarget = ref(null)
const endDate = ref('')

const confirm = reactive({
  show: false,
  title: '',
  message: '',
  warning: '',
  confirmText: 'Ya',
  variant: 'danger',
  loading: false,
  action: null,
})

const leaveForm = reactive({
  employee_id: '',
  leave_type: 'tahunan',
  start_date: '',
  end_date: '',
  reason: '',
  status: 'pending',
  attachment: null,
})

const decreeForm = reactive({
  id: null,
  employee_id: '',
  decree_type: 'jabatan',
  number: '',
  title: '',
  decree_date: '',
  effective_date: '',
  end_date: '',
  description: '',
  file: null,
})

const positionForm = reactive({
  employee_id: '',
  structural_position_id: '',
  employee_decree_id: '',
  started_at: '',
  ended_at: '',
  decree_number: '',
  notes: '',
})

const currentActionLabel = computed(() => {
  if (tab.value === 'sk') return 'Tambah SK'
  if (tab.value === 'jabatan') return 'Tetapkan Jabatan'
  return 'Catat Cuti'
})

const leaveDurationDays = computed(() => {
  if (!leaveForm.start_date || !leaveForm.end_date) return 0
  const start = new Date(leaveForm.start_date)
  const end = new Date(leaveForm.end_date)
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 0
  return Math.round((end - start) / 86400000) + 1
})

let leaveTimer = null
let decreeTimer = null
let positionTimer = null

function formatDate(value) {
  if (!value) return '—'
  try {
    return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return value
  }
}

function initials(name) {
  if (!name) return '?'
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function pageRange(p) {
  if (!p?.total) return '0 data'
  const from = (p.current_page - 1) * p.per_page + 1
  const to = Math.min(p.current_page * p.per_page, p.total)
  return `${from}–${to} dari ${p.total}`
}

function applyMeta(target, payload) {
  const meta = payload?.meta || {}
  target.current_page = meta.current_page ?? 1
  target.last_page = meta.last_page ?? 1
  target.per_page = meta.per_page ?? target.per_page
  target.total = meta.total ?? (payload?.data || []).length
}

function switchTab(next) {
  tab.value = next
  if (next === 'cuti') loadLeaves(1)
  if (next === 'sk') loadDecrees(1)
  if (next === 'jabatan') loadPositions(1)
  if (next === 'riwayat' && historyEmployeeId.value) loadHistory()
}

function openCurrentAction() {
  if (tab.value === 'sk') openDecreeModal()
  else if (tab.value === 'jabatan') openPositionModal()
  else openLeaveModal()
}

async function loadEmployees() {
  try {
    const { data } = await employeeApi.getAll({ per_page: 200, status: 'Aktif' })
    employees.value = data.data || data || []
  } catch {
    employees.value = []
  }
}

async function loadMeta() {
  try {
    const [leaveMeta, decreeMeta, positionsRes] = await Promise.all([
      employeeLeaveApi.meta(),
      employeeDecreeApi.meta(),
      structuralPositionApi.listMaster(),
    ])
    leaveTypes.value = leaveMeta.data.leave_types || {}
    leaveStatuses.value = leaveMeta.data.statuses || {}
    decreeTypes.value = decreeMeta.data.decree_types || {}
    positionMaster.value = positionsRes.data.data || []
  } catch {
    toast.error('Gagal memuat referensi kepegawaian')
  }
}

async function loadSummaries() {
  try {
    const [leaveRes, decreeRes, posRes] = await Promise.all([
      employeeLeaveApi.getAll({ status: 'pending', per_page: 1 }),
      employeeDecreeApi.getAll({ per_page: 1 }),
      structuralPositionApi.getAll({ active_only: 1, per_page: 1 }),
    ])
    summaries.pendingLeaves = leaveRes.data.meta?.total ?? 0
    summaries.decrees = decreeRes.data.meta?.total ?? 0
    summaries.activePositions = posRes.data.meta?.total ?? 0
  } catch {
    /* ringkasan opsional */
  }
}

async function loadLeaves(page = 1) {
  loadingLeaves.value = true
  try {
    const { data } = await employeeLeaveApi.getAll({
      ...leaveFilters,
      page,
      per_page: leavePage.per_page || 15,
    })
    leaves.value = data.data || []
    applyMeta(leavePage, data)
  } catch {
    toast.error('Gagal memuat data cuti')
  } finally {
    loadingLeaves.value = false
  }
}

function changeLeavePerPage(n) {
  leavePage.per_page = n
  loadLeaves(1)
}

function debounceLoadLeaves() {
  clearTimeout(leaveTimer)
  leaveTimer = setTimeout(() => loadLeaves(1), 350)
}

async function loadDecrees(page = 1) {
  loadingDecrees.value = true
  try {
    const { data } = await employeeDecreeApi.getAll({
      ...decreeFilters,
      page,
      per_page: decreePage.per_page || 15,
    })
    decrees.value = data.data || []
    applyMeta(decreePage, data)
  } catch {
    toast.error('Gagal memuat data SK')
  } finally {
    loadingDecrees.value = false
  }
}

function changeDecreePerPage(n) {
  decreePage.per_page = n
  loadDecrees(1)
}

function debounceLoadDecrees() {
  clearTimeout(decreeTimer)
  decreeTimer = setTimeout(() => loadDecrees(1), 350)
}

async function loadPositions(page = 1) {
  loadingPositions.value = true
  try {
    const params = {
      search: positionFilters.search || undefined,
      structural_position_id: positionFilters.structural_position_id || undefined,
      active_only: positionFilters.active_only ? 1 : undefined,
      page,
      per_page: positionPage.per_page || 15,
    }
    const { data } = await structuralPositionApi.getAll(params)
    positions.value = data.data || []
    applyMeta(positionPage, data)
  } catch {
    toast.error('Gagal memuat jabatan struktural')
  } finally {
    loadingPositions.value = false
  }
}

function changePositionPerPage(n) {
  positionPage.per_page = n
  loadPositions(1)
}

function debounceLoadPositions() {
  clearTimeout(positionTimer)
  positionTimer = setTimeout(() => loadPositions(1), 350)
}

async function loadHistory() {
  if (!historyEmployeeId.value) {
    timeline.value = []
    historyEmployee.value = null
    return
  }
  loadingHistory.value = true
  try {
    const { data } = await careerHistoryApi.get(historyEmployeeId.value)
    historyEmployee.value = data.data?.employee || null
    timeline.value = data.data?.timeline || []
  } catch {
    toast.error('Gagal memuat riwayat')
    timeline.value = []
  } finally {
    loadingHistory.value = false
  }
}

function openLeaveModal() {
  formError.value = ''
  Object.assign(leaveForm, {
    employee_id: '',
    leave_type: 'tahunan',
    start_date: '',
    end_date: '',
    reason: '',
    status: 'pending',
    attachment: null,
  })
  showLeaveModal.value = true
}

function onLeaveFile(e) {
  leaveForm.attachment = e.target.files?.[0] || null
}

async function submitLeave() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    fd.append('employee_id', leaveForm.employee_id)
    fd.append('leave_type', leaveForm.leave_type)
    fd.append('start_date', leaveForm.start_date)
    fd.append('end_date', leaveForm.end_date)
    fd.append('status', leaveForm.status)
    if (leaveForm.reason) fd.append('reason', leaveForm.reason)
    if (leaveForm.attachment) fd.append('attachment', leaveForm.attachment)
    await employeeLeaveApi.create(fd)
    toast.success('Pengajuan cuti disimpan')
    showLeaveModal.value = false
    loadLeaves(1)
    loadSummaries()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

async function decideLeave(item, action) {
  try {
    await employeeLeaveApi.decide(item.id, { action })
    toast.success(action === 'approve' ? 'Cuti disetujui' : 'Cuti ditolak')
    loadLeaves(leavePage.current_page)
    loadSummaries()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memproses')
  }
}

function openRejectModal(item) {
  formError.value = ''
  rejectTarget.value = item
  rejectReason.value = ''
  showRejectModal.value = true
}

async function submitReject() {
  if (!rejectTarget.value || !rejectReason.value.trim()) return
  saving.value = true
  formError.value = ''
  try {
    await employeeLeaveApi.decide(rejectTarget.value.id, {
      action: 'reject',
      rejection_reason: rejectReason.value.trim(),
    })
    toast.success('Cuti ditolak')
    showRejectModal.value = false
    loadLeaves(leavePage.current_page)
    loadSummaries()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menolak'
  } finally {
    saving.value = false
  }
}

function askCancelLeave(item) {
  confirm.title = 'Batalkan cuti?'
  confirm.message = `Pengajuan cuti ${item.employee?.name} akan dibatalkan.`
  confirm.warning = ''
  confirm.confirmText = 'Batalkan'
  confirm.variant = 'danger'
  confirm.action = async () => {
    await employeeLeaveApi.cancel(item.id)
    toast.success('Cuti dibatalkan')
    loadLeaves(leavePage.current_page)
    loadSummaries()
  }
  confirm.show = true
}

async function runConfirm() {
  if (!confirm.action) {
    confirm.show = false
    return
  }
  confirm.loading = true
  try {
    await confirm.action()
    confirm.show = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memproses')
  } finally {
    confirm.loading = false
    confirm.action = null
  }
}

function openDecreeModal(item = null) {
  formError.value = ''
  if (item) {
    Object.assign(decreeForm, {
      id: item.id,
      employee_id: item.employee_id,
      decree_type: item.decree_type,
      number: item.number,
      title: item.title,
      decree_date: item.decree_date,
      effective_date: item.effective_date || '',
      end_date: item.end_date || '',
      description: item.description || '',
      file: null,
    })
  } else {
    Object.assign(decreeForm, {
      id: null,
      employee_id: '',
      decree_type: 'jabatan',
      number: '',
      title: '',
      decree_date: '',
      effective_date: '',
      end_date: '',
      description: '',
      file: null,
    })
  }
  showDecreeModal.value = true
}

function onDecreeFile(e) {
  decreeForm.file = e.target.files?.[0] || null
}

async function submitDecree() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    fd.append('employee_id', decreeForm.employee_id)
    fd.append('decree_type', decreeForm.decree_type)
    fd.append('number', decreeForm.number)
    fd.append('title', decreeForm.title)
    fd.append('decree_date', decreeForm.decree_date)
    if (decreeForm.effective_date) fd.append('effective_date', decreeForm.effective_date)
    if (decreeForm.end_date) fd.append('end_date', decreeForm.end_date)
    if (decreeForm.description) fd.append('description', decreeForm.description)
    if (decreeForm.file) fd.append('file', decreeForm.file)
    if (decreeForm.id) {
      await employeeDecreeApi.update(decreeForm.id, fd)
      toast.success('SK diperbarui')
    } else {
      await employeeDecreeApi.create(fd)
      toast.success('SK ditambahkan')
    }
    showDecreeModal.value = false
    loadDecrees(1)
    loadSummaries()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

async function downloadDecree(item) {
  try {
    const { data } = await employeeDecreeApi.download(item.id)
    const url = URL.createObjectURL(data)
    const a = document.createElement('a')
    a.href = url
    a.download = item.file_name || 'sk.pdf'
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.error('Gagal mengunduh file')
  }
}

function askDeleteDecree(item) {
  confirm.title = 'Hapus SK?'
  confirm.message = `SK ${item.number} akan dihapus dari arsip.`
  confirm.warning = 'Berkas terlampir juga akan dihapus.'
  confirm.confirmText = 'Hapus'
  confirm.variant = 'danger'
  confirm.action = async () => {
    await employeeDecreeApi.delete(item.id)
    toast.success('SK dihapus')
    loadDecrees(1)
    loadSummaries()
  }
  confirm.show = true
}

function openPositionModal() {
  formError.value = ''
  Object.assign(positionForm, {
    employee_id: '',
    structural_position_id: '',
    employee_decree_id: '',
    started_at: '',
    ended_at: '',
    decree_number: '',
    notes: '',
  })
  employeeDecrees.value = []
  showPositionModal.value = true
}

async function loadEmployeeDecrees() {
  if (!positionForm.employee_id) {
    employeeDecrees.value = []
    return
  }
  try {
    const { data } = await employeeDecreeApi.getAll({ employee_id: positionForm.employee_id, per_page: 50 })
    employeeDecrees.value = data.data || []
  } catch {
    employeeDecrees.value = []
  }
}

async function submitPosition() {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      employee_id: Number(positionForm.employee_id),
      structural_position_id: Number(positionForm.structural_position_id),
      started_at: positionForm.started_at,
      ended_at: positionForm.ended_at || null,
      decree_number: positionForm.decree_number || null,
      notes: positionForm.notes || null,
      employee_decree_id: positionForm.employee_decree_id ? Number(positionForm.employee_decree_id) : null,
    }
    await structuralPositionApi.assign(payload)
    toast.success('Jabatan ditetapkan')
    showPositionModal.value = false
    loadPositions(1)
    loadSummaries()
  } catch (e) {
    formError.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

function openEndPositionModal(item) {
  formError.value = ''
  endTarget.value = item
  endDate.value = new Date().toISOString().slice(0, 10)
  showEndModal.value = true
}

async function submitEndPosition() {
  if (!endTarget.value || !endDate.value) return
  saving.value = true
  formError.value = ''
  try {
    await structuralPositionApi.end(endTarget.value.id, { ended_at: endDate.value })
    toast.success('Jabatan diakhiri')
    showEndModal.value = false
    loadPositions(positionPage.current_page)
    loadSummaries()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal mengakhiri jabatan'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadEmployees(), loadMeta(), loadSummaries()])
  loadLeaves(1)
})
</script>

<style scoped>
.kepegawaian-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title {
  margin: 0 0 0.25rem;
  font-size: var(--fs-title, 18px);
  letter-spacing: -0.02em;
  color: var(--text-primary, #1e293b);
}

.page-subtitle {
  margin: 0;
  font-size: var(--fs-subtitle, 12px);
  color: var(--text-secondary, #64748b);
  line-height: 1.45;
  max-width: 42rem;
}

.btn-header {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}

.stat-card {
  text-align: left;
  background: var(--bg-primary, #fff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: var(--radius-md, 12px);
  padding: 0.9rem 1rem;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
}

.stat-card:hover {
  border-color: #a7f3d0;
}

.stat-card.active {
  border-color: var(--primary-color, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.stat-label {
  display: block;
  font-size: 12px;
  color: var(--text-secondary, #64748b);
  margin-bottom: 0.2rem;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--primary-color, #059669);
  letter-spacing: -0.03em;
}

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
.tab-main { min-width: 0; padding: 14px 16px 16px; }
@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
}

.hint-banner {
  margin: 0;
  padding: 0.7rem 0.9rem;
  background: rgba(5, 150, 105, 0.08);
  color: #047857;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1.45;
}

.search-wrap {
  position: relative;
  flex: 1 1 180px;
  min-width: 0;
}

.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted, #94a3b8);
  pointer-events: none;
}

.search-wrap .search-input {
  width: 100%;
  padding: 0.5rem 0.7rem 0.5rem 2rem;
}

.filter-select-wide { min-width: min(280px, 100%); }

.check-inline {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 13px;
  color: var(--text-primary, #1e293b);
  white-space: nowrap;
}

.check-inline input { accent-color: var(--primary-color, #059669); }

.table-card {
  background: var(--bg-primary, #fff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: var(--radius-md, 12px);
  overflow: auto;
  box-shadow: var(--shadow-sm);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid var(--border-color, #e2e8f0);
  text-align: left;
  vertical-align: middle;
}

.data-table th {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-secondary, #64748b);
  background: var(--bg-secondary, #f8fafc);
}

.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover { background: #f8fafc; }

.col-actions { white-space: nowrap; }

.person-cell {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 999px;
  background: rgba(5, 150, 105, 0.12);
  color: var(--primary-color, #059669);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.avatar-sm { width: 28px; height: 28px; }
.avatar-lg { width: 44px; height: 44px; font-size: 14px; }

.cell-title { font-weight: 600; color: var(--text-primary, #1e293b); }
.cell-sub {
  font-size: 12px;
  color: var(--text-secondary, #64748b);
  margin-top: 0.1rem;
}

.actions { display: flex; flex-wrap: wrap; gap: 0.35rem; }

.btn-sm {
  padding: 0.3rem 0.6rem;
  border-radius: 7px;
  border: 1px solid var(--border-color, #e2e8f0);
  background: #fff;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  min-height: 32px;
}

.btn-approve {
  background: var(--primary-color, #059669);
  color: #fff;
  border-color: var(--primary-color, #059669);
}

.btn-approve:hover { background: var(--primary-hover, #047857); }

.btn-ghost { color: #475569; }
.btn-ghost:hover { background: #f1f5f9; }
.btn-ghost-danger { color: #b91c1c; border-color: #fecaca; }
.btn-ghost-danger:hover { background: #fef2f2; }

.btn-primary,
.btn-secondary,
.btn-danger {
  padding: 0.5rem 0.95rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  min-height: 40px;
  font-weight: 600;
  font-size: 13px;
}

.btn-primary {
  background: var(--primary-color, #059669);
  color: #fff;
}

.btn-primary:hover:not(:disabled) { background: var(--primary-hover, #047857); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  background: #fff;
  color: var(--text-primary, #1e293b);
  border: 1px solid var(--border-color, #e2e8f0);
}

.btn-secondary:hover { background: #f8fafc; }

.btn-danger {
  background: var(--danger-color, #ef4444);
  color: #fff;
}

.type-chip {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  background: var(--bg-tertiary, #f1f5f9);
  color: #334155;
  font-size: 12px;
  font-weight: 500;
}

.status-badge {
  display: inline-block;
  padding: 0.15rem 0.55rem;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-cancelled { background: #e2e8f0; color: #475569; }

.empty-state {
  text-align: center;
  padding: 2.75rem 1rem;
  background: #fff;
  border: 1px dashed var(--border-color, #e2e8f0);
  border-radius: var(--radius-md, 12px);
}

.empty-icon {
  color: var(--primary-color, #059669);
  margin-bottom: 0.6rem;
}

.empty-title { margin: 0 0 0.35rem; }
.empty-desc {
  margin: 0 0 1rem;
  color: var(--text-secondary, #64748b);
  font-size: 13px;
}

.muted { color: var(--text-muted, #94a3b8); }

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fff;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: var(--radius-md, 12px);
}

.pagination-info { font-size: 12px; color: var(--text-secondary, #64748b); }
.pagination-buttons { display: flex; gap: 0.4rem; }

.btn-page {
  padding: 0.4rem 0.75rem;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
}

.btn-page:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-page:not(:disabled):hover { background: #f8fafc; }

.mobile-cards { display: none; flex-direction: column; gap: 0.75rem; }

.item-card {
  background: #fff;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.item-card-top {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: flex-start;
}

.item-card-meta {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary, #64748b);
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
  background: #fff;
  border-radius: 14px;
  width: min(640px, 95%);
  max-height: 90vh;
  overflow: auto;
  box-shadow: var(--shadow-lg);
}

.modal-sm { width: min(420px, 95%); }

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.modal-header h3 { margin: 0; font-size: 1rem; }

.modal-body {
  padding: 1.15rem 1.25rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.btn-close {
  border: none;
  background: transparent;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: var(--text-secondary, #64748b);
}

.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.form-group label { font-size: 12px; font-weight: 600; color: #334155; }

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  font-size: 16px;
  background: #fff;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 0.75rem;
}

.form-hint {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary, #64748b);
}

.file-picker {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
}

.file-picker input { display: none; }

.file-picker-btn {
  padding: 0.4rem 0.7rem;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
}

.file-name { font-size: 12px; color: var(--text-secondary, #64748b); }

.error-message { color: #b91c1c; font-size: 13px; }

.history-header {
  margin-bottom: 1rem;
  padding: 0.9rem 1rem 1rem;
  background: #fff;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
}
.history-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.history-header h3 { margin: 0; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: #ecfdf5; color: #059669;
}
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.meta-value { font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; word-break: break-word; }
@media (max-width: 768px) { .meta-grid { grid-template-columns: 1fr 1fr; } }

.timeline {
  list-style: none;
  margin: 0;
  padding: 0 0 0 0.85rem;
  border-left: 2px solid #d1fae5;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.timeline-item {
  position: relative;
  background: #fff;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  padding: 0.85rem 1rem;
  margin-left: 0.75rem;
}

.timeline-dot {
  position: absolute;
  left: -1.3rem;
  top: 1.1rem;
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: var(--primary-color, #059669);
  border: 2px solid #fff;
  box-shadow: 0 0 0 2px #a7f3d0;
}

.type-leave .timeline-dot { background: #d97706; box-shadow: 0 0 0 2px #fde68a; }
.type-decree .timeline-dot { background: var(--primary-color, #059669); }
.type-structural_position .timeline-dot { background: #0f766e; box-shadow: 0 0 0 2px #99f6e4; }
.type-mutation .timeline-dot { background: #64748b; box-shadow: 0 0 0 2px #cbd5e1; }

.timeline-date { font-size: 12px; color: var(--text-secondary, #64748b); margin-bottom: 0.25rem; }

.timeline-type {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--primary-color, #059669);
  margin-bottom: 0.15rem;
}

@media (max-width: 768px) {
  .page-header { flex-direction: column; }
  .btn-header { width: 100%; justify-content: center; }
  .stat-grid { grid-template-columns: 1fr; }
  .table-desktop { display: none; }
  .mobile-cards { display: flex; }
}
</style>
