<template>
  <Layout>
    <div class="teacher-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Data Guru</h2>
            <p>Kelola data guru sekolah Anda</p>
          </div>
          <div class="action-buttons-group">
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
            <label for="import-excel-employee" class="btn-secondary btn-compact" style="cursor: pointer;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Import</span>
            </label>
            <input type="file" id="import-excel-employee" accept=".xlsx,.xls" style="display: none;" @change="handleImportExcel">
            <button @click="showAddModal = true" class="btn-primary btn-compact">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Guru</span>
            </button>
          </div>
        </div>
      </div>

      <div class="filters">
        <input 
          v-model="filters.search" 
          @input="loadTeachers" 
          placeholder="Cari nama, NIP, atau NUPTK..."
          class="search-input"
        />
        <select v-model="filters.status" @change="loadTeachers" class="filter-select">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Pensiun">Pensiun</option>
          <option value="Pindah">Pindah</option>
          <option value="Tidak Aktif">Tidak Aktif</option>
        </select>
        <select v-model="filters.type" @change="loadTeachers" class="filter-select">
          <option value="">Semua Tipe</option>
          <option value="Guru">Guru</option>
          <option value="Staff">Staff</option>
          <option value="Tenaga Administrasi">Tenaga Administrasi</option>
          <option value="Tenaga Kebersihan">Tenaga Kebersihan</option>
          <option value="Tenaga Keamanan">Tenaga Keamanan</option>
          <option value="Lainnya">Lainnya</option>
        </select>
        <select v-model="filters.employment_status" @change="loadTeachers" class="filter-select">
          <option value="">Semua Status Kepegawaian</option>
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

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
              <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
              <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
            </circle>
          </svg>
        </div>
        <p>Memuat data...</p>
      </div>
      
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tipe</th>
              <th>NIP</th>
              <th>NUPTK</th>
              <th>Nama</th>
              <th>Jenis Kelamin</th>
              <th>Status Kepegawaian</th>
              <th>Mata Pelajaran</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="teacher in teachers" :key="teacher.id">
              <td>{{ teacher.type || 'Guru' }}</td>
              <td>{{ teacher.nip || '-' }}</td>
              <td>{{ teacher.nuptk || '-' }}</td>
              <td>{{ teacher.name }}</td>
              <td>{{ teacher.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
              <td>{{ teacher.employment_status || '-' }}</td>
              <td>{{ teacher.subject || '-' }}</td>
              <td>
                <span :class="getStatusClass(teacher.status)">
                  {{ teacher.status }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="viewTeacher(teacher)" class="btn-action btn-view" title="Lihat Biodata">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="editTeacher(teacher)" class="btn-action btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="deleteTeacher(teacher.id)" class="btn-action btn-delete" title="Hapus">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="teachers.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada data guru</h3>
          <p>Mulai dengan menambahkan guru baru</p>
          <button @click="showAddModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Guru</span>
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ showEditModal ? 'Edit' : 'Tambah' }} Guru</h3>
            <button @click="closeModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleSubmit" class="modal-body">
            <!-- Tabs Navigation -->
            <div class="tabs-nav">
              <button 
                type="button"
                @click="activeTab = 1" 
                :class="['tab-btn', { active: activeTab === 1 }]"
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
                :class="['tab-btn', { active: activeTab === 2 }]"
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
                :class="['tab-btn', { active: activeTab === 3 }]"
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
                :class="['tab-btn', { active: activeTab === 4 }]"
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
                :class="['tab-btn', { active: activeTab === 5 }]"
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
            <div v-show="activeTab === 1" class="tab-content">
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
                  <label>NIP</label>
                  <input v-model="form.nip" />
                </div>
              </div>

              <div class="form-row">
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
            </div>

            <!-- Tab 2: Kepegawaian -->
            <div v-show="activeTab === 2" class="tab-content">
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
            </div>

            <!-- Tab 3: Tambahan -->
            <div v-show="activeTab === 3" class="tab-content">
              <div class="form-group">
                <label>Catatan</label>
                <textarea v-model="form.notes" rows="5"></textarea>
              </div>
            </div>

            <!-- Tab 4: Pendidikan -->
            <div v-show="activeTab === 4" class="tab-content">
              <div class="education-section">
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
            </div>

            <!-- Tab 5: Berkas -->
            <div v-show="activeTab === 5" class="tab-content">
              <div class="documents-section">
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
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button v-if="activeTab > 1" type="button" @click="activeTab--" class="btn-secondary">Sebelumnya</button>
              <button v-if="activeTab < 5" type="button" @click="activeTab++" class="btn-secondary">Selanjutnya</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Teacher Modal -->
      <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
        <div class="modal-content view-modal" @click.stop>
          <div class="modal-header">
            <h3>Biodata Lengkap Guru</h3>
            <button @click="closeViewModal" class="btn-close">×</button>
          </div>
          
          <div class="view-body" v-if="viewingTeacher">
            <!-- Identitas Guru -->
            <div class="biodata-section">
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
            <div class="biodata-section">
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

            <!-- Riwayat Pendidikan -->
            <div class="biodata-section" v-if="viewingTeacher.educations && viewingTeacher.educations.length > 0">
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
            <div class="biodata-section" v-if="viewingTeacher.documents && viewingTeacher.documents.length > 0">
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

            <!-- Catatan -->
            <div class="biodata-section" v-if="viewingTeacher.notes">
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
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { employeeApi } from '@/api/teacher'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'
import * as XLSX from 'xlsx'

const toast = useToast()

const teachers = ref([])
const loading = ref(true)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const viewingTeacher = ref(null)
const activeTab = ref(1)
const saving = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  status: '',
  type: '',
  employment_status: ''
})

