<template>
    <div class="subject-page">
      <div class="toolbar">
        <div class="filters filters-inline">
          <input
            v-model="filters.search"
            @input="debounceLoad"
            placeholder="Cari kode atau nama..."
            class="search-input"
          />
          <select v-model="filters.active_only" @change="onActiveFilterChange" class="filter-select">
            <option :value="true">Aktif saja</option>
            <option :value="false">Semua</option>
          </select>
        </div>
        <div class="toolbar-actions">
          <template v-if="selectedIds.length">
            <button
              type="button"
              class="btn-secondary btn-compact"
              :disabled="bulkWorking"
              @click="doBulkSetActive(true)"
            >
              {{ bulkWorking ? 'Memproses...' : `Aktifkan (${selectedIds.length})` }}
            </button>
            <button
              type="button"
              class="btn-secondary btn-compact"
              :disabled="bulkWorking"
              @click="doBulkSetActive(false)"
            >
              {{ bulkWorking ? 'Memproses...' : `Nonaktifkan (${selectedIds.length})` }}
            </button>
            <button
              type="button"
              class="btn-danger btn-compact"
              :disabled="bulkWorking"
              @click="confirmBulkDelete"
            >
              Hapus terpilih ({{ selectedIds.length }})
            </button>
          </template>
          <button @click="openAddModal" class="btn-primary btn-compact">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Mata Pelajaran</span>
          </button>
        </div>
      </div>

      <div v-if="error && !showModal" class="page-error">{{ error }}</div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="7" :cell-widths="['40px', '80px', '160px', '90px', '1fr', '80px', '120px']" />
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-check">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  title="Pilih semua di halaman ini"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="col-no">No</th>
              <th>Kode</th>
              <th>Nama</th>
              <th>Sumber</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in subjects" :key="item.id">
              <td class="col-check">
                <input type="checkbox" :value="item.id" v-model="selectedIds" />
              </td>
              <td class="col-no">{{ rowNumber(index) }}</td>
              <td>{{ displayValue(item.code) }}</td>
              <td>{{ displayValue(item.name) }}</td>
              <td>
                <span :class="item.subject_catalog_id || item.from_catalog ? 'badge-catalog' : 'badge-muted'">
                  {{ item.subject_catalog_id || item.from_catalog ? 'Katalog' : 'Manual' }}
                </span>
              </td>
              <td>{{ displayValue(item.description) }}</td>
              <td>
                <span :class="item.is_active ? 'badge-success' : 'badge-muted'">
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <TableAction kind="edit" @click="editSubject(item)" />
                  <TableAction kind="delete" @click="confirmDelete(item)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="subjects.length === 0" class="empty-state">
          <p>Belum ada mata pelajaran. Tambah data atau ubah filter.</p>
        </div>
        <PaginationBar
          v-if="pagination.total > 0"
          embedded
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :per-page="pagination.per_page"
          :total="pagination.total"
          item-label="mapel"
          @page-change="goToPage"
          @per-page-change="changePerPage"
        />
      </div>

      <!-- Add/Edit Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content subject-modal" @click.stop>
          <div class="subject-modal-hero">
            <div class="subject-modal-hero-text">
              <p class="subject-modal-eyebrow">{{ editing ? 'Perbarui data' : 'Tambah ke sekolah' }}</p>
              <h3>{{ editing ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}</h3>
              <p class="subject-modal-sub">
                {{ editing
                  ? 'Ubah kode, nama, atau status mapel sekolah Anda.'
                  : 'Pilih dari katalog standar agar siap dipakai bank soal & ujian online.' }}
              </p>
            </div>
            <button type="button" class="modal-close" aria-label="Tutup" @click="closeModal">×</button>
          </div>

          <form @submit.prevent="save" class="modal-body subject-modal-body">
            <div v-if="error" class="error-message">{{ error }}</div>

            <div v-if="!editing" class="mode-picker">
              <button
                type="button"
                :class="['mode-card', { active: createMode === 'catalog' }]"
                @click="setCreateMode('catalog')"
              >
                <span class="mode-card-icon catalog-icon" aria-hidden="true">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M4 6.5A2.5 2.5 0 016.5 4H20v14.5A1.5 1.5 0 0118.5 20H6.5A2.5 2.5 0 014 17.5v-11z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  </svg>
                </span>
                <span class="mode-card-copy">
                  <strong>Dari katalog</strong>
                  <small>Kode seragam · cocok untuk ujian online</small>
                </span>
              </button>
              <button
                type="button"
                :class="['mode-card', { active: createMode === 'manual' }]"
                @click="setCreateMode('manual')"
              >
                <span class="mode-card-icon manual-icon" aria-hidden="true">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  </svg>
                </span>
                <span class="mode-card-copy">
                  <strong>Input manual</strong>
                  <small>Untuk mapel khusus / muatan lokal</small>
                </span>
              </button>
            </div>

            <div v-if="!editing && createMode === 'catalog'" class="catalog-panel">
              <div class="catalog-toolbar">
                <div>
                  <p class="catalog-toolbar-title">Pilih mapel katalog</p>
                  <p v-if="catalogJenjangLabel" class="catalog-toolbar-meta">
                    Jenjang <span class="jenjang-chip">{{ catalogJenjangLabel }}</span>
                    <template v-if="institutionLevel"> · {{ institutionLevel }}</template>
                  </p>
                  <p v-else-if="!loadingCatalog" class="catalog-toolbar-warn">
                    Jenjang sekolah belum dikenali.
                  </p>
                </div>
                <div v-if="filteredAvailableCatalog.length" class="catalog-list-actions">
                  <button type="button" class="link-btn" @click="selectAllCatalog">Pilih semua</button>
                  <button type="button" class="link-btn" @click="clearCatalogSelection">Kosongkan</button>
                </div>
              </div>

              <div v-if="availableCatalog.length" class="catalog-search-wrap">
                <input
                  v-model="catalogSearch"
                  type="search"
                  class="form-input catalog-search"
                  placeholder="Cari kode atau nama mapel..."
                />
              </div>

              <div v-if="loadingCatalog" class="catalog-list empty">
                <div class="empty-visual spinner" aria-hidden="true"></div>
                <p>Memuat katalog jenjang Anda...</p>
              </div>
              <div v-else-if="!catalogJenjang" class="catalog-list empty">
                <div class="empty-visual warn" aria-hidden="true">!</div>
                <p>Lengkapi jenjang institusi agar katalog bisa ditampilkan.</p>
              </div>
              <div v-else-if="availableCatalog.length === 0" class="catalog-list empty">
                <div class="empty-visual ok" aria-hidden="true">OK</div>
                <p>Semua mapel katalog jenjang ini sudah ditambahkan, atau katalog belum diisi Super Admin.</p>
              </div>
              <div v-else-if="filteredAvailableCatalog.length === 0" class="catalog-list empty">
                <p>Tidak ada hasil untuk “{{ catalogSearch }}”.</p>
              </div>
              <div v-else class="catalog-list">
                <label
                  v-for="c in filteredAvailableCatalog"
                  :key="c.id"
                  :class="['catalog-item', { selected: selectedCatalogIds.includes(Number(c.id)) }]"
                >
                  <input
                    type="checkbox"
                    :value="Number(c.id)"
                    v-model="selectedCatalogIds"
                  />
                  <span class="catalog-item-check" aria-hidden="true"></span>
                  <span class="catalog-item-body">
                    <span class="catalog-item-code">{{ c.code }}</span>
                    <span class="catalog-item-name">{{ c.name }}</span>
                  </span>
                </label>
              </div>

              <div v-if="selectedCatalogIds.length" class="selection-bar">
                <span class="selection-count">{{ selectedCatalogIds.length }}</span>
                <span>mapel siap ditambahkan</span>
              </div>
            </div>

            <template v-if="editing || createMode === 'manual'">
              <div class="manual-panel">
                <div class="form-group">
                  <label>Kode <span class="required">*</span></label>
                  <input
                    v-model="form.code"
                    type="text"
                    required
                    class="form-input"
                    placeholder="Contoh: MAT, IPA"
                  />
                </div>
                <div class="form-group">
                  <label>Nama <span class="required">*</span></label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="form-input"
                    placeholder="Matematika, IPA"
                  />
                </div>
                <div class="form-group">
                  <label>Deskripsi</label>
                  <textarea v-model="form.description" rows="2" class="form-input" placeholder="Opsional"></textarea>
                </div>
                <div class="form-group" v-if="editing">
                  <label>Status</label>
                  <select v-model="form.is_active" class="form-input">
                    <option :value="true">Aktif</option>
                    <option :value="false">Nonaktif</option>
                  </select>
                  <p v-if="editing.subject_catalog_id || editing.from_catalog" class="field-hint">
                    Mapel ini berasal dari katalog (kode {{ editing.code }}).
                  </p>
                </div>
              </div>
            </template>

            <div class="modal-footer subject-modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="saving || catalogSaveDisabled" class="btn-primary">
                {{ saveButtonLabel }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="showConfirm"
        :title="bulkDeletePending ? 'Hapus Mata Pelajaran Terpilih' : 'Hapus Mata Pelajaran'"
        :message="bulkDeletePending ? bulkDeleteMessage : singleDeleteMessage"
        confirm-text="Hapus"
        :loading="bulkWorking"
        @confirm="bulkDeletePending ? doBulkDelete() : doDelete()"
        @cancel="cancelDelete"
      />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { subjectApi } from '@/api/subject'
import { subjectCatalogApi } from '@/api/subjectCatalog'
import { institutionApi } from '@/api/institution'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const subjects = ref([])
const loading = ref(false)
const saving = ref(false)
const bulkWorking = ref(false)
const error = ref('')
const showModal = ref(false)
const showConfirm = ref(false)
const bulkDeletePending = ref(false)
const editing = ref(null)
const toDelete = ref(null)
const selectedIds = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

const createMode = ref('catalog')
const catalogItems = ref([])
const existingSubjectsLite = ref([])
const selectedCatalogIds = ref([])
const catalogSearch = ref('')
const loadingCatalog = ref(false)
const resolvedInstitutionLevel = ref('')
const jenjangLabels = ref({
  SD: 'SD/MI',
  SMP: 'SMP/MTs',
  SMA: 'SMA/MA',
  SMK: 'SMK/MAK',
  PAUD: 'PAUD/TK',
})

const filters = reactive({
  search: '',
  active_only: true,
})

const allSelected = computed(() =>
  subjects.value.length > 0 && selectedIds.value.length === subjects.value.length
)
const someSelected = computed(() =>
  selectedIds.value.length > 0 && selectedIds.value.length < subjects.value.length
)
const singleDeleteMessage = 'Yakin ingin menghapus mata pelajaran ini? Jika sudah dipakai di jadwal, penghapusan akan ditolak.'
const bulkDeleteMessage = computed(() =>
  `Yakin ingin menghapus ${selectedIds.value.length} mata pelajaran yang dipilih? Jika sudah dipakai di jadwal, penghapusan akan ditolak.`
)

const institutionLevel = computed(() => {
  if (resolvedInstitutionLevel.value) return resolvedInstitutionLevel.value
  const active = authStore.activeInstitution
  const home = authStore.user?.institution
  return active?.level || home?.level || ''
})

const catalogJenjang = computed(() => jenjangFromLevel(institutionLevel.value))
const catalogJenjangLabel = computed(() => {
  if (!catalogJenjang.value) return ''
  return jenjangLabels.value[catalogJenjang.value] || catalogJenjang.value
})

const usedCatalogIds = computed(() => {
  const ids = new Set()
  for (const s of existingSubjectsLite.value) {
    if (s.subject_catalog_id) ids.add(Number(s.subject_catalog_id))
  }
  return ids
})

const availableCatalog = computed(() => {
  const usedCodes = new Set(
    existingSubjectsLite.value.map((s) => String(s.code || '').trim().toLowerCase()).filter(Boolean)
  )
  const jenjang = catalogJenjang.value
  return catalogItems.value.filter((c) => {
    // Hard filter: hanya jenjang sekolah
    if (jenjang && String(c.jenjang || '').toUpperCase() !== jenjang) return false
    if (usedCatalogIds.value.has(Number(c.id))) return false
    if (usedCodes.has(String(c.code || '').trim().toLowerCase())) return false
    return true
  })
})

const filteredAvailableCatalog = computed(() => {
  const q = catalogSearch.value.trim().toLowerCase()
  if (!q) return availableCatalog.value
  return availableCatalog.value.filter((c) => {
    const code = String(c.code || '').toLowerCase()
    const name = String(c.name || '').toLowerCase()
    return code.includes(q) || name.includes(q)
  })
})

const catalogSaveDisabled = computed(() => {
  if (editing.value || createMode.value !== 'catalog') return false
  return !catalogJenjang.value || selectedCatalogIds.value.length === 0 || loadingCatalog.value
})

const saveButtonLabel = computed(() => {
  if (saving.value) return 'Menyimpan...'
  if (!editing.value && createMode.value === 'catalog' && selectedCatalogIds.value.length > 1) {
    return `Tambah ${selectedCatalogIds.value.length} mapel`
  }
  return 'Simpan'
})

function jenjangFromLevel(level) {
  if (!level) return null
  const key = String(level).trim().toUpperCase()
  const map = {
    SD: 'SD',
    MI: 'SD',
    SMP: 'SMP',
    MTS: 'SMP',
    SMA: 'SMA',
    MA: 'SMA',
    SMK: 'SMK',
    MAK: 'SMK',
    PAUD: 'PAUD',
    TK: 'PAUD',
    KB: 'PAUD',
  }
  return map[key] || null
}

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function rowNumber(index) {
  const page = Number(pagination.value.current_page) || 1
  const perPage = Number(pagination.value.per_page) || 15
  return (page - 1) * perPage + index + 1
}

const form = reactive({
  code: '',
  name: '',
  description: '',
  is_active: true,
})

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    pagination.value.current_page = 1
    loadSubjects()
  }, 300)
}

