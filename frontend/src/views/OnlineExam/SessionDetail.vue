<template>    <div class="session-detail-page">
      <router-link :to="backLink" class="back-link">← {{ backLabel }}</router-link>

      <header class="session-header">
        <div class="header-title-row">
          <h2>{{ session?.name || 'Sesi ujian' }}</h2>
          <span v-if="session?.status" :class="['status-badge', session.status]">{{ statusLabel(session.status, 'session') }}</span>
        </div>
        <div v-if="session" class="meta-grid">
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Ujian</span>
              <span class="meta-value">{{ session.exam?.name || '—' }}</span>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 7h8M8 11h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Mapel</span>
              <span class="meta-value">{{ session.exam?.subject?.name || '—' }}</span>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Durasi</span>
              <span class="meta-value">{{ session.exam?.duration_minutes != null ? `${session.exam.duration_minutes} menit` : '—' }}</span>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <div class="meta-body">
              <span class="meta-label">Peserta</span>
              <span class="meta-value">{{ participants.length }} siswa</span>
            </div>
          </div>
        </div>
      </header>

      <div v-if="loading" class="content-card"><p>Memuat...</p></div>
      <template v-else-if="session">

        <!-- Hanya tampil dari menu Kontrol Ujian (fokus=kontrol) atau dari detail ujian (tanpa fokus) -->
        <div v-if="fokus !== 'peserta'" ref="controlSectionRef" class="content-card control-section" :class="{ 'section-focus': fokus === 'kontrol' }">
          <h3>Kendali ujian</h3>
          <p v-if="fokus === 'kontrol'" class="section-hint">Untuk mengelola daftar peserta dan cetak kartu, gunakan menu <router-link to="/ujian-online/sesi?fokus=peserta">Peserta Ujian</router-link>.</p>
          <div class="button-group">
            <button v-if="session.status === 'draft' || session.status === 'scheduled'" class="btn-primary" @click="startSession" :disabled="controlLoading">Mulai ujian</button>
            <button v-if="session.status === 'started'" class="btn-danger" @click="endSession" :disabled="controlLoading">Akhiri ujian</button>
            <button v-if="session.status !== 'draft'" class="btn-secondary" @click="resetSession" :disabled="controlLoading">Reset sesi</button>
            <button v-if="session.status === 'ended'" class="btn-secondary" @click="computeScores" :disabled="controlLoading">Hitung nilai otomatis</button>
          </div>
          <div v-if="session.status === 'started'" class="entry-pin-block">
            <p class="entry-pin-label">Kode ujian untuk siswa (6 huruf, diperbarui tiap 20 menit):</p>
            <p class="entry-pin-value">{{ session.entry_pin || '–' }}</p>
            <p v-if="session.entry_pin_updated_at" class="entry-pin-time">Berlaku sejak: {{ formatEntryPinTime(session.entry_pin_updated_at) }}</p>
            <button type="button" class="btn-secondary" :disabled="entryPinLoading" @click="regenerateEntryPin">Rilis token baru</button>
          </div>
        </div>

        <!-- Monitoring & laporan: Kontrol Ujian / detail sesi (bukan menu Peserta saja) -->
        <div
          v-if="fokus !== 'peserta'"
          ref="monitorSectionRef"
          class="content-card monitor-section"
          :class="{ 'section-focus': fokus === 'kontrol' }"
        >
          <div class="monitor-header">
            <div>
              <h3>Monitoring peserta</h3>
              <p class="section-hint">
                Status live saat ujian berjalan. Koreksi uraian, rilis nilai, dan export laporan ada di sini.
                <span v-if="autoRefreshActive" class="live-dot" title="Memperbarui otomatis">● Live</span>
                <span v-else-if="lastMonitorAt" class="monitor-updated">Diperbarui {{ lastMonitorAt }}</span>
              </p>
            </div>
            <div class="monitor-actions">
              <button type="button" class="btn-secondary" :disabled="monitorLoading" @click="fetchSession(true)">
                {{ monitorLoading ? 'Memuat...' : 'Refresh' }}
              </button>
              <button
                type="button"
                class="btn-secondary"
                :disabled="exportLoading || !participants.length"
                @click="exportResults"
              >
                {{ exportLoading ? 'Mengekspor...' : 'Export Excel' }}
              </button>
              <button
                type="button"
                class="btn-primary"
                :disabled="releaseAllLoading || !canReleaseAny"
                @click="releaseAllScores"
              >
                {{ releaseAllLoading ? 'Merilis...' : 'Rilis semua nilai' }}
              </button>
            </div>
          </div>

          <div class="monitor-stats">
            <div class="monitor-stat">
              <span class="monitor-stat-value">{{ monitorSummary.total }}</span>
              <span class="monitor-stat-label">Total</span>
            </div>
            <div class="monitor-stat">
              <span class="monitor-stat-value">{{ monitorSummary.registered }}</span>
              <span class="monitor-stat-label">Belum masuk</span>
            </div>
            <div class="monitor-stat monitor-stat--active">
              <span class="monitor-stat-value">{{ monitorSummary.started }}</span>
              <span class="monitor-stat-label">Mengerjakan</span>
            </div>
            <div class="monitor-stat monitor-stat--done">
              <span class="monitor-stat-value">{{ monitorSummary.submitted }}</span>
              <span class="monitor-stat-label">Selesai</span>
            </div>
            <div class="monitor-stat">
              <span class="monitor-stat-value">{{ monitorSummary.scoreReleased }}</span>
              <span class="monitor-stat-label">Nilai dirilis</span>
            </div>
            <div class="monitor-stat">
              <span class="monitor-stat-value">{{ monitorSummary.avgScoreLabel }}</span>
              <span class="monitor-stat-label">Rata-rata nilai</span>
            </div>
          </div>

          <div v-if="participants.length === 0" class="monitor-empty">
            Belum ada peserta.
            <router-link :to="{ path: `/ujian-online/sesi/${route.params.id}`, query: { fokus: 'peserta' } }">Tambah peserta</router-link>
          </div>
          <template v-else>
            <div class="roster-toolbar">
              <input v-model="rosterSearch" type="search" class="search-input" placeholder="Cari nama / NIS / nomor peserta..." />
              <select v-model="rosterClassId" class="filter-select">
                <option value="">Semua kelas</option>
                <option v-for="c in rosterClassOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <p class="roster-meta">{{ rosterRangeLabel }}</p>
            <div v-if="pagedParticipants.length" class="table-scroll">
              <table class="data-table monitor-table">
                <thead>
                  <tr>
                    <th>No.</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Nilai</th>
                    <th>Rilis</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="group in monitorPageGroups" :key="'mon-g-' + group.key">
                    <tr class="group-row">
                      <td colspan="9">Kelas {{ group.name }}</td>
                    </tr>
                    <tr v-for="(p, i) in group.rows" :key="'mon-' + p.id">
                      <td>{{ p.participant_order ?? (group.start + i + 1) }}</td>
                      <td>
                        <strong>{{ p.student?.name || '–' }}</strong>
                        <div class="cell-meta">{{ p.nomor_peserta || p.student?.nisn || '–' }}</div>
                      </td>
                      <td>{{ p.student?.class?.name || '–' }}</td>
                      <td>
                        <span :class="['status-pill', p.status]">{{ participantStatusLabel(p.status) }}</span>
                      </td>
                      <td>{{ formatEntryPinTime(p.started_at) || '–' }}</td>
                      <td>{{ formatEntryPinTime(p.submitted_at) || '–' }}</td>
                      <td>
                        <template v-if="p.score != null">
                          {{ formatScore(p.score) }}
                          <span v-if="p.score_max != null" class="score-max">/ {{ formatScore(p.score_max) }}</span>
                        </template>
                        <template v-else>–</template>
                      </td>
                      <td>{{ p.score_released ? 'Ya' : 'Tidak' }}</td>
                      <td class="monitor-row-actions">
                        <button
                          type="button"
                          class="btn-action"
                          :disabled="p.status !== 'submitted'"
                          @click="openGrading(p)"
                        >
                          Koreksi
                        </button>
                        <button
                          type="button"
                          class="btn-action"
                          :disabled="p.status !== 'submitted' || p.score == null || p.score_released"
                          @click="releaseScore(p.id)"
                        >
                          Rilis
                        </button>
                        <button
                          type="button"
                          class="btn-action btn-delete"
                          :disabled="p.status === 'registered' || resetParticipantLoading === p.id"
                          @click="resetParticipant(p)"
                        >
                          Reset
                        </button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
            <p v-else class="monitor-empty">Tidak ada peserta yang cocok.</p>
            <PaginationBar
              :page="rosterPage"
              :last-page="rosterLastPage"
              :per-page="rosterPerPage"
              :total="filteredParticipants.length"
              item-label="peserta"
              @page-change="goRosterPage"
              @per-page-change="changeRosterPerPage"
            />
          </template>
        </div>

        <!-- Hanya tampil dari menu Peserta Ujian (fokus=peserta) atau dari detail ujian (tanpa fokus) -->
        <div v-if="fokus !== 'kontrol'" ref="pesertaSectionRef" class="content-card section" :class="{ 'section-focus': fokus === 'peserta' }">
          <h3>Peserta ({{ participants.length }})</h3>
          <p v-if="fokus === 'peserta'" class="section-hint">Untuk mulai/akhiri ujian, monitoring, dan laporan, gunakan menu <router-link to="/ujian-online/sesi?fokus=kontrol">Kontrol Ujian</router-link>.</p>
          <div class="form-group">
            <p>Tambahkan siswa dari data siswa. Urutkan per kelas lalu abjad, atau seret baris jika seluruh daftar tampil di satu halaman.</p>
            <div class="button-row">
              <button type="button" class="btn-primary" @click="showAddModal = true">+ Tambah peserta</button>
              <button type="button" class="btn-secondary" :disabled="reorderLoading || !participants.length" @click="sortParticipantsByClass">Urutkan per kelas</button>
              <button type="button" class="btn-secondary" :disabled="generateNumbersLoading || !participants.length" @click="generateParticipantNumbers">Generate nomor peserta</button>
              <button type="button" class="btn-secondary" :disabled="!participants.length" @click="openPrintCardsPreview">Cetak kartu peserta</button>
            </div>
            <div class="roster-toolbar">
              <input v-model="rosterSearch" type="search" class="search-input" placeholder="Cari nama / NIS / nomor peserta..." />
              <select v-model="rosterClassId" class="filter-select">
                <option value="">Semua kelas</option>
                <option v-for="c in rosterClassOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <p class="roster-meta">{{ rosterRangeLabel }}<template v-if="!canReorder"> · kosongkan filter untuk mengubah urutan seret</template></p>
          </div>
          <div v-if="pagedParticipants.length" class="table-scroll">
          <table class="data-table data-table-reorder">
            <thead>
              <tr>
                <th v-if="canReorder" class="col-reorder"></th>
                <th class="col-no">No.</th>
                <th>Nomor peserta</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>NISN</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in participantPageGroups" :key="'p-g-' + group.key">
                <tr class="group-row">
                  <td :colspan="canReorder ? 8 : 7">Kelas {{ group.name }}</td>
                </tr>
                <tr
                  v-for="(p, i) in group.rows"
                  :key="p.id"
                  :class="{ 'drag-over': dragOverIndex === participantIndex(p), 'dragging': dragFromIndex === participantIndex(p) }"
                  @dragover.prevent="canReorder && onDragOver($event, participantIndex(p))"
                  @drop="canReorder && onDrop($event, participantIndex(p))"
                >
                  <td v-if="canReorder" class="col-reorder">
                    <span class="drag-handle" draggable="true" title="Seret untuk mengubah urutan" aria-hidden="true" @dragstart="onDragStart($event, participantIndex(p))" @dragend="onDragEnd">⋮⋮</span>
                    <div class="move-buttons">
                      <button type="button" class="btn-move" title="Naik" :disabled="reorderLoading || participantIndex(p) === 0" @click.stop="moveUp(participantIndex(p))">↑</button>
                      <button type="button" class="btn-move" title="Turun" :disabled="reorderLoading || participantIndex(p) === participants.length - 1" @click.stop="moveDown(participantIndex(p))">↓</button>
                    </div>
                  </td>
                  <td class="col-no">{{ p.participant_order ?? (group.start + i + 1) }}</td>
                  <td class="col-nomor-peserta">{{ p.nomor_peserta || '–' }}</td>
                  <td>{{ p.student?.name }}</td>
                  <td>{{ p.student?.class?.name || '–' }}</td>
                  <td>{{ p.student?.nisn || '–' }}</td>
                  <td>{{ participantStatusLabel(p.status) }}</td>
                  <td>
                    <TableAction kind="edit" :disabled="p.status !== 'registered' || p.participant_order == null" @click="openEditParticipant(p)" />
                    <TableAction kind="delete" :disabled="p.status !== 'registered'" @click="removeParticipant(p)" />
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
          </div>
          <p v-else-if="participants.length" class="monitor-empty">Tidak ada peserta yang cocok.</p>
          <PaginationBar
            :page="rosterPage"
            :last-page="rosterLastPage"
            :per-page="rosterPerPage"
            :total="filteredParticipants.length"
            item-label="peserta"
            @page-change="goRosterPage"
            @per-page-change="changeRosterPerPage"
          />
        </div>

        <div v-if="showAddModal" class="modal-overlay" @click.self="closeAddModal">
          <div class="modal content-card add-participant-modal">
            <h3>Tambah peserta</h3>
            <p class="modal-desc">Pilih siswa yang akan ditambahkan ke sesi ini. Hanya siswa yang belum terdaftar ditampilkan.</p>
            <div v-if="studentsModalLoading" class="modal-loading">
              <span class="spinner"></span>
              <span>Memuat data siswa...</span>
            </div>
            <template v-else>
              <div v-if="availableStudents.length === 0" class="modal-empty">
                <p v-if="addParticipantClassId">
                  Tidak ada siswa di kelas ini yang belum terdaftar, atau kelas belum memiliki siswa.
                </p>
                <p v-else>Tidak ada siswa yang bisa ditambahkan. Semua siswa sudah terdaftar di sesi ini.</p>
              </div>
              <template v-else>
                <div class="add-participant-filter">
                  <label class="filter-label">Kelas / Rombel</label>
                  <select v-model="addParticipantClassId" class="filter-select">
                    <option value="">Semua kelas</option>
                    <option v-for="c in classList" :key="c.id" :value="c.id">{{ c.name }} (Kelas {{ c.grade ?? '–' }})</option>
                  </select>
                </div>
                <div class="add-participant-search">
                  <input
                    v-model="addParticipantSearch"
                    type="search"
                    placeholder="Cari nama, NISN, kelas..."
                    class="search-input"
                    autocomplete="off"
                  />
                </div>
                <div class="add-participant-toolbar">
                  <button type="button" class="btn-link" @click="selectAllAvailable">Pilih semua</button>
                  <span class="toolbar-sep">|</span>
                  <button type="button" class="btn-link" @click="selectedStudentIds = []">Hapus pilihan</button>
                  <span v-if="filteredAvailableStudents.length !== availableStudents.length" class="filter-badge">
                    {{ filteredAvailableStudents.length }} dari {{ availableStudents.length }}
                  </span>
                </div>
                <div class="add-participant-list" role="listbox" aria-multiselectable="true">
                  <label
                    v-for="s in filteredAvailableStudents"
                    :key="s.id"
                    class="add-participant-item"
                    :class="{ selected: selectedStudentIds.includes(s.id) }"
                  >
                    <input
                      v-model="selectedStudentIds"
                      type="checkbox"
                      :value="s.id"
                      class="item-checkbox"
                    />
                    <div class="item-content">
                      <span class="item-name">{{ s.name }}</span>
                      <span class="item-meta">
                        NISN {{ s.nisn || '–' }}
                        <template v-if="s.class_detail"> · Kelas {{ s.class_detail.grade ?? '–' }} · Rombel {{ s.class_detail.name || '–' }}</template>
                        <template v-else-if="s.class"> · {{ s.class }}</template>
                      </span>
                    </div>
                  </label>
                </div>
                <p class="selected-count">{{ selectedStudentIds.length }} siswa dipilih</p>
              </template>
            </template>
            <div class="modal-actions">
              <button
                class="btn-primary"
                :disabled="!selectedStudentIds.length || addLoading"
                @click="addParticipants"
              >
                {{ addLoading ? 'Menambah...' : 'Tambah peserta' }}
              </button>
              <button class="btn-secondary" @click="closeAddModal">Batal</button>
            </div>
          </div>
        </div>

        <div v-if="showGradingModal" class="modal-overlay" @click.self="closeGradingModal">
          <div class="modal content-card grading-modal">
            <h3>Koreksi nilai — {{ gradingParticipant?.student?.name }}</h3>
            <p class="hint">Berikan nilai untuk soal uraian. PG dan isian sudah dinilai otomatis.</p>
            <div v-if="gradingAnswersLoading" class="loading-text">Memuat jawaban...</div>
            <div v-else class="grading-list">
              <div v-for="a in gradingAnswers" :key="a.id" class="grading-item">
                <div class="grading-question">{{ a.body }}</div>
                <div v-if="a.type === 'uraian'" class="grading-answer">
                  <strong>Jawaban:</strong> {{ a.answer_text || '(kosong)' }}
                </div>
                <div class="grading-score-row">
                  <label>Nilai (maks {{ a.weight }}):</label>
                  <input v-if="a.type === 'uraian'" v-model.number="gradingScores[a.id]" type="number" min="0" :max="a.weight" step="0.01" class="score-input" />
                  <span v-else>{{ a.score != null ? a.score : '-' }}</span>
                </div>
              </div>
            </div>
            <div class="modal-actions">
              <button class="btn-primary" @click="saveGradingScores" :disabled="gradingSaving">Simpan nilai uraian</button>
              <button class="btn-secondary" @click="recomputeGradingParticipant" :disabled="gradingSaving || !gradingParticipant">Hitung ulang total</button>
              <button class="btn-secondary" @click="closeGradingModal">Tutup</button>
            </div>
          </div>
        </div>

        <div v-if="showEditParticipantModal" class="modal-overlay" @click.self="closeEditParticipantModal">
          <div class="modal content-card edit-participant-modal">
            <h3>Edit nomor peserta</h3>
            <p class="modal-desc">Ubah nomor urut peserta (hanya bisa diedit jika status Terdaftar).</p>
            <div v-if="editParticipant" class="form-group">
              <label class="filter-label">Peserta</label>
              <p class="edit-participant-name">{{ editParticipant.student?.name }}</p>
              <label class="filter-label">Nomor peserta (urut)</label>
              <input v-model.number="editParticipantOrder" type="number" min="1" :max="participants.length + 100" class="filter-select" />
            </div>
            <div class="modal-actions">
              <button class="btn-primary" :disabled="editParticipantSaving || editParticipantOrder < 1" @click="saveEditParticipant">Simpan</button>
              <button class="btn-secondary" @click="closeEditParticipantModal">Batal</button>
            </div>
          </div>
        </div>
      </template>
    </div></template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import TableAction from '@/components/TableAction.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { examApi } from '@/api/exam'
