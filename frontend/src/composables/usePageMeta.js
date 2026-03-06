/**
 * Set document title and meta tags (description, Open Graph).
 * Useful for public pages like school profile. Call clear() on route leave if needed.
 */
export function usePageMeta() {
  const defaultTitle = 'Sekolah'
  const defaultDescription = 'Profil sekolah/madrasah'

  function setMeta({ title = defaultTitle, description = defaultDescription, image = null, url = null } = {}) {
    if (typeof document === 'undefined') return

    document.title = title

    setMetaTag('name', 'description', description)
    setMetaTag('property', 'og:title', title)
    setMetaTag('property', 'og:description', description)
    if (url) setMetaTag('property', 'og:url', url)
    if (image) setMetaTag('property', 'og:image', image)
  }

  function setMetaTag(attrName, attrValue, content) {
    if (!content) return
    let el = document.querySelector(`meta[${attrName}="${attrValue}"]`)
    if (!el) {
      el = document.createElement('meta')
      el.setAttribute(attrName, attrValue)
      document.head.appendChild(el)
    }
    el.setAttribute('content', content)
  }

  function clear() {
    document.title = defaultTitle
    setMetaTag('name', 'description', defaultDescription)
    setMetaTag('property', 'og:title', defaultTitle)
    setMetaTag('property', 'og:description', defaultDescription)
    const ogUrl = document.querySelector('meta[property="og:url"]')
    if (ogUrl) ogUrl.remove()
    const ogImage = document.querySelector('meta[property="og:image"]')
    if (ogImage) ogImage.remove()
  }

  return { setMeta, clear }
}
