import { defineStore } from 'pinia'
import { appBrandingApi } from '@/api/appBranding'

export const useAppBrandingStore = defineStore('appBranding', {
  state: () => ({
    appLogoUrl: null,
    faviconUrl: null,
    loaded: false
  }),

  getters: {
    hasAppLogo: (state) => !!state.appLogoUrl,
    hasFavicon: (state) => !!state.faviconUrl
  },

  actions: {
    async fetchBranding() {
      if (this.loaded) return
      try {
        const res = await appBrandingApi.getBranding()
        const data = res.data?.data || {}
        this.appLogoUrl = data.app_logo_url || null
        this.faviconUrl = data.favicon_url || null
        this.loaded = true
        this.applyFavicon()
      } catch {
        this.loaded = true
      }
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

    setBrandingFromUpload({ appLogoUrl, faviconUrl }) {
      if (appLogoUrl !== undefined) this.appLogoUrl = appLogoUrl
      if (faviconUrl !== undefined) {
        this.faviconUrl = faviconUrl
        this.applyFavicon()
      }
    }
  }
})
