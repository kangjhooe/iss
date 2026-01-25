<template>
  <Layout>
    <div class="module-access-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Kelola Akses Modul</h2>
            <p>Atur akses modul untuk setiap guru</p>
          </div>
          <button @click="saveAll" :disabled="saving" class="btn-primary">
            <svg v-if="!saving" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M17 21V13H7V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 3V8H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span v-else>Menyimpan...</span>
            <span v-if="!saving">Simpan Semua Perubahan</span>
          </button>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
              <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
              <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
            </circle>
          </svg>
        </div>
        <p>Memuat data...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p>{{ error }}</p>
        <button @click="loadData" class="btn-primary">Coba Lagi</button>
      </div>

      <div v-else class="content-section">
        <div class="search-bar">
          <input 
            v-model="searchQuery" 
            placeholder="Cari nama guru atau email..."
            class="search-input"
          />
        </div>

        <div v-if="filteredTeachers.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <h3>Tidak ada guru ditemukan</h3>
          <p>{{ searchQuery ? 'Coba ubah kata kunci pencarian' : 'Belum ada guru yang terdaftar' }}</p>
        </div>

        <div v-else class="teachers-grid">
          <div 
            v-for="teacher in filteredTeachers" 
            :key="teacher.id" 
            class="teacher-card"
            :class="{ 'has-changes': hasChanges(teacher.id) }"
          >
            <div class="teacher-header">
              <div class="teacher-info">
                <h3>{{ teacher.name }}</h3>
                <p class="teacher-email">{{ teacher.email }}</p>
                <div v-if="teacher.teacher_profile" class="teacher-meta">
                  <span v-if="teacher.teacher_profile.nip">NIP: {{ teacher.teacher_profile.nip }}</span>
                  <span v-if="teacher.teacher_profile.nuptk">NUPTK: {{ teacher.teacher_profile.nuptk }}</span>
                </div>
              </div>
              <div v-if="hasChanges(teacher.id)" class="change-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Ada perubahan</span>
              </div>
            </div>

            <div class="modules-section">
              <h4>Akses Modul</h4>
              <div class="modules-grid">
                <label 
                  v-for="module in availableModules" 
                  :key="module.key" 
                  class="module-checkbox"
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
            </div>
          </div>
        </div>
      </div>
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
  max-width: 1400px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.header-content p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loading-spinner {
  margin-bottom: 16px;
}

.loading-state p,
.error-state p {
  color: #64748b;
  font-size: 14px;
  margin-top: 16px;
}

.error-state svg {
  color: #ef4444;
  margin-bottom: 16px;
}

.search-bar {
  margin-bottom: 24px;
}

.search-input {
  width: 100%;
  max-width: 400px;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 8px 0;
}

.empty-state p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.teachers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 24px;
}

.teacher-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  transition: all 0.2s ease;
}

.teacher-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.teacher-card.has-changes {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.teacher-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
}

.teacher-info h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.teacher-email {
  font-size: 13px;
  color: #64748b;
  margin: 0 0 8px 0;
}

.teacher-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  color: #94a3b8;
}

.change-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  color: #92400e;
}

.change-badge svg {
  width: 14px;
  height: 14px;
}

.modules-section h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 12px 0;
}

.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px;
}

.module-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #ffffff;
  font-size: 13px;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
}

.module-checkbox:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.module-checkbox input {
  accent-color: #6366f1;
  cursor: pointer;
}

.module-checkbox input:checked + span {
  font-weight: 500;
  color: #1e293b;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .teachers-grid {
    grid-template-columns: 1fr;
  }
  
  .header-content {
    flex-direction: column;
  }
  
  .modules-grid {
    grid-template-columns: 1fr;
  }
}
</style>