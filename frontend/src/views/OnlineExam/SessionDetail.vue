<template>
  <Layout>
    <div class="session-detail-page">
      <header class="page-header">
        <router-link :to="backLink" class="back-link">← {{ backLabel }}</router-link>
        <h2>{{ session?.name }}</h2>
        <span :class="['status-badge', session?.status]">{{ session?.status ? statusLabel(session.status, 'session') : '' }}</span>
      </header>

      <div v-if="loading" class="content-card"><p>Memuat...</p></div>
      <template v-else-if="session">
        <div class="content-card info">
          <p><strong>Ujian</strong> {{ session.exam?.name }} — {{ session.exam?.subject?.name }}</p>
          <p><strong>Durasi</strong> {{ session.exam?.duration_minutes }} menit</p>
        </div>

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

        <!-- Hanya tampil dari menu Peserta Ujian (fokus=peserta) atau dari detail ujian (tanpa fokus) -->
        <div v-if="fokus !== 'kontrol'" ref="pesertaSectionRef" class="content-card section" :class="{ 'section-focus': fokus === 'peserta' }">
          <h3>Peserta ({{ participants.length }})</h3>
          <p v-if="fokus === 'peserta'" class="section-hint">Untuk mulai/akhiri ujian dan kode ujian, gunakan menu <router-link to="/ujian-online/sesi?fokus=kontrol">Kontrol Ujian</router-link>.</p>
          <div class="form-group">
            <p>Tambahkan siswa dari data siswa. Pilih siswa lalu klik Tambah. Urutkan dengan seret baris atau tombol Naik/Turun; nomor peserta mengikuti urutan daftar.</p>
            <div class="button-row">
              <button type="button" class="btn-primary" @click="showAddModal = true">+ Tambah peserta</button>
              <button type="button" class="btn-secondary" :disabled="generateNumbersLoading || !participants.length" @click="generateParticipantNumbers">Generate nomor peserta</button>
              <button type="button" class="btn-secondary" :disabled="!participants.length" @click="openPrintCardsPreview">Cetak kartu peserta</button>
            </div>
          </div>
          <table class="data-table data-table-reorder">
            <thead>
              <tr>
                <th class="col-reorder"></th>
                <th class="col-no">No.</th>
                <th>Nomor peserta</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(p, idx) in participants"
                :key="p.id"
                :class="{ 'drag-over': dragOverIndex === idx, 'dragging': dragFromIndex === idx }"
                @dragover.prevent="onDragOver($event, idx)"
                @drop="onDrop($event, idx)"
              >
                <td class="col-reorder">
                  <span class="drag-handle" draggable="true" title="Seret untuk mengubah urutan" aria-hidden="true" @dragstart="onDragStart($event, idx)" @dragend="onDragEnd">⋮⋮</span>
                  <div class="move-buttons">
                    <button type="button" class="btn-move" title="Naik" :disabled="reorderLoading || idx === 0" @click.stop="moveUp(idx)">↑</button>
                    <button type="button" class="btn-move" title="Turun" :disabled="reorderLoading || idx === participants.length - 1" @click.stop="moveDown(idx)">↓</button>
                  </div>
                </td>
                <td class="col-no">{{ p.participant_order ?? idx + 1 }}</td>
                <td class="col-nomor-peserta">{{ p.nomor_peserta || '–' }}</td>
                <td>{{ p.student?.name }}</td>
                <td>{{ p.student?.nisn || '–' }}</td>
                <td>{{ participantStatusLabel(p.status) }}</td>
                <td>
                  <button type="button" class="btn-action" :disabled="p.status !== 'registered' || p.participant_order == null" @click="openEditParticipant(p)">Edit</button>
                  <button type="button" class="btn-action btn-delete" @click="removeParticipant(p)" :disabled="p.status !== 'registered'">Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
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
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
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
const showAddModal = ref(false)
const controlSectionRef = ref(null)
const pesertaSectionRef = ref(null)
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

const fokus = computed(() => route.query.fokus || '')

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
  if (session.value?.exam?.code) {
    return { path: `/ujian-online/exams/${encodeURIComponent(session.value.exam.code)}` }
  }
  return { path: '/ujian-online/exams' }
})

const backLabel = computed(() => {
  if (session.value?.exam?.name) return session.value.exam.name
  if (fokus.value === 'peserta') return 'Peserta Ujian'
  if (fokus.value === 'kontrol') return 'Kontrol Ujian'
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

function openPrintCardsPreview() {
  if (!session.value || !participants.length) return
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
  try {
    const [sRes, pRes] = await Promise.all([
      examApi.getSession(route.params.id),
      examApi.listParticipants(route.params.id)
    ])
    session.value = sRes.data?.data ?? sRes.data
    participants.value = pRes.data?.data ?? pRes.data ?? []
  } catch (e) {
    toast.error('Gagal memuat sesi ujian', e.response?.data?.message || 'Data sesi tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  } finally {
    loading.value = false
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
  const el = qFokus === 'peserta' ? pesertaSectionRef.value : qFokus === 'kontrol' ? controlSectionRef.value : null
  if (el && typeof el.scrollIntoView === 'function') el.scrollIntoView({ behavior: 'smooth', block: 'start' })
})
</script>

<style scoped>
.session-detail-page {
  padding: 1rem;
  padding-left: max(1rem, env(safe-area-inset-left));
  padding-right: max(1rem, env(safe-area-inset-right));
}
.page-header { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
.back-link { color: #059669; text-decoration: none; }
.status-badge { font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; }
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
