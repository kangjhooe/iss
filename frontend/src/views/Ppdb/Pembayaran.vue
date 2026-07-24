<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Pembayaran PPDB</h1>
              <p class="page-subtitle">Biaya periode & status pembayaran calon</p>
            </div>
          </div>
        </div>
      </header>

      <main class="page-main">
        <section class="content-card">
          <h2 class="section-title">Biaya per periode</h2>
          <p class="section-hint">Atur biaya pendaftaran dan daftar ulang. Perubahan langsung tersimpan.</p>
          <div v-if="periodsLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="3" :columns="4" /></div>
          <div v-else-if="periods.length === 0" class="empty-inline">
            Belum ada periode. <router-link to="/ppdb/konfigurasi">Buat di Konfigurasi</router-link>
          </div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Periode</th>
                  <th>Status</th>
                  <th>Biaya daftar</th>
                  <th>Biaya daftar ulang</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in periods" :key="p.id">
                  <td><strong>{{ p.name }}</strong></td>
                  <td><span :class="['status-badge', 'status-' + p.status]">{{ statusPeriodLabel(p.status) }}</span></td>
                  <td>
                    <input v-model.number="feeForms[p.id].registration_fee" type="number" min="0" step="1000" class="fee-input" placeholder="0" />
                  </td>
                  <td>
                    <input v-model.number="feeForms[p.id].re_registration_fee" type="number" min="0" step="1000" class="fee-input" placeholder="0" />
                  </td>
                  <td>
                    <button type="button" class="btn-action btn-edit" :disabled="savingFeeId === p.id" @click="saveFees(p)">
                      {{ savingFeeId === p.id ? '...' : 'Simpan' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="content-card">
          <div class="filters-bar" style="padding: 0; box-shadow: none; border: none; margin-bottom: 1rem;">
            <h2 class="section-title" style="margin: 0; flex: 1;">Status pembayaran calon</h2>
            <select v-model="filters.ppdb_period_id" class="filter-select" @change="loadApplicants">
              <option value="">Semua Periode</option>
              <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <select v-model="filters.payment_status" class="filter-select" @change="loadApplicants">
              <option value="">Semua status bayar</option>
              <option v-for="(l, k) in paymentStatusLabels" :key="k" :value="k">{{ l }}</option>
            </select>
          </div>

          <div v-if="applicantsLoading" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="6" /></div>
          <div v-else-if="applicants.length === 0" class="empty-inline">Tidak ada data sesuai filter.</div>
          <div v-else class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No. Pendaftaran</th>
                  <th>Nama</th>
                  <th>Status</th>
                  <th>Bayar</th>
                  <th>Nominal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in applicants" :key="a.id">
                  <td><strong>{{ a.registration_number }}</strong></td>
                  <td>{{ a.name }}</td>
                  <td><span :class="['status-badge', 'status-' + a.status]">{{ statusApplicantLabels[a.status] || a.status }}</span></td>
                  <td><span :class="['pay-badge', 'pay-' + (a.payment_status || 'unpaid')]">{{ paymentStatusLabels[a.payment_status] || a.payment_status }}</span></td>
                  <td>{{ formatCurrency(a.payment_amount) }}</td>
                  <td>
                    <router-link :to="`/ppdb/pendaftar/${a.id}`" class="btn-action btn-edit">Detail</router-link>
                    <button v-if="a.payment_status !== 'paid'" type="button" class="btn-action btn-primary-sm" :disabled="markingId === a.id" @click="markPaid(a)">
                      {{ markingId === a.id ? '...' : 'Tandai lunas' }}
                    </button>
                    <button v-else type="button" class="btn-action btn-edit" :disabled="markingId === a.id" @click="markUnpaid(a)">Batalkan</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="pagination.last_page > 1" class="pagination-bar" style="margin-top: 1rem;">
            <span class="pagination-info">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <div class="pagination-btns">
              <button type="button" class="pagination-btn" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Sebelumnya</button>
              <button type="button" class="pagination-btn" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Selanjutnya</button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { ppdbPeriodApi, ppdbApplicantApi } from '@/api/ppdb'
import { useToast } from '@/composables/useToast'
import {
  statusPeriodLabel,
  statusApplicantLabels,
  paymentStatusLabels,
  formatCurrency,
} from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const route = useRoute()

const periods = ref([])
const periodsLoading = ref(false)
const feeForms = reactive({})
const savingFeeId = ref(null)

const applicants = ref([])
const applicantsLoading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filters = ref({
  ppdb_period_id: '',
  payment_status: route.query.payment_status ? String(route.query.payment_status) : 'unpaid',
})
const markingId = ref(null)

async function loadPeriods() {
  periodsLoading.value = true
  try {
    const res = await ppdbPeriodApi.getAll({ per_page: 100 })
    periods.value = res.data.data || []
    periods.value.forEach((p) => {
      feeForms[p.id] = {
        registration_fee: p.registration_fee ?? '',
        re_registration_fee: p.re_registration_fee ?? '',
      }
    })
  } catch (e) {
    toast.error('Gagal memuat periode', e.formattedMessage || 'Coba lagi.')
  } finally {
    periodsLoading.value = false
  }
}

async function saveFees(p) {
  savingFeeId.value = p.id
  try {
    const form = feeForms[p.id]
    await ppdbPeriodApi.update(p.id, {
      registration_fee: form.registration_fee === '' ? null : form.registration_fee,
      re_registration_fee: form.re_registration_fee === '' ? null : form.re_registration_fee,
    })
    toast.success('Biaya periode disimpan')
    await loadPeriods()
  } catch (e) {
    toast.error('Gagal menyimpan biaya', e.formattedMessage || 'Coba lagi.')
  } finally {
    savingFeeId.value = null
  }
}

async function loadApplicants() {
  applicantsLoading.value = true
  try {
    const params = {
      page: pagination.value.current_page,
      per_page: 15,
    }
    if (filters.value.ppdb_period_id) params.ppdb_period_id = filters.value.ppdb_period_id
    if (filters.value.payment_status) params.payment_status = filters.value.payment_status
    const res = await ppdbApplicantApi.getAll(params)
    applicants.value = res.data.data || []
    const meta = res.data.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      total: meta.total ?? 0,
    }
  } catch (e) {
    toast.error('Gagal memuat calon', e.formattedMessage || 'Coba lagi.')
  } finally {
    applicantsLoading.value = false
  }
}

function goPage(page) {
  pagination.value.current_page = page
  loadApplicants()
}

async function markPaid(a) {
  markingId.value = a.id
  try {
    await ppdbApplicantApi.setPayment(a.id, { payment_status: 'paid' })
    toast.success('Ditandai lunas')
    loadApplicants()
  } catch (e) {
    toast.error('Gagal update pembayaran', e.formattedMessage || 'Coba lagi.')
  } finally {
    markingId.value = null
  }
}

async function markUnpaid(a) {
  markingId.value = a.id
  try {
    await ppdbApplicantApi.setPayment(a.id, { payment_status: 'unpaid' })
    toast.success('Status dikembalikan ke belum bayar')
    loadApplicants()
  } catch (e) {
    toast.error('Gagal update pembayaran', e.formattedMessage || 'Coba lagi.')
  } finally {
    markingId.value = null
  }
}

onMounted(async () => {
  await loadPeriods()
  loadApplicants()
})
</script>

<style scoped>
.section-title { margin: 0 0 0.35rem; font-size: 1.1rem; color: #1e293b; }
.section-hint { margin: 0 0 1rem; font-size: 0.9rem; color: #64748b; }
.empty-inline { color: #64748b; }
.fee-input {
  width: 140px;
  padding: 0.45rem 0.65rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}
.pay-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
}
.pay-unpaid { background: #fee2e2; color: #b91c1c; }
.pay-pending { background: #fef3c7; color: #92400e; }
.pay-paid { background: #d1fae5; color: #065f46; }
.pay-waived { background: #e2e8f0; color: #475569; }
a.btn-action { text-decoration: none; display: inline-block; }
</style>
