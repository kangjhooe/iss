<template>
  <Layout>
    <div class="calendar-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Kalender Akademik</h1>
              <p class="page-subtitle">Kelola event, libur, ujian, dan kegiatan sekolah</p>
            </div>
          </div>
          <div class="header-actions">
            <div class="view-toggle" role="group" aria-label="Mode tampilan">
              <button
                type="button"
                class="toggle-btn"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                Daftar
              </button>
              <button
                type="button"
                class="toggle-btn"
                :class="{ active: viewMode === 'month' }"
                @click="switchToMonth"
              >
                Bulan
              </button>
            </div>
            <button type="button" class="btn-header-primary" @click="openCreate">+ Tambah Event</button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div class="content-card filters-bar">
          <div class="filter-field grow">
            <label class="filter-label">Cari</label>
            <input
              v-model="filters.search"
              class="filter-input"
              placeholder="Judul event..."
              @input="onFilterChange"
            />
          </div>
          <div class="filter-field">
            <label class="filter-label">Tahun ajaran</label>
            <select v-model="filters.academic_year_id" class="filter-select" @change="onYearFilterChange">
              <option value="">Semua</option>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">
                {{ year.code }} — {{ year.name }}
              </option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Semester</label>
            <select v-model="filters.semester_id" class="filter-select" @change="onFilterChange">
              <option value="">Semua</option>
              <option v-for="s in filterSemesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Jenis</label>
            <select v-model="filters.event_type" class="filter-select" @change="onFilterChange">
              <option value="">Semua</option>
              <option v-for="t in eventTypes" :key="t" :value="t">{{ t }}</option>
            </select>
          </div>
          <div class="filter-field">
            <label class="filter-label">Status</label>
            <select v-model="filters.status" class="filter-select" @change="onFilterChange">
              <option value="">Semua</option>
              <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>

        <div v-if="viewMode === 'month'" class="content-card">
          <div class="month-nav">
            <button type="button" class="btn-secondary" @click="shiftMonth(-1)">←</button>
            <h3>{{ monthLabel }}</h3>
            <button type="button" class="btn-secondary" @click="shiftMonth(1)">→</button>
            <button type="button" class="btn-ghost" @click="goThisMonth">Hari ini</button>
          </div>
          <div v-if="monthLoading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="5" :columns="7" />
          </div>
          <div v-else class="month-grid">
            <div v-for="d in weekDayLabels" :key="d" class="month-dow">{{ d }}</div>
            <div
              v-for="(cell, idx) in monthCells"
              :key="idx"
              class="month-cell"
              :class="{ outside: !cell.inMonth, today: cell.isToday }"
            >
              <div class="cell-day">{{ cell.day }}</div>
              <button
                v-for="ev in cell.events"
                :key="ev.id"
                type="button"
                class="cell-event"
                :style="{ borderLeftColor: ev.color || typeColor(ev.event_type) }"
                :title="ev.title"
                @click="editEvent(ev)"
              >
                {{ ev.title }}
              </button>
            </div>
          </div>
        </div>

        <div v-else class="content-card">
          <div v-if="loading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="6" :columns="7" />
          </div>
          <div v-else-if="events.length === 0" class="empty-state">
            <h3>Belum ada event</h3>
            <p>Tambah libur, ujian, atau kegiatan agar muncul di dashboard siswa.</p>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Jenis</th>
                  <th>Tanggal</th>
                  <th>Waktu</th>
                  <th>Status</th>
                  <th>Warna</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="event in events" :key="event.id">
                  <td>
                    <strong>{{ event.title }}</strong>
                    <div v-if="event.academic_year" class="meta-line">
                      {{ event.academic_year.code }}
                      <span v-if="event.semester"> · {{ event.semester.name }}</span>
                    </div>
                  </td>
                  <td>
                    <span
                      class="type-badge"
                      :style="{ background: typeColor(event.event_type) + '22', color: typeColor(event.event_type) }"
                    >
                      {{ event.event_type }}
                    </span>
                  </td>
                  <td>{{ formatDateRange(event.start_date, event.end_date) }}</td>
                  <td>
                    <span v-if="event.is_all_day">Seharian</span>
                    <span v-else>{{ formatTime(event.start_time) }} – {{ formatTime(event.end_time) }}</span>
                  </td>
                  <td><span :class="getStatusClass(event.status)">{{ event.status }}</span></td>
                  <td>
                    <span class="color-swatch" :style="{ background: event.color || typeColor(event.event_type) }" />
                  </td>
                  <td>
                    <TableAction kind="edit" @click="editEvent(event)" />
                    <TableAction kind="delete" @click="deleteEvent(event)" />
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="pagination && pagination.last_page > 1" class="pagination">
              <span>Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
              <div class="pagination-btns">
                <button
                  type="button"
                  class="btn-secondary"
                  :disabled="pagination.current_page === 1"
                  @click="loadEvents(pagination.current_page - 1)"
                >
                  Sebelumnya
                </button>
                <button
                  type="button"
                  class="btn-secondary"
                  :disabled="pagination.current_page === pagination.last_page"
                  @click="loadEvents(pagination.current_page + 1)"
                >
                  Selanjutnya
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-card">
          <h3>{{ editingEvent ? 'Edit Event' : 'Tambah Event' }}</h3>
          <div v-if="error" class="modal-error">{{ error }}</div>
          <form @submit.prevent="saveEvent">
            <div class="form-grid">
              <div class="form-group">
                <label>Tahun Ajaran <span class="required">*</span></label>
                <select v-model="form.academic_year_id" required class="form-select" @change="onFormYearChange">
                  <option value="">Pilih</option>
                  <option v-for="year in academicYears" :key="year.id" :value="year.id">
                    {{ year.code }} — {{ year.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Semester</label>
                <select v-model="form.semester_id" class="form-select">
                  <option value="">— Opsional —</option>
                  <option v-for="s in formSemesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
              <div class="form-group full">
                <label>Judul <span class="required">*</span></label>
                <input v-model="form.title" type="text" required maxlength="255" class="form-input" placeholder="Mis: Libur Semester" />
              </div>
              <div class="form-group full">
                <label>Deskripsi</label>
                <textarea v-model="form.description" class="form-textarea" placeholder="Opsional" />
              </div>
              <div class="form-group">
                <label>Jenis <span class="required">*</span></label>
                <select v-model="form.event_type" required class="form-select" @change="onTypeChange">
                  <option v-for="t in eventTypes" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status" class="form-select">
                  <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal Mulai <span class="required">*</span></label>
                <input v-model="form.start_date" type="date" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Tanggal Akhir</label>
                <input v-model="form.end_date" type="date" class="form-input" />
              </div>
              <div class="form-group full">
                <label class="checkbox-label">
                  <input v-model="form.is_all_day" type="checkbox" />
                  Seharian (tanpa jam spesifik)
                </label>
              </div>
              <template v-if="!form.is_all_day">
                <div class="form-group">
                  <label>Jam Mulai <span class="required">*</span></label>
                  <input v-model="form.start_time" type="time" class="form-input" required />
                </div>
                <div class="form-group">
                  <label>Jam Akhir <span class="required">*</span></label>
                  <input v-model="form.end_time" type="time" class="form-input" required />
                </div>
              </template>
              <div class="form-group">
                <label>Warna</label>
                <input v-model="form.color" type="color" class="form-color" />
              </div>
              <div class="form-group">
                <label>Pengingat (hari sebelum)</label>
                <div class="reminder-chips">
                  <button
                    v-for="d in reminderOptions"
                    :key="d"
                    type="button"
                    class="chip"
                    :class="{ active: form.reminder_days_before.includes(d) }"
                    @click="toggleReminder(d)"
                  >
                    {{ d }} hari
                  </button>
                </div>
              </div>
            </div>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeModal">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : (editingEvent ? 'Simpan Perubahan' : 'Tambah Event') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    />
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { academicCalendarApi } from '@/api/academicCalendar'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import './academicCalendar.css'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()
const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const eventTypes = ['Ujian', 'Libur', 'Kegiatan', 'Other']
const statuses = ['Aktif', 'Draft', 'Dibatalkan']
const reminderOptions = [1, 3, 7, 14]
const weekDayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const TYPE_COLORS = {
  Ujian: '#dc2626',
  Libur: '#059669',
  Kegiatan: '#2563eb',
  Other: '#64748b'
}

const viewMode = ref('list')
const events = ref([])
const monthEvents = ref([])
const loading = ref(true)
const monthLoading = ref(false)
const pagination = ref(null)
const filterSemesters = ref([])
const formSemesters = ref([])
const showModal = ref(false)
const editingEvent = ref(null)
const saving = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  academic_year_id: '',
  semester_id: '',
  event_type: '',
  status: ''
})

