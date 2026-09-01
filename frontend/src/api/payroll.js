import api from './index'

export const payrollComponentApi = {
  getAll(params) {
    return api.get('/v1/payroll/components', { params })
  },
  create(data) {
    return api.post('/v1/payroll/components', data)
  },
  update(id, data) {
    return api.put(`/v1/payroll/components/${id}`, data)
  },
  delete(id) {
    return api.delete(`/v1/payroll/components/${id}`)
  },
}

export const payrollPositionAllowanceApi = {
  getAll() {
    return api.get('/v1/payroll/position-allowances')
  },
  sync(items) {
    return api.put('/v1/payroll/position-allowances', { items })
  },
}

export const payrollProfileApi = {
  getAll(params) {
    return api.get('/v1/payroll/employee-profiles', { params })
  },
  employeesLite() {
    return api.get('/v1/payroll/employee-profiles/employees-lite')
  },
  create(data) {
    return api.post('/v1/payroll/employee-profiles', data)
  },
  update(id, data) {
    return api.put(`/v1/payroll/employee-profiles/${id}`, data)
  },
}

export const payrollPeriodApi = {
  getAll(params) {
    return api.get('/v1/payroll/periods', { params })
  },
  create(data) {
    return api.post('/v1/payroll/periods', data)
  },
  close(id) {
    return api.post(`/v1/payroll/periods/${id}/close`)
  },
}

export const payrollRunApi = {
  getAll(params) {
    return api.get('/v1/payroll/runs', { params })
  },
  get(id) {
    return api.get(`/v1/payroll/runs/${id}`)
  },
  generate(data) {
    return api.post('/v1/payroll/runs/generate', data)
  },
  slips(runId, params) {
    return api.get(`/v1/payroll/runs/${runId}/slips`, { params })
  },
  finalize(id) {
    return api.post(`/v1/payroll/runs/${id}/finalize`)
  },
  markPaid(id) {
    return api.post(`/v1/payroll/runs/${id}/mark-paid`)
  },
  unpay(id) {
    return api.post(`/v1/payroll/runs/${id}/unpay`)
  },
  reopen(id) {
    return api.post(`/v1/payroll/runs/${id}/reopen`)
  },
  delete(id) {
    return api.delete(`/v1/payroll/runs/${id}`)
  },
  exportExcel(id) {
    return api.get(`/v1/payroll/runs/${id}/export/excel`, { responseType: 'blob' })
  },
  exportPdf(id) {
    return api.get(`/v1/payroll/runs/${id}/export/pdf`, { responseType: 'blob' })
  },
}

export const payrollSlipApi = {
  get(id) {
    return api.get(`/v1/payroll/slips/${id}`)
  },
  update(id, data) {
    return api.put(`/v1/payroll/slips/${id}`, data)
  },
  pdf(id) {
    return api.get(`/v1/payroll/slips/${id}/pdf`, { responseType: 'blob' })
  },
}

export const payrollMyApi = {
  slips(params) {
    return api.get('/v1/payroll/my/slips', { params })
  },
  get(id) {
    return api.get(`/v1/payroll/my/slips/${id}`)
  },
  pdf(id) {
    return api.get(`/v1/payroll/my/slips/${id}/pdf`, { responseType: 'blob' })
  },
}

export function openPdfBlob(res, filename) {
  const blob = new Blob([res.data], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  window.open(url, '_blank', 'noopener,noreferrer')
  setTimeout(() => window.URL.revokeObjectURL(url), 60000)
}

export function downloadBlob(res, fallbackName, mimeType) {
  const blob = new Blob([res.data], { type: mimeType })
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
