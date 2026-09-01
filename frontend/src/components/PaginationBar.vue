<template>
  <nav v-if="total > 0" class="pg-bar" :class="{ embedded }" aria-label="Navigasi halaman">
    <span class="pg-info">{{ rangeText }} dari {{ total }}{{ itemLabel ? ` ${itemLabel}` : '' }}</span>
    <div class="pg-controls">
      <label v-if="showPerPage" class="pg-per">
        <span>Per halaman</span>
        <select :value="perPage" @change="onPerPageChange">
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
      </label>
      <div class="pg-buttons">
        <button type="button" class="pg-btn" :disabled="safePage <= 1" @click="go(safePage - 1)">Sebelumnya</button>
        <button
          v-for="p in visiblePages"
          :key="p"
          type="button"
          class="pg-btn pg-num"
          :class="{ active: p === safePage }"
          :aria-current="p === safePage ? 'page' : undefined"
          @click="go(p)"
        >{{ p }}</button>
        <button type="button" class="pg-btn" :disabled="safePage >= safeLast" @click="go(safePage + 1)">Selanjutnya</button>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 15 },
  total: { type: Number, default: 0 },
  itemLabel: { type: String, default: '' },
  showPerPage: { type: Boolean, default: true },
  perPageOptions: { type: Array, default: () => [10, 15, 25, 50] },
  embedded: { type: Boolean, default: false },
})

const emit = defineEmits(['page-change', 'per-page-change'])

const safePage = computed(() => Math.max(1, Number(props.page) || 1))
const safeLast = computed(() => Math.max(1, Number(props.lastPage) || 1))
const safePerPage = computed(() => Math.max(1, Number(props.perPage) || 15))

const rangeText = computed(() => {
  if (!props.total) return '0'
  const from = (safePage.value - 1) * safePerPage.value + 1
  const to = Math.min(safePage.value * safePerPage.value, props.total)
  return `${from}–${to}`
})

const visiblePages = computed(() => {
  const last = safeLast.value
  const current = safePage.value
  const start = Math.max(1, current - 2)
  const end = Math.min(last, start + 4)
  const from = Math.max(1, end - 4)
  const pages = []
  for (let p = from; p <= end; p++) pages.push(p)
  return pages.length ? pages : [1]
})

function go(page) {
  const next = Math.min(safeLast.value, Math.max(1, page))
  if (next === safePage.value) return
  emit('page-change', next)
}

function onPerPageChange(event) {
  const n = Number(event.target.value)
  if (!n || n === safePerPage.value) return
  emit('per-page-change', n)
}
</script>

<style scoped>
.pg-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.pg-bar.embedded {
  border: 0;
  border-top: 1px solid #e2e8f0;
  border-radius: 0;
}
.pg-info {
  font-size: 0.85rem;
  color: #64748b;
}
.pg-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}
.pg-per {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 600;
}
.pg-per select {
  width: auto;
  min-width: 64px;
  padding: 0.3rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  font: inherit;
}
.pg-buttons {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.pg-btn {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
  color: #334155;
}
.pg-num {
  min-width: 36px;
  padding: 0.4rem 0.5rem;
}
.pg-btn.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
  font-weight: 700;
}
.pg-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pg-btn:hover:not(:disabled):not(.active) {
  background: #f1f5f9;
}
</style>
