<template>
  <div class="stimulus-media-panel">
    <div class="panel-head">
      <label>Media (opsional)</label>
      <p class="hint">Tambahkan gambar, audio, video, atau tautan YouTube. Bisa digabung dengan teks di bawah.</p>
    </div>

    <div class="media-actions">
      <button type="button" class="btn-chip" :disabled="uploading" @click="pickFile('image')">+ Gambar</button>
      <button type="button" class="btn-chip" :disabled="uploading" @click="pickFile('audio')">+ Audio</button>
      <button type="button" class="btn-chip" :disabled="uploading" @click="pickFile('video')">+ Video</button>
      <button type="button" class="btn-chip" :disabled="uploading" @click="showYoutube = !showYoutube">+ YouTube</button>
      <span v-if="uploading" class="uploading">Mengunggah…</span>
    </div>

    <div v-if="showYoutube" class="youtube-row">
      <input
        v-model="youtubeUrl"
        type="url"
        placeholder="Tempel URL YouTube (watch / youtu.be / shorts)"
        @keydown.enter.prevent="addYoutube"
      />
      <button type="button" class="btn-secondary" @click="addYoutube">Tambah</button>
    </div>

    <ul v-if="modelValue.length" class="media-list">
      <li v-for="(item, idx) in modelValue" :key="idx" class="media-item">
        <div class="media-meta">
          <span class="badge">{{ mediaTypeLabel(item.type) }}</span>
          <input
            class="caption-input"
            type="text"
            :value="item.caption || ''"
            placeholder="Keterangan (opsional)"
            maxlength="255"
            @input="updateCaption(idx, $event.target.value)"
          />
        </div>
        <div class="media-preview">
          <img v-if="item.type === 'image'" :src="item.url" alt="" />
          <audio v-else-if="item.type === 'audio'" :src="item.url" controls preload="metadata" />
          <video v-else-if="item.type === 'video'" :src="item.url" controls preload="metadata" />
          <iframe
            v-else-if="item.type === 'youtube'"
            :src="item.url"
            title="YouTube"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            loading="lazy"
          />
        </div>
        <button type="button" class="btn-remove" title="Hapus media" @click="removeAt(idx)">Hapus</button>
      </li>
    </ul>

    <input ref="fileInput" type="file" class="hidden-file" :accept="fileAccept" @change="onFileChosen" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'
import { acceptForKind, extractYoutubeId, mediaTypeLabel, youtubeEmbedUrl } from '@/utils/stimulusMedia'

const props = defineProps({
  modelValue: { type: Array, default: () => [] }
})
const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const fileInput = ref(null)
const pendingKind = ref('image')
const fileAccept = ref(acceptForKind('image'))
const uploading = ref(false)
const showYoutube = ref(false)
const youtubeUrl = ref('')

function setMedia(next) {
  emit('update:modelValue', next)
}

function pickFile(kind) {
  pendingKind.value = kind
  fileAccept.value = acceptForKind(kind)
  fileInput.value?.click()
}

async function onFileChosen(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file) return
  uploading.value = true
  try {
    const res = await examApi.uploadQuestionAsset(file, pendingKind.value)
    const url = res.data?.location || res.data?.url
    if (!url) throw new Error('URL kosong')
    setMedia([
      ...props.modelValue,
      { type: pendingKind.value, url, caption: null, video_id: null }
    ])
    toast.success('Berhasil', `${mediaTypeLabel(pendingKind.value)} ditambahkan.`)
  } catch (err) {
    const msg = err.response?.data?.message || 'File tidak dapat diunggah.'
    toast.error('Gagal mengunggah', msg)
  } finally {
    uploading.value = false
  }
}

function addYoutube() {
  const id = extractYoutubeId(youtubeUrl.value)
  if (!id) {
    toast.error('URL tidak valid', 'Tempel tautan YouTube yang valid.')
    return
  }
  setMedia([
    ...props.modelValue,
    { type: 'youtube', url: youtubeEmbedUrl(id), caption: null, video_id: id }
  ])
  youtubeUrl.value = ''
  showYoutube.value = false
  toast.success('Berhasil', 'Video YouTube ditambahkan.')
}

function updateCaption(idx, caption) {
  const next = props.modelValue.map((item, i) => (i === idx ? { ...item, caption: caption || null } : item))
  setMedia(next)
}

function removeAt(idx) {
  setMedia(props.modelValue.filter((_, i) => i !== idx))
}
</script>

<style scoped>
.stimulus-media-panel { margin-bottom: 1rem; }
.panel-head label { display: block; margin-bottom: 0.15rem; font-weight: 500; }
.hint { margin: 0 0 0.5rem; font-size: 0.85rem; color: #6b7280; }
.media-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; margin-bottom: 0.5rem; }
.btn-chip {
  padding: 0.35rem 0.7rem;
  border: 1px solid #d1d5db;
  background: #f9fafb;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
}
.btn-chip:hover:not(:disabled) { background: #ecfdf5; border-color: #6ee7b7; }
.btn-chip:disabled { opacity: 0.6; cursor: not-allowed; }
.uploading { font-size: 0.85rem; color: #6b7280; }
.youtube-row { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.youtube-row input { flex: 1; padding: 0.45rem 0.6rem; border: 1px solid #d1d5db; border-radius: 6px; }
.btn-secondary { padding: 0.45rem 0.85rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; }
.media-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.75rem; }
.media-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.65rem;
  background: #fafafa;
}
.media-meta { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem; }
.badge {
  font-size: 0.75rem;
  font-weight: 600;
  color: #065f46;
  background: #d1fae5;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  white-space: nowrap;
}
.caption-input { flex: 1; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; }
.media-preview img,
.media-preview video {
  max-width: 100%;
  max-height: 220px;
  border-radius: 6px;
  display: block;
}
.media-preview audio { width: 100%; }
.media-preview iframe {
  width: 100%;
  aspect-ratio: 16 / 9;
  border: 0;
  border-radius: 6px;
  background: #111;
}
.btn-remove {
  margin-top: 0.45rem;
  background: none;
  border: none;
  color: #dc2626;
  cursor: pointer;
  font-size: 0.85rem;
  padding: 0;
}
.hidden-file { display: none; }
</style>
