<template>
  <section ref="sectionRef" class="section profile-section reveal-section">
    <div class="section-inner">
      <h2 class="section-title"><span class="section-title-text">Profil {{ institutionTypeLabel }}</span></h2>
      <div class="profile-card card card--hover">
        <p class="profile-desc" :class="{ 'profile-desc--muted': !descriptionText }">{{ descriptionText || 'Deskripsi belum diisi.' }}</p>
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

const institutionTypeLabel = computed(() =>
  getInstitutionTypeLabel(props.institution?.level) || 'Sekolah/Madrasah'
)

const descriptionText = computed(() => {
  const d = props.institution?.description
  return d != null && String(d).trim() !== '' ? String(d).trim() : ''
})

const sectionRef = ref(null)
defineExpose({ sectionRef })
</script>

<style scoped>
.section { padding: 56px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 24px; letter-spacing: -0.02em; }
.section-title-text { display: inline-block; padding-bottom: 8px; border-bottom: 3px solid #059669; border-radius: 0 0 2px 0; }
.profile-section { background: #f8fafc; }
.card { background: #fff; border-radius: 16px; padding: 2.25rem 2rem; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04); border: 1px solid rgba(226, 232, 240, 0.8); transition: transform 0.3s ease, box-shadow 0.3s ease; }
.card--hover:hover { transform: translateY(-4px); box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.12); }
.profile-desc { color: #475569; line-height: 1.75; margin: 0; font-size: 0.9375rem; }
.profile-desc--muted { color: #94a3b8; font-style: italic; }
.reveal-section .section-title,
.reveal-section .card {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible .section-title { opacity: 1; transform: translateY(0); transition-delay: 0.1s; }
.reveal-section.is-visible .card { opacity: 1; transform: translateY(0); transition-delay: 0.2s; }
</style>
