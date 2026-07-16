<template>
  <Layout>
    <div class="correspondence-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Arsip Persuratan</h2>
            <p>Kelola surat masuk, keluar, dan internal</p>
          </div>
          <div class="action-buttons-group">
            <button @click="openAddModal" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Surat</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Dashboard -->
      <div v-if="statistics" class="statistics-dashboard">
        <div class="stat-card">
          <div class="stat-icon stat-total">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.summary?.total || 0 }}</div>
            <div class="stat-label">Total Surat</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-masuk">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.summary?.masuk || 0 }}</div>
            <div class="stat-label">Surat Masuk</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-keluar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.summary?.keluar || 0 }}</div>
            <div class="stat-label">Surat Keluar</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-internal">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ statistics.summary?.internal || 0 }}</div>
            <div class="stat-label">Surat Internal</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon stat-pending">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-value">{{ (statistics.pending?.approvals || 0) + (statistics.pending?.dispositions || 0) }}</div>
            <div class="stat-label">Pending</div>
          </div>
        </div>
      </div>

      <!-- Action Bar -->
      <div class="action-bar">
        <div class="action-group">
          <button @click="showAdvancedSearch = !showAdvancedSearch" class="btn-secondary btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="btn-label-full">Pencarian Lanjutan</span>
            <span class="btn-label-short">Filter</span>
          </button>
          <button @click="showExportModal = true" class="btn-secondary btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Export
          </button>
          <button @click="showImportModal = true" class="btn-secondary btn-sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M17 8L12 3L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Import
          </button>
        </div>
      </div>

      <!-- Advanced Search -->
      <div v-if="showAdvancedSearch" class="advanced-search">
        <div class="search-row">
          <div class="form-group">
            <label>Tanggal Dari</label>
            <input v-model="filters.date_from" type="date" class="form-input" @change="loadCorrespondence" />
          </div>
          <div class="form-group">
            <label>Tanggal Sampai</label>
            <input v-model="filters.date_to" type="date" class="form-input" @change="loadCorrespondence" />
          </div>
          <div class="form-group">
            <label>Jenis Surat</label>
            <select v-model="filters.letter_type_code" @change="loadCorrespondence" class="form-input">
              <option value="">Semua Jenis</option>
              <option v-for="lt in letterTypes" :key="lt.code" :value="lt.code">
                {{ lt.code }} - {{ lt.abbr }} ({{ lt.name }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Kategori</label>
            <select v-model="filters.category_id" @change="loadCorrespondence" class="form-input">
              <option value="">Semua Kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
        </div>
        <button @click="resetFilters" class="btn-secondary btn-sm">Reset Filter</button>
      </div>

      <div class="filters filters-inline">
        <input 
          v-model="filters.search" 
          @input="debounceSearch" 
          placeholder="Cari nomor surat, perihal, atau pengirim..."
          class="search-input"
        />
        <select v-model="filters.type" @change="loadCorrespondence" class="filter-select">
          <option value="">Semua Tipe</option>
          <option value="masuk">Surat Masuk</option>
          <option value="keluar">Surat Keluar</option>
          <option value="internal">Surat Internal</option>
        </select>
        <select v-model="filters.status" @change="loadCorrespondence" class="filter-select">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="pending">Menunggu Persetujuan</option>
          <option value="approved">Disetujui</option>
          <option value="sent">Terkirim</option>
          <option value="archived">Diarsipkan</option>
        </select>
        <select v-model="filters.priority" @change="loadCorrespondence" class="filter-select">
          <option value="">Semua Prioritas</option>
          <option value="biasa">Biasa</option>
          <option value="penting">Penting</option>
          <option value="sangat_penting">Sangat Penting</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="8" :cell-widths="['48px', '120px', '80px', '80px', '1fr', '140px', '100px', '90px']" />
      </div>
      
      <div v-else class="list-wrapper">
        <!-- Desktop: table -->
        <div v-if="correspondence.length > 0" class="table-container table-desktop">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th class="col-no-surat">No. Surat</th>
                <th class="col-tipe">Tipe</th>
                <th class="col-jenis">Jenis</th>
                <th class="col-perihal">Perihal</th>
                <th class="col-dari-kepada">Dari/Kepada</th>
                <th class="col-tanggal">Tanggal</th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in correspondence" :key="item.id">
                <td class="col-no">{{ rowNumber(index) }}</td>
                <td class="col-no-surat">{{ item.letter_number || item.reference_number || '-' }}</td>
                <td class="col-tipe">
                  <span :class="['badge', getTypeClass(item.type)]">
                    {{ getTypeLabel(item.type) }}
                  </span>
                </td>
                <td class="col-jenis">
                  <span v-if="item.letter_type_name" class="letter-type-badge">
                    {{ item.letter_type_code }} - {{ item.letter_type_abbr }} ({{ item.letter_type_name }})
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="col-perihal">{{ item.subject }}</td>
                <td class="col-dari-kepada">{{ item.type === 'masuk' ? (item.from || '-') : (item.to || '-') }}</td>
                <td class="col-tanggal">{{ formatDate(item.date) }}</td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <button @click="viewCorrespondence(item)" class="btn-action btn-view" title="Lihat Detail">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button v-if="item.status === 'pending'" @click="approveCorrespondence(item.id)" class="btn-action btn-approve" title="Setujui">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button v-if="item.status === 'approved'" @click="sendCorrespondence(item.id)" class="btn-action btn-send" title="Kirim">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="editCorrespondence(item)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteCorrespondence(item.id)" class="btn-action btn-delete" title="Hapus">
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

        <!-- Mobile: cards -->
        <div v-if="correspondence.length > 0" class="correspondence-cards table-mobile">
          <div v-for="item in correspondence" :key="'m-' + item.id" class="correspondence-card">
            <div class="correspondence-card-main">
              <div class="correspondence-card-top">
                <span :class="['badge', getTypeClass(item.type)]">{{ getTypeLabel(item.type) }}</span>
                <span class="correspondence-card-date">{{ formatDate(item.date) }}</span>
              </div>
              <h3 class="correspondence-card-subject">{{ item.subject || '-' }}</h3>
              <div class="correspondence-card-meta">
                <span class="correspondence-card-number">{{ item.letter_number || item.reference_number || '-' }}</span>
                <span v-if="item.letter_type_abbr" class="correspondence-card-type">{{ item.letter_type_code }} · {{ item.letter_type_abbr }}</span>
              </div>
              <p class="correspondence-card-party">
                {{ item.type === 'masuk' ? 'Dari' : 'Kepada' }}: {{ item.type === 'masuk' ? (item.from || '-') : (item.to || '-') }}
              </p>
            </div>
            <div class="correspondence-card-actions">
              <button @click="viewCorrespondence(item)" class="btn-action btn-view" title="Lihat Detail">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button v-if="item.status === 'pending'" @click="approveCorrespondence(item.id)" class="btn-action btn-approve" title="Setujui">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button v-if="item.status === 'approved'" @click="sendCorrespondence(item.id)" class="btn-action btn-send" title="Kirim">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="editCorrespondence(item)" class="btn-action btn-edit" title="Edit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="deleteCorrespondence(item.id)" class="btn-action btn-delete" title="Hapus">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="correspondence.length > 0 && correspondenceList.last_page > 1" class="pagination">
          <button 
            @click="loadCorrespondence(correspondenceList.current_page - 1)" 
            :disabled="correspondenceList.current_page === 1"
            class="pagination-btn"
          >
            Sebelumnya
          </button>
          <span class="pagination-info">
            Halaman {{ correspondenceList.current_page }} dari {{ correspondenceList.last_page }}
            (Total: {{ correspondenceList.total }} surat)
          </span>
          <button 
            @click="loadCorrespondence(correspondenceList.current_page + 1)" 
            :disabled="correspondenceList.current_page >= correspondenceList.last_page"
            class="pagination-btn"
          >
            Selanjutnya
          </button>
        </div>

        <div v-if="correspondence.length === 0 && !loading" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada surat</h3>
          <p>Belum ada surat yang terdaftar</p>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Surat' : 'Tambah Surat' }}</h3>
            <button @click="closeModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveCorrespondence" class="modal-body">
            <div class="form-group">
              <label>Tipe Surat <span class="required">*</span></label>
              <select v-model="form.type" @change="onTypeChange" required class="form-input">
                <option value="">Pilih Tipe</option>
                <option value="masuk">Surat Masuk</option>
                <option value="keluar">Surat Keluar</option>
                <option value="internal">Surat Internal</option>
              </select>
            </div>

            <div class="form-group">
              <label>Jenis Surat <span class="required">*</span></label>
              <select v-model="form.letter_type_code" required class="form-input">
                <option value="">Pilih Jenis Surat</option>
                <option v-for="lt in letterTypes" :key="lt.code" :value="lt.code">
                  {{ lt.code }} - {{ lt.abbr }} ({{ lt.name }})
                </option>
              </select>
              <p v-if="letterTypes.length === 0" class="form-hint" style="color: #ef4444; margin-top: 4px;">
                Memuat jenis surat...
              </p>
              <p v-else-if="!form.type" class="form-hint" style="color: #64748b; margin-top: 4px;">
                Pilih tipe surat terlebih dahulu
              </p>
            </div>

            <div class="form-group">
              <label>Nomor Surat</label>
              <input 
                v-model="form.letter_number" 
                type="text" 
                class="form-input" 
                :placeholder="(form.type === 'keluar' || form.type === 'internal') ? 'Akan di-generate otomatis' : 'Masukkan nomor surat'"
                :readonly="(form.type === 'keluar' || form.type === 'internal') && !editingItem"
              />
              <p v-if="(form.type === 'keluar' || form.type === 'internal') && !editingItem && form.letter_type_code && form.date" class="form-hint">
                Nomor surat akan di-generate otomatis setelah disimpan (format: KK-NNN/JS/{NPSN}/BLN/TAHUN)
              </p>
            </div>

            <div class="form-group">
              <label>Nomor Referensi</label>
              <input v-model="form.reference_number" type="text" class="form-input" placeholder="Nomor surat yang dirujuk" />
            </div>

            <div class="form-group">
              <label>Perihal <span class="required">*</span></label>
              <input v-model="form.subject" type="text" required class="form-input" placeholder="Perihal surat" />
            </div>

            <div class="form-group">
              <label v-if="form.type === 'masuk'">Dari <span class="required">*</span></label>
              <label v-else-if="form.type === 'keluar'">Kepada <span class="required">*</span></label>
              <label v-else>Kepada</label>
              <input v-if="form.type === 'masuk'" v-model="form.from" type="text" required class="form-input" placeholder="Nama pengirim" />
              <input v-else v-model="form.to" type="text" :required="form.type === 'keluar'" class="form-input" placeholder="Nama penerima" />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Surat <span class="required">*</span></label>
                <input v-model="form.date" type="date" required class="form-input" />
              </div>

              <div v-if="form.type === 'masuk'" class="form-group">
                <label>Tanggal Terima</label>
                <input v-model="form.received_date" type="date" class="form-input" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Prioritas</label>
                <select v-model="form.priority" class="form-input">
                  <option value="biasa">Biasa</option>
                  <option value="penting">Penting</option>
                  <option value="sangat_penting">Sangat Penting</option>
                </select>
              </div>

              <div class="form-group">
                <label>Kategori</label>
                <select v-model="form.category_id" class="form-input" :disabled="!form.type">
                  <option value="">Pilih Kategori (Opsional)</option>
                  <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <p v-if="!form.type" class="form-hint" style="color: #64748b; margin-top: 4px;">
                  Pilih tipe surat terlebih dahulu
                </p>
              </div>
            </div>

            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-input">
                <option value="draft">Draft</option>
                <option value="pending">Menunggu Persetujuan</option>
                <option value="approved">Disetujui</option>
                <option value="sent">Terkirim</option>
                <option value="archived">Diarsipkan</option>
              </select>
            </div>

            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="form.description" class="form-input" rows="4" placeholder="Keterangan tambahan"></textarea>
            </div>

            <div class="form-group">
              <label>File Lampiran (PDF)</label>
              <input 
                ref="fileInput"
                type="file" 
                @change="handleFileChange"
                accept=".pdf"
                class="form-input"
              />
              <p v-if="form.file_name" class="file-info">File: {{ form.file_name }}</p>
              <p class="form-hint">Format: PDF, maksimal 5MB</p>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- View Modal -->
      <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
        <div class="modal-content modal-large" @click.stop>
          <div class="modal-header">
            <h3>Detail Surat</h3>
            <button @click="closeViewModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div v-if="viewingItem" class="modal-body">
            <div class="detail-section">
              <div class="detail-row">
                <div class="detail-item">
                  <label>Tipe Surat</label>
                  <span :class="['badge', getTypeClass(viewingItem.type)]">
                    {{ getTypeLabel(viewingItem.type) }}
                  </span>
                </div>
                <div v-if="viewingItem.letter_type_name" class="detail-item">
                  <label>Jenis Surat</label>
                  <span>{{ viewingItem.letter_type_code }} - {{ viewingItem.letter_type_abbr }} ({{ viewingItem.letter_type_name }})</span>
                </div>
                <div class="detail-item">
                  <label>Status</label>
                  <span :class="['badge', getStatusClass(viewingItem.status)]">
                    {{ getStatusLabel(viewingItem.status) }}
                  </span>
                </div>
              </div>

              <div class="detail-row">
                <div class="detail-item">
                  <label>Nomor Surat</label>
                  <span>{{ viewingItem.letter_number || '-' }}</span>
                </div>
                <div class="detail-item">
                  <label>Nomor Referensi</label>
                  <span>{{ viewingItem.reference_number || '-' }}</span>
                </div>
              </div>

              <div class="detail-item">
                <label>Perihal</label>
                <span>{{ viewingItem.subject }}</span>
              </div>

              <div class="detail-row">
                <div class="detail-item">
                  <label>{{ viewingItem.type === 'masuk' ? 'Dari' : 'Kepada' }}</label>
                  <span>{{ viewingItem.type === 'masuk' ? (viewingItem.from || '-') : (viewingItem.to || '-') }}</span>
                </div>
                <div class="detail-item">
                  <label>Tanggal Surat</label>
                  <span>{{ formatDate(viewingItem.date) }}</span>
                </div>
              </div>

              <div v-if="viewingItem.type === 'masuk' && viewingItem.received_date" class="detail-item">
                <label>Tanggal Terima</label>
                <span>{{ formatDate(viewingItem.received_date) }}</span>
              </div>

              <div class="detail-row">
                <div class="detail-item">
                  <label>Prioritas</label>
                  <span :class="['badge', getPriorityClass(viewingItem.priority)]">
                    {{ getPriorityLabel(viewingItem.priority) }}
                  </span>
                </div>
                <div v-if="viewingItem.category" class="detail-item">
                  <label>Kategori</label>
                  <span>{{ viewingItem.category.name }}</span>
                </div>
              </div>

              <div v-if="viewingItem.description" class="detail-item">
                <label>Keterangan</label>
                <span>{{ viewingItem.description }}</span>
              </div>

              <div v-if="viewingItem.file_name" class="detail-item">
                <label>File Lampiran</label>
                <a :href="getFileUrl(viewingItem)" target="_blank" class="file-link">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  {{ viewingItem.file_name }}
                </a>
              </div>

              <div v-if="viewingItem.institution" class="detail-item">
                <label>Instansi</label>
                <span>{{ viewingItem.institution.name }}</span>
              </div>

              <!-- Attachments Section -->
              <div class="attachments-section">
                <div class="section-header">
                  <h3>Lampiran</h3>
                  <button 
                    v-if="viewingItem && viewingItem.status !== 'archived'"
                    @click="openAttachmentModal" 
                    class="btn-primary btn-sm"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Tambah Lampiran
                  </button>
                </div>

                <div v-if="loadingAttachments" class="loading-state-small">
                  <p>Memuat lampiran...</p>
                </div>

                <div v-else-if="attachments.length === 0" class="empty-state-small">
                  <p>Belum ada lampiran</p>
                </div>

                <div v-else class="attachments-list">
                  <div 
                    v-for="attachment in attachments" 
                    :key="attachment.id" 
                    class="attachment-item"
                  >
                    <div class="attachment-info">
                      <div class="attachment-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                      <div class="attachment-details">
                        <div class="attachment-name">{{ attachment.file_name }}</div>
                        <div class="attachment-meta">
                          <span>{{ attachment.file_size_human }}</span>
                          <span v-if="attachment.mime_type"> • {{ attachment.mime_type }}</span>
                          <span> • {{ formatDate(attachment.created_at) }}</span>
                        </div>
                        <div v-if="attachment.description" class="attachment-description">
                          {{ attachment.description }}
                        </div>
                      </div>
                    </div>
                    <div class="attachment-actions">
                      <a 
                        :href="attachment.file_url" 
                        target="_blank"
                        class="btn-action btn-download"
                        title="Download"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </a>
                      <button 
                        v-if="viewingItem && viewingItem.status !== 'archived'"
                        @click="deleteAttachment(attachment.id)"
                        class="btn-action btn-delete"
                        title="Hapus"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Dispositions Section -->
              <div class="dispositions-section">
                <div class="section-header">
                  <h3>Disposisi</h3>
                  <button 
                    v-if="viewingItem && viewingItem.status !== 'archived'"
                    @click="openDispositionModal" 
                    class="btn-primary btn-sm"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Tambah Disposisi
                  </button>
                </div>

                <div v-if="loadingDispositions" class="loading-state-small">
                  <p>Memuat disposisi...</p>
                </div>

                <div v-else-if="dispositions.length === 0" class="empty-state-small">
                  <p>Belum ada disposisi</p>
                </div>

                <div v-else class="dispositions-list">
                  <div 
                    v-for="disposition in dispositions" 
                    :key="disposition.id" 
                    class="disposition-item"
                    :class="{ 'completed': disposition.status === 'completed' }"
                  >
                    <div class="disposition-header">
                      <div class="disposition-from-to">
                        <span class="disposition-label">Dari:</span>
                        <strong>{{ disposition.from_user?.name || '-' }}</strong>
                        <span class="disposition-separator">→</span>
                        <span class="disposition-label">Kepada:</span>
                        <strong>{{ disposition.to_user?.name || '-' }}</strong>
                      </div>
                      <span :class="['badge', disposition.status === 'completed' ? 'badge-success' : 'badge-warning']">
                        {{ disposition.status === 'completed' ? 'Selesai' : 'Pending' }}
                      </span>
                    </div>
                    <div class="disposition-instruction">
                      <strong>Instruksi:</strong>
                      <p>{{ disposition.instruction }}</p>
                    </div>
                    <div class="disposition-footer">
                      <span class="disposition-date">
                        Dibuat: {{ formatDate(disposition.created_at) }}
                      </span>
                      <span v-if="disposition.completed_at" class="disposition-date">
                        Selesai: {{ formatDate(disposition.completed_at) }}
                      </span>
                      <div class="disposition-actions">
                        <button 
                          v-if="disposition.status === 'pending' && disposition.to_user_id === currentUserId"
                          @click="completeDisposition(disposition.id)"
                          class="btn-action btn-complete"
                          title="Selesaikan"
                        >
                          Selesaikan
                        </button>
                        <button 
                          v-if="disposition.status === 'pending' && disposition.from_user_id === currentUserId"
                          @click="editDisposition(disposition)"
                          class="btn-action btn-edit"
                          title="Edit"
                        >
                          Edit
                        </button>
                        <button 
                          v-if="disposition.status === 'pending' && disposition.from_user_id === currentUserId"
                          @click="deleteDisposition(disposition.id)"
                          class="btn-action btn-delete"
                          title="Hapus"
                        >
                          Hapus
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Attachment Modal -->
    <div v-if="showAttachmentModal" class="modal-overlay" @click="closeAttachmentModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Tambah Lampiran</h3>
          <button @click="closeAttachmentModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>File Lampiran <span class="required">*</span></label>
            <input 
              ref="attachmentFileInput"
              type="file" 
              multiple
              @change="handleAttachmentFilesChange"
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
              class="form-input"
            />
            <p class="form-hint">Pilih satu atau lebih file (maksimal 10 file, masing-masing maksimal 10MB)</p>
            <p class="form-hint">Format yang didukung: PDF, DOC, DOCX, JPG, JPEG, PNG</p>
          </div>
          <div v-if="attachmentFiles.length > 0" class="attachment-files-preview">
            <h4>File yang akan diunggah:</h4>
            <ul class="files-list">
              <li v-for="(file, index) in attachmentFiles" :key="index" class="file-item">
                <span>{{ file.name }}</span>
                <span class="file-size">({{ formatFileSize(file.size) }})</span>
                <button @click="removeAttachmentFile(index)" class="btn-remove-file">×</button>
              </li>
            </ul>
          </div>
          <div class="modal-footer">
            <button @click="closeAttachmentModal" class="btn-secondary">Batal</button>
            <button @click="uploadAttachments" class="btn-primary" :disabled="uploadingAttachments || attachmentFiles.length === 0">
              {{ uploadingAttachments ? 'Mengunggah...' : 'Unggah' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <div v-if="showExportModal" class="modal-overlay" @click="showExportModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Export Data</h3>
          <button @click="showExportModal = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>Pilih format export:</p>
          <div class="export-options">
            <button @click="exportData('excel')" class="btn-primary" :disabled="exporting">
              {{ exporting ? 'Mengekspor...' : 'Export ke Excel (CSV)' }}
            </button>
          </div>
          <div class="export-pdf-section">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">Export ke PDF:</label>
            <div style="display: flex; gap: 8px; align-items: center;">
              <select v-model="pdfExportType" class="form-input" style="flex: 1;">
                <option value="">Pilih tipe surat</option>
                <option value="masuk">Surat Masuk</option>
                <option value="keluar">Surat Keluar</option>
                <option value="internal">Surat Internal</option>
              </select>
              <button @click="exportData('pdf', pdfExportType)" class="btn-primary" :disabled="exporting || !pdfExportType">
                {{ exporting ? 'Mengekspor...' : 'Export PDF' }}
              </button>
            </div>
          </div>
          <p class="form-hint">Data akan diekspor sesuai dengan filter yang sedang aktif</p>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal-overlay" @click="showImportModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Import Data</h3>
          <button @click="showImportModal = false" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>File CSV <span class="required">*</span></label>
            <input 
              ref="importFileInput"
              type="file" 
              accept=".csv"
              class="form-input"
            />
            <p class="form-hint">Format file: CSV (.csv)</p>
            <p class="form-hint">Maksimal ukuran: 10MB</p>
          </div>
          <div class="form-group">
            <button @click="downloadImportTemplate" class="btn-secondary btn-sm">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Download Template
            </button>
          </div>
          <div class="modal-footer">
            <button @click="showImportModal = false" class="btn-secondary">Batal</button>
            <button @click="importData" class="btn-primary" :disabled="importing">
              {{ importing ? 'Mengimpor...' : 'Import' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Disposition Modal -->
    <div v-if="showDispositionModal" class="modal-overlay" @click="closeDispositionModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingDisposition ? 'Edit Disposisi' : 'Tambah Disposisi' }}</h3>
          <button @click="closeDispositionModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Kepada <span class="required">*</span></label>
            <select v-model="dispositionForm.to_user_id" required class="form-input">
              <option value="">Pilih Penerima</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Instruksi <span class="required">*</span></label>
            <textarea 
              v-model="dispositionForm.instruction" 
              required 
              class="form-input" 
              rows="4"
              placeholder="Masukkan instruksi disposisi..."
              maxlength="1000"
            ></textarea>
            <p class="form-hint">{{ dispositionForm.instruction.length }}/1000 karakter</p>
          </div>
          <div class="modal-footer">
            <button @click="closeDispositionModal" class="btn-secondary">Batal</button>
            <button @click="saveDisposition" class="btn-primary" :disabled="savingDisposition">
              {{ savingDisposition ? 'Menyimpan...' : 'Simpan' }}
            </button>
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
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import correspondenceApi from '@/api/correspondence'
import api from '@/api'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const correspondence = ref([])
const categories = ref([])
const letterTypes = ref([])
const users = ref([])
const dispositions = ref([])
const attachments = ref([])
const attachmentFiles = ref([])
const loading = ref(false)
const saving = ref(false)
const loadingDispositions = ref(false)
const loadingAttachments = ref(false)
const savingDisposition = ref(false)
const uploadingAttachments = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const showDispositionModal = ref(false)
const showAttachmentModal = ref(false)
const editingItem = ref(null)
const viewingItem = ref(null)
const editingDisposition = ref(null)
const fileInput = ref(null)
const attachmentFileInput = ref(null)
const importFileInput = ref(null)
const currentUserId = ref(null)
const statistics = ref(null)
const showAdvancedSearch = ref(false)
const showExportModal = ref(false)
const showImportModal = ref(false)
const exporting = ref(false)
const importing = ref(false)
const searchTimeout = ref(null)
const pdfExportType = ref('')

const filters = ref({
  search: '',
  type: '',
  status: '',
  priority: ''
})

const form = ref({
  type: '',
  letter_type_code: '',
  letter_number: '',
  reference_number: '',
  subject: '',
  from: '',
  to: '',
  date: '',
  received_date: '',
  priority: 'biasa',
  status: 'draft',
  category_id: '',
  description: '',
  file: null,
  file_name: ''
})

const dispositionForm = ref({
  to_user_id: '',
  instruction: ''
})

const correspondenceList = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const loadCorrespondence = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 15
    }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.type) params.type = filters.value.type
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.priority) params.priority = filters.value.priority

    const response = await correspondenceApi.list(params)
    
    // Debug logging (development only)
    if (import.meta.env.DEV) {
      console.log('Correspondence response:', response)
      console.log('Response data:', response.data)
    }
    
    // Handle paginated response from Laravel Resource Collection
    // Laravel Resource Collection with pagination format: 
    // { data: [...], links: {...}, meta: { current_page, last_page, per_page, total, ... } }
    if (response.data) {
      if (response.data.data && Array.isArray(response.data.data)) {
        // Paginated response from Resource Collection
        correspondence.value = response.data.data
        const meta = response.data.meta || {}
        correspondenceList.value = {
          data: response.data.data,
          current_page: meta.current_page || response.data.current_page || 1,
          last_page: meta.last_page || response.data.last_page || 1,
          per_page: meta.per_page || response.data.per_page || 15,
          total: meta.total || response.data.total || 0
        }
      } else if (Array.isArray(response.data)) {
        // Direct array response
        correspondence.value = response.data
        correspondenceList.value = {
          data: response.data,
          current_page: 1,
          last_page: 1,
          per_page: response.data.length,
          total: response.data.length
        }
      } else {
        // Empty or unexpected format
        correspondence.value = []
        correspondenceList.value = {
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 0
        }
      }
    } else {
      correspondence.value = []
      correspondenceList.value = {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
    }
  } catch (error) {
    if (import.meta.env.DEV) {
      console.error('Error loading correspondence:', error)
      console.error('Error response:', error.response)
      console.error('Error data:', error.response?.data)
    }
    const errorMessage = error.response?.data?.message || error.message || 'Gagal memuat data surat'
    toast.error('Gagal', errorMessage)
    
    // Set empty data on error
    correspondence.value = []
    correspondenceList.value = {
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    }
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await correspondenceApi.getCategories()
    categories.value = response.data.data || response.data
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to load categories:', error)
  }
}

// Filter kategori berdasarkan tipe surat yang dipilih
const filteredCategories = computed(() => {
  if (!form.value.type) {
    return []
  }
  return categories.value.filter(cat => cat.type === form.value.type)
})

const loadLetterTypes = async () => {
  try {
    const response = await correspondenceApi.getLetterTypes()
    
    // Debug logging (development only)
    if (import.meta.env.DEV) {
      console.log('Letter types response:', response)
    }
    
    // Handle different response formats
    if (response.data) {
      if (Array.isArray(response.data)) {
        letterTypes.value = response.data
      } else if (response.data.data && Array.isArray(response.data.data)) {
        letterTypes.value = response.data.data
      } else {
        letterTypes.value = []
      }
    } else {
      letterTypes.value = []
    }
    
    // Debug logging (development only)
    if (import.meta.env.DEV) {
      console.log('Loaded letter types:', letterTypes.value)
    }
  } catch (error) {
    if (import.meta.env.DEV) {
      console.error('Failed to load letter types:', error)
      console.error('Error response:', error.response)
    }
    toast.error('Gagal', 'Gagal memuat jenis surat')
  }
}

const openAddModal = () => {
  editingItem.value = null
  form.value = {
    type: '',
    letter_type_code: '',
    letter_number: '',
    reference_number: '',
    subject: '',
    from: '',
    to: '',
    date: '',
    received_date: '',
    priority: 'biasa',
    status: 'draft',
    category_id: '',
    description: '',
    file: null,
    file_name: ''
  }
  showModal.value = true
}

const onTypeChange = () => {
  // Reset letter_number jika surat keluar atau internal (akan auto-generate)
  if ((form.value.type === 'keluar' || form.value.type === 'internal') && !editingItem.value) {
    form.value.letter_number = ''
  }
}

const editCorrespondence = (item) => {
  editingItem.value = item
  form.value = {
    type: item.type,
    letter_type_code: item.letter_type_code || '',
    letter_number: item.letter_number || '',
    reference_number: item.reference_number || '',
    subject: item.subject,
    from: item.from || '',
    to: item.to || '',
    date: item.date ? item.date.split('T')[0] : '',
    received_date: item.received_date ? item.received_date.split('T')[0] : '',
    priority: item.priority || 'biasa',
    status: item.status,
    category_id: item.category_id || '',
    description: item.description || '',
    file: null,
    file_name: item.file_name || ''
  }
  showModal.value = true
}

const viewCorrespondence = async (item) => {
  try {
    const response = await correspondenceApi.get(item.id)
    viewingItem.value = response.data.data || response.data
    showViewModal.value = true
    // Load dispositions and attachments for this correspondence
    loadDispositions(item.id)
    loadAttachments(item.id)
  } catch (error) {
    toast.error('Gagal', 'Gagal memuat detail surat')
    if (import.meta.env.DEV) console.error(error)
  }
}

const loadDispositions = async (correspondenceId) => {
  loadingDispositions.value = true
  try {
    const response = await correspondenceApi.getDispositions(correspondenceId)
    dispositions.value = response.data.data || response.data || []
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to load dispositions:', error)
    dispositions.value = []
  } finally {
    loadingDispositions.value = false
  }
}

const loadUsers = async () => {
  try {
    const response = await correspondenceApi.getUsers()
    users.value = response.data.data || response.data || []
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to load users:', error)
    toast.error('Gagal', 'Gagal memuat daftar user')
  }
}

const openDispositionModal = () => {
  editingDisposition.value = null
  dispositionForm.value = {
    to_user_id: '',
    instruction: ''
  }
  showDispositionModal.value = true
}

const editDisposition = (disposition) => {
  editingDisposition.value = disposition
  dispositionForm.value = {
    to_user_id: disposition.to_user_id,
    instruction: disposition.instruction
  }
  showDispositionModal.value = true
}

const closeDispositionModal = () => {
  showDispositionModal.value = false
  editingDisposition.value = null
  dispositionForm.value = {
    to_user_id: '',
    instruction: ''
  }
}

const saveDisposition = async () => {
  if (!dispositionForm.value.to_user_id) {
    toast.error('Gagal', 'Penerima wajib diisi')
    return
  }
  if (!dispositionForm.value.instruction || dispositionForm.value.instruction.trim() === '') {
    toast.error('Gagal', 'Instruksi wajib diisi')
    return
  }

  savingDisposition.value = true
  try {
    if (editingDisposition.value) {
      await correspondenceApi.updateDisposition(editingDisposition.value.id, dispositionForm.value)
      toast.success('Berhasil', 'Disposisi berhasil diperbarui')
    } else {
      await correspondenceApi.createDisposition(viewingItem.value.id, dispositionForm.value)
      toast.success('Berhasil', 'Disposisi berhasil dibuat')
    }
    
    closeDispositionModal()
    loadDispositions(viewingItem.value.id)
  } catch (error) {
    const errorData = error.response?.data
    const message = errorData?.message || 'Gagal menyimpan disposisi'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    savingDisposition.value = false
  }
}

const completeDisposition = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Selesaikan',
    message: 'Apakah Anda yakin ingin menyelesaikan disposisi ini?',
    warning: ''
  })
  
  if (!confirmed) return

  try {
    await correspondenceApi.completeDisposition(id)
    toast.success('Berhasil', 'Disposisi berhasil diselesaikan')
    loadDispositions(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menyelesaikan disposisi'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  }
}

const deleteDisposition = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus disposisi ini?',
    warning: 'Disposisi akan dihapus secara permanen.'
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await correspondenceApi.deleteDisposition(id)
    toast.success('Berhasil', 'Disposisi berhasil dihapus')
    loadDispositions(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus disposisi'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    setDeleteLoading(false)
  }
}

