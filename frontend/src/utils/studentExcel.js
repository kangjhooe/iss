import { excelAddressColumns, pickAddressFromExcel } from './addressFields.js'

export const STUDENT_EXCEL_REQUIRED_HEADERS = [
  'NIK',
  'Nama Lengkap',
  'Tempat Lahir',
  'Tanggal Lahir',
  'Tingkat',
]

export const STUDENT_EXCEL_COL_WIDTHS = [
  { wch: 20 }, { wch: 15 }, { wch: 15 }, { wch: 30 }, { wch: 15 },
  { wch: 20 }, { wch: 15 }, { wch: 10 }, { wch: 36 }, { wch: 22 },
  { wch: 18 }, { wch: 22 }, { wch: 16 }, { wch: 12 }, { wch: 15 }, { wch: 25 },
  { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 10 }, { wch: 10 }, { wch: 25 }, { wch: 20 }, { wch: 15 },
  { wch: 15 }, { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 },
  { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 25 }, { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 15 }, { wch: 20 }, { wch: 20 }, { wch: 20 }, { wch: 20 },
  { wch: 30 },
]

const PARENT_STATUS_CODES = ['masih_hidup', 'meninggal_dunia', 'tidak_diketahui']
const RESIDENCE_CODES = ['asrama', 'kost_kontrak', 'tinggal_dengan_orang_tua', 'lainnya']
const GUARDIAN_TYPE_CODES = ['sama_dengan_ayah', 'sama_dengan_ibu', 'lainnya']

export function studentExcelClassName(student) {
  const name = student?.class_detail?.name || student?.class || ''
  return String(name).trim()
}

export function studentExcelAcademicYear(student) {
  const detailName = String(student?.academic_year_detail?.name || '')
  const fromName = detailName.match(/(\d{4}\/\d{4})/)
  if (fromName) return fromName[1]
  const stored = String(student?.academic_year || '')
  const fromStored = stored.match(/(\d{4}\/\d{4})/)
  if (fromStored) return fromStored[1]
  return stored || student?.academic_year_detail?.code || ''
}

export function excelIsoDate(value) {
  if (value === undefined || value === null || value === '') return ''
  if (value instanceof Date) return formatLocalDate(value) || ''
  const raw = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10)
  return raw
}

export function studentToExcelRow(student = {}, { markRequired = true } = {}) {
  const row = {
    'NIK': student.nik || '',
    'NIS': student.nis || '',
    'NISN': student.nisn || '',
    'Nama Lengkap': student.name || '',
    'Jenis Kelamin': excelGender(student.gender),
    'Tempat Lahir': student.birth_place || '',
    'Tanggal Lahir': excelIsoDate(student.birth_date),
    'Tingkat': student.tingkat ?? '',
    ...excelAddressColumns(student),
    'No. Telepon': student.phone || '',
    'Email': student.email || '',
    'Agama': student.religion || '',
    'No. KK': student.no_kk || '',
    'Cita-cita': student.aspiration || '',
    'Hobi': student.hobby || '',
    'Disabilitas': student.disability || '',
    'Tinggi Badan (cm)': student.height ?? '',
    'Berat Badan (kg)': student.weight ?? '',
    'Sekolah Sebelumnya': student.previous_school || '',
    'NPSN Sekolah Asal': student.previous_school_npsn || '',
    'Alamat Sekolah Asal': student.previous_school_address || '',
    'Jenis Tempat Tinggal': excelCode(student.residence_type, RESIDENCE_CODES),
    'Kelas': studentExcelClassName(student),
    'Tahun Ajaran': studentExcelAcademicYear(student),
    'Status': student.status || '',
    'Nama Ayah': student.father_name || '',
    'Status Ayah': excelCode(student.father_status, PARENT_STATUS_CODES),
    'NIK Ayah': student.father_nik || '',
    'Tempat Lahir Ayah': student.father_birth_place || '',
    'Tanggal Lahir Ayah': excelIsoDate(student.father_birth_date),
    'Pendidikan Ayah': student.father_education || '',
    'Pekerjaan Ayah': student.father_occupation || '',
    'Penghasilan Ayah': student.father_income ?? '',
    'Nama Ibu': student.mother_name || '',
    'Status Ibu': excelCode(student.mother_status, PARENT_STATUS_CODES),
    'NIK Ibu': student.mother_nik || '',
    'Tempat Lahir Ibu': student.mother_birth_place || '',
    'Tanggal Lahir Ibu': excelIsoDate(student.mother_birth_date),
    'Pendidikan Ibu': student.mother_education || '',
    'Pekerjaan Ibu': student.mother_occupation || '',
    'Penghasilan Ibu': student.mother_income ?? '',
    'Nama Wali': student.guardian_name || '',
    'No. Telepon Wali': student.guardian_phone || '',
    'Jenis Wali': excelCode(student.guardian_type, GUARDIAN_TYPE_CODES),
    'Status Wali': excelCode(student.guardian_status, PARENT_STATUS_CODES),
    'NIK Wali': student.guardian_nik || '',
    'Tempat Lahir Wali': student.guardian_birth_place || '',
    'Tanggal Lahir Wali': excelIsoDate(student.guardian_birth_date),
    'Pendidikan Wali': student.guardian_education || '',
    'Pekerjaan Wali': student.guardian_occupation || '',
    'Penghasilan Wali': student.guardian_income ?? '',
    'Catatan': student.notes || '',
  }

  if (!markRequired) return row

  const marked = {}
  for (const [key, value] of Object.entries(row)) {
    marked[STUDENT_EXCEL_REQUIRED_HEADERS.includes(key) ? `${key}*` : key] = value
  }
  return marked
}

