import { ref } from 'vue'
import { registerSW } from 'virtual:pwa-register'

export function usePWA() {
  const needRefresh = ref(false)
  const offlineReady = ref(false)
  const updateServiceWorker = ref(null)

  if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
    updateServiceWorker.value = registerSW({
      immediate: true,
      onNeedRefresh() {
        needRefresh.value = true
      },
      onOfflineReady() {
        offlineReady.value = true
      },
      onRegisteredSW(swUrl, registration) {
        if (registration) {
          setInterval(() => {
            registration.update()
          }, 60 * 60 * 1000)
        }
      },
      onRegisterError(error) {
        console.error('Service Worker registration error:', error)
      }
    })
  }

  async function updateSW() {
    if (updateServiceWorker.value) {
      await updateServiceWorker.value(true)
      needRefresh.value = false
    }
  }

  return {
    needRefresh,
    offlineReady,
    updateSW
  }
}
