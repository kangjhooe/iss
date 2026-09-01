<template>
    <div class="raport-page">
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
                <th>Rata Penilaian</th>
                <th>UTS</th>
                <th>UAS</th>
                <th>Nilai Akhir</th>
                <th>KKM</th>
                <th>Predikat</th>
                <th>Ketuntasan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in raportRows" :key="row.subject_id">
                <td>
                  {{ displayValue(row.subject?.name) }}
                  <div v-if="penilaianDetail(row)" class="penilaian-detail">{{ penilaianDetail(row) }}</div>
                </td>
                <td>{{ row.rata_penilaian ?? 'Belum ada data' }}</td>
                <td>{{ row.uts ?? 'Belum ada data' }}</td>
                <td>{{ row.uas ?? 'Belum ada data' }}</td>
                <td>{{ row.nilai_akhir ?? 'Belum ada data' }}</td>
                <td>{{ row.kkm ?? '—' }}</td>
                <td>
                  <span v-if="row.predicate" class="pred-chip" :class="`pred-${row.predicate}`">{{ row.predicate }}</span>
                  <span v-else>—</span>
                </td>
                <td>
                  <span
                    v-if="row.tuntas_label"
                    class="tuntas-chip"
                    :class="row.is_tuntas ? 'tuntas' : 'belum'"
                  >{{ row.tuntas_label }}</span>
                  <span v-else>—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { gradeBookApi } from '@/api/gradeBook'
import { studentApi } from '@/api/student'
import { semesterApi } from '@/api/semester'
import { teacherApi } from '@/api/teacher'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useActiveAcademicPeriod } from '@/composables/useActiveAcademicPeriod'

const toast = useToast()
const route = useRoute()
const authStore = useAuthStore()
const { ensureLoaded, resolveDefaultSemesterId } = useActiveAcademicPeriod()
const loading = ref(false)
const exporting = ref(false)
const raportRows = ref([])
const students = ref([])
const semesters = ref([])

const filters = ref({
  student_id: '',
  semester_id: '',
})

const classFilterId = computed(() => route.query.class_id ? String(route.query.class_id) : '')
const canAccessStudentModule = computed(() => (authStore.user?.permissions || []).includes('student'))
const homeroomClassIds = computed(() => (authStore.user?.homeroom_class_ids || []).map(Number))

const hasSelection = computed(() => filters.value.student_id && filters.value.semester_id)

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function penilaianDetail(row) {
  const src = row?.penilaian
  if (!src || typeof src !== 'object') return ''
  const parts = Object.keys(src)
    .map((k) => Number(k))
    .filter((n) => Number.isInteger(n) && n > 0)
    .sort((a, b) => a - b)
    .map((n) => {
      const v = src[n] ?? src[String(n)]
      return v == null || v === '' ? null : `P${n}: ${v}`
    })
    .filter(Boolean)
  return parts.length ? parts.join(' · ') : ''
}

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
    toast.error('Gagal memuat raport', e.formattedMessage || 'Data raport tidak dapat dimuat. Periksa koneksi dan coba lagi.')
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
    toast.error('Gagal mengekspor raport', e.formattedMessage || 'Raport tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = false
  }
}

async function loadStudents() {
  try {
    const classId = classFilterId.value
    const isHomeroomClass = classId && homeroomClassIds.value.includes(Number(classId))

    if (classId && isHomeroomClass) {
      const res = await teacherApi.getHomeroomClassStudents(classId, {
        per_page: 100,
        status: 'Aktif',
      })
      students.value = res.data?.data || []
      return
    }

    if (!canAccessStudentModule.value) {
      students.value = []
      return
    }

    const params = { per_page: 500, status: 'Aktif' }
    if (classId) params.class_id = classId
    const res = await studentApi.getAll(params)
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
  await Promise.all([loadStudents(), loadSemesters(), ensureLoaded()])
  if (route.query.semester_id) {
    filters.value.semester_id = String(route.query.semester_id)
  } else {
    filters.value.semester_id = resolveDefaultSemesterId('', semesters.value)
  }
  if (route.query.student_id) {
    filters.value.student_id = String(route.query.student_id)
  }
})
</script>

<style scoped>
.raport-page {
  padding: 1.5rem;
  max-width: 100%;
  min-height: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
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
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  min-width: 200px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-select {
    min-width: 0;
    width: 100%;
  }
}
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
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
  width: 100%;
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
.penilaian-detail {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}
.pred-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #e2e8f0;
  color: #334155;
}
.pred-chip.pred-A { background: #d1fae5; color: #065f46; }
.pred-chip.pred-B { background: #dbeafe; color: #1e40af; }
.pred-chip.pred-C { background: #fef3c7; color: #92400e; }
.pred-chip.pred-D { background: #fee2e2; color: #991b1b; }
.tuntas-chip {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.tuntas-chip.tuntas { background: #d1fae5; color: #065f46; }
.tuntas-chip.belum { background: #fee2e2; color: #991b1b; }
.data-table th,
.data-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.data-table th {
  font-weight: 600;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #065f46;
}
.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
