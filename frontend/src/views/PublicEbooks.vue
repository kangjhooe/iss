<template>
  <div class="public-ebooks-page">
    <nav class="navbar">
      <div class="navbar-inner">
        <router-link :to="`/${npsn}`" class="navbar-brand">
          <img v-if="institution?.logo_url" :src="institution.logo_url" alt="" class="navbar-logo-img" />
          <div v-else class="navbar-logo" aria-hidden="true">📚</div>
          <span class="navbar-title">{{ institution?.name || 'Perpustakaan Digital' }}</span>
        </router-link>
        <div class="navbar-links">
          <router-link :to="`/${npsn}`" class="nav-link">Beranda Sekolah</router-link>
          <router-link to="/login" class="btn btn-primary">Masuk</router-link>
        </div>
      </div>
    </nav>

    <main class="main">
      <header class="page-header">
        <h1>Perpustakaan Digital</h1>
        <p class="page-subtitle">Baca ebook PDF koleksi sekolah — tanpa perlu login</p>
      </header>

      <div class="filters">
        <div class="filters-row">
          <div class="filter-group search-wrap">
            <label for="pub-ebook-search">Cari</label>
            <input
              id="pub-ebook-search"
              v-model="filters.search"
              type="search"
              class="filter-input"
              placeholder="Judul, pengarang, ISBN..."
              @input="debounceLoad"
            />
          </div>
          <div class="filter-group">
            <label for="pub-ebook-category">Kategori</label>
            <select id="pub-ebook-category" v-model="filters.category_id" class="filter-input" @change="loadEbooks(1)">
              <option value="">Semua kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} — {{ c.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="loading" class="state-wrap">Memuat katalog ebook...</div>
      <div v-else-if="loadError" class="state-wrap">
        <p>{{ loadError }}</p>
        <button type="button" class="btn btn-primary" @click="loadEbooks(1)">Coba lagi</button>
      </div>
      <div v-else-if="!ebooks.length" class="state-wrap">
        <h3>Belum ada ebook publik</h3>
        <p>Sekolah belum menandai ebook yang dapat dibaca tanpa login.</p>
        <router-link :to="`/${npsn}`" class="back-link">← Kembali ke beranda sekolah</router-link>
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
            <button type="button" class="btn btn-primary" :disabled="openingId === book.id" @click="openReader(book)">
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
    </main>

    <Teleport to="body">
      <EbookPdfViewer
        v-if="readerOpen && streamUrl"
        :stream-url="streamUrl"
        :title="readerTitle"
        :watermark="watermark"
        @close="closeReader"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { schoolPublicApi } from '@/api/schoolPublic'
import EbookPdfViewer from '@/components/EbookPdfViewer.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { usePageMeta } from '@/composables/usePageMeta'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const toast = useToast()
const pageMeta = usePageMeta()

const npsn = computed(() => String(route.params.npsn || ''))
const institution = ref(null)
const ebooks = ref([])
const categories = ref([])
const loading = ref(false)
const loadError = ref('')
const filters = ref({ search: '', category_id: '' })
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const openingId = ref(null)
const readerOpen = ref(false)
const streamUrl = ref('')
const readerTitle = ref('')
const watermark = ref('')
let debounceTimer = null

/**
 * stream_path dari API berbentuk /api/v1/...
 * Sesuaikan jika VITE_API_BASE_URL absolut (host berbeda).
 */
function resolveStreamUrl(streamPath) {
  const base = import.meta.env.VITE_API_BASE_URL || '/api'
  if (typeof base === 'string' && base.startsWith('http')) {
    const origin = base.replace(/\/api\/?$/, '')
    return origin + streamPath
  }
  return streamPath
}

async function loadInstitution() {
  try {
    const res = await schoolPublicApi.getInstitution(npsn.value)
    institution.value = res.data?.data || null
    if (institution.value?.name) {
      pageMeta.setMeta({
        title: `Perpustakaan Digital — ${institution.value.name}`,
        description: `Baca ebook PDF koleksi ${institution.value.name} tanpa login.`
      })
    }
  } catch (_) {
    institution.value = { name: 'Sekolah/Madrasah', npsn: npsn.value }
  }
}

async function loadCategories() {
  try {
    const res = await schoolPublicApi.getPublicEbookCategories(npsn.value)
    categories.value = res.data?.data ?? []
  } catch (_) {
    categories.value = []
  }
}

async function loadEbooks(page = 1) {
  loading.value = true
  loadError.value = ''
  try {
    const res = await schoolPublicApi.getPublicEbooks(npsn.value, {
      page,
      per_page: meta.value.per_page || 15,
      search: filters.value.search || undefined,
      category_id: filters.value.category_id || undefined
    })
    ebooks.value = res.data?.data ?? []
    if (res.data?.institution) {
      institution.value = { ...(institution.value || {}), ...res.data.institution }
    }
    const m = res.data?.meta || {}
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
    const res = await schoolPublicApi.issuePublicEbookViewer(npsn.value, book.id)
    const data = res.data?.data
    if (!data?.stream_path) {
      throw new Error('Sesi baca tidak tersedia.')
    }
    readerTitle.value = data.title || book.title
    watermark.value = data.watermark || ''
    streamUrl.value = resolveStreamUrl(data.stream_path)
    readerOpen.value = true
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.message || 'Tidak dapat membuka ebook.')
  } finally {
    openingId.value = null
  }
}

function closeReader() {
  readerOpen.value = false
  streamUrl.value = ''
  readerTitle.value = ''
  watermark.value = ''
}

watch(npsn, () => {
  loadInstitution()
  loadCategories()
  loadEbooks(1)
})

onMounted(() => {
  loadInstitution()
  loadCategories()
  loadEbooks(1)
})

onBeforeUnmount(() => {
  clearTimeout(debounceTimer)
})
</script>

<style scoped>
.public-ebooks-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #ecfdf5 0%, #f8fafc 35%, #f1f5f9 100%);
}
.navbar {
  position: sticky;
  top: 0;
  z-index: 20;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid #e2e8f0;
}
.navbar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.navbar-brand {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: #0f172a;
  font-weight: 700;
  min-width: 0;
}
.navbar-logo-img { width: 36px; height: 36px; object-fit: contain; border-radius: 8px; }
.navbar-logo {
  width: 36px; height: 36px; border-radius: 8px;
  background: #ecfdf5; display: grid; place-items: center;
}
.navbar-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.navbar-links { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
.nav-link { color: #047857; text-decoration: none; font-weight: 500; }
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0.5rem 0.9rem; border-radius: 8px; border: none;
  font-weight: 600; cursor: pointer; text-decoration: none;
}
.btn-primary { background: #059669; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #047857; }
.btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }

.main { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem 2.5rem; }
.page-header { margin-bottom: 1.25rem; }
.page-header h1 { margin: 0 0 0.25rem; font-size: 1.6rem; color: #0f172a; }
.page-subtitle { margin: 0; color: #64748b; }

.filters { margin-bottom: 1.25rem; }
.filters-row { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.35rem; min-width: 180px; }
.filter-group.search-wrap { flex: 1; min-width: 220px; }
.filter-group label { font-size: 0.85rem; font-weight: 500; color: #475569; }
.filter-input { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }

.state-wrap { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
.state-wrap h3 { margin: 0 0 0.5rem; color: #334155; }
.back-link { display: inline-block; margin-top: 1rem; color: #059669; text-decoration: none; font-weight: 500; }

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
.ebook-body .btn { margin-top: auto; width: 100%; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-top: 1.5rem; }
.pagination-btn { padding: 0.45rem 0.85rem; border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; cursor: pointer; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pagination-info { font-size: 0.85rem; color: #64748b; }

@media (max-width: 640px) {
  .navbar-title { max-width: 140px; }
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-group,
  .filter-group.search-wrap {
    min-width: 0;
    width: 100%;
  }
  .ebook-grid {
    grid-template-columns: 1fr;
  }
}
</style>
