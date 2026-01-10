<template>
  <div class="register-container">
    <div class="register-card">
      <div class="card-header">
        <div class="logo">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#667eea"/>
            <path d="M2 17L12 22L22 17" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h1>Daftar Sekolah Baru</h1>
        <p>Buat akun untuk sekolah Anda</p>
      </div>
      
      <form @submit.prevent="handleRegister" class="register-form">
        <div class="form-group">
          <label>NPSN *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.npsn" 
              :class="{ 'input-error': fieldErrors.npsn }"
              placeholder="Nomor Pokok Sekolah Nasional (8 angka)"
              maxlength="8"
              @input="handleNpsnInput"
              @blur="() => {
                const validation = validateForm({ npsn: form.npsn }, { npsn: validationRules.npsn })
                fieldErrors.npsn = validation.errors.npsn || ''
              }"
            />
          </div>
          <small class="form-hint">8 digit angka NPSN sekolah Anda</small>
          <span v-if="fieldErrors.npsn" class="error-text">{{ fieldErrors.npsn }}</span>
        </div>
        
        <div class="form-group">
          <label>Nama Sekolah/Madrasah *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.institution_name" 
              :class="{ 'input-error': fieldErrors.institution_name }"
              @blur="() => {
                const validation = validateForm({ institution_name: form.institution_name }, { institution_name: validationRules.institution_name })
                fieldErrors.institution_name = validation.errors.institution_name || ''
              }"
              placeholder="Nama sekolah/madrasah"
            />
          </div>
          <span v-if="fieldErrors.institution_name" class="error-text">{{ fieldErrors.institution_name }}</span>
        </div>
        
        <div class="form-group">
          <label>Nama Lengkap Admin *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.name" 
              :class="{ 'input-error': fieldErrors.name }"
              @blur="() => {
                const validation = validateForm({ name: form.name }, { name: validationRules.name })
                fieldErrors.name = validation.errors.name || ''
              }"
              placeholder="Nama lengkap admin"
            />
          </div>
          <span v-if="fieldErrors.name" class="error-text">{{ fieldErrors.name }}</span>
        </div>
        
        <div class="form-group">
          <label>Email *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="L22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="email" 
              v-model="form.email" 
              :class="{ 'input-error': fieldErrors.email }"
              @blur="() => {
                const validation = validateForm({ email: form.email }, { email: validationRules.email })
                fieldErrors.email = validation.errors.email || ''
              }"
              placeholder="nama@email.com"
            />
          </div>
          <span v-if="fieldErrors.email" class="error-text">{{ fieldErrors.email }}</span>
        </div>
        
        <div class="form-group">
          <label>No HP/WA *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22 16.92V19.92C22.0011 20.1985 21.9441 20.4742 21.8325 20.7292C21.7209 20.9841 21.5573 21.2126 21.3528 21.3992C21.1482 21.5857 20.9071 21.7261 20.6446 21.8114C20.3821 21.8966 20.104 21.9247 19.83 21.8939C16.7428 21.4963 13.787 20.4471 11.19 18.8399C8.77382 17.4277 6.72533 15.5007 5.19 13.1999C3.57982 10.6512 2.50334 7.78999 2.03 4.78994C1.99926 4.51589 2.02738 4.23826 2.11219 3.97626C2.197 3.71426 2.33642 3.47379 2.52188 3.27005C2.70734 3.06631 2.93459 2.90401 3.18833 2.79378C3.44207 2.68355 3.71651 2.62793 3.995 2.62994H6.995C7.78865 2.61148 8.57006 2.83241 9.24057 3.26507C9.91108 3.69773 10.4404 4.32405 10.76 5.05994L12.04 8.17994C12.3382 8.85019 12.4938 9.57618 12.495 10.3099C12.4963 11.0437 12.3432 11.7706 12.047 12.4399L10.767 15.5599C10.4511 16.2991 10.0237 16.9844 9.5 17.5899C8.97634 18.1954 8.36403 18.7128 7.685 19.1199" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="tel" 
              v-model="form.phone" 
              :class="{ 'input-error': fieldErrors.phone }"
              placeholder="08xxxxxxxxxx"
              @input="handlePhoneInput"
              @blur="() => {
                const validation = validateForm({ phone: form.phone }, { phone: validationRules.phone })
                fieldErrors.phone = validation.errors.phone || ''
              }"
            />
          </div>
          <span v-if="fieldErrors.phone" class="error-text">{{ fieldErrors.phone }}</span>
        </div>
        
        <div class="form-group">
          <label>Password *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="password" 
              v-model="form.password" 
              :class="{ 'input-error': fieldErrors.password }"
              placeholder="Minimal 8 karakter"
              minlength="8"
              @blur="() => {
                const validation = validateForm({ password: form.password }, { password: validationRules.password })
                fieldErrors.password = validation.errors.password || ''
                // Re-validate password_confirmation if it has value
                if (form.password_confirmation) {
                  const confirmValidation = validateForm(
                    { password_confirmation: form.password_confirmation }, 
                    { password_confirmation: validationRules.password_confirmation }
                  )
                  fieldErrors.password_confirmation = confirmValidation.errors.password_confirmation || ''
                }
              }"
            />
          </div>
          <span v-if="fieldErrors.password" class="error-text">{{ fieldErrors.password }}</span>
        </div>
        
        <div class="form-group">
          <label>Konfirmasi Password *</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="password" 
              v-model="form.password_confirmation" 
              :class="{ 'input-error': fieldErrors.password_confirmation }"
              placeholder="Ulangi password"
              @blur="() => {
                const validation = validateForm(
                  { password_confirmation: form.password_confirmation }, 
                  { password_confirmation: validationRules.password_confirmation }
                )
                fieldErrors.password_confirmation = validation.errors.password_confirmation || ''
              }"
            />
          </div>
          <span v-if="fieldErrors.password_confirmation" class="error-text">{{ fieldErrors.password_confirmation }}</span>
        </div>
        
        <button type="submit" :disabled="loading" class="btn-primary">
          <span v-if="!loading">Daftar</span>
          <span v-else class="loading-spinner">
            <svg class="spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
            Memproses...
          </span>
        </button>
      </form>
      
      <div v-if="error" class="error-message">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>{{ error }}</span>
      </div>
      
      <div class="card-footer">
        <p>
          Sudah punya akun? 
          <router-link to="/login" class="link">Masuk</router-link>
        </p>
        <div class="back-home">
          <router-link to="/" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  npsn: '',
  institution_name: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const loading = ref(false)
