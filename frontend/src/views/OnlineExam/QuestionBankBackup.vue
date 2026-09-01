<template>    <div class="stimulus-page">
      <header class="page-header">
        <h2>Stimulus Soal</h2>
        <p class="page-desc">Satu stimulus bisa dipakai untuk banyak soal (soal berkelompok). Isi judul untuk memudahkan memilih saat membuat soal.</p>
        <button class="btn-primary" @click="openForm()">+ Tambah stimulus</button>
      </header>

      <div v-if="loading" class="content-card"><p>Memuat...</p></div>
      <div v-else-if="stimuli.length === 0" class="empty-state content-card">
        <h3>Belum ada stimulus</h3>
        <p>Stimulus dipakai untuk satu teks/gambar yang dipakai beberapa soal (soal berkelompok). Buat stimulus dulu, lalu pilih saat menambah soal di Bank Soal.</p>
        <button class="btn-primary" @click="openForm()">Tambah stimulus</button>
      </div>
      <div v-else class="table-wrap content-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Konten (ringkasan)</th>
              <th>Mapel</th>
              <th>Digunakan oleh</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in stimuli" :key="s.id">
              <td>{{ s.title || '(tanpa judul)' }}</td>
              <td>{{ stripHtml(s.content || '').slice(0, 80) }}{{ stripHtml(s.content || '').length > 80 ? '…' : '' }}</td>
              <td>{{ getSubjectName(s.subject_id) }}</td>
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
            <label>Mapel (opsional)</label>
            <select v-model="form.subject_id">
              <option value="">-</option>
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
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
    </div></template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableAction from '@/components/TableAction.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'

const { toast } = useToast()
const stimuli = ref([])
const subjects = ref([])
const loading = ref(false)
const showForm = ref(false)
const editingId = ref(null)
const saving = ref(false)

const form = reactive({ subject_id: '', title: '', content: '' })

function stripHtml(html) {
  if (!html) return ''
  const div = document.createElement('div')
  div.innerHTML = html
  return (div.textContent || div.innerText || '').trim()
}

async function fetchStimuli() {
  loading.value = true
  try {
    const res = await examApi.listStimuli({ per_page: 100 })
    stimuli.value = res.data?.data ?? res.data ?? []
  } finally {
    loading.value = false
  }
}

function getSubjectName(id) {
  if (!id) return '-'
  return subjects.value.find(s => s.id === id)?.name ?? id
}

async function loadSubjects() {
  try {
    const res = await examApi.listSubjects({ per_page: 100 })
    subjects.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    subjects.value = []
  }
}

function openForm(s = null) {
  editingId.value = s?.id ?? null
  form.subject_id = s?.subject_id ?? ''
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
        subject_id: form.subject_id || null,
        title: form.title || null,
        content: form.content
      })
      toast.success('Berhasil', 'Stimulus diperbarui.')
    } else {
      await examApi.createStimulus({
        subject_id: form.subject_id || null,
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
  loadSubjects()
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
</style>
