<template>
  <GuideRoleGuide :guide="studentGuide" />
</template>

<script setup>
import GuideRoleGuide from '@/components/GuideRoleGuide.vue'
import { studentGuide } from '@/content/guides/student'
</script>
