<template>
    <div class="keuangan-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Pembayaran</h1>
              <p class="page-subtitle">Catat pembayaran atas tagihan siswa</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" :disabled="exporting" @click="doExport">
              {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-header-primary" @click="openPay()">+ Catat pembayaran</button>
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
            <label class="filter-label">Metode</label>
            <select v-model="filters.method" class="filter-select" @change="reloadFromStart">
              <option value="">Semua</option>
              <option v-for="o in paymentMethodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>
          <div class="filter-field" style="flex:1;min-width:160px">
            <label class="filter-label">Cari</label>
            <input v-model="filters.search" class="filter-input" placeholder="Siswa / referensi" @keyup.enter="reloadFromStart" />
          </div>
        </div>

        <div class="content-card">
          <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
          <div v-else-if="items.length === 0" class="empty-state">
            <h3>Belum ada pembayaran</h3>
            <p>Catat pembayaran dari tagihan yang masih outstanding.</p>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Siswa</th>
                  <th>Tagihan</th>
                  <th class="num">Nominal</th>
                  <th>Metode</th>
                  <th>Ref</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in items" :key="row.id">
                  <td>{{ formatDate(row.paid_at) }}</td>
                  <td>{{ row.invoice?.student?.name || '—' }}</td>
                  <td>
                    <div>{{ row.invoice?.title || '—' }}</div>
                    <div class="muted" style="font-size:0.8rem">{{ row.invoice?.fee_type?.name }}</div>
                  </td>
                  <td class="num">{{ formatRp(row.amount) }}</td>
                  <td>{{ methodLabel(row.method) }}</td>
                  <td>{{ row.reference || '—' }}</td>
                  <td>
                    <TableAction kind="print" title="Kwitansi" @click="printReceipt(row)" />
                    <TableAction kind="delete" @click="remove(row)" />
                  </td>
                </tr>
              </tbody>
            </table>
            <PaginationBar
              :page="meta.current_page"
              :last-page="meta.last_page"
              :per-page="meta.per_page"
              :total="meta.total"
              item-label="pembayaran"
              @page-change="goPage"
              @per-page-change="changePerPage"
            />
          </div>
        </div>
      </main>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card">
          <h3>Catat pembayaran</h3>
          <div v-if="modalError" class="modal-error">{{ modalError }}</div>
          <form @submit.prevent="save">
            <div class="form-grid">
              <div class="form-group full">
                <label>Cari tagihan outstanding</label>
                <input
                  v-model="invoiceSearch"
                  class="form-input"
                  placeholder="Ketik nama / NIS / judul..."
                  @input="onSearchInput"
                />
              </div>
              <div class="form-group full">
                <label>Pilih tagihan *</label>
                <select v-model="form.invoice_id" class="form-select" required @change="onInvoicePick">
                  <option disabled value="">{{ searchingOutstanding ? 'Mencari...' : 'Pilih tagihan' }}</option>
                  <option v-for="inv in outstanding" :key="inv.id" :value="inv.id">
                    {{ inv.student?.name }} — {{ inv.title }} (sisa {{ formatRp(inv.remaining) }})
                  </option>
                </select>
                <p v-if="!searchingOutstanding && outstanding.length === 0" class="muted" style="margin:0.35rem 0 0;font-size:0.8rem">
                  Tidak ada tagihan. Ubah kata kunci pencarian.
                </p>
              </div>
              <div class="form-group">
                <label>Nominal *</label>
                <MoneyInput v-model="form.amount" :min="1" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Tanggal bayar</label>
                <input v-model="form.paid_at" type="datetime-local" class="form-input" />
              </div>
              <div class="form-group">
                <label>Metode</label>
                <select v-model="form.method" class="form-select">
                  <option v-for="o in paymentMethodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Referensi</label>
                <input v-model="form.reference" class="form-input" placeholder="No. transfer / kwitansi" />
              </div>
              <div class="form-group full">
                <label>Catatan</label>
                <textarea v-model="form.notes" class="form-textarea" />
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
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import MoneyInput from '@/components/MoneyInput.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { financeInvoiceApi, financePaymentApi } from '@/api/finance'
import {
  paymentMethodOptions,
  methodLabel,
  formatRp,
  printPaymentReceipt,
} from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const items = ref([])
const outstanding = ref([])
const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const searchingOutstanding = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const invoiceSearch = ref('')
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const filters = reactive({ from: '', to: '', method: '', search: '', page: 1 })
const form = reactive({
  invoice_id: '',
  amount: null,
  paid_at: '',
  method: 'cash',
  reference: '',
  notes: '',
})

let searchTimer = null

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('id-ID')
  } catch {
    return iso
  }
}

function reloadFromStart() {
  filters.page = 1
  load()
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await financePaymentApi.getAll({
      page: filters.page,
      per_page: meta.per_page || 15,
      from: filters.from || undefined,
      to: filters.to || undefined,
      method: filters.method || undefined,
      search: filters.search || undefined,
    })
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat pembayaran.')
  } finally {
    loading.value = false
  }
}

async function loadOutstanding(search = '') {
  searchingOutstanding.value = true
  try {
    const res = await financeInvoiceApi.getAll({
      status: 'outstanding',
      per_page: 30,
      search: search || undefined,
    })
    outstanding.value = res.data?.data || []
  } catch (e) {
    outstanding.value = []
    modalError.value = apiError(e, 'Gagal memuat daftar tagihan.')
  } finally {
    searchingOutstanding.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadOutstanding(invoiceSearch.value.trim()), 300)
}

function onInvoicePick() {
  const inv = outstanding.value.find((i) => String(i.id) === String(form.invoice_id))
  if (inv) form.amount = Number(inv.remaining || 0)
}

async function openPay() {
  error.value = ''
  modalError.value = ''
  invoiceSearch.value = ''
  form.invoice_id = ''
  form.amount = null
  form.paid_at = ''
  form.method = 'cash'
  form.reference = ''
  form.notes = ''
  showModal.value = true
  await loadOutstanding()
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

async function save() {
  saving.value = true
  modalError.value = ''
  error.value = ''
  success.value = ''
  try {
    const res = await financePaymentApi.create({
      invoice_id: form.invoice_id,
      amount: form.amount,
      paid_at: form.paid_at || undefined,
      method: form.method,
      reference: form.reference || null,
      notes: form.notes || null,
    })
    success.value = 'Pembayaran dicatat.'
    showModal.value = false
    const created = res.data?.data
    if (created) {
      try {
        await printPaymentReceipt(created)
      } catch (e) {
        error.value = apiError(e, 'Pembayaran tersimpan, tetapi gagal membuka kwitansi PDF.')
      }
    }
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

async function printReceipt(row) {
  try {
    await printPaymentReceipt(row.id || row)
  } catch (e) {
    error.value = apiError(e, 'Gagal membuka kwitansi PDF.')
  }
}

async function remove(row) {
  if (!confirm('Hapus pembayaran ini? Status tagihan akan dihitung ulang.')) return
  try {
    await financePaymentApi.delete(row.id)
    success.value = 'Pembayaran dihapus.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menghapus.')
  }
}

async function doExport() {
  exporting.value = true
  error.value = ''
  try {
    await financePaymentApi.export({
      from: filters.from || undefined,
      to: filters.to || undefined,
      method: filters.method || undefined,
      search: filters.search || undefined,
    })
  } catch (e) {
    error.value = apiError(e, 'Gagal export.')
  } finally {
    exporting.value = false
  }
}

onMounted(load)
onBeforeUnmount(() => clearTimeout(searchTimer))
</script>
