<template>    <div class="wali-page">
      <div v-if="!homeroomClasses.length" class="state-card empty">
        <h3>Anda belum diangkat sebagai wali kelas</h3>
        <p>Menu ini muncul setelah Anda ditetapkan sebagai wali pada kelas aktif di sekolah ini.</p>
        <router-link to="/teacher/dashboard" class="btn-primary link-btn">Kembali ke dashboard</router-link>
      </div>

      <template v-else>
        <div class="toolbar">
          <div class="toolbar-left">
            <select
              v-if="homeroomClasses.length > 1"
              class="filter-select"
              :value="selectedClassId"
              @change="selectClass($event.target.value)"
            >
              <option v-for="c in homeroomClasses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
            </select>
            <input
              v-if="panel === 'siswa'"
              v-model="studentQuery"
              type="text"
              class="search-input"
              placeholder="Cari nama, NIS, atau NISN…"
              @input="onSearchInput"
            >
            <button v-if="panel === 'siswa' && listFilter" type="button" class="filter-chip" @click="clearListFilter">
              Filter: {{ listFilterLabel }} ×
            </button>
            <div class="class-snapshot" :aria-label="classSnapshotLabel">
              <span v-if="homeroomClasses.length === 1 && selectedClass?.name" class="class-name">{{ selectedClass.name }}</span>
              <span class="class-count">
                <strong>{{ classHeadcount.total }}</strong> siswa
              </span>
              <span class="class-gender g-l">{{ classHeadcount.male }} L</span>
              <span class="class-gender g-p">{{ classHeadcount.female }} P</span>
            </div>
          </div>
          <div class="toolbar-actions">
            <template v-if="panel === 'siswa'">
              <button
                v-if="accounts.missing_account > 0"
                type="button"
                class="btn-secondary"
                :disabled="bulkAccountLoading"
                @click="bulkEnsureClassAccounts"
              >
                {{ bulkAccountLoading ? 'Membuat akun…' : `Buat akun (${accounts.missing_account})` }}
              </button>
            </template>
            <template v-else-if="panel === 'absensi'">
              <button type="button" class="period-chip" :class="{ active: attendancePeriod === 'week' }" @click="setAttendancePeriod('week')">7 hari</button>
              <button type="button" class="period-chip" :class="{ active: attendancePeriod === 'month' }" @click="setAttendancePeriod('month')">30 hari</button>
              <router-link v-if="canAccessModule('teaching_journal')" :to="{ path: '/attendance/student', query: { class_id: selectedClassId, tab: 'rekap' } }" class="btn-secondary link-btn-sm">Rekap lengkap</router-link>
            </template>
            <template v-else-if="panel === 'nilai'">
              <router-link v-if="canAccessModule('grade_book')" :to="{ path: '/raport-kelas', query: { class_id: selectedClassId } }" class="btn-secondary link-btn-sm">Rekap nilai</router-link>
              <router-link v-if="canAccessModule('grade_book')" :to="{ path: '/raport', query: { class_id: selectedClassId } }" class="btn-secondary link-btn-sm">Raport siswa</router-link>
            </template>
            <template v-else-if="panel === 'usulan'">
              <router-link v-if="canAccessBk" :to="{ path: '/laporan-bk', query: { class_id: selectedClassId } }" class="btn-secondary link-btn-sm">Laporan BK</router-link>
            </template>
            <template v-else-if="panel === 'jadwal'">
              <button type="button" class="btn-secondary" :disabled="scheduleLoading || exporting" @click="runExport('schedule')">Preview PDF</button>
            </template>
            <template v-else-if="panel === 'keuangan'">
              <button
                type="button"
                class="btn-secondary"
                :disabled="!financeGenerateTypes.length || financeGenerating"
                @click="openFinanceGenerate"
              >+ Tagihan kelas</button>
            </template>
            <div class="export-wrap" ref="exportWrapRef">
              <button type="button" class="btn-secondary" :disabled="!selectedClassId || exporting" @click="exportOpen = !exportOpen">
                {{ exporting ? 'Menyiapkan…' : 'Cetak' }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              </button>
              <div v-if="exportOpen" class="export-menu" role="menu">
                <button type="button" role="menuitem" @click="runExport('roster')">Daftar siswa (PDF)</button>
                <button type="button" role="menuitem" @click="runExport('identitas')">Identitas peserta didik (PDF)</button>
                <button type="button" role="menuitem" @click="runExport('contacts-pdf')">Kontak ortu (PDF)</button>
                <button type="button" role="menuitem" @click="runExport('contacts-csv')">Kontak ortu (CSV)</button>
                <button type="button" role="menuitem" @click="runExport('attendance')">Rekap absen (PDF)</button>
                <button type="button" role="menuitem" @click="runExport('schedule')">Jadwal kelas (PDF)</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="panel === 'siswa' && attentionItems.length" class="attention-bar">
          <span class="attention-label">Perlu ditindaklanjuti</span>
          <button
            v-for="item in attentionItems"
            :key="item.key"
            type="button"
            class="attention-link"
            @click="item.run()"
          >{{ item.label }}</button>
        </div>

        <!-- Panel: Siswa -->
        <section v-if="panel === 'siswa'" class="panel-body">
          <div v-if="studentsLoading" class="state-card soft"><p>Memuat daftar siswa…</p></div>
          <div v-else-if="studentsError" class="state-card empty soft">
            <h3>Gagal memuat siswa</h3>
            <p>{{ studentsError }}</p>
            <button type="button" class="btn-primary" @click="loadStudents()">Coba lagi</button>
          </div>
          <div v-else-if="!filteredStudents.length" class="state-card empty soft">
            <h3>{{ studentQuery.trim() || listFilter ? 'Tidak ada hasil' : 'Belum ada siswa aktif' }}</h3>
            <p v-if="studentQuery.trim()">Tidak ada siswa yang cocok dengan “{{ studentQuery.trim() }}”.</p>
            <p v-else-if="listFilter">Tidak ada siswa pada filter ini. <button type="button" class="chip-link" @click="clearListFilter">Hapus filter</button></p>
          </div>
          <template v-else>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-num">No</th>
                    <th><button type="button" class="th-sort" @click="setSort('name')">Nama <span class="sort-icon" :class="sortClass('name')"></span></button></th>
                    <th><button type="button" class="th-sort" @click="setSort('nis')">NIS <span class="sort-icon" :class="sortClass('nis')"></span></button></th>
                    <th><button type="button" class="th-sort" @click="setSort('nisn')">NISN <span class="sort-icon" :class="sortClass('nisn')"></span></button></th>
                    <th>Kontak</th>
                    <th>Akun</th>
                    <th class="col-gender"><button type="button" class="th-sort" @click="setSort('gender')">L/P <span class="sort-icon" :class="sortClass('gender')"></span></button></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(student, index) in filteredStudents" :key="student.id" class="row-click" @click="openProfile(student)">
                    <td class="col-num">{{ startIndex + index + 1 }}</td>
                    <td>
                      <div class="student-cell">
                        <img v-if="student.photo_url" :src="student.photo_url" class="student-avatar student-avatar-img" :alt="student.name" />
                        <span v-else class="student-avatar" :class="genderTone(student.gender)">{{ initials(student.name) }}</span>
                        <span class="student-name">{{ student.name }}</span>
                      </div>
                    </td>
                    <td><span class="nis-chip">{{ student.nis || '—' }}</span></td>
                    <td><span class="nis-chip">{{ student.nisn || '—' }}</span></td>
                    <td>
                      <div class="contact-mini">
                        <span v-if="student.guardian_phone || student.phone">{{ student.guardian_phone || student.phone }}</span>
                        <span v-else class="muted">—</span>
                      </div>
                    </td>
                    <td>
                      <span class="account-pill" :class="accountTone(student)">{{ accountLabel(student) }}</span>
                    </td>
                    <td class="col-gender"><span class="gender-pill" :class="genderTone(student.gender)">{{ genderLabel(student.gender) }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <PaginationBar
              v-if="!listFilter"
              :page="pagination.current_page"
              :last-page="pagination.last_page"
              :per-page="pagination.per_page"
              :total="pagination.total"
              item-label="siswa"
              @page-change="goToPage"
              @per-page-change="changePerPage"
            />
          </template>
        </section>

        <!-- Panel: Absensi -->
        <section v-else-if="panel === 'absensi'" class="panel-body">
          <div v-if="attendanceLoading" class="state-card soft"><p>Memuat rekap absensi…</p></div>
          <div v-else-if="attendanceError" class="state-card empty soft"><h3>Gagal memuat</h3><p>{{ attendanceError }}</p></div>
          <template v-else-if="attendanceSummary">
            <div class="overview-stats">
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.hadir || 0 }}</strong><span>Hadir</span></div>
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.izin || 0 }}</strong><span>Izin</span></div>
              <div class="ov-stat"><strong>{{ attendanceSummary.totals?.sakit || 0 }}</strong><span>Sakit</span></div>
              <div class="ov-stat" :class="{ warn: (attendanceSummary.totals?.alpha || 0) > 0 }"><strong>{{ attendanceSummary.totals?.alpha || 0 }}</strong><span>Alpa</span></div>
            </div>
            <div v-if="attendanceSummary.repeat_alpha?.length" class="alert-box">
              <h3>Alpa berulang (≥ {{ attendanceSummary.alpha_threshold }} sesi)</h3>
              <ul>
                <li v-for="s in attendanceSummary.repeat_alpha" :key="s.student_id">
                  <button type="button" class="chip-link" @click="openProfileById(s.student_id, s.name)">{{ s.name }}</button>
                  · {{ s.alpha }} alpa
                </li>
              </ul>
            </div>
            <div v-if="!attendanceRows.length" class="state-card empty soft">
              <h3>Belum ada rekap absensi</h3>
              <p>Belum ada data absensi siswa untuk periode ini.</p>
            </div>
            <template v-else>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-num">No</th>
                      <th>Nama</th>
                      <th>Hadir</th>
                      <th>Izin</th>
                      <th>Sakit</th>
                      <th>Alpa</th>
                      <th>Tercatat</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(r, index) in pagedAttendanceRows" :key="r.student_id" class="row-click" @click="openProfileById(r.student_id, r.name)">
                      <td class="col-num">{{ attendanceStartIndex + index + 1 }}</td>
                      <td>{{ r.name }}</td>
                      <td>{{ r.counts?.hadir || 0 }}</td>
                      <td>{{ r.counts?.izin || 0 }}</td>
                      <td>{{ r.counts?.sakit || 0 }}</td>
                      <td :class="{ 'cell-warn': (r.counts?.alpha || 0) >= (attendanceSummary.alpha_threshold || 2) }">{{ r.counts?.alpha || 0 }}</td>
                      <td>{{ r.recorded || 0 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <PaginationBar
                :page="attendancePagination.current_page"
                :last-page="attendancePagination.last_page"
                :per-page="attendancePagination.per_page"
                :total="attendancePagination.total"
                item-label="siswa"
                @page-change="goToAttendancePage"
                @per-page-change="changeAttendancePerPage"
              />
            </template>
          </template>
        </section>

        <!-- Panel: Nilai -->
        <section v-else-if="panel === 'nilai'" class="panel-body">
          <div v-if="gradesLoading" class="state-card soft"><p>Memuat ringkasan nilai…</p></div>
          <div v-else-if="gradesError" class="state-card empty soft"><h3>Gagal memuat</h3><p>{{ gradesError }}</p></div>
          <template v-else-if="gradesOverview">
            <div class="overview-stats">
              <div class="ov-stat"><strong>{{ gradesOverview.summary?.subject_count || 0 }}</strong><span>Mapel</span></div>
              <div class="ov-stat" :class="{ warn: (gradesOverview.summary?.missing_any || 0) > 0 }"><strong>{{ gradesOverview.summary?.missing_any || 0 }}</strong><span>Tanpa nilai</span></div>
              <div class="ov-stat" :class="{ warn: (gradesOverview.summary?.below_kkm_any || 0) > 0 }"><strong>{{ gradesOverview.summary?.below_kkm_any || 0 }}</strong><span>Di bawah KKM</span></div>
            </div>
            <div v-if="!gradesRows.length" class="state-card empty soft">
              <h3>Belum ada ringkasan nilai</h3>
              <p>Belum ada data nilai siswa untuk semester aktif.</p>
            </div>
            <template v-else>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-num">No</th>
                      <th>Nama</th>
                      <th>Rata-rata</th>
                      <th>Kosong</th>
                      <th>Di bawah KKM</th>
                      <th>Detail</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(r, index) in pagedGradesRows" :key="r.student_id" class="row-click" @click="openProfileById(r.student_id, r.name)">
                      <td class="col-num">{{ gradesStartIndex + index + 1 }}</td>
                      <td>{{ r.name }}</td>
                      <td>{{ r.average != null ? r.average : '—' }}</td>
                      <td :class="{ 'cell-warn': r.missing_count > 0 }">{{ r.missing_count }}</td>
                      <td :class="{ 'cell-warn': r.below_kkm_count > 0 }">{{ r.below_kkm_count }}</td>
                      <td class="detail-cell">
                        <span v-for="m in r.missing.slice(0, 3)" :key="'m'+m.subject_id" class="tag tag-miss">{{ m.subject_name }}</span>
                        <span v-for="b in r.below_kkm.slice(0, 3)" :key="'b'+b.subject_id" class="tag tag-kkm">{{ b.subject_name }} {{ b.nilai_akhir }}</span>
                        <span v-if="(r.missing_count + r.below_kkm_count) > 6" class="muted">…</span>
                        <span v-if="!r.missing_count && !r.below_kkm_count" class="muted">Lengkap</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <PaginationBar
                :page="gradesPagination.current_page"
                :last-page="gradesPagination.last_page"
                :per-page="gradesPagination.per_page"
                :total="gradesPagination.total"
                item-label="siswa"
                @page-change="goToGradesPage"
                @per-page-change="changeGradesPerPage"
              />
            </template>
          </template>
        </section>

        <!-- Panel: Usulan -->
        <section v-else-if="panel === 'usulan'" class="panel-card">
          <div class="subtabs">
            <button type="button" class="subtab" :class="{ active: usulanTab === 'violation' }" @click="usulanTab = 'violation'">Pelanggaran</button>
            <button type="button" class="subtab" :class="{ active: usulanTab === 'achievement' }" @click="usulanTab = 'achievement'">Prestasi</button>
            <button type="button" class="subtab" :class="{ active: usulanTab === 'mutation' }" @click="usulanTab = 'mutation'">Mutasi</button>
          </div>

          <div v-if="usulanTab === 'violation'" class="usulan-grid">
            <form class="form-card" @submit.prevent="submitViolation">
              <h3>Ajukan pelanggaran</h3>
              <label>Siswa
                <select v-model="vioForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>Jenis
                <select v-model="vioForm.violation_type_id" required>
                  <option value="">Pilih jenis</option>
                  <option v-for="t in violationTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                </select>
              </label>
              <label>Tanggal <input v-model="vioForm.violation_date" type="date" required></label>
              <label>Keterangan <textarea v-model="vioForm.description" rows="2" placeholder="Opsional"></textarea></label>
              <p v-if="!violationTypes.length" class="form-warn">Jenis pelanggaran belum tersedia. Minta BK/admin menambahkan master jenis.</p>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting || !violationTypes.length">Kirim usulan</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan</h3>
              <div v-if="violationsLoading" class="muted">Memuat…</div>
              <div v-else-if="!violations.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="v in violations" :key="v.id">
                  <div>
                    <strong>{{ v.student?.name }}</strong>
                    <span class="muted"> · {{ v.violation_type?.name }} · {{ v.violation_date }}</span>
                  </div>
                  <span class="status-pill" :class="statusTone(v.status)">{{ statusLabel(v.status) }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div v-else-if="usulanTab === 'achievement'" class="usulan-grid">
            <form class="form-card" @submit.prevent="submitAchievement">
              <h3>Ajukan prestasi</h3>
              <label>Siswa
                <select v-model="achForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>Jenis
                <select v-model="achForm.achievement_type_id" required>
                  <option value="">Pilih jenis</option>
                  <option v-for="t in achievementTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                </select>
              </label>
              <label>Tanggal <input v-model="achForm.achievement_date" type="date" required></label>
              <label>Catatan <textarea v-model="achForm.notes" rows="2" placeholder="Opsional"></textarea></label>
              <p v-if="!achievementTypes.length" class="form-warn">Jenis prestasi belum tersedia.</p>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting || !achievementTypes.length">Kirim usulan</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan</h3>
              <div v-if="achievementsLoading" class="muted">Memuat…</div>
              <div v-else-if="!achievements.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="a in achievements" :key="a.id">
                  <div>
                    <strong>{{ a.student?.name }}</strong>
                    <span class="muted"> · {{ a.achievement_type?.name }} · {{ a.achievement_date }}</span>
                  </div>
                  <span class="status-pill" :class="statusTone(a.status)">{{ statusLabel(a.status) }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div v-else class="usulan-grid">
            <form class="form-card" @submit.prevent="submitMutation">
              <h3>Ajukan mutasi keluar</h3>
              <label>Siswa
                <select v-model="mutForm.student_id" required>
                  <option value="">Pilih siswa</option>
                  <option v-for="s in studentOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                </select>
              </label>
              <label>NPSN tujuan <input v-model="mutForm.target_npsn" required placeholder="NPSN sekolah tujuan"></label>
              <label class="check-row">
                <input v-model="mutForm.external" type="checkbox"> Sekolah belum terdaftar di sistem
              </label>
              <label v-if="mutForm.external">Nama sekolah tujuan <input v-model="mutForm.target_school_name" :required="mutForm.external"></label>
              <label>Alasan / catatan <textarea v-model="mutForm.notes" rows="2"></textarea></label>
              <button type="submit" class="btn-primary" :disabled="usulanSubmitting">Kirim ke admin</button>
            </form>
            <div class="list-card">
              <h3>Riwayat usulan mutasi</h3>
              <div v-if="mutationsLoading" class="muted">Memuat…</div>
              <div v-else-if="!mutations.length" class="muted">Belum ada usulan.</div>
              <ul v-else class="proposal-list">
                <li v-for="m in mutations" :key="m.id">
                  <div>
                    <strong>{{ m.student?.name }}</strong>
                    <span class="muted"> · {{ m.target_institution?.name || m.target_school_name || m.target_npsn }}</span>
                  </div>
                  <span class="status-pill" :class="statusTone(m.status)">{{ statusLabel(m.status) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Panel: Jadwal -->
        <section v-else-if="panel === 'jadwal'" class="panel-card">
          <div v-if="scheduleLoading" class="state-card soft"><p>Memuat jadwal…</p></div>
          <div v-else-if="scheduleError" class="state-card empty soft"><h3>Gagal memuat jadwal</h3><p>{{ scheduleError }}</p></div>
          <div v-else-if="!schedule?.matrix?.length" class="state-card empty soft"><h3>Belum ada jadwal</h3><p>Jadwal kelas belum diisi untuk semester aktif.</p></div>
          <div v-else class="schedule-scroll">
            <p v-if="schedule?.template?.name" class="section-hint">Template: {{ schedule.template.name }}</p>
            <table class="schedule-table">
              <thead>
                <tr>
                  <th>Hari</th>
                  <th v-for="p in scheduleMaxPeriods" :key="p">JP {{ p }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="day in schedule.matrix" :key="day.day_of_week">
                  <th>{{ day.day_name }}</th>
                  <td v-for="p in scheduleMaxPeriods" :key="p" :class="{ holiday: day.is_holiday }">
                    <template v-if="day.is_holiday"><span class="muted">Libur</span></template>
                    <template v-else-if="day.slots?.[p]">
                      <div class="slot-subject">{{ day.slots[p].subject?.name || day.slots[p].subject_name || '—' }}</div>
                      <div class="slot-teacher">{{ day.slots[p].employee?.name || day.slots[p].teacher_name || '' }}</div>
                    </template>
                    <template v-else><span class="muted">—</span></template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Panel: Keuangan -->
        <section v-else-if="panel === 'keuangan'" class="panel-body">
          <div v-if="financeLoading && !financeSummary" class="state-card soft"><p>Memuat tagihan kelas…</p></div>
          <div v-else-if="financeError && !financeItems.length" class="state-card empty soft">
            <h3>Gagal memuat</h3>
            <p>{{ financeError }}</p>
            <button type="button" class="btn-primary" @click="loadFinanceData()">Coba lagi</button>
          </div>
          <template v-else>
            <div v-if="financeSummary" class="overview-stats finance-stats">
              <div class="ov-stat"><strong>{{ formatRp(financeSummary.outstanding_total) }}</strong><span>Tunggakan</span></div>
              <div class="ov-stat"><strong>{{ financeSummary.invoice_count || 0 }}</strong><span>Tagihan aktif</span></div>
              <div class="ov-stat" :class="{ warn: (financeSummary.student_count || 0) > 0 }"><strong>{{ financeSummary.student_count || 0 }}</strong><span>Siswa</span></div>
            </div>

            <div class="finance-filters">
              <select v-model="financeFilters.fee_type_id" class="filter-select" @change="reloadFinance">
                <option value="">Semua jenis</option>
                <option v-for="t in financeFeeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
              <input
                v-model="financeFilters.search"
                type="text"
                class="search-input"
                placeholder="Cari siswa / judul tagihan…"
                @keyup.enter="reloadFinance"
              />
              <button type="button" class="btn-secondary" @click="reloadFinance">Terapkan</button>
            </div>

            <div v-if="financeLoading" class="state-card soft"><p>Memuat daftar…</p></div>
            <div v-else-if="!financeItems.length" class="state-card empty soft">
              <h3>Tidak ada tunggakan</h3>
              <p>Semua tagihan kelas ini sudah lunas atau belum ada tagihan.</p>
            </div>
            <template v-else>
              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Siswa</th>
                      <th>Tagihan</th>
                      <th class="num">Sisa</th>
                      <th>Jatuh tempo</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in financeItems" :key="row.id">
                      <td>
                        <strong>{{ row.student?.name }}</strong>
                        <div class="muted">{{ row.student?.nis || '—' }}</div>
                      </td>
                      <td>
                        <div>{{ row.title }}</div>
                        <div class="muted">{{ row.fee_type?.name }}</div>
                      </td>
                      <td class="num">{{ formatRp(row.remaining) }}</td>
                      <td>{{ row.due_date || '—' }}</td>
                      <td><span class="status-pill" :class="financeStatusTone(row.status)">{{ financeStatusLabel(row.status) }}</span></td>
                      <td>
                        <button type="button" class="btn-secondary btn-sm" @click="openFinancePay(row)">Bayar</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <PaginationBar
                :page="financePagination.current_page"
                :last-page="financePagination.last_page"
                :per-page="financePagination.per_page"
                :total="financePagination.total"
                item-label="tagihan"
                @page-change="goFinancePage"
                @per-page-change="changeFinancePerPage"
              />
            </template>
          </template>
        </section>
      </template>
    </div>

    <!-- Modal bayar tagihan (wali) -->
    <Teleport to="body">
    <div v-if="financePayOpen" class="modal-backdrop" @click.self="financePayOpen = false">
      <div class="modal-sheet modal-sheet-sm" role="dialog" aria-modal="true">
        <div class="modal-head"><h2>Catat pembayaran</h2></div>
        <p v-if="financePaying" class="modal-hint">
          {{ financePaying.student?.name }} — {{ financePaying.title }} (sisa {{ formatRp(financePaying.remaining) }})
        </p>
        <div v-if="financePayError" class="form-error">{{ financePayError }}</div>
        <form class="modal-form" @submit.prevent="saveFinancePay">
          <label class="field-label">Nominal *</label>
          <input v-model.number="financePayForm.amount" type="number" min="1" step="1000" class="form-input" required />
          <label class="field-label">Metode</label>
          <select v-model="financePayForm.method" class="form-select">
            <option v-for="o in financePaymentMethods" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
          <label class="field-label">Referensi</label>
          <input v-model="financePayForm.reference" class="form-input" placeholder="No. kwitansi / transfer" />
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="financePayOpen = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="financePaySaving">{{ financePaySaving ? 'Menyimpan…' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- Modal buat tagihan kelas (wali) -->
    <Teleport to="body">
    <div v-if="financeGenOpen" class="modal-backdrop" @click.self="financeGenOpen = false">
      <div class="modal-sheet modal-sheet-sm" role="dialog" aria-modal="true">
        <div class="modal-head"><h2>Buat tagihan kelas</h2></div>
        <p class="modal-hint">Hanya jenis biaya cakupan <strong>Kelas</strong> (mis. kas kelas). SPP &amp; tagihan sekolah dibuat bendahara.</p>
        <div v-if="financeGenError" class="form-error">{{ financeGenError }}</div>
        <form class="modal-form" @submit.prevent="saveFinanceGenerate">
          <label class="field-label">Jenis biaya *</label>
          <select v-model="financeGenForm.fee_type_id" class="form-select" required>
            <option disabled value="">Pilih</option>
            <option v-for="t in financeGenerateTypes" :key="t.id" :value="t.id">{{ t.name }} ({{ formatRp(t.default_amount) }})</option>
          </select>
          <label class="field-label">Judul *</label>
          <input v-model="financeGenForm.title" class="form-input" required placeholder="Contoh: Kas kelas Maret 2026" />
          <label class="field-label">Nominal *</label>
          <input v-model.number="financeGenForm.amount" type="number" min="1" step="1000" class="form-input" required />
          <label class="field-label">Jatuh tempo</label>
          <input v-model="financeGenForm.due_date" type="date" class="form-input" />
          <label class="field-label">Catatan</label>
          <textarea v-model="financeGenForm.notes" class="form-textarea" rows="2" />
          <div class="modal-actions">
            <button type="button" class="btn-secondary" @click="financeGenOpen = false">Batal</button>
            <button type="submit" class="btn-primary" :disabled="financeGenerating">{{ financeGenerating ? 'Memproses…' : 'Buat untuk semua siswa' }}</button>
          </div>
        </form>
      </div>
    </div>
    </Teleport>

    <!-- Modal profil -->
    <Teleport to="body">
    <div v-if="profileOpen" class="modal-backdrop" @click.self="closeProfile">
      <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="profile-title">
        <div class="modal-head">
          <div class="pf-head-main">
            <label class="pf-avatar-wrap" title="Unggah foto siswa">
              <img v-if="profile?.photo_url" :src="profile.photo_url" class="pf-avatar pf-avatar-img" :alt="profile?.name || 'Foto'" />
              <span v-else class="pf-avatar" :class="genderTone(profile?.gender)">{{ initials(profile?.name) }}</span>
              <input type="file" :accept="PROFILE_PHOTO_ACCEPT" class="sr-only" :disabled="photoUploading" @change="onProfilePhotoSelect" />
              <span class="pf-avatar-hint">{{ photoUploading ? 'Mengunggah…' : 'Foto' }}</span>
            </label>
            <div class="pf-head-text">
              <div class="pf-title-row">
                <h2 id="profile-title">{{ profile?.name || 'Profil Siswa' }}</h2>
                <span class="gender-pill" :class="genderTone(profile?.gender)">{{ genderLabel(profile?.gender) }}</span>
                <span class="account-pill" :class="accountTone(profile)">{{ accountLabel(profile) }}</span>
              </div>
              <p class="pf-class">{{ profile?.class || selectedClass?.name || 'Kelas wali' }}</p>
              <div class="pf-idrow">
                <button
                  v-if="profile?.nis"
                  type="button"
                  class="pf-idchip"
                  title="Salin NIS"
                  @click="copyText(profile.nis)"
                >NIS {{ profile.nis }}</button>
                <button
                  v-if="profile?.nisn"
                  type="button"
                  class="pf-idchip"
                  title="Salin NISN"
                  @click="copyText(profile.nisn)"
                >NISN {{ profile.nisn }}</button>
                <span v-if="!profile?.nis && !profile?.nisn" class="muted">NIS / NISN belum diisi</span>
              </div>
              <div v-if="snapshot && !profileLoading" class="pf-kpis">
                <span class="pf-kpi">Hadir 7h <strong>{{ snapshot.attendance?.week?.hadir || 0 }}</strong></span>
                <span class="pf-kpi" :class="{ 'is-warn': (snapshot.attendance?.week?.alpha || 0) > 0 }">Alpa <strong>{{ snapshot.attendance?.week?.alpha || 0 }}</strong></span>
                <span class="pf-kpi">Rata <strong>{{ snapshot.grades?.average != null ? snapshot.grades.average : '—' }}</strong></span>
                <span class="pf-kpi" :class="{ 'is-warn': Number(snapshot.bk?.total_points || 0) < 0 }">BK <strong>{{ snapshot.bk?.total_points ?? 0 }}</strong></span>
              </div>
            </div>
          </div>
          <button type="button" class="pf-close" aria-label="Tutup" @click="closeProfile">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </button>
        </div>
        <div class="profile-tabs" role="tablist" aria-label="Bagian profil">
          <button
            type="button"
            role="tab"
            class="profile-tab"
            :class="{ active: profileTab === 'identitas' }"
            :aria-selected="profileTab === 'identitas'"
            @click="profileTab = 'identitas'"
          >Identitas</button>
          <button
            type="button"
            role="tab"
            class="profile-tab"
            :class="{ active: profileTab === 'akademik' }"
            :aria-selected="profileTab === 'akademik'"
            @click="profileTab = 'akademik'"
          >Absen &amp; nilai</button>
          <button
            type="button"
            role="tab"
            class="profile-tab"
            :class="{ active: profileTab === 'bk' }"
            :aria-selected="profileTab === 'bk'"
            @click="profileTab = 'bk'"
          >
            BK
            <span v-if="bkTabCount" class="pf-tab-count">{{ bkTabCount }}</span>
          </button>
          <button
            type="button"
            role="tab"
            class="profile-tab"
            :class="{ active: profileTab === 'catatan' }"
            :aria-selected="profileTab === 'catatan'"
            @click="profileTab = 'catatan'"
          >
            Catatan
            <span v-if="notes.length" class="pf-tab-count">{{ notes.length }}</span>
          </button>
        </div>
        <div v-if="profileLoading" class="modal-body">
          <div class="pf-skel" aria-busy="true" aria-label="Memuat profil">
            <div class="pf-skel-card"></div>
            <div class="pf-skel-card"></div>
            <div class="pf-skel-card pf-span-2"></div>
          </div>
        </div>
        <div v-else-if="profile" class="modal-body" :class="{ 'is-editing': editingStudent && profileTab === 'identitas' }">
          <template v-if="profileTab === 'identitas'">
            <div class="pf-identitas-actions">
              <button v-if="!editingStudent" type="button" class="btn-primary" @click="startEditStudent">Edit data</button>
              <button v-else type="button" class="btn-secondary" :disabled="editSaving" @click="cancelEditStudent">Batal</button>
              <button type="button" class="btn-secondary" :disabled="exporting" @click="printStudentIdentitas">Cetak identitas</button>
              <button v-if="profile.photo_url && !editingStudent" type="button" class="btn-ghost" :disabled="photoUploading" @click="removeProfilePhoto">Hapus foto</button>
            </div>
            <form v-if="editingStudent" class="edit-form" @submit.prevent="saveStudentEdit">
              <div class="edit-tabs" role="tablist" aria-label="Bagian edit data">
                <button type="button" class="edit-tab" :class="{ active: editTab === 1 }" @click="editTab = 1">Identitas</button>
                <button type="button" class="edit-tab" :class="{ active: editTab === 2 }" @click="editTab = 2">Tambahan</button>
                <button type="button" class="edit-tab" :class="{ active: editTab === 3 }" @click="editTab = 3">Ayah</button>
                <button type="button" class="edit-tab" :class="{ active: editTab === 4 }" @click="editTab = 4">Ibu</button>
                <button type="button" class="edit-tab" :class="{ active: editTab === 5 }" @click="editTab = 5">Wali</button>
              </div>
              <div class="edit-panels">

              <div v-show="editTab === 1" class="edit-panel">
                <div class="form-row">
                  <label>NIK <span class="req">*</span><input v-model="editForm.nik" required maxlength="16" inputmode="numeric" /></label>
                  <label>NISN<input v-model="editForm.nisn" maxlength="10" inputmode="numeric" /></label>
                </div>
                <div class="form-row">
                  <label>NIS<input v-model="editForm.nis" /></label>
                  <label>Nama lengkap <span class="req">*</span><input v-model="editForm.name" required /></label>
                </div>
                <div class="form-row">
                  <label>Jenis kelamin <span class="req">*</span>
                    <select v-model="editForm.gender" required>
                      <option value="">Pilih</option>
                      <option value="L">Laki-laki</option>
                      <option value="P">Perempuan</option>
                    </select>
                  </label>
                  <label>Tempat lahir <span class="req">*</span><input v-model="editForm.birth_place" required /></label>
                </div>
                <div class="form-row">
                  <label>Tanggal lahir <span class="req">*</span><input v-model="editForm.birth_date" type="date" required /></label>
                  <label>Agama
                    <select v-model="editForm.religion">
                      <option value="">Pilih</option>
                      <option v-for="opt in religionOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                </div>
                <p class="form-section-label">Alamat</p>
                <AddressCascade v-model="editForm" street-label="Jalan / RT / RW" />
                <div class="form-row">
                  <label>Telepon<input v-model="editForm.phone" /></label>
                  <label>Email<input v-model="editForm.email" type="email" /></label>
                </div>
              </div>

              <div v-show="editTab === 2" class="edit-panel">
                <div class="form-row">
                  <label>No. KK<input v-model="editForm.no_kk" maxlength="16" /></label>
                  <label>Cita-cita
                    <select v-model="editForm.aspiration">
                      <option value="">Pilih</option>
                      <option v-for="opt in aspirationOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                </div>
                <div class="form-row">
                  <label>Hobi
                    <select v-model="editForm.hobby">
                      <option value="">Pilih</option>
                      <option v-for="opt in hobbyOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                  <label>Disabilitas
                    <select v-model="editForm.disability">
                      <option value="">Pilih</option>
                      <option v-for="opt in disabilityOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                </div>
                <div class="form-row">
                  <label>Tempat tinggal
                    <select v-model="editForm.residence_type">
                      <option value="">Pilih</option>
                      <option value="asrama">Asrama</option>
                      <option value="kost_kontrak">Kost/Kontrak</option>
                      <option value="tinggal_dengan_orang_tua">Tinggal dengan Orang Tua</option>
                      <option value="lainnya">Lainnya</option>
                    </select>
                  </label>
                  <label>Tinggi (cm)<input v-model.number="editForm.height" type="number" min="0" max="300" /></label>
                </div>
                <div class="form-row">
                  <label>Berat (kg)<input v-model.number="editForm.weight" type="number" min="0" max="500" /></label>
                  <label>Sekolah asal<input v-model="editForm.previous_school" /></label>
                </div>
                <div class="form-row">
                  <label>NPSN sekolah asal<input v-model="editForm.previous_school_npsn" /></label>
                  <label>Alamat sekolah asal<input v-model="editForm.previous_school_address" /></label>
                </div>
              </div>

              <div v-show="editTab === 3" class="edit-panel">
                <div class="form-row">
                  <label>Status
                    <select v-model="editForm.father_status">
                      <option value="">Pilih</option>
                      <option v-for="opt in parentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </label>
                  <label>NIK<input v-model="editForm.father_nik" maxlength="16" /></label>
                </div>
                <div class="form-row">
                  <label>Nama<input v-model="editForm.father_name" /></label>
                  <label>Tempat lahir<input v-model="editForm.father_birth_place" /></label>
                </div>
                <div class="form-row">
                  <label>Tanggal lahir<input v-model="editForm.father_birth_date" type="date" /></label>
                  <label>Pendidikan
                    <select v-model="editForm.father_education">
                      <option value="">Pilih</option>
                      <option v-for="opt in educationOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                </div>
                <div class="form-row">
                  <label>Pekerjaan
                    <select v-model="editForm.father_occupation">
                      <option value="">Pilih</option>
                      <option v-for="opt in occupationOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                  <label>Penghasilan / bulan (Rp)<input v-model.number="editForm.father_income" type="number" min="0" /></label>
                </div>
              </div>

              <div v-show="editTab === 4" class="edit-panel">
                <div class="form-row">
                  <label>Status
                    <select v-model="editForm.mother_status">
                      <option value="">Pilih</option>
                      <option v-for="opt in parentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </label>
                  <label>NIK<input v-model="editForm.mother_nik" maxlength="16" /></label>
                </div>
                <div class="form-row">
                  <label>Nama<input v-model="editForm.mother_name" /></label>
                  <label>Tempat lahir<input v-model="editForm.mother_birth_place" /></label>
                </div>
                <div class="form-row">
                  <label>Tanggal lahir<input v-model="editForm.mother_birth_date" type="date" /></label>
                  <label>Pendidikan
                    <select v-model="editForm.mother_education">
                      <option value="">Pilih</option>
                      <option v-for="opt in educationOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                </div>
                <div class="form-row">
                  <label>Pekerjaan
                    <select v-model="editForm.mother_occupation">
                      <option value="">Pilih</option>
                      <option v-for="opt in occupationOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </label>
                  <label>Penghasilan / bulan (Rp)<input v-model.number="editForm.mother_income" type="number" min="0" /></label>
                </div>
              </div>

              <div v-show="editTab === 5" class="edit-panel">
                <div class="form-row">
                  <label>Jenis wali
                    <select v-model="editForm.guardian_type" @change="handleGuardianTypeChange">
                      <option value="">Pilih</option>
                      <option value="sama_dengan_ayah">Sama dengan ayah kandung</option>
                      <option value="sama_dengan_ibu">Sama dengan ibu kandung</option>
                      <option value="lainnya">Lainnya</option>
                    </select>
                  </label>
                  <label>Telepon wali<input v-model="editForm.guardian_phone" /></label>
                </div>
                <template v-if="editForm.guardian_type === 'lainnya'">
                  <div class="form-row">
                    <label>Status
                      <select v-model="editForm.guardian_status">
                        <option value="">Pilih</option>
                        <option v-for="opt in parentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                    </label>
                    <label>NIK<input v-model="editForm.guardian_nik" maxlength="16" /></label>
                  </div>
                  <div class="form-row">
                    <label>Nama<input v-model="editForm.guardian_name" /></label>
                    <label>Tempat lahir<input v-model="editForm.guardian_birth_place" /></label>
                  </div>
                  <div class="form-row">
                    <label>Tanggal lahir<input v-model="editForm.guardian_birth_date" type="date" /></label>
                    <label>Pendidikan
                      <select v-model="editForm.guardian_education">
                        <option value="">Pilih</option>
                        <option v-for="opt in educationOptions" :key="opt" :value="opt">{{ opt }}</option>
                      </select>
                    </label>
                  </div>
                  <div class="form-row">
                    <label>Pekerjaan
                      <select v-model="editForm.guardian_occupation">
                        <option value="">Pilih</option>
                        <option v-for="opt in occupationOptions" :key="opt" :value="opt">{{ opt }}</option>
                      </select>
                    </label>
                    <label>Penghasilan / bulan (Rp)<input v-model.number="editForm.guardian_income" type="number" min="0" /></label>
                  </div>
                </template>
              </div>
              </div>

              <div class="edit-actions">
                <button type="submit" class="btn-primary" :disabled="editSaving">{{ editSaving ? 'Menyimpan…' : 'Simpan data' }}</button>
              </div>
            </form>
            <div v-else class="pf-dossier">
              <section class="pf-card">
                <h4>Data diri</h4>
                <div class="pf-fields">
                  <div class="pf-field">
                    <span class="pf-k">NIK</span>
                    <button v-if="profile.nik" type="button" class="pf-v pf-copy" title="Salin NIK" @click="copyText(profile.nik)">{{ profile.nik }}</button>
                    <span v-else class="pf-v muted">—</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">NISN</span>
                    <span class="pf-v">{{ profile.nisn || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">NIS</span>
                    <span class="pf-v">{{ profile.nis || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Agama</span>
                    <span class="pf-v">{{ profile.religion || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Tempat, tanggal lahir</span>
                    <span class="pf-v">{{ formatBirth(profile) }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Telepon</span>
                    <span class="pf-v">{{ profile.phone || '—' }}</span>
                  </div>
                  <div class="pf-field pf-span-2">
                    <span class="pf-k">Email</span>
                    <a v-if="profile.email" class="pf-v pf-inline-link" :href="`mailto:${profile.email}`">{{ profile.email }}</a>
                    <span v-else class="pf-v muted">—</span>
                  </div>
                  <div class="pf-field pf-span-2">
                    <span class="pf-k">Alamat</span>
                    <span class="pf-v">{{ formatFullAddress(profile) || profile.address || '—' }}</span>
                  </div>
                </div>
              </section>

              <section class="pf-card">
                <h4>Kontak cepat</h4>
                <div class="pf-contacts">
                  <div class="pf-contact-card">
                    <span class="pf-k">HP siswa</span>
                    <strong>{{ profile.phone || 'Belum diisi' }}</strong>
                    <div v-if="profile.phone" class="pf-contact-actions">
                      <a class="chip-link" :href="telHref(profile.phone)">Telepon</a>
                      <a class="chip-link chip-wa" :href="waHref(profile.phone)" target="_blank" rel="noopener">WhatsApp</a>
                      <button type="button" class="chip-link" @click="copyText(profile.phone)">Salin</button>
                    </div>
                  </div>
                  <div class="pf-contact-card">
                    <span class="pf-k">HP wali · {{ profile.guardian_name || '—' }}</span>
                    <strong>{{ profile.guardian_phone || 'Belum diisi' }}</strong>
                    <div v-if="profile.guardian_phone" class="pf-contact-actions">
                      <a class="chip-link" :href="telHref(profile.guardian_phone)">Telepon</a>
                      <a class="chip-link chip-wa" :href="waHref(profile.guardian_phone)" target="_blank" rel="noopener">WhatsApp</a>
                      <button type="button" class="chip-link" @click="copyText(profile.guardian_phone)">Salin</button>
                    </div>
                  </div>
                </div>
              </section>

              <section class="pf-card">
                <h4>Keluarga</h4>
                <div class="pf-parents">
                  <article class="pf-person">
                    <span class="pf-k">Ayah</span>
                    <strong>{{ profile.father_name || '—' }}</strong>
                    <p v-if="profile.father_status" class="metric-hint">{{ parentStatusLabel(profile.father_status) }}</p>
                    <p v-if="profile.father_occupation || profile.father_education" class="metric-hint">
                      {{ [profile.father_occupation, profile.father_education].filter(Boolean).join(' · ') }}
                    </p>
                  </article>
                  <article class="pf-person">
                    <span class="pf-k">Ibu</span>
                    <strong>{{ profile.mother_name || '—' }}</strong>
                    <p v-if="profile.mother_status" class="metric-hint">{{ parentStatusLabel(profile.mother_status) }}</p>
                    <p v-if="profile.mother_occupation || profile.mother_education" class="metric-hint">
                      {{ [profile.mother_occupation, profile.mother_education].filter(Boolean).join(' · ') }}
                    </p>
                  </article>
                  <article class="pf-person">
                    <span class="pf-k">Wali</span>
                    <strong>{{ profile.guardian_name || '—' }}</strong>
                    <p v-if="profile.guardian_type" class="metric-hint">{{ guardianTypeLabel(profile.guardian_type) }}</p>
                    <p v-if="profile.guardian_occupation || profile.guardian_education" class="metric-hint">
                      {{ [profile.guardian_occupation, profile.guardian_education].filter(Boolean).join(' · ') }}
                    </p>
                  </article>
                </div>
              </section>

              <section class="pf-card">
                <h4>Data tambahan</h4>
                <div class="pf-fields">
                  <div class="pf-field">
                    <span class="pf-k">No. KK</span>
                    <span class="pf-v">{{ profile.no_kk || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Tempat tinggal</span>
                    <span class="pf-v">{{ residenceLabel(profile.residence_type) }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Cita-cita</span>
                    <span class="pf-v">{{ profile.aspiration || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Hobi</span>
                    <span class="pf-v">{{ profile.hobby || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Disabilitas</span>
                    <span class="pf-v">{{ profile.disability || '—' }}</span>
                  </div>
                  <div class="pf-field">
                    <span class="pf-k">Tinggi / berat</span>
                    <span class="pf-v">{{ formatBodyMeasure(profile) }}</span>
                  </div>
                  <div class="pf-field pf-span-2">
                    <span class="pf-k">Sekolah asal</span>
                    <span class="pf-v">{{ formatPreviousSchool(profile) }}</span>
                  </div>
                </div>
              </section>

              <section class="pf-card account-block">
                <h4>Akun portal siswa</h4>
                <p class="pf-account-status" :class="accountTone(profile)">
                  <strong>{{ profile.has_user_account ? 'Sudah punya akun' : 'Belum punya akun' }}</strong>
                  <template v-if="profile.user_account?.must_change_password"> · wajib ganti sandi</template>
                </p>
                <p class="metric-hint">Login pakai NIK. Sandi awal = tanggal lahir (DDMMYYYY).</p>
                <form class="login-fields-form" @submit.prevent="saveLoginFields">
                  <label>
                    <span>NIK (16 digit)</span>
                    <input v-model="loginForm.nik" type="text" maxlength="16" inputmode="numeric" required pattern="\d{16}" autocomplete="off" />
                  </label>
                  <div class="login-row">
                    <label>
                      <span>Tanggal lahir</span>
                      <input v-model="loginForm.birth_date" type="date" required />
                    </label>
                    <label>
                      <span>Tempat lahir</span>
                      <input v-model="loginForm.birth_place" type="text" maxlength="100" />
                    </label>
                  </div>
                  <div class="login-actions">
                    <button type="submit" class="btn-primary" :disabled="accountActionLoading">
                      {{ accountActionLoading ? 'Menyimpan…' : 'Simpan & sinkron akun' }}
                    </button>
                    <button
                      v-if="!profile.has_user_account"
                      type="button"
                      class="btn-secondary"
                      :disabled="accountActionLoading"
                      @click="ensureProfileAccount"
                    >
                      Buat akun
                    </button>
                    <button
                      v-else
                      type="button"
                      class="btn-secondary"
                      :disabled="accountActionLoading"
                      @click="resetProfilePassword"
                    >
                      Reset sandi
                    </button>
                  </div>
                </form>
              </section>
            </div>
          </template>

          <template v-else-if="profileTab === 'akademik'">
            <template v-if="snapshot">
              <div class="akademik-grid">
              <div class="info-block">
                <div class="pf-section-head">
                  <h4>Absensi 7 hari</h4>
                  <router-link
                    v-if="canAccessModule('teaching_journal')"
                    :to="{ path: '/attendance/student', query: { class_id: selectedClassId, tab: 'rekap' } }"
                    class="chip-link"
                  >Rekap lengkap</router-link>
                </div>
                <div class="pf-att-grid">
                  <div class="pf-att hadir"><strong>{{ snapshot.attendance?.week?.hadir || 0 }}</strong><span>Hadir</span></div>
                  <div class="pf-att izin"><strong>{{ snapshot.attendance?.week?.izin || 0 }}</strong><span>Izin</span></div>
                  <div class="pf-att sakit"><strong>{{ snapshot.attendance?.week?.sakit || 0 }}</strong><span>Sakit</span></div>
                  <div class="pf-att alpha"><strong>{{ snapshot.attendance?.week?.alpha || 0 }}</strong><span>Alpa</span></div>
                </div>
                <p class="metric-hint">30 hari: {{ snapshot.attendance?.month?.alpha || 0 }} alpa · {{ snapshot.attendance?.month?.recorded || 0 }} sesi tercatat</p>
              </div>
              <div class="info-block">
                <div class="pf-section-head">
                  <h4>Nilai semester</h4>
                  <router-link
                    v-if="canAccessModule('grade_book')"
                    :to="{ path: '/raport', query: { class_id: selectedClassId } }"
                    class="chip-link"
                  >Raport siswa</router-link>
                </div>
                <p class="snap-score">Rata-rata {{ snapshot.grades?.average != null ? snapshot.grades.average : '—' }}</p>
                <p class="metric-hint">{{ snapshot.grades?.missing_count || 0 }} kosong · {{ snapshot.grades?.below_kkm_count || 0 }} di bawah KKM</p>
                <div v-if="snapshot.grades?.subjects?.length" class="tag-wrap">
                  <span v-for="s in snapshot.grades.subjects.slice(0, 8)" :key="s.subject_id + s.status" class="tag" :class="s.status === 'missing' ? 'tag-miss' : 'tag-kkm'">
                    {{ s.subject_name }}<template v-if="s.nilai_akhir != null"> {{ s.nilai_akhir }}</template>
                  </span>
                </div>
                <p v-else class="metric-hint" style="margin-top:.45rem">Semua mapel tercatat dan tuntas.</p>
              </div>
              </div>
            </template>
            <div v-else class="pf-empty">
              <p>Belum ada ringkasan akademik untuk siswa ini.</p>
            </div>
          </template>

          <template v-else-if="profileTab === 'bk'">
            <template v-if="snapshot">
              <div class="pf-bk-hero">
                <div>
                  <span class="pf-contact-label">Skor poin BK</span>
                  <p class="snap-score">{{ snapshot.bk?.total_points ?? 0 }}</p>
                </div>
                <div class="pf-bk-split">
                  <span>Langgar <strong>{{ snapshot.bk?.violation_points ?? 0 }}</strong></span>
                  <span>Prestasi <strong>{{ snapshot.bk?.achievement_points ?? 0 }}</strong></span>
                </div>
                <router-link
                  v-if="canAccessBk"
                  :to="{ path: '/laporan-bk', query: { class_id: selectedClassId } }"
                  class="chip-link"
                >Laporan BK</router-link>
              </div>
              <div v-if="snapshot.recent_violations?.length || snapshot.recent_achievements?.length || snapshot.mutations?.length" class="snapshot-lists">
                <div v-if="snapshot.recent_violations?.length" class="info-block">
                  <h4>Pelanggaran terkini</h4>
                  <ul class="mini-list">
                    <li v-for="v in snapshot.recent_violations" :key="'v'+v.id">
                      <div class="pf-list-main">
                        <strong>{{ v.type || '—' }}</strong>
                        <span class="muted">{{ formatDate(v.date) }}<template v-if="v.points"> · {{ v.points }} poin</template></span>
                      </div>
                      <span class="status-pill" :class="statusTone(v.status)">{{ statusLabel(v.status) }}</span>
                    </li>
                  </ul>
                </div>
                <div v-if="snapshot.recent_achievements?.length" class="info-block">
                  <h4>Prestasi terkini</h4>
                  <ul class="mini-list">
                    <li v-for="a in snapshot.recent_achievements" :key="'a'+a.id">
                      <div class="pf-list-main">
                        <strong>{{ a.type || '—' }}</strong>
                        <span class="muted">{{ formatDate(a.date) }}<template v-if="a.points"> · {{ a.points }} poin</template></span>
                      </div>
                      <span class="status-pill" :class="statusTone(a.status)">{{ statusLabel(a.status) }}</span>
                    </li>
                  </ul>
                </div>
                <div v-if="snapshot.mutations?.length" class="info-block">
                  <h4>Riwayat mutasi</h4>
                  <ul class="mini-list">
                    <li v-for="m in snapshot.mutations" :key="'m'+m.id">
                      <div class="pf-list-main">
                        <strong>{{ m.target || '—' }}</strong>
                      </div>
                      <span class="status-pill" :class="statusTone(m.status)">{{ statusLabel(m.status) }}</span>
                    </li>
                  </ul>
                </div>
              </div>
              <div v-else class="pf-empty">
                <p>Belum ada pelanggaran, prestasi, atau mutasi tercatat.</p>
              </div>
            </template>
            <div v-else class="pf-empty">
              <p>Belum ada data BK.</p>
            </div>
          </template>

          <template v-else>
            <div class="notes-block">
              <form class="note-form" @submit.prevent="saveNote">
                <textarea v-model="noteBody" rows="3" placeholder="Tulis catatan perkembangan / tindak lanjut…" required maxlength="5000"></textarea>
                <div class="note-form-bar">
                  <span class="metric-hint">{{ noteBody.length }}/5000</span>
                  <div class="note-form-actions">
                    <button v-if="editingNoteId" type="button" class="btn-ghost" @click="cancelEditNote">Batal</button>
                    <button type="submit" class="btn-primary" :disabled="noteSaving">{{ editingNoteId ? 'Simpan perubahan' : 'Tambah catatan' }}</button>
                  </div>
                </div>
              </form>
              <div v-if="notesLoading" class="muted">Memuat catatan…</div>
              <ul v-else-if="notes.length" class="notes-list">
                <li v-for="n in notes" :key="n.id">
                  <div class="note-meta">
                    <strong>{{ n.author?.name || 'Wali' }}</strong>
                    <span class="muted">{{ formatDateTime(n.created_at) }}</span>
                  </div>
                  <p>{{ n.body }}</p>
                  <div v-if="n.can_edit" class="note-actions">
                    <TableAction kind="edit" @click="startEditNote(n)" />
                    <TableAction kind="delete" @click="removeNote(n)" />
                  </div>
                </li>
              </ul>
              <div v-else class="pf-empty">
                <p>Belum ada catatan. Tulis tindak lanjut agar riwayat wali tetap terhubung.</p>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
    </Teleport>  <AccountCredentialsModal
    :show="!!accountCredentials"
    :title="accountCredentials?.title"
    :name="accountCredentials?.name"
    :login-label="accountCredentials?.loginLabel || 'NIK'"
    :login-value="accountCredentials?.loginValue"
    :password="accountCredentials?.password"
    :hint="accountCredentials?.hint"
    :items="accountCredentials?.items || []"
    @close="accountCredentials = null"
  />
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TableAction from '@/components/TableAction.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import AddressCascade from '@/components/AddressCascade.vue'
import { useAuthStore } from '@/stores/auth'
import { teacherApi } from '@/api/teacher'
import { waliKelasApi } from '@/api/waliKelas'
import { useToast } from '@/composables/useToast'
import { studentLoginCredentials, mapStudentCreatedAccounts } from '@/utils/accountCredentials'
import { emptyAddress, formatFullAddress, pickAddress } from '@/utils/addressFields'
import { PROFILE_PHOTO_ACCEPT, profilePhotoFormData, validateProfilePhoto } from '@/utils/profilePhoto'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const accountCredentials = ref(null)

const selectedClassId = ref('')
const panel = ref('siswa')
const students = ref([])
const studentsLoading = ref(false)
const studentsError = ref('')
const studentQuery = ref('')
const sortBy = ref('name')
const sortDir = ref('asc')
const summary = ref({ total: 0, male: 0, female: 0 })
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const dashboard = ref(null)
const dashLoading = ref(false)
const listFilter = ref(null) // 'bk_high' | 'grades_incomplete' | 'missing_account' | 'incomplete_account' | null
const accountFilterMode = ref('') // '', 'missing', 'incomplete' — server-side via account_status
const bulkAccountLoading = ref(false)
const accountActionLoading = ref(false)
const loginForm = ref({ nik: '', birth_date: '', birth_place: '' })
const editingStudent = ref(false)
const editSaving = ref(false)
const editTab = ref(1)
const editForm = ref(emptyStudentEditForm())
const photoUploading = ref(false)

const religionOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']
const aspirationOptions = ['Dokter', 'Guru', 'Insinyur', 'Polisi', 'Tentara', 'Pilot', 'Arsitek', 'Pengusaha', 'Lainnya']
const hobbyOptions = ['Membaca', 'Menulis', 'Olahraga', 'Musik', 'Seni', 'Fotografi', 'Berkebun', 'Lainnya']
const disabilityOptions = ['Tidak Ada', 'Tuna Netra', 'Tuna Rungu', 'Tuna Wicara', 'Tuna Daksa', 'Tuna Grahita', 'Tuna Laras', 'Lainnya']
const educationOptions = ['Tidak Sekolah', 'SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']
const occupationOptions = ['Tidak Bekerja', 'PNS', 'TNI/Polri', 'Swasta', 'Wiraswasta', 'Petani', 'Nelayan', 'Buruh', 'Lainnya']
const parentStatusOptions = [
  { value: 'masih_hidup', label: 'Masih Hidup' },
  { value: 'meninggal_dunia', label: 'Meninggal Dunia' },
  { value: 'tidak_diketahui', label: 'Tidak Diketahui' },
]

function emptyStudentEditForm() {
  return {
    nik: '',
    nis: '',
    nisn: '',
    name: '',
    gender: '',
    birth_date: '',
    birth_place: '',
    ...emptyAddress(),
    phone: '',
    email: '',
    religion: '',
    no_kk: '',
    aspiration: '',
    hobby: '',
    disability: '',
    height: null,
    weight: null,
    previous_school: '',
    previous_school_npsn: '',
    previous_school_address: '',
    residence_type: '',
    father_name: '',
    father_status: '',
    father_nik: '',
    father_birth_place: '',
    father_birth_date: '',
    father_education: '',
    father_occupation: '',
    father_income: null,
    mother_name: '',
    mother_status: '',
    mother_nik: '',
    mother_birth_place: '',
    mother_birth_date: '',
    mother_education: '',
    mother_occupation: '',
    mother_income: null,
    guardian_name: '',
    guardian_phone: '',
    guardian_type: '',
    guardian_status: '',
    guardian_nik: '',
    guardian_birth_place: '',
    guardian_birth_date: '',
    guardian_education: '',
    guardian_occupation: '',
    guardian_income: null,
    notes: '',
  }
}

function emptyToNull(value) {
  if (value === '' || value === undefined) return null
  if (typeof value === 'number' && Number.isNaN(value)) return null
  return value
}

const profileOpen = ref(false)
const profileTab = ref('identitas')
const profileLoading = ref(false)
const profile = ref(null)
const snapshot = ref(null)
const notes = ref([])
const notesLoading = ref(false)
const noteBody = ref('')
const noteSaving = ref(false)
const editingNoteId = ref(null)

const attendancePeriod = ref('week')
const attendanceSummary = ref(null)
const attendanceLoading = ref(false)
const attendanceError = ref('')
const attendancePage = ref(1)
const attendancePerPage = ref(15)

const gradesOverview = ref(null)
const gradesLoading = ref(false)
const gradesError = ref('')
const gradesPage = ref(1)
const gradesPerPage = ref(15)

const usulanTab = ref('violation')
const usulanSubmitting = ref(false)
const violationTypes = ref([])
const achievementTypes = ref([])
const violations = ref([])
const achievements = ref([])
const mutations = ref([])
const violationsLoading = ref(false)
const achievementsLoading = ref(false)
const mutationsLoading = ref(false)
const studentOptions = ref([])

const vioForm = ref({ student_id: '', violation_type_id: '', violation_date: new Date().toISOString().slice(0, 10), description: '' })
const achForm = ref({ student_id: '', achievement_type_id: '', achievement_date: new Date().toISOString().slice(0, 10), notes: '' })
const mutForm = ref({ student_id: '', target_npsn: '', target_school_name: '', external: false, notes: '' })

const schedule = ref(null)
const scheduleLoading = ref(false)
const scheduleError = ref('')

const financeSummary = ref(null)
const financeItems = ref([])
const financeFeeTypes = ref([])
const financeGenerateTypes = ref([])
const financeLoading = ref(false)
const financeError = ref('')
const financeFilters = ref({ fee_type_id: '', search: '' })
const financePagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const financePayOpen = ref(false)
const financePaying = ref(null)
const financePayForm = ref({ amount: null, method: 'cash', reference: '' })
const financePaySaving = ref(false)
const financePayError = ref('')
const financeGenOpen = ref(false)
const financeGenForm = ref({ fee_type_id: '', title: '', amount: null, due_date: '', notes: '' })
const financeGenerating = ref(false)
const financeGenError = ref('')
const financePaymentMethods = [
  { value: 'cash', label: 'Tunai' },
  { value: 'transfer', label: 'Transfer' },
  { value: 'other', label: 'Lainnya' },
]

const exportOpen = ref(false)
const exporting = ref(false)
const exportWrapRef = ref(null)

let searchTimer = null
let syncingQuery = false

const homeroomClasses = computed(() => authStore.user?.homeroom_classes || [])
const selectedClass = computed(() => homeroomClasses.value.find((c) => String(c.id) === String(selectedClassId.value)) || null)
const classHeadcount = computed(() => {
  if (dashLoading.value) return { total: '…', male: '…', female: '…' }
  return {
    total: dashboard.value?.students?.total ?? summary.value.total ?? 0,
    male: dashboard.value?.students?.male ?? summary.value.male ?? 0,
    female: dashboard.value?.students?.female ?? summary.value.female ?? 0,
  }
})
const classSnapshotLabel = computed(() => {
  const name = selectedClass.value?.name
  const { total, male, female } = classHeadcount.value
  const bits = [`${total} siswa`, `${male} laki-laki`, `${female} perempuan`]
  return name ? `${name}, ${bits.join(', ')}` : bits.join(', ')
})
const startIndex = computed(() => Math.max(0, (pagination.value.current_page - 1) * pagination.value.per_page))
const attendanceRows = computed(() => attendanceSummary.value?.rows || [])
const attendancePagination = computed(() => clientPagination(attendanceRows.value.length, attendancePage.value, attendancePerPage.value))
const attendanceStartIndex = computed(() => Math.max(0, (attendancePagination.value.current_page - 1) * attendancePagination.value.per_page))
const pagedAttendanceRows = computed(() => slicePage(attendanceRows.value, attendancePagination.value))
const gradesRows = computed(() => gradesOverview.value?.rows || [])
const gradesPagination = computed(() => clientPagination(gradesRows.value.length, gradesPage.value, gradesPerPage.value))
const gradesStartIndex = computed(() => Math.max(0, (gradesPagination.value.current_page - 1) * gradesPagination.value.per_page))
const pagedGradesRows = computed(() => slicePage(gradesRows.value, gradesPagination.value))
const canAccessModule = (key) => (authStore.user?.permissions || []).includes(key)
const canAccessBk = computed(() => canAccessModule('bk_report') || canAccessModule('violation') || canAccessModule('counseling'))
const att = computed(() => dashboard.value?.attendance_today || { hadir: 0, izin: 0, sakit: 0, alpha: 0, students_recorded: 0 })
const pending = computed(() => dashboard.value?.pending || { violations: 0, achievements: 0, mutations: 0 })
const pendingTotal = computed(() => (pending.value.violations || 0) + (pending.value.achievements || 0) + (pending.value.mutations || 0))
const accounts = computed(() => dashboard.value?.accounts || {
  with_account: 0,
  missing_account: 0,
  incomplete_data: 0,
  missing_students: [],
  incomplete_students: [],
})
const bkTabCount = computed(() => {
  const s = snapshot.value
  if (!s) return 0
  return (s.recent_violations?.length || 0) + (s.recent_achievements?.length || 0) + (s.mutations?.length || 0)
})

const attentionItems = computed(() => {
  const items = []
  if ((att.value.alpha || 0) > 0) {
    items.push({ key: 'alpha', label: `${att.value.alpha} alpa hari ini`, run: () => setPanel('absensi') })
  }
  const bk = dashboard.value?.bk_high_scores?.count ?? 0
  if (bk > 0) items.push({ key: 'bk', label: `${bk} skor BK tinggi`, run: focusBkHigh })
  const incomplete = dashboard.value?.grades_incomplete ?? 0
  if (incomplete > 0) {
    items.push({ key: 'grades', label: `${incomplete} nilai belum lengkap`, run: focusGradesIncomplete })
  }
  if (pendingTotal.value > 0) {
    items.push({ key: 'usulan', label: `${pendingTotal.value} usulan menunggu`, run: () => setPanel('usulan') })
  }
  const missing = accounts.value.missing_account || 0
  const incompleteLogin = accounts.value.incomplete_data || 0
  if (missing > 0 || incompleteLogin > 0) {
    const parts = []
    if (missing) parts.push(`${missing} belum akun`)
    if (incompleteLogin) parts.push(`${incompleteLogin} kurang data`)
    items.push({ key: 'akun', label: parts.join(' · '), run: focusMissingAccounts })
  }
  return items
})
const scheduleMaxPeriods = computed(() => Math.max(1, Number(schedule.value?.template?.max_periods || 0)))
const listFilterLabel = computed(() => {
  if (listFilter.value === 'bk_high') return 'Skor BK tinggi'
  if (listFilter.value === 'grades_incomplete') return 'Nilai belum lengkap'
  if (listFilter.value === 'missing_account') return 'Belum punya akun'
  if (listFilter.value === 'incomplete_account') return 'Data login kurang'
  return ''
})
const filteredStudents = computed(() => {
  if (!listFilter.value) return students.value
  if (listFilter.value === 'bk_high') {
    const ids = new Set(
      (dashboard.value?.bk_high_scores?.students || dashboard.value?.bk_high_scores?.top || [])
        .map((s) => Number(s.student_id))
    )
    return students.value.filter((s) => ids.has(Number(s.id)))
  }
  if (listFilter.value === 'grades_incomplete') {
    const ids = new Set((dashboard.value?.grades_incomplete_students || []).map((s) => Number(s.student_id)))
    return students.value.filter((s) => ids.has(Number(s.id)))
  }
  return students.value
})

function genderLabel(gender) {
  if (gender === 'L' || gender === 'male' || gender === 'laki-laki') return 'L'
  if (gender === 'P' || gender === 'female' || gender === 'perempuan') return 'P'
  return gender || '—'
}
function genderTone(gender) {
  const g = genderLabel(gender)
  if (g === 'L') return 'tone-l'
  if (g === 'P') return 'tone-p'
  return 'tone-n'
}
function initials(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}
function sortClass(column) {
  if (sortBy.value !== column) return 'is-idle'
  return sortDir.value === 'asc' ? 'is-asc' : 'is-desc'
}
function formatDateTime(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return iso
  }
}
function formatDate(ymd) {
  if (!ymd) return '—'
  try {
    const d = new Date(String(ymd).length <= 10 ? `${ymd}T00:00:00` : ymd)
    if (Number.isNaN(d.getTime())) return ymd
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  } catch {
    return ymd
  }
}
function formatBirth(p) {
  if (!p) return '—'
  const place = p.birth_place || ''
  const date = p.birth_date ? formatDate(p.birth_date) : ''
  if (place && date) return `${place}, ${date}`
  return place || date || '—'
}
function parentStatusLabel(value) {
  return parentStatusOptions.find((opt) => opt.value === value)?.label || '—'
}
function residenceLabel(value) {
  const map = {
    asrama: 'Asrama',
    kost_kontrak: 'Kost/Kontrak',
    tinggal_dengan_orang_tua: 'Tinggal dengan orang tua',
    lainnya: 'Lainnya',
  }
  return map[value] || '—'
}
function guardianTypeLabel(value) {
  const map = {
    sama_dengan_ayah: 'Sama dengan ayah kandung',
    sama_dengan_ibu: 'Sama dengan ibu kandung',
    lainnya: 'Lainnya',
  }
  return map[value] || '—'
}
function formatBodyMeasure(p) {
  if (!p) return '—'
  const h = p.height ? `${p.height} cm` : ''
  const w = p.weight ? `${p.weight} kg` : ''
  if (h && w) return `${h} / ${w}`
  return h || w || '—'
}
function formatPreviousSchool(p) {
  if (!p) return '—'
  const parts = [p.previous_school, p.previous_school_npsn, p.previous_school_address].filter(Boolean)
  return parts.length ? parts.join(' · ') : '—'
}
function phoneDigits(raw) {
  let d = String(raw || '').replace(/\D/g, '')
  if (!d) return ''
  if (d.startsWith('0')) d = `62${d.slice(1)}`
  else if (d.startsWith('8') && d.length >= 9) d = `62${d}`
  return d
}
function telHref(raw) {
  const d = String(raw || '').replace(/[^\d+]/g, '')
  return d ? `tel:${d}` : '#'
}
function waHref(raw) {
  const d = phoneDigits(raw)
  return d ? `https://wa.me/${d}` : '#'
}
function statusLabel(status) {
  const map = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    dicatat: 'Dicatat',
    ditolak: 'Ditolak',
    sanksi_diberikan: 'Sanksi',
    follow_up: 'Tindak lanjut',
    selesai: 'Selesai',
    cancel_pending: 'Batal menunggu',
    cancelled: 'Dibatalkan',
  }
  return map[status] || status || '—'
}
function statusTone(status) {
  if (status === 'pending' || status === 'cancel_pending' || status === 'follow_up') return 'st-pending'
  if (status === 'approved' || status === 'dicatat' || status === 'selesai' || status === 'sanksi_diberikan') return 'st-ok'
  if (status === 'rejected' || status === 'ditolak' || status === 'cancelled') return 'st-bad'
  return ''
}

function syncFromRoute() {
  const fromQuery = route.query.class_id ? String(route.query.class_id) : ''
  const allowed = new Set(homeroomClasses.value.map((c) => String(c.id)))
  if (fromQuery && allowed.has(fromQuery)) selectedClassId.value = fromQuery
  else if (selectedClassId.value && allowed.has(String(selectedClassId.value))) { /* keep current class */ }
  else if (homeroomClasses.value.length) selectedClassId.value = String(homeroomClasses.value[0].id)
  else selectedClassId.value = ''

  const p = String(route.query.panel || 'siswa')
  panel.value = ['siswa', 'absensi', 'nilai', 'usulan', 'jadwal', 'keuangan'].includes(p) ? p : 'siswa'
}

function replaceQuery(extra = {}) {
  const query = { ...route.query, ...extra }
  if (!query.class_id && selectedClassId.value) query.class_id = selectedClassId.value
  if (!query.panel) query.panel = panel.value
  const same = String(route.query.class_id || '') === String(query.class_id || '')
    && String(route.query.panel || '') === String(query.panel || '')
  if (same) return
  syncingQuery = true
  router.replace({ path: '/teacher/wali', query })
}

function ensureQuery() {
  if (!selectedClassId.value) return
  replaceQuery({ class_id: selectedClassId.value, panel: panel.value })
}

function selectClass(id) {
  selectedClassId.value = String(id)
  studentQuery.value = ''
  listFilter.value = null
  accountFilterMode.value = ''
  replaceQuery({ class_id: String(id) })
  refreshClassData()
}

function setPanel(next) {
  panel.value = next
  replaceQuery({ panel: next })
  if (next === 'usulan') loadUsulanData()
  if (next === 'jadwal') loadSchedule()
  if (next === 'keuangan') loadFinanceData()
  if (next === 'absensi') loadAttendanceSummary()
  if (next === 'nilai') loadGradesOverview()
}

function focusBkHigh() {
  listFilter.value = 'bk_high'
  accountFilterMode.value = ''
  setPanel('siswa')
  loadStudentsForFilter()
}

function focusGradesIncomplete() {
  listFilter.value = 'grades_incomplete'
  accountFilterMode.value = ''
  setPanel('nilai')
  loadGradesOverview()
}

function focusMissingAccounts() {
  setPanel('siswa')
  if ((accounts.value.missing_account || 0) > 0) {
    listFilter.value = 'missing_account'
    accountFilterMode.value = 'missing'
  } else if ((accounts.value.incomplete_data || 0) > 0) {
    listFilter.value = 'incomplete_account'
    accountFilterMode.value = 'incomplete'
  } else {
    listFilter.value = null
    accountFilterMode.value = ''
    toast.success('Info', 'Semua siswa aktif di kelas ini sudah punya akun login')
  }
  loadStudents(1)
}

function clearListFilter() {
  listFilter.value = null
  accountFilterMode.value = ''
  loadStudents(1)
}

function accountLabel(student) {
  if (student?.has_user_account) return 'Ada akun'
  const nik = String(student?.nik || '').trim()
  const hasNik = /^\d{16}$/.test(nik)
  const hasBirth = !!student?.birth_date
  if (hasNik && hasBirth) return 'Belum akun'
  return 'Kurang data'
}

function accountTone(student) {
  if (student?.has_user_account) return 'ok'
  const nik = String(student?.nik || '').trim()
  if (/^\d{16}$/.test(nik) && student?.birth_date) return 'warn'
  return 'bad'
}

async function bulkEnsureClassAccounts() {
  if (!selectedClassId.value || bulkAccountLoading.value) return
  const count = accounts.value.missing_account || 0
  if (count <= 0) {
    toast.success('Info', 'Tidak ada siswa yang siap dibuatkan akun')
    return
  }
  if (!window.confirm(`Buat akun login untuk ${count} siswa di kelas ini?\nSandi awal = tanggal lahir (DDMMYYYY).`)) return
  bulkAccountLoading.value = true
  try {
    const res = await waliKelasApi.ensureAccountsBulk(selectedClassId.value, { only_missing: true, limit: 500 })
    const items = mapStudentCreatedAccounts(res.data?.data?.created_accounts || [])
    if (items.length) {
      accountCredentials.value = {
        title: 'Akun login siswa dibuat',
        loginLabel: 'NIK',
        hint: 'Siswa login dengan NIK. Sandi awal = tanggal lahir (DDMMYYYY). Kartu ini hanya hilang jika ditutup.',
        items,
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun massal selesai')
    }
    await Promise.all([loadDashboard(), loadStudents(1)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    bulkAccountLoading.value = false
  }
}

async function saveLoginFields() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.updateLoginFields(selectedClassId.value, profile.value.id, {
      nik: loginForm.value.nik,
      birth_date: loginForm.value.birth_date,
      birth_place: loginForm.value.birth_place || null,
    })
    profile.value = res.data?.data || profile.value
    syncLoginFormFromProfile()
    toast.success('Berhasil', res.data?.message || 'Data login disimpan')
    await Promise.all([loadDashboard(), loadStudents(pagination.value.current_page)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

async function ensureProfileAccount() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.ensureStudentAccount(selectedClassId.value, profile.value.id)
    profile.value = res.data?.data || profile.value
    syncLoginFormFromProfile()
    const creds = studentLoginCredentials(profile.value, res.data?.login_hint)
    if (creds) {
      creds.title = res.data?.user_created ? 'Akun login siswa dibuat' : 'Akun login siswa'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Akun dibuat')
    }
    await Promise.all([loadDashboard(), loadStudents(pagination.value.current_page)])
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

async function resetProfilePassword() {
  if (!profile.value?.id || !selectedClassId.value || accountActionLoading.value) return
  if (!window.confirm('Reset sandi ke tanggal lahir (DDMMYYYY)? Siswa wajib ganti sandi saat login berikutnya.')) return
  accountActionLoading.value = true
  try {
    const res = await waliKelasApi.resetStudentPassword(selectedClassId.value, profile.value.id)
    profile.value = res.data?.data || profile.value
    const creds = studentLoginCredentials(profile.value, res.data?.login_hint)
    if (creds) {
      creds.title = 'Sandi siswa berhasil direset'
      accountCredentials.value = creds
    } else {
      toast.success('Berhasil', res.data?.message || 'Sandi direset')
    }
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    accountActionLoading.value = false
  }
}

function syncLoginFormFromProfile() {
  loginForm.value = {
    nik: profile.value?.nik || '',
    birth_date: profile.value?.birth_date || '',
    birth_place: profile.value?.birth_place || '',
  }
}

async function loadStudentsForFilter() {
  if (!selectedClassId.value) return
  try {
    const response = await teacherApi.getHomeroomClassStudents(selectedClassId.value, {
      per_page: 100,
      status: 'Aktif',
      sort_by: 'name',
      sort_dir: 'asc',
    })
    const payload = response.data || {}
    students.value = payload.data || []
    const meta = payload.meta || {}
    pagination.value = {
      current_page: 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? 100,
      total: meta.total ?? students.value.length,
    }
  } catch {
    /* keep existing list */
  }
}

function setSort(column) {
  if (sortBy.value === column) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else {
    sortBy.value = column
    sortDir.value = 'asc'
  }
  loadStudents(1)
}

function clientPagination(total, page, perPage) {
  const per = Math.max(1, Number(perPage) || 15)
  const last = Math.max(1, Math.ceil(total / per) || 1)
  return {
    current_page: Math.min(Math.max(1, Number(page) || 1), last),
    last_page: last,
    per_page: per,
    total,
  }
}

function slicePage(rows, meta) {
  const start = (meta.current_page - 1) * meta.per_page
  return rows.slice(start, start + meta.per_page)
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return
  loadStudents(page)
}

function changePerPage(n) {
  pagination.value.per_page = n
  loadStudents(1)
}

function goToAttendancePage(page) {
  attendancePage.value = page
}

function changeAttendancePerPage(n) {
  attendancePerPage.value = n
  attendancePage.value = 1
}

function goToGradesPage(page) {
  gradesPage.value = page
}

function changeGradesPerPage(n) {
  gradesPerPage.value = n
  gradesPage.value = 1
}

function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadStudents(1), 300)
}

async function loadStudents(page = pagination.value.current_page) {
  if (!selectedClassId.value) {
    students.value = []
    return
  }
  studentsLoading.value = true
  studentsError.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      status: 'Aktif',
      sort_by: sortBy.value,
      sort_dir: sortDir.value,
    }
    const q = studentQuery.value.trim()
    if (q) params.search = q
    if (accountFilterMode.value === 'missing') params.account_status = 'missing'
    if (accountFilterMode.value === 'incomplete') params.account_status = 'incomplete'
    const response = await teacherApi.getHomeroomClassStudents(selectedClassId.value, params)
    const payload = response.data || {}
    students.value = payload.data || []
    const meta = payload.meta || {}
    pagination.value = {
      current_page: meta.current_page ?? page,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? pagination.value.per_page,
      total: meta.total ?? students.value.length,
    }
    const s = payload.summary || {}
    summary.value = { total: s.total ?? pagination.value.total, male: s.male ?? 0, female: s.female ?? 0 }
  } catch (error) {
    students.value = []
    studentsError.value = error.formattedMessage || error.response?.data?.message || error.message || 'Gagal memuat siswa'
  } finally {
    studentsLoading.value = false
  }
}

async function loadDashboard() {
  if (!selectedClassId.value) {
    dashboard.value = null
    return
  }
  dashLoading.value = true
  try {
    const res = await waliKelasApi.getDashboard(selectedClassId.value)
    dashboard.value = res.data?.data || null
  } catch {
    dashboard.value = null
  } finally {
    dashLoading.value = false
  }
}

async function loadStudentOptions() {
  if (!selectedClassId.value) return
  try {
    const res = await teacherApi.getHomeroomClassStudents(selectedClassId.value, { per_page: 100, status: 'Aktif', sort_by: 'name' })
    studentOptions.value = res.data?.data || []
  } catch {
    studentOptions.value = []
  }
}

async function openProfile(student) {
  profileOpen.value = true
  profileTab.value = 'identitas'
  profileLoading.value = true
  profile.value = student
  snapshot.value = null
  notes.value = []
  noteBody.value = ''
  editingNoteId.value = null
  try {
    const [stuRes, notesRes] = await Promise.all([
      waliKelasApi.getStudent(selectedClassId.value, student.id),
      waliKelasApi.getNotes(selectedClassId.value, student.id),
    ])
    const data = stuRes.data?.data || stuRes.data || student
    profile.value = data
    snapshot.value = data.snapshot || null
    notes.value = notesRes.data?.data || []
    syncLoginFormFromProfile()
    editingStudent.value = false
    editTab.value = 1
  } catch (e) {
    toast.error('Gagal memuat profil', e.formattedMessage || e.message)
  } finally {
    profileLoading.value = false
    notesLoading.value = false
  }
}

function openProfileById(studentId, name) {
  openProfile({ id: studentId, name: name || 'Siswa' })
}

function closeProfile() {
  profileOpen.value = false
  profile.value = null
  snapshot.value = null
  profileTab.value = 'identitas'
  editingStudent.value = false
  editSaving.value = false
  editTab.value = 1
}

function fillEditForm(source = {}) {
  const next = emptyStudentEditForm()
  Object.keys(next).forEach((key) => {
    if (source[key] === undefined || source[key] === null) return
    next[key] = source[key]
  })
  Object.assign(next, pickAddress(source))
  ;['birth_date', 'father_birth_date', 'mother_birth_date', 'guardian_birth_date'].forEach((key) => {
    if (next[key]) next[key] = String(next[key]).split('T')[0]
  })
  editForm.value = next
}

function startEditStudent() {
  if (!profile.value) return
  fillEditForm(profile.value)
  editTab.value = 1
  editingStudent.value = true
}

function cancelEditStudent() {
  editingStudent.value = false
  editTab.value = 1
}

function handleGuardianTypeChange() {
  if (editForm.value.guardian_type === 'sama_dengan_ayah') {
    editForm.value.guardian_name = editForm.value.father_name
    editForm.value.guardian_status = editForm.value.father_status
    editForm.value.guardian_nik = editForm.value.father_nik
    editForm.value.guardian_birth_place = editForm.value.father_birth_place
    editForm.value.guardian_birth_date = editForm.value.father_birth_date
    editForm.value.guardian_education = editForm.value.father_education
    editForm.value.guardian_occupation = editForm.value.father_occupation
    editForm.value.guardian_income = editForm.value.father_income
  } else if (editForm.value.guardian_type === 'sama_dengan_ibu') {
    editForm.value.guardian_name = editForm.value.mother_name
    editForm.value.guardian_status = editForm.value.mother_status
    editForm.value.guardian_nik = editForm.value.mother_nik
    editForm.value.guardian_birth_place = editForm.value.mother_birth_place
    editForm.value.guardian_birth_date = editForm.value.mother_birth_date
    editForm.value.guardian_education = editForm.value.mother_education
    editForm.value.guardian_occupation = editForm.value.mother_occupation
    editForm.value.guardian_income = editForm.value.mother_income
  }
}

async function saveStudentEdit() {
  if (!profile.value?.id || !selectedClassId.value || editSaving.value) return
  editSaving.value = true
  try {
    const payload = {}
    Object.keys(emptyStudentEditForm()).forEach((key) => {
      payload[key] = emptyToNull(editForm.value[key])
    })
    const res = await waliKelasApi.updateStudent(selectedClassId.value, profile.value.id, payload)
    profile.value = { ...profile.value, ...(res.data?.data || {}) }
    syncLoginFormFromProfile()
    editingStudent.value = false
    toast.success('Berhasil', res.data?.message || 'Data siswa disimpan')
    await loadStudents(pagination.value.current_page)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    editSaving.value = false
  }
}

async function onProfilePhotoSelect(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file || !profile.value?.id || !selectedClassId.value) return
  const photoError = validateProfilePhoto(file)
  if (photoError) {
    toast.error('Gagal', photoError)
    return
  }
  photoUploading.value = true
  try {
    const res = await waliKelasApi.uploadPhoto(selectedClassId.value, profile.value.id, profilePhotoFormData(file))
    profile.value = { ...profile.value, ...(res.data?.data || {}) }
    toast.success('Berhasil', res.data?.message || 'Foto diunggah')
    await loadStudents(pagination.value.current_page)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    photoUploading.value = false
  }
}

async function removeProfilePhoto() {
  if (!profile.value?.id || !selectedClassId.value || photoUploading.value) return
  if (!confirm('Hapus foto siswa ini?')) return
  photoUploading.value = true
  try {
    const res = await waliKelasApi.deletePhoto(selectedClassId.value, profile.value.id)
    profile.value = { ...profile.value, ...(res.data?.data || {}), photo_url: null }
    toast.success('Berhasil', res.data?.message || 'Foto dihapus')
    await loadStudents(pagination.value.current_page)
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    photoUploading.value = false
  }
}

async function printStudentIdentitas() {
  if (!profile.value?.id || !selectedClassId.value) return
  exporting.value = true
  try {
    const res = await waliKelasApi.exportIdentitas(selectedClassId.value, { student_id: profile.value.id })
    const err = await parseBlobError(res)
    if (err) throw new Error(err)
    const blob = await asPdfBlob(res.data)
    const name = profile.value.name || 'Siswa'
    if (!openPdfPreview(blob, `Identitas ${name}`)) {
      downloadBlob(blob, `Identitas_${name}.pdf`)
    }
  } catch (e) {
    let msg = e.formattedMessage || e.message || 'Gagal mencetak identitas'
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || msg
      } catch {
        // keep msg
      }
    }
    toast.error('Gagal', msg)
  } finally {
    exporting.value = false
  }
}

function onProfileKeydown(e) {
  if (e.key === 'Escape' && profileOpen.value) closeProfile()
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Disalin', text)
  } catch {
    toast.error('Gagal menyalin')
  }
}

async function saveNote() {
  if (!profile.value || !noteBody.value.trim()) return
  noteSaving.value = true
  try {
    if (editingNoteId.value) {
      await waliKelasApi.updateNote(selectedClassId.value, profile.value.id, editingNoteId.value, { body: noteBody.value.trim() })
      toast.success('Catatan diperbarui')
    } else {
      await waliKelasApi.createNote(selectedClassId.value, profile.value.id, { body: noteBody.value.trim() })
      toast.success('Catatan ditambahkan')
    }
    noteBody.value = ''
    editingNoteId.value = null
    const notesRes = await waliKelasApi.getNotes(selectedClassId.value, profile.value.id)
    notes.value = notesRes.data?.data || []
  } catch (e) {
    toast.error('Gagal menyimpan catatan', e.formattedMessage || e.message)
  } finally {
    noteSaving.value = false
  }
}

function startEditNote(n) {
  editingNoteId.value = n.id
  noteBody.value = n.body
}

function cancelEditNote() {
  editingNoteId.value = null
  noteBody.value = ''
}

async function removeNote(n) {
  if (!confirm('Hapus catatan ini?')) return
  try {
    await waliKelasApi.deleteNote(selectedClassId.value, profile.value.id, n.id)
    notes.value = notes.value.filter((x) => x.id !== n.id)
    toast.success('Catatan dihapus')
  } catch (e) {
    toast.error('Gagal menghapus', e.formattedMessage || e.message)
  }
}

async function loadUsulanData() {
  await Promise.all([loadStudentOptions(), loadTypes(), loadViolations(), loadAchievements(), loadMutations()])
}

async function loadTypes() {
  try {
    const [v, a] = await Promise.all([
      waliKelasApi.getViolationTypes({ class_id: selectedClassId.value }),
      waliKelasApi.getAchievementTypes({ class_id: selectedClassId.value }),
    ])
    violationTypes.value = v.data?.data || []
    achievementTypes.value = a.data?.data || []
  } catch {
    violationTypes.value = []
    achievementTypes.value = []
  }
}

async function loadViolations() {
  if (!selectedClassId.value) return
  violationsLoading.value = true
  try {
    const res = await waliKelasApi.getViolations({ class_id: selectedClassId.value, per_page: 50 })
    violations.value = res.data?.data || []
  } catch {
    violations.value = []
  } finally {
    violationsLoading.value = false
  }
}

async function loadAchievements() {
  if (!selectedClassId.value) return
  achievementsLoading.value = true
  try {
    const res = await waliKelasApi.getAchievements({ class_id: selectedClassId.value, per_page: 50 })
    achievements.value = res.data?.data || []
  } catch {
    achievements.value = []
  } finally {
    achievementsLoading.value = false
  }
}

async function loadMutations() {
  if (!selectedClassId.value) return
  mutationsLoading.value = true
  try {
    const res = await waliKelasApi.getMutations({ class_id: selectedClassId.value, per_page: 50 })
    mutations.value = res.data?.data || []
  } catch {
    mutations.value = []
  } finally {
    mutationsLoading.value = false
  }
}

async function submitViolation() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeViolation({
      class_id: Number(selectedClassId.value),
      student_id: Number(vioForm.value.student_id),
      violation_type_id: Number(vioForm.value.violation_type_id),
      violation_date: vioForm.value.violation_date,
      description: vioForm.value.description || null,
    })
    toast.success('Usulan pelanggaran terkirim')
    vioForm.value.description = ''
    await Promise.all([loadViolations(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function submitAchievement() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeAchievement({
      class_id: Number(selectedClassId.value),
      student_id: Number(achForm.value.student_id),
      achievement_type_id: Number(achForm.value.achievement_type_id),
      achievement_date: achForm.value.achievement_date,
      notes: achForm.value.notes || null,
    })
    toast.success('Usulan prestasi terkirim')
    achForm.value.notes = ''
    await Promise.all([loadAchievements(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function submitMutation() {
  usulanSubmitting.value = true
  try {
    await waliKelasApi.proposeMutation({
      class_id: Number(selectedClassId.value),
      student_id: Number(mutForm.value.student_id),
      target_npsn: mutForm.value.target_npsn,
      target_school_name: mutForm.value.target_school_name || null,
      external: !!mutForm.value.external,
      notes: mutForm.value.notes || null,
    })
    toast.success('Usulan mutasi terkirim ke admin')
    mutForm.value = { student_id: '', target_npsn: '', target_school_name: '', external: false, notes: '' }
    await Promise.all([loadMutations(), loadDashboard()])
  } catch (e) {
    toast.error('Gagal mengajukan', e.formattedMessage || e.response?.data?.message || e.message)
  } finally {
    usulanSubmitting.value = false
  }
}

async function loadSchedule() {
  if (!selectedClassId.value) return
  scheduleLoading.value = true
  scheduleError.value = ''
  try {
    const res = await waliKelasApi.getSchedule(selectedClassId.value)
    schedule.value = res.data?.data || null
  } catch (e) {
    schedule.value = null
    scheduleError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    scheduleLoading.value = false
  }
}

function formatRp(value) {
  const n = Number(value || 0)
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

function financeStatusLabel(status) {
  const map = { unpaid: 'Belum bayar', partial: 'Sebagian', paid: 'Lunas', cancelled: 'Dibatalkan' }
  return map[status] || status || '—'
}

function financeStatusTone(status) {
  if (status === 'paid') return 'ok'
  if (status === 'partial') return 'warn'
  if (status === 'cancelled') return 'muted'
  return 'danger'
}

async function loadFinanceMeta() {
  if (!selectedClassId.value) return
  try {
    const [summaryRes, typesRes, genRes] = await Promise.all([
      waliKelasApi.getFinanceSummary(selectedClassId.value),
      waliKelasApi.getFinanceFeeTypes(selectedClassId.value),
      waliKelasApi.getFinanceFeeTypes(selectedClassId.value, { for_generate: 1 }),
    ])
    financeSummary.value = summaryRes.data?.data || null
    financeFeeTypes.value = typesRes.data?.data || []
    financeGenerateTypes.value = genRes.data?.data || []
  } catch (e) {
    financeSummary.value = null
    financeFeeTypes.value = []
    financeGenerateTypes.value = []
    financeError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat ringkasan keuangan.'
  }
}

async function loadFinanceInvoices(page = financePagination.value.current_page) {
  if (!selectedClassId.value) return
  financeLoading.value = true
  financeError.value = ''
  try {
    const res = await waliKelasApi.getFinanceInvoices(selectedClassId.value, {
      status: 'outstanding',
      page,
      per_page: financePagination.value.per_page,
      fee_type_id: financeFilters.value.fee_type_id || undefined,
      search: financeFilters.value.search?.trim() || undefined,
    })
    financeItems.value = res.data?.data || []
    const m = res.data?.meta || {}
    financePagination.value = {
      current_page: m.current_page || 1,
      last_page: m.last_page || 1,
      per_page: m.per_page ?? financePagination.value.per_page,
      total: m.total ?? 0,
    }
  } catch (e) {
    financeItems.value = []
    financeError.value = e.formattedMessage || e.response?.data?.message || 'Gagal memuat tagihan.'
  } finally {
    financeLoading.value = false
  }
}

async function loadFinanceData() {
  financePagination.value.current_page = 1
  await loadFinanceMeta()
  await loadFinanceInvoices(1)
}

function reloadFinance() {
  financePagination.value.current_page = 1
  loadFinanceInvoices(1)
}

function goFinancePage(page) {
  financePagination.value.current_page = page
  loadFinanceInvoices(page)
}

function changeFinancePerPage(n) {
  financePagination.value.per_page = n
  financePagination.value.current_page = 1
  loadFinanceInvoices(1)
}

function openFinancePay(row) {
  financePaying.value = row
  financePayForm.value = {
    amount: Number(row.remaining || 0),
    method: 'cash',
    reference: '',
  }
  financePayError.value = ''
  financePayOpen.value = true
}

async function saveFinancePay() {
  if (!selectedClassId.value || !financePaying.value) return
  financePaySaving.value = true
  financePayError.value = ''
  try {
    const res = await waliKelasApi.storeFinancePayment(selectedClassId.value, {
      invoice_id: financePaying.value.id,
      amount: financePayForm.value.amount,
      method: financePayForm.value.method,
      reference: financePayForm.value.reference || null,
    })
    financePayOpen.value = false
    toast.success('Pembayaran dicatat')
    await loadFinanceData()
    const created = res.data?.data
    if (created?.id && window.confirm('Cetak kwitansi sekarang?')) {
      try {
        await waliKelasApi.openFinanceReceipt(selectedClassId.value, created.id)
      } catch (e) {
        toast.error('Gagal membuka kwitansi', e.formattedMessage || e.message)
      }
    }
  } catch (e) {
    financePayError.value = e.formattedMessage || e.response?.data?.message || 'Gagal menyimpan pembayaran.'
  } finally {
    financePaySaving.value = false
  }
}

function openFinanceGenerate() {
  financeGenError.value = ''
  const first = financeGenerateTypes.value[0]
  financeGenForm.value = {
    fee_type_id: first?.id || '',
    title: first?.name || '',
    amount: Number(first?.default_amount || 0) || null,
    due_date: '',
    notes: '',
  }
  financeGenOpen.value = true
}

async function saveFinanceGenerate() {
  if (!selectedClassId.value) return
  financeGenerating.value = true
  financeGenError.value = ''
  try {
    const res = await waliKelasApi.generateFinanceInvoices(selectedClassId.value, {
      fee_type_id: financeGenForm.value.fee_type_id,
      title: financeGenForm.value.title,
      amount: financeGenForm.value.amount,
      due_date: financeGenForm.value.due_date || null,
      notes: financeGenForm.value.notes || null,
      all_students: true,
    })
    financeGenOpen.value = false
    toast.success('Tagihan dibuat', res.data?.message || 'Tagihan kelas berhasil dibuat.')
    await loadFinanceData()
  } catch (e) {
    financeGenError.value = e.formattedMessage
      || e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})?.[0]?.[0]
      || 'Gagal membuat tagihan.'
  } finally {
    financeGenerating.value = false
  }
}

function setAttendancePeriod(period) {
  attendancePeriod.value = period
  attendancePage.value = 1
  loadAttendanceSummary()
}

async function loadAttendanceSummary() {
  if (!selectedClassId.value) return
  attendanceLoading.value = true
  attendanceError.value = ''
  attendancePage.value = 1
  try {
    const res = await waliKelasApi.getAttendanceSummary(selectedClassId.value, { period: attendancePeriod.value })
    attendanceSummary.value = res.data?.data || null
  } catch (e) {
    attendanceSummary.value = null
    attendanceError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    attendanceLoading.value = false
  }
}

async function loadGradesOverview() {
  if (!selectedClassId.value) return
  gradesLoading.value = true
  gradesError.value = ''
  gradesPage.value = 1
  try {
    const res = await waliKelasApi.getGradesOverview(selectedClassId.value)
    gradesOverview.value = res.data?.data || null
  } catch (e) {
    gradesOverview.value = null
    gradesError.value = e.formattedMessage || e.response?.data?.message || e.message
  } finally {
    gradesLoading.value = false
  }
}

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

function filenameFromDisposition(headers, fallback) {
  const cd = headers?.['content-disposition'] || headers?.['Content-Disposition'] || ''
  const m = /filename="?([^"]+)"?/i.exec(cd)
  return m?.[1] || fallback
}

async function asPdfBlob(data) {
  const blob = data instanceof Blob ? data : new Blob([data], { type: 'application/pdf' })
  const head = await blob.slice(0, 5).text()
  if (!head.startsWith('%PDF')) {
    const text = await blob.text()
    try {
      const json = JSON.parse(text)
      throw new Error(json.message || 'Gagal menyiapkan PDF.')
    } catch (e) {
      if (e instanceof SyntaxError) {
        throw new Error('File PDF tidak valid. Coba unduh ulang.')
      }
      throw e
    }
  }
  return blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' })
}

function openPdfPreview(blob, title) {
  const pdfBlob = blob instanceof Blob
    ? new Blob([blob], { type: 'application/pdf' })
    : new Blob([blob], { type: 'application/pdf' })
  const url = URL.createObjectURL(pdfBlob)
  const win = window.open('', '_blank')
  if (!win) {
    toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
    URL.revokeObjectURL(url)
    return false
  }
  const safeTitle = String(title || 'Preview PDF').replace(/[<>]/g, '')
  win.document.open()
  win.document.write(`<!DOCTYPE html><html><head><title>${safeTitle}</title>
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
      .actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }
      .actions button, .actions a {
        border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
        cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block;
      }
      .btn-print { background: #059669; color: #fff; }
      .btn-download { background: #1d4ed8; color: #fff; }
      .btn-close { background: #334155; color: #e2e8f0; }
      iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
    </style></head><body>
    <div class="toolbar">
      <h1>${safeTitle}<span class="hint">Preview cetak</span></h1>
      <div class="actions">
        <a id="pdfDownload" class="btn-download" download="${safeTitle.replace(/"/g, '')}.pdf">Unduh</a>
        <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
        <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
      </div>
    </div>
    <iframe id="pdfFrame" title="Preview PDF"></iframe>
  </body></html>`)
  win.document.close()
  const frame = win.document.getElementById('pdfFrame')
  const download = win.document.getElementById('pdfDownload')
  if (download) download.href = url
  if (frame) frame.src = url
  setTimeout(() => URL.revokeObjectURL(url), 180_000)
  return true
}

async function parseBlobError(res) {
  const contentType = res?.headers?.['content-type'] || ''
  if (res?.status === 200 && !contentType.includes('application/json')) return null
  const text = typeof res?.data?.text === 'function' ? await res.data.text() : String(res?.data || '')
  try {
    const json = JSON.parse(text)
    return json.message || 'Gagal menyiapkan PDF.'
  } catch {
    return text || 'Gagal menyiapkan PDF.'
  }
}

async function runExport(kind) {
  if (!selectedClassId.value) return
  exportOpen.value = false
  exporting.value = true
  const classLabel = selectedClass.value?.name || 'kelas'
  try {
    let res
    let fallback = 'export.bin'
    let previewTitle = 'Preview PDF'
    const isCsv = kind === 'contacts-csv'
    if (kind === 'roster') {
      res = await waliKelasApi.exportRoster(selectedClassId.value)
      fallback = `Daftar_Siswa_${classLabel}.pdf`
      previewTitle = `Preview Daftar Siswa ${classLabel}`
    } else if (kind === 'identitas') {
      res = await waliKelasApi.exportIdentitas(selectedClassId.value)
      fallback = `Identitas_Peserta_Didik_${classLabel}.pdf`
      previewTitle = `Preview Identitas Peserta Didik ${classLabel}`
    } else if (kind === 'contacts-pdf') {
      res = await waliKelasApi.exportContacts(selectedClassId.value, { format: 'pdf' })
      fallback = `Kontak_Ortu_${classLabel}.pdf`
      previewTitle = `Preview Kontak Orang Tua ${classLabel}`
    } else if (kind === 'contacts-csv') {
      res = await waliKelasApi.exportContacts(selectedClassId.value, { format: 'csv' })
      fallback = `Kontak_Ortu_${classLabel}.csv`
    } else if (kind === 'attendance') {
      res = await waliKelasApi.exportAttendance(selectedClassId.value, { format: 'pdf' })
      fallback = `Rekap_Absensi_${classLabel}.pdf`
      previewTitle = `Preview Rekap Absensi ${classLabel}`
    } else if (kind === 'schedule') {
      res = await waliKelasApi.exportSchedulePdf(selectedClassId.value)
      fallback = `Jadwal_${classLabel}.pdf`
      previewTitle = `Preview Jadwal Kelas ${classLabel}`
    }

    const blobError = await parseBlobError(res)
    if (blobError) throw new Error(blobError)

    const blob = res.data instanceof Blob ? res.data : new Blob([res.data], { type: isCsv ? 'text/csv' : 'application/pdf' })
    if (isCsv) {
      downloadBlob(blob, filenameFromDisposition(res.headers, fallback))
      toast.success('Berhasil', 'Unduhan CSV siap')
      return
    }
    const pdfBlob = await asPdfBlob(blob)
    if (openPdfPreview(pdfBlob, previewTitle)) {
      toast.success('Berhasil', 'Preview PDF dibuka')
    } else {
      downloadBlob(pdfBlob, filenameFromDisposition(res.headers, fallback))
    }
  } catch (e) {
    let msg = e.formattedMessage || e.response?.data?.message || e.message
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const json = JSON.parse(text)
        msg = json.message || msg
      } catch {
        // keep msg
      }
    }
    toast.error('Gagal export', msg)
  } finally {
    exporting.value = false
  }
}

function refreshClassData() {
  loadStudents(1)
  loadDashboard()
  if (panel.value === 'usulan') loadUsulanData()
  if (panel.value === 'jadwal') loadSchedule()
  if (panel.value === 'keuangan') loadFinanceData()
  if (panel.value === 'absensi') loadAttendanceSummary()
  if (panel.value === 'nilai') loadGradesOverview()
}

function onDocClick(e) {
  if (exportOpen.value && exportWrapRef.value && !exportWrapRef.value.contains(e.target)) {
    exportOpen.value = false
  }
}

watch(() => route.query, () => {
  if (syncingQuery) {
    syncingQuery = false
    syncFromRoute()
    ensureQuery()
    return
  }
  const prevPanel = panel.value
  const prevClass = selectedClassId.value
  syncFromRoute()
  ensureQuery()
  if (selectedClassId.value !== prevClass) {
    refreshClassData()
  } else if (panel.value !== prevPanel) {
    if (panel.value === 'usulan') loadUsulanData()
    if (panel.value === 'jadwal') loadSchedule()
    if (panel.value === 'keuangan') loadFinanceData()
    if (panel.value === 'absensi') loadAttendanceSummary()
    if (panel.value === 'nilai') loadGradesOverview()
  }
}, { deep: true })

watch(homeroomClasses, () => {
  syncFromRoute()
  ensureQuery()
  if (selectedClassId.value) refreshClassData()
}, { deep: true })

watch(profileOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
  if (open) document.addEventListener('keydown', onProfileKeydown)
  else document.removeEventListener('keydown', onProfileKeydown)
})

onMounted(() => {
  syncFromRoute()
  ensureQuery()
  if (selectedClassId.value) refreshClassData()
  document.addEventListener('click', onDocClick)
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onProfileKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.wali-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin: 0 0 8px;
  flex-wrap: nowrap;
}
.toolbar-left {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;
  gap: 8px;
  flex: 1 1 auto;
  min-width: 0;
  margin: 0;
  padding: 0;
  background: none;
  border: none;
  box-shadow: none;
}
.filter-select,
.search-input {
  box-sizing: border-box;
  height: 40px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  font-size: 14px;
  color: #0f172a;
}
.filter-select {
  flex: 0 0 auto;
  min-width: 140px;
  padding: 0 12px;
}
.search-input {
  flex: 0 1 280px;
  min-width: 180px;
  width: 280px;
  max-width: 280px;
  padding: 0 14px;
}
.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.class-snapshot {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  height: 40px;
}
.toolbar-left > :not(.class-snapshot) ~ .class-snapshot {
  padding-left: 12px;
  margin-left: 4px;
  border-left: 1px solid #e2e8f0;
}
.class-name {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  height: 26px;
  padding: 0 10px;
  border-radius: 8px;
  background: #f1f5f9;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.2px;
  white-space: nowrap;
}
.class-count {
  flex: 0 0 auto;
  font-size: 13px;
  color: #64748b;
  white-space: nowrap;
}
.class-count strong {
  font-weight: 700;
  color: #0f172a;
}
.class-gender {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  height: 26px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}
.class-gender.g-l { background: #eff6ff; color: #1d4ed8; }
.class-gender.g-p { background: #fdf2f8; color: #be185d; }
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 0 0 auto;
  margin-left: auto;
  flex-wrap: nowrap;
}

.attention-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 14px;
  margin: 0 0 16px;
  padding: 10px 14px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 10px;
  font-size: 13px;
}
.attention-label { color: #92400e; font-weight: 650; }
.attention-link {
  border: none;
  background: none;
  padding: 0;
  color: #b45309;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.attention-link:hover { color: #92400e; }

.export-wrap { position: relative; }
.export-menu {
  position: absolute; right: 0; top: calc(100% + 6px); z-index: 20; min-width: 220px;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 12px 28px rgba(15,23,42,.12); padding: .35rem;
}
.export-menu button {
  display: block; width: 100%; text-align: left; border: none; background: transparent; padding: .55rem .7rem;
  border-radius: 8px; cursor: pointer; color: #0f172a; font-size: .88rem;
}
.export-menu button:hover { background: #ecfdf5; color: #065f46; }

.account-pill {
  display: inline-flex; padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap;
}
.account-pill.ok { background: #ecfdf5; color: #047857; }
.account-pill.warn { background: #fffbeb; color: #b45309; }
.account-pill.bad { background: #fef2f2; color: #b91c1c; }
.login-fields-form { display: flex; flex-direction: column; gap: .55rem; margin-top: .55rem; }
.login-fields-form label { display: flex; flex-direction: column; gap: .25rem; font-size: .78rem; font-weight: 600; color: #475569; }
.login-fields-form input {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: .45rem .6rem; font: inherit; font-weight: 400; background: #fff; color: #0f172a;
}
.login-row { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; }
.login-actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }
.login-fields-form input:focus {
  outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.panel-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.panel-body { min-width: 0; }
.table-container {
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  overflow-x: auto;
}
.panel-body :deep(.pg-bar) {
  margin-top: 12px;
}
.section-hint { margin: 0 0 .75rem; color: #94a3b8; font-size: .82rem; }

.table-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.data-table th, .data-table td { padding: .75rem .9rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
.data-table th { font-weight: 650; background: #f8fafc; color: #475569; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
.table-container .data-table th { background: #f8fafc; }
.row-click { cursor: pointer; }
.row-click:hover { background: #f8fafc; }
.th-sort { display: inline-flex; align-items: center; gap: 6px; border: none; background: transparent; color: inherit; font: inherit; font-weight: 650; cursor: pointer; text-transform: uppercase; letter-spacing: .04em; }
.sort-icon { display: inline-block; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; opacity: .35; border-bottom: 5px solid currentColor; }
.sort-icon.is-asc { opacity: 1; }
.sort-icon.is-desc { opacity: 1; border-bottom: 0; border-top: 5px solid currentColor; }
.col-num { width: 3rem; color: #94a3b8; }
.col-gender { width: 4.5rem; }
.student-cell { display: flex; align-items: center; gap: .7rem; }
.student-avatar { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; }
.student-avatar-img { object-fit: cover; padding: 0; background: #e2e8f0; }
.student-name { font-weight: 600; color: #0f172a; }
.nis-chip { display: inline-flex; padding: .15rem .5rem; border-radius: 6px; background: #f1f5f9; color: #475569; font-size: .82rem; }
.gender-pill { display: inline-flex; min-width: 1.7rem; justify-content: center; padding: .15rem .45rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
.tone-l { background: #eff6ff; color: #1d4ed8; }
.tone-p { background: #fdf2f8; color: #be185d; }
.tone-n { background: #f1f5f9; color: #64748b; }
.contact-mini { font-size: .82rem; color: #475569; }
.muted { color: #94a3b8; }
.btn-ghost, .btn-secondary {
  border: 1px solid #e2e8f0; background: #fff; color: #334155; padding: 0 .85rem; height: 40px; border-radius: 10px; cursor: pointer; font-size: .85rem;
  display: inline-flex; align-items: center; gap: .35rem; box-sizing: border-box;
}
.btn-ghost:hover, .btn-secondary:hover { border-color: #34d399; color: #065f46; background: #ecfdf5; }
.btn-secondary:disabled { opacity: .45; cursor: not-allowed; }
.btn-primary, .link-btn {
  display: inline-flex; align-items: center; justify-content: center; border: none; background: #059669; color: #fff;
  padding: .55rem 1rem; border-radius: 10px; text-decoration: none; cursor: pointer; font-weight: 600;
}
.btn-primary:hover { background: #047857; }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; }
.state-card { text-align: center; padding: 2rem 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; }
.state-card.soft { background: #f8fafc; border-style: dashed; }
.state-card.empty h3 { margin: 0 0 .45rem; }
.state-card.empty p { margin: 0 0 1rem; color: #64748b; }

.subtabs { display: flex; gap: .4rem; margin-bottom: 1rem; flex-wrap: wrap; }
.subtab { border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; padding: .4rem .85rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: .85rem; }
.subtab.active { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
.usulan-grid { display: grid; grid-template-columns: minmax(280px, 1fr) minmax(280px, 1.2fr); gap: 1rem; }
.form-card, .list-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; background: #f8fafc; }
.form-card h3, .list-card h3 { margin: 0 0 .75rem; font-size: .98rem; }
.form-card label { display: flex; flex-direction: column; gap: .3rem; margin-bottom: .7rem; font-size: .82rem; font-weight: 600; color: #475569; }
.form-card input, .form-card select, .form-card textarea {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: .5rem .65rem; font: inherit; font-weight: 400; background: #fff; color: #0f172a;
}
.check-row { flex-direction: row !important; align-items: center; gap: .5rem !important; font-weight: 500 !important; }
.form-warn { color: #b45309; font-size: .8rem; margin: 0 0 .6rem; }
.proposal-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .55rem; }
.proposal-list li { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .75rem; }
.status-pill { font-size: .72rem; font-weight: 700; text-transform: uppercase; padding: .15rem .45rem; border-radius: 999px; background: #f1f5f9; color: #475569; white-space: nowrap; }
.st-pending { background: #fffbeb; color: #b45309; }
.st-ok { background: #ecfdf5; color: #047857; }
.st-bad { background: #fef2f2; color: #b91c1c; }

.schedule-scroll { overflow-x: auto; }
.schedule-table { width: 100%; border-collapse: collapse; min-width: 720px; font-size: .82rem; }
.schedule-table th, .schedule-table td { border: 1px solid #e2e8f0; padding: .55rem .5rem; vertical-align: top; }
.schedule-table thead th { background: #f8fafc; color: #475569; text-align: center; }
.schedule-table tbody th { background: #f8fafc; text-align: left; white-space: nowrap; }
.schedule-table td.holiday { background: #fef2f2; }
.slot-subject { font-weight: 650; color: #0f172a; }
.slot-teacher { color: #64748b; font-size: .75rem; margin-top: .15rem; }

.modal-backdrop {
  position: fixed; inset: 0; z-index: 11000; display: flex; align-items: center; justify-content: center;
  padding: 1rem; background: rgba(15, 23, 42, .52); backdrop-filter: blur(4px);
}
.modal-sheet {
  display: flex; flex-direction: column;
  width: min(920px, calc(100vw - 2rem));
  height: min(88vh, 780px);
  max-height: min(88vh, 780px);
  overflow: hidden; background: #fff; border-radius: 20px;
  box-shadow: 0 28px 64px rgba(15, 23, 42, .28);
  animation: pf-in .2s ease-out;
}
@keyframes pf-in {
  from { opacity: 0; transform: translateY(10px) scale(.985); }
  to { opacity: 1; transform: none; }
}
.modal-head {
  display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;
  padding: 1.05rem 1.25rem .8rem; flex-shrink: 0;
  background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}
.modal-head h2 { margin: 0; font-size: 1.18rem; line-height: 1.25; color: #0f172a; }
.pf-head-main { display: flex; gap: 1rem; align-items: flex-start; min-width: 0; }
.pf-avatar-wrap {
  display: flex; flex-direction: column; align-items: center; gap: .3rem;
  cursor: pointer; flex-shrink: 0;
}
.pf-avatar {
  width: 72px; height: 72px; border-radius: 18px; flex-shrink: 0;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.05rem; font-weight: 750; letter-spacing: .02em;
  box-shadow: 0 0 0 3px #fff, 0 0 0 5px #d1fae5;
}
.pf-avatar-img { object-fit: cover; padding: 0; background: #e2e8f0; }
.pf-avatar-hint { font-size: .65rem; color: #64748b; font-weight: 650; text-transform: uppercase; letter-spacing: .04em; }
.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
  clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}
.pf-identitas-actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-bottom: .85rem; flex-shrink: 0; }
.pf-identitas-actions .btn-primary,
.pf-identitas-actions .btn-secondary,
.pf-identitas-actions .btn-ghost { height: 38px; }
.edit-form {
  display: flex; flex-direction: column; gap: .75rem; flex: 1; min-height: 0;
}
.edit-tabs {
  display: flex; gap: 4px; padding: 4px; background: #f1f5f9; border-radius: 12px;
  overflow-x: auto; flex-shrink: 0;
}
.edit-tab {
  flex: 1 0 auto; border: none; background: transparent; padding: .5rem .7rem; cursor: pointer; color: #64748b;
  font-weight: 650; font-size: .82rem; white-space: nowrap; border-radius: 8px;
}
.edit-tab:hover { color: #334155; }
.edit-tab.active {
  color: #047857; background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
}
.edit-panels { flex: 1; min-height: 0; overflow: auto; padding-right: 2px; }
.edit-panel { display: flex; flex-direction: column; gap: .7rem; }
.edit-form .form-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
.edit-form label { display: flex; flex-direction: column; gap: .3rem; font-size: .78rem; font-weight: 650; color: #475569; }
.edit-form input, .edit-form select, .edit-form textarea {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: .5rem .65rem; font: inherit; font-weight: 400; background: #fff; color: #0f172a;
}
.edit-form input:focus, .edit-form select:focus, .edit-form textarea:focus {
  outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, .12);
}
.form-section-label { margin: .2rem 0 0; font-size: .78rem; font-weight: 700; color: #334155; }
.req { color: #dc2626; }
.edit-actions {
  display: flex; justify-content: flex-end; flex-shrink: 0;
  margin: auto -1.25rem 0; padding: .75rem 1.25rem .15rem;
  border-top: 1px solid #e2e8f0; background: #fff;
}
.pf-head-text { min-width: 0; }
.pf-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .5rem; }
.pf-class { margin: .2rem 0 .45rem; font-size: .82rem; color: #64748b; }
.pf-idrow { display: flex; flex-wrap: wrap; gap: .35rem; }
.pf-idchip {
  border: 1px solid #e2e8f0; background: #fff; color: #334155; border-radius: 999px;
  padding: .18rem .6rem; font-size: .75rem; font-weight: 650; cursor: pointer;
}
.pf-idchip:hover { border-color: #6ee7b7; background: #ecfdf5; color: #065f46; }
.pf-kpis {
  display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem;
}
.pf-kpi {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .18rem .55rem; border-radius: 999px;
  background: #f8fafc; border: 1px solid #e2e8f0;
  font-size: .72rem; color: #64748b;
}
.pf-kpi strong { color: #0f172a; font-weight: 750; }
.pf-kpi.is-warn, .pf-kpi.is-warn strong { color: #b45309; background: #fffbeb; border-color: #fde68a; }
.pf-close {
  flex-shrink: 0; width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 10px;
  background: #fff; color: #64748b; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
}
.pf-close:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.profile-tabs {
  display: flex; gap: 2px; padding: 0 1.25rem; border-bottom: 1px solid #e2e8f0;
  flex-wrap: nowrap; overflow-x: auto; flex-shrink: 0; background: #fff;
}
.profile-tab {
  border: none; background: transparent; padding: .7rem .9rem; cursor: pointer; color: #64748b;
  font-weight: 650; font-size: .85rem; white-space: nowrap;
  border-bottom: 2px solid transparent; margin-bottom: -1px;
  display: inline-flex; align-items: center; gap: .35rem;
}
.profile-tab:hover { color: #334155; }
.profile-tab.active { color: #047857; border-bottom-color: #059669; }
.pf-tab-count {
  min-width: 1.2rem; padding: 0 .35rem; border-radius: 999px; font-size: .68rem;
  background: #ecfdf5; color: #047857; font-weight: 750; text-align: center;
}
.profile-tab.active .pf-tab-count { background: #059669; color: #fff; }
.modal-body { padding: 1.05rem 1.25rem 1.25rem; overflow: auto; flex: 1; min-height: 0; }
.modal-body.is-editing {
  display: flex; flex-direction: column; padding-bottom: .85rem;
}
.pf-dossier { display: flex; flex-direction: column; gap: .85rem; }
.pf-card {
  border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.05rem; background: #fff;
}
.pf-card h4 {
  margin: 0 0 .75rem; font-size: .72rem; color: #64748b; letter-spacing: .06em;
  text-transform: uppercase; font-weight: 750;
}
.pf-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.15rem; }
.pf-field { display: flex; flex-direction: column; gap: .2rem; min-width: 0; }
.pf-k {
  font-size: .68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 650;
}
.pf-v { color: #0f172a; font-size: .9rem; line-height: 1.4; word-break: break-word; }
.pf-copy {
  border: none; background: none; padding: 0; text-align: left; cursor: pointer; font: inherit; font-weight: 600;
}
.pf-copy:hover { color: #047857; }
.profile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.akademik-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.pf-span-2 { grid-column: 1 / -1; }
.info-block { border: 1px solid #e2e8f0; border-radius: 14px; padding: .95rem; background: #fff; }
.info-block.highlight { background: #fff; }
.info-block h4 { margin: 0 0 .55rem; font-size: .82rem; color: #475569; letter-spacing: .02em; }
.info-block dl { margin: 0; display: flex; flex-direction: column; gap: .5rem; }
.info-block dt, .pf-contact-label {
  font-size: .7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 650;
}
.info-block dd { margin: 0; color: #0f172a; font-size: .9rem; }
.pf-inline-link { color: #047857; text-decoration: none; font-weight: 600; }
.pf-inline-link:hover { text-decoration: underline; }
.pf-account-status { margin: 0 0 .4rem; font-size: .82rem; padding: .4rem .6rem; border-radius: 8px; }
.pf-account-status.ok { background: #ecfdf5; color: #047857; }
.pf-account-status.warn { background: #fffbeb; color: #92400e; }
.pf-account-status.bad { background: #fef2f2; color: #b91c1c; }
.pf-contacts, .pf-parents { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.pf-parents { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.pf-person {
  padding: .75rem .8rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;
}
.pf-person strong, .pf-parents strong { display: block; color: #0f172a; font-size: .92rem; margin: .15rem 0 .1rem; }
.pf-person .metric-hint { margin: .1rem 0 0; }
.pf-contact-card {
  display: flex; flex-direction: column; gap: .2rem;
  padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;
}
.pf-contact-card strong { color: #0f172a; font-size: .95rem; }
.pf-contact-actions { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
.chip-link {
  border: 1px solid #d1fae5; background: #fff; color: #065f46; border-radius: 999px;
  padding: .22rem .6rem; font-size: .75rem; font-weight: 650; text-decoration: none; cursor: pointer;
}
.chip-link:hover { background: #ecfdf5; border-color: #6ee7b7; }
.chip-wa { border-color: #bbf7d0; color: #166534; }
.pf-section-head { display: flex; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .55rem; }
.pf-section-head h4 { margin: 0; }
.pf-att-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; margin-bottom: .55rem; }
.pf-att {
  text-align: center; padding: .7rem .4rem; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0;
}
.pf-att strong { display: block; font-size: 1.2rem; color: #0f172a; }
.pf-att span { font-size: .72rem; color: #64748b; font-weight: 650; }
.pf-att.hadir { background: #ecfdf5; border-color: #a7f3d0; }
.pf-att.hadir strong { color: #047857; }
.pf-att.izin { background: #eff6ff; border-color: #bfdbfe; }
.pf-att.izin strong { color: #1d4ed8; }
.pf-att.sakit { background: #fffbeb; border-color: #fde68a; }
.pf-att.sakit strong { color: #b45309; }
.pf-att.alpha { background: #fef2f2; border-color: #fecaca; }
.pf-att.alpha strong { color: #b91c1c; }
.pf-bk-hero {
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
  padding: .95rem 1rem; margin-bottom: .85rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc;
}
.pf-bk-hero .snap-score { margin: .15rem 0 0; font-size: 1.6rem; }
.pf-bk-split { display: flex; gap: 1rem; font-size: .82rem; color: #64748b; }
.pf-bk-split strong { color: #0f172a; }
.pf-list-main { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1; }
.pf-empty {
  text-align: center; padding: 2rem 1rem; color: #64748b; font-size: .9rem;
  background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 12px;
  min-height: 180px; display: flex; align-items: center; justify-content: center;
}
.pf-empty p { margin: 0; }
.pf-skel { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
.pf-skel-card { height: 132px; border-radius: 14px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: pf-pulse 1.2s ease infinite; }
@keyframes pf-pulse { to { background-position: -200% 0; } }
.notes-block h4 { margin: 0 0 .65rem; }
.note-form { display: flex; flex-direction: column; gap: .5rem; margin-bottom: .85rem; }
.note-form textarea {
  border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem .85rem; font: inherit; resize: vertical; min-height: 84px;
}
.note-form textarea:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
.note-form-bar { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.note-form-actions { display: flex; gap: .4rem; }
.notes-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .65rem; }
.notes-list li { border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem .85rem; background: #fff; }
.note-meta { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; font-size: .82rem; }
.notes-list p { margin: 0; white-space: pre-wrap; color: #334155; }
.note-actions { margin-top: .45rem; display: flex; gap: .4rem; }
.metric-hint { font-size: .78rem; color: #94a3b8; margin: .35rem 0 0; }
.metric-row { display: flex; flex-wrap: wrap; gap: .55rem; font-size: .82rem; color: #334155; }

.filter-chip {
  flex: 0 0 auto;
  height: 40px;
  border: 1px solid #fcd34d; background: #fffbeb; color: #92400e; border-radius: 999px;
  padding: 0 .75rem; font-size: .8rem; font-weight: 650; cursor: pointer; white-space: nowrap;
}
.period-chip {
  border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 0 .75rem;
  height: 40px; border-radius: 10px; font-weight: 650; cursor: pointer; font-size: .82rem;
}
.period-chip.active { background: #059669; border-color: #059669; color: #fff; }
.link-btn-sm {
  display: inline-flex; align-items: center; height: 40px; padding: 0 .75rem; border-radius: 10px;
  border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; font-size: .82rem; box-sizing: border-box;
}
.link-btn-sm:hover { border-color: #34d399; color: #065f46; background: #ecfdf5; }
.overview-stats { display: flex; flex-wrap: wrap; gap: .65rem; margin-bottom: 1rem; }
.ov-stat {
  min-width: 88px; padding: .65rem .85rem; border-radius: 12px; background: #f8fafc;
  border: 1px solid #e2e8f0; text-align: center;
}
.ov-stat strong { display: block; font-size: 1.25rem; color: #0f172a; }
.ov-stat span { font-size: .75rem; color: #64748b; }
.ov-stat.warn { background: #fffbeb; border-color: #fcd34d; }
.ov-stat.warn strong { color: #b45309; }
.alert-box {
  margin-bottom: 1rem; padding: .85rem 1rem; border-radius: 12px;
  background: #fef2f2; border: 1px solid #fecaca;
}
.alert-box h3 { margin: 0 0 .45rem; font-size: .9rem; color: #991b1b; }
.alert-box ul { margin: 0; padding-left: 1.1rem; color: #7f1d1d; font-size: .88rem; }
.cell-warn { color: #b45309; font-weight: 700; }
.detail-cell { display: flex; flex-wrap: wrap; gap: .3rem; max-width: 320px; }
.tag {
  display: inline-flex; padding: .12rem .4rem; border-radius: 6px; font-size: .72rem; font-weight: 650;
}
.tag-miss { background: #f1f5f9; color: #475569; }
.tag-kkm { background: #fff7ed; color: #c2410c; }
.tag-wrap { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
.snapshot-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; margin-bottom: 1rem; }
.snapshot-lists { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.snap-score { margin: 0 0 .25rem; font-size: 1.15rem; font-weight: 750; color: #0f172a; }
.mini-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; font-size: .82rem; color: #334155; }
.mini-list li { display: flex; flex-wrap: wrap; gap: .35rem; align-items: flex-start; justify-content: space-between; }
.finance-stats .ov-stat { flex: 1; min-width: 120px; }
.finance-filters {
  display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1rem;
}
.finance-filters .search-input { flex: 1; min-width: 160px; }
.data-table .num { text-align: right; white-space: nowrap; }
.btn-sm { padding: .35rem .65rem; font-size: .8rem; height: auto; }
.modal-sheet-sm { max-width: 420px; width: min(420px, 96vw); max-height: 90vh; }
.modal-hint { margin: 0 0 1rem; font-size: .88rem; color: #64748b; line-height: 1.45; }
.modal-form { display: flex; flex-direction: column; gap: .65rem; padding: 0 1.25rem 1.25rem; }
.modal-form .field-label { font-size: .82rem; font-weight: 600; color: #334155; }
.form-error {
  margin: 0 1.25rem .75rem; padding: .55rem .75rem; border-radius: 8px;
  background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .85rem;
}
.status-pill.danger { background: #fef2f2; color: #b91c1c; }
.status-pill.ok { background: #ecfdf5; color: #047857; }
.status-pill.muted { background: #f1f5f9; color: #64748b; }

@media (max-width: 1440px) {
  .toolbar {
    flex-wrap: wrap;
    align-items: stretch;
  }
  .toolbar-left {
    flex-wrap: wrap;
    min-width: 0;
  }
  .search-input {
    flex: 1 1 180px;
    width: auto;
    max-width: 240px;
    min-width: 140px;
  }
  .toolbar-actions {
    flex-wrap: wrap;
  }
}
@media (max-width: 980px) {
  .usulan-grid, .profile-grid, .snapshot-grid, .snapshot-lists, .pf-contacts, .pf-parents, .pf-skel, .login-row, .edit-form .form-row, .pf-fields, .akademik-grid { grid-template-columns: 1fr; }
  .pf-att-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 720px) {
  .toolbar {
    flex-wrap: wrap;
    align-items: stretch;
  }
  .toolbar-left {
    flex: 1 1 100%;
    max-width: 100%;
    width: 100%;
    flex-wrap: wrap;
  }
  .search-input,
  .filter-select {
    width: 100%;
    max-width: none;
  }
  .class-snapshot {
    width: 100%;
    margin-left: 0;
    padding-left: 0;
    border-left: none;
    height: auto;
    min-height: 32px;
  }
  .toolbar-actions {
    flex: 1 1 100%;
    width: 100%;
    justify-content: flex-end;
    flex-wrap: wrap;
  }
  .modal-backdrop { padding: 0; align-items: flex-end; }
  .modal-sheet { width: 100%; height: 96vh; max-height: 96vh; border-radius: 18px 18px 0 0; }
  .modal-head { padding: 1rem 1rem .7rem; }
  .modal-body { padding: 1rem; }
  .profile-tabs { padding: 0 1rem; }
  .edit-actions { margin-left: -1rem; margin-right: -1rem; padding-left: 1rem; padding-right: 1rem; }
  .pf-avatar { width: 60px; height: 60px; }
}
</style>
