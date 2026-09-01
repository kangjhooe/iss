<template>    <div class="sp-page dashboard">
      <section class="sp-hero">
        <h1>Halo, {{ authStore.user?.name || 'Orang Tua' }}</h1>
        <p>Portal orang tua / wali murid — pantau akademik anak secara ringkas.</p>
      </section>

      <div v-if="loading" class="sp-loading"><p>Memuat...</p></div>

      <template v-else>
        <section class="sp-panel">
          <div class="sp-panel-header">
            <h2 class="sp-panel-title">Anak / Wali</h2>
          </div>
          <div v-if="!children.length" class="sp-empty">
            <p class="sp-empty-desc">Belum ada siswa terhubung. Pastikan nomor HP wali cocok dengan akun, atau minta admin menautkan lewat parent_links.</p>
          </div>
          <div v-else class="children-grid">
            <div v-for="c in children" :key="c.id" class="child-card">
              <h3>{{ c.name }}</h3>
              <p class="meta">{{ c.class_name || '—' }} · {{ c.institution_name || '' }}</p>
              <div class="child-actions">
                <router-link :to="`/parent/anak/${c.id}/jadwal`" class="sp-action-link">Jadwal</router-link>
                <router-link :to="`/parent/anak/${c.id}/nilai`" class="sp-action-link">Nilai</router-link>
                <router-link :to="`/parent/anak/${c.id}/absensi`" class="sp-action-link">Absensi</router-link>
                <router-link :to="`/parent/anak/${c.id}/pelanggaran`" class="sp-action-link">Pelanggaran</router-link>
              </div>
            </div>
          </div>
        </section>

        <section class="sp-panel">
          <div class="sp-panel-header">
            <h2 class="sp-panel-title">Pengumuman / Kalender</h2>
            <router-link to="/parent/pengumuman" class="sp-panel-link">Lihat semua</router-link>
          </div>
          <div v-if="!announcements.length" class="sp-empty">
            <p class="sp-empty-desc">Belum ada event mendatang.</p>
          </div>
          <div v-else class="sp-list">
            <div v-for="ev in announcements" :key="ev.id" class="sp-list-item sp-list-item--info">
              <span class="sp-list-title">{{ ev.title }}</span>
              <span class="sp-list-meta">{{ formatDate(ev.start_date) }}</span>
              <span v-if="ev.event_type" class="sp-chip">{{ ev.event_type }}</span>
            </div>
          </div>
        </section>
      </template>
    </div></template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { parentApi } from '@/api/parent'
import '@/assets/student-portal.css'

const authStore = useAuthStore()
const loading = ref(true)
const children = ref([])
const announcements = ref([])

function formatDate(d) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return d
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await parentApi.dashboard()
    const data = res.data?.data ?? {}
    children.value = data.children || []
    announcements.value = data.announcements || []
  } catch {
    children.value = []
    announcements.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.children-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
}
.child-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  background: #fff;
}
.child-card h3 {
  margin: 0 0 4px;
  font-size: 1rem;
}
.child-card .meta {
  margin: 0 0 10px;
  color: #64748b;
  font-size: 0.85rem;
}
.child-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.sp-action-link {
  font-size: 0.8rem;
  color: #059669;
  font-weight: 600;
  text-decoration: none;
}
.sp-action-link:hover { text-decoration: underline; }
</style>
