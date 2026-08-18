<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <div>
          <p class="sp-subtitle">{{ classInfo || 'Jadwal pelajaran mingguan' }}</p>
          <div v-if="activeSemester" class="sp-meta">
            <span class="sp-meta-chip">Semester {{ activeSemester.name }}</span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat jadwal...</p>
      </div>

      <div v-else-if="!scheduleMatrix.length" class="sp-empty">
        <div class="sp-empty-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1 4 21 4.9 21 6V20C21 21.1 20.1 22 19 22H5C3.9 22 3 21.1 3 20V6C3 4.9 3.9 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <h3 class="sp-empty-title">Belum ada jadwal</h3>
        <p class="sp-empty-desc">Jadwal semester aktif belum tersedia untuk kelas Anda.</p>
      </div>

      <div v-else class="sp-panel schedule-panel">
        <div class="sp-table-wrap schedule-scroll">
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
.schedule-panel {
  padding: 0;
  overflow: hidden;
}

.schedule-scroll {
  border: none;
  border-radius: 14px;
}

.schedule-table {
  width: 100%;
  min-width: 640px;
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
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.col-period {
  width: 52px;
  font-weight: 700;
  color: #475569;
  background: #f8fafc;
}

.col-day {
  min-width: 120px;
}

.cell {
  font-size: 13px;
  color: #0f172a;
}

.slot-subject {
  font-weight: 700;
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
  .schedule-table th,
  .schedule-table td {
    padding: 8px 6px;
  }

  .col-day {
    min-width: 96px;
  }
}
</style>
