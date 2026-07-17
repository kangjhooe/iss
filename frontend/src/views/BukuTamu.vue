<template>
  <Layout>
    <div class="buku-tamu-page">
      <div class="toolbar">
        <div class="toolbar-left">
          <span class="stat-badge">{{ pagination.total }} kunjungan</span>
          <div class="search-wrap">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Cari nama, instansi, tujuan..."
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
          <button type="button" @click="openAddModal" class="btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Catat Tamu
          </button>
          <button type="button" @click="exportPdf" :disabled="exportingPdf" class="btn-export-pdf">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 18V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 15L12 12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ exportingPdf ? 'Membuka...' : 'Cetak PDF' }}
          </button>
          <button type="button" @click="resetFilters" class="btn-reset">Reset filter</button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="6" :cell-widths="['80px', '1fr', '120px', '1fr', '120px', '120px']" />
      </div>

      <div v-else-if="list.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada data tamu</h3>
        <p class="empty-desc">Klik "Catat Tamu" untuk mencatat kunjungan pertama.</p>
        <button type="button" @click="openAddModal" class="btn-primary btn-empty-cta">Catat Tamu</button>
      </div>

      <div v-else class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th class="th-photo">Foto</th>
                <th class="th-guest">Nama / Instansi</th>
                <th class="th-purpose">Tujuan</th>
                <th class="th-met">Ditemui</th>
                <th class="th-time">Masuk</th>
                <th class="th-time">Keluar</th>
                <th class="col-actions">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in list" :key="item.id">
                <td class="td-photo">
                  <img v-if="item.foto_url" :src="item.foto_url" :alt="item.nama_tamu" class="guest-photo" />
                  <span v-else class="no-photo">—</span>
                </td>
                <td class="td-guest">
                  <span class="guest-name">{{ item.nama_tamu }}</span>
                  <span v-if="item.instansi_asal" class="guest-org">{{ item.instansi_asal }}</span>
                </td>
                <td class="td-purpose">{{ item.tujuan_kunjungan || '—' }}</td>
                <td class="td-met">{{ item.orang_ditemui || '—' }}</td>
                <td class="td-time">{{ formatDateTime(item.waktu_masuk) }}</td>
                <td class="td-time">
                  <span v-if="item.waktu_keluar">{{ formatDateTime(item.waktu_keluar) }}</span>
                  <button v-else type="button" @click="doCheckout(item)" class="btn-checkout">Catat keluar</button>
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

      <!-- Modal: Catat / Edit Tamu -->
      <div v-if="showFormModal" class="modal-overlay" @click.self="showFormModal = false">
        <div class="modal form-modal">
          <header class="modal-header">
            <h2 class="modal-title">{{ editingItem ? 'Edit Tamu' : 'Catat Tamu Baru' }}</h2>
            <button type="button" @click="showFormModal = false" class="modal-close" aria-label="Tutup">×</button>
          </header>
          <form @submit.prevent="submitForm" class="modal-body">
            <section class="form-section">
              <h3 class="form-section-title">Foto tamu <span class="required">*</span></h3>
              <div class="photo-block">
                <input ref="photoInputRef" type="file" accept="image/*" capture="user" class="photo-input-hidden" @change="onPhotoSelect" />
                <template v-if="!cameraActive && !photoPreview && !editingItem?.foto_url">
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
                    <img v-else-if="editingItem?.foto_url" :src="editingItem.foto_url" alt="Foto saat ini" />
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
                  <input v-model="form.nama_tamu" type="text" required maxlength="255" placeholder="Nama lengkap" />
                </div>
                <div class="field">
                  <label>No. identitas</label>
                  <input v-model="form.no_identitas" type="text" maxlength="64" placeholder="NIK / KTP" />
                </div>
              </div>
              <div class="field">
                <label>Instansi / asal</label>
                <input v-model="form.instansi_asal" type="text" maxlength="255" placeholder="Contoh: Dinas Pendidikan" />
              </div>
              <div class="field">
                <label>No. telepon</label>
                <input v-model="form.no_telepon" type="text" maxlength="32" placeholder="08xxxxxxxxxx" />
                <p v-if="form.no_telepon && !isValidPhone(form.no_telepon)" class="field-hint field-hint-error">Format nomor tidak valid (angka, +, spasi)</p>
              </div>
              <div class="field">
                <label>Tujuan kunjungan <span class="required">*</span></label>
                <select v-model="form.tujuan_kunjungan" required>
                  <option value="">— Pilih tujuan —</option>
                  <option v-for="opt in TUJUAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <input v-if="form.tujuan_kunjungan === 'Lainnya'" v-model="form.tujuan_kunjungan_lain" type="text" maxlength="255" placeholder="Tulis tujuan kunjungan" class="field-mt" />
              </div>
              <div class="field">
                <label>Ditemui / penerima</label>
                <input v-model="form.orang_ditemui" type="text" maxlength="255" placeholder="Nama staf yang ditemui" />
              </div>
              <div class="form-row">
                <div class="field">
                  <label>Waktu masuk</label>
                  <input v-model="form.waktu_masuk" type="datetime-local" />
                  <p class="form-hint">Kosongkan = sekarang (saat simpan)</p>
                </div>
                <div v-if="editingItem" class="field">
                  <label>Waktu keluar</label>
                  <input v-model="form.waktu_keluar" type="datetime-local" />
                  <p class="form-hint">Opsional; bisa juga pakai tombol "Catat keluar" di tabel</p>
                </div>
              </div>
              <div class="field">
                <label>Catatan</label>
                <textarea v-model="form.catatan" rows="2" placeholder="Opsional"></textarea>
              </div>
            </section>
            <div v-if="formError" class="form-error">{{ formError }}</div>
            <footer class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-ghost">Batal</button>
              <button type="submit" :disabled="formSubmitting" class="btn-primary">
                {{ formSubmitting ? 'Menyimpan...' : (editingItem ? 'Simpan' : 'Catat tamu') }}
              </button>
            </footer>
          </form>
        </div>
      </div>

      <ConfirmDialog
        v-if="deleteTarget"
        :show="!!deleteTarget"
        title="Hapus Data Tamu"
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
import guestVisitApi from '@/api/guestVisit'
import { institutionApi } from '@/api/institution'
import { getPrincipalTitle } from '@/utils/institution'
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
const photoInputRef = ref(null)
const selectedPhoto = ref(null)
const photoPreview = ref('')
const cameraActive = ref(false)
const videoRef = ref(null)
const cameraStreamRef = ref(null)
const cameraError = ref('')

