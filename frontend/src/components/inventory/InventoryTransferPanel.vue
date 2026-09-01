<template>
  <div class="tab-content">
    <div class="sub-nav">
      <button
        v-for="t in subTabs"
        :key="t.type"
        type="button"
        :class="['sub-nav-btn', { active: activeSubTab === t.type }]"
        @click="setSubTab(t.type)"
      >
        {{ t.label }}
      </button>
    </div>

    <form v-if="activeSubTab !== 'MutasiAset'" class="transfer-form" @submit.prevent="saveTransaction">
      <div class="form-group">
        <label>Barang *</label>
        <select v-model="form.item_id" required>
          <option value="">Pilih Barang</option>
          <option v-for="it in itemOptions" :key="it.id" :value="it.id">
            {{ it.code }} - {{ it.name }}
          </option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tanggal *</label>
          <input v-model="form.transaction_date" type="date" required />
        </div>
        <div v-if="activeSubTab !== 'Mutasi'" class="form-group">
          <label>Jumlah *</label>
          <input v-model.number="form.quantity" type="number" min="1" required />
        </div>
        <div v-else class="form-group">
          <label>Jumlah *</label>
          <input v-model.number="form.quantity" type="number" min="1" required />
        </div>
      </div>

      <div v-if="activeSubTab === 'Mutasi'" class="form-row">
        <div class="form-group">
          <label>Dari Ruangan</label>
          <select v-model="form.from_location_id">
            <option value="">(Opsional)</option>
            <option v-for="r in rooms" :key="'from-' + r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>Ke Ruangan *</label>
          <select v-model="form.to_location_id" required>
            <option value="">Pilih Ruangan</option>
            <option v-for="r in rooms" :key="'to-' + r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
      </div>

      <div v-else-if="activeSubTab === 'Masuk'" class="form-row">
        <div class="form-group">
          <label>Ke Ruangan</label>
          <select v-model="form.to_location_id">
            <option value="">(Opsional)</option>
            <option v-for="r in rooms" :key="'in-' + r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>No. Referensi</label>
          <input v-model="form.reference_number" />
        </div>
      </div>

      <div v-else class="form-row">
        <div class="form-group">
          <label>Dari Ruangan</label>
          <select v-model="form.from_location_id">
            <option value="">(Opsional)</option>
            <option v-for="r in rooms" :key="'out-' + r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>No. Referensi</label>
          <input v-model="form.reference_number" />
        </div>
      </div>

      <div class="form-group">
        <label>Catatan</label>
        <textarea v-model="form.notes" rows="3" />
      </div>

      <div v-if="error" class="error-message">{{ error }}</div>

      <div class="form-actions">
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : 'Simpan Transaksi' }}
        </button>
      </div>
    </form>

    <form v-else class="transfer-form" @submit.prevent="saveAssetTransfer">
      <div class="form-group">
        <label>Unit Aset *</label>
        <select v-model="assetForm.asset_id" required @change="onAssetSelect">
          <option value="">Pilih aset</option>
          <option v-for="a in assetOptions" :key="a.id" :value="a.id">
            {{ a.asset_number }} — {{ a.item?.name }} ({{ a.room?.name || 'tanpa lokasi' }})
          </option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tanggal *</label>
          <input v-model="assetForm.movement_date" type="date" required />
        </div>
        <div class="form-group">
          <label>No. Referensi</label>
          <input v-model="assetForm.reference_number" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Dari Ruangan</label>
          <input type="text" readonly :value="selectedAsset?.room?.name || '-'" class="readonly-input" />
        </div>
        <div class="form-group">
          <label>Ke Ruangan *</label>
          <select v-model="assetForm.to_room_id" required>
            <option value="">Pilih Ruangan</option>
            <option v-for="r in rooms" :key="'asset-to-' + r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Catatan</label>
        <textarea v-model="assetForm.notes" rows="2" />
      </div>
      <div v-if="error" class="error-message">{{ error }}</div>
      <div class="form-actions">
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Menyimpan...' : 'Simpan Mutasi Aset' }}
        </button>
      </div>
    </form>

    <h3 class="report-section-title" style="margin-top: 24px;">
      {{ activeSubTab === 'MutasiAset' ? 'Riwayat Mutasi Aset Terbaru' : 'Riwayat Mutasi Terbaru' }}
    </h3>
    <div v-if="listLoading" class="loading-state"><p>Memuat transaksi...</p></div>
    <div v-else class="table-container">
      <table v-if="activeSubTab === 'MutasiAset'" class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Tanggal</th>
            <th>Aset</th>
            <th>Barang</th>
            <th>Dari → Ke</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(mv, index) in recentMovements" :key="mv.id">
            <td class="col-no">{{ index + 1 }}</td>
            <td>{{ formatDate(mv.movement_date) }}</td>
            <td>{{ mv.asset?.asset_number || '-' }}</td>
            <td class="muted">{{ mv.item?.name || '-' }}</td>
            <td class="muted">{{ mv.from_room?.name || '-' }} → {{ mv.to_room?.name || '-' }}</td>
          </tr>
          <tr v-if="!recentMovements.length">
            <td colspan="5" class="empty-cell">Belum ada mutasi aset</td>
          </tr>
        </tbody>
      </table>
      <table v-else class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Barang</th>
            <th>Qty</th>
            <th>Lokasi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tr, index) in recentTransactions" :key="tr.id">
            <td class="col-no">{{ index + 1 }}</td>
            <td>{{ formatDate(tr.transaction_date) }}</td>
            <td><span :class="getTransactionTypeClass(tr.transaction_type)">{{ tr.transaction_type }}</span></td>
            <td>
              <div class="name-cell">
                <div class="name">{{ tr.item?.name || '-' }}</div>
                <div class="muted small">{{ tr.item?.code || '-' }}</div>
              </div>
            </td>
            <td>{{ tr.quantity }}</td>
            <td class="muted">
              <span v-if="tr.from_location?.name">Dari: {{ tr.from_location.name }}</span>
              <span v-if="tr.to_location?.name"> → Ke: {{ tr.to_location.name }}</span>
            </td>
          </tr>
          <tr v-if="!recentTransactions.length">
            <td colspan="6" class="empty-cell">Belum ada transaksi</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { inventoryApi } from '@/api/inventory'
