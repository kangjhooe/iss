<template>
  <Layout>
    <div class="correspondence-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Persuratan</h2>
            <p>Kelola surat masuk, keluar, dan internal</p>
          </div>
          <button @click="openAddModal" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Surat</span>
          </button>
        </div>
      </div>

      <div class="filters">
        <input 
          v-model="filters.search" 
          @input="loadCorrespondence" 
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
              <th>No. Surat</th>
              <th>Tipe & Jenis</th>
              <th>Perihal</th>
              <th>Dari/Kepada</th>
              <th>Tanggal</th>
              <th>Prioritas</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in correspondence" :key="item.id">
              <td>{{ item.letter_number || item.reference_number || '-' }}</td>
              <td>
                <span :class="['badge', getTypeClass(item.type)]">
                  {{ getTypeLabel(item.type) }}
                </span>
                <span v-if="item.letter_type_name" class="letter-type-badge">
                  {{ item.letter_type_code }} - {{ item.letter_type_abbr }} ({{ item.letter_type_name }})
                </span>
              </td>
              <td>{{ item.subject }}</td>
              <td>{{ item.type === 'masuk' ? (item.from || '-') : (item.to || '-') }}</td>
              <td>{{ formatDate(item.date) }}</td>
              <td>
                <span :class="['badge', getPriorityClass(item.priority)]">
                  {{ getPriorityLabel(item.priority) }}
                </span>
              </td>
              <td>
                <span :class="['badge', getStatusClass(item.status)]">
                  {{ getStatusLabel(item.status) }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="viewCorrespondence(item)" class="btn-action btn-view" title="Lihat Detail">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="printCorrespondence(item.id)" class="btn-action btn-print" title="Cetak PDF">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                <select v-model="form.category_id" class="form-input">
                  <option value="">Pilih Kategori (Opsional)</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
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
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import correspondenceApi from '@/api/correspondence'
import api from '@/api'
import { useToast } from '@/composables/useToast'

const toast = useToast()

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
const currentUserId = ref(null)

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
    
    console.log('Correspondence response:', response)
    console.log('Response data:', response.data)
    
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
    console.error('Error loading correspondence:', error)
    console.error('Error response:', error.response)
    console.error('Error data:', error.response?.data)
    
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
    console.error('Failed to load categories:', error)
  }
}

const loadLetterTypes = async () => {
  try {
    const response = await correspondenceApi.getLetterTypes()
    console.log('Letter types response:', response)
    
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
    
    console.log('Loaded letter types:', letterTypes.value)
  } catch (error) {
    console.error('Failed to load letter types:', error)
    console.error('Error response:', error.response)
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
    console.error(error)
  }
}

const loadDispositions = async (correspondenceId) => {
  loadingDispositions.value = true
  try {
    const response = await correspondenceApi.getDispositions(correspondenceId)
    dispositions.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load dispositions:', error)
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
    console.error('Failed to load users:', error)
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
    console.error(error)
  } finally {
    savingDisposition.value = false
  }
}

const completeDisposition = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menyelesaikan disposisi ini?')) return

  try {
    await correspondenceApi.completeDisposition(id)
    toast.success('Berhasil', 'Disposisi berhasil diselesaikan')
    loadDispositions(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menyelesaikan disposisi'
    toast.error('Gagal', message)
    console.error(error)
  }
}

const deleteDisposition = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus disposisi ini?')) return

  try {
    await correspondenceApi.deleteDisposition(id)
    toast.success('Berhasil', 'Disposisi berhasil dihapus')
    loadDispositions(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus disposisi'
    toast.error('Gagal', message)
    console.error(error)
  }
}

const loadAttachments = async (correspondenceId) => {
  loadingAttachments.value = true
  try {
    const response = await correspondenceApi.getAttachments(correspondenceId)
    attachments.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load attachments:', error)
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
    console.error(error)
  } finally {
    uploadingAttachments.value = false
  }
}

const deleteAttachment = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus lampiran ini?')) return

  try {
    await correspondenceApi.deleteAttachment(id)
    toast.success('Berhasil', 'Lampiran berhasil dihapus')
    loadAttachments(viewingItem.value.id)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus lampiran'
    toast.error('Gagal', message)
    console.error(error)
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
    
    console.error('Error saving correspondence:', error)
    console.error('Error response:', error.response)
    console.error('Error data:', errorData)
    
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
  if (!confirm('Apakah Anda yakin ingin menghapus surat ini?')) return

  try {
    await correspondenceApi.delete(id)
    toast.success('Berhasil', 'Surat berhasil dihapus')
    loadCorrespondence(correspondenceList.value.current_page)
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus surat'
    toast.error('Gagal', message)
    console.error(error)
  }
}

const approveCorrespondence = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menyetujui surat ini?')) return

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
    console.error(error)
  }
}

const sendCorrespondence = async (id) => {
  if (!confirm('Apakah Anda yakin ingin mengirim surat ini?')) return

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
    console.error(error)
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
    console.error(error)
  }
}

const getFileUrl = (item) => {
  if (item.file_path) {
    return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/storage/${item.file_path}`
  }
  return '#'
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
  loadCorrespondence(1)
  loadCategories()
  loadLetterTypes()
  await loadUsers()
  
  // Get current user ID
  try {
    const response = await api.get('/v1/me')
    currentUserId.value = response.data.data?.id || response.data.id
  } catch (error) {
    console.error('Failed to get current user:', error)
    // Try to get from auth store if available
    try {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()
      if (authStore.user?.id) {
        currentUserId.value = authStore.user.id
      }
    } catch (e) {
      console.error('Failed to get user from auth store:', e)
    }
  }
})
</script>

<style scoped>
.correspondence-page {
  padding: 24px;
  max-width: 1400px;
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

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #2563eb;
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

.search-input {
  flex: 1;
  min-width: 200px;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
  color: #3b82f6;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
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
  color: #3b82f6;
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
  color: #8b5cf6;
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
  color: #06b6d4;
}

.btn-send:hover {
  background: rgba(6, 182, 212, 0.1);
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
  font-size: 11px;
  color: #64748b;
  margin-top: 4px;
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
  z-index: 1000;
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
  border-color: #3b82f6;
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
  color: #3b82f6;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
}

.file-link:hover {
  color: #2563eb;
  text-decoration: underline;
}

@media (max-width: 768px) {
  .correspondence-page {
    padding: 16px;
  }

  .header-content {
    flex-direction: column;
  }

  .filters {
    flex-direction: column;
  }

  .form-row,
  .detail-row {
    grid-template-columns: 1fr;
  }

  .data-table {
    font-size: 12px;
  }

  .data-table th,
  .data-table td {
    padding: 12px 8px;
  }

  .action-buttons {
    flex-wrap: wrap;
  }
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
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
</style>
