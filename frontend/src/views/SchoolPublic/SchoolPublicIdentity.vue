<template>
  <section
    v-if="hasFacts"
    id="identitas"
    ref="sectionRef"
    class="section identity-section reveal-section"
  >
    <div class="section-inner">
      <header class="section-header">
        <p class="section-eyebrow">Identitas</p>
        <h2 class="section-title">{{ institutionTypeLabel }}</h2>
      </header>

      <div class="identity-panel">
        <div v-if="institution?.logo_url" class="identity-logo-wrap">
          <img :src="institution.logo_url" :alt="institution.name" class="identity-logo" />
        </div>

        <dl class="identity-facts">
          <div v-if="institution?.npsn" class="fact">
            <dt>NPSN</dt>
            <dd>{{ institution.npsn }}</dd>
          </div>
          <div v-if="institution?.nss" class="fact">
            <dt>{{ nssLabel }}</dt>
            <dd>{{ institution.nss }}</dd>
          </div>
          <div v-if="institution?.level" class="fact">
            <dt>Jenjang</dt>
            <dd>{{ institution.level }}</dd>
          </div>
          <div v-if="institution?.type" class="fact">
            <dt>Tipe</dt>
            <dd>{{ institution.type }}</dd>
          </div>
          <div v-if="institution?.principal_name" class="fact">
            <dt>Kepala {{ institutionTypeLabel }}</dt>
            <dd>{{ institution.principal_name }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { getInstitutionTypeLabel, getNssLabel } from '@/utils/institution'

const props = defineProps({
  institution: { type: Object, default: null }
})

const institutionTypeLabel = computed(() =>
  getInstitutionTypeLabel(props.institution?.level) || 'Sekolah/Madrasah'
)

const nssLabel = computed(() => getNssLabel(props.institution?.level))

const hasFacts = computed(() => {
  const i = props.institution
  return !!(i?.npsn || i?.nss || i?.level || i?.type || i?.principal_name || i?.logo_url)
})

const sectionRef = ref(null)
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

.identity-section {
  background: #fff;
}

.identity-panel {
  display: flex;
  align-items: center;
  gap: 2.5rem;
  padding: 2rem 2.25rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  border-left: 4px solid #059669;
}

.identity-logo-wrap {
  width: 88px;
  height: 88px;
  flex-shrink: 0;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #fff;
}

.identity-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.identity-facts {
  margin: 0;
  flex: 1;
  min-width: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.15rem 1.5rem;
}

.fact dt {
  margin: 0 0 0.25rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #64748b;
}

.fact dd {
  margin: 0;
  font-size: 0.975rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.4;
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

@media (max-width: 640px) {
  .identity-panel {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.5rem;
    gap: 1.25rem;
  }
  .section {
    padding: 56px 20px;
  }
}
</style>
