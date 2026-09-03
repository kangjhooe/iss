/**
 * SEO helpers: meta robots, canonical, JSON-LD, default public copy.
 */
import { appName, appTagline } from '@/config/app'

export const SEO_TITLE = `${appName} — Sistem Informasi Sekolah & Madrasah | PPDB, Raport, Absensi`

export const SEO_DESCRIPTION =
  `Sistem informasi sekolah untuk SD, SMP, SMA & madrasah. Kelola PPDB, siswa, raport, absensi, dan keuangan dalam satu platform. Coba demo gratis di ${appName}.`

export function appOrigin() {
  const fromEnv = (import.meta.env.VITE_APP_URL || '').replace(/\/$/, '')
  if (fromEnv) return fromEnv
  if (typeof window !== 'undefined' && window.location?.origin) return window.location.origin
  return 'https://servr.in'
}

function ensureMeta(attrName, attrValue) {
  if (typeof document === 'undefined') return null
  let el = document.querySelector(`meta[${attrName}="${attrValue}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attrName, attrValue)
    document.head.appendChild(el)
  }
  return el
}

export function setMetaContent(attrName, attrValue, content) {
  if (!content) return
  const el = ensureMeta(attrName, attrValue)
  if (el) el.setAttribute('content', content)
}

export function setRobots(content) {
  setMetaContent('name', 'robots', content)
}

export function setCanonical(href) {
  if (typeof document === 'undefined' || !href) return
  let el = document.querySelector('link[rel="canonical"]')
  if (!el) {
    el = document.createElement('link')
    el.setAttribute('rel', 'canonical')
    document.head.appendChild(el)
  }
  el.setAttribute('href', href)
}

export function absoluteUrl(path = '/') {
  const origin = appOrigin()
  if (!path || path === '/') return `${origin}/`
  const normalized = path.startsWith('/') ? path : `/${path}`
  return `${origin}${normalized}`
}

/**
 * Routes that should appear in search results.
 * Everything else (auth app, login/register) gets noindex.
 */
export function routeShouldIndex(route) {
  if (!route) return false
  if (route.meta?.noindex === true) return false
  if (route.meta?.index === true) return true
  if (route.meta?.requiresAuth) return false
  if (route.meta?.requiresGuest) return false
  const name = route.name
  const publicNames = new Set([
    'Home',
    'ReleaseNotes',
    'Panduan',
    'PanduanAdmin',
    'PanduanGuru',
    'PanduanSiswa',
    'PanduanOrangTua',
    'SchoolPublic',
    'PpdbPublicRegister',
    'PpdbPublicRegisterByNpsn',
    'PublicEbooks',
    'PublicGuestBook',
    'PpdbCheckResult',
    'PpdbLengkapiBerkas',
  ])
  return publicNames.has(name)
}

export function applyRouteSeo(route) {
  if (typeof document === 'undefined' || !route) return

  const indexable = routeShouldIndex(route)
  setRobots(indexable ? 'index, follow' : 'noindex, nofollow')

  const path = route.path || '/'
  // Avoid indexing query-string variants as canonical
  setCanonical(absoluteUrl(path === '/' ? '/' : path))

  if (route.name === 'Home') {
    document.title = SEO_TITLE
    setMetaContent('name', 'description', SEO_DESCRIPTION)
    setMetaContent('property', 'og:title', SEO_TITLE)
    setMetaContent('property', 'og:description', SEO_DESCRIPTION)
    setMetaContent('property', 'og:url', absoluteUrl('/'))
    setMetaContent('name', 'twitter:title', SEO_TITLE)
    setMetaContent('name', 'twitter:description', SEO_DESCRIPTION)
  }
}

export function upsertJsonLd(id, data) {
  if (typeof document === 'undefined') return
  let el = document.getElementById(id)
  if (!el) {
    el = document.createElement('script')
    el.type = 'application/ld+json'
    el.id = id
    document.head.appendChild(el)
  }
  el.textContent = JSON.stringify(data)
}

export function removeJsonLd(id) {
  if (typeof document === 'undefined') return
  document.getElementById(id)?.remove()
}

export function homepageJsonLd() {
  const origin = appOrigin()
  return {
    '@context': 'https://schema.org',
    '@graph': [
      {
        '@type': 'Organization',
        '@id': `${origin}/#organization`,
        name: appName,
        url: `${origin}/`,
        logo: `${origin}/pwa-512x512.png`,
        image: `${origin}/og-image.jpg`,
        description: SEO_DESCRIPTION,
      },
      {
        '@type': 'WebSite',
        '@id': `${origin}/#website`,
        url: `${origin}/`,
        name: appName,
        description: appTagline,
        publisher: { '@id': `${origin}/#organization` },
        inLanguage: 'id-ID',
      },
      {
        '@type': 'SoftwareApplication',
        '@id': `${origin}/#app`,
        name: appName,
        applicationCategory: 'EducationalApplication',
        operatingSystem: 'Web',
        url: `${origin}/`,
        description: SEO_DESCRIPTION,
        offers: {
          '@type': 'Offer',
          price: '0',
          priceCurrency: 'IDR',
          description: 'Daftar gratis untuk sekolah dan madrasah',
        },
        inLanguage: 'id-ID',
        publisher: { '@id': `${origin}/#organization` },
      },
    ],
  }
}
