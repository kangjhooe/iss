<template>
  <div class="ppdb-berkas-page">
    <div class="page-bg" aria-hidden="true">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <nav class="top-bar">
      <router-link to="/" class="top-bar-link">← Beranda</router-link>
      <span class="top-bar-brand">PPDB — Lengkapi Berkas</span>
    </nav>

    <header class="hero">
      <div class="hero-inner">
        <h1 class="hero-title">Lengkapi Berkas</h1>
        <p class="hero-subtitle">Unggah dokumen pendukung calon peserta didik (foto, KK, akte, dll). Maks. 2 MB per file, PDF/JPG/PNG.</p>
      </div>
    </header>

    <main class="public-main">
      <div class="card form-card">
        <section class="form-section">
          <h3 class="section-title">Identitas Pendaftaran</h3>
          <div class="form-grid">
            <div class="form-group">
              <label>Nomor Pendaftaran <span class="required">*</span></label>
              <input
                v-model="registrationNumber"
                type="text"
                class="form-input"
                placeholder="Contoh: 10648387-1-00001"
                @input="uploadError = ''"
              />
            </div>
            <div class="form-group">
              <label>Tanggal Lahir <span class="required">*</span></label>
              <input
                v-model="birthDate"
                type="date"
                class="form-input"
                @input="uploadError = ''"
              />
            </div>
            <div class="form-group">
              <label>NPSN Sekolah (opsional)</label>
              <input
                v-model="npsnSchool"
                type="text"
                class="form-input"
                placeholder="10 digit NPSN sekolah tujuan"
                maxlength="20"
              />
            </div>
          </div>
        </section>

        <section class="form-section">
          <h3 class="section-title">Unggah Dokumen</h3>
          <p class="section-hint">Pilih jenis berkas sesuai daftar sekolah, lalu unggah PDF/JPG/PNG (maks. 2 MB). Maksimal 20 file per calon.</p>
          <div v-if="checklistLoading" class="checklist-status">Memuat daftar berkas...</div>
          <div v-else-if="checklistError" class="field-error">{{ checklistError }}</div>
          <ul v-if="checklistItems.length" class="checklist">
            <li v-for="item in checklistItems" :key="item.key" :class="{ done: item.uploaded, required: item.required && !item.uploaded }">
              <span class="check-mark">{{ item.uploaded ? '✓' : '○' }}</span>
              <span>{{ item.label }}</span>
              <span class="check-meta">{{ item.required ? 'Wajib' : 'Opsional' }}{{ item.uploaded ? ' · sudah unggah' : '' }}</span>
            </li>
          </ul>
          <p v-else-if="checklistLoaded && !checklistItems.length" class="section-hint">Sekolah belum mengatur daftar berkas wajib. Isi nama berkas secara manual.</p>
          <div class="upload-row">
            <div v-if="checklistItems.length" class="form-group flex-1">
              <label>Jenis berkas <span class="required">*</span></label>
              <select v-model="selectedKey" class="form-input" @change="uploadError = ''">
                <option value="">Pilih jenis</option>
                <option v-for="item in checklistItems" :key="item.key" :value="item.key">
                  {{ item.label }}{{ item.uploaded ? ' (ganti)' : '' }}
                </option>
                <option value="__other__">Lainnya</option>
              </select>
            </div>
            <div v-if="needsCustomName" class="form-group flex-1">
              <label>Nama berkas <span class="required">*</span></label>
              <input
                v-model="docName"
                type="text"
                class="form-input"
                placeholder="Contoh: Surat pindah"
                @input="uploadError = ''"
              />
            </div>
            <div class="form-group flex-1">
              <label>File <span class="required">*</span></label>
              <input
                ref="fileInputRef"
                type="file"
                class="form-input file-input"
                accept=".pdf,.jpg,.jpeg,.png"
                @change="onFileSelect"
              />
            </div>
            <div class="form-group btn-wrap">
              <button
                type="button"
                class="btn-upload"
                :disabled="uploading || !canUpload"
                @click="uploadOne"
              >
                <span v-if="uploading" class="btn-spinner"></span>
                <template v-else>Unggah</template>
              </button>
            </div>
          </div>
          <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
          <p v-if="uploadSuccess" class="field-success">{{ uploadSuccess }}</p>
          <ul v-if="uploadedList.length" class="uploaded-list">
            <li v-for="(item, i) in uploadedList" :key="i">
              <span class="uploaded-name">{{ item.name }}</span>
              <span class="uploaded-time">{{ item.at }}</span>
            </li>
          </ul>
        </section>

        <div class="form-actions">
          <router-link to="/daftar-ppdb" class="btn-outline">Daftar PPDB</router-link>
          <router-link to="/cek-hasil-ppdb" class="btn-outline btn-outline-primary">Cek hasil seleksi →</router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ppdbPublicApi } from '@/api/ppdbPublic'

