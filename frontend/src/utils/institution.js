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

/**
 * Daftar tingkat kelas yang valid per jenjang (untuk dropdown kelas/tingkat).
 * SD/MI: 1-6, SMP/MTs: 7-9, SMA/MA/SMK/MAK: 10-12, PAUD/TK: tidak pakai angka.
 * @param {string} level - Level jenjang (SD, SMP, SMA, SMK, MI, MTs, MA, MAK, PAUD, TK)
 * @returns {number[]|null} - Array angka tingkat atau null jika jenjang tidak pakai tingkat numerik
 */
export function getValidGradesForLevel(level) {
  if (!level) return []
  const L = String(level).toUpperCase()
  if (L === 'SD' || L === 'MI') return [1, 2, 3, 4, 5, 6]
  if (L === 'SMP' || L === 'MTs') return [7, 8, 9]
  if (L === 'SMA' || L === 'MA' || L === 'MAK' || L === 'SMK') return [10, 11, 12]
  return [] // PAUD, TK, atau lain tidak pakai tingkat numerik
}
