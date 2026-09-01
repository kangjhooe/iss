/**
 * Ambil koordinat GPS perangkat dengan pesan error yang ramah pengguna.
 */

export const GEO_STATUS = {
  IDLE: 'idle',
  LOADING: 'loading',
  SUCCESS: 'success',
  ERROR: 'error',
}

/**
 * @param {GeolocationPositionError} err
 * @returns {string}
 */
export function geolocationErrorMessage(err) {
  if (!err) return 'Gagal mendapatkan lokasi.'
  if (!window.isSecureContext && window.location.hostname !== 'localhost') {
    return 'Lokasi membutuhkan HTTPS. Buka aplikasi lewat https:// atau localhost.'
  }
  switch (err.code) {
    case 1:
      return 'Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini di pengaturan browser.'
    case 2:
      return 'Lokasi tidak tersedia. Pastikan GPS aktif atau coba di area terbuka.'
    case 3:
      return 'Waktu habis saat mencari lokasi. Coba lagi atau pindah ke area dengan sinyal lebih baik.'
    default:
      return err.message || 'Gagal mendapatkan lokasi.'
  }
}

/**
 * @param {{ enableHighAccuracy?: boolean, timeout?: number, maximumAge?: number }} [options]
 * @returns {Promise<{ latitude: number, longitude: number, accuracy?: number }>}
 */
export function getCurrentCoordinates(options = {}) {
  const {
    enableHighAccuracy = true,
    timeout = 15000,
    maximumAge = 60000,
  } = options

  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Browser tidak mendukung geolocation.'))
      return
    }
    if (!window.isSecureContext && window.location.hostname !== 'localhost') {
      reject(new Error('Lokasi membutuhkan HTTPS. Buka aplikasi lewat https:// atau localhost.'))
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
        })
      },
      (err) => {
        reject(new Error(geolocationErrorMessage(err)))
      },
      { enableHighAccuracy, timeout, maximumAge }
    )
  })
}

/**
 * @param {number|null|undefined} lat
 * @param {number|null|undefined} lng
 * @returns {boolean}
 */
export function hasValidCoordinates(lat, lng) {
  if (lat == null || lng == null || lat === '' || lng === '') return false
  const la = Number(lat)
  const lo = Number(lng)
  if (!Number.isFinite(la) || !Number.isFinite(lo)) return false
  if (la === 0 && lo === 0) return false
  return la >= -90 && la <= 90 && lo >= -180 && lo <= 180
}

/**
 * @param {number} lat
 * @param {number} lng
 * @param {number} [zoom]
 * @returns {string}
 */
export function openStreetMapEmbedUrl(lat, lng, zoom = 16) {
  const la = Number(lat)
  const lo = Number(lng)
  const delta = 0.004 / (zoom / 16)
  const bbox = [lo - delta, la - delta, lo + delta, la + delta].join('%2C')
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${la}%2C${lo}`
}

/**
 * Format pesan error dari API scan QR terkait lokasi.
 * @param {import('axios').AxiosError|Error} err
 * @returns {string}
 */
export function formatQrLocationApiError(err) {
  const data = err?.response?.data
  if (!data) return err?.formattedMessage || err?.message || 'Gagal memproses absensi.'
  if (data.distance != null && data.required_radius != null) {
    return `${data.message} (jarak ~${Math.round(data.distance)} m, batas ${data.required_radius} m)`
  }
  return data.message || 'Gagal memproses absensi.'
}
