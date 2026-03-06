<template>
  <div v-if="showStatus" class="offline-status" :class="{ 'is-offline': isOffline, 'is-syncing': isSyncing }">
    <div class="status-content">
      <span class="status-icon">{{ isOffline ? '📴' : isSyncing ? '🔄' : '✅' }}</span>
      <span class="status-text">
        <span v-if="isOffline">Mode Offline</span>
        <span v-else-if="isSyncing">Menyinkronkan data... {{ Math.round(syncProgress) }}%</span>
        <span v-else-if="pendingCount > 0">{{ pendingCount }} item menunggu sinkronisasi</span>
        <span v-else>Online</span>
      </span>
      <button v-if="pendingCount > 0 && !isSyncing && !isOffline" @click="manualSync" class="btn-sync">
        Sync Sekarang
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { isOnline, onNetworkStatusChange } from '@/utils/offlineStorage'
import { syncQueue } from '@/utils/offlineStorage'
import { useOfflineSync } from '@/composables/useOfflineSync'

const isOffline = ref(!isOnline())
const { isSyncing, syncProgress, syncPendingItems } = useOfflineSync()
const pendingCount = ref(0)
const showStatus = ref(false)

let networkListener = null

onMounted(async () => {
  networkListener = onNetworkStatusChange((online) => {
    isOffline.value = !online
    updatePendingCount()
  })
  
  await updatePendingCount()
  
  // Show status if offline or has pending items
  showStatus.value = isOffline.value || pendingCount.value > 0
  
  // Check pending count periodically
  const interval = setInterval(updatePendingCount, 5000)
  
  onUnmounted(() => {
    clearInterval(interval)
  })
})

async function updatePendingCount() {
  try {
    const pending = await syncQueue.getPending()
    pendingCount.value = pending.length
    showStatus.value = isOffline.value || pendingCount.value > 0 || isSyncing.value
  } catch (error) {
    console.error('Error updating pending count:', error)
  }
}

async function manualSync() {
  await syncPendingItems()
  await updatePendingCount()
}
</script>

<style scoped>
.offline-status {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: #fef3c7;
  color: #92400e;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.offline-status.is-offline {
  background: #fee2e2;
  color: #991b1b;
}

.offline-status.is-syncing {
  background: #dbeafe;
  color: #1e40af;
}

.status-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.status-icon {
  font-size: 1rem;
}

.status-text {
  flex: 1;
  text-align: center;
}

.btn-sync {
  background: #059669;
  color: #fff;
  border: none;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  font-weight: 500;
}

.btn-sync:hover {
  background: #047857;
}

@media (max-width: 768px) {
  .offline-status {
    padding: 0.5rem;
    font-size: 0.8rem;
  }
  
  .status-content {
    flex-wrap: wrap;
    gap: 0.25rem;
  }
  
  .btn-sync {
    width: 100%;
    margin-top: 0.25rem;
  }
}
</style>
