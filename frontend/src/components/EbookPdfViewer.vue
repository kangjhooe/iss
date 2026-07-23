<template>
  <div
    class="ebook-viewer"
    role="dialog"
    aria-modal="true"
    :aria-label="title"
    @contextmenu.prevent
  >
    <div class="viewer-toolbar">
      <div class="viewer-title" :title="title">{{ title }}</div>
      <div class="viewer-controls">
        <button type="button" class="tool-btn" :disabled="page <= 1 || loading" @click="goPage(page - 1)">‹</button>
        <span class="page-info">
          <input
            v-model.number="pageInput"
            type="number"
            class="page-input"
            min="1"
            :max="pageCount || 1"
            :disabled="!pageCount"
            @change="jumpToPage"
          />
          / {{ pageCount || '—' }}
        </span>
        <button type="button" class="tool-btn" :disabled="page >= pageCount || loading" @click="goPage(page + 1)">›</button>
        <button type="button" class="tool-btn" :disabled="scale <= 0.6 || loading" @click="zoom(-0.15)" title="Perkecil">−</button>
        <button type="button" class="tool-btn" :disabled="scale >= 2.5 || loading" @click="zoom(0.15)" title="Perbesar">+</button>
        <button type="button" class="tool-btn tool-close" @click="$emit('close')">Tutup</button>
      </div>
    </div>

    <div ref="scrollEl" class="viewer-body" @scroll="onScroll">
      <div v-if="error" class="viewer-state">{{ error }}</div>
      <div v-else-if="loading && !pageCount" class="viewer-state">Memuat ebook...</div>
      <div class="canvas-wrap" :style="{ width: canvasWidth ? canvasWidth + 'px' : 'auto' }">
        <canvas ref="canvasEl" class="pdf-canvas" />
        <div v-if="watermark" class="watermark" aria-hidden="true">
          <span v-for="n in 12" :key="n" class="watermark-item">{{ watermark }}</span>
        </div>
        <div v-if="rendering" class="render-hint">Merender halaman...</div>
      </div>
    </div>

    <p class="viewer-hint">Konten dilindungi. Download langsung dinonaktifkan. Screenshot tetap mungkin.</p>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker

const props = defineProps({
  streamUrl: { type: String, required: true },
  title: { type: String, default: 'Ebook' },
  watermark: { type: String, default: '' },
})

defineEmits(['close'])

const canvasEl = ref(null)
const scrollEl = ref(null)
const loading = ref(true)
const rendering = ref(false)
const error = ref('')
const page = ref(1)
const pageInput = ref(1)
const pageCount = ref(0)
const scale = ref(1.15)
const canvasWidth = ref(0)

let pdfDoc = null
let renderTask = null
let destroyed = false

async function loadDocument() {
  loading.value = true
  error.value = ''
  pageCount.value = 0
  try {
    if (pdfDoc) {
      await pdfDoc.destroy()
      pdfDoc = null
    }
    const loadingTask = pdfjsLib.getDocument({
      url: props.streamUrl,
      withCredentials: false,
      disableRange: false,
      disableStream: false,
      // Jangan cache agresif di IndexedDB
      disableAutoFetch: false,
    })
    pdfDoc = await loadingTask.promise
    if (destroyed) return
    pageCount.value = pdfDoc.numPages
    page.value = 1
    pageInput.value = 1
    await renderPage()
  } catch (e) {
    if (destroyed) return
    const msg = e?.message || 'Gagal memuat ebook.'
    if (/403|401|expired|kedaluwarsa/i.test(msg) || e?.name === 'UnexpectedResponseException') {
      error.value = 'Sesi baca kedaluwarsa atau ditolak. Tutup lalu buka ulang ebook.'
    } else {
      error.value = 'Tidak dapat membuka ebook. Coba lagi.'
    }
  } finally {
    if (!destroyed) loading.value = false
  }
}

