<template>
  <Layout>
    <div class="digital-archive-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 8V21H3V8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 3H1V8H23V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M10 12H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Arsip Digital</h1>
            <p class="page-subtitle">Kelola dokumen digital: SK, sertifikat, laporan, dan arsip lainnya</p>
          </div>
          <div class="header-actions">
            <button type="button" @click="openCategoryModal" class="btn-secondary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Kategori</span>
            </button>
            <button type="button" @click="openAddModal" class="btn-primary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Dokumen</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="stat-body">
            <span class="stat-value">{{ pagination.total }}</span>
            <span class="stat-label">Total Dokumen</span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <div class="filters-inner">
          <div class="search-wrap">
            <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Cari judul, nomor referensi, atau nama file..."
              class="search-input"
              @input="debounceSearch"
            />
          </div>
          <select v-model="filters.category_id" class="filter-select" @change="loadList">
            <option value="">Semua Kategori</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-date" @change="loadList" />
          <input v-model="filters.date_to" type="date" class="filter-date" @change="loadList" />
          <button type="button" @click="resetFilters" class="btn-reset">Reset</button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="6" :cell-widths="['1fr', '120px', '100px', '100px', '120px', '100px']" />
      </div>

      <!-- Empty -->
      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 8V21H3V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 3H1V8H23V3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 12H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada dokumen</h3>
        <p class="empty-desc">Unggah dokumen pertama untuk mulai mengarsipkan.</p>
        <button type="button" @click="openAddModal" class="btn-primary btn-empty-cta">Tambah Dokumen</button>
      </div>

      <!-- Table -->
      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Judul / File</th>
              <th>Kategori</th>
              <th>Tanggal Dokumen</th>
              <th>Ukuran</th>
              <th>Diunggah oleh</th>
              <th class="col-actions">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in list" :key="item.id">
              <td>
                <div class="cell-title">
                  <span class="title-text">{{ item.title }}</span>
                  <span class="file-name">{{ item.file_name }}</span>
                </div>
              </td>
              <td>
                <span v-if="item.category?.name" class="badge badge-cat">{{ item.category.name }}</span>
                <span v-else class="text-muted">—</span>
              </td>
              <td>{{ formatDate(item.document_date) }}</td>
              <td>{{ item.formatted_file_size || '—' }}</td>
              <td>{{ item.creator?.name || '—' }}</td>
              <td class="col-actions">
                <div class="action-buttons">
                  <TableAction kind="download" @click="downloadFile(item)" />
                  <TableAction kind="edit" @click="openEditModal(item)" />
                  <TableAction kind="delete" @click="confirmDelete(item)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Dokumen -->
      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal-content form-modal">
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Dokumen' : 'Tambah Dokumen' }}</h3>
            <button type="button" @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitForm" class="modal-body">
            <div class="form-group">
              <label>Judul *</label>
              <input v-model="form.title" type="text" required maxlength="255" placeholder="Contoh: SK Pengangkatan 2024" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="2" placeholder="Deskripsi singkat (opsional)"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Kategori</label>
                <select v-model="form.digital_archive_category_id" class="form-select">
                  <option value="">— Pilih Kategori —</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal Dokumen</label>
                <input v-model="form.document_date" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>Nomor Referensi</label>
              <input v-model="form.reference_number" type="text" maxlength="80" placeholder="Contoh: SK/001/2024" />
            </div>
            <div class="form-group">
              <label>{{ editingItem ? 'File (kosongkan jika tidak ganti)' : 'File *' }}</label>
              <input
                ref="fileInputRef"
                type="file"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                class="file-input"
                @change="onFileSelect"
              />
              <p v-if="editingItem && form.file_name" class="form-hint">File saat ini: {{ form.file_name }}</p>
            </div>
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingItem ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah Kategori -->
      <div v-if="showCategoryModal" class="modal-overlay" @click.self="showCategoryModal = false">
        <div class="modal-content form-modal modal-sm">
          <div class="modal-header">
            <h3>Tambah Kategori</h3>
            <button type="button" @click="showCategoryModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitCategory" class="modal-body">
            <div class="form-group">
              <label>Nama Kategori *</label>
              <input v-model="categoryForm.name" type="text" required maxlength="100" placeholder="Contoh: Surat Keputusan" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="categoryForm.description" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="categoryError" class="form-error">{{ categoryError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showCategoryModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="categorySubmitting" class="btn-primary">
                {{ categorySubmitting ? 'Menyimpan...' : 'Tambah' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Dokumen"
        :message="deleteMessage"
        confirmText="Hapus"
        @confirm="doDelete"
        @cancel="deleteTarget = null"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import digitalArchiveApi from '@/api/digitalArchive'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const loading = ref(true)
const list = ref([])
const categories = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = ref({
  search: '',
  category_id: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const showCategoryModal = ref(false)
const editingItem = ref(null)
const fileInputRef = ref(null)
const selectedFile = ref(null)

const form = ref({
  title: '',
  description: '',
  digital_archive_category_id: '',
  document_date: '',
  reference_number: '',
  file_name: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const categoryForm = ref({ name: '', description: '' })
const categorySubmitting = ref(false)
const categoryError = ref('')

const deleteTarget = ref(null)

const deleteMessage = computed(() => {
  if (!deleteTarget.value) return ''
  return `Yakin menghapus dokumen "${deleteTarget.value.title}"? File tidak dapat dikembalikan.`
})

let searchTimeout = null
function debounceSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadList(), 400)
}

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadList() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      search: filters.value.search || undefined,
      category_id: filters.value.category_id || undefined,
      date_from: filters.value.date_from || undefined,
      date_to: filters.value.date_to || undefined,
    }
    const res = await digitalArchiveApi.list(params)
    list.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat arsip', e.formattedMessage || 'Data arsip tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const res = await digitalArchiveApi.getCategories()
    categories.value = res.data.data || []
  } catch {
    categories.value = []
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadList()
}

function resetFilters() {
  filters.value = { search: '', category_id: '', date_from: '', date_to: '' }
  pagination.value.current_page = 1
  loadList()
}

function openAddModal() {
  editingItem.value = null
  selectedFile.value = null
  form.value = {
    title: '',
    description: '',
    digital_archive_category_id: filters.value.category_id || '',
    document_date: new Date().toISOString().slice(0, 10),
    reference_number: '',
    file_name: '',
  }
  formError.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
  showFormModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  selectedFile.value = null
  form.value = {
    title: item.title,
    description: item.description || '',
    digital_archive_category_id: item.digital_archive_category_id || '',
    document_date: item.document_date || '',
    reference_number: item.reference_number || '',
    file_name: item.file_name || '',
  }
  formError.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
  showFormModal.value = true
}

function onFileSelect(e) {
  const file = e.target?.files?.[0]
  selectedFile.value = file || null
}

async function submitForm() {
  formError.value = ''
  if (!form.value.title?.trim()) {
    formError.value = 'Judul wajib diisi.'
    return
  }
  if (!editingItem.value && !selectedFile.value) {
    formError.value = 'File wajib diunggah.'
    return
  }

  formSubmitting.value = true
  try {
    const payload = {
      title: form.value.title.trim(),
      description: form.value.description?.trim() || null,
      digital_archive_category_id: form.value.digital_archive_category_id || null,
      document_date: form.value.document_date || null,
      reference_number: form.value.reference_number?.trim() || null,
    }
    if (editingItem.value) {
      if (selectedFile.value) payload.file = selectedFile.value
      await digitalArchiveApi.put(editingItem.value.id, payload)
      toast.success('Dokumen berhasil diperbarui')
    } else {
      payload.file = selectedFile.value
      await digitalArchiveApi.create(payload)
      toast.success('Dokumen berhasil diarsipkan')
    }
    showFormModal.value = false
    loadList()
  } catch (e) {
    formError.value = e.formattedMessage || 'Gagal menyimpan.'
  } finally {
    formSubmitting.value = false
  }
}

function openCategoryModal() {
  categoryForm.value = { name: '', description: '' }
  categoryError.value = ''
  showCategoryModal.value = true
}

async function submitCategory() {
  categoryError.value = ''
  if (!categoryForm.value.name?.trim()) {
    categoryError.value = 'Nama kategori wajib diisi.'
    return
  }
  categorySubmitting.value = true
  try {
    await digitalArchiveApi.createCategory({
      name: categoryForm.value.name.trim(),
      description: categoryForm.value.description?.trim() || null,
    })
    toast.success('Kategori berhasil ditambah')
    showCategoryModal.value = false
    loadCategories()
  } catch (e) {
    categoryError.value = e.formattedMessage || 'Gagal menambah kategori.'
  } finally {
    categorySubmitting.value = false
  }
}

async function downloadFile(item) {
  try {
    const res = await digitalArchiveApi.download(item.id)
    const blob = new Blob([res.data], { type: item.mime_type || 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = item.file_name || 'download'
    a.click()
    window.URL.revokeObjectURL(url)
    toast.success('Unduh dimulai')
  } catch (e) {
    toast.error('Gagal mengunduh dokumen', e.formattedMessage || 'Dokumen tidak dapat diunduh. Periksa koneksi dan coba lagi.')
  }
}

function confirmDelete(item) {
  deleteTarget.value = item
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await digitalArchiveApi.delete(deleteTarget.value.id)
    toast.success('Dokumen berhasil dihapus')
    deleteTarget.value = null
    loadList()
  } catch (e) {
    toast.error('Gagal menghapus dokumen', e.formattedMessage || 'Dokumen tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadCategories()
  loadList()
})

watch(showFormModal, (v) => {
  if (!v) {
    editingItem.value = null
    selectedFile.value = null
  }
})
</script>

<style scoped>
.digital-archive-page {
  padding: 0;
  max-width: 1280px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 20px;
}

.header-icon-wrap {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.header-icon {
  flex-shrink: 0;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
  letter-spacing: -0.02em;
}

.page-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 10px;
  margin-left: auto;
  flex-shrink: 0;
}

.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid transparent;
  transition: background 0.2s, color 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
  color: #fff;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border-color: #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
  color: #334155;
}

.stats-row {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(15, 118, 110, 0.1);
  color: #0f766e;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.stat-label {
  font-size: 0.8125rem;
  color: #64748b;
}

.filters-bar {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 18px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.filters-inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}

.search-wrap {
  flex: 1;
  min-width: 200px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 38px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.15);
}

.filter-select,
.filter-date {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  min-width: 140px;
}

.btn-reset {
  padding: 8px 14px;
  font-size: 0.8125rem;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
}

.btn-reset:hover {
  background: #f1f5f9;
  color: #475569;
}

.loading-wrap {
  width: 100%;
  margin-top: 16px;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-top: 16px;
}

.empty-icon {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #334155;
  margin: 0 0 8px 0;
}

.empty-desc {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0 0 20px 0;
}

.btn-empty-cta {
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
  color: #fff;
}

.table-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th {
  text-align: left;
  padding: 12px 16px;
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-actions {
  width: 120px;
  text-align: right;
}

.cell-title {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.title-text {
  font-weight: 500;
  color: #0f172a;
}

.file-name {
  font-size: 0.8125rem;
  color: #64748b;
}

.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-cat {
  background: rgba(15, 118, 110, 0.12);
  color: #0f766e;
}

.text-muted {
  color: #94a3b8;
}

.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

.btn-action {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, color 0.2s;
}

.btn-download:hover { background: #ecfdf5; color: #0f766e; }
.btn-edit:hover { background: #eff6ff; color: #059669; }
.btn-delete:hover { background: #fef2f2; color: #dc2626; }

.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  font-size: 0.8125rem;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-page {
  padding: 6px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  font-size: 0.8125rem;
  color: #475569;
}

.btn-page:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
}

.btn-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  color: #64748b;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #334155;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 6px;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea,
.form-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
}

.form-group input:focus,
.form-group textarea:focus,
.form-select:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.15);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.file-input {
  width: 100%;
  padding: 8px 0;
  font-size: 0.875rem;
}

.form-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 6px;
}

.form-error {
  font-size: 0.8125rem;
  color: #dc2626;
  margin-bottom: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
  margin-top: 8px;
}

@media (max-width: 1024px) {
  .header-content { flex-direction: column; align-items: stretch; }
  .header-actions { margin-left: 0; width: 100%; }
}

@media (max-width: 768px) {
  .filters-inner { flex-direction: column; }
  .search-wrap { min-width: 100%; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
