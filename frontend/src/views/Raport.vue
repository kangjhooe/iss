<template>
  <Layout>
    <div class="raport-page">
      <div class="page-header">
        <h1 class="page-title">Raport Siswa</h1>
        <p class="page-subtitle">Lihat dan ekspor nilai per siswa per semester</p>
      </div>

      <div class="filters filters-inline">
        <select v-model="filters.student_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Siswa</option>
          <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.nis || s.nisn || '-' }})</option>
        </select>
        <select v-model="filters.semester_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Semester</option>
          <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <button
          v-if="hasSelection"
          type="button"
          class="btn-primary btn-compact"
          :disabled="loading"
          @click="loadRaport"
        >
          {{ loading ? 'Memuat...' : 'Tampilkan Raport' }}
        </button>
      </div>

      <div v-if="!hasSelection" class="empty-state empty-state-hint">
        <p class="empty-desc">Pilih <strong>Siswa</strong> dan <strong>Semester</strong> lalu klik Tampilkan Raport.</p>
      </div>

      <div v-else-if="loading && !raportRows.length" class="loading-wrap">
        <p>Memuat raport...</p>
      </div>

      <div v-else-if="raportRows.length === 0" class="empty-state">
        <h3 class="empty-title">Belum ada nilai</h3>
        <p class="empty-desc">Siswa ini belum memiliki nilai untuk semester yang dipilih.</p>
      </div>

      <template v-else>
        <div class="table-actions">
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="exporting"
            @click="exportRaport"
          >
            {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
          </button>
        </div>
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Mata Pelajaran</th>
                <th>UH</th>
                <th>UTS</th>
                <th>UAS</th>
                <th>Tugas</th>
                <th>Nilai Akhir</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in raportRows" :key="row.subject_id">
                <td>{{ row.subject?.name }}</td>
                <td>{{ row.uh ?? '-' }}</td>
                <td>{{ row.uts ?? '-' }}</td>
                <td>{{ row.uas ?? '-' }}</td>
                <td>{{ row.tugas ?? '-' }}</td>
                <td>{{ row.nilai_akhir ?? '-' }}</td>
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
import Layout from '@/components/Layout.vue'
import { gradeBookApi } from '@/api/gradeBook'
import { studentApi } from '@/api/student'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(false)
const exporting = ref(false)
const raportRows = ref([])
const students = ref([])
const semesters = ref([])

const filters = ref({
  student_id: '',
  semester_id: '',
})

const hasSelection = computed(() => filters.value.student_id && filters.value.semester_id)

function onFilterChange() {
  raportRows.value = []
}

async function loadRaport() {
  if (!hasSelection.value) return
  loading.value = true
  try {
    const res = await gradeBookApi.getByStudentSemester({
      student_id: filters.value.student_id,
      semester_id: filters.value.semester_id,
    })
    raportRows.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat raport')
    raportRows.value = []
  } finally {
    loading.value = false
  }
}

async function exportRaport() {
  if (!hasSelection.value) return
  exporting.value = true
  try {
    const res = await gradeBookApi.exportStudentRaport({
      student_id: filters.value.student_id,
      semester_id: filters.value.semester_id,
    })
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `raport-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export raport berhasil diunduh')
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal mengekspor')
  } finally {
    exporting.value = false
  }
}

async function loadStudents() {
  try {
    const res = await studentApi.getAll({ per_page: 500 })
    students.value = res.data.data || []
  } catch {
    students.value = []
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

onMounted(async () => {
  await Promise.all([loadStudents(), loadSemesters()])
})
</script>

<style scoped>
.raport-page {
  padding: 1.5rem;
  max-width: 100%;
}
.page-header {
  margin-bottom: 1.5rem;
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
  min-width: 200px;
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
.empty-desc {
  color: #64748b;
  margin: 0;
}
.empty-title {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}
.loading-wrap {
  padding: 2rem;
  text-align: center;
}
.table-actions {
  margin-bottom: 1rem;
}
.table-container {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table th,
.data-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.data-table th {
  font-weight: 600;
  background: #f8fafc;
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
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
