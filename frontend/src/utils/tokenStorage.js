/**
 * Auth storage utility.
 *
 * **Auth saat ini:** Token disimpan di httpOnly cookie oleh backend. Frontend tidak menyimpan
 * token di localStorage (aman dari XSS). Untuk cek login/guard route gunakan auth store
 * (authStore.isAuthenticated), bukan isAuthenticated() di sini.
 *
 * **Yang dipakai di aplikasi:**
 * - clearAuth(): bersihkan sisa data lama di localStorage (dipanggil saat logout / refresh gagal).
 *
 * **Legacy (dinonaktifkan untuk keamanan):** setToken, getToken, setRefreshToken, getRefreshToken,
 * isAuthenticated() — tidak lagi menulis/membaca token dari localStorage agar tidak ada sisa token
 * di client yang bisa dicuri XSS. Panggilan ke fungsi tersebut diabaikan atau mengembalikan null/false.
 */

const STORAGE_KEYS = ['token', 'token_timestamp', 'refresh_token', 'refresh_token_timestamp', 'user']

function clearLegacyStorage() {
  try {
    STORAGE_KEYS.forEach((key) => {
      localStorage.removeItem(key)
    })
  } catch (e) {
    if (import.meta.env.DEV) {
      console.warn('tokenStorage.clearLegacyStorage:', e)
    }
  }
}

/**
 * No-op: auth memakai httpOnly cookie, token tidak disimpan di localStorage.
 */
export function setToken() {
  // Tidak menulis ke localStorage (keamanan)
}

/**
 * Selalu mengembalikan null: auth dari cookie, bukan localStorage.
 */
export function getToken() {
  return null
}

/**
 * No-op: auth memakai httpOnly cookie.
 */
export function setRefreshToken() {
  // Tidak menulis ke localStorage (keamanan)
}

/**
 * Selalu mengembalikan null: auth dari cookie.
 */
export function getRefreshToken() {
  return null
}

/**
 * Cek apakah ada token di localStorage. LEGACY — selalu false.
 * Gunakan auth store (useAuthStore().isAuthenticated) yang mengandalkan cookie + /me.
 */
export function isAuthenticated() {
  return false
}

/**
 * Bersihkan semua data auth dari localStorage (token, refresh_token, user).
 * Dipanggil saat logout dan saat refresh token gagal (401) di API interceptor.
 */
export function clearAuth() {
  clearLegacyStorage()
}