export function studentExcelTemplateRow() {
  return studentToExcelRow({
    nik: '1234567890123456',
    nis: '2024001',
    nisn: '0012345678',
    name: 'Ahmad Fauzi',
    gender: 'L',
    birth_place: 'Jakarta',
    birth_date: '2010-01-15',
    tingkat: 7,
    address: 'Jl. Contoh No. 123',
    village: 'Sukajaya',
    sub_district: 'Kedaton',
    district: 'Bandar Lampung',
    province: 'Lampung',
    postal_code: '35141',
    phone: '081234567890',
    email: 'ahmad@example.com',
    religion: 'Islam',
    no_kk: '1234567890123456',
    aspiration: 'Dokter',
    hobby: 'Membaca',
    disability: '',
    height: 150,
    weight: 45,
    previous_school: 'SD Negeri 1',
    previous_school_npsn: '12345678',
    previous_school_address: 'Jl. Pendidikan No. 1',
    residence_type: 'tinggal_dengan_orang_tua',
    class: 'VII-A',
    academic_year: '2024/2025',
    status: 'Aktif',
    father_name: 'Budi Santoso',
    father_status: 'masih_hidup',
    father_nik: '1234567890123457',
    father_birth_place: 'Jakarta',
    father_birth_date: '1980-05-20',
    father_education: 'S1',
    father_occupation: 'Pegawai Swasta',
    father_income: 5000000,
    mother_name: 'Siti Nurhaliza',
    mother_status: 'masih_hidup',
    mother_nik: '1234567890123458',
    mother_birth_place: 'Bandung',
    mother_birth_date: '1982-08-10',
    mother_education: 'S1',
    mother_occupation: 'Guru',
    mother_income: 4000000,
  })
}

export function studentExcelGuideRows() {
  return [
    { Keterangan: 'Kolom bertanda * wajib diisi: NIK, Nama Lengkap, Tempat Lahir, Tanggal Lahir, Tingkat' },
    { Keterangan: 'File hasil Export memakai header yang sama. Edit lalu Import ulang: siswa Aktif dengan NIK/NISN/NIS yang sama akan diperbarui.' },
    { Keterangan: 'Format tanggal: YYYY-MM-DD (contoh: 2010-01-15). Tanggal DD/MM/YYYY dari file lama tetap diterima.' },
    { Keterangan: 'Jenis Kelamin: L atau P' },
    { Keterangan: 'Tingkat harus sesuai jenjang institusi (SD/MI: 1-6, SMP/MTs: 7-9, SMA/SMK/MA: 10-12)' },
    { Keterangan: 'Jenis Tempat Tinggal: asrama, kost_kontrak, tinggal_dengan_orang_tua, lainnya' },
    { Keterangan: 'Status Ayah/Ibu/Wali: masih_hidup, meninggal_dunia, tidak_diketahui' },
    { Keterangan: 'Jenis Wali: sama_dengan_ayah, sama_dengan_ibu, lainnya' },
    { Keterangan: 'Kolom Kelas = nama kelas yang sudah ada di sekolah (contoh: VII-A). Tahun Ajaran = 2025/2026.' },
    { Keterangan: 'Alamat = jalan/RT/RW. Desa/Kelurahan/Pekon, Kecamatan, Kabupaten/Kota, Provinsi, dan Kode Pos opsional.' },
    { Keterangan: 'Sel kosong tidak menghapus data yang sudah ada. Siswa Pindah, Tidak Aktif, Lulus, Drop Out, atau di kotak sampah tidak diimpor ulang. Aktifkan kembali di Siswa Keluar jika siswa kembali bersekolah.' },
    { Keterangan: 'Hapus baris contoh pada template sebelum mengimpor. Jangan ubah nama header kolom.' },
  ]
}

