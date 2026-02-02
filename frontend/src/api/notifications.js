import api from './index'

export const notificationsApi = {
  getList(params) {
    return api.get('/v1/notifications', { params })
  },
  getUnreadCount() {
    return api.get('/v1/notifications/unread-count')
  },
  markAsRead(id) {
    return api.post(`/v1/notifications/${id}/read`)
  },
  markAllAsRead() {
    return api.post('/v1/notifications/read-all')
  }
}
