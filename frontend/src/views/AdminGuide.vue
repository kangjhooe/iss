<template>
  <GuideRoleGuide :guide="adminGuide" />
</template>

<script setup>
import GuideRoleGuide from '@/components/GuideRoleGuide.vue'
import { adminGuide } from '@/content/guides/admin'
</script>
