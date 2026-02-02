<template>
  <Layout>
    <div class="module-access-page">
      <header class="page-header">
        <div class="header-left">
          <div class="header-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M19.4 15C19.1292 15.6172 19.2584 16.3378 19.73 16.82L19.79 16.88C20.1656 17.2552 20.3764 17.7642 20.3764 18.295C20.3764 18.8258 20.1656 19.3348 19.79 19.71C19.4147 20.0856 18.9057 20.2964 18.3749 20.2964C17.8441 20.2964 17.3351 20.0856 16.96 19.71L16.9 19.65C16.4178 19.1784 15.6972 19.0492 15.08 19.32C14.4752 19.5852 14.0672 20.1692 14.01 20.81L14 22C14 22.5304 13.7893 23.0391 13.4142 23.4142C13.0391 23.7893 12.5304 24 12 24C11.4696 24 10.9609 23.7893 10.5858 23.4142C10.2107 23.0391 10 22.5304 10 22V21.91C9.96924 20.7435 9.44502 19.6651 8.56 19L4.21 16.88C3.78432 16.646 3.43204 16.2937 3.19799 15.868C2.96395 15.4422 2.85913 14.9612 2.9 14.48L3 12C3.00002 10.8053 3.44094 9.65925 4.24134 8.77873C5.04175 7.89821 6.14298 7.34636 7.33 7.22L9 7V5C9 4.46957 9.21071 3.96086 9.58579 3.58579C9.96086 3.21071 10.4696 3 11 3H13C13.5304 3 14.0391 3.21071 14.4142 3.58579C14.7893 3.96086 15 4.46957 15 5V7.09C16.33 7.25 17.5 7.89 18.31 8.87L19.4 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1>Kelola Akses Modul</h1>
            <p class="header-desc">Atur modul yang dapat diakses setiap guru</p>
          </div>
        </div>
        <button
          v-if="!loading && !error && unsavedCount > 0"
          @click="saveAll"
          :disabled="saving"
          class="btn-save-header"
        >
          <svg v-if="!saving" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 21V13H7V21M7 3V8H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span v-else class="btn-spinner"></span>
          <span>{{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </header>

      <div v-if="loading" class="state-wrap state-loading">
        <div class="spinner"></div>
        <p>Memuat daftar guru dan modul...</p>
      </div>

      <div v-else-if="error" class="state-wrap state-error">
        <div class="state-icon state-icon-error">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <p class="state-title">Gagal memuat data</p>
        <p class="state-desc">{{ error }}</p>
        <button @click="loadData" class="btn-primary">Coba Lagi</button>
      </div>

      <template v-else>
        <div class="toolbar">
          <div class="summary">
            <span class="summary-pill">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              {{ filteredTeachers.length }} guru
            </span>
            <span class="summary-pill">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              {{ availableModules.length }} modul
            </span>
          </div>
          <div class="search-wrap">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, email, NIP, atau NUPTK..."
              class="search-input"
            />
          </div>
        </div>

        <div v-if="filteredTeachers.length === 0" class="state-wrap state-empty">
          <div class="state-icon state-icon-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>Tidak ada guru ditemukan</h3>
          <p>{{ searchQuery ? 'Ubah kata kunci pencarian atau hapus filter.' : 'Belum ada guru dengan akun login. Tambah guru di Data Guru dan pastikan akun dibuat.' }}</p>
        </div>

        <div v-else class="cards">
          <article
            v-for="teacher in filteredTeachers"
            :key="teacher.id"
            class="card"
            :class="{ 'card--dirty': hasChanges(teacher.id) }"
          >
            <div class="card-head">
              <div class="card-avatar" :style="{ background: avatarColor(teacher.name) }">
                {{ teacherInitial(teacher.name) }}
              </div>
              <div class="card-meta">
                <h3 class="card-name">{{ teacher.name }}</h3>
                <a :href="`mailto:${teacher.email}`" class="card-email">{{ teacher.email }}</a>
                <div v-if="hasProfile(teacher)" class="card-badges">
                  <span v-if="teacher.teacher_profile?.nip" class="badge">NIP {{ teacher.teacher_profile.nip }}</span>
                  <span v-if="teacher.teacher_profile?.nuptk" class="badge">NUPTK {{ teacher.teacher_profile.nuptk }}</span>
                </div>
              </div>
              <div v-if="hasChanges(teacher.id)" class="card-dirty">
                <span>Belum disimpan</span>
              </div>
            </div>
            <section class="card-section">
              <h4>Akses Modul</h4>
              <div class="modules">
                <label
                  v-for="module in availableModules"
                  :key="module.key"
                  class="module-chip"
                  :class="{ 'module-chip--on': isModuleEnabled(teacher.id, module.key) }"
                >
                  <input
                    type="checkbox"
                    :value="module.key"
                    :checked="isModuleEnabled(teacher.id, module.key)"
                    @change="toggleModule(teacher.id, module.key)"
                  />
                  <span>{{ module.label }}</span>
                </label>
              </div>
            </section>
          </article>
        </div>

        <Transition name="slide-up">
          <div v-if="unsavedCount > 0 && !saving" class="save-bar">
            <span>{{ unsavedCount }} perubahan belum disimpan</span>
            <div class="save-bar-actions">
              <button @click="discardChanges" class="btn-ghost">Batal</button>
              <button @click="saveAll" class="btn-primary">Simpan</button>
            </div>
          </div>
        </Transition>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { permissionApi } from '@/api/permissions'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const searchQuery = ref('')
const teachers = ref([])
const availableModules = ref([])
const pendingChanges = ref({}) // { userId: { permission_keys: [...] } }

const filteredTeachers = computed(() => {
  if (!searchQuery.value) return teachers.value
  
  const query = searchQuery.value.toLowerCase()
  return teachers.value.filter(teacher => 
    teacher.name.toLowerCase().includes(query) ||
    teacher.email.toLowerCase().includes(query) ||
    (teacher.teacher_profile?.nip && teacher.teacher_profile.nip.toLowerCase().includes(query)) ||
    (teacher.teacher_profile?.nuptk && teacher.teacher_profile.nuptk.toLowerCase().includes(query))
  )
})

const unsavedCount = computed(() => {
  return teachers.value.filter(t => hasChanges(t.id)).length
})

function teacherInitial(name) {
  if (!name || !name.trim()) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase().slice(0, 2)
  }
  return name.slice(0, 2).toUpperCase()
}

const AVATAR_COLORS = [
  'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
  'linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%)',
  'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
  'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
  'linear-gradient(135deg, #ef4444 0%, #f87171 100%)',
]
function avatarColor(name) {
  if (!name) return AVATAR_COLORS[0]
  let n = 0
  for (let i = 0; i < name.length; i++) n += name.charCodeAt(i)
  return AVATAR_COLORS[n % AVATAR_COLORS.length]
}

function hasProfile(teacher) {
  return teacher?.teacher_profile && (teacher.teacher_profile.nip || teacher.teacher_profile.nuptk)
}

function discardChanges() {
  pendingChanges.value = {}
  toast.info('Dibatalkan', 'Perubahan belum disimpan telah dibatalkan')
}

const loadData = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const [modulesRes, teachersRes] = await Promise.all([
      permissionApi.getAll(),
      permissionApi.getTeachers()
    ])
    
    availableModules.value = modulesRes.data.data || []
    teachers.value = teachersRes.data.data || []
    pendingChanges.value = {}
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat data'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const isModuleEnabled = (userId, moduleKey) => {
  const teacher = teachers.value.find(t => t.id === userId)
  if (!teacher) return false
  
  const currentPermissions = pendingChanges.value[userId]?.permission_keys ?? teacher.permissions
  return currentPermissions.includes(moduleKey)
}

