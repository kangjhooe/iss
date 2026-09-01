<template>    <div class="page">
      <div class="page-header">
        <div class="page-header-main">
          <router-link to="/teacher/dashboard" class="back-chip">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Dashboard
          </router-link>
          <div>
            <h1>Poin & Prestasi Saya</h1>
            <p class="page-subtitle">Ringkasan apresiasi pada periode berjalan</p>
          </div>
        </div>
        <button type="button" class="btn-primary" @click="showSubmit = true">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Usulkan Prestasi
        </button>
      </div>

      <div class="filters-bar">
        <div class="filter-group">
          <label for="year-filter">Tahun Ajaran</label>
          <select id="year-filter" v-model="period.academic_year_id" class="filter-select" @change="onPeriodChange">
            <option value="">Semua Tahun</option>
            <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name || y.code }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="semester-filter">Semester</label>
          <select id="semester-filter" v-model="period.semester_id" class="filter-select" @change="reload">
            <option value="">Semua Semester</option>
            <option v-for="s in semesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="state-card">
        <div class="loading-spinner" aria-hidden="true"></div>
        <p>Memuat data poin...</p>
      </div>

      <div v-else-if="!summary" class="state-card empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3>Belum dapat memuat data poin</h3>
        <p>Coba ubah filter periode atau muat ulang halaman.</p>
        <button type="button" class="btn-primary" @click="reload">Coba lagi</button>
      </div>

      <template v-else>
        <!-- Hero score -->
        <section class="hero-card">
          <div class="hero-main">
            <span class="hero-label">Total Neto</span>
            <div class="hero-score">{{ summary.total_points ?? 0 }}</div>
            <p class="hero-desc">Prestasi dikurangi pelanggaran pada periode terpilih</p>
          </div>
          <div class="hero-side">
            <div class="hero-rank">
              <span class="hero-rank-label">Peringkat</span>
              <span class="hero-rank-value">{{ summary.rank ? `#${summary.rank}` : '-' }}</span>
            </div>
            <div v-if="summary.matched_reward" class="hero-reward">
              <span class="hero-reward-badge">Reward</span>
              <strong>{{ summary.matched_reward.reward_name }}</strong>
              <span class="hero-reward-range">
                {{ summary.matched_reward.point_min }} – {{ summary.matched_reward.point_max }} poin
              </span>
            </div>
          </div>
        </section>

        <!-- Stat cards -->
        <div class="stats-grid">
          <div class="stat-card stat-ok">
            <div class="stat-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <span class="stat-label">Poin Prestasi</span>
              <span class="stat-value ok">+{{ summary.achievement_points ?? 0 }}</span>
            </div>
          </div>
          <div class="stat-card stat-warn">
            <div class="stat-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <span class="stat-label">Poin Pelanggaran</span>
              <span class="stat-value minus">−{{ summary.violation_points ?? 0 }}</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>
            <div>
              <span class="stat-label">Riwayat Prestasi</span>
              <span class="stat-value">{{ achievements.length }}</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon neutral">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <span class="stat-label">Riwayat Pelanggaran</span>
              <span class="stat-value">{{ myViolations.length }}</span>
            </div>
          </div>
        </div>

        <div v-if="summary.matched_reward?.description" class="reward-banner">
          <div>
            <strong>{{ summary.matched_reward.reward_name }}</strong>
            <p>{{ summary.matched_reward.description }}</p>
          </div>
        </div>

        <div class="content-grid">
          <!-- Leaderboard -->
          <section class="panel leaderboard-panel">
            <div class="panel-head">
              <div>
                <h2>{{ leaderboardTitle }}</h2>
                <p class="panel-sub">{{ leaderboardSubtitle }}</p>
              </div>
            </div>

            <div v-if="leaderboardLoading" class="inline-loading">Memuat peringkat...</div>
            <div v-else-if="!leaderboard.length" class="inline-empty">Belum ada ranking pada periode ini.</div>
            <ol v-else class="leaderboard">
              <li
                v-for="row in leaderboard"
                :key="row.employee_id"
                :class="{ me: isMe(row) }"
              >
                <span class="rank" :class="rankClass(row.rank)">#{{ row.rank }}</span>
                <div class="who">
                  <div class="who-name">
                    <strong>{{ row.employee?.name }}</strong>
                    <span v-if="isMe(row)" class="you-badge">Anda</span>
                  </div>
                  <div class="who-meta">{{ row.employee?.subject || row.employee?.type || '-' }}</div>
                </div>
                <div class="lb-points">
                  <strong class="neto">{{ row.total_points }}</strong>
                  <span class="breakdown">+{{ row.achievement_points || 0 }} / −{{ row.violation_points || 0 }}</span>
                </div>
              </li>
            </ol>
          </section>

          <!-- Categories -->
          <aside class="side-stack">
            <section class="panel">
              <div class="panel-head">
                <h2>Prestasi per Kategori</h2>
              </div>
              <div v-if="!summary.by_category?.length" class="inline-empty">Belum ada poin prestasi.</div>
              <ul v-else class="category-list">
                <li v-for="c in summary.by_category" :key="'a-'+c.category">
                  <div class="cat-main">
                    <span class="cat-name">{{ categoryLabel(c.category) }}</span>
                    <span class="cat-count">{{ c.count }} item</span>
                  </div>
                  <strong class="cat-points ok">+{{ c.points }}</strong>
                </li>
              </ul>
            </section>

            <section class="panel">
              <div class="panel-head">
                <h2>Pelanggaran per Kategori</h2>
              </div>
              <div v-if="!summary.violations_by_category?.length" class="inline-empty">Belum ada poin pelanggaran.</div>
              <ul v-else class="category-list">
                <li v-for="c in summary.violations_by_category" :key="'v-'+c.category">
                  <div class="cat-main">
                    <span class="cat-name">{{ violationCategoryLabel(c.category) }}</span>
                    <span class="cat-count">{{ c.count }} item</span>
                  </div>
                  <strong class="cat-points minus">−{{ c.points }}</strong>
                </li>
              </ul>
            </section>
          </aside>
        </div>

        <!-- History tabs -->
        <section class="panel history-panel">
          <div class="panel-head history-head">
            <div class="tabs" role="tablist">
              <button
                type="button"
                role="tab"
                class="tab"
                :class="{ active: historyTab === 'achievements' }"
                :aria-selected="historyTab === 'achievements'"
                @click="historyTab = 'achievements'"
              >
                Riwayat Prestasi
                <span class="tab-count">{{ achievements.length }}</span>
              </button>
              <button
                type="button"
                role="tab"
                class="tab"
                :class="{ active: historyTab === 'violations' }"
                :aria-selected="historyTab === 'violations'"
                @click="historyTab = 'violations'"
              >
                Riwayat Pelanggaran
                <span class="tab-count">{{ myViolations.length }}</span>
              </button>
            </div>
            <select
              v-if="historyTab === 'achievements'"
              v-model="statusFilter"
              class="filter-select"
              @change="loadAchievements"
            >
              <option value="">Semua status</option>
              <option value="approved">Disetujui</option>
              <option value="pending">Menunggu</option>
              <option value="rejected">Ditolak</option>
            </select>
            <select
              v-else
              v-model="violationStatusFilter"
              class="filter-select"
              @change="loadViolations"
            >
              <option value="">Semua status</option>
              <option value="approved">Disetujui</option>
              <option value="pending">Menunggu</option>
              <option value="rejected">Ditolak</option>
            </select>
          </div>

          <div v-if="historyTab === 'achievements'">
            <div v-if="!achievements.length" class="inline-empty">Belum ada riwayat prestasi.</div>
            <ul v-else class="history">
              <li v-for="a in achievements" :key="a.id" class="history-item">
                <div class="history-body">
                  <strong>{{ a.title || a.achievement_type?.name }}</strong>
                  <div class="history-meta">
                    {{ formatDate(a.achievement_date) }}
                    · {{ categoryLabel(a.achievement_type?.category) }}
                    · {{ levelLabel(a.level) }}
                  </div>
                  <div v-if="a.status === 'rejected' && a.review_notes" class="reject-note">
                    Alasan: {{ a.review_notes }}
                  </div>
                  <a v-if="a.evidence_url" :href="a.evidence_url" target="_blank" rel="noopener" class="link">
                    Lihat bukti →
                  </a>
                </div>
                <div class="history-right">
                  <span class="pts">+{{ a.point_value }}</span>
                  <span :class="['status', a.status]">{{ statusLabel(a.status) }}</span>
                </div>
              </li>
            </ul>
          </div>

          <div v-else>
            <div v-if="!myViolations.length" class="inline-empty">Belum ada riwayat pelanggaran.</div>
            <ul v-else class="history">
              <li v-for="v in myViolations" :key="v.id" class="history-item">
                <div class="history-body">
                  <strong>{{ v.violation_type?.name || 'Pelanggaran' }}</strong>
                  <div class="history-meta">
                    {{ formatDate(v.violation_date) }}
                    · {{ violationCategoryLabel(v.violation_type?.category) }}
                  </div>
                  <div v-if="v.status === 'rejected' && v.review_notes" class="reject-note">
                    Alasan: {{ v.review_notes }}
                  </div>
                  <a v-if="v.evidence_url" :href="v.evidence_url" target="_blank" rel="noopener" class="link">
                    Lihat bukti →
                  </a>
                </div>
                <div class="history-right">
                  <span class="pts minus">−{{ v.point_value }}</span>
                  <span :class="['status', v.status]">{{ statusLabel(v.status) }}</span>
                </div>
              </li>
            </ul>
          </div>
        </section>
      </template>

      <!-- Submit modal -->
      <div
        v-if="showSubmit"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="submit-title"
        @click.self="showSubmit = false"
      >
        <div class="modal">
          <div class="modal-header">
            <div>
              <h3 id="submit-title">Usulkan Prestasi</h3>
              <p class="modal-desc">Usulan ditinjau kepala sekolah / admin sebelum masuk ke skor.</p>
            </div>
            <button type="button" class="modal-close" aria-label="Tutup" @click="showSubmit = false">×</button>
          </div>
          <form @submit.prevent="submitAchievement" class="modal-form">
            <label>
              Jenis Prestasi
              <select v-model="form.achievement_type_id" required @change="onTypeChange">
                <option value="">Pilih jenis</option>
                <option v-for="t in types" :key="t.id" :value="String(t.id)">
                  {{ t.name }} ({{ t.point_value }} poin)
                </option>
              </select>
            </label>
            <label>
              Judul
              <input v-model="form.title" type="text" placeholder="Contoh: Pembimbing OSN Matematika" />
            </label>
            <div class="form-row">
              <label>
                Tanggal
                <input v-model="form.achievement_date" type="date" required />
              </label>
              <label>
                Level
                <select v-model="form.level" @change="onTypeChange">
                  <option value="">Tanpa multiplier</option>
                  <option value="sekolah">Sekolah</option>
                  <option value="kabupaten">Kabupaten</option>
                  <option value="provinsi">Provinsi</option>
                  <option value="nasional">Nasional</option>
                  <option value="internasional">Internasional</option>
                </select>
              </label>
            </div>
            <label>
              Perkiraan Poin
              <input v-model.number="form.point_value" type="number" min="0" />
            </label>
            <label>
              Catatan
              <textarea v-model="form.notes" rows="2" placeholder="Opsional"></textarea>
            </label>
            <label>
              Bukti (opsional)
              <input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onFile" />
            </label>
            <p v-if="formError" class="error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showSubmit = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Mengirim...' : 'Kirim Usulan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { academicYearApi } from '@/api/academicYear'