const error = ref('')
const fieldErrors = ref({
  npsn: '',
  institution_name: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const validationRules = {
  npsn: [
    validators.required('NPSN wajib diisi'),
    validators.npsn('NPSN harus terdiri dari 8 digit angka')
  ],
  institution_name: [
    validators.required('Nama sekolah/madrasah wajib diisi'),
    validators.maxLength(form.value.institution_name, 255, 'Nama sekolah/madrasah maksimal 255 karakter')
  ],
  name: [
    validators.required('Nama lengkap wajib diisi'),
    validators.maxLength(form.value.name, 255, 'Nama maksimal 255 karakter')
  ],
  email: [
    validators.required('Email wajib diisi'),
    validators.email('Format email tidak valid'),
    validators.maxLength(form.value.email, 255, 'Email maksimal 255 karakter')
  ],
  phone: [
    validators.required('Nomor telepon wajib diisi'),
    validators.phone('Format nomor telepon tidak valid (10-15 digit)'),
    validators.maxLength(form.value.phone, 20, 'Nomor telepon maksimal 20 karakter')
  ],
  password: [
    validators.required('Password wajib diisi'),
    validators.minLength(form.value.password, 8, 'Password minimal 8 karakter')
  ],
  password_confirmation: [
    validators.required('Konfirmasi password wajib diisi'),
    () => validators.match(form.value.password_confirmation, form.value.password, 'Password dan konfirmasi password tidak sama')
  ]
}

const handleNpsnInput = (e) => {
  // Hanya allow angka
  e.target.value = e.target.value.replace(/[^0-9]/g, '')
  form.value.npsn = e.target.value
  // Clear error saat user mengetik
  if (fieldErrors.value.npsn) {
    const validation = validateForm({ npsn: form.value.npsn }, { npsn: validationRules.npsn })
    fieldErrors.value.npsn = validation.errors.npsn || ''
  }
}

const handlePhoneInput = (e) => {
  // Hanya allow angka
  e.target.value = e.target.value.replace(/[^0-9]/g, '')
  form.value.phone = e.target.value
  // Clear error saat user mengetik
  if (fieldErrors.value.phone) {
    const validation = validateForm({ phone: form.value.phone }, { phone: validationRules.phone })
    fieldErrors.value.phone = validation.errors.phone || ''
  }
}

const handleRegister = async () => {
  // Clear previous errors
  error.value = ''
  fieldErrors.value = {
    npsn: '',
    institution_name: '',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: ''
  }
  
  // Validate form
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    fieldErrors.value = validation.errors
    error.value = 'Mohon perbaiki kesalahan pada form'
    return
  }
  
  loading.value = true
  
  try {
    await authStore.register(form.value)
    toast.success('Registrasi Berhasil', 'Selamat! Akun Anda berhasil dibuat')
    router.push('/dashboard')
  } catch (err) {
    const errorMessage = err.formattedMessage || err.response?.data?.message || 'Terjadi kesalahan saat pendaftaran'
    error.value = errorMessage
    toast.error('Registrasi Gagal', errorMessage)
    
    // Handle field-specific errors
    if (err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (fieldErrors.value.hasOwnProperty(key)) {
          fieldErrors.value[key] = Array.isArray(serverErrors[key]) 
            ? serverErrors[key][0] 
            : serverErrors[key]
        }
      })
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 24px;
}

.register-card {
  width: 100%;
  max-width: 480px;
  background: white;
  border-radius: 16px;
  padding: 48px 40px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
  max-height: 90vh;
  overflow-y: auto;
}

.card-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.card-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 8px;
  letter-spacing: -0.025em;
}

