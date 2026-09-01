<template>
  <div class="notifications-page">
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrap">
          <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <h1 class="page-title">Notifikasi</h1>
          <p class="page-subtitle">Ketuk notifikasi untuk membuka detail dan menandai dibaca</p>
        </div>
        <div class="header-actions">
          <button
            v-if="filter === 'unread' && notifications.length > 0"
            @click="handleMarkAllAsRead"
            :disabled="markAllLoading"
            class="btn-primary btn-compact"
          >
            {{ markAllLoading ? 'Memproses...' : 'Tandai semua dibaca' }}
          </button>
        </div>
      </div>
    </div>

    <div class="filter-tabs">
      <button
        v-for="opt in filterOptions"
        :key="opt.value"
        :class="['filter-tab', { active: filter === opt.value }]"
        @click="changeFilter(opt.value)"
      >
        {{ opt.label }}
        <span v-if="opt.value === 'unread' && unreadCount > 0" class="filter-tab-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        <span v-else-if="opt.value === 'all' && totalCount > 0" class="filter-tab-badge filter-tab-badge--muted">{{ totalCount }}</span>
      </button>
    </div>

    <div v-if="errorMessage" class="error-state">
      <p class="error-text">{{ errorMessage }}</p>
      <button @click="loadNotifications(1)" class="btn-primary">Coba lagi</button>
    </div>

    <div v-else-if="initialLoading" class="loading-wrap">
      <LoadingSkeleton type="list" :items="6" />
    </div>

    <div v-else-if="notifications.length === 0" class="empty-state">
      <div class="empty-icon">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h3 class="empty-title">Tidak ada notifikasi</h3>
      <p class="empty-desc">
        {{ filter === 'unread' ? 'Tidak ada notifikasi yang belum dibaca.' : filter === 'read' ? 'Belum ada notifikasi yang sudah dibaca.' : 'Belum ada notifikasi.' }}
      </p>
    </div>

    <div v-else class="notifications-list" :class="{ 'notifications-list--refreshing': refreshing }">
      <NotificationItem
        v-for="n in notifications"
        :key="n.id"
        :notification="n"
        @read="onNotificationRead"
      />
    </div>

    <PaginationBar
      v-if="!initialLoading && !errorMessage"
      :page="meta.current_page"
      :last-page="meta.last_page"
      :per-page="meta.per_page"
      :total="meta.total"
      item-label="notifikasi"
      @page-change="loadNotifications"
      @per-page-change="changePerPage"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import NotificationItem from '@/components/NotificationItem.vue'
import { notificationsApi } from '@/api/notifications'
import { useNotifications } from '@/composables/useNotifications'

const filterOptions = [
  { value: 'all', label: 'Semua' },
  { value: 'unread', label: 'Belum dibaca' },
  { value: 'read', label: 'Dibaca' },
]

const { unreadCount, refreshUnreadCount, markAllAsRead } = useNotifications()

const filter = ref('unread')
const notifications = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const totalCount = ref(0)
const initialLoading = ref(true)
const refreshing = ref(false)
const errorMessage = ref('')
const markAllLoading = ref(false)

function onNotificationRead(id) {
  const idx = notifications.value.findIndex((n) => n.id === id)
  if (idx !== -1) {
    notifications.value[idx] = { ...notifications.value[idx], read_at: new Date().toISOString() }
  }
  if (filter.value === 'unread') {
    notifications.value = notifications.value.filter((n) => n.id !== id)
  }
}

function changeFilter(value) {
  if (filter.value === value) return
  filter.value = value
  loadNotifications(1)
}

async function loadNotifications(page = 1) {
  errorMessage.value = ''
  if (initialLoading.value) {
    // keep skeleton
  } else {
    refreshing.value = true
  }
  try {
    const res = await notificationsApi.getList({
      filter: filter.value,
      page,
      per_page: meta.value.per_page || 15,
    })
    notifications.value = res.data?.data ?? []
    const m = res.data?.meta ?? {}
    meta.value = {
      current_page: m.current_page ?? 1,
      last_page: m.last_page ?? 1,
      per_page: m.per_page ?? meta.value.per_page,
      total: m.total ?? 0,
    }
    if (filter.value === 'all') {
      totalCount.value = meta.value.total
    }
    await refreshUnreadCount()
  } catch (e) {
    notifications.value = []
    errorMessage.value = e.response?.data?.message || 'Gagal memuat notifikasi. Silakan coba lagi.'
  } finally {
    initialLoading.value = false
    refreshing.value = false
  }
}

function changePerPage(n) {
  meta.value.per_page = n
  loadNotifications(1)
}

async function handleMarkAllAsRead() {
  markAllLoading.value = true
  try {
    await markAllAsRead()
    await loadNotifications(meta.value.current_page)
  } catch {
    errorMessage.value = 'Gagal menandai semua dibaca.'
  } finally {
    markAllLoading.value = false
  }
}

onMounted(async () => {
  await refreshUnreadCount()
  await loadNotifications(1)
})
</script>

<style scoped>
.notifications-page {
  padding: 0 24px 32px;
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 16px;
}

.header-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon {
  color: #fff;
  width: 28px;
  height: 28px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.header-actions {
  margin-left: auto;
}

.filter-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.filter-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.filter-tab:hover {
  background: #f8fafc;
  color: #334155;
}

.filter-tab.active {
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  border-color: transparent;
  color: #fff;
}

.filter-tab-badge {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  font-size: 11px;
  font-weight: 700;
  line-height: 18px;
  text-align: center;
  border-radius: 9px;
  background: #dc2626;
  color: #fff;
}

.filter-tab.active .filter-tab-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
}

.filter-tab-badge--muted {
  background: #e2e8f0;
  color: #64748b;
}

.filter-tab.active .filter-tab-badge--muted {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.error-state {
  padding: 32px;
  text-align: center;
  background: #fef2f2;
  border-radius: 12px;
  border: 1px solid #fecaca;
}

.error-text {
  color: #b91c1c;
  margin: 0 0 16px 0;
}

.loading-wrap {
  width: 100%;
  min-height: 200px;
}

.empty-state {
  padding: 48px 24px;
  text-align: center;
  background: #f8fafc;
  border-radius: 16px;
}

.empty-icon {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: #334155;
  margin: 0 0 8px 0;
}

.empty-desc {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: opacity 0.15s;
}

.notifications-list--refreshing {
  opacity: 0.55;
  pointer-events: none;
}

.btn-primary.btn-compact {
  padding: 10px 18px;
  font-size: 14px;
  border-radius: 10px;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    width: 100%;
    margin-left: 0;
  }

  .header-actions .btn-primary,
  .header-actions .btn-compact {
    width: 100%;
    justify-content: center;
  }
}
</style>
