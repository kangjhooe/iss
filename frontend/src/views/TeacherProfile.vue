<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <div class="page-header-main">
          <router-link to="/teacher/dashboard" class="back-chip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Dashboard
          </router-link>
          <div>
            <h1>Profil Saya</h1>
            <p class="page-subtitle">Lihat data diri dan ajukan perubahan untuk disetujui admin</p>
          </div>
        </div>
        <div v-if="pendingCount" class="pending-chip">
          {{ pendingCount }} menunggu persetujuan
        </div>
      </div>

      <div v-if="profileError" class="alert alert-warning" role="alert">
        {{ profileError }}
      </div>

      <template v-else>
        <!-- Hero -->
        <section class="hero-card">
          <div class="hero-avatar" aria-hidden="true">{{ initials }}</div>
          <div class="hero-body">
            <div v-if="loadingProfile" class="hero-loading">Memuat profil...</div>
            <template v-else>
              <h2 class="hero-name">{{ teacher?.name || 'Guru' }}</h2>
              <p class="hero-meta">
                <span v-if="teacher?.subject">{{ teacher.subject }}</span>
                <span v-if="teacher?.subject && teacher?.institution?.name" class="hero-dot">·</span>
                <span v-if="teacher?.institution?.name">{{ teacher.institution.name }}</span>
              </p>
              <div class="hero-tags">
                <span v-if="teacher?.nip" class="hero-tag">NIP {{ teacher.nip }}</span>
                <span v-if="teacher?.nuptk" class="hero-tag">NUPTK {{ teacher.nuptk }}</span>
                <span v-if="teacher?.join_date" class="hero-tag">
                  Bergabung {{ formatProfileValue(teacher.join_date, 'join_date') }}
                </span>
              </div>
            </template>
          </div>
        </section>

        <div class="content-grid">
          <!-- Data profil -->
          <section class="panel">
            <div class="panel-header">
              <h2>Data Saat Ini</h2>
              <span class="panel-hint">Hanya tampilan</span>
            </div>

            <div v-if="loadingProfile" class="loading-wrap">Memuat profil...</div>

            <template v-else-if="teacher">
              <div v-for="group in profileGroups" :key="group.key" class="profile-group">
                <h3 class="group-title">{{ group.title }}</h3>
                <dl class="profile-grid">
                  <div v-for="item in group.items" :key="item.key" class="profile-item">
                    <dt>{{ item.label }}</dt>
                    <dd :class="{ empty: item.empty }">{{ item.value }}</dd>
                  </div>
                </dl>
              </div>
            </template>
          </section>

          <!-- Form + riwayat -->
          <div class="side-stack">
            <section class="panel">
              <div class="panel-header">
                <h2>Ajukan Perubahan</h2>
              </div>
              <p class="section-desc">
                Pilih field dan isi nilai baru. Perubahan berlaku setelah disetujui admin.
              </p>
              <p class="form-hint">
                Satu permintaan per field. Jika sudah ada permintaan menunggu untuk field yang sama, tunggu hasilnya dulu.
              </p>

              <form @submit.prevent="submitRequest" class="form">
                <div class="form-group">
                  <label for="field_name">Field yang ingin diubah *</label>
                  <select id="field_name" v-model="form.field_name" required>
                    <option value="">-- Pilih field --</option>
                    <option v-for="f in allowedFields" :key="f" :value="f">{{ getFieldLabel(f) }}</option>
                  </select>
                </div>

                <div v-if="form.field_name" class="current-value">
                  <span class="current-label">Nilai saat ini</span>
                  <span class="current-text">{{ formatProfileValue(teacher?.[form.field_name], form.field_name) }}</span>
                </div>

                <div class="form-group">
                  <label for="new_value">
                    Nilai baru
                    {{ isDateField(form.field_name) ? '(opsional)' : '(kosongkan untuk mengosongkan field)' }}
                  </label>
                  <input
                    v-if="isDateField(form.field_name)"
                    id="new_value"
                    v-model="form.new_value"
                    type="date"
                  />
                  <input
                    v-else-if="form.field_name === 'email'"
                    id="new_value"
                    v-model="form.new_value"
                    type="email"
                    placeholder="contoh@email.com"
                    maxlength="255"
                  />
                  <input
                    v-else
                    id="new_value"
                    v-model="form.new_value"
                    type="text"
                    :placeholder="form.field_name ? ('Masukkan ' + getFieldLabel(form.field_name)) : 'Pilih field terlebih dahulu'"
                    maxlength="500"
                    :disabled="!form.field_name"
                  />
                </div>

                <p v-if="submitError" class="error-msg">{{ submitError }}</p>

                <button type="submit" class="btn-primary" :disabled="submitting || !form.field_name">
                  {{ submitting ? 'Mengirim...' : 'Kirim Permintaan' }}
                </button>
              </form>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h2>Riwayat Permintaan</h2>
                <span v-if="!loading && requests.length" class="panel-hint">{{ requests.length }} total</span>
              </div>

              <div v-if="loading" class="loading-wrap">Memuat...</div>

              <div v-else-if="requests.length === 0" class="empty-state">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p>Belum ada permintaan. Gunakan form di atas untuk mengajukan perubahan data.</p>
              </div>

              <div v-else class="requests-list">
                <div
                  v-for="req in requests"
                  :key="req.id"
                  class="request-card"
                  :class="`request-${req.status}`"
                >
                  <div class="request-head">
                    <span class="field-name">{{ getFieldLabel(req.field_name) }}</span>
                    <span :class="['status-badge', `status-${req.status}`]">{{ getStatusLabel(req.status) }}</span>
                  </div>
                  <div class="request-values">
                    <div class="value-block">
                      <span class="value-label">Lama</span>
                      <span class="value-text">{{ req.old_value || '-' }}</span>
                    </div>
                    <div class="value-arrow" aria-hidden="true">→</div>
                    <div class="value-block">
                      <span class="value-label">Baru</span>
                      <span class="value-text new">{{ req.new_value || '-' }}</span>
                    </div>
                  </div>
                  <div class="request-foot">
                    <span>{{ formatDate(req.created_at) }}</span>
                  </div>
                  <div v-if="req.rejection_reason" class="rejection-box">
                    <strong>Alasan ditolak:</strong> {{ req.rejection_reason }}
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
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

