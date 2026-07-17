<template>
  <Layout>
    <div class="alumni-page">
      <div class="page-header">
        <div class="header-text">
          <h1 class="page-title">Alumni</h1>
          <p class="page-subtitle">Data lulusan, tracking destinasi, dan laporan cetak resmi</p>
        </div>
        <div class="header-actions">
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="printing || loading"
            @click="printPdf"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <router-link to="/luluskan-siswa" class="btn-primary btn-compact btn-add">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Luluskan Siswa</span>
          </router-link>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-label">Total Alumni</span>
          <strong class="stat-value">{{ pagination.total || alumni.length || 0 }}</strong>
        </div>
        <div class="stat-card">
          <span class="stat-label">Tahun Lulus</span>
          <strong class="stat-value">{{ filters.graduation_year || 'Semua' }}</strong>
        </div>
        <div class="stat-card">
          <span class="stat-label">Halaman</span>
          <strong class="stat-value">{{ pagination.current_page }} / {{ pagination.last_page || 1 }}</strong>
        </div>
      </div>

      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          placeholder="Cari nama, NIS, NISN..."
          class="search-input"
        />
        <select v-model="filters.graduation_year" @change="onFilterYear" class="filter-select">
          <option value="">Semua Tahun Lulus</option>
          <option v-for="y in graduationYears" :key="y" :value="y">{{ y }}</option>
        </select>
        <button type="button" class="btn-ghost btn-compact" :disabled="loading" @click="resetFilters">
          Reset
        </button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="9" :cell-widths="['48px', '100px', '120px', '180px', '100px', '120px', '100px', '160px', '100px']" />
      </div>

      <div v-else class="content-wrapper">
        <div v-if="alumni.length > 0" class="table-container table-desktop">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Kelas Terakhir</th>
                <th>Tahun Lulus</th>
                <th>Destinasi</th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in alumni" :key="item.id">
                <td class="col-no">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                <td>{{ item.nis || '-' }}</td>
                <td>{{ item.nisn || '-' }}</td>
                <td>
                  <div class="name-cell">
                    <strong>{{ item.name }}</strong>
                    <span class="status-pill">Lulus</span>
                  </div>
                </td>
                <td>{{ item.gender === 'L' ? 'Laki-laki' : item.gender === 'P' ? 'Perempuan' : '-' }}</td>
                <td>{{ item.class_detail?.name || item.class || '-' }}</td>
                <td>{{ item.graduation_year || '-' }}</td>
                <td>
                  <span v-if="item.current_alumni_destination" class="dest-badge" :title="destinationFull(item.current_alumni_destination)">
                    {{ destinationTypeLabel(item.current_alumni_destination.destination_type) }}:
                    {{ item.current_alumni_destination.destination_name }}
                  </span>
                  <span v-else class="dest-empty">Belum diisi</span>
                </td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <button type="button" class="btn-action btn-dest" @click="openDestinations(item)" title="Kelola destinasi">
                      Destinasi
                    </button>
                    <router-link
                      :to="{ name: 'BukuInduk', params: { id: item.id }, query: { from: 'alumni' } }"
                      class="btn-action btn-view"
                      title="Lihat arsip"
                    >
                      Arsip
                    </router-link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="alumni.length > 0" class="alumni-cards table-mobile">
          <div v-for="item in alumni" :key="item.id" class="alumni-card">
            <div class="alumni-card-main">
              <div class="alumni-card-top">
                <h3 class="alumni-card-name">{{ item.name }}</h3>
                <span class="status-pill">Lulus</span>
              </div>
              <div class="alumni-card-meta">
                <span>{{ item.nisn ? `NISN: ${item.nisn}` : item.nis ? `NIS: ${item.nis}` : '-' }}</span>
                <span class="alumni-card-badge">{{ item.class_detail?.name || item.class || '-' }} · Lulus {{ item.graduation_year || '-' }}</span>
              </div>
              <p v-if="item.current_alumni_destination" class="alumni-card-dest">
                {{ destinationTypeLabel(item.current_alumni_destination.destination_type) }}:
                {{ item.current_alumni_destination.destination_name }}
              </p>
              <p v-else class="alumni-card-dest muted">Destinasi belum diisi</p>
            </div>
            <div class="alumni-card-actions">
              <button type="button" class="btn-action btn-dest" @click="openDestinations(item)">Destinasi</button>
              <router-link
                :to="{ name: 'BukuInduk', params: { id: item.id }, query: { from: 'alumni' } }"
                class="btn-action btn-view"
              >
                Arsip
              </router-link>
            </div>
          </div>
        </div>

        <div v-if="alumni.length === 0" class="empty-state">
          <div class="empty-state-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 14L16 10M12 14L8 10M12 14V21M3 10C3 10 5.5 4 12 4C18.5 4 21 10 21 10M3 10V20C3 20.5523 3.44772 21 4 21H20C20.5523 21 21 20.5523 21 20V10M3 10C3 9.44772 3.44772 9 4 9H20C20.5523 9 21 9.44772 21 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>Belum ada data alumni</h3>
          <p>
            Alumni akan muncul setelah siswa diluluskan. Gunakan tombol
            <strong>Luluskan Siswa</strong> untuk memproses kelulusan per kelas.
          </p>
          <router-link to="/luluskan-siswa" class="btn-empty-cta">Luluskan Siswa</router-link>
        </div>

        <div v-if="showDestModal" class="modal-overlay" @click.self="closeDestModal">
          <div class="modal-dest">
            <div class="modal-dest-header">
              <h3>Tracking destinasi — {{ selectedAlumni?.name || '' }}</h3>
              <button type="button" class="modal-close" @click="closeDestModal" aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-dest-body">
              <div class="dest-list" v-if="destinations.length > 0">
                <div v-for="d in destinations" :key="d.id" class="dest-item">
                  <div class="dest-item-main">
                    <span class="dest-item-type">{{ destinationTypeLabel(d.destination_type) }}</span>
                    <strong>{{ d.destination_name }}</strong>
                    <span v-if="d.program_or_position" class="dest-item-sub">{{ d.program_or_position }}</span>
                    <span v-if="d.year_entered" class="dest-item-year">{{ d.year_entered }}</span>
                  </div>
                  <div class="dest-item-actions">
                    <button type="button" class="btn-icon btn-edit" @click="editDestination(d)" title="Ubah">✎</button>
                    <button type="button" class="btn-icon btn-del" @click="confirmDeleteDest(d)" title="Hapus">⌫</button>
                  </div>
                </div>
              </div>
              <p v-else class="dest-list-empty">Belum ada data destinasi. Tambah di bawah.</p>

              <div class="dest-form-wrap">
                <h4>{{ editingDest ? 'Ubah destinasi' : 'Tambah destinasi' }}</h4>
                <form @submit.prevent="submitDestForm" class="dest-form">
                  <div class="form-row">
                    <label>Jenis</label>
                    <select v-model="destForm.destination_type" required>
                      <option value="">Pilih jenis</option>
                      <option v-for="(label, key) in destinationTypes" :key="key" :value="key">{{ label }}</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <label>Nama sekolah / PT / tempat kerja</label>
                    <input v-model="destForm.destination_name" type="text" required placeholder="Contoh: Universitas Indonesia" maxlength="255" />
                  </div>
                  <div class="form-row">
                    <label>Jurusan / prodi / jabatan (opsional)</label>
                    <input v-model="destForm.program_or_position" type="text" placeholder="Contoh: Teknik Informatika" maxlength="255" />
                  </div>
                  <div class="form-row">
                    <label>Tahun masuk (opsional)</label>
                    <input v-model.number="destForm.year_entered" type="number" min="1990" :max="new Date().getFullYear() + 2" placeholder="Contoh: 2024" />
                  </div>
                  <div class="form-row">
                    <label>Catatan (opsional)</label>
                    <textarea v-model="destForm.notes" rows="2" placeholder="Catatan tambahan"></textarea>
                  </div>
                  <div class="form-actions">
                    <button v-if="editingDest" type="button" class="btn-cancel" @click="cancelEditDest">Batal</button>
                    <button type="submit" class="btn-submit" :disabled="destSaving">
                      {{ destSaving ? 'Menyimpan...' : (editingDest ? 'Simpan perubahan' : 'Tambah destinasi') }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div v-if="destToDelete" class="modal-overlay" @click.self="destToDelete = null">
          <div class="modal-confirm">
            <p>Hapus destinasi "{{ destToDelete.destination_name }}"?</p>
            <div class="modal-confirm-actions">
              <button type="button" class="btn-cancel" @click="destToDelete = null">Batal</button>
              <button type="button" class="btn-del" @click="doDeleteDest">Hapus</button>
            </div>
          </div>
        </div>

        <div v-if="pagination.last_page > 1" class="pagination-wrap">
          <button
            :disabled="pagination.current_page <= 1"
            @click="goPage(pagination.current_page - 1)"
            class="btn-pagination"
          >
            Sebelumnya
          </button>
          <span class="pagination-info">
            Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
            · {{ pagination.total || 0 }} data
          </span>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goPage(pagination.current_page + 1)"
            class="btn-pagination"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { alumniApi } from '@/api/alumni'
import { institutionApi } from '@/api/institution'
import { getPrincipalTitle } from '@/utils/institution'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const printing = ref(false)
const institution = ref(null)
const alumni = ref([])
const graduationYears = ref([])
const filters = reactive({
  search: '',
  graduation_year: ''
})
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const destinationTypes = ref({})
const showDestModal = ref(false)
const selectedAlumni = ref(null)
const destinations = ref([])
const destForm = reactive({
  destination_type: '',
  destination_name: '',
  program_or_position: '',
  year_entered: null,
  notes: ''
})
const editingDest = ref(null)
const destSaving = ref(false)
const destToDelete = ref(null)

const DEST_TYPE_LABELS = {
  Sekolah: 'Lanjut Sekolah',
  Perguruan_Tinggi: 'Perguruan Tinggi',
  Kerja: 'Bekerja',
  Wirausaha: 'Wirausaha',
  Lainnya: 'Lainnya'
}

function destinationTypeLabel(key) {
  return destinationTypes.value[key] || DEST_TYPE_LABELS[key] || key
}

function destinationFull(dest) {
  if (!dest) return ''
  const parts = [
    destinationTypeLabel(dest.destination_type),
    dest.destination_name,
    dest.program_or_position,
    dest.year_entered ? `Th. ${dest.year_entered}` : null
  ].filter(Boolean)
  return parts.join(' · ')
}

function resetDestForm() {
  destForm.destination_type = ''
  destForm.destination_name = ''
  destForm.program_or_position = ''
  destForm.year_entered = null
  destForm.notes = ''
  editingDest.value = null
}

async function openDestinations(item) {
  selectedAlumni.value = item
  showDestModal.value = true
  resetDestForm()
  await loadDestinations()
}

function closeDestModal() {
  showDestModal.value = false
  selectedAlumni.value = null
  destinations.value = []
  resetDestForm()
  loadAlumni()
}

async function loadDestinations() {
  if (!selectedAlumni.value?.id) return
  try {
    const res = await alumniApi.getDestinationsByStudent(selectedAlumni.value.id)
    destinations.value = res.data?.data ?? []
  } catch {
    destinations.value = []
  }
}

async function submitDestForm() {
  if (!selectedAlumni.value?.id) return
  destSaving.value = true
  try {
    if (editingDest.value) {
      await alumniApi.updateDestination(editingDest.value.id, {
        destination_type: destForm.destination_type,
        destination_name: destForm.destination_name,
        program_or_position: destForm.program_or_position || null,
        year_entered: destForm.year_entered || null,
        notes: destForm.notes || null
      })
    } else {
      await alumniApi.storeDestination({
        student_id: selectedAlumni.value.id,
        destination_type: destForm.destination_type,
        destination_name: destForm.destination_name,
        program_or_position: destForm.program_or_position || null,
        year_entered: destForm.year_entered || null,
        notes: destForm.notes || null
      })
    }
    resetDestForm()
    await loadDestinations()
    loadAlumni()
  } catch (e) {
    const msg = e.response?.data?.message || e.formattedMessage || 'Destinasi tidak dapat disimpan. Coba lagi.'
    toast.error('Gagal menyimpan destinasi', msg)
  } finally {
    destSaving.value = false
  }
}

function cancelEditDest() {
  resetDestForm()
}

function editDestination(d) {
  editingDest.value = d
  destForm.destination_type = d.destination_type
  destForm.destination_name = d.destination_name
  destForm.program_or_position = d.program_or_position || ''
  destForm.year_entered = d.year_entered || null
  destForm.notes = d.notes || ''
}

function confirmDeleteDest(d) {
  destToDelete.value = d
}

async function doDeleteDest() {
  if (!destToDelete.value?.id) return
  try {
    await alumniApi.destroyDestination(destToDelete.value.id)
    destToDelete.value = null
    await loadDestinations()
    loadAlumni()
  } catch (e) {
    const msg = e.response?.data?.message || e.formattedMessage || 'Destinasi tidak dapat dihapus. Coba lagi.'
    toast.error('Gagal menghapus destinasi', msg)
  }
}

let debounceTimer = null
function debouncedLoad() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    pagination.current_page = 1
    loadAlumni()
  }, 300)
}

