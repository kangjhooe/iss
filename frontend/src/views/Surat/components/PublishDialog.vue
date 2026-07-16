<script setup>
import { computed, ref, watch } from 'vue'
import {
  DEFAULT_LETTER_TYPE_CODE,
  LETTER_TYPES,
  exampleLetterNumber
} from '../utils/letterTypes'

const props = defineProps({
  open: { type: Boolean, default: false },
  judul: { type: String, default: '' },
  letterTypeCode: { type: String, default: DEFAULT_LETTER_TYPE_CODE },
  tanggal: { type: String, default: '' },
  toDefault: { type: String, default: '' }
})

const emit = defineEmits(['close', 'confirm'])

const submitting = ref(false)
const error = ref('')
const form = ref({
  letter_type_code: DEFAULT_LETTER_TYPE_CODE,
  tanggal: '',
  to: '',
  priority: 'biasa'
})

const canSubmit = computed(() => !!form.value.letter_type_code && !!form.value.tanggal && !!form.value.to?.trim())
const nomorContoh = computed(() =>
  exampleLetterNumber(form.value.letter_type_code || DEFAULT_LETTER_TYPE_CODE, {
    date: form.value.tanggal || new Date()
  })
)

watch(() => props.open, (val) => {
  if (!val) return
  error.value = ''
  submitting.value = false
  form.value = {
    letter_type_code: String(props.letterTypeCode || DEFAULT_LETTER_TYPE_CODE).padStart(2, '0'),
    tanggal: props.tanggal || new Date().toISOString().slice(0, 10),
    to: props.toDefault || 'Yang berkepentingan',
    priority: 'biasa'
  }
  // Lepas fokusus editor agar panel CKEditor tidak menelan klik di modal
  if (typeof document !== 'undefined' && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
})

function setLetterType(event) {
  form.value.letter_type_code = String(event.target.value || DEFAULT_LETTER_TYPE_CODE)
}

function confirm() {
  if (!canSubmit.value) {
    error.value = 'Lengkapi jenis surat, tanggal, dan penerima'
    return
  }
  submitting.value = true
  emit('confirm', { ...form.value })
}

function setSubmitting(val) {
  submitting.value = val
}

defineExpose({ setSubmitting })
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-card" role="dialog" aria-modal="true">
        <header class="modal-header">
          <h3>Terbitkan Surat</h3>
          <button type="button" class="btn-close" @click="emit('close')">×</button>
        </header>

        <div class="modal-body">
          <div class="info-box">
            <strong>Apa yang terjadi?</strong>
            <ul>
              <li>Surat masuk ke <em>Arsip Persuratan</em> sebagai surat keluar</li>
              <li>Nomor resmi digenerate otomatis (format Arsip yang sudah ada)</li>
              <li>Placeholder <code v-pre>{{nomor_surat}}</code> diisi nomor tersebut</li>
            </ul>
          </div>

          <p class="judul">{{ judul || 'Tanpa judul' }}</p>
          <p v-if="error" class="error">{{ error }}</p>

          <div class="field">
            <label for="publish-letter-type">Jenis surat</label>
            <select
              id="publish-letter-type"
              :value="form.letter_type_code"
              @change="setLetterType"
            >
              <option v-for="t in LETTER_TYPES" :key="t.code" :value="t.code">
                {{ t.code }} - {{ t.name }} ({{ t.abbr }})
              </option>
            </select>
            <span class="field-hint">Contoh nomor: <code>{{ nomorContoh }}</code></span>
          </div>

          <div class="field">
            <label for="publish-tanggal">Tanggal surat</label>
            <input id="publish-tanggal" v-model="form.tanggal" type="date" />
          </div>

          <div class="field">
            <label for="publish-to">Penerima (Kepada)</label>
            <input id="publish-to" v-model="form.to" type="text" placeholder="Nama penerima / instansi" />
          </div>

          <div class="field">
            <label for="publish-priority">Prioritas</label>
            <select id="publish-priority" v-model="form.priority">
              <option value="biasa">Biasa</option>
              <option value="penting">Penting</option>
              <option value="sangat_penting">Sangat penting</option>
            </select>
          </div>
        </div>

        <footer class="modal-footer">
          <button type="button" class="btn" :disabled="submitting" @click="emit('close')">Batal</button>
          <button type="button" class="btn btn-primary" :disabled="submitting || !canSubmit" @click="confirm">
            {{ submitting ? 'Menerbitkan...' : 'Terbitkan & dapatkan nomor' }}
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(32, 33, 36, 0.55);
  /* Di atas sidebar (1000) dan panel CKEditor (~9999) */
  z-index: 11000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card {
  position: relative;
  background: #fff;
  width: min(480px, 100%);
  border-radius: 12px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.25);
  overflow: visible;
  z-index: 1;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 { margin: 0; font-size: 16px; }

.btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  color: #5f6368;
}

.modal-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-box {
  background: #e8f0fe;
  color: #174ea6;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 12px;
  line-height: 1.45;
}

.info-box ul {
  margin: 6px 0 0;
  padding-left: 18px;
}

.info-box code {
  font-size: 11px;
  background: rgba(255, 255, 255, 0.7);
  padding: 1px 4px;
  border-radius: 4px;
}

.judul {
  margin: 0;
  font-weight: 600;
  font-size: 14px;
  color: #202124;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  color: #3c4043;
  position: relative;
  z-index: 2;
}

.field label {
  font-size: 13px;
  color: #3c4043;
}

.field input,
.field select {
  border: 1px solid #dadce0;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 14px;
  background: #fff;
  width: 100%;
  position: relative;
  z-index: 2;
  pointer-events: auto;
}

.field-hint {
  font-size: 11px;
  color: #5f6368;
}

.field-hint code {
  font-family: ui-monospace, monospace;
  color: #174ea6;
  background: #e8f0fe;
  padding: 2px 5px;
  border-radius: 4px;
  word-break: break-all;
}

.error { color: #d93025; margin: 0; font-size: 13px; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid #eee;
}

.btn {
  border: 1px solid #dadce0;
  background: #fff;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
}

.btn-primary {
  background: #188038;
  border-color: #188038;
  color: #fff;
}

.btn-primary:disabled,
.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
