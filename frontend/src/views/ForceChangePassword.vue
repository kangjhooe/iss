<template>
  <div class="force-change-page">
    <div class="force-change-card">
      <div class="card-header">
        <AppLogo :size="48" />
        <h1>Ganti Sandi Wajib</h1>
        <p>
          Anda masih memakai sandi awal (tanggal lahir). Demi keamanan, ganti sandi sebelum melanjutkan.
        </p>
      </div>

      <form @submit.prevent="submit" class="form">
        <div class="form-group">
          <label for="current-password">Sandi saat ini <span class="required">*</span></label>
          <input
            id="current-password"
            v-model="form.current_password"
            type="password"
            placeholder="Tanggal lahir DDMMYYYY"
            autocomplete="current-password"
            :class="{ 'input-error': errors.current_password }"
          />
          <span v-if="errors.current_password" class="error-text">{{ errors.current_password }}</span>
          <span class="hint">Sandi awal = tanggal lahir, contoh 15 Maret 2010 → 15032010</span>
        </div>

        <div class="form-group">
          <label for="new-password">Sandi baru <span class="required">*</span></label>
          <input
            id="new-password"
            v-model="form.password"
            type="password"
            placeholder="Minimal 8 karakter, huruf dan angka"
            autocomplete="new-password"
            :class="{ 'input-error': errors.password }"
          />
          <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
        </div>

        <div class="form-group">
          <label for="confirm-password">Konfirmasi sandi baru <span class="required">*</span></label>
          <input
            id="confirm-password"
            v-model="form.password_confirmation"
            type="password"
            placeholder="Ulangi sandi baru"
            autocomplete="new-password"
            :class="{ 'input-error': errors.password_confirmation }"
          />
          <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation }}</span>
        </div>

        <p v-if="error" class="error-banner">{{ error }}</p>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Menyimpan...' : 'Simpan Sandi Baru' }}
        </button>
      </form>

      <button type="button" class="btn-logout" :disabled="loading" @click="logout">
        Keluar
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import AppLogo from '@/components/AppLogo.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const errors = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const loading = ref(false)
const error = ref('')

function clearErrors() {
  errors.current_password = ''
  errors.password = ''
  errors.password_confirmation = ''
  error.value = ''
}

function getDefaultRoute(role) {
  if (role === 'super_admin') return '/super-admin/dashboard'
  if (role === 'teacher' || role === 'staff') return '/teacher/dashboard'
  if (role === 'student') return '/student/dashboard'
  return '/dashboard'
}

async function submit() {
  clearErrors()
  if (!form.current_password) {
    errors.current_password = 'Sandi saat ini wajib diisi'
    return
  }
  if (!form.password) {
    errors.password = 'Sandi baru wajib diisi'
    return
  }
  if (form.password.length < 8) {
    errors.password = 'Sandi minimal 8 karakter'
    return
  }
  if (!/[a-zA-Z]/.test(form.password) || !/\d/.test(form.password)) {
    errors.password = 'Sandi harus mengandung huruf dan angka'
    return
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Konfirmasi sandi tidak cocok'
    return
  }

  loading.value = true
  try {
    const res = await authApi.changePassword({
      current_password: form.current_password,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    if (res.data?.user) {
      authStore.user = res.data.user
    } else if (authStore.user) {
      authStore.user = { ...authStore.user, must_change_password: false }
    }
    toast.success('Berhasil', 'Sandi berhasil diubah')
    router.replace(getDefaultRoute(authStore.user?.role))
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      Object.keys(data.errors).forEach((key) => {
        if (key in errors) {
          errors[key] = Array.isArray(data.errors[key]) ? data.errors[key][0] : data.errors[key]
        }
      })
    } else {
      error.value = data?.message || 'Gagal mengubah sandi'
    }
  } finally {
    loading.value = false
  }
}

async function logout() {
  await authStore.logout()
}
</script>

<style scoped>
.force-change-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: linear-gradient(160deg, #eef2f7 0%, #f8fafc 45%, #e8eef5 100%);
}

.force-change-card {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 16px;
  padding: 2rem 1.75rem;
  box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
}

.card-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.card-header h1 {
  margin: 0.75rem 0 0.5rem;
  font-size: 1.35rem;
  color: #0f172a;
}

.card-header p {
  margin: 0;
  color: #64748b;
  font-size: 0.925rem;
  line-height: 1.5;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: #334155;
}

.required {
  color: #dc2626;
}

.form-group input {
  width: 100%;
  padding: 0.7rem 0.85rem;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 0.95rem;
}

.form-group input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.input-error {
  border-color: #dc2626 !important;
}

.error-text {
  display: block;
  margin-top: 0.3rem;
  color: #dc2626;
  font-size: 0.8rem;
}

.hint {
  display: block;
  margin-top: 0.3rem;
  color: #64748b;
  font-size: 0.78rem;
}

.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  padding: 0.65rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
}

.btn-primary {
  width: 100%;
  margin-top: 0.25rem;
  padding: 0.8rem;
  border: none;
  border-radius: 10px;
  background: #2563eb;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-logout {
  width: 100%;
  margin-top: 0.75rem;
  padding: 0.65rem;
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-logout:hover {
  color: #0f172a;
}
</style>
