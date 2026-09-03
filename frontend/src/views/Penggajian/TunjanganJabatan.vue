<template>
  <div class="keuangan-page penggajian-page">
    <header class="page-header">
      <div class="header-content">
        <div class="header-left">
          <div>
            <h1 class="page-title">Tunjangan Jabatan</h1>
            <p class="page-subtitle">Nominal tunjangan per jabatan struktural — otomatis dihitung saat proses gaji</p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn-header-primary" :disabled="saving" @click="save">
            {{ saving ? 'Menyimpan...' : 'Simpan perubahan' }}
          </button>
        </div>
      </div>
    </header>

    <main class="page-main">
      <div v-if="error" class="error-banner">{{ error }}</div>
      <div v-if="success" class="success-banner">{{ success }}</div>

      <div class="content-card" style="margin-bottom:1rem;padding:0.85rem 1rem;font-size:0.9rem">
        Tunjangan dihitung dari jabatan struktural aktif pegawai pada periode gaji.
        Atur nominal per jabatan di bawah; pegawai dengan beberapa jabatan mendapat penjumlahan tunjangan.
      </div>

      <div class="content-card">
        <div v-if="loading" class="loading-wrap"><LoadingSkeleton type="table" :rows="6" :columns="4" /></div>
        <div v-else-if="!items.length" class="empty-state">
          <h3>Belum ada jabatan struktural</h3>
          <p>Atur jabatan struktural di modul Kepegawaian terlebih dahulu.</p>
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Jabatan</th>
                <th>Kode</th>
                <th class="num">Nominal / bulan</th>
                <th>Aktif</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in items" :key="row.structural_position_id">
                <td><strong>{{ row.label }}</strong></td>
                <td class="muted">{{ row.key }}</td>
                <td class="num">
                  <MoneyInput
                    v-model="row.amount"
                    :min="0"
                    class="form-input"
                    style="max-width:160px;margin-left:auto"
                    :disabled="!row.is_active"
                  />
                </td>
                <td>
                  <input v-model="row.is_active" type="checkbox" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import MoneyInput from '@/components/MoneyInput.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { payrollPositionAllowanceApi } from '@/api/payroll'
import { apiError } from './penggajianConstants'
import './penggajian.css'

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await payrollPositionAllowanceApi.getAll()
    items.value = (res.data?.data || []).map((row) => ({ ...row }))
  } catch (e) {
    error.value = apiError(e, 'Gagal memuat tunjangan jabatan.')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const payload = items.value.map((row) => ({
      structural_position_id: row.structural_position_id,
      amount: Number(row.amount || 0),
      is_active: !!row.is_active,
    }))
    const res = await payrollPositionAllowanceApi.sync(payload)
    items.value = (res.data?.data || []).map((row) => ({ ...row }))
    success.value = res.data?.message || 'Tunjangan jabatan disimpan.'
  } catch (e) {
    error.value = apiError(e, 'Gagal menyimpan.')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
