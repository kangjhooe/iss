<template>
  <Layout>
    <div class="stimulus-page">
      <header class="page-header">
        <nav class="breadcrumb">
          <router-link to="/ujian-online/bank-soal">Bank Soal</router-link>
          <span v-if="currentBank" class="sep">/</span>
          <router-link v-if="currentBank" :to="`/ujian-online/bank-soal/${bankId}/soal`" class="crumb-bank">{{ currentBank.name || currentBank.code }}</router-link>
          <span v-if="currentBank" class="sep">/</span>
          <span v-if="currentBank" class="current">Stimulus</span>
        </nav>
        <h2>Stimulus Soal</h2>
        <p class="page-desc">Stimulus hanya dipakai di bank ini. Satu stimulus bisa dipakai untuk banyak soal (soal berkelompok).</p>
        <button v-if="bankId" class="btn-primary" @click="openForm()">+ Tambah stimulus</button>
      </header>

      <div v-if="!bankId" class="content-card info-box">
        <p>Pilih bank soal terlebih dahulu.</p>
        <router-link to="/ujian-online/bank-soal" class="btn-primary">Daftar Bank Soal</router-link>
      </div>
      <div v-else-if="loading" class="content-card"><p>Memuat...</p></div>
      <div v-else-if="stimuli.length === 0" class="empty-state content-card">
        <h3>Belum ada stimulus</h3>
        <p>Stimulus dipakai untuk satu teks/gambar yang dipakai beberapa soal (soal berkelompok). Buat stimulus dulu, lalu pilih saat menambah soal di bank ini.</p>
        <button class="btn-primary" @click="openForm()">Tambah stimulus</button>
      </div>
      <div v-else class="table-wrap content-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Konten (ringkasan)</th>
              <th>Digunakan oleh</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in stimuli" :key="s.id">
              <td>{{ s.title || '(tanpa judul)' }}</td>
              <td>{{ stripHtml(s.content || '').slice(0, 80) }}{{ stripHtml(s.content || '').length > 80 ? '…' : '' }}</td>
              <td>{{ s.questions_count != null ? s.questions_count : '–' }} soal</td>
              <td>
                <TableAction kind="edit" @click="openForm(s)" />
                <TableAction kind="delete" @click="confirmDelete(s)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal content-card modal-wide">
          <h3>{{ editingId ? 'Edit stimulus' : 'Tambah stimulus' }}</h3>
          <div class="form-group">
            <label>Judul (untuk identifikasi di daftar soal)</label>
            <input v-model="form.title" type="text" placeholder="Contoh: Bacaan tentang ekosistem" maxlength="255" />
            <p class="hint">Isi judul singkat agar mudah memilih stimulus saat membuat soal.</p>
          </div>
          <div class="form-group">
            <label>Konten *</label>
            <RichTextEditor v-model="form.content" placeholder="Teks, gambar, atau tabel yang akan dipakai untuk beberapa soal" min-height="200px" />
          </div>
          <div class="modal-actions">
            <button class="btn-primary" @click="submitForm" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const { toast } = useToast()
const bankId = ref(route.params.bankId ? Number(route.params.bankId) : null)
const currentBank = ref(null)
const stimuli = ref([])
const loading = ref(false)
const showForm = ref(false)
const editingId = ref(null)
const saving = ref(false)

const form = reactive({ title: '', content: '' })

function stripHtml(html) {
  if (!html) return ''
  const div = document.createElement('div')
  div.innerHTML = html
  return (div.textContent || div.innerText || '').trim()
}

async function loadBank() {
  if (!bankId.value) return
  try {
    const res = await examApi.getBank(bankId.value)
    currentBank.value = res.data?.data ?? res.data
  } catch (_) {
    currentBank.value = null
  }
}

async function fetchStimuli() {
  if (!bankId.value) {
    stimuli.value = []
    return
  }
  loading.value = true
  try {
    const res = await examApi.listStimuli({ bank_soal_id: bankId.value, per_page: 100 })
    stimuli.value = res.data?.data ?? res.data ?? []
  } finally {
    loading.value = false
  }
}

function openForm(s = null) {
  editingId.value = s?.id ?? null
  form.title = s?.title ?? ''
  form.content = s?.content ?? ''
  showForm.value = true
}

async function submitForm() {
  const contentTrim = (form.content || '').trim()
  if (!contentTrim) {
    toast.error('Validasi', 'Konten wajib diisi.')
    return
  }
  saving.value = true
  try {
    if (editingId.value) {
      await examApi.updateStimulus(editingId.value, {
        title: form.title || null,
        content: form.content
      })
      toast.success('Berhasil', 'Stimulus diperbarui.')
    } else {
      await examApi.createStimulus({
        bank_soal_id: bankId.value,
        title: form.title || null,
        content: form.content
      })
      toast.success('Berhasil', 'Stimulus ditambahkan.')
    }
    showForm.value = false
    fetchStimuli()
  } catch (e) {
    toast.error('Gagal menyimpan stimulus', e.response?.data?.message || 'Perubahan tidak dapat disimpan. Coba lagi.')
  } finally {
    saving.value = false
  }
}

async function confirmDelete(s) {
  const n = s.questions_count ?? 0
  if (n > 0 && !confirm(`Stimulus ini dipakai oleh ${n} soal. Yakin hapus? Soal tetap ada tapi tanpa stimulus.`)) return
  if (n === 0 && !confirm('Hapus stimulus ini?')) return
  try {
    await examApi.deleteStimulus(s.id)
    toast.success('Berhasil', 'Stimulus dihapus.')
    fetchStimuli()
  } catch (e) {
    toast.error('Gagal menghapus stimulus', e.response?.data?.message || 'Stimulus tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadBank()
  fetchStimuli()
})

watch(() => route.params.bankId, (id) => {
  bankId.value = id ? Number(id) : null
  loadBank()
  fetchStimuli()
})
</script>

<style scoped>
.stimulus-page { padding: 1rem; }
.page-header { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 1rem; }
.page-header h2 { margin: 0; flex-basis: 100%; }
.page-desc { margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem; flex-basis: 100%; }
.btn-primary { padding: 0.5rem 1rem; background: #059669; color: #fff; border: none; border-radius: 6px; }
.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.btn-action { margin-right: 0.5rem; background: none; border: none; cursor: pointer; font-size: inherit; }
.btn-edit { color: #059669; }
.btn-delete { color: #dc2626; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; overflow-y: auto; padding: 1rem; }
.modal { max-width: 560px; width: 100%; }
.modal-wide { max-width: 720px; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; }
.form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.hint { margin: 0.25rem 0 0; font-size: 0.85rem; color: #6b7280; }
.modal-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
.btn-secondary { padding: 0.5rem 1rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; }
.empty-state { text-align: center; padding: 2rem; }
.breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280; }
.breadcrumb a { color: #059669; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb .sep { color: #d1d5db; }
.breadcrumb .current { color: #374151; font-weight: 500; }
.info-box { padding: 1.5rem; text-align: center; }
.info-box .btn-primary { margin-top: 0.5rem; display: inline-block; }
</style>
