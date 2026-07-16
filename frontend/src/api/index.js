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

// Response interceptor untuk handle error
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Coba refresh: refresh_token dikirim otomatis via httpOnly cookie
      try {
        const refreshResponse = await api.post('/v1/refresh-token')
        // Backend set cookie auth_token baru; retry request (cookie ikut terkirim)
        return api.request(error.config)
      } catch (refreshError) {
        // Refresh gagal (cookie kedaluwarsa / tidak ada) -> logout
        clearAuth()
        window.location.href = '/login'
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
