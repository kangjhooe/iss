export const componentTypeOptions = [
  { value: 'earning', label: 'Pendapatan' },
  { value: 'deduction', label: 'Potongan' },
]

export const calcModeOptions = [
  { value: 'fixed', label: 'Nominal tetap' },
  { value: 'per_alpha_day', label: 'Per hari alpha / cuti tanpa gaji' },
  { value: 'manual', label: 'Manual per pegawai' },
  { value: 'structural_position', label: 'Tunjangan jabatan struktural' },
  { value: 'thr', label: 'THR (gaji pokok × pengali)' },
]

export const runStatusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'finalized', label: 'Final' },
  { value: 'paid', label: 'Dibayar' },
]

export const periodStatusOptions = [
  { value: 'open', label: 'Terbuka' },
  { value: 'closed', label: 'Ditutup' },
]

export const typeLabel = (v) => componentTypeOptions.find((o) => o.value === v)?.label || v || '—'
export const calcModeLabel = (v) => calcModeOptions.find((o) => o.value === v)?.label || v || '—'
export const runStatusLabel = (v) => runStatusOptions.find((o) => o.value === v)?.label || v || '—'

export function formatRp(value) {
  const n = Number(value || 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

export function currentPayrollPeriod() {
  const d = new Date()
  return { year: d.getFullYear(), month: d.getMonth() + 1 }
}

export const monthOptions = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
]

export function apiError(e, fallback = 'Terjadi kesalahan.') {
  return e?.formattedMessage
    || e?.response?.data?.message
    || Object.values(e?.response?.data?.errors || {})?.[0]?.[0]
    || fallback
}
