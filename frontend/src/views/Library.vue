<template>
    <div class="library-page">
      <!-- Quick stats strip -->
      <div class="stats-strip">
        <div class="stat-item">
          <span class="stat-num">{{ displayStat(stats.total_books) }}</span>
          <span class="stat-tag">Buku</span>
        </div>
        <div class="stat-item">
          <span class="stat-num">{{ displayStat(stats.available_copies) }}</span>
          <span class="stat-tag">Tersedia</span>
        </div>
        <div class="stat-item">
          <span class="stat-num">{{ displayStat(stats.borrowed_copies) }}</span>
          <span class="stat-tag">Dipinjam</span>
        </div>
        <div class="stat-item" v-if="(stats.total_ebook_views ?? 0) > 0 || (stats.total_ebooks ?? 0) > 0">
          <span class="stat-num">{{ displayStat(stats.total_ebook_views) }}</span>
          <span class="stat-tag">Ebook dibuka</span>
        </div>
        <div class="stat-item stat-warn" v-if="(stats.overdue_count ?? 0) > 0">
          <span class="stat-num">{{ stats.overdue_count }}</span>
          <span class="stat-tag">Terlambat</span>
        </div>
      </div>

      <div class="tab-shell">
        <nav class="section-nav" role="tablist" aria-label="Modul Perpustakaan">
          <button
            v-for="t in mainTabList"
            :key="t.id"
            type="button"
            role="tab"
            :aria-selected="mainTab === t.id"
            @click="switchMainTab(t.id)"
            :class="['sec-btn', { active: mainTab === t.id }]"
          >
            <span class="sec-icon" aria-hidden="true">
              <svg v-if="t.id === 'katalog'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
              <svg v-else-if="t.id === 'sirkulasi'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M16 3h5v5M21 3l-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="2"/></svg>
              <svg v-else-if="t.id === 'laporan'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19V5a1 1 0 0 1 1-1h10l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="2"/><path d="M14 4v5h5M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" stroke="currentColor" stroke-width="2"/></svg>
            </span>
            <span class="sec-label">{{ t.label }}</span>
          </button>
        </nav>
        <div class="tab-main">
        <div v-if="subTabList.length" class="sub-nav" role="tablist" aria-label="Sub modul perpustakaan">
          <button
            v-for="t in subTabList"
            :key="t.id"
            type="button"
            role="tab"
            :aria-selected="activeTab === t.id"
            @click="activeTab = t.id"
            :class="['sub-nav-btn', { active: activeTab === t.id }]"
          >
            <span>{{ t.label }}</span>
          </button>
        </div>

      <!-- BOOKS TAB -->
      <div v-show="activeTab === 'books'" class="tab-content">
          <div class="tab-header">
            <div class="filters filters-inline">
              <div class="search-wrap">
                <input
                  v-model="bookFilters.search"
                  @input="debounceLoadBooks"
                  placeholder="Cari judul, pengarang, ISBN..."
                  class="search-input"
                />
                <button v-if="bookFilters.search" type="button" class="search-clear" @click="bookFilters.search = ''; loadBooks(1)" aria-label="Hapus pencarian">×</button>
              </div>
              <select v-model="bookFilters.category_id" @change="loadBooks(1)" class="filter-select">
                <option value="">Semua Kategori</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
              </select>
            </div>
            <div class="tab-actions">
              <button type="button" class="btn-icon-tool" :disabled="exportingBooksCsv" @click="exportBooksCsv" title="Export CSV" aria-label="Export CSV">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button type="button" class="btn-icon-tool" :disabled="exportingBooksPdf" @click="exportBooksPdf" title="Cetak PDF" aria-label="Cetak PDF">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M6 9V2h12v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button type="button" class="btn-icon-tool" @click="showImportModal = true" title="Import Excel" aria-label="Import Excel">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button type="button" @click="openBookModal()" class="btn-primary btn-add">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>Tambah Buku</span>
              </button>
            </div>
          </div>
          <div v-if="booksLoading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="8" :columns="7" :cell-widths="['6%','22%','16%','14%','12%','8%','10%']" />
          </div>
          <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('title')">
                    Judul <span class="sort-icon" :class="bookSortClass('title')" aria-hidden="true"></span>
                  </button>
                </th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('author')">
                    Pengarang <span class="sort-icon" :class="bookSortClass('author')" aria-hidden="true"></span>
                  </button>
                </th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('category')">
                    Kategori <span class="sort-icon" :class="bookSortClass('category')" aria-hidden="true"></span>
                  </button>
                </th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('isbn')">
                    ISBN <span class="sort-icon" :class="bookSortClass('isbn')" aria-hidden="true"></span>
                  </button>
                </th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('copies_count')">
                    Eksemplar <span class="sort-icon" :class="bookSortClass('copies_count')" aria-hidden="true"></span>
                  </button>
                </th>
                <th>
                  <button type="button" class="th-sort" @click="setBookSort('ebook')">
                    Ebook <span class="sort-icon" :class="bookSortClass('ebook')" aria-hidden="true"></span>
                  </button>
                </th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(b, index) in books" :key="b.id">
                <td class="col-no">{{ bookRowNumber(index) }}</td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ displayValue(b.title) }}</div>
                    <div v-if="b.publisher" class="muted small">{{ displayValue(b.publisher) }}{{ b.year ? ', ' + b.year : '' }}</div>
                  </div>
                </td>
                <td>{{ displayValue(b.author) }}</td>
                <td>{{ displayValue(b.category?.name) }}</td>
                <td>{{ displayValue(b.isbn) }}</td>
                <td>{{ b.copies_count ?? b.available_copies_count ?? 0 }}</td>
                <td>
                  <template v-if="b.has_ebook">
                    <span class="badge badge-ebook">PDF</span>
                    <span v-if="b.is_public_ebook" class="badge badge-public" title="Tampil di halaman publik">Publik</span>
                    <span v-if="b.ebook_view_count" class="muted small" style="display:block;margin-top:0.2rem">{{ b.ebook_view_count }}x dibuka</span>
                  </template>
                  <span v-else class="muted small">—</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button type="button" @click="openBookModal(b)" class="btn-action btn-edit" title="Edit" aria-label="Edit buku">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button type="button" @click="openCopyModal(null, b)" class="btn-action btn-secondary" title="Eksemplar" aria-label="Tambah eksemplar">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.5 2H20V22H6.5A2.5 2.5 0 0 1 4 19.5V4.5A2.5 2.5 0 0 1 6.5 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 7H16M8 11H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </button>
                    <button type="button" @click="confirmDelete('book', b)" class="btn-action btn-delete" title="Hapus" aria-label="Hapus buku">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="books.length === 0" class="empty-state">
            <h3>Belum ada buku</h3>
            <p>Tambahkan kategori lalu tambah buku.</p>
            <button @click="openBookModal()" class="btn-primary">Tambah Buku</button>
          </div>
          <PaginationBar
            embedded
            :page="booksMeta.current_page"
            :last-page="booksMeta.last_page"
            :per-page="booksMeta.per_page"
            :total="booksMeta.total"
            item-label="buku"
            @page-change="loadBooks"
            @per-page-change="changeBooksPerPage"
          />
        </div>
      </div>

      <!-- CATEGORIES TAB -->
      <div v-show="activeTab === 'categories'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <div class="search-wrap">
              <input v-model="categoryFilters.search" @input="debounceLoadCategories" placeholder="Cari kode / nama..." class="search-input" />
              <button v-if="categoryFilters.search" type="button" class="search-clear" @click="categoryFilters.search = ''; loadCategories(1)" aria-label="Hapus pencarian">×</button>
            </div>
            <select v-model="categoryFilters.is_active" @change="loadCategories(1)" class="filter-select">
              <option value="">Semua</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <button type="button" @click="openCategoryModal()" class="btn-primary btn-add">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Tambah Kategori</span>
          </button>
        </div>
        <div v-if="categoriesLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="6" :columns="4" />
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Kode</th><th>Nama</th><th>Status</th><th class="col-aksi">Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="c in categories" :key="c.id">
                <td>{{ displayValue(c.code) }}</td>
                <td><div class="name-cell"><div class="name">{{ displayValue(c.name) }}</div><div v-if="c.description" class="muted small">{{ displayValue(c.description) }}</div></div></td>
                <td><span :class="c.is_active ? 'badge-success' : 'badge-gray'">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <button type="button" @click="openCategoryModal(c)" class="btn-action btn-edit" title="Edit" aria-label="Edit kategori">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button type="button" @click="confirmDelete('category', c)" class="btn-action btn-delete" title="Hapus" aria-label="Hapus kategori">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="categories.length === 0" class="empty-state">
            <h3>Belum ada kategori</h3>
            <p>Tambahkan kategori buku terlebih dahulu.</p>
            <button @click="openCategoryModal()" class="btn-primary">Tambah Kategori</button>
          </div>
          <PaginationBar
            embedded
            :page="categoriesMeta.current_page"
            :last-page="categoriesMeta.last_page"
            :per-page="categoriesMeta.per_page"
            :total="categoriesMeta.total"
            item-label="kategori"
            @page-change="loadCategories"
            @per-page-change="changeCategoriesPerPage"
          />
        </div>
      </div>

      <!-- COPIES TAB -->
      <div v-show="activeTab === 'copies'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <div class="search-wrap">
              <input v-model="copyFilters.search" @input="debounceLoadCopies" placeholder="Kode eksemplar..." class="search-input" />
              <button v-if="copyFilters.search" type="button" class="search-clear" @click="copyFilters.search = ''; loadCopies(1)" aria-label="Hapus pencarian">×</button>
            </div>
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
          <button type="button" @click="openCopyModal()" class="btn-primary btn-add">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Tambah Eksemplar</span>
          </button>
        </div>
        <div v-if="copiesLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="6" :columns="5" />
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Kode</th><th>Buku</th><th>Status</th><th>Kondisi</th><th class="col-aksi">Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="cp in copies" :key="cp.id">
                <td><strong>{{ displayValue(cp.copy_code) }}</strong></td>
                <td><div class="name-cell"><div class="name">{{ displayValue(cp.book?.title) }}</div><div class="muted small">{{ displayValue(cp.book?.author) }}</div></div></td>
                <td><span :class="getCopyStatusClass(cp.status)">{{ cp.status }}</span></td>
                <td>{{ displayValue(cp.condition) }}</td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <button type="button" @click="openCopyModal(cp)" class="btn-action btn-edit" title="Edit" aria-label="Edit eksemplar">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button v-if="cp.status === 'Tersedia'" type="button" @click="openLoanModal(cp)" class="btn-action btn-secondary" title="Pinjam" aria-label="Catat peminjaman">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M16 3h5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 3l-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button type="button" @click="confirmDelete('copy', cp)" class="btn-action btn-delete" title="Hapus" aria-label="Hapus eksemplar">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="copies.length === 0" class="empty-state">
            <h3>Belum ada eksemplar</h3>
            <p>Tambahkan buku lalu tambah eksemplar.</p>
            <button @click="openCopyModal()" class="btn-primary">Tambah Eksemplar</button>
          </div>
          <PaginationBar
            embedded
            :page="copiesMeta.current_page"
            :last-page="copiesMeta.last_page"
            :per-page="copiesMeta.per_page"
            :total="copiesMeta.total"
            item-label="eksemplar"
            @page-change="loadCopies"
            @per-page-change="changeCopiesPerPage"
          />
        </div>
      </div>

      <!-- LOANS TAB -->
      <div v-show="activeTab === 'loans'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <div class="search-wrap">
              <input v-model="loanFilters.search" @input="debounceLoadLoans" placeholder="Cari peminjam..." class="search-input" />
              <button v-if="loanFilters.search" type="button" class="search-clear" @click="loanFilters.search = ''; loadLoans(1)" aria-label="Hapus pencarian">×</button>
            </div>
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
          <button type="button" @click="openLoanModal()" class="btn-primary btn-add">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Catat Peminjaman</span>
          </button>
        </div>
        <div v-if="loansLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="6" :columns="7" />
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Peminjam</th><th>Buku / Eksemplar</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Denda</th><th class="col-aksi">Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="ln in loans" :key="ln.id">
                <td>
                  <div class="name-cell">
                    <div class="name">{{ displayValue(ln.borrower_name) }}</div>
                    <div class="muted small">{{ ln.borrower_type }} {{ ln.borrower_identifier ? ' · ' + ln.borrower_identifier : '' }}</div>
                  </div>
                </td>
                <td>
                  <div class="name-cell">
                    <div class="name">{{ displayValue(ln.copy?.book?.title) }}</div>
                    <div class="muted small">Eks: {{ displayValue(ln.copy?.copy_code) }}</div>
                  </div>
                </td>
                <td>{{ displayValue(ln.loan_date) }}</td>
                <td>{{ displayValue(ln.due_date) }}</td>
                <td><span :class="getLoanStatusClass(ln.status)">{{ ln.status }}</span></td>
                <td>Rp {{ formatNumber(ln.fine_amount || 0) }} <span v-if="ln.remaining_fine > 0" class="muted">(sisa: {{ formatNumber(ln.remaining_fine) }})</span></td>
                <td class="col-aksi">
                  <div class="action-buttons">
                    <button
                      v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'"
                      type="button"
                      @click="openReturnModal(ln)"
                      class="btn-action btn-secondary"
                      title="Kembalikan"
                      aria-label="Kembalikan buku"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M9 14L4 9l5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button
                      v-if="ln.status === 'Dipinjam' || ln.status === 'Terlambat'"
                      type="button"
                      @click="renewLoan(ln)"
                      class="btn-action btn-renew"
                      title="Perpanjang 7 hari"
                      aria-label="Perpanjang peminjaman 7 hari"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button
                      v-if="ln.remaining_fine > 0"
                      type="button"
                      @click="openFinePaymentModal(ln)"
                      class="btn-action btn-edit"
                      title="Bayar Denda"
                      aria-label="Bayar denda"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="2"/>
                        <path d="M6 12h.01M18 12h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="loans.length === 0" class="empty-state">
            <h3>Belum ada peminjaman</h3>
            <p>Catat peminjaman dari tab Eksemplar atau tombol di atas.</p>
          </div>
          <PaginationBar
            embedded
            :page="loansMeta.current_page"
            :last-page="loansMeta.last_page"
            :per-page="loansMeta.per_page"
            :total="loansMeta.total"
            item-label="peminjaman"
            @page-change="loadLoans"
            @per-page-change="changeLoansPerPage"
          />
        </div>
      </div>

      <!-- FINES TAB -->
      <div v-show="activeTab === 'fines'" class="tab-content">
        <div class="tab-header">
          <div class="filters filters-inline">
            <div class="search-wrap">
              <input v-model="fineFilters.loan_id" @input="debounceLoadFinePayments" placeholder="ID peminjaman (opsional)..." class="search-input" />
              <button v-if="fineFilters.loan_id" type="button" class="search-clear" @click="fineFilters.loan_id = ''; loadFinePayments(1)" aria-label="Hapus">×</button>
            </div>
            <button type="button" class="btn-icon-tool" :disabled="exportingFinesPdf" @click="previewFinesPdf" title="Cetak PDF" aria-label="Cetak PDF laporan denda">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 9V2h12v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
        <div v-if="finePaymentsLoading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="6" :columns="4" />
        </div>
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr><th>Tanggal</th><th>Peminjaman</th><th>Jumlah</th><th>Metode</th></tr>
            </thead>
            <tbody>
              <tr v-for="fp in finePayments" :key="fp.id">
                <td>{{ displayValue(fp.paid_at) }}</td>
                <td>#{{ fp.loan_id }}</td>
                <td>Rp {{ formatNumber(fp.amount) }}</td>
                <td>{{ displayValue(fp.payment_method) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="finePayments.length === 0" class="empty-state">
            <h3>Belum ada pembayaran denda</h3>
          </div>
          <PaginationBar
            embedded
            :page="finePaymentsMeta.current_page"
            :last-page="finePaymentsMeta.last_page"
            :per-page="finePaymentsMeta.per_page"
            :total="finePaymentsMeta.total"
            item-label="data"
            @page-change="loadFinePayments"
            @per-page-change="changeFinePaymentsPerPage"
          />
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
          <button type="button" class="btn-primary btn-add" :disabled="exportingPdf" @click="previewLoansPdf" title="Preview / Cetak PDF Laporan Peminjaman">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 9V2h12v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exportingPdf ? 'Memuat...' : 'Laporan Peminjaman' }}</span>
          </button>
          <button type="button" class="btn-secondary btn-add" :disabled="exportingFinesPdf" @click="previewFinesPdf" title="Cetak PDF Laporan Denda">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 9V2h12v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ exportingFinesPdf ? 'Memuat...' : 'Laporan Denda' }}</span>
          </button>
        </div>
        <div class="reports-grid">
          <div class="stat-cards">
            <div class="stat-card" v-for="(sc, idx) in reportStatCards" :key="idx" :style="{ animationDelay: idx * 0.05 + 's' }">
              <span class="stat-value">{{ sc.value }}</span>
              <span class="stat-label">{{ sc.label }}</span>
            </div>
          </div>
          <div class="report-section chart-section">
            <h3>Peminjaman per Bulan ({{ reportYear }})</h3>
            <div class="chart-wrap" v-if="!loansByMonthLoading">
              <Bar :data="loansByMonthData" :options="chartOptionsBar" />
            </div>
            <p v-else class="muted chart-placeholder">Memuat data peminjaman per bulan...</p>
            <select v-model="reportYear" @change="onReportYearChange" class="filter-select chart-year-select">
              <option v-for="y in reportYearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
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
          <div class="report-section chart-section">
            <h3>Bacaan Ebook per Bulan ({{ reportYear }})</h3>
            <div class="chart-wrap" v-if="!ebookViewsByMonthLoading">
              <Bar :data="ebookViewsByMonthData" :options="chartOptionsBar" />
            </div>
            <p v-else class="muted chart-placeholder">Memuat data bacaan ebook...</p>
          </div>
          <div class="report-section">
            <h3>Ebook Paling Sering Dibuka</h3>
            <p class="muted small" style="margin-bottom:0.75rem">Dihitung saat PDF berhasil dibuka (deduplikasi 30 menit per pengunjung).</p>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Pengarang</th>
                  <th>Total</th>
                  <th>Siswa</th>
                  <th>Staf</th>
                  <th>Publik</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(eb, i) in topEbooks" :key="i">
                  <td>
                    {{ eb.title }}
                    <span v-if="eb.is_public_ebook" class="badge badge-public" style="margin-left:0.35rem">Publik</span>
                  </td>
                  <td>{{ eb.author || '-' }}</td>
                  <td>{{ eb.ebook_view_count }}</td>
                  <td>{{ eb.views_student ?? 0 }}</td>
                  <td>{{ eb.views_staff ?? 0 }}</td>
                  <td>{{ eb.views_public ?? 0 }}</td>
                </tr>
              </tbody>
            </table>
            <p v-if="topEbooks.length === 0" class="muted">Belum ada data bacaan ebook.</p>
          </div>
        </div>
      </div>
        </div>
      </div>

      <!-- Confirm delete -->
      <ConfirmDialog
        :show="confirmShow"
        title="Konfirmasi Hapus"
        :message="confirmMessage"
        :loading="confirmLoading"
        @confirm="executeDelete"
        @update:show="confirmShow = $event"
      />

      <!-- MODALS (Category, Book, Copy, Loan, Return, FinePayment) - see script for refs -->
      <Teleport to="body">
        <Transition name="modal">
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
        </Transition>

        <Transition name="modal">
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
              <div class="form-group">
                <label>Ebook PDF (opsional, maks. 20 MB)</label>
                <input type="file" accept="application/pdf,.pdf" @change="onBookEbookChange" />
                <p v-if="editingBook?.has_ebook && !removeEbook" class="muted small" style="margin-top:0.35rem">
                  Ebook sudah terunggah.
                  <button type="button" class="btn-link" @click="removeEbook = true">Hapus ebook</button>
                </p>
                <p v-if="removeEbook" class="muted small" style="margin-top:0.35rem">
                  Ebook akan dihapus saat disimpan.
                  <button type="button" class="btn-link" @click="removeEbook = false">Batalkan</button>
                </p>
              </div>
              <div class="form-group">
                <label class="checkbox-label" :class="{ 'is-disabled': !canSetPublicEbook }">
                  <input type="checkbox" v-model="bookForm.is_public_ebook" :disabled="!canSetPublicEbook" />
                  Tampilkan di perpustakaan digital publik (tanpa login)
                </label>
                <p v-if="!canSetPublicEbook" class="muted small" style="margin-top:0.25rem">
                  Unggah file PDF ebook terlebih dahulu agar opsi ini bisa diaktifkan.
                </p>
                <p v-else class="muted small" style="margin-top:0.25rem">
                  Jika dicentang, ebook bisa dibaca tanpa login di halaman Perpustakaan Digital publik sekolah.
                </p>
              </div>
              <div class="modal-footer">
                <button type="button" @click="showBookModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Simpan</button>
              </div>
            </form>
          </div>
        </div>
        </Transition>

        <Transition name="modal">
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
        </Transition>

        <Transition name="modal">
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
        </Transition>

        <Transition name="modal">
        <div v-if="showReturnModal" class="modal-overlay" @click.self="showReturnModal = false">
          <div class="modal-card">
            <h3>Pengembalian Buku</h3>
            <p v-if="returningLoan" class="muted">Peminjam: {{ returningLoan.borrower_name }} · Buku: {{ returningLoan.copy?.book?.title }} ({{ returningLoan.copy?.copy_code }})</p>
            <form @submit.prevent="saveReturn">
              <div class="form-group"><label>Denda (Rp)</label><MoneyInput v-model="returnForm.fine_amount" :min="0" /></div>
              <div class="form-group"><label>Catatan</label><textarea v-model="returnForm.notes" rows="2"></textarea></div>
              <div class="modal-footer">
                <button type="button" @click="showReturnModal = false" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary" :disabled="saving">Kembalikan</button>
              </div>
            </form>
          </div>
        </div>
        </Transition>

        <Transition name="modal">
        <div v-if="showFinePaymentModal" class="modal-overlay" @click.self="showFinePaymentModal = false">
          <div class="modal-card">
            <h3>Bayar Denda</h3>
            <p v-if="finePaymentLoan" class="muted">Sisa denda: Rp {{ formatNumber(finePaymentLoan.remaining_fine) }}</p>
            <form @submit.prevent="saveFinePayment">
              <div class="form-group"><label>Jumlah (Rp) *</label><MoneyInput v-model="finePaymentForm.amount" :min="0" required /></div>
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
        </Transition>
      </Teleport>

      <Transition name="modal">
        <div v-if="showImportModal" class="modal-overlay" @click.self="closeImportModal">
          <div class="modal-card">
            <h3>Import Katalog Buku (Excel)</h3>
            <p class="muted small">
              Unduh template, isi data, lalu pilih file <strong>.xlsx</strong>.
              File dibaca di browser (sama seperti import siswa), lalu dikirim ke server per batch.
              Kolom wajib: <strong>kode_kategori</strong> dan <strong>judul</strong> (kode harus sudah ada di master kategori).
              Duplikat ISBN atau judul+pengarang akan diperbarui.
            </p>
            <div class="form-group" style="margin-top:1rem">
              <label>File Excel (.xlsx) *</label>
              <input
                ref="importFileInput"
                type="file"
                accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                @change="onImportFileChange"
              />
              <p class="muted small" style="margin-top:0.35rem">Maks. 10 MB · Format .xlsx</p>
            </div>
            <div class="form-group">
              <button type="button" class="btn-secondary btn-compact" :disabled="downloadingTemplate" @click="downloadBooksTemplate">
                {{ downloadingTemplate ? 'Mengunduh...' : 'Unduh Template Excel' }}
              </button>
            </div>
            <div v-if="importResult" class="import-result">
              <p>
                Ditambah: <strong>{{ importResult.success }}</strong> ·
                Diperbarui: <strong>{{ importResult.updated }}</strong> ·
                Gagal: <strong>{{ importResult.failed }}</strong>
              </p>
              <ul v-if="importResult.errors?.length" class="import-errors">
                <li v-for="(err, i) in importResult.errors.slice(0, 20)" :key="i">{{ err }}</li>
                <li v-if="importResult.errors.length > 20">… dan {{ importResult.errors.length - 20 }} error lainnya</li>
              </ul>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeImportModal" class="btn-secondary">Tutup</button>
              <button type="button" class="btn-primary" :disabled="importing || !importFile" @click="runImportBooks">
                {{ importing ? 'Mengimpor...' : 'Import' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import MoneyInput from '@/components/MoneyInput.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { libraryApi } from '@/api/library'
import { openPdfBlob } from '@/utils/pdfPreview'
import { useToast } from '@/composables/useToast'
import * as XLSX from 'xlsx'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const toast = useToast()

const activeTab = ref('books')
const saving = ref(false)

const KATALOG_TABS = ['books', 'copies']
const SIRKULASI_TABS = ['loans', 'fines']

const mainTabList = [
  { id: 'katalog', label: 'Katalog', icon: '📚' },
  { id: 'sirkulasi', label: 'Sirkulasi', icon: '📖' },
  { id: 'laporan', label: 'Laporan', icon: '📊' },
  { id: 'pengaturan', label: 'Pengaturan', icon: '🏷️' },
]

const mainTab = computed(() => {
  if (KATALOG_TABS.includes(activeTab.value)) return 'katalog'
  if (SIRKULASI_TABS.includes(activeTab.value)) return 'sirkulasi'
  if (activeTab.value === 'reports') return 'laporan'
  if (activeTab.value === 'categories') return 'pengaturan'
  return 'katalog'
})

const subTabList = computed(() => {
  if (mainTab.value === 'katalog') {
    return [
      { id: 'books', label: 'Katalog Buku' },
      { id: 'copies', label: 'Eksemplar' },
    ]
  }
  if (mainTab.value === 'sirkulasi') {
    return [
      { id: 'loans', label: 'Peminjaman' },
      { id: 'fines', label: 'Denda' },
    ]
  }
  return []
})

function switchMainTab(next) {
  if (next === 'katalog') {
    activeTab.value = KATALOG_TABS.includes(activeTab.value) ? activeTab.value : 'books'
  } else if (next === 'sirkulasi') {
    activeTab.value = SIRKULASI_TABS.includes(activeTab.value) ? activeTab.value : 'loans'
  } else if (next === 'laporan') {
    activeTab.value = 'reports'
  } else if (next === 'pengaturan') {
    activeTab.value = 'categories'
  }
}

// Categories
const categories = ref([])
const categoriesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const categoriesLoading = ref(false)
const categoriesForSelect = ref([])
const categoryFilters = ref({ search: '', is_active: '' })
const showCategoryModal = ref(false)
const editingCategory = ref(null)
const categoryForm = ref({ code: '', name: '', description: '', is_active: true })

// Books
const books = ref([])
const booksMeta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })
const booksLoading = ref(false)
const bookSortBy = ref('title')
const bookSortDir = ref('asc')
const bookFilters = ref({ search: '', category_id: '' })
const showBookModal = ref(false)
const editingBook = ref(null)
const bookForm = ref({ category_id: '', isbn: '', title: '', author: '', publisher: '', year: null, language: '', pages: null, shelf_code: '', description: '', is_public_ebook: false })
const bookCoverFile = ref(null)
const bookEbookFile = ref(null)
const removeEbook = ref(false)
const canSetPublicEbook = computed(() => {
  if (removeEbook.value) return false
  return !!(bookEbookFile.value || editingBook.value?.has_ebook)
})
const showImportModal = ref(false)
const importFile = ref(null)
const importFileInput = ref(null)
const importing = ref(false)
const downloadingTemplate = ref(false)
const exportingBooksCsv = ref(false)
const exportingBooksPdf = ref(false)
const importResult = ref(null)

