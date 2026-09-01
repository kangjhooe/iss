<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { templateService } from '../services/templateService'
import suratService from '../services/suratService'

const props = defineProps({
  open: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'generated'])

const templates = ref([])
const classes = ref([])
const students = ref([])
const employees = ref([])
const loading = ref(false)
const loadingStudents = ref(false)
const loadingEmployees = ref(false)
const submitting = ref(false)
const studentSearch = ref('')
const employeeSearch = ref('')
const studentClassId = ref('')
const employeeType = ref('')
const studentPickerOpen = ref(true)
const employeePickerOpen = ref(true)
const form = ref({
  template_id: '',
  subject_type: 'siswa',
  student_id: '',
  employee_id: '',
  judul: '',
  tanggal: new Date().toISOString().slice(0, 10)
})
const error = ref('')
const studentHint = ref('')
const employeeHint = ref('')
let searchTimer = null
let employeeSearchTimer = null

function isPlatform(t) {
  return !!(t?.is_platform || t?.institution_id == null)
}

const platformTemplates = computed(() => templates.value.filter(isPlatform))
const schoolTemplates = computed(() => templates.value.filter((t) => !isPlatform(t)))

const needsStudent = computed(() => form.value.subject_type === 'siswa')
const needsEmployee = computed(() => form.value.subject_type === 'pegawai')

const selectedStudent = computed(() =>
  students.value.find((s) => String(s.id) === String(form.value.student_id)) || null
)

const selectedEmployee = computed(() =>
  employees.value.find((e) => String(e.id) === String(form.value.employee_id)) || null
)

async function loadTemplates() {
  const tplRes = await templateService.list({ status: 'aktif', per_page: 100 })
  templates.value = tplRes.data?.data || []
}

async function loadClasses() {
  try {
    const res = await suratService.classes()
    classes.value = res.data?.data || []
  } catch {
    classes.value = []
  }
}

async function loadStudents() {
  const search = studentSearch.value.trim()
  if (!studentClassId.value && !search) {
    students.value = []
    studentHint.value = 'Pilih kelas atau ketik nama/NIS untuk menampilkan siswa.'
    return
  }

  loadingStudents.value = true
  studentHint.value = ''
  try {
    const params = {}
    if (studentClassId.value) params.class_id = studentClassId.value
    if (search) params.search = search
    const res = await suratService.students(params)
    students.value = res.data?.data || []
    if (!students.value.length) {
      studentHint.value = search
        ? 'Tidak ada siswa yang cocok dengan pencarian.'
        : 'Tidak ada siswa aktif di kelas ini.'
    }
  } catch (e) {
    students.value = []
    studentHint.value = e.response?.data?.message || 'Gagal memuat daftar siswa'
  } finally {
    loadingStudents.value = false
  }
}

async function loadEmployees() {
  const search = employeeSearch.value.trim()
  if (!employeeType.value && !search) {
    employees.value = []
    employeeHint.value = 'Pilih tipe atau ketik nama/NIP untuk menampilkan guru/pegawai.'
    return
  }

  loadingEmployees.value = true
  employeeHint.value = ''
  try {
    const params = { per_page: 100 }
    if (employeeType.value) params.type = employeeType.value
    if (search) params.search = search
    const res = await suratService.employees(params)
    employees.value = res.data?.data || []
    if (!employees.value.length) {
      employeeHint.value = search
        ? 'Tidak ada guru/pegawai yang cocok dengan pencarian.'
        : 'Tidak ada guru/pegawai aktif untuk filter ini.'
    }
  } catch (e) {
    employees.value = []
    employeeHint.value = e.response?.data?.message || 'Gagal memuat daftar guru/pegawai'
  } finally {
    loadingEmployees.value = false
  }
}

async function loadData() {
  loading.value = true
  error.value = ''
  form.value = {
    template_id: '',
    subject_type: 'siswa',
    student_id: '',
    employee_id: '',
    judul: '',
    tanggal: new Date().toISOString().slice(0, 10)
  }
  studentSearch.value = ''
  employeeSearch.value = ''
  studentClassId.value = ''
  employeeType.value = ''
  students.value = []
  employees.value = []
  studentPickerOpen.value = true
  employeePickerOpen.value = true
  studentHint.value = 'Pilih kelas atau ketik nama/NIS untuk menampilkan siswa.'
  employeeHint.value = 'Pilih tipe atau ketik nama/NIP untuk menampilkan guru/pegawai.'
  try {
    await Promise.all([loadTemplates(), loadClasses()])
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadStudents, 300)
}

