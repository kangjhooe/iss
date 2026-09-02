<template>
  <Teleport to="body">
    <Transition name="menu-palette">
      <div
        v-if="open"
        class="menu-palette-overlay"
        role="presentation"
        @click="close"
      >
        <div
          class="menu-palette"
          role="dialog"
          aria-modal="true"
          aria-label="Cari menu"
          @click.stop
        >
          <div class="menu-palette-input-wrap">
            <svg class="menu-palette-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
              <path d="M20 20L16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            <input
              ref="inputRef"
              v-model="query"
              type="search"
              class="menu-palette-input"
              placeholder="Cari menu..."
              autocomplete="off"
              autocapitalize="off"
              spellcheck="false"
              @keydown="onInputKeydown"
            />
            <kbd class="menu-palette-kbd">Esc</kbd>
          </div>

          <ul v-if="filtered.length" class="menu-palette-list" role="listbox">
            <li
              v-for="(item, index) in filtered"
              :key="item.id"
              role="option"
              :aria-selected="index === activeIndex"
            >
              <button
                type="button"
                class="menu-palette-item"
                :class="{ 'menu-palette-item--active': index === activeIndex }"
                @click="selectItem(item)"
                @mouseenter="activeIndex = index"
              >
                <span class="menu-palette-item-text">
                  <span class="menu-palette-item-label">{{ item.label }}</span>
                  <span v-if="item.groupLabel" class="menu-palette-item-group">{{ item.groupLabel }}</span>
                </span>
                <span class="menu-palette-item-actions">
                  <span v-if="item.maturity === 'beta'" class="menu-palette-beta">Beta</span>
                  <button
                    type="button"
                    class="menu-palette-pin"
                    :class="{ 'menu-palette-pin--on': isPinned(item.pinId) }"
                    :title="isPinned(item.pinId) ? 'Lepas pin' : 'Pin ke favorit'"
                    :aria-label="isPinned(item.pinId) ? 'Lepas pin' : 'Pin ke favorit'"
                    @click.stop="onTogglePin(item)"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"
                        :fill="isPinned(item.pinId) ? 'currentColor' : 'none'"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </button>
                </span>
              </button>
            </li>
          </ul>
          <p v-else class="menu-palette-empty">Tidak ada menu yang cocok.</p>

          <div class="menu-palette-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> navigasi</span>
            <span><kbd>Enter</kbd> buka</span>
            <span><kbd>Ctrl</kbd><kbd>K</kbd> tutup/buka</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { filterMenuItems } from '@/utils/sidebarMenu'

const props = defineProps({
  open: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
  isPinned: { type: Function, required: true },
  togglePin: { type: Function, required: true },
})

const emit = defineEmits(['update:open', 'pin-limit'])

const router = useRouter()
const query = ref('')
const activeIndex = ref(0)
const inputRef = ref(null)

const filtered = computed(() => filterMenuItems(query.value, props.items))

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    query.value = ''
    activeIndex.value = 0
    await nextTick()
    inputRef.value?.focus()
  }
})

watch(filtered, () => {
  activeIndex.value = 0
})

function close() {
  emit('update:open', false)
}

function selectItem(item) {
  if (!item?.to) return
  close()
  router.push(item.to)
}

function onTogglePin(item) {
  const ok = props.togglePin(item.pinId)
  if (!ok) emit('pin-limit')
}

function onInputKeydown(e) {
  if (e.key === 'Escape') {
    e.preventDefault()
    close()
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    if (!filtered.value.length) return
    activeIndex.value = (activeIndex.value + 1) % filtered.value.length
    return
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault()
    if (!filtered.value.length) return
    activeIndex.value = (activeIndex.value - 1 + filtered.value.length) % filtered.value.length
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    const item = filtered.value[activeIndex.value]
    if (item) selectItem(item)
  }
}
</script>

<style scoped>
.menu-palette-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 12vh 16px 16px;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
}

.menu-palette {
  width: min(520px, 100%);
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.menu-palette-input-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
}

.menu-palette-search-icon {
  flex-shrink: 0;
  color: #94a3b8;
}

.menu-palette-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  color: #0f172a;
  background: transparent;
  min-width: 0;
}

.menu-palette-input::placeholder {
  color: #94a3b8;
}

.menu-palette-kbd {
  flex-shrink: 0;
  font-size: 10px;
  font-family: inherit;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 2px 6px;
}

.menu-palette-list {
  list-style: none;
  margin: 0;
  padding: 6px;
  max-height: min(50vh, 360px);
  overflow-y: auto;
}

.menu-palette-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border: none;
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  text-align: left;
  font-family: inherit;
  color: #0f172a;
  transition: background 0.12s ease;
}

.menu-palette-item:hover,
.menu-palette-item--active {
  background: #f1f5f9;
}

.menu-palette-item-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.menu-palette-item-label {
  font-size: 14px;
  font-weight: 600;
}

.menu-palette-item-group {
  font-size: 12px;
  color: #64748b;
}

.menu-palette-item-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.menu-palette-beta {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #b45309;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 4px;
  padding: 1px 5px;
}

.menu-palette-pin {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.12s, background 0.12s;
}

.menu-palette-pin:hover {
  background: #e2e8f0;
  color: #64748b;
}

.menu-palette-pin--on {
  color: #f59e0b;
}

.menu-palette-empty {
  margin: 0;
  padding: 20px 16px;
  text-align: center;
  font-size: 13px;
  color: #64748b;
}

.menu-palette-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  padding: 10px 16px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  font-size: 11px;
  color: #64748b;
}

.menu-palette-footer kbd {
  font-family: inherit;
  font-size: 10px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 1px 4px;
  margin-right: 2px;
}

.menu-palette-enter-active,
.menu-palette-leave-active {
  transition: opacity 0.15s ease;
}

.menu-palette-enter-active .menu-palette,
.menu-palette-leave-active .menu-palette {
  transition: transform 0.15s ease, opacity 0.15s ease;
}

.menu-palette-enter-from,
.menu-palette-leave-to {
  opacity: 0;
}

.menu-palette-enter-from .menu-palette,
.menu-palette-leave-to .menu-palette {
  transform: translateY(-8px) scale(0.98);
  opacity: 0;
}
</style>
