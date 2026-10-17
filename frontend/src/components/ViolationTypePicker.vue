<template>
  <div class="type-picker">
    <div class="picker-heading">
      <span class="field-label">Jenis Pelanggaran<template v-if="required"> *</template></span>
      <span v-if="selectedType" class="picker-point-hint">+{{ pointValue(selectedType) }} poin</span>
    </div>

    <div v-if="selectedType" class="picker-card" :class="{ 'is-selected': !panelOpen }">
      <span class="point-pill" :class="selectedType.category">
        <strong>+{{ pointValue(selectedType) }}</strong>
        <span>poin</span>
      </span>
      <span class="type-copy">
        <strong :title="selectedType.name">{{ selectedType.name }}</strong>
        <span>{{ categoryLabel(selectedType.category) }}</span>
      </span>
      <button v-if="!panelOpen" type="button" class="picker-change" @click="openPanel">Ganti</button>
    </div>

    <div v-show="!selectedType || panelOpen" class="picker-panel">
      <p class="picker-hint">Angka di kiri adalah poin yang menambah skor pelanggaran.</p>

      <label class="picker-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
          <path d="M20 20L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Cari nama atau kode"
          autocomplete="off"
          aria-label="Cari jenis pelanggaran"
        />
      </label>

      <div v-if="categoryChips.length > 1" class="type-chips" role="group" aria-label="Filter kategori">
        <button
          type="button"
          class="type-chip"
          :class="{ active: !category }"
          :aria-pressed="!category"
          @click="category = ''"
        >Semua</button>
        <button
          v-for="chip in categoryChips"
          :key="chip.value"
          type="button"
          class="type-chip"
          :class="{ active: category === chip.value }"
          :aria-pressed="category === chip.value"
          @click="category = chip.value"
        >{{ chip.label }}</button>
      </div>

      <div ref="listRef" class="picker-list" role="listbox" aria-label="Jenis pelanggaran" :aria-busy="loading && !types.length">
        <div v-if="loading && !types.length" class="picker-state">
          <span class="picker-spinner"></span>
          Memuat jenis pelanggaran...
        </div>
        <div v-else-if="!selectable.length" class="picker-state">{{ emptyText }}</div>
        <div v-else-if="!filtered.length" class="picker-state">Tidak ada jenis yang cocok.</div>
        <template v-else>
          <div class="picker-list-meta">{{ filtered.length }} jenis — pilih satu</div>
          <button
            v-for="type in filtered"
            :key="type.id"
            type="button"
            role="option"
            class="type-option"
            :class="{ active: isSelected(type) }"
            :aria-selected="isSelected(type)"
            @click="selectType(type)"
          >
            <span class="point-pill" :class="type.category">
              <strong>+{{ pointValue(type) }}</strong>
              <span>poin</span>
            </span>
            <span class="type-copy">
              <strong :title="type.name">{{ type.name }}</strong>
              <span>
                {{ categoryLabel(type.category) }}
                <template v-if="shortCode(type)"> · {{ shortCode(type) }}</template>
              </span>
            </span>
            <svg v-if="isSelected(type)" class="picker-check" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" fill="#dc2626"/>
              <path d="M8 12.5l2.5 2.5L16 9.5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'

const CATEGORIES = [
  { value: 'ringan', label: 'Ringan' },
  { value: 'sedang', label: 'Sedang' },
  { value: 'berat', label: 'Berat' },
  { value: 'sangat_berat', label: 'Sangat Berat' },
]

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  types: { type: Array, default: () => [] },
  fallback: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  required: { type: Boolean, default: true },
  emptyText: { type: String, default: 'Belum ada jenis pelanggaran.' },
})

const emit = defineEmits(['update:modelValue'])

const search = ref('')
const category = ref('')
const panelOpen = ref(props.modelValue === '' || props.modelValue == null)
const listRef = ref(null)

const selectable = computed(() => props.types.filter((type) => {
  if (type?.is_active === false && !isSelected(type)) return false
  return true
}))

const categoryChips = computed(() => {
  const present = new Set(selectable.value.map((type) => type.category).filter(Boolean))
  const known = CATEGORIES.filter((item) => present.has(item.value))
  const extra = [...present]
    .filter((value) => !CATEGORIES.some((item) => item.value === value))
    .map((value) => ({ value, label: value }))
  return [...known, ...extra]
})

const filtered = computed(() => {
  const query = search.value.trim().toLowerCase()
  const order = Object.fromEntries(CATEGORIES.map((item, index) => [item.value, index]))
  return selectable.value
    .filter((type) => {
      if (category.value && type.category !== category.value) return false
      if (!query) return true
      const name = String(type.name || '').toLowerCase()
      const code = String(type.code || '').toLowerCase()
      return name.includes(query) || code.includes(query)
    })
    .sort((a, b) => {
      const byCategory = (order[a.category] ?? 99) - (order[b.category] ?? 99)
      if (byCategory !== 0) return byCategory
      const byPoints = pointValue(b) - pointValue(a)
      if (byPoints !== 0) return byPoints
      return String(a.name || '').localeCompare(String(b.name || ''), 'id')
    })
})

