<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import Toolbar from './components/Toolbar.vue'
import Paper from './components/Paper.vue'
import EditorSurat from './components/EditorSurat.vue'
import PreviewDialog from './components/PreviewDialog.vue'
import GenerateDialog from './components/GenerateDialog.vue'
import LetterOptions from './components/LetterOptions.vue'
import KopPreview from './components/KopPreview.vue'
import TtdPreview from './components/TtdPreview.vue'
import PublishDialog from './components/PublishDialog.vue'
import suratService from './services/suratService'
import { kopService } from './services/kopService'
import { asetTandaTanganService } from './services/asetTandaTanganService'
import { SuratLayoutClient } from './utils/suratLayoutClient'
import { DEFAULT_LETTER_TYPE_CODE, suratDensityForLetterType } from './utils/letterTypes'

const toast = useToast()
const router = useRouter()
const route = useRoute()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const list = ref([])
const listLoading = ref(true)
const search = ref('')
const currentId = ref(null)
const judul = ref('Surat baru')
const isiHtml = ref('<p></p>')
const nomor = ref('')
const status = ref('draft')
const tanggal = ref(new Date().toISOString().slice(0, 10))
const letterTypeCode = ref(DEFAULT_LETTER_TYPE_CODE)
const correspondenceId = ref(null)
const studentName = ref('')
const saving = ref(false)
const publishing = ref(false)
const dirty = ref(false)
const previewOpen = ref(false)
const generateOpen = ref(false)
const publishOpen = ref(false)
const publishDialogRef = ref(null)
const zoom = ref(1)
const isMobile = ref(typeof window !== 'undefined' && window.innerWidth <= 768)
const sidebarOpen = ref(!isMobile.value)
const optionsOpen = ref(false)
const createMenuOpen = ref(false)
const skipDirty = ref(false)
const kopHtml = ref('')
const ttdHtml = ref('')
const kopsCache = ref([])
const institutionProfile = ref(null)
const ttdCache = ref([])
const stempelCache = ref([])

const options = ref({
  tampilkan_kop: true,
  kop_id: null,
  tampilkan_tanda_tangan: false,
  tanda_tangan_id: null,
  tampilkan_stempel: false,
  stempel_id: null,
  posisi_ttd: 'kanan'
})

const hasDocument = computed(() => !!currentId.value)
const fullPrintHtml = computed(() => `${kopHtml.value}${isiHtml.value}${ttdHtml.value}`)
const suratDensity = computed(() => suratDensityForLetterType(letterTypeCode.value))

function defaultOptions() {
  return {
    tampilkan_kop: true,
    kop_id: null,
    tampilkan_tanda_tangan: false,
    tanda_tangan_id: null,
    tampilkan_stempel: false,
    stempel_id: null,
    posisi_ttd: 'kanan'
  }
}

async function loadAssetCaches() {
  try {
    const [kopRes, ttdRes, stempelRes, instRes] = await Promise.all([
      kopService.list({ status: 'aktif' }),
      asetTandaTanganService.list({ jenis: 'tanda_tangan', status: 'aktif' }),
      asetTandaTanganService.list({ jenis: 'stempel', status: 'aktif' }),
      suratService.letterheadContext()
    ])
    kopsCache.value = kopRes.data?.data || []
    ttdCache.value = ttdRes.data?.data || []
    stempelCache.value = stempelRes.data?.data || []
    institutionProfile.value = instRes.data?.data || null
    refreshLayoutPreview()
  } catch {
    /* ignore */
  }
}

function refreshLayoutPreview() {
  const layout = new SuratLayoutClient()
  const institution = institutionProfile.value
  let kop = null
  if (options.value.tampilkan_kop) {
    kop = kopsCache.value.find((k) => k.id === options.value.kop_id)
      || kopsCache.value.find((k) => k.is_default)
      || null
  }
  const ttd = options.value.tampilkan_tanda_tangan
    ? ttdCache.value.find((t) => t.id === options.value.tanda_tangan_id) || null
    : null
  const stempel = options.value.tampilkan_stempel
    ? stempelCache.value.find((s) => s.id === options.value.stempel_id) || null
    : null

  kopHtml.value = layout.renderKop(kop, institution)
  ttdHtml.value = layout.renderSignature(ttd, stempel, options.value.posisi_ttd || 'kanan')
}

