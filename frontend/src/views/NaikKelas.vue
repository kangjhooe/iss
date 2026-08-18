<template>
  <Layout>
    <div class="naik-kelas-page">
      <div class="page-header">
        <h1 class="page-title">Naik Kelas</h1>
        <p class="page-subtitle">
          Pindahkan siswa aktif ke kelas dan tahun ajaran tujuan. Pastikan kelas tujuan sudah tersedia di tahun ajaran baru.
        </p>
      </div>

      <div class="form-card">
        <h2 class="section-title">1. Pilih Kelas & Tahun Ajaran Sumber</h2>
        <div class="filter-row">
          <div class="field-group">
            <label>Tahun Ajaran Sumber</label>
            <select v-model="sourceAcademicYearId" class="select-input">
              <option value="">-- Pilih Tahun Ajaran --</option>
              <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
            </select>
          </div>
          <div class="field-group">
            <label>Kelas Sumber</label>
            <select v-model="sourceClassId" class="select-input" :disabled="!sourceAcademicYearId || loadingSourceClasses">
              <option value="">{{ loadingSourceClasses ? 'Memuat kelas...' : '-- Pilih Kelas --' }}</option>
              <option v-for="c in sourceClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <p v-if="sourceAcademicYearId && !loadingSourceClasses && sourceClasses.length === 0" class="field-hint warn">
              Tidak ada kelas di tahun ajaran ini. Buat kelas di menu Kelas, atau pilih tahun ajaran lain.
            </p>
          </div>
        </div>
      </div>

      <div v-if="sourceClassId && sourceAcademicYearId" class="form-card">
        <h2 class="section-title">2. Daftar Siswa (Aktif)</h2>
        <div v-if="loadingStudents" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="5" :columns="5" />
        </div>
        <div v-else-if="sourceStudents.length === 0" class="empty-inline">
          Belum ada siswa aktif di kelas ini (atau siswa belum terhubung ke class_id).
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 44px;">
                  <input
                    type="checkbox"
                    :checked="selectedAll"
                    :indeterminate="selectedIds.length > 0 && selectedIds.length < sourceStudents.length"
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
              <tr v-for="s in sourceStudents" :key="s.id">
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

      <div v-if="sourceClassId && sourceAcademicYearId && sourceStudents.length > 0" class="form-card">
        <h2 class="section-title">3. Pilih Tahun Ajaran & Kelas Tujuan</h2>
        <div class="filter-row">
          <div class="field-group">
            <label>Tahun Ajaran Tujuan</label>
            <select v-model="targetAcademicYearId" class="select-input">
              <option value="">-- Pilih Tahun Ajaran --</option>
              <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
            </select>
          </div>
          <div class="field-group">
            <label>Kelas Tujuan</label>
            <select v-model="targetClassId" class="select-input" :disabled="!targetAcademicYearId || loadingTargetClasses">
              <option value="">{{ loadingTargetClasses ? 'Memuat kelas...' : '-- Pilih Kelas --' }}</option>
              <option v-for="c in targetClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div class="field-group">
            <label>Semester Tujuan (opsional)</label>
            <select v-model="targetSemesterId" class="select-input" :disabled="!targetAcademicYearId">
              <option value="">-- Default (Semester 1) --</option>
              <option v-for="sem in targetSemesters" :key="sem.id" :value="sem.id">{{ sem.name }}</option>
            </select>
          </div>
        </div>

        <div
          v-if="targetAcademicYearId && !loadingTargetClasses && targetClasses.length === 0"
          class="clone-box"
        >
          <p>
            Belum ada kelas di tahun ajaran tujuan.
            Salin struktur kelas dari tahun ajaran sumber agar naik kelas bisa dilanjutkan.
          </p>
          <button type="button" class="btn-secondary" :disabled="cloning" @click="cloneClassesToTarget">
            {{ cloning ? 'Menyalin...' : 'Salin Kelas dari Tahun Sumber' }}
          </button>
        </div>

        <div class="action-row">
          <button
            type="button"
            class="btn-primary"
            :disabled="!targetClassId || !targetAcademicYearId || selectedIds.length === 0 || promoting"
            @click="confirmPromote"
          >
            <span v-if="promoting">Memproses...</span>
            <span v-else>Naik Kelas ({{ selectedIds.length }} siswa)</span>
          </button>
        </div>
      </div>

      <div v-if="resultMessage" class="result-card" :class="resultSuccess ? 'result-success' : 'result-warning'">
        <p>{{ resultMessage }}</p>
        <ul v-if="failedList.length > 0" class="failed-list">
          <li v-for="f in failedList" :key="f.id">ID {{ f.id }}: {{ f.reason }}</li>
        </ul>
      </div>
    </div>

    <div v-if="showConfirm" class="modal-overlay" @click.self="showConfirm = false">
      <div class="modal-box">
        <h3>Konfirmasi Naik Kelas</h3>
        <p>
          <strong>{{ selectedIds.length }}</strong> siswa akan dipindahkan ke kelas dan tahun ajaran tujuan.
          Riwayat kelas akan tercatat otomatis. Lanjutkan?
        </p>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" @click="showConfirm = false">Batal</button>
          <button type="button" class="btn-primary" :disabled="promoting" @click="doPromote">Ya, Naik Kelas</button>
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
import { useReferenceDataStore } from '@/stores/referenceData'
import { useAuthStore } from '@/stores/auth'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'

