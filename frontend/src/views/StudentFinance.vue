<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <div>
          <p class="sp-subtitle">Lihat tagihan sekolah dan riwayat pembayaran Anda</p>
        </div>
      </div>

      <div v-if="summary" class="sp-stats">
        <div class="sp-stat sp-stat--warn">
          <div class="sp-stat-body">
            <span class="sp-stat-label">Tunggakan</span>
            <span class="sp-stat-value">{{ formatRp(summary.outstanding) }}</span>
          </div>
        </div>
        <div class="sp-stat sp-stat--ok">
          <div class="sp-stat-body">
            <span class="sp-stat-label">Sudah dibayar</span>
            <span class="sp-stat-value">{{ formatRp(summary.paid) }}</span>
          </div>
        </div>
        <div class="sp-stat sp-stat--primary">
          <div class="sp-stat-body">
            <span class="sp-stat-label">Total tagihan</span>
            <span class="sp-stat-value">{{ formatRp(summary.billed) }}</span>
          </div>
        </div>
      </div>

      <div class="sp-filters">
        <div class="sp-filter">
          <label for="finance-tab">Tampilan</label>
          <select id="finance-tab" v-model="activeTab">
            <option value="invoices">Tagihan</option>
            <option value="payments">Riwayat pembayaran</option>
          </select>
        </div>
        <div v-if="activeTab === 'invoices'" class="sp-filter">
          <label for="invoice-status">Status</label>
          <select id="invoice-status" v-model="invoiceStatus" @change="loadInvoices">
            <option value="outstanding">Belum lunas</option>
            <option value="">Semua (aktif)</option>
            <option value="unpaid">Belum bayar</option>
            <option value="partial">Sebagian</option>
            <option value="paid">Lunas</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat data keuangan...</p>
      </div>

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat data</h3>
        <p class="sp-empty-desc">Silakan coba lagi.</p>
        <div class="sp-empty-actions">
          <button type="button" class="sp-btn sp-btn--soft" @click="reload">Coba lagi</button>
        </div>
      </div>

      <template v-else-if="activeTab === 'invoices'">
        <div v-if="!invoices.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada tagihan</h3>
          <p class="sp-empty-desc">
            {{ invoiceStatus === 'outstanding' ? 'Tidak ada tunggakan untuk saat ini.' : 'Belum ada tagihan pada filter ini.' }}
          </p>
        </div>

        <div v-else class="sp-panel">
          <div class="sp-table-wrap sp-table-desktop">
            <table class="sp-table">
              <thead>
                <tr>
                  <th>Tagihan</th>
                  <th>Periode</th>
                  <th>Jatuh tempo</th>
                  <th>Nominal</th>
                  <th>Dibayar</th>
                  <th>Sisa</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in invoices" :key="row.id">
                  <td>
                    <div>{{ row.title || '—' }}</div>
                    <div class="sp-muted">{{ row.fee_type?.name || '—' }}</div>
                  </td>
                  <td>{{ row.period_label || '—' }}</td>
                  <td>{{ formatDate(row.due_date) }}</td>
                  <td>{{ formatRp(row.amount) }}</td>
                  <td>{{ formatRp(row.amount_paid) }}</td>
                  <td>{{ formatRp(row.remaining) }}</td>
                  <td>
                    <span class="sp-badge" :class="statusBadgeClass(row.status)">
                      {{ statusLabel(row.status) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="sp-mobile-cards">
            <article v-for="row in invoices" :key="'mi-' + row.id" class="sp-mobile-card">
              <div class="sp-mobile-card-title">{{ row.title || 'Tagihan' }}</div>
              <div class="sp-mobile-card-row"><span>Jenis</span><strong>{{ row.fee_type?.name || '—' }}</strong></div>
              <div class="sp-mobile-card-row"><span>Periode</span><strong>{{ row.period_label || '—' }}</strong></div>
              <div class="sp-mobile-card-row"><span>Sisa</span><strong>{{ formatRp(row.remaining) }}</strong></div>
              <div class="sp-mobile-card-row">
                <span>Status</span>
                <strong>
                  <span class="sp-badge" :class="statusBadgeClass(row.status)">
                    {{ statusLabel(row.status) }}
                  </span>
                </strong>
              </div>
            </article>
          </div>
        </div>
      </template>

      <template v-else>
        <div v-if="!payments.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada pembayaran</h3>
          <p class="sp-empty-desc">Pembayaran yang dicatat sekolah akan muncul di sini.</p>
        </div>

        <div v-else class="sp-panel">
          <div class="sp-table-wrap sp-table-desktop">
            <table class="sp-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Tagihan</th>
                  <th>Nominal</th>
                  <th>Metode</th>
                  <th>Referensi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in payments" :key="row.id">
                  <td>{{ formatDateTime(row.paid_at) }}</td>
                  <td>
                    <div>{{ row.invoice?.title || '—' }}</div>
                    <div class="sp-muted">{{ row.invoice?.fee_type?.name || '—' }}</div>
                  </td>
                  <td>{{ formatRp(row.amount) }}</td>
                  <td>{{ methodLabel(row.method) }}</td>
                  <td>{{ row.reference || '—' }}</td>
                  <td>
                    <button
                      type="button"
                      class="sp-btn sp-btn--soft sp-btn--sm"
                      :disabled="receiptLoadingId === row.id"
                      @click="openReceipt(row)"
                    >
                      {{ receiptLoadingId === row.id ? 'Membuka...' : 'Kwitansi' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="sp-mobile-cards">
            <article v-for="row in payments" :key="'mp-' + row.id" class="sp-mobile-card">
              <div class="sp-mobile-card-title">{{ row.invoice?.title || 'Pembayaran' }}</div>
              <div class="sp-mobile-card-row"><span>Tanggal</span><strong>{{ formatDateTime(row.paid_at) }}</strong></div>
              <div class="sp-mobile-card-row"><span>Nominal</span><strong>{{ formatRp(row.amount) }}</strong></div>
              <div class="sp-mobile-card-row"><span>Metode</span><strong>{{ methodLabel(row.method) }}</strong></div>
              <div class="sp-mobile-card-actions">
                <button
                  type="button"
                  class="sp-btn sp-btn--soft sp-btn--sm"
                  :disabled="receiptLoadingId === row.id"
                  @click="openReceipt(row)"
                >
                  {{ receiptLoadingId === row.id ? 'Membuka...' : 'Kwitansi PDF' }}
                </button>
              </div>
            </article>
          </div>
        </div>
      </template>

      <p class="sp-footnote">
        Pembayaran dicatat oleh petugas sekolah. Hubungi bendahara jika ada perbedaan data.
      </p>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { studentFinanceApi } from '@/api/finance'
import { formatRp, methodLabel, statusLabel } from '@/views/Keuangan/keuanganConstants'
import '@/assets/student-portal.css'

const authStore = useAuthStore()
const toast = useToast()

const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(false)
const loadError = ref(false)
const summary = ref(null)
const invoices = ref([])
const payments = ref([])
const activeTab = ref('invoices')
const invoiceStatus = ref('outstanding')
const receiptLoadingId = ref(null)

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDateTime(val) {
  if (!val) return '—'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusBadgeClass(status) {
  if (status === 'paid') return 'sp-badge--ok'
  if (status === 'partial') return 'sp-badge--pending'
  if (status === 'cancelled') return 'sp-badge--cancel'
  return 'sp-badge--danger'
}

async function loadSummary() {
  const res = await studentFinanceApi.getSummary()
  summary.value = res.data?.data ?? null
}

async function loadInvoices() {
  const params = { per_page: 100 }
  if (invoiceStatus.value) params.status = invoiceStatus.value
  const res = await studentFinanceApi.getInvoices(params)
  const list = res.data?.data ?? res.data ?? []
  invoices.value = Array.isArray(list) ? list : (list?.data ?? [])
}

async function loadPayments() {
  const res = await studentFinanceApi.getPayments({ per_page: 100 })
  const list = res.data?.data ?? res.data ?? []
  payments.value = Array.isArray(list) ? list : (list?.data ?? [])
}

async function reload() {
  if (!studentId.value) {
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = false
  try {
    await loadSummary()
    if (activeTab.value === 'invoices') {
      await loadInvoices()
    } else {
      await loadPayments()
    }
  } catch {
    loadError.value = true
    invoices.value = []
    payments.value = []
  } finally {
    loading.value = false
  }
}

async function openReceipt(row) {
  if (!row?.id) return
  receiptLoadingId.value = row.id
  try {
    await studentFinanceApi.openReceipt(row.id)
  } catch {
    toast.error('Gagal membuka kwitansi PDF.')
  } finally {
    receiptLoadingId.value = null
  }
}

watch(activeTab, () => {
  reload()
})

onMounted(reload)
</script>

<style scoped>
.sp-muted {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 2px;
}

.sp-footnote {
  margin: 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.45;
}

.sp-mobile-card-actions {
  margin-top: 10px;
}

.sp-btn--sm {
  padding: 6px 10px;
  font-size: 12px;
}
</style>
