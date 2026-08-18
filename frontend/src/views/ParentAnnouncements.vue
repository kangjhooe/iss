<template>
  <Layout>
    <div class="sp-page">
      <div class="sp-page-header">
        <div>
          <router-link to="/parent/dashboard" class="back-link">← Dashboard</router-link>
          <h1>Pengumuman</h1>
          <p class="page-meta">Event kalender akademik mendatang</p>
        </div>
      </div>

      <div v-if="loading" class="sp-loading"><p>Memuat...</p></div>
      <div v-else-if="!events.length" class="sp-empty">
        <p class="sp-empty-desc">Belum ada pengumuman / event mendatang.</p>
      </div>
      <div v-else class="sp-list">
        <div v-for="ev in events" :key="ev.id" class="sp-list-item sp-list-item--info">
          <span class="sp-list-title">{{ ev.title }}</span>
          <span class="sp-list-meta">{{ formatDate(ev.start_date) }}</span>
          <span v-if="ev.event_type" class="sp-chip">{{ ev.event_type }}</span>
          <p v-if="ev.description" class="desc">{{ ev.description }}</p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { parentApi } from '@/api/parent'
import '@/assets/student-portal.css'

const loading = ref(true)
const events = ref([])

function formatDate(d) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return d
  }
}

onMounted(async () => {
  try {
    const res = await parentApi.announcements({ days: 60 })
    const list = res.data?.data ?? res.data ?? []
    events.value = Array.isArray(list) ? list : []
  } catch {
    events.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.back-link {
  display: inline-block;
  margin-bottom: 6px;
  color: #059669;
  font-size: 0.85rem;
  text-decoration: none;
  font-weight: 600;
}
.desc {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 0.85rem;
  width: 100%;
}
</style>
