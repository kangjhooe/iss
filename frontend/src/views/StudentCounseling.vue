<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <p class="sp-subtitle">Riwayat dan jadwal konseling Anda</p>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat data konseling...</p>
      </div>

      <div v-else-if="!sessions.length" class="sp-empty">
        <div class="sp-empty-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 15C21 16.1 20.1 17 19 17H7L3 21V5C3 3.9 3.9 3 5 3H19C20.1 3 21 3.9 21 5V15Z" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <h3 class="sp-empty-title">Belum ada konseling</h3>
        <p class="sp-empty-desc">{{ loadError ? 'Gagal memuat data. Silakan coba lagi nanti.' : 'Belum ada jadwal atau riwayat konseling.' }}</p>
      </div>

      <div v-else class="sp-panel">
        <div class="sp-list">
          <div v-for="c in sessions" :key="c.id" class="sp-list-item counseling-card">
            <div class="card-main">
              <div class="card-top">
                <span class="sp-list-title">{{ c.counseling_type?.name || 'Konseling' }}</span>
                <span class="sp-badge" :class="statusBadge(c.status)">{{ c.status || '-' }}</span>
              </div>
              <div class="sp-list-meta">{{ formatDate(c.session_date || c.scheduled_at || c.date) }}</div>
              <div v-if="c.counselor?.name" class="sp-list-meta">Konselor: {{ c.counselor.name }}</div>
              <div v-if="c.summary" class="card-summary">{{ c.summary }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { counselingApi } from '@/api/counseling'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const loadError = ref(false)
const sessions = ref([])

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusBadge(status) {
  if (!status) return 'sp-badge--pending'
  const s = String(status).toLowerCase()
  if (s.includes('selesai') || s.includes('done')) return 'sp-badge--done'
  if (s.includes('dibatalkan') || s.includes('cancel')) return 'sp-badge--cancel'
  return 'sp-badge--pending'
}

onMounted(async () => {
  if (!studentId.value) {
    loading.value = false
    return
  }
  try {
    loadError.value = false
    const res = await counselingApi.getByStudent(studentId.value, { per_page: 50 })
    const list = res.data?.data ?? res.data ?? []
    sessions.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    loadError.value = true
    sessions.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.counseling-card {
  align-items: stretch;
  background: #fff;
}

.card-main {
  width: 100%;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.card-summary {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid #e2e8f0;
  font-size: 13px;
  color: #334155;
  line-height: 1.5;
}
</style>