// Copies
const copies = ref([])
const copiesMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const copiesLoading = ref(false)
const copyFilters = ref({ search: '', book_id: '', status: '' })
const showCopyModal = ref(false)
const editingCopy = ref(null)
const copyForm = ref({ book_id: '', copy_code: '', status: 'Tersedia', condition: 'Baik', notes: '' })
const booksList = ref([])

// Loans
const loans = ref([])
const loansMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
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
const finePaymentsMeta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const finePaymentsLoading = ref(false)
const fineFilters = ref({ loan_id: '' })
const showFinePaymentModal = ref(false)
const finePaymentLoan = ref(null)
const finePaymentForm = ref({ amount: 0, paid_at: '', payment_method: '', notes: '' })

// Reports
const stats = ref({})

function displayStat(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return typeof v === 'number' ? String(v) : String(v).trim() || 'Belum ada data'
}

function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

const topBooks = ref([])
const topEbooks = ref([])
const reportDateFrom = ref('')
const reportDateTo = ref('')
const exportingPdf = ref(false)
const exportingFinesPdf = ref(false)
const reportYear = ref(new Date().getFullYear())
const reportYearOptions = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2]
})
const loansByMonth = ref([])
const loansByMonthLoading = ref(false)
const ebookViewsByMonth = ref([])
const ebookViewsByMonthLoading = ref(false)

