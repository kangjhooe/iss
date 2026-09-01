<template>
  <div class="tab-content dashboard-panel">
    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="card" :lines="4" />
    </div>

    <template v-else>
      <div v-if="quickActions.length" class="dashboard-quick dashboard-quick-top">
        <h3 class="dashboard-quick-heading">Aksi Cepat</h3>
        <div class="dashboard-quick-grid">
          <button
            v-for="action in quickActions"
            :key="action.id"
            type="button"
            :class="['dashboard-quick-chip', `quick-${action.tab}`, { primary: action.primary }]"
            :title="action.desc"
            @click="$emit('navigate', action.tab)"
          >
            <span class="dashboard-quick-icon" aria-hidden="true" v-html="action.icon" />
            <span class="dashboard-quick-label">{{ action.label }}</span>
          </button>
        </div>
      </div>

      <div class="dashboard-header dashboard-header-compact">
        <h2 class="dashboard-title">Ringkasan Inventaris</h2>
      </div>

      <div class="dashboard-stats">
        <article
          v-for="(kpi, index) in kpiCards"
          :key="kpi.id"
          :class="['dashboard-stat-card', kpi.tone]"
          :style="{ animationDelay: `${index * 45}ms` }"
        >
          <div class="dashboard-stat-icon" aria-hidden="true" v-html="kpi.icon" />
          <div class="dashboard-stat-body">
            <span class="dashboard-stat-label">{{ kpi.label }}</span>
            <strong class="dashboard-stat-value" :class="{ 'is-currency': kpi.isCurrency }">{{ kpi.value }}</strong>
            <span v-if="kpi.hint" class="dashboard-stat-hint">{{ kpi.hint }}</span>
          </div>
        </article>
      </div>

      <div v-if="hasFeedPanels" class="dashboard-feed">
        <div class="dashboard-feed-grid">
          <section v-if="showLoanFeed" class="dashboard-feed-card">
            <div class="dashboard-feed-head">
              <h3 class="dashboard-feed-title">Peminjaman</h3>
              <button type="button" class="dashboard-feed-link" @click="$emit('navigate', 'loans')">Lihat semua</button>
            </div>
            <div v-if="feedLoading" class="dashboard-feed-empty muted">Memuat...</div>
            <template v-else>
              <div v-if="overdueLoans.length" class="dashboard-feed-block dashboard-feed-block-warn">
                <div class="dashboard-feed-block-label">Terlambat ({{ overdueLoans.length }})</div>
                <ul class="dashboard-feed-list">
                  <li v-for="loan in overdueLoans" :key="'od-' + loan.id">
                    <button type="button" class="dashboard-feed-row" @click="$emit('navigate', 'loans')">
                      <span class="dashboard-feed-row-main">{{ loan.item_name }}</span>
                      <span class="dashboard-feed-row-meta">Jatuh tempo {{ formatDate(loan.expected_return_date) }}</span>
                    </button>
                  </li>
                </ul>
              </div>
              <div v-if="dueSoonLoans.length" class="dashboard-feed-block dashboard-feed-block-soon">
                <div class="dashboard-feed-block-label">Jatuh tempo 7 hari ({{ dueSoonLoans.length }})</div>
                <ul class="dashboard-feed-list">
                  <li v-for="loan in dueSoonLoans" :key="'ds-' + loan.id">
                    <button type="button" class="dashboard-feed-row" @click="$emit('navigate', 'loans')">
                      <span class="dashboard-feed-row-main">{{ loan.item_name }}</span>
                      <span class="dashboard-feed-row-meta">{{ loan.borrower_name }} · {{ formatDate(loan.expected_return_date) }}</span>
                    </button>
                  </li>
                </ul>
              </div>
              <p v-if="!overdueLoans.length && !dueSoonLoans.length" class="dashboard-feed-empty muted">Tidak ada peminjaman terlambat atau jatuh tempo minggu ini.</p>
            </template>
          </section>

          <section v-if="canShowOpnameFeed" class="dashboard-feed-card">
            <div class="dashboard-feed-head">
              <h3 class="dashboard-feed-title">Opname Berjalan</h3>
              <button type="button" class="dashboard-feed-link" @click="$emit('navigate', 'opname')">Lihat semua</button>
            </div>
            <div v-if="feedLoading" class="dashboard-feed-empty muted">Memuat...</div>
            <ul v-else-if="activeOpnames.length" class="dashboard-feed-list">
              <li v-for="row in activeOpnames" :key="row.id">
                <button type="button" class="dashboard-feed-row" @click="$emit('navigate', 'opname')">
                  <span class="dashboard-feed-row-main">{{ row.opname_number }}</span>
                  <span class="dashboard-feed-row-meta">
                    {{ opnameTypeLabel(row.opname_type) }} · {{ row.room?.name || 'Semua ruangan' }}
                    · {{ row.counted_lines ?? 0 }}/{{ row.lines_count ?? 0 }}
                  </span>
                </button>
              </li>
            </ul>
            <p v-else class="dashboard-feed-empty muted">Tidak ada sesi opname draft atau berlangsung.</p>
          </section>

          <section v-if="topCategories.length" class="dashboard-feed-card">
            <div class="dashboard-feed-head">
              <h3 class="dashboard-feed-title">Top Kategori</h3>
            </div>
            <ul class="dashboard-category-list">
              <li v-for="row in topCategories" :key="row.label">
                <div class="dashboard-category-row">
                  <span class="dashboard-category-name">{{ row.label }}</span>
                  <span class="dashboard-category-meta">{{ row.count }} item · {{ row.quantity }} unit</span>
                </div>
                <div class="dashboard-category-bar" aria-hidden="true">
                  <span class="dashboard-category-bar-fill" :style="{ width: row.percent + '%' }" />
                </div>
              </li>
            </ul>
          </section>

          <section v-if="canShowTransactionFeed" class="dashboard-feed-card">
            <div class="dashboard-feed-head">
              <h3 class="dashboard-feed-title">Aktivitas Terbaru</h3>
              <button type="button" class="dashboard-feed-link" @click="$emit('navigate', 'stock')">Lihat semua</button>
            </div>
            <div v-if="feedLoading" class="dashboard-feed-empty muted">Memuat...</div>
            <ul v-else-if="recentTransactions.length" class="dashboard-feed-list">
              <li v-for="tx in recentTransactions" :key="tx.id">
                <button type="button" class="dashboard-feed-row" @click="$emit('navigate', 'stock')">
                  <span class="dashboard-feed-row-main">
                    <span :class="['dashboard-tx-badge', transactionBadgeClass(tx.type)]">{{ tx.type }}</span>
                    {{ tx.item_name }}
                  </span>
                  <span class="dashboard-feed-row-meta">
                    {{ formatDate(tx.date) }} · {{ tx.quantity }} unit
                    <span v-if="tx.reference_number"> · {{ tx.reference_number }}</span>
                  </span>
                </button>
              </li>
            </ul>
            <p v-else class="dashboard-feed-empty muted">Belum ada transaksi stok tercatat.</p>
          </section>
        </div>
      </div>

      <div class="dashboard-split">
        <div class="dashboard-card">
          <h3 class="dashboard-card-title">Status Barang</h3>
          <table class="data-table small dashboard-mini-table">
            <thead>
              <tr><th class="col-no">No</th><th>Status</th><th>Item</th><th>Unit</th></tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in statusRows" :key="'st-' + row.label">
                <td class="col-no">{{ index + 1 }}</td>
                <td>{{ row.label }}</td>
                <td>{{ row.count }}</td>
                <td>{{ row.quantity }}</td>
              </tr>
              <tr v-if="!statusRows.length">
                <td colspan="4" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="dashboard-card">
          <h3 class="dashboard-card-title">Kondisi Barang</h3>
          <table class="data-table small dashboard-mini-table">
            <thead>
              <tr><th class="col-no">No</th><th>Kondisi</th><th>Item</th><th>Unit</th></tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in conditionRows" :key="'cd-' + row.label">
                <td class="col-no">{{ index + 1 }}</td>
                <td>{{ row.label }}</td>
                <td>{{ row.count }}</td>
                <td>{{ row.quantity }}</td>
              </tr>
              <tr v-if="!conditionRows.length">
                <td colspan="4" class="empty-cell">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="alertItems.length" class="dashboard-alerts">
        <h3 class="dashboard-card-title">Perlu Perhatian</h3>
        <div class="dashboard-alert-list">
          <button
            v-for="item in alertItems"
            :key="item.id"
            type="button"
            class="dashboard-alert-item"
            @click="$emit('navigate', item.tab)"
          >
            <span class="dashboard-alert-count">{{ item.count }}</span>
            <span class="dashboard-alert-text">
              <strong>{{ item.label }}</strong>
              <span class="muted">{{ item.hint }}</span>
            </span>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { inventoryApi } from '@/api/inventory'
