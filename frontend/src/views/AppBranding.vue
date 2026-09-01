<template>    <div class="app-branding-page">
      <header class="page-header">
        <h1 class="page-header__title">Branding Aplikasi</h1>
        <p class="page-header__desc">
          Kelola logo, favicon, dan konten hero halaman awal. Logo sekolah masing-masing tetap dikelola di <strong>Kelola Institusi</strong>.
        </p>
      </header>

      <!-- Logo & Favicon: grid dua kolom -->
      <section class="branding-section">
        <h2 class="section-title">Logo & Favicon</h2>
        <div class="branding-grid">
          <div class="branding-card">
            <div class="branding-card__header">
              <span class="branding-card__label">Logo Aplikasi</span>
              <span class="branding-card__meta">JPG, PNG, GIF · Maks. 2MB</span>
            </div>
            <div class="branding-card__body">
              <div class="preview-wrap">
                <div class="preview-box preview-box--logo">
                  <AppLogo v-if="!uploadingLogo" :size="80" />
                  <span v-else class="preview-loading">Mengunggah…</span>
                </div>
                <input
                  ref="logoInputRef"
                  type="file"
                  accept="image/jpeg,image/png,image/gif"
                  class="input-hidden"
                  @change="onLogoSelect"
                />
                <button type="button" class="btn btn-primary btn-block" :disabled="uploadingLogo" @click="logoInputRef?.click()">
                  {{ uploadingLogo ? 'Mengunggah…' : (appBranding.appLogoUrl ? 'Ganti Logo' : 'Unggah Logo') }}
                </button>
              </div>
              <p v-if="logoError" class="error-msg">{{ logoError }}</p>
            </div>
          </div>

          <div class="branding-card">
            <div class="branding-card__header">
              <span class="branding-card__label">Favicon</span>
              <span class="branding-card__meta">ICO, PNG, SVG · Maks. 512KB</span>
            </div>
            <div class="branding-card__body">
              <div class="preview-wrap">
                <div class="preview-box preview-box--favicon">
                  <img v-if="appBranding.faviconUrl && !uploadingFavicon" :src="appBranding.faviconUrl" alt="Favicon" class="favicon-img" />
                  <span v-else-if="uploadingFavicon" class="preview-loading">Mengunggah…</span>
                  <span v-else class="preview-empty">Belum ada</span>
                </div>
                <input
                  ref="faviconInputRef"
                  type="file"
                  accept=".ico,image/png,image/svg+xml"
                  class="input-hidden"
                  @change="onFaviconSelect"
                />
                <button type="button" class="btn btn-primary btn-block" :disabled="uploadingFavicon" @click="faviconInputRef?.click()">
                  {{ uploadingFavicon ? 'Mengunggah…' : (appBranding.faviconUrl ? 'Ganti Favicon' : 'Unggah Favicon') }}
                </button>
              </div>
              <p v-if="faviconError" class="error-msg">{{ faviconError }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Hero Halaman Awal -->
      <section class="branding-section branding-section--hero">
        <h2 class="section-title">Hero Halaman Awal</h2>
        <p class="section-desc">Teks dan gambar yang tampil di bagian atas halaman depan. Kosongkan untuk memakai teks bawaan.</p>

        <div class="hero-card">
          <div class="hero-block">
            <h3 class="hero-block__title">Teks</h3>
            <div class="form-group">
              <label for="hero-headline" class="label">Judul</label>
              <input
                id="hero-headline"
                v-model="heroForm.hero_headline"
                type="text"
                class="input"
                placeholder="Contoh: Satu Platform untuk Mengelola Sekolah & Madrasah"
                maxlength="255"
              />
              <span class="input-hint">{{ heroForm.hero_headline.length }}/255</span>
            </div>
            <div class="form-group">
              <label for="hero-subheadline" class="label">Subjudul</label>
              <textarea
                id="hero-subheadline"
                v-model="heroForm.hero_subheadline"
                class="input input--textarea"
                placeholder="Deskripsi singkat di bawah judul"
                rows="3"
                maxlength="5000"
              />
              <span class="input-hint">{{ heroForm.hero_subheadline.length }}/5000</span>
            </div>
          </div>

          <div class="hero-block">
            <h3 class="hero-block__title">Gambar Hero</h3>
            <p class="hero-block__hint">Tampil di sisi kanan (desktop) atau atas (mobile). JPG, PNG, GIF · Maks. 2MB.</p>
            <div class="hero-image-row">
              <div class="preview-box preview-box--hero">
                <img v-if="appBranding.heroImageUrl && !uploadingHeroImage" :src="appBranding.heroImageUrl" alt="Hero" class="hero-preview-img" />
                <span v-else-if="uploadingHeroImage" class="preview-loading">Mengunggah…</span>
                <span v-else class="preview-empty">Belum ada gambar</span>
              </div>
              <div class="hero-image-actions">
                <input
                  ref="heroImageInputRef"
                  type="file"
                  accept="image/jpeg,image/png,image/gif"
                  class="input-hidden"
                  @change="onHeroImageSelect"
                />
                <button type="button" class="btn btn-primary" :disabled="uploadingHeroImage" @click="heroImageInputRef?.click()">
                  {{ uploadingHeroImage ? 'Mengunggah…' : (appBranding.heroImageUrl ? 'Ganti Gambar' : 'Unggah Gambar') }}
                </button>
              </div>
            </div>
            <p v-if="heroImageError" class="error-msg">{{ heroImageError }}</p>
          </div>

          <div class="hero-block hero-block--cta">
            <h3 class="hero-block__title">Tombol CTA</h3>
            <p class="hero-block__hint">Teks dan link tombol di bawah subjudul. Kosongkan untuk memakai bawaan.</p>
            <div class="form-row">
              <div class="form-group">
                <label for="hero-primary-cta-text" class="label">Tombol utama · Teks</label>
                <input id="hero-primary-cta-text" v-model="heroForm.hero_primary_cta_text" type="text" class="input" placeholder="Contoh: Lihat Contoh" maxlength="100" />
              </div>
              <div class="form-group">
                <label for="hero-primary-cta-to" class="label">Tombol utama · Link</label>
                <input id="hero-primary-cta-to" v-model="heroForm.hero_primary_cta_to" type="text" class="input" placeholder="#fitur atau /register" maxlength="255" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="hero-secondary-cta-text" class="label">Tombol kedua · Teks</label>
                <input id="hero-secondary-cta-text" v-model="heroForm.hero_secondary_cta_text" type="text" class="input" placeholder="Contoh: Daftar Gratis" maxlength="100" />
              </div>
              <div class="form-group">
                <label for="hero-secondary-cta-to" class="label">Tombol kedua · Link</label>
                <input id="hero-secondary-cta-to" v-model="heroForm.hero_secondary_cta_to" type="text" class="input" placeholder="/register" maxlength="255" />
              </div>
            </div>
          </div>

          <div class="hero-actions">
            <button type="button" class="btn btn-primary btn-save" :disabled="savingHero" @click="saveHero">
              {{ savingHero ? 'Menyimpan…' : 'Simpan Teks Hero' }}
            </button>
            <p v-if="heroSaveError" class="error-msg">{{ heroSaveError }}</p>
          </div>
        </div>
      </section>
    </div></template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAppBrandingStore } from '@/stores/appBranding'