import { semesterApi } from '@/api/semester'
import { institutionApi } from '@/api/institution'
import { myTeacherAppreciationApi } from '@/api/teacherAppreciation'

const loading = ref(true)
const saving = ref(false)
const showSubmit = ref(false)
const summary = ref(null)
const achievements = ref([])
const myViolations = ref([])
const leaderboard = ref([])
const leaderboardLoading = ref(false)
const leaderboardMode = ref('guru_only')
const leaderboardGroup = ref(null)
const myEmployeeId = ref(null)
const types = ref([])
const academicYears = ref([])
const semesters = ref([])
const statusFilter = ref('')
const violationStatusFilter = ref('')
const formError = ref('')
const evidenceFile = ref(null)
const historyTab = ref('achievements')

const period = reactive({ academic_year_id: '', semester_id: '' })
const form = reactive({
  achievement_type_id: '',
  title: '',
  achievement_date: new Date().toISOString().slice(0, 10),
  level: '',
  point_value: 0,
  notes: '',
})

const leaderboardTitle = computed(() => {
  if (leaderboardMode.value === 'combined') return 'Leaderboard Pegawai'
  if (leaderboardMode.value === 'separated') {
    return leaderboardGroup.value === 'staff' ? 'Leaderboard Staff' : 'Leaderboard Guru'
  }
  return 'Leaderboard Guru'
})
const leaderboardSubtitle = computed(() => {
  const top = `Skor neto · Top ${leaderboard.value.length || 50}`
  if (leaderboardMode.value === 'separated') {
    return `${top} · ranking terpisah`
  }
  if (leaderboardMode.value === 'combined') {
    return `${top} · guru & staff digabung`
  }
  return top
})

