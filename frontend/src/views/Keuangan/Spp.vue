<template>
  <Layout>
    <div class="keuangan-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">SPP <span class="beta-pill">Beta</span></h1>
              <p class="page-subtitle">Generate tagihan SPP bulanan untuk siswa aktif</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" :disabled="exporting || !monthlyTypes.length" @click="doExport">
              {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-header-primary" :disabled="!monthlyTypes.length" @click="openGenerate">
              Generate SPP
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="error" class="error-banner">{{ error }}</div>
        <div v-if="success" class="success-banner">{{ success }}</div>

        <div class="content-card filters-bar">
          <div class="filter-field">
            <label class="filter-label">Jenis SPP</label>
            <select v-model="filters.fee_type_id" class="filter-select" @change="reloadFromStart">
              <option value="">Semua bulanan</option>
              <option v-for="t in monthlyTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Status</label>
            <select v-model="filters.status" class="filter-select" @change="reloadFromStart">
              <option value="">Semua</option>
              <option v-for="o in invoiceStatusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Periode</label>
            <input v-model="filters.period_label" type="month" class="filter-input" @change="reloadFromStart" />
          </div>
          <div class="filter-field" style="flex:1;min-width:160px">
            <label class="filter-label">Cari siswa</label>
            <input v-model="filters.search" class="filter-input" placeholder="Nama / NIS" @keyup.enter="reloadFromStart" />
          </div>
        </div>

        <div class="content-card">
          <div v-if="!monthlyTypes.length && !loadingTypes" class="empty-state">
            <h3>Belum ada jenis biaya bulanan</h3>
            <p>Buat jenis biaya dengan frekuensi <strong>Bulanan</strong> di menu Jenis Biaya (mis. SPP).</p>
            <router-link to="/keuangan/jenis-biaya" class="btn-primary">Ke Jenis Biaya</router-link>
          </div>
          <div v-else-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="7" /></div>
          <div v-else-if="items.length === 0" class="empty-state">
            <h3>Belum ada tagihan SPP</h3>
            <p>Generate SPP untuk periode {{ filters.period_label || 'terpilih' }}.</p>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Siswa</th>
                  <th>Kelas</th>
                  <th>Judul</th>
                  <th>Periode</th>
                  <th class="num">Tagihan</th>
                  <th class="num">Dibayar</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in items" :key="row.id">
                  <td>
                    <strong>{{ row.student?.name }}</strong>
                    <div class="muted" style="font-size:0.8rem">{{ row.student?.nis || '—' }}</div>
                  </td>
                  <td>{{ row.class?.name || '—' }}</td>
                  <td>{{ row.title }}</td>
                  <td>{{ row.period_label || '—' }}</td>
                  <td class="num">{{ formatRp(row.amount) }}</td>
                  <td class="num">{{ formatRp(row.amount_paid) }}</td>
                  <td><span :class="['pill', `pill-${row.status}`]">{{ statusLabel(row.status) }}</span></td>
                  <td>
                    <button
                      v-if="row.status !== 'cancelled' && row.status !== 'paid'"
                      type="button"
                      class="btn-action btn-edit"
                      @click="openEdit(row)"
                    >Edit</button>
                    <button
                      v-if="row.status !== 'cancelled' && row.status !== 'paid'"
                      type="button"
                      class="btn-action btn-danger"
                      @click="cancelInvoice(row)"
                    >Batal</button>
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

      <div v-if="showGenerate" class="modal-overlay" @click.self="showGenerate = false">
        <div class="modal-card">
          <h3>Generate SPP bulanan</h3>
          <div v-if="modalError" class="modal-error">{{ modalError }}</div>
          <form @submit.prevent="generate">
            <div class="form-grid">
              <div class="form-group full">
                <label>Jenis biaya *</label>
                <select v-model="gen.fee_type_id" class="form-select" required>
                  <option disabled value="">Pilih</option>
                  <option v-for="t in monthlyTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ formatRp(t.default_amount) }})</option>
                </select>
              </div>
              <div class="form-group">
                <label>Periode *</label>
                <input v-model="gen.period_label" type="month" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Jatuh tempo</label>
                <input v-model="gen.due_date" type="date" class="form-input" />
              </div>
              <div class="form-group">
                <label>Nominal (opsional)</label>
                <input v-model.number="gen.amount" type="number" min="0" step="1000" class="form-input" placeholder="Pakai default jika kosong" />
              </div>
              <div class="form-group full">
                <label>Target</label>
                <select v-model="gen.target" class="form-select">
                  <option value="all">Semua siswa aktif</option>
                  <option value="class">Satu kelas</option>
                  <option value="student">Siswa tertentu</option>
                </select>
              </div>
              <div v-if="gen.target === 'class'" class="form-group full">
                <label>Kelas *</label>
                <select v-model="gen.class_id" class="form-select" required>
                  <option disabled value="">Pilih kelas</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div v-if="gen.target === 'student'" class="form-group full">
                <label>Cari siswa *</label>
                <input
                  v-model="studentQuery"
                  class="form-input"
                  placeholder="Ketik nama / NIS lalu Enter"
                  @keyup.enter.prevent="searchStudents"
                />
                <button type="button" class="btn-secondary" style="margin-top:0.5rem" @click="searchStudents">Cari</button>
                <div v-if="studentResults.length" class="student-pick-list">
                  <label v-for="s in studentResults" :key="s.id" class="student-pick-item">
                    <input v-model="gen.student_ids" type="checkbox" :value="s.id" />
                    <span>{{ s.name }} <span class="muted">({{ s.nis || '—' }} · {{ s.class?.name || s.school_class?.name || '—' }})</span></span>
                  </label>
                </div>
                <p v-if="gen.student_ids.length" class="muted" style="margin:0.5rem 0 0;font-size:0.8rem">
                  {{ gen.student_ids.length }} siswa dipilih
                </p>
              </div>
              <div class="form-group full">
                <label>Judul (opsional)</label>
                <input v-model="gen.title" class="form-input" placeholder="Otomatis dari nama jenis + periode" />
              </div>
            </div>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showGenerate = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="generating">{{ generating ? 'Memproses...' : 'Generate' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showEdit" class="modal-overlay" @click.self="showEdit = false">
        <div class="modal-card">
          <h3>Edit tagihan SPP</h3>
          <div v-if="editError" class="modal-error">{{ editError }}</div>
          <p class="muted" style="margin-top:-0.5rem;margin-bottom:1rem">
            {{ editing?.student?.name }} — {{ editing?.period_label || '—' }} — sisa {{ formatRp(editing?.remaining) }}
          </p>
          <form @submit.prevent="saveEdit">
            <div class="form-grid">
              <div class="form-group full">
                <label>Judul *</label>
                <input v-model="editForm.title" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Nominal *</label>
                <input v-model.number="editForm.amount" type="number" min="1" step="1000" class="form-input" required />
              </div>
              <div class="form-group">
                <label>Jatuh tempo</label>
                <input v-model="editForm.due_date" type="date" class="form-input" />
              </div>
              <div class="form-group full">
                <label>Catatan</label>
                <textarea v-model="editForm.notes" class="form-textarea" />
              </div>
            </div>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showEdit = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="savingEdit">{{ savingEdit ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { classApi } from '@/api/class'
import { studentApi } from '@/api/student'
import { financeFeeTypeApi, financeInvoiceApi } from '@/api/finance'
import {
  invoiceStatusOptions,
  statusLabel,
  formatRp,
  currentPeriodLabel,
} from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const items = ref([])
const monthlyTypes = ref([])
const classes = ref([])
const studentResults = ref([])
const studentQuery = ref('')
const loading = ref(false)
const loadingTypes = ref(false)
const generating = ref(false)
const exporting = ref(false)
const savingEdit = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const editError = ref('')
const showGenerate = ref(false)
const showEdit = ref(false)
const editing = ref(null)
const meta = reactive({ current_page: 1, last_page: 1, prev: null, next: null })
const filters = reactive({
  period_label: currentPeriodLabel(),
  fee_type_id: '',
  status: '',
  search: '',
  page: 1,
})
const gen = reactive({
  fee_type_id: '',
  period_label: currentPeriodLabel(),
  due_date: '',
  amount: null,
  target: 'all',
  class_id: '',
  student_ids: [],
  title: '',
})
const editForm = reactive({ title: '', amount: null, due_date: '', notes: '' })

const selectedType = computed(() => monthlyTypes.value.find((t) => String(t.id) === String(gen.fee_type_id)))
let autoTitle = ''

watch(() => [gen.fee_type_id, gen.period_label], () => {
  const name = selectedType.value?.name || 'SPP'
  const next = `${name} ${gen.period_label || ''}`.trim()
  if (!gen.title || gen.title === autoTitle) {
    gen.title = next
  }
  autoTitle = next
})

watch(() => gen.target, () => {
  gen.class_id = ''
  gen.student_ids = []
  studentResults.value = []
  studentQuery.value = ''
})

function openGenerate() {
  modalError.value = ''
  gen.target = 'all'
  gen.class_id = ''
  gen.student_ids = []
  studentResults.value = []
  studentQuery.value = ''
  showGenerate.value = true
}

async function loadTypes() {
  loadingTypes.value = true
  try {
    const res = await financeFeeTypeApi.getAll({ frequency: 'monthly', active_only: 1, per_page: 100 })
    monthlyTypes.value = res.data?.data || []
    if (!gen.fee_type_id && monthlyTypes.value[0]) gen.fee_type_id = monthlyTypes.value[0].id
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat jenis SPP.')
    monthlyTypes.value = []
  } finally {
    loadingTypes.value = false
  }
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 100 })
    classes.value = res.data?.data || []
  } catch {
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
    const params = {
      page: filters.page,
      per_page: 20,
      frequency: 'monthly',
      period_label: filters.period_label || undefined,
      status: filters.status || undefined,
      search: filters.search || undefined,
      fee_type_id: filters.fee_type_id || undefined,
    }
    const res = await financeInvoiceApi.getAll(params)
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.prev = m.current_page > 1
    meta.next = m.current_page < m.last_page
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat tagihan SPP.')
  } finally {
    loading.value = false
  }
}

function goPage(page) {
  filters.page = page
  load()
}

async function searchStudents() {
  const q = studentQuery.value.trim()
  if (!q) {
    modalError.value = 'Ketik nama atau NIS siswa.'
    return
  }
  try {
    const res = await studentApi.getAll({ search: q, status: 'Aktif', per_page: 20 })
    studentResults.value = res.data?.data || []
    if (!studentResults.value.length) modalError.value = 'Siswa tidak ditemukan.'
    else modalError.value = ''
  } catch (e) {
    modalError.value = apiError(e, 'Gagal mencari siswa.')
  }
}

async function generate() {
  generating.value = true
  modalError.value = ''
  error.value = ''
  success.value = ''
  try {
    const payload = {
      fee_type_id: gen.fee_type_id,
      period_label: gen.period_label,
      due_date: gen.due_date || null,
      title: gen.title || undefined,
    }
    if (gen.target === 'class') {
      if (!gen.class_id) {
        modalError.value = 'Pilih kelas.'
        generating.value = false
        return
      }
      payload.class_id = gen.class_id
    } else if (gen.target === 'student') {
      if (!gen.student_ids.length) {
        modalError.value = 'Pilih minimal satu siswa.'
        generating.value = false
        return
      }
      payload.student_ids = [...gen.student_ids]
    } else {
      payload.all_students = true
    }
    if (gen.amount) payload.amount = gen.amount
    const res = await financeInvoiceApi.generate(payload)
    success.value = res.data?.message || 'Tagihan SPP dibuat.'
    showGenerate.value = false
    filters.period_label = gen.period_label
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal generate SPP.')
  } finally {
    generating.value = false
  }
}

function openEdit(row) {
  editing.value = row
  editForm.title = row.title || ''
  editForm.amount = Number(row.amount || 0)
  editForm.due_date = row.due_date || ''
  editForm.notes = row.notes || ''
  editError.value = ''
  showEdit.value = true
}

async function saveEdit() {
  savingEdit.value = true
  editError.value = ''
  try {
    await financeInvoiceApi.update(editing.value.id, {
      title: editForm.title,
      amount: editForm.amount,
      due_date: editForm.due_date || null,
      notes: editForm.notes || null,
    })
    success.value = 'Tagihan SPP diperbarui.'
    showEdit.value = false
    await load()
  } catch (e) {
    editError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    savingEdit.value = false
  }
}

async function cancelInvoice(row) {
  if (!confirm(`Batalkan/hapus tagihan "${row.title}" untuk ${row.student?.name}?`)) return
  try {
    await financeInvoiceApi.delete(row.id)
    success.value = 'Tagihan SPP dibatalkan/dihapus.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal membatalkan.')
  }
}

async function doExport() {
  exporting.value = true
  error.value = ''
  try {
    await financeInvoiceApi.export({
      frequency: 'monthly',
      period_label: filters.period_label || undefined,
      status: filters.status || undefined,
      search: filters.search || undefined,
      fee_type_id: filters.fee_type_id || undefined,
    })
  } catch (e) {
    error.value = apiError(e, 'Gagal export.')
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadTypes(), loadClasses()])
  await load()
})
</script>

<style scoped>
.student-pick-list {
  margin-top: 0.65rem;
  max-height: 180px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.35rem 0.5rem;
}
.student-pick-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.35rem 0;
  font-size: 0.88rem;
  cursor: pointer;
}
</style>
