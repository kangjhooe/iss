<template>
    <div class="qr-generate-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">Generate QR Absensi</h1>
            <p class="page-subtitle">Kartu QR tetap untuk siswa atau pegawai. Cetak per kelas, lalu scan saat absensi.</p>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-secondary btn-compact" :disabled="!cards.length || printing" @click="printCards">
              Cetak kartu
            </button>
            <button type="button" class="btn-secondary btn-compact" :disabled="!canPrintPdf || printingPdf" @click="openPdf">
              {{ printingPdf ? 'Menyiapkan PDF...' : 'Cetak PDF' }}
            </button>
          </div>
        </div>
      </div>

      <div class="toolbar">
        <select v-model="qrType" class="form-select" @change="resetAll">
          <option value="student">Siswa</option>
          <option value="employee">Guru/Staff</option>
        </select>

        <template v-if="qrType === 'student'">
          <select v-model="qrClassId" class="form-select" @change="onClassChange">
            <option value="">Pilih kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <input
            v-model="search"
            type="search"
            class="form-select"
            placeholder="Cari nama / NIS"
            :disabled="!qrClassId"
          />
        </template>
        <template v-else>
          <input v-model="search" type="search" class="form-select" placeholder="Cari nama / NIP" />
        </template>

        <button
          type="button"
          class="btn-primary btn-compact"
          :disabled="!canGenerateAll || loading"
          @click="generateAll"
        >
          {{ loading ? 'Menggenerate...' : (qrType === 'student' ? 'Generate semua di kelas' : 'Generate semua pegawai') }}
        </button>
        <button
          type="button"
          class="btn-secondary btn-compact"
          :disabled="!selectedIds.length || loading"
          @click="generateSelected"
        >
          Generate terpilih ({{ selectedIds.length }})
        </button>
      </div>

      <p v-if="qrType === 'student' && !qrClassId" class="hint-text">Pilih kelas untuk melihat siswa dan generate massal.</p>
      <p v-else-if="listLoading" class="hint-text">Memuat daftar...</p>
      <p v-else-if="!filteredRows.length" class="hint-text">Tidak ada data aktif yang sesuai.</p>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-check">
                <input type="checkbox" :checked="allVisibleSelected" @change="toggleSelectAll" />
              </th>
              <th>{{ qrType === 'student' ? 'NIS' : 'NIP' }}</th>
              <th>Nama</th>
              <th>{{ qrType === 'student' ? 'Kelas' : 'Jenis' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredRows" :key="row.id">
              <td>
                <input type="checkbox" :value="row.id" v-model="selectedIds" />
              </td>
              <td>{{ qrType === 'student' ? (row.nis || '—') : (row.nip || '—') }}</td>
              <td>{{ row.name }}</td>
              <td>{{ qrType === 'student' ? (selectedClassName || '—') : (row.type || '—') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="cards.length" class="cards-section">
        <h2 class="section-title">{{ cards.length }} kartu siap cetak</h2>
        <div class="card-grid">
          <article v-for="card in cards" :key="card.id" class="qr-card">
            <div class="qr-card-head">{{ authStore.activeInstitution?.name || authStore.user?.institution?.name || 'Sekolah' }}</div>
            <div class="qr-card-body">
              <div class="qr-frame">
                <img :src="card.qr_code" :alt="`QR ${card.name}`" class="qr-image" />
              </div>
              <div class="qr-meta">
                <strong>{{ card.name }}</strong>
                <span v-if="card.nis">NIS {{ card.nis }}</span>
                <span v-if="card.nip">NIP {{ card.nip }}</span>
                <span v-if="card.class_name">{{ card.class_name }}</span>
                <span v-if="card.type">{{ card.type }}</span>
              </div>
            </div>
            <div class="qr-card-foot">Kartu QR Absensi</div>
            <button type="button" class="btn-link qr-download" @click="downloadCard(card)">Unduh</button>
          </article>
        </div>
      </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { qrAttendanceApi } from '@/api/attendance'
import { studentApi } from '@/api/student'
import { employeeApi } from '@/api/teacher'
import { classApi } from '@/api/class'

const toast = useToast()
const authStore = useAuthStore()

const qrType = ref('student')
const qrClassId = ref('')
const search = ref('')
const classes = ref([])
const students = ref([])
const employees = ref([])
const selectedIds = ref([])
const cards = ref([])
const loading = ref(false)
const listLoading = ref(false)
const printing = ref(false)
const printingPdf = ref(false)

const selectedClassName = computed(() => classes.value.find((c) => String(c.id) === String(qrClassId.value))?.name || '')

const canGenerateAll = computed(() => qrType.value === 'employee' || Boolean(qrClassId.value))
const canPrintPdf = computed(() => qrType.value === 'employee' || Boolean(qrClassId.value) || selectedIds.value.length)

const filteredRows = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = qrType.value === 'student' ? students.value : employees.value
  if (!q) return list
  return list.filter((row) => {
    const hay = `${row.name || ''} ${row.nis || ''} ${row.nisn || ''} ${row.nip || ''} ${row.type || ''}`
    return hay.toLowerCase().includes(q)
  })
})

const allVisibleSelected = computed(() => {
  const ids = filteredRows.value.map((r) => r.id)
  return ids.length > 0 && ids.every((id) => selectedIds.value.includes(id))
})

function toggleSelectAll(event) {
  const ids = filteredRows.value.map((r) => r.id)
  if (event.target.checked) {
    selectedIds.value = [...new Set([...selectedIds.value, ...ids])]
  } else {
    selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id))
  }
}

