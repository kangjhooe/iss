<template>
  <div class="tab-content">
    <div class="tab-header">
      <div>
        <h2 class="reports-title">Penghapusan Barang</h2>
        <p class="muted">Setiap pencatatan penghapusan tersimpan terpisah (termasuk penghapusan sebagian). Stok barang otomatis berkurang.</p>
      </div>
      <button type="button" class="btn-primary" @click="openDisposeModal()">Catat Penghapusan</button>
    </div>

    <div v-if="loading" class="loading-state"><p>Memuat data...</p></div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Unit Aset</th>
            <th>Jumlah</th>
            <th>Tgl Penghapusan</th>
            <th>Status Akhir</th>
            <th>No. SK / BA</th>
            <th>Dokumen</th>
            <th>Alasan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in items" :key="row.id">
            <td class="col-no">{{ inventoryRowNumber(meta, index) }}</td>
            <td>{{ row.item_code || row.item?.code }}</td>
            <td>{{ row.item_name || row.item?.name }}</td>
            <td class="muted">{{ row.asset_number || row.asset?.asset_number || '-' }}</td>
            <td>{{ row.quantity }} {{ row.item_unit || row.item?.unit || 'Unit' }}</td>
            <td>{{ formatDate(row.disposal_date) }}</td>
            <td><span :class="getStatusClass(row.status)">{{ row.status }}</span></td>
            <td>{{ row.disposal_document_number || '-' }}</td>
            <td>
              <a
                v-if="row.document_url"
                :href="row.document_url"
                target="_blank"
                rel="noopener"
                class="doc-link"
                :title="row.document_name || 'Unduh SK/BA'"
              >
                PDF
              </a>
              <span v-else class="muted">-</span>
            </td>
            <td class="muted">{{ row.disposal_reason || '-' }}</td>
            <td class="table-actions-cell">
              <TableAction kind="view" title="Detail barang" @click="$emit('view-detail', row.item ?? { id: row.item_id })" />
              <TableAction kind="edit" title="Edit" @click="openEditModal(row)" />
              <TableAction kind="delete" title="Batalkan" @click="confirmUndo(row)" />
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="items.length === 0" class="empty-state">
        <h3>Belum ada penghapusan tercatat</h3>
        <p>Gunakan tombol "Catat Penghapusan" untuk mencatat barang yang tidak digunakan lagi.</p>
      </div>
      <PaginationBar
        embedded
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="data"
        @page-change="load"
        @per-page-change="changePerPage"
      />
    </div>

    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingRow ? 'Edit Penghapusan' : 'Catat Penghapusan Barang' }}</h3>
          <button type="button" class="btn-close" @click="closeModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="submitForm">
          <div v-if="!editingRow" class="form-group">
            <label>Jenis Penghapusan *</label>
            <select v-model="disposeMode" @change="onDisposeModeChange">
              <option value="stock">Barang Stok</option>
              <option value="asset">Aset Individual</option>
            </select>
          </div>
          <div v-if="!editingRow && disposeMode === 'stock'" class="form-group">
            <label>Barang *</label>
            <select v-model="form.item_id" required @change="onItemChange">
              <option value="">Pilih barang aktif</option>
              <option v-for="it in itemOptions" :key="it.id" :value="it.id">
                {{ it.code }} — {{ it.name }} ({{ it.quantity }} {{ it.unit || 'Unit' }}{{ itemStatusSuffix(it) }})
              </option>
            </select>
          </div>
          <div v-if="!editingRow && disposeMode === 'asset'" class="form-group">
            <label>Unit Aset *</label>
            <select v-model="form.asset_id" required @change="onAssetChange">
              <option value="">Pilih aset</option>
              <option v-for="a in assetOptions" :key="a.id" :value="a.id">
                {{ a.asset_number }} — {{ a.item?.name || a.item_name }} ({{ a.status }})
              </option>
            </select>
          </div>
          <div v-else-if="editingRow" class="form-group">
            <label>Barang / Aset</label>
            <input
              type="text"
              readonly
              :value="editingRow.asset_number
                ? `${editingRow.asset_number} — ${editingRow.item_name || ''}`
                : `${editingRow.item_code || ''} — ${editingRow.item_name || ''}`"
            />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tanggal Penghapusan *</label>
              <input v-model="form.disposal_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Status Akhir *</label>
              <select v-model="form.status" required>
                <option value="Dijual">Dijual</option>
                <option value="Hilang">Hilang</option>
                <option value="Rusak">Rusak / Tidak Layak</option>
              </select>
            </div>
          </div>
          <div v-if="editingRow && !editingRow.asset_id" class="form-row">
            <div class="form-group">
              <label>Jumlah *</label>
              <input v-model.number="form.quantity" type="number" min="1" required />
            </div>
            <div class="form-group">
              <label>No. SK / Berita Acara</label>
              <input v-model="form.disposal_document_number" placeholder="Opsional" />
            </div>
          </div>
          <div v-else-if="editingRow" class="form-group">
            <label>No. SK / Berita Acara</label>
            <input v-model="form.disposal_document_number" placeholder="Opsional" />
          </div>
          <div v-else-if="!editingRow && disposeMode === 'stock'" class="form-row">
            <div class="form-group">
              <label>Jumlah *</label>
              <input
                v-model.number="form.quantity"
                type="number"
                min="1"
                :max="maxDisposeQty"
                required
              />
              <p v-if="maxDisposeQty" class="form-hint">{{ disposeQtyHint }}</p>
            </div>
            <div class="form-group">
              <label>No. SK / Berita Acara</label>
              <input v-model="form.disposal_document_number" placeholder="Opsional" />
            </div>
          </div>
          <div v-else-if="!editingRow" class="form-group">
            <label>No. SK / Berita Acara</label>
            <input v-model="form.disposal_document_number" placeholder="Opsional" />
          </div>
          <div class="form-group">
            <label>Alasan Penghapusan *</label>
            <textarea v-model="form.disposal_reason" rows="3" required placeholder="Contoh: Rusak total, tidak ekonomis diperbaiki" />
          </div>
          <div class="form-group">
            <label>Dokumen SK / Berita Acara (PDF)</label>
            <input
              ref="documentInput"
              type="file"
              accept=".pdf,application/pdf"
              @change="onDocumentChange"
            />
            <p v-if="editingRow?.document_name" class="form-hint muted">
              Dokumen saat ini: {{ editingRow.document_name }}
              <button type="button" class="link-btn" :disabled="removingDocument" @click="removeDocument">
                {{ removingDocument ? 'Menghapus...' : 'Hapus dokumen' }}
              </button>
            </p>
            <p class="form-hint muted">Opsional. Maks. 2 MB, format PDF.</p>
          </div>
          <div v-if="error" class="error-message">{{ error }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Menyimpan...' : (editingRow ? 'Simpan Perubahan' : 'Simpan Penghapusan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryApi } from '@/api/inventory'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { formatDate, getStatusClass } from '@/composables/inventory/inventoryFormatters'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'

