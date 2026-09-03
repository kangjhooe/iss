<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Komponen Gaji</h1>
            <p class="page-subtitle">Pendapatan dan potongan — otomatis atau manual per pegawai</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-header-primary" @click="openModal()">+ Tambah komponen</button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="5" :columns="6" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada komponen</h3>
          <p>Sistem akan membuat komponen bawaan saat halaman dimuat.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kode</th>
                <th>Jenis</th>
                <th>Perhitungan</th>
                <th class="num">Default</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td>
                  <strong>{{ row.name }}</strong>
                  <div v-if="row.description" class="muted" style="font-size:0.8rem">{{ row.description }}</div>
                </td>
                <td>{{ row.code }}</td>
                <td>{{ typeLabel(row.type) }}</td>
                <td>{{ calcModeLabel(row.calc_mode) }}</td>
                <td class="num">{{ formatRp(row.default_amount) }}</td>
                <td>
                  <span :class="['pill', row.is_active ? 'pill-on' : 'pill-off']">
                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <TableAction kind="edit" @click="openModal(row)" />
                  <TableAction v-if="!row.is_system" kind="delete" @click="remove(row)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal-card">
        <h3>{{ editing ? 'Edit komponen' : 'Tambah komponen' }}</h3>
        <div v-if="modalError" class="modal-error">{{ modalError }}</div>
        <form @submit.prevent="save">
          <div class="form-grid">
            <div class="form-group full">
              <label>Nama *</label>
              <input v-model="form.name" class="form-input" required maxlength="120" />
            </div>
            <div class="form-group">
              <label>Kode *</label>
              <input v-model="form.code" class="form-input" required maxlength="40" :disabled="editing?.is_system" />
            </div>
            <div class="form-group">
              <label>Jenis *</label>
              <select v-model="form.type" class="form-select" required>
                <option v-for="o in componentTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Mode perhitungan</label>
              <select v-model="form.calc_mode" class="form-select">
                <option v-for="o in calcModeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ form.calc_mode === 'thr' ? 'Pengali THR (× gaji pokok)' : 'Nominal default' }}</label>
              <MoneyInput v-model="form.default_amount" :min="0" :decimals="1" class="form-input" />
            </div>
            <div class="form-group full">
              <label>Deskripsi</label>
              <textarea v-model="form.description" class="form-textarea" />
            </div>
            <div class="form-group">
              <label><input v-model="form.is_active" type="checkbox" /> Aktif</label>
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
import { payrollComponentApi } from '@/api/payroll'
import {
  componentTypeOptions,
  calcModeOptions,
  typeLabel,
  calcModeLabel,
  formatRp,
  apiError,
} from './penggajianConstants'
import './penggajian.css'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const modalError = ref('')
const showModal = ref(false)
const editing = ref(null)
const form = reactive({
  name: '',
  code: '',
  description: '',
  type: 'earning',
  calc_mode: 'fixed',
  default_amount: 0,
  is_active: true,
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await payrollComponentApi.getAll()
    items.value = res.data?.data || res.data || []
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat komponen gaji.')
  } finally {
    loading.value = false
  }
}

function openModal(row = null) {
  editing.value = row
  modalError.value = ''
  if (row) {
    Object.assign(form, {
      name: row.name,
      code: row.code,
      description: row.description || '',
      type: row.type,
      calc_mode: row.calc_mode,
      default_amount: row.default_amount,
      is_active: row.is_active,
    })
  } else {
    Object.assign(form, {
      name: '',
      code: '',
      description: '',
      type: 'earning',
      calc_mode: 'fixed',
      default_amount: 0,
      is_active: true,
    })
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  modalError.value = ''
  try {
    const payload = { ...form }
    if (editing.value) {
      await payrollComponentApi.update(editing.value.id, payload)
      success.value = 'Komponen diperbarui.'
    } else {
      await payrollComponentApi.create(payload)
      success.value = 'Komponen ditambahkan.'
    }
    showModal.value = false
    await load()
  } catch (e) {
    modalError.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`Hapus komponen "${row.name}"?`)) return
  try {
    await payrollComponentApi.delete(row.id)
    success.value = 'Komponen dihapus.'
    await load()
  } catch (e) {
    error.value = apiError(e, 'Gagal menghapus.')
  }
}

onMounted(load)
</script>