import { formatCurrency, formatDate, getTransactionTypeClass } from '@/composables/inventory/inventoryFormatters'
import {
  INVENTORY_QUICK_ACTION_ITEMS,
  canAccessInventoryTab,
} from '@/composables/inventory/inventoryRoutes'
import { useInventoryFacilityOptions } from '@/composables/inventory/useInventoryFacilityOptions'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  stats: { type: Object, default: null },
  inRepairCount: { type: Number, default: 0 }
})

defineEmits(['navigate'])

const toast = useToast()
const authStore = useAuthStore()
const { rooms, ensureFacilityOptions } = useInventoryFacilityOptions()

const loading = ref(false)
const feedLoading = ref(false)
const localStats = ref(null)
const localInRepair = ref(0)
const activeLoans = ref([])
const activeOpnames = ref([])
const recentTransactions = ref([])

const FEED_LIMIT = 5
const ACTIVE_OPNAME_STATUSES = new Set(['draft', 'in_progress'])

const canShowOpnameFeed = computed(() => canAccessInventoryTab(authStore.user, 'opname'))
const canShowTransactionFeed = computed(() => canAccessInventoryTab(authStore.user, 'stock'))
const showLoanFeed = computed(() => canAccessInventoryTab(authStore.user, 'loans'))

function startOfDay(value) {
  const date = value instanceof Date ? new Date(value) : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  date.setHours(0, 0, 0, 0)
  return date
}