function goToPage(page) {
  pagination.value.current_page = page
  loadSubjects()
}

function changePerPage(n) {
  pagination.value.per_page = n
  pagination.value.current_page = 1
  loadSubjects()
}

function onActiveFilterChange() {
  pagination.value.current_page = 1
  loadSubjects()
}

function toggleSelectAll(event) {
  if (event?.target?.checked) {
    selectedIds.value = subjects.value.map((s) => s.id)
  } else {
    selectedIds.value = []
  }
}

async function loadSubjects() {
  loading.value = true
  error.value = ''
  selectedIds.value = []
  try {
    const params = {
      active_only: filters.active_only,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    }
    if (filters.search) params.search = filters.search
    const res = await subjectApi.getAll(params)
    subjects.value = res.data.data ?? res.data ?? []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? pagination.value.current_page,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? subjects.value.length,
    }
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.code = ''
  form.name = ''
  form.description = ''
  form.is_active = true
  selectedCatalogIds.value = []
  catalogSearch.value = ''
}

function setCreateMode(mode) {
  createMode.value = mode
  resetForm()
  error.value = ''
  if (mode === 'catalog') {
    loadCatalog()
  }
}

function selectAllCatalog() {
  const visibleIds = filteredAvailableCatalog.value.map((c) => Number(c.id))
  const merged = new Set([...selectedCatalogIds.value, ...visibleIds])
  selectedCatalogIds.value = [...merged]
}

