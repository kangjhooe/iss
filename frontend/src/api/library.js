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
  getEbookCategories(params = {}) {
    return api.get('/v1/library/ebooks/categories', { params })
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
    const formData = buildBookFormData(data)
    return api.post('/v1/library/books', formData)
  },
  updateBook(id, data) {
    const formData = buildBookFormData(data)
    formData.append('_method', 'PUT')
    return api.post(`/v1/library/books/${id}`, formData)
  },
  deleteBook(id) {
    return api.delete(`/v1/library/books/${id}`)
  },
  downloadBooksTemplate() {
    return api.get('/v1/library/books/import/template')
  },
  importBooks(books) {
    return api.post('/v1/library/books/import', { books })
  },
  exportBooksCsv(params = {}) {
    return api.get('/v1/library/books/export/csv', { params, responseType: 'blob' })
  },
  exportBooksPdf(params = {}) {
    return api.get('/v1/library/books/export/pdf', { params, responseType: 'blob' })
  },

  // ==================== Ebooks (siswa & staf) ====================
  getEbooks(params = {}) {
    return api.get('/v1/library/ebooks', { params })
  },
  /**
   * Ambil blob PDF ebook (untuk viewer). Cookie auth ikut terkirim.
   */
  getEbookBlob(bookId) {
    return api.get(`/v1/library/books/${bookId}/ebook`, {
      responseType: 'blob'
    })
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
  getTopEbooks(params = {}) {
    return api.get('/v1/library/reports/top-ebooks', { params })
  },
  getLoansByMonth(params = {}) {
    return api.get('/v1/library/reports/loans-by-month', { params })
  },
  getEbookViewsByMonth(params = {}) {
    return api.get('/v1/library/reports/ebook-views-by-month', { params })
  },
  exportLoansPdf(params = {}) {
    return api.get('/v1/library/reports/export/loans-pdf', { params, responseType: 'blob' })
  },
  exportFinesPdf(params = {}) {
    return api.get('/v1/library/reports/export/fines-pdf', { params, responseType: 'blob' })
  }
}

function buildBookFormData(data) {
  const formData = new FormData()
  Object.keys(data || {}).forEach((key) => {
    const value = data[key]
    if (value === undefined || value === null || value === '') return
    if ((key === 'cover' || key === 'ebook') && value instanceof File) {
      formData.append(key, value)
    } else if (key === 'remove_ebook') {
      formData.append(key, value ? '1' : '0')
    } else if (key === 'is_public_ebook') {
      formData.append(key, value ? '1' : '0')
    } else if (key !== 'cover' && key !== 'ebook') {
      formData.append(key, value)
    }
  })
  return formData
}