const loadAttachments = async (correspondenceId) => {
  loadingAttachments.value = true
  try {
    const response = await correspondenceApi.getAttachments(correspondenceId)
    attachments.value = response.data.data || response.data || []
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to load attachments:', error)
    attachments.value = []
  } finally {
    loadingAttachments.value = false
  }
}

const openAttachmentModal = () => {
  attachmentFiles.value = []
  showAttachmentModal.value = true
}

const closeAttachmentModal = () => {
  showAttachmentModal.value = false
  attachmentFiles.value = []
  if (attachmentFileInput.value) {
    attachmentFileInput.value.value = ''
  }
}

const handleAttachmentFilesChange = (event) => {
  const files = Array.from(event.target.files || [])
  if (files.length > 10) {
    toast.error('Gagal', 'Maksimal 10 file dapat diunggah sekaligus')
    return
  }
  
  // Validate file sizes (max 10MB each)
  const invalidFiles = files.filter(file => file.size > 10 * 1024 * 1024)
  if (invalidFiles.length > 0) {
    toast.error('Gagal', 'Beberapa file melebihi ukuran maksimal 10MB')
    return
  }
  
  attachmentFiles.value = files
}

const removeAttachmentFile = (index) => {
  attachmentFiles.value.splice(index, 1)
  if (attachmentFileInput.value) {
    attachmentFileInput.value.value = ''
  }
}

