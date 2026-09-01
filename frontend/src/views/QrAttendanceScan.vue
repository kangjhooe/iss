<template>    <div class="qr-attendance-scan-page">
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

        <div class="location-status" :class="locationStatusClass">
          <div class="location-status-main">
            <span v-if="locationStatus === 'loading'">Mendapatkan lokasi...</span>
            <span v-else-if="locationStatus === 'success'">
              Lokasi siap
              <template v-if="currentLocation.accuracy"> (akurasi ±{{ Math.round(currentLocation.accuracy) }} m)</template>
            </span>
            <span v-else-if="locationStatus === 'error'">{{ locationError }}</span>
            <span v-else-if="locationRequired">Lokasi wajib — tekan "Ambil lokasi" sebelum scan</span>
            <span v-else>Lokasi opsional (sekolah belum mengatur koordinat)</span>
          </div>
          <button
            type="button"
            class="btn-location-retry"
            :disabled="locationStatus === 'loading'"
            @click="refreshLocation(true)"
          >
            {{ locationStatus === 'loading' ? 'Mengambil...' : 'Ambil lokasi' }}
          </button>
        </div>
        <p v-if="locationRequired && locationConfig.location_radius" class="location-hint">
          Scan hanya diterima dalam radius {{ locationConfig.location_radius }} m dari titik sekolah.
        </p>

        <div class="scanner-section">
          <QrScanner @scan="handleQrScan" @error="handleScannerError" />
          <p v-if="busy" class="scan-busy">Memproses absensi...</p>
          <p v-else-if="scanBlockedByLocation" class="scan-blocked">
            Aktifkan lokasi terlebih dahulu untuk melanjutkan scan.
          </p>

          <details class="manual-box">
            <summary>Input manual</summary>
            <input v-model="manualQrData" class="form-input" placeholder="Tempel kode QR dari kartu" />
            <button
              type="button"
              class="btn-primary"
              :disabled="!manualQrData.trim() || busy || scanBlockedByLocation"
              @click="handleManualScan"
            >
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
    </div></template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import QrScanner from '@/components/QrScanner.vue'
import { useToast } from '@/composables/useToast'
import { qrAttendanceApi } from '@/api/attendance'
import { teachingJournalApi } from '@/api/teachingJournal'
import {
  GEO_STATUS,
  formatQrLocationApiError,
  getCurrentCoordinates,
  hasValidCoordinates,
} from '@/utils/geolocation'

const toast = useToast()

const attendanceType = ref('student')
const selectedTeachingJournal = ref('')
const attendanceDate = ref(new Date().toISOString().slice(0, 10))
const journalDate = ref(new Date().toISOString().slice(0, 10))
const teachingJournals = ref([])
const loadingJournals = ref(false)
const locationStatus = ref(GEO_STATUS.IDLE)
const locationError = ref('')
const currentLocation = ref({ latitude: null, longitude: null, accuracy: null })
const locationConfig = ref({
  location_required: false,
  location_radius: 100,
})
const manualQrData = ref('')
const scanResult = ref(null)
const busy = ref(false)
const recent = ref([])
const inFlightTokens = new Set()

const locationRequired = computed(() => Boolean(locationConfig.value.location_required))

const locationReady = computed(() =>
  hasValidCoordinates(currentLocation.value.latitude, currentLocation.value.longitude)
)

const scanBlockedByLocation = computed(() =>
  locationRequired.value && !locationReady.value && locationStatus.value !== GEO_STATUS.LOADING
)

const locationStatusClass = computed(() => {
  if (locationStatus.value === GEO_STATUS.SUCCESS) return 'ok'
  if (locationStatus.value === GEO_STATUS.ERROR) return 'bad'
  if (locationRequired.value && !locationReady.value) return 'warn'
  return ''
})

function todayIso() {
  return new Date().toISOString().slice(0, 10)
}

async function loadLocationConfig() {
  try {
    const res = await qrAttendanceApi.getLocationConfig()
    const data = res.data.data || {}
    locationConfig.value = {
      location_required: Boolean(data.location_required),
      location_radius: data.location_radius ?? 100,
    }
  } catch {
    locationConfig.value = { location_required: false, location_radius: 100 }
  }
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

async function refreshLocation(force = false) {
  if (!force && locationReady.value) {
    locationStatus.value = GEO_STATUS.SUCCESS
    return true
  }

  locationStatus.value = GEO_STATUS.LOADING
  locationError.value = ''

  try {
    const coords = await getCurrentCoordinates()
    currentLocation.value = {
      latitude: coords.latitude,
      longitude: coords.longitude,
      accuracy: coords.accuracy ?? null,
    }
    locationStatus.value = GEO_STATUS.SUCCESS
    return true
  } catch (e) {
    locationStatus.value = GEO_STATUS.ERROR
    locationError.value = e.message || 'Gagal mendapatkan lokasi.'
    currentLocation.value = { latitude: null, longitude: null, accuracy: null }
    return false
  }
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

  if (locationRequired.value && !locationReady.value) {
    const ok = await refreshLocation(true)
    if (!ok) {
      toast.error('Lokasi wajib', locationError.value || 'Aktifkan izin lokasi lalu tekan "Ambil lokasi".')
      return
    }
  } else if (!locationReady.value) {
    await refreshLocation(false)
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
    const errorMsg = formatQrLocationApiError(e)
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
  await Promise.all([
    loadLocationConfig(),
    loadTeachingJournals(),
    refreshLocation(true),
  ])
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
  margin: 1rem 0 0.35rem;
  padding: 0.6rem 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.location-status.ok { background: #dcfce7; color: #166534; }
.location-status.bad { background: #fee2e2; color: #991b1b; }
.location-status.warn { background: #fef3c7; color: #92400e; }
.location-status-main { flex: 1; min-width: 180px; }
.btn-location-retry {
  padding: 0.35rem 0.7rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
  font-size: 0.82rem;
  cursor: pointer;
  white-space: nowrap;
}
.location-status.ok .btn-location-retry { border-color: #86efac; }
.location-status.bad .btn-location-retry,
.location-status.warn .btn-location-retry { border-color: currentColor; }
.btn-location-retry:disabled { opacity: 0.6; cursor: not-allowed; }
.location-hint {
  margin: 0 0 0.75rem;
  font-size: 0.8rem;
  color: #64748b;
}
.scanner-section { margin-top: 0.5rem; }
.scan-busy { text-align: center; color: #047857; font-weight: 600; }
.scan-blocked {
  text-align: center;
  color: #92400e;
  font-size: 0.88rem;
  margin: 0.5rem 0 0;
}
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
