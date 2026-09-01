<template>    <div class="system-settings-page">
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

      <section class="panel panel-spaced">
        <div class="panel-header">
          <div>
            <h3>Backup Database</h3>
            <p>
              Buat dump MySQL (.sql.gz) lalu unduh kapan saja. Maksimal 10 file disimpan di server.
              File upload di storage tidak termasuk.
            </p>
          </div>
        </div>

        <div class="actions backup-actions">
          <button
            type="button"
            class="btn-primary"
            :disabled="creatingBackup"
            @click="createBackup"
          >
            {{ creatingBackup ? 'Membuat backup...' : 'Buat Backup Sekarang' }}
          </button>
          <button
            type="button"
            class="btn-secondary"
            :disabled="loadingBackups || creatingBackup"
            @click="loadBackups"
          >
            Muat Ulang
          </button>
        </div>

        <p v-if="backupError" class="form-error">{{ backupError }}</p>

        <div v-if="loadingBackups && backups.length === 0" class="backup-empty">
          Memuat daftar backup...
        </div>
        <div v-else-if="backups.length === 0" class="backup-empty">
          Belum ada backup. Klik “Buat Backup Sekarang” untuk membuat file .sql.gz.
        </div>
        <div v-else class="backup-table-wrap">
          <table class="backup-table">
            <thead>
              <tr>
                <th>File</th>
                <th>Ukuran</th>
                <th>Dibuat</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in backups" :key="item.filename">
                <td class="filename">{{ item.filename }}</td>
                <td>{{ formatSize(item.size) }}</td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td class="row-actions">
                  <button
                    type="button"
                    class="btn-link"
                    :disabled="downloading === item.filename"
                    @click="downloadBackup(item)"
                  >
                    {{ downloading === item.filename ? 'Mengunduh...' : 'Unduh' }}
                  </button>
                  <button
                    type="button"
                    class="btn-link btn-danger"
                    :disabled="deleting === item.filename"
                    @click="deleteBackup(item)"
                  >
                    {{ deleting === item.filename ? 'Menghapus...' : 'Hapus' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div></template>

<script setup>
import { ref, onMounted } from 'vue'
import { appBrandingApi } from '@/api/appBranding'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
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

const backups = ref([])
const loadingBackups = ref(false)
const creatingBackup = ref(false)
const downloading = ref(null)
const deleting = ref(null)
const backupError = ref('')

onMounted(async () => {
  await brandingStore.refreshBranding()
  form.value.maintenance_mode = !!brandingStore.maintenanceMode
  form.value.maintenance_message = brandingStore.maintenanceMessage || ''
  await loadBackups()
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

async function loadBackups() {
  loadingBackups.value = true
  backupError.value = ''
  try {
    const res = await superAdminPlatformApi.listDatabaseBackups()
    backups.value = res.data?.data || []
  } catch (err) {
    backupError.value = err.response?.data?.message || err.formattedMessage || 'Gagal memuat daftar backup'
  } finally {
    loadingBackups.value = false
  }
}

async function createBackup() {
  creatingBackup.value = true
  backupError.value = ''
  try {
    const res = await superAdminPlatformApi.createDatabaseBackup()
    const created = res.data?.data
    toast.success('Berhasil', res.data?.message || 'Backup dibuat')
    await loadBackups()
    if (created?.filename) {
      await downloadBackup(created)
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.formattedMessage || 'Gagal membuat backup'
    backupError.value = msg
    toast.error('Gagal', msg)
  } finally {
    creatingBackup.value = false
  }
}

async function downloadBackup(item) {
  const filename = item.filename
  downloading.value = filename
  backupError.value = ''
  try {
    const res = await superAdminPlatformApi.downloadDatabaseBackup(filename)
    const blob = res.data
    if (blob instanceof Blob && blob.type && blob.type.includes('application/json')) {
      const text = await blob.text()
      const parsed = JSON.parse(text)
      throw new Error(parsed.message || 'Gagal mengunduh backup')
    }
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Backup diunduh')
  } catch (err) {
    const msg = err.message || err.response?.data?.message || err.formattedMessage || 'Gagal mengunduh backup'
    backupError.value = msg
    toast.error('Gagal', msg)
  } finally {
    downloading.value = null
  }
}

async function deleteBackup(item) {
  if (!confirm(`Hapus backup ${item.filename}?`)) return
  deleting.value = item.filename
  backupError.value = ''
  try {
    await superAdminPlatformApi.deleteDatabaseBackup(item.filename)
    toast.success('Berhasil', 'Backup dihapus')
    await loadBackups()
  } catch (err) {
    const msg = err.response?.data?.message || err.formattedMessage || 'Gagal menghapus backup'
    backupError.value = msg
    toast.error('Gagal', msg)
  } finally {
    deleting.value = null
  }
}

function formatSize(bytes) {
  const n = Number(bytes) || 0
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('id-ID')
  } catch {
    return iso
  }
}
</script>

<style scoped>
.system-settings-page {
  width: 100%;
  max-width: 860px;
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

.panel-spaced {
  margin-top: 20px;
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

.backup-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
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

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.backup-empty {
  font-size: 14px;
  color: #64748b;
  padding: 12px 0;
}

.backup-table-wrap {
  overflow-x: auto;
}

.backup-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.backup-table th,
.backup-table td {
  text-align: left;
  padding: 10px 8px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.backup-table th {
  color: #64748b;
  font-weight: 600;
}

.backup-table .filename {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  word-break: break-all;
}

.row-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  white-space: nowrap;
}

.btn-link {
  background: none;
  border: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  font-size: 13px;
}

.btn-link:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-danger {
  color: #dc2626;
}

@media (max-width: 768px) {
  .panel-header {
    flex-direction: column;
  }
}
</style>
