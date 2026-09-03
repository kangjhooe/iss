<template>
  <nav
    ref="rootEl"
    class="public-navbar"
    :class="{
      'public-navbar--scrolled': scrolled,
      'public-navbar--fixed': fixed,
    }"
  >
    <div class="public-navbar__inner">
      <router-link to="/" class="public-navbar__brand" @click="closeMenu">
        <div class="public-navbar__logo">
          <AppLogo :size="36" />
        </div>
        <span class="public-navbar__title">{{ appName }}</span>
      </router-link>

      <div class="public-navbar__links">
        <router-link to="/panduan" class="public-navbar__link">Panduan</router-link>
        <router-link to="/catatan-rilis" class="public-navbar__link">Update</router-link>
        <router-link to="/login?demo=1" class="public-navbar__link">Coba Demo</router-link>
      </div>

      <div class="public-navbar__auth">
        <router-link to="/login" class="public-navbar__login">Masuk</router-link>
        <router-link to="/register" class="public-navbar__register">Daftar</router-link>
        <button
          type="button"
          class="public-navbar__toggle"
          :aria-expanded="menuOpen"
          aria-controls="public-nav-mobile-menu"
          :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
          @click="menuOpen = !menuOpen"
        >
          <span class="public-navbar__toggle-bar" :class="{ open: menuOpen }"></span>
          <span class="public-navbar__toggle-bar" :class="{ open: menuOpen }"></span>
          <span class="public-navbar__toggle-bar" :class="{ open: menuOpen }"></span>
        </button>
      </div>
    </div>
  </nav>

  <div
    v-if="fixed"
    class="public-navbar__spacer"
    :style="{ height: `${navHeight}px` }"
    aria-hidden="true"
  />

  <Teleport to="body">
    <Transition name="public-nav-menu">
      <div
        v-if="menuOpen"
        class="public-navbar__backdrop"
        @click="closeMenu"
      />
    </Transition>
    <Transition name="public-nav-drawer">
      <div
        v-if="menuOpen"
        id="public-nav-mobile-menu"
        class="public-navbar__drawer"
        role="dialog"
        aria-label="Menu"
      >
        <button type="button" class="public-navbar__drawer-close" aria-label="Tutup menu" @click="closeMenu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6L6 18M6 6l12 12" />
          </svg>
        </button>
        <router-link to="/panduan" class="public-navbar__drawer-link" @click="closeMenu">Panduan</router-link>
        <router-link to="/catatan-rilis" class="public-navbar__drawer-link" @click="closeMenu">Update</router-link>
        <router-link to="/login?demo=1" class="public-navbar__drawer-link" @click="closeMenu">Coba Demo</router-link>
        <div class="public-navbar__drawer-auth">
          <router-link to="/login" class="public-navbar__login" @click="closeMenu">Masuk</router-link>
          <router-link to="/register" class="public-navbar__register" @click="closeMenu">Daftar</router-link>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { appName } from '@/config/app'
import AppLogo from '@/components/AppLogo.vue'

