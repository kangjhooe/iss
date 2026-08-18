<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <p class="sp-subtitle">Catatan pelanggaran dan prestasi semester berjalan</p>
        <div class="sp-actions">
          <router-link to="/student/poin" class="sp-btn sp-btn--soft">Lihat poin</router-link>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat data...</p>
      </div>

      <template v-else>
        <div class="sp-stats summary-stats">
          <div class="sp-stat sp-stat--warn">
            <div class="sp-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 9V13M12 17H12.01M21 12A9 9 0 1 1 3 12A9 9 0 0 1 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div>
              <span class="sp-stat-label">Pelanggaran</span>
              <span class="sp-stat-value">{{ violations.length }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--ok">
            <div class="sp-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2"/></svg>
            </div>
            <div>
              <span class="sp-stat-label">Prestasi</span>
              <span class="sp-stat-value">{{ achievements.length }}</span>
            </div>
          </div>
        </div>

        <div class="two-col">
          <section class="sp-panel">
            <div class="sp-panel-header">
              <h2 class="sp-panel-title">Pelanggaran</h2>
            </div>
            <div v-if="violations.length" class="sp-list">
              <div v-for="v in violations" :key="'v-' + v.id" class="sp-list-item sp-list-item--danger">
                <span class="sp-list-kicker">Pelanggaran</span>
                <span class="sp-list-title">{{ v.violation_type?.name || v.description || '-' }}</span>
                <span class="sp-list-meta">{{ formatDate(v.violation_date || v.date) }}</span>
                <span v-if="violationPoints(v) != null" class="sp-chip">+{{ violationPoints(v) }} poin</span>
              </div>
            </div>
            <div v-else class="sp-empty compact">
              <p class="sp-empty-desc">{{ loadError ? 'Gagal memuat data.' : 'Belum ada catatan pelanggaran.' }}</p>
            </div>
          </section>

          <section class="sp-panel">
            <div class="sp-panel-header">
              <h2 class="sp-panel-title">Prestasi</h2>
            </div>
            <div v-if="achievements.length" class="sp-list">
              <div v-for="a in achievements" :key="'a-' + a.id" class="sp-list-item sp-list-item--ok">
                <span class="sp-list-kicker">Prestasi</span>
                <span class="sp-list-title">{{ a.achievement_type?.name || a.description || '-' }}</span>
                <span class="sp-list-meta">{{ formatDate(a.achievement_date || a.date) }}</span>
                <span v-if="achievementPoints(a) != null" class="sp-chip sp-chip--strong">−{{ achievementPoints(a) }} poin</span>
              </div>
            </div>
            <div v-else class="sp-empty compact">
              <p class="sp-empty-desc">{{ loadError ? 'Gagal memuat data.' : 'Belum ada catatan prestasi.' }}</p>
            </div>
          </section>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { violationApi, achievementApi } from '@/api/violation'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const loadError = ref(false)
const violations = ref([])
const achievements = ref([])

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function violationPoints(v) {
  const pts = v.point_weight ?? v.violation_type?.point_weight ?? v.points
  return pts === undefined || pts === null ? null : pts
}

function achievementPoints(a) {
  const pts = a.point_value ?? a.achievement_type?.point_value ?? a.points
  return pts === undefined || pts === null ? null : pts
}

onMounted(async () => {
  if (!studentId.value) {
    loading.value = false
    return
  }
  try {
    loadError.value = false
    const [vRes, aRes] = await Promise.all([
      violationApi.getByStudent(studentId.value, { per_page: 100 }),
      achievementApi.getByStudent(studentId.value, { per_page: 100 })
    ])
    const vList = vRes.data?.data ?? vRes.data ?? []
    const aList = aRes.data?.data ?? aRes.data ?? []
    violations.value = Array.isArray(vList) ? vList : (vList?.data ?? [])
    achievements.value = Array.isArray(aList) ? aList : (aList?.data ?? [])
  } catch {
    loadError.value = true
    violations.value = []
    achievements.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.summary-stats {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.summary-stats .sp-stat {
  cursor: default;
}

.two-col {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.compact {
  padding: 24px 16px;
  border-style: solid;
  background: #f8fafc;
}

@media (max-width: 900px) {
  .two-col,
  .summary-stats {
    grid-template-columns: 1fr;
  }
}
</style>
