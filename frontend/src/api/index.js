import axios from 'axios'
import { getToken, removeToken, getRefreshToken } from '@/utils/tokenStorage'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true
})

// Request interceptor untuk menambahkan token
api.interceptors.request.use(
  (config) => {
    const token = getToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
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
      // Try to refresh token
      const refreshToken = getRefreshToken()
      if (refreshToken) {
        try {
          const refreshResponse = await axios.post('/api/v1/refresh-token', {
            refresh_token: refreshToken
          })
          const newToken = refreshResponse.data.token
          const { setToken } = await import('@/utils/tokenStorage')
          setToken(newToken)
          
          // Retry original request
          error.config.headers.Authorization = `Bearer ${newToken}`
          return api.request(error.config)
        } catch (refreshError) {
          // Refresh failed, logout user
          removeToken()
          window.location.href = '/login'
        }
      } else {
        removeToken()
        window.location.href = '/login'
      }
    }
    
    // Format error message untuk ditampilkan ke user
    if (error.response?.data?.message) {
      error.formattedMessage = error.response.data.message
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
