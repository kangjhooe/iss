<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Permintaan Perubahan Data</h1>
        <p class="page-subtitle">Ajukan perubahan data diri yang perlu disetujui operator sekolah</p>
      </div>

      <div v-if="!profile" class="alert alert-warning">
        Data profil siswa belum dilengkapi. Hubungi operator sekolah untuk menghubungkan akun dengan data siswa.
      </div>

      <template v-else>
        <!-- Form ajukan permintaan -->
        <section class="section card">
          <h2 class="section-title">Ajukan Perubahan</h2>
          <form @submit.prevent="submitRequest" class="form">
            <div class="form-group">
              <label>Field yang ingin diubah *</label>
              <select v-model="form.field_name" required>
                <option value="">-- Pilih field --</option>
                <option v-for="f in allowedFields" :key="f" :value="f">{{ getFieldLabel(f) }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nilai baru *</label>
              <input v-model="form.new_value" type="text" :placeholder="'Masukkan ' + getFieldLabel(form.field_name)" maxlength="500" />
            </div>
            <p v-if="submitError" class="error-msg">{{ submitError }}</p>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Mengirim...' : 'Kirim Permintaan' }}
            </button>
          </form>
        </section>

        <!-- Daftar permintaan saya -->
        <section class="section">
          <h2 class="section-title">Riwayat Permintaan Saya</h2>
          <div v-if="loading" class="loading-wrap">Memuat...</div>
          <div v-else-if="requests.length === 0" class="empty-state">
            Belum ada permintaan. Gunakan form di atas untuk mengajukan perubahan data.
          </div>
          <div v-else class="requests-list">
            <div v-for="req in requests" :key="req.id" class="request-card">
              <div class="request-head">
                <span class="field-name">{{ getFieldLabel(req.field_name) }}</span>
                <span :class="['status-badge', `status-${req.status}`]">{{ getStatusLabel(req.status) }}</span>
              </div>
              <div class="request-details">
                <div class="row"><span class="label">Nilai lama:</span> {{ req.old_value || '-' }}</div>
                <div class="row"><span class="label">Nilai baru:</span> {{ req.new_value || '-' }}</div>
                <div class="row"><span class="label">Tanggal:</span> {{ formatDate(req.created_at) }}</div>
                <div v-if="req.rejection_reason" class="row rejection">
                  <span class="label">Alasan ditolak:</span> {{ req.rejection_reason }}
                </div>
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { studentChangeRequestApi } from '@/api/studentChangeRequest'

const authStore = useAuthStore()
const toast = useToast()
const profile = computed(() => authStore.user?.student_profile)

const allowedFields = ref([])
const requests = ref([])
const loading = ref(true)
const submitting = ref(false)
const submitError = ref('')

const form = ref({
  field_name: '',
  new_value: ''
})

const FIELD_LABELS = {
  address: 'Alamat',
  phone: 'No. HP',
  religion: 'Agama',
  no_kk: 'No. KK',
  aspiration: 'Cita-cita',
  hobby: 'Hobi',
  disability: 'Disabilitas',
  height: 'Tinggi Badan (cm)',
  weight: 'Berat Badan (kg)',
  previous_school: 'Sekolah Asal',
  residence_type: 'Jenis Tempat Tinggal',
  father_name: 'Nama Ayah',
  father_status: 'Status Ayah',
  father_nik: 'NIK Ayah',
  father_birth_place: 'Tempat Lahir Ayah',
  father_birth_date: 'Tanggal Lahir Ayah',
  father_education: 'Pendidikan Ayah',
  father_occupation: 'Pekerjaan Ayah',
  father_income: 'Penghasilan Ayah',
  mother_name: 'Nama Ibu',
  mother_status: 'Status Ibu',
  mother_nik: 'NIK Ibu',
  mother_birth_place: 'Tempat Lahir Ibu',
  mother_birth_date: 'Tanggal Lahir Ibu',
  mother_education: 'Pendidikan Ibu',
  mother_occupation: 'Pekerjaan Ibu',
  mother_income: 'Penghasilan Ibu',
  guardian_name: 'Nama Wali',
  guardian_phone: 'No. HP Wali',
  guardian_type: 'Jenis Wali',
  guardian_status: 'Status Wali',
  guardian_nik: 'NIK Wali',
  guardian_birth_place: 'Tempat Lahir Wali',
  guardian_birth_date: 'Tanggal Lahir Wali',
  guardian_education: 'Pendidikan Wali',
  guardian_occupation: 'Pekerjaan Wali',
  guardian_income: 'Penghasilan Wali',
  notes: 'Catatan'
}

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function getStatusLabel(status) {
  const map = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
  return map[status] || status
}

function formatDate(s) {
  if (!s) return '-'
  return new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadAllowedFields() {
  try {
    const res = await studentChangeRequestApi.getAllowedFields()
    allowedFields.value = res.data?.data || []
  } catch {
    allowedFields.value = []
  }
}

async function loadRequests() {
  if (!profile.value?.id) return
  loading.value = true
  try {
    const res = await studentChangeRequestApi.getAll()
    requests.value = res.data?.data || []
  } catch {
    toast.error('Gagal', 'Gagal memuat riwayat permintaan')
    requests.value = []
  } finally {
    loading.value = false
  }
}

async function submitRequest() {
  if (!profile.value?.id) return
  submitError.value = ''
  if (!form.value.field_name || (form.value.new_value || '').trim() === '') {
    submitError.value = 'Pilih field dan isi nilai baru.'
    return
  }
  submitting.value = true
  try {
    await studentChangeRequestApi.create({
      student_id: profile.value.id,
      field_name: form.value.field_name,
      new_value: (form.value.new_value || '').trim()
    })
    toast.success('Berhasil', 'Permintaan telah dikirim. Menunggu persetujuan operator.')
    form.value = { field_name: '', new_value: '' }
    await loadRequests()
  } catch (err) {
    submitError.value = err.formattedMessage || err.response?.data?.message || 'Gagal mengirim permintaan'
    toast.error('Gagal', submitError.value)
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await authStore.fetchUser()
  await loadAllowedFields()
  await loadRequests()
})
</script>

<style scoped>
.page { max-width: 720px; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #64748b; margin: 0; }

.alert {
  padding: 14px 18px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
}

.section { margin-bottom: 32px; }
.section-title { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 16px 0; }
.card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px; }
.form-group select,
.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
}
.error-msg { color: #dc2626; font-size: 14px; margin-bottom: 12px; }
.btn-primary {
  padding: 10px 20px;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

.loading-wrap { padding: 24px; text-align: center; color: #64748b; }
.empty-state { padding: 24px; background: #f8fafc; border-radius: 12px; color: #64748b; font-size: 14px; }

.requests-list { display: flex; flex-direction: column; gap: 12px; }
.request-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}
.request-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.field-name { font-weight: 600; color: #1e293b; }
.status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-approved { background: #d1fae5; color: #059669; }
.status-rejected { background: #fee2e2; color: #dc2626; }
.request-details .row { font-size: 13px; margin-bottom: 4px; }
.request-details .label { color: #64748b; margin-right: 8px; }
.request-details .rejection { color: #dc2626; }
</style>