import { classApi } from '@/api/class'
import api from '@/api'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const toast = useToast()
const session = ref(null)
const participants = ref([])
const loading = ref(true)
const controlLoading = ref(false)
const monitorLoading = ref(false)
const exportLoading = ref(false)
const releaseAllLoading = ref(false)
const resetParticipantLoading = ref(null)
const lastMonitorAt = ref('')
const showAddModal = ref(false)
const controlSectionRef = ref(null)
const pesertaSectionRef = ref(null)
const monitorSectionRef = ref(null)
const availableStudents = ref([])
const selectedStudentIds = ref([])
const addLoading = ref(false)
const addParticipantSearch = ref('')
const studentsModalLoading = ref(false)
const addParticipantClassId = ref('')
const classList = ref([])
const showGradingModal = ref(false)
const gradingParticipant = ref(null)
const gradingAnswers = ref([])
const gradingAnswersLoading = ref(false)
const gradingScores = ref({})
const gradingSaving = ref(false)
const entryPinLoading = ref(false)
const showEditParticipantModal = ref(false)
const editParticipant = ref(null)
const editParticipantOrder = ref(1)
const editParticipantSaving = ref(false)
const generateNumbersLoading = ref(false)
const reorderLoading = ref(false)
const dragFromIndex = ref(null)
const dragOverIndex = ref(null)
const rosterPerPage = ref(20)
const rosterSearch = ref('')
const rosterClassId = ref('')
const rosterPage = ref(1)
let refreshTimer = null

