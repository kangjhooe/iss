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
 * **Legacy (jangan dipakai untuk auth baru):** setToken, getToken, setRefreshToken, getRefreshToken,
 * isAuthenticated() — tetap ada untuk kompatibilitas; jangan dipakai untuk keputusan auth/guard.
 */

// Hanya dipakai oleh fungsi legacy setToken/getToken; auth baru pakai cookie
const ENCRYPTION_KEY = 'iss_token_key'

/**
 * Simple encryption (for demo purposes)
 * In production, use a proper encryption library
 */
function encrypt(text) {
  // Simple base64 encoding (not secure, but better than plain text)
  // In production, use crypto-js or similar
  try {
    return btoa(encodeURIComponent(text))
  } catch (e) {
    console.error('Encryption error:', e)
    return text
  }
}

/**
 * Simple decryption
 */
function decrypt(encryptedText) {
  try {
    return decodeURIComponent(atob(encryptedText))
  } catch (e) {
    console.error('Decryption error:', e)
    return encryptedText
  }
}

/**
 * Store token securely
 */
export function setToken(token) {
  if (!token) {
    removeToken()
    return
  }

  try {
    const encrypted = encrypt(token)
    localStorage.setItem('token', encrypted)
    
    // Also store timestamp for expiration check
    localStorage.setItem('token_timestamp', Date.now().toString())
  } catch (e) {
    console.error('Error storing token:', e)
    // Fallback to plain storage if encryption fails
    localStorage.setItem('token', token)
  }
}

/**
 * Get token securely
 */
export function getToken() {
  try {
    const encrypted = localStorage.getItem('token')
    if (!encrypted) return null

    // Check if token is expired (24 hours)
    const timestamp = localStorage.getItem('token_timestamp')
    if (timestamp) {
      const age = Date.now() - parseInt(timestamp)
      const maxAge = 24 * 60 * 60 * 1000 // 24 hours
      
      if (age > maxAge) {
        removeToken()
        return null
      }
    }

    return decrypt(encrypted)
  } catch (e) {
    console.error('Error getting token:', e)
    // Fallback to plain storage
    return localStorage.getItem('token')
  }
}

/**
 * Remove token
 */
export function removeToken() {
  localStorage.removeItem('token')
  localStorage.removeItem('token_timestamp')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('refresh_token_timestamp')
}

/**
 * Store refresh token securely
 */
export function setRefreshToken(token) {
  if (!token) {
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('refresh_token_timestamp')
    return
  }

  try {
    const encrypted = encrypt(token)
    localStorage.setItem('refresh_token', encrypted)
    localStorage.setItem('refresh_token_timestamp', Date.now().toString())
  } catch (e) {
    console.error('Error storing refresh token:', e)
    localStorage.setItem('refresh_token', token)
  }
}

/**
 * Get refresh token securely
 */
export function getRefreshToken() {
  try {
    const encrypted = localStorage.getItem('refresh_token')
    if (!encrypted) return null

    // Check if refresh token is expired (30 days)
    const timestamp = localStorage.getItem('refresh_token_timestamp')
    if (timestamp) {
      const age = Date.now() - parseInt(timestamp)
      const maxAge = 30 * 24 * 60 * 60 * 1000 // 30 days
      
      if (age > maxAge) {
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('refresh_token_timestamp')
        return null
      }
    }

    return decrypt(encrypted)
  } catch (e) {
    console.error('Error getting refresh token:', e)
    return localStorage.getItem('refresh_token')
  }
}

/**
 * Cek apakah ada token di localStorage. LEGACY: jangan dipakai untuk guard/route.
 * Gunakan auth store (useAuthStore().isAuthenticated) yang mengandalkan cookie + /me.
 */
export function isAuthenticated() {
  return !!getToken()
}

/**
 * Bersihkan semua data auth dari localStorage (token, refresh_token, user).
 * Dipanggil saat logout dan saat refresh token gagal (401) di API interceptor.
 */
export function clearAuth() {
  removeToken()
  localStorage.removeItem('user')
}
