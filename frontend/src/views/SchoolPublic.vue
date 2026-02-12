<template>
  <div class="school-public-page">
    <div class="page-bg" aria-hidden="true">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12h6v10" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="navbar-title">{{ institution?.name || 'Sekolah' }}</span>
        </router-link>
        <div class="navbar-links">
          <router-link to="/" class="nav-link">Beranda</router-link>
          <router-link :to="`/${npsn}/daftar-ppdb`" class="nav-link">Daftar PPDB</router-link>
          <router-link to="/lengkapi-berkas-ppdb" class="nav-link">Lengkapi Berkas</router-link>
          <router-link to="/cek-hasil-ppdb" class="nav-link">Cek Hasil PPDB</router-link>
          <a href="#buku-tamu" class="nav-link">Buku Tamu</a>
          <a href="#kontak" class="nav-link">Kontak</a>
        </div>
        <div class="navbar-actions">
          <router-link to="/login" class="btn btn-primary">Masuk</router-link>
        </div>
      </div>
    </nav>

    <!-- Loading / Error -->
    <div v-if="loading" class="state-wrap state-loading card">
      <div class="spinner"></div>
      <p>Memuat data sekolah...</p>
    </div>
    <div v-else-if="error" class="state-wrap state-error card">
      <div class="state-icon state-icon-error">!</div>
      <h2>Sekolah tidak ditemukan</h2>
      <p>NPSN tidak valid atau sekolah tidak aktif.</p>
      <router-link to="/" class="btn-outline">← Beranda</router-link>
    </div>

    <template v-else-if="institution">
      <!-- Hero -->
      <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <h1 class="hero-title">{{ institution.name }}</h1>
          <p v-if="institution.address" class="hero-subtitle">{{ institution.address }}</p>
          <p v-if="institution.npsn" class="hero-npsn">NPSN {{ institution.npsn }}</p>
          <div class="hero-actions">
            <router-link :to="`/${npsn}/daftar-ppdb`" class="btn btn-primary btn-lg">Daftar PPDB</router-link>
            <router-link to="/cek-hasil-ppdb" class="btn btn-secondary btn-lg">Cek Hasil PPDB</router-link>
          </div>
        </div>
      </section>

      <!-- Profil singkat -->
      <section class="section profile-section">
        <div class="section-inner">
          <h2 class="section-title">Profil Sekolah</h2>
          <div class="profile-card card">
            <p v-if="institution.description" class="profile-desc">{{ institution.description }}</p>
            <dl class="profile-list">
              <div v-if="institution.address" class="profile-row">
                <dt>Alamat</dt>
                <dd>{{ institution.address }}</dd>
              </div>
              <div v-if="institution.phone" class="profile-row">
                <dt>Telepon</dt>
                <dd><a :href="'tel:' + institution.phone">{{ institution.phone }}</a></dd>
              </div>
              <div v-if="institution.email" class="profile-row">
                <dt>Email</dt>
                <dd><a :href="'mailto:' + institution.email">{{ institution.email }}</a></dd>
              </div>
              <div v-if="institution.website" class="profile-row">
                <dt>Website</dt>
                <dd><a :href="institution.website" target="_blank" rel="noopener">{{ institution.website }}</a></dd>
              </div>
            </dl>
          </div>
        </div>
      </section>

      <!-- Buku Tamu (form sama dengan dashboard admin + keamanan honeypot & throttle) -->
      <section id="buku-tamu" class="section buku-tamu-section">
        <div class="section-inner">
          <h2 class="section-title">Buku Tamu</h2>
          <p class="section-subtitle">Isi form di bawah untuk mencatat kunjungan Anda. Form sama dengan yang digunakan di dashboard sekolah.</p>

          <div v-if="guestSubmitted" class="state-wrap state-success card">
            <div class="success-icon-wrap">
              <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"/>
              </svg>
            </div>
            <h2>Terima kasih</h2>
            <p>Data kunjungan Anda telah dicatat.</p>
          </div>

          <form v-else class="guest-form card guest-form-modal" @submit.prevent="submitGuest">
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
            <!-- Honeypot: jangan isi. Bot biasanya mengisi. -->
            <div class="hp" aria-hidden="true">
              <label for="website_hp">Website</label>
              <input id="website_hp" v-model="guestForm.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hp-input" />
            </div>
            <div v-if="guestError" class="error-banner">{{ guestError }}</div>
            <button type="submit" class="btn-submit" :disabled="guestLoading">
              <span v-if="guestLoading" class="btn-spinner"></span>
              {{ guestLoading ? 'Mengirim...' : 'Catat tamu' }}
            </button>
          </form>
        </div>
      </section>

      <!-- Kontak & Peta -->
      <section id="kontak" class="section contact-section">
        <div class="section-inner">
          <h2 class="section-title">Lokasi</h2>
          <div v-if="institution.latitude && institution.longitude" class="map-wrap">
            <a
              :href="`https://www.google.com/maps?q=${institution.latitude},${institution.longitude}`"
              target="_blank"
              rel="noopener"
              class="map-link card"
            >
              <span class="map-link-icon">📍</span>
              <span>Buka di Google Maps</span>
            </a>
          </div>
          <p v-else-if="institution.address" class="contact-address">{{ institution.address }}</p>
        </div>
      </section>

      <!-- Footer -->
      <footer class="footer">
        <div class="footer-inner">
          <div class="footer-brand">
            <span class="footer-name">{{ institution.name }}</span>
            <p v-if="institution.npsn" class="footer-npsn">NPSN {{ institution.npsn }}</p>
          </div>
          <div class="footer-links">
            <router-link :to="`/${npsn}/daftar-ppdb`">Daftar PPDB</router-link>
            <router-link to="/cek-hasil-ppdb">Cek Hasil PPDB</router-link>
            <router-link to="/login">Masuk</router-link>
          </div>
          <div class="footer-bottom">
            <p>&copy; {{ currentYear }} {{ institution.name }}</p>
          </div>
        </div>
      </footer>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { schoolPublicApi } from '@/api/schoolPublic'

