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

const REGION_PREFIX = '(?:desa|kelurahan|pekon|kel\\.?|ds\\.?|kecamatan|kec\\.?|kabupaten|kab\\.?|kota|provinsi|prov\\.?)'

function escapeRegex(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

function normalizeRegionSegment(value) {
  let text = String(value ?? '').toLowerCase().trim()
  if (!text) return ''
  text = text.replace(/[.,]/g, ' ').replace(/\s+/g, ' ')
  text = text.replace(new RegExp(`^${REGION_PREFIX}\\s+`, 'i'), '')
  return text.trim()
}

function stripExtraQualifier(text) {
  return String(text || '').replace(/^(kota|kabupaten|kab|adm)\s+/i, '').trim()
}

function segmentMatchesRegion(segment, wanted) {
  return wanted.some((name) => {
    if (segment === name) return true
    const segmentCore = stripExtraQualifier(segment)
    const nameCore = stripExtraQualifier(name)
    return segmentCore === name || nameCore === segment || segmentCore === nameCore
  })
}

function stripOnePrefixedTrailingName(street, wanted) {
  for (const name of wanted) {
    if (!name || /^\d+$/.test(name)) continue
    const next = street.replace(new RegExp(`\\s+${REGION_PREFIX}\\s+${escapeRegex(name)}\\s*$`, 'i'), '')
    if (next !== street) return next.trimEnd()
  }
  return street
}

export function stripTrailingRegions(street, regionNames = []) {
  let result = String(street ?? '').trim()
  const wanted = regionNames.map(normalizeRegionSegment).filter(Boolean)
  if (!result || !wanted.length) return result

  const parts = result.split(',').map((part) => part.trim()).filter(Boolean)
  while (parts.length) {
    const last = normalizeRegionSegment(parts[parts.length - 1])
    if (!last || !segmentMatchesRegion(last, wanted)) break
    parts.pop()
  }
  result = parts.join(', ')

  let changed = true
  while (changed) {
    const next = stripOnePrefixedTrailingName(result, wanted)
    changed = next !== result
    result = next
  }

  return result.replace(/[,\s]+$/g, '').trim()
}

export function formatFullAddress(source = {}) {
  if (!source) return ''
  const street = stripTrailingRegions(source.address, [
    source.village,
    source.sub_district,
    source.district,
    source.province,
    source.postal_code,
  ])
  const parts = [
    street,
    source.village,
    source.sub_district ? `Kec. ${source.sub_district}` : '',
    source.district,
    source.province,
    source.postal_code,
  ].filter((part) => part != null && String(part).trim() !== '')
  return parts.join(', ') || source.full_address || ''
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
