import api from './index'

/**
 * Library API client (Perpustakaan)
 * Endpoint prefix: /api/v1/library/...
 */
export const libraryApi = {
  // ==================== Categories ====================
  getCategories(params = {}) {
    return api.get('/v1/library/categories', { params })
  },
  getCategory(id) {
    return api.get(`/v1/library/categories/${id}`)
  },
  createCategory(data) {
    return api.post('/v1/library/categories', data)
  },
  updateCategory(id, data) {
    return api.put(`/v1/library/categories/${id}`, data)
  },
  deleteCategory(id) {
    return api.delete(`/v1/library/categories/${id}`)
  },

  // ==================== Books ====================
  getBooks(params = {}) {
    return api.get('/v1/library/books', { params })
  },
  getBook(id) {
    return api.get(`/v1/library/books/${id}`)
  },
  createBook(data) {
    const formData = new FormData()
    Object.keys(data || {}).forEach((key) => {
      const value = data[key]
      if (value === undefined || value === null || value === '') return
      if (key === 'cover' && value && value instanceof File) {
        formData.append('cover', value)
      } else {
        formData.append(key, value)
      }
    })
    return api.post('/v1/library/books', formData)
  },
  updateBook(id, data) {
    const formData = new FormData()
    Object.keys(data || {}).forEach((key) => {
      const value = data[key]
      if (value === undefined || value === null || value === '') return
      if (key === 'cover' && value && value instanceof File) {
        formData.append('cover', value)
      } else {
        formData.append(key, value)
      }
    })
    return api.put(`/v1/library/books/${id}`, formData)
  },
  deleteBook(id) {
    return api.delete(`/v1/library/books/${id}`)
  },

  // ==================== Copies ====================
  getCopies(params = {}) {
    return api.get('/v1/library/copies', { params })
  },
  getCopy(id) {
    return api.get(`/v1/library/copies/${id}`)
  },
  createCopy(data) {
    return api.post('/v1/library/copies', data)
  },
  updateCopy(id, data) {
    return api.put(`/v1/library/copies/${id}`, data)
  },
  deleteCopy(id) {
    return api.delete(`/v1/library/copies/${id}`)
  },

  // ==================== Loans ====================
  getLoans(params = {}) {
    return api.get('/v1/library/loans', { params })
  },
  getLoan(id) {
    return api.get(`/v1/library/loans/${id}`)
  },
  createLoan(data) {
    return api.post('/v1/library/loans', data)
  },
  returnLoan(id, data = {}) {
    return api.post(`/v1/library/loans/${id}/return`, data)
  },
  calculateFine(id) {
    return api.get(`/v1/library/loans/${id}/calculate-fine`)
  },
  renewLoan(id, data = {}) {
    return api.post(`/v1/library/loans/${id}/renew`, data)
  },

  // ==================== Fine Payments ====================
  getFinePayments(params = {}) {
    return api.get('/v1/library/fine-payments', { params })
  },
  createFinePayment(data) {
    return api.post('/v1/library/fine-payments', data)
  },

  // ==================== Reports ====================
  getStatistics(params = {}) {
    return api.get('/v1/library/reports/statistics', { params })
  },
  getTopBooks(params = {}) {
    return api.get('/v1/library/reports/top-books', { params })
  },
  getLoansByMonth(params = {}) {
    return api.get('/v1/library/reports/loans-by-month', { params })
  },
  exportLoansPdf(params = {}) {
    return api.get('/v1/library/reports/export/loans-pdf', { params, responseType: 'blob' })
  }
}