// Confirm delete
const confirmShow = ref(false)
const confirmTarget = ref(null) // { type: 'category'|'book'|'copy', item }
const confirmLoading = ref(false)

const confirmMessage = computed(() => {
  if (!confirmTarget.value) return ''
  const { type, item } = confirmTarget.value
  if (type === 'category') return `Hapus kategori "${item.name}"?`
  if (type === 'book') return `Hapus buku "${item.title}"?`
  if (type === 'copy') return `Hapus eksemplar ${item.copy_code}?`
  return ''
})

const reportStatCards = computed(() => [
  { value: stats.value.total_books ?? 0, label: 'Total Buku' },
  { value: stats.value.total_copies ?? 0, label: 'Total Eksemplar' },
  { value: stats.value.available_copies ?? 0, label: 'Tersedia' },
  { value: stats.value.borrowed_copies ?? 0, label: 'Dipinjam' },
  { value: stats.value.overdue_count ?? 0, label: 'Terlambat' },
  { value: 'Rp ' + formatNumber(stats.value.total_fines_collected ?? 0), label: 'Denda Terkumpul' },
  { value: stats.value.total_ebooks ?? 0, label: 'Total Ebook' },
  { value: stats.value.total_ebook_views ?? 0, label: 'Total Dibuka' },
  { value: stats.value.ebook_views_this_month ?? 0, label: 'Dibuka Bulan Ini' }
])

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1 } }
  }
}