async function loadList() {
  listLoading.value = true
  try {
    const res = await suratService.list({
      search: search.value || undefined,
      per_page: 50
    })
    list.value = res.data?.data || []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak dapat memuat daftar surat')
  } finally {
    listLoading.value = false
  }
}

async function openSurat(id) {
  try {
    const res = await suratService.get(id)
    const data = res.data.data
    skipDirty.value = true
    currentId.value = data.id
    judul.value = data.judul || 'Tanpa judul'
    isiHtml.value = data.isi_html || '<p></p>'
    nomor.value = data.nomor || ''
    status.value = data.status || 'draft'
    tanggal.value = data.tanggal
      ? String(data.tanggal).slice(0, 10)
      : new Date().toISOString().slice(0, 10)
    letterTypeCode.value = data.letter_type_code || DEFAULT_LETTER_TYPE_CODE
    correspondenceId.value = data.correspondence_id || data.correspondence?.id || null
    studentName.value = data.student?.name || ''
    options.value = {
      tampilkan_kop: data.tampilkan_kop !== false,
      kop_id: data.kop_id || null,
      tampilkan_tanda_tangan: !!data.tampilkan_tanda_tangan,
      tanda_tangan_id: data.tanda_tangan_id || null,
      tampilkan_stempel: !!data.tampilkan_stempel,
      stempel_id: data.stempel_id || null,
      posisi_ttd: data.posisi_ttd || 'kanan'
    }
    dirty.value = false
    refreshLayoutPreview()
    if (isMobile.value) sidebarOpen.value = false
    await nextTick()
    skipDirty.value = false
    router.replace({ query: { ...route.query, id: data.id } })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Surat tidak ditemukan')
  }
}

function newBlank() {
  skipDirty.value = true
  currentId.value = null
  judul.value = 'Surat baru'
  isiHtml.value = '<p></p>'
  nomor.value = ''
  status.value = 'draft'
  tanggal.value = new Date().toISOString().slice(0, 10)
  letterTypeCode.value = DEFAULT_LETTER_TYPE_CODE
  correspondenceId.value = null
  studentName.value = ''
  options.value = defaultOptions()
  dirty.value = false
  refreshLayoutPreview()
  if (isMobile.value) sidebarOpen.value = false
  nextTick(() => { skipDirty.value = false })
  router.replace({ query: {} })
}

function fitZoomToViewport() {
  if (typeof window === 'undefined') return
  const mobile = window.innerWidth <= 768
  isMobile.value = mobile
  if (!mobile) return
  const available = Math.max(240, window.innerWidth - 24)
  const paperPx = 210 * 3.779527559
  zoom.value = Math.min(0.95, Math.max(0.42, +(available / paperPx).toFixed(2)))
}

function onViewportChange() {
  const wasMobile = isMobile.value
  fitZoomToViewport()
  if (!wasMobile && isMobile.value) {
    sidebarOpen.value = false
    optionsOpen.value = false
  }
  if (wasMobile && !isMobile.value) {
    sidebarOpen.value = true
  }
}

async function save() {
  if (!judul.value?.trim()) {
    toast.warning('Perhatian', 'Judul surat wajib diisi')
    return
  }
  saving.value = true
  try {
    const payload = {
      judul: judul.value.trim(),
      isi_html: isiHtml.value,
      tanggal: tanggal.value || null,
      letter_type_code: letterTypeCode.value || DEFAULT_LETTER_TYPE_CODE,
      ...options.value
    }
    let res
    if (currentId.value) {
      res = await suratService.update(currentId.value, payload)
    } else {
      res = await suratService.create(payload)
    }
    const data = res.data.data
    currentId.value = data.id
    nomor.value = data.nomor || ''
    status.value = data.status || 'draft'
    correspondenceId.value = data.correspondence_id || null
    dirty.value = false
    toast.success('Berhasil', status.value === 'terbit' ? 'Surat disimpan' : 'Draft disimpan')
    await loadList()
    router.replace({ query: { ...route.query, id: data.id } })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan surat')
  } finally {
    saving.value = false
  }
}

async function openPublish() {
  if (!currentId.value) {
    toast.warning('Perhatian', 'Simpan draft terlebih dahulu')
    return
  }
  if (dirty.value) {
    toast.warning('Perhatian', 'Simpan perubahan sebelum menerbitkan')
    return
  }
  if (status.value === 'terbit') {
    toast.info('Info', 'Surat sudah diterbitkan')
    return
  }
  publishOpen.value = true
}

