import { computed, onMounted, onUnmounted, ref } from 'vue'

const ACCORDION_KEY = 'iss.sidebar.accordionMode'
const LAPTOP_MAX_WIDTH = 1440

/** @typedef {'auto' | 'on' | 'off'} AccordionMode */

function readMode() {
  try {
    const raw = localStorage.getItem(ACCORDION_KEY)
    if (raw === 'on' || raw === 'off' || raw === 'auto') return raw
  } catch {
    /* ignore */
  }
  return 'auto'
}

function writeMode(mode) {
  try {
    localStorage.setItem(ACCORDION_KEY, mode)
  } catch {
    /* ignore */
  }
}

export function resolveAccordionEnabled(mode, windowWidth) {
  if (mode === 'on') return true
  if (mode === 'off') return false
  return windowWidth <= LAPTOP_MAX_WIDTH
}

const MODE_LABELS = {
  auto: 'Otomatis (laptop)',
  on: 'Satu grup',
  off: 'Bebas',
}

export function useSidebarAccordion() {
  const mode = ref(readMode())
  const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1920)

  const enabled = computed(() => resolveAccordionEnabled(mode.value, windowWidth.value))
  const modeLabel = computed(() => MODE_LABELS[mode.value] || MODE_LABELS.auto)

  function onResize() {
    windowWidth.value = window.innerWidth
  }

  function cycleMode() {
    const order = ['auto', 'on', 'off']
    const idx = order.indexOf(mode.value)
    const next = order[(idx + 1) % order.length]
    mode.value = next
    writeMode(next)
  }

  onMounted(() => {
    window.addEventListener('resize', onResize)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', onResize)
  })

  return {
    accordionMode: mode,
    accordionEnabled: enabled,
    accordionModeLabel: modeLabel,
    cycleAccordionMode: cycleMode,
  }
}
