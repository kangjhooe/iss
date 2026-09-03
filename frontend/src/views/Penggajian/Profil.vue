<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Profil Gaji Pegawai</h1>
            <p class="page-subtitle">Gaji pokok, rekening, dan override komponen per pegawai</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-header-primary" @click="openModal()">+ Atur gaji pegawai</button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card filters-bar">
        <div class="filter-field" style="flex:1;min-width:180px">
          <label class="filter-label">Cari</label>
          <input v-model="filters.search" class="filter-input" placeholder="Nama / NIP" @keyup.enter="reloadFromStart" />
        </div>
        <button type="button" class="btn-secondary" @click="reloadFromStart">Terapkan</button>
      </div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="5" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada profil gaji</h3>
          <p>Atur gaji pokok pegawai sebelum memproses penggajian bulanan.</p>
          <button type="button" class="btn-primary" @click="openModal()">Atur gaji pegawai</button>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Pegawai</th>
                <th>NIP</th>
                <th>Jenis</th>
                <th class="num">Gaji Pokok</th>
                <th>Rekening</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td><strong>{{ row.employee?.name }}</strong></td>
                <td>{{ row.employee?.nip || '—' }}</td>
                <td>{{ row.employee?.type || '—' }}</td>
                <td class="num">{{ formatRp(row.base_salary) }}</td>
                <td>
                  <span v-if="row.bank_name">{{ row.bank_name }} — {{ row.bank_account }}</span>
                  <span v-else class="muted">—</span>
                </td>
                <td><TableAction kind="edit" @click="openModal(row)" /></td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            :page="meta.current_page"
            :last-page="meta.last_page"
            :per-page="meta.per_page"
            :total="meta.total"
            item-label="profil"
            @page-change="goPage"
            @per-page-change="changePerPage"
          />
        </div>
      </div>
    </main>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card" style="max-width:640px">
        <h3>{{ editing ? 'Edit profil gaji' : 'Atur gaji pegawai' }}</h3>
        <div v-if="modalError" class="modal-error">{{ modalError }}</div>
        <form @submit.prevent="save">
          <div class="form-grid">
            <div class="form-group full" v-if="!editing">
              <label>Pegawai *</label>
              <select v-model="form.employee_id" class="form-select" required>
                <option value="">— Pilih pegawai —</option>
                <option
                  v-for="e in employees"
                  :key="e.id"
                  :value="e.id"
                  :disabled="e.has_profile"
                >
                  {{ e.name }} ({{ e.type }}){{ e.has_profile ? ' — sudah ada' : '' }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Gaji pokok *</label>
              <MoneyInput v-model="form.base_salary" :min="0" class="form-input" required />
            </div>
            <div class="form-group">
              <label>Metode bayar</label>
              <select v-model="form.payment_method" class="form-select">
                <option value="transfer">Transfer</option>
                <option value="cash">Tunai</option>
              </select>
            </div>
            <div class="form-group">
              <label>Bank</label>
              <input v-model="form.bank_name" class="form-input" placeholder="BCA, Mandiri, ..." />
            </div>
            <div class="form-group">
              <label>No. rekening</label>
              <input v-model="form.bank_account" class="form-input" />
            </div>
            <div class="form-group full">
              <label>Catatan</label>
              <textarea v-model="form.notes" class="form-textarea" rows="2" />
            </div>
          </div>

          <div v-if="componentOverrides.length" class="content-card" style="margin-top:1rem;padding:0.75rem">
            <h4 style="margin:0 0 0.75rem;font-size:0.95rem">Override komponen (opsional)</h4>
            <div v-for="(c, idx) in componentOverrides" :key="c.component_id" class="slip-line-row">
              <span>{{ c.name }} <small class="muted">({{ typeLabel(c.type) }})</small></span>
              <MoneyInput v-model="c.amount" :min="0" class="form-input" placeholder="default" />
              <label><input v-model="c.is_active" type="checkbox" /></label>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="showModal = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import MoneyInput from '@/components/MoneyInput.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { payrollComponentApi, payrollProfileApi } from '@/api/payroll'
import { formatRp, typeLabel, apiError } from './penggajianConstants'
import './penggajian.css'

const items = ref([])
const employees = ref([])
const components = ref([])
const componentOverrides = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const editing = ref(null)
const meta = reactive({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const filters = reactive({ search: '', page: 1 })
const form = reactive({
  employee_id: '',
  base_salary: 0,
  payment_method: 'transfer',
  bank_name: '',
  bank_account: '',
  notes: '',
})

async function loadComponents() {
  try {
    const res = await payrollComponentApi.getAll({ active_only: 1 })
    components.value = res.data?.data || res.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat komponen gaji.')
  }
}

async function loadEmployees() {
  try {
    const res = await payrollProfileApi.employeesLite()
    employees.value = res.data?.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat daftar pegawai.')
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = { per_page: meta.per_page, page: filters.page }
    if (filters.search) params.search = filters.search
    const res = await payrollProfileApi.getAll(params)
    items.value = res.data?.data || []
    const m = res.data?.meta || {}
    meta.current_page = m.current_page || 1
    meta.last_page = m.last_page || 1
    meta.per_page = m.per_page ?? meta.per_page
    meta.total = m.total ?? 0
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat profil gaji.')
  } finally {
    loading.value = false
  }
}

function buildOverrides(existing = []) {
  const map = new Map((existing || []).map((c) => [c.component_id, c]))
  componentOverrides.value = components.value.map((comp) => {
    const ex = map.get(comp.id)
    return {
      component_id: comp.id,
      name: comp.name,
      type: comp.type,
      amount: ex?.amount ?? null,
      is_active: ex?.is_active ?? true,
    }
  })
}

function openModal(row = null) {
  editing.value = row
  modalError.value = ''
  if (row) {
    Object.assign(form, {
      employee_id: row.employee_id,
      base_salary: row.base_salary,
      payment_method: row.payment_method || 'transfer',
      bank_name: row.bank_name || '',
      bank_account: row.bank_account || '',
      notes: row.notes || '',
    })
    buildOverrides(row.components)
  } else {
    Object.assign(form, {
      employee_id: '',
      base_salary: 0,
      payment_method: 'transfer',
      bank_name: '',
      bank_account: '',
      notes: '',
    })
    buildOverrides()
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  modalError.value = ''
  try {
    const payload = {
      ...form,
      employee_id: Number(form.employee_id || editing.value?.employee_id),
      components: componentOverrides.value
        .filter((c) => c.amount !== null && c.amount !== '' || !c.is_active)
        .map((c) => ({
          component_id: c.component_id,
          amount: c.amount === '' || c.amount === null ? null : Number(c.amount),
          is_active: c.is_active,
        })),
    }
    if (editing.value) {
      await payrollProfileApi.update(editing.value.id, payload)
      success.value = 'Profil gaji diperbarui.'
    } else {
      await payrollProfileApi.create(payload)
      success.value = 'Profil gaji disimpan.'
    }
    showModal.value = false
    await load()
    await loadEmployees()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

function goPage(page) {
  filters.page = page
  load()
}

function changePerPage(n) {
  meta.per_page = n
  filters.page = 1
  load()
}

function reloadFromStart() {
  filters.page = 1
  load()
}

onMounted(async () => {
  await Promise.all([loadComponents(), loadEmployees()])
  await load()
})
</script>
