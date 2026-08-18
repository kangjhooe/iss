import api from './index'

export const pklApi = {
  getPeriods(params) {
    return api.get('/v1/pkl/periods', { params })
  },
  createPeriod(data) {
    return api.post('/v1/pkl/periods', data)
  },
  updatePeriod(id, data) {
    return api.put(`/v1/pkl/periods/${id}`, data)
  },
  deletePeriod(id) {
    return api.delete(`/v1/pkl/periods/${id}`)
  },
  getPlacements(params) {
    return api.get('/v1/pkl/placements', { params })
  },
  exportPlacements(params) {
    return api.get('/v1/pkl/placements/export', { params, responseType: 'blob' })
  },
  bulkCreatePlacements(data) {
    return api.post('/v1/pkl/placements/bulk', data)
  },
  getPlacement(id) {
    return api.get(`/v1/pkl/placements/${id}`)
  },
  createPlacement(data) {
    return api.post('/v1/pkl/placements', data)
  },
  updatePlacement(id, data) {
    return api.put(`/v1/pkl/placements/${id}`, data)
  },
  deletePlacement(id) {
    return api.delete(`/v1/pkl/placements/${id}`)
  },
  getMonitoringLogs(placementId) {
    return api.get(`/v1/pkl/placements/${placementId}/monitoring-logs`)
  },
  createMonitoringLog(placementId, data) {
    return api.post(`/v1/pkl/placements/${placementId}/monitoring-logs`, data)
  },
  deleteMonitoringLog(placementId, logId) {
    return api.delete(`/v1/pkl/placements/${placementId}/monitoring-logs/${logId}`)
  },
  getJournals(placementId) {
    return api.get(`/v1/pkl/placements/${placementId}/journals`)
  },
  updateJournalNotes(placementId, journalId, data) {
    return api.put(`/v1/pkl/placements/${placementId}/journals/${journalId}`, data)
  },
  // Portal siswa
  myPlacements() {
    return api.get('/v1/pkl/my/placements')
  },
  myPlacement(placementId) {
    return api.get(`/v1/pkl/my/placements/${placementId}`)
  },
  myJournals(placementId, params) {
    return api.get(`/v1/pkl/my/placements/${placementId}/journals`, { params })
  },
  createMyJournal(placementId, data) {
    return api.post(`/v1/pkl/my/placements/${placementId}/journals`, data)
  },
  updateMyJournal(placementId, journalId, data) {
    return api.put(`/v1/pkl/my/placements/${placementId}/journals/${journalId}`, data)
  },
  deleteMyJournal(placementId, journalId) {
    return api.delete(`/v1/pkl/my/placements/${placementId}/journals/${journalId}`)
  },
}
