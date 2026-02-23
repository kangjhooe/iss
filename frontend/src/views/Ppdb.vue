<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div class="header-icon-wrap">
              <svg class="header-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <h1 class="page-title">PPDB</h1>
              <p class="page-subtitle">Penerimaan Peserta Didik Baru — kelola periode, jalur, dan calon</p>
            </div>
          </div>
          <div class="header-actions">
            <button v-if="activeTab === 'periods'" @click="openPeriodModal()" class="btn-header-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Periode</span>
            </button>
            <button v-if="activeTab === 'channels'" @click="openChannelModal()" class="btn-header-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Jalur</span>
            </button>
            <button v-if="activeTab === 'applicants'" @click="openApplicantModal()" class="btn-header-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Calon</span>
            </button>
          </div>
        </div>
      </header>

      <div class="nav-tabs-wrap">
        <nav class="nav-tabs" role="tablist">
          <button :class="['nav-tab', { active: activeTab === 'periods' }]" @click="switchTab('periods')" role="tab">
            <span class="nav-tab-label">Periode</span>
            <span class="nav-tab-hint">Gelombang & jadwal</span>
          </button>
          <button :class="['nav-tab', { active: activeTab === 'channels' }]" @click="switchTab('channels')" role="tab">
            <span class="nav-tab-label">Jalur</span>
            <span class="nav-tab-hint">Zonasi, afirmasi, dll</span>
          </button>
          <button :class="['nav-tab', { active: activeTab === 'applicants' }]" @click="switchTab('applicants')" role="tab">
            <span class="nav-tab-label">Calon</span>
            <span class="nav-tab-hint">Data & verifikasi</span>
          </button>
          <button :class="['nav-tab', { active: activeTab === 'statistics' }]" @click="switchTab('statistics')" role="tab">
            <span class="nav-tab-label">Statistik</span>
            <span class="nav-tab-hint">Rekap periode & jalur</span>
          </button>
        </nav>
      </div>

      <main class="page-main">
        <!-- Tab: Periode -->
        <template v-if="activeTab === 'periods'">
          <div v-if="periodsLoading" class="loading-wrap content-card"><LoadingSkeleton type="table" :rows="6" :columns="7" /></div>
          <div v-else-if="periods.length === 0" class="empty-state content-card">
            <div class="empty-icon">📅</div>
            <h3>Belum ada periode PPDB</h3>
            <p>Buat periode (gelombang) pendaftaran terlebih dahulu.</p>
            <button @click="openPeriodModal()" class="btn-primary">Tambah Periode</button>
          </div>
          <div v-else class="table-wrap content-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Tahun Ajaran</th>
                  <th>Buka</th>
                  <th>Tutup</th>
                  <th>Status</th>
                  <th>Jumlah Calon</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in periods" :key="p.id">
                  <td><strong>{{ p.name }}</strong></td>
                  <td>{{ p.academic_year?.code || p.academic_year?.name || '-' }}</td>
                  <td>{{ formatDate(p.open_date) }}</td>
                  <td>{{ formatDate(p.close_date) }}</td>
                  <td><span :class="['status-badge', 'status-' + p.status]">{{ statusPeriodLabel(p.status) }}</span></td>
                  <td>{{ p.applicants_count ?? 0 }}</td>
                  <td>
                    <button @click="openPeriodModal(p)" class="btn-action btn-edit">Edit</button>
                    <button @click="confirmDeletePeriod(p)" class="btn-action btn-delete">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Tab: Jalur -->
        <template v-if="activeTab === 'channels'">
          <div v-if="channelsLoading" class="loading-wrap content-card"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
          <div v-else-if="channels.length === 0" class="empty-state content-card">
            <div class="empty-icon">🛤️</div>
            <h3>Belum ada jalur pendaftaran</h3>
            <p>Tambahkan jalur seperti Zonasi, Afirmasi, Prestasi.</p>
            <button @click="openChannelModal()" class="btn-primary">Tambah Jalur</button>
          </div>
          <div v-else class="table-wrap content-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Kuota</th>
                  <th>Aktif</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in channels" :key="c.id">
                  <td><code>{{ c.code }}</code></td>
                  <td>{{ c.name }}</td>
                  <td>{{ c.quota ?? '-' }}</td>
                  <td>{{ c.is_active ? 'Ya' : 'Tidak' }}</td>
                  <td>
                    <button @click="openChannelModal(c)" class="btn-action btn-edit">Edit</button>
                    <button @click="confirmDeleteChannel(c)" class="btn-action btn-delete">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Tab: Statistik -->
        <template v-if="activeTab === 'statistics'">
          <div class="filters-bar content-card">
            <label class="filter-label">Periode</label>
            <select v-model="statsPeriodId" @change="loadStatistics" class="filter-select">
              <option value="">Pilih periode</option>
              <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div v-if="statsLoading" class="loading-wrap content-card"><LoadingSkeleton type="table" :rows="4" :columns="4" /></div>
          <div v-else-if="!statsPeriodId" class="empty-state content-card empty-state-sm">
            <p>Pilih periode untuk melihat statistik.</p>
          </div>
          <div v-else-if="statsData" class="stats-dashboard content-card">
            <div class="stats-cards">
              <div class="stats-card stats-total">
                <span class="stats-value">{{ statsData.total }}</span>
                <span class="stats-label">Total Calon</span>
              </div>
            </div>
            <template v-if="statsData.total === 0">
              <p class="empty-stats-msg">Belum ada calon peserta didik pada periode ini.</p>
            </template>
            <template v-else>
              <div class="stats-section">
                <h4>Per Status</h4>
                <div v-if="Object.keys(statsData.by_status || {}).length" class="stats-grid">
                  <div v-for="(count, status) in (statsData.by_status || {})" :key="status" class="stats-row">
                    <span class="status-badge" :class="'status-' + status">{{ statusApplicantLabels[status] || status }}</span>
                    <strong>{{ count }}</strong>
                  </div>
                </div>
                <p v-else class="stats-empty">—</p>
              </div>
              <div class="stats-section">
                <h4>Per Jalur</h4>
                <table v-if="(statsData.by_channel || []).length" class="data-table data-table-compact">
                  <thead>
                    <tr><th>Jalur</th><th>Jumlah</th></tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in (statsData.by_channel || [])" :key="row.channel_id">
                      <td>{{ row.channel_name }}</td>
                      <td>{{ row.count }}</td>
                    </tr>
                  </tbody>
                </table>
                <p v-else class="stats-empty">—</p>
              </div>
            </template>
          </div>
        </template>

        <!-- Tab: Calon -->
        <template v-if="activeTab === 'applicants'">
          <div class="filters-bar content-card">
            <div class="search-wrap">
              <input v-model="applicantFilters.search" type="text" placeholder="Cari nama, no. pendaftaran, NISN..." class="search-input" @input="debounceLoadApplicants" />
            </div>
            <select v-model="applicantFilters.ppdb_period_id" @change="loadApplicants" class="filter-select">
              <option value="">Semua Periode</option>
              <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="applicantFilters.ppdb_channel_id" @change="loadApplicants" class="filter-select">
              <option value="">Semua Jalur</option>
              <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="applicantFilters.status" @change="loadApplicants" class="filter-select">
              <option value="">Semua Status</option>
              <option v-for="(l, k) in statusApplicantLabels" :key="k" :value="k">{{ l }}</option>
            </select>
            <button type="button" class="btn-export" :disabled="exportingApplicants" @click="exportApplicants">
              {{ exportingApplicants ? 'Mengekspor...' : 'Export CSV' }}
            </button>
          </div>
          <div v-if="applicantsLoading" class="loading-wrap content-card"><LoadingSkeleton type="table" :rows="8" :columns="8" /></div>
          <div v-else-if="applicants.length === 0" class="empty-state content-card">
            <div class="empty-icon">👤</div>
            <h3>Belum ada calon peserta didik</h3>
            <p>Pilih periode dan jalur, lalu tambah calon.</p>
            <button @click="openApplicantModal()" class="btn-primary">Tambah Calon</button>
          </div>
          <div v-else class="table-wrap content-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No. Pendaftaran</th>
                  <th>Nama</th>
                  <th>Jalur</th>
                  <th>Periode</th>
                  <th>Rank</th>
                  <th>Status</th>
                  <th>Berkas</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in applicants" :key="a.id">
                  <td><strong>{{ a.registration_number }}</strong></td>
                  <td>{{ a.name }}</td>
                  <td>{{ a.channel?.name }}</td>
                  <td>{{ a.period?.name }}</td>
                  <td>{{ a.rank ?? '-' }}</td>
                  <td><span :class="['status-badge', 'status-' + a.status]">{{ statusApplicantLabels[a.status] || a.status }}</span></td>
                  <td>{{ a.documents_verified ? '✓' : '-' }}</td>
                  <td>
                    <button @click="openApplicantDetail(a)" class="btn-action btn-edit">Detail</button>
                    <button v-if="canSetResult(a)" @click="openResultModal(a)" class="btn-action btn-edit">Hasil</button>
                    <button v-if="canConfirmReReg(a)" @click="doConfirmReReg(a)" class="btn-action btn-edit">Daftar Ulang</button>
                    <button v-if="canConvertToStudent(a)" @click="openConvertModal(a)" class="btn-action btn-primary-sm">Jadikan Siswa</button>
                    <button @click="openApplicantModal(a)" class="btn-action btn-edit">Edit</button>
                    <button @click="confirmDeleteApplicant(a)" class="btn-action btn-delete">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="applicantsPagination.last_page > 1" class="pagination-bar content-card">
            <span class="pagination-info">Halaman {{ applicantsPagination.current_page }} / {{ applicantsPagination.last_page }} ({{ applicantsPagination.total }} data)</span>
            <div class="pagination-btns">
              <button type="button" class="pagination-btn" :disabled="applicantsPagination.current_page <= 1" @click="goApplicantsPage(applicantsPagination.current_page - 1)">Sebelumnya</button>
              <button type="button" class="pagination-btn" :disabled="applicantsPagination.current_page >= applicantsPagination.last_page" @click="goApplicantsPage(applicantsPagination.current_page + 1)">Selanjutnya</button>
            </div>
          </div>
        </template>
      </main>

      <!-- Modal Periode -->
      <div v-if="showPeriodModal" class="modal-overlay" @click="showPeriodModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingPeriod ? 'Edit Periode PPDB' : 'Tambah Periode PPDB' }}</h3>
            <button @click="showPeriodModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitPeriod" class="modal-body">
            <div class="form-group">
              <label>Tahun Ajaran *</label>
              <select v-model="periodForm.academic_year_id" required class="form-select">
                <option value="">Pilih tahun ajaran</option>
                <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.code }} {{ y.name ? ' - ' + y.name : '' }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Periode *</label>
              <input v-model="periodForm.name" type="text" required placeholder="Contoh: Gelombang 1" />
            </div>
            <div class="form-group">
              <label>Jenjang (opsional)</label>
              <input v-model="periodForm.level" type="text" placeholder="SD, SMP, SMA" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Buka *</label>
                <input v-model="periodForm.open_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Tanggal Tutup *</label>
                <input v-model="periodForm.close_date" type="date" required />
              </div>
            </div>
            <div class="form-group">
              <label>Batas Daftar Ulang (opsional)</label>
              <input v-model="periodForm.re_registration_deadline" type="date" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="periodForm.status" class="form-select">
                <option value="draft">Draft</option>
                <option value="open">Dibuka</option>
                <option value="closed">Ditutup</option>
                <option value="finished">Selesai</option>
              </select>
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="periodForm.description" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="periodFormError" class="error-message">{{ periodFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showPeriodModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="periodFormSubmitting" class="btn-primary">{{ periodFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Jalur -->
      <div v-if="showChannelModal" class="modal-overlay" @click="showChannelModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingChannel ? 'Edit Jalur' : 'Tambah Jalur Pendaftaran' }}</h3>
            <button @click="showChannelModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitChannel" class="modal-body">
            <div class="form-group">
              <label>Kode *</label>
              <input v-model="channelForm.code" type="text" required placeholder="zonasi, afirmasi, prestasi" />
            </div>
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="channelForm.name" type="text" required placeholder="Jalur Zonasi" />
            </div>
            <div class="form-group">
              <label>Kuota (opsional)</label>
              <input v-model.number="channelForm.quota" type="number" min="0" placeholder="0" />
            </div>
            <div class="form-group">
              <label>Persyaratan (opsional)</label>
              <textarea v-model="channelForm.requirements" rows="2" placeholder="Teks persyaratan"></textarea>
            </div>
            <div class="form-group">
              <label><input v-model="channelForm.is_active" type="checkbox" /> Aktif</label>
            </div>
            <div v-if="channelFormError" class="error-message">{{ channelFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showChannelModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="channelFormSubmitting" class="btn-primary">{{ channelFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Calon (Tambah/Edit) -->
      <div v-if="showApplicantModal" class="modal-overlay" @click="showApplicantModal = false">
        <div class="modal-content form-modal form-modal-wide" @click.stop>
          <div class="modal-header">
            <h3>{{ editingApplicant ? 'Edit Calon Peserta Didik' : 'Tambah Calon Peserta Didik' }}</h3>
            <button @click="showApplicantModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitApplicant" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Periode PPDB *</label>
                <select v-model="applicantForm.ppdb_period_id" required :disabled="!!editingApplicant" class="form-select">
                  <option value="">Pilih periode</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jalur *</label>
                <select v-model="applicantForm.ppdb_channel_id" required class="form-select">
                  <option value="">Pilih jalur</option>
                  <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Nama Lengkap *</label>
              <input v-model="applicantForm.name" type="text" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>NIK</label>
                <input v-model="applicantForm.nik" type="text" />
              </div>
              <div class="form-group">
                <label>NISN</label>
                <input v-model="applicantForm.nisn" type="text" />
              </div>
              <div class="form-group">
                <label>Jenis Kelamin *</label>
                <select v-model="applicantForm.gender" required class="form-select">
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tempat Lahir</label>
                <input v-model="applicantForm.birth_place" type="text" />
              </div>
              <div class="form-group">
                <label>Tanggal Lahir</label>
                <input v-model="applicantForm.birth_date" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="applicantForm.address" rows="2"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="applicantForm.phone" type="text" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input v-model="applicantForm.email" type="email" />
              </div>
              <div class="form-group">
                <label>Agama</label>
                <select v-model="applicantForm.religion" class="form-select">
                  <option value="">—</option>
                  <option value="Islam">Islam</option>
                  <option value="Kristen">Kristen</option>
                  <option value="Katolik">Katolik</option>
                  <option value="Hindu">Hindu</option>
                  <option value="Buddha">Buddha</option>
                  <option value="Konghucu">Konghucu</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>NPSN Sekolah Asal</label>
                <input v-model="applicantForm.previous_school_npsn" type="text" placeholder="10 digit" />
              </div>
              <div class="form-group">
                <label>Nama Sekolah Asal</label>
                <input v-model="applicantForm.previous_school" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Alamat Sekolah Asal</label>
              <textarea v-model="applicantForm.previous_school_address" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Ayah — Nama</label>
              <input v-model="applicantForm.father_name" type="text" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Ayah — NIK</label>
                <input v-model="applicantForm.father_nik" type="text" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Ayah — Telepon</label>
                <input v-model="applicantForm.father_phone" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Ibu — Nama</label>
              <input v-model="applicantForm.mother_name" type="text" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Ibu — NIK</label>
                <input v-model="applicantForm.mother_nik" type="text" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Ibu — Telepon</label>
                <input v-model="applicantForm.mother_phone" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Wali — Nama / Telepon / Hubungan</label>
              <div class="form-row">
                <input v-model="applicantForm.guardian_name" type="text" placeholder="Nama wali" />
                <input v-model="applicantForm.guardian_phone" type="text" placeholder="Telepon" />
                <input v-model="applicantForm.guardian_relation" type="text" placeholder="Hubungan" />
              </div>
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="applicantForm.notes" rows="2"></textarea>
            </div>
            <div v-if="applicantFormError" class="error-message">{{ applicantFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showApplicantModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="applicantFormSubmitting" class="btn-primary">{{ applicantFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Set Hasil Seleksi -->
      <div v-if="showResultModal" class="modal-overlay" @click="showResultModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Set Hasil Seleksi — {{ resultTarget?.registration_number }}</h3>
            <button @click="showResultModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitResult" class="modal-body">
            <div class="form-group">
              <label>Hasil *</label>
              <select v-model="resultForm.status" required class="form-select">
                <option value="passed">Lulus</option>
                <option value="reserve">Cadangan</option>
                <option value="failed">Tidak Lulus</option>
              </select>
            </div>
            <div class="form-group">
              <label>Rank (opsional)</label>
              <input v-model.number="resultForm.rank" type="number" min="1" placeholder="Urutan peringkat" />
            </div>
            <div class="form-group">
              <label>Catatan hasil</label>
              <textarea v-model="resultForm.result_notes" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="resultFormError" class="error-message">{{ resultFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showResultModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="resultFormSubmitting" class="btn-primary">{{ resultFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Jadikan Siswa -->
      <div v-if="showConvertModal" class="modal-overlay" @click="showConvertModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Jadikan Siswa — {{ convertTarget?.registration_number }}</h3>
            <button @click="showConvertModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitConvert" class="modal-body">
            <div class="form-group">
              <label>Kelas (opsional)</label>
              <select v-model="convertForm.class_id" class="form-select">
                <option value="">— Tanpa kelas —</option>
                <option v-for="c in convertClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div v-if="convertFormError" class="error-message">{{ convertFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showConvertModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="convertFormSubmitting" class="btn-primary">{{ convertFormSubmitting ? 'Memproses...' : 'Jadikan Siswa' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Detail Calon (verifikasi & dokumen) -->
      <div v-if="detailApplicant" class="modal-overlay" @click="detailApplicant = null">
        <div class="modal-content form-modal form-modal-wide" @click.stop>
          <div class="modal-header">
            <h3>Detail Calon — {{ detailApplicant.registration_number }}</h3>
            <button @click="detailApplicant = null" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <p><strong>Nama:</strong> {{ detailApplicant.name }} &nbsp; <strong>Jalur:</strong> {{ detailApplicant.channel?.name }} &nbsp; <strong>Status:</strong> {{ statusApplicantLabels[detailApplicant.status] }}</p>
            <p v-if="detailApplicant.rank"><strong>Rank:</strong> {{ detailApplicant.rank }} &nbsp; <span v-if="detailApplicant.announcement_at"><strong>Pengumuman:</strong> {{ formatDate(detailApplicant.announcement_at) }}</span></p>
            <p v-if="detailApplicant.student_id"><strong>Sudah jadi siswa:</strong> NIS {{ detailApplicant.student?.nis }} — {{ detailApplicant.student?.name }}</p>
            <p><strong>Berkas:</strong> {{ detailApplicant.documents_verified ? 'Terverifikasi' : 'Belum verifikasi' }}</p>
            <div class="action-row">
              <button v-if="canSetResult(detailApplicant)" @click="openResultModal(detailApplicant); detailApplicant = null" class="btn-primary btn-compact">Set Hasil</button>
              <button v-if="canConfirmReReg(detailApplicant)" @click="doConfirmReReg(detailApplicant)" class="btn-primary btn-compact">Konfirmasi Daftar Ulang</button>
              <button v-if="canConvertToStudent(detailApplicant)" @click="openConvertModal(detailApplicant); detailApplicant = null" class="btn-primary btn-compact">Jadikan Siswa</button>
            </div>
            <div v-if="detailApplicant.documents?.length" class="doc-list">
              <p><strong>Dokumen:</strong></p>
              <ul>
                <li v-for="d in detailApplicant.documents" :key="d.id">
                  {{ d.name }} — {{ d.file_name }}
                  <button type="button" class="link-download" @click="downloadDocument(d)">Unduh</button>
                </li>
              </ul>
            </div>
            <div class="form-group">
              <label>Verifikasi berkas</label>
              <label><input v-model="verificationForm.documents_verified" type="checkbox" /> Dokumen lengkap & valid</label>
              <textarea v-model="verificationForm.verification_notes" rows="2" placeholder="Catatan verifikasi"></textarea>
              <button type="button" class="btn-primary btn-compact" :disabled="verificationSubmitting" @click="submitVerification">{{ verificationSubmitting ? 'Menyimpan...' : 'Simpan Verifikasi' }}</button>
            </div>
          </div>
        </div>
      </div>

      <ConfirmDialog v-if="deletePeriodTarget" :show="!!deletePeriodTarget" title="Hapus Periode" message="Yakin menghapus periode ini? Periode yang sudah memiliki calon tidak dapat dihapus." confirmText="Hapus" @confirm="doDeletePeriod" @cancel="deletePeriodTarget = null" />
      <ConfirmDialog v-if="deleteChannelTarget" :show="!!deleteChannelTarget" title="Hapus Jalur" message="Yakin menghapus jalur ini? Jalur yang sudah dipakai calon tidak dapat dihapus." confirmText="Hapus" @confirm="doDeleteChannel" @cancel="deleteChannelTarget = null" />
      <ConfirmDialog v-if="deleteApplicantTarget" :show="!!deleteApplicantTarget" title="Hapus Calon" message="Yakin menghapus data calon ini?" confirmText="Hapus" @confirm="doDeleteApplicant" @cancel="deleteApplicantTarget = null" />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { ppdbPeriodApi, ppdbChannelApi, ppdbApplicantApi } from '@/api/ppdb'
import { useReferenceDataStore } from '@/stores/referenceData'
import { classApi } from '@/api/class'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const activeTab = ref('periods')

const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const periods = ref([])
const periodsLoading = ref(false)
const channels = ref([])
const channelsLoading = ref(false)
const applicants = ref([])
const applicantsLoading = ref(false)
const applicantsPagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const applicantFilters = ref({ search: '', ppdb_period_id: '', ppdb_channel_id: '', status: '' })
const exportingApplicants = ref(false)
const statsPeriodId = ref('')
const statsData = ref(null)
const statsLoading = ref(false)

const showPeriodModal = ref(false)
const editingPeriod = ref(null)
const periodForm = ref({ academic_year_id: '', name: '', level: '', open_date: '', close_date: '', re_registration_deadline: '', status: 'draft', description: '' })
const periodFormError = ref('')
const periodFormSubmitting = ref(false)

const showResultModal = ref(false)
const resultTarget = ref(null)
const resultForm = ref({ status: 'passed', rank: null, result_notes: '' })
const resultFormError = ref('')
const resultFormSubmitting = ref(false)

const showConvertModal = ref(false)
const convertTarget = ref(null)
const convertForm = ref({ class_id: '' })
const convertClasses = ref([])
const convertFormError = ref('')
const convertFormSubmitting = ref(false)

const showChannelModal = ref(false)
const editingChannel = ref(null)
const channelForm = ref({ code: '', name: '', quota: null, requirements: '', is_active: true })
const channelFormError = ref('')
const channelFormSubmitting = ref(false)

const showApplicantModal = ref(false)
const editingApplicant = ref(null)
const applicantForm = ref({
  ppdb_period_id: '', ppdb_channel_id: '', name: '', nik: '', nisn: '', gender: 'L',
  birth_date: '', birth_place: '', address: '', phone: '', email: '', religion: '', previous_school: '',
  previous_school_npsn: '', previous_school_address: '',
  father_name: '', father_phone: '', father_nik: '', mother_name: '', mother_phone: '', mother_nik: '',
  guardian_name: '', guardian_phone: '', guardian_relation: '', notes: ''
})
const applicantFormError = ref('')
const applicantFormSubmitting = ref(false)

const detailApplicant = ref(null)
const verificationForm = ref({ documents_verified: false, verification_notes: '' })
const verificationSubmitting = ref(false)

const deletePeriodTarget = ref(null)
const deleteChannelTarget = ref(null)
const deleteApplicantTarget = ref(null)

const statusPeriodLabel = (s) => ({ draft: 'Draft', open: 'Dibuka', closed: 'Ditutup', finished: 'Selesai' }[s] || s)
const statusApplicantLabels = {
  draft: 'Draft',
  submitted: 'Terkirim',
  verification: 'Verifikasi',
  verified: 'Terverifikasi',
  rejected: 'Ditolak',
  passed: 'Lulus',
  reserve: 'Cadangan',
  failed: 'Tidak Lulus',
  re_registration: 'Daftar Ulang',
  converted: 'Jadi Siswa',
  cancelled: 'Dibatalkan',
}

function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'periods') loadPeriods()
  else if (tab === 'channels') loadChannels()
  else if (tab === 'applicants') loadApplicants()
  else if (tab === 'statistics') {
    if (periods.value.length === 0) loadPeriods()
    statsData.value = null
    if (statsPeriodId.value) loadStatistics()
  }
}

async function loadStatistics() {
  const id = statsPeriodId.value
  if (!id) {
    statsData.value = null
    return
  }
  statsLoading.value = true
  statsData.value = null
  try {
    const res = await ppdbPeriodApi.getStatistics(id)
    statsData.value = res.data?.data || res.data
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat statistik')
  } finally {
    statsLoading.value = false
  }
}

async function loadPeriods() {
  periodsLoading.value = true
  try {
    const res = await ppdbPeriodApi.getAll({ per_page: 100 })
    periods.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat periode')
  } finally {
    periodsLoading.value = false
  }
}

async function loadChannels() {
  channelsLoading.value = true
  try {
    const res = await ppdbChannelApi.getAll({ active_only: false })
    channels.value = res.data.data || []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat jalur')
  } finally {
    channelsLoading.value = false
  }
}

let applicantDebounce = null
function debounceLoadApplicants() {
  clearTimeout(applicantDebounce)
  applicantDebounce = setTimeout(loadApplicants, 300)
}

async function loadApplicants() {
  applicantsLoading.value = true
  try {
    const params = {
      page: applicantsPagination.value.current_page,
      per_page: 15,
      ...applicantFilters.value,
    }
    if (!params.ppdb_period_id) delete params.ppdb_period_id
    if (!params.ppdb_channel_id) delete params.ppdb_channel_id
    if (!params.status) delete params.status
    if (!params.search) delete params.search
    const res = await ppdbApplicantApi.getAll(params)
    applicants.value = res.data.data || []
    const meta = res.data.meta || {}
    applicantsPagination.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1, per_page: meta.per_page ?? 15, total: meta.total ?? 0 }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat calon')
  } finally {
    applicantsLoading.value = false
  }
}

function goApplicantsPage(page) {
  applicantsPagination.value.current_page = page
  loadApplicants()
}

async function exportApplicants() {
  exportingApplicants.value = true
  try {
    const params = { ...applicantFilters.value }
    if (!params.ppdb_period_id) delete params.ppdb_period_id
    if (!params.ppdb_channel_id) delete params.ppdb_channel_id
    if (!params.status) delete params.status
    if (!params.search) delete params.search
    const res = await ppdbApplicantApi.export(params)
    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'calon-ppdb-' + new Date().toISOString().slice(0, 10) + '.csv'
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Export berhasil')
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal mengekspor')
  } finally {
    exportingApplicants.value = false
  }
}

function canSetResult(a) {
  return a && ['verified', 'submitted', 'verification'].includes(a.status)
}
function canConfirmReReg(a) {
  return a && ['passed', 'reserve'].includes(a.status)
}
function canConvertToStudent(a) {
  return a && (a.status === 're_registration' || (a.re_registration_confirmed_at && a.status !== 'converted')) && !a.student_id
}

function openPeriodModal(p = null) {
  editingPeriod.value = p
  if (p) {
    periodForm.value = {
      academic_year_id: p.academic_year_id,
      name: p.name,
      level: p.level || '',
      open_date: p.open_date || '',
      close_date: p.close_date || '',
      re_registration_deadline: p.re_registration_deadline || '',
      status: p.status || 'draft',
      description: p.description || '',
    }
  } else {
    periodForm.value = { academic_year_id: '', name: '', level: '', open_date: '', close_date: '', re_registration_deadline: '', status: 'draft', description: '' }
  }
  periodFormError.value = ''
  showPeriodModal.value = true
}

async function submitPeriod() {
  periodFormError.value = ''
  periodFormSubmitting.value = true
  try {
    const payload = { ...periodForm.value }
    if (!payload.re_registration_deadline) payload.re_registration_deadline = null
    if (editingPeriod.value) {
      await ppdbPeriodApi.update(editingPeriod.value.id, payload)
      toast.success('Periode berhasil diperbarui')
    } else {
      await ppdbPeriodApi.create(payload)
      toast.success('Periode berhasil ditambahkan')
    }
    showPeriodModal.value = false
    loadPeriods()
  } catch (e) {
    periodFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    periodFormSubmitting.value = false
  }
}

function openResultModal(a) {
  resultTarget.value = a
  resultForm.value = {
    status: a.status === 'passed' ? 'passed' : a.status === 'reserve' ? 'reserve' : a.status === 'failed' ? 'failed' : 'passed',
    rank: a.rank ?? null,
    result_notes: a.result_notes || '',
  }
  resultFormError.value = ''
  showResultModal.value = true
}

async function submitResult() {
  if (!resultTarget.value) return
  resultFormError.value = ''
  resultFormSubmitting.value = true
  try {
    await ppdbApplicantApi.setResult(resultTarget.value.id, {
      status: resultForm.value.status,
      rank: resultForm.value.rank || null,
      result_notes: resultForm.value.result_notes || null,
    })
    toast.success('Hasil seleksi disimpan')
    showResultModal.value = false
    const idx = applicants.value.findIndex(x => x.id === resultTarget.value.id)
    if (idx >= 0) {
      const res = await ppdbApplicantApi.get(resultTarget.value.id)
      applicants.value[idx] = res.data.data || res.data
    }
    if (detailApplicant.value?.id === resultTarget.value.id) {
      const res = await ppdbApplicantApi.get(resultTarget.value.id)
      detailApplicant.value = res.data.data || res.data
    }
    resultTarget.value = null
  } catch (e) {
    resultFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    resultFormSubmitting.value = false
  }
}

async function doConfirmReReg(a) {
  try {
    await ppdbApplicantApi.confirmReRegistration(a.id)
    toast.success('Daftar ulang dikonfirmasi')
    const idx = applicants.value.findIndex(x => x.id === a.id)
    if (idx >= 0) {
      const res = await ppdbApplicantApi.get(a.id)
      applicants.value[idx] = res.data.data || res.data
    }
    if (detailApplicant.value?.id === a.id) {
      const res = await ppdbApplicantApi.get(a.id)
      detailApplicant.value = res.data.data || res.data
    }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal konfirmasi')
  }
}

function openConvertModal(a) {
  convertTarget.value = a
  convertForm.value = { class_id: '' }
  convertFormError.value = ''
  convertClasses.value = []
  const period = periods.value.find(p => p.id === a.ppdb_period_id) || a.period
  if (period?.academic_year_id) {
    classApi.getAll({ academic_year_id: period.academic_year_id, per_page: 200 }).then((res) => {
      convertClasses.value = res.data.data || []
    }).catch(() => {})
  }
  showConvertModal.value = true
}

async function submitConvert() {
  if (!convertTarget.value) return
  convertFormError.value = ''
  convertFormSubmitting.value = true
  try {
    const res = await ppdbApplicantApi.convertToStudent(convertTarget.value.id, { class_id: convertForm.value.class_id || undefined })
    toast.success(res.data?.message || 'Calon berhasil dijadikan siswa')
    showConvertModal.value = false
    const idx = applicants.value.findIndex(x => x.id === convertTarget.value.id)
    if (idx >= 0) {
      const r = await ppdbApplicantApi.get(convertTarget.value.id)
      applicants.value[idx] = r.data.data || r.data
    }
    if (detailApplicant.value?.id === convertTarget.value.id) detailApplicant.value = null
    convertTarget.value = null
    loadApplicants()
  } catch (e) {
    convertFormError.value = e.formattedMessage || 'Gagal menjadikan siswa'
  } finally {
    convertFormSubmitting.value = false
  }
}

function confirmDeletePeriod(p) {
  deletePeriodTarget.value = p
}

async function doDeletePeriod() {
  if (!deletePeriodTarget.value) return
  try {
    await ppdbPeriodApi.delete(deletePeriodTarget.value.id)
    toast.success('Periode dihapus')
    deletePeriodTarget.value = null
    loadPeriods()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openChannelModal(c = null) {
  editingChannel.value = c
  if (c) {
    channelForm.value = { code: c.code, name: c.name, quota: c.quota ?? '', requirements: c.requirements || '', is_active: c.is_active ?? true }
  } else {
    channelForm.value = { code: '', name: '', quota: '', requirements: '', is_active: true }
  }
  channelFormError.value = ''
  showChannelModal.value = true
}

async function submitChannel() {
  channelFormError.value = ''
  channelFormSubmitting.value = true
  try {
    const payload = { ...channelForm.value }
    if (payload.quota === '') payload.quota = null
    if (editingChannel.value) {
      await ppdbChannelApi.update(editingChannel.value.id, payload)
      toast.success('Jalur berhasil diperbarui')
    } else {
      await ppdbChannelApi.create(payload)
      toast.success('Jalur berhasil ditambahkan')
    }
    showChannelModal.value = false
    loadChannels()
  } catch (e) {
    channelFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    channelFormSubmitting.value = false
  }
}

function confirmDeleteChannel(c) {
  deleteChannelTarget.value = c
}

async function doDeleteChannel() {
  if (!deleteChannelTarget.value) return
  try {
    await ppdbChannelApi.delete(deleteChannelTarget.value.id)
    toast.success('Jalur dihapus')
    deleteChannelTarget.value = null
    loadChannels()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

function openApplicantModal(a = null) {
  editingApplicant.value = a
  if (a) {
    applicantForm.value = {
      ppdb_period_id: a.ppdb_period_id,
      ppdb_channel_id: a.ppdb_channel_id,
      name: a.name,
      nik: a.nik || '',
      nisn: a.nisn || '',
      gender: a.gender,
      birth_date: a.birth_date || '',
      birth_place: a.birth_place || '',
      address: a.address || '',
      phone: a.phone || '',
      email: a.email || '',
      religion: a.religion || '',
      previous_school: a.previous_school || '',
      previous_school_npsn: a.previous_school_npsn || '',
      previous_school_address: a.previous_school_address || '',
      father_name: a.father_name || '',
      father_phone: a.father_phone || '',
      father_nik: a.father_nik || '',
      mother_name: a.mother_name || '',
      mother_phone: a.mother_phone || '',
      mother_nik: a.mother_nik || '',
      guardian_name: a.guardian_name || '',
      guardian_phone: a.guardian_phone || '',
      guardian_relation: a.guardian_relation || '',
      notes: a.notes || '',
    }
  } else {
    applicantForm.value = {
      ppdb_period_id: periods.value[0]?.id || '',
      ppdb_channel_id: channels.value[0]?.id || '',
      name: '', nik: '', nisn: '', gender: 'L',
      birth_date: '', birth_place: '', address: '', phone: '', email: '', religion: '', previous_school: '',
      previous_school_npsn: '', previous_school_address: '',
      father_name: '', father_phone: '', father_nik: '', mother_name: '', mother_phone: '', mother_nik: '',
      guardian_name: '', guardian_phone: '', guardian_relation: '', notes: '',
    }
  }
  applicantFormError.value = ''
  showApplicantModal.value = true
}

async function submitApplicant() {
  applicantFormError.value = ''
  applicantFormSubmitting.value = true
  try {
    const payload = { ...applicantForm.value }
    if (!payload.ppdb_period_id || !payload.ppdb_channel_id || !payload.name || !payload.gender) {
      applicantFormError.value = 'Periode, jalur, nama, dan jenis kelamin wajib diisi.'
      applicantFormSubmitting.value = false
      return
    }
    if (editingApplicant.value) {
      await ppdbApplicantApi.update(editingApplicant.value.id, payload)
      toast.success('Calon berhasil diperbarui')
    } else {
      await ppdbApplicantApi.create(payload)
      toast.success('Calon berhasil ditambahkan')
    }
    showApplicantModal.value = false
    loadApplicants()
  } catch (e) {
    applicantFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    applicantFormSubmitting.value = false
  }
}

function openApplicantDetail(a) {
  detailApplicant.value = a
  verificationForm.value = {
    documents_verified: a.documents_verified ?? false,
    verification_notes: a.verification_notes || '',
  }
  if (!a.documents?.length && a.id) {
    ppdbApplicantApi.get(a.id).then((res) => {
      detailApplicant.value = res.data.data || res.data
    }).catch(() => {})
  }
}

async function downloadDocument(d) {
  if (!detailApplicant.value?.id) return
  try {
    const res = await ppdbApplicantApi.downloadDocument(detailApplicant.value.id, d.id)
    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = d.file_name || d.name || 'document'
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal mengunduh')
  }
}

async function submitVerification() {
  if (!detailApplicant.value) return
  verificationSubmitting.value = true
  try {
    await ppdbApplicantApi.setVerification(detailApplicant.value.id, verificationForm.value)
    detailApplicant.value = { ...detailApplicant.value, documents_verified: verificationForm.value.documents_verified, verification_notes: verificationForm.value.verification_notes, status: verificationForm.value.documents_verified ? 'verified' : 'verification' }
    toast.success('Verifikasi berhasil disimpan')
    loadApplicants()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menyimpan verifikasi')
  } finally {
    verificationSubmitting.value = false
  }
}

function confirmDeleteApplicant(a) {
  deleteApplicantTarget.value = a
}

async function doDeleteApplicant() {
  if (!deleteApplicantTarget.value) return
  const id = deleteApplicantTarget.value.id
  try {
    await ppdbApplicantApi.delete(id)
    toast.success('Calon dihapus')
    deleteApplicantTarget.value = null
    if (detailApplicant.value?.id === id) detailApplicant.value = null
    loadApplicants()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menghapus')
  }
}

onMounted(() => {
  referenceStore.getAcademicYears()
  loadPeriods()
  loadChannels()
  if (activeTab.value === 'applicants') loadApplicants()
})
</script>

<style scoped>
.ppdb-page { padding: 0 1.25rem 2.5rem; max-width: 1400px; margin: 0 auto; }
.page-header {
  position: relative;
  margin: -1.25rem -1.25rem 0;
  margin-bottom: 1.5rem;
  padding: 1.5rem 1.5rem 1.75rem;
  border-radius: 0 0 20px 20px;
  overflow: hidden;
}
.header-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #7c3aed 100%);
  opacity: 0.97;
}
.header-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.header-left { display: flex; align-items: center; gap: 1rem; }
.header-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.header-icon { color: #fff; }
.page-title { margin: 0; font-size: 1.6rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.page-subtitle { margin: 0.35rem 0 0; color: rgba(255,255,255,0.9); font-size: 0.9rem; }
.header-actions { display: flex; gap: 0.5rem; }
.btn-header-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.1rem;
  background: #fff;
  color: #4f46e5;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: transform 0.05s, box-shadow 0.2s;
}
.btn-header-primary:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.15); transform: translateY(-1px); }

.nav-tabs-wrap { margin-bottom: 1.25rem; }
.nav-tabs {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
  background: #f1f5f9;
  padding: 0.35rem;
  border-radius: 14px;
  width: fit-content;
}
.nav-tab {
  padding: 0.65rem 1.1rem;
  border: none;
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: background 0.2s, color 0.2s;
}
.nav-tab:hover { background: rgba(255,255,255,0.8); }
.nav-tab.active {
  background: #fff;
  color: #4f46e5;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.nav-tab-label { display: block; font-size: 0.95rem; }
.nav-tab-hint { font-size: 0.72rem; color: #64748b; margin-top: 0.15rem; }
.nav-tab.active .nav-tab-hint { color: #6366f1; }

.page-main { display: flex; flex-direction: column; gap: 1rem; }
.content-card {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9;
}
.filters-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.search-wrap { flex: 1; min-width: 200px; }
.search-input {
  width: 100%;
  padding: 0.6rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9rem;
}
.search-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.15); }
.filter-label { font-size: 0.9rem; font-weight: 500; color: #475569; }
.filter-select {
  padding: 0.6rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  min-width: 160px;
  font-size: 0.9rem;
  background: #fff;
}
.btn-export {
  padding: 0.6rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  font-weight: 500;
  cursor: pointer;
}
.btn-export:hover:not(:disabled) { background: #f8fafc; border-color: #6366f1; color: #4f46e5; }
.btn-export:disabled { opacity: 0.6; cursor: not-allowed; }

.table-wrap { overflow-x: auto; padding: 0; }
.table-wrap.content-card { padding: 0; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.data-table th {
  font-weight: 600;
  background: #f8fafc;
  color: #475569;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.data-table tbody tr:hover { background: #fafafa; }
.data-table td:first-child { font-weight: 500; }
.data-table code { background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.85rem; }
.status-badge {
  display: inline-block;
  padding: 0.3rem 0.6rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
}
.status-draft { background: #f1f5f9; color: #475569; }
.status-open, .status-submitted, .status-verification { background: #dbeafe; color: #1e40af; }
.status-verified, .status-passed { background: #d1fae5; color: #065f46; }
.status-closed, .status-finished { background: #e2e8f0; color: #475569; }
.status-rejected, .status-failed, .status-cancelled { background: #fee2e2; color: #b91c1c; }
.status-reserve { background: #fef3c7; color: #92400e; }
.status-converted { background: #d1fae5; color: #047857; }
.btn-action {
  padding: 0.35rem 0.65rem;
  margin-right: 0.35rem;
  margin-bottom: 0.25rem;
  border-radius: 8px;
  cursor: pointer;
  border: 1px solid #e2e8f0;
  font-size: 0.82rem;
  font-weight: 500;
  background: #fff;
}
.btn-action:hover { background: #f8fafc; }
.btn-edit { color: #475569; }
.btn-delete { color: #b91c1c; border-color: #fecaca; }
.btn-delete:hover { background: #fef2f2; }
.btn-primary-sm {
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  color: #fff;
  border: none;
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  cursor: pointer;
  margin-right: 0.35rem;
  font-size: 0.82rem;
  font-weight: 600;
}
.btn-primary-sm:hover { opacity: 0.95; }
.action-row { display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0.5rem 0; }

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
}
.empty-icon { font-size: 3rem; margin-bottom: 1rem; line-height: 1; }
.empty-state h3 { margin: 0 0 0.5rem; font-size: 1.2rem; color: #1e293b; }
.empty-state p { margin: 0 0 1.25rem; color: #64748b; }
.empty-state-sm { padding: 2rem; }
.empty-state .btn-primary {
  padding: 0.7rem 1.5rem;
  background: linear-gradient(135deg, #4f46e5, #6366f1);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
.empty-state .btn-primary:hover { opacity: 0.95; }

.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
}
.pagination-info { font-size: 0.9rem; color: #64748b; }
.pagination-btns { display: flex; gap: 0.5rem; }
.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  font-weight: 500;
  cursor: pointer;
}
.pagination-btn:hover:not(:disabled) { background: #f8fafc; border-color: #6366f1; color: #4f46e5; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.loading-wrap { padding: 2rem; }
.stats-dashboard { padding: 1.75rem; }
.stats-cards { display: flex; gap: 1rem; margin-bottom: 1.75rem; flex-wrap: wrap; }
.stats-card {
  background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  min-width: 160px;
  border: 1px solid #e9d5ff;
}
.stats-total .stats-value { font-size: 2rem; font-weight: 800; display: block; color: #5b21b6; }
.stats-label { font-size: 0.9rem; color: #6d28d9; font-weight: 500; }
.stats-section { margin-bottom: 1.5rem; }
.stats-section h4 { margin: 0 0 0.75rem; font-size: 0.95rem; color: #334155; font-weight: 600; }
.stats-grid { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; }
.stats-row { display: flex; align-items: center; gap: 0.5rem; }
.stats-row .status-badge { margin-right: 0.25rem; }
.empty-stats-msg { color: #64748b; margin: 0.5rem 0; }
.stats-empty { color: #64748b; margin: 0.25rem 0; }
.data-table-compact { max-width: 380px; }
.data-table-compact th, .data-table-compact td { padding: 0.5rem 0.75rem; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,23,42,0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.5rem;
}
.modal-content {
  background: #fff;
  border-radius: 20px;
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2);
}
.form-modal-wide { max-width: 680px; }
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #fafafa;
  border-radius: 20px 20px 0 0;
}
.modal-header h3 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e293b; }
.btn-close {
  background: none;
  border: none;
  font-size: 1.75rem;
  cursor: pointer;
  padding: 0 0.25rem;
  color: #64748b;
  line-height: 1;
}
.btn-close:hover { color: #1e293b; }
.modal-body { padding: 1.5rem; }
.form-group { margin-bottom: 1.1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; font-size: 0.9rem; color: #475569; }
.form-group input[type="text"], .form-group input[type="email"], .form-group input[type="number"], .form-group input[type="date"], .form-group textarea, .form-select {
  width: 100%;
  padding: 0.6rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
}
.form-group input:focus, .form-select:focus, .form-group textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99,102,241,0.12);
}
.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
.form-row .form-group { flex: 1; min-width: 140px; }
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #fafafa;
  border-radius: 0 0 20px 20px;
}
.btn-primary, .btn-secondary {
  padding: 0.65rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.95rem;
}
.btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border: none; }
.btn-primary:hover:not(:disabled) { opacity: 0.95; }
.btn-secondary { background: #fff; border: 1px solid #e2e8f0; color: #475569; }
.btn-secondary:hover { background: #f8fafc; }
.error-message { color: #b91c1c; font-size: 0.9rem; margin-bottom: 0.5rem; }
.doc-list { margin: 0.75rem 0; }
.doc-list ul { margin: 0.35rem 0 0 1.25rem; }
.link-download { margin-left: 0.5rem; color: #4f46e5; font-weight: 500; cursor: pointer; }
.link-download:hover { text-decoration: underline; }
</style>