function categoryLabel(c) {
  return ({ akademik: 'Akademik', pengembangan: 'Pengembangan', pengabdian: 'Pengabdian', inovasi: 'Inovasi', kedisiplinan: 'Kedisiplinan' })[c] || c || '-'
}
function violationCategoryLabel(c) {
  return ({ kehadiran: 'Kehadiran', kedisiplinan: 'Kedisiplinan', administrasi: 'Administrasi', lainnya: 'Lainnya' })[c] || c || '-'
}
function levelLabel(l) {
  return ({ sekolah: 'Sekolah', kabupaten: 'Kabupaten', provinsi: 'Provinsi', nasional: 'Nasional', internasional: 'Internasional' })[l] || '-'
}
function statusLabel(s) {
  return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' })[s] || s
}
function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

function isMe(row) {
  const id = myEmployeeId.value ?? summary.value?.employee?.id
  return id != null && Number(row.employee_id) === Number(id)
}

function rankClass(rank) {
  if (rank === 1) return 'gold'
  if (rank === 2) return 'silver'
  if (rank === 3) return 'bronze'
  return ''
}

function periodParams() {
  return { academic_year_id: period.academic_year_id, semester_id: period.semester_id }
}

async function onPeriodChange() {
  if (period.academic_year_id) {
    try {
      const res = await semesterApi.getByAcademicYear(period.academic_year_id)
      semesters.value = res.data?.data || res.data || []
    } catch {
      semesters.value = []
    }
  }
  await reload()
}