function participantClass(p) {
  return p?.student?.class || null
}

function groupByClass(rows, getClass) {
  const groups = []
  let current = null
  let index = 0
  for (const row of rows || []) {
    const c = getClass(row)
    const key = String(c?.id ?? c?.name ?? '')
    const name = c?.name || 'Tanpa kelas'
    if (!current || current.key !== key) {
      current = { key, name, rows: [], start: index }
      groups.push(current)
    }
    current.rows.push(row)
    index += 1
  }
  return groups
}

const rosterClassOptions = computed(() => {
  const map = new Map()
  for (const p of participants.value) {
    const c = participantClass(p)
    if (!c?.id || map.has(c.id)) continue
    map.set(c.id, {
      id: c.id,
      name: c.name || '—',
      grade: c.grade == null || c.grade === '' ? 999 : Number(c.grade),
    })
  }
  return [...map.values()].sort((a, b) => (a.grade - b.grade) || a.name.localeCompare(b.name, 'id', { numeric: true }))
})

const filteredParticipants = computed(() => {
  const q = rosterSearch.value.trim().toLowerCase()
  const cid = rosterClassId.value
  return participants.value.filter((p) => {
    if (cid && String(participantClass(p)?.id ?? '') !== String(cid)) return false
    if (!q) return true
    const hay = `${p.student?.name || ''} ${p.student?.nis || ''} ${p.student?.nisn || ''} ${p.nomor_peserta || ''}`.toLowerCase()
    return hay.includes(q)
  })
})

