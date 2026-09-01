<template>
  <div class="tab-content">
    <div class="tab-header">
      <div class="filters filters-inline">
        <div class="search-wrap">
          <input
            v-model="search"
            class="search-input"
            placeholder="Cari nama ruangan..."
            @input="debounceFilter"
          />
          <button
            v-if="search"
            type="button"
            class="search-clear"
            aria-label="Hapus pencarian"
            @click="search = ''"
          >
            ×
          </button>
        </div>
        <select v-model="filterType" class="filter-select" @change="applyFilter">
          <option value="">Semua Tipe</option>
          <option v-for="t in roomTypes" :key="t" :value="t">{{ t }}</option>
        </select>
        <select v-model="filterBuilding" class="filter-select" @change="applyFilter">
          <option value="">Semua Gedung</option>
          <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>
      <router-link to="/facility" class="btn-outline btn-compact">Kelola Ruangan</router-link>
    </div>

    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="6" :columns="3" />
    </div>

    <div v-else class="room-layout">
      <div class="room-list table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th>Ruangan</th>
              <th>Tipe</th>
              <th>Gedung</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(room, index) in filteredRooms"
              :key="room.id"
              :class="{ 'row-selected': selectedRoomId === room.id }"
              class="room-row"
              @click="selectRoom(room)"
            >
              <td class="col-no">{{ index + 1 }}</td>
              <td>{{ room.name }}</td>
              <td>{{ room.type || '-' }}</td>
              <td>{{ room.building?.name || '-' }}</td>
            </tr>
            <tr v-if="!filteredRooms.length">
              <td colspan="4" class="empty-cell">Tidak ada ruangan</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="selectedRoomId" class="room-detail">
        <div v-if="detailLoading" class="loading-state"><p>Memuat detail...</p></div>
        <template v-else-if="roomDetail">
          <h3 class="report-section-title">{{ roomDetail.name }}</h3>
          <div class="detail-grid">
            <div><span class="muted">Tipe</span><div>{{ roomDetail.type || '-' }}</div></div>
            <div><span class="muted">Gedung</span><div>{{ roomDetail.building?.name || '-' }}</div></div>
            <div><span class="muted">Kapasitas</span><div>{{ roomDetail.capacity ?? '-' }}</div></div>
            <div v-if="roomDetail.description" class="detail-full">
              <span class="muted">Deskripsi</span><div>{{ roomDetail.description }}</div>
            </div>
          </div>

          <h4 class="report-section-title" style="margin-top: 16px;">Barang di Ruangan</h4>
          <div class="table-container">
            <table class="data-table small">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Kode</th>
                  <th>Nama</th>
                  <th>Qty</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(it, index) in roomItems" :key="it.id">
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>{{ it.code }}</td>
                  <td>
                    <button type="button" class="link-btn" @click="$emit('view-detail', it)">
                      {{ it.name }}
                    </button>
                  </td>
                  <td>{{ it.quantity }}</td>
                  <td><span :class="getStatusClass(it.status)">{{ it.status }}</span></td>
                </tr>
                <tr v-if="!roomItems.length">
                  <td colspan="5" class="empty-cell">Tidak ada barang</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
      <div v-else class="room-detail room-detail-empty muted">
        Pilih ruangan untuk melihat detail dan daftar barang.
      </div>
    </div>
  </div>
</template>

<script setup>
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { computed, onMounted, ref } from 'vue'
import { facilityApi } from '@/api/facility'
import { inventoryApi } from '@/api/inventory'
import { getStatusClass } from '@/composables/inventory/inventoryFormatters'
import { safeArray } from '@/composables/inventory/inventoryApiHelpers'
import { useToast } from '@/composables/useToast'

defineEmits(['view-detail'])

const toast = useToast()

const loading = ref(false)
const detailLoading = ref(false)
const allRooms = ref([])
const buildings = ref([])
const search = ref('')
const filterType = ref('')
const filterBuilding = ref('')
const selectedRoomId = ref(null)
const roomDetail = ref(null)
const roomItems = ref([])
let searchTimeout = null

const roomTypes = computed(() => {
  const set = new Set(allRooms.value.map((r) => r.type).filter(Boolean))
  return [...set].sort()
})

const filteredRooms = computed(() => {
  let list = allRooms.value
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter((r) => (r.name || '').toLowerCase().includes(q))
  if (filterType.value) list = list.filter((r) => r.type === filterType.value)
  if (filterBuilding.value) list = list.filter((r) => String(r.building_id || r.building?.id) === String(filterBuilding.value))
  return list
})

function debounceFilter() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {}, 300)
}

function applyFilter() {
  // filtered via computed
}

async function loadRooms() {
  loading.value = true
  try {
    const [roomsRes, buildingsRes] = await Promise.all([
      facilityApi.getRooms({}),
      facilityApi.getBuildings({})
    ])
    allRooms.value = safeArray(roomsRes)
    buildings.value = safeArray(buildingsRes)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat ruangan')
    allRooms.value = []
    buildings.value = []
  } finally {
    loading.value = false
  }
}

async function selectRoom(room) {
  selectedRoomId.value = room.id
  detailLoading.value = true
  try {
    const [roomRes, itemsRes] = await Promise.all([
      facilityApi.getRoom(room.id),
      inventoryApi.getItems({ room_id: room.id, per_page: 100 })
    ])
    roomDetail.value = roomRes.data?.data ?? roomRes.data ?? room
    roomItems.value = safeArray(itemsRes)
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat detail ruangan')
    roomDetail.value = null
    roomItems.value = []
  } finally {
    detailLoading.value = false
  }
}

onMounted(loadRooms)
</script>

<style scoped>
.room-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
  gap: 16px;
}

.room-row {
  cursor: pointer;
}

.row-selected {
  background: #ecfdf5 !important;
}

.room-detail {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  background: #fff;
  min-height: 200px;
}

.room-detail-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 32px;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  font-size: 14px;
}

.detail-full {
  grid-column: 1 / -1;
}

.link-btn {
  background: none;
  border: none;
  padding: 0;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  text-align: left;
}

@media (max-width: 900px) {
  .room-layout {
    grid-template-columns: 1fr;
  }
}
</style>