const TUJUAN_OPTIONS = ['Rapat', 'Urusan siswa', 'Dinas', 'Kunjungan', 'Lainnya']

const form = ref({
  nama_tamu: '',
  no_identitas: '',
  instansi_asal: '',
  no_telepon: '',
  tujuan_kunjungan: '',
  tujuan_kunjungan_lain: '',
  orang_ditemui: '',
  waktu_masuk: '',
  waktu_keluar: '',
  catatan: '',
})
const formSubmitting = ref(false)
const formError = ref('')

const deleteTarget = ref(null)
const exportingPdf = ref(false)

const deleteMessage = computed(() => {
  if (!deleteTarget.value) return ''
  return `Yakin menghapus data tamu "${deleteTarget.value.nama_tamu}"?`
})

let searchTimeout = null
function debounceSearch() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadList(), 400)
}

function formatDateTime(val) {
  if (!val) return '—'
  const d = new Date(val)
  return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
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
    const res = await guestVisitApi.list(params)
    list.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat buku tamu', e.formattedMessage)
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

function fullPhotoUrl(url) {
  if (!url) return ''
  if (url.startsWith('http://') || url.startsWith('https://')) return url
  const origin = window.location.origin
  return url.startsWith('/') ? origin + url : origin + '/' + url
}

function toDateTimeLocal(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  return `${y}-${m}-${day}T${h}:${min}`
}

function isValidPhone(val) {
  if (!val || !String(val).trim()) return true
  return /^[\d\s+()-]{10,32}$/.test(String(val).trim())
}

function formatDatePrint(dateStr) {
  if (!dateStr) return '–'
  const d = new Date(dateStr)
  return d.toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function exportPdf() {
  exportingPdf.value = true
  try {
    const [instRes, exportRes] = await Promise.all([
      institutionApi.getMy(),
      guestVisitApi.exportData({
        search: filters.value.search || undefined,
        date_from: filters.value.date_from || undefined,
        date_to: filters.value.date_to || undefined,
      }),
    ])
    const institution = instRes.data?.data || instRes.data || {}
    const visits = exportRes.data?.data || []

    const addressParts = []
    if (institution.address) addressParts.push(institution.address)
    if (institution.village) addressParts.push(institution.village)
    if (institution.sub_district) addressParts.push(`Kec. ${institution.sub_district}`)
    if (institution.district) addressParts.push(institution.district)
    if (institution.province) addressParts.push(institution.province)
    if (institution.postal_code) addressParts.push(institution.postal_code)
    const fullAddress = addressParts.join(', ') || '–'

    const periodText =
      filters.value.date_from || filters.value.date_to
        ? `Periode: ${filters.value.date_from ? formatDatePrint(filters.value.date_from).split(' ')[0] : '...'} s/d ${filters.value.date_to ? formatDatePrint(filters.value.date_to).split(' ')[0] : '...'}`
        : ''

    const rows =
      visits.length === 0
        ? '<tr><td colspan="9" style="text-align:center;padding:12px;">Tidak ada data kunjungan.</td></tr>'
        : visits
            .map(
              (v, i) => `
            <tr>
              <td class="num">${i + 1}</td>
              <td class="td-photo">${v.foto_url ? `<img src="${fullPhotoUrl(v.foto_url)}" alt="" class="photo-thumb" onerror="this.style.display='none'" />` : '–'}</td>
              <td>${escapeHtml(v.nama_tamu)}</td>
              <td>${escapeHtml(v.no_identitas || '–')}</td>
              <td>${escapeHtml(v.instansi_asal || '–')}</td>
              <td>${escapeHtml(v.tujuan_kunjungan)}</td>
              <td>${escapeHtml(v.orang_ditemui || '–')}</td>
              <td class="time">${formatDatePrint(v.waktu_masuk)}</td>
              <td class="time">${formatDatePrint(v.waktu_keluar)}</td>
            </tr>`
            )
            .join('')

    const content = `
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Buku Tamu - ${escapeHtml(institution.name || '')}</title>
  <style>
    @media print { @page { size: A4 landscape; margin: 1.2cm 1.4cm 1.2cm 1.2cm; } }
    body { font-family: 'DejaVu Sans', 'Segoe UI', sans-serif; font-size: 9pt; line-height: 1.25; color: #000; margin: 0; padding-right: 1px; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .header { text-align: center; margin: 10px 0 8px 0; }
    .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 4px 0; }
    .period { font-size: 8pt; margin-bottom: 8px; color: #555; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
    table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: middle; }
    table th { background: #e8e8e8; font-weight: bold; }
    .num { width: 28px; text-align: center; }
    .td-photo { width: 48px; text-align: center; padding: 2px; }
    .photo-thumb { width: 40px; height: 40px; object-fit: cover; display: block; margin: 0 auto; }
    .time { white-space: nowrap; }
    .footer { display: flex; justify-content: space-between; margin-top: 28px; page-break-inside: avoid; font-size: 9pt; }
    .footer-meta { font-size: 7pt; color: #666; }
    .footer-right { text-align: center; min-width: 220px; }
    .sig-space { height: 56px; }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${institution.logo ? `<img src="${fullPhotoUrl(institution.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
      <div class="kop-text">
        ${institution.foundation_name ? `<div class="foundation">${escapeHtml(institution.foundation_name)}</div>` : ''}
        <div class="school">${escapeHtml(institution.name || 'NAMA LEMBAGA')}</div>
        <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
        <div class="school-info">
          NPSN: ${escapeHtml(institution.npsn || '–')}
          ${institution.nss ? ` · NSS: ${escapeHtml(institution.nss)}` : ''}
          ${institution.phone ? ` · Telp: ${escapeHtml(institution.phone)}` : ''}
          ${institution.email ? ` · Email: ${escapeHtml(institution.email)}` : ''}
          ${institution.website ? ` · ${escapeHtml(institution.website)}` : ''}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <div class="header">
    <h1>BUKU TAMU</h1>
    ${periodText ? `<div class="period">${periodText}</div>` : ''}
  </div>
  <table>
    <thead>
      <tr>
        <th class="num">No</th>
        <th class="td-photo">Foto</th>
        <th>Nama Tamu</th>
        <th>No. Identitas</th>
        <th>Instansi / Asal</th>
        <th>Tujuan</th>
        <th>Ditemui</th>
        <th class="time">Waktu Masuk</th>
        <th class="time">Waktu Keluar</th>
      </tr>
    </thead>
    <tbody>${rows}</tbody>
  </table>
  <div class="footer">
    <div class="footer-meta">
      Dicetak pada ${new Date().toLocaleString('id-ID')}<br>${visits.length} catatan
    </div>
    <div class="footer-right">
      ${escapeHtml(institution.district || institution.city || '........................')}, ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}<br>
      ${escapeHtml(getPrincipalTitle(institution.level))}
      <div class="sig-space"></div>
      <strong>${escapeHtml(institution.principal_name || '___________________')}</strong><br>
      NIP. ${escapeHtml(institution.principal_nip || '___________________')}
    </div>
  </div>
</body>
</html>`
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      toast.error('Popup diblokir', 'Izinkan popup untuk jendela cetak')
      return
    }
    printWindow.document.write(content)
    printWindow.document.close()
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = `Buku_Tamu_${filters.value.date_from || ''}_${filters.value.date_to || ''}.pdf`
    }, 300)
    toast.success('Jendela cetak dibuka. Gunakan Ctrl+P / Cmd+P untuk simpan PDF.')
  } catch (e) {
    toast.error('Gagal membuka cetak', e.formattedMessage)
  } finally {
    exportingPdf.value = false
  }
}

function escapeHtml(s) {
  if (s == null) return '–'
  const str = String(s)
  const div = document.createElement('div')
  div.textContent = str
  return div.innerHTML
}

function openAddModal() {
  editingItem.value = null
  selectedPhoto.value = null
  photoPreview.value = ''
  const now = new Date()
  form.value = {
    nama_tamu: '',
    no_identitas: '',
    instansi_asal: '',
    no_telepon: '',
    tujuan_kunjungan: '',
    tujuan_kunjungan_lain: '',
    orang_ditemui: '',
    waktu_masuk: toDateTimeLocal(now.toISOString()),
    waktu_keluar: '',
    catatan: '',
  }
  formError.value = ''
  if (photoInputRef.value) photoInputRef.value.value = ''
  showFormModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  selectedPhoto.value = null
  photoPreview.value = ''
  const tujuan = item.tujuan_kunjungan || ''
  const isTujuanLainnya = tujuan && !TUJUAN_OPTIONS.includes(tujuan)
  form.value = {
    nama_tamu: item.nama_tamu || '',
    no_identitas: item.no_identitas || '',
    instansi_asal: item.instansi_asal || '',
    no_telepon: item.no_telepon || '',
    tujuan_kunjungan: isTujuanLainnya ? 'Lainnya' : tujuan,
    tujuan_kunjungan_lain: isTujuanLainnya ? tujuan : '',
    orang_ditemui: item.orang_ditemui || '',
    waktu_masuk: toDateTimeLocal(item.waktu_masuk),
    waktu_keluar: toDateTimeLocal(item.waktu_keluar),
    catatan: item.catatan || '',
  }
  formError.value = ''
  if (photoInputRef.value) photoInputRef.value.value = ''
  showFormModal.value = true
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
    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
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
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
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
    const url = URL.createObjectURL(file)
    photoPreview.value = url
  } else {
    photoPreview.value = ''
  }
}

watch(showFormModal, (v) => {
  if (!v) {
    closeCamera()
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
    photoPreview.value = ''
    selectedPhoto.value = null
  }
})

async function submitForm() {
  formError.value = ''
  if (!form.value.nama_tamu?.trim()) {
    formError.value = 'Nama tamu wajib diisi.'
    return
  }
  const tujuanValue = form.value.tujuan_kunjungan === 'Lainnya'
    ? form.value.tujuan_kunjungan_lain?.trim()
    : form.value.tujuan_kunjungan?.trim()
  if (!tujuanValue) {
    formError.value = 'Tujuan kunjungan wajib diisi.'
    return
  }
  if (form.value.no_telepon?.trim() && !isValidPhone(form.value.no_telepon)) {
    formError.value = 'Format nomor telepon tidak valid (min 10 karakter, angka/+/spasi).'
    return
  }
  if (!editingItem.value && !selectedPhoto.value) {
    formError.value = 'Foto tamu wajib diambil (gunakan kamera).'
    return
  }

  formSubmitting.value = true
  try {
    const payload = {
      nama_tamu: form.value.nama_tamu.trim(),
      no_identitas: form.value.no_identitas?.trim() || null,
      instansi_asal: form.value.instansi_asal?.trim() || null,
      no_telepon: form.value.no_telepon?.trim() || null,
      tujuan_kunjungan: tujuanValue,
      orang_ditemui: form.value.orang_ditemui?.trim() || null,
      catatan: form.value.catatan?.trim() || null,
    }
    if (form.value.waktu_masuk) payload.waktu_masuk = form.value.waktu_masuk
    if (editingItem.value && form.value.waktu_keluar) payload.waktu_keluar = form.value.waktu_keluar
    if (editingItem.value) {
      if (selectedPhoto.value) payload.foto = selectedPhoto.value
      await guestVisitApi.update(editingItem.value.id, payload)
      toast.success('Data tamu berhasil diperbarui')
    } else {
      payload.foto = selectedPhoto.value
      await guestVisitApi.create(payload)
      toast.success('Tamu berhasil dicatat')
    }
    showFormModal.value = false
    loadList()
  } catch (e) {
    formError.value = e.formattedMessage || 'Gagal menyimpan.'
  } finally {
    formSubmitting.value = false
  }
}

async function doCheckout(item) {
  try {
    await guestVisitApi.checkout(item.id)
    toast.success('Waktu keluar berhasil dicatat')
    loadList()
  } catch (e) {
    toast.error('Gagal mencatat keluar', e.formattedMessage)
  }
}

function confirmDelete(item) {
  deleteTarget.value = item
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await guestVisitApi.delete(deleteTarget.value.id)
    toast.success('Data tamu berhasil dihapus')
    deleteTarget.value = null
    loadList()
  } catch (e) {
    toast.error('Gagal menghapus', e.formattedMessage)
  }
}

onMounted(() => {
  loadList()
})
</script>

<style scoped>
.buku-tamu-page {
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
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
  background: var(--color-primary-light, #ecfdf5);
  color: var(--color-primary, #059669);
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

.btn-header,
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  cursor: pointer;
  transition: box-shadow 0.2s ease;
}
.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
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

.search-wrap {
  position: relative;
  width: 220px;
  min-width: 0;
}

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

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-export-pdf {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-primary, #059669);
  background: var(--color-primary-light, #ecfdf5);
  border: 1px solid rgba(26, 115, 232, 0.3);
  border-radius: 6px;
  cursor: pointer;
}

.btn-export-pdf:hover:not(:disabled) {
  background: var(--color-primary, #059669);
  color: #fff;
  border-color: var(--color-primary, #059669);
}

.btn-export-pdf:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

.card {
  background: var(--color-surface, #fff);
  border-radius: 10px;
  border: 1px solid var(--color-border, #e8eaed);
  overflow: hidden;
}

.table-scroll { overflow-x: auto; }

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  text-align: left;
}

.data-table th {
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-muted, #5f6368);
  background: var(--color-bg-subtle, #f8f9fa);
  padding: 0.625rem 1rem;
  border-bottom: 1px solid var(--color-border, #e8eaed);
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border, #f1f3f4);
  font-size: 0.875rem;
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background: var(--color-bg-subtle, #fafafa);
}

.data-table tbody tr:last-child td { border-bottom: none; }

.th-photo, .td-photo { width: 64px; }
.th-guest { min-width: 140px; }
.th-purpose { min-width: 120px; }
.th-met { min-width: 100px; }
.th-time { min-width: 100px; white-space: nowrap; }
.td-time { white-space: nowrap; font-size: 0.8125rem; color: var(--color-text-muted, #5f6368); }
.col-actions { width: 88px; text-align: right; }

.guest-photo {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 8px;
  display: block;
}

.no-photo { font-size: 0.8125rem; color: var(--color-text-muted, #bdc1c6); }

.td-guest .guest-name { font-weight: 500; display: block; }
.guest-org {
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
  display: block;
  margin-top: 0.125rem;
}

.td-purpose, .td-met { color: var(--color-text-muted, #5f6368); font-size: 0.8125rem; }

.guest-name {
  font-weight: 500;
}

.guest-org {
  font-size: 0.85rem;
  color: var(--color-text-muted, #5f6368);
}

.btn-checkout {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  background: var(--color-primary-light, #ecfdf5);
  color: var(--color-primary, #059669);
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-checkout:hover {
  background: var(--color-primary, #059669);
  color: #fff;
}

.action-buttons { display: flex; gap: 0.25rem; justify-content: flex-end; }

.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: var(--color-text-muted, #5f6368);
}

.btn-icon:hover {
  background: var(--color-bg-subtle, #f1f3f4);
  color: var(--color-text, #1a1a1a);
}

.btn-edit:hover { background: rgba(5, 150, 105, 0.15); color: #059669; }
.btn-delete:hover { background: #fce8e6; color: #c5221f; }

.pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--color-border, #e8eaed);
  font-size: 0.8125rem;
  color: var(--color-text-muted, #5f6368);
}

.pagination-buttons { display: flex; gap: 0.375rem; }

.btn-page {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
}

.btn-page:hover:not(:disabled) { background: var(--color-bg-subtle, #f8f9fa); }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: var(--color-surface, #fff);
  border-radius: 12px;
  max-width: 480px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--color-border, #e8eaed);
}

.modal-title { margin: 0; font-size: 1.125rem; font-weight: 600; }

.modal-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: var(--color-text-muted, #5f6368);
  border-radius: 6px;
}

.modal-close:hover {
  background: var(--color-bg-subtle, #f1f3f4);
  color: var(--color-text, #1a1a1a);
}

.modal-body { padding: 1.25rem; overflow-y: auto; }

.form-section { margin-bottom: 1.25rem; }
.form-section:last-of-type { margin-bottom: 0; }

.form-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-text-muted, #5f6368);
  margin: 0 0 0.75rem 0;
}

.form-section-title .required { color: #c5221f; }

.field { margin-bottom: 0.75rem; }
.field:last-child { margin-bottom: 0; }

.field label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: var(--color-text, #1a1a1a);
}

.field .required { color: #c5221f; }

.field input,
.field textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border, #dadce0);
  border-radius: 6px;
  font-size: 0.875rem;
  background: var(--color-surface, #fff);
}

.field textarea { resize: vertical; min-height: 60px; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

.photo-block { margin-top: 0.25rem; }

.photo-input-hidden {
  position: absolute;
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  z-index: -1;
}

.photo-buttons { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }

.btn-camera {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-primary, #059669);
  background: var(--color-primary-light, #ecfdf5);
  border: 1px solid rgba(26, 115, 232, 0.3);
  border-radius: 8px;
  cursor: pointer;
}

.btn-camera:hover {
  background: var(--color-primary, #059669);
  color: #fff;
  border-color: var(--color-primary, #059669);
}

.btn-camera.btn-small { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }

.btn-link {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  color: var(--color-primary, #059669);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
}

.btn-link:hover { color: #1557b0; }

.camera-block { margin-top: 0.5rem; }

.camera-video {
  display: block;
  width: 100%;
  max-width: 320px;
  border-radius: 8px;
  background: #111;
  aspect-ratio: 4/3;
  object-fit: cover;
}

.camera-error { font-size: 0.8125rem; color: #c5221f; margin: 0.5rem 0 0 0; }

.camera-buttons { display: flex; gap: 0.5rem; margin-top: 0.5rem; }

.btn-capture {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  background: var(--color-primary, #059669);
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.btn-ghost {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  color: var(--color-text-muted, #5f6368);
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.btn-ghost:hover {
  background: var(--color-bg-subtle, #f1f3f4);
  color: var(--color-text, #1a1a1a);
}

.photo-preview-box {
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--color-border, #e8eaed);
  margin-bottom: 0.5rem;
}

.photo-preview-box img { width: 100%; height: 100%; object-fit: cover; }

.form-hint {
  font-size: 0.75rem;
  color: var(--color-text-muted, #9aa0a6);
  margin: 0.5rem 0 0 0;
}

.field-hint { font-size: 0.75rem; margin-top: 0.25rem; }
.field-hint-error { color: #c5221f; }
.field-mt { margin-top: 0.5rem; display: block; width: 100%; }

.form-error { font-size: 0.8125rem; color: #c5221f; margin-top: 0.75rem; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border, #e8eaed);
  background: var(--color-bg-subtle, #fafafa);
}

.modal-footer .btn-primary {
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  background: var(--color-primary, #059669);
  color: #fff;
  cursor: pointer;
}

.modal-footer .btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

@media (max-width: 768px) {
  .buku-tamu-page {
    padding: 0 0 1rem;
  }

  .page-header {
    margin-bottom: 1rem;
  }

  .header-content {
    gap: 0.75rem;
  }

  .header-icon-wrap {
    width: 38px;
    height: 38px;
  }

  .header-icon {
    width: 20px;
    height: 20px;
  }

  .page-title {
    font-size: 1.125rem;
  }

  .page-subtitle {
    font-size: 0.75rem;
  }

  .btn-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.8125rem;
    width: 100%;
    justify-content: center;
  }

  .btn-header svg {
    width: 16px;
    height: 16px;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
    padding: 0.5rem 0.75rem;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }

  .toolbar-left {
    flex-direction: column;
    gap: 0.5rem;
  }

  .stat-badge {
    padding-right: 0;
    border-right: none;
    font-size: 0.75rem;
  }

  .search-wrap {
    width: 100%;
  }

  .search-input {
    padding: 0.4rem 0.5rem 0.4rem 1.75rem;
    font-size: 0.8125rem;
  }

  .search-icon {
    left: 8px;
    width: 14px;
    height: 14px;
  }

  .filter-dates {
    width: 100%;
    flex-wrap: wrap;
  }

  .filter-date {
    flex: 1;
    min-width: 0;
    padding: 0.4rem 0.5rem;
    font-size: 0.75rem;
  }

  .toolbar-right {
    flex-wrap: wrap;
    gap: 0.375rem;
  }

  .btn-export-pdf,
  .btn-reset {
    padding: 0.4rem 0.6rem;
    font-size: 0.75rem;
  }

  .btn-export-pdf svg {
    width: 14px;
    height: 14px;
  }

  .empty-state {
    padding: 1.5rem 1rem;
  }

  .empty-icon {
    margin-bottom: 0.75rem;
  }

  .empty-icon svg {
    width: 48px;
    height: 48px;
  }

  .empty-title {
    font-size: 1rem;
  }

  .empty-desc {
    font-size: 0.8125rem;
    margin-bottom: 1rem;
  }

  .btn-empty-cta {
    padding: 0.4rem 1rem;
    font-size: 0.8125rem;
  }

  .card.table-card {
    border-radius: 8px;
  }

  .data-table th,
  .data-table td {
    padding: 0.5rem 0.5rem;
    font-size: 0.75rem;
  }

  .data-table th {
    font-size: 0.6875rem;
    padding: 0.4rem 0.5rem;
  }

  .th-photo,
  .td-photo {
    width: 44px;
  }

  .guest-photo {
    width: 32px;
    height: 32px;
  }

  .td-guest .guest-name,
  .guest-org {
    font-size: 0.75rem;
  }

  .td-purpose,
  .td-met,
  .td-time {
    font-size: 0.6875rem;
  }

  .th-guest { min-width: 100px; }
  .th-purpose { min-width: 80px; }
  .th-met { min-width: 72px; }
  .th-time { min-width: 72px; }
  .col-actions { width: 72px; }

  .btn-checkout {
    padding: 0.2rem 0.4rem;
    font-size: 0.6875rem;
  }

  .btn-icon {
    width: 28px;
    height: 28px;
  }

  .btn-icon svg {
    width: 14px;
    height: 14px;
  }

  .pagination {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
  }

  .pagination-buttons {
    justify-content: center;
  }

  .btn-page {
    padding: 0.35rem 0.6rem;
    font-size: 0.75rem;
  }

  .modal-overlay {
    padding: 0.5rem;
    align-items: flex-end;
  }

  .modal {
    max-height: 85vh;
    border-radius: 12px 12px 0 0;
  }

  .modal-header {
    padding: 0.75rem 1rem;
  }

  .modal-title {
    font-size: 1rem;
  }

  .modal-close {
    width: 28px;
    height: 28px;
    font-size: 1.25rem;
  }

  .modal-body {
    padding: 1rem;
  }

  .form-section-title {
    font-size: 0.6875rem;
    margin-bottom: 0.5rem;
  }

  .field label {
    font-size: 0.75rem;
  }

  .field input,
  .field textarea,
  .field select {
    padding: 0.4rem 0.6rem;
    font-size: 0.8125rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .btn-camera {
    padding: 0.4rem 0.75rem;
    font-size: 0.8125rem;
  }

  .btn-camera.btn-small {
    padding: 0.3rem 0.5rem;
    font-size: 0.75rem;
  }

  .photo-preview-box {
    width: 80px;
    height: 80px;
  }

  .camera-video {
    max-width: 100%;
  }

  .modal-footer {
    padding: 0.75rem 1rem;
  }

  .modal-footer .btn-primary,
  .modal-footer .btn-ghost {
    padding: 0.4rem 1rem;
    font-size: 0.8125rem;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.4rem 0.35rem;
    font-size: 0.6875rem;
  }

  .data-table th {
    font-size: 0.625rem;
  }

  .th-photo,
  .td-photo {
    width: 36px;
  }

  .guest-photo {
    width: 28px;
    height: 28px;
  }

  .col-actions {
    width: 64px;
  }

  .btn-icon {
    width: 26px;
    height: 26px;
  }
}
</style>
