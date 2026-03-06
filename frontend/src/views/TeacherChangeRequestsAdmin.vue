<template>
  <Layout>
    <div class="change-requests-page">
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="6" :cell-widths="['100px', '1fr', '120px', '100px', '1fr', '120px']" />
      </div>

      <div v-else class="requests-container">
        <div class="filter-tabs">
          <button
            @click="filterStatus = 'pending'"
            :class="['tab', { active: filterStatus === 'pending' }]"
          >
            Pending ({{ pendingCount }})
          </button>
          <button
            @click="filterStatus = 'approved'"
            :class="['tab', { active: filterStatus === 'approved' }]"
          >
            Disetujui
          </button>
          <button
            @click="filterStatus = 'rejected'"
            :class="['tab', { active: filterStatus === 'rejected' }]"
          >
            Ditolak
          </button>
          <button
            @click="filterStatus = ''"
            :class="['tab', { active: filterStatus === '' }]"
          >
            Semua
          </button>
        </div>

        <div v-if="requests.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p>Belum ada request {{ filterStatus ? filterStatus : '' }}</p>
        </div>

        <div v-else class="requests-list">
          <div v-for="request in requests" :key="request.id" class="request-card">
            <div class="request-header">
              <div class="request-info">
                <h3>{{ getFieldLabel(request.field_name) }}</h3>
                <p class="employee-name">
                  {{ request.employee?.name }}
                  <span v-if="request.employee?.institution?.name" class="institution-badge">{{ request.employee.institution.name }}</span>
                  <span class="employee-meta">({{ request.employee?.email || request.employee?.nip || '-' }})</span>
                </p>
              </div>
              <span :class="['status-badge', `status-${request.status}`]">
                {{ getStatusLabel(request.status) }}
              </span>
            </div>

            <div class="request-details">
              <div class="detail-row">
                <span class="label">Nilai Lama:</span>
                <span class="value">{{ request.old_value || '-' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Nilai Baru:</span>
                <span class="value new-value">{{ request.new_value }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Diminta oleh:</span>
                <span class="value">{{ request.requester?.name }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Tanggal Request:</span>
                <span class="value">{{ formatDate(request.created_at) }}</span>
              </div>
              <div v-if="request.status !== 'pending'" class="detail-row">
                <span class="label">{{ request.status === 'approved' ? 'Disetujui oleh' : 'Ditolak oleh' }}:</span>
                <span class="value">{{ request.approver?.name }}</span>
              </div>
              <div v-if="request.rejection_reason" class="detail-row">
                <span class="label">Alasan Penolakan:</span>
                <span class="value rejection-reason">{{ request.rejection_reason }}</span>
              </div>
            </div>

            <div v-if="request.status === 'pending'" class="request-actions">
              <button @click="openApproveModal(request)" class="btn-approve">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Setujui
              </button>
              <button @click="openRejectModal(request)" class="btn-reject">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tolak
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Approve Modal -->
      <div v-if="showApproveModalFlag" class="modal-overlay" @click="showApproveModalFlag = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Setujui Request</h3>
            <button @click="showApproveModalFlag = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menyetujui perubahan data guru ini?</p>
            <div class="approval-details">
              <div class="detail-row">
                <span class="label">Field:</span>
                <span class="value">{{ selectedRequest ? getFieldLabel(selectedRequest.field_name) : '' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Dari:</span>
                <span class="value">{{ selectedRequest?.old_value || '-' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Menjadi:</span>
                <span class="value new-value">{{ selectedRequest?.new_value }}</span>
              </div>
            </div>
            <div v-if="approveError" class="error-message">{{ approveError }}</div>
            <div class="modal-footer">
              <button @click="showApproveModalFlag = false" class="btn-secondary">Batal</button>
              <button @click="handleApprove" :disabled="processing" class="btn-approve">
                {{ processing ? 'Memproses...' : 'Setujui' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div v-if="showRejectModalFlag" class="modal-overlay" @click="showRejectModalFlag = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tolak Request</h3>
            <button @click="showRejectModalFlag = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="handleReject" class="modal-body">
            <div class="form-group">
              <label>Alasan Penolakan *</label>
              <textarea
                v-model="rejectionReason"
                rows="4"
                placeholder="Masukkan alasan penolakan..."
                required
              ></textarea>
            </div>
            <div v-if="rejectError" class="error-message">{{ rejectError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showRejectModalFlag = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="processing" class="btn-reject">
                {{ processing ? 'Memproses...' : 'Tolak' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { teacherChangeRequestApi } from '@/api/teacherChangeRequest'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const FIELD_LABELS = {
  address: 'Alamat',
  phone: 'No. HP',
  email: 'Email',
  religion: 'Agama',
  birth_place: 'Tempat Lahir',
  birth_date: 'Tanggal Lahir',
  education_level: 'Pendidikan',
  major: 'Jurusan',
  subject: 'Mata Pelajaran',
  notes: 'Catatan',
  certification_status: 'Status Sertifikasi',
  certification_date: 'Tanggal Sertifikasi',
  teacher_registration_number: 'Nomor Registrasi Guru',
  certification_number: 'Nomor Sertifikasi',
  certification_issuing_authority: 'Lembaga Penerbit Sertifikasi'
}

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

const loading = ref(true)
const requests = ref([])
const filterStatus = ref('pending')
const pendingCount = ref(0)
const showApproveModalFlag = ref(false)
const showRejectModalFlag = ref(false)
const selectedRequest = ref(null)
const rejectionReason = ref('')
const processing = ref(false)
const approveError = ref('')
const rejectError = ref('')

const loadRequests = async () => {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (filterStatus.value) params.status = filterStatus.value
    const response = await teacherChangeRequestApi.getAll(params)
    const body = response.data
    requests.value = Array.isArray(body?.data) ? body.data : []
    if (filterStatus.value === 'pending' || filterStatus.value === '') {
      const countResponse = await teacherChangeRequestApi.getPendingCount()
      pendingCount.value = countResponse.data?.count ?? 0
    }
  } catch (err) {
    toast.error('Gagal', 'Gagal memuat data request')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const showApproveModal = (request) => {
  selectedRequest.value = request
  approveError.value = ''
  showApproveModalFlag.value = true
}

const showRejectModal = (request) => {
  selectedRequest.value = request
  rejectionReason.value = ''
  rejectError.value = ''
  showRejectModalFlag.value = true
}

const handleApprove = async () => {
  approveError.value = ''
  processing.value = true
  try {
    await teacherChangeRequestApi.approve(selectedRequest.value.id, { action: 'approve' })
    toast.success('Berhasil', 'Request berhasil disetujui')
    showApproveModalFlag.value = false
    await loadRequests()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menyetujui request'
    approveError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    processing.value = false
  }
}

const handleReject = async () => {
  if (!rejectionReason.value.trim()) {
    rejectError.value = 'Alasan penolakan wajib diisi'
    return
  }
  rejectError.value = ''
  processing.value = true
  try {
    await teacherChangeRequestApi.approve(selectedRequest.value.id, {
      action: 'reject',
      rejection_reason: rejectionReason.value
    })
    toast.success('Berhasil', 'Request berhasil ditolak')
    showRejectModalFlag.value = false
    await loadRequests()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menolak request'
    rejectError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    processing.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
  return labels[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

watch(filterStatus, () => {
  loadRequests()
})

onMounted(() => {
  loadRequests()
})
</script>

<style scoped>
.change-requests-page { width: 100%; max-width: 100%; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }
.page-header { margin-bottom: 32px; }
.header-content h2 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.header-content p { color: #64748b; font-size: 14px; margin: 0; }

.filter-tabs { display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; }
.tab { padding: 12px 24px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-weight: 500; cursor: pointer; transition: all 0.2s ease; margin-bottom: -2px; }
.tab:hover { color: #475569; }
.tab.active { color: #16a34a; border-bottom-color: #16a34a; }

.requests-list { display: flex; flex-direction: column; gap: 16px; }
.request-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; }
.request-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.request-info h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
.employee-name { color: #64748b; font-size: 14px; margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.institution-badge { font-size: 12px; padding: 2px 8px; background: #e0f2fe; color: #0369a1; border-radius: 6px; font-weight: 500; }
.employee-meta { color: #94a3b8; font-size: 13px; }

.status-badge { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-approved { background: #d1fae5; color: #059669; }
.status-rejected { background: #fee2e2; color: #dc2626; }

.request-details { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
.detail-row { display: flex; gap: 12px; }
.detail-row .label { font-weight: 600; color: #64748b; min-width: 120px; }
.detail-row .value { color: #1e293b; }
.detail-row .new-value { color: #16a34a; font-weight: 600; }
.rejection-reason { color: #dc2626; font-style: italic; }

.request-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.btn-approve, .btn-reject { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; border: none; }
.btn-approve { background: #d1fae5; color: #059669; }
.btn-approve:hover { background: #a7f3d0; }
.btn-reject { background: #fee2e2; color: #dc2626; }
.btn-reject:hover { background: #fecaca; }

.empty-state { text-align: center; padding: 80px 40px; color: #64748b; }
.empty-state svg { margin-bottom: 16px; opacity: 0.5; }

.approval-details { background: #f8fafc; padding: 16px; border-radius: 8px; margin: 16px 0; }
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { color: #1e293b; font-size: 24px; font-weight: 700; margin: 0; }
.btn-close { background: none; border: none; font-size: 28px; color: #999; cursor: pointer; line-height: 1; }
.modal-body { padding: 32px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.btn-secondary { padding: 12px 24px; background: #f1f5f9; color: #475569; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 14px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px; }
.form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; font-family: inherit; resize: vertical; }
.form-group textarea:focus { outline: none; border-color: #16a34a; box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1); }
.error-message { padding: 16px; background: #fef2f2; color: #dc2626; border-radius: 12px; margin-bottom: 16px; border: 1px solid #fecaca; font-size: 14px; }
.loading-wrap { text-align: center; padding: 24px; color: #64748b; }
</style>
