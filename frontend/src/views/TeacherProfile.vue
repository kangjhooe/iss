<template>    <div class="page">
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
            <p class="page-subtitle">
              Data non-kunci bisa diubah langsung. Data kunci tetap bisa diajukan, menunggu persetujuan admin.
            </p>
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
        <section class="hero-card">
          <div v-if="loadingProfile" class="hero-loading">Memuat profil...</div>
          <template v-else>
              <div class="hero-top">
                <label class="hero-avatar-wrap" title="Unggah foto (JPG/PNG, maks. 1 MB)">
                  <img v-if="teacher?.photo_url" :src="teacher.photo_url" class="hero-avatar hero-avatar-img" :alt="teacher?.name || 'Foto'" />
                  <div v-else class="hero-avatar" aria-hidden="true">{{ initials }}</div>
                  <input type="file" :accept="PROFILE_PHOTO_ACCEPT" class="sr-only" :disabled="photoUploading" @change="onMyPhotoSelect" />
                  <span class="hero-avatar-hint">{{ photoUploading ? 'Mengunggah…' : 'Foto' }}</span>
                </label>
                <div>
                  <h2 class="hero-name">{{ teacher?.name || 'Guru' }}</h2>
                  <p class="hero-meta">
                    <span v-if="teacher?.subject">{{ teacher.subject }}</span>
                    <span v-if="teacher?.subject && teacher?.institution?.name" class="hero-dot">·</span>
                    <span v-if="teacher?.institution?.name">{{ teacher.institution.name }}</span>
                  </p>
                  <button
                    v-if="teacher?.photo_url"
                    type="button"
                    class="hero-photo-remove"
                    :disabled="photoUploading"
                    @click="removeMyPhoto"
                  >Hapus foto</button>
                </div>
              </div>
              <div v-if="teacher?.nip || teacher?.nuptk || teacher?.nik || teacher?.join_date" class="meta-grid">
                <div v-if="teacher?.nip" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">NIP</span>
                    <span class="meta-value">{{ teacher.nip }}</span>
                  </div>
                </div>
                <div v-if="teacher?.nuptk" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">NUPTK</span>
                    <span class="meta-value">{{ teacher.nuptk }}</span>
                  </div>
                </div>
                <div v-if="teacher?.nik" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18" stroke="currentColor" stroke-width="2"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">NIK</span>
                    <span class="meta-value">{{ teacher.nik }}</span>
                  </div>
                </div>
                <div v-if="teacher?.join_date" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">Bergabung</span>
                    <span class="meta-value">{{ formatProfileValue(teacher.join_date, 'join_date') }}</span>
                  </div>
                </div>
              </div>
            </template>
        </section>

        <div class="content-grid">
          <div class="main-stack">
            <section class="panel">
              <div class="panel-header">
                <h2>Data Saat Ini</h2>
                <span class="panel-hint panel-hint-icons" title="Gembok tertutup = butuh approval · Gembok terbuka = edit langsung">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  approval
                  <span class="hint-sep">·</span>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M8 11V8a4 4 0 0 1 7.2-2.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  langsung
                </span>
              </div>

              <div v-if="loadingProfile" class="loading-wrap">Memuat profil...</div>

              <template v-else-if="teacher">
                <div v-for="group in profileGroups" :key="group.key" class="profile-group">
                  <h3 class="group-title">{{ group.title }}</h3>
                  <dl class="profile-grid">
                    <div v-for="item in group.items" :key="item.key" class="profile-item">
                      <dt>
                        {{ item.label }}
                        <span
                          v-if="item.needsApproval"
                          class="field-badge key"
                          title="Data kunci — perubahan butuh persetujuan admin"
                          aria-label="Data kunci"
                        >
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </span>
                        <span
                          v-else
                          class="field-badge free"
                          title="Bisa diubah langsung tanpa persetujuan"
                          aria-label="Bisa diedit langsung"
                        >
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 11V8a4 4 0 0 1 7.2-2.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </span>
                      </dt>
                      <dd :class="{ empty: item.empty }">{{ item.value }}</dd>
                    </div>
                  </dl>
                </div>
              </template>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h2>Edit Langsung</h2>
                <span class="panel-hint">Tanpa persetujuan</span>
              </div>
              <p class="section-desc">
                Ubah alamat, kontak, pendidikan, dan catatan. Perubahan langsung tersimpan.
              </p>

              <form @submit.prevent="saveSelfEdit" class="form">
                <div class="form-grid">
                  <div class="form-group">
                    <label for="self_phone">No. HP</label>
                    <input id="self_phone" v-model="selfForm.phone" type="text" maxlength="20" placeholder="08..." />
                  </div>
                  <div class="form-group">
                    <label for="self_religion">Agama</label>
                    <select id="self_religion" v-model="selfForm.religion">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in RELIGION_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_education_level">Pendidikan</label>
                    <select id="self_education_level" v-model="selfForm.education_level">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in EDUCATION_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_major">Jurusan</label>
                    <input id="self_major" v-model="selfForm.major" type="text" maxlength="255" placeholder="Jurusan pendidikan" />
                  </div>
                  <div class="form-group form-group-full">
                    <AddressCascade v-model="selfForm" />
                  </div>
                  <div class="form-group form-group-full">
                    <label for="self_notes">Catatan</label>
                    <textarea id="self_notes" v-model="selfForm.notes" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
                  </div>
                </div>

                <p v-if="selfError" class="error-msg">{{ selfError }}</p>
                <button type="submit" class="btn-primary" :disabled="savingSelf || !selfDirty">
                  {{ savingSelf ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </form>
            </section>
          </div>

          <div class="side-stack">
            <section class="panel">
              <div class="panel-header">
                <h2>Ajukan Perubahan Data Kunci</h2>
              </div>
              <p class="section-desc">
                Nama, NIK, NIP, NUPTK, email, mapel, TTL, status, dan sertifikasi. Berlaku setelah admin menyetujui.
              </p>
              <p class="form-hint">
                Satu permintaan per field. Jika sudah ada permintaan menunggu untuk field yang sama, tunggu hasilnya dulu.
              </p>

              <form @submit.prevent="submitRequest" class="form">
                <div class="form-group">
                  <label for="field_name">Field yang ingin diubah *</label>
                  <select id="field_name" v-model="form.field_name" required>
                    <option value="">-- Pilih field --</option>
                    <option v-for="f in approvalFields" :key="f" :value="f">{{ getFieldLabel(f) }}</option>
                  </select>
                </div>

                <div v-if="form.field_name" class="current-value">
                  <span class="current-label">Nilai saat ini</span>
                  <span class="current-text">{{ formatProfileValue(teacher?.[form.field_name], form.field_name) }}</span>
                </div>

                <div class="form-group">
                  <label for="new_value">Nilai baru</label>

                  <select
                    v-if="isSelectField(form.field_name)"
                    id="new_value"
                    v-model="form.new_value"
                  >
                    <option value="">-- Pilih --</option>
                    <option
                      v-for="opt in getSelectOptions(form.field_name)"
                      :key="opt.value"
                      :value="opt.value"
                    >
                      {{ opt.label }}
                    </option>
                  </select>

                  <input
                    v-else-if="isDateField(form.field_name)"
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
                    v-else-if="form.field_name === 'nik'"
                    id="new_value"
                    v-model="form.new_value"
                    type="text"
                    inputmode="numeric"
                    maxlength="16"
                    placeholder="16 digit NIK"
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

                <button type="submit" class="btn-primary btn-amber" :disabled="submitting || !form.field_name">
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
                <p>Belum ada permintaan data kunci.</p>
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
                      <span class="value-text">{{ formatRequestValue(req.old_value, req.field_name) }}</span>
                    </div>
                    <div class="value-arrow" aria-hidden="true">→</div>
                    <div class="value-block">
                      <span class="value-label">Baru</span>
                      <span class="value-text new">{{ formatRequestValue(req.new_value, req.field_name) }}</span>
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
    </div></template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import AddressCascade from '@/components/AddressCascade.vue'
import { emptyAddress, formatFullAddress, pickAddress } from '@/utils/addressFields'
import { useToast } from '@/composables/useToast'
import { teacherApi } from '@/api/teacher'
import { teacherChangeRequestApi } from '@/api/teacherChangeRequest'
import { PROFILE_PHOTO_ACCEPT, profilePhotoFormData, validateProfilePhoto } from '@/utils/profilePhoto'

const toast = useToast()

const teacher = ref(null)
const profileError = ref('')
const loadingProfile = ref(true)
const approvalFields = ref([])
const selfEditableFields = ref([])
const requests = ref([])
const loading = ref(true)
const submitting = ref(false)
const submitError = ref('')
const savingSelf = ref(false)
const selfError = ref('')
const photoUploading = ref(false)

const form = ref({
  field_name: '',
  new_value: ''
})

const selfForm = ref({
  phone: '',
  religion: '',
  education_level: '',
  major: '',
  ...emptyAddress(),
  notes: ''
})

const selfBaseline = ref({
  phone: '',
  religion: '',
  education_level: '',
  major: '',
  ...emptyAddress(),
  notes: ''
})

const FIELD_LABELS = {
  name: 'Nama',
  nik: 'NIK',
  nip: 'NIP',
  nuptk: 'NUPTK',
  gender: 'Jenis Kelamin',
  email: 'Email',
  address: 'Alamat',
  phone: 'No. HP',
  religion: 'Agama',
  birth_place: 'Tempat Lahir',
  birth_date: 'Tanggal Lahir',
  education_level: 'Pendidikan',
  major: 'Jurusan',
  subject: 'Mata Pelajaran',
  employment_status: 'Status Kepegawaian',
  status: 'Status',
  join_date: 'Tanggal Bergabung',
  notes: 'Catatan',
  certification_status: 'Status Sertifikasi',
  certification_date: 'Tanggal Sertifikasi',
  teacher_registration_number: 'Nomor Registrasi Guru',
  certification_number: 'Nomor Sertifikasi',
  certification_issuing_authority: 'Lembaga Penerbit Sertifikasi'
}

const RELIGION_OPTIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']
const EDUCATION_OPTIONS = ['SMA', 'D3', 'S1', 'S2', 'S3']
const GENDER_OPTIONS = [
  { value: 'L', label: 'Laki-laki' },
  { value: 'P', label: 'Perempuan' }
]
const EMPLOYMENT_STATUS_OPTIONS = [
  'PNS', 'CPNS', 'Guru Tetap Yayasan', 'Guru Honor Sekolah', 'Guru Kontrak',
  'Pegawai Tetap Yayasan', 'Pegawai Honor', 'Pegawai Kontrak'
].map((v) => ({ value: v, label: v }))
const STATUS_OPTIONS = [
  'Aktif', 'Cuti', 'Pensiun', 'Pindah', 'Mengundurkan Diri', 'Tidak Aktif'
].map((v) => ({ value: v, label: v }))
const CERT_STATUS_OPTIONS = [
  { value: 'Sudah', label: 'Sudah' },
  { value: 'Belum', label: 'Belum' }
]

const DEFAULT_APPROVAL_FIELDS = [
  'name', 'nik', 'nip', 'nuptk', 'gender', 'email', 'birth_place', 'birth_date',
  'subject', 'employment_status', 'status', 'join_date',
  'certification_status', 'certification_date', 'teacher_registration_number',
  'certification_number', 'certification_issuing_authority'
]

const DEFAULT_SELF_FIELDS = [
  'address', 'village', 'sub_district', 'district', 'province', 'postal_code',
  'wilayah_province_code', 'wilayah_regency_code', 'wilayah_district_code', 'wilayah_village_code',
  'phone', 'religion', 'education_level', 'major', 'notes'
]

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function isDateField(field) {
  return ['birth_date', 'certification_date', 'join_date'].includes(field)
}

function isSelectField(field) {
  return ['gender', 'employment_status', 'status', 'certification_status'].includes(field)
}

function getSelectOptions(field) {
  if (field === 'gender') return GENDER_OPTIONS
  if (field === 'employment_status') return EMPLOYMENT_STATUS_OPTIONS
  if (field === 'status') return STATUS_OPTIONS
  if (field === 'certification_status') return CERT_STATUS_OPTIONS
  return []
}

function formatGender(val) {
  if (val === 'L') return 'Laki-laki'
  if (val === 'P') return 'Perempuan'
  return val || '-'
}

function formatProfileValue(val, field) {
  if (val == null || val === '') return '-'
  if (field === 'gender') return formatGender(val)
  const dateFields = ['birth_date', 'certification_date', 'join_date']
  if (dateFields.includes(field) && val) {
    return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  }
  return String(val)
}

function formatRequestValue(val, field) {
  if (val == null || val === '') return '-'
  if (field === 'gender') return formatGender(val)
  if (isDateField(field) && val) {
    const d = new Date(val)
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
    }
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

function normalizeSelfValue(val) {
  return val == null ? '' : String(val)
}

function syncSelfFormFromTeacher() {
  const t = teacher.value || {}
  const next = {
    phone: normalizeSelfValue(t.phone),
    religion: normalizeSelfValue(t.religion),
    education_level: normalizeSelfValue(t.education_level),
    major: normalizeSelfValue(t.major),
    ...pickAddressValues(t),
    notes: normalizeSelfValue(t.notes)
  }
  selfForm.value = { ...next }
  selfBaseline.value = { ...next }
}

const initials = computed(() => {
  const name = teacher.value?.name || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'G'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

async function onMyPhotoSelect(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  const photoError = validateProfilePhoto(file)
  if (photoError) {
    toast.error('Gagal', photoError)
    return
  }
  photoUploading.value = true
  try {
    const res = await teacherChangeRequestApi.uploadMyPhoto(profilePhotoFormData(file))
    const data = res.data?.data
    if (data) teacher.value = { ...teacher.value, ...data }
    toast.success('Berhasil', res.data?.message || 'Foto profil diunggah')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal mengunggah foto')
  } finally {
    photoUploading.value = false
  }
}

async function removeMyPhoto() {
  if (!teacher.value?.photo_url || photoUploading.value) return
  if (!confirm('Hapus foto profil?')) return
  photoUploading.value = true
  try {
    const res = await teacherChangeRequestApi.deleteMyPhoto()
    const data = res.data?.data
    teacher.value = { ...(teacher.value || {}), ...(data || {}), photo_url: null, photo_path: null }
    toast.success('Berhasil', res.data?.message || 'Foto profil dihapus')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal menghapus foto')
  } finally {
    photoUploading.value = false
  }
}

const pendingCount = computed(() => requests.value.filter((r) => r.status === 'pending').length)

const selfDirty = computed(() => {
  return Object.keys(selfBaseline.value).some(
    (k) => normalizeSelfValue(selfForm.value[k]) !== normalizeSelfValue(selfBaseline.value[k])
  )
})

const approvalFieldSet = computed(() => new Set(approvalFields.value.length ? approvalFields.value : DEFAULT_APPROVAL_FIELDS))

function pickAddressValues(source) {
  const picked = pickAddress(source)
  return Object.fromEntries(Object.entries(picked).map(([k, v]) => [k, normalizeSelfValue(v)]))
}

function makeItem(key, label) {
  const raw = key === 'address' ? formatFullAddress(teacher.value) : teacher.value?.[key]
  const value = formatProfileValue(raw, key)
  return {
    key,
    label,
    value,
    empty: value === '-',
    needsApproval: approvalFieldSet.value.has(key)
  }
}

const profileGroups = computed(() => {
  if (!teacher.value) return []

  const identityKeys = ['name', 'nik', 'nip', 'nuptk', 'gender', 'join_date', 'status', 'employment_status']
  const contactKeys = ['phone', 'email', 'address', 'religion', 'birth_place', 'birth_date']
  const eduKeys = ['education_level', 'major', 'subject', 'notes']
  const certKeys = [
    'certification_status',
    'certification_date',
    'teacher_registration_number',
    'certification_number',
    'certification_issuing_authority'
  ]

  const pick = (keys) =>
    keys
      .filter((k) => {
        const hasValue = teacher.value?.[k] != null && teacher.value?.[k] !== ''
        return hasValue || approvalFieldSet.value.has(k) || DEFAULT_SELF_FIELDS.includes(k)
      })
      .map((k) => makeItem(k, getFieldLabel(k)))

  const groups = []
  const identity = pick(identityKeys)
  if (identity.length) groups.push({ key: 'identity', title: 'Identitas', items: identity })

  const contact = pick(contactKeys)
  if (contact.length) groups.push({ key: 'contact', title: 'Kontak & Pribadi', items: contact })

  const edu = pick(eduKeys)
  if (edu.length) groups.push({ key: 'edu', title: 'Pendidikan & Mengajar', items: edu })

  const cert = pick(certKeys)
  if (cert.length) groups.push({ key: 'cert', title: 'Sertifikasi', items: cert })

  return groups
})

async function loadProfile() {
  loadingProfile.value = true
  profileError.value = ''
  try {
    const res = await teacherApi.getDashboard()
    const data = res.data?.data
    teacher.value = data?.teacher || null
    if (!teacher.value) {
      profileError.value = 'Profil guru tidak ditemukan.'
    } else {
      syncSelfFormFromTeacher()
    }
  } catch (err) {
    profileError.value = err.response?.data?.message || 'Gagal memuat profil.'
  } finally {
    loadingProfile.value = false
  }
}

async function loadAllowedFields() {
  try {
    const res = await teacherChangeRequestApi.getAllowedFields()
    const body = res.data || {}
    approvalFields.value = body.approval_fields || body.data || DEFAULT_APPROVAL_FIELDS
    selfEditableFields.value = body.self_editable_fields || DEFAULT_SELF_FIELDS
  } catch {
    approvalFields.value = DEFAULT_APPROVAL_FIELDS
    selfEditableFields.value = DEFAULT_SELF_FIELDS
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

async function saveSelfEdit() {
  selfError.value = ''
  if (!selfDirty.value) {
    selfError.value = 'Tidak ada perubahan untuk disimpan.'
    return
  }

  const payload = {}
  for (const key of selfEditableFields.value.length ? selfEditableFields.value : DEFAULT_SELF_FIELDS) {
    if (normalizeSelfValue(selfForm.value[key]) !== normalizeSelfValue(selfBaseline.value[key])) {
      const val = (selfForm.value[key] || '').trim()
      payload[key] = val === '' ? null : val
    }
  }

  if (!Object.keys(payload).length) {
    selfError.value = 'Tidak ada perubahan untuk disimpan.'
    return
  }

  savingSelf.value = true
  try {
    const res = await teacherChangeRequestApi.updateMyProfile(payload)
    teacher.value = res.data?.data || teacher.value
    syncSelfFormFromTeacher()
    toast.success('Berhasil', 'Profil berhasil diperbarui.')
  } catch (err) {
    selfError.value = err.response?.data?.message
      || err.response?.data?.errors?.fields?.[0]
      || 'Gagal menyimpan profil'
    toast.error('Gagal', selfError.value)
  } finally {
    savingSelf.value = false
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
    submitError.value = err.response?.data?.message
      || Object.values(err.response?.data?.errors || {})?.[0]?.[0]
      || 'Gagal mengirim permintaan'
    toast.error('Gagal', submitError.value)
  } finally {
    submitting.value = false
  }
}

watch(
  () => form.value.field_name,
  (field) => {
    if (!field) {
      form.value.new_value = ''
      return
    }
    const current = teacher.value?.[field]
    if (isDateField(field) && current) {
      form.value.new_value = typeof current === 'string' ? current.slice(0, 10) : ''
    } else if (isSelectField(field) && current != null) {
      form.value.new_value = String(current)
    } else if (current != null && current !== '') {
      form.value.new_value = String(current)
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
  max-width: 52ch;
  line-height: 1.45;
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
  flex-direction: column;
  gap: 14px;
  padding: 18px 20px;
  margin-bottom: 16px;
  border-radius: 16px;
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}

.hero-top {
  display: flex;
  align-items: center;
  gap: 16px;
}

.hero-avatar-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  flex-shrink: 0;
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
  overflow: hidden;
}

.hero-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  padding: 0;
}

.hero-avatar-hint {
  font-size: 10px;
  font-weight: 650;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  opacity: 0.85;
}

.hero-photo-remove {
  margin-top: 6px;
  border: 0;
  background: rgba(255, 255, 255, 0.16);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 8px;
  cursor: pointer;
}

.hero-photo-remove:disabled {
  opacity: 0.6;
  cursor: wait;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
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
  margin: 0;
  font-size: 13px;
  opacity: 0.92;
  font-weight: 500;
}

.hero-dot {
  margin: 0 6px;
  opacity: 0.7;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 8px 12px;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: rgba(255,255,255,0.16); color: #fff;
}
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(255,255,255,0.7); }
.meta-value { font-size: 13.5px; font-weight: 600; color: #fff; line-height: 1.35; word-break: break-word; }

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
  gap: 16px;
  align-items: start;
}

.main-stack,
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

.panel-hint-icons {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.panel-hint-icons svg {
  flex-shrink: 0;
}

.hint-sep {
  opacity: 0.5;
  margin: 0 2px;
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
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.field-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 6px;
  flex-shrink: 0;
}

.field-badge.key {
  background: #fff7ed;
  color: #c2410c;
  border: 1px solid #fed7aa;
}

.field-badge.free {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #bbf7d0;
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

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 4px;
}

.form-group {
  margin-bottom: 14px;
}

.form-group-full {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.form-group select,
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  font-size: 14px;
  background: #fff;
  color: #0f172a;
  font-family: inherit;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-group textarea {
  resize: vertical;
  min-height: 72px;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
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

.btn-amber {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
}

.btn-amber:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(180, 83, 9, 0.35);
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

@media (max-width: 1440px) {
  .content-grid {
    grid-template-columns: minmax(0, 1fr) minmax(240px, 0.75fr);
    gap: 12px;
  }

  .panel {
    padding: 14px 16px;
  }

  .page-header {
    margin-bottom: 12px;
  }
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

  .meta-grid {
    grid-template-columns: 1fr 1fr;
  }

  .profile-grid,
  .form-grid {
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
