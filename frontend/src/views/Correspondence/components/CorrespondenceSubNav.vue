<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' }
})
</script>

<template>
  <header class="subnav-wrap">
    <div class="subnav-top">
      <div>
        <h1>{{ title }}</h1>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
      <div class="subnav-actions">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<style scoped>
.subnav-wrap {
  margin-bottom: 16px;
}

.subnav-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 12px;
}

.subnav-top h1 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 700;
  color: #202124;
}

.subnav-top p {
  margin: 0;
  font-size: 13px;
  color: #5f6368;
}

.subnav-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

@media (max-width: 700px) {
  .subnav-top {
    flex-direction: column;
  }
}
</style>