const TUJUAN_OPTIONS = ['Rapat', 'Urusan siswa', 'Dinas', 'Kunjungan', 'Lainnya']

const route = useRoute()
const npsn = computed(() => route.params.npsn)

const institution = ref(null)
const loading = ref(true)
const error = ref('')

const guestForm = ref({
  nama_tamu: '',
  no_identitas: '',
  instansi_asal: '',
  no_telepon: '',
  tujuan_kunjungan: '',
  tujuan_kunjungan_lain: '',
  orang_ditemui: '',
  catatan: '',
  website: '', // honeypot
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
const cameraStreamRef = ref(null)

const currentYear = computed(() => new Date().getFullYear())

function isValidPhone(val) {
  if (!val || !String(val).trim()) return true
  return /^[\d\s+()-]{10,32}$/.test(String(val).trim())
}

async function fetchInstitution() {
  if (!npsn.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await schoolPublicApi.getInstitution(npsn.value)
    institution.value = data.data
  } catch (e) {
    const msg = e.response?.data?.message || 'Gagal memuat data sekolah.'
    error.value = msg
    institution.value = null
  } finally {
    loading.value = false
  }
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
    cameraStreamRef.value = stream
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
  const stream = cameraStreamRef.value
  if (stream) {
    stream.getTracks().forEach((t) => t.stop())
    cameraStreamRef.value = null
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
    formData.append('npsn', npsn.value)
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

onMounted(() => fetchInstitution())
watch(npsn, () => fetchInstitution())
</script>

<style scoped>
.school-public-page {
  min-height: 100vh;
  background: #f8fafc;
  position: relative;
}

.page-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.4;
}
.blob-1 { width: 400px; height: 400px; background: #c7d2fe; top: -100px; right: -100px; }
.blob-2 { width: 300px; height: 300px; background: #e9d5ff; bottom: 20%; left: -80px; }
.blob-3 { width: 250px; height: 250px; background: #ddd6fe; bottom: -50px; right: 20%; }

.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid #e2e8f0;
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  position: relative;
  z-index: 10;
}
.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 18px;
}
.navbar-brand:hover { color: #667eea; }
.navbar-logo img { width: 36px; height: 36px; object-fit: contain; }
.navbar-logo-img { width: 36px; height: 36px; object-fit: contain; border-radius: 8px; }
.navbar-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
.navbar-links { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
.nav-link {
  color: #64748b;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.2s;
}
.nav-link:hover { color: #667eea; }
.navbar-actions { display: flex; gap: 12px; align-items: center; }
.btn {
  padding: 10px 20px;
  min-height: 44px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
}
.btn-primary { background: #667eea; color: white; }
.btn-primary:hover { background: #5568d3; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25); }
.btn-secondary { background: white; color: #667eea; border: 1.5px solid #667eea; }
.btn-secondary:hover { background: #f8fafc; border-color: #5568d3; color: #5568d3; }
.btn-lg { padding: 14px 28px; font-size: 16px; }

.state-wrap {
  max-width: 480px;
  margin: 48px auto;
  text-align: center;
  padding: 3rem 2rem;
  position: relative;
  z-index: 1;
}
.card {
  background: #fff;
  border-radius: 20px;
  padding: 2rem 1.75rem;
  box-shadow: 0 10px 40px -12px rgba(79, 70, 229, 0.12), 0 4px 12px -4px rgba(0,0,0,0.06);
  border: 1px solid rgba(255,255,255,0.8);
}
.state-loading { color: #64748b; }
.state-loading .spinner {
  width: 44px;
  height: 44px;
  margin: 0 auto 1.25rem;
  border: 3px solid #e9d5ff;
  border-top-color: #7c3aed;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.state-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 1.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.4rem;
}
.state-icon-error { background: #fee2e2; color: #b91c1c; }
.state-error { color: #b91c1c; }
.state-error h2 { margin: 0 0 0.5rem; font-size: 1.25rem; }
.state-error p { margin: 0 0 1rem; }
.btn-outline {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #6d28d9;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  font-size: 0.95rem;
  border: 2px solid #8b5cf6;
  transition: background 0.2s, color 0.2s;
}
.btn-outline:hover { background: #f5f3ff; color: #5b21b6; }

.hero {
  position: relative;
  padding: 72px 24px 80px;
  text-align: center;
  z-index: 1;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%);
  z-index: 0;
}
.hero-content { position: relative; z-index: 1; max-width: 720px; margin: 0 auto; }
.hero-title { font-size: 38px; font-weight: 700; color: #1e293b; margin-bottom: 12px; letter-spacing: -0.02em; line-height: 1.2; }
.hero-subtitle { font-size: 17px; color: #64748b; margin-bottom: 8px; }
.hero-npsn { font-size: 14px; color: #94a3b8; margin-bottom: 28px; }
.hero-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

.section { padding: 48px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 24px; font-weight: 700; color: #1e293b; text-align: center; margin-bottom: 8px; }
.section-subtitle { font-size: 15px; color: #64748b; text-align: center; margin-bottom: 24px; }

.profile-section { background: #fff; }
.profile-card { padding: 2rem; }
.profile-desc { color: #475569; line-height: 1.7; margin-bottom: 1.5rem; }
.profile-list { margin: 0; }
.profile-row { margin-bottom: 0.75rem; }
.profile-row dt { font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem; }
.profile-row dd { margin: 0; font-size: 1rem; color: #1e293b; }
.profile-row a { color: #667eea; text-decoration: none; }
.profile-row a:hover { text-decoration: underline; }

.buku-tamu-section { background: #f8fafc; }
.guest-form { padding: 2rem; }
.guest-form-modal .form-section { margin-bottom: 1.25rem; }
.guest-form-modal .form-section:last-of-type { margin-bottom: 0; }
.form-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin: 0 0 0.75rem 0;
}
.form-section-title .required { color: #b91c1c; }
.form-section-title .optional { font-weight: 400; text-transform: none; color: #94a3b8; }
.field { margin-bottom: 0.75rem; }
.field:last-child { margin-bottom: 0; }
.field label { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.25rem; color: #1e293b; }
.field .required { color: #b91c1c; }
.field input,
.field select,
.field textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #fff;
}
.field input:focus,
.field select:focus,
.field textarea:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
}
.field textarea { resize: vertical; min-height: 60px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
.photo-block { margin-top: 0.25rem; }
.photo-input-hidden { position: absolute; width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; z-index: -1; }
.photo-buttons { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.btn-camera {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
  color: #6d28d9; background: #f5f3ff; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; cursor: pointer;
}
.btn-camera:hover { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }
.btn-camera.btn-small { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
.btn-link { padding: 0.5rem 0.75rem; font-size: 0.8125rem; color: #6d28d9; background: none; border: none; cursor: pointer; text-decoration: underline; }
.btn-link:hover { color: #5b21b6; }
.camera-block { margin-top: 0.5rem; }
.camera-video { display: block; width: 100%; max-width: 320px; border-radius: 8px; background: #111; aspect-ratio: 4/3; object-fit: cover; }
.camera-error { font-size: 0.8125rem; color: #b91c1c; margin: 0.5rem 0 0 0; }
.camera-buttons { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.btn-capture { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #fff; background: #8b5cf6; border: none; border-radius: 8px; cursor: pointer; }
.btn-ghost { padding: 0.5rem 1rem; font-size: 0.875rem; color: #64748b; background: transparent; border: none; border-radius: 8px; cursor: pointer; }
.btn-ghost:hover { background: #f1f5f9; color: #1e293b; }
.photo-preview-box { width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 0.5rem; }
.photo-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.form-hint { font-size: 0.75rem; color: #94a3b8; margin: 0.5rem 0 0 0; }
.field-hint { font-size: 0.75rem; margin-top: 0.25rem; }
.field-hint-error { color: #b91c1c; }
.field-mt { margin-top: 0.5rem; display: block; width: 100%; }
.hp { position: absolute; left: -9999px; opacity: 0; pointer-events: none; height: 0; overflow: hidden; }
.hp-input { width: 1px; height: 1px; }
.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  border: 1px solid #fecaca;
}
.btn-submit {
  width: 100%;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.05s, box-shadow 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: 0 4px 14px -2px rgba(124, 58, 237, 0.4);
}
.btn-submit:hover:not(:disabled) { box-shadow: 0 6px 20px -4px rgba(124, 58, 237, 0.45); }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.state-success { text-align: center; padding: 3rem 2rem; }
.success-icon-wrap {
  width: 72px;
  height: 72px;
  margin: 0 auto 1.25rem;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px -4px rgba(124, 58, 237, 0.35);
}
.success-icon { width: 36px; height: 36px; color: #fff; }
.state-success h2 { margin: 0 0 0.5rem; font-size: 1.35rem; color: #1e293b; }
.state-success p { margin: 0; color: #64748b; }

.contact-section { background: #fff; }
.map-wrap { text-align: center; }
.map-link {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.5rem;
  text-decoration: none;
  color: #6d28d9;
  font-weight: 600;
  border-radius: 14px;
  border: 2px solid #8b5cf6;
  transition: background 0.2s, color 0.2s;
}
.map-link:hover { background: #f5f3ff; color: #5b21b6; }
.map-link-icon { font-size: 1.5rem; }
.contact-address { text-align: center; color: #64748b; margin: 0; }

.footer {
  background: #1e293b;
  color: #94a3b8;
  padding: 40px 24px 24px;
  margin-top: 48px;
  position: relative;
  z-index: 1;
}
.footer-inner { max-width: 1100px; margin: 0 auto; text-align: center; }
.footer-brand { margin-bottom: 1rem; }
.footer-name { font-weight: 600; font-size: 1.1rem; color: #f1f5f9; display: block; }
.footer-npsn { font-size: 0.9rem; margin: 0.25rem 0 0; }
.footer-links { display: flex; gap: 24px; justify-content: center; margin-bottom: 1.5rem; flex-wrap: wrap; }
.footer-links a { color: #94a3b8; text-decoration: none; }
.footer-links a:hover { color: #e2e8f0; }
.footer-bottom p { margin: 0; font-size: 0.875rem; color: #64748b; }
</style>
