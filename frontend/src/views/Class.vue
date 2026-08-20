<template>
  <Layout>
    <div class="class-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <input 
            v-model="filters.search" 
            @input="loadClasses" 
            placeholder="Cari nama atau kode kelas..."
            class="search-input"
          />
          <select v-model="filters.grade" @change="loadClasses" class="filter-select">
            <option value="">Semua Tingkat</option>
            <option v-for="grade in availableGrades" :key="grade" :value="grade">
              Tingkat {{ grade }}
            </option>
          </select>
          <select v-model="filters.status" @change="loadClasses" class="filter-select">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Nonaktif">Nonaktif</option>
          </select>
        </div>
        <div class="toolbar-actions">
          <button @click="exportPdf" :disabled="exportingPdf" class="btn-secondary btn-compact" title="Preview PDF">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exportingPdf ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <button @click="showAddModal = true" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Kelas</span>
          </button>
        </div>
      </div>

      <div v-if="listError && !loading" class="error-state">
        <p class="error-text">{{ listError }}</p>
        <button @click="loadClasses(1)" class="btn-primary">Coba lagi</button>
      </div>

      <div v-else-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="5" :columns="8" />
      </div>
      
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Nama Kelas</th>
              <th>Tingkat</th>
              <th v-if="isSmk">Jurusan</th>
              <th>Ruangan</th>
              <th>Wali Kelas</th>
              <th>Siswa</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="classItem in classes" :key="classItem.id">
              <td>{{ displayValue(classItem.code) }}</td>
              <td>{{ displayValue(classItem.name) }}</td>
              <td>{{ classItem.grade != null && classItem.grade !== '' ? `Tingkat ${classItem.grade}` : 'Belum ada data' }}</td>
              <td v-if="isSmk">{{ displayValue(classItem.program_keahlian?.name || classItem.program_keahlian_name) }}</td>
              <td>{{ displayValue(classItem.room?.name) }}</td>
              <td>{{ displayValue(classItem.teacher?.name) }}</td>
              <td>
                <span 
                  v-if="classItem.capacity"
                  @click="openViewStudentsModal(classItem)"
                  class="link-student-count"
                  title="Klik untuk melihat daftar siswa"
                >
                  {{ classItem.students_count ?? 0 }} / {{ classItem.capacity }}
                </span>
                <span 
                  v-else
                  @click="openViewStudentsModal(classItem)"
                  class="link-student-count"
                  title="Klik untuk melihat daftar siswa"
                >
                  {{ classItem.students_count ?? 0 }}
                </span>
              </td>
              <td>
                <span :class="getStatusClass(classItem.status)">
                  {{ classItem.status || 'Belum ada data' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="openAddStudentModal(classItem)" class="btn-action btn-add" title="Tambah Siswa">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="editClass(classItem)" class="btn-action btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button @click="deleteClass(classItem.id)" class="btn-action btn-delete" title="Hapus">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="classes.length === 0" class="empty-state">
          <p>Belum ada data kelas</p>
        </div>

        <div v-if="pagination && pagination.last_page > 1" class="pagination">
          <button 
            @click="loadClasses(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1"
            class="pagination-btn"
          >
            Sebelumnya
          </button>
          <span class="pagination-info">
            Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
          </span>
          <button 
            @click="loadClasses(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="pagination-btn"
          >
            Selanjutnya
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingClass ? 'Edit Kelas' : 'Tambah Kelas' }}</h3>
            <button @click="closeModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveClass" class="modal-body">
            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="form-group">
              <label>Kode Kelas <span class="text-muted">(Opsional)</span></label>
              <input 
                v-model="form.code" 
                type="text" 
                placeholder="Contoh: VII-A, 10-IPA-1"
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label>Nama Kelas <span class="required">*</span></label>
              <input 
                v-model="form.name" 
                type="text" 
                required
                placeholder="Contoh: VII-A, 10 IPA 1, Kelas A"
                class="form-input"
              />
            </div>

            <div class="form-group" v-if="institutionLevel && institutionLevel !== 'PAUD' && institutionLevel !== 'TK'">
              <label>Tingkat <span class="required">*</span></label>
              <select v-model="form.grade" required class="form-input">
                <option value="">Pilih Tingkat</option>
                <option v-for="grade in availableGrades" :key="grade" :value="grade">
                  Tingkat {{ grade }}
                </option>
              </select>
            </div>

            <div class="form-group" v-if="isSmk">
              <label>Program Keahlian / Jurusan</label>
              <select v-model="form.program_keahlian_id" class="form-input">
                <option :value="null">Belum ditentukan</option>
                <option v-for="pk in programKeahlianList" :key="pk.id" :value="pk.id">
                  {{ pk.code ? `${pk.code} — ` : '' }}{{ pk.name }}
                </option>
              </select>
            </div>

            <!-- Tahun Ajaran otomatis menggunakan tahun ajaran aktif institusi -->
            <input type="hidden" v-model="form.academic_year_id" />

            <div class="form-group">
              <label>Ruangan <span class="text-muted">(Opsional)</span></label>
              <select v-model="form.room_id" class="form-input">
                <option value="">Pilih Ruangan</option>
                <option v-for="room in rooms" :key="room.id" :value="room.id">
                  {{ room.name }} {{ room.code ? `(${room.code})` : '' }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Wali Kelas <span class="text-muted">(Opsional)</span></label>
              <select v-model="form.teacher_id" class="form-input">
                <option value="">Pilih Wali Kelas</option>
                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                  {{ teacher.name }} {{ teacher.nip ? `(${teacher.nip})` : '' }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Kapasitas <span class="text-muted">(Opsional)</span></label>
              <input 
                v-model.number="form.capacity" 
                type="number" 
                min="1"
                placeholder="Jumlah maksimal siswa"
                class="form-input"
              />
            </div>

            <div class="form-group">
              <label>Status</label>
              <select v-model="form.status" class="form-input">
                <option value="Aktif">Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
              </select>
            </div>

            <div class="form-group">
              <label>Deskripsi <span class="text-muted">(Opsional)</span></label>
              <textarea 
                v-model="form.description" 
                rows="3"
                placeholder="Catatan tambahan"
                class="form-input"
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                <span v-if="saving">Menyimpan...</span>
                <span v-else>{{ editingClass ? 'Simpan Perubahan' : 'Tambah Kelas' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Students Modal -->
      <div v-if="showAddStudentModal" class="modal-overlay" @click="closeAddStudentModal">
        <div class="modal-content" @click.stop style="max-width: 700px;">
          <div class="modal-header">
            <h3>Tambah Siswa ke Kelas {{ selectedClass?.name }}</h3>
            <button @click="closeAddStudentModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="form-group">
              <label>Cari Siswa</label>
              <input 
                v-model="studentSearch" 
                type="text" 
                placeholder="Cari nama, NIS, atau NISN..."
                class="form-input"
              />
            </div>

            <div v-if="loadingStudents" class="loading-state" style="padding: 20px;">
              <p>Memuat data siswa...</p>
            </div>

            <div v-else-if="availableStudents.length === 0" class="empty-state" style="padding: 20px;">
              <p>Tidak ada siswa yang tersedia</p>
            </div>

            <div v-else class="student-list" style="max-height: 400px;">
              <div 
                v-for="student in availableStudents" 
                :key="student.id"
                class="student-item"
                :class="{ 'student-item-selected': selectedStudentIds.includes(student.id) }"
                @click="toggleStudent(student.id)"
              >
                <input 
                  type="checkbox" 
                  :checked="selectedStudentIds.includes(student.id)"
                  @change="toggleStudent(student.id)"
                  style="margin-right: 12px;"
                />
                <div style="flex: 1;">
                  <div style="font-weight: 500; color: #2d3748;">{{ student.name }}</div>
                  <div style="font-size: 12px; color: #718096; margin-top: 4px;">
                    <span v-if="student.nis">NIS: {{ student.nis }}</span>
                    <span v-if="student.nisn"> | NISN: {{ student.nisn }}</span>
                    <span v-if="student.gender"> | {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="selectedStudentIds.length > 0" style="margin-top: 16px; padding: 12px; background: #f7fafc; border-radius: 8px;">
              <strong>{{ selectedStudentIds.length }} siswa dipilih</strong>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeAddStudentModal" class="btn-secondary">Batal</button>
              <button type="button" @click="addStudentsToClass" :disabled="saving || selectedStudentIds.length === 0" class="btn-primary">
                <span v-if="saving">Menambahkan...</span>
                <span v-else>Tambah {{ selectedStudentIds.length > 0 ? `(${selectedStudentIds.length})` : '' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Students Modal -->
      <div v-if="showViewStudentsModal" class="modal-overlay" @click="closeViewStudentsModal">
        <div class="modal-content" @click.stop style="max-width: 700px;">
          <div class="modal-header">
            <h3>Daftar Siswa - {{ selectedClass?.name }}</h3>
            <button @click="closeViewStudentsModal" class="modal-close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div v-if="loadingClassStudents" class="loading-state" style="padding: 20px;">
              <p>Memuat data siswa...</p>
            </div>

            <div v-else-if="classStudents.length === 0" class="empty-state" style="padding: 20px;">
              <p>Belum ada siswa di kelas ini</p>
            </div>

            <div v-else class="student-list" style="max-height: 400px;">
              <div 
                v-for="student in classStudents" 
                :key="student.id"
                class="student-item"
                style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-bottom: 1px solid #f0f0f0;"
              >
                <div style="flex: 1;">
                  <div style="font-weight: 500; color: #2d3748;">{{ student.name }}</div>
                  <div style="font-size: 12px; color: #718096; margin-top: 4px;">
                    <span v-if="student.nis">NIS: {{ student.nis }}</span>
                    <span v-if="student.nisn"> | NISN: {{ student.nisn }}</span>
                    <span v-if="student.gender"> | {{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                  </div>
                </div>
                <button 
                  @click="removeStudentFromClass(student.id)"
                  class="btn-action btn-delete"
                  title="Hapus dari kelas"
                  style="margin-left: 12px;"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="modal-footer" style="margin-top: 16px;">
              <button type="button" @click="closeViewStudentsModal" class="btn-secondary">Tutup</button>
            </div>
          </div>
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
import { ref, onMounted, computed, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { classApi } from '@/api/class'
import { institutionApi } from '@/api/institution'
import { facilityApi } from '@/api/facility'
import { teacherApi } from '@/api/teacher'
import { programKeahlianApi } from '@/api/programKeahlian'
import { useReferenceDataStore } from '@/stores/referenceData'
import { studentApi } from '@/api/student'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { isVocationalLevel } from '@/utils/institution'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const classes = ref([])
const loading = ref(true)
const listError = ref('')
const pagination = ref(null)
const filters = ref({
  search: '',
  grade: '',
  status: ''
})

const showAddModal = ref(false)
const showEditModal = ref(false)
const showAddStudentModal = ref(false)
const showViewStudentsModal = ref(false)
const selectedClass = ref(null)
const editingClass = ref(null)
const saving = ref(false)
const error = ref('')
const availableStudents = ref([])
const classStudents = ref([])
const selectedStudentIds = ref([])
const loadingStudents = ref(false)
const loadingClassStudents = ref(false)
const studentSearch = ref('')
const exportingPdf = ref(false)

const referenceStore = useReferenceDataStore()
const academicYearsList = computed(() => referenceStore.academicYears)

const institution = ref(null)
const institutionLevel = computed(() => institution.value?.level)
const isSmk = computed(() => isVocationalLevel(institutionLevel.value))
const rooms = ref([])
const teachers = ref([])
const programKeahlianList = ref([])
const currentAcademicYear = ref(null)

const form = ref({
  code: '',
  name: '',
  grade: null,
  program_keahlian_id: null,
  academic_year_id: null,
  room_id: null,
  teacher_id: null,
  capacity: null,
  status: 'Aktif',
  description: ''
})

const availableGrades = computed(() => {
  const level = institutionLevel.value
  if (!level) return []
  
  if (level === 'SD' || level === 'MI') {
    return [1, 2, 3, 4, 5, 6]
  } else if (level === 'SMP' || level === 'MTs') {
    return [7, 8, 9]
  } else if (level === 'SMA' || level === 'MA' || level === 'MAK' || level === 'SMK') {
    return [10, 11, 12]
  }
  return []
})

function displayValue (v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

const loadInstitution = async () => {
  try {
    const response = await institutionApi.getMy()
    institution.value = response.data.data || response.data
  } catch (err) {
    console.error('Failed to load institution:', err)
  }
}

const loadRooms = async () => {
  try {
    const response = await facilityApi.getRooms({ type: 'Kelas' })
    rooms.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load rooms:', err)
  }
}

const loadTeachers = async () => {
  try {
    const response = await teacherApi.getAll({ status: 'Aktif' })
    teachers.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load teachers:', err)
  }
}

const loadProgramKeahlian = async () => {
  if (!isSmk.value) {
    programKeahlianList.value = []
    return
  }
  try {
    const response = await programKeahlianApi.getAll({ status: 'Aktif', all: 1 })
    programKeahlianList.value = response.data?.data || response.data || []
  } catch (err) {
    console.error('Failed to load program keahlian:', err)
    programKeahlianList.value = []
  }
}

const loadClasses = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 15,
      ...filters.value
    }
    
    // Remove empty filters
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    listError.value = ''
    const response = await classApi.getAll(params)
    classes.value = response.data.data || []
    pagination.value = response.data.meta || null
  } catch (err) {
    listError.value = 'Gagal memuat data kelas. Silakan coba lagi.'
    toast.error('Gagal memuat data kelas', 'Daftar kelas tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    console.error('Failed to load classes:', err)
  } finally {
    loading.value = false
  }
}

const openAddStudentModal = async (classItem) => {
  selectedClass.value = classItem
  selectedStudentIds.value = []
  studentSearch.value = ''
  showAddStudentModal.value = true
  await loadAvailableStudents()
}

const openViewStudentsModal = async (classItem) => {
  selectedClass.value = classItem
  showViewStudentsModal.value = true
  await loadClassStudents()
}

const loadClassStudents = async () => {
  if (!selectedClass.value) return
  
  loadingClassStudents.value = true
  try {
    const response = await classApi.getStudents(selectedClass.value.id, { per_page: 100 })
    classStudents.value = response.data.data || []
  } catch (err) {
    toast.error('Gagal memuat data siswa', 'Daftar siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    console.error('Failed to load class students:', err)
  } finally {
    loadingClassStudents.value = false
  }
}

const removeStudentFromClass = async (studentId) => {
  if (!selectedClass.value) return
  
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus siswa ini dari kelas?',
    warning: ''
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await classApi.removeStudent(selectedClass.value.id, studentId)
    toast.success('Berhasil', 'Siswa berhasil dihapus dari kelas')
    await loadClassStudents()
    await loadClasses()
  } catch (err) {
    const message = err.response?.data?.message || 'Gagal menghapus siswa dari kelas'
    toast.error('Gagal menghapus siswa dari kelas', message)
  } finally {
    setDeleteLoading(false)
  }
}

const closeViewStudentsModal = () => {
  showViewStudentsModal.value = false
  selectedClass.value = null
  classStudents.value = []
}

const loadAvailableStudents = async () => {
  if (!selectedClass.value) return
  
  loadingStudents.value = true
  try {
    const params = {
      search: studentSearch.value || undefined,
      per_page: 50
    }
    const response = await classApi.getAvailableStudents(selectedClass.value.id, params)
    availableStudents.value = response.data.data || []
  } catch (err) {
    toast.error('Gagal memuat data siswa', 'Daftar siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    console.error('Failed to load available students:', err)
  } finally {
    loadingStudents.value = false
  }
}

const addStudentsToClass = async () => {
  if (!selectedClass.value || selectedStudentIds.value.length === 0) {
    toast.error('Pilihan wajib', 'Pilih minimal satu siswa.')
    return
  }

  saving.value = true
  error.value = ''

  try {
    const res = await classApi.addStudents(selectedClass.value.id, selectedStudentIds.value)
    toast.success('Berhasil', 'Siswa berhasil ditambahkan ke kelas')
    closeAddStudentModal()
    const updatedClass = res?.data?.data
    if (updatedClass && typeof updatedClass.students_count === 'number') {
      const idx = classes.value.findIndex(c => c.id === updatedClass.id)
      if (idx !== -1) {
        const next = [...classes.value]
        next[idx] = { ...next[idx], ...updatedClass }
        classes.value = next
      }
    }
    await loadClasses()
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan saat menambahkan siswa'
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const firstError = Object.values(errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    }
    toast.error('Gagal menambahkan siswa ke kelas', error.value)
  } finally {
    saving.value = false
  }
}

const toggleStudent = (studentId) => {
  const index = selectedStudentIds.value.indexOf(studentId)
  if (index > -1) {
    selectedStudentIds.value.splice(index, 1)
  } else {
    selectedStudentIds.value.push(studentId)
  }
}

const closeAddStudentModal = () => {
  showAddStudentModal.value = false
  selectedClass.value = null
  selectedStudentIds.value = []
  studentSearch.value = ''
  availableStudents.value = []
  error.value = ''
}

const editClass = (classItem) => {
  editingClass.value = classItem
  form.value = {
    code: classItem.code || '',
    name: classItem.name || '',
    grade: classItem.grade || null,
    program_keahlian_id: classItem.program_keahlian_id || null,
    academic_year_id: classItem.academic_year_id || null,
    room_id: classItem.room_id || null,
    teacher_id: classItem.teacher_id || null,
    capacity: classItem.capacity || null,
    status: classItem.status || 'Aktif',
    description: classItem.description || ''
  }
  showEditModal.value = true
}

const deleteClass = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: 'Apakah Anda yakin ingin menghapus kelas ini?',
    warning: 'Data kelas akan dihapus secara permanen dan tidak dapat dikembalikan.'
  })
  
  if (!confirmed) return

  setDeleteLoading(true)
  try {
    await classApi.delete(id)
    toast.success('Berhasil', 'Kelas berhasil dihapus')
    loadClasses()
  } catch (err) {
    const message = err.response?.data?.message || 'Gagal menghapus kelas'
    toast.error('Gagal menghapus kelas', message)
  } finally {
    setDeleteLoading(false)
  }
}

const saveClass = async () => {
  saving.value = true
  error.value = ''

  try {
    const data = { ...form.value }
    
    // Convert empty strings to null
    Object.keys(data).forEach(key => {
      if (data[key] === '') {
        data[key] = null
      }
    })

    if (editingClass.value) {
      const classId = editingClass.value.id
      // Saat edit, academic_year_id tidak bisa diubah
      delete data.academic_year_id
      const res = await classApi.update(classId, data)
      toast.success('Berhasil', 'Kelas berhasil diperbarui')
      closeModal()
      // Update baris di tabel dari response agar wali kelas dll langsung tampil
      const updated = res?.data?.data
      if (updated) {
        const idx = classes.value.findIndex(c => c.id === classId)
        if (idx !== -1) {
          const next = [...classes.value]
          next[idx] = { ...next[idx], ...updated }
          if (typeof updated.students_count === 'number') next[idx].students_count = updated.students_count
          classes.value = next
        } else {
          await loadClasses(pagination.value?.current_page || 1)
        }
      } else {
        await loadClasses(pagination.value?.current_page || 1)
      }
    } else {
      // Saat create, pastikan academic_year_id sudah di-set dari active_academic_year_id
      if (!data.academic_year_id && institution.value?.active_academic_year_id) {
        data.academic_year_id = institution.value.active_academic_year_id
      }
      const res = await classApi.create(data)
      toast.success('Berhasil', 'Kelas berhasil ditambahkan')
      closeModal()
      // Selalu tampilkan kelas baru: sisipkan dari response ke awal daftar (supaya tidak hilang di halaman 2)
      const created = res?.data?.data
      if (created) {
        if (created.students_count === undefined) created.students_count = 0
        classes.value = [created, ...classes.value]
        if (pagination.value && typeof pagination.value.total === 'number') {
          pagination.value = { ...pagination.value, total: pagination.value.total + 1 }
        }
      } else {
        await loadClasses(1)
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data'
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const firstError = Object.values(errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    }
  } finally {
    saving.value = false
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingClass.value = null
  error.value = ''
  form.value = {
    code: '',
    name: '',
    grade: null,
    program_keahlian_id: null,
    academic_year_id: institution.value?.active_academic_year_id || null,
    room_id: null,
    teacher_id: null,
    capacity: null,
    status: 'Aktif',
    description: ''
  }
}

const getStatusClass = (status) => {
  return status === 'Aktif' ? 'status-badge status-active' : 'status-badge status-inactive'
}

const parseBlobErrorMessage = async (err) => {
  const data = err?.response?.data
  if (data instanceof Blob) {
    try {
      const text = await data.text()
      const json = JSON.parse(text)
      return json.message || json.error || null
    } catch {
      return null
    }
  }
  if (typeof data === 'object' && data?.message) return data.message
  return err?.formattedMessage || err?.message || null
}

const exportPdf = async () => {
  exportingPdf.value = true
  try {
    const params = {
      ...filters.value
    }

    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })

    const response = await classApi.exportPdf(params)
    const contentType = response.headers?.['content-type'] || ''
    if (response.status !== 200 || contentType.includes('application/json')) {
      const text = typeof response.data?.text === 'function' ? await response.data.text() : String(response.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mencetak data kelas.')
    }

    const blob = response.data instanceof Blob
      ? response.data
      : new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
      URL.revokeObjectURL(url)
      return
    }

    const title = 'Preview Data Kelas'
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
        .toolbar {
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
          padding: 10px 14px; background: #0f172a; color: #f8fafc;
          border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
        }
        .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
        .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
        .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>${title}<span class="hint">Preview cetak</span></h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
    toast.success('Berhasil', 'Preview PDF Data Kelas dibuka.')
  } catch (err) {
    console.error('Failed to export PDF:', err)
    const msg = await parseBlobErrorMessage(err)
    toast.error('Gagal mencetak data kelas', msg || err.message || 'Laporan PDF tidak dapat dibuka. Silakan coba lagi.')
  } finally {
    exportingPdf.value = false
  }
}

// Debounce search
let searchTimeout = null
watch(studentSearch, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (showAddStudentModal.value) {
      loadAvailableStudents()
    }
  }, 500)
})

onMounted(async () => {
  await loadInstitution()
  await referenceStore.getAcademicYears()
  
  // Set academic_year_id otomatis dari active_academic_year_id institusi
  if (institution.value?.active_academic_year_id) {
    form.value.academic_year_id = institution.value.active_academic_year_id
  }
  
  await Promise.all([loadRooms(), loadTeachers(), loadProgramKeahlian(), loadClasses()])
})
</script>

<style scoped>
.class-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.toolbar .filters {
  margin-bottom: 0;
  flex: 1;
  min-width: 200px;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-compact {
  padding: 8px 14px;
  font-size: 13px;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 10px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  flex: 1;
  min-width: 200px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.error-state {
  padding: 32px;
  text-align: center;
  background: #fef2f2;
  border-radius: 12px;
  border: 1px solid #fecaca;
}

.error-state .error-text {
  color: #b91c1c;
  margin: 0 0 16px 0;
}

.loading-wrap {
  width: 100%;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #718096;
}

.loading-spinner {
  margin-bottom: 16px;
}

.table-container {
  background: white;
  border-radius: 12px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.data-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #065f46;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #2d3748;
}

.link-student-count {
  cursor: pointer;
  color: #059669;
  text-decoration: underline;
  font-weight: 500;
}

.link-student-count:hover {
  color: #047857;
}

.text-empty {
  color: #94a3b8;
  font-style: italic;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-add {
  color: #059669;
}

.btn-add:hover {
  background: #ecfdf5;
}

.btn-edit {
  color: #059669;
}

.btn-edit:hover {
  background: #ecfdf5;
}

.btn-delete {
  color: #f56565;
}

.btn-delete:hover {
  background: #fff5f5;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-active {
  background: #c6f6d5;
  color: #22543d;
}

.status-inactive {
  background: #fed7d7;
  color: #742a2a;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
  color: #64748b;
}

.empty-state p {
  margin: 0;
}

.student-list {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
}

.student-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.2s;
  background: white;
}

.student-item:last-child {
  border-bottom: none;
}

.student-item-selected {
  background: #ecfdf5;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #e2e8f0;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #059669;
  color: #059669;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 14px;
  color: #718096;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1a202c;
}

.modal-close {
  padding: 4px;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #718096;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #2d3748;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 14px;
  color: #2d3748;
}

.required {
  color: #e53e3e;
}

.text-muted {
  color: #718096;
  font-weight: 400;
}

.form-input {
  width: 100%;
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.error-message {
  padding: 12px;
  background: #fed7d7;
  color: #742a2a;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  padding: 10px 20px;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #cbd5e0;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 1024px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .toolbar-actions .btn-primary,
  .toolbar-actions .btn-compact {
    flex: 1 1 auto;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .data-table th,
  .data-table td {
    padding: 12px;
    font-size: 13px;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    max-height: 92vh;
  }

  .modal-footer {
    flex-direction: column-reverse;
  }

  .modal-footer .btn-primary,
  .modal-footer .btn-secondary {
    width: 100%;
    justify-content: center;
  }
}
</style>
