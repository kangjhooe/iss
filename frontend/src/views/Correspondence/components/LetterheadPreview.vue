<script setup>
defineProps({
  html: { type: String, default: '' }
})
</script>

<template>
  <div v-if="html" class="kop-preview" v-html="html" />
</template>

<style scoped>
.kop-preview {
  margin-bottom: 8px;
  font-family: 'Times New Roman', Times, serif;
}

.kop-preview :deep(table td),
.kop-preview :deep(table th) {
  border: none !important;
}

.kop-preview :deep(img) {
  max-width: 70px;
  max-height: 70px;
  object-fit: contain;
}
</style>