function resetAll() {
  qrClassId.value = ''
  search.value = ''
  students.value = []
  selectedIds.value = []
  cards.value = []
}

function onClassChange() {
  search.value = ''
  selectedIds.value = []
  cards.value = []
  loadStudentsForClass()
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 200, status: 'Aktif' })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadStudentsForClass() {
  students.value = []
  if (!qrClassId.value) return
  listLoading.value = true
  try {
    const collected = []
    let page = 1
    let lastPage = 1
    do {
      const res = await studentApi.getAll({
        class_id: qrClassId.value,
        status: 'Aktif',
        per_page: 100,
        page,
        sort_by: 'name',
        sort_dir: 'asc',
      })
      collected.push(...(res.data.data || []))
      lastPage = Number(res.data.meta?.last_page || 1)
      page += 1
    } while (page <= lastPage && page <= 20)
    students.value = collected
  } catch {
    students.value = []
    toast.error('Gagal memuat daftar siswa', 'Daftar siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    listLoading.value = false
  }
}

async function loadEmployees() {
  listLoading.value = true
  try {
    const collected = []
    let page = 1
    let lastPage = 1
    do {
      const res = await employeeApi.getAll({ per_page: 100, page, status: 'Aktif' })
      collected.push(...(res.data.data || []))
      lastPage = Number(res.data.meta?.last_page || 1)
      page += 1
    } while (page <= lastPage && page <= 20)
    employees.value = collected.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'id'))
  } catch {
    employees.value = []
    toast.error('Gagal memuat daftar pegawai', 'Daftar pegawai tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    listLoading.value = false
  }
}

async function generateAll() {
  if (qrType.value === 'student' && !qrClassId.value) return
  loading.value = true
  try {
    const res = qrType.value === 'student'
      ? await qrAttendanceApi.generateStudentBulk({ class_id: Number(qrClassId.value) })
      : await qrAttendanceApi.generateEmployeeBulk({})
    cards.value = res.data.data.cards || []
    if (!cards.value.length) {
      toast.warning('Kosong', res.data.message || 'Tidak ada data aktif.')
      return
    }
    toast.success('Berhasil', `${cards.value.length} kartu QR siap dicetak.`)
  } catch (e) {
    toast.error('Gagal generate', e.formattedMessage || 'QR tidak dapat digenerate.')
  } finally {
    loading.value = false
  }
}