const route = useRoute()
const registrationNumber = ref('')
const birthDate = ref('')
const npsnSchool = ref('')
const docName = ref('')
const selectedKey = ref('')
const fileInputRef = ref(null)
const selectedFile = ref(null)
const uploading = ref(false)
const uploadError = ref('')
const uploadSuccess = ref('')
const uploadedList = ref([])
const checklistLoading = ref(false)
const checklistLoaded = ref(false)
const checklistError = ref('')
const checklistItems = ref([])

const needsCustomName = computed(() => {
  if (!checklistItems.value.length) return true
  return selectedKey.value === '__other__'
})

const canUpload = computed(() => {
  if (!registrationNumber.value.trim() || !birthDate.value || !selectedFile.value) return false
  if (needsCustomName.value) return !!docName.value.trim()
  return !!selectedKey.value
})

function onFileSelect(e) {
  uploadError.value = ''
  const f = e.target.files?.[0]
  selectedFile.value = f || null
}

function clearUploadForm() {
  if (needsCustomName.value) docName.value = ''
  selectedFile.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function checklistParams() {
  const params = {
    registration_number: registrationNumber.value.trim(),
    birth_date: birthDate.value,
  }
  if (npsnSchool.value.trim()) params.npsn = npsnSchool.value.trim()
  return params
}

async function loadChecklist() {
  if (!registrationNumber.value.trim() || !birthDate.value) {
    checklistItems.value = []
    checklistLoaded.value = false
    checklistError.value = ''
    return
  }
  checklistLoading.value = true
  checklistError.value = ''
  try {
    const res = await ppdbPublicApi.getDocumentChecklist(checklistParams())
    const data = res.data?.data || res.data
    checklistItems.value = data?.document_summary?.items || []
    checklistLoaded.value = true
  } catch (e) {
    checklistItems.value = []
    checklistLoaded.value = false
    checklistError.value = e.response?.data?.message || e.formattedMessage || 'Nomor pendaftaran tidak ditemukan.'
  } finally {
    checklistLoading.value = false
  }
}

async function uploadOne() {
  if (!canUpload.value) return
  uploadError.value = ''
  uploadSuccess.value = ''
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('registration_number', registrationNumber.value.trim())
    formData.append('birth_date', birthDate.value)
    if (npsnSchool.value.trim()) formData.append('npsn', npsnSchool.value.trim())
    const key = selectedKey.value && selectedKey.value !== '__other__' ? selectedKey.value : ''
    if (key) formData.append('document_key', key)
    if (needsCustomName.value) formData.append('name', docName.value.trim())
    formData.append('file', selectedFile.value)
    const res = await ppdbPublicApi.uploadDocument(formData)
    const uploadedName = res.data?.data?.name || docName.value.trim() || 'Berkas'
    uploadedList.value.unshift({
      name: uploadedName,
      at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    })
    if (res.data?.document_summary?.items) {
      checklistItems.value = res.data.document_summary.items
      checklistLoaded.value = true
    } else {
      await loadChecklist()
    }
    uploadSuccess.value = 'Berkas berhasil diunggah.'
    clearUploadForm()
    setTimeout(() => { uploadSuccess.value = '' }, 3000)
  } catch (e) {
    const data = e.response?.data
    if (data?.errors && typeof data.errors === 'object') {
      const first = Object.values(data.errors).flat()
      uploadError.value = Array.isArray(first) ? first[0] : first || data.message || 'Gagal mengunggah.'
    } else {
      uploadError.value = data?.message || e.formattedMessage || 'Gagal mengunggah. Periksa nomor pendaftaran dan format file (PDF/JPG/PNG, max 2MB).'
    }
  } finally {
    uploading.value = false
  }
}