const form = ref({
  type: 'Guru',
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

let editingId = null

const loadTeachers = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.employment_status) params.employment_status = filters.value.employment_status
    
    const response = await employeeApi.getAll(params)
    teachers.value = response.data.data || []
  } catch (err) {
    error.value = 'Gagal memuat data guru'
    console.error(err)
  } finally {
    loading.value = false
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
  editingId = teacher.id
  activeTab.value = 1
  
  // Load full employee data with educations and documents
  try {
    const response = await employeeApi.get(teacher.id)
    const fullData = response.data.data
    
    Object.assign(form.value, fullData)
    if (fullData.birth_date) {
      form.value.birth_date = fullData.birth_date.split('T')[0]
    }
    if (fullData.join_date) {
      form.value.join_date = fullData.join_date.split('T')[0]
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
  if (!confirm('Apakah Anda yakin ingin menghapus guru ini?')) return
  
  try {
    await employeeApi.delete(id)
    toast.success('Berhasil', 'Guru berhasil dihapus')
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus guru')
  }
}

const validationRules = {
  name: [
    (value) => validators.required(value, 'Nama lengkap wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama maksimal 255 karakter')
  ],
  gender: [
    (value) => validators.required(value, 'Jenis kelamin wajib diisi')
  ],
  email: [
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

const handleSubmit = async () => {
  error.value = ''
  
  // Validate form
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    error.value = 'Mohon perbaiki kesalahan pada form: ' + Object.values(validation.errors).join(', ')
    return
  }
  
  saving.value = true
  
  try {
    if (editingId) {
      await employeeApi.update(editingId, form.value)
      toast.success('Berhasil', 'Data guru berhasil diperbarui')
    } else {
      await employeeApi.create(form.value)
      toast.success('Berhasil', 'Guru berhasil ditambahkan')
    }
    closeModal()
    loadTeachers()
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan data'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
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
    type: 'Guru',
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

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
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

  if (!editingId) {
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

    const response = await employeeApi.uploadDocument(editingId, formData)

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
  if (!confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) return

  if (!editingId) {
    toast.error('Gagal', 'ID pegawai tidak ditemukan')
    return
  }

  try {
    saving.value = true
    await employeeApi.deleteDocument(editingId, documentId)
    
    // Remove from form
    form.value.documents.splice(index, 1)
    
    toast.success('Berhasil', 'Dokumen berhasil dihapus')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus dokumen')
  } finally {
    saving.value = false
  }
}

const getStatusClass = (status) => {
  const classes = {
    'Aktif': 'status-active',
    'Pensiun': 'status-success',
    'Pindah': 'status-warning',
    'Tidak Aktif': 'status-inactive'
  }
  return classes[status] || ''
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
      'Catatan': employee.notes || ''
    }))
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(excelData)
    
    // Set column widths
    const colWidths = [
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 15 }, { wch: 30 }
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
        'Catatan': ''
      }
    ]
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)
    
    // Set column widths
    const colWidths = [
      { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 30 }, { wch: 15 },
      { wch: 20 }, { wch: 15 }, { wch: 40 }, { wch: 15 }, { wch: 25 },
      { wch: 15 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
      { wch: 15 }, { wch: 15 }, { wch: 30 }
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
        notes: mapField('Catatan', 'notes')
      }
    })
    
    // Filter data yang valid (minimal harus ada Nama)
    const validData = mappedData.filter(item => item.name)
    
    if (validData.length === 0) {
      toast.error('Gagal', 'Tidak ada data valid yang dapat diimpor. Pastikan kolom Nama Lengkap terisi.')
      return
    }
    
    // Kirim ke backend (batch import)
    const response = await employeeApi.import(validData)
    
    if (response.data.success_count > 0) {
      toast.success('Berhasil', `Berhasil mengimpor ${response.data.success_count} data pegawai${response.data.error_count > 0 ? `, ${response.data.error_count} gagal` : ''}`)
      if (response.data.errors && response.data.errors.length > 0) {
        console.warn('Import errors:', response.data.errors)
      }
      loadTeachers()
    } else {
      toast.error('Gagal', 'Gagal mengimpor data pegawai')
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
  loadTeachers()
})
</script>

