<template>
  <Layout>
    <div class="student-page">
      <div class="list-tabs">
        <button
          type="button"
          :class="['tab-btn', { active: !filters.only_trashed }]"
          @click="switchListTab(false)"
        >
          Daftar Siswa
        </button>
        <button
          type="button"
          :class="['tab-btn', { active: filters.only_trashed }]"
          @click="switchListTab(true)"
        >
          Kotak Sampah
        </button>
      </div>
      <div class="toolbar">
        <div v-if="!filters.only_trashed" class="filters filters-inline">
          <input 
            v-model="filters.search" 
            @input="loadStudents(1)"
            placeholder="Cari nama, NIK, NIS, NISN..."
            class="search-input"
          />
          <select v-model="filters.class_id" @change="loadStudents(1)" class="filter-select">
            <option value="">Semua Kelas</option>
            <option value="__none__">Tanpa Kelas</option>
            <option v-for="c in filterClassList" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-if="availableStudentGrades.length" v-model="filters.tingkat" @change="loadStudents(1)" class="filter-select">
            <option value="">Semua Tingkat</option>
            <option value="__none__">Tanpa Tingkat</option>
            <option v-for="grade in availableStudentGrades" :key="grade" :value="grade">
              Tingkat {{ grade }}
            </option>
          </select>
          <select v-model="filters.status" @change="loadStudents(1)" class="filter-select">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Lulus">Lulus</option>
            <option value="Pindah">Pindah</option>
            <option value="Drop Out">Drop Out</option>
            <option value="Tidak Aktif">Tidak Aktif</option>
          </select>
          <select v-model="filters.account_status" @change="loadStudents(1)" class="filter-select">
            <option value="">Semua Akun</option>
            <option value="missing">Belum punya akun</option>
            <option value="incomplete">Data login belum lengkap</option>
            <option value="ready">Sudah punya akun</option>
          </select>
        </div>
        <div v-else class="filters filters-inline">
          <input
            v-model="filters.search"
            @input="loadStudents(1)"
            placeholder="Cari nama, NIK, NIS, NISN..."
            class="search-input"
          />
        </div>
        <div class="toolbar-actions">
          <template v-if="!filters.only_trashed">
          <button @click="exportToExcel" class="btn-secondary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Export</span>
          </button>
          <button @click="downloadTemplate" class="btn-secondary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Template</span>
          </button>
          <label for="import-excel" class="btn-secondary btn-compact cursor-pointer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Import</span>
          </label>
          <input type="file" id="import-excel" accept=".xlsx,.xls" class="input-hidden" @change="handleImportExcel">
          <button
            v-if="canManageStudentAccount && (accountStatus.missing_account > 0)"
            type="button"
            class="btn-secondary btn-compact"
            :disabled="bulkAccountLoading"
            @click="bulkEnsureAccounts"
          >
            <span>{{ bulkAccountLoading ? 'Membuat akun…' : `Buat akun (${accountStatus.missing_account})` }}</span>
          </button>
          <button @click="openAddModal" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Siswa</span>
          </button>
          </template>
        </div>
      </div>

      <div
        v-if="!filters.only_trashed && !loading && (accountStatus.missing_account > 0 || accountStatus.incomplete_data > 0)"
        class="account-status-banner"
      >
        <div class="account-status-text">
          <strong>Akun login siswa</strong>
          <span>
            {{ accountStatus.with_account }} sudah punya akun ·
            {{ accountStatus.missing_account }} siap dibuat ·
            {{ accountStatus.incomplete_data }} data belum lengkap (NIK/tgl lahir)
          </span>
          <span class="hint">Login: NIK · Sandi awal: tanggal lahir (DDMMYYYY)</span>
        </div>
        <div class="account-status-actions">
          <button
            v-if="accountStatus.missing_account > 0"
            type="button"
            class="btn-secondary btn-compact"
            @click="filters.account_status = 'missing'; loadStudents(1)"
          >
            Lihat tanpa akun
          </button>
          <button
            v-if="accountStatus.incomplete_data > 0"
            type="button"
            class="btn-secondary btn-compact"
            @click="filters.account_status = 'incomplete'; loadStudents(1)"
          >
            Lihat data kurang
          </button>
          <button
            v-if="canManageStudentAccount && accountStatus.missing_account > 0"
            type="button"
            class="btn-primary btn-compact"
            :disabled="bulkAccountLoading"
            @click="bulkEnsureAccounts"
          >
            {{ bulkAccountLoading ? 'Memproses…' : 'Buat akun massal' }}
          </button>
        </div>
      </div>

      <!-- Error state (list load failed) -->
      <div v-if="error && !loading" class="error-state">
        <p class="error-text">{{ error }}</p>
        <button @click="loadStudents()" class="btn-primary">Coba lagi</button>
      </div>

      <!-- Loading skeleton -->
      <div v-else-if="loading" class="loading-wrap">
        <StudentTableSkeleton />
      </div>

      <!-- Table / empty -->
      <div v-else class="content-wrapper">
        <StudentTable
          :students="students"
          :trash-mode="filters.only_trashed"
          :start-index="(pagination.current_page - 1) * pagination.per_page"
          :get-status-class="getStatusClass"
          :sort-by="filters.sort_by"
          :sort-dir="filters.sort_dir"
          @sort="setSort"
          @view="viewStudent"
          @edit="editStudent"
          @delete="deleteStudent"
          @add="openAddModal"
          @restore="handleRestoreStudent"
        >
          <template #empty>
            <template v-if="filters.only_trashed">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h3>Tidak ada data di kotak sampah</h3>
              <p>Data siswa yang dihapus akan muncul di sini dan dapat dipulihkan</p>
            </template>
            <template v-else>
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Belum ada data siswa</h3>
            <p>Mulai dengan menambahkan siswa baru</p>
            <button @click="openAddModal" class="btn-primary btn-compact">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Siswa</span>
            </button>
            </template>
          </template>
        </StudentTable>
        <div v-if="students.length > 0 && pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan
            {{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
            dari {{ pagination.total }} siswa
          </span>
          <div class="pagination-buttons">
            <button
              type="button"
              class="btn-page"
              :disabled="pagination.current_page <= 1"
              @click="goToPage(pagination.current_page - 1)"
            >
              Sebelumnya
            </button>
            <span class="page-num">
              Halaman {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              type="button"
              class="btn-page"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="goToPage(pagination.current_page + 1)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay form-modal-overlay" @click="closeModal">
        <div class="modal-content form-modal-content" @click.stop>
          <div class="form-modal-header">
            <div class="form-modal-title-wrap">
              <div class="form-modal-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <h3 class="form-modal-title">{{ showEditModal ? 'Edit' : 'Tambah' }} Siswa</h3>
                <p class="form-modal-subtitle">{{ showEditModal ? 'Perbarui data siswa' : 'Isi data siswa baru' }}</p>
              </div>
            </div>
            <button @click="closeModal" class="btn-close-modal" type="button" aria-label="Tutup">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
          
          <form @submit.prevent="handleSubmit" class="form-modal-body">
            <!-- Tabs Navigation -->
            <div class="form-tabs-nav">
              <button 
                type="button"
                @click="activeTab = 1" 
                :class="['form-tab-btn', { active: activeTab === 1 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Identitas</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 2" 
                :class="['form-tab-btn', { active: activeTab === 2 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Tambahan</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 3" 
                :class="['form-tab-btn', { active: activeTab === 3 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Data Ayah</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 4" 
                :class="['form-tab-btn', { active: activeTab === 4 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Data Ibu</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 5" 
                :class="['form-tab-btn', { active: activeTab === 5 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Data Wali</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 6" 
                :class="['form-tab-btn', { active: activeTab === 6 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Dokumen</span>
              </button>
            </div>

            <!-- Tab 1: Identitas -->
            <div v-show="activeTab === 1" class="form-tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>NIK <span class="required">*</span></label>
                  <input v-model="form.nik" required placeholder="Nomor Induk Kependudukan" />
                </div>
                <div class="form-group">
                  <label>NISN</label>
                  <input v-model="form.nisn" placeholder="Nomor Induk Siswa Nasional" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>NIS</label>
                  <input v-model="form.nis" placeholder="Nomor Induk Siswa" />
                </div>
                <div class="form-group">
                  <label>Nama Lengkap <span class="required">*</span></label>
                  <input v-model="form.name" required placeholder="Nama lengkap siswa" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Jenis Kelamin <span class="required">*</span></label>
                  <select v-model="form.gender" required>
                    <option value="">Pilih jenis kelamin</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Tempat Lahir <span class="required">*</span></label>
                  <input v-model="form.birth_place" required placeholder="Kota/kabupaten" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Tanggal Lahir <span class="required">*</span></label>
                  <input type="date" v-model="form.birth_date" required />
                </div>
                <div v-if="availableStudentGrades.length" class="form-group">
                  <label>Tingkat <span class="required">*</span></label>
                  <select v-model.number="form.tingkat" required @change="onTingkatChange">
                    <option :value="null">Pilih tingkat</option>
                    <option v-for="grade in availableStudentGrades" :key="grade" :value="grade">
                      Tingkat {{ grade }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Tab 2: Tambahan -->
            <div v-show="activeTab === 2" class="form-tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>No KK</label>
                  <input v-model="form.no_kk" placeholder="Nomor Kartu Keluarga" />
                </div>
                <div class="form-group">
                  <label>Cita-cita</label>
                  <select v-model="form.aspiration">
                    <option value="">Pilih</option>
                    <option value="Dokter">Dokter</option>
                    <option value="Guru">Guru</option>
                    <option value="Insinyur">Insinyur</option>
                    <option value="Polisi">Polisi</option>
                    <option value="Tentara">Tentara</option>
                    <option value="Pilot">Pilot</option>
                    <option value="Arsitek">Arsitek</option>
                    <option value="Pengusaha">Pengusaha</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Hobi</label>
                  <select v-model="form.hobby">
                    <option value="">Pilih</option>
                    <option value="Membaca">Membaca</option>
                    <option value="Menulis">Menulis</option>
                    <option value="Olahraga">Olahraga</option>
                    <option value="Musik">Musik</option>
                    <option value="Seni">Seni</option>
                    <option value="Fotografi">Fotografi</option>
                    <option value="Berkebun">Berkebun</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Agama</label>
                  <select v-model="form.religion">
                    <option value="">Pilih</option>
                    <option value="Islam">Islam</option>
                    <option value="Kristen">Kristen</option>
                    <option value="Katolik">Katolik</option>
                    <option value="Hindu">Hindu</option>
                    <option value="Buddha">Buddha</option>
                    <option value="Konghucu">Konghucu</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Disabilitas</label>
                  <select v-model="form.disability">
                    <option value="">Pilih</option>
                    <option value="Tidak Ada">Tidak Ada</option>
                    <option value="Tuna Netra">Tuna Netra</option>
                    <option value="Tuna Rungu">Tuna Rungu</option>
                    <option value="Tuna Wicara">Tuna Wicara</option>
                    <option value="Tuna Daksa">Tuna Daksa</option>
                    <option value="Tuna Grahita">Tuna Grahita</option>
                    <option value="Tuna Laras">Tuna Laras</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Tempat Tinggal</label>
                  <select v-model="form.residence_type">
                    <option value="">Pilih</option>
                    <option value="asrama">Asrama</option>
                    <option value="kost_kontrak">Kost/Kontrak</option>
                    <option value="tinggal_dengan_orang_tua">Tinggal dengan Orang Tua</option>
                    <option value="lainnya">Lainnya</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Tinggi Badan (cm)</label>
                  <input type="number" v-model.number="form.height" min="0" max="300" />
                </div>
                <div class="form-group">
                  <label>Berat Badan (kg)</label>
                  <input type="number" v-model.number="form.weight" min="0" max="500" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Asal Sekolah</label>
                  <input v-model="form.previous_school" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Kelas</label>
                  <template v-if="formClassListLoading">
                    <div class="form-field-skeleton" aria-hidden="true">
                      <div class="skeleton-line-inline"></div>
                    </div>
                    <small class="text-muted">Memuat daftar kelas...</small>
                  </template>
                  <template v-else>
                    <select
                      v-model="form.class_id"
                      @change="onFormClassChange"
                    >
                      <option :value="null">Pilih Kelas</option>
                      <option
                        v-for="c in matchingFormClassList"
                        :key="c.id"
                        :value="c.id"
                      >
                        {{ c.grade != null ? `Tingkat ${c.grade} - ${c.name}` : c.name }}
                      </option>
                    </select>
                  </template>
                  <small v-if="!formClassListLoading && ((showAddModal && !myInstitution?.active_semester_id) || (showEditModal && !form.semester_id))" class="text-muted">Semester belum ditetapkan; pilih tahun ajaran/semester aktif di pengaturan institusi.</small>
                </div>
              </div>
            </div>

            <!-- Tab 3: Data Ayah Kandung -->
            <div v-show="activeTab === 3" class="form-tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="form.father_status">
                    <option value="">Pilih</option>
                    <option value="masih_hidup">Masih Hidup</option>
                    <option value="meninggal_dunia">Meninggal Dunia</option>
                    <option value="tidak_diketahui">Tidak Diketahui</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>NIK</label>
                  <input v-model="form.father_nik" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Nama Lengkap</label>
                  <input v-model="form.father_name" />
                </div>
                <div class="form-group">
                  <label>Tempat Lahir</label>
                  <input v-model="form.father_birth_place" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Tanggal Lahir</label>
                  <input type="date" v-model="form.father_birth_date" />
                </div>
                <div class="form-group">
                  <label>Pendidikan</label>
                  <select v-model="form.father_education">
                    <option value="">Pilih</option>
                    <option value="Tidak Sekolah">Tidak Sekolah</option>
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                    <option value="SMA">SMA</option>
                    <option value="D1">D1</option>
                    <option value="D2">D2</option>
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Pekerjaan</label>
                  <select v-model="form.father_occupation">
                    <option value="">Pilih</option>
                    <option value="Tidak Bekerja">Tidak Bekerja</option>
                    <option value="PNS">PNS</option>
                    <option value="TNI/Polri">TNI/Polri</option>
                    <option value="Swasta">Swasta</option>
                    <option value="Wiraswasta">Wiraswasta</option>
                    <option value="Petani">Petani</option>
                    <option value="Nelayan">Nelayan</option>
                    <option value="Buruh">Buruh</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Penghasilan per Bulan (Rp)</label>
                  <input type="number" v-model.number="form.father_income" min="0" />
                </div>
              </div>
            </div>

            <!-- Tab 4: Data Ibu Kandung -->
            <div v-show="activeTab === 4" class="form-tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="form.mother_status">
                    <option value="">Pilih</option>
                    <option value="masih_hidup">Masih Hidup</option>
                    <option value="meninggal_dunia">Meninggal Dunia</option>
                    <option value="tidak_diketahui">Tidak Diketahui</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>NIK</label>
                  <input v-model="form.mother_nik" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Nama Lengkap</label>
                  <input v-model="form.mother_name" />
                </div>
                <div class="form-group">
                  <label>Tempat Lahir</label>
                  <input v-model="form.mother_birth_place" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Tanggal Lahir</label>
                  <input type="date" v-model="form.mother_birth_date" />
                </div>
                <div class="form-group">
                  <label>Pendidikan</label>
                  <select v-model="form.mother_education">
                    <option value="">Pilih</option>
                    <option value="Tidak Sekolah">Tidak Sekolah</option>
                    <option value="SD">SD</option>
                    <option value="SMP">SMP</option>
                    <option value="SMA">SMA</option>
                    <option value="D1">D1</option>
                    <option value="D2">D2</option>
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Pekerjaan</label>
                  <select v-model="form.mother_occupation">
                    <option value="">Pilih</option>
                    <option value="Tidak Bekerja">Tidak Bekerja</option>
                    <option value="PNS">PNS</option>
                    <option value="TNI/Polri">TNI/Polri</option>
                    <option value="Swasta">Swasta</option>
                    <option value="Wiraswasta">Wiraswasta</option>
                    <option value="Petani">Petani</option>
                    <option value="Nelayan">Nelayan</option>
                    <option value="Buruh">Buruh</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Penghasilan per Bulan (Rp)</label>
                  <input type="number" v-model.number="form.mother_income" min="0" />
                </div>
              </div>
            </div>

            <!-- Tab 5: Data Wali -->
            <div v-show="activeTab === 5" class="form-tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>Wali</label>
                  <select v-model="form.guardian_type" @change="handleGuardianTypeChange">
                    <option value="">Pilih</option>
                    <option value="sama_dengan_ayah">Sama dengan Ayah Kandung</option>
                    <option value="sama_dengan_ibu">Sama dengan Ibu Kandung</option>
                    <option value="lainnya">Lainnya</option>
                  </select>
                </div>
              </div>

              <div v-if="form.guardian_type === 'lainnya'" class="guardian-form">
                <div class="form-row">
                  <div class="form-group">
                    <label>Status</label>
                    <select v-model="form.guardian_status">
                      <option value="">Pilih</option>
                      <option value="masih_hidup">Masih Hidup</option>
                      <option value="meninggal_dunia">Meninggal Dunia</option>
                      <option value="tidak_diketahui">Tidak Diketahui</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>NIK</label>
                    <input v-model="form.guardian_nik" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input v-model="form.guardian_name" />
                  </div>
                  <div class="form-group">
                    <label>Telepon</label>
                    <input v-model="form.guardian_phone" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input v-model="form.guardian_birth_place" />
                  </div>
                  <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" v-model="form.guardian_birth_date" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Pendidikan</label>
                    <select v-model="form.guardian_education">
                      <option value="">Pilih</option>
                      <option value="Tidak Sekolah">Tidak Sekolah</option>
                      <option value="SD">SD</option>
                      <option value="SMP">SMP</option>
                      <option value="SMA">SMA</option>
                      <option value="D1">D1</option>
                      <option value="D2">D2</option>
                      <option value="D3">D3</option>
                      <option value="D4">D4</option>
                      <option value="S1">S1</option>
                      <option value="S2">S2</option>
                      <option value="S3">S3</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Pekerjaan</label>
                    <select v-model="form.guardian_occupation">
                      <option value="">Pilih</option>
                      <option value="Tidak Bekerja">Tidak Bekerja</option>
                      <option value="PNS">PNS</option>
                      <option value="TNI/Polri">TNI/Polri</option>
                      <option value="Swasta">Swasta</option>
                      <option value="Wiraswasta">Wiraswasta</option>
                      <option value="Petani">Petani</option>
                      <option value="Nelayan">Nelayan</option>
                      <option value="Buruh">Buruh</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Penghasilan per Bulan (Rp)</label>
                    <input type="number" v-model.number="form.guardian_income" min="0" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 6: Dokumen -->
            <div v-show="activeTab === 6" class="form-tab-content">
              <div class="documents-section">
                <div v-if="!editingId" class="no-documents">
                  <p>Simpan data siswa terlebih dahulu untuk mengupload dokumen</p>
                </div>
                <template v-else>
                  <div class="upload-section">
                    <h3>Upload Dokumen</h3>
                    <div class="form-group">
                      <label>Nama Dokumen *</label>
                      <input v-model="documentForm.name" placeholder="Contoh: Kartu Keluarga, Ijazah, dll" />
                    </div>
                    <div class="form-group">
                      <label>Deskripsi</label>
                      <textarea v-model="documentForm.description" rows="3" placeholder="Deskripsi dokumen (opsional)"></textarea>
                    </div>
                    <div class="form-group">
                      <label>File *</label>
                      <input 
                        type="file" 
                        ref="fileInput"
                        @change="handleFileSelect"
                        accept=".jpg,.jpeg,.png,.pdf"
                      />
                      <small class="text-muted">Format: JPG, PNG, PDF. Maksimal 2 MB per file. Maksimal 20 file.</small>
                    </div>
                    <div v-if="selectedFile" class="file-preview">
                      <p><strong>File terpilih:</strong> {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})</p>
                    </div>
                    <button 
                      @click="uploadDocument" 
                      :disabled="uploadingDocument || !documentForm.name || !selectedFile"
                      class="btn btn-primary"
                      type="button"
                    >
                      {{ uploadingDocument ? 'Mengupload...' : 'Upload Dokumen' }}
                    </button>
                  </div>

                  <div class="documents-list" v-if="currentStudentDocuments && currentStudentDocuments.length > 0">
                    <h3>Daftar Dokumen ({{ currentStudentDocuments.length }}/20)</h3>
                    <div class="document-grid">
                      <div v-for="doc in currentStudentDocuments" :key="doc.id" class="document-card">
                        <div class="document-icon">
                          <svg v-if="doc.mime_type === 'application/pdf'" width="32" height="32" viewBox="0 0 24 24" fill="none">
                            <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2"/>
                          </svg>
                          <svg v-else width="32" height="32" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M3 9H21" stroke="currentColor" stroke-width="2"/>
                          </svg>
                        </div>
                        <div class="document-info">
                          <h4>{{ doc.name }}</h4>
                          <p class="text-muted">{{ doc.file_name }}</p>
                          <p class="text-muted">{{ doc.file_size_human || formatFileSize(doc.file_size) }}</p>
                          <p v-if="doc.description" class="text-muted">{{ doc.description }}</p>
                        </div>
                        <div class="document-actions">
                          <button @click="downloadDocument(doc.id)" class="btn btn-sm btn-secondary" type="button">Download</button>
                          <button @click="deleteDocument(doc.id)" class="btn btn-sm btn-danger" type="button">Hapus</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="no-documents">
                    <p>Belum ada dokumen yang diupload</p>
                  </div>
                </template>
              </div>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="form-modal-footer">
              <button type="button" @click="closeModal" class="btn-ghost">Batal</button>
              <div class="form-modal-footer-actions">
                <button v-if="activeTab > 1" type="button" @click="activeTab--" class="btn-outline">Sebelumnya</button>
                <button v-if="activeTab < 6" type="button" @click="activeTab++" class="btn-outline">Selanjutnya</button>
                <button v-if="activeTab !== 6" type="submit" :disabled="saving" class="btn-submit">
                  <span v-if="saving" class="btn-spinner"></span>
                  <span>{{ saving ? 'Menyimpan...' : 'Simpan' }}</span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- View Student Modal -->
      <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
        <div class="modal-content view-modal" @click.stop id="student-biodata">
          <div class="modal-header">
            <h3>Biodata Lengkap Siswa</h3>
            <div class="header-actions">
              <button @click="downloadBukuIndukPdf" class="btn-print" title="Cetak Buku Induk (PDF)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Buku Induk (PDF)</span>
              </button>
              <button @click="printPDF" class="btn-print btn-print-biodata" title="Cetak Biodata">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cetak Biodata</span>
              </button>
              <router-link v-if="viewingStudent" :to="{ name: 'BukuInduk', params: { id: viewingStudent.id } }" class="btn-buku-induk-link" @click="closeViewModal">Lihat Buku Induk →</router-link>
              <button @click="closeViewModal" class="btn-close">×</button>
            </div>
          </div>
          
          <div class="view-body" v-if="viewingStudent">
            <!-- Identitas Siswa -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Identitas Siswa
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingStudent.nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIS</span>
                  <span class="value">{{ viewingStudent.nis || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NISN</span>
                  <span class="value">{{ viewingStudent.nisn || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nama Lengkap</span>
                  <span class="value">{{ viewingStudent.name || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Jenis Kelamin</span>
                  <span class="value">{{ viewingStudent.gender === 'L' ? 'Laki-laki' : viewingStudent.gender === 'P' ? 'Perempuan' : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Lahir</span>
                  <span class="value">{{ viewingStudent.birth_place || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Lahir</span>
                  <span class="value">{{ formatDate(viewingStudent.birth_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Alamat</span>
                  <span class="value">{{ viewingStudent.address || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Telepon</span>
                  <span class="value">{{ viewingStudent.phone || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Email</span>
                  <span class="value">{{ viewingStudent.email || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Kelas</span>
                  <span class="value">{{ viewingStudent.class || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tingkat</span>
                  <span class="value">{{ viewingStudent.tingkat ?? '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tahun Ajaran</span>
                  <span class="value">{{ viewingStudent.academic_year || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value" :class="getStatusClass(viewingStudent.status)">{{ viewingStudent.status || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Akun Login -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Akun Login
              </h4>
              <div class="biodata-grid" v-if="viewingStudent.has_user_account && viewingStudent.user_account">
                <div class="biodata-item">
                  <span class="label">Login (NIK)</span>
                  <span class="value">{{ viewingStudent.user_account.login_nik || viewingStudent.nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Sandi awal</span>
                  <span class="value">Tanggal lahir (DDMMYYYY)</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Wajib ganti sandi</span>
                  <span class="value">{{ viewingStudent.user_account.must_change_password ? 'Ya' : 'Tidak' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Status akun</span>
                  <span class="value">{{ viewingStudent.user_account.is_active === false ? 'Nonaktif' : 'Aktif' }}</span>
                </div>
                <div class="biodata-item full-width" v-if="canResetStudentAccount">
                  <span class="label">Reset sandi</span>
                  <span class="value">
                    <button
                      type="button"
                      class="btn-reset-password"
                      :disabled="studentAccountLoading"
                      @click="resetStudentPassword"
                    >
                      {{ studentAccountLoading ? 'Memproses...' : 'Reset ke tanggal lahir' }}
                    </button>
                    <span class="hint">Siswa wajib ganti sandi saat login berikutnya.</span>
                  </span>
                </div>
              </div>
              <div class="biodata-grid" v-else>
                <div class="biodata-item full-width">
                  <span class="value hint">
                    Akun login belum dibuat. Pastikan NIK dan tanggal lahir terisi, lalu buat akun.
                  </span>
                </div>
                <div class="biodata-item full-width" v-if="canManageStudentAccount">
                  <span class="value">
                    <button
                      type="button"
                      class="btn-reset-password"
                      :disabled="studentAccountLoading"
                      @click="ensureStudentAccount"
                    >
                      {{ studentAccountLoading ? 'Memproses...' : 'Buat akun login' }}
                    </button>
                  </span>
                </div>
              </div>
            </div>

            <!-- Data Tambahan -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Data Tambahan
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">No KK</span>
                  <span class="value">{{ viewingStudent.no_kk || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Cita-cita</span>
                  <span class="value">{{ viewingStudent.aspiration || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Hobi</span>
                  <span class="value">{{ viewingStudent.hobby || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Agama</span>
                  <span class="value">{{ viewingStudent.religion || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Disabilitas</span>
                  <span class="value">{{ viewingStudent.disability || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Tinggal</span>
                  <span class="value">{{ viewingStudent.residence_type ? formatResidenceType(viewingStudent.residence_type) : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tinggi Badan</span>
                  <span class="value">{{ viewingStudent.height ? viewingStudent.height + ' cm' : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Berat Badan</span>
                  <span class="value">{{ viewingStudent.weight ? viewingStudent.weight + ' kg' : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Asal Sekolah</span>
                  <span class="value">{{ viewingStudent.previous_school || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Catatan</span>
                  <span class="value">{{ viewingStudent.notes || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Data Ayah -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Data Ayah Kandung
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value">{{ viewingStudent.father_status ? formatStatus(viewingStudent.father_status) : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingStudent.father_nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nama Lengkap</span>
                  <span class="value">{{ viewingStudent.father_name || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Lahir</span>
                  <span class="value">{{ viewingStudent.father_birth_place || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Lahir</span>
                  <span class="value">{{ formatDate(viewingStudent.father_birth_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pendidikan</span>
                  <span class="value">{{ viewingStudent.father_education || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pekerjaan</span>
                  <span class="value">{{ viewingStudent.father_occupation || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Penghasilan per Bulan</span>
                  <span class="value">{{ viewingStudent.father_income ? 'Rp ' + formatCurrency(viewingStudent.father_income) : '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Data Ibu -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Data Ibu Kandung
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value">{{ viewingStudent.mother_status ? formatStatus(viewingStudent.mother_status) : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingStudent.mother_nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nama Lengkap</span>
                  <span class="value">{{ viewingStudent.mother_name || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Lahir</span>
                  <span class="value">{{ viewingStudent.mother_birth_place || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Lahir</span>
                  <span class="value">{{ formatDate(viewingStudent.mother_birth_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pendidikan</span>
                  <span class="value">{{ viewingStudent.mother_education || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pekerjaan</span>
                  <span class="value">{{ viewingStudent.mother_occupation || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Penghasilan per Bulan</span>
                  <span class="value">{{ viewingStudent.mother_income ? 'Rp ' + formatCurrency(viewingStudent.mother_income) : '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Data Wali -->
            <div class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Data Wali
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">Wali</span>
                  <span class="value">{{ viewingStudent.guardian_type ? formatGuardianType(viewingStudent.guardian_type) : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value">{{ viewingStudent.guardian_status ? formatStatus(viewingStudent.guardian_status) : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingStudent.guardian_nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nama Lengkap</span>
                  <span class="value">{{ viewingStudent.guardian_name || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Telepon</span>
                  <span class="value">{{ viewingStudent.guardian_phone || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Lahir</span>
                  <span class="value">{{ viewingStudent.guardian_birth_place || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Lahir</span>
                  <span class="value">{{ formatDate(viewingStudent.guardian_birth_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pendidikan</span>
                  <span class="value">{{ viewingStudent.guardian_education || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pekerjaan</span>
                  <span class="value">{{ viewingStudent.guardian_occupation || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Penghasilan per Bulan</span>
                  <span class="value">{{ viewingStudent.guardian_income ? 'Rp ' + formatCurrency(viewingStudent.guardian_income) : '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Riwayat Konseling (jika user punya akses modul konseling) -->
            <div v-if="canAccessCounseling" class="biodata-section">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Riwayat Konseling
              </h4>
              <div v-if="studentCounselingLoading" class="counseling-skeleton">
                <LoadingSkeleton
                  type="table"
                  :rows="3"
                  :columns="5"
                  :cell-widths="['90px', '120px', '90px', '90px', '200px']"
                />
              </div>
              <div v-else-if="!studentCounselingSessions.length" class="counseling-empty">
                <p>Belum ada sesi konseling.</p>
                <router-link v-if="viewingStudent" :to="{ path: '/counseling', query: { student_id: viewingStudent.id } }" class="link-counseling" @click="closeViewModal">Tambah Sesi Konseling →</router-link>
              </div>
              <div v-else class="counseling-table-wrap">
                <table class="counseling-table">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>Konselor</th>
                      <th>Jenis</th>
                      <th>Status</th>
                      <th>Ringkasan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="s in studentCounselingSessions" :key="s.id">
                      <td>{{ formatDate(s.session_date) }}</td>
                      <td>{{ s.counselor?.name || '-' }}</td>
                      <td>{{ s.counseling_type?.name || '-' }}</td>
                      <td><span :class="['counseling-status', 'status-' + s.status]">{{ counselingStatusLabel(s.status) }}</span></td>
                      <td class="summary-cell">{{ (s.summary || '-').slice(0, 60) }}{{ (s.summary && s.summary.length > 60) ? '…' : '' }}</td>
                    </tr>
                  </tbody>
                </table>
                <router-link v-if="viewingStudent" :to="{ path: '/counseling', query: { student_id: viewingStudent.id } }" class="link-counseling link-counseling-footer" @click="closeViewModal">Lihat semua & tambah sesi →</router-link>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              v-if="viewingStudent?.status === 'Aktif'"
              type="button"
              class="btn-primary"
              :disabled="graduatingStudent"
              @click="graduateFromView"
            >
              {{ graduatingStudent ? 'Memproses...' : 'Luluskan Siswa' }}
            </button>
            <button
              v-if="viewingStudent?.status === 'Lulus'"
              type="button"
              class="btn-danger"
              :disabled="graduatingStudent"
              @click="revokeGraduationFromView"
            >
              {{ graduatingStudent ? 'Memproses...' : 'Batal Lulus' }}
            </button>
            <button type="button" @click="closeViewModal" class="btn-secondary">Tutup</button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="importPreview.open" class="modal-overlay" @click="closeImportPreview">
      <div class="modal-content import-preview-modal" @click.stop>
        <div class="modal-header">
          <h3>Pratinjau Import Siswa</h3>
          <button type="button" class="btn-close" @click="closeImportPreview">×</button>
        </div>
        <div class="modal-body">
          <p class="modal-message">
            {{ importPreview.valid.length }} baris siap diimpor
            <template v-if="importPreview.invalid.length">
              · {{ importPreview.invalid.length }} baris dilewati
            </template>
          </p>
          <p class="hint">Akun login dibuat otomatis jika NIK (16 digit) dan tanggal lahir terisi.</p>
          <div v-if="importPreview.valid.length" class="import-preview-table-wrap">
            <table class="counseling-table">
              <thead>
                <tr>
                  <th>NIK</th>
                  <th>Nama</th>
                  <th>Tgl Lahir</th>
                  <th>Tingkat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in importPreview.valid.slice(0, 8)" :key="'v'+idx">
                  <td>{{ row.nik }}</td>
                  <td>{{ row.name }}</td>
                  <td>{{ row.birth_date }}</td>
                  <td>{{ row.tingkat }}</td>
                </tr>
              </tbody>
            </table>
            <p v-if="importPreview.valid.length > 8" class="hint">…dan {{ importPreview.valid.length - 8 }} baris lainnya</p>
          </div>
          <div v-if="importPreview.invalid.length" class="import-invalid-box">
            <strong>Baris tidak valid</strong>
            <ul>
              <li v-for="(row, idx) in importPreview.invalid.slice(0, 10)" :key="'i'+idx">
                Baris {{ row.row }}: {{ row.reason }}
              </li>
            </ul>
            <p v-if="importPreview.invalid.length > 10" class="hint">…dan {{ importPreview.invalid.length - 10 }} kesalahan lain</p>
          </div>
          <div v-if="importPreview.serverErrors?.length" class="import-invalid-box">
            <strong>Error dari server</strong>
            <ul>
              <li v-for="(err, idx) in importPreview.serverErrors.slice(0, 10)" :key="'e'+idx">{{ err }}</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" :disabled="importPreview.loading" @click="closeImportPreview">Batal</button>
          <button
            type="button"
            class="btn-primary"
            :disabled="importPreview.loading || !importPreview.valid.length"
            @click="confirmImportExcel"
          >
            {{ importPreview.loading ? 'Mengimpor…' : `Impor ${importPreview.valid.length} siswa` }}
          </button>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :confirm-text="confirmDialog.confirmText"
      :cancel-text="confirmDialog.cancelText"
      :loading-text="confirmDialog.loadingText"
      :confirm-variant="confirmDialog.confirmVariant"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    />
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import StudentTable from '@/components/student/StudentTable.vue'
import StudentTableSkeleton from '@/components/StudentTableSkeleton.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { useStudentList } from '@/composables/useStudentList'
import { useAuthStore } from '@/stores/auth'
import { studentApi } from '@/api/student'
import { alumniApi } from '@/api/alumni'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { counselingApi } from '@/api/counseling'
import { extracurricularApi } from '@/api/extracurricular'
import { validators } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { getInstitutionTypeLabel, getPrincipalTitle, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import * as XLSX from 'xlsx'

const toast = useToast()
const authStore = useAuthStore()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const {
  students,
  loading,
  error,
  filters,
  pagination,
  loadStudents: loadStudentsBase,
  buildListParams,
  setSort,
  goToPage,
  getStatusClass
} = useStudentList()

const accountStatus = ref({
  total: 0,
  with_account: 0,
  missing_account: 0,
  incomplete_data: 0,
})
const accountStatusLoading = ref(false)
const bulkAccountLoading = ref(false)
const importPreview = ref({
  open: false,
  loading: false,
  valid: [],
  invalid: [],
  serverErrors: [],
})

async function loadAccountStatus() {
  if (filters.value.only_trashed) return
  accountStatusLoading.value = true
  try {
    const params = { ...buildListParams(1) }
    delete params.page
    delete params.per_page
    delete params.account_status
    const res = await studentApi.accountStatus(params)
    accountStatus.value = {
      total: res.data?.data?.total ?? 0,
      with_account: res.data?.data?.with_account ?? 0,
      missing_account: res.data?.data?.missing_account ?? 0,
      incomplete_data: res.data?.data?.incomplete_data ?? 0,
    }
  } catch {
    /* ignore banner errors */
  } finally {
    accountStatusLoading.value = false
  }
}

async function loadStudents(page) {
  await loadStudentsBase(page)
  loadAccountStatus()
}

async function bulkEnsureAccounts() {
  if (bulkAccountLoading.value || !canManageStudentAccount.value) return
  const missing = accountStatus.value.missing_account || 0
  if (missing <= 0) {
    toast.success('Info', 'Tidak ada siswa yang siap dibuatkan akun')
    return
  }
  const ok = await showConfirm({
    title: 'Buat akun login massal?',
    message: `${missing} siswa dengan NIK & tanggal lahir lengkap akan dibuatkan akun. Sandi awal = tanggal lahir (DDMMYYYY).`,
    warning: 'Siswa wajib ganti sandi saat login pertama.',
    confirmText: 'Buat akun',
    cancelText: 'Batal',
    confirmVariant: 'primary',
  })
  if (!ok) return

  bulkAccountLoading.value = true
  try {
    const payload = {
      only_missing: true,
      limit: 2000,
      class_id: filters.value.class_id || undefined,
      tingkat: filters.value.tingkat || undefined,
      status: filters.value.status || 'Aktif',
    }
    const res = await studentApi.ensureAccountsBulk(payload)
    toast.success('Berhasil', res.data?.message || 'Akun massal selesai diproses')
    const skipped = res.data?.data?.errors || []
    if (skipped.length) {
      toast.error('Sebagian dilewati', `${skipped.length} siswa tidak bisa dibuatkan akun (cek NIK/tgl lahir)`)
    }
    await loadStudents(1)
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal membuat akun massal')
  } finally {
    bulkAccountLoading.value = false
  }
}
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const viewingStudent = ref(null)
const graduatingStudent = ref(false)
const studentCounselingSessions = ref([])
const studentCounselingLoading = ref(false)
const studentExtracurricularEnrollments = ref([])
const studentExtracurricularLoading = ref(false)
const myInstitution = ref(null)
const formClassList = ref([])
const formClassListLoading = ref(false)
const filterClassList = ref([])

const availableStudentGrades = computed(() => {
  const level = myInstitution.value?.level
  if (level === 'SD' || level === 'MI') return [1, 2, 3, 4, 5, 6]
  if (level === 'SMP' || level === 'MTs') return [7, 8, 9]
  if (level === 'SMA' || level === 'MA' || level === 'MAK' || level === 'SMK') return [10, 11, 12]
  return []
})

const canAccessCounseling = computed(() => {
  const role = authStore.user?.role
  if (!role) return false
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  return (authStore.user?.permissions || []).includes('counseling')
})

const canAccessExtracurricular = computed(() => {
  const role = authStore.user?.role
  if (!role) return false
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  return (authStore.user?.permissions || []).includes('extracurricular')
})

const canManageStudentAccount = computed(() => {
  const role = authStore.user?.role
  if (role === 'super_admin' || role === 'admin' || role === 'institution_admin') return true
  return (authStore.user?.permissions || []).includes('student')
})

const canResetStudentAccount = computed(() => {
  const role = authStore.user?.role
  return role === 'super_admin' || role === 'admin' || role === 'institution_admin'
})

const studentAccountLoading = ref(false)

async function ensureStudentAccount() {
  if (!viewingStudent.value?.id || studentAccountLoading.value) return
  studentAccountLoading.value = true
  try {
    const res = await studentApi.ensureAccount(viewingStudent.value.id)
    if (res.data?.data) {
      viewingStudent.value = res.data.data
    }
    toast.success('Berhasil', res.data?.message || 'Akun login siswa siap digunakan')
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Tidak dapat membuat akun login siswa')
  } finally {
    studentAccountLoading.value = false
  }
}

async function resetStudentPassword() {
  if (!viewingStudent.value?.id || studentAccountLoading.value) return
  const ok = await showConfirm({
    title: 'Reset sandi siswa?',
    message: 'Sandi akan dikembalikan ke tanggal lahir (DDMMYYYY). Siswa wajib ganti sandi saat login berikutnya.',
    warning: '',
    confirmText: 'Reset sandi',
    cancelText: 'Batal',
    confirmVariant: 'primary'
  })
  if (!ok) return
  studentAccountLoading.value = true
  try {
    const res = await studentApi.resetPassword(viewingStudent.value.id)
    if (res.data?.data) {
      viewingStudent.value = res.data.data
    }
    toast.success('Berhasil', res.data?.message || 'Sandi berhasil direset')
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Tidak dapat mereset sandi siswa')
  } finally {
    studentAccountLoading.value = false
  }
}

const saving = ref(false)
const deleteLoading = ref(false)
const activeTab = ref(1)

const form = ref({
  nik: '',
  nis: '',
  nisn: '',
  name: '',
  gender: '',
  birth_date: '',
  birth_place: '',
  address: '',
  phone: '',
  email: '',
  religion: '',
  no_kk: '',
  aspiration: '',
  hobby: '',
  disability: '',
  height: null,
  weight: null,
  previous_school: '',
  residence_type: '',
  tingkat: null,
  class: '',
  class_id: null,
  academic_year: '',
  academic_year_id: null,
  semester_id: null,
  status: 'Aktif',
  father_name: '',
  father_status: '',
  father_nik: '',
  father_birth_place: '',
  father_birth_date: '',
  father_education: '',
  father_occupation: '',
  father_income: null,
  mother_name: '',
  mother_status: '',
  mother_nik: '',
  mother_birth_place: '',
  mother_birth_date: '',
  mother_education: '',
  mother_occupation: '',
  mother_income: null,
  guardian_name: '',
  guardian_phone: '',
  guardian_type: '',
  guardian_status: '',
  guardian_nik: '',
  guardian_birth_place: '',
  guardian_birth_date: '',
  guardian_education: '',
  guardian_occupation: '',
  guardian_income: null,
  notes: ''
})

const matchingFormClassList = computed(() => {
  if (!availableStudentGrades.value.length) return formClassList.value
  if (form.value.tingkat === null || form.value.tingkat === '') return []
  return formClassList.value.filter(c => Number(c.grade) === Number(form.value.tingkat))
})

const documentForm = ref({
  name: '',
  description: ''
})
const selectedFile = ref(null)
const uploadingDocument = ref(false)
const currentStudentDocuments = ref([])
const fileInput = ref(null)

let editingId = null

/** Sorted class list helper (by grade then name). */
function sortClasses(list) {
  return [...(list || [])].sort((a, b) => {
    const ga = a.grade ?? 0
    const gb = b.grade ?? 0
    if (ga !== gb) return ga - gb
    return (a.name || '').localeCompare(b.name || '')
  })
}

/** Load classes for form dropdown: by semester (add = active semester, edit = student semester). */
async function loadFormClasses(semesterId) {
  if (!semesterId) {
    formClassList.value = []
    return
  }
  formClassListLoading.value = true
  formClassList.value = []
  try {
    const res = await classApi.getAll({ semester_id: semesterId, per_page: 200 })
    const list = res.data?.data ?? res.data ?? []
    formClassList.value = sortClasses(list)
  } catch {
    formClassList.value = []
  } finally {
    formClassListLoading.value = false
  }
}

/** Load classes for filter dropdown (active semester). */
async function loadFilterClasses() {
  try {
    const r = await institutionApi.getMy()
    const inst = r.data?.data ?? r.data ?? {}
    myInstitution.value = inst
    const sid = inst?.active_semester_id
    if (!sid) {
      filterClassList.value = []
      return
    }
    const res = await classApi.getAll({ semester_id: sid, per_page: 200 })
    const list = res.data?.data ?? res.data ?? []
    filterClassList.value = sortClasses(list)
  } catch {
    filterClassList.value = []
  }
}

/** Open add modal: load institution, then classes for active semester, then show modal. */
async function openAddModal() {
  formClassListLoading.value = true
  formClassList.value = []
  try {
    const r = await institutionApi.getMy()
    myInstitution.value = r.data?.data ?? r.data ?? {}
    const sid = myInstitution.value?.active_semester_id
    if (sid) await loadFormClasses(sid)
    else formClassList.value = []
  } catch {
    formClassList.value = []
  } finally {
    formClassListLoading.value = false
  }
  showAddModal.value = true
}

function normalizeAcademicYearLabel(value) {
  if (value && typeof value === 'object') {
    return value.code || value.name || ''
  }
  if (typeof value !== 'string' || !value) return ''
  const match = value.match(/(\d{4}\/\d{4})/)
  return match ? match[1] : value
}

/** When user selects a class, sync form.class, semester_id, academic_year_id from selected class. */
function onFormClassChange() {
  const id = form.value.class_id
  if (!id) {
    form.value.class = ''
    form.value.semester_id = null
    form.value.academic_year_id = null
    form.value.academic_year = ''
    return
  }
  const c = formClassList.value.find(x => x.id === id)
  if (c) {
    form.value.class = c.name || ''
    form.value.semester_id = c.semester_id ?? form.value.semester_id
    form.value.academic_year_id = c.academic_year_id ?? form.value.academic_year_id
    const yearLabel = normalizeAcademicYearLabel(c.academic_year)
    if (yearLabel) form.value.academic_year = yearLabel
  }
}

function onTingkatChange() {
  if (!form.value.class_id) return

  const selectedClass = formClassList.value.find(c => c.id === form.value.class_id)
  if (selectedClass && Number(selectedClass.grade) !== Number(form.value.tingkat)) {
    form.value.class_id = null
    form.value.class = ''
    form.value.semester_id = null
    form.value.academic_year_id = null
  }
}

const editStudent = async (student) => {
  editingId = student.id
  // Hanya salin field form — jangan Object.assign seluruh response (nested object bisa bikin validasi gagal)
  Object.keys(form.value).forEach((key) => {
    if (Object.prototype.hasOwnProperty.call(student, key)) {
      form.value[key] = student[key]
    }
  })
  form.value.academic_year = normalizeAcademicYearLabel(
    student.academic_year_detail?.code || student.academic_year
  )
  // Format dates
  if (student.birth_date) {
    form.value.birth_date = student.birth_date.split('T')[0]
  }
  if (student.father_birth_date) {
    form.value.father_birth_date = student.father_birth_date.split('T')[0]
  }
  if (student.mother_birth_date) {
    form.value.mother_birth_date = student.mother_birth_date.split('T')[0]
  }
  if (student.guardian_birth_date) {
    form.value.guardian_birth_date = student.guardian_birth_date.split('T')[0]
  }
  activeTab.value = 1
  showEditModal.value = true
  // Load kelas dropdown: semester siswa (edit)
  const semesterId = form.value.semester_id ?? (await institutionApi.getMy().then(r => (r.data?.data ?? r.data)?.active_semester_id))
  await loadFormClasses(semesterId)
  // Load dokumen
  await loadStudentDocuments()
}

const handleGuardianTypeChange = () => {
  if (form.value.guardian_type === 'sama_dengan_ayah') {
    // Copy father data to guardian
    form.value.guardian_name = form.value.father_name
    form.value.guardian_status = form.value.father_status
    form.value.guardian_nik = form.value.father_nik
    form.value.guardian_birth_place = form.value.father_birth_place
    form.value.guardian_birth_date = form.value.father_birth_date
    form.value.guardian_education = form.value.father_education
    form.value.guardian_occupation = form.value.father_occupation
    form.value.guardian_income = form.value.father_income
  } else if (form.value.guardian_type === 'sama_dengan_ibu') {
    // Copy mother data to guardian
    form.value.guardian_name = form.value.mother_name
    form.value.guardian_status = form.value.mother_status
    form.value.guardian_nik = form.value.mother_nik
    form.value.guardian_birth_place = form.value.mother_birth_place
    form.value.guardian_birth_date = form.value.mother_birth_date
    form.value.guardian_education = form.value.mother_education
    form.value.guardian_occupation = form.value.mother_occupation
    form.value.guardian_income = form.value.mother_income
  } else {
    // Clear guardian data if "lainnya"
    form.value.guardian_name = ''
    form.value.guardian_status = ''
    form.value.guardian_nik = ''
    form.value.guardian_birth_place = ''
    form.value.guardian_birth_date = ''
    form.value.guardian_education = ''
    form.value.guardian_occupation = ''
    form.value.guardian_income = null
  }
}

const deleteStudent = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus siswa ini?',
    warning: 'Data siswa akan dipindahkan ke kotak sampah dan dapat dipulihkan kapan saja dari menu Kotak Sampah.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await studentApi.delete(id)
    toast.success('Berhasil', 'Siswa berhasil dihapus')
    loadStudents()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus siswa')
  } finally {
    setDeleteLoading(false)
  }
}

function switchListTab(onlyTrashed) {
  filters.value.only_trashed = !!onlyTrashed
  loadStudents(1)
}

async function handleRestoreStudent(student) {
  try {
    await studentApi.restore(student.id)
    toast.success('Berhasil', 'Siswa berhasil dipulihkan')
    loadStudents()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memulihkan siswa')
  }
}

const validationRules = {
  nik: [
    (value) => validators.required(value, 'NIK wajib diisi')
  ],
  name: [
    (value) => validators.required(value, 'Nama lengkap wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama maksimal 255 karakter')
  ],
  gender: [
    (value) => validators.required(value, 'Jenis kelamin wajib diisi')
  ],
  birth_date: [
    (value) => validators.required(value, 'Tanggal lahir wajib diisi'),
    (value) => validators.date(value, 'Format tanggal tidak valid')
  ],
  birth_place: [
    (value) => validators.required(value, 'Tempat lahir wajib diisi')
  ],
  tingkat: [
    (value) => availableStudentGrades.value.length === 0 || validators.required(value, 'Tingkat siswa wajib diisi')
  ],
  email: [
    (value) => validators.email(value, 'Format email tidak valid'),
    (value) => validators.maxLength(value, 255, 'Email maksimal 255 karakter')
  ],
  phone: [
    (value) => validators.phone(value, 'Format nomor telepon tidak valid')
  ]
}

// Setup form validation
const { validateAll, setErrors } = useFormValidation({
  form,
  initialValues: {},
  rules: validationRules
})

const handleSubmit = async () => {
  error.value = ''
  
  // Sync guardian data if guardian_type is set
  if (form.value.guardian_type === 'sama_dengan_ayah') {
    form.value.guardian_name = form.value.father_name
    form.value.guardian_status = form.value.father_status
    form.value.guardian_nik = form.value.father_nik
    form.value.guardian_birth_place = form.value.father_birth_place
    form.value.guardian_birth_date = form.value.father_birth_date
    form.value.guardian_education = form.value.father_education
    form.value.guardian_occupation = form.value.father_occupation
    form.value.guardian_income = form.value.father_income
  } else if (form.value.guardian_type === 'sama_dengan_ibu') {
    form.value.guardian_name = form.value.mother_name
    form.value.guardian_status = form.value.mother_status
    form.value.guardian_nik = form.value.mother_nik
    form.value.guardian_birth_place = form.value.mother_birth_place
    form.value.guardian_birth_date = form.value.mother_birth_date
    form.value.guardian_education = form.value.mother_education
    form.value.guardian_occupation = form.value.mother_occupation
    form.value.guardian_income = form.value.mother_income
  }
  
  // Validate form
  const isValid = validateAll()
  if (!isValid) {
    error.value = 'Mohon perbaiki kesalahan pada form'
    return
  }
  
  saving.value = true
  
  try {
    const payload = {
      ...form.value,
      academic_year: normalizeAcademicYearLabel(form.value.academic_year),
    }
    if (editingId) {
      await studentApi.update(editingId, payload)
      toast.success('Berhasil', 'Data siswa berhasil diperbarui')
    } else {
      await studentApi.create(payload)
      toast.success('Berhasil', 'Siswa berhasil ditambahkan')
    }
    closeModal()
    loadStudents()
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan data'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
    
    // Set field errors from server
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }
  } finally {
    saving.value = false
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingId = null
  activeTab.value = 1
  form.value = {
    nik: '',
    nis: '',
    nisn: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    address: '',
    phone: '',
    email: '',
    religion: '',
    no_kk: '',
    aspiration: '',
    hobby: '',
    disability: '',
    height: null,
    weight: null,
    previous_school: '',
    residence_type: '',
    tingkat: null,
    class: '',
    class_id: null,
    academic_year: '',
    academic_year_id: null,
    semester_id: null,
    status: 'Aktif',
    father_name: '',
    father_status: '',
    father_nik: '',
    father_birth_place: '',
    father_birth_date: '',
    father_education: '',
    father_occupation: '',
    father_income: null,
    mother_name: '',
    mother_status: '',
    mother_nik: '',
    mother_birth_place: '',
    mother_birth_date: '',
    mother_education: '',
    mother_occupation: '',
    mother_income: null,
    guardian_name: '',
    guardian_phone: '',
    guardian_type: '',
    guardian_status: '',
    guardian_nik: '',
    guardian_birth_place: '',
    guardian_birth_date: '',
    guardian_education: '',
    guardian_occupation: '',
    guardian_income: null,
    notes: ''
  }
  // Reset dokumen
  currentStudentDocuments.value = []
  documentForm.value = { name: '', description: '' }
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  error.value = ''
}

const viewStudent = (student) => {
  viewingStudent.value = { ...student }
  showViewModal.value = true
  if (canAccessCounseling.value && student?.id) loadStudentCounseling(student.id)
  if (canAccessExtracurricular.value && student?.id) loadStudentExtracurriculars(student.id)
}

const closeViewModal = () => {
  showViewModal.value = false
  viewingStudent.value = null
  graduatingStudent.value = false
  studentCounselingSessions.value = []
  studentExtracurricularEnrollments.value = []
  studentExtracurricularLoading.value = false
}

const graduateFromView = async () => {
  if (!viewingStudent.value?.id || viewingStudent.value.status !== 'Aktif') return
  const name = viewingStudent.value.name || 'siswa ini'
  const confirmed = await showConfirm({
    title: 'Luluskan Siswa',
    message: `Apakah Anda yakin ingin meluluskan ${name}? Status akan diubah menjadi Lulus dan siswa muncul di daftar Alumni.`,
    warning: 'Tindakan ini dapat memengaruhi data kesiswaan aktif.',
    confirmText: 'Ya, Luluskan',
    loadingText: 'Memproses...',
    confirmVariant: 'primary'
  })
  if (!confirmed) return

  graduatingStudent.value = true
  try {
    await alumniApi.graduate(viewingStudent.value.id)
    toast.success('Berhasil', 'Siswa berhasil diluluskan.')
    closeViewModal()
    await loadStudents()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.formattedMessage || 'Gagal meluluskan siswa')
  } finally {
    graduatingStudent.value = false
  }
}

const revokeGraduationFromView = async () => {
  if (!viewingStudent.value?.id || viewingStudent.value.status !== 'Lulus') return
  const name = viewingStudent.value.name || 'siswa ini'
  const confirmed = await showConfirm({
    title: 'Batalkan Kelulusan',
    message: `Kembalikan ${name} ke status Aktif?`,
    warning: 'Siswa akan hilang dari daftar Alumni. Jika masih ada destinasi atau pengambilan ijazah, pembatalan akan ditolak.',
    confirmText: 'Ya, Batalkan Lulus',
    loadingText: 'Memproses...',
    confirmVariant: 'danger'
  })
  if (!confirmed) return

  graduatingStudent.value = true
  try {
    await alumniApi.revokeGraduation(viewingStudent.value.id)
    toast.success('Berhasil', 'Kelulusan dibatalkan. Siswa kembali berstatus Aktif.')
    closeViewModal()
    await loadStudents()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.formattedMessage || 'Gagal membatalkan kelulusan')
  } finally {
    graduatingStudent.value = false
  }
}

async function loadStudentCounseling(studentId) {
  studentCounselingLoading.value = true
  studentCounselingSessions.value = []
  try {
    const res = await counselingApi.getByStudent(studentId, { per_page: 20 })
    studentCounselingSessions.value = res.data.data || []
  } catch {
    studentCounselingSessions.value = []
  } finally {
    studentCounselingLoading.value = false
  }
}

async function loadStudentExtracurriculars(studentId) {
  studentExtracurricularLoading.value = true
  studentExtracurricularEnrollments.value = []
  try {
    const res = await extracurricularApi.getByStudent(studentId, { per_page: 100 })
    studentExtracurricularEnrollments.value = res.data?.data ?? []
  } catch {
    studentExtracurricularEnrollments.value = []
  } finally {
    studentExtracurricularLoading.value = false
  }
}

const counselingStatusLabels = { jadwal: 'Jadwal', berlangsung: 'Berlangsung', selesai: 'Selesai', dibatalkan: 'Dibatalkan' }
function counselingStatusLabel(status) {
  return counselingStatusLabels[status] || status || '-'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0'
  return new Intl.NumberFormat('id-ID').format(amount)
}

const formatStatus = (status) => {
  const statusMap = {
    'masih_hidup': 'Masih Hidup',
    'meninggal_dunia': 'Meninggal Dunia',
    'tidak_diketahui': 'Tidak Diketahui'
  }
  return statusMap[status] || status
}

const formatResidenceType = (type) => {
  const typeMap = {
    'asrama': 'Asrama',
    'kost_kontrak': 'Kost/Kontrak',
    'tinggal_dengan_orang_tua': 'Tinggal dengan Orang Tua',
    'lainnya': 'Lainnya'
  }
  return typeMap[type] || type
}

const formatGuardianType = (type) => {
  const typeMap = {
    'sama_dengan_ayah': 'Sama dengan Ayah Kandung',
    'sama_dengan_ibu': 'Sama dengan Ibu Kandung',
    'lainnya': 'Lainnya'
  }
  return typeMap[type] || type
}

// Export to Excel
const exportToExcel = async () => {
  try {
    loading.value = true
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.class_id) params.class_id = filters.value.class_id
    if (filters.value.tingkat !== '' && filters.value.tingkat !== null) {
      params.tingkat = filters.value.tingkat
    }
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.sort_by) params.sort_by = filters.value.sort_by
    if (filters.value.sort_dir) params.sort_dir = filters.value.sort_dir

    const response = await studentApi.export(params)
    const allStudents = response.data.data || []
    
    // Siapkan data untuk Excel
    const excelData = allStudents.map(student => ({
      'NIK': student.nik || '',
      'NIS': student.nis || '',
      'NISN': student.nisn || '',
      'Nama Lengkap': student.name || '',
      'Jenis Kelamin': student.gender === 'L' ? 'Laki-laki' : student.gender === 'P' ? 'Perempuan' : '',
      'Tempat Lahir': student.birth_place || '',
      'Tanggal Lahir': student.birth_date ? new Date(student.birth_date).toLocaleDateString('id-ID') : '',
      'Alamat': student.address || '',
      'No. Telepon': student.phone || '',
      'Email': student.email || '',
      'Agama': student.religion || '',
      'No. KK': student.no_kk || '',
      'Cita-cita': student.aspiration || '',
      'Hobi': student.hobby || '',
      'Disabilitas': student.disability || '',
      'Tinggi Badan (cm)': student.height || '',
      'Berat Badan (kg)': student.weight || '',
      'Sekolah Sebelumnya': student.previous_school || '',
      'Jenis Tempat Tinggal': formatResidenceType(student.residence_type) || '',
      'Tingkat': student.tingkat ?? '',
      'Kelas': student.class || '',
      'Tahun Ajaran': student.academic_year || '',
      'Status': student.status || '',
      'Nama Ayah': student.father_name || '',
      'Status Ayah': formatStatus(student.father_status) || '',
      'NIK Ayah': student.father_nik || '',
      'Tempat Lahir Ayah': student.father_birth_place || '',
      'Tanggal Lahir Ayah': student.father_birth_date ? new Date(student.father_birth_date).toLocaleDateString('id-ID') : '',
      'Pendidikan Ayah': student.father_education || '',
      'Pekerjaan Ayah': student.father_occupation || '',
      'Penghasilan Ayah': student.father_income || '',
      'Nama Ibu': student.mother_name || '',
      'Status Ibu': formatStatus(student.mother_status) || '',
      'NIK Ibu': student.mother_nik || '',
      'Tempat Lahir Ibu': student.mother_birth_place || '',
      'Tanggal Lahir Ibu': student.mother_birth_date ? new Date(student.mother_birth_date).toLocaleDateString('id-ID') : '',
      'Pendidikan Ibu': student.mother_education || '',
      'Pekerjaan Ibu': student.mother_occupation || '',
      'Penghasilan Ibu': student.mother_income || '',
      'Nama Wali': student.guardian_name || '',
      'No. Telepon Wali': student.guardian_phone || '',
      'Jenis Wali': formatGuardianType(student.guardian_type) || '',
      'Status Wali': formatStatus(student.guardian_status) || '',
      'NIK Wali': student.guardian_nik || '',
      'Tempat Lahir Wali': student.guardian_birth_place || '',
      'Tanggal Lahir Wali': student.guardian_birth_date ? new Date(student.guardian_birth_date).toLocaleDateString('id-ID') : '',
      'Pendidikan Wali': student.guardian_education || '',
      'Pekerjaan Wali': student.guardian_occupation || '',
      'Penghasilan Wali': student.guardian_income || '',
      'Catatan': student.notes || ''
    }))
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(excelData)
    
    // Set column widths
    const colWidths = [
      { wch: 20 }, { wch: 15 }, { wch: 15 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 10 }, { wch: 10 }, { wch: 25 }, { wch: 20 }, { wch: 15 },
      { wch: 15 }, { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 30 }
    ]
    ws['!cols'] = colWidths
    
    XLSX.utils.book_append_sheet(wb, ws, 'Data Siswa')
    
    // Download file
    const fileName = `Data_Siswa_${new Date().toISOString().split('T')[0]}.xlsx`
    XLSX.writeFile(wb, fileName)
    
    toast.success('Berhasil', `Data berhasil diekspor ke Excel (${allStudents.length} siswa)`)
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal mengekspor data ke Excel')
  } finally {
    loading.value = false
  }
}

// Download Template Excel
const downloadTemplate = () => {
  try {
    // Kolom bertanda * wajib diisi
    const templateData = [
      {
        'NIK*': '1234567890123456',
        'NIS': '2024001',
        'NISN': '0012345678',
        'Nama Lengkap*': 'Ahmad Fauzi',
        'Jenis Kelamin': 'L',
        'Tempat Lahir*': 'Jakarta',
        'Tanggal Lahir*': '2010-01-15',
        'Alamat': 'Jl. Contoh No. 123',
        'No. Telepon': '081234567890',
        'Email': 'ahmad@example.com',
        'Agama': 'Islam',
        'No. KK': '1234567890123456',
        'Cita-cita': 'Dokter',
        'Hobi': 'Membaca',
        'Disabilitas': '',
        'Tinggi Badan (cm)': '150',
        'Berat Badan (kg)': '45',
        'Sekolah Sebelumnya': 'SD Negeri 1',
        'Jenis Tempat Tinggal': 'tinggal_dengan_orang_tua',
        'Tingkat*': '7',
        'Kelas': 'VII-A',
        'Tahun Ajaran': '2024/2025',
        'Status': 'Aktif',
        'Nama Ayah': 'Budi Santoso',
        'Status Ayah': 'masih_hidup',
        'NIK Ayah': '1234567890123457',
        'Tempat Lahir Ayah': 'Jakarta',
        'Tanggal Lahir Ayah': '1980-05-20',
        'Pendidikan Ayah': 'S1',
        'Pekerjaan Ayah': 'Pegawai Swasta',
        'Penghasilan Ayah': '5000000',
        'Nama Ibu': 'Siti Nurhaliza',
        'Status Ibu': 'masih_hidup',
        'NIK Ibu': '1234567890123458',
        'Tempat Lahir Ibu': 'Bandung',
        'Tanggal Lahir Ibu': '1982-08-10',
        'Pendidikan Ibu': 'S1',
        'Pekerjaan Ibu': 'Guru',
        'Penghasilan Ibu': '4000000',
        'Nama Wali': '',
        'No. Telepon Wali': '',
        'Jenis Wali': '',
        'Status Wali': '',
        'NIK Wali': '',
        'Tempat Lahir Wali': '',
        'Tanggal Lahir Wali': '',
        'Pendidikan Wali': '',
        'Pekerjaan Wali': '',
        'Penghasilan Wali': '',
        'Catatan': ''
      }
    ]

    const guideData = [
      { Keterangan: 'Kolom bertanda * wajib diisi' },
      { Keterangan: 'Kolom wajib: NIK*, Nama Lengkap*, Tempat Lahir*, Tanggal Lahir*, Tingkat*' },
      { Keterangan: 'Format Tanggal Lahir: YYYY-MM-DD (contoh: 2010-01-15)' },
      { Keterangan: 'Tingkat harus sesuai jenjang institusi (SD/MI: 1-6, SMP/MTs: 7-9, SMA/SMK/MA: 10-12)' },
      { Keterangan: 'Jangan ubah nama header kolom agar import berhasil' },
    ]
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)
    const wsGuide = XLSX.utils.json_to_sheet(guideData)
    
    // Set column widths
    const colWidths = [
      { wch: 20 }, { wch: 15 }, { wch: 15 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 10 }, { wch: 10 }, { wch: 25 }, { wch: 20 }, { wch: 15 },
      { wch: 15 }, { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 30 }
    ]
    ws['!cols'] = colWidths
    wsGuide['!cols'] = [{ wch: 90 }]
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Siswa')
    XLSX.utils.book_append_sheet(wb, wsGuide, 'Petunjuk')
    
    // Download file
    const fileName = `Template_Import_Siswa.xlsx`
    XLSX.writeFile(wb, fileName)
    
    toast.success('Berhasil', 'Template Excel berhasil didownload. Kolom bertanda * wajib diisi.')
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal mendownload template Excel')
  }
}

function closeImportPreview() {
  if (importPreview.value.loading) return
  importPreview.value = { open: false, loading: false, valid: [], invalid: [], serverErrors: [] }
}

function mapImportExcelRows(jsonData) {
  const normalizeHeader = (key) => String(key || '').replace(/\s*\*\s*$/, '').replace(/\s*\(wajib\)\s*$/i, '').trim()
  const parseDate = (dateStr) => {
    if (!dateStr) return null
    if (dateStr instanceof Date) return dateStr.toISOString().split('T')[0]
    if (typeof dateStr === 'number' && XLSX?.SSF?.parse_date_code) {
      const parsed = XLSX.SSF.parse_date_code(dateStr)
      if (parsed) {
        const mm = String(parsed.m).padStart(2, '0')
        const dd = String(parsed.d).padStart(2, '0')
        return `${parsed.y}-${mm}-${dd}`
      }
    }
    const date = new Date(dateStr)
    if (!isNaN(date.getTime())) return date.toISOString().split('T')[0]
    return null
  }
  const parseGender = (val) => {
    if (!val) return null
    const str = String(val).toLowerCase()
    if (str.includes('laki') || str === 'l' || str === 'laki-laki') return 'L'
    if (str.includes('perempuan') || str === 'p' || str === 'perempuan') return 'P'
    return null
  }
  const parseStatus = (val) => {
    if (!val) return null
    const str = String(val).toLowerCase()
    if (str.includes('hidup') || str === 'masih hidup') return 'masih_hidup'
    if (str.includes('meninggal') || str === 'meninggal dunia') return 'meninggal_dunia'
    if (str.includes('tidak') || str === 'tidak diketahui') return 'tidak_diketahui'
    return null
  }
  const parseResidenceType = (val) => {
    if (!val) return null
    const str = String(val).toLowerCase()
    if (str.includes('asrama')) return 'asrama'
    if (str.includes('kost') || str.includes('kontrak')) return 'kost_kontrak'
    if (str.includes('orang tua') || str.includes('tinggal')) return 'tinggal_dengan_orang_tua'
    return 'lainnya'
  }
  const parseGuardianType = (val) => {
    if (!val) return null
    const str = String(val).toLowerCase()
    if (str.includes('ayah')) return 'sama_dengan_ayah'
    if (str.includes('ibu')) return 'sama_dengan_ibu'
    return 'lainnya'
  }

  const valid = []
  const invalid = []

  jsonData.forEach((row, index) => {
    const normalizedRow = {}
    Object.keys(row || {}).forEach((key) => {
      normalizedRow[normalizeHeader(key)] = row[key]
    })
    const mapField = (excelCol) => {
      const value = normalizedRow[excelCol]
      if (value === undefined || value === null || value === '') return null
      return value
    }

    const item = {
      nik: mapField('NIK'),
      nis: mapField('NIS'),
      nisn: mapField('NISN'),
      name: mapField('Nama Lengkap'),
      gender: parseGender(mapField('Jenis Kelamin')),
      birth_place: mapField('Tempat Lahir'),
      birth_date: parseDate(mapField('Tanggal Lahir')),
      address: mapField('Alamat'),
      phone: mapField('No. Telepon'),
      email: mapField('Email'),
      religion: mapField('Agama'),
      no_kk: mapField('No. KK'),
      aspiration: mapField('Cita-cita'),
      hobby: mapField('Hobi'),
      disability: mapField('Disabilitas'),
      height: mapField('Tinggi Badan (cm)') ? parseFloat(mapField('Tinggi Badan (cm)')) : null,
      weight: mapField('Berat Badan (kg)') ? parseFloat(mapField('Berat Badan (kg)')) : null,
      previous_school: mapField('Sekolah Sebelumnya'),
      residence_type: parseResidenceType(mapField('Jenis Tempat Tinggal')),
      tingkat: mapField('Tingkat') ? parseInt(mapField('Tingkat'), 10) : null,
      class: mapField('Kelas'),
      academic_year: mapField('Tahun Ajaran'),
      status: mapField('Status') || 'Aktif',
      father_name: mapField('Nama Ayah'),
      father_status: parseStatus(mapField('Status Ayah')),
      father_nik: mapField('NIK Ayah'),
      father_birth_place: mapField('Tempat Lahir Ayah'),
      father_birth_date: parseDate(mapField('Tanggal Lahir Ayah')),
      father_education: mapField('Pendidikan Ayah'),
      father_occupation: mapField('Pekerjaan Ayah'),
      father_income: mapField('Penghasilan Ayah') ? parseFloat(mapField('Penghasilan Ayah')) : null,
      mother_name: mapField('Nama Ibu'),
      mother_status: parseStatus(mapField('Status Ibu')),
      mother_nik: mapField('NIK Ibu'),
      mother_birth_place: mapField('Tempat Lahir Ibu'),
      mother_birth_date: parseDate(mapField('Tanggal Lahir Ibu')),
      mother_education: mapField('Pendidikan Ibu'),
      mother_occupation: mapField('Pekerjaan Ibu'),
      mother_income: mapField('Penghasilan Ibu') ? parseFloat(mapField('Penghasilan Ibu')) : null,
      guardian_name: mapField('Nama Wali'),
      guardian_phone: mapField('No. Telepon Wali'),
      guardian_type: parseGuardianType(mapField('Jenis Wali')),
      guardian_status: parseStatus(mapField('Status Wali')),
      guardian_nik: mapField('NIK Wali'),
      guardian_birth_place: mapField('Tempat Lahir Wali'),
      guardian_birth_date: parseDate(mapField('Tanggal Lahir Wali')),
      guardian_education: mapField('Pendidikan Wali'),
      guardian_occupation: mapField('Pekerjaan Wali'),
      guardian_income: mapField('Penghasilan Wali') ? parseFloat(mapField('Penghasilan Wali')) : null,
      notes: mapField('Catatan'),
    }

    const missing = []
    if (!item.nik) missing.push('NIK')
    if (!item.name) missing.push('Nama')
    if (!item.birth_place) missing.push('Tempat Lahir')
    if (!item.birth_date) missing.push('Tanggal Lahir')
    if (item.tingkat == null || Number.isNaN(item.tingkat)) missing.push('Tingkat')

    if (missing.length) {
      invalid.push({ row: index + 2, reason: `Kolom wajib kosong: ${missing.join(', ')}` })
    } else {
      valid.push(item)
    }
  })

  return { valid, invalid }
}

// Import from Excel — tampilkan pratinjau dulu
const handleImportExcel = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  try {
    loading.value = true
    const data = await file.arrayBuffer()
    const workbook = XLSX.read(data, { type: 'array', cellDates: true })
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
    const jsonData = XLSX.utils.sheet_to_json(firstSheet)

    if (jsonData.length === 0) {
      toast.error('Gagal', 'File Excel kosong')
      return
    }

    const { valid, invalid } = mapImportExcelRows(jsonData)
    if (!valid.length) {
      toast.error(
        'Gagal',
        'Tidak ada data valid. Pastikan kolom NIK, Nama Lengkap, Tempat Lahir, Tanggal Lahir, dan Tingkat terisi.'
      )
      importPreview.value = { open: true, loading: false, valid: [], invalid, serverErrors: [] }
      return
    }

    importPreview.value = { open: true, loading: false, valid, invalid, serverErrors: [] }
  } catch (err) {
    console.error(err)
    toast.error('Gagal', err.formattedMessage || 'Gagal membaca file Excel')
  } finally {
    loading.value = false
    event.target.value = ''
  }
}

async function confirmImportExcel() {
  if (!importPreview.value.valid.length || importPreview.value.loading) return
  importPreview.value.loading = true
  try {
    const response = await studentApi.import(importPreview.value.valid)
    const successCount = response.data?.success_count || 0
    const errorCount = response.data?.error_count || 0
    const errors = response.data?.errors || []
    if (errorCount > 0) {
      importPreview.value.serverErrors = errors
      toast.success('Sebagian berhasil', `Berhasil ${successCount}, gagal ${errorCount}. Periksa daftar error.`)
      await loadStudents()
    } else {
      toast.success('Berhasil', `Berhasil mengimpor ${successCount || importPreview.value.valid.length} data siswa`)
      closeImportPreview()
      await loadStudents()
    }
  } catch (err) {
    console.error(err)
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal mengimpor data dari Excel')
  } finally {
    importPreview.value.loading = false
  }
}

const downloadBukuIndukPdf = async () => {
  if (!viewingStudent.value?.id) return
  try {
    const res = await studentApi.downloadBukuIndukPdf(viewingStudent.value.id)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Buku_Induk_${viewingStudent.value.name || viewingStudent.value.id}.pdf`
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Buku induk berhasil diunduh')
  } catch (err) {
    console.error(err)
    toast.error('Gagal', err.response?.data?.message || 'Gagal mengunduh buku induk')
  }
}

const printPDF = async () => {
  if (!viewingStudent.value) return
  
  try {
    // Ambil data institusi
    const institutionResponse = await institutionApi.getMy()
    const institution = institutionResponse.data?.data || institutionResponse.data || {}
    
    const printWindow = window.open('', '_blank')
    const student = viewingStudent.value
    const filename = `${student.nik || 'NIK'}_${student.name || 'Siswa'}.pdf`
    
    // Format alamat lengkap
    const addressParts = []
    if (institution.address) addressParts.push(institution.address)
    if (institution.village) addressParts.push(institution.village)
    if (institution.sub_district) addressParts.push(`Kec. ${institution.sub_district}`)
    if (institution.district) addressParts.push(institution.district)
    if (institution.province) addressParts.push(institution.province)
    if (institution.postal_code) addressParts.push(institution.postal_code)
    const fullAddress = addressParts.join(', ') || '-'
    const principalLabel = getPrincipalTitle(institution?.level)
    
    const content = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Biodata ${student.name}</title>
        <style>
        @media print {
          @page {
            size: A4;
            margin: 1.2cm 2cm 2cm 2cm;
          }
        }
        body {
          font-family: 'Times New Roman', serif;
          line-height: 1.3;
          color: #000;
          max-width: 800px;
          margin: 0 auto;
          padding: 0;
        }
        .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
        .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
        .kop-logo { width: 66px; height: 66px; object-fit: contain; }
        .kop-text { min-width: 0; text-align: center; }
        .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
        .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .school-address { font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.35; margin-top: 3px; }
        .school-info { font-family: Arial, Helvetica, sans-serif; font-size: 9px; margin-top: 2px; }
        .header {
          text-align: center;
          margin-bottom: 20px;
          margin-top: 15px;
        }
        .header h1 {
          color: #000;
          margin: 0;
          font-size: 18px;
          font-weight: bold;
          text-transform: uppercase;
          letter-spacing: 1px;
          line-height: 1.2;
        }
        .header p {
          margin-top: 4px;
          font-size: 13px;
          line-height: 1.2;
        }
        .section {
          margin-bottom: 20px;
          page-break-inside: avoid;
        }
        .section-title {
          background: #f0f0f0;
          color: #000;
          padding: 8px 12px;
          margin: 0 0 10px 0;
          font-size: 14px;
          font-weight: bold;
          border-left: 4px solid #000;
          line-height: 1.2;
        }
        .biodata-grid {
          display: grid;
          grid-template-columns: 1fr 2fr;
          gap: 0;
          margin-bottom: 12px;
          width: calc(100% - 2px);
          max-width: calc(100% - 2px);
          border: 1px solid #ddd;
        }
        .biodata-item {
          display: contents;
        }
        .label {
          font-weight: bold;
          color: #000;
          padding: 7px 10px;
          background: #f8f8f8;
          border-right: 1px solid #ddd;
          border-bottom: 1px solid #ddd;
          font-size: 13px;
          line-height: 1.2;
        }
        .value {
          padding: 7px 10px;
          border-bottom: 1px solid #ddd;
          font-size: 13px;
          line-height: 1.2;
        }
        .biodata-grid .biodata-item:last-child .label,
        .biodata-grid .biodata-item:nth-last-child(2) .label {
          border-bottom: none;
        }
        .biodata-grid .biodata-item:last-child .value,
        .biodata-grid .biodata-item:nth-last-child(2) .value {
          border-bottom: none;
        }
        .footer {
          margin-top: 40px;
          padding-top: 20px;
          border-top: 1px solid #ddd;
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          page-break-inside: avoid;
        }
        .footer-left {
          flex: 1;
        }
        .footer-right {
          flex: 1;
          text-align: right;
        }
        .footer-date {
          font-size: 12px;
          margin-bottom: 40px;
          line-height: 1.3;
        }
        .footer-signature {
          font-size: 12px;
          line-height: 1.3;
        }
        .footer-signature-label {
          margin-bottom: 60px;
          font-weight: bold;
        }
        .footer-signature-name {
          font-weight: bold;
          text-decoration: underline;
        }
        .footer-signature-nip {
          font-size: 11px;
          margin-top: 5px;
        }
        .no-print {
          display: none;
        }
        </style>
      </head>
      <body>
        <header class="kop">
          <div class="kop-inner">
            <div>${institution.logo ? `<img src="${institution.logo}" alt="Logo ${getInstitutionTypeLabel(institution?.level) || 'Sekolah/Madrasah'}" class="kop-logo" />` : ''}</div>
            <div class="kop-text">
              ${institution.foundation_name ? `<div class="foundation">${institution.foundation_name}</div>` : ''}
              <div class="school">${institution.name || 'NAMA LEMBAGA'}</div>
              <div class="school-address">${fullAddress || '-'}</div>
              <div class="school-info">
                NPSN: ${institution.npsn || '-'}
                ${institution.nss ? ` · ${getNssLabel(institution.level)}: ${institution.nss}` : ''}
                ${institution.phone ? ` · Telp: ${institution.phone}` : ''}
                ${institution.email ? ` · Email: ${institution.email}` : ''}
                ${institution.website ? ` · ${institution.website}` : ''}
              </div>
            </div>
            <div></div>
          </div>
        </header>
        
        <div class="header">
          <h1>BIODATA SISWA</h1>
          <p>${student.name || ''}</p>
        </div>
        
        <div class="section">
        <h3 class="section-title">Identitas Siswa</h3>
        <div class="biodata-grid">
          <div class="biodata-item"><span class="label">NIK</span><span class="value">${student.nik || '-'}</span></div>
          <div class="biodata-item"><span class="label">NIS</span><span class="value">${student.nis || '-'}</span></div>
          <div class="biodata-item"><span class="label">NISN</span><span class="value">${student.nisn || '-'}</span></div>
          <div class="biodata-item"><span class="label">Nama Lengkap</span><span class="value">${student.name || '-'}</span></div>
          <div class="biodata-item"><span class="label">Jenis Kelamin</span><span class="value">${student.gender === 'L' ? 'Laki-laki' : student.gender === 'P' ? 'Perempuan' : '-'}</span></div>
          <div class="biodata-item"><span class="label">Tempat Lahir</span><span class="value">${student.birth_place || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tanggal Lahir</span><span class="value">${formatDate(student.birth_date)}</span></div>
          <div class="biodata-item"><span class="label">Alamat</span><span class="value">${student.address || '-'}</span></div>
          <div class="biodata-item"><span class="label">Telepon</span><span class="value">${student.phone || '-'}</span></div>
          <div class="biodata-item"><span class="label">Email</span><span class="value">${student.email || '-'}</span></div>
          <div class="biodata-item"><span class="label">Kelas</span><span class="value">${student.class || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tahun Ajaran</span><span class="value">${student.academic_year || '-'}</span></div>
          <div class="biodata-item"><span class="label">Status</span><span class="value">${student.status || '-'}</span></div>
        </div>
      </div>
      
      <div class="section">
        <h3 class="section-title">Data Tambahan</h3>
        <div class="biodata-grid">
          <div class="biodata-item"><span class="label">No KK</span><span class="value">${student.no_kk || '-'}</span></div>
          <div class="biodata-item"><span class="label">Cita-cita</span><span class="value">${student.aspiration || '-'}</span></div>
          <div class="biodata-item"><span class="label">Hobi</span><span class="value">${student.hobby || '-'}</span></div>
          <div class="biodata-item"><span class="label">Agama</span><span class="value">${student.religion || '-'}</span></div>
          <div class="biodata-item"><span class="label">Disabilitas</span><span class="value">${student.disability || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tempat Tinggal</span><span class="value">${student.residence_type ? formatResidenceType(student.residence_type) : '-'}</span></div>
          <div class="biodata-item"><span class="label">Tinggi Badan</span><span class="value">${student.height ? student.height + ' cm' : '-'}</span></div>
          <div class="biodata-item"><span class="label">Berat Badan</span><span class="value">${student.weight ? student.weight + ' kg' : '-'}</span></div>
          <div class="biodata-item"><span class="label">Asal Sekolah</span><span class="value">${student.previous_school || '-'}</span></div>
          <div class="biodata-item"><span class="label">Catatan</span><span class="value">${student.notes || '-'}</span></div>
        </div>
      </div>
      
      <div class="section">
        <h3 class="section-title">Data Ayah Kandung</h3>
        <div class="biodata-grid">
          <div class="biodata-item"><span class="label">Status</span><span class="value">${student.father_status ? formatStatus(student.father_status) : '-'}</span></div>
          <div class="biodata-item"><span class="label">NIK</span><span class="value">${student.father_nik || '-'}</span></div>
          <div class="biodata-item"><span class="label">Nama Lengkap</span><span class="value">${student.father_name || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tempat Lahir</span><span class="value">${student.father_birth_place || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tanggal Lahir</span><span class="value">${formatDate(student.father_birth_date)}</span></div>
          <div class="biodata-item"><span class="label">Pendidikan</span><span class="value">${student.father_education || '-'}</span></div>
          <div class="biodata-item"><span class="label">Pekerjaan</span><span class="value">${student.father_occupation || '-'}</span></div>
          <div class="biodata-item"><span class="label">Penghasilan per Bulan</span><span class="value">${student.father_income ? 'Rp ' + formatCurrency(student.father_income) : '-'}</span></div>
        </div>
      </div>
      
      <div class="section">
        <h3 class="section-title">Data Ibu Kandung</h3>
        <div class="biodata-grid">
          <div class="biodata-item"><span class="label">Status</span><span class="value">${student.mother_status ? formatStatus(student.mother_status) : '-'}</span></div>
          <div class="biodata-item"><span class="label">NIK</span><span class="value">${student.mother_nik || '-'}</span></div>
          <div class="biodata-item"><span class="label">Nama Lengkap</span><span class="value">${student.mother_name || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tempat Lahir</span><span class="value">${student.mother_birth_place || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tanggal Lahir</span><span class="value">${formatDate(student.mother_birth_date)}</span></div>
          <div class="biodata-item"><span class="label">Pendidikan</span><span class="value">${student.mother_education || '-'}</span></div>
          <div class="biodata-item"><span class="label">Pekerjaan</span><span class="value">${student.mother_occupation || '-'}</span></div>
          <div class="biodata-item"><span class="label">Penghasilan per Bulan</span><span class="value">${student.mother_income ? 'Rp ' + formatCurrency(student.mother_income) : '-'}</span></div>
        </div>
      </div>
      
      <div class="section">
        <h3 class="section-title">Data Wali</h3>
        <div class="biodata-grid">
          <div class="biodata-item"><span class="label">Wali</span><span class="value">${student.guardian_type ? formatGuardianType(student.guardian_type) : '-'}</span></div>
          <div class="biodata-item"><span class="label">Status</span><span class="value">${student.guardian_status ? formatStatus(student.guardian_status) : '-'}</span></div>
          <div class="biodata-item"><span class="label">NIK</span><span class="value">${student.guardian_nik || '-'}</span></div>
          <div class="biodata-item"><span class="label">Nama Lengkap</span><span class="value">${student.guardian_name || '-'}</span></div>
          <div class="biodata-item"><span class="label">Telepon</span><span class="value">${student.guardian_phone || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tempat Lahir</span><span class="value">${student.guardian_birth_place || '-'}</span></div>
          <div class="biodata-item"><span class="label">Tanggal Lahir</span><span class="value">${formatDate(student.guardian_birth_date)}</span></div>
          <div class="biodata-item"><span class="label">Pendidikan</span><span class="value">${student.guardian_education || '-'}</span></div>
          <div class="biodata-item"><span class="label">Pekerjaan</span><span class="value">${student.guardian_occupation || '-'}</span></div>
          <div class="biodata-item"><span class="label">Penghasilan per Bulan</span><span class="value">${student.guardian_income ? 'Rp ' + formatCurrency(student.guardian_income) : '-'}</span></div>
        </div>
      </div>
      
      <div class="footer">
        <div class="footer-left">
          <div class="footer-date">
            <strong>Catatan:</strong><br>
            Dokumen ini adalah data resmi yang tercatat dalam sistem sekolah.
          </div>
        </div>
        <div class="footer-right">
          <div class="footer-date">
            ${institution.district || 'Kota/Kabupaten'}, ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
          </div>
          <div class="footer-signature">
            <div class="footer-signature-label">${principalLabel}</div>
            <div class="footer-signature-name">${institution.principal_name || '___________________'}</div>
            <div class="footer-signature-nip">${institution.principal_nip ? 'NIP. ' + institution.principal_nip : 'NIP. ___________________'}</div>
          </div>
        </div>
      </div>
    </body>
    </html>
  `
  
    printWindow.document.write(content)
    printWindow.document.close()
    
    setTimeout(() => {
      printWindow.print()
      // Set filename saat save PDF
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    console.error('Error loading institution data:', err)
    toast.error('Gagal', 'Gagal memuat data institusi untuk KOP surat')
  }
}

// Methods untuk dokumen
const formatFileSize = (bytes) => {
  if (!bytes || bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validasi ukuran file (2MB = 2 * 1024 * 1024 bytes)
  const maxSize = 2 * 1024 * 1024
  if (file.size > maxSize) {
    error.value = 'Ukuran file terlalu besar. Maksimal 2 MB'
    event.target.value = ''
    selectedFile.value = null
    return
  }

  // Validasi tipe file
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf']
  if (!allowedTypes.includes(file.type)) {
    error.value = 'Format file tidak didukung. Hanya JPG, PNG, dan PDF'
    event.target.value = ''
    selectedFile.value = null
    return
  }

  selectedFile.value = file
  error.value = ''
}

const loadStudentDocuments = async () => {
  if (!editingId) return

  try {
    const response = await studentApi.get(editingId)
    currentStudentDocuments.value = response.data.data.documents || []
  } catch (err) {
    console.error('Failed to load documents', err)
  }
}

const uploadDocument = async () => {
  if (!editingId) {
    error.value = 'Simpan data siswa terlebih dahulu'
    return
  }

  if (!documentForm.value.name || !selectedFile.value) {
    error.value = 'Nama dokumen dan file wajib diisi'
    return
  }

  uploadingDocument.value = true
  error.value = ''

  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('name', documentForm.value.name)
    if (documentForm.value.description) {
      formData.append('description', documentForm.value.description)
    }

    await studentApi.uploadDocument(editingId, formData)
    toast.success('Berhasil', 'Dokumen berhasil diupload')

    // Reset form
    documentForm.value = { name: '', description: '' }
    selectedFile.value = null
    if (fileInput.value) {
      fileInput.value.value = ''
    }

    // Reload dokumen
    await loadStudentDocuments()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal mengupload dokumen'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    uploadingDocument.value = false
  }
}

const deleteDocument = async (documentId) => {
  if (!editingId) return

  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Yakin ingin menghapus dokumen ini?',
    warning: 'Dokumen akan dihapus secara permanen.'
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await studentApi.deleteDocument(editingId, documentId)
    toast.success('Berhasil', 'Dokumen berhasil dihapus')
    await loadStudentDocuments()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menghapus dokumen'
    toast.error('Gagal', errorMsg)
  } finally {
    setDeleteLoading(false)
  }
}

const downloadDocument = async (documentId) => {
  if (!editingId) return

  try {
    const response = await studentApi.downloadDocument(editingId, documentId)
    const doc = currentStudentDocuments.value.find(d => d.id === documentId)
    const fileName = doc?.file_name || 'document'
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', fileName)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal mengunduh dokumen'
    toast.error('Gagal', errorMsg)
  }
}

onMounted(() => {
  loadFilterClasses()
  loadStudents()
})
</script>

<style scoped>
.student-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.list-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 16px;
}

.list-tabs .tab-btn {
  padding: 10px 20px;
  border: 1px solid #d1fae5;
  background: #fff;
  color: #047857;
  border-radius: 10px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.list-tabs .tab-btn:hover {
  background: #ecfdf5;
}

.list-tabs .tab-btn.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: #047857;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.toolbar .filters {
  margin-bottom: 0;
  flex: 1;
  min-width: 200px;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.account-status-banner {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  flex-wrap: wrap;
  margin: -8px 0 20px;
  padding: 14px 16px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  border-radius: 10px;
}

.account-status-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: #1e3a5f;
}

.account-status-text .hint,
.import-preview-modal .hint {
  color: #64748b;
  font-size: 12px;
}

.account-status-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.import-preview-modal {
  max-width: 640px;
  width: 100%;
}

.import-preview-table-wrap {
  margin-top: 12px;
  overflow-x: auto;
}

.import-invalid-box {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  font-size: 13px;
}

.import-invalid-box ul {
  margin: 8px 0 0;
  padding-left: 18px;
}

.btn-compact {
  padding: 8px 14px;
  font-size: 13px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.header-content .page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.header-content .page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

/* Filter + pencarian: 1 baris ke samping (horizontal), diperkecil agar tidak perlu scroll */
.filters-inline {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: 8px;
  padding: 8px 10px;
  margin-bottom: 16px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.filters-inline::-webkit-scrollbar {
  height: 3px;
}

.filters-inline .search-input {
  flex: 1 1 auto;
  min-width: 60px;
  padding: 6px 10px;
  font-size: 12px;
  border-radius: 8px;
}

.filters-inline .filter-select {
  flex: 0 0 auto;
  min-width: 85px;
  padding: 6px 26px 6px 8px;
  font-size: 12px;
  border-radius: 8px;
}

.search-input,
.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.filter-select {
  min-width: 180px;
}

.error-state {
  padding: 32px;
  text-align: center;
  background: #fef2f2;
  border-radius: 12px;
  border: 1px solid #fecaca;
}

.error-state .error-text {
  color: #b91c1c;
  margin: 0 0 16px 0;
}

.loading-wrap {
  width: 100%;
  min-height: 200px;
}

.content-wrapper {
  position: relative;
}

.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 20px;
  padding: 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.pagination-info,
.page-num {
  color: #64748b;
  font-size: 14px;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-page {
  padding: 8px 14px;
  color: #047857;
  font-weight: 500;
  background: #fff;
  border: 1px solid #d1fae5;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.btn-page:hover:not(:disabled) {
  background: #ecfdf5;
  border-color: #6ee7b7;
}

.btn-page:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

/* Mobile cards (hidden on desktop) */
.student-cards.table-mobile {
  display: none;
}

.student-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.student-card-main {
  flex: 1;
  min-width: 0;
}

.student-card-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 6px 0;
  line-height: 1.3;
}

.student-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #64748b;
}

.student-card-id {
  flex-shrink: 0;
}

.student-card-class {
  padding: 2px 8px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.student-card-status {
  display: inline-block;
  margin-top: 8px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.student-card-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.student-card-actions .btn-action {
  width: 44px;
  height: 44px;
  padding: 10px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.data-table th {
  padding: 16px 20px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table td:last-child {
  text-align: center;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.status-active {
  color: #27ae60;
  font-weight: 600;
}

.status-success {
  color: #3498db;
  font-weight: 600;
}

.status-warning {
  color: #f39c12;
  font-weight: 600;
}

.status-danger {
  color: #e74c3c;
  font-weight: 600;
}

.status-inactive {
  color: #95a5a6;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: center;
}

.btn-action {
  padding: 8px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: transparent;
}

.btn-view {
  color: #10b981;
}

.btn-view:hover {
  background: rgba(16, 185, 129, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.btn-edit {
  color: #059669;
}

.btn-edit:hover {
  background: rgba(5, 150, 105, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.2);
}

.btn-delete {
  color: #ef4444;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 8px;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 24px 0;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Form modal: overlay dengan blur */
.form-modal-overlay {
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  padding: 24px;
}

.form-modal-content {
  background: #ffffff;
  border-radius: 20px;
  width: 100%;
  max-width: 920px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(15, 23, 42, 0.06);
  animation: formModalIn 0.25s ease-out;
}

@keyframes formModalIn {
  from {
    opacity: 0;
    transform: scale(0.98) translateY(-12px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.form-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 28px;
  border-bottom: 1px solid #e2e8f0;
  background: #fafbfc;
  border-radius: 20px 20px 0 0;
  flex-shrink: 0;
}

.form-modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 16px;
}

.form-modal-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.form-modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.form-modal-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 4px 0 0 0;
  font-weight: 400;
}

.btn-close-modal {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s;
}

.btn-close-modal:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.form-modal-body {
  padding: 28px;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.form-tabs-nav {
  display: flex;
  gap: 6px;
  margin-bottom: 28px;
  padding-bottom: 0;
  border-bottom: 1px solid #e2e8f0;
  overflow-x: auto;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}

.form-tabs-nav::-webkit-scrollbar {
  height: 4px;
}

.form-tab-btn {
  padding: 12px 18px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  margin-bottom: -1px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  transition: color 0.2s, background 0.2s, border-color 0.2s;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 10px 10px 0 0;
}

.form-tab-btn:hover {
  color: #059669;
  background: #ecfdf5;
}

.form-tab-btn.active {
  color: #059669;
  border-bottom-color: #059669;
  font-weight: 600;
  background: #ecfdf5;
}

.form-tab-btn svg {
  flex-shrink: 0;
  opacity: 0.85;
}

.form-tab-content {
  min-height: 280px;
  animation: formTabFade 0.2s ease-out;
}

@keyframes formTabFade {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Form fields inside form modal */
.form-modal-body .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-modal-body .form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-modal-body .form-group label {
  margin-bottom: 0;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.form-modal-body .form-group label .required {
  color: #dc2626;
}

.form-modal-body .form-group label::after {
  display: none;
}

.form-field-skeleton {
  margin-bottom: 6px;
}
.form-field-skeleton .skeleton-line-inline {
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s ease-in-out infinite;
}
@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.form-modal-body .form-group input,
.form-modal-body .form-group select,
.form-modal-body .form-group textarea {
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: #0f172a;
  font-family: inherit;
}

.form-modal-body .form-group input:hover,
.form-modal-body .form-group select:hover,
.form-modal-body .form-group textarea:hover {
  border-color: #cbd5e1;
}

.form-modal-body .form-group input:focus,
.form-modal-body .form-group select:focus,
.form-modal-body .form-group textarea:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
  transform: none;
}

.form-modal-body .form-group input::placeholder,
.form-modal-body .form-group textarea::placeholder {
  color: #94a3b8;
}

.form-modal-body .form-group select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 42px;
}

.form-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 28px;
  border-top: 1px solid #e2e8f0;
  background: #fafbfc;
  border-radius: 0 0 20px 20px;
  flex-shrink: 0;
}

.form-modal-footer-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-ghost {
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  background: transparent;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}

.btn-ghost:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-outline {
  padding: 10px 18px;
  font-size: 14px;
  font-weight: 500;
  color: #059669;
  background: #fff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}

.btn-outline:hover {
  background: #ecfdf5;
  border-color: #a5b4fc;
}

.btn-submit {
  padding: 12px 24px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border: none;
  border-radius: 10px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: formSpinner 0.7s linear infinite;
}

@keyframes formSpinner {
  to {
    transform: rotate(360deg);
  }
}

.form-modal-body .guardian-form {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.modal-content {
  background: white;
  border-radius: 24px;
  width: 90%;
  max-width: 950px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 28px 32px;
  border-bottom: 2px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
  border-radius: 24px 24px 0 0;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 26px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-print {
  padding: 10px 20px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-print:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.view-modal {
  max-width: 1000px;
}

.view-body {
  padding: 32px;
  max-height: calc(90vh - 200px);
  overflow-y: auto;
}

.biodata-section {
  margin-bottom: 32px;
  page-break-inside: avoid;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #059669;
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.biodata-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.biodata-item {
  display: contents;
}

.biodata-item .label {
  padding: 14px 18px;
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  font-size: 14px;
  border-right: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
}

.biodata-item:last-child .label,
.biodata-item:nth-last-child(2) .label {
  border-bottom: none;
}

.biodata-item .value {
  padding: 14px 18px;
  color: #1e293b;
  font-size: 14px;
  border-bottom: 1px solid #e2e8f0;
}

.biodata-item:last-child .value,
.biodata-item:nth-last-child(2) .value {
  border-bottom: none;
}

.biodata-item .value .hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
}

.btn-reset-password {
  padding: 8px 16px;
  background: #f59e0b;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-reset-password:hover:not(:disabled) {
  background: #d97706;
}

.btn-reset-password:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Riwayat Konseling di view modal */
.counseling-skeleton {
  padding: 0.5rem 0;
  min-height: 120px;
}
.counseling-loading {
  padding: 1rem;
  color: #64748b;
  font-size: 0.9rem;
}
.counseling-empty {
  padding: 1rem;
  color: #64748b;
  font-size: 0.9rem;
}
.counseling-empty .link-counseling {
  display: inline-block;
  margin-top: 0.5rem;
  color: #059669;
  font-weight: 500;
  text-decoration: none;
}
.counseling-empty .link-counseling:hover {
  text-decoration: underline;
}
.counseling-table-wrap {
  overflow-x: auto;
}
.counseling-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.counseling-table th,
.counseling-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.counseling-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
}
.counseling-table .summary-cell {
  max-width: 180px;
}
.counseling-status {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}
.counseling-status.status-jadwal { background: #e0f2fe; color: #0369a1; }
.counseling-status.status-berlangsung { background: #fef3c7; color: #b45309; }
.counseling-status.status-selesai { background: #d1fae5; color: #047857; }
.counseling-status.status-dibatalkan { background: #f1f5f9; color: #64748b; }
.counseling-status.status-ekskul-aktif { background: #d1fae5; color: #047857; }
.counseling-status.status-ekskul-keluar { background: #feebc8; color: #c05621; }
.counseling-status.status-ekskul-lulus { background: #e9d8fd; color: #553c9a; }
.link-counseling {
  color: #059669;
  font-weight: 500;
  text-decoration: none;
}
.link-counseling:hover {
  text-decoration: underline;
}
.link-counseling-footer {
  display: inline-block;
  margin-top: 0.75rem;
  font-size: 0.9rem;
}

@media print {
  .modal-overlay {
    position: static;
    background: white;
  }
  
  .modal-content {
    box-shadow: none;
    max-width: 100%;
    max-height: none;
  }
  
  .modal-header .header-actions,
  .modal-footer {
    display: none !important;
  }
  
  .view-body {
    max-height: none;
    overflow: visible;
  }
}

.btn-buku-induk-link {
  color: var(--color-primary, #059669);
  text-decoration: none;
  font-size: 0.9rem;
  padding: 8px 12px;
  border-radius: 8px;
}
.btn-buku-induk-link:hover {
  text-decoration: underline;
  background: #f1f5f9;
}
.btn-close {
  background: #f1f5f9;
  border: none;
  font-size: 24px;
  color: #64748b;
  cursor: pointer;
  line-height: 1;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
  transform: rotate(90deg);
}

.modal-body {
  padding: 32px;
  background: #ffffff;
}

.tabs-nav {
  display: flex;
  gap: 4px;
  margin-bottom: 32px;
  padding-bottom: 0;
  border-bottom: 2px solid #e2e8f0;
  overflow-x: auto;
  scrollbar-width: none;
}

.tabs-nav::-webkit-scrollbar {
  display: none;
}

.tab-btn {
  padding: 14px 24px;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  transition: all 0.3s ease;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  position: relative;
}

.tab-btn svg {
  transition: all 0.3s ease;
  opacity: 0.7;
}

.tab-btn:hover {
  color: #059669;
  background: #f8fafc;
  border-radius: 8px 8px 0 0;
}

.tab-btn:hover svg {
  opacity: 1;
  transform: scale(1.1);
}

.tab-btn.active {
  color: #059669;
  border-bottom-color: #059669;
  font-weight: 600;
  background: linear-gradient(to bottom, rgba(5, 150, 105, 0.05), transparent);
}

.tab-btn.active svg {
  opacity: 1;
  color: #059669;
}

.tab-content {
  min-height: 300px;
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.guardian-form {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  position: relative;
}

.form-group label {
  margin-bottom: 10px;
  color: #1e293b;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.form-group label::after {
  content: '';
  flex: 1;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 14px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #ffffff;
  transition: all 0.3s ease;
  color: #1e293b;
  font-family: inherit;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.form-group input:hover,
.form-group select:hover,
.form-group textarea:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1), 0 4px 12px rgba(5, 150, 105, 0.15);
  transform: translateY(-1px);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #94a3b8;
  opacity: 0.7;
}

.form-group select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 45px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  margin-top: 40px;
  padding-top: 24px;
  border-top: 2px solid #f1f5f9;
}

/* Tombol aksi: 1 baris ke samping (horizontal), bukan ke bawah */
.action-buttons-group {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 8px;
  align-items: center;
}

.action-buttons-group .btn-compact {
  flex: 0 0 auto;
  white-space: nowrap;
}

.btn-compact {
  padding: 6px 10px !important;
  font-size: 12px !important;
  gap: 4px !important;
  border-radius: 8px;
}

.btn-compact svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.btn-add {
  border-color: #059669;
  color: #059669;
  background: rgba(5, 150, 105, 0.08);
}

.btn-add:hover {
  background: rgba(5, 150, 105, 0.15);
  border-color: #059669;
  color: #059669;
}

.btn-primary {
  padding: 10px 20px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  padding: 8px 16px;
  background: #ffffff;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.error-message {
  padding: 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid #fecaca;
  font-size: 14px;
}

.loading-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.loading-spinner {
  color: #059669;
}

.loading-state p {
  font-size: 16px;
  font-weight: 500;
  margin: 0;
}

/* Responsive Styles */
@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  /* Tombol aksi: 1 baris ke samping (horizontal), 4 tombol sejajar */
  .student-page .action-buttons-group {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 8px;
  }

  .student-page .action-buttons-group .btn-compact {
    flex: 1 1 0;
    min-width: 0;
    height: 36px;
    padding: 0 4px !important;
    font-size: 11px !important;
    justify-content: center;
    border-radius: 8px;
  }

  .student-page .action-buttons-group .btn-compact svg {
    width: 12px;
    height: 12px;
  }

  .student-page .action-buttons-group .btn-compact span {
    display: none;
  }

  /* Filter + pencarian: 1 baris ke samping, diperkecil */
  .student-page .filters-inline {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 6px;
    padding: 6px 8px;
  }

  .student-page .filters-inline .search-input {
    flex: 1 1 0;
    min-width: 0;
    padding: 6px 8px;
    font-size: 11px;
  }

  .student-page .filters-inline .filter-select {
    flex: 0 0 auto;
    min-width: 72px;
    max-width: 95px;
    padding: 6px 22px 6px 6px;
    font-size: 11px;
  }
}

@media (max-width: 768px) {
  .student-page {
    padding: 0 8px;
  }

  .page-header {
    margin-bottom: 16px;
    padding: 12px 0;
  }

  .header-content {
    gap: 12px;
  }

  .header-content .page-subtitle {
    font-size: 13px;
  }

  /* Tombol aksi: 1 baris ke samping (horizontal) */
  .student-page .action-buttons-group {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 6px;
  }

  .student-page .action-buttons-group .btn-compact {
    flex: 1 1 0;
    min-width: 0;
    height: 34px;
    padding: 0 4px !important;
    font-size: 10px !important;
  }

  .student-page .action-buttons-group .btn-compact svg {
    width: 12px;
    height: 12px;
  }

  /* Filter + pencarian: 1 baris ke samping, diperkecil */
  .student-page .filters-inline {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    padding: 5px 6px;
    margin-bottom: 10px;
    gap: 5px;
  }

  .student-page .filters-inline .search-input {
    flex: 1 1 0;
    min-width: 0;
    padding: 5px 6px;
    font-size: 11px;
    min-height: 32px;
  }

  .student-page .filters-inline .filter-select {
    flex: 0 0 auto;
    min-width: 62px;
    max-width: 82px;
    padding: 5px 18px 5px 6px;
    font-size: 10px;
    min-height: 32px;
  }

  /* Mobile: show cards, hide table */
  .table-container.table-desktop {
    display: none;
  }

  .student-cards.table-mobile {
    display: block;
  }

  .table-container {
    border-radius: 12px;
    overflow-x: auto;
  }

  .data-table {
    min-width: 800px;
  }

  .data-table th {
    padding: 12px 14px;
    font-size: 11px;
  }

  .data-table td {
    padding: 12px 14px;
    font-size: 13px;
  }

  .action-buttons {
    flex-wrap: wrap;
    gap: 6px;
  }

  .btn-action {
    width: 40px;
    height: 40px;
    padding: 6px;
  }

  .modal-content {
    width: 95%;
    max-width: 95%;
    margin: 20px auto;
    max-height: 90vh;
  }

  .form-modal-overlay {
    padding: 12px;
  }

  .form-modal-content {
    max-height: 95vh;
  }

  .form-modal-header {
    padding: 18px 20px;
  }

  .form-modal-title-wrap {
    gap: 12px;
  }

  .form-modal-icon {
    width: 42px;
    height: 42px;
  }

  .form-modal-title {
    font-size: 18px;
  }

  .form-modal-subtitle {
    font-size: 12px;
  }

  .form-modal-body {
    padding: 20px;
  }

  .form-tabs-nav {
    margin-bottom: 20px;
  }

  .form-tab-btn {
    padding: 10px 14px;
    font-size: 13px;
  }

  .form-modal-footer {
    padding: 16px 20px;
    flex-wrap: wrap;
  }

  .form-modal-footer-actions {
    flex-wrap: wrap;
    gap: 8px;
  }

  .modal-header {
    padding: 20px 20px;
  }

  .modal-header h3 {
    font-size: 20px;
  }

  .modal-body {
    padding: 20px;
  }

  .form-modal-body .form-row {
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 18px;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 20px;
  }

  .form-grid {
    grid-template-columns: 1fr !important;
  }

  .biodata-grid {
    grid-template-columns: 1fr;
  }

  .biodata-item .label {
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }

  .biodata-item:last-child .label {
    border-bottom: none;
  }

  .tabs-nav {
    margin-bottom: 24px;
    padding-bottom: 0;
    -webkit-overflow-scrolling: touch;
  }

  .tab-btn {
    padding: 12px 16px;
    font-size: 13px;
    flex-shrink: 0;
  }

  .modal-footer {
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 20px;
    margin-top: 24px;
  }

  .modal-footer .btn-primary,
  .modal-footer .btn-secondary {
    flex: 1;
    min-width: 120px;
    justify-content: center;
  }

  .view-body {
    padding: 20px;
  }

  .section-title {
    font-size: 18px;
  }
}

@media (max-width: 480px) {
  .student-page {
    padding: 0 8px;
  }

  .page-header {
    margin-bottom: 12px;
    padding: 8px 0;
  }

  .header-content .page-subtitle {
    font-size: 12px;
  }

  /* Tombol aksi: diperkecil agar tidak perlu scroll */
  .student-page .action-buttons-group .btn-compact {
    height: 32px;
    border-radius: 6px;
    padding: 0 3px !important;
    font-size: 10px !important;
  }

  .student-page .action-buttons-group .btn-compact svg {
    width: 11px;
    height: 11px;
  }

  /* Filter + pencarian: diperkecil agar tidak perlu scroll */
  .student-page .filters-inline {
    padding: 4px 6px;
    margin-bottom: 8px;
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 4px;
  }

  .student-page .filters-inline .search-input {
    font-size: 11px;
    min-height: 30px;
    padding: 4px 6px;
  }

  .student-page .filters-inline .filter-select {
    min-height: 30px;
    min-width: 56px;
    max-width: 72px;
    padding: 4px 16px 4px 5px;
    font-size: 10px;
  }

  .student-card {
    padding: 14px 12px;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .student-card-main {
    padding-bottom: 0;
  }

  .student-card-name {
    font-size: 15px;
  }

  .student-card-meta {
    font-size: 12px;
  }

  .student-card-actions {
    justify-content: flex-end;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
  }

  .student-card-actions .btn-action {
    width: 44px;
    height: 44px;
  }

  .data-table th,
  .data-table td {
    padding: 10px 12px;
    font-size: 12px;
  }

  .data-table th {
    font-size: 10px;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border-radius: 0;
    max-height: 100vh;
  }

  .form-modal-overlay {
    padding: 0;
  }

  .form-modal-content {
    max-height: 100vh;
    border-radius: 0;
  }

  .form-modal-header {
    padding: 14px 16px;
  }

  .form-modal-icon {
    width: 38px;
    height: 38px;
  }

  .form-modal-title {
    font-size: 17px;
  }

  .form-modal-subtitle {
    display: none;
  }

  .form-modal-body {
    padding: 16px;
  }

  .form-tabs-nav {
    margin-bottom: 16px;
    gap: 4px;
  }

  .form-tab-btn {
    padding: 8px 12px;
    font-size: 12px;
    gap: 6px;
  }

  .form-modal-footer {
    padding: 14px 16px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .form-modal-footer-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .btn-ghost,
  .btn-outline,
  .btn-submit {
    padding: 12px 16px;
    font-size: 14px;
  }

  .modal-header {
    padding: 16px 16px;
  }

  .modal-header h3 {
    font-size: 18px;
  }

  .modal-body {
    padding: 16px;
  }

  .tabs-nav {
    margin-bottom: 20px;
    gap: 2px;
    -webkit-overflow-scrolling: touch;
  }

  .tab-btn {
    flex: 0 0 auto;
    min-width: 0;
    font-size: 11px;
    padding: 10px 10px;
  }

  .form-modal-body .form-group input,
  .form-modal-body .form-group select,
  .form-modal-body .form-group textarea {
    padding: 12px 14px;
    font-size: 16px;
    min-height: 48px;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: 12px 14px;
    font-size: 16px;
    min-height: 48px;
  }

  .modal-footer {
    padding: 16px 0 0;
    margin-top: 20px;
  }

  .modal-footer .btn-secondary,
  .modal-footer .btn-primary {
    padding: 12px 16px;
  }

  .empty-state {
    padding: 40px 20px;
  }

  .empty-state h3 {
    font-size: 18px;
  }

  .empty-state p {
    font-size: 13px;
  }
}

/* Documents Section Styles */
.documents-section {
  padding: 1rem 0;
}

.upload-section {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  border: 1px solid #e2e8f0;
}

.upload-section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1e293b;
  font-weight: 600;
}

.file-preview {
  margin-top: 0.5rem;
  padding: 0.75rem;
  background: #e0f2fe;
  border-radius: 8px;
  border: 1px solid #bae6fd;
}

.file-preview p {
  margin: 0;
  font-size: 0.875rem;
  color: #0369a1;
}

.text-muted {
  color: #64748b;
  font-size: 0.875rem;
}

.documents-list {
  margin-top: 2rem;
}

.documents-list h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1e293b;
  font-weight: 600;
}

.document-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.document-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.document-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #cbd5e1;
}

.document-icon {
  color: #64748b;
  margin-bottom: 0.25rem;
}

.document-info {
  flex: 1;
}

.document-info h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  color: #1e293b;
  font-weight: 600;
}

.document-info p {
  margin: 0.25rem 0;
  font-size: 0.875rem;
}

.document-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
  padding-top: 0.5rem;
  border-top: 1px solid #f1f5f9;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.75rem;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.no-documents {
  text-align: center;
  padding: 3rem 2rem;
  color: #64748b;
  background: #f8fafc;
  border-radius: 12px;
  border: 2px dashed #cbd5e1;
}

.no-documents p {
  margin: 0;
  font-size: 0.875rem;
}
</style>