let checklistTimer = null
watch([registrationNumber, birthDate, npsnSchool], () => {
  if (checklistTimer) clearTimeout(checklistTimer)
  checklistTimer = setTimeout(loadChecklist, 400)
})

onMounted(() => {
  const q = route.query
  if (q.registration_number) registrationNumber.value = q.registration_number
  if (q.birth_date) birthDate.value = q.birth_date
  if (q.npsn) npsnSchool.value = q.npsn
})
</script>

<style scoped>
.ppdb-berkas-page { min-height: 100vh; padding-bottom: 3rem; }
.page-bg {
  position: fixed;
  inset: 0;
  z-index: -1;
  background: #f8fafc;
  overflow: hidden;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.4;
}
.blob-1 { width: 320px; height: 320px; background: #c7d2fe; top: -80px; right: -80px; }
.blob-2 { width: 280px; height: 280px; background: #a5b4fc; bottom: 20%; left: -60px; }

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
}
.top-bar-link { color: #059669; font-weight: 500; text-decoration: none; }
.top-bar-link:hover { text-decoration: underline; }
.top-bar-brand { font-weight: 600; color: #334155; }

.hero {
  padding: 2rem 1.25rem 1.5rem;
  text-align: center;
}
.hero-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: #1e293b; }
.hero-subtitle { margin: 0.5rem 0 0; font-size: 0.9rem; color: #64748b; max-width: 520px; margin-left: auto; margin-right: auto; }

.public-main { max-width: 640px; margin: 0 auto; padding: 0 1.25rem; }
.card {
  background: #fff;
  border-radius: 16px;
  padding: 1.5rem 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  border: 1px solid #e2e8f0;
}
.form-section { margin-bottom: 1.5rem; }
.section-title { margin: 0 0 0.75rem; font-size: 1.05rem; font-weight: 600; color: #334155; }
.section-hint { margin: 0 0 1rem; font-size: 0.85rem; color: #64748b; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
.form-group { margin-bottom: 0; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; font-size: 0.9rem; color: #475569; }
.required { color: #b91c1c; }
.form-input {
  width: 100%;
  padding: 0.6rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
}
.form-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15); }
.file-input { padding: 0.5rem; }

.upload-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
}
.flex-1 { flex: 1; min-width: 140px; }
.btn-wrap { flex-shrink: 0; }
.btn-upload {
  padding: 0.65rem 1.25rem;
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  min-width: 100px;
}
.btn-upload:hover:not(:disabled) { opacity: 0.95; }
.btn-upload:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.field-error { color: #b91c1c; font-size: 0.9rem; margin-top: 0.5rem; }
.field-success { color: #059669; font-size: 0.9rem; margin-top: 0.5rem; }
.uploaded-list {
  margin: 1rem 0 0;
  padding-left: 1.25rem;
  font-size: 0.9rem;
  color: #475569;
}
.uploaded-list li { margin-bottom: 0.35rem; }
.uploaded-name { font-weight: 500; }
.uploaded-time { margin-left: 0.5rem; color: #94a3b8; font-size: 0.85rem; }
.checklist-status { font-size: 0.9rem; color: #64748b; margin-bottom: 0.75rem; }
.checklist {
  list-style: none;
  margin: 0 0 1rem;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.checklist li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #475569;
  padding: 0.35rem 0.5rem;
  border-radius: 8px;
  background: #f8fafc;
}
.checklist li.done { color: #047857; background: #ecfdf5; }
.checklist li.required { color: #b91c1c; background: #fef2f2; }
.check-mark { font-weight: 700; width: 1.1rem; }
.check-meta { margin-left: auto; font-size: 0.78rem; color: #94a3b8; }

.form-actions { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; }
.btn-outline {
  display: inline-block;
  padding: 0.6rem 1.25rem;
  border: 1px solid #059669;
  color: #059669;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
}
.btn-outline:hover { background: #ecfdf5; }
.btn-outline-primary { background: #ecfdf5; border-color: #059669; color: #059669; }
.btn-outline-primary:hover { background: #d1fae5; }
</style>
