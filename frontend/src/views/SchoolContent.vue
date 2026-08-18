<template>
  <Layout>
    <div class="page">
      <header class="page-header">
        <div>
          <h1 class="page-title">Berita & Galeri</h1>
          <p class="page-subtitle">Konten yang tampil di halaman publik sekolah</p>
        </div>
        <button type="button" class="btn-primary" @click="openCreate">Tambah</button>
      </header>

      <div class="toolbar">
        <select v-model="filters.type" class="filter-select" @change="load">
          <option value="">Semua tipe</option>
          <option value="news">Berita</option>
          <option value="gallery">Galeri</option>
        </select>
      </div>

      <div v-if="loading" class="muted">Memuat...</div>
      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Judul</th>
            <th>Tipe</th>
            <th>Status</th>
            <th>Terbit</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in posts" :key="p.id">
            <td>{{ p.title }}</td>
            <td>{{ p.type }}</td>
            <td>{{ p.is_published ? 'Published' : 'Draft' }}</td>
            <td>{{ formatDate(p.published_at) }}</td>
            <td>
              <button type="button" class="btn-link" @click="openEdit(p)">Edit</button>
              <button type="button" class="btn-link danger" @click="remove(p)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
        <div class="modal">
          <h3>{{ editing ? 'Edit konten' : 'Tambah konten' }}</h3>
          <label>Judul <input v-model="form.title" class="form-input" /></label>
          <label>Tipe
            <select v-model="form.type" class="form-input">
              <option value="news">Berita</option>
              <option value="gallery">Galeri</option>
            </select>
          </label>
          <label>Isi <textarea v-model="form.body" rows="4" class="form-input" /></label>
          <label class="checkbox"><input v-model="form.is_published" type="checkbox" /> Publikasikan</label>
          <label>Cover <input type="file" accept="image/*" @change="onCover" /></label>
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
            <button type="button" class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { schoolPostsApi } from '@/api/schoolPosts'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const posts = ref([])
const showModal = ref(false)
const editing = ref(null)
const coverFile = ref(null)
const filters = reactive({ type: '' })
const form = reactive({
  title: '',
  type: 'news',
  body: '',
  is_published: true,
})

function formatDate(v) {
  if (!v) return '—'
  return new Date(v).toLocaleDateString('id-ID')
}

async function load() {
  loading.value = true
  try {
    const res = await schoolPostsApi.list({ type: filters.type || undefined, per_page: 50 })
    posts.value = res.data?.data || []
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Tidak dapat memuat konten.')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  form.title = ''
  form.type = 'news'
  form.body = ''
  form.is_published = true
  coverFile.value = null
  showModal.value = true
}

function openEdit(p) {
  editing.value = p
  form.title = p.title
  form.type = p.type
  form.body = p.body || ''
  form.is_published = !!p.is_published
  coverFile.value = null
  showModal.value = true
}

function onCover(e) {
  coverFile.value = e.target.files?.[0] || null
}

async function save() {
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('title', form.title)
    fd.append('type', form.type)
    fd.append('body', form.body || '')
    fd.append('is_published', form.is_published ? '1' : '0')
    if (coverFile.value) fd.append('cover', coverFile.value)
    if (editing.value) await schoolPostsApi.update(editing.value.id, fd)
    else await schoolPostsApi.create(fd)
    showModal.value = false
    toast.success('Berhasil', 'Konten disimpan.')
    await load()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Tidak dapat menyimpan.')
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  if (!confirm(`Hapus «${p.title}»?`)) return
  try {
    await schoolPostsApi.remove(p.id)
    toast.success('Berhasil', 'Konten dihapus.')
    await load()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Tidak dapat menghapus.')
  }
}

onMounted(load)
</script>

<style scoped>
.page { max-width: 1000px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; gap: 1rem; align-items: start; margin-bottom: 1rem; }
.page-title { margin: 0; font-size: 1.35rem; }
.page-subtitle { margin: 0.25rem 0 0; color: #64748b; }
.toolbar { margin-bottom: 0.75rem; }
.filter-select, .form-input { width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; }
.data-table { width: 100%; border-collapse: collapse; background: #fff; }
.data-table th, .data-table td { padding: 0.65rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
.btn-primary, .btn-secondary { border: none; border-radius: 8px; padding: 0.45rem 0.9rem; cursor: pointer; font-weight: 600; }
.btn-primary { background: #0f766e; color: #fff; }
.btn-secondary { background: #e2e8f0; }
.btn-link { border: none; background: none; color: #0f766e; cursor: pointer; margin-right: 0.5rem; }
.btn-link.danger { color: #b91c1c; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: flex; align-items: center; justify-content: center; z-index: 50; }
.modal { background: #fff; border-radius: 12px; padding: 1rem; width: min(520px, 92vw); display: grid; gap: 0.65rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; }
.checkbox { display: flex; gap: 0.4rem; align-items: center; }
.muted { color: #64748b; }
</style>
