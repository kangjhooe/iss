<template>
  <router-link
    v-if="to"
    :to="to"
    class="tbl-act"
    :class="`tbl-act--${kind}`"
    :title="label"
    :aria-label="label"
    @click="$emit('click', $event)"
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path :d="path" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </router-link>
  <button
    v-else
    type="button"
    class="tbl-act"
    :class="`tbl-act--${kind}`"
    :title="label"
    :aria-label="label"
    :disabled="disabled"
    @click="$emit('click', $event)"
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path :d="path" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  </button>
</template>

<script setup>
import { computed } from 'vue'

defineEmits(['click'])

const props = defineProps({
  kind: { type: String, required: true },
  title: { type: String, default: '' },
  to: { type: [String, Object], default: null },
  disabled: { type: Boolean, default: false },
})

const LABELS = {
  edit: 'Edit',
  delete: 'Hapus',
  view: 'Lihat',
  manage: 'Kelola',
  add: 'Tambah',
  print: 'Cetak',
  download: 'Unduh',
  approve: 'Setujui',
  reject: 'Tolak',
  cancel: 'Batal',
  restore: 'Pulihkan',
  preview: 'Preview',
  duplicate: 'Duplikat',
  transaction: 'Transaksi',
  return: 'Kembalikan',
  complete: 'Selesai',
  copy: 'Salin',
}

const PATHS = {
  edit: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z',
  delete: 'M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14M10 11v6M14 11v6',
  view: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
  manage: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z',
  add: 'M12 5v14M5 12h14',
  print: 'M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6v-8z',
  download: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
  approve: 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
  reject: 'M18 6 6 18M6 6l12 12',
  cancel: 'M18 6 6 18M6 6l12 12',
  restore: 'M3 12a9 9 0 1 0 3-6.7M3 4v5h5',
  preview: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
  duplicate: 'M16 8H8a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zM6 16H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2',
  transaction: 'M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7',
  return: 'M9 14 4 9l5-5M4 9h10.5a6.5 6.5 0 0 1 0 13H11',
  complete: 'M20 6 9 17l-5-5',
  copy: 'M16 8H8a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2zM6 16H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2',
}

const label = computed(() => props.title || LABELS[props.kind] || props.kind)
const path = computed(() => PATHS[props.kind] || PATHS.edit)
</script>

<style scoped>
.tbl-act {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  text-decoration: none;
  flex-shrink: 0;
}
.tbl-act:hover:not(:disabled) { background: #f1f5f9; }
.tbl-act:disabled { opacity: 0.4; cursor: not-allowed; }
.tbl-act--edit { color: #059669; }
.tbl-act--edit:hover:not(:disabled) { background: #ecfdf5; }
.tbl-act--delete,
.tbl-act--reject,
.tbl-act--cancel { color: #dc2626; }
.tbl-act--delete:hover:not(:disabled),
.tbl-act--reject:hover:not(:disabled),
.tbl-act--cancel:hover:not(:disabled) { background: #fef2f2; }
.tbl-act--view,
.tbl-act--preview,
.tbl-act--manage { color: #2563eb; }
.tbl-act--view:hover:not(:disabled),
.tbl-act--preview:hover:not(:disabled),
.tbl-act--manage:hover:not(:disabled) { background: #eff6ff; }
.tbl-act--approve,
.tbl-act--complete,
.tbl-act--restore { color: #059669; }
.tbl-act--approve:hover:not(:disabled),
.tbl-act--complete:hover:not(:disabled),
.tbl-act--restore:hover:not(:disabled) { background: #ecfdf5; }
.tbl-act--print,
.tbl-act--download,
.tbl-act--transaction,
.tbl-act--return,
.tbl-act--add,
.tbl-act--duplicate,
.tbl-act--copy { color: #0f766e; }
.tbl-act--print:hover:not(:disabled),
.tbl-act--download:hover:not(:disabled),
.tbl-act--transaction:hover:not(:disabled),
.tbl-act--return:hover:not(:disabled),
.tbl-act--add:hover:not(:disabled),
.tbl-act--duplicate:hover:not(:disabled),
.tbl-act--copy:hover:not(:disabled) { background: #f0fdfa; }
</style>
