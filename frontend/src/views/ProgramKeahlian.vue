<template>
  <Layout>
    <div class="partners-page">
      <header class="page-header">
        <div class="header-content">
          <div class="header-text">
            <h1 class="page-title">Program Keahlian</h1>
            <p class="page-subtitle">Master jurusan SMK/MAK — dipakai kelas dan Kaprog</p>
          </div>
          <button type="button" class="btn-primary btn-header" @click="openModal()">Tambah Program</button>
        </div>
      </header>

      <div v-if="!isSmk" class="empty-state">
        <h3>Hanya untuk SMK/MAK</h3>
        <p>Program keahlian tidak berlaku untuk jenjang institusi ini.</p>
      </div>

      <template v-else>
        <div class="toolbar">
          <input v-model="filters.search" type="text" class="search-input" placeholder="Cari kode atau nama..." @input="debounceLoad" />
          <select v-model="filters.status" class="filter-select" @change="loadList">
            <option value="">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Nonaktif">Nonaktif</option>
          </select>
        </div>

        <div v-if="loading" class="loading-wrap"><p>Memuat program keahlian...</p></div>
        <div v-else-if="!list.length" class="empty-state">
          <h3>Belum ada program keahlian</h3>
          <p>Tambahkan jurusan (mis. TKJ, RPL) lalu tautkan ke kelas dan Kaprog.</p>
          <button type="button" class="btn-primary" @click="openModal()">Tambah Program</button>
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in list" :key="item.id">
                <td>{{ item.code || '—' }}</td>
                <td>
                  <strong>{{ item.name }}</strong>
                  <div v-if="item.description" class="cell-sub">{{ item.description }}</div>
                </td>
                <td>{{ item.classes_count ?? 0 }}</td>
                <td><span class="status-chip" :class="item.status === 'Aktif' ? 'ok' : 'off'">{{ item.status }}</span></td>
                <td class="col-aksi">
                  <TableAction kind="edit" @click="openModal(item)" />
                  <TableAction kind="delete" @click="remove(item)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-card">
          <h2>{{ form.id ? 'Edit Program' : 'Tambah Program' }}</h2>
          <form @submit.prevent="save">
            <label>Kode</label>
            <input v-model="form.code" maxlength="50" placeholder="Contoh: TKJ" />
            <label>Nama *</label>
            <input v-model="form.name" required maxlength="255" placeholder="Teknik Komputer dan Jaringan" />
            <label>Status</label>
            <select v-model="form.status">
              <option value="Aktif">Aktif</option>
              <option value="Nonaktif">Nonaktif</option>
            </select>
            <label>Urutan</label>
            <input v-model.number="form.sort_order" type="number" min="0" max="9999" />
            <label>Deskripsi</label>
            <textarea v-model="form.description" rows="2"></textarea>
            <p v-if="error" class="form-error">{{ error }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { programKeahlianApi } from '@/api/programKeahlian'
import { useAuthStore } from '@/stores/auth'
import { getActiveInstitutionLevel, isVocationalLevel } from '@/utils/institution'
import '@/assets/module-page.css'

const authStore = useAuthStore()
const isSmk = computed(() => isVocationalLevel(getActiveInstitutionLevel(authStore)))

const list = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const error = ref('')
const filters = reactive({ search: '', status: '' })
const form = reactive({
  id: null,
  code: '',
  name: '',
  status: 'Aktif',
  sort_order: 0,
  description: '',
})

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(loadList, 300)
}

async function loadList() {
  if (!isSmk.value) return
  loading.value = true
  try {
    const res = await programKeahlianApi.getAll({
      search: filters.search || undefined,
      status: filters.status || undefined,
      per_page: 100,
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
  Object.assign(form, {
    id: item?.id || null,
    code: item?.code || '',
    name: item?.name || '',
    status: item?.status || 'Aktif',
    sort_order: item?.sort_order ?? 0,
    description: item?.description || '',
  })
  showModal.value = true
}

async function save() {
  saving.value = true
  error.value = ''
  const payload = {
    code: form.code || null,
    name: form.name,
    status: form.status,
    sort_order: form.sort_order ?? 0,
    description: form.description || null,
  }
  try {
    if (form.id) await programKeahlianApi.update(form.id, payload)
    else await programKeahlianApi.create(payload)
    showModal.value = false
    await loadList()
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal menyimpan program keahlian.'
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(`Hapus program "${item.name}"?`)) return
  try {
    await programKeahlianApi.delete(item.id)
    await loadList()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus program keahlian.')
  }
}

onMounted(loadList)
</script>

<style scoped>
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
  background: #fff; border-radius: 12px; padding: 1.25rem; width: min(480px, 100%); max-height: 90vh; overflow: auto;
}
.modal-card label { display: block; margin: 0.75rem 0 0.25rem; font-size: 0.85rem; font-weight: 600; }
.modal-card input, .modal-card select, .modal-card textarea {
  width: 100%; padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;
}
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-error { color: #dc2626; font-size: 0.875rem; margin-top: 0.5rem; }
</style>