export function studentExcelDataSheetName(sheetNames = []) {
  const names = Array.isArray(sheetNames) ? sheetNames : []
  return names.find((name) => String(name || '').trim().toLowerCase() !== 'petunjuk') || names[0]
}

export function parseStudentExcelDate(value, XLSX) {
  if (value === undefined || value === null || value === '') return null
  if (value instanceof Date) return formatLocalDate(value)
  if (typeof value === 'number' && XLSX?.SSF?.parse_date_code) {
    const parsed = XLSX.SSF.parse_date_code(value)
    if (parsed) {
      return `${parsed.y}-${String(parsed.m).padStart(2, '0')}-${String(parsed.d).padStart(2, '0')}`
    }
  }
  const raw = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10)
  const dmy = raw.match(/^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$/)
  if (dmy) {
    return `${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}`
  }
  return formatLocalDate(new Date(raw))
}

export function parseStudentExcelGender(val) {
  if (val === undefined || val === null || val === '') return null
  const str = String(val).toLowerCase().trim()
  if (str === 'l' || str.includes('laki')) return 'L'
  if (str === 'p' || str.includes('perempuan')) return 'P'
  return null
}

export function parseStudentExcelParentStatus(val) {
  if (val === undefined || val === null || val === '') return null
  const str = String(val).toLowerCase().trim().replace(/\s+/g, '_')
  if (PARENT_STATUS_CODES.includes(str)) return str
  if (str.includes('hidup')) return 'masih_hidup'
  if (str.includes('meninggal')) return 'meninggal_dunia'
  if (str.includes('tidak')) return 'tidak_diketahui'
  return null
}

export function parseStudentExcelResidenceType(val) {
  if (val === undefined || val === null || val === '') return null
  const str = String(val).toLowerCase().trim().replace(/\s+/g, '_')
  if (RESIDENCE_CODES.includes(str)) return str
  if (str.includes('asrama')) return 'asrama'
  if (str.includes('kost') || str.includes('kontrak')) return 'kost_kontrak'
  if (str.includes('orang_tua') || str.includes('orang tua') || str.includes('tinggal')) return 'tinggal_dengan_orang_tua'
  return 'lainnya'
}

export function parseStudentExcelGuardianType(val) {
  if (val === undefined || val === null || val === '') return null
  const str = String(val).toLowerCase().trim().replace(/\s+/g, '_')
  if (GUARDIAN_TYPE_CODES.includes(str)) return str
  if (str.includes('ayah')) return 'sama_dengan_ayah'
  if (str.includes('ibu')) return 'sama_dengan_ibu'
  return 'lainnya'
}

export function asStudentExcelDigitId(val, { padTo = 0 } = {}) {
  if (val === undefined || val === null || val === '') return null
  let s
  if (typeof val === 'number') {
    if (!Number.isFinite(val)) return null
    s = String(Math.trunc(val))
  } else {
    s = String(val).trim()
    if (/^\d+\.0+$/.test(s)) s = s.replace(/\.0+$/, '')
    s = s.replace(/[\s.\-]/g, '')
  }
  s = s.replace(/[^\d]/g, '')
  if (!s) return null
  if (padTo && s.length < padTo && s.length >= padTo - 2) {
    s = s.padStart(padTo, '0')
  }
  return s
}

