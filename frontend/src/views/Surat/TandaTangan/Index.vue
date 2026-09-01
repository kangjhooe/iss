<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import SuratSubNav from '../components/SuratSubNav.vue'
import TableAction from '@/components/TableAction.vue'
import { asetTandaTanganService } from '../services/asetTandaTanganService'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const list = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(false)
const filterJenis = ref('')
const form = ref(emptyForm())
const file = ref(null)
const filePreview = ref(null)

function revokeFilePreview() {
  if (filePreview.value) {
    URL.revokeObjectURL(filePreview.value)
    filePreview.value = null
  }
}

function onFileChange(event) {
  const selected = event.target.files?.[0] || null
  revokeFilePreview()
  file.value = selected
  filePreview.value = selected ? URL.createObjectURL(selected) : null
}

function clearFile() {
  revokeFilePreview()
  file.value = null
}

const filtered = computed(() => {
  if (!filterJenis.value) return list.value
  return list.value.filter((i) => i.jenis === filterJenis.value)
})

function emptyForm() {
  return {
    id: null,
    jenis: 'tanda_tangan',
    nama: '',
    pemilik_nama: '',
    pemilik_jabatan: '',
    pemilik_nip: '',
    lebar_mm: 40,
    tinggi_mm: 20,
    is_default: false,
    status: 'aktif',
    file_url: null
  }
}

async function load() {
  loading.value = true
  try {
    const res = await asetTandaTanganService.list()
    list.value = res.data?.data || []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat aset')
  } finally {
    loading.value = false
  }
}

function startCreate(jenis = 'tanda_tangan') {
  form.value = emptyForm()
  form.value.jenis = jenis
  form.value.lebar_mm = jenis === 'stempel' ? 35 : 40
  form.value.tinggi_mm = jenis === 'stempel' ? 35 : 20
  clearFile()
  editing.value = true
}

function startEdit(item) {
  form.value = {
    id: item.id,
    jenis: item.jenis,
    nama: item.nama,
    pemilik_nama: item.pemilik_nama || '',
    pemilik_jabatan: item.pemilik_jabatan || '',
    pemilik_nip: item.pemilik_nip || '',
    lebar_mm: item.lebar_mm || 40,
    tinggi_mm: item.tinggi_mm || 20,
    is_default: !!item.is_default,
    status: item.status,
    file_url: item.file_url
  }
  clearFile()
  editing.value = true
}

function buildFormData() {
  const fd = new FormData()
  const f = form.value
  fd.append('jenis', f.jenis)
  fd.append('nama', f.nama)
  fd.append('pemilik_nama', f.pemilik_nama || '')
  fd.append('pemilik_jabatan', f.pemilik_jabatan || '')
  fd.append('pemilik_nip', f.pemilik_nip || '')
  fd.append('lebar_mm', String(f.lebar_mm || 40))
  fd.append('tinggi_mm', String(f.tinggi_mm || 20))
  fd.append('is_default', f.is_default ? '1' : '0')
  fd.append('status', f.status)
  if (file.value) fd.append('file', file.value)
  return fd
}