const DISPOSABLE_STATUSES = new Set(['Tersedia', 'Rusak', 'Hilang'])
const DISPOSAL_STATUS_PRESETS = new Set(['Dijual', 'Hilang', 'Rusak'])

const emit = defineEmits(['view-detail', 'changed'])

const toast = useToast()
const { showConfirm, setLoading: setDeleteLoading } = useConfirmDelete()

const items = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const itemOptions = ref([])
const assetOptions = ref([])
const disposeMode = ref('stock')

const showModal = ref(false)
const editingRow = ref(null)
const saving = ref(false)
const error = ref('')
const form = ref(emptyForm())
const documentFile = ref(null)
const documentInput = ref(null)
const removingDocument = ref(false)

const selectedStockItem = computed(() => {
  if (editingRow.value) return null
  return itemOptions.value.find((i) => i.id === Number(form.value.item_id)) || null
})

const maxDisposeQty = computed(() => selectedStockItem.value?.quantity || null)

const disposeQtyHint = computed(() => {
  const qty = maxDisposeQty.value
  if (!qty) return ''
  const status = selectedStockItem.value?.status
  if (status === 'Rusak' || status === 'Hilang') {
    return `Stok ${status.toLowerCase()}: ${qty} unit — hapus seluruhnya agar hilang dari Beranda`
  }
  return `Stok tersedia: ${qty} unit`
})

