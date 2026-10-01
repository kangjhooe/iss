<script setup>
import { computed, onMounted, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import { useAuthStore } from '@/stores/auth'
import EditorSurat from '../components/EditorSurat.vue'
import Paper from '../components/Paper.vue'
import PreviewDialog from '../components/PreviewDialog.vue'
import SuratSubNav from '../components/SuratSubNav.vue'
import TableAction from '@/components/TableAction.vue'
import { templateService } from '../services/templateService'
import {
  DEFAULT_LETTER_TYPE_CODE,
  LETTER_TYPES,
  exampleLetterNumber,
  letterTypeLabel,
  suratDensityForLetterType
} from '../utils/letterTypes'

const toast = useToast()
const authStore = useAuthStore()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const list = ref([])
const placeholders = ref({})
const loading = ref(true)
const saving = ref(false)
const forking = ref(false)
const editing = ref(false)
const previewOpen = ref(false)
const form = ref({
  id: null,
  nama: '',
  kode: '',
  isi_html: '<p></p>',
  status: 'aktif',
  letter_type_code: DEFAULT_LETTER_TYPE_CODE,
  subject_type: 'siswa',
  is_platform: false,
  institution_id: null
})

const isSuperAdmin = computed(() => authStore.user?.role === 'super_admin')
const isPlatformSelected = computed(() => !!form.value.is_platform)
const canEditSelected = computed(() => {
  if (!form.value.id) return true
  if (isPlatformSelected.value) return isSuperAdmin.value
  return true
})
const nomorContoh = computed(() => exampleLetterNumber(form.value.letter_type_code))
const suratDensity = computed(() => suratDensityForLetterType(form.value.letter_type_code))
const hasNomorPlaceholder = computed(() => /\{\{\s*nomor_surat\s*\}\}/.test(form.value.isi_html || ''))

const platformList = computed(() => list.value.filter((t) => t.is_platform || t.institution_id == null))
const ownList = computed(() => list.value.filter((t) => !(t.is_platform || t.institution_id == null)))

function isPlatform(item) {
  return !!(item?.is_platform || item?.institution_id == null)
}

async function load() {
  loading.value = true
  try {
    const res = await templateService.list({ per_page: 100 })
    list.value = res.data?.data || []
    placeholders.value = res.data?.placeholders || {}
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat template')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    id: null,
    nama: '',
    kode: '',
    isi_html: '<p>Nomor : {{nomor_surat}}</p><p>Nama: {{nama}}</p><p style="text-align:right">{{kota}}, {{tanggal_lengkap}}</p>',
    status: 'aktif',
    letter_type_code: DEFAULT_LETTER_TYPE_CODE,
    subject_type: 'siswa',
    is_platform: false,
    institution_id: null
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
    subject_type: item.subject_type || 'siswa',
    is_platform: isPlatform(item),
    institution_id: item.institution_id
  }
  editing.value = true
}

function insertPlaceholder(key) {
  if (!canEditSelected.value) return
  form.value.isi_html += `{{${key}}}`
}

async function save() {
  if (!canEditSelected.value) {
    toast.warning('Perhatian', 'Template platform bersifat baca saja. Salin ke sekolah Anda untuk menyesuaikan.')
    return
  }
  if (!form.value.nama.trim() || !form.value.kode.trim()) {
    toast.warning('Perhatian', 'Nama dan kode wajib diisi')
    return
  }
  if (!form.value.letter_type_code) {
    toast.warning('Perhatian', 'Pilih jenis surat untuk penomoran resmi')
    return
  }
  if (!hasNomorPlaceholder.value) {
    toast.warning(
      'Perhatian',
      'Sisipkan placeholder {{nomor_surat}} di isi template. Nomor resmi digenerate saat Terbitkan.'
    )
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
    }
    if (form.value.id) {
      await templateService.update(form.value.id, payload)
      toast.success('Berhasil', 'Template diperbarui')
    } else {
      await templateService.create(payload)
      toast.success('Berhasil', 'Template dibuat')
    }
    editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan template')
  } finally {
    saving.value = false
  }
}

async function forkTemplate(item) {
  const target = item || form.value
  if (!target?.id) return
  forking.value = true
  try {
    const res = await templateService.fork(target.id)
    toast.success('Berhasil', 'Template disalin ke sekolah Anda. Silakan sesuaikan jika perlu.')
    await load()
    const copy = res.data?.data
    if (copy) startEdit(copy)
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyalin template')
  } finally {
    forking.value = false
  }
}

async function toggleStatus(item) {
  if (isPlatform(item) && !isSuperAdmin.value) {
    toast.warning('Perhatian', 'Status template platform hanya diubah oleh Super Admin.')
    return
  }
  try {
    await templateService.toggleStatus(item.id)
    toast.success('Berhasil', 'Status diperbarui')
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal mengubah status')
  }
}