.card-header p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.register-form {
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 18px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  color: #334155;
  font-weight: 500;
  font-size: 14px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
  z-index: 1;
}

.form-group input {
  width: 100%;
  padding: 12px 14px 12px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.15s ease;
  background: white;
  color: #0f172a;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input::placeholder {
  color: #94a3b8;
}

.form-group input.input-error {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.error-text {
  display: block;
  margin-top: 4px;
  color: #dc2626;
  font-size: 12px;
}

.form-hint {
  display: block;
  margin-top: 4px;
  color: #94a3b8;
  font-size: 12px;
}

.btn-primary {
  width: 100%;
  padding: 12px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
}

.btn-primary:active:not(:disabled) {
  background: #4c5bc4;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-spinner {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.error-message {
  margin-bottom: 20px;
  padding: 12px 14px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 8px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #fecaca;
}

.error-message svg {
  flex-shrink: 0;
  color: #dc2626;
}

.card-footer {
  text-align: center;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.card-footer p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  margin-bottom: 16px;
}

.back-home {
  margin-top: 16px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #64748b;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.15s ease;
}

.back-link:hover {
  color: #475569;
}

.back-link svg {
  flex-shrink: 0;
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s ease;
}

.link:hover {
  color: #5568d3;
  text-decoration: underline;
}

@media (max-width: 640px) {
  .register-container {
    padding: 16px;
  }
  
  .register-card {
    padding: 32px 24px;
  }
  
  .card-header h1 {
    font-size: 24px;
  }
}
</style>