<style scoped>
.teacher-page {
  max-width: 1400px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
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
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  background: #3b82f6;
  color: white;
}

.btn-edit:hover {
  background: #2563eb;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
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

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
  color: #667eea;
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
  color: #667eea;
  background: #f8fafc;
  border-radius: 8px 8px 0 0;
}

.tab-btn:hover svg {
  opacity: 1;
  transform: scale(1.1);
}

.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
  font-weight: 600;
  background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05), transparent);
}

.tab-btn.active svg {
  opacity: 1;
  color: #667eea;
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

.view-modal {
  max-width: 1000px;
}

.view-body {
  padding: 30px;
  max-height: calc(90vh - 100px);
  overflow-y: auto;
}

.biodata-section {
  margin-bottom: 32px;
}

.biodata-section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.section-title svg {
  color: #667eea;
}

.biodata-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.biodata-item {
  display: flex;
  flex-direction: column;
  border-bottom: 1px solid #e2e8f0;
}

.biodata-item:nth-child(odd) {
  border-right: 1px solid #e2e8f0;
}

.biodata-item:nth-last-child(-n+2) {
  border-bottom: none;
}

.biodata-item .label {
  padding: 12px 18px;
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0;
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

/* Education Section Styles */
.education-section {
  margin-top: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-header h4 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.btn-add-education {
  padding: 10px 16px;
  background: #667eea;
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
  background: #5568d3;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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

.documents-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.doc-count {
  padding: 6px 12px;
  background: #e0e7ff;
  color: #667eea;
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

.upload-area:hover {
  border-color: #667eea;
  background: #f0f4ff;
}

.upload-label {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  color: #667eea;
  font-weight: 600;
  transition: all 0.2s ease;
}

.upload-label.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.upload-label svg {
  color: #667eea;
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

.info-box p {
  margin: 0;
  font-size: 14px;
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
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
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

.education-view-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.education-view-header {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.education-view-header h5 {
  font-size: 18px;
  font-weight: 700;
  color: #667eea;
  margin: 0;
}

/* Documents View Styles */
.documents-view-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.document-view-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
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
</style>
