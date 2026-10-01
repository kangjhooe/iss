<template>
    <div class="ekskul-detail">
      <div class="detail-top">
        <button type="button" class="btn-back" @click="$router.push('/extracurricular')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Kembali
        </button>
      </div>

      <div v-if="loading" class="loading-wrap">
        <div class="loading-spinner"></div>
        <p>Memuat data ekstrakurikuler...</p>
      </div>
      <div v-else-if="loadError" class="error-state">
        <p>{{ loadError }}</p>
        <button class="btn-primary" @click="loadItem">Coba lagi</button>
      </div>

      <template v-else>
        <div class="ekskul-header">
          <div class="header-info">
            <div class="header-title-row">
              <h1>{{ item?.name || 'Ekstrakurikuler' }}</h1>
              <span :class="['status-badge', item?.status === 'Aktif' ? 'status-active' : 'status-inactive']">
                {{ item?.status || '—' }}
              </span>
            </div>
            <p v-if="item?.description" class="header-desc">{{ item.description }}</p>
            <div class="meta-grid">
              <div v-if="item?.supervisor?.name" class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Pembina</span>
                  <span class="meta-value">{{ item.supervisor.name }}</span>
                </div>
              </div>
              <div v-if="scheduleText" class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Jadwal</span>
                  <span class="meta-value">{{ scheduleText }}</span>
                </div>
              </div>
              <div v-if="locationText" class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Lokasi</span>
                  <span class="meta-value">{{ locationText }}</span>
                </div>
              </div>
              <div class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Peserta</span>
                  <span class="meta-value">{{ participants.length }} siswa</span>
                </div>
              </div>
              <div v-if="!canSetKkm && !isMemorization" class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">KKM</span>
                  <span class="meta-value">{{ item?.kkm ?? '—' }} <span class="meta-hint">diisi pembina</span></span>
                </div>
              </div>
              <div v-if="isMemorization" class="meta-item">
                <span class="meta-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </span>
                <div class="meta-body">
                  <span class="meta-label">Mode</span>
                  <span class="meta-value">Hapalan / setoran ayat</span>
                </div>
              </div>
            </div>
            <div v-if="canSetKkm && !isMemorization" class="kkm-box">
              <label class="kkm-label">KKM</label>
              <input
                v-model.number="kkmDraft"
                type="number"
                min="0"
                max="100"
                step="0.01"
                class="form-input form-input-sm input-score"
              />
              <button
                type="button"
                class="btn-primary btn-sm"
                :disabled="savingKkm || kkmDraft === '' || kkmDraft == null"
                @click="saveKkm"
              >{{ savingKkm ? 'Menyimpan...' : 'Simpan KKM' }}</button>
              <span class="muted kkm-hint">Di bawah KKM = D; di atas dibagi C, B, A. Predikat dihitung ulang setelah disimpan.</span>
            </div>
          </div>
        </div>

        <div class="tab-shell">
        <nav class="section-nav" role="tablist">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            role="tab"
            :class="['sec-btn', { active: tab === t.key }]"
            :aria-selected="tab === t.key"
            @click="tab = t.key"
          >
            <span class="sec-icon" aria-hidden="true">
              <svg v-if="t.key === 'peserta'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <svg v-else-if="t.key === 'pertemuan'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                <path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <svg v-else-if="t.key === 'nilai'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M4 19V5a1 1 0 0 1 1-1h10l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z" stroke="currentColor" stroke-width="2"/>
                <path d="M14 4v5h5M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <svg v-else-if="t.key === 'hafalan'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/>
                <path d="M9 8h7M9 12h7M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/>
              </svg>
            </span>
            <span class="sec-label">{{ t.label }}</span>
          </button>
        </nav>

        <div class="tab-main">

        <!-- PESERTA -->
        <div v-show="tab === 'peserta'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Daftar Peserta</h2>
            <button type="button" class="btn-primary btn-sm" @click="showAddPeserta = !showAddPeserta">
              {{ showAddPeserta ? 'Tutup form' : 'Tambah Peserta' }}
            </button>
          </div>

          <div v-if="showAddPeserta" class="add-box">
            <div class="form-row">
              <select v-model="availableClassId" class="form-input" @change="onClassChange">
                <option value="">-- Pilih kelas --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <input
                v-if="availableClassId"
                v-model="availableSearch"
                type="text"
                class="form-input"
                placeholder="Cari siswa..."
                @input="debounceAvailable"
              />
            </div>
            <div v-if="availableLoading" class="muted">Memuat siswa...</div>
            <div v-else-if="availableClassId && availableStudents.length" class="available-list">
              <label class="check-all">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" /> Pilih semua ({{ availableStudents.length }})
              </label>
              <div class="available-scroll">
                <div v-for="s in availableStudents" :key="s.id" class="check-row" @click="toggleStudent(s.id)">
                  <input type="checkbox" :checked="selectedIds.includes(s.id)" @click.stop="toggleStudent(s.id)" />
                  <span class="check-name">{{ s.name }}</span>
                  <span class="muted">{{ s.nis || '—' }}</span>
                </div>
              </div>
              <button
                type="button"
                class="btn-primary btn-sm"
                :disabled="!selectedIds.length || savingPeserta"
                @click="submitPeserta"
              >{{ savingPeserta ? 'Menyimpan...' : `Tambah ${selectedIds.length} peserta` }}</button>
            </div>
            <p v-else-if="availableClassId" class="muted">Tidak ada siswa tersedia.</p>
          </div>

          <div v-if="pesertaLoading" class="muted pad-sm">Memuat peserta...</div>
          <div v-else-if="participants.length" class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Nama</th>
                  <th>NIS / NISN</th>
                  <th>Kelas</th>
                  <th class="col-aksi">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in participantGroups" :key="'p-' + group.key">
                  <tr class="group-row">
                    <td colspan="5">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                  </tr>
                  <tr v-for="(p, i) in group.rows" :key="p.id">
                    <td class="col-no">{{ group.start + i + 1 }}</td>
                    <td class="cell-strong">{{ p.student?.name || '—' }}</td>
                    <td>{{ p.student?.nis || '—' }} / {{ p.student?.nisn || '—' }}</td>
                    <td>{{ p.student?.class?.name || '—' }}</td>
                    <td class="col-aksi">
                      <TableAction kind="delete" @click="removePeserta(p)" />
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-inline">Belum ada peserta.</div>
        </div>

        <!-- PERTEMUAN -->
        <div v-show="tab === 'pertemuan'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Pertemuan & Kehadiran</h2>
            <button type="button" class="btn-primary btn-sm" @click="showSessionForm = !showSessionForm">
              {{ showSessionForm ? 'Batal' : 'Tambah Pertemuan' }}
            </button>
          </div>

          <form v-if="showSessionForm" class="add-box" @submit.prevent="submitSession">
            <div class="form-grid">
              <div class="form-group">
                <label>Tanggal <span class="req">*</span></label>
                <input v-model="sessionForm.session_date" type="date" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam mulai</label>
                <input v-model="sessionForm.start_time" type="time" class="form-input" />
              </div>
              <div class="form-group">
                <label>Jam selesai</label>
                <input v-model="sessionForm.end_time" type="time" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Materi / topik</label>
              <input v-model="sessionForm.topic" type="text" class="form-input" placeholder="Materi kegiatan" />
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="sessionForm.notes" rows="2" class="form-input"></textarea>
            </div>
            <label class="check-all">
              <input v-model="sessionForm.with_attendance" type="checkbox" /> Buat daftar kehadiran dari peserta aktif
            </label>
            <button type="submit" class="btn-primary btn-sm" :disabled="savingSession">
              {{ savingSession ? 'Menyimpan...' : 'Simpan pertemuan' }}
            </button>
          </form>

          <div v-if="sessionsLoading" class="muted pad-sm">Memuat pertemuan...</div>
          <div v-else-if="sessions.length" class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Tanggal</th>
                  <th>Materi</th>
                  <th>Kehadiran</th>
                  <th class="col-aksi">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(s, i) in sessions" :key="s.id" :class="{ 'row-active': attendanceSession?.id === s.id || gradingSession?.id === s.id }">
                  <td class="col-no">{{ i + 1 }}</td>
                  <td>
                    <div class="cell-strong">{{ formatDate(s.session_date) }}</div>
                    <div v-if="s.start_time" class="cell-sub">{{ s.start_time }}{{ s.end_time ? `–${s.end_time}` : '' }}</div>
                  </td>
                  <td>{{ s.topic || '—' }}</td>
                  <td>{{ s.attendances_count ?? 0 }} siswa</td>
                  <td class="col-aksi">
                    <div class="action-btns">
                      <button
                        type="button"
                        class="btn-icon"
                        :class="{ active: attendanceSession?.id === s.id }"
                        title="Kehadiran"
                        aria-label="Kehadiran"
                        @click="openAttendance(s)"
                      >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                      <button
                        v-if="!isMemorization"
                        type="button"
                        class="btn-icon"
                        :class="{ active: gradingSession?.id === s.id }"
                        title="Penilaian"
                        aria-label="Penilaian"
                        @click="openGrading(s)"
                      >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M12 20H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M16.5 3.5C16.8978 3.10219 17.4374 2.87869 18 2.87869C18.2786 2.87869 18.5544 2.93355 18.8118 3.04015C19.0692 3.14676 19.303 3.30301 19.5 3.5C19.697 3.69699 19.8532 3.93077 19.9598 4.18815C20.0665 4.44554 20.1213 4.72142 20.1213 5C20.1213 5.27858 20.0665 5.55446 19.9598 5.81185C19.8532 6.06923 19.697 6.30301 19.5 6.5L7 19L3 20L4 16L16.5 3.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                      <button
                        type="button"
                        class="btn-icon danger"
                        title="Hapus"
                        aria-label="Hapus pertemuan"
                        @click="deleteSession(s)"
                      >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M10 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="empty-inline">Belum ada pertemuan. Tambah pertemuan untuk mulai isi kehadiran.</div>

          <div v-if="attendanceSession" class="add-box attendance-box">
            <div class="panel-toolbar">
              <strong>Kehadiran — {{ formatDate(attendanceSession.session_date) }}{{ attendanceSession.topic ? ` · ${attendanceSession.topic}` : '' }}</strong>
              <button type="button" class="btn-secondary btn-sm" @click="attendanceSession = null">Tutup</button>
            </div>
            <div v-if="attendanceLoading" class="muted">Memuat kehadiran...</div>
            <template v-else>
              <div class="roster-toolbar">
                <input
                  v-model="attendancePager.search"
                  type="search"
                  class="form-input"
                  placeholder="Cari nama / NIS..."
                />
                <select v-model="attendancePager.classId" class="form-input">
                  <option value="">Semua kelas</option>
                  <option v-for="c in attendancePager.classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <button type="button" class="btn-secondary btn-sm" :disabled="!attendancePager.paged.length" @click="markAttendancePage('hadir')">
                  Tandai halaman ini Hadir
                </button>
              </div>
              <p class="roster-meta">{{ attendancePager.rangeLabel }} · urut per kelas, lalu abjad</p>
              <div v-if="attendancePager.paged.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in attendancePageGroups" :key="'att-' + group.key">
                      <tr class="group-row">
                        <td colspan="4">Kelas {{ group.name }}</td>
                      </tr>
                      <tr v-for="(a, i) in group.rows" :key="a.student_id">
                        <td class="col-no">{{ group.start + i + 1 }}</td>
                        <td class="cell-strong">{{ a.student?.name }}</td>
                        <td>{{ a.student?.class?.name || '—' }}</td>
                        <td>
                          <select v-model="a.status" class="form-input form-input-sm status-select">
                            <option v-for="st in attendanceStatuses" :key="st" :value="st">{{ statusLabel(st) }}</option>
                          </select>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Tidak ada siswa yang cocok.</p>
              <PaginationBar
                :page="attendancePager.page"
                :last-page="attendancePager.lastPage"
                :per-page="attendancePager.perPage"
                :total="attendancePager.total"
                item-label="siswa"
                @page-change="attendancePager.goPage"
                @per-page-change="attendancePager.changePerPage"
              />
              <button type="button" class="btn-primary btn-sm" :disabled="savingAttendance || !attendances.length" @click="saveAttendance">
                {{ savingAttendance ? 'Menyimpan...' : 'Simpan kehadiran' }}
              </button>
            </template>
          </div>

          <div v-if="gradingSession" class="add-box attendance-box">
            <div class="panel-toolbar">
              <strong>
                Penilaian — {{ formatDate(gradingSession.session_date) }}{{ gradingSession.topic ? ` · ${gradingSession.topic}` : '' }}
                <span class="meta-chip" style="margin-left:8px">KKM {{ gradingKkm }}</span>
              </strong>
              <button type="button" class="btn-secondary btn-sm" @click="gradingSession = null">Tutup</button>
            </div>
            <p class="muted pad-sm" style="padding-top:0">Siswa alpha otomatis tanpa nilai. Predikat dihitung dari KKM.</p>
            <div v-if="gradingLoading" class="muted">Memuat nilai...</div>
            <template v-else>
              <div class="roster-toolbar">
                <input
                  v-model="gradingPager.search"
                  type="search"
                  class="form-input"
                  placeholder="Cari nama / NIS..."
                />
                <select v-model="gradingPager.classId" class="form-input">
                  <option value="">Semua kelas</option>
                  <option v-for="c in gradingPager.classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <p class="roster-meta">{{ gradingPager.rangeLabel }} · urut per kelas, lalu abjad</p>
              <div v-if="gradingPager.paged.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th>Kehadiran</th>
                      <th>Nilai</th>
                      <th>Predikat</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in gradingPageGroups" :key="'gr-' + group.key">
                      <tr class="group-row">
                        <td colspan="6">Kelas {{ group.name }}</td>
                      </tr>
                      <tr v-for="(g, i) in group.rows" :key="g.student_id">
                        <td class="col-no">{{ group.start + i + 1 }}</td>
                        <td class="cell-strong">{{ g.student?.name }}</td>
                        <td>{{ g.student?.class?.name || '—' }}</td>
                        <td>{{ statusLabel(g.attendance_status) || '—' }}</td>
                        <td>
                          <input
                            v-model.number="g.score"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="form-input form-input-sm input-score"
                            :disabled="g.score_locked"
                            :placeholder="g.score_locked ? 'Alpha' : ''"
                            @input="onSessionScoreInput(g)"
                          />
                        </td>
                        <td>{{ g.predicate || '—' }}</td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Tidak ada siswa yang cocok.</p>
              <PaginationBar
                :page="gradingPager.page"
                :last-page="gradingPager.lastPage"
                :per-page="gradingPager.perPage"
                :total="gradingPager.total"
                item-label="siswa"
                @page-change="gradingPager.goPage"
                @per-page-change="gradingPager.changePerPage"
              />
              <button type="button" class="btn-primary btn-sm" :disabled="savingSessionGrades || !sessionGrades.length" @click="saveSessionGrades">
                {{ savingSessionGrades ? 'Menyimpan...' : 'Simpan penilaian' }}
              </button>
            </template>
          </div>
        </div>

        <!-- NILAI / REKAP -->
        <div v-show="tab === 'nilai'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Rekap Nilai Peserta</h2>
            <button type="button" class="btn-secondary btn-sm" :disabled="savingGrades || gradesLoading" @click="recalcGrades">
              {{ savingGrades ? 'Menghitung...' : 'Hitung ulang nilai akhir' }}
            </button>
          </div>
          <p class="muted pad-sm" style="padding-top:0">
            Nilai akhir = rata-rata skor pertemuan yang terisi.
            KKM {{ gradesKkm ?? item?.kkm ?? '—' }} · Predikat: &lt;KKM = D; di atas KKM dibagi C, B, A.
          </p>
          <div v-if="gradesLoading" class="muted pad-sm">Memuat rekap nilai...</div>
          <template v-else-if="grades.length">
            <div class="roster-toolbar">
              <input
                v-model="gradesPager.search"
                type="search"
                class="form-input"
                placeholder="Cari nama / NIS..."
              />
              <select v-model="gradesPager.classId" class="form-input">
                <option value="">Semua kelas</option>
                <option v-for="c in gradesPager.classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <p class="roster-meta">{{ gradesPager.rangeLabel }} · urut per kelas, lalu abjad</p>
            <div v-if="gradesPager.paged.length" class="table-scroll">
              <table class="data-table matrix-table">
                <thead>
                  <tr>
                    <th class="col-no sticky-col">No</th>
                    <th class="sticky-col sticky-name">Nama</th>
                    <th>Kelas</th>
                    <th
                      v-for="s in gradeSessions"
                      :key="s.id"
                      class="col-center"
                      :title="sessionColTitle(s)"
                    >{{ formatDateShort(s.session_date) }}</th>
                    <th class="col-center">Jml</th>
                    <th class="col-center">Rata-rata</th>
                    <th class="col-center">Akhir</th>
                    <th class="col-center">Predikat</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="group in gradePageGroups" :key="'nilai-' + group.key">
                    <tr class="group-row">
                      <td :colspan="7 + gradeSessions.length">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                    </tr>
                    <tr v-for="(g, i) in group.rows" :key="g.student_id">
                      <td class="col-no sticky-col">{{ group.start + i + 1 }}</td>
                      <td class="cell-strong sticky-col sticky-name">{{ g.student?.name }}</td>
                      <td>{{ g.student?.class?.name || '—' }}</td>
                      <td v-for="s in gradeSessions" :key="s.id" class="col-center">
                        {{ g.scores?.[String(s.id)] != null ? g.scores[String(s.id)] : '—' }}
                      </td>
                      <td class="col-center">{{ g.graded_sessions ?? 0 }}</td>
                      <td class="col-center">{{ g.average != null ? g.average : '—' }}</td>
                      <td class="col-center">{{ g.final_score != null ? g.final_score : '—' }}</td>
                      <td class="col-center">{{ g.predicate || '—' }}</td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
            <p v-else class="muted pad-sm">Tidak ada siswa yang cocok.</p>
            <PaginationBar
              :page="gradesPager.page"
              :last-page="gradesPager.lastPage"
              :per-page="gradesPager.perPage"
              :total="gradesPager.total"
              item-label="siswa"
              @page-change="gradesPager.goPage"
              @per-page-change="gradesPager.changePerPage"
            />
          </template>
          <div v-else class="empty-inline">Belum ada peserta aktif. Tambah pertemuan lalu isi penilaian untuk melihat rekap.</div>
        </div>

        <!-- HAFALAN -->
        <div v-show="tab === 'hafalan'" class="panel">
          <div class="panel-toolbar">
            <h2 class="panel-title">Hapalan / Setoran</h2>
            <div class="toolbar-actions">
              <button type="button" class="btn-secondary btn-sm" :disabled="memLoading" @click="loadMemorization">Refresh</button>
              <button
                v-if="!memTargets.length"
                type="button"
                class="btn-primary btn-sm"
                :disabled="memSavingTarget"
                @click="createJuz30Target"
              >
                {{ memSavingTarget ? 'Menyimpan...' : 'Set target Juz 30' }}
              </button>
            </div>
          </div>

          <div v-if="memTargets.length" class="mem-targets">
            <div v-for="t in memTargets" :key="t.id" class="mem-target-chip">
              <strong>{{ t.name || scopeLabel(t.scope) }}</strong>
              <span class="muted"> · {{ t.target_ayahs }} ayat</span>
              <span v-if="t.scope === 'class' && t.class" class="muted"> · {{ t.class.name }}</span>
              <span v-if="t.scope === 'student' && t.student" class="muted"> · {{ t.student.name }}</span>
              <button type="button" class="btn-link danger" title="Hapus target" @click="removeTarget(t)">×</button>
            </div>
          </div>
          <p v-else class="muted pad-sm">Belum ada target. Buat target Juz 30 (37 surat) atau atur custom lewat API.</p>

          <div v-if="memLoading" class="muted pad-sm">Memuat progres hapalan...</div>
          <template v-else>
            <div class="roster-toolbar">
              <input v-model="memPager.search" type="search" class="form-input" placeholder="Cari nama / NIS..." />
              <select v-model="memPager.classId" class="form-input">
                <option value="">Semua kelas</option>
                <option v-for="c in memPager.classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <p class="roster-meta">{{ memPager.rangeLabel }} · klik siswa untuk checklist ayat</p>
            <div v-if="memPager.paged.length" class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Target</th>
                    <th class="col-center">Progres</th>
                    <th class="col-center">%</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="group in memPageGroups" :key="'mem-' + group.key">
                    <tr class="group-row">
                      <td colspan="6">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                    </tr>
                    <tr
                      v-for="(row, i) in group.rows"
                      :key="row.student.id"
                      class="row-clickable"
                      :class="{ 'row-active': memSelectedStudentId === row.student.id }"
                      @click="openStudentMem(row)"
                    >
                      <td class="col-no">{{ group.start + i + 1 }}</td>
                      <td class="cell-strong">{{ row.student.name }}</td>
                      <td>{{ row.student.class?.name || '—' }}</td>
                      <td>{{ row.target_name || '—' }}</td>
                      <td class="col-center">{{ row.deposited_ayahs }}/{{ row.target_ayahs || '—' }}</td>
                      <td class="col-center">
                        <span class="mem-pct" :class="pctClass(row.percent)">{{ row.percent }}%</span>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
            <PaginationBar
              v-if="memPager.total"
              :page="memPager.page"
              :last-page="memPager.lastPage"
              :per-page="memPager.perPage"
              :total="memPager.total"
              item-label="siswa"
              @page-change="memPager.goPage"
              @per-page-change="memPager.changePerPage"
            />
            <div v-else class="empty-inline">Belum ada peserta aktif.</div>
          </template>

          <div v-if="memSelectedStudentId" class="add-box mem-detail">
            <div class="panel-toolbar">
              <strong>{{ memDetail?.student?.name || 'Siswa' }} — checklist hapalan</strong>
              <button type="button" class="btn-secondary btn-sm" @click="closeStudentMem">Tutup</button>
            </div>
            <div v-if="memDetailLoading" class="muted pad-sm">Memuat detail...</div>
            <template v-else-if="memDetail">
              <p class="muted pad-sm" style="padding-top:0">
                Progres {{ memDetail.deposited_ayahs }}/{{ memDetail.target_ayahs }} ayat ({{ memDetail.percent }}%)
              </p>
              <div class="mem-surah-list">
                <button
                  v-for="s in memDetail.surahs"
                  :key="s.surah_number"
                  type="button"
                  class="mem-surah-btn"
                  :class="{ active: memChecklistSurah === s.surah_number }"
                  @click="loadChecklist(s.surah_number)"
                >
                  <span class="mem-surah-num">{{ s.surah_number }}</span>
                  <span class="mem-surah-name">{{ s.name_id || s.name_latin }}</span>
                  <span class="mem-surah-prog">{{ s.deposited_ayahs }}/{{ s.target_ayahs }}</span>
                </button>
              </div>

              <div v-if="memChecklist" class="mem-checklist">
                <div class="panel-toolbar">
                  <strong>
                    {{ memChecklist.surah.name_id }}
                    <span class="muted">({{ memChecklist.deposited_count }}/{{ memChecklist.surah.ayah_count }})</span>
                  </strong>
                  <div class="toolbar-actions">
                    <button type="button" class="btn-secondary btn-sm" @click="selectAllAyahs(true)">Centang semua</button>
                    <button type="button" class="btn-secondary btn-sm" @click="selectAllAyahs(false)">Kosongkan</button>
                    <button type="button" class="btn-primary btn-sm" :disabled="memSavingChecklist" @click="saveChecklist">
                      {{ memSavingChecklist ? 'Menyimpan...' : 'Simpan setoran' }}
                    </button>
                  </div>
                </div>
                <div class="ayah-grid">
                  <label
                    v-for="a in memChecklist.ayahs"
                    :key="a.ayah_number"
                    class="ayah-check"
                    :class="{ on: isAyahChecked(a.ayah_number) }"
                  >
                    <input
                      type="checkbox"
                      :checked="isAyahChecked(a.ayah_number)"
                      @change="toggleAyah(a.ayah_number, $event.target.checked)"
                    />
                    <span class="ayah-num">{{ a.ayah_number }}</span>
                    <span v-if="a.text_ar" class="ayah-ar" dir="rtl">{{ a.text_ar }}</span>
                  </label>
                </div>
              </div>
              <p v-else-if="memDetail.surahs?.length" class="muted pad-sm">Pilih surat di atas untuk mencentang ayat.</p>
              <p v-else class="muted pad-sm">Belum ada target surat untuk siswa ini.</p>
            </template>
          </div>
        </div>

        <!-- LAPORAN -->
        <div v-show="tab === 'laporan'" class="panel">
          <div class="panel-toolbar report-toolbar">
            <h2 class="panel-title">Laporan Kegiatan</h2>
            <div class="toolbar-actions">
              <button type="button" class="btn-secondary btn-sm" :disabled="reportLoading" @click="loadReport">Refresh</button>
              <button type="button" class="btn-secondary btn-sm" :disabled="!report || exportingCsv" @click="exportReport">
                {{ exportingCsv ? 'Mengekspor...' : 'Export CSV' }}
              </button>
              <button type="button" class="btn-primary btn-sm" :disabled="!report || printingPdf" @click="printReportPdf">
                {{ printingPdf ? 'Menyiapkan...' : 'Cetak PDF' }}
              </button>
            </div>
          </div>

          <div class="report-filters">
            <div class="period-tabs">
              <button
                v-for="p in periodOptions"
                :key="p.value"
                type="button"
                :class="['period-btn', { active: reportFilters.period === p.value }]"
                @click="setPeriod(p.value)"
              >{{ p.label }}</button>
            </div>
            <div class="filter-controls">
              <select
                v-if="reportFilters.period === 'semester'"
                v-model="reportFilters.semester_id"
                class="form-input filter-input"
                @change="loadReport"
              >
                <option value="">Semester aktif</option>
                <option v-for="s in semesters" :key="s.id" :value="s.id">
                  {{ s.name }}{{ s.academic_year?.name ? ` · ${s.academic_year.name}` : '' }}
                </option>
              </select>
              <template v-if="reportFilters.period === 'year' || reportFilters.period === 'month'">
                <select v-model.number="reportFilters.year" class="form-input filter-input" @change="loadReport">
                  <option v-for="y in yearOptions" :key="y" :value="y">Tahun {{ y }}</option>
                </select>
              </template>
              <select
                v-if="reportFilters.period === 'month'"
                v-model.number="reportFilters.month"
                class="form-input filter-input"
                @change="loadReport"
              >
                <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
              <select v-if="reportClassOptions.length" v-model="reportClassId" class="form-input filter-input">
                <option value="">Semua kelas</option>
                <option v-for="c in reportClassOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
          </div>

          <div v-if="reportLoading" class="muted pad-sm">Memuat laporan...</div>
          <template v-else-if="report">
            <p class="period-label">Periode: <strong>{{ report.period?.label || '—' }}</strong></p>

            <div class="stat-grid">
              <div class="stat-card">
                <div class="stat-val">{{ report.participants_count ?? 0 }}</div>
                <div class="stat-label">Peserta</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.sessions_count ?? 0 }}</div>
                <div class="stat-label">Pertemuan</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.attendance?.hadir ?? 0 }}</div>
                <div class="stat-label">Total Hadir</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.attendance?.hadir_pct != null ? report.attendance.hadir_pct + '%' : '—' }}</div>
                <div class="stat-label">% Kehadiran</div>
              </div>
              <div class="stat-card">
                <div class="stat-val">{{ report.grades?.average_score ?? '—' }}</div>
                <div class="stat-label">Rata-rata Nilai</div>
              </div>
            </div>

            <div class="report-section">
              <h3 class="report-section-title">Daftar Pertemuan ({{ report.sessions?.length || 0 }})</h3>
              <div v-if="report.sessions?.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Tanggal</th>
                      <th>Jam</th>
                      <th>Materi</th>
                      <th>Kehadiran</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(s, i) in report.sessions" :key="s.id || i">
                      <td class="col-no">{{ i + 1 }}</td>
                      <td>{{ formatDate(s.session_date) }}</td>
                      <td>{{ s.start_time || s.end_time ? `${s.start_time || '?'}–${s.end_time || '?'}` : '—' }}</td>
                      <td>{{ s.topic || '—' }}</td>
                      <td>{{ s.attendances_count ?? 0 }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Tidak ada pertemuan pada periode ini.</p>
            </div>

            <div class="report-section">
              <h3 class="report-section-title">
                Rekap Kehadiran per Pertemuan
                <span class="title-meta">
                  ({{ filteredAttendanceRows.length }} peserta × {{ report.attendance_matrix?.sessions?.length || 0 }} pertemuan)
                </span>
              </h3>
              <p class="matrix-hint">
                Setiap kolom = satu pertemuan. H = Hadir, I = Izin, S = Sakit, A = Alpha, — = belum dicatat.
              </p>
              <div v-if="report.attendance_matrix?.sessions?.length && filteredAttendanceRows.length" class="table-scroll matrix-scroll">
                <table class="data-table matrix-table">
                  <thead>
                    <tr>
                      <th class="col-no sticky-col">No</th>
                      <th class="sticky-col sticky-name">Nama</th>
                      <th>Kelas</th>
                      <th
                        v-for="s in report.attendance_matrix.sessions"
                        :key="s.id"
                        class="col-center col-session"
                        :title="sessionColTitle(s)"
                      >
                        <span class="session-day">{{ s.label }}</span>
                        <span class="session-month">{{ sessionMonthLabel(s.session_date) }}</span>
                      </th>
                      <th class="col-center">H</th>
                      <th class="col-center">I</th>
                      <th class="col-center">S</th>
                      <th class="col-center">A</th>
                      <th class="col-center">%</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in reportAttendanceGroups" :key="'attm-' + group.key">
                      <tr class="group-row">
                        <td :colspan="8 + (report.attendance_matrix.sessions?.length || 0)">
                          Kelas {{ group.name }} · {{ group.rows.length }} siswa
                        </td>
                      </tr>
                      <tr v-for="(r, i) in group.rows" :key="r.student_id">
                        <td class="col-no sticky-col">{{ group.start + i + 1 }}</td>
                        <td class="cell-strong sticky-col sticky-name">{{ r.name }}</td>
                        <td>{{ r.class?.name || '—' }}</td>
                        <td
                          v-for="s in report.attendance_matrix.sessions"
                          :key="s.id"
                          class="col-center"
                        >
                          <span :class="['att-code', attCodeClass(matrixStatus(r, s.id))]">
                            {{ attCode(matrixStatus(r, s.id)) }}
                          </span>
                        </td>
                        <td class="col-center">{{ r.hadir }}</td>
                        <td class="col-center">{{ r.izin }}</td>
                        <td class="col-center">{{ r.sakit }}</td>
                        <td class="col-center">{{ r.alpha }}</td>
                        <td class="col-center">{{ r.hadir_pct != null ? r.hadir_pct + '%' : '—' }}</td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Belum ada pertemuan/peserta untuk ditampilkan dalam matriks kehadiran.</p>
            </div>

            <div class="report-section">
              <h3 class="report-section-title">
                Rekap Nilai per Pertemuan
                <span v-if="report.kkm != null" class="muted"> · KKM {{ report.kkm }}</span>
              </h3>
              <div v-if="report.grade_matrix?.sessions?.length && filteredGradeRows.length" class="table-scroll">
                <table class="data-table matrix-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th
                        v-for="s in report.grade_matrix.sessions"
                        :key="s.id"
                        class="col-center"
                        :title="sessionColTitle(s)"
                      >{{ s.label || formatDateShort(s.session_date) }}</th>
                      <th class="col-center">Jml</th>
                      <th class="col-center">Rata</th>
                      <th class="col-center">Akhir</th>
                      <th class="col-center">Pred.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in reportGradeGroups" :key="'grm-' + group.key">
                      <tr class="group-row">
                        <td :colspan="7 + (report.grade_matrix.sessions?.length || 0)">
                          Kelas {{ group.name }} · {{ group.rows.length }} siswa
                        </td>
                      </tr>
                      <tr v-for="(r, i) in group.rows" :key="r.student_id">
                        <td class="col-no">{{ group.start + i + 1 }}</td>
                        <td class="cell-strong">{{ r.name }}</td>
                        <td>{{ r.class?.name || '—' }}</td>
                        <td v-for="s in report.grade_matrix.sessions" :key="s.id" class="col-center">
                          {{ r.scores?.[String(s.id)] != null ? r.scores[String(s.id)] : '—' }}
                        </td>
                        <td class="col-center">{{ r.graded_sessions ?? 0 }}</td>
                        <td class="col-center">{{ r.average != null ? r.average : '—' }}</td>
                        <td class="col-center">{{ r.score != null ? r.score : '—' }}</td>
                        <td class="col-center">{{ r.predicate || '—' }}</td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Belum ada nilai pertemuan untuk ditampilkan.</p>
            </div>

            <div v-if="report.period?.type !== 'month'" class="report-section">
              <h3 class="report-section-title">Rekap Ringkas per Siswa ({{ filteredPerStudentRows.length }})</h3>
              <div v-if="filteredPerStudentRows.length" class="table-scroll">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama</th>
                      <th>Kelas</th>
                      <th class="col-center">Hadir</th>
                      <th class="col-center">Izin</th>
                      <th class="col-center">Sakit</th>
                      <th class="col-center">Alpha</th>
                      <th class="col-center">% Hadir</th>
                      <th class="col-center">Nilai</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="group in reportPerStudentGroups" :key="'ps-' + group.key">
                      <tr class="group-row">
                        <td colspan="9">Kelas {{ group.name }} · {{ group.rows.length }} siswa</td>
                      </tr>
                      <tr v-for="(r, i) in group.rows" :key="r.student_id">
                        <td class="col-no">{{ group.start + i + 1 }}</td>
                        <td class="cell-strong">{{ r.name }}</td>
                        <td>{{ r.class?.name || '—' }}</td>
                        <td class="col-center">{{ r.hadir }}</td>
                        <td class="col-center">{{ r.izin }}</td>
                        <td class="col-center">{{ r.sakit }}</td>
                        <td class="col-center">{{ r.alpha }}</td>
                        <td class="col-center">{{ r.hadir_pct != null ? r.hadir_pct + '%' : '—' }}</td>
                        <td class="col-center">{{ r.score != null ? r.score : '—' }}{{ r.predicate ? ` (${r.predicate})` : '' }}</td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
              <p v-else class="muted">Belum ada data kehadiran untuk direkap.</p>
            </div>
          </template>
          <div v-else class="empty-inline">Pilih periode lalu muat laporan.</div>
        </div>
        </div>
        </div>
      </template>

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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import TableAction from '@/components/TableAction.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { extracurricularApi } from '@/api/extracurricular'
import { semesterApi } from '@/api/semester'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'

const route = useRoute()
const toast = useToast()
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const id = computed(() => Number(route.params.id))
const isMemorization = computed(() => (item.value?.assessment_mode || 'standard') === 'memorization')
const tabs = computed(() => {
  const base = [
    { key: 'peserta', label: 'Peserta' },
    { key: 'pertemuan', label: 'Pertemuan' },
  ]
  if (isMemorization.value) {
    base.push({ key: 'hafalan', label: 'Hapalan' })
  } else {
    base.push({ key: 'nilai', label: 'Rekap Nilai' })
  }
  base.push({ key: 'laporan', label: 'Laporan' })
  return base
})
const tab = ref('peserta')

const item = ref(null)
const loading = ref(true)
const loadError = ref('')
const canSetKkm = ref(false)
const kkmDraft = ref(75)
const savingKkm = ref(false)

const DAYS = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu' }
const scheduleText = computed(() => {
  const days = Array.isArray(item.value?.day_labels) && item.value.day_labels.length
    ? item.value.day_labels
    : (item.value?.days_of_week || []).map((d) => DAYS[d]).filter(Boolean)
  if (!days.length) return ''
  const time = item.value.start_time || item.value.end_time
    ? ` ${String(item.value.start_time || '?').slice(0, 5)}-${String(item.value.end_time || '?').slice(0, 5)}`
    : ''
  return days.join(', ') + time
})
const locationText = computed(() => {
  if (item.value?.is_outdoor) {
    return item.value.location_note ? `Di luar: ${item.value.location_note}` : 'Di luar ruangan'
  }
  return item.value?.room?.name || ''
})

const classes = ref([])
const semesters = ref([])
const participants = ref([])
const pesertaLoading = ref(false)
const showAddPeserta = ref(false)
const availableClassId = ref('')
const availableSearch = ref('')
const availableStudents = ref([])
const availableLoading = ref(false)
const selectedIds = ref([])
const savingPeserta = ref(false)

const sessions = ref([])
const sessionsLoading = ref(false)
const showSessionForm = ref(false)
const savingSession = ref(false)
const sessionForm = ref({
  session_date: new Date().toISOString().slice(0, 10),
  start_time: '',
  end_time: '',
  topic: '',
  notes: '',
  with_attendance: true,
})

const attendanceSession = ref(null)
const attendances = ref([])
const attendanceStatuses = ref(['hadir', 'izin', 'sakit', 'alpha'])
const attendanceLoading = ref(false)
const savingAttendance = ref(false)

const gradingSession = ref(null)
const sessionGrades = ref([])
const gradingKkm = ref(75)
const gradingLoading = ref(false)
const savingSessionGrades = ref(false)

function groupByClass(rows, getClass) {
  const groups = []
  let current = null
  let index = 0
  for (const row of rows || []) {
    const c = getClass(row)
    const key = String(c?.id ?? c?.name ?? '')
    const name = c?.name || 'Tanpa kelas'
    if (!current || current.key !== key) {
      current = { key, name, rows: [], start: index }
      groups.push(current)
    }
    current.rows.push(row)
    index += 1
  }
  return groups
}

function uniqueClassesFrom(rows, getClass) {
  const map = new Map()
  for (const row of rows || []) {
    const c = getClass(row)
    if (!c?.id || map.has(c.id)) continue
    map.set(c.id, {
      id: c.id,
      name: c.name || '—',
      grade: c.grade == null || c.grade === '' ? 999 : Number(c.grade),
    })
  }
  return [...map.values()].sort((a, b) => (a.grade - b.grade) || a.name.localeCompare(b.name, 'id', { numeric: true }))
}

function useRosterPager(rowsRef, getStudent) {
  const page = ref(1)
  const perPage = ref(20)
  const search = ref('')
  const classId = ref('')

  const classOptions = computed(() => uniqueClassesFrom(rowsRef.value, (row) => getStudent(row)?.class))

  const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    const cid = classId.value
    return (rowsRef.value || []).filter((row) => {
      const st = getStudent(row)
      if (cid && String(st?.class?.id ?? '') !== String(cid)) return false
      if (!q) return true
      const hay = `${st?.name || ''} ${st?.nis || ''}`.toLowerCase()
      return hay.includes(q)
    })
  })

  const lastPage = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)))
  const total = computed(() => filtered.value.length)
  const paged = computed(() => {
    const start = (page.value - 1) * perPage.value
    return filtered.value.slice(start, start + perPage.value)
  })
  const rangeLabel = computed(() => {
    const totalRows = filtered.value.length
    if (!totalRows) return '0 dari 0 siswa'
    const start = (page.value - 1) * perPage.value + 1
    const end = Math.min(page.value * perPage.value, totalRows)
    return `Menampilkan ${start}–${end} dari ${totalRows} siswa`
  })

  function reset() {
    page.value = 1
    search.value = ''
    classId.value = ''
  }

  function goPage(next) {
    page.value = next
  }

  function changePerPage(n) {
    perPage.value = n
    page.value = 1
  }

  watch([search, classId], () => {
    page.value = 1
  })
  watch(lastPage, (last) => {
    if (page.value > last) page.value = last
  })

  return reactive({
    page,
    perPage,
    search,
    classId,
    classOptions,
    filtered,
    lastPage,
    total,
    paged,
    rangeLabel,
    reset,
    goPage,
    changePerPage,
  })
}