async function confirmPublish(payload) {
  publishing.value = true
  try {
    const res = await suratService.publish(currentId.value, payload)
    const data = res.data.data
    nomor.value = data.nomor || ''
    status.value = data.status || 'terbit'
    correspondenceId.value = data.correspondence_id || data.correspondence?.id || null
    isiHtml.value = data.isi_html || isiHtml.value
    letterTypeCode.value = data.letter_type_code || letterTypeCode.value
    if (data.tanggal) tanggal.value = String(data.tanggal).slice(0, 10)
    dirty.value = false
    publishOpen.value = false
    toast.success('Diterbitkan', `Nomor resmi: ${nomor.value}`)
    await loadList()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menerbitkan surat')
    publishDialogRef.value?.setSubmitting?.(false)
  } finally {
    publishing.value = false
  }
}

function markDirty() {
  if (!skipDirty.value) dirty.value = true
}

function onOptionsChange() {
  if (!skipDirty.value) dirty.value = true
  refreshLayoutPreview()
}

watch(judul, () => { if (!skipDirty.value) dirty.value = true })
watch(isiHtml, () => { if (!skipDirty.value) dirty.value = true })
watch(options, onOptionsChange, { deep: true })

function openPdfPrintWindow(blob) {
  const url = URL.createObjectURL(blob)
  const win = window.open('', '_blank')
  if (!win) {
    URL.revokeObjectURL(url)
    toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk cetak.')
    return
  }
  win.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cetak Surat</title>
<style>*{box-sizing:border-box}html,body{margin:0;height:100%}embed{display:block;width:100%;height:100vh;border:0}</style>
</head><body><embed src="${url}" type="application/pdf" /></body></html>`)
  win.document.close()
  setTimeout(() => {
    try {
      win.focus()
      win.print()
    } catch {
      /* biarkan viewer PDF menangani cetak */
    }
  }, 700)
}

async function parsePdfBlobResponse(res, fallbackMessage) {
  const contentType = res.headers?.['content-type'] || ''
  if (res.status !== 200 || contentType.includes('application/json')) {
    const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
    const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
    throw new Error(json.message || fallbackMessage)
  }
  return res.data instanceof Blob
    ? res.data
    : new Blob([res.data], { type: 'application/pdf' })
}

async function ensureSavedForOutput() {
  if (!currentId.value) {
    toast.warning('Perhatian', 'Simpan surat terlebih dahulu')
    return false
  }
  if (dirty.value) {
    await save()
  }
  return true
}

function openPreview() {
  refreshLayoutPreview()
  previewOpen.value = true
}

async function handlePrint() {
  previewOpen.value = false
  if (!(await ensureSavedForOutput())) return
  try {
    const res = await suratService.print(currentId.value)
    const blob = await parsePdfBlobResponse(res, 'Gagal cetak surat')
    openPdfPrintWindow(blob)
  } catch (e) {
    toast.error('Gagal', e.message || 'Gagal cetak surat')
  }
}

async function exportPdf() {
  if (!(await ensureSavedForOutput())) return
  try {
    const res = await suratService.exportPdf(currentId.value)
    const blob = await parsePdfBlobResponse(res, 'Gagal export PDF')
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Surat_${(nomor.value || currentId.value).toString().replace(/[^\w\-]+/g, '_')}.pdf`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
    toast.success('Berhasil', 'PDF berhasil diunduh')
  } catch (e) {
    toast.error('Gagal', e.message || e.response?.data?.message || 'Gagal export PDF')
  }
}

async function onGenerated(surat) {
  toast.success('Draft siap', 'Edit isi surat, lalu klik Terbitkan untuk mendapatkan nomor resmi')
  await loadList()
  await openSurat(surat.id)
}

async function deleteSurat(item) {
  const ok = await showConfirm({
    title: 'Hapus surat?',
    message: `Hapus "${item.judul}"? Tindakan ini tidak dapat dibatalkan.`
  })
  if (!ok) return
  try {
    await suratService.remove(item.id)
    toast.success('Berhasil', 'Surat dihapus')
    if (currentId.value === item.id) newBlank()
    await loadList()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadList, 300)
})

function openGenerate() {
  createMenuOpen.value = false
  generateOpen.value = true
}

function startBlank() {
  createMenuOpen.value = false
  newBlank()
}