function monthSeries(rows, label, color) {
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']
  const data = rows || []
  const countByMonth = Array.from({ length: 12 }, (_, i) => {
    const d = data.find(r => (r.month || 0) === i + 1)
    return d ? (d.count ?? d.loan_count ?? 0) : 0
  })
  return {
    labels: months,
    datasets: [{
      label,
      data: countByMonth,
      backgroundColor: color,
    }]
  }
}

const loansByMonthData = computed(() => monthSeries(loansByMonth.value, 'Peminjaman', 'rgba(5, 150, 105, 0.7)'))
const ebookViewsByMonthData = computed(() => monthSeries(ebookViewsByMonth.value, 'Bacaan Ebook', 'rgba(37, 99, 235, 0.7)'))

function formatNumber(n) { return Number(n).toLocaleString('id-ID') }
function formatDate(d) { return d ? (typeof d === 'string' ? d : d.toISOString().slice(0, 10)) : '-' }
function getErrorMessage(e) {
  const data = e.response?.data
  // Blob error responses (template/pdf download)
  if (data instanceof Blob) {
    return e.message || 'Terjadi kesalahan.'
  }
  const msg = data?.message
  if (msg) return msg
  const errs = data?.errors
  if (errs && typeof errs === 'object') {
    const first = Object.values(errs)[0]
    return Array.isArray(first) ? first[0] : first
  }
  return data?.error || e.message || 'Terjadi kesalahan.'
}