const rosterLastPage = computed(() => Math.max(1, Math.ceil(filteredParticipants.value.length / rosterPerPage.value)))
const pagedParticipants = computed(() => {
  const start = (rosterPage.value - 1) * rosterPerPage.value
  return filteredParticipants.value.slice(start, start + rosterPerPage.value)
})
const rosterRangeLabel = computed(() => {
  const total = filteredParticipants.value.length
  if (!total) return '0 dari 0 peserta'
  const start = (rosterPage.value - 1) * rosterPerPage.value + 1
  const end = Math.min(rosterPage.value * rosterPerPage.value, total)
  return `Menampilkan ${start}–${end} dari ${total} peserta`
})
const canReorder = computed(() => !rosterSearch.value && !rosterClassId.value && rosterLastPage.value === 1)
const monitorPageGroups = computed(() => groupByClass(pagedParticipants.value, participantClass).map((group) => ({
  ...group,
  start: (rosterPage.value - 1) * rosterPerPage.value + group.start,
})))
const participantPageGroups = computed(() => monitorPageGroups.value)

function participantIndex(p) {
  return participants.value.findIndex((row) => row.id === p.id)
}

watch([rosterSearch, rosterClassId], () => { rosterPage.value = 1 })
watch(rosterLastPage, (last) => {
  if (rosterPage.value > last) rosterPage.value = last
})

function goRosterPage(page) {
  rosterPage.value = page
}

function changeRosterPerPage(n) {
  rosterPerPage.value = n
  rosterPage.value = 1
}

const fokus = computed(() => route.query.fokus || '')

const monitorSummary = computed(() => {
  const list = participants.value
  const submitted = list.filter(p => p.status === 'submitted')
  const withScore = submitted.filter(p => p.score != null)
  const avg = withScore.length
    ? withScore.reduce((sum, p) => sum + Number(p.score), 0) / withScore.length
    : null
  return {
    total: list.length,
    registered: list.filter(p => p.status === 'registered').length,
    started: list.filter(p => p.status === 'started').length,
    submitted: submitted.length,
    scoreReleased: list.filter(p => p.score_released).length,
    avgScoreLabel: avg != null ? formatScore(avg) : '–'
  }
})

const canReleaseAny = computed(() =>
  participants.value.some(p => p.status === 'submitted' && p.score != null && !p.score_released)
)

const autoRefreshActive = computed(() => session.value?.status === 'started')

const filteredAvailableStudents = computed(() => {
  const q = (addParticipantSearch.value || '').trim().toLowerCase()
  const list = availableStudents.value
  if (!q) return list
  return list.filter(s => {
    const name = (s.name || '').toLowerCase()
    const nis = (s.nis || '').toLowerCase()
    const nisn = (s.nisn || '').toLowerCase()
    const clsName = (s.class_detail?.name ?? s.class ?? '').toString().toLowerCase()
    const grade = (s.class_detail?.grade ?? '').toString().toLowerCase()
    return name.includes(q) || nis.includes(q) || nisn.includes(q) || clsName.includes(q) || grade.includes(q)
  })
})

const backLink = computed(() => {
  if (fokus.value === 'peserta') return { path: '/ujian-online/sesi', query: { fokus: 'peserta' } }
  if (fokus.value === 'kontrol') return { path: '/ujian-online/sesi', query: { fokus: 'kontrol' } }
  if (session.value?.exam?.code) {
    return { path: `/ujian-online/exams/${encodeURIComponent(session.value.exam.code)}` }
  }
  return { path: '/ujian-online/exams' }
})