function closeCreateMenu() {
  createMenuOpen.value = false
}

onMounted(async () => {
  fitZoomToViewport()
  window.addEventListener('resize', onViewportChange)
  await Promise.all([loadList(), loadAssetCaches()])
  const qid = route.query.id
  if (qid) await openSurat(Number(qid))
  else refreshLayoutPreview()
  document.addEventListener('click', closeCreateMenu)
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  document.removeEventListener('click', closeCreateMenu)
  window.removeEventListener('resize', onViewportChange)
})
</script>

<template>
    <div class="surat-app">
      <div
        v-if="sidebarOpen && isMobile"
        class="sidebar-backdrop no-print"
        @click="sidebarOpen = false"
      />

      <!-- Sidebar -->
      <aside class="surat-sidebar no-print" :class="{ collapsed: !sidebarOpen, 'is-drawer': isMobile }">
        <div class="sidebar-head">
          <div class="sidebar-head-row">
            <h2>Persuratan</h2>
            <button
              v-if="isMobile"
              type="button"
              class="sidebar-close"
              aria-label="Tutup daftar"
              @click="sidebarOpen = false"
            >×</button>
          </div>

          <div class="create-wrap" @click.stop>
            <button type="button" class="btn-create" @click="createMenuOpen = !createMenuOpen">
              + Buat Surat
              <span class="caret">▾</span>
            </button>
            <div v-if="createMenuOpen" class="create-menu">
              <button type="button" @click="startBlank">Surat kosong</button>
              <button type="button" @click="openGenerate">Dari template</button>
            </div>
          </div>

          <input v-model="search" class="search-input" type="search" placeholder="Cari surat..." />
        </div>

        <div class="sidebar-list">
          <div class="list-label">Riwayat</div>
          <LoadingSkeleton v-if="listLoading" :rows="6" />
          <template v-else>
            <div
              v-for="item in list"
              :key="item.id"
              class="surat-item"
              :class="{ active: currentId === item.id }"
            >
              <button type="button" class="surat-item-main" @click="openSurat(item.id)">
                <span class="item-title">{{ item.judul }}</span>
                <span class="item-meta">
                  <span class="pill" :class="item.status === 'terbit' ? 'pill-ok' : 'pill-draft'">
                    {{ item.status === 'terbit' ? 'Terbit' : 'Draft' }}
                  </span>
                  {{ item.nomor || 'Belum ada nomor' }}
                </span>
              </button>
              <button
                v-if="item.status !== 'terbit'"
                type="button"
                class="item-delete"
                title="Hapus draft"
                @click.stop="deleteSurat(item)"
              >×</button>
            </div>
            <p v-if="!list.length" class="empty">Belum ada surat</p>
          </template>
        </div>
      </aside>

      <!-- Main -->
      <section class="surat-main">
        <Toolbar
          v-model:title="judul"
          :saving="saving"
          :publishing="publishing"
          :dirty="dirty"
          :has-document="!!currentId"
          :options-open="optionsOpen"
          :nomor="nomor"
          :status="status"
          :correspondence-id="correspondenceId"
          class="no-print"
          @save="save"
          @preview="openPreview"
          @print="handlePrint"
          @export-pdf="exportPdf"
          @publish="openPublish"
          @toggle-options="optionsOpen = !optionsOpen"
          @toggle-sidebar="sidebarOpen = !sidebarOpen"
        />

        <div class="workspace">
          <div class="editor-canvas">
            <div class="zoom-bar no-print">
              <div class="zoom-controls">
                <button type="button" class="zoom-btn" @click="zoom = Math.max(0.4, +(zoom - 0.1).toFixed(1))">−</button>
                <span>{{ Math.round(zoom * 100) }}%</span>
                <button type="button" class="zoom-btn" @click="zoom = Math.min(1.4, +(zoom + 0.1).toFixed(1))">+</button>
              </div>
            </div>

            <Paper :zoom="zoom" :density="suratDensity">
              <div class="print-only" v-html="fullPrintHtml" />
              <div class="editor-wrap no-print">
                <KopPreview :html="kopHtml" />
                <EditorSurat
                  v-model="isiHtml"
                  :disabled="publishOpen || previewOpen || generateOpen"
                  @update:model-value="markDirty"
                />
                <TtdPreview :html="ttdHtml" />
              </div>
            </Paper>
          </div>

          <LetterOptions
            v-model="options"
            :letter-type-code="letterTypeCode"
            :open="optionsOpen"
            :disabled-letter-type="status === 'terbit'"
            @close="optionsOpen = false"
            @update:letter-type-code="(code) => { letterTypeCode = code; markDirty() }"
          />
        </div>
      </section>
    </div>

    <PreviewDialog
      :open="previewOpen"
      :title="judul"
      :html="fullPrintHtml"
      :density="suratDensity"
      @close="previewOpen = false"
      @print="handlePrint"
    />

    <GenerateDialog
      :open="generateOpen"
      @close="generateOpen = false"
      @generated="onGenerated"
    />

    <PublishDialog
      ref="publishDialogRef"
      :open="publishOpen"
      :judul="judul"
      :letter-type-code="letterTypeCode"
      :tanggal="tanggal"
      :to-default="studentName || 'Yang berkepentingan'"
      @close="publishOpen = false"
      @confirm="confirmPublish"
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
.surat-app {
  display: flex;
  height: calc(100vh - 64px);
  margin: -24px;
  background: #ececec;
  overflow: hidden;
  position: relative;
}

