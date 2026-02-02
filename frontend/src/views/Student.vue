<template>
  <Layout>
    <div class="student-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Data Siswa</h2>
            <p>Kelola data siswa sekolah Anda</p>
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
            <label for="import-excel" class="btn-secondary btn-compact" style="cursor: pointer;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Import</span>
            </label>
            <input type="file" id="import-excel" accept=".xlsx,.xls" style="display: none;" @change="handleImportExcel">
            <button @click="showAddModal = true" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Siswa</span>
            </button>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <input 
          v-model="filters.search" 
          @input="loadStudents" 
          placeholder="Cari nama, NIK, NIS, NISN..."
          class="search-input"
        />
        <select v-model="filters.class" @change="loadStudents" class="filter-select">
          <option value="">Semua Kelas</option>
          <option value="1">Kelas 1</option>
          <option value="2">Kelas 2</option>
          <option value="3">Kelas 3</option>
          <option value="4">Kelas 4</option>
          <option value="5">Kelas 5</option>
          <option value="6">Kelas 6</option>
        </select>
        <select v-model="filters.status" @change="loadStudents" class="filter-select">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Lulus">Lulus</option>
          <option value="Pindah">Pindah</option>
          <option value="Drop Out">Drop Out</option>
          <option value="Tidak Aktif">Tidak Aktif</option>
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
      
      <div v-else class="content-wrapper">
        <!-- Desktop: table (hidden when empty) -->
        <div v-if="students.length > 0" class="table-container table-desktop">
          <table class="data-table">
            <thead>
              <tr>
                <th>NIK</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.id">
                <td>{{ student.nik || '-' }}</td>
                <td>{{ student.nis || '-' }}</td>
                <td>{{ student.nisn || '-' }}</td>
                <td>{{ student.name }}</td>
                <td>{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                <td>{{ student.class || '-' }}</td>
                <td>
                  <span :class="getStatusClass(student.status)">
                    {{ student.status }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="viewStudent(student)" class="btn-action btn-view" title="Lihat Biodata">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="editStudent(student)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteStudent(student.id)" class="btn-action btn-delete" title="Hapus">
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
        </div>

        <!-- Mobile: cards (hidden when empty) -->
        <div v-if="students.length > 0" class="student-cards table-mobile">
          <div
            v-for="student in students"
            :key="student.id"
            class="student-card"
          >
            <div class="student-card-main">
              <h3 class="student-card-name">{{ student.name }}</h3>
              <div class="student-card-meta">
                <span v-if="student.nis || student.nisn" class="student-card-id">
                  {{ student.nis ? `NIS: ${student.nis}` : '' }}{{ student.nis && student.nisn ? ' · ' : '' }}{{ student.nisn ? `NISN: ${student.nisn}` : '' }}
                </span>
                <span v-else class="student-card-id">NIK: {{ student.nik || '-' }}</span>
                <span class="student-card-class">{{ student.class || '-' }}</span>
              </div>
              <span :class="['student-card-status', getStatusClass(student.status)]">
                {{ student.status }}
              </span>
            </div>
            <div class="student-card-actions">
              <button @click="viewStudent(student)" class="btn-action btn-view" title="Lihat">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="editStudent(student)" class="btn-action btn-edit" title="Edit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="deleteStudent(student.id)" class="btn-action btn-delete" title="Hapus">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-if="students.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada data siswa</h3>
          <p>Mulai dengan menambahkan siswa baru</p>
          <button @click="showAddModal = true" class="btn-secondary btn-compact btn-add">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Siswa</span>
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ showEditModal ? 'Edit' : 'Tambah' }} Siswa</h3>
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
                <span>Tambahan</span>
              </button>
              <button 
                type="button"
                @click="activeTab = 3" 
                :class="['tab-btn', { active: activeTab === 3 }]"
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
                :class="['tab-btn', { active: activeTab === 4 }]"
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
                :class="['tab-btn', { active: activeTab === 5 }]"
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
                :class="['tab-btn', { active: activeTab === 6 }]"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Dokumen</span>
              </button>
            </div>

            <!-- Tab 1: Identitas -->
            <div v-show="activeTab === 1" class="tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>NIK *</label>
                  <input v-model="form.nik" required />
                </div>
                <div class="form-group">
                  <label>NISN</label>
                  <input v-model="form.nisn" />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>NIS</label>
                  <input v-model="form.nis" />
                </div>
                <div class="form-group">
                  <label>Nama Lengkap *</label>
                  <input v-model="form.name" required />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Jenis Kelamin *</label>
                  <select v-model="form.gender" required>
                    <option value="">Pilih</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Tempat Lahir *</label>
                  <input v-model="form.birth_place" required />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Tanggal Lahir *</label>
                  <input type="date" v-model="form.birth_date" required />
                </div>
              </div>
            </div>

            <!-- Tab 2: Tambahan -->
            <div v-show="activeTab === 2" class="tab-content">
              <div class="form-row">
                <div class="form-group">
                  <label>No KK</label>
                  <input v-model="form.no_kk" />
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
            </div>

            <!-- Tab 3: Data Ayah Kandung -->
            <div v-show="activeTab === 3" class="tab-content">
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
            <div v-show="activeTab === 4" class="tab-content">
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
            <div v-show="activeTab === 5" class="tab-content">
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
            <div v-show="activeTab === 6" class="tab-content">
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

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button v-if="activeTab > 1" type="button" @click="activeTab--" class="btn-secondary">Sebelumnya</button>
              <button v-if="activeTab < 6" type="button" @click="activeTab++" class="btn-secondary">Selanjutnya</button>
              <button v-if="activeTab !== 6" type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
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
              <button @click="printPDF" class="btn-print" title="Cetak PDF">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cetak PDF</span>
              </button>
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
                  <span class="label">Tahun Ajaran</span>
                  <span class="value">{{ viewingStudent.academic_year || '-' }}</span>
                </div>
                <div class="biodata-item">
                  <span class="label">Status</span>
                  <span class="value" :class="getStatusClass(viewingStudent.status)">{{ viewingStudent.status || '-' }}</span>
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
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeViewModal" class="btn-secondary">Tutup</button>
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
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { studentApi } from '@/api/student'
import { institutionApi } from '@/api/institution'
import { validators } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { getInstitutionTypeLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import * as XLSX from 'xlsx'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const students = ref([])
const loading = ref(true)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const viewingStudent = ref(null)
const saving = ref(false)
const deleteLoading = ref(false)
const error = ref('')
const activeTab = ref(1)

const filters = ref({
  search: '',
  class: '',
  status: ''
})

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
  class: '',
  academic_year: '',
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

const documentForm = ref({
  name: '',
  description: ''
})
const selectedFile = ref(null)
const uploadingDocument = ref(false)
const currentStudentDocuments = ref([])
const fileInput = ref(null)

let editingId = null

const loadStudents = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.class) params.class = filters.value.class
    if (filters.value.status) params.status = filters.value.status
    
    const response = await studentApi.getAll(params)
    students.value = response.data.data || []
  } catch (err) {
    error.value = 'Gagal memuat data siswa'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const editStudent = async (student) => {
  editingId = student.id
  Object.assign(form.value, student)
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
    warning: 'Data siswa akan dihapus secara permanen dan tidak dapat dikembalikan.'
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
    if (editingId) {
      await studentApi.update(editingId, form.value)
      toast.success('Berhasil', 'Data siswa berhasil diperbarui')
    } else {
      await studentApi.create(form.value)
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
    class: '',
    academic_year: '',
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

const getStatusClass = (status) => {
  const classes = {
    'Aktif': 'status-active',
    'Lulus': 'status-success',
    'Pindah': 'status-warning',
    'Drop Out': 'status-danger',
    'Tidak Aktif': 'status-inactive'
  }
  return classes[status] || ''
}

const viewStudent = (student) => {
  viewingStudent.value = { ...student }
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  viewingStudent.value = null
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
    // Ambil semua data siswa tanpa pagination
    const params = { per_page: 10000 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.class) params.class = filters.value.class
    if (filters.value.status) params.status = filters.value.status
    
    const response = await studentApi.getAll(params)
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
        'NIK': '1234567890123456',
        'NIS': '2024001',
        'NISN': '0012345678',
        'Nama Lengkap': 'Ahmad Fauzi',
        'Jenis Kelamin': 'L',
        'Tempat Lahir': 'Jakarta',
        'Tanggal Lahir': '2010-01-15',
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
        'Kelas': '1',
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
    
    // Buat workbook
    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)
    
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
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Siswa')
    
    // Download file
    const fileName = `Template_Import_Siswa.xlsx`
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
      
      // Parse status
      const parseStatus = (val) => {
        if (!val) return null
        const str = String(val).toLowerCase()
        if (str.includes('hidup') || str === 'masih hidup') return 'masih_hidup'
        if (str.includes('meninggal') || str === 'meninggal dunia') return 'meninggal_dunia'
        if (str.includes('tidak') || str === 'tidak diketahui') return 'tidak_diketahui'
        return null
      }
      
      // Parse jenis tempat tinggal
      const parseResidenceType = (val) => {
        if (!val) return null
        const str = String(val).toLowerCase()
        if (str.includes('asrama')) return 'asrama'
        if (str.includes('kost') || str.includes('kontrak')) return 'kost_kontrak'
        if (str.includes('orang tua') || str.includes('tinggal')) return 'tinggal_dengan_orang_tua'
        return 'lainnya'
      }
      
      // Parse jenis wali
      const parseGuardianType = (val) => {
        if (!val) return null
        const str = String(val).toLowerCase()
        if (str.includes('ayah')) return 'sama_dengan_ayah'
        if (str.includes('ibu')) return 'sama_dengan_ibu'
        return 'lainnya'
      }
      
      return {
        nik: mapField('NIK', 'nik'),
        nis: mapField('NIS', 'nis'),
        nisn: mapField('NISN', 'nisn'),
        name: mapField('Nama Lengkap', 'name'),
        gender: parseGender(mapField('Jenis Kelamin', 'gender')),
        birth_place: mapField('Tempat Lahir', 'birth_place'),
        birth_date: parseDate(mapField('Tanggal Lahir', 'birth_date')),
        address: mapField('Alamat', 'address'),
        phone: mapField('No. Telepon', 'phone'),
        email: mapField('Email', 'email'),
        religion: mapField('Agama', 'religion'),
        no_kk: mapField('No. KK', 'no_kk'),
        aspiration: mapField('Cita-cita', 'aspiration'),
        hobby: mapField('Hobi', 'hobby'),
        disability: mapField('Disabilitas', 'disability'),
        height: mapField('Tinggi Badan (cm)', 'height') ? parseFloat(mapField('Tinggi Badan (cm)', 'height')) : null,
        weight: mapField('Berat Badan (kg)', 'weight') ? parseFloat(mapField('Berat Badan (kg)', 'weight')) : null,
        previous_school: mapField('Sekolah Sebelumnya', 'previous_school'),
        residence_type: parseResidenceType(mapField('Jenis Tempat Tinggal', 'residence_type')),
        class: mapField('Kelas', 'class'),
        academic_year: mapField('Tahun Ajaran', 'academic_year'),
        status: mapField('Status', 'status') || 'Aktif',
        father_name: mapField('Nama Ayah', 'father_name'),
        father_status: parseStatus(mapField('Status Ayah', 'father_status')),
        father_nik: mapField('NIK Ayah', 'father_nik'),
        father_birth_place: mapField('Tempat Lahir Ayah', 'father_birth_place'),
        father_birth_date: parseDate(mapField('Tanggal Lahir Ayah', 'father_birth_date')),
        father_education: mapField('Pendidikan Ayah', 'father_education'),
        father_occupation: mapField('Pekerjaan Ayah', 'father_occupation'),
        father_income: mapField('Penghasilan Ayah', 'father_income') ? parseFloat(mapField('Penghasilan Ayah', 'father_income')) : null,
        mother_name: mapField('Nama Ibu', 'mother_name'),
        mother_status: parseStatus(mapField('Status Ibu', 'mother_status')),
        mother_nik: mapField('NIK Ibu', 'mother_nik'),
        mother_birth_place: mapField('Tempat Lahir Ibu', 'mother_birth_place'),
        mother_birth_date: parseDate(mapField('Tanggal Lahir Ibu', 'mother_birth_date')),
        mother_education: mapField('Pendidikan Ibu', 'mother_education'),
        mother_occupation: mapField('Pekerjaan Ibu', 'mother_occupation'),
        mother_income: mapField('Penghasilan Ibu', 'mother_income') ? parseFloat(mapField('Penghasilan Ibu', 'mother_income')) : null,
        guardian_name: mapField('Nama Wali', 'guardian_name'),
        guardian_phone: mapField('No. Telepon Wali', 'guardian_phone'),
        guardian_type: parseGuardianType(mapField('Jenis Wali', 'guardian_type')),
        guardian_status: parseStatus(mapField('Status Wali', 'guardian_status')),
        guardian_nik: mapField('NIK Wali', 'guardian_nik'),
        guardian_birth_place: mapField('Tempat Lahir Wali', 'guardian_birth_place'),
        guardian_birth_date: parseDate(mapField('Tanggal Lahir Wali', 'guardian_birth_date')),
        guardian_education: mapField('Pendidikan Wali', 'guardian_education'),
        guardian_occupation: mapField('Pekerjaan Wali', 'guardian_occupation'),
        guardian_income: mapField('Penghasilan Wali', 'guardian_income') ? parseFloat(mapField('Penghasilan Wali', 'guardian_income')) : null,
        notes: mapField('Catatan', 'notes')
      }
    })
    
    // Filter data yang valid (minimal harus ada NIK dan Nama)
    const validData = mappedData.filter(item => item.nik && item.name)
    
    if (validData.length === 0) {
      toast.error('Gagal', 'Tidak ada data valid yang dapat diimpor. Pastikan kolom NIK dan Nama Lengkap terisi.')
      return
    }
    
    // Kirim ke backend
    const response = await studentApi.import(validData)
    
    toast.success('Berhasil', `Berhasil mengimpor ${response.data.success_count || validData.length} data siswa`)
    loadStudents()
    
    // Reset input file
    event.target.value = ''
  } catch (err) {
    console.error(err)
    toast.error('Gagal', err.formattedMessage || 'Gagal mengimpor data dari Excel')
  } finally {
    loading.value = false
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
    const principalLabel = `Kepala ${getInstitutionTypeLabel(institution?.level) || 'Sekolah/Madrasah'}`
    
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
        .kop {
          border-bottom: 3px solid #000;
          padding-bottom: 12px;
          margin-bottom: 20px;
          text-align: center;
        }
        .kop-header {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 20px;
          margin-bottom: 12px;
        }
        .kop-logo {
          max-width: 80px;
          max-height: 80px;
          object-fit: contain;
        }
        .kop-name {
          font-size: 18px;
          font-weight: bold;
          margin-bottom: 4px;
          text-transform: uppercase;
          letter-spacing: 1px;
          line-height: 1.2;
        }
        .kop-address {
          font-size: 12px;
          margin-bottom: 6px;
          line-height: 1.3;
        }
        .kop-info {
          font-size: 11px;
          margin-top: 6px;
          display: flex;
          justify-content: center;
          gap: 20px;
          flex-wrap: wrap;
          line-height: 1.2;
        }
        .kop-info-item {
          display: flex;
          gap: 5px;
        }
        .kop-info-label {
          font-weight: bold;
        }
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
        <div class="kop">
          <div class="kop-header">
            ${institution.logo ? `<img src="${institution.logo}" alt="Logo Sekolah" class="kop-logo" />` : ''}
            <div style="flex: 1;">
              <div class="kop-name">${institution.name || 'NAMA LEMBAGA'}</div>
              <div class="kop-address">${fullAddress}</div>
            </div>
          </div>
          <div class="kop-info">
            <div class="kop-info-item">
              <span class="kop-info-label">NPSN:</span>
              <span>${institution.npsn || '-'}</span>
            </div>
            <div class="kop-info-item">
              <span class="kop-info-label">No. Statistik:</span>
              <span>${institution.nss || '-'}</span>
            </div>
          </div>
        </div>
        
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
  loadStudents()
})
</script>

<style scoped>
.student-page {
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
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
  color: #64748b;
  font-size: 13px;
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

.content-wrapper {
  position: relative;
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
  color: #3b82f6;
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
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
  color: #667eea;
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
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(102, 126, 234, 0.15);
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
  border-color: #667eea;
  color: #667eea;
  background: rgba(102, 126, 234, 0.08);
}

.btn-add:hover {
  background: rgba(102, 126, 234, 0.15);
  border-color: #667eea;
  color: #5a67d8;
}

.btn-primary {
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  color: #667eea;
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

  .header-content h2 {
    font-size: 22px;
  }

  .header-content p {
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

  .modal-header {
    padding: 20px 20px;
  }

  .modal-header h3 {
    font-size: 20px;
  }

  .modal-body {
    padding: 20px;
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

  .header-content h2 {
    font-size: 20px;
    line-height: 1.25;
  }

  .header-content p {
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