async function parseBlobError(blob) {
  try {
    const text = await blob.text()
    const parsed = JSON.parse(text)
    return parsed.message || parsed.error || 'Terjadi kesalahan.'
  } catch {
    return 'Terjadi kesalahan.'
  }
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
    const res = await libraryApi.getCategories({ page, per_page: categoriesMeta.value.per_page, search: categoryFilters.value.search || undefined, is_active: categoryFilters.value.is_active || undefined })
    categories.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    categoriesMeta.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? categoriesMeta.value.per_page,
      total: meta.total ?? 0
    }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    categoriesLoading.value = false
  }
}
async function loadBooks(page = 1) {
  booksLoading.value = true
  try {
    const res = await libraryApi.getBooks({
      page,
      per_page: booksMeta.value.per_page,
      search: bookFilters.value.search || undefined,
      category_id: bookFilters.value.category_id || undefined,
      sort_by: bookSortBy.value,
      sort_dir: bookSortDir.value
    })
    books.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    booksMeta.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      total: meta.total ?? 0,
      per_page: meta.per_page ?? booksMeta.value.per_page
    }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    booksLoading.value = false
  }
}

function bookRowNumber(index) {
  const page = booksMeta.value.current_page || 1
  const perPage = booksMeta.value.per_page || 25
  return (page - 1) * perPage + index + 1
}

function bookSortClass(column) {
  if (bookSortBy.value !== column) return 'is-idle'
  return bookSortDir.value === 'asc' ? 'is-asc' : 'is-desc'
}

function setBookSort(column) {
  if (bookSortBy.value === column) {
    bookSortDir.value = bookSortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    bookSortBy.value = column
    bookSortDir.value = 'asc'
  }
  loadBooks(1)
}
async function loadCopies(page = 1) {
  copiesLoading.value = true
  try {
    const res = await libraryApi.getCopies({ page, per_page: copiesMeta.value.per_page, book_id: copyFilters.value.book_id || undefined, status: copyFilters.value.status || undefined, search: copyFilters.value.search || undefined })
    copies.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    copiesMeta.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? copiesMeta.value.per_page,
      total: meta.total ?? 0
    }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    copiesLoading.value = false
  }
}
async function loadLoans(page = 1) {
  loansLoading.value = true
  try {
    const res = await libraryApi.getLoans({ page, per_page: loansMeta.value.per_page, status: loanFilters.value.status || undefined, borrower_type: loanFilters.value.borrower_type || undefined, search: loanFilters.value.search || undefined })
    loans.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    loansMeta.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? loansMeta.value.per_page,
      total: meta.total ?? 0
    }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    loansLoading.value = false
  }
}
async function loadFinePayments(page = 1) {
  finePaymentsLoading.value = true
  try {
    const res = await libraryApi.getFinePayments({ page, per_page: finePaymentsMeta.value.per_page, loan_id: fineFilters.value.loan_id || undefined })
    finePayments.value = res.data.data ?? []
    const meta = res.data.meta || res.data
    finePaymentsMeta.value = {
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? finePaymentsMeta.value.per_page,
      total: meta.total ?? 0
    }
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    finePaymentsLoading.value = false
  }
}
function changeBooksPerPage(n) {
  booksMeta.value.per_page = n
  booksMeta.value.current_page = 1
  loadBooks(1)
}
function changeCategoriesPerPage(n) {
  categoriesMeta.value.per_page = n
  categoriesMeta.value.current_page = 1
  loadCategories(1)
}
function changeCopiesPerPage(n) {
  copiesMeta.value.per_page = n
  copiesMeta.value.current_page = 1
  loadCopies(1)
}
function changeLoansPerPage(n) {
  loansMeta.value.per_page = n
  loansMeta.value.current_page = 1
  loadLoans(1)
}
function changeFinePaymentsPerPage(n) {
  finePaymentsMeta.value.per_page = n
  finePaymentsMeta.value.current_page = 1
  loadFinePayments(1)
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
async function loadTopEbooks() {
  try {
    const res = await libraryApi.getTopEbooks({ limit: 10 })
    topEbooks.value = res.data.data || []
  } catch (_) {
    topEbooks.value = []
  }
}
async function loadLoansByMonth() {
  loansByMonthLoading.value = true
  try {
    const res = await libraryApi.getLoansByMonth({ year: reportYear.value })
    const raw = res.data?.data ?? res.data
    loansByMonth.value = Array.isArray(raw) ? raw : []
  } catch (_) {
    loansByMonth.value = []
  } finally {
    loansByMonthLoading.value = false
  }
}
async function loadEbookViewsByMonth() {
  ebookViewsByMonthLoading.value = true
  try {
    const res = await libraryApi.getEbookViewsByMonth({ year: reportYear.value })
    const raw = res.data?.data ?? res.data
    ebookViewsByMonth.value = Array.isArray(raw) ? raw : []
  } catch (_) {
    ebookViewsByMonth.value = []
  } finally {
    ebookViewsByMonthLoading.value = false
  }
}
function onReportYearChange() {
  loadLoansByMonth()
  loadEbookViewsByMonth()
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
    loadCategoriesForSelect()
    loadBooksList()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    saving.value = false
  }
}
function confirmDelete(type, item) {
  confirmTarget.value = { type, item }
  confirmShow.value = true
}
async function executeDelete() {
  if (!confirmTarget.value) return
  confirmLoading.value = true
  const { type, item } = confirmTarget.value
  try {
    if (type === 'category') {
      await libraryApi.deleteCategory(item.id)
      toast.success('Berhasil', 'Kategori dihapus')
      loadCategories(categoriesMeta.value.current_page)
      loadCategoriesForSelect()
      loadBooksList()
    } else if (type === 'book') {
      await libraryApi.deleteBook(item.id)
      toast.success('Berhasil', 'Buku dihapus')
      loadBooks(booksMeta.value.current_page)
      loadBooksList()
      loadCopies(copiesMeta.value.current_page)
    } else if (type === 'copy') {
      await libraryApi.deleteCopy(item.id)
      toast.success('Berhasil', 'Eksemplar dihapus')
      loadCopies(copiesMeta.value.current_page)
      loadAvailableCopies()
    }
    confirmShow.value = false
    confirmTarget.value = null
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    confirmLoading.value = false
  }
}

