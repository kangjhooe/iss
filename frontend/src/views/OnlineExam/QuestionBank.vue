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
          <router-link v-if="bankId" :to="`/ujian-online/bank-soal/${bankId}/stimulus`" class="btn-secondary btn-link-stimulus">Kelola Stimulus</router-link>
          <button v-if="bankId" type="button" class="btn-secondary" @click="openImportModal">Import Excel</button>
          <select v-if="!bankId" v-model="filterSubjectId" @change="fetchQuestions(1)">
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

      <template v-else>
        <div class="filters-bar content-card">
          <input
            v-model="filterSearch"
            type="search"
            class="filter-search"
            placeholder="Cari teks soal..."
            autocomplete="off"
            @keydown.enter.prevent="fetchQuestions(1)"
          />
          <select v-model="filterType" class="filter-select" @change="fetchQuestions(1)">
            <option value="">Semua tipe</option>
            <option value="pg">Pilihan ganda</option>
            <option value="pg_kompleks">PG kompleks</option>
            <option value="matching">Mencocokkan</option>
            <option value="isian">Isian</option>
            <option value="uraian">Uraian</option>
          </select>
          <select v-model="filterStimulusId" class="filter-select" @change="fetchQuestions(1)">
            <option value="">Semua stimulus</option>
            <option value="none">Tanpa stimulus</option>
            <option v-for="st in stimuli" :key="st.id" :value="String(st.id)">{{ stimulusOptionLabel(st) }}</option>
          </select>
          <button type="button" class="btn-secondary" @click="fetchQuestions(1)">Cari</button>
          <button v-if="hasActiveFilters" type="button" class="btn-secondary" @click="clearFilters">Reset</button>
        </div>

        <div v-if="filterStimulusId && filterStimulusId !== 'none'" class="stimulus-banner content-card">
          <div>
            <strong>Filter stimulus:</strong> {{ selectedStimulusLabel }}
            <span class="banner-hint">— satu stimulus bisa dipakai banyak soal. Tambah soal baru akan memakai stimulus ini.</span>
          </div>
          <button type="button" class="btn-primary btn-sm" @click="openForm(null, { preferStimulus: filterStimulusId })">
            + Soal dengan stimulus ini
          </button>
        </div>

        <div v-if="loading" class="content-card"><p>Memuat...</p></div>
        <div v-else-if="questions.length === 0" class="empty-state content-card">
          <h3>{{ hasActiveFilters ? 'Tidak ada soal yang cocok' : 'Belum ada soal' }}</h3>
          <p v-if="hasActiveFilters">Ubah filter atau reset pencarian.</p>
          <p v-else>Tambahkan soal ke bank ini. Stimulus opsional — satu stimulus boleh dipakai beberapa soal.</p>
          <button class="btn-primary" @click="openForm()">Tambah Soal</button>
        </div>
        <div v-else class="table-wrap content-card">
          <p class="reorder-hint">Urutkan dengan tombol ↑↓ (urutan dipakai saat soal dipasang ke ujian).</p>
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-ord">Urut</th>
                <th>Soal</th>
                <th>Stimulus</th>
                <th>Tipe</th>
                <th>Bobot</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(q, idx) in questions"
                :key="q.id"
                :class="{ 'drag-over': dragOverIndex === idx, dragging: dragFromIndex === idx }"
                @dragover.prevent="onDragOver($event, idx)"
                @drop="onDrop($event, idx)"
              >
                <td class="col-ord">
                  <span class="drag-handle" draggable="true" title="Seret untuk mengubah urutan" @dragstart="onDragStart($event, idx)" @dragend="onDragEnd">⋮⋮</span>
                  <div class="move-buttons">
                    <button type="button" class="btn-move" title="Naik" :disabled="reorderLoading || idx === 0" @click="moveQuestion(idx, -1)">↑</button>
                    <button type="button" class="btn-move" title="Turun" :disabled="reorderLoading || idx === questions.length - 1" @click="moveQuestion(idx, 1)">↓</button>
                  </div>
                </td>
                <td>{{ stripHtml((q.body || '')).slice(0, 80) }}{{ stripHtml(q.body || '').length > 80 ? '…' : '' }}</td>
                <td>
                  <button
                    v-if="q.stimulus"
                    type="button"
                    class="stimulus-chip"
                    :title="'Filter soal dengan stimulus ini'"
                    @click="filterByStimulus(q.stimulus_id)"
                  >
                    {{ q.stimulus.title || stripHtml(q.stimulus.content || '').slice(0, 30) + '…' }}
                  </button>
                  <span v-else>–</span>
                </td>
                <td>{{ typeLabel(q.type) }}</td>
                <td>{{ q.weight }}</td>
                <td class="row-actions">
                  <TableAction kind="preview" @click="openPreview(q)" />
                  <TableAction kind="edit" @click="openForm(q)" />
                  <TableAction kind="duplicate" :disabled="duplicatingId === q.id" @click="duplicateQuestion(q)" />
                  <TableAction kind="delete" @click="confirmDelete(q)" />
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="pagination && pagination.last_page > 1" class="pagination">
            <button type="button" :disabled="pagination.current_page <= 1" @click="fetchQuestions(pagination.current_page - 1)">Sebelumnya</button>
            <span>Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchQuestions(pagination.current_page + 1)">Selanjutnya</button>
          </div>
        </div>
      </template>

      <!-- Form tambah/edit -->
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
                <option v-for="st in filteredStimuli" :key="st.id" :value="String(st.id)">{{ stimulusOptionLabel(st) }}</option>
              </select>
              <button type="button" class="btn-secondary btn-add-stimulus" @click="openStimulusForm">+ Buat stimulus baru</button>
            </div>
            <p class="hint">Satu stimulus bisa dipakai untuk banyak soal. Pilih stimulus yang sama saat menambah soal berikutnya.</p>
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
            <label>Opsi (A, B, C, D...). Pilih tepat satu jawaban benar.</label>
            <div v-for="(opt, i) in form.options" :key="i" class="option-row option-row-rich">
              <input v-model="opt.option_key" placeholder="A" class="opt-key" />
              <RichTextEditor v-model="opt.body" :placeholder="'Opsi ' + (opt.option_key || (i+1))" min-height="80px" class="opt-editor" />
              <label class="opt-correct">
                <input type="radio" name="pg-correct" :checked="opt.is_correct" @change="setPgCorrect(i)" />
                Benar
              </label>
              <button v-if="form.options.length > 2" type="button" class="btn-remove-opt" title="Hapus opsi" @click="form.options.splice(i, 1)">×</button>
            </div>
            <button type="button" class="btn-small" @click="form.options.push({ option_key: nextOptionKey(), body: '', is_correct: false })">+ Opsi</button>
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
              <button v-if="form.options.length > 2" type="button" class="btn-remove-opt" title="Hapus opsi" @click="form.options.splice(i, 1)">×</button>
            </div>
            <button type="button" class="btn-small" @click="form.options.push({ option_key: nextOptionKey(), body: '', is_correct: false, option_weight: 0 })">+ Opsi</button>
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
            <label>Kunci jawaban (teks tepat) *</label>
            <input v-model="form.key_answer" type="text" />
            <p class="hint">Penilaian otomatis membandingkan teks (tanpa huruf besar/kecil).</p>
            <label class="alias-label">Alias kunci (opsional)</label>
            <div v-for="(alias, ai) in form.key_answer_aliases" :key="'alias'+ai" class="alias-row">
              <input v-model="form.key_answer_aliases[ai]" type="text" :placeholder="'Alias ' + (ai + 1)" />
              <button type="button" class="btn-remove-opt" title="Hapus alias" @click="form.key_answer_aliases.splice(ai, 1)">×</button>
            </div>
            <button type="button" class="btn-small" @click="form.key_answer_aliases.push('')">+ Alias</button>
            <p class="hint">Contoh: kunci <code>H2O</code>, alias <code>h2o</code> / <code>air</code> — semua diterima benar.</p>
          </div>
          <div class="modal-actions">
            <button class="btn-primary" @click="submitQuestion" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
          </div>
        </div>
      </div>

      <!-- Quick-add stimulus -->
      <div v-if="showStimulusForm" class="modal-overlay modal-overlay-stimulus" @click.self="showStimulusForm = false">
        <div class="modal content-card modal-wide">
          <h3>Buat stimulus baru</h3>
          <p class="hint block">Stimulus hanya untuk bank ini. Setelah disimpan akan otomatis terpilih untuk soal ini, dan bisa dipilih lagi untuk soal lain.</p>
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

      <!-- Import Excel -->
      <div v-if="showImportModal" class="modal-overlay" @click.self="showImportModal = false">
        <div class="modal content-card">
          <h3>Import soal (Excel / CSV)</h3>
          <p class="hint block">
            Unduh template, isi baris soal (PG / isian / uraian / PG kompleks), lalu unggah.
            Kolom <code>stimulus_title</code> sama = stimulus bersama untuk beberapa soal.
          </p>
          <div class="modal-actions" style="margin-bottom: 1rem;">
            <button type="button" class="btn-secondary" :disabled="templateLoading" @click="downloadTemplate">
              {{ templateLoading ? 'Mengunduh…' : 'Unduh template' }}
            </button>
          </div>
          <div class="form-group">
            <label>File Excel / CSV</label>
            <input type="file" accept=".xlsx,.xls,.csv,.txt" @change="onImportFileChange" />
          </div>
          <div v-if="importResult" class="import-result">
            <p>Berhasil: {{ importResult.created }} · Gagal: {{ importResult.failed }}</p>
            <ul v-if="importResult.errors?.length">
              <li v-for="(err, i) in importResult.errors.slice(0, 8)" :key="i">{{ err }}</li>
            </ul>
          </div>
          <div class="modal-actions">
            <button class="btn-primary" :disabled="!importFile || importLoading" @click="submitImport">
              {{ importLoading ? 'Mengimpor…' : 'Import' }}
            </button>
            <button type="button" class="btn-secondary" @click="showImportModal = false">Tutup</button>
          </div>
        </div>
      </div>

      <!-- Preview seperti siswa -->
      <div v-if="showPreview" class="modal-overlay" @click.self="closePreview">
        <div class="modal content-card modal-wide preview-modal">
          <div class="preview-header">
            <h3>Preview soal</h3>
            <span class="preview-badge">{{ typeLabel(previewQuestion?.type) }} · bobot {{ previewQuestion?.weight }}</span>
          </div>
          <p class="hint">Tampilan mirip yang dilihat siswa (tanpa kunci jawaban).</p>

          <div v-if="previewQuestion?.stimulus" class="preview-stimulus">
            <div class="preview-stimulus-label">Stimulus</div>
            <div v-if="previewQuestion.stimulus.title" class="preview-stimulus-title">{{ previewQuestion.stimulus.title }}</div>
            <div class="preview-html" v-html="previewQuestion.stimulus.content"></div>
          </div>

          <div class="preview-body">
            <div class="preview-html" v-html="previewQuestion?.body"></div>
          </div>

          <div v-if="previewQuestion?.type === 'pg' || previewQuestion?.type === 'pg_kompleks'" class="preview-options">
            <label
              v-for="(opt, i) in (previewQuestion.options || [])"
              :key="opt.id || i"
              class="preview-option"
            >
              <input
                :type="previewQuestion.type === 'pg' ? 'radio' : 'checkbox'"
                disabled
                :name="'preview-opt'"
              />
              <span class="preview-opt-key">{{ opt.option_key || String.fromCharCode(65 + i) }}.</span>
              <span class="preview-html" v-html="opt.body"></span>
            </label>
          </div>

          <div v-else-if="previewQuestion?.type === 'matching'" class="preview-matching">
            <div class="matching-columns">
              <div class="matching-col">
                <strong>Kolom kiri</strong>
                <div v-for="(item, i) in (previewQuestion.matching_data?.left || [])" :key="'pl'+i" class="matching-item">
                  {{ i + 1 }}. {{ item.text }}
                </div>
              </div>
              <div class="matching-col">
                <strong>Kolom kanan</strong>
                <div v-for="(item, i) in (previewQuestion.matching_data?.right || [])" :key="'pr'+i" class="matching-item">
                  {{ i + 1 }}. {{ item.text }}
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="previewQuestion?.type === 'isian'" class="preview-isian">
            <input type="text" disabled placeholder="Kotak isian jawaban siswa" />
          </div>

          <div v-else-if="previewQuestion?.type === 'uraian'" class="preview-isian">
            <textarea disabled rows="4" placeholder="Kotak uraian jawaban siswa"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-primary" @click="openForm(previewQuestion); closePreview()">Edit soal</button>
            <button type="button" class="btn-secondary" @click="closePreview">Tutup</button>
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
import TableAction from '@/components/TableAction.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { examApi } from '@/api/exam'
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
const filterSearch = ref('')
const filterType = ref('')
const filterStimulusId = ref('')
const pagination = ref(null)
const showForm = ref(false)
const showStimulusForm = ref(false)
const showPreview = ref(false)
const showImportModal = ref(false)
const previewQuestion = ref(null)
const editingId = ref(null)
const saving = ref(false)
const savingStimulus = ref(false)
const duplicatingId = ref(null)
const reorderLoading = ref(false)
const dragFromIndex = ref(null)
const dragOverIndex = ref(null)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)
const templateLoading = ref(false)

