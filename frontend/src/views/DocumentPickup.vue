<template>
  <Layout>
    <div class="document-pickup-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">Pengambilan Ijazah</h1>
            <p class="page-subtitle">Catat serah terima dokumen alumni (ijazah, raport, SKHUN, dan lainnya)</p>
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
              placeholder="Cari nama, NIS, NISN..."
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
          <button type="button" @click="resetFilters" class="btn-reset">Reset</button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="7" :cell-widths="['110px', '1fr', '160px', '120px', '110px', '64px', '90px']" />
      </div>

      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 18V12M9 15L12 12L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada pencatatan</h3>
        <p class="empty-desc">Catat pengambilan dokumen saat alumni datang mengambil ijazah, raport, atau SKHUN.</p>
        <button type="button" @click="openAddModal" class="btn-primary btn-empty-cta">Catat Pengambilan</button>
      </div>

      <template v-else>
        <!-- Desktop table -->
        <div class="card table-card table-desktop">
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="th-date">Tanggal</th>
                  <th class="th-student">Alumni</th>
                  <th class="th-docs">Dokumen</th>
                  <th class="th-nomor">No. Ijazah</th>
                  <th class="th-received">Petugas</th>
                  <th class="th-photo">Foto</th>
                  <th class="col-actions">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in list" :key="item.id">
                  <td class="td-date">
                    <span class="date-primary">{{ formatDate(item.pickup_date) }}</span>
                    <span class="date-time">{{ formatTime(item.pickup_date) }}</span>
                  </td>
                  <td class="td-student">
                    <span class="student-name">{{ item.student?.name || '—' }}</span>
                    <span class="student-meta">
                      <template v-if="item.student?.nisn">NISN {{ item.student.nisn }}</template>
                      <template v-else-if="item.student?.nis">NIS {{ item.student.nis }}</template>
                      <template v-if="item.student?.graduation_year"> · Lulus {{ item.student.graduation_year }}</template>
                    </span>
                  </td>
                  <td class="td-docs">
                    <div class="doc-chips">
                      <span v-for="chip in getDocChips(item)" :key="chip" class="doc-chip">{{ chip }}</span>
                      <span v-if="!getDocChips(item).length" class="doc-empty">—</span>
                    </div>
                  </td>
                  <td class="td-nomor">
                    <span v-if="item.nomor_ijazah" class="nomor-main">{{ item.nomor_ijazah }}</span>
                    <span v-if="item.kode_blangko" class="nomor-sub">{{ item.kode_blangko }}</span>
                    <span v-if="!item.nomor_ijazah && !item.kode_blangko" class="doc-empty">—</span>
                  </td>
                  <td class="td-received">{{ item.received_by || '—' }}</td>
                  <td class="td-photo">
                    <button
                      v-if="item.photo_url"
                      type="button"
                      class="photo-thumb-btn"
                      @click="openPhotoPreview(item.photo_url)"
                      title="Lihat foto"
                    >
                      <img :src="item.photo_url" alt="Foto serah terima" class="pickup-photo" />
                    </button>
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
            <span class="pagination-info">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}–{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}</span>
            <div class="pagination-buttons">
              <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
              <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
            </div>
          </div>
        </div>

        <!-- Mobile cards -->
        <div class="pickup-cards table-mobile">
          <article v-for="item in list" :key="'m-' + item.id" class="pickup-card">
            <div class="pickup-card-top">
              <div>
                <h3 class="pickup-card-name">{{ item.student?.name || '—' }}</h3>
                <p class="pickup-card-meta">
                  {{ formatDateTime(item.pickup_date) }}
                  <template v-if="item.student?.graduation_year"> · Lulus {{ item.student.graduation_year }}</template>
                </p>
              </div>
              <button
                v-if="item.photo_url"
                type="button"
                class="photo-thumb-btn"
                @click="openPhotoPreview(item.photo_url)"
              >
                <img :src="item.photo_url" alt="" class="pickup-photo pickup-photo-lg" />
              </button>
            </div>
            <div class="doc-chips">
              <span v-for="chip in getDocChips(item)" :key="chip" class="doc-chip">{{ chip }}</span>
            </div>
            <div v-if="item.nomor_ijazah || item.received_by" class="pickup-card-footer">
              <span v-if="item.nomor_ijazah">No. {{ item.nomor_ijazah }}</span>
              <span v-if="item.received_by">Petugas: {{ item.received_by }}</span>
            </div>
            <div class="pickup-card-actions">
              <TableAction kind="edit" @click="openEditModal(item)" />
              <TableAction kind="delete" @click="confirmDelete(item)" />
            </div>
          </article>
          <div v-if="pagination.last_page > 1" class="pagination pagination-mobile">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="pagination-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Modal: Catat / Edit -->
      <div v-if="showFormModal" class="modal-overlay" @click.self="closeFormModal">
        <div class="modal form-modal">
          <header class="modal-header">
            <div>
              <h2 class="modal-title">{{ editingItem ? 'Edit Pengambilan' : 'Catat Pengambilan' }}</h2>
              <p class="modal-subtitle">{{ editingItem ? 'Perbarui data serah terima dokumen' : 'Isi data saat alumni mengambil dokumen' }}</p>
            </div>
            <button type="button" @click="closeFormModal" class="modal-close" aria-label="Tutup">×</button>
          </header>
          <form @submit.prevent="submitForm" class="modal-body">
            <section class="form-section">
              <h3 class="form-section-title">Alumni <span class="required">*</span></h3>
              <div v-if="editingItem" class="selected-alumni">
                <div class="selected-alumni-info">
                  <strong>{{ editingItem.student?.name }}</strong>
                  <span>
                    <template v-if="editingItem.student?.nisn">NISN {{ editingItem.student.nisn }}</template>
                    <template v-if="editingItem.student?.graduation_year"> · Lulus {{ editingItem.student.graduation_year }}</template>
                  </span>
                </div>
                <p class="form-hint">Alumni tidak dapat diubah saat edit.</p>
              </div>
              <div v-else class="field alumni-combobox-wrap">
                <label>Cari & pilih alumni</label>
                <div v-if="selectedAlumni" class="selected-alumni">
                  <div class="selected-alumni-info">
                    <strong>{{ selectedAlumni.name }}</strong>
                    <span>
                      <template v-if="selectedAlumni.nisn">NISN {{ selectedAlumni.nisn }}</template>
                      <template v-else-if="selectedAlumni.nis">NIS {{ selectedAlumni.nis }}</template>
                      · Lulus {{ selectedAlumni.graduation_year || '?' }}
                    </span>
                  </div>
                  <button type="button" class="btn-clear-alumni" @click="clearAlumni">Ganti</button>
                </div>
                <template v-else>
                  <input
                    v-model="alumniSearch"
                    type="text"
                    placeholder="Ketik nama, NIS, atau NISN..."
                    class="form-select"
                    autocomplete="off"
                    @focus="alumniDropdownOpen = true"
                    @blur="closeAlumniDropdown"
                    @input="debounceAlumniSearch"
                  />
                  <div v-if="alumniDropdownOpen" class="alumni-dropdown" @mousedown.prevent>
                    <p v-if="alumniLoading" class="alumni-dropdown-hint">Memuat daftar alumni...</p>
                    <ul v-else-if="alumniOptions.length === 0" class="alumni-list">
                      <li class="alumni-list-empty">Tidak ditemukan. Pastikan siswa berstatus Lulus.</li>
                    </ul>
                    <ul v-else class="alumni-list">
                      <li
                        v-for="a in alumniOptions"
                        :key="a.id"
                        class="alumni-option"
                        @mousedown="selectAlumni(a)"
                      >
                        <strong>{{ a.name }}</strong>
                        <span>{{ a.nisn || a.nis || '—' }} · Lulus {{ a.graduation_year || '?' }}</span>
                      </li>
                    </ul>
                  </div>
                </template>
              </div>
            </section>

            <section class="form-section">
              <h3 class="form-section-title">Waktu & dokumen</h3>
              <div class="field">
                <label>Tanggal & jam pengambilan <span class="required">*</span></label>
                <input v-model="form.pickup_date" type="datetime-local" required class="form-select" />
              </div>
              <div class="field">
                <label>Dokumen yang diambil</label>
                <div class="doc-toggle-group">
                  <label class="doc-toggle" :class="{ active: form.taken_ijazah }">
                    <input v-model="form.taken_ijazah" type="checkbox" />
                    <span>Ijazah</span>
                  </label>
                  <label class="doc-toggle" :class="{ active: form.taken_raport }">
                    <input v-model="form.taken_raport" type="checkbox" />
                    <span>Raport</span>
                  </label>
                  <label class="doc-toggle" :class="{ active: form.taken_skhun }">
                    <input v-model="form.taken_skhun" type="checkbox" />
                    <span>SKHUN</span>
                  </label>
                </div>
              </div>
              <div class="field">
                <label>Dokumen lainnya</label>
                <textarea v-model="form.dokumen_lainnya" rows="2" maxlength="1000" placeholder="Contoh: Surat Keterangan, Transkrip, Piagam" class="form-select"></textarea>
              </div>
              <div class="form-row">
                <div class="field">
                  <label>Nomor Ijazah</label>
                  <input v-model="form.nomor_ijazah" type="text" maxlength="64" placeholder="Opsional" class="form-select" />
                </div>
                <div class="field">
                  <label>Kode Blangko</label>
                  <input v-model="form.kode_blangko" type="text" maxlength="64" placeholder="Opsional" class="form-select" />
                </div>
              </div>
            </section>

            <section class="form-section">
              <h3 class="form-section-title">
                Foto serah terima
                <span v-if="!editingItem" class="required">*</span>
              </h3>
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
                      <button type="button" class="btn-primary" :disabled="cameraLoading" @click="captureFromCamera">Ambil foto</button>
                      <button type="button" class="btn-ghost" @click="closeCamera">Batal</button>
                    </div>
                  </div>
                </template>
                <template v-else-if="!photoPreview && !editingItem?.photo_url">
                  <div class="photo-dropzone">
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
                <p class="form-hint">{{ editingItem ? 'Opsional. Ganti jika ingin memperbarui foto.' : 'Wajib. Ambil dari kamera atau unggah file (maks. 5MB).' }}</p>
              </div>
            </section>

            <section class="form-section">
              <h3 class="form-section-title">Petugas & catatan</h3>
              <div class="field">
                <label>Diterima oleh (petugas)</label>
                <input v-model="form.received_by" type="text" maxlength="255" placeholder="Nama petugas yang menyerahkan" class="form-select" />
              </div>
              <div class="field">
                <label>Catatan</label>
                <textarea v-model="form.notes" rows="2" placeholder="Opsional" class="form-select"></textarea>
              </div>
            </section>

            <div v-if="formError" class="form-error">{{ formError }}</div>
            <footer class="modal-footer">
              <button type="button" @click="closeFormModal" class="btn-ghost">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingItem ? 'Simpan perubahan' : 'Catat pengambilan') }}
              </button>
            </footer>
          </form>
        </div>
      </div>

      <!-- Photo lightbox -->
      <div v-if="photoLightboxUrl" class="lightbox-overlay" @click.self="photoLightboxUrl = ''">
        <button type="button" class="lightbox-close" @click="photoLightboxUrl = ''" aria-label="Tutup">×</button>
        <img :src="photoLightboxUrl" alt="Foto serah terima" class="lightbox-img" />
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
import TableAction from '@/components/TableAction.vue'
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
const selectedAlumni = ref(null)
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
const photoLightboxUrl = ref('')

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

