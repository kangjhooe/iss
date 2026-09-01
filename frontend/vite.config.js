import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appName = env.VITE_APP_NAME || 'servr.in'
  const appTagline = env.VITE_APP_TAGLINE || 'One Platform for Smarter Education'
  const appUrl = (env.VITE_APP_URL || 'https://servr.in').replace(/\/$/, '')
  const pageTitle = `${appName} - ${appTagline}`
  const pageDescription = `${appName} - ${appTagline}. Sistem manajemen sekolah terintegrasi untuk sekolah dan madrasah di Indonesia. Kelola profil institusi, data siswa, guru, fasilitas, kelas, laporan, dan surat-menyurat dalam satu platform.`
  const ogImage = `${appUrl}/pwa-512x512.png`

  return {
  plugins: [
    {
      name: 'html-social-meta',
      transformIndexHtml(html) {
        return html
          .replaceAll('%HTML_TITLE%', pageTitle)
          .replaceAll('%HTML_DESCRIPTION%', pageDescription)
          .replaceAll('%HTML_APP_NAME%', appName)
          .replaceAll('%HTML_OG_URL%', `${appUrl}/`)
          .replaceAll('%HTML_OG_IMAGE%', ogImage)
      }
    },
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: false,
      includeAssets: [
        'favicon.ico',
        'favicon-32x32.png',
        'favicon-192x192.png',
        'apple-touch-icon.png',
        'pwa-192x192.png',
        'pwa-512x512.png',
        'pwa-maskable-192x192.png',
        'pwa-maskable-512x512.png'
      ],
      manifest: {
        id: '/',
        name: appName,
        short_name: appName,
        description: `${appName} - ${appTagline}. Sistem manajemen sekolah terintegrasi untuk sekolah dan madrasah di Indonesia`,
        lang: 'id',
        theme_color: '#0ea5e9',
        background_color: '#ffffff',
        display: 'standalone',
        start_url: '/',
        scope: '/',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any'
          },
          {
            src: 'pwa-maskable-192x192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'maskable'
          },
          {
            src: 'pwa-maskable-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        navigateFallback: 'index.html',
        navigateFallbackDenylist: [/^\/api/, /^\/storage/],
        runtimeCaching: [
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'image-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 * 30
              }
            }
          }
        ]
      },
      devOptions: {
        enabled: true,
        type: 'module'
      }
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: (id) => {
          if (id.includes('node_modules')) {
            if (id.includes('vue/') || id === 'vue') return 'vue'
            if (id.includes('vue-router')) return 'vue-router'
            if (id.includes('pinia')) return 'pinia'
            if (id.includes('xlsx')) return 'xlsx'
            if (id.includes('quill') || id.includes('@vueup/vue-quill')) return 'quill'
            if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'chart'
            if (id.includes('axios')) return 'axios'
            if (id.includes('pdfjs-dist')) return 'pdfjs'
          }
        }
      }
    },
    chunkSizeWarningLimit: 600
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: process.env.VITE_PROXY_TARGET || 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      }
    }
  }
  }
})
