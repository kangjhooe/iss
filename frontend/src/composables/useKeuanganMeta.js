import { financeFeeTypeApi, financePickerApi } from '@/api/finance'

/** Label kelas siswa dari berbagai bentuk respons API */
export function studentClassLabel(student) {
  if (!student) return '—'
  if (student.class_name) return student.class_name
  if (student.class_detail?.name) return student.class_detail.name
  if (typeof student.class === 'string' && student.class) return student.class
  if (student.class?.name) return student.class.name
  if (student.school_class?.name) return student.school_class.name
  return '—'
}

export async function loadKeuanganFeeTypes(options = {}) {
  const params = { active_only: 1, per_page: 100, ...(options.params || {}) }
  const res = await financeFeeTypeApi.getAll(params)
  let items = res.data?.data || []
  if (options.excludeMonthly) {
    items = items.filter((t) => t.frequency !== 'monthly')
  }
  return items
}

export async function loadKeuanganClasses() {
  const res = await financePickerApi.classesLite()
  return res.data?.data || []
}

export async function searchKeuanganStudents(query, classId = '') {
  const q = String(query || '').trim()
  const params = {}
  if (classId) params.class_id = classId
  if (q) params.q = q
  const res = await financePickerApi.studentsLite(params)
  return res.data?.data || []
}
