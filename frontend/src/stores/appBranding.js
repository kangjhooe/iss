import { defineStore } from 'pinia'
import { appBrandingApi } from '@/api/appBranding'

export const useAppBrandingStore = defineStore('appBranding', {
  state: () => ({
    appLogoUrl: null,
    faviconUrl: null,
    heroHeadline: null,
    heroSubheadline: null,
    heroImageUrl: null,
    heroPrimaryCtaText: null,
    heroPrimaryCtaTo: null,
    heroSecondaryCtaText: null,
    heroSecondaryCtaTo: null,
    maintenanceMode: false,
    maintenanceMessage: null,
    loaded: false
  }),

  getters: {
    hasAppLogo: (state) => !!state.appLogoUrl,
    hasFavicon: (state) => !!state.faviconUrl,
    hasHeroImage: (state) => !!state.heroImageUrl,
    isMaintenanceMode: (state) => !!state.maintenanceMode
  },

  actions: {
    async fetchBranding() {
      if (this.loaded) return
      try {
        const res = await appBrandingApi.getBranding()
        const data = res.data?.data || {}
        this.appLogoUrl = data.app_logo_url || null
        this.faviconUrl = data.favicon_url || null
        this.heroHeadline = data.hero_headline ?? null
        this.heroSubheadline = data.hero_subheadline ?? null
        this.heroImageUrl = data.hero_image_url ?? null
        this.heroPrimaryCtaText = data.hero_primary_cta_text ?? null
        this.heroPrimaryCtaTo = data.hero_primary_cta_to ?? null
        this.heroSecondaryCtaText = data.hero_secondary_cta_text ?? null
        this.heroSecondaryCtaTo = data.hero_secondary_cta_to ?? null
        this.maintenanceMode = !!data.maintenance_mode
        this.maintenanceMessage = data.maintenance_message || null
        this.loaded = true
        this.applyFavicon()
      } catch {
        this.loaded = true
      }
    },

    async refreshBranding() {
      this.loaded = false
      await this.fetchBranding()
    },

    setMaintenance(data) {
      if (data.maintenance_mode !== undefined) this.maintenanceMode = !!data.maintenance_mode
      if (data.maintenance_message !== undefined) this.maintenanceMessage = data.maintenance_message
    },

    applyFavicon() {
      if (typeof document === 'undefined' || !this.faviconUrl) return
      let link = document.querySelector('link[rel="icon"]')
      if (!link) {
        link = document.createElement('link')
        link.rel = 'icon'
        document.head.appendChild(link)
      }
      link.href = this.faviconUrl
    },

    setBrandingFromUpload({ appLogoUrl, faviconUrl, heroImageUrl }) {
      if (appLogoUrl !== undefined) this.appLogoUrl = appLogoUrl
      if (faviconUrl !== undefined) {
        this.faviconUrl = faviconUrl
        this.applyFavicon()
      }
      if (heroImageUrl !== undefined) this.heroImageUrl = heroImageUrl
    },

    setHeroFromResponse(data) {
      if (data.hero_headline !== undefined) this.heroHeadline = data.hero_headline
      if (data.hero_subheadline !== undefined) this.heroSubheadline = data.hero_subheadline
      if (data.hero_image_url !== undefined) this.heroImageUrl = data.hero_image_url
      if (data.hero_primary_cta_text !== undefined) this.heroPrimaryCtaText = data.hero_primary_cta_text
      if (data.hero_primary_cta_to !== undefined) this.heroPrimaryCtaTo = data.hero_primary_cta_to
      if (data.hero_secondary_cta_text !== undefined) this.heroSecondaryCtaText = data.hero_secondary_cta_text
      if (data.hero_secondary_cta_to !== undefined) this.heroSecondaryCtaTo = data.hero_secondary_cta_to
    }
  }
})
