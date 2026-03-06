<template>
  <Layout>
    <div class="qr-attendance-scan-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Scan QR Code Absensi</h1>
            <p class="page-subtitle">Scan QR code siswa atau guru untuk absensi dengan validasi lokasi</p>
          </div>
        </div>
      </div>

      <div class="scan-section">
        <div class="form-group">
          <label>Tipe Absensi *</label>
          <select v-model="attendanceType" class="form-select" @change="resetForm">
            <option value="student">Siswa (per jam pelajaran)</option>
            <option value="employee">Guru/Staff (per hari)</option>
          </select>
        </div>

        <div v-if="attendanceType === 'student'" class="form-group">
          <label>Jurnal Mengajar *</label>
          <select v-model="selectedTeachingJournal" class="form-select" :disabled="loadingJournals">
            <option value="">Pilih jurnal mengajar</option>
            <option v-for="j in teachingJournals" :key="j.id" :value="j.id">
              {{ formatDate(j.journal_date) }} - {{ j.school_class?.name }} - {{ j.subject?.name }} (Jam ke-{{ j.period }})
            </option>
          </select>
          <button v-if="!loadingJournals" @click="loadTeachingJournals" class="btn-link">Refresh</button>
        </div>

        <div v-if="attendanceType === 'employee'" class="form-group">
          <label>Tanggal *</label>
          <input v-model="attendanceDate" type="date" class="form-input" />
        </div>

        <div class="location-status">
          <div v-if="locationStatus === 'loading'" class="status-item">
            <span class="status-icon">📍</span>
            <span>Mendapatkan lokasi...</span>
          </div>
          <div v-else-if="locationStatus === 'success'" class="status-item success">
            <span class="status-icon">✅</span>
            <span>Lokasi berhasil didapatkan</span>
          </div>
          <div v-else-if="locationStatus === 'error'" class="status-item error">
            <span class="status-icon">❌</span>
            <span>{{ locationError }}</span>
          </div>
        </div>

        <div class="scanner-section">
          <h3>Scan QR Code</h3>
          <QrScanner @scan="handleQrScan" @error="handleScannerError" ref="qrScannerRef" />
          
          <div class="manual-input">
            <p class="manual-hint">Atau masukkan data QR code secara manual (format JSON):</p>
            <textarea 
              v-model="manualQrData" 
              placeholder='{"type":"student","id":1,"institution_id":1,"timestamp":1234567890}'
              class="form-input manual-qr-input" 
              rows="3"
            ></textarea>
            <button @click="handleManualScan" class="btn-primary" :disabled="!manualQrData.trim()">Scan Manual</button>
          </div>
        </div>

        <div v-if="scanResult" class="scan-result" :class="scanResult.type">
          <h4>{{ scanResult.title }}</h4>
          <p>{{ scanResult.message }}</p>
          <div v-if="scanResult.distance !== undefined" class="distance-info">
            <p><strong>Jarak dari sekolah:</strong> {{ Math.round(scanResult.distance) }} meter</p>
            <p v-if="scanResult.requiredRadius"><strong>Radius yang diizinkan:</strong> {{ scanResult.requiredRadius }} meter</p>
          </div>
          <div v-if="scanResult.data" class="result-data">
            <pre>{{ JSON.stringify(scanResult.data, null, 2) }}</pre>
          </div>
          <button @click="resetScanResult" class="btn-secondary">Scan Lagi</button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import QrScanner from '@/components/QrScanner.vue'
import { useToast } from '@/composables/useToast'
import { qrAttendanceApi } from '@/api/attendance'
import { teachingJournalApi } from '@/api/teachingJournal'

const toast = useToast()

const attendanceType = ref('student')
const selectedTeachingJournal = ref('')
const attendanceDate = ref(new Date().toISOString().slice(0, 10))
const teachingJournals = ref([])
const loadingJournals = ref(false)
const locationStatus = ref('idle') // idle, loading, success, error
const locationError = ref('')
const currentLocation = ref({ latitude: null, longitude: null })
const manualQrData = ref('')
const scanResult = ref(null)
const qrScannerRef = ref(null)

function formatDate(d) {
  if (!d) return '-'
  const date = typeof d === 'string' ? new Date(d) : d
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadTeachingJournals() {
  loadingJournals.value = true
  try {
    const res = await teachingJournalApi.getAll({ per_page: 100 })
    teachingJournals.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jurnal mengajar')
  } finally {
    loadingJournals.value = false
  }
}

async function getCurrentLocation() {
  locationStatus.value = 'loading'
  locationError.value = ''
  
  if (!navigator.geolocation) {
    locationStatus.value = 'error'
    locationError.value = 'Browser tidak mendukung geolocation'
    return false
  }

  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        currentLocation.value = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        }
        locationStatus.value = 'success'
        resolve(true)
      },
      (error) => {
        locationStatus.value = 'error'
        locationError.value = 'Gagal mendapatkan lokasi: ' + error.message
        resolve(false)
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      }
    )
  })
}

