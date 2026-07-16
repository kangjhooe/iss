<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import EditorSurat from '@/views/Surat/components/EditorSurat.vue'
import Paper from '@/views/Surat/components/Paper.vue'
import PreviewDialog from '@/views/Surat/components/PreviewDialog.vue'
import { templateService } from '@/views/Surat/services/templateService'
import {
  DEFAULT_LETTER_TYPE_CODE,
  LETTER_TYPES,
  exampleLetterNumber,
  letterTypeLabel
} from '@/views/Surat/utils/letterTypes'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const list = ref([])
const placeholders = ref({})
const loading = ref(true)
const saving = ref(false)
const editing = ref(false)
const previewOpen = ref(false)
const form = ref({
  id: null,
  nama: '',
  kode: '',
  isi_html: '<p></p>',
  status: 'aktif',
  letter_type_code: DEFAULT_LETTER_TYPE_CODE,
  subject_type: 'siswa'
})

const nomorContoh = computed(() => exampleLetterNumber(form.value.letter_type_code))
const hasNomorPlaceholder = computed(() => /\{\{\s*nomor_surat\s*\}\}/.test(form.value.isi_html || ''))

async function load() {
  loading.value = true
  try {
    const res = await templateService.list({ per_page: 100, scope: 'platform' })
    list.value = res.data?.data || []
    placeholders.value = res.data?.placeholders || {}
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat template platform')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    id: null,
    nama: '',
    kode: '',
    isi_html:
      '<p>Nomor : {{nomor_surat}}</p><p style="text-align:justify">...</p><p style="text-align:right">{{kota}}, {{tanggal_lengkap}}</p>',
    status: 'aktif',
    letter_type_code: DEFAULT_LETTER_TYPE_CODE,
    subject_type: 'siswa'
  }
}

function startCreate() {
  resetForm()
  editing.value = true
}

function startEdit(item) {
  form.value = {
    id: item.id,
    nama: item.nama,
    kode: item.kode,
    isi_html: item.isi_html,
    status: item.status,
    letter_type_code: item.letter_type_code || DEFAULT_LETTER_TYPE_CODE,
    subject_type: item.subject_type || 'siswa'
  }
  editing.value = true
}

function insertPlaceholder(key) {
  form.value.isi_html += `{{${key}}}`
}

