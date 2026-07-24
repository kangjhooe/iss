<template>
  <Layout>
    <div class="keuangan-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Tunggakan <span class="beta-pill">Beta</span></h1>
              <p class="page-subtitle">Daftar tagihan yang belum lunas</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" :disabled="exporting" @click="doExport">
              {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="error" class="error-banner">{{ error }}</div>
        <div v-if="success" class="success-banner">{{ success }}</div>

        <div class="content-card filters-bar">
          <div class="filter-field">
            <label class="filter-label">Jenis biaya</label>
            <select v-model="filters.fee_type_id" class="filter-select" @change="reloadFromStart">
              <option value="">Semua</option>
              <option v-for="t in feeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Kelas</label>
            <select v-model="filters.class_id" class="filter-select" @change="reloadFromStart">
              <option value="">Semua</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="filter-field" style="flex:1;min-width:160px">
            <label class="filter-label">Cari</label>
            <input v-model="filters.search" class="filter-input" placeholder="Nama / NIS" @keyup.enter="reloadFromStart" />
          </div>
        </div>

        <div class="content-card">
          <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
          <div v-else-if="items.length === 0" class="empty-state">
            <h3>Tidak ada tunggakan</h3>
            <p>Semua tagihan sudah lunas atau belum ada tagihan.</p>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Siswa</th>
                  <th>Tagihan</th>
                  <th class="num">Sisa</th>
                  <th>Jatuh tempo</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in items" :key="row.id">
                  <td>
                    <strong>{{ row.student?.name }}</strong>
                    <div class="muted" style="font-size:0.8rem">{{ row.class?.name || '—' }}</div>
                  </td>
                  <td>
                    <div>{{ row.title }}</div>
                    <div class="muted" style="font-size:0.8rem">{{ row.fee_type?.name }}</div>
                  </td>
                  <td class="num">{{ formatRp(row.remaining) }}</td>
                  <td>{{ row.due_date || '—' }}</td>
                  <td><span :class="['pill', `pill-${row.status}`]">{{ statusLabel(row.status) }}</span></td>
                  <td>
                    <button type="button" class="btn-action btn-edit" @click="quickPay(row)">Bayar</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="pagination">
              <span>Halaman {{ meta.current_page || 1 }} / {{ meta.last_page || 1 }}</span>
              <div class="pagination-btns">
                <button type="button" class="btn-secondary" :disabled="!meta.prev" @click="goPage(meta.current_page - 1)">Sebelumnya</button>
                <button type="button" class="btn-secondary" :disabled="!meta.next" @click="goPage(meta.current_page + 1)">Berikutnya</button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card">
          <h3>Bayar tunggakan</h3>
          <div v-if="modalError" class="modal-error">{{ modalError }}</div>
          <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem">
            {{ paying?.student?.name }} — {{ paying?.title }} (sisa {{ formatRp(paying?.remaining) }})
          </p>
          <form @submit.prevent="savePay">
            <div class="form-grid">
              <div class="form-group">
                <label>Nominal *</label>
                <input v-model.number="payForm.amount" type="number" min="1" step="1000" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Metode</label>
                <select v-model="payForm.method" class="form-select">
                  <option v-for="o in paymentMethodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
              </div>
              <div class="form-group full">
                <label>Referensi</label>
                <input v-model="payForm.reference" class="form-input" />
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
  </Layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { classApi } from '@/api/class'
import { financeFeeTypeApi, financeInvoiceApi, financePaymentApi } from '@/api/finance'
import {
  paymentMethodOptions,
  statusLabel,
  formatRp,
  printPaymentReceipt,
} from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const items = ref([])
const feeTypes = ref([])
const classes = ref([])
const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const paying = ref(null)
const meta = reactive({ current_page: 1, last_page: 1, prev: null, next: null })
const filters = reactive({ fee_type_id: '', class_id: '', search: '', page: 1 })
const payForm = reactive({ amount: null, method: 'cash', reference: '' })

async function loadMeta() {
  try {
    const [ft, cl] = await Promise.all([
      financeFeeTypeApi.getAll({ active_only: 1, per_page: 100 }),
      classApi.getAll({ per_page: 100 }),
    ])
    feeTypes.value = ft.data?.data || []
    classes.value = cl.data?.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat filter.')
    feeTypes.value = []
    classes.value = []
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
    const res = await financeInvoiceApi.getAll({
      status: 'outstanding',
      page: filters.page,
      per_page: 20,
      fee_type_id: filters.fee_type_id || undefined,
      class_id: filters.class_id || undefined,
      search: filters.search || undefined,
    })
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.prev = m.current_page > 1
    meta.next = m.current_page < m.last_page
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat tunggakan.')
  } finally {
    loading.value = false
  }
}

function goPage(page) {
  filters.page = page
  load()
}

function quickPay(row) {
  paying.value = row
  payForm.amount = Number(row.remaining || 0)
  payForm.method = 'cash'
  payForm.reference = ''
  modalError.value = ''
  showModal.value = true
}

async function savePay() {
  saving.value = true
  modalError.value = ''
  error.value = ''
  success.value = ''
  try {
    const res = await financePaymentApi.create({
      invoice_id: paying.value.id,
      amount: payForm.amount,
      method: payForm.method,
      reference: payForm.reference || null,
    })
    const created = res.data?.data
    success.value = 'Pembayaran dicatat.'
    showModal.value = false
    await load()
    if (created && confirm('Cetak kwitansi sekarang?')) {
      try {
        await printPaymentReceipt(created)
      } catch (e) {
        error.value = apiError(e, 'Pembayaran tersimpan, tetapi gagal membuka kwitansi PDF.')
      }
    }
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

async function doExport() {
  exporting.value = true
  error.value = ''
  try {
    await financeInvoiceApi.export({
      status: 'outstanding',
      fee_type_id: filters.fee_type_id || undefined,
      class_id: filters.class_id || undefined,
      search: filters.search || undefined,
    })
  } catch (e) {
    error.value = apiError(e, 'Gagal export.')
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  await loadMeta()
  await load()
})
</script>
