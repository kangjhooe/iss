<template>
    <div class="raport-page">
      <div class="page-header">
        <div class="page-header-main">
          <router-link v-if="classFilterId" to="/teacher/wali" class="back-chip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Wali Kelas
          </router-link>
          <div class="page-header-copy">
            <h1 class="page-title">Rekap Nilai Kelas</h1>
            <p class="page-subtitle">
              Nilai akhir semua mapel, rata-rata, dan ranking siswa
            </p>
          </div>
        </div>
        <div v-if="hasData" class="header-actions">
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="exporting || printingPdf || loading"
            @click="exportCsv"
          >
            {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
          </button>
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="exporting || printingPdf || loading"
            @click="printPdf"
          >
            {{ printingPdf ? 'Menyiapkan...' : 'Cetak PDF' }}
          </button>
        </div>
      </div>

      <section class="toolbar-card">
        <div class="toolbar-fields">
          <label v-if="homeroomClasses.length > 1" class="field">
            <span class="field-label">Kelas</span>
            <select v-model="filters.class_id" class="filter-select" @change="onFilterChange">
              <option value="">Pilih Kelas</option>
              <option v-for="c in homeroomClasses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
          </label>
          <label class="field">
            <span class="field-label">Semester</span>
            <select v-model="filters.semester_id" class="filter-select" @change="onFilterChange">
              <option value="">Pilih Semester</option>
              <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
            </select>
          </label>
          <div v-if="hasSelection" class="field field-action">
            <span class="field-label">&nbsp;</span>
            <button
              type="button"
              class="btn-primary btn-compact"
              :disabled="loading"
              @click="loadRaport"
            >
              {{ loading ? 'Memuat...' : 'Muat ulang' }}
            </button>
          </div>
        </div>

        <div v-if="hasData" class="summary-strip">
          <span class="summary-chip">
            <strong>{{ selectedClassName || 'Kelas' }}</strong>
          </span>
          <span class="summary-chip muted">
            {{ selectedSemesterName || 'Semester' }}
          </span>
          <span class="summary-chip">
            <strong>{{ rows.length }}</strong> siswa
          </span>
          <span class="summary-chip">
            <strong>{{ subjects.length }}</strong> mapel
          </span>
        </div>
      </section>

      <div v-if="!hasSelection" class="state-card soft">
        <div class="state-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3>Pilih filter terlebih dahulu</h3>
        <p>Pilih <strong>Kelas</strong> dan <strong>Semester</strong> untuk menampilkan rekap nilai.</p>
      </div>

      <div v-else-if="loading && !rows.length" class="state-card soft">
        <p class="loading-text">Memuat rekap nilai...</p>
      </div>

      <div v-else-if="!rows.length" class="state-card empty">
        <div class="state-icon" aria-hidden="true">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h3>Belum ada data siswa</h3>
        <p>Kelas ini belum memiliki siswa untuk semester yang dipilih.</p>
      </div>

      <section v-else class="table-panel">
        <div class="table-panel-head">
          <div>
            <h2>Tabel Rekap</h2>
            <p class="table-hint">Scroll horizontal untuk melihat semua mapel. Kolom rata-rata & ranking di kanan.</p>
          </div>
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="studentQuery"
              type="search"
              class="search-input"
              placeholder="Cari nama / NIS..."
              aria-label="Cari siswa"
            >
          </div>
        </div>

        <div v-if="!filteredRows.length" class="table-empty">
          Tidak ada siswa yang cocok dengan pencarian.
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no sticky-col">No</th>
                <th class="col-nis sticky-col sticky-nis">NIS</th>
                <th class="col-name sticky-col sticky-name">Nama</th>
                <th
                  v-for="subject in subjects"
                  :key="subject.id"
                  class="col-subject"
                  :title="subjectTitle(subject)"
                >
                  <span class="subject-code">{{ subject.code || shortSubject(subject.name) }}</span>
                  <span class="subject-name">{{ subject.name }}</span>
                  <span v-if="subject.kkm != null" class="subject-kkm">KKM {{ subject.kkm }}</span>
                </th>
                <th class="col-avg">Rata-rata</th>
                <th class="col-rank">Ranking</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in filteredRows"
                :key="row.student_id"
                :class="rankRowClass(row.rank)"
              >
                <td class="col-no sticky-col">{{ index + 1 }}</td>
                <td class="col-nis sticky-col sticky-nis">{{ row.student?.nis || '—' }}</td>
                <td class="col-name sticky-col sticky-name">{{ row.student?.name || '—' }}</td>
                <td
                  v-for="subject in subjects"
                  :key="subject.id"
                  class="col-grade"
                  :class="gradeCellClass(row, subject.id)"
                  :title="gradeTitle(row, subject.id)"
                >
                  <template v-if="gradeRaw(row, subject.id) != null">
                    <span class="grade-score">{{ gradeDisplay(row, subject.id) }}</span>
                    <span v-if="gradePredicate(row, subject.id)" class="grade-pred">{{ gradePredicate(row, subject.id) }}</span>
                  </template>
                  <span v-else class="muted">—</span>
                </td>
                <td class="col-avg">
                  <span v-if="row.average != null" class="avg-value">{{ row.average }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="col-rank">
                  <span
                    v-if="row.rank != null"
                    class="rank-chip"
                    :class="rankClass(row.rank)"
                  >#{{ row.rank }}</span>
                  <span v-else class="muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { gradeBookApi } from '@/api/gradeBook'
import { semesterApi } from '@/api/semester'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useActiveAcademicPeriod } from '@/composables/useActiveAcademicPeriod'

const toast = useToast()
const route = useRoute()
const authStore = useAuthStore()
const { ensureLoaded, resolveDefaultSemesterId } = useActiveAcademicPeriod()
const loading = ref(false)
const exporting = ref(false)
const printingPdf = ref(false)
const subjects = ref([])
const rows = ref([])
const semesters = ref([])
const studentQuery = ref('')

const filters = ref({
  class_id: '',
  semester_id: '',
})

const homeroomClasses = computed(() => authStore.user?.homeroom_classes || [])
const classFilterId = computed(() => route.query.class_id ? String(route.query.class_id) : '')
const selectedClassName = computed(() => {
  const id = filters.value.class_id || classFilterId.value
  return homeroomClasses.value.find((c) => String(c.id) === String(id))?.name || ''
})
const selectedSemesterName = computed(() => {
  const id = filters.value.semester_id
  return semesters.value.find((s) => String(s.id) === String(id))?.name || ''
})

const hasSelection = computed(() => filters.value.class_id && filters.value.semester_id)
const hasData = computed(() => rows.value.length > 0)

const filteredRows = computed(() => {
  const q = studentQuery.value.trim().toLowerCase()
  if (!q) return rows.value
  return rows.value.filter((row) => {
    const name = String(row.student?.name || '').toLowerCase()
    const nis = String(row.student?.nis || '').toLowerCase()
    const nisn = String(row.student?.nisn || '').toLowerCase()
    return name.includes(q) || nis.includes(q) || nisn.includes(q)
  })
})

function shortSubject(name) {
  if (!name) return '—'
  const parts = String(name).trim().split(/\s+/)
  if (parts.length === 1) return parts[0].slice(0, 6)
  return parts.map((p) => p[0]).join('').slice(0, 6).toUpperCase()
}

function subjectTitle(subject) {
  if (!subject) return ''
  const kkm = subject.kkm != null ? ` · KKM ${subject.kkm}` : ''
  return `${subject.name || ''}${kkm}`
}

function gradeEntry(row, subjectId) {
  return (row.subjects || []).find((s) => Number(s.subject_id) === Number(subjectId)) || null
}

function gradeRaw(row, subjectId) {
  const entry = gradeEntry(row, subjectId)
  if (!entry || entry.nilai_akhir === null || entry.nilai_akhir === undefined) return null
  return entry.nilai_akhir
}

function gradeDisplay(row, subjectId) {
  const v = gradeRaw(row, subjectId)
  return v == null ? '—' : v
}

function gradePredicate(row, subjectId) {
  return gradeEntry(row, subjectId)?.predicate || null
}

function gradeTitle(row, subjectId) {
  const entry = gradeEntry(row, subjectId)
  if (!entry || entry.nilai_akhir == null) return 'Belum ada nilai'
  const parts = [`Nilai ${entry.nilai_akhir}`]
  if (entry.kkm != null) parts.push(`KKM ${entry.kkm}`)
  if (entry.predicate) parts.push(`Predikat ${entry.predicate}`)
  if (entry.tuntas_label) parts.push(entry.tuntas_label)
  return parts.join(' · ')
}

function gradeCellClass(row, subjectId) {
  const entry = gradeEntry(row, subjectId)
  if (!entry || entry.nilai_akhir == null) return { 'is-empty': true }
  return {
    'is-tuntas': entry.is_tuntas === true,
    'is-belum': entry.is_tuntas === false,
  }
}

function rankClass(rank) {
  if (rank === 1) return 'rank-gold'
  if (rank === 2) return 'rank-silver'
  if (rank === 3) return 'rank-bronze'
  return ''
}

function rankRowClass(rank) {
  if (rank === 1) return 'row-rank-1'
  if (rank === 2) return 'row-rank-2'
  if (rank === 3) return 'row-rank-3'
  return ''
}

function onFilterChange() {
  subjects.value = []
  rows.value = []
  studentQuery.value = ''
  if (hasSelection.value) {
    loadRaport()
  }
}

async function loadRaport() {
  if (!hasSelection.value) return
  loading.value = true
  try {
    const res = await gradeBookApi.getByClassSemester({
      class_id: filters.value.class_id,
      semester_id: filters.value.semester_id,
    })
    const data = res.data?.data || {}
    subjects.value = Array.isArray(data.subjects) ? data.subjects : []
    rows.value = Array.isArray(data.rows) ? data.rows : []
  } catch (e) {
    toast.error('Gagal memuat rekap', e.formattedMessage || 'Rekap nilai kelas tidak dapat dimuat.')
    subjects.value = []
    rows.value = []
  } finally {
    loading.value = false
  }
}

async function exportCsv() {
  if (!hasSelection.value || !rows.value.length) return
  exporting.value = true
  try {
    const res = await gradeBookApi.exportClassRaport({
      class_id: filters.value.class_id,
      semester_id: filters.value.semester_id,
    })
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    const classSlug = (selectedClassName.value || 'kelas').replace(/\s+/g, '-').toLowerCase()
    link.setAttribute('download', `rekap-nilai-${classSlug}-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export CSV berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor CSV', e.formattedMessage || 'Rekap nilai tidak dapat diekspor.')
  } finally {
    exporting.value = false
  }
}

async function printPdf() {
  if (!hasSelection.value || !rows.value.length) return
  printingPdf.value = true
  try {
    const res = await gradeBookApi.exportClassRaportPdf({
      class_id: filters.value.class_id,
      semester_id: filters.value.semester_id,
    })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Rekap Nilai — ${selectedClassName.value || 'Kelas'}`
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
        .toolbar {
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
          padding: 10px 14px; background: #0f172a; color: #f8fafc;
          border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
        }
        .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
        .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
        .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>${title}<span class="hint">Preview cetak</span></h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
  } catch (e) {
    toast.error('Gagal mencetak PDF', e.formattedMessage || 'PDF rekap nilai tidak dapat dibuka.')
  } finally {
    printingPdf.value = false
  }
}

function syncClassFromRoute() {
  const fromQuery = classFilterId.value
  const allowed = new Set(homeroomClasses.value.map((c) => String(c.id)))
  if (fromQuery && allowed.has(fromQuery)) {
    filters.value.class_id = fromQuery
    return
  }
  if (homeroomClasses.value.length === 1) {
    filters.value.class_id = String(homeroomClasses.value[0].id)
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 200 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

watch(homeroomClasses, () => syncClassFromRoute(), { deep: true })

onMounted(async () => {
  syncClassFromRoute()
  await Promise.all([loadSemesters(), ensureLoaded()])
  if (route.query.semester_id) {
    filters.value.semester_id = String(route.query.semester_id)
  } else if (!filters.value.semester_id) {
    filters.value.semester_id = resolveDefaultSemesterId('', semesters.value)
  }
  if (hasSelection.value) {
    loadRaport()
  }
})
</script>

<style scoped>
.raport-page {
  padding: 1.25rem 1.5rem 2rem;
  max-width: 100%;
  min-height: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 18%, #f1f5f9 100%);
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.page-header-main {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  flex-wrap: wrap;
  min-width: 0;
}

.page-header-copy {
  min-width: 0;
}

.back-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}

.back-chip:hover {
  border-color: #86efac;
  color: #047857;
  background: #ecfdf5;
}

.page-title {
  font-size: 1.45rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #0f172a;
  margin: 0 0 0.2rem;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.toolbar-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 0.95rem 1.05rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.toolbar-fields {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1rem;
  align-items: flex-end;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  min-width: 0;
}

.field-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #64748b;
}

.filter-select {
  padding: 0.55rem 0.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9rem;
  min-width: 200px;
  background: #f8fafc;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}

.filter-select:focus {
  background: #fff;
  border-color: #34d399;
  box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18);
}

.summary-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 0.85rem;
  padding-top: 0.85rem;
  border-top: 1px solid #f1f5f9;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.28rem 0.7rem;
  border-radius: 999px;
  background: #ecfdf5;
  color: #065f46;
  font-size: 0.8rem;
}

.summary-chip.muted {
  background: #f1f5f9;
  color: #475569;
}

.summary-chip strong {
  font-weight: 700;
}

.state-card {
  text-align: center;
  padding: 2.25rem 1.25rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.state-card.soft {
  background: #f8fafc;
  border-style: dashed;
}

.state-card h3 {
  margin: 0 0 0.4rem;
  color: #0f172a;
  font-size: 1.05rem;
}

.state-card p {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}

.state-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 0.85rem;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ecfdf5;
  color: #059669;
}

.loading-text {
  margin: 0;
  color: #64748b;
}

.table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1rem 1.05rem 1.15rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.table-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.85rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}

.table-panel-head h2 {
  margin: 0;
  font-size: 1.05rem;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.table-hint {
  margin: 0.2rem 0 0;
  color: #94a3b8;
  font-size: 0.8rem;
}

.search-wrap {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: min(240px, 70vw);
  padding: 0.5rem 0.75rem 0.5rem 2.1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 0.88rem;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}

.search-input:focus {
  background: #fff;
  border-color: #34d399;
  box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18);
}

.table-empty {
  padding: 1.5rem;
  text-align: center;
  color: #64748b;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px dashed #e2e8f0;
}

.table-container {
  overflow: auto;
  max-height: min(70vh, 820px);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.data-table {
  width: max-content;
  min-width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.86rem;
}

.data-table th,
.data-table td {
  padding: 0.55rem 0.65rem;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
  background: #fff;
}

.data-table th {
  position: sticky;
  top: 0;
  z-index: 3;
  font-weight: 700;
  font-size: 0.78rem;
  letter-spacing: 0.01em;
  background: #ecfdf5;
  color: #065f46;
  border-bottom: 1px solid #a7f3d0;
  text-align: center;
}

.data-table tbody tr:hover td {
  background: #f8fafc;
}

.data-table tbody tr:hover td.sticky-col {
  background: #f1f5f9;
}

.col-no {
  width: 44px;
  text-align: center;
  color: #64748b;
}

.col-nis {
  width: 88px;
  text-align: center;
  color: #475569;
  font-variant-numeric: tabular-nums;
}

.col-name {
  text-align: left !important;
  min-width: 160px;
  font-weight: 600;
  color: #0f172a;
}

.sticky-col {
  position: sticky;
  left: 0;
  z-index: 2;
}

.sticky-nis {
  left: 44px;
}

.sticky-name {
  left: 132px;
  box-shadow: 4px 0 8px -6px rgba(15, 23, 42, 0.18);
}

th.sticky-col {
  z-index: 4;
  background: #d1fae5;
}

th.sticky-name {
  text-align: left !important;
}

.col-subject {
  min-width: 72px;
  max-width: 110px;
  vertical-align: bottom;
}

.subject-code {
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  color: #047857;
  line-height: 1.2;
}

.subject-name {
  margin-top: 0.15rem;
  font-size: 0.68rem;
  font-weight: 500;
  color: #64748b;
  line-height: 1.2;
  max-width: 100px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  white-space: normal;
}

.subject-kkm {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.62rem;
  font-weight: 600;
  color: #0369a1;
}

.col-grade {
  text-align: center;
  font-variant-numeric: tabular-nums;
  color: #0f172a;
  min-width: 56px;
}

.col-grade .grade-score {
  display: block;
  font-weight: 600;
  line-height: 1.2;
}

.col-grade .grade-pred {
  display: block;
  margin-top: 0.1rem;
  font-size: 0.65rem;
  font-weight: 700;
  color: #64748b;
}

.col-grade.is-empty {
  color: #cbd5e1;
}

.col-grade.is-tuntas .grade-score { color: #059669; }
.col-grade.is-tuntas .grade-pred { color: #047857; }
.col-grade.is-belum .grade-score { color: #dc2626; }
.col-grade.is-belum .grade-pred { color: #b91c1c; }

.col-avg {
  text-align: center;
  min-width: 88px;
  background: #f0fdf4 !important;
  font-weight: 700;
}

th.col-avg,
th.col-rank {
  background: #a7f3d0;
  color: #064e3b;
}

.avg-value {
  color: #047857;
  font-variant-numeric: tabular-nums;
}

.col-rank {
  text-align: center;
  min-width: 84px;
  background: #f0fdf4 !important;
}

.rank-chip {
  display: inline-flex;
  min-width: 36px;
  justify-content: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: #f1f5f9;
  color: #334155;
}

.rank-gold { background: #fef3c7; color: #b45309; }
.rank-silver { background: #e2e8f0; color: #475569; }
.rank-bronze { background: #ffedd5; color: #c2410c; }

.row-rank-1 td { background: #fffbeb; }
.row-rank-2 td { background: #f8fafc; }
.row-rank-3 td { background: #fff7ed; }

.row-rank-1 td.sticky-col,
.row-rank-1 td.col-avg,
.row-rank-1 td.col-rank { background: #fef3c7; }

.row-rank-2 td.sticky-col,
.row-rank-2 td.col-avg,
.row-rank-2 td.col-rank { background: #e2e8f0; }

.row-rank-3 td.sticky-col,
.row-rank-3 td.col-avg,
.row-rank-3 td.col-rank { background: #ffedd5; }

.muted {
  color: #94a3b8;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 0.95rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.18);
}

.btn-secondary {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #334155;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  white-space: nowrap;
}

@media (max-width: 720px) {
  .raport-page {
    padding: 1rem;
  }

  .filter-select {
    min-width: 0;
    width: 100%;
  }

  .field {
    width: 100%;
  }

  .sticky-nis,
  .sticky-name {
    left: auto;
    position: static;
    box-shadow: none;
  }

  th.sticky-col,
  td.sticky-col {
    position: static;
  }

  .data-table {
    font-size: 0.75rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.4rem 0.45rem;
  }

  .col-name {
    min-width: 110px;
  }

  .col-subject {
    min-width: 56px;
    max-width: 80px;
  }

  .col-grade {
    min-width: 44px;
  }

  .col-avg,
  .col-rank {
    min-width: 64px;
  }

  .col-grade .grade-pred {
    font-size: 0.6rem;
  }
}
</style>
