<template>
  <div class="tab-content">
    <div class="list-mode-tabs">
      <button
        type="button"
        :class="['sub-nav-btn', { active: !filters.only_trashed }]"
        @click="setTrashMode(false)"
      >
        Daftar Barang
      </button>
      <button
        type="button"
        :class="['sub-nav-btn', { active: filters.only_trashed }]"
        @click="setTrashMode(true)"
      >
        Kotak Sampah
      </button>
    </div>

    <div class="tab-header">
      <div class="filters-toolbar">
        <div class="filters filters-inline">
          <div class="search-wrap">
            <input
              v-model="filters.search"
              class="search-input"
              placeholder="Cari kode, nama, merk, model, serial..."
              @input="debounceLoadItems"
            />
            <button
              v-if="filters.search"
              type="button"
              class="search-clear"
              aria-label="Hapus pencarian"
              @click="filters.search = ''; loadItems(1)"
            >
              ×
            </button>
          </div>
          <select v-model="filters.category_id" class="filter-select" @change="loadItems(1)">
            <option value="">Semua Kategori</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.code }} - {{ cat.name }}
            </option>
          </select>
          <select v-model="filters.status" class="filter-select" @change="loadItems(1)">
            <option value="">Semua Status</option>
            <option value="Tersedia">Tersedia</option>
            <option value="Dipinjam">Dipinjam</option>
            <option value="Rusak">Rusak</option>
            <option value="Hilang">Hilang</option>
            <option value="Dijual">Dijual</option>
          </select>
          <button
            type="button"
            class="filter-toggle"
            :class="{ active: showAdvancedFilters || advancedFiltersActive }"
            :aria-expanded="showAdvancedFilters"
            @click="showAdvancedFilters = !showAdvancedFilters"
          >
            Filter lanjutan
            <span v-if="advancedFiltersActive" class="filter-active-dot" aria-hidden="true" />
            <span class="filter-toggle-icon">{{ showAdvancedFilters ? '▼' : '▶' }}</span>
          </button>
        </div>
        <div v-show="showAdvancedFilters" class="filters filters-inline filters-advanced">
          <select v-model="filters.tracking_type" class="filter-select" @change="loadItems(1)">
            <option value="">Semua Tipe</option>
            <option v-for="t in trackingTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <select v-model="filters.condition" class="filter-select" @change="loadItems(1)">
            <option value="">Semua Kondisi</option>
            <option value="Baik">Baik</option>
            <option value="Rusak Ringan">Rusak Ringan</option>
            <option value="Rusak Berat">Rusak Berat</option>
            <option value="Habis Pakai">Habis Pakai</option>
          </select>
          <select v-model="filters.building_id" class="filter-select" @change="onBuildingFilterChange">
            <option value="">Semua Gedung</option>
            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
          <select v-model="filters.room_id" class="filter-select" @change="loadItems(1)">
            <option value="">Semua Ruangan</option>
            <option v-for="r in filterableRooms" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
          <button
            v-if="advancedFiltersActive"
            type="button"
            class="filter-reset"
            @click="resetAdvancedFilters"
          >
            Reset filter
          </button>
        </div>
      </div>
      <div v-if="!filters.only_trashed" class="tab-actions">
        <button v-if="canManage" type="button" class="btn-secondary btn-compact" :disabled="exportingItems" @click="exportItems">
          {{ exportingItems ? 'Mengekspor...' : 'Export Excel' }}
        </button>
        <button v-if="canManage" type="button" class="btn-secondary btn-compact" @click="openImportModal">
          Import Excel
        </button>
        <button type="button" class="btn-primary btn-add" @click="openItemModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>Tambah Master Barang</span>
        </button>
      </div>
    </div>

    <div v-if="itemsLoading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="8" :columns="8" />
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Tipe</th>
            <th>Kategori</th>
            <th>Qty</th>
            <th>Kondisi</th>
            <th>Status</th>
            <th>Lokasi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(it, index) in items" :key="it.id">
            <td class="col-no">{{ inventoryRowNumber(itemsMeta, index) }}</td>
            <td>
              <div class="code-cell">
                <div class="code">{{ it.code }}</div>
                <div v-if="it.serial_number" class="muted">SN: {{ it.serial_number }}</div>
              </div>
            </td>
            <td>
              <div class="name-cell">
                <div class="name">{{ it.name }}</div>
                <div class="muted">{{ [it.brand, it.model].filter(Boolean).join(' ') || '-' }}</div>
              </div>
            </td>
            <td><span class="badge-muted">{{ trackingTypeLabel(it.tracking_type) }}</span></td>
            <td>{{ it.category?.name || '-' }}</td>
            <td>{{ it.quantity }} {{ it.unit || 'Unit' }}</td>
            <td><span :class="getConditionClass(it.condition)">{{ it.condition }}</span></td>
            <td><span :class="getStatusClass(it.status)">{{ it.status }}</span></td>
            <td>
              <div class="muted">{{ itemLocationLabel(it) }}</div>
              <div v-if="it.location_note" class="muted small">{{ it.location_note }}</div>
            </td>
            <td>
              <div class="action-buttons">
                <template v-if="filters.only_trashed">
                  <TableAction kind="restore" @click="restoreItem(it)" />
                </template>
                <template v-else>
                  <TableAction kind="view" @click="$emit('view-detail', it)" />
                  <TableAction kind="edit" @click="openItemModal(it)" />
                  <TableAction
                    v-if="it.tracking_type === 'stock' && (it.quantity || 0) > 0"
                    kind="duplicate"
                    title="Konversi ke Aset Individual"
                    @click="confirmSplit(it)"
                  />
                  <TableAction kind="transaction" @click="$emit('transfer', it)" />
                  <TableAction kind="delete" @click="deleteItem(it)" />
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="items.length === 0" class="empty-state">
        <h3>{{ filters.only_trashed ? 'Kotak sampah kosong' : 'Tidak ada barang' }}</h3>
        <p>
          {{ filters.only_trashed
            ? 'Barang yang dihapus akan muncul di sini dan dapat dipulihkan.'
            : 'Mulai dengan menambahkan barang inventaris.' }}
        </p>
        <button v-if="!filters.only_trashed" type="button" class="btn-primary" @click="openItemModal()">
          Tambah Barang
        </button>
      </div>

      <PaginationBar
        embedded
        :page="itemsMeta.current_page"
        :last-page="itemsMeta.last_page"
        :per-page="itemsMeta.per_page"
        :total="itemsMeta.total"
        item-label="barang"
        @page-change="loadItems"
        @per-page-change="changeItemsPerPage"
      />
    </div>

    <!-- Item modal -->
    <div v-if="showItemModal" class="modal-overlay" @click="closeItemModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingItem ? 'Edit Master Barang' : 'Tambah Master Barang' }}</h3>
          <button type="button" class="btn-close" @click="closeItemModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="saveItem">
          <div class="form-row">
            <div class="form-group">
              <label>Tipe Pelacakan *</label>
              <select v-model="itemForm.tracking_type" :disabled="!!editingItem" required>
                <option v-for="t in trackingTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
              <p v-if="editingItem" class="muted small">Tipe pelacakan tidak dapat diubah setelah dibuat.</p>
            </div>
            <div class="form-group">
              <label>Kategori *</label>
              <select v-model="itemForm.category_id" required>
                <option value="">Pilih Kategori</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.code }} - {{ cat.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Kode Barang</label>
              <input
                v-model="itemForm.code"
                :readonly="!!editingItem && !canEditCode"
                placeholder="Kosongkan untuk auto-generate"
              />
              <p v-if="!editingItem" class="muted small">Tahun pada kode otomatis mengikuti tanggal beli.</p>
              <p v-else-if="canEditCode" class="muted small">Admin dapat mengubah kode inventaris.</p>
            </div>
            <div v-if="!editingItem" class="form-group">
              <label>Kode Khusus</label>
              <input v-model="itemForm.custom_code" maxlength="20" placeholder="Opsional" />
            </div>
          </div>
          <div v-if="!editingItem && itemForm.tracking_type === 'stock'" class="form-row">
            <div class="form-group">
              <label>No. Referensi (Transaksi Masuk)</label>
              <input v-model="itemForm.reference_number" placeholder="Opsional" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Nama Barang *</label>
              <input v-model="itemForm.name" required />
            </div>
            <div class="form-group">
              <label>{{ itemForm.tracking_type === 'individual' && !editingItem ? 'Jumlah Unit Aset *' : 'Jumlah *' }}</label>
              <input
                v-model.number="itemForm.quantity"
                type="number"
                min="1"
                :readonly="!!editingItem"
                required
              />
              <p v-if="editingItem" class="muted small">Ubah jumlah stok melalui transaksi Masuk/Keluar/Penyesuaian.</p>
              <p v-else-if="itemForm.tracking_type === 'individual'" class="muted small">Sistem akan membuat nomor aset per unit.</p>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Satuan</label>
              <input v-model="itemForm.unit" placeholder="Unit" />
            </div>
            <div class="form-group">
              <label>Status</label>
              <select v-model="itemForm.status">
                <option value="Tersedia">Tersedia</option>
                <option value="Dipinjam">Dipinjam</option>
                <option value="Rusak">Rusak</option>
                <option value="Hilang">Hilang</option>
                <option value="Dijual">Dijual</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Kondisi *</label>
              <select v-model="itemForm.condition" required>
                <option value="Baik">Baik</option>
                <option value="Rusak Ringan">Rusak Ringan</option>
                <option value="Rusak Berat">Rusak Berat</option>
                <option value="Habis Pakai">Habis Pakai</option>
              </select>
            </div>
            <div class="form-group">
              <label>Merk</label>
              <input v-model="itemForm.brand" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Model</label>
              <input v-model="itemForm.model" />
            </div>
            <div v-if="itemForm.tracking_type === 'stock'" class="form-group">
              <label>Serial Number</label>
              <input v-model="itemForm.serial_number" :disabled="!!editingItem && editingItem.tracking_type === 'individual'" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Cara/Sumber Perolehan</label>
              <select v-model="itemForm.acquisition_method">
                <option value="">(Opsional)</option>
                <option v-for="m in acquisitionMethods" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Sumber Dana</label>
              <select v-model="itemForm.funding_source">
                <option value="">(Opsional)</option>
                <option v-for="src in fundingSources" :key="src" :value="src">{{ src }}</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Kepemilikan</label>
              <select v-model="itemForm.ownership_type">
                <option value="">(Opsional)</option>
                <option v-for="o in ownershipTypes" :key="o" :value="o">{{ o }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Pemilik</label>
              <input v-model="itemForm.owner_name" placeholder="Opsional" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Tanggal Beli</label>
              <input v-model="itemForm.purchase_date" type="date" />
            </div>
            <div class="form-group">
              <label>Harga Beli</label>
              <input v-model.number="itemForm.purchase_price" type="number" min="0" step="0.01" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Supplier / Pemasok</label>
              <input v-model="itemForm.supplier" />
            </div>
            <div class="form-group">
              <label>Garansi Sampai</label>
              <input v-model="itemForm.warranty_expiry" type="date" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Penanggung Jawab</label>
              <select v-model="itemForm.responsible_employee_id">
                <option value="">(Opsional)</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                  {{ emp.name }}{{ emp.nip ? ` (${emp.nip})` : '' }}
                </option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Gedung</label>
              <select v-model="itemForm.building_id">
                <option value="">(Opsional)</option>
                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Ruangan{{ isRoomScoped ? ' *' : '' }}</label>
              <select v-model="itemForm.room_id" :required="isRoomScoped">
                <option value="">{{ isRoomScoped ? 'Pilih ruangan tanggung jawab Anda' : '(Opsional)' }}</option>
                <option v-for="r in selectableRooms" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Catatan Lokasi</label>
            <textarea v-model="itemForm.location_note" rows="2" />
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea v-model="itemForm.description" rows="3" />
          </div>
          <div class="form-group">
            <label>Gambar (JPG/PNG)</label>
            <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/jpg" @change="handleImageChange" />
            <p v-if="imageName" class="form-hint">File: {{ imageName }}</p>
          </div>
          <div v-if="itemError" class="error-message">{{ itemError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeItemModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="itemSaving">
              {{ itemSaving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showImportModal" class="modal-overlay" @click="closeImportModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Import Master Barang (Excel)</h3>
          <button type="button" class="btn-close" @click="closeImportModal">×</button>
        </div>
        <div class="modal-body">
          <p class="muted small">
            Unduh template, isi data, lalu pilih file <strong>.xlsx</strong>.
            File dibaca di browser (sama seperti import perpustakaan), lalu dikirim ke server.
            Kolom wajib: <strong>kode_kategori</strong> dan <strong>nama</strong>.
            Kode kategori harus sudah ada di Pengaturan → Kategori.
          </p>
          <div class="form-group">
            <label>File Excel (.xlsx) *</label>
            <input
              ref="importFileInput"
              type="file"
              accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              @change="onImportFileChange"
            />
          </div>
          <div class="form-group">
            <button type="button" class="btn-secondary btn-compact" :disabled="downloadingTemplate" @click="downloadTemplate">
              {{ downloadingTemplate ? 'Mengunduh...' : 'Unduh Template Excel' }}
            </button>
          </div>
          <div v-if="importResult" class="import-result">
            <p>
              Ditambah: <strong>{{ importResult.success }}</strong> ·
              Diperbarui: <strong>{{ importResult.updated }}</strong> ·
              Gagal: <strong>{{ importResult.failed }}</strong>
            </p>
            <ul v-if="importResult.errors?.length" class="import-errors">
              <li v-for="(err, i) in importResult.errors.slice(0, 20)" :key="i">{{ err }}</li>
              <li v-if="importResult.errors.length > 20">… dan {{ importResult.errors.length - 20 }} error lainnya</li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeImportModal">Tutup</button>
            <button type="button" class="btn-primary" :disabled="importing || !importFile" @click="runImport">
              {{ importing ? 'Mengimpor...' : 'Import' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :confirm-text="confirmDialog.confirmText"
      :loading="confirmDialog.loading"
      :loading-text="confirmDialog.loadingText"
      :confirm-variant="confirmDialog.confirmVariant"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { inventoryApi } from '@/api/inventory'
import { employeeApi } from '@/api/teacher'
import {
  getConditionClass,
  getStatusClass,
  itemLocationLabel
} from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { INVENTORY_FUNDING_SOURCES, INVENTORY_TRACKING_TYPES, INVENTORY_OWNERSHIP_TYPES, INVENTORY_ACQUISITION_METHODS, trackingTypeLabel } from '@/composables/inventory/inventoryConstants'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import { useAuthStore } from '@/stores/auth'
import { downloadInventoryImportTemplate, parseInventoryExcelFile } from '@/composables/inventory/useInventoryImport'
import { useInventoryAccess } from '@/composables/inventory/useInventoryAccess'

const props = defineProps({
  categories: { type: Array, default: () => [] },
  buildings: { type: Array, default: () => [] },
  rooms: { type: Array, default: () => [] }
})

defineEmits(['view-detail', 'transfer'])

const toast = useToast()
const { canManage, isRoomScoped, managedRoomIds } = useInventoryAccess()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()
const authStore = useAuthStore()

const canEditCode = computed(() => {
  const role = authStore.user?.role
  return role === 'admin' || role === 'institution_admin' || role === 'super_admin'
})

const filters = ref({
  search: '',
  category_id: '',
  status: '',
  condition: '',
  room_id: '',
  building_id: '',
  tracking_type: '',
  only_trashed: false
})

const selectableRooms = computed(() => {
  if (!isRoomScoped.value) return props.rooms
  const allowed = new Set(managedRoomIds.value)
  return props.rooms.filter((r) => allowed.has(Number(r.id)))
})

const filterableRooms = computed(() => {
  let list = selectableRooms.value
  if (filters.value.building_id) {
    const buildingId = Number(filters.value.building_id)
    list = list.filter((r) => Number(r.building_id) === buildingId)
  }
  return list
})

const showAdvancedFilters = ref(false)

const advancedFiltersActive = computed(() => {
  const f = filters.value
  return !!(f.tracking_type || f.condition || f.building_id || f.room_id)
})

function onBuildingFilterChange() {
  const roomStillValid = filterableRooms.value.some((r) => String(r.id) === String(filters.value.room_id))
  if (!roomStillValid) filters.value.room_id = ''
  loadItems(1)
}

function resetAdvancedFilters() {
  filters.value.tracking_type = ''
  filters.value.condition = ''
  filters.value.building_id = ''
  filters.value.room_id = ''
  loadItems(1)
}

const items = ref([])
const employees = ref([])
const fundingSources = INVENTORY_FUNDING_SOURCES
const trackingTypes = INVENTORY_TRACKING_TYPES
const ownershipTypes = INVENTORY_OWNERSHIP_TYPES
const acquisitionMethods = INVENTORY_ACQUISITION_METHODS
const itemsLoading = ref(false)
const itemsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
let itemSearchTimeout = null

const showItemModal = ref(false)
const editingItem = ref(null)
const itemSaving = ref(false)
const itemError = ref('')
const imageInput = ref(null)
const imageName = ref('')
const exportingItems = ref(false)
const showImportModal = ref(false)
const importing = ref(false)
const downloadingTemplate = ref(false)
const importFile = ref(null)
const importFileInput = ref(null)
const importResult = ref(null)
const itemForm = ref(emptyItemForm())

function emptyItemForm() {
  return {
    category_id: '',
    tracking_type: 'stock',
    code: '',
    custom_code: '',
    reference_number: '',
    name: '',
    brand: '',
    model: '',
    serial_number: '',
    purchase_date: '',
    purchase_price: '',
    supplier: '',
    acquisition_method: '',
    ownership_type: '',
    owner_name: '',
    condition: 'Baik',
    status: 'Tersedia',
    quantity: 1,
    unit: 'Unit',
    room_id: '',
    building_id: '',
    location_note: '',
    warranty_expiry: '',
    description: '',
    funding_source: '',
    responsible_employee_id: '',
    image: null
  }
}

async function loadItems(page = 1) {
  itemsLoading.value = true
  try {
    const params = { page, per_page: itemsMeta.value.per_page }
    Object.entries(filters.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') params[k] = v
    })
    if (filters.value.only_trashed) params.only_trashed = true

    const res = await inventoryApi.getItems(params)
    items.value = safeArray(res)
    itemsMeta.value = parsePagination(res, itemsMeta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat barang')
    items.value = []
  } finally {
    itemsLoading.value = false
  }
}

function changeItemsPerPage(n) {
  itemsMeta.value.per_page = n
  itemsMeta.value.current_page = 1
  loadItems(1)
}

function debounceLoadItems() {
  if (itemSearchTimeout) clearTimeout(itemSearchTimeout)
  itemSearchTimeout = setTimeout(() => loadItems(1), 450)
}

function setTrashMode(trash) {
  filters.value.only_trashed = trash
  loadItems(1)
}

function openItemModal(it = null) {
  editingItem.value = it
  itemError.value = ''
  imageName.value = ''
  if (imageInput.value) imageInput.value.value = ''

  if (it) {
    itemForm.value = {
      category_id: it.category_id || it.category?.id || '',
      tracking_type: it.tracking_type || 'stock',
      code: it.code || '',
      custom_code: '',
      reference_number: '',
      name: it.name || '',
      brand: it.brand || '',
      model: it.model || '',
      serial_number: it.serial_number || '',
      purchase_date: it.purchase_date ? String(it.purchase_date).split('T')[0] : '',
      purchase_price: it.purchase_price || '',
      supplier: it.supplier || '',
      acquisition_method: it.acquisition_method || '',
      ownership_type: it.ownership_type || '',
      owner_name: it.owner_name || '',
      condition: it.condition || 'Baik',
      status: it.status || 'Tersedia',
      quantity: it.quantity || 1,
      unit: it.unit || 'Unit',
      room_id: it.room_id || it.room?.id || '',
      building_id: it.building_id || it.building?.id || '',
      location_note: it.location_note || '',
      warranty_expiry: it.warranty_expiry ? String(it.warranty_expiry).split('T')[0] : '',
      description: it.description || '',
      funding_source: it.funding_source || '',
      responsible_employee_id: it.responsible_employee_id || it.responsible_employee?.id || '',
      image: null
    }
  } else {
    itemForm.value = emptyItemForm()
  }
  showItemModal.value = true
}

function closeItemModal() {
  showItemModal.value = false
  editingItem.value = null
  itemError.value = ''
}

function handleImageChange(e) {
  const file = e.target?.files?.[0]
  if (!file) {
    itemForm.value.image = null
    imageName.value = ''
    return
  }
  itemForm.value.image = file
  imageName.value = file.name
}

async function saveItem() {
  itemSaving.value = true
  itemError.value = ''
  try {
    const basePayload = {
      category_id: itemForm.value.category_id,
      tracking_type: itemForm.value.tracking_type,
      code: itemForm.value.code ? itemForm.value.code.trim() : null,
      name: itemForm.value.name ? itemForm.value.name.trim() : null,
      brand: itemForm.value.brand ? itemForm.value.brand.trim() : null,
      model: itemForm.value.model ? itemForm.value.model.trim() : null,
      serial_number: itemForm.value.tracking_type === 'stock' && itemForm.value.serial_number
        ? itemForm.value.serial_number.trim()
        : null,
      purchase_date: itemForm.value.purchase_date || null,
      purchase_price: itemForm.value.purchase_price !== '' ? itemForm.value.purchase_price : null,
      supplier: itemForm.value.supplier ? itemForm.value.supplier.trim() : null,
      acquisition_method: itemForm.value.acquisition_method || null,
      ownership_type: itemForm.value.ownership_type || null,
      owner_name: itemForm.value.owner_name ? itemForm.value.owner_name.trim() : null,
      condition: itemForm.value.condition,
      status: itemForm.value.status,
      unit: itemForm.value.unit ? itemForm.value.unit.trim() : null,
      room_id: itemForm.value.room_id || null,
      building_id: itemForm.value.building_id || null,
      location_note: itemForm.value.location_note ? itemForm.value.location_note.trim() : null,
      warranty_expiry: itemForm.value.warranty_expiry || null,
      description: itemForm.value.description ? itemForm.value.description.trim() : null,
      funding_source: itemForm.value.funding_source || null,
      responsible_employee_id: itemForm.value.responsible_employee_id || null,
      image: itemForm.value.image || null
    }

    if (editingItem.value) {
      await inventoryApi.updateItem(editingItem.value.id, basePayload)
      toast.success('Berhasil', 'Master barang berhasil diperbarui')
    } else {
      await inventoryApi.createItem({
        ...basePayload,
        quantity: itemForm.value.quantity,
        custom_code: itemForm.value.custom_code ? itemForm.value.custom_code.trim() : null,
        reference_number: itemForm.value.reference_number ? itemForm.value.reference_number.trim() : null
      })
      toast.success('Berhasil', 'Master barang berhasil ditambahkan')
    }
    closeItemModal()
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan barang'
    itemError.value = msg
    toast.error('Gagal', msg)
  } finally {
    itemSaving.value = false
  }
}

async function confirmSplit(it) {
  const confirmed = await showConfirm({
    title: 'Konversi ke Aset Individual',
    message: `Konversi seluruh stok "${it.name}" (${it.quantity} unit) menjadi aset individual?`,
    warning: 'Master akan berubah tipe pelacakan. Setiap unit mendapat nomor aset dan QR sendiri. Tindakan ini tidak otomatis memecah stok sebagian.',
    confirmText: 'Konversi',
    confirmVariant: 'primary',
    loadingText: 'Memproses...',
  })
  if (!confirmed) return
  setDeleteLoading(true)
  try {
    await inventoryApi.splitItemAssets(it.id, it.quantity)
    toast.success('Berhasil', `${it.quantity} aset individual berhasil dibuat`)
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal konversi aset')
  } finally {
    setDeleteLoading(false)
  }
}

async function deleteItem(it) {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: `Hapus barang "${it.name}"?`,
    warning: 'Barang akan dipindahkan ke kotak sampah dan dapat dipulihkan kembali.'
  })
  if (!confirmed) return
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteItem(it.id)
    toast.success('Berhasil', 'Barang berhasil dihapus')
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal menghapus barang')
  } finally {
    setDeleteLoading(false)
  }
}

async function restoreItem(it) {
  try {
    await inventoryApi.restoreItem(it.id)
    toast.success('Berhasil', 'Barang berhasil dipulihkan')
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memulihkan barang')
  }
}

async function loadEmployees() {
  try {
    const res = await employeeApi.getAll({ per_page: 200 })
    employees.value = safeArray(res)
  } catch {
    employees.value = []
  }
}

function buildExportParams() {
  const params = {}
  if (filters.value.search?.trim()) params.search = filters.value.search.trim()
  if (filters.value.category_id) params.category_id = filters.value.category_id
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.condition) params.condition = filters.value.condition
  if (filters.value.tracking_type) params.tracking_type = filters.value.tracking_type
  if (filters.value.building_id) params.building_id = filters.value.building_id
  if (filters.value.room_id) params.room_id = filters.value.room_id
  return params
}

async function exportItems() {
  exportingItems.value = true
  try {
    await inventoryApi.exportItemsExcel(buildExportParams())
    toast.success('Berhasil', 'Excel master barang diunduh')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal export Excel')
  } finally {
    exportingItems.value = false
  }
}

function openImportModal() {
  importResult.value = null
  importFile.value = null
  if (importFileInput.value) importFileInput.value.value = ''
  showImportModal.value = true
}

function closeImportModal() {
  showImportModal.value = false
}

function onImportFileChange(e) {
  importFile.value = e.target.files?.[0] || null
  importResult.value = null
}

async function downloadTemplate() {
  downloadingTemplate.value = true
  try {
    await downloadInventoryImportTemplate(props.categories)
    toast.success('Berhasil', 'Template Excel diunduh')
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal membuat template')
  } finally {
    downloadingTemplate.value = false
  }
}

async function runImport() {
  if (!importFile.value) {
    toast.error('Gagal', 'Pilih file Excel (.xlsx) terlebih dahulu')
    return
  }
  const name = importFile.value.name.toLowerCase()
  if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
    toast.error('Gagal', 'Format file harus Excel (.xlsx)')
    return
  }

  importing.value = true
  importResult.value = null
  try {
    const buffer = await importFile.value.arrayBuffer()
    const rows = parseInventoryExcelFile(buffer)
    if (!rows.length) {
      throw new Error('Tidak ada baris valid di file Excel')
    }
    const res = await inventoryApi.importItems(rows)
    importResult.value = res.data?.data ?? res.data ?? null
    toast.success('Berhasil', 'Import master barang selesai')
    await loadItems(1)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || err.message || 'Gagal import Excel')
  } finally {
    importing.value = false
  }
}

onMounted(() => {
  loadItems(1)
  loadEmployees()
})

defineExpose({ refresh: () => loadItems(itemsMeta.value.current_page || 1) })
</script>

