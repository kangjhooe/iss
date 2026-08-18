<template>
  <Layout>
    <div class="luluskan-page">
      <div class="page-header">
        <h1 class="page-title">Luluskan Siswa</h1>
        <p class="page-subtitle">
          Pilih siswa aktif per kelas, lalu tetapkan sebagai lulusan (alumni). Riwayat kelas dan tahun lulus dicatat otomatis.
        </p>
      </div>

      <div class="form-card">
        <h2 class="section-title">1. Pilih Kelas & Tahun Ajaran</h2>
        <div class="filter-row">
          <div class="field-group">
            <label>Tahun Ajaran</label>
            <select v-model="academicYearId" class="select-input">
              <option value="">-- Pilih Tahun Ajaran --</option>
              <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
            </select>
          </div>
          <div class="field-group">
            <label>Kelas</label>
            <select v-model="classId" class="select-input" :disabled="!academicYearId || loadingClasses">
              <option value="">{{ loadingClasses ? 'Memuat kelas...' : '-- Pilih Kelas --' }}</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">
                {{ c.name }}<template v-if="c.students_count != null"> ({{ c.students_count }} siswa)</template>
              </option>
            </select>
            <p v-if="academicYearId && !loadingClasses && classes.length === 0" class="field-hint warn">
              Tidak ada kelas di tahun ajaran ini. Coba pilih tahun ajaran tempat kelas/siswa masih terdaftar.
            </p>
          </div>
        </div>
      </div>

      <div v-if="classId && academicYearId" class="form-card">
        <h2 class="section-title">2. Daftar Siswa (Aktif)</h2>
        <div v-if="loadingStudents" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="5" :columns="5" />
        </div>
        <div v-else-if="students.length === 0" class="empty-inline">
          Belum ada siswa aktif di kelas ini.
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 44px;">
                  <input
                    type="checkbox"
                    :checked="selectedAll"
                    :indeterminate="selectedIds.length > 0 && selectedIds.length < students.length"
                    @change="toggleSelectAll"
                  />
                </th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>JK</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in students" :key="s.id">
                <td>
                  <input type="checkbox" :value="s.id" v-model="selectedIds" />
                </td>
                <td>{{ s.nis || '-' }}</td>
                <td>{{ s.nisn || '-' }}</td>
                <td>{{ s.name }}</td>
                <td>{{ s.gender === 'L' ? 'L' : 'P' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="classId && academicYearId && students.length > 0" class="form-card">
        <h2 class="section-title">3. Tahun Lulus & Proses</h2>
        <div class="filter-row">
          <div class="field-group">
            <label>Tahun Lulus</label>
            <input
              v-model.number="graduationYear"
              type="number"
              min="1900"
              :max="currentYear + 2"
              class="select-input"
              placeholder="Contoh: 2026"
            />
            <p class="field-hint">Kosongkan untuk memakai tahun dari tahun ajaran / tahun berjalan.</p>
          </div>
        </div>

        <div class="action-row">
          <button
            type="button"
            class="btn-primary"
            :disabled="selectedIds.length === 0 || graduating"
            @click="confirmGraduate"
          >
            <span v-if="graduating">Memproses...</span>
            <span v-else>Luluskan ({{ selectedIds.length }} siswa)</span>
          </button>
          <router-link to="/alumni" class="btn-secondary btn-link">Lihat Alumni</router-link>
        </div>
      </div>

      <div v-if="resultMessage" class="result-card" :class="resultSuccess ? 'result-success' : 'result-warning'">
        <p>{{ resultMessage }}</p>
        <ul v-if="failedList.length > 0" class="failed-list">
          <li v-for="f in failedList" :key="f.id">ID {{ f.id }}: {{ f.reason }}</li>
        </ul>
        <router-link v-if="resultSuccess" to="/alumni" class="result-link">Buka halaman Alumni →</router-link>
      </div>
    </div>

    <div v-if="showConfirm" class="modal-overlay" @click.self="showConfirm = false">
      <div class="modal-box">
        <h3>Konfirmasi Luluskan Siswa</h3>
        <p>
          <strong>{{ selectedIds.length }}</strong> siswa akan diubah status menjadi
          <strong>Lulus</strong>
          <template v-if="graduationYear"> (tahun lulus {{ graduationYear }})</template>.
          Mereka akan muncul di daftar Alumni. Lanjutkan?
        </p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showConfirm = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="graduating" @click="doGraduate">
            Ya, Luluskan
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { studentApi } from '@/api/student'
import { alumniApi } from '@/api/alumni'
import { classApi } from '@/api/class'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useAuthStore } from '@/stores/auth'

const referenceStore = useReferenceDataStore()
const authStore = useAuthStore()
const academicYears = computed(() => referenceStore.academicYears)
const currentYear = new Date().getFullYear()

const classes = ref([])
const academicYearId = ref('')
const classId = ref('')
const students = ref([])
const selectedIds = ref([])
const graduationYear = ref(currentYear)
const loadingClasses = ref(false)
const loadingStudents = ref(false)
const graduating = ref(false)
const showConfirm = ref(false)
const resultMessage = ref('')
const resultSuccess = ref(false)
const failedList = ref([])

const selectedAll = computed(() => {
  return students.value.length > 0 && selectedIds.value.length === students.value.length
})

const selectedClass = computed(() =>
  classes.value.find((c) => String(c.id) === String(classId.value))
)

function institutionParams() {
  const params = {}
  const fromUser = authStore.activeInstitutionId || authStore.user?.institution_id
  const fromClass = selectedClass.value?.institution_id
  if (fromUser) params.institution_id = fromUser
  else if (fromClass) params.institution_id = fromClass
  return params
}

function inferYearFromAcademicYearName(name) {
  if (!name) return currentYear
  const m = String(name).match(/(\d{4})\s*\/\s*(\d{4})/)
  if (m) return Number(m[2])
  const m2 = String(name).match(/(\d{4})/)
  if (m2) return Number(m2[1]) + 1
  return currentYear
}

async function loadClasses() {
  if (!academicYearId.value) {
    classes.value = []
    classId.value = ''
    return
  }
  loadingClasses.value = true
  try {
    const res = await classApi.getAll({
      academic_year_id: academicYearId.value,
      per_page: 100,
      ...institutionParams()
    })
    classes.value = res.data?.data ?? []
  } catch {
    classes.value = []
  } finally {
    loadingClasses.value = false
  }
  classId.value = ''
  students.value = []
  selectedIds.value = []
}

async function loadStudents() {
  if (!classId.value) {
    students.value = []
    selectedIds.value = []
    return
  }
  loadingStudents.value = true
  try {
    // Jangan filter academic_year_id di siswa: class_id sudah cukup.
    // Filter year di student sering mengosongkan daftar jika data tahun ajaran
    // siswa tidak sinkron dengan kelasnya.
    const res = await classApi.getStudents(classId.value, {
      status: 'Aktif',
      per_page: 100
    })
    const data = (res.data?.data ?? []).filter((s) => s.status === 'Aktif')
    students.value = data
    selectedIds.value = data.map((s) => s.id)
  } catch {
    try {
      const res = await studentApi.getAll({
        class_id: classId.value,
        status: 'Aktif',
        per_page: 100,
        ...institutionParams()
      })
      const data = res.data?.data ?? []
      students.value = data
      selectedIds.value = data.map((s) => s.id)
    } catch {
      students.value = []
      selectedIds.value = []
    }
  } finally {
    loadingStudents.value = false
  }
}

function toggleSelectAll() {
  if (selectedIds.value.length === students.value.length) {
    selectedIds.value = []
  } else {
    selectedIds.value = students.value.map((s) => s.id)
  }
}

function confirmGraduate() {
  if (selectedIds.value.length === 0) return
  showConfirm.value = true
}

async function doGraduate() {
  graduating.value = true
  resultMessage.value = ''
  failedList.value = []
  try {
    const payload = {
      student_ids: selectedIds.value
    }
    if (graduationYear.value) {
      payload.graduation_year = Number(graduationYear.value)
    }

    const res = await alumniApi.graduateBulk(payload)
    const data = res.data
    showConfirm.value = false
    resultSuccess.value = (data.success ?? 0) > 0
    resultMessage.value = data.message || `${data.success} siswa berhasil diluluskan.`
    failedList.value = data.failed || []
    if (data.success > 0) {
      await loadStudents()
    }
  } catch (err) {
    showConfirm.value = false
    resultSuccess.value = false
    const errors = err.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0]
      resultMessage.value = Array.isArray(first) ? first[0] : String(first)
    } else {
      resultMessage.value = err.response?.data?.message || 'Gagal memproses kelulusan siswa.'
    }
    failedList.value = []
  } finally {
    graduating.value = false
  }
}

