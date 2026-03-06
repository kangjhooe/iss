<template>
  <section id="kontak" ref="sectionRef" class="section contact-section reveal-section">
    <div class="section-inner">
      <h2 class="section-title"><span class="section-title-text">Lokasi</span></h2>

      <!-- Peta embed: tampil dari koordinat atau dari alamat -->
      <div v-if="mapEmbedUrl" class="map-wrap">
        <div class="map-embed-wrap">
          <iframe
            :src="mapEmbedUrl"
            class="map-iframe"
            title="Peta lokasi sekolah"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          />
        </div>
        <p v-if="institution?.address" class="contact-address">{{ institution.address }}</p>
      </div>

      <!-- Tanpa koordinat dan tanpa alamat -->
      <template v-else>
        <p class="contact-address contact-address--muted">Alamat belum diisi.</p>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  institution: { type: Object, default: null }
})

const sectionRef = ref(null)

// Koordinat hanya dari data institusi (tidak ada hardcode)
const lat = computed(() => {
  const v = props.institution?.latitude
  if (v === null || v === undefined) return null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
})
const lng = computed(() => {
  const v = props.institution?.longitude
  if (v === null || v === undefined) return null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
})

const hasCoordinates = computed(() => lat.value != null && lng.value != null)

// Embed peta: prioritas koordinat dari data; fallback alamat hanya jika koordinat tidak ada
const mapEmbedUrl = computed(() => {
  if (hasCoordinates.value) {
    return `https://www.google.com/maps?q=${lat.value},${lng.value}&z=16&output=embed`
  }
  const addr = props.institution?.address
  if (addr && String(addr).trim()) {
    return `https://www.google.com/maps?q=${encodeURIComponent(String(addr).trim())}&output=embed`
  }
  return ''
})

defineExpose({ sectionRef })
</script>

<style scoped>
.section { padding: 56px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 24px; letter-spacing: -0.02em; }
.section-title-text { display: inline-block; padding-bottom: 8px; border-bottom: 3px solid #059669; border-radius: 0 0 2px 0; }
.contact-section { background: #f8fafc; }
.map-wrap { text-align: center; }
.map-embed-wrap {
  width: 100%;
  max-width: 680px;
  margin: 0 auto 1.25rem;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
  aspect-ratio: 16/10;
  min-height: 280px;
}
.map-iframe { width: 100%; height: 100%; border: 0; display: block; min-height: 280px; }
.contact-address { text-align: center; color: #475569; margin: 0 0 0.5rem; font-size: 0.9375rem; line-height: 1.6; }
.contact-address--muted { color: #94a3b8; }
.card { border-radius: 16px; }
.card--hover { transition: transform 0.25s ease, box-shadow 0.25s ease; }
.reveal-section .section-title,
.reveal-section .card {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible .section-title { opacity: 1; transform: translateY(0); transition-delay: 0.1s; }
.reveal-section.is-visible .card { opacity: 1; transform: translateY(0); transition-delay: 0.2s; }
</style>
