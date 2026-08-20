<template>
  <Layout>
    <div class="lab-detail-page">
      <div class="detail-top">
        <router-link to="/lab" class="btn-back">← Kembali ke Manajemen Lab</router-link>
        <div v-if="room" class="detail-actions">
          <button v-if="canManage" type="button" class="btn-secondary btn-compact" @click="exportPdf">Cetak PDF</button>
          <button v-if="canManage" type="button" class="btn-secondary btn-compact" @click="openConditionModal">Ubah Kondisi</button>
        </div>
      </div>

      <div v-if="loading" class="loading-state"><p>Memuat detail lab...</p></div>
      <div v-else-if="!room" class="empty-state"><p>Lab tidak ditemukan.</p></div>

      <template v-else>
        <header class="lab-header">
          <div class="header-title-row">
            <h1>{{ room.name }}</h1>
            <span v-if="room.condition" :class="['status-badge', getConditionClass(room.condition)]">{{ room.condition }}</span>
          </div>
          <div class="meta-grid">
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Tipe</span>
                <span class="meta-value">{{ labTypeLabel(room.lab_type) }}</span>
              </div>
            </div>
            <div v-if="room.code" class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Kode</span>
                <span class="meta-value">{{ room.code }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 21V8l9-5 9 5v13" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21v-8h6v8" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Gedung</span>
                <span class="meta-value">{{ room.building?.name || '—' }}<template v-if="room.floor != null"> · Lt. {{ room.floor }}</template></span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Penanggung jawab</span>
                <span class="meta-value">{{ room.responsible_employee?.name || 'Belum ditetapkan' }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Inventaris</span>
                <span class="meta-value">{{ room.stats?.inventory_count ?? 0 }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon meta-icon-warn" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Rusak</span>
                <span class="meta-value">{{ room.stats?.damaged_count ?? 0 }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M16 3h5v5M21 3l-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Dipinjam</span>
                <span class="meta-value">{{ room.stats?.active_loans ?? 0 }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon meta-icon-accent" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Booking pending</span>
                <span class="meta-value">{{ room.stats?.pending_bookings ?? 0 }}</span>
              </div>
            </div>
            <div class="meta-item">
              <span class="meta-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
              </span>
              <div class="meta-body">
                <span class="meta-label">Perawatan</span>
                <span class="meta-value">{{ room.stats?.open_maintenance ?? 0 }}</span>
              </div>
            </div>
          </div>
        </header>

        <div class="tab-shell">
          <nav class="section-nav" role="tablist">
            <button
              v-for="s in sections"
              :key="s.key"
              type="button"
              role="tab"
              :class="['sec-btn', { active: section === s.key }]"
              :aria-selected="section === s.key"
              @click="section = s.key"
            >
              <span class="sec-icon" aria-hidden="true">
                <svg v-if="s.key === 'inventory'" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
                <svg v-else-if="s.key === 'schedule'" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <svg v-else-if="s.key === 'loans'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M16 3h5v5M21 3l-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="2"/></svg>
                <svg v-else-if="s.key === 'booking'" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 3v4M16 3v4M8 14h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <svg v-else-if="s.key === 'maintenance'" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
              </span>
              <span class="sec-label">{{ s.label }}</span>
              <span v-if="s.badge" class="sec-badge">{{ s.badge }}</span>
            </button>
          </nav>
          <div class="tab-main">

        <!-- Inventaris -->
        <section v-show="section === 'inventory'" class="panel">
          <div class="panel-head">
            <h2>Inventaris Lab</h2>
            <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openItemModal()">Tambah Barang</button>
          </div>
          <div v-if="sectionLoading.inventory" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Kode</th><th>Nama</th><th>Qty</th><th>Kondisi</th><th>Status</th><th v-if="canManage">Aksi</th></tr></thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" :class="{ 'row-warn': isDamaged(item) }">
                <td>{{ item.code || '-' }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.quantity }} {{ item.unit || '' }}</td>
                <td>{{ item.condition }}</td>
                <td>{{ item.status }}</td>
                <td v-if="canManage" class="actions-cell">
                  <TableAction kind="edit" @click="openItemModal(item)" />
                  <button type="button" class="btn-action btn-xs" @click="openMaintFromItem(item)">Laporkan Rusak</button>
                  <TableAction kind="delete" @click="deleteItem(item)" />
                </td>
              </tr>
              <tr v-if="!items.length"><td :colspan="canManage ? 6 : 5" class="muted">Belum ada barang.</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Jadwal -->
        <section v-show="section === 'schedule'" class="panel">
          <div class="panel-head">
            <h2>Jadwal Penggunaan Lab</h2>
            <button v-if="canManage && activeSemesterId" type="button" class="btn-primary btn-compact" @click="openScheduleModal()">Tambah Jadwal</button>
          </div>
          <p v-if="!activeSemesterId" class="muted">Pilih semester aktif di profil instansi untuk mengelola jadwal.</p>
          <div v-else-if="sectionLoading.schedule" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Hari</th><th>Jam</th><th>Waktu</th><th>Mapel</th><th>Kelas</th><th>Guru</th><th v-if="canManage">Aksi</th></tr></thead>
            <tbody>
              <tr v-for="s in schedules" :key="s.id">
                <td>{{ s.day_name || dayNamesMap[s.day_of_week] }}</td>
                <td>{{ s.period }}</td>
                <td>{{ s.start_time }}-{{ s.end_time }}</td>
                <td>{{ s.subject?.name || '-' }}</td>
                <td>{{ s.school_class?.name || '-' }}</td>
                <td>{{ s.employee?.name || '-' }}</td>
                <td v-if="canManage" class="actions-cell">
                  <TableAction kind="edit" @click="openScheduleModal(s)" />
                  <TableAction kind="delete" @click="deleteSchedule(s)" />
                </td>
              </tr>
              <tr v-if="!schedules.length"><td :colspan="canManage ? 7 : 6" class="muted">Belum ada jadwal.</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Peminjaman -->
        <section v-show="section === 'loans'" class="panel">
          <div class="panel-head">
            <h2>Peminjaman Alat</h2>
            <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openLoanModal()">Catat Pinjam</button>
          </div>
          <div v-if="sectionLoading.loans" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Tanggal</th><th>Barang</th><th>Peminjam</th><th>Qty</th><th>Kembali</th><th>Status</th><th v-if="canManage">Aksi</th></tr></thead>
            <tbody>
              <tr v-for="loan in loans" :key="loan.id" :class="{ 'row-warn': loan.is_overdue || loan.status === 'Terlambat' }">
                <td>{{ loan.loan_date }}</td>
                <td>{{ loan.item?.name || '-' }}</td>
                <td>{{ loan.borrower_name }}</td>
                <td>{{ loan.quantity }}</td>
                <td>{{ loan.expected_return_date }}</td>
                <td>{{ loan.status }}{{ loan.is_overdue ? ' (Terlambat)' : '' }}</td>
                <td v-if="canManage" class="actions-cell">
                  <TableAction v-if="['Dipinjam','Terlambat'].includes(loan.status)" kind="return" @click="openReturnModal(loan)" />
                </td>
              </tr>
              <tr v-if="!loans.length"><td :colspan="canManage ? 7 : 6" class="muted">Belum ada peminjaman.</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Booking -->
        <section v-show="section === 'booking'" class="panel">
          <div class="panel-head">
            <h2>Booking Lab</h2>
            <button type="button" class="btn-primary btn-compact" @click="openBookingModal()">Ajukan Booking</button>
          </div>
          <div v-if="sectionLoading.booking" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Tanggal</th><th>Waktu</th><th>Pemohon</th><th>Keperluan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr v-for="b in bookings" :key="b.id">
                <td>{{ formatDate(b.date) }}</td>
                <td>{{ formatTime(b.start_time) }}-{{ formatTime(b.end_time) }}</td>
                <td>{{ b.requester_name || b.requester_employee?.name || '-' }}</td>
                <td>{{ b.purpose }}</td>
                <td><span :class="'st-' + b.status">{{ statusLabel(b.status) }}</span></td>
                <td class="actions-cell">
                  <template v-if="canManage && b.status === 'pending'">
                    <TableAction kind="approve" @click="approveBooking(b)" />
                    <TableAction kind="reject" @click="rejectBooking(b)" />
                  </template>
                  <TableAction v-if="['pending','approved'].includes(b.status)" kind="cancel" @click="cancelBooking(b)" />
                </td>
              </tr>
              <tr v-if="!bookings.length"><td colspan="6" class="muted">Belum ada booking.</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Perawatan -->
        <section v-show="section === 'maintenance'" class="panel">
          <div class="panel-head">
            <h2>Perawatan & Kerusakan</h2>
            <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openMaintModal()">Laporkan Kerusakan</button>
          </div>
          <div v-if="sectionLoading.maintenance" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Tanggal</th><th>Barang</th><th>Jenis</th><th>Status</th><th>Deskripsi</th><th v-if="canManage">Aksi</th></tr></thead>
            <tbody>
              <tr v-for="m in maintenances" :key="m.id">
                <td>{{ m.scheduled_date }}</td>
                <td>{{ m.item?.name || '-' }}</td>
                <td>{{ m.maintenance_type }}</td>
                <td>{{ m.status }}</td>
                <td>{{ m.description || '-' }}</td>
                <td v-if="canManage" class="actions-cell">
                  <TableAction v-if="m.status !== 'Selesai'" kind="complete" @click="completeMaint(m)" />
                </td>
              </tr>
              <tr v-if="!maintenances.length"><td :colspan="canManage ? 6 : 5" class="muted">Belum ada catatan perawatan.</td></tr>
            </tbody>
          </table>
        </section>

        <!-- Jurnal -->
        <section v-show="section === 'journal'" class="panel">
          <div class="panel-head">
            <h2>Jurnal Pemakaian Lab</h2>
            <button v-if="canManage" type="button" class="btn-primary btn-compact" @click="openJournalModal()">Tambah Jurnal</button>
          </div>
          <div v-if="sectionLoading.journal" class="muted">Memuat...</div>
          <table v-else class="data-table">
            <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Kelas</th><th>Peserta</th><th>Insiden</th><th v-if="canManage">Aksi</th></tr></thead>
            <tbody>
              <tr v-for="j in journals" :key="j.id">
                <td>{{ formatDate(j.date) }}</td>
                <td>{{ j.activity }}</td>
                <td>{{ j.school_class?.name || '-' }}</td>
                <td>{{ j.participants_count ?? '-' }}</td>
                <td>{{ j.incident_notes || '-' }}</td>
                <td v-if="canManage" class="actions-cell">
                  <TableAction kind="delete" @click="deleteJournal(j)" />
                </td>
              </tr>
              <tr v-if="!journals.length"><td :colspan="canManage ? 6 : 5" class="muted">Belum ada jurnal.</td></tr>
            </tbody>
          </table>
        </section>
          </div>
        </div>
      </template>

      <!-- Modals -->
      <div v-if="showItemModal" class="modal-overlay" @click.self="showItemModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>{{ editingItem ? 'Edit Barang' : 'Tambah Barang' }}</h3><button type="button" class="btn-close" @click="showItemModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveItem">
            <div class="form-group"><label>Nama *</label><input v-model="itemForm.name" required class="form-input" /></div>
            <div class="form-group"><label>Kategori *</label>
              <select v-model="itemForm.category_id" required class="form-input">
                <option value="">Pilih kategori</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Kode</label><input v-model="itemForm.code" class="form-input" placeholder="Kosongkan = otomatis" /></div>
              <div class="form-group"><label>Qty *</label><input v-model.number="itemForm.quantity" type="number" min="1" required class="form-input" /></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Satuan</label><input v-model="itemForm.unit" class="form-input" placeholder="Unit" /></div>
              <div class="form-group"><label>Kondisi</label>
                <select v-model="itemForm.condition" class="form-input">
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                  <option value="Habis Pakai">Habis Pakai</option>
                </select>
              </div>
            </div>
            <div class="form-group"><label>Status</label>
              <select v-model="itemForm.status" class="form-input">
                <option value="Tersedia">Tersedia</option>
                <option value="Dipinjam">Dipinjam</option>
                <option value="Rusak">Rusak</option>
                <option value="Hilang">Hilang</option>
              </select>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showItemModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>

      <div v-if="showScheduleModal" class="modal-overlay" @click.self="showScheduleModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>{{ editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h3><button type="button" class="btn-close" @click="showScheduleModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveSchedule">
            <div class="form-row">
              <div class="form-group"><label>Hari *</label>
                <select v-model.number="scheduleForm.day_of_week" required class="form-input">
                  <option v-for="(n, d) in dayNamesMap" :key="d" :value="Number(d)">{{ n }}</option>
                </select>
              </div>
              <div class="form-group"><label>Jam ke *</label><input v-model.number="scheduleForm.period" type="number" min="1" max="10" required class="form-input" /></div>
            </div>
            <div class="form-group"><label>Kelas *</label>
              <select v-model="scheduleForm.class_id" required class="form-input"><option value="">Pilih</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </div>
            <div class="form-group"><label>Mapel *</label>
              <select v-model="scheduleForm.subject_id" required class="form-input"><option value="">Pilih</option><option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option></select>
            </div>
            <div class="form-group"><label>Guru *</label>
              <select v-model="scheduleForm.employee_id" required class="form-input"><option value="">Pilih</option><option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option></select>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showScheduleModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>

      <div v-if="showLoanModal" class="modal-overlay" @click.self="showLoanModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>Catat Peminjaman</h3><button type="button" class="btn-close" @click="showLoanModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveLoan">
            <div class="form-group"><label>Barang *</label>
              <select v-model="loanForm.item_id" required class="form-input"><option value="">Pilih</option><option v-for="i in availableItems" :key="i.id" :value="i.id">{{ i.name }} (stok {{ i.quantity }})</option></select>
            </div>
            <div class="form-group"><label>Nama peminjam *</label><input v-model="loanForm.borrower_name" required class="form-input" /></div>
            <div class="form-row">
              <div class="form-group"><label>Qty *</label><input v-model.number="loanForm.quantity" type="number" min="1" required class="form-input" /></div>
              <div class="form-group"><label>Tipe</label>
                <select v-model="loanForm.borrower_type" class="form-input"><option>Employee</option><option>Student</option><option>External</option></select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Tgl pinjam *</label><input v-model="loanForm.loan_date" type="date" required class="form-input" /></div>
              <div class="form-group"><label>Rencana kembali *</label><input v-model="loanForm.expected_return_date" type="date" required class="form-input" /></div>
            </div>
            <div class="form-group"><label>Keperluan</label><input v-model="loanForm.purpose" class="form-input" /></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showLoanModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>

      <div v-if="showReturnModal" class="modal-overlay" @click.self="showReturnModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>Pengembalian</h3><button type="button" class="btn-close" @click="showReturnModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveReturn">
            <div class="form-group"><label>Tanggal kembali *</label><input v-model="returnForm.actual_return_date" type="date" required class="form-input" /></div>
            <div class="form-group"><label>Kondisi saat kembali</label>
              <select v-model="returnForm.return_condition" class="form-input">
                <option value="">—</option>
                <option value="Baik">Baik</option>
                <option value="Rusak Ringan">Rusak Ringan</option>
                <option value="Rusak Berat">Rusak Berat</option>
                <option value="Habis Pakai">Habis Pakai</option>
              </select>
            </div>
            <div class="form-group"><label>Status barang</label>
              <select v-model="returnForm.return_item_status" class="form-input"><option value="Tersedia">Tersedia</option><option value="Rusak">Rusak</option><option value="Hilang">Hilang</option></select>
            </div>
            <div class="form-group"><label>Catatan</label><textarea v-model="returnForm.notes" class="form-input" rows="2"></textarea></div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showReturnModal = false">Batal</button>
              <button type="button" class="btn-danger" :disabled="saving" @click="markLost">Tandai Hilang</button>
              <button type="submit" class="btn-primary" :disabled="saving">Kembalikan</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showBookingModal" class="modal-overlay" @click.self="showBookingModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>Ajukan Booking Lab</h3><button type="button" class="btn-close" @click="showBookingModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveBooking">
            <div class="form-group"><label>Keperluan *</label><input v-model="bookingForm.purpose" required class="form-input" /></div>
            <div class="form-group"><label>Tanggal *</label><input v-model="bookingForm.date" type="date" required class="form-input" /></div>
            <div class="form-row">
              <div class="form-group"><label>Mulai *</label><input v-model="bookingForm.start_time" type="time" required class="form-input" /></div>
              <div class="form-group"><label>Selesai *</label><input v-model="bookingForm.end_time" type="time" required class="form-input" /></div>
            </div>
            <div class="form-group"><label>Catatan</label><textarea v-model="bookingForm.notes" class="form-input" rows="2"></textarea></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showBookingModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Kirim</button></div>
          </form>
        </div>
      </div>

      <div v-if="showMaintModal" class="modal-overlay" @click.self="showMaintModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>Laporkan Kerusakan / Perawatan</h3><button type="button" class="btn-close" @click="showMaintModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveMaint">
            <div class="form-group"><label>Barang *</label>
              <select v-model="maintForm.item_id" required class="form-input"><option value="">Pilih</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option></select>
            </div>
            <div class="form-group"><label>Jenis *</label>
              <select v-model="maintForm.maintenance_type" class="form-input"><option>Perbaikan</option><option>Perawatan</option><option>Kalibrasi</option><option>Inspeksi</option></select>
            </div>
            <div class="form-group"><label>Tanggal *</label><input v-model="maintForm.scheduled_date" type="date" required class="form-input" /></div>
            <div class="form-group"><label>Deskripsi</label><textarea v-model="maintForm.description" class="form-input" rows="2"></textarea></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showMaintModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>

      <div v-if="showJournalModal" class="modal-overlay" @click.self="showJournalModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header"><h3>Jurnal Pemakaian</h3><button type="button" class="btn-close" @click="showJournalModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveJournal">
            <div class="form-group"><label>Tanggal *</label><input v-model="journalForm.date" type="date" required class="form-input" /></div>
            <div class="form-row">
              <div class="form-group"><label>Mulai</label><input v-model="journalForm.start_time" type="time" class="form-input" /></div>
              <div class="form-group"><label>Selesai</label><input v-model="journalForm.end_time" type="time" class="form-input" /></div>
            </div>
            <div class="form-group"><label>Kegiatan *</label><input v-model="journalForm.activity" required class="form-input" /></div>
            <div class="form-row">
              <div class="form-group"><label>Kelas</label>
                <select v-model="journalForm.class_id" class="form-input"><option value="">—</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
              </div>
              <div class="form-group"><label>Peserta</label><input v-model.number="journalForm.participants_count" type="number" min="0" class="form-input" /></div>
            </div>
            <div class="form-group"><label>Catatan</label><textarea v-model="journalForm.notes" class="form-input" rows="2"></textarea></div>
            <div class="form-group"><label>Catatan insiden</label><textarea v-model="journalForm.incident_notes" class="form-input" rows="2"></textarea></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showJournalModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>

      <div v-if="showConditionModal" class="modal-overlay" @click.self="showConditionModal = false">
        <div class="modal-content modal-narrow" @click.stop>
          <div class="modal-header"><h3>Ubah Kondisi Lab</h3><button type="button" class="btn-close" @click="showConditionModal = false">×</button></div>
          <form class="modal-body" @submit.prevent="saveCondition">
            <div class="form-group"><label>Kondisi</label>
              <select v-model="conditionForm.condition" class="form-input"><option>Baik</option><option>Rusak Ringan</option><option>Rusak Sedang</option><option>Rusak Berat</option></select>
            </div>
            <div class="form-group"><label>Deskripsi</label><textarea v-model="conditionForm.description" class="form-input" rows="2"></textarea></div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="showConditionModal = false">Batal</button><button type="submit" class="btn-primary" :disabled="saving">Simpan</button></div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { facilityApi } from '@/api/facility'
import { inventoryApi } from '@/api/inventory'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { institutionApi } from '@/api/institution'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { employeeApi } from '@/api/teacher'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const dayNamesMap = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' }
const SECTION_KEYS = ['inventory', 'schedule', 'loans', 'booking', 'maintenance', 'journal']
const loading = ref(true)
const saving = ref(false)
const room = ref(null)
const section = ref('inventory')
const items = ref([])
const schedules = ref([])
const loans = ref([])
const bookings = ref([])
const maintenances = ref([])
const journals = ref([])
const classes = ref([])
const subjects = ref([])
const teachers = ref([])
const categories = ref([])
const activeSemesterId = ref(null)

const sectionLoaded = reactive({
  inventory: false,
  schedule: false,
  loans: false,
  booking: false,
  maintenance: false,
  journal: false,
})
const sectionLoading = reactive({
  inventory: false,
  schedule: false,
  loans: false,
  booking: false,
  maintenance: false,
  journal: false,
})
const lookupsLoaded = reactive({
  categories: false,
  schedule: false,
  classes: false,
})
let scheduleLookupsPromise = null
let categoriesPromise = null

const canManage = computed(() => !!room.value?.can_manage)
const isAdmin = computed(() => {
  const r = authStore.user?.role
  return r === 'admin' || r === 'institution_admin' || r === 'super_admin' || !!room.value?.is_admin
})
const availableItems = computed(() => items.value.filter(i => i.status === 'Tersedia' || !i.status))

const sections = computed(() => [
  { key: 'inventory', label: 'Inventaris' },
  { key: 'schedule', label: 'Jadwal' },
  { key: 'loans', label: 'Peminjaman', badge: room.value?.stats?.active_loans || null },
  { key: 'booking', label: 'Booking', badge: room.value?.stats?.pending_bookings || null },
  { key: 'maintenance', label: 'Perawatan', badge: room.value?.stats?.open_maintenance || null },
  { key: 'journal', label: 'Jurnal' },
])

const showItemModal = ref(false)
const editingItem = ref(null)
const itemForm = reactive({ name: '', code: '', category_id: '', quantity: 1, unit: 'Unit', condition: 'Baik', status: 'Tersedia' })

const showScheduleModal = ref(false)
const editingSchedule = ref(null)
const scheduleForm = reactive({ day_of_week: 1, period: 1, class_id: '', subject_id: '', employee_id: '' })

const showLoanModal = ref(false)
const loanForm = reactive({
  item_id: '', borrower_name: '', borrower_type: 'Employee', quantity: 1,
  loan_date: new Date().toISOString().slice(0, 10),
  expected_return_date: '', purpose: ''
})

const showReturnModal = ref(false)
const returningLoan = ref(null)
const returnForm = reactive({ actual_return_date: new Date().toISOString().slice(0, 10), return_condition: '', return_item_status: 'Tersedia', notes: '' })

const showBookingModal = ref(false)
const bookingForm = reactive({ purpose: '', date: '', start_time: '08:00', end_time: '10:00', notes: '' })

const showMaintModal = ref(false)
const maintForm = reactive({ item_id: '', maintenance_type: 'Perbaikan', scheduled_date: new Date().toISOString().slice(0, 10), description: '' })

const showJournalModal = ref(false)
const journalForm = reactive({ date: new Date().toISOString().slice(0, 10), start_time: '', end_time: '', activity: '', class_id: '', participants_count: null, notes: '', incident_notes: '' })

const showConditionModal = ref(false)
const conditionForm = reactive({ condition: 'Baik', description: '' })

function labTypeLabel(key) {
  return ({ IPA: 'Lab IPA', Komputer: 'Lab Komputer', Bahasa: 'Lab Bahasa', Lainnya: 'Lainnya' })[key] || key || 'Lab'
}
function getConditionClass(c) {
  if (!c) return ''
  return 'condition-' + String(c).toLowerCase().replace(/\s/g, '-')
}
function isDamaged(item) {
  return ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'].includes(item.condition) || ['Rusak', 'Hilang'].includes(item.status)
}
function formatDate(d) {
  if (!d) return '-'
  return String(d).slice(0, 10)
}
function formatTime(t) {
  if (!t) return '-'
  return String(t).slice(0, 5)
}
function statusLabel(s) {
  return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' })[s] || s
}

function unwrapList(res) {
  const d = res?.data
  if (Array.isArray(d?.data)) return d.data
  if (Array.isArray(d)) return d
  return []
}

function resetSectionCache() {
  for (const key of SECTION_KEYS) {
    sectionLoaded[key] = false
    sectionLoading[key] = false
  }
  items.value = []
  schedules.value = []
  loans.value = []
  bookings.value = []
  maintenances.value = []
  journals.value = []
}

async function loadRoom({ silent = false } = {}) {
  if (!silent) loading.value = true
  try {
    const res = await facilityApi.getRoom(route.params.id)
    room.value = res?.data?.data ?? res?.data ?? null
    if (room.value && route.query.section) section.value = String(route.query.section)
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat lab')
    room.value = null
  } finally {
    if (!silent) loading.value = false
  }
}

async function loadSection(key, { force = false } = {}) {
  if (!room.value || !SECTION_KEYS.includes(key)) return
  if (!force && (sectionLoaded[key] || sectionLoading[key])) return

  const id = room.value.id
  sectionLoading[key] = true
  try {
    if (key === 'inventory') {
      const res = await inventoryApi.getItems({ room_id: id, per_page: 100 })
      items.value = unwrapList(res)
    } else if (key === 'schedule') {
      if (activeSemesterId.value) {
        const res = await lessonScheduleApi.getByRoom(id, { semester_id: activeSemesterId.value })
        schedules.value = unwrapList(res)
      } else {
        schedules.value = []
      }
    } else if (key === 'loans') {
      const res = await inventoryApi.getLoans({ room_id: id, per_page: 50 })
      loans.value = unwrapList(res)
    } else if (key === 'booking') {
      const res = await facilityApi.getLabBookings({ room_id: id, per_page: 50 })
      bookings.value = unwrapList(res)
    } else if (key === 'maintenance') {
      const res = await inventoryApi.getMaintenances({ room_id: id, per_page: 50 })
      maintenances.value = unwrapList(res)
    } else if (key === 'journal') {
      const res = await facilityApi.getLabJournals({ room_id: id, per_page: 50 })
      journals.value = unwrapList(res)
    }
    sectionLoaded[key] = true
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memuat data lab')
  } finally {
    sectionLoading[key] = false
  }
}

async function refreshSections(keys, { withRoom = false } = {}) {
  const tasks = keys.map((key) => loadSection(key, { force: true }))
  if (withRoom) tasks.push(loadRoom({ silent: true }))
  await Promise.all(tasks)
}

async function ensureCategories() {
  if (lookupsLoaded.categories) return
  if (categoriesPromise) return categoriesPromise
  categoriesPromise = inventoryApi.getCategories({ per_page: 200 })
    .then((r) => { categories.value = unwrapList(r) })
    .catch(() => { categories.value = [] })
    .finally(() => {
      lookupsLoaded.categories = true
      categoriesPromise = null
    })
  return categoriesPromise
}

async function ensureScheduleLookups() {
  if (lookupsLoaded.schedule) return
  if (scheduleLookupsPromise) return scheduleLookupsPromise
  scheduleLookupsPromise = Promise.all([
    classApi.getAll({ per_page: 200 }).then((r) => { classes.value = unwrapList(r) }).catch(() => {}),
    subjectApi.getAll({ per_page: 200 }).then((r) => { subjects.value = unwrapList(r) }).catch(() => {}),
    employeeApi.getAll({ per_page: 500, type: 'Guru' }).then(async (r) => {
      let list = unwrapList(r)
      if (!list.length) {
        try {
          const r2 = await employeeApi.getAll({ per_page: 500 })
          list = unwrapList(r2)
        } catch {}
      }
      teachers.value = list
    }).catch(() => { teachers.value = [] }),
  ]).finally(() => {
    lookupsLoaded.schedule = true
    lookupsLoaded.classes = true
    scheduleLookupsPromise = null
  })
  return scheduleLookupsPromise
}

async function ensureClasses() {
  if (lookupsLoaded.classes || classes.value.length) {
    lookupsLoaded.classes = true
    return
  }
  try {
    const r = await classApi.getAll({ per_page: 200 })
    classes.value = unwrapList(r)
  } catch {
    classes.value = []
  } finally {
    lookupsLoaded.classes = true
  }
}

function defaultLabCategoryId() {
  const lab = categories.value.find(c => String(c.code || '').toUpperCase() === 'LAB' || /lab/i.test(c.name || ''))
  return lab?.id || categories.value[0]?.id || ''
}

async function openItemModal(item = null) {
  await ensureCategories()
  editingItem.value = item
  itemForm.name = item?.name || ''
  itemForm.code = item?.code || ''
  itemForm.category_id = item?.category_id || item?.category?.id || defaultLabCategoryId()
  itemForm.quantity = item?.quantity ?? 1
  itemForm.unit = item?.unit || 'Unit'
  itemForm.condition = item?.condition || 'Baik'
  if (!['Baik', 'Rusak Ringan', 'Rusak Berat', 'Habis Pakai'].includes(itemForm.condition)) {
    itemForm.condition = 'Baik'
  }
  itemForm.status = item?.status || 'Tersedia'
  if (!['Tersedia', 'Dipinjam', 'Rusak', 'Hilang', 'Dijual'].includes(itemForm.status)) {
    itemForm.status = 'Tersedia'
  }
  showItemModal.value = true
}

async function saveItem() {
  if (!itemForm.category_id) {
    toast.error('Validasi', 'Kategori wajib dipilih')
    return
  }
  saving.value = true
  try {
    const payload = {
      name: itemForm.name,
      category_id: Number(itemForm.category_id),
      quantity: Number(itemForm.quantity),
      unit: itemForm.unit || 'Unit',
      condition: itemForm.condition,
      status: itemForm.status || 'Tersedia',
      room_id: room.value.id,
      building_id: room.value.building_id || undefined,
    }
    if (itemForm.code) payload.code = itemForm.code
    if (editingItem.value) await inventoryApi.updateItem(editingItem.value.id, payload)
    else await inventoryApi.createItem(payload)
    toast.success('Berhasil', 'Barang disimpan')
    showItemModal.value = false
    await refreshSections(['inventory'], { withRoom: true })
  } catch (e) {
    const msg = e.response?.data?.message
      || (e.response?.data?.errors && Object.values(e.response.data.errors).flat()[0])
      || 'Gagal menyimpan'
    toast.error('Gagal', msg)
  } finally { saving.value = false }
}

async function deleteItem(item) {
  if (!confirm(`Hapus ${item.name}?`)) return
  try {
    await inventoryApi.deleteItem(item.id)
    toast.success('Berhasil', 'Barang dihapus')
    await refreshSections(['inventory'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

async function openScheduleModal(s = null) {
  await ensureScheduleLookups()
  editingSchedule.value = s
  scheduleForm.day_of_week = s?.day_of_week ?? 1
  scheduleForm.period = s?.period ?? 1
  scheduleForm.class_id = s?.class_id || s?.school_class?.id || ''
  scheduleForm.subject_id = s?.subject_id || s?.subject?.id || ''
  scheduleForm.employee_id = s?.employee_id || s?.employee?.id || ''
  showScheduleModal.value = true
}

async function saveSchedule() {
  saving.value = true
  try {
    const payload = {
      semester_id: Number(activeSemesterId.value),
      class_id: Number(scheduleForm.class_id),
      day_of_week: Number(scheduleForm.day_of_week),
      period: Number(scheduleForm.period),
      subject_id: Number(scheduleForm.subject_id),
      employee_id: Number(scheduleForm.employee_id),
      room_id: room.value.id,
    }
    if (editingSchedule.value) await lessonScheduleApi.update(editingSchedule.value.id, payload)
    else await lessonScheduleApi.create(payload)
    toast.success('Berhasil', 'Jadwal disimpan')
    showScheduleModal.value = false
    await refreshSections(['schedule'])
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan jadwal')
  } finally { saving.value = false }
}

async function deleteSchedule(s) {
  if (!confirm('Hapus jadwal ini?')) return
  try {
    await lessonScheduleApi.delete(s.id)
    toast.success('Berhasil', 'Jadwal dihapus')
    await refreshSections(['schedule'])
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

async function openLoanModal() {
  await loadSection('inventory')
  loanForm.item_id = ''
  loanForm.borrower_name = ''
  loanForm.quantity = 1
  loanForm.loan_date = new Date().toISOString().slice(0, 10)
  loanForm.expected_return_date = ''
  loanForm.purpose = ''
  showLoanModal.value = true
}

async function saveLoan() {
  saving.value = true
  try {
    await inventoryApi.createLoan({ ...loanForm, item_id: Number(loanForm.item_id), quantity: Number(loanForm.quantity) })
    toast.success('Berhasil', 'Peminjaman dicatat')
    showLoanModal.value = false
    await refreshSections(['loans', 'inventory'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal mencatat pinjam')
  } finally { saving.value = false }
}

function openReturnModal(loan) {
  returningLoan.value = loan
  returnForm.actual_return_date = new Date().toISOString().slice(0, 10)
  returnForm.return_condition = ''
  returnForm.return_item_status = 'Tersedia'
  returnForm.notes = ''
  showReturnModal.value = true
}

async function saveReturn() {
  saving.value = true
  try {
    await inventoryApi.returnLoan(returningLoan.value.id, { ...returnForm, mark_as: 'Dikembalikan' })
    toast.success('Berhasil', 'Pengembalian dicatat')
    showReturnModal.value = false
    await refreshSections(['loans', 'inventory'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal mengembalikan')
  } finally { saving.value = false }
}

async function markLost() {
  saving.value = true
  try {
    await inventoryApi.returnLoan(returningLoan.value.id, { ...returnForm, mark_as: 'Hilang', return_item_status: 'Hilang' })
    toast.success('Berhasil', 'Barang ditandai hilang')
    showReturnModal.value = false
    await refreshSections(['loans', 'inventory'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menandai hilang')
  } finally { saving.value = false }
}

function openBookingModal() {
  bookingForm.purpose = ''
  bookingForm.date = ''
  bookingForm.start_time = '08:00'
  bookingForm.end_time = '10:00'
  bookingForm.notes = ''
  showBookingModal.value = true
}

async function saveBooking() {
  saving.value = true
  try {
    await facilityApi.createLabBooking({ ...bookingForm, room_id: room.value.id })
    toast.success('Berhasil', 'Booking dikirim')
    showBookingModal.value = false
    await refreshSections(['booking'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal mengajukan booking')
  } finally { saving.value = false }
}

async function approveBooking(b) {
  try {
    await facilityApi.approveLabBooking(b.id)
    toast.success('Berhasil', 'Booking disetujui')
    await refreshSections(['booking'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyetujui')
  }
}

async function rejectBooking(b) {
  const reason = prompt('Alasan penolakan (opsional):') ?? ''
  try {
    await facilityApi.rejectLabBooking(b.id, { rejection_reason: reason })
    toast.success('Berhasil', 'Booking ditolak')
    await refreshSections(['booking'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menolak')
  }
}

async function cancelBooking(b) {
  if (!confirm('Batalkan booking ini?')) return
  try {
    await facilityApi.cancelLabBooking(b.id)
    toast.success('Berhasil', 'Booking dibatalkan')
    await refreshSections(['booking'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal membatalkan')
  }
}

async function openMaintModal() {
  await loadSection('inventory')
  maintForm.item_id = ''
  maintForm.maintenance_type = 'Perbaikan'
  maintForm.scheduled_date = new Date().toISOString().slice(0, 10)
  maintForm.description = ''
  showMaintModal.value = true
}

async function openMaintFromItem(item) {
  await openMaintModal()
  maintForm.item_id = item.id
  maintForm.description = `Kerusakan pada ${item.name}`
}

async function saveMaint() {
  saving.value = true
  try {
    await inventoryApi.createMaintenance({
      item_id: Number(maintForm.item_id),
      maintenance_type: maintForm.maintenance_type,
      scheduled_date: maintForm.scheduled_date,
      description: maintForm.description,
      status: 'Terjadwal',
    })
    // Mark item as Rusak if perbaikan
    if (maintForm.maintenance_type === 'Perbaikan') {
      const item = items.value.find(i => i.id === Number(maintForm.item_id))
      if (item) {
        await inventoryApi.updateItem(item.id, {
          name: item.name,
          quantity: item.quantity,
          condition: item.condition === 'Baik' ? 'Rusak Ringan' : item.condition,
          status: 'Rusak',
          room_id: room.value.id,
        })
      }
    }
    toast.success('Berhasil', 'Laporan kerusakan dicatat')
    showMaintModal.value = false
    await refreshSections(['maintenance', 'inventory'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan')
  } finally { saving.value = false }
}

async function completeMaint(m) {
  try {
    await inventoryApi.updateMaintenance(m.id, { status: 'Selesai', completed_date: new Date().toISOString().slice(0, 10) })
    toast.success('Berhasil', 'Perawatan selesai')
    await refreshSections(['maintenance'], { withRoom: true })
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memperbarui')
  }
}

async function openJournalModal() {
  await ensureClasses()
  journalForm.date = new Date().toISOString().slice(0, 10)
  journalForm.start_time = ''
  journalForm.end_time = ''
  journalForm.activity = ''
  journalForm.class_id = ''
  journalForm.participants_count = null
  journalForm.notes = ''
  journalForm.incident_notes = ''
  showJournalModal.value = true
}

async function saveJournal() {
  saving.value = true
  try {
    const payload = {
      room_id: room.value.id,
      date: journalForm.date,
      start_time: journalForm.start_time || null,
      end_time: journalForm.end_time || null,
      activity: journalForm.activity,
      class_id: journalForm.class_id || null,
      participants_count: journalForm.participants_count,
      notes: journalForm.notes || null,
      incident_notes: journalForm.incident_notes || null,
    }
    await facilityApi.createLabJournal(payload)
    toast.success('Berhasil', 'Jurnal dicatat')
    showJournalModal.value = false
    await refreshSections(['journal'])
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan jurnal')
  } finally { saving.value = false }
}

async function deleteJournal(j) {
  if (!confirm('Hapus jurnal ini?')) return
  try {
    await facilityApi.deleteLabJournal(j.id)
    toast.success('Berhasil', 'Jurnal dihapus')
    await refreshSections(['journal'])
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus')
  }
}

function openConditionModal() {
  conditionForm.condition = room.value.condition || 'Baik'
  conditionForm.description = room.value.description || ''
  showConditionModal.value = true
}

async function saveCondition() {
  saving.value = true
  try {
    if (isAdmin.value) {
      await facilityApi.updateRoom(room.value.id, {
        building_id: room.value.building_id || '',
        name: room.value.name,
        code: room.value.code || '',
        type: 'Laboratorium',
        lab_type: room.value.lab_type || null,
        floor: room.value.floor,
        area: room.value.area ?? '',
        capacity: room.value.capacity ?? '',
        condition: conditionForm.condition,
        description: conditionForm.description,
        responsible_employee_id: room.value.responsible_employee_id || null,
      })
    } else {
      await facilityApi.updateRoom(room.value.id, {
        condition: conditionForm.condition,
        description: conditionForm.description,
      })
    }
    toast.success('Berhasil', 'Kondisi lab diperbarui')
    showConditionModal.value = false
    await loadRoom()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal memperbarui')
  } finally { saving.value = false }
}

async function exportPdf() {
  try {
    const res = await facilityApi.exportLab(room.value.id)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Laporan — ${room.value.name || 'Lab'}`
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
        .toolbar {
          display: flex; align-items: center; justify-content: space-between; gap: 12px;
          padding: 10px 14px; background: #0f172a; color: #f8fafc;
          border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
        }
        .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
        .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
        .actions { display: flex; gap: 8px; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>${title}<span class="hint">Preview — cetak dari tombol di bawah atau dari viewer PDF</span></h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
  } catch (e) {
    toast.error('Gagal', 'Gagal membuka preview laporan PDF')
  }
}

watch(() => route.params.id, async () => {
  resetSectionCache()
  await loadRoom()
  if (room.value) await loadSection(section.value)
})

watch(section, (key) => {
  if (room.value) loadSection(key)
})

onMounted(async () => {
  try {
    const instRes = await institutionApi.getMy()
    const inst = instRes?.data?.data ?? instRes?.data
    if (inst?.active_semester_id) activeSemesterId.value = inst.active_semester_id
  } catch {}
  await loadRoom()
  if (room.value) await loadSection(section.value)
})
</script>

<style scoped>
.lab-detail-page { padding: 0 0 2rem; background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 25%, #f1f5f9 100%); min-height: 100%; }
.detail-top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
.btn-back { color: #059669; text-decoration: none; font-weight: 600; }
.detail-actions { display: flex; gap: 0.5rem; }
.lab-header { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.15rem 1.25rem 1.2rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.header-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.lab-header h1 { margin: 0; font-size: clamp(1.2rem, 2.5vw, 1.5rem); color: #0f172a; letter-spacing: -0.02em; }
.status-badge { font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 999px; }
.status-badge.condition-baik { background: #dcfce7; color: #166534; }
.status-badge.condition-rusak-ringan { background: #ffedd5; color: #c2410c; }
.status-badge.condition-rusak-sedang,
.status-badge.condition-rusak-berat { background: #fee2e2; color: #991b1b; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: #ecfdf5; color: #059669;
}
.meta-icon-warn { background: #fff7ed; color: #c2410c; }
.meta-icon-accent { background: #eff6ff; color: #2563eb; }
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.meta-value { font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; word-break: break-word; }
@media (max-width: 768px) { .meta-grid { grid-template-columns: 1fr 1fr; } }
.tab-shell {
  display: grid;
  grid-template-columns: 188px minmax(0, 1fr);
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  min-height: 360px;
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
.sec-btn:focus-visible { outline: 2px solid #059669; outline-offset: 1px; }
.sec-btn.active { background: #fff; color: #065f46; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0; }
.sec-icon {
  display: inline-flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; flex-shrink: 0;
}
.sec-btn.active .sec-icon { background: #d1fae5; color: #047857; }
.sec-label { font-size: 13.5px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.3; flex: 1; }
.sec-badge { background: #f97316; color: #fff; border-radius: 999px; font-size: 0.7rem; padding: 0.1rem 0.4rem; font-weight: 700; }
.tab-main { min-width: 0; padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.panel { background: transparent; border: none; border-radius: 0; padding: 1rem 1.25rem; }
.panel-head { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
.panel-head h2 { margin: 0; font-size: 1.05rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td { border-bottom: 1px solid #e2e8f0; padding: 0.55rem 0.4rem; text-align: left; }
.data-table th { color: #64748b; font-weight: 600; font-size: 0.8rem; }
.row-warn { background: #fff7ed; }
.muted { color: #94a3b8; }
.actions-cell { white-space: nowrap; }
.btn-primary, .btn-secondary, .btn-danger { border: none; border-radius: 8px; padding: 0.5rem 0.9rem; cursor: pointer; font-weight: 600; }
.btn-primary { background: #059669; color: #fff; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-compact { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.btn-action { border: none; background: transparent; cursor: pointer; color: #059669; font-size: 0.8rem; margin-right: 0.25rem; }
.btn-action.btn-delete { color: #dc2626; }
.btn-xs { font-size: 0.78rem; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: flex; align-items: center; justify-content: center; z-index: 80; padding: 1rem; }
.modal-content { background: #fff; border-radius: 14px; width: min(520px, 100%); max-height: 90vh; overflow: auto; }
.modal-narrow { width: min(400px, 100%); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; }
.btn-close { border: none; background: transparent; font-size: 1.4rem; cursor: pointer; }
.modal-body { padding: 1rem 1.25rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.85rem; color: #475569; margin-bottom: 0.25rem; }
.form-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.5rem 0.65rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.condition-baik { color: #059669; font-weight: 600; }
.condition-rusak-ringan { color: #d97706; font-weight: 600; }
.condition-rusak-sedang, .condition-rusak-berat { color: #dc2626; font-weight: 600; }
.st-pending { color: #d97706; font-weight: 600; }
.st-approved { color: #059669; font-weight: 600; }
.st-rejected, .st-cancelled { color: #94a3b8; }
.loading-state, .empty-state { padding: 2rem; text-align: center; color: #64748b; }
.panel { overflow-x: auto; -webkit-overflow-scrolling: touch; }

@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row; overflow-x: auto; border-right: none; border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
  .form-row { grid-template-columns: 1fr; }
  .modal-footer {
    flex-direction: column-reverse;
  }
  .modal-footer .btn-primary,
  .modal-footer .btn-secondary {
    width: 100%;
    justify-content: center;
  }
  .data-table { font-size: 0.8rem; }
  .data-table th, .data-table td { padding: 0.4rem 0.3rem; }
  .actions-cell { white-space: normal; }
}
</style>
