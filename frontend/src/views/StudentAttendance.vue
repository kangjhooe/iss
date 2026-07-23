<template>
  <Layout>
    <div class="page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="alert alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah. Silakan hubungi operator sekolah atau admin.
        <router-link to="/student/dashboard" class="alert-link">← Kembali ke Dashboard</router-link>
      </div>

      <div class="page-header">
        <div class="page-header-row">
          <div>
            <h1>Absensi Saya</h1>
            <p class="page-subtitle">Riwayat kehadiran berdasarkan jurnal mengajar</p>
          </div>
          <button
            type="button"
            class="btn-export"
            :disabled="exporting || loading || !attendances.length"
            @click="exportPdf"
          >
            {{ exporting ? 'Mengekspor...' : 'Cetak PDF' }}
          </button>
        </div>
      </div>

      <div class="filters">
        <div class="filters-row">
          <div class="filter-group">
            <label for="semester">Semester</label>
            <select id="semester" v-model="filters.semester_id" class="filter-input" @change="loadAttendances">
              <option value="">Semua semester</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="date_from">Dari tanggal</label>
            <input
              id="date_from"
              v-model="filters.date_from"
              type="date"
              class="filter-input"
              @change="loadAttendances"
            />
          </div>
          <div class="filter-group">
            <label for="date_to">Sampai tanggal</label>
            <input
              id="date_to"
              v-model="filters.date_to"
              type="date"
              class="filter-input"
              @change="loadAttendances"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat riwayat absensi...</p>
      </div>

      <div v-else-if="loadError" class="empty-state">
        <p>Gagal memuat data absensi. Silakan coba lagi atau kembali ke dashboard.</p>
        <button type="button" class="btn-retry" @click="loadAttendances">Coba lagi</button>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else-if="!attendances.length" class="empty-state">
        <p>Belum ada data absensi untuk filter yang dipilih.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Semester</th>
                <th>Kelas</th>
                <th>Mata Pelajaran</th>
                <th>Guru</th>
                <th>Jam ke</th>
                <th>Status</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in attendances" :key="row.id">
                <td>{{ formatDate(row.date) }}</td>
                <td>{{ row.semester_name || '-' }}</td>
                <td>{{ row.class_name || '-' }}</td>
                <td>{{ row.subject_name || '-' }}</td>
                <td>{{ row.teacher_name || '-' }}</td>
                <td>{{ row.period ?? '-' }}</td>
                <td>
                  <span class="badge" :class="'badge-' + (row.status || 'hadir')">
                    {{ row.status_label || row.status || 'Hadir' }}
                  </span>
                </td>
                <td>{{ row.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { semesterApi } from '@/api/semester'
import { studentAttendanceApi } from '@/api/attendance'

const authStore = useAuthStore()
const toast = useToast()

const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(false)
const loadError = ref(false)
const exporting = ref(false)
const attendances = ref([])
const semesters = ref([])

const filters = ref({
  semester_id: '',
  date_from: '',
  date_to: ''
})

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function cleanParams() {
  const params = {}
  if (filters.value.semester_id) params.semester_id = filters.value.semester_id
  if (filters.value.date_from) params.date_from = filters.value.date_from
  if (filters.value.date_to) params.date_to = filters.value.date_to
  return params
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    const list = res.data?.data ?? res.data ?? []
    semesters.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    semesters.value = []
  }
}

async function loadAttendances() {
  if (!studentId.value) {
    loading.value = false
    return
  }
  loading.value = true
  try {
    loadError.value = false
    const res = await studentAttendanceApi.getMy(cleanParams())
    const list = res.data?.data ?? res.data ?? []
    attendances.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    loadError.value = true
    attendances.value = []
  } finally {
    loading.value = false
  }
}

async function exportPdf() {
  exporting.value = true
  try {
    const res = await studentAttendanceApi.exportMy(cleanParams())
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `Riwayat_Absensi_${new Date().toISOString().slice(0, 10)}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Riwayat absensi PDF berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor', e.formattedMessage || 'Riwayat absensi tidak dapat diekspor.')
  } finally {
    exporting.value = false
  }
}

onMounted(async () => {
  await loadSemesters()
  await loadAttendances()
})
</script>

<style scoped>
.page {
  max-width: 100%;
  padding: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.page-header {
  margin-bottom: 20px;
}

.page-header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.btn-export {
  padding: 0.45rem 0.9rem;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filters {
  margin-bottom: 16px;
}

.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 16px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-group label {
  font-size: 13px;
  color: #64748b;
}

.filter-input {
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  font-size: 14px;
  color: #0f172a;
  min-width: 170px;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 40px 24px;
  color: #64748b;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.table-wrap {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px;
}

.table-scroll {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  min-width: 720px;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
  font-size: 13px;
}

.data-table th {
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.badge-hadir {
  background: #dcfce7;
  color: #15803d;
}

.badge-alpha {
  background: #fee2e2;
  color: #b91c1c;
}

.badge-izin {
  background: #e0f2fe;
  color: #0369a1;
}

.badge-sakit {
  background: #fef3c7;
  color: #b45309;
}

.badge-dinas_luar {
  background: #e5e7eb;
  color: #374151;
}

.btn-retry {
  margin-top: 12px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid #0f172a;
  background: #0f172a;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-retry:hover {
  background: #111827;
}

.back-link {
  display: inline-block;
  margin-top: 16px;
  color: #059669;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
}

.back-link:hover {
  text-decoration: underline;
  color: #047857;
}

.alert {
  padding: 14px 18px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 14px;
  line-height: 1.5;
}

.alert-warning {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
}

.alert-link {
  display: inline-block;
  margin-top: 10px;
  color: #b45309;
  font-weight: 600;
  text-decoration: none;
}

.alert-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .filter-input {
    min-width: 0;
    width: 100%;
  }

  .data-table {
    min-width: 0;
  }
}
</style>

