<template>
  <Layout>
    <div class="page account-settings-page">
      <p class="page-desc">Ubah nama, email, atau sandi akun Anda.</p>

      <div class="settings-grid">
        <!-- Ubah profil (nama & email) -->
        <section class="section card" aria-labelledby="profile-heading">
          <div class="section-header">
            <span class="section-icon section-icon--profile" aria-hidden="true">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <h2 id="profile-heading" class="section-title">Profil</h2>
          </div>
          <p v-if="profileSuccess" class="success-text">Profil berhasil diperbarui.</p>
          <p v-if="profileSuccess && profileEmailChanged" class="success-note">Jika Anda mengubah email, periksa kotak masuk email baru untuk tautan verifikasi.</p>
          <form @submit.prevent="submitProfile" class="form">
            <div class="form-group">
              <label for="account-name">Nama <span class="required">*</span></label>
              <input
                id="account-name"
                v-model="profileForm.name"
                type="text"
                placeholder="Nama lengkap"
                maxlength="255"
                :class="{ 'input-error': profileErrors.name }"
                :aria-invalid="!!profileErrors.name"
                :aria-describedby="profileErrors.name ? 'profile-name-error' : undefined"
              />
              <span v-if="profileErrors.name" id="profile-name-error" class="error-text" role="alert">{{ profileErrors.name }}</span>
            </div>
            <div class="form-group">
              <label for="account-email">Email <span class="required">*</span></label>
              <input
                id="account-email"
                v-model="profileForm.email"
                type="email"
                placeholder="email@contoh.com"
                maxlength="255"
                autocomplete="email"
                :class="{ 'input-error': profileErrors.email }"
                :aria-invalid="!!profileErrors.email"
                :aria-describedby="profileErrors.email ? 'profile-email-error' : undefined"
              />
              <span v-if="profileErrors.email" id="profile-email-error" class="error-text" role="alert">{{ profileErrors.email }}</span>
            </div>
            <button type="submit" class="btn-primary" :disabled="profileLoading" :aria-busy="profileLoading">
              {{ profileLoading ? 'Menyimpan...' : 'Simpan Profil' }}
            </button>
          </form>
        </section>

        <!-- Ubah sandi -->
        <section class="section card" aria-labelledby="password-heading">
          <div class="section-header">
            <span class="section-icon section-icon--password" aria-hidden="true">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3v2M12 19v2M3 12h2M19 12h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
            <div>
              <h2 id="password-heading" class="section-title">Ubah Sandi</h2>
              <p class="section-desc">Gunakan sandi baru minimal 8 karakter dan mengandung huruf serta angka.</p>
            </div>
          </div>
          <form @submit.prevent="submitPassword" class="form">
            <div class="form-group">
              <label for="current-password">Sandi saat ini <span class="required">*</span></label>
              <input
                id="current-password"
                v-model="passwordForm.current_password"
                type="password"
                placeholder="Masukkan sandi saat ini"
                autocomplete="current-password"
                :class="{ 'input-error': passwordErrors.current_password }"
                :aria-invalid="!!passwordErrors.current_password"
                :aria-describedby="passwordErrors.current_password ? 'password-current-error' : undefined"
              />
              <span v-if="passwordErrors.current_password" id="password-current-error" class="error-text" role="alert">{{ passwordErrors.current_password }}</span>
            </div>
            <div class="form-group">
              <label for="new-password">Sandi baru <span class="required">*</span></label>
              <input
                id="new-password"
                v-model="passwordForm.password"
                type="password"
                placeholder="Sandi baru (min. 8 karakter, huruf + angka)"
                autocomplete="new-password"
                :class="{ 'input-error': passwordErrors.password }"
                :aria-invalid="!!passwordErrors.password"
                :aria-describedby="passwordErrors.password ? 'password-new-error' : 'password-hint'"
              />
              <p id="password-hint" class="form-hint">Minimal 8 karakter, kombinasi huruf dan angka.</p>
              <span v-if="passwordErrors.password" id="password-new-error" class="error-text" role="alert">{{ passwordErrors.password }}</span>
            </div>
            <div class="form-group">
              <label for="new-password-confirm">Konfirmasi sandi baru <span class="required">*</span></label>
              <input
                id="new-password-confirm"
                v-model="passwordForm.password_confirmation"
                type="password"
                placeholder="Ulangi sandi baru"
                autocomplete="new-password"
                :class="{ 'input-error': passwordErrors.password_confirmation }"
                :aria-invalid="!!passwordErrors.password_confirmation"
                :aria-describedby="passwordErrors.password_confirmation ? 'password-confirm-error' : undefined"
              />
              <span v-if="passwordErrors.password_confirmation" id="password-confirm-error" class="error-text" role="alert">{{ passwordErrors.password_confirmation }}</span>
            </div>
            <button type="submit" class="btn-primary" :disabled="passwordLoading" :aria-busy="passwordLoading">
              {{ passwordLoading ? 'Mengubah...' : 'Ubah Sandi' }}
            </button>
          </form>
        </section>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import { useToast } from '@/composables/useToast'
