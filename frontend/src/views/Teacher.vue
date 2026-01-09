<template>
  <Layout>
    <div class="teacher-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Data Guru</h2>
            <p>Kelola data guru sekolah Anda</p>
          </div>
          <button @click="showAddModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Guru</span>
          </button>
        </div>
      </div>

      <div class="filters">
        <input 
          v-model="filters.search" 
          @input="loadTeachers" 
          placeholder="Cari nama, NIP, atau NUPTK..."
          class="search-input"
        />
        <select v-model="filters.status" @change="loadTeachers" class="filter-select">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Pensiun">Pensiun</option>
          <option value="Pindah">Pindah</option>
          <option value="Tidak Aktif">Tidak Aktif</option>
        </select>
        <select v-model="filters.employment_status" @change="loadTeachers" class="filter-select">
          <option value="">Semua Status Kepegawaian</option>
          <option value="PNS">PNS</option>
          <option value="CPNS">CPNS</option>
          <option value="Guru Tetap Yayasan">Guru Tetap Yayasan</option>
          <option value="Guru Honor Sekolah">Guru Honor Sekolah</option>
          <option value="Guru Kontrak">Guru Kontrak</option>
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
              <th>NIP</th>
              <th>NUPTK</th>
              <th>Nama</th>
              <th>Jenis Kelamin</th>
              <th>Status Kepegawaian</th>
              <th>Mata Pelajaran</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="teacher in teachers" :key="teacher.id">
              <td>{{ teacher.nip || '-' }}</td>
              <td>{{ teacher.nuptk || '-' }}</td>
              <td>{{ teacher.name }}</td>
              <td>{{ teacher.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
              <td>{{ teacher.employment_status || '-' }}</td>
              <td>{{ teacher.subject || '-' }}</td>
              <td>
                <span :class="getStatusClass(teacher.status)">
                  {{ teacher.status }}
                </span>
              </td>
              <td>
                <button @click="editTeacher(teacher)" class="btn-edit">Edit</button>
                <button @click="deleteTeacher(teacher.id)" class="btn-delete">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="teachers.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada data guru</h3>
          <p>Mulai dengan menambahkan guru baru</p>
          <button @click="showAddModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Guru</span>
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ showEditModal ? 'Edit' : 'Tambah' }} Guru</h3>
            <button @click="closeModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleSubmit" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>NIP</label>
                <input v-model="form.nip" />
              </div>
              <div class="form-group">
                <label>NUPTK</label>
                <input v-model="form.nuptk" />
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
                <label>Status Kepegawaian</label>
                <select v-model="form.employment_status">
                  <option value="">Pilih</option>
                  <option value="PNS">PNS</option>
                  <option value="CPNS">CPNS</option>
                  <option value="Guru Tetap Yayasan">Guru Tetap Yayasan</option>
                  <option value="Guru Honor Sekolah">Guru Honor Sekolah</option>
                  <option value="Guru Kontrak">Guru Kontrak</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Pendidikan Terakhir</label>
                <select v-model="form.education_level">
                  <option value="">Pilih</option>
                  <option value="SMA">SMA</option>
                  <option value="D3">D3</option>
                  <option value="S1">S1</option>
                  <option value="S2">S2</option>
                  <option value="S3">S3</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jurusan</label>
                <input v-model="form.major" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Mata Pelajaran</label>
                <input v-model="form.subject" />
              </div>
              <div class="form-group">
                <label>Tanggal Bergabung</label>
                <input type="date" v-model="form.join_date" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.status">
                  <option value="Aktif">Aktif</option>
                  <option value="Pensiun">Pensiun</option>
                  <option value="Pindah">Pindah</option>
                  <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
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
import { teacherApi } from '@/api/teacher'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const teachers = ref([])
const loading = ref(true)
const showAddModal = ref(false)
const showEditModal = ref(false)
const saving = ref(false)
const error = ref('')

const filters = ref({
  search: '',
  status: '',
  employment_status: ''
})

const form = ref({
  nip: '',
  nuptk: '',
  name: '',
  gender: '',
  birth_date: '',
  birth_place: '',
  address: '',
  phone: '',
  email: '',
  religion: '',
  employment_status: '',
  education_level: '',
  major: '',
  subject: '',
  status: 'Aktif',
  join_date: '',
  notes: ''
})

let editingId = null

const loadTeachers = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.employment_status) params.employment_status = filters.value.employment_status
    
    const response = await teacherApi.getAll(params)
    teachers.value = response.data.data || []
  } catch (err) {
    error.value = 'Gagal memuat data guru'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const editTeacher = (teacher) => {
  editingId = teacher.id
  Object.assign(form.value, teacher)
  if (teacher.birth_date) {
    form.value.birth_date = teacher.birth_date.split('T')[0]
  }
  if (teacher.join_date) {
    form.value.join_date = teacher.join_date.split('T')[0]
  }
  showEditModal.value = true
}

const deleteTeacher = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus guru ini?')) return
  
  try {
    await teacherApi.delete(id)
    toast.success('Berhasil', 'Guru berhasil dihapus')
    loadTeachers()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus guru')
  }
}

const validationRules = {
  name: [
    (value) => validators.required(value, 'Nama lengkap wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama maksimal 255 karakter')
  ],
  gender: [
    (value) => validators.required(value, 'Jenis kelamin wajib diisi')
  ],
  email: [
    (value) => validators.email(value, 'Format email tidak valid'),
    (value) => validators.maxLength(value, 255, 'Email maksimal 255 karakter')
  ],
  phone: [
    (value) => validators.phone(value, 'Format nomor telepon tidak valid')
  ],
  birth_date: [
    (value) => validators.date(value, 'Format tanggal tidak valid')
  ],
  join_date: [
    (value) => validators.date(value, 'Format tanggal tidak valid')
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
      await teacherApi.update(editingId, form.value)
      toast.success('Berhasil', 'Data guru berhasil diperbarui')
    } else {
      await teacherApi.create(form.value)
      toast.success('Berhasil', 'Guru berhasil ditambahkan')
    }
    closeModal()
    loadTeachers()
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
    nip: '',
    nuptk: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    address: '',
    phone: '',
    email: '',
    religion: '',
    employment_status: '',
    education_level: '',
    major: '',
    subject: '',
    status: 'Aktif',
    join_date: '',
    notes: ''
  }
  error.value = ''
}

const getStatusClass = (status) => {
  const classes = {
    'Aktif': 'status-active',
    'Pensiun': 'status-success',
    'Pindah': 'status-warning',
    'Tidak Aktif': 'status-inactive'
  }
  return classes[status] || ''
}

onMounted(() => {
  loadTeachers()
})
</script>

<style scoped>
.teacher-page {
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
  padding: 10px 20px;
  background: #e0e0e0;
  color: #333;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #d0d0d0;
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
