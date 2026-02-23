<template>
  <Layout>
    <div class="semester-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Manajemen Semester</h2>
            <p>Kelola data semester per tahun ajaran</p>
          </div>
          <div class="action-buttons-group">
            <button @click="showAddModal = true" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Semester</span>
            </button>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <input 
          v-model="filters.search" 
          @input="loadSemesters" 
          placeholder="Cari semester..."
          class="search-input"
        />
        <select v-model="filters.academic_year_id" @change="loadSemesters" class="filter-select">
          <option value="">Semua Tahun Ajaran</option>
          <option v-for="year in academicYears" :key="year.id" :value="year.id">
            {{ year.code }} - {{ year.name }}
          </option>
        </select>
        <select v-model="filters.status" @change="loadSemesters" class="filter-select">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Arsip">Arsip</option>
          <option value="Draft">Draft</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="7" :cell-widths="['100px', '120px', '80px', '120px', '120px', '80px', '120px']" />
      </div>
      
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tahun Ajaran</th>
              <th>Nama</th>
              <th>Urutan</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Akhir</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="semester in semesters" :key="semester.id">
              <td>
                <strong v-if="semester.academic_year">{{ semester.academic_year.code }}</strong>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ semester.name || '-' }}</td>
              <td>{{ semester.order || '-' }}</td>
              <td>{{ formatDate(semester.start_date) }}</td>
              <td>{{ formatDate(semester.end_date) }}</td>
              <td>
                <span :class="getStatusClass(semester.status)">
                  {{ semester.status }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="editSemester(semester)" class="btn-action btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button 
                    v-if="semester.status !== 'Aktif'" 
                    @click="activateSemester(semester.id)" 
                    class="btn-action btn-activate" 
                    title="Aktifkan"
                  >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="deleteSemester(semester.id)" class="btn-action btn-delete" title="Hapus">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="semesters.length === 0" class="empty-state">
          <p>Tidak ada data semester</p>
        </div>

        <div v-if="pagination && pagination.last_page > 1" class="pagination">
          <button 
            @click="loadSemesters(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1"
            class="pagination-btn"
          >
            Sebelumnya
          </button>
          <span class="pagination-info">
            Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
          </span>
          <button 
            @click="loadSemesters(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="pagination-btn"
          >
            Selanjutnya
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSemester ? 'Edit Semester' : 'Tambah Semester' }}</h3>
            <button @click="closeModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveSemester" class="modal-body">
            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="form-group">
              <label>Tahun Ajaran <span class="required">*</span></label>
              <select v-model="form.academic_year_id" required class="form-input">
                <option value="">Pilih Tahun Ajaran</option>
                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                  {{ year.code }} - {{ year.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Nama Semester <span class="required">*</span></label>
              <select v-model="form.name" required class="form-input">
                <option value="">Pilih Semester</option>
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
              </select>
            </div>

            <div class="form-group">
              <label>Urutan <span class="required">*</span></label>
              <input 
                v-model.number="form.order" 
                type="number" 
                required
                min="1"
                placeholder="1 atau 2"
                class="form-input"
              />
              <small class="form-hint">Urutan semester dalam tahun ajaran (1 untuk Ganjil, 2 untuk Genap)</small>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Mulai <span class="required">*</span></label>
                <input 
                  v-model="form.start_date" 
                  type="date" 
                  required
                  class="form-input"
                />
              </div>

              <div class="form-group">
                <label>Tanggal Akhir <span class="required">*</span></label>
                <input 
                  v-model="form.end_date" 
                  type="date" 
                  required
                  class="form-input"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-input">
                <option value="Draft">Draft</option>
                <option value="Aktif">Aktif</option>
                <option value="Arsip">Arsip</option>
              </select>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea 
                v-model="form.description" 
                rows="3"
                class="form-input"
                placeholder="Deskripsi semester (opsional)"
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                <span v-if="saving">Menyimpan...</span>
                <span v-else>{{ editingSemester ? 'Simpan Perubahan' : 'Tambah Semester' }}</span>
              </button>
            </div>
          </form>
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
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const semesters = ref([])
const loading = ref(true)
const pagination = ref(null)
const filters = ref({
  search: '',
  academic_year_id: '',
  status: ''
})

const showAddModal = ref(false)
const showEditModal = ref(false)
const editingSemester = ref(null)
const saving = ref(false)
const error = ref('')

const form = ref({
  academic_year_id: '',
  name: '',
  order: 1,
  start_date: '',
  end_date: '',
  status: 'Draft',
  description: ''
})

const loadSemesters = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 15,
      ...filters.value
    }
    
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    const response = await semesterApi.getAll(params)
    semesters.value = response.data.data || []
    pagination.value = response.data.meta || null
  } catch (err) {
    toast.error('Gagal', 'Gagal memuat data semester')
    console.error('Failed to load semesters:', err)
  } finally {
    loading.value = false
  }
}

