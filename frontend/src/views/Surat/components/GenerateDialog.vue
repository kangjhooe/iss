<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { templateService } from '../services/templateService'
import suratService from '../services/suratService'

const props = defineProps({
  open: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'generated'])

const templates = ref([])
const students = ref([])
const employees = ref([])
const loading = ref(false)
const loadingStudents = ref(false)
const loadingEmployees = ref(false)
const submitting = ref(false)
const studentSearch = ref('')
const employeeSearch = ref('')
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

async function loadTemplates() {
  const tplRes = await templateService.list({ status: 'aktif', per_page: 100 })
  templates.value = tplRes.data?.data || []
}

async function loadStudents(search = '') {
  loadingStudents.value = true
  studentHint.value = ''
  try {
    const res = await suratService.students({
      per_page: 50,
      search: search || undefined
    })
    students.value = res.data?.data || []
    if (!students.value.length) {
      studentHint.value = search
        ? 'Tidak ada siswa yang cocok dengan pencarian.'
        : 'Belum ada siswa aktif di institusi ini.'
    }
  } catch (e) {
    students.value = []
    studentHint.value = e.response?.data?.message || 'Gagal memuat daftar siswa'
  } finally {
    loadingStudents.value = false
  }
}

async function loadEmployees(search = '') {
  loadingEmployees.value = true
  employeeHint.value = ''
  try {
    const res = await suratService.employees({
      per_page: 50,
      search: search || undefined
    })
    employees.value = res.data?.data || []
    if (!employees.value.length) {
      employeeHint.value = search
        ? 'Tidak ada guru/pegawai yang cocok dengan pencarian.'
        : 'Belum ada guru/pegawai aktif di institusi ini.'
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
  try {
    await loadTemplates()
    await Promise.all([loadStudents(), loadEmployees()])
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    loadStudents(studentSearch.value.trim())
  }, 300)
}

function onEmployeeSearchInput() {
  clearTimeout(employeeSearchTimer)
  employeeSearchTimer = setTimeout(() => {
    loadEmployees(employeeSearch.value.trim())
  }, 300)
}

watch(() => props.open, (val) => {
  if (val) loadData()
})

watch(() => form.value.subject_type, () => {
  form.value.student_id = ''
  form.value.employee_id = ''
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
  }
}

function selectedStudentLabel(s) {
  const kelas = s.kelas ? ` · ${s.kelas}` : ''
  return `${s.name} — ${s.nis || s.nisn || '-'}${kelas}`
}

function selectedEmployeeLabel(e) {
  const tipe = e.type ? ` · ${e.type}` : ''
  const id = e.nip || e.nuptk || '-'
  return `${e.name} — ${id}${tipe}`
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
            <label class="field">
              <span>Cari siswa</span>
              <input
                v-model="studentSearch"
                type="search"
                placeholder="Ketik nama, NIS, atau NISN..."
                @input="onSearchInput"
              />
            </label>

            <label class="field">
              <span>Siswa <em>(wajib)</em></span>
              <select v-model="form.student_id" :disabled="loadingStudents">
                <option value="">
                  {{ loadingStudents ? 'Memuat siswa...' : '— Pilih siswa —' }}
                </option>
                <option v-for="s in students" :key="s.id" :value="s.id">
                  {{ selectedStudentLabel(s) }}
                </option>
              </select>
              <small v-if="studentHint" class="field-hint">{{ studentHint }}</small>
              <small v-else-if="students.length" class="field-hint">{{ students.length }} siswa ditampilkan</small>
            </label>
          </template>

          <template v-if="needsEmployee">
            <label class="field">
              <span>Cari guru / pegawai</span>
              <input
                v-model="employeeSearch"
                type="search"
                placeholder="Ketik nama, NIP, atau NUPTK..."
                @input="onEmployeeSearchInput"
              />
            </label>

            <label class="field">
              <span>Guru / Pegawai <em>(wajib)</em></span>
              <select v-model="form.employee_id" :disabled="loadingEmployees">
                <option value="">
                  {{ loadingEmployees ? 'Memuat data...' : '— Pilih guru/pegawai —' }}
                </option>
                <option v-for="e in employees" :key="e.id" :value="e.id">
                  {{ selectedEmployeeLabel(e) }}
                </option>
              </select>
              <small v-if="employeeHint" class="field-hint">{{ employeeHint }}</small>
              <small v-else-if="employees.length" class="field-hint">{{ employees.length }} data ditampilkan</small>
            </label>
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
  width: min(520px, 100%);
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
  max-height: min(70vh, 640px);
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

.field em {
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
</style>
