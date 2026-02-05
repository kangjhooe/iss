<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <h1>Jadwal Pelajaran</h1>
        <p v-if="classInfo" class="page-subtitle">{{ classInfo }}</p>
        <p v-if="activeSemester" class="page-meta">Semester: {{ activeSemester.name }}</p>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat jadwal...</p>
      </div>

      <div v-else-if="!scheduleMatrix.length" class="empty-state">
        <p>Belum ada jadwal untuk semester aktif.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="schedule-wrap">
        <div class="table-scroll">
          <table class="schedule-table">
            <thead>
              <tr>
                <th class="col-period">Jam</th>
                <th v-for="day in scheduleMatrix" :key="day.day_of_week" class="col-day">
                  {{ day.day_name }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in periodRange" :key="period">
                <td class="col-period">{{ period }}</td>
                <td v-for="day in scheduleMatrix" :key="day.day_of_week" class="col-day cell">
                  <template v-if="getSlot(day, period)">
                    <div class="slot-subject">{{ getSlot(day, period).subject?.name || '-' }}</div>
                    <div class="slot-time">{{ getSlot(day, period).start_time }} - {{ getSlot(day, period).end_time }}</div>
                    <div class="slot-room">{{ getSlot(day, period).room?.name || '-' }}</div>
                  </template>
                  <span v-else class="slot-empty">-</span>
                </td>
              </tr>
            </tbody>
          </table>
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
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { semesterApi } from '@/api/semester'

const authStore = useAuthStore()

const classId = computed(() => authStore.user?.student_profile?.class_id)
const classInfo = computed(() => {
  const name = authStore.user?.student_profile?.class_name || authStore.user?.student_profile?.class
  return name ? `Kelas ${name}` : null
})

const loading = ref(true)
const activeSemester = ref(null)
const scheduleRaw = ref(null)

const scheduleMatrix = computed(() => {
  const raw = scheduleRaw.value
  if (!raw?.matrix || !Array.isArray(raw.matrix)) return []
  return raw.matrix
})

const periodRange = computed(() => {
  const maxPeriod = 10
  return Array.from({ length: maxPeriod }, (_, i) => i + 1)
})

function getSlot(dayRow, period) {
  const slots = dayRow.slots
  if (!slots || typeof slots !== 'object') return null
  return slots[period] || null
}

onMounted(async () => {
  try {
    const resSem = await semesterApi.getActive()
    const dataSem = resSem.data?.data ?? resSem.data
    activeSemester.value = Array.isArray(dataSem) ? dataSem[0] : dataSem
  } catch {
    activeSemester.value = null
  }

  if (!classId.value || !activeSemester.value?.id) {
    loading.value = false
    return
  }

  try {
    const res = await lessonScheduleApi.getByClass(classId.value, { semester_id: activeSemester.value.id })
    scheduleRaw.value = res.data?.data ?? res.data ?? null
  } catch {
    scheduleRaw.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.page {
  max-width: 100%;
  padding: 0;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 8px 0;
}

.page-subtitle,
.page-meta {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #64748b;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.back-link {
  display: inline-block;
  margin-top: 16px;
  color: #0ea5e9;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
}

.back-link:hover {
  text-decoration: underline;
}

.schedule-wrap {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px;
  overflow: hidden;
}

.table-scroll {
  overflow-x: auto;
}

.schedule-table {
  width: 100%;
  min-width: 500px;
  border-collapse: collapse;
}

.schedule-table th,
.schedule-table td {
  padding: 12px 10px;
  border: 1px solid #e2e8f0;
  text-align: center;
  vertical-align: top;
}

.schedule-table th {
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.col-period {
  width: 48px;
  font-weight: 600;
  color: #475569;
}

.col-day {
  min-width: 120px;
}

.cell {
  font-size: 13px;
  color: #0f172a;
}

.slot-subject {
  font-weight: 600;
  margin-bottom: 4px;
}

.slot-time,
.slot-room {
  font-size: 11px;
  color: #64748b;
}

.slot-empty {
  color: #cbd5e1;
}

@media (max-width: 768px) {
  .schedule-table {
    font-size: 12px;
  }

  .schedule-table th,
  .schedule-table td {
    padding: 8px 6px;
  }

  .col-day {
    min-width: 90px;
  }
}
</style>
