<template>
  <Layout>
    <div class="teacher-page">
      <div class="list-tabs">
        <button
          type="button"
          :class="['tab-btn', { active: !filters.only_trashed }]"
          @click="switchListTab(false)"
        >
          Daftar Guru
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
        <TeacherFilters
          v-if="!filters.only_trashed"
          :model-value="filters"
          @update:model-value="(v) => Object.assign(filters.value, v)"
          @filter="loadTeachers"
        />
        <div v-else class="filters filters-inline">
          <input
            v-model="filters.search"
            @input="loadTeachers"
            placeholder="Cari nama, NIP, NUPTK..."
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
          <button
            v-if="isInstitutionAdmin"
            @click="openAssignmentRequestModal"
            class="btn-secondary btn-compact"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Non Induk</span>
          </button>
          <button
            v-if="isInstitutionAdmin"
            @click="openAssignmentRequestsModal"
            class="btn-secondary btn-compact"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Permintaan</span>
          </button>
          <label for="import-excel-employee" class="btn-secondary btn-compact cursor-pointer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Import</span>
          </label>
          <input type="file" id="import-excel-employee" accept=".xlsx,.xls" class="input-hidden" @change="handleImportExcel">
          <button @click="showAddModal = true" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Guru</span>
          </button>
          </template>
        </div>
      </div>

      <p v-if="listError" class="error-message">{{ listError }}</p>

      <TeacherTableSkeleton v-if="loading" />

      <TeacherTable
        v-else
        :teachers="teachers"
        :trash-mode="filters.only_trashed"
        :get-teacher-subject="getTeacherSubject"
        :get-status-class="getStatusClass"
        @view="viewTeacher"
        @edit="editTeacher"
        @delete="deleteTeacher"
        @add="showAddModal = true"
        @restore="handleRestoreTeacher"
      >
        <template #empty>
          <template v-if="filters.only_trashed">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Tidak ada data di kotak sampah</h3>
            <p>Data guru yang dihapus akan muncul di sini dan dapat dipulihkan</p>
          </template>
          <template v-else>
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Belum ada data guru</h3>
            <p>Mulai dengan menambahkan guru baru</p>
            <button type="button" @click="showAddModal = true" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Guru</span>
            </button>
          </template>
        </template>
      </TeacherTable>

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
                <h3 class="form-modal-title">{{ showEditModal ? 'Edit' : 'Tambah' }} Guru</h3>
                <p class="form-modal-subtitle">{{ showEditModal ? 'Perbarui data guru' : 'Isi data guru baru' }}</p>
              </div>
            </div>
            <button type="button" @click="closeModal" class="btn-close-modal" aria-label="Tutup">
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
                <span>Kepegawaian</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 3" 
                :class="['form-tab-btn', { active: activeTab === 3 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Tambahan</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 4" 
                :class="['form-tab-btn', { active: activeTab === 4 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 19.5C4 20.163 4.26339 20.7989 4.73223 21.2678C5.20107 21.7366 5.83696 22 6.5 22H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M19.5 2H4.5C3.67157 2 3 2.67157 3 3.5V20.5C3 21.3284 3.67157 22 4.5 22H19.5C20.3284 22 21 21.3284 21 20.5V3.5C21 2.67157 20.3284 2 19.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M3 7H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7 2V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M17 2V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Pendidikan</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 5" 
                :class="['form-tab-btn', { active: activeTab === 5 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Berkas</span>
              </button>
            </div>

            <!-- Tab 1: Identitas -->
            <div v-show="activeTab === 1" class="form-tab-content">
              <fieldset :disabled="isNonIndukEdit" class="fieldset-reset">
                <div class="form-row">
                  <div class="form-group">
                    <label>Tipe Pegawai *</label>
                    <select v-model="form.type" required>
                      <option value="Guru">Guru</option>
                      <option value="Staff">Staff</option>
                      <option value="Tenaga Administrasi">Tenaga Administrasi</option>
                      <option value="Tenaga Kebersihan">Tenaga Kebersihan</option>
                      <option value="Tenaga Keamanan">Tenaga Keamanan</option>
                      <option value="Lainnya">Lainnya</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>NIK *</label>
                    <input v-model="form.nik" required />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>NIP</label>
                    <input v-model="form.nip" />
                  </div>
                  <div class="form-group">
                    <label>NUPTK</label>
                    <input v-model="form.nuptk" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input v-model="form.name" required />
                  </div>
                  <div class="form-group">
                    <label>Jenis Kelamin *</label>
                    <select v-model="form.gender" required>
                      <option value="">Pilih</option>
                      <option value="L">Laki-laki</option>
                      <option value="P">Perempuan</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" v-model="form.birth_date" />
                  </div>
                  <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input v-model="form.birth_place" />
                  </div>
                </div>

                <div class="form-group">
                  <label>Alamat</label>
                  <textarea v-model="form.address" rows="3"></textarea>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Telepon</label>
                    <input v-model="form.phone" />
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" v-model="form.email" />
                  </div>
                </div>

                <div class="form-row">
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
              </fieldset>

              <div v-if="isNonIndukEdit" class="info-box">
                <p>Biodata hanya dapat diubah oleh sekolah induk.</p>
              </div>
            </div>

            <!-- Tab 2: Kepegawaian -->
            <div v-show="activeTab === 2" class="form-tab-content">
            <fieldset :disabled="isNonIndukEdit" class="fieldset-reset">
              <div class="form-row">
                <div class="form-group">
                  <label>Status Kepegawaian</label>
                  <select v-model="form.employment_status">
                    <option value="">Pilih</option>
                    <option value="PNS">PNS</option>
                    <option value="CPNS">CPNS</option>
                    <option value="Guru Tetap Yayasan">Guru Tetap Yayasan</option>
                    <option value="Guru Honor Sekolah">Guru Honor Sekolah</option>
                    <option value="Guru Kontrak">Guru Kontrak</option>
                    <option value="Pegawai Tetap Yayasan">Pegawai Tetap Yayasan</option>
                    <option value="Pegawai Honor">Pegawai Honor</option>
                    <option value="Pegawai Kontrak">Pegawai Kontrak</option>
                  </select>
                </div>
                  <div class="form-group">
                    <label>Pendidikan Terakhir</label>
                    <select v-model="form.education_level">
                      <option value="">Pilih</option>
                      <option value="SMA">SMA</option>
                      <option value="D3">D3</option>
                      <option value="S1">S1</option>
                      <option value="S2">S2</option>
                      <option value="S3">S3</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Jurusan</label>
                    <input v-model="form.major" />
                  </div>
                  <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <input v-model="form.subject" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>Tanggal Bergabung</label>
                    <input type="date" v-model="form.join_date" />
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select v-model="form.status">
                      <option value="Aktif">Aktif</option>
                      <option value="Pensiun">Pensiun</option>
                      <option value="Pindah">Pindah</option>
                      <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                  </div>
                </div>

                <h4 class="form-subsection-title">Sertifikasi Guru</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Status Sertifikasi</label>
                    <select v-model="form.certification_status">
                      <option value="">Pilih</option>
                      <option value="Sudah">Sudah</option>
                      <option value="Belum">Belum</option>
                    </select>
                  </div>
                  <div class="form-group" v-if="form.certification_status === 'Sudah'">
                    <label>Tanggal Sertifikasi</label>
                    <input type="date" v-model="form.certification_date" />
                  </div>
                  <div class="form-group">
                    <label>Nomor Registrasi Guru (NRG)</label>
                    <input v-model="form.teacher_registration_number" placeholder="NRG" />
                  </div>
                </div>
                <div class="form-row" v-if="form.certification_status === 'Sudah'">
                  <div class="form-group">
                    <label>Nomor Sertifikat Pendidik</label>
                    <input v-model="form.certification_number" placeholder="Nomor sertifikat" />
                  </div>
                  <div class="form-group">
                    <label>Lembaga Penerbit</label>
                    <input v-model="form.certification_issuing_authority" placeholder="Contoh: Kemendikbud" />
                  </div>
                </div>
              </fieldset>

              <div v-if="isNonIndukEdit" class="assignment-section">
                <h4>Penugasan Non-Induk</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <input v-model="form.assignment_subject" />
                  </div>
                  <div class="form-group">
                    <label>Penugasan</label>
                    <input v-model="form.assignment_title" />
                  </div>
                </div>
                <div class="form-group">
                  <label>Catatan Penugasan</label>
                  <textarea v-model="form.assignment_notes" rows="3"></textarea>
                </div>
              </div>
            </div>

            <!-- Tab 3: Tambahan -->
            <div v-show="activeTab === 3" class="form-tab-content">
              <fieldset :disabled="isNonIndukEdit" class="fieldset-reset">
                <div class="form-group">
                  <label>Catatan</label>
                  <textarea v-model="form.notes" rows="5"></textarea>
                </div>

                <div class="module-access">
                  <h4>Akun Login & Akses Modul</h4>
                  <p class="form-hint">Isi email untuk membuat akun login pegawai. Semua pegawai (Guru, Staff, dll.) bisa punya akun dengan role dan modul akses.</p>
                  <div class="form-row">
                    <div class="form-group">
                      <label>Role akun login</label>
                      <select v-model="form.user_role" class="form-input">
                        <option value="">Tidak buat akun</option>
                        <option value="teacher">Guru (teacher)</option>
                        <option value="staff">Staff (staff)</option>
                      </select>
                      <span class="form-hint">Pilih role untuk akun login. Email wajib diisi jika memilih role.</span>
                    </div>
                  </div>
                  <div class="form-section additional-duties-section">
                    <h5>Tugas Tambahan</h5>
                    <div v-if="loadingAdditionalDuties" class="info-box">
                      <p>Memuat daftar tugas tambahan...</p>
                    </div>
                    <div v-else class="module-grid">
                      <label v-for="duty in availableAdditionalDuties" :key="duty.id" class="module-option">
                        <input type="checkbox" :value="duty.id" v-model="form.additional_duty_ids" />
                        <span>{{ duty.label }}</span>
                      </label>
                    </div>
                    <p class="form-hint">Tugas tambahan memberi akses otomatis ke modul terkait (digabung dengan akses modul di bawah).</p>
                  </div>
                  <div v-if="form.user_role" class="module-access-grid">
                    <h5>Akses Modul</h5>
                    <div v-if="loadingPermissions" class="info-box">
                      <p>Memuat daftar modul...</p>
                    </div>
                    <div v-else class="module-grid">
                      <label v-for="module in availableModules" :key="module.key" class="module-option">
                        <input type="checkbox" :value="module.key" v-model="form.permission_keys" />
                        <span>{{ module.label }}</span>
                      </label>
                    </div>
                    <p class="form-hint">Hanya modul yang dicentang dapat diakses oleh akun ini.</p>
                  </div>
                </div>
              </fieldset>
              <div v-if="isNonIndukEdit" class="info-box">
                <p>Catatan hanya dapat diubah oleh sekolah induk.</p>
              </div>
            </div>

            <!-- Tab 4: Pendidikan -->
            <div v-show="activeTab === 4" class="form-tab-content">
              <div v-if="!isNonIndukEdit" class="education-section">
                <div class="section-header">
                  <h4>Riwayat Pendidikan</h4>
                  <button type="button" @click="addEducation" class="btn-add-education">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Tambah Pendidikan</span>
                  </button>
                </div>

                <div v-for="(education, index) in form.educations" :key="index" class="education-item">
                  <div class="education-item-header">
                    <h5>Pendidikan {{ index + 1 }}</h5>
                    <button type="button" @click="removeEducation(index)" class="btn-remove-education" v-if="form.educations.length > 1">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label>Tingkat Pendidikan</label>
                      <select v-model="education.level">
                        <option value="">Pilih</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>
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
                      <label>Nama Sekolah/Universitas</label>
                      <input v-model="education.school_name" />
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label>Jurusan</label>
                      <input v-model="education.major" />
                    </div>
                    <div class="form-group">
                      <label>Tahun Lulus</label>
                      <input type="number" v-model.number="education.graduation_year" min="1900" :max="new Date().getFullYear() + 10" />
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label>Nomor Ijazah</label>
                      <input v-model="education.certificate_number" />
                    </div>
                    <div class="form-group">
                      <label>Kota</label>
                      <input v-model="education.city" />
                    </div>
                  </div>

                  <div class="form-group">
                    <label>Catatan</label>
                    <textarea v-model="education.notes" rows="2"></textarea>
                  </div>
                </div>
              </div>
              <div v-else class="info-box">
                <p>Riwayat pendidikan hanya dapat diubah oleh sekolah induk.</p>
              </div>
            </div>

            <!-- Tab 5: Berkas -->
            <div v-show="activeTab === 5" class="form-tab-content">
              <div v-if="!isNonIndukEdit" class="documents-section">
                <div class="section-header">
                  <h4>Berkas Dokumen</h4>
                  <div class="documents-info">
                    <span class="doc-count">{{ (form.documents || []).length }}/20 file</span>
                  </div>
                </div>

                <div v-if="editingId" class="upload-section">
                  <div class="upload-area">
                    <input 
                      type="file" 
                      id="document-upload" 
                      ref="documentUpload"
                      accept=".pdf" 
                      @change="handleDocumentUpload"
                      style="display: none;"
                      :disabled="(form.documents || []).length >= 20"
                    />
                    <label 
                      for="document-upload" 
                      class="upload-label"
                      :class="{ disabled: (form.documents || []).length >= 20 }"
                    >
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <span>Upload Dokumen PDF (Maks. 2MB)</span>
                    </label>
                    <p class="upload-hint">Format: PDF | Maksimal 20 file | Maksimal 2MB per file</p>
                  </div>
                </div>

                <div v-else class="info-box">
                  <p>Simpan data pegawai terlebih dahulu untuk mengupload berkas</p>
                </div>

                <div v-if="form.documents && form.documents.length > 0" class="documents-list">
                  <div v-for="(doc, index) in form.documents" :key="doc.id || index" class="document-item">
                    <div class="document-info">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <div class="document-details">
                        <span class="document-name">{{ doc.name }}</span>
                        <span class="document-meta">{{ doc.file_name }} • {{ doc.file_size_human || formatFileSize(doc.file_size) }}</span>
                      </div>
                    </div>
                    <div class="document-actions">
                      <a :href="doc.file_url" target="_blank" class="btn-download" title="Download">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </a>
                      <button @click="deleteDocument(doc.id, index)" class="btn-delete-doc" title="Hapus">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <div v-else-if="editingId" class="empty-documents">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <p>Belum ada berkas yang diupload</p>
                </div>
              </div>
              <div v-else class="info-box">
                <p>Berkas hanya dapat diubah oleh sekolah induk.</p>
              </div>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="form-modal-footer">
              <button type="button" @click="closeModal" class="btn-ghost">Batal</button>
              <div class="form-modal-footer-actions">
                <button v-if="activeTab > 1" type="button" @click="activeTab--" class="btn-outline">Sebelumnya</button>
                <button v-if="activeTab < 5" type="button" @click="activeTab++" class="btn-outline">Selanjutnya</button>
                <button type="submit" :disabled="saving" class="btn-submit">
                  <span v-if="saving" class="btn-spinner"></span>
                  <span>{{ saving ? 'Menyimpan...' : 'Simpan' }}</span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- View Teacher Modal -->
      <div v-if="showViewModal" class="modal-overlay view-modal-overlay" @click="closeViewModal">
        <div class="modal-content view-modal" @click.stop>
          <div class="view-modal-header">
            <div class="view-header-actions">
              <button type="button" class="btn-print" @click="printPDF" title="Cetak PDF">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cetak PDF</span>
              </button>
              <button type="button" class="view-modal-close" @click="closeViewModal" aria-label="Tutup">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>
            <div class="view-profile-strip" v-if="viewingTeacher">
              <div class="view-profile-avatar">
                {{ (viewingTeacher.name || 'P').charAt(0).toUpperCase() }}
              </div>
              <div class="view-profile-info">
                <h2 class="view-profile-name">{{ viewingTeacher.name || '-' }}</h2>
                <div class="view-profile-meta">
                  <span class="view-profile-type">{{ viewingTeacher.type || 'Pegawai' }}</span>
                  <span class="view-profile-status" :class="getStatusClass(viewingTeacher.status)">{{ viewingTeacher.status || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="view-body" v-if="viewingTeacher">
            <!-- Identitas -->
            <div class="biodata-section view-card">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Identitas Guru
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">NIP</span>
                  <span class="value">{{ viewingTeacher.nip || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NIK</span>
                  <span class="value">{{ viewingTeacher.nik || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tipe Pegawai</span>
                  <span class="value">{{ viewingTeacher.type || 'Guru' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">NUPTK</span>
                  <span class="value">{{ viewingTeacher.nuptk || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nama Lengkap</span>
                  <span class="value">{{ viewingTeacher.name || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Jenis Kelamin</span>
                  <span class="value">{{ viewingTeacher.gender === 'L' ? 'Laki-laki' : viewingTeacher.gender === 'P' ? 'Perempuan' : '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tempat Lahir</span>
                  <span class="value">{{ viewingTeacher.birth_place || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Lahir</span>
                  <span class="value">{{ formatDate(viewingTeacher.birth_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Alamat</span>
                  <span class="value">{{ viewingTeacher.address || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Telepon</span>
                  <span class="value">{{ viewingTeacher.phone || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Email</span>
                  <span class="value">{{ viewingTeacher.email || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Agama</span>
                  <span class="value">{{ viewingTeacher.religion || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Data Kepegawaian -->
            <div class="biodata-section view-card">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Data Kepegawaian
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">Status Kepegawaian</span>
                  <span class="value">{{ viewingTeacher.employment_status || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Pendidikan Terakhir</span>
                  <span class="value">{{ viewingTeacher.education_level || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Jurusan</span>
                  <span class="value">{{ viewingTeacher.major || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Mata Pelajaran</span>
                  <span class="value">{{ viewingTeacher.subject || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Tanggal Bergabung</span>
                  <span class="value">{{ formatDate(viewingTeacher.join_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value" :class="getStatusClass(viewingTeacher.status)">{{ viewingTeacher.status || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Sertifikasi Guru -->
            <div class="biodata-section view-card" v-if="isViewingGuru">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5292C20.3248 17.7721 20.3766 18.0325 20.3766 18.296C20.3766 18.5595 20.3248 18.8199 20.2241 19.0628C20.1235 19.3057 19.976 19.5263 19.79 19.712C19.6043 19.898 19.3837 20.0455 19.1408 20.1461C18.8979 20.2468 18.6375 20.2986 18.374 20.2986C18.1105 20.2986 17.8501 20.2468 17.6072 20.1461C17.3643 20.0455 17.1437 19.898 16.958 19.712L16.898 19.652C16.6623 19.4215 16.363 19.2668 16.0386 19.208C15.7142 19.1492 15.3796 19.1889 15.078 19.322C14.7842 19.4508 14.532 19.6574 14.3543 19.9175C14.1766 20.1776 14.0813 20.4801 14.08 20.79V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.6052 9.96512 20.3125 9.77251 20.0752C9.5799 19.8379 9.31274 19.6687 9.01 19.59C8.70838 19.4569 8.37381 19.4172 8.04941 19.476C7.72502 19.5348 7.42568 19.6895 7.19 19.92L7.13 19.98C6.94425 20.166 6.72368 20.3135 6.48077 20.4141C6.23786 20.5148 5.97747 20.5666 5.714 20.5666C5.45053 20.5666 5.19014 20.5148 4.94723 20.4141C4.70432 20.3135 4.48375 20.166 4.298 19.98C4.11205 19.7943 3.96453 19.5737 3.86388 19.3308C3.76322 19.0879 3.71144 18.8275 3.71144 18.564C3.71144 18.3005 3.76322 18.0401 3.86388 17.7972C3.96453 17.5543 4.11205 17.3337 4.298 17.148L4.358 17.088C4.59368 16.8575 4.74841 16.5582 4.8072 16.2338C4.86598 15.9094 4.82628 15.5748 4.692 15.273C4.56321 14.9792 4.35659 14.727 4.09651 14.5493C3.83642 14.3716 3.53394 14.2763 3.224 14.275H3C2.46957 14.275 1.96086 14.0643 1.58579 13.6892C1.21071 13.3141 1 12.8054 1 12.275C1 11.7446 1.21071 11.2359 1.58579 10.8608C1.96086 10.4857 2.46957 10.275 3 10.275H3.09C3.39482 10.2673 3.68752 10.1601 3.92482 9.96751C4.16212 9.7749 4.3313 9.50774 4.41 9.205C4.54312 8.90338 4.5828 8.56881 4.52402 8.24441C4.46524 7.92002 4.31049 7.62068 4.08 7.385L4.02 7.325C3.83425 7.13925 3.68673 6.91868 3.58608 6.67577C3.48542 6.43286 3.43364 6.17247 3.43364 5.909C3.43364 5.64553 3.48542 5.38514 3.58608 5.14223C3.68673 4.89932 3.83425 4.67875 4.02 4.493C4.20575 4.30705 4.42632 4.15953 4.66923 4.05888C4.91214 3.95822 5.17253 3.90644 5.436 3.90644C5.69947 3.90644 5.95986 3.95822 6.20277 4.05888C6.44568 4.15953 6.66625 4.30705 6.852 4.493L6.912 4.553C7.14347 4.78868 7.44281 4.94341 7.7672 5.0022C8.09159 5.06098 8.42624 5.02128 8.728 4.887V4.89C9.02179 4.76121 9.274 4.55459 9.45169 4.29451C9.62938 4.03442 9.72472 3.73194 9.726 3.422V3.275C9.726 2.74457 9.93672 2.23586 10.3118 1.86079C10.6869 1.48572 11.1956 1.275 11.726 1.275C12.2564 1.275 12.7651 1.48572 13.1402 1.86079C13.5153 2.23586 13.726 2.74457 13.726 3.275V3.365C13.7283 3.67494 13.8236 3.97742 14.0013 4.23751C14.179 4.49759 14.4312 4.70421 14.725 4.833C15.0266 4.96612 15.3612 5.0058 15.6856 4.94702C16.01 4.88824 16.3093 4.73349 16.545 4.503L16.605 4.443C16.7907 4.25725 17.0113 4.10973 17.2542 4.00908C17.4971 3.90842 17.7575 3.85664 18.021 3.85664C18.2845 3.85664 18.5449 3.90842 18.7878 4.00908C19.0307 4.10973 19.2512 4.25725 19.437 4.443L19.497 4.503C19.7327 4.73349 20.032 4.88824 20.3564 4.94702C20.6808 5.0058 21.0154 4.96612 21.317 4.833C21.6108 4.70421 21.863 4.49759 22.0407 4.23751C22.2184 3.97742 22.3137 3.67494 22.316 3.365V3.275C22.316 2.74457 22.5267 2.23586 22.9018 1.86079C23.2769 1.48572 23.7856 1.275 24.316 1.275C24.8464 1.275 25.3551 1.48572 25.7302 1.86079C26.1053 2.23586 26.316 2.74457 26.316 3.275V3.422C26.3173 3.73194 26.4126 4.03442 26.5903 4.29451C26.768 4.55459 27.0202 4.76121 27.314 4.89C27.6156 5.02312 27.9502 5.0628 28.2746 5.00402C28.599 4.94524 28.8983 4.79049 29.134 4.56L29.194 4.5C29.3797 4.31425 29.6003 4.16673 29.8432 4.06608C30.0861 3.96542 30.3465 3.91364 30.61 3.91364C30.8735 3.91364 31.1339 3.96542 31.3768 4.06608C31.6197 4.16673 31.8402 4.31425 32.026 4.5L32.086 4.56C32.3165 4.79568 32.4712 5.09502 32.53 5.41941C32.5888 5.74381 32.5491 6.07844 32.416 6.38V6.39C32.2872 6.68379 32.0806 6.936 31.8205 7.11369C31.5604 7.29138 31.2579 7.38672 30.948 7.388H30.8C30.4906 7.38928 30.1881 7.48463 29.928 7.66232C29.668 7.84001 29.4613 8.09221 29.333 8.386V8.385C29.1999 8.68662 29.1602 9.02119 29.219 9.34558C29.2777 9.66998 29.4325 9.96932 29.663 10.205L29.723 10.265C29.9087 10.4507 30.0562 10.6713 30.1569 10.9142C30.2575 11.1571 30.3093 11.4175 30.3093 11.681C30.3093 11.9445 30.2575 12.2049 30.1569 12.4478C30.0562 12.6907 29.9087 12.9112 29.723 13.097L29.663 13.157C29.4325 13.3927 29.2777 13.692 29.219 14.0164C29.1602 14.3408 29.1999 14.6754 29.333 14.977C29.4613 15.271 29.668 15.5232 29.928 15.7009C30.1881 15.8786 30.4906 15.9739 30.8 15.975H30.9C31.2099 15.9763 31.5124 16.0716 31.7725 16.2493C32.0326 16.427 32.2392 16.6792 32.368 16.973V17C32.368 17.5304 32.1573 18.0391 31.7822 18.4142C31.4071 18.7893 30.8984 19 30.368 19H30.316C30.0061 19.0013 29.7036 19.0966 29.4435 19.2743C29.1834 19.452 28.9768 19.7042 28.848 19.998C28.7149 20.2996 28.6752 20.6342 28.7339 20.9586C28.7927 21.283 28.9475 21.5823 29.178 21.818L29.238 21.878C29.4237 22.0637 29.5712 22.2843 29.6719 22.5272C29.7725 22.7701 29.8243 23.0305 29.8243 23.294C29.8243 23.5575 29.7725 23.8179 29.6719 24.0608C29.5712 24.3037 29.4237 24.5242 29.238 24.71L29.178 24.77C28.9475 25.0057 28.7927 25.305 28.7339 25.6294C28.6752 25.9538 28.7149 26.2884 28.848 26.59V26.59C28.9768 26.884 29.1834 27.1362 29.4435 27.3139C29.7036 27.4916 30.0061 27.5869 30.316 27.588H30.368C30.8984 27.588 31.4071 27.7987 31.7822 28.1738C32.1573 28.5489 32.368 29.0576 32.368 29.588C32.368 30.1184 32.1573 30.6271 31.7822 31.0022C31.4071 31.3773 30.8984 31.588 30.368 31.588H30.09C29.7842 31.5957 29.4915 31.7029 29.2542 31.8955C29.0169 32.0881 28.8477 32.3553 28.77 32.658C28.6369 32.9596 28.5972 33.2942 28.656 33.6186C28.7148 33.943 28.8695 34.2423 29.1 34.478L29.16 34.538C29.3457 34.7237 29.4932 34.9443 29.5939 35.1872C29.6945 35.4301 29.7463 35.6905 29.7463 35.954C29.7463 36.2175 29.6945 36.4779 29.5939 36.7208C29.4932 36.9637 29.3457 37.1842 29.16 37.37L29.1 37.43C28.8643 37.6605 28.7096 37.9598 28.6508 38.2842C28.592 38.6086 28.6317 38.9432 28.765 39.245C28.8937 39.539 29.1003 39.7912 29.3604 39.9689C29.6205 40.1466 29.923 40.2419 30.232 40.243H30.368C30.8984 40.243 31.4071 40.4537 31.7822 40.8288C32.1573 41.2039 32.368 41.7126 32.368 42.243C32.368 42.7734 32.1573 43.2821 31.7822 43.6572C31.4071 44.0323 30.8984 44.243 30.368 44.243H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Sertifikasi Guru
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="label">Status Sertifikasi</span>
                  <span class="value">{{ viewingTeacher.certification_status || '-' }}</span>
                </div>
                <div class="biodata-item" v-if="viewingTeacher.certification_status === 'Sudah'">
                  <span class="label">Tanggal Sertifikasi</span>
                  <span class="value">{{ formatDate(viewingTeacher.certification_date) }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Nomor Registrasi Guru (NRG)</span>
                  <span class="value">{{ viewingTeacher.teacher_registration_number || '-' }}</span>
                </div>
                <div class="biodata-item" v-if="viewingTeacher.certification_status === 'Sudah'">
                  <span class="label">Nomor Sertifikat Pendidik</span>
                  <span class="value">{{ viewingTeacher.certification_number || '-' }}</span>
                </div>
                <div class="biodata-item" v-if="viewingTeacher.certification_status === 'Sudah'">
                  <span class="label">Lembaga Penerbit</span>
                  <span class="value">{{ viewingTeacher.certification_issuing_authority || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Akun Login -->
            <div class="biodata-section view-card biodata-section-akun">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Akun Login
              </h4>
              <div class="biodata-grid" v-if="viewingTeacher.has_user_account && viewingTeacher.user_account">
                <div class="biodata-item">
                  <span class="label">Email (untuk login)</span>
                  <span class="value">{{ viewingTeacher.user_account.email || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Role</span>
                  <span class="value">{{ formatUserRole(viewingTeacher.user_account.role) }}</span>
                </div>
                <div class="biodata-item full-width" v-if="viewingTeacher.additional_duties && viewingTeacher.additional_duties.length">
                  <span class="label">Tugas tambahan</span>
                  <span class="value">
                    <span v-for="d in viewingTeacher.additional_duties" :key="d.id" class="permission-tag">{{ d.label }}</span>
                  </span>
                </div>
                <div class="biodata-item full-width" v-if="viewingTeacher.user_account.permissions && viewingTeacher.user_account.permissions.length">
                  <span class="label">Modul akses</span>
                  <span class="value">
                    <span v-for="key in viewingTeacher.user_account.permissions" :key="key" class="permission-tag">{{ key }}</span>
                  </span>
                </div>
                <div class="biodata-item full-width" v-else>
                  <span class="label">Modul akses</span>
                  <span class="value">-</span>
                </div>
                <div class="biodata-item full-width">
                  <span class="label">Ubah akses</span>
                  <span class="value hint">Gunakan tombol <strong>Edit</strong> di tabel untuk mengubah modul akses (permission) akun ini.</span>
                </div>
                <div class="biodata-item full-width" v-if="canResetEmployeePassword">
                  <span class="label">Reset sandi</span>
                  <span class="value">
                    <button type="button" class="btn-reset-password" @click="openResetPasswordModal">
                      Reset sandi login
                    </button>
                    <span class="hint">Beri tahu pegawai sandi baru secara aman setelah direset.</span>
                  </span>
                </div>
              </div>
              <div class="biodata-grid" v-else>
                <div class="biodata-item full-width">
                  <span class="value hint">Akun login belum dibuat. Isi email dan pilih role di form <strong>Edit</strong> pegawai lalu simpan untuk membuat akun.</span>
                </div>
              </div>
            </div>

            <!-- Riwayat Pendidikan -->
            <div class="biodata-section view-card" v-if="viewingTeacher.educations && viewingTeacher.educations.length > 0">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 19.5C4 18.837 4.26339 18.2011 4.73223 17.7322C5.20107 17.2634 5.83696 17 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 19.5C4 20.163 4.26339 20.7989 4.73223 21.2678C5.20107 21.7366 5.83696 22 6.5 22H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M19.5 2H4.5C3.67157 2 3 2.67157 3 3.5V20.5C3 21.3284 3.67157 22 4.5 22H19.5C20.3284 22 21 21.3284 21 20.5V3.5C21 2.67157 20.3284 2 19.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M3 7H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Riwayat Pendidikan
              </h4>
              <div class="educations-list">
                <div v-for="(edu, index) in viewingTeacher.educations" :key="index" class="education-view-item">
                  <div class="education-view-header">
                    <h5>{{ edu.level || 'Tidak Diketahui' }}</h5>
                  </div>
                  <div class="biodata-grid">
                    <div class="biodata-item">
                      <span class="label">Nama Sekolah/Universitas</span>
                      <span class="value">{{ edu.school_name || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Jurusan</span>
                      <span class="value">{{ edu.major || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Tahun Lulus</span>
                      <span class="value">{{ edu.graduation_year || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Nomor Ijazah</span>
                      <span class="value">{{ edu.certificate_number || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Kota</span>
                      <span class="value">{{ edu.city || '-' }}</span>
                    </div>
                    <div class="biodata-item" v-if="edu.notes">
                      <span class="label">Catatan</span>
                      <span class="value">{{ edu.notes }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Berkas Dokumen -->
            <div class="biodata-section view-card" v-if="viewingTeacher.documents && viewingTeacher.documents.length > 0">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Berkas Dokumen
              </h4>
              <div class="documents-view-list">
                <div v-for="(doc, index) in viewingTeacher.documents" :key="doc.id || index" class="document-view-item">
                  <div class="document-view-info">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div class="document-view-details">
                      <span class="document-view-name">{{ doc.name }}</span>
                      <span class="document-view-meta">{{ doc.file_name }} • {{ doc.file_size_human || formatFileSize(doc.file_size) }}</span>
                    </div>
                  </div>
                  <a :href="doc.file_url" target="_blank" class="btn-download-view" title="Download">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>
                </div>
              </div>
            </div>

            <!-- Riwayat Non-Induk -->
            <div class="biodata-section view-card" v-if="viewingTeacher.assignments && viewingTeacher.assignments.length > 0">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Riwayat Non-Induk
              </h4>
              <div class="assignment-history">
                <div v-for="assignment in viewingTeacher.assignments" :key="assignment.id" class="assignment-item">
                  <div class="assignment-item-header">
                    <div>
                      <h5>{{ assignment.institution?.name || '-' }}</h5>
                      <span class="assignment-status" :class="`status-${assignment.status}`">{{ assignment.status }}</span>
                    </div>
                    <button
                      v-if="canEndAssignment(assignment)"
                      type="button"
                      class="btn-secondary"
                      @click="endAssignment(assignment)"
                    >
                      Akhiri
                    </button>
                  </div>
                  <div class="biodata-grid">
                    <div class="biodata-item">
                      <span class="label">Mata Pelajaran</span>
                      <span class="value">{{ assignment.subject || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Penugasan</span>
                      <span class="value">{{ assignment.assignment_title || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Mulai</span>
                      <span class="value">{{ assignment.started_at || '-' }}</span>
                    </div>
                    <div class="biodata-item">
                      <span class="label">Selesai</span>
                      <span class="value">{{ assignment.ended_at || '-' }}</span>
                    </div>
                    <div class="biodata-item" v-if="assignment.assignment_notes">
                      <span class="label">Catatan</span>
                      <span class="value">{{ assignment.assignment_notes }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Catatan -->
            <div class="biodata-section view-card" v-if="viewingTeacher.notes">
              <h4 class="section-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Catatan
              </h4>
              <div class="biodata-grid">
                <div class="biodata-item">
                  <span class="value">{{ viewingTeacher.notes }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reset Sandi Modal (admin) -->
      <div v-if="showResetPasswordModal" class="modal-overlay" @click="closeResetPasswordModal">
        <div class="modal-content reset-password-modal" @click.stop>
          <div class="modal-header">
            <h3>Reset sandi login</h3>
            <button type="button" class="btn-close" @click="closeResetPasswordModal">×</button>
          </div>
          <div class="modal-body">
            <p v-if="viewingTeacher" class="reset-password-target">
              Pegawai: <strong>{{ viewingTeacher.name }}</strong> ({{ viewingTeacher.user_account?.email || viewingTeacher.email }})
            </p>
            <div class="form-group">
              <label>Sandi baru</label>
              <input
                v-model="resetPasswordForm.password"
                type="password"
                placeholder="Min. 8 karakter, huruf dan angka"
                autocomplete="new-password"
              />
              <span v-if="resetPasswordError" class="error-text">{{ resetPasswordError }}</span>
            </div>
            <div class="form-group">
              <label>Konfirmasi sandi</label>
              <input
                v-model="resetPasswordForm.password_confirmation"
                type="password"
                placeholder="Ulangi sandi baru"
                autocomplete="new-password"
              />
            </div>
            <p class="form-hint">Setelah direset, beri tahu pegawai sandi baru secara aman (lisan/dokumen internal) dan sarankan ganti sandi setelah login.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeResetPasswordModal">Batal</button>
            <button type="button" class="btn-primary" @click="submitResetPassword" :disabled="resetPasswordLoading">
              {{ resetPasswordLoading ? 'Memproses...' : 'Reset sandi' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Non-Induk Request Modal -->
      <div v-if="showAssignmentModal" class="modal-overlay" @click="closeAssignmentModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tambah Guru Non-Induk</h3>
            <button @click="closeAssignmentModal" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>NIK</label>
                <input v-model="nikSearch" placeholder="Masukkan NIK 16 digit" />
              </div>
              <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn-secondary" @click="searchByNik" :disabled="searchLoading">
                  {{ searchLoading ? 'Mencari...' : 'Cari' }}
                </button>
              </div>
            </div>

            <div v-if="searchResult" class="assignment-result">
              <div class="info-box">
                <p><strong>Nama:</strong> {{ searchResult.name }}</p>
                <p><strong>NIK:</strong> {{ searchResult.nik }}</p>
                <p><strong>Sekolah Induk:</strong> {{ searchResult.institution?.name || '-' }}</p>
              </div>

              <div v-if="isSameInstitution(searchResult)" class="info-box">
                <p>Guru ini sudah menjadi induk di sekolah Anda.</p>
              </div>

              <div v-else class="assignment-form">
                <div class="form-row">
                  <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <input v-model="assignmentForm.subject" />
                  </div>
                  <div class="form-group">
                    <label>Penugasan</label>
                    <input v-model="assignmentForm.assignment_title" />
                  </div>
                </div>
                <div class="form-group">
                  <label>Catatan Penugasan</label>
                  <textarea v-model="assignmentForm.assignment_notes" rows="3"></textarea>
                </div>
                <button type="button" class="btn-primary" @click="submitAssignmentRequest" :disabled="assignmentSubmitting">
                  {{ assignmentSubmitting ? 'Mengirim...' : 'Ajukan Non-Induk' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Non-Induk Requests Modal -->
      <div v-if="showAssignmentRequestsModal" class="modal-overlay" @click="closeAssignmentRequestsModal">
        <div class="modal-content view-modal" @click.stop>
          <div class="modal-header">
            <h3>Permintaan Non-Induk</h3>
            <button @click="closeAssignmentRequestsModal" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div v-if="loadingPendingAssignments" class="loading-state">
              <div class="loading-spinner">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                    <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
                  </circle>
                </svg>
              </div>
              <p>Memuat permintaan...</p>
            </div>

            <div v-else-if="pendingAssignments.length === 0" class="empty-state">
              <h3>Tidak ada permintaan</h3>
              <p>Belum ada permintaan non-induk yang masuk.</p>
            </div>

            <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Guru</th>
                    <th>Sekolah Peminta</th>
                    <th>Mata Pelajaran</th>
                    <th>Penugasan</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="request in pendingAssignments" :key="request.id">
                    <td>
                      <div class="name-cell">
                        <span>{{ request.employee?.name || '-' }}</span>
                        <span class="badge-request">Pending</span>
                      </div>
                    </td>
                    <td>{{ request.institution?.name || '-' }}</td>
                    <td>{{ request.subject || '-' }}</td>
                    <td>{{ request.assignment_title || '-' }}</td>
                    <td>
                      <div class="action-buttons">
                        <button @click="approveAssignment(request)" class="btn-action btn-view" title="Setujui">
                          ✓
                        </button>
                        <button @click="rejectAssignment(request)" class="btn-action btn-delete" title="Tolak">
                          ×
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Result Modal -->
      <div v-if="showImportResultModal" class="modal-overlay" @click="closeImportResultModal">
        <div class="modal-content view-modal" @click.stop>
          <div class="modal-header">
            <h3>Hasil Import Guru</h3>
            <button @click="closeImportResultModal" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div class="import-summary">
              <div class="summary-card">
                <span class="summary-label">Akun dibuat</span>
                <span class="summary-value">{{ importResult.created_accounts.length }}</span>
              </div>
              <div class="summary-card">
                <span class="summary-label">Konflik email</span>
                <span class="summary-value">{{ importResult.account_conflicts.length }}</span>
              </div>
              <div class="summary-card">
                <span class="summary-label">Error validasi</span>
                <span class="summary-value">{{ importResult.errors.length }}</span>
              </div>
            </div>

            <div class="import-actions" v-if="importResult.created_accounts.length">
              <button class="btn-secondary btn-compact" @click="downloadImportCredentials">
                Download Password
              </button>
              <span class="import-note">Simpan password ini dan minta guru mengganti setelah login.</span>
            </div>

            <div v-if="importResult.created_accounts.length" class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Baris</th>
                    <th>Email</th>
                    <th>Password Awal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in importResult.created_accounts" :key="`${item.row}-${item.email}`">
                    <td>{{ item.row }}</td>
                    <td>{{ item.email }}</td>
                    <td><span class="import-tag">{{ item.password }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="importResult.account_conflicts.length" class="table-container">
              <h4 class="section-subtitle">Konflik Email</h4>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Baris</th>
                    <th>Email</th>
                    <th>Role Terdeteksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in importResult.account_conflicts" :key="`${item.row}-${item.email}`">
                    <td>{{ item.row }}</td>
                    <td>{{ item.email }}</td>
                    <td>{{ item.role }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="importResult.errors.length" class="table-container">
              <h4 class="section-subtitle">Error Import</h4>
              <ul class="import-errors">
                <li v-for="(error, index) in importResult.errors" :key="index">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    />
  </Layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import TeacherFilters from '@/components/teacher/TeacherFilters.vue'
import TeacherTable from '@/components/teacher/TeacherTable.vue'
import TeacherTableSkeleton from '@/components/TeacherTableSkeleton.vue'
import { useTeacherList } from '@/composables/useTeacherList'
import { employeeApi } from '@/api/teacher'
import { institutionApi } from '@/api/institution'
import { permissionApi } from '@/api/permissions'
import { useReferenceDataStore } from '@/stores/referenceData'
import { getInstitutionTypeLabel } from '@/utils/institution'
import { validators } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'
import * as XLSX from 'xlsx'

const toast = useToast()
const authStore = useAuthStore()
const referenceStore = useReferenceDataStore()
const availableAdditionalDuties = computed(() => referenceStore.additionalDuties)
const loadingAdditionalDuties = computed(() => referenceStore.additionalDutiesLoading)
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const availableModules = ref([])
const loadingPermissions = ref(false)

const { teachers, loading, error: listError, filters, loadTeachers, getTeacherSubject, getStatusClass } = useTeacherList()
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const showResetPasswordModal = ref(false)
const showAssignmentModal = ref(false)
const showAssignmentRequestsModal = ref(false)
const showImportResultModal = ref(false)
const viewingTeacher = ref(null)
const activeTab = ref(1)
const saving = ref(false)
const deleteLoading = ref(false)
const error = ref('')
const nikSearch = ref('')
const searchResult = ref(null)
const searchLoading = ref(false)
const assignmentSubmitting = ref(false)
const assignmentForm = ref({
  subject: '',
  assignment_title: '',
  assignment_notes: ''
})
const pendingAssignments = ref([])
const loadingPendingAssignments = ref(false)
const importResult = ref({
  created_accounts: [],
  account_conflicts: [],
  errors: []
})
const resetPasswordForm = ref({ password: '', password_confirmation: '' })
const resetPasswordLoading = ref(false)
const resetPasswordError = ref('')

const form = ref({
  type: 'Guru',
  nik: '',
  nip: '',
  nuptk: '',
  name: '',
  gender: '',
  birth_date: '',
  birth_place: '',
  address: '',
  phone: '',
  email: '',
  religion: '',
  employment_status: '',
  education_level: '',
  major: '',
  subject: '',
  status: 'Aktif',
  join_date: '',
  notes: '',
  certification_status: '',
  certification_date: '',
  teacher_registration_number: '',
  certification_number: '',
  certification_issuing_authority: '',
  user_role: '',
  permission_keys: ['correspondence'],
  additional_duty_ids: [],
  affiliation: null,
  current_assignment: null,
  assignment_subject: '',
  assignment_title: '',
  assignment_notes: '',
  educations: [{
    level: '',
    school_name: '',
    major: '',
    graduation_year: null,
    certificate_number: '',
    city: '',
    notes: ''
  }],
  documents: []
})

const editingId = ref(null)

const isInstitutionAdmin = computed(() => authStore.user?.role === 'institution_admin')
const isNonIndukEdit = computed(() => Boolean(editingId.value && form.value.affiliation === 'non_induk'))
const isViewingGuru = computed(() => (viewingTeacher.value?.type || '').toString().toLowerCase() === 'guru')
const canResetEmployeePassword = computed(() => {
  const role = authStore.user?.role
  return role === 'institution_admin' || role === 'admin' || role === 'super_admin'
})

const loadPermissions = async () => {
  loadingPermissions.value = true
  try {
    const response = await permissionApi.getAll()
    availableModules.value = response.data.data || []
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal memuat daftar modul')
  } finally {
    loadingPermissions.value = false
  }
}

const formatUserRole = (role) => {
  if (!role) return '-'
  if (role === 'teacher') return 'Guru'
  if (role === 'staff') return 'Staff'
  return role
}

const isSameInstitution = (employee) => {
  return employee?.institution?.id === authStore.user?.institution_id
}

const openAssignmentRequestModal = () => {
  showAssignmentModal.value = true
  nikSearch.value = ''
  searchResult.value = null
  assignmentForm.value = {
    subject: '',
    assignment_title: '',
    assignment_notes: ''
  }
}

const closeAssignmentModal = () => {
  showAssignmentModal.value = false
  nikSearch.value = ''
  searchResult.value = null
  assignmentSubmitting.value = false
}

const searchByNik = async () => {
  if (!nikSearch.value) {
    toast.error('Gagal', 'NIK wajib diisi')
    return
  }

  searchLoading.value = true
  try {
    const response = await employeeApi.searchByNik(nikSearch.value)
    searchResult.value = response.data.data
  } catch (err) {
    searchResult.value = null
    toast.error('Gagal', err.formattedMessage || 'Pegawai tidak ditemukan')
  } finally {
    searchLoading.value = false
  }
}

const submitAssignmentRequest = async () => {
  if (!searchResult.value) return

  assignmentSubmitting.value = true
  try {
    await employeeApi.requestAssignment(searchResult.value.id, assignmentForm.value)
    toast.success('Berhasil', 'Permintaan non-induk berhasil dikirim')
    closeAssignmentModal()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal mengirim permintaan')
  } finally {
    assignmentSubmitting.value = false
  }
}

const openAssignmentRequestsModal = async () => {
  showAssignmentRequestsModal.value = true
  await loadPendingAssignments()
}

const closeAssignmentRequestsModal = () => {
  showAssignmentRequestsModal.value = false
}

const closeImportResultModal = () => {
  showImportResultModal.value = false
  importResult.value = {
    created_accounts: [],
    account_conflicts: [],
    errors: []
  }
}

const downloadImportCredentials = () => {
  if (!importResult.value.created_accounts.length) return

  const escapeCsv = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`
  const header = ['Row', 'Email', 'Password']
  const rows = importResult.value.created_accounts.map((item) => [
    item.row,
    item.email,
    item.password
  ])

  const csvContent = [
    header.map(escapeCsv).join(','),
    ...rows.map((row) => row.map(escapeCsv).join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `akun_guru_import_${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const loadPendingAssignments = async () => {
  loadingPendingAssignments.value = true
  try {
    const response = await employeeApi.getPendingAssignments({ per_page: 100 })
    pendingAssignments.value = response.data.data || []
  } catch (err) {
    toast.error('Gagal', 'Gagal memuat permintaan non-induk')
    console.error(err)
  } finally {
    loadingPendingAssignments.value = false
  }
}

const approveAssignment = async (assignment) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Persetujuan',
    message: 'Setujui permintaan non-induk ini?',
    warning: ''
  })
  
  if (!confirmed) return

  try {
    await employeeApi.approveAssignment(assignment.id)
    toast.success('Berhasil', 'Permintaan disetujui')
    loadPendingAssignments()
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menyetujui permintaan')
  }
}

const rejectAssignment = async (assignment) => {
  const reason = prompt('Alasan penolakan:', '')
  if (!reason) return

  try {
    await employeeApi.rejectAssignment(assignment.id, { rejection_reason: reason })
    toast.success('Berhasil', 'Permintaan ditolak')
    loadPendingAssignments()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menolak permintaan')
  }
}

const canEndAssignment = (assignment) => {
  return assignment?.status === 'approved'
    && isInstitutionAdmin.value
    && viewingTeacher.value?.institution_id === authStore.user?.institution_id
}

const endAssignment = async (assignment) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Akhiri Penugasan',
    message: 'Akhiri penugasan non-induk ini?',
    warning: ''
  })
  
  if (!confirmed) return

  const reason = prompt('Alasan mengakhiri penugasan (opsional):', '') || null

  try {
    await employeeApi.endAssignment(assignment.id, { ended_reason: reason })
    toast.success('Berhasil', 'Penugasan diakhiri')
    if (viewingTeacher.value?.id) {
      const response = await employeeApi.get(viewingTeacher.value.id)
      viewingTeacher.value = response.data.data
    }
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal mengakhiri penugasan')
  }
}

const viewTeacher = async (teacher) => {
  try {
    // Load full employee data with educations and documents
    const response = await employeeApi.get(teacher.id)
    viewingTeacher.value = response.data.data
    showViewModal.value = true
  } catch (err) {
    toast.error('Gagal', 'Gagal memuat data lengkap pegawai')
    console.error(err)
  }
}

const editTeacher = async (teacher) => {
  editingId.value = teacher.id
  activeTab.value = 1
  
  // Load full employee data with educations and documents
  try {
    const response = await employeeApi.get(teacher.id)
    const fullData = response.data.data
    
    Object.assign(form.value, fullData)
    form.value.affiliation = fullData.affiliation || null
    form.value.current_assignment = fullData.current_assignment || null
    form.value.assignment_subject = fullData.current_assignment?.subject || ''
    form.value.assignment_title = fullData.current_assignment?.assignment_title || ''
    form.value.assignment_notes = fullData.current_assignment?.assignment_notes || ''
    form.value.user_role = (fullData.user_account?.role && ['teacher', 'staff'].includes(fullData.user_account.role))
      ? fullData.user_account.role
      : ''
    form.value.permission_keys = fullData.user_account?.permissions || ['correspondence']
    form.value.additional_duty_ids = (fullData.additional_duties || []).map(d => d.id)
    if (fullData.birth_date) {
      form.value.birth_date = fullData.birth_date.split('T')[0]
    }
    if (fullData.join_date) {
      form.value.join_date = fullData.join_date.split('T')[0]
    }
    if (fullData.certification_date) {
      form.value.certification_date = fullData.certification_date.split('T')[0]
    }
    
    // Set educations
    form.value.educations = fullData.educations && fullData.educations.length > 0 
      ? fullData.educations 
      : [{
          level: '',
          school_name: '',
          major: '',
          graduation_year: null,
          certificate_number: '',
          city: '',
          notes: ''
        }]
    
    // Set documents
    form.value.documents = fullData.documents || []
    
    showEditModal.value = true
  } catch (err) {
    toast.error('Gagal', 'Gagal memuat data lengkap pegawai')
    console.error(err)
  }
}

const deleteTeacher = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus guru ini?',
    warning: 'Data guru akan dipindahkan ke kotak sampah dan dapat dipulihkan kapan saja dari menu Kotak Sampah.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await employeeApi.delete(id)
    toast.success('Berhasil', 'Guru berhasil dihapus')
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus guru')
  } finally {
    setDeleteLoading(false)
  }
}

function switchListTab(onlyTrashed) {
  filters.value.only_trashed = !!onlyTrashed
  loadTeachers()
}

async function handleRestoreTeacher(teacher) {
  try {
    await employeeApi.restore(teacher.id)
    toast.success('Berhasil', 'Guru berhasil dipulihkan')
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memulihkan guru')
  }
}

const validationRules = {
  nik: [
    (value) => validators.required(value, 'NIK wajib diisi'),
    (value) => validators.nik(value, 'NIK harus terdiri dari 16 digit angka')
  ],
  name: [
    (value) => validators.required(value, 'Nama lengkap wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama maksimal 255 karakter')
  ],
  gender: [
    (value) => validators.required(value, 'Jenis kelamin wajib diisi')
  ],
  email: [
    (value) => form.value.type === 'Guru'
      ? validators.required(value, 'Email wajib diisi untuk guru')
      : null,
    (value) => validators.email(value, 'Format email tidak valid'),
    (value) => validators.maxLength(value, 255, 'Email maksimal 255 karakter')
  ],
  phone: [
    (value) => validators.phone(value, 'Format nomor telepon tidak valid')
  ],
  birth_date: [
    (value) => validators.date(value, 'Format tanggal tidak valid')
  ],
  join_date: [
    (value) => validators.date(value, 'Format tanggal tidak valid')
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

  if (isNonIndukEdit.value) {
    saving.value = true
    try {
      const assignmentId = form.value.current_assignment?.id
      if (!assignmentId) {
        error.value = 'Data penugasan non-induk tidak ditemukan'
        return
      }

      await employeeApi.updateAssignment(assignmentId, {
        subject: form.value.assignment_subject,
        assignment_title: form.value.assignment_title,
        assignment_notes: form.value.assignment_notes
      })

      toast.success('Berhasil', 'Penugasan non-induk berhasil diperbarui')
      closeModal()
      loadTeachers()
    } catch (err) {
      const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan penugasan'
      error.value = errorMsg
      toast.error('Gagal', errorMsg)
    } finally {
      saving.value = false
    }
    return
  }
  
  // Validate form
  const isValid = validateAll()
  if (!isValid) {
    error.value = 'Mohon perbaiki kesalahan pada form'
    return
  }
  
  saving.value = true
  
  try {
    let response = null
    const payload = { ...form.value }
    if (!payload.user_role) {
      delete payload.user_role
      delete payload.permission_keys
    }
    // additional_duty_ids always sent for guru (backend merges with permissions)

    if (editingId.value) {
      response = await employeeApi.update(editingId.value, payload)
      toast.success('Berhasil', 'Data guru berhasil diperbarui')
    } else {
      response = await employeeApi.create(payload)
      toast.success('Berhasil', 'Guru berhasil ditambahkan')
      const newTeacher = response?.data?.data
      if (newTeacher && typeof newTeacher === 'object') {
        teachers.value = [newTeacher, ...teachers.value]
      }
    }

    const generatedPassword = response?.data?.generated_password
    if (generatedPassword) {
      toast.success(
        'Password Akun Guru',
        `Password awal: ${generatedPassword}. Harap simpan dan ganti setelah login.`
      )
    }

    const userConflict = response?.data?.user_conflict
    if (userConflict?.email && userConflict?.role) {
      toast.warning(
        'Perhatian',
        `Email ${userConflict.email} sudah digunakan akun role ${userConflict.role}.`
      )
    }
    closeModal()
    await loadTeachers()
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
  editingId.value = null
  activeTab.value = 1
  form.value = {
    type: 'Guru',
    nik: '',
    nip: '',
    nuptk: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    address: '',
    phone: '',
    email: '',
    religion: '',
    employment_status: '',
    education_level: '',
    major: '',
    subject: '',
    status: 'Aktif',
    join_date: '',
    notes: '',
    certification_status: '',
    certification_date: '',
    teacher_registration_number: '',
    certification_number: '',
    certification_issuing_authority: '',
    user_role: '',
    permission_keys: ['correspondence'],
    additional_duty_ids: [],
    affiliation: null,
    current_assignment: null,
    assignment_subject: '',
    assignment_title: '',
    assignment_notes: '',
    educations: [{
      level: '',
      school_name: '',
      major: '',
      graduation_year: null,
      certificate_number: '',
      city: '',
      notes: ''
    }],
    documents: []
  }
  error.value = ''
  if (documentUpload.value) {
    documentUpload.value.value = ''
  }
}

const closeViewModal = () => {
  showViewModal.value = false
  viewingTeacher.value = null
}

const openResetPasswordModal = () => {
  resetPasswordForm.value = { password: '', password_confirmation: '' }
  resetPasswordError.value = ''
  showResetPasswordModal.value = true
}

const closeResetPasswordModal = () => {
  showResetPasswordModal.value = false
  resetPasswordForm.value = { password: '', password_confirmation: '' }
  resetPasswordError.value = ''
}

const submitResetPassword = async () => {
  const { password, password_confirmation } = resetPasswordForm.value
  resetPasswordError.value = ''
  if (!password || password.length < 8) {
    resetPasswordError.value = 'Sandi minimal 8 karakter dan harus mengandung huruf serta angka.'
    return
  }
  if (password !== password_confirmation) {
    resetPasswordError.value = 'Konfirmasi sandi tidak cocok.'
    return
  }
  if (!/^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(password)) {
    resetPasswordError.value = 'Sandi harus mengandung huruf dan angka.'
    return
  }
  if (!viewingTeacher.value?.id) return
  resetPasswordLoading.value = true
  try {
    await employeeApi.resetPasswordByAdmin(viewingTeacher.value.id, {
      password,
      password_confirmation
    })
    toast.success('Berhasil', 'Sandi berhasil direset. Beri tahu pegawai sandi baru secara aman dan sarankan ganti sandi setelah login.')
    closeResetPasswordModal()
  } catch (err) {
    const msg = err.response?.data?.message || err.formattedMessage || 'Gagal reset sandi'
    resetPasswordError.value = msg
    toast.error('Gagal', msg)
  } finally {
    resetPasswordLoading.value = false
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const printPDF = async () => {
  if (!viewingTeacher.value) return
  try {
    const institutionResponse = await institutionApi.getMy()
    const institution = institutionResponse.data?.data || institutionResponse.data || {}
    const printWindow = window.open('', '_blank')
    const emp = viewingTeacher.value
    const filename = `${emp.nik || 'NIK'}_${emp.name || 'Pegawai'}.pdf`
    const addressParts = []
    if (institution.address) addressParts.push(institution.address)
    if (institution.village) addressParts.push(institution.village)
    if (institution.sub_district) addressParts.push(`Kec. ${institution.sub_district}`)
    if (institution.district) addressParts.push(institution.district)
    if (institution.province) addressParts.push(institution.province)
    if (institution.postal_code) addressParts.push(institution.postal_code)
    const fullAddress = addressParts.join(', ') || '-'
    const principalLabel = `Kepala ${getInstitutionTypeLabel(institution?.level) || 'Sekolah/Madrasah'}`
    const roleLabel = (r) => { if (!r) return '-'; if (r === 'teacher') return 'Guru'; if (r === 'staff') return 'Staff'; return r }
    const content = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Biodata ${emp.name}</title>
  <style>
  @media print { @page { size: A4; margin: 1cm 1.5cm 1cm 1.5cm; } }
  body { font-family: 'Times New Roman', serif; line-height: 1.15; color: #000; max-width: 800px; margin: 0 auto; padding: 0; font-size: 12px; }
  .kop { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; text-align: center; }
  .kop-header { display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 6px; }
  .kop-logo { max-width: 64px; max-height: 64px; object-fit: contain; }
  .kop-name { font-size: 16px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.15; }
  .kop-address { font-size: 11px; margin-bottom: 4px; line-height: 1.2; }
  .kop-info { font-size: 10px; margin-top: 4px; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }
  .kop-info-item { display: flex; gap: 4px; }
  .kop-info-label { font-weight: bold; }
  .header { text-align: center; margin-bottom: 10px; margin-top: 8px; }
  .header h1 { color: #000; margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.15; }
  .header p { margin-top: 2px; font-size: 12px; line-height: 1.15; }
  .section { margin-bottom: 10px; page-break-inside: avoid; }
  .section-title { background: #f0f0f0; color: #000; padding: 4px 10px; margin: 0 0 6px 0; font-size: 12px; font-weight: bold; border-left: 3px solid #000; line-height: 1.2; }
  .biodata-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 0; margin-bottom: 6px; border: 1px solid #ddd; }
  .biodata-item { display: contents; }
  .label { font-weight: bold; color: #000; padding: 3px 8px; background: #f8f8f8; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; font-size: 11px; line-height: 1.2; }
  .value { padding: 3px 8px; border-bottom: 1px solid #ddd; font-size: 11px; line-height: 1.2; }
  .biodata-grid .biodata-item:last-child .label, .biodata-grid .biodata-item:nth-last-child(2) .label { border-bottom: none; }
  .biodata-grid .biodata-item:last-child .value, .biodata-grid .biodata-item:nth-last-child(2) .value { border-bottom: none; }
  .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; align-items: flex-start; }
  .footer-right { flex: 1; text-align: right; }
  .footer-date { font-size: 11px; margin-bottom: 24px; line-height: 1.2; }
  .footer-signature-label { margin-bottom: 36px; font-weight: bold; font-size: 11px; }
  .footer-signature-name { font-weight: bold; text-decoration: underline; font-size: 12px; }
  .footer-signature-nip { font-size: 10px; margin-top: 2px; }
  </style>
</head>
<body>
  <div class="kop">
    <div class="kop-header">
      ${institution.logo ? `<img src="${institution.logo}" alt="Logo" class="kop-logo" />` : ''}
      <div style="flex: 1;">
        <div class="kop-name">${institution.name || 'NAMA LEMBAGA'}</div>
        <div class="kop-address">${fullAddress}</div>
      </div>
    </div>
    <div class="kop-info">
      <div class="kop-info-item"><span class="kop-info-label">NPSN:</span><span>${institution.npsn || '-'}</span></div>
      <div class="kop-info-item"><span class="kop-info-label">No. Statistik:</span><span>${institution.nss || '-'}</span></div>
    </div>
  </div>
  <div class="header">
    <h1>Biodata Pegawai</h1>
    <p>${emp.name || ''}</p>
  </div>
  <div class="section">
    <h3 class="section-title">Identitas</h3>
    <div class="biodata-grid">
      <div class="biodata-item"><span class="label">NIP</span><span class="value">${emp.nip || '-'}</span></div>
      <div class="biodata-item"><span class="label">NIK</span><span class="value">${emp.nik || '-'}</span></div>
      <div class="biodata-item"><span class="label">Tipe Pegawai</span><span class="value">${emp.type || '-'}</span></div>
      <div class="biodata-item"><span class="label">NUPTK</span><span class="value">${emp.nuptk || '-'}</span></div>
      <div class="biodata-item"><span class="label">Nama Lengkap</span><span class="value">${emp.name || '-'}</span></div>
      <div class="biodata-item"><span class="label">Jenis Kelamin</span><span class="value">${emp.gender === 'L' ? 'Laki-laki' : emp.gender === 'P' ? 'Perempuan' : '-'}</span></div>
      <div class="biodata-item"><span class="label">Tempat Lahir</span><span class="value">${emp.birth_place || '-'}</span></div>
      <div class="biodata-item"><span class="label">Tanggal Lahir</span><span class="value">${formatDate(emp.birth_date)}</span></div>
      <div class="biodata-item"><span class="label">Alamat</span><span class="value">${emp.address || '-'}</span></div>
      <div class="biodata-item"><span class="label">Telepon</span><span class="value">${emp.phone || '-'}</span></div>
      <div class="biodata-item"><span class="label">Email</span><span class="value">${emp.email || '-'}</span></div>
      <div class="biodata-item"><span class="label">Agama</span><span class="value">${emp.religion || '-'}</span></div>
    </div>
  </div>
  <div class="section">
    <h3 class="section-title">Data Kepegawaian</h3>
    <div class="biodata-grid">
      <div class="biodata-item"><span class="label">Status Kepegawaian</span><span class="value">${emp.employment_status || '-'}</span></div>
      <div class="biodata-item"><span class="label">Pendidikan Terakhir</span><span class="value">${emp.education_level || '-'}</span></div>
      <div class="biodata-item"><span class="label">Jurusan</span><span class="value">${emp.major || '-'}</span></div>
      <div class="biodata-item"><span class="label">Mata Pelajaran</span><span class="value">${emp.subject || '-'}</span></div>
      <div class="biodata-item"><span class="label">Tanggal Bergabung</span><span class="value">${formatDate(emp.join_date)}</span></div>
      <div class="biodata-item"><span class="label">Status</span><span class="value">${emp.status || '-'}</span></div>
    </div>
  </div>
  ${emp.has_user_account && emp.user_account ? `
  <div class="section">
    <h3 class="section-title">Akun Login</h3>
    <div class="biodata-grid">
      <div class="biodata-item"><span class="label">Email (login)</span><span class="value">${emp.user_account.email || '-'}</span></div>
      <div class="biodata-item"><span class="label">Role</span><span class="value">${roleLabel(emp.user_account.role)}</span></div>
    </div>
  </div>
  ` : ''}
  ${emp.educations && emp.educations.length ? `
  <div class="section">
    <h3 class="section-title">Riwayat Pendidikan</h3>
    ${emp.educations.map((edu, i) => `
    <div style="margin-bottom: 6px;">
      <div style="font-weight: bold; margin-bottom: 3px; font-size: 11px;">${edu.level || '-'}</div>
      <div class="biodata-grid">
        <div class="biodata-item"><span class="label">Nama Sekolah</span><span class="value">${edu.school_name || '-'}</span></div>
        <div class="biodata-item"><span class="label">Jurusan</span><span class="value">${edu.major || '-'}</span></div>
        <div class="biodata-item"><span class="label">Tahun Lulus</span><span class="value">${edu.graduation_year || '-'}</span></div>
        <div class="biodata-item"><span class="label">Nomor Ijazah</span><span class="value">${edu.certificate_number || '-'}</span></div>
      </div>
    </div>
    `).join('')}
  </div>
  ` : ''}
  ${emp.notes ? `
  <div class="section">
    <h3 class="section-title">Catatan</h3>
    <p style="margin: 0; font-size: 11px; line-height: 1.2;">${emp.notes}</p>
  </div>
  ` : ''}
  <div class="footer">
    <div></div>
    <div class="footer-right">
      <div class="footer-date">${institution.district || 'Kota/Kabupaten'}, ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</div>
      <div>
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
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    console.error('Error loading institution for print:', err)
    toast.error('Gagal', 'Gagal memuat data institusi untuk KOP surat')
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) return '-'
  if (bytes >= 1073741824) {
    return (bytes / 1073741824).toFixed(2) + ' GB'
  } else if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(2) + ' MB'
  } else if (bytes >= 1024) {
    return (bytes / 1024).toFixed(2) + ' KB'
  } else {
    return bytes + ' bytes'
  }
}

const addEducation = () => {
  form.value.educations.push({
    level: '',
    school_name: '',
    major: '',
    graduation_year: null,
    certificate_number: '',
    city: '',
    notes: ''
  })
}

const removeEducation = (index) => {
  if (form.value.educations.length > 1) {
    form.value.educations.splice(index, 1)
  }
}

const documentUpload = ref(null)

const handleDocumentUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file
  if (file.type !== 'application/pdf') {
    toast.error('Gagal', 'File harus berformat PDF')
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    toast.error('Gagal', 'Ukuran file maksimal 2MB')
    event.target.value = ''
    return
  }

  if (!editingId.value) {
    toast.error('Gagal', 'Simpan data pegawai terlebih dahulu')
    event.target.value = ''
    return
  }

  if (form.value.documents.length >= 20) {
    toast.error('Gagal', 'Maksimal 20 file dokumen')
    event.target.value = ''
    return
  }

  try {
    saving.value = true
    
    // Prompt for document name
    const docName = prompt('Masukkan nama dokumen (contoh: KK, Ijazah S1, dll):')
    if (!docName || docName.trim() === '') {
      event.target.value = ''
      saving.value = false
      return
    }

    const formData = new FormData()
    formData.append('file', file)
    formData.append('name', docName.trim())

    const response = await employeeApi.uploadDocument(editingId.value, formData)

    // Add document to form
    form.value.documents.push(response.data.data)
    
    toast.success('Berhasil', 'Dokumen berhasil diupload')
    event.target.value = ''
  } catch (err) {
    toast.error('Gagal', err.message || 'Gagal mengupload dokumen')
    console.error(err)
  } finally {
    saving.value = false
  }
}

const deleteDocument = async (documentId, index) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus dokumen ini?',
    warning: 'Dokumen akan dihapus secara permanen.'
  })
  
  if (!confirmed) return

  if (!editingId.value) {
    toast.error('Gagal', 'ID pegawai tidak ditemukan')
    return
  }

  try {
    saving.value = true
    await employeeApi.deleteDocument(editingId.value, documentId)
    
    // Remove from form
    form.value.documents.splice(index, 1)
    
    toast.success('Berhasil', 'Dokumen berhasil dihapus')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus dokumen')
  } finally {
    saving.value = false
  }
}

// Export to Excel
const exportToExcel = async () => {
  try {
    loading.value = true
    // Ambil semua data pegawai tanpa pagination
    const params = { per_page: 10000 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.employment_status) params.employment_status = filters.value.employment_status
    
    const response = await employeeApi.getAll(params)
    const allEmployees = response.data.data || []
    
    // Siapkan data untuk Excel
    const excelData = allEmployees.map(employee => ({
      'Tipe Pegawai': employee.type || '',
      'NIK': employee.nik || '',
      'NIP': employee.nip || '',
      'NUPTK': employee.nuptk || '',
      'Nama Lengkap': employee.name || '',
      'Jenis Kelamin': employee.gender === 'L' ? 'Laki-laki' : employee.gender === 'P' ? 'Perempuan' : '',
      'Tempat Lahir': employee.birth_place || '',
      'Tanggal Lahir': employee.birth_date ? new Date(employee.birth_date).toLocaleDateString('id-ID') : '',
      'Alamat': employee.address || '',
      'No. Telepon': employee.phone || '',
      'Email': employee.email || '',
      'Agama': employee.religion || '',
      'Status Kepegawaian': employee.employment_status || '',
      'Tingkat Pendidikan': employee.education_level || '',
      'Jurusan': employee.major || '',
      'Mata Pelajaran': employee.subject || '',
      'Status': employee.status || '',
      'Tanggal Bergabung': employee.join_date ? new Date(employee.join_date).toLocaleDateString('id-ID') : '',
      'Catatan': employee.notes || '',
      'Status Sertifikasi': employee.certification_status || '',
      'Tanggal Sertifikasi': employee.certification_date ? new Date(employee.certification_date).toLocaleDateString('id-ID') : '',
      'Nomor Registrasi Guru (NRG)': employee.teacher_registration_number || '',
      'Nomor Sertifikat Pendidik': employee.certification_number || '',
      'Lembaga Penerbit Sertifikat': employee.certification_issuing_authority || ''
    }))
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(excelData)
    
    // Set column widths
    const colWidths = [
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 15 }, { wch: 30 },
      { wch: 18 }, { wch: 18 }, { wch: 22 }, { wch: 25 }, { wch: 25 }
    ]
    ws['!cols'] = colWidths
    
    XLSX.utils.book_append_sheet(wb, ws, 'Data Pegawai')
    
    // Download file
    const fileName = `Data_Pegawai_${new Date().toISOString().split('T')[0]}.xlsx`
    XLSX.writeFile(wb, fileName)
    
    toast.success('Berhasil', 'Data berhasil diekspor ke Excel')
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
    // Buat data template dengan header dan 1 baris contoh
    const templateData = [
      {
        'Tipe Pegawai': 'Guru',
        'NIK': '1234567890123456',
        'NIP': '1234567890123456',
        'NUPTK': '1234567890123456',
        'Nama Lengkap': 'Ahmad Fauzi',
        'Jenis Kelamin': 'L',
        'Tempat Lahir': 'Jakarta',
        'Tanggal Lahir': '1980-01-15',
        'Alamat': 'Jl. Contoh No. 123',
        'No. Telepon': '081234567890',
        'Email': 'ahmad@example.com',
        'Agama': 'Islam',
        'Status Kepegawaian': 'PNS',
        'Tingkat Pendidikan': 'S1',
        'Jurusan': 'Pendidikan Matematika',
        'Mata Pelajaran': 'Matematika',
        'Status': 'Aktif',
        'Tanggal Bergabung': '2020-01-01',
        'Catatan': '',
        'Status Sertifikasi': 'Belum',
        'Tanggal Sertifikasi': '',
        'Nomor Registrasi Guru (NRG)': '',
        'Nomor Sertifikat Pendidik': '',
        'Lembaga Penerbit Sertifikat': ''
      }
    ]
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)
    
    // Set column widths
    const colWidths = [
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 15 }, { wch: 30 },
      { wch: 18 }, { wch: 18 }, { wch: 22 }, { wch: 25 }, { wch: 25 }
    ]
    ws['!cols'] = colWidths
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Pegawai')
    
    // Download file
    const fileName = `Template_Import_Pegawai.xlsx`
    XLSX.writeFile(wb, fileName)
    
    toast.success('Berhasil', 'Template Excel berhasil didownload. Silakan isi data sesuai format yang ada.')
  } catch (err) {
    console.error(err)
    toast.error('Gagal', 'Gagal mendownload template Excel')
  }
}

// Import from Excel
const handleImportExcel = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  try {
    loading.value = true
    
    // Baca file Excel
    const data = await file.arrayBuffer()
    const workbook = XLSX.read(data, { type: 'array' })
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
    const jsonData = XLSX.utils.sheet_to_json(firstSheet)
    
    if (jsonData.length === 0) {
      toast.error('Gagal', 'File Excel kosong')
      return
    }
    
    // Mapping kolom Excel ke field database
    const mappedData = jsonData.map(row => {
      const mapField = (excelCol, dbField) => {
        const value = row[excelCol]
        if (value === undefined || value === null || value === '') return null
        return value
      }
      
      // Parse tanggal
      const parseDate = (dateStr) => {
        if (!dateStr) return null
        if (dateStr instanceof Date) return dateStr.toISOString().split('T')[0]
        // Coba parse berbagai format tanggal
        const date = new Date(dateStr)
        if (!isNaN(date.getTime())) {
          return date.toISOString().split('T')[0]
        }
        return null
      }
      
      // Parse jenis kelamin
      const parseGender = (val) => {
        if (!val) return null
        const str = String(val).toLowerCase()
        if (str.includes('laki') || str === 'l' || str === 'laki-laki') return 'L'
        if (str.includes('perempuan') || str === 'p' || str === 'perempuan') return 'P'
        return null
      }
      
      return {
        type: mapField('Tipe Pegawai', 'type') || 'Guru',
        nik: mapField('NIK', 'nik'),
        nip: mapField('NIP', 'nip'),
        nuptk: mapField('NUPTK', 'nuptk'),
        name: mapField('Nama Lengkap', 'name'),
        gender: parseGender(mapField('Jenis Kelamin', 'gender')),
        birth_place: mapField('Tempat Lahir', 'birth_place'),
        birth_date: parseDate(mapField('Tanggal Lahir', 'birth_date')),
        address: mapField('Alamat', 'address'),
        phone: mapField('No. Telepon', 'phone'),
        email: mapField('Email', 'email'),
        religion: mapField('Agama', 'religion'),
        employment_status: mapField('Status Kepegawaian', 'employment_status'),
        education_level: mapField('Tingkat Pendidikan', 'education_level'),
        major: mapField('Jurusan', 'major'),
        subject: mapField('Mata Pelajaran', 'subject'),
        status: mapField('Status', 'status') || 'Aktif',
        join_date: parseDate(mapField('Tanggal Bergabung', 'join_date')),
        notes: mapField('Catatan', 'notes'),
        certification_status: mapField('Status Sertifikasi', 'certification_status') && ['Sudah', 'Belum'].includes(String(mapField('Status Sertifikasi', 'certification_status')).trim()) ? String(mapField('Status Sertifikasi', 'certification_status')).trim() : null,
        certification_date: parseDate(mapField('Tanggal Sertifikasi', 'certification_date')),
        teacher_registration_number: mapField('Nomor Registrasi Guru (NRG)', 'teacher_registration_number'),
        certification_number: mapField('Nomor Sertifikat Pendidik', 'certification_number'),
        certification_issuing_authority: mapField('Lembaga Penerbit Sertifikat', 'certification_issuing_authority')
      }
    })
    
    // Filter data yang valid (minimal harus ada Nama)
    const validData = mappedData.filter(item => item.name && item.nik)
    
    if (validData.length === 0) {
      toast.error('Gagal', 'Tidak ada data valid yang dapat diimpor. Pastikan kolom Nama Lengkap dan NIK terisi.')
      return
    }
    
    // Kirim ke backend (batch import)
    const response = await employeeApi.import(validData)
    const createdAccounts = response.data.created_accounts || []
    const accountConflicts = response.data.account_conflicts || []
    const importErrors = response.data.errors || []
    
    if (response.data.success_count > 0) {
      toast.success('Berhasil', `Berhasil mengimpor ${response.data.success_count} data pegawai${response.data.error_count > 0 ? `, ${response.data.error_count} gagal` : ''}`)
      if (importErrors.length > 0) {
        console.warn('Import errors:', importErrors)
      }
      loadTeachers()
    } else {
      toast.error('Gagal', 'Gagal mengimpor data pegawai')
    }

    if (createdAccounts.length || accountConflicts.length || importErrors.length) {
      importResult.value = {
        created_accounts: createdAccounts,
        account_conflicts: accountConflicts,
        errors: importErrors
      }
      showImportResultModal.value = true
    }
    
    // Reset input file
    event.target.value = ''
  } catch (err) {
    console.error(err)
    toast.error('Gagal', err.formattedMessage || 'Gagal mengimpor data dari Excel')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPermissions()
  referenceStore.getAdditionalDuties()
})
</script>

<style scoped>
.teacher-page {
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

.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
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

.status-inactive {
  color: #95a5a6;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.name-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.badge-non-induk {
  display: inline-flex;
  align-self: flex-start;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fde68a;
  color: #92400e;
  font-size: 11px;
  font-weight: 600;
}

.badge-request {
  display: inline-flex;
  align-self: flex-start;
  padding: 2px 8px;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  font-size: 11px;
  font-weight: 600;
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
}

.btn-view {
  background: #10b981;
  color: white;
}

.btn-view:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-edit {
  background: #059669;
  color: white;
}

.btn-edit:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
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

/* Form modal (Tambah/Edit Guru) – same style as Student form */
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

.form-subsection-title {
  font-size: 14px;
  color: #475569;
  margin: 20px 0 12px 0;
  padding-bottom: 6px;
  border-bottom: 1px solid #e2e8f0;
}

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
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: formSpinner 0.7s linear infinite;
}

@keyframes formSpinner {
  to {
    transform: rotate(360deg);
  }
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 30px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #999;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 30px;
}

.import-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.summary-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-label {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.summary-value {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}

.import-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.import-note {
  font-size: 12px;
  color: #64748b;
}

.import-tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #4338ca;
  font-weight: 600;
  font-size: 12px;
}

.section-subtitle {
  margin: 16px 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.import-errors {
  margin: 0;
  padding-left: 18px;
  color: #b91c1c;
  font-size: 13px;
}

.import-errors li {
  margin-bottom: 6px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-modal-body .fieldset-reset > .form-group {
  margin-bottom: 18px;
}

.form-modal-body .fieldset-reset > .form-group:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 8px;
  color: #333;
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.form-modal-body .form-group textarea {
  min-height: 88px;
  resize: vertical;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: formSpinner 0.7s linear infinite;
}

.reset-password-modal .modal-body {
  padding: 20px 24px;
}
.reset-password-modal .error-text {
  color: #dc2626;
  font-size: 13px;
  margin-top: 4px;
  display: block;
}
.reset-password-target {
  margin: 0 0 16px 0;
  font-size: 14px;
  color: #475569;
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
  transition: background 0.2s;
}
.btn-reset-password:hover {
  background: #d97706;
}
.btn-reset-password + .hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
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
  padding: 10px 20px;
  background: #e0e0e0;
  color: #333;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #d0d0d0;
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

.form-modal-body .error-message {
  margin: 0 28px 20px;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
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

.tabs-nav {
  display: flex;
  gap: 4px;
  margin-bottom: 32px;
  padding-bottom: 0;
  border-bottom: 2px solid #e2e8f0;
  overflow-x: auto;
  scrollbar-width: none;
}

.form-modal-body .form-tabs-nav {
  margin: 0 -4px 28px 0;
  padding: 0 0 0 0;
  border-bottom: 1px solid #e2e8f0;
  background: transparent;
  gap: 6px;
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

/* View modal – clean & professional */
.view-modal-overlay {
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
}
.view-modal {
  max-width: 720px;
  padding: 0;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}
.view-modal-header {
  position: relative;
  padding: 24px 24px 28px;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  color: white;
}
.view-header-actions {
  position: absolute;
  top: 16px;
  right: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.view-header-actions .btn-print {
  padding: 9px 16px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}
.view-header-actions .btn-print:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
}
.view-header-actions .view-modal-close {
  position: static;
  width: 40px;
  height: 40px;
  min-width: 40px;
  min-height: 40px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(255, 255, 255, 0.12);
  color: white;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
}
.view-header-actions .view-modal-close:hover {
  background: rgba(255, 255, 255, 0.2);
}
.view-modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: rgba(255, 255, 255, 0.12);
  color: white;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
}
.view-modal-close:hover {
  background: rgba(255, 255, 255, 0.2);
}
.view-profile-strip {
  display: flex;
  align-items: center;
  gap: 20px;
}
.view-profile-avatar {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.5px;
  flex-shrink: 0;
}
.view-profile-info {
  min-width: 0;
}
.view-profile-name {
  margin: 0 0 8px 0;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.3px;
  line-height: 1.25;
  color: white;
}
.view-profile-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.view-profile-type {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 500;
}
.view-profile-status {
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.view-profile-status.status-active {
  background: rgba(34, 197, 94, 0.25);
  color: #86efac;
}
.view-profile-status.status-success,
.view-profile-status.status-inactive {
  background: rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.9);
}
.view-profile-status.status-warning {
  background: rgba(251, 191, 36, 0.25);
  color: #fde047;
}

@media (max-width: 560px) {
  .view-modal { max-width: 95%; }
  .view-profile-strip { flex-direction: column; align-items: flex-start; gap: 14px; }
  .view-profile-name { font-size: 18px; }
  .view-body .biodata-grid { grid-template-columns: 1fr; }
  .view-body .biodata-item:nth-last-child(-n+2) { border-bottom: 1px solid #f1f5f9; }
  .view-body .biodata-item:last-child { border-bottom: none; }
}

.biodata-section-akun {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
}
.biodata-section-akun .section-title { padding-left: 12px; border-left-color: #059669; }

.fieldset-reset {
  border: none;
  padding: 0;
  margin: 0;
  min-inline-size: 0;
}

.assignment-section {
  margin-top: 24px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
}

.assignment-section h4 {
  margin-bottom: 16px;
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
}

.assignment-history {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.assignment-item {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 18px;
  background: #f8fafc;
}

.assignment-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e2e8f0;
}

.assignment-item-header h5 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #334155;
}

.assignment-status {
  display: inline-flex;
  margin-top: 6px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.assignment-status.status-approved {
  background: #dcfce7;
  color: #166534;
}

.assignment-status.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.assignment-status.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.assignment-status.status-ended {
  background: #e2e8f0;
  color: #475569;
}

.view-body {
  padding: 24px;
  max-height: calc(90vh - 180px);
  overflow-y: auto;
  background: #f1f5f9;
}

.biodata-section.view-card {
  background: white;
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.biodata-section:last-child {
  margin-bottom: 0;
}

.biodata-section .section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 600;
  color: #334155;
  margin: 0 0 16px 0;
  padding: 0 0 0 12px;
  border-left: 4px solid #059669;
  border-bottom: none;
  padding-bottom: 0;
}

.biodata-section .section-title svg {
  color: #059669;
  opacity: 0.9;
  flex-shrink: 0;
}

.biodata-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: none;
  border-radius: 0;
  overflow: visible;
  background: transparent;
}

.biodata-item {
  display: flex;
  flex-direction: column;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: transparent;
}

.biodata-item:nth-child(odd) {
  border-right: none;
}

.biodata-item:nth-last-child(-n+2) {
  border-bottom: none;
}

.biodata-item .label {
  padding: 10px 0 4px 0;
  background: transparent;
  color: #64748b;
  font-size: 12px;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  border: none;
}

.biodata-item .value {
  padding: 0 0 14px 0;
  color: #0f172a;
  font-size: 14px;
  font-weight: 500;
  border: none;
  line-height: 1.4;
}

.biodata-item:last-child .value,
.biodata-item:nth-last-child(2) .value {
  padding-bottom: 0;
}

.biodata-item.full-width {
  grid-column: 1 / -1;
}

.biodata-item .value.hint {
  color: #64748b;
  font-size: 13px;
  font-weight: 400;
}

.permission-tag {
  display: inline-block;
  margin: 2px 4px 2px 0;
  padding: 4px 10px;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 6px;
  font-size: 12px;
}

/* Education Section Styles */
.education-section {
  margin-top: 20px;
}

.form-modal-body .education-section {
  margin-top: 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.form-modal-body .section-header {
  margin-bottom: 18px;
}

.section-header h4 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.form-modal-body .section-header h4 {
  font-size: 16px;
  color: #334155;
}

.btn-add-education {
  padding: 10px 16px;
  background: #059669;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.btn-add-education:hover {
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.education-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.education-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.education-item-header h5 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.btn-remove-education {
  padding: 6px 10px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-remove-education:hover {
  background: #dc2626;
  transform: scale(1.05);
}

/* Documents Section Styles */
.documents-section {
  margin-top: 20px;
}

.form-modal-body .documents-section {
  margin-top: 0;
}

.documents-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-count {
  padding: 6px 12px;
  background: #d1fae5;
  color: #059669;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.upload-section {
  margin-bottom: 24px;
}

.upload-area {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 32px;
  text-align: center;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.form-modal-body .upload-area {
  border-radius: 12px;
  padding: 28px;
  background: #fafbfc;
}

.upload-area:hover {
  border-color: #059669;
  background: #f0f4ff;
}

.form-modal-body .upload-area:hover {
  border-color: #059669;
  background: #ecfdf5;
}

.upload-label {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  color: #059669;
  font-weight: 600;
  transition: all 0.2s ease;
}

.upload-label.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.upload-label svg {
  color: #059669;
}

.upload-hint {
  margin-top: 12px;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 0;
}

.info-box {
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  color: #92400e;
  margin-bottom: 24px;
}

.form-modal-body .info-box {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  padding: 14px 18px;
  margin-top: 16px;
  margin-bottom: 0;
  text-align: left;
}

.info-box p {
  margin: 0;
  font-size: 14px;
}

.form-modal-body .info-box p {
  font-size: 13px;
}

.module-access {
  margin-top: 16px;
}

.form-modal-body .module-access {
  margin-top: 20px;
  padding: 18px 20px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.module-access-grid {
  margin-top: 12px;
}
.module-access-grid h5 {
  margin: 0 0 8px 0;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.form-modal-body .module-access-grid h5 {
  font-size: 13px;
  color: #64748b;
}

.module-access h4 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
}

.form-modal-body .module-access h4 {
  font-size: 15px;
  color: #334155;
  margin-bottom: 6px;
}

.module-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
}

.form-modal-body .module-grid {
  gap: 8px;
}

.module-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  font-size: 13px;
  color: #334155;
}

.form-modal-body .module-option {
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  transition: border-color 0.2s ease;
}

.form-modal-body .module-option:hover {
  border-color: #cbd5e1;
}

.module-option input {
  accent-color: #059669;
}

.form-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #64748b;
}

.form-modal-body .form-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

.documents-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.document-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.document-item:hover {
  border-color: #059669;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.1);
}

.document-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.document-info svg {
  color: #ef4444;
  flex-shrink: 0;
}

.document-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.document-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 14px;
}

.document-meta {
  font-size: 12px;
  color: #64748b;
}

.document-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-download,
.btn-download-view {
  padding: 8px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-download:hover,
.btn-download-view:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-delete-doc {
  padding: 8px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-delete-doc:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.empty-documents {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}

.empty-documents svg {
  color: #cbd5e1;
  margin-bottom: 12px;
}

.empty-documents p {
  margin: 0;
  font-size: 14px;
}

/* Education View Styles */
.educations-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.educations-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.education-view-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
}

.education-view-header {
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e2e8f0;
}

.education-view-header h5 {
  font-size: 15px;
  font-weight: 600;
  color: #475569;
  margin: 0;
}

/* Documents View Styles */
.documents-view-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.document-view-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.document-view-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.document-view-info svg {
  color: #ef4444;
  flex-shrink: 0;
}

.document-view-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.document-view-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 14px;
}

.document-view-meta {
  font-size: 12px;
  color: #64748b;
}

/* Action Buttons Group */
.action-buttons-group {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-compact {
  padding: 8px 16px !important;
  font-size: 13px !important;
  gap: 6px !important;
  border-radius: 10px;
}

.btn-compact svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* Responsive Styles */
@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .action-buttons-group {
    width: 100%;
    flex-wrap: wrap;
  }

  .filters {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
    min-width: auto;
  }
}

@media (max-width: 768px) {
  .page-header {
    margin-bottom: 16px;
  }

  .header-content .page-subtitle {
    font-size: 13px;
  }

  .filters {
    padding: 16px;
    margin-bottom: 16px;
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

  .form-modal-content {
    width: 95%;
    max-width: 95%;
    max-height: 90vh;
    border-radius: 20px;
  }

  .form-modal-header {
    padding: 20px 20px;
  }

  .form-modal-icon {
    width: 42px;
    height: 42px;
  }

  .form-modal-title {
    font-size: 18px;
  }

  .form-modal-body .form-tabs-nav {
    margin-left: 0;
    margin-right: 0;
    margin-bottom: 24px;
    padding: 0;
  }

  .form-tab-btn {
    padding: 8px 12px;
    font-size: 12px;
  }

  .form-tab-btn svg {
    width: 14px;
    height: 14px;
  }

  .form-tab-content {
    padding: 0;
  }

  .form-modal-body .form-row {
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 16px;
  }

  .form-modal-footer {
    padding: 14px 20px 20px;
    flex-direction: column;
    align-items: stretch;
  }

  .form-modal-footer-actions {
    justify-content: flex-end;
  }

  .form-modal-body {
    padding: 20px;
  }

  .modal-body {
    padding: 20px;
  }

  .form-grid {
    grid-template-columns: 1fr !important;
  }
}

@media (max-width: 480px) {

  .action-buttons-group {
    flex-direction: column;
  }

  .action-buttons-group button,
  .action-buttons-group label {
    width: 100%;
    justify-content: center;
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

  .form-modal-content {
    width: 100%;
    max-width: 100%;
    max-height: 100vh;
    border-radius: 0;
  }

  .form-modal-header {
    padding: 16px 16px;
  }

  .form-modal-title-wrap {
    gap: 12px;
  }

  .form-modal-icon {
    width: 40px;
    height: 40px;
  }

  .form-modal-title {
    font-size: 17px;
  }

  .form-modal-subtitle {
    font-size: 12px;
  }

  .form-modal-footer {
    padding: 12px 16px 16px;
  }

  .form-modal-body {
    padding: 16px;
  }

  .modal-body {
    padding: 16px;
  }
}
</style>
