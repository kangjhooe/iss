<template>
  <div class="skeleton-container">
    <!-- Table Skeleton -->
    <div v-if="type === 'table'" class="skeleton-table">
      <div class="skeleton-row" v-for="i in rows" :key="i">
        <div class="skeleton-cell" v-for="j in columns" :key="j" :style="{ width: cellWidths[j - 1] || '100px' }"></div>
      </div>
    </div>

    <!-- Card Skeleton -->
    <div v-else-if="type === 'card'" class="skeleton-card">
      <div class="skeleton-header"></div>
      <div class="skeleton-body">
        <div class="skeleton-line" v-for="i in lines" :key="i" :style="{ width: lineWidths[i - 1] || '100%' }"></div>
      </div>
    </div>

    <!-- List Skeleton -->
    <div v-else-if="type === 'list'" class="skeleton-list">
      <div class="skeleton-list-item" v-for="i in items" :key="i">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-content">
          <div class="skeleton-line" style="width: 60%"></div>
          <div class="skeleton-line" style="width: 40%"></div>
        </div>
      </div>
    </div>

    <!-- Default Skeleton -->
    <div v-else class="skeleton-default">
      <div class="skeleton-line" v-for="i in lines" :key="i" :style="{ width: lineWidths[i - 1] || '100%' }"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'default',
    validator: (value) => ['table', 'card', 'list', 'default'].includes(value)
  },
  rows: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 5
  },
  cellWidths: {
    type: Array,
    default: () => []
  },
  lines: {
    type: Number,
    default: 3
  },
  lineWidths: {
    type: Array,
    default: () => []
  },
  items: {
    type: Number,
    default: 5
  }
})
</script>

<style scoped>
.skeleton-container {
  width: 100%;
}

/* Table Skeleton */
.skeleton-table {
  width: 100%;
}

.skeleton-row {
  display: flex;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.skeleton-cell {
  height: 20px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
  border-radius: 4px;
}

/* Card Skeleton */
.skeleton-card {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.skeleton-header {
  height: 24px;
  width: 200px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
  border-radius: 4px;
  margin-bottom: 24px;
}

.skeleton-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* List Skeleton */
.skeleton-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.skeleton-list-item {
  display: flex;
  gap: 16px;
  align-items: center;
  padding: 12px;
  background: white;
  border-radius: 8px;
}

.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
  flex-shrink: 0;
}

.skeleton-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Default Skeleton */
.skeleton-default {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-line {
  height: 16px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
  border-radius: 4px;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
