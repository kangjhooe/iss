<template>
  <Layout>
    <div class="exam-list-page">
      <header class="page-header">
        <div class="header-content">
          <div>
            <h2 class="page-title">Daftar Ujian</h2>
            <p class="page-subtitle">Kelola ujian dan sesi</p>
          </div>
          <div class="header-actions">
            <router-link to="/ujian-online/exams/buat" class="btn-primary btn-compact btn-add">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
              </svg>
              <span>Buat Ujian</span>
            </router-link>
          </div>
        </div>
      </header>

      <div v-if="loading" class="loading-wrap content-card">
        <p class="loading-text">Memuat...</p>
      </div>
      <div v-else-if="exams.length === 0" class="empty-state content-card">
        <h3>Belum ada ujian</h3>
        <p>Buat ujian baru, lalu pilih soal dari bank (boleh lintas tingkat, selama mapel sama).</p>
        <router-link to="/ujian-online/exams/buat" class="btn-primary btn-compact btn-add">Buat Ujian</router-link>
      </div>
      <div v-else class="table-wrap content-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama Ujian</th>
                <th>Jumlah Peserta</th>
                <th>Durasi</th>
                <th>Sesi</th>
                <th class="col-actions">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in exams" :key="e.id">
                <td><strong>{{ e.name }}</strong></td>
                <td>{{ e.participants_count ?? 0 }}</td>
                <td>{{ e.duration_minutes != null ? e.duration_minutes + ' menit' : '–' }}</td>
                <td>{{ e.sessions?.length ?? 0 }}</td>
                <td class="col-actions">
                  <div class="action-btns">
                    <router-link :to="`/ujian-online/exams/${encodeURIComponent(e.code || String(e.id))}`" class="btn-action btn-detail" title="Detail" aria-label="Detail">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </router-link>
                    <router-link :to="`/ujian-online/exams/${encodeURIComponent(e.code || String(e.id))}/edit`" class="btn-action btn-edit" title="Edit" aria-label="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50001C18.8978 2.10219 19.4374 1.87869 20 1.87869C20.5626 1.87869 21.1022 2.10219 21.5 2.50001C21.8978 2.89784 22.1213 3.4374 22.1213 4.00001C22.1213 4.56262 21.8978 5.10219 21.5 5.50001L12 15L8 16L9 12L18.5 2.50001Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </router-link>
                    <button type="button" @click="confirmDelete(e)" class="btn-action btn-delete" title="Hapus" aria-label="Hapus">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="pagination" class="pagination">
          <button type="button" :disabled="!pagination.prev" @click="fetchExams(pagination.current_page - 1)">Sebelumnya</button>
          <span>Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button type="button" :disabled="!pagination.next" @click="fetchExams(pagination.current_page + 1)">Selanjutnya</button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'

const { toast } = useToast()
const route = useRoute()
const exams = ref([])
const loading = ref(true)
const pagination = ref(null)

async function fetchExams(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 15 }
    if (route.query.subject_id) params.subject_id = String(route.query.subject_id)
    const res = await examApi.listExams(params)
    const data = res.data
    exams.value = data?.data ?? data ?? []
    pagination.value = data?.meta ? { ...data.meta, ...data.links } : null
  } catch (e) {
    const msg = e.response?.data?.message || e.formattedMessage || 'Daftar ujian tidak dapat dimuat. Periksa koneksi dan coba lagi.'
    toast.error('Gagal memuat daftar ujian', msg)
  } finally {
    loading.value = false
  }
}

async function confirmDelete(exam) {
  if (!confirm('Hapus ujian ini? Semua sesi dan data terkait akan ikut terhapus.')) return
  try {
    await examApi.deleteExam(exam.id)
    toast.success('Berhasil', 'Ujian telah dihapus.')
    fetchExams()
  } catch (e) {
    const msg = e.response?.data?.message || e.formattedMessage || 'Ujian tidak dapat dihapus. Coba lagi.'
    toast.error('Gagal menghapus ujian', msg)
  }
}

onMounted(() => fetchExams())
</script>

<style scoped>
@import '@/assets/module-page.css';

.exam-list-page {
  padding: 0;
}

.content-card {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

.loading-wrap {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-text {
  color: #64748b;
  margin: 0;
}

.table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table th,
.data-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  white-space: nowrap;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table .col-actions {
  width: 1%;
  white-space: nowrap;
}

.action-btns {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 8px;
  align-items: center;
}

.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: 8px;
  text-decoration: none;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
}

.btn-detail {
  color: #059669;
  border-color: #059669;
  background: rgba(5, 150, 105, 0.08);
}

.btn-detail:hover {
  background: rgba(5, 150, 105, 0.15);
}

.btn-edit {
  color: #0369a1;
  border-color: #0ea5e9;
  background: rgba(14, 165, 233, 0.08);
}

.btn-edit:hover {
  background: rgba(14, 165, 233, 0.15);
}

.btn-delete {
  color: #b91c1c;
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.06);
}

.btn-delete:hover {
  background: rgba(220, 38, 38, 0.12);
}

.pagination {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.pagination span {
  color: #64748b;
  font-size: 13px;
}

.pagination button {
  padding: 6px 12px;
  font-size: 13px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #334155;
  cursor: pointer;
}

.pagination button:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  color: #1e293b;
}

.empty-state p {
  margin: 0 0 16px 0;
  color: #64748b;
  font-size: 14px;
}

.btn-secondary {
  border: 1px solid #cbd5e1;
  color: #475569;
  background: #fff;
}

.btn-secondary:hover {
  background: #f1f5f9;
}

.exam-list-page .header-actions .btn-primary,
.exam-list-page .empty-state .btn-primary {
  background: #059669;
  color: #fff;
  border-color: #059669;
  text-decoration: none;
}

.exam-list-page .header-actions .btn-primary:hover,
.exam-list-page .empty-state .btn-primary:hover {
  background: #047857;
  color: #fff;
  border-color: #047857;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions .btn-add,
  .header-actions .btn-primary {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .content-card {
    padding: 12px;
  }

  .data-table th,
  .data-table td {
    padding: 10px 12px;
    font-size: 13px;
  }

  .action-btns {
    gap: 6px;
  }

  /* Touch-friendly: min 44px tap target on mobile */
  .btn-action {
    min-width: 44px;
    min-height: 44px;
    width: auto;
    height: auto;
    padding: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .btn-action svg {
    width: 16px;
    height: 16px;
  }
}
</style>
