<template>
  <Layout>
    <div class="grade-book-page">
      <div v-if="contextLabel" class="context-bar">
        <div class="context-main">
          <span class="context-badge">Buku Nilai</span>
          <strong>{{ contextLabel }}</strong>
          <span v-if="meta.grade" class="context-chip">Tingkat {{ meta.grade }}</span>
        </div>
        <button
          v-if="hasSelection"
          type="button"
          class="btn-secondary btn-compact"
          :disabled="loading"
          @click="loadGrades"
        >
          {{ loading ? 'Memuat...' : 'Muat ulang' }}
        </button>
      </div>

      <div class="filters filters-inline">
        <select v-model="filters.semester_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Semester</option>
          <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
        </select>
        <select v-model="filters.class_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Kelas</option>
          <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>
        <select v-model="filters.subject_id" @change="onFilterChange" class="filter-select">
          <option value="">Pilih Mata Pelajaran</option>
          <option v-for="sub in filteredSubjects" :key="sub.id" :value="String(sub.id)">{{ sub.name }}</option>
        </select>
      </div>

      <div v-if="hasSelection && meta.grade" class="kkm-bar">
        <div class="kkm-info">
          <span class="kkm-title">KKM tingkat {{ meta.grade }}</span>
          <span class="kkm-hint">Berlaku untuk semua kelas tingkat ini pada mapel & semester terpilih</span>
        </div>
        <div v-if="meta.can_set_kkm" class="kkm-form">
          <input
            v-model.number="kkmDraft"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="kkm-input"
            placeholder="0–100"
            aria-label="KKM"
          >
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="savingKkm || kkmDraft === '' || kkmDraft == null"
            @click="saveKkm"
          >
            {{ savingKkm ? 'Menyimpan...' : 'Simpan KKM' }}
          </button>
        </div>
        <div v-else class="kkm-readonly">
          <span class="kkm-value">{{ meta.kkm != null ? meta.kkm : 'Belum diisi' }}</span>
        </div>
      </div>

      <div v-if="hasSelection" class="kkm-bar weight-bar">
        <div class="kkm-info">
          <span class="kkm-title">Bobot nilai akhir</span>
          <span class="kkm-hint">
            Rata penilaian × bobot + UTS × bobot + UAS × bobot (jumlah harus 100%)
            <template v-if="!meta.weights_set"> · default 40 / 30 / 30</template>
          </span>
        </div>
        <div v-if="meta.can_set_weights" class="weight-form-wrap">
          <div class="weight-form">
            <label class="weight-field">
              <span>Penilaian %</span>
              <input
                v-model.number="weightDraft.penilaian"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="kkm-input"
                aria-label="Bobot penilaian"
              >
            </label>
            <label class="weight-field">
              <span>UTS %</span>
              <input
                v-model.number="weightDraft.uts"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="kkm-input"
                aria-label="Bobot UTS"
              >
            </label>
            <label class="weight-field">
              <span>UAS %</span>
              <input
                v-model.number="weightDraft.uas"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="kkm-input"
                aria-label="Bobot UAS"
              >
            </label>
            <span class="weight-sum" :class="{ 'weight-sum-ok': weightSumOk, 'weight-sum-bad': !weightSumOk }">
              Σ {{ weightSumDisplay }}%
            </span>
            <button
              type="button"
              class="btn-primary btn-compact"
              :disabled="savingWeights || !weightSumOk"
              @click="saveWeights"
            >
              {{ savingWeights ? 'Menyimpan...' : 'Simpan Bobot & Deadline' }}
            </button>
          </div>
          <div class="deadline-form">
            <label class="weight-field">
              <span>Deadline Penilaian</span>
              <input v-model="deadlineDraft.penilaian" type="date" class="kkm-input" />
            </label>
            <label class="weight-field">
              <span>Deadline UTS</span>
              <input v-model="deadlineDraft.uts" type="date" class="kkm-input" />
            </label>
            <label class="weight-field">
              <span>Deadline UAS</span>
              <input v-model="deadlineDraft.uas" type="date" class="kkm-input" />
            </label>
            <label class="weight-field">
              <span>Deadline Nilai Akhir</span>
              <input v-model="deadlineDraft.nilai_akhir" type="date" class="kkm-input" />
            </label>
          </div>
        </div>
        <div v-else class="kkm-readonly">
          <span class="kkm-value">
            P {{ meta.weights.penilaian }}% · UTS {{ meta.weights.uts }}% · UAS {{ meta.weights.uas }}%
          </span>
        </div>
      </div>

      <div v-if="!hasSelection" class="empty-state empty-state-hint">
        <p class="empty-desc">Pilih <strong>Semester</strong>, <strong>Kelas</strong>, dan <strong>Mata Pelajaran</strong>. Nilai akan dimuat otomatis.</p>
      </div>

      <div v-else-if="loading && !rows.length" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="10" :columns="10" :cell-widths="['40px', '1fr', '90px', '80px', '80px', '80px', '80px', '100px', '70px', '110px']" />
      </div>

      <div v-else-if="rows.length === 0" class="empty-state">
        <h3 class="empty-title">Belum ada data siswa</h3>
        <p class="empty-desc">Kelas ini belum memiliki siswa atau filter belum sesuai.</p>
      </div>

      <template v-else>
        <div class="table-actions">
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="assessmentCount >= 100"
            @click="addAssessmentColumn"
          >
            + Tambah kolom penilaian
          </button>
          <button
            v-if="assessmentCount > 1"
            type="button"
            class="btn-secondary btn-compact"
            :disabled="!canRemoveLastAssessment"
            :title="canRemoveLastAssessment ? '' : 'Kosongkan nilai di kolom terakhir dulu'"
            @click="removeLastAssessmentColumn"
          >
            Hapus kolom terakhir
          </button>
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="exporting"
            @click="exportToCsv"
          >
            {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
          </button>
          <button
            type="button"
            class="btn-secondary btn-compact"
            :disabled="printingPdf"
            @click="printPdf"
          >
            {{ printingPdf ? 'Menyiapkan PDF...' : 'Cetak PDF' }}
          </button>
          <button
            type="button"
            class="btn-primary btn-compact"
            :disabled="saving"
            @click="saveGrades"
          >
            {{ saving ? 'Menyimpan...' : 'Simpan Nilai' }}
          </button>
        </div>
        <div class="table-container">
          <table class="data-table grade-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th class="col-rank">Peringkat</th>
                <th class="col-name">Nama</th>
                <th class="col-nis">NIS</th>
                <th
                  v-for="n in assessmentIndexes"
                  :key="`h-p-${n}`"
                  class="col-grade"
                >
                  P{{ n }}
                </th>
                <th class="col-grade">Rata P <span class="formula-hint">(otomatis)</span></th>
                <th class="col-grade">UTS</th>
                <th class="col-grade">UAS</th>
                <th class="col-grade">Nilai Akhir <span class="formula-hint">(otomatis)</span></th>
                <th class="col-pred">Predikat</th>
                <th class="col-tuntas">Ketuntasan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in rows" :key="row.student_id">
                <td class="col-no">{{ startIndex + idx + 1 }}</td>
                <td class="col-rank">
                  <span
                    v-if="row.rank != null"
                    class="rank-chip"
                    :class="rankClass(row.rank)"
                  >#{{ row.rank }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="col-name">{{ displayValue(row.student?.name) }}</td>
                <td class="col-nis">{{ displayValue(row.student?.nis || row.student?.nisn) }}</td>
                <td
                  v-for="n in assessmentIndexes"
                  :key="`c-p-${row.student_id}-${n}`"
                  class="col-grade"
                >
                  <input
                    v-model.number="row.penilaian[n]"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade col-readonly">
                  {{ row.rata_penilaian != null ? row.rata_penilaian : '—' }}
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.uts"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.uas"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="computeNilaiAkhirForRow(row)"
                  />
                </td>
                <td class="col-grade">
                  <input
                    v-model.number="row.nilai_akhir"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="grade-input"
                    placeholder="-"
                    @input="() => { refreshKkmStatus(row); refreshRanks() }"
                  />
                </td>
                <td class="col-pred">
                  <span v-if="row.predicate" class="pred-chip" :class="`pred-${row.predicate}`">{{ row.predicate }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td class="col-tuntas">
                  <span
                    v-if="row.is_tuntas === true"
                    class="tuntas-chip tuntas"
                  >Tuntas</span>
                  <span
                    v-else-if="row.is_tuntas === false"
                    class="tuntas-chip belum"
                  >Belum</span>
                  <span v-else class="muted">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="pagination.total > 0" class="pagination-bar">
          <span class="pagination-info">
            Menampilkan
            {{ startIndex + 1 }}–{{ Math.min(startIndex + rows.length, pagination.total) }}
            dari {{ pagination.total }} siswa
          </span>
          <div class="pagination-buttons">
            <button
              type="button"
              class="btn-page"
              :disabled="pagination.current_page <= 1 || loading"
              @click="goToPage(pagination.current_page - 1)"
            >
              Sebelumnya
            </button>
            <span class="page-num">
              Halaman {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              type="button"
              class="btn-page"
              :disabled="pagination.current_page >= pagination.last_page || loading"
              @click="goToPage(pagination.current_page + 1)"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { gradeBookApi } from '@/api/gradeBook'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { semesterApi } from '@/api/semester'
import { institutionApi } from '@/api/institution'
import { employeeApi } from '@/api/teacher'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const route = useRoute()
const authStore = useAuthStore()

const isTeacher = computed(() => {
  const role = authStore.user?.role
  return role === 'teacher' || role === 'staff'
})

const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const printingPdf = ref(false)
const savingKkm = ref(false)
const savingWeights = ref(false)
const applyingFilters = ref(false)
const rows = ref([])
const rankingScores = ref({})
const semesters = ref([])
const classes = ref([])
const subjects = ref([])
const teachingPairs = ref([])
const activeSemesterId = ref('')
const kkmDraft = ref(null)
const assessmentCount = ref(1)
const weightDraft = ref({
  penilaian: 40,
  uts: 30,
  uas: 30,
})
const deadlineDraft = ref({
  penilaian: '',
  uts: '',
  uas: '',
  nilai_akhir: '',
})
const meta = ref({
  grade: null,
  kkm: null,
  kkm_set: false,
  can_set_kkm: false,
  class_name: null,
  weights: { penilaian: 40, uts: 30, uas: 30 },
  weights_set: false,
  can_set_weights: false,
})
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

const filters = ref({
  semester_id: '',
  class_id: '',
  subject_id: '',
})

const hasSelection = computed(() => {
  return Boolean(filters.value.semester_id && filters.value.class_id && filters.value.subject_id)
})

const startIndex = computed(() =>
  Math.max(0, (pagination.value.current_page - 1) * pagination.value.per_page)
)

const assessmentIndexes = computed(() =>
  Array.from({ length: Math.max(1, assessmentCount.value) }, (_, i) => i + 1)
)

const weightSum = computed(() => {
  const p = Number(weightDraft.value.penilaian) || 0
  const u = Number(weightDraft.value.uts) || 0
  const a = Number(weightDraft.value.uas) || 0
  return Math.round((p + u + a) * 100) / 100
})

const weightSumDisplay = computed(() => weightSum.value)
const weightSumOk = computed(() => Math.abs(weightSum.value - 100) < 0.01)

const canRemoveLastAssessment = computed(() => {
  if (assessmentCount.value <= 1) return false
  const n = assessmentCount.value
  return rows.value.every((row) => {
    const v = row.penilaian?.[n]
    return v === '' || v == null || v === undefined
  })
})

const filteredSubjects = computed(() => {
  if (!isTeacher.value || !filters.value.class_id) return subjects.value
  const allowed = new Set(
    teachingPairs.value
      .filter((p) => String(p.class_id) === String(filters.value.class_id))
      .map((p) => String(p.subject_id))
  )
  return subjects.value.filter((s) => allowed.has(String(s.id)))
})

const contextLabel = computed(() => {
  if (!hasSelection.value) return ''
  const subjectName = subjects.value.find((s) => String(s.id) === String(filters.value.subject_id))?.name
  const className = classes.value.find((c) => String(c.id) === String(filters.value.class_id))?.name
  if (subjectName && className) return `${subjectName} — ${className}`
  if (subjectName) return subjectName
  if (className) return className
  return ''
})

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function predicateFromScore(score, kkm) {
  if (score == null || kkm == null || kkm === '') return null
  const k = Number(kkm)
  const s = Number(score)
  if (Number.isNaN(s) || Number.isNaN(k)) return null
  if (s < k) return 'D'
  const band = (100 - k) / 3
  if (band <= 0) return 'A'
  if (s >= k + 2 * band) return 'A'
  if (s >= k + band) return 'B'
  return 'C'
}

function refreshKkmStatus(row) {
  const nilai = toNum(row.nilai_akhir)
  const kkm = meta.value.kkm
  row.predicate = predicateFromScore(nilai, kkm)
  if (nilai == null || kkm == null) {
    row.is_tuntas = null
    row.tuntas_label = null
  } else {
    row.is_tuntas = nilai >= Number(kkm)
    row.tuntas_label = row.is_tuntas ? 'Tuntas' : 'Belum tuntas'
  }
}

function rankClass(rank) {
  if (rank === 1) return 'rank-gold'
  if (rank === 2) return 'rank-silver'
  if (rank === 3) return 'rank-bronze'
  return ''
}

/** Competition ranking: 100,90,90,80 → 1,2,2,4 */
function computeRankMap(scoresByStudentId) {
  const ranked = Object.entries(scoresByStudentId)
    .map(([sid, score]) => ({ sid: String(sid), score: toNum(score) }))
    .filter((x) => x.score != null)
    .sort((a, b) => b.score - a.score)

  const ranks = {}
  Object.keys(scoresByStudentId).forEach((sid) => {
    ranks[String(sid)] = null
  })

  let position = 0
  let prevScore = null
  let prevRank = 0
  ranked.forEach((item) => {
    position += 1
    if (prevScore != null && Math.abs(item.score - prevScore) < 0.00001) {
      ranks[item.sid] = prevRank
    } else {
      ranks[item.sid] = position
      prevRank = position
    }
    prevScore = item.score
  })
  return ranks
}

function refreshRanks() {
  const map = { ...rankingScores.value }
  rows.value.forEach((row) => {
    map[String(row.student_id)] = toNum(row.nilai_akhir)
  })
  const ranks = computeRankMap(map)
  rows.value.forEach((row) => {
    row.rank = ranks[String(row.student_id)] ?? null
  })
}

function averagePenilaian(penilaian) {
  if (!penilaian || typeof penilaian !== 'object') return null
  const vals = []
  for (const n of assessmentIndexes.value) {
    const v = toNum(penilaian[n] ?? penilaian[String(n)])
    if (v != null) vals.push(v)
  }
  if (!vals.length) return null
  return Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 100) / 100
}

function computeNilaiAkhirForRow(row) {
  const rata = averagePenilaian(row.penilaian)
  row.rata_penilaian = rata
  const uts = toNum(row.uts)
  const uas = toNum(row.uas)
  const wp = Number(meta.value.weights?.penilaian ?? 40)
  const wUts = Number(meta.value.weights?.uts ?? 30)
  const wUas = Number(meta.value.weights?.uas ?? 30)

  let ok = true
  let total = 0
  if (wp > 0) {
    if (rata == null) ok = false
    else total += rata * (wp / 100)
  }
  if (wUts > 0) {
    if (uts == null) ok = false
    else total += uts * (wUts / 100)
  }
  if (wUas > 0) {
    if (uas == null) ok = false
    else total += uas * (wUas / 100)
  }
  if (ok && (wp > 0 || wUts > 0 || wUas > 0)) {
    row.nilai_akhir = Math.round(total * 100) / 100
  }
  refreshKkmStatus(row)
  refreshRanks()
}

function normalizePenilaianMap(raw) {
  const map = {}
  const src = raw && typeof raw === 'object' ? raw : {}
  for (let i = 1; i <= Math.max(assessmentCount.value, 1); i++) {
    const v = src[i] ?? src[String(i)]
    map[i] = v === '' || v == null ? null : Number(v)
    if (Number.isNaN(map[i])) map[i] = null
  }
  // Keep any higher indexes that exist in data
  for (const key of Object.keys(src)) {
    const n = Number(key)
    if (!Number.isInteger(n) || n < 1) continue
    if (map[n] !== undefined) continue
    const v = src[key]
    map[n] = v === '' || v == null ? null : Number(v)
    if (Number.isNaN(map[n])) map[n] = null
  }
  return map
}

function normalizeRow(row) {
  const penilaian = normalizePenilaianMap(row.penilaian)
  const next = {
    ...row,
    penilaian,
    uts: row.uts ?? null,
    uas: row.uas ?? null,
    nilai_akhir: row.nilai_akhir ?? null,
  }
  next.rata_penilaian = averagePenilaian(penilaian)
  refreshKkmStatus(next)
  return next
}

function ensurePenilaianSlots() {
  rows.value.forEach((row) => {
    if (!row.penilaian || typeof row.penilaian !== 'object') {
      row.penilaian = {}
    }
    for (const n of assessmentIndexes.value) {
      if (!(n in row.penilaian) && !(String(n) in row.penilaian)) {
        row.penilaian[n] = null
      }
    }
  })
}

function addAssessmentColumn() {
  if (assessmentCount.value >= 100) return
  assessmentCount.value += 1
  ensurePenilaianSlots()
}

function removeLastAssessmentColumn() {
  if (!canRemoveLastAssessment.value) return
  const n = assessmentCount.value
  rows.value.forEach((row) => {
    if (row.penilaian) {
      delete row.penilaian[n]
      delete row.penilaian[String(n)]
    }
    computeNilaiAkhirForRow(row)
  })
  assessmentCount.value = Math.max(1, assessmentCount.value - 1)
}

function resetMeta() {
  meta.value = {
    grade: null,
    kkm: null,
    kkm_set: false,
    can_set_kkm: false,
    class_name: null,
    weights: { penilaian: 40, uts: 30, uas: 30 },
    weights_set: false,
    can_set_weights: false,
  }
  kkmDraft.value = null
  weightDraft.value = { penilaian: 40, uts: 30, uas: 30 }
  deadlineDraft.value = { penilaian: '', uts: '', uas: '', nilai_akhir: '' }
  assessmentCount.value = 1
  rankingScores.value = {}
}

function resetPagination() {
  pagination.value = {
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  }
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) {
    return
  }
  loadGrades(page)
}

function pairExists(classId, subjectId) {
  if (!classId || !subjectId) return false
  return teachingPairs.value.some(
    (p) => String(p.class_id) === String(classId) && String(p.subject_id) === String(subjectId)
  )
}

function resolveDefaultSemesterId(preferredId = '') {
  const preferred = preferredId ? String(preferredId) : ''
  if (preferred && semesters.value.some((s) => String(s.id) === preferred)) {
    return preferred
  }
  if (activeSemesterId.value && semesters.value.some((s) => String(s.id) === String(activeSemesterId.value))) {
    return String(activeSemesterId.value)
  }
  const markedActive = semesters.value.find((s) => s.is_active)
  if (markedActive) return String(markedActive.id)
  return semesters.value.length ? String(semesters.value[0].id) : ''
}

async function onFilterChange() {
  if (applyingFilters.value) return
  rows.value = []
  resetMeta()
  resetPagination()

  if (isTeacher.value) {
    await loadTeachingLoad()
    if (filters.value.class_id && filters.value.subject_id && !pairExists(filters.value.class_id, filters.value.subject_id)) {
      filters.value.subject_id = ''
    }
    if (filters.value.class_id && !classes.value.some((c) => String(c.id) === String(filters.value.class_id))) {
      filters.value.class_id = ''
      filters.value.subject_id = ''
    }
  }

  if (hasSelection.value) {
    await loadGrades(1)
  }
}

async function loadTeachingLoad() {
  if (!isTeacher.value || !filters.value.semester_id) {
    teachingPairs.value = []
    if (isTeacher.value) {
      classes.value = []
      subjects.value = []
    }
    return
  }
  try {
    const res = await employeeApi.getTeachingLoad({ semester_id: filters.value.semester_id })
    const data = res.data?.data ?? res.data ?? {}
    teachingPairs.value = Array.isArray(data.pairs) ? data.pairs : []
    classes.value = Array.isArray(data.classes) ? data.classes : []
    subjects.value = Array.isArray(data.subjects) ? data.subjects : []
  } catch {
    teachingPairs.value = []
    classes.value = []
    subjects.value = []
  }
}

async function loadGrades(page = pagination.value.current_page) {
  if (!hasSelection.value) return
  loading.value = true
  try {
    const res = await gradeBookApi.getByClassSubjectSemester({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
      page,
      per_page: pagination.value.per_page || 20,
    })
    const m = res.data?.meta || {}
    const w = m.weights || {}
    assessmentCount.value = Math.max(1, Number(m.assessment_count) || 1)
    meta.value = {
      grade: m.grade ?? null,
      kkm: m.kkm != null ? Number(m.kkm) : null,
      kkm_set: !!m.kkm_set,
      can_set_kkm: !!m.can_set_kkm,
      class_name: m.class_name || null,
      weights: {
        penilaian: w.penilaian != null ? Number(w.penilaian) : 40,
        uts: w.uts != null ? Number(w.uts) : 30,
        uas: w.uas != null ? Number(w.uas) : 30,
      },
      weights_set: !!w.set,
      can_set_weights: !!m.can_set_weights,
    }
    kkmDraft.value = meta.value.kkm != null ? meta.value.kkm : 75
    weightDraft.value = { ...meta.value.weights }
    const d = m.deadlines || {}
    deadlineDraft.value = {
      penilaian: d.penilaian || '',
      uts: d.uts || '',
      uas: d.uas || '',
      nilai_akhir: d.nilai_akhir || '',
    }
    rankingScores.value = m.ranking_scores && typeof m.ranking_scores === 'object'
      ? { ...m.ranking_scores }
      : {}
    rows.value = (Array.isArray(res.data?.data) ? res.data.data : []).map((r) => normalizeRow(r))
    ensurePenilaianSlots()
    rows.value.forEach((row) => {
      // hitung ulang tanpa refreshRanks di tiap baris — panggil sekali di akhir
      const rata = averagePenilaian(row.penilaian)
      row.rata_penilaian = rata
      const uts = toNum(row.uts)
      const uas = toNum(row.uas)
      const wp = Number(meta.value.weights?.penilaian ?? 40)
      const wUts = Number(meta.value.weights?.uts ?? 30)
      const wUas = Number(meta.value.weights?.uas ?? 30)
      let ok = true
      let total = 0
      if (wp > 0) {
        if (rata == null) ok = false
        else total += rata * (wp / 100)
      }
      if (wUts > 0) {
        if (uts == null) ok = false
        else total += uts * (wUts / 100)
      }
      if (wUas > 0) {
        if (uas == null) ok = false
        else total += uas * (wUas / 100)
      }
      if (ok && (wp > 0 || wUts > 0 || wUas > 0)) {
        row.nilai_akhir = Math.round(total * 100) / 100
      }
      refreshKkmStatus(row)
    })
    refreshRanks()
    pagination.value = {
      current_page: m.current_page ?? page,
      last_page: m.last_page ?? 1,
      per_page: m.per_page ?? 20,
      total: m.total ?? rows.value.length,
    }
  } catch (e) {
    toast.error('Gagal memuat buku nilai', e.formattedMessage || 'Data buku nilai tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    rows.value = []
    resetMeta()
    resetPagination()
  } finally {
    loading.value = false
  }
}

async function saveKkm() {
  if (!hasSelection.value || !meta.value.grade) return
  const value = kkmDraft.value
  if (value === '' || value == null || Number(value) < 0 || Number(value) > 100) {
    toast.error('Gagal', 'KKM harus antara 0–100')
    return
  }
  savingKkm.value = true
  try {
    await gradeBookApi.upsertKkm({
      semester_id: filters.value.semester_id,
      subject_id: filters.value.subject_id,
      class_id: filters.value.class_id,
      grade: meta.value.grade,
      kkm: Number(value),
    })
    meta.value.kkm = Number(value)
    meta.value.kkm_set = true
    rows.value.forEach((row) => refreshKkmStatus(row))
    toast.success('KKM disimpan', `KKM tingkat ${meta.value.grade} = ${Number(value)}`)
  } catch (e) {
    toast.error('Gagal menyimpan KKM', e.formattedMessage || 'KKM tidak dapat disimpan.')
  } finally {
    savingKkm.value = false
  }
}

async function saveWeights() {
  if (!hasSelection.value) return
  if (!weightSumOk.value) {
    toast.error('Gagal', 'Jumlah bobot harus 100%')
    return
  }
  savingWeights.value = true
  try {
    const res = await gradeBookApi.upsertWeights({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
      weight_penilaian: Number(weightDraft.value.penilaian),
      weight_uts: Number(weightDraft.value.uts),
      weight_uas: Number(weightDraft.value.uas),
      assessment_count: assessmentCount.value,
      deadline_penilaian: deadlineDraft.value.penilaian || null,
      deadline_uts: deadlineDraft.value.uts || null,
      deadline_uas: deadlineDraft.value.uas || null,
      deadline_nilai_akhir: deadlineDraft.value.nilai_akhir || null,
    })
    meta.value.weights = {
      penilaian: Number(weightDraft.value.penilaian),
      uts: Number(weightDraft.value.uts),
      uas: Number(weightDraft.value.uas),
    }
    meta.value.weights_set = true
    const recalculated = Number(res.data?.data?.recalculated_students ?? 0)
    await loadGrades()
    toast.success(
      'Bobot & deadline disimpan',
      recalculated > 0
        ? `P ${meta.value.weights.penilaian}% · UTS ${meta.value.weights.uts}% · UAS ${meta.value.weights.uas}% · ${recalculated} nilai akhir dihitung ulang`
        : `P ${meta.value.weights.penilaian}% · UTS ${meta.value.weights.uts}% · UAS ${meta.value.weights.uas}%`
    )
  } catch (e) {
    toast.error('Gagal menyimpan bobot', e.formattedMessage || 'Bobot tidak dapat disimpan.')
  } finally {
    savingWeights.value = false
  }
}

async function exportToCsv() {
  if (!hasSelection.value) return
  exporting.value = true
  try {
    const res = await gradeBookApi.export({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
    })
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `buku-nilai-${new Date().toISOString().slice(0, 10)}.csv`)
    link.click()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor buku nilai', e.formattedMessage || 'Data tidak dapat diekspor. Periksa koneksi dan coba lagi.')
  } finally {
    exporting.value = false
  }
}

async function printPdf() {
  if (!hasSelection.value) return
  printingPdf.value = true
  try {
    const res = await gradeBookApi.exportPdf({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
    })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Buku Nilai — ${contextLabel.value || 'Buku Nilai'}`
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
        .toolbar {
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
          padding: 10px 14px; background: #0f172a; color: #f8fafc;
          border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
        }
        .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
        .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
        .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>${title}<span class="hint">Preview cetak</span></h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
  } catch (e) {
    toast.error('Gagal mencetak PDF', e.formattedMessage || 'PDF buku nilai tidak dapat dibuka.')
  } finally {
    printingPdf.value = false
  }
}

function toNum(v) {
  if (v === '' || v == null || v === undefined) return null
  const n = Number(v)
  return !isNaN(n) ? n : null
}

async function saveGrades() {
  if (!hasSelection.value || rows.value.length === 0) return
  saving.value = true
  try {
    const grades = rows.value.map((row) => {
      const penilaian = {}
      for (const n of assessmentIndexes.value) {
        const v = toNum(row.penilaian?.[n] ?? row.penilaian?.[String(n)])
        if (v != null) penilaian[String(n)] = v
      }
      const uts = toNum(row.uts)
      const uas = toNum(row.uas)
      computeNilaiAkhirForRow(row)
      return {
        student_id: row.student_id,
        penilaian,
        uts,
        uas,
        nilai_akhir: toNum(row.nilai_akhir),
      }
    })
    await gradeBookApi.bulkSave({
      semester_id: filters.value.semester_id,
      class_id: filters.value.class_id,
      subject_id: filters.value.subject_id,
      assessment_count: assessmentCount.value,
      grades,
    })
    toast.success('Nilai berhasil disimpan')
    await loadGrades()
  } catch (e) {
    toast.error('Gagal menyimpan nilai', e.formattedMessage || 'Nilai tidak dapat disimpan. Periksa data dan coba lagi.')
  } finally {
    saving.value = false
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 200 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

async function loadActiveSemester() {
  try {
    const fromAuth = authStore.activeInstitution?.active_semester_id
      || authStore.user?.institution?.active_semester_id
    if (fromAuth) {
      activeSemesterId.value = String(fromAuth)
      return
    }
    const res = await institutionApi.getMy()
    const inst = res.data?.data || res.data || null
    if (inst?.active_semester_id) {
      activeSemesterId.value = String(inst.active_semester_id)
    }
  } catch {
    // ignore — fallback ke semester pertama
  }
}

async function loadClasses() {
  if (isTeacher.value) return
  try {
    const res = await classApi.getAll({ per_page: 200 })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadSubjects() {
  if (isTeacher.value) return
  try {
    const res = await subjectApi.getAll({ per_page: 200 })
    subjects.value = res.data.data || []
  } catch {
    subjects.value = []
  }
}

async function applyRouteFilters({ autoLoad = true } = {}) {
  applyingFilters.value = true
  try {
    const q = route.query
    const nextSemester = resolveDefaultSemesterId(q.semester_id ? String(q.semester_id) : filters.value.semester_id)
    filters.value.semester_id = nextSemester

    if (isTeacher.value) {
      await loadTeachingLoad()
    } else if (!classes.value.length || !subjects.value.length) {
      await Promise.all([loadClasses(), loadSubjects()])
    }

    if (q.class_id) filters.value.class_id = String(q.class_id)
    if (q.subject_id) filters.value.subject_id = String(q.subject_id)

    if (isTeacher.value && filters.value.class_id && filters.value.subject_id) {
      if (!pairExists(filters.value.class_id, filters.value.subject_id)) {
        // Tetap biarkan nilai query; backend yang validasi. Jangan hapus diam-diam.
      }
    }

    if (autoLoad && hasSelection.value) {
      await loadGrades(1)
    } else if (!hasSelection.value) {
      rows.value = []
      resetMeta()
      resetPagination()
    }
  } finally {
    applyingFilters.value = false
  }
}

watch(
  () => [route.query.semester_id, route.query.class_id, route.query.subject_id],
  async () => {
    if (applyingFilters.value) return
    await applyRouteFilters({ autoLoad: true })
  }
)

onMounted(async () => {
  await Promise.all([loadSemesters(), loadActiveSemester()])
  if (!isTeacher.value) {
    await Promise.all([loadClasses(), loadSubjects()])
  }
  await applyRouteFilters({ autoLoad: true })
})
</script>

<style scoped>
.grade-book-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.context-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.context-main {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex-wrap: wrap;
}

.context-badge {
  display: inline-flex;
  padding: 3px 8px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.context-main strong {
  font-size: 14px;
  color: #0f172a;
}

.context-chip {
  display: inline-flex;
  padding: 3px 8px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
}

.kkm-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 12px;
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #d1fae5;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(5, 150, 105, 0.06);
}

.kkm-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.kkm-title {
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
}

.kkm-hint {
  font-size: 12px;
  color: #64748b;
}

.kkm-form {
  display: flex;
  align-items: center;
  gap: 8px;
}

.kkm-input {
  width: 88px;
  padding: 0.45rem 0.6rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  text-align: center;
}

.kkm-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.kkm-readonly .kkm-value {
  font-size: 14px;
  font-weight: 700;
  color: #047857;
}

.weight-bar {
  border-color: #bfdbfe;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.06);
}

.weight-bar .kkm-title {
  color: #1e40af;
}

.weight-form {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.weight-form-wrap {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.deadline-form {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  flex-wrap: wrap;
  padding-top: 4px;
  border-top: 1px dashed #dbeafe;
}

.weight-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}

.weight-sum {
  font-size: 13px;
  font-weight: 700;
  padding: 0.45rem 0.5rem;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
}

.weight-sum-ok {
  background: #ecfdf5;
  color: #047857;
}

.weight-sum-bad {
  background: #fef2f2;
  color: #b91c1c;
}

.col-readonly {
  text-align: center;
  font-weight: 600;
  color: #334155;
  font-variant-numeric: tabular-nums;
}

.page-header {
  margin-bottom: 1.5rem;
}
.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.header-icon {
  flex-shrink: 0;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}
.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}
.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
  align-items: center;
}
.filter-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  min-width: 160px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}
.loading-wrap {
  width: 100%;
  margin: 1rem 0;
}
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px dashed #e2e8f0;
}
.empty-state-hint {
  padding: 1.5rem;
}
.empty-title {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}
.empty-desc {
  color: #64748b;
  margin: 0;
}
.table-actions {
  margin-bottom: 1rem;
}
.table-container {
  overflow-x: auto;
}
.data-table.grade-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table.grade-table th,
.data-table.grade-table td {
  padding: 0.5rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.data-table.grade-table th {
  font-weight: 600;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  color: #065f46;
}
.col-no {
  width: 40px;
  text-align: center;
}
.col-rank {
  width: 72px;
  text-align: center;
}
.rank-chip {
  display: inline-flex;
  min-width: 36px;
  justify-content: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: #f1f5f9;
  color: #334155;
}
.rank-gold { background: #fef3c7; color: #b45309; }
.rank-silver { background: #e2e8f0; color: #475569; }
.rank-bronze { background: #ffedd5; color: #c2410c; }
.col-name {
  min-width: 160px;
}
.col-nis {
  width: 100px;
}
.col-grade {
  width: 90px;
}
.col-pred {
  width: 72px;
  text-align: center;
}
.col-tuntas {
  width: 110px;
  text-align: center;
}
.pred-chip {
  display: inline-flex;
  min-width: 28px;
  justify-content: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}
.pred-A { background: #ecfdf5; color: #047857; }
.pred-B { background: #eff6ff; color: #1d4ed8; }
.pred-C { background: #fffbeb; color: #b45309; }
.pred-D { background: #fef2f2; color: #b91c1c; }
.tuntas-chip {
  display: inline-flex;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}
.tuntas-chip.tuntas { background: #ecfdf5; color: #047857; }
.tuntas-chip.belum { background: #fef2f2; color: #b91c1c; }
.muted { color: #94a3b8; }
.formula-hint {
  font-size: 0.75rem;
  font-weight: normal;
  color: #64748b;
}
.grade-input {
  width: 100%;
  max-width: 80px;
  padding: 0.4rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: center;
}
.grade-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}
.btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid #e2e8f0;
  background: #f1f5f9;
  color: #475569;
}
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 0.9rem;
  padding-top: 0.85rem;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-num {
  font-size: 0.85rem;
  color: #475569;
  font-weight: 600;
}

.btn-page {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-page:hover:not(:disabled) {
  border-color: #34d399;
  color: #065f46;
  background: #ecfdf5;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
