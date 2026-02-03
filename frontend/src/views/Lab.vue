<template>
  <Layout>
    <div class="lab-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Manajemen Lab</h2>
            <p>Daftar ruang laboratorium dan penanggung jawab (Kepala Lab)</p>
          </div>
          <router-link to="/facility" class="btn-secondary btn-compact">
            Kelola Sarana Prasarana
          </router-link>
        </div>
      </div>

      <div class="tabs-nav-lab">
        <button type="button" :class="['tab-btn-lab', { active: labTab === 'list' }]" @click="labTab = 'list'">
          Daftar Lab
        </button>
        <button type="button" :class="['tab-btn-lab', { active: labTab === 'report' }]" @click="switchToReport">
          Laporan Lab
        </button>
        <button type="button" :class="['tab-btn-lab', { active: labTab === 'mylabs' }]" @click="switchToMyLabs">
          Dashboard Saya
        </button>
      </div>

      <div v-show="labTab === 'list'" class="tab-panel">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="search"
              @input="debounceLoad"
              placeholder="Cari nama atau kode lab..."
              class="search-input"
            />
            <select v-model="buildingId" @change="loadLabs" class="filter-select">
              <option value="">Semua Gedung</option>
              <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-model="labType" @change="loadLabs" class="filter-select">
              <option value="">Semua Jenis Lab</option>
              <option value="IPA">Lab IPA</option>
              <option value="Komputer">Lab Komputer</option>
              <option value="Bahasa">Lab Bahasa</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
        </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
              <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
              <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
            </circle>
          </svg>
        </div>
        <p>Memuat data lab...</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 36px;"></th>
              <th>Nama Ruang</th>
              <th>Kode</th>
              <th>Jenis Lab</th>
              <th>Gedung</th>
              <th>Lantai</th>
              <th>Penanggung Jawab (Kepala Lab)</th>
              <th>Kondisi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="room in labs" :key="room.id">
              <tr>
                <td>
                  <button type="button" class="btn-expand" :aria-expanded="expandedRoomId === room.id" @click="toggleInventory(room)" title="Lihat inventaris lab">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" :class="{ expanded: expandedRoomId === room.id }">
                      <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </td>
                <td>{{ room.name }}</td>
              <td>{{ room.code || '-' }}</td>
              <td>{{ labTypeLabel(room.lab_type) }}</td>
              <td>{{ room.building?.name || '-' }}</td>
              <td>{{ room.floor }}</td>
              <td>
                <select
                  :value="room.responsible_employee_id || ''"
                  @change="(e) => updateResponsible(room, e.target.value)"
                  class="responsible-select"
                  :disabled="savingId === room.id"
                >
                  <option value="">— Pilih penanggung jawab —</option>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                    {{ emp.name }}{{ emp.nip ? ' (' + emp.nip + ')' : '' }}
                  </option>
                </select>
                <span v-if="savingId === room.id" class="saving-label">Menyimpan...</span>
              </td>
              <td><span :class="getConditionClass(room.condition)">{{ room.condition }}</span></td>
              </tr>
              <tr v-if="expandedRoomId === room.id" class="inventory-detail-row">
                <td colspan="8" class="inventory-detail-cell">
                  <div class="inventory-detail-header">Barang di lab ini</div>
                  <div v-if="getRoomInventory(room.id).loading" class="inventory-loading">Memuat...</div>
                  <div v-else-if="getRoomInventory(room.id).items.length === 0" class="inventory-empty">Tidak ada barang inventaris di ruang ini.</div>
                  <table v-else class="inventory-subtable">
                    <thead>
                      <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Qty</th>
                        <th>Kondisi</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in getRoomInventory(room.id).items" :key="item.id">
                        <td>{{ item.code || '-' }}</td>
                        <td>{{ item.name }}</td>
                        <td>{{ item.category?.name || '-' }}</td>
                        <td>{{ item.quantity }} {{ item.unit || '' }}</td>
                        <td>{{ item.condition || '-' }}</td>
                        <td>{{ item.status || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="inventory-detail-header" style="margin-top: 1rem;">Jadwal penggunaan lab</div>
                  <div v-if="getRoomSchedule(room.id).loading" class="inventory-loading">Memuat jadwal...</div>
                  <div v-else-if="!activeSemesterId" class="inventory-empty">Pilih semester aktif di profil instansi untuk menampilkan jadwal.</div>
                  <div v-else-if="getRoomSchedule(room.id).items.length === 0" class="inventory-empty">Tidak ada jadwal pelajaran di ruang ini.</div>
                  <table v-else class="inventory-subtable">
                    <thead>
                      <tr>
                        <th>Hari</th>
                        <th>Jam ke</th>
                        <th>Waktu</th>
                        <th>Mapel</th>
                        <th>Kelas</th>
                        <th>Guru</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="s in getRoomSchedule(room.id).items" :key="s.id">
                        <td>{{ s.day_name || '-' }}</td>
                        <td>{{ s.period }}</td>
                        <td>{{ s.start_time || '-' }}-{{ s.end_time || '-' }}</td>
                        <td>{{ s.subject?.name || '-' }}</td>
                        <td>{{ s.school_class?.name || '-' }}</td>
                        <td>{{ s.employee?.name || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
        <div v-if="labs.length === 0" class="empty-state">
          <p>Belum ada ruang laboratorium. Tambah ruangan dengan tipe <strong>Laboratorium</strong> di menu Sarana Prasarana.</p>
          <router-link to="/facility" class="btn-primary">Ke Sarana Prasarana</router-link>
        </div>
      </div>
      </div>

      <div v-show="labTab === 'report'" class="tab-panel">
        <div v-if="labReportLoading" class="loading-state">
          <p>Memuat laporan lab...</p>
        </div>
        <div v-else-if="labReportData" class="report-lab">
          <div class="report-summary-cards">
            <div class="report-card">
              <span class="report-card-value">{{ labReportData.summary?.total_labs ?? 0 }}</span>
              <span class="report-card-label">Total Lab</span>
            </div>
            <div v-for="(count, cond) in labReportData.summary?.by_condition" :key="'cond-' + cond" class="report-card">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">Kondisi {{ cond }}</span>
            </div>
          </div>
          <div class="report-summary-cards">
            <div v-for="(count, type) in labReportData.summary?.by_lab_type" :key="'type-' + type" class="report-card report-card-small">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">{{ labTypeLabel(type) }}</span>
            </div>
          </div>
          <table class="data-table report-table">
            <thead>
              <tr>
                <th>Nama Lab</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th>Kondisi</th>
                <th>Penanggung Jawab</th>
                <th>Jumlah Barang</th>
                <th>Jadwal (semester aktif)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lab in labReportData.labs" :key="lab.id">
                <td>{{ lab.name }}</td>
                <td>{{ labTypeLabel(lab.lab_type) }}</td>
                <td>{{ lab.building?.name || '-' }}</td>
                <td><span :class="getConditionClass(lab.condition)">{{ lab.condition }}</span></td>
                <td>{{ lab.responsible_employee?.name || '-' }}</td>
                <td>{{ lab.inventory_count }}</td>
                <td>{{ lab.schedule_count }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="!labReportData.labs?.length" class="empty-state">
            <p>Belum ada data lab.</p>
          </div>
        </div>
      </div>

      <div v-show="labTab === 'mylabs'" class="tab-panel">
        <div v-if="myLabsLoading" class="loading-state">
          <p>Memuat...</p>
        </div>
        <div v-else-if="myLabsData" class="report-lab">
          <div class="report-summary-cards">
            <div class="report-card">
              <span class="report-card-value">{{ myLabsData.summary?.total ?? 0 }}</span>
              <span class="report-card-label">Lab yang saya tanggung jawabi</span>
            </div>
            <div v-for="(count, cond) in myLabsData.summary?.by_condition" :key="'my-cond-' + cond" class="report-card report-card-small">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">{{ cond }}</span>
            </div>
          </div>
          <div v-if="!myLabsData.labs?.length" class="empty-state">
            <p>Anda belum ditetapkan sebagai penanggung jawab (Kepala Lab) untuk ruang lab manapun. Tetapkan di tab Daftar Lab atau melalui Sarana Prasarana.</p>
            <router-link to="/facility" class="btn-primary">Ke Sarana Prasarana</router-link>
          </div>
          <table v-else class="data-table report-table">
            <thead>
              <tr>
                <th>Nama Lab</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th>Kondisi</th>
                <th>Jumlah Barang</th>
                <th>Jadwal (sem. aktif)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lab in myLabsData.labs" :key="lab.id">
                <td>{{ lab.name }}</td>
                <td>{{ labTypeLabel(lab.lab_type) }}</td>
                <td>{{ lab.building?.name || '-' }}</td>
                <td><span :class="getConditionClass(lab.condition)">{{ lab.condition }}</span></td>
                <td>{{ lab.inventory_count }}</td>
                <td>{{ lab.schedule_count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { facilityApi } from '@/api/facility'
import { employeeApi } from '@/api/teacher'
import { inventoryApi } from '@/api/inventory'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { institutionApi } from '@/api/institution'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const labs = ref([])
const buildings = ref([])
const employees = ref([])
const loading = ref(false)
const savingId = ref(null)
const search = ref('')
const buildingId = ref('')
const labType = ref('')
const expandedRoomId = ref(null)
const roomInventoryMap = ref({})
const roomScheduleMap = ref({})
const activeSemesterId = ref(null)
const labTab = ref('list')
const labReportData = ref(null)
const labReportLoading = ref(false)
const myLabsData = ref(null)
const myLabsLoading = ref(false)

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadLabs(), 300)
}

async function loadLabs() {
  loading.value = true
  try {
    const params = { type: 'Laboratorium' }
    if (search.value) params.search = search.value
    if (buildingId.value) params.building_id = buildingId.value
    if (labType.value) params.lab_type = labType.value
    const res = await facilityApi.getRooms(params)
    labs.value = res?.data?.data ?? res?.data ?? []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat data lab')
    labs.value = []
  } finally {
    loading.value = false
  }
}

async function loadBuildings() {
  try {
    const res = await facilityApi.getBuildings({})
    buildings.value = res?.data?.data ?? res?.data ?? []
  } catch {
    buildings.value = []
  }
}

async function loadEmployees() {
  try {
    const res = await employeeApi.getAll({ per_page: 500 })
    const list = res?.data?.data ?? res?.data ?? []
    employees.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    employees.value = []
  }
}

async function updateResponsible(room, employeeId) {
  const value = employeeId ? Number(employeeId) : null
  savingId.value = room.id
  try {
    await facilityApi.updateRoom(room.id, {
      building_id: room.building_id || '',
      name: room.name,
      code: room.code || '',
      type: room.type,
      lab_type: room.lab_type || null,
      floor: room.floor,
      area: room.area ?? '',
      capacity: room.capacity ?? '',
      condition: room.condition,
      description: room.description || '',
      responsible_employee_id: value
    })
    room.responsible_employee_id = value
    if (value) {
      const emp = employees.value.find(e => e.id === value)
      room.responsible_employee = emp ? { id: emp.id, name: emp.name, nip: emp.nip, nuptk: emp.nuptk } : null
    } else {
      room.responsible_employee = null
    }
    toast.success('Berhasil', 'Penanggung jawab berhasil diperbarui')
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memperbarui penanggung jawab')
  } finally {
    savingId.value = null
  }
}

function labTypeLabel(key) {
  const labels = { IPA: 'Lab IPA', Komputer: 'Lab Komputer', Bahasa: 'Lab Bahasa', Lainnya: 'Lainnya' }
  return labels[key] || key || '-'
}

function getRoomInventory(roomId) {
  const m = roomInventoryMap.value[roomId]
  return m || { items: [], loading: false, loaded: false }
}

function getRoomSchedule(roomId) {
  const m = roomScheduleMap.value[roomId]
  return m || { items: [], loading: false, loaded: false }
}

async function toggleInventory(room) {
  const id = room.id
  if (expandedRoomId.value === id) {
    expandedRoomId.value = null
    return
  }
  expandedRoomId.value = id
  const inv = roomInventoryMap.value[id]
  if (!inv?.loaded) {
    roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items: [], loading: true, loaded: false } }
    try {
      const res = await inventoryApi.getItems({ room_id: id, per_page: 100 })
      const data = res?.data
      const items = data?.data ?? (Array.isArray(data) ? data : [])
      roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items, loading: false, loaded: true } }
    } catch {
      roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items: [], loading: false, loaded: true } }
    }
  }
  const sched = roomScheduleMap.value[id]
  if (!sched?.loaded && activeSemesterId.value) {
    roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items: [], loading: true, loaded: false } }
    try {
      const res = await lessonScheduleApi.getByRoom(id, { semester_id: activeSemesterId.value })
      const items = res?.data?.data ?? (Array.isArray(res?.data) ? res.data : [])
      roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items, loading: false, loaded: true } }
    } catch {
      roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items: [], loading: false, loaded: true } }
    }
  }
}

