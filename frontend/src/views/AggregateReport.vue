<template>
  <Layout>
    <div class="report-page">
      <div class="page-header">
        <div class="page-header-text">
          <h2>Laporan Agregat</h2>
          <p>Ringkasan lintas institusi: jenjang, jenis, kontak WA, dan per sekolah</p>
        </div>
        <div class="filters-bar">
          <select v-model="filters.level" class="filter-select" @change="loadReport">
            <option value="">Semua Jenjang</option>
            <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
          </select>
          <select v-model="filters.type" class="filter-select" @change="loadReport">
            <option value="">Semua Jenis</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
          </select>
          <select v-model="filters.is_active" class="filter-select" @change="loadReport">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Dibekukan</option>
          </select>
          <button type="button" class="btn-export" :disabled="exporting" @click="handleExport">
            {{ exporting ? 'Mengekspor...' : 'Export Excel' }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="4" />
      </div>

      <template v-else>
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-label">Institusi</span>
            <strong class="stat-value">{{ formatNumber(summary.institutions) }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Aktif</span>
            <strong class="stat-value">{{ formatNumber(summary.active_institutions) }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Siswa Aktif</span>
            <strong class="stat-value">{{ formatNumber(summary.students) }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Guru Aktif</span>
            <strong class="stat-value">{{ formatNumber(summary.teachers) }}</strong>
          </div>
        </div>

        <div class="panels-grid">
          <section class="panel">
            <h3>Per Jenjang</h3>
            <div class="table-scroll">
              <table class="data-table data-table-compact">
                <thead>
                  <tr><th>Jenjang</th><th>Institusi</th><th>Siswa</th><th>Guru</th></tr>
                </thead>
                <tbody>
                  <tr v-for="row in byLevel" :key="row.level">
                    <td>{{ row.level }}</td>
                    <td>{{ formatNumber(row.institutions) }}</td>
                    <td>{{ formatNumber(row.students) }}</td>
                    <td>{{ formatNumber(row.teachers) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="byLevel.length === 0" class="empty-hint">Tidak ada data</p>
          </section>

          <section class="panel">
            <h3>Per Jenis</h3>
            <div class="table-scroll">
              <table class="data-table data-table-compact">
                <thead>
                  <tr><th>Jenis</th><th>Institusi</th><th>Siswa</th><th>Guru</th></tr>
                </thead>
                <tbody>
                  <tr v-for="row in byType" :key="row.type">
                    <td>{{ row.type }}</td>
                    <td>{{ formatNumber(row.institutions) }}</td>
                    <td>{{ formatNumber(row.students) }}</td>
                    <td>{{ formatNumber(row.teachers) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="byType.length === 0" class="empty-hint">Tidak ada data</p>
          </section>
        </div>

        <section class="panel">
          <h3>Per Provinsi</h3>
          <div class="table-scroll">
            <table class="data-table data-table-compact">
              <thead>
                <tr><th>Provinsi</th><th>Institusi</th><th>Siswa</th><th>Guru</th></tr>
              </thead>
              <tbody>
                <tr v-for="row in byProvince" :key="row.province">
                  <td>{{ row.province }}</td>
                  <td>{{ formatNumber(row.institutions) }}</td>
                  <td>{{ formatNumber(row.students) }}</td>
                  <td>{{ formatNumber(row.teachers) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="byProvince.length === 0" class="empty-hint">Tidak ada data</p>
        </section>

        <section class="panel panel-detail">
          <div class="panel-header">
            <h3>Detail Institusi</h3>
            <input v-model="search" class="search-input" placeholder="Cari nama, NPSN, WA, email..." />
          </div>

          <!-- Desktop / tablet table -->
          <div class="table-container table-desktop">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>NPSN</th>
                  <th>Jenjang</th>
                  <th>Jenis</th>
                  <th>Provinsi</th>
                  <th>No HP/WA</th>
                  <th>Email</th>
                  <th>Siswa</th>
                  <th>Guru</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inst in filteredInstitutions" :key="'t-' + inst.id">
                  <td>{{ inst.name }}</td>
                  <td>{{ inst.npsn || '—' }}</td>
                  <td>{{ inst.level || '—' }}</td>
                  <td>{{ inst.type || '—' }}</td>
                  <td>{{ inst.province || '—' }}</td>
                  <td>
                    <a
                      v-if="inst.phone"
                      class="contact-link"
                      :href="waLink(inst.phone)"
                      target="_blank"
                      rel="noopener noreferrer"
                    >{{ inst.phone }}</a>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>
                    <a
                      v-if="inst.email"
                      class="contact-link"
                      :href="'mailto:' + inst.email"
                    >{{ inst.email }}</a>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td>{{ formatNumber(inst.students) }}</td>
                  <td>{{ formatNumber(inst.teachers) }}</td>
                  <td>
                    <span :class="inst.is_active ? 'badge-active' : 'badge-inactive'">
                      {{ inst.is_active ? 'Aktif' : 'Dibekukan' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile cards -->
          <div class="inst-cards">
            <article v-for="inst in filteredInstitutions" :key="'c-' + inst.id" class="inst-card">
              <div class="inst-card-top">
                <div class="inst-card-main">
                  <strong>{{ inst.name }}</strong>
                  <span class="inst-meta">{{ inst.npsn || '—' }} · {{ inst.level || '—' }} · {{ inst.type || '—' }}</span>
                </div>
                <span :class="inst.is_active ? 'badge-active' : 'badge-inactive'">
                  {{ inst.is_active ? 'Aktif' : 'Dibekukan' }}
                </span>
              </div>
              <div class="inst-card-stats">
                <div>
                  <span>Provinsi</span>
                  <strong>{{ inst.province || '—' }}</strong>
                </div>
                <div>
                  <span>No HP/WA</span>
                  <strong>
                    <a
                      v-if="inst.phone"
                      class="contact-link"
                      :href="waLink(inst.phone)"
                      target="_blank"
                      rel="noopener noreferrer"
                    >{{ inst.phone }}</a>
                    <template v-else>—</template>
                  </strong>
                </div>
                <div>
                  <span>Email</span>
                  <strong>
                    <a
                      v-if="inst.email"
                      class="contact-link"
                      :href="'mailto:' + inst.email"
                    >{{ inst.email }}</a>
                    <template v-else>—</template>
                  </strong>
                </div>
                <div>
                  <span>Siswa</span>
                  <strong>{{ formatNumber(inst.students) }}</strong>
                </div>
                <div>
                  <span>Guru</span>
                  <strong>{{ formatNumber(inst.teachers) }}</strong>
                </div>
              </div>
            </article>
          </div>

          <p v-if="filteredInstitutions.length === 0" class="empty-hint">Tidak ada institusi</p>
        </section>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const exporting = ref(false)
const search = ref('')
const levels = ['TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MAK', 'MTs', 'MI', 'PAUD']
const filters = ref({ level: '', type: '', is_active: '' })
const summary = ref({ institutions: 0, active_institutions: 0, students: 0, teachers: 0 })
const byLevel = ref([])
const byType = ref([])
const byProvince = ref([])
const institutions = ref([])

const filteredInstitutions = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return institutions.value
  return institutions.value.filter((i) =>
    (i.name || '').toLowerCase().includes(q) ||
    (i.npsn || '').toLowerCase().includes(q) ||
    (i.phone || '').toLowerCase().includes(q) ||
    (i.email || '').toLowerCase().includes(q)
  )
})

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n || 0)

/** Normalize phone to WhatsApp deep link (62… without leading 0). */
const waLink = (phone) => {
  const digits = String(phone || '').replace(/\D/g, '')
  if (!digits) return '#'
  const intl = digits.startsWith('0') ? `62${digits.slice(1)}` : digits
  return `https://wa.me/${intl}`
}

const buildParams = () => {
  const params = {}
  if (filters.value.level) params.level = filters.value.level
  if (filters.value.type) params.type = filters.value.type
  if (filters.value.is_active !== '') params.is_active = filters.value.is_active === '1'
  return params
}

const loadReport = async () => {
  loading.value = true
  try {
    const res = await superAdminPlatformApi.getAggregateReport(buildParams())
    const data = res.data?.data || {}
    summary.value = data.summary || summary.value
    byLevel.value = data.by_level || []
    byType.value = data.by_type || []
    byProvince.value = data.by_province || []
    institutions.value = data.institutions || []
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat laporan')
  } finally {
    loading.value = false
  }
}

const handleExport = async () => {
  exporting.value = true
  try {
    const res = await superAdminPlatformApi.exportAggregateReport(buildParams())
    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `laporan-agregat-${new Date().toISOString().slice(0, 10)}.xlsx`
    a.click()
    window.URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Excel berhasil diunduh')
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal export Excel')
  } finally {
    exporting.value = false
  }
}

onMounted(loadReport)
</script>

<style scoped>
.report-page {
  width: 100%;
  max-width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.page-header-text h2 {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.3px;
}

.page-header-text p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.4;
}

.filters-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.filter-select,
.search-input {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
  background: white;
  color: #0f172a;
  min-width: 0;
}

.filter-select:focus,
.search-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.btn-export {
  border: 1px solid #059669;
  background: #059669;
  color: white;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-export:hover:not(:disabled) {
  background: #047857;
  border-color: #047857;
}

.btn-export:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.stat-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

.panels-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.panels-grid .panel {
  margin-bottom: 0;
}

.panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.panel h3 {
  margin: 0 0 12px;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.panel-header h3 {
  margin: 0;
}

.table-scroll,
.table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  max-width: 100%;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 10px 8px;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
  font-size: 13px;
  color: #0f172a;
}

.data-table th {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  color: #64748b;
}

.data-table-compact {
  min-width: 0;
}

.data-table-compact th:not(:first-child),
.data-table-compact td:not(:first-child) {
  text-align: right;
  white-space: nowrap;
}

.contact-link {
  color: #059669;
  text-decoration: none;
  font-weight: 500;
  white-space: nowrap;
}

.contact-link:hover {
  text-decoration: underline;
}

.text-muted {
  color: #94a3b8;
}

.badge-active,
.badge-inactive {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-active {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.badge-inactive {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.empty-hint {
  color: #94a3b8;
  font-size: 13px;
  margin: 8px 0 0;
}

.inst-cards {
  display: none;
}

.loading-wrap {
  margin-top: 8px;
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 800px) {
  .panels-grid {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    margin-bottom: 16px;
  }

  /* judul sudah di topbar — hemat ruang di mobile */
  .page-header-text h2 {
    display: none;
  }

  .page-header-text p {
    font-size: 13px;
  }

  .filters-bar {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    width: 100%;
  }

  .filter-select {
    width: 100%;
    min-height: 42px;
    font-size: 14px;
  }

  .filters-bar .filter-select:nth-child(3) {
    grid-column: 1 / -1;
  }

  .btn-export {
    grid-column: 1 / -1;
    width: 100%;
    min-height: 44px;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
  }

  .stat-card {
    padding: 14px;
    border-radius: 12px;
  }

  .stat-value {
    font-size: 1.35rem;
  }

  .panel {
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 12px;
  }

  .panel h3 {
    font-size: 0.9375rem;
  }

  .panel-header {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .search-input {
    width: 100%;
    min-height: 42px;
    font-size: 14px;
    box-sizing: border-box;
  }

  .table-desktop {
    display: none;
  }

  .inst-cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .inst-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    background: #f8fafc;
  }

  .inst-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
  }

  .inst-card-main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .inst-card-main strong {
    font-size: 14px;
    color: #0f172a;
    line-height: 1.3;
    word-break: break-word;
  }

  .inst-meta {
    font-size: 12px;
    color: #64748b;
  }

  .inst-card-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
  }

  .inst-card-stats span {
    display: block;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #94a3b8;
    margin-bottom: 2px;
  }

  .inst-card-stats strong {
    font-size: 13px;
    color: #0f172a;
    font-weight: 600;
    word-break: break-word;
  }

  .data-table-compact th,
  .data-table-compact td {
    padding: 8px 6px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .page-header-text p {
    font-size: 12px;
  }

  .stat-label {
    font-size: 11px;
  }

  .stat-value {
    font-size: 1.25rem;
  }

  .inst-card-stats {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
