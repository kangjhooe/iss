<template>
  <div class="keuangan-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Pengeluaran</h1>
            <p class="page-subtitle">Gaji pegawai tercatat otomatis saat proses gaji ditandai dibayar</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-secondary" :disabled="exportingCsv" @click="doExport">
            {{ exportingCsv ? 'Mengekspor...' : 'Export CSV' }}
          </button>
          <button v-if="canManage" type="button" class="btn-header-primary" @click="openModal()">+ Catat pengeluaran</button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card filters-bar">
        <div class="filter-field">
          <label class="filter-label">Dari</label>
          <input v-model="filters.from" type="date" class="filter-input" @change="reloadFromStart" />
        </div>
        <div class="filter-field">
          <label class="filter-label">Sampai</label>
          <input v-model="filters.to" type="date" class="filter-input" @change="reloadFromStart" />
        </div>
        <div class="filter-field">
          <label class="filter-label">Kategori</label>
          <select v-model="filters.category" class="filter-select" @change="reloadFromStart">
            <option value="">Semua</option>
            <option v-for="o in expenseCategoryOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </div>
        <div class="filter-field">
          <label class="filter-label">Sumber</label>
          <select v-model="filters.source" class="filter-select" @change="reloadFromStart">
            <option value="">Semua</option>
            <option v-for="o in expenseSourceOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </div>
        <div class="filter-field" style="flex:1;min-width:160px">
          <label class="filter-label">Cari</label>
          <input v-model="filters.search" class="filter-input" placeholder="Judul / referensi" @keyup.enter="reloadFromStart" />
        </div>
        <button type="button" class="btn-secondary" @click="reloadFromStart">Terapkan</button>
      </div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="6" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada pengeluaran</h3>
          <p>Gaji yang ditandai dibayar di modul Penggajian akan muncul di sini secara otomatis.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Sumber</th>
                <th class="num">Nominal</th>
                <th>Metode</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td>{{ formatDate(row.expense_date) }}</td>
                <td>
                  <strong>{{ row.title }}</strong>
                  <div v-if="row.payroll_run" class="muted" style="font-size:0.8rem">
                    Proses: {{ row.payroll_run.label || `#${row.payroll_run.id}` }}
                    <span v-if="row.payroll_run.period?.label"> · {{ row.payroll_run.period.label }}</span>
                  </div>
                  <div v-else-if="row.notes" class="muted" style="font-size:0.8rem">{{ row.notes }}</div>
                </td>
                <td>{{ expenseCategoryLabel(row.category) }}</td>
                <td>
                  <span :class="['pill', row.source === 'auto' ? 'pill-on' : 'pill-off']">
                    {{ expenseSourceLabel(row.source) }}
                  </span>
                </td>
                <td class="num">{{ formatRp(row.amount) }}</td>
                <td>{{ methodLabel(row.method) }}</td>
                <td class="table-actions">
                  <TableAction v-if="canManage && row.source === 'manual'" kind="delete" @click="remove(row)" />
                  <span v-else class="muted" style="font-size:0.8rem">—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            :page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="meta.per_page"
            :total="meta.total"
            item-label="pengeluaran"
            @page-change="goPage"
            @per-page-change="changePerPage"
          />
        </div>
      </div>
    </main>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card">
        <h3>Catat pengeluaran manual</h3>
        <div v-if="modalError" class="modal-error">{{ modalError }}</div>
        <form @submit.prevent="save">
          <div class="form-grid">
            <div class="form-group full">
              <label>Judul *</label>
              <input v-model="form.title" class="form-input" required maxlength="255" placeholder="Contoh: Listrik, ATK, konsumsi rapat" />
            </div>
            <div class="form-group">
              <label>Nominal *</label>
              <MoneyInput v-model="form.amount" :min="1" class="form-input" required />
            </div>
            <div class="form-group">
              <label>Tanggal</label>
              <input v-model="form.expense_date" type="date" class="form-input" />
            </div>
            <div class="form-group">
              <label>Metode</label>
              <select v-model="form.method" class="form-select">
                <option v-for="o in paymentMethodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
            <div class="form-group full">
              <label>Referensi</label>
              <input v-model="form.reference" class="form-input" placeholder="No. bukti / transfer" />
            </div>
            <div class="form-group full">
              <label>Catatan</label>
              <textarea v-model="form.notes" class="form-textarea" rows="2" />
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import MoneyInput from '@/components/MoneyInput.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { useAuthStore } from '@/stores/auth'
import { financeExpenseApi } from '@/api/finance'
import { hasModuleAccess } from '@/utils/moduleAccess'
import {
  formatRp,
  paymentMethodOptions,
  expenseCategoryOptions,
  expenseSourceOptions,
  expenseCategoryLabel,
  expenseSourceLabel,
  methodLabel,
} from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const authStore = useAuthStore()
const canManage = computed(() => hasModuleAccess(authStore.user, 'finance'))
const usePayrollApi = computed(() => !canManage.value && hasModuleAccess(authStore.user, 'payroll'))

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const exportingCsv = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const meta = reactive({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const filters = reactive({ from: '', to: '', category: '', source: '', search: '', page: 1 })
const form = reactive({
  title: '',
  amount: null,
  expense_date: new Date().toISOString().slice(0, 10),
  method: 'cash',
  reference: '',
  notes: '',
})

function formatDate(iso) {
  if (!iso) return '—'
  const raw = String(iso).slice(0, 10)
  const [y, m, d] = raw.split('-').map(Number)
  if (!y || !m || !d) return '—'
  return new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = { per_page: meta.per_page, page: filters.page }
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    if (filters.category) params.category = filters.category
    if (filters.source) params.source = filters.source
    if (filters.search) params.search = filters.search
    const res = await financeExpenseApi.getAll(params, { payroll: usePayrollApi.value })
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat pengeluaran.')
  } finally {
    loading.value = false
  }
}

function openModal() {
  modalError.value = ''
  Object.assign(form, {
    title: '',
    amount: null,
    expense_date: new Date().toISOString().slice(0, 10),
    method: 'cash',
    reference: '',
    notes: '',
  })
  showModal.value = true
}

async function save() {
  if (!form.amount || Number(form.amount) <= 0) {
    modalError.value = 'Nominal harus lebih dari 0.'
    return
  }
  saving.value = true
  modalError.value = ''
  try {
    await financeExpenseApi.create({ ...form })
    success.value = 'Pengeluaran dicatat.'
    showModal.value = false
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`Hapus pengeluaran "${row.title}"?`)) return
  try {
    await financeExpenseApi.delete(row.id)
    success.value = 'Pengeluaran dihapus.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menghapus.')
  }
}

function goPage(page) {
  filters.page = page
  load()
}

function changePerPage(n) {
  meta.per_page = n
  filters.page = 1
  load()
}

function reloadFromStart() {
  filters.page = 1
  load()
}

async function doExport() {
  exportingCsv.value = true
  error.value = ''
  try {
    await financeExpenseApi.export({
      from: filters.from || undefined,
      to: filters.to || undefined,
      category: filters.category || undefined,
      source: filters.source || undefined,
      search: filters.search || undefined,
    }, { payroll: usePayrollApi.value })
    success.value = 'Export pengeluaran diunduh.'
  } catch (e) {
    error.value = apiError(e, 'Gagal export.')
  } finally {
    exportingCsv.value = false
  }
}

onMounted(load)
</script>
