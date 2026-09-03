<template>
  <div v-if="lockedStudent" class="student-picker-locked">
    <span v-if="label" class="field-label">{{ label }}</span>
    <div class="picker-student-card is-static">
      <span class="picker-avatar" :style="avatarStyle(lockedStudent)">{{ studentInitials(lockedStudent) }}</span>
      <div class="picker-student-meta">
        <strong>{{ lockedStudent.name || '—' }}</strong>
        <span>
          {{ studentIdLabel(lockedStudent) }}
          <template v-if="studentClassLabel(lockedStudent)"> · {{ studentClassLabel(lockedStudent) }}</template>
        </span>
      </div>
    </div>
  </div>

  <div v-else class="student-picker">
    <div class="picker-heading">
      <span class="field-label">{{ label }}<template v-if="required"> *</template></span>
      <span v-if="selectedStudent" class="picker-selected-hint">1 dipilih</span>
    </div>

    <div v-if="selectedStudent" class="picker-student-card is-selected">
      <span class="picker-avatar" :style="avatarStyle(selectedStudent)">{{ studentInitials(selectedStudent) }}</span>
      <div class="picker-student-meta">
        <strong>{{ selectedStudent.name }}</strong>
        <span>
          {{ studentIdLabel(selectedStudent) }}
          <template v-if="studentClassLabel(selectedStudent)"> · {{ studentClassLabel(selectedStudent) }}</template>
        </span>
      </div>
      <button v-if="!pickerPanelOpen" type="button" class="picker-clear" @click="pickerPanelOpen = true">Ganti</button>
    </div>

    <div v-show="!selectedStudent || pickerPanelOpen" class="picker-panel">
      <div class="picker-toolbar">
        <label class="picker-field">
          <span>Kelas</span>
          <select v-model="pickerClassId" @change="onClassChange">
            <option value="">{{ requireClass ? 'Pilih kelas dulu' : 'Semua kelas' }}</option>
            <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
          </select>
        </label>
        <label class="picker-field picker-field-search">
          <span>Cari siswa</span>
          <div class="picker-search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M20 20L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="pickerStudentSearch"
              type="text"
              placeholder="Nama, NIS, NISN, atau NIK"
              autocomplete="off"
              :disabled="requireClass && !pickerClassId"
              @input="debounceSearch"
            />
          </div>
        </label>
      </div>

      <div class="picker-list" role="listbox" :aria-label="label" :aria-busy="loading">
        <div v-if="loading" class="picker-state">
          <span class="picker-spinner"></span>
          Memuat siswa...
        </div>
        <div v-else-if="requireClass && !pickerClassId" class="picker-state">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p>Pilih kelas untuk menampilkan daftar siswa.</p>
        </div>
        <div v-else-if="!requireClass && !pickerClassId && !pickerStudentSearch.trim()" class="picker-state">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p>Pilih kelas atau ketik nama/NIS untuk menampilkan siswa.</p>
        </div>
        <div v-else-if="error" class="picker-state is-error">{{ error }}</div>
        <div v-else-if="!students.length" class="picker-state">Tidak ada siswa cocok.</div>
        <template v-else>
          <div class="picker-list-meta">{{ students.length }} siswa — pilih satu</div>
          <button
            v-for="s in students"
            :key="s.id"
            type="button"
            role="option"
            class="picker-option"
            :class="{ active: isSelected(s) }"
            :aria-selected="isSelected(s)"
            @click="selectStudent(s)"
          >
            <span class="picker-avatar" :style="avatarStyle(s)">{{ studentInitials(s) }}</span>
            <span class="picker-student-meta">
              <strong>{{ s.name }}</strong>
              <span>
                {{ studentIdLabel(s) }}
                <template v-if="studentClassLabel(s)"> · {{ studentClassLabel(s) }}</template>
              </span>
            </span>
            <svg v-if="isSelected(s)" class="picker-check" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" :fill="checkColor"/>
              <path d="M8 12.5l2.5 2.5L16 9.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { violationApi } from '@/api/violation'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  lockedStudent: { type: Object, default: null },
  label: { type: String, default: 'Siswa' },
  required: { type: Boolean, default: true },
  requireClass: { type: Boolean, default: true },
  checkColor: { type: String, default: '#059669' },
  focusRingColor: { type: String, default: 'rgba(5, 150, 105, 0.12)' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const classes = ref([])
const pickerClassId = ref('')
const pickerStudentSearch = ref('')
const students = ref([])
const loading = ref(false)
const error = ref('')
const pickerPanelOpen = ref(true)
const selectedStudent = ref(null)
let searchTimer = null

const AVATAR_COLORS = [
  { bg: '#d1fae5', fg: '#047857' },
  { bg: '#e0f2fe', fg: '#0369a1' },
  { bg: '#fef3c7', fg: '#b45309' },
  { bg: '#ede9fe', fg: '#6d28d9' },
  { bg: '#fce7f3', fg: '#be185d' },
  { bg: '#ffedd5', fg: '#c2410c' },
]

function studentInitials(s) {
  const parts = String(s?.name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

function avatarStyle(s) {
  const name = s?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  const color = AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
  return { background: color.bg, color: color.fg }
}

function studentIdLabel(s) {
  return s?.nis || s?.nisn || s?.nik || '—'
}

function studentClassLabel(s) {
  return s?.class_name || s?.class?.name || ''
}

function isSelected(s) {
  return String(props.modelValue) === String(s?.id)
}

function selectStudent(s) {
  emit('update:modelValue', String(s.id))
  emit('select', s)
  selectedStudent.value = s
  pickerPanelOpen.value = false
}

async function loadClasses() {
  try {
    const res = await violationApi.classesLite()
    classes.value = res.data?.data || []
  } catch {
    classes.value = []
  }
}

async function loadStudents() {
  const q = pickerStudentSearch.value.trim()
  if (props.requireClass && !pickerClassId.value) {
    students.value = []
    error.value = ''
    return
  }
  if (!props.requireClass && !pickerClassId.value && !q) {
    students.value = []
    error.value = ''
    return
  }

  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (pickerClassId.value) params.class_id = pickerClassId.value
    if (q) params.q = q
    const res = await violationApi.studentsLite(params)
    students.value = res.data?.data || []
    if (!students.value.length) {
      error.value = q
        ? 'Tidak ada siswa cocok. Coba kata kunci lain.'
        : 'Tidak ada siswa aktif di kelas ini.'
    }
    syncSelectedFromList()
  } catch (e) {
    students.value = []
    error.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat data siswa.'
  } finally {
    loading.value = false
  }
}

function syncSelectedFromList() {
  if (!props.modelValue) {
    selectedStudent.value = null
    return
  }
  const found = students.value.find((s) => String(s.id) === String(props.modelValue))
  if (found) selectedStudent.value = found
}

function debounceSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadStudents(), 300)
}

