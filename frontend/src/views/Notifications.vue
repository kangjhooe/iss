<template>
  <Layout>
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
            <p class="page-subtitle">Daftar notifikasi mutasi dan aktivitas lainnya</p>
          </div>
          <div class="header-actions">
            <button
              v-if="filter === 'unread' && notifications.length > 0"
              @click="markAllAsRead"
              :disabled="markAllLoading"
              class="btn-primary btn-compact"
            >
              {{ markAllLoading ? 'Memproses...' : 'Tandai semua dibaca' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs">
        <button
          v-for="opt in filterOptions"
          :key="opt.value"
          :class="['filter-tab', { active: filter === opt.value }]"
          @click="filter = opt.value; loadNotifications(1)"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Error state -->
      <div v-if="errorMessage" class="error-state">
        <p class="error-text">{{ errorMessage }}</p>
        <button @click="loadNotifications(1)" class="btn-primary">Coba lagi</button>
      </div>

      <!-- Loading skeleton -->
      <div v-else-if="loading" class="loading-wrap">
        <LoadingSkeleton type="list" :items="8" />
      </div>

      <!-- Empty state -->
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

      <!-- List -->
      <div v-else class="notifications-list">
        <div
          v-for="n in notifications"
          :key="n.id"
          class="notification-card"
          :class="{ unread: !n.read_at }"
        >
          <div class="notification-body">
            <p class="notification-message">{{ n.message || 'Notifikasi' }}</p>
            <span class="notification-time">{{ formatDate(n.created_at) }}</span>
            <router-link v-if="n.type === 'ppdb_registration'" to="/ppdb" class="notification-link">Buka PPDB →</router-link>
          </div>
          <button
            v-if="!n.read_at"
            @click="markOneAsRead(n.id)"
            :disabled="markingId === n.id"
            class="btn-mark-read"
            title="Tandai dibaca"
          >
            {{ markingId === n.id ? '...' : 'Tandai dibaca' }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1 && !loading && !errorMessage" class="pagination-bar">
        <span class="pagination-info">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
        <div class="pagination-btns">
          <button
            type="button"
            class="btn-page"
            :disabled="meta.current_page <= 1"
            @click="loadNotifications(meta.current_page - 1)"
          >
            Sebelumnya
          </button>
          <button
            type="button"
            class="btn-page"
            :disabled="meta.current_page >= meta.last_page"
            @click="loadNotifications(meta.current_page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { notificationsApi } from '@/api/notifications'

const filterOptions = [
  { value: 'all', label: 'Semua' },
  { value: 'unread', label: 'Belum dibaca' },
  { value: 'read', label: 'Dibaca' }
]

const filter = ref('unread')
const notifications = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const loading = ref(false)
const errorMessage = ref('')
const markAllLoading = ref(false)
const markingId = ref(null)

function formatDate(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = now - d
  if (diff < 60000) return 'Baru saja'
  if (diff < 3600000) return `${Math.floor(diff / 60000)} menit lalu`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} jam lalu`
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadNotifications(page = 1) {
  errorMessage.value = ''
  loading.value = true
  try {
    const res = await notificationsApi.getList({
      filter: filter.value,
      page,
      per_page: 20
    })
    notifications.value = res.data?.data ?? []
    const m = res.data?.meta ?? {}
    meta.value = {
      current_page: m.current_page ?? 1,
      last_page: m.last_page ?? 1
    }
  } catch (e) {
    notifications.value = []
    errorMessage.value = e.response?.data?.message || 'Gagal memuat notifikasi. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

async function markOneAsRead(id) {
  markingId.value = id
  try {
    await notificationsApi.markAsRead(id)
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) notifications.value[idx] = { ...notifications.value[idx], read_at: new Date().toISOString() }
  } catch {
    // ignore
  } finally {
    markingId.value = null
  }
}

async function markAllAsRead() {
  markAllLoading.value = true
  try {
    await notificationsApi.markAllAsRead()
    await loadNotifications(meta.value.current_page)
  } catch {
    errorMessage.value = 'Gagal menandai semua dibaca.'
  } finally {
    markAllLoading.value = false
  }
}

loadNotifications(1)
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
}

.filter-tab {
  padding: 10px 18px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
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
  gap: 12px;
}

.notification-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: background 0.2s;
}

.notification-card.unread {
  background: #f0f9ff;
  border-color: #bae6fd;
}

.notification-link {
  display: inline-block;
  margin-top: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #059669;
  text-decoration: none;
}
.notification-link:hover {
  text-decoration: underline;
}

.notification-body {
  flex: 1;
  min-width: 0;
}

.notification-message {
  margin: 0 0 6px 0;
  font-size: 15px;
  color: #0f172a;
  line-height: 1.4;
}

.notification-time {
  font-size: 13px;
  color: #64748b;
}

.btn-mark-read {
  flex-shrink: 0;
  padding: 6px 12px;
  font-size: 13px;
  color: #059669;
  background: transparent;
  border: 1px solid #818cf8;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-mark-read:hover:not(:disabled) {
  background: #ecfdf5;
}

.btn-mark-read:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
}

.pagination-btns {
  display: flex;
  gap: 8px;
}

.btn-page {
  padding: 8px 16px;
  font-size: 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  cursor: pointer;
}

.btn-page:hover:not(:disabled) {
  background: #f1f5f9;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary.btn-compact {
  padding: 10px 18px;
  font-size: 14px;
  border-radius: 10px;
}
</style>