const backLabel = computed(() => {
  if (fokus.value === 'peserta') return 'Peserta Ujian'
  if (fokus.value === 'kontrol') return 'Kontrol Ujian'
  if (session.value?.exam?.name) return session.value.exam.name
  return 'Daftar Ujian'
})

const printSessionCardsUrl = computed(() => {
  return session.value ? examApi.printSessionCardsUrl(session.value.id) : '#'
})

const printSessionCardsPreviewUrl = computed(() => {
  const base = printSessionCardsUrl.value
  if (base === '#') return base
  return base + (base.includes('?') ? '&' : '?') + 'preview=1'
})

function formatScore(val) {
  if (val == null || val === '') return '–'
  const n = Number(val)
  if (Number.isNaN(n)) return String(val)
  return Number.isInteger(n) ? String(n) : n.toFixed(2)
}

function openPrintCardsPreview() {
  if (!session.value || !participants.value.length) return
  window.open(printSessionCardsPreviewUrl.value, '_blank', 'noopener')
}

function printCardUrl(participantId) {
  return examApi.printCardUrl(participantId)
}

function statusLabel(s, type = 'session') {
  if (type === 'session') {
    const map = { draft: 'Draf', scheduled: 'Terjadwal', started: 'Berlangsung', ended: 'Selesai' }
    return map[s] || s
  }
  const map = { registered: 'Terdaftar', started: 'Mengerjakan', submitted: 'Selesai' }
  return map[s] || s
}
function participantStatusLabel(s) {
  const map = { registered: 'Terdaftar', started: 'Mengerjakan', submitted: 'Selesai' }
  return map[s] || s
}

function formatEntryPinTime(iso) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return iso
  }
}

function stopAutoRefresh() {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

function startAutoRefresh() {
  stopAutoRefresh()
  if (session.value?.status !== 'started') return
  refreshTimer = setInterval(() => {
    if (document.visibilityState === 'hidden') return
    fetchSession(true)
  }, 10000)
}

async function regenerateEntryPin() {
  if (!session.value?.id) return
  entryPinLoading.value = true
  try {
    const res = await examApi.regenerateEntryPin(session.value.id)
    const data = res.data
    if (data.entry_pin) {
      session.value = { ...session.value, entry_pin: data.entry_pin, entry_pin_updated_at: data.entry_pin_updated_at }
      toast.success('Kode ujian diperbarui: ' + data.entry_pin)
    }
  } catch (e) {
    toast.error('Gagal memperbarui kode ujian', e.response?.data?.message || 'Kode ujian tidak dapat diperbarui. Coba lagi.')
  } finally {
    entryPinLoading.value = false
  }
}

async function fetchSession(silent = false) {
  if (!silent) loading.value = true
  else monitorLoading.value = true
  try {
    const [sRes, pRes] = await Promise.all([
      examApi.getSession(route.params.id),
      examApi.listParticipants(route.params.id)
    ])
    session.value = sRes.data?.data ?? sRes.data
    participants.value = pRes.data?.data ?? pRes.data ?? []
    lastMonitorAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    if (session.value?.status === 'started') startAutoRefresh()
    else stopAutoRefresh()
  } catch (e) {
    if (!silent) {
      toast.error('Gagal memuat sesi ujian', e.response?.data?.message || 'Data sesi tidak dapat dimuat. Periksa koneksi dan coba lagi.')
    }
  } finally {
    loading.value = false
    monitorLoading.value = false
  }
}

async function exportResults() {
  if (!session.value?.id) return
  exportLoading.value = true
  try {
    const res = await examApi.exportSessionResults(session.value.id)
    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `hasil-ujian-${session.value.name || session.value.id}.xlsx`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
    toast.success('Laporan diekspor.')
  } catch (e) {
    toast.error('Gagal export laporan', e.response?.data?.message || 'File laporan tidak dapat diunduh. Coba lagi.')
  } finally {
    exportLoading.value = false
  }
}

async function releaseAllScores() {
  if (!session.value?.id || !canReleaseAny.value) return
  if (!confirm('Rilis semua nilai peserta yang sudah selesai dan punya skor?')) return
  releaseAllLoading.value = true
  try {
    const res = await examApi.releaseAllScores(session.value.id)
    toast.success(res.data?.message || 'Nilai dirilis.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal merilis nilai', e.response?.data?.message || 'Nilai tidak dapat dirilis. Coba lagi.')
  } finally {
    releaseAllLoading.value = false
  }
}

async function resetParticipant(p) {
  if (!p?.id || p.status === 'registered') return
  if (!confirm(`Reset peserta ${p.student?.name || ''}? Jawaban akan dihapus dan mereka bisa masuk lagi.`)) return
  resetParticipantLoading.value = p.id
  try {
    await examApi.resetParticipant(p.id)
    toast.success('Peserta direset.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal mereset peserta', e.response?.data?.message || 'Peserta tidak dapat direset. Coba lagi.')
  } finally {
    resetParticipantLoading.value = null
  }
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 200 })
    const data = res.data?.data ?? res.data
    classList.value = Array.isArray(data) ? data : (data?.data ?? [])
  } catch (_) {
    classList.value = []
  }
}

async function loadStudents() {
  studentsModalLoading.value = true
  try {
    const params = { per_page: 500 }
    if (addParticipantClassId.value) params.class_id = addParticipantClassId.value
    const res = await api.get('/v1/student', { params })
    const raw = res.data?.data ?? res.data
    const list = Array.isArray(raw) ? raw : (raw?.data ?? [])
    const existingIds = participants.value.map(p => p.student_id)
    availableStudents.value = list.filter(s => !existingIds.includes(s.id))
  } catch (_) {
    availableStudents.value = []
  } finally {
    studentsModalLoading.value = false
  }
}

function closeAddModal() {
  showAddModal.value = false
  addParticipantSearch.value = ''
  addParticipantClassId.value = ''
  selectedStudentIds.value = []
}

function selectAllAvailable() {
  selectedStudentIds.value = filteredAvailableStudents.value.map(s => s.id)
}

async function startSession() {
  controlLoading.value = true
  try {
    await examApi.startSession(route.params.id)
    toast.success('Ujian dimulai.')
    fetchSession()
  } catch (e) {
    toast.error('Gagal memulai ujian', e.response?.data?.message || 'Sesi tidak dapat dimulai. Coba lagi.')
  } finally {
    controlLoading.value = false
  }
}

