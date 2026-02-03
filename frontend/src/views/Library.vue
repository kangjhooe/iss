<template>
  <Layout>
    <div class="library-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Perpustakaan</h2>
            <p>Katalog buku, eksemplar, peminjaman, dan laporan</p>
          </div>
        </div>
      </div>

      <div class="tabs-container">
        <div class="tabs-nav">
          <button @click="activeTab = 'books'" :class="['tab-btn', { active: activeTab === 'books' }]">
            <span>Katalog Buku</span>
          </button>
          <button @click="activeTab = 'categories'" :class="['tab-btn', { active: activeTab === 'categories' }]">
            <span>Kategori</span>
          </button>
          <button @click="activeTab = 'copies'" :class="['tab-btn', { active: activeTab === 'copies' }]">
            <span>Eksemplar</span>
          </button>
          <button @click="activeTab = 'loans'" :class="['tab-btn', { active: activeTab === 'loans' }]">
            <span>Peminjaman</span>
          </button>
          <button @click="activeTab = 'fines'" :class="['tab-btn', { active: activeTab === 'fines' }]">
            <span>Denda</span>
          </button>
          <button @click="activeTab = 'reports'" :class="['tab-btn', { active: activeTab === 'reports' }]">
            <span>Laporan</span>
          </button>
        </div>
      </div>

      <!-- BOOKS TAB -->
      <div v-show="activeTab === 'books'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="bookFilters.search"
              @input="debounceLoadBooks"
              placeholder="Cari judul, pengarang, ISBN..."
              class="search-input"
            />
            <select v-model="bookFilters.category_id" @change="loadBooks(1)" class="filter-select">
              <option value="">Semua Kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
            </select>
          </div>
          <button @click="openBookModal()" class="btn-primary"><span>Tambah Buku</span></button>
        </div>
        <div v-if="booksLoading" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Pengarang</th>
                <th>Kategori</th>
                <th>ISBN</th>
                <th>Eksemplar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in books" :key="b.id">
                <td>
                  <div class="name-cell">
                    <div class="name">{{ b.title }}</div>
                    <div v-if="b.publisher" class="muted small">{{ b.publisher }}{{ b.year ? ', ' + b.year : '' }}</div>
                  </div>
                </td>
                <td>{{ b.author || '-' }}</td>
                <td>{{ b.category?.name || '-' }}</td>
                <td>{{ b.isbn || '-' }}</td>
                <td>{{ b.copies_count ?? b.available_copies_count ?? 0 }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="openBookModal(b)" class="btn-action btn-edit">Edit</button>
                    <button @click="openCopyModal(null, b)" class="btn-action btn-secondary">Eksemplar</button>
                    <button @click="deleteBook(b)" class="btn-action btn-delete">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="books.length === 0" class="empty-state">
            <h3>Tidak ada buku</h3>
            <p>Tambahkan kategori lalu tambah buku.</p>
            <button @click="openBookModal()" class="btn-primary">Tambah Buku</button>
          </div>
          <div v-if="booksMeta.last_page > 1" class="pagination">
            <button @click="loadBooks(booksMeta.current_page - 1)" :disabled="booksMeta.current_page === 1" class="pagination-btn">Sebelumnya</button>
            <span class="pagination-info">Halaman {{ booksMeta.current_page }} dari {{ booksMeta.last_page }} (Total: {{ booksMeta.total }})</span>
            <button @click="loadBooks(booksMeta.current_page + 1)" :disabled="booksMeta.current_page >= booksMeta.last_page" class="pagination-btn">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- CATEGORIES TAB -->
      <div v-show="activeTab === 'categories'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input v-model="categoryFilters.search" @input="debounceLoadCategories" placeholder="Cari kode / nama..." class="search-input" />
            <select v-model="categoryFilters.is_active" @change="loadCategories(1)" class="filter-select">
              <option value="">Semua</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button @click="openCategoryModal()" class="btn-primary"><span>Tambah Kategori</span></button>
        </div>
        <div v-if="categoriesLoading" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="c in categories" :key="c.id">
                <td>{{ c.code }}</td>
                <td><div class="name-cell"><div class="name">{{ c.name }}</div><div v-if="c.description" class="muted small">{{ c.description }}</div></div></td>
                <td><span :class="c.is_active ? 'badge-success' : 'badge-gray'">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td>
                  <div class="action-buttons">
                    <button @click="openCategoryModal(c)" class="btn-action btn-edit">Edit</button>
                    <button @click="deleteCategory(c)" class="btn-action btn-delete">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="categories.length === 0" class="empty-state">
            <h3>Tidak ada kategori</h3>
            <p>Tambahkan kategori buku terlebih dahulu.</p>
            <button @click="openCategoryModal()" class="btn-primary">Tambah Kategori</button>
          </div>
          <div v-if="categoriesMeta.last_page > 1" class="pagination">
            <button @click="loadCategories(categoriesMeta.current_page - 1)" :disabled="categoriesMeta.current_page === 1" class="pagination-btn">Sebelumnya</button>
            <span class="pagination-info">Halaman {{ categoriesMeta.current_page }} dari {{ categoriesMeta.last_page }}</span>
            <button @click="loadCategories(categoriesMeta.current_page + 1)" :disabled="categoriesMeta.current_page >= categoriesMeta.last_page" class="pagination-btn">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- COPIES TAB -->
      <div v-show="activeTab === 'copies'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input v-model="copyFilters.search" @input="debounceLoadCopies" placeholder="Kode eksemplar..." class="search-input" />
            <select v-model="copyFilters.book_id" @change="loadCopies(1)" class="filter-select">
              <option value="">Semua Buku</option>
              <option v-for="b in booksList" :key="b.id" :value="b.id">{{ b.title }}</option>
            </select>
            <select v-model="copyFilters.status" @change="loadCopies(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Tersedia">Tersedia</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Rusak">Rusak</option>
              <option value="Hilang">Hilang</option>
            </select>
          </div>
          <button @click="openCopyModal()" class="btn-primary"><span>Tambah Eksemplar</span></button>
        </div>
        <div v-if="copiesLoading" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Kode</th><th>Buku</th><th>Status</th><th>Kondisi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="cp in copies" :key="cp.id">
                <td><strong>{{ cp.copy_code }}</strong></td>
                <td><div class="name-cell"><div class="name">{{ cp.book?.title }}</div><div class="muted small">{{ cp.book?.author }}</div></div></td>
                <td><span :class="getCopyStatusClass(cp.status)">{{ cp.status }}</span></td>
                <td>{{ cp.condition || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="openCopyModal(cp)" class="btn-action btn-edit">Edit</button>
                    <button v-if="cp.status === 'Tersedia'" @click="openLoanModal(cp)" class="btn-action btn-secondary">Pinjam</button>
                    <button @click="deleteCopy(cp)" class="btn-action btn-delete">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="copies.length === 0" class="empty-state">
            <h3>Tidak ada eksemplar</h3>
            <p>Tambahkan buku lalu tambah eksemplar.</p>
            <button @click="openCopyModal()" class="btn-primary">Tambah Eksemplar</button>
          </div>
          <div v-if="copiesMeta.last_page > 1" class="pagination">
            <button @click="loadCopies(copiesMeta.current_page - 1)" :disabled="copiesMeta.current_page === 1" class="pagination-btn">Sebelumnya</button>
            <span class="pagination-info">Halaman {{ copiesMeta.current_page }} dari {{ copiesMeta.last_page }}</span>
            <button @click="loadCopies(copiesMeta.current_page + 1)" :disabled="copiesMeta.current_page >= copiesMeta.last_page" class="pagination-btn">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- LOANS TAB -->
      <div v-show="activeTab === 'loans'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input v-model="loanFilters.search" @input="debounceLoadLoans" placeholder="Cari peminjam..." class="search-input" />
            <select v-model="loanFilters.status" @change="loadLoans(1)" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Dipinjam">Dipinjam</option>
              <option value="Terlambat">Terlambat</option>
              <option value="Dikembalikan">Dikembalikan</option>
            </select>
            <select v-model="loanFilters.borrower_type" @change="loadLoans(1)" class="filter-select">
              <option value="">Semua Tipe</option>
              <option value="Student">Siswa</option>
              <option value="Employee">Guru/Karyawan</option>
              <option value="External">Tamu</option>
            </select>
          </div>
          <button @click="openLoanModal()" class="btn-primary"><span>Catat Peminjaman</span></button>
        </div>
        <div v-if="loansLoading" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Peminjam</th><th>Buku / Eksemplar</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Denda</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="ln in loans" :key="ln.id">
                <td>
                  <div class="name-cell">
                    <div class="name">{{ ln.borrower_name }}</div>
                    <div class="muted small">{{ ln.borrower_type }} {{ ln.borrower_identifier ? ' · ' + ln.borrower_identifier : '' }}</div>
                  </div>
                </td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ ln.copy?.book?.title }}</div>
                    <div class="muted small">Eks: {{ ln.copy?.copy_code }}</div>
                  </div>
                </td>
                <td>{{ ln.loan_date }}</td>
                <td>{{ ln.due_date }}</td>
                <td><span :class="getLoanStatusClass(ln.status)">{{ ln.status }}</span></td>
                <td>Rp {{ formatNumber(ln.fine_amount || 0) }} <span v-if="ln.remaining_fine > 0" class="muted">(sisa: {{ formatNumber(ln.remaining_fine) }})</span></td>
                <td>
                  <div class="action-buttons">
                    <button v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'" @click="openReturnModal(ln)" class="btn-action btn-secondary">Kembalikan</button>
                    <button v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'" @click="renewLoan(ln)" class="btn-action btn-renew" title="Perpanjang 7 hari">Perpanjang</button>
                    <button v-if="ln.remaining_fine > 0" @click="openFinePaymentModal(ln)" class="btn-action btn-edit">Bayar Denda</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="loans.length === 0" class="empty-state">
            <h3>Belum ada peminjaman</h3>
            <p>Catat peminjaman dari tab Eksemplar atau tombol di atas.</p>
          </div>
          <div v-if="loansMeta.last_page > 1" class="pagination">
            <button @click="loadLoans(loansMeta.current_page - 1)" :disabled="loansMeta.current_page === 1" class="pagination-btn">Sebelumnya</button>
            <span class="pagination-info">Halaman {{ loansMeta.current_page }} dari {{ loansMeta.last_page }}</span>
            <button @click="loadLoans(loansMeta.current_page + 1)" :disabled="loansMeta.current_page >= loansMeta.last_page" class="pagination-btn">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- FINES TAB -->
      <div v-show="activeTab === 'fines'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input v-model="fineFilters.loan_id" @input="debounceLoadFinePayments" placeholder="ID peminjaman (opsional)..." class="search-input" />
          </div>
        </div>
        <div v-if="finePaymentsLoading" class="loading-state"><p>Memuat data...</p></div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Tanggal</th><th>Peminjaman</th><th>Jumlah</th><th>Metode</th></tr>
            </thead>
            <tbody>
              <tr v-for="fp in finePayments" :key="fp.id">
                <td>{{ fp.paid_at }}</td>
                <td>#{{ fp.loan_id }}</td>
                <td>Rp {{ formatNumber(fp.amount) }}</td>
                <td>{{ fp.payment_method || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="finePayments.length === 0" class="empty-state">
            <h3>Belum ada pembayaran denda</h3>
          </div>
          <div v-if="finePaymentsMeta.last_page > 1" class="pagination">
            <button @click="loadFinePayments(finePaymentsMeta.current_page - 1)" :disabled="finePaymentsMeta.current_page === 1" class="pagination-btn">Sebelumnya</button>
            <span class="pagination-info">Halaman {{ finePaymentsMeta.current_page }} dari {{ finePaymentsMeta.last_page }}</span>
            <button @click="loadFinePayments(finePaymentsMeta.current_page + 1)" :disabled="finePaymentsMeta.current_page >= finePaymentsMeta.last_page" class="pagination-btn">Selanjutnya</button>
          </div>
        </div>
      </div>

      <!-- REPORTS TAB -->
      <div v-show="activeTab === 'reports'" class="tab-content">
        <div class="report-export-bar">
          <div class="filters filters-inline">
            <label class="filter-label">Periode:</label>
            <input v-model="reportDateFrom" type="date" class="filter-select" />
            <span class="filter-sep">s/d</span>
            <input v-model="reportDateTo" type="date" class="filter-select" />
          </div>
          <button type="button" class="btn-primary" :disabled="exportingPdf" @click="previewLoansPdf">
            <span v-if="exportingPdf">Memuat...</span>
            <span v-else>Preview / Cetak PDF Laporan Peminjaman</span>
          </button>
        </div>
        <div class="reports-grid">
          <div class="stat-cards">
            <div class="stat-card"><span class="stat-value">{{ stats.total_books ?? 0 }}</span><span class="stat-label">Total Buku</span></div>
            <div class="stat-card"><span class="stat-value">{{ stats.total_copies ?? 0 }}</span><span class="stat-label">Total Eksemplar</span></div>
            <div class="stat-card"><span class="stat-value">{{ stats.available_copies ?? 0 }}</span><span class="stat-label">Tersedia</span></div>
            <div class="stat-card"><span class="stat-value">{{ stats.borrowed_copies ?? 0 }}</span><span class="stat-label">Dipinjam</span></div>
            <div class="stat-card"><span class="stat-value">{{ stats.overdue_count ?? 0 }}</span><span class="stat-label">Terlambat</span></div>
            <div class="stat-card"><span class="stat-value">Rp {{ formatNumber(stats.total_fines_collected ?? 0) }}</span><span class="stat-label">Denda Terkumpul</span></div>
          </div>
          <div class="report-section">
            <h3>Buku Paling Banyak Dipinjam</h3>
            <table class="data-table">
              <thead><tr><th>Judul</th><th>Pengarang</th><th>Jumlah Pinjam</th></tr></thead>
              <tbody>
                <tr v-for="(tb, i) in topBooks" :key="i">
                  <td>{{ tb.title }}</td>
                  <td>{{ tb.author || '-' }}</td>
                  <td>{{ tb.loan_count }}</td>
                </tr>
              </tbody>
            </table>
            <p v-if="topBooks.length === 0" class="muted">Belum ada data.</p>
          </div>
        </div>
      </div>

      <!-- MODALS (Category, Book, Copy, Loan, Return, FinePayment) - see script for refs -->
      <Teleport to="body">
        <div v-if="showCategoryModal" class="modal-overlay" @click.self="showCategoryModal = false">
          <div class="modal-card">
            <h3>{{ editingCategory ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
            <form @submit.prevent="saveCategory">
              <div class="form-group"><label>Kode</label><input v-model="categoryForm.code" required maxlength="20" /></div>
              <div class="form-group"><label>Nama</label><input v-model="categoryForm.name" required maxlength="100" /></div>
              <div class="form-group"><label>Deskripsi</label><textarea v-model="categoryForm.description" rows="2"></textarea></div>
              <div class="form-group"><label><input type="checkbox" v-model="categoryForm.is_active" /> Aktif</label></div>
              <div class="modal-footer">
                <button type="button" @click="showCategoryModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showBookModal" class="modal-overlay" @click.self="showBookModal = false">
          <div class="modal-card modal-wide">
            <h3>{{ editingBook ? 'Edit Buku' : 'Tambah Buku' }}</h3>
            <form @submit.prevent="saveBook">
              <div class="form-row">
                <div class="form-group"><label>Kategori *</label><select v-model="bookForm.category_id" required><option value="">Pilih</option><option v-for="c in categoriesForSelect" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option></select></div>
                <div class="form-group"><label>ISBN</label><input v-model="bookForm.isbn" maxlength="30" /></div>
              </div>
              <div class="form-group"><label>Judul *</label><input v-model="bookForm.title" required maxlength="255" /></div>
              <div class="form-row">
                <div class="form-group"><label>Pengarang</label><input v-model="bookForm.author" maxlength="255" /></div>
                <div class="form-group"><label>Penerbit</label><input v-model="bookForm.publisher" maxlength="255" /></div>
              </div>
              <div class="form-row">
                <div class="form-group"><label>Tahun</label><input v-model.number="bookForm.year" type="number" min="1000" max="2100" /></div>
                <div class="form-group"><label>Bahasa</label><input v-model="bookForm.language" maxlength="50" /></div>
                <div class="form-group"><label>Halaman</label><input v-model.number="bookForm.pages" type="number" min="0" /></div>
              </div>
              <div class="form-group"><label>Rak / Lokasi</label><input v-model="bookForm.shelf_code" maxlength="50" /></div>
              <div class="form-group"><label>Deskripsi</label><textarea v-model="bookForm.description" rows="3"></textarea></div>
              <div class="form-group"><label>Cover (gambar)</label><input type="file" accept="image/*" @change="onBookCoverChange" /></div>
              <div class="modal-footer">
                <button type="button" @click="showBookModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showCopyModal" class="modal-overlay" @click.self="showCopyModal = false">
          <div class="modal-card">
            <h3>{{ editingCopy ? 'Edit Eksemplar' : 'Tambah Eksemplar' }}</h3>
            <form @submit.prevent="saveCopy">
              <div class="form-group"><label>Buku *</label><select v-model="copyForm.book_id" required :disabled="!!editingCopy"><option value="">Pilih</option><option v-for="b in booksList" :key="b.id" :value="b.id">{{ b.title }}</option></select></div>
              <div class="form-group"><label>Kode Eksemplar *</label><input v-model="copyForm.copy_code" required maxlength="50" /></div>
              <div class="form-row">
                <div class="form-group"><label>Status</label><select v-model="copyForm.status"><option value="Tersedia">Tersedia</option><option value="Dipinjam">Dipinjam</option><option value="Rusak">Rusak</option><option value="Hilang">Hilang</option></select></div>
                <div class="form-group"><label>Kondisi</label><select v-model="copyForm.condition"><option value="Baik">Baik</option><option value="Rusak Ringan">Rusak Ringan</option><option value="Rusak Berat">Rusak Berat</option></select></div>
              </div>
              <div class="form-group"><label>Catatan</label><textarea v-model="copyForm.notes" rows="2"></textarea></div>
              <div class="modal-footer">
                <button type="button" @click="showCopyModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showLoanModal" class="modal-overlay" @click.self="showLoanModal = false">
          <div class="modal-card">
            <h3>Catat Peminjaman</h3>
            <form @submit.prevent="saveLoan">
              <div class="form-group" v-if="!selectedCopyForLoan"><label>Eksemplar *</label><select v-model="loanForm.copy_id" required><option value="">Pilih eksemplar tersedia</option><option v-for="cp in availableCopies" :key="cp.id" :value="cp.id">{{ cp.copy_code }} - {{ cp.book?.title }}</option></select></div>
              <div v-else class="form-group"><label>Eksemplar</label><input :value="selectedCopyForLoan.copy_code + ' - ' + (selectedCopyForLoan.book?.title)" disabled /></div>
              <div class="form-group"><label>Tipe Peminjam *</label><select v-model="loanForm.borrower_type" required><option value="Student">Siswa</option><option value="Employee">Guru/Karyawan</option><option value="External">Tamu</option></select></div>
              <div class="form-group"><label>Nama Peminjam *</label><input v-model="loanForm.borrower_name" required /></div>
              <div class="form-group"><label>NIS/NIK/Identitas</label><input v-model="loanForm.borrower_identifier" /></div>
              <div class="form-row">
                <div class="form-group"><label>Tanggal Pinjam *</label><input v-model="loanForm.loan_date" type="date" required /></div>
                <div class="form-group"><label>Jatuh Tempo *</label><input v-model="loanForm.due_date" type="date" required /></div>
              </div>
              <div class="form-group"><label>Catatan</label><textarea v-model="loanForm.notes" rows="2"></textarea></div>
              <div class="modal-footer">
                <button type="button" @click="closeLoanModal" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showReturnModal" class="modal-overlay" @click.self="showReturnModal = false">
          <div class="modal-card">
            <h3>Pengembalian Buku</h3>
            <p v-if="returningLoan" class="muted">Peminjam: {{ returningLoan.borrower_name }} · Buku: {{ returningLoan.copy?.book?.title }} ({{ returningLoan.copy?.copy_code }})</p>
            <form @submit.prevent="saveReturn">
              <div class="form-group"><label>Denda (Rp)</label><input v-model.number="returnForm.fine_amount" type="number" min="0" step="1000" /></div>
              <div class="form-group"><label>Catatan</label><textarea v-model="returnForm.notes" rows="2"></textarea></div>
              <div class="modal-footer">
                <button type="button" @click="showReturnModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Kembalikan</button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showFinePaymentModal" class="modal-overlay" @click.self="showFinePaymentModal = false">
          <div class="modal-card">
            <h3>Bayar Denda</h3>
            <p v-if="finePaymentLoan" class="muted">Sisa denda: Rp {{ formatNumber(finePaymentLoan.remaining_fine) }}</p>
            <form @submit.prevent="saveFinePayment">
              <div class="form-group"><label>Jumlah (Rp) *</label><input v-model.number="finePaymentForm.amount" type="number" min="0" step="1000" required /></div>
              <div class="form-group"><label>Tanggal Bayar *</label><input v-model="finePaymentForm.paid_at" type="date" required /></div>
              <div class="form-group"><label>Metode</label><input v-model="finePaymentForm.payment_method" placeholder="Tunai/Transfer/dll" /></div>
              <div class="form-group"><label>Catatan</label><textarea v-model="finePaymentForm.notes" rows="2"></textarea></div>
              <div class="modal-footer">
                <button type="button" @click="showFinePaymentModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Bayar</button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
    </div>
  </Layout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { libraryApi } from '@/api/library'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const activeTab = ref('books')
const saving = ref(false)

// Categories
const categories = ref([])
const categoriesMeta = ref({ current_page: 1, last_page: 1 })
const categoriesLoading = ref(false)
const categoriesForSelect = ref([])
const categoryFilters = ref({ search: '', is_active: '' })
const showCategoryModal = ref(false)
const editingCategory = ref(null)
const categoryForm = ref({ code: '', name: '', description: '', is_active: true })

// Books
const books = ref([])
const booksMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const booksLoading = ref(false)
const bookFilters = ref({ search: '', category_id: '' })
const showBookModal = ref(false)
const editingBook = ref(null)
const bookForm = ref({ category_id: '', isbn: '', title: '', author: '', publisher: '', year: null, language: '', pages: null, shelf_code: '', description: '' })
let bookCoverFile = null

// Copies
const copies = ref([])
const copiesMeta = ref({ current_page: 1, last_page: 1 })
const copiesLoading = ref(false)
const copyFilters = ref({ search: '', book_id: '', status: '' })
const showCopyModal = ref(false)
const editingCopy = ref(null)
const copyForm = ref({ book_id: '', copy_code: '', status: 'Tersedia', condition: 'Baik', notes: '' })
const booksList = ref([])

// Loans
const loans = ref([])
const loansMeta = ref({ current_page: 1, last_page: 1 })
const loansLoading = ref(false)
const loanFilters = ref({ search: '', status: '', borrower_type: '' })
const showLoanModal = ref(false)
const selectedCopyForLoan = ref(null)
const loanForm = ref({ copy_id: '', borrower_type: 'Student', borrower_name: '', borrower_identifier: '', loan_date: '', due_date: '', notes: '' })
const availableCopies = ref([])

// Return
const showReturnModal = ref(false)
const returningLoan = ref(null)
const returnForm = ref({ fine_amount: 0, notes: '' })

// Fine payments
const finePayments = ref([])
const finePaymentsMeta = ref({ current_page: 1, last_page: 1 })
const finePaymentsLoading = ref(false)
const fineFilters = ref({ loan_id: '' })
const showFinePaymentModal = ref(false)
const finePaymentLoan = ref(null)
const finePaymentForm = ref({ amount: 0, paid_at: '', payment_method: '', notes: '' })

// Reports
const stats = ref({})
const topBooks = ref([])
const reportDateFrom = ref('')
const reportDateTo = ref('')
const exportingPdf = ref(false)

function formatNumber(n) { return Number(n).toLocaleString('id-ID') }
function formatDate(d) { return d ? (typeof d === 'string' ? d : d.toISOString().slice(0, 10)) : '-' }
function getErrorMessage(e) {
  const msg = e.response?.data?.message
  if (msg) return msg
  const errs = e.response?.data?.errors
  if (errs && typeof errs === 'object') {
    const first = Object.values(errs)[0]
    return Array.isArray(first) ? first[0] : first
  }
  return e.response?.data?.error || e.message || 'Terjadi kesalahan.'
}

function getCopyStatusClass(s) {
  if (s === 'Tersedia') return 'badge-success'
  if (s === 'Dipinjam' || s === 'Terlambat') return 'badge-warning'
  if (s === 'Rusak' || s === 'Hilang') return 'badge-danger'
  return 'badge-gray'
}
function getLoanStatusClass(s) {
  if (s === 'Dikembalikan') return 'badge-success'
  if (s === 'Terlambat') return 'badge-danger'
  if (s === 'Dipinjam') return 'badge-warning'
  return 'badge-gray'
}

async function loadCategories(page = 1) {
  categoriesLoading.value = true
  try {
    const res = await libraryApi.getCategories({ page, per_page: 15, search: categoryFilters.value.search || undefined, is_active: categoryFilters.value.is_active || undefined })
    categories.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    categoriesMeta.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    categoriesLoading.value = false
  }
}
async function loadBooks(page = 1) {
  booksLoading.value = true
  try {
    const res = await libraryApi.getBooks({ page, per_page: 15, search: bookFilters.value.search || undefined, category_id: bookFilters.value.category_id || undefined })
    books.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    booksMeta.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1, total: meta.total ?? 0 }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    booksLoading.value = false
  }
}
async function loadCopies(page = 1) {
  copiesLoading.value = true
  try {
    const res = await libraryApi.getCopies({ page, per_page: 15, book_id: copyFilters.value.book_id || undefined, status: copyFilters.value.status || undefined, search: copyFilters.value.search || undefined })
    copies.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    copiesMeta.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    copiesLoading.value = false
  }
}
async function loadLoans(page = 1) {
  loansLoading.value = true
  try {
    const res = await libraryApi.getLoans({ page, per_page: 15, status: loanFilters.value.status || undefined, borrower_type: loanFilters.value.borrower_type || undefined, search: loanFilters.value.search || undefined })
    loans.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    loansMeta.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    loansLoading.value = false
  }
}
async function loadFinePayments(page = 1) {
  finePaymentsLoading.value = true
  try {
    const res = await libraryApi.getFinePayments({ page, per_page: 15, loan_id: fineFilters.value.loan_id || undefined })
    finePayments.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    finePaymentsMeta.value = { current_page: meta.current_page ?? 1, last_page: meta.last_page ?? 1 }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    finePaymentsLoading.value = false
  }
}
async function loadStats() {
  try {
    const res = await libraryApi.getStatistics()
    stats.value = res.data.data || {}
  } catch (_) {}
}
async function loadTopBooks() {
  try {
    const res = await libraryApi.getTopBooks({ limit: 10 })
    topBooks.value = res.data.data || []
  } catch (_) {}
}
async function loadBooksList() {
  try {
    const res = await libraryApi.getBooks({ per_page: 200 })
    booksList.value = res.data.data || []
  } catch (_) {}
}
async function loadAvailableCopies() {
  try {
    const res = await libraryApi.getCopies({ status: 'Tersedia', per_page: 100 })
    availableCopies.value = res.data.data ?? []
  } catch (_) {}
}
async function loadCategoriesForSelect() {
  try {
    const res = await libraryApi.getCategories({ per_page: 200, is_active: 1 })
    categoriesForSelect.value = res.data.data ?? []
  } catch (_) {}
}

let debounceTimer
function debounceLoadBooks() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadBooks(1), 400) }
function debounceLoadCategories() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadCategories(1), 400) }
function debounceLoadCopies() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadCopies(1), 400) }
function debounceLoadLoans() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadLoans(1), 400) }
function debounceLoadFinePayments() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => loadFinePayments(1), 400) }

