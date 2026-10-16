export const frequencyOptions = [
  { value: 'monthly', label: 'Bulanan (rutin)' },
  { value: 'yearly', label: 'Tahunan' },
  { value: 'one_time', label: 'Sekali' },
  { value: 'as_needed', label: 'Sesekali / sesuai kebutuhan' },
]

export const scopeOptions = [
  { value: 'school', label: 'Sekolah' },
  { value: 'class', label: 'Kelas' },
  { value: 'student', label: 'Siswa' },
]

export const invoiceStatusOptions = [
  { value: 'unpaid', label: 'Belum bayar' },
  { value: 'partial', label: 'Sebagian' },
  { value: 'paid', label: 'Lunas' },
  { value: 'cancelled', label: 'Dibatalkan' },
]

export const paymentMethodOptions = [
  { value: 'cash', label: 'Tunai' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'other', label: 'Lainnya' },
]

export const expenseCategoryOptions = [
  { value: 'payroll', label: 'Gaji pegawai' },
  { value: 'other', label: 'Lainnya' },
]

export const expenseSourceOptions = [
  { value: 'auto', label: 'Otomatis (penggajian)' },
  { value: 'manual', label: 'Manual' },
]

export const frequencyLabel = (v) => frequencyOptions.find((o) => o.value === v)?.label || v || '—'
export const scopeLabel = (v) => scopeOptions.find((o) => o.value === v)?.label || v || '—'
export const statusLabel = (v) => invoiceStatusOptions.find((o) => o.value === v)?.label || v || '—'
export const methodLabel = (v) => paymentMethodOptions.find((o) => o.value === v)?.label || v || '—'
export const expenseCategoryLabel = (v) => expenseCategoryOptions.find((o) => o.value === v)?.label || v || '—'
export const expenseSourceLabel = (v) => expenseSourceOptions.find((o) => o.value === v)?.label || v || '—'

export function formatRp(value) {
  const n = Number(value || 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

export function currentPeriodLabel() {
  const d = new Date()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `${d.getFullYear()}-${m}`
}

export const feeTypePresets = [
  { name: 'SPP', code: 'SPP', frequency: 'monthly', scope: 'school', default_amount: 150000 },
  { name: 'Kas Kelas', code: 'KAS', frequency: 'as_needed', scope: 'class', default_amount: 10000 },
  { name: 'Iuran Kegiatan', code: 'IURAN', frequency: 'one_time', scope: 'school', default_amount: 50000 },
  { name: 'Seragam', code: 'SERAGAM', frequency: 'one_time', scope: 'school', default_amount: 250000 },
]

/**
 * Cetak kwitansi via PDF DomPDF (kop + TTD), sama pola laporan lain.
 * @param {number|string|{id:number}} paymentOrId
 */
export async function printPaymentReceipt(paymentOrId) {
  const { financePaymentApi } = await import('@/api/finance')
  const id = typeof paymentOrId === 'object' ? paymentOrId?.id : paymentOrId
  if (!id) return
  await financePaymentApi.openReceipt(id)
}