function openBookModal(book = null) {
  editingBook.value = book
  bookCoverFile.value = null
  bookEbookFile.value = null
  removeEbook.value = false
  loadCategoriesForSelect()
  bookForm.value = book
    ? {
        category_id: String(book.category_id),
        isbn: book.isbn || '',
        title: book.title,
        author: book.author || '',
        publisher: book.publisher || '',
        year: book.year || null,
        language: book.language || '',
        pages: book.pages || null,
        shelf_code: book.shelf_code || '',
        description: book.description || '',
        is_public_ebook: !!book.is_public_ebook
      }
    : {
        category_id: '',
        isbn: '',
        title: '',
        author: '',
        publisher: '',
        year: null,
        language: '',
        pages: null,
        shelf_code: '',
        description: '',
        is_public_ebook: false
      }
  showBookModal.value = true
}
function onBookCoverChange(e) { bookCoverFile.value = e.target.files?.[0] || null }
function onBookEbookChange(e) {
  bookEbookFile.value = e.target.files?.[0] || null
  if (bookEbookFile.value) removeEbook.value = false
}
async function saveBook() {
  saving.value = true
  try {
    const payload = { ...bookForm.value }
    if (bookCoverFile.value) payload.cover = bookCoverFile.value
    if (bookEbookFile.value) payload.ebook = bookEbookFile.value
    if (removeEbook.value) {
      payload.remove_ebook = true
      payload.is_public_ebook = false
    }
    if (!canSetPublicEbook.value) {
      payload.is_public_ebook = false
    }
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
    if (!openPdfBlob(res, 'laporan-peminjaman.pdf')) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'PDF dibuka di tab baru. Anda dapat mencetak atau menyimpan dari sana.')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    exportingPdf.value = false
  }
}

async function previewFinesPdf() {
  exportingFinesPdf.value = true
  try {
    const params = {}
    if (reportDateFrom.value) params.date_from = reportDateFrom.value
    if (reportDateTo.value) params.date_to = reportDateTo.value
    const res = await libraryApi.exportFinesPdf(params)
    if (!openPdfBlob(res, 'laporan-denda.pdf')) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'PDF dibuka di tab baru. Anda dapat mencetak atau menyimpan dari sana.')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    exportingFinesPdf.value = false
  }
}

function bookExportParams() {
  return {
    search: bookFilters.value.search || undefined,
    category_id: bookFilters.value.category_id || undefined
  }
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  setTimeout(() => URL.revokeObjectURL(url), 30000)
}

async function exportBooksCsv() {
  exportingBooksCsv.value = true
  try {
    const res = await libraryApi.exportBooksCsv(bookExportParams())
    downloadBlob(new Blob([res.data], { type: 'text/csv;charset=utf-8' }), `katalog_buku_${Date.now()}.csv`)
    toast.success('Berhasil', 'CSV katalog diunduh')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    exportingBooksCsv.value = false
  }
}

async function exportBooksPdf() {
  exportingBooksPdf.value = true
  try {
    const res = await libraryApi.exportBooksPdf(bookExportParams())
    if (!openPdfBlob(res, 'katalog-buku.pdf')) {
      toast.error('Gagal', 'Pop-up diblokir atau file bukan PDF.')
      return
    }
    toast.success('Berhasil', 'PDF katalog dibuka di tab baru')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    exportingBooksPdf.value = false
  }
}

function closeImportModal() {
  showImportModal.value = false
  importFile.value = null
  importResult.value = null
  if (importFileInput.value) importFileInput.value.value = ''
}

function onImportFileChange(e) {
  importFile.value = e.target.files?.[0] || null
  importResult.value = null
}

async function downloadBooksTemplate() {
  downloadingTemplate.value = true
  try {
    let sampleCode = 'FKS'
    try {
      const meta = await libraryApi.downloadBooksTemplate()
      sampleCode = meta.data?.data?.sample_kode_kategori || categoriesForSelect.value?.[0]?.code || 'FKS'
    } catch {
      sampleCode = categoriesForSelect.value?.[0]?.code || 'FKS'
    }

    const templateData = [{
      kode_kategori: sampleCode,
      judul: 'Contoh Judul Buku',
      isbn: '9786020000000',
      pengarang: 'Nama Pengarang',
      penerbit: 'Nama Penerbit',
      tahun: 2024,
      bahasa: 'Indonesia',
      halaman: 200,
      rak: 'R-A-01',
      deskripsi: 'Deskripsi singkat (opsional)',
      jumlah_eksemplar: 2
    }]

    const wb = XLSX.utils.book_new()
    const ws = XLSX.utils.json_to_sheet(templateData)
    ws['!cols'] = [
      { wch: 14 }, { wch: 30 }, { wch: 16 }, { wch: 20 }, { wch: 18 },
      { wch: 8 }, { wch: 12 }, { wch: 10 }, { wch: 10 }, { wch: 30 }, { wch: 16 }
    ]
    XLSX.utils.book_append_sheet(wb, ws, 'Katalog Buku')
    XLSX.writeFile(wb, 'template_katalog_buku.xlsx')
    toast.success('Berhasil', 'Template Excel diunduh')
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e) || 'Gagal membuat template Excel')
  } finally {
    downloadingTemplate.value = false
  }
}

/** Excel sering mengirim angka; paksa string agar validasi/hosting tidak menolak (ISBN, kode, dll). */
function cellToText(value) {
  if (value === undefined || value === null) return null
  if (typeof value === 'number') {
    if (!Number.isFinite(value)) return null
    // Hindari 9786… jadi scientific notation
    if (Number.isInteger(value) || Math.floor(value) === value) {
      return String(Math.trunc(value))
    }
    return String(value)
  }
  const s = String(value).trim()
  return s === '' ? null : s
}