const editSemester = (semester) => {
  editingSemester.value = semester
  form.value = {
    academic_year_id: semester.academic_year_id || '',
    name: semester.name || '',
    order: semester.order || 1,
    start_date: semester.start_date || '',
    end_date: semester.end_date || '',
    status: semester.status || 'Draft',
    description: semester.description || ''
  }
  showEditModal.value = true
}

const activateSemester = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Aktifkan',
    message: 'Apakah Anda yakin ingin mengaktifkan semester ini?',
    warning: 'Semester aktif lainnya dalam tahun ajaran yang sama akan dinonaktifkan.'
  })
  
  if (!confirmed) return

  try {
    await semesterApi.activate(id)
    toast.success('Berhasil', 'Semester berhasil diaktifkan')
    loadSemesters()
  } catch (err) {
    const message = err.response?.data?.message || 'Gagal mengaktifkan semester'
    toast.error('Gagal', message)
  }
}

const deleteSemester = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus semester ini?')) {
    return
  }

  try {
    await semesterApi.delete(id)
    toast.success('Berhasil', 'Semester berhasil dihapus')
    loadSemesters()
  } catch (err) {
    const message = err.response?.data?.message || 'Gagal menghapus semester'
    toast.error('Gagal', message)
  }
}

const saveSemester = async () => {
  saving.value = true
  error.value = ''

  try {
    const data = { ...form.value }
    
    Object.keys(data).forEach(key => {
      if (data[key] === '') {
        data[key] = null
      }
    })

    if (editingSemester.value) {
      await semesterApi.update(editingSemester.value.id, data)
      toast.success('Berhasil', 'Semester berhasil diperbarui')
    } else {
      await semesterApi.create(data)
      toast.success('Berhasil', 'Semester berhasil ditambahkan')
    }

    closeModal()
    loadSemesters()
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data'
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const firstError = Object.values(errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    }
  } finally {
    saving.value = false
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingSemester.value = null
  error.value = ''
  form.value = {
    academic_year_id: '',
    name: '',
    order: 1,
    start_date: '',
    end_date: '',
    status: 'Draft',
    description: ''
  }
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
    'Aktif': 'status-badge status-active',
    'Arsip': 'status-badge status-archive',
    'Draft': 'status-badge status-draft'
  }
  return classes[status] || 'status-badge'
}

onMounted(() => {
  referenceStore.getAcademicYears()
  loadSemesters()
})
</script>

<style scoped>
.semester-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #1a202c;
}

.header-content p {
  font-size: 14px;
  color: #718096;
  margin: 0;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #5568d3;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  flex: 1;
  min-width: 200px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #718096;
}

.loading-spinner {
  margin-bottom: 16px;
}

.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f7fafc;
}

.data-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #4a5568;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #2d3748;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-edit {
  color: #48bb78;
}

.btn-edit:hover {
  background: #f0fff4;
}

.btn-activate {
  color: #f59e0b;
}

.btn-activate:hover {
  background: #fef3c7;
}

.btn-delete {
  color: #f56565;
}

.btn-delete:hover {
  background: #fff5f5;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-active {
  background: #c6f6d5;
  color: #22543d;
}

.status-archive {
  background: #e5e7eb;
  color: #374151;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.text-muted {
  color: #9ca3af;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
  color: #718096;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #e2e8f0;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #cbd5e0;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 14px;
  color: #718096;
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

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
  color: #1a202c;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  color: #718096;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #f7fafc;
  color: #2d3748;
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
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.required {
  color: #dc2626;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #718096;
}

.error-message {
  padding: 12px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  padding: 10px 20px;
  background: white;
  color: #374151;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f7fafc;
  border-color: #cbd5e0;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .btn-primary {
    width: 100%;
  }
}
</style>