import { appBrandingApi } from '@/api/appBranding'
import { useToast } from '@/composables/useToast'

const appBranding = useAppBrandingStore()
const toast = useToast()

const logoInputRef = ref(null)
const faviconInputRef = ref(null)
const heroImageInputRef = ref(null)
const uploadingLogo = ref(false)
const uploadingFavicon = ref(false)
const uploadingHeroImage = ref(false)
const savingHero = ref(false)
const logoError = ref('')
const faviconError = ref('')
const heroImageError = ref('')
const heroSaveError = ref('')

const heroForm = reactive({
  hero_headline: '',
  hero_subheadline: '',
  hero_primary_cta_text: '',
  hero_primary_cta_to: '',
  hero_secondary_cta_text: '',
  hero_secondary_cta_to: ''
})

onMounted(async () => {
  await appBranding.fetchBranding()
  heroForm.hero_headline = appBranding.heroHeadline ?? ''
  heroForm.hero_subheadline = appBranding.heroSubheadline ?? ''
  heroForm.hero_primary_cta_text = appBranding.heroPrimaryCtaText ?? ''
  heroForm.hero_primary_cta_to = appBranding.heroPrimaryCtaTo ?? ''
  heroForm.hero_secondary_cta_text = appBranding.heroSecondaryCtaText ?? ''
  heroForm.hero_secondary_cta_to = appBranding.heroSecondaryCtaTo ?? ''
})

async function onLogoSelect(e) {
  const file = e.target.files?.[0]
  if (!file) return
  logoError.value = ''
  uploadingLogo.value = true
  try {
    const res = await appBrandingApi.uploadLogo(file)
    const url = res.data?.data?.app_logo_url
    if (url) appBranding.setBrandingFromUpload({ appLogoUrl: url })
    toast.success(res.data?.message || 'Logo berhasil diunggah')
  } catch (err) {
    logoError.value = err.formattedMessage || err.message || 'Gagal mengunggah logo'
    toast.error('Gagal mengunggah logo', logoError.value)
  } finally {
    uploadingLogo.value = false
    if (logoInputRef.value) logoInputRef.value.value = ''
  }
}

async function onFaviconSelect(e) {
  const file = e.target.files?.[0]
  if (!file) return
  faviconError.value = ''
  uploadingFavicon.value = true
  try {
    const res = await appBrandingApi.uploadFavicon(file)
    const url = res.data?.data?.favicon_url
    if (url) appBranding.setBrandingFromUpload({ faviconUrl: url })
    toast.success(res.data?.message || 'Favicon berhasil diunggah')
  } catch (err) {
    faviconError.value = err.formattedMessage || err.message || 'Gagal mengunggah favicon'
    toast.error('Gagal mengunggah favicon', faviconError.value)
  } finally {
    uploadingFavicon.value = false
    if (faviconInputRef.value) faviconInputRef.value.value = ''
  }
}

