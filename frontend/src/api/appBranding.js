import api from './index'

export const appBrandingApi = {
  /**
   * Ambil branding aplikasi (logo & favicon URL). Public, tidak butuh auth.
   */
  getBranding() {
    return api.get('/v1/app-branding')
  },

  /**
   * Upload logo aplikasi. Hanya super admin.
   */
  uploadLogo(file) {
    const formData = new FormData()
    formData.append('logo', file)
    return api.post('/v1/app-branding/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  /**
   * Upload favicon. Hanya super admin.
   */
  uploadFavicon(file) {
    const formData = new FormData()
    formData.append('favicon', file)
    return api.post('/v1/app-branding/favicon', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  /**
   * Update teks hero halaman awal. Hanya super admin.
   */
  updateHero(payload) {
    return api.put('/v1/app-branding/hero', payload)
  },

  /**
   * Update maintenance mode. Hanya super admin.
   */
  updateMaintenance(payload) {
    return api.put('/v1/app-branding/maintenance', payload)
  },

  /**
   * Upload gambar hero halaman awal. Hanya super admin.
   */
  uploadHeroImage(file) {
    const formData = new FormData()
    formData.append('hero_image', file)
    return api.post('/v1/app-branding/hero-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }
}
