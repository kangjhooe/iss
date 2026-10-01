<template>
  <div class="subject-catalog-page">
    <div class="page-intro">
      <p>
        Katalog mata pelajaran standar aplikasi. Kode 4 digit:
        <strong>1</strong>xxx SD/MI,
        <strong>2</strong>xxx SMP/MTs,
        <strong>3</strong>xxx SMA/MA,
        <strong>4</strong>xxx SMK/MAK,
        <strong>5</strong>xxx PAUD/TK.
      </p>
    </div>

    <div class="toolbar">
      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          @input="debounceLoad"
          placeholder="Cari kode atau nama..."
          class="search-input"
        />
        <select v-model="filters.jenjang" @change="onFilterChange" class="filter-select">
          <option value="">Semua jenjang</option>
          <option v-for="opt in jenjangOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }} ({{ opt.code_prefix }}xxx)
          </option>
        </select>
        <select v-model="filters.active_only" @change="onFilterChange" class="filter-select">
          <option :value="true">Aktif saja</option>
          <option :value="false">Semua</option>
        </select>
      </div>
      <div class="toolbar-actions">
        <button @click="openAddModal" class="btn-primary btn-compact">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Tambah Mapel</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="6" :columns="6" :cell-widths="['60px', '90px', '1fr', '110px', '80px', '100px']" />
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Jenjang</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="item.id">
            <td class="col-no">{{ rowNumber(index) }}</td>
            <td><strong>{{ displayValue(item.code) }}</strong></td>
            <td>{{ displayValue(item.name) }}</td>
            <td>{{ displayValue(item.jenjang_label || item.jenjang) }}</td>
            <td>
              <span :class="item.is_active ? 'badge-success' : 'badge-muted'">
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <TableAction kind="edit" @click="editItem(item)" />
                <TableAction kind="delete" @click="confirmDelete(item)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="items.length === 0" class="empty-state">
        <p>Belum ada data katalog. Tambah mata pelajaran standar lewat tombol di atas.</p>
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

    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editing ? 'Edit Mata Pelajaran Katalog' : 'Tambah Mata Pelajaran Katalog' }}</h3>
          <button @click="closeModal" class="modal-close" type="button">×</button>
        </div>
        <form @submit.prevent="save" class="modal-body">
          <div v-if="error" class="error-message">{{ error }}</div>

          <div class="form-group">
            <label>Jenjang <span class="required">*</span></label>
            <select v-model="form.jenjang" required class="form-input" @change="onJenjangChange">
              <option value="" disabled>Pilih jenjang</option>
              <option v-for="opt in jenjangOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }} — kode {{ opt.code_prefix }}xxx
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Kode <span class="required">*</span></label>
            <input
              v-model="form.code"
              type="text"
              required
              maxlength="4"
              pattern="\d{4}"
              :placeholder="codePlaceholder"
              class="form-input"
            />
            <small class="form-hint">{{ codeHint }}</small>
          </div>

          <div class="form-group">
            <label>Nama <span class="required">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Contoh: Bahasa Indonesia"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Deskripsi</label>
            <textarea v-model="form.description" rows="2" class="form-input" placeholder="Opsional"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Urutan</label>
              <input v-model.number="form.sort_order" type="number" min="0" class="form-input" />
            </div>
            <div class="form-group" v-if="editing">
              <label>Status</label>
              <select v-model="form.is_active" class="form-input">
                <option :value="true">Aktif</option>
                <option :value="false">Nonaktif</option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmDialog
      :show="showConfirm"
      title="Hapus Mata Pelajaran Katalog"
      message="Yakin ingin menghapus mapel ini dari katalog global?"
      confirm-text="Hapus"
      @confirm="doDelete"
      @cancel="showConfirm = false; toDelete = null"
      @update:show="showConfirm = $event"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { subjectCatalogApi } from '@/api/subjectCatalog'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const showModal = ref(false)
const showConfirm = ref(false)
const editing = ref(null)
const toDelete = ref(null)
const jenjangOptions = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = reactive({
  search: '',
  jenjang: '',
  active_only: true,
})

const form = reactive({
  code: '',
  name: '',
  jenjang: '',
  description: '',
  is_active: true,
  sort_order: 0,
})

const selectedJenjangOpt = computed(() =>
  jenjangOptions.value.find((o) => o.value === form.jenjang) || null
)

const codePlaceholder = computed(() => selectedJenjangOpt.value?.code_hint || '1001')

const codeHint = computed(() => {
  const opt = selectedJenjangOpt.value
  if (!opt) return 'Pilih jenjang dulu. Contoh: 1001 Bahasa Indonesia (SD/MI).'
  return `Untuk ${opt.label}, kode harus diawali ${opt.code_prefix}. Contoh: ${opt.code_hint}`
})

function displayValue(v) {
  if (v === null || v === undefined || v === '') return '—'
  return String(v).trim() || '—'
}

