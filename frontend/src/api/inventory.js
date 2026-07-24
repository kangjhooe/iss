import api from './index'

/**
 * Inventory API client (Inventaris)
 * Endpoint prefix: /api/v1/inventory/...
 */
export const inventoryApi = {
  // ==================== Categories ====================
  getCategories(params = {}) {
    return api.get('/v1/inventory/categories', { params })
  },
  getCategory(id) {
    return api.get(`/v1/inventory/categories/${id}`)
  },
  createCategory(data) {
    return api.post('/v1/inventory/categories', data)
  },
  updateCategory(id, data) {
    return api.put(`/v1/inventory/categories/${id}`, data)
  },
  deleteCategory(id) {
    return api.delete(`/v1/inventory/categories/${id}`)
  },

  // ==================== Items ====================
  getItems(params = {}) {
    return api.get('/v1/inventory/items', { params })
  },
  getItem(id) {
    return api.get(`/v1/inventory/items/${id}`)
  },
  createItem(data) {
    const formData = new FormData()
    Object.keys(data || {}).forEach((key) => {
      const value = data[key]
      if (value === undefined || value === null || value === '') return
      if (key === 'image' && value) {
        formData.append('image', value)
      } else {
        formData.append(key, value)
      }
    })
    return api.post('/v1/inventory/items', formData)
  },
  updateItem(id, data) {
    const formData = new FormData()
    Object.keys(data || {}).forEach((key) => {
      const value = data[key]
      if (value === undefined || value === null || value === '') return
      if (key === 'image' && value) {
        formData.append('image', value)
      } else {
        formData.append(key, value)
      }
    })
    return api.post(`/v1/inventory/items/${id}?_method=PUT`, formData)
  },
  deleteItem(id) {
    return api.delete(`/v1/inventory/items/${id}`)
  },

  // ==================== Transactions ====================
  getTransactions(params = {}) {
    return api.get('/v1/inventory/transactions', { params })
  },
  getTransaction(id) {
    return api.get(`/v1/inventory/transactions/${id}`)
  },
  createTransaction(data) {
    return api.post('/v1/inventory/transactions', data)
  },

  // ==================== Maintenances ====================
  getMaintenances(params = {}) {
    return api.get('/v1/inventory/maintenances', { params })
  },
  getMaintenance(id) {
    return api.get(`/v1/inventory/maintenances/${id}`)
  },
  createMaintenance(data) {
    return api.post('/v1/inventory/maintenances', data)
  },
  updateMaintenance(id, data) {
    return api.put(`/v1/inventory/maintenances/${id}`, data)
  },

  // ==================== Loans ====================
  getLoans(params = {}) {
    return api.get('/v1/inventory/loans', { params })
  },
  getLoan(id) {
    return api.get(`/v1/inventory/loans/${id}`)
  },
  createLoan(data) {
    return api.post('/v1/inventory/loans', data)
  },
  returnLoan(id, data = {}) {
    return api.post(`/v1/inventory/loans/${id}/return`, data)
  },

  // ==================== Reports ====================
  getReportStatistics(params = {}) {
    return api.get('/v1/inventory/reports/statistics', { params })
  },
  getReportByCategory(params = {}) {
    return api.get('/v1/inventory/reports/by-category', { params })
  },
  getReportByLocation(params = {}) {
    return api.get('/v1/inventory/reports/by-location', { params })
  },
  getReportDamagedMissing(params = {}) {
    return api.get('/v1/inventory/reports/damaged-missing', { params })
  },
  getReportLoaned(params = {}) {
    return api.get('/v1/inventory/reports/loaned', { params })
  },
  getReportAssetValue(params = {}) {
    return api.get('/v1/inventory/reports/asset-value', { params })
  },
  getReportMaintenance(params = {}) {
    return api.get('/v1/inventory/reports/maintenance', { params })
  },
  getReportTransactions(params = {}) {
    return api.get('/v1/inventory/reports/transactions', { params })
  },
  exportReportPdf(params = {}) {
    return api.get('/v1/inventory/reports/export/pdf', { params, responseType: 'blob' })
  }
}

