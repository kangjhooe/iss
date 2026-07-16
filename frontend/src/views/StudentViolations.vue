<template>
  <Layout>
    <div class="page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="alert alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah. Silakan hubungi operator sekolah atau admin.
        <router-link to="/student/dashboard" class="alert-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat data...</p>
      </div>

      <div v-else class="content-wrap">
        <section class="section">
          <h2 class="section-title">Pelanggaran</h2>
          <div v-if="violations.length" class="list">
            <div v-for="v in violations" :key="'v-' + v.id" class="card card-violation">
              <div class="card-main">
                <span class="card-type">Pelanggaran</span>
                <span class="card-desc">{{ v.violation_type?.name || v.description || '-' }}</span>
                <span class="card-date">{{ formatDate(v.violation_date || v.date) }}</span>
              </div>
              <div v-if="violationPoints(v) != null" class="card-meta">Poin: +{{ violationPoints(v) }}</div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p v-if="loadError">Gagal memuat data. Silakan coba lagi atau kembali ke dashboard.</p>
            <p v-else>Belum ada catatan pelanggaran.</p>
            <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
          </div>
        </section>

        <section class="section">
          <h2 class="section-title">Prestasi</h2>
          <div v-if="achievements.length" class="list">
            <div v-for="a in achievements" :key="'a-' + a.id" class="card card-achievement">
              <div class="card-main">
                <span class="card-type">Prestasi</span>
                <span class="card-desc">{{ a.achievement_type?.name || a.description || '-' }}</span>
                <span class="card-date">{{ formatDate(a.achievement_date || a.date) }}</span>
              </div>
              <div v-if="achievementPoints(a) != null" class="card-meta">Poin: −{{ achievementPoints(a) }}</div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p v-if="loadError">Gagal memuat data. Silakan coba lagi atau kembali ke dashboard.</p>
            <p v-else>Belum ada catatan prestasi.</p>
            <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
          </div>
        </section>

        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>
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
.page { max-width: 100%; padding: 0; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }
.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #64748b; margin: 0; }

.loading-state {
  text-align: center; padding: 48px 24px; color: #64748b;
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
}

.content-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }
.section { margin-bottom: 28px; }
.section:last-of-type { margin-bottom: 20px; }
.section-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 12px 0; }

.list { display: flex; flex-direction: column; gap: 10px; }
.card {
  padding: 14px 16px; border-radius: 10px; border: 1px solid #e2e8f0;
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px;
}
.card-violation { background: #fef2f2; border-color: #fecaca; }
.card-achievement { background: #f0fdf4; border-color: #bbf7d0; }
.card-type { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; }
.card-desc { flex: 1; font-weight: 600; color: #0f172a; }
.card-date { font-size: 13px; color: #64748b; }
.card-meta { font-size: 12px; color: #475569; }

.empty-state { padding: 16px; text-align: center; color: #94a3b8; font-size: 14px; }
.back-link { display: inline-block; margin-top: 16px; color: #059669; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; color: #047857; }

.alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; }
.alert-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; }
.alert-link { display: inline-block; margin-top: 10px; color: #b45309; font-weight: 600; text-decoration: none; }
.alert-link:hover { text-decoration: underline; }
</style>
