import { defineStore } from 'pinia'
import { authApi } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token')
  }),

  actions: {
    async login(credentials) {
      try {
        const response = await authApi.login(credentials)
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        localStorage.setItem('token', this.token)
        return response.data
      } catch (error) {
        throw error
      }
    },

    async register(data) {
      try {
        const response = await authApi.register(data)
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        localStorage.setItem('token', this.token)
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
        localStorage.removeItem('token')
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
