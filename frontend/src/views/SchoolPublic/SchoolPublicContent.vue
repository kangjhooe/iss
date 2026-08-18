<template>
  <section id="berita" class="section content-section reveal-section">
    <div class="section-inner">
      <h2 class="section-title">Berita & Galeri</h2>
      <p class="section-lead">Informasi dan dokumentasi kegiatan sekolah</p>
      <div v-if="loading" class="muted">Memuat konten...</div>
      <div v-else-if="!posts.length" class="muted">Belum ada berita atau galeri yang dipublikasikan.</div>
      <div v-else class="post-grid">
        <article v-for="p in posts" :key="p.id" class="post-card">
          <img v-if="p.cover_url" :src="p.cover_url" :alt="p.title" class="post-cover" />
          <div class="post-body">
            <span class="post-type">{{ p.type === 'gallery' ? 'Galeri' : 'Berita' }}</span>
            <h3>{{ p.title }}</h3>
            <p v-if="p.body">{{ excerpt(p.body) }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { schoolPostsApi } from '@/api/schoolPosts'

const props = defineProps({
  npsn: { type: String, required: true },
})

const posts = ref([])
const loading = ref(false)

function excerpt(text) {
  const t = String(text || '').replace(/<[^>]+>/g, '')
  return t.length > 140 ? `${t.slice(0, 140)}…` : t
}

async function load() {
  if (!props.npsn) return
  loading.value = true
  try {
    const res = await schoolPostsApi.publicByNpsn(props.npsn, { per_page: 8 })
    posts.value = res.data?.data || []
  } catch {
    posts.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => props.npsn, load)
</script>

<style scoped>
.content-section { padding: 3rem 1.25rem; background: #f8fafc; }
.section-inner { max-width: 1040px; margin: 0 auto; }
.section-title { margin: 0 0 0.35rem; font-size: 1.6rem; color: #0f172a; }
.section-lead { margin: 0 0 1.25rem; color: #64748b; }
.post-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
.post-card { background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; }
.post-cover { width: 100%; height: 140px; object-fit: cover; display: block; }
.post-body { padding: 0.85rem 1rem 1rem; }
.post-type { font-size: 0.75rem; color: #0f766e; font-weight: 700; text-transform: uppercase; }
.post-body h3 { margin: 0.25rem 0; font-size: 1.05rem; }
.post-body p { margin: 0.35rem 0 0; color: #475569; font-size: 0.9rem; }
.muted { color: #64748b; }
</style>
