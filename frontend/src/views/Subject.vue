<template>
    <div class="subject-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            @input="debounceLoad"
            placeholder="Cari kode atau nama..."
            class="search-input"
          />
          <select v-model="filters.active_only" @change="onActiveFilterChange" class="filter-select">
            <option :value="true">Aktif saja</option>
            <option :value="false">Semua</option>
          </select>
        </div>
        <div class="toolbar-actions">
          <button @click="openAddModal" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Mata Pelajaran</span>
          </button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" :cell-widths="['80px', '160px', '1fr', '80px', '120px']" />
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th>Kode</th>
              <th>Nama</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in subjects" :key="item.id">
              <td class="col-no">{{ rowNumber(index) }}</td>
              <td>{{ displayValue(item.code) }}</td>
              <td>{{ displayValue(item.name) }}</td>
              <td>{{ displayValue(item.description) }}</td>
              <td>
                <span :class="item.is_active ? 'badge-success' : 'badge-muted'">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <TableAction kind="edit" @click="editSubject(item)" />
                  <TableAction kind="delete" @click="confirmDelete(item)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="subjects.length === 0" class="empty-state">
          <p>Belum ada mata pelajaran. Tambah data atau ubah filter.</p>
        </div>
        <PaginationBar
          v-if="pagination.total > 0"
          embedded
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="mapel"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</h3>
            <button @click="closeModal" class="modal-close">×</button>
          </div>
          <form @submit.prevent="save" class="modal-body">
            <div v-if="error" class="error-message">{{ error }}</div>
            <div class="form-group">
              <label>Kode <span class="required">*</span></label>
              <input v-model="form.code" type="text" required placeholder="Contoh: MAT, IPA" class="form-input" />
            </div>
            <div class="form-group">
              <label>Nama <span class="required">*</span></label>
              <input v-model="form.name" type="text" required placeholder="Matematika, IPA" class="form-input" />
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="2" class="form-input"></textarea>
            </div>
            <div class="form-group" v-if="editing">
              <label>Status</label>
              <select v-model="form.is_active" class="form-input">
                <option :value="true">Aktif</option>
                <option :value="false">Nonaktif</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="showConfirm"
        title="Hapus Mata Pelajaran"
        message="Yakin ingin menghapus mata pelajaran ini? Jika sudah dipakai di jadwal, penghapusan akan ditolak."
        confirmLabel="Hapus"
        @confirm="doDelete"
        @cancel="showConfirm = false; toDelete = null"
      />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { subjectApi } from '@/api/subject'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const subjects = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const showModal = ref(false)
const showConfirm = ref(false)
const editing = ref(null)
const toDelete = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = reactive({
  search: '',
  active_only: true,
})

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function rowNumber(index) {
  const page = Number(pagination.value.current_page) || 1
  const perPage = Number(pagination.value.per_page) || 15
  return (page - 1) * perPage + index + 1
}

const form = reactive({
  code: '',
  name: '',
  description: '',
  is_active: true,
})

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    pagination.value.current_page = 1
    loadSubjects()
  }, 300)
}

function goToPage(page) {
  pagination.value.current_page = page
  loadSubjects()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadSubjects()
}

function onActiveFilterChange() {
  pagination.value.current_page = 1
  loadSubjects()
}

async function loadSubjects() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      active_only: filters.active_only,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    }
    if (filters.search) params.search = filters.search
    const res = await subjectApi.getAll(params)
    subjects.value = res.data.data ?? res.data ?? []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? pagination.value.current_page,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? subjects.value.length,
    }
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editing.value = null
  form.code = ''
  form.name = ''
  form.description = ''
  form.is_active = true
  error.value = ''
  showModal.value = true
}

function editSubject(item) {
  editing.value = item
  form.code = item.code
  form.name = item.name
  form.description = item.description || ''
  form.is_active = item.is_active ?? true
  error.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value = null
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editing.value) {
      await subjectApi.update(editing.value.id, {
        code: form.code,
        name: form.name,
        description: form.description || null,
        is_active: form.is_active,
      })
    } else {
      await subjectApi.create({
        code: form.code,
        name: form.name,
        description: form.description || null,
        is_active: form.is_active,
      })
    }
    closeModal()
    loadSubjects()
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal menyimpan.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  toDelete.value = item
  showConfirm.value = true
}

async function doDelete() {
  if (!toDelete.value) return
  try {
    await subjectApi.delete(toDelete.value.id)
    showConfirm.value = false
    toDelete.value = null
    loadSubjects()
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal menghapus.'
  }
}

onMounted(loadSubjects)
</script>

<style scoped>
.subject-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.toolbar .filters { margin-bottom: 0; flex: 1; min-width: 200px; }
.toolbar-actions { display: flex; gap: 0.5rem; }
.btn-compact { display: inline-flex; align-items: center; gap: 0.5rem; }
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.action-buttons-group { display: flex; gap: 0.5rem; }
.filters-inline { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input { min-width: 200px; padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
.search-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .action-buttons-group {
    width: 100%;
  }

  .action-buttons-group .btn-primary,
  .toolbar-actions {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .filters,
  .filters-inline {
    flex-direction: column;
    align-items: stretch;
  }
  .search-input,
  .filter-select {
    min-width: 0;
    width: 100%;
  }
}
.loading-state { text-align: center; padding: 2rem; }
.table-container { overflow-x: auto; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; }
.col-no {
  width: 52px;
  text-align: center;
  white-space: nowrap;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}
.badge-success { background: #c6f6d5; color: #276749; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.badge-muted { background: #e2e8f0; color: #4a5568; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.action-buttons { display: flex; gap: 0.5rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 8px; max-width: 480px; width: 90%; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; }
.modal-body { padding: 1.25rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 500; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.form-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.error-message { color: #c53030; margin-bottom: 0.75rem; font-size: 0.875rem; }
.empty-state { padding: 1.5rem; text-align: center; color: #64748b; }
.btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-secondary { background: #e2e8f0; color: #2d3748; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }
.btn-action { padding: 0.35rem 0.6rem; font-size: 0.875rem; border-radius: 4px; cursor: pointer; border: none; }
.btn-edit { background: rgba(5, 150, 105, 0.12); color: #059669; }
.btn-delete { background: #fed7d7; color: #c53030; }
</style>