function getConditionClass(condition) {
  if (!condition) return ''
  const c = condition.toLowerCase().replace(/\s/g, '-')
  return `condition-${c}`
}

function formatNumber(n) {
  if (n == null || n === '') return '-'
  return Number(n).toLocaleString('id-ID')
}

async function switchToReport() {
  labTab.value = 'report'
  if (labReportData.value) return
  labReportLoading.value = true
  try {
    const res = await facilityApi.getLabReport()
    labReportData.value = res?.data?.data ?? res?.data ?? null
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat laporan lab')
    labReportData.value = { summary: {}, labs: [] }
  } finally {
    labReportLoading.value = false
  }
}

async function switchToMyLabs() {
  labTab.value = 'mylabs'
  if (myLabsData.value) return
  myLabsLoading.value = true
  try {
    const res = await facilityApi.getMyLabs()
    myLabsData.value = res?.data?.data ?? res?.data ?? null
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat data lab saya')
    myLabsData.value = { summary: { total: 0 }, labs: [] }
  } finally {
    myLabsLoading.value = false
  }
}

onMounted(async () => {
  try {
    const instRes = await institutionApi.getMy()
    const inst = instRes?.data?.data ?? instRes?.data
    if (inst?.active_semester_id) activeSemesterId.value = inst.active_semester_id
  } catch {}
  await Promise.all([loadBuildings(), loadEmployees()])
  await loadLabs()
})
</script>

