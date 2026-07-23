import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appName = env.VITE_APP_NAME || 'servr.in'

  return {
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
      manifest: {
        name: appName,
        short_name: appName,
        description: `${appName} - One Platform for Smarter Education. Sistem manajemen sekolah terintegrasi untuk sekolah dan madrasah di Indonesia`,
        theme_color: '#0ea5e9',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        runtimeCaching: [
          // Jangan cache request API (termasuk login/register/logout) agar setelah logout
          // request login selalu ke server dan tidak dapat response cache lama (mis. 422)
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'image-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
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
      }
    }
  }
  }
})
