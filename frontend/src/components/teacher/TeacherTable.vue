<template>
  <div class="teacher-table-wrap">
    <!-- Desktop table -->
    <div v-if="teachers.length > 0" class="table-container table-desktop">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th>Nama</th>
            <th>NIP / NUPTK</th>
            <th>Mata Pelajaran</th>
            <th>Kepegawaian</th>
            <th>Status</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(teacher, index) in teachers" :key="teacher.id">
            <td class="col-no">{{ index + 1 }}</td>
            <td>
              <div class="name-cell">
                <span class="name-text">{{ dash(teacher.name) }}</span>
                <div class="name-meta">
                  <span v-if="teacher.type && teacher.type !== 'Guru'" class="meta-chip">{{ teacher.type }}</span>
                  <span v-if="teacher.affiliation === 'non_induk'" class="meta-chip meta-chip-warn">Non-Induk</span>
                  <span v-if="teacher.gender" class="meta-muted">{{ teacher.gender === 'L' ? 'L' : teacher.gender === 'P' ? 'P' : '' }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="id-cell">
                <span>{{ dash(teacher.nip) }}</span>
                <span class="id-sub">{{ dash(teacher.nuptk) }}</span>
              </div>
            </td>
            <td>{{ dash(getTeacherSubject(teacher)) }}</td>
            <td>{{ dash(teacher.employment_status) }}</td>
            <td>
              <span class="status-badge" :class="getStatusClass(teacher.status)">
                {{ teacher.status || '—' }}
              </span>
            </td>
            <td class="col-aksi">
              <div class="action-buttons">
                <template v-if="trashMode">
                  <button type="button" @click="$emit('restore', teacher)" class="btn-action btn-restore" title="Pulihkan">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M3 10H21M7 15H17M12 4V20M4 10L12 4L20 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                </template>
                <template v-else>
                  <button type="button" @click="$emit('view', teacher)" class="btn-action btn-view" title="Lihat biodata">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button type="button" @click="$emit('edit', teacher)" class="btn-action btn-edit" title="Edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </button>
                  <button type="button" @click="$emit('delete', teacher.id)" class="btn-action btn-delete" title="Hapus">
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

    <!-- Mobile cards -->
    <div v-if="teachers.length > 0" class="teacher-cards table-mobile">
      <div v-for="teacher in teachers" :key="teacher.id" class="teacher-card">
        <div class="teacher-card-main">
          <div class="teacher-card-top">
            <h3 class="teacher-card-name">{{ dash(teacher.name) }}</h3>
            <span class="status-badge" :class="getStatusClass(teacher.status)">
              {{ teacher.status || '—' }}
            </span>
          </div>
          <div class="teacher-card-meta">
            <span v-if="teacher.affiliation === 'non_induk'" class="meta-chip meta-chip-warn">Non-Induk</span>
            <span v-if="teacher.nip">NIP {{ teacher.nip }}</span>
            <span v-if="teacher.nuptk">NUPTK {{ teacher.nuptk }}</span>
            <span v-if="getTeacherSubject(teacher)">{{ getTeacherSubject(teacher) }}</span>
          </div>
        </div>
        <div class="teacher-card-actions">
          <template v-if="trashMode">
            <button type="button" @click="$emit('restore', teacher)" class="btn-action btn-restore" title="Pulihkan">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 10H21M7 15H17M12 4V20M4 10L12 4L20 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </template>
          <template v-else>
            <button type="button" @click="$emit('view', teacher)" class="btn-action btn-view" title="Lihat">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button type="button" @click="$emit('edit', teacher)" class="btn-action btn-edit" title="Edit">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
            <button type="button" @click="$emit('delete', teacher.id)" class="btn-action btn-delete" title="Hapus">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </template>
        </div>
      </div>
    </div>

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
function dash(v) {
  if (v === null || v === undefined || v === '') return '—'
  const s = String(v).trim()
  return s || '—'
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
.teacher-table-wrap {
  width: 100%;
}

.table-container {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

.data-table {
  width: 100%;
  min-width: 760px;
  border-collapse: collapse;
  font-size: 14px;
}

.data-table th,
.data-table td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.data-table th {
  font-weight: 600;
  font-size: 12px;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #065f46;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  white-space: nowrap;
}

.data-table .col-no {
  width: 3rem;
  text-align: center;
  white-space: nowrap;
  color: #94a3b8;
}

.data-table .col-aksi {
  width: 120px;
  text-align: center;
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

.name-text {
  font-weight: 600;
  color: #0f172a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.name-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.meta-chip {
  display: inline-flex;
  padding: 1px 8px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 600;
}

.meta-chip-warn {
  background: #fef3c7;
  color: #92400e;
}

.meta-muted {
  font-size: 11px;
  color: #94a3b8;
}

.id-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-variant-numeric: tabular-nums;
}

.id-sub {
  font-size: 12px;
  color: #94a3b8;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  background: #f1f5f9;
  color: #64748b;
}

.status-badge.status-active {
  background: #d1fae5;
  color: #047857;
}

.status-badge.status-success {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-badge.status-warning {
  background: #fef3c7;
  color: #b45309;
}

.status-badge.status-leave {
  background: #ffedd5;
  color: #c2410c;
}

.status-badge.status-resigned {
  background: #fee2e2;
  color: #b91c1c;
}

.status-badge.status-inactive {
  background: #f1f5f9;
  color: #64748b;
}

.action-buttons {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  justify-content: center;
}

.btn-action {
  width: 34px;
  height: 34px;
  padding: 0;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  transition: background 0.15s ease, color 0.15s ease;
}

.btn-view,
.btn-edit,
.btn-restore {
  color: #059669;
}

.btn-view:hover,
.btn-edit:hover,
.btn-restore:hover {
  background: rgba(5, 150, 105, 0.1);
}

.btn-delete {
  color: #ef4444;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
}

.teacher-cards {
  display: none;
  flex-direction: column;
  gap: 12px;
}

.teacher-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.teacher-card-main {
  flex: 1;
  min-width: 0;
}

.teacher-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 6px;
}

.teacher-card-name {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
}

.teacher-card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 6px;
}

.teacher-card-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.empty-state {
  text-align: center;
  padding: 64px 24px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 16px;
}

.empty-state svg {
  color: #cbd5e1;
}

.empty-state h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.empty-state p {
  margin: 0 0 8px;
  font-size: 14px;
}

.empty-state .btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .table-desktop {
    display: none;
  }

  .teacher-cards.table-mobile {
    display: flex;
  }
}
</style>