const attendancePager = useRosterPager(attendances, (a) => a.student)
const gradingPager = useRosterPager(sessionGrades, (g) => g.student)
const attendancePageGroups = computed(() => groupByClass(
  attendancePager.paged,
  (a) => a.student?.class,
).map((group) => ({
  ...group,
  start: (attendancePager.page - 1) * attendancePager.perPage + group.start,
})))
const gradingPageGroups = computed(() => groupByClass(
  gradingPager.paged,
  (g) => g.student?.class,
).map((group) => ({
  ...group,
  start: (gradingPager.page - 1) * gradingPager.perPage + group.start,
})))
const participantGroups = computed(() => groupByClass(participants.value, (p) => p.student?.class))

const grades = ref([])
const gradeSessions = ref([])
const gradesKkm = ref(null)
const gradesLoading = ref(false)
const savingGrades = ref(false)
const gradesPager = useRosterPager(grades, (g) => g.student)
const gradePageGroups = computed(() => groupByClass(
  gradesPager.paged,
  (g) => g.student?.class,
).map((group) => ({
  ...group,
  start: (gradesPager.page - 1) * gradesPager.perPage + group.start,
})))

const memTargets = ref([])
const memProgress = ref([])
const memLoading = ref(false)
const memSavingTarget = ref(false)
const memPager = useRosterPager(memProgress, (r) => r.student)
const memPageGroups = computed(() => groupByClass(
  memPager.paged,
  (r) => r.student?.class,
).map((group) => ({
  ...group,
  start: (memPager.page - 1) * memPager.perPage + group.start,
})))
const memSelectedStudentId = ref(null)
const memDetail = ref(null)
const memDetailLoading = ref(false)
const memChecklist = ref(null)
const memChecklistSurah = ref(null)
const memDraftAyahs = ref(new Set())
const memSavingChecklist = ref(false)