const toggleModule = (userId, moduleKey) => {
  const teacher = teachers.value.find(t => t.id === userId)
  if (!teacher) return
  
  if (!pendingChanges.value[userId]) {
    pendingChanges.value[userId] = {
      permission_keys: [...teacher.permissions]
    }
  }
  
  const permissions = pendingChanges.value[userId].permission_keys
  const index = permissions.indexOf(moduleKey)
  
  if (index > -1) {
    permissions.splice(index, 1)
  } else {
    permissions.push(moduleKey)
  }
}

const hasChanges = (userId) => {
  if (!pendingChanges.value[userId]) return false
  
  const teacher = teachers.value.find(t => t.id === userId)
  if (!teacher) return false
  
  const original = [...teacher.permissions].sort()
  const changed = [...pendingChanges.value[userId].permission_keys].sort()
  
  return JSON.stringify(original) !== JSON.stringify(changed)
}

const saveAll = async () => {
  const changesToSave = Object.entries(pendingChanges.value).filter(([userId]) => hasChanges(userId))
  
  if (changesToSave.length === 0) {
    toast.info('Info', 'Tidak ada perubahan yang perlu disimpan')
    return
  }
  
  saving.value = true
  
  try {
    const promises = changesToSave.map(([userId, changes]) =>
      permissionApi.updateUserPermissions(userId, changes.permission_keys)
    )
    
    await Promise.all(promises)
    
    toast.success('Berhasil', `Akses modul untuk ${changesToSave.length} guru berhasil diperbarui`)
    
    await loadData()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menyimpan perubahan')
    console.error(err)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.module-access-page {
  max-width: 1200px;
  padding-bottom: 100px;
}

/* ----- Header ----- */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.page-header h1 {
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
  letter-spacing: -0.02em;
}

.header-desc {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.btn-save-header {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-save-header:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.btn-save-header:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ----- States ----- */
.state-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 64px 24px;
}

.state-loading .spinner,
.state-loading p {
  color: #64748b;
}

.state-loading .spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  margin-bottom: 16px;
  animation: spin 0.8s linear infinite;
}

.state-loading p {
  font-size: 14px;
  margin: 0;
}

.state-error .state-icon-error {
  color: #ef4444;
  margin-bottom: 16px;
}

.state-error .state-title {
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.state-error .state-desc {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 20px 0;
}

.state-empty .state-icon-empty {
  color: #cbd5e1;
  margin-bottom: 20px;
}

.state-empty h3 {
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.state-empty p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

/* ----- Toolbar ----- */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.summary {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.summary-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background: #f1f5f9;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
}

.summary-pill svg {
  color: #6366f1;
  flex-shrink: 0;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 360px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 11px 14px 11px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* ----- Cards ----- */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 20px;
}

.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.card--dirty {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.card-head {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 20px 20px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.card-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
  letter-spacing: -0.02em;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.card-meta {
  flex: 1;
  min-width: 0;
}

.card-name {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 4px 0;
  line-height: 1.3;
}

.card-email {
  font-size: 13px;
  color: #6366f1;
  text-decoration: none;
  display: block;
  margin-bottom: 8px;
  word-break: break-all;
}

.card-email:hover {
  text-decoration: underline;
}

.card-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.badge {
  font-size: 11px;
  padding: 4px 8px;
  background: #f1f5f9;
  color: #64748b;
  border-radius: 6px;
  font-weight: 500;
}

.card-dirty {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 600;
  color: #b45309;
  background: #fef3c7;
  padding: 6px 10px;
  border-radius: 8px;
}

.card-section {
  padding: 16px 20px 20px;
}

.card-section h4 {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 12px 0;
}

.modules {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.module-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 13px;
  color: #475569;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s ease;
}

.module-chip:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.module-chip--on {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border-color: transparent;
  color: white;
}

.module-chip input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.module-chip input:checked + span {
  font-weight: 500;
}

/* ----- Save bar ----- */
.save-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 24px;
  background: #fff;
  border-top: 1px solid #e2e8f0;
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.08);
  z-index: 50;
}

.save-bar span {
  font-size: 14px;
  font-weight: 500;
  color: #475569;
}

.save-bar-actions {
  display: flex;
  gap: 10px;
}

.btn-ghost {
  padding: 10px 18px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  background: #fff;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.btn-ghost:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-left {
    flex-direction: row;
  }

  .cards {
    grid-template-columns: 1fr;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-wrap {
    max-width: none;
  }

  .save-bar {
    flex-direction: column;
    align-items: stretch;
    padding: 14px 16px;
  }
}
</style>