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

function pathWithoutAppLayout(path = '') {
  if (EXACT_PATHS_WITHOUT_APP_LAYOUT.has(path)) return true
  if (PATH_PREFIXES_WITHOUT_APP_LAYOUT.some((prefix) => path.startsWith(prefix))) return true
  if (/^\/[^/]+\/(ebooks|buku-tamu|daftar-ppdb)(\/|$)/.test(path)) return true
  return false
}

export function routeUsesAppLayout(route) {
  if (route?.meta?.noLayout) return false

  if (route?.name) {
    if (ROUTES_WITHOUT_APP_LAYOUT.has(route.name)) return false
    return true
  }

  // Name belum ada (mis. sebelum router siap): jangan flash sidebar di halaman publik.
  if (pathWithoutAppLayout(route?.path || '')) return false

  // Route belum ter-resolve: lebih aman tanpa Layout daripada sidebar sekilas.
  return false
}
