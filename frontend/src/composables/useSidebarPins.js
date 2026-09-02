import { ref, watch } from 'vue'
import { MAX_SIDEBAR_PINS } from '@/utils/sidebarMenu'

function storageKey(userId) {
  return `iss.sidebar.pins.${userId || 'anon'}`
}

function readPins(userId) {
  try {
    const raw = localStorage.getItem(storageKey(userId))
    if (!raw) return []
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return []
    return parsed.filter((id) => typeof id === 'string').slice(0, MAX_SIDEBAR_PINS)
  } catch {
    return []
  }
}

function writePins(userId, ids) {
  try {
    localStorage.setItem(storageKey(userId), JSON.stringify(ids.slice(0, MAX_SIDEBAR_PINS)))
  } catch {
    /* ignore */
  }
}

export function useSidebarPins(getUserId) {
  const pinnedIds = ref(readPins(getUserId()))

  watch(
    () => getUserId(),
    (id) => {
      pinnedIds.value = readPins(id)
    }
  )

  function isPinned(pinId) {
    return pinnedIds.value.includes(pinId)
  }

  function togglePin(pinId) {
    const current = [...pinnedIds.value]
    const idx = current.indexOf(pinId)
    if (idx !== -1) {
      current.splice(idx, 1)
    } else {
      if (current.length >= MAX_SIDEBAR_PINS) return false
      current.push(pinId)
    }
    pinnedIds.value = current
    writePins(getUserId(), current)
    return true
  }

  function removePin(pinId) {
    if (!isPinned(pinId)) return
    pinnedIds.value = pinnedIds.value.filter((id) => id !== pinId)
    writePins(getUserId(), pinnedIds.value)
  }

  return {
    pinnedIds,
    isPinned,
    togglePin,
    removePin,
    MAX_PINS: MAX_SIDEBAR_PINS,
  }
}