const uploadAttachments = async () => {
  if (attachmentFiles.value.length === 0) {
    toast.error('Gagal', 'Pilih file terlebih dahulu')
    return
  }

  uploadingAttachments.value = true
  try {
    await correspondenceApi.uploadAttachments(viewingItem.value.id, attachmentFiles.value)
    toast.success('Berhasil', 'Lampiran berhasil diunggah')
    closeAttachmentModal()
    loadAttachments(viewingItem.value.id)
  } catch (error) {
    const errorData = error.response?.data
    const message = errorData?.message || 'Gagal mengunggah lampiran'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    uploadingAttachments.value = false
  }
}

const deleteAttachment = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus lampiran ini?',
    warning: 'Lampiran akan dihapus secara permanen.'
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await correspondenceApi.deleteAttachment(id)
    toast.success('Berhasil', 'Lampiran berhasil dihapus')
    loadAttachments(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus lampiran'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    setDeleteLoading(false)
  }
}

const formatFileSize = (bytes) => {
  if (bytes >= 1073741824) {
    return (bytes / 1073741824).toFixed(2) + ' GB'
  } else if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(2) + ' MB'
  } else if (bytes >= 1024) {
    return (bytes / 1024).toFixed(2) + ' KB'
  }
  return bytes + ' bytes'
}

