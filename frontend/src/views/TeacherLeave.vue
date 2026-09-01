<template>    <div class="leave-page">
      <div class="toolbar">
        <div>
          <h2 class="page-title">Cuti Saya</h2>
          <p class="page-desc">Ajukan cuti dan pantau status persetujuan.</p>
        </div>
        <button type="button" class="btn-primary" @click="openModal">Ajukan Cuti</button>
      </div>

      <div class="filters filters-inline">
        <select v-model="status" class="filter-select" @change="load">
          <option value="">Semua Status</option>
          <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="5" :columns="5" />
      </div>
      <div v-else-if="!items.length" class="empty-state">
        <h3 class="empty-title">Belum ada pengajuan</h3>
        <p class="empty-desc">Ajukan cuti pertama Anda.</p>
      </div>
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Jenis</th>
              <th>Periode</th>
              <th>Hari</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>
                <div class="cell-title">{{ item.leave_type_label }}</div>
                <div class="cell-sub">{{ item.reason || '-' }}</div>
              </td>
              <td>{{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</td>
              <td>{{ item.duration_days }}</td>
              <td><span :class="['status-badge', `status-${item.status}`]">{{ item.status_label }}</span></td>
              <td>
                <TableAction
                  v-if="item.status === 'pending' || item.status === 'approved'"
                  kind="cancel"
                  title="Batalkan"
                  @click="cancel(item)"
                />
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Ajukan Cuti</h3>
            <button type="button" class="btn-close" @click="showModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submit">
            <div class="form-group">
              <label>Jenis cuti *</label>
              <select v-model="form.leave_type" required>
                <option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai *</label>
                <input v-model="form.start_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Selesai *</label>
                <input v-model="form.end_date" type="date" required />
              </div>
            </div>
            <div class="form-group">
              <label>Alasan</label>
              <textarea v-model="form.reason" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Lampiran PDF</label>
              <input type="file" accept="application/pdf" @change="onFile" />
            </div>
            <div v-if="error" class="error-message">{{ error }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Mengirim...' : 'Ajukan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { useToast } from '@/composables/useToast'
import { employeeLeaveApi } from '@/api/kepegawaian'

const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const error = ref('')
const items = ref([])
const status = ref('')
const leaveTypes = ref({})
const statuses = ref({})
const form = reactive({
  leave_type: 'tahunan',
  start_date: '',
  end_date: '',
  reason: '',
  attachment: null,
})

function formatDate(value) {
  if (!value) return '-'
  try {
    return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return value
  }
}

async function loadMeta() {
  try {
    const { data } = await employeeLeaveApi.meta()
    leaveTypes.value = data.leave_types || {}
    statuses.value = data.statuses || {}
  } catch {
    leaveTypes.value = {
      tahunan: 'Cuti Tahunan',
      sakit: 'Cuti Sakit',
      melahirkan: 'Cuti Melahirkan',
      penting: 'Cuti Alasan Penting',
      tanpa_gaji: 'Cuti di Luar Tanggungan',
      lainnya: 'Lainnya',
    }
    statuses.value = {
      pending: 'Menunggu',
      approved: 'Disetujui',
      rejected: 'Ditolak',
      cancelled: 'Dibatalkan',
    }
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await employeeLeaveApi.getMy({
      status: status.value || undefined,
      per_page: 50,
    })
    items.value = data.data || []
  } catch {
    toast.error('Gagal memuat cuti saya')
  } finally {
    loading.value = false
  }
}

function openModal() {
  error.value = ''
  Object.assign(form, {
    leave_type: 'tahunan',
    start_date: '',
    end_date: '',
    reason: '',
    attachment: null,
  })
  showModal.value = true
}

function onFile(e) {
  form.attachment = e.target.files?.[0] || null
}

async function submit() {
  saving.value = true
  error.value = ''
  try {
    const fd = new FormData()
    fd.append('leave_type', form.leave_type)
    fd.append('start_date', form.start_date)
    fd.append('end_date', form.end_date)
    if (form.reason) fd.append('reason', form.reason)
    if (form.attachment) fd.append('attachment', form.attachment)
    await employeeLeaveApi.createMy(fd)
    toast.success('Pengajuan cuti dikirim')
    showModal.value = false
    load()
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Gagal mengajukan'
  } finally {
    saving.value = false
  }
}

async function cancel(item) {
  if (!window.confirm('Batalkan pengajuan cuti ini?')) return
  try {
    await employeeLeaveApi.cancel(item.id)
    toast.success('Pengajuan dibatalkan')
    load()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal membatalkan')
  }
}

onMounted(async () => {
  await loadMeta()
  load()
})
</script>

<style scoped>
.leave-page { display: flex; flex-direction: column; gap: 1rem; }
.toolbar { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
.page-title { margin: 0; font-size: 1.25rem; }
.page-desc { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.9rem; }
.filters-inline { display: flex; gap: 0.5rem; }
.filter-select, .btn-primary, .btn-secondary {
  padding: 0.5rem 0.9rem; border-radius: 8px; min-height: 40px; border: 1px solid #d1d5db;
}
.btn-primary { background: var(--primary, #2563eb); color: #fff; border: none; cursor: pointer; }
.btn-secondary { background: #f3f4f6; cursor: pointer; }
.table-container { overflow-x: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 0.9rem; border-bottom: 1px solid #eee; text-align: left; }
.data-table th { font-size: 0.8rem; text-transform: uppercase; color: #6b7280; background: #f9fafb; }
.cell-title { font-weight: 600; }
.cell-sub { font-size: 0.85rem; color: #6b7280; }
.status-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-cancelled { background: #e5e7eb; color: #374151; }
.btn-sm { padding: 0.3rem 0.55rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; }
.empty-state { text-align: center; padding: 2.5rem 1rem; color: #6b7280; }
.empty-title { margin: 0 0 0.35rem; color: #111; }
.muted { color: #9ca3af; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center;
  z-index: 1000; padding: 1rem;
}
.modal-content { background: #fff; border-radius: 12px; width: min(560px, 95%); max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #eee; }
.modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; }
.btn-close { border: none; background: transparent; font-size: 1.5rem; cursor: pointer; }
.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.form-group label { font-size: 0.85rem; font-weight: 600; }
.form-group input, .form-group select, .form-group textarea {
  padding: 0.55rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.error-message { color: #b91c1c; font-size: 0.9rem; }
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
}
</style>
