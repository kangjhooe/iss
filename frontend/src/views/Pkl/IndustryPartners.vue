<template>    <div class="partners-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">
              Mitra DU/DI
            </h1>
            <p class="page-subtitle">Master perusahaan/industri untuk PKL dan BKK</p>
          </div>
          <button type="button" class="btn-primary btn-header" @click="openModal()">Tambah Mitra</button>
        </div>
      </header>

      <div class="toolbar">
        <input v-model="filters.search" type="text" class="search-input" placeholder="Cari nama, bidang, kota..." @input="debounceLoad" />
        <select v-model="filters.status" class="filter-select" @change="loadList">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Nonaktif">Nonaktif</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap"><p>Memuat mitra...</p></div>
      <div v-else-if="!list.length" class="empty-state">
        <h3>Belum ada mitra</h3>
        <p>Tambahkan perusahaan mitra untuk penempatan PKL atau lowongan BKK.</p>
        <button type="button" class="btn-primary" @click="openModal()">Tambah Mitra</button>
      </div>
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Bidang</th>
              <th>Kota</th>
              <th>PIC</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in list" :key="item.id">
              <td>
                <strong>{{ item.name }}</strong>
                <div v-if="item.phone || item.email" class="cell-sub">{{ item.phone || item.email }}</div>
              </td>
              <td>{{ item.business_field || '—' }}</td>
              <td>{{ item.district || item.city || '—' }}</td>
              <td>{{ item.pic_name || '—' }}</td>
              <td><span class="status-chip" :class="item.status === 'Aktif' ? 'ok' : 'off'">{{ item.status }}</span></td>
              <td class="col-aksi">
                <TableAction kind="edit" @click="openModal(item)" />
                <TableAction kind="delete" @click="remove(item)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card">
          <h2>{{ form.id ? 'Edit Mitra' : 'Tambah Mitra' }}</h2>
          <form @submit.prevent="save">
            <label>Nama perusahaan *</label>
            <input v-model="form.name" required maxlength="255" />
            <label>Bidang usaha</label>
            <input v-model="form.business_field" maxlength="255" />
            <AddressCascade v-model="form" />
            <div class="form-row">
              <div>
                <label>Telepon</label>
                <input v-model="form.phone" />
              </div>
              <div>
                <label>Email</label>
                <input v-model="form.email" type="email" />
              </div>
            </div>
            <div class="form-row">
              <div>
                <label>Nama PIC</label>
                <input v-model="form.pic_name" />
              </div>
              <div>
                <label>Telepon PIC</label>
                <input v-model="form.pic_phone" />
              </div>
            </div>
            <label>Status</label>
            <select v-model="form.status">
              <option value="Aktif">Aktif</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
            <label>Catatan</label>
            <textarea v-model="form.notes" rows="2"></textarea>
            <p v-if="error" class="form-error">{{ error }}</p>
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
import AddressCascade from '@/components/AddressCascade.vue'
import { industryPartnersApi } from '@/api/industryPartners'
import { emptyAddress, pickAddress } from '@/utils/addressFields'
import '@/assets/module-page.css'

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const error = ref('')
const filters = reactive({ search: '', status: '' })
function blankForm() {
  return {
    id: null,
    name: '',
    business_field: '',
    ...emptyAddress(),
    city: '',
    phone: '',
    email: '',
    pic_name: '',
    pic_phone: '',
    status: 'Aktif',
    notes: '',
  }
}

const form = ref(blankForm())

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(loadList, 300)
}

async function loadList() {
  loading.value = true
  try {
    const res = await industryPartnersApi.getAll({
      search: filters.search || undefined,
      status: filters.status || undefined,
      per_page: 50,
    })
    list.value = res.data?.data || res.data || []
  } catch {
    list.value = []
  } finally {
    loading.value = false
  }
}

function openModal(item = null) {
  error.value = ''
  if (!item) {
    form.value = blankForm()
    showModal.value = true
    return
  }
  const address = pickAddress(item)
  if (!address.district && item.city) {
    address.district = item.city
  }
  form.value = {
    ...blankForm(),
    id: item.id || null,
    name: item.name || '',
    business_field: item.business_field || '',
    ...address,
    city: item.city || address.district || '',
    phone: item.phone || '',
    email: item.email || '',
    pic_name: item.pic_name || '',
    pic_phone: item.pic_phone || '',
    status: item.status || 'Aktif',
    notes: item.notes || '',
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  error.value = ''
  const payload = { ...form.value }
  delete payload.id
  try {
    if (form.value.id) await industryPartnersApi.update(form.value.id, payload)
    else await industryPartnersApi.create(payload)
    showModal.value = false
    await loadList()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan mitra.'
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`Hapus mitra "${item.name}"?`)) return
  try {
    await industryPartnersApi.delete(item.id)
    await loadList()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus mitra.')
  }
}

onMounted(loadList)
</script>

<style scoped>
.beta-badge {
  display: inline-block;
  margin-left: 0.4rem;
  padding: 0.1rem 0.45rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  vertical-align: middle;
  color: #92400e;
  background: #fef3c7;
  border-radius: 999px;
}
.toolbar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input, .filter-select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; }
.search-input { min-width: 220px; flex: 1; }
.cell-sub { font-size: 0.8rem; color: #6b7280; }
.status-chip { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; }
.status-chip.ok { background: #d1fae5; color: #065f46; }
.status-chip.off { background: #f3f4f6; color: #4b5563; }
.col-aksi { white-space: nowrap; }
.btn-link { background: none; border: none; color: #2563eb; cursor: pointer; margin-right: 0.5rem; }
.btn-link.danger { color: #dc2626; }
.empty-state { text-align: center; padding: 3rem 1rem; background: #fff; border-radius: 12px; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;
}
.modal-card {
  background: #fff; border-radius: 12px; padding: 1.25rem; width: min(640px, 100%); max-height: 90vh; overflow: auto;
}
.modal-card label { display: block; margin: 0.75rem 0 0.25rem; font-size: 0.85rem; font-weight: 600; }
.modal-card input, .modal-card select, .modal-card textarea {
  width: 100%; padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem; }
@media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
</style>