<style scoped>
.lab-page {
  padding: 1rem;
  max-width: 1200px;
  margin: 0 auto;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.header-content h2 {
  margin: 0 0 0.25rem 0;
  font-size: 1.5rem;
}
.header-content p {
  margin: 0;
  color: var(--text-muted, #666);
  font-size: 0.9rem;
}
.tab-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}
.search-input {
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
  background: #fff;
}
.responsible-select {
  min-width: 220px;
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
  background: #fff;
}
.saving-label {
  margin-left: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-muted, #666);
}
.loading-state {
  text-align: center;
  padding: 2rem;
  color: var(--text-muted, #666);
}
.loading-spinner {
  margin-bottom: 0.5rem;
}
.loading-spinner svg {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.table-container {
  overflow-x: auto;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 8px;
  background: #fff;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color, #eee);
}
.data-table th {
  background: var(--table-header-bg, #f5f5f5);
  font-weight: 600;
  font-size: 0.85rem;
}
.data-table tbody tr:hover {
  background: var(--row-hover-bg, #fafafa);
}
.condition-baik { color: #16a34a; }
.condition-rusak-ringan { color: #ca8a04; }
.condition-rusak-sedang { color: #ea580c; }
.condition-rusak-berat { color: #dc2626; }
.btn-expand {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: var(--text-muted, #666);
  border-radius: 4px;
}
.btn-expand:hover { color: var(--primary, #2563eb); }
.btn-expand svg { display: block; transition: transform 0.2s; }
.btn-expand svg.expanded { transform: rotate(90deg); }
.inventory-detail-row { background: var(--row-expanded-bg, #f8fafc); }
.inventory-detail-cell { padding: 1rem 1rem 1rem 3rem; vertical-align: top; border-bottom: 1px solid var(--border-color, #e2e8f0); }
.inventory-detail-header { font-weight: 600; margin-bottom: 0.75rem; font-size: 0.9rem; }
.inventory-loading, .inventory-empty { color: var(--text-muted, #64748b); font-size: 0.9rem; padding: 0.5rem 0; }
.inventory-subtable { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
.inventory-subtable th, .inventory-subtable td { padding: 0.4rem 0.6rem; text-align: left; border: 1px solid var(--border-color, #e2e8f0); }
.inventory-subtable th { background: #f1f5f9; font-weight: 600; }
.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--text-muted, #666);
}
.empty-state p {
  margin-bottom: 1rem;
}
.tabs-nav-lab {
  display: flex;
  gap: 0.25rem;
  margin-bottom: 1rem;
}
.tab-btn-lab {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color, #e2e8f0);
  background: #fff;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}
.tab-btn-lab.active {
  background: var(--primary, #2563eb);
  color: #fff;
  border-color: var(--primary, #2563eb);
}
.tab-panel { margin-top: 0; }
.report-lab { padding: 0.5rem 0; }
.report-summary-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.report-card {
  min-width: 120px;
  padding: 1rem;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  background: #fff;
  text-align: center;
}
.report-card-value { display: block; font-size: 1.5rem; font-weight: 700; color: var(--primary, #2563eb); }
.report-card-label { font-size: 0.85rem; color: var(--text-muted, #64748b); }
.report-card-small { min-width: 90px; padding: 0.75rem; }
.report-card-small .report-card-value { font-size: 1.2rem; }
.report-table { margin-top: 1rem; }
.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  text-decoration: none;
  border: none;
  cursor: pointer;
}
.btn-primary {
  background: var(--primary, #2563eb);
  color: #fff;
}
.btn-secondary {
  background: var(--secondary-bg, #e5e7eb);
  color: var(--text, #374151);
}
.btn-secondary:hover {
  background: var(--secondary-hover, #d1d5db);
}
</style>
