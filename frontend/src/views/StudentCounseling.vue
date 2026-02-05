<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Konseling</h1>
        <p class="page-subtitle">Riwayat dan jadwal konseling Anda</p>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat data konseling...</p>
      </div>

      <div v-else-if="!sessions.length" class="empty-state">
        <p>Belum ada jadwal atau riwayat konseling.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="content-wrap">
        <div class="list">
          <div v-for="c in sessions" :key="c.id" class="card">
            <div class="card-row">
              <span class="card-type">{{ c.counseling_type?.name || 'Konseling' }}</span>
              <span class="card-status" :class="statusClass(c.status)">{{ c.status || '-' }}</span>
            </div>
            <div class="card-date">{{ formatDate(c.session_date || c.scheduled_at || c.date) }}</div>
            <div v-if="c.counselor?.name" class="card-meta">Konselor: {{ c.counselor.name }}</div>
            <div v-if="c.summary" class="card-summary">{{ c.summary }}</div>
          </div>
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
import { counselingApi } from '@/api/counseling'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const sessions = ref([])

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusClass(status) {
  if (!status) return ''
  const s = String(status).toLowerCase()
  if (s.includes('selesai') || s.includes('done')) return 'status-done'
  if (s.includes('dibatalkan') || s.includes('cancel')) return 'status-cancel'
  return 'status-pending'
}

onMounted(async () => {
  if (!studentId.value) {
    loading.value = false
    return
  }
  try {
    const res = await counselingApi.getByStudent(studentId.value, { per_page: 50 })
    const list = res.data?.data ?? res.data ?? []
    sessions.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    sessions.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page { max-width: 100%; padding: 0; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #64748b; margin: 0; }

.loading-state, .empty-state {
  text-align: center; padding: 48px 24px; color: #64748b;
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
}

.content-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }
.list { display: flex; flex-direction: column; gap: 12px; }
.card {
  padding: 16px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;
}
.card-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.card-type { font-weight: 700; color: #0f172a; }
.card-status { font-size: 12px; font-weight: 600; padding: 4px 8px; border-radius: 6px; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-done { background: #d1fae5; color: #065f46; }
.status-cancel { background: #fee2e2; color: #991b1b; }
.card-date { font-size: 13px; color: #64748b; margin-bottom: 4px; }
.card-meta { font-size: 13px; color: #475569; }
.card-summary { font-size: 13px; color: #334155; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
.back-link { display: inline-block; margin-top: 16px; color: #0ea5e9; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; }
</style>
