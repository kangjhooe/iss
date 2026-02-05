import { ref, onMounted } from 'vue'
import api from '@/api'

const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || ''

export function usePushNotifications() {
  const isSupported = ref(false)
  const isSubscribed = ref(false)
  const subscription = ref(null)
  const permission = ref('default')

  onMounted(async () => {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      isSupported.value = true
      await checkSubscription()
      checkPermission()
    }
  })

  async function checkPermission() {
    if ('Notification' in window) {
      permission.value = Notification.permission
    }
  }

  async function checkSubscription() {
    try {
      const registration = await navigator.serviceWorker.ready
      const sub = await registration.pushManager.getSubscription()
      isSubscribed.value = !!sub
      subscription.value = sub
    } catch (error) {
      console.error('Error checking subscription:', error)
    }
  }

  async function requestPermission() {
    if (!('Notification' in window)) {
      throw new Error('Browser tidak mendukung notifikasi')
    }

    const result = await Notification.requestPermission()
    permission.value = result
    return result === 'granted'
  }

  async function subscribe() {
    if (!isSupported.value) {
      throw new Error('Browser tidak mendukung push notifications')
    }

    if (permission.value !== 'granted') {
      const granted = await requestPermission()
      if (!granted) {
        throw new Error('Izin notifikasi ditolak')
      }
    }

    try {
      const registration = await navigator.serviceWorker.ready
      
      const sub = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
      })

      subscription.value = sub
      isSubscribed.value = true

      // Send subscription to server (if endpoint exists)
      try {
        await api.post('/v1/push/subscribe', {
          subscription: sub.toJSON()
        })
      } catch (error) {
        console.warn('Push subscription endpoint not available:', error)
        // Continue even if endpoint doesn't exist
      }

      return sub
    } catch (error) {
      console.error('Error subscribing to push:', error)
      throw error
    }
  }

  async function unsubscribe() {
    if (!subscription.value) {
      return
    }

    try {
      await subscription.value.unsubscribe()
      
      // Remove subscription from server (if endpoint exists)
      try {
        await api.post('/v1/push/unsubscribe', {
          subscription: subscription.value.toJSON()
        })
      } catch (error) {
        console.warn('Push unsubscribe endpoint not available:', error)
        // Continue even if endpoint doesn't exist
      }

      subscription.value = null
      isSubscribed.value = false
    } catch (error) {
      console.error('Error unsubscribing from push:', error)
      throw error
    }
  }

  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4)
    const base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/')

    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
  }

  return {
    isSupported,
    isSubscribed,
    permission,
    subscribe,
    unsubscribe,
    requestPermission
  }
}
