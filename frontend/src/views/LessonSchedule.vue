<template>
  <Layout>
    <div class="schedule-page">
      <div class="toolbar">
        <div class="tabs">
          <button :class="['tab', { active: activeTab === 'byClass' }]" @click="activeTab = 'byClass'; loadByClassIfNeeded()">Jadwal per Kelas</button>
          <button :class="['tab', { active: activeTab === 'list' }]" @click="activeTab = 'list'; loadSchedules()">Daftar Slot</button>
          <button :class="['tab', { active: activeTab === 'copy' }]" @click="activeTab = 'copy'">Copy Jadwal</button>
        </div>
        <div class="toolbar-actions">
          <router-link to="/subject" class="btn-secondary btn-compact">Mata Pelajaran</router-link>
          <button v-if="activeTab === 'list'" @click="openAddSlotModal" class="btn-primary btn-compact">Tambah Slot</button>
        </div>
      </div>

      <!-- Tab: Jadwal per Kelas -->
      <template v-if="activeTab === 'byClass'">
        <div class="filters filters-inline">
          <label>Semester</label>
          <select v-model="filterSemesterId" @change="loadByClassIfNeeded" class="filter-select">
            <option value="">Pilih Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }} ({{ s.academic_year?.name || '-' }})</option>
          </select>
          <label>Kelas</label>
          <select v-model="filterClassId" @change="loadByClassIfNeeded" class="filter-select">
            <option value="">Pilih Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div v-if="!filterSemesterId || !filterClassId" class="empty-state">
          <p>Pilih semester dan kelas untuk menampilkan jadwal.</p>
        </div>
        <div v-else-if="loadingMatrix" class="loading-state"><p>Memuat jadwal...</p></div>
        <div v-else class="schedule-matrix-wrap">
          <table class="schedule-matrix">
            <thead>
              <tr>
                <th>Jam</th>
                <th v-for="day in dayNames" :key="day.key">{{ day.label }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in maxPeriods" :key="period">
                <td class="period-cell">Ke-{{ period }}</td>
                <td v-for="day in dayKeys" :key="day" class="slot-cell">
                  <template v-if="getSlot(day, period)">
                    <div class="slot-subject">{{ getSlot(day, period).subject?.name }}</div>
                    <div class="slot-teacher">{{ getSlot(day, period).employee?.name }}</div>
                    <div class="slot-room" v-if="getSlot(day, period).room">{{ getSlot(day, period).room?.name }}</div>
                    <button type="button" class="slot-edit-btn" @click="editSlotFromMatrix(getSlot(day, period))" title="Edit">✎</button>
                  </template>
                  <button v-else type="button" class="slot-add-btn" @click="openAddSlotFor(day, period)" title="Tambah">+</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Tab: Daftar Slot -->
      <template v-if="activeTab === 'list'">
        <div class="filters filters-inline">
          <select v-model="listFilters.semester_id" @change="loadSchedules" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <select v-model="listFilters.class_id" @change="loadSchedules" class="filter-select">
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="listFilters.day_of_week" @change="loadSchedules" class="filter-select">
            <option value="">Semua Hari</option>
            <option v-for="(label, key) in dayNamesMap" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div v-if="loadingList" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kelas</th>
                <th>Hari</th>
                <th>Jam ke</th>
                <th>Mata Pelajaran</th>
                <th>Guru</th>
                <th>Ruangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scheduleList" :key="row.id">
                <td>{{ displayValue(row.school_class?.name) }}</td>
                <td>{{ row.day_name || 'Belum ada data' }}</td>
                <td>{{ row.period ?? 'Belum ada data' }}</td>
                <td>{{ displayValue(row.subject?.name) }}</td>
                <td>{{ displayValue(row.employee?.name) }}</td>
                <td>{{ displayValue(row.room?.name) }}</td>
                <td>
                  <button type="button" class="btn-action btn-edit" @click="editSlot(row)">Edit</button>
                  <button type="button" class="btn-action btn-delete" @click="deleteSlot(row.id)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="scheduleList.length === 0" class="empty-state"><p>Belum ada jadwal.</p></div>
        </div>
      </template>

      <!-- Tab: Copy Jadwal -->
      <template v-if="activeTab === 'copy'">
        <div class="copy-form card">
          <p>Salin jadwal dari satu semester ke semester lain. Pilih kelas opsional untuk menyalin hanya satu kelas.</p>
          <div class="form-group">
            <label>Semester Sumber</label>
            <select v-model="copyForm.source_semester_id" class="form-input">
              <option value="">Pilih</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Semester Tujuan</label>
            <select v-model="copyForm.target_semester_id" class="form-input">
              <option value="">Pilih</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Kelas (kosongkan = semua kelas)</label>
            <select v-model="copyForm.class_id" class="form-input">
              <option value="">Semua Kelas</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <button type="button" @click="doCopy" :disabled="copying || !copyForm.source_semester_id || !copyForm.target_semester_id" class="btn-primary">
            {{ copying ? 'Menyalin...' : 'Salin Jadwal' }}
          </button>
          <p v-if="copyResult" class="copy-result">{{ copyResult }}</p>
        </div>
      </template>

      <!-- Add/Edit Slot Modal -->
      <div v-if="showSlotModal" class="modal-overlay" @click="closeSlotModal">
        <div class="modal-content modal-wide" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSlot ? 'Edit Slot Jadwal' : 'Tambah Slot Jadwal' }}</h3>
            <button @click="closeSlotModal" class="modal-close">×</button>
          </div>
          <form @submit.prevent="saveSlot" class="modal-body">
            <div v-if="slotError" class="error-message">{{ slotError }}</div>
            <div class="form-row">
              <div class="form-group">
                <label>Semester <span class="required">*</span></label>
                <select v-model="slotForm.semester_id" required class="form-input">
                  <option value="">Pilih</option>
                  <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Kelas <span class="required">*</span></label>
                <select v-model="slotForm.class_id" required class="form-input">
                  <option value="">Pilih</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Hari <span class="required">*</span></label>
                <select v-model="slotForm.day_of_week" required class="form-input">
                  <option v-for="(label, key) in dayNamesMap" :key="key" :value="Number(key)">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jam ke <span class="required">*</span></label>
                <select v-model.number="slotForm.period" required class="form-input">
                  <option v-for="p in 10" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Mata Pelajaran <span class="required">*</span></label>
              <select v-model="slotForm.subject_id" required class="form-input">
                <option value="">Pilih</option>
                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.code }} - {{ sub.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Guru <span class="required">*</span></label>
              <select v-model="slotForm.employee_id" required class="form-input">
                <option value="">Pilih</option>
                <option v-for="emp in teachers" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ruangan (opsional)</label>
              <select v-model="slotForm.room_id" class="form-input">
                <option value="">—</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeSlotModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="savingSlot" class="btn-primary">{{ savingSlot ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { subjectApi } from '@/api/subject'
import { classApi } from '@/api/class'
import { institutionApi } from '@/api/institution'
import { semesterApi } from '@/api/semester'
import { teacherApi } from '@/api/teacher'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const activeTab = ref('byClass')
const semesters = ref([])
const classes = ref([])
const subjects = ref([])
const teachers = ref([])
const rooms = ref([])
const filterSemesterId = ref('')
const filterClassId = ref('')
const scheduleMatrix = ref(null)
const scheduleList = ref([])
const loadingMatrix = ref(false)
const loadingList = ref(false)
const savingSlot = ref(false)
const copying = ref(false)
const copyResult = ref('')
const showSlotModal = ref(false)
const editingSlot = ref(null)
const slotError = ref('')

const dayNamesMap = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' }
const dayKeys = [1, 2, 3, 4, 5]
const dayNames = computed(() => dayKeys.map(k => ({ key: k, label: dayNamesMap[k] })))
const maxPeriods = 10

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

const listFilters = reactive({
  semester_id: '',
  class_id: '',
  day_of_week: '',
})

const copyForm = reactive({
  source_semester_id: '',
  target_semester_id: '',
  class_id: '',
})

const slotForm = reactive({
  semester_id: '',
  class_id: '',
  day_of_week: 1,
  period: 1,
  subject_id: '',
  employee_id: '',
  room_id: '',
})

async function loadInitial() {
  try {
    const [instRes, semRes, classRes, subRes, empRes, roomRes] = await Promise.all([
      institutionApi.getMy().catch(() => ({ data: null })),
      semesterApi.getAll({ per_page: 100 }),
      classApi.getAll({ per_page: 200 }),
      subjectApi.getAll({ per_page: 'all', active_only: true }).catch(() => ({ data: [] })),
      teacherApi.getAll({ status: 'Aktif', per_page: 200 }),
      facilityApi.getRooms({ per_page: 200 }),
    ])
    const institution = instRes.data?.data ?? instRes.data ?? null
    semesters.value = semRes.data?.data ?? semRes.data ?? []
    classes.value = classRes.data?.data ?? classRes.data ?? []
    subjects.value = Array.isArray(subRes.data) ? subRes.data : (subRes.data?.data ?? [])
    teachers.value = empRes.data?.data ?? empRes.data ?? []
    rooms.value = roomRes.data?.data ?? roomRes.data ?? []
    if (institution?.active_semester_id) {
      listFilters.semester_id = String(institution.active_semester_id)
      filterSemesterId.value = String(institution.active_semester_id)
    }
  } catch (e) {
    console.error(e)
  }
}

async function loadByClassIfNeeded() {
  if (!filterSemesterId.value || !filterClassId.value) return
  loadingMatrix.value = true
  try {
    const res = await lessonScheduleApi.getByClass(filterClassId.value, { semester_id: filterSemesterId.value })
    scheduleMatrix.value = res.data
  } catch (e) {
    scheduleMatrix.value = null
  } finally {
    loadingMatrix.value = false
  }
}

function getSlot(dayOfWeek, period) {
  if (!scheduleMatrix.value?.matrix) return null
  const dayRow = scheduleMatrix.value.matrix.find(d => d.day_of_week === dayOfWeek)
  if (!dayRow?.slots) return null
  const slot = dayRow.slots[period]
  return slot?.data ?? slot ?? null
}

function openAddSlotFor(dayOfWeek, period) {
  editingSlot.value = null
  slotForm.semester_id = filterSemesterId.value || ''
  slotForm.class_id = filterClassId.value || ''
  slotForm.day_of_week = dayOfWeek
  slotForm.period = period
  slotForm.subject_id = ''
  slotForm.employee_id = ''
  slotForm.room_id = ''
  slotError.value = ''
  showSlotModal.value = true
}

function editSlotFromMatrix(slotData) {
  if (!slotData?.id) return
  editingSlot.value = slotData
  slotForm.semester_id = slotData.semester_id || filterSemesterId.value || ''
  slotForm.class_id = slotData.class_id || filterClassId.value || ''
  slotForm.day_of_week = slotData.day_of_week
  slotForm.period = slotData.period
  slotForm.subject_id = slotData.subject_id || ''
  slotForm.employee_id = slotData.employee_id || ''
  slotForm.room_id = slotData.room_id || ''
  slotError.value = ''
  showSlotModal.value = true
}

function editSlot(row) {
  editingSlot.value = row
  slotForm.semester_id = row.semester_id || ''
  slotForm.class_id = row.class_id || ''
  slotForm.day_of_week = row.day_of_week
  slotForm.period = row.period
  slotForm.subject_id = row.subject_id || ''
  slotForm.employee_id = row.employee_id || ''
  slotForm.room_id = row.room_id || ''
  slotError.value = ''
  showSlotModal.value = true
}

function openAddSlotModal() {
  editingSlot.value = null
  slotForm.semester_id = listFilters.semester_id || ''
  slotForm.class_id = listFilters.class_id || ''
  slotForm.day_of_week = 1
  slotForm.period = 1
  slotForm.subject_id = ''
  slotForm.employee_id = ''
  slotForm.room_id = ''
  slotError.value = ''
  showSlotModal.value = true
}

function closeSlotModal() {
  showSlotModal.value = false
  editingSlot.value = null
}

async function saveSlot() {
  savingSlot.value = true
  slotError.value = ''
  try {
    const payload = {
      semester_id: Number(slotForm.semester_id),
      class_id: Number(slotForm.class_id),
      day_of_week: Number(slotForm.day_of_week),
      period: Number(slotForm.period),
      subject_id: Number(slotForm.subject_id),
      employee_id: Number(slotForm.employee_id),
      room_id: slotForm.room_id ? Number(slotForm.room_id) : null,
    }
    if (editingSlot.value?.id) {
      await lessonScheduleApi.update(editingSlot.value.id, payload)
    } else {
      await lessonScheduleApi.create(payload)
    }
    closeSlotModal()
    loadByClassIfNeeded()
    loadSchedules()
  } catch (e) {
    slotError.value = e.formattedMessage || e.response?.data?.message || e.message || 'Gagal menyimpan.'
  } finally {
    savingSlot.value = false
  }
}

async function deleteSlot(id) {
  if (!confirm('Hapus slot ini?')) return
  try {
    await lessonScheduleApi.delete(id)
    loadByClassIfNeeded()
    loadSchedules()
  } catch (e) {
    const msg = e.formattedMessage || e.message || e.response?.data?.message || 'Slot jadwal tidak dapat dihapus. Coba lagi.'
    toast.error('Gagal menghapus slot jadwal', msg)
  }
}

async function loadSchedules() {
  loadingList.value = true
  try {
    const params = {}
    if (listFilters.semester_id) params.semester_id = listFilters.semester_id
    if (listFilters.class_id) params.class_id = listFilters.class_id
    if (listFilters.day_of_week) params.day_of_week = listFilters.day_of_week
    const res = await lessonScheduleApi.getAll(params)
    scheduleList.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    scheduleList.value = []
  } finally {
    loadingList.value = false
  }
}

async function doCopy() {
  copying.value = true
  copyResult.value = ''
  try {
    const res = await lessonScheduleApi.copySemester({
      source_semester_id: copyForm.source_semester_id,
      target_semester_id: copyForm.target_semester_id,
      class_id: copyForm.class_id || undefined,
    })
    copyResult.value = res.data?.message || `Berhasil menyalin ${res.data?.copied_count ?? 0} slot.`
    loadByClassIfNeeded()
    loadSchedules()
  } catch (e) {
    copyResult.value = e.formattedMessage || e.response?.data?.message || e.message || 'Gagal menyalin.'
  } finally {
    copying.value = false
  }
}

onMounted(() => {
  loadInitial()
  loadSchedules()
})
</script>

<style scoped>
.schedule-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
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

.toolbar .tabs {
  margin-bottom: 0;
}

.toolbar-actions {
  display: flex;
  gap: 0.5rem;
}

.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.header-actions { display: flex; gap: 0.5rem; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.tab { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; }
.tab.active { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; border-color: #059669; }
.filters-inline { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; min-width: 160px; transition: border-color 0.2s, box-shadow 0.2s; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.loading-state, .empty-state { padding: 1.5rem; text-align: center; color: #64748b; }
.schedule-matrix-wrap { overflow-x: auto; }
.schedule-matrix { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.schedule-matrix th, .schedule-matrix td { border: 1px solid #e2e8f0; padding: 0.5rem; vertical-align: top; }
.schedule-matrix th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; }
.period-cell { min-width: 60px; }
.slot-cell { min-width: 120px; position: relative; }
.slot-subject { font-weight: 500; }
.slot-teacher { font-size: 0.75rem; color: #718096; }
.slot-room { font-size: 0.75rem; color: #a0aec0; }
.slot-add-btn, .slot-edit-btn { margin-top: 4px; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; cursor: pointer; border: 1px solid #e2e8f0; background: #f7fafc; }
.slot-add-btn:hover, .slot-edit-btn:hover { background: #ecfdf5; border-color: #059669; color: #059669; }
.copy-form { max-width: 400px; padding: 1.5rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
.form-group { margin-bottom: 1rem; }
.form-row { display: flex; gap: 1rem; }
.form-row .form-group { flex: 1; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.form-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.copy-result { margin-top: 1rem; color: #047857; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead tr { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 600; color: #065f46; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 8px; max-width: 480px; width: 90%; max-height: 90vh; overflow-y: auto; }
.modal-wide { max-width: 520px; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; }
.modal-body { padding: 1.25rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.error-message { color: #c53030; margin-bottom: 0.75rem; font-size: 0.875rem; }
.btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-primary:hover:not(:disabled) { filter: brightness(1.05); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
.btn-secondary { background: #e2e8f0; color: #2d3748; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-action { padding: 0.35rem 0.6rem; font-size: 0.875rem; border-radius: 4px; cursor: pointer; border: none; }
.btn-edit { background: rgba(5, 150, 105, 0.12); color: #059669; }
.btn-delete { background: #fed7d7; color: #c53030; }
</style>
