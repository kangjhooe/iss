<template>    <div class="sp-page">
      <div class="sp-page-header">
        <div>
          <h1>Jadwal Mengajar</h1>
          <p class="sp-subtitle">Semester aktif · tampilan mingguan</p>
          <div v-if="activeSemester" class="sp-meta">
            <span class="sp-meta-chip">{{ activeSemester.name }}</span>
            <span v-if="activeSemester.academic_year?.name" class="sp-meta-chip muted">
              {{ activeSemester.academic_year.name }}
            </span>
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

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat jadwal</h3>
        <p class="sp-empty-desc">{{ loadError }}</p>
        <button type="button" class="btn-primary btn-sm" @click="loadSchedule">Coba lagi</button>
      </div>

      <div v-else-if="!scheduleMatrix.length" class="sp-empty">
        <div class="sp-empty-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1 4 21 4.9 21 6V20C21 21.1 20.1 22 19 22H5C3.9 22 3 21.1 3 20V6C3 4.9 3.9 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <h3 class="sp-empty-title">Belum ada jadwal</h3>
        <p class="sp-empty-desc">Jadwal mengajar semester aktif belum tersedia untuk Anda.</p>
        <router-link to="/teacher/dashboard" class="profile-link">Kembali ke Dashboard</router-link>
      </div>

      <template v-else-if="view === 'today'">
        <section v-if="nextSlot" class="sp-next">
          <div>
            <div class="sp-next-kicker">{{ nextSlot.isCurrent ? 'Sedang berlangsung' : 'Jam berikutnya' }}</div>
            <div class="sp-next-title">{{ slotSubject(nextSlot) }} · {{ slotClass(nextSlot) }}</div>
          </div>
          <div class="sp-next-meta">
            Jam {{ nextSlot.period }}
            <template v-if="nextSlot.start_time"> · {{ nextSlot.start_time }}{{ nextSlot.end_time ? '–' + nextSlot.end_time : '' }}</template>
            <template v-if="slotRoom(nextSlot)"> · {{ slotRoom(nextSlot) }}</template>
          </div>
        </section>

        <div v-if="todaySlots.length" class="sp-slot-list">
          <div
            v-for="slot in todaySlots"
            :key="slot.id || slot.period"
            class="sp-slot"
            :class="{ 'sp-now': isCurrentSlot(slot) }"
          >
            <div>
              <span class="sp-slot-period">Jam {{ slot.period }}</span>
              <span v-if="slot.start_time" class="sp-slot-time">{{ slot.start_time }}–{{ slot.end_time }}</span>
            </div>
            <div>
              <div class="sp-list-title">{{ slotSubject(slot) }} · {{ slotClass(slot) }}</div>
              <div class="sp-list-meta">
                <template v-if="slotRoom(slot)">{{ slotRoom(slot) }}</template>
                <template v-else>—</template>
              </div>
            </div>
            <div v-if="hasTeachingActions" class="slot-actions">
              <router-link
                v-if="canAccessModule('teaching_journal')"
                class="slot-link"
                :to="journalLink(slot)"
              >Jurnal</router-link>
              <router-link
                v-if="canAccessModule('grade_book')"
                class="slot-link"
                :to="gradeBookLink(slot)"
              >Nilai</router-link>
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
                    <div class="slot-class">{{ slotClass(getSlot(day, period)) }}</div>
                    <div v-if="getSlot(day, period).start_time" class="slot-time">
                      {{ getSlot(day, period).start_time }} – {{ getSlot(day, period).end_time }}
                    </div>
                    <div v-if="slotRoom(getSlot(day, period))" class="slot-room">{{ slotRoom(getSlot(day, period)) }}</div>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div></template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { semesterApi } from '@/api/semester'
import { useAuthStore } from '@/stores/auth'
import { hasModuleAccess as canAccessModule } from '@/utils/moduleAccess'

const authStore = useAuthStore()

const DAY_NAMES = {
  1: 'Senin',
  2: 'Selasa',
  3: 'Rabu',
  4: 'Kamis',
  5: 'Jumat',
  6: 'Sabtu',
  7: 'Minggu',
}

const loading = ref(true)
const loadError = ref('')
const view = ref('week')
const activeSemester = ref(null)
const schedules = ref([])

const todayDayOfWeek = (() => {
  const js = new Date().getDay()
  return js === 0 ? 7 : js
})()

const hasTeachingActions = computed(() =>
  canAccessModule(authStore.user, 'teaching_journal') || canAccessModule(authStore.user, 'grade_book')
)

