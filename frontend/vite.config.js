import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'
import { buildHomepageJsonLd, buildSeoShell } from './scripts/seoShell.js'
import { HIDE_PRERENDER_BOOT } from './scripts/hidePrerenderBoot.js'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appName = env.VITE_APP_NAME || 'servr.in'
  const appTagline = env.VITE_APP_TAGLINE || 'Sistem Informasi Sekolah & Madrasah'
  const appUrl = (env.VITE_APP_URL || 'https://servr.in').replace(/\/$/, '')
  const pageTitle = `${appName} — Sistem Informasi Sekolah & Madrasah | PPDB, Raport, Absensi`
  const pageDescription = `Sistem informasi sekolah untuk SD, SMP, SMA & madrasah. Kelola PPDB, siswa, raport, absensi, dan keuangan dalam satu platform. Coba demo gratis di ${appName}.`
  const ogImage = `${appUrl}/og-image.jpg`
  const seoShell = buildSeoShell({ appName, appTagline, pageDescription })
  const jsonLd = buildHomepageJsonLd({ appName, appTagline, appUrl, pageDescription })
  const jsonLdTag = `<script type="application/ld+json" id="homepage-jsonld">${JSON.stringify(jsonLd)}</script>`

  return {
  plugins: [
    {
      name: 'html-social-meta',
      transformIndexHtml(html) {
        // Shell SEO hanya untuk no-JS/crawler. Jangan taruh di #app tanpa noscript —
        // hard-refresh /dashboard sempat menampilkan halaman publik (FOUC) sebelum Vue mount.
        const shellStyles = `<style id="seo-shell-style">.seo-shell{max-width:720px;margin:2rem auto;padding:1.25rem;font-family:system-ui,sans-serif;line-height:1.55;color:#0f172a}.seo-shell h1{font-size:1.75rem;line-height:1.25;margin:0 0 .75rem}.seo-shell h2{font-size:1.15rem;margin:1.25rem 0 .5rem}.seo-shell ul{padding-left:1.2rem}.seo-shell a{color:#047857}</style>`
        return html
          .replaceAll('@@HTML_TITLE@@', pageTitle)
          .replaceAll('@@HTML_DESCRIPTION@@', pageDescription)
          .replaceAll('@@HTML_APP_NAME@@', appName)
          .replaceAll('@@HTML_OG_URL@@', `${appUrl}/`)
          .replaceAll('@@HTML_OG_IMAGE@@', ogImage)
          // HIDE_PRERENDER_BOOT: index.html prerender = Home, tapi juga SPA fallback
          // untuk /super-admin/* dll. Sembunyikan sampai Vue mount di luar /.
          .replace('</head>', `    ${shellStyles}\n    ${jsonLdTag}\n    ${HIDE_PRERENDER_BOOT}\n  </head>`)
          .replace(
            '<div id="app"></div>',
            `<div id="app"><noscript>${seoShell}</noscript></div>`
          )
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
        'pwa-maskable-512x512.png',
        'og-image.jpg',
        'robots.txt',
        'sitemap.xml'
      ],
      manifest: {
        id: '/',
        name: appName,
        short_name: appName,
        description: `${appName} — ${appTagline}. Kelola PPDB, raport, absensi, dan administrasi sekolah dalam satu platform.`,
        lang: 'id',
        theme_color: '#059669',
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
        globPatterns: ['**/*.{js,css,html,ico,png,svg,jpg,txt,xml}'],
        navigateFallback: 'index.html',
        // Biarkan folder HTML prerender (mis. /catatan-rilis/) dilayani apa adanya
        navigateFallbackDenylist: [
          /^\/api/,
          /^\/storage/,
          /^\/catatan-rilis(?:\/|$)/,
        ],
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
        // SW di dev sering cache respons kosong → halaman putih; aktifkan hanya saat uji PWA.
        enabled: false,
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
