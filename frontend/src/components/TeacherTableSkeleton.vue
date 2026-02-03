<template>
  <LoadingSkeleton
    type="table"
    :rows="8"
    :columns="10"
    :cell-widths="['80px', '100px', '100px', '100px', '180px', '100px', '140px', '120px', '90px', '120px']"
  />
</template>

<script setup>
import LoadingSkeleton from './LoadingSkeleton.vue'
</script>
