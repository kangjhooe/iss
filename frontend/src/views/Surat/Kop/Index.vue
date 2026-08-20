<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import SuratSubNav from '../components/SuratSubNav.vue'
import TableAction from '@/components/TableAction.vue'
import { kopService } from '../services/kopService'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const list = ref([])
const loading = ref(true)
const saving = ref(false)
const editing = ref(false)
const form = ref(emptyForm())
const logoKiriFile = ref(null)
const logoKananFile = ref(null)
const logoKiriPreview = ref(null)
const logoKananPreview = ref(null)

function revokePreview(urlRef) {
  if (urlRef.value) {
    URL.revokeObjectURL(urlRef.value)
    urlRef.value = null
  }
}

function onLogoKiriChange(event) {
  const file = event.target.files?.[0] || null
  revokePreview(logoKiriPreview)
  logoKiriFile.value = file
  logoKiriPreview.value = file ? URL.createObjectURL(file) : null
}

function onLogoKananChange(event) {
  const file = event.target.files?.[0] || null
  revokePreview(logoKananPreview)
  logoKananFile.value = file
  logoKananPreview.value = file ? URL.createObjectURL(file) : null
}

function clearLogoFiles() {
  revokePreview(logoKiriPreview)
  revokePreview(logoKananPreview)
  logoKiriFile.value = null
  logoKananFile.value = null
}

function emptyForm() {
  return {
    id: null,
    nama: '',
    baris_1: '',
    baris_2: '',
    baris_3: '',
    alamat: '',
    telepon: '',
    email: '',
    website: '',
    tampilkan_garis: true,
    is_default: false,
    status: 'aktif',
    logo_kiri_url: null,
    logo_kanan_url: null
  }
}

async function load() {
  loading.value = true
  try {
    const res = await kopService.list()
    list.value = res.data?.data || []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat kop')
  } finally {
    loading.value = false
  }
}

function startCreate() {
  form.value = emptyForm()
  clearLogoFiles()
  editing.value = true
}

function startEdit(item) {
  form.value = {
    id: item.id,
    nama: item.nama,
    baris_1: item.baris_1 || '',
    baris_2: item.baris_2 || '',
    baris_3: item.baris_3 || '',
    alamat: item.alamat || '',
    telepon: item.telepon || '',
    email: item.email || '',
    website: item.website || '',
    tampilkan_garis: !!item.tampilkan_garis,
    is_default: !!item.is_default,
    status: item.status,
    logo_kiri_url: item.logo_kiri_url,
    logo_kanan_url: item.logo_kanan_url
  }
  clearLogoFiles()
  editing.value = true
}

function buildFormData() {
  const fd = new FormData()
  const f = form.value
  fd.append('nama', f.nama)
  fd.append('baris_1', f.baris_1 || '')
  fd.append('baris_2', f.baris_2 || '')
  fd.append('baris_3', f.baris_3 || '')
  fd.append('alamat', f.alamat || '')
  fd.append('telepon', f.telepon || '')
  fd.append('email', f.email || '')
  fd.append('website', f.website || '')
  fd.append('tampilkan_garis', f.tampilkan_garis ? '1' : '0')
  fd.append('is_default', f.is_default ? '1' : '0')
  fd.append('status', f.status)
  if (logoKiriFile.value) fd.append('logo_kiri', logoKiriFile.value)
  if (logoKananFile.value) fd.append('logo_kanan', logoKananFile.value)
  return fd
}