function onClassChange() {
  emit('update:modelValue', '')
  selectedStudent.value = null
  pickerStudentSearch.value = ''
  pickerPanelOpen.value = true
  loadStudents()
}

function reset() {
  pickerClassId.value = ''
  pickerStudentSearch.value = ''
  students.value = []
  error.value = ''
  pickerPanelOpen.value = true
  selectedStudent.value = null
  emit('update:modelValue', '')
  clearTimeout(searchTimer)
}

watch(() => props.modelValue, (id) => {
  if (!id) {
    selectedStudent.value = null
    return
  }
  if (selectedStudent.value && String(selectedStudent.value.id) === String(id)) return
  const found = students.value.find((s) => String(s.id) === String(id))
  if (found) selectedStudent.value = found
})

onMounted(() => {
  loadClasses()
})

defineExpose({ reset, loadClasses })
</script>

<style scoped>
.student-picker {
  padding: 0.9rem 1rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.student-picker-locked .field-label {
  display: block;
  margin-bottom: 0.45rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
}
.picker-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}
.picker-heading .field-label {
  margin: 0;
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
}
.picker-selected-hint {
  font-size: 0.75rem;
  font-weight: 600;
  color: #047857;
  background: #d1fae5;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}
.picker-student-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.85rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.picker-student-card.is-selected {
  border-color: #059669;
  background: #ecfdf5;
  box-shadow: 0 0 0 3px v-bind(focusRingColor);
}
.picker-student-card.is-static {
  background: #fff;
}
.picker-avatar {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}
.picker-student-meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.picker-student-meta strong,
.picker-student-meta span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.picker-student-meta strong {
  font-size: 0.92rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.25;
}
.picker-student-meta span {
  font-size: 0.78rem;
  color: #64748b;
  line-height: 1.3;
}
.picker-clear {
  margin-left: auto;
  flex-shrink: 0;
  padding: 0.3rem 0.7rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.picker-clear:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.picker-student-card + .picker-panel {
  margin-top: 0.7rem;
}
.picker-toolbar {
  display: grid;
  grid-template-columns: minmax(120px, 0.9fr) minmax(160px, 1.4fr);
  gap: 0.65rem;
}
.picker-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin: 0;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
}
.picker-field select,
.picker-search-wrap {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
}
.picker-field select {
  padding: 0.5rem 0.7rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
}
.picker-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0 0.7rem;
  color: #94a3b8;
}
.picker-search-wrap input {
  flex: 1;
  width: auto;
  min-width: 0;
  border: none;
  border-radius: 0;
  padding: 0.5rem 0;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
  background: transparent;
  box-shadow: none;
}
.picker-search-wrap input:focus {
  outline: none;
}
.picker-search-wrap:focus-within {
  border-color: #059669;
  box-shadow: 0 0 0 3px v-bind(focusRingColor);
}
.picker-search-wrap input:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
.picker-list {
  margin-top: 0.65rem;
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
.picker-list-meta {
  padding: 0.45rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 1;
}
.picker-option {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.75rem;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  cursor: pointer;
}
.picker-option:last-child {
  border-bottom: none;
}
.picker-option:hover {
  background: #f8fafc;
}
.picker-option.active {
  background: #ecfdf5;
}
.picker-option.active .picker-student-meta strong {
  color: #047857;
}
.picker-check {
  margin-left: auto;
  flex-shrink: 0;
}
.picker-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 132px;
  padding: 1rem;
  text-align: center;
  font-size: 0.85rem;
  color: #64748b;
}
.picker-state p {
  margin: 0;
  max-width: 220px;
  line-height: 1.45;
}
.picker-state.is-error {
  color: #b91c1c;
  min-height: 88px;
}
.picker-spinner {
  width: 22px;
  height: 22px;
  border: 2px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: picker-spin 0.7s linear infinite;
}
@keyframes picker-spin {
  to { transform: rotate(360deg); }
}
@media (max-width: 540px) {
  .picker-toolbar {
    grid-template-columns: 1fr;
  }
}
</style>
