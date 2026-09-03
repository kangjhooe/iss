<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <div class="search-wrap">
          <input
            v-model="filters.search"
            class="search-input"
            placeholder="Cari nomor aset, serial, nama..."
            @input="debounceLoad"
          />
        </div>
        <select v-model="filters.status" class="filter-select" @change="load(1)">
          <option value="">Semua Status</option>
          <option value="Tersedia">Tersedia</option>
          <option value="Dipinjam">Dipinjam</option>
          <option value="Rusak">Rusak</option>
          <option value="Hilang">Hilang</option>
        </select>
        <select v-model="filters.room_id" class="filter-select" @change="load(1)">
          <option value="">Semua Ruangan</option>
          <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </div>
      <div class="header-actions">
        <button type="button" class="btn-secondary btn-compact" :disabled="printingQr" @click="printBulkQr">
          {{ printingQr ? 'Menyiapkan...' : 'Cetak Label QR' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="8" :columns="7" />
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nomor Aset</th>
            <th>Master Barang</th>
            <th>Serial</th>
            <th>Kondisi</th>
            <th>Status</th>
            <th>Lokasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(a, index) in assets" :key="a.id">
            <td class="col-no">{{ inventoryRowNumber(meta, index) }}</td>
            <td>
              <div class="name">{{ a.asset_number }}</div>
              <div class="muted small">{{ a.inventory_number || '-' }}</div>
            </td>
            <td>
              <div class="name">{{ a.item?.name || '-' }}</div>
              <div class="muted small">{{ a.item?.code || '-' }}</div>
            </td>
            <td class="muted">{{ a.serial_number || '-' }}</td>
            <td><span :class="getConditionClass(a.condition)">{{ a.condition }}</span></td>
            <td><span :class="getStatusClass(a.status)">{{ a.status }}</span></td>
            <td class="muted">{{ assetLocationLabel(a) }}</td>
            <td>
              <div class="action-buttons">
                <TableAction kind="view" title="Detail" @click="openDetail(a)" />
                <TableAction
                  kind="print"
                  title="Cetak QR"
                  :disabled="printingQrId === a.id"
                  @click="printSingleQr(a)"
                />
                <TableAction kind="download" title="Cetak KIB" @click="exportKib(a)" />
                <TableAction
                  v-if="a.disposal_status !== 'disposed' && canDisposeAsset(a)"
                  kind="delete"
                  title="Hapus Aset"
                  @click="confirmDispose(a)"
                />
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!assets.length" class="empty-state">
        <h3>Belum ada aset individual</h3>
        <p>Buat master barang dengan tipe "Aset Individual" atau konversi stok existing.</p>
      </div>

      <PaginationBar
        embedded
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="aset"
        @page-change="load"
        @per-page-change="changePerPage"
      />
    </div>

    <Teleport to="body">
      <div v-if="detailVisible" class="inventory-slide-root slide-overlay" @click="closeDetail">
        <aside class="slide-panel" @click.stop>
          <div class="slide-panel-header">
            <h3>Detail Aset</h3>
            <button type="button" class="btn-close" @click="closeDetail">×</button>
          </div>
          <div v-if="detailLoading" class="loading-state"><p>Memuat...</p></div>
          <div v-else-if="detail" class="slide-panel-body">
            <div class="slide-panel-summary">
              <div class="name">{{ detail.asset_number }}</div>
              <div class="muted">{{ detail.item?.name }}</div>
            </div>
            <div class="detail-grid">
              <div><span class="muted">Nomor Inventaris</span><div>{{ detail.inventory_number || '-' }}</div></div>
              <div><span class="muted">Serial</span><div>{{ detail.serial_number || '-' }}</div></div>
              <div><span class="muted">Kondisi</span><div>{{ detail.condition }}</div></div>
              <div><span class="muted">Status</span><div>{{ detail.status }}</div></div>
              <div><span class="muted">Lokasi</span><div>{{ assetLocationLabel(detail) }}</div></div>
              <div><span class="muted">PJ</span><div>{{ detail.responsible_employee?.name || '-' }}</div></div>
              <div><span class="muted">Tgl Perolehan</span><div>{{ formatDate(detail.purchase_date) }}</div></div>
              <div><span class="muted">Nilai</span><div>{{ formatCurrency(detail.purchase_price) }}</div></div>
            </div>
            <div v-if="qrImage" class="qr-preview">
              <img :src="qrImage" alt="QR Aset" />
              <p class="muted small">Scan untuk identifikasi aset</p>
            </div>
            <div class="detail-actions">
              <button type="button" class="btn-secondary btn-compact" :disabled="exportingKib" @click="exportKib(detail)">
                {{ exportingKib ? 'Menyiapkan...' : 'Cetak KIB Unit' }}
              </button>
            </div>
          </div>
        </aside>
      </div>

    </Teleport>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryApi } from '@/api/inventory'
import { openPdfBlob } from '@/utils/pdfPreview'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { formatDate, formatCurrency, getConditionClass, getStatusClass } from '@/composables/inventory/inventoryFormatters'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'

defineProps({
  rooms: { type: Array, default: () => [] }
})

const toast = useToast()
const { showConfirm, setLoading: setDeleteLoading } = useConfirmDelete()
const assets = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const filters = ref({ search: '', status: '', room_id: '' })
let searchTimeout = null

const detailVisible = ref(false)
const detailLoading = ref(false)
const detail = ref(null)
const qrImage = ref('')
const printingQr = ref(false)
const printingQrId = ref(null)
const exportingKib = ref(false)

const DISPOSABLE_ASSET_STATUSES = new Set(['Tersedia', 'Rusak', 'Hilang'])

function canDisposeAsset(asset) {
  return DISPOSABLE_ASSET_STATUSES.has(asset?.status)
}

function disposalStatusForAsset(asset) {
  const status = asset?.status
  return status === 'Hilang' || status === 'Rusak' ? status : 'Rusak'
}

function assetLocationLabel(a) {
  if (a.room?.name) return a.room.name
  if (a.building?.name) return a.building.name
  return a.location_note || '-'
}

async function load(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: meta.value.per_page }
    Object.entries(filters.value).forEach(([k, v]) => {
      if (v) params[k] = v
    })
    const res = await inventoryApi.getAssets(params)
    assets.value = safeArray(res)
    meta.value = parsePagination(res, meta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat aset')
    assets.value = []
  } finally {
    loading.value = false
  }
}

