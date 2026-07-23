<template>
  <Layout>
    <div class="page">
      <div class="page-header">
        <div>
          <h1>Perpustakaan Digital</h1>
          <p class="page-subtitle">Baca ebook PDF koleksi sekolah Anda</p>
        </div>
      </div>

      <div class="filters">
        <div class="filters-row">
          <div class="filter-group search-wrap">
            <label for="ebook-search">Cari</label>
            <input
              id="ebook-search"
              v-model="filters.search"
              type="search"
              class="filter-input"
              placeholder="Judul, pengarang, ISBN..."
              @input="debounceLoad"
            />
          </div>
          <div class="filter-group">
            <label for="ebook-category">Kategori</label>
            <select id="ebook-category" v-model="filters.category_id" class="filter-input" @change="loadEbooks(1)">
              <option value="">Semua kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Memuat katalog ebook...</p>
      </div>

      <div v-else-if="loadError" class="empty-state">
        <p>{{ loadError }}</p>
        <button type="button" class="btn-primary" @click="loadEbooks(1)">Coba lagi</button>
      </div>

      <div v-else-if="!ebooks.length" class="empty-state">
        <h3>Belum ada ebook</h3>
        <p>Pustakawan belum mengunggah buku PDF, atau tidak ada yang cocok dengan filter.</p>
        <router-link to="/student/dashboard" class="back-link">← Kembali ke Dashboard</router-link>
      </div>

      <div v-else class="ebook-grid">
        <article v-for="book in ebooks" :key="book.id" class="ebook-card">
          <div class="ebook-cover">
            <img v-if="book.cover_url" :src="book.cover_url" :alt="book.title" />
            <div v-else class="cover-placeholder" aria-hidden="true">PDF</div>
          </div>
          <div class="ebook-body">
            <h2 class="ebook-title">{{ book.title }}</h2>
            <p class="ebook-meta">{{ book.author || 'Pengarang tidak diketahui' }}</p>
            <p v-if="book.category?.name" class="ebook-cat">{{ book.category.name }}</p>
            <button type="button" class="btn-primary" :disabled="openingId === book.id" @click="openReader(book)">
              {{ openingId === book.id ? 'Membuka...' : 'Baca' }}
            </button>
          </div>
        </article>
      </div>

      <div v-if="meta.last_page > 1" class="pagination">
        <button type="button" class="pagination-btn" :disabled="meta.current_page <= 1" @click="loadEbooks(meta.current_page - 1)">Sebelumnya</button>
        <span class="pagination-info">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button type="button" class="pagination-btn" :disabled="meta.current_page >= meta.last_page" @click="loadEbooks(meta.current_page + 1)">Selanjutnya</button>
      </div>

      <Teleport to="body">
        <div v-if="readerOpen" class="reader-overlay" role="dialog" aria-modal="true" :aria-label="readerTitle">
          <div class="reader-toolbar">
            <div class="reader-title">{{ readerTitle }}</div>
            <div class="reader-actions">
              <a v-if="readerUrl" :href="readerUrl" target="_blank" rel="noopener" class="btn-secondary">Tab baru</a>
              <button type="button" class="btn-secondary" @click="closeReader">Tutup</button>
            </div>
          </div>
          <iframe v-if="readerUrl" class="reader-frame" :src="readerUrl" title="Pembaca ebook PDF" />
          <div v-else class="reader-loading">Memuat PDF...</div>
        </div>
      </Teleport>
    </div>
  </Layout>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useToast } from '@/composables/useToast'
import { libraryApi } from '@/api/library'

const toast = useToast()

const ebooks = ref([])
const categories = ref([])
const loading = ref(false)
const loadError = ref('')
const filters = ref({ search: '', category_id: '' })
const meta = ref({ current_page: 1, last_page: 1 })
const openingId = ref(null)

const readerOpen = ref(false)
const readerTitle = ref('')
const readerUrl = ref('')

let debounceTimer = null
let objectUrl = null

function revokeObjectUrl() {
  if (objectUrl) {
    URL.revokeObjectURL(objectUrl)
    objectUrl = null
  }
}

async function loadCategories() {
  try {
    const res = await libraryApi.getEbookCategories({ per_page: 200, is_active: 1 })
    categories.value = res.data.data ?? []
  } catch (_) {
    categories.value = []
  }
}