const report = ref(null)
const reportLoading = ref(false)
const exportingCsv = ref(false)
const printingPdf = ref(false)
const reportClassId = ref('')
const filteredAttendanceRows = computed(() => {
  const rows = report.value?.attendance_matrix?.rows || []
  if (!reportClassId.value) return rows
  return rows.filter((r) => String(r.class?.id ?? '') === String(reportClassId.value))
})
const filteredGradeRows = computed(() => {
  const rows = report.value?.grade_matrix?.rows || []
  if (!reportClassId.value) return rows
  return rows.filter((r) => String(r.class?.id ?? '') === String(reportClassId.value))
})
const filteredPerStudentRows = computed(() => {
  const rows = report.value?.per_student || []
  if (!reportClassId.value) return rows
  return rows.filter((r) => String(r.class?.id ?? '') === String(reportClassId.value))
})
const reportClassOptions = computed(() => uniqueClassesFrom(
  report.value?.attendance_matrix?.rows || report.value?.grade_matrix?.rows || report.value?.per_student || [],
  (r) => r.class,
))
const reportAttendanceGroups = computed(() => groupByClass(filteredAttendanceRows.value, (r) => r.class))
const reportGradeGroups = computed(() => groupByClass(filteredGradeRows.value, (r) => r.class))
const reportPerStudentGroups = computed(() => groupByClass(filteredPerStudentRows.value, (r) => r.class))

