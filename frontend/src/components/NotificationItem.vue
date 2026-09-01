<template>
  <component
    :is="rootTag"
    class="notification-item"
    :class="{
      'notification-item--unread': !notification.read_at,
      'notification-item--compact': compact,
      'notification-item--clickable': clickable,
    }"
    :type="rootTag === 'button' ? 'button' : undefined"
    :role="rootTag === 'div' ? 'button' : undefined"
    :tabindex="rootTag === 'div' ? 0 : undefined"
    @click="handleClick"
    @keydown.enter.prevent="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <div
      class="notification-item__icon"
      :style="{ background: meta.bg, color: meta.color }"
      aria-hidden="true"
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path :d="iconPath" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>
    <div class="notification-item__body">
      <div class="notification-item__head">
        <span class="notification-item__type" :style="{ color: meta.color }">{{ meta.label }}</span>
        <span v-if="!notification.read_at" class="notification-item__dot" aria-label="Belum dibaca" />
      </div>
      <p class="notification-item__message">{{ notification.message || 'Notifikasi' }}</p>
      <div class="notification-item__footer">
        <span class="notification-item__time">{{ formatNotificationDate(notification.created_at) }}</span>
        <span v-if="actionLabel && route" class="notification-item__action">{{ actionLabel }} →</span>
      </div>
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  formatNotificationDate,
  getNotificationRoute,
  getNotificationMeta,
  getNotificationActionLabel,
  useNotifications,
} from '@/composables/useNotifications'

const props = defineProps({
  notification: { type: Object, required: true },
  compact: { type: Boolean, default: false },
})

const emit = defineEmits(['opened', 'read'])

const router = useRouter()
const { markOneAsRead } = useNotifications()

const route = computed(() => getNotificationRoute(props.notification))
const meta = computed(() => getNotificationMeta(props.notification))
const actionLabel = computed(() => getNotificationActionLabel(props.notification))
const clickable = computed(() => !!route.value || !props.notification.read_at)
const rootTag = computed(() => (clickable.value ? 'button' : 'div'))

const ICON_PATHS = {
  ppdb_registration: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
  teacher_mutation: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21l-3.5-3.5M21 15v6',
  student_mutation: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM18 8l2 2 4-4',
  alumni_destination: 'M22 10v6M2 10l10-5 10 5-10 5zM6 12v5c0 2 4 3 6 3s6-1 6-3v-5',
  feedback_ticket: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
  password_reset_request: 'M12 15v2M8 11V7a4 4 0 1 1 8 0v4M5 11h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z',
  broadcast: 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0',
  disposition: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8',
  academic_calendar_reminder: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
  academic_calendar_parent: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
  inventory: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z',
  lab: 'M9 3h6v7l5 9H4l5-9V3zM10 3V2M14 3V2',
  default: 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0',
}

const iconPath = computed(() => ICON_PATHS[props.notification.type] || ICON_PATHS.default)

async function handleClick() {
  if (!clickable.value) return

  if (!props.notification.read_at) {
    try {
      await markOneAsRead(props.notification.id, true)
      emit('read', props.notification.id)
    } catch {
      return
    }
  }

  if (route.value) {
    await router.push(route.value)
    emit('opened')
  }
}
</script>

<style scoped>
.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  text-align: left;
  color: inherit;
  font-family: inherit;
  transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
}

button.notification-item {
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
}

.notification-item--clickable:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.notification-item--unread {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.notification-item--compact {
  padding: 12px 14px;
  border-radius: 0;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  box-shadow: none;
}

.notification-item--compact:last-child {
  border-bottom: none;
}

.notification-item--compact:hover {
  background: #f8fafc;
  box-shadow: none;
}

.notification-item__icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-item--compact .notification-item__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
}

.notification-item__body {
  flex: 1;
  min-width: 0;
}

.notification-item__head {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

.notification-item__type {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.notification-item__dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #059669;
  flex-shrink: 0;
}

.notification-item__message {
  margin: 0 0 6px;
  font-size: 14px;
  color: #0f172a;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-item--compact .notification-item__message {
  font-size: 13px;
}

.notification-item__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
}

.notification-item__time {
  font-size: 12px;
  color: #94a3b8;
}

.notification-item__action {
  font-size: 12px;
  font-weight: 600;
  color: #059669;
}
</style>