function isDueThisWeek(dateStr) {
  const due = startOfDay(dateStr)
  if (!due) return false
  const today = startOfDay(new Date())
  const end = new Date(today)
  end.setDate(end.getDate() + 7)
  return due >= today && due <= end
}

function isLoanOverdue(loan) {
  return loan?.is_overdue === true || loan?.status === 'Terlambat'
}

const overdueLoans = computed(() =>
  activeLoans.value.filter(isLoanOverdue).slice(0, FEED_LIMIT)
)

const dueSoonLoans = computed(() =>
  activeLoans.value
    .filter((loan) => !isLoanOverdue(loan) && isDueThisWeek(loan.expected_return_date))
    .slice(0, FEED_LIMIT)
)

const topCategories = computed(() => {
  const source = stats.value?.by_category
  if (!source || typeof source !== 'object') return []

  const rows = Object.entries(source).map(([label, row]) => ({
    label,
    count: row?.count ?? 0,
    quantity: row?.quantity ?? 0,
  }))
  rows.sort((a, b) => b.quantity - a.quantity || b.count - a.count)
  const top = rows.slice(0, FEED_LIMIT)
  const maxQuantity = top[0]?.quantity || 1

  return top.map((row) => ({
    ...row,
    percent: Math.max(8, Math.round((row.quantity / maxQuantity) * 100)),
  }))
})

const hasFeedPanels = computed(() =>
  showLoanFeed.value
  || canShowOpnameFeed.value
  || topCategories.value.length > 0
  || canShowTransactionFeed.value
)

function opnameTypeLabel(type) {
  return type === 'asset' ? 'Aset' : 'Stok'
}