import { formatDate, getTransactionTypeClass } from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  rooms: { type: Array, default: () => [] },
  preselectedItem: { type: Object, default: null }
})

const toast = useToast()

    const subTabs = [
  { type: 'Mutasi', label: 'Mutasi Stok' },
  { type: 'MutasiAset', label: 'Mutasi Aset' },
  { type: 'Masuk', label: 'Barang Masuk' },
  { type: 'Keluar', label: 'Barang Keluar' },
  { type: 'Penyesuaian', label: 'Penyesuaian Stok' }
]

const activeSubTab = ref('Mutasi')
const itemOptions = ref([])
const assetOptions = ref([])
const recentTransactions = ref([])
const recentMovements = ref([])
const listLoading = ref(false)
const saving = ref(false)
const error = ref('')

const form = ref({
  item_id: '',
  transaction_type: 'Mutasi',
  transaction_date: new Date().toISOString().split('T')[0],
  quantity: 1,
  reference_number: '',
  from_location_id: '',
  to_location_id: '',
  notes: ''
})

const assetForm = ref({
  asset_id: '',
  movement_date: new Date().toISOString().split('T')[0],
  to_room_id: '',
  reference_number: '',
  notes: ''
})

const selectedAsset = computed(() =>
  assetOptions.value.find((a) => String(a.id) === String(assetForm.value.asset_id)) || null
)

