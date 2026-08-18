<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <p class="sp-subtitle">Riwayat kehadiran berdasarkan jurnal mengajar</p>
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

      <div v-else-if="!attendances.length" class="sp-empty">
        <h3 class="sp-empty-title">Belum ada absensi</h3>
        <p class="sp-empty-desc">Belum ada data absensi untuk filter yang dipilih.</p>
      </div>

      <div v-else class="sp-panel attendance-panel">
        <div class="sp-table-wrap sp-table-desktop">
          <table class="sp-table">
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
                  <span class="sp-badge" :class="'sp-badge--' + (row.status || 'hadir')">
                    {{ row.status_label || row.status || 'Hadir' }}
                  </span>
                </td>
                <td>{{ row.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sp-mobile-cards">
          <article v-for="row in attendances" :key="'m-' + row.id" class="sp-mobile-card">
            <div class="sp-mobile-card-title">{{ row.subject_name || 'Absensi' }}</div>
            <div class="sp-mobile-card-row"><span>Tanggal</span><strong>{{ formatDate(row.date) }}</strong></div>
            <div class="sp-mobile-card-row"><span>Guru</span><strong>{{ row.teacher_name || '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>Jam ke</span><strong>{{ row.period ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row">
              <span>Status</span>
              <strong>
                <span class="sp-badge" :class="'sp-badge--' + (row.status || 'hadir')">
                  {{ row.status_label || row.status || 'Hadir' }}
                </span>
              </strong>
            </div>
          </article>
        </div>
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
.attendance-panel {
  padding: 0;
  overflow: hidden;
}

.sp-table-wrap {
  border: none;
}
</style>
