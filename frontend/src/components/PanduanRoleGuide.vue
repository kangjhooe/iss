<template>
  <div class="panduan-page">
    <PublicNavbar @height-change="onNavbarHeight" />

    <main class="panduan-main">
      <section class="panduan-hero">
        <div class="hero-glow hero-glow--one" aria-hidden="true" />
        <div class="hero-glow hero-glow--two" aria-hidden="true" />
        <div class="panduan-inner panduan-hero__inner">
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <router-link to="/panduan">Panduan</router-link>
            <span aria-hidden="true">/</span>
            <span>{{ guide.breadcrumb || guide.audience }}</span>
          </nav>
          <p class="audience">{{ guide.audience }}</p>
          <h1>{{ guide.title }}</h1>
          <p class="lead">{{ guide.subtitle }}</p>
          <p v-if="guide.roadmap" class="hero-roadmap">
            {{ guide.roadmap }}
          </p>
          <div class="hero-meta">
            <span class="hero-meta__pill">{{ guide.steps.length }} langkah</span>
            <span class="hero-meta__sep" aria-hidden="true">·</span>
            <span class="hero-meta__text">{{ guide.readTime || 'Lompat lewat daftar isi' }}</span>
          </div>
          <div v-if="canDownloadPdf" class="hero-actions">
            <button
              type="button"
              class="btn btn-download"
              :disabled="pdfDownloading"
              @click="downloadGuidePdf"
            >
              <svg class="btn-download__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              {{ pdfDownloading ? 'Menyiapkan PDF...' : 'Unduh panduan (PDF)' }}
            </button>
          </div>

          <PanduanRoleSwitch :current-slug="guide.slug" />
        </div>
      </section>

      <div ref="guideShellEl" class="panduan-inner guide-shell">
        <div ref="tocRailEl" class="toc-rail">
          <div
            v-show="isDesktop && tocPinned"
            class="toc-placeholder"
            :style="{ height: `${tocHeight}px` }"
            aria-hidden="true"
          />
          <aside
            id="daftar-isi"
            ref="tocAsideEl"
            class="toc-aside"
            :class="{ 'toc-aside--pinned': isDesktop && tocPinned }"
            aria-label="Daftar isi langkah"
          >
            <p class="toc-label">Daftar isi</p>
            <p class="toc-active-hint">
              Langkah {{ activeIndex + 1 }} — {{ activeStep?.title }}
            </p>
            <ol class="toc-list">
              <li v-for="(step, index) in guide.steps" :key="step.id">
                <a
                  :href="`#${step.id}`"
                  class="toc-link"
                  :class="{ 'toc-link--active': activeId === step.id }"
                  @click="onTocClick($event, step.id)"
                >
                  <span class="toc-link__num">{{ index + 1 }}</span>
                  <span class="toc-link__text">{{ step.shortTitle || step.title }}</span>
                </a>
              </li>
            </ol>
          </aside>
        </div>

        <div class="guide-content">
          <ol class="timeline" :aria-label="`Langkah ${guide.title}`">
            <li
              v-for="(step, index) in guide.steps"
              :id="step.id"
              :key="step.id"
              ref="stepEls"
              class="timeline-item"
              :class="{
                'is-visible': visibleMap[step.id],
                'is-active': activeId === step.id
              }"
              :style="{ '--reveal-delay': `${Math.min(index, 6) * 40}ms` }"
            >
              <div class="timeline-rail" aria-hidden="true">
                <span class="timeline-num">{{ index + 1 }}</span>
                <span v-if="index < guide.steps.length - 1" class="timeline-line" />
              </div>

              <article class="step-card">
                <header class="step-header">
                  <p class="step-kicker">Langkah {{ index + 1 }} dari {{ guide.steps.length }}</p>
                  <h2 class="step-title">{{ step.title }}</h2>
                  <p class="step-summary">{{ step.summary }}</p>
                </header>

                <ul v-if="step.actions?.length" class="action-list">
                  <li v-for="(action, i) in step.actions" :key="i">
                    <span class="action-check" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 5 5L20 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>{{ action }}</span>
                  </li>
                </ul>

                <p v-if="step.tip" class="step-tip">
                  <span class="step-tip__label">Tips</span>
                  {{ step.tip }}
                </p>

                <div v-if="step.faqs?.length" class="faq-list">
                  <div v-for="(faq, i) in step.faqs" :key="i" class="faq-item">
                    <p class="faq-q">{{ faq.q }}</p>
                    <p class="faq-a">{{ faq.a }}</p>
                  </div>
                </div>

                <div class="step-footer">
                  <router-link
                    v-if="step.link"
                    :to="step.link.to"
                    class="step-link"
                  >
                    {{ step.link.label }}
                    <span aria-hidden="true">→</span>
                  </router-link>

                  <div class="step-nav">
                    <a
                      v-if="guide.steps[index + 1]"
                      :href="`#${guide.steps[index + 1].id}`"
                      class="step-nav__next"
                      @click="onTocClick($event, guide.steps[index + 1].id)"
                    >
                      Langkah berikutnya
                      <span aria-hidden="true">→</span>
                    </a>
                    <span v-else class="step-nav__done">Selesai — Anda di langkah terakhir</span>
                  </div>
                </div>
              </article>
            </li>
          </ol>

          <div class="guide-footer-cta">
            <div>
              <p class="guide-footer-cta__title">{{ guide.cta?.title || 'Siap mencoba?' }}</p>
              <p class="guide-footer-cta__desc">{{ guide.cta?.desc || 'Kembali ke daftar panduan untuk peran lain.' }}</p>
            </div>
            <div class="guide-footer-cta__actions">
              <router-link
                v-if="guide.cta?.primary"
                :to="guide.cta.primary.to"
                class="btn btn-primary"
              >{{ guide.cta.primary.label }}</router-link>
              <router-link
                :to="guide.cta?.secondary?.to || '/panduan'"
                class="btn btn-ghost"
              >{{ guide.cta?.secondary?.label || 'Semua panduan' }}</router-link>
            </div>
          </div>

          <PanduanRoleSwitch :current-slug="guide.slug" class="role-switch--footer" />
        </div>
      </div>
    </main>

    <footer class="footer">
      <div class="footer-accent"></div>
      <div class="footer-inner">
        <div class="footer-brand">
          <AppLogo class="footer-logo" :size="28" />
          <div class="footer-text">
            <span class="footer-name">{{ appName }}</span>
            <p class="footer-tagline">{{ appTagline }}</p>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; {{ currentYear }} {{ appName }}</span>
          <span class="footer-sep">·</span>
          <router-link to="/panduan" class="footer-version">Panduan</router-link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { appName, appTagline } from '@/config/app'
