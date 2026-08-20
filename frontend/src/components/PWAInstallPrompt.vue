<template>
  <div v-if="showPrompt" class="pwa-install-prompt">
    <div class="prompt-content">
      <div class="prompt-icon">{{ isIos ? '📲' : '📱' }}</div>
      <div class="prompt-text">
        <h3>{{ isIos ? `Tambahkan ${appName}` : `Install ${appName}` }}</h3>
        <p v-if="isIos">
          Di Safari, ketuk tombol <strong>Bagikan</strong> lalu pilih <strong>Add to Home Screen</strong>
        </p>
        <p v-else>Install aplikasi untuk akses lebih cepat dari layar utama HP</p>
      </div>
      <div class="prompt-actions">
        <button type="button" class="btn-dismiss" @click="dismiss">Nanti</button>
        <button v-if="!isIos" type="button" class="btn-install" @click="install">Install</button>
        <button v-else type="button" class="btn-install" @click="dismiss">Mengerti</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { appName } from '@/config/app'
import {
  clearDeferredPrompt,
  getDeferredPrompt,
  isIosDevice,
  isStandaloneDisplay,
  onInstallPromptChange
} from '@/utils/pwaInstall'

const DISMISS_KEY = 'pwa-install-dismissed'
const DISMISS_DAYS = 7

const showPrompt = ref(false)
const isIos = ref(false)
let deferredPrompt = null
let unsubscribe = null
let iosTimer = null

function wasDismissedRecently() {
  const dismissed = localStorage.getItem(DISMISS_KEY)
  if (!dismissed) return false
  const daysSinceDismissed = (Date.now() - parseInt(dismissed, 10)) / (1000 * 60 * 60 * 24)
  return daysSinceDismissed < DISMISS_DAYS
}

onMounted(() => {
  if (isStandaloneDisplay() || wasDismissedRecently()) {
    return
  }

  isIos.value = isIosDevice()

  unsubscribe = onInstallPromptChange((event) => {
    deferredPrompt = event
    if (event && !isStandaloneDisplay() && !wasDismissedRecently()) {
      showPrompt.value = true
    }
  })

  if (getDeferredPrompt()) {
    return
  }

  if (isIos.value) {
    iosTimer = window.setTimeout(() => {
      if (!isStandaloneDisplay() && !wasDismissedRecently()) {
        showPrompt.value = true
      }
    }, 2500)
  }
})

onUnmounted(() => {
  unsubscribe?.()
  if (iosTimer) window.clearTimeout(iosTimer)
})

function dismiss() {
  showPrompt.value = false
  localStorage.setItem(DISMISS_KEY, Date.now().toString())
}

async function install() {
  if (!deferredPrompt) {
    deferredPrompt = getDeferredPrompt()
  }
  if (!deferredPrompt) {
    return
  }

  deferredPrompt.prompt()
  await deferredPrompt.userChoice
  clearDeferredPrompt()
  deferredPrompt = null
  showPrompt.value = false
}
</script>

<style scoped>
.pwa-install-prompt {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10000;
  max-width: 400px;
  width: calc(100% - 40px);
}

.prompt-content {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.prompt-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.prompt-text {
  flex: 1;
}

.prompt-text h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
}

.prompt-text p {
  margin: 0;
  font-size: 0.875rem;
  color: #64748b;
}

.prompt-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-dismiss,
.btn-install {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.btn-dismiss {
  background: #f1f5f9;
  color: #64748b;
}

.btn-dismiss:hover {
  background: #e2e8f0;
}

.btn-install {
  background: #059669;
  color: #fff;
}

.btn-install:hover {
  background: #047857;
}

@media (max-width: 640px) {
  .prompt-content {
    flex-direction: column;
    text-align: center;
  }

  .prompt-actions {
    width: 100%;
  }

  .btn-dismiss,
  .btn-install {
    flex: 1;
  }
}
</style>