defineProps({
  /**
   * Navbar tetap di atas saat scroll (position: fixed + spacer).
   * Default true: sticky CSS sering gagal karena overflow-x:hidden di App.
   */
  fixed: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['height-change'])

const rootEl = ref(null)
const menuOpen = ref(false)
const scrolled = ref(false)
const navHeight = ref(57)
let scrollRaf = 0
let resizeObserver = null

function closeMenu() {
  menuOpen.value = false
}

function onMenuKeydown(e) {
  if (e.key === 'Escape') closeMenu()
}

function updateScroll() {
  scrolled.value = window.scrollY > 12
}

function onScroll() {
  if (scrollRaf) return
  scrollRaf = requestAnimationFrame(() => {
    scrollRaf = 0
    updateScroll()
    // Compact state mengubah tinggi — ukur ulang setelah frame berikutnya
    nextTick(measureHeight)
  })
}

function measureHeight({ force = false } = {}) {
  if (!rootEl.value) return
  const next = Math.ceil(rootEl.value.getBoundingClientRect().height)
  if (next === navHeight.value && !force) return
  navHeight.value = next
  emit('height-change', next)
}

function onWindowResize() {
  measureHeight()
}

watch(menuOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

watch(scrolled, () => {
  nextTick(() => measureHeight())
})

onMounted(() => {
  updateScroll()
  measureHeight({ force: true })
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onMenuKeydown)
  window.addEventListener('resize', onWindowResize, { passive: true })
  if (typeof ResizeObserver !== 'undefined' && rootEl.value) {
    resizeObserver = new ResizeObserver(() => measureHeight())
    resizeObserver.observe(rootEl.value)
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onMenuKeydown)
  window.removeEventListener('resize', onWindowResize)
  if (scrollRaf) cancelAnimationFrame(scrollRaf)
  resizeObserver?.disconnect()
  resizeObserver = null
  document.body.style.overflow = ''
})

defineExpose({
  rootEl,
  height: navHeight,
  measureHeight,
})
</script>

<style scoped>
@keyframes public-navbar-enter {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.public-navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-bottom: 1px solid transparent;
  padding-top: env(safe-area-inset-top, 0);
  transition:
    background 0.25s ease,
    border-color 0.25s ease,
    box-shadow 0.25s ease;
  animation: public-navbar-enter 0.4s ease both;
}

.public-navbar--fixed {
  position: fixed;
  left: 0;
  right: 0;
}

.public-navbar--scrolled {
  background: rgba(255, 255, 255, 0.86);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom-color: #e2e8f0;
  box-shadow:
    0 1px 0 rgba(15, 23, 42, 0.04),
    0 10px 28px -16px rgba(15, 23, 42, 0.18);
}

.public-navbar__spacer {
  flex-shrink: 0;
  width: 100%;
}

.public-navbar__inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 12px 24px;
  padding-left: max(24px, env(safe-area-inset-left));
  padding-right: max(24px, env(safe-area-inset-right));
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: nowrap;
  transition: padding 0.25s ease;
}

.public-navbar--scrolled .public-navbar__inner {
  padding-top: 8px;
  padding-bottom: 8px;
}

.public-navbar__brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
  font-size: 18px;
  flex-shrink: 0;
  transition: color 0.2s ease;
}

.public-navbar__brand:hover {
  color: #059669;
}

.public-navbar__logo {
  display: flex;
  transition: transform 0.25s ease;
}

.public-navbar__brand:hover .public-navbar__logo {
  transform: scale(1.04);
}

.public-navbar__title {
  white-space: nowrap;
}

.public-navbar__links {
  display: flex;
  align-items: center;
  gap: 4px 20px;
  flex: 1;
  min-width: 0;
}

.public-navbar__link {
  position: relative;
  color: #64748b;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  transition: color 0.2s ease;
  padding: 8px 4px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
}

.public-navbar__link::after {
  content: '';
  position: absolute;
  left: 4px;
  right: 4px;
  bottom: 6px;
  height: 1.5px;
  background: #059669;
  border-radius: 1px;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.2s ease;
  pointer-events: none;
}

.public-navbar__link:hover {
  color: #059669;
}

.public-navbar__link:hover::after,
.public-navbar__link.router-link-active::after {
  transform: scaleX(1);
}

.public-navbar__link.router-link-active {
  color: #059669;
}

.public-navbar__link:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
  border-radius: 6px;
}

.public-navbar__auth {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
  padding-left: 20px;
  border-left: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.public-navbar__login {
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  padding: 8px 12px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: color 0.2s ease, background 0.2s ease;
  -webkit-tap-highlight-color: transparent;
}

.public-navbar__login:hover {
  color: #059669;
  background: #f8fafc;
}

.public-navbar__login:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.public-navbar__register {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  padding: 10px 18px;
  min-height: 44px;
  border-radius: 8px;
  text-decoration: none;
  background: #059669;
  color: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  -webkit-tap-highlight-color: transparent;
}

.public-navbar__register:hover {
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px -4px rgba(5, 150, 105, 0.45);
}

.public-navbar__register:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.public-navbar__toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 44px;
  min-width: 44px;
  height: 44px;
  padding: 10px;
  background: transparent;
  border: none;
  cursor: pointer;
  border-radius: 8px;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  color: #1e293b;
}

