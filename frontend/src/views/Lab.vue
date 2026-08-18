<template>
  <Layout>
    <div class="lab-page">
      <div class="tabs-nav-lab">
        <button
          v-if="!isLabResponsibleOnly"
          type="button"
          :class="['tab-btn-lab', { active: labTab === 'list' }]"
          @click="labTab = 'list'"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Daftar Lab</span>
        </button>
        <button
          v-if="!isLabResponsibleOnly"
          type="button"
          :class="['tab-btn-lab', { active: labTab === 'report' }]"
          @click="switchToReport"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 17V7M13 17V7M17 17V7M5 17V7M3 21H21M3 3H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Laporan Lab</span>
        </button>
        <button type="button" :class="['tab-btn-lab', { active: labTab === 'mylabs' }]" @click="switchToMyLabs">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>{{ isLabResponsibleOnly ? 'Lab Saya' : 'Dashboard Saya' }}</span>
        </button>
      </div>

      <div v-show="labTab === 'list'" class="tab-panel">
        <div class="tab-header">
          <div class="filters filters-inline">
            <input
              v-model="search"
              @input="debounceLoad"
              placeholder="Cari nama atau kode lab..."
              class="search-input"
            />
            <select v-model="buildingId" @change="loadLabs" class="filter-select">
              <option value="">Semua Gedung</option>
              <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <select v-model="labType" @change="loadLabs" class="filter-select">
              <option value="">Semua Jenis Lab</option>
              <option value="IPA">Lab IPA</option>
              <option value="Komputer">Lab Komputer</option>
              <option value="Bahasa">Lab Bahasa</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="tab-header-actions">
            <button v-if="isSchoolAdmin" type="button" class="btn-primary btn-compact" @click="openLabModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Lab</span>
            </button>
            <router-link v-if="hasFacilityModule" to="/facility" class="btn-secondary btn-compact">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Kelola Sarana Prasarana</span>
            </router-link>
          </div>
        </div>

      <div v-if="loading" class="loading-state">
        <div class="loading-spinner">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
              <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
              <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
            </circle>
          </svg>
        </div>
        <p>Memuat data lab...</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 160px;" class="th-expand" title="Klik untuk melihat inventaris barang dan jadwal penggunaan lab">Inventaris & Jadwal</th>
              <th>Nama Ruang</th>
              <th>Kode</th>
              <th>Jenis Lab</th>
              <th>Gedung</th>
              <th>Lantai</th>
              <th>Penanggung Jawab (Kepala Lab)</th>
              <th>Kondisi</th>
              <th style="width: 120px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="room in labs" :key="room.id">
              <tr>
                <td class="td-expand">
                  <button type="button" class="btn-expand" :aria-expanded="expandedRoomId === room.id" @click="toggleInventory(room)" :title="expandedRoomId === room.id ? 'Tutup detail' : 'Lihat inventaris barang & jadwal lab ' + room.name">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" :class="{ expanded: expandedRoomId === room.id }">
                      <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="btn-expand-label">{{ expandedRoomId === room.id ? 'Tutup detail' : 'Lihat detail' }}</span>
                  </button>
                </td>
                <td>{{ room.name }}</td>
              <td>{{ displayValue(room.code) }}</td>
              <td>{{ labTypeLabel(room.lab_type) }}</td>
              <td>{{ displayValue(room.building?.name) }}</td>
              <td>{{ room.floor }}</td>
              <td>
                <select
                  v-if="isSchoolAdmin"
                  :value="room.responsible_employee_id || ''"
                  @focus="ensureEmployees"
                  @change="(e) => updateResponsible(room, e.target.value)"
                  class="responsible-select"
                  :disabled="savingId === room.id"
                >
                  <option value="">— Pilih penanggung jawab —</option>
                  <option
                    v-if="room.responsible_employee_id && !employees.some(e => e.id === room.responsible_employee_id)"
                    :value="room.responsible_employee_id"
                  >
                    {{ room.responsible_employee?.name || 'Penanggung jawab saat ini' }}
                  </option>
                  <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                    {{ emp.name }}{{ emp.nip ? ' (' + emp.nip + ')' : '' }}
                  </option>
                </select>
                <span v-else>{{ room.responsible_employee?.name || 'Belum ditetapkan' }}</span>
                <span v-if="savingId === room.id" class="saving-label">Menyimpan...</span>
              </td>
              <td><span :class="getConditionClass(room.condition)">{{ room.condition }}</span></td>
              <td class="actions-cell">
                <router-link :to="`/lab/${room.id}`" class="btn-action btn-edit" title="Kelola lab">Kelola</router-link>
                <template v-if="isSchoolAdmin">
                  <button type="button" class="btn-action btn-edit" @click="openLabModal(room)" title="Edit lab">Edit</button>
                  <button type="button" class="btn-action btn-delete" @click="confirmDeleteLab(room)" title="Hapus lab">Hapus</button>
                </template>
              </td>
              </tr>
              <tr v-if="expandedRoomId === room.id" class="inventory-detail-row">
                <td colspan="9" class="inventory-detail-cell">
                  <div class="expanded-detail-card">
                    <div class="expanded-detail-card-header">
                      <h4 class="expanded-detail-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Detail Lab: {{ room.name }}
                        <span v-if="room.code" class="expanded-detail-code">({{ room.code }})</span>
                      </h4>
                      <div style="display:flex;gap:0.5rem;align-items:center;">
                        <router-link :to="`/lab/${room.id}`" class="btn-sm btn-primary">Buka workspace</router-link>
                        <button type="button" class="btn-expand-inline" @click="toggleInventory(room)" title="Tutup panel ini">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 15L12 9L6 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                          Tutup
                        </button>
                      </div>
                    </div>
                    <p class="expanded-detail-desc">Kelola inventaris barang dan jadwal penggunaan lab ini di bawah.</p>

                    <div class="detail-section detail-panel">
                      <div class="inventory-detail-header">
                        <span class="detail-panel-title">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 7L12 3L4 7M20 7L12 11M20 7V17L12 21M12 11L4 7M12 11V21M4 7V17L12 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                          Inventaris Lab
                        </span>
                        <button type="button" class="btn-sm btn-primary" @click="openItemModal(room)">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          Tambah Barang
                        </button>
                      </div>
                      <div v-if="getRoomInventory(room.id).loading" class="inventory-loading">Memuat inventaris...</div>
                      <div v-else-if="getRoomInventory(room.id).items.length === 0" class="inventory-empty">Tidak ada barang di lab ini. Klik <strong>Tambah Barang</strong> untuk menambah inventaris.</div>
                      <table v-else class="inventory-subtable">
                        <thead>
                          <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Qty</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th style="width: 100px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in getRoomInventory(room.id).items" :key="item.id">
                            <td>{{ displayValue(item.code) }}</td>
                            <td>{{ item.name }}</td>
                            <td>{{ displayValue(item.category?.name) }}</td>
                            <td>{{ item.quantity }} {{ item.unit || '' }}</td>
                            <td>{{ displayValue(item.condition) }}</td>
                            <td>{{ displayValue(item.status) }}</td>
                            <td>
                              <button type="button" class="btn-action btn-edit btn-xs" @click="openItemModal(room, item)">Edit</button>
                              <button type="button" class="btn-action btn-delete btn-xs" @click="confirmDeleteItem(room, item)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <div class="detail-section detail-panel">
                      <div class="inventory-detail-header">
                        <span class="detail-panel-title">
                          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                          Jadwal Penggunaan Lab
                        </span>
                        <button type="button" class="btn-sm btn-primary" @click="openScheduleModal(room)" :disabled="!activeSemesterId">
                          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          Tambah Jadwal
                        </button>
                      </div>
                      <div v-if="getRoomSchedule(room.id).loading" class="inventory-loading">Memuat jadwal...</div>
                      <div v-else-if="!activeSemesterId" class="inventory-empty">Pilih semester aktif di profil instansi untuk menampilkan dan mengelola jadwal.</div>
                      <div v-else-if="getRoomSchedule(room.id).items.length === 0" class="inventory-empty">Belum ada jadwal di lab ini. Klik <strong>Tambah Jadwal</strong> untuk menambah slot.</div>
                      <table v-else class="inventory-subtable">
                        <thead>
                          <tr>
                            <th>Hari</th>
                            <th>Jam ke</th>
                            <th>Waktu</th>
                            <th>Mapel</th>
                            <th>Kelas</th>
                            <th>Guru</th>
                            <th style="width: 100px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="s in getRoomSchedule(room.id).items" :key="s.id">
                            <td>{{ displayValue(s.day_name) }}</td>
                            <td>{{ s.period }}</td>
                            <td>{{ displayValue(s.start_time) }}-{{ displayValue(s.end_time) }}</td>
                            <td>{{ displayValue(s.subject?.name) }}</td>
                            <td>{{ displayValue(s.school_class?.name) }}</td>
                            <td>{{ displayValue(s.employee?.name) }}</td>
                            <td>
                              <button type="button" class="btn-action btn-edit btn-xs" @click="openScheduleModal(room, s)">Edit</button>
                              <button type="button" class="btn-action btn-delete btn-xs" @click="confirmDeleteSchedule(room, s)">Hapus</button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
        <div v-if="labs.length === 0" class="empty-state">
          <svg class="empty-state-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p>Belum ada ruang laboratorium. Klik <strong>Tambah Lab</strong> atau kelola via Sarana Prasarana.</p>
          <div class="empty-state-actions">
            <button type="button" class="btn-primary" @click="openLabModal()">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span>Tambah Lab</span>
            </button>
            <router-link v-if="hasFacilityModule" to="/facility" class="btn-secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Ke Sarana Prasarana</span>
            </router-link>
          </div>
        </div>
      </div>
      </div>

      <div v-show="labTab === 'report'" class="tab-panel">
        <div class="tab-header" style="margin-bottom: 1rem;">
          <div></div>
          <div class="tab-header-actions">
            <button type="button" class="btn-secondary btn-compact" @click="exportAllLabsPdf">Cetak PDF</button>
          </div>
        </div>
        <div v-if="labReportLoading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat laporan lab...</p>
        </div>
        <div v-else-if="labReportData" class="report-lab">
          <div class="report-summary-cards">
            <div class="report-card">
              <span class="report-card-value">{{ labReportData.summary?.total_labs ?? 0 }}</span>
              <span class="report-card-label">Total Lab</span>
            </div>
            <div class="report-card">
              <span class="report-card-value">{{ labReportData.summary?.total_damaged_items ?? 0 }}</span>
              <span class="report-card-label">Barang Rusak</span>
            </div>
            <div class="report-card">
              <span class="report-card-value">{{ labReportData.summary?.total_open_maintenance ?? 0 }}</span>
              <span class="report-card-label">Perawatan Terbuka</span>
            </div>
            <div v-for="(count, cond) in labReportData.summary?.by_condition" :key="'cond-' + cond" class="report-card">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">Kondisi {{ cond }}</span>
            </div>
          </div>
          <div class="report-summary-cards">
            <div v-for="(count, type) in labReportData.summary?.by_lab_type" :key="'type-' + type" class="report-card report-card-small">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">{{ labTypeLabel(type) }}</span>
            </div>
          </div>
          <table class="data-table report-table">
            <thead>
              <tr>
                <th>Nama Lab</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th>Kondisi</th>
                <th>Penanggung Jawab</th>
                <th>Jumlah Barang</th>
                <th>Rusak</th>
                <th>Jadwal (semester aktif)</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lab in labReportData.labs" :key="lab.id">
                <td>{{ lab.name }}</td>
                <td>{{ labTypeLabel(lab.lab_type) }}</td>
                <td>{{ displayValue(lab.building?.name) }}</td>
                <td><span :class="getConditionClass(lab.condition)">{{ lab.condition }}</span></td>
                <td>{{ displayValue(lab.responsible_employee?.name) }}</td>
                <td>{{ lab.inventory_count }}</td>
                <td>{{ lab.damaged_count ?? 0 }}</td>
                <td>{{ lab.schedule_count }}</td>
                <td><router-link :to="`/lab/${lab.id}`" class="btn-action btn-edit">Kelola</router-link></td>
              </tr>
            </tbody>
          </table>
          <div v-if="!labReportData.labs?.length" class="empty-state">
            <svg class="empty-state-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 17V7M13 17V7M17 17V7M5 17V7M3 21H21M3 3H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>Belum ada data lab.</p>
          </div>
        </div>
      </div>

      <div v-show="labTab === 'mylabs'" class="tab-panel">
        <div v-if="myLabsLoading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat dashboard lab...</p>
        </div>
        <div v-else-if="myLabsData" class="report-lab">
          <div class="report-summary-cards">
            <div class="report-card">
              <span class="report-card-value">{{ myLabsData.summary?.total ?? 0 }}</span>
              <span class="report-card-label">Lab yang saya tanggung jawabi</span>
            </div>
            <div class="report-card report-card-small">
              <span class="report-card-value">{{ myLabsData.summary?.total_pending_bookings ?? 0 }}</span>
              <span class="report-card-label">Booking menunggu</span>
            </div>
            <div class="report-card report-card-small">
              <span class="report-card-value">{{ myLabsData.summary?.total_active_loans ?? 0 }}</span>
              <span class="report-card-label">Peminjaman aktif</span>
            </div>
            <div class="report-card report-card-small">
              <span class="report-card-value">{{ myLabsData.summary?.total_damaged ?? 0 }}</span>
              <span class="report-card-label">Barang rusak</span>
            </div>
            <div v-for="(count, cond) in myLabsData.summary?.by_condition" :key="'my-cond-' + cond" class="report-card report-card-small">
              <span class="report-card-value">{{ count }}</span>
              <span class="report-card-label">{{ cond }}</span>
            </div>
          </div>
          <div v-if="!myLabsData.labs?.length" class="empty-state">
            <svg class="empty-state-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>Anda belum ditetapkan sebagai penanggung jawab (Kepala Lab) untuk ruang lab manapun. Minta admin menetapkan Anda di Manajemen Lab atau Sarana Prasarana.</p>
            <router-link v-if="hasFacilityModule" to="/facility" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Ke Sarana Prasarana</span>
            </router-link>
          </div>
          <table v-else class="data-table report-table">
            <thead>
              <tr>
                <th>Nama Lab</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th>Kondisi</th>
                <th>Barang</th>
                <th>Rusak</th>
                <th>Pinjam</th>
                <th>Booking</th>
                <th>Jadwal hari ini</th>
                <th>Pemakaian minggu ini</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lab in myLabsData.labs" :key="lab.id">
                <td>{{ lab.name }}</td>
                <td>{{ labTypeLabel(lab.lab_type) }}</td>
                <td>{{ displayValue(lab.building?.name) }}</td>
                <td><span :class="getConditionClass(lab.condition)">{{ lab.condition }}</span></td>
                <td>{{ lab.inventory_count }}</td>
                <td>{{ lab.damaged_count ?? 0 }}</td>
                <td>{{ lab.active_loans ?? 0 }}</td>
                <td>{{ lab.pending_bookings ?? 0 }}</td>
                <td>{{ lab.today_schedule_count ?? 0 }}</td>
                <td>{{ lab.week_usage_count ?? 0 }}</td>
                <td>
                  <router-link :to="`/lab/${lab.id}`" class="btn-action btn-edit">Kelola</router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Lab -->
      <div v-if="showLabModal" class="modal-overlay" @click.self="closeLabModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingLab ? 'Edit Lab' : 'Tambah Lab' }}</h3>
            <button type="button" class="btn-close" @click="closeLabModal" aria-label="Tutup">×</button>
          </div>
          <form @submit.prevent="saveLab" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Ruang <span class="required">*</span></label>
                <input v-model="labForm.name" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Kode</label>
                <input v-model="labForm.code" class="form-input" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Gedung</label>
                <select v-model="labForm.building_id" class="form-input">
                  <option value="">Pilih Gedung</option>
                  <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jenis Lab</label>
                <select v-model="labForm.lab_type" class="form-input">
                  <option value="">— Pilih —</option>
                  <option value="IPA">Lab IPA</option>
                  <option value="Komputer">Lab Komputer</option>
                  <option value="Bahasa">Lab Bahasa</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Lantai <span class="required">*</span></label>
                <input type="number" v-model.number="labForm.floor" min="1" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Luas (m²)</label>
                <input type="number" v-model.number="labForm.area" step="0.01" min="0" class="form-input" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" v-model.number="labForm.capacity" min="0" class="form-input" />
              </div>
              <div class="form-group">
                <label>Kondisi <span class="required">*</span></label>
                <select v-model="labForm.condition" required class="form-input">
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Sedang">Rusak Sedang</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Penanggung Jawab (Kepala Lab)</label>
              <select v-model="labForm.responsible_employee_id" class="form-input">
                <option value="">— Tidak ada —</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}{{ emp.nip ? ' (' + emp.nip + ')' : '' }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="labForm.description" rows="2" class="form-input"></textarea>
            </div>
            <p v-if="labFormError" class="form-error">{{ labFormError }}</p>
            <div class="modal-footer">
              <button type="button" @click="closeLabModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="labSaving" class="btn-primary">{{ labSaving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Konfirmasi Hapus Lab -->
      <div v-if="showDeleteLabModal" class="modal-overlay" @click.self="cancelDeleteLab">
        <div class="modal-content modal-narrow" @click.stop>
          <div class="modal-header">
            <h3>Hapus Lab</h3>
            <button type="button" class="btn-close" @click="cancelDeleteLab">×</button>
          </div>
          <div class="modal-body">
            <p>Yakin ingin menghapus lab <strong>{{ deleteLabName }}</strong>? Ruangan akan dihapus dari data sarana prasarana.</p>
          </div>
          <div class="modal-footer">
            <button type="button" @click="cancelDeleteLab" class="btn-secondary">Batal</button>
            <button type="button" @click="doDeleteLab" :disabled="deleteLabLoading" class="btn-danger">{{ deleteLabLoading ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Barang Inventaris -->
      <div v-if="showItemModal" class="modal-overlay" @click.self="closeItemModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingItem ? 'Edit Barang' : 'Tambah Barang ke Lab' }}</h3>
            <button type="button" class="btn-close" @click="closeItemModal">×</button>
          </div>
          <form @submit.prevent="saveItem" class="modal-body">
            <div class="form-group">
              <label>Nama Barang <span class="required">*</span></label>
              <input v-model="itemForm.name" required class="form-input" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Kode</label>
                <input v-model="itemForm.code" class="form-input" placeholder="Opsional" />
              </div>
              <div class="form-group">
                <label>Kategori <span class="required">*</span></label>
                <select v-model="itemForm.category_id" required class="form-input">
                  <option value="">— Pilih —</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Jumlah <span class="required">*</span></label>
                <input type="number" v-model.number="itemForm.quantity" min="1" required class="form-input" />
              </div>
              <div class="form-group">
                <label>Satuan</label>
                <input v-model="itemForm.unit" class="form-input" placeholder="Unit, pcs, dll" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Kondisi</label>
                <select v-model="itemForm.condition" class="form-input">
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                  <option value="Habis Pakai">Habis Pakai</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="itemForm.status" class="form-input">
                  <option value="Tersedia">Tersedia</option>
                  <option value="Dipinjam">Dipinjam</option>
                  <option value="Rusak">Rusak</option>
                  <option value="Hilang">Hilang</option>
                </select>
              </div>
            </div>
            <p v-if="itemFormError" class="form-error">{{ itemFormError }}</p>
            <div class="modal-footer">
              <button type="button" @click="closeItemModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="itemSaving" class="btn-primary">{{ itemSaving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah/Edit Jadwal Lab -->
      <div v-if="showScheduleModal" class="modal-overlay" @click.self="closeScheduleModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal di Lab' }}</h3>
            <button type="button" class="btn-close" @click="closeScheduleModal">×</button>
          </div>
          <form @submit.prevent="saveSchedule" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Semester <span class="required">*</span></label>
                <select v-model="scheduleForm.semester_id" required class="form-input">
                  <option value="">Pilih</option>
                  <option v-for="s in semesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Kelas <span class="required">*</span></label>
                <select v-model="scheduleForm.class_id" required class="form-input">
                  <option value="">Pilih</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Hari <span class="required">*</span></label>
                <select v-model.number="scheduleForm.day_of_week" required class="form-input">
                  <option v-for="(label, key) in dayNamesMap" :key="key" :value="Number(key)">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jam ke <span class="required">*</span></label>
                <select v-model.number="scheduleForm.period" required class="form-input">
                  <option v-for="p in 10" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Mata Pelajaran <span class="required">*</span></label>
              <select v-model="scheduleForm.subject_id" required class="form-input">
                <option value="">Pilih</option>
                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Guru <span class="required">*</span></label>
              <select v-model="scheduleForm.employee_id" required class="form-input">
                <option value="">Pilih</option>
                <option v-for="emp in teachers" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
              </select>
            </div>
            <p v-if="scheduleFormError" class="form-error">{{ scheduleFormError }}</p>
            <div class="modal-footer">
              <button type="button" @click="closeScheduleModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="scheduleSaving" class="btn-primary">{{ scheduleSaving ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Konfirmasi Hapus Barang -->
      <div v-if="showDeleteItemModal" class="modal-overlay" @click.self="cancelDeleteItem">
        <div class="modal-content modal-narrow" @click.stop>
          <div class="modal-header">
            <h3>Hapus Barang</h3>
            <button type="button" class="btn-close" @click="cancelDeleteItem">×</button>
          </div>
          <div class="modal-body">
            <p>Yakin ingin menghapus barang <strong>{{ deleteItemName }}</strong> dari inventaris?</p>
          </div>
          <div class="modal-footer">
            <button type="button" @click="cancelDeleteItem" class="btn-secondary">Batal</button>
            <button type="button" @click="doDeleteItem" :disabled="deleteItemLoading" class="btn-danger">{{ deleteItemLoading ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </div>
      </div>

      <!-- Modal: Konfirmasi Hapus Jadwal -->
      <div v-if="showDeleteScheduleModal" class="modal-overlay" @click.self="cancelDeleteSchedule">
        <div class="modal-content modal-narrow" @click.stop>
          <div class="modal-header">
            <h3>Hapus Jadwal</h3>
            <button type="button" class="btn-close" @click="cancelDeleteSchedule">×</button>
          </div>
          <div class="modal-body">
            <p>Yakin ingin menghapus slot jadwal ini dari lab?</p>
          </div>
          <div class="modal-footer">
            <button type="button" @click="cancelDeleteSchedule" class="btn-secondary">Batal</button>
            <button type="button" @click="doDeleteSchedule" :disabled="deleteScheduleLoading" class="btn-danger">{{ deleteScheduleLoading ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import Layout from '@/components/Layout.vue'
import { facilityApi } from '@/api/facility'
import { employeeApi } from '@/api/teacher'
import { inventoryApi } from '@/api/inventory'
import { lessonScheduleApi } from '@/api/lessonSchedule'
import { institutionApi } from '@/api/institution'
import { semesterApi } from '@/api/semester'
import { classApi } from '@/api/class'
import { subjectApi } from '@/api/subject'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'

const toast = useToast()
const authStore = useAuthStore()
const isSchoolAdmin = computed(() => {
  const r = authStore.user?.role
  return r === 'admin' || r === 'institution_admin' || r === 'super_admin'
})

const hasFacilityModule = computed(() => {
  if (isSchoolAdmin.value) return true
  return (authStore.user?.permissions || []).includes('facility')
})

const isLabResponsibleOnly = computed(() => {
  return !!authStore.user?.is_lab_responsible && !hasFacilityModule.value
})

const dayNamesMap = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat' }

const labs = ref([])
const buildings = ref([])
const employees = ref([])
const loading = ref(false)
const savingId = ref(null)
const search = ref('')
const buildingId = ref('')
const labType = ref('')
const expandedRoomId = ref(null)
const roomInventoryMap = ref({})
const roomScheduleMap = ref({})
const activeSemesterId = ref(null)
const labTab = ref('list')
const labReportData = ref(null)
const labReportLoading = ref(false)
const myLabsData = ref(null)
const myLabsLoading = ref(false)

const semesters = ref([])
const classes = ref([])
const subjects = ref([])
const teachers = ref([])
const categories = ref([])
const employeesLoaded = ref(false)
const categoriesLoaded = ref(false)
const scheduleLookupsLoaded = ref(false)
let employeesPromise = null
let categoriesPromise = null
let scheduleLookupsPromise = null

const showLabModal = ref(false)
const editingLab = ref(null)
const labForm = reactive({
  name: '',
  code: '',
  building_id: '',
  lab_type: '',
  floor: 1,
  area: '',
  capacity: '',
  condition: 'Baik',
  description: '',
  responsible_employee_id: ''
})
const labFormError = ref('')
const labSaving = ref(false)

const showDeleteLabModal = ref(false)
const deleteLabId = ref(null)
const deleteLabName = ref('')
const deleteLabLoading = ref(false)

const showItemModal = ref(false)
const itemModalRoom = ref(null)
const editingItem = ref(null)
const itemForm = reactive({
  name: '',
  code: '',
  category_id: '',
  quantity: 1,
  unit: '',
  condition: 'Baik',
  status: 'Tersedia'
})
const itemFormError = ref('')
const itemSaving = ref(false)

const showDeleteItemModal = ref(false)
const deleteItemRoom = ref(null)
const deleteItemId = ref(null)
const deleteItemName = ref('')
const deleteItemLoading = ref(false)

const showScheduleModal = ref(false)
const scheduleModalRoom = ref(null)
const editingSchedule = ref(null)
const scheduleForm = reactive({
  semester_id: '',
  class_id: '',
  day_of_week: 1,
  period: 1,
  subject_id: '',
  employee_id: ''
})
const scheduleFormError = ref('')
const scheduleSaving = ref(false)

const showDeleteScheduleModal = ref(false)
const deleteScheduleRoom = ref(null)
const deleteScheduleId = ref(null)
const deleteScheduleLoading = ref(false)

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadLabs(), 300)
}

async function openLabModal(room = null) {
  await Promise.all([loadBuildings(), ensureEmployees()])
  editingLab.value = room
  if (room) {
    labForm.name = room.name || ''
    labForm.code = room.code || ''
    labForm.building_id = room.building_id || ''
    labForm.lab_type = room.lab_type || ''
    labForm.floor = room.floor ?? 1
    labForm.area = room.area ?? ''
    labForm.capacity = room.capacity ?? ''
    labForm.condition = room.condition || 'Baik'
    labForm.description = room.description || ''
    labForm.responsible_employee_id = room.responsible_employee_id || ''
  } else {
    labForm.name = ''
    labForm.code = ''
    labForm.building_id = ''
    labForm.lab_type = ''
    labForm.floor = 1
    labForm.area = ''
    labForm.capacity = ''
    labForm.condition = 'Baik'
    labForm.description = ''
    labForm.responsible_employee_id = ''
  }
  labFormError.value = ''
  showLabModal.value = true
}

function closeLabModal() {
  showLabModal.value = false
  editingLab.value = null
  labFormError.value = ''
}

async function saveLab() {
  labFormError.value = ''
  labSaving.value = true
  try {
    const payload = {
      name: labForm.name,
      code: labForm.code || null,
      building_id: labForm.building_id || null,
      type: 'Laboratorium',
      lab_type: labForm.lab_type || null,
      floor: labForm.floor,
      area: labForm.area !== '' ? labForm.area : null,
      capacity: labForm.capacity !== '' ? labForm.capacity : null,
      condition: labForm.condition,
      description: labForm.description || null,
      responsible_employee_id: labForm.responsible_employee_id ? Number(labForm.responsible_employee_id) : null
    }
    if (editingLab.value) {
      await facilityApi.updateRoom(editingLab.value.id, payload)
      toast.success('Berhasil', 'Data lab berhasil diperbarui')
    } else {
      await facilityApi.createRoom(payload)
      toast.success('Berhasil', 'Data lab berhasil ditambahkan')
    }
    closeLabModal()
    await loadLabs()
    if (labReportData.value) {
      const res = await facilityApi.getLabReport()
      labReportData.value = res?.data?.data ?? res?.data ?? null
    }
    if (myLabsData.value) {
      const res = await facilityApi.getMyLabs()
      myLabsData.value = res?.data?.data ?? res?.data ?? null
    }
  } catch (e) {
    labFormError.value = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error('Gagal', labFormError.value)
  } finally {
    labSaving.value = false
  }
}

function confirmDeleteLab(room) {
  deleteLabId.value = room.id
  deleteLabName.value = room.name || 'Lab'
  showDeleteLabModal.value = true
}

function cancelDeleteLab() {
  showDeleteLabModal.value = false
  deleteLabId.value = null
  deleteLabName.value = ''
}

async function doDeleteLab() {
  if (!deleteLabId.value) return
  deleteLabLoading.value = true
  try {
    await facilityApi.deleteRoom(deleteLabId.value)
    toast.success('Berhasil', 'Lab berhasil dihapus')
    cancelDeleteLab()
    if (expandedRoomId.value === deleteLabId.value) expandedRoomId.value = null
    await loadLabs()
    if (labReportData.value) {
      const res = await facilityApi.getLabReport()
      labReportData.value = res?.data?.data ?? res?.data ?? null
    }
    if (myLabsData.value) {
      const res = await facilityApi.getMyLabs()
      myLabsData.value = res?.data?.data ?? res?.data ?? null
    }
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal menghapus lab')
  } finally {
    deleteLabLoading.value = false
  }
}

async function openItemModal(room, item = null) {
  await ensureCategories()
  itemModalRoom.value = room
  editingItem.value = item
  const defaultCat = categories.value.find(c => String(c.code || '').toUpperCase() === 'LAB' || /lab/i.test(c.name || ''))
  if (item) {
    itemForm.name = item.name || ''
    itemForm.code = item.code || ''
    itemForm.category_id = item.category_id || item.category?.id || defaultCat?.id || ''
    itemForm.quantity = item.quantity ?? 1
    itemForm.unit = item.unit || 'Unit'
    itemForm.condition = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Habis Pakai'].includes(item.condition) ? item.condition : 'Baik'
    itemForm.status = ['Tersedia', 'Dipinjam', 'Rusak', 'Hilang', 'Dijual'].includes(item.status) ? item.status : 'Tersedia'
  } else {
    itemForm.name = ''
    itemForm.code = ''
    itemForm.category_id = defaultCat?.id || ''
    itemForm.quantity = 1
    itemForm.unit = 'Unit'
    itemForm.condition = 'Baik'
    itemForm.status = 'Tersedia'
  }
  itemFormError.value = ''
  showItemModal.value = true
}

function closeItemModal() {
  showItemModal.value = false
  itemModalRoom.value = null
  editingItem.value = null
  itemFormError.value = ''
}

async function saveItem() {
  if (!itemModalRoom.value) return
  if (!itemForm.category_id) {
    itemFormError.value = 'Kategori wajib dipilih'
    toast.error('Validasi', itemFormError.value)
    return
  }
  itemFormError.value = ''
  itemSaving.value = true
  const roomId = itemModalRoom.value.id
  try {
    const payload = {
      name: itemForm.name,
      category_id: Number(itemForm.category_id),
      quantity: itemForm.quantity,
      unit: itemForm.unit || 'Unit',
      condition: itemForm.condition,
      status: itemForm.status || 'Tersedia',
      room_id: roomId,
      building_id: itemModalRoom.value.building_id || undefined,
    }
    if (itemForm.code) payload.code = itemForm.code
    if (editingItem.value) {
      await inventoryApi.updateItem(editingItem.value.id, payload)
      toast.success('Berhasil', 'Barang berhasil diperbarui')
    } else {
      await inventoryApi.createItem(payload)
      toast.success('Berhasil', 'Barang berhasil ditambahkan ke lab')
    }
    closeItemModal()
    const res = await inventoryApi.getItems({ room_id: roomId, per_page: 100 })
    const data = res?.data
    const items = data?.data ?? (Array.isArray(data) ? data : [])
    roomInventoryMap.value = { ...roomInventoryMap.value, [roomId]: { items, loading: false, loaded: true } }
  } catch (e) {
    itemFormError.value = e.response?.data?.message
      || (e.response?.data?.errors && Object.values(e.response.data.errors).flat()[0])
      || e.message || 'Gagal menyimpan'
    toast.error('Gagal', itemFormError.value)
  } finally {
    itemSaving.value = false
  }
}

function confirmDeleteItem(room, item) {
  deleteItemRoom.value = room
  deleteItemId.value = item.id
  deleteItemName.value = item.name || 'Barang'
  showDeleteItemModal.value = true
}

function cancelDeleteItem() {
  showDeleteItemModal.value = false
  deleteItemRoom.value = null
  deleteItemId.value = null
  deleteItemName.value = ''
}

async function doDeleteItem() {
  if (!deleteItemId.value || !deleteItemRoom.value) return
  deleteItemLoading.value = true
  const roomId = deleteItemRoom.value.id
  try {
    await inventoryApi.deleteItem(deleteItemId.value)
    toast.success('Berhasil', 'Barang dihapus dari inventaris')
    cancelDeleteItem()
    const res = await inventoryApi.getItems({ room_id: roomId, per_page: 100 })
    const data = res?.data
    const items = data?.data ?? (Array.isArray(data) ? data : [])
    roomInventoryMap.value = { ...roomInventoryMap.value, [roomId]: { items, loading: false, loaded: true } }
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal menghapus')
  } finally {
    deleteItemLoading.value = false
  }
}

async function openScheduleModal(room, schedule = null) {
  await ensureScheduleLookups()
  scheduleModalRoom.value = room
  editingSchedule.value = schedule
  if (schedule) {
    scheduleForm.semester_id = schedule.semester_id || activeSemesterId.value || ''
    scheduleForm.class_id = schedule.class_id || schedule.school_class?.id || ''
    scheduleForm.day_of_week = schedule.day_of_week ?? 1
    scheduleForm.period = schedule.period ?? 1
    scheduleForm.subject_id = schedule.subject_id || schedule.subject?.id || ''
    scheduleForm.employee_id = schedule.employee_id || schedule.employee?.id || ''
  } else {
    scheduleForm.semester_id = activeSemesterId.value || ''
    scheduleForm.class_id = ''
    scheduleForm.day_of_week = 1
    scheduleForm.period = 1
    scheduleForm.subject_id = ''
    scheduleForm.employee_id = ''
  }
  scheduleFormError.value = ''
  showScheduleModal.value = true
}

function closeScheduleModal() {
  showScheduleModal.value = false
  scheduleModalRoom.value = null
  editingSchedule.value = null
  scheduleFormError.value = ''
}

async function saveSchedule() {
  if (!scheduleModalRoom.value) return
  scheduleFormError.value = ''
  scheduleSaving.value = true
  const roomId = scheduleModalRoom.value.id
  try {
    const payload = {
      semester_id: Number(scheduleForm.semester_id),
      class_id: Number(scheduleForm.class_id),
      day_of_week: Number(scheduleForm.day_of_week),
      period: Number(scheduleForm.period),
      subject_id: Number(scheduleForm.subject_id),
      employee_id: Number(scheduleForm.employee_id),
      room_id: roomId
    }
    if (editingSchedule.value) {
      await lessonScheduleApi.update(editingSchedule.value.id, payload)
      toast.success('Berhasil', 'Jadwal berhasil diperbarui')
    } else {
      await lessonScheduleApi.create(payload)
      toast.success('Berhasil', 'Jadwal berhasil ditambahkan')
    }
    closeScheduleModal()
    if (activeSemesterId.value) {
      const res = await lessonScheduleApi.getByRoom(roomId, { semester_id: activeSemesterId.value })
      const items = res?.data?.data ?? (Array.isArray(res?.data) ? res.data : [])
      roomScheduleMap.value = { ...roomScheduleMap.value, [roomId]: { items, loading: false, loaded: true } }
    }
  } catch (e) {
    scheduleFormError.value = e.response?.data?.message || e.message || 'Gagal menyimpan'
    toast.error('Gagal', scheduleFormError.value)
  } finally {
    scheduleSaving.value = false
  }
}

function confirmDeleteSchedule(room, schedule) {
  deleteScheduleRoom.value = room
  deleteScheduleId.value = schedule.id
  showDeleteScheduleModal.value = true
}

function cancelDeleteSchedule() {
  showDeleteScheduleModal.value = false
  deleteScheduleRoom.value = null
  deleteScheduleId.value = null
}

async function doDeleteSchedule() {
  if (!deleteScheduleId.value || !deleteScheduleRoom.value) return
  deleteScheduleLoading.value = true
  const roomId = deleteScheduleRoom.value.id
  try {
    await lessonScheduleApi.delete(deleteScheduleId.value)
    toast.success('Berhasil', 'Jadwal dihapus')
    cancelDeleteSchedule()
    if (activeSemesterId.value) {
      const res = await lessonScheduleApi.getByRoom(roomId, { semester_id: activeSemesterId.value })
      const items = res?.data?.data ?? (Array.isArray(res?.data) ? res.data : [])
      roomScheduleMap.value = { ...roomScheduleMap.value, [roomId]: { items, loading: false, loaded: true } }
    }
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal menghapus')
  } finally {
    deleteScheduleLoading.value = false
  }
}

async function loadLabs() {
  loading.value = true
  try {
    const params = { type: 'Laboratorium' }
    if (search.value) params.search = search.value
    if (buildingId.value) params.building_id = buildingId.value
    if (labType.value) params.lab_type = labType.value
    const res = await facilityApi.getRooms(params)
    labs.value = res?.data?.data ?? res?.data ?? []
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat data lab')
    labs.value = []
  } finally {
    loading.value = false
  }
}

async function loadBuildings() {
  if (buildings.value.length) return
  try {
    const res = await facilityApi.getBuildings({})
    buildings.value = res?.data?.data ?? res?.data ?? []
  } catch {
    buildings.value = []
  }
}

async function ensureEmployees() {
  if (employeesLoaded.value) return
  if (employeesPromise) return employeesPromise
  employeesPromise = (async () => {
    try {
      const res = await employeeApi.getAll({ per_page: 500 })
      const list = res?.data?.data ?? res?.data ?? []
      employees.value = Array.isArray(list) ? list : (list?.data ?? [])
    } catch {
      employees.value = []
    } finally {
      employeesLoaded.value = true
      employeesPromise = null
    }
  })()
  return employeesPromise
}

async function ensureCategories() {
  if (categoriesLoaded.value) return
  if (categoriesPromise) return categoriesPromise
  categoriesPromise = (async () => {
    try {
      const res = await inventoryApi.getCategories({ per_page: 200 })
      categories.value = res?.data?.data ?? res?.data ?? []
    } catch {
      categories.value = []
    } finally {
      categoriesLoaded.value = true
      categoriesPromise = null
    }
  })()
  return categoriesPromise
}

async function ensureScheduleLookups() {
  if (scheduleLookupsLoaded.value) return
  if (scheduleLookupsPromise) return scheduleLookupsPromise
  scheduleLookupsPromise = Promise.all([
    loadSemesters(),
    loadClasses(),
    loadSubjects(),
    loadTeachers(),
  ]).finally(() => {
    scheduleLookupsLoaded.value = true
    scheduleLookupsPromise = null
  })
  return scheduleLookupsPromise
}

async function updateResponsible(room, employeeId) {
  const value = employeeId ? Number(employeeId) : null
  savingId.value = room.id
  try {
    await facilityApi.updateRoom(room.id, {
      building_id: room.building_id || '',
      name: room.name,
      code: room.code || '',
      type: room.type,
      lab_type: room.lab_type || null,
      floor: room.floor,
      area: room.area ?? '',
      capacity: room.capacity ?? '',
      condition: room.condition,
      description: room.description || '',
      responsible_employee_id: value
    })
    room.responsible_employee_id = value
    if (value) {
      const emp = employees.value.find(e => e.id === value)
      room.responsible_employee = emp ? { id: emp.id, name: emp.name, nip: emp.nip, nuptk: emp.nuptk } : null
    } else {
      room.responsible_employee = null
    }
    toast.success('Berhasil', 'Penanggung jawab berhasil diperbarui')
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memperbarui penanggung jawab')
  } finally {
    savingId.value = null
  }
}

function labTypeLabel(key) {
  const labels = { IPA: 'Lab IPA', Komputer: 'Lab Komputer', Bahasa: 'Lab Bahasa', Lainnya: 'Lainnya' }
  return labels[key] || key || 'Belum ada data'
}

function displayValue(val) {
  if (val === undefined || val === null) return 'Belum ada data'
  const s = String(val).trim()
  return s === '' ? 'Belum ada data' : val
}

function getRoomInventory(roomId) {
  const m = roomInventoryMap.value[roomId]
  return m || { items: [], loading: false, loaded: false }
}

function getRoomSchedule(roomId) {
  const m = roomScheduleMap.value[roomId]
  return m || { items: [], loading: false, loaded: false }
}

async function toggleInventory(room) {
  const id = room.id
  if (expandedRoomId.value === id) {
    expandedRoomId.value = null
    return
  }
  expandedRoomId.value = id
  const inv = roomInventoryMap.value[id]
  if (!inv?.loaded) {
    roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items: [], loading: true, loaded: false } }
    try {
      const res = await inventoryApi.getItems({ room_id: id, per_page: 100 })
      const data = res?.data
      const items = data?.data ?? (Array.isArray(data) ? data : [])
      roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items, loading: false, loaded: true } }
    } catch {
      roomInventoryMap.value = { ...roomInventoryMap.value, [id]: { items: [], loading: false, loaded: true } }
    }
  }
  const sched = roomScheduleMap.value[id]
  if (!sched?.loaded && activeSemesterId.value) {
    roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items: [], loading: true, loaded: false } }
    try {
      const res = await lessonScheduleApi.getByRoom(id, { semester_id: activeSemesterId.value })
      const items = res?.data?.data ?? (Array.isArray(res?.data) ? res.data : [])
      roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items, loading: false, loaded: true } }
    } catch {
      roomScheduleMap.value = { ...roomScheduleMap.value, [id]: { items: [], loading: false, loaded: true } }
    }
  }
}

