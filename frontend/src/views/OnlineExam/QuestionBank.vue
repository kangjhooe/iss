<template>
  <Layout>
    <div class="bank-page">
      <header class="page-header">
        <nav class="breadcrumb">
          <router-link to="/ujian-online/bank-soal">Bank Soal</router-link>
          <span v-if="currentBank" class="sep">/</span>
          <span v-if="currentBank" class="current">{{ currentBank.name || currentBank.code }}</span>
        </nav>
        <h2>{{ currentBank ? (currentBank.name || 'Soal: ' + currentBank.code) : 'Soal' }}</h2>
        <div class="header-actions">
          <router-link v-if="bankId" :to="`/ujian-online/bank-soal/${bankId}/stimulus`" class="btn-secondary btn-link-stimulus">Stimulus</router-link>
          <select v-if="!bankId" v-model="filterSubjectId" @change="fetchQuestions">
            <option value="">Semua mapel</option>
            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <button class="btn-primary" @click="openForm()">+ Tambah Soal</button>
        </div>
      </header>

      <div v-if="!bankId" class="content-card info-box">
        <p>Pilih bank soal dari daftar, lalu klik <strong>Kelola Soal</strong> untuk mengisi soal.</p>
        <router-link to="/ujian-online/bank-soal" class="btn-primary">Daftar Bank Soal</router-link>
      </div>
      <div v-else-if="loading" class="content-card"><p>Memuat...</p></div>
      <div v-else-if="questions.length === 0" class="empty-state content-card">
        <h3>Belum ada soal</h3>
        <p>Tambahkan soal ke bank ini.</p>
        <button class="btn-primary" @click="openForm()">Tambah Soal</button>
      </div>
      <div v-else class="table-wrap content-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Soal</th>
              <th>Stimulus</th>
              <th>Tipe</th>
              <th>Bobot</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="q in questions" :key="q.id">
              <td>{{ stripHtml((q.body || '')).slice(0, 80) }}{{ stripHtml(q.body || '').length > 80 ? '…' : '' }}</td>
              <td>{{ q.stimulus ? (q.stimulus.title || stripHtml(q.stimulus.content || '').slice(0, 30) + '…') : '–' }}</td>
              <td>{{ typeLabel(q.type) }}</td>
              <td>{{ q.weight }}</td>
              <td>
                <button type="button" class="btn-action btn-edit" @click="openForm(q)">Edit</button>
                <button type="button" class="btn-action btn-delete" @click="confirmDelete(q)">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal content-card modal-wide">
          <h3>{{ editingId ? 'Edit soal' : 'Tambah soal' }}</h3>
          <div v-if="currentBank" class="form-group info-row">
            <span>Mapel: <strong>{{ currentBank.subject?.name }}</strong></span>
            <span v-if="currentBank.grade"> — Kelas {{ currentBank.grade }}</span>
          </div>
          <div v-if="!bankId" class="form-group">
            <label>Mapel *</label>
            <select v-model="form.subject_id" required>
              <option value="">Pilih</option>
              <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Stimulus (opsional)</label>
            <div class="stimulus-row">
              <select v-model="form.stimulus_id">
                <option value="">Tanpa stimulus</option>
                <option v-for="st in filteredStimuli" :key="st.id" :value="st.id">{{ stimulusOptionLabel(st) }}</option>
              </select>
              <button type="button" class="btn-secondary btn-add-stimulus" @click="openStimulusForm">+ Buat stimulus baru</button>
            </div>
            <p class="hint">Satu stimulus bisa dipakai untuk banyak soal. Atau buat baru lewat tombol di atas.</p>
          </div>
          <div class="form-group">
            <label>Tipe *</label>
            <select v-model="form.type">
              <option value="pg">Pilihan ganda</option>
              <option value="pg_kompleks">Pilihan ganda kompleks</option>
              <option value="matching">Mencocokkan</option>
              <option value="isian">Isian singkat</option>
              <option value="uraian">Uraian</option>
            </select>
          </div>
          <div class="form-group">
            <label>Pertanyaan *</label>
            <RichTextEditor v-model="form.body" placeholder="Tulis pertanyaan (bisa format teks, gambar, tabel)" min-height="180px" />
          </div>
          <div class="form-group">
            <label>Bobot</label>
            <input v-if="form.type !== 'pg_kompleks'" v-model.number="form.weight" type="number" min="0" step="0.01" />
            <p v-else class="hint">Bobot soal dihitung otomatis dari jumlah bobot opsi yang benar.</p>
          </div>
          <div v-if="form.type === 'pg'" class="form-group">
            <label>Opsi (A, B, C, D...). Centang satu jawaban benar.</label>
            <div v-for="(opt, i) in form.options" :key="i" class="option-row option-row-rich">
              <input v-model="opt.option_key" placeholder="A" class="opt-key" />
              <RichTextEditor v-model="opt.body" :placeholder="'Opsi ' + (opt.option_key || (i+1))" min-height="80px" class="opt-editor" />
              <label class="opt-correct"><input v-model="opt.is_correct" type="checkbox" /> Benar</label>
            </div>
            <button type="button" class="btn-small" @click="form.options.push({ option_key: '', body: '', is_correct: false })">+ Opsi</button>
          </div>
          <div v-if="form.type === 'pg_kompleks'" class="form-group">
            <label>Opsi (A, B, C, D...). Centang jawaban yang benar dan isi bobot tiap opsi.</label>
            <div v-for="(opt, i) in form.options" :key="i" class="option-row option-row-rich option-row-pg-kompleks">
              <input v-model="opt.option_key" placeholder="A" class="opt-key" />
              <RichTextEditor v-model="opt.body" :placeholder="'Opsi ' + (opt.option_key || (i+1))" min-height="80px" class="opt-editor" />
              <label class="opt-correct"><input v-model="opt.is_correct" type="checkbox" /> Benar</label>
              <div class="opt-weight-wrap">
                <label class="opt-weight-label">Bobot</label>
                <input v-model.number="opt.option_weight" type="number" min="0" step="0.01" class="opt-weight" />
              </div>
            </div>
            <button type="button" class="btn-small" @click="form.options.push({ option_key: '', body: '', is_correct: false, option_weight: 0 })">+ Opsi</button>
          </div>
          <div v-if="form.type === 'matching'" class="form-group matching-form">
            <label>Kolom kiri dan kanan — isi pasangan yang cocok.</label>
            <div class="matching-columns">
              <div class="matching-col">
                <strong>Kolom kiri</strong>
                <div v-for="(item, i) in form.matching_left" :key="'L'+i" class="matching-item">
                  <span class="matching-num">{{ i + 1 }}.</span>
                  <input v-model="item.text" placeholder="Teks kiri" />
                </div>
                <button type="button" class="btn-small" @click="addMatchingLeft">+ Baris kiri</button>
              </div>
              <div class="matching-col">
                <strong>Kolom kanan</strong>
                <div v-for="(item, i) in form.matching_right" :key="'R'+i" class="matching-item">
                  <span class="matching-num">{{ i + 1 }}.</span>
                  <input v-model="item.text" placeholder="Teks kanan" />
                </div>
                <button type="button" class="btn-small" @click="form.matching_right.push({ id: '', text: '' })">+ Baris kanan</button>
              </div>
            </div>
            <div class="matching-correct">
              <strong>Pasangan benar (kiri → kanan)</strong>
              <p class="hint">Pilih untuk setiap nomor kiri, nomor kanan yang cocok.</p>
              <div v-for="(leftItem, i) in form.matching_left" :key="'C'+i" class="correct-row">
                <span>{{ i + 1 }}. {{ leftItem.text || '(kosong)' }}</span>
                <select v-model="form.matching_correct[i]">
                  <option value="">— Pilih kanan —</option>
                  <option v-for="(r, j) in form.matching_right" :key="j" :value="String(j)">{{ j + 1 }}. {{ r.text || '(kosong)' }}</option>
                </select>
              </div>
            </div>
          </div>
          <div v-if="form.type === 'isian'" class="form-group">
            <label>Kunci jawaban (teks tepat)</label>
            <input v-model="form.key_answer" type="text" />
          </div>
          <div class="modal-actions">
            <button class="btn-primary" @click="submitQuestion" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          </div>
        </div>
      </div>

      <!-- Modal quick-add stimulus (di atas modal soal) -->
      <div v-if="showStimulusForm" class="modal-overlay modal-overlay-stimulus" @click.self="showStimulusForm = false">
        <div class="modal content-card modal-wide">
          <h3>Buat stimulus baru</h3>
          <p class="hint block">Stimulus hanya untuk bank ini. Setelah disimpan akan otomatis terpilih untuk soal ini.</p>
          <div class="form-group">
            <label>Judul (untuk identifikasi di daftar)</label>
            <input v-model="stimulusForm.title" type="text" placeholder="Contoh: Bacaan tentang ekosistem" maxlength="255" />
          </div>
          <div class="form-group">
            <label>Konten *</label>
            <RichTextEditor v-model="stimulusForm.content" placeholder="Teks, gambar, atau tabel yang dipakai untuk beberapa soal" min-height="200px" />
          </div>
          <div class="modal-actions">
            <button class="btn-primary" @click="submitStimulusForm" :disabled="savingStimulus">{{ savingStimulus ? 'Menyimpan...' : 'Simpan' }}</button>
            <button type="button" class="btn-secondary" @click="showStimulusForm = false">Batal</button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { examApi } from '@/api/exam'
