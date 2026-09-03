<template>
  <input
    ref="inputRef"
    type="text"
    inputmode="decimal"
    :value="displayValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :required="required"
    v-bind="$attrs"
    @input="onInput"
    @blur="onBlur"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { formatMoneyInput, parseMoneyInput } from '@/utils/moneyInput'

defineOptions({ inheritAttrs: false })

const model = defineModel({ type: [Number, null], default: null })

const props = defineProps({
  decimals: { type: Number, default: 0 },
  min: { type: Number, default: null },
  max: { type: Number, default: null },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})

const inputRef = ref(null)
const displayValue = computed(() => formatMoneyInput(model.value, { decimals: props.decimals }))

function clamp(value) {
  if (value == null) return null
  let n = value
  if (props.min != null && n < props.min) n = props.min
  if (props.max != null && n > props.max) n = props.max
  return n
}

function onInput(e) {
  const parsed = parseMoneyInput(e.target.value, { decimals: props.decimals })
  model.value = parsed
  const formatted = formatMoneyInput(parsed, { decimals: props.decimals })
  e.target.value = formatted
  const len = formatted.length
  e.target.setSelectionRange(len, len)
}

function onBlur(e) {
  const clamped = clamp(model.value)
  if (clamped !== model.value) model.value = clamped
  e.target.value = formatMoneyInput(model.value, { decimals: props.decimals })
}

watch(
  () => model.value,
  () => {
    const el = inputRef.value
    if (!el || document.activeElement === el) return
    el.value = formatMoneyInput(model.value, { decimals: props.decimals })
  },
)
</script>