async function save() {
  if (!form.value.nama.trim()) {
    toast.warning('Perhatian', 'Nama kop wajib diisi')
    return
  }
  saving.value = true
  try {
    const fd = buildFormData()
    if (form.value.id) {
      await kopService.update(form.value.id, fd)
      toast.success('Berhasil', 'Kop diperbarui')
    } else {
      await kopService.create(fd)
      toast.success('Berhasil', 'Kop dibuat')
    }
    editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan kop')
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  const ok = await showConfirm({ title: 'Hapus kop?', message: `Hapus "${item.nama}"?` })
  if (!ok) return
  try {
    await kopService.remove(item.id)
    toast.success('Berhasil', 'Kop dihapus')
    if (form.value.id === item.id) editing.value = false
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

onMounted(load)
onBeforeUnmount(clearLogoFiles)
</script>

<template>
  <Layout>
    <div class="page">
      <SuratSubNav
        title="Manajemen KOP"
        subtitle="Atur kop resmi yang bisa dipilih opsional di setiap surat"
      >
        <template #actions>
          <button type="button" class="btn-primary" @click="startCreate">+ Kop Baru</button>
        </template>
      </SuratSubNav>

      <div class="grid">
        <aside class="list-panel">
          <LoadingSkeleton v-if="loading" :rows="4" />
          <article v-for="item in list" :key="item.id" class="card" :class="{ active: editing && form.id === item.id }">
            <div class="card-body" @click="startEdit(item)">
              <h3>{{ item.nama }} <span v-if="item.is_default" class="badge">Default</span></h3>
              <p>{{ item.baris_1 || '—' }} · {{ item.status }}</p>
            </div>
            <TableAction kind="delete" @click="remove(item)" />
          </article>
          <p v-if="!loading && !list.length" class="empty">Belum ada kop</p>
        </aside>

        <section v-if="editing" class="form-panel">
          <div class="fields">
            <label>Nama kop<input v-model="form.nama" type="text" /></label>
            <label>Baris 1 (instansi atas)<input v-model="form.baris_1" type="text" /></label>
            <label>Baris 2 (nama madrasah/sekolah)<input v-model="form.baris_2" type="text" /></label>
            <label>Baris 3 (opsional)<input v-model="form.baris_3" type="text" /></label>
            <label class="full">Alamat<textarea v-model="form.alamat" rows="2" /></label>
            <label>Telepon<input v-model="form.telepon" type="text" /></label>
            <label>Email<input v-model="form.email" type="email" /></label>
            <label>Website<input v-model="form.website" type="text" /></label>
            <label>Logo kiri
              <input type="file" accept="image/*" @change="onLogoKiriChange" />
              <img v-if="logoKiriPreview || form.logo_kiri_url" :src="logoKiriPreview || form.logo_kiri_url" class="thumb" alt="Logo kiri" />
            </label>
            <label>Logo kanan
              <input type="file" accept="image/*" @change="onLogoKananChange" />
              <img v-if="logoKananPreview || form.logo_kanan_url" :src="logoKananPreview || form.logo_kanan_url" class="thumb" alt="Logo kanan" />
            </label>
            <label class="check"><input v-model="form.tampilkan_garis" type="checkbox" /> Tampilkan garis bawah kop</label>
            <label class="check"><input v-model="form.is_default" type="checkbox" /> Jadikan default</label>
            <label>Status
              <select v-model="form.status">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </label>
          </div>

          <div class="preview-box">
            <div class="preview-kop">
              <div class="preview-row">
                <img v-if="logoKiriPreview || form.logo_kiri_url" :src="logoKiriPreview || form.logo_kiri_url" alt="" />
                <div class="preview-text">
                  <strong>{{ form.baris_1 || 'BARIS 1' }}</strong>
                  <strong>{{ form.baris_2 || 'BARIS 2' }}</strong>
                  <span v-if="form.baris_3">{{ form.baris_3 }}</span>
                  <small>{{ form.alamat || 'Alamat...' }}</small>
                </div>
                <img v-if="logoKananPreview || form.logo_kanan_url" :src="logoKananPreview || form.logo_kanan_url" alt="" />
              </div>
              <div v-if="form.tampilkan_garis" class="preview-line" />
            </div>
          </div>

          <div class="actions">
            <button type="button" class="btn" @click="editing = false">Batal</button>
            <button type="button" class="btn-primary" :disabled="saving" @click="save">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </section>

        <section v-else class="form-panel empty-editor">
          <p>Pilih kop di kiri atau buat kop baru.</p>
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
    />
  </Layout>
</template>

<style scoped>
.grid { display:grid; grid-template-columns:300px 1fr; gap:16px; min-height:70vh; }
.list-panel, .form-panel { background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:12px; }
.card { padding:10px; border-radius:8px; margin-bottom:6px; }
.card.active, .card:hover { background:#f1f3f4; }
.card-body { cursor:pointer; }
.card h3 { margin:0 0 4px; font-size:14px; }
.card p { margin:0; font-size:12px; color:#5f6368; }
.badge { font-size:10px; background:#e8f0fe; color:#1a73e8; border-radius:999px; padding:2px 6px; margin-left:6px; }
.link-danger { border:none; background:transparent; color:#d93025; font-size:12px; cursor:pointer; margin-top:6px; }
.fields { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.fields label { display:flex; flex-direction:column; gap:4px; font-size:12px; color:#3c4043; }
.fields .full { grid-column:1 / -1; }
.fields .check { flex-direction:row; align-items:center; gap:8px; }
.fields input, .fields textarea, .fields select { border:1px solid #dadce0; border-radius:6px; padding:8px; font-size:13px; }
.thumb { max-width:64px; max-height:64px; margin-top:6px; object-fit:contain; }
.preview-box { margin-top:16px; background:#ececec; border-radius:8px; padding:16px; }
.preview-kop { background:#fff; padding:16px; }
.preview-row { display:flex; align-items:center; gap:12px; }
.preview-row img { width:64px; height:64px; object-fit:contain; }
.preview-text { flex:1; text-align:center; display:flex; flex-direction:column; gap:2px; font-family:'Times New Roman',serif; }
.preview-text strong { text-transform:uppercase; font-size:13px; }
.preview-text small { font-size:10px; }
.preview-line { border-top:3px solid #000; border-bottom:1px solid #000; height:4px; margin-top:10px; }
.actions { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
.btn, .btn-primary { border:1px solid #dadce0; background:#fff; border-radius:6px; padding:8px 14px; cursor:pointer; font-size:13px; }
.btn-primary { background:#1a73e8; border-color:#1a73e8; color:#fff; }
.empty, .empty-editor { text-align:center; color:#80868b; padding:24px; }
.empty-editor { display:flex; align-items:center; justify-content:center; }
@media (max-width:960px){ .grid{grid-template-columns:1fr;} .fields{grid-template-columns:1fr;} }
</style>
