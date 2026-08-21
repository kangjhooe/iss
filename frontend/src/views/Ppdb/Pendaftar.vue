<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Data Pendaftar</h1>
              <p class="page-subtitle">Calon peserta didik — verifikasi & hasil seleksi</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" @click="openApplicantModal()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span>Tambah Calon</span>
            </button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div class="filters-bar content-card">
          <div class="search-wrap">
            <input v-model="applicantFilters.search" type="text" placeholder="Cari nama, no. pendaftaran, NISN..." class="search-input" @input="debounceLoadApplicants" />
          </div>
          <select v-model="applicantFilters.ppdb_period_id" class="filter-select" @change="loadApplicants">
            <option value="">Semua Periode</option>
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <select v-model="applicantFilters.ppdb_channel_id" class="filter-select" @change="loadApplicants">
            <option value="">Semua Jalur</option>
            <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
            <select v-model="applicantFilters.status" class="filter-select" @change="onStatusFilterChange">
              <option value="">Semua Status</option>
              <option v-for="(l, k) in statusApplicantLabels" :key="k" :value="k">{{ l }}</option>
            </select>
            <select v-model="applicantFilters.payment_status" class="filter-select" @change="loadApplicants">
              <option value="">Semua Bayar</option>
              <option v-for="(l, k) in paymentStatusLabels" :key="k" :value="k">{{ l }}</option>
            </select>
            <button type="button" class="filter-quick-btn" :class="{ active: applicantFilters.needs_verification }" @click="setFilterNeedsVerification">
              Perlu verifikasi
            </button>
            <button type="button" class="filter-quick-btn" :class="{ active: applicantFilters.needs_result }" @click="setFilterNeedsResult">
              Perlu set hasil
            </button>
            <button v-if="selectedForVerify.length" type="button" class="btn-bulk-verify" :disabled="bulkVerifying" @click="doBulkVerification">
              {{ bulkVerifying ? 'Memproses...' : 'Tandai verified (' + selectedForVerify.length + ')' }}
            </button>
            <button v-if="selectedForResult.length" type="button" class="btn-bulk-verify" :disabled="bulkResulting" @click="openBulkResultModal">
              Set hasil ({{ selectedForResult.length }})
            </button>
            <button v-if="selectedForReReg.length" type="button" class="btn-bulk-verify" :disabled="bulkReReging" @click="showBulkReRegConfirm = true">
              {{ bulkReReging ? 'Memproses...' : 'Daftar ulang (' + selectedForReReg.length + ')' }}
            </button>
            <button v-if="selectedForConvert.length" type="button" class="btn-bulk-verify" :disabled="convertFormSubmitting" @click="openBulkConvertModal">
              Jadikan siswa ({{ selectedForConvert.length }})
            </button>
            <button type="button" class="btn-export" :disabled="exportingApplicants" @click="exportApplicants('csv')">
              {{ exportingApplicants ? 'Mengekspor...' : 'Export CSV' }}
            </button>
            <button type="button" class="btn-export" :disabled="exportingApplicants" @click="exportApplicants('xlsx')">
              Export Excel
            </button>
        </div>

        <div v-if="applicantsLoading" class="loading-wrap content-card"><LoadingSkeleton type="table" :rows="8" :columns="8" /></div>
        <div v-else-if="applicants.length === 0" class="empty-state content-card">
          <div class="empty-icon">👤</div>
          <h3>Belum ada calon peserta didik</h3>
          <p>Pilih periode dan jalur, lalu tambah calon.</p>
          <button type="button" class="btn-primary" @click="openApplicantModal()">Tambah Calon</button>
        </div>
        <div v-else class="table-wrap content-card">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-check">
                  <input type="checkbox" :checked="allEligibleSelected" :indeterminate="someEligibleSelected" @change="toggleSelectAllApplicants" />
                </th>
                <th>No. Pendaftaran</th>
                <th>Nama</th>
                <th>Jalur</th>
                <th>Periode</th>
                <th>Rank</th>
                <th>Status</th>
                <th>Bayar</th>
                <th>Berkas</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in applicants" :key="a.id">
                  <td class="col-check">
                    <input
                      v-if="isSelectable(a)"
                      type="checkbox"
                      :value="a.id"
                      v-model="selectedApplicantIds"
                    />
                    <span v-else class="cell-empty">—</span>
                  </td>
                  <td><strong>{{ a.registration_number }}</strong></td>
                  <td>{{ a.name }}</td>
                  <td>{{ a.channel?.name }}</td>
                  <td>{{ a.period?.name }}</td>
                  <td>{{ a.rank ?? '-' }}</td>
                  <td><span :class="['status-badge', 'status-' + a.status]">{{ statusApplicantLabels[a.status] || a.status }}</span></td>
                  <td><span :class="['pay-badge', 'pay-' + (a.payment_status || 'unpaid')]">{{ paymentStatusLabels[a.payment_status] || 'Belum bayar' }}</span></td>
                  <td>{{ documentSummaryLabel(a) }}</td>
                <td>
                  <TableAction kind="view" :to="`/ppdb/pendaftar/${a.id}`" title="Detail" />
                  <button v-if="canSetResult(a)" type="button" class="btn-action btn-edit" @click="openResultModal(a)">Hasil</button>
                  <button v-if="canConfirmReReg(a)" type="button" class="btn-action btn-edit" @click="doConfirmReReg(a)">Daftar Ulang</button>
                  <button v-if="canConvertToStudent(a)" type="button" class="btn-action btn-primary-sm" @click="openConvertModal(a)">Jadikan Siswa</button>
                  <TableAction kind="edit" @click="openApplicantModal(a)" />
                  <TableAction kind="delete" @click="confirmDeleteApplicant(a)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="applicantsPagination.last_page > 1" class="pagination-bar content-card">
          <span class="pagination-info">Halaman {{ applicantsPagination.current_page }} / {{ applicantsPagination.last_page }} ({{ applicantsPagination.total }} data)</span>
          <div class="pagination-btns">
            <button type="button" class="pagination-btn" :disabled="applicantsPagination.current_page <= 1" @click="goApplicantsPage(applicantsPagination.current_page - 1)">Sebelumnya</button>
            <button type="button" class="pagination-btn" :disabled="applicantsPagination.current_page >= applicantsPagination.last_page" @click="goApplicantsPage(applicantsPagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </main>

      <div v-if="showApplicantModal" class="modal-overlay" @click="showApplicantModal = false">
        <div class="modal-content form-modal form-modal-wide" @click.stop>
          <div class="modal-header">
            <h3>{{ editingApplicant ? 'Edit Calon Peserta Didik' : 'Tambah Calon Peserta Didik' }}</h3>
            <button type="button" class="btn-close" @click="showApplicantModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitApplicant">
            <div class="form-row">
              <div class="form-group">
                <label>Periode PPDB *</label>
                <select v-model="applicantForm.ppdb_period_id" required :disabled="!!editingApplicant" class="form-select">
                  <option value="">Pilih periode</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jalur *</label>
                <select v-model="applicantForm.ppdb_channel_id" required class="form-select">
                  <option value="">Pilih jalur</option>
                  <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Nama Lengkap *</label>
              <input v-model="applicantForm.name" type="text" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>NIK</label>
                <input v-model="applicantForm.nik" type="text" />
              </div>
              <div class="form-group">
                <label>NISN</label>
                <input v-model="applicantForm.nisn" type="text" />
              </div>
              <div class="form-group">
                <label>Jenis Kelamin *</label>
                <select v-model="applicantForm.gender" required class="form-select">
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Tempat Lahir</label>
                <input v-model="applicantForm.birth_place" type="text" />
              </div>
              <div class="form-group">
                <label>Tanggal Lahir</label>
                <input v-model="applicantForm.birth_date" type="date" />
              </div>
            </div>
            <AddressCascade v-model="applicantForm" />
            <div class="form-row">
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="applicantForm.phone" type="text" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input v-model="applicantForm.email" type="email" />
              </div>
              <div class="form-group">
                <label>Agama</label>
                <select v-model="applicantForm.religion" class="form-select">
                  <option value="">—</option>
                  <option value="Islam">Islam</option>
                  <option value="Kristen">Kristen</option>
                  <option value="Katolik">Katolik</option>
                  <option value="Hindu">Hindu</option>
                  <option value="Buddha">Buddha</option>
                  <option value="Konghucu">Konghucu</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>NPSN Sekolah Asal</label>
                <input v-model="applicantForm.previous_school_npsn" type="text" placeholder="10 digit" />
              </div>
              <div class="form-group">
                <label>Nama Sekolah Asal</label>
                <input v-model="applicantForm.previous_school" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Alamat Sekolah Asal</label>
              <textarea v-model="applicantForm.previous_school_address" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Ayah — Nama</label>
              <input v-model="applicantForm.father_name" type="text" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Ayah — NIK</label>
                <input v-model="applicantForm.father_nik" type="text" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Ayah — Telepon</label>
                <input v-model="applicantForm.father_phone" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Ibu — Nama</label>
              <input v-model="applicantForm.mother_name" type="text" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Ibu — NIK</label>
                <input v-model="applicantForm.mother_nik" type="text" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Ibu — Telepon</label>
                <input v-model="applicantForm.mother_phone" type="text" />
              </div>
            </div>
            <div class="form-group">
              <label>Wali — Nama / Telepon / Hubungan</label>
              <div class="form-row">
                <input v-model="applicantForm.guardian_name" type="text" placeholder="Nama wali" />
                <input v-model="applicantForm.guardian_phone" type="text" placeholder="Telepon" />
                <input v-model="applicantForm.guardian_relation" type="text" placeholder="Hubungan" />
              </div>
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="applicantForm.notes" rows="2"></textarea>
            </div>
            <div v-if="applicantFormError" class="error-message">{{ applicantFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showApplicantModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="applicantFormSubmitting">{{ applicantFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showResultModal" class="modal-overlay" @click="showResultModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Set Hasil Seleksi — {{ resultTarget?.registration_number }}</h3>
            <button type="button" class="btn-close" @click="showResultModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitResult">
            <div class="form-group">
              <label>Hasil *</label>
              <select v-model="resultForm.status" required class="form-select">
                <option value="passed">Lulus</option>
                <option value="reserve">Cadangan</option>
                <option value="failed">Tidak Lulus</option>
              </select>
            </div>
            <div class="form-group">
              <label>Rank (opsional)</label>
              <input v-model.number="resultForm.rank" type="number" min="1" placeholder="Urutan peringkat" />
            </div>
            <div class="form-group">
              <label>Catatan hasil</label>
              <textarea v-model="resultForm.result_notes" rows="2" placeholder="Opsional"></textarea>
            </div>
            <div v-if="resultFormError" class="error-message">{{ resultFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showResultModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="resultFormSubmitting">{{ resultFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showConvertModal" class="modal-overlay" @click="closeConvertModal">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>{{ convertBulk ? ('Jadikan Siswa — ' + selectedForConvert.length + ' calon') : ('Jadikan Siswa — ' + (convertTarget?.registration_number || '')) }}</h3>
            <button type="button" class="btn-close" @click="closeConvertModal">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitConvert">
            <div class="form-group">
              <label>Kelas (opsional)</label>
              <select v-model="convertForm.class_id" class="form-select">
                <option value="">— Tanpa kelas —</option>
                <option v-for="c in convertClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div v-if="convertFormError" class="error-message">{{ convertFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeConvertModal">Batal</button>
              <button type="submit" class="btn-primary" :disabled="convertFormSubmitting">{{ convertFormSubmitting ? 'Memproses...' : 'Jadikan Siswa' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showBulkResultModal" class="modal-overlay" @click="showBulkResultModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Set Hasil Massal ({{ selectedForResult.length }} calon)</h3>
            <button type="button" class="btn-close" @click="showBulkResultModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="doBulkResult">
            <div class="form-group">
              <label>Hasil *</label>
              <select v-model="bulkResultForm.status" required class="form-select">
                <option value="passed">Lulus</option>
                <option value="reserve">Cadangan</option>
                <option value="failed">Tidak Lulus</option>
              </select>
            </div>
            <div class="form-group">
              <label>Catatan (opsional)</label>
              <textarea v-model="bulkResultForm.result_notes" rows="2"></textarea>
            </div>
            <div v-if="bulkResultError" class="error-message">{{ bulkResultError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showBulkResultModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="bulkResulting">{{ bulkResulting ? 'Memproses...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog v-if="deleteApplicantTarget" :show="!!deleteApplicantTarget" title="Hapus Calon" message="Yakin menghapus data calon ini?" confirmText="Hapus" @confirm="doDeleteApplicant" @cancel="deleteApplicantTarget = null" />
      <ConfirmDialog
        v-if="showBulkReRegConfirm"
        :show="showBulkReRegConfirm"
        title="Daftar ulang massal"
        :message="'Konfirmasi daftar ulang untuk ' + selectedForReReg.length + ' calon yang lulus/cadangan?'"
        confirmText="Konfirmasi"
        @confirm="doBulkReReg"
        @cancel="showBulkReRegConfirm = false"
      />
      <AccountCredentialsModal
        :show="!!accountCredentials"
        :title="accountCredentials?.title"
        :name="accountCredentials?.name"
        :login-label="accountCredentials?.loginLabel || 'NIK'"
        :login-value="accountCredentials?.loginValue"
        :password="accountCredentials?.password"
        :hint="accountCredentials?.hint"
        @close="accountCredentials = null"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import AddressCascade from '@/components/AddressCascade.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { ppdbPeriodApi, ppdbChannelApi, ppdbApplicantApi } from '@/api/ppdb'
import { classApi } from '@/api/class'
import { useToast } from '@/composables/useToast'
import { studentLoginCredentials } from '@/utils/accountCredentials'
import { pickAddress } from '@/utils/addressFields'
import {
  statusApplicantLabels,
  paymentStatusLabels,
  canSetResult,
  canConfirmReReg,
  canConvertToStudent,
  canBulkVerify,
  canBulkSelect,
  emptyApplicantForm,
  documentSummaryLabel,
} from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const accountCredentials = ref(null)
const route = useRoute()
const router = useRouter()

const periods = ref([])
const channels = ref([])
const applicants = ref([])
const applicantsLoading = ref(false)
const applicantsPagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const applicantFilters = ref({
  search: '',
  ppdb_period_id: '',
  ppdb_channel_id: '',
  status: '',
  payment_status: '',
  needs_verification: false,
  needs_result: false,
})
const selectedApplicantIds = ref([])
const bulkVerifying = ref(false)
const bulkResulting = ref(false)
const bulkReReging = ref(false)
const showBulkReRegConfirm = ref(false)
const convertBulk = ref(false)
const exportingApplicants = ref(false)
const showBulkResultModal = ref(false)
const bulkResultForm = ref({ status: 'passed', result_notes: '' })
const bulkResultError = ref('')

const showApplicantModal = ref(false)
const editingApplicant = ref(null)
const applicantForm = ref(emptyApplicantForm())
const applicantFormError = ref('')
const applicantFormSubmitting = ref(false)

const showResultModal = ref(false)
const resultTarget = ref(null)
const resultForm = ref({ status: 'passed', rank: null, result_notes: '' })
const resultFormError = ref('')
const resultFormSubmitting = ref(false)

const showConvertModal = ref(false)
const convertTarget = ref(null)
const convertForm = ref({ class_id: '' })
const convertClasses = ref([])
const convertFormError = ref('')
const convertFormSubmitting = ref(false)

const deleteApplicantTarget = ref(null)

const eligibleIds = computed(() =>
  applicants.value.filter(a => canBulkSelect(a)).map(a => a.id)
)
const selectedApplicants = computed(() =>
  applicants.value.filter(a => selectedApplicantIds.value.includes(a.id))
)
const selectedForVerify = computed(() =>
  selectedApplicants.value.filter(a => canBulkVerify(a))
)
const selectedForResult = computed(() =>
  selectedApplicants.value.filter(a => canSetResult(a))
)
const selectedForReReg = computed(() =>
  selectedApplicants.value.filter(a => canConfirmReReg(a))
)
const selectedForConvert = computed(() =>
  selectedApplicants.value.filter(a => canConvertToStudent(a))
)
const allEligibleSelected = computed(() =>
  eligibleIds.value.length > 0 && eligibleIds.value.every(id => selectedApplicantIds.value.includes(id))
)
const someEligibleSelected = computed(() =>
  selectedApplicantIds.value.length > 0 && !allEligibleSelected.value
)

function isSelectable(a) {
  return canBulkSelect(a)
}

function applyQueryFilters() {
  const q = route.query
  if (q.needs_verification === '1' || q.needs_verification === 'true') {
    applicantFilters.value.needs_verification = true
    applicantFilters.value.needs_result = false
    applicantFilters.value.status = ''
  } else if (q.needs_result === '1' || q.needs_result === 'true') {
    applicantFilters.value.needs_result = true
    applicantFilters.value.needs_verification = false
    applicantFilters.value.status = ''
  }
  if (q.ppdb_period_id) applicantFilters.value.ppdb_period_id = String(q.ppdb_period_id)
  if (q.status) applicantFilters.value.status = String(q.status)
  if (q.payment_status) applicantFilters.value.payment_status = String(q.payment_status)
}

function onStatusFilterChange() {
  applicantFilters.value.needs_verification = false
  applicantFilters.value.needs_result = false
  loadApplicants()
}

async function loadPeriods() {
  try {
    const res = await ppdbPeriodApi.getAll({ per_page: 100 })
    periods.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat periode', e.formattedMessage || 'Coba lagi.')
  }
}

async function loadChannels() {
  try {
    const res = await ppdbChannelApi.getAll({ active_only: false })
    channels.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat jalur', e.formattedMessage || 'Coba lagi.')
  }
}

let applicantDebounce = null
function debounceLoadApplicants() {
  clearTimeout(applicantDebounce)
  applicantDebounce = setTimeout(loadApplicants, 300)
}

function setFilterNeedsVerification() {
  applicantFilters.value.status = ''
  applicantFilters.value.needs_result = false
  applicantFilters.value.needs_verification = true
  router.replace({ query: { ...route.query, needs_verification: '1', needs_result: undefined } })
  loadApplicants()
}

function setFilterNeedsResult() {
  applicantFilters.value.status = ''
  applicantFilters.value.needs_verification = false
  applicantFilters.value.needs_result = true
  router.replace({ query: { ...route.query, needs_result: '1', needs_verification: undefined } })
  loadApplicants()
}

function toggleSelectAllApplicants(e) {
  selectedApplicantIds.value = e.target.checked ? [...eligibleIds.value] : []
}

async function doBulkVerification() {
  if (!selectedForVerify.value.length) return
  bulkVerifying.value = true
  try {
    await ppdbApplicantApi.bulkVerification({
      applicant_ids: selectedForVerify.value.map(a => a.id),
      documents_verified: true,
    })
    toast.success(selectedForVerify.value.length + ' calon ditandai verified')
    selectedApplicantIds.value = []
    loadApplicants()
  } catch (e) {
    toast.error('Gagal bulk verifikasi', e.response?.data?.message || e.formattedMessage)
  } finally {
    bulkVerifying.value = false
  }
}

async function loadApplicants() {
  applicantsLoading.value = true
  try {
    const params = {
      page: applicantsPagination.value.current_page,
      per_page: 15,
      ...applicantFilters.value,
    }
    if (!params.ppdb_period_id) delete params.ppdb_period_id
    if (!params.ppdb_channel_id) delete params.ppdb_channel_id
    if (!params.status) delete params.status
    if (!params.payment_status) delete params.payment_status
    if (!params.needs_verification) delete params.needs_verification
    if (!params.needs_result) delete params.needs_result
    if (!params.search) delete params.search
    const res = await ppdbApplicantApi.getAll(params)
    applicants.value = res.data.data || []
    const meta = res.data.meta || {}
    applicantsPagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 15,
      total: meta.total ?? 0,
    }
    selectedApplicantIds.value = []
  } catch (e) {
    toast.error('Gagal memuat calon PPDB', e.formattedMessage || 'Daftar calon tidak dapat dimuat.')
  } finally {
    applicantsLoading.value = false
  }
}

function goApplicantsPage(page) {
  applicantsPagination.value.current_page = page
  loadApplicants()
}

async function exportApplicants(format = 'csv') {
  exportingApplicants.value = true
  try {
    const params = { ...applicantFilters.value, format }
    if (!params.ppdb_period_id) delete params.ppdb_period_id
    if (!params.ppdb_channel_id) delete params.ppdb_channel_id
    if (!params.status) delete params.status
    if (!params.payment_status) delete params.payment_status
    if (!params.search) delete params.search
    if (!params.needs_verification) delete params.needs_verification
    if (!params.needs_result) delete params.needs_result
    const res = await ppdbApplicantApi.export(params)
    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'calon-ppdb-' + new Date().toISOString().slice(0, 10) + (format === 'xlsx' ? '.xlsx' : '.csv')
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Export berhasil')
  } catch (e) {
    toast.error('Gagal mengekspor data PPDB', e.formattedMessage || 'Data tidak dapat diekspor.')
  } finally {
    exportingApplicants.value = false
  }
}

function openBulkResultModal() {
  if (!selectedForResult.value.length) return
  bulkResultForm.value = { status: 'passed', result_notes: '' }
  bulkResultError.value = ''
  showBulkResultModal.value = true
}

async function doBulkResult() {
  if (!selectedForResult.value.length) return
  bulkResultError.value = ''
  bulkResulting.value = true
  try {
    const res = await ppdbApplicantApi.bulkResult({
      applicant_ids: selectedForResult.value.map(a => a.id),
      status: bulkResultForm.value.status,
      result_notes: bulkResultForm.value.result_notes || null,
    })
    toast.success(res.data?.message || 'Hasil massal disimpan')
    showBulkResultModal.value = false
    selectedApplicantIds.value = []
    loadApplicants()
  } catch (e) {
    bulkResultError.value = e.response?.data?.message || e.formattedMessage || 'Gagal menyimpan'
  } finally {
    bulkResulting.value = false
  }
}

function openApplicantModal(a = null) {
  editingApplicant.value = a
  if (a) {
    applicantForm.value = {
      ...emptyApplicantForm(),
      ...pickAddress(a),
      ppdb_period_id: a.ppdb_period_id,
      ppdb_channel_id: a.ppdb_channel_id,
      name: a.name,
      nik: a.nik || '',
      nisn: a.nisn || '',
      gender: a.gender,
      birth_date: a.birth_date || '',
      birth_place: a.birth_place || '',
      phone: a.phone || '',
      email: a.email || '',
      religion: a.religion || '',
      previous_school: a.previous_school || '',
      previous_school_npsn: a.previous_school_npsn || '',
      previous_school_address: a.previous_school_address || '',
      father_name: a.father_name || '',
      father_phone: a.father_phone || '',
      father_nik: a.father_nik || '',
      mother_name: a.mother_name || '',
      mother_phone: a.mother_phone || '',
      mother_nik: a.mother_nik || '',
      guardian_name: a.guardian_name || '',
      guardian_phone: a.guardian_phone || '',
      guardian_relation: a.guardian_relation || '',
      notes: a.notes || '',
    }
  } else {
    applicantForm.value = {
      ...emptyApplicantForm(),
      ppdb_period_id: periods.value[0]?.id || '',
      ppdb_channel_id: channels.value[0]?.id || '',
    }
  }
  applicantFormError.value = ''
  showApplicantModal.value = true
}

async function submitApplicant() {
  applicantFormError.value = ''
  applicantFormSubmitting.value = true
  try {
    const payload = { ...applicantForm.value }
    if (!payload.ppdb_period_id || !payload.ppdb_channel_id || !payload.name || !payload.gender) {
      applicantFormError.value = 'Periode, jalur, nama, dan jenis kelamin wajib diisi.'
      applicantFormSubmitting.value = false
      return
    }
    if (editingApplicant.value) {
      await ppdbApplicantApi.update(editingApplicant.value.id, payload)
      toast.success('Calon berhasil diperbarui')
    } else {
      await ppdbApplicantApi.create(payload)
      toast.success('Calon berhasil ditambahkan')
    }
    showApplicantModal.value = false
    loadApplicants()
  } catch (e) {
    applicantFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    applicantFormSubmitting.value = false
  }
}

function openResultModal(a) {
  resultTarget.value = a
  resultForm.value = {
    status: a.status === 'passed' ? 'passed' : a.status === 'reserve' ? 'reserve' : a.status === 'failed' ? 'failed' : 'passed',
    rank: a.rank ?? null,
    result_notes: a.result_notes || '',
  }
  resultFormError.value = ''
  showResultModal.value = true
}

async function submitResult() {
  if (!resultTarget.value) return
  resultFormError.value = ''
  resultFormSubmitting.value = true
  try {
    await ppdbApplicantApi.setResult(resultTarget.value.id, {
      status: resultForm.value.status,
      rank: resultForm.value.rank || null,
      result_notes: resultForm.value.result_notes || null,
    })
    toast.success('Hasil seleksi disimpan')
    showResultModal.value = false
    resultTarget.value = null
    loadApplicants()
  } catch (e) {
    resultFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    resultFormSubmitting.value = false
  }
}

async function doConfirmReReg(a) {
  try {
    await ppdbApplicantApi.confirmReRegistration(a.id)
    toast.success('Daftar ulang dikonfirmasi')
    loadApplicants()
  } catch (e) {
    toast.error('Gagal konfirmasi daftar ulang', e.formattedMessage || 'Coba lagi.')
  }
}

async function doBulkReReg() {
  const targets = [...selectedForReReg.value]
  if (!targets.length) {
    showBulkReRegConfirm.value = false
    return
  }
  bulkReReging.value = true
  showBulkReRegConfirm.value = false
  let ok = 0
  const errors = []
  for (const a of targets) {
    try {
      await ppdbApplicantApi.confirmReRegistration(a.id)
      ok++
    } catch (e) {
      errors.push((a.registration_number || a.name) + ': ' + (e.response?.data?.message || e.formattedMessage || 'gagal'))
    }
  }
  bulkReReging.value = false
  selectedApplicantIds.value = []
  loadApplicants()
  if (ok) toast.success(ok + ' calon dikonfirmasi daftar ulang')
  if (errors.length) toast.error('Sebagian gagal', errors.slice(0, 3).join(' '))
}

function loadConvertClasses(applicant) {
  convertClasses.value = []
  const period = periods.value.find(p => p.id === applicant?.ppdb_period_id) || applicant?.period
  if (period?.academic_year_id) {
    classApi.getAll({ academic_year_id: period.academic_year_id, per_page: 200 }).then((res) => {
      convertClasses.value = res.data.data || []
    }).catch(() => {})
  }
}

function closeConvertModal() {
  showConvertModal.value = false
  convertBulk.value = false
  convertTarget.value = null
  convertFormError.value = ''
}

function openConvertModal(a) {
  convertBulk.value = false
  convertTarget.value = a
  convertForm.value = { class_id: '' }
  convertFormError.value = ''
  loadConvertClasses(a)
  showConvertModal.value = true
}

function openBulkConvertModal() {
  if (!selectedForConvert.value.length) return
  convertBulk.value = true
  convertTarget.value = selectedForConvert.value[0]
  convertForm.value = { class_id: '' }
  convertFormError.value = ''
  loadConvertClasses(selectedForConvert.value[0])
  showConvertModal.value = true
}

async function submitConvert() {
  convertFormError.value = ''
  convertFormSubmitting.value = true
  const classId = convertForm.value.class_id || undefined
  try {
    if (convertBulk.value) {
      const targets = [...selectedForConvert.value]
      let ok = 0
      const errors = []
      for (const a of targets) {
        try {
          await ppdbApplicantApi.convertToStudent(a.id, { class_id: classId })
          ok++
        } catch (e) {
          errors.push((a.registration_number || a.name) + ': ' + (e.response?.data?.message || e.formattedMessage || 'gagal'))
        }
      }
      showConvertModal.value = false
      convertTarget.value = null
      convertBulk.value = false
      selectedApplicantIds.value = []
      loadApplicants()
      if (ok) toast.success(ok + ' calon dijadikan siswa')
      if (errors.length) {
        convertFormError.value = errors.slice(0, 3).join(' ')
        toast.error('Sebagian gagal', errors.slice(0, 3).join(' '))
      }
      return
    }

    if (!convertTarget.value) return
    const res = await ppdbApplicantApi.convertToStudent(convertTarget.value.id, {
      class_id: classId,
    })
    showConvertModal.value = false
    const creds = studentLoginCredentials({
      name: convertTarget.value.name,
      nik: convertTarget.value.nik,
      birth_date: convertTarget.value.birth_date,
    }, res.data?.login_hint)
    convertTarget.value = null
    loadApplicants()
    if (creds) {
      creds.title = 'Akun login siswa dibuat'
      accountCredentials.value = creds
    } else {
      toast.success(res.data?.message || 'Calon berhasil dijadikan siswa')
    }
  } catch (e) {
    convertFormError.value = e.formattedMessage || 'Gagal menjadikan siswa'
  } finally {
    convertFormSubmitting.value = false
  }
}

function confirmDeleteApplicant(a) {
  deleteApplicantTarget.value = a
}

async function doDeleteApplicant() {
  if (!deleteApplicantTarget.value) return
  try {
    await ppdbApplicantApi.delete(deleteApplicantTarget.value.id)
    toast.success('Calon dihapus')
    deleteApplicantTarget.value = null
    loadApplicants()
  } catch (e) {
    toast.error('Gagal menghapus calon', e.formattedMessage || 'Calon tidak dapat dihapus.')
  }
}

watch(() => route.query, () => {
  applyQueryFilters()
  loadApplicants()
})

onMounted(async () => {
  applyQueryFilters()
  await Promise.all([loadPeriods(), loadChannels()])
  loadApplicants()
})
</script>

<style scoped>
a.btn-action {
  display: inline-block;
  text-decoration: none;
}
.pay-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}
.pay-unpaid { background: #fee2e2; color: #b91c1c; }
.pay-pending { background: #fef3c7; color: #92400e; }
.pay-paid { background: #d1fae5; color: #065f46; }
.pay-waived { background: #e2e8f0; color: #475569; }
</style>
