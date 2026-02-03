<template>
  <div class="student-table-wrap">
    <!-- Desktop: table -->
    <div v-if="students.length > 0" class="table-container table-desktop">
      <table class="data-table">
        <thead>
          <tr>
            <th>NIK</th>
            <th>NIS</th>
            <th>NISN</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="student in students" :key="student.id">
            <td>{{ student.nik || '-' }}</td>
            <td>{{ student.nis || '-' }}</td>
            <td>{{ student.nisn || '-' }}</td>
            <td>{{ student.name }}</td>
            <td>{{ student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td>{{ student.class || '-' }}</td>
            <td>
              <span :class="getStatusClass(student.status)">
                {{ student.status }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
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
          <h3 class="student-card-name">{{ student.name }}</h3>
          <div class="student-card-meta">
            <span v-if="student.nis || student.nisn" class="student-card-id">
              {{ student.nis ? `NIS: ${student.nis}` : '' }}{{ student.nis && student.nisn ? ' · ' : '' }}{{ student.nisn ? `NISN: ${student.nisn}` : '' }}
            </span>
            <span v-else class="student-card-id">NIK: {{ student.nik || '-' }}</span>
            <span class="student-card-class">{{ student.class || '-' }}</span>
          </div>
          <span :class="['student-card-status', getStatusClass(student.status)]">
            {{ student.status }}
          </span>
        </div>
        <div class="student-card-actions">
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
        <h3>Tidak ada data siswa</h3>
        <p>Mulai dengan menambahkan siswa baru</p>
        <button type="button" @click="$emit('add')" class="btn-secondary btn-compact btn-add">
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
defineProps({
  students: {
    type: Array,
    default: () => []
  },
  getStatusClass: {
    type: Function,
    default: () => () => ''
  }
})

defineEmits(['view', 'edit', 'delete', 'add'])
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
  color: #64748b;
  background: #f8fafc;
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

.btn-view { color: #10b981; }
.btn-view:hover { background: rgba(16, 185, 129, 0.1); }
.btn-edit { color: #3b82f6; }
.btn-edit:hover { background: rgba(59, 130, 246, 0.1); }
.btn-delete { color: #ef4444; }
.btn-delete:hover { background: rgba(239, 68, 68, 0.1); }

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
.student-card-name { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0 0 6px 0; }
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
