<template>
  <Layout>
    <div class="releases-page">
      <div class="page-header">
        <div class="header-content">
          <div class="heading-block">
            <span class="eyebrow">Konten publik</span>
            <h2>Catatan Rilis</h2>
            <p>Bagikan perkembangan dan pembaruan aplikasi kepada pengguna.</p>
          </div>
          <div class="header-actions">
            <a href="/catatan-rilis" target="_blank" rel="noopener" class="btn-secondary btn-compact">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M14 5H19V10M19 5L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M19 14V17C19 18.1046 18.1046 19 17 19H7C5.89543 19 5 18.1046 5 17V7C5 5.89543 5.89543 5 7 5H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>Lihat halaman publik</span>
            </a>
            <button type="button" class="btn-primary btn-compact" @click="openCreate">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>Tambah rilis</span>
            </button>
          </div>
        </div>
      </div>

      <div class="release-toolbar">
        <div>
          <span class="toolbar-label">Status publikasi</span>
          <div class="status-tabs" role="group" aria-label="Filter status catatan rilis">
            <button
              v-for="option in statusOptions"
              :key="option.value"
              type="button"
              class="status-tab"
              :class="{ active: statusFilter === option.value }"
              :aria-pressed="statusFilter === option.value"
              @click="setStatusFilter(option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
        <span v-if="!loading" class="result-count">{{ items.length }} rilis ditampilkan</span>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="list" :items="5" />
      </div>

      <div v-else-if="items.length === 0" class="empty-state">
        <div class="empty-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            <path d="M14 2V8H20M8 13H16M8 17H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3>{{ statusFilter === 'all' ? 'Belum ada catatan rilis' : 'Tidak ada rilis dengan status ini' }}</h3>
        <p>{{ statusFilter === 'all' ? 'Buat catatan pertama untuk membagikan perkembangan aplikasi.' : 'Pilih status lain atau buat catatan rilis baru.' }}</p>
        <button type="button" class="btn-primary" @click="openCreate">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Tambah rilis
        </button>
      </div>

      <div v-else class="releases-list">
        <article
          v-for="item in items"
          :key="item.id"
          class="release-card"
          :class="{ 'release-card--published': item.is_published, 'release-card--draft': !item.is_published }"
        >
          <div class="card-top">
            <div class="card-heading">
              <div class="release-icon" :class="{ published: item.is_published }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                  <path d="M14 2V8H20M8 13H16M8 17H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <div class="title-row">
                  <h3>{{ item.title }}</h3>
                  <span class="badge" :class="item.is_published ? 'badge-published' : 'badge-draft'">
                    <span class="badge-dot"></span>
                    {{ item.is_published ? 'Tayang' : 'Draft' }}
                  </span>
                </div>
                <div class="meta-row">
                  <span class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M7 3V6M17 3V6M4 9H20M5 5H19C19.5523 5 20 5.44772 20 6V19C20 19.5523 19.5523 20 19 20H5C4.44772 20 4 19.5523 4 19V6C4 5.44772 4.44772 5 5 5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    {{ formatDate(item.released_at) }}
                  </span>
                  <span v-if="item.version" class="badge badge-version">v{{ item.version }}</span>
                </div>
              </div>
            </div>
            <div class="card-actions" aria-label="Aksi catatan rilis">
              <button type="button" class="action-btn" title="Edit catatan" aria-label="Edit catatan rilis" @click="openEdit(item)">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M13.5 6.5L17.5 10.5M4 20L8.5 19L19 8.5C20.1046 7.39543 20.1046 5.60457 19 4.5C17.8954 3.39543 16.1046 3.39543 15 4.5L4.5 15L4 20Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Edit</span>
              </button>
              <button
                type="button"
                class="action-btn"
                :class="{ 'action-btn--unpublish': item.is_published }"
                :title="item.is_published ? 'Tarik dari publik' : 'Publikasikan'"
                :aria-label="item.is_published ? 'Tarik catatan dari halaman publik' : 'Publikasikan catatan rilis'"
                @click="togglePublish(item)"
                :disabled="togglingId === item.id"
              >
                <svg v-if="item.is_published" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M3 3L21 21M10.6 10.7C10.2144 11.0668 10 11.5182 10 12C10 13.1046 10.8954 14 12 14C12.4818 14 12.9332 13.7856 13.3 13.4M9.9 4.24C10.5883 4.0789 11.2918 3.99835 12 4C16.5 4 20 8 21 12C20.6579 13.2547 20.0731 14.432 19.28 15.46M6.61 6.61C4.62 7.95 3.44 10 3 12C4 16 7.5 20 12 20C13.58 20 15 19.5 16.21 18.67" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M2 12C3.5 7.5 7 5 12 5C17 5 20.5 7.5 22 12C20.5 16.5 17 19 12 19C7 19 3.5 16.5 2 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
                </svg>
                <span>{{ togglingId === item.id ? 'Memproses...' : (item.is_published ? 'Tarik' : 'Publikasikan') }}</span>
              </button>
              <button type="button" class="action-btn action-btn--danger" title="Hapus catatan" aria-label="Hapus catatan rilis" @click="askDelete(item)">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 7H20M9 11V17M15 11V17M6 7L7 20H17L18 7M9 7V4H15V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Hapus</span>
              </button>
            </div>
          </div>
          <div class="release-content">
            <span class="content-label">Yang baru</span>
            <ul class="items-preview">
              <li v-for="(point, idx) in item.items" :key="idx">
                <span class="item-check" aria-hidden="true">✓</span>
                <span>{{ point }}</span>
              </li>
            </ul>
          </div>
          <div class="card-foot">
            <span v-if="item.creator">
              Dibuat oleh <strong>{{ item.creator.name }}</strong>
            </span>
            <span v-if="item.published_at">Tayang {{ formatDateTime(item.published_at) }}</span>
            <span v-else>Belum ditampilkan ke publik</span>
          </div>
        </article>
      </div>

      <div v-if="showForm" class="modal-overlay" @click.self="closeForm">
        <div class="modal-content" role="dialog" aria-modal="true" :aria-labelledby="editingId ? 'edit-release-title' : 'create-release-title'">
          <div class="modal-header">
            <div>
              <h3 :id="editingId ? 'edit-release-title' : 'create-release-title'">
                {{ editingId ? 'Edit catatan rilis' : 'Tambah catatan rilis' }}
              </h3>
              <p>{{ editingId ? 'Perbarui informasi yang akan dilihat pengguna.' : 'Tulis ringkasan perubahan terbaru aplikasi.' }}</p>
            </div>
            <button type="button" class="btn-close" aria-label="Tutup formulir" @click="closeForm">×</button>
          </div>
          <form class="modal-body" @submit.prevent="handleSave">
            <div class="form-group">
              <label>Judul *</label>
              <input v-model="form.title" type="text" required maxlength="200" class="form-control" placeholder="Update Juli 2026" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Versi</label>
                <input v-model="form.version" type="text" maxlength="50" class="form-control" placeholder="0.3.26" />
              </div>
              <div class="form-group">
                <label>Tanggal rilis *</label>
                <input v-model="form.released_at" type="date" required class="form-control" />
              </div>
            </div>
            <div class="form-group">
              <label>Poin pembaruan *</label>
              <p class="field-hint">Gunakan satu poin untuk satu perubahan agar mudah dibaca.</p>
              <div class="items-editor">
                <div v-for="(point, idx) in form.items" :key="idx" class="item-row">
                  <input
                    v-model="form.items[idx]"
                    type="text"
                    required
                    maxlength="500"
                    class="form-control"
                    :placeholder="`Poin ${idx + 1}`"
                  />
                  <button
                    type="button"
                    class="btn-icon"
                    :disabled="form.items.length <= 1"
                    @click="removeItem(idx)"
                    aria-label="Hapus poin"
                  >
                    ×
                  </button>
                </div>
                <button type="button" class="btn-add-point" @click="addItem">
                  <span aria-hidden="true">+</span> Tambah poin
                </button>
              </div>
            </div>
            <div class="publish-option">
              <label>
                <input v-model="form.is_published" type="checkbox" />
                <span class="toggle-control" aria-hidden="true"></span>
                <span>
                  <strong>Publikasikan sekarang</strong>
                  <small>Catatan langsung tampil di halaman publik setelah disimpan.</small>
                </span>
              </label>
            </div>
            <p v-if="error" class="form-error">{{ error }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeForm">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : (editingId ? 'Simpan perubahan' : 'Simpan rilis') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="showDelete"
        title="Hapus Catatan Rilis"
        :message="deleteTarget ? `Hapus ${deleteTarget.title}?` : ''"
        warning="Entri ini akan hilang dari halaman publik."
        confirm-text="Hapus"
        :loading="deleting"
        @confirm="confirmDelete"
        @cancel="showDelete = false"
        @update:show="showDelete = $event"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const togglingId = ref(null)
const items = ref([])
const statusFilter = ref('all')
const showForm = ref(false)
const showDelete = ref(false)
const deleteTarget = ref(null)
const editingId = ref(null)
const error = ref('')

const statusOptions = [
  { value: 'all', label: 'Semua' },
  { value: 'published', label: 'Tayang' },
  { value: 'draft', label: 'Draft' },
]

const emptyForm = () => ({
  title: '',
  version: '',
  released_at: new Date().toISOString().slice(0, 10),
  items: [''],
  is_published: true,
})

const form = ref(emptyForm())

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso + (iso.includes('T') ? '' : 'T00:00:00')).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const formatDateTime = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadItems = async () => {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value
    }
    const res = await superAdminPlatformApi.getReleases(params)
    items.value = res.data?.data || []
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat catatan rilis')
  } finally {
    loading.value = false
  }
}