function clearCatalogSelection() {
  selectedCatalogIds.value = []
}

async function resolveInstitutionLevel() {
  const fromAuth =
    authStore.activeInstitution?.level
    || authStore.user?.institution?.level
    || ''
  if (fromAuth) {
    resolvedInstitutionLevel.value = fromAuth
    return fromAuth
  }

  try {
    const res = await institutionApi.getMy()
    const inst = res.data?.data ?? res.data ?? null
    const level = inst?.level || ''
    resolvedInstitutionLevel.value = level
    return level
  } catch {
    resolvedInstitutionLevel.value = ''
    return ''
  }
}

async function loadCatalog() {
  loadingCatalog.value = true
  selectedCatalogIds.value = []
  catalogItems.value = []
  error.value = ''
  try {
    await resolveInstitutionLevel()
    const jenjang = catalogJenjang.value
    if (!jenjang) {
      catalogItems.value = []
      return
    }

    try {
      const meta = await subjectCatalogApi.getMeta()
      const labels = meta.data?.data?.labels || meta.data?.labels
      if (labels && typeof labels === 'object') {
        jenjangLabels.value = { ...jenjangLabels.value, ...labels }
      }
    } catch {
      /* meta opsional */
    }

    const [catalogRes, subjectRes] = await Promise.all([
      subjectCatalogApi.getAll({
        active_only: true,
        per_page: 'all',
        jenjang,
      }),
      subjectApi.getAll({ active_only: false, per_page: 'all' }),
    ])

    const list = catalogRes.data?.data ?? catalogRes.data ?? []
    // Safety: hanya simpan item jenjang yang diminta
    catalogItems.value = (Array.isArray(list) ? list : []).filter(
      (c) => String(c.jenjang || '').toUpperCase() === jenjang
    )

    const existing = subjectRes.data?.data ?? subjectRes.data ?? []
    existingSubjectsLite.value = Array.isArray(existing) ? existing : []
  } catch (e) {
    catalogItems.value = []
    error.value = e.formattedMessage || e.message || 'Gagal memuat katalog mapel.'
  } finally {
    loadingCatalog.value = false
  }
}

