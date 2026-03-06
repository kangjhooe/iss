<template>
  <Layout>
    <div class="page">
      <div v-if="loading" class="loading-state">
        <p>Memuat data poin...</p>
      </div>

      <div v-else-if="!summary" class="empty-state">
        <p>Belum dapat memuat data poin.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="content-wrap">
        <div class="points-grid">
          <div class="point-card point-total">
            <span class="point-label">Total Poin</span>
            <span class="point-value">{{ summary.total_points ?? '-' }}</span>
          </div>
          <div class="point-card point-violation">
            <span class="point-label">Poin Pelanggaran</span>
            <span class="point-value">{{ summary.violation_points ?? '-' }}</span>
          </div>
          <div class="point-card point-achievement">
            <span class="point-label">Poin Prestasi</span>
            <span class="point-value">{{ summary.achievement_points ?? summary.achievement_bank ?? '-' }}</span>
          </div>
        </div>

        <div v-if="summary.required_action" class="action-card">
          <h3 class="action-title">Tindakan yang Diperlukan</h3>
          <p class="action-name">{{ summary.required_action.action_name }}</p>
          <p v-if="summary.required_action.description" class="action-desc">{{ summary.required_action.description }}</p>
          <p class="action-range">
            Rentang poin: {{ summary.required_action.point_min }} – {{ summary.required_action.point_max }}
          </p>
        </div>

        <div v-else class="action-card action-ok">
          <p class="action-name">Poin Anda dalam batas aman.</p>
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
import { studentPointApi } from '@/api/violation'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const summary = ref(null)

onMounted(async () => {
  if (!studentId.value) {
    loading.value = false
    return
  }
  try {
    const res = await studentPointApi.getSummary(studentId.value)
    summary.value = res.data?.data ?? res.data ?? null
  } catch {
    summary.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page { max-width: 100%; padding: 0; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #64748b; margin: 0; }

.loading-state, .empty-state {
  text-align: center; padding: 48px 24px; color: #64748b;
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
}

.content-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }

.points-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.point-card {
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
}

.point-total { background: #eff6ff; border-color: #bfdbfe; }
.point-violation { background: #fef2f2; border-color: #fecaca; }
.point-achievement { background: #f0fdf4; border-color: #bbf7d0; }

.point-label { display: block; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
.point-value { font-size: 24px; font-weight: 700; color: #0f172a; }

.action-card {
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  margin-bottom: 20px;
}

.action-card.action-ok {
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.action-title { font-size: 14px; font-weight: 700; color: #991b1b; margin: 0 0 8px 0; }
.action-card.action-ok .action-name { color: #065f46; }
.action-name { font-weight: 600; color: #0f172a; margin: 0 0 6px 0; }
.action-desc { font-size: 14px; color: #475569; margin: 0 0 6px 0; }
.action-range { font-size: 13px; color: #64748b; margin: 0; }

.back-link { display: inline-block; margin-top: 16px; color: #059669; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; color: #047857; }
</style>
