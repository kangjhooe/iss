import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { institutionApi } from '@/api/institution'

/**
 * Pilih semester default: query/preferred → semester aktif institusi → is_active → pertama di list.
 */
export function resolveSemesterIdFromList(semesters, { preferredId = '', activeSemesterId = '' } = {}) {
  const list = Array.isArray(semesters) ? semesters : []
  const preferred = preferredId ? String(preferredId) : ''
  if (preferred && list.some((s) => String(s.id) === preferred)) {
    return preferred
  }
  const active = activeSemesterId ? String(activeSemesterId) : ''
  if (active && list.some((s) => String(s.id) === active)) {
    return active
  }
  const markedActive = list.find((s) => s.is_active)
  if (markedActive) return String(markedActive.id)
  return list.length ? String(list[0].id) : ''
}

export function useActiveAcademicPeriod() {
  const authStore = useAuthStore()
  const cachedYearId = ref('')
  const cachedSemesterId = ref('')

  const activeAcademicYearId = computed(() => {
    const inst = authStore.activeInstitution || authStore.user?.institution
    const id = inst?.active_academic_year_id || cachedYearId.value
    return id ? String(id) : ''
  })

  const activeSemesterId = computed(() => {
    const inst = authStore.activeInstitution || authStore.user?.institution
    const id = inst?.active_semester_id || cachedSemesterId.value
    return id ? String(id) : ''
  })

  async function ensureLoaded() {
    if (activeSemesterId.value && activeAcademicYearId.value) return
    try {
      const res = await institutionApi.getMy()
      const inst = res.data?.data || res.data || null
      if (inst?.active_semester_id) cachedSemesterId.value = String(inst.active_semester_id)
      if (inst?.active_academic_year_id) cachedYearId.value = String(inst.active_academic_year_id)
    } catch {
      // ignore — caller falls back to semester list
    }
  }

  function resolveDefaultSemesterId(preferredId = '', semesters = []) {
    return resolveSemesterIdFromList(semesters, {
      preferredId,
      activeSemesterId: activeSemesterId.value || cachedSemesterId.value,
    })
  }

  async function refreshFromServer() {
    await authStore.fetchUser()
    cachedYearId.value = ''
    cachedSemesterId.value = ''
  }

  return {
    activeAcademicYearId,
    activeSemesterId,
    ensureLoaded,
    resolveDefaultSemesterId,
    refreshFromServer,
  }
}