import AppLogo from '@/components/AppLogo.vue'
import PublicNavbar from '@/components/PublicNavbar.vue'
import PanduanRoleSwitch from '@/components/PanduanRoleSwitch.vue'
import { guidesPublicApi } from '@/api/guidesPublic'
import { usePageMeta } from '@/composables/usePageMeta'
import { useToast } from '@/composables/useToast'
import { openPdfBlob } from '@/utils/pdfPreview'
import { absoluteUrl } from '@/utils/seo'

const props = defineProps({
  guide: {
    type: Object,
    required: true,
  },
})

const guide = computed(() => props.guide)
const currentYear = computed(() => new Date().getFullYear())
const { setMeta } = usePageMeta()
const toast = useToast()
const pdfDownloading = ref(false)

const PDF_DOWNLOADERS = {
  admin: () => guidesPublicApi.downloadAdminPdf(),
  guru: () => guidesPublicApi.downloadGuruPdf(),
  siswa: () => guidesPublicApi.downloadSiswaPdf(),
  'orang-tua': () => guidesPublicApi.downloadOrangTuaPdf(),
}

const canDownloadPdf = computed(() => (
  !!guide.value.pdf && typeof PDF_DOWNLOADERS[guide.value.slug] === 'function'
))

const stepEls = ref([])
const tocRailEl = ref(null)
const tocAsideEl = ref(null)
const guideShellEl = ref(null)
const visibleMap = reactive({})
const activeId = ref(props.guide.steps[0]?.id || '')
const navbarHeight = ref(57)
const tocHeight = ref(320)
const isDesktop = ref(true)
const tocPinned = ref(false)

const activeIndex = computed(() =>
  Math.max(0, guide.value.steps.findIndex((s) => s.id === activeId.value))
)
const activeStep = computed(() => guide.value.steps[activeIndex.value] || guide.value.steps[0])
const stepScrollMargin = computed(() => navbarHeight.value + 20)

