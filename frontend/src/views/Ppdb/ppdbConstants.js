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

export function canSetResult(a) {
  return a && ['verified', 'submitted', 'verification'].includes(a.status)
}

export function canConfirmReReg(a) {
  return a && ['passed', 'reserve'].includes(a.status)
}

export function canConvertToStudent(a) {
  return a && (a.status === 're_registration' || (a.re_registration_confirmed_at && a.status !== 'converted')) && !a.student_id
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
  address: '',
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
