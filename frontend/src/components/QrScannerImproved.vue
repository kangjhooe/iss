<template>
  <div class="qr-scanner-improved">
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
      <canvas ref="canvasElement" style="display: none;"></canvas>
      <div class="scanner-overlay">
        <div class="scan-frame"></div>
        <p class="scan-hint">Arahkan kamera ke QR code</p>
        <p v-if="scanning" class="scan-status">Memindai...</p>
      </div>
      <div class="scanner-controls">
        <button @click="stopScanner" class="btn-secondary">Stop Scanner</button>
      </div>
    </div>
    
    <!-- Fallback: Manual input -->
    <div class="manual-fallback">
      <p class="fallback-hint">Atau masukkan data QR code secara manual:</p>
      <textarea 
        v-model="manualInput" 
        placeholder="Tempel data QR code di sini (format JSON)..."
        class="manual-input"
        rows="3"
        @input="handleManualInput"
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const emit = defineEmits(['scan', 'error'])

const videoElement = ref(null)
const canvasElement = ref(null)
const hasPermission = ref(false)
const error = ref('')
const scanning = ref(false)
const manualInput = ref('')
let stream = null
let scanInterval = null

// Simple QR code detection using canvas
// Note: For production, consider using html5-qrcode library:
// npm install html5-qrcode
// import { Html5Qrcode } from 'html5-qrcode'

async function requestPermission() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { 
        facingMode: 'environment', // Use back camera on mobile
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
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
  if (scanning.value) return
  scanning.value = true
  
  // Simple QR detection using canvas
  // This is a basic implementation - for production use html5-qrcode
  scanInterval = setInterval(() => {
    if (videoElement.value && canvasElement.value && 
        videoElement.value.readyState === videoElement.value.HAVE_ENOUGH_DATA) {
      try {
        const canvas = canvasElement.value
        const video = videoElement.value
        canvas.width = video.videoWidth
        canvas.height = video.videoHeight
        const ctx = canvas.getContext('2d')
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
        
        // Try to read QR code from canvas
        // Note: This requires a QR code library like jsQR or html5-qrcode
        // For now, we'll rely on manual input
      } catch (e) {
        console.error('Scan error:', e)
      }
    }
  }, 500)
}

function stopScanner() {
  scanning.value = false
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

function handleManualInput() {
  const input = manualInput.value.trim()
  if (input && isValidJson(input)) {
    try {
      const parsed = JSON.parse(input)
      if (parsed.type && parsed.id && parsed.institution_id) {
        emit('scan', input)
        manualInput.value = ''
      }
    } catch (e) {
      // Invalid JSON, ignore
    }
  }
}

function isValidJson(str) {
  try {
    JSON.parse(str)
    return true
  } catch {
    return false
  }
}

onMounted(() => {
  initScanner()
})

onUnmounted(() => {
  stopScanner()
})

// Expose method to manually set QR data
defineExpose({
  setQrData(qrData) {
    emit('scan', qrData)
  }
})
</script>

<style scoped>
.qr-scanner-improved {
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
  margin-bottom: 1rem;
}

.scanner-container {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  background: #000;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1rem;
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
  border: 3px solid #0ea5e9;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.scan-hint {
  margin-top: 1rem;
  color: #fff;
  font-size: 0.9rem;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.scan-status {
  margin-top: 0.5rem;
  color: #0ea5e9;
  font-size: 0.85rem;
  font-weight: 600;
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
  background: #0ea5e9;
  color: #fff;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.9);
  color: #000;
}

.manual-fallback {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.fallback-hint {
  margin-bottom: 0.5rem;
  color: #64748b;
  font-size: 0.85rem;
}

.manual-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.85rem;
  font-family: monospace;
  resize: vertical;
}
</style>