let revealObserver = null
let rafId = 0
let activeLockUntil = 0

function prefersReducedMotion() {
  return typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function applyNavbarCssVars() {
  document.documentElement.style.setProperty('--panduan-navbar-h', `${navbarHeight.value}px`)
  document.documentElement.style.setProperty(
    '--panduan-step-offset',
    `${navbarHeight.value + 20}px`
  )
}

function onNavbarHeight(height) {
  if (!height) return
  navbarHeight.value = height
  applyNavbarCssVars()
  syncTocPin()
}

function measureNavbar() {
  applyNavbarCssVars()
}

function clearAsidePinStyles(el) {
  if (!el) return
  el.style.position = ''
  el.style.top = ''
  el.style.left = ''
  el.style.width = ''
  el.style.maxHeight = ''
  el.style.zIndex = ''
}

function setActiveId(id) {
  if (!id || id === activeId.value) return
  activeId.value = id
}

function updateActiveFromScroll() {
  if (Date.now() < activeLockUntil) return
  const marker = navbarHeight.value + 96
  let current = guide.value.steps[0]?.id
  for (const step of guide.value.steps) {
    const el = document.getElementById(step.id)
    if (!el) continue
    if (el.getBoundingClientRect().top <= marker) current = step.id
  }
  setActiveId(current)
}

function syncTocPin() {
  isDesktop.value = window.innerWidth > 900
  measureNavbar()

  const el = tocAsideEl.value
  if (!isDesktop.value || !tocRailEl.value || !el || !guideShellEl.value) {
    if (tocPinned.value) tocPinned.value = false
    clearAsidePinStyles(el)
    return
  }

  const asideRect = el.getBoundingClientRect()
  if (asideRect.height > 0) tocHeight.value = Math.ceil(asideRect.height)

  const railRect = tocRailEl.value.getBoundingClientRect()
  const shellRect = guideShellEl.value.getBoundingClientRect()
  const pinTop = navbarHeight.value + 16
  const maxTop = shellRect.bottom - tocHeight.value - 16
  const shouldPin = railRect.top <= pinTop && shellRect.bottom > pinTop + 48

  if (!shouldPin) {
    if (tocPinned.value) tocPinned.value = false
    clearAsidePinStyles(el)
    return
  }

  if (!tocPinned.value) tocPinned.value = true

  const top = Math.min(pinTop, maxTop)
  el.style.position = 'fixed'
  el.style.top = `${top}px`
  el.style.left = `${Math.round(railRect.left)}px`
  el.style.width = `${Math.round(railRect.width)}px`
  el.style.maxHeight = `${Math.max(140, window.innerHeight - Math.max(top, 0) - 16)}px`
  el.style.zIndex = '15'
}

function onScrollOrResize() {
  if (rafId) cancelAnimationFrame(rafId)
  rafId = requestAnimationFrame(() => {
    syncTocPin()
    updateActiveFromScroll()
    rafId = 0
  })
}

function scrollToId(id) {
  const el = document.getElementById(id)
  if (!el) return
  measureNavbar()
  setActiveId(id)
  activeLockUntil = Date.now() + 700
  const top = el.getBoundingClientRect().top + window.scrollY - stepScrollMargin.value
  window.scrollTo({
    top: Math.max(0, top),
    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
  })
  if (typeof history !== 'undefined') {
    history.replaceState(null, '', `#${id}`)
  }
  onScrollOrResize()
}

function onTocClick(event, id) {
  event.preventDefault()
  scrollToId(id)
}

async function downloadGuidePdf() {
  if (pdfDownloading.value || !canDownloadPdf.value) return
  const downloader = PDF_DOWNLOADERS[guide.value.slug]
  if (!downloader) return

  pdfDownloading.value = true
  try {
    const res = await downloader()
    const blob = res.data
    if (blob?.type && blob.type.includes('json')) {
      toast.error('Gagal membuka', 'Panduan PDF tidak dapat dibuat.')
      return
    }
    const fallback = `panduan-${guide.value.slug}-${String(appName).toLowerCase().replace(/[^a-z0-9]+/g, '-')}.pdf`
    const filename = guide.value.pdf?.filename || fallback
    if (!openPdfBlob(res, filename)) {
      toast.error('Gagal membuka PDF', 'Izinkan pop-up browser, lalu coba lagi.')
    }
  } catch (e) {
    toast.error('Gagal membuka panduan', e.formattedMessage || 'Coba lagi.')
  } finally {
    pdfDownloading.value = false
  }
}

function applyPageMeta() {
  setMeta({
    title: `${guide.value.title} · ${appName}`,
    description: guide.value.subtitle,
    url: absoluteUrl(`/panduan/${guide.value.slug}`),
  })
}

function resetGuideState() {
  Object.keys(visibleMap).forEach((k) => delete visibleMap[k])
  activeId.value = guide.value.steps[0]?.id || ''
  tocPinned.value = false
  clearAsidePinStyles(tocAsideEl.value)
}

function bindStepObservers() {
  revealObserver?.disconnect()
  revealObserver = null

  if (prefersReducedMotion()) {
    guide.value.steps.forEach((s) => { visibleMap[s.id] = true })
    return
  }

  revealObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue
        const id = entry.target.id
        if (id) visibleMap[id] = true
        revealObserver?.unobserve(entry.target)
      }
    },
    { threshold: 0.18, rootMargin: '0px 0px -8% 0px' }
  )

  stepEls.value.forEach((el) => {
    if (el) revealObserver.observe(el)
  })
}

