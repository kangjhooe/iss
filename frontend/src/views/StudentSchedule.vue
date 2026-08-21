<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <div>
          <p class="sp-subtitle">{{ classInfo || 'Jadwal pelajaran' }}</p>
          <div v-if="activeSemester" class="sp-meta">
            <span class="sp-meta-chip">{{ activeSemester.name }}</span>
          </div>
        </div>
        <div class="sp-tabs" role="tablist">
          <button type="button" class="sp-tab" :class="{ 'is-active': view === 'today' }" @click="view = 'today'">Hari ini</button>
          <button type="button" class="sp-tab" :class="{ 'is-active': view === 'week' }" @click="view = 'week'">Minggu ini</button>
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

      <template v-else-if="view === 'today'">
        <section v-if="nextSlot" class="sp-next">
          <div>
            <div class="sp-next-kicker">{{ nextSlot.isCurrent ? 'Sedang berlangsung' : 'Jam berikutnya' }}</div>
            <div class="sp-next-title">{{ slotSubject(nextSlot) }}</div>
          </div>
          <div class="sp-next-meta">
            Jam {{ nextSlot.period }} · {{ nextSlot.start_time }}–{{ nextSlot.end_time }}
            <template v-if="slotTeacher(nextSlot)"> · {{ slotTeacher(nextSlot) }}</template>
          </div>
        </section>

        <div v-if="todayHoliday" class="sp-empty">
          <h3 class="sp-empty-title">Libur</h3>
          <p class="sp-empty-desc">Hari ini tidak ada jam pelajaran di jadwal kelas Anda.</p>
        </div>
        <div v-else-if="todaySlots.length" class="sp-slot-list">
          <div
            v-for="slot in todaySlots"
            :key="slot.id || slot.period"
            class="sp-slot"
            :class="{ 'sp-now': isCurrentSlot(slot) }"
          >
            <div>
              <span class="sp-slot-period">Jam {{ slot.period }}</span>
              <span class="sp-slot-time">{{ slot.start_time }}–{{ slot.end_time }}</span>
            </div>
            <div>
              <div class="sp-list-title">{{ slotSubject(slot) }}</div>
              <div class="sp-list-meta">
                <template v-if="slotTeacher(slot)">{{ slotTeacher(slot) }}</template>
                <template v-if="slotTeacher(slot) && slotRoom(slot)"> · </template>
                <template v-if="slotRoom(slot)">{{ slotRoom(slot) }}</template>
                <template v-if="!slotTeacher(slot) && !slotRoom(slot)">—</template>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="sp-empty">
          <h3 class="sp-empty-title">Tidak ada jadwal hari ini</h3>
          <p class="sp-empty-desc">Buka tab Minggu ini untuk melihat jadwal lengkap.</p>
        </div>
      </template>

      <div v-else class="sp-panel schedule-panel">
        <div class="sp-table-wrap schedule-scroll">
          <table class="schedule-table">
            <thead>
              <tr>
                <th class="col-period">Jam</th>
                <th
                  v-for="day in scheduleMatrix"
                  :key="day.day_of_week"
                  class="col-day"
                  :class="{ 'is-today': day.day_of_week === todayDayOfWeek }"
                >
                  {{ day.day_name }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in periodRange" :key="period">
                <td class="col-period">{{ period }}</td>
                <td
                  v-for="day in scheduleMatrix"
                  :key="day.day_of_week"
                  class="col-day cell"
                  :class="{ 'is-today': day.day_of_week === todayDayOfWeek }"
                >
                  <template v-if="getSlot(day, period)">
                    <div class="slot-subject">{{ slotSubject(getSlot(day, period)) }}</div>
                    <div class="slot-time">{{ getSlot(day, period).start_time }} – {{ getSlot(day, period).end_time }}</div>
                    <div v-if="slotTeacher(getSlot(day, period))" class="slot-room">{{ slotTeacher(getSlot(day, period)) }}</div>
                    <div v-if="slotRoom(getSlot(day, period))" class="slot-room">{{ slotRoom(getSlot(day, period)) }}</div>
                  </template>
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
const view = ref('today')
const activeSemester = ref(null)
const scheduleRaw = ref(null)

const todayDayOfWeek = (() => {
  const js = new Date().getDay()
  return js === 0 ? 7 : js
})()

const scheduleMatrix = computed(() => {
  const raw = scheduleRaw.value
  if (!raw?.matrix || !Array.isArray(raw.matrix)) return []
  return raw.matrix
})

const periodRange = computed(() => {
  const fromTemplate = Number(scheduleRaw.value?.template?.max_periods || 0)
  const fromDays = Math.max(0, ...scheduleMatrix.value.map((d) => Number(d.periods) || 0))
  let fromSlots = 0
  for (const day of scheduleMatrix.value) {
    const keys = Object.keys(day.slots || {}).map(Number).filter((n) => n > 0)
    fromSlots = Math.max(fromSlots, ...keys, 0)
  }
  const max = fromTemplate || fromDays || fromSlots
  return max ? Array.from({ length: max }, (_, i) => i + 1) : []
})

function unwrapSlot(slot) {
  if (!slot) return null
  return slot.data && typeof slot.data === 'object' ? slot.data : slot
}

function getSlot(dayRow, period) {
  const slots = dayRow.slots
  if (!slots || typeof slots !== 'object') return null
  return unwrapSlot(slots[period] || slots[String(period)])
}

function slotSubject(slot) {
  return slot?.subject?.name || '-'
}

function slotTeacher(slot) {
  return slot?.employee?.name || ''
}

function slotRoom(slot) {
  return slot?.room?.name || ''
}

const todayRow = computed(() => scheduleMatrix.value.find((d) => Number(d.day_of_week) === todayDayOfWeek) || null)
const todayHoliday = computed(() => !!todayRow.value?.is_holiday)

const todaySlots = computed(() => {
  const row = todayRow.value
  if (!row || row.is_holiday) return []
  return periodRange.value
    .map((period) => {
      const slot = getSlot(row, period)
      return slot ? { ...slot, period: slot.period || period } : null
    })
    .filter(Boolean)
})

function parseTodayTime(t) {
  if (!t) return null
  const [h, m] = String(t).split(':').map((n) => Number(n))
  if (Number.isNaN(h)) return null
  const d = new Date()
  d.setHours(h, Number.isNaN(m) ? 0 : m, 0, 0)
  return d
}

function isCurrentSlot(slot) {
  const start = parseTodayTime(slot.start_time)
  const end = parseTodayTime(slot.end_time)
  if (!start || !end) return false
  const now = new Date()
  return now >= start && now < end
}

const nextSlot = computed(() => {
  const current = todaySlots.value.find((s) => isCurrentSlot(s))
  if (current) return { ...current, isCurrent: true }
  const now = new Date()
  const upcoming = todaySlots.value.find((s) => {
    const start = parseTodayTime(s.start_time)
    return start && start > now
  })
  return upcoming ? { ...upcoming, isCurrent: false } : null
})

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

.schedule-table th.is-today,
.schedule-table td.is-today {
  background: #ecfdf5;
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
