<template>    <div class="sp-page">
      <div class="sp-page-header">
        <div>
          <router-link to="/parent/dashboard" class="back-link">← Dashboard</router-link>
          <h1>{{ pageTitle }}</h1>
          <p v-if="studentName" class="page-meta">{{ studentName }}</p>
        </div>
      </div>

      <div v-if="loading" class="sp-loading"><p>Memuat...</p></div>
      <div v-else-if="error" class="sp-alert sp-alert-warning">{{ error }}</div>

      <!-- Schedule -->
      <template v-else-if="section === 'jadwal'">
        <div v-if="!rows.length" class="sp-empty"><p class="sp-empty-desc">Belum ada jadwal.</p></div>
        <div v-else class="sp-table-wrap">
          <table class="sp-table">
            <thead>
              <tr><th>Hari</th><th>Jam</th><th>Mapel</th><th>Guru</th><th>Ruang</th></tr>
            </thead>
            <tbody>
              <tr v-for="r in rows" :key="r.id">
                <td>{{ dayLabel(r.day_of_week) }}</td>
                <td>{{ fmtTime(r.start_time) }}–{{ fmtTime(r.end_time) }}</td>
                <td>{{ r.subject?.name || '—' }}</td>
                <td>{{ r.teacher?.name || '—' }}</td>
                <td>{{ r.room?.name || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Grades -->
      <template v-else-if="section === 'nilai'">
        <div v-if="!rows.length" class="sp-empty"><p class="sp-empty-desc">Belum ada nilai.</p></div>
        <template v-else>
          <div class="chart-solo">
            <AppChart title="Nilai vs KKM" type="bar" :chart-data="gradesChart" :options="gradesChartOptions" />
          </div>
          <div class="sp-table-wrap">
            <table class="sp-table">
              <thead>
                <tr><th>Mapel</th><th>Nilai</th><th>KKM</th><th>Predikat</th></tr>
              </thead>
              <tbody>
                <tr v-for="(g, i) in rows" :key="g.id || g.subject_id || i">
                  <td>{{ g.subject?.name || g.subject_name || '—' }}</td>
                  <td><strong>{{ g.nilai_akhir ?? g.value ?? '—' }}</strong></td>
                  <td>{{ g.kkm ?? '—' }}</td>
                  <td>{{ g.predicate || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </template>

      <!-- Attendance -->
      <template v-else-if="section === 'absensi'">
        <div v-if="summary" class="sp-stats" style="margin-bottom: 16px">
          <div class="sp-stat"><span class="sp-stat-label">Hadir</span><span class="sp-stat-value">{{ summary.hadir ?? 0 }}</span></div>
          <div class="sp-stat"><span class="sp-stat-label">Izin</span><span class="sp-stat-value">{{ summary.izin ?? 0 }}</span></div>
          <div class="sp-stat"><span class="sp-stat-label">Sakit</span><span class="sp-stat-value">{{ summary.sakit ?? 0 }}</span></div>
          <div class="sp-stat"><span class="sp-stat-label">Alpha</span><span class="sp-stat-value">{{ summary.alpha ?? 0 }}</span></div>
        </div>
        <div class="chart-solo" v-if="attendanceChart">
          <AppChart title="Komposisi kehadiran" type="doughnut" :chart-data="attendanceChart" />
        </div>
        <div v-if="!rows.length" class="sp-empty"><p class="sp-empty-desc">Belum ada data absensi.</p></div>
        <div v-else class="sp-table-wrap">
          <table class="sp-table">
            <thead>
              <tr><th>Tanggal</th><th>Mapel</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr v-for="(a, i) in rows" :key="a.id || i">
                <td>{{ formatDate(a.date || a.attendance_date) }}</td>
                <td>{{ a.subject?.name || a.subject_name || '—' }}</td>
                <td>{{ a.status || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Violations -->
      <template v-else-if="section === 'pelanggaran'">
        <div v-if="!rows.length" class="sp-empty"><p class="sp-empty-desc">Tidak ada catatan pelanggaran.</p></div>
        <div v-else class="sp-list">
          <div v-for="v in rows" :key="v.id" class="sp-list-item sp-list-item--danger">
            <span class="sp-list-title">{{ v.violation_type?.name || v.description || '—' }}</span>
            <span class="sp-list-meta">{{ formatDate(v.violation_date || v.date) }}</span>
          </div>
        </div>
      </template>
    </div></template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppChart from '@/components/AppChart.vue'
import { parentApi } from '@/api/parent'
import { doughnutFromCounts, countStatuses, chartOptionsBar } from '@/composables/useChart'
import '@/assets/student-portal.css'

const props = defineProps({
  section: { type: String, required: true },
})

const route = useRoute()
const studentId = computed(() => Number(route.params.studentId))
const loading = ref(true)
const error = ref('')
const rows = ref([])
const summary = ref(null)
const studentName = ref('')

const pageTitle = computed(() => {
  const map = { jadwal: 'Jadwal', nilai: 'Nilai', absensi: 'Absensi', pelanggaran: 'Pelanggaran' }
  return map[props.section] || 'Detail'
})

const attendanceChart = computed(() => {
  const fromSummary = doughnutFromCounts(summary.value)
  if (fromSummary) return fromSummary
  return doughnutFromCounts(countStatuses(rows.value))
})

const gradesChart = computed(() => {
  const list = (rows.value || []).filter((g) => g.nilai_akhir != null || g.value != null)
  if (!list.length) return null
  return {
    labels: list.map((g) => g.subject?.name || g.subject_name || '—'),
    datasets: [
      {
        label: 'Nilai',
        data: list.map((g) => Number(g.nilai_akhir ?? g.value ?? 0)),
        backgroundColor: '#059669',
        borderRadius: 4,
        maxBarThickness: 28,
      },
      {
        label: 'KKM',
        data: list.map((g) => Number(g.kkm ?? 0)),
        backgroundColor: '#94a3b8',
        borderRadius: 4,
        maxBarThickness: 28,
      },
    ],
  }
})
const gradesChartOptions = { ...chartOptionsBar, plugins: { legend: { position: 'bottom' } } }

const DAYS = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu' }
function dayLabel(d) { return DAYS[d] || d }
function fmtTime(t) {
  if (!t) return '—'
  if (typeof t === 'string') return t.slice(0, 5)
  return t
}
function formatDate(d) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return d
  }
}

async function load() {
  if (!studentId.value) return
  loading.value = true
  error.value = ''
  rows.value = []
  summary.value = null
  try {
    let res
    if (props.section === 'jadwal') res = await parentApi.schedule(studentId.value)
    else if (props.section === 'nilai') res = await parentApi.grades(studentId.value)
    else if (props.section === 'absensi') res = await parentApi.attendance(studentId.value)
    else if (props.section === 'pelanggaran') res = await parentApi.violations(studentId.value)
    else return

    const data = res.data?.data ?? res.data ?? []
    rows.value = Array.isArray(data) ? data : (data?.data ?? [])
    studentName.value = res.data?.meta?.student_name || ''
    summary.value = res.data?.meta?.summary || null
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

watch([studentId, () => props.section], load, { immediate: true })
</script>

<style scoped>
.back-link {
  display: inline-block;
  margin-bottom: 6px;
  color: #059669;
  font-size: 0.85rem;
  text-decoration: none;
  font-weight: 600;
}
.chart-solo {
  max-width: 520px;
  margin-bottom: 16px;
}
</style>
