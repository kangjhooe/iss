import api from './index'

function downloadExcelBlob(res, fallbackName) {
  const blob = new Blob([res.data], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  const disposition = res.headers?.['content-disposition'] || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)
  a.download = match?.[1] || fallbackName
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}

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
  getImportTemplate() {
    return api.get('/v1/inventory/items/import/template')
  },
  importItems(rows) {
    return api.post('/v1/inventory/items/import', { rows })
  },
  exportItemsExcel(params = {}) {
    return api.get('/v1/inventory/items/export/excel', { params, responseType: 'blob' }).then((res) => {
      downloadExcelBlob(res, `Master_Barang_Inventaris_${new Date().toISOString().slice(0, 10)}.xlsx`)
      return res
    })
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
  restoreItem(id) {
    return api.post(`/v1/inventory/items/${id}/restore`)
  },
  disposeItem(id, data) {
    return api.post(`/v1/inventory/items/${id}/dispose`, data)
  },
  disposeAsset(id, data) {
    return api.post(`/v1/inventory/assets/${id}/dispose`, data)
  },
  getDisposals(params = {}) {
    return api.get('/v1/inventory/disposals', { params })
  },
  updateDisposal(id, data) {
    return api.put(`/v1/inventory/disposals/${id}`, data)
  },
  uploadDisposalDocument(id, file) {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/v1/inventory/disposals/${id}/document`, formData)
  },
  deleteDisposalDocument(id) {
    return api.delete(`/v1/inventory/disposals/${id}/document`)
  },
  deleteDisposal(id) {
    return api.delete(`/v1/inventory/disposals/${id}`)
  },
  exportKib(id) {
    return api.get(`/v1/inventory/items/${id}/export/kib`, { responseType: 'blob' })
  },

  // ==================== Assets (Aset Individual) ====================
  getAssets(params = {}) {
    return api.get('/v1/inventory/assets', { params })
  },
  getAsset(id) {
    return api.get(`/v1/inventory/assets/${id}`)
  },
  createAsset(data) {
    return api.post('/v1/inventory/assets', data)
  },
  updateAsset(id, data) {
    return api.put(`/v1/inventory/assets/${id}`, data)
  },
  getAssetQr(id) {
    return api.get(`/v1/inventory/assets/${id}/qr`)
  },
  resolveAssetQr(token) {
    return api.get('/v1/inventory/assets/resolve-qr', { params: { token } })
  },
  splitItemAssets(itemId, count) {
    return api.post(`/v1/inventory/items/${itemId}/split-assets`, { count })
  },
  transferAsset(id, data) {
    return api.post(`/v1/inventory/assets/${id}/transfer`, data)
  },
  exportAssetKib(id) {
    return api.get(`/v1/inventory/assets/${id}/export/kib`, { responseType: 'blob' })
  },
  getAssetQrBulk(data) {
    return api.post('/v1/inventory/assets/qr/bulk', data)
  },
  printAssetQrPdf(data) {
    return api.post('/v1/inventory/assets/qr/print-pdf', data, { responseType: 'blob' })
  },
  getAssetMovements(params = {}) {
    return api.get('/v1/inventory/asset-movements', { params })
  },

  // ==================== Stock Opname ====================
  getStockOpnames(params = {}) {
    return api.get('/v1/inventory/stock-opnames', { params })
  },
  getStockOpname(id) {
    return api.get(`/v1/inventory/stock-opnames/${id}`)
  },
  createStockOpname(data) {
    return api.post('/v1/inventory/stock-opnames', data)
  },
  refreshStockOpnameLines(id) {
    return api.post(`/v1/inventory/stock-opnames/${id}/refresh-lines`)
  },
  updateStockOpnameLine(opnameId, lineId, data) {
    return api.put(`/v1/inventory/stock-opnames/${opnameId}/lines/${lineId}`, data)
  },
  updateAssetOpnameLine(opnameId, lineId, data) {
    return api.put(`/v1/inventory/stock-opnames/${opnameId}/asset-lines/${lineId}`, data)
  },
  finalizeStockOpname(id) {
    return api.post(`/v1/inventory/stock-opnames/${id}/finalize`)
  },
  cancelStockOpname(id) {
    return api.post(`/v1/inventory/stock-opnames/${id}/cancel`)
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
  updateLoan(id, data) {
    return api.put(`/v1/inventory/loans/${id}`, data)
  },
  returnLoan(id, data = {}) {
    return api.post(`/v1/inventory/loans/${id}/return`, data)
  },

  // ==================== Reports ====================
  getReportStatistics(params = {}) {
    return api.get('/v1/inventory/reports/statistics', { params })
  },
  getReportStock(params = {}) {
    return api.get('/v1/inventory/reports/stock', { params })
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
  getReportAssetMovements(params = {}) {
    return api.get('/v1/inventory/reports/asset-movements', { params })
  },
  getReportDisposed(params = {}) {
    return api.get('/v1/inventory/reports/disposed', { params })
  },
  exportReportPdf(params = {}) {
    return api.get('/v1/inventory/reports/export/pdf', { params, responseType: 'blob' })
  },
  exportReportExcel(params = {}) {
    return api.get('/v1/inventory/reports/export/excel', { params, responseType: 'blob' }).then((res) => {
      const type = params.type || 'summary'
      downloadExcelBlob(res, `Laporan_Inventaris_${type}_${new Date().toISOString().slice(0, 10)}.xlsx`)
      return res
    })
  }
}

