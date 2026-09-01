<template>
  <div class="tab-content reports-panel">
    <div class="reports-layout">
      <aside class="reports-picker" aria-label="Jenis laporan">
        <div
          v-for="group in reportGroups"
          :key="group.id"
          class="reports-picker-group"
        >
          <span class="reports-picker-group-label">{{ group.label }}</span>
          <button
            v-for="type in group.types"
            :key="type"
            type="button"
            :class="['reports-picker-btn', { active: reportType === type }]"
            :aria-current="reportType === type ? 'page' : undefined"
            @click="setReportType(type)"
          >
            {{ reportMeta[type].label }}
          </button>
        </div>
        <div class="reports-picker-foot muted">
          <p>KIB per barang → detail → Cetak KIB</p>
          <p>Fasilitas lengkap → Sarana Prasarana</p>
        </div>
      </aside>

      <div class="reports-main">
        <div class="reports-main-header">
          <div class="reports-main-heading">
            <h2 class="reports-title">{{ currentReportMeta.label }}</h2>
            <p class="reports-desc">{{ currentReportMeta.hint }}</p>
            <p class="reports-pdf-note muted">{{ currentReportMeta.pdfNote }}</p>
          </div>
          <div class="reports-actions">
            <button
              type="button"
              class="btn-secondary btn-compact"
              :disabled="exportingReportExcel || reportLoading"
              @click="exportReportExcel"
            >
              {{ exportingReportExcel ? 'Menyiapkan...' : 'Export Excel' }}
            </button>
            <button
              type="button"
              class="btn-primary btn-compact"
              :disabled="exportingReportPdf || reportLoading"
              :title="pdfButtonTitle"
              @click="exportReportPdf"
            >
              {{ exportingReportPdf ? 'Menyiapkan...' : 'Cetak PDF' }}
            </button>
          </div>
        </div>

        <div v-if="reportHasFilters" class="report-filters-card">
          <form class="report-filters" @submit.prevent="loadActiveReport">
            <select
              v-if="reportShowsFilter(reportType, 'category_id')"
              v-model="reportFilters.category_id"
              class="filter-select"
            >
              <option value="">Semua Kategori</option>
              <option v-for="cat in reportCategories" :key="'rc-' + cat.id" :value="cat.id">
                {{ cat.code }} - {{ cat.name }}
              </option>
            </select>
            <select
              v-if="reportShowsFilter(reportType, 'status')"
              v-model="reportFilters.status"
              class="filter-select"
            >
              <option value="">Semua Status</option>
              <option value="Tersedia">Tersedia</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Rusak">Rusak</option>
              <option value="Hilang">Hilang</option>
              <option value="Dijual">Dijual</option>
            </select>
            <select
              v-if="reportShowsFilter(reportType, 'condition')"
              v-model="reportFilters.condition"
              class="filter-select"
            >
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
              <option value="Habis Pakai">Habis Pakai</option>
            </select>
            <select
              v-if="reportShowsFilter(reportType, 'building_id')"
              v-model="reportFilters.building_id"
              class="filter-select"
            >
              <option value="">Semua Gedung</option>
              <option v-for="b in buildings" :key="'rb-' + b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select
              v-if="reportShowsFilter(reportType, 'room_id')"
              v-model="reportFilters.room_id"
              class="filter-select"
            >
              <option value="">Semua Ruangan</option>
              <option v-for="r in rooms" :key="'rr-' + r.id" :value="r.id">{{ r.name }}</option>
            </select>
            <select
              v-if="reportShowsFilter(reportType, 'transaction_type')"
              v-model="reportFilters.transaction_type"
              class="filter-select"
            >
              <option value="">Semua Jenis</option>
              <option value="Masuk">Masuk</option>
              <option value="Keluar">Keluar</option>
              <option value="Mutasi">Mutasi</option>
              <option value="Penyesuaian">Penyesuaian</option>
            </select>
            <input
              v-if="reportShowsFilter(reportType, 'date_range')"
              v-model="reportFilters.date_from"
              type="date"
              class="filter-select"
              title="Dari tanggal"
            />
            <input
              v-if="reportShowsFilter(reportType, 'date_range')"
              v-model="reportFilters.date_to"
              type="date"
              class="filter-select"
              title="Sampai tanggal"
            />
            <button type="submit" class="btn-secondary btn-compact" :disabled="reportLoading">
              {{ reportLoading ? 'Memuat...' : 'Terapkan Filter' }}
            </button>
          </form>
        </div>

        <div class="reports-results">
          <div v-if="reportLoading" class="loading-state">
            <p>Memuat laporan...</p>
          </div>

          <template v-else>
      <div v-if="reportType === 'summary'" class="report-summary-block">
        <div class="report-kpi-row">
          <div class="report-kpi">
            <span class="report-kpi-label">Total Barang</span>
            <strong>{{ reportStats?.total_items ?? 0 }}</strong>
            <span class="muted">{{ reportStats?.total_quantity ?? 0 }} unit</span>
          </div>
          <div class="report-kpi">
            <span class="report-kpi-label">Estimasi Nilai</span>
            <strong>{{ formatCurrency(reportStats?.total_value || 0) }}</strong>
          </div>
          <div class="report-kpi">
            <span class="report-kpi-label">Garansi &lt; 3 bln</span>
            <strong>{{ reportStats?.warranty_expiring_soon ?? 0 }}</strong>
          </div>
          <div class="report-kpi">
            <span class="report-kpi-label">Rusak / Hilang</span>
            <strong>{{ reportDamaged.length }}</strong>
          </div>
          <div class="report-kpi">
            <span class="report-kpi-label">Dipinjam</span>
            <strong>{{ reportLoaned.length }}</strong>
          </div>
        </div>

        <div class="report-tables-split">
          <div class="table-container">
            <h3 class="report-section-title">Ringkasan per Status</h3>
            <table class="data-table">
              <thead><tr><th class="col-no">No</th><th>Status</th><th>Jumlah Item</th><th>Total Unit</th></tr></thead>
              <tbody>
                <tr v-for="(entry, index) in Object.entries(reportStats?.by_status || {})" :key="'st-' + entry[0]">
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>{{ entry[0] }}</td>
                  <td>{{ entry[1].count }}</td>
                  <td>{{ entry[1].quantity }}</td>
                </tr>
                <tr v-if="!Object.keys(reportStats?.by_status || {}).length">
                  <td colspan="4" class="empty-cell">Tidak ada data</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="table-container">
            <h3 class="report-section-title">Ringkasan per Kondisi</h3>
            <table class="data-table">
              <thead><tr><th class="col-no">No</th><th>Kondisi</th><th>Jumlah Item</th><th>Total Unit</th></tr></thead>
              <tbody>
                <tr v-for="(entry, index) in Object.entries(reportStats?.by_condition || {})" :key="'cd-' + entry[0]">
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>{{ entry[0] }}</td>
                  <td>{{ entry[1].count }}</td>
                  <td>{{ entry[1].quantity }}</td>
                </tr>
                <tr v-if="!Object.keys(reportStats?.by_condition || {}).length">
                  <td colspan="4" class="empty-cell">Tidak ada data</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="table-container">
          <h3 class="report-section-title">Estimasi Nilai Perolehan per Kategori</h3>
          <table class="data-table">
            <thead>
              <tr><th class="col-no">No</th><th>Kategori</th><th>Jumlah Item</th><th>Total Unit</th><th>Nilai (Rp)</th></tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in reportAsset" :key="'as-' + row.category">
                <td class="col-no">{{ idx + 1 }}</td>
                <td>{{ row.category }}</td>
                <td>{{ row.item_count ?? row.count }}</td>
                <td>{{ row.total_quantity }}</td>
                <td>{{ formatCurrency(row.total_value || 0) }}</td>
              </tr>
              <tr v-if="!reportAsset.length"><td colspan="5" class="empty-cell">Tidak ada data</td></tr>
            </tbody>
            <tfoot v-if="reportAsset.length">
              <tr>
                <td colspan="4" class="text-right"><strong>Total</strong></td>
                <td><strong>{{ formatCurrency(reportAssetGrandTotal) }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div v-else-if="reportType === 'stock'" class="table-container">
        <h3 class="report-section-title">Daftar Stok Barang</h3>
        <p v-if="reportStockMeta.truncated" class="muted report-note">
          Menampilkan {{ reportStock.length }} dari {{ reportStockMeta.total }} barang
        </p>
        <table class="data-table report-wide-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Kode</th><th>Nama</th><th>Merk/Model</th><th>SN</th>
              <th>Kategori</th><th>Qty</th><th>Kondisi</th><th>Status</th>
              <th>Lokasi</th><th>Tgl Beli</th><th>Harga</th><th>Nilai</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportStock" :key="'stk-' + row.id">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ row.code }}</td>
              <td>{{ row.name }}</td>
              <td>{{ row.brand_model || '-' }}</td>
              <td>{{ row.serial_number || '-' }}</td>
              <td>{{ row.category || '-' }}</td>
              <td>{{ row.quantity }}{{ row.unit ? ' ' + row.unit : '' }}</td>
              <td>{{ row.condition || '-' }}</td>
              <td>{{ row.status || '-' }}</td>
              <td>{{ row.location || '-' }}</td>
              <td>{{ formatDate(row.purchase_date) || '-' }}</td>
              <td>{{ row.purchase_price != null ? formatCurrency(row.purchase_price) : '-' }}</td>
              <td>{{ row.total_value != null ? formatCurrency(row.total_value) : '-' }}</td>
            </tr>
            <tr v-if="!reportStock.length"><td colspan="13" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
          <tfoot v-if="reportStock.length">
            <tr>
              <td colspan="6" class="text-right"><strong>Total</strong></td>
              <td><strong>{{ reportStockMeta.total_quantity }}</strong></td>
              <td colspan="4"></td>
              <td colspan="2"><strong>{{ formatCurrency(reportStockMeta.total_value || 0) }}</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div v-else-if="reportType === 'asset'" class="table-container">
          <h3 class="report-section-title">Estimasi Nilai Perolehan per Kategori</h3>
        <table class="data-table">
          <thead>
            <tr><th class="col-no">No</th><th>Kategori</th><th>Jumlah Item</th><th>Total Unit</th><th>Nilai (Rp)</th></tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportAsset" :key="'av-' + row.category">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ row.category }}</td>
              <td>{{ row.item_count }}</td>
              <td>{{ row.total_quantity }}</td>
              <td>{{ formatCurrency(row.total_value || 0) }}</td>
            </tr>
            <tr v-if="!reportAsset.length"><td colspan="5" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
          <tfoot v-if="reportAsset.length">
            <tr>
              <td colspan="4" class="text-right"><strong>Total</strong></td>
              <td><strong>{{ formatCurrency(reportAssetGrandTotal) }}</strong></td>
            </tr>
          </tfoot>
        </table>
        <h3 class="report-section-title report-section-spaced">Rincian per Barang</h3>
        <table class="data-table report-wide-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Kode</th><th>Nama</th><th>Kategori</th>
              <th>Qty</th><th>Harga Satuan</th><th>Nilai</th><th>Tgl Beli</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportAssetItems" :key="'avi-' + idx + '-' + row.code">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ row.code }}</td>
              <td>{{ row.name }}</td>
              <td>{{ row.category }}</td>
              <td>{{ row.quantity }}{{ row.unit ? ' ' + row.unit : '' }}</td>
              <td>{{ formatCurrency(row.unit_price || 0) }}</td>
              <td>{{ formatCurrency(row.total_value || 0) }}</td>
              <td>{{ formatDate(row.purchase_date) || '-' }}</td>
            </tr>
            <tr v-if="!reportAssetItems.length"><td colspan="8" class="empty-cell">Tidak ada rincian</td></tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="reportType === 'location'" class="table-container">
        <h3 class="report-section-title">Inventaris per Ruangan</h3>
        <div v-for="loc in reportByLocation" :key="loc.location_name + loc.location_type" class="report-subsection">
          <h4 class="report-section-title">{{ loc.location_name }} ({{ loc.count }} barang, {{ loc.total_quantity }} unit)</h4>
          <table class="data-table">
            <thead>
              <tr><th class="col-no">No</th><th>Kode</th><th>Nama</th><th>Kategori</th><th>Qty</th></tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in loc.items" :key="item.id">
                <td class="col-no">{{ index + 1 }}</td>
                <td>{{ item.code }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.category || '-' }}</td>
                <td>{{ item.quantity }}</td>
              </tr>
              <tr v-if="!loc.items?.length"><td colspan="5" class="empty-cell">Tidak ada barang</td></tr>
            </tbody>
          </table>
        </div>
        <p v-if="!reportByLocation.length" class="empty-cell">Tidak ada data</p>
      </div>

      <div v-else-if="reportType === 'category'" class="table-container">
        <h3 class="report-section-title">Inventaris per Kategori</h3>
        <div v-for="cat in reportByCategory" :key="cat.category" class="report-subsection">
          <h4 class="report-section-title">{{ cat.category }} ({{ cat.count }} barang, {{ cat.total_quantity }} unit)</h4>
          <table class="data-table">
            <thead>
              <tr><th class="col-no">No</th><th>Kode</th><th>Nama</th><th>Qty</th><th>Kondisi</th><th>Status</th><th>Lokasi</th></tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in cat.items" :key="item.id">
                <td class="col-no">{{ index + 1 }}</td>
                <td>{{ item.code }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.quantity }}{{ item.unit ? ' ' + item.unit : '' }}</td>
                <td>{{ item.condition || '-' }}</td>
                <td>{{ item.status || '-' }}</td>
                <td>{{ item.location || '-' }}</td>
              </tr>
              <tr v-if="!cat.items?.length"><td colspan="7" class="empty-cell">Tidak ada barang</td></tr>
            </tbody>
          </table>
        </div>
        <p v-if="!reportByCategory.length" class="empty-cell">Tidak ada data</p>
      </div>

      <div v-else-if="reportType === 'damaged'" class="table-container">
        <h3 class="report-section-title">Barang Rusak / Hilang</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Kode</th><th>Nama</th><th>Kategori</th>
              <th>Kondisi / Status</th><th>Qty</th><th>Lokasi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportDamaged" :key="'dm-' + row.id + '-' + idx">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ row.code }}</td>
              <td>{{ row.name }}</td>
              <td>{{ row.category || '-' }}</td>
              <td>{{ row.statusLabel || row.label || row.condition || '-' }}</td>
              <td>{{ row.quantity }}{{ row.unit ? ' ' + row.unit : '' }}</td>
              <td>{{ row.location || '-' }}</td>
            </tr>
            <tr v-if="!reportDamaged.length"><td colspan="7" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="reportType === 'loaned'" class="table-container">
        <h3 class="report-section-title">Peminjaman Aktif</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Kode</th><th>Barang</th><th>Unit Aset</th><th>Kategori</th><th>Peminjam</th>
              <th>Qty</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportLoaned" :key="'ln-' + row.id">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ row.item_code || row.code }}</td>
              <td>{{ row.item_name || row.name }}</td>
              <td class="muted">{{ row.asset_number || '-' }}</td>
              <td>{{ row.category || '-' }}</td>
              <td>{{ row.borrower_name }}</td>
              <td>{{ row.quantity }}</td>
              <td>{{ formatDate(row.loan_date) || '-' }}</td>
              <td>{{ formatDate(row.expected_return_date) || '-' }}</td>
              <td>{{ row.is_overdue ? 'Terlambat' : (row.status || '-') }}</td>
            </tr>
            <tr v-if="!reportLoaned.length"><td colspan="10" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="reportType === 'transactions'" class="table-container">
        <h3 class="report-section-title">Mutasi / Transaksi</h3>
        <table class="data-table report-wide-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Tanggal</th><th>No Ref</th><th>Kode</th><th>Nama</th>
              <th>Jenis</th><th>Qty</th><th>Dari</th><th>Ke</th><th>Oleh</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportTransactions" :key="'tr-' + row.id">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ formatDate(row.date) || '-' }}</td>
              <td>{{ row.reference_number || '-' }}</td>
              <td>{{ row.item_code }}</td>
              <td>{{ row.item_name }}</td>
              <td>{{ row.type }}</td>
              <td>{{ row.quantity }}</td>
              <td>{{ row.from_location || '-' }}</td>
              <td>{{ row.to_location || '-' }}</td>
              <td>{{ row.created_by || '-' }}</td>
            </tr>
            <tr v-if="!reportTransactions.length"><td colspan="10" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="reportType === 'asset_movements'" class="table-container">
        <h3 class="report-section-title">Mutasi Aset Individual</h3>
        <table class="data-table report-wide-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Tanggal</th><th>No Aset</th><th>Kode</th><th>Barang</th>
              <th>Dari</th><th>Ke</th><th>No Ref</th><th>Oleh</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportAssetMovements" :key="'am-' + row.id">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ formatDate(row.movement_date) || '-' }}</td>
              <td>{{ row.asset_number || '-' }}</td>
              <td>{{ row.item_code || '-' }}</td>
              <td>{{ row.item_name || '-' }}</td>
              <td>{{ row.from_location || '-' }}</td>
              <td>{{ row.to_location || '-' }}</td>
              <td>{{ row.reference_number || '-' }}</td>
              <td>{{ row.created_by || '-' }}</td>
            </tr>
            <tr v-if="!reportAssetMovements.length"><td colspan="9" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="reportType === 'maintenance'" class="table-container">
        <h3 class="report-section-title">Pemeliharaan</h3>
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Tgl Jadwal</th><th>Kode</th><th>Barang</th><th>Jenis</th>
              <th>Biaya</th><th>Status</th><th>Teknisi</th><th>Selesai</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportMaintenance" :key="'mt-' + row.id">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ formatDate(row.scheduled_date) || '-' }}</td>
              <td>{{ row.item_code }}</td>
              <td>{{ row.item_name }}</td>
              <td>{{ row.type }}</td>
              <td>{{ row.cost != null ? formatCurrency(row.cost) : '-' }}</td>
              <td>{{ row.status || '-' }}</td>
              <td>{{ row.technician_name || '-' }}</td>
              <td>{{ formatDate(row.completed_date) || '-' }}</td>
            </tr>
            <tr v-if="!reportMaintenance.length"><td colspan="9" class="empty-cell">Tidak ada data</td></tr>
          </tbody>
          <tfoot v-if="reportMaintenance.length">
            <tr>
              <td colspan="5" class="text-right"><strong>Total biaya</strong></td>
              <td colspan="4"><strong>{{ formatCurrency(reportMaintenanceTotalCost) }}</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div v-else-if="reportType === 'disposal'" class="table-container">
        <h3 class="report-section-title">Riwayat Penghapusan Barang</h3>
        <p class="muted report-note">Barang yang sudah dicatat dihapus resmi (SK/BA). Untuk mencatat penghapusan baru, gunakan tab Operasional → Penghapusan.</p>
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th><th>Tgl Penghapusan</th><th>Kode</th><th>Nama</th><th>Kategori</th>
              <th>Status Akhir</th><th>Qty</th><th>No. SK / BA</th><th>Alasan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in reportDisposed" :key="'dp-' + (row.id || idx)">
              <td class="col-no">{{ idx + 1 }}</td>
              <td>{{ formatDate(row.disposed_at || row.disposal_date) || '-' }}</td>
              <td>{{ row.code || row.item_code }}</td>
              <td>{{ row.name || row.item_name }}</td>
              <td>{{ row.category || '-' }}</td>
              <td>{{ row.status || '-' }}</td>
              <td>{{ row.quantity }}{{ row.unit ? ' ' + row.unit : '' }}</td>
              <td>{{ row.disposal_document_number || '-' }}</td>
              <td>{{ row.disposal_reason || '-' }}</td>
            </tr>
            <tr v-if="!reportDisposed.length"><td colspan="9" class="empty-cell">Tidak ada data penghapusan</td></tr>
          </tbody>
        </table>
      </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { inventoryApi } from '@/api/inventory'
