<template>
  <div class="verify-email-container">
    <div class="verify-email-card">
      <div class="card-header">
        <div class="logo">
          <AppLogo :size="48" />
        </div>
        <h1>Verifikasi Email</h1>
        <p v-if="!emailVerified">Masukkan kode verifikasi yang dikirim ke email Anda</p>
        <p v-else>Email Anda telah berhasil diverifikasi!</p>
      </div>
      
      <form @submit.prevent="handleVerifyEmail" class="verify-email-form" v-if="!emailVerified">
        <div class="form-group">
          <label>Email</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
          <label>Kode Verifikasi</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.verification_code" 
              :class="{ 'input-error': fieldErrors.verification_code }"
              @blur="() => {
                const validation = validateForm({ verification_code: form.verification_code }, { verification_code: validationRules.verification_code })
                fieldErrors.verification_code = validation.errors.verification_code || ''
              }"
              placeholder="Masukkan kode verifikasi"
            />
          </div>
          <span v-if="fieldErrors.verification_code" class="error-text">{{ fieldErrors.verification_code }}</span>
        </div>
        
        <button type="submit" :disabled="loading" class="btn-primary">
          <span v-if="!loading">Verifikasi Email</span>
          <span v-else class="loading-spinner">
            <svg class="spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
            Memverifikasi...
          </span>
        </button>

        <div class="resend-section">
          <p>Tidak menerima kode?</p>
          <button type="button" @click="handleResendVerification" :disabled="resending || resendCooldown > 0" class="btn-link">
            {{ resendCooldown > 0 ? `Kirim ulang (${resendCooldown}s)` : 'Kirim ulang kode verifikasi' }}
          </button>
        </div>
      </form>

      <div v-if="emailVerified" class="success-message">
        <div class="success-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2"/>
            <path d="M8 12L11 15L16 9" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2>Email Terverifikasi!</h2>
        <p>Email Anda telah berhasil diverifikasi. Silakan login untuk melanjutkan.</p>
      </div>
      
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
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authApi } from '@/api/auth'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'
import AppLogo from '@/components/AppLogo.vue'

const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = ref({
  email: '',
  verification_code: ''
})

const loading = ref(false)
const resending = ref(false)
const error = ref('')
const emailVerified = ref(false)
const resendCooldown = ref(0)
const fieldErrors = ref({
  email: '',
  verification_code: ''
})

const validationRules = {
  email: [
    (value) => validators.required(value, 'Email wajib diisi'),
    (value) => validators.email(value, 'Format email tidak valid')
  ],
  verification_code: [
    (value) => validators.required(value, 'Kode verifikasi wajib diisi')
  ]
}

onMounted(() => {
  // Get email from query params if available
  if (route.query.email) {
    form.value.email = route.query.email
  }
  if (route.query.code) {
    form.value.verification_code = route.query.code
    // Auto verify if both email and code are provided
    if (form.value.email && form.value.verification_code) {
      handleVerifyEmail()
    }
  }
})

const handleVerifyEmail = async () => {
  error.value = ''
  fieldErrors.value = { email: '', verification_code: '' }
  
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    fieldErrors.value = validation.errors
    error.value = 'Mohon perbaiki kesalahan pada form'
    return
  }
  
  loading.value = true
  
  try {
    await authApi.verifyEmail(form.value)
    emailVerified.value = true
    toast.success('Berhasil', 'Email berhasil diverifikasi')
    
    // Redirect to login after 3 seconds
    setTimeout(() => {
      router.push('/login')
    }, 3000)
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Gagal memverifikasi email'
    error.value = errorMessage
    toast.error('Gagal', errorMessage)
    
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

const handleResendVerification = async () => {
  if (!form.value.email) {
    error.value = 'Email wajib diisi'
    return
  }

  const emailValidation = validateForm({ email: form.value.email }, { email: validationRules.email })
  if (!emailValidation.isValid) {
    fieldErrors.value.email = emailValidation.errors.email || ''
    error.value = 'Email tidak valid'
    return
  }

  resending.value = true
  error.value = ''
  
  try {
    await authApi.resendVerificationEmail(form.value.email)
    toast.success('Berhasil', 'Kode verifikasi baru telah dikirim ke email Anda')
    resendCooldown.value = 60 // 60 seconds cooldown
    
    const interval = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0) {
        clearInterval(interval)
      }
    }, 1000)
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Gagal mengirim ulang kode verifikasi'
    error.value = errorMessage
    toast.error('Gagal', errorMessage)
  } finally {
    resending.value = false
  }
}
</script>

<style scoped>
.verify-email-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 24px;
}

.verify-email-card {
  width: 100%;
  max-width: 440px;
  background: white;
  border-radius: 16px;
  padding: 48px 40px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
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

.verify-email-form {
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 20px;
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

.resend-section {
  text-align: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.resend-section p {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 8px;
}

.btn-link {
  background: none;
  border: none;
  color: #667eea;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  padding: 0;
  text-decoration: underline;
  transition: color 0.15s ease;
}

.btn-link:hover:not(:disabled) {
  color: #5568d3;
}

.btn-link:disabled {
  opacity: 0.5;
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

.success-message {
  text-align: center;
  padding: 24px 0;
  margin-bottom: 24px;
}

.success-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.success-message h2 {
  font-size: 24px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 12px;
}

.success-message p {
  color: #64748b;
  font-size: 14px;
  margin-bottom: 8px;
  line-height: 1.6;
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
  .verify-email-container {
    padding: 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .verify-email-card {
    padding: 32px 24px;
  }

  .card-header h1 {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .verify-email-container {
    padding: 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .verify-email-card {
    padding: 24px 16px;
  }

  .card-header h1 {
    font-size: 20px;
  }

  .btn-primary {
    min-height: 44px;
  }
}
</style>
