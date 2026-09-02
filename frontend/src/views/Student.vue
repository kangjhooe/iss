<template>
    <div class="student-page">
      <div class="list-tabs">
        <button
          type="button"
          :class="['tab-btn', { active: !filters.only_trashed }]"
          @click="switchListTab(false)"
        >
          Daftar Siswa
        </button>
        <router-link to="/siswa-keluar" class="tab-btn">
          Siswa Keluar
        </router-link>
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
          <button
            v-if="canManageStudentAccount"
            type="button"
            class="btn-secondary btn-compact"
            @click="openNisSettings"
          >
            <span>Format NIS</span>
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

      <div
        v-if="!filters.only_trashed && !loading && canManageStudentAccount && nisNumbering.missing_nis_count > 0"
        class="account-status-banner nis-status-banner"
      >
        <div class="account-status-text">
          <strong>NIS lokal</strong>
          <span>{{ nisNumbering.missing_nis_count }} siswa aktif belum punya NIS</span>
          <span class="hint">
            Format berikutnya:
            {{ nisNumbering.preview || nisNumbering.preview_error || 'atur format dulu' }}
          </span>
        </div>
        <div class="account-status-actions">
          <button
            type="button"
            class="btn-secondary btn-compact"
            @click="filters.missing_nis = '1'; loadStudents(1)"
          >
            Lihat tanpa NIS
          </button>
          <button
            type="button"
            class="btn-secondary btn-compact"
            @click="openNisSettings"
          >
            Atur format
          </button>
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="nisGenerateLoading || !!nisNumbering.preview_error"
            @click="bulkGenerateNis"
          >
            {{ nisGenerateLoading ? 'Memuat pratinjau…' : `Pratinjau NIS (${nisNumbering.missing_nis_count})` }}
          </button>
        </div>
      </div>

      <div
        v-if="!filters.only_trashed && (filters.account_status || filters.missing_nis)"
        class="list-subset-bar"
      >
        <span>Menampilkan:
          <strong v-if="filters.account_status === 'missing'">siswa tanpa akun</strong>
          <strong v-else-if="filters.account_status === 'incomplete'">siswa dengan data login kurang</strong>
          <strong v-else-if="filters.account_status === 'ready'">siswa yang sudah punya akun</strong>
          <strong v-if="filters.missing_nis">{{ filters.account_status ? ' · ' : '' }}siswa tanpa NIS</strong>
        </span>
        <button type="button" class="btn-ghost-link" @click="clearListSubset">Tampilkan semua</button>
      </div>
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
          :show-status="filters.only_trashed"
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
          @force-delete="handleForceDeleteStudent"
        >
          <template #empty>
            <template v-if="filters.only_trashed">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <h3>Tidak ada data di kotak sampah</h3>
              <p>Data siswa yang dihapus akan muncul di sini. Pulihkan untuk mengembalikan, atau hapus permanen jika data salah.</p>
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
        <PaginationBar
          v-if="students.length > 0"
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="siswa"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay form-modal-overlay" @click="closeModal">
        <div
          class="modal-content form-modal-content"
          :class="{ 'form-modal-content--choice': showAddModal && !showEditModal && addMode === 'choose' }"
          @click.stop
        >
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
                <p class="form-modal-subtitle">{{ addModalSubtitle }}</p>
              </div>
            </div>
            <button @click="closeModal" class="btn-close-modal" type="button" aria-label="Tutup">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div
            v-if="showAddModal && !showEditModal && addMode === 'choose'"
            class="form-modal-body add-choice-panel"
          >
            <button type="button" class="add-choice-row" @click="addMode = 'manual'">
              <span class="add-choice-icon-wrap" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                  <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="add-choice-text">
                <strong>Tambah manual</strong>
                <span>Isi formulir siswa baru satu per satu</span>
              </span>
              <span class="add-choice-arrow" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </button>
            <button type="button" class="add-choice-row" @click="addMode = 'feeder'">
              <span class="add-choice-icon-wrap add-choice-icon-wrap--pull" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                  <path d="M12 3v12M7 8l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span class="add-choice-text">
                <strong>Tarik dari jenjang sebelumnya</strong>
                <span>Ambil alumni lulus dari MTs/SMP. Arsip sekolah asal tetap ada.</span>
              </span>
              <span class="add-choice-arrow" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
            </button>
            <div class="add-choice-footer">
              <button type="button" class="btn-ghost" @click="closeModal">Batal</button>
            </div>
          </div>

          <div
            v-if="showAddModal && !showEditModal && addMode !== 'choose'"
            class="add-mode-back-bar"
          >
            <button type="button" class="add-mode-back-btn" @click="backToAddChoice">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Pilih cara lain
            </button>
          </div>

          <div v-if="showAddModal && !showEditModal && addMode === 'feeder'" class="form-modal-body feeder-panel">
            <p class="form-hint">
              Masukkan NPSN sekolah asal (jenjang sebelumnya). Arsip alumni di sekolah asal tetap tersimpan;
              yang ditarik menjadi siswa baru di sekolah ini.
            </p>
            <div class="feeder-npsn-row">
              <div class="form-group">
                <label>NPSN sekolah asal</label>
                <input
                  v-model="feederNpsn"
                  type="text"
                  inputmode="numeric"
                  maxlength="8"
                  placeholder="8 digit NPSN"
                  @input="feederNpsn = feederNpsn.replace(/\D/g, '').slice(0, 8)"
                />
              </div>
              <button
                type="button"
                class="btn-outline"
                :disabled="feederLoading || feederNpsn.length !== 8"
                @click="loadFeederAlumni"
              >
                {{ feederLoading ? 'Memuat...' : 'Tampilkan alumni' }}
              </button>
            </div>
            <p v-if="feederOrigin" class="feeder-origin">
              {{ feederOrigin.name }}
              <span class="text-muted">({{ feederOrigin.level }} · NPSN {{ feederOrigin.npsn }})</span>
            </p>
            <div v-if="feederAlumni.length" class="form-group">
              <label>Cari nama atau NIK</label>
              <input v-model="feederSearch" type="text" placeholder="Filter nama atau NIK..." />
            </div>
            <div v-if="filteredFeederAlumni.length" class="feeder-list-wrap">
              <label class="feeder-select-all">
                <input type="checkbox" :checked="feederAllSelected" @change="toggleSelectAllFeeder" />
                Pilih semua yang bisa ditarik ({{ feederSelectable.length }})
              </label>
              <div class="feeder-list">
                <label
                  v-for="row in filteredFeederAlumni"
                  :key="row.id"
                  :class="['feeder-row', { disabled: row.already_enrolled }]"
                >
                  <input
                    type="checkbox"
                    :value="row.id"
                    :disabled="row.already_enrolled"
                    v-model="feederSelectedIds"
                  />
                  <span class="feeder-row-main">
                    <strong>{{ row.name }}</strong>
                    <span class="feeder-nisn">NIK {{ row.nik || '–' }}</span>
                    <span v-if="row.nisn" class="feeder-nisn feeder-nisn-secondary">NISN {{ row.nisn }}</span>
                  </span>
                  <span class="feeder-row-meta">
                    <span v-if="row.gender">{{ row.gender === 'P' ? 'P' : 'L' }}</span>
                    <span v-if="row.graduation_year">Lulus {{ row.graduation_year }}</span>
                    <span v-if="row.already_enrolled" class="feeder-already">Sudah terdaftar</span>
                  </span>
                </label>
              </div>
            </div>
            <p v-else-if="feederOrigin && !feederLoading" class="text-muted">Tidak ada alumni yang cocok.</p>
            <div v-if="feederAlumni.length" class="feeder-class-row">
              <div v-if="availableStudentGrades.length" class="form-group">
                <label>Tingkat masuk</label>
                <select v-model="feederTingkat">
                  <option v-for="grade in availableStudentGrades" :key="grade" :value="grade">
                    Kelas {{ grade }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Kelas (opsional)</label>
                <select v-model="feederClassId">
                  <option value="">Belum ditentukan</option>
                  <option v-for="c in matchingFeederClassList" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div v-if="feederError" class="error-message">{{ feederError }}</div>
            <div class="form-modal-footer feeder-footer">
              <div class="form-modal-footer-actions">
                <button type="button" @click="backToAddChoice" class="btn-ghost">Kembali</button>
                <button type="button" @click="closeModal" class="btn-ghost">Batal</button>
              </div>
              <button
                type="button"
                class="btn-submit"
                :disabled="feederSaving || !feederSelectedIds.length"
                @click="submitFeederPull"
              >
                <span v-if="feederSaving" class="btn-spinner"></span>
                <span>{{ feederSaving ? 'Menarik...' : `Tarik ${feederSelectedIds.length || ''} siswa` }}</span>
              </button>
            </div>
          </div>
          
          <form
            v-if="showEditModal || (showAddModal && addMode === 'manual')"
            @submit.prevent="handleSubmit"
            class="form-modal-body"
          >
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
              <div class="form-photo-row">
                <div class="form-photo-preview">
                  <img v-if="photoPreviewUrl" :src="photoPreviewUrl" alt="Foto siswa" />
                  <span v-else>3×4</span>
                </div>
                <div class="form-photo-meta">
                  <label>Foto siswa</label>
                  <input type="file" :accept="PROFILE_PHOTO_ACCEPT" @change="onStudentPhotoSelect" />
                  <p class="field-hint">JPG atau PNG, maks. 10 MB. Foto dipotong otomatis ke ukuran 3×4 (autocrop). {{ editingId ? 'Langsung tersimpan setelah dipotong.' : 'Diunggah setelah data siswa disimpan.' }}</p>
                  <button
                    v-if="editingId && (formPhotoUrl || pendingPhotoFile)"
                    type="button"
                    class="btn-outline"
                    :disabled="photoUploading"
                    @click="removeStudentPhoto"
                  >Hapus foto</button>
                </div>
              </div>
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
                  <p class="field-hint">Kosongkan untuk generate otomatis sesuai format sekolah.</p>
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

              <p class="form-section-label">Alamat</p>
              <AddressCascade v-model="form" street-label="Jalan / RT / RW" />

              <div class="form-row">
                <div class="form-group">
                  <label>Telepon</label>
                  <input v-model="form.phone" placeholder="Nomor HP siswa / orang tua" />
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" v-model="form.email" placeholder="email@contoh.com" />
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
                <div class="form-group">
                  <label>NPSN Sekolah Asal</label>
                  <input v-model="form.previous_school_npsn" maxlength="20" placeholder="8 digit NPSN" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Alamat Sekolah Asal</label>
                  <input v-model="form.previous_school_address" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea v-model="form.notes" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
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
                          <TableAction kind="download" title="Download" @click="downloadDocument(doc.id)" />
                          <TableAction kind="delete" @click="deleteDocument(doc.id)" />
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
              <div class="form-modal-footer-actions">
                <button
                  v-if="showAddModal && !showEditModal"
                  type="button"
                  @click="backToAddChoice"
                  class="btn-ghost"
                >Kembali</button>
                <button type="button" @click="closeModal" class="btn-ghost">Batal</button>
              </div>
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
              <button @click="previewBukuIndukPdf" class="btn-print" :disabled="printingBukuInduk" title="Cetak Buku Induk (PDF)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ printingBukuInduk ? 'Membuka...' : 'Buku Induk' }}</span>
              </button>
              <button @click="printBiodataPdf('lengkap')" class="btn-print btn-print-biodata" :disabled="printingBiodata" title="Cetak Biodata Lengkap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ printingBiodata === 'lengkap' ? 'Membuka...' : 'Biodata Lengkap' }}</span>
              </button>
              <button @click="printBiodataPdf('singkat')" class="btn-print btn-print-biodata" :disabled="!!printingBiodata" title="Cetak Biodata Singkat (1 lembar)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ printingBiodata === 'singkat' ? 'Membuka...' : 'Biodata Singkat' }}</span>
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
              <div class="biodata-photo-wrap">
                <img v-if="viewingStudent.photo_url" :src="viewingStudent.photo_url" class="biodata-photo" :alt="viewingStudent.name" />
                <div v-else class="biodata-photo biodata-photo-empty">3×4</div>
              </div>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingStudent.nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIS</span>
                  <span class="value">
                    {{ viewingStudent.nis || '-' }}
                    <button
                      v-if="canManageStudentAccount && !viewingStudent.nis"
                      type="button"
                      class="btn-secondary btn-compact btn-inline-nis"
                      :disabled="nisGenerateLoading"
                      @click="generateNisForViewing"
                    >
                      {{ nisGenerateLoading ? 'Mengisi…' : 'Generate NIS' }}
                    </button>
                  </span>
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
                  <span class="value">{{ formatFullAddress(viewingStudent) || '-' }}</span>
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
                  <span class="value">{{ studentClassName(viewingStudent) || '-' }}</span>
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
                  <span class="label">NPSN Sekolah Asal</span>
                  <span class="value">{{ viewingStudent.previous_school_npsn || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Alamat Sekolah Asal</span>
                  <span class="value">{{ viewingStudent.previous_school_address || '-' }}</span>
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
              v-if="viewingStudent?.status === 'Aktif'"
              type="button"
              class="btn-secondary"
              :disabled="graduatingStudent || leavingStudent"
              @click="openLeaveModal"
            >
              Tandai Keluar
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

    <div v-if="leaveModal.open" class="modal-overlay" @click="closeLeaveModal">
      <div class="modal-content leave-modal" @click.stop>
        <div class="modal-header">
          <h3>Tandai siswa keluar</h3>
          <button type="button" class="btn-close" @click="closeLeaveModal">×</button>
        </div>
        <div class="modal-body">
          <p class="modal-message">
            {{ viewingStudent?.name || 'Siswa ini' }} akan dipindah dari daftar aktif ke
            <router-link to="/siswa-keluar" @click="closeLeaveModal">Siswa Keluar</router-link>.
          </p>
          <div class="leave-options">
            <label v-for="opt in leaveStatusOptions" :key="opt.value" class="leave-option">
              <input v-model="leaveModal.status" type="radio" :value="opt.value" />
              <span>
                <strong>{{ opt.label }}</strong>
                <small>{{ opt.hint }}</small>
              </span>
            </label>
          </div>
          <p v-if="leaveModal.status === 'Pindah'" class="hint">
            Mutasi ke sekolah lain yang terdaftar di aplikasi lebih tepat lewat menu
            <router-link to="/student-mutation" @click="closeLeaveModal">Mutasi</router-link>.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" :disabled="leavingStudent" @click="closeLeaveModal">Batal</button>
          <button type="button" class="btn-primary" :disabled="leavingStudent" @click="confirmLeaveStudent">
            {{ leavingStudent ? 'Memproses...' : 'Simpan' }}
          </button>
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
          <p class="hint">File hasil Export bisa diedit lalu diimpor lagi. Siswa Aktif yang NIK, NISN, atau NIS-nya sudah ada akan diperbarui. Siswa Pindah, Tidak Aktif, Lulus, Drop Out, atau yang ada di kotak sampah ditolak — bukan diaktifkan kembali. Aktifkan kembali di Siswa Keluar jika siswa kembali bersekolah. Akun login dibuat otomatis jika NIK (16 digit) dan tanggal lahir terisi.</p>
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
          <div v-if="importPreview.serverErrors?.length" class="import-invalid-box import-reject-box">
            <strong>Ditolak ({{ importPreview.serverErrors.length }} baris)</strong>
            <p class="hint">Siswa tidak aktif, mutasi, alumni, atau yang sudah terdaftar di sekolah lain tidak diubah.</p>
            <ul>
              <li v-for="(err, idx) in importPreview.serverErrors" :key="'e'+idx">{{ err }}</li>
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

    <div v-if="nisSettings.open" class="modal-overlay" @click="nisSettings.open = false">
      <div class="modal-content import-preview-modal nis-settings-modal" @click.stop>
        <div class="modal-header">
          <h3>Format NIS lokal</h3>
          <button type="button" class="btn-close" @click="nisSettings.open = false">×</button>
        </div>
        <div class="modal-body">
          <p class="hint">Setiap sekolah bisa memakai format sendiri. NIS yang sudah terisi tidak diubah.</p>
          <div class="form-group">
            <label>Format</label>
            <select v-model="nisSettings.form.preset">
              <option v-for="p in nisNumbering.presets" :key="p.value" :value="p.value">
                {{ p.label }} (contoh {{ p.example }})
              </option>
            </select>
          </div>
          <div v-if="needsNisPrefix" class="form-group">
            <label>Kode sekolah (prefix)</label>
            <input v-model="nisSettings.form.prefix" maxlength="12" placeholder="Contoh: S atau MTs" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Digit nomor urut</label>
              <select v-model.number="nisSettings.form.seq_digits">
                <option :value="3">3 (001)</option>
                <option :value="4">4 (0001)</option>
                <option :value="5">5 (00001)</option>
                <option :value="6">6 (000001)</option>
              </select>
            </div>
            <div class="form-group">
              <label>Reset nomor urut</label>
              <select v-model="nisSettings.form.reset">
                <option value="yearly">Setiap tahun ajaran</option>
                <option value="never">Tidak pernah (terus bertambah)</option>
              </select>
            </div>
          </div>
          <div v-if="nisSettings.form.preset === 'custom'" class="form-group">
            <label>Pola kustom</label>
            <input v-model="nisSettings.form.pattern" placeholder="{PREFIX}/{YY}/{SEQ}" />
            <p class="field-hint">Token: {YYYY} {YY} {PREFIX} {NPSN4} {SEQ} atau {SEQ:4}</p>
          </div>
          <p class="nis-preview-line">
            Contoh format:
            <strong>{{ nisSettingsPreview || '—' }}</strong>
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="nisSettings.open = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="nisSettings.saving" @click="saveNisSettings">
            {{ nisSettings.saving ? 'Menyimpan…' : 'Simpan format' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="nisAssignPreview.open" class="modal-overlay" @click="!nisAssignPreview.applying && closeNisAssignPreview()">
      <div class="modal-content import-preview-modal nis-assign-modal" @click.stop>
        <div class="modal-header">
          <h3>{{ nisAssignPreview.result ? 'NIS sudah diterapkan' : 'Pratinjau NIS lokal' }}</h3>
          <button type="button" class="btn-close" :disabled="nisAssignPreview.applying" @click="closeNisAssignPreview">×</button>
        </div>
        <div class="modal-body">
          <template v-if="nisAssignPreview.result">
            <p class="modal-message">
              {{ nisAssignPreview.result.assigned }} siswa mendapat NIS. Nomor ini sudah tersimpan di data siswa.
            </p>
            <div v-if="nisAssignPreview.result.assigned_rows?.length" class="import-preview-table-wrap">
              <table class="counseling-table">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>NIS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in nisAssignPreview.result.assigned_rows.slice(0, 40)" :key="'r'+row.id">
                    <td>{{ row.name }}</td>
                    <td>{{ row.class || row.tingkat || '—' }}</td>
                    <td><strong>{{ row.nis }}</strong></td>
                  </tr>
                </tbody>
              </table>
              <p v-if="nisAssignPreview.result.assigned_rows.length > 40" class="hint">
                …dan {{ nisAssignPreview.result.assigned_rows.length - 40 }} siswa lain
              </p>
            </div>
            <div v-if="nisAssignPreview.result.errors?.length" class="import-invalid-box">
              <strong>Sebagian dilewati</strong>
              <ul>
                <li v-for="(err, idx) in nisAssignPreview.result.errors.slice(0, 8)" :key="'ne'+idx">{{ err }}</li>
              </ul>
            </div>
          </template>
          <template v-else>
            <p class="modal-message">
              {{ nisAssignPreview.rows.length }} siswa akan mendapat NIS jika Anda menerapkan.
              Nomor di bawah belum disimpan.
            </p>
            <p class="hint">
              Centang siswa yang akan diisi. Batal tidak mengubah data.
              Jika sebagian tidak dicentang, nomor urut dihitung ulang saat terapkan.
            </p>
            <p v-if="nisAssignPreview.truncated" class="hint">
              Hanya {{ nisAssignPreview.rows.length }} dari {{ nisAssignPreview.total_missing }} siswa ditampilkan. Terapkan per batch.
            </p>
            <p v-if="nisAssignPreview.error" class="error-text">{{ nisAssignPreview.error }}</p>
            <div v-if="nisAssignPreview.loading" class="hint">Memuat pratinjau…</div>
            <div v-else-if="nisAssignPreview.rows.length" class="import-preview-table-wrap">
              <table class="counseling-table">
                <thead>
                  <tr>
                    <th class="col-check">
                      <input
                        type="checkbox"
                        :checked="nisAssignAllSelected"
                        :indeterminate.prop="nisAssignSomeSelected && !nisAssignAllSelected"
                        @change="toggleNisAssignAll($event.target.checked)"
                      />
                    </th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>NIS usulan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in nisAssignPreview.rows" :key="row.id">
                    <td class="col-check">
                      <input v-model="nisAssignPreview.selected[row.id]" type="checkbox" />
                    </td>
                    <td>{{ row.name }}</td>
                    <td>{{ row.class || row.tingkat || '—' }}</td>
                    <td><strong>{{ row.proposed_nis }}</strong></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
        <div class="modal-footer">
          <template v-if="nisAssignPreview.result">
            <button type="button" class="btn-primary" @click="closeNisAssignPreview">Tutup</button>
          </template>
          <template v-else>
            <button type="button" class="btn-secondary" :disabled="nisAssignPreview.applying" @click="closeNisAssignPreview">Batal</button>
            <button
              type="button"
              class="btn-primary"
              :disabled="nisAssignPreview.loading || nisAssignPreview.applying || nisAssignSelectedCount === 0"
              @click="applyNisAssignPreview"
            >
              {{ nisAssignPreview.applying ? 'Menerapkan…' : `Terapkan (${nisAssignSelectedCount})` }}
            </button>
          </template>
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
    <ProfilePhotoCropModal
      v-model:show="photoCropModalOpen"
      :file="photoCropSourceFile"
      @confirm="onStudentPhotoCropped"
      @cancel="onStudentPhotoCropCancel"
    />
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import ProfilePhotoCropModal from '@/components/ProfilePhotoCropModal.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import StudentTable from '@/components/student/StudentTable.vue'
import StudentTableSkeleton from '@/components/StudentTableSkeleton.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AddressCascade from '@/components/AddressCascade.vue'
import { emptyAddress, formatFullAddress } from '@/utils/addressFields'
import {
  STUDENT_EXCEL_COL_WIDTHS,
  mapStudentImportExcelRows,
  studentExcelDataSheetName,
  studentExcelGuideRows,
  studentExcelTemplateRow,
  studentToExcelRow,
} from '@/utils/studentExcel'
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
import { studentLoginCredentials, mapStudentCreatedAccounts } from '@/utils/accountCredentials'
import { PROFILE_PHOTO_ACCEPT, profilePhotoFormData, validateProfilePhoto, validateProfilePhotoSource } from '@/utils/profilePhoto'
import { openPdfBlob } from '@/utils/pdfPreview'
import { parseBlobError } from '@/utils/blobError'
import * as XLSX from 'xlsx'

const toast = useToast()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()
const accountCredentials = ref(null)

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
  changePerPage,
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
const nisGenerateLoading = ref(false)
const nisAssignPreview = ref({
  open: false,
  loading: false,
  applying: false,
  rows: [],
  selected: {},
  truncated: false,
  total_missing: 0,
  error: null,
  result: null,
})
const nisAssignSelectedCount = computed(() => {
  return nisAssignPreview.value.rows.filter((row) => nisAssignPreview.value.selected[row.id]).length
})
const nisAssignAllSelected = computed(() => {
  const rows = nisAssignPreview.value.rows
  return rows.length > 0 && rows.every((row) => nisAssignPreview.value.selected[row.id])
})
const nisAssignSomeSelected = computed(() => nisAssignSelectedCount.value > 0)
const nisNumbering = ref({
  settings: {
    preset: 'tahun_urut',
    prefix: '',
    seq_digits: 5,
    reset: 'yearly',
    pattern: '',
  },
  presets: [],
  preview: '',
  preview_error: null,
  missing_nis_count: 0,
  year_code: '',
})
const nisSettings = ref({
  open: false,
  saving: false,
  form: {
    preset: 'tahun_urut',
    prefix: '',
    seq_digits: 5,
    reset: 'yearly',
    pattern: '',
  },
})
const needsNisPrefix = computed(() => {
  const preset = nisSettings.value.form.preset
  return preset === 'prefix_tahun_urut' || preset === 'prefix_tahun2_urut' || preset === 'custom'
})
const nisSettingsPreview = computed(() => {
  const form = nisSettings.value.form
  const year = nisNumbering.value.year_code || String(new Date().getFullYear())
  const yy = year.slice(-2)
  const seq = String(1).padStart(Number(form.seq_digits) || 5, '0')
  const prefix = (form.prefix || '').trim()
  const npsn4 = (myInstitution.value?.npsn || '0000').toString().slice(-4).padStart(4, '0')
  const map = {
    tahun_urut: `${year}${seq}`,
    tahun2_urut: `${yy}${seq}`,
    urut_saja: seq,
    prefix_tahun_urut: `${prefix}${year}${seq}`,
    prefix_tahun2_urut: `${prefix}${yy}${seq}`,
    npsn4_tahun_urut: `${npsn4}${yy}${seq}`,
  }
  if (form.preset === 'custom') {
    const digitsMatch = (form.pattern || '').match(/\{SEQ:([1-8])\}/)
    const customSeq = String(1).padStart(digitsMatch ? Number(digitsMatch[1]) : (Number(form.seq_digits) || 5), '0')
    return (form.pattern || '')
      .replace(/\{SEQ:[1-8]\}/g, customSeq)
      .replace('{YYYY}', year)
      .replace('{YY}', yy)
      .replace('{PREFIX}', prefix)
      .replace('{NPSN4}', npsn4)
      .replace('{SEQ}', customSeq)
  }
  return map[form.preset] || nisNumbering.value.preview
})
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
  loadNisNumbering()
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
    const createdAccounts = res.data?.data?.created_accounts || []
    const items = mapStudentCreatedAccounts(createdAccounts)
    if (items.length) {
      accountCredentials.value = {
        title: 'Akun login siswa dibuat',
        loginLabel: 'NIK',
        hint: 'Siswa login dengan NIK. Sandi awal = tanggal lahir (DDMMYYYY). Kartu ini hanya hilang jika ditutup.',
        items,
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun massal selesai diproses')
    }
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

async function loadNisNumbering() {
  if (filters.value.only_trashed) return
  try {
    const res = await studentApi.getNisNumbering()
    const data = res.data?.data || {}
    nisNumbering.value = {
      settings: {
        preset: data.settings?.preset || 'tahun_urut',
        prefix: data.settings?.prefix || '',
        seq_digits: data.settings?.seq_digits || 5,
        reset: data.settings?.reset || 'yearly',
        pattern: data.settings?.pattern || '',
      },
      presets: data.presets || [],
      preview: data.preview || '',
      preview_error: data.preview_error || null,
      missing_nis_count: data.missing_nis_count || 0,
      year_code: data.year_code || '',
    }
  } catch {
    /* ignore banner errors */
  }
}

function openNisSettings() {
  nisSettings.value.form = { ...nisNumbering.value.settings }
  nisSettings.value.open = true
}

async function saveNisSettings() {
  if (nisSettings.value.saving) return
  nisSettings.value.saving = true
  try {
    const res = await studentApi.updateNisNumbering(nisSettings.value.form)
    const data = res.data?.data || {}
    nisNumbering.value = {
      settings: {
        preset: data.settings?.preset || nisSettings.value.form.preset,
        prefix: data.settings?.prefix || '',
        seq_digits: data.settings?.seq_digits || 5,
        reset: data.settings?.reset || 'yearly',
        pattern: data.settings?.pattern || '',
      },
      presets: data.presets || nisNumbering.value.presets,
      preview: data.preview || '',
      preview_error: data.preview_error || null,
      missing_nis_count: data.missing_nis_count ?? nisNumbering.value.missing_nis_count,
      year_code: data.year_code || nisNumbering.value.year_code,
    }
    nisSettings.value.open = false
    toast.success('Berhasil', res.data?.message || 'Format NIS disimpan')
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.response?.data?.errors?.prefix?.[0] || 'Gagal menyimpan format NIS')
  } finally {
    nisSettings.value.saving = false
  }
}

async function bulkGenerateNis() {
  if (nisGenerateLoading.value || !canManageStudentAccount.value) return
  if (nisNumbering.value.preview_error) {
    toast.error('Format belum siap', nisNumbering.value.preview_error)
    return
  }
  const missing = nisNumbering.value.missing_nis_count || 0
  if (missing <= 0) {
    toast.success('Info', 'Semua siswa aktif sudah punya NIS')
    return
  }
  await openNisAssignPreview()
}

function closeNisAssignPreview() {
  if (nisAssignPreview.value.applying) return
  nisAssignPreview.value.open = false
  nisAssignPreview.value.result = null
  nisAssignPreview.value.error = null
}

function toggleNisAssignAll(checked) {
  const next = {}
  nisAssignPreview.value.rows.forEach((row) => {
    next[row.id] = !!checked
  })
  nisAssignPreview.value.selected = next
}

async function openNisAssignPreview(studentIds) {
  nisGenerateLoading.value = true
  nisAssignPreview.value = {
    open: true,
    loading: true,
    applying: false,
    rows: [],
    selected: {},
    truncated: false,
    total_missing: 0,
    error: null,
    result: null,
  }
  try {
    const payload = { limit: 500 }
    if (studentIds?.length) payload.student_ids = studentIds
    const res = await studentApi.previewGenerateNis(payload)
    const rows = res.data?.data?.rows || []
    const selected = {}
    rows.forEach((row) => { selected[row.id] = true })
    nisAssignPreview.value.rows = rows
    nisAssignPreview.value.selected = selected
    nisAssignPreview.value.truncated = !!res.data?.data?.truncated
    nisAssignPreview.value.total_missing = res.data?.data?.total_missing || rows.length
    if (!rows.length) {
      nisAssignPreview.value.error = 'Tidak ada siswa tanpa NIS untuk dipratinjau.'
    }
  } catch (err) {
    nisAssignPreview.value.error = err.response?.data?.message || 'Gagal memuat pratinjau NIS'
  } finally {
    nisAssignPreview.value.loading = false
    nisGenerateLoading.value = false
  }
}

async function applyNisAssignPreview() {
  const ids = nisAssignPreview.value.rows
    .filter((row) => nisAssignPreview.value.selected[row.id])
    .map((row) => row.id)
  if (!ids.length || nisAssignPreview.value.applying) return
  nisAssignPreview.value.applying = true
  try {
    const res = await studentApi.generateNisBulk({ student_ids: ids, limit: 2000 })
    nisAssignPreview.value.result = {
      assigned: res.data?.data?.assigned || 0,
      assigned_rows: res.data?.data?.assigned_rows || [],
      errors: res.data?.data?.errors || [],
    }
    toast.success('Berhasil', res.data?.message || 'NIS diterapkan')
    await loadStudents(1)
    await loadNisNumbering()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menerapkan NIS')
  } finally {
    nisAssignPreview.value.applying = false
  }
}

async function generateNisForViewing() {
  if (!viewingStudent.value?.id || nisGenerateLoading.value) return
  nisGenerateLoading.value = true
  try {
    const previewRes = await studentApi.previewGenerateNis({
      student_ids: [viewingStudent.value.id],
      limit: 1,
    })
    const row = previewRes.data?.data?.rows?.[0]
    if (!row?.proposed_nis) {
      toast.error('Gagal', 'Tidak ada usulan NIS untuk siswa ini')
      return
    }
    nisGenerateLoading.value = false
    const ok = await showConfirm({
      title: 'Terapkan NIS lokal?',
      message: `${viewingStudent.value.name} akan mendapat NIS ${row.proposed_nis}.`,
      warning: 'Setelah diterapkan, nomor ini tersimpan di data siswa. Batal jika formatnya salah.',
      confirmText: 'Terapkan',
      cancelText: 'Batal',
      confirmVariant: 'primary',
    })
    if (!ok) return

    nisGenerateLoading.value = true
    const res = await studentApi.generateNis(viewingStudent.value.id)
    if (res.data?.data) {
      viewingStudent.value = { ...viewingStudent.value, ...res.data.data }
    }
    toast.success('Berhasil', res.data?.message || 'NIS berhasil diterapkan')
    await loadStudents()
    await loadNisNumbering()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal generate NIS')
  } finally {
    nisGenerateLoading.value = false
  }
}
const showAddModal = ref(false)
const showEditModal = ref(false)
const addMode = ref('choose')
const feederNpsn = ref('')
const feederOrigin = ref(null)
const feederAlumni = ref([])
const feederSelectedIds = ref([])
const feederSearch = ref('')
const feederTingkat = ref(null)
const feederClassId = ref('')
const feederLoading = ref(false)
const feederSaving = ref(false)
const feederError = ref('')
const showViewModal = ref(false)
const viewingStudent = ref(null)
const graduatingStudent = ref(false)
const leavingStudent = ref(false)
const leaveModal = ref({ open: false, status: 'Tidak Aktif' })
const leaveStatusOptions = [
  { value: 'Pindah', label: 'Pindah', hint: 'Pindah sekolah, tanpa alur Mutasi di aplikasi.' },
  { value: 'Drop Out', label: 'Drop Out', hint: 'Berhenti sekolah atau dikeluarkan.' },
  { value: 'Tidak Aktif', label: 'Tidak Aktif', hint: 'Tidak aktif, termasuk meninggal dunia atau kasus lain.' },
]
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

const addModalSubtitle = computed(() => {
  if (showEditModal.value) return 'Perbarui data siswa'
  if (addMode.value === 'choose') return 'Pilih cara menambahkan siswa'
  if (addMode.value === 'feeder') return 'Tarik alumni jenjang sebelumnya menjadi siswa baru'
  return 'Isi data siswa baru'
})

const filteredFeederAlumni = computed(() => {
  const term = feederSearch.value.trim().toLowerCase()
  if (!term) return feederAlumni.value
  return feederAlumni.value.filter((row) => {
    const name = (row.name || '').toLowerCase()
    const nik = (row.nik || '').toLowerCase()
    const nisn = (row.nisn || '').toLowerCase()
    return name.includes(term) || nik.includes(term) || nisn.includes(term)
  })
})

const feederSelectable = computed(() =>
  filteredFeederAlumni.value.filter((row) => !row.already_enrolled)
)

const feederAllSelected = computed(() =>
  feederSelectable.value.length > 0
  && feederSelectable.value.every((row) => feederSelectedIds.value.includes(row.id))
)

const matchingFeederClassList = computed(() => {
  if (!availableStudentGrades.value.length) return formClassList.value
  if (feederTingkat.value === null || feederTingkat.value === '') return []
  return formClassList.value.filter((c) => Number(c.grade) === Number(feederTingkat.value))
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
    const creds = studentLoginCredentials(res.data?.data || viewingStudent.value, res.data?.login_hint)
    if (creds) {
      creds.title = res.data?.user_created ? 'Akun login siswa dibuat' : 'Akun login siswa'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun login siswa siap digunakan')
    }
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
    const creds = studentLoginCredentials(res.data?.data || viewingStudent.value, res.data?.login_hint)
    if (creds) {
      creds.title = 'Sandi siswa berhasil direset'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Sandi berhasil direset')
    }
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Tidak dapat mereset sandi siswa')
  } finally {
    studentAccountLoading.value = false
  }
}

const saving = ref(false)
const deleteLoading = ref(false)
const activeTab = ref(1)

function emptyStudentForm() {
  return {
    nik: '',
    nis: '',
    nisn: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    ...emptyAddress(),
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
    previous_school_npsn: '',
    previous_school_address: '',
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
}

function emptyToNull(value) {
  if (value === '' || value === undefined) return null
  if (typeof value === 'number' && Number.isNaN(value)) return null
  return value
}

function fillStudentForm(source = {}) {
  const next = emptyStudentForm()
  Object.keys(next).forEach((key) => {
    if (source[key] === undefined || source[key] === null) return
    next[key] = source[key]
  })
  next.academic_year = normalizeAcademicYearLabel(
    source.academic_year_detail?.code || source.academic_year
  )
  if (next.class_id != null && next.class_id !== '') {
    next.class_id = Number(next.class_id)
  }
  if (!next.class && source.class_detail?.name) {
    next.class = source.class_detail.name
  }
  ;['birth_date', 'father_birth_date', 'mother_birth_date', 'guardian_birth_date'].forEach((key) => {
    if (next[key]) next[key] = String(next[key]).split('T')[0]
  })
  form.value = next
}

function buildStudentPayload() {
  const payload = {}
  Object.keys(emptyStudentForm()).forEach((key) => {
    payload[key] = emptyToNull(form.value[key])
  })
  payload.academic_year = normalizeAcademicYearLabel(form.value.academic_year) || null
  if (payload.class_id != null && payload.class_id !== '') {
    payload.class_id = Number(payload.class_id)
  } else {
    payload.class_id = null
  }
  return payload
}

const form = ref(emptyStudentForm())

const matchingFormClassList = computed(() => {
  const currentId = form.value.class_id
  let list = formClassList.value
  if (availableStudentGrades.value.length) {
    if (form.value.tingkat === null || form.value.tingkat === '') {
      list = []
    } else {
      list = formClassList.value.filter(c => Number(c.grade) === Number(form.value.tingkat))
    }
  }
  if (currentId && !list.some(c => Number(c.id) === Number(currentId))) {
    const current = formClassList.value.find(c => Number(c.id) === Number(currentId))
    if (current) return [current, ...list]
  }
  return list
})

const documentForm = ref({
  name: '',
  description: ''
})
const selectedFile = ref(null)
const uploadingDocument = ref(false)
const currentStudentDocuments = ref([])
const fileInput = ref(null)
const pendingPhotoFile = ref(null)
const pendingPhotoPreview = ref('')
const formPhotoUrl = ref('')
const photoUploading = ref(false)
const photoPreviewUrl = computed(() => pendingPhotoPreview.value || formPhotoUrl.value || '')
const photoCropModalOpen = ref(false)
const photoCropSourceFile = ref(null)

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
  formClassListLoading.value = true
  formClassList.value = []
  try {
    if (semesterId) {
      const res = await classApi.getAll({ semester_id: semesterId, per_page: 200 })
      const list = res.data?.data ?? res.data ?? []
      formClassList.value = sortClasses(Array.isArray(list) ? list : [])
    }
    await ensureCurrentClassInList()
  } catch {
    formClassList.value = []
    await ensureCurrentClassInList()
  } finally {
    formClassListLoading.value = false
  }
}

async function ensureCurrentClassInList() {
  const id = form.value.class_id
  if (!id) return
  if (formClassList.value.some(c => Number(c.id) === Number(id))) return
  try {
    const res = await classApi.get(id)
    const row = res.data?.data ?? res.data
    if (row?.id) {
      formClassList.value = sortClasses([...formClassList.value, row])
    }
  } catch {
    /* kelas lama sudah tidak ada — siswa bisa pilih kelas baru */
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
  resetFeederForm()
  addMode.value = 'choose'
  showEditModal.value = false
  editingId = null
  fillStudentForm()
  currentStudentDocuments.value = []
  formPhotoUrl.value = ''
  clearPendingPhoto()
  formClassListLoading.value = true
  formClassList.value = []
  try {
    const r = await institutionApi.getMy()
    myInstitution.value = r.data?.data ?? r.data ?? {}
    const sid = myInstitution.value?.active_semester_id
    if (sid) await loadFormClasses(sid)
    else formClassList.value = []
    feederTingkat.value = availableStudentGrades.value[0] ?? null
  } catch {
    formClassList.value = []
  } finally {
    formClassListLoading.value = false
  }
  showAddModal.value = true
}

function backToAddChoice() {
  resetFeederForm()
  addMode.value = 'choose'
  activeTab.value = 1
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
  const c = formClassList.value.find(x => Number(x.id) === Number(id))
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

  const selectedClass = formClassList.value.find(c => Number(c.id) === Number(form.value.class_id))
  if (selectedClass && Number(selectedClass.grade) !== Number(form.value.tingkat)) {
    form.value.class_id = null
    form.value.class = ''
    form.value.semester_id = null
    form.value.academic_year_id = null
  }
}

const editStudent = async (student) => {
  try {
    const response = await studentApi.get(student.id)
    const fullData = response.data?.data ?? response.data
    editingId = fullData.id
    fillStudentForm(fullData)
    currentStudentDocuments.value = fullData.documents || []
    formPhotoUrl.value = fullData.photo_url || ''
    clearPendingPhoto()
    activeTab.value = 1
    showEditModal.value = true
    const semesterId = form.value.semester_id
      ?? (await institutionApi.getMy().then(r => (r.data?.data ?? r.data)?.active_semester_id))
    await loadFormClasses(semesterId)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat data lengkap siswa')
  }
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
  filters.value.account_status = ''
  filters.value.missing_nis = ''
  const query = { ...route.query }
  if (onlyTrashed) query.trashed = '1'
  else delete query.trashed
  router.replace({ path: '/student', query })
  loadStudents(1)
}

function clearListSubset() {
  filters.value.account_status = ''
  filters.value.missing_nis = ''
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

async function handleForceDeleteStudent(student) {
  const confirmed = await showConfirm({
    title: 'Hapus permanen',
    message: `Hapus permanen ${student.name}? Nilai, absensi, dan berkas terkait ikut terhapus.`,
    warning: 'NISN dan NIK akan dibebaskan. Tindakan ini tidak dapat dibatalkan.',
    confirmText: 'Hapus permanen',
  })
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await studentApi.forceDelete(student.id)
    toast.success('Berhasil', 'Siswa dihapus secara permanen')
    loadStudents()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.formattedMessage || 'Gagal menghapus permanen siswa')
  } finally {
    setDeleteLoading(false)
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
    const payload = buildStudentPayload()
    if (editingId) {
      await studentApi.update(editingId, payload)
      toast.success('Berhasil', 'Data siswa berhasil diperbarui')
      closeModal()
      loadStudents()
    } else {
      const res = await studentApi.create(payload)
      const newId = res.data?.data?.id
      if (newId && pendingPhotoFile.value) {
        try {
          await uploadStudentPhotoFile(newId, pendingPhotoFile.value)
        } catch (photoErr) {
          toast.error('Foto', photoErr.formattedMessage || 'Data tersimpan, tetapi foto gagal diunggah')
        }
      }
      closeModal()
      loadStudents()
      const creds = studentLoginCredentials(res.data?.data || payload, res.data?.login_hint)
      if (creds) {
        creds.title = 'Akun login siswa dibuat'
        accountCredentials.value = creds
      } else {
        toast.success('Berhasil', 'Siswa berhasil ditambahkan')
      }
    }
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
  fillStudentForm()
  // Reset dokumen
  currentStudentDocuments.value = []
  documentForm.value = { name: '', description: '' }
  formPhotoUrl.value = ''
  clearPendingPhoto()
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  error.value = ''
  resetFeederForm()
  addMode.value = 'choose'
}

function resetFeederForm() {
  feederNpsn.value = ''
  feederOrigin.value = null
  feederAlumni.value = []
  feederSelectedIds.value = []
  feederSearch.value = ''
  feederClassId.value = ''
  feederLoading.value = false
  feederSaving.value = false
  feederError.value = ''
}

function toggleSelectAllFeeder() {
  if (feederAllSelected.value) {
    feederSelectedIds.value = []
    return
  }
  feederSelectedIds.value = feederSelectable.value.map((row) => row.id)
}

async function loadFeederAlumni() {
  feederError.value = ''
  feederAlumni.value = []
  feederSelectedIds.value = []
  feederOrigin.value = null
  const npsn = feederNpsn.value.replace(/\D/g, '')
  if (npsn.length !== 8) {
    feederError.value = 'NPSN sekolah asal harus 8 digit.'
    return
  }
  feederLoading.value = true
  try {
    const res = await studentApi.feederAlumni({ origin_npsn: npsn })
    feederOrigin.value = res.data?.origin ?? null
    feederAlumni.value = res.data?.data ?? []
    if (!feederTingkat.value) {
      feederTingkat.value = availableStudentGrades.value[0] ?? null
    }
  } catch (err) {
    feederError.value = err.formattedMessage || err.response?.data?.message || 'Gagal memuat daftar alumni.'
  } finally {
    feederLoading.value = false
  }
}

async function submitFeederPull() {
  feederError.value = ''
  const npsn = feederNpsn.value.replace(/\D/g, '')
  if (npsn.length !== 8) {
    feederError.value = 'NPSN sekolah asal harus 8 digit.'
    return
  }
  if (!feederSelectedIds.value.length) {
    feederError.value = 'Pilih minimal satu alumni.'
    return
  }
  feederSaving.value = true
  try {
    const res = await studentApi.pullFromFeeder({
      origin_npsn: npsn,
      student_ids: feederSelectedIds.value,
      tingkat: feederTingkat.value || undefined,
      class_id: feederClassId.value || undefined,
    })
    closeModal()
    loadStudents()
    const created = res.data?.created_count || 0
    const skipped = res.data?.skipped_count || 0
    const failed = res.data?.error_count || 0
    let detail = res.data?.message || `${created} siswa berhasil ditarik.`
    if (skipped || failed) {
      detail += ` Lewati: ${skipped}, gagal: ${failed}.`
    }
    toast.success('Berhasil', detail)
  } catch (err) {
    feederError.value = err.formattedMessage || err.response?.data?.message || 'Gagal menarik alumni.'
    toast.error('Gagal', feederError.value)
  } finally {
    feederSaving.value = false
  }
}

watch(feederTingkat, () => {
  if (!feederClassId.value) return
  const selected = formClassList.value.find((c) => c.id === feederClassId.value)
  if (selected && Number(selected.grade) !== Number(feederTingkat.value)) {
    feederClassId.value = ''
  }
})

const viewStudent = async (student) => {
  try {
    const response = await studentApi.get(student.id)
    viewingStudent.value = response.data?.data ?? { ...student }
    showViewModal.value = true
    if (canAccessCounseling.value && viewingStudent.value?.id) {
      loadStudentCounseling(viewingStudent.value.id)
    }
    if (canAccessExtracurricular.value && viewingStudent.value?.id) {
      loadStudentExtracurriculars(viewingStudent.value.id)
    }
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat data lengkap siswa')
  }
}

const closeViewModal = () => {
  showViewModal.value = false
  viewingStudent.value = null
  graduatingStudent.value = false
  leavingStudent.value = false
  leaveModal.value = { open: false, status: 'Tidak Aktif' }
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

function openLeaveModal() {
  if (!viewingStudent.value?.id || viewingStudent.value.status !== 'Aktif') return
  leaveModal.value = { open: true, status: 'Tidak Aktif' }
}

function closeLeaveModal() {
  if (leavingStudent.value) return
  leaveModal.value = { open: false, status: 'Tidak Aktif' }
}

async function confirmLeaveStudent() {
  if (!viewingStudent.value?.id || viewingStudent.value.status !== 'Aktif') return
  const status = leaveModal.value.status
  const allowed = leaveStatusOptions.map((o) => o.value)
  if (!allowed.includes(status)) return

  leavingStudent.value = true
  try {
    await studentApi.update(viewingStudent.value.id, { status })
    toast.success('Berhasil', `${viewingStudent.value.name} dipindah ke Siswa Keluar (${status}).`)
    closeLeaveModal()
    closeViewModal()
    await loadStudents()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.formattedMessage || 'Gagal menandai siswa keluar')
  } finally {
    leavingStudent.value = false
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

function studentClassName(student) {
  if (!student) return ''
  const name = student.class_detail?.name || student.class || ''
  return String(name).trim()
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
    if (filters.value.sort_by) params.sort_by = filters.value.sort_by
    if (filters.value.sort_dir) params.sort_dir = filters.value.sort_dir

    const response = await studentApi.export(params)
    const allStudents = response.data.data || []
    writeStudentWorkbook(
      allStudents.map((student) => studentToExcelRow(student)),
      'Data Siswa',
      `Data_Siswa_${new Date().toISOString().split('T')[0]}.xlsx`
    )
    toast.success('Berhasil', `Data berhasil diekspor ke Excel (${allStudents.length} siswa). File ini bisa diedit lalu diimpor lagi.`)
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal mengekspor data ke Excel')
  } finally {
    loading.value = false
  }
}

function writeStudentWorkbook(rows, dataSheetName, fileName) {
  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.json_to_sheet(rows)
  const wsGuide = XLSX.utils.json_to_sheet(studentExcelGuideRows())
  ws['!cols'] = STUDENT_EXCEL_COL_WIDTHS
  wsGuide['!cols'] = [{ wch: 110 }]
  XLSX.utils.book_append_sheet(wb, ws, dataSheetName)
  XLSX.utils.book_append_sheet(wb, wsGuide, 'Petunjuk')
  XLSX.writeFile(wb, fileName)
}

const downloadTemplate = () => {
  try {
    writeStudentWorkbook(
      [studentExcelTemplateRow()],
      'Template Import Siswa',
      'Template_Import_Siswa.xlsx'
    )
    toast.success('Berhasil', 'Template Excel berhasil didownload. Kolom bertanda * wajib diisi. File hasil Export memakai format yang sama.')
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal mendownload template Excel')
  }
}

function closeImportPreview() {
  if (importPreview.value.loading) return
  importPreview.value = { open: false, loading: false, valid: [], invalid: [], serverErrors: [] }
}

// Import from Excel — tampilkan pratinjau dulu
const handleImportExcel = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  try {
    loading.value = true
    const data = await file.arrayBuffer()
    const workbook = XLSX.read(data, { type: 'array', cellDates: true })
    const sheetName = studentExcelDataSheetName(workbook.SheetNames)
    const firstSheet = workbook.Sheets[sheetName]
    if (!firstSheet) {
      toast.error('Gagal', 'Sheet data tidak ditemukan')
      return
    }
    const jsonData = XLSX.utils.sheet_to_json(firstSheet)

    if (jsonData.length === 0) {
      toast.error('Gagal', 'File Excel kosong')
      return
    }

    const { valid, invalid } = mapStudentImportExcelRows(jsonData, XLSX)
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
    const createdCount = response.data?.created_count || 0
    const updatedCount = response.data?.updated_count || 0
    const errorCount = response.data?.error_count || 0
    const errors = response.data?.errors || []
    const summaryParts = []
    if (createdCount) summaryParts.push(`${createdCount} baru`)
    if (updatedCount) summaryParts.push(`${updatedCount} diperbarui`)
    const summary = summaryParts.length ? summaryParts.join(', ') : `${successCount} data`
    if (errorCount > 0) {
      importPreview.value.serverErrors = errors
      const rejectNote = `${errorCount} baris ditolak. Siswa pindah/tidak aktif/alumni tidak diimpor ulang — lihat alasan di bawah.`
      if (successCount > 0) {
        toast.warning('Sebagian ditolak', `Berhasil ${summary}. ${rejectNote}`, 8000)
      } else {
        toast.error('Impor ditolak', rejectNote, 8000)
      }
      await loadStudents()
    } else {
      toast.success('Berhasil', `Berhasil mengimpor ${summary} siswa`)
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

const printingBukuInduk = ref(false)
const printingBiodata = ref(null)

const previewBukuIndukPdf = async () => {
  if (!viewingStudent.value?.id) return
  printingBukuInduk.value = true
  try {
    const res = await studentApi.downloadBukuIndukPdf(viewingStudent.value.id)
    const filename = `Buku_Induk_${viewingStudent.value.name || viewingStudent.value.id}.pdf`
    if (!openPdfBlob(res, filename)) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan pop-up untuk preview PDF.')
      return
    }
    toast.success('Berhasil', 'Buku induk dibuka di tab baru')
  } catch (err) {
    console.error(err)
    toast.error('Gagal', await parseBlobError(err, 'Gagal membuka buku induk'))
  } finally {
    printingBukuInduk.value = false
  }
}

const printBiodataPdf = async (mode = 'lengkap') => {
  if (!viewingStudent.value?.id) return
  printingBiodata.value = mode
  try {
    const res = await studentApi.printBiodataPdf(viewingStudent.value.id, mode)
    const label = mode === 'singkat' ? 'Singkat' : 'Lengkap'
    const filename = `Biodata_${label}_${viewingStudent.value.name || viewingStudent.value.id}.pdf`
    if (!openPdfBlob(res, filename)) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan pop-up untuk preview PDF.')
      return
    }
    toast.success('Berhasil', `Biodata ${label.toLowerCase()} dibuka di tab baru`)
  } catch (err) {
    console.error(err)
    toast.error('Gagal', await parseBlobError(err, 'Gagal mencetak biodata'))
  } finally {
    printingBiodata.value = null
  }
}
function clearPendingPhoto() {
  if (pendingPhotoPreview.value) URL.revokeObjectURL(pendingPhotoPreview.value)
  pendingPhotoPreview.value = ''
  pendingPhotoFile.value = null
}

async function uploadStudentPhotoFile(id, file) {
  const res = await studentApi.uploadPhoto(id, profilePhotoFormData(file))
  formPhotoUrl.value = res.data?.data?.photo_url || ''
  return res
}

async function onStudentPhotoSelect(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  const photoError = validateProfilePhotoSource(file)
  if (photoError) {
    toast.error('Gagal', photoError)
    return
  }
  photoCropSourceFile.value = file
  photoCropModalOpen.value = true
}

function onStudentPhotoCropCancel() {
  photoCropSourceFile.value = null
}

async function onStudentPhotoCropped(file) {
  photoCropSourceFile.value = null
  const photoError = validateProfilePhoto(file)
  if (photoError) {
    toast.error('Gagal', photoError)
    return
  }
  if (editingId) {
    photoUploading.value = true
    try {
      await uploadStudentPhotoFile(editingId, file)
      toast.success('Berhasil', 'Foto siswa diunggah')
      loadStudents()
    } catch (err) {
      toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal mengunggah foto')
    } finally {
      photoUploading.value = false
    }
    return
  }
  clearPendingPhoto()
  pendingPhotoFile.value = file
  pendingPhotoPreview.value = URL.createObjectURL(file)
}

async function removeStudentPhoto() {
  if (pendingPhotoFile.value || pendingPhotoPreview.value) {
    clearPendingPhoto()
    return
  }
  if (!editingId) return
  photoUploading.value = true
  try {
    await studentApi.deletePhoto(editingId)
    formPhotoUrl.value = ''
    toast.success('Berhasil', 'Foto siswa dihapus')
    loadStudents()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal menghapus foto')
  } finally {
    photoUploading.value = false
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
  filters.value.only_trashed = route.query.trashed === '1' || route.query.trashed === 'true'
  loadStudents()
})

watch(() => route.query.trashed, (value) => {
  const wantTrash = value === '1' || value === 'true'
  if (wantTrash === !!filters.value.only_trashed) return
  filters.value.only_trashed = wantTrash
  filters.value.account_status = ''
  filters.value.missing_nis = ''
  loadStudents(1)
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
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.list-tabs .tab-btn:hover {
  background: #ecfdf5;
}

.list-tabs .tab-btn.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: #047857;
}

.list-subset-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin: -8px 0 16px;
  padding: 10px 14px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  font-size: 13px;
  color: #92400e;
}

.btn-ghost-link {
  border: none;
  background: none;
  color: #047857;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  padding: 0;
}

.btn-ghost-link:hover {
  text-decoration: underline;
}

.leave-modal {
  max-width: 480px;
}

.leave-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 12px 0;
}

.leave-option {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
}

.leave-option:has(input:checked) {
  border-color: #059669;
  background: #ecfdf5;
}

.leave-option strong {
  display: block;
  color: #0f172a;
}

.leave-option small {
  display: block;
  color: #64748b;
  margin-top: 2px;
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

.nis-status-banner {
  border-color: #a7f3d0;
  background: #ecfdf5;
}

.nis-status-banner .account-status-text {
  color: #064e3b;
}

.field-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #64748b;
}

.btn-inline-nis {
  margin-left: 8px;
  vertical-align: middle;
}

.nis-preview-line {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f8fafc;
  font-size: 13px;
  color: #334155;
}

.nis-settings-modal .form-group {
  margin-bottom: 12px;
}

.nis-assign-modal {
  max-width: 720px;
}

.nis-assign-modal .col-check {
  width: 36px;
  text-align: center;
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

.import-reject-box {
  background: #fef2f2;
  border-color: #fecaca;
  max-height: 240px;
  overflow-y: auto;
}

.import-reject-box .hint {
  margin: 6px 0 0;
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

.form-modal-content.form-modal-content--choice {
  max-width: 480px;
}

.form-modal-content--choice .form-modal-body {
  flex: 0 0 auto;
  overflow: visible;
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

.add-choice-panel {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 16px 20px 20px;
}

.add-choice-row {
  display: flex;
  align-items: center;
  gap: 14px;
  width: 100%;
  text-align: left;
  padding: 14px 14px 14px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  color: #0f172a;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}

.add-choice-row:hover,
.add-choice-row:focus-visible {
  border-color: #059669;
  background: #f0fdf4;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
  outline: none;
}

.add-choice-icon-wrap {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ecfdf5;
  color: #047857;
}

.add-choice-icon-wrap--pull {
  background: #eff6ff;
  color: #1d4ed8;
}

.add-choice-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.add-choice-text strong {
  font-size: 15px;
  font-weight: 650;
  line-height: 1.3;
}

.add-choice-text span {
  font-size: 13px;
  color: #64748b;
  line-height: 1.4;
  font-weight: 400;
}

.add-choice-arrow {
  flex-shrink: 0;
  color: #94a3b8;
  display: flex;
}

.add-choice-row:hover .add-choice-arrow {
  color: #059669;
}

.add-choice-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 4px;
}

.add-mode-back-bar {
  flex-shrink: 0;
  padding: 12px 28px 0;
}

.add-mode-back-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: none;
  background: none;
  color: #047857;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.add-mode-back-btn:hover {
  color: #065f46;
}

.feeder-panel .form-hint {
  margin: 0 0 16px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
}

.feeder-npsn-row,
.feeder-class-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  margin-bottom: 16px;
}

.feeder-npsn-row .form-group,
.feeder-class-row .form-group {
  flex: 1;
  margin-bottom: 0;
}

.feeder-origin {
  margin: 0 0 16px;
  font-weight: 600;
  color: #0f172a;
}

.feeder-select-all {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
}

.feeder-list-wrap {
  margin-bottom: 16px;
}

.feeder-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.feeder-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
}

.feeder-row:last-child {
  border-bottom: none;
}

.feeder-row.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.feeder-row-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.feeder-nisn {
  font-size: 12px;
  color: #64748b;
}

.feeder-nisn-secondary {
  color: #94a3b8;
}

.feeder-row-meta {
  display: flex;
  gap: 8px;
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
}

.feeder-already {
  color: #b45309;
  font-weight: 600;
}

.feeder-footer {
  margin: 8px -28px -28px;
  border-radius: 0 0 20px 20px;
}

.form-tab-btn svg {
  flex-shrink: 0;
  opacity: 0.85;
}

.form-tab-content {
  min-height: 280px;
  animation: formTabFade 0.2s ease-out;
}

.form-modal-body .form-section-label {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  margin: 8px 0 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.form-modal-body :deep(.address-cascade) {
  display: flex;
  width: 100%;
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

.form-modal-content.form-modal-content--choice {
  max-width: 480px;
  overflow: visible;
}

.form-modal-content--choice .form-modal-header {
  padding: 18px 20px;
}

.form-modal-content--choice .form-modal-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
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

.biodata-photo-wrap {
  float: right;
  margin: 0 0 12px 16px;
}

.biodata-photo {
  width: 84px;
  height: 112px;
  object-fit: cover;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
}

.biodata-photo-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 700;
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

.form-photo-row {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
  padding: 12px;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
}

.form-photo-preview {
  width: 84px;
  height: 112px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.form-photo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.form-photo-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.form-photo-meta label {
  font-weight: 600;
  font-size: 14px;
  color: #1e293b;
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
    align-items: stretch;
    gap: 16px;
  }

  /* Tombol aksi: wrap di layar sempit agar tidak terpotong */
  .student-page .action-buttons-group {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    width: 100%;
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

  /* Tombol aksi: wrap di layar sempit */
  .student-page .action-buttons-group {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    width: 100%;
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
    font-size: 12px;
  }

  .add-mode-back-bar {
    padding: 10px 16px 0;
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
