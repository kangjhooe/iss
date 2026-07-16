<script setup>
defineProps({
  html: { type: String, default: '' }
})
</script>

<template>
  <div v-if="html" class="ttd-preview" v-html="html" />
</template>

<style scoped>
.ttd-preview {
  margin-top: 24px;
  font-family: 'Times New Roman', Times, serif;
  font-size: 12pt;
}

.ttd-preview :deep(img) {
  max-width: none;
}
</style>