function getConditionClass(condition) {
  if (!condition) return ''
  const c = condition.toLowerCase().replace(/\s/g, '-')
  return `condition-${c}`
}

function formatNumber(n) {
  if (n == null || n === '') return '-'
  return Number(n).toLocaleString('id-ID')
}

async function switchToReport() {
  labTab.value = 'report'
  if (labReportData.value) return
  labReportLoading.value = true
  try {
    const res = await facilityApi.getLabReport()
    labReportData.value = res?.data?.data ?? res?.data ?? null
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat laporan lab')
    labReportData.value = { summary: {}, labs: [] }
  } finally {
    labReportLoading.value = false
  }
}

async function switchToMyLabs() {
  labTab.value = 'mylabs'
  if (myLabsData.value) return
  myLabsLoading.value = true
  try {
    const res = await facilityApi.getMyLabs()
    myLabsData.value = res?.data?.data ?? res?.data ?? null
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || e.message || 'Gagal memuat data lab saya')
    myLabsData.value = { summary: { total: 0 }, labs: [] }
  } finally {
    myLabsLoading.value = false
  }
}

onMounted(async () => {
  try {
    const instRes = await institutionApi.getMy()
    const inst = instRes?.data?.data ?? instRes?.data
    if (inst?.active_semester_id) activeSemesterId.value = inst.active_semester_id
  } catch {}

  if (isLabResponsibleOnly.value) {
    await switchToMyLabs()
    return
  }

  await loadBuildings()
  await loadLabs()
})

