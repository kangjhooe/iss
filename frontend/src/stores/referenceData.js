import { defineStore } from 'pinia'
import { academicYearApi } from '@/api/academicYear'
import { additionalDutiesApi } from '@/api/additionalDuties'

const CACHE_TTL_MS = 5 * 60 * 1000 // 5 menit
const DEFAULT_PER_PAGE = 100

export const useReferenceDataStore = defineStore('referenceData', {
  state: () => ({
    academicYears: [],
    academicYearsExpiry: 0,
    additionalDuties: [],
    additionalDutiesExpiry: 0,
    academicYearsLoading: false,
    additionalDutiesLoading: false
  }),

  actions: {
    /**
     * Ambil daftar tahun ajaran (untuk dropdown). Pakai cache 5 menit.
     * @returns {Promise<Array>}
     */
    async getAcademicYears() {
      if (Date.now() < this.academicYearsExpiry && this.academicYears.length > 0) {
        return this.academicYears
      }
      this.academicYearsLoading = true
      try {
        const res = await academicYearApi.getAll({ per_page: DEFAULT_PER_PAGE })
        const data = res.data?.data ?? res.data ?? []
        this.academicYears = Array.isArray(data) ? data : []
        this.academicYearsExpiry = Date.now() + CACHE_TTL_MS
        return this.academicYears
      } catch {
        this.academicYears = []
        return []
      } finally {
        this.academicYearsLoading = false
      }
    },

    /**
     * Invalidasi cache tahun ajaran (dipanggil setelah create/update/delete di halaman tahun ajaran).
     */
    invalidateAcademicYears() {
      this.academicYearsExpiry = 0
      this.academicYears = []
    },

    /**
     * Ambil daftar tugas tambahan (untuk dropdown). Pakai cache 5 menit.
     * @returns {Promise<Array>}
     */
    async getAdditionalDuties() {
      if (Date.now() < this.additionalDutiesExpiry && this.additionalDuties.length > 0) {
        return this.additionalDuties
      }
      this.additionalDutiesLoading = true
      try {
        const res = await additionalDutiesApi.getAll()
        const data = res.data?.data ?? res.data ?? []
        this.additionalDuties = Array.isArray(data) ? data : []
        this.additionalDutiesExpiry = Date.now() + CACHE_TTL_MS
        return this.additionalDuties
      } catch {
        this.additionalDuties = []
        return []
      } finally {
        this.additionalDutiesLoading = false
      }
    },

    /**
     * Invalidasi cache tugas tambahan.
     */
    invalidateAdditionalDuties() {
      this.additionalDutiesExpiry = 0
      this.additionalDuties = []
    }
  }
})
