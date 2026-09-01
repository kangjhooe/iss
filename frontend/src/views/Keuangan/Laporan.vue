<template>
    <div class="keuangan-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Laporan Keuangan</h1>
              <p class="page-subtitle">Ringkasan tagihan, penerimaan, pengeluaran, dan arus kas</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" :disabled="exporting" @click="doExportOutstanding">
              {{ exporting === 'outstanding' ? 'Mengekspor...' : 'Export tunggakan CSV' }}
            </button>
            <button type="button" class="btn-header-primary" :disabled="exporting" @click="doExportPayments">
              {{ exporting === 'payments' ? 'Mengekspor...' : 'Export pembayaran CSV' }}
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="error" class="error-banner">{{ error }}</div>

        <div class="content-card filters-bar">
          <div class="filter-field">
            <label class="filter-label">Pembayaran dari</label>
            <input v-model="filters.from" type="date" class="filter-input" @change="load" />
          </div>
          <div class="filter-field">
            <label class="filter-label">Sampai</label>
            <input v-model="filters.to" type="date" class="filter-input" @change="load" />
          </div>
          <button type="button" class="btn-secondary" @click="load">Muat ulang</button>
        </div>

        <div v-if="loading" class="content-card"><LoadingSkeleton type="table" :rows="3" :columns="4" /></div>
        <template v-else-if="summary">
          <div class="summary-grid">
            <div class="summary-card">
              <span class="summary-label">Jenis biaya aktif</span>
              <strong class="summary-value">{{ summary.fee_types_active }}</strong>
            </div>
            <div class="summary-card">
              <span class="summary-label">Total ditagih</span>
              <strong class="summary-value">{{ formatRp(summary.billed) }}</strong>
            </div>
            <div class="summary-card">
              <span class="summary-label">Terkumpul</span>
              <strong class="summary-value">{{ formatRp(summary.collected) }}</strong>
            </div>
            <div class="summary-card">
              <span class="summary-label">Tunggakan</span>
              <strong class="summary-value">{{ formatRp(summary.outstanding) }}</strong>
              <span class="summary-hint">{{ summary.arrears_count }} tagihan</span>
            </div>
            <div class="summary-card">
              <span class="summary-label">Pembayaran (filter tanggal)</span>
              <strong class="summary-value">{{ formatRp(summary.payments_in_range?.amount) }}</strong>
              <span class="summary-hint">{{ summary.payments_in_range?.count || 0 }} transaksi</span>
            </div>
            <div class="summary-card">
              <span class="summary-label">Pengeluaran (filter tanggal)</span>
              <strong class="summary-value">{{ formatRp(summary.expenses_in_range?.amount) }}</strong>
              <span class="summary-hint">{{ summary.expenses_in_range?.count || 0 }} transaksi</span>
            </div>
            <div class="summary-card">
              <span class="summary-label">Arus kas bersih</span>
              <strong class="summary-value" :class="{ 'text-danger': (summary.net_in_range || 0) < 0 }">{{ formatRp(summary.net_in_range) }}</strong>
              <span class="summary-hint">Penerimaan − pengeluaran</span>
            </div>
          </div>

          <div class="charts-grid">
            <AppChart title="Terkumpul vs tunggakan" type="doughnut" :chart-data="financeShareChart" />
            <AppChart title="Penerimaan per bulan" type="line" :chart-data="paymentsMonthChart" />
            <AppChart title="Pengeluaran per bulan" type="line" :chart-data="expensesMonthChart" />
            <AppChart
              class="charts-span"
              title="Per jenis biaya"
              subtitle="Terkumpul dan sisa tagihan"
              type="bar"
              :chart-data="feeTypeChart"
              :options="chartOptionsBarStacked"
            />
          </div>

          <div class="content-card">
            <h3 style="margin:0 0 1rem;font-size:1rem">Per jenis biaya</h3>
            <div v-if="!(summary.by_fee_type || []).length" class="empty-state">
              <p>Belum ada data tagihan.</p>
            </div>
            <div v-else class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Jenis</th>
                    <th>Frekuensi</th>
                    <th class="num">Tagihan</th>
                    <th class="num">Ditagih</th>
                    <th class="num">Terkumpul</th>
                    <th class="num">Sisa</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in summary.by_fee_type" :key="row.fee_type_id">
                    <td>{{ row.fee_type?.name || '—' }}</td>
                    <td>{{ frequencyLabel(row.fee_type?.frequency) }}</td>
                    <td class="num">{{ row.total }}</td>
                    <td class="num">{{ formatRp(row.amount) }}</td>
                    <td class="num">{{ formatRp(row.amount_paid) }}</td>
                    <td class="num">{{ formatRp(row.remaining) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>
      </main>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AppChart from '@/components/AppChart.vue'
import { financeApi, financeInvoiceApi, financePaymentApi } from '@/api/finance'
import { doughnutFromEntries, lineFromSeries, chartOptionsBarStacked } from '@/composables/useChart'
import { formatRp, frequencyLabel } from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const summary = ref(null)
const loading = ref(false)
const exporting = ref('')
const error = ref('')
const filters = reactive({ from: '', to: '' })

const financeShareChart = computed(() => doughnutFromEntries([
  { label: 'Terkumpul', value: summary.value?.collected || 0, color: '#059669' },
  { label: 'Tunggakan', value: summary.value?.outstanding || 0, color: '#ef4444' },
]))

const paymentsMonthChart = computed(() => {
  const rows = summary.value?.payments_by_month || []
  if (!rows.length || !rows.some((r) => Number(r.amount) > 0)) return null
  return lineFromSeries(rows.map((r) => r.label), rows.map((r) => Number(r.amount || 0)), 'Penerimaan')
})

const expensesMonthChart = computed(() => {
  const rows = summary.value?.expenses_by_month || []
  if (!rows.length || !rows.some((r) => Number(r.amount) > 0)) return null
  return lineFromSeries(rows.map((r) => r.label), rows.map((r) => Number(r.amount || 0)), 'Pengeluaran', '#dc2626')
})

const feeTypeChart = computed(() => {
  const rows = (summary.value?.by_fee_type || []).slice(0, 8)
  if (!rows.length) return null
  return {
    labels: rows.map((r) => r.fee_type?.name || '—'),
    datasets: [
      {
        label: 'Terkumpul',
        data: rows.map((r) => Number(r.amount_paid || 0)),
        backgroundColor: '#059669',
        borderRadius: 4,
      },
      {
        label: 'Sisa',
        data: rows.map((r) => Number(r.remaining || 0)),
        backgroundColor: '#f59e0b',
        borderRadius: 4,
      },
    ],
  }
})

async function load() {
  loading.value = true
  error.value = ''
  summary.value = null
  try {
    const res = await financeApi.getSummary({
      from: filters.from || undefined,
      to: filters.to || undefined,
    })
    summary.value = res.data?.data || null
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat laporan.')
  } finally {
    loading.value = false
  }
}

async function doExportOutstanding() {
  exporting.value = 'outstanding'
  error.value = ''
  try {
    await financeInvoiceApi.export({ status: 'outstanding' })
  } catch (e) {
    error.value = apiError(e, 'Gagal export tunggakan.')
  } finally {
    exporting.value = ''
  }
}

async function doExportPayments() {
  exporting.value = 'payments'
  error.value = ''
  try {
    await financePaymentApi.export({
      from: filters.from || undefined,
      to: filters.to || undefined,
    })
  } catch (e) {
    error.value = apiError(e, 'Gagal export pembayaran.')
  } finally {
    exporting.value = ''
  }
}

onMounted(load)
</script>
