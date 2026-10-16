import { computed, ref, watch } from 'vue'

function storageKey(userId) {
  return `iss.menu.layout.${userId || 'anon'}`
}

function readLayout(userId) {
  try {
    const raw = localStorage.getItem(storageKey(userId))
    if (raw === 'navbar' || raw === 'sidebar') return raw
  } catch {
    /* ignore */
  }
  return 'sidebar'
}

function writeLayout(userId, mode) {
  try {
    localStorage.setItem(storageKey(userId), mode)
  } catch {
    /* ignore */
  }
}

export function useMenuLayout(getUserId) {
  const menuLayout = ref(readLayout(getUserId()))

  watch(
    () => getUserId(),
    (id) => {
      menuLayout.value = readLayout(id)
    }
  )

  const isNavbarLayout = computed(() => menuLayout.value === 'navbar')

  function setMenuLayout(next) {
    const mode = next === 'navbar' ? 'navbar' : 'sidebar'
    menuLayout.value = mode
    writeLayout(getUserId(), mode)
  }

  return { menuLayout, isNavbarLayout, setMenuLayout }
}
