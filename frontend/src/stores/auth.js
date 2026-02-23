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
        const response = await authApi.login(credentials)
        if (!response) {
          throw new Error('Tidak ada response dari server')
        }
        if (response.status && response.status >= 400) {
          const errorMessage = response.data?.message || response.data?.error || 'Terjadi kesalahan saat login'
          throw new Error(errorMessage)
        }
        const responseData = response.data || response
        if (!responseData || !responseData.user) {
          throw new Error('Data user tidak ditemukan dalam response')
        }
        // Auth token & refresh token disimpan di httpOnly cookie oleh backend; tidak disimpan di localStorage
        this.user = responseData.user
        this.isAuthenticated = true
        return responseData
      } catch (error) {
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
      } catch {
        // Tetap bersihkan state dan redirect meski API gagal
      } finally {
        this.user = null
        this.isAuthenticated = false
        clearAuth()
        // Full reload ke /login agar cookie/state bersih dan request login berikutnya tidak terpengaruh cache atau state lama
        window.location.href = '/login'
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
