<template>    <div class="onboard-page">
      <div class="page-header">
        <div>
          <h2>Onboarding Sekolah</h2>
          <p>Buat institusi baru beserta admin pertama dalam satu alur</p>
        </div>
        <router-link to="/super-admin/institution-admins" class="btn-secondary btn-compact">Kelola Admin</router-link>
      </div>

      <div class="wizard">
        <div class="steps">
          <div class="step" :class="{ active: step === 1, done: step > 1 }">
            <span class="step-num">1</span>
            <span>Institusi</span>
          </div>
          <div class="step-line" :class="{ done: step > 1 }"></div>
          <div class="step" :class="{ active: step === 2, done: step > 2 }">
            <span class="step-num">2</span>
            <span>Admin</span>
          </div>
          <div class="step-line" :class="{ done: step > 2 }"></div>
          <div class="step" :class="{ active: step === 3, done: step > 2 && result }">
            <span class="step-num">3</span>
            <span>Selesai</span>
          </div>
        </div>

        <form v-if="!result" @submit.prevent="handleNext">
          <div v-if="step === 1" class="panel">
            <h3>Data Institusi</h3>
            <div class="form-grid">
              <div class="form-group full">
                <label>Nama Sekolah/Madrasah *</label>
                <input v-model="form.name" type="text" required class="form-control" maxlength="255" />
              </div>
              <div class="form-group full">
                <label>Nama Yayasan</label>
                <input v-model="form.foundation_name" type="text" class="form-control" maxlength="255" placeholder="Opsional — tampil di kop laporan" />
              </div>
              <div class="form-group">
                <label>NPSN</label>
                <input v-model="form.npsn" type="text" class="form-control" maxlength="8" placeholder="8 digit" />
              </div>
              <div class="form-group">
                <label>Jenjang</label>
                <select v-model="form.level" class="form-control">
                  <option value="">Pilih jenjang</option>
                  <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jenis *</label>
                <select v-model="form.type" required class="form-control">
                  <option value="Swasta">Swasta</option>
                  <option value="Negeri">Negeri</option>
                </select>
              </div>
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" type="text" class="form-control" maxlength="20" />
              </div>
              <div class="form-group full">
                <AddressCascade v-model="form" />
              </div>
              <div class="form-group">
                <label class="checkbox-label">
                  <input v-model="form.is_active" type="checkbox" />
                  Institusi aktif
                </label>
              </div>
            </div>
          </div>

          <div v-else-if="step === 2" class="panel">
            <h3>Admin Pertama</h3>
            <p class="panel-desc">Akun ini menjadi institution admin dan mendapatkan akses penuh modul sekolah.</p>
            <div class="form-grid">
              <div class="form-group">
                <label>Nama Admin *</label>
                <input v-model="form.admin_name" type="text" required class="form-control" maxlength="255" />
              </div>
              <div class="form-group">
                <label>Email Admin *</label>
                <input v-model="form.admin_email" type="email" required class="form-control" maxlength="255" />
              </div>
              <div class="form-group">
                <label>Sandi (opsional)</label>
                <input v-model="form.admin_password" type="password" class="form-control" autocomplete="new-password" />
                <small class="form-hint">Kosongkan untuk generate otomatis.</small>
              </div>
              <div v-if="form.admin_password" class="form-group">
                <label>Konfirmasi Sandi</label>
                <input v-model="form.admin_password_confirmation" type="password" class="form-control" autocomplete="new-password" />
              </div>
            </div>
          </div>

          <p v-if="error" class="form-error">{{ error }}</p>

          <div class="wizard-actions">
            <button v-if="step > 1" type="button" class="btn-secondary" @click="step -= 1">Kembali</button>
            <div class="spacer"></div>
            <button v-if="step < 2" type="submit" class="btn-primary">Lanjut</button>
            <button v-else type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Memproses...' : 'Buat Institusi & Admin' }}
            </button>
          </div>
        </form>

        <div v-else class="panel success-panel">
          <h3>Onboarding berhasil</h3>
          <p>
            <strong>{{ result.institution?.name }}</strong> dan admin
            <strong>{{ result.admin?.name }}</strong> ({{ result.admin?.email }}) telah dibuat.
          </p>

          <div v-if="result.admin?.email || result.temporary_password" class="password-box">
            <p class="cred-note">Simpan kredensial ini. Sandi hanya ditampilkan di halaman ini.</p>
            <div v-if="result.admin?.email" class="cred-row">
              <div>
                <small>Email</small>
                <code>{{ result.admin.email }}</code>
              </div>
              <button type="button" class="btn-secondary btn-compact" @click="copyText(result.admin.email, 'Email')">Salin</button>
            </div>
            <div v-if="result.temporary_password" class="cred-row">
              <div>
                <small>Sandi sementara</small>
                <code>{{ result.temporary_password }}</code>
              </div>
              <button type="button" class="btn-secondary btn-compact" @click="copyPassword">Salin</button>
            </div>
          </div>

          <div class="checklist">
            <h4>Checklist berikutnya</h4>
            <ul>
              <li v-for="item in result.checklist" :key="item.key" :class="{ done: item.done }">
                <span>{{ item.done ? '✓' : '○' }} {{ item.label }}</span>
                <router-link v-if="!item.done && item.to" :to="item.to">Buka</router-link>
              </li>
            </ul>
          </div>

          <div class="wizard-actions">
            <button type="button" class="btn-secondary" @click="resetWizard">Onboarding Lagi</button>
            <div class="spacer"></div>
            <router-link to="/super-admin/institution-admins" class="btn-secondary">Lihat Admin</router-link>
            <router-link to="/super-admin/dashboard" class="btn-primary">Ke Dashboard</router-link>
          </div>
        </div>
      </div>
    </div></template>

