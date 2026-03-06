<template>
  <section ref="heroRef" id="main-content" class="hero hero--animate" tabindex="-1">
    <div class="hero-bg">
      <img v-if="institution?.cover_image_url" :src="institution.cover_image_url" alt="" class="hero-cover-img" />
      <div class="hero-pattern" aria-hidden="true"></div>
    </div>
    <div class="hero-content">
      <h1 class="hero-title">{{ institution?.name }}</h1>
      <p v-if="institution?.address" class="hero-subtitle">{{ institution.address }}</p>
      <div v-if="institution?.npsn || institution?.level || institution?.type" class="hero-meta">
        <span v-if="institution.npsn" class="hero-meta-item">NPSN {{ institution.npsn }}</span>
        <span v-if="institution.level" class="hero-meta-item">{{ institution.level }}</span>
        <span v-if="institution.type" class="hero-meta-item">{{ institution.type }}</span>
      </div>
      <div class="hero-actions">
        <router-link :to="`/${npsn}/daftar-ppdb`" class="btn btn-primary btn-lg btn-cta">Daftar PPDB</router-link>
        <router-link :to="{ path: '/cek-hasil-ppdb', query: { npsn } }" class="btn btn-secondary btn-lg">Cek Hasil PPDB</router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  institution: { type: Object, default: null },
  npsn: { type: String, default: '' }
})

const heroRef = ref(null)
defineExpose({ heroRef })
</script>

<style scoped>
.hero {
  position: relative;
  padding: 88px 24px 100px;
  text-align: center;
  z-index: 1;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, #ecfdf5 0%, #d1fae5 35%, #f0fdf4 70%, #f8fafc 100%);
  z-index: 0;
}
.hero-cover-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
  opacity: 0.35;
}
.hero-bg .hero-pattern { z-index: 1; }
.hero:has(.hero-cover-img) .hero-content { text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.hero:has(.hero-cover-img) .hero-title { color: #0f172a; }
.hero:has(.hero-cover-img) .hero-subtitle { color: #334155; }
.hero:has(.hero-cover-img) .hero-meta-item { background: rgba(255,255,255,0.9); }
.hero-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(5, 150, 105, 0.06) 1px, transparent 0);
  background-size: 24px 24px;
  pointer-events: none;
}
.hero-content { position: relative; z-index: 1; max-width: 760px; margin: 0 auto; }
.hero-title {
  font-size: clamp(28px, 5vw, 42px);
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 14px;
  letter-spacing: -0.03em;
  line-height: 1.15;
}
.hero-subtitle { font-size: 1.0625rem; color: #475569; margin-bottom: 10px; line-height: 1.5; }
.hero-meta { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 32px; }
.hero-meta-item {
  font-size: 0.8125rem;
  color: #475569;
  padding: 6px 14px;
  background: rgba(255,255,255,0.8);
  border: 1px solid rgba(5, 150, 105, 0.2);
  border-radius: 999px;
  font-weight: 500;
}
.hero-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; position: relative; align-items: center; }
.btn { padding: 10px 20px; min-height: 44px; border-radius: 10px; font-size: 14px; font-weight: 500; text-decoration: none; transition: all 0.25s ease; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
.btn-primary { background: linear-gradient(145deg, #059669 0%, #047857 100%); color: white; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); }
.btn-primary:hover { background: linear-gradient(145deg, #047857 0%, #065f46 100%); box-shadow: 0 8px 24px rgba(5, 150, 105, 0.4); transform: translateY(-2px); }
.btn-secondary { background: #fff; color: #059669; border: 2px solid #059669; }
.btn-secondary:hover { background: #ecfdf5; border-color: #047857; color: #047857; transform: translateY(-2px); }
.btn-lg { padding: 14px 26px; font-size: 0.9375rem; border-radius: 12px; }
.btn-cta { padding: 16px 32px; font-size: 1.0625rem; font-weight: 600; }
.share-copied { position: absolute; left: 50%; bottom: -1.75rem; transform: translateX(-50%); font-size: 0.8125rem; color: #059669; font-weight: 600; white-space: nowrap; animation: fadeInUp 0.3s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.hero--animate .hero-title { animation: fadeInUp 0.6s ease both; }
.hero--animate .hero-subtitle { animation: fadeInUp 0.6s ease 0.1s both; }
.hero--animate .hero-meta { animation: fadeInUp 0.6s ease 0.2s both; }
.hero--animate .hero-actions { animation: fadeInUp 0.6s ease 0.3s both; }
</style>