async function endSession() {
  controlLoading.value = true
  try {
    await examApi.endSession(route.params.id)
    toast.success('Ujian diakhiri.')
    fetchSession()
  } catch (e) {
    toast.error('Gagal mengakhiri ujian', e.response?.data?.message || 'Sesi tidak dapat diakhiri. Coba lagi.')
  } finally {
    controlLoading.value = false
  }
}

async function resetSession() {
  if (!confirm('Reset sesi? Semua jawaban peserta akan dihapus.')) return
  controlLoading.value = true
  try {
    await examApi.resetSession(route.params.id)
    toast.success('Sesi direset.')
    fetchSession()
  } catch (e) {
    toast.error('Gagal mereset sesi', e.response?.data?.message || 'Sesi tidak dapat direset. Coba lagi.')
  } finally {
    controlLoading.value = false
  }
}

async function computeScores() {
  controlLoading.value = true
  try {
    await examApi.computeScores(route.params.id)
    toast.success('Nilai dihitung.')
    fetchSession()
  } catch (e) {
    toast.error('Gagal menghitung nilai', e.response?.data?.message || 'Nilai tidak dapat dihitung. Coba lagi.')
  } finally {
    controlLoading.value = false
  }
}

async function addParticipants() {
  addLoading.value = true
  try {
    await examApi.addParticipants(route.params.id, [...selectedStudentIds.value])
    toast.success('Berhasil', 'Peserta ditambahkan.')
    closeAddModal()
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal menambah peserta', e.response?.data?.message || 'Peserta tidak dapat ditambahkan. Coba lagi.')
  } finally {
    addLoading.value = false
  }
}

async function removeParticipant(p) {
  if (p.status !== 'registered' || !confirm('Hapus peserta?')) return
  try {
    await examApi.removeParticipant(p.id)
    toast.success('Berhasil', 'Peserta dihapus.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal menghapus peserta', e.response?.data?.message || 'Peserta tidak dapat dihapus. Coba lagi.')
  }
}

async function generateParticipantNumbers() {
  if (!route.params.id || !participants.value.length) return
  generateNumbersLoading.value = true
  try {
    await examApi.generateParticipantNumbers(route.params.id)
    toast.success('Berhasil', 'Nomor peserta di-generate.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal generate nomor peserta', e.response?.data?.message || 'Nomor peserta tidak dapat di-generate. Coba lagi.')
  } finally {
    generateNumbersLoading.value = false
  }
}

function openEditParticipant(p) {
  if (p.status !== 'registered') return
  editParticipant.value = p
  editParticipantOrder.value = p.participant_order ?? 0
  showEditParticipantModal.value = true
}

function closeEditParticipantModal() {
  showEditParticipantModal.value = false
  editParticipant.value = null
  editParticipantOrder.value = 1
}

async function saveEditParticipant() {
  if (!editParticipant.value || editParticipantOrder.value < 1) return
  editParticipantSaving.value = true
  try {
    await examApi.updateParticipant(editParticipant.value.id, { participant_order: editParticipantOrder.value })
    toast.success('Berhasil', 'Nomor peserta diperbarui.')
    closeEditParticipantModal()
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal menyimpan nomor peserta', e.response?.data?.message || 'Perubahan tidak dapat disimpan. Coba lagi.')
  } finally {
    editParticipantSaving.value = false
  }
}

function sortParticipantsByClass() {
  const sorted = [...participants.value].sort((a, b) => {
    const ca = participantClass(a)
    const cb = participantClass(b)
    const grade = (ca?.grade ?? 999) - (cb?.grade ?? 999)
    if (grade !== 0) return grade
    const className = String(ca?.name || '').localeCompare(String(cb?.name || ''), 'id', { numeric: true })
    if (className !== 0) return className
    return String(a.student?.name || '').localeCompare(String(b.student?.name || ''), 'id')
  })
  applyReorder(sorted.map((p) => p.id))
}

function getOrderedIds() {
  return participants.value.map(p => p.id)
}

async function applyReorder(orderedIds) {
  if (!route.params.id || orderedIds.length === 0) return
  reorderLoading.value = true
  try {
    await examApi.reorderParticipants(route.params.id, orderedIds)
    toast.success('Berhasil', 'Urutan peserta diperbarui.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal mengubah urutan peserta', e.response?.data?.message || 'Urutan tidak dapat diubah. Coba lagi.')
  } finally {
    reorderLoading.value = false
  }
}

function moveUp(idx) {
  if (idx <= 0) return
  const ids = getOrderedIds()
  ;[ids[idx - 1], ids[idx]] = [ids[idx], ids[idx - 1]]
  applyReorder(ids)
}

function moveDown(idx) {
  if (idx >= participants.value.length - 1) return
  const ids = getOrderedIds()
  ;[ids[idx], ids[idx + 1]] = [ids[idx + 1], ids[idx]]
  applyReorder(ids)
}

function onDragStart(e, idx) {
  dragFromIndex.value = idx
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(idx))
  e.dataTransfer.setData('application/json', JSON.stringify({ index: idx }))
  if (e.target) {
    try {
      e.target.classList.add('dragging')
    } catch (_) {}
  }
}

function onDragOver(e, idx) {
  e.preventDefault()
  if (dragFromIndex.value === null) return
  if (idx !== dragFromIndex.value) dragOverIndex.value = idx
}

function onDrop(e, toIndex) {
  e.preventDefault()
  const from = dragFromIndex.value
  if (from === null || from === toIndex) {
    dragFromIndex.value = null
    dragOverIndex.value = null
    return
  }
  const ids = getOrderedIds()
  const [moved] = ids.splice(from, 1)
  ids.splice(toIndex, 0, moved)
  dragFromIndex.value = null
  dragOverIndex.value = null
  applyReorder(ids)
}

function onDragEnd() {
  dragFromIndex.value = null
  dragOverIndex.value = null
}

async function releaseScore(participantId) {
  try {
    await examApi.releaseScore(participantId)
    toast.success('Berhasil', 'Nilai dirilis.')
    await fetchSession(true)
  } catch (e) {
    toast.error('Gagal merilis nilai', e.response?.data?.message || 'Nilai tidak dapat dirilis. Coba lagi.')
  }
}

function openGrading(p) {
  gradingParticipant.value = p
  gradingAnswers.value = []
  gradingScores.value = {}
  showGradingModal.value = true
  loadGradingAnswers(p.id)
}