function changePerPage(n) {
  meta.value.per_page = n
  meta.value.current_page = 1
  load(1)
}

function debounceLoad() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => load(1), 400)
}

async function openDetail(row) {
  detailVisible.value = true
  detailLoading.value = true
  qrImage.value = ''
  try {
    const res = await inventoryApi.getAsset(row.id)
    detail.value = res.data?.data ?? res.data ?? res
    const qrRes = await inventoryApi.getAssetQr(row.id)
    qrImage.value = qrRes.data?.data?.qr_code ?? qrRes.data?.qr_code ?? ''
  } catch {
    detail.value = row
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailVisible.value = false
  detail.value = null
}

async function printSingleQr(row) {
  printingQrId.value = row.id
  try {
    const response = await inventoryApi.printAssetQrPdf({ asset_ids: [row.id] })
    if (!openPdfBlob(response, `qr-aset-${row.asset_number || row.id}.pdf`)) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'Label QR dibuka')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal cetak label QR')
  } finally {
    printingQrId.value = null
  }
}

async function printBulkQr() {
  printingQr.value = true
  try {
    const params = {}
    if (filters.value.room_id) params.room_id = filters.value.room_id
    if (filters.value.item_id) params.item_id = filters.value.item_id
    const response = await inventoryApi.printAssetQrPdf(params)
    if (!openPdfBlob(response, 'label-qr-inventaris.pdf')) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'PDF label QR dibuka')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal cetak label QR')
  } finally {
    printingQr.value = false
  }
}

async function exportKib(asset) {
  exportingKib.value = true
  try {
    const response = await inventoryApi.exportAssetKib(asset.id)
    if (!openPdfBlob(response, `kib-aset-${asset.asset_number || asset.id}.pdf`)) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'KIB aset dibuka')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal cetak KIB')
  } finally {
    exportingKib.value = false
  }
}

async function confirmDispose(asset) {
  const reason = window.prompt(`Alasan penghapusan aset ${asset.asset_number}:`, '')
  if (reason === null) return
  if (!reason.trim()) {
    toast.error('Gagal', 'Alasan penghapusan wajib diisi')
    return
  }
  setDeleteLoading(true)
  try {
    await inventoryApi.disposeAsset(asset.id, {
      disposal_date: new Date().toISOString().split('T')[0],
      disposal_reason: reason.trim(),
      status: disposalStatusForAsset(asset)
    })
    toast.success('Berhasil', 'Penghapusan aset berhasil dicatat')
    await load(meta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal menghapus aset')
  } finally {
    setDeleteLoading(false)
  }
}

onMounted(() => load(1))

defineExpose({ refresh: load })
</script>

<style scoped>
.qr-preview {
  text-align: center;
  margin-top: 16px;
}
.qr-preview img {
  max-width: 200px;
  height: auto;
}
.modal-sm {
  max-width: 320px;
}
.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}
.detail-actions {
  margin-top: 16px;
}
.badge-muted {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  background: #f1f5f9;
  font-size: 12px;
}
</style>
