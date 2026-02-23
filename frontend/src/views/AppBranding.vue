<template>
  <Layout>
    <div class="app-branding-page">
      <div class="page-header">
        <h1>Branding Aplikasi</h1>
        <p>Logo dan favicon ini tampil di halaman awal, login, register, serta sidebar. <strong>Logo sekolah masing-masing tetap dikelola di Kelola Institusi dan tidak berubah.</strong></p>
      </div>

      <div class="branding-cards">
        <!-- Logo Aplikasi -->
        <div class="branding-card">
          <h2>Logo Aplikasi</h2>
          <p class="card-desc">Tampil di beranda, login, register, dan sidebar. Format: JPG, PNG, atau GIF. Maks. 2MB.</p>
          <div class="preview-row">
            <div class="preview-box">
              <AppLogo v-if="!uploadingLogo" :size="120" />
              <div v-else class="preview-loading">Mengunggah...</div>
            </div>
            <div class="upload-actions">
              <input
                ref="logoInputRef"
                type="file"
                accept="image/jpeg,image/png,image/gif"
                class="input-hidden"
                @change="onLogoSelect"
              />
              <button type="button" class="btn btn-primary" :disabled="uploadingLogo" @click="logoInputRef?.click()">
                {{ uploadingLogo ? 'Mengunggah...' : (appBranding.appLogoUrl ? 'Ganti Logo' : 'Unggah Logo') }}
              </button>
            </div>
          </div>
          <p v-if="logoError" class="error-text">{{ logoError }}</p>
        </div>

        <!-- Favicon -->
        <div class="branding-card">
          <h2>Favicon</h2>
          <p class="card-desc">Ikon di tab browser. Format: ICO, PNG, atau SVG. Maks. 512KB.</p>
          <div class="preview-row">
            <div class="preview-box preview-box-favicon">
              <img v-if="appBranding.faviconUrl && !uploadingFavicon" :src="appBranding.faviconUrl" alt="Favicon" class="favicon-preview" />
              <span v-else-if="uploadingFavicon" class="preview-loading">Mengunggah...</span>
              <span v-else class="preview-placeholder">Belum ada favicon</span>
            </div>
            <div class="upload-actions">
              <input
                ref="faviconInputRef"
                type="file"
                accept=".ico,image/png,image/svg+xml"
                class="input-hidden"
                @change="onFaviconSelect"
              />
              <button type="button" class="btn btn-primary" :disabled="uploadingFavicon" @click="faviconInputRef?.click()">
                {{ uploadingFavicon ? 'Mengunggah...' : (appBranding.faviconUrl ? 'Ganti Favicon' : 'Unggah Favicon') }}
              </button>
            </div>
          </div>
          <p v-if="faviconError" class="error-text">{{ faviconError }}</p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import AppLogo from '@/components/AppLogo.vue'
import { useAppBrandingStore } from '@/stores/appBranding'
import { appBrandingApi } from '@/api/appBranding'
import { useToast } from '@/composables/useToast'

const appBranding = useAppBrandingStore()
const toast = useToast()

const logoInputRef = ref(null)
const faviconInputRef = ref(null)
const uploadingLogo = ref(false)
const uploadingFavicon = ref(false)
const logoError = ref('')
const faviconError = ref('')

onMounted(() => {
  appBranding.fetchBranding()
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
    toast.error(logoError.value)
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
    toast.error(faviconError.value)
  } finally {
    uploadingFavicon.value = false
    if (faviconInputRef.value) faviconInputRef.value.value = ''
  }
}
</script>

<style scoped>
.app-branding-page {
  max-width: 800px;
}

.page-header {
  margin-bottom: 32px;
}

.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.page-header p {
  font-size: 15px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
}

.branding-cards {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.branding-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 24px;
}

.branding-card h2 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.card-desc {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.preview-row {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.preview-box {
  width: 120px;
  height: 120px;
  border: 1px dashed var(--border-color);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-secondary);
}

.preview-box-favicon {
  width: 64px;
  height: 64px;
}

.favicon-preview {
  max-width: 48px;
  max-height: 48px;
  object-fit: contain;
}

.preview-placeholder,
.preview-loading {
  font-size: 13px;
  color: var(--text-muted);
}

.upload-actions .btn {
  min-height: 44px;
}

.error-text {
  margin-top: 8px;
  font-size: 14px;
  color: var(--danger-color);
}

@media (max-width: 600px) {
  .preview-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
