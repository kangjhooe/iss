export const INVENTORY_FUNDING_SOURCES = [
  'BOS',
  'BOSDA',
  'APBD',
  'APBN',
  'Yayasan',
  'Donatur',
  'Mandiri',
  'Hibah',
  'Swadaya',
  'Dana Komite',
  'Lainnya'
]

export const INVENTORY_TRACKING_TYPES = [
  { value: 'stock', label: 'Stok / Kuantitas' },
  { value: 'individual', label: 'Aset Individual' }
]

export const INVENTORY_OWNERSHIP_TYPES = [
  'Negara',
  'Pemerintah Daerah',
  'Yayasan',
  'Satuan Pendidikan',
  'Hibah',
  'Pihak Lain',
  'Belum Ditentukan'
]

export const INVENTORY_ACQUISITION_METHODS = [
  'Pembelian',
  'Hibah',
  'Bantuan Pemerintah',
  'Bantuan Pemerintah Daerah',
  'Sumbangan',
  'Transfer',
  'Donasi',
  'Lainnya'
]

export function trackingTypeLabel(value) {
  return INVENTORY_TRACKING_TYPES.find((t) => t.value === value)?.label || value || 'Stok'
}
