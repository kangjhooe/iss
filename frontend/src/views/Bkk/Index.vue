<template>
  <Layout>
    <div class="bkk-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">
              BKK / Bursa Kerja
            </h1>
            <p class="page-subtitle">Lowongan dan penyaluran alumni ke dunia kerja</p>
          </div>
          <div class="header-actions">
            <button
              v-if="tab === 'applications'"
              type="button"
              class="btn-secondary btn-header"
              @click="exportApplications"
              :disabled="exporting"
            >
              {{ exporting ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-primary btn-header" @click="openCreate">
              {{ tab === 'vacancies' ? 'Tambah Lowongan' : 'Daftarkan Alumni' }}
            </button>
          </div>
        </div>
      </header>

      <div class="tabs">
        <button type="button" :class="{ active: tab === 'vacancies' }" @click="tab = 'vacancies'; loadVacancies()">Lowongan</button>
        <button type="button" :class="{ active: tab === 'applications' }" @click="tab = 'applications'; loadApplications()">Lamaran</button>
      </div>

      <template v-if="tab === 'vacancies'">
        <div class="toolbar">
          <input v-model="vacancyFilters.search" class="search-input" placeholder="Cari lowongan..." @input="debounceVacancies" />
          <select v-model="vacancyFilters.status" class="filter-select" @change="loadVacancies">
            <option value="">Semua Status</option>
            <option value="buka">Buka</option>
            <option value="tutup">Tutup</option>
          </select>
        </div>
        <div v-if="loadingVacancies" class="loading-wrap"><p>Memuat lowongan...</p></div>
        <div v-else-if="!vacancies.length" class="empty-state">
          <h3>Belum ada lowongan</h3>
          <button type="button" class="btn-primary" @click="openVacancyModal()">Tambah Lowongan</button>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Perusahaan</th>
              <th>Posisi</th>
              <th>Kuota</th>
              <th>Deadline</th>
              <th>Lamaran</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in vacancies" :key="v.id">
              <td><strong>{{ v.title }}</strong></td>
              <td>{{ v.industry_partner?.name || v.company_name || '—' }}</td>
              <td>{{ v.position || '—' }}</td>
              <td>{{ v.quota ?? '—' }}</td>
              <td>{{ formatDate(v.deadline) }}</td>
              <td>{{ v.applications_count ?? 0 }}</td>
              <td><span class="status-chip" :class="v.status">{{ v.status }}</span></td>
              <td>
                <TableAction kind="edit" @click="openVacancyModal(v)" />
                <button type="button" class="btn-link" @click="tab = 'applications'; applicationFilters.bkk_vacancy_id = String(v.id); loadApplications()">Lamaran</button>
                <TableAction kind="delete" @click="removeVacancy(v)" />
              </td>
            </tr>
          </tbody>
        </table>
      </template>

      <template v-else>
        <div class="toolbar">
          <select v-model="applicationFilters.bkk_vacancy_id" class="filter-select" @change="loadApplications">
            <option value="">Semua Lowongan</option>
            <option v-for="v in vacancyOptions" :key="v.id" :value="String(v.id)">{{ v.title }}</option>
          </select>
          <input v-model="applicationFilters.search" class="search-input" placeholder="Cari alumni..." @input="debounceApplications" />
          <select v-model="applicationFilters.status" class="filter-select" @change="loadApplications">
            <option value="">Semua Status</option>
            <option value="diajukan">Diajukan</option>
            <option value="seleksi">Seleksi</option>
            <option value="diterima">Diterima</option>
            <option value="ditolak">Ditolak</option>
          </select>
        </div>
        <div v-if="loadingApplications" class="loading-wrap"><p>Memuat lamaran...</p></div>
        <div v-else-if="!applications.length" class="empty-state">
          <h3>Belum ada lamaran</h3>
          <button type="button" class="btn-primary" @click="openApplicationModal()">Daftarkan Alumni</button>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Alumni</th>
              <th>Lowongan</th>
              <th>Perusahaan</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in applications" :key="a.id">
              <td>
                <strong>{{ a.student?.name }}</strong>
                <div class="cell-sub">Lulus {{ a.student?.graduation_year || '—' }}</div>
              </td>
              <td>{{ a.vacancy?.title || '—' }}</td>
              <td>{{ a.vacancy?.industry_partner?.name || a.vacancy?.company_name || '—' }}</td>
              <td>{{ formatDate(a.applied_at) }}</td>
              <td>
                <select class="status-select" :value="a.status" @change="changeStatus(a, $event.target.value)">
                  <option value="diajukan">Diajukan</option>
                  <option value="seleksi">Seleksi</option>
                  <option value="diterima">Diterima</option>
                  <option value="ditolak">Ditolak</option>
                </select>
              </td>
              <td>
                <TableAction kind="delete" @click="removeApplication(a)" />
              </td>
            </tr>
          </tbody>
        </table>
      </template>

      <!-- Vacancy modal -->
      <div v-if="showVacancyModal" class="modal-overlay" @click.self="showVacancyModal = false">
        <div class="modal-card">
          <h2>{{ vacancyForm.id ? 'Edit Lowongan' : 'Tambah Lowongan' }}</h2>
          <form @submit.prevent="saveVacancy">
            <label>Judul *</label>
            <input v-model="vacancyForm.title" required />
            <label>Mitra DU/DI</label>
            <select v-model="vacancyForm.industry_partner_id">
              <option value="">— (isi nama perusahaan manual)</option>
              <option v-for="m in partners" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <label>Nama perusahaan (jika tanpa mitra)</label>
            <input v-model="vacancyForm.company_name" />
            <label>Posisi</label>
            <input v-model="vacancyForm.position" />
            <div class="form-row">
              <div><label>Kuota</label><input v-model.number="vacancyForm.quota" type="number" min="1" /></div>
              <div><label>Deadline</label><input v-model="vacancyForm.deadline" type="date" /></div>
            </div>
            <label>Status</label>
            <select v-model="vacancyForm.status">
              <option value="buka">Buka</option>
              <option value="tutup">Tutup</option>
            </select>
            <label>Deskripsi</label>
            <textarea v-model="vacancyForm.description" rows="2"></textarea>
            <label>Persyaratan</label>
            <textarea v-model="vacancyForm.requirements" rows="2"></textarea>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showVacancyModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Application modal -->
      <div v-if="showApplicationModal" class="modal-overlay" @click.self="showApplicationModal = false">
        <div class="modal-card">
          <h2>Daftarkan Alumni</h2>
          <form @submit.prevent="saveApplication">
            <label>Lowongan *</label>
            <select v-model="applicationForm.bkk_vacancy_id" required>
              <option value="">Pilih lowongan</option>
              <option v-for="v in vacancyOptions" :key="v.id" :value="v.id">{{ v.title }}</option>
            </select>
            <label>Cari alumni *</label>
            <input v-model="alumniSearch" placeholder="Ketik nama/NIS..." @input="searchAlumni" />
            <select v-model="applicationForm.student_id" required>
              <option value="">Pilih alumni</option>
              <option v-for="s in alumniOptions" :key="s.id" :value="s.id">
                {{ s.name }} — Lulus {{ s.graduation_year || '?' }}
              </option>
            </select>
            <label>Status awal</label>
            <select v-model="applicationForm.status">
              <option value="diajukan">Diajukan</option>
              <option value="seleksi">Seleksi</option>
              <option value="diterima">Diterima</option>
            </select>
            <label>Catatan</label>
            <textarea v-model="applicationForm.notes" rows="2"></textarea>
            <p class="hint">Jika status <strong>Diterima</strong>, destinasi alumni tipe Kerja akan diisi otomatis.</p>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showApplicationModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { bkkApi } from '@/api/bkk'
import { industryPartnersApi } from '@/api/industryPartners'
import { alumniApi } from '@/api/alumni'
import '@/assets/module-page.css'

const tab = ref('vacancies')
const vacancies = ref([])
const vacancyOptions = ref([])
const applications = ref([])
const partners = ref([])
const alumniOptions = ref([])
const loadingVacancies = ref(false)
const loadingApplications = ref(false)
const saving = ref(false)
const exporting = ref(false)
const formError = ref('')
const showVacancyModal = ref(false)
const showApplicationModal = ref(false)
const alumniSearch = ref('')

const vacancyFilters = reactive({ search: '', status: '' })
const applicationFilters = reactive({ search: '', status: '', bkk_vacancy_id: '' })
const vacancyForm = reactive({
  id: null, title: '', industry_partner_id: '', company_name: '', position: '',
  quota: null, deadline: '', status: 'buka', description: '', requirements: '',
})
const applicationForm = reactive({
  bkk_vacancy_id: '', student_id: '', status: 'diajukan', notes: '',
})

function formatDate(v) {
  if (!v) return '—'
  const d = String(v).slice(0, 10)
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

function openCreate() {
  if (tab.value === 'vacancies') openVacancyModal()
  else openApplicationModal()
}

function downloadBlob(blob, filename) {
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = filename
  link.click()
  URL.revokeObjectURL(link.href)
}

async function exportApplications() {
  exporting.value = true
  try {
    const res = await bkkApi.exportApplications({
      search: applicationFilters.search || undefined,
      status: applicationFilters.status || undefined,
      bkk_vacancy_id: applicationFilters.bkk_vacancy_id || undefined,
    })
    downloadBlob(res.data, `bkk-lamaran-${new Date().toISOString().slice(0, 10)}.csv`)
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengekspor lamaran.')
  } finally {
    exporting.value = false
  }
}

let t1, t2, t3
function debounceVacancies() { clearTimeout(t1); t1 = setTimeout(loadVacancies, 300) }
function debounceApplications() { clearTimeout(t2); t2 = setTimeout(loadApplications, 300) }
function searchAlumni() { clearTimeout(t3); t3 = setTimeout(loadAlumniOptions, 300) }

async function loadVacancies() {
  loadingVacancies.value = true
  try {
    const res = await bkkApi.getVacancies({
      search: vacancyFilters.search || undefined,
      status: vacancyFilters.status || undefined,
      per_page: 50,
    })
    vacancies.value = res.data?.data || []
  } catch { vacancies.value = [] }
  finally { loadingVacancies.value = false }
}

async function loadVacancyOptions() {
  try {
    const res = await bkkApi.getVacancies({ per_page: 100 })
    vacancyOptions.value = res.data?.data || []
  } catch { vacancyOptions.value = [] }
}

async function loadApplications() {
  loadingApplications.value = true
  try {
    const res = await bkkApi.getApplications({
      search: applicationFilters.search || undefined,
      status: applicationFilters.status || undefined,
      bkk_vacancy_id: applicationFilters.bkk_vacancy_id || undefined,
      per_page: 50,
    })
    applications.value = res.data?.data || []
  } catch { applications.value = [] }
  finally { loadingApplications.value = false }
}

async function loadPartners() {
  try {
    const res = await industryPartnersApi.getAll({ status: 'Aktif', per_page: 100 })
    partners.value = res.data?.data || []
  } catch { partners.value = [] }
}

async function loadAlumniOptions() {
  try {
    const res = await alumniApi.getList({ search: alumniSearch.value || undefined, per_page: 20 })
    alumniOptions.value = res.data?.data || []
  } catch { alumniOptions.value = [] }
}

function openVacancyModal(item = null) {
  formError.value = ''
  Object.assign(vacancyForm, {
    id: item?.id || null,
    title: item?.title || '',
    industry_partner_id: item?.industry_partner_id || '',
    company_name: item?.company_name || '',
    position: item?.position || '',
    quota: item?.quota ?? null,
    deadline: item?.deadline ? String(item.deadline).slice(0, 10) : '',
    status: item?.status || 'buka',
    description: item?.description || '',
    requirements: item?.requirements || '',
  })
  showVacancyModal.value = true
}

async function saveVacancy() {
  saving.value = true
  formError.value = ''
  const payload = {
    ...vacancyForm,
    industry_partner_id: vacancyForm.industry_partner_id || null,
    quota: vacancyForm.quota || null,
  }
  delete payload.id
  try {
    if (vacancyForm.id) await bkkApi.updateVacancy(vacancyForm.id, payload)
    else await bkkApi.createVacancy(payload)
    showVacancyModal.value = false
    await Promise.all([loadVacancies(), loadVacancyOptions()])
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal menyimpan lowongan.'
  } finally { saving.value = false }
}

async function removeVacancy(v) {
  if (!confirm(`Hapus lowongan "${v.title}"?`)) return
  try {
    await bkkApi.deleteVacancy(v.id)
    await Promise.all([loadVacancies(), loadVacancyOptions()])
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus.')
  }
}

function openApplicationModal() {
  formError.value = ''
  Object.assign(applicationForm, {
    bkk_vacancy_id: applicationFilters.bkk_vacancy_id || '',
    student_id: '',
    status: 'diajukan',
    notes: '',
  })
  loadAlumniOptions()
  showApplicationModal.value = true
}

async function saveApplication() {
  saving.value = true
  formError.value = ''
  try {
    await bkkApi.createApplication({ ...applicationForm })
    showApplicationModal.value = false
    tab.value = 'applications'
    await loadApplications()
  } catch (e) {
    formError.value = e.response?.data?.message || 'Gagal mendaftarkan alumni.'
  } finally { saving.value = false }
}

async function changeStatus(app, status) {
  try {
    await bkkApi.updateApplication(app.id, { status })
    app.status = status
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengubah status.')
    await loadApplications()
  }
}

async function removeApplication(a) {
  if (!confirm(`Hapus lamaran ${a.student?.name}?`)) return
  try {
    await bkkApi.deleteApplication(a.id)
    await loadApplications()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus.')
  }
}

onMounted(async () => {
  await Promise.all([loadVacancies(), loadVacancyOptions(), loadPartners()])
})
</script>

<style scoped>
.beta-badge {
  display: inline-block; margin-left: 0.4rem; padding: 0.1rem 0.45rem;
  font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
  vertical-align: middle; color: #92400e; background: #fef3c7; border-radius: 999px;
}
.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

@media (max-width: 1024px) {
  .header-content { flex-direction: column; align-items: stretch; }
  .header-actions { width: 100%; }
  .header-actions .btn-primary,
  .header-actions .btn-secondary { flex: 1; justify-content: center; }
}
.tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.tabs button {
  border: 1px solid #d1d5db; background: #fff; padding: 0.45rem 0.9rem; border-radius: 999px; cursor: pointer;
}
.tabs button.active { background: #1e3a5f; color: #fff; border-color: #1e3a5f; }
.toolbar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input, .filter-select, .status-select {
  padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px;
}
.search-input { min-width: 200px; flex: 1; }
.status-select { text-transform: capitalize; }
.cell-sub { font-size: 0.8rem; color: #6b7280; }
.status-chip { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; background: #e5e7eb; text-transform: capitalize; }
.status-chip.buka { background: #d1fae5; color: #065f46; }
.status-chip.tutup { background: #f3f4f6; color: #4b5563; }
.btn-link { background: none; border: none; color: #2563eb; cursor: pointer; margin-right: 0.4rem; }
.btn-link.danger { color: #dc2626; }
.empty-state { text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 12px; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
}
.modal-card {
  background: #fff; border-radius: 12px; padding: 1.25rem; width: min(540px, 100%); max-height: 90vh; overflow: auto;
}
.modal-card label { display: block; margin: 0.75rem 0 0.25rem; font-size: 0.85rem; font-weight: 600; }
.modal-card input, .modal-card select, .modal-card textarea {
  width: 100%; padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-error { color: #dc2626; font-size: 0.875rem; }
.hint { font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem; }
@media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
</style>