const loadStatistics = async () => {
  try {
    const response = await correspondenceApi.getStatistics()
    statistics.value = response.data.data || response.data
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to load statistics:', error)
  }
}

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    loadCorrespondence()
  }, 500)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    type: '',
    status: '',
    priority: '',
    date_from: '',
    date_to: '',
    letter_type_code: '',
    category_id: ''
  }
  loadCorrespondence()
}

const exportData = async (format, type = null) => {
  exporting.value = true
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.priority) params.priority = filters.value.priority
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to
    if (filters.value.letter_type_code) params.letter_type_code = filters.value.letter_type_code
    if (filters.value.category_id) params.category_id = filters.value.category_id

    // Untuk Excel, gunakan filter type yang ada
    // Untuk PDF, gunakan type yang dipilih dari dropdown
    if (format === 'excel') {
      if (filters.value.type) params.type = filters.value.type
    } else if (format === 'pdf') {
      if (type) {
        params.type = type
      } else {
        toast.error('Gagal', 'Pilih tipe surat terlebih dahulu')
        exporting.value = false
        return
      }
    }

    let response
    if (format === 'excel') {
      response = await correspondenceApi.exportExcel(params)
    } else {
      response = await correspondenceApi.exportPdf(params)
    }

    const downloadUrl = response.data.download_url || response.data.data?.download_url
    if (downloadUrl) {
      window.open(downloadUrl, '_blank')
      toast.success('Berhasil', 'Export berhasil, file sedang diunduh')
    } else {
      toast.error('Gagal', 'URL download tidak ditemukan')
    }
    showExportModal.value = false
    pdfExportType.value = '' // Reset setelah export
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengekspor data'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    exporting.value = false
  }
}

