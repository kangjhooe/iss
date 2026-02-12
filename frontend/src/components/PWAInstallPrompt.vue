<template>
  <div v-if="showPrompt" class="pwa-install-prompt">
    <div class="prompt-content">
      <div class="prompt-icon">📱</div>
      <div class="prompt-text">
        <h3>Install servr</h3>
        <p>Install aplikasi untuk akses lebih cepat dan fitur offline</p>
      </div>
      <div class="prompt-actions">
        <button @click="dismiss" class="btn-dismiss">Nanti</button>
        <button @click="install" class="btn-install">Install</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const showPrompt = ref(false)
let deferredPrompt = null

onMounted(() => {
  // Check if already installed
  if (window.matchMedia('(display-mode: standalone)').matches) {
    return
  }

  // Check if dismissed before
  const dismissed = localStorage.getItem('pwa-install-dismissed')
  if (dismissed) {
    const dismissedTime = parseInt(dismissed)
    const daysSinceDismissed = (Date.now() - dismissedTime) / (1000 * 60 * 60 * 24)
    if (daysSinceDismissed < 7) {
      return // Don't show for 7 days after dismissal
    }
  }

  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    showPrompt.value = true
  })
})

function dismiss() {
  showPrompt.value = false
  localStorage.setItem('pwa-install-dismissed', Date.now().toString())
}

async function install() {
  if (!deferredPrompt) {
    return
  }

  deferredPrompt.prompt()
  const { outcome } = await deferredPrompt.userChoice
  
  if (outcome === 'accepted') {
    console.log('User accepted the install prompt')
  }
  
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
  background: #0ea5e9;
  color: #fff;
}

.btn-install:hover {
  background: #0284c7;
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