import api from '@/api'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const bankId = ref(route.params.bankId ? Number(route.params.bankId) : null)
const currentBank = ref(null)

const toast = useToast()
const questions = ref([])
const subjects = ref([])
const stimuli = ref([])
const loading = ref(false)
const filterSubjectId = ref('')
const showForm = ref(false)
const showStimulusForm = ref(false)
const editingId = ref(null)
const saving = ref(false)
const savingStimulus = ref(false)

const form = reactive({
  subject_id: '',
  stimulus_id: '',
  type: 'pg',
  body: '',
  weight: 1,
  key_answer: '',
  options: [{ option_key: 'A', body: '', is_correct: false }, { option_key: 'B', body: '', is_correct: false }],
  matching_left: [{ id: '1', text: '' }, { id: '2', text: '' }],
  matching_right: [{ id: '3', text: '' }, { id: '4', text: '' }],
  matching_correct: []
})

const stimulusForm = reactive({ title: '', content: '' })

async function fetchQuestions() {
  loading.value = true
  try {
    const params = { per_page: 100 }
    if (bankId.value) params.bank_soal_id = bankId.value
    else if (filterSubjectId.value) params.subject_id = filterSubjectId.value
    const res = await examApi.listQuestions(params)
    questions.value = res.data?.data ?? res.data ?? []
  } finally {
    loading.value = false
  }
}