const now = new Date()
const monthCursor = ref(new Date(now.getFullYear(), now.getMonth(), 1))

const emptyForm = () => ({
  academic_year_id: '',
  semester_id: '',
  title: '',
  description: '',
  event_type: 'Kegiatan',
  start_date: '',
  end_date: '',
  is_all_day: true,
  start_time: '',
  end_time: '',
  reminder_days_before: [],
  color: TYPE_COLORS.Kegiatan,
  status: 'Aktif'
})

const form = ref(emptyForm())

let filterTimer = null
const onFilterChange = () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    if (viewMode.value === 'list') loadEvents(1)
    else loadMonthEvents()
  }, 250)
}

const typeColor = (type) => TYPE_COLORS[type] || TYPE_COLORS.Other

const monthLabel = computed(() =>
  monthCursor.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
)

const monthRange = computed(() => {
  const y = monthCursor.value.getFullYear()
  const m = monthCursor.value.getMonth()
  const start = new Date(y, m, 1)
  const end = new Date(y, m + 1, 0)
  return {
    start_date: toYmd(start),
    end_date: toYmd(end)
  }
})

const monthCells = computed(() => {
  const y = monthCursor.value.getFullYear()
  const m = monthCursor.value.getMonth()
  const first = new Date(y, m, 1)
  const last = new Date(y, m + 1, 0)
  const startPad = (first.getDay() + 6) % 7
  const cells = []
  const rows = Math.ceil((startPad + last.getDate()) / 7) * 7
  const todayStr = toYmd(new Date())

  for (let i = 0; i < rows; i++) {
    const dayNum = i - startPad + 1
    const date = new Date(y, m, dayNum)
    const inMonth = dayNum >= 1 && dayNum <= last.getDate()
    const ymd = toYmd(date)
    cells.push({
      day: date.getDate(),
      inMonth,
      isToday: ymd === todayStr,
      events: inMonth ? monthEvents.value.filter((ev) => eventCoversDate(ev, ymd)) : []
    })
  }
  return cells
})

