<template>
  <div class="tab-content">
    <div class="tab-header">
      <div>
        <h2 class="reports-title">Stock Opname</h2>
        <p class="muted">Hitung fisik barang stok atau cek keberadaan aset individual per ruangan.</p>
      </div>
      <button type="button" class="btn-primary" @click="openCreateModal">Buat Sesi Opname</button>
    </div>

    <div v-if="loading" class="loading-state"><p>Memuat data...</p></div>
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>No. Opname</th>
            <th>Tipe</th>
            <th>Tanggal</th>
            <th>Ruangan</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in opnames" :key="row.id">
            <td class="col-no">{{ inventoryRowNumber(meta, index) }}</td>
            <td>{{ row.opname_number }}</td>
            <td><span class="badge-muted">{{ opnameTypeLabel(row.opname_type) }}</span></td>
            <td>{{ formatDate(row.opname_date) }}</td>
            <td class="muted">{{ row.room?.name || 'Semua ruangan' }}</td>
            <td><span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td>
            <td class="muted">{{ row.counted_lines ?? 0 }} / {{ row.lines_count ?? 0 }}</td>
            <td>
              <TableAction kind="view" title="Detail" @click="openDetail(row)" />
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!opnames.length" class="empty-state">
        <h3>Belum ada sesi opname</h3>
        <p>Buat sesi baru untuk memulai penghitungan fisik atau inventarisasi aset.</p>
      </div>
      <PaginationBar
        embedded
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="sesi"
        @page-change="load"
        @per-page-change="changePerPage"
      />
    </div>

    <div v-if="showCreateModal" class="modal-overlay" @click="showCreateModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Buat Sesi Opname</h3>
          <button type="button" class="btn-close" @click="showCreateModal = false">×</button>
        </div>
        <form class="modal-body" @submit.prevent="submitCreate">
          <div class="form-group">
            <label>Tipe Opname *</label>
            <select v-model="createForm.opname_type" required>
              <option value="stock">Stok Barang (kuantitas)</option>
              <option value="asset">Aset Individual (cek unit)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tanggal Opname *</label>
            <input v-model="createForm.opname_date" type="date" required />
          </div>
          <div class="form-group">
            <label>Ruangan (opsional)</label>
            <select v-model="createForm.room_id">
              <option value="">Semua ruangan</option>
              <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="createForm.notes" rows="2" />
          </div>
          <div v-if="createError" class="error-message">{{ createError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="showCreateModal = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="creating">{{ creating ? 'Membuat...' : 'Buat & Muat Data' }}</button>
          </div>
        </form>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="detailVisible" class="inventory-slide-root slide-overlay" @click="closeDetail">
        <aside class="slide-panel slide-panel-wide" @click.stop>
          <div class="slide-panel-header">
            <div>
              <h3>{{ detail?.opname_number || 'Detail Opname' }}</h3>
              <p class="muted small">
                {{ opnameTypeLabel(detail?.opname_type) }} · {{ statusLabel(detail?.status) }} · {{ formatDate(detail?.opname_date) }}
              </p>
            </div>
            <div class="slide-header-actions">
              <button
                v-if="detail?.status === 'in_progress' || detail?.status === 'draft'"
                type="button"
                class="btn-secondary btn-compact"
                :disabled="detailLoading"
                @click="refreshLines"
              >Muat Ulang</button>
              <button
                v-if="detail?.status === 'in_progress'"
                type="button"
                class="btn-primary btn-compact"
                :disabled="finalizing"
                @click="finalizeOpname"
              >Finalisasi</button>
              <button type="button" class="btn-close" @click="closeDetail">×</button>
            </div>
          </div>
          <div v-if="detailLoading" class="loading-state"><p>Memuat...</p></div>
          <div v-else-if="detail && detail.opname_type === 'asset'" class="slide-panel-body">
            <table class="data-table small">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Aset / Barang</th>
                  <th>Buku</th>
                  <th>Ditemukan?</th>
                  <th>Kondisi Fisik</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ln, index) in detailAssetLines" :key="ln.id">
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>
                    <div class="name">{{ ln.asset?.asset_number }}</div>
                    <div class="muted small">{{ ln.asset?.item?.name || '-' }}</div>
                  </td>
                  <td class="muted small">{{ ln.book_status }} / {{ ln.book_condition || '-' }}</td>
                  <td>
                    <select
                      v-if="isEditable"
                      v-model="assetLineEdits[ln.id].found"
                      class="input-compact"
                      @change="saveAssetLine(ln)"
                    >
                      <option :value="null">-</option>
                      <option :value="true">Ya</option>
                      <option :value="false">Tidak</option>
                    </select>
                    <span v-else>{{ ln.found === true ? 'Ya' : ln.found === false ? 'Tidak' : '-' }}</span>
                  </td>
                  <td>
                    <select
                      v-if="isEditable"
                      v-model="assetLineEdits[ln.id].counted_condition"
                      class="input-compact"
                      @change="saveAssetLine(ln)"
                    >
                      <option value="">-</option>
                      <option value="Baik">Baik</option>
                      <option value="Rusak Ringan">Rusak Ringan</option>
                      <option value="Rusak Berat">Rusak Berat</option>
                      <option value="Habis Pakai">Habis Pakai</option>
                    </select>
                    <span v-else>{{ ln.counted_condition || ln.book_condition || '-' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else-if="detail" class="slide-panel-body">
            <table class="data-table small">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Barang</th>
                  <th>Buku</th>
                  <th>Fisik</th>
                  <th>Selisih</th>
                  <th>Kondisi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ln, index) in detailLines" :key="ln.id">
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>
                    <div class="name">{{ ln.item?.name }}</div>
                    <div class="muted small">{{ ln.item?.code }}</div>
                  </td>
                  <td>{{ ln.book_quantity }}</td>
                  <td>
                    <input
                      v-if="isEditable"
                      v-model.number="lineEdits[ln.id].counted_quantity"
                      type="number"
                      min="0"
                      class="input-compact"
                      @blur="saveLine(ln)"
                    />
                    <span v-else>{{ ln.counted_quantity ?? '-' }}</span>
                  </td>
                  <td>
                    <span :class="varianceClass(ln.variance ?? (lineEdits[ln.id]?.counted_quantity != null ? lineEdits[ln.id].counted_quantity - ln.book_quantity : null))">
                      {{ displayVariance(ln) }}
                    </span>
                  </td>
                  <td>
                    <select
                      v-if="isEditable"
                      v-model="lineEdits[ln.id].condition"
                      class="input-compact"
                      @change="saveLine(ln)"
                    >
                      <option value="">-</option>
                      <option value="Baik">Baik</option>
                      <option value="Rusak Ringan">Rusak Ringan</option>
                      <option value="Rusak Berat">Rusak Berat</option>
                      <option value="Habis Pakai">Habis Pakai</option>
                    </select>
                    <span v-else>{{ ln.condition || '-' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </aside>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryApi } from '@/api/inventory'
import { formatDate } from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

defineProps({
  rooms: { type: Array, default: () => [] }
})

const toast = useToast()
const opnames = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')
const createForm = ref({
  opname_date: new Date().toISOString().split('T')[0],
  opname_type: 'stock',
  room_id: '',
  notes: ''
})

const detailVisible = ref(false)
const detailLoading = ref(false)
const detail = ref(null)
const detailLines = ref([])
const detailAssetLines = ref([])
const lineEdits = ref({})
const assetLineEdits = ref({})
const finalizing = ref(false)

const isEditable = computed(() => ['draft', 'in_progress'].includes(detail.value?.status))

function opnameTypeLabel(type) {
  return type === 'asset' ? 'Aset Individual' : 'Stok Barang'
}

function statusLabel(s) {
  return ({ draft: 'Draft', in_progress: 'Berlangsung', finalized: 'Final', cancelled: 'Batal' })[s] || s
}

function statusClass(s) {
  if (s === 'finalized') return 'badge-success'
  if (s === 'cancelled') return 'badge-muted'
  if (s === 'in_progress') return 'badge-info'
  return 'badge-muted'
}

function varianceClass(v) {
  if (v == null || v === 0) return ''
  return v > 0 ? 'text-success' : 'text-danger'
}

function displayVariance(ln) {
  if (ln.variance != null) return ln.variance > 0 ? `+${ln.variance}` : ln.variance
  const edit = lineEdits.value[ln.id]
  if (edit?.counted_quantity != null) {
    const v = edit.counted_quantity - ln.book_quantity
    return v > 0 ? `+${v}` : v
  }
  return '-'
}

async function load(page = 1) {
  loading.value = true
  try {
    const res = await inventoryApi.getStockOpnames({ page, per_page: meta.value.per_page })
    opnames.value = safeArray(res)
    meta.value = parsePagination(res, meta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat opname')
    opnames.value = []
  } finally {
    loading.value = false
  }
}

function changePerPage(n) {
  meta.value.per_page = n
  load(1)
}

function openCreateModal() {
  createError.value = ''
  createForm.value = {
    opname_date: new Date().toISOString().split('T')[0],
    opname_type: 'stock',
    room_id: '',
    notes: ''
  }
  showCreateModal.value = true
}

async function submitCreate() {
  creating.value = true
  createError.value = ''
  try {
    const payload = {
      opname_date: createForm.value.opname_date,
      opname_type: createForm.value.opname_type,
      notes: createForm.value.notes?.trim() || null
    }
    if (createForm.value.room_id) payload.room_id = createForm.value.room_id
    const res = await inventoryApi.createStockOpname(payload)
    toast.success('Berhasil', 'Sesi opname dibuat')
    showCreateModal.value = false
    await load(1)
    const data = res.data?.data ?? res.data
    if (data?.id) openDetail({ id: data.id })
  } catch (err) {
    createError.value = err.formattedMessage || err.response?.data?.message || 'Gagal membuat opname'
  } finally {
    creating.value = false
  }
}

function initLineEdits(lines) {
  const edits = {}
  for (const ln of lines) {
    edits[ln.id] = {
      counted_quantity: ln.counted_quantity ?? null,
      condition: ln.condition || ''
    }
  }
  lineEdits.value = edits
}

function initAssetLineEdits(lines) {
  const edits = {}
  for (const ln of lines) {
    edits[ln.id] = {
      found: ln.found ?? null,
      counted_condition: ln.counted_condition || ''
    }
  }
  assetLineEdits.value = edits
}

async function openDetail(row) {
  detailVisible.value = true
  detailLoading.value = true
  try {
    const res = await inventoryApi.getStockOpname(row.id)
    const data = res.data?.data ?? res.data ?? res
    detail.value = data
    if (data.opname_type === 'asset') {
      detailAssetLines.value = data.asset_lines || []
      detailLines.value = []
      initAssetLineEdits(detailAssetLines.value)
    } else {
      detailLines.value = data.lines || []
      detailAssetLines.value = []
      initLineEdits(detailLines.value)
    }
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat detail')
    closeDetail()
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailVisible.value = false
  detail.value = null
  detailLines.value = []
  detailAssetLines.value = []
}

async function refreshLines() {
  if (!detail.value) return
  detailLoading.value = true
  try {
    await inventoryApi.refreshStockOpnameLines(detail.value.id)
    await openDetail({ id: detail.value.id })
    toast.success('Berhasil', 'Daftar diperbarui')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat ulang')
  } finally {
    detailLoading.value = false
  }
}

async function saveLine(ln) {
  if (!detail.value || !isEditable.value) return
  const edit = lineEdits.value[ln.id]
  try {
    await inventoryApi.updateStockOpnameLine(detail.value.id, ln.id, {
      counted_quantity: edit.counted_quantity,
      condition: edit.condition || null
    })
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menyimpan baris')
  }
}

async function saveAssetLine(ln) {
  if (!detail.value || !isEditable.value) return
  const edit = assetLineEdits.value[ln.id]
  try {
    await inventoryApi.updateAssetOpnameLine(detail.value.id, ln.id, {
      found: edit.found,
      counted_condition: edit.counted_condition || null
    })
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal menyimpan baris aset')
  }
}

async function finalizeOpname() {
  if (!detail.value) return
  const msg = detail.value.opname_type === 'asset'
    ? 'Finalisasi opname aset? Status/kondisi unit akan diperbarui sesuai hasil cek.'
    : 'Finalisasi opname? Penyesuaian stok akan diterapkan untuk baris ber-selisih.'
  if (!confirm(msg)) return
  finalizing.value = true
  try {
    await inventoryApi.finalizeStockOpname(detail.value.id)
    toast.success('Berhasil', 'Opname difinalisasi')
    await openDetail({ id: detail.value.id })
    await load(meta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal finalisasi')
  } finally {
    finalizing.value = false
  }
}

onMounted(() => load(1))
defineExpose({ refresh: () => load(meta.value.current_page || 1) })
</script>

<style scoped>
.slide-panel-wide { width: min(720px, 100%); }
.input-compact { width: 88px; padding: 4px 6px; font-size: 13px; }
.text-success { color: #059669; font-weight: 600; }
.text-danger { color: #dc2626; font-weight: 600; }
.badge-muted { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; font-size: 12px; }
</style>
