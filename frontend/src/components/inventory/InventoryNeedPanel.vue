<template>
  <div class="tab-content">
    <template v-if="!detailId">
      <div class="tab-header">
        <div>
          <h2 class="reports-title">Analisis Kebutuhan</h2>
          <p class="muted">Bandingkan sarana dan prasarana yang ada dengan kebutuhan tahun ajaran. Stok dan ruang dihitung dari data inventaris.</p>
        </div>
        <div class="tab-actions">
          <button type="button" class="btn-primary" @click="openCreate()">Buat Analisis</button>
        </div>
      </div>

      <div class="filters-inline">
        <input
          v-model="search"
          type="search"
          class="search-input"
          placeholder="Cari judul atau tahun ajaran"
          @keyup.enter="loadList(1)"
        />
        <select v-model="yearFilter" class="filter-select" @change="loadList(1)">
          <option value="">Semua tahun ajaran</option>
          <option v-for="year in academicYears" :key="year.id" :value="String(year.id)">{{ year.name }}</option>
        </select>
        <select v-model="statusFilter" class="filter-select" @change="loadList(1)">
          <option value="">Semua status</option>
          <option value="draft">Draf</option>
          <option value="final">Final</option>
        </select>
        <button type="button" class="btn-secondary btn-compact" @click="loadList(1)">Cari</button>
      </div>

      <div v-if="loading" class="loading-state"><p>Memuat data...</p></div>
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th>Tahun Ajaran</th>
              <th>Judul</th>
              <th>Status</th>
              <th>Siswa</th>
              <th>Rombel</th>
              <th>Jenis Kurang</th>
              <th>Prioritas Tinggi</th>
              <th>Anggaran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in items" :key="row.id">
              <td class="col-no">{{ inventoryRowNumber(meta, index) }}</td>
              <td>{{ row.academic_year_label || row.academic_year?.name || '-' }}</td>
              <td>{{ row.title }}</td>
              <td><span :class="statusClass(row.status)">{{ row.status_label }}</span></td>
              <td>{{ formatNumber(row.student_count) }}</td>
              <td>{{ formatNumber(row.rombel_count) }}</td>
              <td>{{ formatNumber(row.shortage_lines) }}</td>
              <td>{{ formatNumber(row.high_priority_lines) }}</td>
              <td>
                <div>Beli {{ formatCurrency(row.procure_budget) }}</div>
                <div class="need-sub">Perbaiki {{ formatCurrency(row.repair_budget) }}</div>
              </td>
              <td class="table-actions-cell">
                <TableAction kind="view" title="Buka" @click="openDetail(row.id)" />
                <TableAction kind="duplicate" title="Salin" @click="openCreate(row)" />
                <TableAction kind="print" title="Cetak PDF" @click="printAnalysis(row.id, row.title)" />
                <TableAction kind="delete" title="Hapus" @click="confirmDeleteAnalysis(row)" />
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="items.length === 0" class="empty-state">
          <h3>Belum ada analisis kebutuhan</h3>
          <p>Buat dokumen untuk tahun ajaran yang sedang berjalan. Template awal bisa diubah.</p>
        </div>
        <PaginationBar
          embedded
          :page="meta.current_page"
          :last-page="meta.last_page"
          :per-page="meta.per_page"
          :total="meta.total"
          item-label="dokumen"
          @page-change="loadList"
          @per-page-change="changePerPage"
        />
      </div>
    </template>

    <template v-else>
      <div class="tab-header">
        <div>
          <button type="button" class="btn-secondary btn-compact" @click="closeDetail">Kembali</button>
          <h2 class="reports-title need-detail-title">{{ analysis?.title || 'Analisis Kebutuhan' }}</h2>
          <p class="muted">
            <span v-if="analysis" :class="statusClass(analysis.status)">{{ analysis.status_label }}</span>
            <span v-if="analysis"> · {{ analysis.academic_year_label || '-' }}</span>
            <span v-if="analysis?.counts_synced_at"> · Angka {{ formatStamp(analysis.counts_synced_at) }}</span>
          </p>
        </div>
        <div v-if="analysis" class="tab-actions">
          <button type="button" class="btn-secondary btn-compact" :disabled="!!busy" @click="openCreate(analysis)">
            Salin
          </button>
          <button type="button" class="btn-secondary btn-compact" :disabled="!!busy" @click="printAnalysis(analysis.id, analysis.title)">
            {{ busy === 'print' ? 'Menyiapkan...' : 'Cetak PDF' }}
          </button>
          <button
            v-if="isDraft"
            type="button"
            class="btn-secondary btn-compact"
            :disabled="!!busy"
            @click="refreshCounts"
          >
            {{ busy === 'refresh' ? 'Memperbarui...' : 'Perbarui Angka' }}
          </button>
          <button
            v-if="isDraft"
            type="button"
            class="btn-primary btn-compact"
            :disabled="!!busy"
            @click="confirmFinalize"
          >
            Finalkan
          </button>
          <button
            v-else
            type="button"
            class="btn-secondary btn-compact"
            :disabled="!!busy"
            @click="confirmReopen"
          >
            Buka Kembali
          </button>
        </div>
      </div>

      <div v-if="detailLoading" class="loading-state"><p>Memuat dokumen...</p></div>
      <template v-else-if="analysis">
        <p v-if="!isDraft" class="need-banner">
          Dokumen final. Angka dikunci
          <template v-if="analysis.finalized_at"> pada {{ formatStamp(analysis.finalized_at) }}</template>.
          Buka kembali jika perlu revisi.
        </p>

        <div class="dashboard-stats">
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Siswa aktif</span>
              <strong class="dashboard-stat-value">{{ formatNumber(analysis.student_count) }}</strong>
            </div>
          </article>
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Rombel aktif</span>
              <strong class="dashboard-stat-value">{{ formatNumber(analysis.rombel_count) }}</strong>
            </div>
          </article>
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Jenis kurang</span>
              <strong class="dashboard-stat-value">{{ formatNumber(analysis.shortage_lines) }}</strong>
            </div>
          </article>
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Prioritas tinggi</span>
              <strong class="dashboard-stat-value">{{ formatNumber(analysis.high_priority_lines) }}</strong>
            </div>
          </article>
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Anggaran beli</span>
              <strong class="dashboard-stat-value is-currency">{{ formatCurrency(analysis.procure_budget) }}</strong>
            </div>
          </article>
          <article class="dashboard-stat-card">
            <div class="dashboard-stat-body">
              <span class="dashboard-stat-label">Anggaran perbaikan</span>
              <strong class="dashboard-stat-value is-currency">{{ formatCurrency(analysis.repair_budget) }}</strong>
            </div>
          </article>
        </div>

        <form v-if="isDraft" class="need-header-form" @submit.prevent="saveHeader">
          <div class="form-row">
            <div class="form-group">
              <label>Tahun ajaran</label>
              <select v-model="headerForm.academic_year_id" required>
                <option v-for="year in academicYears" :key="'h-' + year.id" :value="year.id">{{ year.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Judul</label>
              <input v-model="headerForm.title" type="text" maxlength="200" required />
            </div>
          </div>
          <div class="form-group">
            <label>Catatan</label>
            <textarea v-model="headerForm.notes" rows="2" maxlength="2000" />
          </div>
          <button type="submit" class="btn-secondary btn-compact" :disabled="busy === 'header'">
            {{ busy === 'header' ? 'Menyimpan...' : 'Simpan judul' }}
          </button>
        </form>
        <p v-else-if="analysis.notes" class="need-notes">{{ analysis.notes }}</p>

        <section v-for="section in sections" :key="section.key" class="need-section">
          <div class="need-section-head">
            <div>
              <h3>{{ section.label }}</h3>
              <p class="muted">{{ section.hint }}</p>
            </div>
            <button v-if="isDraft" type="button" class="btn-primary btn-compact" @click="openLineModal(section.key)">
              Tambah baris
            </button>
          </div>
          <div class="table-container">
            <table class="data-table need-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Jenis</th>
                  <th>Dasar</th>
                  <th>Rasio</th>
                  <th class="need-num">Butuh</th>
                  <th class="need-num">Baik</th>
                  <th class="need-num">Ringan</th>
                  <th class="need-num">Sedang</th>
                  <th class="need-num">Berat</th>
                  <th class="need-num">Layak</th>
                  <th>Selisih</th>
                  <th>Prioritas</th>
                  <th class="need-num">Anggaran</th>
                  <th v-if="isDraft">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(line, index) in section.lines"
                  :key="line.id"
                  :class="{ 'need-row-short': line.shortage_qty > 0 }"
                >
                  <td class="col-no">{{ index + 1 }}</td>
                  <td>
                    <strong>{{ line.name }}</strong>
                    <div class="need-sub">{{ line.unit }}<template v-if="line.room_type"> · {{ line.room_type }}</template></div>
                    <div v-if="line.notes" class="need-sub">{{ line.notes }}</div>
                    <div v-if="line.existing_habis > 0" class="need-sub">Habis pakai {{ line.existing_habis }} tidak dihitung layak</div>
                    <div
                      v-if="line.section === 'sarana' && !line.category_id && !(line.source_item_ids || []).length"
                      class="need-sub"
                    >
                      Belum dikaitkan ke barang, jumlah tersedia masih 0.
                    </div>
                  </td>
                  <td>{{ line.basis_label }}</td>
                  <td>{{ line.ratio_label }}</td>
                  <td class="need-num">{{ formatNumber(line.required_qty) }}</td>
                  <td class="need-num">{{ formatNumber(line.existing_baik) }}</td>
                  <td class="need-num">{{ formatNumber(line.existing_rusak_ringan) }}</td>
                  <td class="need-num">{{ formatNumber(line.existing_rusak_sedang) }}</td>
                  <td class="need-num">{{ formatNumber(line.existing_rusak_berat) }}</td>
                  <td class="need-num">
                    {{ formatNumber(line.usable_qty) }}
                    <div class="need-sub">{{ line.usable_policy_label }}</div>
                  </td>
                  <td>
                    <span :class="gapClass(line.gap_label)">{{ gapText(line) }}</span>
                    <div v-if="line.shortage_qty > 0" class="need-sub">
                      Beli {{ formatNumber(line.procure_qty) }} · Perbaiki {{ formatNumber(line.repair_qty) }}
                    </div>
                  </td>
                  <td><span :class="priorityClass(line.priority)">{{ line.priority_label }}</span></td>
                  <td class="need-num">
                    <div>Beli {{ costText(line.procure_cost, line.procure_qty) }}</div>
                    <div class="need-sub">Perbaiki {{ costText(line.repair_cost, line.repair_qty) }}</div>
                  </td>
                  <td v-if="isDraft" class="table-actions-cell">
                    <TableAction kind="edit" title="Edit" @click="openLineModal(line.section, line)" />
                    <TableAction kind="delete" title="Hapus" @click="confirmDeleteLine(line)" />
                  </td>
                </tr>
                <tr v-if="section.lines.length === 0">
                  <td :colspan="isDraft ? 14 : 13" class="empty-cell">Belum ada baris {{ section.label.toLowerCase() }}.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <p class="need-footnote muted">
          Kebutuhan dibulatkan ke atas dari dasar × rasio. Kekurangan = kebutuhan − yang layak.
          Unit rusak yang menutup kekurangan dihitung sebagai perbaikan. Sisanya dibeli. Habis pakai tidak diperbaiki.
          Barang hilang, dijual, atau sudah dihapus tidak dihitung. Barang yang sedang dipinjam tetap dihitung.
          Jumlah ruang menghitung semua ruang tipe itu, termasuk yang rusak.
        </p>
      </template>
    </template>

    <div v-if="showCreate" class="modal-overlay" @click="showCreate = false">
      <div class="modal-content modal-content-sm" @click.stop>
        <div class="modal-header">
          <h3>Buat Analisis Kebutuhan</h3>
          <button type="button" class="btn-close" @click="showCreate = false">×</button>
        </div>
        <form class="modal-body" @submit.prevent="submitCreate">
          <div class="form-group">
            <label>Tahun ajaran *</label>
            <select v-model="createForm.academic_year_id" required>
              <option value="">Pilih tahun ajaran</option>
              <option v-for="year in academicYears" :key="'c-' + year.id" :value="year.id">{{ year.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Judul</label>
            <input v-model="createForm.title" type="text" maxlength="200" placeholder="Kosongkan untuk judul otomatis" />
          </div>
          <div class="form-group">
            <label>Isi awal</label>
            <select v-model="createForm.mode">
              <option value="template">Template awal</option>
              <option value="copy">Salin dari dokumen lain</option>
              <option value="blank">Kosong</option>
            </select>
          </div>
          <div v-if="createForm.mode === 'copy'" class="form-group">
            <label>Dokumen sumber *</label>
            <select v-model="createForm.source_analysis_id" required>
              <option value="">Pilih dokumen</option>
              <option v-for="row in copyOptions" :key="'src-' + row.id" :value="String(row.id)">
                {{ row.academic_year_label || 'Tanpa tahun' }} — {{ row.title }}
              </option>
            </select>
            <p class="form-hint muted">Rasio, harga, prioritas, dan kaitan barang ikut disalin. Jumlah siswa, rombel, dan stok dihitung ulang.</p>
          </div>
          <p v-else-if="createForm.mode === 'template'" class="form-hint muted">
            Ruang kelas, laboratorium, meja, kursi, dan papan tulis. Rasio bisa diubah setelah dibuat.
          </p>
          <div v-if="formError" class="error-message">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="showCreate = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="busy === 'create'">
              {{ busy === 'create' ? 'Menyimpan...' : 'Buat' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showLine" class="modal-overlay" @click="closeLineModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingLine ? 'Edit Baris' : 'Tambah Baris' }}</h3>
          <button type="button" class="btn-close" @click="closeLineModal">×</button>
        </div>
        <form class="modal-body" @submit.prevent="submitLine">
          <div class="form-row">
            <div class="form-group">
              <label>Bagian *</label>
              <select v-model="lineForm.section">
                <option value="prasarana">Prasarana</option>
                <option value="sarana">Sarana</option>
              </select>
            </div>
            <div class="form-group">
              <label>Satuan *</label>
              <input v-model="lineForm.unit" type="text" maxlength="30" required />
            </div>
          </div>
          <div class="form-group">
            <label>Jenis *</label>
            <input v-model="lineForm.name" type="text" maxlength="150" required />
          </div>
          <div v-if="lineForm.section === 'prasarana'" class="form-group">
            <label>Tipe ruang yang dihitung *</label>
            <select v-model="lineForm.room_type" required>
              <option value="">Pilih tipe ruang</option>
              <option v-for="type in roomTypes" :key="type" :value="type">{{ type }}</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Dasar hitung *</label>
              <select v-model="lineForm.basis_type">
                <option value="siswa">Jumlah siswa</option>
                <option value="rombel">Jumlah rombel</option>
                <option value="ruang">Jumlah ruang</option>
                <option value="manual">Angka manual</option>
              </select>
            </div>
            <div v-if="lineForm.basis_type === 'ruang'" class="form-group">
              <label>Tipe ruang dasar *</label>
              <select v-model="lineForm.basis_room_type" required>
                <option value="">Pilih tipe ruang</option>
                <option v-for="type in roomTypes" :key="'b-' + type" :value="type">{{ type }}</option>
              </select>
            </div>
            <div v-else-if="lineForm.basis_type === 'manual'" class="form-group">
              <label>Nilai dasar *</label>
              <input v-model.number="lineForm.basis_value" type="number" min="0" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Rasio pembilang *</label>
              <input v-model.number="lineForm.ratio_numerator" type="number" min="1" required />
            </div>
            <div class="form-group">
              <label>Rasio penyebut *</label>
              <input v-model.number="lineForm.ratio_denominator" type="number" min="1" required />
            </div>
          </div>
          <p class="form-hint muted">1 / 30 berarti 1 unit untuk setiap 30 dasar hitung. 1 / 1 berarti satu banding satu.</p>
          <div class="form-row">
            <div class="form-group">
              <label>Yang dihitung layak *</label>
              <select v-model="lineForm.usable_policy">
                <option value="baik_dan_ringan">Baik dan rusak ringan</option>
                <option value="baik_only">Hanya kondisi Baik</option>
              </select>
            </div>
            <div class="form-group">
              <label>Prioritas *</label>
              <select v-model="lineForm.priority">
                <option value="tinggi">Tinggi</option>
                <option value="sedang">Sedang</option>
                <option value="rendah">Rendah</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Harga beli satuan</label>
              <input v-model="lineForm.unit_price" type="number" min="0" step="1" placeholder="Untuk jumlah yang harus dibeli" />
            </div>
            <div class="form-group">
              <label>Biaya perbaikan per unit</label>
              <input v-model="lineForm.unit_repair_price" type="number" min="0" step="1" placeholder="Untuk unit yang rusak" />
            </div>
          </div>
          <p class="form-hint muted">Unit rusak yang masih ada dihitung dulu sebagai perbaikan. Sisa kekurangan baru dihitung sebagai pembelian. Habis pakai tetap masuk pembelian.</p>
          <template v-if="lineForm.section === 'sarana'">
            <div class="form-group">
              <label>Kategori</label>
              <select v-model="lineForm.category_id">
                <option value="">Tidak dikaitkan</option>
                <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.code }} — {{ cat.name }}</option>
              </select>
              <p class="form-hint muted">Dipakai bila tidak ada barang yang dicentang.</p>
            </div>
            <div class="form-group">
              <label>Barang</label>
              <input v-model="itemSearch" type="search" placeholder="Cari kode atau nama barang" />
              <div v-if="lineForm.source_item_ids.length" class="need-selected">
                <button
                  v-for="id in lineForm.source_item_ids"
                  :key="'sel-' + id"
                  type="button"
                  @click="removeItem(id)"
                >
                  {{ itemLabel(id) }} ×
                </button>
              </div>
              <div class="need-check-list">
                <label v-for="item in filteredItems" :key="item.id">
                  <input
                    type="checkbox"
                    :checked="lineForm.source_item_ids.includes(Number(item.id))"
                    @change="toggleItem(item.id)"
                  />
                  <span>{{ item.code }} — {{ item.name }}</span>
                </label>
                <p v-if="filteredItems.length === 0" class="muted">Tidak ada barang yang cocok.</p>
              </div>
              <p v-if="hiddenItemCount > 0" class="form-hint muted">{{ hiddenItemCount }} barang lain disembunyikan. Persempit pencarian.</p>
            </div>
          </template>
          <div class="form-group">
            <label>Keterangan</label>
            <textarea v-model="lineForm.notes" rows="2" maxlength="1000" />
          </div>
          <div v-if="formError" class="error-message">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="closeLineModal">Batal</button>
            <button type="submit" class="btn-primary" :disabled="busy === 'line'">
              {{ busy === 'line' ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <ConfirmDialog
      :show="confirmDialog.show"
      :title="confirmDialog.title"
      :message="confirmDialog.message"
      :warning="confirmDialog.warning"
      :confirm-text="confirmDialog.confirmText"
      :cancel-text="confirmDialog.cancelText"
      :loading-text="confirmDialog.loadingText"
      :confirm-variant="confirmDialog.confirmVariant"
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
import PaginationBar from '@/components/PaginationBar.vue'
import TableAction from '@/components/TableAction.vue'
import { inventoryApi } from '@/api/inventory'
import { inventoryRowNumber, parsePagination, safeArray } from '@/composables/inventory/inventoryApiHelpers'
import { formatCurrency } from '@/composables/inventory/inventoryFormatters'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useReferenceDataStore } from '@/stores/referenceData'
import { openPdfBlob } from '@/utils/pdfPreview'
import { parseBlobError } from '@/utils/blobError'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const referenceStore = useReferenceDataStore()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const items = ref([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const search = ref('')
const yearFilter = ref('')
const statusFilter = ref('')

const analysis = ref(null)
const detailLoading = ref(false)
const busy = ref('')
const sources = ref({ categories: [], items: [], room_types: [] })

const showCreate = ref(false)
const createForm = ref({ academic_year_id: '', title: '', mode: 'template', source_analysis_id: '' })
const copyOptions = ref([])
const headerForm = ref({ academic_year_id: '', title: '', notes: '' })

const showLine = ref(false)
const editingLine = ref(null)
const lineForm = ref(emptyLine('sarana'))
const itemSearch = ref('')
const formError = ref('')

const academicYears = computed(() => referenceStore.academicYears || [])
const categories = computed(() => sources.value.categories || [])
const roomTypes = computed(() => sources.value.room_types || [])
const detailId = computed(() => {
  const id = Number(route.query.id)
  return id && !Number.isNaN(id) ? id : null
})
const isDraft = computed(() => analysis.value?.status !== 'final')
const sections = computed(() => [
  {
    key: 'prasarana',
    label: 'Prasarana',
    hint: 'Jumlah ruang dibanding rombel, siswa, atau angka manual.',
    lines: (analysis.value?.lines || []).filter((line) => line.section === 'prasarana'),
  },
  {
    key: 'sarana',
    label: 'Sarana',
    hint: 'Barang dibanding siswa, rombel, atau jumlah ruang.',
    lines: (analysis.value?.lines || []).filter((line) => line.section === 'sarana'),
  },
])
const matchedItems = computed(() => {
  const cat = lineForm.value.category_id
  const q = itemSearch.value.trim().toLowerCase()
  let list = sources.value.items || []
  if (cat) list = list.filter((item) => String(item.category_id) === String(cat))
  if (q) {
    list = list.filter((item) => `${item.code || ''} ${item.name || ''}`.toLowerCase().includes(q))
  }
  return list
})
const filteredItems = computed(() => matchedItems.value.slice(0, 40))
const hiddenItemCount = computed(() => Math.max(0, matchedItems.value.length - filteredItems.value.length))

function emptyLine(section) {
  return {
    section,
    name: '',
    unit: section === 'prasarana' ? 'Ruang' : 'Unit',
    basis_type: section === 'prasarana' ? 'rombel' : 'siswa',
    basis_room_type: '',
    room_type: section === 'prasarana' ? 'Kelas' : '',
    basis_value: 0,
    ratio_numerator: 1,
    ratio_denominator: 1,
    usable_policy: 'baik_dan_ringan',
    category_id: '',
    source_item_ids: [],
    priority: 'sedang',
    unit_price: '',
    unit_repair_price: '',
    notes: '',
  }
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString('id-ID')
}

function costText(amount, qty) {
  if (!qty) return '-'
  if (amount == null) return '-'
  return formatCurrency(amount)
}

function formatStamp(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusClass(status) {
  return status === 'final' ? 'badge-success' : 'badge-warning'
}

function priorityClass(priority) {
  if (priority === 'tinggi') return 'badge-danger'
  if (priority === 'rendah') return 'badge-gray'
  return 'badge-warning'
}

function gapClass(label) {
  if (label === 'Kurang') return 'badge-danger'
  if (label === 'Lebih') return 'badge-info'
  return 'badge-success'
}

function gapText(line) {
  if (line.shortage_qty > 0) return `Kurang ${formatNumber(line.shortage_qty)}`
  if (line.surplus_qty > 0) return `Lebih ${formatNumber(line.surplus_qty)}`
  return 'Cukup'
}

function errMsg(err, fallback) {
  const data = err?.response?.data
  const first = data?.errors && Object.values(data.errors).flat()[0]
  return first || data?.message || err?.formattedMessage || err?.message || fallback
}

function applyAnalysis(data) {
  analysis.value = data
  headerForm.value = {
    academic_year_id: data?.academic_year_id || '',
    title: data?.title || '',
    notes: data?.notes || '',
  }
}

async function loadSources() {
  try {
    const res = await inventoryApi.getNeedSources()
    sources.value = res.data?.data || { categories: [], items: [], room_types: [] }
  } catch {
    sources.value = { categories: [], items: [], room_types: [] }
  }
}

async function loadList(page = 1) {
  loading.value = true
  try {
    const res = await inventoryApi.getNeedAnalyses({
      page,
      per_page: meta.value.per_page,
      search: search.value || undefined,
      academic_year_id: yearFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = safeArray(res)
    meta.value = parsePagination(res, meta.value)
  } catch (err) {
    items.value = []
    toast.error('Gagal', errMsg(err, 'Gagal memuat analisis kebutuhan'))
  } finally {
    loading.value = false
  }
}

function changePerPage(perPage) {
  meta.value.per_page = perPage
  loadList(1)
}

async function loadDetail(id) {
  detailLoading.value = true
  try {
    const res = await inventoryApi.getNeedAnalysis(id)
    applyAnalysis(res.data?.data || res.data)
  } catch (err) {
    analysis.value = null
    toast.error('Gagal', errMsg(err, 'Gagal memuat dokumen'))
    closeDetail()
  } finally {
    detailLoading.value = false
  }
}

function openDetail(id) {
  router.replace({ name: route.name, query: { ...route.query, id: String(id) } })
}

function closeDetail() {
  const query = { ...route.query }
  delete query.id
  router.replace({ name: route.name, query })
}

function defaultYearId(source) {
  const active = authStore.activeAcademicYearId
  if (active && (!source || Number(active) !== Number(source.academic_year_id))) return active
  const other = academicYears.value.find((year) => !source || Number(year.id) !== Number(source.academic_year_id))
  return other?.id || active || academicYears.value[0]?.id || ''
}

async function loadCopyOptions(source = null) {
  try {
    const res = await inventoryApi.getNeedAnalyses({ per_page: 100, page: 1 })
    const rows = safeArray(res)
    if (source && !rows.some((row) => Number(row.id) === Number(source.id))) {
      rows.unshift(source)
    }
    copyOptions.value = rows
  } catch {
    copyOptions.value = source ? [source] : []
  }
}

function openCreate(source = null) {
  createForm.value = {
    academic_year_id: defaultYearId(source),
    title: '',
    mode: source ? 'copy' : 'template',
    source_analysis_id: source ? String(source.id) : '',
  }
  formError.value = ''
  showCreate.value = true
  loadCopyOptions(source)
}

async function submitCreate() {
  formError.value = ''
  busy.value = 'create'
  try {
    if (createForm.value.mode === 'copy' && !createForm.value.source_analysis_id) {
      formError.value = 'Pilih dokumen yang akan disalin.'
      return
    }
    const res = await inventoryApi.createNeedAnalysis({
      academic_year_id: createForm.value.academic_year_id,
      title: createForm.value.title || null,
      with_template: createForm.value.mode === 'template',
      source_analysis_id: createForm.value.mode === 'copy' ? createForm.value.source_analysis_id : null,
    })
    showCreate.value = false
    toast.success('Berhasil', 'Analisis kebutuhan dibuat')
    const created = res.data?.data
    if (created?.id) openDetail(created.id)
  } catch (err) {
    formError.value = errMsg(err, 'Gagal membuat analisis')
  } finally {
    busy.value = ''
  }
}

async function saveHeader() {
  if (!analysis.value) return
  busy.value = 'header'
  try {
    const res = await inventoryApi.updateNeedAnalysis(analysis.value.id, {
      title: headerForm.value.title,
      notes: headerForm.value.notes,
      academic_year_id: headerForm.value.academic_year_id,
    })
    applyAnalysis(res.data?.data)
    toast.success('Berhasil', 'Dokumen diperbarui')
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal menyimpan dokumen'))
  } finally {
    busy.value = ''
  }
}

async function refreshCounts() {
  if (!analysis.value) return
  busy.value = 'refresh'
  try {
    const res = await inventoryApi.refreshNeedAnalysis(analysis.value.id)
    applyAnalysis(res.data?.data)
    toast.success('Berhasil', 'Angka diperbarui dari data terkini')
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal memperbarui angka'))
  } finally {
    busy.value = ''
  }
}

async function confirmFinalize() {
  const confirmed = await showConfirm({
    title: 'Finalkan Dokumen',
    message: 'Angka kebutuhan, stok, dan anggaran akan dikunci.',
    warning: 'Perubahan berikutnya perlu membuka dokumen kembali.',
    confirmText: 'Finalkan',
    confirmVariant: 'primary',
    loadingText: 'Menyimpan...',
  })
  if (!confirmed || !analysis.value) return
  busy.value = 'finalize'
  try {
    const res = await inventoryApi.finalizeNeedAnalysis(analysis.value.id)
    applyAnalysis(res.data?.data)
    toast.success('Berhasil', 'Dokumen difinalkan')
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal memfinalkan dokumen'))
  } finally {
    busy.value = ''
  }
}

async function confirmReopen() {
  const confirmed = await showConfirm({
    title: 'Buka Kembali',
    message: 'Dokumen kembali menjadi draf dan angkanya dihitung ulang dari data terkini.',
    warning: 'Cetakan final sebelumnya tidak berubah, tetapi dokumen ini bisa diedit lagi.',
    confirmText: 'Buka kembali',
    confirmVariant: 'primary',
    loadingText: 'Membuka...',
  })
  if (!confirmed || !analysis.value) return
  busy.value = 'reopen'
  try {
    const res = await inventoryApi.reopenNeedAnalysis(analysis.value.id)
    applyAnalysis(res.data?.data)
    toast.success('Berhasil', 'Dokumen dibuka kembali')
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal membuka dokumen'))
  } finally {
    busy.value = ''
  }
}

async function confirmDeleteAnalysis(row) {
  const confirmed = await showConfirm({
    title: 'Hapus Analisis',
    message: `Hapus "${row.title}"?`,
    warning: 'Baris kebutuhan di dokumen ini ikut terhapus.',
  })
  if (!confirmed) return
  try {
    await inventoryApi.deleteNeedAnalysis(row.id)
    toast.success('Berhasil', 'Analisis dihapus')
    if (detailId.value === row.id) closeDetail()
    else await loadList(meta.value.current_page || 1)
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal menghapus analisis'))
  }
}

function itemLabel(id) {
  const item = (sources.value.items || []).find((row) => Number(row.id) === Number(id))
  return item ? `${item.code} — ${item.name}` : `Barang #${id}`
}

function toggleItem(id) {
  const num = Number(id)
  const ids = lineForm.value.source_item_ids
  const index = ids.indexOf(num)
  if (index >= 0) ids.splice(index, 1)
  else ids.push(num)
}

function removeItem(id) {
  lineForm.value.source_item_ids = lineForm.value.source_item_ids.filter((itemId) => itemId !== Number(id))
}

function openLineModal(section, line = null) {
  formError.value = ''
  itemSearch.value = ''
  editingLine.value = line
  if (!line) {
    lineForm.value = emptyLine(section)
  } else {
    lineForm.value = {
      section: line.section,
      name: line.name,
      unit: line.unit,
      basis_type: line.basis_type,
      basis_room_type: line.basis_room_type || '',
      room_type: line.room_type || '',
      basis_value: line.basis_value || 0,
      ratio_numerator: line.ratio_numerator,
      ratio_denominator: line.ratio_denominator,
      usable_policy: line.usable_policy,
      category_id: line.category_id ? String(line.category_id) : '',
      source_item_ids: (line.source_item_ids || []).map(Number),
      priority: line.priority,
      unit_price: line.unit_price == null ? '' : line.unit_price,
      unit_repair_price: line.unit_repair_price == null ? '' : line.unit_repair_price,
      notes: line.notes || '',
    }
  }
  showLine.value = true
}

function closeLineModal() {
  showLine.value = false
  editingLine.value = null
}

function linePayload() {
  const form = lineForm.value
  return {
    section: form.section,
    name: form.name.trim(),
    unit: form.unit.trim(),
    basis_type: form.basis_type,
    basis_room_type: form.basis_type === 'ruang' ? form.basis_room_type : null,
    room_type: form.section === 'prasarana' ? form.room_type : null,
    basis_value: form.basis_type === 'manual' ? Number(form.basis_value || 0) : 0,
    ratio_numerator: Number(form.ratio_numerator),
    ratio_denominator: Number(form.ratio_denominator),
    usable_policy: form.usable_policy,
    category_id: form.section === 'sarana' && form.category_id ? Number(form.category_id) : null,
    source_item_ids: form.section === 'sarana' ? form.source_item_ids.map(Number) : [],
    priority: form.priority,
    unit_price: form.unit_price === '' || form.unit_price == null ? null : Number(form.unit_price),
    unit_repair_price: form.unit_repair_price === '' || form.unit_repair_price == null ? null : Number(form.unit_repair_price),
    notes: form.notes || null,
  }
}

async function submitLine() {
  if (!analysis.value) return
  formError.value = ''
  busy.value = 'line'
  const wasEdit = !!editingLine.value
  try {
    const payload = linePayload()
    const res = editingLine.value
      ? await inventoryApi.updateNeedLine(analysis.value.id, editingLine.value.id, payload)
      : await inventoryApi.createNeedLine(analysis.value.id, payload)
    applyAnalysis(res.data?.data)
    closeLineModal()
    toast.success('Berhasil', wasEdit ? 'Baris diperbarui' : 'Baris ditambahkan')
  } catch (err) {
    formError.value = errMsg(err, 'Gagal menyimpan baris')
  } finally {
    busy.value = ''
  }
}

async function confirmDeleteLine(line) {
  const confirmed = await showConfirm({
    title: 'Hapus Baris',
    message: `Hapus "${line.name}" dari analisis ini?`,
    warning: '',
  })
  if (!confirmed || !analysis.value) return
  try {
    const res = await inventoryApi.deleteNeedLine(analysis.value.id, line.id)
    applyAnalysis(res.data?.data)
    toast.success('Berhasil', 'Baris dihapus')
  } catch (err) {
    toast.error('Gagal', errMsg(err, 'Gagal menghapus baris'))
  }
}

async function printAnalysis(id, title) {
  busy.value = 'print'
  try {
    const response = await inventoryApi.exportNeedAnalysisPdf(id)
    const opened = await openPdfBlob(response, `${title || 'Analisis Kebutuhan'}.pdf`)
    if (!opened) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
      return
    }
    toast.success('Berhasil', 'Preview PDF dibuka.')
  } catch (err) {
    toast.error('Gagal', await parseBlobError(err, 'Gagal mencetak analisis kebutuhan'))
  } finally {
    busy.value = ''
  }
}

watch(detailId, (id) => {
  if (id) loadDetail(id)
  else {
    analysis.value = null
    loadList(meta.value.current_page || 1)
  }
}, { immediate: true })

onMounted(() => {
  referenceStore.getAcademicYears()
  loadSources()
})
</script>
