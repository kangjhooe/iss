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

app.mount('#app')