const now = new Date()
const reportFilters = ref({
  period: 'semester',
  semester_id: '',
  year: now.getFullYear(),
  month: now.getMonth() + 1,
})

const periodOptions = [
  { value: 'semester', label: 'Per Semester' },
  { value: 'year', label: 'Per Tahun' },
  { value: 'month', label: 'Per Bulan' },
]

const monthOptions = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
]

const yearOptions = computed(() => {
  const y = now.getFullYear()
  return [y + 1, y, y - 1, y - 2, y - 3]
})

function reportParams() {
  const f = reportFilters.value
  const params = { period: f.period }
  if (f.period === 'semester') {
    if (f.semester_id) params.semester_id = f.semester_id
  } else if (f.period === 'year') {
    params.year = f.year
  } else if (f.period === 'month') {
    params.year = f.year
    params.month = f.month
  }
  return params
}

function setPeriod(period) {
  reportFilters.value.period = period
  loadReport()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
function statusLabel(s) {
  return ({ hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpha: 'Alpha' })[s] || s
}
function matrixStatus(row, sessionId) {
  if (!row?.statuses) return null
  return row.statuses[String(sessionId)] ?? row.statuses[sessionId] ?? null
}
function attCode(status) {
  if (!status) return '—'
  return ({ hadir: 'H', izin: 'I', sakit: 'S', alpha: 'A' })[status] || String(status).charAt(0).toUpperCase()
}
function attCodeClass(status) {
  if (!status) return 'att-empty'
  return `att-${status}`
}
function sessionMonthLabel(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { month: 'short' })
}
function sessionColTitle(s) {
  const date = formatDate(s.session_date)
  return s.topic ? `${date} · ${s.topic}` : date
}
function formatDateShort(d) {
  if (!d) return '—'
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
function predicateFromScore(score, kkm = 75) {
  if (score == null || score === '') return ''
  const n = Number(score)
  const k = Number(kkm)
  if (Number.isNaN(n)) return ''
  if (n < k) return 'D'
  const band = (100 - k) / 3
  if (band <= 0) return 'A'
  if (n >= k + 2 * band) return 'A'
  if (n >= k + band) return 'B'
  return 'C'
}
function onSessionScoreInput(g) {
  if (g.score_locked) {
    g.score = null
    g.predicate = ''
    return
  }
  if (g.score === '' || g.score == null) {
    g.predicate = ''
    return
  }
  g.predicate = predicateFromScore(g.score, gradingKkm.value)
}

async function loadItem() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await extracurricularApi.get(id.value)
    item.value = res.data.data || res.data
    canSetKkm.value = !!res.data.meta_access?.can_set_kkm
    kkmDraft.value = item.value?.kkm != null ? Number(item.value.kkm) : 75
  } catch (e) {
    loadError.value = e.formattedMessage || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

async function saveKkm() {
  if (!canSetKkm.value) return
  const value = kkmDraft.value
  if (value === '' || value == null || Number(value) < 0 || Number(value) > 100) {
    toast.error('Gagal', 'KKM harus antara 0–100')
    return
  }
  savingKkm.value = true
  try {
    const res = await extracurricularApi.update(id.value, { kkm: Number(value) })
    item.value = res.data.data || res.data
    kkmDraft.value = item.value?.kkm != null ? Number(item.value.kkm) : Number(value)
    gradingKkm.value = kkmDraft.value
    gradesKkm.value = kkmDraft.value
    toast.success('Berhasil', 'KKM disimpan. Predikat dihitung ulang.')
    if (tab.value === 'nilai') await loadGrades()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan KKM')
  } finally {
    savingKkm.value = false
  }
}

async function loadPeserta() {
  pesertaLoading.value = true
  try {
    const res = await extracurricularApi.getStudents(id.value)
    const raw = res.data?.data ?? res.data
    participants.value = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : [])
  } catch (e) {
    participants.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat peserta')
  } finally {
    pesertaLoading.value = false
  }
}

