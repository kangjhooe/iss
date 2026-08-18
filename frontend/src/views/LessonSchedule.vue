<template>
  <Layout>
    <div class="schedule-page">
      <div class="toolbar">
        <div class="tabs">
          <button v-if="isTeacher" :class="['tab', { active: activeTab === 'my' }]" @click="switchTab('my')">Jadwal Saya</button>
          <button :class="['tab', { active: activeTab === 'template' }]" @click="switchTab('template')">1. Template Jadwal</button>
          <button :class="['tab', { active: activeTab === 'byClass' }]" @click="switchTab('byClass')">2. Jadwal per Kelas</button>
          <button :class="['tab', { active: activeTab === 'print' }]" @click="switchTab('print')">Cetak PDF</button>
          <button :class="['tab', { active: activeTab === 'list' }]" @click="switchTab('list')">Daftar Slot</button>
          <button :class="['tab', { active: activeTab === 'copy' }]" @click="switchTab('copy')">Copy Jadwal</button>
        </div>
        <div class="toolbar-actions">
          <router-link to="/subject" class="btn-secondary btn-compact">Mata Pelajaran</router-link>
        </div>
      </div>

      <!-- Tab: Jadwal Saya -->
      <template v-if="activeTab === 'my'">
        <div class="filters filters-inline">
          <label>Semester</label>
          <select v-model="mySemesterId" @change="loadMySchedule" class="filter-select">
            <option value="">Pilih Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }} ({{ s.academic_year?.name || '-' }})</option>
          </select>
        </div>
        <div v-if="!mySemesterId" class="empty-state">
          <p>Pilih semester untuk menampilkan jadwal mengajar Anda.</p>
        </div>
        <div v-else-if="loadingMy" class="loading-state"><p>Memuat jadwal...</p></div>
        <div v-else-if="mySchedules.length === 0" class="empty-state">
          <p>Belum ada jadwal mengajar di semester ini untuk sekolah aktif.</p>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Hari</th>
                <th>Jam ke</th>
                <th>Kelas</th>
                <th>Mata Pelajaran</th>
                <th>Ruangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in mySchedules" :key="row.id">
                <td>{{ row.day_name || dayNamesMap[row.day_of_week] || '-' }}</td>
                <td>{{ row.period ?? '-' }}</td>
                <td>{{ displayValue(row.school_class?.name) }}</td>
                <td>{{ displayValue(row.subject?.name) }}</td>
                <td>{{ displayValue(row.room?.name) }}</td>
                <td>
                  <router-link
                    class="btn-action btn-edit"
                    :to="{
                      path: '/teaching-journal',
                      query: {
                        semester_id: row.semester_id || mySemesterId,
                        class_id: row.class_id,
                        subject_id: row.subject_id,
                        lesson_schedule_id: row.id,
                        period: row.period,
                      },
                    }"
                  >Buat Jurnal</router-link>
                  <router-link
                    class="btn-action btn-edit"
                    :to="{
                      path: '/grade-book',
                      query: {
                        semester_id: row.semester_id || mySemesterId,
                        class_id: row.class_id,
                        subject_id: row.subject_id,
                      },
                    }"
                  >Buku Nilai</router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Tab: Template Jadwal (hari libur + JP) -->
      <template v-if="activeTab === 'template'">
        <div class="filters filters-inline">
          <label>Semester</label>
          <select v-model="templateSemesterId" @change="onTemplateSemesterChange" class="filter-select">
            <option value="">Pilih Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }} ({{ s.academic_year?.name || '-' }})</option>
          </select>
        </div>

        <div v-if="!templateSemesterId" class="empty-state">
          <p>Pilih semester untuk mengelola template jadwal.</p>
        </div>
        <div v-else-if="loadingTemplate" class="loading-state"><p>Memuat template...</p></div>
        <div v-else class="template-layout">
          <div class="card template-list-card">
            <div class="template-list-header">
              <h4>Daftar Template</h4>
              <button type="button" class="btn-secondary btn-compact" @click="startCreateTemplate">+ Baru</button>
            </div>
            <p class="template-hint">
              Satu sekolah bisa punya beberapa template (mis. 8 JP / 9 JP). Tiap kelas memilih template-nya sendiri.
            </p>
            <ul class="template-list">
              <li
                v-for="t in templates"
                :key="t.id"
                :class="['template-list-item', { active: !isCreatingTemplate && String(selectedTemplateId) === String(t.id) }]"
                @click="selectTemplate(t.id)"
              >
                <span class="template-list-name">{{ t.name }}</span>
              </li>
              <li v-if="isCreatingTemplate" class="template-list-item active creating">
                <span class="template-list-name">{{ templateName || 'Template baru' }}</span>
                <span class="template-badge draft">Baru</span>
              </li>
              <li v-if="templates.length === 0 && !isCreatingTemplate" class="template-list-empty">Belum ada template tersimpan.</li>
            </ul>
          </div>

          <div class="card template-card">
            <div v-if="!selectedTemplateId && !isCreatingTemplate" class="empty-state">
              <p>Pilih template di kiri, atau buat template baru.</p>
            </div>
            <template v-else-if="selectedTemplateId || isCreatingTemplate">
              <div class="template-editor-header">
                <div class="form-group template-name-group">
                  <label>Nama Template</label>
                  <input v-model="templateName" type="text" class="form-input" maxlength="100" placeholder="Contoh: Reguler 8 JP" />
                </div>
                <div v-if="!isCreatingTemplate" class="template-editor-actions">
                  <button
                    type="button"
                    class="btn-danger btn-compact"
                    :disabled="savingTemplate"
                    @click="deleteSelectedTemplate"
                  >
                    Hapus
                  </button>
                </div>
                <div v-else class="template-editor-actions">
                  <button type="button" class="btn-secondary btn-compact" :disabled="savingTemplate" @click="cancelCreateTemplate">
                    Batal
                  </button>
                </div>
              </div>
              <p class="template-hint">
                Tandai hari libur mingguan, lalu tentukan berapa JP tiap hari aktif.
                Setiap kelas memilih template sendiri di tab Jadwal per Kelas.
              </p>
              <div v-if="templateError" class="error-message">{{ templateError }}</div>
              <table class="data-table template-table">
                <thead>
                  <tr>
                    <th>Hari</th>
                    <th>Hari Libur</th>
                    <th>Jumlah JP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="day in templateDays" :key="day.day_of_week">
                    <td>{{ day.day_name }}</td>
                    <td>
                      <label class="checkbox-label">
                        <input type="checkbox" v-model="day.is_holiday" @change="onHolidayToggle(day)" />
                        Libur
                      </label>
                    </td>
                    <td>
                      <input
                        type="number"
                        min="0"
                        max="20"
                        class="form-input jp-input"
                        v-model.number="day.periods"
                        :disabled="day.is_holiday"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
              <div class="template-actions">
                <button
                  v-if="isCreatingTemplate"
                  type="button"
                  class="btn-primary"
                  :disabled="savingTemplate || !templateName.trim()"
                  @click="createTemplate"
                >
                  {{ savingTemplate ? 'Membuat...' : 'Buat Template' }}
                </button>
                <button
                  v-else
                  type="button"
                  class="btn-primary"
                  :disabled="savingTemplate"
                  @click="saveTemplate"
                >
                  {{ savingTemplate ? 'Menyimpan...' : 'Simpan Template' }}
                </button>
                <span v-if="templateSavedMsg" class="copy-result">{{ templateSavedMsg }}</span>
              </div>
            </template>
          </div>
        </div>
      </template>

      <!-- Tab: Jadwal per Kelas -->
      <template v-if="activeTab === 'byClass'">
        <div class="filters filters-inline">
          <label>Semester</label>
          <select v-model="filterSemesterId" @change="onFilterSemesterChange" class="filter-select">
            <option value="">Pilih Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }} ({{ s.academic_year?.name || '-' }})</option>
          </select>
          <label>Kelas</label>
          <select v-model="filterClassId" @change="loadByClassIfNeeded" class="filter-select">
            <option value="">Pilih Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <label>Template Kelas</label>
          <select
            v-model="classTemplateId"
            class="filter-select"
            :disabled="!filterSemesterId || !filterClassId || savingClassTemplate || !classTemplates.length"
            @change="saveClassTemplate"
          >
            <option value="">— Lepas template —</option>
            <option v-for="t in classTemplates" :key="t.id" :value="String(t.id)">
              {{ t.name }}
            </option>
          </select>
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="!filterSemesterId || !filterClassId || exportingPdf"
            @click="exportSchedulePdf('class', { semester_id: filterSemesterId, class_id: filterClassId })"
          >
            {{ exportingPdf ? 'Menyiapkan...' : 'Preview PDF' }}
          </button>
        </div>

        <div v-if="filterSemesterId && !templatePersisted" class="notice-banner">
          Belum ada template tersimpan untuk semester ini.
          <button type="button" class="link-btn" @click="switchTab('template')">Buat template dulu</button>
          — grid memakai sementara 8 JP/hari.
        </div>
        <div v-else-if="filterSemesterId && filterClassId && !classTemplateId" class="notice-banner">
          Kelas ini belum punya template. Pilih template di atas agar jumlah JP sesuai kelas tersebut.
        </div>

        <div v-if="!filterSemesterId || !filterClassId" class="empty-state">
          <p>Pilih semester dan kelas. Slot mengikuti template (hari libur &amp; jumlah JP).</p>
        </div>
        <div v-else-if="loadingMatrix" class="loading-state"><p>Memuat jadwal...</p></div>
        <div v-else class="schedule-matrix-wrap">
          <table class="schedule-matrix">
            <thead>
              <tr>
                <th>Jam</th>
                <th v-for="day in activeMatrixDays" :key="day.day_of_week">
                  {{ day.day_name }}
                  <span class="day-jp">({{ day.periods }} JP)</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in maxPeriods" :key="period">
                <td class="period-cell">Ke-{{ period }}</td>
                <td
                  v-for="day in activeMatrixDays"
                  :key="day.day_of_week"
                  class="slot-cell"
                  :class="{ 'slot-out': period > day.periods }"
                >
                  <template v-if="period > day.periods">
                    <span class="slot-na">—</span>
                  </template>
                  <template v-else-if="getSlot(day.day_of_week, period)">
                    <div class="slot-subject">{{ getSlot(day.day_of_week, period).subject?.name }}</div>
                    <div class="slot-teacher">{{ getSlot(day.day_of_week, period).employee?.name }}</div>
                    <div class="slot-room" v-if="getSlot(day.day_of_week, period).room">{{ getSlot(day.day_of_week, period).room?.name }}</div>
                    <button type="button" class="slot-edit-btn" @click="editSlotFromMatrix(getSlot(day.day_of_week, period))" title="Edit">✎</button>
                  </template>
                  <button v-else type="button" class="slot-add-btn" @click="openAddSlotFor(day.day_of_week, period)" title="Tambah">+</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Tab: Cetak PDF -->
      <template v-if="activeTab === 'print'">
        <div class="card print-card">
          <p class="template-hint">
            Cetak jadwal resmi (kop + tanda tangan). Preview terbuka di tab baru.
          </p>
          <div class="form-group">
            <label>Jenis cetak</label>
            <div class="print-mode-tabs">
              <button type="button" :class="['tab', { active: printForm.mode === 'class' }]" @click="printForm.mode = 'class'">Per Kelas</button>
              <button type="button" :class="['tab', { active: printForm.mode === 'teacher' }]" @click="printForm.mode = 'teacher'">Per Guru</button>
              <button type="button" :class="['tab', { active: printForm.mode === 'subject' }]" @click="printForm.mode = 'subject'">Per Mapel</button>
            </div>
          </div>
          <div class="form-group">
            <label>Semester <span class="required">*</span></label>
            <select v-model="printForm.semester_id" class="form-input" @change="onPrintSemesterChange">
              <option value="">Pilih Semester</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }} ({{ s.academic_year?.name || '-' }})</option>
            </select>
          </div>
          <div v-if="printForm.mode === 'class'" class="form-group">
            <label>Kelas <span class="required">*</span></label>
            <select v-model="printForm.class_id" class="form-input">
              <option value="">Pilih Kelas</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div v-if="printForm.mode === 'teacher'" class="form-group">
            <label>Guru <span class="required">*</span></label>
            <select v-model="printForm.employee_id" class="form-input">
              <option value="">Pilih Guru</option>
              <option v-for="emp in teachers" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
            </select>
          </div>
          <div v-if="printForm.mode === 'subject'" class="form-group">
            <label>Mata Pelajaran <span class="required">*</span></label>
            <select v-model="printForm.subject_id" class="form-input">
              <option value="">Pilih Mapel</option>
              <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.code }} - {{ sub.name }}</option>
            </select>
          </div>
          <div class="template-actions">
            <button type="button" class="btn-primary" :disabled="!canPrintPdf || exportingPdf" @click="exportFromPrintTab">
              {{ exportingPdf ? 'Menyiapkan PDF...' : 'Preview PDF' }}
            </button>
          </div>
        </div>
      </template>

      <!-- Tab: Daftar Slot -->
      <template v-if="activeTab === 'list'">
        <div class="filters filters-inline">
          <select v-model="listFilters.semester_id" @change="onListSemesterChange" class="filter-select">
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
                <th>Jam</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Ruangan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scheduleList" :key="row.id">
                <td>{{ row.school_class?.name || '-' }}</td>
                <td>{{ row.day_name || dayNamesMap[row.day_of_week] || '-' }}</td>
                <td>{{ row.period }}</td>
                <td>{{ row.subject?.name || '-' }}</td>
                <td>{{ row.employee?.name || '-' }}</td>
                <td>{{ row.room?.name || '—' }}</td>
                <td>
                  <button type="button" class="btn-action btn-edit" @click="editSlot(row)">Edit</button>
                  <button type="button" class="btn-action btn-delete" @click="deleteSlot(row.id)">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="scheduleList.length === 0" class="empty-state"><p>Belum ada slot jadwal.</p></div>
        </div>
      </template>

      <!-- Tab: Copy -->
      <template v-if="activeTab === 'copy'">
        <div class="card copy-form">
          <p>Salin jadwal dari satu semester ke semester lain. Pilih kelas opsional untuk menyalin hanya satu kelas.</p>
          <div class="form-group">
            <label>Semester sumber</label>
            <select v-model="copyForm.source_semester_id" class="form-input">
              <option value="">Pilih</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Semester tujuan</label>
            <select v-model="copyForm.target_semester_id" class="form-input">
              <option value="">Pilih</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Kelas (opsional)</label>
            <select v-model="copyForm.class_id" class="form-input">
              <option value="">Semua kelas</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <button type="button" @click="doCopy" :disabled="copying || !copyForm.source_semester_id || !copyForm.target_semester_id" class="btn-primary">
            {{ copying ? 'Menyalin...' : 'Salin Jadwal' }}
          </button>
          <p v-if="copyResult" class="copy-result">{{ copyResult }}</p>
        </div>
      </template>

      <!-- Modal slot -->
      <div v-if="showSlotModal" class="modal-overlay" @click="closeSlotModal">
        <div class="modal-content modal-wide" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSlot ? 'Edit Slot Jadwal' : 'Isi Slot Jadwal' }}</h3>
            <button @click="closeSlotModal" class="modal-close">×</button>
          </div>
          <form @submit.prevent="saveSlot" class="modal-body">
            <div v-if="slotError" class="error-message">{{ slotError }}</div>
            <div class="slot-context">
              <span>{{ classNameById(slotForm.class_id) }}</span>
              <span>·</span>
              <span>{{ dayNamesMap[slotForm.day_of_week] }}</span>
              <span>·</span>
              <span>Jam ke-{{ slotForm.period }}</span>
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
            <div v-if="!editingSlot" class="form-group">
              <label>Berapa JP <span class="required">*</span></label>
              <select v-model.number="slotForm.duration" required class="form-input">
                <option v-for="n in availableDurations" :key="n" :value="n">{{ n }} JP</option>
              </select>
              <p class="field-hint">Mengisi berurutan dari jam ke-{{ slotForm.period }}.</p>
            </div>
            <div class="form-group">
              <label>Ruangan (opsional)</label>
              <select v-model="slotForm.room_id" class="form-input">
                <option value="">—</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="slotForm.allow_teacher_conflict" />
                Tetap simpan meski guru bentrok (kelas/sekolah lain)
              </label>
              <p class="field-hint">Centang jika guru memang boleh mengajar di jam yang sama di tempat lain.</p>
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
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const authStore = useAuthStore()
const isTeacher = computed(() => {
  const role = authStore.user?.role
  return role === 'teacher' || role === 'staff'
})