async function save() {
  if (!form.value.nama.trim() || !form.value.kode.trim()) {
    toast.warning('Perhatian', 'Nama dan kode wajib diisi')
    return
  }
  if (!hasNomorPlaceholder.value) {
    toast.warning('Perhatian', 'Sisipkan placeholder {{nomor_surat}} di isi template.')
    return
  }
  saving.value = true
  try {
    const payload = {
      nama: form.value.nama.trim(),
      kode: form.value.kode.trim().toUpperCase(),
      isi_html: form.value.isi_html,
      status: form.value.status,
      letter_type_code: form.value.letter_type_code,
      subject_type: form.value.subject_type || 'siswa'
      // institution_id sengaja tidak dikirim → backend buat sebagai platform
    }
    if (form.value.id) {
      await templateService.update(form.value.id, payload)
      toast.success('Berhasil', 'Template platform diperbarui')
    } else {
      await templateService.create(payload)
      toast.success('Berhasil', 'Template platform dibuat — tersedia untuk semua sekolah')
    }
    editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

async function toggleStatus(item) {
  try {
    await templateService.toggleStatus(item.id)
    toast.success('Berhasil', 'Status diperbarui')
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal mengubah status')
  }
}

async function remove(item) {
  const ok = await showConfirm({
    title: 'Hapus template platform?',
    message: `Hapus "${item.nama}"? Sekolah tidak akan lagi melihat template ini (salinan yang sudah dibuat tetap ada).`,
    warning: 'Tindakan ini memengaruhi semua institusi.'
  })
  if (!ok) return
  try {
    await templateService.remove(item.id)
    toast.success('Berhasil', 'Template platform dihapus')
    if (form.value.id === item.id) editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

onMounted(load)
</script>

<template>
  <Layout>
    <div class="sa-tpl">
      <div class="page-header">
        <div>
          <h2>Library Template Surat</h2>
          <p>Template platform yang tersedia untuk semua sekolah. Sekolah dapat memakai atau menyalin untuk disesuaikan.</p>
        </div>
        <button type="button" class="btn-primary" @click="startCreate">+ Template Platform</button>
      </div>

      <div class="tpl-grid">
        <aside class="tpl-list">
          <LoadingSkeleton v-if="loading" :rows="5" />
          <template v-else>
            <article
              v-for="item in list"
              :key="item.id"
              class="tpl-card"
              :class="{ active: form.id === item.id && editing }"
            >
              <div class="tpl-card-body" @click="startEdit(item)">
                <h3>{{ item.nama }}</h3>
                <p>{{ item.kode }} · {{ letterTypeLabel(item.letter_type_code) }} · {{ item.status }}</p>
              </div>
              <div class="tpl-card-actions">
                <button type="button" @click="toggleStatus(item)">
                  {{ item.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
                <button type="button" class="danger" @click="remove(item)">Hapus</button>
              </div>
            </article>
            <p v-if="!list.length" class="empty">Belum ada template platform. Buat yang pertama.</p>
          </template>
        </aside>

        <section v-if="editing" class="tpl-editor">
          <div class="form-row">
            <label>
              Nama
              <input v-model="form.nama" type="text" />
            </label>
            <label>
              Kode
              <input v-model="form.kode" type="text" placeholder="SKAB" />
            </label>
            <label>
              Status
              <select v-model="form.status">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </label>
            <label class="span-full">
              Jenis surat
              <select v-model="form.letter_type_code">
                <option v-for="t in LETTER_TYPES" :key="t.code" :value="t.code">
                  {{ t.code }} — {{ t.name }} ({{ t.abbr }})
                </option>
              </select>
            </label>
            <label class="span-full">
              Subjek data
              <select v-model="form.subject_type">
                <option value="siswa">Siswa</option>
                <option value="pegawai">Guru / Pegawai</option>
                <option value="umum">Tanpa subjek (isi manual)</option>
              </select>
            </label>
          </div>

          <div class="nomor-hint">
            <div>
              <strong>Penomoran</strong>
              <p>Wajib ada <code v-pre>{{nomor_surat}}</code> di isi. Contoh: <code>{{ nomorContoh }}</code></p>
            </div>
          </div>

          <div class="placeholder-bar">
            <span>Sisipkan:</span>
            <button
              v-for="(label, key) in placeholders"
              :key="key"
              type="button"
              class="ph-chip"
              :title="label"
              @click="insertPlaceholder(key)"
            >
              {{ '{' + '{' + key + '}' + '}' }}
            </button>
          </div>

          <div class="editor-area">
            <Paper :zoom="0.85">
              <EditorSurat v-model="form.isi_html" />
            </Paper>
          </div>

          <div class="editor-actions">
            <button type="button" class="btn" @click="previewOpen = true">Preview</button>
            <button type="button" class="btn" @click="editing = false">Batal</button>
            <button type="button" class="btn-primary" :disabled="saving" @click="save">
              {{ saving ? 'Menyimpan...' : 'Simpan Template Platform' }}
            </button>
          </div>
        </section>

        <section v-else class="tpl-empty-editor">
          <p>Pilih template di kiri atau buat template platform baru.</p>
        </section>
      </div>
    </div>

    <PreviewDialog
      :open="previewOpen"
      :title="form.nama || 'Preview'"
      :html="form.isi_html"
      @close="previewOpen = false"
      @print="() => { previewOpen = false; window.print() }"
    />

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </Layout>
</template>

<style scoped>
.sa-tpl {
  max-width: 1200px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
}

.page-header h2 {
  margin: 0 0 4px;
  font-size: 1.35rem;
}

.page-header p {
  margin: 0;
  color: #5f6368;
  font-size: 13px;
  max-width: 560px;
}

.tpl-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 16px;
  min-height: 70vh;
}

.tpl-list {
  background: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 8px;
  overflow: auto;
  max-height: 75vh;
}

.tpl-card {
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 6px;
}

.tpl-card.active,
.tpl-card:hover {
  background: #f1f3f4;
}

.tpl-card-body {
  cursor: pointer;
}

.tpl-card h3 {
  margin: 0 0 4px;
  font-size: 14px;
}

.tpl-card p {
  margin: 0;
  font-size: 12px;
  color: #5f6368;
}

.tpl-card-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.tpl-card-actions button {
  border: none;
  background: transparent;
  color: #1a73e8;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}

.tpl-card-actions .danger {
  color: #d93025;
}

.tpl-editor,
.tpl-empty-editor {
  background: #ececec;
  border-radius: 12px;
  border: 1px solid #e5e5e5;
  padding: 12px;
  min-height: 70vh;
}

.tpl-empty-editor {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #80868b;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 10px;
}

.form-row label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  color: #3c4043;
}

.form-row .span-full {
  grid-column: 1 / -1;
}

.form-row input,
.form-row select {
  border: 1px solid #dadce0;
  border-radius: 6px;
  padding: 8px;
  font-size: 13px;
  background: #fff;
}

.nomor-hint {
  background: #e8f0fe;
  border: 1px solid #c6dafc;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
  font-size: 12px;
}

.nomor-hint p {
  margin: 4px 0 0;
}

.nomor-hint code {
  font-family: ui-monospace, monospace;
  background: #fff;
  padding: 1px 5px;
  border-radius: 4px;
}

.placeholder-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  margin-bottom: 10px;
  font-size: 12px;
  color: #5f6368;
}

.ph-chip {
  border: 1px solid #dadce0;
  background: #fff;
  border-radius: 999px;
  padding: 3px 8px;
  font-size: 11px;
  font-family: ui-monospace, monospace;
  cursor: pointer;
}

.editor-area {
  max-height: 55vh;
  overflow: auto;
  background: #ececec;
  border-radius: 8px;
}

.editor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 12px;
}

.btn,
.btn-primary {
  border: 1px solid #dadce0;
  background: #fff;
  border-radius: 6px;
  padding: 8px 14px;
  font-size: 13px;
  cursor: pointer;
}

.btn-primary {
  background: #1a73e8;
  border-color: #1a73e8;
  color: #fff;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty {
  text-align: center;
  color: #80868b;
  padding: 24px;
  font-size: 13px;
}

@media (max-width: 960px) {
  .page-header {
    flex-direction: column;
  }

  .tpl-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
