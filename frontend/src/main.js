import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/module-page.css'
import './assets/student-portal.css'
import './assets/super-admin-responsive.css'
import './utils/pwaInstall'
import { usePWA } from './composables/usePWA'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

usePWA()

// Cek sesi dulu (cookie), lalu tunggu navigasi awal — cegah flash halaman publik
// saat hard-refresh di /dashboard sebelum auth state siap.
const authStore = useAuthStore()
authStore.ensureAuthChecked().finally(() => {
  app.use(router)
  router.isReady().then(() => {
    app.mount('#app')
  })
})