function rowNumber(index) {
  const page = Number(pagination.value.current_page) || 1
  const perPage = Number(pagination.value.per_page) || 15
  return (page - 1) * perPage + index + 1
}

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    pagination.value.current_page = 1
    loadItems()
  }, 300)
}

function onFilterChange() {
  pagination.value.current_page = 1
  loadItems()
}

function goToPage(page) {
  pagination.value.current_page = page
  loadItems()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadItems()
}

function onJenjangChange() {
  const opt = selectedJenjangOpt.value
  if (!opt) return
  // Prefill prefix when adding new and code empty / wrong prefix
  if (!editing.value) {
    if (!form.code || form.code.length < 1 || form.code[0] !== opt.code_prefix) {
      form.code = opt.code_hint
    }
  }
}

async function loadMeta() {
  try {
    const res = await subjectCatalogApi.getMeta()
    jenjangOptions.value = res.data?.data?.jenjang_options || []
  } catch {
    jenjangOptions.value = [
      { value: 'SD', label: 'SD/MI', code_prefix: '1', code_hint: '1001' },
      { value: 'SMP', label: 'SMP/MTs', code_prefix: '2', code_hint: '2001' },
      { value: 'SMA', label: 'SMA/MA', code_prefix: '3', code_hint: '3001' },
      { value: 'SMK', label: 'SMK/MAK', code_prefix: '4', code_hint: '4001' },
      { value: 'PAUD', label: 'PAUD/TK', code_prefix: '5', code_hint: '5001' },
    ]
  }
}

async function loadItems() {
  loading.value = true
  error.value = ''
  try {
    const params = {
      active_only: filters.active_only,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    }
    if (filters.search) params.search = filters.search
    if (filters.jenjang) params.jenjang = filters.jenjang
    const res = await subjectCatalogApi.getAll(params)
    items.value = res.data.data ?? res.data ?? []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? pagination.value.current_page,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? items.value.length,
    }
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal memuat data.'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  editing.value = null
  form.code = ''
  form.name = ''
  form.jenjang = filters.jenjang || ''
  form.description = ''
  form.is_active = true
  form.sort_order = 0
  error.value = ''
  if (form.jenjang) onJenjangChange()
  showModal.value = true
}

function editItem(item) {
  editing.value = item
  form.code = item.code
  form.name = item.name
  form.jenjang = item.jenjang
  form.description = item.description || ''
  form.is_active = item.is_active ?? true
  form.sort_order = item.sort_order ?? 0
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
    const payload = {
      code: String(form.code || '').trim(),
      name: String(form.name || '').trim(),
      jenjang: form.jenjang,
      description: form.description || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: form.is_active,
    }
    if (editing.value) {
      await subjectCatalogApi.update(editing.value.id, payload)
      toast.success('Katalog mapel diperbarui')
    } else {
      await subjectCatalogApi.create(payload)
      toast.success('Katalog mapel ditambahkan')
    }
    closeModal()
    loadItems()
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
    await subjectCatalogApi.delete(toDelete.value.id)
    toast.success('Katalog mapel dihapus')
    showConfirm.value = false
    toDelete.value = null
    loadItems()
  } catch (e) {
    toast.error(e.formattedMessage || e.message || 'Gagal menghapus.')
  }
}

onMounted(async () => {
  await loadMeta()
  await loadItems()
})
</script>

<style scoped>
.subject-catalog-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.page-intro {
  margin-bottom: 1rem;
  padding: 0.85rem 1rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 8px;
  color: #065f46;
  font-size: 0.9rem;
  line-height: 1.45;
}
.page-intro p { margin: 0; }
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
.filters-inline { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.search-input { min-width: 200px; padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; }
.search-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.loading-wrap { margin-top: 0.5rem; }
.table-container { overflow-x: auto; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; }
.col-no { width: 52px; text-align: center; color: #64748b; font-variant-numeric: tabular-nums; }
.badge-success { background: #c6f6d5; color: #276749; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.badge-muted { background: #e2e8f0; color: #4a5568; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.action-buttons { display: flex; gap: 0.5rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 8px; max-width: 520px; width: 90%; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; line-height: 1; }
.modal-body { padding: 1.25rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 500; }
.required { color: #c53030; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
.form-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.form-hint { display: block; margin-top: 0.35rem; color: #64748b; font-size: 0.8rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.error-message { color: #c53030; margin-bottom: 0.75rem; font-size: 0.875rem; }
.empty-state { padding: 1.5rem; text-align: center; color: #64748b; }
.btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-secondary { background: #e2e8f0; color: #2d3748; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }

@media (max-width: 768px) {
  .filters-inline { flex-direction: column; align-items: stretch; }
  .search-input, .filter-select { min-width: 0; width: 100%; }
  .form-row { grid-template-columns: 1fr; }
  .toolbar-actions { width: 100%; }
  .toolbar-actions .btn-primary { width: 100%; justify-content: center; }
}
</style>
