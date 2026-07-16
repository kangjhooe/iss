<template>
  <Layout>
    <div class="ekskul-detail">
      <div class="detail-top">
        <button type="button" class="btn-back" @click="$router.push('/extracurricular')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Kembali
        </button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <div class="loading-spinner"></div>
        <p>Memuat data ekstrakurikuler...</p>
      </div>
      <div v-else-if="loadError" class="error-state">
        <p>{{ loadError }}</p>
        <button class="btn-primary" @click="loadItem">Coba lagi</button>
      </div>

      <template v-else>
        <div class="ekskul-header">
          <div class="header-info">
            <div class="header-title-row">
              <h1>{{ item?.name || 'Ekstrakurikuler' }}</h1>
              <span :class="['status-badge', item?.status === 'Aktif' ? 'status-active' : 'status-inactive']">
                {{ item?.status || '—' }}
              </span>
            </div>
            <p v-if="item?.description" class="header-desc">{{ item.description }}</p>
            <div class="meta-chips">
              <span v-if="item?.supervisor?.name" class="meta-chip">Pembina: {{ item.supervisor.name }}</span>
              <span v-if="scheduleText" class="meta-chip">{{ scheduleText }}</span>
              <span v-if="locationText" class="meta-chip">{{ locationText }}</span>
              <span class="meta-chip">{{ participants.length }} peserta</span>
            </div>
          </div>
        </div>

        <div class="section-nav" role="tablist">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            role="tab"
            :class="['sec-btn', { active: tab === t.key }]"
            @click="tab = t.key"
          >{{ t.label }}</button>
        </div>

        <!-- PESERTA -->
        <div v-show="tab === 'peserta'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Daftar Peserta</h2>
            <button type="button" class="btn-primary btn-sm" @click="showAddPeserta = !showAddPeserta">
              {{ showAddPeserta ? 'Tutup form' : 'Tambah Peserta' }}
            </button>
          </div>

          <div v-if="showAddPeserta" class="add-box">
            <div class="form-row">
              <select v-model="availableClassId" class="form-input" @change="onClassChange">
                <option value="">-- Pilih kelas --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <input
                v-if="availableClassId"
                v-model="availableSearch"
                type="text"
                class="form-input"
                placeholder="Cari siswa..."
                @input="debounceAvailable"
              />
            </div>
            <div v-if="availableLoading" class="muted">Memuat siswa...</div>
            <div v-else-if="availableClassId && availableStudents.length" class="available-list">
              <label class="check-all">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" /> Pilih semua ({{ availableStudents.length }})
              </label>
              <div class="available-scroll">
                <div v-for="s in availableStudents" :key="s.id" class="check-row" @click="toggleStudent(s.id)">
                  <input type="checkbox" :checked="selectedIds.includes(s.id)" @click.stop @change="toggleStudent(s.id)" />
                  <span class="check-name">{{ s.name }}</span>
                  <span class="muted">{{ s.nis || '—' }}</span>
                </div>
              </div>
              <button
                type="button"
                class="btn-primary btn-sm"
                :disabled="!selectedIds.length || savingPeserta"
                @click="submitPeserta"
              >{{ savingPeserta ? 'Menyimpan...' : `Tambah ${selectedIds.length} peserta` }}</button>
            </div>
            <p v-else-if="availableClassId" class="muted">Tidak ada siswa tersedia.</p>
          </div>

          <div v-if="pesertaLoading" class="muted pad-sm">Memuat peserta...</div>
          <div v-else-if="participants.length" class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Nama</th>
                  <th>NIS / NISN</th>
                  <th>Kelas</th>
                  <th class="col-aksi">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, i) in participants" :key="p.id">
                  <td class="col-no">{{ i + 1 }}</td>
                  <td class="cell-strong">{{ p.student?.name || '—' }}</td>
                  <td>{{ p.student?.nis || '—' }} / {{ p.student?.nisn || '—' }}</td>
                  <td>{{ p.student?.class?.name || '—' }}</td>
                  <td class="col-aksi">
                    <button type="button" class="btn-link danger" @click="removePeserta(p)">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-inline">Belum ada peserta.</div>
        </div>

        <!-- PERTEMUAN -->
        <div v-show="tab === 'pertemuan'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Pertemuan & Kehadiran</h2>
            <button type="button" class="btn-primary btn-sm" @click="showSessionForm = !showSessionForm">
              {{ showSessionForm ? 'Batal' : 'Tambah Pertemuan' }}
            </button>
          </div>

          <form v-if="showSessionForm" class="add-box" @submit.prevent="submitSession">
            <div class="form-grid">
              <div class="form-group">
                <label>Tanggal <span class="req">*</span></label>
                <input v-model="sessionForm.session_date" type="date" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam mulai</label>
                <input v-model="sessionForm.start_time" type="time" class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam selesai</label>
                <input v-model="sessionForm.end_time" type="time" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Materi / topik</label>
              <input v-model="sessionForm.topic" type="text" class="form-input" placeholder="Materi kegiatan" />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="sessionForm.notes" rows="2" class="form-input"></textarea>
            </div>
            <label class="check-all">
              <input v-model="sessionForm.with_attendance" type="checkbox" /> Buat daftar kehadiran dari peserta aktif
            </label>
            <button type="submit" class="btn-primary btn-sm" :disabled="savingSession">
              {{ savingSession ? 'Menyimpan...' : 'Simpan pertemuan' }}
            </button>
          </form>

          <div v-if="sessionsLoading" class="muted pad-sm">Memuat pertemuan...</div>
          <div v-else-if="sessions.length" class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Tanggal</th>
                  <th>Materi</th>
                  <th>Kehadiran</th>
                  <th class="col-aksi">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(s, i) in sessions" :key="s.id" :class="{ 'row-active': attendanceSession?.id === s.id }">
                  <td class="col-no">{{ i + 1 }}</td>
                  <td>
                    <div class="cell-strong">{{ formatDate(s.session_date) }}</div>
                    <div v-if="s.start_time" class="cell-sub">{{ s.start_time }}{{ s.end_time ? `–${s.end_time}` : '' }}</div>
                  </td>
                  <td>{{ s.topic || '—' }}</td>
                  <td>{{ s.attendances_count ?? 0 }} siswa</td>
                  <td class="col-aksi">
                    <button type="button" class="btn-link" @click="openAttendance(s)">Kehadiran</button>
                    <button type="button" class="btn-link danger" @click="deleteSession(s)">Hapus</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-inline">Belum ada pertemuan. Tambah pertemuan untuk mulai isi kehadiran.</div>

          <div v-if="attendanceSession" class="add-box attendance-box">
            <div class="panel-toolbar">
              <strong>Kehadiran — {{ formatDate(attendanceSession.session_date) }}{{ attendanceSession.topic ? ` · ${attendanceSession.topic}` : '' }}</strong>
              <button type="button" class="btn-secondary btn-sm" @click="attendanceSession = null">Tutup</button>
            </div>
            <div v-if="attendanceLoading" class="muted">Memuat kehadiran...</div>
            <template v-else>
              <div class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(a, i) in attendances" :key="a.student_id">
                      <td class="col-no">{{ i + 1 }}</td>
                      <td class="cell-strong">{{ a.student?.name }}</td>
                      <td>{{ a.student?.class?.name || '—' }}</td>
                      <td>
                        <select v-model="a.status" class="form-input form-input-sm status-select">
                          <option v-for="st in attendanceStatuses" :key="st" :value="st">{{ statusLabel(st) }}</option>
                        </select>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn-primary btn-sm" :disabled="savingAttendance" @click="saveAttendance">
                {{ savingAttendance ? 'Menyimpan...' : 'Simpan kehadiran' }}
              </button>
            </template>
          </div>
        </div>

        <!-- NILAI -->
        <div v-show="tab === 'nilai'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Nilai Peserta</h2>
            <button type="button" class="btn-primary btn-sm" :disabled="savingGrades || !grades.length" @click="saveGrades">
              {{ savingGrades ? 'Menyimpan...' : 'Simpan nilai' }}
            </button>
          </div>
          <div v-if="gradesLoading" class="muted pad-sm">Memuat nilai...</div>
          <div v-else-if="grades.length" class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th>Nilai</th>
                  <th>Predikat</th>
                  <th>Catatan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(g, i) in grades" :key="g.student_id">
                  <td class="col-no">{{ i + 1 }}</td>
                  <td class="cell-strong">{{ g.student?.name }}</td>
                  <td>{{ g.student?.class?.name || '—' }}</td>
                  <td>
                    <input v-model.number="g.score" type="number" min="0" max="100" step="0.01" class="form-input form-input-sm input-score" @input="onScoreInput(g)" />
                  </td>
                  <td>
                    <input v-model="g.predicate" type="text" maxlength="5" class="form-input form-input-sm input-pred" />
                  </td>
                  <td>
                    <input v-model="g.notes" type="text" class="form-input form-input-sm" placeholder="Opsional" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-inline">Belum ada peserta aktif untuk dinilai.</div>
        </div>

        <!-- LAPORAN -->
        <div v-show="tab === 'laporan'" class="panel">
          <div class="panel-toolbar report-toolbar">
            <h2 class="panel-title">Laporan Kegiatan</h2>
            <div class="toolbar-actions">
              <button type="button" class="btn-secondary btn-sm" :disabled="reportLoading" @click="loadReport">Refresh</button>
              <button type="button" class="btn-secondary btn-sm" :disabled="!report || exportingCsv" @click="exportReport">
                {{ exportingCsv ? 'Mengekspor...' : 'Export CSV' }}
              </button>
              <button type="button" class="btn-primary btn-sm" :disabled="!report || printingPdf" @click="printReportPdf">
                {{ printingPdf ? 'Menyiapkan...' : 'Cetak PDF' }}
              </button>
            </div>
          </div>

          <div class="report-filters">
            <div class="period-tabs">
              <button
                v-for="p in periodOptions"
                :key="p.value"
                type="button"
                :class="['period-btn', { active: reportFilters.period === p.value }]"
                @click="setPeriod(p.value)"
              >{{ p.label }}</button>
            </div>
            <div class="filter-controls">
              <select
                v-if="reportFilters.period === 'semester'"
                v-model="reportFilters.semester_id"
                class="form-input filter-input"
                @change="loadReport"
              >
                <option value="">Semester aktif</option>
                <option v-for="s in semesters" :key="s.id" :value="s.id">
                  {{ s.name }}{{ s.academic_year?.name ? ` · ${s.academic_year.name}` : '' }}
                </option>
              </select>
              <template v-if="reportFilters.period === 'year' || reportFilters.period === 'month'">
                <select v-model.number="reportFilters.year" class="form-input filter-input" @change="loadReport">
                  <option v-for="y in yearOptions" :key="y" :value="y">Tahun {{ y }}</option>
                </select>
              </template>
              <select
                v-if="reportFilters.period === 'month'"
                v-model.number="reportFilters.month"
                class="form-input filter-input"
                @change="loadReport"
              >
                <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </div>
          </div>

          <div v-if="reportLoading" class="muted pad-sm">Memuat laporan...</div>
          <template v-else-if="report">
            <p class="period-label">Periode: <strong>{{ report.period?.label || '—' }}</strong></p>

            <div class="stat-grid">
              <div class="stat-card">
                <div class="stat-val">{{ report.participants_count ?? 0 }}</div>
                <div class="stat-label">Peserta</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.sessions_count ?? 0 }}</div>
                <div class="stat-label">Pertemuan</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.attendance?.hadir ?? 0 }}</div>
                <div class="stat-label">Total Hadir</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.attendance?.hadir_pct != null ? report.attendance.hadir_pct + '%' : '—' }}</div>
                <div class="stat-label">% Kehadiran</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.grades?.average_score ?? '—' }}</div>
                <div class="stat-label">Rata-rata Nilai</div>
              </div>
            </div>

            <div class="report-section">
              <h3 class="report-section-title">Daftar Pertemuan ({{ report.sessions?.length || 0 }})</h3>
              <div v-if="report.sessions?.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Tanggal</th>
                      <th>Jam</th>
                      <th>Materi</th>
                      <th>Kehadiran</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(s, i) in report.sessions" :key="s.id || i">
                      <td class="col-no">{{ i + 1 }}</td>
                      <td>{{ formatDate(s.session_date) }}</td>
                      <td>{{ s.start_time || s.end_time ? `${s.start_time || '?'}–${s.end_time || '?'}` : '—' }}</td>
                      <td>{{ s.topic || '—' }}</td>
                      <td>{{ s.attendances_count ?? 0 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Tidak ada pertemuan pada periode ini.</p>
            </div>

            <div class="report-section">
              <h3 class="report-section-title">
                Rekap Kehadiran per Pertemuan
                <span class="title-meta">
                  ({{ report.attendance_matrix?.rows?.length || 0 }} peserta × {{ report.attendance_matrix?.sessions?.length || 0 }} pertemuan)
                </span>
              </h3>
              <p class="matrix-hint">
                Setiap kolom = satu pertemuan. H = Hadir, I = Izin, S = Sakit, A = Alpha, — = belum dicatat.
              </p>
              <div v-if="report.attendance_matrix?.sessions?.length && report.attendance_matrix?.rows?.length" class="table-scroll matrix-scroll">
                <table class="data-table matrix-table">
                  <thead>
                    <tr>
                      <th class="col-no sticky-col">No</th>
                      <th class="sticky-col sticky-name">Nama</th>
                      <th>Kelas</th>
                      <th
                        v-for="s in report.attendance_matrix.sessions"
                        :key="s.id"
                        class="col-center col-session"
                        :title="sessionColTitle(s)"
                      >
                        <span class="session-day">{{ s.label }}</span>
                        <span class="session-month">{{ sessionMonthLabel(s.session_date) }}</span>
                      </th>
                      <th class="col-center">H</th>
                      <th class="col-center">I</th>
                      <th class="col-center">S</th>
                      <th class="col-center">A</th>
                      <th class="col-center">%</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(r, i) in report.attendance_matrix.rows" :key="r.student_id">
                      <td class="col-no sticky-col">{{ i + 1 }}</td>
                      <td class="cell-strong sticky-col sticky-name">{{ r.name }}</td>
                      <td>{{ r.class?.name || '—' }}</td>
                      <td
                        v-for="s in report.attendance_matrix.sessions"
                        :key="s.id"
                        class="col-center"
                      >
                        <span :class="['att-code', attCodeClass(matrixStatus(r, s.id))]">
                          {{ attCode(matrixStatus(r, s.id)) }}
                        </span>
                      </td>
                      <td class="col-center">{{ r.hadir }}</td>
                      <td class="col-center">{{ r.izin }}</td>
                      <td class="col-center">{{ r.sakit }}</td>
                      <td class="col-center">{{ r.alpha }}</td>
                      <td class="col-center">{{ r.hadir_pct != null ? r.hadir_pct + '%' : '—' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Belum ada pertemuan/peserta untuk ditampilkan dalam matriks kehadiran.</p>
            </div>

            <div v-if="report.period?.type !== 'month'" class="report-section">
              <h3 class="report-section-title">Rekap Ringkas per Siswa ({{ report.per_student?.length || 0 }})</h3>
              <div v-if="report.per_student?.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th class="col-center">Hadir</th>
                      <th class="col-center">Izin</th>
                      <th class="col-center">Sakit</th>
                      <th class="col-center">Alpha</th>
                      <th class="col-center">% Hadir</th>
                      <th class="col-center">Nilai</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(r, i) in report.per_student" :key="r.student_id">
                      <td class="col-no">{{ i + 1 }}</td>
                      <td class="cell-strong">{{ r.name }}</td>
                      <td>{{ r.class?.name || '—' }}</td>
                      <td class="col-center">{{ r.hadir }}</td>
                      <td class="col-center">{{ r.izin }}</td>
                      <td class="col-center">{{ r.sakit }}</td>
                      <td class="col-center">{{ r.alpha }}</td>
                      <td class="col-center">{{ r.hadir_pct != null ? r.hadir_pct + '%' : '—' }}</td>
                      <td class="col-center">{{ r.score != null ? r.score : '—' }}{{ r.predicate ? ` (${r.predicate})` : '' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Belum ada data kehadiran untuk direkap.</p>
            </div>
          </template>
          <div v-else class="empty-inline">Pilih periode lalu muat laporan.</div>
        </div>
      </template>

      <ConfirmDialog
        :show="confirmDialog.show"
        :title="confirmDialog.title"
        :message="confirmDialog.message"
        :warning="confirmDialog.warning"
        :loading="confirmDialog.loading"
        @confirm="handleConfirm"
        @cancel="handleCancel"
        @update:show="confirmDialog.show = $event"
      />
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { extracurricularApi } from '@/api/extracurricular'
import { classApi } from '@/api/class'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'

const route = useRoute()
const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const id = computed(() => Number(route.params.id))
const tabs = [
  { key: 'peserta', label: 'Peserta' },
  { key: 'pertemuan', label: 'Pertemuan' },
  { key: 'nilai', label: 'Nilai' },
  { key: 'laporan', label: 'Laporan' },
]
const tab = ref('peserta')

const item = ref(null)
const loading = ref(true)
const loadError = ref('')

const DAYS = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu' }
const scheduleText = computed(() => {
  const days = Array.isArray(item.value?.day_labels) && item.value.day_labels.length
    ? item.value.day_labels
    : (item.value?.days_of_week || []).map((d) => DAYS[d]).filter(Boolean)
  if (!days.length) return ''
  const time = item.value.start_time || item.value.end_time
    ? ` ${String(item.value.start_time || '?').slice(0, 5)}-${String(item.value.end_time || '?').slice(0, 5)}`
    : ''
  return days.join(', ') + time
})
const locationText = computed(() => {
  if (item.value?.is_outdoor) {
    return item.value.location_note ? `Di luar: ${item.value.location_note}` : 'Di luar ruangan'
  }
  return item.value?.room?.name || ''
})

const classes = ref([])
const semesters = ref([])
const participants = ref([])
const pesertaLoading = ref(false)
const showAddPeserta = ref(false)
const availableClassId = ref('')
const availableSearch = ref('')
const availableStudents = ref([])
const availableLoading = ref(false)
const selectedIds = ref([])
const savingPeserta = ref(false)

const sessions = ref([])
const sessionsLoading = ref(false)
const showSessionForm = ref(false)
const savingSession = ref(false)
const sessionForm = ref({
  session_date: new Date().toISOString().slice(0, 10),
  start_time: '',
  end_time: '',
  topic: '',
  notes: '',
  with_attendance: true,
})

const attendanceSession = ref(null)
const attendances = ref([])
const attendanceStatuses = ref(['hadir', 'izin', 'sakit', 'alpha'])
const attendanceLoading = ref(false)
const savingAttendance = ref(false)

const grades = ref([])
const gradesLoading = ref(false)
const savingGrades = ref(false)

const report = ref(null)
const reportLoading = ref(false)
const exportingCsv = ref(false)
const printingPdf = ref(false)

const now = new Date()
const reportFilters = ref({
  period: 'semester',
  semester_id: '',
  year: now.getFullYear(),
  month: now.getMonth() + 1,
})

const periodOptions = [
  { value: 'semester', label: 'Per Semester' },
  { value: 'year', label: 'Per Tahun' },
  { value: 'month', label: 'Per Bulan' },
]

const monthOptions = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
]

const yearOptions = computed(() => {
  const y = now.getFullYear()
  return [y + 1, y, y - 1, y - 2, y - 3]
})

function reportParams() {
  const f = reportFilters.value
  const params = { period: f.period }
  if (f.period === 'semester') {
    if (f.semester_id) params.semester_id = f.semester_id
  } else if (f.period === 'year') {
    params.year = f.year
  } else if (f.period === 'month') {
    params.year = f.year
    params.month = f.month
  }
  return params
}

function setPeriod(period) {
  reportFilters.value.period = period
  loadReport()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
function statusLabel(s) {
  return ({ hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpha: 'Alpha' })[s] || s
}
function matrixStatus(row, sessionId) {
  if (!row?.statuses) return null
  return row.statuses[String(sessionId)] ?? row.statuses[sessionId] ?? null
}
function attCode(status) {
  if (!status) return '—'
  return ({ hadir: 'H', izin: 'I', sakit: 'S', alpha: 'A' })[status] || String(status).charAt(0).toUpperCase()
}
function attCodeClass(status) {
  if (!status) return 'att-empty'
  return `att-${status}`
}
function sessionMonthLabel(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { month: 'short' })
}
function sessionColTitle(s) {
  const date = formatDate(s.session_date)
  return s.topic ? `${date} · ${s.topic}` : date
}
function predicateFromScore(score) {
  if (score == null || score === '') return ''
  const n = Number(score)
  if (n >= 90) return 'A'
  if (n >= 80) return 'B'
  if (n >= 70) return 'C'
  return 'D'
}
function onScoreInput(g) {
  if (g.score === '' || g.score == null) {
    g.predicate = ''
    return
  }
  g.predicate = predicateFromScore(g.score)
}

async function loadItem() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await extracurricularApi.get(id.value)
    item.value = res.data.data || res.data
  } catch (e) {
    loadError.value = e.formattedMessage || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

async function loadPeserta() {
  pesertaLoading.value = true
  try {
    const res = await extracurricularApi.getStudents(id.value)
    const raw = res.data?.data ?? res.data
    participants.value = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : [])
  } catch (e) {
    participants.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat peserta')
  } finally {
    pesertaLoading.value = false
  }
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ status: 'Aktif', per_page: 200 })
    classes.value = res.data.data || []
  } catch {
    classes.value = []
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

function onClassChange() {
  selectedIds.value = []
  availableStudents.value = []
  availableSearch.value = ''
  if (availableClassId.value) loadAvailable()
}

async function loadAvailable() {
  if (!availableClassId.value) return
  availableLoading.value = true
  try {
    const params = { class_id: availableClassId.value, per_page: 200 }
    if (availableSearch.value) params.search = availableSearch.value
    const res = await extracurricularApi.getAvailableStudents(id.value, params)
    const data = res.data?.data ?? res.data
    availableStudents.value = Array.isArray(data) ? data : []
  } catch (e) {
    availableStudents.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat siswa')
  } finally {
    availableLoading.value = false
  }
}

let availDebounce
function debounceAvailable() {
  clearTimeout(availDebounce)
  availDebounce = setTimeout(loadAvailable, 350)
}

const allSelected = computed(() =>
  availableStudents.value.length > 0 && selectedIds.value.length === availableStudents.value.length
)
function toggleAll() {
  selectedIds.value = allSelected.value ? [] : availableStudents.value.map((s) => s.id)
}
function toggleStudent(sid) {
  const i = selectedIds.value.indexOf(sid)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(sid)
}

async function submitPeserta() {
  if (!selectedIds.value.length) return
  savingPeserta.value = true
  try {
    const res = await extracurricularApi.addStudents(id.value, { student_ids: selectedIds.value })
    toast.success('Berhasil', res.data?.message || 'Peserta ditambahkan')
    showAddPeserta.value = false
    selectedIds.value = []
    availableClassId.value = ''
    availableStudents.value = []
    await loadPeserta()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menambah peserta')
  } finally {
    savingPeserta.value = false
  }
}

function removePeserta(p) {
  showConfirm({
    title: 'Keluarkan Peserta',
    message: `Keluarkan ${p.student?.name || 'siswa ini'} dari ekskul?`,
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.removeStudent(id.value, p.student_id)
      toast.success('Berhasil', 'Peserta dikeluarkan')
      await loadPeserta()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || 'Gagal mengeluarkan')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function loadSessions() {
  sessionsLoading.value = true
  try {
    const res = await extracurricularApi.getSessions(id.value)
    sessions.value = res.data.data || []
  } catch (e) {
    sessions.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat pertemuan')
  } finally {
    sessionsLoading.value = false
  }
}

async function submitSession() {
  savingSession.value = true
  try {
    const payload = { ...sessionForm.value }
    if (!payload.start_time) payload.start_time = null
    if (!payload.end_time) payload.end_time = null
    await extracurricularApi.createSession(id.value, payload)
    toast.success('Berhasil', 'Pertemuan ditambahkan')
    showSessionForm.value = false
    sessionForm.value = {
      session_date: new Date().toISOString().slice(0, 10),
      start_time: '',
      end_time: '',
      topic: '',
      notes: '',
      with_attendance: true,
    }
    await loadSessions()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan pertemuan')
  } finally {
    savingSession.value = false
  }
}

function deleteSession(s) {
  showConfirm({
    title: 'Hapus Pertemuan',
    message: `Hapus pertemuan ${formatDate(s.session_date)}? Data kehadiran terkait ikut terhapus.`,
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.deleteSession(id.value, s.id)
      toast.success('Berhasil', 'Pertemuan dihapus')
      if (attendanceSession.value?.id === s.id) attendanceSession.value = null
      await loadSessions()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || 'Gagal menghapus')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function openAttendance(s) {
  attendanceSession.value = s
  attendanceLoading.value = true
  try {
    const res = await extracurricularApi.getAttendances(id.value, s.id)
    attendances.value = res.data.data || []
    if (res.data.statuses) attendanceStatuses.value = res.data.statuses
  } catch (e) {
    attendances.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat kehadiran')
  } finally {
    attendanceLoading.value = false
  }
}

async function saveAttendance() {
  savingAttendance.value = true
  try {
    await extracurricularApi.saveAttendances(id.value, attendanceSession.value.id, {
      attendances: attendances.value.map((a) => ({
        student_id: a.student_id,
        status: a.status,
        notes: a.notes || null,
      })),
    })
    toast.success('Berhasil', 'Kehadiran disimpan')
    await loadSessions()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan kehadiran')
  } finally {
    savingAttendance.value = false
  }
}

async function loadGrades() {
  gradesLoading.value = true
  try {
    const res = await extracurricularApi.getGrades(id.value)
    grades.value = (res.data.data || []).map((g) => ({ ...g }))
  } catch (e) {
    grades.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat nilai')
  } finally {
    gradesLoading.value = false
  }
}

async function saveGrades() {
  savingGrades.value = true
  try {
    await extracurricularApi.saveGrades(id.value, {
      grades: grades.value.map((g) => ({
        student_id: g.student_id,
        score: g.score === '' || g.score == null ? null : g.score,
        predicate: g.predicate || null,
        notes: g.notes || null,
      })),
    })
    toast.success('Berhasil', 'Nilai disimpan')
    await loadGrades()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan nilai')
  } finally {
    savingGrades.value = false
  }
}

async function loadReport() {
  reportLoading.value = true
  try {
    const res = await extracurricularApi.getReport(id.value, reportParams())
    report.value = res.data.data || null
  } catch (e) {
    report.value = null
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat laporan')
  } finally {
    reportLoading.value = false
  }
}

async function exportReport() {
  exportingCsv.value = true
  try {
    const res = await extracurricularApi.exportReport(id.value, reportParams())
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `laporan-${item.value?.name || 'ekskul'}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(link.href)
    toast.success('Berhasil', 'Laporan CSV diunduh')
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal export CSV')
  } finally {
    exportingCsv.value = false
  }
}

async function printReportPdf() {
  printingPdf.value = true
  try {
    const res = await extracurricularApi.exportReportPdf(id.value, reportParams())
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Laporan — ${item.value?.name || 'Ekskul'}`
    const periodHint = report.value?.period?.label ? ` · ${report.value.period.label}` : ''
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
        <h1>${title}<span class="hint">Preview cetak${periodHint}</span></h1>
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
    toast.error('Gagal', e.formattedMessage || 'Gagal membuka preview laporan PDF')
  } finally {
    printingPdf.value = false
  }
}

watch(tab, (t) => {
  if (t === 'peserta') loadPeserta()
  if (t === 'pertemuan') loadSessions()
  if (t === 'nilai') loadGrades()
  if (t === 'laporan') loadReport()
})

watch(() => route.params.id, async () => {
  await loadItem()
  tab.value = 'peserta'
  await Promise.all([loadPeserta(), loadClasses(), loadSemesters()])
})

onMounted(async () => {
  await loadItem()
  await Promise.all([loadPeserta(), loadClasses(), loadSemesters()])
})
</script>

<style scoped>
.ekskul-detail {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 25%, #f1f5f9 100%);
}

.detail-top {
  margin-bottom: 12px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: none;
  background: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  font-size: 14px;
}

.ekskul-header {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.header-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.header-info h1 {
  margin: 0;
  font-size: clamp(1.15rem, 2.5vw, 1.5rem);
  color: #0f172a;
}

.header-desc {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.45;
}

.meta-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.meta-chip {
  display: inline-block;
  padding: 4px 10px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 999px;
  font-size: 12px;
  color: #047857;
  font-weight: 500;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-active { background: #c6f6d5; color: #22543d; }
.status-inactive { background: #fed7d7; color: #742a2a; }

.section-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}

.sec-btn {
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 999px;
  padding: 0.45rem 0.9rem;
  cursor: pointer;
  font-size: 0.88rem;
  color: #475569;
  font-weight: 500;
}

.sec-btn.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
}

.panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.panel-toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
}

.panel-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.toolbar-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 0 -4px;
  padding: 0 4px;
}

.data-table {
  width: 100%;
  min-width: 560px;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.data-table th {
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #065f46;
  padding: 12px 14px;
  font-weight: 600;
  white-space: nowrap;
}

.data-table td {
  padding: 12px 14px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #334155;
  vertical-align: middle;
}

.data-table tbody tr:hover { background: #f8fafc; }
.data-table-compact { min-width: 420px; }
.row-active { background: #ecfdf5 !important; }

.col-no { width: 48px; text-align: center; color: #94a3b8; }
.col-aksi { width: 130px; text-align: right; white-space: nowrap; }
.col-center { text-align: center; }
.cell-strong { font-weight: 600; color: #0f172a; }
.cell-sub { font-size: 12px; color: #94a3b8; }

.form-row, .form-grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.form-grid .form-group { flex: 1; min-width: 140px; }
.form-group { margin-bottom: 10px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: #334155; }
.req { color: #dc2626; }

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-input-sm { padding: 6px 8px; font-size: 13px; }
.input-score { width: 88px; max-width: 100%; }
.input-pred { width: 64px; max-width: 100%; }
.status-select { min-width: 110px; }

.btn-primary, .btn-secondary {
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.2);
}

.btn-primary:disabled, .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.btn-sm { padding: 7px 12px; }

.btn-link {
  border: none;
  background: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  margin-left: 6px;
  padding: 0;
}

.btn-link.danger { color: #dc2626; }

.add-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 14px;
}

.attendance-box { margin-top: 14px; }

.check-all, .check-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  margin-bottom: 6px;
  cursor: pointer;
}

.check-name { font-weight: 500; color: #0f172a; }
.check-row .muted { margin-left: auto; }

.available-scroll {
  max-height: 240px;
  overflow-y: auto;
  margin-bottom: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  background: #fff;
}

.muted { color: #94a3b8; font-size: 14px; }
.pad-sm { padding: 12px 0; }
.empty-inline {
  padding: 28px 12px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.loading-wrap, .error-state {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 48px 24px;
  text-align: center;
  color: #64748b;
}

.error-state { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.error-state p { margin: 0 0 16px; }

.loading-spinner {
  width: 28px;
  height: 28px;
  border: 2px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Laporan */
.report-filters {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
  padding: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.period-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.period-btn {
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.period-btn.active {
  background: #ecfdf5;
  border-color: #059669;
  color: #047857;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-input {
  max-width: 240px;
  min-width: 140px;
  flex: 1;
}

.period-label {
  margin: 0 0 14px;
  font-size: 13px;
  color: #64748b;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px;
  margin-bottom: 18px;
}

.stat-card {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 12px;
  text-align: center;
}

.stat-val { font-size: 20px; font-weight: 700; color: #0f172a; }
.stat-label { font-size: 11px; color: #64748b; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.03em; }

.report-section { margin-bottom: 20px; }

.report-section-title {
  margin: 0 0 8px;
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.title-meta {
  font-weight: 500;
  text-transform: none;
  color: #64748b;
  letter-spacing: 0;
}

.matrix-hint {
  margin: 0 0 10px;
  font-size: 12px;
  color: #64748b;
}

.matrix-scroll {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.matrix-table {
  min-width: 640px;
}

.matrix-table th.col-session {
  min-width: 42px;
  padding: 8px 6px;
  vertical-align: bottom;
}

.session-day {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
  line-height: 1.2;
}

.session-month {
  display: block;
  font-size: 10px;
  font-weight: 500;
  color: #94a3b8;
  text-transform: none;
  letter-spacing: 0;
}

.att-code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.att-hadir { background: #d1fae5; color: #047857; }
.att-izin { background: #ffedd5; color: #c2410c; }
.att-sakit { background: #dbeafe; color: #1d4ed8; }
.att-alpha { background: #fee2e2; color: #dc2626; }
.att-empty { color: #cbd5e1; font-weight: 500; }

.sticky-col {
  position: sticky;
  left: 0;
  background: #fff;
  z-index: 1;
}

.matrix-table thead .sticky-col {
  background: #d1fae5;
  z-index: 2;
}

.sticky-name {
  left: 48px;
  min-width: 120px;
}

.matrix-table tbody tr:hover .sticky-col {
  background: #f8fafc;
}

@media (max-width: 768px) {
  .panel { padding: 12px; }
  .report-toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-actions { width: 100%; }
  .toolbar-actions .btn-sm { flex: 1; justify-content: center; }
  .filter-input { max-width: none; width: 100%; }
  .filter-controls { flex-direction: column; }
  .data-table { min-width: 520px; }
  .data-table th, .data-table td { padding: 10px 12px; }
  .sticky-col { position: static; }
  .sticky-name { left: auto; }
}

@media (max-width: 480px) {
  .sec-btn { flex: 1 1 calc(50% - 0.4rem); text-align: center; justify-content: center; }
  .period-btn { flex: 1 1 calc(33% - 6px); text-align: center; font-size: 12px; padding: 7px 8px; }
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
