<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Ekstrakurikuler</h1>
        <p class="page-subtitle">Daftar ekstrakurikuler yang Anda ikuti beserta rekap nilai</p>
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
            <button type="button" class="card-head" @click="toggleExpand(e)">
              <div class="card-head-text">
                <div class="card-name">{{ e.name }}</div>
                <div v-if="e.supervisor?.name" class="card-meta">Pembina: {{ e.supervisor.name }}</div>
                <div v-if="e.semester?.name || e.academic_year?.name" class="card-meta">
                  {{ [e.semester?.name, e.academic_year?.name].filter(Boolean).join(' · ') }}
                </div>
                <div v-if="enrollmentJoinedAt(e)" class="card-date">Bergabung: {{ formatDate(enrollmentJoinedAt(e)) }}</div>
              </div>
              <span class="expand-hint">{{ expandedId === e.id ? 'Tutup' : 'Lihat nilai' }}</span>
            </button>

            <div v-if="expandedId === e.id" class="card-body">
              <div v-if="gradeLoadingId === e.id" class="muted">Memuat rekap nilai...</div>
              <div v-else-if="gradeError[e.id]" class="error-text">{{ gradeError[e.id] }}</div>
              <template v-else-if="gradeData[e.id]">
                <div class="summary-row">
                  <span class="chip">KKM {{ gradeData[e.id].extracurricular?.kkm ?? '—' }}</span>
                  <span class="chip">Dinilai {{ gradeData[e.id].graded_sessions ?? 0 }} pertemuan</span>
                  <span class="chip">Rata-rata {{ gradeData[e.id].average ?? '—' }}</span>
                  <span class="chip chip-strong">
                    Akhir {{ gradeData[e.id].final_score ?? '—' }}
                    <template v-if="gradeData[e.id].predicate"> ({{ gradeData[e.id].predicate }})</template>
                  </span>
                </div>
                <div v-if="gradeData[e.id].sessions?.length" class="table-scroll">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Tanggal</th>
                        <th>Materi</th>
                        <th>Kehadiran</th>
                        <th>Nilai</th>
                        <th>Predikat</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="s in gradeData[e.id].sessions" :key="s.session_id">
                        <td>{{ formatDate(s.session_date) }}</td>
                        <td>{{ s.topic || '—' }}</td>
                        <td>{{ statusLabel(s.attendance_status) }}</td>
                        <td>{{ s.score != null ? s.score : '—' }}</td>
                        <td>{{ s.predicate || '—' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-else class="muted">Belum ada pertemuan / nilai pada semester ini.</p>
              </template>
            </div>
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
const expandedId = ref(null)
const gradeLoadingId = ref(null)
const gradeData = ref({})
const gradeError = ref({})

const items = computed(() => {
  const raw = rawEnrollments.value
  if (!Array.isArray(raw)) return []
  return raw.map((e) => {
    const ex = e.extracurricular || e
    const base = typeof ex === 'object' && ex !== null ? { ...ex } : { id: e.id, name: '-' }
    return {
      ...base,
      supervisor: base.supervisor || e.extracurricular?.supervisor || null,
      semester: e.semester,
      academic_year: e.academic_year,
      _enrollment: e,
    }
  }).filter((e) => e.name)
})

function enrollmentJoinedAt(item) {
  const en = item._enrollment
  return en?.joined_at ?? en?.created_at ?? null
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val.includes('T') ? val : val + 'T00:00:00')
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusLabel(s) {
  return ({ hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpha: 'Alpha' })[s] || (s || '—')
}

async function toggleExpand(item) {
  if (expandedId.value === item.id) {
    expandedId.value = null
    return
  }
  expandedId.value = item.id
  if (gradeData.value[item.id] || gradeLoadingId.value === item.id) return

  gradeLoadingId.value = item.id
  gradeError.value = { ...gradeError.value, [item.id]: '' }
  try {
    const res = await extracurricularApi.getMyGrades(item.id)
    gradeData.value = { ...gradeData.value, [item.id]: res.data?.data || null }
  } catch (e) {
    gradeError.value = {
      ...gradeError.value,
      [item.id]: e.formattedMessage || 'Gagal memuat rekap nilai',
    }
  } finally {
    gradeLoadingId.value = null
  }
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
  border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; overflow: hidden;
}
.card-head {
  width: 100%; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
  padding: 16px; border: 0; background: transparent; text-align: left; cursor: pointer;
}
.card-head:hover { background: #f1f5f9; }
.card-name { font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.card-meta, .card-date, .muted { font-size: 13px; color: #64748b; }
.expand-hint { font-size: 13px; font-weight: 600; color: #059669; white-space: nowrap; padding-top: 2px; }
.card-body { padding: 0 16px 16px; border-top: 1px solid #e2e8f0; }
.summary-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
.chip {
  font-size: 12px; padding: 4px 10px; border-radius: 999px;
  background: #fff; border: 1px solid #e2e8f0; color: #475569;
}
.chip-strong { background: #ecfdf5; border-color: #a7f3d0; color: #047857; font-weight: 600; }
.table-scroll { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; }
.data-table th, .data-table td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
.data-table th { background: #f1f5f9; font-weight: 600; color: #334155; }
.error-text { color: #b91c1c; font-size: 13px; margin-top: 12px; }
.back-link { display: inline-block; margin-top: 16px; color: #059669; text-decoration: none; font-weight: 600; font-size: 14px; }
.back-link:hover { color: #047857; text-decoration: underline; }
</style>