function transactionBadgeClass(type) {
  return getTransactionTypeClass(type).replace('badge-', 'is-')
}

function unwrapList(payload) {
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload)) return payload
  return []
}

async function loadFeedPanels() {
  feedLoading.value = true
  try {
    const tasks = []

    if (showLoanFeed.value) {
      tasks.push(
        inventoryApi.getReportLoaned({}).then((res) => {
          const data = res.data?.data ?? res.data ?? {}
          activeLoans.value = data.loans || []
        })
      )
    } else {
      activeLoans.value = []
    }

    if (canShowTransactionFeed.value) {
      tasks.push(
        inventoryApi.getReportTransactions({}).then((res) => {
          const data = res.data?.data ?? res.data ?? {}
          recentTransactions.value = (data.transactions || []).slice(0, FEED_LIMIT)
        })
      )
    } else {
      recentTransactions.value = []
    }

    if (canShowOpnameFeed.value) {
      tasks.push(
        inventoryApi.getStockOpnames({ per_page: 20 }).then((res) => {
          activeOpnames.value = unwrapList(res.data)
            .filter((row) => ACTIVE_OPNAME_STATUSES.has(row.status))
            .slice(0, FEED_LIMIT)
        })
      )
    } else {
      activeOpnames.value = []
    }

    await Promise.all(tasks)
  } catch {
    activeLoans.value = []
    recentTransactions.value = []
    activeOpnames.value = []
  } finally {
    feedLoading.value = false
  }
}

const stats = computed(() => props.stats ?? localStats.value)
const inRepairCount = computed(() => props.inRepairCount || localInRepair.value)

function mapStatRows(source) {
  if (!source || typeof source !== 'object') return []
  return Object.entries(source).map(([label, row]) => ({
    label,
    count: row?.count ?? 0,
    quantity: row?.quantity ?? 0
  }))
}

const statusRows = computed(() => mapStatRows(stats.value?.by_status))
const conditionRows = computed(() => mapStatRows(stats.value?.by_condition))

const KPI_ICONS = {
  items: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  assets: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 9h4M7 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  value: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/><path d="M12 8v8M9.5 10.5h4a2 2 0 1 1 0 4h-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  rooms: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 10.5L12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
  repair: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 7l3 3-7 7H7v-3l7-7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
}

const kpiCards = computed(() => {
  const cards = [
    {
      id: 'items',
      tone: 'tone-emerald',
      label: 'Total Barang',
      value: stats.value?.total_items ?? 0,
      hint: `${stats.value?.total_quantity ?? 0} unit`,
      icon: KPI_ICONS.items,
    },
  ]

  const assetCount = stats.value?.individual_asset_count ?? 0
  if (assetCount > 0) {
    cards.push({
      id: 'assets',
      tone: 'tone-violet',
      label: 'Aset Individual',
      value: assetCount,
      hint: 'unit tercatat',
      icon: KPI_ICONS.assets,
    })
  }

  cards.push(
    {
      id: 'value',
      tone: 'tone-sky',
      label: 'Estimasi Nilai',
      value: formatCurrency(stats.value?.total_value || 0),
      hint: 'nilai buku inventaris',
      icon: KPI_ICONS.value,
      isCurrency: true,
    },
    {
      id: 'rooms',
      tone: 'tone-blue',
      label: 'Jumlah Ruangan',
      value: rooms.value.length,
      hint: 'lokasi terdaftar',
      icon: KPI_ICONS.rooms,
    },
    {
      id: 'repair',
      tone: inRepairCount.value > 0 ? 'tone-amber' : 'tone-slate',
      label: 'Dalam Perbaikan',
      value: inRepairCount.value,
      hint: inRepairCount.value > 0 ? 'pemeliharaan aktif' : 'tidak ada antrean',
      icon: KPI_ICONS.repair,
    },
  )

  return cards
})

