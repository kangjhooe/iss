<template>
  <PanduanRoleGuide :guide="adminGuide" />
</template>

<script setup>
import PanduanRoleGuide from '@/components/PanduanRoleGuide.vue'
import { adminGuide } from '@/content/guides/admin'
</script>
