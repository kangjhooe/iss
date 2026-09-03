<template>
  <section
    ref="heroRef"
    class="hero-section"
    :class="{ 'hero-section--animate': heroVisible }"
    aria-labelledby="hero-heading"
  >
    <div class="hero-section__bg" aria-hidden="true">
      <span class="hero-section__blob hero-section__blob--1"></span>
      <span class="hero-section__blob hero-section__blob--2"></span>
    </div>
    <div class="hero-section__inner">
      <div class="hero-section__copy">
        <p class="hero-section__badge">Gratis untuk sekolah · Siap pakai</p>
        <h1 id="hero-heading" class="hero-section__headline">
          <template v-if="headlineParts.accent">
            {{ headlineParts.before }}<span class="hero-section__accent">{{ headlineParts.accent }}</span>{{ headlineParts.after }}
          </template>
          <template v-else>{{ headline }}</template>
        </h1>
        <p class="hero-section__subheadline">
          {{ subheadline }}
        </p>
        <div class="hero-section__actions">
          <a
            v-if="isHashLink(primaryCtaTo)"
            :href="primaryCtaTo"
            class="hero-section__btn hero-section__btn--primary"
          >
            {{ primaryCtaText }}
          </a>
          <router-link
            v-else
            :to="primaryCtaTo"
            class="hero-section__btn hero-section__btn--primary"
          >
            {{ primaryCtaText }}
          </router-link>
          <a
            v-if="isHashLink(secondaryCtaTo)"
            :href="secondaryCtaTo"
            class="hero-section__btn hero-section__btn--secondary"
          >
            {{ secondaryCtaText }}
          </a>
          <router-link
            v-else
            :to="secondaryCtaTo"
            class="hero-section__btn hero-section__btn--secondary"
          >
            {{ secondaryCtaText }}
          </router-link>
        </div>
      </div>
      <div class="hero-section__visual">
        <div class="hero-section__glow" aria-hidden="true"></div>
        <div v-if="heroImageUrl" class="hero-section__image-wrap" aria-hidden="true">
          <img :src="heroImageUrl" alt="" class="hero-section__hero-image" />
        </div>
        <div v-else class="hero-section__mockup" aria-hidden="true">
          <div class="hero-section__mockup-bar">
            <span class="hero-section__mockup-dot"></span>
            <span class="hero-section__mockup-dot"></span>
            <span class="hero-section__mockup-dot"></span>
          </div>
          <div class="hero-section__mockup-ui">
            <div class="hero-section__mockup-side">
              <span class="hero-section__mockup-brand"></span>
              <span class="hero-section__mockup-side-item is-active"></span>
              <span class="hero-section__mockup-side-item"></span>
              <span class="hero-section__mockup-side-item"></span>
              <span class="hero-section__mockup-side-item"></span>
            </div>
            <div class="hero-section__mockup-main">
              <div class="hero-section__mockup-kpis">
                <div class="hero-section__mockup-kpi">
                  <span class="hero-section__mockup-kpi-label"></span>
                  <span class="hero-section__mockup-kpi-value"></span>
                </div>
                <div class="hero-section__mockup-kpi">
                  <span class="hero-section__mockup-kpi-label"></span>
                  <span class="hero-section__mockup-kpi-value"></span>
                </div>
                <div class="hero-section__mockup-kpi">
                  <span class="hero-section__mockup-kpi-label"></span>
                  <span class="hero-section__mockup-kpi-value"></span>
                </div>
              </div>
              <div class="hero-section__mockup-bars">
                <span style="--h: 42%"></span>
                <span style="--h: 68%"></span>
                <span style="--h: 54%"></span>
                <span style="--h: 86%"></span>
                <span style="--h: 60%"></span>
                <span style="--h: 74%"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  headline: {
    type: String,
    default: 'Sistem Informasi Sekolah & Madrasah dalam Satu Platform',
  },
  subheadline: {
    type: String,
    default: 'Kelola PPDB, data siswa & guru, raport, absensi, dan administrasi—semua dalam satu tempat. Tanpa ribet.',
  },
  heroImageUrl: {
    type: String,
    default: '',
  },
  primaryCtaText: {
    type: String,
    default: 'Daftar Gratis',
  },
  primaryCtaTo: {
    type: String,
    default: '/register',
  },
  secondaryCtaText: {
    type: String,
    default: 'Lihat Fitur',
  },
  secondaryCtaTo: {
    type: String,
    default: '#fitur',
  },
})

const heroRef = ref(null)
const heroVisible = ref(true)
const heroImageUrl = computed(() => props.heroImageUrl || '')

function isHashLink(to) {
  return typeof to === 'string' && to.startsWith('#')
}

const ACCENT_PHRASE = 'Sekolah & Madrasah'
const headlineParts = computed(() => {
  const text = props.headline || ''
  const index = text.indexOf(ACCENT_PHRASE)
  if (index === -1) return { accent: null }
  return {
    before: text.slice(0, index),
    accent: ACCENT_PHRASE,
    after: text.slice(index + ACCENT_PHRASE.length),
  }
})
</script>

<style scoped>
.hero-section {
  position: relative;
  padding: 72px 20px 80px;
  padding-left: max(20px, env(safe-area-inset-left));
  padding-right: max(20px, env(safe-area-inset-right));
  overflow: hidden;
}

.hero-section__bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(165deg, #f8fafc 0%, #ecfdf5 45%, #f0fdf4 100%);
  z-index: 0;
}

.hero-section__blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(64px);
  pointer-events: none;
}

.hero-section__blob--1 {
  width: 420px;
  height: 420px;
  background: rgba(5, 150, 105, 0.18);
  top: -140px;
  right: 8%;
}

