<template>
  <div class="rich-text-editor">
    <div v-if="formulaOpen" class="formula-dialog">
      <label class="formula-label">
        Rumus
      <textarea
        ref="formulaInput"
        v-model="formulaTex"
        rows="3"
        class="formula-input"
        placeholder="Contoh: H_2O, x^2, \frac{a}{b}"
        @keydown.esc.prevent="closeFormula"
      />
      </label>
      <div class="formula-examples">
        <button v-for="ex in examples" :key="ex.tex" type="button" class="formula-chip" @click="applyExample(ex)">{{ ex.label }}</button>
      </div>
      <label class="formula-display">
        <input v-model="formulaDisplay" type="checkbox" />
        Tampilkan di baris sendiri
      </label>
      <div ref="formulaPreview" class="formula-preview" :class="{ block: formulaDisplay }"></div>
      <p v-if="formulaError" class="formula-error">{{ formulaError }}</p>
      <p class="formula-hint">Huruf Arab diketik atau ditempel langsung. Arah kanan-ke-kiri ada di toolbar.</p>
      <div class="formula-actions">
        <button type="button" class="formula-insert" :disabled="!formulaTex.trim()" @click="insertFormula">{{ formulaIndex == null ? 'Sisipkan' : 'Perbarui' }}</button>
        <button type="button" class="formula-cancel" @click="closeFormula">Batal</button>
      </div>
    </div>
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
import { nextTick, ref, watch } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import Quill from 'quill'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'
import { MAX_FORMULA_LENGTH, renderFormula } from '@/utils/questionMath'
import { registerFormulaBlot } from '@/utils/formulaBlot'

registerFormulaBlot()

const toast = useToast()
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis di sini...' },
  minHeight: { type: String, default: '120px' }
})

const emit = defineEmits(['update:modelValue'])

const editorRef = ref(null)
const formulaOpen = ref(false)
const formulaTex = ref('')
const formulaDisplay = ref(false)
const formulaError = ref('')
const formulaIndex = ref(null)
const formulaInput = ref(null)
const formulaPreview = ref(null)
const savedRange = ref(null)
const examples = [
  { label: 'H₂O', tex: 'H_2O' },
  { label: 'x²', tex: 'x^2' },
  { label: 'pecahan', tex: '\\frac{a}{b}' },
  { label: 'akar', tex: '\\sqrt{x}' },
  { label: 'α + β', tex: '\\alpha+\\beta' }
]

const editorOptions = {
  placeholder: props.placeholder
}

function onEditorReady(quill) {
  const toolbar = quill.getModule('toolbar')
  if (toolbar) {
    toolbar.addHandler('image', imageHandler.bind(null, quill))
    addFormulaButton(quill, toolbar)
  }
  quill.root.addEventListener('dblclick', (event) => onFormulaDblClick(quill, event))
  upgradeStoredFormulas(quill)
}

function addFormulaButton(quill, toolbar) {
  const group = document.createElement('span')
  group.className = 'ql-formats'
  const button = document.createElement('button')
  button.type = 'button'
  button.className = 'ql-formula'
  button.title = 'Sisipkan rumus. Klik dua kali rumus untuk mengubah.'
  button.setAttribute('aria-label', 'Sisipkan rumus')
  const icons = Quill.import('ui/icons')
  button.innerHTML = icons.formula || '√x'
  button.addEventListener('click', (event) => {
    event.preventDefault()
    openFormula(quill, null)
  })
  group.appendChild(button)
  toolbar.container.appendChild(group)
}

function onFormulaDblClick(quill, event) {
  const node = event.target?.closest?.('.ql-formula')
  if (!node || !quill.root.contains(node)) return
  const blot = Quill.find(node)
  if (!blot) return
  event.preventDefault()
  openFormula(quill, quill.getIndex(blot), node)
}

function openFormula(quill, index, node = null) {
  const range = quill.getSelection()
  savedRange.value = range ? { index: range.index, length: range.length } : null
  formulaIndex.value = index
  formulaTex.value = node ? (node.getAttribute('data-math') || '') : ''
  formulaDisplay.value = node ? node.getAttribute('data-display') === 'block' : false
  formulaError.value = ''
  formulaOpen.value = true
  nextTick(() => formulaInput.value?.focus())
}

function closeFormula() {
  formulaOpen.value = false
  formulaError.value = ''
}

function applyExample(ex) {
  formulaTex.value = ex.tex
  formulaError.value = ''
}

