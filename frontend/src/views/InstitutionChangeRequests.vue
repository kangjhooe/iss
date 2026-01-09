<template>
  <Layout>
    <div class="change-requests-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Manajemen Request Perubahan</h2>
            <p>Kelola request perubahan nama sekolah dan NPSN</p>
          </div>
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

      <div v-else class="requests-container">
        <!-- Filter Tabs -->
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

        <!-- Requests List -->
        <div v-if="requests.length === 0" class="empty-state">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p>Tidak ada request {{ filterStatus ? filterStatus : '' }}</p>
        </div>

        <div v-else class="requests-list">
          <div v-for="request in requests" :key="request.id" class="request-card">
            <div class="request-header">
              <div class="request-info">
                <h3>{{ request.field_name === 'name' ? 'Nama Sekolah' : 'NPSN' }}</h3>
                <p class="institution-name">{{ request.institution?.name }}</p>
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
            <p>Apakah Anda yakin ingin menyetujui perubahan ini?</p>
            <div class="approval-details">
              <div class="detail-row">
                <span class="label">Field:</span>
                <span class="value">{{ selectedRequest?.field_name === 'name' ? 'Nama Sekolah' : 'NPSN' }}</span>
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
import { institutionChangeRequestApi } from '@/api/institutionChangeRequest'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()

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
    const params = filterStatus.value ? { status: filterStatus.value } : {}
    const response = await institutionChangeRequestApi.getAll(params)
    requests.value = response.data.data || []
    
    if (filterStatus.value === 'pending' || filterStatus.value === '') {
      const countResponse = await institutionChangeRequestApi.getPendingCount()
      pendingCount.value = countResponse.data.count || 0
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
    await institutionChangeRequestApi.approve(selectedRequest.value.id, {
      action: 'approve'
    })
    
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
    await institutionChangeRequestApi.approve(selectedRequest.value.id, {
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
  const labels = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
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

onMounted(async () => {
  await authStore.fetchUser()
  if (authStore.user?.role !== 'super_admin') {
    toast.error('Akses Ditolak', 'Hanya super admin yang dapat mengakses halaman ini')
    // Redirect will be handled by router guard
    return
  }
  await loadRequests()
})
</script>

<style scoped>
.change-requests-page {
  max-width: 1200px;
}

.page-header {
  margin-bottom: 32px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.filter-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e2e8f0;
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
  color: #667eea;
  border-bottom-color: #667eea;
}

.requests-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.request-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.request-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.request-info h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.institution-name {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #d97706;
}

.status-approved {
  background: #d1fae5;
  color: #059669;
}

.status-rejected {
  background: #fee2e2;
  color: #dc2626;
}

.request-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.detail-row {
  display: flex;
  gap: 12px;
}

.detail-row .label {
  font-weight: 600;
  color: #64748b;
  min-width: 120px;
}

.detail-row .value {
  color: #1e293b;
}

.detail-row .new-value {
  color: #667eea;
  font-weight: 600;
}

.rejection-reason {
  color: #dc2626;
  font-style: italic;
}

.request-actions {
  display: flex;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.btn-approve,
.btn-reject {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  border: none;
}

.btn-approve {
  background: #d1fae5;
  color: #059669;
}

.btn-approve:hover {
  background: #a7f3d0;
}

.btn-reject {
  background: #fee2e2;
  color: #dc2626;
}

.btn-reject:hover {
  background: #fecaca;
}

.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
}

.empty-state svg {
  margin-bottom: 16px;
  opacity: 0.5;
}

.approval-details {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  margin: 16px 0;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #999;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 32px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.btn-secondary {
  padding: 12px 24px;
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #333;
  font-weight: 500;
  font-size: 14px;
}

.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  font-family: inherit;
  resize: vertical;
}

.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.error-message {
  padding: 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  margin-bottom: 16px;
  border: 1px solid #fecaca;
  font-size: 14px;
}

.loading-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.loading-spinner {
  color: #667eea;
}
</style>
