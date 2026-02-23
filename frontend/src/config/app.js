/**
 * Nama dan tagline aplikasi dari env (bisa diubah per deployment/white-label).
 * Set VITE_APP_NAME di .env untuk mengubah nama aplikasi.
 */
export const appName = import.meta.env.VITE_APP_NAME || 'servr.in'
export const appTagline = 'One Platform for Smarter Education'
export const appVersion = import.meta.env.VITE_APP_VERSION || '0.1.26.02.23'
