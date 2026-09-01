export function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return String(dateString)
  return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

export function formatCurrency(num) {
  if (num == null || num === '') return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(num)
}

export function getConditionClass(condition) {
  const map = {
    Baik: 'badge-success',
    'Rusak Ringan': 'badge-warning',
    'Rusak Berat': 'badge-danger',
    'Habis Pakai': 'badge-gray'
  }
  return map[condition] || 'badge-gray'
}

export function getStatusClass(status) {
  const map = {
    Tersedia: 'badge-success',
    Dipinjam: 'badge-info',
    Rusak: 'badge-warning',
    Hilang: 'badge-danger',
    Dijual: 'badge-gray'
  }
  return map[status] || 'badge-gray'
}

export function getTransactionTypeClass(type) {
  const map = {
    Masuk: 'badge-success',
    Keluar: 'badge-danger',
    Mutasi: 'badge-info',
    Penyesuaian: 'badge-warning'
  }
  return map[type] || 'badge-gray'
}

export function getMaintenanceStatusClass(status) {
  const map = {
    Terjadwal: 'badge-info',
    'Dalam Proses': 'badge-warning',
    Selesai: 'badge-success',
    Dibatalkan: 'badge-gray'
  }
  return map[status] || 'badge-gray'
}

export function itemLocationLabel(item) {
  if (item?.room?.name) return item.room.name
  if (item?.building?.name) return item.building.name
  return item?.location_note || '-'
}

export const LOAN_DURATION_PRESETS = [
  { label: '3 hari', days: 3 },
  { label: '7 hari', days: 7 },
  { label: '14 hari', days: 14 },
  { label: '1 bulan', days: 30 }
]

export function addDaysToDateString(dateStr, days) {
  if (!dateStr) return ''
  const d = new Date(`${dateStr}T12:00:00`)
  if (Number.isNaN(d.getTime())) return ''
  d.setDate(d.getDate() + days)
  return d.toISOString().split('T')[0]
}

export function todayDateString() {
  return new Date().toISOString().split('T')[0]
}