async function handleQrScan(qrData) {
  await processQrScan(qrData)
}

async function handleManualScan() {
  if (!manualQrData.value.trim()) {
    toast.error('Masukkan data QR code terlebih dahulu')
    return
  }
  await processQrScan(manualQrData.value.trim())
}

async function processQrScan(qrData) {
  // Validate QR data format
  try {
    const parsed = JSON.parse(qrData)
    if (!parsed.type || !parsed.id || !parsed.institution_id || !parsed.timestamp) {
      toast.error('Format QR code tidak valid')
      return
    }
  } catch (e) {
    toast.error('QR code tidak valid. Pastikan format JSON benar.')
    return
  }

  // Get location (try to get, but don't fail if not available)
  // The backend will validate if institution has coordinates set
  const hasLocation = await getCurrentLocation()
  if (!hasLocation) {
    // Show warning but continue - backend will handle validation
    toast.warning('Lokasi tidak dapat dideteksi. Absensi mungkin akan ditolak jika sekolah sudah set koordinat.')
  }

  // Validate form
  if (attendanceType.value === 'student' && !selectedTeachingJournal.value) {
    toast.error('Pilih jurnal mengajar terlebih dahulu')
    return
  }
  if (attendanceType.value === 'employee' && !attendanceDate.value) {
    toast.error('Pilih tanggal terlebih dahulu')
    return
  }

  try {
    const payload = {
      qr_data: qrData,
      latitude: currentLocation.value.latitude,
      longitude: currentLocation.value.longitude,
      attendance_type: attendanceType.value,
    }

    if (attendanceType.value === 'student') {
      payload.teaching_journal_id = parseInt(selectedTeachingJournal.value)
    } else {
      payload.date = attendanceDate.value
    }

    const res = await qrAttendanceApi.scanQr(payload)
    
    scanResult.value = {
      type: 'success',
      title: 'Absensi Berhasil!',
      message: res.data.message,
      data: res.data.data,
    }
    
    toast.success(res.data.message)
    manualQrData.value = ''
  } catch (e) {
    const errorMsg = e.response?.data?.message || e.formattedMessage || 'Gagal memproses absensi'
    const errorData = e.response?.data || {}
    
    scanResult.value = {
      type: 'error',
      title: 'Gagal Absensi',
      message: errorMsg,
      data: errorData,
      distance: errorData.distance,
      requiredRadius: errorData.required_radius,
    }
    toast.error(errorMsg)
  }
}

function handleScannerError(error) {
  toast.error('Error scanner: ' + error.message)
}

function resetForm() {
  scanResult.value = null
  manualQrData.value = ''
}

function resetScanResult() {
  scanResult.value = null
  manualQrData.value = ''
}

onMounted(async () => {
  if (attendanceType.value === 'student') {
    await loadTeachingJournals()
  }
  await getCurrentLocation()
})
</script>

<style scoped>
.qr-attendance-scan-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1.5rem;
}

.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.scan-section {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}

.form-select,
.form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}

.btn-link {
  margin-top: 0.5rem;
  color: #059669;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  text-decoration: underline;
}

.location-status {
  margin: 1rem 0;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-item.success {
  color: #166534;
}

.status-item.error {
  color: #991b1b;
}

.status-icon {
  font-size: 1.2rem;
}

.scanner-section {
  margin-top: 2rem;
}

.scanner-section h3 {
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.manual-input {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.manual-hint {
  margin-bottom: 0.5rem;
  color: #64748b;
  font-size: 0.9rem;
}

.manual-qr-input {
  font-family: monospace;
  font-size: 0.85rem;
}

.btn-primary {
  margin-top: 0.75rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  background: #059669;
  color: #fff;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.scan-result {
  margin-top: 1.5rem;
  padding: 1rem;
  border-radius: 8px;
}

.scan-result.success {
  background: #dcfce7;
  border: 1px solid #166534;
  color: #166534;
}

.scan-result.error {
  background: #fee2e2;
  border: 1px solid #991b1b;
  color: #991b1b;
}

.scan-result h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
}

.scan-result p {
  margin: 0 0 0.75rem 0;
}

.distance-info {
  margin: 0.75rem 0;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 6px;
}

.distance-info p {
  margin: 0.25rem 0;
  font-size: 0.9rem;
}

.result-data {
  margin: 0.75rem 0;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 6px;
  overflow-x: auto;
}

.result-data pre {
  margin: 0;
  font-size: 0.85rem;
  white-space: pre-wrap;
}

.btn-secondary {
  margin-top: 0.75rem;
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
}
</style>
