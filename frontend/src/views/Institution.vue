<template>
  <Layout>
    <div class="institution-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Profil Instansi</h2>
            <p>Kelola informasi sekolah Anda</p>
          </div>
          <button @click="showEditModal = true" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Edit Profil</span>
          </button>
        </div>
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
      
      <div v-else-if="institution" class="institution-card">
        <div class="info-section">
          <h3>Informasi Umum</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Nama Sekolah</label>
              <p>{{ institution.name }}</p>
            </div>
            <div class="info-item">
              <label>NPSN</label>
              <p>{{ institution.npsn || '-' }}</p>
            </div>
            <div class="info-item">
              <label>NSS</label>
              <p>{{ institution.nss || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Jenjang</label>
              <p>{{ institution.level || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Jenis</label>
              <p>{{ institution.type }}</p>
            </div>
            <div class="info-item">
              <label>Status</label>
              <p :class="institution.is_active ? 'status-active' : 'status-inactive'">
                {{ institution.is_active ? 'Aktif' : 'Tidak Aktif' }}
              </p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Alamat</h3>
          <div class="info-grid">
            <div class="info-item full-width">
              <label>Alamat Lengkap</label>
              <p>{{ institution.address || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Desa/Kelurahan</label>
              <p>{{ institution.village || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Kecamatan</label>
              <p>{{ institution.sub_district || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Kabupaten/Kota</label>
              <p>{{ institution.district || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Provinsi</label>
              <p>{{ institution.province || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Kode Pos</label>
              <p>{{ institution.postal_code || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Kontak</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Telepon</label>
              <p>{{ institution.phone || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Email</label>
              <p>{{ institution.email || '-' }}</p>
            </div>
            <div class="info-item">
              <label>Website</label>
              <p>{{ institution.website || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Kepala Sekolah</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Nama</label>
              <p>{{ institution.principal_name || '-' }}</p>
            </div>
            <div class="info-item">
              <label>NIP</label>
              <p>{{ institution.principal_nip || '-' }}</p>
            </div>
          </div>
        </div>

        <div v-if="institution.description" class="info-section">
          <h3>Deskripsi</h3>
          <p>{{ institution.description }}</p>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="modal-overlay" @click="showEditModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Edit Profil Instansi</h3>
            <button @click="showEditModal = false" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleUpdate" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Sekolah *</label>
                <input v-model="form.name" required />
              </div>
              <div class="form-group">
                <label>NPSN</label>
                <input v-model="form.npsn" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>NSS</label>
                <input v-model="form.nss" />
              </div>
              <div class="form-group">
                <label>Jenjang</label>
                <select v-model="form.level">
                  <option value="">Pilih Jenjang</option>
                  <option value="TK">TK</option>
                  <option value="SD">SD</option>
                  <option value="SMP">SMP</option>
                  <option value="SMA">SMA</option>
                  <option value="SMK">SMK</option>
                  <option value="MA">MA</option>
                  <option value="MTs">MTs</option>
                  <option value="MI">MI</option>
                  <option value="PAUD">PAUD</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenis *</label>
                <select v-model="form.type" required>
                  <option value="Swasta">Swasta</option>
                  <option value="Negeri">Negeri</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="form.address" rows="3"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Desa/Kelurahan</label>
                <input v-model="form.village" />
              </div>
              <div class="form-group">
                <label>Kecamatan</label>
                <input v-model="form.sub_district" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kabupaten/Kota</label>
                <input v-model="form.district" />
              </div>
              <div class="form-group">
                <label>Provinsi</label>
                <input v-model="form.province" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kode Pos</label>
                <input v-model="form.postal_code" />
              </div>
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="form.email" />
              </div>
              <div class="form-group">
                <label>Website</label>
                <input v-model="form.website" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Kepala Sekolah</label>
                <input v-model="form.principal_name" />
              </div>
              <div class="form-group">
                <label>NIP Kepala Sekolah</label>
                <input v-model="form.principal_nip" />
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="4"></textarea>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="modal-footer">
              <button type="button" @click="showEditModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="updating" class="btn-primary">
                {{ updating ? 'Menyimpan...' : 'Simpan' }}
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
import { institutionApi } from '@/api/institution'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const institution = ref(null)
const loading = ref(true)
const showEditModal = ref(false)
const updating = ref(false)
const error = ref('')

const form = ref({
  name: '',
  npsn: '',
  nss: '',
  level: '',
  type: 'Swasta',
  address: '',
  village: '',
  sub_district: '',
  district: '',
  province: '',
  postal_code: '',
  phone: '',
  email: '',
  website: '',
  principal_name: '',
  principal_nip: '',
  description: ''
})

const loadInstitution = async () => {
  loading.value = true
  try {
    const response = await institutionApi.getMy()
    institution.value = response.data
    Object.assign(form.value, response.data)
  } catch (err) {
    error.value = 'Gagal memuat data institusi'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const validationRules = {
  name: [
    validators.required('Nama sekolah wajib diisi'),
    validators.maxLength(form.value.name, 255, 'Nama sekolah maksimal 255 karakter')
  ],
  npsn: [
    () => form.value.npsn ? validators.npsn('NPSN harus terdiri dari 8 digit angka')(form.value.npsn) : null
  ],
  type: [
    validators.required('Jenis institusi wajib diisi')
  ],
  email: [
    () => form.value.email ? validators.email('Format email tidak valid')(form.value.email) : null,
    () => form.value.email ? validators.maxLength(form.value.email, 255, 'Email maksimal 255 karakter')(form.value.email) : null
  ],
  website: [
    () => form.value.website ? validators.url('Format URL tidak valid')(form.value.website) : null
  ],
  phone: [
    () => form.value.phone ? validators.phone('Format nomor telepon tidak valid')(form.value.phone) : null,
    () => form.value.phone ? validators.maxLength(form.value.phone, 20, 'Nomor telepon maksimal 20 karakter')(form.value.phone) : null
  ]
}

const handleUpdate = async () => {
  error.value = ''
  
  // Validate form
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    error.value = 'Mohon perbaiki kesalahan pada form: ' + Object.values(validation.errors).join(', ')
    return
  }
  
  updating.value = true
  
  try {
    const response = await institutionApi.update(institution.value.id, form.value)
    institution.value = response.data.data
    showEditModal.value = false
    toast.success('Berhasil', 'Profil institusi berhasil diperbarui')
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal memperbarui data'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    updating.value = false
  }
}

onMounted(() => {
  loadInstitution()
})
</script>

<style scoped>
.institution-page {
  max-width: 1200px;
}

.page-header {
  margin-bottom: 32px;
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

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.institution-card {
  background: white;
  border-radius: 20px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.info-section {
  margin-bottom: 32px;
  padding-bottom: 32px;
  border-bottom: 1px solid #e2e8f0;
}

.info-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.info-section h3 {
  color: #1e293b;
  margin-bottom: 24px;
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-section h3::before {
  content: '';
  width: 4px;
  height: 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item p {
  color: #1e293b;
  font-size: 15px;
  font-weight: 500;
  margin: 0;
  word-break: break-word;
}

.status-active {
  color: #27ae60;
  font-weight: 600;
}

.status-inactive {
  color: #e74c3c;
  font-weight: 600;
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
  padding: 24px 32px;
  border-bottom: 1px solid #e2e8f0;
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
  padding: 32px;
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
  display: flex;
  align-items: center;
  gap: 12px;
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
