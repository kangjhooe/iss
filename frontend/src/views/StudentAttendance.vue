<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <p class="sp-subtitle">Rekap kehadiran dari jurnal mengajar guru</p>
        <div class="sp-actions">
          <button
            type="button"
            class="sp-btn sp-btn--primary"
            :disabled="exporting || loading || !attendances.length"
            @click="exportPdf"
          >
            {{ exporting ? 'Mengekspor...' : 'Cetak PDF' }}
          </button>
        </div>
      </div>

      <div class="sp-filters">
        <div class="sp-filter">
          <label for="semester">Semester</label>
          <select id="semester" v-model="filters.semester_id" @change="loadAttendances">
            <option value="">Semua semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
        <div class="sp-filter">
          <label for="date_from">Dari tanggal</label>
          <input id="date_from" v-model="filters.date_from" type="date" @change="loadAttendances" />
        </div>
        <div class="sp-filter">
          <label for="date_to">Sampai tanggal</label>
          <input id="date_to" v-model="filters.date_to" type="date" @change="loadAttendances" />
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat riwayat absensi...</p>
      </div>

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat data</h3>
        <p class="sp-empty-desc">Silakan coba lagi.</p>
        <div class="sp-empty-actions">
          <button type="button" class="sp-btn sp-btn--soft" @click="loadAttendances">Coba lagi</button>
        </div>
      </div>

      <template v-else>
        <div class="sp-stats sp-stats--4">
          <div class="sp-stat sp-stat--ok">
            <div>
              <span class="sp-stat-label">Hadir</span>
              <span class="sp-stat-value">{{ statusCounts.hadir }}</span>
            </div>
          </div>
          <div class="sp-stat">
            <div>
              <span class="sp-stat-label">Izin</span>
              <span class="sp-stat-value">{{ statusCounts.izin }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--warn">
            <div>
              <span class="sp-stat-label">Sakit</span>
              <span class="sp-stat-value">{{ statusCounts.sakit }}</span>
            </div>
          </div>
          <div class="sp-stat" :class="{ 'sp-stat--warn': statusCounts.alpha }">
            <div>
              <span class="sp-stat-label">Alpha</span>
              <span class="sp-stat-value">{{ statusCounts.alpha }}</span>
            </div>
          </div>
        </div>

        <div v-if="!dayGroups.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada absensi</h3>
          <p class="sp-empty-desc">Belum ada data absensi untuk filter yang dipilih.</p>
        </div>

        <div v-else class="sp-slot-list">
          <article v-for="group in dayGroups" :key="group.date" class="sp-day-group">
            <div class="sp-day-head">
              <span class="sp-day-title">{{ formatDate(group.date) }}</span>
              <span class="sp-badge" :class="'sp-badge--' + group.summaryStatus">{{ group.summaryLabel }}</span>
              <span class="sp-list-meta">{{ group.rows.length }} jam</span>
            </div>
            <div class="sp-day-body">
              <div v-for="row in group.rows" :key="row.id" class="sp-day-row">
                <div>
                  <strong>{{ row.subject_name || 'Pelajaran' }}</strong>
                  <div class="sp-list-meta">
                    Jam ke {{ row.period ?? '—' }}
                    <template v-if="row.teacher_name"> · {{ row.teacher_name }}</template>
                  </div>
                  <div v-if="row.notes" class="sp-list-meta">{{ row.notes }}</div>
                </div>
                <span class="sp-badge" :class="'sp-badge--' + (row.status || 'hadir')">
                  {{ row.status_label || row.status || 'Hadir' }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { countStatuses, ATTENDANCE_LABELS } from '@/composables/useChart'
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

const STATUS_RANK = { alpha: 4, sakit: 3, izin: 2, dinas_luar: 1, hadir: 0 }

const statusCounts = computed(() => countStatuses(attendances.value))

const dayGroups = computed(() => {
  const map = new Map()
  for (const row of attendances.value) {
    const key = String(row.date || '').slice(0, 10) || 'tanpa-tanggal'
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(row)
  }
  return [...map.entries()]
    .sort((a, b) => String(b[0]).localeCompare(String(a[0])))
    .map(([date, rows]) => {
      const sorted = [...rows].sort((a, b) => (a.period || 0) - (b.period || 0))
      let summaryStatus = 'hadir'
      let rank = -1
      for (const row of sorted) {
        const status = String(row.status || 'hadir').toLowerCase()
        const next = STATUS_RANK[status] ?? 0
        if (next > rank) {
          rank = next
          summaryStatus = status
        }
      }
      return {
        date,
        rows: sorted,
        summaryStatus,
        summaryLabel: ATTENDANCE_LABELS[summaryStatus] || summaryStatus,
      }
    })
})

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })
}

function cleanParams() {
  const params = {}
  if (filters.value.semester_id) params.semester_id = filters.value.semester_id
  if (filters.value.date_from) params.date_from = filters.value.date_from
  if (filters.value.date_to) params.date_to = filters.value.date_to
  return params
}

async function loadSemesters() {
  let active = null
  try {
    const res = await semesterApi.getActive()
    const data = res.data?.data ?? res.data
    active = Array.isArray(data) ? data[0] : data
  } catch {
    active = null
  }
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    const list = res.data?.data ?? res.data ?? []
    semesters.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    semesters.value = active ? [active] : []
  }
  if (active?.id) filters.value.semester_id = active.id
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