async function openAddModal() {
  editing.value = null
  createMode.value = 'catalog'
  resetForm()
  error.value = ''
  showModal.value = true
  await loadCatalog()
}

function editSubject(item) {
  editing.value = item
  createMode.value = 'manual'
  selectedCatalogIds.value = []
  form.code = item.code
  form.name = item.name
  form.description = item.description || ''
  form.is_active = item.is_active ?? true
  error.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editing.value = null
  resetForm()
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editing.value) {
      await subjectApi.update(editing.value.id, {
        code: form.code,
        name: form.name,
        description: form.description || null,
        is_active: form.is_active,
      })
    } else if (createMode.value === 'catalog') {
      if (!catalogJenjang.value) {
        error.value = 'Jenjang sekolah belum dikenali. Lengkapi data institusi dulu.'
        return
      }
      const ids = [...selectedCatalogIds.value].map(Number).filter(Boolean)
      if (!ids.length) {
        error.value = 'Pilih minimal satu mata pelajaran dari katalog.'
        return
      }

      const results = await Promise.allSettled(
        ids.map((id) => subjectApi.create({
          subject_catalog_id: id,
          is_active: true,
        }))
      )
      const ok = results.filter((r) => r.status === 'fulfilled').length
      const fail = results.length - ok
      if (ok === 0) {
        const firstErr = results.find((r) => r.status === 'rejected')
        error.value = firstErr?.reason?.formattedMessage
          || firstErr?.reason?.response?.data?.message
          || 'Gagal menambahkan mapel dari katalog.'
        return
      }
      closeModal()
      await loadSubjects()
      if (fail > 0) {
        error.value = `${ok} mapel berhasil ditambahkan, ${fail} gagal.`
      }
      return
    } else {
      await subjectApi.create({
        code: form.code,
        name: form.name,
        description: form.description || null,
        is_active: form.is_active,
      })
    }
    closeModal()
    loadSubjects()
  } catch (e) {
    error.value = e.formattedMessage || e.response?.data?.message || e.message || 'Gagal menyimpan.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  toDelete.value = item
  bulkDeletePending.value = false
  showConfirm.value = true
}