import { formatDate, formatCurrency } from '@/composables/inventory/inventoryFormatters'
import { safeArray } from '@/composables/inventory/inventoryApiHelpers'
import {
  INVENTORY_REPORT_GROUPS,
  INVENTORY_REPORT_META,
  getReportMeta,
  reportShowsFilter
} from '@/composables/inventory/inventoryReportConfig'
import { useToast } from '@/composables/useToast'

defineProps({
  buildings: { type: Array, default: () => [] },
  rooms: { type: Array, default: () => [] }
})

const toast = useToast()

const reportGroups = INVENTORY_REPORT_GROUPS
const reportMeta = INVENTORY_REPORT_META

const reportType = ref('summary')
const reportLoading = ref(false)
const exportingReportPdf = ref(false)
const exportingReportExcel = ref(false)
const reportCategories = ref([])
const reportFilters = ref({
  category_id: '',
  status: '',
  condition: '',
  building_id: '',
  room_id: '',
  date_from: '',
  date_to: '',
  transaction_type: ''
})

const reportStats = ref(null)
const reportStock = ref([])
const reportStockMeta = ref({ total: 0, truncated: false, total_quantity: 0, total_value: 0 })
const reportDamaged = ref([])
const reportLoaned = ref([])
const reportAsset = ref([])
const reportAssetItems = ref([])
const reportAssetGrandTotal = ref(0)
const reportTransactions = ref([])
const reportAssetMovements = ref([])
const reportMaintenance = ref([])
const reportMaintenanceTotalCost = ref(0)
const reportByLocation = ref([])
const reportByCategory = ref([])
const reportDisposed = ref([])

