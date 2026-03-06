<template>
  <Layout>
    <div class="qr-generate-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" fill="currentColor"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Generate QR Code Absensi</h1>
            <p class="page-subtitle">Generate QR code untuk siswa atau guru untuk absensi</p>
          </div>
        </div>
      </div>

      <div class="generate-section">
        <div class="form-group">
          <label>Tipe *</label>
          <select v-model="qrType" class="form-select" @change="resetQr">
            <option value="student">Siswa</option>
            <option value="employee">Guru/Staff</option>
          </select>
        </div>

        <div v-if="qrType === 'student'" class="form-group">
          <label>Pilih Siswa *</label>
          <select v-model="selectedStudentId" class="form-select" @change="generateQr">
            <option value="">Pilih siswa</option>
            <option v-for="s in students" :key="s.id" :value="s.id">
              {{ s.nis }} - {{ s.name }}
            </option>
          </select>
        </div>

        <div v-if="qrType === 'employee'" class="form-group">
          <label>Pilih Pegawai *</label>
          <select v-model="selectedEmployeeId" class="form-select" @change="generateQr">
            <option value="">Pilih pegawai</option>
            <option v-for="e in employees" :key="e.id" :value="e.id">
              {{ e.nip || '-' }} - {{ e.name }} ({{ e.type }})
            </option>
          </select>
        </div>

        <div v-if="qrCode" class="qr-display">
          <h3>QR Code</h3>
          <div class="qr-image-container">
            <img :src="qrCode" alt="QR Code" class="qr-image" />
          </div>
          <div class="qr-info">
            <p><strong>Nama:</strong> {{ qrInfo.name }}</p>
            <p v-if="qrInfo.nis"><strong>NIS:</strong> {{ qrInfo.nis }}</p>
            <p v-if="qrInfo.nip"><strong>NIP:</strong> {{ qrInfo.nip }}</p>
          </div>
          <div class="qr-actions">
            <button @click="downloadQr" class="btn-primary">Download QR Code</button>
            <button @click="printQr" class="btn-secondary">Print</button>
          </div>
          <p class="qr-note">QR code ini berlaku selama 1 jam. Scan QR code untuk absensi.</p>
        </div>

        <div v-if="loading" class="loading-state">
          <p>Menggenerate QR code...</p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import { useToast } from '@/composables/useToast'
import { qrAttendanceApi } from '@/api/attendance'
import { studentApi } from '@/api/student'
import { employeeApi } from '@/api/teacher'

const toast = useToast()

const qrType = ref('student')
const selectedStudentId = ref('')
const selectedEmployeeId = ref('')
const students = ref([])
const employees = ref([])
const qrCode = ref('')
const qrInfo = ref({})
const loading = ref(false)

async function loadStudents() {
  try {
    const res = await studentApi.getAll({ per_page: 500, status: 'Aktif' })
    students.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat daftar siswa')
  }
}

async function loadEmployees() {
  try {
    const res = await employeeApi.getAll({ per_page: 500 })
    employees.value = res.data.data || []
  } catch (e) {
    toast.error('Gagal memuat daftar pegawai')
  }
}

async function generateQr() {
  if (qrType.value === 'student' && !selectedStudentId.value) return
  if (qrType.value === 'employee' && !selectedEmployeeId.value) return

  loading.value = true
  qrCode.value = ''
  qrInfo.value = {}

  try {
    let res
    if (qrType.value === 'student') {
      res = await qrAttendanceApi.generateStudentQr(selectedStudentId.value)
      qrInfo.value = {
        name: res.data.data.student_name,
        nis: res.data.data.nis,
      }
    } else {
      res = await qrAttendanceApi.generateEmployeeQr(selectedEmployeeId.value)
      qrInfo.value = {
        name: res.data.data.employee_name,
        nip: res.data.data.nip,
      }
    }

    qrCode.value = res.data.data.qr_code
    toast.success('QR code berhasil digenerate')
  } catch (e) {
    toast.error(e.formattedMessage || 'Gagal generate QR code')
  } finally {
    loading.value = false
  }
}

function resetQr() {
  qrCode.value = ''
  qrInfo.value = {}
  selectedStudentId.value = ''
  selectedEmployeeId.value = ''
}

function downloadQr() {
  if (!qrCode.value) return

  const link = document.createElement('a')
  link.href = qrCode.value
  link.download = `qr-code-${qrType.value}-${Date.now()}.png`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function printQr() {
  if (!qrCode.value) return

  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <html>
      <head>
        <title>Print QR Code</title>
        <style>
          body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
          }
          img {
            max-width: 400px;
            height: auto;
          }
          .info {
            margin-top: 1rem;
            text-align: center;
          }
        </style>
      </head>
      <body>
        <img src="${qrCode.value}" alt="QR Code" />
        <div class="info">
          <p><strong>${qrInfo.value.name}</strong></p>
          ${qrInfo.value.nis ? `<p>NIS: ${qrInfo.value.nis}</p>` : ''}
          ${qrInfo.value.nip ? `<p>NIP: ${qrInfo.value.nip}</p>` : ''}
        </div>
      </body>
    </html>
  `)
  printWindow.document.close()
  printWindow.print()
}

onMounted(async () => {
  await Promise.all([loadStudents(), loadEmployees()])
})
</script>

<style scoped>
.qr-generate-page {
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1.5rem;
}

.header-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 0.9rem;
}

.generate-section {
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
}

.form-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}

.qr-display {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.qr-display h3 {
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.qr-image-container {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.qr-image {
  max-width: 300px;
  width: 100%;
  height: auto;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  background: #fff;
}

.qr-info {
  margin: 1rem 0;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.qr-info p {
  margin: 0.5rem 0;
}

.qr-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary {
  border: none;
  background: #059669;
  color: #fff;
}

.btn-secondary {
  border: 1px solid #e2e8f0;
  background: #fff;
}

.qr-note {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #fef3c7;
  border-radius: 6px;
  color: #92400e;
  font-size: 0.85rem;
}

.loading-state {
  margin-top: 2rem;
  text-align: center;
  padding: 2rem;
  color: #64748b;
}
</style>
