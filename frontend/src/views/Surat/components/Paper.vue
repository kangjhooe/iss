<script setup>
defineProps({
  zoom: { type: Number, default: 1 }
})
</script>

<template>
  <div class="surat-page-stage">
    <div class="surat-page-shell" :style="{ transform: `scale(${zoom})` }">
      <div class="surat-page-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<style src="@/styles/surat-page.css"></style>