.sidebar-backdrop {
  display: none;
}

.surat-sidebar {
  width: 268px;
  background: #fff;
  border-right: 1px solid #e0e0e0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.2s ease, margin 0.2s ease, transform 0.22s ease;
}

.surat-sidebar.collapsed {
  width: 0;
  margin-left: -1px;
  overflow: hidden;
  border: none;
}

.sidebar-head {
  padding: 14px;
  border-bottom: 1px solid #eee;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.sidebar-head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.sidebar-head h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #202124;
}

.sidebar-close {
  display: none;
  width: 36px;
  height: 36px;
  border: none;
  background: #f1f3f4;
  border-radius: 8px;
  font-size: 22px;
  line-height: 1;
  color: #5f6368;
  cursor: pointer;
  flex-shrink: 0;
}

.create-wrap {
  position: relative;
}

.btn-create {
  width: 100%;
  border: none;
  background: #1a73e8;
  color: #fff;
  border-radius: 8px;
  padding: 9px 12px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 40px;
}

.btn-create:hover {
  background: #1765cc;
}

.caret {
  font-size: 10px;
  opacity: 0.9;
}

.create-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #dadce0;
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  z-index: 40;
  overflow: hidden;
}

.create-menu button {
  display: block;
  width: 100%;
  text-align: left;
  border: none;
  background: #fff;
  padding: 12px;
  font-size: 13px;
  cursor: pointer;
  color: #202124;
  min-height: 44px;
}

.create-menu button:hover {
  background: #f1f3f4;
}

.search-input {
  border: 1px solid #dadce0;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 13px;
  width: 100%;
  box-sizing: border-box;
}

.sidebar-list {
  overflow-y: auto;
  flex: 1;
  padding: 8px;
  -webkit-overflow-scrolling: touch;
}

.list-label {
  font-size: 11px;
  font-weight: 600;
  color: #80868b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 4px 10px 8px;
}

.surat-item {
  display: flex;
  align-items: stretch;
  border-radius: 8px;
  margin-bottom: 2px;
}

.surat-item:hover {
  background: #f1f3f4;
}

.surat-item.active {
  background: #e8f0fe;
}