const scheduleMatrix = computed(() => {
  const list = schedules.value
  if (!list.length) return []

  const daysWithSlots = new Set(list.map((s) => Number(s.day_of_week)).filter((d) => d >= 1 && d <= 7))
  const orderedDays = [1, 2, 3, 4, 5, 6, 7].filter((d) => daysWithSlots.has(d))
  const maxPeriod = Math.max(8, ...list.map((s) => Number(s.period) || 0))

  return orderedDays.map((day) => {
    const slots = {}
    for (let period = 1; period <= maxPeriod; period += 1) {
      const slot = list.find(
        (s) => Number(s.day_of_week) === day && Number(s.period) === period
      )
      slots[period] = slot || null
    }
    return {
      day_of_week: day,
      day_name: DAY_NAMES[day] || `Hari ${day}`,
      periods: maxPeriod,
      slots,
    }
  })
})

const periodRange = computed(() => {
  const max = Math.max(0, ...scheduleMatrix.value.map((d) => Number(d.periods) || 0))
  return max ? Array.from({ length: max }, (_, i) => i + 1) : []
})

function getSlot(dayRow, period) {
  const slots = dayRow.slots
  if (!slots || typeof slots !== 'object') return null
  return slots[period] || slots[String(period)] || null
}

function slotSubject(slot) {
  return slot?.subject?.name || '-'
}

function slotClass(slot) {
  return slot?.school_class?.name || '-'
}

function slotRoom(slot) {
  return slot?.room?.name || ''
}

const todayRow = computed(() => scheduleMatrix.value.find((d) => Number(d.day_of_week) === todayDayOfWeek) || null)

const todaySlots = computed(() => {
  const row = todayRow.value
  if (!row) return []
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

function journalLink(slot) {
  return {
    path: '/teaching-journal',
    query: {
      semester_id: slot.semester_id || activeSemester.value?.id,
      class_id: slot.class_id,
      subject_id: slot.subject_id,
      lesson_schedule_id: slot.id,
      period: slot.period,
    },
  }
}

function gradeBookLink(slot) {
  return {
    path: '/grade-book',
    query: {
      semester_id: slot.semester_id || activeSemester.value?.id,
      class_id: slot.class_id,
      subject_id: slot.subject_id,
    },
  }
}

async function loadSchedule() {
  loading.value = true
  loadError.value = ''
  try {
    const resSem = await semesterApi.getActive()
    const dataSem = resSem.data?.data ?? resSem.data
    activeSemester.value = Array.isArray(dataSem) ? dataSem[0] : dataSem

    const res = await lessonScheduleApi.getMyTeachingLoad(
      activeSemester.value?.id ? { semester_id: activeSemester.value.id } : {}
    )
    const data = res.data?.data ?? res.data ?? {}
    const list = Array.isArray(data.schedules) ? data.schedules : []
    schedules.value = list.map((s) => s?.data ?? s)

    if (!activeSemester.value?.id && data.semester_id) {
      activeSemester.value = { id: data.semester_id, name: 'Semester aktif' }
    }
  } catch (e) {
    schedules.value = []
    loadError.value = e.response?.data?.message || e.formattedMessage || e.message || 'Gagal memuat jadwal.'
  } finally {
    loading.value = false
  }
}

onMounted(loadSchedule)
</script>

<style scoped>
.sp-meta-chip.muted {
  background: #f1f5f9;
  color: #64748b;
}

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
  min-width: 720px;
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
  min-width: 130px;
}

.cell {
  font-size: 13px;
  color: #0f172a;
}

.slot-subject {
  font-weight: 700;
  margin-bottom: 4px;
}

.slot-class {
  font-size: 12px;
  color: #334155;
  margin-bottom: 4px;
}

.slot-time,
.slot-room {
  font-size: 11px;
  color: #64748b;
}

.sp-slot {
  display: grid;
  grid-template-columns: 88px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
}

.slot-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.slot-link {
  font-size: 12px;
  font-weight: 600;
  color: #047857;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 8px;
  background: #ecfdf5;
}

.slot-link:hover {
  background: #d1fae5;
}

.profile-link {
  display: inline-block;
  margin-top: 12px;
  color: #047857;
  font-weight: 600;
  text-decoration: none;
}

.btn-sm {
  margin-top: 12px;
  padding: 8px 14px;
  font-size: 13px;
}

@media (max-width: 768px) {
  .sp-slot {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .slot-actions {
    justify-content: flex-start;
  }

  .schedule-table th,
  .schedule-table td {
    padding: 8px 6px;
  }

  .col-day {
    min-width: 96px;
  }
}
</style>