function toYmd(d) {
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function eventCoversDate(ev, ymd) {
  const start = ev.start_date
  const end = ev.end_date || ev.start_date
  return start <= ymd && ymd <= end
}

async function loadFilterSemesters(yearId) {
  filterSemesters.value = []
  if (!yearId) return
  try {
    const res = await semesterApi.getByAcademicYear(yearId)
    filterSemesters.value = res.data?.data || res.data || []
  } catch {
    filterSemesters.value = []
  }
}

async function loadFormSemesters(yearId) {
  formSemesters.value = []
  if (!yearId) return
  try {
    const res = await semesterApi.getByAcademicYear(yearId)
    formSemesters.value = res.data?.data || res.data || []
  } catch {
    formSemesters.value = []
  }
}

function cleanParams(obj) {
  const params = { ...obj }
  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  return params
}

async function loadEvents(page = 1) {
  loading.value = true
  try {
    const response = await academicCalendarApi.getAll(cleanParams({
      page,
      per_page: 15,
      ...filters.value
    }))
    events.value = response.data.data || []
    pagination.value = response.data.meta
      ? {
          current_page: response.data.meta.current_page,
          last_page: response.data.meta.last_page
        }
      : null
  } catch (e) {
    toast.error('Gagal memuat event', e.response?.data?.message || '')
    events.value = []
  } finally {
    loading.value = false
  }
}

async function loadMonthEvents() {
  monthLoading.value = true
  try {
    const response = await academicCalendarApi.getCalendar({
      start_date: monthRange.value.start_date,
      end_date: monthRange.value.end_date
    })
    let list = response.data.data || response.data || []
    if (filters.value.academic_year_id) {
      list = list.filter((e) => String(e.academic_year_id) === String(filters.value.academic_year_id))
    }
    if (filters.value.semester_id) {
      list = list.filter((e) => String(e.semester_id) === String(filters.value.semester_id))
    }
    if (filters.value.event_type) {
      list = list.filter((e) => e.event_type === filters.value.event_type)
    }
    if (filters.value.status) {
      list = list.filter((e) => e.status === filters.value.status)
    }
    if (filters.value.search) {
      const q = filters.value.search.toLowerCase()
      list = list.filter((e) => (e.title || '').toLowerCase().includes(q))
    }
    monthEvents.value = list
  } catch (e) {
    toast.error('Gagal memuat kalender', e.response?.data?.message || '')
    monthEvents.value = []
  } finally {
    monthLoading.value = false
  }
}

function onYearFilterChange() {
  filters.value.semester_id = ''
  loadFilterSemesters(filters.value.academic_year_id)
  onFilterChange()
}

function switchToMonth() {
  viewMode.value = 'month'
  loadMonthEvents()
}

function shiftMonth(delta) {
  const d = new Date(monthCursor.value)
  d.setMonth(d.getMonth() + delta)
  monthCursor.value = d
  loadMonthEvents()
}

function goThisMonth() {
  const t = new Date()
  monthCursor.value = new Date(t.getFullYear(), t.getMonth(), 1)
  loadMonthEvents()
}

function openCreate() {
  editingEvent.value = null
  form.value = emptyForm()
  const active = academicYears.value.find((y) => y.status === 'Aktif')
  if (active) {
    form.value.academic_year_id = active.id
    loadFormSemesters(active.id)
  }
  error.value = ''
  showModal.value = true
}

async function editEvent(event) {
  editingEvent.value = event
  form.value = {
    academic_year_id: event.academic_year_id || '',
    semester_id: event.semester_id || '',
    title: event.title || '',
    description: event.description || '',
    event_type: event.event_type || 'Kegiatan',
    start_date: event.start_date || '',
    end_date: event.end_date || '',
    is_all_day: event.is_all_day !== false,
    start_time: formatTime(event.start_time) || '',
    end_time: formatTime(event.end_time) || '',
    reminder_days_before: Array.isArray(event.reminder_days_before) ? [...event.reminder_days_before] : [],
    color: event.color || typeColor(event.event_type),
    status: event.status || 'Aktif'
  }
  await loadFormSemesters(form.value.academic_year_id)
  error.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingEvent.value = null
  error.value = ''
}

function onFormYearChange() {
  form.value.semester_id = ''
  loadFormSemesters(form.value.academic_year_id)
}

function onTypeChange() {
  if (!editingEvent.value || !editingEvent.value.color) {
    form.value.color = typeColor(form.value.event_type)
  }
}

function toggleReminder(d) {
  const arr = form.value.reminder_days_before
  const i = arr.indexOf(d)
  if (i >= 0) arr.splice(i, 1)
  else arr.push(d)
  arr.sort((a, b) => a - b)
}

async function saveEvent() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      academic_year_id: Number(form.value.academic_year_id),
      semester_id: form.value.semester_id ? Number(form.value.semester_id) : null,
      title: form.value.title.trim(),
      description: form.value.description || null,
      event_type: form.value.event_type,
      start_date: form.value.start_date,
      end_date: form.value.end_date || form.value.start_date,
      is_all_day: !!form.value.is_all_day,
      start_time: form.value.is_all_day ? null : form.value.start_time,
      end_time: form.value.is_all_day ? null : form.value.end_time,
      reminder_days_before: form.value.reminder_days_before.length ? form.value.reminder_days_before : null,
      color: form.value.color || null,
      status: form.value.status
    }

    if (editingEvent.value) {
      await academicCalendarApi.update(editingEvent.value.id, payload)
      toast.success('Berhasil', 'Event berhasil diperbarui')
    } else {
      await academicCalendarApi.create(payload)
      toast.success('Berhasil', 'Event berhasil ditambahkan')
    }
    closeModal()
    if (viewMode.value === 'list') await loadEvents(pagination.value?.current_page || 1)
    else await loadMonthEvents()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e.response?.data?.message || 'Gagal menyimpan event')
  } finally {
    saving.value = false
  }
}

