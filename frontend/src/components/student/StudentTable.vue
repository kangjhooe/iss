<template>
  <div class="student-table-wrap">
    <!-- Desktop: table -->
    <div v-if="students.length > 0" class="table-container table-desktop">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'nik')">
                NIK
                <span class="sort-icon" :class="sortClass('nik')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'nis')">
                NIS
                <span class="sort-icon" :class="sortClass('nis')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'nisn')">
                NISN
                <span class="sort-icon" :class="sortClass('nisn')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'name')">
                Nama
                <span class="sort-icon" :class="sortClass('name')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'gender')">
                Jenis Kelamin
                <span class="sort-icon" :class="sortClass('gender')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'tingkat')">
                Tingkat
                <span class="sort-icon" :class="sortClass('tingkat')" aria-hidden="true"></span>
              </button>
            </th>
            <th>
              <button type="button" class="th-sort" @click="$emit('sort', 'class')">
                Kelas
                <span class="sort-icon" :class="sortClass('class')" aria-hidden="true"></span>
              </button>
            </th>
            <th v-if="showStatus">
              <button type="button" class="th-sort" @click="$emit('sort', 'status')">
                Status
                <span class="sort-icon" :class="sortClass('status')" aria-hidden="true"></span>
              </button>
            </th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(student, index) in students" :key="student.id">
            <td class="col-no">{{ startIndex + index + 1 }}</td>
            <td>{{ displayValue(student.nik) }}</td>
            <td>{{ displayValue(student.nis) }}</td>
            <td>{{ displayValue(student.nisn) }}</td>
            <td>
              <div class="name-cell">
                <img v-if="student.photo_url" :src="student.photo_url" class="name-photo" :alt="student.name" />
                <span>{{ displayValue(student.name) }}</span>
              </div>
            </td>
            <td>{{ student.gender === 'L' ? 'Laki-laki' : student.gender === 'P' ? 'Perempuan' : 'Belum ada data' }}</td>
            <td>{{ displayValue(student.tingkat) }}</td>
            <td>{{ displayClassName(student) }}</td>
            <td v-if="showStatus">
              <span :class="getStatusClass(student.status)">
                {{ student.status || 'Belum ada data' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <template v-if="trashMode">
                  <button type="button" @click="$emit('restore', student)" class="btn-action btn-restore" title="Pulihkan">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 10H21M7 15H17M12 4V20M4 10L12 4L20 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button type="button" @click="$emit('force-delete', student)" class="btn-action btn-delete" title="Hapus permanen">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </template>
                <template v-else-if="archiveMode">
                  <button type="button" @click="$emit('view', student)" class="btn-action btn-view" title="Lihat arsip">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button type="button" @click="$emit('reactivate', student)" class="btn-action btn-restore" title="Aktifkan kembali">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </template>
                <template v-else>
                <button @click="$emit('view', student)" class="btn-action btn-view" title="Lihat Biodata">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <button @click="$emit('edit', student)" class="btn-action btn-edit" title="Edit">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <button @click="$emit('delete', student.id)" class="btn-action btn-delete" title="Hapus">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile: cards -->
    <div v-if="students.length > 0" class="student-cards table-mobile">
      <div v-for="student in students" :key="student.id" class="student-card">
        <div class="student-card-main">
          <div class="student-card-head">
            <img v-if="student.photo_url" :src="student.photo_url" class="name-photo" :alt="student.name" />
            <h3 class="student-card-name">{{ student.name }}</h3>
          </div>
          <div class="student-card-meta">
            <span v-if="student.nis || student.nisn" class="student-card-id">
              {{ student.nis ? `NIS: ${student.nis}` : '' }}{{ student.nis && student.nisn ? ' · ' : '' }}{{ student.nisn ? `NISN: ${student.nisn}` : '' }}
            </span>
            <span v-else class="student-card-id">NIK: {{ displayValue(student.nik) }}</span>
            <span class="student-card-class">
              Tingkat: {{ displayValue(student.tingkat) }} · Kelas: {{ displayClassName(student) }}
            </span>
          </div>
          <span v-if="showStatus" :class="['student-card-status', getStatusClass(student.status)]">
            {{ student.status || 'Belum ada data' }}
          </span>
        </div>
        <div class="student-card-actions">
          <template v-if="trashMode">
            <button type="button" @click="$emit('restore', student)" class="btn-action btn-restore" title="Pulihkan">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 10H21M7 15H17M12 4V20M4 10L12 4L20 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button type="button" @click="$emit('force-delete', student)" class="btn-action btn-delete" title="Hapus permanen">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </template>
          <template v-else-if="archiveMode">
            <button type="button" @click="$emit('view', student)" class="btn-action btn-view" title="Lihat arsip">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button type="button" @click="$emit('reactivate', student)" class="btn-action btn-restore" title="Aktifkan kembali">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </template>
          <template v-else>
          <button @click="$emit('view', student)" class="btn-action btn-view" title="Lihat">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button @click="$emit('edit', student)" class="btn-action btn-edit" title="Edit">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button @click="$emit('delete', student.id)" class="btn-action btn-delete" title="Hapus">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Empty state slot when no students -->
    <div v-if="students.length === 0" class="empty-state">
      <slot name="empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3>Belum ada data siswa</h3>
        <p>Mulai dengan menambahkan siswa baru</p>
        <button type="button" @click="$emit('add')" class="btn-primary btn-compact">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Tambah Siswa</span>
        </button>
      </slot>
    </div>
  </div>
</template>

<script setup>
function displayValue(v) {
  if (v === null || v === undefined || v === '') return 'Belum ada data'
  return String(v).trim() || 'Belum ada data'
}

function displayClassName(student) {
  return displayValue(student?.class_detail?.name || student?.class)
}

const props = defineProps({
  students: {
    type: Array,
    default: () => []
  },
  trashMode: {
    type: Boolean,
    default: false
  },
  archiveMode: {
    type: Boolean,
    default: false
  },
  showStatus: {
    type: Boolean,
    default: true
  },
  startIndex: {
    type: Number,
    default: 0
  },
  getStatusClass: {
    type: Function,
    default: () => () => ''
  },
  sortBy: {
    type: String,
    default: 'created_at'
  },
  sortDir: {
    type: String,
    default: 'desc'
  }
})

defineEmits(['view', 'edit', 'delete', 'add', 'restore', 'force-delete', 'reactivate', 'sort'])

function sortClass(column) {
  if (props.sortBy !== column) return 'is-idle'
  return props.sortDir === 'asc' ? 'is-asc' : 'is-desc'
}
</script>

<style scoped>
.student-table-wrap {
  width: 100%;
}

.table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table th,
.data-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  font-weight: 600;
  color: #065f46;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

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
}

.th-sort:hover {
  color: #047857;
}

.sort-icon {
  display: inline-block;
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  opacity: 0.35;
  border-bottom: 5px solid currentColor;
}

.sort-icon.is-idle {
  opacity: 0.25;
}

.sort-icon.is-asc {
  opacity: 1;
  border-bottom: 5px solid currentColor;
  border-top: 0;
}

.sort-icon.is-desc {
  opacity: 1;
  border-bottom: 0;
  border-top: 5px solid currentColor;
}

.data-table .col-no {
  width: 3rem;
  text-align: center;
  white-space: nowrap;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.status-active { color: #10b981; font-weight: 600; }
.status-success { color: #3498db; font-weight: 600; }
.status-warning { color: #f39c12; font-weight: 600; }
.status-danger { color: #e74c3c; font-weight: 600; }
.status-inactive { color: #95a5a6; font-weight: 600; }

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: center;
}

.btn-action {
  padding: 8px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: transparent;
}

.btn-view { color: #059669; }
.btn-view:hover { background: rgba(5, 150, 105, 0.1); }
.btn-edit { color: #059669; }
.btn-edit:hover { background: rgba(5, 150, 105, 0.1); }
.btn-delete { color: #ef4444; }
.btn-delete:hover { background: rgba(239, 68, 68, 0.1); }

.btn-restore { color: #059669; }
.btn-restore:hover { background: rgba(5, 150, 105, 0.15); }

/* Mobile cards */
.student-cards { display: none; }
@media (max-width: 768px) {
  .table-desktop { display: none; }
  .student-cards.table-mobile { display: flex; flex-direction: column; gap: 12px; }
}

.student-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.student-card-main { flex: 1; min-width: 0; }
.student-card-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
.student-card-name { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; }
.name-cell { display: flex; align-items: center; gap: 10px; }
.name-photo {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  object-fit: cover;
  background: #e2e8f0;
  flex-shrink: 0;
}
.student-card-meta { font-size: 13px; color: #64748b; margin-bottom: 8px; }
.student-card-class { display: block; font-size: 12px; color: #94a3b8; }
.student-card-status { font-size: 13px; font-weight: 600; }
.student-card-actions { display: flex; gap: 8px; flex-shrink: 0; }

.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-state svg { color: #cbd5e1; margin-bottom: 8px; }
.empty-state h3 { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0; }
.empty-state p { font-size: 14px; color: #64748b; margin: 0 0 24px 0; }
</style>
