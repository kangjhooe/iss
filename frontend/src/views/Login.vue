<template>
  <div class="login-container">
    <div class="login-card">
      <div class="card-header">
        <div class="logo">
          <AppLogo :size="48" />
        </div>
        <h1>Selamat Datang</h1>
        <div v-if="brandingStore.isMaintenanceMode" class="maintenance-notice">
          {{ brandingStore.maintenanceMessage || 'Sistem sedang dalam mode pemeliharaan. Hanya super admin yang dapat masuk.' }}
        </div>
      </div>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label>NIK / Email</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.login" 
              :class="{ 'input-error': fieldErrors.login }"
              @blur="() => validateField('login')"
              placeholder="NIK siswa (16 digit) atau email"
              autocomplete="username"
            />
          </div>
          <span v-if="fieldErrors.login" class="error-text">{{ fieldErrors.login }}</span>
        </div>
        
        <div class="form-group">
          <label>Password</label>
          <div class="input-wrapper input-wrapper-password">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              :type="showPassword ? 'text' : 'password'" 
              v-model="form.password" 
              :class="{ 'input-error': fieldErrors.password }"
              @blur="() => validateField('password')"
              placeholder="Siswa: DDMMYYYY · Staff: password"
              autocomplete="current-password"
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
        
        <div class="forgot-password-link">
          <router-link to="/forgot-password" class="link">Lupa password?</router-link>
        </div>
        
        <button type="submit" :disabled="loading" class="btn-primary">
          <span v-if="!loading">Masuk</span>
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

      <div class="demo-panel">
        <button type="button" class="demo-toggle" @click="showDemo = !showDemo">
          <span>Coba sekolah demo (gratis)</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" :class="{ open: showDemo }">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div v-if="showDemo" class="demo-body">
          <p class="demo-title">{{ DEMO_SCHOOL.name }}</p>
          <p class="demo-note">{{ DEMO_SCHOOL.resetNote }} Jangan masukkan data asli.</p>
          <div class="demo-actions">
            <button
              v-for="role in DEMO_SCHOOL.roles"
              :key="role.key"
              type="button"
              class="demo-fill"
              :disabled="loading"
              @click="fillDemo(role.key)"
            >
              {{ role.label }}
            </button>
          </div>
        </div>
      </div>
      
      <div class="card-footer">
        <p>
          Belum punya akun? 
          <router-link to="/register" class="link">Daftar Sekarang</router-link>
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppBrandingStore } from '@/stores/appBranding'
import { validators } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { useToast } from '@/composables/useToast'
import AppLogo from '@/components/AppLogo.vue'
import { DEMO_SCHOOL } from '@/constants/demoSchool'

const brandingStore = useAppBrandingStore()
const route = useRoute()
const showDemo = ref(false)

const toast = useToast()

const router = useRouter()
const authStore = useAuthStore()

const initialForm = {
  login: '',
  password: ''
}
const form = ref({ ...initialForm })

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const validationRules = {
  login: [
    (value) => validators.required(value, 'NIK atau email wajib diisi'),
    (value) => {
      const v = String(value || '').trim()
      // validateForm treats any truthy return as an error message — success must be null/undefined
      if (/^\d{16}$/.test(v)) return null
      if (v.includes('@')) return validators.email(v, 'Format email tidak valid')
      return 'Masukkan NIK 16 digit (siswa) atau email'
    }
  ],
  password: [
    (value) => validators.required(value, 'Password wajib diisi')
  ]
}

const { fieldErrors, validateField, validateAll, clearErrors, setErrors } = useFormValidation({
  form,
  initialValues: initialForm,
  rules: validationRules
})

onMounted(() => {
  brandingStore.refreshBranding()
  if (route.query.demo === '1' || route.query.demo === 'true') {
    showDemo.value = true
  }
})

function fillDemo(roleKey) {
  clearErrors()
  error.value = ''
  const role = DEMO_SCHOOL.roles.find((r) => r.key === roleKey)
  if (!role) return
  form.value.login = role.login
  form.value.password = role.password
}