function onEmployeeSearchInput() {
  clearTimeout(employeeSearchTimer)
  employeeSearchTimer = setTimeout(loadEmployees, 300)
}

function onClassChange() {
  form.value.student_id = ''
  studentPickerOpen.value = true
  loadStudents()
}

function onEmployeeTypeChange() {
  form.value.employee_id = ''
  employeePickerOpen.value = true
  loadEmployees()
}

function selectStudent(student) {
  form.value.student_id = student.id
  studentPickerOpen.value = false
}

function selectEmployee(employee) {
  form.value.employee_id = employee.id
  employeePickerOpen.value = false
}

watch(() => props.open, (val) => {
  if (val) loadData()
})

watch(() => form.value.subject_type, () => {
  form.value.student_id = ''
  form.value.employee_id = ''
  studentPickerOpen.value = true
  employeePickerOpen.value = true
})

onMounted(() => {
  if (props.open) loadData()
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  clearTimeout(employeeSearchTimer)
})

function onTemplateChange() {
  const tpl = templates.value.find((t) => String(t.id) === String(form.value.template_id))
  if (tpl) {
    form.value.judul = tpl.nama
    form.value.subject_type = tpl.subject_type || 'siswa'
    form.value.student_id = ''
    form.value.employee_id = ''
    studentPickerOpen.value = true
    employeePickerOpen.value = true
  }
}

function studentIdLabel(s) {
  return s.nis || s.nisn || s.nik || '—'
}

function selectedStudentLabel(s) {
  const kelas = s.kelas ? ` · ${s.kelas}` : ''
  return `${s.name} — ${studentIdLabel(s)}${kelas}`
}

function selectedEmployeeLabel(e) {
  const tipe = e.type ? ` · ${e.type}` : ''
  const id = e.nip || e.nuptk || '-'
  const jabatan = e.jabatan ? ` · ${e.jabatan}` : ''
  return `${e.name} — ${id}${tipe}${jabatan}`
}

