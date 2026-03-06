<template>
  <section ref="sectionRef" class="section vision-section reveal-section">
    <div class="section-inner">
      <h2 class="section-title"><span class="section-title-text">Visi & Misi</span></h2>
      <div class="vision-card card card--hover">
        <div class="vision-block">
          <h3 class="vision-heading">Visi</h3>
          <p class="vision-text" :class="{ 'vision-text--empty': !visionText }">{{ visionText || 'Visi belum diisi.' }}</p>
        </div>
        <div class="vision-divider" aria-hidden="true"></div>
        <div class="vision-block">
          <h3 class="vision-heading">Misi</h3>
          <p class="vision-text" :class="{ 'vision-text--empty': !missionText }">{{ missionText || 'Misi belum diisi.' }}</p>
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

// Pakai computed agar reaktif terhadap perubahan institution dari API
const visionText = computed(() => {
  const v = props.institution?.vision
  return v != null && String(v).trim() !== '' ? String(v).trim() : ''
})
const missionText = computed(() => {
  const v = props.institution?.mission
  return v != null && String(v).trim() !== '' ? String(v).trim() : ''
})

defineExpose({ sectionRef })
</script>

<style scoped>
.section { padding: 56px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 24px; letter-spacing: -0.02em; }
.section-title-text { display: inline-block; padding-bottom: 8px; border-bottom: 3px solid #059669; border-radius: 0 0 2px 0; }
.vision-section { background: #fff; }
.card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04); border: 1px solid rgba(226, 232, 240, 0.8); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.2s ease; }
.card--hover:hover { transform: translateY(-4px); box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.12), 0 12px 24px -8px rgba(5, 150, 105, 0.12); border-color: rgba(5, 150, 105, 0.15); }
.vision-card { padding: 2rem; }
.vision-block { margin: 0; }
.vision-heading { font-size: 0.875rem; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem 0; }
.vision-text { font-size: 0.9375rem; color: #475569; line-height: 1.7; margin: 0; white-space: pre-line; }
.vision-text--empty { color: #94a3b8; font-style: italic; }
.vision-divider { height: 1px; background: #e2e8f0; margin: 1.25rem 0; }
.reveal-section .section-title,
.reveal-section .card {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible .section-title { opacity: 1; transform: translateY(0); transition-delay: 0.1s; }
.reveal-section.is-visible .card { opacity: 1; transform: translateY(0); transition-delay: 0.2s; }
</style>