const referenceStore = useReferenceDataStore()
const authStore = useAuthStore()
const academicYears = computed(() => referenceStore.academicYears)

const sourceClasses = ref([])
const targetClasses = ref([])
const targetSemesters = ref([])
const sourceAcademicYearId = ref('')
const sourceClassId = ref('')
const targetAcademicYearId = ref('')
const targetClassId = ref('')
const targetSemesterId = ref('')
const sourceStudents = ref([])
const selectedIds = ref([])
const loadingSourceClasses = ref(false)
const loadingTargetClasses = ref(false)
const loadingStudents = ref(false)
const promoting = ref(false)
const cloning = ref(false)
const showConfirm = ref(false)
const resultMessage = ref('')
const resultSuccess = ref(false)
const failedList = ref([])

const selectedAll = computed(() => {
  return sourceStudents.value.length > 0 && selectedIds.value.length === sourceStudents.value.length
})

const selectedSourceClass = computed(() =>
  sourceClasses.value.find((c) => String(c.id) === String(sourceClassId.value))
)

function institutionParams() {
  const params = {}
  const fromUser = authStore.activeInstitutionId || authStore.user?.institution_id
  const fromClass = selectedSourceClass.value?.institution_id
  if (fromUser) params.institution_id = fromUser
  else if (fromClass) params.institution_id = fromClass
  return params
}

async function loadSourceClasses() {
  if (!sourceAcademicYearId.value) {
    sourceClasses.value = []
    sourceClassId.value = ''
    return
  }
  loadingSourceClasses.value = true
  try {
    const res = await classApi.getAll({
      academic_year_id: sourceAcademicYearId.value,
      per_page: 100,
      ...institutionParams()
    })
    sourceClasses.value = res.data?.data ?? []
  } catch {
    sourceClasses.value = []
  } finally {
    loadingSourceClasses.value = false
  }
  sourceClassId.value = ''
  sourceStudents.value = []
  selectedIds.value = []
}

async function loadTargetClasses() {
  if (!targetAcademicYearId.value) {
    targetClasses.value = []
    targetClassId.value = ''
    return
  }
  loadingTargetClasses.value = true
  try {
    const res = await classApi.getAll({
      academic_year_id: targetAcademicYearId.value,
      per_page: 100,
      ...institutionParams()
    })
    targetClasses.value = res.data?.data ?? []
  } catch {
    targetClasses.value = []
  } finally {
    loadingTargetClasses.value = false
  }
  targetClassId.value = ''
}

async function loadTargetSemesters() {
  if (!targetAcademicYearId.value) {
    targetSemesters.value = []
    targetSemesterId.value = ''
    return
  }
  try {
    const res = await semesterApi.getByAcademicYear(targetAcademicYearId.value)
    targetSemesters.value = res.data?.data ?? []
  } catch {
    targetSemesters.value = []
  }
  targetSemesterId.value = ''
}

