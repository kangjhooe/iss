<template>
  <component :is="rootTag" ref="rootEl" class="question-html" v-html="html || ''" />
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { renderMathIn } from '@/utils/questionMath'

const props = defineProps({
  html: { type: String, default: '' },
  tag: { type: String, default: 'div' }
})

const rootEl = ref(null)
const rootTag = computed(() => (props.tag === 'span' ? 'span' : 'div'))

async function paint() {
  await nextTick()
  renderMathIn(rootEl.value)
}

watch(() => props.html, paint, { immediate: true })
</script>

<style scoped>
.question-html {
  line-height: 1.65;
  font-family: "Segoe UI", Tahoma, "Traditional Arabic", "Noto Naskh Arabic", "Arial Unicode MS", sans-serif;
  overflow-wrap: anywhere;
}
.question-html :deep(img) { max-width: 100%; height: auto; }
.question-html :deep(table) { border-collapse: collapse; max-width: 100%; }
.question-html :deep(th),
.question-html :deep(td) { border: 1px solid #d1d5db; padding: 0.35rem 0.5rem; }
.question-html :deep(.ql-align-center) { text-align: center; }
.question-html :deep(.ql-align-right) { text-align: right; }
.question-html :deep(.ql-align-justify) { text-align: justify; }
.question-html :deep(.ql-direction-rtl),
.question-html :deep([dir="rtl"]) { direction: rtl; text-align: right; }
.question-html :deep(.ql-direction-rtl.ql-align-center) { text-align: center; }
.question-html :deep(.ql-direction-rtl.ql-align-justify) { text-align: justify; }
.question-html :deep(.ql-size-small) { font-size: 0.75em; }
.question-html :deep(.ql-size-large) { font-size: 1.5em; }
.question-html :deep(.ql-size-huge) { font-size: 2.5em; }
.question-html :deep(.ql-font-serif) { font-family: Georgia, "Times New Roman", serif; }
.question-html :deep(.ql-font-monospace) { font-family: ui-monospace, monospace; }
.question-html :deep(.ql-indent-1) { padding-inline-start: 3em; }
.question-html :deep(.ql-indent-2) { padding-inline-start: 6em; }
.question-html :deep(.ql-indent-3) { padding-inline-start: 9em; }
.question-html :deep(.ql-indent-4) { padding-inline-start: 12em; }
.question-html :deep(.ql-indent-5) { padding-inline-start: 15em; }
.question-html :deep(.ql-indent-6) { padding-inline-start: 18em; }
.question-html :deep(.ql-indent-7) { padding-inline-start: 21em; }
.question-html :deep(.ql-indent-8) { padding-inline-start: 24em; }
.question-html :deep(.ql-formula) {
  white-space: nowrap;
  vertical-align: middle;
}
.question-html :deep(.ql-formula[data-display="block"]) {
  display: block;
  white-space: normal;
  text-align: center;
  margin: 0.6rem 0;
  overflow-x: auto;
}
</style>
