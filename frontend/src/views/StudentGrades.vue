<template>
  <Layout>
    <div class="page">
      <div class="tab-header">
        <h1 class="tab-title">Nilai Saya</h1>
        <div v-if="semesters.length" class="semester-select-wrap">
          <label for="semester-select">Semester:</label>
          <select id="semester-select" v-model="selectedSemesterId" class="semester-select">
            <option value="">Pilih semester</option>
            <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat nilai...</p>
      </div>

      <div v-else-if="!selectedSemesterId" class="empty-state">
        <p>Pilih semester untuk melihat nilai.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else-if="!grades.length" class="empty-state">
        <p>Belum ada nilai untuk semester ini.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="grades-wrap">
        <div class="grades-actions">
          <button
            type="button"
            class="btn-download"
            :disabled="downloadingRaport"
            @click="downloadRaport"
          >
            {{ downloadingRaport ? 'Mengunduh...' : 'Download Raport (CSV)' }}
          </button>
        </div>
        <div class="table-scroll">
          <table class="grades-table">
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
                    class="tuntas-chip"
                    :class="g.is_tuntas ? 'tuntas' : 'belum'"
                  >{{ g.tuntas_label }}</span>
                  <span v-else>-</span>
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
.page {
  max-width: 100%;
  padding: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.tab-header {
  margin-bottom: 24px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.tab-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.page-header {
  margin-bottom: 24px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.page-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.semester-select-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.semester-select-wrap label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.semester-select {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  color: #0f172a;
  min-width: 180px;
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
  color: #059669;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
}

.back-link:hover {
  text-decoration: underline;
  color: #047857;
}

.grades-wrap {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 20px;
  overflow: hidden;
}

.grades-actions {
  margin-bottom: 16px;
}

.btn-download {
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: box-shadow 0.2s;
}

.btn-download:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.btn-download:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.table-scroll {
  overflow-x: auto;
}

.grades-table {
  width: 100%;
  border-collapse: collapse;
}

.grades-table th,
.grades-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
}

.grades-table th {
  background: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
}

.grades-table td {
  font-size: 14px;
  color: #0f172a;
}

.subject-name {
  font-weight: 600;
}

.penilaian-detail {
  margin-top: 4px;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
}

.nilai-akhir {
  font-weight: 700;
  color: #059669;
}

.pred-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  background: #e2e8f0;
  color: #334155;
}
.pred-chip.pred-A { background: #d1fae5; color: #065f46; }
.pred-chip.pred-B { background: #dbeafe; color: #1e40af; }
.pred-chip.pred-C { background: #fef3c7; color: #92400e; }
.pred-chip.pred-D { background: #fee2e2; color: #991b1b; }

.tuntas-chip {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}
.tuntas-chip.tuntas { background: #d1fae5; color: #065f46; }
.tuntas-chip.belum { background: #fee2e2; color: #991b1b; }

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .grades-table th,
  .grades-table td {
    padding: 10px 8px;
    font-size: 13px;
  }
}
</style>
