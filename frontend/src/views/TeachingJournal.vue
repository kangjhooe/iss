<template>
    <div class="teaching-journal-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <select v-model="filters.semester_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <select v-model="filters.class_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-if="!isTeacher" v-model="filters.employee_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Guru</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
          <select v-model="filters.subject_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Mapel</option>
            <option v-for="sub in filterSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadJournals" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadJournals" />
        </div>
        <div class="toolbar-actions">
          <button @click="exportToCsv" :disabled="exporting" class="btn-secondary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
          </button>
          <button @click="openAddModal" class="btn-primary btn-compact">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Jurnal</span>
          </button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="8" :cell-widths="['90px', '100px', '120px', '120px', '60px', '1fr', '1fr', '90px']" />
      </div>

      <div v-else-if="journals.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 6.25278V19.2528M12 6.25278C10.8321 5.47686 9.24649 5 7.5 5C5.75351 5 4.16789 5.47686 3 6.25278V19.2528C4.16789 18.4769 5.75351 18 7.5 18C9.24649 18 10.8321 18.4769 12 19.2528M12 6.25278C13.1679 5.47686 14.7535 5 16.5 5C18.2465 5 19.8321 5.47686 21 6.25278V19.2528C19.8321 18.4769 18.2465 18 16.5 18C14.7535 18 13.1679 18.4769 12 19.2528" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada jurnal mengajar</h3>
        <p class="empty-desc">Tambahkan jurnal untuk mencatat materi dan kehadiran setiap pertemuan.</p>
        <button @click="openAddModal" class="btn-primary btn-empty-cta">Tambah Jurnal</button>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Kelas</th>
              <th>Mapel</th>
              <th>Guru</th>
              <th>Jam ke</th>
              <th>Materi</th>
              <th>Kehadiran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="j in journals" :key="j.id">
              <td>{{ formatDate(j.journal_date) }}</td>
              <td>{{ displayValue(j.school_class?.name) }}</td>
              <td>{{ displayValue(j.subject?.name) }}</td>
              <td>{{ displayValue(j.employee?.name) }}</td>
              <td>{{ j.period ?? 'Belum ada data' }}</td>
              <td class="summary-cell">{{ truncate(j.material_taught, 40) || 'Belum ada data' }}</td>
              <td class="summary-cell">{{ truncate(j.attendance_notes, 30) || 'Belum ada data' }}</td>
              <td>
                <div class="action-buttons">
                  <TableAction kind="edit" @click="openEditModal(j)" />
                  <TableAction kind="delete" @click="confirmDelete(j)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar
          embedded
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="data"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />
      </div>

      <!-- Modal: Tambah/Edit Jurnal -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingJournal ? 'Edit Jurnal Mengajar' : 'Tambah Jurnal Mengajar' }}</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitForm" class="modal-body">
            <div class="form-group">
              <label>Semester *</label>
              <select v-model="form.semester_id" required class="form-select">
                <option value="">Pilih semester</option>
                <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Kelas *</label>
              <select v-model="form.class_id" required class="form-select" @change="onFormClassChange">
                <option value="">Pilih kelas</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Mata Pelajaran *</label>
              <select v-model="form.subject_id" required class="form-select" @change="onFormSubjectChange">
                <option value="">Pilih mapel</option>
                <option v-for="sub in formSubjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
              </select>
            </div>
            <div v-if="formScheduleSlots.length" class="form-group">
              <label>Slot jadwal (opsional)</label>
              <select v-model="form.lesson_schedule_id" class="form-select" @change="onFormScheduleChange">
                <option value="">Otomatis dari kelas + mapel</option>
                <option v-for="slot in formScheduleSlots" :key="slot.id" :value="slot.id">
                  {{ slot.day_name || ('Hari ' + slot.day_of_week) }} · jam ke-{{ slot.period }}
                </option>
              </select>
            </div>
            <div v-if="!isTeacher" class="form-group">
              <label>Guru *</label>
              <select v-model="form.employee_id" required class="form-select">
                <option value="">Pilih guru</option>
                <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Tanggal *</label>
              <input v-model="form.journal_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Jam ke</label>
              <input v-model.number="form.period" type="number" min="1" max="20" placeholder="1" />
            </div>
            <div class="form-group">
              <label>Materi yang diajarkan</label>
              <textarea v-model="form.material_taught" rows="3" placeholder="Materi yang diajarkan (opsional)"></textarea>
            </div>
            <div class="form-group">
              <label>Catatan kehadiran</label>
              <textarea v-model="form.attendance_notes" rows="2" placeholder="Catatan kehadiran siswa (opsional)"></textarea>
            </div>
            <div class="form-group">
              <label>Catatan lain</label>
              <textarea v-model="form.notes" rows="2" placeholder="Catatan lain (opsional)"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingJournal ? 'Simpan' : 'Tambah') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Jurnal Mengajar"
        :message="deleteMessage"
        confirmText="Hapus"
        @confirm="doDelete"
        @cancel="deleteTarget = null"
      />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { teachingJournalApi } from '@/api/teachingJournal'