const alertItems = computed(() => {
  const items = []
  const warranty = stats.value?.warranty_expiring_soon ?? 0
  if (warranty > 0) {
    items.push({
      id: 'warranty',
      count: warranty,
      label: 'Garansi segera habis',
      hint: 'Berlaku kurang dari 3 bulan',
      tab: 'items'
    })
  }
  if (inRepairCount.value > 0) {
    items.push({
      id: 'repair',
      count: inRepairCount.value,
      label: 'Dalam perbaikan',
      hint: 'Pemeliharaan belum selesai',
      tab: 'maintenances'
    })
  }
  const damaged = (stats.value?.by_status?.Rusak?.count ?? 0) + (stats.value?.by_status?.Hilang?.count ?? 0)
  if (damaged > 0) {
    items.push({
      id: 'damaged',
      count: damaged,
      label: 'Rusak / hilang',
      hint: 'Catat penghapusan administratif (SK/BA)',
      tab: 'disposal'
    })
  }
  const loaned = stats.value?.by_status?.Dipinjam?.count ?? 0
  if (loaned > 0) {
    items.push({
      id: 'loaned',
      count: loaned,
      label: 'Sedang dipinjam',
      hint: 'Peminjaman aktif',
      tab: 'loans'
    })
  }
  const overdue = activeLoans.value.filter(isLoanOverdue).length
  if (overdue > 0) {
    items.push({
      id: 'loan-overdue',
      count: overdue,
      label: 'Peminjaman terlambat',
      hint: 'Lewat jatuh tempo',
      tab: 'loans'
    })
  }
  const dueSoon = activeLoans.value.filter((loan) => !isLoanOverdue(loan) && isDueThisWeek(loan.expected_return_date)).length
  if (dueSoon > 0) {
    items.push({
      id: 'loan-due-soon',
      count: dueSoon,
      label: 'Jatuh tempo minggu ini',
      hint: 'Perlu diingatkan',
      tab: 'loans'
    })
  }
  return items
})

const QUICK_ACTION_META = {
  rooms: {
    desc: 'Lihat barang per ruangan',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 10.5L12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
  },
  transfer: {
    desc: 'Pindah lokasi barang',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  },
  opname: {
    desc: 'Hitung fisik stok & aset',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  },
  stock: {
    desc: 'Catat barang masuk & keluar',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 4v16M5 11l7-7 7 7M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  },
  disposal: {
    desc: 'Hapus barang dari buku',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M9 7V5h6v2M6 7l1 12h10l1-12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  },
  categories: {
    desc: 'Kelola kategori barang',
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 7h7l2 2h7v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
  },
  scan: {
    desc: 'Cek unit di lapangan',
    primary: true,
    icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h3v3h-3zM19 14h1v1h-1zM14 19h1v1h-1zM19 19h1v1h-1z" stroke="currentColor" stroke-width="2"/></svg>',
  },
}

const quickActions = computed(() =>
  INVENTORY_QUICK_ACTION_ITEMS
    .filter((item) => canAccessInventoryTab(authStore.user, item.tab))
    .map((item) => {
      const meta = QUICK_ACTION_META[item.tab] || {}
      return {
        id: item.tab,
        tab: item.tab,
        label: item.label,
        desc: meta.desc || '',
        primary: meta.primary === true,
        icon: meta.icon || '',
      }
    })
)

async function loadDashboard() {
  const needsFetch = !props.stats
  if (needsFetch) loading.value = true
  try {
    await ensureFacilityOptions()
    if (needsFetch) {
      const [statsRes, maintRes] = await Promise.all([
        inventoryApi.getReportStatistics({}),
        inventoryApi.getReportMaintenance({})
      ])
      localStats.value = statsRes.data?.data ?? statsRes.data ?? null
      const maintData = maintRes.data?.data ?? maintRes.data ?? {}
      const list = maintData.maintenances || []
      localInRepair.value = list.filter((m) =>
        ['Terjadwal', 'Dalam Proses'].includes(m.status)
      ).length
    }
    await loadFeedPanels()
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat ringkasan inventaris')
    localStats.value = null
    localInRepair.value = 0
    activeLoans.value = []
    recentTransactions.value = []
    activeOpnames.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)

defineExpose({ refresh: loadDashboard })
</script>