function formatTime(val) {
  if (!val) return ''
  const d = new Date(val)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function getDocChips(item) {
  const parts = []
  if (item.taken_ijazah) parts.push('Ijazah')
  if (item.taken_raport) parts.push('Raport')
  if (item.taken_skhun) parts.push('SKHUN')
  if (item.dokumen_lainnya) {
    item.dokumen_lainnya.split(/[,;]+/).map((s) => s.trim()).filter(Boolean).forEach((s) => parts.push(s))
  }
  return parts
}

function selectAlumni(a) {
  form.value.student_id = a.id
  selectedAlumni.value = a
  alumniSearch.value = ''
  alumniDropdownOpen.value = false
}

function clearAlumni() {
  form.value.student_id = ''
  selectedAlumni.value = null
  alumniSearch.value = ''
  loadAlumniOptions()
}

function closeAlumniDropdown() {
  setTimeout(() => { alumniDropdownOpen.value = false }, 200)
}

function openPhotoPreview(url) {
  photoLightboxUrl.value = url
}

let searchTimeout = null
function debounceSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    loadList()
  }, 400)
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
    toast.error('Gagal memuat daftar alumni', e.formattedMessage || 'Daftar alumni tidak dapat dimuat.')
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
    toast.error('Gagal memuat data pengambilan ijazah', e.formattedMessage || 'Data tidak dapat dimuat.')
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

