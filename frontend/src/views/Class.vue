<template>
  <Layout>
    <div class="class-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Manajemen Kelas</h2>
            <p>Kelola data kelas sekolah Anda</p>
          </div>
          <div class="action-buttons-group">
            <button @click="showAddModal = true" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Kelas</span>
            </button>
          </div>
        </div>
      </div>

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
              <th>Ruangan</th>
              <th>Wali Kelas</th>
              <th>Siswa</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="classItem in classes" :key="classItem.id">
              <td>{{ classItem.code || '-' }}</td>
              <td>{{ classItem.name }}</td>
              <td>{{ classItem.grade ? `Tingkat ${classItem.grade}` : '-' }}</td>
              <td>{{ classItem.room?.name || '-' }}</td>
              <td>{{ classItem.teacher?.name || '-' }}</td>
              <td>
                <span 
                  v-if="classItem.capacity"
                  @click="openViewStudentsModal(classItem)"
                  style="cursor: pointer; color: #4299e1; text-decoration: underline;"
                  title="Klik untuk melihat daftar siswa"
                >
                  {{ classItem.students_count || 0 }} / {{ classItem.capacity }}
                </span>
                <span 
                  v-else
                  @click="openViewStudentsModal(classItem)"
                  style="cursor: pointer; color: #4299e1; text-decoration: underline;"
                  title="Klik untuk melihat daftar siswa"
                >
                  {{ classItem.students_count || 0 }}
                </span>
              </td>
              <td>
                <span :class="getStatusClass(classItem.status)">
                  {{ classItem.status }}
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
          <p>Tidak ada data kelas</p>
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

            <div v-else class="student-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
              <div 
                v-for="student in availableStudents" 
                :key="student.id"
                class="student-item"
                style="display: flex; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s;"
                :style="{ background: selectedStudentIds.includes(student.id) ? '#ebf8ff' : 'white' }"
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

            <div v-else class="student-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
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
import { academicYearApi } from '@/api/academicYear'
import { studentApi } from '@/api/student'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'

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

const institution = ref(null)
const institutionLevel = computed(() => institution.value?.level)
const rooms = ref([])
const teachers = ref([])
const academicYearsList = ref([])
const currentAcademicYear = ref(null)

const form = ref({
  code: '',
  name: '',
  grade: null,
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

const loadAcademicYears = async () => {
  try {
    const response = await academicYearApi.getAll({ per_page: 100 })
    academicYearsList.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load academic years:', err)
  }
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
    toast.error('Gagal', 'Gagal memuat data kelas')
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
    toast.error('Gagal', 'Gagal memuat data siswa')
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
    loadClasses() // Refresh class list to update count
  } catch (err) {
    const message = err.response?.data?.message || 'Gagal menghapus siswa dari kelas'
    toast.error('Gagal', message)
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
    toast.error('Gagal', 'Gagal memuat data siswa')
    console.error('Failed to load available students:', err)
  } finally {
    loadingStudents.value = false
  }
}

const addStudentsToClass = async () => {
  if (!selectedClass.value || selectedStudentIds.value.length === 0) {
    toast.error('Gagal', 'Pilih minimal satu siswa')
    return
  }

  saving.value = true
  error.value = ''

  try {
    await classApi.addStudents(selectedClass.value.id, selectedStudentIds.value)
    toast.success('Berhasil', 'Siswa berhasil ditambahkan ke kelas')
    closeAddStudentModal()
    loadClasses()
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan saat menambahkan siswa'
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      const firstError = Object.values(errors)[0]
      error.value = Array.isArray(firstError) ? firstError[0] : firstError
    }
    toast.error('Gagal', error.value)
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
    toast.error('Gagal', message)
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
      // Saat edit, academic_year_id tidak bisa diubah
      delete data.academic_year_id
      await classApi.update(editingClass.value.id, data)
      toast.success('Berhasil', 'Kelas berhasil diperbarui')
    } else {
      // Saat create, pastikan academic_year_id sudah di-set dari active_academic_year_id
      if (!data.academic_year_id && institution.value?.active_academic_year_id) {
        data.academic_year_id = institution.value.active_academic_year_id
      }
      await classApi.create(data)
      toast.success('Berhasil', 'Kelas berhasil ditambahkan')
    }

    closeModal()
    loadClasses()
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
  await loadAcademicYears()
  
  // Set academic_year_id otomatis dari active_academic_year_id institusi
  if (institution.value?.active_academic_year_id) {
    form.value.academic_year_id = institution.value.active_academic_year_id
  }
  
  await Promise.all([loadRooms(), loadTeachers(), loadClasses()])
})
</script>

<style scoped>
.class-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #1a202c;
}

.header-content p {
  font-size: 14px;
  color: #718096;
  margin: 0;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: #5568d3;
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
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  flex: 1;
  min-width: 200px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
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
  min-height: 200px;
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
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f7fafc;
}

.data-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #4a5568;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #2d3748;
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
  color: #48bb78;
}

.btn-add:hover {
  background: #f0fff4;
}

.btn-edit {
  color: #48bb78;
}

.btn-edit:hover {
  background: #f0fff4;
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
  color: #718096;
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
  border-color: #667eea;
  color: #667eea;
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
  border-color: #667eea;
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
</style>