const activeTab = ref('byClass')
const semesters = ref([])
const classes = ref([])
const subjects = ref([])
const teachers = ref([])
const rooms = ref([])
const activeAcademicYearId = ref('')
const filterSemesterId = ref('')
const filterClassId = ref('')
const mySemesterId = ref('')
const mySchedules = ref([])
const loadingMy = ref(false)
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

const templateSemesterId = ref('')
const templates = ref([])
const selectedTemplateId = ref('')
const templateName = ref('')
const templateDays = ref([])
const loadingTemplate = ref(false)
const savingTemplate = ref(false)
const templateError = ref('')
const templateSavedMsg = ref('')
const templatePersisted = ref(false)
const isCreatingTemplate = ref(false)
const classTemplates = ref([])
const classTemplateId = ref('')
const previousClassTemplateId = ref('')
const savingClassTemplate = ref(false)
const exportingPdf = ref(false)

const printForm = reactive({
  mode: 'class',
  semester_id: '',
  class_id: '',
  employee_id: '',
  subject_id: '',
})

const dayNamesMap = {
  1: 'Senin',
  2: 'Selasa',
  3: 'Rabu',
  4: 'Kamis',
  5: 'Jumat',
  6: 'Sabtu',
  7: 'Minggu',
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
  duration: 1,
  subject_id: '',
  employee_id: '',
  room_id: '',
  allow_teacher_conflict: false,
})

