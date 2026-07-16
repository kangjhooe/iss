<script setup>
/**
 * CKEditor 5 Community (GPL) — lokal, tanpa SaaS / premium plugins.
 */
import { computed, ref, watch } from 'vue'
import { Ckeditor } from '@ckeditor/ckeditor5-vue'
import {
  ClassicEditor,
  Essentials,
  Paragraph,
  Bold,
  Italic,
  Underline,
  Font,
  Alignment,
  List,
  Table,
  TableToolbar,
  TableProperties,
  TableCellProperties,
  Image,
  ImageToolbar,
  ImageUpload,
  ImageCaption,
  ImageStyle,
  ImageResize,
  SimpleUploadAdapter,
  HorizontalLine,
  Undo,
  Heading,
  GeneralHtmlSupport
} from 'ckeditor5'
import 'ckeditor5/ckeditor5.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'ready'])

const editor = ClassicEditor
const editorInstance = ref(null)

const apiBase = import.meta.env.VITE_API_BASE_URL || '/api'

const config = {
  licenseKey: 'GPL',
  plugins: [
    Essentials,
    Paragraph,
    Heading,
    Bold,
    Italic,
    Underline,
    Font,
    Alignment,
    List,
    Table,
    TableToolbar,
    TableProperties,
    TableCellProperties,
    Image,
    ImageToolbar,
    ImageUpload,
    ImageCaption,
    ImageStyle,
    ImageResize,
    SimpleUploadAdapter,
    HorizontalLine,
    Undo,
    GeneralHtmlSupport
  ],
  toolbar: {
    items: [
      'undo', 'redo',
      '|',
      'heading',
      '|',
      'fontFamily', 'fontSize',
      '|',
      'bold', 'italic', 'underline',
      '|',
      'alignment',
      '|',
      'numberedList', 'bulletedList',
      '|',
      'insertTable', 'uploadImage', 'horizontalLine'
    ],
    shouldNotGroupWhenFull: true
  },
  fontFamily: {
    options: [
      'default',
      'Times New Roman, Times, serif',
      'Arial, Helvetica, sans-serif',
      'Georgia, serif',
      'Courier New, Courier, monospace'
    ]
  },
  fontSize: {
    options: [10, 11, 12, 14, 16, 18, 20, 24, 28, 32]
  },
  alignment: {
    options: ['left', 'center', 'right', 'justify']
  },
  table: {
    contentToolbar: [
      'tableColumn', 'tableRow', 'mergeTableCells',
      'tableProperties', 'tableCellProperties'
    ]
  },
  image: {
    toolbar: [
      'imageStyle:inline', 'imageStyle:block', 'imageStyle:side',
      '|', 'toggleImageCaption', 'imageTextAlternative'
    ]
  },
  simpleUpload: {
    uploadUrl: `${apiBase}/v1/surat/upload-image`,
    withCredentials: true
  },
  htmlSupport: {
    allow: [
      { name: /.*/, attributes: true, classes: true, styles: true }
    ]
  }
}

const data = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

function onReady(instance) {
  editorInstance.value = instance
  emit('ready', instance)
  if (props.disabled) {
    instance.enableReadOnlyMode('surat-readonly')
  }
}

watch(() => props.disabled, (disabled) => {
  if (!editorInstance.value) return
  if (disabled) {
    editorInstance.value.enableReadOnlyMode('surat-readonly')
  } else {
    editorInstance.value.disableReadOnlyMode('surat-readonly')
  }
})
</script>

<template>
  <div class="editor-surat" :class="{ 'is-disabled': disabled }">
    <Ckeditor
      v-model="data"
      :editor="editor"
      :config="config"
      @ready="onReady"
    />
  </div>
</template>

<style scoped>
.editor-surat {
  width: 100%;
}

.editor-surat :deep(.ck.ck-editor) {
  width: 100%;
}

.editor-surat :deep(.ck.ck-toolbar) {
  border: none !important;
  border-bottom: 1px solid #e0e0e0 !important;
  background: #fafafa !important;
  border-radius: 0 !important;
  position: sticky;
  top: 0;
  z-index: 5;
}

.editor-surat :deep(.ck.ck-editor__main > .ck-editor__editable) {
  border: none !important;
  box-shadow: none !important;
  min-height: 900px;
  padding: 0 !important;
  background: transparent !important;
}

.editor-surat :deep(.ck-focused) {
  border: none !important;
  box-shadow: none !important;
}

.editor-surat :deep(.ck-content) {
  font-family: 'Times New Roman', Times, serif;
  font-size: 12pt;
  line-height: 1.6;
  color: #111;
}

.editor-surat :deep(.ck-content p) {
  margin: 0 0 0.6em;
}

.editor-surat :deep(.ck-content table) {
  border-collapse: collapse;
  width: 100%;
}

.editor-surat :deep(.ck-content td),
.editor-surat :deep(.ck-content th) {
  border: 1px solid #333;
  padding: 4px 8px;
}
</style>
