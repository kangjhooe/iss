<template>
  <div class="qr-scanner">
    <div v-if="error" class="error-message">
      <p>{{ error }}</p>
      <button type="button" @click="restart" class="btn-primary">Coba lagi</button>
    </div>
    <div v-show="!error" class="scanner-container">
      <div :id="scannerId" class="scanner-viewport"></div>
      <p class="scan-hint">Arahkan kamera ke QR code kartu absensi</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

const emit = defineEmits(['scan', 'error'])

const scannerId = `qr-reader-${Math.random().toString(36).slice(2, 10)}`
const error = ref('')
let scanner = null
let lastText = ''
let lastAt = 0

async function startScanner() {
  error.value = ''
  await stopScanner()

  try {
    scanner = new Html5Qrcode(scannerId, false)
    const cameras = await Html5Qrcode.getCameras().catch(() => [])
    const backCam = cameras.find((c) => /back|rear|environment/i.test(c.label))
    const cameraConfig = backCam?.id || { facingMode: 'environment' }

    await scanner.start(
      cameraConfig,
      { fps: 8, qrbox: { width: 220, height: 220 } },
      (decodedText) => {
        const text = String(decodedText || '').trim()
        if (!text) return
        const now = Date.now()
        if (text === lastText && now - lastAt < 2500) return
        lastText = text
        lastAt = now
        emit('scan', text)
      }
    )
  } catch (err) {
    error.value = 'Tidak dapat mengakses kamera. Izinkan kamera di browser, atau gunakan input manual.'
    emit('error', err)
    await stopScanner()
  }
}

async function stopScanner() {
  if (!scanner) return
  try {
    await scanner.stop()
  } catch {
    // already stopped
  }
  try {
    scanner.clear()
  } catch {
    // ignore
  }
  scanner = null
}

function restart() {
  startScanner()
}

onMounted(() => {
  startScanner()
})

onUnmounted(() => {
  stopScanner()
})

defineExpose({ restart })
</script>

<style scoped>
.qr-scanner {
  width: 100%;
  max-width: 420px;
  margin: 0 auto;
}

.error-message {
  padding: 1.5rem;
  text-align: center;
  background: #fee2e2;
  border-radius: 12px;
  color: #991b1b;
}

.scanner-container {
  position: relative;
  width: 100%;
  background: #0f172a;
  border-radius: 12px;
  overflow: hidden;
}

.scanner-viewport {
  width: 100%;
  min-height: 280px;
}

.scanner-viewport :deep(video) {
  width: 100%;
  border-radius: 12px;
  object-fit: cover;
}

.scan-hint {
  margin: 0;
  padding: 0.65rem 0.75rem;
  color: #e2e8f0;
  font-size: 0.85rem;
  text-align: center;
}

.btn-primary {
  margin-top: 0.75rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  background: #059669;
  color: #fff;
  cursor: pointer;
}
</style>