.hero-section__blob--2 {
  width: 300px;
  height: 300px;
  background: rgba(16, 185, 129, 0.14);
  bottom: -100px;
  left: -40px;
}

.hero-section__inner {
  position: relative;
  z-index: 1;
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 48px;
  align-items: center;
}

.hero-section__copy {
  text-align: left;
}

.hero-section__badge {
  display: inline-flex;
  align-items: center;
  margin: 0 0 14px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(5, 150, 105, 0.1);
  border: 1px solid rgba(5, 150, 105, 0.18);
  color: #047857;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.hero-section__headline,
.hero-section__subheadline,
.hero-section__actions,
.hero-section__badge {
  opacity: 1;
  transform: translateY(0);
}

.hero-section--animate .hero-section__badge {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.45s ease-out forwards;
}

.hero-section--animate .hero-section__headline {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.5s ease-out 0.06s forwards;
}

.hero-section--animate .hero-section__subheadline {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.45s ease-out 0.12s forwards;
}

.hero-section--animate .hero-section__actions {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.45s ease-out 0.18s forwards;
}

@keyframes hero-fade-up {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-section__headline {
  font-size: clamp(28px, 4.4vw, 42px);
  font-weight: 800;
  color: #0f172a;
  line-height: 1.18;
  letter-spacing: -0.03em;
  margin: 0 0 14px;
}

.hero-section__accent {
  color: #059669;
}

.hero-section__subheadline {
  font-size: clamp(15px, 2vw, 17px);
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 28px;
  max-width: 440px;
}

.hero-section__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.hero-section__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 22px;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 10px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
  -webkit-tap-highlight-color: transparent;
  border: 2px solid transparent;
}

.hero-section__btn--primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
}

.hero-section__btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45);
}

.hero-section__btn--secondary {
  background: #fff;
  color: #059669;
  border-color: #059669;
}

.hero-section__btn--secondary:hover {
  background: #f8fafc;
  border-color: #047857;
  color: #047857;
  transform: translateY(-1px);
}

.hero-section__btn:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.hero-section__visual {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-section__glow {
  position: absolute;
  width: 78%;
  height: 78%;
  border-radius: 32px;
  background: radial-gradient(circle, rgba(5, 150, 105, 0.22) 0%, transparent 70%);
  filter: blur(12px);
  z-index: 0;
}

.hero-section__image-wrap {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14), 0 8px 24px rgba(5, 150, 105, 0.1);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.hero-section__hero-image {
  width: 100%;
  height: auto;
  display: block;
  aspect-ratio: 16 / 10;
  object-fit: cover;
}

.hero-section__mockup {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14), 0 8px 24px rgba(5, 150, 105, 0.1);
  border: 1px solid rgba(226, 232, 240, 0.85);
  overflow: hidden;
}

.hero-section--animate .hero-section__mockup,
.hero-section--animate .hero-section__image-wrap {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.6s ease-out 0.2s forwards;
}

.hero-section__mockup-bar {
  display: flex;
  gap: 6px;
  padding: 10px 14px;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
}

.hero-section__mockup-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
}

.hero-section__mockup-dot:nth-child(1) { background: #f87171; }
.hero-section__mockup-dot:nth-child(2) { background: #fbbf24; }
.hero-section__mockup-dot:nth-child(3) { background: #34d399; }

.hero-section__mockup-ui {
  display: grid;
  grid-template-columns: 72px 1fr;
  min-height: 220px;
}

.hero-section__mockup-side {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px 10px;
  background: linear-gradient(180deg, #064e3b 0%, #047857 100%);
}

.hero-section__mockup-brand {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.92);
  margin-bottom: 6px;
}

.hero-section__mockup-side-item {
  height: 8px;
  border-radius: 99px;
  background: rgba(255, 255, 255, 0.28);
}

.hero-section__mockup-side-item.is-active {
  background: #fff;
}

.hero-section__mockup-main {
  padding: 16px;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-section__mockup-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 14px;
}

.hero-section__mockup-kpi {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 8px;
}

.hero-section__mockup-kpi-label {
  display: block;
  height: 6px;
  width: 55%;
  border-radius: 99px;
  background: #e2e8f0;
  margin-bottom: 8px;
}

.hero-section__mockup-kpi-value {
  display: block;
  height: 10px;
  width: 70%;
  border-radius: 99px;
  background: linear-gradient(90deg, #059669, #34d399);
}

.hero-section__mockup-kpi:nth-child(2) .hero-section__mockup-kpi-value {
  width: 58%;
}

.hero-section__mockup-kpi:nth-child(3) .hero-section__mockup-kpi-value {
  width: 64%;
}

.hero-section__mockup-bars {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 92px;
  padding: 12px 10px 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.hero-section__mockup-bars span {
  flex: 1;
  height: var(--h);
  border-radius: 6px 6px 2px 2px;
  background: linear-gradient(180deg, #34d399 0%, #059669 100%);
  opacity: 0.85;
}

@media (max-width: 768px) {
  .hero-section {
    padding: 40px 16px 56px;
  }

  .hero-section__inner {
    grid-template-columns: 1fr;
    gap: 32px;
    text-align: center;
  }

  .hero-section__copy {
    text-align: center;
  }

  .hero-section__subheadline {
    max-width: none;
    margin-left: auto;
    margin-right: auto;
  }

  .hero-section__actions {
    justify-content: center;
  }

  .hero-section__visual {
    order: -1;
  }

  .hero-section__mockup,
  .hero-section__image-wrap {
    max-width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-section__headline,
  .hero-section__subheadline,
  .hero-section__actions,
  .hero-section__badge,
  .hero-section__mockup,
  .hero-section__image-wrap {
    opacity: 1;
    transform: none;
    animation: none;
  }
}
</style>