const form = reactive({
  subject_id: '',
  stimulus_id: '',
  type: 'pg',
  body: '',
  weight: 1,
  key_answer: '',
  key_answer_aliases: [],
  options: [{ option_key: 'A', body: '', is_correct: false }, { option_key: 'B', body: '', is_correct: false }],
  matching_left: [{ id: '1', text: '' }, { id: '2', text: '' }],
  matching_right: [{ id: '3', text: '' }, { id: '4', text: '' }],
  matching_correct: []
})

const stimulusForm = reactive({ title: '', content: '' })

const filteredStimuli = computed(() => stimuli.value || [])

const hasActiveFilters = computed(() =>
  !!(filterSearch.value.trim() || filterType.value || filterStimulusId.value)
)

const selectedStimulusLabel = computed(() => {
  const st = stimuli.value.find(s => String(s.id) === String(filterStimulusId.value))
  return st ? stimulusOptionLabel(st) : 'Stimulus terpilih'
})

async function fetchQuestions(page = 1) {
  if (!bankId.value && !filterSubjectId.value) {
    questions.value = []
    pagination.value = null
    return
  }
  loading.value = true
  try {
    const params = { per_page: 50, page }
    if (bankId.value) params.bank_soal_id = bankId.value
    else if (filterSubjectId.value) params.subject_id = filterSubjectId.value
    if (filterSearch.value.trim()) params.search = filterSearch.value.trim()
    if (filterType.value) params.type = filterType.value
    if (filterStimulusId.value && filterStimulusId.value !== 'none') {
      params.stimulus_id = filterStimulusId.value
    } else if (filterStimulusId.value === 'none') {
      params.stimulus_id = 'none'
    }
    const res = await examApi.listQuestions(params)
    let list = res.data?.data ?? res.data ?? []
    if (!Array.isArray(list)) list = []
    questions.value = list
    const meta = res.data?.meta
    pagination.value = meta
      ? {
          current_page: meta.current_page,
          last_page: meta.last_page,
          total: meta.total
        }
      : null
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filterSearch.value = ''
  filterType.value = ''
  filterStimulusId.value = ''
  fetchQuestions(1)
}

function filterByStimulus(stimulusId) {
  filterStimulusId.value = stimulusId != null ? String(stimulusId) : ''
  fetchQuestions(1)
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

function nextOptionKey() {
  const used = new Set(form.options.map(o => (o.option_key || '').toUpperCase()))
  for (let i = 0; i < 26; i++) {
    const k = String.fromCharCode(65 + i)
    if (!used.has(k)) return k
  }
  return String(form.options.length + 1)
}

function setPgCorrect(index) {
  form.options.forEach((o, i) => {
    o.is_correct = i === index
  })
}

function addMatchingLeft() {
  form.matching_left.push({ id: '', text: '' })
  form.matching_correct.push('')
}

function openForm(q = null, opts = {}) {
  editingId.value = q?.id ?? null
  form.subject_id = q?.subject_id ?? ''
  const prefer = opts.preferStimulus != null ? String(opts.preferStimulus) : ''
  form.stimulus_id = q?.stimulus_id != null
    ? String(q.stimulus_id)
    : (prefer || (filterStimulusId.value && filterStimulusId.value !== 'none' ? filterStimulusId.value : ''))
  form.type = q?.type ?? 'pg'
  form.body = q?.body ?? ''
  form.weight = q?.weight ?? 1
  form.key_answer = q?.key_answer ?? ''
  form.key_answer_aliases = Array.isArray(q?.key_answer_aliases) ? [...q.key_answer_aliases] : []
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

function openPreview(q) {
  previewQuestion.value = q
  showPreview.value = true
}

function closePreview() {
  showPreview.value = false
  previewQuestion.value = null
}

function openStimulusForm() {
  stimulusForm.title = ''
  stimulusForm.content = ''
  showStimulusForm.value = true
}

async function submitStimulusForm() {
  const contentTrim = stripHtml(stimulusForm.content || '')
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
      await loadStimuli()
      form.stimulus_id = String(newId)
    }
    showStimulusForm.value = false
    toast.success('Stimulus ditambahkan dan dipilih untuk soal ini.')
  } catch (e) {
    const msg = e?.formattedMessage || e?.response?.data?.message || 'Gagal menyimpan'
    toast.error('Gagal menyimpan stimulus', msg)
  } finally {
    savingStimulus.value = false
  }
}

function validateFormClient() {
  if (!stripHtml(form.body || '')) {
    return 'Pertanyaan wajib diisi.'
  }
  if (form.type === 'pg' || form.type === 'pg_kompleks') {
    const opts = form.options.filter(o => stripHtml(o.body || '') !== '')
    if (opts.length < 2) return 'Minimal 2 opsi jawaban.'
    const correct = opts.filter(o => o.is_correct)
    if (form.type === 'pg' && correct.length !== 1) return 'Pilihan ganda harus punya tepat satu jawaban benar.'
    if (form.type === 'pg_kompleks' && correct.length < 1) return 'Pilihan ganda kompleks minimal satu opsi benar.'
    if (form.type === 'pg_kompleks') {
      for (const o of correct) {
        if (!(Number(o.option_weight) > 0)) return 'Opsi benar harus punya bobot lebih dari 0.'
      }
    }
  }
  if (form.type === 'isian' && !(form.key_answer || '').trim() && !form.key_answer_aliases.some(a => (a || '').trim())) {
    return 'Kunci jawaban atau minimal satu alias wajib diisi untuk soal isian.'
  }
  if (form.type === 'matching') {
    const left = form.matching_left.filter(l => l.text.trim())
    const right = form.matching_right.filter(r => r.text.trim())
    if (left.length < 2 || right.length < 2) return 'Kolom kiri dan kanan minimal 2 baris.'
    const paired = form.matching_correct.filter((v, i) => form.matching_left[i]?.text.trim() && v !== '')
    if (paired.length < 1) return 'Minimal satu pasangan benar harus ditentukan.'
  }
  return null
}

async function submitQuestion() {
  const err = validateFormClient()
  if (err) {
    toast.error('Validasi', err)
    return
  }
  saving.value = true
  try {
    const payload = {
      stimulus_id: form.stimulus_id ? Number(form.stimulus_id) : null,
      type: form.type,
      body: form.body,
      weight: form.weight,
      key_answer: form.key_answer || null,
      key_answer_aliases: form.type === 'isian'
        ? form.key_answer_aliases.map(a => (a || '').trim()).filter(Boolean)
        : null
    }
    if (!bankId.value) payload.subject_id = form.subject_id
    if (form.type === 'pg' || form.type === 'pg_kompleks') {
      payload.options = form.options
        .filter(o => stripHtml(o.body || '') !== '')
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
    await loadStimuli()
    fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    const errors = e.response?.data?.errors
    const first = errors && typeof errors === 'object' ? Object.values(errors).flat().find(Boolean) : null
    toast.error('Gagal menyimpan soal', first || e.response?.data?.message || 'Perubahan tidak dapat disimpan. Coba lagi.')
  } finally {
    saving.value = false
  }
}

async function duplicateQuestion(q) {
  if (!q?.id) return
  duplicatingId.value = q.id
  try {
    await examApi.duplicateQuestion(q.id)
    toast.success('Soal diduplikat (stimulus tetap sama jika ada).')
    await loadStimuli()
    fetchQuestions(1)
  } catch (e) {
    toast.error('Gagal menduplikat soal', e.response?.data?.message || 'Coba lagi.')
  } finally {
    duplicatingId.value = null
  }
}

async function applyReorder(orderedIds) {
  if (!bankId.value || !orderedIds.length) return
  reorderLoading.value = true
  try {
    await examApi.reorderQuestions(bankId.value, orderedIds)
    toast.success('Urutan soal diperbarui.')
    await fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal mengubah urutan', e.response?.data?.message || 'Coba lagi.')
  } finally {
    reorderLoading.value = false
  }
}

function moveQuestion(idx, delta) {
  const to = idx + delta
  if (to < 0 || to >= questions.value.length) return
  const ids = questions.value.map(q => q.id)
  const [moved] = ids.splice(idx, 1)
  ids.splice(to, 0, moved)
  applyReorder(ids)
}

function onDragStart(e, idx) {
  dragFromIndex.value = idx
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(idx))
}

function onDragOver(e, idx) {
  e.preventDefault()
  if (dragFromIndex.value === null) return
  if (idx !== dragFromIndex.value) dragOverIndex.value = idx
}

function onDrop(e, toIndex) {
  e.preventDefault()
  const from = dragFromIndex.value
  if (from === null || from === toIndex) {
    dragFromIndex.value = null
    dragOverIndex.value = null
    return
  }
  const ids = questions.value.map(q => q.id)
  const [moved] = ids.splice(from, 1)
  ids.splice(toIndex, 0, moved)
  dragFromIndex.value = null
  dragOverIndex.value = null
  applyReorder(ids)
}

function onDragEnd() {
  dragFromIndex.value = null
  dragOverIndex.value = null
}

function openImportModal() {
  importFile.value = null
  importResult.value = null
  showImportModal.value = true
}

function onImportFileChange(e) {
  importFile.value = e.target?.files?.[0] || null
}

async function downloadTemplate() {
  templateLoading.value = true
  try {
    const res = await examApi.downloadImportTemplate()
    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-import-soal.xlsx'
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal unduh template', e.response?.data?.message || 'Coba lagi.')
  } finally {
    templateLoading.value = false
  }
}

async function submitImport() {
  if (!bankId.value || !importFile.value) return
  importLoading.value = true
  importResult.value = null
  try {
    const res = await examApi.importQuestions(bankId.value, importFile.value)
    importResult.value = res.data?.data ?? res.data
    toast.success(res.data?.message || 'Import selesai.')
    await loadStimuli()
    fetchQuestions(1)
  } catch (e) {
    toast.error('Gagal import', e.response?.data?.message || 'Coba lagi.')
  } finally {
    importLoading.value = false
  }
}

async function confirmDelete(q) {
  if (!confirm('Hapus soal ini?')) return
  try {
    await examApi.deleteQuestion(q.id)
    toast.success('Soal dihapus.')
    await loadStimuli()
    fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal menghapus soal', e.response?.data?.message || 'Soal tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadSubjects()
  if (bankId.value) {
    loadBank()
    fetchQuestions(1)
  }
})

watch(() => route.params.bankId, (id) => {
  bankId.value = id ? Number(id) : null
  currentBank.value = null
  filterSearch.value = ''
  filterType.value = ''
  filterStimulusId.value = ''
  if (bankId.value) {
    loadBank()
    fetchQuestions(1)
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
.btn-primary { padding: 0.5rem 1rem; background: #059669; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.875rem; }
.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; }
.filters-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.filter-search { flex: 1; min-width: 180px; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; min-width: 140px; }
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
.stimulus-banner {
  display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;
  background: #f0fdf4; border: 1px solid #86efac;
}
.banner-hint { display: block; font-size: 0.8125rem; color: #64748b; margin-top: 0.15rem; }
.stimulus-chip {
  background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 999px;
  padding: 0.15rem 0.55rem; font-size: 0.8125rem; cursor: pointer; max-width: 220px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.stimulus-chip:hover { background: #d1fae5; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.row-actions { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.btn-action { font-size: 0.8125rem; background: none; border: none; cursor: pointer; padding: 0.2rem 0.35rem; }
.btn-preview { color: #2563eb; }
.btn-edit { color: #059669; }
.btn-dup { color: #7c3aed; }
.btn-delete { color: #dc2626; }
.pagination { display: flex; gap: 0.75rem; align-items: center; justify-content: center; margin-top: 1rem; }
.pagination button { padding: 0.35rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1050; overflow-y: auto; padding: 1rem; }
.modal-overlay-stimulus { z-index: 1060; }
.modal { max-width: 560px; width: 100%; max-height: calc(100vh - 2rem); overflow-y: auto; }
.modal-wide { max-width: 720px; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.hint { margin: 0.25rem 0 0; font-size: 0.85rem; color: #6b7280; }
.hint.block { margin-bottom: 0.5rem; }
.stimulus-row { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.stimulus-row select { flex: 1; min-width: 200px; }
.btn-add-stimulus { flex-shrink: 0; white-space: nowrap; }
.option-row { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
.option-row-rich { align-items: flex-start; flex-wrap: wrap; }
.option-row-rich .opt-key { width: 44px; flex-shrink: 0; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; }
.option-row-rich .opt-editor { flex: 1; min-width: 200px; }
.option-row-rich .opt-correct { flex-shrink: 0; white-space: nowrap; }
.btn-remove-opt { border: none; background: #fee2e2; color: #b91c1c; border-radius: 4px; width: 28px; height: 28px; cursor: pointer; flex-shrink: 0; }
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
.btn-small { margin-top: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.875rem; cursor: pointer; }
.modal-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
.btn-secondary { padding: 0.5rem 1rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-link-stimulus { text-decoration: none; }
.empty-state { text-align: center; padding: 2rem; }

.preview-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; }
.preview-header h3 { margin: 0; }
.preview-badge { font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 999px; }
.preview-stimulus {
  margin: 1rem 0; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
}
.preview-stimulus-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 0.35rem; }
.preview-stimulus-title { font-weight: 600; margin-bottom: 0.35rem; }
.preview-body { margin: 1rem 0; }
.preview-html :deep(img) { max-width: 100%; height: auto; }
.preview-options { display: flex; flex-direction: column; gap: 0.5rem; }
.preview-option {
  display: flex; gap: 0.5rem; align-items: flex-start; padding: 0.65rem 0.75rem;
  border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;
}
.preview-opt-key { font-weight: 600; flex-shrink: 0; }
.preview-matching .matching-columns { display: flex; gap: 1.5rem; }
.preview-isian input,
.preview-isian textarea {
  width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;
}
.reorder-hint { font-size: 0.8125rem; color: #64748b; margin: 0 0 0.75rem; }
.col-ord { width: 72px; vertical-align: middle; }
.drag-handle { cursor: grab; color: #94a3b8; margin-right: 0.25rem; user-select: none; }
.move-buttons { display: inline-flex; flex-direction: column; gap: 0.1rem; }
.btn-move {
  border: 1px solid #e2e8f0; background: #fff; border-radius: 4px; width: 22px; height: 18px;
  line-height: 1; font-size: 0.7rem; cursor: pointer; padding: 0;
}
.btn-move:disabled { opacity: 0.4; cursor: not-allowed; }
tr.drag-over { background: #ecfdf5; }
tr.dragging { opacity: 0.6; }
.alias-label { display: block; margin-top: 0.75rem; margin-bottom: 0.25rem; font-size: 0.875rem; }
.alias-row { display: flex; gap: 0.35rem; align-items: center; margin-bottom: 0.35rem; }
.alias-row input { flex: 1; }
.import-result { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; font-size: 0.875rem; }
.import-result ul { margin: 0.35rem 0 0; padding-left: 1.1rem; color: #b91c1c; }

@media (max-width: 768px) {
  .filter-search,
  .filter-select {
    min-width: 0;
    width: 100%;
  }
  .filters,
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .stimulus-row,
  .option-row-rich,
  .alias-row {
    flex-direction: column;
    align-items: stretch;
  }
  .stimulus-row select,
  .option-row-rich .opt-editor,
  .matching-correct .correct-row select {
    min-width: 0;
    width: 100%;
  }
}
</style>
