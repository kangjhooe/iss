<template>
  <section id="buku-tamu" ref="sectionRef" class="section buku-tamu-section reveal-section">
    <div class="section-inner">
      <h2 class="section-title"><span class="section-title-text">Buku Tamu</span></h2>
      <p class="section-subtitle">Isi form di bawah untuk mencatat kunjungan Anda.</p>

      <div v-if="guestSubmitted" class="state-wrap state-success card">
        <div class="success-icon-wrap">
          <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </div>
        <h2>Terima kasih</h2>
        <p>Data kunjungan Anda telah dicatat.</p>
      </div>

      <form v-else class="guest-form card card--hover guest-form-modal" @submit.prevent="submitGuest">
        <section class="form-section">
          <h3 class="form-section-title">Foto tamu <span class="optional">(opsional)</span></h3>
          <div class="photo-block">
            <input ref="photoInputRef" type="file" accept="image/*" capture="user" class="photo-input-hidden" @change="onPhotoSelect" />
            <template v-if="!cameraActive && !photoPreview">
              <div class="photo-buttons">
                <button type="button" class="btn-camera" @click="openCamera">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                  </svg>
                  Buka Kamera
                </button>
                <button type="button" class="btn-link" @click="triggerPhotoInput">Pilih dari file</button>
              </div>
            </template>
            <div v-else-if="cameraActive" class="camera-block">
              <video ref="videoRef" class="camera-video" autoplay playsinline muted></video>
              <p v-if="cameraError" class="camera-error">{{ cameraError }}</p>
              <div class="camera-buttons">
                <button type="button" class="btn-capture" @click="captureFromCamera">Ambil Foto</button>
                <button type="button" class="btn-ghost" @click="closeCamera">Batal</button>
              </div>
            </div>
            <template v-else>
              <div class="photo-preview-box">
                <img v-if="photoPreview" :src="photoPreview" alt="Preview" />
              </div>
              <div class="photo-buttons">
                <button type="button" class="btn-camera btn-small" @click="openCamera">Ganti (Buka Kamera)</button>
                <button type="button" class="btn-link" @click="triggerPhotoInput">Pilih dari file</button>
              </div>
            </template>
            <p class="form-hint">Maks. 5MB. Di desktop & ponsel, "Buka Kamera" membuka kamera langsung.</p>
          </div>
        </section>
        <section class="form-section">
          <h3 class="form-section-title">Data tamu</h3>
          <div class="form-row">
            <div class="field">
              <label>Nama tamu <span class="required">*</span></label>
              <input v-model="guestForm.nama_tamu" type="text" required maxlength="255" placeholder="Nama lengkap" />
            </div>
            <div class="field">
              <label>No. identitas</label>
              <input v-model="guestForm.no_identitas" type="text" maxlength="64" placeholder="NIK / KTP" />
            </div>
          </div>
          <div class="field">
            <label>Instansi / asal</label>
            <input v-model="guestForm.instansi_asal" type="text" maxlength="255" placeholder="Contoh: Dinas Pendidikan" />
          </div>
          <div class="field">
            <label>No. telepon</label>
            <input v-model="guestForm.no_telepon" type="text" maxlength="32" placeholder="08xxxxxxxxxx" />
            <p v-if="guestForm.no_telepon && !isValidPhone(guestForm.no_telepon)" class="field-hint field-hint-error">Format nomor tidak valid (angka, +, spasi, min 10 karakter)</p>
          </div>
          <div class="field">
            <label>Tujuan kunjungan <span class="required">*</span></label>
            <select v-model="guestForm.tujuan_kunjungan" required>
              <option value="">— Pilih tujuan —</option>
              <option v-for="opt in TUJUAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <input v-if="guestForm.tujuan_kunjungan === 'Lainnya'" v-model="guestForm.tujuan_kunjungan_lain" type="text" maxlength="255" placeholder="Tulis tujuan kunjungan" class="field-mt" />
          </div>
          <div class="field">
            <label>Ditemui / penerima</label>
            <input v-model="guestForm.orang_ditemui" type="text" maxlength="255" placeholder="Nama staf yang ditemui" />
          </div>
          <div class="field">
            <label>Catatan</label>
            <textarea v-model="guestForm.catatan" rows="2" placeholder="Opsional" maxlength="2000"></textarea>
          </div>
        </section>
        <div class="hp" aria-hidden="true">
          <label for="website_hp_bt">Website</label>
          <input id="website_hp_bt" v-model="guestForm.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hp-input" />
        </div>
        <div v-if="guestError" class="error-banner">{{ guestError }}</div>
        <button type="submit" class="btn-submit" :disabled="guestLoading">
          <span v-if="guestLoading" class="btn-spinner"></span>
          {{ guestLoading ? 'Mengirim...' : 'Catat tamu' }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { schoolPublicApi } from '@/api/schoolPublic'

const TUJUAN_OPTIONS = ['Rapat', 'Urusan siswa', 'Dinas', 'Kunjungan', 'Lainnya']

const props = defineProps({
  npsn: { type: String, default: '' }
})

const sectionRef = ref(null)
const guestForm = ref({
  nama_tamu: '',
  no_identitas: '',
  instansi_asal: '',
  no_telepon: '',
  tujuan_kunjungan: '',
  tujuan_kunjungan_lain: '',
  orang_ditemui: '',
  catatan: '',
  website: ''
})
const guestLoading = ref(false)
const guestError = ref('')
const guestSubmitted = ref(false)
const selectedPhoto = ref(null)
const photoPreview = ref('')
const cameraActive = ref(false)
const cameraError = ref('')
const videoRef = ref(null)
const photoInputRef = ref(null)
let cameraStreamRef = null

function isValidPhone(val) {
  if (!val || !String(val).trim()) return true
  return /^[\d\s+()-]{10,32}$/.test(String(val).trim())
}

function triggerPhotoInput() {
  photoInputRef.value?.click()
}

async function openCamera() {
  cameraError.value = ''
  if (!navigator.mediaDevices?.getUserMedia) {
    cameraError.value = 'Kamera tidak didukung di browser ini. Gunakan "Pilih dari file".'
    return
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false
    })
    cameraStreamRef = stream
    cameraActive.value = true
    await nextTick()
    if (videoRef.value) videoRef.value.srcObject = stream
  } catch (err) {
    cameraError.value = err.name === 'NotAllowedError'
      ? 'Akses kamera ditolak. Izinkan kamera di pengaturan browser.'
      : (err.message || 'Tidak dapat mengakses kamera. Gunakan "Pilih dari file".')
  }
}

