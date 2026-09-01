<template>
  <div v-if="show" class="crop-overlay" @click="handleOverlayClick">
    <div class="crop-modal" @click.stop>
      <div class="crop-header">
        <h3>Potong Foto 3×4</h3>
        <button type="button" class="btn-close" :disabled="processing" @click="handleCancel">×</button>
      </div>

      <div class="crop-body">
        <p class="crop-hint">Geser foto untuk menyesuaikan posisi. Gunakan zoom atau Autocrop untuk hasil cepat.</p>

        <div
          ref="viewportRef"
          class="crop-viewport"
          @pointerdown="onPointerDown"
          @pointermove="onPointerMove"
          @pointerup="onPointerUp"
          @pointercancel="onPointerUp"
          @pointerleave="onPointerUp"
        >
          <img
            v-if="imageUrl"
            :src="imageUrl"
            alt="Pratinjau crop"
            class="crop-image"
            :style="imageStyle"
            draggable="false"
          />
          <div class="crop-frame" aria-hidden="true">
            <span class="crop-frame-label">3×4</span>
          </div>
        </div>

        <div class="crop-controls">
          <label class="zoom-label" for="crop-zoom">Zoom</label>
          <input
            id="crop-zoom"
            v-model.number="zoomPercent"
            type="range"
            min="100"
            max="300"
            step="1"
            :disabled="!imageLoaded || processing"
            @input="onZoomInput"
          />
          <span class="zoom-value">{{ zoomPercent }}%</span>
        </div>

        <div class="crop-actions">
          <button type="button" class="btn-outline" :disabled="!imageLoaded || processing" @click="applyAutoCrop">
            Autocrop
          </button>
          <button type="button" class="btn-secondary" :disabled="processing" @click="handleCancel">Batal</button>
          <button type="button" class="btn-primary" :disabled="!imageLoaded || processing" @click="handleConfirm">
            {{ processing ? 'Memproses...' : 'Gunakan Foto' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import {
  PROFILE_PHOTO_CROP_ASPECT,
  clampCropRect,
  computeAutoCropRect,
  cropImageToJpegFile,
  loadImageFromFile,
} from '@/utils/profilePhotoCrop'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  file: {
    type: File,
    default: null,
  },
})

const emit = defineEmits(['confirm', 'cancel', 'update:show'])

const toast = useToast()
const viewportRef = ref(null)
const imageUrl = ref('')
const imageElement = ref(null)
const imageLoaded = ref(false)
const processing = ref(false)

const naturalWidth = ref(0)
const naturalHeight = ref(0)
const cropRect = ref({ x: 0, y: 0, width: 0, height: 0 })
const baseCropRect = ref({ x: 0, y: 0, width: 0, height: 0 })
const zoomPercent = ref(100)

const dragging = ref(false)
const dragStart = ref({ x: 0, y: 0 })
const rectStart = ref({ x: 0, y: 0 })

const viewportSize = ref({ width: 240, height: 320 })

const imageStyle = computed(() => {
  const rect = cropRect.value
  if (!rect.width || !naturalWidth.value) {
    return { display: 'none' }
  }

  const scaleX = viewportSize.value.width / rect.width
  const scaleY = viewportSize.value.height / rect.height
  const displayWidth = naturalWidth.value * scaleX
  const displayHeight = naturalHeight.value * scaleY

  return {
    width: `${displayWidth}px`,
    height: `${displayHeight}px`,
    transform: `translate(${-rect.x * scaleX}px, ${-rect.y * scaleY}px)`,
  }
})

function updateViewportSize() {
  const el = viewportRef.value
  if (!el) return
  viewportSize.value = {
    width: el.clientWidth,
    height: el.clientHeight,
  }
}

function syncZoomFromRect() {
  if (!baseCropRect.value.width) {
    zoomPercent.value = 100
    return
  }
  const ratio = baseCropRect.value.width / cropRect.value.width
  zoomPercent.value = Math.round(Math.min(300, Math.max(100, ratio * 100)))
}

function applyAutoCrop() {
  if (!naturalWidth.value || !naturalHeight.value) return
  cropRect.value = computeAutoCropRect(naturalWidth.value, naturalHeight.value, PROFILE_PHOTO_CROP_ASPECT)
  baseCropRect.value = { ...cropRect.value }
  syncZoomFromRect()
}

function onZoomInput() {
  if (!baseCropRect.value.width) return

  const factor = zoomPercent.value / 100
  const current = cropRect.value
  const centerX = current.x + current.width / 2
  const centerY = current.y + current.height / 2

  let nextWidth = baseCropRect.value.width / factor
  let nextHeight = nextWidth / PROFILE_PHOTO_CROP_ASPECT

  const maxWidth = naturalWidth.value
  const maxHeight = naturalHeight.value
  if (nextWidth > maxWidth) {
    nextWidth = maxWidth
    nextHeight = nextWidth / PROFILE_PHOTO_CROP_ASPECT
  }
  if (nextHeight > maxHeight) {
    nextHeight = maxHeight
    nextWidth = nextHeight * PROFILE_PHOTO_CROP_ASPECT
  }

  cropRect.value = clampCropRect({
    x: centerX - nextWidth / 2,
    y: centerY - nextHeight / 2,
    width: nextWidth,
    height: nextHeight,
  }, naturalWidth.value, naturalHeight.value)
}

