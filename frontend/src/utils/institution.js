/**
 * Utility functions untuk institution
 */

/**
 * Mendapatkan label jenis instansi berdasarkan level
 * @param {string} level - Level jenjang (SD, SMP, SMA, SMK, MI, MTs, MA, MAK, dll)
 * @returns {string} - "Sekolah" atau "Madrasah"
 */
export function getInstitutionTypeLabel(level) {
  if (!level) return 'Instansi'
  
  const madrasahLevels = ['MI', 'MTs', 'MA', 'MAK']
  const sekolahLevels = ['SD', 'SMP', 'SMA', 'SMK']
  
  if (madrasahLevels.includes(level)) {
    return 'Madrasah'
  } else if (sekolahLevels.includes(level)) {
    return 'Sekolah'
  }
  
  return 'Instansi' // Default untuk TK, PAUD, dll
}

/**
 * Mendapatkan label plural (banyak) jenis instansi berdasarkan level
 * @param {string} level - Level jenjang
 * @returns {string} - "Sekolah" atau "Madrasah"
 */
export function getInstitutionTypeLabelPlural(level) {
  return getInstitutionTypeLabel(level)
}
