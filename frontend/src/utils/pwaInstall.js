/**
 * Capture the browser install prompt as soon as the app boots.
 * beforeinstallprompt can fire before Vue components mount.
 */

let deferredPrompt = null
const subscribers = new Set()

function notify() {
  subscribers.forEach((fn) => fn(deferredPrompt))
}

if (typeof window !== 'undefined') {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt = event
    notify()
  })

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null
    notify()
  })
}

export function getDeferredPrompt() {
  return deferredPrompt
}

export function clearDeferredPrompt() {
  deferredPrompt = null
  notify()
}

export function onInstallPromptChange(fn) {
  subscribers.add(fn)
  fn(deferredPrompt)
  return () => subscribers.delete(fn)
}

export function isStandaloneDisplay() {
  return window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true
}

export function isIosDevice() {
  const ua = window.navigator.userAgent.toLowerCase()
  const isIos = /iphone|ipad|ipod/.test(ua)
  const isIpadOs = window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1
  return isIos || isIpadOs
}