async function remove(item) {
  if (isPlatform(item) && !isSuperAdmin.value) {
    toast.warning('Perhatian', 'Template platform hanya dapat dihapus oleh Super Admin.')
    return
  }
  const ok = await showConfirm({
    title: 'Hapus template?',
    message: `Hapus template "${item.nama}"?`
  })
  if (!ok) return
  try {
    await templateService.remove(item.id)
    toast.success('Berhasil', 'Template dihapus')
    if (form.value.id === item.id) editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

onMounted(load)
</script>

<template>
    <div class="tpl-page">
      <SuratSubNav
        title="Template Surat"
        subtitle="Pakai template platform atau buat template milik sekolah"
      >
        <template #actions>
          <button type="button" class="btn-primary" @click="startCreate">+ Template Sekolah</button>
        </template>
      </SuratSubNav>

      <div class="tpl-grid">
        <aside class="tpl-list">
          <LoadingSkeleton v-if="loading" :rows="5" />
          <template v-else>
            <div v-if="platformList.length" class="tpl-section">
              <h4 class="tpl-section-title">Template Platform</h4>
              <article
                v-for="item in platformList"
                :key="'p-' + item.id"
                class="tpl-card"
                :class="{ active: form.id === item.id && editing }"
              >
                <div class="tpl-card-body" @click="startEdit(item)">
                  <div class="tpl-card-top">
                    <h3>{{ item.nama }}</h3>
                    <span class="badge badge-platform">Platform</span>
                  </div>
                  <p>{{ item.kode }} · {{ letterTypeLabel(item.letter_type_code) }} · {{ item.status }}</p>
                </div>
                <div class="tpl-card-actions">
                  <button type="button" @click="forkTemplate(item)" :disabled="forking">
                    Salin ke sekolah
                  </button>
                </div>
              </article>
            </div>

            <div class="tpl-section">
              <h4 class="tpl-section-title">Template Sekolah</h4>
              <article
                v-for="item in ownList"
                :key="'o-' + item.id"
                class="tpl-card"
                :class="{ active: form.id === item.id && editing }"
              >
                <div class="tpl-card-body" @click="startEdit(item)">
                  <div class="tpl-card-top">
                    <h3>{{ item.nama }}</h3>
                    <span class="badge badge-own">Sekolah</span>
                  </div>
                  <p>{{ item.kode }} · {{ letterTypeLabel(item.letter_type_code) }} · {{ item.status }}</p>
                </div>
                <div class="tpl-card-actions">
                  <button type="button" @click="toggleStatus(item)">
                    {{ item.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                  </button>
                  <TableAction kind="delete" @click="remove(item)" />
                </div>
              </article>
              <p v-if="!ownList.length" class="empty">
                Belum ada template sekolah. Salin dari platform atau buat baru.
              </p>
            </div>
          </template>
        </aside>

        <section v-if="editing" class="tpl-editor">
          <div v-if="isPlatformSelected && !isSuperAdmin" class="platform-banner">
            <div>
              <strong>Template platform (baca saja)</strong>
              <p>
                Disediakan Super Admin untuk semua sekolah. Untuk menyesuaikan teks,
                salin dulu ke sekolah Anda.
              </p>
            </div>
            <button type="button" class="btn-primary" :disabled="forking" @click="forkTemplate()">
              {{ forking ? 'Menyalin...' : 'Salin ke sekolah saya' }}
            </button>
          </div>

          <div class="form-row">
            <label>
              Nama
              <input v-model="form.nama" type="text" :readonly="!canEditSelected" />
            </label>
            <label>
              Kode
              <input v-model="form.kode" type="text" placeholder="SKAB" :readonly="!canEditSelected" />
            </label>
            <label>
              Status
              <select v-model="form.status" :disabled="!canEditSelected">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </label>
            <label class="span-full">
              Jenis surat (untuk penomoran Arsip)
              <select v-model="form.letter_type_code" :disabled="!canEditSelected">
                <option v-for="t in LETTER_TYPES" :key="t.code" :value="t.code">
                  {{ t.code }} — {{ t.name }} ({{ t.abbr }})
                </option>
              </select>
            </label>
            <label class="span-full">
              Subjek data (saat buat surat dari template)
              <select v-model="form.subject_type" :disabled="!canEditSelected">
                <option value="siswa">Siswa</option>
                <option value="pegawai">Guru / Pegawai</option>
                <option value="umum">Tanpa subjek (isi manual)</option>
              </select>
            </label>
          </div>

          <div class="nomor-hint">
            <div>
              <strong>Penomoran resmi</strong>
              <p>
                Nomor digenerate otomatis saat surat <em>diterbitkan</em> (masuk Arsip),
                memakai jenis di atas. Di isi template wajib ada
                <code v-pre>{{nomor_surat}}</code> — jangan ketik nomor manual.
              </p>
            </div>
            <div class="nomor-contoh">
              <span>Contoh nomor</span>
              <code>{{ nomorContoh }}</code>
            </div>
          </div>
          <p v-if="!hasNomorPlaceholder" class="nomor-warn">
            Belum ada placeholder <code v-pre>{{nomor_surat}}</code> di isi template.
          </p>

          <div v-if="canEditSelected" class="placeholder-bar">
            <span>Sisipkan:</span>
            <button
              v-for="(label, key) in placeholders"
              :key="key"
              type="button"
              class="ph-chip"
              :class="{ 'ph-chip--nomor': key === 'nomor_surat' }"
              :title="label"
              @click="insertPlaceholder(key)"
            >
              {{ '{' + '{' + key + '}' + '}' }}
            </button>
          </div>

          <div class="editor-area">
            <Paper :zoom="0.85" :density="suratDensity">
              <EditorSurat v-model="form.isi_html" :disabled="!canEditSelected" />
            </Paper>
          </div>

          <div class="editor-actions">
            <button type="button" class="btn" @click="previewOpen = true">Preview</button>
            <button type="button" class="btn" @click="editing = false">Batal</button>
            <button
              v-if="canEditSelected"
              type="button"
              class="btn-primary"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan Template' }}
            </button>
          </div>
        </section>

        <section v-else class="tpl-empty-editor">
          <p>Pilih template di kiri, salin dari platform, atau buat template sekolah baru.</p>
        </section>
      </div>
    </div>

    <PreviewDialog
      :open="previewOpen"
      :title="form.nama || 'Preview Template'"
      :html="form.isi_html"
      :density="suratDensity"
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
</template>

<style scoped>
.tpl-page {
  margin: -8px;
}

.tpl-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
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

.tpl-section {
  margin-bottom: 12px;
}

.tpl-section-title {
  margin: 8px 6px 6px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #80868b;
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

.tpl-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
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

.badge {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 999px;
  line-height: 1.4;
}

.badge-platform {
  background: #e8f0fe;
  color: #174ea6;
}

.badge-own {
  background: #e6f4ea;
  color: #137333;
}

.tpl-card-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
  flex-wrap: wrap;
}

.tpl-card-actions button {
  border: none;
  background: transparent;
  color: #1a73e8;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}

.tpl-card-actions button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.platform-banner {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  background: #e8f0fe;
  border: 1px solid #c6dafc;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 10px;
  font-size: 12px;
  color: #3c4043;
}

.platform-banner p {
  margin: 4px 0 0;
  line-height: 1.45;
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

.form-row input[readonly],
.form-row select:disabled {
  background: #f8f9fa;
  color: #5f6368;
}

.nomor-hint {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  background: #e8f0fe;
  border: 1px solid #c6dafc;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
  font-size: 12px;
  color: #3c4043;
}

.nomor-hint p {
  margin: 4px 0 0;
  line-height: 1.45;
}

.nomor-hint code {
  font-family: ui-monospace, monospace;
  background: #fff;
  padding: 1px 5px;
  border-radius: 4px;
}

.nomor-contoh {
  flex-shrink: 0;
  text-align: right;
}

.nomor-contoh span {
  display: block;
  font-size: 11px;
  color: #5f6368;
  margin-bottom: 4px;
}

.nomor-contoh code {
  display: inline-block;
  font-size: 12px;
  font-weight: 600;
  color: #174ea6;
  background: #fff;
  border: 1px solid #c6dafc;
  padding: 6px 8px;
  border-radius: 6px;
}

.nomor-warn {
  margin: 0 0 10px;
  font-size: 12px;
  color: #b06000;
  background: #fef7e0;
  border: 1px solid #f6d977;
  border-radius: 6px;
  padding: 8px 10px;
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

.ph-chip:hover {
  background: #e8f0fe;
  border-color: #1a73e8;
  color: #1a73e8;
}

.ph-chip--nomor {
  border-color: #1a73e8;
  background: #e8f0fe;
  color: #174ea6;
  font-weight: 600;
}

.editor-area {
  max-height: 60vh;
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
  padding: 16px 12px;
  font-size: 13px;
}

@media (max-width: 960px) {
  .tpl-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr 1fr;
  }

  .form-row .span-full {
    grid-column: 1 / -1;
  }

  .nomor-hint,
  .platform-banner {
    flex-direction: column;
    align-items: stretch;
  }

  .nomor-contoh {
    text-align: left;
  }
}
</style>
