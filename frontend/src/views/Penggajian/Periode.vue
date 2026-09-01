<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Periode Gaji</h1>
            <p class="page-subtitle">Kelola periode bulanan sebelum memproses penggajian</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-header-primary" @click="openModal()">+ Buat periode</button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="4" :columns="5" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada periode</h3>
          <p>Buat periode untuk bulan berjalan terlebih dahulu.</p>
          <button type="button" class="btn-primary" @click="openModal()">Buat periode</button>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Periode</th>
                <th>Tanggal</th>
                <th>Hari kerja</th>
                <th>Proses</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td><strong>{{ row.label }}</strong></td>
                <td>{{ row.start_date }} — {{ row.end_date }}</td>
                <td>{{ row.working_days }}</td>
                <td>{{ row.runs_count || 0 }}</td>
                <td>
                  <span :class="['status-pill', row.status]">{{ row.status === 'open' ? 'Terbuka' : 'Ditutup' }}</span>
                </td>
                <td>
                  <button
                    v-if="row.status === 'open'"
                    type="button"
                    class="btn-secondary btn-sm"
                    @click="closePeriod(row)"
                  >Tutup</button>
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            :page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="meta.per_page"
            :total="meta.total"
            item-label="periode"
            @page-change="goPage"
            @per-page-change="changePerPage"
          />
        </div>
      </div>
    </main>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card">
        <h3>Buat periode gaji</h3>
        <div v-if="modalError" class="modal-error">{{ modalError }}</div>
        <form @submit.prevent="save">
          <div class="form-grid">
            <div class="form-group">
              <label>Tahun *</label>
              <input v-model.number="form.year" type="number" min="2000" max="2100" class="form-input" required />
            </div>
            <div class="form-group">
              <label>Bulan *</label>
              <select v-model.number="form.month" class="form-select" required>
                <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Hari kerja</label>
              <input v-model.number="form.working_days" type="number" min="1" max="31" class="form-input" placeholder="otomatis" />
            </div>
            <div class="form-group full">
              <label>Label</label>
              <input v-model="form.label" class="form-input" placeholder="Opsional, mis. Gaji Januari 2026" />
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
import { onMounted, reactive, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { payrollPeriodApi } from '@/api/payroll'
import { monthOptions, currentPayrollPeriod, apiError } from './penggajianConstants'
import './penggajian.css'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const meta = reactive({ current_page: 1, last_page: 1, per_page: 12, total: 0 })
const filters = reactive({ page: 1 })
const cur = currentPayrollPeriod()
const form = reactive({
  year: cur.year,
  month: cur.month,
  working_days: null,
  label: '',
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await payrollPeriodApi.getAll({ per_page: meta.per_page, page: filters.page })
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat periode.')
  } finally {
    loading.value = false
  }
}

function openModal() {
  modalError.value = ''
  const c = currentPayrollPeriod()
  Object.assign(form, { year: c.year, month: c.month, working_days: null, label: '' })
  showModal.value = true
}

async function save() {
  saving.value = true
  modalError.value = ''
  try {
    const payload = { ...form }
    if (!payload.working_days) delete payload.working_days
    if (!payload.label) delete payload.label
    await payrollPeriodApi.create(payload)
    success.value = 'Periode dibuat.'
    showModal.value = false
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal membuat periode.')
  } finally {
    saving.value = false
  }
}

async function closePeriod(row) {
  if (!confirm(`Tutup periode "${row.label}"?`)) return
  try {
    await payrollPeriodApi.close(row.id)
    success.value = 'Periode ditutup.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menutup periode.')
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