.public-navbar__toggle:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.public-navbar__toggle-bar {
  display: block;
  width: 22px;
  height: 2px;
  background: currentColor;
  border-radius: 1px;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.public-navbar__toggle-bar.open:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.public-navbar__toggle-bar.open:nth-child(2) {
  opacity: 0;
}

.public-navbar__toggle-bar.open:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

.public-navbar__backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 199;
  -webkit-tap-highlight-color: transparent;
}

.public-navbar__drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: min(280px, 85vw);
  background: #ffffff;
  z-index: 200;
  padding: 72px 24px 24px;
  padding-top: max(72px, calc(env(safe-area-inset-top) + 56px));
  padding-right: max(24px, env(safe-area-inset-right));
  padding-bottom: max(24px, env(safe-area-inset-bottom));
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
}

.public-navbar__drawer-close {
  position: absolute;
  top: max(16px, env(safe-area-inset-top));
  right: max(16px, env(safe-area-inset-right));
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: #f8fafc;
  border-radius: 10px;
  color: #334155;
  cursor: pointer;
}

.public-navbar__drawer-close:hover {
  background: #ecfdf5;
  color: #059669;
}

.public-navbar__drawer-link {
  padding: 14px 16px;
  border-radius: 8px;
  color: #1e293b;
  text-decoration: none;
  font-size: 16px;
  font-weight: 500;
  min-height: 48px;
  display: flex;
  align-items: center;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s, color 0.15s;
}

.public-navbar__drawer-link:hover,
.public-navbar__drawer-link.router-link-active {
  background: #f1f5f9;
  color: #059669;
}

.public-navbar__drawer-link:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 2px;
}

.public-navbar__drawer-auth {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 8px;
  align-items: center;
}

.public-navbar__drawer-auth .public-navbar__login,
.public-navbar__drawer-auth .public-navbar__register {
  flex: 1;
  justify-content: center;
}

.public-nav-menu-enter-active,
.public-nav-menu-leave-active {
  transition: opacity 0.2s ease;
}

.public-nav-menu-enter-from,
.public-nav-menu-leave-to {
  opacity: 0;
}

.public-nav-drawer-enter-active,
.public-nav-drawer-leave-active {
  transition: transform 0.25s ease;
}

.public-nav-drawer-enter-from,
.public-nav-drawer-leave-to {
  transform: translateX(100%);
}

@media (prefers-reduced-motion: reduce) {
  .public-navbar {
    animation: none;
  }

  .public-navbar,
  .public-navbar__inner,
  .public-navbar__brand,
  .public-navbar__logo,
  .public-navbar__link,
  .public-navbar__link::after,
  .public-navbar__login,
  .public-navbar__register,
  .public-navbar__toggle-bar {
    transition: none;
  }

  .public-navbar__brand:hover .public-navbar__logo,
  .public-navbar__register:hover {
    transform: none;
  }

  .public-nav-menu-enter-active,
  .public-nav-menu-leave-active,
  .public-nav-drawer-enter-active,
  .public-nav-drawer-leave-active {
    transition: none;
  }
}

@media (max-width: 768px) {
  .public-navbar__links {
    display: none;
  }

  .public-navbar__toggle {
    display: flex;
  }

  .public-navbar__auth {
    border-left: none;
    padding-left: 0;
    gap: 6px;
  }

  .public-navbar__inner {
    padding: 12px 20px;
    padding-left: max(20px, env(safe-area-inset-left));
    padding-right: max(20px, env(safe-area-inset-right));
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .public-navbar__inner {
    padding: 12px 16px;
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
  }

  .public-navbar__brand {
    font-size: 16px;
  }

  .public-navbar__title {
    font-size: 15px;
    line-height: 1.3;
  }

  .public-navbar__login {
    padding: 8px;
    font-size: 13px;
  }

  .public-navbar__register {
    padding: 8px 14px;
    min-height: 40px;
    font-size: 13px;
  }
}

@media (hover: none) and (pointer: coarse) {
  .public-navbar__brand:hover .public-navbar__logo,
  .public-navbar__register:hover {
    transform: none;
  }

  .public-navbar__register:hover {
    box-shadow: none;
  }

  .public-navbar__link:hover::after {
    transform: scaleX(0);
  }

  .public-navbar__link.router-link-active::after {
    transform: scaleX(1);
  }
}
</style>
