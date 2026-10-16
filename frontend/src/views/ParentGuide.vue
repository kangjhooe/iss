<template>
  <GuideRoleGuide :guide="parentGuide" />
</template>

<script setup>
import GuideRoleGuide from '@/components/GuideRoleGuide.vue'
import { parentGuide } from '@/content/guides/parent'
</script>
