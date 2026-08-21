export const ADDRESS_KEYS = [
  'address',
  'village',
  'sub_district',
  'district',
  'province',
  'postal_code',
  'wilayah_province_code',
  'wilayah_regency_code',
  'wilayah_district_code',
  'wilayah_village_code',
]

export function emptyAddress() {
  return Object.fromEntries(ADDRESS_KEYS.map((key) => [key, '']))
}

export function pickAddress(source = {}) {
  const out = emptyAddress()
  for (const key of ADDRESS_KEYS) {
    out[key] = source[key] ?? ''
  }
  return out
}

export function formatFullAddress(source = {}) {
  if (!source) return ''
  if (source.full_address) return source.full_address
  const parts = [
    source.address,
    source.village,
    source.sub_district ? `Kec. ${source.sub_district}` : '',
    source.district,
    source.province,
    source.postal_code,
  ].filter((part) => part != null && String(part).trim() !== '')
  return parts.join(', ')
}

const EXCEL_ADDRESS_ALIASES = {
  address: ['Alamat'],
  village: ['Desa/Kelurahan/Pekon', 'Desa/Kelurahan', 'Desa'],
  sub_district: ['Kecamatan'],
  district: ['Kabupaten/Kota', 'Kabupaten', 'Kota'],
  province: ['Provinsi'],
  postal_code: ['Kode Pos'],
}

function excelCellText(value) {
  if (value === undefined || value === null || value === '') return null
  const text = String(value).trim()
  return text === '' ? null : text
}

export function excelAddressColumns(source = {}) {
  return {
    'Alamat': source.address || '',
    'Desa/Kelurahan/Pekon': source.village || '',
    'Kecamatan': source.sub_district || '',
    'Kabupaten/Kota': source.district || '',
    'Provinsi': source.province || '',
    'Kode Pos': source.postal_code || '',
  }
}

export function pickAddressFromExcel(mapField) {
  const out = {}
  for (const [field, headers] of Object.entries(EXCEL_ADDRESS_ALIASES)) {
    let value = null
    for (const header of headers) {
      value = excelCellText(mapField(header))
      if (value) break
    }
    if (value) out[field] = value
  }
  return out
}