function confirmBulkDelete() {
  if (!selectedIds.value.length) return
  toDelete.value = null
  bulkDeletePending.value = true
  showConfirm.value = true
}

function cancelDelete() {
  showConfirm.value = false
  toDelete.value = null
  bulkDeletePending.value = false
}

async function doDelete() {
  if (!toDelete.value) return
  try {
    await subjectApi.delete(toDelete.value.id)
    cancelDelete()
    loadSubjects()
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal menghapus.'
  }
}

async function doBulkDelete() {
  if (!selectedIds.value.length) return
  bulkWorking.value = true
  error.value = ''
  try {
    const ids = [...selectedIds.value]
    const results = await Promise.allSettled(ids.map((id) => subjectApi.delete(id)))
    const ok = results.filter((r) => r.status === 'fulfilled').length
    const fail = results.length - ok
    cancelDelete()
    selectedIds.value = []
    await loadSubjects()
    if (fail > 0) {
      error.value = `${ok} berhasil dihapus, ${fail} gagal (mungkin sudah dipakai di jadwal).`
    }
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal menghapus.'
  } finally {
    bulkWorking.value = false
  }
}

async function doBulkSetActive(isActive) {
  if (!selectedIds.value.length) return
  bulkWorking.value = true
  error.value = ''
  try {
    const ids = [...selectedIds.value]
    const results = await Promise.allSettled(
      ids.map((id) => subjectApi.update(id, { is_active: isActive }))
    )
    const ok = results.filter((r) => r.status === 'fulfilled').length
    const fail = results.length - ok
    selectedIds.value = []
    await loadSubjects()
    if (fail > 0) {
      error.value = `${ok} berhasil diubah, ${fail} gagal.`
    }
  } catch (e) {
    error.value = e.formattedMessage || e.message || 'Gagal mengubah status.'
  } finally {
    bulkWorking.value = false
  }
}

