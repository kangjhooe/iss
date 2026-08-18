<template>
  <Layout>
    <div class="sp-page">
      <div v-if="!studentId && authStore.user?.role === 'student'" class="sp-alert sp-alert-warning">
        <strong>Profil siswa tidak ditemukan.</strong> Data Anda mungkin belum dihubungkan dengan data siswa di sekolah.
      </div>

      <div class="sp-page-header">
        <div>
          <p class="sp-subtitle">Lowongan BKK terbuka dan status lamaran Anda</p>
        </div>
      </div>

      <div class="sp-filters">
        <div class="sp-filter">
          <label for="bkk-tab">Tampilan</label>
          <select id="bkk-tab" v-model="activeTab">
            <option value="vacancies">Lowongan terbuka</option>
            <option value="applications">Lamaran saya</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="sp-loading"><p>Memuat data BKK...</p></div>
      <div v-else-if="loadError" class="sp-empty">
        <h3 class="sp-empty-title">Gagal memuat data</h3>
        <p class="sp-empty-desc">Silakan coba lagi.</p>
        <button type="button" class="sp-btn sp-btn--soft" @click="reload">Coba lagi</button>
      </div>

      <template v-else-if="activeTab === 'vacancies'">
        <div v-if="!vacancies.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada lowongan terbuka</h3>
          <p class="sp-empty-desc">Cek lagi nanti atau hubungi BKK sekolah.</p>
        </div>
        <div v-else class="vacancy-list">
          <article v-for="v in vacancies" :key="v.id" class="vacancy-card">
            <div class="vacancy-head">
              <h3>{{ v.title }}</h3>
              <span class="status-chip buka">{{ v.status }}</span>
            </div>
            <p class="vacancy-meta">
              {{ v.industry_partner?.name || v.company_name || '—' }}
              <template v-if="v.position"> · {{ v.position }}</template>
            </p>
            <p v-if="v.deadline" class="vacancy-deadline">Deadline: {{ formatDate(v.deadline) }}</p>
            <p v-if="v.description" class="vacancy-desc">{{ v.description }}</p>
            <p v-if="v.requirements" class="vacancy-req"><strong>Syarat:</strong> {{ v.requirements }}</p>
            <div class="vacancy-actions">
              <button
                type="button"
                class="sp-btn sp-btn--primary"
                :disabled="appliedIds.has(v.id) || applyingId === v.id"
                @click="apply(v)"
              >
                {{ appliedIds.has(v.id) ? 'Sudah dilamar' : (applyingId === v.id ? 'Mengirim...' : 'Lamar') }}
              </button>
            </div>
          </article>
        </div>
      </template>

      <template v-else>
        <div v-if="!applications.length" class="sp-empty">
          <h3 class="sp-empty-title">Belum ada lamaran</h3>
          <p class="sp-empty-desc">Pilih lowongan terbuka lalu klik Lamar.</p>
        </div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Lowongan</th>
              <th>Perusahaan</th>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in applications" :key="a.id">
              <td>{{ a.vacancy?.title || '—' }}</td>
              <td>{{ a.vacancy?.industry_partner?.name || a.vacancy?.company_name || '—' }}</td>
              <td>{{ formatDate(a.applied_at) }}</td>
              <td><span class="status-chip" :class="a.status">{{ a.status }}</span></td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>
  </Layout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { bkkApi } from '@/api/bkk'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const toast = useToast()

const studentId = computed(() => authStore.user?.student_id || authStore.user?.student?.id || null)
const activeTab = ref('vacancies')
const loading = ref(false)
const loadError = ref(false)
const vacancies = ref([])
const applications = ref([])
const applyingId = ref(null)

const appliedIds = computed(() => new Set(applications.value.map((a) => a.bkk_vacancy_id || a.vacancy?.id).filter(Boolean)))

function formatDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return String(v)
  }
}

async function loadVacancies() {
  const res = await bkkApi.myVacancies({ per_page: 50 })
  vacancies.value = res.data?.data || res.data || []
}

async function loadApplications() {
  const res = await bkkApi.myApplications()
  applications.value = res.data?.data || []
}

async function reload() {
  loading.value = true
  loadError.value = false
  try {
    await Promise.all([loadVacancies(), loadApplications()])
  } catch (e) {
    loadError.value = true
    toast.error('Gagal', e.formattedMessage || 'Data BKK tidak dapat dimuat.')
  } finally {
    loading.value = false
  }
}

async function apply(v) {
  applyingId.value = v.id
  try {
    await bkkApi.applyMyself({ bkk_vacancy_id: v.id })
    toast.success('Berhasil', 'Lamaran dikirim.')
    await loadApplications()
    activeTab.value = 'applications'
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Tidak dapat mengirim lamaran.')
  } finally {
    applyingId.value = null
  }
}

watch(activeTab, () => {})
onMounted(reload)
</script>

<style scoped>
.sp-page { max-width: 960px; margin: 0 auto; padding: 0 0 2rem; }
.sp-alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; background: #fff7ed; color: #9a3412; }
.sp-page-header { margin-bottom: 1rem; }
.sp-subtitle { color: #64748b; margin: 0; }
.sp-filters { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
.sp-filter label { display: block; font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; }
.sp-filter select { padding: 0.45rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 8px; }
.sp-loading, .sp-empty { text-align: center; padding: 2rem 1rem; color: #64748b; }
.sp-empty-title { margin: 0 0 0.35rem; color: #0f172a; }
.sp-btn { border: none; border-radius: 8px; padding: 0.5rem 0.9rem; cursor: pointer; font-weight: 600; }
.sp-btn--primary { background: #0f766e; color: #fff; }
.sp-btn--primary:disabled { opacity: 0.6; cursor: not-allowed; }
.sp-btn--soft { background: #e2e8f0; color: #334155; }
.vacancy-list { display: grid; gap: 0.85rem; }
.vacancy-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; background: #fff; }
.vacancy-head { display: flex; justify-content: space-between; gap: 0.75rem; align-items: start; }
.vacancy-head h3 { margin: 0; font-size: 1.05rem; }
.vacancy-meta, .vacancy-deadline, .vacancy-desc, .vacancy-req { margin: 0.4rem 0 0; color: #475569; font-size: 0.9rem; }
.vacancy-actions { margin-top: 0.75rem; }
.status-chip { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.75rem; text-transform: capitalize; background: #e2e8f0; }
.status-chip.buka, .status-chip.diajukan { background: #ccfbf1; color: #115e59; }
.status-chip.seleksi { background: #fef3c7; color: #92400e; }
.status-chip.diterima { background: #dcfce7; color: #166534; }
.status-chip.ditolak { background: #fee2e2; color: #991b1b; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { padding: 0.65rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
.data-table th { background: #f8fafc; color: #334155; }
</style>
