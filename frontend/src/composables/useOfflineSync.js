import { ref, onMounted } from 'vue'
import { syncQueue, isOnline, onNetworkStatusChange } from '@/utils/offlineStorage'
import { studentAttendanceApi, employeeAttendanceApi } from '@/api/attendance'
import { useToast } from './useToast'

export function useOfflineSync() {
  const isSyncing = ref(false)
  const syncProgress = ref(0)
  const toast = useToast()

  onMounted(() => {
    // Sync when coming online
    onNetworkStatusChange(async (online) => {
      if (online) {
        await syncPendingItems()
      }
    })

    // Initial sync check
    if (isOnline()) {
      syncPendingItems()
    }
  })

  async function syncPendingItems() {
    if (isSyncing.value) return

    try {
      isSyncing.value = true
      const pending = await syncQueue.getPending()
      
      if (pending.length === 0) {
        return
      }

      let synced = 0
      let failed = 0
      
      for (const item of pending) {
        try {
          await syncItem(item)
          await syncQueue.markSynced(item.id)
          synced++
          syncProgress.value = (synced / pending.length) * 100
        } catch (error) {
          console.error('Error syncing item:', error)
          const errorMessage = error.response?.data?.message || error.message || 'Unknown error'
          await syncQueue.markFailed(item.id, errorMessage)
          failed++
          
          // If too many retries, remove from queue
          if (item.retries >= 3) {
            await syncQueue.remove(item.id)
          }
        }
      }
      
      if (failed > 0 && synced === 0) {
        toast.error(`${failed} item gagal disinkronkan. Periksa koneksi internet.`)
      }

      if (synced > 0) {
        toast.success(`${synced} item berhasil disinkronkan`)
      }
    } catch (error) {
      console.error('Error syncing pending items:', error)
    } finally {
      isSyncing.value = false
      syncProgress.value = 0
    }
  }

  async function syncItem(item) {
    switch (item.type) {
      case 'student-attendance':
        await studentAttendanceApi.saveForTeachingJournal(
          item.data.teachingJournalId,
          item.data.attendances
        )
        break
      case 'employee-attendance':
        await employeeAttendanceApi.bulkStore(
          item.data.date,
          item.data.attendances
        )
        break
      default:
        throw new Error(`Unknown sync type: ${item.type}`)
    }
  }

  return {
    isSyncing,
    syncProgress,
    syncPendingItems
  }
}