async function loadClasses() {
  try {
    const res = await extracurricularApi.classesLite()
    classes.value = res.data.data || []
  } catch (e) {
    classes.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat daftar kelas')
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res.data.data || []
  } catch {
    semesters.value = []
  }
}

function onClassChange() {
  selectedIds.value = []
  availableStudents.value = []
  availableSearch.value = ''
  if (availableClassId.value) loadAvailable()
}

async function loadAvailable() {
  if (!availableClassId.value) return
  availableLoading.value = true
  try {
    const params = { class_id: availableClassId.value, per_page: 200 }
    if (availableSearch.value) params.search = availableSearch.value
    const res = await extracurricularApi.getAvailableStudents(id.value, params)
    const data = res.data?.data ?? res.data
    availableStudents.value = Array.isArray(data) ? data : []
  } catch (e) {
    availableStudents.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat siswa')
  } finally {
    availableLoading.value = false
  }
}

let availDebounce
function debounceAvailable() {
  clearTimeout(availDebounce)
  availDebounce = setTimeout(loadAvailable, 350)
}

const allSelected = computed(() =>
  availableStudents.value.length > 0 && selectedIds.value.length === availableStudents.value.length
)
function toggleAll() {
  selectedIds.value = allSelected.value ? [] : availableStudents.value.map((s) => s.id)
}
function toggleStudent(sid) {
  const i = selectedIds.value.indexOf(sid)
  if (i >= 0) selectedIds.value.splice(i, 1)
  else selectedIds.value.push(sid)
}

