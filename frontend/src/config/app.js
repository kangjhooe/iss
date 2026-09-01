/**
 * Nama, tagline, dan versi aplikasi dari env (bisa diubah per deployment/white-label).
 * Set VITE_APP_NAME / VITE_APP_TAGLINE / VITE_APP_VERSION di .env.
 */
export const appName = import.meta.env.VITE_APP_NAME || 'servr.in'
export const appTagline = import.meta.env.VITE_APP_TAGLINE || 'One Platform for Smarter Education'
export const appVersion = import.meta.env.VITE_APP_VERSION || '0.1.26.02.23'