async function renderPage() {
  if (!pdfDoc || !canvasEl.value) return
  rendering.value = true
  try {
    if (renderTask) {
      try { renderTask.cancel() } catch (_) {}
      renderTask = null
    }
    const pdfPage = await pdfDoc.getPage(page.value)
    const viewport = pdfPage.getViewport({ scale: scale.value })
    const canvas = canvasEl.value
    const context = canvas.getContext('2d', { alpha: false })
    const outputScale = window.devicePixelRatio || 1
    canvas.width = Math.floor(viewport.width * outputScale)
    canvas.height = Math.floor(viewport.height * outputScale)
    canvas.style.width = Math.floor(viewport.width) + 'px'
    canvas.style.height = Math.floor(viewport.height) + 'px'
    canvasWidth.value = Math.floor(viewport.width)

    const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
    renderTask = pdfPage.render({
      canvasContext: context,
      viewport,
      transform,
    })
    await renderTask.promise
    renderTask = null
  } catch (e) {
    if (e?.name !== 'RenderingCancelledException') {
      error.value = 'Gagal menampilkan halaman.'
    }
  } finally {
    rendering.value = false
  }
}

function goPage(p) {
  if (!pageCount.value) return
  const next = Math.min(Math.max(1, p), pageCount.value)
  page.value = next
  pageInput.value = next
  renderPage()
  nextTick(() => {
    if (scrollEl.value) scrollEl.value.scrollTop = 0
  })
}

function jumpToPage() {
  goPage(Number(pageInput.value) || 1)
}

function zoom(delta) {
  scale.value = Math.round(Math.min(2.5, Math.max(0.6, scale.value + delta)) * 100) / 100
  renderPage()
}

function onScroll() {
  // reserved for future continuous scroll mode
}

function onKey(e) {
  if (e.key === 'Escape') return
  if (e.key === 'ArrowRight' || e.key === 'PageDown') {
    e.preventDefault()
    goPage(page.value + 1)
  } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
    e.preventDefault()
    goPage(page.value - 1)
  }
}

watch(() => props.streamUrl, () => {
  loadDocument()
})

onMounted(() => {
  window.addEventListener('keydown', onKey)
  loadDocument()
})

onBeforeUnmount(() => {
  destroyed = true
  window.removeEventListener('keydown', onKey)
  if (renderTask) {
    try { renderTask.cancel() } catch (_) {}
  }
  if (pdfDoc) {
    pdfDoc.destroy().catch(() => {})
    pdfDoc = null
  }
})
</script>

<style scoped>
.ebook-viewer {
  position: fixed;
  inset: 0;
  z-index: 2000;
  background: #0f172a;
  display: flex;
  flex-direction: column;
  user-select: none;
  -webkit-user-select: none;
}
.viewer-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 1rem;
  background: #1e293b;
  color: #fff;
  flex-shrink: 0;
}
.viewer-title {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}
.viewer-controls {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.tool-btn {
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.55rem;
  border: none;
  border-radius: 8px;
  background: #334155;
  color: #f8fafc;
  font-weight: 600;
  cursor: pointer;
}
.tool-btn:hover:not(:disabled) { background: #475569; }
.tool-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.tool-close { background: #059669; }
.tool-close:hover:not(:disabled) { background: #047857; }
.page-info {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.85rem;
  color: #cbd5e1;
  margin: 0 0.25rem;
}
.page-input {
  width: 3.2rem;
  padding: 0.25rem 0.35rem;
  border-radius: 6px;
  border: 1px solid #475569;
  background: #0f172a;
  color: #fff;
  text-align: center;
}
.viewer-body {
  flex: 1;
  overflow: auto;
  display: flex;
  justify-content: center;
  padding: 1rem;
  background: #1e293b;
}
.canvas-wrap {
  position: relative;
  max-width: 100%;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
  background: #fff;
}
.pdf-canvas {
  display: block;
  max-width: 100%;
  height: auto;
}
.watermark {
  pointer-events: none;
  position: absolute;
  inset: 0;
  overflow: hidden;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2.5rem 1rem;
  align-content: space-around;
  justify-items: center;
  padding: 2rem;
  opacity: 0.14;
}
.watermark-item {
  transform: rotate(-28deg);
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  text-align: center;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}
.render-hint {
  position: absolute;
  left: 50%;
  bottom: 0.75rem;
  transform: translateX(-50%);
  background: rgba(15, 23, 42, 0.7);
  color: #fff;
  font-size: 0.75rem;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
}
.viewer-state {
  color: #cbd5e1;
  align-self: center;
  text-align: center;
  padding: 2rem;
}
.viewer-hint {
  margin: 0;
  padding: 0.4rem 1rem;
  font-size: 0.75rem;
  color: #94a3b8;
  background: #0f172a;
  text-align: center;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .viewer-toolbar { flex-wrap: wrap; }
  .viewer-title { width: 100%; }
}

@media print {
  .ebook-viewer { display: none !important; }
}
</style>