import Layout from '@/components/Layout.vue'

const toast = useToast()
const authStore = useAuthStore()

const profileForm = ref({
  name: '',
  email: ''
})
const profileErrors = ref({ name: '', email: '' })
const profileLoading = ref(false)
const profileSuccess = ref(false)
const profileEmailChanged = ref(false)

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const passwordErrors = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const passwordLoading = ref(false)

function initProfileFromUser() {
  if (authStore.user) {
    profileForm.value.name = authStore.user.name || ''
    profileForm.value.email = authStore.user.email || ''
  }
}

initProfileFromUser()
watch(() => authStore.user, () => initProfileFromUser(), { deep: true })

onMounted(async () => {
  if (!authStore.user && authStore.isAuthenticated) {
    try {
      await authStore.fetchUser()
      initProfileFromUser()
    } catch {
      // ignore
    }
  }
})

function setProfileErrors(errors) {
  profileErrors.value = {
    name: Array.isArray(errors?.name) ? errors.name[0] : errors?.name || '',
    email: Array.isArray(errors?.email) ? errors.email[0] : errors?.email || ''
  }
}

async function submitProfile() {
  profileErrors.value = { name: '', email: '' }
  profileSuccess.value = false
  if (!profileForm.value.name?.trim()) {
    profileErrors.value.name = 'Nama wajib diisi'
    return
  }
  if (!profileForm.value.email?.trim()) {
    profileErrors.value.email = 'Email wajib diisi'
    return
  }
  const email = profileForm.value.email.trim()
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    profileErrors.value.email = 'Format email tidak valid'
    return
  }

  profileLoading.value = true
  try {
    const previousEmail = authStore.user?.email
    const res = await authApi.updateProfile({
      name: profileForm.value.name.trim(),
      email
    })
    if (res?.data?.user) {
      authStore.user = res.data.user
      profileEmailChanged.value = previousEmail !== undefined && previousEmail !== res.data.user.email
    }
    profileSuccess.value = true
    toast.success('Berhasil', 'Profil berhasil diperbarui')
  } catch (err) {
    profileSuccess.value = false
    profileEmailChanged.value = false
    const data = err.response?.data
    if (data?.errors) setProfileErrors(data.errors)
    else toast.error('Gagal memperbarui profil', data?.message || 'Profil tidak dapat diperbarui. Coba lagi.')
  } finally {
    profileLoading.value = false
  }
}

function setPasswordErrors(errors) {
  passwordErrors.value = {
    current_password: Array.isArray(errors?.current_password) ? errors.current_password[0] : errors?.current_password || '',
    password: Array.isArray(errors?.password) ? errors.password[0] : errors?.password || '',
    password_confirmation: Array.isArray(errors?.password_confirmation) ? errors.password_confirmation[0] : errors?.password_confirmation || ''
  }
}