async function generateSelected() {
  if (!selectedIds.value.length) return
  loading.value = true
  try {
    const res = qrType.value === 'student'
      ? await qrAttendanceApi.generateStudentBulk({
          class_id: qrClassId.value ? Number(qrClassId.value) : undefined,
          student_ids: selectedIds.value,
        })
      : await qrAttendanceApi.generateEmployeeBulk({ employee_ids: selectedIds.value })
    cards.value = res.data.data.cards || []
    toast.success('Berhasil', `${cards.value.length} kartu QR siap dicetak.`)
  } catch (e) {
    toast.error('Gagal generate', e.formattedMessage || 'QR tidak dapat digenerate.')
  } finally {
    loading.value = false
  }
}

function fileSlug(value) {
  return String(value || '').toLowerCase().replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '') || 'qr'
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  }[char]))
}

function downloadCard(card) {
  const link = document.createElement('a')
  link.href = card.qr_code
  const idPart = card.nis || card.nip || card.id
  const classPart = card.class_name || qrType.value
  link.download = `qr-${fileSlug(classPart)}-${fileSlug(idPart)}-${fileSlug(card.name)}.svg`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function buildPrintGrid(cardsHtml) {
  const rows = []
  for (let i = 0; i < cardsHtml.length; i += 2) {
    rows.push(`
      <tr>
        <td>${cardsHtml[i] || ''}</td>
        <td>${cardsHtml[i + 1] || ''}</td>
      </tr>
    `)
  }
  return rows.join('')
}

function printCards() {
  if (!cards.value.length) return
  printing.value = true
  const institutionName = authStore.activeInstitution?.name
    || authStore.user?.institution?.name
    || 'Sekolah'

  const cardBlocks = cards.value.map((card) => `
    <div class="card">
      <div class="card-head">${escapeHtml(institutionName)}</div>
      <table class="card-body">
        <tr>
          <td class="qr-cell">
            <div class="qr-frame">
              <img src="${card.qr_code}" alt="" />
            </div>
            <div class="scan-hint">Scan saat absensi</div>
          </td>
          <td class="meta-cell">
            <div class="primary-id">${escapeHtml(card.name)}</div>
            <table class="meta-rows">
              ${card.nis ? `<tr><td class="lbl">NIS</td><td class="val">${escapeHtml(card.nis)}</td></tr>` : ''}
              ${card.nip ? `<tr><td class="lbl">NIP</td><td class="val">${escapeHtml(card.nip)}</td></tr>` : ''}
              ${card.class_name ? `<tr><td class="lbl">Kelas</td><td class="val">${escapeHtml(card.class_name)}</td></tr>` : ''}
              ${card.type ? `<tr><td class="lbl">Jenis</td><td class="val">${escapeHtml(card.type)}</td></tr>` : ''}
            </table>
          </td>
        </tr>
      </table>
      <div class="card-foot">Kartu QR Absensi</div>
    </div>
  `)

  const win = window.open('', '_blank')
  if (!win) {
    printing.value = false
    toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk mencetak.')
    return
  }
  win.document.write(`<!DOCTYPE html>
    <html lang="id">
      <head>
        <title>Kartu QR Absensi</title>
        <style>
          @page { size: A4 portrait; margin: 8mm; }
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.35; }
          .page-title { font-size: 11pt; font-weight: 700; color: #065f46; margin: 0 0 6mm; }
          .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
          .grid td { width: 50%; padding: 3mm; vertical-align: top; }
          .card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            height: 63mm;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
          }
          .card-head {
            background: #047857;
            color: #fff;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            padding: 3px 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .card-body { width: 100%; border-collapse: collapse; }
          .qr-cell { width: 36mm; text-align: center; vertical-align: middle; padding: 5px 4px 4px 6px; }
          .qr-frame {
            display: inline-block;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 3px;
          }
          .qr-frame img { width: 30mm; height: 30mm; display: block; }
          .scan-hint { font-size: 7pt; color: #64748b; margin-top: 2px; }
          .meta-cell { vertical-align: middle; padding: 6px 8px 6px 2px; }
          .primary-id {
            font-size: 13pt;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 5px;
            line-height: 1.15;
            word-break: break-word;
          }
          .meta-rows { width: 100%; border-collapse: collapse; }
          .meta-rows td { padding: 1px 0; vertical-align: top; font-size: 9pt; }
          .meta-rows .lbl { width: 14mm; color: #64748b; padding-right: 3px; white-space: nowrap; }
          .meta-rows .val { color: #1e293b; font-weight: 700; word-break: break-word; }
          .card-foot {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 7pt;
            padding: 2px 8px;
            text-align: right;
            letter-spacing: 0.3px;
            text-transform: uppercase;
          }
        </style>
      </head>
      <body>
        <h1 class="page-title">Kartu QR Absensi${selectedClassName.value ? ' — ' + escapeHtml(selectedClassName.value) : ''}</h1>
        <table class="grid">${buildPrintGrid(cardBlocks)}</table>
      </body>
    </html>`)
  win.document.close()
  win.focus()
  win.print()
  printing.value = false
}

async function openPdf() {
  printingPdf.value = true
  try {
    const res = qrType.value === 'student'
      ? await qrAttendanceApi.printStudentPdf({
          class_id: qrClassId.value || undefined,
          student_ids: selectedIds.value.length ? selectedIds.value.join(',') : undefined,
        })
      : await qrAttendanceApi.printEmployeePdf({
          employee_ids: selectedIds.value.length ? selectedIds.value.join(',') : undefined,
        })
    const contentType = res.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mencetak PDF.')
    }
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open(url, '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat PDF.')
    }
  } catch (e) {
    toast.error('Gagal PDF', e.formattedMessage || e.message || 'PDF tidak dapat dibuat.')
  } finally {
    printingPdf.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadClasses(), loadEmployees()])
})
</script>

