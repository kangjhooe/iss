<template>
  <div class="table-container">
    <table v-if="teachers.length > 0" class="data-table">
      <thead>
        <tr>
          <th>Tipe</th>
          <th>NIK</th>
          <th>NIP</th>
          <th>NUPTK</th>
          <th>Nama</th>
          <th>Jenis Kelamin</th>
          <th>Status Kepegawaian</th>
          <th>Mata Pelajaran</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="teacher in teachers" :key="teacher.id">
          <td>{{ teacher.type || 'Guru' }}</td>
          <td>{{ displayValue(teacher.nik) }}</td>
          <td>{{ displayValue(teacher.nip) }}</td>
          <td>{{ displayValue(teacher.nuptk) }}</td>
          <td>
            <div class="name-cell">
              <span>{{ displayValue(teacher.name) }}</span>
              <span v-if="teacher.affiliation === 'non_induk'" class="badge-non-induk">Non-Induk</span>
            </div>
          </td>
          <td>{{ teacher.gender === 'L' ? 'Laki-laki' : teacher.gender === 'P' ? 'Perempuan' : 'Belum ada data' }}</td>
          <td>{{ displayValue(teacher.employment_status) }}</td>
          <td>{{ getTeacherSubject(teacher) || 'Belum ada data' }}</td>
          <td>
            <span :class="getStatusClass(teacher.status)">
              {{ teacher.status || 'Belum ada data' }}
            </span>
          </td>
          <td>
            <div class="action-buttons">
              <template v-if="trashMode">
                <button @click="$emit('restore', teacher)" class="btn-action btn-restore" title="Pulihkan">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 10H21M7 15H17M12 4V20M4 10L12 4L20 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </template>
              <template v-else>
              <button @click="$emit('view', teacher)" class="btn-action btn-view" title="Lihat Biodata">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="$emit('edit', teacher)" class="btn-action btn-edit" title="Edit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button @click="$emit('delete', teacher.id)" class="btn-action btn-delete" title="Hapus">
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

    <div v-if="teachers.length === 0" class="empty-state">
      <slot name="empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3>Belum ada data guru</h3>
        <p>Mulai dengan menambahkan guru baru</p>
        <button type="button" @click="$emit('add')" class="btn-primary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Tambah Guru</span>
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

defineProps({
  teachers: {
    type: Array,
    default: () => []
  },
  trashMode: {
    type: Boolean,
    default: false
  },
  getTeacherSubject: {
    type: Function,
    required: true
  },
  getStatusClass: {
    type: Function,
    required: true
  }
})

defineEmits(['view', 'edit', 'delete', 'add', 'restore'])
</script>

<style scoped>
.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  min-width: 1020px;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.data-table th {
  padding: 14px 16px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.data-table th:nth-child(1) { width: 72px; }   /* Tipe */
.data-table th:nth-child(2) { width: 120px; } /* NIK */
.data-table th:nth-child(3) { width: 120px; } /* NIP */
.data-table th:nth-child(4) { width: 100px; } /* NUPTK */
.data-table th:nth-child(5) { min-width: 160px; } /* Nama */
.data-table th:nth-child(6) { width: 108px; } /* Jenis Kelamin */
.data-table th:nth-child(7) { min-width: 130px; } /* Status Kepegawaian */
.data-table th:nth-child(8) { min-width: 120px; } /* Mata Pelajaran */
.data-table th:nth-child(9) { width: 88px; }  /* Status */
.data-table th:nth-child(10) { width: 132px; } /* Aksi */

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
  vertical-align: middle;
}

.data-table td:nth-child(5) {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.name-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.name-cell span:first-child {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.badge-non-induk {
  display: inline-flex;
  align-self: flex-start;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fde68a;
  color: #92400e;
  font-size: 11px;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: nowrap;
  justify-content: flex-start;
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
  flex-shrink: 0;
}

.btn-view {
  background: #10b981;
  color: white;
}

.btn-view:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-edit {
  background: #059669;
  color: white;
}

.btn-edit:hover {
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-delete {
  background: #ef4444;
  color: white;
}

.btn-delete:hover {
  background: #dc2626;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-restore {
  background: #059669;
  color: white;
}

.btn-restore:hover {
  background: #047857;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

/* Status badges (classes passed from parent) */
:deep(.status-active) {
  color: #27ae60;
  font-weight: 600;
}

:deep(.status-success) {
  color: #3498db;
  font-weight: 600;
}

:deep(.status-warning) {
  color: #f39c12;
  font-weight: 600;
}

:deep(.status-inactive) {
  color: #95a5a6;
  font-weight: 600;
}

.empty-state {
  padding: 48px 24px;
  text-align: center;
  background: #f8fafc;
  border-radius: 16px;
  border: 2px dashed #e2e8f0;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px;
  font-size: 18px;
  color: #1e293b;
}

.empty-state p {
  margin: 0 0 20px;
  font-size: 14px;
  color: #64748b;
}

.empty-state .btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.empty-state .btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
}

@media (max-width: 768px) {
  .table-container {
    border-radius: 12px;
  }

  .data-table {
    min-width: 900px;
  }

  .data-table th,
  .data-table td {
    padding: 12px 14px;
    font-size: 13px;
  }

  .data-table th {
    font-size: 11px;
  }
}
</style>
