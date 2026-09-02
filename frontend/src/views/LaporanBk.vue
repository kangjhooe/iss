<template>
    <div class="laporan-bk-page">
      <svg xmlns="http://www.w3.org/2000/svg" class="icon-sprite" aria-hidden="true">
        <symbol id="bk-empty" viewBox="0 0 24 24" fill="none">
          <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
      </svg>

      <div class="toolbar">
        <div class="toolbar-spacer"></div>
        <div class="header-actions toolbar-actions">
          <button type="button" class="btn-secondary btn-compact" :disabled="printing || loading" @click="printPdf">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>
          <button type="button" class="btn-secondary btn-compact" :disabled="exporting || loading" @click="exportCsv">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exporting ? 'Mengekspor...' : 'Export CSV' }}</span>
          </button>
        </div>
      </div>

      <div class="tab-shell">
        <nav class="section-nav" aria-label="Navigasi laporan BK">
          <button type="button" :class="['sec-btn', { active: viewTab === 'ringkasan' }]" @click="switchTab('ringkasan')">
            <span class="sec-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19V5M4 19h16M8 16V9M12 16V7M16 16v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="sec-text">
              <span class="sec-label">Ringkasan</span>
              <span class="sec-hint">Rekap kelas & tren</span>
            </span>
          </button>
          <button type="button" :class="['sec-btn', { active: viewTab === 'skor' }]" @click="switchTab('skor')">
            <span class="sec-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            </span>
            <span class="sec-text">
              <span class="sec-label">Skor siswa</span>
              <span class="sec-hint">Poin per siswa</span>
            </span>
          </button>
          <button type="button" :class="['sec-btn', { active: viewTab === 'catatan' }]" @click="switchTab('catatan')">
            <span class="sec-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <span class="sec-text">
              <span class="sec-label">Catatan</span>
              <span class="sec-hint">Pelanggaran, prestasi, konseling</span>
            </span>
          </button>
        </nav>

        <div class="tab-main">
          <p class="tab-description">{{ tabDescription }}</p>

          <div v-if="viewTab === 'catatan'" class="sub-nav" role="tablist" aria-label="Jenis catatan">
            <button type="button" role="tab" :class="['sub-nav-btn', { active: notesKind === 'violations' }]" :aria-selected="notesKind === 'violations'" @click="notesKind = 'violations'">
              Pelanggaran ({{ detail?.total ?? 0 }})
            </button>
            <button type="button" role="tab" :class="['sub-nav-btn', { active: notesKind === 'achievements' }]" :aria-selected="notesKind === 'achievements'" @click="notesKind = 'achievements'">
              Prestasi ({{ detail?.achievements_total ?? detail?.achievements?.length ?? 0 }})
            </button>
            <button type="button" role="tab" :class="['sub-nav-btn', { active: notesKind === 'counseling' }]" :aria-selected="notesKind === 'counseling'" @click="notesKind = 'counseling'">
              Konseling ({{ detail?.counseling_total ?? detail?.counseling?.length ?? 0 }})
            </button>
          </div>

          <div class="filters">
            <select v-model="filters.academic_year_id" class="filter-select" @change="onPeriodChange">
              <option value="">Semua Tahun Ajaran</option>
              <option v-for="y in academicYears" :key="y.id" :value="String(y.id)">{{ y.name }}</option>
            </select>
            <select v-model="filters.semester_id" class="filter-select" @change="reload">
              <option value="">Semua Semester</option>
              <option v-for="s in filteredSemesters" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
            </select>
            <select v-model="filters.class_id" class="filter-select" @change="reload" :disabled="isHomeroomScoped && classes.length <= 1">
              <option v-if="!isHomeroomScoped" value="">Semua Kelas</option>
              <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
            <button type="button" class="filter-toggle" :aria-expanded="periodAdvanced" @click="periodAdvanced = !periodAdvanced">
              {{ periodAdvanced ? 'Sembunyikan filter' : 'Filter lanjutan' }}
              <span class="filter-toggle-icon">{{ periodAdvanced ? '▼' : '▶' }}</span>
            </button>
          </div>

          <div v-show="periodAdvanced" class="filters filters-advanced">
            <select v-model="filters.month" class="filter-select" @change="reload">
              <option value="">Semua Bulan</option>
              <option v-for="m in 12" :key="m" :value="String(m)">{{ monthFullLabel(m) }}</option>
            </select>
            <select v-model="filters.year" class="filter-select" @change="reload" title="Tahun kalender">
              <option v-for="y in chartYears" :key="y" :value="String(y)">Tahun {{ y }}</option>
            </select>
          </div>

          <div v-if="activeChips.length" class="filter-chips">
            <button
              v-for="chip in activeChips"
              :key="chip.key"
              type="button"
              class="filter-chip"
              :disabled="chip.disabled"
              @click="clearChip(chip.key)"
            >
              {{ chip.label }}
              <span v-if="!chip.disabled" aria-hidden="true">×</span>
            </button>
          </div>

          <div v-if="loading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="8" :columns="7" :cell-widths="['90px', '140px', '160px', '100px', '80px', '80px', '1fr']" />
          </div>

      <!-- ========== RINGKASAN ========== -->
      <template v-else-if="viewTab === 'ringkasan' && report">
        <div class="stat-cards">
          <button type="button" class="summary-card card-warning" @click="openNotes('violations')">
            <span class="summary-icon summary-icon-warning" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            </span>
            <span class="summary-body">
              <span class="summary-value">{{ report.summary?.total_violations ?? 0 }}</span>
              <span class="summary-label">Pelanggaran · {{ report.summary?.total_violation_points ?? 0 }} poin</span>
            </span>
          </button>
          <button type="button" class="summary-card card-good" @click="openNotes('achievements')">
            <span class="summary-icon summary-icon-good" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.4 6.9H22l-5.6 4.1 2.1 6.9L12 16.8 5.5 19.9 7.6 13 2 8.9h7.6L12 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            </span>
            <span class="summary-body">
              <span class="summary-value">{{ report.summary?.total_achievements ?? 0 }}</span>
              <span class="summary-label">Prestasi · −{{ report.summary?.total_achievement_points ?? 0 }} poin</span>
            </span>
          </button>
          <button type="button" class="summary-card card-score" @click="switchTab('skor')">
            <span class="summary-icon summary-icon-score" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <span class="summary-body">
              <span class="summary-value">{{ report.summary?.net_score ?? 0 }}</span>
              <span class="summary-label">Skor bersih</span>
            </span>
          </button>
          <button type="button" class="summary-card card-total" @click="openNotes('counseling')">
            <span class="summary-icon summary-icon-total" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="summary-body">
              <span class="summary-value">{{ report.summary?.total_counseling ?? 0 }}</span>
              <span class="summary-label">Sesi konseling</span>
            </span>
          </button>
        </div>

        <section class="report-section">
          <h3 class="section-title">Per kelas</h3>
          <div v-if="!report.by_class?.length" class="empty-state">
            <div class="empty-icon">
              <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
            </div>
            <h3 class="empty-title">Belum ada data</h3>
            <p class="empty-desc">Tidak ada pelanggaran, prestasi, atau konseling untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Kelas</th>
                    <th class="th-num">Pelanggaran</th>
                    <th class="th-num">Prestasi</th>
                    <th class="th-num">Konseling</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in report.by_class"
                    :key="row.class_id ?? row.class_name"
                    class="row-clickable"
                    @click="drillToClass(row)"
                  >
                    <td>{{ row.class_name }}</td>
                    <td class="td-num">{{ row.violation_count }}</td>
                    <td class="td-num td-good">{{ row.achievement_count ?? 0 }}</td>
                    <td class="td-num">{{ row.counseling_count }}</td>
                    <td class="td-action">
                      <button type="button" class="link-btn" @click.stop="drillToClass(row)">Skor siswa →</button>
                    </td>
                  </tr>
                </tbody>
              </table>
          </div>
        </section>

        <section class="report-section">
          <div class="section-head">
            <h3 class="section-title">Tren {{ report.by_month?.year }}</h3>
            <select v-model="filters.year" class="filter-select filter-select-sm" @change="reloadSummaryOnly" title="Tahun kalender untuk grafik tren">
              <option v-for="y in chartYears" :key="'t'+y" :value="String(y)">{{ y }}</option>
            </select>
          </div>
          <div class="chart-box" v-if="monthChartData">
            <div class="chart-wrap">
              <Bar :data="monthChartData" :options="chartOptionsBar" />
            </div>
          </div>
        </section>

        <div class="split-tables">
          <section class="report-section">
            <h3 class="section-title">Jenis pelanggaran</h3>
            <div v-if="report.top_violation_types?.length" class="table-container">
              <table class="data-table">
                  <thead>
                    <tr>
                      <th>Jenis</th>
                      <th class="th-num">Jumlah</th>
                      <th class="th-num">Poin</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in report.top_violation_types" :key="row.violation_type_id ?? row.type_name">
                      <td>{{ row.type_name }}</td>
                      <td class="td-num">{{ row.count }}</td>
                      <td class="td-num">{{ row.total_points }}</td>
                    </tr>
                  </tbody>
                </table>
            </div>
            <p v-else class="empty-desc muted">Belum ada catatan pelanggaran.</p>
          </section>
          <section class="report-section">
            <h3 class="section-title">Jenis prestasi</h3>
            <div v-if="report.top_achievement_types?.length" class="table-container">
              <table class="data-table">
                  <thead>
                    <tr>
                      <th>Jenis</th>
                      <th class="th-num">Jumlah</th>
                      <th class="th-num">Poin</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in report.top_achievement_types" :key="row.achievement_type_id ?? row.type_name">
                      <td>{{ row.type_name }}</td>
                      <td class="td-num">{{ row.count }}</td>
                      <td class="td-num td-good">{{ row.total_points }}</td>
                    </tr>
                  </tbody>
                </table>
            </div>
            <p v-else class="empty-desc muted">Belum ada catatan prestasi.</p>
          </section>
        </div>
      </template>

      <!-- ========== SKOR SISWA ========== -->
      <template v-else-if="viewTab === 'skor'">
        <div v-if="!detail?.by_student?.length" class="empty-state">
          <div class="empty-icon">
            <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
          </div>
          <h3 class="empty-title">Belum ada data</h3>
          <p class="empty-desc">Tidak ada siswa dengan pelanggaran atau prestasi untuk filter ini.</p>
        </div>
        <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>NIS</th>
                  <th>Nama</th>
                  <th class="th-num">Pelanggaran</th>
                  <th class="th-num">Prestasi</th>
                  <th class="th-num">Skor</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in bkStudentGroups" :key="'bk-' + group.key">
                  <tr class="group-row">
                    <td colspan="6">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                  </tr>
                  <tr v-for="(row, i) in group.rows" :key="row.student_id ?? (group.key + '-' + i)">
                    <td>{{ group.start + i + 1 }}</td>
                    <td>{{ row.nis || '—' }}</td>
                    <td>{{ row.student_name || '—' }}</td>
                    <td class="td-num">{{ row.violation_points ?? row.total_points }} <span class="td-muted">({{ row.violation_count }})</span></td>
                    <td class="td-num td-good">−{{ row.achievement_points ?? 0 }} <span class="td-muted">({{ row.achievement_count ?? 0 }})</span></td>
                    <td class="td-num td-total" :class="scoreClass(row.score)">{{ row.score ?? ((row.violation_points ?? row.total_points) - (row.achievement_points ?? 0)) }}</td>
                  </tr>
                </template>
              </tbody>
            </table>
        </div>
      </template>

      <!-- ========== CATATAN ========== -->
      <template v-else-if="viewTab === 'catatan'">
        <p v-if="detail?.truncated" class="section-hint">Ditampilkan maksimal 2000 baris per jenis catatan.</p>

        <section v-if="notesKind === 'violations'" class="report-section">
          <div v-if="!detail?.items?.length" class="empty-state">
            <div class="empty-icon">
              <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
            </div>
            <h3 class="empty-title">Belum ada pelanggaran</h3>
            <p class="empty-desc">Tidak ada catatan untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th class="th-num">Poin</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.items" :key="row.id">
                    <td>{{ formatDate(row.violation_date) }}</td>
                    <td>
                      <span class="student-name">{{ row.student_name || '—' }}</span>
                      <span class="student-meta">{{ row.nis || '—' }}</span>
                    </td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.violation_type }}</td>
                    <td class="td-num">{{ row.point_weight }}</td>
                    <td><span :class="['status-badge', 'status-' + row.status]">{{ statusLabel(row.status) }}</span></td>
                  </tr>
                </tbody>
              </table>
          </div>
        </section>

        <section v-else-if="notesKind === 'achievements'" class="report-section">
          <div v-if="!(detail?.achievements?.length)" class="empty-state">
            <div class="empty-icon">
              <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
            </div>
            <h3 class="empty-title">Belum ada prestasi</h3>
            <p class="empty-desc">Tidak ada catatan prestasi untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis Prestasi</th>
                    <th class="th-num">Poin</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.achievements" :key="row.id">
                    <td>{{ formatDate(row.achievement_date) }}</td>
                    <td>
                      <span class="student-name">{{ row.student_name || '—' }}</span>
                      <span class="student-meta">{{ row.nis || '—' }}</span>
                    </td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.achievement_type }}</td>
                    <td class="td-num td-good">−{{ row.point_value }}</td>
                  </tr>
                </tbody>
              </table>
          </div>
        </section>

        <section v-else class="report-section">
          <div v-if="!(detail?.counseling?.length)" class="empty-state">
            <div class="empty-icon">
              <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
            </div>
            <h3 class="empty-title">Belum ada konseling</h3>
            <p class="empty-desc">Tidak ada sesi konseling untuk filter yang dipilih.</p>
          </div>
          <div v-else class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Konselor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in detail.counseling" :key="row.id">
                    <td>{{ formatDate(row.session_date) }}</td>
                    <td>
                      <span class="student-name">{{ row.student_name || '—' }}</span>
                      <span class="student-meta">{{ row.nis || '—' }}</span>
                    </td>
                    <td>{{ row.class_name }}</td>
                    <td>{{ row.counseling_type }}</td>
                    <td><span :class="['status-badge', 'status-' + row.status]">{{ counselingStatusLabel(row.status) }}</span></td>
                    <td>{{ row.counselor_name || '—' }}</td>
                  </tr>
                </tbody>
              </table>
          </div>
        </section>
      </template>

      <div v-else-if="!loading" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" aria-hidden="true"><use href="#bk-empty"/></svg>
        </div>
        <h3 class="empty-title">Gagal memuat laporan</h3>
        <p class="empty-desc">Coba ubah filter atau periksa koneksi.</p>
      </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { bkReportApi } from '@/api/bkReport'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useAuthStore } from '@/stores/auth'