const handleLogin = async () => {
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
    await authStore.login({
      login: String(form.value.login || '').trim(),
      password: form.value.password
    })
    toast.success('Login Berhasil', 'Selamat datang kembali!')

    if (authStore.user?.must_change_password) {
      router.push({ name: 'ForceChangePassword' })
      return
    }
    
    // Redirect based on user role
    if (authStore.user?.role === 'super_admin') {
      router.push('/super-admin/dashboard')
    } else if (authStore.user?.role === 'teacher' || authStore.user?.role === 'staff') {
      router.push('/teacher/dashboard')
    } else if (authStore.user?.role === 'student') {
      router.push('/student/dashboard')
    } else {
      router.push('/dashboard')
    }
  } catch (err) {
    console.error('Login error:', err)
    
    // Handle different types of errors
    let errorMessage = 'Terjadi kesalahan saat login'
    
    if (err.formattedMessage) {
      errorMessage = err.formattedMessage
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.response?.data?.error) {
      // Backend returns error field for non-validation errors
      errorMessage = err.response.data.error
    } else if (err.response?.data?.errors) {
      // Validation errors
      const errors = err.response.data.errors
      const firstError = Object.values(errors)[0]
      errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
    } else if (err.message) {
      // Network errors or other errors
      if (err.message.includes('Network Error') || err.code === 'ERR_NETWORK') {
        errorMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.'
      } else if (err.message.includes('timeout')) {
        errorMessage = 'Request timeout. Silakan coba lagi.'
      } else {
        errorMessage = err.message
      }
    } else if (err.response?.status === 401) {
      errorMessage = 'NIK/email atau password salah'
    } else if (err.response?.status === 422) {
      errorMessage = 'Data yang dimasukkan tidak valid'
    } else if (err.response?.status === 429) {
      errorMessage = 'Terlalu banyak percobaan. Silakan tunggu sebentar.'
    } else if (err.response?.status >= 500) {
      errorMessage = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.'
    }
    
    error.value = errorMessage
    toast.error('Login Gagal', errorMessage)
    
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
.login-container {
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

.login-container::before,
.login-container::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.4;
  animation: float 20s ease-in-out infinite;
  pointer-events: none;
}

.login-container::before {
  width: 400px;
  height: 400px;
  background: rgba(5, 150, 105, 0.15);
  top: -100px;
  right: -100px;
  animation-delay: 0s;
}

.login-container::after {
  width: 300px;
  height: 300px;
  background: rgba(4, 120, 87, 0.12);
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
.login-card {
  width: 100%;
  max-width: 440px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 48px 40px;
  box-shadow: 0 4px 24px rgba(5, 150, 105, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
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

.maintenance-notice {
  margin-top: 14px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 13px;
  line-height: 1.45;
  text-align: left;
}

.login-form {
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 20px;
  animation: formGroupIn 0.4s ease both;
}

.form-group:nth-child(1) { animation-delay: 0.25s; }
.form-group:nth-child(2) { animation-delay: 0.35s; }
.form-group:nth-child(3) { animation-delay: 0.45s; }

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
  color: #059669;
  background: rgba(5, 150, 105, 0.08);
}

.password-toggle:focus {
  outline: none;
  color: #059669;
  background: rgba(5, 150, 105, 0.12);
}

.form-group input:hover {
  border-color: #cbd5e1;
}

.form-group input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12);
  transform: translateY(-1px);
}

.form-group:focus-within .input-icon {
  color: #059669;
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

.forgot-password-link {
  text-align: right;
  margin-bottom: 16px;
  margin-top: -8px;
  animation: formGroupIn 0.4s ease 0.5s both;
}

.forgot-password-link .link {
  font-size: 13px;
}

.btn-primary {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
  animation: formGroupIn 0.4s ease 0.55s both;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45);
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 2px 10px rgba(5, 150, 105, 0.3);
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
  animation: fadeIn 0.5s ease 0.6s both;
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
  color: #059669;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.25s ease;
  position: relative;
}

.link:hover {
  color: #047857;
  text-decoration: underline;
}

.demo-panel {
  margin-top: 20px;
  border: 1px solid #d1fae5;
  border-radius: 12px;
  background: #f0fdf4;
  overflow: hidden;
}

.demo-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px 14px;
  border: none;
  background: transparent;
  color: #065f46;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.demo-toggle svg {
  transition: transform 0.2s ease;
}

.demo-toggle svg.open {
  transform: rotate(180deg);
}

.demo-body {
  padding: 0 14px 14px;
}

.demo-title {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 600;
  color: #064e3b;
}

.demo-note {
  margin: 0 0 10px;
  font-size: 12px;
  color: #047857;
  line-height: 1.4;
}

.demo-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.demo-fill {
  flex: 1;
  min-width: 88px;
  padding: 8px 10px;
  border: 1px solid #6ee7b7;
  border-radius: 8px;
  background: #fff;
  color: #047857;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.demo-fill:hover:not(:disabled) {
  background: #ecfdf5;
}

.demo-fill:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Kurangi motion untuk aksesibilitas */
@media (prefers-reduced-motion: reduce) {
  .login-container,
  .login-container::before,
  .login-container::after,
  .login-card,
  .card-header,
  .logo,
  .form-group,
  .forgot-password-link,
  .btn-primary,
  .card-footer {
    animation: none !important;
  }
}

@media (max-width: 640px) {
  .login-container {
    padding: 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .login-card {
    padding: 32px 24px;
  }

  .card-header h1 {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .login-container {
    padding: 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .login-card {
    padding: 24px 16px;
  }

  .card-header h1 {
    font-size: 20px;
  }

  .form-group input {
    font-size: 16px; /* hindari zoom iOS */
  }

  .btn-primary {
    min-height: 44px;
  }
}
</style>
