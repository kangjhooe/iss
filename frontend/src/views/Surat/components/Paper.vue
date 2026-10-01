<script setup>
defineProps({
  zoom: { type: Number, default: 1 },
  density: { type: String, default: 'compact' }
})
</script>

<template>
  <div class="surat-page-stage">
    <div class="surat-page-shell" :style="{ transform: `scale(${zoom})` }">
      <div class="surat-page-content" :class="`surat-density-${density === 'relaxed' ? 'relaxed' : 'compact'}`">
        <slot />
      </div>
    </div>
  </div>
</template>

<style>
@import '@/styles/surat-page.css';
</style>
