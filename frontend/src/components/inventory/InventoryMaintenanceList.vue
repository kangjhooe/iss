<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <select v-model="filters.item_id" class="filter-select" @change="loadMaintenances(1)">
          <option value="">Semua Barang</option>
          <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
        </select>
        <select v-model="filters.maintenance_type" class="filter-select" @change="loadMaintenances(1)">
          <option value="">Semua Jenis</option>
          <option value="Perawatan">Perawatan</option>
          <option value="Perbaikan">Perbaikan</option>
          <option value="Kalibrasi">Kalibrasi</option>
          <option value="Inspeksi">Inspeksi</option>
        </select>
        <select v-model="filters.status" class="filter-select" @change="loadMaintenances(1)">
          <option value="">Semua Status</option>
          <option value="Terjadwal">Terjadwal</option>
          <option value="Dalam Proses">Dalam Proses</option>
          <option value="Selesai">Selesai</option>
          <option value="Dibatalkan">Dibatalkan</option>
        </select>
      </div>
      <button type="button" class="btn-primary" @click="openMaintenanceModal()">
        <span>Tambah Pemeliharaan</span>
      </button>
    </div>

    <div v-if="maintenancesLoading" class="loading-state"><p>Memuat data...</p></div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Barang</th>
            <th>Unit Aset</th>
            <th>Jenis</th>
            <th>Tanggal Jadwal</th>
            <th>Tanggal Selesai</th>
            <th>Status</th>
            <th>Biaya</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, index) in maintenances" :key="m.id">
            <td class="col-no">{{ inventoryRowNumber(maintenancesMeta, index) }}</td>
            <td>
              <div class="name-cell">
                <div class="name">{{ m.item?.name || '-' }}</div>
                <div class="muted small">{{ m.item?.code || '-' }}</div>
              </div>
            </td>
            <td class="muted">{{ m.asset?.asset_number || '-' }}</td>
            <td>{{ m.maintenance_type }}</td>
            <td>{{ formatDate(m.scheduled_date) }}</td>
            <td>{{ formatDate(m.completed_date) || '-' }}</td>
            <td><span :class="getMaintenanceStatusClass(m.status)">{{ m.status }}</span></td>
            <td>{{ m.cost != null ? formatCurrency(m.cost) : '-' }}</td>
            <td>
              <div class="action-buttons">
                <TableAction kind="edit" @click="openMaintenanceModal(m)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="maintenances.length === 0" class="empty-state">
        <h3>Belum ada data pemeliharaan</h3>
        <p>Catat jadwal perawatan, perbaikan, kalibrasi, atau inspeksi.</p>
        <button type="button" class="btn-primary" @click="openMaintenanceModal()">Tambah Pemeliharaan</button>
      </div>

      <PaginationBar
        embedded
        :page="maintenancesMeta.current_page"
        :last-page="maintenancesMeta.last_page"
        :per-page="maintenancesMeta.per_page"
        :total="maintenancesMeta.total"
        item-label="data"
        @page-change="loadMaintenances"
        @per-page-change="changeMaintenancesPerPage"
      />
    </div>

    <div v-if="showMaintenanceModal" class="modal-overlay" @click="closeMaintenanceModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingMaintenance ? 'Edit Pemeliharaan' : 'Tambah Pemeliharaan' }}</h3>
          <button type="button" class="btn-close" @click="closeMaintenanceModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="saveMaintenance">
          <div class="form-group">
            <label>Barang *</label>
            <select v-model="maintenanceForm.item_id" required :disabled="!!editingMaintenance" @change="onMaintenanceItemChange">
              <option value="">Pilih Barang</option>
              <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
            </select>
          </div>
          <div v-if="selectedMaintenanceItem?.tracking_type === 'individual'" class="form-group">
            <label>Unit Aset *</label>
            <select v-model="maintenanceForm.asset_id" required :disabled="!!editingMaintenance">
              <option value="">Pilih unit aset</option>
              <option v-for="a in maintenanceAssetOptions" :key="a.id" :value="a.id">
                {{ a.asset_number }} — {{ a.condition }} / {{ a.status }}
              </option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Jenis Pemeliharaan *</label>
              <select v-model="maintenanceForm.maintenance_type" required>
                <option value="Perawatan">Perawatan</option>
                <option value="Perbaikan">Perbaikan</option>
                <option value="Kalibrasi">Kalibrasi</option>
                <option value="Inspeksi">Inspeksi</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="maintenanceForm.status">
                <option value="Terjadwal">Terjadwal</option>
                <option value="Dalam Proses">Dalam Proses</option>
                <option value="Selesai">Selesai</option>
                <option value="Dibatalkan">Dibatalkan</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tanggal Jadwal *</label>
              <input v-model="maintenanceForm.scheduled_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Tanggal Selesai</label>
              <input v-model="maintenanceForm.completed_date" type="date" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Biaya (Rp)</label>
              <input v-model.number="maintenanceForm.cost" type="number" min="0" step="0.01" />
            </div>
            <div class="form-group">
              <label>Vendor / Teknisi</label>
              <input v-model="maintenanceForm.vendor" />
            </div>
          </div>
          <div class="form-group">
            <label>Nama Teknisi</label>
            <input v-model="maintenanceForm.technician_name" />
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea v-model="maintenanceForm.description" rows="2" />
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="maintenanceForm.notes" rows="2" />
          </div>
          <div v-if="maintenanceError" class="error-message">{{ maintenanceError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeMaintenanceModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="maintenanceSaving">
              {{ maintenanceSaving ? 'Menyimpan...' : 'Simpan' }}
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
  formatCurrency,
  getMaintenanceStatusClass
} from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const maintenances = ref([])
const maintenancesLoading = ref(false)
const maintenancesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const filters = ref({ item_id: '', maintenance_type: '', status: '' })
const itemOptions = ref([])
const maintenanceAssetOptions = ref([])

const selectedMaintenanceItem = computed(() =>
  itemOptions.value.find((i) => String(i.id) === String(maintenanceForm.value.item_id)) || null
)

const showMaintenanceModal = ref(false)
const editingMaintenance = ref(null)
const maintenanceSaving = ref(false)
const maintenanceError = ref('')
const maintenanceForm = ref(emptyMaintenanceForm())

function emptyMaintenanceForm() {
  return {
    item_id: '',
    asset_id: '',
    maintenance_type: 'Perawatan',
    scheduled_date: new Date().toISOString().split('T')[0],
    completed_date: '',
    cost: null,
    vendor: '',
    description: '',
    status: 'Terjadwal',
    technician_name: '',
    notes: ''
  }
}

async function loadItemOptions() {
  try {
    const res = await inventoryApi.getItems({ per_page: 200 })
    itemOptions.value = safeArray(res)
  } catch {
    itemOptions.value = []
  }
}

async function loadMaintenances(page = 1) {
  maintenancesLoading.value = true
  try {
    const params = { page, per_page: maintenancesMeta.value.per_page }
    if (filters.value.item_id) params.item_id = filters.value.item_id
    if (filters.value.maintenance_type) params.maintenance_type = filters.value.maintenance_type
    if (filters.value.status) params.status = filters.value.status
    const res = await inventoryApi.getMaintenances(params)
    maintenances.value = safeArray(res)
    maintenancesMeta.value = parsePagination(res, maintenancesMeta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat pemeliharaan')
    maintenances.value = []
  } finally {
    maintenancesLoading.value = false
  }
}

function changeMaintenancesPerPage(n) {
  maintenancesMeta.value.per_page = n
  maintenancesMeta.value.current_page = 1
  loadMaintenances(1)
}

async function loadMaintenanceAssetOptions(itemId) {
  if (!itemId) {
    maintenanceAssetOptions.value = []
    return
  }
  try {
    const res = await inventoryApi.getAssets({ item_id: itemId, per_page: 100 })
    maintenanceAssetOptions.value = safeArray(res)
  } catch {
    maintenanceAssetOptions.value = []
  }
}

async function onMaintenanceItemChange() {
  maintenanceForm.value.asset_id = ''
  const it = selectedMaintenanceItem.value
  if (it?.tracking_type === 'individual') {
    await loadMaintenanceAssetOptions(it.id)
  } else {
    maintenanceAssetOptions.value = []
  }
}

async function openMaintenanceModal(m = null) {
  if (itemOptions.value.length === 0) await loadItemOptions()
  editingMaintenance.value = m
  maintenanceError.value = ''
  if (m) {
    maintenanceForm.value = {
      item_id: m.item_id || m.item?.id,
      asset_id: m.asset_id || m.asset?.id || '',
      maintenance_type: m.maintenance_type || 'Perawatan',
      scheduled_date: m.scheduled_date ? String(m.scheduled_date).split('T')[0] : '',
      completed_date: m.completed_date ? String(m.completed_date).split('T')[0] : '',
      cost: m.cost ?? null,
      vendor: m.vendor || '',
      description: m.description || '',
      status: m.status || 'Terjadwal',
      technician_name: m.technician_name || '',
      notes: m.notes || ''
    }
    await onMaintenanceItemChange()
    if (m.asset_id) maintenanceForm.value.asset_id = m.asset_id
  } else {
    maintenanceForm.value = emptyMaintenanceForm()
  }
  showMaintenanceModal.value = true
}

function closeMaintenanceModal() {
  showMaintenanceModal.value = false
  editingMaintenance.value = null
  maintenanceError.value = ''
}

async function markItemRusakIfNeeded(itemId, assetId) {
  const type = maintenanceForm.value.maintenance_type
  const status = maintenanceForm.value.status
  if (type !== 'Perbaikan' && status !== 'Dalam Proses') return

  const item = itemOptions.value.find((i) => String(i.id) === String(itemId))
  if (!item) return

  try {
    if (item.tracking_type === 'individual' && assetId) {
      const asset = maintenanceAssetOptions.value.find((a) => String(a.id) === String(assetId))
      await inventoryApi.updateAsset(assetId, {
        condition: asset?.condition === 'Baik' ? 'Rusak Ringan' : (asset?.condition || 'Rusak Ringan'),
        status: 'Rusak'
      })
    } else {
      await inventoryApi.updateItem(item.id, {
        name: item.name,
        condition: item.condition === 'Baik' ? 'Rusak Ringan' : item.condition,
        status: 'Rusak',
        room_id: item.room_id || item.room?.id || null
      })
    }
  } catch {
    // non-blocking; maintenance already saved
  }
}

async function saveMaintenance() {
  maintenanceSaving.value = true
  maintenanceError.value = ''
  try {
    const payload = {
      item_id: maintenanceForm.value.item_id,
      maintenance_type: maintenanceForm.value.maintenance_type,
      scheduled_date: maintenanceForm.value.scheduled_date,
      completed_date: maintenanceForm.value.completed_date || null,
      cost: maintenanceForm.value.cost ?? null,
      vendor: maintenanceForm.value.vendor?.trim() || null,
      description: maintenanceForm.value.description?.trim() || null,
      status: maintenanceForm.value.status,
      technician_name: maintenanceForm.value.technician_name?.trim() || null,
      notes: maintenanceForm.value.notes?.trim() || null
    }
    if (maintenanceForm.value.asset_id) payload.asset_id = maintenanceForm.value.asset_id
    if (editingMaintenance.value) {
      await inventoryApi.updateMaintenance(editingMaintenance.value.id, payload)
      toast.success('Berhasil', 'Pemeliharaan berhasil diperbarui')
    } else {
      await inventoryApi.createMaintenance(payload)
      await markItemRusakIfNeeded(payload.item_id, payload.asset_id)
      toast.success('Berhasil', 'Pemeliharaan berhasil ditambahkan')
    }
    closeMaintenanceModal()
    await loadMaintenances(maintenancesMeta.value.current_page || 1)
    await loadItemOptions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan pemeliharaan'
    maintenanceError.value = msg
    toast.error('Gagal', msg)
  } finally {
    maintenanceSaving.value = false
  }
}

onMounted(async () => {
  await loadItemOptions()
  await loadMaintenances(1)
})
</script>