function closeCamera() {
  if (cameraStreamRef) {
    cameraStreamRef.getTracks().forEach((t) => t.stop())
    cameraStreamRef = null
  }
  cameraActive.value = false
  cameraError.value = ''
  if (videoRef.value) videoRef.value.srcObject = null
}

function captureFromCamera() {
  const video = videoRef.value
  if (!video || !video.videoWidth) return
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0)
  canvas.toBlob(
    (blob) => {
      if (!blob) return
      const file = new File([blob], 'foto-tamu.jpg', { type: 'image/jpeg' })
      selectedPhoto.value = file
      photoPreview.value = URL.createObjectURL(blob)
      closeCamera()
    },
    'image/jpeg',
    0.9
  )
}

function onPhotoSelect(e) {
  const file = e.target?.files?.[0]
  selectedPhoto.value = file || null
  if (file) {
    photoPreview.value = URL.createObjectURL(file)
  } else {
    photoPreview.value = ''
  }
}

async function submitGuest() {
  guestError.value = ''
  if (!guestForm.value.nama_tamu?.trim()) {
    guestError.value = 'Nama tamu wajib diisi.'
    return
  }
  const tujuanValue = guestForm.value.tujuan_kunjungan === 'Lainnya'
    ? guestForm.value.tujuan_kunjungan_lain?.trim()
    : guestForm.value.tujuan_kunjungan?.trim()
  if (!tujuanValue) {
    guestError.value = 'Tujuan kunjungan wajib diisi.'
    return
  }
  if (guestForm.value.no_telepon?.trim() && !isValidPhone(guestForm.value.no_telepon)) {
    guestError.value = 'Format nomor telepon tidak valid (min 10 karakter, angka/+/spasi).'
    return
  }

  guestLoading.value = true
  try {
    const formData = new FormData()
    formData.append('npsn', props.npsn)
    formData.append('nama_tamu', guestForm.value.nama_tamu.trim())
    if (guestForm.value.no_identitas?.trim()) formData.append('no_identitas', guestForm.value.no_identitas.trim())
    if (guestForm.value.instansi_asal?.trim()) formData.append('instansi_asal', guestForm.value.instansi_asal.trim())
    if (guestForm.value.no_telepon?.trim()) formData.append('no_telepon', guestForm.value.no_telepon.trim())
    formData.append('tujuan_kunjungan', tujuanValue)
    if (guestForm.value.orang_ditemui?.trim()) formData.append('orang_ditemui', guestForm.value.orang_ditemui.trim())
    if (guestForm.value.catatan?.trim()) formData.append('catatan', guestForm.value.catatan.trim())
    if (guestForm.value.website) formData.append('website', guestForm.value.website)
    if (selectedPhoto.value) formData.append('foto', selectedPhoto.value)

    await schoolPublicApi.submitGuestVisit(formData)
    guestSubmitted.value = true
    guestForm.value = { nama_tamu: '', no_identitas: '', instansi_asal: '', no_telepon: '', tujuan_kunjungan: '', tujuan_kunjungan_lain: '', orang_ditemui: '', catatan: '', website: '' }
    selectedPhoto.value = null
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
    photoPreview.value = ''
  } catch (e) {
    guestError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan. Silakan coba lagi.'
  } finally {
    guestLoading.value = false
  }
}