async function submit() {
  if (!form.value.template_id) {
    error.value = 'Pilih template terlebih dahulu'
    return
  }
  if (needsStudent.value && !form.value.student_id) {
    error.value = 'Pilih siswa terlebih dahulu'
    return
  }
  if (needsEmployee.value && !form.value.employee_id) {
    error.value = 'Pilih guru/pegawai terlebih dahulu'
    return
  }
  submitting.value = true
  error.value = ''
  try {
    const payload = {
      template_id: Number(form.value.template_id),
      subject_type: form.value.subject_type,
      judul: form.value.judul || undefined,
      tanggal: form.value.tanggal || undefined
    }
    if (needsStudent.value) {
      payload.student_id = Number(form.value.student_id)
    }
    if (needsEmployee.value) {
      payload.employee_id = Number(form.value.employee_id)
    }
    const res = await suratService.generate(payload)
    emit('generated', res.data.data)
    emit('close')
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal membuat draft surat'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-card" role="dialog" aria-modal="true">
        <header class="modal-header">
          <h3>Buat Surat dari Template</h3>
          <button type="button" class="btn-close" @click="emit('close')">×</button>
        </header>

        <div class="modal-body">
          <div class="hint">
            Draft dibuat <strong>tanpa nomor</strong>. Nomor resmi baru muncul setelah Anda klik
            <strong>Terbitkan</strong>.
          </div>
          <p v-if="loading" class="muted">Memuat...</p>
          <p v-if="error" class="error">{{ error }}</p>

          <label class="field">
            <span>Template</span>
            <select v-model="form.template_id" @change="onTemplateChange">
              <option value="">— Pilih template —</option>
              <optgroup v-if="platformTemplates.length" label="Template Platform">
                <option v-for="t in platformTemplates" :key="'p-' + t.id" :value="t.id">
                  {{ t.nama }} ({{ t.kode }})
                </option>
              </optgroup>
              <optgroup v-if="schoolTemplates.length" label="Template Sekolah">
                <option v-for="t in schoolTemplates" :key="'s-' + t.id" :value="t.id">
                  {{ t.nama }} ({{ t.kode }})
                </option>
              </optgroup>
            </select>
            <small v-if="!loading && !templates.length" class="field-hint">
              Belum ada template aktif. Kelola di menu Template.
            </small>
          </label>

          <fieldset class="field fieldset">
            <legend>Subjek surat</legend>
            <div class="radio-row">
              <label class="radio">
                <input v-model="form.subject_type" type="radio" value="siswa" />
                Siswa
              </label>
              <label class="radio">
                <input v-model="form.subject_type" type="radio" value="pegawai" />
                Guru / Pegawai
              </label>
              <label class="radio">
                <input v-model="form.subject_type" type="radio" value="umum" />
                Tanpa subjek
              </label>
            </div>
            <small class="field-hint">
              Mengikuti template yang dipilih; bisa diganti jika perlu.
            </small>
          </fieldset>

          <template v-if="needsStudent">
            <div class="picker-block">
              <div class="picker-head">
                <span>Siswa <em>(wajib)</em></span>
                <span v-if="selectedStudent" class="picker-badge">1 dipilih</span>
              </div>

              <div v-if="selectedStudent && !studentPickerOpen" class="picker-selected">
                <div class="picker-selected-main">
                  <strong>{{ selectedStudent.name }}</strong>
                  <span>{{ studentIdLabel(selectedStudent) }}<template v-if="selectedStudent.kelas"> · {{ selectedStudent.kelas }}</template></span>
                </div>
                <button type="button" class="picker-change" @click="studentPickerOpen = true">Ganti</button>
              </div>

              <div v-show="!selectedStudent || studentPickerOpen" class="picker-panel">
                <div class="picker-toolbar">
                  <label class="picker-field">
                    <span>Kelas</span>
                    <select v-model="studentClassId" @change="onClassChange">
                      <option value="">— Pilih kelas —</option>
                      <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                        {{ c.name }}
                      </option>
                    </select>
                  </label>
                  <label class="picker-field picker-field-grow">
                    <span>Cari siswa</span>
                    <input
                      v-model="studentSearch"
                      type="search"
                      placeholder="Nama, NIS, NISN, atau NIK"
                      @input="onSearchInput"
                    />
                  </label>
                </div>

                <div class="picker-list" :aria-busy="loadingStudents">
                  <div v-if="loadingStudents" class="picker-state">Memuat siswa...</div>
                  <div v-else-if="studentHint" class="picker-state">{{ studentHint }}</div>
                  <template v-else>
                    <div v-if="students.length" class="picker-list-meta">{{ students.length }} siswa</div>
                    <button
                      v-for="s in students"
                      :key="s.id"
                      type="button"
                      class="picker-option"
                      :class="{ active: String(form.student_id) === String(s.id) }"
                      @click="selectStudent(s)"
                    >
                      <span class="picker-option-main">
                        <strong>{{ s.name }}</strong>
                        <span>{{ studentIdLabel(s) }}<template v-if="s.kelas"> · {{ s.kelas }}</template></span>
                      </span>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </template>

          <template v-if="needsEmployee">
            <div class="picker-block">
              <div class="picker-head">
                <span>Guru / Pegawai <em>(wajib)</em></span>
                <span v-if="selectedEmployee" class="picker-badge">1 dipilih</span>
              </div>

              <div v-if="selectedEmployee && !employeePickerOpen" class="picker-selected">
                <div class="picker-selected-main">
                  <strong>{{ selectedEmployee.name }}</strong>
                  <span>{{ selectedEmployeeLabel(selectedEmployee) }}</span>
                </div>
                <button type="button" class="picker-change" @click="employeePickerOpen = true">Ganti</button>
              </div>

              <div v-show="!selectedEmployee || employeePickerOpen" class="picker-panel">
                <div class="picker-toolbar">
                  <label class="picker-field">
                    <span>Tipe</span>
                    <select v-model="employeeType" @change="onEmployeeTypeChange">
                      <option value="">— Semua tipe —</option>
                      <option value="Guru">Guru</option>
                      <option value="Pegawai">Pegawai</option>
                    </select>
                  </label>
                  <label class="picker-field picker-field-grow">
                    <span>Cari</span>
                    <input
                      v-model="employeeSearch"
                      type="search"
                      placeholder="Nama, NIP, NUPTK, atau NIK"
                      @input="onEmployeeSearchInput"
                    />
                  </label>
                </div>

                <div class="picker-list" :aria-busy="loadingEmployees">
                  <div v-if="loadingEmployees" class="picker-state">Memuat data...</div>
                  <div v-else-if="employeeHint" class="picker-state">{{ employeeHint }}</div>
                  <template v-else>
                    <div v-if="employees.length" class="picker-list-meta">{{ employees.length }} data</div>
                    <button
                      v-for="e in employees"
                      :key="e.id"
                      type="button"
                      class="picker-option"
                      :class="{ active: String(form.employee_id) === String(e.id) }"
                      @click="selectEmployee(e)"
                    >
                      <span class="picker-option-main">
                        <strong>{{ e.name }}</strong>
                        <span>{{ selectedEmployeeLabel(e) }}</span>
                      </span>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </template>

          <label class="field">
            <span>Judul</span>
            <input v-model="form.judul" type="text" placeholder="Judul surat" />
          </label>

          <label class="field">
            <span>Tanggal</span>
            <input v-model="form.tanggal" type="date" />
          </label>
        </div>

        <footer class="modal-footer">
          <button type="button" class="btn" @click="emit('close')">Batal</button>
          <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
            {{ submitting ? 'Memproses...' : 'Buat Draft' }}
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(32, 33, 36, 0.55);
  z-index: 11000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card {
  background: #fff;
  width: min(580px, 100%);
  border-radius: 12px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 16px;
}

.btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  color: #5f6368;
}

.modal-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: min(75vh, 720px);
  overflow-y: auto;
}