const activeMatrixDays = computed(() => {
  const matrix = scheduleMatrix.value?.matrix
  if (Array.isArray(matrix) && matrix.length) {
    return matrix.filter((d) => !d.is_holiday && (d.periods || 0) > 0)
  }
  return []
})

const maxPeriods = computed(() => {
  const fromTemplate = scheduleMatrix.value?.template?.max_periods
  if (fromTemplate) return fromTemplate
  return Math.max(0, ...activeMatrixDays.value.map((d) => d.periods || 0), 0)
})

const availableDurations = computed(() => {
  const day = activeMatrixDays.value.find((d) => d.day_of_week === Number(slotForm.day_of_week))
  const maxForDay = day?.periods || 8
  const remaining = Math.max(1, maxForDay - Number(slotForm.period) + 1)
  return Array.from({ length: remaining }, (_, i) => i + 1)
})

const canPrintPdf = computed(() => {
  if (!printForm.semester_id) return false
  if (printForm.mode === 'class') return !!printForm.class_id
  if (printForm.mode === 'teacher') return !!printForm.employee_id
  if (printForm.mode === 'subject') return !!printForm.subject_id
  return false
})

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function classNameById(id) {
  const c = classes.value.find((x) => String(x.id) === String(id))
  return c?.name || `Kelas #${id}`
}

