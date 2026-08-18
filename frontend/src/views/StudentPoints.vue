<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <p class="sp-subtitle">Ringkasan poin pelanggaran dan prestasi Anda</p>
        <div class="sp-actions">
          <router-link to="/student/pelanggaran-prestasi" class="sp-btn sp-btn--soft">Riwayat detail</router-link>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat data poin...</p>
      </div>

      <div v-else-if="!summary" class="sp-empty">
        <h3 class="sp-empty-title">Data poin belum tersedia</h3>
        <p class="sp-empty-desc">Belum dapat memuat ringkasan poin. Coba muat ulang halaman.</p>
      </div>

      <template v-else>
        <div class="sp-stats points-stats">
          <div class="sp-stat sp-stat--primary">
            <div class="sp-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div>
              <span class="sp-stat-label">Total Poin</span>
              <span class="sp-stat-value">{{ summary.total_points ?? '-' }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--warn">
            <div class="sp-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 9V13M12 17H12.01M21 12A9 9 0 1 1 3 12A9 9 0 0 1 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div>
              <span class="sp-stat-label">Poin Pelanggaran</span>
              <span class="sp-stat-value">{{ summary.violation_points ?? '-' }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--ok">
            <div class="sp-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2"/></svg>
            </div>
            <div>
              <span class="sp-stat-label">Poin Prestasi</span>
              <span class="sp-stat-value">{{ summary.achievement_points ?? summary.achievement_bank ?? '-' }}</span>
            </div>
          </div>
        </div>

        <div v-if="summary.required_action" class="sp-panel action-card">
          <h3 class="action-title">Tindakan yang Diperlukan</h3>
          <p class="action-name">{{ summary.required_action.action_name }}</p>
          <p v-if="summary.required_action.description" class="action-desc">{{ summary.required_action.description }}</p>
          <p class="action-range">
            Rentang poin: {{ summary.required_action.point_min }} – {{ summary.required_action.point_max }}
          </p>
          <p v-if="summary.action_fulfilled" class="action-fulfilled">Status: sudah ditindak pada periode ini.</p>
          <p v-else-if="summary.action_pending" class="action-pending">
            Status: menunggu pelaksanaan tindakan
            <template v-if="summary.new_points_since_action > 0">
              (skor naik +{{ summary.new_points_since_action }} sejak tindakan terakhir)
            </template>.
          </p>
        </div>

        <div v-else class="sp-panel action-card action-ok">
          <p class="action-name">Poin Anda dalam batas aman.</p>
          <p class="action-desc">Pertahankan kedisiplinan dan terus kumpulkan prestasi.</p>
        </div>
      </template>
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
.points-stats .sp-stat {
  cursor: default;
}

.action-card {
  border-color: #fecaca;
  background: linear-gradient(180deg, #fff5f5 0%, #fff 60%);
}

.action-card.action-ok {
  border-color: #bbf7d0;
  background: linear-gradient(180deg, #f0fdf4 0%, #fff 60%);
}

.action-title {
  font-size: 13px;
  font-weight: 800;
  color: #991b1b;
  margin: 0 0 8px 0;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.action-name {
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
  font-size: 16px;
}

.action-card.action-ok .action-name {
  color: #065f46;
}

.action-desc {
  font-size: 13px;
  color: #475569;
  margin: 0 0 6px 0;
}

.action-range {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.action-fulfilled {
  font-size: 13px;
  color: #047857;
  font-weight: 700;
  margin: 10px 0 0;
}

.action-pending {
  font-size: 13px;
  color: #b45309;
  font-weight: 700;
  margin: 10px 0 0;
}
</style>
