<template>
  <div v-if="items.length" class="stimulus-media-view">
    <figure v-for="(item, idx) in items" :key="idx" class="media-block" :class="'type-' + item.type">
      <img v-if="item.type === 'image'" :src="item.url" :alt="item.caption || 'Gambar stimulus'" />
      <audio v-else-if="item.type === 'audio'" :src="item.url" controls preload="metadata" />
      <video v-else-if="item.type === 'video'" :src="item.url" controls preload="metadata" playsinline />
      <div v-else-if="item.type === 'youtube'" class="yt-wrap">
        <iframe
          :src="item.url"
          :title="item.caption || 'Video YouTube'"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
          loading="lazy"
        />
      </div>
      <figcaption v-if="item.caption">{{ item.caption }}</figcaption>
    </figure>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  media: { type: Array, default: () => [] }
})

const items = computed(() => (Array.isArray(props.media) ? props.media.filter((m) => m?.url) : []))
</script>

<style scoped>
.stimulus-media-view { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 0.75rem; }
.media-block { margin: 0; }
.media-block img,
.media-block video {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  display: block;
}
.media-block audio { width: 100%; }
.yt-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #111;
  border-radius: 6px;
  overflow: hidden;
}
.yt-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
}
figcaption {
  margin-top: 0.35rem;
  font-size: 0.85rem;
  color: #6b7280;
}
</style>