async function reload() {
  loading.value = true
  try {
    const [sumRes] = await Promise.all([
      myTeacherAppreciationApi.getSummary(periodParams()),
      loadAchievements(),
      loadViolations(),
      loadLeaderboard(),
    ])
    summary.value = sumRes.data?.data || null
    if (summary.value?.employee?.id) {
      myEmployeeId.value = summary.value.employee.id
    }
  } catch {
    summary.value = null
  } finally {
    loading.value = false
  }
}

async function loadLeaderboard() {
  leaderboardLoading.value = true
  try {
    const res = await myTeacherAppreciationApi.getLeaderboard({
      ...periodParams(),
      limit: 50,
    })
    leaderboard.value = res.data?.data || []
    leaderboardMode.value = res.data?.meta?.mode || 'guru_only'
    leaderboardGroup.value = res.data?.meta?.group || null
    if (res.data?.meta?.my_employee_id) {
      myEmployeeId.value = res.data.meta.my_employee_id
    }
  } catch {
    leaderboard.value = []
  } finally {
    leaderboardLoading.value = false
  }
}

async function loadAchievements() {
  try {
    const res = await myTeacherAppreciationApi.getAchievements({
      ...periodParams(),
      status: statusFilter.value || undefined,
      per_page: 50,
    })
    achievements.value = res.data?.data || []
  } catch {
    achievements.value = []
  }
}

