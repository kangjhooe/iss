import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/module-page.css'
import './assets/student-portal.css'
import './assets/super-admin-responsive.css'
import './utils/pwaInstall'
import { usePWA } from './composables/usePWA'

const app = createApp(App)

app.use(createPinia())
app.use(router)

usePWA()

// Tunggu navigasi awal selesai supaya halaman publik (mis. /panduan/*)
// tidak sempat me-render Layout/sidebar sebelum route.name tersedia.
router.isReady().then(() => {
  app.mount('#app')
})
