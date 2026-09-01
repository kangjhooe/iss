<template>    <div class="booking-page">
      <header class="page-head">
        <div>
          <h1>Booking Lab</h1>
          <p>Ajukan pemakaian laboratorium di luar jadwal pelajaran tetap.</p>
        </div>
        <button type="button" class="btn-primary" @click="openForm()">Ajukan Booking</button>
      </header>

      <div class="cards">
        <div class="card">
          <strong>{{ myBookings.filter(b => b.status === 'pending').length }}</strong>
          <span>Menunggu</span>
        </div>
        <div class="card">
          <strong>{{ myBookings.filter(b => b.status === 'approved').length }}</strong>
          <span>Disetujui</span>
        </div>
        <div class="card">
          <strong>{{ labs.length }}</strong>
          <span>Lab tersedia</span>
        </div>
      </div>

      <section class="panel">
        <h2>Ajuan Saya</h2>
        <div v-if="loading" class="muted">Memuat...</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Lab</th>
              <th>Tanggal</th>
              <th>Waktu</th>
              <th>Keperluan</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in myBookings" :key="b.id">
              <td>{{ b.room?.name || '-' }}</td>
              <td>{{ formatDate(b.date) }}</td>
              <td>{{ formatTime(b.start_time) }}-{{ formatTime(b.end_time) }}</td>
              <td>{{ b.purpose }}</td>
              <td><span :class="'st-' + b.status">{{ statusLabel(b.status) }}</span></td>
              <td>
                <TableAction
                  v-if="['pending', 'approved'].includes(b.status)"
                  kind="cancel"
                  title="Batalkan"
                  @click="cancel(b)"
                />
              </td>
            </tr>
            <tr v-if="!myBookings.length">
              <td colspan="6" class="muted">Belum ada ajuan booking. Klik Ajukan Booking untuk memulai.</td>
            </tr>
          </tbody>
        </table>
      </section>

      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Ajukan Booking Lab</h3>
            <button type="button" class="btn-close" @click="showForm = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submit">
            <div class="form-group">
              <label>Lab *</label>
              <select v-model="form.room_id" required class="form-input">
                <option value="">Pilih lab</option>
                <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                  {{ lab.name }}{{ lab.building?.name ? ` · ${lab.building.name}` : '' }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Keperluan *</label>
              <input v-model="form.purpose" required class="form-input" placeholder="Contoh: Ujian praktik IPA" />
            </div>
            <div class="form-group">
              <label>Tanggal *</label>
              <input v-model="form.date" type="date" required class="form-input" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Mulai *</label>
                <input v-model="form.start_time" type="time" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Selesai *</label>
                <input v-model="form.end_time" type="time" required class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="form.notes" rows="2" class="form-input"></textarea>
            </div>
            <p v-if="error" class="form-error">{{ error }}</p>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Mengirim...' : 'Kirim Ajuan' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import TableAction from '@/components/TableAction.vue'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const showForm = ref(false)
const error = ref('')
const labs = ref([])
const myBookings = ref([])

const form = reactive({
  room_id: '',
  purpose: '',
  date: '',
  start_time: '08:00',
  end_time: '10:00',
  notes: '',
})

function unwrapList(res) {
  const d = res?.data
  if (Array.isArray(d?.data)) return d.data
  if (Array.isArray(d)) return d
  return []
}

function formatDate(d) {
  return d ? String(d).slice(0, 10) : '-'
}
function formatTime(t) {
  return t ? String(t).slice(0, 5) : '-'
}
function statusLabel(s) {
  return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' })[s] || s
}

function openForm() {
  form.room_id = labs.value[0]?.id || ''
  form.purpose = ''
  form.date = ''
  form.start_time = '08:00'
  form.end_time = '10:00'
  form.notes = ''
  error.value = ''
  showForm.value = true
}

async function load() {
  loading.value = true
  try {
    const [labsRes, bookRes] = await Promise.all([
      facilityApi.getLabsForBooking(),
      facilityApi.getOpenLabBookings({ mine: 1, per_page: 50 }),
    ])
    labs.value = unwrapList(labsRes)
    myBookings.value = unwrapList(bookRes)
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat data booking')
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''
  try {
    await facilityApi.createOpenLabBooking({
      room_id: Number(form.room_id),
      purpose: form.purpose,
      date: form.date,
      start_time: form.start_time,
      end_time: form.end_time,
      notes: form.notes || null,
    })
    toast.success('Berhasil', 'Ajuan booking lab dikirim')
    showForm.value = false
    await load()
  } catch (e) {
    error.value = e.response?.data?.message
      || (e.response?.data?.errors && Object.values(e.response.data.errors).flat()[0])
      || 'Gagal mengirim ajuan'
    toast.error('Gagal', error.value)
  } finally {
    saving.value = false
  }
}

async function cancel(b) {
  if (!confirm('Batalkan ajuan booking ini?')) return
  try {
    await facilityApi.cancelOpenLabBooking(b.id)
    toast.success('Berhasil', 'Booking dibatalkan')
    await load()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal membatalkan')
  }
}

onMounted(load)
</script>

<style scoped>
.booking-page {
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 30%, #f1f5f9 100%);
  min-height: 100%;
}
.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}
.page-head h1 { margin: 0 0 0.25rem; font-size: 1.45rem; color: #0f172a; }
.page-head p { margin: 0; color: #64748b; }
.cards { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem; }
.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem 1.1rem;
  min-width: 110px;
}
.card strong { display: block; font-size: 1.35rem; color: #059669; }
.card span { font-size: 0.8rem; color: #64748b; }
.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.25rem;
}
.panel h2 { margin: 0 0 0.75rem; font-size: 1.05rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { border-bottom: 1px solid #e2e8f0; padding: 0.55rem 0.4rem; text-align: left; }
.data-table th { color: #64748b; font-size: 0.8rem; }
.muted { color: #94a3b8; }
.btn-primary, .btn-secondary {
  border: none; border-radius: 8px; padding: 0.55rem 1rem; cursor: pointer; font-weight: 600;
}
.btn-primary { background: #059669; color: #fff; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.btn-link { border: none; background: transparent; cursor: pointer; color: #059669; }
.btn-link.danger { color: #dc2626; }
.st-pending { color: #d97706; font-weight: 600; }
.st-approved { color: #059669; font-weight: 600; }
.st-rejected, .st-cancelled { color: #94a3b8; }
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center; z-index: 80; padding: 1rem;
}
.modal-content { background: #fff; border-radius: 14px; width: min(480px, 100%); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; }
.btn-close { border: none; background: transparent; font-size: 1.4rem; cursor: pointer; }
.modal-body { padding: 1rem 1.25rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.85rem; color: #475569; margin-bottom: 0.25rem; }
.form-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.65rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.form-error { color: #dc2626; font-size: 0.88rem; }
</style>