async function submitPassword() {
  passwordErrors.value = { current_password: '', password: '', password_confirmation: '' }
  const { current_password, password, password_confirmation } = passwordForm.value
  if (!current_password) {
    passwordErrors.value.current_password = 'Sandi saat ini wajib diisi'
    return
  }
  if (!password) {
    passwordErrors.value.password = 'Sandi baru wajib diisi'
    return
  }
  if (password.length < 8) {
    passwordErrors.value.password = 'Sandi minimal 8 karakter'
    return
  }
  if (!/[a-zA-Z]/.test(password) || !/\d/.test(password)) {
    passwordErrors.value.password = 'Sandi harus mengandung huruf dan angka'
    return
  }
  if (password !== password_confirmation) {
    passwordErrors.value.password_confirmation = 'Konfirmasi sandi tidak cocok'
    return
  }

  passwordLoading.value = true
  try {
    const res = await authApi.changePassword({
      current_password: current_password,
      password,
      password_confirmation
    })
    if (res.data?.user) {
      authStore.user = res.data.user
    } else if (authStore.user) {
      authStore.user = { ...authStore.user, must_change_password: false }
    }
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    toast.success('Berhasil', 'Sandi berhasil diubah')
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) setPasswordErrors(data.errors)
    else toast.error('Gagal mengubah sandi', data?.message || 'Kata sandi tidak dapat diubah. Pastikan sandi lama benar dan coba lagi.')
  } finally {
    passwordLoading.value = false
  }
}
</script>

<style scoped>
.account-settings-page {
  max-width: 100%;
  width: 100%;
}

.settings-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 900px) {
  .settings-grid {
    grid-template-columns: 1fr 1fr;
    gap: 1.75rem;
  }
}

.page-desc {
  color: #64748b;
  font-size: 0.9375rem;
  margin: 0 0 1.75rem 0;
  line-height: 1.5;
}

.section {
  margin-bottom: 0;
}

.section.card {
  background: #fff;
  border-radius: 16px;
  padding: 1.75rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.2s ease;
  min-height: 0;
}

.section.card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.section-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.section-header .section-desc {
  margin: 0.25rem 0 0 0;
}

.section-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.section-icon--profile {
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.12) 0%, rgba(4, 120, 87, 0.12) 100%);
  color: #059669;
}

.section-icon--password {
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.12) 0%, rgba(4, 120, 87, 0.12) 100%);
  color: #059669;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.section-desc {
  color: #64748b;
  font-size: 0.8125rem;
  margin: 0 0 1rem 0;
  line-height: 1.45;
}

.success-text {
  margin: 0 0 1rem 0;
  padding: 0.75rem 1rem;
  background: #ecfdf5;
  color: #059669;
  font-size: 0.875rem;
  border-radius: 10px;
  border: 1px solid #a7f3d0;
}

.success-note {
  margin: 0 0 1rem 0;
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  color: #64748b;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #059669;
}

.form-hint {
  margin: 0.25rem 0 0 0;
  font-size: 0.75rem;
  color: #64748b;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-of-type {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
}

.required {
  color: #dc2626;
}

.form-group input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.875rem;
  color: #0f172a;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.form-group input.input-error {
  border-color: #dc2626;
}

.form-group input.input-error:focus {
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}

.form-group input::placeholder {
  color: #94a3b8;
}

.error-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #dc2626;
}

.btn-primary {
  padding: 0.625rem 1.25rem;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s ease, transform 0.05s ease;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.95;
}

.btn-primary:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .account-settings-page {
    max-width: 100%;
  }

  .settings-grid {
    gap: 1.25rem;
  }

  .section.card {
    padding: 1.25rem;
    border-radius: 12px;
  }

  .section-header {
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
  }

  .section-icon {
    width: 40px;
    height: 40px;
  }

  .section-icon svg {
    width: 20px;
    height: 20px;
  }

  .page-desc {
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
  }
}
</style>