watch(
  () => props.guide?.slug,
  async () => {
    resetGuideState()
    applyPageMeta()
    await nextTick()
    bindStepObservers()
    syncTocPin()
    updateActiveFromScroll()
  }
)

onMounted(async () => {
  applyPageMeta()
  await nextTick()
  measureNavbar()
  syncTocPin()
  updateActiveFromScroll()

  const hash = typeof window !== 'undefined' ? window.location.hash.replace(/^#/, '') : ''
  if (hash && guide.value.steps.some((s) => s.id === hash)) {
    requestAnimationFrame(() => scrollToId(hash))
  }

  bindStepObservers()
  window.addEventListener('scroll', onScrollOrResize, { passive: true })
  window.addEventListener('resize', onScrollOrResize, { passive: true })
})

onUnmounted(() => {
  revealObserver?.disconnect()
  revealObserver = null
  if (rafId) cancelAnimationFrame(rafId)
  window.removeEventListener('scroll', onScrollOrResize)
  window.removeEventListener('resize', onScrollOrResize)
  document.documentElement.style.removeProperty('--panduan-navbar-h')
  document.documentElement.style.removeProperty('--panduan-step-offset')
})
</script>

<style scoped>
.panduan-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  color: #0f172a;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid transparent;
  transition: transform 0.2s ease, filter 0.2s ease, border-color 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-ghost {
  color: #334155;
  background: #fff;
  border-color: #e2e8f0;
}

.btn-ghost:hover {
  border-color: #cbd5e1;
}

.btn-primary {
  color: #fff;
  background: linear-gradient(135deg, #0d9488, #059669);
}

.btn-primary:hover {
  filter: brightness(1.05);
}

.panduan-main {
  flex: 1;
}

.panduan-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px;
}

.panduan-hero {
  position: relative;
  overflow: hidden;
  padding: 40px 0 32px;
  background: linear-gradient(165deg, #ecfdf5 0%, #f0fdfa 45%, #f8fafc 100%);
  border-bottom: 1px solid #e2e8f0;
}

.hero-glow {
  position: absolute;
  border-radius: 999px;
  filter: blur(64px);
  pointer-events: none;
}

.hero-glow--one {
  width: 260px;
  height: 260px;
  top: -90px;
  right: 12%;
  background: rgba(45, 212, 191, 0.32);
}

.hero-glow--two {
  width: 200px;
  height: 200px;
  bottom: -70px;
  left: 8%;
  background: rgba(16, 185, 129, 0.18);
}

.panduan-hero__inner {
  position: relative;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  font-size: 0.85rem;
  color: #64748b;
}

.breadcrumb a {
  color: #0f766e;
  text-decoration: none;
  font-weight: 600;
}

.audience {
  margin: 0 0 8px;
  color: #0f766e;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.panduan-hero h1 {
  margin: 0 0 10px;
  font-size: clamp(1.6rem, 3.5vw, 2.2rem);
  letter-spacing: -0.03em;
  font-weight: 800;
}

.lead {
  margin: 0;
  max-width: 640px;
  color: #475569;
  font-size: 1.05rem;
  line-height: 1.55;
}

.hero-roadmap {
  margin: 14px 0 0;
  max-width: 720px;
  color: #0f766e;
  font-size: 0.88rem;
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: -0.01em;
}

.hero-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 14px;
  flex-wrap: wrap;
}

.hero-meta__pill {
  display: inline-flex;
  padding: 5px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid #a7f3d0;
  color: #0f766e;
  font-size: 0.8rem;
  font-weight: 700;
}

.hero-meta__sep {
  color: #94a3b8;
}

.hero-meta__text {
  color: #64748b;
  font-size: 0.9rem;
}

.hero-actions {
  margin-top: 18px;
}

.btn-download {
  gap: 8px;
  color: #0f766e;
  background: #fff;
  border-color: #99f6e4;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.08);
  cursor: pointer;
}

.btn-download:hover:not(:disabled) {
  border-color: #5eead4;
  background: #f0fdfa;
}

.btn-download:disabled {
  opacity: 0.7;
  cursor: wait;
  transform: none;
}

.btn-download__icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.guide-shell {
  display: flex;
  align-items: flex-start;
  gap: 32px;
  padding-top: 28px;
  padding-bottom: 64px;
}

.toc-rail {
  flex: 0 0 230px;
  width: 230px;
  max-width: 230px;
  position: relative;
}

.toc-placeholder {
  width: 100%;
  pointer-events: none;
}

.toc-aside {
  padding: 16px 14px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #fff;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  scrollbar-width: thin;
  overflow-y: auto;
}

.toc-aside--pinned {
  margin: 0;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.toc-label {
  margin: 0 0 6px;
  font-size: 0.72rem;
  font-weight: 750;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
}

.toc-active-hint {
  margin: 0 0 12px;
  min-height: 2.2em;
  font-size: 0.8rem;
  color: #0f766e;
  font-weight: 600;
  line-height: 1.35;
  transition: opacity 0.2s ease;
}

.toc-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toc-link {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  text-decoration: none;
  color: #475569;
  font-size: 0.88rem;
  font-weight: 600;
  line-height: 1.35;
  border: 1px solid transparent;
  background: transparent;
  box-shadow: inset 0 0 0 0 transparent;
  transition:
    background-color 0.28s ease,
    color 0.28s ease,
    border-color 0.28s ease,
    box-shadow 0.28s ease;
}

.toc-link:hover {
  background: #f0fdfa;
  color: #0f766e;
}

.toc-link--active {
  background: #ecfdf5;
  color: #0f766e;
  box-shadow: inset 3px 0 0 #0d9488;
}

.toc-link__num {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  background: #e2e8f0;
  color: #334155;
  font-size: 0.72rem;
  font-weight: 750;
  transition: background 0.28s ease, color 0.28s ease, transform 0.28s ease;
}

.toc-link--active .toc-link__num {
  background: linear-gradient(145deg, #0d9488, #059669);
  color: #fff;
  transform: scale(1.06);
}

.guide-content {
  flex: 1 1 auto;
  min-width: 0;
}

.timeline {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.timeline-item {
  display: grid;
  grid-template-columns: 48px minmax(0, 1fr);
  gap: 16px;
  scroll-margin-top: var(--panduan-step-offset, 80px);
  opacity: 0;
  transform: translateY(18px);
  transition:
    opacity 0.45s ease var(--reveal-delay, 0ms),
    transform 0.45s ease var(--reveal-delay, 0ms);
}

.timeline-item.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.timeline-rail {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 4px;
}

.timeline-num {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  background: linear-gradient(145deg, #0d9488, #059669);
  color: #fff;
  font-size: 0.9rem;
  font-weight: 750;
  box-shadow: 0 6px 14px rgba(13, 148, 136, 0.28);
}

.timeline-item.is-active .timeline-num {
  box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.2), 0 6px 14px rgba(13, 148, 136, 0.28);
}

.timeline-item.is-visible .timeline-num {
  animation: numPop 0.45s ease both;
  animation-delay: var(--reveal-delay, 0ms);
}

.timeline-line {
  width: 2px;
  flex: 1;
  min-height: 24px;
  margin: 10px 0 6px;
  background: linear-gradient(180deg, #99f6e4, #e2e8f0);
  border-radius: 2px;
}

.step-card {
  margin-bottom: 20px;
  padding: 22px 22px 20px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #fff;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  transition: border-color 0.28s ease, box-shadow 0.28s ease, transform 0.2s ease;
}

.step-card:hover {
  border-color: #99f6e4;
  box-shadow: 0 10px 24px rgba(13, 148, 136, 0.08);
  transform: translateY(-2px);
}

.timeline-item.is-active .step-card {
  border-color: #5eead4;
  box-shadow: 0 10px 24px rgba(13, 148, 136, 0.1);
}

.step-kicker {
  margin: 0 0 6px;
  color: #0f766e;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.step-title {
  margin: 0 0 8px;
  font-size: 1.22rem;
  letter-spacing: -0.02em;
  font-weight: 780;
}

.step-summary {
  margin: 0 0 16px;
  color: #475569;
  font-size: 0.98rem;
  line-height: 1.55;
}

.action-list {
  list-style: none;
  margin: 0 0 14px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.action-list li {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  color: #334155;
  font-size: 0.94rem;
  line-height: 1.45;
}

.action-check {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 1px;
  display: grid;
  place-items: center;
  background: #ecfdf5;
  color: #0f766e;
}

.action-check svg {
  width: 13px;
  height: 13px;
}

.step-tip {
  margin: 0 0 14px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  color: #334155;
  font-size: 0.9rem;
  line-height: 1.5;
}

.step-tip__label {
  display: inline-block;
  margin-right: 6px;
  color: #0f766e;
  font-weight: 750;
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}

.faq-item {
  padding: 12px 14px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.faq-q {
  margin: 0 0 4px;
  font-weight: 700;
  color: #0f172a;
  font-size: 0.92rem;
}

.faq-a {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.5;
}

.step-footer {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.step-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0f766e;
  font-weight: 700;
  font-size: 0.92rem;
  text-decoration: none;
  width: fit-content;
}

.step-link:hover {
  text-decoration: underline;
}

.step-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.step-nav__next {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #0f766e;
  font-size: 0.9rem;
  font-weight: 750;
  text-decoration: none;
}

.step-nav__next:hover {
  text-decoration: underline;
}

.step-nav__done {
  color: #94a3b8;
  font-size: 0.86rem;
  font-weight: 600;
}

.guide-footer-cta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 12px;
  padding: 20px 22px;
  border-radius: 16px;
  border: 1px dashed #99f6e4;
  background: #f0fdfa;
}

.guide-footer-cta__title {
  margin: 0 0 4px;
  font-weight: 750;
  color: #0f766e;
}

.guide-footer-cta__desc {
  margin: 0;
  color: #475569;
  font-size: 0.92rem;
}

.guide-footer-cta__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.footer {
  margin-top: auto;
  border-top: 1px solid #e2e8f0;
  background: #fff;
}

.footer-accent {
  height: 3px;
  background: linear-gradient(90deg, #0d9488, #059669, #34d399);
}

.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 22px 20px 28px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.footer-name {
  font-weight: 700;
}

.footer-tagline {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 0.85rem;
}

.footer-bottom {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  color: #64748b;
  font-size: 0.85rem;
}

.footer-sep {
  opacity: 0.5;
}

.footer-version {
  color: #0f766e;
  text-decoration: none;
  font-weight: 600;
}

@keyframes numPop {
  from { transform: scale(0.72); }
  to { transform: scale(1); }
}

@media (max-width: 900px) {
  .guide-shell {
    flex-direction: column;
    gap: 16px;
  }

  .toc-rail {
    flex: none;
    width: 100%;
    max-width: none;
  }

  .toc-aside {
    position: static !important;
    left: auto !important;
    top: auto !important;
    width: auto !important;
    max-height: none !important;
    overflow: visible;
  }

  .toc-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
  }

  .toc-link--active {
    box-shadow: none;
    border: 1px solid #99f6e4;
  }
}

@media (max-width: 640px) {
  .navbar-actions .nav-link {
    display: none;
  }

  .toc-list {
    grid-template-columns: 1fr;
  }

  .timeline-item {
    grid-template-columns: 36px minmax(0, 1fr);
    gap: 12px;
  }

  .timeline-num {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }

  .step-card {
    padding: 18px 16px 16px;
  }

  .hero-roadmap {
    font-size: 0.8rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .timeline-item,
  .timeline-item.is-visible,
  .btn,
  .step-card,
  .timeline-num,
  .toc-link {
    transition: none !important;
    animation: none !important;
    opacity: 1;
    transform: none;
  }
}
</style>