const setStatusFilter = (status) => {
  if (statusFilter.value === status) return
  statusFilter.value = status
  loadItems()
}

const openCreate = () => {
  editingId.value = null
  form.value = emptyForm()
  error.value = ''
  showForm.value = true
}

const openEdit = (item) => {
  editingId.value = item.id
  form.value = {
    title: item.title || '',
    version: item.version || '',
    released_at: item.released_at || '',
    items: item.items?.length ? [...item.items] : [''],
    is_published: !!item.is_published,
  }
  error.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  editingId.value = null
  error.value = ''
  form.value = emptyForm()
}

const addItem = () => {
  form.value.items.push('')
}

const removeItem = (idx) => {
  if (form.value.items.length <= 1) return
  form.value.items.splice(idx, 1)
}

const handleSave = async () => {
  error.value = ''
  const cleanedItems = form.value.items.map((s) => s.trim()).filter(Boolean)
  if (!form.value.title.trim()) {
    error.value = 'Judul wajib diisi'
    return
  }
  if (cleanedItems.length === 0) {
    error.value = 'Minimal satu poin pembaruan'
    return
  }

  saving.value = true
  try {
    const payload = {
      title: form.value.title.trim(),
      version: form.value.version.trim() || null,
      released_at: form.value.released_at,
      items: cleanedItems,
      is_published: !!form.value.is_published,
    }
    if (editingId.value) {
      await superAdminPlatformApi.updateRelease(editingId.value, payload)
      toast.success('Tersimpan', 'Catatan rilis diperbarui')
    } else {
      await superAdminPlatformApi.createRelease(payload)
      toast.success('Tersimpan', 'Catatan rilis ditambahkan')
    }
    closeForm()
    await loadItems()
  } catch (err) {
    const data = err.response?.data
    error.value = data?.message || data?.errors?.items?.[0] || 'Gagal menyimpan'
  } finally {
    saving.value = false
  }
}

