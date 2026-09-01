<template>    <div class="audit-log-page">
      <div class="tab-header">
        <div class="filters filters-inline"></div>
        <button
          type="button"
          class="btn-primary btn-compact"
          :disabled="exporting"
          @click="handleExport"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
        </button>
      </div>

      <!-- Filters -->
      <div class="filters filters-inline">
        <div class="filter-group">
          <label>User</label>
          <select v-model="filters.user_id" @change="loadLogs" class="filter-select">
            <option value="">Semua User</option>
            <option v-for="u in filterOptions.users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Tanggal dari</label>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadLogs" />
        </div>
        <div class="filter-group">
          <label>Tanggal sampai</label>
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadLogs" />
        </div>
        <div class="filter-group">
          <label>Modul</label>
          <select v-model="filters.module" @change="loadLogs" class="filter-select">
            <option value="">Semua Modul</option>
            <option v-for="m in filterOptions.modules" :key="m" :value="m">{{ m }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Aksi</label>
          <select v-model="filters.action" @change="loadLogs" class="filter-select">
            <option value="">Semua Aksi</option>
            <option v-for="a in filterOptions.actions" :key="a" :value="a">{{ actionLabel(a) }}</option>
          </select>
        </div>
        <button type="button" class="btn-secondary btn-compact" @click="resetFilters">Reset Filter</button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="5" :cell-widths="['140px', '120px', '100px', '80px', '1fr']" />
      </div>

      <div v-else-if="logs.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada data audit log</h3>
        <p class="empty-desc">Belum ada aktivitas tercatat atau tidak ada hasil untuk filter yang dipilih.</p>
        <button type="button" class="btn-primary btn-empty-cta" @click="resetFilters">Tampilkan Semua</button>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>User</th>
              <th>Modul</th>
              <th>Aksi</th>
              <th>Deskripsi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ formatDateTime(log.created_at) }}</td>
              <td>{{ log.user_name || '-' }}</td>
              <td><span class="module-badge">{{ log.module || log.auditable_type }}</span></td>
              <td><span class="action-badge">{{ actionLabel(log.action) }}</span></td>
              <td class="desc-cell">{{ log.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationBar
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="log"
        @page-change="goToPage"
        @per-page-change="changePerPage"
      />
    </div></template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { auditLogApi } from '@/api/auditLog'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const logs = ref([])
const loading = ref(true)
const exporting = ref(false)
const filterOptions = ref({ users: [], modules: [], actions: [] })
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const filters = ref({
  user_id: '',
  date_from: '',
  date_to: '',
  module: '',
  action: ''
})

function actionLabel(action) {
  const labels = {
    created: 'Tambah',
    updated: 'Ubah',
    deleted: 'Hapus',
    'module_access.updated': 'Akses modul',
    'institution_modules.updated': 'Modul sekolah'
  }
  return labels[action] || action
}

function formatDateTime(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function buildParams(page = 1) {
  const params = { page, per_page: meta.value.per_page || 15 }
  if (filters.value.user_id) params.user_id = filters.value.user_id
  if (filters.value.date_from) params.date_from = filters.value.date_from
  if (filters.value.date_to) params.date_to = filters.value.date_to
  if (filters.value.module) params.module = filters.value.module
  if (filters.value.action) params.action = filters.value.action
  return params
}

async function loadFilterOptions() {
  try {
    const res = await auditLogApi.getFilterOptions()
    filterOptions.value = {
      users: res.data?.users ?? [],
      modules: res.data?.modules ?? [],
      actions: res.data?.actions ?? []
    }
  } catch {
    filterOptions.value = { users: [], modules: [], actions: [] }
  }
}

async function loadLogs(page = 1) {
  loading.value = true
  try {
    const res = await auditLogApi.getList(buildParams(page))
    logs.value = res.data?.data ?? []
    const m = res.data?.meta ?? {}
    meta.value = {
      current_page: m.current_page ?? 1,
      last_page: m.last_page ?? 1,
      per_page: m.per_page ?? meta.value.per_page,
      total: m.total ?? 0
    }
  } catch {
    logs.value = []
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  loadLogs(page)
}

function changePerPage(n) {
  meta.value.per_page = n
  loadLogs(1)
}

function resetFilters() {
  filters.value = { user_id: '', date_from: '', date_to: '', module: '', action: '' }
  loadLogs(1)
}

async function handleExport() {
  exporting.value = true
  try {
    const params = buildParams(1)
    delete params.page
    delete params.per_page
    const res = await auditLogApi.exportCsv(params)
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data])
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `audit-log-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export failed', err)
    const msg = err?.response?.data?.message || err?.formattedMessage || 'Log audit tidak dapat diekspor ke CSV. Periksa koneksi dan coba lagi.'
    toast.error('Gagal mengekspor log audit', msg)
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  await loadFilterOptions()
  loadLogs(1)
})
</script>

<style scoped>
.audit-log-page {
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.tab-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1.25rem;
}

.tab-header .filters-inline {
  flex: 1;
  margin-bottom: 0;
}

.page-header {
  margin-bottom: 1.5rem;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-icon-wrap {
  flex-shrink: 0;
}

.header-icon {
  color: var(--color-primary, #059669);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.header-actions {
  margin-left: auto;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
  margin-bottom: 1.5rem;
}

.filters-inline .filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filters-inline .filter-group label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  min-width: 140px;
}

.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.loading-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #64748b;
}

.loading-spinner {
  margin: 0 auto 1rem;
}

.loading-spinner svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  margin-bottom: 1rem;
  color: #94a3b8;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}

.empty-desc {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0 0 1rem 0;
}

.btn-empty-cta {
  margin-top: 0.5rem;
}

.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: white;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #0f172a;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.desc-cell {
  max-width: 320px;
}

.module-badge, .action-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.module-badge {
  background: #ecfdf5;
  color: #047857;
}

.action-badge {
  background: #dbeafe;
  color: #1e40af;
}

.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
  padding: 0.75rem 0;
  font-size: 0.875rem;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-page {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #475569;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-page:hover:not(:disabled) {
  background: #f8fafc;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-num {
  color: #64748b;
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
  }

  .tab-header .filters-inline {
    width: 100%;
    flex-direction: column;
    flex-wrap: nowrap;
    overflow: visible;
  }

  .filters-inline .filter-group {
    width: 100%;
  }

  .filters-inline .filter-group .filter-select,
  .filters-inline .filter-group input {
    width: 100%;
  }

  .tab-header .btn-primary,
  .tab-header .btn-compact {
    width: 100%;
    justify-content: center;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .data-table {
    min-width: 640px;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.25rem;
  }
}
</style>
