import { defineStore } from 'pinia'
import { authApi } from '@/api/auth'
import router from '@/router'
import { clearAuth } from '@/utils/tokenStorage'

const getDefaultRoute = (role) => {
  if (role === 'super_admin') return '/super-admin/dashboard'
  if (role === 'teacher' || role === 'staff') return '/teacher/dashboard'
  if (role === 'student') return '/student/dashboard'
  if (role === 'parent') return '/parent/dashboard'
  return '/dashboard'
}

function syncActiveInstitutionGlobal(user) {
  if (typeof window === 'undefined') return
  window.__ISS_ACTIVE_INSTITUTION_ID__ = user?.active_institution_id
    || user?.institution_id
    || null
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false // Ditentukan dari /me atau setelah login; token via httpOnly cookie
  }),

  getters: {
    isImpersonating: (state) => !!state.user?.impersonation?.active,
    availableInstitutions: (state) => state.user?.available_institutions || [],
    activeInstitutionId: (state) => state.user?.active_institution_id || state.user?.institution_id || null,
    activeInstitution: (state) => state.user?.active_institution || state.user?.institution || null,
    activeAcademicYearId: (state) => {
      const inst = state.user?.active_institution || state.user?.institution
      return inst?.active_academic_year_id || null
    },
    activeSemesterId: (state) => {
      const inst = state.user?.active_institution || state.user?.institution
      return inst?.active_semester_id || null
    },
    activeAffiliation: (state) => state.user?.active_affiliation || 'induk',
    canSwitchInstitution: (state) => (state.user?.available_institutions || []).length > 1,
    isDemoInstitution: (state) => {
      const inst = state.user?.active_institution || state.user?.institution
      return !!(inst?.is_demo)
    },
    monetization: (state) => {
      return state.user?.monetization
        || state.user?.active_institution?.monetization
        || { launched: false, store_visible: false }
    },
    isMonetizationVisible: (state) => {
      const m = state.user?.monetization || state.user?.active_institution?.monetization
      return !!(m?.launched && m?.store_visible)
    },
    /** Sebelum launch selalu true; setelah launch mengikuti entitlement paket/add-on. */
    isOnlineExamEntitled: (state) => {
      const m = state.user?.monetization || state.user?.active_institution?.monetization
      if (!m?.launched || !m?.online_exam?.enforced) return true
      return m.online_exam?.entitled !== false
    },
    hiddenModuleKeys: (state) => {
      const keys = state.user?.hidden_module_keys
        || state.user?.active_institution?.hidden_module_keys
        || state.user?.institution?.hidden_module_keys
        || []
      return Array.isArray(keys) ? keys : []
    },
  },

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
        syncActiveInstitutionGlobal(this.user)
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
        syncActiveInstitutionGlobal(this.user)
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
        syncActiveInstitutionGlobal(null)
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
        syncActiveInstitutionGlobal(this.user)
        return response.data.user
      } catch (error) {
        this.isAuthenticated = false
        this.user = null
        syncActiveInstitutionGlobal(null)
        throw error
      }
    },

    async switchInstitution(institutionId) {
      const response = await authApi.switchInstitution(institutionId)
      const user = response.data?.user
      if (!user) throw new Error('Data user tidak ditemukan')
      this.user = user
      syncActiveInstitutionGlobal(this.user)
      // Pastikan header request berikutnya memakai sekolah baru sebelum reload
      if (typeof window !== 'undefined' && user.active_institution_id) {
        window.__ISS_ACTIVE_INSTITUTION_ID__ = user.active_institution_id
      }
      return user
    },

    async startImpersonate(adminId) {
      const response = await authApi.startImpersonate(adminId)
      const user = response.data?.user
      if (!user) throw new Error('Data user tidak ditemukan')
      this.user = user
      this.isAuthenticated = true
      syncActiveInstitutionGlobal(this.user)
      await router.replace(getDefaultRoute(user.role))
      return user
    },

    async startImpersonateTeacher(employeeId) {
      const response = await authApi.startImpersonateTeacher(employeeId)
      const user = response.data?.user
      if (!user) throw new Error('Data user tidak ditemukan')
      this.user = user
      this.isAuthenticated = true
      syncActiveInstitutionGlobal(this.user)
      await router.replace(getDefaultRoute(user.role))
      return user
    },

    async stopImpersonate() {
      const response = await authApi.stopImpersonate()
      const user = response.data?.user
      if (!user) throw new Error('Data user tidak ditemukan')
      this.user = user
      this.isAuthenticated = true
      syncActiveInstitutionGlobal(this.user)
      await router.replace(getDefaultRoute(user.role))
      return user
    }
  }
})
