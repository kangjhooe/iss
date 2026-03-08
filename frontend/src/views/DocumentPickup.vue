<template>
  <Layout>
    <div class="document-pickup-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 18V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 15L12 12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="header-text">
            <h1 class="page-title">Pengambilan Ijazah</h1>
            <p class="page-subtitle">Catatan pengambilan dokumen (ijazah, raport, SKHUN, dll) oleh alumni</p>
          </div>
          <button type="button" @click="openAddModal" class="btn-primary btn-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Catat Pengambilan
          </button>
        </div>
      </header>

      <div class="toolbar">
        <div class="toolbar-left">
          <span class="stat-badge">{{ pagination.total }} pencatatan</span>
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Cari nama siswa, NIS, NISN..."
              class="search-input"
              @input="debounceSearch"
            />
          </div>
          <div class="filter-dates">
            <input v-model="filters.date_from" type="date" class="filter-date" @change="loadList" />
            <span class="date-sep">–</span>
            <input v-model="filters.date_to" type="date" class="filter-date" @change="loadList" />
          </div>
        </div>
        <div class="toolbar-right">
          <button type="button" @click="resetFilters" class="btn-reset">Reset filter</button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="8" :cell-widths="['90px', '1fr', '100px', '1fr', '120px', '120px', '80px', '100px']" />
      </div>

      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada data pengambilan ijazah</h3>
        <p class="empty-desc">Klik "Catat Pengambilan" untuk mencatat pengambilan dokumen oleh alumni (siswa status Lulus).</p>
        <button type="button" @click="openAddModal" class="btn-primary btn-empty-cta">Catat Pengambilan</button>
      </div>

      <div v-else class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-date">Tanggal</th>
                <th class="th-student">Siswa (Alumni)</th>
                <th class="th-docs">Dokumen Diambil</th>
                <th class="th-nomor">No. Ijazah / Kode Blangko</th>
                <th class="th-received">Diterima oleh</th>
                <th class="th-photo">Foto</th>
                <th class="col-actions">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in list" :key="item.id">
                <td class="td-date">{{ formatDateTime(item.pickup_date) }}</td>
                <td class="td-student">
                  <span class="student-name">{{ item.student?.name || '—' }}</span>
                  <span class="student-meta">{{ item.student?.nisn ? `NISN: ${item.student.nisn}` : item.student?.nis ? `NIS: ${item.student.nis}` : '' }} <template v-if="item.student?.graduation_year">· Lulus {{ item.student.graduation_year }}</template></span>
                </td>
                <td class="td-docs">{{ formatItemsTaken(item) }}</td>
                <td class="td-nomor">{{ item.nomor_ijazah || item.kode_blangko || '—' }}</td>
                <td class="td-received">{{ item.received_by || '—' }}</td>
                <td class="td-photo">
                  <img v-if="item.photo_url" :src="item.photo_url" alt="" class="pickup-photo" />
                  <span v-else class="no-photo">—</span>
                </td>
                <td class="col-actions">
                  <div class="action-buttons">
                    <button type="button" @click="openEditModal(item)" class="btn-icon btn-edit" title="Edit" aria-label="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button type="button" @click="confirmDelete(item)" class="btn-icon btn-delete" title="Hapus" aria-label="Hapus">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="pagination.last_page > 1" class="pagination">
          <span class="pagination-info">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}</span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- Modal: Catat / Edit Pengambilan -->
      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal form-modal">
          <header class="modal-header">
            <h2 class="modal-title">{{ editingItem ? 'Edit Pengambilan Ijazah' : 'Catat Pengambilan Baru' }}</h2>
            <button type="button" @click="showFormModal = false" class="modal-close" aria-label="Tutup">×</button>
          </header>
          <form @submit.prevent="submitForm" class="modal-body">
            <section class="form-section">
              <h3 class="form-section-title">Siswa (Alumni) <span class="required">*</span></h3>
              <div v-if="editingItem" class="field">
                <input :value="editingItem.student?.name" type="text" class="form-select" readonly disabled />
                <p class="form-hint">Siswa tidak dapat diubah saat edit.</p>
              </div>
              <div v-else class="field alumni-combobox-wrap">
                <label>Pilih alumni (ketik nama/NIS/NISN untuk cari)</label>
                <input
                  v-model="alumniSearch"
                  type="text"
                  placeholder="Ketik nama/NIS/NISN lalu pilih dari daftar"
                  class="form-select"
                  autocomplete="off"
                  @focus="alumniDropdownOpen = true"
                  @blur="closeAlumniDropdown"
                  @input="debounceAlumniSearch"
                />
                <div v-if="alumniDropdownOpen" class="alumni-dropdown" @mousedown.prevent>
                  <p v-if="alumniLoading" class="form-hint">Memuat daftar alumni...</p>
                  <ul v-else-if="alumniOptions.length === 0" class="alumni-list">
                    <li class="alumni-list-empty">Tidak ada alumni. Ketik untuk mencari.</li>
                  </ul>
                  <ul v-else class="alumni-list">
                    <li
                      v-for="a in alumniOptions"
                      :key="a.id"
                      class="alumni-option"
                      @mousedown="selectAlumni(a)"
                    >
                      {{ a.name }} {{ a.nisn ? `(${a.nisn})` : a.nis ? `(${a.nis})` : '' }} — Lulus {{ a.graduation_year || '?' }}
                    </li>
                  </ul>
                </div>
                <p v-if="!editingItem && alumniOptions.length === 0 && !alumniLoading" class="form-hint">Hanya siswa status Lulus. Kosongkan untuk memuat daftar.</p>
              </div>
            </section>
            <section class="form-section">
              <h3 class="form-section-title">Tanggal & Dokumen</h3>
              <div class="field">
                <label>Tanggal & jam pengambilan <span class="required">*</span></label>
                <input v-model="form.pickup_date" type="datetime-local" required />
              </div>
              <div class="field">
                <label>Dokumen yang diambil</label>
                <div class="checkbox-group">
                  <label class="checkbox-label"><input v-model="form.taken_ijazah" type="checkbox" /> Ijazah</label>
                  <label class="checkbox-label"><input v-model="form.taken_raport" type="checkbox" /> Raport</label>
                  <label class="checkbox-label"><input v-model="form.taken_skhun" type="checkbox" /> SKHUN</label>
                </div>
              </div>
              <div class="field">
                <label>Dokumen lainnya (ketik manual)</label>
                <textarea v-model="form.dokumen_lainnya" rows="2" maxlength="1000" placeholder="Contoh: Surat Keterangan, Transkrip, Piagam"></textarea>
              </div>
              <div class="form-row">
                <div class="field">
                  <label>Nomor Ijazah</label>
                  <input v-model="form.nomor_ijazah" type="text" maxlength="64" placeholder="Nomor ijazah" />
                </div>
                <div class="field">
                  <label>Kode Blangko</label>
                  <input v-model="form.kode_blangko" type="text" maxlength="64" placeholder="Kode blangko" />
                </div>
              </div>
            </section>
            <section class="form-section">
              <h3 class="form-section-title">Foto serah terima ijazah <span class="required">*</span></h3>
              <div class="photo-block">
                <input
                  id="doc-pickup-photo-input"
                  ref="photoInputRef"
                  type="file"
                  accept="image/*"
                  class="photo-input-hidden"
                  @change="onPhotoSelect"
                />
                <template v-if="showCameraUI">
                  <div class="camera-preview-wrap">
                    <video ref="cameraVideoRef" autoplay playsinline muted class="camera-video"></video>
                    <p v-if="cameraLoading" class="camera-loading-text">Memuat kamera...</p>
                    <p v-if="cameraError" class="form-error camera-error-inline">{{ cameraError }}</p>
                    <div class="camera-actions">
                      <button type="button" class="btn-primary" :disabled="cameraLoading" @click="captureFromCamera">
                        Ambil foto
                      </button>
                      <button type="button" class="btn-ghost" @click="closeCamera">Batal</button>
                    </div>
                  </div>
                </template>
                <template v-else-if="!photoPreview && !editingItem?.photo_url">
                  <div class="photo-buttons">
                    <button type="button" class="btn-camera" @click="startCamera">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                      </svg>
                      Buka kamera
                    </button>
                    <span class="photo-or">atau</span>
                    <label for="doc-pickup-photo-input" class="btn-camera btn-camera-secondary label-as-button">
                      Pilih dari file
                    </label>
                  </div>
                </template>
                <template v-else>
                  <div class="photo-preview-box">
                    <img v-if="photoPreview" :src="photoPreview" alt="Preview" />
                    <img v-else-if="editingItem?.photo_url" :src="editingItem.photo_url" alt="Foto saat ini" />
                  </div>
                  <div class="photo-buttons">
                    <button type="button" class="btn-camera" @click="startCamera">Ambil foto lagi</button>
                    <label for="doc-pickup-photo-input" class="btn-link btn-small label-as-button">Pilih file lain</label>
                  </div>
                </template>
                <p class="form-hint">Wajib. Klik "Buka kamera" untuk webcam (desktop) atau kamera (HP). Maks. 5MB.</p>
              </div>
            </section>
            <section class="form-section">
              <h3 class="form-section-title">Lainnya</h3>
              <div class="field">
                <label>Diterima oleh (petugas)</label>
                <input v-model="form.received_by" type="text" maxlength="255" placeholder="Nama petugas yang menyerahkan" />
              </div>
              <div class="field">
                <label>Catatan</label>
                <textarea v-model="form.notes" rows="2" placeholder="Opsional"></textarea>
              </div>
            </section>
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <footer class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-ghost">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingItem ? 'Simpan' : 'Catat pengambilan') }}
              </button>
            </footer>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Data Pengambilan"
        :message="deleteMessage"
        confirmText="Hapus"
        @confirm="doDelete"
        @cancel="deleteTarget = null"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import documentPickupApi from '@/api/documentPickup'