const importData = async () => {
  if (!importFileInput.value || !importFileInput.value.files || !importFileInput.value.files[0]) {
    toast.error('Gagal', 'Pilih file terlebih dahulu')
    return
  }

  importing.value = true
  try {
    const response = await correspondenceApi.import(importFileInput.value.files[0])
    const results = response.data.data || response.data
    
    if (results.success > 0) {
      toast.success('Berhasil', `${results.success} data berhasil diimpor`)
      loadCorrespondence()
      loadStatistics()
    }
    
    if (results.failed > 0 && results.errors && results.errors.length > 0) {
      const errorMsg = results.errors.slice(0, 5).join('\n')
      toast.error('Peringatan', `${results.failed} data gagal diimpor:\n${errorMsg}`)
    }
    
    showImportModal.value = false
    if (importFileInput.value) {
      importFileInput.value.value = ''
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengimpor data'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    importing.value = false
  }
}

const downloadImportTemplate = async () => {
  try {
    const response = await correspondenceApi.downloadImportTemplate()
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template_import_surat.csv'
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
    toast.success('Berhasil', 'Template berhasil diunduh')
  } catch (error) {
    toast.error('Gagal', 'Gagal mengunduh template')
    if (import.meta.env.DEV) console.error(error)
  }
}

const closeModal = () => {
  showModal.value = false
  editingItem.value = null
}

const closeViewModal = () => {
  showViewModal.value = false
  viewingItem.value = null
}

const handleFileChange = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    form.value.file = file
    form.value.file_name = file.name
  }
}