function cellToInt(value) {
  const s = cellToText(value)
  if (s === null || !/^-?\d+$/.test(s)) return null
  return parseInt(s, 10)
}

function mapExcelBookRow(row) {
  const get = (...keys) => {
    for (const key of keys) {
      if (row[key] !== undefined && row[key] !== null && String(row[key]).trim() !== '') {
        return row[key]
      }
    }
    const lowerMap = {}
    Object.keys(row || {}).forEach((k) => {
      lowerMap[String(k).toLowerCase().replace(/[\s-]+/g, '_')] = row[k]
    })
    for (const key of keys) {
      const normalized = String(key).toLowerCase().replace(/[\s-]+/g, '_')
      if (lowerMap[normalized] !== undefined && lowerMap[normalized] !== null && String(lowerMap[normalized]).trim() !== '') {
        return lowerMap[normalized]
      }
    }
    return null
  }

  return {
    kode_kategori: cellToText(get('kode_kategori', 'Kode Kategori', 'kode kategori')),
    judul: cellToText(get('judul', 'Judul')),
    isbn: cellToText(get('isbn', 'ISBN')),
    pengarang: cellToText(get('pengarang', 'Pengarang', 'author')),
    penerbit: cellToText(get('penerbit', 'Penerbit')),
    tahun: cellToInt(get('tahun', 'Tahun')),
    bahasa: cellToText(get('bahasa', 'Bahasa')),
    halaman: cellToInt(get('halaman', 'Halaman')),
    rak: cellToText(get('rak', 'Rak')),
    deskripsi: cellToText(get('deskripsi', 'Deskripsi')),
    jumlah_eksemplar: cellToInt(get('jumlah_eksemplar', 'Jumlah Eksemplar', 'eksemplar'))
  }
}

const IMPORT_CHUNK_SIZE = 100

async function runImportBooks() {
  if (!importFile.value) {
    toast.error('Gagal', 'Pilih file Excel (.xlsx) terlebih dahulu')
    return
  }
  const name = (importFile.value.name || '').toLowerCase()
  if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
    toast.error('Gagal', 'Format file harus Excel (.xlsx)')
    return
  }

  importing.value = true
  importResult.value = null
  try {
    // Parse di browser (SheetJS) — sama seperti import siswa; tidak butuh PHP zip di hosting
    const buffer = await importFile.value.arrayBuffer()
    const workbook = XLSX.read(buffer, { type: 'array', cellDates: true })
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
    const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '', raw: true })

    if (!jsonData.length) {
      toast.error('Gagal', 'File Excel kosong')
      return
    }

    const books = jsonData
      .map(mapExcelBookRow)
      .filter((row) => row.judul || row.kode_kategori || row.isbn)

    if (!books.length) {
      toast.error('Gagal', 'Tidak ada baris data yang dapat diimpor. Pastikan kolom judul/kode_kategori terisi.')
      return
    }

    const aggregated = { success: 0, updated: 0, failed: 0, errors: [] }

    for (let offset = 0; offset < books.length; offset += IMPORT_CHUNK_SIZE) {
      const chunk = books.slice(offset, offset + IMPORT_CHUNK_SIZE)
      const res = await libraryApi.importBooks(chunk)
      const d = res.data?.data || { success: 0, updated: 0, failed: 0, errors: [] }
      aggregated.success += d.success || 0
      aggregated.updated += d.updated || 0
      aggregated.failed += d.failed || 0
      if (Array.isArray(d.errors) && d.errors.length) {
        const rowBase = offset
        d.errors.forEach((err) => {
          // Geser nomor baris relatif chunk agar tetap global (best-effort)
          aggregated.errors.push(String(err).replace(/Baris (\d+)/, (_, n) => `Baris ${rowBase + Number(n)}`))
        })
      }
    }

    importResult.value = aggregated
    toast.success(
      'Import selesai',
      `+${aggregated.success} · update ${aggregated.updated} · gagal ${aggregated.failed}`
    )
    loadBooks(1)
    loadBooksList()
    loadStats()
    loadCategoriesForSelect()
  } catch (e) {
    toast.error('Gagal', getErrorMessage(e))
  } finally {
    importing.value = false
  }
}

watch(activeTab, (tab) => {
  if (tab === 'books') loadBooks(1)
  if (tab === 'categories') loadCategories(1)
  if (tab === 'copies') { loadCopies(1); loadBooksList() }
  if (tab === 'loans') loadLoans(1)
  if (tab === 'fines') loadFinePayments(1)
  if (tab === 'reports') {
    loadStats()
    loadTopBooks()
    loadTopEbooks()
    loadLoansByMonth()
    loadEbookViewsByMonth()
  }
})
onMounted(() => {
  loadStats()
  loadCategories(1)
  loadCategoriesForSelect()
  loadBooks(1)
  loadBooksList()
})
</script>

<style scoped>
.library-page { padding: 0 1rem 2rem; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%); min-height: 100%; }

