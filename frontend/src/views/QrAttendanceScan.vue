<template>
  <Layout>
    <div class="qr-attendance-scan-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h1 class="page-title">Scan QR Absensi</h1>
            <p class="page-subtitle">Scan kartu QR siswa untuk absen per jam pelajaran, atau kartu pegawai untuk absen harian.</p>
          </div>
        </div>
      </div>

      <div class="scan-section">
        <div class="form-row">
          <div class="form-group">
            <label>Tipe absensi</label>
            <select v-model="attendanceType" class="form-select" @change="resetForm">
              <option value="student">Siswa (per jam pelajaran)</option>
              <option value="employee">Guru/Staff (per hari)</option>
            </select>
          </div>

          <div v-if="attendanceType === 'student'" class="form-group grow">
            <label>Jurnal mengajar hari ini</label>
            <select v-model="selectedTeachingJournal" class="form-select" :disabled="loadingJournals">
              <option value="">Pilih jurnal mengajar</option>
              <option v-for="j in teachingJournals" :key="j.id" :value="j.id">
                {{ j.school_class?.name || j.schoolClass?.name }} · {{ j.subject?.name }} · Jam ke-{{ j.period }}
              </option>
            </select>
          </div>

          <div v-if="attendanceType === 'student'" class="form-group">
            <label>Tanggal jurnal</label>
            <input v-model="journalDate" type="date" class="form-input" @change="loadTeachingJournals" />
          </div>

          <div v-if="attendanceType === 'employee'" class="form-group">
            <label>Tanggal</label>
            <input v-model="attendanceDate" type="date" class="form-input" />
          </div>
        </div>

        <div class="location-status">
          <span v-if="locationStatus === 'loading'">Mendapatkan lokasi...</span>
          <span v-else-if="locationStatus === 'success'" class="ok">Lokasi siap</span>
          <span v-else-if="locationStatus === 'error'" class="bad">{{ locationError }}</span>
          <span v-else>Lokasi belum diambil (hanya wajib jika sekolah mengatur koordinat)</span>
        </div>

        <div class="scanner-section">
          <QrScanner @scan="handleQrScan" @error="handleScannerError" />
          <p v-if="busy" class="scan-busy">Memproses absensi...</p>

          <details class="manual-box">
            <summary>Input manual</summary>
            <input v-model="manualQrData" class="form-input" placeholder="Tempel kode QR dari kartu" />
            <button type="button" class="btn-primary" :disabled="!manualQrData.trim() || busy" @click="handleManualScan">
              Proses
            </button>
          </details>
        </div>

        <div v-if="scanResult" class="scan-result" :class="scanResult.type">
          <h4>{{ scanResult.title }}</h4>
          <p>{{ scanResult.message }}</p>
          <p v-if="scanResult.person" class="person-name">{{ scanResult.person }}</p>
        </div>

        <div v-if="recent.length" class="recent">
          <h3>Baru saja di-scan</h3>
          <ul>
            <li v-for="item in recent" :key="item.key">
              <strong>{{ item.name }}</strong>
              <span>{{ item.detail }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import QrScanner from '@/components/QrScanner.vue'
import { useToast } from '@/composables/useToast'
import { qrAttendanceApi } from '@/api/attendance'
import { teachingJournalApi } from '@/api/teachingJournal'

const toast = useToast()

const attendanceType = ref('student')
const selectedTeachingJournal = ref('')
const attendanceDate = ref(new Date().toISOString().slice(0, 10))
const journalDate = ref(new Date().toISOString().slice(0, 10))
const teachingJournals = ref([])
const loadingJournals = ref(false)
const locationStatus = ref('idle')
const locationError = ref('')
const currentLocation = ref({ latitude: null, longitude: null })
const manualQrData = ref('')
const scanResult = ref(null)
const busy = ref(false)
const recent = ref([])
const inFlightTokens = new Set()

function todayIso() {
  return new Date().toISOString().slice(0, 10)
}

async function loadTeachingJournals() {
  loadingJournals.value = true
  try {
    const date = journalDate.value || todayIso()
    const res = await teachingJournalApi.getAll({
      per_page: 100,
      date_from: date,
      date_to: date,
    })
    teachingJournals.value = res.data.data || []
    if (selectedTeachingJournal.value) {
      const stillThere = teachingJournals.value.some((j) => String(j.id) === String(selectedTeachingJournal.value))
      if (!stillThere) selectedTeachingJournal.value = ''
    }
  } catch {
    teachingJournals.value = []
    toast.error('Gagal memuat jurnal mengajar', 'Data jurnal tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loadingJournals.value = false
  }
}

async function getCurrentLocation(force = false) {
  if (!force && currentLocation.value.latitude && currentLocation.value.longitude) {
    locationStatus.value = 'success'
    return true
  }
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
      (err) => {
        locationStatus.value = 'error'
        locationError.value = 'Gagal mendapatkan lokasi: ' + err.message
        resolve(false)
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000,
      }
    )
  })
}

async function handleQrScan(qrData) {
  await processQrScan(qrData)
}

