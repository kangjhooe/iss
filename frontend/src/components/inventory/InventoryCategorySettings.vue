<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <div class="search-wrap">
          <input
            v-model="filters.search"
            class="search-input"
            placeholder="Cari kode / nama kategori..."
            @input="debounceLoad"
          />
          <button
            v-if="filters.search"
            type="button"
            class="search-clear"
            aria-label="Hapus pencarian"
            @click="filters.search = ''; load(1)"
          >
            ×
          </button>
        </div>
        <select v-model="filters.is_active" class="filter-select" @change="load(1)">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Nonaktif</option>
        </select>
      </div>
      <div class="tab-actions">
        <button type="button" class="btn-primary btn-add" @click="openModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>Tambah Kategori</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="6" :columns="4" />
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(cat, index) in categories" :key="cat.id">
            <td class="col-no">{{ inventoryRowNumber(meta, index) }}</td>
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
                <TableAction kind="edit" @click="openModal(cat)" />
                <TableAction kind="delete" @click="remove(cat)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="categories.length === 0" class="empty-state">
        <h3>Tidak ada kategori</h3>
        <p>Tambahkan kategori inventaris terlebih dahulu.</p>
        <button type="button" class="btn-primary" @click="openModal()">Tambah Kategori</button>
      </div>

      <PaginationBar
        embedded
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="kategori"
        @page-change="load"
        @per-page-change="changePerPage"
      />
    </div>

    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
          <button type="button" class="btn-close" @click="closeModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="save">
          <div class="form-row">
            <div class="form-group">
              <label>Kode *</label>
              <input v-model="form.code" required maxlength="10" placeholder="Contoh: MEU" />
            </div>
            <div class="form-group">
              <label>Nama *</label>
              <input v-model="form.name" required maxlength="100" />
            </div>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea v-model="form.description" rows="3" />
          </div>
          <div class="form-group">
            <label>
              <input v-model="form.is_active" type="checkbox" />
              Aktif
            </label>
          </div>
          <div v-if="error" class="error-message">{{ error }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useInventoryCategories } from '@/composables/inventory/useInventoryCategories'

const {
  categories,
  loading,
  meta,
  filters,
  showModal,
  editing,
  saving,
  error,
  form,
  load,
  debounceLoad,
  changePerPage,
  openModal,
  closeModal,
  save,
  remove
} = useInventoryCategories()

onMounted(() => load(1))
</script>
