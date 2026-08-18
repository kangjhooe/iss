<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <p class="sp-subtitle">Nilai per semester dan unduh raport CSV</p>
        <div class="sp-actions">
          <div v-if="semesters.length" class="semester-select-wrap">
            <label for="semester-select">Semester</label>
            <select id="semester-select" v-model="selectedSemesterId" class="sp-select">
              <option value="">Pilih semester</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <button
            v-if="grades.length"
            type="button"
            class="sp-btn sp-btn--primary"
            :disabled="downloadingRaport"
            @click="downloadRaport"
          >
            {{ downloadingRaport ? 'Mengunduh...' : 'Download Raport' }}
          </button>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat nilai...</p>
      </div>

      <div v-else-if="!selectedSemesterId" class="sp-empty">
        <h3 class="sp-empty-title">Pilih semester</h3>
        <p class="sp-empty-desc">Pilih semester di atas untuk melihat nilai.</p>
      </div>

      <div v-else-if="!grades.length" class="sp-empty">
        <h3 class="sp-empty-title">Belum ada nilai</h3>
        <p class="sp-empty-desc">Belum ada nilai untuk semester ini.</p>
      </div>

      <div v-else class="sp-panel grades-panel">
        <div class="sp-table-wrap sp-table-desktop">
          <table class="sp-table">
            <thead>
              <tr>
                <th>Mata Pelajaran</th>
                <th>Rata Penilaian</th>
                <th>UTS</th>
                <th>UAS</th>
                <th>Nilai Akhir</th>
                <th>KKM</th>
                <th>Predikat</th>
                <th>Ketuntasan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="g in grades" :key="g.subject_id">
                <td class="subject-name">
                  {{ g.subject?.name || '-' }}
                  <div v-if="penilaianDetail(g)" class="penilaian-detail">{{ penilaianDetail(g) }}</div>
                </td>
                <td>{{ g.rata_penilaian ?? '-' }}</td>
                <td>{{ g.uts ?? '-' }}</td>
                <td>{{ g.uas ?? '-' }}</td>
                <td class="nilai-akhir">{{ g.nilai_akhir ?? '-' }}</td>
                <td>{{ g.kkm ?? '-' }}</td>
                <td>
                  <span v-if="g.predicate" class="pred-chip" :class="`pred-${g.predicate}`">{{ g.predicate }}</span>
                  <span v-else>-</span>
                </td>
                <td>
                  <span
                    v-if="g.tuntas_label"
                    class="sp-badge"
                    :class="g.is_tuntas ? 'sp-badge--ok' : 'sp-badge--danger'"
                  >{{ g.tuntas_label }}</span>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="sp-mobile-cards">
          <article v-for="g in grades" :key="'m-' + g.subject_id" class="sp-mobile-card">
            <div class="sp-mobile-card-title">{{ g.subject?.name || '-' }}</div>
            <div class="sp-mobile-card-row"><span>Rata Penilaian</span><strong>{{ g.rata_penilaian ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>UTS</span><strong>{{ g.uts ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>UAS</span><strong>{{ g.uas ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>Nilai Akhir</span><strong>{{ g.nilai_akhir ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>KKM</span><strong>{{ g.kkm ?? '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>Predikat</span><strong>{{ g.predicate || '-' }}</strong></div>
            <div class="sp-mobile-card-row"><span>Ketuntasan</span><strong>{{ g.tuntas_label || '-' }}</strong></div>
          </article>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { gradeBookApi } from '@/api/gradeBook'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const authStore = useAuthStore()

const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(false)
const downloadingRaport = ref(false)
const semesters = ref([])
const selectedSemesterId = ref('')
const grades = ref([])

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
    const res = await gradeBookApi.getByStudentSemester({
      student_id: studentId.value,
      semester_id: selectedSemesterId.value
    })
    const list = res.data?.data ?? res.data ?? []
    grades.value = Array.isArray(list) ? list : (list?.data ?? [])
  } catch {
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

async function downloadRaport() {
  if (!studentId.value || !selectedSemesterId.value) return
  downloadingRaport.value = true
  try {
    const res = await gradeBookApi.exportStudentRaport({
      student_id: studentId.value,
      semester_id: selectedSemesterId.value
    })
    const blob = res.data
    const disposition = res.headers?.['content-disposition']
    let filename = 'raport.csv'
    if (disposition && /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.test(disposition)) {
      const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
      if (match && match[1]) filename = match[1].replace(/['"]/g, '').trim()
    }
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    const msg = err?.formattedMessage || err?.response?.data?.message || 'Raport tidak dapat diunduh. Periksa koneksi dan coba lagi.'
    toast.error('Gagal mengunduh raport', msg)
  } finally {
    downloadingRaport.value = false
  }
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

.grades-panel {
  padding: 0;
  overflow: hidden;
}

.sp-table-wrap {
  border: none;
}

.subject-name {
  font-weight: 600;
}

.penilaian-detail {
  margin-top: 4px;
  font-size: 11px;
  color: #64748b;
  font-weight: 400;
}

.nilai-akhir {
  font-weight: 800;
  color: #0f172a;
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
