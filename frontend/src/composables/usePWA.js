import { ref, onMounted } from 'vue'
import { registerSW } from 'virtual:pwa-register'

export function usePWA() {
  const updateServiceWorker = ref(null)
  const needRefresh = ref(false)
  const offlineReady = ref(false)

  onMounted(() => {
    if ('serviceWorker' in navigator) {
      updateServiceWorker.value = registerSW({
        immediate: true,
        onNeedRefresh() {
          needRefresh.value = true
        },
        onOfflineReady() {
          offlineReady.value = true
        },
        onRegistered(registration) {
          console.log('Service Worker registered:', registration)
        },
        onRegisterError(error) {
          console.error('Service Worker registration error:', error)
        }
      })
    }
  })

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
