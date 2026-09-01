<template>    <div class="sp-page">
      <div class="sp-page-header">
        <p class="sp-subtitle">Baca ebook PDF koleksi sekolah Anda</p>
      </div>

      <div class="sp-filters">
        <div class="sp-filter search-wrap">
          <label for="ebook-search">Cari</label>
          <input
            id="ebook-search"
            v-model="filters.search"
            type="search"
            placeholder="Judul, pengarang, ISBN..."
            @input="debounceLoad"
          />
        </div>
        <div class="sp-filter">
          <label for="ebook-category">Kategori</label>
          <select id="ebook-category" v-model="filters.category_id" @change="loadEbooks(1)">
            <option value="">Semua kategori</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat katalog ebook...</p>
      </div>

      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat</h3>
        <p class="sp-empty-desc">{{ loadError }}</p>
        <div class="sp-empty-actions">
          <button type="button" class="sp-btn sp-btn--primary" @click="loadEbooks(1)">Coba lagi</button>
        </div>
      </div>

      <div v-else-if="!ebooks.length" class="sp-empty">
        <h3 class="sp-empty-title">Belum ada ebook</h3>
        <p class="sp-empty-desc">Pustakawan belum mengunggah buku PDF, atau tidak ada yang cocok dengan filter.</p>
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
            <button type="button" class="sp-btn sp-btn--primary" :disabled="openingId === book.id" @click="openReader(book)">
              {{ openingId === book.id ? 'Membuka...' : 'Baca' }}
            </button>
          </div>
        </article>
      </div>

      <PaginationBar
        :page="meta.current_page"
        :last-page="meta.last_page"
        :per-page="meta.per_page"
        :total="meta.total"
        item-label="ebook"
        @page-change="loadEbooks"
        @per-page-change="changePerPage"
      />

      <Teleport to="body">
        <div v-if="readerOpen" class="reader-overlay" role="dialog" aria-modal="true" :aria-label="readerTitle">
          <div class="reader-toolbar">
            <div class="reader-title">{{ readerTitle }}</div>
            <div class="reader-actions">
              <a v-if="readerUrl" :href="readerUrl" target="_blank" rel="noopener" class="sp-btn sp-btn--ghost reader-btn">Tab baru</a>
              <button type="button" class="sp-btn sp-btn--ghost reader-btn" @click="closeReader">Tutup</button>
            </div>
          </div>
          <iframe v-if="readerUrl" class="reader-frame" :src="readerUrl" title="Pembaca ebook PDF" />
          <div v-else class="reader-loading">Memuat PDF...</div>
        </div>
      </Teleport>
    </div></template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { useToast } from '@/composables/useToast'
import { libraryApi } from '@/api/library'

const toast = useToast()

const ebooks = ref([])
const categories = ref([])
const loading = ref(false)
const loadError = ref('')
const filters = ref({ search: '', category_id: '' })
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
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
      per_page: meta.value.per_page || 15,
      search: filters.value.search || undefined,
      category_id: filters.value.category_id || undefined
    })
    ebooks.value = res.data.data ?? []
    const m = res.data.meta || {}
    meta.value = {
      current_page: m.current_page ?? page,
      last_page: m.last_page ?? 1,
      per_page: m.per_page ?? meta.value.per_page,
      total: m.total ?? 0
    }
  } catch (e) {
    ebooks.value = []
    loadError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat ebook.'
  } finally {
    loading.value = false
  }
}

function changePerPage(n) {
  meta.value.per_page = n
  loadEbooks(1)
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
.search-wrap {
  flex: 2;
  min-width: 220px;
}

.ebook-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
}

.ebook-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.ebook-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.ebook-cover {
  aspect-ratio: 3 / 4;
  background: linear-gradient(160deg, #ecfdf5, #f1f5f9);
  display: flex;
  align-items: center;
  justify-content: center;
}

.ebook-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cover-placeholder {
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #059669;
  font-size: 1.25rem;
}

.ebook-body {
  padding: 14px 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.ebook-title {
  margin: 0;
  font-size: 15px;
  color: #0f172a;
  line-height: 1.35;
}

.ebook-meta,
.ebook-cat {
  margin: 0;
  font-size: 12px;
  color: #64748b;
}

.ebook-body .sp-btn {
  margin-top: auto;
  width: 100%;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 4px;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

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

.reader-title {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.reader-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.reader-btn {
  background: #334155;
  border-color: #475569;
  color: #fff;
}

.reader-frame {
  flex: 1;
  width: 100%;
  border: 0;
  background: #334155;
}

.reader-loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}

@media (max-width: 640px) {
  .reader-toolbar {
    flex-wrap: wrap;
  }

  .ebook-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