function insertFormula() {
  const tex = formulaTex.value.trim()
  if (!tex) return
  if (tex.length > MAX_FORMULA_LENGTH) {
    formulaError.value = `Rumus maksimal ${MAX_FORMULA_LENGTH} karakter.`
    return
  }
  const quill = editorRef.value?.getQuill?.()
  if (!quill) return
  const value = formulaDisplay.value ? { tex, display: true } : tex
  if (formulaIndex.value != null) {
    quill.deleteText(formulaIndex.value, 1, 'user')
    quill.insertEmbed(formulaIndex.value, 'formula', value, 'user')
    quill.setSelection(formulaIndex.value + 1, 0, 'silent')
  } else {
    const index = savedRange.value ? savedRange.value.index : Math.max(quill.getLength() - 1, 0)
    if (savedRange.value?.length) {
      quill.deleteText(index, savedRange.value.length, 'user')
    }
    quill.insertEmbed(index, 'formula', value, 'user')
    quill.insertText(index + 1, ' ', 'user')
    quill.setSelection(index + 2, 0, 'silent')
  }
  closeFormula()
}

function upgradeStoredFormulas(quill) {
  const html = props.modelValue || ''
  if (!html.includes('data-math')) return
  try {
    const delta = quill.clipboard.convert({ html })
    quill.setContents(delta, 'silent')
  } catch (e) {
    console.error('Gagal memuat rumus di editor', e)
  }
}

function imageHandler(quill) {
  const input = document.createElement('input')
  input.setAttribute('type', 'file')
  input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp')
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
      const msg = e.response?.data?.message || e.formattedMessage || 'Gambar tidak dapat diunggah. Pastikan format JPG/PNG/GIF dan ukuran sesuai batas.'
      toast.error('Gagal mengunggah gambar', msg)
    }
  }
}

watch([formulaTex, formulaDisplay, formulaOpen], async () => {
  if (!formulaOpen.value) return
  await nextTick()
  if (!formulaPreview.value) return
  const tex = formulaTex.value.trim()
  if (!tex) {
    formulaPreview.value.textContent = 'Pratinjau rumus muncul di sini'
    return
  }
  renderFormula(formulaPreview.value, tex, formulaDisplay.value)
})
</script>

<style scoped>
.rich-text-editor { position: relative; }
.rich-text-editor :deep(.ql-container) {
  min-height: v-bind(minHeight);
  font-size: 14px;
}
.rich-text-editor :deep(.ql-editor) {
  min-height: v-bind(minHeight);
  font-family: "Segoe UI", Tahoma, "Traditional Arabic", "Noto Naskh Arabic", sans-serif;
}
.rich-text-editor :deep(.ql-editor .ql-direction-rtl) {
  direction: rtl;
  text-align: right;
}
.rich-text-editor :deep(.ql-editor .ql-direction-rtl.ql-align-center) { text-align: center; }
.rich-text-editor :deep(.ql-editor .ql-direction-rtl.ql-align-justify) { text-align: justify; }
.rich-text-editor :deep(.ql-formula) { white-space: nowrap; }
.rich-text-editor :deep(.ql-formula[data-display="block"]) {
  display: block;
  margin: 0.5rem 0;
  text-align: center;
  overflow-x: auto;
  white-space: normal;
}
.formula-dialog {
  margin-bottom: 0.5rem;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #f8fafc;
}
.formula-label { display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem; }
.formula-input {
  display: block;
  width: 100%;
  box-sizing: border-box;
  font-family: ui-monospace, monospace;
  font-size: 0.9rem;
  padding: 0.45rem 0.55rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  resize: vertical;
  margin-top: 0.35rem;
}
.formula-examples { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.45rem; }
.formula-chip {
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 999px;
  padding: 0.15rem 0.55rem;
  font-size: 0.75rem;
  cursor: pointer;
  color: #334155;
}
.formula-chip:hover { border-color: #059669; color: #047857; }
.formula-display { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.5rem; font-size: 0.8rem; color: #334155; }
.formula-preview {
  margin-top: 0.5rem;
  min-height: 2rem;
  padding: 0.45rem 0.55rem;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 6px;
  overflow-x: auto;
}
.formula-preview.block { text-align: center; }
.formula-error { margin: 0.4rem 0 0; color: #b91c1c; font-size: 0.8rem; }
.formula-hint { margin: 0.4rem 0 0; color: #64748b; font-size: 0.75rem; }
.formula-actions { display: flex; gap: 0.4rem; margin-top: 0.55rem; }
.formula-insert, .formula-cancel {
  border: none;
  border-radius: 6px;
  padding: 0.35rem 0.7rem;
  cursor: pointer;
  font-size: 0.85rem;
}
.formula-insert { background: #059669; color: #fff; }
.formula-insert:disabled { opacity: 0.5; cursor: not-allowed; }
.formula-cancel { background: #e5e7eb; color: #374151; }
</style>
