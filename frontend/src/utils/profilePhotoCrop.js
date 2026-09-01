export const PROFILE_PHOTO_CROP_ASPECT = 3 / 4
export const PROFILE_PHOTO_OUTPUT_WIDTH = 600
export const PROFILE_PHOTO_OUTPUT_HEIGHT = 800
export const PROFILE_PHOTO_SOURCE_MAX_BYTES = 10 * 1024 * 1024

export function computeAutoCropRect(width, height, aspect = PROFILE_PHOTO_CROP_ASPECT) {
  const imgAspect = width / height

  if (imgAspect > aspect) {
    const cropHeight = height
    const cropWidth = height * aspect
    return {
      x: (width - cropWidth) / 2,
      y: 0,
      width: cropWidth,
      height: cropHeight,
    }
  }

  const cropWidth = width
  const cropHeight = width / aspect
  return {
    x: 0,
    y: (height - cropHeight) / 2,
    width: cropWidth,
    height: cropHeight,
  }
}

export function clampCropRect(rect, imageWidth, imageHeight, aspect = PROFILE_PHOTO_CROP_ASPECT) {
  let { x, y, width, height } = rect

  width = Math.max(1, Math.min(width, imageWidth))
  height = width / aspect
  if (height > imageHeight) {
    height = imageHeight
    width = height * aspect
  }

  x = Math.max(0, Math.min(x, imageWidth - width))
  y = Math.max(0, Math.min(y, imageHeight - height))

  return { x, y, width, height }
}

export function loadImageFromFile(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const image = new Image()
    image.onload = () => resolve(image)
    image.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('Gagal memuat gambar.'))
    }
    image.src = url
  })
}

async function canvasToJpegBlob(canvas, quality) {
  return new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (!blob) {
        reject(new Error('Gagal memproses foto.'))
        return
      }
      resolve(blob)
    }, 'image/jpeg', quality)
  })
}

export async function cropImageToJpegFile(image, rect, maxBytes = 1024 * 1024) {
  const canvas = document.createElement('canvas')
  canvas.width = PROFILE_PHOTO_OUTPUT_WIDTH
  canvas.height = PROFILE_PHOTO_OUTPUT_HEIGHT

  const ctx = canvas.getContext('2d')
  if (!ctx) {
    throw new Error('Gagal memproses foto.')
  }

  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  ctx.drawImage(
    image,
    rect.x,
    rect.y,
    rect.width,
    rect.height,
    0,
    0,
    canvas.width,
    canvas.height,
  )

  let quality = 0.88
  let blob = await canvasToJpegBlob(canvas, quality)

  while (blob.size > maxBytes && quality > 0.45) {
    quality -= 0.08
    blob = await canvasToJpegBlob(canvas, quality)
  }

  if (blob.size > maxBytes) {
    throw new Error('Foto hasil crop masih terlalu besar. Coba area crop lebih kecil.')
  }

  return new File([blob], 'photo.jpg', { type: 'image/jpeg' })
}