const togglePublish = async (item) => {
  togglingId.value = item.id
  try {
    await superAdminPlatformApi.updateRelease(item.id, {
      is_published: !item.is_published,
    })
    toast.success('Berhasil', item.is_published ? 'Dijadikan draft' : 'Dipublikasikan')
    await loadItems()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal mengubah status')
  } finally {
    togglingId.value = null
  }
}

const askDelete = (item) => {
  deleteTarget.value = item
  showDelete.value = true
}

const confirmDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await superAdminPlatformApi.deleteRelease(deleteTarget.value.id)
    toast.success('Dihapus', 'Catatan rilis dihapus')
    showDelete.value = false
    deleteTarget.value = null
    await loadItems()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menghapus')
  } finally {
    deleting.value = false
  }
}

onMounted(loadItems)
</script>

<style scoped>
.releases-page {
  width: 100%;
  max-width: 1040px;
  padding-bottom: 32px;
}

.page-header {
  margin-bottom: 20px;
}

.header-content {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}

.heading-block {
  min-width: 0;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.eyebrow,
.toolbar-label,
.content-label {
  font-size: 11px;
  font-weight: 750;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}

.eyebrow {
  display: inline-block;
  margin-bottom: 5px;
  color: #059669;
}

.page-header h2 {
  margin-bottom: 5px;
  font-size: 26px;
  letter-spacing: -0.025em;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: nowrap;
}

.loading-wrap {
  padding: 8px 0;
}

.header-actions .btn-compact {
  min-height: 38px;
  padding: 8px 13px !important;
  border-radius: 10px;
  gap: 7px !important;
  font-size: 13px !important;
  font-weight: 650;
  text-decoration: none;
  white-space: nowrap;
}

.header-actions svg,
.empty-state .btn-primary svg {
  width: 16px;
  height: 16px;
  flex: 0 0 auto;
}

.btn-primary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 650;
  cursor: pointer;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.btn-primary {
  border: 1px solid #059669;
  background: #059669;
  color: #fff;
  box-shadow: 0 1px 2px rgba(5, 150, 105, 0.18);
}

.btn-primary:hover:not(:disabled) {
  border-color: #047857;
  background: #047857;
  box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);
}

