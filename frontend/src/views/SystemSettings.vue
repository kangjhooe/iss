<template>
  <Layout>
    <div class="system-settings-page">
      <div class="page-header">
        <div>
          <h2>Pengaturan Sistem</h2>
          <p>Mode pemeliharaan dan konfigurasi platform</p>
        </div>
      </div>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h3>Mode Pemeliharaan</h3>
            <p>Saat aktif, hanya super admin yang dapat login dan memakai sistem.</p>
          </div>
          <span :class="form.maintenance_mode ? 'badge-warn' : 'badge-ok'">
            {{ form.maintenance_mode ? 'Aktif' : 'Nonaktif' }}
          </span>
        </div>

        <form @submit.prevent="handleSave">
          <label class="switch-row">
            <input v-model="form.maintenance_mode" type="checkbox" />
            <span>Aktifkan mode pemeliharaan</span>
          </label>

          <div class="form-group">
            <label>Pesan untuk pengguna</label>
            <textarea
              v-model="form.maintenance_message"
              class="form-control"
              rows="3"
              maxlength="1000"
              placeholder="Sistem sedang dalam mode pemeliharaan. Silakan coba lagi nanti."
            ></textarea>
          </div>

          <p v-if="error" class="form-error">{{ error }}</p>
          <p v-if="success" class="form-success">{{ success }}</p>

          <div class="actions">
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { appBrandingApi } from '@/api/appBranding'
import { useAppBrandingStore } from '@/stores/appBranding'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const brandingStore = useAppBrandingStore()
const saving = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  maintenance_mode: false,
  maintenance_message: ''
})

onMounted(async () => {
  await brandingStore.refreshBranding()
  form.value.maintenance_mode = !!brandingStore.maintenanceMode
  form.value.maintenance_message = brandingStore.maintenanceMessage || ''
})

const handleSave = async () => {
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    const res = await appBrandingApi.updateMaintenance({
      maintenance_mode: form.value.maintenance_mode,
      maintenance_message: form.value.maintenance_message || null
    })
    brandingStore.setMaintenance(res.data?.data || {})
    form.value.maintenance_mode = !!brandingStore.maintenanceMode
    form.value.maintenance_message = brandingStore.maintenanceMessage || ''
    success.value = res.data?.message || 'Pengaturan disimpan'
    toast.success('Berhasil', success.value)
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal menyimpan pengaturan'
    toast.error('Gagal', error.value)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.system-settings-page {
  width: 100%;
  max-width: 720px;
}

.page-header {
  margin-bottom: 20px;
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

.panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 20px;
}

.panel-header h3 {
  margin: 0 0 4px;
  font-size: 17px;
  color: #0f172a;
}

.panel-header p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.badge-ok,
.badge-warn {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-ok {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.badge-warn {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.switch-row {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 16px;
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

.form-error {
  color: #dc2626;
  font-size: 13px;
}

.form-success {
  color: #059669;
  font-size: 13px;
}

.actions {
  margin-top: 12px;
}

.btn-primary {
  background: #059669;
  color: white;
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .panel-header {
    flex-direction: column;
  }
}
</style>