const CORE_FIELDS = ['name', 'nip', 'nuptk', 'join_date']

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function isDateField(field) {
  return field === 'birth_date' || field === 'certification_date'
}

function formatProfileValue(val, field) {
  if (val == null || val === '') return '-'
  const dateFields = ['birth_date', 'certification_date', 'join_date']
  if (dateFields.includes(field) && val) {
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  }
  return String(val)
}

function getStatusLabel(status) {
  const map = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
  return map[status] || status
}

function formatDate(s) {
  if (!s) return '-'
  return new Date(s).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const initials = computed(() => {
  const name = teacher.value?.name || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'G'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const pendingCount = computed(() => requests.value.filter((r) => r.status === 'pending').length)

function makeItem(key, label) {
  const raw = teacher.value?.[key]
  const value = formatProfileValue(raw, key)
  return { key, label, value, empty: value === '-' }
}

const profileGroups = computed(() => {
  if (!teacher.value) return []

  const identity = [
    makeItem('name', 'Nama'),
    makeItem('nip', 'NIP'),
    makeItem('nuptk', 'NUPTK'),
    makeItem('join_date', 'Tanggal Bergabung'),
  ]

  const contactKeys = ['phone', 'email', 'address', 'religion', 'birth_place', 'birth_date']
  const eduKeys = ['education_level', 'major', 'subject', 'notes']
  const certKeys = [
    'certification_status',
    'certification_date',
    'teacher_registration_number',
    'certification_number',
    'certification_issuing_authority',
  ]

  const allowed = allowedFields.value || []
  const extraKeys = allowed.filter((f) => !CORE_FIELDS.includes(f))

  const pick = (keys) =>
    keys
      .filter((k) => extraKeys.includes(k) || teacher.value?.[k])
      .map((k) => makeItem(k, getFieldLabel(k)))

  const groups = [
    { key: 'identity', title: 'Identitas', items: identity },
  ]

  const contact = pick(contactKeys)
  if (contact.length) groups.push({ key: 'contact', title: 'Kontak & Pribadi', items: contact })

  const edu = pick(eduKeys)
  if (edu.length) groups.push({ key: 'edu', title: 'Pendidikan & Mengajar', items: edu })

  const cert = pick(certKeys)
  if (cert.length) groups.push({ key: 'cert', title: 'Sertifikasi', items: cert })

  const used = new Set([...CORE_FIELDS, ...contactKeys, ...eduKeys, ...certKeys])
  const other = extraKeys.filter((k) => !used.has(k)).map((k) => makeItem(k, getFieldLabel(k)))
  if (other.length) groups.push({ key: 'other', title: 'Lainnya', items: other })

  return groups
})

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
.page {
  width: 100%;
  max-width: 100%;
  padding: 0 0 8px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.page-header-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.back-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: fit-content;
  padding: 6px 10px;
  border-radius: 8px;
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  color: #047857;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s;
}

.back-chip:hover {
  background: #d1fae5;
}

.page-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px;
  letter-spacing: -0.3px;
}

.page-subtitle {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.pending-chip {
  font-size: 12px;
  font-weight: 600;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  padding: 6px 12px;
  border-radius: 999px;
  white-space: nowrap;
}

.alert {
  padding: 14px 18px;
  border-radius: 12px;
  margin-bottom: 16px;
  font-size: 14px;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
}

.hero-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  margin-bottom: 16px;
  border-radius: 16px;
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}

.hero-avatar {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
  flex-shrink: 0;
  letter-spacing: 0.02em;
}

.hero-body {
  min-width: 0;
  flex: 1;
}

.hero-loading {
  opacity: 0.85;
  font-style: italic;
  font-size: 14px;
}

.hero-name {
  margin: 0 0 4px;
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.3px;
}

.hero-meta {
  margin: 0 0 10px;
  font-size: 13px;
  opacity: 0.92;
  font-weight: 500;
}

.hero-dot {
  margin: 0 6px;
  opacity: 0.7;
}

.hero-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.hero-tag {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.22);
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
  gap: 16px;
  align-items: start;
}

.side-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.panel {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  padding: 20px 22px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 14px;
}

.panel-header h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.2px;
}

