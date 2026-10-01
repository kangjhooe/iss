import axios from 'axios'
import { clearAuth } from '@/utils/tokenStorage'
import { shouldHardRedirectToLogin } from '@/utils/appLayout'

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

const AUTH_NO_REFRESH_RE = /\/v1\/(login|logout|refresh-token|register|forgot-password|reset-password|verify-email|resend-verification|password-reset-requests)(\?|$)/

/** Satu flight refresh untuk semua 401 paralel; cegah storm ke /refresh-token. */
let refreshPromise = null
/** Setelah refresh gagal di sesi ini, jangan spam refresh lagi sampai login sukses. */
let refreshFailed = false
/**
 * Saat logout/teardown sesi: jangan hard-redirect ke /login.
 * Request 401 paralel (notifikasi, dashboard, dll.) sering menimpa redirect logout ke beranda.
 */
let authRedirectSuppressed = false

function shouldSkipTokenRefresh(config) {
  if (!config) return true
  if (config._retry) return true
  if (config.skipAuthRefresh) return true
  const url = String(config.url || '')
  return AUTH_NO_REFRESH_RE.test(url)
}

function redirectToLoginIfProtectedRoute() {
  if (typeof window === 'undefined') return
  if (authRedirectSuppressed) return
  const path = window.location.pathname || ''
  if (shouldHardRedirectToLogin(path)) {
    window.location.replace('/login')
  }
}

/**
 * Blokir hard-redirect auth (dipakai selama logout agar tetap ke beranda).
 * Tidak memblokir refresh saat memanggil /logout dengan access token kedaluwarsa.
 */
export function suppressAuthRedirect() {
  authRedirectSuppressed = true
}

/**
 * Dipanggil setelah login/register sukses agar 401 berikutnya boleh coba refresh lagi.
 */
export function resetAuthRefreshState() {
  refreshFailed = false
  refreshPromise = null
  authRedirectSuppressed = false
}

async function refreshAccessToken() {
  if (refreshPromise) return refreshPromise
  refreshPromise = api.post('/v1/refresh-token')
    .then((res) => {
      refreshFailed = false
      return res
    })
    .catch((err) => {
      refreshFailed = true
      throw err
    })
    .finally(() => {
      refreshPromise = null
    })
  return refreshPromise
}

// Response interceptor untuk handle error
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // Jangan recursive-refresh pada endpoint auth; satu kali retry saja.
    // Tanpa ini, 401 dari /me atau cookie basi memicu storm /refresh-token.
    if (error.response?.status === 401 && !shouldSkipTokenRefresh(originalRequest)) {
      if (refreshFailed) {
        clearAuth()
        redirectToLoginIfProtectedRoute()
        return Promise.reject(error)
      }

      originalRequest._retry = true
      try {
        await refreshAccessToken()
        // Backend set cookie auth_token baru; retry request (cookie ikut terkirim)
        return api.request(originalRequest)
      } catch (refreshError) {
        clearAuth()
        // Hanya hard-redirect di rute app (dashboard dll). Halaman publik (/) tetap.
        redirectToLoginIfProtectedRoute()
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