async function loadSourceStudents() {
  if (!sourceClassId.value) {
    sourceStudents.value = []
    selectedIds.value = []
    return
  }
  loadingStudents.value = true
  try {
    // class_id sudah menunjuk kelas sumber; jangan filter academic_year_id di siswa
    // agar data yang tahun ajarannya tidak sinkron tetap muncul.
    const res = await classApi.getStudents(sourceClassId.value, {
      status: 'Aktif',
      per_page: 100
    })
    const data = (res.data?.data ?? []).filter((s) => s.status === 'Aktif')
    sourceStudents.value = data
    selectedIds.value = data.map((s) => s.id)
  } catch {
    // Fallback ke endpoint student jika class students gagal
    try {
      const res = await studentApi.getAll({
        class_id: sourceClassId.value,
        status: 'Aktif',
        per_page: 100,
        ...institutionParams()
      })
      const data = res.data?.data ?? []
      sourceStudents.value = data
      selectedIds.value = data.map((s) => s.id)
    } catch {
      sourceStudents.value = []
      selectedIds.value = []
    }
  } finally {
    loadingStudents.value = false
  }
}

async function cloneClassesToTarget() {
  if (!sourceAcademicYearId.value || !targetAcademicYearId.value) return
  cloning.value = true
  resultMessage.value = ''
  try {
    const payload = {
      source_academic_year_id: Number(sourceAcademicYearId.value),
      target_academic_year_id: Number(targetAcademicYearId.value),
      ...institutionParams()
    }
    if (targetSemesterId.value) {
      payload.target_semester_id = Number(targetSemesterId.value)
    }
    const res = await classApi.cloneToYear(payload)
    resultSuccess.value = (res.data?.created ?? 0) > 0
    resultMessage.value = res.data?.message || 'Kelas berhasil disalin.'
    await loadTargetClasses()
  } catch (err) {
    resultSuccess.value = false
    resultMessage.value = err.response?.data?.message || 'Gagal menyalin kelas.'
  } finally {
    cloning.value = false
  }
}

function toggleSelectAll() {
  if (selectedIds.value.length === sourceStudents.value.length) {
    selectedIds.value = []
  } else {
    selectedIds.value = sourceStudents.value.map((s) => s.id)
  }
}

function confirmPromote() {
  if (selectedIds.value.length === 0 || !targetClassId.value || !targetAcademicYearId.value) return
  showConfirm.value = true
}

async function doPromote() {
  promoting.value = true
  resultMessage.value = ''
  failedList.value = []
  try {
    const payload = {
      source_class_id: Number(sourceClassId.value),
      source_academic_year_id: Number(sourceAcademicYearId.value),
      target_class_id: Number(targetClassId.value),
      target_academic_year_id: Number(targetAcademicYearId.value),
      student_ids: selectedIds.value,
      ...institutionParams()
    }
    if (targetSemesterId.value) {
      payload.target_semester_id = Number(targetSemesterId.value)
    }

    const res = await studentApi.promote(payload)
    const data = res.data
    showConfirm.value = false
    resultSuccess.value = (data.success ?? 0) > 0
    resultMessage.value = data.message || (data.success + ' siswa berhasil naik kelas.')
    failedList.value = data.failed || []
    if (data.success > 0) {
      await loadSourceStudents()
    }
  } catch (err) {
    showConfirm.value = false
    resultSuccess.value = false
    const errors = err.response?.data?.errors
    if (errors) {
      const first = Object.values(errors)[0]
      resultMessage.value = Array.isArray(first) ? first[0] : String(first)
    } else {
      resultMessage.value = err.response?.data?.message || 'Gagal memproses naik kelas.'
    }
    failedList.value = []
  } finally {
    promoting.value = false
  }
}

onMounted(() => {
  referenceStore.getAcademicYears()
})

watch(sourceAcademicYearId, () => {
  loadSourceClasses()
})

watch(sourceClassId, () => {
  loadSourceStudents()
})

watch(targetAcademicYearId, () => {
  loadTargetClasses()
  loadTargetSemesters()
})
</script>

<style scoped>
.naik-kelas-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
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

.clone-box {
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #fffbeb;
  border: 1px solid #fcd34d;
  color: #92400e;
}

.clone-box p {
  margin: 0 0 12px;
  font-size: 14px;
  line-height: 1.5;
}

.action-row {
  margin-top: 20px;
}

.btn-primary {
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  border: none;
  background: #059669;
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