async function loadTeachers() {
  try {
    const res = await employeeApi.getAll({ per_page: 500, type: 'Guru' })
    let list = res?.data?.data ?? res?.data ?? []
    if (!Array.isArray(list)) list = list?.data ?? []
    if (!list.length) {
      const res2 = await employeeApi.getAll({ per_page: 500 })
      list = res2?.data?.data ?? res2?.data ?? []
      if (!Array.isArray(list)) list = list?.data ?? []
    }
    teachers.value = list
  } catch {
    teachers.value = []
  }
}

async function exportAllLabsPdf() {
  try {
    const res = await facilityApi.exportLabReport()
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview.')
      URL.revokeObjectURL(url)
      return
    }
    win.document.write(`<!DOCTYPE html><html><head><title>Preview Rekap Lab</title>
      <style>
        body{margin:0;font-family:system-ui,sans-serif;background:#0f172a}
        .toolbar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;color:#f8fafc;border-bottom:1px solid #1e293b}
        .toolbar h1{margin:0;font-size:14px}
        .actions button{border:none;border-radius:8px;padding:8px 14px;font-weight:600;cursor:pointer}
        .btn-print{background:#059669;color:#fff}
        .btn-close{background:#334155;color:#e2e8f0;margin-left:8px}
        iframe{width:100%;height:calc(100vh - 52px);border:0;background:#525659}
      </style></head><body>
      <div class="toolbar">
        <h1>Preview Rekapitulasi Lab</h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus();document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
  } catch (e) {
    toast.error('Gagal', 'Gagal membuka preview laporan PDF')
  }
}

async function loadSemesters() {
  try {
    const res = await semesterApi.getAll({ per_page: 100 })
    semesters.value = res?.data?.data ?? res?.data ?? []
  } catch {
    semesters.value = []
  }
}

async function loadClasses() {
  try {
    const res = await classApi.getAll({ per_page: 200 })
    classes.value = res?.data?.data ?? res?.data ?? []
  } catch {
    classes.value = []
  }
}

async function loadSubjects() {
  try {
    const res = await subjectApi.getAll({ per_page: 200 })
    const raw = res?.data?.data ?? res?.data ?? []
    subjects.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
  } catch {
    subjects.value = []
  }
}
</script>

<style scoped>
.lab-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  padding: 0;
  margin: 0;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.tab-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.tab-header-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.filters-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  flex: 1;
  min-width: 0;
}

.search-input {
  min-width: 200px;
  padding: 0.6rem 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 14px;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #059669;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.filter-select {
  padding: 0.6rem 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 14px;
  transition: all 0.2s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.responsible-select {
  min-width: 220px;
  padding: 0.4rem 0.6rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

.responsible-select:focus {
  outline: none;
  border-color: #059669;
}

.saving-label {
  margin-left: 0.5rem;
  font-size: 0.85rem;
  color: #64748b;
}

.loading-state {
  text-align: center;
  padding: 2.5rem;
  color: #64748b;
}

.loading-spinner {
  margin-bottom: 0.5rem;
  color: #059669;
}

.loading-spinner svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.table-container {
  overflow-x: auto;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.data-table th {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}
.condition-baik { color: #16a34a; }
.condition-rusak-ringan { color: #ca8a04; }
.condition-rusak-sedang { color: #ea580c; }
.condition-rusak-berat { color: #dc2626; }
.btn-expand {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: #64748b;
  border-radius: 4px;
}
.btn-expand:hover { color: #059669; }
.btn-expand svg { display: block; transition: transform 0.2s; }
.btn-expand svg.expanded { transform: rotate(90deg); }
.inventory-detail-row { background: #f8fafc; }
.inventory-detail-cell { padding: 0; vertical-align: top; border-bottom: 1px solid #e5e7eb; }
.expanded-detail-card {
  margin: 0.75rem 1rem 1rem 2.5rem;
  padding: 1.25rem;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  border-left: 4px solid #059669;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.expanded-detail-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 600;
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.expanded-detail-title svg { color: #059669; flex-shrink: 0; }
.expanded-detail-code { font-weight: 500; color: #64748b; font-size: 0.95rem; }
.detail-panel-title svg { color: #059669; }
.inventory-subtable th { background: #f0fdf4; font-weight: 600; }
.tabs-nav-lab {
  display: flex;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.tab-btn-lab {
  padding: 0.6rem 1.25rem;
  border: 1px solid #e5e7eb;
  background: #fff;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 500;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.tab-btn-lab:hover {
  border-color: #a7f3d0;
  background: #f8fafc;
  color: #059669;
}

.tab-btn-lab.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border-color: #059669;
}

.tab-panel { margin-top: 0; }

.expanded-detail-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.expanded-detail-desc {
  margin: 0 0 1.25rem;
  font-size: 0.875rem;
  color: #64748b;
}

.btn-expand-inline {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
}

.btn-expand-inline:hover { background: #e2e8f0; color: #0f172a; }

.detail-section.detail-panel {
  margin-bottom: 1.25rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
}

.detail-panel:last-child { margin-bottom: 0; }

.detail-panel-title {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #0f172a;
}

.inventory-detail-header { font-weight: 600; margin-bottom: 0.75rem; font-size: 0.9rem; }

.inventory-loading, .inventory-empty { color: #64748b; font-size: 0.9rem; padding: 0.5rem 0; }

.inventory-subtable { width: 100%; font-size: 0.85rem; border-collapse: collapse; }

.inventory-subtable th, .inventory-subtable td { padding: 0.4rem 0.6rem; text-align: left; border: 1px solid #e5e7eb; }

.empty-state {
  text-align: center;
  padding: 2.5rem;
  color: #64748b;
}

.empty-state-icon {
  display: block;
  margin: 0 auto 1rem;
  opacity: 0.6;
  color: #94a3b8;
}

.empty-state p {
  margin-bottom: 1rem;
  color: #64748b;
}

.empty-state-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
}

.report-lab { padding: 0.5rem 0; }
.report-summary-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.report-card {
  min-width: 120px;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.report-card-value { display: block; font-size: 1.5rem; font-weight: 700; color: #059669; }
.report-card-label { font-size: 0.85rem; color: #64748b; }
.report-card-small { min-width: 90px; padding: 0.75rem; }
.report-card-small .report-card-value { font-size: 1.2rem; }
.report-table { margin-top: 1rem; }
.report-table thead th {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}

.btn-secondary {
  background: #fff;
  color: #475569;
  border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.actions-cell {
  white-space: nowrap;
}
.btn-action {
  padding: 0.35rem 0.6rem;
  font-size: 0.8rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  margin-right: 0.25rem;
}
.btn-action.btn-edit {
  background: #e0f2fe;
  color: #0369a1;
}
.btn-action.btn-edit:hover {
  background: #bae6fd;
}
.btn-action.btn-delete {
  background: #fee2e2;
  color: #b91c1c;
}
.btn-action.btn-delete:hover {
  background: #fecaca;
}
.btn-xs { padding: 0.2rem 0.4rem; font-size: 0.75rem; }
.btn-sm {
  padding: 0.35rem 0.6rem;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 500;
}
.btn-sm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.detail-section { margin-bottom: 0.5rem; }
.inventory-detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
  font-size: 0.9rem;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-content {
  background: #fff;
  border-radius: 12px;
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}
.modal-content.modal-narrow { max-width: 400px; }
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  color: #64748b;
  padding: 0 0.25rem;
}
.btn-close:hover { color: #1e293b; }
.modal-body {
  padding: 1.25rem;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.form-group {
  margin-bottom: 0.75rem;
}
.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  color: #374151;
}
.form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.9rem;
}
.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}
.required { color: #dc2626; }
.form-error {
  color: #dc2626;
  font-size: 0.85rem;
  margin: 0.5rem 0 0;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #e5e7eb;
  margin: 0 -1.25rem -1.25rem;
  padding: 1rem 1.25rem;
}
.btn-danger {
  background: #dc2626;
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  font-weight: 500;
  cursor: pointer;
}
.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}
.btn-danger:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    max-height: 92vh;
    border-radius: 16px 16px 0 0;
  }

  .modal-footer {
    flex-direction: column-reverse;
    gap: 0.5rem;
  }

  .modal-footer .btn-primary,
  .modal-footer .btn-secondary,
  .modal-footer .btn-danger {
    width: 100%;
    justify-content: center;
  }
}
</style>