async function loadBank() {
  if (!bankId.value) return
  try {
    const res = await examApi.getBank(bankId.value)
    currentBank.value = res.data?.data ?? res.data
    await loadStimuli()
  } catch (_) {
    currentBank.value = null
  }
}

async function loadSubjects() {
  try {
    const res = await examApi.listSubjects({ per_page: 100 })
    subjects.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    subjects.value = []
  }
}

async function loadStimuli() {
  if (!bankId.value) {
    stimuli.value = []
    return
  }
  try {
    const res = await examApi.listStimuli({ bank_soal_id: bankId.value, per_page: 100 })
    stimuli.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    stimuli.value = []
  }
}

const filteredStimuli = computed(() => stimuli.value || [])

function stimulusOptionLabel(st) {
  const label = st.title ? st.title.trim() : stripHtml(st.content || '').slice(0, 60)
  const suffix = st.questions_count != null && st.questions_count > 0 ? ` (${st.questions_count} soal)` : ''
  return (label || '(tanpa judul)') + suffix
}

function typeLabel(type) {
  const map = { pg: 'PG', pg_kompleks: 'PG Kompleks', matching: 'Mencocokkan', isian: 'Isian', uraian: 'Uraian' }
  return map[type] || type
}

function stripHtml(html) {
  if (!html) return ''
  const div = document.createElement('div')
  div.innerHTML = html
  return (div.textContent || div.innerText || '').trim()
}

function addMatchingLeft() {
  form.matching_left.push({ id: '', text: '' })
  form.matching_correct.push('')
}

