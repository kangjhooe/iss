<template>
  <div class="qr-scanner">
    <div v-if="!hasPermission" class="permission-error">
      <p>Akses kamera diperlukan untuk scan QR code.</p>
      <button @click="requestPermission" class="btn-primary">Izinkan Akses Kamera</button>
    </div>
    <div v-else-if="error" class="error-message">
      <p>{{ error }}</p>
      <button @click="initScanner" class="btn-primary">Coba Lagi</button>
    </div>
    <div v-else class="scanner-container">
      <video ref="videoElement" autoplay playsinline class="scanner-video"></video>
      <div class="scanner-overlay">
        <div class="scan-frame"></div>
        <p class="scan-hint">Arahkan kamera ke QR code</p>
      </div>
      <div class="scanner-controls">
        <button @click="stopScanner" class="btn-secondary">Stop Scanner</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const emit = defineEmits(['scan', 'error'])

const videoElement = ref(null)
const hasPermission = ref(false)
const error = ref('')
let stream = null
let scanning = false

// Simple QR code detection using canvas and image processing
// Note: For production, consider using a library like html5-qrcode or vue-qrcode-reader
let scanInterval = null

async function requestPermission() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' } // Use back camera on mobile
    })
    if (videoElement.value) {
      videoElement.value.srcObject = stream
      hasPermission.value = true
      error.value = ''
      startScanning()
    }
  } catch (err) {
    error.value = 'Tidak dapat mengakses kamera. Pastikan izin kamera sudah diberikan.'
    hasPermission.value = false
    emit('error', err)
  }
}

function startScanning() {
  if (scanning) return
  scanning = true
  
  // Simple QR detection - for production use a proper QR library
  scanInterval = setInterval(() => {
    if (videoElement.value && videoElement.value.readyState === videoElement.value.HAVE_ENOUGH_DATA) {
      // This is a placeholder - in production, use html5-qrcode or similar
      // For now, we'll rely on the user to manually input QR data
    }
  }, 500)
}

function stopScanner() {
  scanning = false
  if (scanInterval) {
    clearInterval(scanInterval)
    scanInterval = null
  }
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
  if (videoElement.value) {
    videoElement.value.srcObject = null
  }
}

function initScanner() {
  error.value = ''
  requestPermission()
}

onMounted(() => {
  initScanner()
})

onUnmounted(() => {
  stopScanner()
})

// Expose method to manually set QR data (for fallback input)
defineExpose({
  setQrData(qrData) {
    emit('scan', qrData)
  }
})
</script>

<style scoped>
.qr-scanner {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
}

.permission-error,
.error-message {
  padding: 2rem;
  text-align: center;
  background: #fee2e2;
  border-radius: 12px;
  color: #991b1b;
}

.scanner-container {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  background: #000;
  border-radius: 12px;
  overflow: hidden;
}

.scanner-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scanner-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.scan-frame {
  width: 70%;
  aspect-ratio: 1;
  border: 3px solid #059669;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.scan-hint {
  margin-top: 1rem;
  color: #fff;
  font-size: 0.9rem;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.scanner-controls {
  position: absolute;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary {
  background: #059669;
  color: #fff;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.9);
  color: #000;
}
</style>
