<template>
  <Layout>
    <div class="page">
      <div v-if="profileError" class="alert alert-warning">
        {{ profileError }}
      </div>

      <template v-else>
        <!-- Data profil (read-only) -->
        <section class="section card">
          <h2 class="section-title">Data Saat Ini</h2>
          <div v-if="loadingProfile" class="loading-wrap">Memuat profil...</div>
          <dl v-else-if="teacher" class="profile-list">
            <div class="profile-row">
              <dt>Nama</dt>
              <dd>{{ teacher.name || '-' }}</dd>
            </div>
            <div class="profile-row">
              <dt>NIP</dt>
              <dd>{{ teacher.nip || '-' }}</dd>
            </div>
            <div class="profile-row">
              <dt>NUPTK</dt>
              <dd>{{ teacher.nuptk || '-' }}</dd>
            </div>
            <div class="profile-row">
              <dt>Tanggal Bergabung</dt>
              <dd>{{ formatProfileValue(teacher.join_date, 'join_date') }}</dd>
            </div>
            <div v-for="f in allowedFields" :key="f" class="profile-row">
              <dt>{{ getFieldLabel(f) }}</dt>
              <dd>{{ formatProfileValue(teacher[f], f) }}</dd>
            </div>
          </dl>
        </section>

        <!-- Form ajukan permintaan -->
        <section class="section card">
          <h2 class="section-title">Ajukan Perubahan / Lengkapi Data</h2>
          <p class="section-desc">Pilih field dan isi nilai baru. Perubahan akan berlaku setelah disetujui admin.</p>
          <p class="form-hint">Satu permintaan per field. Jika sudah ada permintaan menunggu untuk field yang sama, tunggu persetujuan atau penolakan terlebih dahulu.</p>
          <form @submit.prevent="submitRequest" class="form">
            <div class="form-group">
              <label>Field yang ingin diubah *</label>
              <select v-model="form.field_name" required>
                <option value="">-- Pilih field --</option>
                <option v-for="f in allowedFields" :key="f" :value="f">{{ getFieldLabel(f) }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nilai baru {{ isDateField(form.field_name) ? '(opsional)' : '(kosongkan untuk mengosongkan field)' }}</label>
              <input
                v-if="isDateField(form.field_name)"
                v-model="form.new_value"
                type="date"
                :placeholder="'Masukkan ' + getFieldLabel(form.field_name)"
              />
              <input
                v-else-if="form.field_name === 'email'"
                v-model="form.new_value"
                type="email"
                placeholder="contoh@email.com"
                maxlength="255"
              />
              <input
                v-else
                v-model="form.new_value"
                type="text"
                :placeholder="'Masukkan ' + getFieldLabel(form.field_name)"
                maxlength="500"
              />
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

      <router-link to="/teacher/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { useToast } from '@/composables/useToast'
import { teacherApi } from '@/api/teacher'
import { teacherChangeRequestApi } from '@/api/teacherChangeRequest'

const toast = useToast()

const teacher = ref(null)
const profileError = ref('')
const loadingProfile = ref(true)
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
  email: 'Email',
  religion: 'Agama',
  birth_place: 'Tempat Lahir',
  birth_date: 'Tanggal Lahir',
  education_level: 'Pendidikan',
  major: 'Jurusan',
  subject: 'Mata Pelajaran',
  notes: 'Catatan',
  certification_status: 'Status Sertifikasi',
  certification_date: 'Tanggal Sertifikasi',
  teacher_registration_number: 'Nomor Registrasi Guru',
  certification_number: 'Nomor Sertifikasi',
  certification_issuing_authority: 'Lembaga Penerbit Sertifikasi'
}

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function isDateField(field) {
  return field === 'birth_date' || field === 'certification_date'
}

function formatProfileValue(val, field) {
  if (val == null || val === '') return '-'
  const dateFields = ['birth_date', 'certification_date', 'join_date']
  if (dateFields.includes(field) && val) return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  return String(val)
}

function getStatusLabel(status) {
  const map = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
  return map[status] || status
}

function formatDate(s) {
  if (!s) return '-'
  return new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadProfile() {
  loadingProfile.value = true
  profileError.value = ''
  try {
    const res = await teacherApi.getDashboard()
    const data = res.data?.data
    teacher.value = data?.teacher || null
    if (!teacher.value) profileError.value = 'Profil guru tidak ditemukan.'
  } catch (err) {
    profileError.value = err.response?.data?.message || 'Gagal memuat profil.'
  } finally {
    loadingProfile.value = false
  }
}

async function loadAllowedFields() {
  try {
    const res = await teacherChangeRequestApi.getAllowedFields()
    allowedFields.value = res.data?.data || []
  } catch {
    allowedFields.value = []
  }
}

async function loadRequests() {
  loading.value = true
  try {
    const res = await teacherChangeRequestApi.getAll({ per_page: 50 })
    const body = res.data
    requests.value = Array.isArray(body?.data) ? body.data : []
  } catch {
    toast.error('Gagal', 'Gagal memuat riwayat permintaan')
    requests.value = []
  } finally {
    loading.value = false
  }
}

async function submitRequest() {
  submitError.value = ''
  if (!form.value.field_name) {
    submitError.value = 'Pilih field yang ingin diubah.'
    return
  }
  submitting.value = true
  try {
    await teacherChangeRequestApi.create({
      field_name: form.value.field_name,
      new_value: (form.value.new_value || '').trim() || null
    })
    toast.success('Berhasil', 'Permintaan telah dikirim. Menunggu persetujuan admin.')
    form.value = { field_name: '', new_value: '' }
    await loadRequests()
    await loadProfile()
  } catch (err) {
    submitError.value = err.response?.data?.message || 'Gagal mengirim permintaan'
    toast.error('Gagal', submitError.value)
  } finally {
    submitting.value = false
  }
}

watch(
  () => form.value.field_name,
  (field) => {
    if (!field) return
    if (teacher.value && isDateField(field) && teacher.value[field]) {
      const d = teacher.value[field]
      form.value.new_value = typeof d === 'string' ? d.slice(0, 10) : ''
    } else {
      form.value.new_value = ''
    }
  }
)

onMounted(async () => {
  await loadAllowedFields()
  await loadProfile()
  await loadRequests()
})
</script>

<style scoped>
.page { max-width: 720px; padding: 0; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }
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
.section-title { font-size: 18px; font-weight: 600; color: #1e293b; margin: 0 0 8px 0; }
.section-desc { font-size: 13px; color: #64748b; margin: 0 0 16px 0; }
.form-hint { font-size: 12px; color: #94a3b8; margin: -8px 0 16px 0; line-height: 1.4; }
.card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }

.profile-list { margin: 0; }
.profile-row { display: flex; flex-wrap: wrap; padding: 12px 0; border-bottom: 1px solid #e2e8f0; gap: 12px; }
.profile-row:last-of-type { border-bottom: none; }
.profile-row dt { font-size: 13px; font-weight: 600; color: #64748b; min-width: 140px; margin: 0; }
.profile-row dd { font-size: 14px; color: #0f172a; margin: 0; flex: 1; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px; }
.form-group select,
.form-group input {
  width: 100%;
  max-width: 400px;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
}
.error-msg { color: #dc2626; font-size: 14px; margin-bottom: 12px; }
.btn-primary {
  padding: 10px 20px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: box-shadow 0.2s;
}
.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
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

.back-link { display: inline-block; margin-top: 8px; color: #059669; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; color: #047857; }
</style>