function openForm(q = null) {
  editingId.value = q?.id ?? null
  form.subject_id = q?.subject_id ?? ''
  form.stimulus_id = q?.stimulus_id ?? ''
  form.type = q?.type ?? 'pg'
  form.body = q?.body ?? ''
  form.weight = q?.weight ?? 1
  form.key_answer = q?.key_answer ?? ''
  form.options = (q?.options && q.options.length) ? q.options.map(o => ({
    option_key: o.option_key,
    body: o.body,
    is_correct: !!o.is_correct,
    option_weight: o.option_weight != null ? Number(o.option_weight) : 0
  })) : (q?.type === 'pg_kompleks'
    ? [{ option_key: 'A', body: '', is_correct: false, option_weight: 0 }, { option_key: 'B', body: '', is_correct: false, option_weight: 0 }]
    : [{ option_key: 'A', body: '', is_correct: false }, { option_key: 'B', body: '', is_correct: false }])
  if (q?.type === 'matching' && q?.matching_data) {
    const md = q.matching_data
    form.matching_left = (md.left && md.left.length) ? md.left.map((x, i) => ({ id: x.id || String(i + 1), text: x.text || '' })) : [{ id: '1', text: '' }, { id: '2', text: '' }]
    form.matching_right = (md.right && md.right.length) ? md.right.map((x, i) => ({ id: x.id || String(form.matching_left.length + i + 1), text: x.text || '' })) : [{ id: '3', text: '' }, { id: '4', text: '' }]
    form.matching_correct = (md.correct && md.correct.length) ? form.matching_left.map(l => {
      const pair = md.correct.find(c => c.left_id === l.id || c.left_id === String(l.id))
      if (!pair) return ''
      const rightIndex = form.matching_right.findIndex(r => r.id === pair.right_id || String(r.id) === pair.right_id)
      return rightIndex >= 0 ? String(rightIndex) : ''
    }) : form.matching_left.map(() => '')
  } else {
    form.matching_left = [{ id: '1', text: '' }, { id: '2', text: '' }]
    form.matching_right = [{ id: '3', text: '' }, { id: '4', text: '' }]
    form.matching_correct = []
  }
  showForm.value = true
  loadStimuli()
}

function openStimulusForm() {
  stimulusForm.title = ''
  stimulusForm.content = ''
  showStimulusForm.value = true
}

async function submitStimulusForm() {
  const contentTrim = (stimulusForm.content || '').trim()
  if (!contentTrim) {
    toast.error('Validasi', 'Konten stimulus wajib diisi.')
    return
  }
  if (!bankId.value) {
    toast.error('Bank soal tidak diketahui', 'Pilih atau buat bank soal terlebih dahulu.')
    return
  }
  savingStimulus.value = true
  try {
    const res = await examApi.createStimulus({
      bank_soal_id: bankId.value,
      title: stimulusForm.title || null,
      content: stimulusForm.content
    })
    const created = res.data?.data ?? res.data
    const newId = created?.id
    if (newId) {
      stimuli.value = [...(stimuli.value || []), created]
      form.stimulus_id = newId
    }
    showStimulusForm.value = false
    toast.success('Stimulus ditambahkan dan dipilih untuk soal ini.')
  } catch (e) {
    const msg = e?.formattedMessage || e?.response?.data?.message || e?.response?.data?.errors ? (Object.values(e.response.data.errors)[0]?.[0] || 'Gagal menyimpan') : 'Gagal menyimpan'
    toast.error('Gagal menyimpan stimulus', msg)
  } finally {
    savingStimulus.value = false
  }
}

async function submitQuestion() {
  saving.value = true
  try {
    const payload = { stimulus_id: form.stimulus_id || null, type: form.type, body: form.body, weight: form.weight, key_answer: form.key_answer || null }
    if (!bankId.value) payload.subject_id = form.subject_id
    if (form.type === 'pg' || form.type === 'pg_kompleks') {
      payload.options = form.options
        .filter(o => (o.body || '').trim() !== '')
        .map(o => ({
          option_key: o.option_key,
          body: o.body,
          is_correct: !!o.is_correct,
          ...(form.type === 'pg_kompleks' && { option_weight: Number(o.option_weight) || 0 })
        }))
    }
    if (form.type === 'matching') {
      const left = form.matching_left.filter(l => l.text.trim()).map((l, i) => ({ id: String(i + 1), text: l.text.trim() }))
      const right = form.matching_right.filter(r => r.text.trim()).map((r, i) => ({ id: String(left.length + i + 1), text: r.text.trim() }))
      const correct = []
      left.forEach((l, li) => {
        const ri = form.matching_correct[li]
        if (ri !== undefined && ri !== '') {
          const rIdx = Number(ri)
          if (form.matching_right[rIdx] && form.matching_right[rIdx].text.trim()) {
            const filteredRightIndex = form.matching_right.slice(0, rIdx + 1).filter(r => r.text.trim()).length - 1
            if (filteredRightIndex >= 0 && right[filteredRightIndex]) {
              correct.push({ left_id: l.id, right_id: right[filteredRightIndex].id })
            }
          }
        }
      })
      payload.matching_data = { left, right, correct }
    }
    if (editingId.value) {
      await examApi.updateQuestion(editingId.value, payload)
      toast.success('Soal diperbarui.')
    } else {
      if (bankId.value) payload.bank_soal_id = bankId.value
      else payload.subject_id = form.subject_id
      await examApi.createQuestion(payload)
      toast.success('Soal ditambahkan.')
    }
    showForm.value = false
    fetchQuestions()
  } catch (e) {
    toast.error('Gagal menyimpan bank soal', e.response?.data?.message || 'Perubahan tidak dapat disimpan. Coba lagi.')
  } finally {
    saving.value = false
  }
}

