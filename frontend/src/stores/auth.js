import { defineStore } from 'pinia'
import { authApi } from '@/api/auth'
import router from '@/router'
import { getToken, setToken, removeToken, setRefreshToken, getRefreshToken, isAuthenticated } from '@/utils/tokenStorage'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: getToken(),
    isAuthenticated: isAuthenticated()
  }),

  actions: {
    async login(credentials) {
      try {
        const response = await authApi.login(credentials)
        
        // Validate response structure
        if (!response || !response.data) {
          throw new Error('Invalid response from server')
        }
        
        if (!response.data.token) {
          throw new Error('Token tidak ditemukan dalam response')
        }
        
        if (!response.data.user) {
          throw new Error('Data user tidak ditemukan dalam response')
        }
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        setToken(this.token)
        if (response.data.refresh_token) {
          setRefreshToken(response.data.refresh_token)
        }
        return response.data
      } catch (error) {
        console.error('Auth store login error:', error)
        throw error
      }
    },

    async register(data) {
      try {
        const response = await authApi.register(data)
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        setToken(this.token)
        if (response.data.refresh_token) {
          setRefreshToken(response.data.refresh_token)
        }
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
        this.token = null
        this.isAuthenticated = false
        removeToken()
        router.push('/login')
      }
    },

    async fetchUser() {
      try {
        const response = await authApi.me()
        this.user = response.data.user
        return response.data.user
      } catch (error) {
        this.logout()
        throw error
      }
    }
  }
})