async function handleManualScan() {
  if (!manualQrData.value.trim()) {
    toast.error('Data tidak lengkap', 'Masukkan kode QR terlebih dahulu.')
    return
  }
  await processQrScan(manualQrData.value.trim())
}

function looksLikeQrToken(qrData) {
  return /^ISS1\./.test(String(qrData || '').trim())
}

async function processQrScan(qrData) {
  const token = String(qrData || '').trim()
  if (!looksLikeQrToken(token)) {
    toast.error('QR tidak dikenali', 'Gunakan kartu QR absensi yang digenerate dari menu Generate QR.')
    return
  }
  if (busy.value || inFlightTokens.has(token)) return

  if (attendanceType.value === 'student' && !selectedTeachingJournal.value) {
    toast.error('Pilihan wajib', 'Pilih jurnal mengajar terlebih dahulu.')
    return
  }
  if (attendanceType.value === 'employee' && !attendanceDate.value) {
    toast.error('Pilihan wajib', 'Pilih tanggal terlebih dahulu.')
    return
  }

  const hasLocation = await getCurrentLocation(false)
  if (!hasLocation) {
    toast.warning('Peringatan', 'Lokasi tidak terdeteksi. Absensi ditolak jika sekolah sudah mengatur koordinat.')
  }

  busy.value = true
  inFlightTokens.add(token)
  try {
    const payload = {
      qr_data: token,
      latitude: currentLocation.value.latitude,
      longitude: currentLocation.value.longitude,
      attendance_type: attendanceType.value,
    }
    if (attendanceType.value === 'student') {
      payload.teaching_journal_id = parseInt(selectedTeachingJournal.value, 10)
    } else {
      payload.date = attendanceDate.value
    }

    const res = await qrAttendanceApi.scanQr(payload)
    const already = Boolean(res.data.already_recorded)
    const person = res.data.data?.student_name || res.data.data?.employee_name || ''
    scanResult.value = {
      type: already ? 'info' : 'success',
      title: already ? 'Sudah tercatat' : 'Absensi berhasil',
      message: res.data.message,
      person,
    }
    pushRecent(person, already ? 'Sudah tercatat' : 'Hadir')
    if (already) {
      toast.info('Sudah tercatat', person || res.data.message)
    } else {
      toast.success('Berhasil', person ? `${person} hadir.` : res.data.message)
    }
    manualQrData.value = ''
  } catch (e) {
    const errorMsg = e.response?.data?.message || e.formattedMessage || 'Gagal memproses absensi'
    scanResult.value = {
      type: 'error',
      title: 'Gagal absensi',
      message: errorMsg,
      person: '',
    }
    toast.error('Gagal absensi', errorMsg)
  } finally {
    busy.value = false
    setTimeout(() => inFlightTokens.delete(token), 1500)
  }
}

function pushRecent(name, detail) {
  if (!name) return
  recent.value = [
    { key: `${Date.now()}-${name}`, name, detail },
    ...recent.value,
  ].slice(0, 12)
}

function handleScannerError() {
  // Pesan kamera sudah tampil di komponen scanner
}

function resetForm() {
  scanResult.value = null
  manualQrData.value = ''
  if (attendanceType.value === 'student') {
    loadTeachingJournals()
  }
}

onMounted(async () => {
  await Promise.all([loadTeachingJournals(), getCurrentLocation(true)])
})
</script>

<style scoped>
.qr-attendance-scan-page { width: 100%; padding: 1.5rem; }
.page-header { margin-bottom: 1rem; }
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.scan-section {
  background: #fff;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.form-row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.form-group { min-width: 180px; }
.form-group.grow { flex: 1; min-width: 240px; }
.form-group label { display: block; font-weight: 500; margin-bottom: 0.35rem; font-size: 0.85rem; }
.form-select, .form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}
.location-status {
  margin: 1rem 0;
  padding: 0.6rem 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #64748b;
}
.location-status .ok { color: #166534; }
.location-status .bad { color: #991b1b; }
.scanner-section { margin-top: 0.5rem; }
.scan-busy { text-align: center; color: #047857; font-weight: 600; }
.manual-box { margin-top: 1rem; color: #64748b; }
.manual-box input { margin: 0.5rem 0; }
.btn-primary {
  padding: 0.45rem 0.9rem;
  border: none;
  border-radius: 8px;
  background: #059669;
  color: #fff;
  cursor: pointer;
}
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.scan-result { margin-top: 1rem; padding: 0.9rem 1rem; border-radius: 8px; }
.scan-result.success { background: #dcfce7; color: #166534; }
.scan-result.info { background: #e0f2fe; color: #075985; }
.scan-result.error { background: #fee2e2; color: #991b1b; }
.scan-result h4 { margin: 0 0 0.35rem; }
.scan-result p { margin: 0; }
.person-name { font-weight: 700; margin-top: 0.35rem !important; }
.recent { margin-top: 1.25rem; }
.recent h3 { font-size: 0.95rem; margin: 0 0 0.5rem; }
.recent ul { list-style: none; margin: 0; padding: 0; }
.recent li {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.45rem 0;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.9rem;
}
</style>

