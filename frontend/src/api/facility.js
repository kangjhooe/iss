import api from './index'

/**
 * Facility API client untuk mengelola sarana prasarana (tanah, gedung, ruangan)
 * @namespace facilityApi
 */
export const facilityApi = {
  // ==================== Land (Tanah) ====================
  
  /**
   * Mendapatkan daftar tanah dengan filter opsional
   * @param {Object} [params] - Parameter query (search, status, dll)
   * @returns {Promise} Response dari API
   */
  getLands(params) {
    return api.get('/v1/facility/lands', { params })
  },

  /**
   * Mendapatkan detail tanah berdasarkan ID
   * @param {number|string} id - ID tanah
   * @returns {Promise} Response dari API
   */
  getLand(id) {
    return api.get(`/v1/facility/lands/${id}`)
  },

  /**
   * Membuat data tanah baru
   * @param {Object} data - Data tanah yang akan dibuat
   * @returns {Promise} Response dari API
   */
  createLand(data) {
    return api.post('/v1/facility/lands', data)
  },

  /**
   * Memperbarui data tanah
   * @param {number|string} id - ID tanah yang akan diperbarui
   * @param {Object} data - Data tanah yang baru
   * @returns {Promise} Response dari API
   */
  updateLand(id, data) {
    return api.put(`/v1/facility/lands/${id}`, data)
  },

  /**
   * Menghapus data tanah
   * @param {number|string} id - ID tanah yang akan dihapus
   * @returns {Promise} Response dari API
   */
  deleteLand(id) {
    return api.delete(`/v1/facility/lands/${id}`)
  },

  // ==================== Building (Gedung) ====================
  
  /**
   * Mendapatkan daftar gedung dengan filter opsional
   * @param {Object} [params] - Parameter query (search, status, dll)
   * @returns {Promise} Response dari API
   */
  getBuildings(params) {
    return api.get('/v1/facility/buildings', { params })
  },

  /**
   * Mendapatkan detail gedung berdasarkan ID
   * @param {number|string} id - ID gedung
   * @returns {Promise} Response dari API
   */
  getBuilding(id) {
    return api.get(`/v1/facility/buildings/${id}`)
  },

  /**
   * Membuat data gedung baru
   * @param {Object} data - Data gedung yang akan dibuat
   * @returns {Promise} Response dari API
   */
  createBuilding(data) {
    return api.post('/v1/facility/buildings', data)
  },

  /**
   * Memperbarui data gedung
   * @param {number|string} id - ID gedung yang akan diperbarui
   * @param {Object} data - Data gedung yang baru
   * @returns {Promise} Response dari API
   */
  updateBuilding(id, data) {
    return api.put(`/v1/facility/buildings/${id}`, data)
  },

  /**
   * Menghapus data gedung
   * @param {number|string} id - ID gedung yang akan dihapus
   * @returns {Promise} Response dari API
   */
  deleteBuilding(id) {
    return api.delete(`/v1/facility/buildings/${id}`)
  },

  // ==================== Room (Ruangan) ====================
  
  /**
   * Mendapatkan daftar ruangan dengan filter opsional
   * @param {Object} [params] - Parameter query (search, type, building_id, dll)
   * @returns {Promise} Response dari API
   */
  getRooms(params) {
    return api.get('/v1/facility/rooms', { params })
  },

  /**
   * Mendapatkan detail ruangan berdasarkan ID
   * @param {number|string} id - ID ruangan
   * @returns {Promise} Response dari API
   */
  getRoom(id) {
    return api.get(`/v1/facility/rooms/${id}`)
  },

  /**
   * Membuat data ruangan baru
   * @param {Object} data - Data ruangan yang akan dibuat
   * @returns {Promise} Response dari API
   */
  createRoom(data) {
    return api.post('/v1/facility/rooms', data)
  },

  /**
   * Memperbarui data ruangan
   * @param {number|string} id - ID ruangan yang akan diperbarui
   * @param {Object} data - Data ruangan yang baru
   * @returns {Promise} Response dari API
   */
  updateRoom(id, data) {
    return api.put(`/v1/facility/rooms/${id}`, data)
  },

  /**
   * Menghapus data ruangan
   * @param {number|string} id - ID ruangan yang akan dihapus
   * @returns {Promise} Response dari API
   */
  deleteRoom(id) {
    return api.delete(`/v1/facility/rooms/${id}`)
  },

  // ==================== Helper Methods ====================
  
  /**
   * Mendapatkan ruangan berdasarkan jenis (type)
   * @param {string} type - Jenis ruangan (contoh: 'Kelas', 'Laboratorium', dll)
   * @param {Object} [params] - Parameter query tambahan
   * @returns {Promise} Response dari API
   */
  getRoomsByType(type, params = {}) {
    return api.get('/v1/facility/rooms', { 
      params: { ...params, type } 
    })
  },

  /**
   * Mendapatkan ruangan berdasarkan gedung
   * @param {number|string} buildingId - ID gedung
   * @param {Object} [params] - Parameter query tambahan
   * @returns {Promise} Response dari API
   */
  getRoomsByBuilding(buildingId, params = {}) {
    return api.get('/v1/facility/rooms', { 
      params: { ...params, building_id: buildingId } 
    })
  },

  /**
   * Export laporan sarana prasarana (PDF) — tanah, gedung, ruangan.
   */
  exportPdf(params = {}) {
    return api.get('/v1/facility/export/pdf', { params, responseType: 'blob' })
  },

  /**
   * Laporan khusus lab: ringkasan dan daftar lab dengan jumlah inventaris & jadwal.
   */
  getLabReport(params = {}) {
    return api.get('/v1/facility/lab-report', { params })
  },

  /**
   * Lab yang menjadi tanggung jawab user saat ini (Kepala Lab).
   */
  getMyLabs() {
    return api.get('/v1/facility/my-labs')
  },

  /**
   * Export laporan lab (semua lab atau satu lab).
   */
  exportLabReport(params = {}) {
    return api.get('/v1/facility/lab-report/export', { params, responseType: 'blob' })
  },

  exportLab(id, params = {}) {
    return api.get(`/v1/facility/labs/${id}/export`, { params, responseType: 'blob' })
  },

  // ==================== Lab Booking ====================
  getLabBookings(params = {}) {
    return api.get('/v1/facility/lab-bookings', { params })
  },
  createLabBooking(data) {
    return api.post('/v1/facility/lab-bookings', data)
  },
  approveLabBooking(id, data = {}) {
    return api.post(`/v1/facility/lab-bookings/${id}/approve`, data)
  },
  rejectLabBooking(id, data = {}) {
    return api.post(`/v1/facility/lab-bookings/${id}/reject`, data)
  },
  cancelLabBooking(id) {
    return api.post(`/v1/facility/lab-bookings/${id}/cancel`)
  },

  // Booking terbuka untuk guru (tanpa modul facility)
  getLabsForBooking(params = {}) {
    return api.get('/v1/lab-booking/labs', { params })
  },
  getOpenLabBookings(params = {}) {
    return api.get('/v1/lab-booking', { params })
  },
  createOpenLabBooking(data) {
    return api.post('/v1/lab-booking', data)
  },
  cancelOpenLabBooking(id) {
    return api.post(`/v1/lab-booking/${id}/cancel`)
  },

  // ==================== Lab Usage Journal ====================
  getLabJournals(params = {}) {
    return api.get('/v1/facility/lab-journals', { params })
  },
  createLabJournal(data) {
    return api.post('/v1/facility/lab-journals', data)
  },
  updateLabJournal(id, data) {
    return api.put(`/v1/facility/lab-journals/${id}`, data)
  },
  deleteLabJournal(id) {
    return api.delete(`/v1/facility/lab-journals/${id}`)
  }
}