<style scoped>
.qr-generate-page {
  width: 100%;
  padding: 1.5rem;
}

.page-header { margin-bottom: 1rem; }
.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.header-actions, .toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}
.toolbar { margin-bottom: 1rem; }
.form-select {
  min-width: 160px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}
.hint-text { color: #64748b; font-size: 0.9rem; }
.table-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.08);
  overflow: auto;
}
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td {
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
  font-size: 0.9rem;
}
.col-check { width: 36px; }
.cards-section { margin-top: 1.5rem; }
.section-title { font-size: 1.05rem; margin: 0 0 0.75rem; }
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 0.75rem;
}
.qr-card {
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.qr-card-head {
  background: #047857;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 0.35rem 0.6rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.qr-card-body {
  display: flex;
  gap: 0.65rem;
  padding: 0.65rem;
  align-items: center;
}
.qr-frame {
  flex-shrink: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.35rem;
}
.qr-image { width: 108px; height: 108px; display: block; }
.qr-meta {
  flex: 1;
  min-width: 0;
  text-align: left;
  font-size: 0.82rem;
  color: #334155;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.qr-meta strong {
  font-size: 0.95rem;
  color: #065f46;
  line-height: 1.2;
}
.qr-card-foot {
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.62rem;
  padding: 0.25rem 0.6rem;
  text-align: right;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.btn-primary, .btn-secondary, .btn-link, .btn-compact {
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-compact { padding: 0.45rem 0.75rem; }
.btn-primary { border: none; background: #059669; color: #fff; }
.btn-secondary { border: 1px solid #e2e8f0; background: #fff; }
.btn-link { border: none; background: none; color: #059669; text-decoration: underline; }
.qr-download { margin: 0 0.65rem 0.65rem; align-self: flex-start; }
.btn-primary:disabled, .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
