<template>
    <div class="inventory-page">
      <div class="inventory-content">
        <InventoryDashboard
          v-if="activeTab === 'dashboard'"
          ref="dashboardRef"
          :stats="stats"
          :in-repair-count="inRepairCount"
          @navigate="onDashboardNavigate"
        />

        <InventoryItemList
          v-else-if="activeTab === 'items'"
          ref="itemListRef"
          :categories="categories"
          :buildings="buildings"
          :rooms="rooms"
          @view-detail="openItemDetail"
          @transfer="onTransferItem"
        />

        <InventoryAssetList
          v-else-if="activeTab === 'assets'"
          ref="assetListRef"
          :rooms="rooms"
        />

        <InventoryRoomPanel
          v-else-if="activeTab === 'rooms'"
          @view-detail="openItemDetail"
        />

        <InventoryTransferPanel
          v-else-if="activeTab === 'transfer'"
          ref="transferRef"
          :rooms="rooms"
          :preselected-item="transferPreselectedItem"
        />

        <InventoryOpnamePanel
          v-else-if="activeTab === 'opname'"
          ref="opnameRef"
          :rooms="rooms"
        />

        <InventoryStockPanel
          v-else-if="activeTab === 'stock'"
        />

        <InventoryMaintenanceList
          v-else-if="activeTab === 'maintenances'"
        />

        <InventoryLoanList
          v-else-if="activeTab === 'loans'"
        />

        <InventoryDisposalPanel
          v-else-if="activeTab === 'disposal'"
          ref="disposalRef"
          @view-detail="openItemDetail"
          @changed="loadSummaryStats"
        />

        <InventoryReportPanel
          v-else-if="activeTab === 'reports'"
          :buildings="buildings"
          :rooms="rooms"
        />

        <InventoryCategorySettings
          v-else-if="activeTab === 'categories'"
        />
      </div>

      <ItemDetailPanel
        v-model="detailVisible"
        :item-id="detailItemId"
      />

      <ConfirmDialog
        :show="confirmDialog.show"
        :title="confirmDialog.title"
        :message="confirmDialog.message"
        :warning="confirmDialog.warning"
        :loading="confirmDialog.loading"
        @confirm="handleConfirm"
        @cancel="handleCancel"
        @update:show="confirmDialog.show = $event"
      />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import InventoryDashboard from '@/components/inventory/InventoryDashboard.vue'
import InventoryItemList from '@/components/inventory/InventoryItemList.vue'
import InventoryAssetList from '@/components/inventory/InventoryAssetList.vue'
import ItemDetailPanel from '@/components/inventory/ItemDetailPanel.vue'
import InventoryRoomPanel from '@/components/inventory/InventoryRoomPanel.vue'
import InventoryTransferPanel from '@/components/inventory/InventoryTransferPanel.vue'
import InventoryStockPanel from '@/components/inventory/InventoryStockPanel.vue'
import InventoryMaintenanceList from '@/components/inventory/InventoryMaintenanceList.vue'
import InventoryLoanList from '@/components/inventory/InventoryLoanList.vue'
import InventoryDisposalPanel from '@/components/inventory/InventoryDisposalPanel.vue'
import InventoryOpnamePanel from '@/components/inventory/InventoryOpnamePanel.vue'
import InventoryReportPanel from '@/components/inventory/InventoryReportPanel.vue'
import InventoryCategorySettings from '@/components/inventory/InventoryCategorySettings.vue'
import { inventoryApi } from '@/api/inventory'
import { useInventoryCategories } from '@/composables/inventory/useInventoryCategories'
import { useInventoryFacilityOptions } from '@/composables/inventory/useInventoryFacilityOptions'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import {
  INVENTORY_TAB_BY_ROUTE_NAME,
  inventoryPathForTab,
} from '@/composables/inventory/inventoryRoutes'
import '@/assets/inventory-page.css'

const { confirmDialog, handleConfirm, handleCancel } = useConfirmDelete()

const { categories, load: loadCategories } = useInventoryCategories()
const { buildings, rooms, ensureFacilityOptions } = useInventoryFacilityOptions()
const route = useRoute()
const router = useRouter()

const stats = ref(null)
const inRepairCount = ref(0)

const detailVisible = ref(false)
const detailItemId = ref(null)

const dashboardRef = ref(null)
const itemListRef = ref(null)
const transferRef = ref(null)

const activeTab = computed(() => INVENTORY_TAB_BY_ROUTE_NAME[route.name] || 'dashboard')

const transferPreselectedItem = computed(() => {
  const raw = route.query.item_id
  const id = raw ? Number(raw) : null
  if (!id || Number.isNaN(id)) return null
  return { id }
})

async function loadSummaryStats() {
  try {
    const [statsRes, maintRes] = await Promise.all([
      inventoryApi.getReportStatistics({}),
      inventoryApi.getReportMaintenance({})
    ])
    stats.value = statsRes.data?.data ?? statsRes.data ?? null
    const maintData = maintRes.data?.data ?? maintRes.data ?? {}
    const list = maintData.maintenances || []
    inRepairCount.value = list.filter((m) =>
      ['Terjadwal', 'Dalam Proses'].includes(m.status)
    ).length
  } catch {
    stats.value = null
    inRepairCount.value = 0
  }
}

function onDashboardNavigate(tab) {
  if (tab === 'scan') {
    router.push('/inventory/scan')
    return
  }
  router.push(inventoryPathForTab(tab))
}

function openItemDetail(item) {
  detailItemId.value = item?.id ?? item?.item_id ?? item
  detailVisible.value = true
}

function onTransferItem(item) {
  const id = item?.id
  router.push(inventoryPathForTab('transfer', id ? { item_id: id } : {}))
}

async function handleTabActivated(tab) {
  if (tab === 'items' || tab === 'reports' || tab === 'assets') {
    await ensureFacilityOptions()
    await loadCategories(1)
  }
  if (tab === 'dashboard') {
    await loadSummaryStats()
    dashboardRef.value?.refresh?.()
  }
}

watch(activeTab, (tab) => {
  handleTabActivated(tab)
}, { immediate: true })

onMounted(async () => {
  await Promise.all([ensureFacilityOptions(), loadCategories(1), loadSummaryStats()])
})
</script>