const saveCorrespondence = async () => {
  // Validasi
  if (!form.value.type) {
    toast.error('Gagal', 'Tipe surat wajib diisi')
    return
  }
  if (!form.value.letter_type_code) {
    toast.error('Gagal', 'Jenis surat wajib diisi')
    return
  }
  if (!form.value.subject) {
    toast.error('Gagal', 'Perihal wajib diisi')
    return
  }
  if (!form.value.date) {
    toast.error('Gagal', 'Tanggal surat wajib diisi')
    return
  }
  if (form.value.type === 'masuk' && !form.value.from) {
    toast.error('Gagal', 'Pengirim wajib diisi untuk surat masuk')
    return
  }
  if (form.value.type === 'keluar' && !form.value.to) {
    toast.error('Gagal', 'Penerima wajib diisi untuk surat keluar')
    return
  }

  saving.value = true
  try {
    const data = {
      type: form.value.type,
      letter_type_code: form.value.letter_type_code,
      subject: form.value.subject.trim(),
      date: form.value.date,
      priority: form.value.priority,
      status: form.value.status,
      description: form.value.description ? form.value.description.trim() : null,
      file: form.value.file
    }

    // Handle letter_number: kosongkan untuk auto-generate surat keluar dan internal baru
    if ((form.value.type === 'keluar' || form.value.type === 'internal') && !editingItem.value) {
      data.letter_number = null // Will be auto-generated
    } else {
      data.letter_number = form.value.letter_number || null
    }

    // Handle reference_number
    data.reference_number = form.value.reference_number ? form.value.reference_number.trim() : null

    // Handle from/to based on type
    if (form.value.type === 'masuk') {
      data.from = form.value.from.trim()
      data.to = null
      data.received_date = form.value.received_date || null
    } else {
      data.from = null
      data.to = form.value.to ? form.value.to.trim() : null
      data.received_date = null
    }

    // Handle category_id - convert empty string to null
    data.category_id = form.value.category_id && form.value.category_id !== '' ? form.value.category_id : null

    if (editingItem.value) {
      const response = await correspondenceApi.update(editingItem.value.id, data)
      toast.success('Berhasil', 'Surat berhasil diperbarui')
      // Update viewing item if modal is open
      if (viewingItem.value && viewingItem.value.id === editingItem.value.id) {
        viewingItem.value = response.data.data || response.data
      }
    } else {
      const response = await correspondenceApi.create(data)
      // Tampilkan nomor surat yang di-generate jika surat keluar
      const responseData = response.data?.data || response.data
      if (form.value.type === 'keluar' && responseData?.letter_number) {
        toast.success('Berhasil', `Surat berhasil ditambahkan. Nomor: ${responseData.letter_number}`)
      } else {
        toast.success('Berhasil', 'Surat berhasil ditambahkan')
      }
    }

    closeModal()
    loadCorrespondence(correspondenceList.value.current_page)
  } catch (error) {
    const errorData = error.response?.data
    let message = 'Gagal menyimpan surat'
    if (import.meta.env.DEV) {
      console.error('Error saving correspondence:', error)
      console.error('Error response:', error.response)
      console.error('Error data:', errorData)
    }
    if (errorData) {
      if (errorData.message) {
        message = errorData.message
      } else if (errorData.errors) {
        // Handle validation errors
        const errors = Object.values(errorData.errors).flat()
        message = errors.join(', ')
      } else if (errorData.error) {
        message = errorData.error
      }
    } else if (error.message) {
      message = error.message
    }
    
    toast.error('Gagal', message)
  } finally {
    saving.value = false
  }
}

const deleteCorrespondence = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus surat ini?',
    warning: 'Surat akan dihapus secara permanen dan tidak dapat dikembalikan.'
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await correspondenceApi.delete(id)
    toast.success('Berhasil', 'Surat berhasil dihapus')
    loadCorrespondence(correspondenceList.value.current_page)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus surat'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  } finally {
    setDeleteLoading(false)
  }
}

const approveCorrespondence = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Setujui',
    message: 'Apakah Anda yakin ingin menyetujui surat ini?',
    warning: ''
  })
  
  if (!confirmed) return

  try {
    await correspondenceApi.approve(id)
    toast.success('Berhasil', 'Surat berhasil disetujui')
    loadCorrespondence(correspondenceList.value.current_page)
    // Update viewing item if modal is open
    if (viewingItem.value && viewingItem.value.id === id) {
      const response = await correspondenceApi.get(id)
      viewingItem.value = response.data.data || response.data
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menyetujui surat'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  }
}

const sendCorrespondence = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Kirim',
    message: 'Apakah Anda yakin ingin mengirim surat ini?',
    warning: 'Surat yang sudah dikirim tidak dapat diubah.'
  })
  
  if (!confirmed) return

  try {
    await correspondenceApi.send(id)
    toast.success('Berhasil', 'Surat berhasil dikirim')
    loadCorrespondence(correspondenceList.value.current_page)
    // Update viewing item if modal is open
    if (viewingItem.value && viewingItem.value.id === id) {
      const response = await correspondenceApi.get(id)
      viewingItem.value = response.data.data || response.data
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal mengirim surat'
    toast.error('Gagal', message)
    if (import.meta.env.DEV) console.error(error)
  }
}