defineExpose({ sectionRef })
</script>

<style scoped>
.section { padding: 56px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 24px; letter-spacing: -0.02em; }
.section-title-text { display: inline-block; padding-bottom: 8px; border-bottom: 3px solid #059669; border-radius: 0 0 2px 0; }
.section-subtitle { font-size: 0.9375rem; color: #64748b; text-align: center; margin-bottom: 28px; line-height: 1.5; }
.buku-tamu-section { background: #fff; }
.card { background: #fff; border-radius: 16px; padding: 2rem 1.75rem; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04); border: 1px solid rgba(226, 232, 240, 0.8); transition: transform 0.3s ease, box-shadow 0.3s ease; }
.card--hover:hover { transform: translateY(-4px); box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.12); }
.guest-form { padding: 2rem; }
.guest-form-modal .form-section { margin-bottom: 1.25rem; }
.form-section-title { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin: 0 0 0.75rem 0; }
.form-section-title .required { color: #b91c1c; }
.form-section-title .optional { font-weight: 400; text-transform: none; color: #94a3b8; }
.field { margin-bottom: 0.75rem; }
.field label { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.25rem; color: #1e293b; }
.field .required { color: #b91c1c; }
.field input, .field select, .field textarea { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; background: #fff; }
.field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
.field textarea { resize: vertical; min-height: 60px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
.photo-block { margin-top: 0.25rem; }
.photo-input-hidden { position: absolute; width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; z-index: -1; }
.photo-buttons { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.btn-camera { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #059669; background: #ecfdf5; border: 1px solid rgba(5, 150, 105, 0.3); border-radius: 8px; cursor: pointer; }
.btn-camera:hover { background: #059669; color: #fff; border-color: #059669; }
.btn-camera.btn-small { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
.btn-link { padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #059669; background: none; border: none; cursor: pointer; text-decoration: underline; }
.camera-block { margin-top: 0.5rem; }
.camera-video { display: block; width: 100%; max-width: 320px; border-radius: 8px; background: #111; aspect-ratio: 4/3; object-fit: cover; }
.camera-error { font-size: 0.8125rem; color: #b91c1c; margin: 0.5rem 0 0 0; }
.camera-buttons { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.btn-capture { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #fff; background: #059669; border: none; border-radius: 8px; cursor: pointer; }
.btn-ghost { padding: 0.5rem 1rem; font-size: 0.875rem; color: #64748b; background: transparent; border: none; border-radius: 8px; cursor: pointer; }
.photo-preview-box { width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 0.5rem; }
.photo-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.form-hint { font-size: 0.75rem; color: #94a3b8; margin: 0.5rem 0 0 0; }
.field-hint { font-size: 0.75rem; margin-top: 0.25rem; }
.field-hint-error { color: #b91c1c; }
.field-mt { margin-top: 0.5rem; display: block; width: 100%; }
.hp { position: absolute; left: -9999px; opacity: 0; pointer-events: none; height: 0; overflow: hidden; }
.error-banner { background: #fef2f2; color: #b91c1c; padding: 1rem 1.25rem; border-radius: 12px; font-size: 0.9rem; margin-bottom: 1rem; border: 1px solid #fecaca; }
.btn-submit { width: 100%; padding: 1rem 1.5rem; background: linear-gradient(145deg, #059669 0%, #047857 100%); color: #fff; border: none; border-radius: 14px; font-size: 1.0625rem; font-weight: 700; cursor: pointer; transition: transform 0.1s, box-shadow 0.25s ease; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35); }
.btn-submit:hover:not(:disabled) { box-shadow: 0 10px 28px rgba(5, 150, 105, 0.4); transform: translateY(-1px); }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.state-wrap { max-width: 480px; margin: 0 auto; text-align: center; padding: 3rem 2rem; }
.state-success { animation: fadeInUp 0.5s ease; }
.success-icon-wrap { width: 72px; height: 72px; margin: 0 auto 1.25rem; background: linear-gradient(135deg, #059669, #047857); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px -4px rgba(5, 150, 105, 0.35); }
.success-icon { width: 36px; height: 36px; color: #fff; }
.state-success h2 { margin: 0 0 0.5rem; font-size: 1.35rem; color: #1e293b; }
.state-success p { margin: 0; color: #64748b; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.reveal-section .section-title,
.reveal-section .card {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible .section-title { opacity: 1; transform: translateY(0); transition-delay: 0.1s; }
.reveal-section.is-visible .card { opacity: 1; transform: translateY(0); transition-delay: 0.2s; }
</style>
