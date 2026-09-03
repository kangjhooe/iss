/**
 * Set document title and meta tags (description, Open Graph).
 * Useful for public pages like school profile. Call clear() on route leave if needed.
 */
import { setMetaContent } from '@/utils/seo'

export function usePageMeta() {
  const defaultTitle = 'Sekolah'
  const defaultDescription = 'Profil sekolah/madrasah'

  function setMeta({ title = defaultTitle, description = defaultDescription, image = null, url = null } = {}) {
    if (typeof document === 'undefined') return

    document.title = title

    setMetaContent('name', 'description', description)
    setMetaContent('property', 'og:title', title)
    setMetaContent('property', 'og:description', description)
    if (url) setMetaContent('property', 'og:url', url)
    if (image) setMetaContent('property', 'og:image', image)
    setMetaContent('name', 'twitter:title', title)
    setMetaContent('name', 'twitter:description', description)
    if (image) setMetaContent('name', 'twitter:image', image)
  }

  function clear() {
    document.title = defaultTitle
    setMetaContent('name', 'description', defaultDescription)
    setMetaContent('property', 'og:title', defaultTitle)
    setMetaContent('property', 'og:description', defaultDescription)
    const ogUrl = document.querySelector('meta[property="og:url"]')
    if (ogUrl) ogUrl.remove()
    const ogImage = document.querySelector('meta[property="og:image"]')
    if (ogImage) ogImage.remove()
  }

  return { setMeta, clear }
}
