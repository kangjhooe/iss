<template>
  <section
    ref="heroRef"
    id="main-content"
    class="hero hero--animate"
    :class="{ 'hero--cover': !!institution?.cover_image_url }"
    tabindex="-1"
  >
    <div class="hero-bg" aria-hidden="true">
      <img
        v-if="institution?.cover_image_url"
        :src="institution.cover_image_url"
        alt=""
        class="hero-cover-img"
      />
      <div class="hero-overlay"></div>
    </div>

    <div class="hero-content">
      <div v-if="institution?.logo_url" class="hero-logo-wrap">
        <img :src="institution.logo_url" :alt="institution.name" class="hero-logo" />
      </div>

      <h1 class="hero-title">{{ institution?.name }}</h1>

      <p v-if="tagline" class="hero-subtitle">{{ tagline }}</p>

      <p v-if="metaParts.length" class="hero-meta">
        <template v-for="(part, i) in metaParts" :key="part">
          <span v-if="i > 0" class="hero-meta-sep" aria-hidden="true">·</span>
          <span>{{ part }}</span>
        </template>
      </p>

      <div class="hero-actions">
        <router-link :to="`/${npsn}/daftar-ppdb`" class="btn btn-primary btn-cta">
          Daftar PPDB
        </router-link>
        <div class="hero-links">
          <router-link :to="`/${npsn}/ebooks`" class="hero-text-link">Perpustakaan Digital</router-link>
          <router-link :to="{ path: '/cek-hasil-ppdb', query: { npsn } }" class="hero-text-link">
            Cek Hasil PPDB
          </router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  institution: { type: Object, default: null },
  npsn: { type: String, default: '' }
})

const heroRef = ref(null)

const tagline = computed(() => {
  const desc = props.institution?.description
  if (desc != null && String(desc).trim()) {
    const t = String(desc).trim().replace(/\s+/g, ' ')
    return t.length > 140 ? `${t.slice(0, 137)}…` : t
  }
  return props.institution?.address || ''
})

const metaParts = computed(() => {
  const i = props.institution
  if (!i) return []
  const parts = []
  if (i.npsn) parts.push(`NPSN ${i.npsn}`)
  if (i.level) parts.push(i.level)
  if (i.type) parts.push(i.type)
  return parts
})

defineExpose({ heroRef })
</script>

<style scoped>
.hero {
  position: relative;
  min-height: min(72vh, 560px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 96px 24px 88px;
  text-align: center;
  z-index: 1;
  overflow: hidden;
}

.hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
  background: linear-gradient(165deg, #064e3b 0%, #047857 42%, #0f766e 78%, #134e4a 100%);
}

.hero-cover-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, rgba(6, 78, 59, 0.72) 0%, rgba(15, 23, 42, 0.55) 55%, rgba(15, 23, 42, 0.78) 100%);
}

.hero:not(.hero--cover) .hero-overlay {
  background:
    radial-gradient(ellipse 80% 60% at 50% 20%, rgba(16, 185, 129, 0.18), transparent 55%),
    linear-gradient(180deg, rgba(6, 78, 59, 0.15) 0%, rgba(15, 23, 42, 0.35) 100%);
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 720px;
  margin: 0 auto;
}

.hero-logo-wrap {
  width: 96px;
  height: 96px;
  margin: 0 auto 1.25rem;
  border-radius: 18px;
  overflow: hidden;
  background: #fff;
  border: 3px solid rgba(255, 255, 255, 0.85);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
}

.hero-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.hero-title {
  font-size: clamp(1.75rem, 4.5vw, 2.75rem);
  font-weight: 800;
  color: #fff;
  margin: 0 0 0.75rem;
  letter-spacing: -0.03em;
  line-height: 1.15;
  text-wrap: balance;
}

.hero-subtitle {
  font-size: 1.0625rem;
  color: rgba(255, 255, 255, 0.88);
  margin: 0 0 1rem;
  line-height: 1.55;
  max-width: 36rem;
  margin-left: auto;
  margin-right: auto;
}

.hero-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.5rem;
  justify-content: center;
  align-items: center;
  margin: 0 0 2rem;
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.72);
  font-weight: 500;
  letter-spacing: 0.01em;
}

.hero-meta-sep {
  opacity: 0.55;
}

.hero-actions {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.15rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  min-height: 44px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: #fff;
  color: #047857;
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.22);
}

.btn-primary:hover {
  background: #f0fdf4;
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.28);
}

.btn-cta {
  padding: 15px 36px;
  font-size: 1.0625rem;
  min-width: 200px;
}

.hero-links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.5rem;
  justify-content: center;
}

.hero-text-link {
  color: rgba(255, 255, 255, 0.88);
  text-decoration: none;
  font-size: 0.9375rem;
  font-weight: 500;
  border-bottom: 1px solid rgba(255, 255, 255, 0.35);
  padding-bottom: 2px;
  transition: color 0.2s, border-color 0.2s;
}

.hero-text-link:hover {
  color: #fff;
  border-bottom-color: #fff;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(18px); }
  to { opacity: 1; transform: translateY(0); }
}

.hero--animate .hero-logo-wrap { animation: fadeInUp 0.55s ease both; }
.hero--animate .hero-title { animation: fadeInUp 0.55s ease 0.08s both; }
.hero--animate .hero-subtitle { animation: fadeInUp 0.55s ease 0.14s both; }
.hero--animate .hero-meta { animation: fadeInUp 0.55s ease 0.2s both; }
.hero--animate .hero-actions { animation: fadeInUp 0.55s ease 0.28s both; }

@media (max-width: 480px) {
  .hero {
    min-height: auto;
    padding: 80px 20px 64px;
  }
  .hero-logo-wrap {
    width: 80px;
    height: 80px;
  }
}
</style>
