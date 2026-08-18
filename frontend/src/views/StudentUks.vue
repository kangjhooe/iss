<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <p class="sp-subtitle">Ringkasan kunjungan Anda ke Unit Kesehatan Sekolah (UKS)</p>
      </div>

      <div v-if="loading" class="sp-loading">
        <p>Memuat riwayat kunjungan UKS...</p>
      </div>

      <template v-else>
        <div class="sp-stats uks-stats">
          <div class="sp-stat sp-stat--primary">
            <div>
              <span class="sp-stat-label">Total kunjungan</span>
              <span class="sp-stat-value">{{ summary.total ?? 0 }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--ok">
            <div>
              <span class="sp-stat-label">Selesai</span>
              <span class="sp-stat-value">{{ summary.selesai ?? 0 }}</span>
            </div>
          </div>
          <div class="sp-stat sp-stat--warn">
            <div>
              <span class="sp-stat-label">Observasi</span>
              <span class="sp-stat-value">{{ summary.observasi ?? 0 }}</span>
            </div>
          </div>
          <div class="sp-stat">
            <div>
              <span class="sp-stat-label">Rujuk</span>
              <span class="sp-stat-value">{{ summary.rujuk ?? 0 }}</span>
            </div>
          </div>
        </div>

        <p v-if="summary.last_visit_date" class="last-visit">
          Kunjungan terakhir: <strong>{{ formatDate(summary.last_visit_date) }}</strong>
        </p>

        <div v-if="loadError" class="sp-empty">
          <h3 class="sp-empty-title">Gagal memuat data</h3>
          <p class="sp-empty-desc">Silakan coba lagi.</p>
          <div class="sp-empty-actions">
            <button type="button" class="sp-btn sp-btn--soft" @click="loadVisits">Coba lagi</button>
          </div>
        </div>

        <div v-else-if="!visits.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada kunjungan UKS</h3>
          <p class="sp-empty-desc">Belum ada catatan kunjungan Anda ke UKS.</p>
        </div>

        <div v-else class="sp-panel">
          <div class="sp-list">
            <div v-for="v in visits" :key="v.id" class="sp-list-item uks-card">
              <div class="card-main">
                <div class="card-top">
                  <span class="sp-list-title">{{ v.visit_type?.name || 'Kunjungan UKS' }}</span>
                  <span class="sp-badge" :class="statusBadge(v.status)">{{ statusLabel(v.status) }}</span>
                </div>
                <div class="sp-list-meta">{{ formatDate(v.visit_date) }}</div>
                <div v-if="v.recorder?.name" class="sp-list-meta">Petugas: {{ v.recorder.name }}</div>
                <div v-if="v.complaint" class="card-row"><span>Keluhan</span><strong>{{ v.complaint }}</strong></div>
                <div v-if="v.action_taken" class="card-row"><span>Tindakan</span><strong>{{ v.action_taken }}</strong></div>
                <div v-if="hasVitals(v)" class="vitals">
                  <span v-if="v.height_cm != null">TB {{ v.height_cm }} cm</span>
                  <span v-if="v.weight_kg != null">BB {{ v.weight_kg }} kg</span>
                  <span v-if="v.temperature_c != null">Suhu {{ v.temperature_c }}°C</span>
                  <span v-if="v.blood_pressure">TD {{ v.blood_pressure }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Layout from '@/components/Layout.vue'
import { useAuthStore } from '@/stores/auth'
import { uksApi } from '@/api/uks'

const authStore = useAuthStore()
const studentId = computed(() => authStore.user?.student_profile?.id)

const loading = ref(true)
const loadError = ref(false)
const visits = ref([])
const summary = ref({
  total: 0,
  selesai: 0,
  observasi: 0,
  rujuk: 0,
  last_visit_date: null,
})

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  if (Number.isNaN(d.getTime())) return val
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusLabel(status) {
  const map = { selesai: 'Selesai', observasi: 'Observasi', rujuk: 'Rujuk' }
  return map[status] || status || '-'
}

function statusBadge(status) {
  if (status === 'selesai') return 'sp-badge--done'
  if (status === 'observasi') return 'sp-badge--pending'
  if (status === 'rujuk') return 'sp-badge--cancel'
  return 'sp-badge--pending'
}

function hasVitals(v) {
  return v.height_cm != null || v.weight_kg != null || v.temperature_c != null || v.blood_pressure
}

async function loadVisits() {
  if (!studentId.value) {
    loading.value = false
    return
  }
  loading.value = true
  try {
    loadError.value = false
    const res = await uksApi.getMy()
    const list = res.data?.data ?? []
    visits.value = Array.isArray(list) ? list : []
    summary.value = res.data?.summary || {
      total: visits.value.length,
      selesai: 0,
      observasi: 0,
      rujuk: 0,
      last_visit_date: null,
    }
  } catch {
    loadError.value = true
    visits.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadVisits)
</script>

<style scoped>
.uks-stats {
  margin-bottom: 12px;
}

.last-visit {
  margin: 0 0 16px;
  font-size: 13px;
  color: #64748b;
}

.uks-card {
  align-items: stretch;
  background: #fff;
}

.card-main {
  width: 100%;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.card-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 8px;
  font-size: 13px;
}

.card-row span {
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.card-row strong {
  color: #334155;
  font-weight: 500;
  line-height: 1.45;
}

.vitals {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid #e2e8f0;
}

.vitals span {
  font-size: 12px;
  color: #475569;
  background: #f1f5f9;
  padding: 3px 8px;
  border-radius: 6px;
}
</style>