function itemStatusSuffix(item) {
  if (!item?.status || item.status === 'Tersedia') return ''
  return ` · ${item.status}`
}

function disposalStatusForEntity(status) {
  return DISPOSAL_STATUS_PRESETS.has(status) ? status : 'Rusak'
}

function emptyForm() {
  return {
    item_id: '',
    asset_id: '',
    disposal_date: new Date().toISOString().split('T')[0],
    disposal_reason: '',
    disposal_document_number: '',
    status: 'Dijual',
    quantity: 1
  }
}

async function load(page = 1) {
  loading.value = true
  try {
    const res = await inventoryApi.getDisposals({ page, per_page: meta.value.per_page })
    items.value = safeArray(res)
    meta.value = parsePagination(res, meta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat data penghapusan')
    items.value = []
  } finally {
    loading.value = false
  }
}

function changePerPage(n) {
  meta.value.per_page = n
  meta.value.current_page = 1
  load(1)
}

async function loadItemOptions() {
  try {
    const res = await inventoryApi.getItems({ per_page: 200, tracking_type: 'stock' })
    const rows = safeArray(res).filter((it) => !it.disposed_at && (it.quantity || 0) > 0)
    rows.sort((a, b) => {
      const aNeeds = a.status === 'Rusak' || a.status === 'Hilang' ? 0 : 1
      const bNeeds = b.status === 'Rusak' || b.status === 'Hilang' ? 0 : 1
      return aNeeds - bNeeds || String(a.name).localeCompare(String(b.name), 'id')
    })
    itemOptions.value = rows
  } catch {
    itemOptions.value = []
  }
}

async function loadAssetOptions() {
  try {
    const res = await inventoryApi.getAssets({ per_page: 200, disposal_status: 'active' })
    assetOptions.value = safeArray(res)
      .filter((a) => DISPOSABLE_STATUSES.has(a.status))
      .sort((a, b) => {
        const aNeeds = a.status === 'Rusak' || a.status === 'Hilang' ? 0 : 1
        const bNeeds = b.status === 'Rusak' || b.status === 'Hilang' ? 0 : 1
        return aNeeds - bNeeds || String(a.asset_number).localeCompare(String(b.asset_number), 'id')
      })
  } catch {
    assetOptions.value = []
  }
}

function onDisposeModeChange() {
  form.value.item_id = ''
  form.value.asset_id = ''
  form.value.quantity = 1
}

function onItemChange() {
  const it = selectedStockItem.value
  if (!it) return
  form.value.quantity = it.status === 'Rusak' || it.status === 'Hilang'
    ? (it.quantity || 1)
    : 1
  form.value.status = disposalStatusForEntity(it.status)
}

function onAssetChange() {
  const asset = assetOptions.value.find((a) => a.id === Number(form.value.asset_id))
  if (!asset) return
  form.value.status = disposalStatusForEntity(asset.status)
}

async function openDisposeModal() {
  editingRow.value = null
  disposeMode.value = 'stock'
  await Promise.all([loadItemOptions(), loadAssetOptions()])
  error.value = ''
  form.value = emptyForm()
  showModal.value = true
}

function openEditModal(row) {
  editingRow.value = row
  error.value = ''
  form.value = {
    item_id: row.item_id,
    disposal_date: row.disposal_date ? String(row.disposal_date).split('T')[0] : '',
    disposal_reason: row.disposal_reason || '',
    disposal_document_number: row.disposal_document_number || '',
    status: row.status || 'Dijual',
    quantity: row.quantity || 1
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingRow.value = null
  error.value = ''
  documentFile.value = null
  if (documentInput.value) documentInput.value.value = ''
}

function onDocumentChange(e) {
  documentFile.value = e.target.files?.[0] || null
}

async function removeDocument() {
  if (!editingRow.value?.id || !editingRow.value?.has_document) return
  removingDocument.value = true
  try {
    const res = await inventoryApi.deleteDisposalDocument(editingRow.value.id)
    editingRow.value = res.data?.data ?? res.data ?? editingRow.value
    documentFile.value = null
    if (documentInput.value) documentInput.value.value = ''
    toast.success('Berhasil', 'Dokumen dihapus')
    await load(meta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menghapus dokumen')
  } finally {
    removingDocument.value = false
  }
}

async function uploadDocumentIfNeeded(disposalId) {
  if (!documentFile.value || !disposalId) return
  await inventoryApi.uploadDisposalDocument(disposalId, documentFile.value)
}

async function findLatestDisposalId() {
  const params = { per_page: 1 }
  if (disposeMode.value === 'asset' && form.value.asset_id) {
    params.asset_id = form.value.asset_id
  } else if (form.value.item_id) {
    params.item_id = form.value.item_id
  } else {
    return null
  }
  const res = await inventoryApi.getDisposals(params)
  const row = safeArray(res)[0]
  return row?.id ?? null
}

async function submitForm() {
  saving.value = true
  error.value = ''
  const payload = {
    disposal_date: form.value.disposal_date,
    disposal_reason: form.value.disposal_reason.trim(),
    disposal_document_number: form.value.disposal_document_number?.trim() || null,
    status: form.value.status,
    quantity: form.value.quantity
  }
  try {
    if (editingRow.value) {
      await inventoryApi.updateDisposal(editingRow.value.id, payload)
      await uploadDocumentIfNeeded(editingRow.value.id)
      toast.success('Berhasil', 'Data penghapusan diperbarui')
    } else if (disposeMode.value === 'asset') {
      await inventoryApi.disposeAsset(form.value.asset_id, payload)
      const disposalId = await findLatestDisposalId()
      await uploadDocumentIfNeeded(disposalId)
      toast.success('Berhasil', 'Penghapusan aset berhasil dicatat')
    } else {
      await inventoryApi.disposeItem(form.value.item_id, payload)
      const disposalId = await findLatestDisposalId()
      await uploadDocumentIfNeeded(disposalId)
      toast.success('Berhasil', 'Penghapusan barang berhasil dicatat')
    }
    closeModal()
    await load(meta.value.current_page || 1)
    emit('changed')
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan penghapusan'
    error.value = msg
    toast.error('Gagal', msg)
  } finally {
    saving.value = false
  }
}

async function confirmUndo(row) {
  const confirmed = await showConfirm({
    title: 'Batalkan Penghapusan',
    message: `Batalkan penghapusan ${row.quantity} unit "${row.item_name || row.item?.name}"?`,
    warning: row.asset_id
      ? 'Aset akan dikembalikan ke status aktif.'
      : 'Stok barang akan dikembalikan sesuai jumlah penghapusan ini.'
  })
  if (!confirmed) return
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteDisposal(row.id)
    toast.success('Berhasil', 'Penghapusan dibatalkan, stok dikembalikan')
    await load(meta.value.current_page || 1)
    emit('changed')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal membatalkan penghapusan')
  } finally {
    setDeleteLoading(false)
  }
}

onMounted(() => load(1))

defineExpose({ refresh: () => load(meta.value.current_page || 1) })
</script>

<style scoped>
.table-actions-cell {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.doc-link {
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
}

.doc-link:hover {
  text-decoration: underline;
}

.link-btn {
  margin-left: 0.5rem;
  background: none;
  border: none;
  color: #dc2626;
  cursor: pointer;
  font-size: 0.85rem;
  padding: 0;
}

.link-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
