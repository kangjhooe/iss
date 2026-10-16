<template>
  <div class="period-list-page">
    <header class="page-header">
      <div class="header-content">
        <div>
          <h2 class="page-title">Gelombang Ujian</h2>
          <p class="page-subtitle">Admin buat UAS/UTS, lalu isi mapel, jadwal, dan peserta. Guru tinggal pick soal.</p>
        </div>
        <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openCreate">+ Buat gelombang</button>
      </div>
    </header>

    <div v-if="loading" class="content-card"><p class="muted">Memuat…</p></div>
    <div v-else-if="!periods.length" class="content-card empty">
      <h3>Belum ada gelombang</h3>
      <p>Contoh: buat <strong>UAS Genap 2025/2026</strong>, lalu tambah mapel di dalamnya.</p>
      <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openCreate">Buat gelombang</button>
    </div>
    <div v-else class="content-card table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Gelombang</th>
            <th>Jadwal</th>
            <th>Mapel</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in periods" :key="p.id">
            <td>
              <strong>{{ p.name }}</strong>
              <div class="code">{{ p.code }}</div>
            </td>
            <td>{{ formatWindow(p) }}</td>
            <td>{{ p.exams_ready_count ?? 0 }}/{{ p.exams_count ?? 0 }} siap soal</td>
            <td><span class="badge" :class="p.status">{{ statusLabel(p.status) }}</span></td>
            <td class="col-actions">
              <router-link :to="`/ujian-online/gelombang/${p.id}`" class="btn-secondary btn-sm">Buka</router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="modal-backdrop" @click.self="showForm = false">
      <div class="modal-card">
        <h3>{{ editingId ? 'Edit gelombang' : 'Buat gelombang' }}</h3>
        <p class="hint">Cukup nama + jadwal. Mapel dan peserta diatur di halaman berikutnya.</p>
        <label>Kode <span class="req">*</span>
          <input v-model="form.code" maxlength="64" placeholder="UAS-2026-GANJIL" :disabled="!!editingId" />
        </label>
        <label>Nama <span class="req">*</span>
          <input v-model="form.name" maxlength="255" placeholder="UAS Genap 2025/2026" />
        </label>
        <label>Mulai
          <input v-model="form.window_start_at" type="datetime-local" />
        </label>
        <label>Selesai
          <input v-model="form.window_end_at" type="datetime-local" />
        </label>
        <label>Catatan
          <textarea v-model="form.description" rows="2" placeholder="Opsional"></textarea>
        </label>
        <div class="modal-actions">
          <button type="button" class="btn-primary" :disabled="saving" @click="save">{{ saving ? '…' : 'Simpan' }}</button>
          <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const periods = ref([])
const loading = ref(true)
const showForm = ref(false)
const saving = ref(false)
const editingId = ref(null)
const form = ref({
  code: '',
  name: '',
  description: '',
  window_start_at: '',
  window_end_at: ''
})

const canManage = computed(() => {
  const role = auth.user?.role
  return role === 'admin' || role === 'institution_admin' || role === 'super_admin'
})

function statusLabel(s) {
  return ({ draft: 'Draf', open: 'Dibuka', closed: 'Ditutup' })[s] || s
}

function formatWindow(p) {
  if (!p.window_start_at && !p.window_end_at) return 'Belum dijadwalkan'
  const a = p.window_start_at ? p.window_start_at.slice(0, 16).replace('T', ' ') : '—'
  const b = p.window_end_at ? p.window_end_at.slice(0, 16).replace('T', ' ') : '—'
  return `${a} → ${b}`
}

function toLocalInput(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function openCreate() {
  editingId.value = null
  form.value = { code: '', name: '', description: '', window_start_at: '', window_end_at: '' }
  showForm.value = true
}

async function fetchPeriods() {
  loading.value = true
  try {
    const res = await examApi.listPeriods({ per_page: 50 })
    periods.value = res.data?.data ?? []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak bisa memuat gelombang.')
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value.code.trim() || !form.value.name.trim()) {
    toast.error('Validasi', 'Kode dan nama wajib.')
    return
  }
  saving.value = true
  try {
    const payload = {
      code: form.value.code.trim(),
      name: form.value.name.trim(),
      description: form.value.description?.trim() || null,
      window_start_at: form.value.window_start_at || null,
      window_end_at: form.value.window_end_at || null,
      status: 'open'
    }
    const res = await examApi.createPeriod(payload)
    const created = res.data?.data ?? res.data
    toast.success('Berhasil', 'Gelombang dibuat. Lanjut tambah mapel.')
    showForm.value = false
    if (created?.id) router.push(`/ujian-online/gelombang/${created.id}`)
    else fetchPeriods()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Tidak bisa menyimpan.')
  } finally {
    saving.value = false
  }
}

onMounted(fetchPeriods)
</script>

<style scoped>
.period-list-page { padding: 1.25rem 1rem 2rem; max-width: 960px; margin: 0 auto; }
.page-header { margin-bottom: 1rem; }
.header-content { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
.page-title { margin: 0 0 0.25rem; font-size: 1.35rem; }
.page-subtitle { margin: 0; color: #64748b; font-size: 0.9rem; }
.content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; }
.empty h3 { margin: 0 0 0.35rem; }
.muted { color: #64748b; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { text-align: left; padding: 0.65rem 0.5rem; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
.code { font-size: 0.75rem; color: #64748b; }
.badge { font-size: 0.72rem; padding: 0.15rem 0.45rem; border-radius: 999px; background: #f1f5f9; }
.badge.open { background: #d1fae5; color: #065f46; }
.badge.draft { background: #e2e8f0; color: #475569; }
.badge.closed { background: #fee2e2; color: #991b1b; }
.btn-primary, .btn-secondary { border: none; border-radius: 8px; padding: 0.45rem 0.85rem; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; }
.btn-primary { background: #059669; color: #fff; }
.btn-secondary { background: #f1f5f9; color: #334155; }
.btn-sm { font-size: 0.8rem; padding: 0.3rem 0.6rem; }
.btn-compact { font-size: 0.875rem; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; }
.modal-card { background: #fff; border-radius: 14px; padding: 1.25rem; width: min(420px, 100%); display: flex; flex-direction: column; gap: 0.65rem; }
.modal-card h3 { margin: 0; }
.hint { margin: 0; font-size: 0.82rem; color: #64748b; }
.modal-card label { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.85rem; font-weight: 600; color: #334155; }
.modal-card input, .modal-card textarea { font-weight: 400; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.45rem 0.6rem; }
.req { color: #dc2626; }
.modal-actions { display: flex; gap: 0.5rem; margin-top: 0.35rem; }
</style>