const FILTER_KEYS = ['category_id', 'status', 'condition', 'building_id', 'room_id', 'transaction_type', 'date_range']

const reportHasFilters = computed(() =>
  FILTER_KEYS.some((key) => reportShowsFilter(reportType.value, key))
)

const currentReportMeta = computed(() => getReportMeta(reportType.value))

const currentReportLabel = computed(() => currentReportMeta.value.label)

const pdfButtonTitle = computed(() => currentReportMeta.value.pdfNote)

function buildReportParams() {
  const params = { type: reportType.value }
  const f = reportFilters.value
  if (f.category_id) params.category_id = f.category_id
  if (f.status) params.status = f.status
  if (f.condition) params.condition = f.condition
  if (f.building_id) params.building_id = f.building_id
  if (f.room_id) params.room_id = f.room_id
  if (f.date_from) params.date_from = f.date_from
  if (f.date_to) params.date_to = f.date_to
  if (f.transaction_type) params.transaction_type = f.transaction_type
  return params
}

async function loadReportCategories() {
  try {
    const res = await inventoryApi.getCategories({ per_page: 200 })
    reportCategories.value = safeArray(res)
  } catch {
    reportCategories.value = []
  }
}

function openPdfPreview(blob, title) {
  const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
  const win = window.open('', '_blank')
  if (!win) {
    toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
    URL.revokeObjectURL(url)
    return false
  }
  win.document.write(`<!DOCTYPE html><html><head><title>${title}</title></head><body>
    <iframe src="${url}" style="width:100%;height:100vh;border:0"></iframe></body></html>`)
  win.document.close()
  setTimeout(() => URL.revokeObjectURL(url), 120_000)
  return true
}

