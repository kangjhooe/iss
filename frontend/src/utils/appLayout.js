/** Route names that render without the global app sidebar layout. */
export const ROUTES_WITHOUT_APP_LAYOUT = new Set([
  'Home',
  'Login',
  'Register',
  'ReleaseNotes',
  'Panduan',
  'PanduanAdmin',
  'PanduanGuru',
  'PanduanSiswa',
  'PanduanOrangTua',
  'ForgotPassword',
  'ResetPassword',
  'VerifyEmail',
  'ForceChangePassword',
  'SchoolPublic',
  'PpdbPublicRegister',
  'PpdbPublicRegisterByNpsn',
  'PublicEbooks',
  'PublicGuestBook',
  'PpdbCheckResult',
  'PpdbLengkapiBerkas',
])

/** Exact paths that never use the app shell (fallback while route name is unresolved). */
const EXACT_PATHS_WITHOUT_APP_LAYOUT = new Set([
  '/',
  '/login',
  '/register',
  '/catatan-rilis',
  '/panduan',
  '/forgot-password',
  '/reset-password',
  '/verify-email',
  '/ganti-sandi-wajib',
  '/daftar-ppdb',
  '/cek-hasil-ppdb',
  '/lengkapi-berkas-ppdb',
])

const PATH_PREFIXES_WITHOUT_APP_LAYOUT = [
  '/panduan/',
]

/** Paths that clearly belong to the authenticated app shell. */
const APP_SHELL_PATH_PREFIXES = [
  '/dashboard',
  '/teacher',
  '/student',
  '/parent',
  '/super-admin',
  '/institution',
  '/grade-book',
  '/lesson-schedule',
  '/teaching-journal',
  '/subject',
  '/class',
  '/schedule',
  '/attendance',
  '/report',
  '/module-access',
  '/billing',
  '/inventory',
  '/facility',
  '/correspondence',
  '/library',
  '/finance',
  '/ppdb',
  '/exam',
  '/account',
  '/notification',
  '/audit-log',
  '/feedback',
  '/academic',
  '/violation',
  '/counseling',
  '/uks',
  '/extracurricular',
  '/kepegawaian',
  '/piket',
  '/raport',
  '/alumni',
  '/digital-archive',
  '/school-content',
  '/pengambilan-ijazah',
]

function pathWithoutAppLayout(path = '') {
  if (EXACT_PATHS_WITHOUT_APP_LAYOUT.has(path)) return true
  if (PATH_PREFIXES_WITHOUT_APP_LAYOUT.some((prefix) => path.startsWith(prefix))) return true
  if (/^\/[^/]+\/(ebooks|buku-tamu|daftar-ppdb)(\/|$)/.test(path)) return true
  // Profil publik sekolah: /{npsn} (biasanya 8 digit)
  if (/^\/\d{8}$/.test(path)) return true
  return false
}

function pathLikelyNeedsAppLayout(path = '') {
  if (!path || pathWithoutAppLayout(path)) return false
  return APP_SHELL_PATH_PREFIXES.some(
    (prefix) => path === prefix || path.startsWith(`${prefix}/`)
  )
}

export function routeUsesAppLayout(route) {
  if (route?.meta?.noLayout) return false

  if (route?.name) {
    if (ROUTES_WITHOUT_APP_LAYOUT.has(route.name)) return false
    return true
  }

  const path = route?.path || ''

  // Name belum ada: jangan flash sidebar di halaman publik.
  if (pathWithoutAppLayout(path)) return false

  // Hard-refresh di /dashboard dll.: pakai Layout segera (hindari flash konten publik).
  if (pathLikelyNeedsAppLayout(path)) return true

  // Route belum ter-resolve: lebih aman tanpa Layout.
  return false
}
