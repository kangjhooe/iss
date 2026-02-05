<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Pelanggaran & Prestasi</h1>
        <p class="page-subtitle">Riwayat pelanggaran dan prestasi Anda</p>
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
              <div v-if="v.points !== undefined" class="card-meta">Poin: {{ v.points }}</div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>Belum ada catatan pelanggaran.</p>
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
              <div v-if="a.points !== undefined" class="card-meta">Poin: {{ a.points }}</div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>Belum ada catatan prestasi.</p>
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
const violations = ref([])
const achievements = ref([])

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  if (!studentId.value) {
    loading.value = false
    return
  }
  try {
    const [vRes, aRes] = await Promise.all([
      violationApi.getByStudent(studentId.value, { per_page: 100 }),
      achievementApi.getByStudent(studentId.value, { per_page: 100 })
    ])
    const vList = vRes.data?.data ?? vRes.data ?? []
    const aList = aRes.data?.data ?? aRes.data ?? []
    violations.value = Array.isArray(vList) ? vList : (vList?.data ?? [])
    achievements.value = Array.isArray(aList) ? aList : (aList?.data ?? [])
  } catch {
    violations.value = []
    achievements.value = []
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
.back-link { display: inline-block; margin-top: 16px; color: #0ea5e9; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; }
</style>
