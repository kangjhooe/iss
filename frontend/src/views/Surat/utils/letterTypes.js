/**
 * Jenis surat resmi (sinkron dengan App\Models\Correspondence::getLetterTypes).
 * Dipakai untuk penomoran: KK-NNN/JS/{NPSN}/BLN/TAHUN
 */
export const DEFAULT_LETTER_TYPE_CODE = '09'

export const LETTER_TYPES = [
  { code: '01', abbr: 'SK', name: 'Surat Keputusan' },
  { code: '02', abbr: 'SU', name: 'Surat Undangan' },
  { code: '03', abbr: 'SPm', name: 'Surat Permohonan' },
  { code: '04', abbr: 'SPb', name: 'Surat Pemberitahuan' },
  { code: '05', abbr: 'SPj', name: 'Surat Peminjaman' },
  { code: '06', abbr: 'SPn', name: 'Surat Pernyataan' },
  { code: '07', abbr: 'SM', name: 'Surat Mandat' },
  { code: '08', abbr: 'ST', name: 'Surat Tugas' },
  { code: '09', abbr: 'SKet', name: 'Surat Keterangan' },
  { code: '10', abbr: 'SR', name: 'Surat Rekomendasi' },
  { code: '11', abbr: 'SB', name: 'Surat Balasan' },
  { code: '12', abbr: 'SPPD', name: 'Surat Perintah Perjalanan Dinas' },
  { code: '13', abbr: 'SRT', name: 'Sertifikat' },
  { code: '14', abbr: 'SPK', name: 'Perjanjian Kerja' },
  { code: '15', abbr: 'SPg', name: 'Surat Pengantar' },
  { code: '16', abbr: 'SL', name: 'Surat Lainnya' }
]

/** Jenis surat yang boleh multi-halaman (tidak dipaksa 1 lembar). */
export const RELAXED_LETTER_TYPE_CODES = ['01', '13', '14']

/**
 * Density cetak/preview — sinkron dengan SuratLayoutService::densityForLetterType.
 * @returns {'compact'|'relaxed'}
 */
export function suratDensityForLetterType(code) {
  const normalized = String(code || DEFAULT_LETTER_TYPE_CODE).replace(/\D/g, '').padStart(2, '0') || DEFAULT_LETTER_TYPE_CODE
  return RELAXED_LETTER_TYPE_CODES.includes(normalized) ? 'relaxed' : 'compact'
}

const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII']

export function getLetterType(code) {
  return LETTER_TYPES.find((t) => t.code === code) || null
}

export function letterTypeLabel(code) {
  const t = getLetterType(code)
  if (!t) return code || '—'
  return `${t.code} — ${t.name}`
}

/**
 * Contoh nomor resmi (bukan nomor aktual).
 * Format: KK-NNN/JS/{NPSN}/BLN/TAHUN
 */
export function exampleLetterNumber(code, { npsn = 'NPSN', sequence = 1, date = new Date() } = {}) {
  const t = getLetterType(code) || getLetterType(DEFAULT_LETTER_TYPE_CODE)
  const d = date instanceof Date ? date : new Date(date)
  const month = ROMAN_MONTHS[d.getMonth()] || 'I'
  const year = d.getFullYear()
  const seq = String(sequence).padStart(3, '0')
  return `${t.code}-${seq}/${t.abbr}/${npsn || 'NPSN'}/${month}/${year}`
}