async function loadEbooks(page = 1) {
  loading.value = true
  loadError.value = ''
  try {
    const res = await libraryApi.getEbooks({
      page,
      per_page: 12,
      search: filters.value.search || undefined,
      category_id: filters.value.category_id || undefined
    })
    ebooks.value = res.data.data ?? []
    const m = res.data.meta || {}
    meta.value = {
      current_page: m.current_page ?? page,
      last_page: m.last_page ?? 1
    }
  } catch (e) {
    ebooks.value = []
    loadError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat ebook.'
  } finally {
    loading.value = false
  }
}

function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadEbooks(1), 400)
}

async function openReader(book) {
  openingId.value = book.id
  try {
    const res = await libraryApi.getEbookBlob(book.id)
    const contentType = res.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      const text = await res.data.text?.()
      let msg = 'Gagal membuka ebook.'
      try { msg = JSON.parse(text)?.message || msg } catch (_) {}
      throw new Error(msg)
    }
    revokeObjectUrl()
    objectUrl = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
    readerTitle.value = book.title
    readerUrl.value = objectUrl
    readerOpen.value = true
  } catch (e) {
    let msg = e.formattedMessage || e.message || 'Tidak dapat membuka ebook.'
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const text = await data.text()
        const parsed = JSON.parse(text)
        if (parsed?.message) msg = parsed.message
      } catch (_) {}
    }
    toast.error('Gagal', msg)
  } finally {
    openingId.value = null
  }
}

function closeReader() {
  readerOpen.value = false
  readerUrl.value = ''
  readerTitle.value = ''
  revokeObjectUrl()
}

onMounted(() => {
  loadCategories()
  loadEbooks(1)
})

onBeforeUnmount(() => {
  clearTimeout(debounceTimer)
  revokeObjectUrl()
})
</script>

<style scoped>
.page { padding: 0 1rem 2rem; max-width: 1100px; margin: 0 auto; }
.page-header { margin-bottom: 1.25rem; }
.page-header h1 { margin: 0 0 0.25rem; font-size: 1.5rem; color: #0f172a; }
.page-subtitle { margin: 0; color: #64748b; font-size: 0.95rem; }

.filters { margin-bottom: 1.25rem; }
.filters-row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.35rem; min-width: 180px; }
.filter-group.search-wrap { flex: 1; min-width: 220px; }
.filter-group label { font-size: 0.85rem; font-weight: 500; color: #475569; }
.filter-input { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }

.loading-state, .empty-state { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
.empty-state h3 { margin: 0 0 0.5rem; color: #334155; }

.ebook-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}
.ebook-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.ebook-cover {
  aspect-ratio: 3 / 4;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ebook-cover img { width: 100%; height: 100%; object-fit: cover; }
.cover-placeholder {
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #059669;
  font-size: 1.25rem;
}
.ebook-body { padding: 0.9rem 1rem 1.1rem; display: flex; flex-direction: column; gap: 0.35rem; flex: 1; }
.ebook-title { margin: 0; font-size: 1rem; color: #0f172a; line-height: 1.35; }
.ebook-meta, .ebook-cat { margin: 0; font-size: 0.85rem; color: #64748b; }
.ebook-body .btn-primary { margin-top: auto; }

.btn-primary {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  padding: 0.55rem 0.9rem;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary:hover:not(:disabled) { background: #047857; }
.btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-secondary {
  padding: 0.45rem 0.8rem;
  background: #f1f5f9;
  color: #334155;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
}
.btn-secondary:hover { background: #e2e8f0; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-top: 1.5rem; }
.pagination-btn { padding: 0.45rem 0.85rem; border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; cursor: pointer; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pagination-info { font-size: 0.85rem; color: #64748b; }
.back-link { display: inline-block; margin-top: 1rem; color: #059669; text-decoration: none; font-weight: 500; }

.reader-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  background: #0f172a;
  display: flex;
  flex-direction: column;
}
.reader-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: #1e293b;
  color: #fff;
}
.reader-title { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reader-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
.reader-frame { flex: 1; width: 100%; border: 0; background: #334155; }
.reader-loading { flex: 1; display: flex; align-items: center; justify-content: center; color: #cbd5e1; }

@media (max-width: 640px) {
  .reader-toolbar { flex-wrap: wrap; }
}
</style>