function onFilterYear() {
  pagination.current_page = 1
  loadAlumni()
}

function resetFilters() {
  filters.search = ''
  filters.graduation_year = ''
  pagination.current_page = 1
  loadAlumni()
}

async function loadGraduationYears() {
  try {
    const res = await alumniApi.getGraduationYears()
    graduationYears.value = res.data?.data || []
  } catch {
    graduationYears.value = []
  }
}

async function loadDestinationTypes() {
  try {
    const res = await alumniApi.getDestinationTypes()
    destinationTypes.value = res.data?.data || {}
  } catch {
    destinationTypes.value = {}
  }
}

async function ensureInstitutionLoaded() {
  if (institution.value?.name) return
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data || res.data || null
  } catch {
    institution.value = null
  }
}

async function loadAlumni() {
  loading.value = true
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page
    }
    if (filters.search) params.search = filters.search
    if (filters.graduation_year) params.graduation_year = filters.graduation_year
    const res = await alumniApi.getList(params)
    alumni.value = res.data?.data ?? []
    const meta = res.data?.meta
    if (meta) {
      pagination.current_page = meta.current_page
      pagination.last_page = meta.last_page
      pagination.per_page = meta.per_page
      pagination.total = meta.total ?? alumni.value.length
    } else {
      pagination.total = alumni.value.length
    }
  } catch {
    alumni.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

function goPage(page) {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  loadAlumni()
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function genderLabel(gender) {
  if (gender === 'L') return 'Laki-laki'
  if (gender === 'P') return 'Perempuan'
  return '-'
}

function destinationPrintText(item) {
  const dest = item.current_alumni_destination
  if (!dest) return '-'
  const parts = [
    destinationTypeLabel(dest.destination_type),
    dest.destination_name,
    dest.program_or_position
  ].filter(Boolean)
  return parts.join(' — ')
}

async function fetchAllAlumniForPrint() {
  const params = {
    page: 1,
    per_page: 1000
  }
  if (filters.search) params.search = filters.search
  if (filters.graduation_year) params.graduation_year = filters.graduation_year

  const res = await alumniApi.getList(params)
  return res.data?.data ?? []
}

async function printPdf() {
  printing.value = true
  try {
    await ensureInstitutionLoaded()
    const rows = await fetchAllAlumniForPrint()
    if (!rows.length) {
      toast.error('Gagal', 'Tidak ada data alumni untuk dicetak sesuai filter saat ini.')
      return
    }

    const inst = institution.value || {}
    const instName = inst.name || 'Sekolah'
    const fullAddress = [
      inst.address,
      inst.village ? `Desa/Kel. ${inst.village}` : '',
      inst.sub_district ? `Kec. ${inst.sub_district}` : '',
      inst.district,
      inst.province,
      inst.postal_code,
    ].filter(Boolean).join(', ')

    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const placeDate = `${inst.district || inst.city || '........................'}, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric',
    })}`
    const filename = `Laporan_Alumni_${new Date().toISOString().slice(0, 10)}.pdf`
    const filterLabel = [
      filters.graduation_year ? `Tahun Lulus: ${filters.graduation_year}` : 'Tahun Lulus: Semua',
      filters.search ? `Pencarian: ${filters.search}` : null,
    ].filter(Boolean).join(' · ')

    const tableRows = rows.map((item, index) => `
      <tr>
        <td class="num">${index + 1}</td>
        <td>${escapeHtml(item.nis || '-')}</td>
        <td>${escapeHtml(item.nisn || '-')}</td>
        <td>${escapeHtml(item.name || '-')}</td>
        <td>${escapeHtml(genderLabel(item.gender))}</td>
        <td>${escapeHtml(item.class_detail?.name || item.class || '-')}</td>
        <td class="num">${escapeHtml(item.graduation_year || '-')}</td>
        <td>${escapeHtml(destinationPrintText(item))}</td>
      </tr>
    `).join('')

    const content = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${escapeHtml(filename)}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #111; margin: 16px; }
    h1 { font-size: 16px; margin: 12px 0 4px; text-align: center; text-transform: uppercase; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .subtitle { text-align: center; color: #444; margin-bottom: 8px; }
    .period { text-align: center; margin-bottom: 14px; font-size: 11px; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; }
    td.num, th.num { text-align: right; }
    tr { page-break-inside: avoid; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .footer { display: flex; justify-content: space-between; margin-top: 28px; page-break-inside: avoid; }
    .footer-right { text-align: center; min-width: 220px; }
    .sig-space { height: 56px; }
    @media print {
      @page { size: A4 portrait; margin: 10mm 12mm 10mm 10mm; }
      body { margin: 0; padding-right: 1px; }
    }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${inst.logo ? `<img src="${escapeHtml(inst.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
      <div class="kop-text">
        ${inst.foundation_name ? `<div class="foundation">${escapeHtml(inst.foundation_name)}</div>` : ''}
        <div class="school">${escapeHtml(instName)}</div>
        <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
        <div class="school-info">
          NPSN: ${escapeHtml(inst.npsn || '-')}
          ${inst.nss ? ` · NSS: ${escapeHtml(inst.nss)}` : ''}
          ${inst.phone ? ` · Telp: ${escapeHtml(inst.phone)}` : ''}
          ${inst.email ? ` · Email: ${escapeHtml(inst.email)}` : ''}
          ${inst.website ? ` · ${escapeHtml(inst.website)}` : ''}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>Laporan Data Alumni</h1>
  <div class="subtitle">Daftar Lulusan &amp; Destinasi</div>
  <div class="period"><strong>Filter:</strong> ${escapeHtml(filterLabel)}</div>
  <p class="note">Jumlah data: ${rows.length}</p>
  <table>
    <thead>
      <tr>
        <th class="num">No</th>
        <th>NIS</th>
        <th>NISN</th>
        <th>Nama</th>
        <th>JK</th>
        <th>Kelas Terakhir</th>
        <th class="num">Th. Lulus</th>
        <th>Destinasi</th>
      </tr>
    </thead>
    <tbody>
      ${tableRows}
    </tbody>
  </table>
  <div class="footer">
    <div>
      <strong>Dibuat pada:</strong><br>${escapeHtml(createdAt)}
    </div>
    <div class="footer-right">
      ${escapeHtml(placeDate)}<br>
      ${escapeHtml(getPrincipalTitle(inst.level))}
      <div class="sig-space"></div>
      <strong>${escapeHtml(inst.principal_name || '___________________')}</strong>
      <br>NIP. ${escapeHtml(inst.principal_nip || '___________________')}
    </div>
  </div>
</body>
</html>`

    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      toast.error('Gagal', 'Popup diblokir. Izinkan popup untuk mencetak PDF.')
      return
    }
    printWindow.document.write(content)
    printWindow.document.close()
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    if (import.meta.env.DEV) console.error('Error printing alumni report:', err)
    toast.error('Gagal', 'Gagal menyiapkan cetak PDF alumni.')
  } finally {
    printing.value = false
  }
}

onMounted(() => {
  ensureInstitutionLoaded()
  loadGraduationYears()
  loadDestinationTypes()
  loadAlumni()
})
</script>

<style scoped>
.alumni-page {
  width: 100%;
  max-width: 100%;
  padding: 0;
}

.page-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.header-actions a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: opacity 0.2s, transform 0.15s, background 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.btn-primary:hover {
  opacity: 0.95;
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  color: #0f766e;
  border: 2px solid #99f6e4;
}

.btn-secondary:hover:not(:disabled) {
  background: #ecfdf5;
}

.btn-secondary:disabled,
.btn-ghost:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-ghost {
  background: #f8fafc;
  color: #475569;
  border: 2px solid #e2e8f0;
}

.btn-ghost:hover:not(:disabled) {
  background: #f1f5f9;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 14px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.stat-label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.25rem;
  color: #0f172a;
}

.filters.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.search-input {
  flex: 1;
  min-width: 220px;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.filter-select {
  min-width: 160px;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
}

.content-wrapper {
  position: relative;
}

.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.data-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.data-table .col-no {
  width: 3rem;
  text-align: center;
  white-space: nowrap;
}

.data-table .col-aksi {
  width: 180px;
  text-align: center;
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.name-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  background: #dcfce7;
  color: #166534;
}

.action-buttons {
  display: inline-flex;
  gap: 6px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 10px;
  border-radius: 10px;
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-action:hover {
  background: #d1fae5;
}

.dest-badge {
  font-size: 12px;
  color: #334155;
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dest-empty,
.muted {
  color: #94a3b8;
}

.alumni-card-dest {
  font-size: 13px;
  color: #475569;
  margin: 6px 0 0 0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-dest {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-dest-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.modal-dest-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  line-height: 1;
  color: white;
  opacity: 0.9;
  cursor: pointer;
  padding: 0 4px;
}

.modal-close:hover {
  opacity: 1;
}

.modal-dest-body {
  padding: 24px;
  overflow-y: auto;
}

.dest-list {
  margin-bottom: 24px;
}

.dest-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 8px;
}

.dest-item-main {
  flex: 1;
  min-width: 0;
}

.dest-item-main .dest-item-type {
  display: inline-block;
  font-size: 11px;
  text-transform: uppercase;
  color: #059669;
  font-weight: 600;
  margin-bottom: 4px;
}

.dest-item-main strong {
  display: block;
  font-size: 14px;
  color: #1e293b;
}

.dest-item-sub,
.dest-item-year {
  font-size: 13px;
  color: #64748b;
  display: block;
  margin-top: 2px;
}

.dest-item-actions {
  display: flex;
  gap: 4px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-icon.btn-edit {
  background: #ecfdf5;
  color: #4338ca;
}

.btn-icon.btn-edit:hover {
  background: #c7d2fe;
}

.btn-icon.btn-del {
  background: #fee2e2;
  color: #b91c1c;
}

.btn-icon.btn-del:hover {
  background: #fecaca;
}

.dest-list-empty {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 24px 0;
}

.dest-form-wrap h4 {
  margin: 0 0 16px 0;
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.dest-form .form-row {
  margin-bottom: 14px;
}

.dest-form .form-row label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  margin-bottom: 6px;
}

.dest-form .form-row input,
.dest-form .form-row select,
.dest-form .form-row textarea {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
}

.dest-form .form-row input:focus,
.dest-form .form-row select:focus,
.dest-form .form-row textarea:focus {
  outline: none;
  border-color: #059669;
}

.dest-form .form-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-cancel {
  padding: 10px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #f8fafc;
}

.btn-submit {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit:hover:not(:disabled) {
  opacity: 0.95;
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.modal-confirm {
  background: white;
  border-radius: 16px;
  padding: 24px;
  max-width: 360px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-confirm p {
  margin: 0 0 20px 0;
  font-size: 15px;
  color: #1e293b;
}

.modal-confirm-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-del {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  background: #dc2626;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-del:hover {
  background: #b91c1c;
}

.alumni-cards.table-mobile {
  display: none;
  flex-direction: column;
  gap: 12px;
}

.alumni-card {
  display: flex;
  flex-direction: column;
  padding: 16px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  gap: 12px;
}

.alumni-card-main {
  flex: 1;
  min-width: 0;
}

.alumni-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.alumni-card-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  line-height: 1.3;
}

.alumni-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #64748b;
  margin-top: 6px;
}

.alumni-card-badge {
  padding: 2px 8px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.alumni-card-actions {
  display: flex;
  gap: 8px;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  background: white;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.empty-state-icon {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 12px 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  line-height: 1.6;
  max-width: 420px;
  margin: 0 auto 24px;
}

.btn-empty-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  font-weight: 600;
  font-size: 14px;
  border-radius: 12px;
  text-decoration: none;
}

.pagination-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 24px;
  padding: 16px;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
}

.btn-pagination {
  padding: 10px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
}

.btn-pagination:hover:not(:disabled) {
  border-color: #059669;
  background: #f8fafc;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.table-mobile { display: none; }
.table-desktop { display: block; }

@media (max-width: 900px) {
  .stats-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    margin-bottom: 16px;
  }

  .filters.filters-inline {
    padding: 16px;
    margin-bottom: 16px;
  }

  .search-input,
  .filter-select {
    min-width: 100%;
  }

  .table-desktop { display: none; }
  .alumni-cards.table-mobile { display: flex; }

  .empty-state {
    padding: 32px 16px;
  }
}
</style>
