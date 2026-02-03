import { defineStore } from 'pinia'
import { authApi } from '@/api/auth'
import router from '@/router'
import { clearAuth } from '@/utils/tokenStorage'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false // Ditentukan dari /me atau setelah login; token via httpOnly cookie
  }),

  actions: {
    async login(credentials) {
      try {
        console.log('=== LOGIN REQUEST START ===')
        console.log('Credentials:', { ...credentials, password: '***' })
        
        const response = await authApi.login(credentials)
        
        // Log full response for debugging
        console.log('=== LOGIN RESPONSE DEBUG ===')
        console.log('Full response object:', response)
        console.log('Response type:', typeof response)
        console.log('Response.data:', response?.data)
        console.log('Response.data type:', typeof response?.data)
        console.log('Response.status:', response?.status)
        console.log('Response keys:', Object.keys(response || {}))
        if (response?.data) {
          console.log('Response.data keys:', Object.keys(response.data))
          console.log('Response.data.token:', response.data.token)
          console.log('Response.data.user:', response.data.user)
        }
        console.log('=== END RESPONSE DEBUG ===')
        
        // Validate response structure
        if (!response) {
          console.error('No response received')
          throw new Error('Tidak ada response dari server')
        }
        
        // Check if response has error status
        if (response.status && response.status >= 400) {
          const errorMessage = response.data?.message || response.data?.error || 'Terjadi kesalahan saat login'
          throw new Error(errorMessage)
        }
        
        // Validate response data - check if data exists
        const responseData = response.data || response
        if (!responseData) {
          console.error('Invalid response structure - no data:', response)
          throw new Error('Invalid response from server')
        }
        
        if (!responseData.user) {
          console.error('User data not found in response. Response data:', responseData)
          throw new Error('Data user tidak ditemukan dalam response')
        }

        // Auth token & refresh token disimpan di httpOnly cookie oleh backend; tidak disimpan di localStorage
        this.user = responseData.user
        this.isAuthenticated = true
        return responseData
      } catch (error) {
        console.error('=== LOGIN ERROR DEBUG ===')
        console.error('Error object:', error)
        console.error('Error type:', typeof error)
        console.error('Error.response:', error.response)
        console.error('Error.response?.status:', error.response?.status)
        console.error('Error.response?.statusText:', error.response?.statusText)
        console.error('Error.response?.data:', error.response?.data)
        console.error('Error.response?.headers:', error.response?.headers)
        console.error('Error.message:', error.message)
        console.error('Error.code:', error.code)
        console.error('Error.config:', error.config)
        console.error('Error.stack:', error.stack)
        console.error('=== END ERROR DEBUG ===')
        
        // Re-throw with better error message
        if (error.response) {
          // Server responded with error
          if (error.response.data) {
            if (error.response.data.message) {
              throw new Error(error.response.data.message)
            } else if (error.response.data.error) {
              throw new Error(error.response.data.error)
            } else if (error.response.data.errors) {
              // Handle validation errors
              const errors = error.response.data.errors
              const firstError = Object.values(errors)[0]
              const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
              throw new Error(errorMessage)
            }
          }
          // Response exists but no data
          throw new Error(`Server error: ${error.response.status} ${error.response.statusText || ''}`)
        } else if (error.request) {
          // Request was made but no response received
          throw new Error('Tidak ada response dari server. Periksa koneksi internet Anda.')
        } else if (error.message) {
          // Error occurred in setting up the request
          throw error
        } else {
          throw new Error('Terjadi kesalahan saat login. Silakan coba lagi.')
        }
      }
    },

    async register(data) {
      try {
        const response = await authApi.register(data)
        this.user = response.data.user
        this.isAuthenticated = true
        return response.data
      } catch (error) {
        throw error
      }
    },

    async logout() {
      try {
        await authApi.logout()
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.isAuthenticated = false
        clearAuth()
        router.push('/login')
      }
    },

    async fetchUser() {
      try {
        const response = await authApi.me()
        this.user = response.data.user
        this.isAuthenticated = true
        return response.data.user
      } catch (error) {
        this.isAuthenticated = false
        this.user = null
        throw error
      }
    }
  }
})
