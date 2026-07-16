<template>
  <Layout>
    <div class="alumni-page">
      <div class="page-header">
        <div class="header-content">
          <h1 class="page-title">Alumni</h1>
          <p class="page-subtitle">Data lulusan / alumni sekolah Anda</p>
          <div class="action-buttons-group">
            <router-link to="/luluskan-siswa" class="btn-secondary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Luluskan Siswa</span>
            </router-link>
          </div>
        </div>
      </div>

      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          @input="debouncedLoad"
          placeholder="Cari nama, NIS, NISN..."
          class="search-input"
        />
        <select v-model="filters.graduation_year" @change="loadAlumni" class="filter-select">
          <option value="">Semua Tahun Lulus</option>
          <option v-for="y in graduationYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="8" :columns="9" :cell-widths="['48px', '100px', '120px', '180px', '100px', '120px', '100px', '160px', '100px']" />
      </div>

      <div v-else class="content-wrapper">
        <!-- Desktop: table -->
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
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in alumni" :key="item.id">
                <td class="col-no">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                <td>{{ item.nis || '-' }}</td>
                <td>{{ item.nisn || '-' }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                <td>{{ item.class_detail?.name || item.class || '-' }}</td>
                <td>{{ item.graduation_year || '-' }}</td>
                <td>
                  <span v-if="item.current_alumni_destination" class="dest-badge">
                    {{ destinationTypeLabel(item.current_alumni_destination.destination_type) }}: {{ item.current_alumni_destination.destination_name }}
                  </span>
                  <span v-else class="dest-empty">—</span>
                </td>
                <td>
                  <button type="button" class="btn-action btn-dest" @click="openDestinations(item)" title="Kelola destinasi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Destinasi
                  </button>
                  <router-link :to="{ name: 'BukuInduk', params: { id: item.id }, query: { from: 'alumni' } }" class="btn-action btn-view" title="Lihat arsip">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile: cards -->
        <div v-if="alumni.length > 0" class="alumni-cards table-mobile">
          <div v-for="item in alumni" :key="item.id" class="alumni-card">
            <div class="alumni-card-main">
              <h3 class="alumni-card-name">{{ item.name }}</h3>
              <div class="alumni-card-meta">
                <span class="alumni-card-id">{{ item.nisn ? `NISN: ${item.nisn}` : item.nis ? `NIS: ${item.nis}` : '-' }}</span>
                <span class="alumni-card-badge">{{ item.class_detail?.name || item.class || '-' }} · Lulus {{ item.graduation_year || '-' }}</span>
              </div>
              <p v-if="item.current_alumni_destination" class="alumni-card-dest">
                {{ destinationTypeLabel(item.current_alumni_destination.destination_type) }}: {{ item.current_alumni_destination.destination_name }}
              </p>
              <span class="alumni-card-status">Lulus</span>
            </div>
            <div class="alumni-card-actions">
              <button type="button" class="btn-action btn-dest" @click="openDestinations(item)" title="Kelola destinasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <router-link :to="{ name: 'BukuInduk', params: { id: item.id }, query: { from: 'alumni' } }" class="btn-action btn-view" title="Lihat arsip">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
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
          <p>Alumni akan muncul setelah siswa diluluskan. Gunakan tombol <strong>Luluskan Siswa</strong> untuk memproses kelulusan per kelas.</p>
          <router-link to="/luluskan-siswa" class="btn-empty-cta">Luluskan Siswa</router-link>
        </div>

        <!-- Modal: Kelola destinasi alumni -->
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

        <!-- Konfirmasi hapus -->
        <div v-if="destToDelete" class="modal-overlay" @click.self="destToDelete = null">
          <div class="modal-confirm">
            <p>Hapus destinasi "{{ destToDelete.destination_name }}"?</p>
            <div class="modal-confirm-actions">
              <button type="button" class="btn-cancel" @click="destToDelete = null">Batal</button>
              <button type="button" class="btn-del" @click="doDeleteDest">Hapus</button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
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
import { ref, reactive, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { alumniApi } from '@/api/alumni'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const alumni = ref([])
const graduationYears = ref([])
const filters = reactive({
  search: '',
  graduation_year: ''
})
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15
})

// Tracking destinasi alumni
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
    }
  } catch {
    alumni.value = []
  } finally {
    loading.value = false
  }
}

function goPage(page) {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  loadAlumni()
}

onMounted(() => {
  loadGraduationYears()
  loadDestinationTypes()
  loadAlumni()
})

watch(() => filters.graduation_year, () => {
  pagination.current_page = 1
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
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.header-content .page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.header-content .page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.action-buttons-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.action-buttons-group a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

/* Filters: card style seperti Data Siswa */
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

.search-input:focus {
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
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.loading-state {
  text-align: center;
  padding: 48px 24px;
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.loading-state p {
  margin: 16px 0 0;
  color: #64748b;
  font-size: 14px;
}

.loading-spinner {
  color: #059669;
}

.loading-spinner svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
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
  padding: 16px 20px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table .col-no {
  width: 3rem;
  text-align: center;
  white-space: nowrap;
}

.data-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table td:last-child {
  text-align: center;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  border-radius: 10px;
  color: #059669;
  text-decoration: none;
  transition: background 0.2s;
}

.btn-action:hover {
  background: rgba(5, 150, 105, 0.12);
}

.btn-dest {
  margin-right: 6px;
}

.dest-badge {
  font-size: 12px;
  color: #475569;
  display: inline-block;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dest-empty {
  color: #94a3b8;
}

.alumni-card-dest {
  font-size: 13px;
  color: #475569;
  margin: 6px 0 0 0;
}

/* Modal destinasi */
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

.dest-item-sub, .dest-item-year {
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

/* Mobile cards */
.alumni-cards.table-mobile {
  display: none;
  flex-direction: column;
  gap: 12px;
}

.alumni-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
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

.alumni-card-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 6px 0;
  line-height: 1.3;
}

.alumni-card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #64748b;
}

.alumni-card-badge {
  padding: 2px 8px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.alumni-card-status {
  display: inline-block;
  margin-top: 8px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  background: #dcfce7;
  color: #166534;
}

.alumni-card-actions .btn-action {
  width: 44px;
  height: 44px;
}

/* Empty state */
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

.empty-state p strong {
  color: #475569;
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
  transition: opacity 0.2s, transform 0.15s;
}

.btn-empty-cta:hover {
  opacity: 0.95;
  transform: translateY(-1px);
}

/* Pagination */
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
  transition: border-color 0.2s, background 0.2s;
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

@media (max-width: 768px) {
  .page-header {
    margin-bottom: 16px;
  }

  .filters.filters-inline {
    padding: 16px;
    margin-bottom: 16px;
  }

  .search-input {
    min-width: 100%;
  }

  .filter-select {
    min-width: 100%;
  }

  .table-desktop { display: none; }
  .alumni-cards.table-mobile { display: flex; }

  .empty-state {
    padding: 32px 16px;
  }

  .empty-state h3 {
    font-size: 18px;
  }

  .empty-state p {
    font-size: 13px;
  }
}
</style>
