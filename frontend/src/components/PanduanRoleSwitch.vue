<template>
  <nav
    v-if="roles.length"
    class="role-switch"
    aria-label="Pindah ke panduan peran lain"
  >
    <p class="role-switch__label">Panduan peran lain</p>
    <div class="role-switch__links">
      <router-link
        v-for="role in roles"
        :key="role.slug"
        :to="role.to"
        class="role-switch__link"
        :class="[
          `role-switch__link--${role.accent}`,
          { 'role-switch__link--pending': pendingSlug === role.slug },
        ]"
        :aria-busy="pendingSlug === role.slug ? 'true' : undefined"
        @mouseenter="prefetchRole(role)"
        @focus="prefetchRole(role)"
        @click="onRoleClick(role, $event)"
      >
        <span
          v-if="pendingSlug === role.slug"
          class="role-switch__spinner"
          aria-hidden="true"
        />
        {{ pendingSlug === role.slug ? 'Memuat…' : role.navLabel }}
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { guideRoles } from '@/content/guides'
import { useRouteLoading } from '@/composables/useRouteLoading'

const props = defineProps({
  currentSlug: {
    type: String,
    required: true,
  },
})

const NAV_LABELS = {
  admin: 'Admin',
  guru: 'Guru',
  siswa: 'Siswa',
  'orang-tua': 'Orang Tua',
}

const PREFETCHERS = {
  admin: () => import('@/views/PanduanAdmin.vue'),
  guru: () => import('@/views/PanduanGuru.vue'),
  siswa: () => import('@/views/PanduanSiswa.vue'),
  'orang-tua': () => import('@/views/PanduanOrangTua.vue'),
}

const route = useRoute()
const { isRouteLoading } = useRouteLoading()
const pendingSlug = ref(null)
const prefetched = new Set()

const roles = computed(() =>
  guideRoles
    .filter((role) => role.available && role.slug !== props.currentSlug)
    .map((role) => ({
      ...role,
      navLabel: NAV_LABELS[role.slug] || role.title,
    }))
)

function prefetchRole(role) {
  const load = PREFETCHERS[role.slug]
  if (!load || prefetched.has(role.slug)) return
  prefetched.add(role.slug)
  load().catch(() => {
    prefetched.delete(role.slug)
  })
}

function onRoleClick(role, event) {
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
    return
  }
  if (isRouteLoading.value && pendingSlug.value) {
    event.preventDefault()
    return
  }
  pendingSlug.value = role.slug
  prefetchRole(role)
}

watch(
  () => route.path,
  () => {
    pendingSlug.value = null
  }
)

watch(isRouteLoading, (loading) => {
  if (!loading) pendingSlug.value = null
})

onMounted(() => {
  const run = () => {
    roles.value.forEach((role) => prefetchRole(role))
  }
  if (typeof window.requestIdleCallback === 'function') {
    window.requestIdleCallback(run, { timeout: 2500 })
  } else {
    setTimeout(run, 600)
  }
})
</script>

<style scoped>
.role-switch {
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid rgba(148, 163, 184, 0.35);
}

.role-switch__label {
  margin: 0 0 10px;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.role-switch__links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.role-switch__link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 650;
  text-decoration: none;
  transition: transform 0.15s ease, border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
}

.role-switch__link:hover {
  transform: translateY(-1px);
}

.role-switch__link--pending {
  pointer-events: none;
  opacity: 0.85;
  cursor: wait;
}

.role-switch__spinner {
  width: 12px;
  height: 12px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: role-switch-spin 0.7s linear infinite;
}

@keyframes role-switch-spin {
  to { transform: rotate(360deg); }
}

.role-switch__link--teal:hover {
  border-color: #99f6e4;
  background: #f0fdfa;
  color: #0f766e;
}

.role-switch__link--blue:hover {
  border-color: #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
}

.role-switch__link--amber:hover {
  border-color: #fde68a;
  background: #fffbeb;
  color: #b45309;
}

.role-switch__link--violet:hover {
  border-color: #ddd6fe;
  background: #f5f3ff;
  color: #6d28d9;
}

.role-switch--footer {
  margin-top: 20px;
  padding-top: 18px;
}
</style>
