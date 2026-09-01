/** Route names that render without the global app sidebar layout. */
export const ROUTES_WITHOUT_APP_LAYOUT = new Set([
  'Home',
  'Login',
  'Register',
  'ReleaseNotes',
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

export function routeUsesAppLayout(route) {
  if (!route?.name) return true
  if (ROUTES_WITHOUT_APP_LAYOUT.has(route.name)) return false
  if (route.meta?.noLayout) return false
  return true
}
