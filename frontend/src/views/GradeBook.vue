<template>
  <Layout>
    <div class="grade-book-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Buku Nilai</h1>
            <p class="page-subtitle">Input nilai per kelas, mapel, dan semester (UH, UTS, UAS, Tugas, Nilai Akhir)</p>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <select v-model="filters.semester_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Semester</option>
          <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select v-model="filters.class_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Kelas</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="filters.subject_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Mata Pelajaran</option>
          <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
        </select>
        <button
          v-if="hasSelection"
          type="button"
          class="btn-primary btn-compact"
          :disabled="loading"
          @click="loadGrades"
        >
          {{ loading ? 'Memuat...' : 'Tampilkan Nilai' }}
        </button>
      </div>

      <div v-if="!hasSelection" class="empty-state empty-state-hint">
        <p class="empty-desc">Pilih <strong>Semester</strong>, <strong>Kelas</strong>, dan <strong>Mata Pelajaran</strong> lalu klik Tampilkan Nilai.</p>
      </div>

      <div v-else-if="loading && !rows.length" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="10" :columns="8" :cell-widths="['40px', '1fr', '90px', '80px', '80px', '80px', '80px', '100px']" />
      </div>

      <div v-else-if="rows.length === 0" class="empty-state">
        <h3 class="empty-title">Tidak ada siswa</h3>
        <p class="empty-desc">Kelas ini belum memiliki siswa atau filter belum sesuai.</p>
      </div>

      <template v-else>
        <div class="table-actions">
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="exporting"
            @click="exportToCsv"
          >
            {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
          </button>
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="saving"
            @click="saveGrades"
          >
            {{ saving ? 'Menyimpan...' : 'Simpan Nilai' }}
          </button>
        </div>
        <div class="table-container">
          <table class="data-table grade-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th class="col-name">Nama</th>
                <th class="col-nis">NIS</th>
                <th class="col-grade">UH</th>
                <th class="col-grade">UTS</th>
                <th class="col-grade">UAS</th>
                <th class="col-grade">Tugas</th>
                <th class="col-grade">Nilai Akhir <span class="formula-hint">(otomatis)</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in rows" :key="row.student_id">
                <td class="col-no">{{ idx + 1 }}</td>
                <td class="col-name">{{ row.student?.name }}</td>
                <td class="col-nis">{{ row.student?.nis || row.student?.nisn || '-' }}</td>
                <td class="col-grade">
                  <input
                    v-model.number="row.uh"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.uts"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.uas"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.tugas"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.nilai_akhir"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { gradeBookApi } from '@/api/gradeBook'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const route = useRoute()

const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const rows = ref([])
const semesters = ref([])
const classes = ref([])
const subjects = ref([])

const filters = ref({
  semester_id: '',
  class_id: '',
  subject_id: '',
})

const hasSelection = computed(() => {
  return filters.value.semester_id && filters.value.class_id && filters.value.subject_id
})

const WEIGHT_UH = 0.20
const WEIGHT_UTS = 0.30
const WEIGHT_UAS = 0.30
const WEIGHT_TUGAS = 0.20

function computeNilaiAkhirForRow(row) {
  const uh = toNum(row.uh)
  const uts = toNum(row.uts)
  const uas = toNum(row.uas)
  const tugas = toNum(row.tugas)
  if (uh != null && uts != null && uas != null && tugas != null) {
    const n = uh * WEIGHT_UH + uts * WEIGHT_UTS + uas * WEIGHT_UAS + tugas * WEIGHT_TUGAS
    row.nilai_akhir = Math.round(n * 100) / 100
  }
}

function onFilterChange() {
  rows.value = []
}

async function loadGrades() {
  if (!hasSelection.value) return
  loading.value = true
  try {
    const res = await gradeBookApi.getByClassSubjectSemester({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
    })
    rows.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat buku nilai')
    rows.value = []
  } finally {
    loading.value = false
  }
}

async function exportToCsv() {
  if (!hasSelection.value) return
  exporting.value = true
  try {
    const res = await gradeBookApi.export({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
    })
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `buku-nilai-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal mengekspor')
  } finally {
    exporting.value = false
  }
}

function toNum(v) {
  if (v === '' || v == null || v === undefined) return null
  const n = Number(v)
  return !isNaN(n) ? n : null
}

async function saveGrades() {
  if (!hasSelection.value || rows.value.length === 0) return
  saving.value = true
  try {
    const grades = rows.value.map((row) => {
      const uh = toNum(row.uh)
      const uts = toNum(row.uts)
      const uas = toNum(row.uas)
      const tugas = toNum(row.tugas)
      let nilaiAkhir = toNum(row.nilai_akhir)
      if (nilaiAkhir == null && uh != null && uts != null && uas != null && tugas != null) {
        nilaiAkhir = Math.round((uh * WEIGHT_UH + uts * WEIGHT_UTS + uas * WEIGHT_UAS + tugas * WEIGHT_TUGAS) * 100) / 100
      }
      return {
        student_id: row.student_id,
        uh,
        uts,
        uas,
        tugas,
        nilai_akhir: nilaiAkhir,
      }
    })
    await gradeBookApi.bulkSave({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
      grades,
    })
    toast.success('Nilai berhasil disimpan')
    await loadGrades()
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal menyimpan nilai')
  } finally {
    saving.value = false
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

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 200 })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadSubjects() {
  try {
    const res = await subjectApi.getAll({ per_page: 200 })
    subjects.value = res.data.data || []
  } catch {
    subjects.value = []
  }
}

onMounted(async () => {
  await Promise.all([loadSemesters(), loadClasses(), loadSubjects()])
  const q = route.query
  if (q.semester_id) filters.value.semester_id = String(q.semester_id)
  if (q.class_id) filters.value.class_id = String(q.class_id)
  if (q.subject_id) filters.value.subject_id = String(q.subject_id)
  if (hasSelection.value) await loadGrades()
})
</script>

<style scoped>
.grade-book-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
}
.page-header {
  margin-bottom: 1.5rem;
}
.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.header-icon {
  flex-shrink: 0;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}
.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
  align-items: center;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  min-width: 160px;
}
.loading-wrap {
  width: 100%;
  margin: 1rem 0;
}
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed #e2e8f0;
}
.empty-state-hint {
  padding: 1.5rem;
}
.empty-title {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}
.empty-desc {
  color: #64748b;
  margin: 0;
}
.table-actions {
  margin-bottom: 1rem;
}
.table-container {
  overflow-x: auto;
}
.data-table.grade-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table.grade-table th,
.data-table.grade-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.data-table.grade-table th {
  font-weight: 600;
  background: #f8fafc;
}
.col-no {
  width: 40px;
  text-align: center;
}
.col-name {
  min-width: 160px;
}
.col-nis {
  width: 100px;
}
.col-grade {
  width: 90px;
}
.formula-hint {
  font-size: 0.75rem;
  font-weight: normal;
  color: #64748b;
}
.grade-input {
  width: 100%;
  max-width: 80px;
  padding: 0.4rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: center;
}
.grade-input:focus {
  outline: none;
  border-color: #0ea5e9;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
}
.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
  color: #fff;
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid #e2e8f0;
  background: #f1f5f9;
  color: #475569;
}
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
