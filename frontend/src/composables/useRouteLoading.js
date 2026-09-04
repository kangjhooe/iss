import { ref } from 'vue'

/** Shared flag: true while Vue Router resolves a lazy route / navigation. */
const isRouteLoading = ref(false)
let navToken = 0

export function useRouteLoading() {
  return { isRouteLoading }
}

export function startRouteLoading() {
  navToken += 1
  isRouteLoading.value = true
  return navToken
}

export function stopRouteLoading(token) {
  if (token != null && token !== navToken) return
  isRouteLoading.value = false
}