async function loadViolations() {
  try {
    const res = await myTeacherAppreciationApi.getViolations({
      ...periodParams(),
      status: violationStatusFilter.value || undefined,
      per_page: 50,
    })
    myViolations.value = res.data?.data || []
  } catch {
    myViolations.value = []
  }
}

function onTypeChange() {
  const type = types.value.find((t) => String(t.id) === String(form.achievement_type_id))
  if (!type) return
  const factor = form.level ? Number((type.level_multipliers || {})[form.level] || 1) : 1
  form.point_value = Math.round(Number(type.point_value || 0) * factor)
  if (!form.title) form.title = type.name
}

function onFile(e) {
  evidenceFile.value = e.target.files?.[0] || null
}

async function submitAchievement() {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      achievement_type_id: form.achievement_type_id,
      title: form.title,
      achievement_date: form.achievement_date,
      level: form.level || undefined,
      point_value: form.point_value,
      notes: form.notes,
      ...periodParams(),
    }
    if (evidenceFile.value) payload.evidence = evidenceFile.value
    await myTeacherAppreciationApi.submit(payload)
    showSubmit.value = false
    Object.assign(form, {
      achievement_type_id: '',
      title: '',
      achievement_date: new Date().toISOString().slice(0, 10),
      level: '',
      point_value: 0,
      notes: '',
    })
    evidenceFile.value = null
    await reload()
  } catch (e) {
    formError.value = e.formattedMessage || 'Gagal mengirim usulan'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const [yearsRes, typesRes, instRes] = await Promise.all([
      academicYearApi.getAll(),
      myTeacherAppreciationApi.getTypes(),
      institutionApi.getMy().catch(() => null),
    ])
    academicYears.value = yearsRes.data?.data || yearsRes.data || []
    types.value = typesRes.data?.data || []
    const institution = instRes ? (instRes.data?.data || instRes.data || null) : null
    const instYearId = institution?.active_academic_year_id
    const instSemId = institution?.active_semester_id
    if (instYearId) {
      const activeYear = academicYears.value.find((y) => String(y.id) === String(instYearId))
        || academicYears.value.find((y) => y.is_active)
        || academicYears.value[0]
      if (activeYear) {
        period.academic_year_id = String(activeYear.id)
        const semRes = await semesterApi.getByAcademicYear(activeYear.id)
        semesters.value = semRes.data?.data || semRes.data || []
        const activeSem = (instSemId
          ? semesters.value.find((s) => String(s.id) === String(instSemId))
          : null)
          || semesters.value.find((s) => s.is_active)
          || semesters.value[0]
        if (activeSem) period.semester_id = String(activeSem.id)
      }
    } else if (academicYears.value.length) {
      const fallbackYear = academicYears.value.find((y) => y.is_active) || academicYears.value[0]
      if (fallbackYear) {
        const semRes = await semesterApi.getByAcademicYear(fallbackYear.id)
        semesters.value = semRes.data?.data || semRes.data || []
      }
    }
  } catch {
    /* continue */
  }
  await reload()
})
</script>

<style scoped>
.page {
  width: 100%;
  max-width: 100%;
  padding: 0 0 8px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.page-header-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.back-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: fit-content;
  padding: 6px 10px;
  border-radius: 8px;
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  color: #047857;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s;
}

.back-chip:hover {
  background: #d1fae5;
}

.page-header h1 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.3px;
}