async function submitPeserta() {
  if (!selectedIds.value.length) return
  savingPeserta.value = true
  try {
    const res = await extracurricularApi.addStudents(id.value, {
      student_ids: selectedIds.value.map((sid) => Number(sid)).filter((sid) => Number.isInteger(sid) && sid > 0),
    })
    toast.success('Berhasil', res.data?.message || 'Peserta ditambahkan')
    showAddPeserta.value = false
    selectedIds.value = []
    availableClassId.value = ''
    availableStudents.value = []
    await loadPeserta()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menambah peserta')
  } finally {
    savingPeserta.value = false
  }
}

function removePeserta(p) {
  showConfirm({
    title: 'Keluarkan Peserta',
    message: `Keluarkan ${p.student?.name || 'siswa ini'} dari ekskul?`,
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.removeStudent(id.value, p.student_id)
      toast.success('Berhasil', 'Peserta dikeluarkan')
      await loadPeserta()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || 'Gagal mengeluarkan')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function loadSessions() {
  sessionsLoading.value = true
  try {
    const res = await extracurricularApi.getSessions(id.value)
    sessions.value = res.data.data || []
  } catch (e) {
    sessions.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat pertemuan')
  } finally {
    sessionsLoading.value = false
  }
}

async function submitSession() {
  savingSession.value = true
  try {
    const payload = { ...sessionForm.value }
    if (!payload.start_time) payload.start_time = null
    if (!payload.end_time) payload.end_time = null
    await extracurricularApi.createSession(id.value, payload)
    toast.success('Berhasil', 'Pertemuan ditambahkan')
    showSessionForm.value = false
    sessionForm.value = {
      session_date: new Date().toISOString().slice(0, 10),
      start_time: '',
      end_time: '',
      topic: '',
      notes: '',
      with_attendance: true,
    }
    await loadSessions()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan pertemuan')
  } finally {
    savingSession.value = false
  }
}

function deleteSession(s) {
  showConfirm({
    title: 'Hapus Pertemuan',
    message: `Hapus pertemuan ${formatDate(s.session_date)}? Data kehadiran terkait ikut terhapus.`,
  }).then(async (ok) => {
    if (!ok) return
    setDeleteLoading(true)
    try {
      await extracurricularApi.deleteSession(id.value, s.id)
      toast.success('Berhasil', 'Pertemuan dihapus')
      if (attendanceSession.value?.id === s.id) attendanceSession.value = null
      if (gradingSession.value?.id === s.id) gradingSession.value = null
      await loadSessions()
    } catch (e) {
      toast.error('Gagal', e.formattedMessage || 'Gagal menghapus')
    } finally {
      setDeleteLoading(false)
    }
  })
}

async function openAttendance(s) {
  gradingSession.value = null
  attendanceSession.value = s
  attendancePager.reset()
  attendanceLoading.value = true
  try {
    const res = await extracurricularApi.getAttendances(id.value, s.id)
    attendances.value = res.data.data || []
    if (res.data.statuses) attendanceStatuses.value = res.data.statuses
  } catch (e) {
    attendances.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat kehadiran')
  } finally {
    attendanceLoading.value = false
  }
}

function markAttendancePage(status) {
  for (const row of attendancePager.paged) {
    row.status = status
  }
}

async function saveAttendance() {
  savingAttendance.value = true
  try {
    await extracurricularApi.saveAttendances(id.value, attendanceSession.value.id, {
      attendances: attendances.value.map((a) => ({
        student_id: a.student_id,
        status: a.status,
        notes: a.notes || null,
      })),
    })
    toast.success('Berhasil', 'Kehadiran disimpan')
    await loadSessions()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan kehadiran')
  } finally {
    savingAttendance.value = false
  }
}

async function openGrading(s) {
  attendanceSession.value = null
  gradingSession.value = s
  gradingPager.reset()
  gradingLoading.value = true
  try {
    const res = await extracurricularApi.getSessionGrades(id.value, s.id)
    sessionGrades.value = (res.data.data || []).map((g) => ({ ...g }))
    gradingKkm.value = res.data.kkm != null ? Number(res.data.kkm) : 75
  } catch (e) {
    sessionGrades.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat penilaian')
  } finally {
    gradingLoading.value = false
  }
}

async function saveSessionGrades() {
  savingSessionGrades.value = true
  try {
    await extracurricularApi.saveSessionGrades(id.value, gradingSession.value.id, {
      grades: sessionGrades.value.map((g) => ({
        student_id: g.student_id,
        score: g.score_locked || g.score === '' || g.score == null ? null : g.score,
        notes: g.notes || null,
      })),
    })
    toast.success('Berhasil', 'Penilaian disimpan')
    await loadSessions()
    if (tab.value === 'nilai') await loadGrades()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan penilaian')
  } finally {
    savingSessionGrades.value = false
  }
}

async function loadGrades() {
  gradesLoading.value = true
  try {
    const res = await extracurricularApi.getGrades(id.value)
    grades.value = (res.data.data || []).map((g) => ({ ...g }))
    gradeSessions.value = res.data.sessions || []
    gradesKkm.value = res.data.kkm != null ? Number(res.data.kkm) : null
  } catch (e) {
    grades.value = []
    gradeSessions.value = []
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat rekap nilai')
  } finally {
    gradesLoading.value = false
  }
}

async function recalcGrades() {
  savingGrades.value = true
  try {
    await extracurricularApi.saveGrades(id.value, {})
    toast.success('Berhasil', 'Nilai akhir dihitung ulang dari rata-rata pertemuan')
    await loadGrades()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menghitung ulang nilai')
  } finally {
    savingGrades.value = false
  }
}

async function loadReport() {
  reportLoading.value = true
  try {
    const res = await extracurricularApi.getReport(id.value, reportParams())
    report.value = res.data.data || null
    reportClassId.value = ''
  } catch (e) {
    report.value = null
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat laporan')
  } finally {
    reportLoading.value = false
  }
}

async function exportReport() {
  exportingCsv.value = true
  try {
    const res = await extracurricularApi.exportReport(id.value, reportParams())
    const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `laporan-${item.value?.name || 'ekskul'}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(link.href)
    toast.success('Berhasil', 'Laporan CSV diunduh')
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal export CSV')
  } finally {
    exportingCsv.value = false
  }
}

async function printReportPdf() {
  printingPdf.value = true
  try {
    const res = await extracurricularApi.exportReportPdf(id.value, reportParams())
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Laporan — ${item.value?.name || 'Ekskul'}`
    const periodHint = report.value?.period?.label ? ` · ${report.value.period.label}` : ''
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
        .actions { display: flex; gap: 8px; flex-shrink: 0; }
        .actions button {
          border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
          cursor: pointer; font-size: 13px;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-close { background: #334155; color: #e2e8f0; }
        iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
      </style></head><body>
      <div class="toolbar">
        <h1>${title}<span class="hint">Preview cetak${periodHint}</span></h1>
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
    toast.error('Gagal', e.formattedMessage || 'Gagal membuka preview laporan PDF')
  } finally {
    printingPdf.value = false
  }
}

watch(tab, (t) => {
  if (t === 'peserta') loadPeserta()
  if (t === 'pertemuan') loadSessions()
  if (t === 'nilai') loadGrades()
  if (t === 'hafalan') loadMemorization()
  if (t === 'laporan') loadReport()
})

watch(() => route.params.id, async () => {
  await loadItem()
  tab.value = 'peserta'
  closeStudentMem()
  await Promise.all([loadPeserta(), loadClasses(), loadSemesters()])
})

onMounted(async () => {
  await loadItem()
  await Promise.all([loadPeserta(), loadClasses(), loadSemesters()])
})

function scopeLabel(scope) {
  if (scope === 'class') return 'Target kelas'
  if (scope === 'student') return 'Target siswa'
  return 'Target ekskul'
}

function pctClass(pct) {
  const n = Number(pct) || 0
  if (n >= 90) return 'pct-high'
  if (n >= 60) return 'pct-mid'
  return 'pct-low'
}

async function loadMemorization() {
  memLoading.value = true
  try {
    const [tRes, pRes] = await Promise.all([
      extracurricularApi.getMemorizationTargets(id.value),
      extracurricularApi.getMemorizationProgress(id.value),
    ])
    memTargets.value = tRes.data.data || []
    memProgress.value = pRes.data.data || []
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat hapalan')
  } finally {
    memLoading.value = false
  }
}

async function createJuz30Target() {
  memSavingTarget.value = true
  try {
    await extracurricularApi.createMemorizationTarget(id.value, {
      scope: 'extracurricular',
      template: 'juz30',
      name: 'Juz 30',
    })
    toast.success('Berhasil', 'Target Juz 30 dibuat')
    await loadMemorization()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal membuat target')
  } finally {
    memSavingTarget.value = false
  }
}

async function removeTarget(t) {
  const ok = await showConfirm({
    title: 'Hapus target?',
    message: `Hapus target "${t.name || scopeLabel(t.scope)}"? Progres setoran siswa tetap tersimpan.`,
  })
  if (!ok) return
  setDeleteLoading(true)
  try {
    await extracurricularApi.deleteMemorizationTarget(id.value, t.id)
    toast.success('Berhasil', 'Target dihapus')
    await loadMemorization()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menghapus target')
  } finally {
    setDeleteLoading(false)
  }
}

async function openStudentMem(row) {
  memSelectedStudentId.value = row.student.id
  memChecklist.value = null
  memChecklistSurah.value = null
  memDraftAyahs.value = new Set()
  memDetailLoading.value = true
  try {
    const res = await extracurricularApi.getStudentMemorizationProgress(id.value, row.student.id)
    memDetail.value = res.data.data
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat detail siswa')
    memDetail.value = null
  } finally {
    memDetailLoading.value = false
  }
}

function closeStudentMem() {
  memSelectedStudentId.value = null
  memDetail.value = null
  memChecklist.value = null
  memChecklistSurah.value = null
  memDraftAyahs.value = new Set()
}

async function loadChecklist(surahNumber) {
  if (!memSelectedStudentId.value) return
  memChecklistSurah.value = surahNumber
  try {
    const res = await extracurricularApi.getSurahChecklist(id.value, memSelectedStudentId.value, surahNumber)
    memChecklist.value = res.data.data
    memDraftAyahs.value = new Set(
      (memChecklist.value?.ayahs || []).filter((a) => a.deposited).map((a) => a.ayah_number)
    )
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal memuat checklist surat')
  }
}

function toggleAyah(num, checked) {
  const next = new Set(memDraftAyahs.value)
  if (checked) next.add(num)
  else next.delete(num)
  memDraftAyahs.value = next
}

function isAyahChecked(num) {
  return memDraftAyahs.value.has(num)
}

function selectAllAyahs(on) {
  if (!memChecklist.value) return
  if (on) {
    memDraftAyahs.value = new Set(memChecklist.value.ayahs.map((a) => a.ayah_number))
  } else {
    memDraftAyahs.value = new Set()
  }
}

async function saveChecklist() {
  if (!memSelectedStudentId.value || !memChecklistSurah.value) return
  memSavingChecklist.value = true
  try {
    const res = await extracurricularApi.syncSurahChecklist(
      id.value,
      memSelectedStudentId.value,
      memChecklistSurah.value,
      {
        ayah_numbers: [...memDraftAyahs.value].sort((a, b) => a - b),
        replace: true,
      }
    )
    memChecklist.value = res.data.data
    memDraftAyahs.value = new Set(
      (memChecklist.value?.ayahs || []).filter((a) => a.deposited).map((a) => a.ayah_number)
    )
    toast.success('Berhasil', 'Setoran disimpan')
    const progressRes = await extracurricularApi.getStudentMemorizationProgress(id.value, memSelectedStudentId.value)
    memDetail.value = progressRes.data.data
    await loadMemorization()
  } catch (e) {
    toast.error('Gagal', e.formattedMessage || 'Gagal menyimpan checklist')
  } finally {
    memSavingChecklist.value = false
  }
}
</script>

<style scoped>
.ekskul-detail {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 25%, #f1f5f9 100%);
}

.detail-top {
  margin-bottom: 12px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border: none;
  background: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  font-size: 14px;
}

.ekskul-header {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.15rem 1.25rem 1.2rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.header-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.header-info h1 {
  margin: 0;
  font-size: clamp(1.2rem, 2.5vw, 1.55rem);
  color: #0f172a;
  letter-spacing: -0.02em;
}

.header-desc {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.45;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 8px 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.meta-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-width: 0;
}

.meta-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #ecfdf5;
  color: #059669;
}

.meta-body {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.meta-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #94a3b8;
}

.meta-value {
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.35;
  word-break: break-word;
}

.meta-hint {
  font-weight: 500;
  color: #94a3b8;
  font-size: 12px;
}

.meta-chip {
  display: inline-block;
  padding: 4px 10px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 999px;
  font-size: 12px;
  color: #047857;
  font-weight: 500;
}

.kkm-box {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 10px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.kkm-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.kkm-hint {
  flex: 1 1 100%;
  font-size: 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.status-active { background: #c6f6d5; color: #22543d; }
.status-inactive { background: #fed7d7; color: #742a2a; }

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

.sec-btn:hover:not(.active) {
  background: #fff;
  color: #0f172a;
}

.sec-btn:focus-visible {
  outline: 2px solid #059669;
  outline-offset: 1px;
}

.sec-btn.active {
  background: #fff;
  color: #065f46;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 0 0 1px #e2e8f0;
}

.sec-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #ecfdf5;
  color: #059669;
  flex-shrink: 0;
}

.sec-btn.active .sec-icon {
  background: #d1fae5;
  color: #047857;
}

.sec-label {
  font-size: 13.5px;
  font-weight: 600;
  letter-spacing: -0.01em;
  line-height: 1.3;
}

.tab-main {
  min-width: 0;
}

.panel {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 16px 18px 18px;
  box-shadow: none;
}

.panel-toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
}

.panel-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.toolbar-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.table-scroll {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 0 -4px;
  padding: 0 4px;
}

.data-table {
  width: 100%;
  min-width: 560px;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.data-table th {
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #065f46;
  padding: 12px 14px;
  font-weight: 600;
  white-space: nowrap;
}

.data-table td {
  padding: 12px 14px;
  border-top: 1px solid #e2e8f0;
  font-size: 14px;
  color: #334155;
  vertical-align: middle;
}

.data-table tbody tr:hover { background: #f8fafc; }
.data-table-compact { min-width: 420px; }
.row-active { background: #ecfdf5 !important; }

.col-no { width: 48px; text-align: center; color: #94a3b8; }
.col-aksi { width: 132px; text-align: right; white-space: nowrap; }
.action-btns {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
}
.btn-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  padding: 0;
  border-radius: 8px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #047857;
  cursor: pointer;
}
.btn-icon:hover,
.btn-icon.active {
  background: #d1fae5;
  border-color: #059669;
}
.btn-icon.danger {
  color: #b91c1c;
  border-color: #fecaca;
  background: #fef2f2;
}
.btn-icon.danger:hover {
  background: #fee2e2;
  border-color: #dc2626;
}
.col-center { text-align: center; }
.cell-strong { font-weight: 600; color: #0f172a; }
.cell-sub { font-size: 12px; color: #94a3b8; }

.form-row, .form-grid {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.form-grid .form-group { flex: 1; min-width: 140px; }
.form-group { margin-bottom: 10px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: #334155; }
.req { color: #dc2626; }

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-input-sm { padding: 6px 8px; font-size: 13px; }
.input-score { width: 88px; max-width: 100%; }
.input-pred { width: 64px; max-width: 100%; }
.status-select { min-width: 110px; }

.btn-primary, .btn-secondary {
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.2);
}

.btn-primary:disabled, .btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secondary { background: #e2e8f0; color: #334155; }
.btn-sm { padding: 7px 12px; }

.btn-link {
  border: none;
  background: none;
  color: #059669;
  font-weight: 600;
  cursor: pointer;
  font-size: 13px;
  margin-left: 6px;
  padding: 0;
}

.btn-link.danger { color: #dc2626; }

.add-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 14px;
}

.attendance-box { margin-top: 14px; }

.roster-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
}
.roster-toolbar .form-input {
  flex: 1;
  min-width: 140px;
  max-width: 260px;
}
.roster-meta {
  margin: 0 0 10px;
  font-size: 12px;
  color: #64748b;
}

.pagination-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin: 10px 0 12px;
}
.pagination-info { font-size: 12px; color: #64748b; }
.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-page {
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
}
.btn-page:disabled { opacity: 0.45; cursor: not-allowed; }
.page-num { font-size: 12px; color: #475569; font-weight: 600; }

.group-row td {
  background: #f1f5f9;
  font-weight: 700;
  font-size: 12px;
  color: #334155;
  letter-spacing: 0.02em;
  padding-top: 8px;
  padding-bottom: 8px;
}
.matrix-table tbody .group-row .sticky-col,
.matrix-table tbody .group-row td {
  background: #f1f5f9;
}

.check-all, .check-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  margin-bottom: 6px;
  cursor: pointer;
}

.check-name { font-weight: 500; color: #0f172a; }
.check-row .muted { margin-left: auto; }

.available-scroll {
  max-height: 240px;
  overflow-y: auto;
  margin-bottom: 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  background: #fff;
}

.muted { color: #94a3b8; font-size: 14px; }
.pad-sm { padding: 12px 0; }
.empty-inline {
  padding: 28px 12px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.loading-wrap, .error-state {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 48px 24px;
  text-align: center;
  color: #64748b;
}

.error-state { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.error-state p { margin: 0 0 16px; }

.loading-spinner {
  width: 28px;
  height: 28px;
  border: 2px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Laporan */
.report-filters {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
  padding: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.period-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.period-btn {
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
}

.period-btn.active {
  background: #ecfdf5;
  border-color: #059669;
  color: #047857;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-input {
  max-width: 240px;
  min-width: 140px;
  flex: 1;
}

.period-label {
  margin: 0 0 14px;
  font-size: 13px;
  color: #64748b;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px;
  margin-bottom: 18px;
}

.stat-card {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 12px;
  text-align: center;
}

.stat-val { font-size: 20px; font-weight: 700; color: #0f172a; }
.stat-label { font-size: 11px; color: #64748b; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.03em; }

.report-section { margin-bottom: 20px; }

.report-section-title {
  margin: 0 0 8px;
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.title-meta {
  font-weight: 500;
  text-transform: none;
  color: #64748b;
  letter-spacing: 0;
}

.matrix-hint {
  margin: 0 0 10px;
  font-size: 12px;
  color: #64748b;
}

.matrix-scroll {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.matrix-table {
  min-width: 640px;
}

.matrix-table th.col-session {
  min-width: 42px;
  padding: 8px 6px;
  vertical-align: bottom;
}

.session-day {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #065f46;
  line-height: 1.2;
}

.session-month {
  display: block;
  font-size: 10px;
  font-weight: 500;
  color: #94a3b8;
  text-transform: none;
  letter-spacing: 0;
}

.att-code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
}

.att-hadir { background: #d1fae5; color: #047857; }
.att-izin { background: #ffedd5; color: #c2410c; }
.att-sakit { background: #dbeafe; color: #1d4ed8; }
.att-alpha { background: #fee2e2; color: #dc2626; }
.att-empty { color: #cbd5e1; font-weight: 500; }

.sticky-col {
  position: sticky;
  left: 0;
  background: #fff;
  z-index: 1;
}

.matrix-table thead .sticky-col {
  background: #d1fae5;
  z-index: 2;
}

.sticky-name {
  left: 48px;
  min-width: 120px;
}

.matrix-table tbody tr:hover .sticky-col {
  background: #f8fafc;
}

@media (max-width: 768px) {
  .tab-shell { grid-template-columns: 1fr; min-height: 0; }
  .section-nav {
    flex-direction: row;
    overflow-x: auto;
    border-right: none;
    border-bottom: 1px solid #eef2f7;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .section-nav::-webkit-scrollbar { display: none; }
  .sec-btn { width: auto; flex: 1 0 auto; }
  .panel { padding: 12px; }
  .report-toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-actions { width: 100%; }
  .toolbar-actions .btn-sm { flex: 1; justify-content: center; }
  .filter-input { max-width: none; width: 100%; }
  .filter-controls { flex-direction: column; }
  .data-table { min-width: 520px; }
  .data-table th, .data-table td { padding: 10px 12px; }
  .sticky-col { position: static; }
  .sticky-name { left: auto; }
  .roster-toolbar .form-input { max-width: none; }
  .meta-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 480px) {
  .period-btn { flex: 1 1 calc(33% - 6px); text-align: center; font-size: 12px; padding: 7px 8px; }
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
}

.mem-targets {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}
.mem-target-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 13px;
}
.mem-pct { font-weight: 700; }
.pct-high { color: #047857; }
.pct-mid { color: #b45309; }
.pct-low { color: #b91c1c; }
.row-clickable { cursor: pointer; }
.row-clickable:hover { background: #f0fdf4; }
.mem-detail { margin-top: 16px; }
.mem-surah-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
  max-height: 180px;
  overflow-y: auto;
}
.mem-surah-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 13px;
}
.mem-surah-btn.active {
  border-color: #059669;
  background: #ecfdf5;
}
.mem-surah-num {
  font-weight: 700;
  color: #047857;
  min-width: 1.5rem;
}
.mem-surah-name { font-weight: 600; color: #0f172a; }
.mem-surah-prog { color: #64748b; font-size: 12px; }
.ayah-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
  gap: 6px;
  max-height: 360px;
  overflow-y: auto;
  padding: 4px 0;
}
.ayah-check {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 8px 4px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 12px;
  text-align: center;
}
.ayah-check.on {
  border-color: #059669;
  background: #ecfdf5;
}
.ayah-check input { accent-color: #059669; }
.ayah-num { font-weight: 700; color: #334155; }
.ayah-ar {
  font-size: 11px;
  color: #64748b;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