onMounted(loadSubjects)
</script>

<style scoped>
.subject-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 1.5rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.toolbar .filters { margin-bottom: 0; flex: 1; min-width: 200px; }
.toolbar-actions { display: flex; gap: 0.5rem; }
.btn-compact { display: inline-flex; align-items: center; gap: 0.5rem; }
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; }
.action-buttons-group { display: flex; gap: 0.5rem; }
.filters-inline { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.search-input { min-width: 200px; padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
.search-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
.filter-select { padding: 0.5rem 0.75rem; border: 2px solid #e2e8f0; border-radius: 6px; transition: border-color 0.2s, box-shadow 0.2s; }
.filter-select:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .action-buttons-group {
    width: 100%;
  }

  .action-buttons-group .btn-primary,
  .toolbar-actions {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .filters,
  .filters-inline {
    flex-direction: column;
    align-items: stretch;
  }
  .search-input,
  .filter-select {
    min-width: 0;
    width: 100%;
  }
}
.loading-state { text-align: center; padding: 2rem; }
.table-container { overflow-x: auto; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; }
.col-no {
  width: 52px;
  text-align: center;
  white-space: nowrap;
  color: #64748b;
  font-variant-numeric: tabular-nums;
}
.col-check {
  width: 40px;
  text-align: center;
}
.col-check input {
  cursor: pointer;
}
.badge-success { background: #c6f6d5; color: #276749; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.badge-muted { background: #e2e8f0; color: #4a5568; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.badge-catalog { background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.875rem; }
.action-buttons { display: flex; gap: 0.5rem; }
.btn-danger {
  background: #dc2626;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.25);
}
.btn-danger:hover:not(:disabled) { background: #b91c1c; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }
.page-error {
  margin-bottom: 0.75rem;
  padding: 0.65rem 0.85rem;
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 6px;
  font-size: 0.875rem;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content.subject-modal {
  background: #fff;
  border-radius: 18px;
  max-width: 580px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
  animation: subjectModalIn 0.2s ease-out;
}
@keyframes subjectModalIn {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.subject-modal-hero {
  position: relative;
  padding: 1.35rem 1.4rem 1.2rem;
  background:
    radial-gradient(circle at 90% 10%, rgba(255,255,255,0.22), transparent 40%),
    linear-gradient(135deg, #059669 0%, #047857 55%, #065f46 100%);
  color: #fff;
}
.subject-modal-eyebrow {
  margin: 0 0 0.25rem;
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  opacity: 0.85;
  font-weight: 700;
}
.subject-modal-hero h3 {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
}
.subject-modal-sub {
  margin: 0.4rem 0 0;
  font-size: 0.88rem;
  line-height: 1.4;
  opacity: 0.92;
  max-width: 42ch;
}
.subject-modal-hero .modal-close {
  position: absolute;
  top: 0.7rem;
  right: 0.75rem;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background: rgba(255,255,255,0.14);
  color: #fff;
  border: none;
  font-size: 1.4rem;
  line-height: 1;
  cursor: pointer;
}
.subject-modal-hero .modal-close:hover { background: rgba(255,255,255,0.24); }
.subject-modal-body {
  padding: 1.2rem 1.35rem 1.25rem;
  overflow-y: auto;
}
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; color: #334155; font-size: 0.875rem; }
.form-input {
  width: 100%;
  padding: 0.65rem 0.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.field-hint { margin: 0.4rem 0 0; font-size: 0.8rem; color: #64748b; line-height: 1.4; }

.mode-picker {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
  margin-bottom: 1.1rem;
}
.mode-card {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  text-align: left;
  padding: 0.85rem 0.9rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s, transform 0.15s;
}
.mode-card:hover {
  border-color: #a7f3d0;
  background: #f0fdf4;
}
.mode-card.active {
  border-color: #059669;
  background: linear-gradient(180deg, #ecfdf5 0%, #fff 100%);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}
.mode-card-icon {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.mode-card-icon.catalog-icon { background: #d1fae5; color: #047857; }
.mode-card-icon.manual-icon { background: #e2e8f0; color: #475569; }
.mode-card.active .mode-card-icon.manual-icon { background: #d1fae5; color: #047857; }
.mode-card-copy { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.mode-card-copy strong { font-size: 0.9rem; color: #0f172a; }
.mode-card-copy small { font-size: 0.75rem; color: #64748b; line-height: 1.3; }

.catalog-panel {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  padding: 0.9rem;
}
.catalog-toolbar {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: flex-start;
  margin-bottom: 0.7rem;
}
.catalog-toolbar-title {
  margin: 0;
  font-weight: 700;
  color: #0f172a;
  font-size: 0.95rem;
}
.catalog-toolbar-meta {
  margin: 0.3rem 0 0;
  font-size: 0.8rem;
  color: #64748b;
}
.catalog-toolbar-warn {
  margin: 0.3rem 0 0;
  font-size: 0.8rem;
  color: #b45309;
}
.jenjang-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  font-weight: 700;
  font-size: 0.75rem;
}
.catalog-list-actions { display: flex; gap: 0.75rem; flex-shrink: 0; }
.link-btn {
  background: none;
  border: none;
  color: #047857;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
}
.link-btn:hover { text-decoration: underline; }
.catalog-search-wrap { margin-bottom: 0.65rem; }
.catalog-search { background: #f8fafc; }

.catalog-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
}
.catalog-list.empty {
  padding: 1.5rem 1rem;
  color: #64748b;
  font-size: 0.875rem;
  text-align: center;
}
.empty-visual {
  width: 40px;
  height: 40px;
  margin: 0 auto 0.55rem;
  border-radius: 12px;
  background: #ecfdf5;
  color: #047857;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.75rem;
}
.empty-visual.warn { background: #fff7ed; color: #c2410c; }
.empty-visual.ok { background: #ecfdf5; color: #047857; }
.empty-visual.spinner {
  border: 2px solid #a7f3d0;
  border-top-color: #059669;
  background: transparent;
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.catalog-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.7rem 0.8rem;
  border-bottom: 1px solid #e2e8f0;
  cursor: pointer;
  transition: background 0.12s;
}
.catalog-item:last-child { border-bottom: none; }
.catalog-item:hover { background: #ecfdf5; }
.catalog-item.selected { background: #d1fae5; }
.catalog-item input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.catalog-item-check {
  width: 18px;
  height: 18px;
  border-radius: 6px;
  border: 1.5px solid #94a3b8;
  background: #fff;
  flex-shrink: 0;
  position: relative;
}
.catalog-item.selected .catalog-item-check {
  border-color: #059669;
  background: #059669;
}
.catalog-item.selected .catalog-item-check::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 2px;
  width: 5px;
  height: 9px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}
.catalog-item-body {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}
.catalog-item-code {
  font-variant-numeric: tabular-nums;
  color: #047857;
  font-weight: 700;
  font-size: 0.78rem;
}
.catalog-item-name {
  color: #0f172a;
  font-size: 0.9rem;
  font-weight: 500;
}
.selection-bar {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 0.75rem;
  border-radius: 10px;
  background: linear-gradient(90deg, #ecfdf5, #f0fdf4);
  color: #065f46;
  font-size: 0.85rem;
  font-weight: 600;
}
.selection-count {
  min-width: 1.6rem;
  height: 1.6rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: #059669;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}
.manual-panel {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 0.95rem;
  background: #fff;
}
.subject-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 1.1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.error-message {
  color: #b91c1c;
  margin-bottom: 0.85rem;
  font-size: 0.875rem;
  padding: 0.65rem 0.8rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
}
.empty-state { padding: 1.5rem; text-align: center; color: #64748b; }
.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  padding: 0.65rem 1.15rem;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}
.btn-primary:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: none; }
.btn-secondary {
  background: #f1f5f9;
  color: #334155;
  padding: 0.65rem 1.1rem;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
}
.btn-action { padding: 0.35rem 0.6rem; font-size: 0.875rem; border-radius: 4px; cursor: pointer; border: none; }
.btn-edit { background: rgba(5, 150, 105, 0.12); color: #059669; }
.btn-delete { background: #fed7d7; color: #c53030; }

@media (max-width: 640px) {
  .mode-picker { grid-template-columns: 1fr; }
  .catalog-toolbar { flex-direction: column; }
}
</style>
