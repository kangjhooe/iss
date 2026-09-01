<template>    <div class="change-requests-page">
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="6" :cell-widths="['100px', '1fr', '120px', '100px', '1fr', '120px']" />
      </div>

      <div v-else class="requests-container">
        <div class="filter-tabs">
          <button
            @click="filterStatus = 'pending'"
            :class="['tab', { active: filterStatus === 'pending' }]"
          >
            Menunggu ({{ pendingCount }})
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
          <p>Belum ada permintaan {{ filterStatus ? filterStatus : '' }}</p>
        </div>

        <div v-else class="requests-list">
          <div v-for="request in requests" :key="request.id" class="request-card">
            <div class="request-header">
              <div class="request-info">
                <h3>{{ getFieldLabel(request.field_name) }}</h3>
                <p class="student-name">{{ request.student?.name }} ({{ request.student?.nis || '-' }})</p>
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
              <button @click="openApproveModal(request)" class="btn-approve">Setujui</button>
              <button @click="openRejectModal(request)" class="btn-reject">Tolak</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Approve Modal -->
      <div v-if="showApproveModalFlag" class="modal-overlay" @click="showApproveModalFlag = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Setujui Permintaan</h3>
            <button @click="showApproveModalFlag = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menyetujui perubahan ini?</p>
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
            <h3>Tolak Permintaan</h3>
            <button @click="showRejectModalFlag = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="handleReject" class="modal-body">
            <div class="form-group">
              <label>Alasan Penolakan</label>
              <textarea v-model="rejectionReason" rows="4" placeholder="Masukkan alasan penolakan (opsional)..."></textarea>
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
    </div></template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { studentChangeRequestApi } from '@/api/studentChangeRequest'
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

const FIELD_LABELS = {
  name: 'Nama',
  nik: 'NIK',
  nis: 'NIS',
  nisn: 'NISN',
  gender: 'Jenis Kelamin',
  birth_place: 'Tempat Lahir',
  birth_date: 'Tanggal Lahir',
  email: 'Email',
  address: 'Alamat',
  phone: 'No. HP',
  religion: 'Agama',
  no_kk: 'No. KK',
  aspiration: 'Cita-cita',
  hobby: 'Hobi',
  disability: 'Disabilitas',
  height: 'Tinggi Badan',
  weight: 'Berat Badan',
  previous_school: 'Sekolah Asal',
  previous_school_npsn: 'NPSN Sekolah Asal',
  previous_school_address: 'Alamat Sekolah Asal',
  residence_type: 'Jenis Tempat Tinggal',
  father_name: 'Nama Ayah',
  father_status: 'Status Ayah',
  father_nik: 'NIK Ayah',
  father_birth_place: 'Tempat Lahir Ayah',
  father_birth_date: 'Tanggal Lahir Ayah',
  father_education: 'Pendidikan Ayah',
  father_occupation: 'Pekerjaan Ayah',
  father_income: 'Penghasilan Ayah',
  mother_name: 'Nama Ibu',
  mother_status: 'Status Ibu',
  mother_nik: 'NIK Ibu',
  mother_birth_place: 'Tempat Lahir Ibu',
  mother_birth_date: 'Tanggal Lahir Ibu',
  mother_education: 'Pendidikan Ibu',
  mother_occupation: 'Pekerjaan Ibu',
  mother_income: 'Penghasilan Ibu',
  guardian_name: 'Nama Wali',
  guardian_phone: 'No. HP Wali',
  guardian_type: 'Jenis Wali',
  guardian_status: 'Status Wali',
  guardian_nik: 'NIK Wali',
  guardian_birth_place: 'Tempat Lahir Wali',
  guardian_birth_date: 'Tanggal Lahir Wali',
  guardian_education: 'Pendidikan Wali',
  guardian_occupation: 'Pekerjaan Wali',
  guardian_income: 'Penghasilan Wali',
  notes: 'Catatan'
}

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function getStatusLabel(status) {
  const labels = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }
  return labels[status] || status
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadRequests = async () => {
  loading.value = true
  try {
    const params = filterStatus.value ? { status: filterStatus.value } : {}
    const response = await studentChangeRequestApi.getAll(params)
    requests.value = response.data.data || []
    if (filterStatus.value === 'pending' || filterStatus.value === '') {
      const countResponse = await studentChangeRequestApi.getPendingCount()
      pendingCount.value = countResponse.data.count ?? 0
    }
  } catch (err) {
    toast.error('Gagal memuat permintaan perubahan', 'Data permintaan perubahan siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const openApproveModal = (request) => {
  selectedRequest.value = request
  approveError.value = ''
  showApproveModalFlag.value = true
}

const openRejectModal = (request) => {
  selectedRequest.value = request
  rejectionReason.value = ''
  rejectError.value = ''
  showRejectModalFlag.value = true
}

const handleApprove = async () => {
  approveError.value = ''
  processing.value = true
  try {
    await studentChangeRequestApi.approve(selectedRequest.value.id, { action: 'approve' })
    toast.success('Berhasil', 'Permintaan berhasil disetujui')
    showApproveModalFlag.value = false
    await loadRequests()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menyetujui permintaan'
    approveError.value = msg
    toast.error('Gagal', msg)
  } finally {
    processing.value = false
  }
}

const handleReject = async () => {
  rejectError.value = ''
  processing.value = true
  try {
    await studentChangeRequestApi.approve(selectedRequest.value.id, {
      action: 'reject',
      rejection_reason: rejectionReason.value || undefined
    })
    toast.success('Berhasil', 'Permintaan berhasil ditolak')
    showRejectModalFlag.value = false
    await loadRequests()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menolak permintaan'
    rejectError.value = msg
    toast.error('Gagal', msg)
  } finally {
    processing.value = false
  }
}

watch(filterStatus, () => loadRequests())

onMounted(async () => {
  await authStore.fetchUser()
  await loadRequests()
})
</script>

<style scoped>
.change-requests-page { width: 100%; max-width: 100%; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }
.page-header { margin-bottom: 32px; }
.header-content h2 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.header-content p { color: #64748b; font-size: 14px; margin: 0; }

.filter-tabs { display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; }
.tab { padding: 12px 24px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748b; font-weight: 500; cursor: pointer; margin-bottom: -2px; }
.tab:hover { color: #475569; }
.tab.active { color: #059669; border-bottom-color: #059669; }

.requests-list { display: flex; flex-direction: column; gap: 16px; }
.request-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; }
.request-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
.request-info h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
.student-name { color: #64748b; font-size: 14px; margin: 0; }
.status-badge { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-approved { background: #d1fae5; color: #059669; }
.status-rejected { background: #fee2e2; color: #dc2626; }

.request-details { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
.detail-row { display: flex; gap: 12px; }
.detail-row .label { font-weight: 600; color: #64748b; min-width: 120px; }
.detail-row .value { color: #1e293b; }
.detail-row .new-value { color: #059669; font-weight: 600; }
.rejection-reason { color: #dc2626; font-style: italic; }

.request-actions { display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.btn-approve, .btn-reject { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; border: none; }
.btn-approve { background: #d1fae5; color: #059669; }
.btn-approve:hover { background: #a7f3d0; }
.btn-reject { background: #fee2e2; color: #dc2626; }
.btn-reject:hover { background: #fecaca; }

.empty-state { text-align: center; padding: 80px 40px; color: #64748b; }
.loading-wrap { padding: 24px; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
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
.error-message { padding: 16px; background: #fef2f2; color: #dc2626; border-radius: 12px; margin-bottom: 16px; border: 1px solid #fecaca; font-size: 14px; }
</style>
