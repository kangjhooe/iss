<template>
  <Layout>
    <div class="inventory-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Inventaris</h2>
            <p>Kelola kategori, barang inventaris, dan transaksi</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs-container">
        <div class="tabs-nav">
          <button @click="activeTab = 'items'" :class="['tab-btn', { active: activeTab === 'items' }]">
            <span>Barang</span>
          </button>
          <button @click="activeTab = 'categories'" :class="['tab-btn', { active: activeTab === 'categories' }]">
            <span>Kategori</span>
          </button>
          <button @click="activeTab = 'transactions'" :class="['tab-btn', { active: activeTab === 'transactions' }]">
            <span>Transaksi</span>
          </button>
          <button @click="activeTab = 'maintenances'" :class="['tab-btn', { active: activeTab === 'maintenances' }]">
            <span>Pemeliharaan</span>
          </button>
          <button @click="activeTab = 'loans'" :class="['tab-btn', { active: activeTab === 'loans' }]">
            <span>Peminjaman</span>
          </button>
          <button @click="activeTab = 'reports'" :class="['tab-btn', { active: activeTab === 'reports' }]">
            <span>Laporan</span>
          </button>
        </div>
      </div>

      <!-- ITEMS TAB -->
      <div v-show="activeTab === 'items'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="itemFilters.search"
              @input="debounceLoadItems"
              placeholder="Cari kode, nama, merk, model, serial..."
              class="search-input"
            />

            <select v-model="itemFilters.category_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.code }} - {{ cat.name }}
              </option>
            </select>

            <select v-model="itemFilters.status" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Tersedia">Tersedia</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Rusak">Rusak</option>
              <option value="Hilang">Hilang</option>
              <option value="Dijual">Dijual</option>
            </select>

            <select v-model="itemFilters.condition" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
              <option value="Habis Pakai">Habis Pakai</option>
            </select>

            <select v-model="itemFilters.building_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Gedung</option>
              <option v-for="b in buildings" :key="b.id" :value="b.id">
                {{ b.name }}
              </option>
            </select>

            <select v-model="itemFilters.room_id" @change="loadItems(1)" class="filter-select">
              <option value="">Semua Ruangan</option>
              <option v-for="r in rooms" :key="r.id" :value="r.id">
                {{ r.name }}
              </option>
            </select>
          </div>

          <button @click="openItemModal()" class="btn-primary">
            <span>Tambah Barang</span>
          </button>
        </div>

        <div v-if="itemsLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Qty</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in items" :key="it.id">
                <td>
                  <div class="code-cell">
                    <div class="code">{{ it.code }}</div>
                    <div v-if="it.serial_number" class="muted">SN: {{ it.serial_number }}</div>
                  </div>
                </td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ it.name }}</div>
                    <div class="muted">{{ [it.brand, it.model].filter(Boolean).join(' ') || '-' }}</div>
                  </div>
                </td>
                <td>{{ it.category?.name || '-' }}</td>
                <td>{{ it.quantity }} {{ it.unit || 'Unit' }}</td>
                <td><span :class="getConditionClass(it.condition)">{{ it.condition }}</span></td>
                <td><span :class="getStatusClass(it.status)">{{ it.status }}</span></td>
                <td>
                  <div class="muted">
                    {{ it.room?.name || (it.building?.name || '-') }}
                  </div>
                  <div v-if="it.location_note" class="muted small">
                    {{ it.location_note }}
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openItemModal(it)" class="btn-action btn-edit" title="Edit">Edit</button>
                    <button @click="openTransactionModal(it)" class="btn-action btn-secondary" title="Transaksi">Transaksi</button>
                    <button @click="deleteItem(it)" class="btn-action btn-delete" title="Hapus">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="items.length === 0" class="empty-state">
            <h3>Tidak ada barang</h3>
            <p>Mulai dengan menambahkan barang inventaris.</p>
            <button @click="openItemModal()" class="btn-primary">Tambah Barang</button>
          </div>

          <div v-if="itemsMeta.last_page > 1" class="pagination">
            <button
              @click="loadItems(itemsMeta.current_page - 1)"
              :disabled="itemsMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ itemsMeta.current_page }} dari {{ itemsMeta.last_page }} (Total: {{ itemsMeta.total }})
            </span>
            <button
              @click="loadItems(itemsMeta.current_page + 1)"
              :disabled="itemsMeta.current_page >= itemsMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- CATEGORIES TAB -->
      <div v-show="activeTab === 'categories'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="categoryFilters.search"
              @input="debounceLoadCategories"
              placeholder="Cari kode / nama kategori..."
              class="search-input"
            />
            <select v-model="categoryFilters.is_active" @change="loadCategories(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button @click="openCategoryModal()" class="btn-primary">
            <span>Tambah Kategori</span>
          </button>
        </div>

        <div v-if="categoriesLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td>{{ cat.code }}</td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ cat.name }}</div>
                    <div v-if="cat.description" class="muted small">{{ cat.description }}</div>
                  </div>
                </td>
                <td>
                  <span :class="cat.is_active ? 'badge-success' : 'badge-gray'">
                    {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openCategoryModal(cat)" class="btn-action btn-edit">Edit</button>
                    <button @click="deleteCategory(cat)" class="btn-action btn-delete">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="categories.length === 0" class="empty-state">
            <h3>Tidak ada kategori</h3>
            <p>Tambahkan kategori inventaris terlebih dahulu.</p>
            <button @click="openCategoryModal()" class="btn-primary">Tambah Kategori</button>
          </div>

          <div v-if="categoriesMeta.last_page > 1" class="pagination">
            <button
              @click="loadCategories(categoriesMeta.current_page - 1)"
              :disabled="categoriesMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ categoriesMeta.current_page }} dari {{ categoriesMeta.last_page }} (Total: {{ categoriesMeta.total }})
            </span>
            <button
              @click="loadCategories(categoriesMeta.current_page + 1)"
              :disabled="categoriesMeta.current_page >= categoriesMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- TRANSACTIONS TAB -->
      <div v-show="activeTab === 'transactions'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <select v-model="transactionFilters.transaction_type" @change="loadTransactions(1)" class="filter-select">
              <option value="">Semua Jenis</option>
              <option value="Masuk">Masuk</option>
              <option value="Keluar">Keluar</option>
              <option value="Mutasi">Mutasi</option>
              <option value="Penyesuaian">Penyesuaian</option>
            </select>
            <input v-model="transactionFilters.date_from" @change="loadTransactions(1)" type="date" class="filter-select" />
            <input v-model="transactionFilters.date_to" @change="loadTransactions(1)" type="date" class="filter-select" />
          </div>

          <button @click="openTransactionModal()" class="btn-primary">
            <span>Catat Transaksi</span>
          </button>
        </div>

        <div v-if="transactionsLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
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
              <tr v-for="tr in transactions" :key="tr.id">
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
            <h3>Belum ada transaksi</h3>
            <p>Catat transaksi masuk/keluar/mutasi/penyesuaian.</p>
            <button @click="openTransactionModal()" class="btn-primary">Catat Transaksi</button>
          </div>

          <div v-if="transactionsMeta.last_page > 1" class="pagination">
            <button
              @click="loadTransactions(transactionsMeta.current_page - 1)"
              :disabled="transactionsMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ transactionsMeta.current_page }} dari {{ transactionsMeta.last_page }} (Total: {{ transactionsMeta.total }})
            </span>
            <button
              @click="loadTransactions(transactionsMeta.current_page + 1)"
              :disabled="transactionsMeta.current_page >= transactionsMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- MAINTENANCES TAB -->
      <div v-show="activeTab === 'maintenances'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <select v-model="maintenanceFilters.item_id" @change="loadMaintenances(1)" class="filter-select">
              <option value="">Semua Barang</option>
              <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
            </select>
            <select v-model="maintenanceFilters.maintenance_type" @change="loadMaintenances(1)" class="filter-select">
              <option value="">Semua Jenis</option>
              <option value="Perawatan">Perawatan</option>
              <option value="Perbaikan">Perbaikan</option>
              <option value="Kalibrasi">Kalibrasi</option>
              <option value="Inspeksi">Inspeksi</option>
            </select>
            <select v-model="maintenanceFilters.status" @change="loadMaintenances(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Terjadwal">Terjadwal</option>
              <option value="Dalam Proses">Dalam Proses</option>
              <option value="Selesai">Selesai</option>
              <option value="Dibatalkan">Dibatalkan</option>
            </select>
          </div>
          <button @click="openMaintenanceModal()" class="btn-primary">
            <span>Tambah Pemeliharaan</span>
          </button>
        </div>

        <div v-if="maintenancesLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Barang</th>
                <th>Jenis</th>
                <th>Tanggal Jadwal</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
                <th>Biaya</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in maintenances" :key="m.id">
                <td>
                  <div class="name-cell">
                    <div class="name">{{ m.item?.name || '-' }}</div>
                    <div class="muted small">{{ m.item?.code || '-' }}</div>
                  </div>
                </td>
                <td>{{ m.maintenance_type }}</td>
                <td>{{ formatDate(m.scheduled_date) }}</td>
                <td>{{ formatDate(m.completed_date) || '-' }}</td>
                <td><span :class="getMaintenanceStatusClass(m.status)">{{ m.status }}</span></td>
                <td>{{ m.cost != null ? formatCurrency(m.cost) : '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="openMaintenanceModal(m)" class="btn-action btn-edit">Edit</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="maintenances.length === 0" class="empty-state">
            <h3>Belum ada data pemeliharaan</h3>
            <p>Catat jadwal perawatan, perbaikan, kalibrasi, atau inspeksi.</p>
            <button @click="openMaintenanceModal()" class="btn-primary">Tambah Pemeliharaan</button>
          </div>

          <div v-if="maintenancesMeta.last_page > 1" class="pagination">
            <button
              @click="loadMaintenances(maintenancesMeta.current_page - 1)"
              :disabled="maintenancesMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ maintenancesMeta.current_page }} dari {{ maintenancesMeta.last_page }} (Total: {{ maintenancesMeta.total }})
            </span>
            <button
              @click="loadMaintenances(maintenancesMeta.current_page + 1)"
              :disabled="maintenancesMeta.current_page >= maintenancesMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- LOANS TAB -->
      <div v-show="activeTab === 'loans'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <select v-model="loanFilters.item_id" @change="loadLoans(1)" class="filter-select">
              <option value="">Semua Barang</option>
              <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
            </select>
            <select v-model="loanFilters.borrower_type" @change="loadLoans(1)" class="filter-select">
              <option value="">Semua Peminjam</option>
              <option value="Employee">Pegawai</option>
              <option value="Student">Siswa</option>
              <option value="External">Eksternal</option>
            </select>
            <select v-model="loanFilters.status" @change="loadLoans(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Dikembalikan">Dikembalikan</option>
            </select>
          </div>
          <button @click="openLoanModal()" class="btn-primary">
            <span>Tambah Peminjaman</span>
          </button>
        </div>

        <div v-if="loansLoading" class="loading-state">
          <p>Memuat data...</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Barang</th>
                <th>Peminjam</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Jatuh Tempo</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ln in loans" :key="ln.id" :class="{ 'row-overdue': ln.is_overdue && ln.status === 'Dipinjam' }">
                <td>
                  <div class="name-cell">
                    <div class="name">{{ ln.item?.name || '-' }}</div>
                    <div class="muted small">{{ ln.item?.code || '-' }}</div>
                  </div>
                </td>
                <td>{{ ln.borrower_name }} <span v-if="ln.borrower_phone" class="muted small">({{ ln.borrower_phone }})</span></td>
                <td>{{ formatDate(ln.loan_date) }}</td>
                <td>{{ formatDate(ln.expected_return_date) }}</td>
                <td>{{ ln.quantity }}</td>
                <td>
                  <span :class="ln.status === 'Dikembalikan' ? 'badge-success' : (ln.is_overdue ? 'badge-danger' : 'badge-info')">
                    {{ ln.status }}{{ ln.is_overdue && ln.status === 'Dipinjam' ? ' (Terlambat)' : '' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button
                      v-if="ln.status === 'Dipinjam'"
                      @click="openReturnLoanModal(ln)"
                      class="btn-action btn-secondary"
                    >
                      Kembalikan
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="loans.length === 0" class="empty-state">
            <h3>Belum ada data peminjaman</h3>
            <p>Catat peminjaman barang oleh pegawai, siswa, atau pihak eksternal.</p>
            <button @click="openLoanModal()" class="btn-primary">Tambah Peminjaman</button>
          </div>

          <div v-if="loansMeta.last_page > 1" class="pagination">
            <button
              @click="loadLoans(loansMeta.current_page - 1)"
              :disabled="loansMeta.current_page === 1"
              class="pagination-btn"
            >
              Sebelumnya
            </button>
            <span class="pagination-info">
              Halaman {{ loansMeta.current_page }} dari {{ loansMeta.last_page }} (Total: {{ loansMeta.total }})
            </span>
            <button
              @click="loadLoans(loansMeta.current_page + 1)"
              :disabled="loansMeta.current_page >= loansMeta.last_page"
              class="pagination-btn"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- REPORTS TAB -->
      <div v-show="activeTab === 'reports'" class="tab-content">
        <div class="reports-grid">
          <section class="report-card">
            <h3>Statistik Inventaris</h3>
            <div v-if="reportStatsLoading" class="muted">Memuat...</div>
            <div v-else-if="reportStats" class="report-stats">
              <p><strong>Total Barang:</strong> {{ reportStats.total_items }} ({{ reportStats.total_quantity }} unit)</p>
              <p><strong>Nilai Aset:</strong> {{ formatCurrency(reportStats.total_value || 0) }}</p>
              <p><strong>Garansi habis dalam 3 bulan:</strong> {{ reportStats.warranty_expiring_soon || 0 }} barang</p>
            </div>
            <button @click="loadReportStats" class="btn-outline btn-compact">Muat Ulang</button>
          </section>

          <section class="report-card">
            <h3>Barang Rusak / Hilang</h3>
            <div v-if="reportDamagedLoading" class="muted">Memuat...</div>
            <div v-else-if="reportDamaged.length" class="report-list">
              <table class="data-table small">
                <thead><tr><th>Barang</th><th>Kondisi</th><th>Keterangan</th></tr></thead>
                <tbody>
                  <tr v-for="(row, idx) in reportDamaged" :key="row.id + '-' + idx">
                    <td>{{ row.name }} ({{ row.code }})</td>
                    <td>{{ row.condition || '-' }}</td>
                    <td>{{ row.statusLabel }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="muted">Tidak ada</p>
            <button @click="loadReportDamaged" class="btn-outline btn-compact">Muat Ulang</button>
          </section>

          <section class="report-card">
            <h3>Sedang Dipinjam</h3>
            <div v-if="reportLoanedLoading" class="muted">Memuat...</div>
            <div v-else-if="reportLoaned.length" class="report-list">
              <table class="data-table small">
                <thead><tr><th>Barang</th><th>Peminjam</th><th>Tgl Jatuh Tempo</th></tr></thead>
                <tbody>
                  <tr v-for="row in reportLoaned" :key="row.id">
                    <td>{{ row.item_name || row.name }} ({{ row.item_code || row.code }})</td>
                    <td>{{ row.borrower_name }}</td>
                    <td>{{ formatDate(row.expected_return_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="muted">Tidak ada</p>
            <button @click="loadReportLoaned" class="btn-outline btn-compact">Muat Ulang</button>
          </section>

          <section class="report-card full-width">
            <h3>Nilai Aset per Kategori</h3>
            <div v-if="reportAssetLoading" class="muted">Memuat...</div>
            <div v-else-if="reportAsset.length" class="report-list">
              <table class="data-table">
                <thead><tr><th>Kategori</th><th>Jumlah Barang</th><th>Total Unit</th><th>Nilai (Rp)</th></tr></thead>
                <tbody>
                  <tr v-for="row in reportAsset" :key="row.category">
                    <td>{{ row.category }}</td>
                    <td>{{ row.item_count ?? row.count }}</td>
                    <td>{{ row.total_quantity }}</td>
                    <td>{{ formatCurrency(row.total_value || 0) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="muted">Tidak ada data</p>
            <button @click="loadReportAsset" class="btn-outline btn-compact">Muat Ulang</button>
          </section>
        </div>
      </div>

      <!-- ITEM MODAL -->
      <div v-if="showItemModal" class="modal-overlay" @click="closeItemModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Barang' : 'Tambah Barang' }}</h3>
            <button @click="closeItemModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveItem" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Kategori *</label>
                <select v-model="itemForm.category_id" required>
                  <option value="">Pilih Kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.code }} - {{ cat.name }}
                  </option>
                </select>
                <p v-if="categories.length === 0" class="form-hint">Belum ada kategori. Tambahkan kategori dulu.</p>
              </div>
              <div class="form-group">
                <label>Kode Barang</label>
                <input v-model="itemForm.code" :readonly="!editingItem" placeholder="Kosongkan untuk auto-generate" />
                <p v-if="!editingItem" class="form-hint">Jika kosong, sistem akan membuat kode otomatis.</p>
              </div>
            </div>

            <div v-if="!editingItem" class="form-row">
              <div class="form-group">
                <label>Kode Khusus</label>
                <input v-model="itemForm.custom_code" placeholder="Contoh: BKBA (opsional)" maxlength="20" />
              </div>
              <div class="form-group">
                <label>No. Referensi (Transaksi Masuk)</label>
                <input v-model="itemForm.reference_number" placeholder="Opsional" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Barang *</label>
                <input v-model="itemForm.name" required />
              </div>
              <div class="form-group">
                <label>Jumlah *</label>
                <input type="number" v-model.number="itemForm.quantity" min="1" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Satuan</label>
                <input v-model="itemForm.unit" placeholder="Unit" />
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="itemForm.status">
                  <option value="Tersedia">Tersedia</option>
                  <option value="Dipinjam">Dipinjam</option>
                  <option value="Rusak">Rusak</option>
                  <option value="Hilang">Hilang</option>
                  <option value="Dijual">Dijual</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kondisi *</label>
                <select v-model="itemForm.condition" required>
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                  <option value="Habis Pakai">Habis Pakai</option>
                </select>
              </div>
              <div class="form-group">
                <label>Merk</label>
                <input v-model="itemForm.brand" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Model</label>
                <input v-model="itemForm.model" />
              </div>
              <div class="form-group">
                <label>Serial Number</label>
                <input v-model="itemForm.serial_number" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Beli</label>
                <input type="date" v-model="itemForm.purchase_date" />
              </div>
              <div class="form-group">
                <label>Harga Beli</label>
                <input type="number" v-model.number="itemForm.purchase_price" min="0" step="0.01" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Supplier</label>
                <input v-model="itemForm.supplier" />
              </div>
              <div class="form-group">
                <label>Garansi Sampai</label>
                <input type="date" v-model="itemForm.warranty_expiry" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Gedung</label>
                <select v-model="itemForm.building_id">
                  <option value="">(Opsional)</option>
                  <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ruangan</label>
                <select v-model="itemForm.room_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Catatan Lokasi</label>
              <textarea v-model="itemForm.location_note" rows="2" placeholder="Opsional"></textarea>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="itemForm.description" rows="3" placeholder="Opsional"></textarea>
            </div>

            <div class="form-group">
              <label>Gambar (JPG/PNG)</label>
              <input ref="imageInput" type="file" accept="image/jpeg,image/png,image/jpg" @change="handleImageChange" />
              <p v-if="imageName" class="form-hint">File: {{ imageName }}</p>
              <p class="form-hint">Maks 2MB.</p>
            </div>

            <div v-if="itemError" class="error-message">{{ itemError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeItemModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="itemSaving" class="btn-primary">
                {{ itemSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- CATEGORY MODAL -->
      <div v-if="showCategoryModal" class="modal-overlay" @click="closeCategoryModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
            <button @click="closeCategoryModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveCategory" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Kode *</label>
                <input v-model="categoryForm.code" required maxlength="10" placeholder="Contoh: MEU" />
              </div>
              <div class="form-group">
                <label>Nama *</label>
                <input v-model="categoryForm.name" required maxlength="100" />
              </div>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="categoryForm.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" v-model="categoryForm.is_active" />
                Aktif
              </label>
            </div>

            <div v-if="categoryError" class="error-message">{{ categoryError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeCategoryModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="categorySaving" class="btn-primary">
                {{ categorySaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- TRANSACTION MODAL -->
      <div v-if="showTransactionModal" class="modal-overlay" @click="closeTransactionModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Catat Transaksi</h3>
            <button @click="closeTransactionModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveTransaction" class="modal-body">
            <div class="form-group">
              <label>Barang *</label>
              <select v-model="transactionForm.item_id" required>
                <option value="">Pilih Barang</option>
                <option v-for="it in itemOptions" :key="it.id" :value="it.id">
                  {{ it.code }} - {{ it.name }}
                </option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenis Transaksi *</label>
                <select v-model="transactionForm.transaction_type" required>
                  <option value="Masuk">Masuk</option>
                  <option value="Keluar">Keluar</option>
                  <option value="Mutasi">Mutasi</option>
                  <option value="Penyesuaian">Penyesuaian</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal *</label>
                <input type="date" v-model="transactionForm.transaction_date" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jumlah *</label>
                <input type="number" v-model.number="transactionForm.quantity" min="1" required />
              </div>
              <div class="form-group">
                <label>No. Referensi</label>
                <input v-model="transactionForm.reference_number" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Dari Ruangan</label>
                <select v-model="transactionForm.from_location_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Ke Ruangan</label>
                <select v-model="transactionForm.to_location_id">
                  <option value="">(Opsional)</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="transactionForm.notes" rows="3"></textarea>
            </div>

            <div v-if="transactionError" class="error-message">{{ transactionError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeTransactionModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="transactionSaving" class="btn-primary">
                {{ transactionSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- MAINTENANCE MODAL -->
      <div v-if="showMaintenanceModal" class="modal-overlay" @click="closeMaintenanceModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingMaintenance ? 'Edit Pemeliharaan' : 'Tambah Pemeliharaan' }}</h3>
            <button @click="closeMaintenanceModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveMaintenance" class="modal-body">
            <div class="form-group">
              <label>Barang *</label>
              <select v-model="maintenanceForm.item_id" required :disabled="!!editingMaintenance">
                <option value="">Pilih Barang</option>
                <option v-for="it in itemOptions" :key="it.id" :value="it.id">{{ it.code }} - {{ it.name }}</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenis Pemeliharaan *</label>
                <select v-model="maintenanceForm.maintenance_type" required>
                  <option value="Perawatan">Perawatan</option>
                  <option value="Perbaikan">Perbaikan</option>
                  <option value="Kalibrasi">Kalibrasi</option>
                  <option value="Inspeksi">Inspeksi</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="maintenanceForm.status">
                  <option value="Terjadwal">Terjadwal</option>
                  <option value="Dalam Proses">Dalam Proses</option>
                  <option value="Selesai">Selesai</option>
                  <option value="Dibatalkan">Dibatalkan</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Jadwal *</label>
                <input type="date" v-model="maintenanceForm.scheduled_date" required />
              </div>
              <div class="form-group">
                <label>Tanggal Selesai</label>
                <input type="date" v-model="maintenanceForm.completed_date" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Biaya (Rp)</label>
                <input type="number" v-model.number="maintenanceForm.cost" min="0" step="0.01" />
              </div>
              <div class="form-group">
                <label>Vendor / Teknisi</label>
                <input v-model="maintenanceForm.vendor" placeholder="Nama vendor atau teknisi" />
              </div>
            </div>

            <div class="form-group">
              <label>Nama Teknisi</label>
              <input v-model="maintenanceForm.technician_name" />
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="maintenanceForm.description" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="maintenanceForm.notes" rows="2"></textarea>
            </div>

            <div v-if="maintenanceError" class="error-message">{{ maintenanceError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeMaintenanceModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="maintenanceSaving" class="btn-primary">
                {{ maintenanceSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- LOAN MODAL -->
      <div v-if="showLoanModal" class="modal-overlay" @click="closeLoanModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tambah Peminjaman</h3>
            <button @click="closeLoanModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="saveLoan" class="modal-body">
            <div class="form-group">
              <label>Barang *</label>
              <select v-model="loanForm.item_id" required @change="onLoanItemChange">
                <option value="">Pilih Barang</option>
                <option v-for="it in availableItemOptions" :key="it.id" :value="it.id">
                  {{ it.code }} - {{ it.name }} (Tersedia: {{ it.quantity || 0 }})
                </option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenis Peminjam *</label>
                <select v-model="loanForm.borrower_type" required>
                  <option value="Employee">Pegawai</option>
                  <option value="Student">Siswa</option>
                  <option value="External">Eksternal</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jumlah *</label>
                <input type="number" v-model.number="loanForm.quantity" min="1" required />
              </div>
            </div>

            <div class="form-group">
              <label>Nama Peminjam *</label>
              <input v-model="loanForm.borrower_name" required placeholder="Nama lengkap" />
            </div>

            <div class="form-group">
              <label>No. Telepon</label>
              <input v-model="loanForm.borrower_phone" placeholder="08xxxxxxxxxx" />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Pinjam *</label>
                <input type="date" v-model="loanForm.loan_date" required />
              </div>
              <div class="form-group">
                <label>Tanggal Jatuh Tempo *</label>
                <input type="date" v-model="loanForm.expected_return_date" required />
              </div>
            </div>

            <div class="form-group">
              <label>Tujuan Peminjaman</label>
              <input v-model="loanForm.purpose" placeholder="Opsional" />
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="loanForm.notes" rows="2"></textarea>
            </div>

            <div v-if="loanError" class="error-message">{{ loanError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeLoanModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="loanSaving" class="btn-primary">
                {{ loanSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- RETURN LOAN MODAL -->
      <div v-if="showReturnLoanModal" class="modal-overlay" @click="closeReturnLoanModal">
        <div class="modal-content modal-content-sm" @click.stop>
          <div class="modal-header">
            <h3>Pengembalian Barang</h3>
            <button @click="closeReturnLoanModal" class="btn-close">×</button>
          </div>

          <form @submit.prevent="submitReturnLoan" class="modal-body">
            <p v-if="returningLoan" class="muted">
              {{ returningLoan.item?.name }} — dipinjam oleh {{ returningLoan.borrower_name }}
            </p>
            <div class="form-group">
              <label>Tanggal Dikembalikan *</label>
              <input type="date" v-model="returnLoanForm.actual_return_date" required />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="returnLoanForm.notes" rows="2"></textarea>
            </div>
            <div v-if="returnLoanError" class="error-message">{{ returnLoanError }}</div>
            <div class="modal-footer">
              <button type="button" @click="closeReturnLoanModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="returnLoanSaving" class="btn-primary">
                {{ returnLoanSaving ? 'Menyimpan...' : 'Catat Pengembalian' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
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
  </Layout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { inventoryApi } from '@/api/inventory'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const activeTab = ref('items')

// Shared options
const buildings = ref([])
const rooms = ref([])

const safeArray = (response) => {
  if (response?.data?.data && Array.isArray(response.data.data)) return response.data.data
  if (Array.isArray(response?.data)) return response.data
  return []
}

const parsePagination = (response, fallback = { current_page: 1, last_page: 1, per_page: 15, total: 0 }) => {
  const meta = response?.data?.meta || response?.data?.data?.meta
  if (meta) {
    return {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      per_page: meta.per_page || 15,
      total: meta.total || 0
    }
  }
  return fallback
}

// ==================== Categories state ====================
const categories = ref([])
const categoriesLoading = ref(false)
const categoriesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const categoryFilters = ref({ search: '', is_active: '' })
const categorySearchTimeout = ref(null)

const loadCategories = async (page = 1) => {
  categoriesLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (categoryFilters.value.search) params.search = categoryFilters.value.search
    if (categoryFilters.value.is_active !== '') params.is_active = categoryFilters.value.is_active

    const res = await inventoryApi.getCategories(params)
    categories.value = safeArray(res)
    categoriesMeta.value = parsePagination(res, categoriesMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat kategori'
    toast.error('Gagal', msg)
    categories.value = []
  } finally {
    categoriesLoading.value = false
  }
}

const debounceLoadCategories = () => {
  if (categorySearchTimeout.value) clearTimeout(categorySearchTimeout.value)
  categorySearchTimeout.value = setTimeout(() => loadCategories(1), 400)
}

// Category modal
const showCategoryModal = ref(false)
const editingCategory = ref(null)
const categorySaving = ref(false)
const categoryError = ref('')
const categoryForm = ref({
  code: '',
  name: '',
  description: '',
  is_active: true
})

const openCategoryModal = (cat = null) => {
  editingCategory.value = cat
  categoryError.value = ''
  categoryForm.value = cat
    ? {
        code: cat.code || '',
        name: cat.name || '',
        description: cat.description || '',
        is_active: !!cat.is_active
      }
    : { code: '', name: '', description: '', is_active: true }
  showCategoryModal.value = true
}

const closeCategoryModal = () => {
  showCategoryModal.value = false
  editingCategory.value = null
  categoryError.value = ''
}

const saveCategory = async () => {
  categorySaving.value = true
  categoryError.value = ''
  try {
    const payload = {
      code: (categoryForm.value.code || '').trim(),
      name: (categoryForm.value.name || '').trim(),
      description: categoryForm.value.description ? categoryForm.value.description.trim() : null,
      is_active: !!categoryForm.value.is_active
    }

    if (editingCategory.value) {
      await inventoryApi.updateCategory(editingCategory.value.id, payload)
      toast.success('Berhasil', 'Kategori berhasil diperbarui')
    } else {
      await inventoryApi.createCategory(payload)
      toast.success('Berhasil', 'Kategori berhasil ditambahkan')
    }

    closeCategoryModal()
    await loadCategories(categoriesMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan kategori'
    categoryError.value = msg
    toast.error('Gagal', msg)
  } finally {
    categorySaving.value = false
  }
}

const deleteCategory = async (cat) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: `Hapus kategori "${cat.name}"?`,
    warning: 'Kategori akan dihapus secara permanen.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteCategory(cat.id)
    toast.success('Berhasil', 'Kategori berhasil dihapus')
    await loadCategories(categoriesMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menghapus kategori'
    toast.error('Gagal', msg)
  } finally {
    setDeleteLoading(false)
  }
}

// ==================== Items state ====================
const items = ref([])
const itemsLoading = ref(false)
const itemsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const itemFilters = ref({
  search: '',
  category_id: '',
  status: '',
  condition: '',
  room_id: '',
  building_id: ''
})
const itemSearchTimeout = ref(null)

const loadItems = async (page = 1) => {
  itemsLoading.value = true
  try {
    const params = { page, per_page: 15 }
    Object.entries(itemFilters.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') params[k] = v
    })

    const res = await inventoryApi.getItems(params)
    items.value = safeArray(res)
    itemsMeta.value = parsePagination(res, itemsMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat barang'
    toast.error('Gagal', msg)
    items.value = []
  } finally {
    itemsLoading.value = false
  }
}

const debounceLoadItems = () => {
  if (itemSearchTimeout.value) clearTimeout(itemSearchTimeout.value)
  itemSearchTimeout.value = setTimeout(() => loadItems(1), 450)
}

// Item modal
const showItemModal = ref(false)
const editingItem = ref(null)
const itemSaving = ref(false)
const itemError = ref('')
const imageInput = ref(null)
const imageName = ref('')

const itemForm = ref({
  category_id: '',
  code: '',
  custom_code: '',
  reference_number: '',
  name: '',
  brand: '',
  model: '',
  serial_number: '',
  purchase_date: '',
  purchase_price: '',
  supplier: '',
  condition: 'Baik',
  status: 'Tersedia',
  quantity: 1,
  unit: 'Unit',
  room_id: '',
  building_id: '',
  location_note: '',
  warranty_expiry: '',
  description: '',
  image: null
})

const openItemModal = async (it = null) => {
  // ensure option lists
  if (categories.value.length === 0) await loadCategories(1)
  if (buildings.value.length === 0) await loadBuildings()
  if (rooms.value.length === 0) await loadRooms()

  editingItem.value = it
  itemError.value = ''
  imageName.value = ''
  if (imageInput.value) imageInput.value.value = ''

  if (it) {
    itemForm.value = {
      category_id: it.category_id || it.category?.id || '',
      code: it.code || '',
      custom_code: '',
      reference_number: '',
      name: it.name || '',
      brand: it.brand || '',
      model: it.model || '',
      serial_number: it.serial_number || '',
      purchase_date: it.purchase_date ? String(it.purchase_date).split('T')[0] : '',
      purchase_price: it.purchase_price || '',
      supplier: it.supplier || '',
      condition: it.condition || 'Baik',
      status: it.status || 'Tersedia',
      quantity: it.quantity || 1,
      unit: it.unit || 'Unit',
      room_id: it.room_id || it.room?.id || '',
      building_id: it.building_id || it.building?.id || '',
      location_note: it.location_note || '',
      warranty_expiry: it.warranty_expiry ? String(it.warranty_expiry).split('T')[0] : '',
      description: it.description || '',
      image: null
    }
  } else {
    itemForm.value = {
      category_id: '',
      code: '',
      custom_code: '',
      reference_number: '',
      name: '',
      brand: '',
      model: '',
      serial_number: '',
      purchase_date: '',
      purchase_price: '',
      supplier: '',
      condition: 'Baik',
      status: 'Tersedia',
      quantity: 1,
      unit: 'Unit',
      room_id: '',
      building_id: '',
      location_note: '',
      warranty_expiry: '',
      description: '',
      image: null
    }
  }

  showItemModal.value = true
}

const closeItemModal = () => {
  showItemModal.value = false
  editingItem.value = null
  itemError.value = ''
}

const handleImageChange = (e) => {
  const file = e.target?.files?.[0]
  if (!file) {
    itemForm.value.image = null
    imageName.value = ''
    return
  }
  itemForm.value.image = file
  imageName.value = file.name
}

const saveItem = async () => {
  itemSaving.value = true
  itemError.value = ''
  try {
    const basePayload = {
      category_id: itemForm.value.category_id,
      code: itemForm.value.code ? itemForm.value.code.trim() : null,
      name: itemForm.value.name ? itemForm.value.name.trim() : null,
      brand: itemForm.value.brand ? itemForm.value.brand.trim() : null,
      model: itemForm.value.model ? itemForm.value.model.trim() : null,
      serial_number: itemForm.value.serial_number ? itemForm.value.serial_number.trim() : null,
      purchase_date: itemForm.value.purchase_date || null,
      purchase_price: itemForm.value.purchase_price !== '' ? itemForm.value.purchase_price : null,
      supplier: itemForm.value.supplier ? itemForm.value.supplier.trim() : null,
      condition: itemForm.value.condition,
      status: itemForm.value.status,
      quantity: itemForm.value.quantity,
      unit: itemForm.value.unit ? itemForm.value.unit.trim() : null,
      room_id: itemForm.value.room_id || null,
      building_id: itemForm.value.building_id || null,
      location_note: itemForm.value.location_note ? itemForm.value.location_note.trim() : null,
      warranty_expiry: itemForm.value.warranty_expiry || null,
      description: itemForm.value.description ? itemForm.value.description.trim() : null,
      image: itemForm.value.image || null
    }

    if (editingItem.value) {
      // Update request does not accept custom_code/reference_number; omit.
      await inventoryApi.updateItem(editingItem.value.id, basePayload)
      toast.success('Berhasil', 'Barang berhasil diperbarui')
    } else {
      const payload = {
        ...basePayload,
        custom_code: itemForm.value.custom_code ? itemForm.value.custom_code.trim() : null,
        reference_number: itemForm.value.reference_number ? itemForm.value.reference_number.trim() : null
      }
      await inventoryApi.createItem(payload)
      toast.success('Berhasil', 'Barang berhasil ditambahkan')
    }

    closeItemModal()
    await loadItems(itemsMeta.value.current_page || 1)
    // refresh transaction tab item options
    await loadItemOptions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan barang'
    itemError.value = msg
    toast.error('Gagal', msg)
  } finally {
    itemSaving.value = false
  }
}

const deleteItem = async (it) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus',
    message: `Hapus barang "${it.name}"?`,
    warning: 'Barang akan dihapus secara permanen dan tidak dapat dikembalikan.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await inventoryApi.deleteItem(it.id)
    toast.success('Berhasil', 'Barang berhasil dihapus')
    await loadItems(itemsMeta.value.current_page || 1)
    await loadItemOptions()
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menghapus barang'
    toast.error('Gagal', msg)
  } finally {
    setDeleteLoading(false)
  }
}

// ==================== Transactions state ====================
const transactions = ref([])
const transactionsLoading = ref(false)
const transactionsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const transactionFilters = ref({ transaction_type: '', date_from: '', date_to: '' })

const loadTransactions = async (page = 1) => {
  transactionsLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (transactionFilters.value.transaction_type) params.transaction_type = transactionFilters.value.transaction_type
    if (transactionFilters.value.date_from) params.date_from = transactionFilters.value.date_from
    if (transactionFilters.value.date_to) params.date_to = transactionFilters.value.date_to

    const res = await inventoryApi.getTransactions(params)
    transactions.value = safeArray(res)
    transactionsMeta.value = parsePagination(res, transactionsMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat transaksi'
    toast.error('Gagal', msg)
    transactions.value = []
  } finally {
    transactionsLoading.value = false
  }
}

// Transaction modal
const showTransactionModal = ref(false)
const transactionSaving = ref(false)
const transactionError = ref('')
const itemOptions = ref([])

const transactionForm = ref({
  item_id: '',
  transaction_type: 'Masuk',
  transaction_date: new Date().toISOString().split('T')[0],
  quantity: 1,
  reference_number: '',
  from_location_id: '',
  to_location_id: '',
  notes: ''
})

const loadItemOptions = async () => {
  try {
    const res = await inventoryApi.getItems({ per_page: 100 })
    itemOptions.value = safeArray(res)
  } catch {
    itemOptions.value = []
  }
}

const openTransactionModal = async (it = null) => {
  if (rooms.value.length === 0) await loadRooms()
  if (itemOptions.value.length === 0) await loadItemOptions()

  transactionError.value = ''
  transactionForm.value = {
    item_id: it?.id || '',
    transaction_type: 'Masuk',
    transaction_date: new Date().toISOString().split('T')[0],
    quantity: 1,
    reference_number: '',
    from_location_id: '',
    to_location_id: '',
    notes: ''
  }
  showTransactionModal.value = true
}

const closeTransactionModal = () => {
  showTransactionModal.value = false
  transactionError.value = ''
}

const saveTransaction = async () => {
  transactionSaving.value = true
  transactionError.value = ''
  try {
    const payload = {
      item_id: transactionForm.value.item_id,
      transaction_type: transactionForm.value.transaction_type,
      transaction_date: transactionForm.value.transaction_date,
      quantity: transactionForm.value.quantity,
      reference_number: transactionForm.value.reference_number ? transactionForm.value.reference_number.trim() : null,
      from_location_id: transactionForm.value.from_location_id || null,
      to_location_id: transactionForm.value.to_location_id || null,
      notes: transactionForm.value.notes ? transactionForm.value.notes.trim() : null
    }
    await inventoryApi.createTransaction(payload)
    toast.success('Berhasil', 'Transaksi berhasil dicatat')
    closeTransactionModal()
    await loadTransactions(transactionsMeta.value.current_page || 1)
    await loadItems(itemsMeta.value.current_page || 1) // reflect qty/location changes
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan transaksi'
    transactionError.value = msg
    toast.error('Gagal', msg)
  } finally {
    transactionSaving.value = false
  }
}

// ==================== Facility options loaders ====================
const loadBuildings = async () => {
  try {
    const res = await facilityApi.getBuildings({})
    buildings.value = safeArray(res)
  } catch {
    buildings.value = []
  }
}

const loadRooms = async () => {
  try {
    const res = await facilityApi.getRooms({})
    rooms.value = safeArray(res)
  } catch {
    rooms.value = []
  }
}

// ==================== UI helpers ====================
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return String(dateString)
  return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' })
}

const getConditionClass = (condition) => {
  const map = {
    Baik: 'badge-success',
    'Rusak Ringan': 'badge-warning',
    'Rusak Berat': 'badge-danger',
    'Habis Pakai': 'badge-gray'
  }
  return map[condition] || 'badge-gray'
}

const getStatusClass = (status) => {
  const map = {
    Tersedia: 'badge-success',
    Dipinjam: 'badge-info',
    Rusak: 'badge-warning',
    Hilang: 'badge-danger',
    Dijual: 'badge-gray'
  }
  return map[status] || 'badge-gray'
}

const getTransactionTypeClass = (type) => {
  const map = {
    Masuk: 'badge-success',
    Keluar: 'badge-danger',
    Mutasi: 'badge-info',
    Penyesuaian: 'badge-warning'
  }
  return map[type] || 'badge-gray'
}

const getMaintenanceStatusClass = (status) => {
  const map = {
    Terjadwal: 'badge-info',
    'Dalam Proses': 'badge-warning',
    Selesai: 'badge-success',
    Dibatalkan: 'badge-gray'
  }
  return map[status] || 'badge-gray'
}

const formatCurrency = (num) => {
  if (num == null || num === '') return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num)
}

// ==================== Maintenances state ====================
const maintenances = ref([])
const maintenancesLoading = ref(false)
const maintenancesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const maintenanceFilters = ref({ item_id: '', maintenance_type: '', status: '' })

const loadMaintenances = async (page = 1) => {
  maintenancesLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (maintenanceFilters.value.item_id) params.item_id = maintenanceFilters.value.item_id
    if (maintenanceFilters.value.maintenance_type) params.maintenance_type = maintenanceFilters.value.maintenance_type
    if (maintenanceFilters.value.status) params.status = maintenanceFilters.value.status

    const res = await inventoryApi.getMaintenances(params)
    maintenances.value = safeArray(res)
    maintenancesMeta.value = parsePagination(res, maintenancesMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat pemeliharaan'
    toast.error('Gagal', msg)
    maintenances.value = []
  } finally {
    maintenancesLoading.value = false
  }
}

const showMaintenanceModal = ref(false)
const editingMaintenance = ref(null)
const maintenanceSaving = ref(false)
const maintenanceError = ref('')
const maintenanceForm = ref({
  item_id: '',
  maintenance_type: 'Perawatan',
  scheduled_date: new Date().toISOString().split('T')[0],
  completed_date: '',
  cost: null,
  vendor: '',
  description: '',
  status: 'Terjadwal',
  technician_name: '',
  notes: ''
})

const openMaintenanceModal = async (m = null) => {
  if (itemOptions.value.length === 0) await loadItemOptions()
  editingMaintenance.value = m
  maintenanceError.value = ''
  if (m) {
    maintenanceForm.value = {
      item_id: m.item_id || m.item?.id,
      maintenance_type: m.maintenance_type || 'Perawatan',
      scheduled_date: m.scheduled_date ? String(m.scheduled_date).split('T')[0] : '',
      completed_date: m.completed_date ? String(m.completed_date).split('T')[0] : '',
      cost: m.cost ?? null,
      vendor: m.vendor || '',
      description: m.description || '',
      status: m.status || 'Terjadwal',
      technician_name: m.technician_name || '',
      notes: m.notes || ''
    }
  } else {
    maintenanceForm.value = {
      item_id: '',
      maintenance_type: 'Perawatan',
      scheduled_date: new Date().toISOString().split('T')[0],
      completed_date: '',
      cost: null,
      vendor: '',
      description: '',
      status: 'Terjadwal',
      technician_name: '',
      notes: ''
    }
  }
  showMaintenanceModal.value = true
}

const closeMaintenanceModal = () => {
  showMaintenanceModal.value = false
  editingMaintenance.value = null
  maintenanceError.value = ''
}

const saveMaintenance = async () => {
  maintenanceSaving.value = true
  maintenanceError.value = ''
  try {
    const payload = {
      item_id: maintenanceForm.value.item_id,
      maintenance_type: maintenanceForm.value.maintenance_type,
      scheduled_date: maintenanceForm.value.scheduled_date,
      completed_date: maintenanceForm.value.completed_date || null,
      cost: maintenanceForm.value.cost ?? null,
      vendor: maintenanceForm.value.vendor?.trim() || null,
      description: maintenanceForm.value.description?.trim() || null,
      status: maintenanceForm.value.status,
      technician_name: maintenanceForm.value.technician_name?.trim() || null,
      notes: maintenanceForm.value.notes?.trim() || null
    }
    if (editingMaintenance.value) {
      await inventoryApi.updateMaintenance(editingMaintenance.value.id, payload)
      toast.success('Berhasil', 'Pemeliharaan berhasil diperbarui')
    } else {
      await inventoryApi.createMaintenance(payload)
      toast.success('Berhasil', 'Pemeliharaan berhasil ditambahkan')
    }
    closeMaintenanceModal()
    await loadMaintenances(maintenancesMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan pemeliharaan'
    maintenanceError.value = msg
    toast.error('Gagal', msg)
  } finally {
    maintenanceSaving.value = false
  }
}

// ==================== Loans state ====================
const loans = ref([])
const loansLoading = ref(false)
const loansMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const loanFilters = ref({ item_id: '', borrower_type: '', status: '' })

const loadLoans = async (page = 1) => {
  loansLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (loanFilters.value.item_id) params.item_id = loanFilters.value.item_id
    if (loanFilters.value.borrower_type) params.borrower_type = loanFilters.value.borrower_type
    if (loanFilters.value.status) params.status = loanFilters.value.status

    const res = await inventoryApi.getLoans(params)
    loans.value = safeArray(res)
    loansMeta.value = parsePagination(res, loansMeta.value)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal memuat peminjaman'
    toast.error('Gagal', msg)
    loans.value = []
  } finally {
    loansLoading.value = false
  }
}

const showLoanModal = ref(false)
const loanSaving = ref(false)
const loanError = ref('')
const loanForm = ref({
  item_id: '',
  borrower_type: 'Employee',
  borrower_name: '',
  borrower_phone: '',
  loan_date: new Date().toISOString().split('T')[0],
  expected_return_date: '',
  quantity: 1,
  purpose: '',
  notes: ''
})

const availableItemOptions = ref([])
const loadAvailableItemOptions = async () => {
  try {
    const res = await inventoryApi.getItems({ per_page: 200, status: 'Tersedia' })
    const list = safeArray(res)
    availableItemOptions.value = list.filter(it => (it.quantity || 0) > 0)
  } catch {
    availableItemOptions.value = []
  }
}

const onLoanItemChange = () => {
  const it = availableItemOptions.value.find(i => i.id === Number(loanForm.value.item_id))
  if (it) loanForm.value.quantity = Math.min(loanForm.value.quantity || 1, it.quantity || 1)
}

const openLoanModal = async () => {
  await loadAvailableItemOptions()
  loanError.value = ''
  const today = new Date().toISOString().split('T')[0]
  const nextWeek = new Date()
  nextWeek.setDate(nextWeek.getDate() + 7)
  loanForm.value = {
    item_id: '',
    borrower_type: 'Employee',
    borrower_name: '',
    borrower_phone: '',
    loan_date: today,
    expected_return_date: nextWeek.toISOString().split('T')[0],
    quantity: 1,
    purpose: '',
    notes: ''
  }
  showLoanModal.value = true
}

const closeLoanModal = () => {
  showLoanModal.value = false
  loanError.value = ''
}

const saveLoan = async () => {
  loanSaving.value = true
  loanError.value = ''
  try {
    const payload = {
      item_id: loanForm.value.item_id,
      borrower_type: loanForm.value.borrower_type,
      borrower_name: loanForm.value.borrower_name?.trim() || '',
      borrower_phone: loanForm.value.borrower_phone?.trim() || null,
      loan_date: loanForm.value.loan_date,
      expected_return_date: loanForm.value.expected_return_date,
      quantity: loanForm.value.quantity,
      purpose: loanForm.value.purpose?.trim() || null,
      notes: loanForm.value.notes?.trim() || null
    }
    await inventoryApi.createLoan(payload)
    toast.success('Berhasil', 'Peminjaman berhasil dicatat')
    closeLoanModal()
    await loadLoans(loansMeta.value.current_page || 1)
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan peminjaman'
    loanError.value = msg
    toast.error('Gagal', msg)
  } finally {
    loanSaving.value = false
  }
}

// Return loan modal
const showReturnLoanModal = ref(false)
const returningLoan = ref(null)
const returnLoanSaving = ref(false)
const returnLoanError = ref('')
const returnLoanForm = ref({ actual_return_date: new Date().toISOString().split('T')[0], notes: '' })

const openReturnLoanModal = (loan) => {
  returningLoan.value = loan
  returnLoanError.value = ''
  returnLoanForm.value = {
    actual_return_date: new Date().toISOString().split('T')[0],
    notes: ''
  }
  showReturnLoanModal.value = true
}

const closeReturnLoanModal = () => {
  showReturnLoanModal.value = false
  returningLoan.value = null
  returnLoanError.value = ''
}

const submitReturnLoan = async () => {
  if (!returningLoan.value) return
  returnLoanSaving.value = true
  returnLoanError.value = ''
  try {
    await inventoryApi.returnLoan(returningLoan.value.id, {
      actual_return_date: returnLoanForm.value.actual_return_date,
      notes: returnLoanForm.value.notes?.trim() || null
    })
    toast.success('Berhasil', 'Pengembalian berhasil dicatat')
    closeReturnLoanModal()
    await loadLoans(loansMeta.value.current_page || 1)
    await loadItems(itemsMeta.value.current_page || 1)
  } catch (err) {
    const msg = err.formattedMessage || err.response?.data?.message || 'Gagal mencatat pengembalian'
    returnLoanError.value = msg
    toast.error('Gagal', msg)
  } finally {
    returnLoanSaving.value = false
  }
}

// ==================== Reports state ====================
const reportStats = ref(null)
const reportStatsLoading = ref(false)
const reportDamaged = ref([])
const reportDamagedLoading = ref(false)
const reportLoaned = ref([])
const reportLoanedLoading = ref(false)
const reportAsset = ref([])
const reportAssetLoading = ref(false)

const loadReportStats = async () => {
  reportStatsLoading.value = true
  try {
    const res = await inventoryApi.getReportStatistics({})
    reportStats.value = res.data?.data ?? res.data ?? null
  } catch {
    reportStats.value = null
  } finally {
    reportStatsLoading.value = false
  }
}

const loadReportDamaged = async () => {
  reportDamagedLoading.value = true
  try {
    const res = await inventoryApi.getReportDamagedMissing({})
    const data = res.data?.data ?? res.data ?? {}
    const damaged = (data.damaged || []).map(d => ({ ...d, statusLabel: d.condition || 'Rusak' }))
    const missing = (data.missing || []).map(m => ({ ...m, condition: '-', statusLabel: 'Hilang' }))
    reportDamaged.value = [...damaged, ...missing]
  } catch {
    reportDamaged.value = []
  } finally {
    reportDamagedLoading.value = false
  }
}

const loadReportLoaned = async () => {
  reportLoanedLoading.value = true
  try {
    const res = await inventoryApi.getReportLoaned({})
    const data = res.data?.data ?? res.data ?? {}
    reportLoaned.value = data.loans || []
  } catch {
    reportLoaned.value = []
  } finally {
    reportLoanedLoading.value = false
  }
}

const loadReportAsset = async () => {
  reportAssetLoading.value = true
  try {
    const res = await inventoryApi.getReportAssetValue({})
    const data = res.data?.data ?? res.data ?? {}
    reportAsset.value = data.by_category || []
  } catch {
    reportAsset.value = []
  } finally {
    reportAssetLoading.value = false
  }
}

watch(activeTab, async (tab) => {
  if (tab === 'items') {
    if (categories.value.length === 0) await loadCategories(1)
    if (buildings.value.length === 0) await loadBuildings()
    if (rooms.value.length === 0) await loadRooms()
    await loadItems(1)
  } else if (tab === 'categories') {
    await loadCategories(1)
  } else if (tab === 'transactions') {
    if (rooms.value.length === 0) await loadRooms()
    await loadItemOptions()
    await loadTransactions(1)
  } else if (tab === 'maintenances') {
    await loadItemOptions()
    await loadMaintenances(1)
  } else if (tab === 'loans') {
    await loadItemOptions()
    await loadLoans(1)
  } else if (tab === 'reports') {
    await loadReportStats()
    await loadReportDamaged()
    await loadReportLoaned()
    await loadReportAsset()
  }
})

onMounted(async () => {
  await Promise.all([loadBuildings(), loadRooms(), loadCategories(1)])
  await loadItems(1)
})
</script>

<style scoped>
.inventory-page {
  width: 100%;
  max-width: 100%;
}

.page-header {
  margin-bottom: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.tabs-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.tabs-nav {
  display: flex;
  gap: 4px;
  padding: 8px;
}

.tab-btn {
  flex: 1;
  padding: 12px 16px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
  transition: all 0.2s ease;
  border-radius: 12px;
}

.tab-btn:hover {
  background: #f8fafc;
  color: #667eea;
}

.tab-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.tab-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  padding: 24px;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.filters {
  display: flex;
  gap: 12px;
  flex: 1;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input {
  flex: 1;
  min-width: 260px;
}

.filter-select {
  min-width: 180px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.btn-primary {
  padding: 12px 18px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 700;
  font-size: 14px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(102, 126, 234, 0.35);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-state {
  padding: 48px 16px;
  text-align: center;
  color: #64748b;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.data-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
  vertical-align: top;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table.small th,
.data-table.small td {
  padding: 8px 12px;
  font-size: 13px;
}

.row-overdue {
  background: #fef2f2;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-action {
  border: none;
  border-radius: 10px;
  padding: 8px 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 12px;
  transition: all 0.2s ease;
}

.btn-edit {
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.18);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.18);
}

.btn-secondary {
  background: rgba(100, 116, 139, 0.12);
  color: #334155;
}

.btn-secondary:hover {
  background: rgba(100, 116, 139, 0.18);
}

.empty-state {
  text-align: center;
  padding: 56px 20px;
  color: #64748b;
}

.empty-state h3 {
  color: #1e293b;
  margin: 0 0 8px 0;
}

.empty-state p {
  margin: 0 0 16px 0;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  margin-top: 12px;
  border-radius: 12px;
}

.pagination-btn {
  padding: 8px 14px;
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 13px;
  color: #64748b;
}

.badge-success,
.badge-warning,
.badge-danger,
.badge-info,
.badge-gray {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-info {
  background: #dbeafe;
  color: #1e40af;
}

.badge-gray {
  background: #f1f5f9;
  color: #475569;
}

.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.report-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.report-card.full-width {
  grid-column: 1 / -1;
}

.report-card h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
  color: #1e293b;
}

.report-stats p,
.report-list {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #475569;
}

.report-list {
  margin-bottom: 12px;
  overflow-x: auto;
}

.report-card .btn-outline {
  margin-top: 8px;
}

.btn-outline {
  padding: 8px 14px;
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
}

.btn-outline:hover {
  background: #f8fafc;
  border-color: #667eea;
  color: #667eea;
}

.btn-compact {
  padding: 8px 14px;
  font-size: 13px;
}

.modal-content-sm {
  max-width: 420px;
}

.muted {
  color: #64748b;
}

.small {
  font-size: 12px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-content {
  background: white;
  border-radius: 18px;
  width: 100%;
  max-width: 820px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}

.btn-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  font-size: 20px;
  line-height: 1;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.modal-body {
  padding: 20px 24px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 14px;
}

.form-group label {
  font-size: 13px;
  font-weight: 800;
  color: #1e293b;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 12px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-hint {
  font-size: 12px;
  color: #64748b;
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  padding: 12px 18px;
  background: white;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 800;
}

.btn-secondary:hover {
  background: #f8fafc;
}

.error-message {
  padding: 14px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  border: 1px solid #fecaca;
  font-size: 14px;
  margin-top: 10px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  .filter-select {
    min-width: 160px;
  }
  .search-input {
    min-width: 220px;
  }
}
</style>

