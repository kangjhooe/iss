import { ref } from 'vue'
import { facilityApi } from '@/api/facility'
import { safeArray } from './inventoryApiHelpers'

export function useInventoryFacilityOptions() {
  const buildings = ref([])
  const rooms = ref([])
  const loading = ref(false)

  async function loadBuildings() {
    try {
      const res = await facilityApi.getBuildings({})
      buildings.value = safeArray(res)
    } catch {
      buildings.value = []
    }
  }

  async function loadRooms() {
    try {
      const res = await facilityApi.getRooms({})
      rooms.value = safeArray(res)
    } catch {
      rooms.value = []
    }
  }

  async function ensureFacilityOptions() {
    loading.value = true
    try {
      await Promise.all([
        buildings.value.length ? Promise.resolve() : loadBuildings(),
        rooms.value.length ? Promise.resolve() : loadRooms()
      ])
    } finally {
      loading.value = false
    }
  }

  return {
    buildings,
    rooms,
    loading,
    loadBuildings,
    loadRooms,
    ensureFacilityOptions
  }
}