async function loadGradingAnswers(participantId) {
  gradingAnswersLoading.value = true
  try {
    const res = await examApi.getParticipantAnswers(participantId)
    const list = res.data?.data ?? []
    gradingAnswers.value = list
    const scores = {}
    list.forEach(a => { scores[a.id] = a.score != null ? a.score : '' })
    gradingScores.value = scores
  } catch (e) {
    toast.error('Gagal memuat jawaban uraian', e.response?.data?.message || 'Jawaban tidak dapat dimuat. Coba lagi.')
  } finally {
    gradingAnswersLoading.value = false
  }
}

function closeGradingModal() {
  showGradingModal.value = false
  gradingParticipant.value = null
  gradingAnswers.value = []
}

async function saveGradingScores() {
  gradingSaving.value = true
  try {
    const uraianAnswers = gradingAnswers.value.filter(a => a.type === 'uraian')
    for (const a of uraianAnswers) {
      const val = gradingScores.value[a.id]
      if (val !== '' && val !== null && val !== undefined) {
        await examApi.updateAnswerScore(a.id, Number(val))
      }
    }
    toast.success('Nilai uraian disimpan.')
    if (gradingParticipant.value) loadGradingAnswers(gradingParticipant.value.id)
    fetchSession()
  } catch (e) {
    toast.error('Gagal menyimpan nilai uraian', e.response?.data?.message || 'Nilai tidak dapat disimpan. Coba lagi.')
  } finally {
    gradingSaving.value = false
  }
}

async function recomputeGradingParticipant() {
  if (!gradingParticipant.value) return
  gradingSaving.value = true
  try {
    await examApi.recomputeParticipant(gradingParticipant.value.id)
    toast.success('Nilai total dihitung ulang.')
    fetchSession()
    closeGradingModal()
  } catch (e) {
    toast.error('Gagal menghitung ulang nilai', e.response?.data?.message || 'Nilai tidak dapat dihitung ulang. Coba lagi.')
  } finally {
    gradingSaving.value = false
  }
}

onMounted(() => fetchSession())

onUnmounted(() => stopAutoRefresh())

watch(showAddModal, async (v) => {
  if (v) {
    await loadClasses()
    loadStudents()
  }
})

watch(addParticipantClassId, () => {
  if (showAddModal.value) loadStudents()
})

watch([loading, () => route.query.fokus], async ([isLoading, qFokus]) => {
  if (isLoading || !qFokus) return
  await nextTick()
  const el = qFokus === 'peserta'
    ? pesertaSectionRef.value
    : qFokus === 'kontrol'
      ? (monitorSectionRef.value || controlSectionRef.value)
      : null
  if (el && typeof el.scrollIntoView === 'function') el.scrollIntoView({ behavior: 'smooth', block: 'start' })
})
</script>