import { alumniApi } from '@/api/alumni'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const loading = ref(true)
const list = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const filters = ref({
  search: '',
  date_from: '',
  date_to: '',
})

const showFormModal = ref(false)
const editingItem = ref(null)
const form = ref({
  student_id: '',
  pickup_date: '',
  taken_ijazah: false,
  taken_raport: false,
  taken_skhun: false,
  dokumen_lainnya: '',
  nomor_ijazah: '',
  kode_blangko: '',
  received_by: '',
  notes: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const alumniOptions = ref([])
const alumniSearch = ref('')
const alumniLoading = ref(false)
const alumniDropdownOpen = ref(false)
let alumniSearchTimeout = null

const photoInputRef = ref(null)
const selectedPhoto = ref(null)
const photoPreview = ref('')
const showCameraUI = ref(false)
const cameraVideoRef = ref(null)
let cameraStream = null
const cameraError = ref('')
const cameraLoading = ref(false)

const deleteTarget = ref(null)

const deleteMessage = computed(() => {
  if (!deleteTarget.value) return ''
  const name = deleteTarget.value.student?.name || 'Pengambilan'
  return `Yakin menghapus data pengambilan untuk "${name}"?`
})

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDateTime(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function selectAlumni(a) {
  form.value.student_id = a.id
  alumniSearch.value = `${a.name}${a.nisn ? ` (${a.nisn})` : a.nis ? ` (${a.nis})` : ''} — Lulus ${a.graduation_year || '?'}`
  alumniDropdownOpen.value = false
}

function closeAlumniDropdown() {
  setTimeout(() => { alumniDropdownOpen.value = false }, 200)
}

function formatItemsTaken(item) {
  const parts = []
  if (item.taken_ijazah) parts.push('Ijazah')
  if (item.taken_raport) parts.push('Raport')
  if (item.taken_skhun) parts.push('SKHUN')
  if (item.dokumen_lainnya) parts.push(item.dokumen_lainnya)
  return parts.length ? parts.join(', ') : '—'
}

let searchTimeout = null
function debounceSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadList(), 400)
}

function debounceAlumniSearch() {
  if (alumniSearchTimeout) clearTimeout(alumniSearchTimeout)
  alumniSearchTimeout = setTimeout(() => loadAlumniOptions(), 400)
}

async function loadAlumniOptions() {
  alumniLoading.value = true
  try {
    const res = await alumniApi.getList({
      per_page: 100,
      search: alumniSearch.value?.trim() || undefined,
    })
    alumniOptions.value = res.data?.data ?? []
  } catch (e) {
    alumniOptions.value = []
    toast.error('Gagal memuat daftar alumni', e.formattedMessage || 'Daftar alumni tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    alumniLoading.value = false
  }
}

async function loadList() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      search: filters.value.search || undefined,
      date_from: filters.value.date_from || undefined,
      date_to: filters.value.date_to || undefined,
    }
    const res = await documentPickupApi.list(params)
    list.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat data pengambilan ijazah', e.formattedMessage || 'Data pengambilan ijazah tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadList()
}