function nowLocalDatetime() {
  const now = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
}

function openAddModal() {
  editingItem.value = null
  selectedAlumni.value = null
  form.value = {
    student_id: '',
    pickup_date: nowLocalDatetime(),
    taken_ijazah: true,
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
  selectedAlumni.value = item.student || null
  const pickupDt = item.pickup_date ? item.pickup_date.slice(0, 16) : ''
  form.value = {
    student_id: item.student_id,
    pickup_date: pickupDt || nowLocalDatetime(),
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

function closeFormModal() {
  showFormModal.value = false
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
      if (cameraVideoRef.value) cameraVideoRef.value.srcObject = stream
    } catch (e2) {
      cameraError.value = 'Tidak bisa mengakses kamera. Izinkan akses kamera atau pilih file.'
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
    formError.value = 'Alumni wajib dipilih.'
    return
  }
  if (!form.value.pickup_date) {
    formError.value = 'Tanggal & jam pengambilan wajib diisi.'
    return
  }
  if (!editingItem.value && !selectedPhoto.value) {
    formError.value = 'Foto serah terima wajib diisi (kamera atau file).'
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
    if (selectedPhoto.value) payload.foto = selectedPhoto.value

    if (editingItem.value) {
      await documentPickupApi.update(editingItem.value.id, payload)
      toast.success('Data pengambilan ijazah berhasil diperbarui')
    } else {
      await documentPickupApi.create(payload)
      toast.success('Pengambilan ijazah berhasil dicatat')
    }
    showFormModal.value = false
    loadList()
  } catch (e) {
    formError.value = e.formattedMessage || e.response?.data?.message || e.message || 'Gagal menyimpan.'
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
    toast.error('Gagal menghapus data', e.formattedMessage || 'Data tidak dapat dihapus.')
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
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-text { flex: 1; min-width: 0; }

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
  color: var(--color-text, #111827);
  letter-spacing: -0.02em;
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--color-text-muted, #6b7280);
  margin: 0;
  line-height: 1.4;
}

.btn-header {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.125rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 10px;
  border: none;
  background: var(--color-primary, #059669);
  color: #fff;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
}

.btn-header:hover { filter: brightness(1.05); }

.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.875rem 1rem;
  background: var(--color-surface, #fff);
  border-radius: 12px;
  border: 1px solid var(--color-border, #e5e7eb);
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
  font-weight: 600;
  color: var(--color-primary, #059669);
  background: rgba(5, 150, 105, 0.08);
  padding: 0.375rem 0.75rem;
  border-radius: 999px;
  white-space: nowrap;
}

.search-wrap { position: relative; width: 240px; min-width: 0; max-width: 100%; }

.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted, #9ca3af);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.625rem 0.5rem 2rem;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 8px;
  font-size: 0.875rem;
  background: var(--color-surface, #fff);
}

.filter-dates { display: flex; align-items: center; gap: 0.375rem; }

.filter-date {
  padding: 0.5rem 0.625rem;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 8px;
  font-size: 0.8125rem;
  background: var(--color-surface, #fff);
}

.date-sep { font-size: 0.75rem; color: var(--color-text-muted, #9ca3af); }

.btn-reset {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted, #6b7280);
  background: transparent;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 8px;
  cursor: pointer;
}

.btn-reset:hover { background: var(--color-bg-subtle, #f3f4f6); }

.loading-wrap { width: 100%; padding: 1rem 0; }

.empty-state {
  padding: 3.5rem 1.5rem;
  text-align: center;
  background: var(--color-surface, #fff);
  border-radius: 12px;
  border: 1px dashed var(--color-border, #d1d5db);
}

.empty-icon {
  color: var(--color-text-muted, #d1d5db);
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.375rem 0;
}

.empty-desc {
  font-size: 0.875rem;
  color: var(--color-text-muted, #6b7280);
  margin: 0 0 1.25rem 0;
  max-width: 360px;
  margin-left: auto;
  margin-right: auto;
}

.btn-empty-cta {
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 10px;
}

.card.table-card {
  background: var(--color-surface, #fff);
  border-radius: 12px;
  border: 1px solid var(--color-border, #e5e7eb);
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
  padding: 0.875rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border, #f3f4f6);
  vertical-align: top;
}

.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover { background: rgba(5, 150, 105, 0.03); }

.data-table th {
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-text-muted, #6b7280);
  background: var(--color-bg-subtle, #f9fafb);
}

.th-date { width: 120px; }
.th-student { min-width: 180px; }
.th-docs { min-width: 160px; }
.th-nomor { width: 140px; }
.th-received { width: 120px; }
.th-photo { width: 72px; }

.date-primary { display: block; font-weight: 500; color: var(--color-text, #111827); }
.date-time { display: block; font-size: 0.75rem; color: var(--color-text-muted, #6b7280); margin-top: 0.125rem; }

.student-name { display: block; font-weight: 600; color: var(--color-text, #111827); }
.student-meta { display: block; font-size: 0.75rem; color: var(--color-text-muted, #6b7280); margin-top: 0.2rem; }

.doc-chips { display: flex; flex-wrap: wrap; gap: 0.375rem; }
.doc-chip {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 999px;
  background: rgba(5, 150, 105, 0.1);
  color: #047857;
}
.doc-empty { color: var(--color-text-muted, #9ca3af); font-size: 0.8125rem; }

.nomor-main { display: block; font-weight: 500; font-size: 0.8125rem; }
.nomor-sub { display: block; font-size: 0.75rem; color: var(--color-text-muted, #6b7280); margin-top: 0.125rem; }

.pickup-photo {
  width: 44px;
  height: 44px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--color-border, #e5e7eb);
  display: block;
}
.pickup-photo-lg { width: 56px; height: 56px; }
.photo-thumb-btn {
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 8px;
}
.photo-thumb-btn:hover .pickup-photo { outline: 2px solid var(--color-primary, #059669); outline-offset: 1px; }
.no-photo { color: var(--color-text-muted, #9ca3af); font-size: 0.8125rem; }

.col-actions { width: 90px; }
.action-buttons { display: flex; gap: 0.25rem; }
.btn-icon {
  padding: 0.4rem;
  border: none;
  border-radius: 8px;
  background: transparent;
  cursor: pointer;
  color: var(--color-text-muted, #6b7280);
}
.btn-icon:hover { background: var(--color-bg-subtle, #f3f4f6); color: var(--color-text, #111827); }
.btn-edit:hover { color: var(--color-primary, #059669); }
.btn-delete:hover { color: #dc2626; }

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  border-top: 1px solid var(--color-border, #e5e7eb);
}
.pagination-mobile {
  border-top: none;
  justify-content: center;
  padding: 0.5rem 0 0;
}
.pagination-info { font-size: 0.8125rem; color: var(--color-text-muted, #6b7280); }
.pagination-buttons { display: flex; gap: 0.5rem; }
.btn-page {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 8px;
  background: var(--color-surface, #fff);
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-page:not(:disabled):hover { background: var(--color-bg-subtle, #f3f4f6); }

/* Mobile cards */
.table-mobile { display: none; }
.pickup-cards { display: none; flex-direction: column; gap: 0.75rem; }
.pickup-card {
  background: var(--color-surface, #fff);
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 12px;
  padding: 1rem;
}
.pickup-card-top {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.pickup-card-name {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  font-weight: 600;
}
.pickup-card-meta {
  margin: 0;
  font-size: 0.75rem;
  color: var(--color-text-muted, #6b7280);
}
.pickup-card-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
  margin-top: 0.75rem;
  font-size: 0.75rem;
  color: var(--color-text-muted, #6b7280);
}
.pickup-card-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.875rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--color-border, #f3f4f6);
}
.btn-card-action {
  flex: 1;
  padding: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 8px;
  border: 1px solid var(--color-border, #d1d5db);
  background: var(--color-surface, #fff);
  cursor: pointer;
}
.btn-card-danger { color: #dc2626; border-color: #fecaca; }

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-header {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .table-desktop { display: none; }
  .pickup-cards { display: flex; }
  .search-wrap { width: 100%; }
  .stat-badge { border: none; }
  .form-row { flex-direction: column; }
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal {
  background: var(--color-surface, #fff);
  border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
  max-width: 560px;
  width: 100%;
  max-height: 92vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border, #e5e7eb);
  background: linear-gradient(180deg, #f0fdf4 0%, #fff 100%);
}

.modal-title { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.2rem; color: #111827; }
.modal-subtitle { margin: 0; font-size: 0.8125rem; color: #6b7280; }
.modal-close {
  font-size: 1.5rem;
  line-height: 1;
  padding: 0.25rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  flex-shrink: 0;
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
}

.form-section { margin-bottom: 1.5rem; }
.form-section-title {
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.75rem 0;
  color: #374151;
}

.required { color: #dc2626; }

.field { margin-bottom: 0.875rem; }
.field label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  margin-bottom: 0.35rem;
  color: #374151;
}

.form-select,
.field input[type="text"],
.field input[type="datetime-local"],
.field textarea {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 8px;
  font-size: 0.875rem;
  background: var(--color-surface, #fff);
  box-sizing: border-box;
}
.form-select:focus,
.field input:focus,
.field textarea:focus {
  outline: none;
  border-color: var(--color-primary, #059669);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.form-row { display: flex; gap: 0.75rem; }
.form-row .field { flex: 1; min-width: 0; }

.selected-alumni {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(5, 150, 105, 0.06);
  border: 1px solid rgba(5, 150, 105, 0.2);
  border-radius: 10px;
}
.selected-alumni-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}
.selected-alumni-info strong { font-size: 0.9375rem; }
.selected-alumni-info span { font-size: 0.75rem; color: #6b7280; }
.btn-clear-alumni {
  flex-shrink: 0;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
}

.alumni-combobox-wrap { position: relative; }
.alumni-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin-top: 4px;
  max-height: 240px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 20;
}
.alumni-dropdown-hint {
  padding: 0.875rem 1rem;
  margin: 0;
  font-size: 0.8125rem;
  color: #6b7280;
}
.alumni-list {
  list-style: none;
  margin: 0;
  padding: 0.35rem 0;
}
.alumni-option {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  padding: 0.625rem 1rem;
  cursor: pointer;
}
.alumni-option strong { font-size: 0.875rem; }
.alumni-option span { font-size: 0.75rem; color: #6b7280; }
.alumni-option:hover { background: rgba(5, 150, 105, 0.08); }
.alumni-list-empty {
  padding: 0.875rem 1rem;
  font-size: 0.8125rem;
  color: #6b7280;
}

.doc-toggle-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.doc-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.875rem;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  background: #fff;
  transition: all 0.15s ease;
}
.doc-toggle input { accent-color: var(--color-primary, #059669); }
.doc-toggle.active {
  background: rgba(5, 150, 105, 0.1);
  border-color: rgba(5, 150, 105, 0.45);
  color: #047857;
}

.photo-block { margin-bottom: 0.25rem; }
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
.photo-dropzone {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.5rem 1rem;
  border: 1.5px dashed var(--color-border, #d1d5db);
  border-radius: 12px;
  background: #f9fafb;
  margin-bottom: 0.5rem;
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
  color: var(--color-text-muted, #6b7280);
}
.btn-camera {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
}
.btn-camera-secondary {
  border-color: var(--color-primary, #059669);
  color: var(--color-primary, #059669);
}
.camera-preview-wrap {
  position: relative;
  margin-bottom: 0.75rem;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 12px;
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
}
.camera-error-inline { margin: 0.5rem 0.75rem; }
.camera-actions {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #fff;
  border-top: 1px solid var(--color-border, #e5e7eb);
}
.photo-preview-box { margin-bottom: 0.5rem; }
.photo-preview-box img {
  max-width: 100%;
  max-height: 200px;
  object-fit: contain;
  border-radius: 10px;
  border: 1px solid var(--color-border, #e5e7eb);
}
.btn-link { background: none; border: none; color: var(--color-primary, #059669); cursor: pointer; font-size: 0.8125rem; font-weight: 500; }
.btn-small { font-size: 0.75rem; }
.form-hint { font-size: 0.75rem; color: var(--color-text-muted, #6b7280); margin: 0.35rem 0 0 0; }
.form-error {
  font-size: 0.8125rem;
  color: #dc2626;
  margin-bottom: 0.75rem;
  padding: 0.625rem 0.875rem;
  background: #fef2f2;
  border-radius: 8px;
  border: 1px solid #fecaca;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border, #e5e7eb);
  position: sticky;
  bottom: 0;
  background: #fff;
}

.btn-ghost {
  padding: 0.55rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: 8px;
  background: transparent;
  cursor: pointer;
}

.btn-primary {
  padding: 0.55rem 1.125rem;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  background: var(--color-primary, #059669);
  color: #fff;
  cursor: pointer;
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

/* Lightbox */
.lightbox-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.lightbox-close {
  position: absolute;
  top: 1rem;
  right: 1.25rem;
  font-size: 2rem;
  color: #fff;
  background: transparent;
  border: none;
  cursor: pointer;
  line-height: 1;
}
.lightbox-img {
  max-width: min(90vw, 720px);
  max-height: 85vh;
  object-fit: contain;
  border-radius: 8px;
}
</style>