export function mapStudentImportExcelRows(jsonData, XLSX) {
  const normalizeHeader = (key) => String(key || '').replace(/\s*\*\s*$/, '').replace(/\s*\(wajib\)\s*$/i, '').trim()
  const valid = []
  const invalid = []

  jsonData.forEach((row, index) => {
    const normalizedRow = {}
    Object.keys(row || {}).forEach((key) => {
      normalizedRow[normalizeHeader(key)] = row[key]
    })
    const mapField = (excelCol) => {
      const value = normalizedRow[excelCol]
      if (value === undefined || value === null || value === '') return null
      return value
    }

    const item = {
      excel_row: index + 2,
      nik: asStudentExcelDigitId(mapField('NIK')),
      nis: mapField('NIS') == null ? null : String(mapField('NIS')).trim() || null,
      nisn: asStudentExcelDigitId(mapField('NISN'), { padTo: 10 }),
      name: mapField('Nama Lengkap'),
      gender: parseStudentExcelGender(mapField('Jenis Kelamin')),
      birth_place: mapField('Tempat Lahir'),
      birth_date: parseStudentExcelDate(mapField('Tanggal Lahir'), XLSX),
      ...pickAddressFromExcel(mapField),
      phone: mapField('No. Telepon'),
      email: mapField('Email'),
      religion: mapField('Agama'),
      no_kk: mapField('No. KK'),
      aspiration: mapField('Cita-cita'),
      hobby: mapField('Hobi'),
      disability: mapField('Disabilitas'),
      height: mapField('Tinggi Badan (cm)') ? parseFloat(mapField('Tinggi Badan (cm)')) : null,
      weight: mapField('Berat Badan (kg)') ? parseFloat(mapField('Berat Badan (kg)')) : null,
      previous_school: mapField('Sekolah Sebelumnya'),
      previous_school_npsn: mapField('NPSN Sekolah Asal') == null
        ? null
        : String(mapField('NPSN Sekolah Asal')).replace(/\D/g, '') || null,
      previous_school_address: mapField('Alamat Sekolah Asal'),
      residence_type: parseStudentExcelResidenceType(mapField('Jenis Tempat Tinggal')),
      tingkat: mapField('Tingkat') ? parseInt(mapField('Tingkat'), 10) : null,
      class: mapField('Kelas'),
      academic_year: mapField('Tahun Ajaran'),
      status: mapField('Status') || 'Aktif',
      father_name: mapField('Nama Ayah'),
      father_status: parseStudentExcelParentStatus(mapField('Status Ayah')),
      father_nik: mapField('NIK Ayah'),
      father_birth_place: mapField('Tempat Lahir Ayah'),
      father_birth_date: parseStudentExcelDate(mapField('Tanggal Lahir Ayah'), XLSX),
      father_education: mapField('Pendidikan Ayah'),
      father_occupation: mapField('Pekerjaan Ayah'),
      father_income: mapField('Penghasilan Ayah') ? parseFloat(mapField('Penghasilan Ayah')) : null,
      mother_name: mapField('Nama Ibu'),
      mother_status: parseStudentExcelParentStatus(mapField('Status Ibu')),
      mother_nik: mapField('NIK Ibu'),
      mother_birth_place: mapField('Tempat Lahir Ibu'),
      mother_birth_date: parseStudentExcelDate(mapField('Tanggal Lahir Ibu'), XLSX),
      mother_education: mapField('Pendidikan Ibu'),
      mother_occupation: mapField('Pekerjaan Ibu'),
      mother_income: mapField('Penghasilan Ibu') ? parseFloat(mapField('Penghasilan Ibu')) : null,
      guardian_name: mapField('Nama Wali'),
      guardian_phone: mapField('No. Telepon Wali'),
      guardian_type: parseStudentExcelGuardianType(mapField('Jenis Wali')),
      guardian_status: parseStudentExcelParentStatus(mapField('Status Wali')),
      guardian_nik: mapField('NIK Wali'),
      guardian_birth_place: mapField('Tempat Lahir Wali'),
      guardian_birth_date: parseStudentExcelDate(mapField('Tanggal Lahir Wali'), XLSX),
      guardian_education: mapField('Pendidikan Wali'),
      guardian_occupation: mapField('Pekerjaan Wali'),
      guardian_income: mapField('Penghasilan Wali') ? parseFloat(mapField('Penghasilan Wali')) : null,
      notes: mapField('Catatan'),
    }

    const missing = []
    if (!item.nik) missing.push('NIK')
    if (!item.name) missing.push('Nama')
    if (!item.birth_place) missing.push('Tempat Lahir')
    if (!item.birth_date) missing.push('Tanggal Lahir')
    if (item.tingkat == null || Number.isNaN(item.tingkat)) missing.push('Tingkat')

    if (missing.length) {
      invalid.push({ row: index + 2, reason: `Kolom wajib kosong: ${missing.join(', ')}` })
    } else {
      valid.push(item)
    }
  })

  return { valid, invalid }
}

function excelGender(gender) {
  if (gender === undefined || gender === null || gender === '') return ''
  const str = String(gender).toLowerCase().trim()
  if (str === 'l' || str.includes('laki')) return 'L'
  if (str === 'p' || str.includes('perempuan')) return 'P'
  return String(gender)
}

function excelCode(value, allowed) {
  if (value === undefined || value === null || value === '') return ''
  const raw = String(value).trim()
  if (allowed.includes(raw)) return raw
  const slug = raw.toLowerCase().replace(/\s+/g, '_')
  if (allowed.includes(slug)) return slug
  return raw
}

function formatLocalDate(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return null
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}
