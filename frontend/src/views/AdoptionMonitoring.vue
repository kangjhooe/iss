<template>
  <Layout>
    <div class="adoption-page">
      <div class="page-header">
        <div>
          <h2>Monitoring Adopsi</h2>
          <p>Aktivitas institusi dan penggunaan modul lintas sekolah</p>
        </div>
        <div class="header-actions">
          <select v-model="inactiveDays" class="filter-select" @change="loadData">
            <option :value="7">Tidak aktif ≥ 7 hari</option>
            <option :value="30">Tidak aktif ≥ 30 hari</option>
            <option :value="90">Tidak aktif ≥ 90 hari</option>
          </select>
          <button type="button" class="btn-secondary btn-compact" :disabled="loading" @click="loadData">Muat Ulang</button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" />
      </div>

      <template v-else>
        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-label">Institusi Aktif</span>
            <strong>{{ summary.active_institutions }}</strong>
          </div>
          <div class="stat-card warn">
            <span class="stat-label">Tanpa Aktivitas</span>
            <strong>{{ summary.inactive_activity_count }}</strong>
          </div>
          <div class="stat-card danger">
            <span class="stat-label">Tanpa Admin</span>
            <strong>{{ summary.no_admin_count }}</strong>
          </div>
          <div class="stat-card">
            <span class="stat-label">Siswa / Guru</span>
            <strong>{{ formatNumber(summary.total_students) }} / {{ formatNumber(summary.total_teachers) }}</strong>
          </div>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Adopsi Modul (guru/staff dengan akses)</h3>
          </div>
          <div class="module-list">
            <div v-for="m in modules" :key="m.key" class="module-row">
              <div class="module-meta">
                <strong>{{ m.label }}</strong>
                <span>{{ m.institutions_count }} institusi · {{ m.users_count }} user</span>
              </div>
              <div class="module-bar-wrap">
                <div class="module-bar" :style="{ width: Math.min(m.adoption_pct, 100) + '%' }"></div>
              </div>
              <span class="module-pct">{{ m.adoption_pct }}%</span>
            </div>
            <p v-if="modules.length === 0" class="empty-hint">Belum ada data permission guru/staff.</p>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Aktivitas per Institusi</h3>
            <input v-model="search" class="search-input" placeholder="Cari institusi..." />
          </div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Institusi</th>
                  <th>Siswa</th>
                  <th>Guru</th>
                  <th>Admin</th>
                  <th>Aktivitas Terakhir</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inst in filteredInstitutions" :key="inst.id" :class="{ 'row-warn': inst.is_inactive && inst.is_active }">
                  <td>
                    <strong>{{ inst.name }}</strong>
                    <div class="sub">{{ inst.npsn || '—' }} · {{ inst.level || '—' }}</div>
                  </td>
                  <td>{{ formatNumber(inst.active_students_count) }}</td>
                  <td>{{ formatNumber(inst.active_teachers_count) }}</td>
                  <td>{{ inst.admins_count }}</td>
                  <td>{{ formatRelative(inst.last_activity_at) }}</td>
                  <td>
                    <span v-if="!inst.is_active" class="badge-inactive">Dibekukan</span>
                    <span v-else-if="inst.admins_count === 0" class="badge-warn">Tanpa admin</span>
                    <span v-else-if="inst.is_inactive" class="badge-warn">Tidak aktif</span>
                    <span v-else class="badge-active">Aktif</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="filteredInstitutions.length === 0" class="empty-hint">Tidak ada institusi.</p>
          </div>
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
const inactiveDays = ref(30)
const search = ref('')
const summary = ref({
  active_institutions: 0,
  inactive_activity_count: 0,
  no_admin_count: 0,
  total_students: 0,
  total_teachers: 0
})
const modules = ref([])
const institutions = ref([])

const filteredInstitutions = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return institutions.value
  return institutions.value.filter((i) =>
    (i.name || '').toLowerCase().includes(q) ||
    (i.npsn || '').toLowerCase().includes(q)
  )
})

const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n || 0)

const formatRelative = (iso) => {
  if (!iso) return 'Belum ada'
  const date = new Date(iso)
  const mins = Math.floor((Date.now() - date.getTime()) / 60000)
  if (mins < 60) return `${Math.max(mins, 0)} mnt lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam lalu`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days} hari lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await superAdminPlatformApi.getAdoption({ inactive_days: inactiveDays.value })
    const data = res.data?.data || {}
    summary.value = data.summary || summary.value
    modules.value = data.modules || []
    institutions.value = data.institutions || []
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat monitoring')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.adoption-page { width: 100%; }
.page-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.page-header h2 { margin: 0 0 4px; font-size: 24px; color: #0f172a; }
.page-header p { margin: 0; color: #64748b; font-size: 14px; }
.header-actions { display: flex; gap: 10px; align-items: center; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.stat-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
}
.stat-card.warn { border-color: #fcd34d; }
.stat-card.danger { border-color: #fca5a5; }
.stat-label { display: block; font-size: 12px; color: #64748b; margin-bottom: 6px; }
.stat-card strong { font-size: 22px; color: #0f172a; }
.panel {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
}
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.panel-header h3 { margin: 0; font-size: 16px; color: #0f172a; }
.module-row {
  display: grid;
  grid-template-columns: minmax(140px, 1.2fr) 1fr 56px;
  gap: 12px;
  align-items: center;
  padding: 10px 0;
  border-top: 1px solid #f1f5f9;
}
.module-meta { display: flex; flex-direction: column; gap: 2px; }
.module-meta span { font-size: 12px; color: #94a3b8; }
.module-bar-wrap {
  height: 8px;
  background: #f1f5f9;
  border-radius: 999px;
  overflow: hidden;
}
.module-bar {
  height: 100%;
  background: #059669;
  border-radius: 999px;
}
.module-pct { text-align: right; font-size: 13px; font-weight: 600; color: #334155; }
.search-input, .filter-select {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
}
.table-container { overflow: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
  font-size: 14px;
}
.data-table th {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
}
.sub { font-size: 12px; color: #94a3b8; }
.row-warn td { background: #fffbeb; }
.badge-active, .badge-inactive, .badge-warn {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}
.badge-active { background: rgba(16,185,129,.12); color: #059669; }
.badge-inactive { background: rgba(239,68,68,.12); color: #dc2626; }
.badge-warn { background: rgba(245,158,11,.15); color: #b45309; }
.empty-hint { color: #94a3b8; font-size: 14px; margin: 8px 0 0; }
.btn-secondary {
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
@media (max-width: 700px) {
  .module-row { grid-template-columns: 1fr; }
  .page-header { flex-direction: column; align-items: stretch; }
  .header-actions { width: 100%; flex-direction: column; }
  .header-actions > * { width: 100%; }
  .panel-header { flex-direction: column; align-items: stretch; }
  .search-input, .filter-select { width: 100%; }
  .stats-grid { grid-template-columns: 1fr; }
  .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .data-table { min-width: 640px; }
}
@media (max-width: 480px) {
  .page-header h2 { font-size: 1.25rem; }
  .panel { padding: 14px; }
}
</style>
