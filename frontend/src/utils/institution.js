/**
 * Utility functions untuk institution
 */

const MADRASAH_LEVELS = ['MI', 'MTS', 'MA', 'MAK']
const SEKOLAH_LEVELS = ['SD', 'SMP', 'SMA', 'SMK']

/**
 * Apakah jenjang termasuk madrasah (MI, MTs, MA, MAK).
 * @param {string} level
 * @returns {boolean}
 */
export function isMadrasahLevel(level) {
  if (!level) return false
  return MADRASAH_LEVELS.includes(String(level).toUpperCase())
}

/**
 * Jabatan penandatangan laporan: satu jabatan saja.
 * Madrasah → "Kepala Madrasah", selain itu → "Kepala Sekolah".
 * @param {string} level
 * @returns {string}
 */
export function getPrincipalTitle(level) {
  return isMadrasahLevel(level) ? 'Kepala Madrasah' : 'Kepala Sekolah'
}

/**
 * Label nomor statistik: madrasah → NSM, selain itu → NSS.
 * @param {string} level
 * @returns {string}
 */
export function getNssLabel(level) {
  return isMadrasahLevel(level) ? 'NSM' : 'NSS'
}

/**
 * Mendapatkan label jenis instansi berdasarkan level
 * @param {string} level - Level jenjang (SD, SMP, SMA, SMK, MI, MTs, MA, MAK, dll)
 * @returns {string} - "Sekolah" atau "Madrasah"
 */
export function getInstitutionTypeLabel(level) {
  if (!level) return 'Instansi'

  const normalized = String(level).toUpperCase()

  if (MADRASAH_LEVELS.includes(normalized)) {
    return 'Madrasah'
  }
  if (SEKOLAH_LEVELS.includes(normalized)) {
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
  if (L === 'SMP' || L === 'MTS') return [7, 8, 9]
  if (L === 'SMA' || L === 'MA' || L === 'MAK' || L === 'SMK') return [10, 11, 12]
  return [] // PAUD, TK, atau lain tidak pakai tingkat numerik
}
