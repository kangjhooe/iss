import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/module-page.css'
import './assets/student-portal.css'
import './assets/super-admin-responsive.css'
import { usePWA } from './composables/usePWA'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Initialize PWA
if ('serviceWorker' in navigator) {
  usePWA()
}

app.mount('#app')