async function exportReportPdf() {
  exportingReportPdf.value = true
  try {
    const response = await inventoryApi.exportReportPdf(buildReportParams())
    const contentType = response.headers?.['content-type'] || ''
    if (response.status !== 200 || contentType.includes('application/json')) {
      throw new Error('Gagal mencetak laporan inventaris.')
    }
    const blob = response.data instanceof Blob
      ? response.data
      : new Blob([response.data], { type: 'application/pdf' })
    if (openPdfPreview(blob, `Preview ${currentReportLabel.value}`)) {
      toast.success('Berhasil', 'Preview PDF Inventaris dibuka.')
    }
  } catch (err) {
    toast.error('Gagal', err.message || err.formattedMessage || err.response?.data?.message || 'Gagal mencetak laporan inventaris.')
  } finally {
    exportingReportPdf.value = false
  }
}

async function exportReportExcel() {
  exportingReportExcel.value = true
  try {
    await inventoryApi.exportReportExcel(buildReportParams())
    toast.success('Berhasil', 'Excel laporan inventaris diunduh.')
  } catch (err) {
    toast.error('Gagal', err.message || err.formattedMessage || err.response?.data?.message || 'Gagal export Excel laporan.')
  } finally {
    exportingReportExcel.value = false
  }
}

async function loadActiveReport() {
  reportLoading.value = true
  const params = buildReportParams()
  try {
    if (reportType.value === 'summary') {
      const [statsRes, damagedRes, loanedRes, assetRes] = await Promise.all([
        inventoryApi.getReportStatistics(params),
        inventoryApi.getReportDamagedMissing(params),
        inventoryApi.getReportLoaned(params),
        inventoryApi.getReportAssetValue(params)
      ])
      reportStats.value = statsRes.data?.data ?? statsRes.data ?? null
      const damagedData = damagedRes.data?.data ?? damagedRes.data ?? {}
      reportDamaged.value = (damagedData.rows || []).length
        ? damagedData.rows.map((d) => ({ ...d, statusLabel: d.label || d.condition || 'Rusak' }))
        : [
            ...(damagedData.damaged || []).map((d) => ({ ...d, statusLabel: d.condition || 'Rusak' })),
            ...(damagedData.missing || []).map((m) => ({ ...m, condition: '-', statusLabel: 'Hilang' }))
          ]
      reportLoaned.value = (loanedRes.data?.data ?? loanedRes.data ?? {}).loans || []
      const assetData = assetRes.data?.data ?? assetRes.data ?? {}
      reportAsset.value = assetData.by_category || []
      reportAssetGrandTotal.value = assetData.grand_total_value || 0
    } else if (reportType.value === 'stock') {
      const res = await inventoryApi.getReportStock(params)
      const data = res.data?.data ?? res.data ?? {}
      reportStock.value = data.items || []
      reportStockMeta.value = {
        total: data.total || 0,
        truncated: !!data.truncated,
        total_quantity: data.total_quantity || 0,
        total_value: data.total_value || 0
      }
    } else if (reportType.value === 'asset') {
      const res = await inventoryApi.getReportAssetValue(params)
      const data = res.data?.data ?? res.data ?? {}
      reportAsset.value = data.by_category || []
      reportAssetGrandTotal.value = data.grand_total_value || 0
      reportAssetItems.value = (data.by_category || []).flatMap((cat) =>
        (cat.items || []).map((item) => ({ ...item, category: cat.category }))
      )
    } else if (reportType.value === 'location') {
      const res = await inventoryApi.getReportByLocation(params)
      const data = res.data?.data ?? res.data ?? {}
      reportByLocation.value = data.by_room || []
    } else if (reportType.value === 'category') {
      const res = await inventoryApi.getReportByCategory(params)
      reportByCategory.value = res.data?.data ?? res.data ?? []
    } else if (reportType.value === 'damaged') {
      const res = await inventoryApi.getReportDamagedMissing(params)
      const data = res.data?.data ?? res.data ?? {}
      reportDamaged.value = (data.rows || []).length
        ? data.rows.map((d) => ({ ...d, statusLabel: d.label || d.condition || 'Rusak' }))
        : [
            ...(data.damaged || []).map((d) => ({ ...d, statusLabel: d.condition || 'Rusak' })),
            ...(data.missing || []).map((m) => ({ ...m, condition: '-', statusLabel: 'Hilang' }))
          ]
    } else if (reportType.value === 'loaned') {
      const res = await inventoryApi.getReportLoaned(params)
      reportLoaned.value = (res.data?.data ?? res.data ?? {}).loans || []
    } else if (reportType.value === 'transactions') {
      const res = await inventoryApi.getReportTransactions(params)
      reportTransactions.value = (res.data?.data ?? res.data ?? {}).transactions || []
    } else if (reportType.value === 'asset_movements') {
      const res = await inventoryApi.getReportAssetMovements(params)
      reportAssetMovements.value = (res.data?.data ?? res.data ?? {}).movements || []
    } else if (reportType.value === 'maintenance') {
      const res = await inventoryApi.getReportMaintenance(params)
      const data = res.data?.data ?? res.data ?? {}
      reportMaintenance.value = data.maintenances || []
      reportMaintenanceTotalCost.value = data.total_cost || 0
    } else if (reportType.value === 'disposal') {
      const res = await inventoryApi.getReportDisposed(params)
      const data = res.data?.data ?? res.data ?? {}
      reportDisposed.value = (data.items || []).map((row) => ({
        ...row,
        disposed_at: row.disposed_at || row.disposal_date
      }))
    }
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat laporan')
  } finally {
    reportLoading.value = false
  }
}

async function setReportType(type) {
  if (reportType.value === type) return
  reportType.value = type
  await loadActiveReport()
}

onMounted(async () => {
  await loadReportCategories()
  await loadActiveReport()
})
</script>
