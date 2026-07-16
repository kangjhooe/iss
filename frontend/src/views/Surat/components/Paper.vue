<script setup>
defineProps({
  zoom: { type: Number, default: 1 }
})
</script>

<template>
  <div class="paper-stage">
    <div class="paper-a4" :style="{ transform: `scale(${zoom})` }">
      <div class="paper-margin">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.paper-stage {
  display: flex;
  justify-content: center;
  padding: 32px 16px 64px;
  min-height: calc(100vh - 120px);
}

.paper-a4 {
  width: 210mm;
  min-height: 297mm;
  background: #fff;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.08),
    0 8px 24px rgba(0, 0, 0, 0.12);
  transform-origin: top center;
  transition: transform 0.15s ease;
}

.paper-margin {
  padding: 20mm 20mm 20mm 25mm;
  min-height: 297mm;
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .paper-stage {
    padding: 8px 0 96px;
    min-height: auto;
    justify-content: flex-start;
    overflow-x: hidden;
  }

  .paper-a4 {
    margin: 0 auto;
  }

  .paper-margin {
    padding: 12mm 14mm;
  }
}

@media print {
  .paper-stage {
    padding: 0;
    min-height: auto;
    background: #fff;
  }

  .paper-a4 {
    width: 100%;
    min-height: auto;
    box-shadow: none;
    transform: none !important;
  }

  .paper-margin {
    padding: 0;
    min-height: auto;
  }
}
</style>
