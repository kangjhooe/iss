/**
 * Password default siswa: tanggal lahir DDMMYYYY.
 * Parse dari string YYYY-MM-DD agar tidak terpengaruh timezone.
 */
export function formatBirthDatePassword(birthDate) {
  if (!birthDate) return ''
  const match = String(birthDate).match(/^(\d{4})-(\d{2})-(\d{2})/)
  if (match) return `${match[3]}${match[2]}${match[1]}`
  return ''
}

export function studentLoginCredentials(student = {}, loginHint = null) {
  const nik = loginHint?.login_value || student?.nik || ''
  const password = loginHint?.password || formatBirthDatePassword(student?.birth_date || loginHint?.birth_date)
  if (!nik || !password) return null
  return {
    title: 'Akun login siswa',
    name: student?.name || '',
    loginLabel: 'NIK',
    loginValue: String(nik),
    password: String(password),
    hint: 'Siswa login dengan NIK. Sandi awal = tanggal lahir (DDMMYYYY). Wajib ganti sandi saat login pertama.',
  }
}

export function mapStudentCreatedAccounts(accounts = []) {
  return (accounts || [])
    .filter((item) => item?.nik && item?.password)
    .map((item) => ({
      name: item.name || '',
      login: item.nik,
      password: item.password,
    }))
}
