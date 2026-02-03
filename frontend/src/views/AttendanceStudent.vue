<template>
  <Layout>
    <div class="attendance-student-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Absensi Siswa</h1>
            <p class="page-subtitle">Kehadiran siswa per jam pelajaran (per sesi jurnal mengajar)</p>
          </div>
        </div>
      </div>

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

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="6" />
      </div>

      <div v-else-if="journals.length === 0" class="empty-state">
        <h3 class="empty-title">Tidak ada jurnal mengajar</h3>
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
              <td>{{ j.school_class?.name }}</td>
              <td>{{ j.subject?.name }}</td>
              <td>{{ j.employee?.name }}</td>
              <td>{{ j.period }}</td>
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

const toast = useToast()

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
    const res = await studentAttendanceApi.getByTeachingJournal(j.id)
    attendanceRows.value = (res.data.data || []).map((row) => ({
      student_id: row.student_id,
      status: row.status || 'hadir',
      notes: row.notes || '',
      student: row.student,
    }))
  } catch (e) {
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
    await studentAttendanceApi.saveForTeachingJournal(selectedJournal.value.id, attendances)
    toast.success('Absensi siswa berhasil disimpan')
    showAttendanceModal.value = false
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
}
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.25rem 0; }
.page-subtitle { color: #64748b; margin: 0; font-size: 0.9rem; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; align-items: center; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; min-width: 140px; }
.loading-wrap { margin: 1rem 0; }
.empty-state { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 12px; }
.empty-title { font-size: 1.25rem; margin: 0 0 0.5rem 0; }
.empty-desc { color: #64748b; margin: 0; }
.table-container { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 600; background: #f8fafc; }
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
.btn-primary { padding: 0.5rem 1rem; border: none; border-radius: 8px; background: #0ea5e9; color: #fff; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
