<template>
  <section
    ref="heroRef"
    class="hero-section"
    :class="{ 'hero-section--animate': heroVisible }"
    aria-labelledby="hero-heading"
  >
    <div class="hero-section__bg" aria-hidden="true"></div>
    <div class="hero-section__inner">
      <div class="hero-section__copy">
        <h1 id="hero-heading" class="hero-section__headline">
          {{ headline }}
        </h1>
        <p class="hero-section__subheadline">
          {{ subheadline }}
        </p>
        <div class="hero-section__actions">
          <router-link
            :to="primaryCtaTo"
            class="hero-section__btn hero-section__btn--primary"
          >
            {{ primaryCtaText }}
          </router-link>
          <router-link
            :to="secondaryCtaTo"
            class="hero-section__btn hero-section__btn--secondary"
          >
            {{ secondaryCtaText }}
          </router-link>
        </div>
      </div>
      <div class="hero-section__visual">
        <div class="hero-section__mockup" aria-hidden="true">
          <div class="hero-section__mockup-bar">
            <span class="hero-section__mockup-dot"></span>
            <span class="hero-section__mockup-dot"></span>
            <span class="hero-section__mockup-dot"></span>
          </div>
          <div class="hero-section__mockup-screen">
            <div class="hero-section__placeholder">
              <svg class="hero-section__placeholder-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M3 21H21V9L12 3L3 9V21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 21V12H15V21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="hero-section__placeholder-text">Tampilan aplikasi</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  headline: {
    type: String,
    default: 'Satu Platform untuk Mengelola Sekolah & Madrasah',
  },
  subheadline: {
    type: String,
    default: 'Profil institusi, data siswa & guru, PPDB, rapor—semua dalam satu tempat. Tanpa ribet.',
  },
  primaryCtaText: {
    type: String,
    default: 'Lihat Contoh',
  },
  primaryCtaTo: {
    type: String,
    default: '#fitur',
  },
  secondaryCtaText: {
    type: String,
    default: 'Daftar Gratis',
  },
  secondaryCtaTo: {
    type: String,
    default: '/register',
  },
})

const heroRef = ref(null)
const heroVisible = ref(true)
</script>

<style scoped>
.hero-section {
  position: relative;
  padding: 40px 20px 48px;
  padding-left: max(20px, env(safe-area-inset-left));
  padding-right: max(20px, env(safe-area-inset-right));
  overflow: hidden;
}

.hero-section__bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%);
  z-index: 0;
  transition: opacity 0.6s ease;
}

.hero-section--animate .hero-section__bg {
  animation: hero-bg-soft 8s ease-in-out infinite;
}

@keyframes hero-bg-soft {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.92; }
}

.hero-section__inner {
  position: relative;
  z-index: 1;
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}

.hero-section__copy {
  text-align: left;
}

/* Tanpa class animate: tampil langsung (fallback). Dengan class: animasi fade-up. */
.hero-section__headline,
.hero-section__subheadline,
.hero-section__actions {
  opacity: 1;
  transform: translateY(0);
}

.hero-section--animate .hero-section__headline {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.5s ease-out forwards;
}

.hero-section--animate .hero-section__subheadline {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.45s ease-out 0.08s forwards;
}

.hero-section--animate .hero-section__actions {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.45s ease-out 0.16s forwards;
}

@keyframes hero-fade-up {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-section__headline {
  font-size: clamp(26px, 4.2vw, 38px);
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
  letter-spacing: -0.02em;
  margin: 0 0 12px;
}

.hero-section__subheadline {
  font-size: clamp(15px, 2vw, 17px);
  color: #64748b;
  line-height: 1.55;
  margin: 0 0 24px;
  max-width: 420px;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.35);
}

.hero-section__btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.45);
}

.hero-section__btn--secondary {
  background: #fff;
  color: #667eea;
  border-color: #667eea;
}

.hero-section__btn--secondary:hover {
  background: #f8fafc;
  border-color: #5568d3;
  color: #5568d3;
  transform: translateY(-1px);
}

.hero-section__btn:focus-visible {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* Visual: mockup placeholder */
.hero-section__visual {
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-section__mockup {
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(30, 41, 59, 0.12), 0 8px 24px rgba(102, 126, 234, 0.08);
  border: 1px solid rgba(226, 232, 240, 0.8);
  overflow: hidden;
}

.hero-section--animate .hero-section__mockup {
  opacity: 0;
  transform: translateY(14px);
  animation: hero-fade-up 0.6s ease-out 0.2s forwards;
}

.hero-section__mockup-bar {
  display: flex;
  gap: 6px;
  padding: 12px 16px;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
}

.hero-section__mockup-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #cbd5e1;
}

.hero-section__mockup-dot:nth-child(1) { background: #94a3b8; }
.hero-section__mockup-dot:nth-child(2) { background: #94a3b8; }
.hero-section__mockup-dot:nth-child(3) { background: #94a3b8; }

.hero-section__mockup-screen {
  aspect-ratio: 16 / 10;
  min-height: 200px;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-section__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
}

.hero-section__placeholder-icon {
  width: 48px;
  height: 48px;
  color: #cbd5e1;
}

.hero-section__placeholder-text {
  font-size: 13px;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-section {
    padding: 32px 16px 40px;
  }

  .hero-section__inner {
    grid-template-columns: 1fr;
    gap: 28px;
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

  .hero-section__mockup {
    max-width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-section--animate .hero-section__bg {
    animation: none;
  }
  .hero-section__headline,
  .hero-section__subheadline,
  .hero-section__actions,
  .hero-section__mockup {
    opacity: 1;
    transform: none;
    animation: none;
  }
}
</style>
