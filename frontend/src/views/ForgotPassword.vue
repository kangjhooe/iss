<template>
  <div class="forgot-password-container">
    <div class="forgot-password-card">
      <div class="card-header">
        <div class="logo">
          <AppLogo :size="48" />
        </div>
        <h1>Lupa Sandi</h1>
        <p>Admin sekolah dapat mengajukan reset sandi ke admin sistem. Sandi baru akan dikirim ke email sekolah secara manual.</p>
      </div>
      
      <form @submit.prevent="handleForgotPassword" class="forgot-password-form" v-if="!submitted">
        <div class="form-group">
          <label>Email akun sekolah</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 6L12 13L2 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input 
              type="email" 
              v-model="form.email" 
              :class="{ 'input-error': fieldErrors.email }"
              autocomplete="username"
              placeholder="admin@sekolah.sch.id"
            />
          </div>
          <span v-if="fieldErrors.email" class="error-text">{{ fieldErrors.email }}</span>
        </div>

        <div class="form-group">
          <label>NPSN</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
              <path d="M7 8H17M7 12H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input 
              type="text" 
              v-model="form.npsn" 
              :class="{ 'input-error': fieldErrors.npsn }"
              inputmode="numeric"
              maxlength="8"
              placeholder="8 digit NPSN"
            />
          </div>
          <span v-if="fieldErrors.npsn" class="error-text">{{ fieldErrors.npsn }}</span>
        </div>

        <div class="form-group">
          <label>Nomor HP <span class="optional">(opsional)</span></label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="5" y="2" width="14" height="20" rx="2" stroke="currentColor" stroke-width="2"/>
              <path d="M10 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input 
              type="tel" 
              v-model="form.contact_phone" 
              :class="{ 'input-error': fieldErrors.contact_phone }"
              placeholder="08xxxxxxxxxx"
            />
          </div>
          <span v-if="fieldErrors.contact_phone" class="error-text">{{ fieldErrors.contact_phone }}</span>
        </div>

        <div class="form-group">
          <label>Catatan <span class="optional">(opsional)</span></label>
          <textarea
            v-model="form.note"
            class="note-input"
            :class="{ 'input-error': fieldErrors.note }"
            rows="3"
            maxlength="1000"
            placeholder="Contoh: akun terkunci setelah beberapa kali salah sandi"
          ></textarea>
          <span v-if="fieldErrors.note" class="error-text">{{ fieldErrors.note }}</span>
        </div>

        <p class="role-note">Guru, staf, dan siswa: hubungi admin sekolah Anda.</p>
        
        <button type="submit" :disabled="loading" class="btn-primary">
          <span v-if="!loading">Ajukan reset ke admin</span>
          <span v-else class="loading-spinner">
            <svg class="spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
            Mengirim...
          </span>
        </button>
      </form>

      <div v-if="submitted" class="success-message">
        <div class="success-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2"/>
            <path d="M8 12L11 15L16 9" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2>Permintaan terkirim</h2>
        <p>Jika data cocok dengan akun admin sekolah, admin sistem akan menghubungi Anda melalui email.</p>
        <p class="hint">Sistem tidak mengirim sandi secara otomatis.</p>
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
          Ingat sandi Anda? 
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
import { authApi } from '@/api/auth'
import { validateForm, validators } from '@/utils/validation'
import { useToast } from '@/composables/useToast'
import AppLogo from '@/components/AppLogo.vue'

const toast = useToast()

const form = ref({
  email: '',
  npsn: '',
  contact_phone: '',
  note: ''
})

const loading = ref(false)
const error = ref('')
const submitted = ref(false)
const fieldErrors = ref({
  email: '',
  npsn: '',
  contact_phone: '',
  note: ''
})

const validationRules = {
  email: [
    (value) => validators.required(value, 'Email wajib diisi'),
    (value) => validators.email(value, 'Format email tidak valid')
  ],
  npsn: [
    (value) => validators.required(value, 'NPSN wajib diisi'),
    (value) => validators.npsn(value)
  ],
  contact_phone: [
    (value) => validators.phone(value)
  ]
}

const handleForgotPassword = async () => {
  error.value = ''
  fieldErrors.value = { email: '', npsn: '', contact_phone: '', note: '' }
  
  const validation = validateForm(form.value, validationRules)
  if (!validation.isValid) {
    fieldErrors.value = { ...fieldErrors.value, ...validation.errors }
    error.value = 'Mohon perbaiki kesalahan pada form'
    return
  }
  
  loading.value = true
  
  try {
    await authApi.requestPasswordReset({
      email: form.value.email.trim(),
      npsn: form.value.npsn.trim(),
      contact_phone: form.value.contact_phone.trim() || undefined,
      note: form.value.note.trim() || undefined
    })
    submitted.value = true
    toast.success('Berhasil', 'Permintaan reset sandi telah dikirim')
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Gagal mengirim permintaan reset sandi'
    error.value = errorMessage
    toast.error('Gagal', errorMessage)
    
    if (err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      Object.keys(serverErrors).forEach(key => {
        if (Object.prototype.hasOwnProperty.call(fieldErrors.value, key)) {
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
.forgot-password-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 24px;
}

.forgot-password-card {
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
  line-height: 1.5;
}

.optional {
  font-weight: 400;
  color: #94a3b8;
}

.role-note {
  margin: 0 0 8px;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.note-input {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  min-height: 80px;
  background: white;
  color: #0f172a;
  box-sizing: border-box;
}

.note-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.note-input.input-error {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.forgot-password-form {
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
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
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
  background: #059669;
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
  background: #047857;
}

.btn-primary:active:not(:disabled) {
  background: #065f46;
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

.success-message p.hint {
  font-size: 13px;
  color: #94a3b8;
  margin-top: 12px;
}

.success-message strong {
  color: #0f172a;
  font-weight: 600;
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
  color: #059669;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s ease;
}

.link:hover {
  color: #047857;
  text-decoration: underline;
}

@media (max-width: 640px) {
  .forgot-password-container {
    padding: 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .forgot-password-card {
    padding: 32px 24px;
  }

  .card-header h1 {
    font-size: 24px;
  }
}

@media (max-width: 480px) {
  .forgot-password-container {
    padding: 12px;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
  }

  .forgot-password-card {
    padding: 24px 16px;
  }

  .card-header h1 {
    font-size: 20px;
  }

  .form-group input {
    font-size: 16px;
  }

  .btn-primary {
    min-height: 44px;
  }
}
</style>