function setSubTab(type) {
  activeSubTab.value = type
  if (type !== 'MutasiAset') {
    form.value.transaction_type = type
  }
  if (type === 'MutasiAset') {
    loadAssetOptions()
    loadRecentMovements()
  } else {
    loadRecentTransactions()
  }
}

async function loadItemOptions() {
  try {
    const res = await inventoryApi.getItems({ per_page: 200, tracking_type: 'stock' })
    itemOptions.value = safeArray(res)
  } catch {
    itemOptions.value = []
  }
}

async function loadAssetOptions() {
  try {
    const res = await inventoryApi.getAssets({ per_page: 200, status: 'Tersedia' })
    assetOptions.value = safeArray(res)
  } catch {
    assetOptions.value = []
  }
}

async function loadRecentMovements() {
  listLoading.value = true
  try {
    const res = await inventoryApi.getAssetMovements({ page: 1, per_page: 10 })
    recentMovements.value = safeArray(res)
  } catch {
    recentMovements.value = []
  } finally {
    listLoading.value = false
  }
}

function onAssetSelect() {
  // readonly from field auto from selectedAsset
}

async function saveAssetTransfer() {
  saving.value = true
  error.value = ''
  try {
    await inventoryApi.transferAsset(assetForm.value.asset_id, {
      to_room_id: assetForm.value.to_room_id,
      movement_date: assetForm.value.movement_date,
      reference_number: assetForm.value.reference_number?.trim() || null,
      notes: assetForm.value.notes?.trim() || null
    })
    toast.success('Berhasil', 'Mutasi aset berhasil dicatat')
    assetForm.value = {
      asset_id: '',
      movement_date: new Date().toISOString().split('T')[0],
      to_room_id: '',
      reference_number: '',
      notes: ''
    }
    await Promise.all([loadAssetOptions(), loadRecentMovements()])
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal mutasi aset'
    error.value = msg
    toast.error('Gagal', msg)
  } finally {
    saving.value = false
  }
}

async function loadRecentTransactions() {
  listLoading.value = true
  try {
    const res = await inventoryApi.getTransactions({ page: 1, per_page: 10, transaction_type: 'Mutasi' })
    recentTransactions.value = safeArray(res)
    parsePagination(res)
  } catch {
    recentTransactions.value = []
  } finally {
    listLoading.value = false
  }
}

function applyItemRoom(itemId) {
  if (!itemId) return
  const it = itemOptions.value.find((i) => String(i.id) === String(itemId))
  if (it?.room_id || it?.room?.id) {
    form.value.from_location_id = it.room_id || it.room?.id || ''
  }
}

watch(
  () => form.value.item_id,
  (id) => applyItemRoom(id)
)

watch(
  () => props.preselectedItem,
  (item) => {
    if (item?.id) {
      form.value.item_id = item.id
      applyItemRoom(item.id)
    }
  },
  { immediate: true }
)

async function saveTransaction() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      item_id: form.value.item_id,
      transaction_type: activeSubTab.value,
      transaction_date: form.value.transaction_date,
      quantity: form.value.quantity,
      reference_number: form.value.reference_number ? form.value.reference_number.trim() : null,
      from_location_id: form.value.from_location_id || null,
      to_location_id: form.value.to_location_id || null,
      notes: form.value.notes ? form.value.notes.trim() : null
    }
    await inventoryApi.createTransaction(payload)
    toast.success('Berhasil', 'Transaksi berhasil dicatat')
    form.value.reference_number = ''
    form.value.notes = ''
    await loadRecentTransactions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan transaksi'
    error.value = msg
    toast.error('Gagal', msg)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadItemOptions()
  if (props.preselectedItem?.id) {
    form.value.item_id = props.preselectedItem.id
    applyItemRoom(props.preselectedItem.id)
  }
  await loadRecentTransactions()
})

defineExpose({ refresh: loadRecentTransactions })
</script>

<style scoped>
.transfer-form {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 8px;
}
.readonly-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  font-size: 14px;
}
</style>
