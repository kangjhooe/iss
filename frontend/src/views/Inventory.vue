<template>
  <Layout>
    <div class="inventory-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Inventaris</h2>
            <p>Kelola kategori, barang inventaris, dan transaksi</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-container">
        <div class="tabs-nav">
          <button @click="activeTab = 'items'" :class="['tab-btn', { active: activeTab === 'items' }]">
            <span>Barang</span>
          </button>
          <button @click="activeTab = 'categories'" :class="['tab-btn', { active: activeTab === 'categories' }]">
            <span>Kategori</span>
          </button>
          <button @click="activeTab = 'transactions'" :class="['tab-btn', { active: activeTab === 'transactions' }]">
            <span>Transaksi</span>
          </button>
        </div>
      </div>

      <!-- ITEMS TAB -->
      <div v-show="activeTab === 'items'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="itemFilters.search"
              @input="debounceLoadItems"
              placeholder="Cari kode, nama, merk, model, serial..."
              class="search-input"
            />

            <select v-model="itemFilters.category_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.code }} - {{ cat.name }}
              </option>
            </select>

            <select v-model="itemFilters.status" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Tersedia">Tersedia</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Rusak">Rusak</option>
              <option value="Hilang">Hilang</option>
              <option value="Dijual">Dijual</option>
            </select>

            <select v-model="itemFilters.condition" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
              <option value="Habis Pakai">Habis Pakai</option>
            </select>

            <select v-model="itemFilters.building_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Gedung</option>
              <option v-for="b in buildings" :key="b.id" :value="b.id">
                {{ b.name }}
              </option>
            </select>

            <select v-model="itemFilters.room_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Ruangan</option>
              <option v-for="r in rooms" :key="r.id" :value="r.id">
                {{ r.name }}
              </option>
            </select>
          </div>

          <button @click="openItemModal()" class="btn-primary">
            <span>Tambah Barang</span>
          </button>
        </div>

        <div v-if="itemsLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Qty</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in items" :key="it.id">
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
                <td>{{ it.category?.name || '-' }}</td>
                <td>{{ it.quantity }} {{ it.unit || 'Unit' }}</td>
                <td><span :class="getConditionClass(it.condition)">{{ it.condition }}</span></td>
                <td><span :class="getStatusClass(it.status)">{{ it.status }}</span></td>
                <td>
                  <div class="muted">
                    {{ it.room?.name || (it.building?.name || '-') }}
                  </div>
                  <div v-if="it.location_note" class="muted small">
                    {{ it.location_note }}
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openItemModal(it)" class="btn-action btn-edit" title="Edit">Edit</button>
                    <button @click="openTransactionModal(it)" class="btn-action btn-secondary" title="Transaksi">Transaksi</button>
                    <button @click="deleteItem(it)" class="btn-action btn-delete" title="Hapus">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="items.length === 0" class="empty-state">
            <h3>Tidak ada barang</h3>
            <p>Mulai dengan menambahkan barang inventaris.</p>
            <button @click="openItemModal()" class="btn-primary">Tambah Barang</button>
          </div>

          <div v-if="itemsMeta.last_page > 1" class="pagination">
            <button
              @click="loadItems(itemsMeta.current_page - 1)"
              :disabled="itemsMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ itemsMeta.current_page }} dari {{ itemsMeta.last_page }} (Total: {{ itemsMeta.total }})
            </span>
            <button
              @click="loadItems(itemsMeta.current_page + 1)"
              :disabled="itemsMeta.current_page >= itemsMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- CATEGORIES TAB -->
      <div v-show="activeTab === 'categories'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="categoryFilters.search"
              @input="debounceLoadCategories"
              placeholder="Cari kode / nama kategori..."
              class="search-input"
            />
            <select v-model="categoryFilters.is_active" @change="loadCategories(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button @click="openCategoryModal()" class="btn-primary">
            <span>Tambah Kategori</span>
          </button>
        </div>

        <div v-if="categoriesLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td>{{ cat.code }}</td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ cat.name }}</div>
                    <div v-if="cat.description" class="muted small">{{ cat.description }}</div>
                  </div>
                </td>
                <td>
                  <span :class="cat.is_active ? 'badge-success' : 'badge-gray'">
                    {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openCategoryModal(cat)" class="btn-action btn-edit">Edit</button>
                    <button @click="deleteCategory(cat)" class="btn-action btn-delete">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="categories.length === 0" class="empty-state">
            <h3>Tidak ada kategori</h3>
            <p>Tambahkan kategori inventaris terlebih dahulu.</p>
            <button @click="openCategoryModal()" class="btn-primary">Tambah Kategori</button>
          </div>

          <div v-if="categoriesMeta.last_page > 1" class="pagination">
            <button
              @click="loadCategories(categoriesMeta.current_page - 1)"
              :disabled="categoriesMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ categoriesMeta.current_page }} dari {{ categoriesMeta.last_page }} (Total: {{ categoriesMeta.total }})
            </span>
            <button
              @click="loadCategories(categoriesMeta.current_page + 1)"
              :disabled="categoriesMeta.current_page >= categoriesMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- TRANSACTIONS TAB -->
      <div v-show="activeTab === 'transactions'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <select v-model="transactionFilters.transaction_type" @change="loadTransactions(1)" class="filter-select">
              <option value="">Semua Jenis</option>
              <option value="Masuk">Masuk</option>
              <option value="Keluar">Keluar</option>
              <option value="Mutasi">Mutasi</option>
              <option value="Penyesuaian">Penyesuaian</option>
            </select>
            <input v-model="transactionFilters.date_from" @change="loadTransactions(1)" type="date" class="filter-select" />
            <input v-model="transactionFilters.date_to" @change="loadTransactions(1)" type="date" class="filter-select" />
          </div>

          <button @click="openTransactionModal()" class="btn-primary">
            <span>Catat Transaksi</span>
          </button>
        </div>

        <div v-if="transactionsLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Barang</th>
                <th>Qty</th>
                <th>Lokasi</th>
                <th>Referensi</th>
                <th>Petugas</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tr in transactions" :key="tr.id">
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
                  <span v-if="tr.to_location?.name" :style="{ marginLeft: tr.from_location?.name ? '8px' : '0' }">
                    → Ke: {{ tr.to_location.name }}
                  </span>
                  <span v-if="!tr.from_location && !tr.to_location">-</span>
                </td>
                <td class="muted">{{ tr.reference_number || '-' }}</td>
                <td class="muted">{{ tr.creator?.name || '-' }}</td>
              </tr>
            </tbody>
          </table>

          <div v-if="transactions.length === 0" class="empty-state">
            <h3>Belum ada transaksi</h3>
            <p>Catat transaksi masuk/keluar/mutasi/penyesuaian.</p>
            <button @click="openTransactionModal()" class="btn-primary">Catat Transaksi</button>
          </div>

          <div v-if="transactionsMeta.last_page > 1" class="pagination">
            <button
              @click="loadTransactions(transactionsMeta.current_page - 1)"
              :disabled="transactionsMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ transactionsMeta.current_page }} dari {{ transactionsMeta.last_page }} (Total: {{ transactionsMeta.total }})
            </span>
            <button
              @click="loadTransactions(transactionsMeta.current_page + 1)"
              :disabled="transactionsMeta.current_page >= transactionsMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- ITEM MODAL -->
      <div v-if="showItemModal" class="modal-overlay" @click="closeItemModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Barang' : 'Tambah Barang' }}</h3>
            <button @click="closeItemModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveItem" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Kategori *</label>
                <select v-model="itemForm.category_id" required>
                  <option value="">Pilih Kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.code }} - {{ cat.name }}
                  </option>
                </select>
                <p v-if="categories.length === 0" class="form-hint">Belum ada kategori. Tambahkan kategori dulu.</p>
              </div>
              <div class="form-group">
                <label>Kode Barang</label>
                <input v-model="itemForm.code" :readonly="!editingItem" placeholder="Kosongkan untuk auto-generate" />
                <p v-if="!editingItem" class="form-hint">Jika kosong, sistem akan membuat kode otomatis.</p>
              </div>
            </div>

            <div v-if="!editingItem" class="form-row">
              <div class="form-group">
                <label>Kode Khusus</label>
                <input v-model="itemForm.custom_code" placeholder="Contoh: BKBA (opsional)" maxlength="20" />
              </div>
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
                <label>Jumlah *</label>
                <input type="number" v-model.number="itemForm.quantity" min="1" required />
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
              <div class="form-group">
                <label>Serial Number</label>
                <input v-model="itemForm.serial_number" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Beli</label>
                <input type="date" v-model="itemForm.purchase_date" />
              </div>
              <div class="form-group">
                <label>Harga Beli</label>
                <input type="number" v-model.number="itemForm.purchase_price" min="0" step="0.01" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Supplier</label>
                <input v-model="itemForm.supplier" />
              </div>
              <div class="form-group">
                <label>Garansi Sampai</label>
                <input type="date" v-model="itemForm.warranty_expiry" />
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
                <label>Ruangan</label>
                <select v-model="itemForm.room_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Catatan Lokasi</label>
              <textarea v-model="itemForm.location_note" rows="2" placeholder="Opsional"></textarea>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="itemForm.description" rows="3" placeholder="Opsional"></textarea>
            </div>

            <div class="form-group">
              <label>Gambar (JPG/PNG)</label>
              <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/jpg" @change="handleImageChange" />
              <p v-if="imageName" class="form-hint">File: {{ imageName }}</p>
              <p class="form-hint">Maks 2MB.</p>
            </div>

            <div v-if="itemError" class="error-message">{{ itemError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeItemModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="itemSaving" class="btn-primary">
                {{ itemSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- CATEGORY MODAL -->
      <div v-if="showCategoryModal" class="modal-overlay" @click="closeCategoryModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
            <button @click="closeCategoryModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveCategory" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Kode *</label>
                <input v-model="categoryForm.code" required maxlength="10" placeholder="Contoh: MEU" />
              </div>
              <div class="form-group">
                <label>Nama *</label>
                <input v-model="categoryForm.name" required maxlength="100" />
              </div>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="categoryForm.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" v-model="categoryForm.is_active" />
                Aktif
              </label>
            </div>

            <div v-if="categoryError" class="error-message">{{ categoryError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeCategoryModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="categorySaving" class="btn-primary">
                {{ categorySaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- TRANSACTION MODAL -->
      <div v-if="showTransactionModal" class="modal-overlay" @click="closeTransactionModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Catat Transaksi</h3>
            <button @click="closeTransactionModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveTransaction" class="modal-body">
            <div class="form-group">
              <label>Barang *</label>
              <select v-model="transactionForm.item_id" required>
                <option value="">Pilih Barang</option>
                <option v-for="it in itemOptions" :key="it.id" :value="it.id">
                  {{ it.code }} - {{ it.name }}
                </option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenis Transaksi *</label>
                <select v-model="transactionForm.transaction_type" required>
                  <option value="Masuk">Masuk</option>
                  <option value="Keluar">Keluar</option>
                  <option value="Mutasi">Mutasi</option>
                  <option value="Penyesuaian">Penyesuaian</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal *</label>
                <input type="date" v-model="transactionForm.transaction_date" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jumlah *</label>
                <input type="number" v-model.number="transactionForm.quantity" min="1" required />
              </div>
              <div class="form-group">
                <label>No. Referensi</label>
                <input v-model="transactionForm.reference_number" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Dari Ruangan</label>
                <select v-model="transactionForm.from_location_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ke Ruangan</label>
                <select v-model="transactionForm.to_location_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="transactionForm.notes" rows="3"></textarea>
            </div>

            <div v-if="transactionError" class="error-message">{{ transactionError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeTransactionModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="transactionSaving" class="btn-primary">
                {{ transactionSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:show="confirmDialog.show = $event"
    />
  </Layout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { inventoryApi } from '@/api/inventory'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const activeTab = ref('items')

// Shared options
const buildings = ref([])
const rooms = ref([])

const safeArray = (response) => {
  if (response?.data?.data && Array.isArray(response.data.data)) return response.data.data
  if (Array.isArray(response?.data)) return response.data
  return []
}

const parsePagination = (response, fallback = { current_page: 1, last_page: 1, per_page: 15, total: 0 }) => {
  const meta = response?.data?.meta || response?.data?.data?.meta
  if (meta) {
    return {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      per_page: meta.per_page || 15,
      total: meta.total || 0
    }
  }
  return fallback
}

// ==================== Categories state ====================
const categories = ref([])
const categoriesLoading = ref(false)
const categoriesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const categoryFilters = ref({ search: '', is_active: '' })
const categorySearchTimeout = ref(null)

const loadCategories = async (page = 1) => {
  categoriesLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (categoryFilters.value.search) params.search = categoryFilters.value.search
    if (categoryFilters.value.is_active !== '') params.is_active = categoryFilters.value.is_active

    const res = await inventoryApi.getCategories(params)
    categories.value = safeArray(res)
    categoriesMeta.value = parsePagination(res, categoriesMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat kategori'
    toast.error('Gagal', msg)
    categories.value = []
  } finally {
    categoriesLoading.value = false
  }
}

const debounceLoadCategories = () => {
  if (categorySearchTimeout.value) clearTimeout(categorySearchTimeout.value)
  categorySearchTimeout.value = setTimeout(() => loadCategories(1), 400)
}

// Category modal
const showCategoryModal = ref(false)
const editingCategory = ref(null)
const categorySaving = ref(false)
const categoryError = ref('')
const categoryForm = ref({
  code: '',
  name: '',
  description: '',
  is_active: true
})

const openCategoryModal = (cat = null) => {
  editingCategory.value = cat
  categoryError.value = ''
  categoryForm.value = cat
    ? {
        code: cat.code || '',
        name: cat.name || '',
        description: cat.description || '',
        is_active: !!cat.is_active
      }
    : { code: '', name: '', description: '', is_active: true }
  showCategoryModal.value = true
}

const closeCategoryModal = () => {
  showCategoryModal.value = false
  editingCategory.value = null
  categoryError.value = ''
}

const saveCategory = async () => {
  categorySaving.value = true
  categoryError.value = ''
  try {
    const payload = {
      code: (categoryForm.value.code || '').trim(),
      name: (categoryForm.value.name || '').trim(),
      description: categoryForm.value.description ? categoryForm.value.description.trim() : null,
      is_active: !!categoryForm.value.is_active
    }

    if (editingCategory.value) {
      await inventoryApi.updateCategory(editingCategory.value.id, payload)
      toast.success('Berhasil', 'Kategori berhasil diperbarui')
    } else {
      await inventoryApi.createCategory(payload)
      toast.success('Berhasil', 'Kategori berhasil ditambahkan')
    }

    closeCategoryModal()
    await loadCategories(categoriesMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan kategori'
    categoryError.value = msg
    toast.error('Gagal', msg)
  } finally {
    categorySaving.value = false
  }
}

const deleteCategory = async (cat) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: `Hapus kategori "${cat.name}"?`,
    warning: 'Kategori akan dihapus secara permanen.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteCategory(cat.id)
    toast.success('Berhasil', 'Kategori berhasil dihapus')
    await loadCategories(categoriesMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menghapus kategori'
    toast.error('Gagal', msg)
  } finally {
    setDeleteLoading(false)
  }
}

// ==================== Items state ====================
const items = ref([])
const itemsLoading = ref(false)
const itemsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const itemFilters = ref({
  search: '',
  category_id: '',
  status: '',
  condition: '',
  room_id: '',
  building_id: ''
})
const itemSearchTimeout = ref(null)

const loadItems = async (page = 1) => {
  itemsLoading.value = true
  try {
    const params = { page, per_page: 15 }
    Object.entries(itemFilters.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') params[k] = v
    })

    const res = await inventoryApi.getItems(params)
    items.value = safeArray(res)
    itemsMeta.value = parsePagination(res, itemsMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat barang'
    toast.error('Gagal', msg)
    items.value = []
  } finally {
    itemsLoading.value = false
  }
}

const debounceLoadItems = () => {
  if (itemSearchTimeout.value) clearTimeout(itemSearchTimeout.value)
  itemSearchTimeout.value = setTimeout(() => loadItems(1), 450)
}

// Item modal
const showItemModal = ref(false)
const editingItem = ref(null)
const itemSaving = ref(false)
const itemError = ref('')
const imageInput = ref(null)
const imageName = ref('')

const itemForm = ref({
  category_id: '',
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
  condition: 'Baik',
  status: 'Tersedia',
  quantity: 1,
  unit: 'Unit',
  room_id: '',
  building_id: '',
  location_note: '',
  warranty_expiry: '',
  description: '',
  image: null
})

const openItemModal = async (it = null) => {
  // ensure option lists
  if (categories.value.length === 0) await loadCategories(1)
  if (buildings.value.length === 0) await loadBuildings()
  if (rooms.value.length === 0) await loadRooms()

  editingItem.value = it
  itemError.value = ''
  imageName.value = ''
  if (imageInput.value) imageInput.value.value = ''

  if (it) {
    itemForm.value = {
      category_id: it.category_id || it.category?.id || '',
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
      condition: it.condition || 'Baik',
      status: it.status || 'Tersedia',
      quantity: it.quantity || 1,
      unit: it.unit || 'Unit',
      room_id: it.room_id || it.room?.id || '',
      building_id: it.building_id || it.building?.id || '',
      location_note: it.location_note || '',
      warranty_expiry: it.warranty_expiry ? String(it.warranty_expiry).split('T')[0] : '',
      description: it.description || '',
      image: null
    }
  } else {
    itemForm.value = {
      category_id: '',
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
      condition: 'Baik',
      status: 'Tersedia',
      quantity: 1,
      unit: 'Unit',
      room_id: '',
      building_id: '',
      location_note: '',
      warranty_expiry: '',
      description: '',
      image: null
    }
  }

  showItemModal.value = true
}

const closeItemModal = () => {
  showItemModal.value = false
  editingItem.value = null
  itemError.value = ''
}

const handleImageChange = (e) => {
  const file = e.target?.files?.[0]
  if (!file) {
    itemForm.value.image = null
    imageName.value = ''
    return
  }
  itemForm.value.image = file
  imageName.value = file.name
}

const saveItem = async () => {
  itemSaving.value = true
  itemError.value = ''
  try {
    const basePayload = {
      category_id: itemForm.value.category_id,
      code: itemForm.value.code ? itemForm.value.code.trim() : null,
      name: itemForm.value.name ? itemForm.value.name.trim() : null,
      brand: itemForm.value.brand ? itemForm.value.brand.trim() : null,
      model: itemForm.value.model ? itemForm.value.model.trim() : null,
      serial_number: itemForm.value.serial_number ? itemForm.value.serial_number.trim() : null,
      purchase_date: itemForm.value.purchase_date || null,
      purchase_price: itemForm.value.purchase_price !== '' ? itemForm.value.purchase_price : null,
      supplier: itemForm.value.supplier ? itemForm.value.supplier.trim() : null,
      condition: itemForm.value.condition,
      status: itemForm.value.status,
      quantity: itemForm.value.quantity,
      unit: itemForm.value.unit ? itemForm.value.unit.trim() : null,
      room_id: itemForm.value.room_id || null,
      building_id: itemForm.value.building_id || null,
      location_note: itemForm.value.location_note ? itemForm.value.location_note.trim() : null,
      warranty_expiry: itemForm.value.warranty_expiry || null,
      description: itemForm.value.description ? itemForm.value.description.trim() : null,
      image: itemForm.value.image || null
    }

    if (editingItem.value) {
      // Update request does not accept custom_code/reference_number; omit.
      await inventoryApi.updateItem(editingItem.value.id, basePayload)
      toast.success('Berhasil', 'Barang berhasil diperbarui')
    } else {
      const payload = {
        ...basePayload,
        custom_code: itemForm.value.custom_code ? itemForm.value.custom_code.trim() : null,
        reference_number: itemForm.value.reference_number ? itemForm.value.reference_number.trim() : null
      }
      await inventoryApi.createItem(payload)
      toast.success('Berhasil', 'Barang berhasil ditambahkan')
    }

    closeItemModal()
    await loadItems(itemsMeta.value.current_page || 1)
    // refresh transaction tab item options
    await loadItemOptions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan barang'
    itemError.value = msg
    toast.error('Gagal', msg)
  } finally {
    itemSaving.value = false
  }
}

const deleteItem = async (it) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: `Hapus barang "${it.name}"?`,
    warning: 'Barang akan dihapus secara permanen dan tidak dapat dikembalikan.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteItem(it.id)
    toast.success('Berhasil', 'Barang berhasil dihapus')
    await loadItems(itemsMeta.value.current_page || 1)
    await loadItemOptions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menghapus barang'
    toast.error('Gagal', msg)
  } finally {
    setDeleteLoading(false)
  }
}

// ==================== Transactions state ====================
const transactions = ref([])
const transactionsLoading = ref(false)
const transactionsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const transactionFilters = ref({ transaction_type: '', date_from: '', date_to: '' })

const loadTransactions = async (page = 1) => {
  transactionsLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (transactionFilters.value.transaction_type) params.transaction_type = transactionFilters.value.transaction_type
    if (transactionFilters.value.date_from) params.date_from = transactionFilters.value.date_from
    if (transactionFilters.value.date_to) params.date_to = transactionFilters.value.date_to

    const res = await inventoryApi.getTransactions(params)
    transactions.value = safeArray(res)
    transactionsMeta.value = parsePagination(res, transactionsMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat transaksi'
    toast.error('Gagal', msg)
    transactions.value = []
  } finally {
    transactionsLoading.value = false
  }
}

// Transaction modal
const showTransactionModal = ref(false)
const transactionSaving = ref(false)
const transactionError = ref('')
const itemOptions = ref([])

const transactionForm = ref({
  item_id: '',
  transaction_type: 'Masuk',
  transaction_date: new Date().toISOString().split('T')[0],
  quantity: 1,
  reference_number: '',
  from_location_id: '',
  to_location_id: '',
  notes: ''
})

const loadItemOptions = async () => {
  try {
    const res = await inventoryApi.getItems({ per_page: 100 })
    itemOptions.value = safeArray(res)
  } catch {
    itemOptions.value = []
  }
}

const openTransactionModal = async (it = null) => {
  if (rooms.value.length === 0) await loadRooms()
  if (itemOptions.value.length === 0) await loadItemOptions()

  transactionError.value = ''
  transactionForm.value = {
    item_id: it?.id || '',
    transaction_type: 'Masuk',
    transaction_date: new Date().toISOString().split('T')[0],
    quantity: 1,
    reference_number: '',
    from_location_id: '',
    to_location_id: '',
    notes: ''
  }
  showTransactionModal.value = true
}

const closeTransactionModal = () => {
  showTransactionModal.value = false
  transactionError.value = ''
}

const saveTransaction = async () => {
  transactionSaving.value = true
  transactionError.value = ''
  try {
    const payload = {
      item_id: transactionForm.value.item_id,
      transaction_type: transactionForm.value.transaction_type,
      transaction_date: transactionForm.value.transaction_date,
      quantity: transactionForm.value.quantity,
      reference_number: transactionForm.value.reference_number ? transactionForm.value.reference_number.trim() : null,
      from_location_id: transactionForm.value.from_location_id || null,
      to_location_id: transactionForm.value.to_location_id || null,
      notes: transactionForm.value.notes ? transactionForm.value.notes.trim() : null
    }
    await inventoryApi.createTransaction(payload)
    toast.success('Berhasil', 'Transaksi berhasil dicatat')
    closeTransactionModal()
    await loadTransactions(transactionsMeta.value.current_page || 1)
    await loadItems(itemsMeta.value.current_page || 1) // reflect qty/location changes
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan transaksi'
    transactionError.value = msg
    toast.error('Gagal', msg)
  } finally {
    transactionSaving.value = false
  }
}

// ==================== Facility options loaders ====================
const loadBuildings = async () => {
  try {
    const res = await facilityApi.getBuildings({})
    buildings.value = safeArray(res)
  } catch {
    buildings.value = []
  }
}

const loadRooms = async () => {
  try {
    const res = await facilityApi.getRooms({})
    rooms.value = safeArray(res)
  } catch {
    rooms.value = []
  }
}

// ==================== UI helpers ====================
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return String(dateString)
  return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getConditionClass = (condition) => {
  const map = {
    Baik: 'badge-success',
    'Rusak Ringan': 'badge-warning',
    'Rusak Berat': 'badge-danger',
    'Habis Pakai': 'badge-gray'
  }
  return map[condition] || 'badge-gray'
}

const getStatusClass = (status) => {
  const map = {
    Tersedia: 'badge-success',
    Dipinjam: 'badge-info',
    Rusak: 'badge-warning',
    Hilang: 'badge-danger',
    Dijual: 'badge-gray'
  }
  return map[status] || 'badge-gray'
}

const getTransactionTypeClass = (type) => {
  const map = {
    Masuk: 'badge-success',
    Keluar: 'badge-danger',
    Mutasi: 'badge-info',
    Penyesuaian: 'badge-warning'
  }
  return map[type] || 'badge-gray'
}

watch(activeTab, async (tab) => {
  if (tab === 'items') {
    if (categories.value.length === 0) await loadCategories(1)
    if (buildings.value.length === 0) await loadBuildings()
    if (rooms.value.length === 0) await loadRooms()
    await loadItems(1)
  } else if (tab === 'categories') {
    await loadCategories(1)
  } else if (tab === 'transactions') {
    if (rooms.value.length === 0) await loadRooms()
    await loadItemOptions()
    await loadTransactions(1)
  }
})

onMounted(async () => {
  await Promise.all([loadBuildings(), loadRooms(), loadCategories(1)])
  await loadItems(1)
})
</script>

<style scoped>
.inventory-page {
  max-width: 1400px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.tabs-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.tabs-nav {
  display: flex;
  gap: 4px;
  padding: 8px;
}

.tab-btn {
  flex: 1;
  padding: 12px 16px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  transition: all 0.2s ease;
  border-radius: 12px;
}

.tab-btn:hover {
  background: #f8fafc;
  color: #667eea;
}

.tab-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.tab-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  padding: 24px;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.filters {
  display: flex;
  gap: 12px;
  flex: 1;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input {
  flex: 1;
  min-width: 260px;
}

.filter-select {
  min-width: 180px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.btn-primary {
  padding: 12px 18px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 700;
  font-size: 14px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(102, 126, 234, 0.35);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-state {
  padding: 48px 16px;
  text-align: center;
  color: #64748b;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.data-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
  vertical-align: top;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-action {
  border: none;
  border-radius: 10px;
  padding: 8px 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 12px;
  transition: all 0.2s ease;
}

.btn-edit {
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.18);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.18);
}

.btn-secondary {
  background: rgba(100, 116, 139, 0.12);
  color: #334155;
}

.btn-secondary:hover {
  background: rgba(100, 116, 139, 0.18);
}

.empty-state {
  text-align: center;
  padding: 56px 20px;
  color: #64748b;
}

.empty-state h3 {
  color: #1e293b;
  margin: 0 0 8px 0;
}

.empty-state p {
  margin: 0 0 16px 0;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  margin-top: 12px;
  border-radius: 12px;
}

.pagination-btn {
  padding: 8px 14px;
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.badge-success,
.badge-warning,
.badge-danger,
.badge-info,
.badge-gray {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-info {
  background: #dbeafe;
  color: #1e40af;
}

.badge-gray {
  background: #f1f5f9;
  color: #475569;
}

.muted {
  color: #64748b;
}

.small {
  font-size: 12px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-content {
  background: white;
  border-radius: 18px;
  width: 100%;
  max-width: 820px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}

.btn-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  font-size: 20px;
  line-height: 1;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.modal-body {
  padding: 20px 24px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 14px;
}

.form-group label {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 12px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-hint {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  padding: 12px 18px;
  background: white;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 800;
}

.btn-secondary:hover {
  background: #f8fafc;
}

.error-message {
  padding: 14px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  border: 1px solid #fecaca;
  font-size: 14px;
  margin-top: 10px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  .filter-select {
    min-width: 160px;
  }
  .search-input {
    min-width: 220px;
  }
}
</style>