.page-subtitle {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: box-shadow 0.2s, filter 0.15s;
  white-space: nowrap;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
  filter: brightness(1.02);
}

.btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.btn-secondary {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 10px;
  padding: 10px 14px;
  cursor: pointer;
  font-weight: 600;
  font-size: 13px;
  color: #334155;
}

.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 12px 14px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 180px;
  flex: 1;
}

.filter-group label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.filter-select {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  background: #fff;
  font-size: 13px;
  color: #0f172a;
}

.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.state-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 48px 20px;
  text-align: center;
  color: #64748b;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.state-card.empty h3 {
  margin: 0;
  color: #334155;
  font-size: 16px;
}

.state-card.empty p {
  margin: 0 0 8px;
  font-size: 13px;
}

.loading-spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #d1fae5;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.hero-card {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 20px;
  padding: 22px 24px;
  margin-bottom: 14px;
  border-radius: 16px;
  background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
  flex-wrap: wrap;
}

.hero-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.85;
  margin-bottom: 4px;
}

.hero-score {
  font-size: 48px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -1px;
  margin-bottom: 6px;
}

.hero-desc {
  margin: 0;
  font-size: 13px;
  opacity: 0.9;
}

.hero-side {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 180px;
}

.hero-rank {
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 12px;
  padding: 12px 14px;
  text-align: center;
}

.hero-rank-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  opacity: 0.85;
  margin-bottom: 2px;
}

