<template>
  <div class="register-container">
    <div class="register-card">
      <div class="card-header">
        <div class="logo">
          <AppLogo :size="48" />
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
              @blur="() => validateField('npsn')"
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
              @blur="() => validateField('institution_name')"
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
              @blur="() => validateField('name')"
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
              <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="email" 
              v-model="form.email" 
              :class="{ 'input-error': fieldErrors.email }"
              @blur="() => validateField('email')"
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
              @blur="() => validateField('phone')"
            />
          </div>
          <span v-if="fieldErrors.phone" class="error-text">{{ fieldErrors.phone }}</span>
        </div>
        
        <div class="form-group">
          <label>Password *</label>
          <div class="input-wrapper input-wrapper-password">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              :type="showPassword ? 'text' : 'password'" 
              v-model="form.password" 
              :class="{ 'input-error': fieldErrors.password }"
              placeholder="Minimal 8 karakter"
              minlength="8"
              @blur="() => {
                validateField('password')
                if (form.password_confirmation) {
                  validateField('password_confirmation')
                }
              }"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
              @click="showPassword = !showPassword"
            >
              <svg v-if="!showPassword" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
          <span v-if="fieldErrors.password" class="error-text">{{ fieldErrors.password }}</span>
        </div>
        
        <div class="form-group">
          <label>Konfirmasi Password *</label>
          <div class="input-wrapper input-wrapper-password">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              :type="showPasswordConfirm ? 'text' : 'password'" 
              v-model="form.password_confirmation" 
              :class="{ 'input-error': fieldErrors.password_confirmation }"
              placeholder="Ulangi password"
              @blur="() => validateField('password_confirmation')"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showPasswordConfirm ? 'Sembunyikan password' : 'Tampilkan password'"
              @click="showPasswordConfirm = !showPasswordConfirm"
            >
              <svg v-if="!showPasswordConfirm" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
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
import { validators } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { useToast } from '@/composables/useToast'
import AppLogo from '@/components/AppLogo.vue'

const toast = useToast()

const router = useRouter()
const authStore = useAuthStore()

const initialForm = {
  npsn: '',
  institution_name: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
}
const form = ref({ ...initialForm })

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const validationRules = {
  npsn: [
    (value) => validators.required(value, 'NPSN wajib diisi'),
    (value) => validators.npsn(value, 'NPSN harus terdiri dari 8 digit angka')
  ],
  institution_name: [
    (value) => validators.required(value, 'Nama sekolah/madrasah wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama sekolah/madrasah maksimal 255 karakter')
  ],
  name: [
    (value) => validators.required(value, 'Nama lengkap wajib diisi'),
    (value) => validators.maxLength(value, 255, 'Nama maksimal 255 karakter')
  ],
  email: [
    (value) => validators.required(value, 'Email wajib diisi'),
    (value) => validators.email(value, 'Format email tidak valid'),
    (value) => validators.maxLength(value, 255, 'Email maksimal 255 karakter')
  ],
  phone: [
    (value) => validators.required(value, 'Nomor telepon wajib diisi'),
    (value) => validators.phone(value, 'Format nomor telepon tidak valid (10-15 digit)'),
    (value) => validators.maxLength(value, 20, 'Nomor telepon maksimal 20 karakter')
  ],
  password: [
    (value) => validators.required(value, 'Password wajib diisi'),
    (value) => validators.minLength(value, 8, 'Password minimal 8 karakter')
  ],
  password_confirmation: [
    (value) => validators.required(value, 'Konfirmasi password wajib diisi'),
    (value) => validators.match(value, form.value.password, 'Password dan konfirmasi password tidak sama')
  ]
}

const { fieldErrors, validateField, validateAll, clearErrors, setErrors } = useFormValidation({
  form,
  initialValues: initialForm,
  rules: validationRules
})

const handleNpsnInput = (e) => {
  // Hanya allow angka
  e.target.value = e.target.value.replace(/[^0-9]/g, '')
  form.value.npsn = e.target.value
  // Clear error saat user mengetik
  if (fieldErrors.npsn) {
    validateField('npsn')
  }
}

const handlePhoneInput = (e) => {
  // Hanya allow angka
  e.target.value = e.target.value.replace(/[^0-9]/g, '')
  form.value.phone = e.target.value
  // Clear error saat user mengetik
  if (fieldErrors.phone) {
    validateField('phone')
  }
}

const handleRegister = async () => {
  // Clear previous errors
  error.value = ''
  clearErrors()
  
  // Validate form
  const isValid = validateAll()
  if (!isValid) {
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
      setErrors(err.response.data.errors)
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Background dengan gradient animasi & ornamen */
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 25%, #f8fafc 50%, #eef2ff 75%, #f0f4ff 100%);
  background-size: 400% 400%;
  animation: gradientShift 12s ease infinite;
}

.register-container::before,
.register-container::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.4;
  animation: float 20s ease-in-out infinite;
  pointer-events: none;
}

