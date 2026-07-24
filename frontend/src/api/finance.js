import api from './index'

function downloadBlob(res, fallbackName) {
  const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8' })
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

export const financeApi = {
  getSummary(params) {
    return api.get('/v1/finance/summary', { params })
  },
}

export const financeFeeTypeApi = {
  getAll(params) {
    return api.get('/v1/finance/fee-types', { params })
  },
  get(id) {
    return api.get(`/v1/finance/fee-types/${id}`)
  },
  create(data) {
    return api.post('/v1/finance/fee-types', data)
  },
  update(id, data) {
    return api.put(`/v1/finance/fee-types/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/finance/fee-types/${id}`)
  },
}

export const financeInvoiceApi = {
  getAll(params) {
    return api.get('/v1/finance/invoices', { params })
  },
  export(params) {
    return api.get('/v1/finance/invoices/export', { params, responseType: 'blob' }).then((res) => {
      downloadBlob(res, 'keuangan-tagihan.csv')
      return res
    })
  },
  get(id) {
    return api.get(`/v1/finance/invoices/${id}`)
  },
  generate(data) {
    return api.post('/v1/finance/invoices/generate', data)
  },
  update(id, data) {
    return api.put(`/v1/finance/invoices/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/finance/invoices/${id}`)
  },
}

export const financePaymentApi = {
  getAll(params) {
    return api.get('/v1/finance/payments', { params })
  },
  export(params) {
    return api.get('/v1/finance/payments/export', { params, responseType: 'blob' }).then((res) => {
      downloadBlob(res, 'keuangan-pembayaran.csv')
      return res
    })
  },
  get(id) {
    return api.get(`/v1/finance/payments/${id}`)
  },
  create(data) {
    return api.post('/v1/finance/payments', data)
  },
  delete(id) {
    return api.delete(`/v1/finance/payments/${id}`)
  },
  /** Buka kwitansi PDF (DomPDF + kop resmi) di tab baru */
  openReceipt(id) {
    return api.get(`/v1/finance/payments/${id}/receipt`, { responseType: 'blob' }).then((res) => {
      const blob = res.data instanceof Blob
        ? res.data
        : new Blob([res.data], { type: 'application/pdf' })
      const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
      window.open(url, '_blank')
      setTimeout(() => URL.revokeObjectURL(url), 60_000)
      return res
    })
  },
}
