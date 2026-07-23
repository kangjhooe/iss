<template>
  <section
    v-if="hasSection"
    id="kontak"
    ref="sectionRef"
    class="section contact-section reveal-section"
  >
    <div class="section-inner">
      <header class="section-header">
        <p class="section-eyebrow">Lokasi & Kontak</p>
        <h2 class="section-title">Hubungi kami</h2>
      </header>

      <div class="contact-layout" :class="{ 'contact-layout--map-only': mapEmbedUrl && !hasAnyContact && !addressText }">
        <div v-if="mapEmbedUrl" class="map-col">
          <div class="map-embed-wrap">
            <iframe
              :src="mapEmbedUrl"
              class="map-iframe"
              title="Peta lokasi sekolah"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            />
          </div>
          <a
            v-if="mapsExternalUrl"
            :href="mapsExternalUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="maps-btn"
          >
            Buka di Google Maps
          </a>
        </div>

        <div class="info-col">
          <div v-if="addressText" class="info-block">
            <h3 class="info-heading">Alamat</h3>
            <p class="info-text">{{ addressText }}</p>
          </div>

          <div v-if="hasAnyContact" class="info-block">
            <h3 class="info-heading">Kontak</h3>
            <ul class="contact-list">
              <li v-if="institution?.phone">
                <a :href="'tel:' + institution.phone" class="contact-link">
                  <span class="contact-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <span>{{ institution.phone }}</span>
                </a>
              </li>
              <li v-if="institution?.email">
                <a :href="'mailto:' + institution.email" class="contact-link">
                  <span class="contact-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                  </span>
                  <span>{{ institution.email }}</span>
                </a>
              </li>
              <li v-if="institution?.website">
                <a :href="institution.website" target="_blank" rel="noopener" class="contact-link">
                  <span class="contact-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  </span>
                  <span class="contact-link-ellipsis">{{ institution.website }}</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  institution: { type: Object, default: null }
})

const sectionRef = ref(null)

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

const addressText = computed(() => {
  const i = props.institution
  if (!i) return ''
  if (i.address && String(i.address).trim()) return String(i.address).trim()
  const parts = [i.address_line, i.village, i.sub_district, i.district, i.province]
    .map((p) => (p != null ? String(p).trim() : ''))
    .filter(Boolean)
  if (i.postal_code) parts.push(String(i.postal_code))
  return parts.join(', ')
})

const hasAnyContact = computed(() => {
  const i = props.institution
  return !!(i?.phone || i?.email || i?.website)
})

const mapEmbedUrl = computed(() => {
  if (hasCoordinates.value) {
    return `https://www.google.com/maps?q=${lat.value},${lng.value}&z=16&output=embed`
  }
  if (addressText.value) {
    return `https://www.google.com/maps?q=${encodeURIComponent(addressText.value)}&output=embed`
  }
  return ''
})

const mapsExternalUrl = computed(() => {
  if (hasCoordinates.value) {
    return `https://www.google.com/maps?q=${lat.value},${lng.value}`
  }
  if (addressText.value) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addressText.value)}`
  }
  return ''
})

const hasSection = computed(() => !!(mapEmbedUrl.value || addressText.value || hasAnyContact.value))

defineExpose({ sectionRef })
</script>

<style scoped>
.section {
  padding: 72px 24px;
  position: relative;
  z-index: 1;
}

.section-inner {
  max-width: 1000px;
  margin: 0 auto;
}

.section-header {
  margin-bottom: 2rem;
  text-align: center;
}

.section-eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #059669;
}

.section-title {
  margin: 0;
  font-size: clamp(1.5rem, 3vw, 1.875rem);
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.contact-section {
  background: #f8fafc;
}

.contact-layout {
  display: grid;
  grid-template-columns: 1.35fr 1fr;
  gap: 2rem;
  align-items: start;
}

.contact-layout--map-only {
  grid-template-columns: 1fr;
  max-width: 720px;
  margin: 0 auto;
}

.map-embed-wrap {
  width: 100%;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  aspect-ratio: 16 / 11;
  min-height: 260px;
  background: #e2e8f0;
}

.map-iframe {
  width: 100%;
  height: 100%;
  border: 0;
  display: block;
  min-height: 260px;
}

.maps-btn {
  display: inline-flex;
  margin-top: 0.85rem;
  padding: 0.55rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #047857;
  background: #fff;
  border: 1px solid rgba(5, 150, 105, 0.35);
  border-radius: 10px;
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s;
}

.maps-btn:hover {
  background: #ecfdf5;
  border-color: #059669;
}

.info-col {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

.info-heading {
  margin: 0 0 0.55rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.info-text {
  margin: 0;
  font-size: 0.975rem;
  line-height: 1.65;
  color: #334155;
}

.contact-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.contact-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
  color: #0f172a;
  font-size: 0.95rem;
  font-weight: 500;
  transition: color 0.2s;
  min-width: 0;
}

.contact-link:hover {
  color: #047857;
}

.contact-icon {
  display: inline-flex;
  flex-shrink: 0;
  color: #059669;
}

.contact-link-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.reveal-section {
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.55s ease, transform 0.55s ease;
}

.reveal-section.is-visible {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 800px) {
  .contact-layout {
    grid-template-columns: 1fr;
  }
  .section {
    padding: 56px 20px;
  }
}
</style>
