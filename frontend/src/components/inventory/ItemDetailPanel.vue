<template>
  <Teleport to="body">
    <div v-if="modelValue" class="item-detail-panel" @click="close">
      <aside class="idp-panel" @click.stop>
        <header class="idp-header">
          <div class="idp-header-top">
            <h3 class="idp-title">Detail Barang</h3>
            <button type="button" class="idp-close" aria-label="Tutup" @click="close">×</button>
          </div>
          <div v-if="item && !loading" class="idp-toolbar">
            <button
              v-if="canSplitToIndividual"
              type="button"
              class="idp-btn idp-btn-outline"
              :disabled="splitting"
              @click="openSplitModal"
            >
              {{ splitting ? 'Memproses...' : 'Konversi Aset' }}
            </button>
            <button
              type="button"
              class="idp-btn idp-btn-outline"
              :disabled="exportingKib"
              @click="exportKib"
            >
              {{ exportingKib ? 'Menyiapkan...' : 'Cetak KIB' }}
            </button>
          </div>
        </header>

        <div v-if="loading" class="idp-state"><p>Memuat detail...</p></div>
        <div v-else-if="!item" class="idp-state"><p>Barang tidak ditemukan.</p></div>

        <template v-else>
          <div class="idp-summary">
            <div class="idp-summary-name">{{ item.name }}</div>
            <div class="idp-summary-code">{{ item.code }}</div>
            <div class="idp-badges">
              <span :class="getConditionClass(item.condition)">{{ item.condition }}</span>
              <span :class="getStatusClass(item.status)">{{ item.status }}</span>
            </div>
          </div>

          <nav class="idp-tabs" role="tablist" aria-label="Bagian detail barang">
            <button
              v-for="t in visibleTabs"
              :key="t.id"
              type="button"
              role="tab"
              :class="['idp-tab', { active: activeTab === t.id }]"
              :aria-selected="activeTab === t.id"
              @click="activeTab = t.id"
            >
              {{ t.label }}
            </button>
          </nav>

          <div class="idp-body">
            <div v-show="activeTab === 'info'" class="idp-grid">
              <div class="idp-field">
                <span class="idp-label">Tipe Pelacakan</span>
                <span class="idp-value">{{ trackingTypeLabel(item.tracking_type) }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Kategori</span>
                <span class="idp-value">{{ item.category?.name || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Jumlah</span>
                <span class="idp-value">{{ item.quantity }} {{ item.unit || 'Unit' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Merk / Model</span>
                <span class="idp-value">{{ [item.brand, item.model].filter(Boolean).join(' ') || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Serial Number</span>
                <span class="idp-value">{{ item.serial_number || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Lokasi</span>
                <span class="idp-value">{{ itemLocationLabel(item) }}</span>
              </div>
              <div v-if="item.location_note" class="idp-field idp-field-full">
                <span class="idp-label">Catatan Lokasi</span>
                <span class="idp-value">{{ item.location_note }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Tanggal Beli</span>
                <span class="idp-value">{{ formatDate(item.purchase_date) }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Harga Beli</span>
                <span class="idp-value">{{ formatCurrency(item.purchase_price) }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Supplier</span>
                <span class="idp-value">{{ item.supplier || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Sumber Dana</span>
                <span class="idp-value">{{ item.funding_source || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Penanggung Jawab</span>
                <span class="idp-value">{{ item.responsible_employee?.name || '-' }}</span>
              </div>
              <div class="idp-field">
                <span class="idp-label">Garansi</span>
                <span class="idp-value">{{ formatDate(item.warranty_expiry) }}</span>
              </div>
              <template v-if="item.disposed_at">
                <div class="idp-field">
                  <span class="idp-label">Tgl Penghapusan</span>
                  <span class="idp-value">{{ formatDate(item.disposed_at) }}</span>
                </div>
                <div class="idp-field">
                  <span class="idp-label">No. SK / BA</span>
                  <span class="idp-value">{{ item.disposal_document_number || '-' }}</span>
                </div>
                <div class="idp-field idp-field-full">
                  <span class="idp-label">Alasan Penghapusan</span>
                  <span class="idp-value">{{ item.disposal_reason || '-' }}</span>
                </div>
              </template>
              <div v-if="item.description" class="idp-field idp-field-full">
                <span class="idp-label">Deskripsi</span>
                <span class="idp-value">{{ item.description }}</span>
              </div>
            </div>

            <div v-show="activeTab === 'assets'" class="idp-table-wrap">
              <table class="idp-table">
                <thead>
                  <tr>
                    <th class="idp-col-no">No</th>
                    <th>Nomor Aset</th>
                    <th>Serial</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                    <th>Lokasi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(a, index) in assets" :key="a.id">
                    <td class="idp-col-no">{{ index + 1 }}</td>
                    <td>{{ a.asset_number }}</td>
                    <td class="idp-muted">{{ a.serial_number || '-' }}</td>
                    <td><span :class="getConditionClass(a.condition)">{{ a.condition }}</span></td>
                    <td><span :class="getStatusClass(a.status)">{{ a.status }}</span></td>
                    <td class="idp-muted">{{ a.room?.name || a.building?.name || '-' }}</td>
                  </tr>
                  <tr v-if="!assets.length">
                    <td colspan="6" class="idp-empty">Belum ada aset</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-show="activeTab === 'transactions'" class="idp-table-wrap">
              <table class="idp-table">
                <thead>
                  <tr>
                    <th class="idp-col-no">No</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Qty</th>
                    <th>Lokasi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(tr, index) in transactions" :key="tr.id">
                    <td class="idp-col-no">{{ index + 1 }}</td>
                    <td>{{ formatDate(tr.transaction_date) }}</td>
                    <td><span :class="getTransactionTypeClass(tr.transaction_type)">{{ tr.transaction_type }}</span></td>
                    <td>{{ tr.quantity }}</td>
                    <td class="idp-muted">
                      <span v-if="tr.from_location?.name">Dari: {{ tr.from_location.name }}</span>
                      <span v-if="tr.to_location?.name"> → Ke: {{ tr.to_location.name }}</span>
                      <span v-if="!tr.from_location && !tr.to_location">-</span>
                    </td>
                  </tr>
                  <tr v-if="!transactions.length">
                    <td colspan="5" class="idp-empty">Belum ada mutasi</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-show="activeTab === 'maintenances'" class="idp-table-wrap">
              <table class="idp-table">
                <thead>
                  <tr>
                    <th class="idp-col-no">No</th>
                    <th>Jenis</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th>Biaya</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(m, index) in maintenances" :key="m.id">
                    <td class="idp-col-no">{{ index + 1 }}</td>
                    <td>{{ m.maintenance_type }}</td>
                    <td>{{ formatDate(m.scheduled_date) }}</td>
                    <td><span :class="getMaintenanceStatusClass(m.status)">{{ m.status }}</span></td>
                    <td>{{ m.cost != null ? formatCurrency(m.cost) : '-' }}</td>
                  </tr>
                  <tr v-if="!maintenances.length">
                    <td colspan="5" class="idp-empty">Belum ada pemeliharaan</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-show="activeTab === 'loans'" class="idp-table-wrap">
              <table class="idp-table idp-table-wide">
                <thead>
                  <tr>
                    <th class="idp-col-no">No</th>
                    <th>Peminjam</th>
                    <th>Unit</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(ln, index) in loans" :key="ln.id">
                    <td class="idp-col-no">{{ index + 1 }}</td>
                    <td>{{ ln.borrower_name }}</td>
                    <td class="idp-muted">{{ ln.asset?.asset_number || '-' }}</td>
                    <td>{{ formatDate(ln.loan_date) }}</td>
                    <td>{{ formatDate(ln.expected_return_date) }}</td>
                    <td>{{ ln.status === 'Dikembalikan' ? formatDate(ln.actual_return_date) : '-' }}</td>
                    <td>{{ ln.status }}</td>
                  </tr>
                  <tr v-if="!loans.length">
                    <td colspan="7" class="idp-empty">Belum ada peminjaman</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>
      </aside>
    </div>

    <div v-if="showSplitModal" class="idp-modal-overlay" @click="showSplitModal = false">
      <div class="idp-modal" @click.stop>
        <div class="idp-modal-header">
          <h3>Konversi ke Aset Individual</h3>
          <button type="button" class="idp-close" @click="showSplitModal = false">×</button>
        </div>
        <div class="idp-modal-body">
          <p class="idp-modal-text">
            Seluruh stok <strong>{{ item?.quantity }} {{ item?.unit || 'unit' }}</strong> akan dipecah menjadi
            aset individual terpisah. Master barang tetap ada, tetapi tidak bisa diubah kuantitasnya lagi.
          </p>
          <div v-if="splitError" class="idp-error">{{ splitError }}</div>
          <div class="idp-modal-footer">
            <button type="button" class="idp-btn idp-btn-muted" @click="showSplitModal = false">Batal</button>
            <button type="button" class="idp-btn idp-btn-primary" :disabled="splitting" @click="confirmSplit">
              {{ splitting ? 'Memproses...' : 'Konversi Semua Unit' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed, onUnmounted } from 'vue'
import {
  formatDate,
  formatCurrency,
  getConditionClass,
  getStatusClass,
  getTransactionTypeClass,
  getMaintenanceStatusClass,
  itemLocationLabel,
} from '@/composables/inventory/inventoryFormatters'
import { trackingTypeLabel } from '@/composables/inventory/inventoryConstants'
import { inventoryApi } from '@/api/inventory'
import { openPdfBlob } from '@/utils/pdfPreview'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  itemId: { type: [Number, String], default: null }
})

const emit = defineEmits(['update:modelValue', 'close'])

const toast = useToast()
const loading = ref(false)
const item = ref(null)
const transactions = ref([])
const maintenances = ref([])
const loans = ref([])
const assets = ref([])
const activeTab = ref('info')
const exportingKib = ref(false)
const splitting = ref(false)
const showSplitModal = ref(false)
const splitError = ref('')

const canSplitToIndividual = computed(() =>
  item.value?.tracking_type === 'stock'
  && !item.value?.disposed_at
  && (item.value?.quantity ?? 0) > 0
)

const visibleTabs = computed(() => {
  const isIndividual = item.value?.tracking_type === 'individual'
  return tabs.filter((t) => {
    if (t.individualOnly && !isIndividual) return false
    if (t.stockOnly && isIndividual) return false
    return true
  })
})

const tabs = [
  { id: 'info', label: 'Info' },
  { id: 'assets', label: 'Aset', individualOnly: true },
  { id: 'transactions', label: 'Mutasi', stockOnly: true },
  { id: 'maintenances', label: 'Pemeliharaan' },
  { id: 'loans', label: 'Peminjaman' }
]

function setBodyScrollLocked(locked) {
  document.body.style.overflow = locked ? 'hidden' : ''
}

async function exportKib() {
  if (!props.itemId) return
  exportingKib.value = true
  try {
    const response = await inventoryApi.exportKib(props.itemId)
    const contentType = response.headers?.['content-type'] || ''
    if (response.status !== 200 || contentType.includes('application/json')) {
      throw new Error('Gagal mencetak KIB.')
    }
    const blob = response.data instanceof Blob
      ? response.data
      : new Blob([response.data], { type: 'application/pdf' })
    const label = item.value?.code || props.itemId
    if (openPdfBlob(blob, `KIB-${label}.pdf`)) {
      toast.success('Berhasil', 'Preview KIB dibuka.')
    } else {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
    }
  } catch (err) {
    toast.error('Gagal', err.message || err.formattedMessage || err.response?.data?.message || 'Gagal mencetak KIB.')
  } finally {
    exportingKib.value = false
  }
}

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function openSplitModal() {
  splitError.value = ''
  showSplitModal.value = true
}

async function confirmSplit() {
  if (!props.itemId || !item.value) return
  splitting.value = true
  splitError.value = ''
  try {
    await inventoryApi.splitItemAssets(props.itemId, item.value.quantity)
    toast.success('Berhasil', `${item.value.quantity} aset individual berhasil dibuat`)
    showSplitModal.value = false
    await loadItem()
    activeTab.value = 'assets'
  } catch (err) {
    splitError.value = err.formattedMessage || err.response?.data?.message || 'Gagal konversi aset'
  } finally {
    splitting.value = false
  }
}

async function loadItem() {
  if (!props.itemId) {
    item.value = null
    return
  }
  loading.value = true
  activeTab.value = 'info'
  try {
    const res = await inventoryApi.getItem(props.itemId)
    const data = res.data?.data ?? res.data ?? null
    item.value = data
    transactions.value = data?.transactions || []
    maintenances.value = data?.maintenances || []
    loans.value = data?.loans || []
    assets.value = data?.assets || []
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat detail barang')
    item.value = null
    transactions.value = []
    maintenances.value = []
    loans.value = []
    assets.value = []
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.modelValue, props.itemId],
  ([visible, id]) => {
    setBodyScrollLocked(visible)
    if (visible && id) loadItem()
    if (!visible) {
      showSplitModal.value = false
      splitError.value = ''
    }
  },
  { immediate: true }
)

onUnmounted(() => setBodyScrollLocked(false))
</script>

<style scoped>
.item-detail-panel {
  position: fixed;
  inset: 0;
  z-index: 11000;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: flex-end;
}

.idp-panel {
  width: min(560px, 100%);
  height: 100%;
  background: #fff;
  box-shadow: -8px 0 32px rgba(15, 23, 42, 0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: idpSlideIn 0.26s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes idpSlideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.idp-header {
  flex-shrink: 0;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  background: #fff;
}

.idp-header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.idp-title {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
}

.idp-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.idp-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f1f5f9;
  color: #64748b;
  font-size: 1.35rem;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}

.idp-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.idp-btn {
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.idp-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.idp-btn-outline {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.idp-btn-outline:hover:not(:disabled) {
  background: #d1fae5;
}

.idp-btn-muted {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.idp-btn-muted:hover:not(:disabled) {
  background: #e2e8f0;
}

.idp-btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}

.idp-btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.idp-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #64748b;
  font-size: 0.9rem;
}

.idp-summary {
  flex-shrink: 0;
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}

.idp-summary-name {
  font-weight: 700;
  font-size: 1rem;
  color: #0f172a;
  line-height: 1.35;
}

.idp-summary-code {
  margin-top: 2px;
  font-size: 0.85rem;
  color: #64748b;
}

.idp-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.idp-tabs {
  flex-shrink: 0;
  display: flex;
  gap: 6px;
  padding: 12px 20px;
  border-bottom: 1px solid #f1f5f9;
  overflow-x: auto;
  scrollbar-width: none;
  background: #fff;
}

.idp-tabs::-webkit-scrollbar {
  display: none;
}

.idp-tab {
  padding: 0.4rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  font-family: inherit;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.idp-tab:hover {
  background: #fff;
  color: #0f172a;
  border-color: #cbd5e1;
}

.idp-tab.active {
  background: #ecfdf5;
  color: #047857;
  border-color: #6ee7b7;
}

.idp-body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 16px 20px 24px;
  -webkit-overflow-scrolling: touch;
}

.idp-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.idp-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  min-width: 0;
}

.idp-field-full {
  grid-column: 1 / -1;
}

.idp-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
}

.idp-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0f172a;
  line-height: 1.45;
  word-break: break-word;
}

.idp-table-wrap {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  -webkit-overflow-scrolling: touch;
}

.idp-table {
  width: 100%;
  min-width: 420px;
  border-collapse: collapse;
}

.idp-table-wide {
  min-width: 640px;
}

.idp-table th,
.idp-table td {
  padding: 0.55rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.8125rem;
  vertical-align: top;
}

.idp-table th {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  font-weight: 600;
  color: #065f46;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  white-space: nowrap;
}

.idp-table tbody tr:hover {
  background: #f8fafc;
}

.idp-col-no {
  width: 2.75rem;
  text-align: center;
  color: #94a3b8;
  font-variant-numeric: tabular-nums;
}

.idp-muted {
  color: #64748b;
}

.idp-empty {
  text-align: center;
  color: #94a3b8;
  padding: 1.25rem !important;
}

.idp-badges .badge-success,
.idp-table .badge-success { background: #dcfce7; color: #166534; }
.idp-badges .badge-warning,
.idp-table .badge-warning { background: #fef3c7; color: #92400e; }
.idp-badges .badge-danger,
.idp-table .badge-danger { background: #fee2e2; color: #b91c1c; }
.idp-badges .badge-info,
.idp-table .badge-info { background: #dbeafe; color: #1e40af; }
.idp-badges .badge-gray,
.idp-table .badge-gray { background: #f1f5f9; color: #475569; }

.idp-badges .badge-success,
.idp-badges .badge-warning,
.idp-badges .badge-danger,
.idp-badges .badge-info,
.idp-badges .badge-gray,
.idp-table .badge-success,
.idp-table .badge-warning,
.idp-table .badge-danger,
.idp-table .badge-info,
.idp-table .badge-gray {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.idp-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 11001;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.idp-modal {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
}

.idp-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.idp-modal-header h3 {
  margin: 0;
  font-size: 1.05rem;
  color: #1e293b;
}

.idp-modal-body {
  padding: 1rem 1.25rem 1.25rem;
}

.idp-modal-text {
  margin: 0;
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.5;
}

.idp-error {
  margin-top: 0.75rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 10px;
  border: 1px solid #fecaca;
  font-size: 0.875rem;
}

.idp-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

@media (max-width: 480px) {
  .idp-grid {
    grid-template-columns: 1fr;
  }

  .idp-header,
  .idp-summary,
  .idp-tabs,
  .idp-body {
    padding-left: 16px;
    padding-right: 16px;
  }
}
</style>
