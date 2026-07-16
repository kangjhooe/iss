<template>
  <LoadingSkeleton 
    type="table" 
    :rows="5" 
    :columns="9"
    :cell-widths="['48px', '120px', '100px', '120px', '200px', '120px', '100px', '100px', '150px']"
  />
</template>

<script setup>
import LoadingSkeleton from './LoadingSkeleton.vue'
</script>
