<template>
  <Layout>
    <div class="student-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Data Siswa</h2>
            <p>Kelola data siswa sekolah Anda</p>
          </div>
          <button @click="showAddModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Siswa</span>
          </button>
        </div>
      </div>

      <div class="filters">
        <input 
          v-model="filters.search" 
          @input="loadStudents" 
          placeholder="Cari nama, NIS, atau NISN..."
          class="search-input"
        />
        <select v-model="filters.class" @change="loadStudents" class="filter-select">
          <option value="">Semua Kelas</option>
          <option value="1">Kelas 1</option>
          <option value="2">Kelas 2</option>
          <option value="3">Kelas 3</option>
          <option value="4">Kelas 4</option>
          <option value="5">Kelas 5</option>
          <option value="6">Kelas 6</option>
        </select>
        <select v-model="filters.status" @change="loadStudents" class="filter-select">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Lulus">Lulus</option>
          <option value="Pindah">Pindah</option>
          <option value="Drop Out">Drop Out</option>
          <option value="Tidak Aktif">Tidak Aktif</option>
        </select>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
              <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
              <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
            </circle>
          </svg>
        </div>
        <p>Memuat data...</p>
      </div>
      
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>NIS</th>
              <th>NISN</th>
              <th>Nama</th>
              <th>Jenis Kelamin</th>
              <th>Kelas</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in students" :key="student.id">
              <td>{{ student.nis || '-' }}</td>
              <td>{{ student.nisn || '-' }}</td>
              <td>{{ student.name }}</td>
              <td>{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
              <td>{{ student.class || '-' }}</td>
              <td>
                <span :class="getStatusClass(student.status)">
                  {{ student.status }}
                </span>
              </td>
              <td>
                <button @click="editStudent(student)" class="btn-edit">Edit</button>
                <button @click="deleteStudent(student.id)" class="btn-delete">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="students.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada data siswa</h3>
          <p>Mulai dengan menambahkan siswa baru</p>
          <button @click="showAddModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Siswa</span>
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ showEditModal ? 'Edit' : 'Tambah' }} Siswa</h3>
            <button @click="closeModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleSubmit" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>NIS</label>
                <input v-model="form.nis" />
              </div>
              <div class="form-group">
                <label>NISN</label>
                <input v-model="form.nisn" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Lengkap *</label>
                <input v-model="form.name" required />
              </div>
              <div class="form-group">
                <label>Jenis Kelamin *</label>
                <select v-model="form.gender" required>
                  <option value="">Pilih</option>
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" v-model="form.birth_date" />
              </div>
              <div class="form-group">
                <label>Tempat Lahir</label>
                <input v-model="form.birth_place" />
              </div>
            </div>

            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="form.address" rows="3"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="form.email" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Agama</label>
                <input v-model="form.religion" />
              </div>
              <div class="form-group">
                <label>Kelas</label>
                <input v-model="form.class" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tahun Ajaran</label>
                <input v-model="form.academic_year" />
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status">
                  <option value="Aktif">Aktif</option>
                  <option value="Lulus">Lulus</option>
                  <option value="Pindah">Pindah</option>
                  <option value="Drop Out">Drop Out</option>
                  <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Ayah</label>
                <input v-model="form.father_name" />
              </div>
              <div class="form-group">
                <label>Nama Ibu</label>
                <input v-model="form.mother_name" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Wali</label>
                <input v-model="form.guardian_name" />
              </div>
              <div class="form-group">
                <label>Telepon Wali</label>
                <input v-model="form.guardian_phone" />
              </div>
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="form.notes" rows="3"></textarea>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { studentApi } from '@/api/student'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const students = ref([])
const loading = ref(true)
const showAddModal = ref(false)
const showEditModal = ref(false)
const saving = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  class: '',
  status: ''
})

const form = ref({
  nis: '',
  nisn: '',
  name: '',
  gender: '',
  birth_date: '',
  birth_place: '',
  address: '',
  phone: '',
  email: '',
  religion: '',
  class: '',
  academic_year: '',
  status: 'Aktif',
  father_name: '',
  mother_name: '',
  guardian_name: '',
  guardian_phone: '',
  notes: ''
})