function openCategoryModal(cat = null) {
  editingCategory.value = cat
  categoryForm.value = cat ? { code: cat.code, name: cat.name, description: cat.description || '', is_active: cat.is_active } : { code: '', name: '', description: '', is_active: true }
  showCategoryModal.value = true
}
async function saveCategory() {
  saving.value = true
  try {
    if (editingCategory.value) {
      await libraryApi.updateCategory(editingCategory.value.id, categoryForm.value)
      toast.success('Berhasil', 'Kategori diperbarui')
    } else {
      await libraryApi.createCategory(categoryForm.value)
      toast.success('Berhasil', 'Kategori ditambahkan')
    }
    showCategoryModal.value = false
    loadCategories(categoriesMeta.value.current_page)
    loadBooksList()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}
async function deleteCategory(cat) {
  if (!confirm('Hapus kategori "' + cat.name + '"?')) return
  try {
    await libraryApi.deleteCategory(cat.id)
    toast.success('Berhasil', 'Kategori dihapus')
    loadCategories(categoriesMeta.value.current_page)
    loadCategoriesForSelect()
    loadBooksList()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  }
}

function openBookModal(book = null) {
  editingBook.value = book
  bookCoverFile = null
  bookForm.value = book ? { category_id: String(book.category_id), isbn: book.isbn || '', title: book.title, author: book.author || '', publisher: book.publisher || '', year: book.year || null, language: book.language || '', pages: book.pages || null, shelf_code: book.shelf_code || '', description: book.description || '' } : { category_id: '', isbn: '', title: '', author: '', publisher: '', year: null, language: '', pages: null, shelf_code: '', description: '' }
  showBookModal.value = true
}
function onBookCoverChange(e) { bookCoverFile = e.target.files?.[0] || null }
async function saveBook() {
  saving.value = true
  try {
    const payload = { ...bookForm.value }
    if (bookCoverFile) payload.cover = bookCoverFile
    if (editingBook.value) {
      await libraryApi.updateBook(editingBook.value.id, payload)
      toast.success('Berhasil', 'Buku diperbarui')
    } else {
      await libraryApi.createBook(payload)
      toast.success('Berhasil', 'Buku ditambahkan')
    }
    showBookModal.value = false
    loadBooks(booksMeta.value.current_page)
    loadBooksList()
    loadAvailableCopies()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}
async function deleteBook(book) {
  if (!confirm('Hapus buku "' + book.title + '"?')) return
  try {
    await libraryApi.deleteBook(book.id)
    toast.success('Berhasil', 'Buku dihapus')
    loadBooks(booksMeta.value.current_page)
    loadBooksList()
    loadCopies(copiesMeta.value.current_page)
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  }
}

function openCopyModal(copy = null, book = null) {
  editingCopy.value = copy
  copyForm.value = copy ? { book_id: String(copy.book_id), copy_code: copy.copy_code, status: copy.status, condition: copy.condition || 'Baik', notes: copy.notes || '' } : { book_id: book ? String(book.id) : '', copy_code: '', status: 'Tersedia', condition: 'Baik', notes: '' }
  showCopyModal.value = true
}
async function saveCopy() {
  saving.value = true
  try {
    if (editingCopy.value) {
      await libraryApi.updateCopy(editingCopy.value.id, { copy_code: copyForm.value.copy_code, status: copyForm.value.status, condition: copyForm.value.condition, notes: copyForm.value.notes })
      toast.success('Berhasil', 'Eksemplar diperbarui')
    } else {
      await libraryApi.createCopy(copyForm.value)
      toast.success('Berhasil', 'Eksemplar ditambahkan')
    }
    showCopyModal.value = false
    loadCopies(copiesMeta.value.current_page)
    loadBooks(booksMeta.value.current_page)
    loadAvailableCopies()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}
async function deleteCopy(cp) {
  if (!confirm('Hapus eksemplar ' + cp.copy_code + '?')) return
  try {
    await libraryApi.deleteCopy(cp.id)
    toast.success('Berhasil', 'Eksemplar dihapus')
    loadCopies(copiesMeta.value.current_page)
    loadAvailableCopies()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  }
}

function openLoanModal(copy = null) {
  selectedCopyForLoan.value = copy || null
  loanForm.value = { copy_id: copy ? String(copy.id) : '', borrower_type: 'Student', borrower_name: '', borrower_identifier: '', loan_date: new Date().toISOString().slice(0, 10), due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10), notes: '' }
  showLoanModal.value = true
  if (!copy) loadAvailableCopies()
}
function closeLoanModal() {
  showLoanModal.value = false
  selectedCopyForLoan.value = null
}
async function saveLoan() {
  saving.value = true
  try {
    const payload = { ...loanForm.value }
    if (selectedCopyForLoan.value) payload.copy_id = selectedCopyForLoan.value.id
    await libraryApi.createLoan(payload)
    toast.success('Berhasil', 'Peminjaman dicatat')
    closeLoanModal()
    loadLoans(loansMeta.value.current_page)
    loadCopies(copiesMeta.value.current_page)
    loadAvailableCopies()
    loadStats()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}

async function openReturnModal(loan) {
  returningLoan.value = loan
  returnForm.value = { fine_amount: 0, notes: '' }
  showReturnModal.value = true
  try {
    const res = await libraryApi.calculateFine(loan.id)
    if (res.data?.fine_amount != null) returnForm.value.fine_amount = Number(res.data.fine_amount)
  } catch (_) {}
}
async function saveReturn() {
  if (!returningLoan.value) return
  saving.value = true
  try {
    await libraryApi.returnLoan(returningLoan.value.id, returnForm.value)
    toast.success('Berhasil', 'Buku dikembalikan')
    showReturnModal.value = false
    returningLoan.value = null
    loadLoans(loansMeta.value.current_page)
    loadCopies(copiesMeta.value.current_page)
    loadAvailableCopies()
    loadStats()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}

function openFinePaymentModal(loan) {
  finePaymentLoan.value = loan
  finePaymentForm.value = { amount: loan.remaining_fine || 0, paid_at: new Date().toISOString().slice(0, 10), payment_method: '', notes: '' }
  showFinePaymentModal.value = true
}
async function saveFinePayment() {
  if (!finePaymentLoan.value) return
  saving.value = true
  try {
    await libraryApi.createFinePayment({ loan_id: finePaymentLoan.value.id, ...finePaymentForm.value })
    toast.success('Berhasil', 'Pembayaran denda dicatat')
    showFinePaymentModal.value = false
    finePaymentLoan.value = null
    loadLoans(loansMeta.value.current_page)
    loadFinePayments(finePaymentsMeta.value.current_page)
    loadStats()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}
async function renewLoan(ln) {
  if (!confirm('Perpanjang peminjaman "' + ln.copy?.book?.title + '" untuk 7 hari?')) return
  try {
    await libraryApi.renewLoan(ln.id, { extra_days: 7 })
    toast.success('Berhasil', 'Peminjaman diperpanjang')
    loadLoans(loansMeta.value.current_page)
    loadStats()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  }
}
async function previewLoansPdf() {
  exportingPdf.value = true
  try {
    const params = {}
    if (reportDateFrom.value) params.date_from = reportDateFrom.value
    if (reportDateTo.value) params.date_to = reportDateTo.value
    const res = await libraryApi.exportLoansPdf(params)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank', 'noopener,noreferrer')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
    toast.success('Berhasil', 'PDF dibuka di tab baru. Anda dapat mencetak atau menyimpan dari sana.')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    exportingPdf.value = false
  }
}

watch(activeTab, (tab) => {
  if (tab === 'books') loadBooks(1)
  if (tab === 'categories') loadCategories(1)
  if (tab === 'copies') { loadCopies(1); loadBooksList() }
  if (tab === 'loans') loadLoans(1)
  if (tab === 'fines') loadFinePayments(1)
  if (tab === 'reports') { loadStats(); loadTopBooks() }
})
onMounted(() => {
  loadCategories(1)
  loadCategoriesForSelect()
  loadBooks(1)
  loadBooksList()
})
</script>

<style scoped>
.library-page { padding: 0 1rem 2rem; }
.page-header { margin-bottom: 1.5rem; }
.page-header h2 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem 0; }
.page-header p { color: #64748b; margin: 0; font-size: 0.9rem; }
.tabs-container { margin-bottom: 1.25rem; border-bottom: 2px solid #e2e8f0; }
.tabs-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.tab-btn { padding: 0.6rem 1rem; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #64748b; font-weight: 500; cursor: pointer; }
.tab-btn:hover { color: #475569; }
.tab-btn.active { color: #667eea; border-bottom-color: #667eea; }
.tab-content { padding-top: 1rem; }
.tab-header { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.search-input { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; min-width: 180px; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; min-width: 140px; }
.btn-primary { padding: 0.6rem 1.25rem; background: #667eea; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #5a67d8; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.loading-state { text-align: center; padding: 2rem; color: #64748b; }
.table-container { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.data-table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 0.8rem; text-transform: uppercase; }
.data-table tbody tr:hover { background: #f8fafc; }
.name-cell .name { font-weight: 500; }
.muted { color: #64748b; font-size: 0.85rem; }
.small { font-size: 0.8rem; }
.action-buttons { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.btn-action { padding: 0.35rem 0.65rem; border-radius: 6px; border: none; font-size: 0.8rem; cursor: pointer; font-weight: 500; }
.btn-edit { background: #dbeafe; color: #1d4ed8; }
.btn-edit:hover { background: #bfdbfe; }
.btn-secondary { background: #e0e7ff; color: #4338ca; }
.btn-secondary:hover { background: #c7d2fe; }
.btn-delete { background: #fee2e2; color: #b91c1c; }
.btn-delete:hover { background: #fecaca; }
.btn-renew { background: #d1fae5; color: #047857; }
.btn-renew:hover { background: #a7f3d0; }
.badge-success { background: #dcfce7; color: #166534; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; }
.badge-warning { background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; }
.badge-danger { background: #fee2e2; color: #b91c1c; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; }
.badge-gray { background: #f1f5f9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.8rem; }
.empty-state { text-align: center; padding: 2.5rem; color: #64748b; }
.empty-state h3 { margin: 0 0 0.5rem 0; color: #475569; }
.pagination { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; flex-wrap: wrap; }
.pagination-btn { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pagination-info { font-size: 0.85rem; color: #64748b; }
.report-export-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
.filter-label { font-size: 0.9rem; color: #475569; font-weight: 500; }
.filter-sep { color: #64748b; font-size: 0.9rem; }
.reports-grid { display: flex; flex-direction: column; gap: 1.5rem; }
.stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
.stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; text-align: center; }
.stat-value { display: block; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
.stat-label { font-size: 0.8rem; color: #64748b; }
.report-section h3 { margin: 0 0 0.75rem 0; font-size: 1rem; color: #475569; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-card { background: #fff; border-radius: 16px; padding: 1.5rem; max-width: 480px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
.modal-wide { max-width: 560px; }
.modal-card h3 { margin: 0 0 1rem 0; font-size: 1.15rem; color: #1e293b; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 500; color: #475569; font-size: 0.9rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.btn-secondary { padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
</style>
