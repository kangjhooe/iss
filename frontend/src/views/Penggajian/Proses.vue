<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Proses Gaji</h1>
            <p class="page-subtitle">Generate, review manual, finalisasi, dan cetak slip gaji</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-header-primary" @click="openGenerateModal()">+ Proses gaji baru</button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card filters-bar">
        <div class="filter-field">
          <label class="filter-label">Periode</label>
          <select v-model="filters.period_id" class="filter-select" @change="reloadRuns">
            <option value="">Semua</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.label }}</option>
          </select>
        </div>
        <div class="filter-field">
          <label class="filter-label">Status</label>
          <select v-model="filters.status" class="filter-select" @change="reloadRuns">
            <option value="">Semua</option>
            <option v-for="o in runStatusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </div>
      </div>

      <div class="content-card">
        <div v-if="loadingRuns" class="loading-wrap"><LoadingSkeleton type="table" :rows="4" :columns="6" /></div>
        <div v-else-if="!runs.length" class="empty-state">
          <h3>Belum ada proses gaji</h3>
          <p>Pastikan periode dan profil gaji pegawai sudah diatur, lalu generate batch baru.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Label</th>
                <th>Periode</th>
                <th>Slip</th>
                <th class="num">Total net</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in runs" :key="row.id">
                <td><strong>{{ row.label }}</strong></td>
                <td>{{ row.period?.label || '—' }}</td>
                <td>{{ row.slips_count || 0 }}</td>
                <td class="num">{{ formatRp(row.total_net) }}</td>
                <td><span :class="['status-pill', row.status]">{{ runStatusLabel(row.status) }}</span></td>
                <td>
                  <button type="button" class="btn-secondary btn-sm" @click="openRun(row)">Kelola</button>
                  <button v-if="row.status === 'draft'" type="button" class="btn-secondary btn-sm" @click="removeRun(row)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            :page="runsMeta.current_page"
            :last-page="runsMeta.last_page"
            :per-page="runsMeta.per_page"
            :total="runsMeta.total"
            item-label="proses"
            @page-change="goRunsPage"
            @per-page-change="changeRunsPerPage"
          />
        </div>
      </div>

      <div v-if="activeRun" class="content-card" style="margin-top:1rem">
        <div class="header-content" style="margin-bottom:1rem">
          <div>
            <h2 style="margin:0;font-size:1.1rem">{{ activeRun.label }}</h2>
            <p class="muted" style="margin:0.25rem 0 0">{{ activeRun.period?.label }} · {{ runStatusLabel(activeRun.status) }}</p>
            <p v-if="activeRun.employee_filter?.options?.include_thr" class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">Termasuk THR</p>
            <p v-if="activeRun.status === 'paid' && activeRun.finance_expense" class="muted" style="margin:0.35rem 0 0;font-size:0.85rem">
              Tercatat di keuangan: {{ formatRp(activeRun.finance_expense.amount) }}
            </p>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-secondary" :disabled="exporting" @click="exportRunExcel">Excel</button>
            <button type="button" class="btn-secondary" :disabled="exporting" @click="exportRunPdf">PDF Rekap</button>
            <button v-if="activeRun.status === 'draft'" type="button" class="btn-primary" :disabled="actionLoading" @click="finalizeRun">Finalisasi</button>
            <button v-if="activeRun.status === 'finalized'" type="button" class="btn-primary" :disabled="actionLoading" @click="markPaid">Tandai dibayar</button>
            <button v-if="activeRun.status === 'finalized'" type="button" class="btn-secondary" :disabled="actionLoading" @click="reopenRun">Buka kembali</button>
            <button v-if="activeRun.status === 'paid'" type="button" class="btn-secondary" :disabled="actionLoading" @click="unpayRun">Batalkan pembayaran</button>
          </div>
        </div>

        <div v-if="loadingSlips" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="5" /></div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>NIP</th>
                <th class="num">Bruto</th>
                <th class="num">Potongan</th>
                <th class="num">Net</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="slip in slips" :key="slip.id">
                <td>{{ slip.employee?.name }}</td>
                <td>{{ slip.employee?.nip || '—' }}</td>
                <td class="num">{{ formatRp(slip.gross) }}</td>
                <td class="num">{{ formatRp(slip.total_deductions) }}</td>
                <td class="num"><strong>{{ formatRp(slip.net) }}</strong></td>
                <td>
                  <button type="button" class="btn-secondary btn-sm" @click="openSlip(slip)">
                    {{ activeRun.status === 'draft' ? 'Edit' : 'Lihat' }}
                  </button>
                  <button type="button" class="btn-secondary btn-sm" @click="printSlip(slip)">PDF</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Generate modal -->
    <div v-if="showGenerate" class="modal-overlay" @click.self="showGenerate = false">
      <div class="modal-card">
        <h3>Proses gaji baru</h3>
        <div v-if="generateError" class="modal-error">{{ generateError }}</div>
        <form @submit.prevent="generate">
          <div class="form-grid">
            <div class="form-group full">
              <label>Periode *</label>
              <select v-model="generateForm.period_id" class="form-select" required>
                <option value="">— Pilih periode —</option>
                <option v-for="p in openPeriods" :key="p.id" :value="p.id">{{ p.label }}</option>
              </select>
            </div>
            <div class="form-group full">
              <label>Label proses</label>
              <input v-model="generateForm.label" class="form-input" placeholder="Opsional" />
            </div>
            <div class="form-group full">
              <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input v-model="generateForm.include_thr" type="checkbox" />
                Sertakan THR (gaji pokok × pengali komponen THR)
              </label>
            </div>
          </div>
          <p class="muted" style="font-size:0.85rem;margin:0.5rem 0 0">
            Sistem akan menghitung gaji otomatis dari profil pegawai dan absensi. Pegawai tanpa profil gaji dilewati.
          </p>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showGenerate = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="generating">{{ generating ? 'Memproses...' : 'Generate' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Slip editor modal -->
    <div v-if="showSlipModal" class="modal-overlay" @click.self="showSlipModal = false">
      <div class="modal-card" style="max-width:720px;max-height:90vh;overflow:auto">
        <h3>Slip — {{ slipForm.employeeName }}</h3>
        <div v-if="slipError" class="modal-error">{{ slipError }}</div>

        <div v-if="slipForm.attendance" class="attendance-note" style="margin-bottom:1rem;padding:0.5rem 0.75rem;background:#fffbeb;border-radius:8px;font-size:0.85rem">
          Absensi: hadir {{ slipForm.attendance.hadir || 0 }},
          alpha {{ slipForm.attendance.alpha || 0 }},
          izin {{ slipForm.attendance.izin || 0 }},
          sakit {{ slipForm.attendance.sakit || 0 }}
          <span v-if="slipForm.attendance.tanpa_gaji"> · cuti tanpa gaji {{ slipForm.attendance.tanpa_gaji }}</span>
        </div>

        <div class="slip-editor-grid">
          <div>
            <h4 style="margin:0 0 0.5rem">Pendapatan</h4>
            <div v-for="(line, idx) in earningLines" :key="'e-' + idx" class="slip-line-row">
              <input v-model="line.label" class="form-input" :disabled="!canEditSlip" />
              <input v-model.number="line.amount" type="number" min="0" step="1000" class="form-input" :disabled="!canEditSlip" />
              <button v-if="canEditSlip" type="button" class="btn-secondary btn-sm" @click="removeLine(line)">×</button>
            </div>
          </div>
          <div>
            <h4 style="margin:0 0 0.5rem">Potongan</h4>
            <div v-for="(line, idx) in deductionLines" :key="'d-' + idx" class="slip-line-row">
              <input v-model="line.label" class="form-input" :disabled="!canEditSlip" />
              <input v-model.number="line.amount" type="number" min="0" step="1000" class="form-input" :disabled="!canEditSlip" />
              <button v-if="canEditSlip" type="button" class="btn-secondary btn-sm" @click="removeLine(line)">×</button>
            </div>
          </div>
        </div>

        <div v-if="canEditSlip" style="margin-top:0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap">
          <button type="button" class="btn-secondary btn-sm" @click="addLine('earning')">+ Pendapatan</button>
          <button type="button" class="btn-secondary btn-sm" @click="addLine('deduction')">+ Potongan</button>
        </div>

        <div class="slip-summary">
          <div class="row"><span>Bruto</span><span>{{ formatRp(slipTotals.gross) }}</span></div>
          <div class="row"><span>Potongan</span><span>{{ formatRp(slipTotals.deductions) }}</span></div>
          <div class="row net"><span>Diterima</span><span>{{ formatRp(slipTotals.net) }}</span></div>
        </div>

        <div v-if="canEditSlip" class="form-group" style="margin-top:1rem">
          <label>Catatan slip</label>
          <textarea v-model="slipForm.notes" class="form-textarea" rows="2" />
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showSlipModal = false">Tutup</button>
          <button v-if="canEditSlip" type="button" class="btn-primary" :disabled="savingSlip" @click="saveSlip">
            {{ savingSlip ? 'Menyimpan...' : 'Simpan perubahan' }}
          </button>
          <button type="button" class="btn-secondary" @click="printSlip({ id: slipForm.id })">Cetak PDF</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import {
  payrollPeriodApi,
  payrollRunApi,
  payrollSlipApi,
  openPdfBlob,
  downloadBlob,
} from '@/api/payroll'
import { parseBlobError } from '@/utils/blobError'
import {
  runStatusOptions,
  runStatusLabel,
  formatRp,
  apiError,
} from './penggajianConstants'
import './penggajian.css'

const runs = ref([])
const periods = ref([])
const slips = ref([])
const activeRun = ref(null)
const loadingRuns = ref(false)
const loadingSlips = ref(false)
const actionLoading = ref(false)
const generating = ref(false)
const savingSlip = ref(false)
const exporting = ref(false)
const printingSlipId = ref(null)
const error = ref('')
const success = ref('')
const generateError = ref('')
const slipError = ref('')
const showGenerate = ref(false)
const showSlipModal = ref(false)
const filters = reactive({ period_id: '', status: '', page: 1 })
const runsMeta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const generateForm = reactive({ period_id: '', label: '', include_thr: false })
const slipForm = reactive({
  id: null,
  employeeName: '',
  notes: '',
  attendance: null,
  lines: [],
  removedIds: [],
})

const openPeriods = computed(() => periods.value.filter((p) => p.status === 'open'))
const canEditSlip = computed(() => activeRun.value?.status === 'draft')
const earningLines = computed(() => slipForm.lines.filter((l) => l.type === 'earning' && !l._removed))
const deductionLines = computed(() => slipForm.lines.filter((l) => l.type === 'deduction' && !l._removed))
const slipTotals = computed(() => {
  const gross = earningLines.value.reduce((s, l) => s + Number(l.amount || 0), 0)
  const deductions = deductionLines.value.reduce((s, l) => s + Number(l.amount || 0), 0)
  return { gross, deductions, net: Math.max(0, gross - deductions) }
})

async function loadPeriods() {
  try {
    const res = await payrollPeriodApi.getAll({ per_page: 50 })
    periods.value = res.data?.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat daftar periode.')
  }
}

async function loadRuns() {
  loadingRuns.value = true
  error.value = ''
  try {
    const params = { per_page: runsMeta.per_page, page: filters.page }
    if (filters.period_id) params.period_id = filters.period_id
    if (filters.status) params.status = filters.status
    const res = await payrollRunApi.getAll(params)
    runs.value = res.data?.data || []
    const m = res.data?.meta || {}
    runsMeta.current_page = m.current_page || 1
    runsMeta.last_page = m.last_page || 1
    runsMeta.per_page = m.per_page ?? runsMeta.per_page
    runsMeta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat proses gaji.')
  } finally {
    loadingRuns.value = false
  }
}

function reloadRuns() {
  filters.page = 1
  activeRun.value = null
  slips.value = []
  loadRuns()
}

function goRunsPage(page) {
  filters.page = page
  loadRuns()
}

function changeRunsPerPage(n) {
  runsMeta.per_page = n
  filters.page = 1
  loadRuns()
}

async function openRun(run) {
  activeRun.value = run
  slips.value = []
  loadingSlips.value = true
  try {
    const res = await payrollRunApi.get(run.id)
    activeRun.value = res.data?.data || res.data || run
    const slipRes = await payrollRunApi.slips(run.id, { per_page: 100 })
    slips.value = slipRes.data?.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat slip.')
  } finally {
    loadingSlips.value = false
  }
}

function openGenerateModal() {
  generateError.value = ''
  generateForm.period_id = openPeriods.value[0]?.id || ''
  generateForm.label = ''
  generateForm.include_thr = false
  showGenerate.value = true
}

async function generate() {
  generating.value = true
  generateError.value = ''
  try {
    const res = await payrollRunApi.generate({
      period_id: Number(generateForm.period_id),
      label: generateForm.label || undefined,
      include_thr: generateForm.include_thr || undefined,
    })
    const meta = res.data?.meta
    let msg = res.data?.message || 'Proses gaji dibuat.'
    if (meta?.skipped > 0) {
      msg += ` ${meta.skipped} pegawai dilewati (belum ada profil gaji).`
    }
    success.value = msg
    showGenerate.value = false
    await loadRuns()
    if (res.data?.data?.id) {
      const run = res.data.data
      await openRun(run)
    }
  } catch (e) {
    generateError.value = apiError(e, 'Gagal memproses gaji.')
  } finally {
    generating.value = false
  }
}

async function openSlip(slipRow) {
  slipError.value = ''
  slipForm.removedIds = []
  try {
    const res = await payrollSlipApi.get(slipRow.id)
    const slip = res.data?.data || res.data
    slipForm.id = slip.id
    slipForm.employeeName = slip.employee?.name || '—'
    slipForm.notes = slip.notes || ''
    slipForm.attendance = slip.attendance_snapshot || null
    slipForm.lines = (slip.lines || []).map((l) => ({ ...l, _removed: false }))
    showSlipModal.value = true
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat slip.')
  }
}

function addLine(type) {
  slipForm.lines.push({
    id: null,
    label: type === 'earning' ? 'Tunjangan' : 'Potongan',
    type,
    amount: 0,
    _removed: false,
  })
}

function removeLine(line) {
  if (line.id) slipForm.removedIds.push(line.id)
  line._removed = true
}

async function saveSlip() {
  savingSlip.value = true
  slipError.value = ''
  try {
    const lines = slipForm.lines
      .filter((l) => !l._removed)
      .map((l) => ({
        id: l.id || undefined,
        label: l.label,
        type: l.type,
        amount: Number(l.amount || 0),
        component_id: l.component_id || undefined,
      }))
    await payrollSlipApi.update(slipForm.id, {
      notes: slipForm.notes,
      lines,
      remove_line_ids: slipForm.removedIds,
    })
    success.value = 'Slip diperbarui.'
    showSlipModal.value = false
    if (activeRun.value) await openRun(activeRun.value)
    await loadRuns()
  } catch (e) {
    slipError.value = apiError(e, 'Gagal menyimpan slip.')
  } finally {
    savingSlip.value = false
  }
}

async function printSlip(slip) {
  if (printingSlipId.value) return
  printingSlipId.value = slip.id
  try {
    const res = await payrollSlipApi.pdf(slip.id)
    openPdfBlob(res, `slip-${slip.id}.pdf`)
  } catch (e) {
    error.value = await parseBlobError(e, 'Gagal mencetak slip.')
  } finally {
    printingSlipId.value = null
  }
}

async function exportRunExcel() {
  if (!activeRun.value) return
  exporting.value = true
  error.value = ''
  try {
    const res = await payrollRunApi.exportExcel(activeRun.value.id)
    downloadBlob(res, `rekap-gaji-${activeRun.value.id}.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
    success.value = 'Excel rekap gaji diunduh.'
  } catch (e) {
    error.value = await parseBlobError(e, 'Gagal mengekspor Excel.')
  } finally {
    exporting.value = false
  }
}

async function exportRunPdf() {
  if (!activeRun.value) return
  exporting.value = true
  error.value = ''
  try {
    const res = await payrollRunApi.exportPdf(activeRun.value.id)
    openPdfBlob(res, `rekap-gaji-${activeRun.value.id}.pdf`)
  } catch (e) {
    error.value = await parseBlobError(e, 'Gagal mencetak rekap PDF.')
  } finally {
    exporting.value = false
  }
}

async function finalizeRun() {
  if (!confirm('Finalisasi proses gaji? Slip tidak bisa diedit lagi.')) return
  actionLoading.value = true
  try {
    await payrollRunApi.finalize(activeRun.value.id)
    success.value = 'Gaji difinalisasi. Pegawai dapat melihat slip.'
    await loadRuns()
    const updated = runs.value.find((r) => r.id === activeRun.value.id)
    if (updated) await openRun(updated)
  } catch (e) {
    error.value = apiError(e, 'Gagal memfinalisasi.')
  } finally {
    actionLoading.value = false
  }
}

async function markPaid() {
  if (!confirm('Tandai semua slip sebagai sudah dibayar? Pengeluaran akan tercatat otomatis di Keuangan.')) return
  actionLoading.value = true
  try {
    await payrollRunApi.markPaid(activeRun.value.id)
    success.value = 'Gaji ditandai dibayar dan tercatat sebagai pengeluaran di Keuangan.'
    await loadRuns()
    const updated = runs.value.find((r) => r.id === activeRun.value.id)
    if (updated) await openRun(updated)
  } catch (e) {
    error.value = apiError(e, 'Gagal menandai dibayar.')
  } finally {
    actionLoading.value = false
  }
}

async function unpayRun() {
  if (!confirm('Batalkan pembayaran? Status kembali ke final dan pengeluaran otomatis dihapus.')) return
  actionLoading.value = true
  try {
    await payrollRunApi.unpay(activeRun.value.id)
    success.value = 'Pembayaran dibatalkan. Proses kembali ke status final.'
    await loadRuns()
    const updated = runs.value.find((r) => r.id === activeRun.value.id)
    if (updated) await openRun(updated)
  } catch (e) {
    error.value = apiError(e, 'Gagal membatalkan pembayaran.')
  } finally {
    actionLoading.value = false
  }
}

async function reopenRun() {
  if (!confirm('Buka kembali proses gaji? Slip bisa diedit lagi dan pegawai tidak bisa melihat slip.')) return
  actionLoading.value = true
  try {
    await payrollRunApi.reopen(activeRun.value.id)
    success.value = 'Proses dibuka kembali untuk diedit.'
    await loadRuns()
    const updated = runs.value.find((r) => r.id === activeRun.value.id)
    if (updated) await openRun(updated)
  } catch (e) {
    error.value = apiError(e, 'Gagal membuka kembali proses.')
  } finally {
    actionLoading.value = false
  }
}

async function removeRun(run) {
  if (!confirm('Hapus proses draft ini?')) return
  try {
    await payrollRunApi.delete(run.id)
    success.value = 'Proses dihapus.'
    if (activeRun.value?.id === run.id) {
      activeRun.value = null
      slips.value = []
    }
    await loadRuns()
  } catch (e) {
    error.value = apiError(e, 'Gagal menghapus.')
  }
}

onMounted(async () => {
  await loadPeriods()
  await loadRuns()
})
</script>