<script setup>
import { ref } from 'vue'
import AddressCascade from '@/components/AddressCascade.vue'
import { institutionAdminApi } from '@/api/institutionAdmin'
import { useToast } from '@/composables/useToast'
import { ADDRESS_KEYS, emptyAddress } from '@/utils/addressFields'

const toast = useToast()
const step = ref(1)
const saving = ref(false)
const error = ref('')
const result = ref(null)

const levels = ['TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MAK', 'MTs', 'MI', 'PAUD']

const emptyForm = () => ({
  name: '',
  foundation_name: '',
  npsn: '',
  level: '',
  type: 'Swasta',
  phone: '',
  ...emptyAddress(),
  is_active: true,
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: ''
})

const form = ref(emptyForm())

const handleNext = async () => {
  error.value = ''

  if (step.value === 1) {
    if (!form.value.name.trim()) {
      error.value = 'Nama institusi wajib diisi'
      return
    }
    if (form.value.npsn && !/^\d{8}$/.test(form.value.npsn)) {
      error.value = 'NPSN harus 8 digit angka'
      return
    }
    step.value = 2
    return
  }

  if (form.value.admin_password && form.value.admin_password !== form.value.admin_password_confirmation) {
    error.value = 'Konfirmasi sandi tidak cocok'
    return
  }

  saving.value = true
  try {
    const payload = {
      name: form.value.name.trim(),
      foundation_name: form.value.foundation_name?.trim() || null,
      npsn: form.value.npsn || null,
      level: form.value.level || null,
      type: form.value.type,
      phone: form.value.phone || null,
      ...Object.fromEntries(ADDRESS_KEYS.map((key) => [key, form.value[key] || null])),
      is_active: form.value.is_active,
      admin_name: form.value.admin_name.trim(),
      admin_email: form.value.admin_email.trim()
    }
    if (form.value.admin_password) {
      payload.admin_password = form.value.admin_password
      payload.admin_password_confirmation = form.value.admin_password_confirmation
    }

    const res = await institutionAdminApi.onboard(payload)
    result.value = {
      institution: res.data?.data?.institution,
      admin: res.data?.data?.admin,
      temporary_password: res.data?.temporary_password || form.value.admin_password || null,
      checklist: res.data?.checklist || []
    }
    step.value = 3
    toast.success('Berhasil', res.data?.message || 'Onboarding selesai')
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal membuat institusi'
    if (err.response?.data?.errors) {
      const first = Object.values(err.response.data.errors)[0]
      if (Array.isArray(first) && first[0]) error.value = first[0]
    }
  } finally {
    saving.value = false
  }
}

const copyText = async (value, label = 'Teks') => {
  try {
    await navigator.clipboard.writeText(String(value || ''))
    toast.success('Disalin', `${label} disalin ke clipboard`)
  } catch {
    toast.error('Gagal', `Tidak dapat menyalin ${label.toLowerCase()}`)
  }
}

const copyPassword = async () => {
  await copyText(result.value.temporary_password, 'Sandi')
}

const resetWizard = () => {
  form.value = emptyForm()
  result.value = null
  step.value = 1
  error.value = ''
}
</script>

<style scoped>
.onboard-page {
  width: 100%;
  max-width: 820px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.page-header h2 {
  margin: 0 0 4px;
  font-size: 24px;
  color: #0f172a;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.wizard {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 28px;
}

.steps {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 28px;
}

.step {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
}

.step.active,
.step.done {
  color: #059669;
}

.step-num {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: inherit;
}

.step.active .step-num,
.step.done .step-num {
  background: rgba(5, 150, 105, 0.15);
}

.step-line {
  flex: 1;
  height: 2px;
  background: #e2e8f0;
}

.step-line.done {
  background: #059669;
}

.panel h3 {
  margin: 0 0 8px;
  font-size: 18px;
  color: #0f172a;
}

.panel-desc {
  margin: 0 0 16px;
  color: #64748b;
  font-size: 14px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-group.full {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.form-control {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
}

.form-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #94a3b8;
}

.checkbox-label {
  display: flex !important;
  align-items: center;
  gap: 8px;
  font-weight: 500 !important;
}

.form-error {
  color: #dc2626;
  font-size: 13px;
  margin: 12px 0 0;
}

.wizard-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 24px;
}

.spacer {
  flex: 1;
}

.success-panel p {
  color: #475569;
}

.password-box {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
  margin: 16px 0;
}

.cred-note {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.cred-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.password-box small {
  display: block;
  color: #64748b;
  margin-bottom: 4px;
}

.password-box code {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.checklist h4 {
  margin: 0 0 10px;
  font-size: 14px;
  color: #0f172a;
}

.checklist ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.checklist li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-top: 1px solid #f1f5f9;
  font-size: 14px;
  color: #475569;
}

.checklist li.done {
  color: #059669;
  font-weight: 600;
}

.checklist a {
  color: #059669;
  font-weight: 600;
  text-decoration: none;
}

.btn-primary,
.btn-secondary {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-primary {
  background: #059669;
  color: white;
  border: none;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-compact {
  padding: 8px 12px;
  font-size: 13px;
}

@media (max-width: 700px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .wizard {
    padding: 16px;
  }

  .steps {
    flex-wrap: wrap;
  }

  .step-line {
    display: none;
  }

  .wizard-actions {
    flex-wrap: wrap;
  }

  .wizard-actions .spacer {
    display: none;
  }

  .wizard-actions > * {
    flex: 1 1 100%;
    justify-content: center;
    text-align: center;
  }

  .password-box {
    flex-direction: column;
    align-items: stretch;
  }

  .checklist li {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
}

@media (max-width: 480px) {
  .onboard-page {
    max-width: 100%;
  }

  .page-header h2 {
    font-size: 1.25rem;
  }
}
</style>
