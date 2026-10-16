<script setup>
defineProps({
  title: { type: String, default: 'Surat baru' },
  saving: { type: Boolean, default: false },
  publishing: { type: Boolean, default: false },
  dirty: { type: Boolean, default: false },
  hasDocument: { type: Boolean, default: false },
  optionsOpen: { type: Boolean, default: false },
  nomor: { type: String, default: '' },
  status: { type: String, default: 'draft' },
  correspondenceId: { type: [Number, String], default: null }
})

const emit = defineEmits([
  'update:title',
  'save',
  'preview',
  'print',
  'export-pdf',
  'publish',
  'toggle-options',
  'toggle-sidebar'
])
</script>

<template>
  <header class="surat-toolbar no-print">
    <div class="toolbar-left">
      <button type="button" class="icon-btn" title="Tampilkan/sembunyikan daftar" @click="emit('toggle-sidebar')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <div class="title-block">
        <input
          class="title-input"
          type="text"
          :value="title"
          placeholder="Judul surat"
          @input="emit('update:title', $event.target.value)"
        />
        <div class="status-row">
          <span v-if="status === 'terbit'" class="status-chip published">Terbit</span>
          <span v-else class="status-chip draft">Draft</span>
          <span v-if="dirty" class="status-chip dirty">Belum disimpan</span>
          <span v-else-if="hasDocument && !dirty" class="status-chip hide-sm">Tersimpan</span>
          <span v-if="nomor" class="nomor-chip" :title="nomor">{{ nomor }}</span>
          <span v-else-if="status === 'draft'" class="nomor-chip muted hide-sm">Belum ada nomor</span>
        </div>
      </div>
    </div>

    <div class="toolbar-actions">
      <button
        type="button"
        class="btn"
        :class="{ active: optionsOpen }"
        title="Opsi KOP, TTD, Stempel"
        @click="emit('toggle-options')"
      >
        Opsi
      </button>
      <div class="btn-group">
        <button type="button" class="btn" :disabled="!hasDocument" @click="emit('preview')">Preview</button>
        <button type="button" class="btn hide-xs" :disabled="!hasDocument" @click="emit('print')">Cetak</button>
        <button type="button" class="btn" :disabled="!hasDocument" @click="emit('export-pdf')">PDF</button>
      </div>
      <button
        type="button"
        class="btn btn-save"
        :disabled="saving || (!hasDocument && !title)"
        @click="emit('save')"
      >
        {{ saving ? '...' : 'Simpan' }}
      </button>
      <button
        v-if="status !== 'terbit'"
        type="button"
        class="btn btn-publish"
        :disabled="publishing || !hasDocument || dirty"
        :title="dirty ? 'Simpan dulu sebelum menerbitkan' : 'Terbitkan untuk mendapatkan nomor resmi'"
        @click="emit('publish')"
      >
        <span class="label-full">{{ publishing ? 'Menerbitkan...' : 'Terbitkan' }}</span>
        <span class="label-short">{{ publishing ? '...' : 'Terbit' }}</span>
      </button>
      <router-link
        v-else-if="correspondenceId"
        class="btn btn-link-arsip"
        :to="{ path: '/correspondence/workflow', query: { highlight: correspondenceId } }"
      >
        <span class="label-full">Lihat di Arsip</span>
        <span class="label-short">Arsip</span>
      </router-link>
    </div>
  </header>
</template>

<style scoped>
.surat-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 52px;
  padding: 8px 12px;
  background: #fff;
  border-bottom: 1px solid #e5e5e5;
  flex-shrink: 0;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.title-block {
  min-width: 0;
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.status-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #dadce0;
  background: #fff;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #3c4043;
  flex-shrink: 0;
}

.icon-btn:hover { background: #f1f3f4; }

.title-input {
  border: none;
  outline: none;
  font-size: 15px;
  font-weight: 600;
  color: #202124;
  background: transparent;
  width: 100%;
  max-width: 280px;
  padding: 6px 8px;
  border-radius: 6px;
  min-width: 100px;
}

.title-input:hover,
.title-input:focus { background: #f1f3f4; }

.status-chip,
.nomor-chip {
  font-size: 11px;
  color: #5f6368;
  background: #f1f3f4;
  border-radius: 999px;
  padding: 3px 8px;
  white-space: nowrap;
  flex-shrink: 0;
}

.status-chip.draft {
  background: #fef7e0;
  color: #a05a00;
}

.status-chip.published {
  background: #e6f4ea;
  color: #137333;
}

.status-chip.dirty {
  background: #fce8e6;
  color: #c5221f;
}

.nomor-chip {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nomor-chip.muted {
  color: #80868b;
  font-style: italic;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.btn-group {
  display: inline-flex;
  border: 1px solid #dadce0;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.btn-group .btn {
  border: none;
  border-radius: 0;
  border-right: 1px solid #dadce0;
  box-shadow: none;
}

.btn-group .btn:last-child { border-right: none; }

.btn {
  border: 1px solid #dadce0;
  background: #fff;
  color: #3c4043;
  font-size: 13px;
  font-weight: 500;
  padding: 7px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 34px;
}

.btn:hover:not(:disabled) { background: #f8f9fa; }

.btn.active {
  background: #e8f0fe;
  border-color: #1a73e8;
  color: #1a73e8;
}

.btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.btn-publish {
  background: #188038;
  border-color: #188038;
  color: #fff;
}

.btn-publish:hover:not(:disabled) {
  background: #137333;
}

.btn-link-arsip {
  background: #e8f0fe;
  border-color: #1a73e8;
  color: #1a73e8;
}

.label-short { display: none; }

@media (max-width: 900px) {
  .surat-toolbar {
    flex-wrap: wrap;
    align-items: stretch;
    gap: 8px;
    padding: 8px 10px;
  }

  .toolbar-left {
    width: 100%;
    flex: 1 1 100%;
  }

  .title-block {
    flex-direction: column;
    align-items: stretch;
    gap: 4px;
  }

  .title-input {
    max-width: none;
    font-size: 14px;
    padding: 8px;
  }

  .toolbar-actions {
    width: 100%;
    flex: 1 1 100%;
    gap: 6px;
    justify-content: stretch;
  }

  .toolbar-actions > .btn,
  .toolbar-actions > .btn-group {
    flex: 1 1 auto;
  }

  .btn-group {
    flex: 1 1 auto;
  }

  .btn-group .btn {
    flex: 1;
    padding: 8px 6px;
    font-size: 12px;
    justify-content: center;
  }

  .btn {
    padding: 8px 10px;
    font-size: 12px;
    min-height: 38px;
  }

  .nomor-chip:not(.muted) {
    max-width: 140px;
  }
}

@media (max-width: 480px) {
  .hide-sm { display: none; }
  .hide-xs { display: none; }

  .label-full { display: none; }
  .label-short { display: inline; }

  .toolbar-actions {
    display: grid;
    grid-template-columns: 1fr 1.4fr 1fr 1fr;
    gap: 6px;
  }

  .toolbar-actions > .btn:first-child {
    grid-column: 1;
  }

  .toolbar-actions > .btn-group {
    grid-column: 2 / 3;
  }

  .btn-save {
    grid-column: 3;
  }

  .btn-publish,
  .btn-link-arsip {
    grid-column: 4;
  }

  .btn,
  .btn-group .btn {
    padding: 8px 4px;
    font-size: 11px;
  }
}
</style>
