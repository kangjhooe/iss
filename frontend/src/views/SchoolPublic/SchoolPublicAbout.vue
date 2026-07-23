<template>
  <section
    v-if="hasContent"
    id="tentang"
    ref="sectionRef"
    class="section about-section reveal-section"
  >
    <div class="section-inner">
      <header class="section-header">
        <p class="section-eyebrow">Tentang</p>
        <h2 class="section-title">{{ institutionTypeLabel }}</h2>
      </header>

      <div class="about-grid" :class="{ 'about-grid--single': !hasVisionBlock || !descriptionText }">
        <div v-if="descriptionText" class="about-block">
          <h3 class="about-heading">Profil</h3>
          <p class="about-text">{{ descriptionText }}</p>
        </div>

        <div v-if="hasVisionBlock" class="about-block about-block--vision">
          <div v-if="visionText" class="vision-item">
            <h3 class="about-heading">Visi</h3>
            <p class="about-text">{{ visionText }}</p>
          </div>
          <div v-if="missionLines.length" class="vision-item">
            <h3 class="about-heading">Misi</h3>
            <ol v-if="missionLines.length > 1" class="mission-list">
              <li v-for="(line, idx) in missionLines" :key="idx">{{ line }}</li>
            </ol>
            <p v-else class="about-text">{{ missionLines[0] }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { getInstitutionTypeLabel } from '@/utils/institution'

const props = defineProps({
  institution: { type: Object, default: null }
})

const sectionRef = ref(null)

const institutionTypeLabel = computed(() =>
  getInstitutionTypeLabel(props.institution?.level) || 'Sekolah/Madrasah'
)

const descriptionText = computed(() => {
  const d = props.institution?.description
  return d != null && String(d).trim() !== '' ? String(d).trim() : ''
})

const visionText = computed(() => {
  const v = props.institution?.vision
  return v != null && String(v).trim() !== '' ? String(v).trim() : ''
})

const missionLines = computed(() => {
  const m = props.institution?.mission
  if (m == null || String(m).trim() === '') return []
  return String(m)
    .split(/\n+/)
    .map((l) => l.replace(/^[\s\d.\-•*)]+/, '').trim())
    .filter(Boolean)
})

const hasVisionBlock = computed(() => !!(visionText.value || missionLines.value.length))

const hasContent = computed(() => !!(descriptionText.value || hasVisionBlock.value))

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

.about-section {
  background: #fff;
}

.about-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2.5rem 3rem;
  align-items: start;
}

.about-grid--single {
  grid-template-columns: 1fr;
  max-width: 720px;
  margin: 0 auto;
}

.about-heading {
  margin: 0 0 0.65rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #059669;
}

.about-text {
  margin: 0;
  font-size: 1rem;
  line-height: 1.75;
  color: #334155;
  white-space: pre-line;
}

.about-block--vision {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

.mission-list {
  margin: 0;
  padding-left: 1.25rem;
  color: #334155;
  font-size: 1rem;
  line-height: 1.7;
}

.mission-list li + li {
  margin-top: 0.5rem;
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

@media (max-width: 768px) {
  .about-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .section {
    padding: 56px 20px;
  }
}
</style>