/* Hero header */
.page-hero { position: relative; margin: -0.5rem -1rem 1.25rem -1rem; padding: 1.5rem 1.5rem 1.75rem; border-radius: 0 0 20px 20px; overflow: hidden; }
.hero-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%); opacity: 0.97; }
.hero-bg::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); opacity: 0.5; }
.hero-content { position: relative; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
.hero-icon-wrap { width: 56px; height: 56px; border-radius: 16px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; animation: iconFloat 3s ease-in-out infinite; }
.hero-icon { width: 32px; height: 32px; color: #fff; }
.hero-title { font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0 0 0.25rem 0; letter-spacing: -0.02em; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.hero-subtitle { color: rgba(255,255,255,0.9); margin: 0; font-size: 0.95rem; }
@keyframes iconFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }

/* Stats strip */
.stats-strip { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; }
.stat-item { background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.65rem 1rem; display: flex; align-items: baseline; gap: 0.5rem; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.stat-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15); }
.stat-item.stat-warn { background: linear-gradient(145deg, #fef3c7 0%, #fde68a 100%); border-color: #f59e0b; }
.stat-num { font-size: 1.15rem; font-weight: 700; color: #1e293b; }
.stat-tag { font-size: 0.8rem; color: #64748b; }
.stat-item.stat-warn .stat-num { color: #92400e; }
.stat-item.stat-warn .stat-tag { color: #b45309; }

/* Tabs */
.tab-shell {
  display: grid;
  grid-template-columns: 188px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
  margin-bottom: 1.25rem;
}
.section-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: #f8fafc;
  border-right: 1px solid #eef2f7;
}
.sec-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: none;
  background: transparent;
  border-radius: 10px;
  cursor: pointer;
  color: #64748b;
  text-align: left;
}
.sec-btn:hover:not(.active) { background: #fff; color: #0f172a; }
.sec-btn.active { background: #fff; color: #065f46; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0; }
.sec-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; flex-shrink: 0;
}
.sec-btn.active .sec-icon { background: #d1fae5; color: #047857; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; }
.tab-main { min-width: 0; padding: 14px 16px 16px; }
@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
}
.tab-icon { font-size: 1.1rem; line-height: 1; }
.sub-nav { display: flex; flex-wrap: wrap; gap: 0.35rem; padding: 0.65rem 0 0.85rem; }
.sub-nav-btn { padding: 0.4rem 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #64748b; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
.sub-nav-btn:hover { background: #fff; color: #0f172a; border-color: #cbd5e1; }
.sub-nav-btn.active { background: #ecfdf5; color: #047857; border-color: #6ee7b7; }
.tab-content { padding-top: 1rem; animation: tabIn 0.3s ease; }
@keyframes tabIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.tab-header { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
.tab-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-left: auto; }
.btn-compact { padding: 0.45rem 0.75rem; font-size: 0.85rem; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #334155; cursor: pointer; font-weight: 500; }
.btn-compact:hover:not(:disabled) { background: #f8fafc; }
.btn-compact:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary.btn-compact { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
.btn-secondary.btn-compact:hover:not(:disabled) { background: #d1fae5; }
.btn-icon-tool {
  width: 36px;
  height: 36px;
  padding: 0;
  border-radius: 8px;
  border: 1px solid #a7f3d0;
  background: #ecfdf5;
  color: #047857;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.1s ease;
}
.btn-icon-tool:hover:not(:disabled) { background: #d1fae5; transform: scale(1.05); }
.btn-icon-tool:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.filters-inline { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.search-wrap { position: relative; display: inline-flex; }
.search-input { padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 10px; min-width: 180px; transition: border-color 0.2s, box-shadow 0.2s; }
.search-input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
.search-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; border: none; background: #e2e8f0; color: #64748b; border-radius: 6px; cursor: pointer; font-size: 1.1rem; line-height: 1; display: flex; align-items: center; justify-content: center; transition: background 0.2s, color 0.2s; }
.search-clear:hover { background: #cbd5e1; color: #475569; }
.per-page-select { min-width: 120px; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 10px; min-width: 140px; }
.btn-primary { padding: 0.6rem 1.25rem; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.2s ease; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-add { display: inline-flex; align-items: center; gap: 0.4rem; }
.btn-secondary.btn-add {
  padding: 0.6rem 1.25rem;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}
.btn-secondary.btn-add:hover:not(:disabled) { background: #d1fae5; }
.btn-secondary.btn-add:disabled { opacity: 0.6; cursor: not-allowed; }
.import-result { margin-top: 0.75rem; padding: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
.import-errors { margin: 0.5rem 0 0; padding-left: 1.1rem; color: #b91c1c; font-size: 0.8rem; max-height: 160px; overflow-y: auto; }
.loading-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; padding: 0.5rem; background: #fff; }
.table-container { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
.data-table th { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); font-weight: 600; color: #065f46; font-size: 0.8rem; text-transform: uppercase; }
.data-table tbody tr { transition: background 0.15s ease; }
.data-table tbody tr:hover { background: #f1f5f9; }
.col-no { width: 3.25rem; text-align: center; color: #94a3b8; font-variant-numeric: tabular-nums; }
.col-aksi { width: 7.5rem; }
.th-sort {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0;
  border: none;
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  text-transform: uppercase;
}
.th-sort:hover { color: #047857; }
.sort-icon {
  display: inline-block;
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  opacity: 0.3;
  border-bottom: 5px solid currentColor;
}
.sort-icon.is-idle { opacity: 0.25; }
.sort-icon.is-asc { opacity: 1; border-bottom: 5px solid currentColor; border-top: 0; }
.sort-icon.is-desc { opacity: 1; border-bottom: 0; border-top: 5px solid currentColor; }
.name-cell .name { font-weight: 500; }
.muted { color: #64748b; font-size: 0.85rem; }
.small { font-size: 0.8rem; }
.action-buttons { display: flex; flex-wrap: nowrap; gap: 0.35rem; align-items: center; }
.btn-action {
  width: 34px;
  height: 34px;
  padding: 0;
  border-radius: 8px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.1s ease, background 0.15s ease;
}
.btn-action:hover { transform: scale(1.05); }
.btn-edit { background: rgba(5, 150, 105, 0.12); color: #059669; }
.btn-edit:hover { background: rgba(5, 150, 105, 0.2); }
.btn-secondary.btn-action,
.btn-action.btn-secondary { background: #ecfdf5; color: #047857; }
.btn-action.btn-secondary:hover { background: #d1fae5; }
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
.pagination-btn { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; transition: background 0.2s; }
.pagination-btn:hover:not(:disabled) { background: #f8fafc; }
.pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.pagination-info { font-size: 0.85rem; color: #64748b; }
.report-export-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
.filter-label { font-size: 0.9rem; color: #475569; font-weight: 500; }
.filter-sep { color: #64748b; font-size: 0.9rem; }
.reports-grid { display: flex; flex-direction: column; gap: 1.5rem; }
.stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
.stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; text-align: center; animation: statCardIn 0.4s ease backwards; }
.stat-card .stat-value { display: block; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
.stat-card .stat-label { font-size: 0.8rem; color: #64748b; }
@keyframes statCardIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.report-section h3 { margin: 0 0 0.75rem 0; font-size: 1rem; color: #475569; }
.chart-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
.chart-wrap { height: 260px; position: relative; }
.chart-year-select { margin-top: 0.75rem; max-width: 120px; }
.chart-placeholder { margin: 1rem 0 0; font-size: 0.9rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-card { background: #fff; border-radius: 16px; padding: 1.5rem; max-width: 480px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.2); }
.modal-wide { max-width: 560px; }
.modal-card h3 { margin: 0 0 1rem 0; font-size: 1.15rem; color: #1e293b; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.35rem; font-weight: 500; color: #475569; font-size: 0.9rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
.modal-footer .btn-secondary { padding: 0.5rem 1rem; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
.modal-footer .btn-secondary:hover { background: #e2e8f0; }
.badge-ebook { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 6px; background: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 600; }
.badge-public { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 6px; background: #eff6ff; color: #1d4ed8; font-size: 0.75rem; font-weight: 600; margin-left: 0.25rem; }
.checkbox-label { display: flex; align-items: flex-start; gap: 0.5rem; font-weight: 500; color: #334155; cursor: pointer; }
.checkbox-label input { margin-top: 0.2rem; }
.checkbox-label.is-disabled { opacity: 0.65; cursor: not-allowed; }
.btn-link { background: none; border: none; color: #059669; cursor: pointer; padding: 0; font-size: inherit; text-decoration: underline; }

/* Modal transition */
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-card, .modal-leave-active .modal-card { transition: transform 0.25s ease; }
.modal-enter-from .modal-card, .modal-leave-to .modal-card { transform: scale(0.95); }

@media (max-width: 768px) {
  .library-page { padding: 0 0.75rem 1.5rem; }
  .page-hero { margin-left: -0.75rem; margin-right: -0.75rem; padding: 1.25rem 1rem; }
  .hero-title { font-size: 1.5rem; }
  .search-input, .filter-select { min-width: 0; width: 100%; }
  .form-row { grid-template-columns: 1fr; }
  .modal-card, .modal-wide { max-width: 100%; margin: 0.5rem; }
}

@media (max-width: 480px) {
  .library-page { padding: 0 0.5rem 1rem; }
  .page-hero { margin-left: -0.5rem; margin-right: -0.5rem; padding: 1rem 0.75rem; }
  .hero-title { font-size: 1.25rem; }
  .hero-icon-wrap { width: 48px; height: 48px; }
  .data-table th, .data-table td { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
}
</style>
