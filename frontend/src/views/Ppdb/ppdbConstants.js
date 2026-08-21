import { emptyAddress } from '@/utils/addressFields'

export const statusPeriodLabel = (s) =>
  ({ draft: 'Draft', open: 'Dibuka', closed: 'Ditutup', finished: 'Selesai' }[s] || s)

export const statusApplicantLabels = {
  draft: 'Draft',
  submitted: 'Terkirim',
  verification: 'Verifikasi',
  verified: 'Terverifikasi',
  rejected: 'Ditolak',
  passed: 'Lulus',
  reserve: 'Cadangan',
  failed: 'Tidak Lulus',
  re_registration: 'Daftar Ulang',
  converted: 'Jadi Siswa',
  cancelled: 'Dibatalkan',
  accepted_elsewhere: 'Sudah diterima di sekolah lain',
}

export const paymentStatusLabels = {
  unpaid: 'Belum bayar',
  pending: 'Menunggu',
  paid: 'Lunas',
  waived: 'Dibebaskan',
}

export function formatDate(val) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

export function formatCurrency(val) {
  if (val === null || val === undefined || val === '') return '—'
  const n = Number(val)
  if (Number.isNaN(n)) return '—'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

export function canBulkVerify(a) {
  return a && ['submitted', 'verification'].includes(a.status)
}

export function canSetResult(a) {
  return a && ['verified', 'submitted', 'verification', 'passed', 'reserve', 'failed'].includes(a.status)
}

export function canConfirmReReg(a) {
  return a && ['passed', 'reserve'].includes(a.status)
}

export function canConvertToStudent(a) {
  if (!a || a.student_id) return false
  if (['converted', 'accepted_elsewhere', 'cancelled', 'rejected', 'failed'].includes(a.status)) return false
  return a.status === 're_registration' || !!a.re_registration_confirmed_at
}

export function canBulkSelect(a) {
  return canBulkVerify(a) || canSetResult(a) || canConfirmReReg(a) || canConvertToStudent(a)
}

export const PPDB_DOCUMENT_PRESETS = [
  { key: 'foto', label: 'Foto 3x4' },
  { key: 'kk', label: 'Kartu Keluarga' },
  { key: 'akte', label: 'Akte Kelahiran' },
  { key: 'rapor', label: 'Rapor terakhir' },
  { key: 'ijazah', label: 'Ijazah / SKL' },
]

export function documentSummaryLabel(a) {
  const s = a?.document_summary
  if (s?.required_total) {
    const mark = a.documents_verified ? ' ✓' : ''
    return `${s.required_uploaded}/${s.required_total}${mark}`
  }
  return a?.documents_verified ? '✓' : '-'
}

export const emptyApplicantForm = () => ({
  ppdb_period_id: '',
  ppdb_channel_id: '',
  name: '',
  nik: '',
  nisn: '',
  gender: 'L',
  birth_date: '',
  birth_place: '',
  ...emptyAddress(),
  phone: '',
  email: '',
  religion: '',
  previous_school: '',
  previous_school_npsn: '',
  previous_school_address: '',
  father_name: '',
  father_phone: '',
  father_nik: '',
  mother_name: '',
  mother_phone: '',
  mother_nik: '',
  guardian_name: '',
  guardian_phone: '',
  guardian_relation: '',
  notes: '',
})
