<template>
  <GuideRoleGuide :guide="teacherGuide" />
</template>

<script setup>
import GuideRoleGuide from '@/components/GuideRoleGuide.vue'
import { teacherGuide } from '@/content/guides/teacher'
</script>
