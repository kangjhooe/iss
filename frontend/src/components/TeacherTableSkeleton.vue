<template>
  <LoadingSkeleton
    type="table"
    :rows="8"
    :columns="7"
    :cell-widths="['48px', '180px', '120px', '120px', '120px', '100px', '110px']"
  />
</template>

<script setup>
import LoadingSkeleton from './LoadingSkeleton.vue'
</script>