<style scoped>
.session-detail-page {
  padding: 1rem;
  padding-left: max(1rem, env(safe-area-inset-left));
  padding-right: max(1rem, env(safe-area-inset-right));
}
.back-link { display: inline-flex; align-items: center; color: #059669; text-decoration: none; font-weight: 600; margin-bottom: 12px; }
.session-header {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.15rem 1.25rem 1.2rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.header-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.session-header h2 { margin: 0; font-size: clamp(1.15rem, 2.5vw, 1.45rem); color: #0f172a; letter-spacing: -0.02em; }
.status-badge { font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 999px; font-weight: 600; }
.status-badge.draft { background: #f1f5f9; color: #475569; }
.status-badge.scheduled { background: #dbeafe; color: #1d4ed8; }
.status-badge.started { background: #dcfce7; color: #166534; }
.status-badge.ended { background: #fee2e2; color: #991b1b; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 8px 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: #ecfdf5; color: #059669;
}
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.meta-value { font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; word-break: break-word; }
@media (max-width: 768px) { .meta-grid { grid-template-columns: 1fr 1fr; } }
.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; }
.content-card.section-focus { outline: 2px solid #059669; outline-offset: 2px; }
.control-section .button-group { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem; }
.btn-primary { padding: 0.5rem 1rem; min-height: 44px; background: #059669; color: #fff; border: none; border-radius: 6px; touch-action: manipulation; }
.btn-danger { padding: 0.5rem 1rem; min-height: 44px; background: #dc2626; color: #fff; border: none; border-radius: 6px; touch-action: manipulation; }
.btn-secondary { padding: 0.5rem 1rem; min-height: 44px; background: #e5e7eb; color: #374151; border-radius: 6px; margin-right: 0.5rem; text-decoration: none; display: inline-block; touch-action: manipulation; }
.hint { font-size: 0.875rem; color: #6b7280; margin: 0.5rem 0; }
.entry-pin-block {
  margin: 1rem 0;
  padding: 1rem;
  background: #f0fdf4;
  border: 1px solid #86efac;
  border-radius: 8px;
}
.entry-pin-label { font-size: 0.875rem; color: #166534; margin: 0 0 0.25rem 0; }
.entry-pin-value { font-family: ui-monospace, monospace; font-size: 1.5rem; font-weight: 700; letter-spacing: 0.15em; color: #15803d; margin: 0.25rem 0; }
.entry-pin-time { font-size: 0.8rem; color: #64748b; margin: 0.25rem 0 0.5rem 0; }
.section-hint { font-size: 0.875rem; color: #64748b; margin: 0 0 0.75rem 0; }
.section-hint a { color: #059669; text-decoration: none; }
.section-hint a:hover { text-decoration: underline; }
.monitor-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.monitor-header h3 { margin: 0 0 0.25rem 0; }
.monitor-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.monitor-actions .btn-secondary,
.monitor-actions .btn-primary { margin-right: 0; }
.live-dot {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-left: 0.5rem;
  color: #16a34a;
  font-weight: 600;
  font-size: 0.8125rem;
}
.monitor-updated { margin-left: 0.5rem; font-size: 0.8125rem; color: #94a3b8; }
.monitor-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.monitor-stat {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.65rem 0.75rem;
  text-align: center;
}
.monitor-stat--active { background: #eff6ff; border-color: #bfdbfe; }
.monitor-stat--done { background: #f0fdf4; border-color: #bbf7d0; }
.monitor-stat-value { display: block; font-size: 1.25rem; font-weight: 700; color: #0f172a; }
.monitor-stat-label { display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.15rem; }
.monitor-empty { padding: 1rem 0; color: #64748b; font-size: 0.9375rem; }
.monitor-empty a { color: #059669; }
.table-scroll { overflow-x: auto; }
.roster-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 10px 0 6px;
}
.roster-toolbar .search-input,
.roster-toolbar .filter-select {
  flex: 1;
  min-width: 160px;
}
.roster-meta { margin: 0 0 8px; font-size: 0.8125rem; color: #64748b; }
.group-row td {
  background: #f1f5f9;
  font-weight: 700;
  font-size: 12px;
  color: #334155;
}
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  margin: 10px 0 0;
}
.btn-page {
  border: 1px solid #e5e7eb;
  background: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.45; cursor: not-allowed; }
.page-num { font-size: 12px; color: #475569; font-weight: 600; }
.monitor-table .cell-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 0.15rem; }
.monitor-table .score-max { color: #64748b; font-size: 0.8125rem; }
.monitor-row-actions { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.status-pill {
  display: inline-block;
  font-size: 0.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: #e2e8f0;
  color: #334155;
}
.status-pill.registered { background: #f1f5f9; color: #475569; }
.status-pill.started { background: #dbeafe; color: #1d4ed8; }
.status-pill.submitted { background: #dcfce7; color: #166534; }
.button-row { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-top: 0.5rem; }
.button-row .link.disabled { pointer-events: none; opacity: 0.6; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.data-table .col-no { width: 2.5rem; text-align: center; }
.data-table .col-reorder { width: 5rem; vertical-align: middle; }
.data-table .col-reorder .drag-handle { cursor: grab; color: #9ca3af; font-size: 1rem; padding: 0.25rem; user-select: none; }
.data-table .col-reorder .drag-handle:active { cursor: grabbing; }
.data-table .col-reorder .move-buttons { display: inline-flex; flex-direction: column; gap: 0; margin-left: 0.25rem; }
.data-table .col-reorder .btn-move { background: none; border: none; cursor: pointer; padding: 0.15rem 0.35rem; font-size: 0.875rem; color: #059669; line-height: 1; min-height: 28px; }
.data-table .col-reorder .btn-move:hover:not(:disabled) { background: #ecfdf5; border-radius: 4px; }
.data-table .col-reorder .btn-move:disabled { color: #d1d5db; cursor: not-allowed; }
.data-table tr.drag-over { background: #ecfdf5; }
.data-table tr.dragging { opacity: 0.5; }
.data-table .col-nomor-peserta { font-family: ui-monospace, monospace; font-size: 0.875rem; }
.btn-action {
  margin-right: 0.5rem;
  font-size: 0.875rem;
  color: #059669;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px 10px;
  min-height: 44px;
  min-width: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  touch-action: manipulation;
}
.btn-delete { color: #dc2626; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 16px; padding-left: max(16px, env(safe-area-inset-left)); padding-right: max(16px, env(safe-area-inset-right)); }
.modal { max-width: 480px; width: 100%; max-height: 90vh; overflow: auto; }
.add-participant-modal { max-width: 420px; display: flex; flex-direction: column; max-height: 85vh; }
.add-participant-modal .modal-desc { font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem 0; }
.modal-loading { display: flex; align-items: center; gap: 0.75rem; padding: 1.5rem; color: #6b7280; }
.spinner { width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top-color: #059669; border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.modal-empty { padding: 1rem; text-align: center; color: #6b7280; font-size: 0.875rem; }
.add-participant-filter { margin-bottom: 0.75rem; }
.add-participant-filter .filter-label { display: block; font-size: 0.8125rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; }
.add-participant-filter .filter-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9375rem;
  background: #fff;
}
.add-participant-filter .filter-select:focus {
  outline: none;
  border-color: #059669;
}
.add-participant-search { margin-bottom: 0.5rem; }
.add-participant-search .search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.9375rem;
}
.add-participant-search .search-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
}
.add-participant-toolbar { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem; font-size: 0.8125rem; }
.add-participant-toolbar .btn-link { background: none; border: none; color: #059669; cursor: pointer; padding: 0.25rem 0; }
.add-participant-toolbar .btn-link:hover { text-decoration: underline; }
.toolbar-sep { color: #d1d5db; }
.filter-badge { font-size: 0.75rem; color: #6b7280; }
.add-participant-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  margin-bottom: 0.5rem;
}
.add-participant-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  min-height: 44px;
  transition: background 0.15s;
}
.add-participant-item:last-child { border-bottom: none; }
.add-participant-item:hover { background: #f9fafb; }
.add-participant-item.selected { background: #ecfdf5; }
.add-participant-item .item-checkbox { flex-shrink: 0; width: 1rem; height: 1rem; accent-color: #059669; cursor: pointer; }
.add-participant-item .item-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.add-participant-item .item-name { font-weight: 500; }
.add-participant-item .item-meta { font-size: 0.8125rem; color: #6b7280; }
.selected-count { font-size: 0.8125rem; color: #6b7280; margin: 0 0 0.5rem 0; }
.full-width { width: 100%; }
.modal-actions { margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
.grading-modal { max-width: 560px; max-height: 90vh; overflow: auto; }
.grading-list { margin: 1rem 0; }
.grading-item { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
.grading-question { font-weight: 500; margin-bottom: 0.25rem; }
.grading-answer { font-size: 0.875rem; color: #374151; margin: 0.5rem 0; }
.grading-score-row { display: flex; align-items: center; gap: 0.5rem; }
.grading-score-row .score-input { width: 80px; padding: 0.25rem 0.5rem; }
.loading-text { padding: 1rem; }
.edit-participant-modal { max-width: 360px; }
.edit-participant-modal .edit-participant-name { font-weight: 500; margin: 0 0 0.5rem 0; }
.edit-participant-modal .form-group { margin-bottom: 0; }
.edit-participant-modal .filter-label { display: block; font-size: 0.8125rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; margin-top: 0.75rem; }
.edit-participant-modal .filter-label:first-child { margin-top: 0; }
.edit-participant-modal input[type="number"] { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.9375rem; }

@media (max-width: 768px) {
  .session-detail-page { padding: 12px; padding-left: max(12px, env(safe-area-inset-left)); padding-right: max(12px, env(safe-area-inset-right)); }
  .control-section .button-group { flex-direction: column; }
  .control-section .button-group .btn-primary,
  .control-section .button-group .btn-danger,
  .control-section .button-group .btn-secondary { width: 100%; margin-right: 0; }
  .modal { max-width: 100%; margin: 0; }
}

@media (max-width: 480px) {
  .session-detail-page { padding: 10px; padding-left: max(10px, env(safe-area-inset-left)); padding-right: max(10px, env(safe-area-inset-right)); }
  .data-table th, .data-table td { padding: 8px 10px; font-size: 13px; }
}
</style>
