<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Ekstrakurikuler</h1>
        <p class="page-subtitle">Daftar ekstrakurikuler yang Anda ikuti</p>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat data ekstrakurikuler...</p>
      </div>

      <div v-else-if="!items.length" class="empty-state">
        <p>Belum terdaftar di ekstrakurikuler.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="content-wrap">
        <div class="list">
          <div v-for="e in items" :key="e.id" class="card">
            <div class="card-name">{{ e.name }}</div>
            <div v-if="e.supervisor?.name" class="card-meta">Pembina: {{ e.supervisor.name }}</div>
            <div v-if="e.semester?.name || e.academic_year?.name" class="card-meta">
              {{ [e.semester?.name, e.academic_year?.name].filter(Boolean).join(' · ') }}
            </div>
            <div v-if="enrollmentJoinedAt(e)" class="card-date">Bergabung: {{ formatDate(enrollmentJoinedAt(e)) }}</div>
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
import { extracurricularApi } from '@/api/extracurricular'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const rawEnrollments = ref([])

const items = computed(() => {
  const raw = rawEnrollments.value
  if (!Array.isArray(raw)) return []
  return raw.map((e) => {
    const ex = e.extracurricular || e
    const base = typeof ex === 'object' && ex !== null ? { ...ex } : { id: e.id, name: '-' }
    return { ...base, semester: e.semester, academic_year: e.academic_year, _enrollment: e }
  }).filter((e) => e.name)
})

function enrollmentJoinedAt(item) {
  const en = item._enrollment
  return en?.joined_at ?? en?.created_at ?? null
}

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
    const res = await extracurricularApi.getByStudent(studentId.value, { per_page: 50 })
    const list = res.data?.data ?? res.data ?? []
    rawEnrollments.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    rawEnrollments.value = []
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
.card-name { font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.card-meta, .card-date { font-size: 13px; color: #64748b; }
.back-link { display: inline-block; margin-top: 16px; color: #0ea5e9; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { text-decoration: underline; }
</style>