.btn-primary:disabled {
  opacity: 0.62;
  cursor: not-allowed;
}

.btn-secondary {
  border: 1px solid #dbe3ec;
  background: #fff;
  color: #334155;
}

.btn-secondary:hover {
  border-color: #a8b5c5;
  background: #f8fafc;
  color: #0f172a;
}

.release-toolbar {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
  padding: 14px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.toolbar-label {
  display: block;
  margin-bottom: 7px;
  color: #64748b;
}

.status-tabs {
  display: inline-flex;
  max-width: 100%;
  gap: 3px;
  padding: 3px;
  overflow-x: auto;
  border-radius: 10px;
  background: #f1f5f9;
  -webkit-overflow-scrolling: touch;
}

.status-tab {
  min-height: 32px;
  padding: 6px 13px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-size: 12px;
  font-weight: 650;
  cursor: pointer;
  transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
}

.status-tab:hover {
  color: #334155;
}

.status-tab.active {
  background: #fff;
  color: #047857;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
}

.status-tab:focus-visible,
.action-btn:focus-visible,
.btn-primary:focus-visible,
.btn-secondary:focus-visible,
.btn-add-point:focus-visible,
.btn-close:focus-visible {
  outline: 2px solid #10b981;
  outline-offset: 2px;
}

.result-count {
  color: #94a3b8;
  font-size: 12px;
  white-space: nowrap;
}

.empty-state {
  text-align: center;
  padding: 56px 24px 64px;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
  background: linear-gradient(to bottom, #ffffff, #f8fafc);
}

.empty-icon {
  display: grid;
  width: 54px;
  height: 54px;
  margin: 0 auto 18px;
  place-items: center;
  border-radius: 16px;
  background: #ecfdf5;
  color: #059669;
}

.empty-icon svg {
  width: 27px;
  height: 27px;
}

.empty-state h3 {
  margin: 0 0 8px;
  color: #1e293b;
  font-size: 18px;
}

.empty-state p {
  max-width: 430px;
  margin: 0 auto 18px;
  line-height: 1.55;
}

.empty-state .btn-primary {
  gap: 7px;
}

.releases-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  animation: fadeIn 0.28s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.release-card {
  position: relative;
  overflow: hidden;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.release-card::before {
  position: absolute;
  inset: 0 auto 0 0;
  width: 3px;
  background: #f59e0b;
  content: '';
}

.release-card--published::before {
  background: #10b981;
}

.release-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 5px 16px rgba(15, 23, 42, 0.07);
}

.card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin: 0;
  padding: 20px 20px 16px 22px;
}

.card-heading {
  display: flex;
  min-width: 0;
  flex: 1 1 420px;
  gap: 12px;
}

.release-icon {
  display: grid;
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  place-items: center;
  border-radius: 10px;
  background: #fff7ed;
  color: #d97706;
}

.release-icon.published {
  background: #ecfdf5;
  color: #059669;
}

.release-icon svg {
  width: 20px;
  height: 20px;
}

.title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.card-top h3 {
  margin: 0;
  color: #172033;
  font-size: 17px;
  font-weight: 700;
  line-height: 1.35;
}

.meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 7px;
  gap: 7px;
  font-size: 13px;
  color: #64748b;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.meta-item svg {
  width: 14px;
  height: 14px;
  color: #94a3b8;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  line-height: 1.2;
}