.panel-hint {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 999px;
}

.section-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 8px;
  line-height: 1.45;
}

.form-hint {
  font-size: 12px;
  color: #94a3b8;
  margin: 0 0 16px;
  line-height: 1.4;
}

.profile-group + .profile-group {
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #f1f5f9;
}

.group-title {
  margin: 0 0 10px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}

.profile-grid {
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.profile-item {
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  padding: 10px 12px;
}

.profile-item dt {
  margin: 0 0 4px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.profile-item dd {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
  word-break: break-word;
  line-height: 1.35;
}

.profile-item dd.empty {
  color: #94a3b8;
  font-weight: 500;
}

.form-group {
  margin-bottom: 14px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.form-group select,
.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  font-size: 14px;
  background: #fff;
  color: #0f172a;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-group select:focus,
.form-group input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.form-group input:disabled {
  background: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.current-value {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin: -4px 0 14px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.current-label {
  font-size: 11px;
  font-weight: 700;
  color: #047857;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.current-text {
  font-size: 13px;
  font-weight: 600;
  color: #065f46;
  word-break: break-word;
}

.error-msg {
  color: #dc2626;
  font-size: 13px;
  margin: 0 0 12px;
  font-weight: 500;
}

.btn-primary {
  width: 100%;
  padding: 11px 16px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: box-shadow 0.2s, filter 0.15s;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
  filter: brightness(1.02);
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.loading-wrap {
  padding: 24px;
  text-align: center;
  color: #64748b;
  font-size: 14px;
}

.empty-state {
  padding: 28px 16px;
  text-align: center;
  color: #94a3b8;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed #e2e8f0;
}

.empty-state svg {
  margin-bottom: 8px;
  opacity: 0.7;
}

.empty-state p {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
}

.requests-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 480px;
  overflow: auto;
}

.request-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
  border-left: 4px solid #cbd5e1;
}

.request-pending { border-left-color: #f59e0b; background: #fffbeb; }
.request-approved { border-left-color: #10b981; background: #f0fdf4; }
.request-rejected { border-left-color: #ef4444; background: #fef2f2; }

.request-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.field-name {
  font-weight: 700;
  color: #0f172a;
  font-size: 14px;
}

.status-badge {
  padding: 3px 9px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.status-pending { background: #fef3c7; color: #b45309; }
.status-approved { background: #d1fae5; color: #047857; }
.status-rejected { background: #fee2e2; color: #b91c1c; }

.request-values {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 8px;
  align-items: start;
  margin-bottom: 8px;
}

.value-block {
  min-width: 0;
}

.value-label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
  margin-bottom: 2px;
}

.value-text {
  font-size: 13px;
  color: #334155;
  word-break: break-word;
  line-height: 1.35;
}

.value-text.new {
  font-weight: 600;
  color: #0f172a;
}

.value-arrow {
  color: #94a3b8;
  font-size: 14px;
  padding-top: 14px;
}

.request-foot {
  font-size: 12px;
  color: #94a3b8;
}

.rejection-box {
  margin-top: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #fecaca;
  color: #b91c1c;
  font-size: 12px;
  line-height: 1.4;
}

@media (max-width: 960px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .requests-list {
    max-height: none;
  }
}

@media (max-width: 640px) {
  .hero-card {
    align-items: flex-start;
  }

  .hero-name {
    font-size: 18px;
  }

  .profile-grid {
    grid-template-columns: 1fr;
  }

  .panel {
    padding: 16px;
  }

  .request-values {
    grid-template-columns: 1fr;
  }

  .value-arrow {
    display: none;
  }

  .page-header h1 {
    font-size: 20px;
  }
}
</style>
