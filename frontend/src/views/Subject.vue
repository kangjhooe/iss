<template>
  <Layout>
    <div class="subject-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Mata Pelajaran</h2>
            <p>Kelola master mata pelajaran untuk jadwal</p>
          </div>
          <div class="action-buttons-group">
            <button @click="openAddModal" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Mata Pelajaran</span>
            </button>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          @input="debounceLoad"
          placeholder="Cari kode atau nama..."
          class="search-input"
        />
        <select v-model="filters.active_only" @change="loadSubjects" class="filter-select">
          <option :value="true">Aktif saja</option>
          <option :value="false">Semua</option>
        </select>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner"></div>
        <p>Memuat data...</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in subjects" :key="item.id">
              <td>{{ item.code }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.description || '-' }}</td>
              <td>
                <span :class="item.is_active ? 'badge-success' : 'badge-muted'">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="editSubject(item)" class="btn-action btn-edit" title="Edit">Edit</button>
                  <button @click="confirmDelete(item)" class="btn-action btn-delete" title="Hapus">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="subjects.length === 0" class="empty-state">
          <p>Belum ada mata pelajaran. Tambah data atau ubah filter.</p>
        </div>
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
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
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

const filters = reactive({
  search: '',
  active_only: true,
})

const form = reactive({
  code: '',
  name: '',
  description: '',
  is_active: true,
})

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(loadSubjects, 300)
}

async function loadSubjects() {
  loading.value = true
  error.value = ''
  try {
    const params = { active_only: filters.active_only }
    if (filters.search) params.search = filters.search
    const res = await subjectApi.getAll(params)
    subjects.value = res.data.data ?? res.data ?? []
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
.subject-page { padding: 1.5rem; }
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.action-buttons-group { display: flex; gap: 0.5rem; }
.filters-inline { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input { min-width: 200px; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.loading-state { text-align: center; padding: 2rem; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { background: #f7fafc; font-weight: 600; }
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
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.error-message { color: #c53030; margin-bottom: 0.75rem; font-size: 0.875rem; }
.empty-state { padding: 1.5rem; text-align: center; color: #718096; }
.btn-primary { background: #3182ce; color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #2d3748; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }
.btn-action { padding: 0.35rem 0.6rem; font-size: 0.875rem; border-radius: 4px; cursor: pointer; border: none; }
.btn-edit { background: #ebf8ff; color: #2b6cb0; }
.btn-delete { background: #fed7d7; color: #c53030; }
</style>
