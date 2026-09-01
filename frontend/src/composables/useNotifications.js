import { ref } from 'vue'
import { notificationsApi } from '@/api/notifications'

const unreadCount = ref(0)

const TYPE_META = {
  ppdb_registration: { label: 'PPDB', color: '#059669', bg: '#ecfdf5' },
  teacher_mutation: { label: 'Mutasi Guru', color: '#7c3aed', bg: '#f5f3ff' },
  student_mutation: { label: 'Mutasi Siswa', color: '#2563eb', bg: '#eff6ff' },
  alumni_destination: { label: 'Alumni', color: '#0891b2', bg: '#ecfeff' },
  feedback_ticket: { label: 'Feedback', color: '#ea580c', bg: '#fff7ed' },
  password_reset_request: { label: 'Reset Password', color: '#dc2626', bg: '#fef2f2' },
  broadcast: { label: 'Pengumuman', color: '#4f46e5', bg: '#eef2ff' },
  disposition: { label: 'Disposisi', color: '#0d9488', bg: '#f0fdfa' },
  academic_calendar_reminder: { label: 'Kalender', color: '#ca8a04', bg: '#fefce8' },
  academic_calendar_parent: { label: 'Kalender', color: '#ca8a04', bg: '#fefce8' },
  inventory: { label: 'Inventaris', color: '#059669', bg: '#ecfdf5' },
  lab: { label: 'Laboratorium', color: '#9333ea', bg: '#faf5ff' },
}

export function formatNotificationDate(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  const now = new Date()
  const diff = now - d
  if (diff < 60000) return 'Baru saja'
  if (diff < 3600000) return `${Math.floor(diff / 60000)} menit lalu`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} jam lalu`
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function getNotificationRoute(notification) {
  const d = notification.data || {}
  switch (notification.type) {
    case 'ppdb_registration':
      return d.applicant_id
        ? `/ppdb/pendaftar/${d.applicant_id}`
        : (d.link || '/ppdb/pendaftar')
    case 'teacher_mutation':
      return '/teacher-mutation'
    case 'student_mutation':
      return '/student-mutation'
    case 'alumni_destination':
      return d.link || '/alumni'
    case 'feedback_ticket':
      return '/feedback'
    case 'password_reset_request':
      return '/super-admin/institution-admins'
    case 'disposition':
      return d.correspondence_id
        ? { path: '/correspondence/workflow', query: { highlight: d.correspondence_id } }
        : '/correspondence/workflow'
    case 'academic_calendar_reminder':
    case 'academic_calendar_parent':
      return '/academic-calendar'
    case 'inventory':
      if (d.loan_id) return '/inventory/peminjaman'
      if (d.item_id) return '/inventory/barang'
      return '/inventory/beranda'
    case 'lab':
      return d.booking_id ? '/lab-booking' : '/lab'
    case 'broadcast':
      return null
    default:
      return d.link || null
  }
}

export function getNotificationMeta(notification) {
  return TYPE_META[notification.type] || {
    label: 'Notifikasi',
    color: '#64748b',
    bg: '#f1f5f9',
  }
}

export function getNotificationActionLabel(notification) {
  const d = notification.data || {}
  switch (notification.type) {
    case 'ppdb_registration':
      return d.action === 're_registration' ? 'Buka daftar ulang' : 'Buka PPDB'
    case 'teacher_mutation':
      return 'Buka Mutasi Guru'
    case 'student_mutation':
      return 'Buka Mutasi Siswa'
    case 'alumni_destination':
      return 'Buka Alumni'
    case 'feedback_ticket':
      return 'Buka Feedback'
    case 'password_reset_request':
      return 'Buka permintaan reset'
    case 'disposition':
      return 'Buka disposisi'
    case 'academic_calendar_reminder':
    case 'academic_calendar_parent':
      return 'Buka kalender'
    case 'inventory':
      return 'Buka inventaris'
    case 'lab':
      return 'Buka laboratorium'
    case 'broadcast':
      return null
    default:
      return d.link ? 'Buka detail' : null
  }
}

export function useNotifications() {
  async function refreshUnreadCount() {
    try {
      const res = await notificationsApi.getUnreadCount()
      unreadCount.value = res.data?.count ?? 0
    } catch {
      unreadCount.value = 0
    }
  }

  async function markOneAsRead(id, wasUnread = true) {
    await notificationsApi.markAsRead(id)
    if (wasUnread && unreadCount.value > 0) {
      unreadCount.value--
    }
  }

  async function markAllAsRead() {
    await notificationsApi.markAllAsRead()
    unreadCount.value = 0
  }

  return {
    unreadCount,
    refreshUnreadCount,
    markOneAsRead,
    markAllAsRead,
    formatNotificationDate,
    getNotificationRoute,
    getNotificationMeta,
    getNotificationActionLabel,
  }
}
