<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <select v-model="transactionFilters.transaction_type" class="filter-select" @change="loadTransactions(1)">
          <option value="">Semua Jenis</option>
          <option value="Masuk">Masuk</option>
          <option value="Keluar">Keluar</option>
        </select>
        <input v-model="transactionFilters.date_from" type="date" class="filter-select" @change="loadTransactions(1)" />
        <input v-model="transactionFilters.date_to" type="date" class="filter-select" @change="loadTransactions(1)" />
      </div>
    </div>

    <div v-if="transactionsLoading" class="loading-state"><p>Memuat data...</p></div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Tanggal</th>
            <th>Jenis</th>
            <th>Barang</th>
            <th>Qty</th>
            <th>Lokasi</th>
            <th>Referensi</th>
            <th>Petugas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(tr, index) in transactions" :key="tr.id">
            <td class="col-no">{{ inventoryRowNumber(transactionsMeta, index) }}</td>
            <td>{{ formatDate(tr.transaction_date) }}</td>
            <td><span :class="getTransactionTypeClass(tr.transaction_type)">{{ tr.transaction_type }}</span></td>
            <td>
              <div class="name-cell">
                <div class="name">{{ tr.item?.name || '-' }}</div>
                <div class="muted small">{{ tr.item?.code || '-' }}</div>
              </div>
            </td>
            <td>{{ tr.quantity }}</td>
            <td class="muted">
              <span v-if="tr.from_location?.name">Dari: {{ tr.from_location.name }}</span>
              <span v-if="tr.to_location?.name" :style="{ marginLeft: tr.from_location?.name ? '8px' : '0' }">
                → Ke: {{ tr.to_location.name }}
              </span>
              <span v-if="!tr.from_location && !tr.to_location">-</span>
            </td>
            <td class="muted">{{ tr.reference_number || '-' }}</td>
            <td class="muted">{{ tr.creator?.name || '-' }}</td>
          </tr>
        </tbody>
      </table>

      <div v-if="transactions.length === 0" class="empty-state">
        <h3>Belum ada transaksi masuk/keluar</h3>
        <p>Riwayat barang masuk dan keluar akan muncul di sini.</p>
      </div>

      <PaginationBar
        embedded
        :page="transactionsMeta.current_page"
        :last-page="transactionsMeta.last_page"
        :per-page="transactionsMeta.per_page"
        :total="transactionsMeta.total"
        item-label="data"
        @page-change="loadTransactions"
        @per-page-change="changeTransactionsPerPage"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { inventoryApi } from '@/api/inventory'
import { formatDate, getTransactionTypeClass } from '@/composables/inventory/inventoryFormatters'
import { safeArray, parsePagination, inventoryRowNumber } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const transactions = ref([])
const transactionsLoading = ref(false)
const transactionsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const transactionFilters = ref({ transaction_type: '', date_from: '', date_to: '' })

async function loadTransactions(page = 1) {
  transactionsLoading.value = true
  try {
    const params = { page, per_page: transactionsMeta.value.per_page }
    if (transactionFilters.value.transaction_type) {
      params.transaction_type = transactionFilters.value.transaction_type
    }
    if (transactionFilters.value.date_from) params.date_from = transactionFilters.value.date_from
    if (transactionFilters.value.date_to) params.date_to = transactionFilters.value.date_to

    const res = await inventoryApi.getTransactions(params)
    const list = safeArray(res).filter((tr) =>
      transactionFilters.value.transaction_type
        ? tr.transaction_type === transactionFilters.value.transaction_type
        : ['Masuk', 'Keluar'].includes(tr.transaction_type)
    )
    transactions.value = list
    transactionsMeta.value = parsePagination(res, transactionsMeta.value)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat transaksi')
    transactions.value = []
  } finally {
    transactionsLoading.value = false
  }
}

function changeTransactionsPerPage(n) {
  transactionsMeta.value.per_page = n
  transactionsMeta.value.current_page = 1
  loadTransactions(1)
}

onMounted(() => loadTransactions(1))
</script>