.hint {
  background: #fef7e0;
  color: #7a5c00;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 12px;
  line-height: 1.45;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  color: #3c4043;
}

.fieldset {
  border: 1px solid #e8eaed;
  border-radius: 8px;
  padding: 10px 12px;
  margin: 0;
}

.fieldset legend {
  padding: 0 4px;
  font-size: 12px;
  color: #5f6368;
}

.radio-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 16px;
}

.radio {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  cursor: pointer;
}

.field em,
.picker-head em {
  font-style: normal;
  color: #d93025;
  font-size: 11px;
}

.field input,
.field select {
  border: 1px solid #dadce0;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 14px;
}

.field-hint {
  color: #80868b;
  font-size: 12px;
}

.picker-block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.picker-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  color: #3c4043;
}

.picker-badge {
  font-size: 11px;
  color: #137333;
  background: #e6f4ea;
  padding: 2px 8px;
  border-radius: 999px;
}

.picker-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid #c6dafc;
  background: #e8f0fe;
  border-radius: 8px;
}

.picker-selected-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.picker-selected-main strong {
  font-size: 14px;
}

.picker-selected-main span {
  font-size: 12px;
  color: #5f6368;
}

.picker-change {
  border: none;
  background: transparent;
  color: #1a73e8;
  font-size: 12px;
  cursor: pointer;
  flex-shrink: 0;
}

.picker-panel {
  border: 1px solid #e8eaed;
  border-radius: 8px;
  overflow: hidden;
}

.picker-toolbar {
  display: grid;
  grid-template-columns: 160px 1fr;
  gap: 8px;
  padding: 10px;
  background: #f8f9fa;
  border-bottom: 1px solid #e8eaed;
}

.picker-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 11px;
  color: #5f6368;
}

.picker-field-grow {
  min-width: 0;
}

.picker-field select,
.picker-field input {
  border: 1px solid #dadce0;
  border-radius: 6px;
  padding: 7px 8px;
  font-size: 13px;
  background: #fff;
}

.picker-list {
  max-height: 220px;
  overflow-y: auto;
}

.picker-list-meta {
  padding: 6px 12px;
  font-size: 11px;
  color: #80868b;
  background: #fff;
  border-bottom: 1px solid #f1f3f4;
}

.picker-state {
  padding: 20px 12px;
  text-align: center;
  font-size: 12px;
  color: #80868b;
}

.picker-option {
  display: flex;
  width: 100%;
  border: none;
  border-bottom: 1px solid #f1f3f4;
  background: #fff;
  padding: 10px 12px;
  text-align: left;
  cursor: pointer;
}

.picker-option:last-child {
  border-bottom: none;
}

.picker-option:hover {
  background: #f8f9fa;
}

.picker-option.active {
  background: #e8f0fe;
}

.picker-option-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.picker-option-main strong {
  font-size: 13px;
  color: #202124;
}

.picker-option-main span {
  font-size: 12px;
  color: #5f6368;
}

.muted { color: #5f6368; margin: 0; }
.error { color: #d93025; margin: 0; font-size: 13px; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid #eee;
}

.btn {
  border: 1px solid #dadce0;
  background: #fff;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
}

.btn-primary {
  background: #1a73e8;
  border-color: #1a73e8;
  color: #fff;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 560px) {
  .picker-toolbar {
    grid-template-columns: 1fr;
  }
}
</style>