.badge-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

.badge-published {
  background: #ecfdf5;
  color: #047857;
}

.badge-draft {
  background: #fff7ed;
  color: #b45309;
}

.badge-version {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #475569;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  align-items: center;
}

.action-btn {
  display: inline-flex;
  min-height: 32px;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 6px 8px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}

.action-btn svg {
  width: 15px;
  height: 15px;
}

.action-btn:hover:not(:disabled) {
  border-color: #cbd5e1;
  background: #f8fafc;
  color: #047857;
}

.action-btn--unpublish:hover:not(:disabled) {
  color: #b45309;
}

.action-btn--danger:hover:not(:disabled) {
  border-color: #fee2e2;
  background: #fef2f2;
  color: #dc2626;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: wait;
}

.release-content {
  margin: 0 20px 0 22px;
  padding: 14px 16px;
  border: 1px solid #edf1f5;
  border-radius: 11px;
  background: #fafcfd;
}

.content-label {
  display: block;
  margin-bottom: 9px;
  color: #64748b;
}

.items-preview {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.items-preview li {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  line-height: 1.5;
}

.item-check {
  display: grid;
  width: 17px;
  height: 17px;
  flex: 0 0 17px;
  margin-top: 2px;
  place-items: center;
  border-radius: 50%;
  background: #ecfdf5;
  color: #059669;
  font-size: 10px;
  font-weight: 800;
}

.card-foot {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 11px 20px 12px 22px;
  border-top: 1px solid #f1f5f9;
  background: #fcfdfe;
  font-size: 12px;
  color: #94a3b8;
}

.card-foot strong {
  color: #64748b;
  font-weight: 650;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
}

.modal-content {
  width: 100%;
  max-width: 620px;
  max-height: 90vh;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 19px 22px;
  border-bottom: 1px solid #e2e8f0;
  background: #fbfcfd;
}

.modal-header h3 {
  margin: 0;
  color: #172033;
  font-size: 18px;
  font-weight: 700;
}

.modal-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
}

