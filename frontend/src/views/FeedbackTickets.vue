<template>
  <Layout>
    <div class="feedback-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>{{ isSuperAdmin ? 'Inbox Feedback' : 'Lapor Bug & Request Fitur' }}</h2>
            <p>
              {{ isSuperAdmin
                ? 'Tinjau laporan bug dan request fitur dari admin sekolah'
                : 'Kirim laporan bug atau usulan fitur ke tim platform (super admin)' }}
            </p>
          </div>
          <button
            v-if="!isSuperAdmin"
            type="button"
            class="btn-primary btn-compact"
            @click="openCreateModal"
          >
            + Buat Laporan
          </button>
        </div>
      </div>

      <div class="filter-tabs">
        <button
          v-for="tab in statusTabs"
          :key="String(tab.value)"
          type="button"
          :class="['tab', { active: filterStatus === tab.value }]"
          @click="setStatusFilter(tab.value)"
        >
          {{ tab.label }}
          <template v-if="tab.value === 'open' && openCount != null"> ({{ openCount }})</template>
        </button>
      </div>

      <div class="type-filter">
        <button
          v-for="opt in typeOptions"
          :key="String(opt.value)"
          type="button"
          :class="['chip', { active: filterType === opt.value }]"
          @click="setTypeFilter(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" :cell-widths="['120px', '1fr', '100px', '100px', '140px']" />
      </div>

      <div v-else-if="tickets.length === 0" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3>Belum ada tiket</h3>
        <p>{{ emptyHint }}</p>
        <button
          v-if="!isSuperAdmin"
          type="button"
          class="btn-primary"
          @click="openCreateModal"
        >
          Buat laporan pertama
        </button>
      </div>

      <div v-else class="tickets-list">
        <div v-for="ticket in tickets" :key="ticket.id" class="ticket-card">
          <div class="ticket-header">
            <div class="ticket-info">
              <div class="ticket-badges">
                <span :class="['type-badge', `type-${ticket.type}`]">{{ typeLabel(ticket.type) }}</span>
                <span :class="['priority-badge', `priority-${ticket.priority}`]">{{ priorityLabel(ticket.priority) }}</span>
                <span v-if="ticket.module" class="module-badge">{{ ticket.module }}</span>
              </div>
              <h3>{{ ticket.title }}</h3>
              <p v-if="isSuperAdmin" class="institution-name">{{ ticket.institution?.name || '-' }}</p>
            </div>
            <span :class="['status-badge', `status-${ticket.status}`]">{{ statusLabel(ticket.status) }}</span>
          </div>

          <p class="ticket-desc" :class="{ clamped: !expandedIds.has(ticket.id) && isLong(ticket.description) }">
            {{ ticket.description }}
          </p>
          <button
            v-if="isLong(ticket.description)"
            type="button"
            class="btn-expand"
            @click="toggleExpand(ticket.id)"
          >
            {{ expandedIds.has(ticket.id) ? 'Sembunyikan' : 'Selengkapnya' }}
          </button>

          <div class="ticket-details">
            <div class="detail-row">
              <span class="label">Pengirim:</span>
              <span class="value">{{ ticket.submitter?.name || '-' }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Tanggal:</span>
              <span class="value">{{ formatDate(ticket.created_at) }}</span>
            </div>
            <div v-if="ticket.admin_note" class="detail-row">
              <span class="label">Catatan admin:</span>
              <span class="value admin-note">{{ ticket.admin_note }}</span>
            </div>
            <div v-if="ticket.handler" class="detail-row">
              <span class="label">Ditangani:</span>
              <span class="value">{{ ticket.handler.name }} · {{ formatDate(ticket.handled_at) }}</span>
            </div>
          </div>

          <div v-if="isSuperAdmin" class="ticket-actions">
            <button
              v-if="isActiveStatus(ticket.status)"
              type="button"
              class="btn-resolve"
              :disabled="processing"
              @click="openResolveModal(ticket)"
            >
              Selesaikan
            </button>
            <button type="button" class="btn-update" @click="openUpdateModal(ticket)">
              Perbarui Status
            </button>
          </div>
        </div>
      </div>

      <div v-if="meta.last_page > 1 && !loading" class="pagination-bar">
        <span class="pagination-info">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
        <div class="pagination-btns">
          <button
            type="button"
            class="btn-page"
            :disabled="meta.current_page <= 1"
            @click="loadTickets(meta.current_page - 1)"
          >
            Sebelumnya
          </button>
          <button
            type="button"
            class="btn-page"
            :disabled="meta.current_page >= meta.last_page"
            @click="loadTickets(meta.current_page + 1)"
          >
            Berikutnya
          </button>
        </div>
      </div>

      <!-- Create / Update modals: Teleport ke body agar tidak tertutup sidebar/topbar -->
      <Teleport to="body">
        <div v-if="showCreateModal" class="feedback-modal-overlay" @click.self="showCreateModal = false">
          <div class="feedback-modal-content">
            <div class="modal-header">
              <h3>Buat Laporan</h3>
              <button type="button" class="btn-close" @click="showCreateModal = false">×</button>
            </div>
            <form class="modal-body" @submit.prevent="handleCreate">
              <div class="form-group">
                <label>Tipe *</label>
                <select v-model="createForm.type" class="form-control" required>
                  <option value="bug">Lapor Bug</option>
                  <option value="feature">Request Fitur</option>
                </select>
              </div>
              <div class="form-group">
                <label>Judul *</label>
                <input
                  v-model="createForm.title"
                  type="text"
                  class="form-control"
                  maxlength="200"
                  placeholder="Ringkas masalah atau usulan..."
                  required
                />
              </div>
              <div class="form-group">
                <label>Deskripsi *</label>
                <textarea
                  v-model="createForm.description"
                  class="form-control"
                  rows="5"
                  maxlength="5000"
                  placeholder="Jelaskan langkah reproduksi, hasil yang diharapkan, atau detail request fitur..."
                  required
                ></textarea>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Modul terkait</label>
                  <select v-model="createForm.module" class="form-control">
                    <option value="">— Opsional —</option>
                    <option v-for="m in moduleOptions" :key="m" :value="m">{{ m }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Prioritas</label>
                  <select v-model="createForm.priority" class="form-control">
                    <option value="low">Rendah</option>
                    <option value="medium">Sedang</option>
                    <option value="high">Tinggi</option>
                  </select>
                </div>
              </div>
              <p v-if="createError" class="form-error">{{ createError }}</p>
              <div class="modal-actions">
                <button type="button" class="btn-secondary" @click="showCreateModal = false">Batal</button>
                <button type="submit" class="btn-primary" :disabled="processing">
                  {{ processing ? 'Mengirim...' : 'Kirim' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showUpdateModal" class="feedback-modal-overlay" @click.self="showUpdateModal = false">
          <div class="feedback-modal-content">
            <div class="modal-header">
              <h3>{{ updateForm.status === 'resolved' ? 'Selesaikan Tiket' : 'Perbarui Tiket' }}</h3>
              <button type="button" class="btn-close" @click="showUpdateModal = false">×</button>
            </div>
            <form class="modal-body" @submit.prevent="handleUpdate">
              <p class="update-title">{{ selectedTicket?.title }}</p>
              <div class="form-group">
                <label>Status *</label>
                <select v-model="updateForm.status" class="form-control" required>
                  <option value="open">Terbuka</option>
                  <option value="in_progress">Sedang diproses</option>
                  <option value="resolved">Selesai</option>
                  <option value="closed">Ditutup</option>
                  <option value="rejected">Ditolak</option>
                </select>
              </div>
              <div class="form-group">
                <label>Prioritas</label>
                <select v-model="updateForm.priority" class="form-control">
                  <option value="low">Rendah</option>
                  <option value="medium">Sedang</option>
                  <option value="high">Tinggi</option>
                </select>
              </div>
              <div class="form-group">
                <label>Catatan untuk sekolah</label>
                <textarea
                  v-model="updateForm.admin_note"
                  class="form-control"
                  rows="4"
                  maxlength="2000"
                  placeholder="Opsional: update, alasan, atau langkah selanjutnya..."
                ></textarea>
              </div>
              <p v-if="updateError" class="form-error">{{ updateError }}</p>
              <div class="modal-actions">
                <button type="button" class="btn-secondary" @click="showUpdateModal = false">Batal</button>
                <button type="submit" class="btn-primary" :disabled="processing">
                  {{ processing ? 'Menyimpan...' : (updateForm.status === 'resolved' ? 'Selesaikan' : 'Simpan') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { feedbackTicketApi } from '@/api/feedbackTicket'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()

const isSuperAdmin = computed(() => authStore.user?.role === 'super_admin')

const loading = ref(true)
const tickets = ref([])
const filterStatus = ref('open')
const filterType = ref('')
const openCount = ref(null)
const showCreateModal = ref(false)
const showUpdateModal = ref(false)
const selectedTicket = ref(null)
const processing = ref(false)
const createError = ref('')
const updateError = ref('')
const expandedIds = ref(new Set())
const meta = ref({ current_page: 1, last_page: 1 })

const createForm = ref({
  type: 'bug',
  title: '',
  description: '',
  module: '',
  priority: 'medium'
})

const updateForm = ref({
  status: 'open',
  priority: 'medium',
  admin_note: ''
})

const statusTabs = [
  { value: 'open', label: 'Aktif' },
  { value: 'resolved', label: 'Selesai' },
  { value: 'closed', label: 'Ditutup' },
  { value: 'rejected', label: 'Ditolak' },
  { value: '', label: 'Semua' }
]

const typeOptions = [
  { value: '', label: 'Semua tipe' },
  { value: 'bug', label: 'Bug' },
  { value: 'feature', label: 'Fitur' }
]

const moduleOptions = [
  'Dashboard',
  'Siswa',
  'Guru',
  'Kelas',
  'Absensi',
  'Nilai',
  'Jadwal',
  'PPDB',
  'Keuangan',
  'Ujian Online',
  'Persuratan',
  'Perpustakaan',
  'BK / Pelanggaran',
  'Mutasi',
  'PKL',
  'BKK',
  'UKS',
  'Kepegawaian',
  'Berita & Galeri',
  'Portal Orang Tua',
  'Lainnya'
]

const emptyHint = computed(() => {
  if (filterStatus.value === 'open') return 'Tidak ada tiket aktif saat ini.'
  if (filterStatus.value) return 'Tidak ada tiket dengan filter status ini.'
  return isSuperAdmin.value
    ? 'Belum ada laporan dari admin sekolah.'
    : 'Kirim laporan pertama jika menemukan bug atau ingin mengusulkan fitur.'
})

const extractError = (err) => {
  const data = err.response?.data
  if (data?.errors) {
    const first = Object.values(data.errors).flat()[0]
    if (first) return first
  }
  return data?.message || 'Terjadi kesalahan'
}

const loadTickets = async (page = 1) => {
  loading.value = true
  try {
    const params = { page, per_page: 15 }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterType.value) params.type = filterType.value

    const response = await feedbackTicketApi.getAll(params)
    const payload = response.data || {}
    const list = Array.isArray(payload.data) ? payload.data : []
    tickets.value = list
    meta.value = {
      current_page: payload.current_page ?? payload.meta?.current_page ?? 1,
      last_page: payload.last_page ?? payload.meta?.last_page ?? 1
    }

    if (isSuperAdmin.value) {
      try {
        const countRes = await feedbackTicketApi.getOpenCount()
        openCount.value = countRes.data.count ?? 0
      } catch {
        openCount.value = null
      }
    } else {
      openCount.value = null
    }
  } catch (err) {
    toast.error('Gagal memuat', 'Data tiket tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const setStatusFilter = (value) => {
  if (filterStatus.value === value) return
  filterStatus.value = value
  loadTickets(1)
}

const setTypeFilter = (value) => {
  if (filterType.value === value) return
  filterType.value = value
  loadTickets(1)
}

const isLong = (text) => (text || '').length > 280

const toggleExpand = (id) => {
  const next = new Set(expandedIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  expandedIds.value = next
}

const openCreateModal = () => {
  createForm.value = {
    type: 'bug',
    title: '',
    description: '',
    module: '',
    priority: 'medium'
  }
  createError.value = ''
  showCreateModal.value = true
}

const openUpdateModal = (ticket) => {
  selectedTicket.value = ticket
  updateForm.value = {
    status: ticket.status,
    priority: ticket.priority || 'medium',
    admin_note: ticket.admin_note || ''
  }
  updateError.value = ''
  showUpdateModal.value = true
}

const openResolveModal = (ticket) => {
  openUpdateModal(ticket)
  updateForm.value.status = 'resolved'
}

const isActiveStatus = (status) => status === 'open' || status === 'in_progress'

const applyStatusFilterAfterUpdate = (status) => {
  if (status === 'resolved' || status === 'closed' || status === 'rejected') {
    filterStatus.value = status
    return
  }
  filterStatus.value = 'open'
}

const handleUpdate = async () => {
  updateError.value = ''
  processing.value = true
  const nextStatus = updateForm.value.status
  try {
    await feedbackTicketApi.update(selectedTicket.value.id, {
      status: nextStatus,
      priority: updateForm.value.priority,
      admin_note: updateForm.value.admin_note?.trim() || null
    })
    toast.success(
      'Berhasil',
      nextStatus === 'resolved' ? 'Tiket ditandai selesai' : 'Tiket berhasil diperbarui'
    )
    showUpdateModal.value = false
    applyStatusFilterAfterUpdate(nextStatus)
    await loadTickets(1)
  } catch (err) {
    updateError.value = extractError(err)
    toast.error('Gagal', updateError.value)
  } finally {
    processing.value = false
  }
}

const handleCreate = async () => {
  createError.value = ''
  processing.value = true
  try {
    const payload = {
      type: createForm.value.type,
      title: createForm.value.title.trim(),
      description: createForm.value.description.trim(),
      priority: createForm.value.priority
    }
    if (createForm.value.module) payload.module = createForm.value.module

    await feedbackTicketApi.create(payload)
    toast.success('Berhasil', 'Laporan berhasil dikirim ke super admin')
    showCreateModal.value = false
    filterStatus.value = 'open'
    await loadTickets(1)
  } catch (err) {
    createError.value = extractError(err)
    toast.error('Gagal', createError.value)
  } finally {
    processing.value = false
  }
}

const typeLabel = (type) => (type === 'bug' ? 'Bug' : type === 'feature' ? 'Fitur' : type)
const priorityLabel = (p) => ({ low: 'Rendah', medium: 'Sedang', high: 'Tinggi' }[p] || p)
const statusLabel = (s) => ({
  open: 'Terbuka',
  in_progress: 'Diproses',
  resolved: 'Selesai',
  closed: 'Ditutup',
  rejected: 'Ditolak'
}[s] || s)

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(async () => {
  await authStore.fetchUser()
  const role = authStore.user?.role
  if (!['super_admin', 'institution_admin', 'admin', 'teacher', 'staff'].includes(role)) {
    toast.error('Akses Ditolak', 'Anda tidak memiliki akses ke halaman ini')
    return
  }
  await loadTickets(1)
})

watch([showCreateModal, showUpdateModal], ([createOpen, updateOpen]) => {
  document.body.style.overflow = (createOpen || updateOpen) ? 'hidden' : ''
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
.feedback-page {
  width: 100%;
  max-width: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  flex-wrap: wrap;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.filter-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  border-bottom: 2px solid #e2e8f0;
  flex-wrap: wrap;
}

.tab {
  padding: 12px 24px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #64748b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: -2px;
}

.tab:hover {
  color: #475569;
}

.tab.active {
  color: #059669;
  border-bottom-color: #059669;
}

.type-filter {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.chip {
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.chip:hover {
  border-color: #cbd5e1;
}

.chip.active {
  background: #ecfdf5;
  border-color: #10b981;
  color: #047857;
  font-weight: 600;
}

.loading-wrap {
  padding: 8px 0;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 4px;
}

.empty-state h3 {
  margin: 0;
  color: #1e293b;
  font-size: 18px;
}

.empty-state p {
  margin: 0 0 8px;
  font-size: 14px;
}

.tickets-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.ticket-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.ticket-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.ticket-badges {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.ticket-info h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
}

.institution-name {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 14px;
}

.ticket-desc {
  color: #475569;
  font-size: 14px;
  line-height: 1.55;
  white-space: pre-wrap;
  margin: 0 0 8px;
}

.ticket-desc.clamped {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
  white-space: normal;
}

.btn-expand {
  border: none;
  background: none;
  color: #059669;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-bottom: 12px;
}

.ticket-details {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 4px;
}

.detail-row {
  display: flex;
  gap: 12px;
  font-size: 14px;
}

.detail-row .label {
  font-weight: 600;
  color: #64748b;
  min-width: 120px;
  flex-shrink: 0;
}

.detail-row .value {
  color: #1e293b;
}

.admin-note {
  color: #0f766e !important;
}

.type-badge,
.priority-badge,
.module-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
}

.module-badge {
  background: #f1f5f9;
  color: #475569;
}

.type-bug {
  background: #fee2e2;
  color: #dc2626;
}

.type-feature {
  background: #dbeafe;
  color: #2563eb;
}

.priority-low {
  background: #f1f5f9;
  color: #64748b;
}

.priority-medium {
  background: #fef3c7;
  color: #d97706;
}

.priority-high {
  background: #fee2e2;
  color: #dc2626;
}

.status-open {
  background: #d1fae5;
  color: #059669;
}

.status-in_progress {
  background: #dbeafe;
  color: #2563eb;
}

.status-resolved {
  background: #d1fae5;
  color: #047857;
}

.status-closed {
  background: #f1f5f9;
  color: #475569;
}

.status-rejected {
  background: #fee2e2;
  color: #dc2626;
}

.ticket-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.btn-primary,
.btn-secondary,
.btn-update,
.btn-resolve,
.btn-page {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  background: #059669;
  color: white;
  border: none;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-compact {
  padding: 8px 12px;
  font-size: 13px;
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-update {
  background: #eff6ff;
  color: #1d4ed8;
  border: none;
}

.btn-update:hover {
  background: #dbeafe;
}

.btn-resolve {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.btn-resolve:hover {
  background: #d1fae5;
}

.btn-resolve:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.pagination-btns {
  display: flex;
  gap: 8px;
}

.btn-page {
  background: white;
  border: 1px solid #e2e8f0;
  color: #334155;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .header-content h2 {
    font-size: 22px;
  }

  .tab {
    padding: 10px 14px;
  }

  .detail-row {
    flex-direction: column;
    gap: 2px;
  }

  .detail-row .label {
    min-width: 0;
  }
}
</style>

<style>
/* Unscoped: Teleport ke body, harus lolos stacking context Layout */
.feedback-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2100;
  padding: 16px;
}

.feedback-modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  overflow: auto;
}

.feedback-modal-content .modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
}

.feedback-modal-content .modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.feedback-modal-content .btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  color: #64748b;
  line-height: 1;
}

.feedback-modal-content .modal-body {
  padding: 20px;
}

.feedback-modal-content .update-title {
  margin: 0 0 16px;
  font-weight: 600;
  color: #0f172a;
}

.feedback-modal-content .form-group {
  margin-bottom: 14px;
}

.feedback-modal-content .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.feedback-modal-content .form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.feedback-modal-content .form-control {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  font-family: inherit;
}

.feedback-modal-content .form-control:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.feedback-modal-content textarea.form-control {
  resize: vertical;
}

.feedback-modal-content .form-error {
  color: #dc2626;
  font-size: 13px;
  margin: 0 0 12px;
}

.feedback-modal-content .modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.feedback-modal-content .btn-primary,
.feedback-modal-content .btn-secondary {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.feedback-modal-content .btn-primary {
  background: #059669;
  color: white;
  border: none;
}

.feedback-modal-content .btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.feedback-modal-content .btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

@media (max-width: 640px) {
  .feedback-modal-content .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