async function deleteEvent(event) {
  const confirmed = await showConfirm({
    title: 'Hapus Event',
    message: `Hapus event "${event.title}"?`,
    warning: 'Tindakan ini tidak dapat dibatalkan.'
  })
  if (!confirmed) return
  try {
    await academicCalendarApi.delete(event.id)
    toast.success('Berhasil', 'Event dihapus')
    if (viewMode.value === 'list') await loadEvents(pagination.value?.current_page || 1)
    else await loadMonthEvents()
  } catch (e) {
    toast.error('Gagal menghapus', e.response?.data?.message || '')
  }
}

function formatDateRange(start, end) {
  if (!start) return '—'
  const a = formatDate(start)
  if (!end || end === start) return a
  return `${a} – ${formatDate(end)}`
}

function formatDate(dateString) {
  if (!dateString) return '—'
  return new Date(dateString + 'T00:00:00').toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function formatTime(t) {
  if (!t) return ''
  return String(t).slice(0, 5)
}

function getStatusClass(status) {
  const key = String(status || '').toLowerCase()
  return `pill pill-${key}`
}

watch(viewMode, (mode) => {
  if (mode === 'month') loadMonthEvents()
})

onMounted(async () => {
  await referenceStore.getAcademicYears()
  await loadEvents()
})
</script>
