<template>
  <section ref="sectionRef" class="section identity-section reveal-section">
    <div class="section-inner identity-inner">
      <h2 class="section-title"><span class="section-title-text">Identitas {{ institutionTypeLabel }}</span></h2>
      <div class="identity-card card card--hover card--accent">
        <div class="identity-block identity-block--main">
          <div v-if="institution?.logo_url" class="identity-logo-wrap">
            <img :src="institution.logo_url" :alt="institution.name" class="identity-logo" />
          </div>
          <div class="identity-data">
            <div v-if="institution?.npsn" class="identity-item">
              <span class="identity-label">NPSN</span>
              <span class="identity-value">{{ institution.npsn }}</span>
            </div>
            <div v-if="institution?.nss" class="identity-item">
              <span class="identity-label">NSS</span>
              <span class="identity-value">{{ institution.nss }}</span>
            </div>
            <div v-if="institution?.level" class="identity-item">
              <span class="identity-label">Jenjang</span>
              <span class="identity-value">{{ institution.level }}</span>
            </div>
            <div v-if="institution?.type" class="identity-item">
              <span class="identity-label">Tipe</span>
              <span class="identity-value">{{ institution.type }}</span>
            </div>
            <div v-if="institution?.principal_name" class="identity-item">
              <span class="identity-label">Kepala {{ institutionTypeLabel }}</span>
              <span class="identity-value">{{ institution.principal_name }}</span>
            </div>
          </div>
        </div>
        <div class="identity-divider" aria-hidden="true"></div>
        <div class="identity-block identity-block--address">
          <h3 class="identity-heading">Alamat</h3>
          <p v-if="institution?.address" class="identity-address-text">{{ institution.address }}</p>
          <template v-else-if="hasAddressParts">
            <p v-if="institution?.address_line" class="identity-address-line">{{ institution.address_line }}</p>
            <p v-if="institution?.village" class="identity-address-line">{{ institution.village }}</p>
            <p v-if="institution?.sub_district" class="identity-address-line">{{ institution.sub_district }}{{ institution.district ? ', ' + institution.district : '' }}</p>
            <p v-if="institution?.province" class="identity-address-line">{{ institution.province }}{{ institution.postal_code ? ' ' + institution.postal_code : '' }}</p>
          </template>
          <p v-else class="identity-empty">Alamat belum diisi.</p>
        </div>
        <div class="identity-divider" aria-hidden="true"></div>
        <div class="identity-block identity-block--contact">
          <h3 class="identity-heading">Kontak</h3>
          <div class="identity-contact-list">
            <a v-if="institution?.phone" :href="'tel:' + institution.phone" class="identity-contact-link">
              <span class="identity-contact-label">Telepon</span>
              <span class="identity-contact-value">{{ institution.phone }}</span>
            </a>
            <a v-if="institution?.email" :href="'mailto:' + institution.email" class="identity-contact-link">
              <span class="identity-contact-label">Email</span>
              <span class="identity-contact-value">{{ institution.email }}</span>
            </a>
            <a v-if="institution?.website" :href="institution.website" target="_blank" rel="noopener" class="identity-contact-link">
              <span class="identity-contact-label">Website</span>
              <span class="identity-contact-value identity-contact-value--ellipsis">{{ institution.website }}</span>
            </a>
          </div>
          <p v-if="!hasAnyContact" class="identity-empty">Tidak ada data kontak.</p>
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

const institutionTypeLabel = computed(() =>
  getInstitutionTypeLabel(props.institution?.level) || 'Sekolah/Madrasah'
)

const hasAnyContact = computed(() => {
  const i = props.institution
  return !!(i?.phone || i?.email || i?.website)
})

const hasAddressParts = computed(() => {
  const i = props.institution
  return !!(i?.address_line || i?.village || i?.sub_district || i?.province)
})

const sectionRef = ref(null)
defineExpose({ sectionRef })
</script>

<style scoped>
.section { padding: 56px 24px; position: relative; z-index: 1; }
.section-inner { max-width: 720px; margin: 0 auto; }
.section-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; text-align: center; margin-bottom: 24px; letter-spacing: -0.02em; }
.section-title-text { display: inline-block; padding-bottom: 8px; border-bottom: 3px solid #059669; border-radius: 0 0 2px 0; }
.identity-section { background: #fff; }
.identity-inner { max-width: 720px; }
.card { background: #fff; border-radius: 16px; padding: 2rem 1.75rem; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04); border: 1px solid rgba(226, 232, 240, 0.8); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.2s ease; }
.card--hover:hover { transform: translateY(-4px); box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.12), 0 12px 24px -8px rgba(5, 150, 105, 0.12); border-color: rgba(5, 150, 105, 0.15); }
.card--accent { border-left: 4px solid #059669; }
.identity-card { padding: 0; overflow: hidden; border-radius: 16px; }
.identity-block { padding: 1.75rem 2rem; }
.identity-block--main {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}
.identity-logo-wrap {
  width: 100px;
  height: 100px;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  flex-shrink: 0;
  background: #f8fafc;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.identity-logo { width: 100%; height: 100%; object-fit: contain; }
.identity-data {
  width: 100%;
  min-width: 0;
  display: grid;
  grid-template-columns: minmax(10rem, auto) 1fr;
  gap: 0.5rem 1rem;
  align-items: start;
}
.identity-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.identity-value { font-size: 0.9375rem; color: #0f172a; font-weight: 600; }
.identity-item { display: contents; }
.identity-divider { height: 1px; background: linear-gradient(90deg, transparent, #e2e8f0 15%, #e2e8f0 85%, transparent); margin: 0 2rem; }
.identity-heading { font-size: 0.7rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 0.6rem 0; }
.identity-address-text, .identity-address-line { margin: 0; font-size: 0.9375rem; color: #334155; line-height: 1.65; }
.identity-address-line + .identity-address-line { margin-top: 0.3rem; }
.identity-contact-list { display: flex; flex-direction: column; gap: 0.5rem; }
.identity-contact-link { display: flex; align-items: baseline; gap: 0.75rem; flex-wrap: wrap; text-decoration: none; color: inherit; transition: color 0.2s; }
.identity-contact-link:hover { color: #047857; }
.identity-contact-label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; min-width: 5rem; flex-shrink: 0; }
.identity-contact-value { font-size: 0.9375rem; color: #047857; font-weight: 600; }
.identity-contact-value--ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.identity-empty { margin: 0; font-size: 0.875rem; color: #94a3b8; }
.reveal-section .section-title,
.reveal-section .card {
  opacity: 1;
  transform: translateY(0);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.reveal-section.is-visible .section-title {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.1s;
}
.reveal-section.is-visible .card {
  opacity: 1;
  transform: translateY(0);
  transition-delay: 0.2s;
}
</style>
