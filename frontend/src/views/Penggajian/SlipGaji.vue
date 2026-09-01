<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Slip Gaji Saya</h1>
            <p class="page-subtitle">Riwayat slip gaji yang sudah difinalisasi</p>
          </div>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="4" :columns="4" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada slip gaji</h3>
          <p>Slip akan muncul setelah bendahara/TU memfinalisasi penggajian periode Anda.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Periode</th>
                <th>Proses</th>
                <th class="num">Gaji diterima</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td><strong>{{ row.period?.label || '—' }}</strong></td>
                <td>{{ row.run?.label || '—' }}</td>
                <td class="num"><strong>{{ formatRp(row.net) }}</strong></td>
                <td><span :class="['status-pill', row.status]">{{ row.status === 'paid' ? 'Dibayar' : 'Final' }}</span></td>
                <td>
                  <button type="button" class="btn-primary btn-sm" :disabled="downloadingId === row.id" @click="downloadPdf(row)">
                    {{ downloadingId === row.id ? 'Mengunduh...' : 'Unduh PDF' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            :page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="meta.per_page"
            :total="meta.total"
            item-label="slip"
            @page-change="goPage"
            @per-page-change="changePerPage"
          />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { payrollMyApi, openPdfBlob } from '@/api/payroll'
import { parseBlobError } from '@/utils/blobError'
import { formatRp, apiError } from './penggajianConstants'
import './penggajian.css'

const items = ref([])
const loading = ref(false)
const downloadingId = ref(null)
const error = ref('')
const meta = reactive({ current_page: 1, last_page: 1, per_page: 12, total: 0 })
const filters = reactive({ page: 1 })

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await payrollMyApi.slips({ per_page: meta.per_page, page: filters.page })
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat slip gaji.')
  } finally {
    loading.value = false
  }
}

async function downloadPdf(row) {
  if (downloadingId.value) return
  downloadingId.value = row.id
  try {
    const res = await payrollMyApi.pdf(row.id)
    openPdfBlob(res, `slip-gaji-${row.id}.pdf`)
  } catch (e) {
    error.value = await parseBlobError(e, 'Gagal mengunduh slip.')
  } finally {
    downloadingId.value = null
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

onMounted(load)
</script>