.surat-item-main {
  flex: 1;
  min-width: 0;
  text-align: left;
  border: none;
  background: transparent;
  padding: 9px 8px 9px 10px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-delete {
  width: 28px;
  border: none;
  background: transparent;
  color: #80868b;
  font-size: 16px;
  cursor: pointer;
  border-radius: 6px;
  opacity: 0;
}

.surat-item:hover .item-delete,
.surat-item.active .item-delete {
  opacity: 1;
}

.item-delete:hover {
  background: #fce8e6;
  color: #d93025;
}

.item-title {
  font-size: 13px;
  font-weight: 600;
  color: #202124;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-meta {
  font-size: 11px;
  color: #5f6368;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}

.pill {
  font-size: 10px;
  font-weight: 600;
  border-radius: 999px;
  padding: 1px 6px;
  flex-shrink: 0;
}

.pill-draft {
  background: #fef7e0;
  color: #a05a00;
}

.pill-ok {
  background: #e6f4ea;
  color: #137333;
}

.empty {
  text-align: center;
  color: #80868b;
  font-size: 13px;
  padding: 24px 8px;
}

.surat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  position: relative;
}

.workspace {
  flex: 1;
  display: flex;
  min-height: 0;
  position: relative;
}

.editor-canvas {
  flex: 1;
  overflow: auto;
  background: #ececec;
  min-width: 0;
  -webkit-overflow-scrolling: touch;
}

.zoom-bar {
  display: flex;
  justify-content: flex-end;
  padding: 8px 16px 0;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #5f6368;
  background: #fff;
  border: 1px solid #dadce0;
  border-radius: 8px;
  padding: 2px 6px;
}

.zoom-btn {
  border: none;
  background: transparent;
  border-radius: 6px;
  padding: 4px 8px;
  cursor: pointer;
  font-size: 14px;
  color: #3c4043;
  min-width: 32px;
  min-height: 32px;
}

.zoom-btn:hover {
  background: #f1f3f4;
}

.print-only {
  display: none;
}

.editor-wrap :deep(.ck.ck-toolbar) {
  margin: calc(-1 * var(--surat-margin-top)) calc(-1 * var(--surat-margin-right)) 12px calc(-1 * var(--surat-margin-left));
  width: calc(100% + var(--surat-margin-right) + var(--surat-margin-left));
}

@media (max-width: 768px) {
  .surat-app {
    margin: -14px -12px;
    height: calc(100dvh - 56px - 72px - env(safe-area-inset-bottom, 0px));
    min-height: 420px;
  }

  .sidebar-backdrop {
    display: block;
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    z-index: 25;
  }

  .surat-sidebar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 30;
    width: min(300px, 86vw);
    height: 100%;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.16);
    transform: translateX(0);
  }

  .surat-sidebar.collapsed {
    width: min(300px, 86vw);
    margin-left: 0;
    overflow: hidden;
    border-right: 1px solid #e0e0e0;
    transform: translateX(-105%);
    pointer-events: none;
    box-shadow: none;
  }

  .sidebar-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .item-delete {
    opacity: 1;
    width: 36px;
  }

  .surat-item-main {
    padding: 12px 8px 12px 10px;
  }

  .zoom-bar {
    padding: 8px 10px 0;
    position: sticky;
    top: 0;
    z-index: 5;
  }

  .editor-wrap :deep(.ck.ck-toolbar) {
    margin: calc(-1 * var(--surat-margin-top)) calc(-1 * var(--surat-margin-right)) 10px calc(-1 * var(--surat-margin-left));
    width: calc(100% + var(--surat-margin-right) + var(--surat-margin-left));
  }

  .editor-wrap :deep(.ck.ck-toolbar .ck-toolbar__items) {
    flex-wrap: wrap;
  }
}

@media (max-width: 480px) {
  .surat-app {
    margin: -12px;
  }

  .surat-sidebar {
    width: min(320px, 92vw);
  }

  .surat-sidebar.collapsed {
    width: min(320px, 92vw);
  }
}

@media print {
  .no-print {
    display: none !important;
  }

  .print-only {
    display: block !important;
  }

  :global(.bottom-nav),
  :global(.layout .header),
  :global(.layout .sidebar) {
    display: none !important;
  }

  :global(.layout .content) {
    padding: 0 !important;
    padding-bottom: 0 !important;
  }

  .surat-app {
    margin: 0 !important;
    height: auto !important;
    background: #fff !important;
    display: block !important;
    overflow: visible !important;
  }

  .surat-sidebar {
    display: none !important;
  }

  .surat-main,
  .workspace {
    display: block !important;
  }

  .editor-canvas {
    overflow: visible !important;
    background: #fff !important;
  }

  .editor-wrap {
    display: none !important;
  }

  .print-only {
    padding: 0;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.6;
    color: #000;
  }

  .print-only :deep(.standard-kop) {
    border-bottom: 3px double #111;
    margin-bottom: 12px;
  }

  .print-only :deep(.standard-kop-inner td),
  .print-only :deep(.kop-table td),
  .print-only :deep(table[style*="border:none"] td) {
    border: none !important;
  }

  .print-only :deep(table:not(.standard-kop-inner):not(.kop-table) td),
  .print-only :deep(table:not(.standard-kop-inner):not(.kop-table) th) {
    border: 1px solid #000;
    padding: 4px 8px;
  }

  .print-only :deep(table) {
    border-collapse: collapse;
    width: 100%;
  }
}
</style>

<style>
@import '@/styles/surat-page.css';
</style>
