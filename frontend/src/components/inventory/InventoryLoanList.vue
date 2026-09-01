<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <select v-model="loanFilters.item_id" class="filter-select" @change="loadLoans(1)">
          <option value="">Semua Barang</option>
          <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
        </select>
        <select v-model="loanFilters.borrower_type" class="filter-select" @change="loadLoans(1)">
          <option value="">Semua Peminjam</option>
          <option value="Employee">Pegawai</option>
          <option value="Student">Siswa</option>
          <option value="External">Eksternal</option>
        </select>
        <select v-model="loanFilters.status" class="filter-select" @change="loadLoans(1)">
          <option value="">Semua Status</option>
          <option value="Dipinjam">Dipinjam</option>
          <option value="Dikembalikan">Dikembalikan</option>
        </select>
      </div>
      <button type="button" class="btn-primary" @click="openLoanModal()">
        <span>Tambah Peminjaman</span>
      </button>
    </div>

    <div v-if="loansLoading" class="loading-state"><p>Memuat data...</p></div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Barang</th>
            <th>Peminjam</th>
            <th>Tgl Pinjam</th>
            <th>Tgl Jatuh Tempo</th>
            <th>Tgl Kembali</th>
            <th>Qty</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(ln, index) in loans" :key="ln.id" :class="{ 'row-overdue': ln.is_overdue && ln.status === 'Dipinjam' }">
            <td class="col-no">{{ inventoryRowNumber(loansMeta, index) }}</td>
            <td>
              <div class="name-cell">
                <div class="name">{{ ln.item?.name || '-' }}</div>
                <div class="muted small">
                  {{ ln.item?.code || '-' }}
                  <span v-if="ln.asset?.asset_number"> · {{ ln.asset.asset_number }}</span>
                </div>
              </div>
            </td>
            <td>
              {{ ln.borrower_name }}
              <span v-if="ln.borrower_phone" class="muted small">({{ ln.borrower_phone }})</span>
            </td>
            <td>{{ formatDate(ln.loan_date) }}</td>
            <td>{{ formatDate(ln.expected_return_date) }}</td>
            <td>{{ ln.status === 'Dikembalikan' ? formatDate(ln.actual_return_date) : '-' }}</td>
            <td>{{ ln.quantity }}</td>
            <td>
              <span :class="ln.status === 'Dikembalikan' ? 'badge-success' : (ln.is_overdue ? 'badge-danger' : 'badge-info')">
                {{ ln.status }}{{ ln.is_overdue && ln.status === 'Dipinjam' ? ' (Terlambat)' : '' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <TableAction
                  v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'"
                  kind="edit"
                  @click="openEditLoanModal(ln)"
                />
                <TableAction
                  v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'"
                  kind="return"
                  @click="openReturnLoanModal(ln)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="loans.length === 0" class="empty-state">
        <h3>Belum ada data peminjaman</h3>
        <p>Catat peminjaman barang oleh pegawai, siswa, atau pihak eksternal.</p>
        <button type="button" class="btn-primary" @click="openLoanModal()">Tambah Peminjaman</button>
      </div>

      <PaginationBar
        embedded
        :page="loansMeta.current_page"
        :last-page="loansMeta.last_page"
        :per-page="loansMeta.per_page"
        :total="loansMeta.total"
        item-label="peminjaman"
        @page-change="loadLoans"
        @per-page-change="changeLoansPerPage"
      />
    </div>

    <div v-if="showLoanModal" class="modal-overlay" @click="closeLoanModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Tambah Peminjaman</h3>
          <button type="button" class="btn-close" @click="closeLoanModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="saveLoan">
          <div class="form-group">
            <label>Barang *</label>
            <select v-model="loanForm.item_id" required @change="onLoanItemChange">
              <option value="">Pilih Barang</option>
              <option v-for="it in availableItemOptions" :key="it.id" :value="it.id">
                {{ it.code }} - {{ it.name }}
                <template v-if="it.tracking_type === 'individual'"> ({{ it.available_quantity || it.quantity || 0 }} unit)</template>
                <template v-else> (Tersedia: {{ it.quantity || 0 }})</template>
              </option>
            </select>
          </div>
          <div v-if="selectedLoanItem?.tracking_type === 'individual'" class="form-group">
            <label>Unit Aset *</label>
            <select v-model="loanForm.asset_id" required>
              <option value="">Pilih unit aset</option>
              <option v-for="a in loanAssetOptions" :key="a.id" :value="a.id">
                {{ a.asset_number }} — {{ a.serial_number || 'tanpa serial' }} ({{ a.status }})
              </option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Jenis Peminjam *</label>
              <select v-model="loanForm.borrower_type" required>
                <option value="Employee">Pegawai</option>
                <option value="Student">Siswa</option>
                <option value="External">Eksternal</option>
              </select>
            </div>
            <div v-if="selectedLoanItem?.tracking_type !== 'individual'" class="form-group">
              <label>Jumlah *</label>
              <input v-model.number="loanForm.quantity" type="number" min="1" required />
            </div>
          </div>
          <div class="form-group">
            <label>Nama Peminjam *</label>
            <input v-model="loanForm.borrower_name" required />
          </div>
          <div class="form-group">
            <label>No. Telepon</label>
            <input v-model="loanForm.borrower_phone" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tanggal Pinjam *</label>
              <input v-model="loanForm.loan_date" type="date" required @change="onLoanDateChange" />
            </div>
            <div class="form-group">
              <label>Tanggal Jatuh Tempo *</label>
              <input v-model="loanForm.expected_return_date" type="date" required />
            </div>
          </div>
          <div class="form-group">
            <label class="muted small">Durasi cepat</label>
            <div class="duration-presets">
              <button
                v-for="preset in loanDurationPresets"
                :key="preset.days"
                type="button"
                class="btn-outline btn-compact"
                @click="applyLoanDuration(preset.days)"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Tujuan Peminjaman</label>
            <input v-model="loanForm.purpose" />
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="loanForm.notes" rows="2" />
          </div>
          <div v-if="loanError" class="error-message">{{ loanError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeLoanModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="loanSaving">
              {{ loanSaving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showEditLoanModal" class="modal-overlay" @click="closeEditLoanModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Ubah Peminjaman</h3>
          <button type="button" class="btn-close" @click="closeEditLoanModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="saveEditLoan">
          <p v-if="editingLoan" class="muted">
            {{ editingLoan.item?.name }} — {{ editingLoan.borrower_name }}
          </p>
          <div class="form-row">
            <div class="form-group">
              <label>Tanggal Pinjam *</label>
              <input v-model="editLoanForm.loan_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Tanggal Jatuh Tempo *</label>
              <input v-model="editLoanForm.expected_return_date" type="date" required />
            </div>
          </div>
          <div class="form-group">
            <label class="muted small">Durasi cepat</label>
            <div class="duration-presets">
              <button
                v-for="preset in loanDurationPresets"
                :key="'edit-' + preset.days"
                type="button"
                class="btn-outline btn-compact"
                @click="applyEditLoanDuration(preset.days)"
              >
                {{ preset.label }}
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Nama Peminjam *</label>
            <input v-model="editLoanForm.borrower_name" required />
          </div>
          <div class="form-group">
            <label>No. Telepon</label>
            <input v-model="editLoanForm.borrower_phone" />
          </div>
          <div class="form-group">
            <label>Tujuan Peminjaman</label>
            <input v-model="editLoanForm.purpose" />
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="editLoanForm.notes" rows="2" />
          </div>
          <div v-if="editLoanError" class="error-message">{{ editLoanError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeEditLoanModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="editLoanSaving">
              {{ editLoanSaving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showReturnLoanModal" class="modal-overlay" @click="closeReturnLoanModal">
      <div class="modal-content modal-content-sm" @click.stop>
        <div class="modal-header">
          <h3>Pengembalian Barang</h3>
          <button type="button" class="btn-close" @click="closeReturnLoanModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="submitReturnLoan">
          <p v-if="returningLoan" class="muted">
            {{ returningLoan.item?.name }} — dipinjam oleh {{ returningLoan.borrower_name }}
          </p>
          <div class="form-group">
            <label>Tanggal Dikembalikan *</label>
            <input v-model="returnLoanForm.actual_return_date" type="date" required />
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="returnLoanForm.notes" rows="2" />
          </div>
          <div v-if="returnLoanError" class="error-message">{{ returnLoanError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeReturnLoanModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="returnLoanSaving">
              {{ returnLoanSaving ? 'Menyimpan...' : 'Catat Pengembalian' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryApi } from '@/api/inventory'
import {
  formatDate,
  LOAN_DURATION_PRESETS,
  addDaysToDateString,
  todayDateString
} from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loanDurationPresets = LOAN_DURATION_PRESETS

const loans = ref([])
const loansLoading = ref(false)
const loansMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const loanFilters = ref({ item_id: '', borrower_type: '', status: '' })
const itemOptions = ref([])
const availableItemOptions = ref([])
const loanAssetOptions = ref([])

const selectedLoanItem = computed(() =>
  availableItemOptions.value.find((i) => String(i.id) === String(loanForm.value.item_id)) || null
)

const showLoanModal = ref(false)
const loanSaving = ref(false)
const loanError = ref('')
const loanForm = ref({
  item_id: '',
  asset_id: '',
  borrower_type: 'Employee',
  borrower_name: '',
  borrower_phone: '',
  loan_date: new Date().toISOString().split('T')[0],
  expected_return_date: '',
  quantity: 1,
  purpose: '',
  notes: ''
})

const showReturnLoanModal = ref(false)
const returningLoan = ref(null)
const returnLoanSaving = ref(false)
const returnLoanError = ref('')
const returnLoanForm = ref({ actual_return_date: todayDateString(), notes: '' })

const showEditLoanModal = ref(false)
const editingLoan = ref(null)
const editLoanSaving = ref(false)
const editLoanError = ref('')
const editLoanForm = ref({
  loan_date: '',
  expected_return_date: '',
  borrower_name: '',
  borrower_phone: '',
  purpose: '',
  notes: ''
})

async function loadItemOptions() {
  try {
    const res = await inventoryApi.getItems({ per_page: 200 })
    itemOptions.value = safeArray(res)
  } catch {
    itemOptions.value = []
  }
}

async function loadAvailableItemOptions() {
  try {
    const res = await inventoryApi.getItems({ per_page: 200, status: 'Tersedia' })
    availableItemOptions.value = safeArray(res).filter((it) => {
      const qty = it.available_quantity ?? it.quantity ?? 0
      return qty > 0 || it.tracking_type === 'individual'
    })
  } catch {
    availableItemOptions.value = []
  }
}

async function loadLoans(page = 1) {
  loansLoading.value = true
  try {
    const params = { page, per_page: loansMeta.value.per_page }
    if (loanFilters.value.item_id) params.item_id = loanFilters.value.item_id
    if (loanFilters.value.borrower_type) params.borrower_type = loanFilters.value.borrower_type
    if (loanFilters.value.status) params.status = loanFilters.value.status
    const res = await inventoryApi.getLoans(params)
    loans.value = safeArray(res)
    loansMeta.value = parsePagination(res, loansMeta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat peminjaman')
    loans.value = []
  } finally {
    loansLoading.value = false
  }
}

function changeLoansPerPage(n) {
  loansMeta.value.per_page = n
  loansMeta.value.current_page = 1
  loadLoans(1)
}

async function loadLoanAssetOptions(itemId) {
  if (!itemId) {
    loanAssetOptions.value = []
    return
  }
  try {
    const res = await inventoryApi.getAssets({ item_id: itemId, status: 'Tersedia', per_page: 100 })
    loanAssetOptions.value = safeArray(res)
  } catch {
    loanAssetOptions.value = []
  }
}

async function onLoanItemChange() {
  const it = selectedLoanItem.value
  loanForm.value.asset_id = ''
  if (!it) {
    loanAssetOptions.value = []
    return
  }
  if (it.tracking_type === 'individual') {
    loanForm.value.quantity = 1
    await loadLoanAssetOptions(it.id)
  } else {
    loanAssetOptions.value = []
    loanForm.value.quantity = Math.min(loanForm.value.quantity || 1, it.quantity || 1)
  }
}

function applyLoanDuration(days) {
  if (!loanForm.value.loan_date) loanForm.value.loan_date = todayDateString()
  loanForm.value.expected_return_date = addDaysToDateString(loanForm.value.loan_date, days)
}

function onLoanDateChange() {
  if (!loanForm.value.expected_return_date && loanForm.value.loan_date) {
    loanForm.value.expected_return_date = addDaysToDateString(loanForm.value.loan_date, 7)
  }
}

async function openLoanModal() {
  await loadAvailableItemOptions()
  loanError.value = ''
  const today = todayDateString()
  loanForm.value = {
    item_id: '',
    asset_id: '',
    borrower_type: 'Employee',
    borrower_name: '',
    borrower_phone: '',
    loan_date: today,
    expected_return_date: addDaysToDateString(today, 7),
    quantity: 1,
    purpose: '',
    notes: ''
  }
  loanAssetOptions.value = []
  showLoanModal.value = true
}

function closeLoanModal() {
  showLoanModal.value = false
  loanError.value = ''
}

async function saveLoan() {
  loanSaving.value = true
  loanError.value = ''
  try {
    const payload = {
      item_id: loanForm.value.item_id,
      borrower_type: loanForm.value.borrower_type,
      borrower_name: loanForm.value.borrower_name?.trim() || '',
      borrower_phone: loanForm.value.borrower_phone?.trim() || null,
      loan_date: loanForm.value.loan_date,
      expected_return_date: loanForm.value.expected_return_date,
      quantity: selectedLoanItem.value?.tracking_type === 'individual' ? 1 : loanForm.value.quantity,
      purpose: loanForm.value.purpose?.trim() || null,
      notes: loanForm.value.notes?.trim() || null
    }
    if (loanForm.value.asset_id) payload.asset_id = loanForm.value.asset_id
    await inventoryApi.createLoan(payload)
    toast.success('Berhasil', 'Peminjaman berhasil dicatat')
    closeLoanModal()
    await loadLoans(loansMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan peminjaman'
    loanError.value = msg
    toast.error('Gagal', msg)
  } finally {
    loanSaving.value = false
  }
}

function openEditLoanModal(loan) {
  editingLoan.value = loan
  editLoanError.value = ''
  editLoanForm.value = {
    loan_date: loan.loan_date ? String(loan.loan_date).split('T')[0] : todayDateString(),
    expected_return_date: loan.expected_return_date ? String(loan.expected_return_date).split('T')[0] : '',
    borrower_name: loan.borrower_name || '',
    borrower_phone: loan.borrower_phone || '',
    purpose: loan.purpose || '',
    notes: loan.notes || ''
  }
  showEditLoanModal.value = true
}

function closeEditLoanModal() {
  showEditLoanModal.value = false
  editingLoan.value = null
  editLoanError.value = ''
}

function applyEditLoanDuration(days) {
  if (!editLoanForm.value.loan_date) editLoanForm.value.loan_date = todayDateString()
  editLoanForm.value.expected_return_date = addDaysToDateString(editLoanForm.value.loan_date, days)
}

async function saveEditLoan() {
  if (!editingLoan.value) return
  editLoanSaving.value = true
  editLoanError.value = ''
  try {
    await inventoryApi.updateLoan(editingLoan.value.id, {
      loan_date: editLoanForm.value.loan_date,
      expected_return_date: editLoanForm.value.expected_return_date,
      borrower_name: editLoanForm.value.borrower_name?.trim() || '',
      borrower_phone: editLoanForm.value.borrower_phone?.trim() || null,
      purpose: editLoanForm.value.purpose?.trim() || null,
      notes: editLoanForm.value.notes?.trim() || null
    })
    toast.success('Berhasil', 'Peminjaman berhasil diperbarui')
    closeEditLoanModal()
    await loadLoans(loansMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memperbarui peminjaman'
    editLoanError.value = msg
    toast.error('Gagal', msg)
  } finally {
    editLoanSaving.value = false
  }
}

function openReturnLoanModal(loan) {
  returningLoan.value = loan
  returnLoanError.value = ''
  returnLoanForm.value = {
    actual_return_date: todayDateString(),
    notes: ''
  }
  showReturnLoanModal.value = true
}

function closeReturnLoanModal() {
  showReturnLoanModal.value = false
  returningLoan.value = null
  returnLoanError.value = ''
}

async function submitReturnLoan() {
  if (!returningLoan.value) return
  returnLoanSaving.value = true
  returnLoanError.value = ''
  try {
    await inventoryApi.returnLoan(returningLoan.value.id, {
      actual_return_date: returnLoanForm.value.actual_return_date,
      notes: returnLoanForm.value.notes?.trim() || null
    })
    toast.success('Berhasil', 'Pengembalian berhasil dicatat')
    closeReturnLoanModal()
    await loadLoans(loansMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal mencatat pengembalian'
    returnLoanError.value = msg
    toast.error('Gagal', msg)
  } finally {
    returnLoanSaving.value = false
  }
}

onMounted(async () => {
  await loadItemOptions()
  await loadLoans(1)
})
</script>

<style scoped>
.duration-presets {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
