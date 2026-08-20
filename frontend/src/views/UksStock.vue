<template>
  <Layout>
    <div class="uks-page">
      <div class="toolbar">
        <div class="main-tabs">
          <button type="button" :class="['main-tab', { active: activeTab === 'stock' }]" @click="activeTab = 'stock'; loadMedicines()">
            <span>Stok Obat</span>
          </button>
          <button type="button" :class="['main-tab', { active: activeTab === 'tx' }]" @click="activeTab = 'tx'; loadTransactions()">
            <span>Riwayat Transaksi</span>
          </button>
        </div>
        <div class="header-actions">
          <button v-if="activeTab === 'stock'" type="button" class="btn-secondary btn-compact" @click="openTxModal()">
            <span>Catat Masuk/Keluar</span>
          </button>
          <button v-if="activeTab === 'stock'" type="button" class="btn-primary btn-compact" @click="openAddModal">
            <span>Tambah Obat</span>
          </button>
        </div>
      </div>

      <div class="uks-dashboard stock-stats">
        <div class="stat-card">
          <span class="stat-label">Item aktif</span>
          <span class="stat-value">{{ stockSummary?.active_items ?? '-' }}</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total stok</span>
          <span class="stat-value">{{ stockSummary?.total_quantity ?? '-' }}</span>
        </div>
        <div class="stat-card warn">
          <span class="stat-label">Stok menipis</span>
          <span class="stat-value">{{ stockSummary?.low_stock ?? '-' }}</span>
        </div>
        <div class="stat-card danger">
          <span class="stat-label">Kedaluwarsa</span>
          <span class="stat-value">{{ stockSummary?.expired ?? '-' }}</span>
        </div>
      </div>

      <template v-if="activeTab === 'stock'">
        <div class="filters filters-inline">
          <input v-model="filters.search" type="text" class="search-input" placeholder="Cari nama / kode obat..." @input="debounceLoad" />
          <select v-model="filters.is_active" class="filter-select" @change="loadMedicines">
            <option value="">Semua status</option>
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
          <label class="check-filter">
            <input v-model="filters.low_stock" type="checkbox" @change="loadMedicines" />
            Stok menipis
          </label>
          <label class="check-filter">
            <input v-model="filters.expired" type="checkbox" @change="loadMedicines" />
            Kedaluwarsa
          </label>
        </div>

        <div v-if="loading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="7" />
        </div>
        <div v-else-if="!medicines.length" class="empty-state">
          <h3 class="empty-title">Belum ada stok obat UKS</h3>
          <p class="empty-desc">Tambah master obat, lalu catat masuk/keluar stok saat dipakai.</p>
          <button type="button" class="btn-primary" @click="openAddModal">Tambah Obat</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kode</th>
                <th>Stok</th>
                <th>Min.</th>
                <th>Kedaluwarsa</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in medicines" :key="m.id" :class="{ 'row-warn': m.is_low_stock, 'row-danger': m.is_expired }">
                <td>
                  <span class="student-name">{{ m.name }}</span>
                  <span v-if="m.description" class="student-meta">{{ truncate(m.description, 50) }}</span>
                </td>
                <td>{{ m.code || '-' }}</td>
                <td>
                  <strong>{{ m.quantity }}</strong> {{ m.unit || 'pcs' }}
                  <span v-if="m.is_low_stock" class="tag tag-warn">Menipis</span>
                </td>
                <td>{{ m.min_stock != null ? m.min_stock : '-' }}</td>
                <td>
                  {{ formatDate(m.expiry_date) }}
                  <span v-if="m.is_expired" class="tag tag-danger">Expired</span>
                </td>
                <td>
                  <span :class="['status-badge', m.is_active ? 'status-selesai' : 'status-rujuk']">
                    {{ m.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <TableAction kind="transaction" title="Transaksi" @click="openTxModal(m)" />
                    <TableAction kind="edit" @click="openEditModal(m)" />
                    <TableAction kind="delete" @click="deleteTarget = m" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">Halaman {{ pagination.current_page }} / {{ pagination.last_page }} · {{ pagination.total }} data</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <template v-else>
        <div class="filters filters-inline">
          <select v-model="txFilters.uks_medicine_id" class="filter-select" @change="loadTransactions">
            <option value="">Semua obat</option>
            <option v-for="m in allMedicines" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
          <select v-model="txFilters.type" class="filter-select" @change="loadTransactions">
            <option value="">Semua jenis</option>
            <option value="masuk">Masuk</option>
            <option value="keluar">Keluar</option>
            <option value="penyesuaian">Penyesuaian</option>
          </select>
          <input v-model="txFilters.date_from" type="date" class="filter-select" @change="loadTransactions" />
          <input v-model="txFilters.date_to" type="date" class="filter-select" @change="loadTransactions" />
        </div>

        <div v-if="txLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="8" :columns="6" />
        </div>
        <div v-else-if="!transactions.length" class="empty-state">
          <h3 class="empty-title">Belum ada transaksi</h3>
          <p class="empty-desc">Catat masuk, keluar, atau penyesuaian stok obat.</p>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Obat</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Petugas</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in transactions" :key="t.id">
                <td>{{ formatDate(t.transaction_date) }}</td>
                <td>{{ t.medicine?.name || '-' }}</td>
                <td><span :class="['status-badge', 'tx-' + t.type]">{{ t.type_label || t.type }}</span></td>
                <td>{{ t.quantity }} {{ t.medicine?.unit || '' }}</td>
                <td>{{ t.creator?.name || '-' }}</td>
                <td>{{ t.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="txPagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">Halaman {{ txPagination.current_page }} / {{ txPagination.last_page }}</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="txPagination.current_page <= 1" @click="goToTxPage(txPagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="txPagination.current_page >= txPagination.last_page" @click="goToTxPage(txPagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal-card modal-card-sm">
          <h3>{{ editingMedicine ? 'Edit Obat UKS' : 'Tambah Obat UKS' }}</h3>
          <p v-if="formError" class="form-error">{{ formError }}</p>
          <form class="form-grid" @submit.prevent="submitForm">
            <label class="full">Nama *
              <input v-model="form.name" required />
            </label>
            <label>Kode
              <input v-model="form.code" />
            </label>
            <label>Satuan
              <input v-model="form.unit" placeholder="pcs / strip / botol" />
            </label>
            <label v-if="!editingMedicine">Stok awal
              <input v-model.number="form.quantity" type="number" min="0" />
            </label>
            <label>Stok minimum
              <input v-model.number="form.min_stock" type="number" min="0" />
            </label>
            <label>Kedaluwarsa
              <input v-model="form.expiry_date" type="date" />
            </label>
            <label>Aktif
              <select v-model="form.is_active">
                <option :value="true">Ya</option>
                <option :value="false">Tidak</option>
              </select>
            </label>
            <label class="full">Deskripsi
              <textarea v-model="form.description" rows="2" />
            </label>
            <div class="modal-actions full">
              <button type="button" class="btn-secondary" @click="showFormModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="formSubmitting">{{ formSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showTxModal" class="modal-overlay" @click.self="showTxModal = false">
        <div class="modal-card modal-card-sm">
          <h3>Catat Transaksi Stok</h3>
          <p v-if="txFormError" class="form-error">{{ txFormError }}</p>
          <form class="form-grid" @submit.prevent="submitTx">
            <label class="full">Obat *
              <select v-model="txForm.uks_medicine_id" required>
                <option value="">Pilih obat</option>
                <option v-for="m in activeMedicines" :key="m.id" :value="m.id">
                  {{ m.name }} (stok: {{ m.quantity }} {{ m.unit }})
                </option>
              </select>
            </label>
            <label>Jenis *
              <select v-model="txForm.type" required>
                <option value="masuk">Masuk</option>
                <option value="keluar">Keluar</option>
                <option value="penyesuaian">Penyesuaian (set stok)</option>
              </select>
            </label>
            <label>Jumlah *
              <input v-model.number="txForm.quantity" type="number" min="0" required />
            </label>
            <label class="full">Tanggal *
              <input v-model="txForm.transaction_date" type="date" required />
            </label>
            <label class="full">Catatan
              <textarea v-model="txForm.notes" rows="2" placeholder="Mis. pembelian / dipakai siswa" />
            </label>
            <p v-if="txForm.type === 'penyesuaian'" class="hint full">Penyesuaian: jumlah diisi sebagai stok akhir (bukan selisih).</p>
            <div class="modal-actions full">
              <button type="button" class="btn-secondary" @click="showTxModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="txSubmitting">{{ txSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="!!deleteTarget"
        title="Hapus obat"
        :message="deleteMessage"
        confirm-text="Hapus"
        @confirm="confirmDelete"
        @cancel="deleteTarget = null"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { uksMedicineApi } from '@/api/uks'
import { useToast } from '@/composables/useToast'
import '@/assets/module-page.css'

const toast = useToast()

const activeTab = ref('stock')
const loading = ref(true)
const txLoading = ref(false)
const medicines = ref([])
const allMedicines = ref([])
const transactions = ref([])
const stockSummary = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })
const txPagination = ref({ current_page: 1, last_page: 1, per_page: 30, total: 0 })

const filters = ref({
  search: '',
  is_active: 'true',
  low_stock: false,
  expired: false,
})

const txFilters = ref({
  uks_medicine_id: '',
  type: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const editingMedicine = ref(null)
const form = ref(emptyForm())
const formSubmitting = ref(false)
const formError = ref('')

const showTxModal = ref(false)
const txForm = ref(emptyTxForm())
const txSubmitting = ref(false)
const txFormError = ref('')
const deleteTarget = ref(null)

let debounceTimer = null

const activeMedicines = computed(() =>
  (allMedicines.value.length ? allMedicines.value : medicines.value).filter((m) => m.is_active !== false)
)

const deleteMessage = computed(() =>
  deleteTarget.value
    ? `Hapus atau nonaktifkan "${deleteTarget.value.name}"? Jika sudah ada transaksi, obat hanya dinonaktifkan.`
    : ''
)

function emptyForm() {
  return {
    name: '',
    code: '',
    unit: 'pcs',
    quantity: 0,
    min_stock: null,
    expiry_date: '',
    description: '',
    is_active: true,
  }
}

function emptyTxForm() {
  return {
    uks_medicine_id: '',
    type: 'masuk',
    quantity: 1,
    transaction_date: new Date().toISOString().slice(0, 10),
    notes: '',
  }
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function truncate(text, n) {
  if (!text) return ''
  return text.length > n ? text.slice(0, n) + '…' : text
}

function cleanParams(obj) {
  const out = {}
  Object.entries(obj).forEach(([k, v]) => {
    if (v === '' || v === null || v === undefined || v === false) return
    out[k] = v
  })
  return out
}

function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    pagination.value.current_page = 1
    loadMedicines()
  }, 300)
}

async function loadSummary() {
  try {
    const res = await uksMedicineApi.getSummary()
    stockSummary.value = res.data?.data ?? null
  } catch {
    stockSummary.value = null
  }
}

async function loadMedicines() {
  loading.value = true
  try {
    const params = {
      ...cleanParams({
        search: filters.value.search,
        is_active: filters.value.is_active,
        low_stock: filters.value.low_stock || undefined,
        expired: filters.value.expired || undefined,
      }),
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    }
    const res = await uksMedicineApi.getAll(params)
    const payload = res.data
    medicines.value = payload?.data ?? []
    if (payload?.meta) {
      pagination.value = {
        current_page: payload.meta.current_page,
        last_page: payload.meta.last_page,
        per_page: payload.meta.per_page,
        total: payload.meta.total,
      }
    }
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat stok obat.')
    medicines.value = []
  } finally {
    loading.value = false
  }
}

async function loadAllMedicines() {
  try {
    const res = await uksMedicineApi.getAll({ per_page: 100, is_active: true })
    allMedicines.value = res.data?.data ?? []
  } catch {
    allMedicines.value = []
  }
}

async function loadTransactions() {
  txLoading.value = true
  try {
    const params = {
      ...cleanParams(txFilters.value),
      page: txPagination.value.current_page,
      per_page: txPagination.value.per_page,
    }
    const res = await uksMedicineApi.getTransactions(params)
    const payload = res.data
    transactions.value = payload?.data ?? []
    if (payload?.meta) {
      txPagination.value = {
        current_page: payload.meta.current_page,
        last_page: payload.meta.last_page,
        per_page: payload.meta.per_page,
        total: payload.meta.total,
      }
    }
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat transaksi.')
    transactions.value = []
  } finally {
    txLoading.value = false
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadMedicines()
}

function goToTxPage(page) {
  txPagination.value.current_page = page
  loadTransactions()
}

function openAddModal() {
  editingMedicine.value = null
  form.value = emptyForm()
  formError.value = ''
  showFormModal.value = true
}

function openEditModal(m) {
  editingMedicine.value = m
  form.value = {
    name: m.name,
    code: m.code || '',
    unit: m.unit || 'pcs',
    quantity: m.quantity,
    min_stock: m.min_stock,
    expiry_date: m.expiry_date || '',
    description: m.description || '',
    is_active: m.is_active !== false,
  }
  formError.value = ''
  showFormModal.value = true
}

function openTxModal(m = null) {
  txForm.value = emptyTxForm()
  if (m) txForm.value.uks_medicine_id = m.id
  txFormError.value = ''
  showTxModal.value = true
}

async function submitForm() {
  formSubmitting.value = true
  formError.value = ''
  try {
    const payload = { ...form.value }
    if (payload.min_stock === '' || payload.min_stock === null) payload.min_stock = null
    if (!payload.expiry_date) payload.expiry_date = null
    if (editingMedicine.value) {
      const { quantity, ...updatePayload } = payload
      await uksMedicineApi.update(editingMedicine.value.id, updatePayload)
      toast.success('Berhasil', 'Obat diperbarui.')
    } else {
      await uksMedicineApi.create(payload)
      toast.success('Berhasil', 'Obat ditambahkan.')
    }
    showFormModal.value = false
    await Promise.all([loadMedicines(), loadSummary(), loadAllMedicines()])
  } catch (e) {
    formError.value = e.formattedMessage || 'Gagal menyimpan.'
  } finally {
    formSubmitting.value = false
  }
}

async function submitTx() {
  txSubmitting.value = true
  txFormError.value = ''
  try {
    await uksMedicineApi.createTransaction(txForm.value)
    toast.success('Berhasil', 'Transaksi stok dicatat.')
    showTxModal.value = false
    await Promise.all([loadMedicines(), loadSummary(), loadAllMedicines()])
    if (activeTab.value === 'tx') await loadTransactions()
  } catch (e) {
    txFormError.value = e.formattedMessage || 'Gagal mencatat transaksi.'
  } finally {
    txSubmitting.value = false
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  try {
    await uksMedicineApi.delete(deleteTarget.value.id)
    toast.success('Berhasil', 'Obat dihapus atau dinonaktifkan.')
    deleteTarget.value = null
    await Promise.all([loadMedicines(), loadSummary(), loadAllMedicines()])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menghapus.')
  }
}

onMounted(async () => {
  await Promise.all([loadSummary(), loadMedicines(), loadAllMedicines()])
})
</script>

<style scoped>
.uks-page { display: flex; flex-direction: column; gap: 16px; }
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.main-tabs { display: flex; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 10px; }
.main-tab { border: none; background: transparent; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 13px; color: #64748b; font-weight: 500; }
.main-tab.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
.stock-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 6px; }
.stat-card.warn { border-color: #fcd34d; background: #fffbeb; }
.stat-card.danger { border-color: #fca5a5; background: #fef2f2; }
.stat-label { font-size: 12px; color: #64748b; }
.stat-value { font-size: 28px; font-weight: 700; color: #0f172a; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.search-input, .filter-select { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; background: #fff; }
.search-input { min-width: 200px; flex: 1; }
.check-filter { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #475569; }
.table-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th, .data-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: top; }
.data-table th { background: #f8fafc; color: #475569; font-weight: 600; }
.row-warn { background: #fffbeb; }
.row-danger { background: #fef2f2; }
.student-name { display: block; font-weight: 600; color: #0f172a; }
.student-meta { display: block; font-size: 11px; color: #94a3b8; margin-top: 2px; }
.status-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.status-selesai { background: #dcfce7; color: #166534; }
.status-rujuk { background: #fee2e2; color: #991b1b; }
.tx-masuk { background: #dbeafe; color: #1e40af; }
.tx-keluar { background: #ffedd5; color: #9a3412; }
.tx-penyesuaian { background: #e0e7ff; color: #3730a3; }
.tag { display: inline-block; margin-left: 6px; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; }
.tag-warn { background: #fef3c7; color: #92400e; }
.tag-danger { background: #fee2e2; color: #991b1b; }
.action-buttons { display: flex; gap: 4px; }
.btn-action { border: none; background: #f1f5f9; border-radius: 6px; width: 30px; height: 30px; cursor: pointer; }
.btn-delete { color: #b91c1c; }
.empty-state { text-align: center; padding: 48px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; }
.empty-title { margin: 0 0 8px; }
.empty-desc { color: #64748b; margin: 0 0 16px; }
.pagination-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.btn-page { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 12px; }
.btn-page:disabled { opacity: .5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 16px; }
.modal-card { background: #fff; border-radius: 14px; padding: 20px; width: min(720px, 100%); max-height: 90vh; overflow: auto; }
.modal-card-sm { width: min(480px, 100%); }
.modal-card h3 { margin: 0 0 12px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-grid label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #475569; font-weight: 600; }
.form-grid input, .form-grid select, .form-grid textarea { font-weight: 400; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; }
.form-grid .full { grid-column: 1 / -1; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.form-error { color: #b91c1c; font-size: 13px; margin: 0 0 8px; }
.hint { margin: 0; font-size: 12px; color: #64748b; }
@media (max-width: 900px) {
  .stock-stats { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 700px) {
  .stock-stats, .form-grid { grid-template-columns: 1fr; }
}
</style>