async function onHeroImageSelect(e) {
  const file = e.target.files?.[0]
  if (!file) return
  heroImageError.value = ''
  uploadingHeroImage.value = true
  try {
    const res = await appBrandingApi.uploadHeroImage(file)
    const url = res.data?.data?.hero_image_url
    if (url) appBranding.setBrandingFromUpload({ heroImageUrl: url })
    toast.success(res.data?.message || 'Gambar hero berhasil diunggah')
  } catch (err) {
    heroImageError.value = err.formattedMessage || err.message || 'Gagal mengunggah gambar hero'
    toast.error('Gagal mengunggah gambar hero', heroImageError.value)
  } finally {
    uploadingHeroImage.value = false
    if (heroImageInputRef.value) heroImageInputRef.value.value = ''
  }
}

async function saveHero() {
  heroSaveError.value = ''
  savingHero.value = true
  try {
    const res = await appBrandingApi.updateHero({
      hero_headline: heroForm.hero_headline || null,
      hero_subheadline: heroForm.hero_subheadline || null,
      hero_primary_cta_text: heroForm.hero_primary_cta_text || null,
      hero_primary_cta_to: heroForm.hero_primary_cta_to || null,
      hero_secondary_cta_text: heroForm.hero_secondary_cta_text || null,
      hero_secondary_cta_to: heroForm.hero_secondary_cta_to || null
    })
    const data = res.data?.data
    if (data) appBranding.setHeroFromResponse(data)
    toast.success(res.data?.message || 'Hero berhasil disimpan')
  } catch (err) {
    heroSaveError.value = err.formattedMessage || err.message || 'Gagal menyimpan hero'
    toast.error('Gagal menyimpan pengaturan hero', heroSaveError.value)
  } finally {
    savingHero.value = false
  }
}
</script>

<style scoped>
.app-branding-page {
  max-width: 720px;
  padding-bottom: 2rem;
}

/* ----- Page header ----- */
.page-header {
  margin-bottom: 2rem;
}

.page-header__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.page-header__desc {
  font-size: 0.9375rem;
  color: var(--text-secondary);
  line-height: 1.55;
  margin: 0;
}

/* ----- Section ----- */
.branding-section {
  margin-bottom: 2.5rem;
}

.branding-section--hero {
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.section-desc {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0 0 1.25rem 0;
  line-height: 1.5;
}

/* ----- Logo & Favicon grid ----- */
.branding-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.branding-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.branding-card__header {
  padding: 1rem 1.25rem;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.branding-card__label {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--text-primary);
}

.branding-card__meta {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.branding-card__body {
  padding: 1.25rem;
}

.preview-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.preview-box {
  border: 1px dashed var(--border-color);
  border-radius: var(--radius-sm);
  background: var(--bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.preview-box--logo {
  width: 120px;
  height: 120px;
}

.preview-box--favicon {
  width: 64px;
  height: 64px;
}

.preview-box--hero {
  width: 100%;
  max-width: 220px;
  aspect-ratio: 16 / 10;
  min-height: 100px;
}

.favicon-img {
  max-width: 40px;
  max-height: 40px;
  object-fit: contain;
}

.hero-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-empty,
.preview-loading {
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.input-hidden {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  font-size: 0.9375rem;
  font-weight: 600;
  border-radius: var(--radius-sm);
  border: none;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-primary {
  background: var(--primary-gradient);
  color: #fff;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}

.btn-primary:hover:not(:disabled) {
  filter: brightness(1.05);
  transform: translateY(-1px);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-block {
  width: 100%;
  min-height: 40px;
}

/* ----- Hero card ----- */
.hero-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 1.5rem 1.5rem 1.25rem;
}

.hero-block {
  margin-bottom: 1.5rem;
}

.hero-block:last-of-type {
  margin-bottom: 0;
}

.hero-block__title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 0.75rem 0;
}

.hero-block__hint {
  font-size: 0.8125rem;
  color: var(--text-muted);
  margin: 0 0 0.75rem 0;
  line-height: 1.4;
}

.hero-block--cta .hero-block__title {
  margin-top: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 0.375rem;
}

.input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.9375rem;
  color: var(--text-primary);
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.input::placeholder {
  color: var(--text-muted);
}

.input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.input--textarea {
  resize: vertical;
  min-height: 5rem;
}

.input-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.hero-image-row {
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.hero-image-actions {
  display: flex;
  align-items: center;
}

.hero-actions {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border-color);
}

.btn-save {
  min-height: 44px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
}

.error-msg {
  margin-top: 0.5rem;
  font-size: 0.8125rem;
  color: var(--danger-color);
}

/* ----- Responsive ----- */
@media (max-width: 900px) {
  .branding-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .branding-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .hero-image-row {
    flex-direction: column;
  }

  .hero-card {
    padding: 1.25rem;
  }

  .page-header,
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-save {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header__title {
    font-size: 1.25rem;
  }
}
</style>