const printCorrespondence = async (id) => {
  try {
    const response = await correspondenceApi.print(id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `surat-${id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Berhasil', 'PDF berhasil diunduh')
  } catch (error) {
    toast.error('Gagal', 'Gagal mencetak surat')
    if (import.meta.env.DEV) console.error(error)
  }
}

const getFileUrl = (item) => {
  if (item.file_url) return item.file_url
  if (item.file_path) {
    const base = import.meta.env.VITE_API_BASE_URL
    const origin = base && !base.startsWith('/') ? new URL(base).origin : window.location.origin
    return `${origin}/storage/${item.file_path}`
  }
  return '#'
}

const rowNumber = (index) => {
  const page = correspondenceList.value.current_page || 1
  const perPage = correspondenceList.value.per_page || 15
  return (page - 1) * perPage + index + 1
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

const getStatusClass = (status) => {
  const classes = {
    draft: 'badge-gray',
    pending: 'badge-yellow',
    approved: 'badge-blue',
    sent: 'badge-green',
    archived: 'badge-gray'
  }
  return classes[status] || 'badge-gray'
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Menunggu',
    approved: 'Disetujui',
    sent: 'Terkirim',
    archived: 'Diarsipkan'
  }
  return labels[status] || status
}

const getPriorityClass = (priority) => {
  const classes = {
    biasa: 'badge-gray',
    penting: 'badge-yellow',
    sangat_penting: 'badge-red'
  }
  return classes[priority] || 'badge-gray'
}

const getPriorityLabel = (priority) => {
  const labels = {
    biasa: 'Biasa',
    penting: 'Penting',
    sangat_penting: 'Sangat Penting'
  }
  return labels[priority] || priority
}

const getTypeClass = (type) => {
  const classes = {
    masuk: 'badge-blue',
    keluar: 'badge-green',
    internal: 'badge-purple'
  }
  return classes[type] || 'badge-gray'
}

const getTypeLabel = (type) => {
  const labels = {
    masuk: 'Surat Masuk',
    keluar: 'Surat Keluar',
    internal: 'Surat Internal'
  }
  return labels[type] || type
}

onMounted(async () => {
  await Promise.all([
    loadCorrespondence(1),
    loadCategories(),
    loadLetterTypes(),
    loadUsers(),
    loadStatistics()
  ])
  
  // Get current user ID
  try {
    const response = await api.get('/v1/me')
    currentUserId.value = response.data.data?.id || response.data.id
  } catch (error) {
    if (import.meta.env.DEV) console.error('Failed to get current user:', error)
    // Try to get from auth store if available
    try {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      if (authStore.user?.id) {
        currentUserId.value = authStore.user.id
      }
    } catch (e) {
      if (import.meta.env.DEV) console.error('Failed to get user from auth store:', e)
    }
  }
})
</script>

<style scoped>
.correspondence-page {
  width: 100%;
  max-width: 100%;
  padding: 24px;
  margin: 0 auto;
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
  margin: 0 0 4px 0;
}

.header-content p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.action-buttons-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.btn-compact {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  font-size: 13px;
  border-radius: 10px;
  white-space: nowrap;
}

.btn-compact svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #059669;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #059669;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filters.filters-inline {
  padding: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.search-input {
  flex: 1;
  min-width: 200px;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 140px;
  box-sizing: border-box;
}

.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 40px;
  gap: 16px;
  color: #64748b;
}

.loading-spinner {
  color: #059669;
}

.list-wrapper {
  width: 100%;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table-desktop {
  display: block;
}

.table-mobile {
  display: none;
}

.correspondence-cards.table-mobile {
  display: none;
  flex-direction: column;
  gap: 12px;
}

.correspondence-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.correspondence-card-main {
  min-width: 0;
}

.correspondence-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.correspondence-card-date {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
}

.correspondence-card-subject {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 8px 0;
  line-height: 1.35;
  word-break: break-word;
}

.correspondence-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 6px;
}

.correspondence-card-number {
  font-weight: 600;
  color: #475569;
  word-break: break-all;
}

.correspondence-card-type {
  padding: 2px 8px;
  background: #f1f5f9;
  border-radius: 6px;
  font-weight: 600;
  color: #475569;
}

.correspondence-card-party {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  word-break: break-word;
}

.correspondence-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding-top: 10px;
  border-top: 1px solid #e2e8f0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}

.data-table th {
  padding: 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Column Widths */
.data-table th.col-no,
.data-table td.col-no {
  width: 52px;
  max-width: 52px;
  min-width: 52px;
  text-align: center;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}

.data-table th.col-no-surat,
.data-table td.col-no-surat {
  width: 220px;
  max-width: 220px;
  min-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.data-table th.col-tipe,
.data-table td.col-tipe {
  width: 110px;
  max-width: 110px;
  min-width: 110px;
}

.data-table th.col-jenis,
.data-table td.col-jenis {
  width: 200px;
  max-width: 200px;
  min-width: 200px;
}

.data-table th.col-perihal,
.data-table td.col-perihal {
  min-width: 200px;
  /* Flexible width, takes remaining space */
  word-break: break-word;
}

.data-table th.col-dari-kepada,
.data-table td.col-dari-kepada {
  width: 180px;
  max-width: 180px;
  min-width: 180px;
  word-break: break-word;
}

.data-table th.col-tanggal,
.data-table td.col-tanggal {
  width: 150px;
  max-width: 150px;
  min-width: 150px;
  white-space: nowrap;
}

.data-table th.col-aksi,
.data-table td.col-aksi {
  width: 100px;
  max-width: 100px;
  min-width: 100px;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
}

.btn-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
  color: #64748b;
}

.btn-action:hover {
  background: rgba(0, 0, 0, 0.05);
}

.btn-view {
  color: #059669;
}

.btn-view:hover {
  background: rgba(59, 130, 246, 0.1);
}

.btn-edit {
  color: #f59e0b;
}

.btn-edit:hover {
  background: rgba(245, 158, 11, 0.1);
}

.btn-delete {
  color: #ef4444;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
}

.btn-print {
  color: #059669;
}

.btn-print:hover {
  background: rgba(139, 92, 246, 0.1);
}

.btn-approve {
  color: #10b981;
}

.btn-approve:hover {
  background: rgba(16, 185, 129, 0.1);
}

.btn-send {
  color: #059669;
}

.btn-send:hover {
  background: rgba(5, 150, 105, 0.1);
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #d1fae5;
  color: #065f46;
}

.badge-gray {
  background: #f1f5f9;
  color: #475569;
}

.badge-yellow {
  background: #fef3c7;
  color: #92400e;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-red {
  background: #fee2e2;
  color: #991b1b;
}

.badge-purple {
  background: #e9d5ff;
  color: #6b21a8;
}

.letter-type-badge {
  display: inline-block;
  font-size: 11px;
  color: #475569;
  padding: 4px 8px;
  background: #f1f5f9;
  border-radius: 6px;
  white-space: normal;
  word-break: break-word;
  line-height: 1.4;
  max-width: 100%;
}

.form-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  font-style: italic;
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
  margin: 0;
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
  z-index: 11000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-large {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.modal-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 8px;
}

.required {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input[type="file"] {
  padding: 8px;
}

textarea.form-input {
  resize: vertical;
  min-height: 100px;
}

.file-info {
  margin-top: 8px;
  font-size: 12px;
  color: #64748b;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 24px;
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-label-short {
  display: none;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-item span {
  font-size: 14px;
  color: #1e293b;
}

.file-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #059669;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
}

.file-link:hover {
  color: #059669;
  text-decoration: underline;
}

@media (max-width: 1024px) {
  .data-table th.col-no,
  .data-table td.col-no,
  .data-table th.col-no-surat,
  .data-table td.col-no-surat,
  .data-table th.col-jenis,
  .data-table td.col-jenis,
  .data-table th.col-dari-kepada,
  .data-table td.col-dari-kepada,
  .data-table th.col-tanggal,
  .data-table td.col-tanggal,
  .data-table th.col-tipe,
  .data-table td.col-tipe,
  .data-table th.col-aksi,
  .data-table td.col-aksi {
    min-width: 0;
    width: auto;
    max-width: none;
  }

  .data-table {
    min-width: 900px;
  }

  /* Override global module-page.css compact-row layout */
  .correspondence-page :deep(.action-buttons-group),
  .action-buttons-group {
    flex-wrap: wrap !important;
  }

  .correspondence-page .action-buttons-group .btn-compact,
  .action-buttons-group .btn-compact {
    flex: 1 1 auto !important;
    height: auto !important;
    min-height: 40px;
    padding: 10px 14px !important;
    font-size: 13px !important;
  }

  .correspondence-page .action-buttons-group .btn-compact span,
  .action-buttons-group .btn-compact span {
    display: inline !important;
  }

  .correspondence-page .filters.filters-inline,
  .filters.filters-inline {
    flex-direction: column !important;
    flex-wrap: wrap !important;
    overflow: visible !important;
    gap: 8px !important;
    padding: 12px !important;
  }

  .correspondence-page .filters-inline .search-input,
  .filters-inline .search-input,
  .correspondence-page .filters-inline .filter-select,
  .filters-inline .filter-select {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    flex: 1 1 auto !important;
    font-size: 14px !important;
    padding: 10px 12px !important;
  }
}

@media (max-width: 768px) {
  .correspondence-page {
    padding: 12px;
    padding-bottom: 88px;
  }

  .page-header {
    margin-bottom: 16px;
  }

  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .header-content h2 {
    font-size: 22px;
  }

  .header-content p {
    font-size: 13px;
  }

  .action-buttons-group {
    width: 100%;
  }

  .action-buttons-group .btn-add {
    width: 100% !important;
    flex: 1 1 100% !important;
    justify-content: center;
    padding: 12px 16px !important;
    min-height: 44px;
  }

  .statistics-dashboard {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 16px;
  }

  .stat-card {
    padding: 12px;
    gap: 10px;
  }

  .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
  }

  .stat-icon svg {
    width: 20px;
    height: 20px;
  }

  .stat-value {
    font-size: 18px;
  }

  .stat-label {
    font-size: 11px;
  }

  .action-bar {
    margin-bottom: 12px;
  }

  .action-group {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
  }

  .action-group .btn-sm {
    width: 100%;
    justify-content: center;
    padding: 10px 8px;
    font-size: 12px;
    white-space: nowrap;
  }

  .action-group .btn-sm svg {
    display: none;
  }

  .btn-label-full {
    display: none;
  }

  .btn-label-short {
    display: inline;
  }

  .filters.filters-inline {
    flex-direction: column !important;
    padding: 12px !important;
    margin-bottom: 16px;
    gap: 8px !important;
  }

  .search-input,
  .filter-select {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
  }

  .advanced-search {
    padding: 12px;
  }

  .search-row {
    grid-template-columns: 1fr;
  }

  .form-row,
  .detail-row {
    grid-template-columns: 1fr;
  }

  .table-desktop {
    display: none;
  }

  .correspondence-cards.table-mobile {
    display: flex;
  }

  .pagination {
    flex-direction: column;
    gap: 10px;
    text-align: center;
    padding: 14px;
    margin-top: 12px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
  }

  .pagination-info {
    font-size: 12px;
    order: -1;
  }

  .pagination-btn {
    width: 100%;
  }

  .empty-state {
    padding: 48px 20px;
  }

  .modal-overlay {
    padding: 0;
    align-items: flex-end;
  }

  .modal-content {
    max-width: 100%;
    width: 100%;
    max-height: 92vh;
    border-radius: 16px 16px 0 0;
  }

  .modal-large {
    max-width: 100%;
  }

  .modal-header,
  .modal-body {
    padding: 16px;
  }

  .modal-footer {
    flex-direction: column-reverse;
    gap: 8px;
  }

  .modal-footer .btn-primary,
  .modal-footer .btn-secondary {
    width: 100%;
    justify-content: center;
  }

  .section-header {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .section-header .btn-sm {
    width: 100%;
    justify-content: center;
  }

  .attachment-item {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .attachment-actions {
    justify-content: flex-end;
  }

  .disposition-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .disposition-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .disposition-actions {
    width: 100%;
  }

  .disposition-actions .btn-sm,
  .disposition-actions .btn-complete {
    flex: 1;
    justify-content: center;
  }

  .export-options {
    flex-direction: column;
  }

  .btn-action {
    width: 40px;
    height: 40px;
  }
}

@media (max-width: 480px) {
  .correspondence-page {
    padding: 10px;
    padding-bottom: 88px;
  }

  .statistics-dashboard {
    grid-template-columns: 1fr 1fr;
  }

  .stat-card:last-child {
    grid-column: 1 / -1;
  }

  .action-group {
    grid-template-columns: repeat(3, 1fr);
  }

  .action-group .btn-sm {
    padding: 10px 4px;
    font-size: 11px;
  }

  .action-group .btn-sm svg {
    display: none;
  }

  .btn-label-full {
    display: none;
  }

  .btn-label-short {
    display: inline;
  }

  .header-content h2 {
    font-size: 20px;
  }

  .correspondence-card {
    padding: 14px;
  }

  .correspondence-card-subject {
    font-size: 14px;
  }
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

.table-desktop .pagination {
  border-top: 1px solid #e2e8f0;
}

.list-wrapper > .pagination {
  margin-top: 12px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.pagination-btn {
  padding: 8px 16px;
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
}

/* Dispositions Section */
.dispositions-section {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 2px solid #e2e8f0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 14px;
}

.dispositions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.disposition-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  transition: all 0.2s;
}

.disposition-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.disposition-item.completed {
  background: #f0fdf4;
  border-color: #86efac;
}

.disposition-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.disposition-from-to {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.disposition-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.disposition-separator {
  color: #94a3b8;
  font-weight: 600;
}

.disposition-instruction {
  margin-bottom: 12px;
}

.disposition-instruction strong {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.disposition-instruction p {
  margin: 0;
  color: #1e293b;
  line-height: 1.6;
  white-space: pre-wrap;
}

.disposition-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid #e2e8f0;
}

.disposition-date {
  font-size: 12px;
  color: #64748b;
}

.disposition-actions {
  display: flex;
  gap: 8px;
}

.btn-complete {
  background: #10b981;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  transition: background 0.2s;
}

.btn-complete:hover {
  background: #059669;
}

.empty-state-small {
  text-align: center;
  padding: 24px;
  color: #64748b;
  font-size: 14px;
}

.loading-state-small {
  text-align: center;
  padding: 24px;
  color: #64748b;
  font-size: 14px;
}

/* Attachments Section */
.attachments-section {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 2px solid #e2e8f0;
}

.attachments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.attachment-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px 16px;
  transition: all 0.2s;
}

.attachment-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.attachment-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.attachment-icon {
  color: #64748b;
  flex-shrink: 0;
}

.attachment-details {
  flex: 1;
  min-width: 0;
}

.attachment-name {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
  word-break: break-word;
}

.attachment-meta {
  font-size: 12px;
  color: #64748b;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.attachment-description {
  font-size: 13px;
  color: #475569;
  margin-top: 4px;
  font-style: italic;
}

.attachment-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.btn-download {
  color: #10b981;
}

.btn-download:hover {
  background: rgba(16, 185, 129, 0.1);
}

.attachment-files-preview {
  margin-top: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
}

.attachment-files-preview h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 12px;
}

.files-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.file-item span {
  color: #1e293b;
  font-size: 13px;
}

.file-size {
  color: #64748b;
  margin-left: 8px;
}

.btn-remove-file {
  background: #fee2e2;
  color: #991b1b;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-remove-file:hover {
  background: #fecaca;
}

/* Statistics Dashboard */
.statistics-dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon.stat-total {
  background: rgba(5, 150, 105, 0.1);
  color: #059669;
}

.stat-icon.stat-masuk {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.stat-icon.stat-keluar {
  background: rgba(59, 130, 246, 0.1);
  color: #059669;
}

.stat-icon.stat-internal {
  background: rgba(168, 85, 247, 0.1);
  color: #a855f7;
}

.stat-icon.stat-pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

/* Action Bar */
.action-bar {
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.action-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Advanced Search */
.advanced-search {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.search-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}

/* Export Options */
.export-options {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.export-options button {
  flex: 1;
}

.export-pdf-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}
</style>
