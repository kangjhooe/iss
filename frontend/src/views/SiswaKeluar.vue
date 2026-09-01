<template>    <div class="keluar-page">
      <div class="list-tabs">
        <router-link to="/student" class="tab-btn">Daftar Siswa</router-link>
        <span class="tab-btn active">Siswa Keluar</span>
        <router-link to="/student?trashed=1" class="tab-btn">Kotak Sampah</router-link>
      </div>

      <div class="page-header">
        <div class="header-text">
          <h1 class="page-title">Siswa Keluar</h1>
          <p class="page-subtitle">Siswa pindah, drop out, atau tidak aktif. Alumni tetap di menu Alumni.</p>
        </div>
      </div>

      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          @input="loadStudents(1)"
          placeholder="Cari nama, NIK, NIS, NISN..."
          class="search-input"
        />
        <select v-model="filters.status" @change="loadStudents(1)" class="filter-select">
          <option value="">Semua (kecuali lulus)</option>
          <option value="Pindah">Pindah</option>
          <option value="Drop Out">Drop Out</option>
          <option value="Tidak Aktif">Tidak Aktif</option>
        </select>
      </div>

      <div v-if="error && !loading" class="error-state">
        <p>{{ error }}</p>
        <button type="button" class="btn-primary" @click="loadStudents()">Coba lagi</button>
      </div>

      <div v-else-if="loading" class="loading-wrap">
        <StudentTableSkeleton />
      </div>

      <div v-else class="content-wrapper">
        <StudentTable
          archive-mode
          :students="students"
          :start-index="(pagination.current_page - 1) * pagination.per_page"
          :get-status-class="getStatusClass"
          :sort-by="filters.sort_by"
          :sort-dir="filters.sort_dir"
          @sort="setSort"
          @view="openBukuInduk"
          @reactivate="reactivateStudent"
        >
          <template #empty>
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 8L21 12L17 16M7 16L3 12L7 8M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Belum ada siswa keluar</h3>
            <p>Siswa pindah, drop out, atau tidak aktif akan muncul di sini. Tandai dari Data Siswa, atau lewat Mutasi.</p>
            <router-link to="/student" class="btn-primary btn-compact">Ke Data Siswa</router-link>
          </template>
        </StudentTable>
        <PaginationBar
          v-if="students.length > 0"
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="siswa"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :confirm-text="confirmDialog.confirmText"
      :cancel-text="confirmDialog.cancelText"
      :loading-text="confirmDialog.loadingText"
      :confirm-variant="confirmDialog.confirmVariant"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    /></template>

<script setup>
import { onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import PaginationBar from '@/components/PaginationBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import StudentTable from '@/components/student/StudentTable.vue'
import StudentTableSkeleton from '@/components/StudentTableSkeleton.vue'
import { useStudentList } from '@/composables/useStudentList'
import { studentApi } from '@/api/student'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading } = useConfirmDelete()

const {
  students,
  loading,
  error,
  filters,
  pagination,
  loadStudents,
  setSort,
  goToPage,
  changePerPage,
  getStatusClass,
} = useStudentList({ inactive: true })

function openBukuInduk(student) {
  router.push({ name: 'BukuInduk', params: { id: student.id }, query: { from: 'siswa-keluar' } })
}

async function reactivateStudent(student) {
  const confirmed = await showConfirm({
    title: 'Aktifkan kembali',
    message: `Kembalikan ${student.name} ke daftar siswa aktif?`,
    warning: 'Kelas dan NIS mungkin perlu diatur ulang setelah diaktifkan.',
    confirmText: 'Ya, aktifkan',
    loadingText: 'Memproses...',
    confirmVariant: 'primary',
  })
  if (!confirmed) return

  setLoading(true)
  try {
    await studentApi.update(student.id, { status: 'Aktif' })
    toast.success('Berhasil', `${student.name} kembali berstatus Aktif.`)
    await loadStudents()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.formattedMessage || 'Gagal mengaktifkan siswa')
  } finally {
    setLoading(false)
  }
}

onMounted(() => {
  loadStudents(1)
})

watch(() => authStore.activeInstitutionId, () => {
  loadStudents(1)
})
</script>

<style scoped>
.keluar-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.list-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 16px;
}

.list-tabs .tab-btn {
  padding: 10px 20px;
  border: 1px solid #d1fae5;
  background: #fff;
  color: #047857;
  border-radius: 10px;
  font-weight: 500;
  font-size: 14px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.list-tabs .tab-btn:hover {
  background: #ecfdf5;
}

.list-tabs .tab-btn.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: #047857;
}

.page-header {
  margin-bottom: 16px;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.filters-inline {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: 8px;
  padding: 8px 10px;
  margin-bottom: 16px;
  overflow-x: auto;
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.search-input,
.filter-select {
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 13px;
  background: #f8fafc;
}

.search-input {
  flex: 1 1 auto;
  min-width: 160px;
}

.filter-select {
  flex: 0 0 auto;
  min-width: 180px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: white;
}

.loading-wrap,
.content-wrapper,
.error-state {
  background: white;
  border-radius: 16px;
  padding: 16px;
  border: 1px solid #e2e8f0;
}

.error-state {
  text-align: center;
  color: #64748b;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  text-decoration: none;
}

.btn-compact {
  margin-top: 8px;
}
</style>
