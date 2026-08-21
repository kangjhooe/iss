<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <p class="sp-subtitle">Nilai per mata pelajaran pada semester yang dipilih</p>
        <div class="sp-actions">
          <div v-if="semesters.length" class="semester-select-wrap">
            <label for="semester-select">Semester</label>
            <select id="semester-select" v-model="selectedSemesterId" class="sp-select">
              <option value="">Pilih semester</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat nilai...</p>
      </div>

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat nilai</h3>
        <p class="sp-empty-desc">Periksa koneksi lalu coba lagi.</p>
        <div class="sp-empty-actions">
          <button type="button" class="sp-btn sp-btn--soft" @click="loadGrades">Coba lagi</button>
        </div>
      </div>

      <div v-else-if="!selectedSemesterId" class="sp-empty">
        <h3 class="sp-empty-title">Pilih semester</h3>
        <p class="sp-empty-desc">Pilih semester di atas untuk melihat nilai.</p>
      </div>

      <div v-else-if="!grades.length" class="sp-empty">
        <h3 class="sp-empty-title">Belum ada nilai</h3>
        <p class="sp-empty-desc">Belum ada nilai untuk semester ini.</p>
      </div>

      <template v-else>
        <div class="sp-stats sp-stats--2">
          <div class="sp-stat sp-stat--ok">
            <div>
              <span class="sp-stat-label">Tuntas</span>
              <span class="sp-stat-value">{{ tuntasCount }}</span>
            </div>
          </div>
          <div class="sp-stat" :class="{ 'sp-stat--warn': belumTuntasCount }">
            <div>
              <span class="sp-stat-label">Belum tuntas</span>
              <span class="sp-stat-value">{{ belumTuntasCount }}</span>
            </div>
          </div>
        </div>

        <div class="sp-grade-grid">
          <article
            v-for="g in grades"
            :key="g.subject_id"
            class="sp-grade-card"
            :class="{ 'is-warn': g.is_tuntas === false, 'is-ok': g.is_tuntas === true }"
          >
            <div class="sp-grade-card-top">
              <div class="sp-grade-name">{{ g.subject?.name || '-' }}</div>
              <div class="sp-grade-score">{{ g.nilai_akhir ?? '-' }}</div>
            </div>
            <div class="sp-grade-meta">
              <span>KKM {{ g.kkm ?? '—' }}</span>
              <span v-if="g.predicate" class="pred-chip" :class="`pred-${g.predicate}`">{{ g.predicate }}</span>
              <span
                v-if="g.tuntas_label"
                class="sp-badge"
                :class="g.is_tuntas ? 'sp-badge--ok' : 'sp-badge--danger'"
              >{{ g.tuntas_label }}</span>
            </div>
            <div class="grade-breakdown">
              <span>Penilaian {{ g.rata_penilaian ?? '—' }}</span>
              <span>UTS {{ g.uts ?? '—' }}</span>
              <span>UAS {{ g.uas ?? '—' }}</span>
            </div>
            <div v-if="penilaianDetail(g)" class="penilaian-detail">{{ penilaianDetail(g) }}</div>
          </article>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { gradeBookApi } from '@/api/gradeBook'
import { semesterApi } from '@/api/semester'

const authStore = useAuthStore()

const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(false)
const loadError = ref(false)
const semesters = ref([])
const selectedSemesterId = ref('')
const grades = ref([])

const tuntasCount = computed(() => grades.value.filter((g) => g.is_tuntas === true).length)
const belumTuntasCount = computed(() => grades.value.filter((g) => g.is_tuntas === false).length)

onMounted(async () => {
  let active = null
  try {
    const res = await semesterApi.getActive()
    const data = res.data?.data ?? res.data
    active = Array.isArray(data) ? data[0] : data
  } catch {
    // ignore
  }
  try {
    const listRes = await semesterApi.getAll({ per_page: 50 })
    const list = listRes.data?.data ?? listRes.data ?? []
    const arr = Array.isArray(list) ? list : (list?.data ?? [])
    semesters.value = arr.length ? arr : (active ? [active] : [])
    selectedSemesterId.value = active?.id || (arr[0]?.id ?? '')
  } catch {
    semesters.value = active ? [active] : []
    selectedSemesterId.value = active?.id ?? ''
  }
})

async function loadGrades() {
  if (!studentId.value || !selectedSemesterId.value) {
    grades.value = []
    return
  }
  loading.value = true
  try {
    loadError.value = false
    const res = await gradeBookApi.getByStudentSemester({
      student_id: studentId.value,
      semester_id: selectedSemesterId.value
    })
    const list = res.data?.data ?? res.data ?? []
    grades.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
    loadError.value = true
    grades.value = []
  } finally {
    loading.value = false
  }
}

watch(selectedSemesterId, () => loadGrades(), { immediate: true })

function penilaianDetail(g) {
  const src = g?.penilaian
  if (!src || typeof src !== 'object') return ''
  const parts = Object.keys(src)
    .map((k) => Number(k))
    .filter((n) => Number.isInteger(n) && n > 0)
    .sort((a, b) => a - b)
    .map((n) => {
      const v = src[n] ?? src[String(n)]
      return v == null || v === '' ? null : `P${n}: ${v}`
    })
    .filter(Boolean)
  return parts.length ? parts.join(' · ') : ''
}
</script>

<style scoped>
.semester-select-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 180px;
}

.semester-select-wrap label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.grade-breakdown {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 12px;
  margin-top: 10px;
  font-size: 12px;
  color: #64748b;
}

.penilaian-detail {
  margin-top: 6px;
  font-size: 11px;
  color: #94a3b8;
}

.pred-chip {
  display: inline-flex;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  background: #f1f5f9;
  color: #334155;
}

.pred-A { background: #d1fae5; color: #065f46; }
.pred-B { background: #dbeafe; color: #1d4ed8; }
.pred-C { background: #fef3c7; color: #92400e; }
.pred-D, .pred-E { background: #fee2e2; color: #991b1b; }

@media (max-width: 768px) {
  .sp-page-header .sp-actions {
    width: 100%;
  }

  .semester-select-wrap {
    flex: 1;
  }
}
</style>