function switchTab(tab) {
  activeTab.value = tab
  if (tab === 'my') loadMySchedule()
  if (tab === 'byClass') {
    loadClassTemplates().then(() => loadByClassIfNeeded())
  }
  if (tab === 'list') loadSchedules()
  if (tab === 'template') {
    if (!templateSemesterId.value && filterSemesterId.value) {
      templateSemesterId.value = filterSemesterId.value
    }
    loadTemplates()
  }
  if (tab === 'print') {
    if (!printForm.semester_id && filterSemesterId.value) {
      printForm.semester_id = filterSemesterId.value
      onPrintSemesterChange()
    }
    if (!printForm.class_id && filterClassId.value) {
      printForm.class_id = filterClassId.value
    }
  }
}

async function onPrintSemesterChange() {
  if (printForm.semester_id) {
    await loadClassesForSemester(printForm.semester_id)
  }
}

async function exportFromPrintTab() {
  const params = { semester_id: printForm.semester_id }
  if (printForm.mode === 'class') params.class_id = printForm.class_id
  if (printForm.mode === 'teacher') params.employee_id = printForm.employee_id
  if (printForm.mode === 'subject') params.subject_id = printForm.subject_id
  await exportSchedulePdf(printForm.mode, params)
}

async function exportSchedulePdf(mode, params) {
  exportingPdf.value = true
  try {
    const res = await lessonScheduleApi.exportPdf({ mode, ...params })
    const contentType = res.headers?.['content-type'] || ''
    if (res.status !== 200 || contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mencetak jadwal.')
    }
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
      URL.revokeObjectURL(url)
      return
    }
    const modeLabel = mode === 'class' ? 'Per Kelas' : mode === 'teacher' ? 'Per Guru' : 'Per Mapel'
    const title = `Preview Jadwal ${modeLabel}`
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        body{margin:0;font-family:system-ui,sans-serif;background:#0f172a}
        .toolbar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;color:#f8fafc;border-bottom:1px solid #1e293b}
        .toolbar h1{margin:0;font-size:14px;font-weight:600}
        .actions button{border:none;border-radius:8px;padding:8px 14px;font-weight:600;cursor:pointer}
        .btn-print{background:#059669;color:#fff}
        .btn-close{background:#334155;color:#e2e8f0;margin-left:8px}
        iframe{width:100%;height:calc(100vh - 52px);border:0;background:#525659}
      </style></head><body>
      <div class="toolbar">
        <h1>${title}</h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus();document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
    toast.success('Berhasil', 'Preview PDF jadwal dibuka.')
  } catch (err) {
    toast.error('Gagal', err.message || err.response?.data?.message || err.formattedMessage || 'Gagal mencetak jadwal.')
  } finally {
    exportingPdf.value = false
  }
}

async function loadClassesForSemester(semesterId) {
  try {
    const semester = semesters.value.find((s) => String(s.id) === String(semesterId))
    const params = {
      per_page: 100,
      status: 'Aktif',
      omit_semester: 1,
    }
    if (semester?.academic_year_id) {
      params.academic_year_id = semester.academic_year_id
    } else if (activeAcademicYearId.value) {
      params.academic_year_id = activeAcademicYearId.value
    }
    if (semesterId) {
      // Prefer classes of this semester when available; fallback year-wide via omit_semester
      const withSem = await classApi.getAll({
        per_page: 100,
        status: 'Aktif',
        semester_id: semesterId,
        academic_year_id: params.academic_year_id,
      })
      let list = withSem.data?.data ?? withSem.data ?? []
      if (!Array.isArray(list)) list = []
      if (list.length === 0) {
        const yearRes = await classApi.getAll(params)
        list = yearRes.data?.data ?? yearRes.data ?? []
      }
      classes.value = Array.isArray(list) ? list : []
    } else {
      const res = await classApi.getAll(params)
      const list = res.data?.data ?? res.data ?? []
      classes.value = Array.isArray(list) ? list : []
    }
  } catch (e) {
    console.error(e)
    classes.value = []
  }
}

async function loadInitial() {
  try {
    const [instRes, semRes, subRes, empRes, roomRes] = await Promise.all([
      institutionApi.getMy().catch(() => ({ data: null })),
      semesterApi.getAll({ per_page: 100 }),
      subjectApi.getAll({ per_page: 'all', active_only: true }).catch(() => ({ data: [] })),
      teacherApi.getAll({ status: 'Aktif', per_page: 200 }),
      facilityApi.getRooms({ per_page: 200 }),
    ])
    const institution = instRes.data?.data ?? instRes.data ?? null
    semesters.value = semRes.data?.data ?? semRes.data ?? []
    subjects.value = Array.isArray(subRes.data) ? subRes.data : (subRes.data?.data ?? [])
    teachers.value = empRes.data?.data ?? empRes.data ?? []
    rooms.value = roomRes.data?.data ?? roomRes.data ?? []
    activeAcademicYearId.value = institution?.active_academic_year_id
      ? String(institution.active_academic_year_id)
      : ''
    if (institution?.active_semester_id) {
      const sid = String(institution.active_semester_id)
      listFilters.semester_id = sid
      filterSemesterId.value = sid
      mySemesterId.value = sid
      templateSemesterId.value = sid
      printForm.semester_id = sid
    }
    await loadClassesForSemester(filterSemesterId.value || listFilters.semester_id)
  } catch (e) {
    console.error(e)
  }
}

async function loadTemplates() {
  templateSavedMsg.value = ''
  templateError.value = ''
  isCreatingTemplate.value = false
  if (!templateSemesterId.value) {
    templates.value = []
    selectedTemplateId.value = ''
    templateDays.value = []
    templatePersisted.value = false
    return
  }
  loadingTemplate.value = true
  try {
    const res = await lessonScheduleApi.listTemplates({ semester_id: templateSemesterId.value })
    const list = res.data?.data ?? res.data ?? []
    templates.value = Array.isArray(list) ? list : []
    templatePersisted.value = templates.value.length > 0

    if (!templates.value.length) {
      selectedTemplateId.value = ''
      templateDays.value = defaultTemplateDays()
      templateName.value = ''
      return
    }

    const keepId = selectedTemplateId.value
      && templates.value.some((t) => String(t.id) === String(selectedTemplateId.value))
      ? selectedTemplateId.value
      : templates.value[0].id

    await selectTemplate(keepId)
  } catch (e) {
    templates.value = []
    selectedTemplateId.value = ''
    templateDays.value = defaultTemplateDays()
    templateError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat template.'
  } finally {
    loadingTemplate.value = false
  }
}

function defaultTemplateDays() {
  return Object.keys(dayNamesMap).map((k) => {
    const day = Number(k)
    const isWeekend = day >= 6
    return {
      day_of_week: day,
      day_name: dayNamesMap[k],
      periods: isWeekend ? 0 : 8,
      is_holiday: isWeekend,
    }
  })
}

function mapTemplateDays(days) {
  return (days || []).map((d) => ({
    day_of_week: d.day_of_week,
    day_name: d.day_name || dayNamesMap[d.day_of_week],
    periods: d.is_holiday ? 0 : Number(d.periods || 0),
    is_holiday: !!d.is_holiday,
  }))
}

async function selectTemplate(id) {
  selectedTemplateId.value = id ? String(id) : ''
  isCreatingTemplate.value = false
  templateSavedMsg.value = ''
  templateError.value = ''
  const t = templates.value.find((x) => String(x.id) === String(id))
  if (!t) {
    templateDays.value = defaultTemplateDays()
    templateName.value = ''
    return
  }
  templateName.value = t.name || ''
  templateDays.value = mapTemplateDays(t.days)
}

async function onTemplateSemesterChange() {
  selectedTemplateId.value = ''
  await loadTemplates()
}

function startCreateTemplate() {
  isCreatingTemplate.value = true
  selectedTemplateId.value = ''
  templateName.value = `Template ${templates.value.length + 1}`
  templateDays.value = defaultTemplateDays()
  templateError.value = ''
  templateSavedMsg.value = ''
}

function cancelCreateTemplate() {
  isCreatingTemplate.value = false
  templateError.value = ''
  if (templates.value.length) {
    const fallback = templates.value[0].id
    selectTemplate(fallback)
  } else {
    selectedTemplateId.value = ''
    templateDays.value = []
    templateName.value = ''
  }
}

async function createTemplate() {
  if (!templateSemesterId.value || !templateName.value.trim()) return
  if (!templateDays.value.length) {
    templateDays.value = defaultTemplateDays()
  }
  savingTemplate.value = true
  templateError.value = ''
  try {
    const res = await lessonScheduleApi.createTemplate({
      semester_id: Number(templateSemesterId.value),
      name: templateName.value.trim(),
      days: templateDays.value.map((d) => ({
        day_of_week: d.day_of_week,
        periods: d.is_holiday ? 0 : Number(d.periods || 0),
        is_holiday: !!d.is_holiday,
      })),
    })
    const data = res.data?.data ?? {}
    isCreatingTemplate.value = false
    await loadTemplates()
    if (data.id) await selectTemplate(data.id)
    templateSavedMsg.value = res.data?.message || 'Template dibuat.'
  } catch (e) {
    templateError.value = e.formattedMessage || e.response?.data?.message || 'Gagal membuat template.'
  } finally {
    savingTemplate.value = false
  }
}

function onHolidayToggle(day) {
  if (day.is_holiday) {
    day.periods = 0
  } else if (!day.periods) {
    day.periods = 8
  }
}

async function saveTemplate() {
  if (!templateSemesterId.value || !selectedTemplateId.value) return
  savingTemplate.value = true
  templateError.value = ''
  templateSavedMsg.value = ''
  try {
    const res = await lessonScheduleApi.updateTemplate(selectedTemplateId.value, {
      name: templateName.value.trim() || 'Template',
      days: templateDays.value.map((d) => ({
        day_of_week: d.day_of_week,
        periods: d.is_holiday ? 0 : Number(d.periods || 0),
        is_holiday: !!d.is_holiday,
      })),
    })
    const data = res.data?.data ?? {}
    templatePersisted.value = true
    templateSavedMsg.value = res.data?.message || 'Template tersimpan.'
    await loadTemplates()
    if (data.id) await selectTemplate(data.id)
    if (String(filterSemesterId.value) === String(templateSemesterId.value)) {
      await loadClassTemplates()
      await loadByClassIfNeeded()
    }
  } catch (e) {
    templateError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan template.'
  } finally {
    savingTemplate.value = false
  }
}

async function deleteSelectedTemplate() {
  if (!selectedTemplateId.value) return
  if (!confirm('Hapus template ini? Kelas yang memakai template ini perlu memilih template lain.')) return
  savingTemplate.value = true
  templateError.value = ''
  try {
    await lessonScheduleApi.deleteTemplate(selectedTemplateId.value)
    selectedTemplateId.value = ''
    await loadTemplates()
    templateSavedMsg.value = 'Template dihapus.'
    if (String(filterSemesterId.value) === String(templateSemesterId.value)) {
      await loadClassTemplates()
      await loadByClassIfNeeded()
    }
  } catch (e) {
    templateError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menghapus template.'
  } finally {
    savingTemplate.value = false
  }
}

async function onFilterSemesterChange() {
  filterClassId.value = ''
  classTemplateId.value = ''
  previousClassTemplateId.value = ''
  scheduleMatrix.value = null
  await loadClassesForSemester(filterSemesterId.value)
  await loadClassTemplates()
  await refreshTemplateFlag()
  loadByClassIfNeeded()
}

async function onListSemesterChange() {
  await loadClassesForSemester(listFilters.semester_id || filterSemesterId.value)
  loadSchedules()
}

async function loadClassTemplates() {
  if (!filterSemesterId.value) {
    classTemplates.value = []
    return
  }
  try {
    const res = await lessonScheduleApi.listTemplates({ semester_id: filterSemesterId.value })
    const list = res.data?.data ?? res.data ?? []
    classTemplates.value = Array.isArray(list) ? list : []
    templatePersisted.value = classTemplates.value.length > 0
  } catch {
    classTemplates.value = []
  }
}

async function refreshTemplateFlag() {
  await loadClassTemplates()
}

async function saveClassTemplate() {
  if (!filterClassId.value) return

  const targetId = classTemplateId.value ? Number(classTemplateId.value) : null
  const previousId = previousClassTemplateId.value

  // No actual change
  if (String(targetId || '') === String(previousId || '')) {
    return
  }

  // Accidental clear protection
  if (!targetId && previousId) {
    const ok = confirm(
      'Lepas template dari kelas ini?\nGrid akan memakai template fallback semester (bukan template khusus kelas).'
    )
    if (!ok) {
      classTemplateId.value = previousId
      return
    }
  }

  savingClassTemplate.value = true
  try {
    let prune = false

    if (targetId) {
      const previewRes = await lessonScheduleApi.previewClassTemplate(filterClassId.value, {
        lesson_schedule_template_id: targetId,
      })
      const preview = previewRes.data?.data ?? previewRes.data ?? {}
      const affected = Number(preview.affected_count || 0)

      if (affected > 0) {
        const samples = Array.isArray(preview.affected_slots) ? preview.affected_slots : []
        const sampleText = samples
          .slice(0, 5)
          .map((s) => `- ${s.day_name} jam ke-${s.period}${s.subject_name ? ` (${s.subject_name})` : ''}`)
          .join('\n')
        const more = affected > samples.length ? `\n… dan ${affected - Math.min(5, samples.length)} lainnya` : ''
        const ok = confirm(
          `Ada ${affected} slot jadwal yang tidak muat di template "${preview.target_template_name || 'baru'}".\n` +
          `Slot tersebut akan dihapus jika Anda lanjutkan.\n\n` +
          (sampleText ? `${sampleText}${more}\n\n` : '') +
          `Lanjutkan dan hapus slot tersebut?`
        )
        if (!ok) {
          classTemplateId.value = previousId
          return
        }
        prune = true
      }
    }

    const res = await lessonScheduleApi.assignClassTemplate(filterClassId.value, {
      lesson_schedule_template_id: targetId,
      prune_out_of_bounds: prune,
    })

    previousClassTemplateId.value = classTemplateId.value
    await loadByClassIfNeeded()
    toast.success('Berhasil', res.data?.message || 'Template kelas disimpan.')
  } catch (e) {
    classTemplateId.value = previousId
    // Backend may still return 409 if preview was skipped/stale
    if (e.response?.status === 409) {
      const data = e.response?.data?.data ?? {}
      const affected = Number(data.affected_count || 0)
      const ok = confirm(
        `Ada ${affected} slot jadwal yang tidak muat di template baru.\n` +
        `Slot tersebut akan dihapus jika Anda lanjutkan.\n\nLanjutkan?`
      )
      if (ok) {
        savingClassTemplate.value = true
        try {
          classTemplateId.value = targetId ? String(targetId) : ''
          const res = await lessonScheduleApi.assignClassTemplate(filterClassId.value, {
            lesson_schedule_template_id: targetId,
            prune_out_of_bounds: true,
          })
          previousClassTemplateId.value = classTemplateId.value
          await loadByClassIfNeeded()
          toast.success('Berhasil', res.data?.message || 'Template kelas disimpan.')
          return
        } catch (e2) {
          classTemplateId.value = previousId
          toast.error('Gagal', e2.formattedMessage || e2.response?.data?.message || 'Gagal menyimpan template kelas.')
          await loadByClassIfNeeded()
          return
        }
      }
      await loadByClassIfNeeded()
      return
    }
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan template kelas.')
    await loadByClassIfNeeded()
  } finally {
    savingClassTemplate.value = false
  }
}

async function loadMySchedule() {
  if (!mySemesterId.value) {
    mySchedules.value = []
    return
  }
  loadingMy.value = true
  try {
    const res = await lessonScheduleApi.getMyTeachingLoad({ semester_id: mySemesterId.value })
    const data = res.data?.data ?? res.data ?? {}
    const list = Array.isArray(data.schedules) ? data.schedules : []
    mySchedules.value = list.map((s) => s?.data ?? s)
  } catch {
    mySchedules.value = []
  } finally {
    loadingMy.value = false
  }
}

async function loadByClassIfNeeded() {
  if (!filterSemesterId.value || !filterClassId.value) return
  loadingMatrix.value = true
  try {
    await loadClassTemplates()
    const res = await lessonScheduleApi.getByClass(filterClassId.value, { semester_id: filterSemesterId.value })
    scheduleMatrix.value = res.data
    classTemplateId.value = res.data?.lesson_schedule_template_id
      ? String(res.data.lesson_schedule_template_id)
      : ''
    previousClassTemplateId.value = classTemplateId.value
    if (res.data?.template) {
      templatePersisted.value = !!res.data.template.is_persisted
    }
  } catch {
    scheduleMatrix.value = null
  } finally {
    loadingMatrix.value = false
  }
}

function getSlot(dayOfWeek, period) {
  if (!scheduleMatrix.value?.matrix) return null
  const dayRow = scheduleMatrix.value.matrix.find((d) => d.day_of_week === dayOfWeek)
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
  slotForm.duration = 1
  slotForm.subject_id = ''
  slotForm.employee_id = ''
  slotForm.room_id = ''
  slotForm.allow_teacher_conflict = false
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
  slotForm.duration = 1
  slotForm.subject_id = slotData.subject_id || ''
  slotForm.employee_id = slotData.employee_id || ''
  slotForm.room_id = slotData.room_id || ''
  slotForm.allow_teacher_conflict = false
  slotError.value = ''
  showSlotModal.value = true
}

function editSlot(row) {
  editingSlot.value = row
  slotForm.semester_id = row.semester_id || ''
  slotForm.class_id = row.class_id || ''
  slotForm.day_of_week = row.day_of_week
  slotForm.period = row.period
  slotForm.duration = 1
  slotForm.subject_id = row.subject_id || ''
  slotForm.employee_id = row.employee_id || ''
  slotForm.room_id = row.room_id || ''
  slotForm.allow_teacher_conflict = false
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
      allow_teacher_conflict: !!slotForm.allow_teacher_conflict,
    }
    if (editingSlot.value?.id) {
      await lessonScheduleApi.update(editingSlot.value.id, payload)
    } else {
      payload.duration = Number(slotForm.duration) || 1
      await lessonScheduleApi.create(payload)
    }
    closeSlotModal()
    loadByClassIfNeeded()
    if (activeTab.value === 'list') loadSchedules()
  } catch (e) {
    const msg = e.formattedMessage || e.response?.data?.message || e.message || 'Gagal menyimpan.'
    slotError.value = msg
    if (!slotForm.allow_teacher_conflict && /Guru sudah terjadwal/i.test(msg)) {
      slotError.value = `${msg} Centang opsi “Tetap simpan meski guru bentrok” jika tetap ingin menyimpan.`
    }
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
  } catch {
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

onMounted(async () => {
  await loadInitial()
  if (isTeacher.value) {
    activeTab.value = 'my'
    await loadMySchedule()
  } else {
    activeTab.value = 'template'
    await loadTemplates()
    await refreshTemplateFlag()
  }
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

.toolbar .tabs { margin-bottom: 0; }
.toolbar-actions { display: flex; gap: 0.5rem; }
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.tab { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; }
.tab.active { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; border-color: #059669; }
.filters-inline { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; min-width: 160px; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.loading-state, .empty-state { padding: 1.5rem; text-align: center; color: #64748b; }
.notice-banner {
  background: #fffbeb;
  border: 1px solid #fcd34d;
  color: #92400e;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}
.link-btn {
  background: none;
  border: none;
  color: #047857;
  text-decoration: underline;
  cursor: pointer;
  padding: 0;
  font: inherit;
}
.schedule-matrix-wrap { overflow-x: auto; }
.schedule-matrix { width: 100%; border-collapse: collapse; font-size: 0.875rem; background: #fff; }
.schedule-matrix th, .schedule-matrix td { border: 1px solid #e2e8f0; padding: 0.5rem; vertical-align: top; }
.schedule-matrix th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; }
.day-jp { font-weight: 400; color: #64748b; font-size: 0.75rem; }
.period-cell { min-width: 60px; }
.slot-cell { min-width: 120px; position: relative; }
.slot-out { background: #f8fafc; }
.slot-na { color: #cbd5e1; }
.slot-subject { font-weight: 500; }
.slot-teacher { font-size: 0.75rem; color: #718096; }
.slot-room { font-size: 0.75rem; color: #a0aec0; }
.slot-add-btn, .slot-edit-btn { margin-top: 4px; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; cursor: pointer; border: 1px solid #e2e8f0; background: #f7fafc; }
.slot-add-btn:hover, .slot-edit-btn:hover { background: #ecfdf5; border-color: #059669; color: #059669; }
.copy-form { max-width: 400px; padding: 1.5rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
.template-layout {
  display: grid;
  grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
  gap: 1rem;
  align-items: start;
}
.template-list-card { padding: 1rem; }
.template-list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.template-list-header h4 { margin: 0; color: #065f46; }
.template-list {
  list-style: none;
  margin: 0 0 0.75rem;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.template-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  cursor: pointer;
  background: #f8fafc;
}
.template-list-item:hover { border-color: #059669; }
.template-list-item.active {
  background: #ecfdf5;
  border-color: #059669;
}
.template-list-name { font-weight: 500; color: #1e293b; }
.template-badge {
  font-size: 0.7rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: #059669;
  color: #fff;
}
.template-list-empty { color: #64748b; font-size: 0.875rem; padding: 0.5rem 0; }
.template-list-item.creating {
  border-style: dashed;
}
.template-badge.draft {
  background: #64748b;
}
.template-editor-header {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 0.75rem;
}
.template-name-group { flex: 1; min-width: 180px; margin-bottom: 0; }
.template-editor-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.template-card { padding: 1.25rem; max-width: 720px; }
.print-card { padding: 1.25rem; max-width: 520px; }
.print-mode-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.template-hint { color: #64748b; margin-bottom: 1rem; font-size: 0.9rem; }
.template-table { margin-bottom: 1rem; }
.template-actions { display: flex; align-items: center; gap: 1rem; }
.checkbox-label { display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; }
.jp-input { max-width: 100px; }
.form-group { margin-bottom: 1rem; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.form-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.field-hint { margin: 0.35rem 0 0; font-size: 0.8rem; color: #64748b; }
.slot-context {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 1rem;
  padding: 0.65rem 0.75rem;
  background: #ecfdf5;
  border-radius: 6px;
  color: #065f46;
  font-weight: 500;
  font-size: 0.9rem;
}
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
.required { color: #c53030; }
.btn-primary { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-primary:hover:not(:disabled) { filter: brightness(1.05); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: #e2e8f0; color: #2d3748; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-danger { background: #fee2e2; color: #b91c1c; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-compact { padding: 0.4rem 0.75rem; font-size: 0.875rem; }
.btn-action { padding: 0.35rem 0.6rem; font-size: 0.875rem; border-radius: 4px; cursor: pointer; border: none; margin-right: 0.25rem; }
.btn-edit { background: rgba(5, 150, 105, 0.12); color: #059669; }
.btn-delete { background: #fed7d7; color: #c53030; }
@media (max-width: 900px) {
  .template-layout { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }

  .tabs {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    flex-wrap: nowrap;
  }

  .filter-select {
    min-width: 0;
    width: 100%;
  }

  .schedule-matrix-wrap {
    margin: 0 -0.25rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
  }

  .schedule-matrix {
    font-size: 0.75rem;
  }

  .schedule-matrix th,
  .schedule-matrix td {
    padding: 0.35rem;
  }

  .period-cell {
    min-width: 44px;
  }

  .slot-cell {
    min-width: 96px;
  }

  .template-name-group {
    min-width: 0;
    width: 100%;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
  }
}
</style>
