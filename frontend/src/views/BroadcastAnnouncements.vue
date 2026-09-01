<template>    <div class="broadcast-page">
      <div class="page-header">
        <div>
          <h2>Broadcast Pengumuman</h2>
          <p>Kirim pengumuman ke admin institusi melalui notifikasi sistem</p>
        </div>
        <button type="button" class="btn-primary btn-compact" @click="showCompose = true">Buat Pengumuman</button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="list" :items="5" />
      </div>

      <div v-else-if="items.length === 0" class="empty-state">
        <h3>Belum ada pengumuman</h3>
        <p>Kirim broadcast pertama untuk menginformasikan admin sekolah.</p>
        <button type="button" class="btn-primary" @click="showCompose = true">Buat Pengumuman</button>
      </div>

      <div v-else class="broadcast-list">
        <article v-for="item in items" :key="item.id" class="broadcast-card">
          <div class="card-top">
            <h3>{{ item.title }}</h3>
            <span class="meta">{{ formatDate(item.published_at || item.created_at) }}</span>
          </div>
          <p class="body">{{ item.body }}</p>
          <div class="card-foot">
            <span>{{ item.recipients_count }} penerima · {{ targetLabel(item.target) }}</span>
            <span v-if="item.creator">oleh {{ item.creator.name }}</span>
          </div>
        </article>
      </div>

      <div v-if="showCompose" class="modal-overlay" @click.self="closeCompose">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Buat Pengumuman</h3>
            <button type="button" class="btn-close" @click="closeCompose">×</button>
          </div>
          <form class="modal-body" @submit.prevent="handleSend">
            <div class="form-group">
              <label>Judul *</label>
              <input v-model="form.title" type="text" required maxlength="200" class="form-control" />
            </div>
            <div class="form-group">
              <label>Isi *</label>
              <textarea v-model="form.body" required maxlength="5000" rows="5" class="form-control"></textarea>
            </div>
            <div class="form-group">
              <label>Penerima *</label>
              <select v-model="form.target" class="form-control">
                <option value="all_admins">Semua admin institusi</option>
                <option value="selected_institutions">Institusi tertentu</option>
              </select>
            </div>
            <div v-if="form.target === 'selected_institutions'" class="form-group">
              <label>Pilih Institusi *</label>
              <div class="inst-picker">
                <label v-for="inst in institutions" :key="inst.id" class="inst-option">
                  <input v-model="form.institution_ids" type="checkbox" :value="inst.id" />
                  <span>{{ inst.name }}</span>
                </label>
              </div>
            </div>
            <p v-if="error" class="form-error">{{ error }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeCompose">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Mengirim...' : 'Kirim' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
import { institutionApi } from '@/api/institution'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const items = ref([])
const institutions = ref([])
const showCompose = ref(false)
const error = ref('')

const form = ref({
  title: '',
  body: '',
  target: 'all_admins',
  institution_ids: []
})

const targetLabel = (t) => (t === 'selected_institutions' ? 'Institusi terpilih' : 'Semua admin')

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const loadItems = async () => {
  loading.value = true
  try {
    const res = await superAdminPlatformApi.getBroadcasts({ per_page: 30 })
    items.value = res.data?.data || []
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat pengumuman')
  } finally {
    loading.value = false
  }
}

const loadInstitutions = async () => {
  try {
    const res = await institutionApi.getAll({ per_page: 100, is_active: true })
    institutions.value = res.data?.data || []
  } catch {
    institutions.value = []
  }
}

const closeCompose = () => {
  showCompose.value = false
  error.value = ''
  form.value = { title: '', body: '', target: 'all_admins', institution_ids: [] }
}

const handleSend = async () => {
  error.value = ''
  if (form.value.target === 'selected_institutions' && form.value.institution_ids.length === 0) {
    error.value = 'Pilih minimal satu institusi'
    return
  }
  saving.value = true
  try {
    const payload = {
      title: form.value.title.trim(),
      body: form.value.body.trim(),
      target: form.value.target
    }
    if (form.value.target === 'selected_institutions') {
      payload.institution_ids = form.value.institution_ids
    }
    const res = await superAdminPlatformApi.createBroadcast(payload)
    toast.success('Terkirim', res.data?.message || 'Pengumuman terkirim')
    closeCompose()
    await loadItems()
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mengirim pengumuman'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadItems(), loadInstitutions()])
})
</script>

<style scoped>
.broadcast-page { width: 100%; max-width: 900px; }
.page-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.page-header h2 { margin: 0 0 4px; font-size: 24px; color: #0f172a; }
.page-header p { margin: 0; color: #64748b; font-size: 14px; }
.empty-state, .broadcast-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
}
.empty-state { text-align: center; }
.empty-state h3 { margin: 0 0 8px; }
.empty-state p { color: #64748b; margin: 0 0 16px; }
.broadcast-list { display: flex; flex-direction: column; gap: 12px; }
.card-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}
.card-top h3 { margin: 0; font-size: 17px; color: #0f172a; }
.meta { font-size: 12px; color: #94a3b8; white-space: nowrap; }
.body {
  margin: 0 0 12px;
  color: #475569;
  white-space: pre-wrap;
  font-size: 14px;
  line-height: 1.5;
}
.card-foot {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 12px;
  color: #94a3b8;
  flex-wrap: wrap;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}
.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  overflow: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
}
.modal-header h3 { margin: 0; }
.btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  color: #64748b;
}
.modal-body { padding: 20px; }
.form-group { margin-bottom: 14px; }
.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: #334155;
}
.form-control {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
}
.inst-picker {
  max-height: 180px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px;
}
.inst-option {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 6px 4px;
  font-size: 13px;
  font-weight: 400 !important;
}
.form-error { color: #dc2626; font-size: 13px; }
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}
.btn-primary, .btn-secondary {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary { background: #059669; color: white; border: none; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-secondary { background: white; color: #334155; border: 1px solid #e2e8f0; }
.btn-compact { padding: 8px 12px; font-size: 13px; }

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .page-header .btn-compact {
    width: 100%;
    justify-content: center;
  }

  .card-top,
  .card-foot {
    flex-direction: column;
    align-items: flex-start;
  }

  .broadcast-card {
    padding: 16px;
  }

  .modal-overlay {
    align-items: flex-start;
  }

  .modal-content {
    max-width: 100%;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions > * {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .broadcast-page {
    max-width: 100%;
  }

  .page-header h2 {
    font-size: 1.25rem;
  }
}
</style>
