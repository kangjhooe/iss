<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Konfigurasi PPDB</h1>
              <p class="page-subtitle">Atur periode (gelombang) dan jalur pendaftaran</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" @click="openPeriodModal()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Periode</span>
            </button>
            <button type="button" class="btn-header-primary" @click="openChannelModal()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Jalur</span>
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <section class="content-card label-card">
          <h2 class="panel-title">Nama program di halaman publik</h2>
          <p class="panel-hint">Tampil di website sekolah (nav, hero, form daftar). Modul di admin tetap disebut PPDB.</p>
          <div class="label-form">
            <div class="label-presets">
              <button
                v-for="preset in admissionPresets"
                :key="preset"
                type="button"
                class="preset-btn"
                :class="{ active: admissionLabelDraft === preset }"
                @click="admissionLabelDraft = preset"
              >{{ preset }}</button>
            </div>
            <div class="label-row">
              <input
                v-model="admissionLabelDraft"
                type="text"
                maxlength="50"
                class="label-input"
                placeholder="PPDB / SPMB / kustom"
              />
              <button type="button" class="btn-primary btn-compact" :disabled="savingLabel" @click="saveAdmissionLabel">
                {{ savingLabel ? 'Menyimpan...' : 'Simpan label' }}
              </button>
            </div>
            <p class="label-preview">Pratinjau publik: <strong>Daftar {{ admissionLabelDraft || 'PPDB' }}</strong></p>
          </div>
        </section>

        <div class="config-grid">
        <section class="content-card config-panel">
          <h2 class="panel-title">Periode</h2>
          <p class="panel-hint">Gelombang & jadwal pendaftaran</p>
          <div v-if="periodsLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="5" /></div>
          <div v-else-if="periods.length === 0" class="empty-state empty-state-sm">
            <h3>Belum ada periode</h3>
            <p>Buat periode (gelombang) terlebih dahulu.</p>
            <button type="button" class="btn-primary" @click="openPeriodModal()">Tambah Periode</button>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Tahun Ajaran</th>
                  <th>Buka</th>
                  <th>Tutup</th>
                  <th>Status</th>
                  <th>Calon</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in periods" :key="p.id">
                  <td><strong>{{ p.name }}</strong></td>
                  <td>{{ p.academic_year?.code || p.academic_year?.name || '-' }}</td>
                  <td>{{ formatDate(p.open_date) }}</td>
                  <td>{{ formatDate(p.close_date) }}</td>
                  <td><span :class="['status-badge', 'status-' + p.status]">{{ statusPeriodLabel(p.status) }}</span></td>
                  <td>{{ p.applicants_count ?? 0 }}</td>
                  <td>
                    <TableAction kind="edit" @click="openPeriodModal(p)" />
                    <TableAction kind="delete" @click="confirmDeletePeriod(p)" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="content-card config-panel">
          <h2 class="panel-title">Jalur</h2>
          <p class="panel-hint">Zonasi, afirmasi, prestasi, dll</p>
          <div v-if="channelsLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="4" /></div>
          <div v-else-if="channels.length === 0" class="empty-state empty-state-sm">
            <h3>Belum ada jalur</h3>
            <p>Tambahkan jalur seperti Zonasi atau Prestasi.</p>
            <button type="button" class="btn-primary" @click="openChannelModal()">Tambah Jalur</button>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Kuota</th>
                  <th>Berkas</th>
                  <th>Aktif</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in channels" :key="c.id">
                  <td><code>{{ c.code }}</code></td>
                  <td>{{ c.name }}</td>
                  <td>{{ c.quota ?? '-' }}</td>
                  <td>{{ (c.required_documents || []).length || '—' }}</td>
                  <td>{{ c.is_active ? 'Ya' : 'Tidak' }}</td>
                  <td>
                    <TableAction kind="edit" @click="openChannelModal(c)" />
                    <TableAction kind="delete" @click="confirmDeleteChannel(c)" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
        </div>
      </main>

      <div v-if="showPeriodModal" class="modal-overlay" @click="showPeriodModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingPeriod ? 'Edit Periode PPDB' : 'Tambah Periode PPDB' }}</h3>
            <button type="button" class="btn-close" @click="showPeriodModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitPeriod">
            <div class="form-group">
              <label>Tahun Ajaran *</label>
              <select v-model="periodForm.academic_year_id" required class="form-select">
                <option value="">Pilih tahun ajaran</option>
                <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.code }}{{ y.name ? ' - ' + y.name : '' }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Periode *</label>
              <input v-model="periodForm.name" type="text" required placeholder="Contoh: Gelombang 1" />
            </div>
            <div class="form-group">
              <label>Jenjang (opsional)</label>
              <input v-model="periodForm.level" type="text" placeholder="SD, SMP, SMA" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Buka *</label>
                <input v-model="periodForm.open_date" type="date" required />
              </div>
              <div class="form-group">
                <label>Tanggal Tutup *</label>
                <input v-model="periodForm.close_date" type="date" required />
              </div>
            </div>
            <div class="form-group">
              <label>Batas Daftar Ulang (opsional)</label>
              <input v-model="periodForm.re_registration_deadline" type="date" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="periodForm.status" class="form-select">
                <option value="draft">Draft</option>
                <option value="open">Dibuka</option>
                <option value="closed">Ditutup</option>
                <option value="finished">Selesai</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Biaya pendaftaran</label>
                <input v-model.number="periodForm.registration_fee" type="number" min="0" step="1000" placeholder="Opsional" />
              </div>
              <div class="form-group">
                <label>Biaya daftar ulang</label>
                <input v-model.number="periodForm.re_registration_fee" type="number" min="0" step="1000" placeholder="Opsional" />
              </div>
            </div>
            <div class="form-group">
              <label>Keterangan</label>
              <textarea v-model="periodForm.description" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="periodFormError" class="error-message">{{ periodFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showPeriodModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="periodFormSubmitting">{{ periodFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showChannelModal" class="modal-overlay" @click="showChannelModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ editingChannel ? 'Edit Jalur' : 'Tambah Jalur Pendaftaran' }}</h3>
            <button type="button" class="btn-close" @click="showChannelModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitChannel">
            <div class="form-group">
              <label>Kode *</label>
              <input v-model="channelForm.code" type="text" required placeholder="zonasi, afirmasi, prestasi" />
            </div>
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="channelForm.name" type="text" required placeholder="Jalur Zonasi" />
            </div>
            <div class="form-group">
              <label>Kuota (opsional)</label>
              <input v-model.number="channelForm.quota" type="number" min="0" placeholder="0" />
            </div>
            <div class="form-group">
              <label>Persyaratan (opsional)</label>
              <textarea v-model="channelForm.requirements" rows="2" placeholder="Teks persyaratan"></textarea>
            </div>
            <div class="form-group">
              <label>Berkas yang harus diunggah</label>
              <p class="field-hint">Centang jenis berkas untuk jalur ini. Kosongkan semua jika tidak memakai daftar.</p>
              <div class="doc-preset-list">
                <div v-for="doc in channelForm.docPresets" :key="doc.key" class="doc-preset-row">
                  <label class="doc-preset-label">
                    <input v-model="doc.enabled" type="checkbox" />
                    {{ doc.label }}
                  </label>
                  <label v-if="doc.enabled" class="doc-required-label">
                    <input v-model="doc.required" type="checkbox" />
                    Wajib
                  </label>
                </div>
              </div>
              <div v-for="(doc, i) in channelForm.customDocuments" :key="'custom-' + i" class="doc-custom-row">
                <input v-model="doc.label" type="text" maxlength="80" placeholder="Nama berkas lain" />
                <label class="doc-required-label">
                  <input v-model="doc.required" type="checkbox" />
                  Wajib
                </label>
                <button type="button" class="btn-link-danger" @click="channelForm.customDocuments.splice(i, 1)">Hapus</button>
              </div>
              <button type="button" class="btn-link" @click="addCustomDocument">+ Berkas lain</button>
            </div>
            <div class="form-group">
              <label><input v-model="channelForm.is_active" type="checkbox" /> Aktif</label>
            </div>
            <div v-if="channelFormError" class="error-message">{{ channelFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showChannelModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="channelFormSubmitting">{{ channelFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog v-if="deletePeriodTarget" :show="!!deletePeriodTarget" title="Hapus Periode" message="Yakin menghapus periode ini? Periode yang sudah memiliki calon tidak dapat dihapus." confirmText="Hapus" @confirm="doDeletePeriod" @cancel="deletePeriodTarget = null" />
      <ConfirmDialog v-if="deleteChannelTarget" :show="!!deleteChannelTarget" title="Hapus Jalur" message="Yakin menghapus jalur ini? Jalur yang sudah dipakai calon tidak dapat dihapus." confirmText="Hapus" @confirm="doDeleteChannel" @cancel="deleteChannelTarget = null" />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { ppdbPeriodApi, ppdbChannelApi } from '@/api/ppdb'
import { institutionApi } from '@/api/institution'
import { useReferenceDataStore } from '@/stores/referenceData'
import { useToast } from '@/composables/useToast'
import { statusPeriodLabel, formatDate, PPDB_DOCUMENT_PRESETS } from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)

const admissionPresets = ['PPDB', 'SPMB', 'PSB', 'PMB']
const admissionLabelDraft = ref('PPDB')
const institutionId = ref(null)
const savingLabel = ref(false)

const periods = ref([])
const periodsLoading = ref(false)
const channels = ref([])
const channelsLoading = ref(false)

const showPeriodModal = ref(false)
const editingPeriod = ref(null)
const periodForm = ref({ academic_year_id: '', name: '', level: '', open_date: '', close_date: '', re_registration_deadline: '', status: 'draft', description: '', registration_fee: '', re_registration_fee: '' })
const periodFormError = ref('')
const periodFormSubmitting = ref(false)

const showChannelModal = ref(false)
const editingChannel = ref(null)
const channelForm = ref(emptyChannelForm())
const channelFormError = ref('')
const channelFormSubmitting = ref(false)

const deletePeriodTarget = ref(null)
const deleteChannelTarget = ref(null)

async function loadAdmissionLabel() {
  try {
    const res = await institutionApi.getMy()
    const inst = res.data?.data || res.data
    institutionId.value = inst?.id || null
    admissionLabelDraft.value = inst?.admission_label || 'PPDB'
  } catch {
    /* ignore — label opsional */
  }
}

async function saveAdmissionLabel() {
  if (!institutionId.value) {
    toast.error('Institusi tidak ditemukan')
    return
  }
  const label = (admissionLabelDraft.value || '').trim() || 'PPDB'
  savingLabel.value = true
  try {
    await institutionApi.update(institutionId.value, { admission_label: label })
    admissionLabelDraft.value = label
    toast.success('Label publik disimpan')
  } catch (e) {
    toast.error('Gagal menyimpan label', e.formattedMessage || 'Coba lagi.')
  } finally {
    savingLabel.value = false
  }
}

async function loadPeriods() {
  periodsLoading.value = true
  try {
    const res = await ppdbPeriodApi.getAll({ per_page: 100 })
    periods.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat periode PPDB', e.formattedMessage || 'Daftar periode tidak dapat dimuat.')
  } finally {
    periodsLoading.value = false
  }
}

async function loadChannels() {
  channelsLoading.value = true
  try {
    const res = await ppdbChannelApi.getAll({ active_only: false })
    channels.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jalur PPDB', e.formattedMessage || 'Daftar jalur tidak dapat dimuat.')
  } finally {
    channelsLoading.value = false
  }
}

function openPeriodModal(p = null) {
  editingPeriod.value = p
  if (p) {
    periodForm.value = {
      academic_year_id: p.academic_year_id,
      name: p.name,
      level: p.level || '',
      open_date: p.open_date || '',
      close_date: p.close_date || '',
      re_registration_deadline: p.re_registration_deadline || '',
      status: p.status || 'draft',
      description: p.description || '',
      registration_fee: p.registration_fee ?? '',
      re_registration_fee: p.re_registration_fee ?? '',
    }
  } else {
    periodForm.value = { academic_year_id: '', name: '', level: '', open_date: '', close_date: '', re_registration_deadline: '', status: 'draft', description: '', registration_fee: '', re_registration_fee: '' }
  }
  periodFormError.value = ''
  showPeriodModal.value = true
}

async function submitPeriod() {
  periodFormError.value = ''
  periodFormSubmitting.value = true
  try {
    const payload = { ...periodForm.value }
    if (!payload.re_registration_deadline) payload.re_registration_deadline = null
    if (payload.registration_fee === '') payload.registration_fee = null
    if (payload.re_registration_fee === '') payload.re_registration_fee = null
    if (editingPeriod.value) {
      await ppdbPeriodApi.update(editingPeriod.value.id, payload)
      toast.success('Periode berhasil diperbarui')
    } else {
      await ppdbPeriodApi.create(payload)
      toast.success('Periode berhasil ditambahkan')
    }
    showPeriodModal.value = false
    loadPeriods()
  } catch (e) {
    periodFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    periodFormSubmitting.value = false
  }
}

function confirmDeletePeriod(p) {
  deletePeriodTarget.value = p
}

async function doDeletePeriod() {
  if (!deletePeriodTarget.value) return
  try {
    await ppdbPeriodApi.delete(deletePeriodTarget.value.id)
    toast.success('Periode dihapus')
    deletePeriodTarget.value = null
    loadPeriods()
  } catch (e) {
    toast.error('Gagal menghapus periode', e.formattedMessage || 'Periode tidak dapat dihapus.')
  }
}

function emptyChannelForm() {
  return {
    code: '',
    name: '',
    quota: '',
    requirements: '',
    is_active: true,
    docPresets: PPDB_DOCUMENT_PRESETS.map((p) => ({ ...p, enabled: false, required: true })),
    customDocuments: [],
  }
}

function channelFormFromChannel(c) {
  const saved = Array.isArray(c?.required_documents) ? c.required_documents : []
  const byKey = Object.fromEntries(saved.map((d) => [d.key, d]))
  return {
    code: c.code,
    name: c.name,
    quota: c.quota ?? '',
    requirements: c.requirements || '',
    is_active: c.is_active ?? true,
    docPresets: PPDB_DOCUMENT_PRESETS.map((p) => ({
      ...p,
      enabled: !!byKey[p.key],
      required: byKey[p.key] ? byKey[p.key].required !== false : true,
    })),
    customDocuments: saved
      .filter((d) => !PPDB_DOCUMENT_PRESETS.some((p) => p.key === d.key))
      .map((d) => ({ key: d.key || '', label: d.label || '', required: d.required !== false })),
  }
}

function requiredDocumentsPayload() {
  const presets = (channelForm.value.docPresets || [])
    .filter((d) => d.enabled)
    .map((d) => ({ key: d.key, label: d.label, required: !!d.required }))
  const custom = (channelForm.value.customDocuments || [])
    .filter((d) => (d.label || '').trim())
    .map((d) => ({
      key: d.key || undefined,
      label: d.label.trim(),
      required: d.required !== false,
    }))
  return [...presets, ...custom]
}

function addCustomDocument() {
  channelForm.value.customDocuments.push({ key: '', label: '', required: true })
}

function openChannelModal(c = null) {
  editingChannel.value = c
  channelForm.value = c ? channelFormFromChannel(c) : emptyChannelForm()
  channelFormError.value = ''
  showChannelModal.value = true
}

async function submitChannel() {
  channelFormError.value = ''
  channelFormSubmitting.value = true
  try {
    const payload = {
      code: channelForm.value.code,
      name: channelForm.value.name,
      quota: channelForm.value.quota === '' ? null : channelForm.value.quota,
      requirements: channelForm.value.requirements,
      is_active: channelForm.value.is_active,
      required_documents: requiredDocumentsPayload(),
    }
    if (editingChannel.value) {
      await ppdbChannelApi.update(editingChannel.value.id, payload)
      toast.success('Jalur berhasil diperbarui')
    } else {
      await ppdbChannelApi.create(payload)
      toast.success('Jalur berhasil ditambahkan')
    }
    showChannelModal.value = false
    loadChannels()
  } catch (e) {
    channelFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    channelFormSubmitting.value = false
  }
}

function confirmDeleteChannel(c) {
  deleteChannelTarget.value = c
}

async function doDeleteChannel() {
  if (!deleteChannelTarget.value) return
  try {
    await ppdbChannelApi.delete(deleteChannelTarget.value.id)
    toast.success('Jalur dihapus')
    deleteChannelTarget.value = null
    loadChannels()
  } catch (e) {
    toast.error('Gagal menghapus jalur', e.formattedMessage || 'Jalur tidak dapat dihapus.')
  }
}

onMounted(() => {
  referenceStore.getAcademicYears()
  loadAdmissionLabel()
  loadPeriods()
  loadChannels()
})
</script>

<style scoped>
.config-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 1100px) {
  .config-grid { grid-template-columns: 1.2fr 0.8fr; align-items: start; }
}
.panel-title { margin: 0; font-size: 1.15rem; color: #1e293b; }
.panel-hint { margin: 0.25rem 0 1rem; font-size: 0.85rem; color: #64748b; }
.config-panel .table-wrap { margin: 0 -0.25rem; }
.label-form { display: flex; flex-direction: column; gap: 0.75rem; }
.label-presets { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.preset-btn {
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  color: #475569;
}
.preset-btn:hover { border-color: #059669; color: #047857; }
.preset-btn.active { background: #ecfdf5; border-color: #059669; color: #047857; }
.label-row { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.label-input {
  flex: 1;
  min-width: 160px;
  max-width: 280px;
  padding: 0.55rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
}
.label-preview { margin: 0; font-size: 0.9rem; color: #64748b; }
.btn-compact { padding: 0.55rem 1rem; }
.field-hint { margin: 0 0 0.6rem; font-size: 0.8rem; color: #64748b; }
.doc-preset-list { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 0.6rem; }
.doc-preset-row, .doc-custom-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.doc-preset-label, .doc-required-label { display: flex; align-items: center; gap: 0.4rem; font-weight: 500; font-size: 0.9rem; color: #334155; }
.doc-custom-row input[type="text"] {
  flex: 1;
  min-width: 140px;
  padding: 0.4rem 0.65rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.btn-link {
  background: none;
  border: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  padding: 0.25rem 0;
}
.btn-link-danger {
  background: none;
  border: none;
  color: #b91c1c;
  cursor: pointer;
  font-size: 0.85rem;
}
</style>
