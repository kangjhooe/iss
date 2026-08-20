<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <div>
          <p class="sp-subtitle">Penempatan PKL / Prakerin dan jurnal kegiatan harian Anda</p>
        </div>
        <div v-if="selectedPlacement && canWriteJournal" class="sp-actions">
          <button type="button" class="sp-btn sp-btn--primary" @click="openJournalModal()">
            + Jurnal hari ini
          </button>
        </div>
      </div>

      <div v-if="loadingPlacements" class="sp-loading">
        <p>Memuat penempatan PKL...</p>
      </div>

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat data</h3>
        <p class="sp-empty-desc">Silakan coba lagi.</p>
        <div class="sp-empty-actions">
          <button type="button" class="sp-btn sp-btn--soft" @click="loadPlacements">Coba lagi</button>
        </div>
      </div>

      <div v-else-if="!placements.length" class="sp-empty">
        <h3 class="sp-empty-title">Belum ada penempatan PKL</h3>
        <p class="sp-empty-desc">Jika Anda sedang atau akan PKL, hubungi koordinator PKL di sekolah.</p>
      </div>

      <template v-else>
        <div class="sp-filters">
          <div class="sp-filter">
            <label for="placement-select">Penempatan</label>
            <select id="placement-select" v-model="selectedPlacementId" @change="onPlacementChange">
              <option v-for="p in placements" :key="p.id" :value="p.id">
                {{ p.industry_partner?.name || 'Mitra' }} — {{ p.period?.name || 'Periode' }} ({{ p.status }})
              </option>
            </select>
          </div>
        </div>

        <div v-if="selectedPlacement" class="sp-stats">
          <div class="sp-stat sp-stat--primary">
            <div class="sp-stat-body">
              <span class="sp-stat-label">Mitra</span>
              <span class="sp-stat-value sp-stat-value--sm">{{ selectedPlacement.industry_partner?.name || '—' }}</span>
            </div>
          </div>
          <div class="sp-stat">
            <div class="sp-stat-body">
              <span class="sp-stat-label">Status</span>
              <span class="sp-stat-value sp-stat-value--sm">{{ statusLabel(selectedPlacement.status) }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--ok">
            <div class="sp-stat-body">
              <span class="sp-stat-label">Jurnal</span>
              <span class="sp-stat-value">{{ selectedPlacement.journals_count ?? journals.length }}</span>
            </div>
          </div>
          <div class="sp-stat">
            <div class="sp-stat-body">
              <span class="sp-stat-label">Nilai</span>
              <span class="sp-stat-value">{{ selectedPlacement.score != null ? selectedPlacement.score : '—' }}</span>
            </div>
          </div>
        </div>

        <section class="sp-panel placement-detail">
          <div class="sp-panel-header">
            <h2 class="sp-panel-title">Detail penempatan</h2>
          </div>
          <div class="detail-grid">
            <div><span>Periode</span><strong>{{ selectedPlacement.period?.name || '—' }}</strong></div>
            <div><span>Pembimbing sekolah</span><strong>{{ selectedPlacement.supervisor?.name || '—' }}</strong></div>
            <div><span>Pembimbing industri</span><strong>{{ selectedPlacement.industry_supervisor_name || '—' }}</strong></div>
            <div><span>Kota mitra</span><strong>{{ selectedPlacement.industry_partner?.city || '—' }}</strong></div>
            <div><span>Mulai</span><strong>{{ formatDate(selectedPlacement.start_date) }}</strong></div>
            <div><span>Selesai</span><strong>{{ formatDate(selectedPlacement.end_date) }}</strong></div>
          </div>
          <p v-if="selectedPlacement.assessment_notes" class="assessment-notes">
            <strong>Catatan penilaian:</strong> {{ selectedPlacement.assessment_notes }}
          </p>
        </section>

        <div class="sp-filters">
          <div class="sp-filter">
            <label for="pkl-tab">Tampilan</label>
            <select id="pkl-tab" v-model="activeTab">
              <option value="journals">Jurnal harian</option>
              <option value="monitoring">Kunjungan pembimbing</option>
            </select>
          </div>
        </div>

        <template v-if="activeTab === 'journals'">
          <div v-if="loadingJournals" class="sp-loading"><p>Memuat jurnal...</p></div>
          <div v-else-if="!journals.length" class="sp-empty">
            <h3 class="sp-empty-title">Belum ada jurnal</h3>
            <p class="sp-empty-desc">Catat kegiatan harian Anda selama PKL di sini.</p>
            <div v-if="canWriteJournal" class="sp-empty-actions">
              <button type="button" class="sp-btn sp-btn--primary" @click="openJournalModal()">Tulis jurnal</button>
            </div>
          </div>
          <div v-else class="sp-panel">
            <div class="sp-table-wrap sp-table-desktop">
              <table class="sp-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Kegiatan</th>
                    <th>Status</th>
                    <th>Catatan pembimbing</th>
                    <th v-if="canWriteJournal">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in journals" :key="row.id">
                    <td>{{ formatDate(row.journal_date) }}</td>
                    <td>{{ row.hours != null ? row.hours + ' jam' : '—' }}</td>
                    <td class="activities-cell">{{ row.activities }}</td>
                    <td>
                      <span class="sp-badge" :class="row.status === 'draft' ? 'sp-badge--izin' : 'sp-badge--hadir'">
                        {{ row.status === 'draft' ? 'Draft' : 'Terkirim' }}
                      </span>
                    </td>
                    <td>{{ row.supervisor_notes || '—' }}</td>
                    <td v-if="canWriteJournal">
                      <TableAction kind="edit" @click="openJournalModal(row)" />
                      <TableAction kind="delete" @click="removeJournal(row)" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="sp-mobile-cards">
              <article v-for="row in journals" :key="'mj-' + row.id" class="sp-mobile-card">
                <div class="sp-mobile-card-title">{{ formatDate(row.journal_date) }}</div>
                <div class="sp-mobile-card-row"><span>Jam</span><strong>{{ row.hours != null ? row.hours + ' jam' : '—' }}</strong></div>
                <p class="mobile-activities">{{ row.activities }}</p>
                <div v-if="row.supervisor_notes" class="sp-muted">Pembimbing: {{ row.supervisor_notes }}</div>
                <div v-if="canWriteJournal" class="sp-mobile-card-actions">
                  <TableAction kind="edit" @click="openJournalModal(row)" />
                  <TableAction kind="delete" @click="removeJournal(row)" />
                </div>
              </article>
            </div>
          </div>
        </template>

        <template v-else>
          <div v-if="!monitoringLogs.length" class="sp-empty">
            <h3 class="sp-empty-title">Belum ada kunjungan</h3>
            <p class="sp-empty-desc">Catatan kunjungan/monitoring dari pembimbing sekolah akan muncul di sini.</p>
          </div>
          <div v-else class="sp-panel">
            <ul class="monitor-list">
              <li v-for="log in monitoringLogs" :key="log.id">
                <div>
                  <strong>{{ formatDate(log.visit_date) }}</strong>
                  · {{ methodLabel(log.method) }}
                  <span v-if="log.logged_by"> · {{ log.logged_by.name }}</span>
                  <p>{{ log.notes || '—' }}</p>
                </div>
              </li>
            </ul>
          </div>
        </template>
      </template>

      <div v-if="showJournalModal" class="sp-modal-overlay" @click.self="showJournalModal = false">
        <div class="sp-modal">
          <h2>{{ journalForm.id ? 'Edit jurnal' : 'Jurnal harian' }}</h2>
          <form @submit.prevent="saveJournal">
            <label for="journal_date">Tanggal *</label>
            <input id="journal_date" v-model="journalForm.journal_date" type="date" required />
            <label for="hours">Jam kegiatan</label>
            <input id="hours" v-model.number="journalForm.hours" type="number" min="0" max="24" step="0.5" placeholder="contoh: 8" />
            <label for="activities">Kegiatan *</label>
            <textarea id="activities" v-model="journalForm.activities" rows="5" required placeholder="Uraikan kegiatan yang Anda lakukan hari ini..." />
            <label for="status">Status</label>
            <select id="status" v-model="journalForm.status">
              <option value="submitted">Terkirim</option>
              <option value="draft">Draft</option>
            </select>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="sp-modal-actions">
              <button type="button" class="sp-btn sp-btn--soft" @click="showJournalModal = false">Batal</button>
              <button type="submit" class="sp-btn sp-btn--primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { pklApi } from '@/api/pkl'

const authStore = useAuthStore()
const toast = useToast()

const studentId = computed(() => authStore.user?.student_profile?.id)

const loadingPlacements = ref(false)
const loadingJournals = ref(false)
const loadError = ref(false)
const placements = ref([])
const selectedPlacementId = ref(null)
const selectedPlacement = ref(null)
const journals = ref([])
const monitoringLogs = ref([])
const activeTab = ref('journals')
const showJournalModal = ref(false)
const saving = ref(false)
const formError = ref('')

const journalForm = reactive({
  id: null,
  journal_date: '',
  activities: '',
  hours: null,
  status: 'submitted',
})

const canWriteJournal = computed(() => {
  const s = selectedPlacement.value?.status
  return s === 'berlangsung' || s === 'draft'
})

function formatDate(val) {
  if (!val) return '—'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return String(val).slice(0, 10)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusLabel(status) {
  const map = { draft: 'Draft', berlangsung: 'Berlangsung', selesai: 'Selesai', batal: 'Batal' }
  return map[status] || status || '—'
}

function methodLabel(method) {
  const map = { kunjungan: 'Kunjungan', telepon: 'Telepon', online: 'Online' }
  return map[method] || method || '—'
}

async function loadPlacements() {
  if (!studentId.value) {
    loadingPlacements.value = false
    return
  }
  loadingPlacements.value = true
  loadError.value = false
  try {
    const res = await pklApi.myPlacements()
    placements.value = res.data?.data || []
    if (placements.value.length) {
      const keep = placements.value.find((p) => p.id === selectedPlacementId.value)
      selectedPlacementId.value = keep ? keep.id : placements.value[0].id
      await loadPlacementDetail()
    } else {
      selectedPlacementId.value = null
      selectedPlacement.value = null
      journals.value = []
      monitoringLogs.value = []
    }
  } catch {
    loadError.value = true
    placements.value = []
  } finally {
    loadingPlacements.value = false
  }
}

async function loadPlacementDetail() {
  if (!selectedPlacementId.value) return
  loadingJournals.value = true
  try {
    const [detailRes, journalRes] = await Promise.all([
      pklApi.myPlacement(selectedPlacementId.value),
      pklApi.myJournals(selectedPlacementId.value),
    ])
    selectedPlacement.value = detailRes.data?.data || null
    monitoringLogs.value = detailRes.data?.monitoring_logs || []
    journals.value = journalRes.data?.data || []
    if (selectedPlacement.value) {
      const idx = placements.value.findIndex((p) => p.id === selectedPlacement.value.id)
      if (idx >= 0) {
        placements.value[idx] = {
          ...placements.value[idx],
          journals_count: selectedPlacement.value.journals_count,
        }
      }
    }
  } catch {
    toast.error('Gagal', 'Tidak dapat memuat detail penempatan.')
    selectedPlacement.value = placements.value.find((p) => p.id === selectedPlacementId.value) || null
    journals.value = []
    monitoringLogs.value = []
  } finally {
    loadingJournals.value = false
  }
}

async function onPlacementChange() {
  await loadPlacementDetail()
}

function openJournalModal(row = null) {
  formError.value = ''
  if (row) {
    journalForm.id = row.id
    journalForm.journal_date = String(row.journal_date).slice(0, 10)
    journalForm.activities = row.activities || ''
    journalForm.hours = row.hours != null ? Number(row.hours) : null
    journalForm.status = row.status || 'submitted'
  } else {
    journalForm.id = null
    journalForm.journal_date = new Date().toISOString().slice(0, 10)
    journalForm.activities = ''
    journalForm.hours = null
    journalForm.status = 'submitted'
  }
  showJournalModal.value = true
}

async function saveJournal() {
  if (!selectedPlacementId.value) return
  saving.value = true
  formError.value = ''
  const payload = {
    journal_date: journalForm.journal_date,
    activities: journalForm.activities,
    hours: journalForm.hours === '' || journalForm.hours == null ? null : journalForm.hours,
    status: journalForm.status,
  }
  try {
    if (journalForm.id) {
      await pklApi.updateMyJournal(selectedPlacementId.value, journalForm.id, payload)
      toast.success('Berhasil', 'Jurnal diperbarui.')
    } else {
      await pklApi.createMyJournal(selectedPlacementId.value, payload)
      toast.success('Berhasil', 'Jurnal disimpan.')
    }
    showJournalModal.value = false
    await loadPlacementDetail()
  } catch (e) {
    formError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan jurnal.'
  } finally {
    saving.value = false
  }
}

async function removeJournal(row) {
  if (!confirm(`Hapus jurnal ${formatDate(row.journal_date)}?`)) return
  try {
    await pklApi.deleteMyJournal(selectedPlacementId.value, row.id)
    toast.success('Berhasil', 'Jurnal dihapus.')
    await loadPlacementDetail()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Jurnal tidak dapat dihapus.')
  }
}

onMounted(loadPlacements)
</script>

<style scoped>
.sp-stat-value--sm {
  font-size: 1rem;
  line-height: 1.3;
}
.placement-detail {
  margin-bottom: 1rem;
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.75rem 1rem;
  padding: 0 0.25rem 0.5rem;
}
.detail-grid span {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.15rem;
}
.detail-grid strong {
  font-size: 0.9rem;
}
.assessment-notes {
  margin: 0.75rem 0.25rem 0;
  font-size: 0.875rem;
  color: #374151;
}
.activities-cell {
  max-width: 360px;
  white-space: pre-wrap;
}
.mobile-activities {
  margin: 0.5rem 0;
  font-size: 0.875rem;
  white-space: pre-wrap;
}
.monitor-list {
  list-style: none;
  margin: 0;
  padding: 0.5rem 0.75rem;
}
.monitor-list li {
  padding: 0.75rem 0;
  border-bottom: 1px solid #e5e7eb;
}
.monitor-list li:last-child {
  border-bottom: none;
}
.monitor-list p {
  margin: 0.35rem 0 0;
  color: #374151;
  font-size: 0.9rem;
}
.sp-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 60;
  padding: 1rem;
}
.sp-modal {
  background: #fff;
  border-radius: 12px;
  padding: 1.25rem;
  width: min(520px, 100%);
  max-height: 90vh;
  overflow: auto;
}
.sp-modal h2 {
  margin: 0 0 0.75rem;
  font-size: 1.1rem;
}
.sp-modal label {
  display: block;
  margin: 0.75rem 0 0.25rem;
  font-size: 0.85rem;
  font-weight: 600;
}
.sp-modal input,
.sp-modal select,
.sp-modal textarea {
  width: 100%;
  padding: 0.5rem 0.65rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  box-sizing: border-box;
  font-size: 16px;
}
.sp-modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}
.form-error {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>
