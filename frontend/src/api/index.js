import axios from 'axios'
import { clearAuth } from '@/utils/tokenStorage'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true // Kirim httpOnly cookie (auth_token) ke backend
})

// Token dikirim via httpOnly cookie; kirim juga konteks sekolah aktif
api.interceptors.request.use(
  (config) => {
    // Remove Content-Type header for FormData to let browser set it with boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    }

    const activeId = typeof window !== 'undefined'
      ? window.__ISS_ACTIVE_INSTITUTION_ID__
      : null
    if (activeId) {
      config.headers['X-Institution-Id'] = String(activeId)
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

const AUTH_NO_REFRESH_RE = /\/v1\/(login|refresh-token|register|forgot-password|reset-password|verify-email|resend-verification|password-reset-requests)(\?|$)/

function shouldSkipTokenRefresh(config) {
  if (!config) return true
  if (config._retry) return true
  const url = String(config.url || '')
  return AUTH_NO_REFRESH_RE.test(url)
}

// Response interceptor untuk handle error
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Jangan recursive-refresh pada endpoint auth; satu kali retry saja.
    // Tanpa ini, 401 dari /me atau cookie basi memicu storm /refresh-token
    // yang menghabiskan throttle login (bucket bersama) sebelum user sempat masuk.
    if (error.response?.status === 401 && !shouldSkipTokenRefresh(originalRequest)) {
      originalRequest._retry = true
      try {
        await api.post('/v1/refresh-token')
        // Backend set cookie auth_token baru; retry request (cookie ikut terkirim)
        return api.request(originalRequest)
      } catch (refreshError) {
        clearAuth()
        if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
          window.location.href = '/login'
        }
        return Promise.reject(refreshError)
      }
    }

    if (error.response?.status === 503 && error.response?.data?.maintenance) {
      error.formattedMessage = error.response.data.message || 'Sistem sedang dalam mode pemeliharaan.'
      return Promise.reject(error)
    }

    // Format error message untuk ditampilkan ke user (untuk 422 utamakan errors agar user lihat alasan spesifik)
    if (error.response?.status === 422 && error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0]
      error.formattedMessage = Array.isArray(firstError) ? firstError[0] : firstError
    } else if (error.response?.data?.message) {
      error.formattedMessage = error.response.data.message
    } else if (error.response?.data?.error) {
      // Handle error field from backend
      error.formattedMessage = error.response.data.error
    } else if (error.response?.data?.errors) {
      // Handle validation errors
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0]
      error.formattedMessage = Array.isArray(firstError) ? firstError[0] : firstError
    } else if (error.message) {
      error.formattedMessage = error.message
    } else {
      error.formattedMessage = 'Terjadi kesalahan. Silakan coba lagi.'
    }
    
    return Promise.reject(error)
  }
)

export default api
