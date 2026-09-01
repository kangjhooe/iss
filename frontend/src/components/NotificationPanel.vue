<template>
  <div class="notification-panel" role="dialog" aria-label="Notifikasi terbaru">
    <div class="notification-panel__header">
      <h2 class="notification-panel__title">Notifikasi</h2>
      <button
        v-if="notifications.length > 0 && unreadCount > 0"
        type="button"
        class="notification-panel__mark-all"
        :disabled="markAllLoading"
        @click="handleMarkAll"
      >
        {{ markAllLoading ? '...' : 'Tandai semua' }}
      </button>
    </div>

    <div v-if="loading" class="notification-panel__loading">
      <div v-for="i in 4" :key="i" class="notification-panel__skeleton" />
    </div>

    <div v-else-if="errorMessage" class="notification-panel__empty">
      <p>{{ errorMessage }}</p>
      <button type="button" class="notification-panel__retry" @click="loadPreview">Coba lagi</button>
    </div>

    <div v-else-if="notifications.length === 0" class="notification-panel__empty">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <p>Tidak ada notifikasi baru</p>
    </div>

    <div v-else class="notification-panel__list">
      <NotificationItem
        v-for="n in notifications"
        :key="n.id"
        :notification="n"
        compact
        @read="onItemRead"
        @opened="$emit('navigate')"
      />
    </div>

    <router-link
      to="/notifications"
      class="notification-panel__footer"
      @click="$emit('navigate')"
    >
      Lihat semua notifikasi
      <span v-if="unreadCount > 0" class="notification-panel__footer-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
    </router-link>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import NotificationItem from '@/components/NotificationItem.vue'
import { notificationsApi } from '@/api/notifications'
import { useNotifications } from '@/composables/useNotifications'

defineEmits(['navigate', 'close'])

const { unreadCount, refreshUnreadCount, markAllAsRead } = useNotifications()

const notifications = ref([])
const loading = ref(false)
const errorMessage = ref('')
const markAllLoading = ref(false)

async function loadPreview() {
  errorMessage.value = ''
  loading.value = true
  try {
    const res = await notificationsApi.getList({ filter: 'unread', page: 1, per_page: 8 })
    notifications.value = res.data?.data ?? []
  } catch (e) {
    notifications.value = []
    errorMessage.value = e.response?.data?.message || 'Gagal memuat notifikasi.'
  } finally {
    loading.value = false
  }
}

async function handleMarkAll() {
  markAllLoading.value = true
  try {
    await markAllAsRead()
    notifications.value = []
  } catch {
    errorMessage.value = 'Gagal menandai semua dibaca.'
  } finally {
    markAllLoading.value = false
  }
}

function onItemRead(id) {
  notifications.value = notifications.value.filter((n) => n.id !== id)
}

onMounted(() => {
  refreshUnreadCount()
  loadPreview()
})

defineExpose({ loadPreview, refreshUnreadCount })
</script>

<style scoped>
.notification-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: min(380px, calc(100vw - 24px));
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12);
  z-index: 200;
  overflow: hidden;
}

.notification-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.notification-panel__title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.notification-panel__mark-all {
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
  color: #059669;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
}

.notification-panel__mark-all:hover:not(:disabled) {
  background: #ecfdf5;
}

.notification-panel__mark-all:disabled {
  opacity: 0.6;
  cursor: wait;
}

.notification-panel__list {
  max-height: 360px;
  overflow-y: auto;
}

.notification-panel__loading {
  padding: 8px 0;
}

.notification-panel__skeleton {
  height: 64px;
  margin: 8px 16px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.notification-panel__empty {
  padding: 32px 20px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

.notification-panel__empty p {
  margin: 8px 0 0;
}

.notification-panel__retry {
  margin-top: 10px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: #059669;
  background: #ecfdf5;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-family: inherit;
}

.notification-panel__footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
  border-top: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.notification-panel__footer:hover {
  background: #f8fafc;
}

.notification-panel__footer-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  font-size: 11px;
  font-weight: 700;
  line-height: 18px;
  text-align: center;
  color: #fff;
  background: #dc2626;
  border-radius: 9px;
}
</style>
