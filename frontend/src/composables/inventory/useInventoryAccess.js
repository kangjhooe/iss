import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { hasModuleAccess } from '@/utils/moduleAccess'
import { isInventoryRoomScopedUser } from '@/composables/inventory/inventoryRoutes'

export function useInventoryAccess() {
  const authStore = useAuthStore()

  const canManage = computed(() => {
    const user = authStore.user
    if (!user) return false
    if (['admin', 'institution_admin'].includes(user.role)) return true
    return hasModuleAccess(user, 'inventory')
  })

  const isRoomScoped = computed(() => isInventoryRoomScopedUser(authStore.user))

  const managedRoomIds = computed(() => {
    const ids = authStore.user?.managed_room_ids
    return Array.isArray(ids) ? ids.map((id) => Number(id)).filter((id) => id > 0) : []
  })

  return { canManage, isRoomScoped, managedRoomIds }
}
