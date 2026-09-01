<template>    <div class="keuangan-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Jenis Biaya</h1>
              <p class="page-subtitle">Katalog SPP, iuran, kas kelas, dan biaya non-rutin lainnya</p>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn-header-primary" @click="openModal()">+ Tambah jenis</button>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="error" class="error-banner">{{ error }}</div>
        <div v-if="success" class="success-banner">{{ success }}</div>

        <div v-if="!loading && items.length === 0" class="content-card">
          <h3 style="margin:0 0 0.5rem;font-size:1rem">Mulai cepat</h3>
          <p class="muted" style="margin:0 0 0.85rem;font-size:0.9rem">Pilih preset umum, lalu sesuaikan nominal.</p>
          <div class="preset-row">
            <button
              v-for="p in feeTypePresets"
              :key="p.code"
              type="button"
              class="preset-chip"
              :disabled="seeding"
              @click="applyPreset(p)"
            >+ {{ p.name }}</button>
          </div>
        </div>

        <div class="content-card filters-bar">
          <div class="filter-field" style="flex:1;min-width:180px">
            <label class="filter-label">Cari</label>
            <input v-model="filters.search" class="filter-input" placeholder="Nama / kode" @keyup.enter="reloadFromStart" />
          </div>
          <div class="filter-field">
            <label class="filter-label">Frekuensi</label>
            <select v-model="filters.frequency" class="filter-select" @change="reloadFromStart">
              <option value="">Semua</option>
              <option v-for="o in frequencyOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>
          <button type="button" class="btn-secondary" @click="reloadFromStart">Terapkan</button>
        </div>

        <div class="content-card">
          <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="6" /></div>
          <div v-else-if="items.length === 0" class="empty-state">
            <h3>Belum ada jenis biaya</h3>
            <p>Buat SPP (bulanan), iuran sekali, atau kas kelas (sesekali).</p>
            <button type="button" class="btn-primary" @click="openModal()">Tambah jenis biaya</button>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Kode</th>
                  <th>Frekuensi</th>
                  <th>Cakupan</th>
                  <th class="num">Default</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in items" :key="row.id">
                  <td>
                    <strong>{{ row.name }}</strong>
                    <div v-if="row.description" class="muted" style="font-size:0.8rem">{{ row.description }}</div>
                  </td>
                  <td>{{ row.code || '—' }}</td>
                  <td>{{ frequencyLabel(row.frequency) }}</td>
                  <td>{{ scopeLabel(row.scope) }}</td>
                  <td class="num">{{ formatRp(row.default_amount) }}</td>
                  <td>
                    <span :class="['pill', row.is_active ? 'pill-on' : 'pill-off']">
                      {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td>
                    <TableAction kind="edit" @click="openModal(row)" />
                    <TableAction kind="delete" @click="remove(row)" />
                  </td>
                </tr>
              </tbody>
            </table>
            <PaginationBar
              :page="meta.current_page"
              :last-page="meta.last_page"
              :per-page="meta.per_page"
              :total="meta.total"
              item-label="jenis biaya"
              @page-change="goPage"
              @per-page-change="changePerPage"
            />
          </div>
        </div>
      </main>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card">
          <h3>{{ editing ? 'Edit jenis biaya' : 'Tambah jenis biaya' }}</h3>
          <div v-if="modalError" class="modal-error">{{ modalError }}</div>
          <form @submit.prevent="save">
            <div class="form-grid">
              <div class="form-group full">
                <label>Nama *</label>
                <input v-model="form.name" class="form-input" required maxlength="255" placeholder="Contoh: SPP / Kas kelas 7A / Iuran study tour" />
              </div>
              <div class="form-group">
                <label>Kode</label>
                <input v-model="form.code" class="form-input" maxlength="50" placeholder="opsional, unik" />
              </div>
              <div class="form-group">
                <label>Nominal default</label>
                <input v-model.number="form.default_amount" type="number" min="0" step="1000" class="form-input" />
              </div>
              <div class="form-group">
                <label>Frekuensi *</label>
                <select v-model="form.frequency" class="form-select" required>
                  <option v-for="o in frequencyOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Cakupan *</label>
                <select v-model="form.scope" class="form-select" required>
                  <option v-for="o in scopeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
              </div>
              <div class="form-group full">
                <label>Deskripsi</label>
                <textarea v-model="form.description" class="form-textarea" />
              </div>
              <div class="form-group">
                <label>
                  <input v-model="form.is_active" type="checkbox" /> Aktif
                </label>
              </div>
            </div>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { financeFeeTypeApi } from '@/api/finance'
import {
  frequencyOptions,
  scopeOptions,
  frequencyLabel,
  scopeLabel,
  formatRp,
  feeTypePresets,
} from './keuanganConstants'
import { apiError } from './keuanganErrors'
import './keuangan.css'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const seeding = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const editing = ref(null)
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const filters = reactive({ search: '', frequency: '', page: 1 })
const form = reactive({
  name: '',
  code: '',
  description: '',
  frequency: 'one_time',
  scope: 'school',
  default_amount: 0,
  is_active: true,
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = { per_page: meta.per_page || 15, page: filters.page }
    if (filters.search) params.search = filters.search
    if (filters.frequency) params.frequency = filters.frequency
    const res = await financeFeeTypeApi.getAll(params)
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat jenis biaya.')
  } finally {
    loading.value = false
  }
}

function goPage(page) {
  filters.page = page
  load()
}

function changePerPage(n) {
  meta.per_page = n
  filters.page = 1
  load()
}

function reloadFromStart() {
  filters.page = 1
  load()
}

function openModal(row = null) {
  editing.value = row
  modalError.value = ''
  form.name = row?.name || ''
  form.code = row?.code || ''
  form.description = row?.description || ''
  form.frequency = row?.frequency || 'one_time'
  form.scope = row?.scope || 'school'
  form.default_amount = Number(row?.default_amount || 0)
  form.is_active = row?.is_active !== false
  showModal.value = true
}

async function applyPreset(preset) {
  seeding.value = true
  error.value = ''
  success.value = ''
  try {
    await financeFeeTypeApi.create({ ...preset, is_active: true })
    success.value = `Jenis "${preset.name}" ditambahkan.`
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menambah preset.')
  } finally {
    seeding.value = false
  }
}

async function save() {
  saving.value = true
  modalError.value = ''
  error.value = ''
  success.value = ''
  try {
    const payload = { ...form, code: form.code || null }
    if (editing.value) {
      await financeFeeTypeApi.update(editing.value.id, payload)
      success.value = 'Jenis biaya diperbarui.'
    } else {
      await financeFeeTypeApi.create(payload)
      success.value = 'Jenis biaya ditambahkan.'
    }
    showModal.value = false
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`Hapus jenis biaya "${row.name}"?`)) return
  try {
    await financeFeeTypeApi.delete(row.id)
    success.value = 'Jenis biaya dihapus.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menghapus.')
  }
}

onMounted(load)
</script>