.btn-close {
  display: grid;
  width: 40px;
  height: 40px;
  flex: 0 0 40px;
  place-items: center;
  border: none;
  background: transparent;
  border-radius: 10px;
  font-size: 24px;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  transition: background-color 0.16s ease, color 0.16s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.modal-body {
  max-height: calc(90vh - 78px);
  overflow-y: auto;
  padding: 22px;
}

.form-group {
  margin-bottom: 17px;
}

.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #334155;
  font-size: 12px;
  font-weight: 650;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.field-hint {
  margin: -2px 0 9px;
  color: #94a3b8;
  font-size: 11px;
}

.form-control {
  width: 100%;
  min-height: 41px;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #dbe3ec;
  border-radius: 10px;
  background: #fff;
  color: #1e293b;
  font-size: 14px;
  transition: border-color 0.16s ease, box-shadow 0.16s ease;
}

.items-editor {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-control:hover {
  border-color: #b8c4d2;
}

.form-control:focus {
  border-color: #10b981;
  outline: none;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.item-row {
  display: flex;
  align-items: stretch;
  gap: 8px;
}

.btn-icon {
  flex-shrink: 0;
  width: 41px;
  height: 41px;
  border: 1px solid #dbe3ec;
  border-radius: 8px;
  background: #fff;
  color: #94a3b8;
  font-size: 20px;
  cursor: pointer;
  transition: background-color 0.16s ease, color 0.16s ease, border-color 0.16s ease;
}

.btn-icon:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-icon:hover:not(:disabled) {
  border-color: #fecaca;
  background: #fef2f2;
  color: #dc2626;
}

.btn-add-point {
  align-self: flex-start;
  padding: 7px 10px;
  border: 0;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 12px;
  font-weight: 650;
  cursor: pointer;
}

.btn-add-point:hover {
  background: #d1fae5;
}

.publish-option {
  margin: 4px 0 18px;
  padding: 13px 14px;
  border: 1px solid #dbe3ec;
  border-radius: 12px;
  background: #f8fafc;
}

.publish-option label {
  display: flex;
  align-items: center;
  gap: 11px;
  margin: 0;
  cursor: pointer;
}

.publish-option input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}

.toggle-control {
  position: relative;
  width: 38px;
  height: 22px;
  flex: 0 0 38px;
  border-radius: 999px;
  background: #cbd5e1;
  transition: background-color 0.18s ease;
}

.toggle-control::after {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.28);
  content: '';
  transition: transform 0.18s ease;
}

.publish-option input:checked + .toggle-control {
  background: #10b981;
}

.publish-option input:checked + .toggle-control::after {
  transform: translateX(16px);
}

.publish-option input:focus-visible + .toggle-control {
  outline: 2px solid #10b981;
  outline-offset: 2px;
}

.publish-option strong,
.publish-option small {
  display: block;
}

.publish-option strong {
  color: #334155;
  font-size: 12px;
  font-weight: 650;
}

.publish-option small {
  margin-top: 2px;
  color: #64748b;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.4;
}

.form-error {
  margin: 0 0 12px;
  padding: 9px 11px;
  border: 1px solid #fecaca;
  border-radius: 9px;
  background: #fef2f2;
  color: #dc2626;
  font-size: 13px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin: 4px -22px -22px;
  padding: 15px 22px;
  border-top: 1px solid #e2e8f0;
  background: #fbfcfd;
}

/* Fluid safeguards shared by all breakpoints */
.heading-block,
.card-heading > div:last-child,
.title-row,
.items-preview li > span:last-child {
  min-width: 0;
}

.page-header h2,
.card-top h3,
.items-preview li > span:last-child {
  overflow-wrap: anywhere;
}

.modal-content {
  max-height: min(90vh, 90dvh);
}

.modal-body {
  max-height: calc(min(90vh, 90dvh) - 78px);
  overscroll-behavior: contain;
}

@media (max-width: 1024px) {
  .header-content {
    align-items: flex-start;
  }

  .header-actions {
    flex: 1 1 100%;
  }

  .header-actions .btn-compact {
    flex: 0 1 auto;
  }

  .card-heading {
    flex-basis: 360px;
  }
}

@media (max-width: 900px) {
  .release-toolbar {
    padding: 12px 14px;
  }

  .card-top {
    padding-right: 16px;
  }

  .card-actions {
    width: 100%;
    padding-left: 50px;
  }
}

@media (max-width: 768px) {
  .releases-page {
    max-width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .page-header {
    padding-top: 0;
  }

  .header-actions .btn-compact {
    flex: 1 1 0;
    min-width: 0;
    min-height: 36px;
    padding: 7px 10px !important;
  }

  .header-actions .btn-compact span {
    display: inline;
  }

  .release-toolbar {
    width: 100%;
    box-sizing: border-box;
    align-items: flex-start;
    flex-direction: column;
    gap: 10px;
  }

  .result-count {
    padding-left: 3px;
  }

  .card-top {
    padding: 16px 15px 14px 18px;
  }

  .card-heading {
    flex-basis: 100%;
  }

  .card-actions {
    width: 100%;
    padding-left: 50px;
  }

  .action-btn {
    min-height: 36px;
  }

  .release-content {
    margin-right: 15px;
    margin-left: 18px;
  }

  .card-foot {
    padding-right: 15px;
    padding-left: 18px;
  }

  .modal-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .modal-content {
    max-width: 100%;
    max-height: min(94vh, 94dvh);
    border-radius: 18px 18px 0 0;
  }

  .modal-body {
    max-height: calc(min(94vh, 94dvh) - 78px);
  }
}

@media (max-width: 600px) {
  .releases-page {
    padding-bottom: 20px;
  }

  .page-header {
    margin-bottom: 16px;
  }

  .header-content {
    gap: 14px;
  }

  .release-toolbar {
    margin-bottom: 14px;
  }

  .release-card {
    border-radius: 13px;
  }

  .card-heading {
    flex-basis: 100%;
  }

  .release-content {
    font-size: 13px;
  }

  .items-preview li {
    gap: 7px;
  }
}

@media (max-width: 480px) {
  .page-header h2 {
    font-size: 22px;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions .btn-compact {
    flex: 1 1 0;
    min-width: 0;
    font-size: 11px !important;
    white-space: normal;
    text-align: center;
    line-height: 1.2;
  }

  .status-tabs {
    width: 100%;
  }

  .status-tab {
    flex: 1 1 0;
    min-width: 0;
    padding-right: 10px;
    padding-left: 10px;
  }

  .result-count {
    white-space: normal;
  }

  .release-toolbar > div {
    width: 100%;
  }

  .release-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
  }

  .card-heading {
    gap: 9px;
  }

  .card-actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    padding-left: 43px;
  }

  .action-btn {
    padding: 6px;
  }

  .action-btn span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .release-content {
    padding: 12px;
  }

  .card-foot {
    align-items: flex-start;
    flex-direction: column;
    gap: 5px;
  }

  .modal-header,
  .modal-body {
    padding-right: 16px;
    padding-left: 16px;
  }

  .modal-actions {
    margin-right: -16px;
    margin-left: -16px;
    padding-right: 16px;
    padding-left: 16px;
  }

  .modal-actions > * {
    flex: 1 1 0;
    min-width: 0;
  }
}

@media (max-width: 380px) {
  .page-header h2 {
    font-size: 20px;
  }

  .page-header p {
    font-size: 12px;
    line-height: 1.45;
  }

  .header-actions {
    flex-direction: column;
  }

  .header-actions .btn-compact {
    width: 100%;
    flex-basis: auto;
  }

  .release-toolbar {
    padding: 10px;
  }

  .status-tabs {
    justify-content: flex-start;
  }

  .status-tab {
    min-width: 72px;
    flex: 0 0 auto;
  }

  .card-top {
    padding: 14px 12px 12px 15px;
  }

  .release-icon {
    width: 32px;
    height: 32px;
    flex-basis: 32px;
  }

  .release-icon svg {
    width: 18px;
    height: 18px;
  }

  .card-top h3 {
    font-size: 15px;
  }

  .card-actions {
    padding-left: 41px;
  }

  .action-btn {
    min-width: 0;
    min-height: 38px;
  }

  .action-btn span {
    display: none;
  }

  .action-btn svg {
    width: 17px;
    height: 17px;
  }

  .release-content {
    margin-right: 12px;
    margin-left: 15px;
  }

  .card-foot {
    padding-right: 12px;
    padding-left: 15px;
  }

  .modal-header {
    padding-top: 14px;
    padding-bottom: 14px;
  }

  .modal-header h3 {
    font-size: 16px;
  }

  .publish-option {
    padding: 11px;
  }
}

@media (max-height: 700px) {
  .modal-overlay {
    align-items: flex-start;
    padding: 8px;
  }

  .modal-content {
    max-height: calc(100vh - 16px);
    max-height: calc(100dvh - 16px);
  }

  .modal-body {
    max-height: calc(100vh - 90px);
    max-height: calc(100dvh - 90px);
  }
}

@media (max-width: 768px) and (max-height: 700px) {
  .modal-overlay {
    align-items: flex-end;
    padding: 0;
  }

  .modal-content {
    max-height: 100vh;
    max-height: 100dvh;
  }

  .modal-body {
    max-height: calc(100vh - 78px);
    max-height: calc(100dvh - 78px);
  }
}

@media (pointer: coarse) {
  .status-tab,
  .action-btn,
  .btn-add-point {
    min-height: 44px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .releases-list,
  .release-card,
  .btn-primary,
  .btn-secondary,
  .action-btn,
  .toggle-control,
  .toggle-control::after {
    animation: none;
    transition: none;
  }
}
</style>