let editingId = null

const loadStudents = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.class) params.class = filters.value.class
    if (filters.value.status) params.status = filters.value.status
    
    const response = await studentApi.getAll(params)
    students.value = response.data.data || []
  } catch (err) {
    error.value = 'Gagal memuat data siswa'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const editStudent = (student) => {
  editingId = student.id
  Object.assign(form.value, student)
  if (student.birth_date) {
    form.value.birth_date = student.birth_date.split('T')[0]
  }
  showEditModal.value = true
}

const deleteStudent = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus siswa ini?')) return
  
  try {
    await studentApi.delete(id)
    toast.success('Berhasil', 'Siswa berhasil dihapus')
    loadStudents()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus siswa')
  }
}

const validationRules = {
  name: [
    validators.required('Nama lengkap wajib diisi'),
    validators.maxLength(form.value.name, 255, 'Nama maksimal 255 karakter')
  ],
  gender: [
    validators.required('Jenis kelamin wajib diisi')
  ],
  email: [
    () => form.value.email ? validators.email('Format email tidak valid')(form.value.email) : null,
    () => form.value.email ? validators.maxLength(form.value.email, 255, 'Email maksimal 255 karakter')(form.value.email) : null
  ],
  phone: [
    () => form.value.phone ? validators.phone('Format nomor telepon tidak valid')(form.value.phone) : null
  ],
  birth_date: [
    () => form.value.birth_date ? validators.date('Format tanggal tidak valid')(form.value.birth_date) : null
  ]
}

const handleSubmit = async () => {
  error.value = ''
  
  // Validate form
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    error.value = 'Mohon perbaiki kesalahan pada form: ' + Object.values(validation.errors).join(', ')
    return
  }
  
  saving.value = true
  
  try {
    if (editingId) {
      await studentApi.update(editingId, form.value)
      toast.success('Berhasil', 'Data siswa berhasil diperbarui')
    } else {
      await studentApi.create(form.value)
      toast.success('Berhasil', 'Siswa berhasil ditambahkan')
    }
    closeModal()
    loadStudents()
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan data'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    saving.value = false
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingId = null
  form.value = {
    nis: '',
    nisn: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    address: '',
    phone: '',
    email: '',
    religion: '',
    class: '',
    academic_year: '',
    status: 'Aktif',
    father_name: '',
    mother_name: '',
    guardian_name: '',
    guardian_phone: '',
    notes: ''
  }
  error.value = ''
}

const getStatusClass = (status) => {
  const classes = {
    'Aktif': 'status-active',
    'Lulus': 'status-success',
    'Pindah': 'status-warning',
    'Drop Out': 'status-danger',
    'Tidak Aktif': 'status-inactive'
  }
  return classes[status] || ''
}

onMounted(() => {
  loadStudents()
})
</script>

<style scoped>
.student-page {
  max-width: 1400px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.search-input,
.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.filter-select {
  min-width: 180px;
}

.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.data-table th {
  padding: 16px 20px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.status-active {
  color: #27ae60;
  font-weight: 600;
}

.status-success {
  color: #3498db;
  font-weight: 600;
}

.status-warning {
  color: #f39c12;
  font-weight: 600;
}

.status-danger {
  color: #e74c3c;
  font-weight: 600;
}

.status-inactive {
  color: #95a5a6;
  font-weight: 600;
}

.btn-edit,
.btn-delete {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  margin-right: 8px;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-edit {
  background: #3b82f6;
  color: white;
}

.btn-edit:hover {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 8px;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 24px 0;
}

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
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 30px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #999;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 30px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 8px;
  color: #333;
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  padding: 12px 24px;
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
}

.error-message {
  padding: 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid #fecaca;
  font-size: 14px;
}

.loading-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.loading-spinner {
  color: #667eea;
}

.loading-state p {
  font-size: 16px;
  font-weight: 500;
  margin: 0;
}
</style>