import { employeeApi } from '@/api/teacher'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { semesterApi } from '@/api/semester'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useActiveAcademicPeriod } from '@/composables/useActiveAcademicPeriod'

const toast = useToast()
const authStore = useAuthStore()
const route = useRoute()
const { ensureLoaded, resolveDefaultSemesterId } = useActiveAcademicPeriod()

const isTeacher = computed(() => {
  const role = authStore.user?.role
  return role === 'teacher' || role === 'staff'
})

const loading = ref(true)
const journals = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const semesters = ref([])
const classes = ref([])
const subjects = ref([])
const employees = ref([])
const currentTeacherId = ref(null)
const teachingPairs = ref([])
const teachingSchedules = ref([])

const filters = ref({
  semester_id: '',
  class_id: '',
  employee_id: '',
  subject_id: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const editingJournal = ref(null)
const form = ref({
  semester_id: '',
  class_id: '',
  subject_id: '',
  employee_id: '',
  lesson_schedule_id: '',
  journal_date: new Date().toISOString().slice(0, 10),
  period: 1,
  material_taught: '',
  attendance_notes: '',
  notes: '',
})
const formSubmitting = ref(false)
const formError = ref('')
const deleteTarget = ref(null)
const exporting = ref(false)

const formSubjects = computed(() => {
  if (!isTeacher.value || !form.value.class_id) return subjects.value
  const allowed = new Set(
    teachingPairs.value
      .filter((p) => String(p.class_id) === String(form.value.class_id))
      .map((p) => String(p.subject_id))
  )
  return subjects.value.filter((s) => allowed.has(String(s.id)))
})

const filterSubjects = computed(() => {
  if (!isTeacher.value || !filters.value.class_id) return subjects.value
  const allowed = new Set(
    teachingPairs.value
      .filter((p) => String(p.class_id) === String(filters.value.class_id))
      .map((p) => String(p.subject_id))
  )
  return subjects.value.filter((s) => allowed.has(String(s.id)))
})

const formScheduleSlots = computed(() => {
  if (!form.value.class_id || !form.value.subject_id) return []
  return teachingSchedules.value.filter(
    (s) =>
      String(s.class_id || s.school_class?.id) === String(form.value.class_id) &&
      String(s.subject_id || s.subject?.id) === String(form.value.subject_id)
  )
})

const deleteMessage = computed(() => {
  if (!deleteTarget.value) return ''
  const j = deleteTarget.value
  return `Yakin menghapus jurnal ${formatDate(j.journal_date)} - ${j.school_class?.name || ''} ${j.subject?.name || ''}?`
})

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function truncate(str, len) {
  if (!str) return '-'
  return str.length <= len ? str : str.slice(0, len) + '…'
}

async function loadJournals() {
  loading.value = true
  formError.value = ''
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
      ...filters.value,
    }
    if (!params.semester_id) delete params.semester_id
    if (!params.class_id) delete params.class_id
    if (!params.employee_id) delete params.employee_id
    if (!params.subject_id) delete params.subject_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to

    const res = await teachingJournalApi.getAll(params)
    journals.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat jurnal mengajar', e.formattedMessage || 'Data jurnal tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
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
  if (isTeacher.value) return
  try {
    const res = await classApi.getAll({ per_page: 200 })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadSubjects() {
  if (isTeacher.value) return
  try {
    const res = await subjectApi.getAll({ per_page: 200 })
    subjects.value = res.data.data || []
  } catch {
    subjects.value = []
  }
}

async function loadTeachingLoad(semesterId) {
  if (!isTeacher.value || !semesterId) {
    teachingPairs.value = []
    teachingSchedules.value = []
    return
  }
  try {
    const res = await employeeApi.getTeachingLoad({ semester_id: semesterId })
    const data = res.data?.data ?? res.data ?? {}
    teachingPairs.value = Array.isArray(data.pairs) ? data.pairs : []
    teachingSchedules.value = Array.isArray(data.schedules)
      ? data.schedules.map((s) => s?.data ?? s)
      : []
    classes.value = Array.isArray(data.classes) ? data.classes : []
    subjects.value = Array.isArray(data.subjects) ? data.subjects : []
    if (data.employee_id) currentTeacherId.value = data.employee_id
  } catch {
    teachingPairs.value = []
    teachingSchedules.value = []
    classes.value = []
    subjects.value = []
  }
}

function onFormClassChange() {
  form.value.subject_id = ''
  form.value.lesson_schedule_id = ''
}

function onFormSubjectChange() {
  form.value.lesson_schedule_id = ''
  const slots = formScheduleSlots.value
  if (slots.length === 1) {
    form.value.lesson_schedule_id = slots[0].id
    form.value.period = slots[0].period || form.value.period
  }
}

function onFormScheduleChange() {
  const slot = formScheduleSlots.value.find((s) => String(s.id) === String(form.value.lesson_schedule_id))
  if (slot?.period) form.value.period = slot.period
}

async function loadEmployees() {
  if (isTeacher.value) return
  try {
    const res = await employeeApi.getAll({ per_page: 500, type: 'Guru' })
    employees.value = res.data.data || []
  } catch {
    employees.value = []
  }
}

async function loadCurrentTeacher() {
  if (!isTeacher.value) return
  try {
    const res = await employeeApi.getDashboard()
    const teacher = res.data?.data?.teacher
    if (teacher?.id) currentTeacherId.value = teacher.id
  } catch {
    currentTeacherId.value = null
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadJournals()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadJournals()
}

async function exportToCsv() {
  exporting.value = true
  try {
    const params = { ...filters.value }
    if (!params.semester_id) delete params.semester_id
    if (!params.class_id) delete params.class_id
    if (!params.employee_id) delete params.employee_id
    if (!params.subject_id) delete params.subject_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    const res = await teachingJournalApi.export(params)
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `jurnal-mengajar-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor jurnal', e.formattedMessage || 'Data tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = false
  }
}

function openAddModal() {
  editingJournal.value = null
  form.value = {
    semester_id: filters.value.semester_id || '',
    class_id: filters.value.class_id || '',
    subject_id: filters.value.subject_id || '',
    employee_id: isTeacher.value ? (currentTeacherId.value || '') : '',
    lesson_schedule_id: '',
    journal_date: new Date().toISOString().slice(0, 10),
    period: 1,
    material_taught: '',
    attendance_notes: '',
    notes: '',
  }
  formError.value = ''
  showFormModal.value = true
  if (isTeacher.value && form.value.semester_id) loadTeachingLoad(form.value.semester_id)
}

function openEditModal(j) {
  editingJournal.value = j
  form.value = {
    semester_id: j.semester_id,
    class_id: j.class_id,
    subject_id: j.subject_id,
    employee_id: j.employee_id,
    lesson_schedule_id: j.lesson_schedule_id || '',
    journal_date: j.journal_date,
    period: j.period ?? 1,
    material_taught: j.material_taught || '',
    attendance_notes: j.attendance_notes || '',
    notes: j.notes || '',
  }
  formError.value = ''
  showFormModal.value = true
  if (isTeacher.value && form.value.semester_id) loadTeachingLoad(form.value.semester_id)
}

async function submitForm() {
  formSubmitting.value = true
  formError.value = ''
  try {
    const payload = {
      semester_id: form.value.semester_id,
      class_id: form.value.class_id,
      subject_id: form.value.subject_id,
      journal_date: form.value.journal_date,
      period: form.value.period || 1,
      material_taught: form.value.material_taught || null,
      attendance_notes: form.value.attendance_notes || null,
      notes: form.value.notes || null,
    }
    if (form.value.lesson_schedule_id) {
      payload.lesson_schedule_id = form.value.lesson_schedule_id
    }
    if (!isTeacher.value) {
      payload.employee_id = form.value.employee_id || null
    }
    if (editingJournal.value) {
      await teachingJournalApi.update(editingJournal.value.id, payload)
      toast.success('Jurnal mengajar berhasil diperbarui')
    } else {
      await teachingJournalApi.create(payload)
      toast.success('Jurnal mengajar berhasil dicatat')
    }
    showFormModal.value = false
    loadJournals()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    formSubmitting.value = false
  }
}

function confirmDelete(j) {
  deleteTarget.value = j
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await teachingJournalApi.delete(deleteTarget.value.id)
    toast.success('Jurnal mengajar dihapus')
    deleteTarget.value = null
    loadJournals()
  } catch (e) {
    toast.error('Gagal menghapus jurnal mengajar', e.formattedMessage || 'Jurnal tidak dapat dihapus. Coba lagi.')
  }
}

watch(
  () => filters.value.semester_id,
  async (semesterId) => {
    if (isTeacher.value) {
      await loadTeachingLoad(semesterId)
      loadJournals()
    }
  }
)

watch(
  () => form.value.semester_id,
  async (semesterId) => {
    if (isTeacher.value && showFormModal.value) {
      await loadTeachingLoad(semesterId)
    }
  }
)

onMounted(async () => {
  await loadCurrentTeacher()
  await Promise.all([loadSemesters(), ensureLoaded()])
  const q = route.query
  if (q.semester_id) {
    filters.value.semester_id = String(q.semester_id)
  } else if (!filters.value.semester_id) {
    filters.value.semester_id = resolveDefaultSemesterId('', semesters.value)
  }
  if (isTeacher.value) {
    await loadTeachingLoad(filters.value.semester_id)
  } else {
    await Promise.all([loadClasses(), loadSubjects(), loadEmployees()])
  }
  if (q.class_id) filters.value.class_id = String(q.class_id)
  if (q.subject_id) filters.value.subject_id = String(q.subject_id)
  await loadJournals()

  if (q.lesson_schedule_id) {
    openAddModal()
    form.value.lesson_schedule_id = String(q.lesson_schedule_id)
    if (q.period) form.value.period = Number(q.period) || form.value.period
    if (q.class_id) form.value.class_id = String(q.class_id)
    if (q.subject_id) form.value.subject_id = String(q.subject_id)
    if (q.semester_id) form.value.semester_id = String(q.semester_id)
  }
})
</script>

<style scoped>
.teaching-journal-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}

.toolbar .filters {
  margin-bottom: 0;
  flex: 1;
  min-width: 200px;
}

.toolbar-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
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
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
.header-actions {
  margin-left: auto;
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
  min-width: 140px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
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
.empty-icon {
  color: #94a3b8;
  margin-bottom: 1rem;
}
.empty-title {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}
.empty-desc {
  color: #64748b;
  margin: 0 0 1.5rem 0;
}
.btn-empty-cta {
  margin-top: 0.5rem;
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
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #065f46;
}
.summary-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.action-buttons {
  display: flex;
  gap: 0.5rem;
}
.btn-action {
  padding: 0.35rem 0.5rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-action.btn-edit:hover {
  background: #ecfdf5;
  border-color: #059669;
}
.btn-action.btn-delete:hover {
  background: #fee2e2;
  border-color: #ef4444;
}
.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.pagination-info {
  font-size: 0.9rem;
  color: #64748b;
}
.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.btn-page {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content.form-modal {
  background: #fff;
  border-radius: 12px;
  max-width: 480px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 {
  margin: 0;
  font-size: 1.15rem;
}
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  padding: 0 0.25rem;
}
.modal-body {
  padding: 1.25rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}
.form-group textarea {
  resize: vertical;
  min-height: 60px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  margin-top: 0.5rem;
}
.error-message {
  color: #dc2626;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}
.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
}
.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
}
.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    width: 100%;
    margin-left: 0;
  }

  .toolbar-actions {
    width: 100%;
  }

  .toolbar-actions .btn-primary,
  .toolbar-actions .btn-secondary {
    flex: 1;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .toolbar .filters {
    min-width: 0;
    width: 100%;
  }
  .filters-inline {
    flex-direction: column;
  }
  .filter-select {
    min-width: 0;
    width: 100%;
  }
  .toolbar-actions {
    width: 100%;
  }
  .toolbar-actions .btn-primary,
  .toolbar-actions .btn-secondary {
    flex: 1;
    justify-content: center;
  }
}
</style>
