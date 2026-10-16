/**
 * Helper media stimulus: YouTube ID + label tipe.
 */

export function extractYoutubeId(url) {
  if (!url || typeof url !== 'string') return null
  const trimmed = url.trim()
  if (/^[A-Za-z0-9_-]{6,20}$/.test(trimmed)) return trimmed
  const patterns = [
    /(?:youtube\.com\/watch\?[^#]*v=|youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]{6,20})/
  ]
  for (const re of patterns) {
    const m = trimmed.match(re)
    if (m?.[1]) return m[1]
  }
  return null
}

export function youtubeEmbedUrl(videoId) {
  return `https://www.youtube-nocookie.com/embed/${videoId}`
}

export function mediaTypeLabel(type) {
  const map = {
    image: 'Gambar',
    audio: 'Audio',
    video: 'Video',
    youtube: 'YouTube',
    text: 'Teks',
    mixed: 'Campuran'
  }
  return map[type] || type || 'Media'
}

export function acceptForKind(kind) {
  if (kind === 'audio') return 'audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,audio/x-m4a,.mp3,.wav,.ogg,.m4a'
  if (kind === 'video') return 'video/mp4,video/webm,video/ogg,.mp4,.webm,.ogg'
  return 'image/jpeg,image/png,image/gif,image/webp'
}
