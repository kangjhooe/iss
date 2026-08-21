/**
 * Akses modul: permission per user + modul yang disembunyikan sekolah.
 */

/** Modul yang tidak di-assign ke guru; hanya admin & kepala sekolah. */
export const PRINCIPAL_ONLY_MODULES = ['report']

export function hiddenModuleKeys(user) {
  const keys = user?.hidden_module_keys
    ?? user?.active_institution?.hidden_module_keys
    ?? user?.institution?.hidden_module_keys
    ?? []
  return Array.isArray(keys) ? keys : []
}

export function isInstitutionModuleHidden(user, moduleKey) {
  if (!user || !moduleKey) return false
  if (user.role === 'super_admin') return false
  return hiddenModuleKeys(user).includes(moduleKey)
}

export function hasModuleAccess(user, moduleKey) {
  if (!user || !moduleKey) return false
  if (isInstitutionModuleHidden(user, moduleKey)) return false
  if (user.role === 'super_admin' || user.role === 'admin' || user.role === 'institution_admin') {
    return true
  }
  if (moduleKey === 'report') {
    if (user.active_affiliation === 'non_induk') return false
    return !!user.is_kepala_sekolah
  }
  if (moduleKey === 'school_content' && (user.permissions || []).includes('institution')) {
    return true
  }
  if ((user.permissions || []).includes(moduleKey)) return true
  if (moduleKey === 'guru_piket' && (user.is_piket_scheduled || user.is_piket_on_duty)) {
    return true
  }
  return false
}

export function hasAnyModuleAccess(user, moduleKeys) {
  if (!user || !Array.isArray(moduleKeys) || !moduleKeys.length) return false
  return moduleKeys.some(key => hasModuleAccess(user, key))
}