async function save() {
  if (!form.value.nama.trim()) {
    toast.warning('Perhatian', 'Nama wajib diisi')
    return
  }
  if (!form.value.id && !file.value) {
    toast.warning('Perhatian', 'File gambar wajib diunggah')
    return
  }
  saving.value = true
  try {
    const fd = buildFormData()
    if (form.value.id) {
      await asetTandaTanganService.update(form.value.id, fd)
      toast.success('Berhasil', 'Aset diperbarui')
    } else {
      await asetTandaTanganService.create(fd)
      toast.success('Berhasil', 'Aset dibuat')
    }
    editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  const ok = await showConfirm({ title: 'Hapus aset?', message: `Hapus "${item.nama}"?` })
  if (!ok) return
  try {
    await asetTandaTanganService.remove(item.id)
    toast.success('Berhasil', 'Aset dihapus')
    if (form.value.id === item.id) editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

onMounted(load)
onBeforeUnmount(clearFile)
</script>

<template>    <div class="page">
      <SuratSubNav
        title="Tanda Tangan & Stempel"
        subtitle="Unggah aset PNG transparan. Dipakai opsional di setiap surat."
      >
        <template #actions>
          <button type="button" class="btn" @click="startCreate('tanda_tangan')">+ Tanda Tangan</button>
          <button type="button" class="btn-primary" @click="startCreate('stempel')">+ Stempel</button>
        </template>
      </SuratSubNav>

      <div class="filters">
        <button type="button" :class="{ active: !filterJenis }" @click="filterJenis = ''">Semua</button>
        <button type="button" :class="{ active: filterJenis === 'tanda_tangan' }" @click="filterJenis = 'tanda_tangan'">Tanda Tangan</button>
        <button type="button" :class="{ active: filterJenis === 'stempel' }" @click="filterJenis = 'stempel'">Stempel</button>
      </div>

      <div class="grid">
        <aside class="list-panel">
          <LoadingSkeleton v-if="loading" :rows="4" />
          <article v-for="item in filtered" :key="item.id" class="card" :class="{ active: editing && form.id === item.id }">
            <div class="card-body" @click="startEdit(item)">
              <img v-if="item.file_url" :src="item.file_url" class="thumb" alt="" />
              <div>
                <h3>{{ item.nama }} <span v-if="item.is_default" class="badge">Default</span></h3>
                <p>{{ item.jenis === 'stempel' ? 'Stempel' : 'Tanda tangan' }} · {{ item.pemilik_nama || '—' }}</p>
              </div>
            </div>
            <TableAction kind="delete" @click="remove(item)" />
          </article>
          <p v-if="!loading && !filtered.length" class="empty">Belum ada aset</p>
        </aside>

        <section v-if="editing" class="form-panel">
          <div class="fields">
            <label>Jenis
              <select v-model="form.jenis">
                <option value="tanda_tangan">Tanda tangan</option>
                <option value="stempel">Stempel</option>
              </select>
            </label>
            <label>Nama<input v-model="form.nama" type="text" /></label>
            <label>Nama pejabat<input v-model="form.pemilik_nama" type="text" /></label>
            <label>Jabatan<input v-model="form.pemilik_jabatan" type="text" placeholder="Kepala Madrasah" /></label>
            <label>NIP<input v-model="form.pemilik_nip" type="text" /></label>
            <label>Lebar (mm)<input v-model.number="form.lebar_mm" type="number" min="10" max="80" /></label>
            <label>Tinggi (mm)<input v-model.number="form.tinggi_mm" type="number" min="10" max="80" /></label>
            <label class="full">File gambar (PNG transparan disarankan)
              <input type="file" accept="image/*" @change="onFileChange" />
            </label>
            <label class="check"><input v-model="form.is_default" type="checkbox" /> Jadikan default untuk jenis ini</label>
            <label>Status
              <select v-model="form.status">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </label>
          </div>

          <div class="preview-box">
            <img
              v-if="filePreview || form.file_url"
              :src="filePreview || form.file_url"
              :style="{ width: form.lebar_mm + 'mm', height: form.tinggi_mm + 'mm' }"
              alt="Preview"
            />
            <p v-else class="empty">Preview gambar</p>
          </div>

          <div class="actions">
            <button type="button" class="btn" @click="editing = false">Batal</button>
            <button type="button" class="btn-primary" :disabled="saving" @click="save">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </section>

        <section v-else class="form-panel empty-editor">
          <p>Pilih aset di kiri atau buat baru.</p>
        </section>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :loading="confirmDialog.loading"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    /></template>

<style scoped>
.filters { display:flex; gap:8px; margin-bottom:12px; }
.filters button { border:1px solid #dadce0; background:#fff; border-radius:999px; padding:6px 12px; cursor:pointer; font-size:12px; }
.filters button.active { background:#e8f0fe; border-color:#1a73e8; color:#1a73e8; }
.grid { display:grid; grid-template-columns:340px 1fr; gap:16px; min-height:70vh; }
.list-panel, .form-panel { background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:12px; }
.card { padding:10px; border-radius:8px; margin-bottom:6px; }
.card.active, .card:hover { background:#f1f3f4; }
.card-body { display:flex; gap:10px; align-items:center; cursor:pointer; }
.thumb { width:48px; height:48px; object-fit:contain; background:#fafafa; border-radius:6px; }
.card h3 { margin:0 0 4px; font-size:14px; }
.card p { margin:0; font-size:12px; color:#5f6368; }
.badge { font-size:10px; background:#e8f0fe; color:#1a73e8; border-radius:999px; padding:2px 6px; margin-left:6px; }
.link-danger { border:none; background:transparent; color:#d93025; font-size:12px; cursor:pointer; margin-top:6px; }
.fields { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.fields label { display:flex; flex-direction:column; gap:4px; font-size:12px; color:#3c4043; }
.fields .full { grid-column:1 / -1; }
.fields .check { flex-direction:row; align-items:center; gap:8px; }
.fields input, .fields select { border:1px solid #dadce0; border-radius:6px; padding:8px; font-size:13px; }
.preview-box { margin-top:16px; background:#ececec; border-radius:8px; padding:24px; display:flex; justify-content:center; align-items:center; min-height:140px; }
.preview-box img { object-fit:contain; background:#fff; padding:8px; border-radius:6px; }
.actions { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
.btn, .btn-primary { border:1px solid #dadce0; background:#fff; border-radius:6px; padding:8px 14px; cursor:pointer; font-size:13px; }
.btn-primary { background:#1a73e8; border-color:#1a73e8; color:#fff; }
.empty, .empty-editor { text-align:center; color:#80868b; padding:24px; }
.empty-editor { display:flex; align-items:center; justify-content:center; }
@media (max-width:960px){ .grid{grid-template-columns:1fr;} .fields{grid-template-columns:1fr;} }
</style>