.hero-rank-value {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.hero-reward {
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.hero-reward-badge {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.85;
}

.hero-reward strong {
  font-size: 14px;
}

.hero-reward-range {
  font-size: 12px;
  opacity: 0.85;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  border-left: 4px solid #94a3b8;
}

.stat-card.stat-ok { border-left-color: #10b981; }
.stat-card.stat-warn { border-left-color: #f59e0b; }

.stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(5, 150, 105, 0.12);
  color: #059669;
}

.stat-warn .stat-icon {
  background: rgba(245, 158, 11, 0.12);
  color: #d97706;
}

.stat-icon.neutral {
  background: rgba(100, 116, 139, 0.1);
  color: #64748b;
}

.stat-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 2px;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.3px;
}

.ok { color: #047857; }
.minus { color: #b91c1c; }

.reward-banner {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  padding: 14px 16px;
  margin-bottom: 16px;
}

.reward-banner strong {
  display: block;
  color: #1e40af;
  font-size: 14px;
  margin-bottom: 2px;
}

.reward-banner p {
  margin: 0;
  font-size: 13px;
  color: #3b82f6;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(260px, 0.7fr);
  gap: 14px;
  margin-bottom: 14px;
  align-items: start;
}

.side-stack {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 18px 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

.panel-head {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: flex-start;
  margin-bottom: 12px;
}

.panel-head h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.panel-sub {
  margin: 2px 0 0;
  font-size: 12px;
  color: #94a3b8;
}

.inline-loading,
.inline-empty {
  padding: 20px 8px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

.leaderboard {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 520px;
  overflow: auto;
}

.leaderboard li {
  display: grid;
  grid-template-columns: 44px 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 11px 8px;
  border-bottom: 1px solid #f1f5f9;
  border-radius: 10px;
}

.leaderboard li:last-child {
  border-bottom: none;
}

.leaderboard li.me {
  background: #ecfdf5;
  border: 1px solid #bbf7d0;
  margin: 4px 0;
  padding: 11px 10px;
}

.leaderboard .rank {
  font-weight: 800;
  font-size: 14px;
  color: #64748b;
  text-align: center;
}

.leaderboard .rank.gold { color: #ca8a04; }
.leaderboard .rank.silver { color: #64748b; }
.leaderboard .rank.bronze { color: #c2410c; }

.who-name {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.who-name strong {
  font-size: 14px;
  color: #0f172a;
}

.who-meta {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}

.you-badge {
  font-size: 10px;
  font-weight: 700;
  background: #059669;
  color: #fff;
  border-radius: 999px;
  padding: 2px 7px;
}

.lb-points {
  text-align: right;
}

.lb-points .neto {
  display: block;
  font-size: 16px;
  color: #0f172a;
}

.lb-points .breakdown {
  display: block;
  font-size: 11px;
  color: #94a3b8;
}

.category-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.category-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.category-list li:last-child {
  border-bottom: none;
}

.cat-name {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
}

.cat-count {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  margin-top: 1px;
}

.cat-points {
  font-size: 15px;
  font-weight: 800;
}

.history-panel {
  margin-bottom: 4px;
}

.history-head {
  flex-wrap: wrap;
  align-items: center;
}

.tabs {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.tab.active {
  background: #ecfdf5;
  border-color: #bbf7d0;
  color: #047857;
}

.tab-count {
  font-size: 11px;
  font-weight: 700;
  background: rgba(15, 23, 42, 0.06);
  padding: 1px 6px;
  border-radius: 999px;
}

.tab.active .tab-count {
  background: rgba(5, 150, 105, 0.15);
}

.history {
  list-style: none;
  margin: 0;
  padding: 0;
}

.history-item {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px solid #f1f5f9;
}

.history-item:last-child {
  border-bottom: none;
}

.history-body strong {
  display: block;
  font-size: 14px;
  color: #0f172a;
  margin-bottom: 3px;
}

.history-meta {
  font-size: 12px;
  color: #94a3b8;
}

.history-right {
  text-align: right;
  flex-shrink: 0;
}

.pts {
  display: block;
  font-weight: 800;
  color: #047857;
  font-size: 16px;
}

.status {
  display: inline-block;
  margin-top: 4px;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
}

.status.approved { background: #ecfdf5; color: #047857; }
.status.pending { background: #fffbeb; color: #b45309; }
.status.rejected { background: #fef2f2; color: #b91c1c; }

.reject-note {
  color: #b91c1c;
  font-size: 12px;
  margin-top: 6px;
  padding: 6px 8px;
  background: #fef2f2;
  border-radius: 8px;
  border: 1px solid #fecaca;
}

.link {
  display: inline-block;
  margin-top: 6px;
  color: #059669;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 80;
  padding: 16px;
}

.modal {
  width: min(520px, 100%);
  background: #fff;
  border-radius: 16px;
  padding: 0;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 18px 20px 12px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
  margin: 0 0 4px;
  font-size: 18px;
  color: #0f172a;
}

.modal-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.modal-close {
  border: none;
  background: transparent;
  font-size: 24px;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  padding: 0 4px;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-form {
  padding: 16px 20px 20px;
}

.modal-form label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 12px;
}

.modal-form input,
.modal-form select,
.modal-form textarea {
  display: block;
  width: 100%;
  margin-top: 5px;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  box-sizing: border-box;
  font-size: 14px;
  font-weight: 400;
  color: #0f172a;
}

.modal-form input:focus,
.modal-form select:focus,
.modal-form textarea:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}

.error {
  color: #b91c1c;
  font-size: 13px;
  margin: 0 0 10px;
  font-weight: 500;
}

@media (max-width: 1440px) {
  .stats-grid {
    gap: 10px;
  }

  .stat-card {
    padding: 12px 14px;
  }

  .content-grid {
    grid-template-columns: minmax(0, 1fr) minmax(220px, 0.7fr);
    gap: 12px;
  }

  .page-header h1 {
    font-size: 20px;
  }
}

@media (max-width: 960px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .leaderboard {
    max-height: none;
  }
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
  }

  .page-header .btn-primary {
    width: 100%;
  }

  .page-header h1 {
    font-size: 20px;
  }

  .hero-score {
    font-size: 40px;
  }

  .hero-side {
    width: 100%;
    flex-direction: row;
  }

  .hero-rank,
  .hero-reward {
    flex: 1;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .history-head {
    flex-direction: column;
    align-items: stretch;
  }

  .history-head .filter-select {
    width: 100%;
  }
}
</style>