.register-container::before {
  width: 400px;
  height: 400px;
  background: rgba(102, 126, 234, 0.15);
  top: -100px;
  right: -100px;
  animation-delay: 0s;
}

.register-container::after {
  width: 300px;
  height: 300px;
  background: rgba(118, 75, 162, 0.12);
  bottom: -80px;
  left: -80px;
  animation-delay: -8s;
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.05); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
}

/* Card masuk dengan animasi */
.register-card {
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 48px 40px;
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
  position: relative;
  z-index: 1;
  animation: cardEnter 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  border: 1px solid rgba(255, 255, 255, 0.8);
}

@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(24px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.card-header {
  text-align: center;
  margin-bottom: 32px;
  animation: fadeInDown 0.5s ease 0.15s both;
}

.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
  animation: logoPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
}

@keyframes logoPop {
  from {
    opacity: 0;
    transform: scale(0.6);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  animation: formGroupIn 0.35s ease both;
}

.form-group:nth-child(1) { animation-delay: 0.2s; }
.form-group:nth-child(2) { animation-delay: 0.28s; }
.form-group:nth-child(3) { animation-delay: 0.36s; }
.form-group:nth-child(4) { animation-delay: 0.44s; }
.form-group:nth-child(5) { animation-delay: 0.52s; }
.form-group:nth-child(6) { animation-delay: 0.6s; }
.form-group:nth-child(7) { animation-delay: 0.68s; }
.form-group:nth-child(8) { animation-delay: 0.76s; }
.form-group:nth-child(9) { animation-delay: 0.84s; }

@keyframes formGroupIn {
  from {
    opacity: 0;
    transform: translateX(-8px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
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
  transition: color 0.25s ease, transform 0.25s ease;
}

.form-group input {
  width: 100%;
  padding: 12px 14px 12px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: all 0.25s ease;
  background: white;
  color: #0f172a;
}

.input-wrapper-password input {
  padding-right: 44px;
}

.password-toggle {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: none;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 8px;
  transition: color 0.2s ease, background 0.2s ease;
}

.password-toggle:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.08);
}

.password-toggle:focus {
  outline: none;
  color: #667eea;
  background: rgba(102, 126, 234, 0.12);
}

.form-group input:hover {
  border-color: #cbd5e1;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
  transform: translateY(-1px);
}

.form-group:focus-within .input-icon {
  color: #667eea;
  transform: scale(1.08);
}

.form-group input::placeholder {
  color: #94a3b8;
}

.form-group input.input-error {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
  animation: shake 0.4s ease;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20% { transform: translateX(-6px); }
  40% { transform: translateX(6px); }
  60% { transform: translateX(-4px); }
  80% { transform: translateX(4px); }
}

.error-text {
  display: block;
  margin-top: 4px;
  color: #dc2626;
  font-size: 12px;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.35);
  animation: formGroupIn 0.35s ease 0.9s both;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.45);
  background: linear-gradient(135deg, #5568d3 0%, #6a4190 100%);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
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
  border-radius: 10px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #fecaca;
  animation: fadeIn 0.3s ease, shake 0.4s ease;
}

.error-message svg {
  flex-shrink: 0;
  color: #dc2626;
}

.card-footer {
  text-align: center;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
  animation: fadeIn 0.5s ease 0.95s both;
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
  transition: all 0.25s ease;
  padding: 6px 10px;
  border-radius: 8px;
}

.back-link:hover {
  color: #475569;
  background: rgba(0, 0, 0, 0.04);
  transform: translateX(-2px);
}

.back-link svg {
  flex-shrink: 0;
  transition: transform 0.25s ease;
}

.back-link:hover svg {
  transform: translateX(-2px);
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.25s ease;
}

.link:hover {
  color: #5568d3;
  text-decoration: underline;
}

/* Kurangi motion untuk aksesibilitas */
@media (prefers-reduced-motion: reduce) {
  .register-container,
  .register-container::before,
  .register-container::after,
  .register-card,
  .card-header,
  .logo,
  .form-group,
  .btn-primary,
  .card-footer {
    animation: none !important;
  }
}

@media (max-width: 640px) {
  .register-container {
    padding: 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .register-card {
    padding: 32px 24px;
  }

  .card-header h1 {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .register-container {
    padding: 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .register-card {
    padding: 24px 16px;
  }

  .card-header h1 {
    font-size: 20px;
  }

  .form-group input,
  .form-group select {
    font-size: 16px;
  }

  .btn-primary {
    min-height: 44px;
  }
}
</style>
