<template>
  <Layout>
    <div class="attendance-student-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <select v-model="filters.semester_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <select v-model="filters.class_id" @change="loadJournals" class="filter-select">
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <input v-model="filters.date_from" type="date" class="filter-select" @change="loadJournals" />
          <input v-model="filters.date_to" type="date" class="filter-select" @change="loadJournals" />
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="6" />
      </div>

      <div v-else-if="journals.length === 0" class="empty-state">
        <h3 class="empty-title">Belum ada jurnal mengajar</h3>
        <p class="empty-desc">Pilih filter atau buat jurnal mengajar terlebih dahulu untuk mengisi absensi siswa.</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Kelas</th>
              <th>Mapel</th>
              <th>Guru</th>
              <th>Jam ke</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="j in journals" :key="j.id">
              <td>{{ formatDate(j.journal_date) }}</td>
              <td>{{ displayValue(j.school_class?.name) }}</td>
              <td>{{ displayValue(j.subject?.name) }}</td>
              <td>{{ displayValue(j.employee?.name) }}</td>
              <td>{{ j.period ?? 'Belum ada data' }}</td>
              <td>
                <button @click="openAttendanceModal(j)" class="btn-primary btn-compact">Isi Absensi</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
          </span>
          <div class="pagination-buttons">
            <button type="button" class="btn-page" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">Sebelumnya</button>
            <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" class="btn-page" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- Modal: Isi Absensi per sesi -->
      <div v-if="showAttendanceModal" class="modal-overlay" @click="showAttendanceModal = false">
        <div class="modal-content form-modal modal-wide" @click.stop>
          <div class="modal-header">
            <h3>Absensi Siswa — {{ selectedJournal ? `${formatDate(selectedJournal.journal_date)} · ${selectedJournal.school_class?.name} · ${selectedJournal.subject?.name} (Jam ke-${selectedJournal.period})` : '' }}</h3>
            <button @click="showAttendanceModal = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <div v-if="attendanceLoading" class="loading-inline">Memuat daftar siswa...</div>
            <div v-if="offlineIndicator" class="offline-indicator">
              <span>📴 Mode Offline - Data dari penyimpanan lokal</span>
            </div>
            <form v-else @submit.prevent="submitAttendance" class="attendance-form">
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>NIS / Nama</th>
                      <th>Status Kehadiran</th>
                      <th>Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in attendanceRows" :key="row.student_id">
                      <td>{{ idx + 1 }}</td>
                      <td>{{ row.student?.nis || '-' }} / {{ row.student?.name }}</td>
                      <td>
                        <select v-model="row.status" class="form-select status-select">
                          <option v-for="(label, val) in studentStatusOptions" :key="val" :value="val">{{ label }}</option>
                        </select>
                      </td>
                      <td>
                        <input v-model="row.notes" type="text" class="form-input notes-input" placeholder="Opsional" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="attendanceFormError" class="form-error">{{ attendanceFormError }}</p>
              <div class="modal-actions">
                <button type="button" @click="showAttendanceModal = false" class="btn-secondary">Batal</button>
                <button type="submit" :disabled="attendanceSaving" class="btn-primary">{{ attendanceSaving ? 'Menyimpan...' : 'Simpan Absensi' }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { useToast } from '@/composables/useToast'
import { teachingJournalApi } from '@/api/teachingJournal'
import { studentAttendanceApi } from '@/api/attendance'
import { semesterApi } from '@/api/semester'
import { classApi } from '@/api/class'
import { studentAttendanceStorage, isOnline, onNetworkStatusChange } from '@/utils/offlineStorage'
import { useOfflineSync } from '@/composables/useOfflineSync'

const toast = useToast()
const { syncPendingItems } = useOfflineSync()

const isOffline = ref(!isOnline())
const offlineIndicator = ref(false)

onNetworkStatusChange((online) => {
  isOffline.value = !online
  if (online) {
    syncPendingItems()
  }
})

const studentStatusOptions = {
  hadir: 'Hadir',
  alpha: 'Alpha',
  izin: 'Izin',
  sakit: 'Sakit',
  dinas_luar: 'Dinas Luar',
}

const filters = ref({
  semester_id: '',
  class_id: '',
  date_from: '',
  date_to: '',
})
const semesters = ref([])
const classes = ref([])
const journals = ref([])
const loading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const showAttendanceModal = ref(false)
const selectedJournal = ref(null)
const attendanceRows = ref([])
const attendanceLoading = ref(false)
const attendanceSaving = ref(false)
const attendanceFormError = ref('')

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function formatDate(d) {
  if (!d) return '-'
  const date = typeof d === 'string' ? new Date(d) : d
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadJournals() {
  loading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
      ...filters.value,
    }
    if (!params.semester_id) delete params.semester_id
    if (!params.class_id) delete params.class_id
    if (!params.date_from) delete params.date_from
    if (!params.date_to) delete params.date_to
    const res = await teachingJournalApi.getAll(params)
    journals.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal memuat jurnal')
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  pagination.value.current_page = page
  loadJournals()
}

async function openAttendanceModal(j) {
  selectedJournal.value = j
  attendanceRows.value = []
  attendanceFormError.value = ''
  showAttendanceModal.value = true
  attendanceLoading.value = true
  try {
    // Try to load from offline storage first if offline
    if (isOffline.value) {
      const offlineData = await studentAttendanceStorage.get(j.id)
      if (offlineData && offlineData.attendances) {
        // Load students from journal data
        const students = j.school_class?.students || []
        attendanceRows.value = students.map((student) => {
          const saved = offlineData.attendances.find(a => a.student_id === student.id)
          return {
            student_id: student.id,
            status: saved?.status || 'hadir',
            notes: saved?.notes || '',
            student: student,
          }
        })
        attendanceLoading.value = false
        offlineIndicator.value = true
        return
      }
    }

    const res = await studentAttendanceApi.getByTeachingJournal(j.id)
    attendanceRows.value = (res.data.data || []).map((row) => ({
      student_id: row.student_id,
      status: row.status || 'hadir',
      notes: row.notes || '',
      student: row.student,
    }))
    offlineIndicator.value = false
  } catch (e) {
    // If offline and API fails, try to load from storage
    if (isOffline.value) {
      const offlineData = await studentAttendanceStorage.get(j.id)
      if (offlineData && offlineData.attendances) {
        const students = j.school_class?.students || []
        attendanceRows.value = students.map((student) => {
          const saved = offlineData.attendances.find(a => a.student_id === student.id)
          return {
            student_id: student.id,
            status: saved?.status || 'hadir',
            notes: saved?.notes || '',
            student: student,
          }
        })
        offlineIndicator.value = true
        attendanceLoading.value = false
        return
      }
    }
    toast.error(e.formattedMessage || 'Gagal memuat daftar absensi')
    showAttendanceModal.value = false
  } finally {
    attendanceLoading.value = false
  }
}

async function submitAttendance() {
  if (!selectedJournal.value) return
  attendanceSaving.value = true
  attendanceFormError.value = ''
  try {
    const attendances = attendanceRows.value.map((row) => ({
      student_id: row.student_id,
      status: row.status,
      notes: row.notes || null,
    }))
    
    // Extract students data for offline storage
    const students = attendanceRows.value.map(row => row.student).filter(Boolean)

    if (isOffline.value || !navigator.onLine) {
      // Save to offline storage with students data
      await studentAttendanceStorage.save(selectedJournal.value.id, attendances, students)
      toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
      showAttendanceModal.value = false
    } else {
      // Try to save online
      try {
        await studentAttendanceApi.saveForTeachingJournal(selectedJournal.value.id, attendances)
        toast.success('Absensi siswa berhasil disimpan')
        showAttendanceModal.value = false
      } catch (e) {
        // If online save fails, save offline
        await studentAttendanceStorage.save(selectedJournal.value.id, attendances, students)
        toast.success('Absensi disimpan secara offline. Akan disinkronkan saat online.')
        showAttendanceModal.value = false
      }
    }
  } catch (e) {
    attendanceFormError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan absensi'
  } finally {
    attendanceSaving.value = false
  }
}

onMounted(async () => {
  try {
    const [semRes, classRes] = await Promise.all([
      semesterApi.getAll({ per_page: 200 }),
      classApi.getAll({ per_page: 200 }),
    ])
    semesters.value = semRes.data.data || []
    classes.value = classRes.data.data || []
  } catch {
    // ignore
  }
  loadJournals()
})
</script>

<style scoped>
.attendance-student-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
.toolbar .filters { margin-bottom: 0; flex: 1; min-width: 200px; }
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center; }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; min-width: 140px; transition: border-color 0.2s, box-shadow 0.2s; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.loading-wrap { width: 100%; margin: 1rem 0; }
.empty-state { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 12px; }
.empty-title { font-size: 1.25rem; margin: 0 0 0.5rem 0; }
.empty-desc { color: #64748b; margin: 0; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 600; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; }
.btn-primary.btn-compact { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.pagination-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1rem; }
.pagination-info { color: #64748b; font-size: 0.9rem; }
.pagination-buttons { display: flex; align-items: center; gap: 0.5rem; }
.btn-page { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; cursor: pointer; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-content { background: #fff; border-radius: 12px; max-height: 90vh; display: flex; flex-direction: column; min-width: 320px; }
.modal-wide { max-width: 720px; width: 100%; }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }
.modal-body { padding: 1.25rem; overflow-y: auto; }
.loading-inline { padding: 2rem; text-align: center; color: #64748b; }
.table-scroll { max-height: 50vh; overflow-y: auto; margin-bottom: 1rem; }
.status-select { min-width: 120px; padding: 0.4rem 0.5rem; }
.notes-input { width: 100%; max-width: 180px; padding: 0.4rem 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px; }
.form-error { color: #dc2626; font-size: 0.9rem; margin-bottom: 0.75rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
.btn-secondary { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; }
.btn-primary { padding: 0.5rem 1rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #fff; cursor: pointer; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.offline-indicator {
  background: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  text-align: center;
}
</style>