async function confirmDelete(q) {
  if (!confirm('Hapus soal ini?')) return
  try {
    await examApi.deleteQuestion(q.id)
    toast.success('Soal dihapus.')
    fetchQuestions()
  } catch (e) {
    toast.error('Gagal menghapus soal', e.response?.data?.message || 'Soal tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadSubjects()
  loadStimuli()
  if (bankId.value) {
    loadBank()
    fetchQuestions()
  }
})

watch(() => route.params.bankId, (id) => {
  bankId.value = id ? Number(id) : null
  currentBank.value = null
  if (bankId.value) {
    loadBank()
    fetchQuestions()
  }
})

watch(() => form.type, (newType) => {
  if (newType === 'pg_kompleks') {
    form.options.forEach(o => {
      if (o.option_weight == null) o.option_weight = 0
    })
  }
})
</script>

<style scoped>
.bank-page { padding: 1rem; }
.page-header { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 1rem; }
.page-header h2 { margin: 0; flex-basis: 100%; }
.breadcrumb { font-size: 0.875rem; margin-bottom: 0.25rem; }
.breadcrumb a { color: #2563eb; text-decoration: none; }
.breadcrumb .sep { color: #9ca3af; margin: 0 0.25rem; }
.breadcrumb .current { color: #374151; }
.header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.info-box { text-align: center; padding: 2rem; }
.info-box p { margin-bottom: 1rem; }
.form-group.info-row { margin-bottom: 0.5rem; color: #6b7280; }
.btn-primary { padding: 0.5rem 1rem; background: #059669; color: #fff; border: none; border-radius: 6px; }
.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.btn-action { margin-right: 0.5rem; font-size: 0.875rem; }
.btn-edit { color: #059669; }
.btn-delete { color: #dc2626; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1050; overflow-y: auto; padding: 1rem; }
.modal-overlay-stimulus { z-index: 1060; }
.modal { max-width: 560px; width: 100%; max-height: calc(100vh - 2rem); overflow-y: auto; }
.modal-wide { max-width: 720px; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.hint { margin: 0.25rem 0 0; font-size: 0.85rem; color: #6b7280; }
.hint.block { margin-bottom: 0.5rem; }
.hint-link { color: #059669; text-decoration: none; }
.hint-link:hover { text-decoration: underline; }
.stimulus-row { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.stimulus-row select { flex: 1; min-width: 200px; }
.btn-add-stimulus { flex-shrink: 0; white-space: nowrap; }
.option-row { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
.option-row-rich { align-items: flex-start; flex-wrap: wrap; }
.option-row-rich .opt-key { width: 44px; flex-shrink: 0; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.option-row-rich .opt-editor { flex: 1; min-width: 200px; }
.option-row-rich .opt-correct { flex-shrink: 0; white-space: nowrap; }
.option-row-pg-kompleks .opt-weight-wrap { display: flex; align-items: center; gap: 0.25rem; flex-shrink: 0; }
.option-row-pg-kompleks .opt-weight-label { font-size: 0.85rem; color: #6b7280; white-space: nowrap; }
.option-row-pg-kompleks .opt-weight { width: 72px; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.matching-form .matching-columns { display: flex; gap: 1.5rem; margin-bottom: 1rem; }
.matching-form .matching-col { flex: 1; }
.matching-form .matching-col strong { display: block; margin-bottom: 0.5rem; }
.matching-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.matching-item .matching-num { min-width: 1.5rem; }
.matching-item input { flex: 1; padding: 0.35rem; }
.matching-correct { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.matching-correct .correct-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
.matching-correct .correct-row select { min-width: 200px; padding: 0.35rem; }
.matching-correct .hint { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; }
.btn-small { margin-top: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.875rem; }
.modal-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
.btn-secondary { padding: 0.5rem 1rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; }
.btn-link-stimulus { text-decoration: none; margin-right: 0.5rem; }
.btn-link-stimulus:hover { opacity: 0.9; }
.empty-state { text-align: center; padding: 2rem; }
</style>