import { getPrincipalTitle, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const toast = useToast()
const route = useRoute()
const referenceStore = useReferenceDataStore()
const authStore = useAuthStore()

const loading = ref(true)
const exporting = ref(false)
const printing = ref(false)
const viewTab = ref('ringkasan') // ringkasan | skor | catatan
const notesKind = ref('violations') // violations | achievements | counseling
const periodAdvanced = ref(false)
const report = ref(null)
const detail = ref(null)
const signers = ref({ principal: {}, bk: {} })
const classes = ref([])
const semesters = ref([])
const institution = ref(null)

const isHomeroomScoped = computed(() => authStore.user?.bk_scope === 'homeroom')
const homeroomClassIds = computed(() => (authStore.user?.homeroom_class_ids || []).map(Number))
const isDetailTab = computed(() => viewTab.value !== 'ringkasan')
const tabDescription = computed(() => {
  if (isHomeroomScoped.value) return 'Menampilkan siswa di kelas yang Anda walikan saja.'
  if (viewTab.value === 'ringkasan') return 'Rekap kelas. Skor bersih = poin pelanggaran − poin prestasi.'
  if (viewTab.value === 'skor') return 'Skor per siswa = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.'
  return 'Catatan pelanggaran, prestasi, dan konseling sesuai filter yang dipilih.'
})

const now = new Date()
const currentYear = now.getFullYear()
const chartYears = [currentYear, currentYear - 1, currentYear - 2]

const filters = ref({
  academic_year_id: '',
  semester_id: '',
  class_id: '',
  month: '',
  year: String(currentYear),
})

const academicYears = computed(() => referenceStore.academicYears || [])

const filteredSemesters = computed(() => {
  const yearId = filters.value.academic_year_id
  if (!yearId) return semesters.value
  return semesters.value.filter(s => String(s.academic_year_id) === String(yearId))
})

const MONTH_NAMES = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const STATUS_LABELS = {
  dicatat: 'Dicatat',
  sanksi_diberikan: 'Sanksi Diberikan',
  follow_up: 'Follow Up',
  selesai: 'Selesai',
}

const COUNSELING_STATUS_LABELS = {
  jadwal: 'Jadwal',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}

function monthFullLabel(m) {
  return MONTH_NAMES[m] || `Bulan ${m}`
}

function statusLabel(s) {
  return STATUS_LABELS[s] || s || '—'
}

function counselingStatusLabel(s) {
  return COUNSELING_STATUS_LABELS[s] || s || '—'
}

function scoreClass(score) {
  const n = Number(score)
  if (Number.isNaN(n)) return ''
  if (n > 40) return 'score-bad'
  if (n > 20) return 'score-warn'
  if (n <= 0) return 'score-good'
  return ''
}

function formatDate(iso) {
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  if (!y || !m || !d) return iso
  return `${d}/${m}/${y}`
}

function groupRowsByClassName(rows) {
  const groups = []
  let current = null
  let index = 0
  for (const row of rows || []) {
    const name = row.class_name || 'Tanpa kelas'
    if (!current || current.name !== name) {
      current = { key: name, name, rows: [], start: index }
      groups.push(current)
    }
    current.rows.push(row)
    index += 1
  }
  return groups
}

const bkStudentGroups = computed(() => groupRowsByClassName(detail.value?.by_student || []))

const selectedClassName = computed(() => {
  const cls = classes.value.find(c => String(c.id) === String(filters.value.class_id))
  return cls?.name || ''
})

const activeChips = computed(() => {
  const chips = []
  if (filters.value.class_id && selectedClassName.value) {
    chips.push({
      key: 'class',
      label: `Kelas ${selectedClassName.value}`,
      disabled: isHomeroomScoped.value && classes.value.length <= 1,
    })
  }
  if (periodAdvanced.value && filters.value.month) {
    chips.push({
      key: 'month',
      label: `${monthFullLabel(Number(filters.value.month))} ${filters.value.year}`,
      disabled: false,
    })
  }
  return chips
})

const periodLabel = computed(() => {
  const parts = []
  parts.push(selectedClassName.value || 'Semua kelas')
  const yearName = academicYears.value.find(y => String(y.id) === String(filters.value.academic_year_id))?.name
  if (yearName) parts.push(yearName)
  const semName = filteredSemesters.value.find(s => String(s.id) === String(filters.value.semester_id))?.name
  if (semName) parts.push(semName)
  if (periodAdvanced.value && filters.value.month) {
    parts.push(`${monthFullLabel(Number(filters.value.month))} ${filters.value.year}`)
  }
  return parts.join(' · ')
})

function cleanParams({ forDetail = false } = {}) {
  const params = {
    academic_year_id: filters.value.academic_year_id,
    semester_id: filters.value.semester_id,
    class_id: filters.value.class_id,
    year: filters.value.year,
  }
  const applyCalendarPeriod = periodAdvanced.value && !!filters.value.month
  if (forDetail) {
    if (applyCalendarPeriod) {
      params.month = filters.value.month
    } else {
      delete params.year
    }
  } else if (applyCalendarPeriod) {
    params.month = filters.value.month
  }
  Object.keys(params).forEach((k) => {
    if (k === 'academic_year_id' || k === 'semester_id') return
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  return params
}

const monthChartData = computed(() => {
  const months = report.value?.by_month?.months
  if (!months?.length) return null
  return {
    labels: months.map(m => m.label || MONTH_NAMES[m.month]),
    datasets: [
      {
        label: 'Pelanggaran',
        data: months.map(m => m.violation_count),
        backgroundColor: 'rgba(239, 68, 68, 0.7)',
        borderRadius: 4,
      },
      {
        label: 'Prestasi',
        data: months.map(m => m.achievement_count ?? 0),
        backgroundColor: 'rgba(16, 185, 129, 0.7)',
        borderRadius: 4,
      },
      {
        label: 'Konseling',
        data: months.map(m => m.counseling_count),
        backgroundColor: 'rgba(14, 165, 233, 0.7)',
        borderRadius: 4,
      },
    ],
  }
})

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
}

function captureSigners(payload) {
  if (payload?.signers) signers.value = payload.signers
}

async function loadSummary() {
  loading.value = true
  try {
    const res = await bkReportApi.getSummary(cleanParams({ forDetail: false }))
    report.value = res.data?.data ?? null
    captureSigners(report.value)
  } catch (e) {
    report.value = null
    toast.error('Gagal memuat ringkasan', e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

async function loadDetail() {
  loading.value = true
  try {
    const res = await bkReportApi.getViolationDetail(cleanParams({ forDetail: true }))
    detail.value = res.data?.data ?? null
    captureSigners(detail.value)
  } catch (e) {
    detail.value = null
    toast.error('Gagal memuat detail', e.formattedMessage || 'Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
  }
}

function invalidateCache() {
  report.value = null
  detail.value = null
}

async function reload() {
  invalidateCache()
  if (isDetailTab.value) await loadDetail()
  else await loadSummary()
}

async function reloadSummaryOnly() {
  report.value = null
  await loadSummary()
}

async function switchTab(tab) {
  if (viewTab.value === tab) return
  viewTab.value = tab
  if (tab === 'ringkasan') {
    if (!report.value) await loadSummary()
  } else if (!detail.value) {
    await loadDetail()
  }
}

async function openNotes(kind) {
  notesKind.value = kind
  await switchTab('catatan')
}

async function drillToClass(row) {
  if (row.class_id) {
    filters.value.class_id = String(row.class_id)
  }
  report.value = null
  detail.value = null
  viewTab.value = 'skor'
  await loadDetail()
}

async function clearChip(key) {
  if (key === 'class') {
    if (isHomeroomScoped.value && classes.value.length <= 1) return
    filters.value.class_id = ''
  }
  if (key === 'month') {
    filters.value.month = ''
  }
  await reload()
}

async function exportCsv() {
  exporting.value = true
  try {
    const isDetail = isDetailTab.value
    const res = isDetail
      ? await bkReportApi.exportViolations(cleanParams({ forDetail: true }))
      : await bkReportApi.export(cleanParams({ forDetail: false }))
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute(
      'download',
      isDetail
        ? `laporan-bk-detail-${new Date().toISOString().slice(0, 10)}.csv`
        : `laporan-bk-per-kelas-${new Date().toISOString().slice(0, 10)}.csv`
    )
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    toast.success('Export berhasil diunduh')
  } catch (e) {
    toast.error('Gagal mengekspor', e.formattedMessage || 'Data tidak dapat diekspor.')
  } finally {
    exporting.value = false
  }
}

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function buildPrintBodyHtml() {
  if (isDetailTab.value) {
    if (!detail.value) return '<p>Tidak ada data.</p>'

    const studentRows = (bkStudentGroups.value || []).flatMap((group) => {
      const header = `<tr class="group-row"><td colspan="9">Kelas ${escapeHtml(group.name)} (${group.rows.length} siswa)</td></tr>`
      const body = group.rows.map((row, i) => {
        const vPts = row.violation_points ?? row.total_points ?? 0
        const aPts = row.achievement_points ?? 0
        const score = row.score ?? (vPts - aPts)
        return `
      <tr>
        <td>${group.start + i + 1}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td class="num">${escapeHtml(row.violation_count)}</td>
        <td class="num">${escapeHtml(vPts)}</td>
        <td class="num">${escapeHtml(row.achievement_count ?? 0)}</td>
        <td class="num">−${escapeHtml(aPts)}</td>
        <td class="num"><strong>${escapeHtml(score)}</strong></td>
      </tr>`
      }).join('')
      return header + body
    }).join('') || '<tr><td colspan="9">Belum ada data</td></tr>'

    const listRows = (detail.value.items || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.violation_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.violation_type)}</td>
        <td>${escapeHtml(row.category)}</td>
        <td class="num">${escapeHtml(row.point_weight)}</td>
        <td>${escapeHtml(statusLabel(row.status))}</td>
        <td>${escapeHtml(row.reporter_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="9">Belum ada data</td></tr>'

    const achRows = (detail.value.achievements || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.achievement_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.achievement_type)}</td>
        <td class="num">−${escapeHtml(row.point_value)}</td>
        <td>${escapeHtml(row.giver_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="7">Belum ada data</td></tr>'

    const counselingRows = (detail.value.counseling || []).map((row) => `
      <tr>
        <td>${escapeHtml(formatDate(row.session_date))}</td>
        <td>${escapeHtml(row.nis || '—')}</td>
        <td>${escapeHtml(row.student_name || '—')}</td>
        <td>${escapeHtml(row.class_name)}</td>
        <td>${escapeHtml(row.counseling_type)}</td>
        <td>${escapeHtml(counselingStatusLabel(row.status))}</td>
        <td>${escapeHtml(row.counselor_name || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="7">Belum ada data</td></tr>'

    return `
      <p class="note">Skor = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.</p>
      <h2>1. Rekap Skor per Siswa</h2>
      <table>
        <thead>
          <tr>
            <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jml Pelanggaran</th><th>Poin Pelanggaran</th>
            <th>Jml Prestasi</th><th>Poin Prestasi</th><th>Skor</th>
          </tr>
        </thead>
        <tbody>${studentRows}</tbody>
      </table>
      <h2>2. Daftar Pelanggaran</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Pelapor</th>
          </tr>
        </thead>
        <tbody>${listRows}</tbody>
      </table>
      <h2>3. Daftar Prestasi</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis Prestasi</th><th>Poin</th><th>Pemberi</th>
          </tr>
        </thead>
        <tbody>${achRows}</tbody>
      </table>
      <h2>4. Daftar Konseling</h2>
      <table>
        <thead>
          <tr>
            <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
            <th>Jenis</th><th>Status</th><th>Konselor</th>
          </tr>
        </thead>
        <tbody>${counselingRows}</tbody>
      </table>
    `
  }

  if (!report.value) return '<p>Tidak ada data.</p>'
  const s = report.value.summary || {}
  const classRows = (report.value.by_class || []).map((row) => `
    <tr>
      <td>${escapeHtml(row.class_name)}</td>
      <td class="num">${escapeHtml(row.violation_count)}</td>
      <td class="num">${escapeHtml(row.achievement_count ?? 0)}</td>
      <td class="num">${escapeHtml(row.counseling_count)}</td>
    </tr>
  `).join('') || '<tr><td colspan="4">Belum ada data</td></tr>'

  const monthRows = (report.value.by_month?.months || []).map((m) => `
    <tr>
      <td>${escapeHtml(monthFullLabel(m.month))}</td>
      <td class="num">${escapeHtml(m.violation_count)}</td>
      <td class="num">${escapeHtml(m.achievement_count ?? 0)}</td>
      <td class="num">${escapeHtml(m.counseling_count)}</td>
    </tr>
  `).join('') || '<tr><td colspan="4">Belum ada data</td></tr>'

  const typeRows = (report.value.top_violation_types || []).map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>${escapeHtml(row.type_name)}</td>
      <td>${escapeHtml(row.category)}</td>
      <td class="num">${escapeHtml(row.count)}</td>
      <td class="num">${escapeHtml(row.total_points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="5">Belum ada data</td></tr>'

  const achTypeRows = (report.value.top_achievement_types || []).map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>${escapeHtml(row.type_name)}</td>
      <td>${escapeHtml(row.category || '—')}</td>
      <td class="num">${escapeHtml(row.count)}</td>
      <td class="num">${escapeHtml(row.total_points)}</td>
    </tr>
  `).join('') || '<tr><td colspan="5">Belum ada data</td></tr>'

  return `
    <div class="stats">
      <div class="stat"><div class="stat-label">Total Pelanggaran</div><div class="stat-value">${s.total_violations ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${s.total_achievements ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Skor Bersih</div><div class="stat-value">${s.net_score ?? 0}</div></div>
      <div class="stat"><div class="stat-label">Total Konseling</div><div class="stat-value">${s.total_counseling ?? 0}</div></div>
    </div>
    <p class="note">Skor bersih = poin pelanggaran − poin prestasi.</p>
    <h2>1. Rekap per Kelas</h2>
    <table>
      <thead><tr><th>Kelas</th><th>Pelanggaran</th><th>Prestasi</th><th>Konseling</th></tr></thead>
      <tbody>${classRows}</tbody>
    </table>
    <h2>2. Rekap per Bulan (${escapeHtml(report.value.by_month?.year || filters.value.year)})</h2>
    <table>
      <thead><tr><th>Bulan</th><th>Pelanggaran</th><th>Prestasi</th><th>Konseling</th></tr></thead>
      <tbody>${monthRows}</tbody>
    </table>
    <h2>3. Top Jenis Pelanggaran</h2>
    <table>
      <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
      <tbody>${typeRows}</tbody>
    </table>
    <h2>4. Top Jenis Prestasi</h2>
    <table>
      <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
      <tbody>${achTypeRows}</tbody>
    </table>
  `
}

function printPdf() {
  const hasData = isDetailTab.value ? !!detail.value : !!report.value
  if (!hasData) {
    toast.error('Gagal', 'Tidak ada data untuk dicetak')
    return
  }

  printing.value = true
  try {
    const inst = institution.value || {}
    const instName = inst.name || 'Sekolah'
    const fullAddress = [
      inst.address,
      inst.village ? `Desa/Kel. ${inst.village}` : '',
      inst.sub_district ? `Kec. ${inst.sub_district}` : '',
      inst.district,
      inst.province,
      inst.postal_code,
    ].filter(Boolean).join(', ')
    const title = isDetailTab.value
      ? 'Laporan BK — Skor Siswa & Catatan'
      : 'Laporan BK — Ringkasan'
    const createdAt = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    })
    const placeDate = `${inst.district || inst.city || '........................'}, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric',
    })}`
    const filename = `Laporan_BK_${isDetailTab.value ? 'Detail' : 'Ringkasan'}_${new Date().toISOString().slice(0, 10)}.pdf`

    const principalRole = signers.value?.principal?.role || getPrincipalTitle(inst.level)
    const principalName = signers.value?.principal?.name || inst.principal_name || ''
    const principalNip = signers.value?.principal?.nip || inst.principal_nip || ''
    const bkRole = signers.value?.bk?.role || 'Guru Bimbingan Konseling'
    const bkName = signers.value?.bk?.name || ''
    const bkNip = signers.value?.bk?.nip || ''

    const content = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${escapeHtml(filename)}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 16px; }
    h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .subtitle { text-align: center; color: #444; margin-bottom: 12px; }
    .period { text-align: center; margin-bottom: 16px; font-size: 11px; }
    h2 { font-size: 12px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; }
    tr.group-row td { background: #e2e8f0; font-weight: 700; text-transform: none; }
    td.num, th.num { text-align: right; }
    .stats { display: flex; gap: 8px; margin-bottom: 12px; }
    .stat { flex: 1; border: 1px solid #333; padding: 8px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #555; }
    .stat-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .printed-at { font-size: 9px; color: #555; margin-top: 12px; text-align: center; }
    .sig-wrap { display: table; width: 100%; margin-top: 28px; page-break-inside: avoid; }
    .sig-col { display: table-cell; width: 50%; vertical-align: top; }
    .sig { text-align: center; min-width: 220px; }
    .sig-col-right { text-align: right; }
    .sig-col-right .sig { display: inline-block; text-align: center; }
    .sig-place, .sig-role { font-size: 10px; line-height: 1.35; }
    .sig-space { height: 56px; }
    .sig-name { font-size: 11px; font-weight: 700; text-decoration: underline; }
    .sig-nip { font-size: 9px; margin-top: 2px; }
    @media print {
      @page { size: A4 ${isDetailTab.value ? 'landscape' : 'portrait'}; margin: 10mm 12mm 10mm 10mm; }
      body { margin: 0; padding-right: 1px; }
    }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${inst.logo ? `<img src="${escapeHtml(inst.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
      <div class="kop-text">
        ${inst.foundation_name ? `<div class="foundation">${escapeHtml(inst.foundation_name)}</div>` : ''}
        <div class="school">${escapeHtml(instName)}</div>
        <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
        <div class="school-info">
          NPSN: ${escapeHtml(inst.npsn || '-')}
          ${inst.nss ? ` · ${getNssLabel(inst.level)}: ${escapeHtml(inst.nss)}` : ''}
          ${inst.phone ? ` · Telp: ${escapeHtml(inst.phone)}` : ''}
          ${inst.email ? ` · Email: ${escapeHtml(inst.email)}` : ''}
          ${inst.website ? ` · ${escapeHtml(inst.website)}` : ''}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>${escapeHtml(title)}</h1>
  <div class="subtitle">Bimbingan Konseling</div>
  <div class="period"><strong>Periode / Filter:</strong> ${escapeHtml(periodLabel.value)}</div>
  ${buildPrintBodyHtml()}
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig">
        <div class="sig-place">&nbsp;</div>
        <div class="sig-role">Mengetahui,<br>${escapeHtml(principalRole)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${escapeHtml(principalName || '___________________')}</div>
        <div class="sig-nip">NIP. ${escapeHtml(principalNip || '___________________')}</div>
      </div>
    </div>
    <div class="sig-col sig-col-right">
      <div class="sig">
        <div class="sig-place">${escapeHtml(placeDate)}</div>
        <div class="sig-role">${escapeHtml(bkRole)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${escapeHtml(bkName || '___________________')}</div>
        <div class="sig-nip">NIP. ${escapeHtml(bkNip || '___________________')}</div>
      </div>
    </div>
  </div>
  <div class="printed-at">Dicetak pada: ${escapeHtml(createdAt)}</div>
</body>
</html>`

    const printWindow = window.open('', '_blank')
    if (!printWindow) {
      toast.error('Gagal', 'Popup diblokir. Izinkan popup untuk mencetak.')
      return
    }
    printWindow.document.write(content)
    printWindow.document.close()
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    if (import.meta.env.DEV) console.error('Error printing PDF:', err)
    toast.error('Gagal', 'Gagal menyiapkan cetak PDF')
  } finally {
    printing.value = false
  }
}

async function onPeriodChange() {
  filters.value.semester_id = ''
  await loadClasses()
  await reload()
}

async function loadClasses() {
  try {
    const params = { per_page: 200 }
    if (filters.value.academic_year_id) params.academic_year_id = filters.value.academic_year_id
    if (filters.value.semester_id) params.semester_id = filters.value.semester_id
    const res = await classApi.getAll(params)
    let list = res.data?.data || []
    if (isHomeroomScoped.value && homeroomClassIds.value.length) {
      const allowed = new Set(homeroomClassIds.value)
      list = list.filter(c => allowed.has(Number(c.id)))
    }
    classes.value = list
    if (isHomeroomScoped.value) {
      if (list.length === 1) {
        filters.value.class_id = String(list[0].id)
      } else if (filters.value.class_id && !list.some(c => String(c.id) === String(filters.value.class_id))) {
        filters.value.class_id = list[0] ? String(list[0].id) : ''
      }
    }
  } catch {
    classes.value = []
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res.data?.data || []
  } catch {
    semesters.value = []
  }
}

async function loadDefaults() {
  try {
    const res = await institutionApi.getMy()
    institution.value = res.data?.data || res.data
    if (institution.value?.active_academic_year_id) {
      filters.value.academic_year_id = String(institution.value.active_academic_year_id)
    }
    if (institution.value?.active_semester_id) {
      filters.value.semester_id = String(institution.value.active_semester_id)
    }
  } catch {
    // ignore
  }
}

onMounted(async () => {
  await Promise.all([
    loadDefaults(),
    referenceStore.getAcademicYears(),
    loadSemesters(),
  ])
  await loadClasses()
  const qClassId = route.query.class_id ? String(route.query.class_id) : ''
  if (qClassId && classes.value.some((c) => String(c.id) === qClassId)) {
    filters.value.class_id = qClassId
  }
  const qTab = String(route.query.tab || '')
  if (qTab === 'detail' || qTab === 'skor') viewTab.value = 'skor'
  else if (qTab === 'catatan') viewTab.value = 'catatan'
  const qNotes = String(route.query.notes || '')
  if (['violations', 'achievements', 'counseling'].includes(qNotes)) {
    notesKind.value = qNotes
  }
  if (isDetailTab.value) await loadDetail()
  else await loadSummary()
})
</script>

<style scoped>
.laporan-bk-page {
  position: relative;
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  margin: 0 auto;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.icon-sprite { position: absolute; width: 0; height: 0; overflow: hidden; }
.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-bottom: 0.75rem;
}
.toolbar-spacer { flex: 1; }
.header-actions,
.toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
}
.tab-shell {
  display: grid;
  grid-template-columns: 200px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
  margin-bottom: 1.5rem;
}
.section-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: #f8fafc;
  border-right: 1px solid #eef2f7;
}
.sec-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: none;
  background: transparent;
  border-radius: 10px;
  cursor: pointer;
  color: #64748b;
  text-align: left;
}
.sec-btn:hover:not(.active) { background: #fff; color: #0f172a; }
.sec-btn.active { background: #fff; color: #065f46; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0; }
.sec-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #ecfdf5;
  color: #059669;
  flex-shrink: 0;
}
.sec-btn.active .sec-icon { background: #d1fae5; color: #047857; }
.sec-text { display: flex; flex-direction: column; gap: 1px; min-width: 0; flex: 1; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; }
.sec-hint { font-size: 11px; font-weight: 500; color: #94a3b8; }
.sec-btn.active .sec-hint { color: #059669; }
.tab-main { min-width: 0; padding: 14px 16px 16px; }
.tab-description {
  margin: 0 0 1rem;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #475569;
  background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 10px;
  border-left: 4px solid #059669;
  line-height: 1.5;
}
.sub-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin: 0 0 1.15rem;
  padding: 0.35rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.sub-nav-btn {
  padding: 0.45rem 0.9rem;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.sub-nav-btn:hover {
  background: #fff;
  color: #0f172a;
  border-color: #e2e8f0;
}
.sub-nav-btn.active {
  background: #fff;
  color: #047857;
  border-color: #6ee7b7;
  box-shadow: 0 1px 3px rgba(5, 150, 105, 0.12);
}
.filter-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
}
.filter-toggle:hover {
  background: #f1f5f9;
  color: #475569;
}
.filter-toggle-icon { font-size: 0.7rem; }
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.filters-advanced {
  margin-top: -0.5rem;
  padding-top: 0.75rem;
  border-style: dashed;
}
.filter-select {
  padding: 0.55rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  min-width: 140px;
  background: #fff;
  font-size: 0.875rem;
  color: #334155;
}
.filter-select-sm { font-size: 0.8rem; padding: 0.3rem 0.5rem; min-width: 0; }
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}
.filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid #a7f3d0;
  background: #ecfdf5;
  color: #047857;
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.filter-chip:disabled { cursor: default; opacity: 0.85; }
.stat-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}
.summary-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1rem 1.15rem;
  border-radius: 12px;
  text-align: left;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.04);
}
.summary-card:hover { box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08); }
.summary-body { min-width: 0; }
.summary-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.summary-icon-total { background: rgba(71, 85, 105, 0.12); color: #475569; }
.summary-icon-warning { background: rgba(146, 64, 14, 0.18); color: #b45309; }
.summary-icon-good { background: rgba(5, 150, 105, 0.18); color: #059669; }
.summary-icon-score { background: rgba(180, 83, 9, 0.14); color: #b45309; }
.summary-value { display: block; font-size: 1.65rem; font-weight: 700; line-height: 1.15; letter-spacing: -0.02em; }
.summary-label { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; display: block; font-weight: 500; }
.card-total { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; }
.card-warning { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); color: #92400e; border: 1px solid #fde68a; }
.card-warning .summary-label { color: #a16207; }
.card-good { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; border: 1px solid #a7f3d0; }
.card-score { background: linear-gradient(145deg, #fffbeb 0%, #fef9c3 100%); color: #854d0e; border: 1px solid #fde68a; }
.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}
.section-title {
  margin: 0 0 0.65rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: #334155;
}
.section-head .section-title { margin: 0; }
.split-tables {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.report-section { margin-top: 0.5rem; }
.table-container {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th,
.data-table td {
  padding: 0.9rem 1.15rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  white-space: nowrap;
}
.data-table tbody tr { transition: background 0.15s ease; }
.data-table tbody tr:nth-child(even) { background: #fafbfc; }
.data-table tbody tr:hover { background: #f0fdf4 !important; }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table th {
  background: #f1f5f9;
  font-weight: 600;
  font-size: 0.8rem;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.data-table thead { position: sticky; top: 0; z-index: 1; }
.student-name { display: block; font-weight: 500; }
.student-meta { font-size: 0.8rem; color: #64748b; }
.group-row td {
  background: #ecfdf5;
  font-weight: 700;
  font-size: 12px;
  color: #065f46;
  text-transform: none;
  letter-spacing: 0;
}
.row-clickable { cursor: pointer; }
.th-num, .td-num { text-align: right; font-variant-numeric: tabular-nums; }
.td-total { font-weight: 700; }
.td-good { color: #059669; font-weight: 600; }
.td-muted { color: #94a3b8; font-weight: 500; font-size: 0.75rem; }
.score-bad { color: #b91c1c; }
.score-warn { color: #c2410c; }
.score-good { color: #047857; }
.section-hint {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
}
.td-action { text-align: right; }
.link-btn {
  border: none;
  background: none;
  color: #059669;
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
  padding: 0;
}
.link-btn:hover { text-decoration: underline; }
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}
.status-badge.status-pending { background: #fffbeb; color: #b45309; }
.status-badge.status-dicatat { background: #ecfdf5; color: #047857; }
.status-badge.status-sanksi_diberikan { background: #fef3c7; color: #92400e; }
.status-badge.status-follow_up { background: #ffedd5; color: #c2410c; }
.status-badge.status-selesai { background: #d1fae5; color: #047857; }
.status-badge.status-ditolak { background: #fef2f2; color: #b91c1c; }
.status-badge.status-jadwal { background: #eff6ff; color: #1d4ed8; }
.status-badge.status-berlangsung { background: #fef3c7; color: #92400e; }
.status-badge.status-dibatalkan { background: #f1f5f9; color: #475569; }
.chart-box {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.15rem;
}
.chart-wrap { height: 260px; }
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 14px;
  border: 1px dashed #cbd5e1;
}
.empty-icon { margin-bottom: 1.25rem; color: #94a3b8; }
.empty-title { font-size: 1.2rem; font-weight: 600; margin: 0 0 0.5rem; color: #334155; }
.empty-desc {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  max-width: 360px;
  margin-left: auto;
  margin-right: auto;
}
.empty-desc.muted { padding: 0.75rem 0; max-width: none; }
.loading-wrap { padding: 0.5rem 0 1rem; }
.btn-primary,
.btn-secondary {
  padding: 0.55rem 1.1rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}
.btn-secondary:hover:not(:disabled) { background: #e2e8f0; }
.btn-compact { white-space: nowrap; }
.btn-secondary:disabled { opacity: 0.6; cursor: not-allowed; }
@media (max-width: 900px) {
  .split-tables { grid-template-columns: 1fr; }
  .stat-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 1100px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row;
    overflow-x: auto;
    border-right: none;
    border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
  .sec-hint { display: none; }
}
@media (max-width: 600px) {
  .laporan-bk-page { padding: 1rem; }
  .stat-cards { grid-template-columns: 1fr; }
  .chart-wrap { height: 220px; }
}
</style>

