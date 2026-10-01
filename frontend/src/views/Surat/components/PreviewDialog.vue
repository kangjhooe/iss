<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'Preview Surat' },
  html: { type: String, default: '' },
  density: { type: String, default: 'compact' }
})

const emit = defineEmits(['close', 'print'])
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="preview-overlay" @click.self="emit('close')">
      <div class="preview-dialog" role="dialog" aria-modal="true">
        <header class="preview-header">
          <h3>{{ title }}</h3>
          <div class="preview-actions">
            <button type="button" class="btn" @click="emit('print')">Cetak</button>
            <button type="button" class="btn-close" aria-label="Tutup" @click="emit('close')">×</button>
          </div>
        </header>
        <div class="preview-body">
          <div class="surat-page-shell preview-paper-wrap">
            <div
              class="surat-page-content preview-paper"
              :class="`surat-density-${density === 'relaxed' ? 'relaxed' : 'compact'}`"
              v-html="html"
            />
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.preview-overlay {
  position: fixed;
  inset: 0;
  background: rgba(32, 33, 36, 0.55);
  z-index: 11000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.preview-dialog {
  background: #ececec;
  width: min(920px, 100%);
  max-height: 92vh;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.25);
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #e5e5e5;
}

.preview-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.preview-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn {
  border: 1px solid #dadce0;
  background: #fff;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
}

.btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  color: #5f6368;
  padding: 0 4px;
}

.preview-body {
  overflow: auto;
  padding: 24px;
  display: flex;
  justify-content: center;
}

.preview-paper-wrap {
  margin: 0 auto;
  flex-shrink: 0;
}

.preview-paper {
  box-shadow: none;
}

.preview-paper :deep(table:not(.standard-kop-inner):not(.kop-table) td),
.preview-paper :deep(table:not(.standard-kop-inner):not(.kop-table) th) {
  border: 1px solid #333;
  padding: 4px 8px;
}

.preview-paper :deep(.standard-kop-inner td),
.preview-paper :deep(.kop-table td),
.preview-paper :deep(table[style*="border:none"] td) {
  border: none !important;
}

.preview-paper :deep(img) {
  max-width: 100%;
}

@media (max-width: 900px) {
  .preview-overlay {
    padding: 8px;
    align-items: stretch;
  }

  .preview-dialog {
    width: 100%;
    max-height: 100vh;
    border-radius: 10px;
  }

  .preview-header {
    padding: 10px 12px;
    gap: 8px;
  }

  .preview-header h3 {
    font-size: 14px;
  }

  .preview-body {
    padding: 12px;
    justify-content: flex-start;
  }
}
</style>

<style>
@import '@/styles/surat-page.css';
</style>