const selectedType = computed(() => {
  if (props.modelValue === '' || props.modelValue == null) return null
  const found = props.types.find((type) => String(type.id) === String(props.modelValue))
  if (found) return found
  if (props.fallback && String(props.fallback.id) === String(props.modelValue)) return props.fallback
  return null
})

function pointValue(type) {
  return Number(type?.point_weight ?? 0)
}

function categoryLabel(value) {
  return CATEGORIES.find((item) => item.value === value)?.label || value || '—'
}

function shortCode(type) {
  const code = String(type?.code || '').trim()
  if (!code || code.length > 12) return ''
  return code
}

function isSelected(type) {
  return String(props.modelValue) === String(type?.id)
}

function selectType(type) {
  emit('update:modelValue', type.id)
  panelOpen.value = false
  search.value = ''
  category.value = ''
}

function openPanel() {
  panelOpen.value = true
  nextTick(() => {
    listRef.value?.querySelector('.type-option.active')?.scrollIntoView({ block: 'nearest' })
  })
}
</script>

<style scoped>
.type-picker {
  padding: 0.9rem 1rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.picker-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}
.field-label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
}
.picker-point-hint {
  flex-shrink: 0;
  font-size: 0.75rem;
  font-weight: 700;
  color: #b91c1c;
  background: #fee2e2;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
}
.picker-card {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.7rem 0.85rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.picker-card.is-selected {
  border-color: #dc2626;
  background: #fef2f2;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}
.picker-card + .picker-panel {
  margin-top: 0.7rem;
}
.picker-hint {
  margin: 0 0 0.55rem;
  font-size: 0.75rem;
  line-height: 1.4;
  color: #64748b;
}
.picker-search {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #94a3b8;
}
.picker-search:focus-within {
  border-color: #dc2626;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}
.picker-search input {
  flex: 1;
  width: auto;
  min-width: 0;
  border: none;
  border-radius: 0;
  padding: 0.5rem 0;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
  background: transparent;
  box-shadow: none;
}
.picker-search input:focus {
  outline: none;
}
.type-chips {
  display: flex;
  gap: 0.35rem;
  margin-top: 0.55rem;
  overflow-x: auto;
}
.type-chip {
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 999px;
  padding: 0.28rem 0.7rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}
.type-chip.active {
  background: #0f172a;
  border-color: #0f172a;
  color: #fff;
}
.picker-list {
  margin-top: 0.65rem;
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}
.picker-list-meta {
  position: sticky;
  top: 0;
  z-index: 1;
  padding: 0.45rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.type-option {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.75rem;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  cursor: pointer;
}
.type-option:last-child {
  border-bottom: none;
}
.type-option:hover {
  background: #f8fafc;
}
.type-option.active {
  background: #fef2f2;
}
.point-pill {
  flex-shrink: 0;
  width: 3.35rem;
  padding: 0.35rem 0.2rem;
  border-radius: 8px;
  text-align: center;
  background: #fee2e2;
  color: #b91c1c;
  line-height: 1.05;
}
.point-pill strong {
  display: block;
  font-size: 0.95rem;
  font-weight: 800;
}
.point-pill span {
  display: block;
  margin-top: 0.12rem;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}
.point-pill.ringan { background: #fef3c7; color: #92400e; }
.point-pill.sedang { background: #ffedd5; color: #c2410c; }
.point-pill.berat { background: #fee2e2; color: #b91c1c; }
.point-pill.sangat_berat { background: #7f1d1d; color: #fff; }
.type-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
}
.type-option .type-copy strong,
.picker-card .type-copy strong {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}
.type-option .type-copy strong {
  -webkit-line-clamp: 2;
  line-clamp: 2;
}
.picker-card .type-copy strong {
  -webkit-line-clamp: 3;
  line-clamp: 3;
}
.type-copy strong {
  font-size: 0.88rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.3;
}
.type-option.active .type-copy strong {
  color: #991b1b;
}
.type-copy > span {
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.3;
}
.picker-change {
  margin-left: auto;
  flex-shrink: 0;
  padding: 0.3rem 0.7rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.picker-change:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.picker-check {
  margin-left: auto;
  flex-shrink: 0;
}
.picker-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 96px;
  padding: 1rem;
  text-align: center;
  font-size: 0.85rem;
  color: #64748b;
}
.picker-spinner {
  width: 22px;
  height: 22px;
  border: 2px solid #e2e8f0;
  border-top-color: #dc2626;
  border-radius: 50%;
  animation: picker-spin 0.7s linear infinite;
}
@keyframes picker-spin {
  to { transform: rotate(360deg); }
}
</style>