onMounted(() => {
  referenceStore.getAcademicYears()
})

watch(academicYearId, (id) => {
  const ay = academicYears.value.find((a) => String(a.id) === String(id))
  if (ay) {
    graduationYear.value = inferYearFromAcademicYearName(ay.name)
  }
  loadClasses()
})

watch(classId, () => {
  loadStudents()
})
</script>

<style scoped>
.luluskan-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
  background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.page-header {
  margin-bottom: 24px;
}

.page-header .page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.page-header .page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  line-height: 1.5;
}

.form-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  padding: 24px;
  margin-bottom: 20px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: #1e293b;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.field-group {
  min-width: 200px;
}

.field-group label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  margin-bottom: 6px;
}

.field-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #94a3b8;
}

.field-hint.warn {
  color: #b45309;
}

.select-input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  background: #fff;
  box-sizing: border-box;
}

.table-wrap {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table th,
.data-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
}

.empty-inline {
  color: #64748b;
  padding: 16px 0;
}

.loading-wrap {
  width: 100%;
  padding: 16px 0;
}

.action-row {
  margin-top: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.btn-primary {
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  border: none;
  background: #2563eb;
  color: white;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 500;
  border: 2px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  color: #334155;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-link {
  text-decoration: none;
}

.result-card {
  padding: 16px 20px;
  border-radius: 12px;
  margin-top: 20px;
}

.result-success {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}

.result-warning {
  background: #fef3c7;
  border: 1px solid #fcd34d;
  color: #92400e;
}

.failed-list {
  margin: 8px 0 0 0;
  padding-left: 20px;
  font-size: 13px;
}

.result-link {
  display: inline-block;
  margin-top: 10px;
  font-weight: 600;
  color: inherit;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-box {
  background: white;
  border-radius: 16px;
  padding: 24px;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-box h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
}

.modal-box p {
  margin: 0 0 20px 0;
  color: #475569;
  line-height: 1.5;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

@media (max-width: 768px) {
  .field-group {
    min-width: 0;
    flex: 1 1 100%;
    width: 100%;
  }
  .filter-row {
    flex-direction: column;
    align-items: stretch;
  }
  .modal-actions {
    flex-direction: column-reverse;
  }
  .modal-actions button {
    width: 100%;
  }
}
</style>