function resetFilters() {
  filters.value = { search: '', date_from: '', date_to: '' }
  pagination.value.current_page = 1
  loadList()
}

function openAddModal() {
  editingItem.value = null
  const now = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  form.value = {
    student_id: '',
    pickup_date: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`,
    taken_ijazah: false,
    taken_raport: false,
    taken_skhun: false,
    dokumen_lainnya: '',
    nomor_ijazah: '',
    kode_blangko: '',
    received_by: '',
    notes: '',
  }
  alumniSearch.value = ''
  alumniOptions.value = []
  alumniDropdownOpen.value = false
  showCameraUI.value = false
  stopCameraStream()
  selectedPhoto.value = null
  photoPreview.value = ''
  formError.value = ''
  if (photoInputRef.value) photoInputRef.value.value = ''
  showFormModal.value = true
  loadAlumniOptions()
}

function openEditModal(item) {
  editingItem.value = item
  const pickupDt = item.pickup_date ? item.pickup_date.slice(0, 16) : ''
  form.value = {
    student_id: item.student_id,
    pickup_date: pickupDt || (() => {
      const now = new Date()
      const pad = (n) => String(n).padStart(2, '0')
      return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
    })(),
    taken_ijazah: !!item.taken_ijazah,
    taken_raport: !!item.taken_raport,
    taken_skhun: !!item.taken_skhun,
    dokumen_lainnya: item.dokumen_lainnya || '',
    nomor_ijazah: item.nomor_ijazah || '',
    kode_blangko: item.kode_blangko || '',
    received_by: item.received_by || '',
    notes: item.notes || '',
  }
  alumniOptions.value = item.student ? [item.student] : []
  showCameraUI.value = false
  stopCameraStream()
  selectedPhoto.value = null
  photoPreview.value = ''
  formError.value = ''
  if (photoInputRef.value) photoInputRef.value.value = ''
  showFormModal.value = true
}

function triggerPhotoInput() {
  photoInputRef.value?.click()
}

function stopCameraStream() {
  if (cameraStream) {
    cameraStream.getTracks().forEach((t) => t.stop())
    cameraStream = null
  }
}

async function startCamera() {
  cameraError.value = ''
  cameraLoading.value = true
  showCameraUI.value = true
  try {
    await nextTick()
    const video = cameraVideoRef.value
    if (!video) return
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' },
      audio: false,
    })
    cameraStream = stream
    video.srcObject = stream
  } catch (e) {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false,
      })
      cameraStream = stream
      cameraVideoRef.value.srcObject = stream
    } catch (e2) {
      cameraError.value = 'Tidak bisa mengakses kamera. Izinkan akses kamera di pengaturan browser atau pilih file.'
      console.warn('getUserMedia failed', e2)
    }
  } finally {
    cameraLoading.value = false
  }
}

function closeCamera() {
  stopCameraStream()
  if (cameraVideoRef.value) cameraVideoRef.value.srcObject = null
  showCameraUI.value = false
  cameraError.value = ''
}

function captureFromCamera() {
  const video = cameraVideoRef.value
  if (!video || !video.videoWidth) return
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0)
  canvas.toBlob(
    (blob) => {
      if (!blob) return
      const file = new File([blob], 'foto-serah-terima.jpg', { type: 'image/jpeg' })
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
  if (photoInputRef.value) photoInputRef.value.value = ''
}

watch(showFormModal, (v) => {
  if (!v) {
    closeCamera()
    if (photoPreview.value) {
      URL.revokeObjectURL(photoPreview.value)
      photoPreview.value = ''
      selectedPhoto.value = null
    }
  }
})

async function submitForm() {
  formError.value = ''
  if (!form.value.student_id) {
    formError.value = 'Siswa (alumni) wajib dipilih.'
    return
  }
  if (!form.value.pickup_date) {
    formError.value = 'Tanggal & jam pengambilan wajib diisi.'
    return
  }
  if (!editingItem.value && !selectedPhoto.value) {
    formError.value = 'Foto serah terima ijazah wajib diisi (ambil dari kamera).'
    return
  }

  formSubmitting.value = true
  try {
    const payload = {
      student_id: form.value.student_id,
      pickup_date: form.value.pickup_date.length <= 10 ? `${form.value.pickup_date}T00:00` : form.value.pickup_date,
      taken_ijazah: form.value.taken_ijazah ? 1 : 0,
      taken_raport: form.value.taken_raport ? 1 : 0,
      taken_skhun: form.value.taken_skhun ? 1 : 0,
      dokumen_lainnya: form.value.dokumen_lainnya?.trim() || '',
      nomor_ijazah: form.value.nomor_ijazah?.trim() || '',
      kode_blangko: form.value.kode_blangko?.trim() || '',
      received_by: form.value.received_by?.trim() || '',
      notes: form.value.notes?.trim() || '',
    }
    if (editingItem.value) {
      if (selectedPhoto.value) payload.foto = selectedPhoto.value
      await documentPickupApi.update(editingItem.value.id, payload)
      toast.success('Data pengambilan ijazah berhasil diperbarui')
    } else {
      if (selectedPhoto.value) payload.foto = selectedPhoto.value
      await documentPickupApi.create(payload)
      toast.success('Pengambilan ijazah berhasil dicatat')
    }
    showFormModal.value = false
    loadList()
  } catch (e) {
    formError.value = e.formattedMessage || e.message || 'Gagal menyimpan.'
  } finally {
    formSubmitting.value = false
  }
}

function confirmDelete(item) {
  deleteTarget.value = item
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await documentPickupApi.delete(deleteTarget.value.id)
    toast.success('Data pengambilan ijazah berhasil dihapus')
    deleteTarget.value = null
    loadList()
  } catch (e) {
    toast.error('Gagal menghapus data pengambilan ijazah', e.formattedMessage || 'Data tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadList()
})
</script>

<style scoped>
.document-pickup-page {
  padding: 0 0 2rem;
}

.page-header { margin-bottom: 1.25rem; }

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: var(--color-primary-light, #e8f0fe);
  color: var(--color-primary, #1a73e8);
}

.header-text { flex: 1; min-width: 0; }

.page-title {
  font-size: 1.375rem;
  font-weight: 600;
  margin: 0 0 0.125rem 0;
  color: var(--color-text, #1a1a1a);
}

.page-subtitle {
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
  margin: 0;
}

.btn-header {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #1a73e8);
  color: #fff;
  cursor: pointer;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  background: var(--color-surface, #fff);
  border-radius: 10px;
  border: 1px solid var(--color-border, #e8eaed);
}

.toolbar-left {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.stat-badge {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted, #5f6368);
  padding-right: 0.75rem;
  border-right: 1px solid var(--color-border, #e8eaed);
}

.search-wrap { position: relative; width: 220px; min-width: 0; }

.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted, #9aa0a6);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.625rem 0.5rem 2rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  font-size: 0.875rem;
  background: var(--color-surface, #fff);
}

.filter-dates { display: flex; align-items: center; gap: 0.375rem; }

.filter-date {
  padding: 0.5rem 0.625rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  font-size: 0.8125rem;
  background: var(--color-surface, #fff);
}

.date-sep { font-size: 0.75rem; color: var(--color-text-muted, #9aa0a6); }

.btn-reset {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.btn-reset:hover { background: var(--color-bg-subtle, #f1f3f4); }

.loading-wrap { width: 100%; padding: 2rem; }

.empty-state {
  padding: 3rem 1.5rem;
  text-align: center;
  background: var(--color-surface, #fff);
  border-radius: 10px;
  border: 1px solid var(--color-border, #e8eaed);
}

.empty-icon {
  color: var(--color-text-muted, #bdc1c6);
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.375rem 0;
}

.empty-desc {
  font-size: 0.875rem;
  color: var(--color-text-muted, #5f6368);
  margin: 0 0 1.25rem 0;
}

.btn-empty-cta { margin-top: 0.5rem; padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 500; border-radius: 8px; }

.card.table-card {
  background: var(--color-surface, #fff);
  border-radius: 10px;
  border: 1px solid var(--color-border, #e8eaed);
  overflow: hidden;
}

.table-scroll { overflow-x: auto; }

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th,
.data-table td {
  padding: 0.75rem 0.875rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border, #e8eaed);
}

.data-table th {
  font-weight: 600;
  color: var(--color-text-muted, #5f6368);
  background: var(--color-bg-subtle, #f8f9fa);
}

.th-date { width: 130px; }
.th-student { min-width: 160px; }
.th-docs { min-width: 140px; }
.th-nomor { width: 120px; }
.th-received { width: 120px; }
.th-photo { width: 70px; }

.student-name { display: block; font-weight: 500; }
.student-meta { font-size: 0.75rem; color: var(--color-text-muted, #5f6368); }

.td-date { white-space: nowrap; font-size: 0.8125rem; }
.td-docs { font-size: 0.8125rem; }
.pickup-photo {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid var(--color-border, #e8eaed);
}

.no-photo { color: var(--color-text-muted, #9aa0a6); font-size: 0.8125rem; }

.col-actions { width: 90px; }
.action-buttons { display: flex; gap: 0.375rem; }
.btn-icon {
  padding: 0.375rem;
  border: none;
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  color: var(--color-text-muted, #5f6368);
}
.btn-icon:hover { background: var(--color-bg-subtle, #f1f3f4); color: var(--color-text, #1a1a1a); }
.btn-edit:hover { color: var(--color-primary, #1a73e8); }
.btn-delete:hover { color: var(--color-danger, #d93025); }

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--color-border, #e8eaed);
}

.pagination-info { font-size: 0.8125rem; color: var(--color-text-muted, #5f6368); }
.pagination-buttons { display: flex; gap: 0.5rem; }
.btn-page {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  background: var(--color-surface, #fff);
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: var(--color-surface, #fff);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border, #e8eaed);
}

.modal-title { font-size: 1.125rem; font-weight: 600; margin: 0; }
.modal-close {
  font-size: 1.5rem;
  line-height: 1;
  padding: 0.25rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: var(--color-text-muted, #5f6368);
}

.modal-body {
  padding: 1.25rem;
  overflow-y: auto;
}

.form-section { margin-bottom: 1.25rem; }
.form-section-title {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  color: var(--color-text, #1a1a1a);
}

.required { color: var(--color-danger, #d93025); }

.field { margin-bottom: 0.75rem; }
.field label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: var(--color-text, #1a1a1a);
}

.field input[type="text"],
.field input[type="date"],
.field textarea,
.form-select {
  width: 100%;
  padding: 0.5rem 0.625rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  font-size: 0.875rem;
  background: var(--color-surface, #fff);
}

.form-row { display: flex; gap: 0.75rem; }
.form-row .field { flex: 1; min-width: 0; }

.alumni-combobox-wrap { position: relative; }
.alumni-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin-top: 2px;
  max-height: 220px;
  overflow-y: auto;
  background: var(--color-surface, #fff);
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 10;
}
.alumni-list {
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
}
.alumni-option {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  cursor: pointer;
}
.alumni-option:hover {
  background: var(--color-bg-subtle, #f1f3f4);
}
.alumni-list-empty {
  padding: 0.75rem;
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
}

.checkbox-group { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; }
.checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
}

.photo-block { margin-bottom: 0.5rem; }
.photo-input-hidden {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}
.label-as-button {
  cursor: pointer;
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.photo-buttons {
  margin-bottom: 0.5rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}
.photo-or {
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
}
.btn-camera {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 8px;
  background: var(--color-surface, #fff);
  cursor: pointer;
}
.btn-camera-secondary {
  border-color: var(--color-primary, #1a73e8);
  color: var(--color-primary, #1a73e8);
}
.camera-preview-wrap {
  position: relative;
  margin-bottom: 0.75rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 8px;
  overflow: hidden;
  background: #000;
}
.camera-video {
  display: block;
  width: 100%;
  max-height: 280px;
  object-fit: contain;
}
.camera-loading-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  margin: 0;
  color: #fff;
  font-size: 0.875rem;
  text-shadow: 0 1px 2px rgba(0,0,0,0.8);
}
.camera-error-inline { margin: 0.5rem 0.75rem; }
.camera-actions {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--color-surface, #fff);
  border-top: 1px solid var(--color-border, #e8eaed);
}
.photo-preview-box {
  margin-bottom: 0.5rem;
}
.photo-preview-box img {
  max-width: 100%;
  max-height: 180px;
  object-fit: contain;
  border-radius: 8px;
  border: 1px solid var(--color-border, #e8eaed);
}
.btn-link { background: none; border: none; color: var(--color-primary, #1a73e8); cursor: pointer; font-size: 0.8125rem; }
.btn-small { font-size: 0.75rem; }
.form-hint { font-size: 0.75rem; color: var(--color-text-muted, #5f6368); margin: 0.25rem 0 0 0; }
.form-error { font-size: 0.8125rem; color: var(--color-danger, #d93025); margin-bottom: 0.75rem; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border, #e8eaed);
}

.btn-ghost {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
}

.btn-primary {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: none;
  border-radius: 6px;
  background: var(--color-primary, #1a73e8);
  color: #fff;
  cursor: pointer;
}
</style>
