import { PROFILE_PHOTO_SOURCE_MAX_BYTES } from '@/utils/profilePhotoCrop'

export const PROFILE_PHOTO_MAX_BYTES = 1024 * 1024
export const PROFILE_PHOTO_ACCEPT = 'image/jpeg,image/png'

const ALLOWED_TYPES = new Set(['image/jpeg', 'image/jpg', 'image/png'])

export function validateProfilePhotoSource(file) {
  if (!file) return 'Pilih file foto.'
  if (!ALLOWED_TYPES.has(file.type)) return 'Format foto harus JPG atau PNG.'
  if (file.size > PROFILE_PHOTO_SOURCE_MAX_BYTES) return 'Ukuran foto maksimal 10 MB.'
  return null
}

export function validateProfilePhoto(file) {
  if (!file) return 'Pilih file foto.'
  if (!ALLOWED_TYPES.has(file.type) && file.type !== 'image/jpeg') return 'Format foto harus JPG atau PNG.'
  if (file.size > PROFILE_PHOTO_MAX_BYTES) return 'Ukuran foto maksimal 1 MB.'
  return null
}

export function profilePhotoFormData(file) {
  const formData = new FormData()
  formData.append('photo', file)
  return formData
}
