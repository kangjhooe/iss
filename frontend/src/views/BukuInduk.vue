<template>
  <Layout>
    <div class="buku-induk-page">
      <div class="tab-header">
        <router-link to="/student" class="back-link">← Kembali ke Daftar Siswa</router-link>
        <div v-if="data" class="header-actions">
          <button
            @click="downloadPdf"
            class="btn-primary btn-compact"
            :disabled="downloadingPdf"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ downloadingPdf ? 'Mengunduh...' : 'Cetak PDF' }}</span>
          </button>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <p>Memuat data buku induk...</p>
      </div>
      <div v-else-if="error" class="error-wrap">
        <p>{{ error }}</p>
        <router-link to="/student" class="btn-secondary">Kembali ke Daftar Siswa</router-link>
      </div>
      <div v-else-if="data" class="buku-induk-content">
        <!-- Identitas Siswa -->
        <section class="buku-section">
          <h2 class="section-title">A. Identitas Siswa</h2>
          <div class="data-grid">
            <div class="data-item"><span class="label">NIS</span><span class="value">{{ data.student.nis || '-' }}</span></div>
            <div class="data-item"><span class="label">NISN</span><span class="value">{{ data.student.nisn || '-' }}</span></div>
            <div class="data-item"><span class="label">NIK</span><span class="value">{{ data.student.nik || '-' }}</span></div>
            <div class="data-item"><span class="label">Nama Lengkap</span><span class="value">{{ data.student.name || '-' }}</span></div>
            <div class="data-item"><span class="label">Jenis Kelamin</span><span class="value">{{ data.student.gender === 'L' ? 'Laki-laki' : data.student.gender === 'P' ? 'Perempuan' : '-' }}</span></div>
            <div class="data-item"><span class="label">Tempat, Tanggal Lahir</span><span class="value">{{ data.student.birth_place || '-' }}, {{ formatDate(data.student.birth_date) }}</span></div>
            <div class="data-item"><span class="label">Agama</span><span class="value">{{ data.student.religion || '-' }}</span></div>
            <div class="data-item"><span class="label">No. KK</span><span class="value">{{ data.student.no_kk || '-' }}</span></div>
            <div class="data-item"><span class="label">Alamat</span><span class="value">{{ data.student.address || '-' }}</span></div>
            <div class="data-item"><span class="label">Telepon / Email</span><span class="value">{{ data.student.phone || '-' }} / {{ data.student.email || '-' }}</span></div>
            <div class="data-item"><span class="label">Tinggi / Berat</span><span class="value">{{ data.student.height ?? '-' }} cm / {{ data.student.weight ?? '-' }} kg</span></div>
            <div class="data-item"><span class="label">Sekolah Asal</span><span class="value">{{ data.student.previous_school || '-' }}</span></div>
            <div class="data-item"><span class="label">Kelas / Tahun Ajaran</span><span class="value">{{ classDisplay }} / {{ academicYearDisplay }}</span></div>
            <div class="data-item"><span class="label">Status</span><span class="value">{{ data.student.status || '-' }}</span></div>
            <div v-if="data.student.graduation_year" class="data-item"><span class="label">Tahun Lulus</span><span class="value">{{ data.student.graduation_year }}</span></div>
            <div v-if="data.student.disability" class="data-item"><span class="label">Kebutuhan Khusus</span><span class="value">{{ data.student.disability }}</span></div>
            <div v-if="data.student.aspiration" class="data-item"><span class="label">Cita-cita</span><span class="value">{{ data.student.aspiration }}</span></div>
            <div v-if="data.student.hobby" class="data-item"><span class="label">Hobi</span><span class="value">{{ data.student.hobby }}</span></div>
            <div v-if="data.student.residence_type" class="data-item"><span class="label">Jenis Tempat Tinggal</span><span class="value">{{ data.student.residence_type }}</span></div>
          </div>
        </section>

        <!-- Data Orang Tua / Wali -->
        <section class="buku-section">
          <h2 class="section-title">B. Data Orang Tua / Wali</h2>
          <div class="data-grid">
            <div class="data-item"><span class="label">Nama Ayah</span><span class="value">{{ data.student.father_name || '-' }}</span></div>
            <div class="data-item"><span class="label">NIK Ayah</span><span class="value">{{ data.student.father_nik || '-' }}</span></div>
            <div class="data-item"><span class="label">Pendidikan / Pekerjaan Ayah</span><span class="value">{{ data.student.father_education || '-' }} / {{ data.student.father_occupation || '-' }}</span></div>
            <div class="data-item"><span class="label">Nama Ibu</span><span class="value">{{ data.student.mother_name || '-' }}</span></div>
            <div class="data-item"><span class="label">NIK Ibu</span><span class="value">{{ data.student.mother_nik || '-' }}</span></div>
            <div class="data-item"><span class="label">Pendidikan / Pekerjaan Ibu</span><span class="value">{{ data.student.mother_education || '-' }} / {{ data.student.mother_occupation || '-' }}</span></div>
            <div v-if="data.student.guardian_name" class="data-item"><span class="label">Nama Wali</span><span class="value">{{ data.student.guardian_name }}</span></div>
            <div v-if="data.student.guardian_name" class="data-item"><span class="label">Telepon Wali</span><span class="value">{{ data.student.guardian_phone || '-' }}</span></div>
          </div>
        </section>

        <!-- Riwayat Kelas -->
        <section class="buku-section">
          <h2 class="section-title">C. Riwayat Kelas</h2>
          <table v-if="data.class_history?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Tahun Ajaran</th>
                <th>Kelas</th>
                <th>Semester</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(h, idx) in data.class_history" :key="h.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ (h.academic_year && typeof h.academic_year === 'object' ? h.academic_year.name : h.academic_year) || '-' }}</td>
                <td>{{ (h.class && typeof h.class === 'object' ? h.class.name : h.class) || '-' }}</td>
                <td>{{ (h.semester && typeof h.semester === 'object' ? h.semester.name : h.semester) || '-' }}</td>
                <td>{{ formatDate(h.start_date) }}</td>
                <td>{{ formatDate(h.end_date) }}</td>
                <td>{{ h.status || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada riwayat kelas.</p>
        </section>

        <!-- Mutasi -->
        <section class="buku-section">
          <h2 class="section-title">D. Riwayat Mutasi</h2>
          <table v-if="data.mutations?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Asal</th>
                <th>Tujuan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(m, idx) in data.mutations" :key="m.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ m.origin_institution?.name || m.origin_school_name || '-' }}</td>
                <td>{{ m.target_institution?.name || m.target_school_name || '-' }}</td>
                <td>{{ m.status || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada riwayat mutasi.</p>
        </section>

        <!-- Prestasi -->
        <section class="buku-section">
          <h2 class="section-title">E. Prestasi</h2>
          <table v-if="data.achievements?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, idx) in data.achievements" :key="a.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ a.achievement_type?.name || '-' }}</td>
                <td>{{ formatDate(a.achievement_date) }}</td>
                <td>{{ a.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data prestasi.</p>
        </section>

        <!-- Pelanggaran -->
        <section class="buku-section">
          <h2 class="section-title">F. Pelanggaran</h2>
          <table v-if="data.violations?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Sanksi</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(v, idx) in data.violations" :key="v.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ v.violation_type?.name || '-' }}</td>
                <td>{{ formatDate(v.violation_date) }}</td>
                <td>{{ v.sanction || '-' }}</td>
                <td>{{ v.description || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data pelanggaran.</p>
        </section>

        <!-- Bimbingan Konseling -->
        <section class="buku-section">
          <h2 class="section-title">G. Bimbingan Konseling</h2>
          <table v-if="data.counseling_sessions?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Hasil / Tindak Lanjut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, idx) in data.counseling_sessions" :key="c.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ c.counseling_type?.name || '-' }}</td>
                <td>{{ formatDate(c.session_date) }}</td>
                <td>{{ c.follow_up_notes || c.summary || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data bimbingan konseling.</p>
        </section>

        <!-- Rekap Kehadiran -->
        <section class="buku-section">
          <h2 class="section-title">H. Rekap Kehadiran</h2>
          <table v-if="data.attendance_summary?.length" class="data-table">
            <thead>
              <tr>
                <th>Tahun Ajaran</th>
                <th>Semester</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Dinas Luar</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(a, idx) in data.attendance_summary" :key="idx">
                <td>{{ a.academic_year_name }}</td>
                <td>{{ a.semester_name }}</td>
                <td>{{ a.hadir ?? 0 }}</td>
                <td>{{ a.sakit ?? 0 }}</td>
                <td>{{ a.izin ?? 0 }}</td>
                <td>{{ a.alpha ?? 0 }}</td>
                <td>{{ a.dinas_luar ?? 0 }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data rekap kehadiran.</p>
        </section>

        <!-- Ringkasan Nilai -->
        <section class="buku-section">
          <h2 class="section-title">I. Ringkasan Nilai (Nilai Akhir)</h2>
          <template v-if="data.grades_summary?.length">
            <div v-for="(period, pIdx) in data.grades_summary" :key="pIdx" class="grades-period">
              <h3 class="period-title">{{ period.academic_year_name }} – {{ period.semester_name }}</h3>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Mata Pelajaran</th>
                    <th>Nilai</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(s, sIdx) in period.subjects" :key="sIdx">
                    <td>{{ s.subject_name }}</td>
                    <td>{{ s.value != null ? s.value : '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
          <p v-else class="no-data">Tidak ada data ringkasan nilai.</p>
        </section>

        <!-- Ekstrakurikuler -->
        <section class="buku-section">
          <h2 class="section-title">J. Ekstrakurikuler</h2>
          <table v-if="data.extracurriculars?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Ekskul</th>
                <th>Tahun Ajaran</th>
                <th>Semester</th>
                <th>Bergabung</th>
                <th>Keluar</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, idx) in data.extracurriculars" :key="e.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ e.extracurricular?.name || '-' }}</td>
                <td>{{ e.academic_year?.name || '-' }}</td>
                <td>{{ e.semester?.name || '-' }}</td>
                <td>{{ formatDate(e.joined_at) }}</td>
                <td>{{ formatDate(e.left_at) }}</td>
                <td>{{ e.status || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data ekstrakurikuler.</p>
        </section>

        <!-- Tujuan Setelah Lulus -->
        <section class="buku-section">
          <h2 class="section-title">K. Tujuan Setelah Lulus</h2>
          <table v-if="data.alumni_destinations?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Nama / Tempat</th>
                <th>Program / Posisi</th>
                <th>Tahun Masuk</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, idx) in data.alumni_destinations" :key="d.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ destinationTypeLabel(d.destination_type) }}</td>
                <td>{{ d.destination_name || '-' }}</td>
                <td>{{ d.program_or_position || '-' }}</td>
                <td>{{ d.year_entered || '-' }}</td>
                <td>{{ d.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data tujuan setelah lulus.</p>
        </section>

        <!-- Ringkasan Perpustakaan -->
        <section class="buku-section">
          <h2 class="section-title">L. Ringkasan Perpustakaan</h2>
          <div class="data-grid">
            <div class="data-item"><span class="label">Total Peminjaman</span><span class="value">{{ data.library_loans_summary?.total_loans ?? 0 }}</span></div>
            <div class="data-item"><span class="label">Keterlambatan (riwayat)</span><span class="value">{{ data.library_loans_summary?.late_count ?? 0 }}</span></div>
            <div class="data-item"><span class="label">Sedang Terlambat</span><span class="value">{{ data.library_loans_summary?.overdue_count ?? 0 }}</span></div>
          </div>
        </section>

        <!-- Riwayat Kesehatan / UKS -->
        <section class="buku-section">
          <h2 class="section-title">M. Riwayat Kesehatan (UKS)</h2>
          <p v-if="!(data.health_records?.length)" class="no-data">Data akan diisi dari modul UKS ketika tersedia.</p>
          <table v-else class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(h, idx) in data.health_records" :key="idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ formatDate(h.date) }}</td>
                <td>{{ h.type || '-' }}</td>
                <td>{{ h.notes || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <!-- Pengambilan Ijazah -->
        <section class="buku-section">
          <h2 class="section-title">N. Pengambilan Ijazah</h2>
          <table v-if="data.document_pickups?.length" class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Dokumen Diambil</th>
                <th>No. Ijazah / Kode Blangko</th>
                <th>Diterima oleh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(dp, idx) in data.document_pickups" :key="dp.id || idx">
                <td>{{ idx + 1 }}</td>
                <td>{{ formatDate(dp.pickup_date) }}</td>
                <td>{{ documentPickupItems(dp) }}</td>
                <td>{{ dp.nomor_ijazah || dp.kode_blangko || '-' }}</td>
                <td>{{ dp.received_by || '-' }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="no-data">Tidak ada data pengambilan ijazah.</p>
        </section>

        <!-- Catatan -->
        <section v-if="data.student?.notes" class="buku-section">
          <h2 class="section-title">O. Catatan</h2>
          <p class="notes-text">{{ data.student.notes }}</p>
        </section>

        <p v-if="data.printed_at" class="meta-printed">Data diambil: {{ data.printed_at }}</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { studentApi } from '@/api/student'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const toast = useToast()

const loading = ref(true)
const error = ref(null)
const data = ref(null)
const downloadingPdf = ref(false)

const studentId = computed(() => route.params.id)

const classDisplay = computed(() => {
  if (!data.value?.student) return '-'
  const s = data.value.student
  const cls = s.class_detail || s.class
  return (cls && typeof cls === 'object' && cls.name) ? cls.name : (typeof s.class === 'string' ? s.class : '-')
})

const academicYearDisplay = computed(() => {
  if (!data.value?.student) return '-'
  const ay = data.value.student.academic_year
  if (ay && typeof ay === 'object' && ay.name) return ay.name
  return ay || data.value.student.academic_year_detail?.name || '-'
})

const DESTINATION_TYPES = {
  Sekolah: 'Lanjut Sekolah (SMA/SMK/dll)',
  Perguruan_Tinggi: 'Perguruan Tinggi',
  Kerja: 'Bekerja',
  Wirausaha: 'Wirausaha',
  Lainnya: 'Lainnya',
}

function formatDate(val) {
  if (!val) return '-'
  const d = new Date(val)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

function destinationTypeLabel(type) {
  return type ? (DESTINATION_TYPES[type] || type) : '-'
}

function documentPickupItems(dp) {
  const items = []
  if (dp.taken_ijazah) items.push('Ijazah')
  if (dp.taken_raport) items.push('Raport')
  if (dp.taken_skhun) items.push('SKHUN')
  if (dp.dokumen_lainnya) items.push(dp.dokumen_lainnya)
  return items.length ? items.join(', ') : '-'
}

async function load() {
  if (!studentId.value) {
    error.value = 'ID siswa tidak valid'
    loading.value = false
    return
  }
  loading.value = true
  error.value = null
  try {
    const res = await studentApi.getBukuInduk(studentId.value)
    data.value = res.data?.data || res.data
    if (!data.value) error.value = 'Data buku induk tidak ditemukan'
  } catch (err) {
    console.error(err)
    error.value = err.response?.data?.message || 'Gagal memuat data buku induk'
  } finally {
    loading.value = false
  }
}

async function downloadPdf() {
  if (!studentId.value || !data.value) return
  downloadingPdf.value = true
  try {
    const res = await studentApi.downloadBukuIndukPdf(studentId.value)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `Buku_Induk_${data.value.student?.name || studentId.value}.pdf`
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Berhasil', 'Buku induk berhasil diunduh')
  } catch (err) {
    console.error(err)
    toast.error('Gagal mengunduh buku induk', err.response?.data?.message || 'Buku induk tidak dapat diunduh. Periksa koneksi dan coba lagi.')
  } finally {
    downloadingPdf.value = false
  }
}

onMounted(() => load())
</script>

<style scoped>
.buku-induk-page {
  padding: 0 0 2rem;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1.25rem;
}

.back-link {
  display: inline-block;
  color: #059669;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
}
.back-link:hover {
  text-decoration: underline;
  color: #047857;
}
.header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.page-title {
  margin: 0 0 4px 0;
  font-size: 1.5rem;
}
.page-subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.95rem;
}
.header-actions {
  flex-shrink: 0;
}

.btn-primary.btn-compact {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  font-size: 0.875rem;
}
.btn-primary.btn-compact:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
}
.btn-primary.btn-compact:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-wrap,
.error-wrap {
  width: 100%;
  text-align: center;
  padding: 3rem 1rem;
}
.error-wrap p {
  margin-bottom: 1rem;
  color: #dc2626;
}
.buku-induk-content {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  padding: 1.5rem;
  margin-top: 1rem;
}
.buku-section {
  margin-bottom: 2rem;
}
.buku-section:last-of-type {
  margin-bottom: 0;
}
.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 12px 0;
  padding-bottom: 6px;
  border-bottom: 2px solid #e2e8f0;
}
.data-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 10px 20px;
}
.data-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.data-item .label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
}
.data-item .value {
  font-size: 0.95rem;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.data-table th,
.data-table td {
  border: 1px solid #e2e8f0;
  padding: 8px 12px;
  text-align: left;
}
.data-table th {
  background: #f8fafc;
  font-weight: 600;
}
.no-data {
  color: #94a3b8;
  font-style: italic;
  margin: 0;
}
.grades-period {
  margin-bottom: 1rem;
}
.grades-period:last-child {
  margin-bottom: 0;
}
.period-title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: #475569;
}
.notes-text {
  margin: 0;
  white-space: pre-wrap;
}
.meta-printed {
  margin-top: 1.5rem;
  font-size: 0.85rem;
  color: #94a3b8;
}
</style>
