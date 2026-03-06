<template>
  <div class="rich-text-editor">
    <QuillEditor
      ref="editorRef"
      :content="modelValue"
      content-type="html"
      theme="snow"
      toolbar="full"
      :placeholder="placeholder"
      :options="editorOptions"
      @update:content="emit('update:modelValue', $event)"
      @ready="onEditorReady"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { examApi } from '@/api/exam'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis di sini...' },
  minHeight: { type: String, default: '120px' }
})

const emit = defineEmits(['update:modelValue'])

const editorRef = ref(null)

const editorOptions = {
  placeholder: props.placeholder
}

function onEditorReady(quill) {
  const toolbar = quill.getModule('toolbar')
  if (!toolbar) return
  toolbar.addHandler('image', imageHandler.bind(null, quill))
}

function imageHandler(quill) {
  const input = document.createElement('input')
  input.setAttribute('type', 'file')
  input.setAttribute('accept', 'image/jpeg,image/png,image/gif')
  input.click()
  input.onchange = async () => {
    const file = input.files?.[0]
    if (!file) return
    try {
      const res = await examApi.uploadQuestionImage(file)
      const url = res.data?.location || res.data?.url
      if (url) {
        const range = quill.getSelection(true)
        quill.insertEmbed(range?.index ?? quill.getLength(), 'image', url)
        quill.setSelection((range?.index ?? quill.getLength()) + 1)
      }
    } catch (e) {
      console.error('Upload gambar gagal:', e)
      alert(e.response?.data?.message || 'Gagal mengunggah gambar.')
    }
  }
}
</script>

<style scoped>
.rich-text-editor :deep(.ql-container) {
  min-height: v-bind(minHeight);
  font-size: 14px;
}
.rich-text-editor :deep(.ql-editor) {
  min-height: v-bind(minHeight);
}
</style>
