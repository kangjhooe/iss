<template>
  <div class="app-logo" :class="{ 'app-logo--img': appLogoUrl }">
    <img
      v-if="appLogoUrl"
      :src="appLogoUrl"
      alt="Logo aplikasi"
      class="app-logo-img"
      :style="imgStyle"
    />
    <svg
      v-else
      class="app-logo-svg"
      :width="size"
      :height="size"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="currentColor"/>
      <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAppBrandingStore } from '@/stores/appBranding'

const props = defineProps({
  size: { type: [Number, String], default: 48 }
})

const appBranding = useAppBrandingStore()

const appLogoUrl = computed(() => appBranding.appLogoUrl)

const imgStyle = computed(() => ({
  width: typeof props.size === 'number' ? `${props.size}px` : props.size,
  height: typeof props.size === 'number' ? `${props.size}px` : props.size
}))

onMounted(() => {
  appBranding.fetchBranding()
})
</script>

<style scoped>
.app-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #059669;
}

.app-logo--img .app-logo-img {
  object-fit: contain;
  display: block;
}

.app-logo-svg {
  vertical-align: middle;
}
</style>