function onPointerDown(event) {
  if (!imageLoaded.value || processing.value) return
  dragging.value = true
  dragStart.value = { x: event.clientX, y: event.clientY }
  rectStart.value = { x: cropRect.value.x, y: cropRect.value.y }
  event.currentTarget?.setPointerCapture?.(event.pointerId)
}

function onPointerMove(event) {
  if (!dragging.value) return

  const rect = viewportRef.value?.getBoundingClientRect()
  if (!rect?.width || !cropRect.value.width) return

  const deltaX = event.clientX - dragStart.value.x
  const deltaY = event.clientY - dragStart.value.y
  const scaleX = cropRect.value.width / rect.width
  const scaleY = cropRect.value.height / rect.height

  cropRect.value = clampCropRect({
    x: rectStart.value.x - deltaX * scaleX,
    y: rectStart.value.y - deltaY * scaleY,
    width: cropRect.value.width,
    height: cropRect.value.height,
  }, naturalWidth.value, naturalHeight.value)
}

function onPointerUp(event) {
  if (!dragging.value) return
  dragging.value = false
  event.currentTarget?.releasePointerCapture?.(event.pointerId)
  syncZoomFromRect()
}

async function loadSource(file) {
  imageLoaded.value = false
  processing.value = false
  zoomPercent.value = 100

  if (imageUrl.value) {
    URL.revokeObjectURL(imageUrl.value)
    imageUrl.value = ''
  }

  if (!file) {
    imageElement.value = null
    naturalWidth.value = 0
    naturalHeight.value = 0
    return
  }

  const image = await loadImageFromFile(file)
  imageElement.value = image
  naturalWidth.value = image.naturalWidth
  naturalHeight.value = image.naturalHeight
  imageUrl.value = URL.createObjectURL(file)
  applyAutoCrop()
  imageLoaded.value = true
  requestAnimationFrame(updateViewportSize)
}

function handleCancel() {
  if (processing.value) return
  emit('cancel')
  emit('update:show', false)
}

function handleOverlayClick() {
  handleCancel()
}

async function handleConfirm() {
  if (!imageElement.value || !imageLoaded.value || processing.value) return
  processing.value = true
  try {
    const croppedFile = await cropImageToJpegFile(imageElement.value, cropRect.value)
    emit('confirm', croppedFile)
    emit('update:show', false)
  } catch (err) {
    toast.error('Gagal', err.message || 'Gagal memproses foto.')
  } finally {
    processing.value = false
  }
}

watch(() => [props.show, props.file], ([visible, file]) => {
  document.body.style.overflow = visible ? 'hidden' : ''
  if (visible && file) {
    loadSource(file).catch((err) => {
      toast.error('Gagal', err.message || 'Gagal memuat gambar.')
      handleCancel()
    })
    requestAnimationFrame(updateViewportSize)
    return
  }
  if (!visible) {
    if (imageUrl.value) URL.revokeObjectURL(imageUrl.value)
    imageUrl.value = ''
    imageElement.value = null
    imageLoaded.value = false
  }
})
</script>

<style scoped>
.crop-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 13000;
  padding: 20px;
}

.crop-modal {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.crop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.crop-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #94a3b8;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.btn-close:hover:not(:disabled) {
  background: #f1f5f9;
  color: #64748b;
}

.crop-body {
  padding: 20px;
}

.crop-hint {
  margin: 0 0 14px;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.crop-viewport {
  position: relative;
  width: 100%;
  aspect-ratio: 3 / 4;
  max-height: 360px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  background: #0f172a;
  touch-action: none;
  cursor: grab;
}

.crop-viewport:active {
  cursor: grabbing;
}

.crop-image {
  position: absolute;
  top: 0;
  left: 0;
  max-width: none;
  user-select: none;
  pointer-events: none;
}

.crop-frame {
  position: absolute;
  inset: 0;
  border: 2px solid rgba(255, 255, 255, 0.95);
  box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.45);
  pointer-events: none;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.crop-frame-label {
  margin-bottom: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.72);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.crop-controls {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 10px;
  align-items: center;
  margin-top: 16px;
}

.zoom-label,
.zoom-value {
  font-size: 13px;
  color: #475569;
}

.zoom-value {
  min-width: 42px;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.crop-controls input[type='range'] {
  width: 100%;
}

.crop-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 18px;
}

.btn-outline,
.btn-secondary,
.btn-primary {
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
}

.btn-outline {
  margin-right: auto;
  background: #fff;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-secondary {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
}

.btn-outline:disabled,
.btn-secondary:disabled,
.btn-primary:disabled,
.btn-close:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>
