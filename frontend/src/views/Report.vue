<template>    <div class="report-page">
      <!-- Toolbar -->
      <div class="tab-header">
        <div class="filters filters-inline"></div>
        <button @click="exportPDF" class="btn-primary btn-compact" :disabled="loading">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Export PDF</span>
        </button>
      </div>

      <!-- Filters -->
      <div class="filters filters-inline">
        <div class="filter-group">
          <label>Bulan</label>
          <select v-model="filters.month" @change="loadReport" class="filter-select">
            <option value="">Semua Bulan</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Tahun</label>
          <select v-model="filters.year" @change="loadReport" class="filter-select">
            <option value="">Semua Tahun</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>
            <input type="checkbox" v-model="filters.compareWithPrevious" @change="loadReport" />
            Bandingkan dengan Tahun Ajaran Sebelumnya
          </label>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="card" :lines="6" :line-widths="['100%', '80%', '60%', '100%', '70%', '50%']" />
      </div>

      <!-- Report Content -->
      <div v-else-if="reportData" class="report-content">
        <!-- Dashboard Mini -->
        <div class="dashboard-mini">
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ reportData.summary?.total_students ?? calculatedTotalStudents }}</div>
              <div class="stat-label">Total Siswa</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ reportData.summary?.total_teachers || 0 }}</div>
              <div class="stat-label">Total Guru</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ reportData.summary?.total_classes || 0 }}</div>
              <div class="stat-label">Total Kelas</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V10M19 10L21 12M19 10L12 3L5 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ reportData.summary?.total_facilities || 0 }}</div>
              <div class="stat-label">Sarana Prasarana</div>
            </div>
          </div>
        </div>

        <!-- Institution Info -->
        <div class="section">
          <h3 class="section-title">Identitas Lembaga</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Nama Lembaga</span>
              <span class="info-value">{{ reportData.institution.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NPSN</span>
              <span class="info-value">{{ reportData.institution.npsn || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">{{ getNssLabel(reportData.institution?.level) }}</span>
              <span class="info-value">{{ reportData.institution.nss || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Level</span>
              <span class="info-value">{{ reportData.institution.level || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Tipe</span>
              <span class="info-value">{{ reportData.institution.type || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Alamat</span>
              <span class="info-value">{{ formatAddress(reportData.institution) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">{{ getPrincipalTitle(reportData.institution?.level) }}</span>
              <span class="info-value">{{ reportData.institution.principal_name || '-' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NIP {{ getPrincipalTitle(reportData.institution?.level) }}</span>
              <span class="info-value">{{ reportData.institution.principal_nip || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Students Statistics -->
        <div class="section">
          <h3 class="section-title">Data Siswa</h3>
          <div class="chart-container" v-if="!studentsTableDetailEffective">
            <div class="chart-wrapper">
              <h4>Jumlah Siswa per Kelas</h4>
              <Bar v-if="studentsChartData" :data="studentsChartData" :options="chartOptions" />
              <div v-else class="chart-placeholder">Memuat data chart...</div>
            </div>
            <div class="chart-wrapper">
              <h4>Distribusi Siswa per Kelas</h4>
              <Doughnut v-if="studentsPieChartData" :data="studentsPieChartData" :options="pieChartOptions" />
              <div v-else class="chart-placeholder">Memuat data chart...</div>
            </div>
          </div>
          <!-- Tabel format laporan: Kls, Jumlah Rombel, Jumlah Awal, Siswa Keluar, Siswa Masuk, Jumlah Akhir -->
          <div class="data-table-container" v-if="studentsTableDetailEffective">
            <p class="table-period-hint" v-if="reportData.period?.month && reportData.period?.year">
              Periode: {{ monthLabel(reportData.period.month) }} {{ reportData.period.year }}
            </p>
            <table class="data-table data-table-student-report">
              <thead>
                <tr>
                  <th rowspan="2" class="col-kls">Kls</th>
                  <th rowspan="2" class="col-romb">Jumlah Rombel</th>
                  <th colspan="3">Jumlah Awal</th>
                  <th colspan="3">Siswa Keluar</th>
                  <th colspan="3">Siswa Masuk</th>
                  <th colspan="3">Jumlah Akhir</th>
                </tr>
                <tr>
                  <th>L</th><th>P</th><th>Jml</th>
                  <th>L</th><th>P</th><th>Jml</th>
                  <th>L</th><th>P</th><th>Jml</th>
                  <th>L</th><th>P</th><th>Jml</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in studentsTableDetailRows" :key="row.grade">
                  <td><strong>{{ row.gradeLabel }}</strong></td>
                  <td>{{ row.jml_romb }}</td>
                  <td>{{ row.jumlah_awal.male }}</td>
                  <td>{{ row.jumlah_awal.female }}</td>
                  <td>{{ row.jumlah_awal.total }}</td>
                  <td>{{ row.siswa_keluar.male || '' }}</td>
                  <td>{{ row.siswa_keluar.female || '' }}</td>
                  <td>{{ row.siswa_keluar.total || '' }}</td>
                  <td>{{ row.siswa_masuk.male || '' }}</td>
                  <td>{{ row.siswa_masuk.female || '' }}</td>
                  <td>{{ row.siswa_masuk.total || '' }}</td>
                  <td>{{ row.jumlah_akhir.male }}</td>
                  <td>{{ row.jumlah_akhir.female }}</td>
                  <td>{{ row.jumlah_akhir.total }}</td>
                </tr>
                <tr class="total-row" v-if="studentsTableDetailEffective?.totals">
                  <td><strong>Jml</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jml_romb }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_awal.male }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_awal.female }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_awal.total }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_keluar.male }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_keluar.female }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_keluar.total }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_masuk.male }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_masuk.female }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.siswa_masuk.total }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_akhir.male }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_akhir.female }}</strong></td>
                  <td><strong>{{ studentsTableDetailEffective.totals.jumlah_akhir.total }}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Tabel sederhana (fallback jika tidak ada data siswa) -->
          <div class="data-table-container" v-else>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kelas</th>
                  <th>Laki-laki</th>
                  <th>Perempuan</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="grade in gradeRange" :key="grade">
                  <td><strong>Kelas {{ grade }}</strong></td>
                  <td>{{ reportData.students?.[`grade_${grade}`]?.male || 0 }}</td>
                  <td>{{ reportData.students?.[`grade_${grade}`]?.female || 0 }}</td>
                  <td><strong>{{ reportData.students?.[`grade_${grade}`]?.total || 0 }}</strong></td>
                </tr>
                <tr class="total-row">
                  <td><strong>Total</strong></td>
                  <td><strong>{{ totalMaleStudents }}</strong></td>
                  <td><strong>{{ totalFemaleStudents }}</strong></td>
                  <td><strong>{{ calculatedTotalStudents }}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Students Status Statistics -->
        <div class="section">
          <h3 class="section-title">Status Siswa</h3>
          <div class="chart-container">
            <div class="chart-wrapper">
              <h4>Distribusi Siswa per Status</h4>
              <Doughnut v-if="studentsStatusChartData" :data="studentsStatusChartData" :options="pieChartOptions" />
            </div>
          </div>
          <div class="data-table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Jumlah</th>
                  <th>Persentase</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(count, status) in reportData.students_by_status" :key="status" v-if="status !== 'total'">
                  <td><strong>{{ status }}</strong></td>
                  <td>{{ count }}</td>
                  <td>{{ reportData.students_by_status?.total > 0 ? ((count / reportData.students_by_status.total) * 100).toFixed(2) : 0 }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Classes Detail -->
        <div class="section" v-if="reportData.classes_detail">
          <h3 class="section-title">Rombongan Belajar (Rombel)</h3>
          <div class="info-grid" style="margin-bottom: 20px;">
            <div class="info-item">
              <span class="info-label">Total Rombel</span>
              <span class="info-value">{{ reportData.classes_detail.total_rombel || 0 }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Rombel</span>
              <span class="info-value">{{ reportData.classes_detail.average_students_per_rombel || 0 }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Kapasitas per Rombel</span>
              <span class="info-value">{{ reportData.classes_detail.average_capacity || 0 }}</span>
            </div>
          </div>
          <div v-for="(rombelList, gradeKey) in reportData.classes_detail.by_grade" :key="gradeKey" style="margin-bottom: 24px;">
            <h4 style="margin-bottom: 12px; color: #475569; font-size: 16px;">{{ gradeKey.replace('grade_', 'Kelas ') }}</h4>
            <div class="data-table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nama Rombel</th>
                    <th>Kode</th>
                    <th>Wali Kelas</th>
                    <th>Ruangan</th>
                    <th>Siswa</th>
                    <th>Kapasitas</th>
                    <th>Utilisasi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(rombel, index) in rombelList" :key="index">
                    <td>{{ rombel.name }}</td>
                    <td>{{ rombel.code || '-' }}</td>
                    <td>{{ rombel.wali_kelas }}</td>
                    <td>{{ rombel.room }}</td>
                    <td>{{ rombel.students }}</td>
                    <td>{{ rombel.capacity || '-' }}</td>
                    <td>
                      <span :style="{ color: rombel.utilization > 100 ? '#ef4444' : rombel.utilization > 80 ? '#f59e0b' : '#10b981' }">
                        {{ rombel.utilization }}%
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Employees Statistics -->
        <div class="section">
          <h3 class="section-title">Data Tenaga Kependidikan</h3>
          <div class="chart-container">
            <div class="chart-wrapper">
              <h4>Distribusi Tenaga Kependidikan</h4>
              <Doughnut v-if="employeesChartData" :data="employeesChartData" :options="pieChartOptions" />
            </div>
            <div class="chart-wrapper">
              <h4>Tenaga Kependidikan per Jenis Kelamin</h4>
              <Bar v-if="employeesGenderChartData" :data="employeesGenderChartData" :options="chartOptions" />
            </div>
          </div>
          <div class="data-table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kategori</th>
                  <th>Laki-laki</th>
                  <th>Perempuan</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Guru</strong></td>
                  <td>{{ reportData.employees?.teachers_male || 0 }}</td>
                  <td>{{ reportData.employees?.teachers_female || 0 }}</td>
                  <td><strong>{{ reportData.employees?.teachers || 0 }}</strong></td>
                </tr>
                <tr>
                  <td><strong>Tenaga Administrasi/Staff</strong></td>
                  <td>{{ reportData.employees?.staff_male || 0 }}</td>
                  <td>{{ reportData.employees?.staff_female || 0 }}</td>
                  <td><strong>{{ reportData.employees?.staff || 0 }}</strong></td>
                </tr>
                <tr class="total-row">
                  <td><strong>Total Tenaga Kependidikan</strong></td>
                  <td><strong>{{ reportData.employees?.male || 0 }}</strong></td>
                  <td><strong>{{ reportData.employees?.female || 0 }}</strong></td>
                  <td><strong>{{ reportData.employees?.total || 0 }}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Facilities Statistics -->
        <div class="section">
          <h3 class="section-title">Sarana Prasarana</h3>
          <div class="facilities-grid">
            <div class="facility-card">
              <h4>Tanah</h4>
              <div class="facility-stats">
                <div class="facility-stat">
                  <span class="facility-label">Jumlah</span>
                  <span class="facility-value">{{ reportData.facilities.land.count }}</span>
                </div>
                <div class="facility-stat">
                  <span class="facility-label">Total Luas (m²)</span>
                  <span class="facility-value">{{ formatNumber(reportData.facilities.land.total_area) }}</span>
                </div>
              </div>
            </div>
            <div class="facility-card">
              <h4>Gedung</h4>
              <div class="facility-stats">
                <div class="facility-stat">
                  <span class="facility-label">Jumlah</span>
                  <span class="facility-value">{{ reportData.facilities.buildings.count }}</span>
                </div>
                <div class="facility-stat">
                  <span class="facility-label">Total Luas (m²)</span>
                  <span class="facility-value">{{ formatNumber(reportData.facilities.buildings.total_area) }}</span>
                </div>
              </div>
            </div>
            <div class="facility-card">
              <h4>Ruangan</h4>
              <div class="facility-stats">
                <div class="facility-stat">
                  <span class="facility-label">Total Ruangan</span>
                  <span class="facility-value">{{ reportData.facilities.rooms.count }}</span>
                </div>
              </div>
              <div class="rooms-by-type">
                <h5>Per Jenis Ruangan</h5>
                <table class="rooms-table">
                  <tbody>
                    <tr v-for="(count, type) in reportData.facilities.rooms.by_type" :key="type">
                      <td>{{ type }}</td>
                      <td><strong>{{ count }}</strong></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Ratios and Indicators -->
        <div class="section">
          <h3 class="section-title">Rasio dan Indikator</h3>
          <div class="ratios-grid">
            <div class="ratio-card">
              <div class="ratio-icon" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ratio-content">
                <div class="ratio-value">{{ reportData.summary?.student_teacher_ratio || 0 }}</div>
                <div class="ratio-label">Rasio Siswa : Guru</div>
              </div>
            </div>
            <div class="ratio-card">
              <div class="ratio-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ratio-content">
                <div class="ratio-value">{{ reportData.summary?.average_students_per_class || 0 }}</div>
                <div class="ratio-label">Rata-rata Siswa per Kelas</div>
              </div>
            </div>
            <div class="ratio-card" v-if="reportData.summary?.average_students_per_rombel">
              <div class="ratio-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="ratio-content">
                <div class="ratio-value">{{ reportData.summary?.average_students_per_rombel || 0 }}</div>
                <div class="ratio-label">Rata-rata Siswa per Rombel</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Comparison Section -->
        <div v-if="reportData.comparison" class="section">
          <h3 class="section-title">Perbandingan dengan Tahun Ajaran Sebelumnya</h3>
          <div class="comparison-container">
            <div class="comparison-chart">
              <h4>Perbandingan Jumlah Siswa</h4>
              <Bar :data="comparisonChartData" :options="comparisonChartOptions" />
            </div>
          </div>
        </div>
      </div>
    </div></template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js'
import { reportApi } from '@/api/report'
import { getPrincipalTitle, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const toast = useToast()
const loading = ref(false)
const reportData = ref(null)

const filters = ref({
  month: (new Date().getMonth() + 1).toString(),
  year: new Date().getFullYear().toString(),
  compareWithPrevious: false
})

const months = [
  { value: '1', label: 'Januari' },
  { value: '2', label: 'Februari' },
  { value: '3', label: 'Maret' },
  { value: '4', label: 'April' },
  { value: '5', label: 'Mei' },
  { value: '6', label: 'Juni' },
  { value: '7', label: 'Juli' },
  { value: '8', label: 'Agustus' },
  { value: '9', label: 'September' },
  { value: '10', label: 'Oktober' },
  { value: '11', label: 'November' },
  { value: '12', label: 'Desember' }
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const yearList = []
  for (let i = currentYear; i >= currentYear - 5; i--) {
    yearList.push(i.toString())
  }
  return yearList
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
    title: {
      display: false,
    }
  }
}

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    }
  }
}

const comparisonChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}

const gradeRange = computed(() => {
  if (!reportData.value?.institution?.level) {
    return [7, 8, 9] // Default to SMP/MTs
  }
  
  const level = reportData.value.institution.level
  const gradeRanges = {
    'TK': [1],
    'PAUD': [1],
    'SD': [1, 2, 3, 4, 5, 6],
    'MI': [1, 2, 3, 4, 5, 6],
    'SMP': [7, 8, 9],
    'MTs': [7, 8, 9],
    'SMA': [10, 11, 12],
    'MA': [10, 11, 12],
    'SMK': [10, 11, 12],
    'MAK': [10, 11, 12],
  }
  
  return gradeRanges[level] || [7, 8, 9]
})

const formatGradeLabel = (grade) => {
  const g = Number(grade)
  if (g === 10) return 'X'
  if (g === 11) return 'XI'
  if (g === 12) return 'XII'
  return String(grade)
}

const studentsTableDetailEffective = computed(() => {
  const detail = reportData.value?.students_table_detail
  if (detail?.by_grade) return detail
  const students = reportData.value?.students
  const classesDetail = reportData.value?.classes_detail
  if (!students || !reportData.value?.institution?.level) return null
  const level = reportData.value.institution.level
  const gradeRanges = { TK: [1], PAUD: [1], SD: [1,2,3,4,5,6], MI: [1,2,3,4,5,6], SMP: [7,8,9], MTs: [7,8,9], SMA: [10,11,12], MA: [10,11,12], SMK: [10,11,12], MAK: [10,11,12] }
  const grades = gradeRanges[level] || [7, 8, 9]
  const byGrade = {}
  let totRomb = 0
  const tot = { jml_romb: 0, jumlah_awal: { male: 0, female: 0, total: 0 }, siswa_keluar: { male: 0, female: 0, total: 0 }, siswa_masuk: { male: 0, female: 0, total: 0 }, jumlah_akhir: { male: 0, female: 0, total: 0 } }
  for (const g of grades) {
    const key = `grade_${g}`
    const s = students[key] || { male: 0, female: 0, total: 0 }
    const jmlRomb = (classesDetail?.by_grade && Array.isArray(classesDetail.by_grade[key])) ? classesDetail.by_grade[key].length : 0
    byGrade[key] = {
      grade: g,
      jml_romb: jmlRomb,
      jumlah_awal: { ...s },
      siswa_keluar: { male: 0, female: 0, total: 0 },
      siswa_masuk: { male: 0, female: 0, total: 0 },
      jumlah_akhir: { ...s },
    }
    totRomb += jmlRomb
    tot.jumlah_awal.male += s.male
    tot.jumlah_awal.female += s.female
    tot.jumlah_awal.total += s.total
    tot.siswa_keluar.total += 0
    tot.siswa_masuk.total += 0
    tot.jumlah_akhir.male += s.male
    tot.jumlah_akhir.female += s.female
    tot.jumlah_akhir.total += s.total
  }
  tot.jml_romb = totRomb
  return { grade_range: [grades[0], grades[grades.length - 1]], by_grade: byGrade, totals: tot }
})

const studentsTableDetailRows = computed(() => {
  const detail = studentsTableDetailEffective.value
  if (!detail?.by_grade) return []
  const [minG, maxG] = detail.grade_range || [1, 12]
  const rows = []
  for (let g = minG; g <= maxG; g++) {
    const key = `grade_${g}`
    const r = detail.by_grade[key]
    if (!r) continue
    rows.push({
      grade: g,
      gradeLabel: formatGradeLabel(g),
      jml_romb: r.jml_romb ?? 0,
      jumlah_awal: r.jumlah_awal ?? { male: 0, female: 0, total: 0 },
      siswa_keluar: r.siswa_keluar ?? { male: 0, female: 0, total: 0 },
      siswa_masuk: r.siswa_masuk ?? { male: 0, female: 0, total: 0 },
      jumlah_akhir: r.jumlah_akhir ?? { male: 0, female: 0, total: 0 },
    })
  }
  return rows
})

const monthLabel = (month) => {
  const names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  return names[Number(month)] || month
}

const studentsChartData = computed(() => {
  if (!reportData.value || !reportData.value.students) {
    if (import.meta.env.DEV) {
      console.log('Chart data: reportData or students is null')
    }
    return null
  }
  
  const grades = gradeRange.value
  const labels = grades.map(g => `Kelas ${g}`)
  const maleData = grades.map(grade => reportData.value.students[`grade_${grade}`]?.male || 0)
  const femaleData = grades.map(grade => reportData.value.students[`grade_${grade}`]?.female || 0)
  
  if (import.meta.env.DEV) {
    console.log('Chart data - Male:', maleData, 'Female:', femaleData)
  }
  
  return {
    labels: labels,
    datasets: [
      {
        label: 'Laki-laki',
        backgroundColor: '#059669',
        data: maleData
      },
      {
        label: 'Perempuan',
        backgroundColor: '#f093fb',
        data: femaleData
      }
    ]
  }
})

const studentsPieChartData = computed(() => {
  if (!reportData.value || !reportData.value.students) return null
  
  const grades = gradeRange.value
  const labels = grades.map(g => `Kelas ${g}`)
  const colors = ['#059669', '#f093fb', '#4facfe', '#43e97b', '#38f9d7', '#f5576c', '#047857', '#059669', '#f093fb', '#4facfe', '#43e97b', '#38f9d7']
  
  return {
    labels: labels,
    datasets: [{
      backgroundColor: colors.slice(0, grades.length),
      data: grades.map(grade => reportData.value.students[`grade_${grade}`]?.total || 0)
    }]
  }
})

const teachersChartData = computed(() => {
  if (!reportData.value) return null
  
  const teachersMale = reportData.value.employees?.teachers_male || reportData.value.teachers?.male || 0
  const teachersFemale = reportData.value.employees?.teachers_female || reportData.value.teachers?.female || 0
  
  return {
    labels: ['Guru'],
    datasets: [
      {
        label: 'Laki-laki',
        backgroundColor: '#059669',
        data: [teachersMale]
      },
      {
        label: 'Perempuan',
        backgroundColor: '#f093fb',
        data: [teachersFemale]
      }
    ]
  }
})

const employeesChartData = computed(() => {
  if (!reportData.value || !reportData.value.employees) return null
  
  const teachers = reportData.value.employees.teachers || 0
  const staff = reportData.value.employees.staff || 0
  
  return {
    labels: ['Guru', 'Tenaga Administrasi/Staff'],
    datasets: [{
      backgroundColor: ['#059669', '#f093fb'],
      data: [teachers, staff]
    }]
  }
})

const employeesGenderChartData = computed(() => {
  if (!reportData.value || !reportData.value.employees) return null
  
  const male = reportData.value.employees.male || 0
  const female = reportData.value.employees.female || 0
  
  return {
    labels: ['Tenaga Kependidikan'],
    datasets: [
      {
        label: 'Laki-laki',
        backgroundColor: '#059669',
        data: [male]
      },
      {
        label: 'Perempuan',
        backgroundColor: '#f093fb',
        data: [female]
      }
    ]
  }
})

const studentsStatusChartData = computed(() => {
  if (!reportData.value || !reportData.value.students_by_status) return null
  
  const statusData = reportData.value.students_by_status
  const labels = []
  const data = []
  const colors = ['#10b981', '#059669', '#f59e0b', '#ef4444', '#94a3b8']
  let colorIndex = 0
  
  for (const [status, count] of Object.entries(statusData)) {
    if (status !== 'total' && count > 0) {
      labels.push(status)
      data.push(count)
      colorIndex++
    }
  }
  
  return {
    labels: labels,
    datasets: [{
      backgroundColor: colors.slice(0, labels.length),
      data: data
    }]
  }
})

const comparisonChartData = computed(() => {
  if (!reportData.value || !reportData.value.comparison || !reportData.value.students) return null
  
  const current = reportData.value.students
  const previous = reportData.value.comparison.students
  const grades = gradeRange.value
  const labels = grades.map(g => `Kelas ${g}`)
  
  return {
    labels: labels,
    datasets: [
      {
        label: reportData.value.academic_year?.name || 'Tahun Ajaran Aktif',
        backgroundColor: '#059669',
        data: grades.map(grade => current[`grade_${grade}`]?.total || 0)
      },
      {
        label: reportData.value.comparison.academic_year || 'Tahun Ajaran Sebelumnya',
        backgroundColor: '#94a3b8',
        data: grades.map(grade => previous[`grade_${grade}`]?.total || 0)
      }
    ]
  }
})

const totalMaleStudents = computed(() => {
  if (!reportData.value || !reportData.value.students) return 0
  return gradeRange.value.reduce((sum, grade) => {
    return sum + (reportData.value.students[`grade_${grade}`]?.male || 0)
  }, 0)
})

const totalFemaleStudents = computed(() => {
  if (!reportData.value || !reportData.value.students) return 0
  return gradeRange.value.reduce((sum, grade) => {
    return sum + (reportData.value.students[`grade_${grade}`]?.female || 0)
  }, 0)
})

const calculatedTotalStudents = computed(() => {
  if (!reportData.value || !reportData.value.students) return 0
  return totalMaleStudents.value + totalFemaleStudents.value
})

const loadReport = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.month) params.month = filters.value.month
    if (filters.value.year) params.year = filters.value.year
    if (filters.value.compareWithPrevious) params.compare_with_previous = true
    
    const response = await reportApi.getStatistics(null, params)
    
      if (response.data && response.data.data) {
      const data = response.data.data
      
      // Debug logging (development only)
      if (import.meta.env.DEV) {
        console.log('Raw API Response:', response)
        console.log('Response data:', response.data)
        console.log('Raw data structure:', JSON.stringify(data, null, 2))
        console.log('Students data (raw):', data.students)
        console.log('Summary data (raw):', data.summary)
      }
      
      // Normalize data structure - ensure students structure exists
      // Get grade range based on institution level
      const institutionLevel = data.institution?.level
      const gradeRanges = {
        'TK': [1],
        'PAUD': [1],
        'SD': [1, 2, 3, 4, 5, 6],
        'MI': [1, 2, 3, 4, 5, 6],
        'SMP': [7, 8, 9],
        'MTs': [7, 8, 9],
        'SMA': [10, 11, 12],
        'MA': [10, 11, 12],
        'SMK': [10, 11, 12],
        'MAK': [10, 11, 12],
      }
      const grades = gradeRanges[institutionLevel] || [7, 8, 9]
      
      if (!data.students) {
        data.students = {}
        grades.forEach(grade => {
          data.students[`grade_${grade}`] = { male: 0, female: 0, total: 0 }
        })
      } else {
        // Ensure each grade has the required structure and recalculate totals
        grades.forEach(grade => {
          const gradeKey = `grade_${grade}`
          if (!data.students[gradeKey]) {
            data.students[gradeKey] = { male: 0, female: 0, total: 0 }
          } else {
            // Ensure values are numbers
            data.students[gradeKey].male = Number(data.students[gradeKey].male) || 0
            data.students[gradeKey].female = Number(data.students[gradeKey].female) || 0
            // Recalculate total from male + female to ensure accuracy
            data.students[gradeKey].total = data.students[gradeKey].male + data.students[gradeKey].female
          }
        })
      }
      
      // Recalculate summary.total_students from actual students data
      if (data.summary) {
        const calculatedTotal = grades.reduce((sum, grade) => {
          return sum + (data.students[`grade_${grade}`]?.total || 0)
        }, 0)
        data.summary.total_students = calculatedTotal
        
        // Debug log (development only)
        if (import.meta.env.DEV) {
          console.log('=== Students Data Normalized ===')
          console.log('Institution Level:', institutionLevel)
          console.log('Grade Range:', grades)
          grades.forEach(grade => {
            console.log(`Grade ${grade}:`, data.students[`grade_${grade}`])
          })
          console.log('Calculated Total:', calculatedTotal)
          console.log('Summary Total (before):', data.summary.total_students)
          console.log('Summary Total (after):', calculatedTotal)
        }
      } else {
        if (import.meta.env.DEV) {
          console.warn('Summary data is missing!')
        }
      }
      
      // Normalize employees/teachers structure
      if (data.employees && !data.teachers) {
        // Create teachers object from employees for backward compatibility
        data.teachers = {
          male: data.employees.teachers_male || 0,
          female: data.employees.teachers_female || 0,
          total: data.employees.teachers || 0
        }
      } else if (!data.teachers) {
        data.teachers = { male: 0, female: 0, total: 0 }
      }
      
      // Ensure students_by_status exists
      if (!data.students_by_status) {
        data.students_by_status = {
          'Aktif': 0,
          'Lulus': 0,
          'Pindah': 0,
          'Drop Out': 0,
          'Lainnya': 0,
          'total': 0
        }
      }
      
      // Ensure classes_detail exists
      if (!data.classes_detail) {
        data.classes_detail = {
          by_grade: {},
          total_rombel: 0,
          total_capacity: 0,
          total_students: 0,
          average_capacity: 0,
          average_students_per_rombel: 0
        }
      }
      
      reportData.value = data
    } else {
      throw new Error('Format data tidak valid')
    }
  } catch (err) {
    if (import.meta.env.DEV) {
      console.error('Failed to load report:', err)
      console.error('Error details:', {
        response: err.response?.data,
        status: err.response?.status,
        message: err.message
      })
    }
    let errorMsg = 'Gagal memuat data laporan'
    if (err.response?.data?.message) {
      errorMsg = err.response.data.message
    } else if (err.response?.data?.error) {
      errorMsg = err.response.data.error
    } else if (err.formattedMessage) {
      errorMsg = err.formattedMessage
    } else if (err.message) {
      errorMsg = err.message
    }
    
    toast.error('Gagal', errorMsg)
  } finally {
    loading.value = false
  }
}

const escapeHtml = (value) => String(value ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

const formatAddress = (institution) => {
  return [
    institution.address,
    institution.village ? `Desa/Kel. ${institution.village}` : '',
    institution.sub_district ? `Kec. ${institution.sub_district}` : '',
    institution.district,
    institution.province,
    institution.postal_code,
  ].filter(Boolean).join(', ') || '-'
}

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('id-ID').format(num)
}

const getPrincipalLabel = (institution) => getPrincipalTitle(institution?.level)

const exportPDF = async () => {
  if (!reportData.value) {
    toast.error('Gagal', 'Tidak ada data untuk diekspor')
    return
  }
  
  try {
    const institution = reportData.value.institution
    const printWindow = window.open('', '_blank')
    const filename = `Laporan_${institution.name}_${new Date().toISOString().split('T')[0]}.pdf`
    
    const fullAddress = formatAddress(institution)
    const principalLabel = getPrincipalLabel(institution)

    const detail = studentsTableDetailEffective.value || reportData.value.students_table_detail
    let studentsTableHtml
    if (detail?.by_grade && detail?.totals) {
      const [minG, maxG] = detail.grade_range || [1, 12]
      const rowCells = (r) => [
        r.jumlah_awal?.male ?? 0, r.jumlah_awal?.female ?? 0, r.jumlah_awal?.total ?? 0,
        r.siswa_keluar?.male ?? '', r.siswa_keluar?.female ?? '', r.siswa_keluar?.total ?? '',
        r.siswa_masuk?.male ?? '', r.siswa_masuk?.female ?? '', r.siswa_masuk?.total ?? '',
        r.jumlah_akhir?.male ?? 0, r.jumlah_akhir?.female ?? 0, r.jumlah_akhir?.total ?? 0
      ]
      const dataRows = []
      for (let g = minG; g <= maxG; g++) {
        const r = detail.by_grade[`grade_${g}`]
        if (!r) continue
        const cells = rowCells(r)
        dataRows.push(`<tr><td><strong>${formatGradeLabel(g)}</strong></td><td>${r.jml_romb ?? 0}</td>${cells.map(c => `<td>${c}</td>`).join('')}</tr>`)
      }
      const t = detail.totals
      const totalCells = [t.jumlah_awal?.male ?? 0, t.jumlah_awal?.female ?? 0, t.jumlah_awal?.total ?? 0, t.siswa_keluar?.male ?? 0, t.siswa_keluar?.female ?? 0, t.siswa_keluar?.total ?? 0, t.siswa_masuk?.male ?? 0, t.siswa_masuk?.female ?? 0, t.siswa_masuk?.total ?? 0, t.jumlah_akhir?.male ?? 0, t.jumlah_akhir?.female ?? 0, t.jumlah_akhir?.total ?? 0].map(v => `<td><strong>${v}</strong></td>`).join('')
      studentsTableHtml = `
        <p class="table-period-hint">Periode: ${monthLabel(reportData.value.period?.month ?? filters.value.month)} ${reportData.value.period?.year || filters.value.year || ''}</p>
        <table class="data-table data-table-student-report">
          <thead>
            <tr>
              <th rowspan="2" class="col-kls">Kls</th>
              <th rowspan="2" class="col-romb">Jumlah Rombel</th>
              <th colspan="3">Jumlah Awal</th>
              <th colspan="3">Siswa Keluar</th>
              <th colspan="3">Siswa Masuk</th>
              <th colspan="3">Jumlah Akhir</th>
            </tr>
            <tr>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
            </tr>
          </thead>
          <tbody>
            ${dataRows.join('')}
            <tr class="total-row"><td><strong>Jml</strong></td><td><strong>${t.jml_romb ?? 0}</strong></td>${totalCells}</tr>
          </tbody>
        </table>
      `
    } else {
      const grades = gradeRange.value
      const studentsTableRows = grades.map(grade => `
      <tr>
        <td><strong>Kelas ${grade}</strong></td>
        <td>${reportData.value.students?.[`grade_${grade}`]?.male || 0}</td>
        <td>${reportData.value.students?.[`grade_${grade}`]?.female || 0}</td>
        <td><strong>${reportData.value.students?.[`grade_${grade}`]?.total || 0}</strong></td>
      </tr>
    `).join('') + `
      <tr>
        <td><strong>Total</strong></td>
        <td><strong>${totalMaleStudents.value}</strong></td>
        <td><strong>${totalFemaleStudents.value}</strong></td>
        <td><strong>${calculatedTotalStudents.value || reportData.value.summary?.total_students || 0}</strong></td>
      </tr>
    `
      studentsTableHtml = `
        <table class="data-table">
          <thead>
            <tr>
              <th>Kelas</th>
              <th>Laki-laki</th>
              <th>Perempuan</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${studentsTableRows}
          </tbody>
        </table>
      `
    }
    
    // Build rooms table HTML
    let roomsTableRows = ''
    if (reportData.value.facilities.rooms.by_type) {
      for (const [type, count] of Object.entries(reportData.value.facilities.rooms.by_type)) {
        roomsTableRows += `<tr><td>${type}</td><td><strong>${count}</strong></td></tr>`
      }
    }
    
    const content = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>${escapeHtml(`Laporan ${institution.name || ''}`)}</title>
        <style>
        @media print {
          @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 10mm;
          }
          body { margin: 0; }
        }
        body {
          font-family: Arial, Helvetica, sans-serif;
          font-size: 12px;
          line-height: 1.3;
          color: #111;
          margin: 16px;
          padding: 0;
        }
        .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
        .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
        .kop-logo { width: 66px; height: 66px; object-fit: contain; }
        .kop-text { min-width: 0; text-align: center; }
        .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
        .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
        .school-info { font-size: 9px; margin-top: 2px; }
        .header {
          text-align: center;
          margin-bottom: 20px;
          margin-top: 15px;
        }
        .header h1 {
          color: #000;
          margin: 0;
          font-size: 18px;
          font-weight: bold;
          text-transform: uppercase;
        }
        .section {
          margin-bottom: 20px;
          page-break-inside: avoid;
        }
        .section-title {
          background: #f0f0f0;
          color: #000;
          padding: 8px 12px;
          margin: 0 0 12px 0;
          font-size: 14px;
          font-weight: bold;
          border-left: 4px solid #000;
        }
        .info-grid {
          display: grid;
          grid-template-columns: 1fr 2fr;
          gap: 8px 16px;
          margin-bottom: 12px;
        }
        .info-item {
          display: contents;
        }
        .info-label {
          font-weight: bold;
          font-size: 12px;
        }
        .info-value {
          font-size: 12px;
        }
        .data-table {
          width: calc(100% - 2px);
          max-width: calc(100% - 2px);
          border-collapse: collapse;
          margin-top: 12px;
          font-size: 12px;
        }
        .data-table th,
        .data-table td {
          border: 1px solid #000;
          padding: 8px;
          text-align: left;
        }
        .data-table th {
          background: #f0f0f0;
          font-weight: bold;
          text-align: center;
        }
        .data-table td {
          text-align: center;
        }
        .total-row {
          background: #f0f0f0;
          font-weight: bold;
        }
        .table-period-hint {
          margin-bottom: 8px;
          font-size: 12px;
          color: #333;
        }
        .data-table-student-report th,
        .data-table-student-report td {
          text-align: center;
        }
        .summary-box {
          background: #f9f9f9;
          border: 1px solid #ddd;
          padding: 12px;
          margin-bottom: 20px;
        }
        .summary-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 12px;
        }
        .summary-item {
          text-align: center;
        }
        .summary-value {
          font-size: 24px;
          font-weight: bold;
          color: #059669;
        }
        .summary-label {
          font-size: 12px;
          color: #666;
        }
        .footer {
          margin-top: 40px;
          display: flex;
          justify-content: space-between;
          page-break-inside: avoid;
        }
        .footer-date {
          font-size: 11px;
        }
        .footer-signature {
          text-align: center;
        }
        .footer-signature-label {
          margin-bottom: 60px;
          font-weight: bold;
        }
        .footer-signature-name {
          font-weight: bold;
          text-decoration: underline;
        }
        .footer-signature-nip {
          font-size: 11px;
          margin-top: 5px;
        }
        </style>
      </head>
      <body>
        <header class="kop">
          <div class="kop-inner">
            <div>${institution.logo ? `<img src="${escapeHtml(institution.logo)}" alt="Logo institusi" class="kop-logo" />` : ''}</div>
            <div class="kop-text">
              ${institution.foundation_name ? `<div class="foundation">${escapeHtml(institution.foundation_name)}</div>` : ''}
              <div class="school">${escapeHtml(institution.name || 'NAMA LEMBAGA')}</div>
              <div class="school-address">${escapeHtml(fullAddress || '-')}</div>
              <div class="school-info">
                NPSN: ${escapeHtml(institution.npsn || '-')}
                ${institution.nss ? ` · ${getNssLabel(institution.level)}: ${escapeHtml(institution.nss)}` : ''}
                ${institution.phone ? ` · Telp: ${escapeHtml(institution.phone)}` : ''}
                ${institution.email ? ` · Email: ${escapeHtml(institution.email)}` : ''}
                ${institution.website ? ` · ${escapeHtml(institution.website)}` : ''}
              </div>
            </div>
            <div></div>
          </div>
        </header>
        
        <div class="header">
          <h1>LAPORAN STATISTIK LEMBAGA</h1>
          <p>Tahun Ajaran: ${reportData.value.academic_year?.name || '-'}</p>
          <p>Periode: ${filters.value.month ? months.find(m => m.value === filters.value.month)?.label : 'Semua'} ${filters.value.year || ''}</p>
        </div>
        
        <div class="section">
          <h3 class="section-title">Ringkasan Eksekutif</h3>
          <div class="summary-box">
            <div class="summary-grid">
              <div class="summary-item">
                <div class="summary-value">${calculatedTotalStudents.value || reportData.value.summary?.total_students || 0}</div>
                <div class="summary-label">Total Siswa</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${reportData.value.summary?.total_teachers || 0}</div>
                <div class="summary-label">Total Guru</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${reportData.value.summary?.total_staff || 0}</div>
                <div class="summary-label">Total Staff</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${reportData.value.summary?.total_classes || 0}</div>
                <div class="summary-label">Total Kelas</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${reportData.value.classes_detail?.total_rombel || 0}</div>
                <div class="summary-label">Total Rombel</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${reportData.value.summary?.total_facilities || 0}</div>
                <div class="summary-label">Sarana Prasarana</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <h3 class="section-title">Identitas Lembaga</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Nama Lembaga</span>
              <span class="info-value">${institution.name}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NPSN</span>
              <span class="info-value">${institution.npsn || '-'}</span>
            </div>
            <div class="info-item">
              <span class="info-label">${getNssLabel(institution.level)}</span>
              <span class="info-value">${institution.nss || '-'}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Level</span>
              <span class="info-value">${institution.level || '-'}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Tipe</span>
              <span class="info-value">${institution.type || '-'}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Alamat</span>
              <span class="info-value">${fullAddress}</span>
            </div>
            <div class="info-item">
              <span class="info-label">${principalLabel}</span>
              <span class="info-value">${institution.principal_name || '-'}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NIP</span>
              <span class="info-value">${institution.principal_nip || '-'}</span>
            </div>
          </div>
        </div>
        
        <div class="section">
          <h3 class="section-title">Data Siswa</h3>
          ${studentsTableHtml}
        </div>
        
        ${reportData.value.students_by_status ? `
        <div class="section">
          <h3 class="section-title">Status Siswa</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Jumlah</th>
                <th>Persentase</th>
              </tr>
            </thead>
            <tbody>
              ${Object.entries(reportData.value.students_by_status).filter(([status]) => status !== 'total').map(([status, count]) => `
              <tr>
                <td><strong>${status}</strong></td>
                <td>${count}</td>
                <td>${reportData.value.students_by_status?.total > 0 ? ((count / reportData.value.students_by_status.total) * 100).toFixed(2) : 0}%</td>
              </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
        ` : ''}
        
        ${reportData.value.classes_detail && reportData.value.classes_detail.total_rombel > 0 ? `
        <div class="section">
          <h3 class="section-title">Rombongan Belajar (Rombel)</h3>
          <div class="info-grid" style="margin-bottom: 12px;">
            <div class="info-item">
              <span class="info-label">Total Rombel</span>
              <span class="info-value">${reportData.value.classes_detail.total_rombel || 0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Rombel</span>
              <span class="info-value">${reportData.value.classes_detail.average_students_per_rombel || 0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Kapasitas per Rombel</span>
              <span class="info-value">${reportData.value.classes_detail.average_capacity || 0}</span>
            </div>
          </div>
          ${Object.entries(reportData.value.classes_detail.by_grade || {}).map(([gradeKey, rombelList]) => `
          <h4 style="margin-top: 16px; margin-bottom: 8px; font-size: 13px; font-weight: bold;">${gradeKey.replace('grade_', 'Kelas ')}</h4>
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama Rombel</th>
                <th>Kode</th>
                <th>Wali Kelas</th>
                <th>Ruangan</th>
                <th>Siswa</th>
                <th>Kapasitas</th>
                <th>Utilisasi</th>
              </tr>
            </thead>
            <tbody>
              ${rombelList.map(rombel => `
              <tr>
                <td>${rombel.name}</td>
                <td>${rombel.code || '-'}</td>
                <td>${rombel.wali_kelas}</td>
                <td>${rombel.room}</td>
                <td>${rombel.students}</td>
                <td>${rombel.capacity || '-'}</td>
                <td>${rombel.utilization}%</td>
              </tr>
              `).join('')}
            </tbody>
          </table>
          `).join('')}
        </div>
        ` : ''}
        
        <div class="section">
          <h3 class="section-title">Data Tenaga Kependidikan</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Kategori</th>
                <th>Laki-laki</th>
                <th>Perempuan</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Guru</strong></td>
                <td>${reportData.value.employees?.teachers_male || 0}</td>
                <td>${reportData.value.employees?.teachers_female || 0}</td>
                <td><strong>${reportData.value.employees?.teachers || 0}</strong></td>
              </tr>
              <tr>
                <td><strong>Tenaga Administrasi/Staff</strong></td>
                <td>${reportData.value.employees?.staff_male || 0}</td>
                <td>${reportData.value.employees?.staff_female || 0}</td>
                <td><strong>${reportData.value.employees?.staff || 0}</strong></td>
              </tr>
              <tr class="total-row">
                <td><strong>Total Tenaga Kependidikan</strong></td>
                <td><strong>${reportData.value.employees?.male || 0}</strong></td>
                <td><strong>${reportData.value.employees?.female || 0}</strong></td>
                <td><strong>${reportData.value.employees?.total || 0}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="section">
          <h3 class="section-title">Sarana Prasarana</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Jumlah Tanah</span>
              <span class="info-value">${reportData.value.facilities.land.count}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Luas Tanah (m²)</span>
              <span class="info-value">${formatNumber(reportData.value.facilities.land.total_area)}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Jumlah Gedung</span>
              <span class="info-value">${reportData.value.facilities.buildings.count}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Luas Gedung (m²)</span>
              <span class="info-value">${formatNumber(reportData.value.facilities.buildings.total_area)}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Ruangan</span>
              <span class="info-value">${reportData.value.facilities.rooms.count}</span>
            </div>
          </div>
          ${roomsTableRows ? `
          <h4 style="margin-top: 16px; font-size: 13px; font-weight: bold;">Ruangan per Jenis</h4>
          <table class="data-table">
            <thead>
              <tr>
                <th>Jenis Ruangan</th>
                <th>Jumlah</th>
              </tr>
            </thead>
            <tbody>
              ${roomsTableRows}
            </tbody>
          </table>
          ` : ''}
        </div>
        
        <div class="section">
          <h3 class="section-title">Rasio dan Indikator</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Rasio Siswa : Guru</span>
              <span class="info-value">${reportData.value.summary?.student_teacher_ratio || 0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Kelas</span>
              <span class="info-value">${reportData.value.summary?.average_students_per_class || 0}</span>
            </div>
            ${reportData.value.summary?.average_students_per_rombel ? `
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Rombel</span>
              <span class="info-value">${reportData.value.summary.average_students_per_rombel}</span>
            </div>
            ` : ''}
          </div>
        </div>
        
        ${reportData.value.comparison ? `
        <div class="section">
          <h3 class="section-title">Perbandingan dengan Tahun Ajaran Sebelumnya</h3>
          <p><strong>Tahun Ajaran Aktif:</strong> ${reportData.value.academic_year?.name || '-'}</p>
          <p><strong>Tahun Ajaran Sebelumnya:</strong> ${reportData.value.comparison.academic_year}</p>
          <table class="data-table">
            <thead>
              <tr>
                <th>Kelas</th>
                <th>${reportData.value.academic_year?.name || 'Tahun Aktif'}</th>
                <th>${reportData.value.comparison.academic_year}</th>
                <th>Selisih</th>
              </tr>
            </thead>
            <tbody>
              ${gradeRange.value.map(grade => `
              <tr>
                <td>Kelas ${grade}</td>
                <td>${reportData.value.students?.[`grade_${grade}`]?.total || 0}</td>
                <td>${reportData.value.comparison.students?.[`grade_${grade}`]?.total || 0}</td>
                <td>${(reportData.value.students?.[`grade_${grade}`]?.total || 0) - (reportData.value.comparison.students?.[`grade_${grade}`]?.total || 0)}</td>
              </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
        ` : ''}
        
        <div class="footer">
          <div class="footer-left">
            <div class="footer-date">
              <strong>Dibuat pada:</strong><br>
              ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
            </div>
          </div>
          <div class="footer-right">
            <div class="footer-date">
              ${institution.district || 'Kota/Kabupaten'}, ${new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
            </div>
            <div class="footer-signature">
              <div class="footer-signature-label">${escapeHtml(principalLabel)}</div>
              <div class="footer-signature-name">${escapeHtml(institution.principal_name || '___________________')}</div>
              <div class="footer-signature-nip">NIP. ${escapeHtml(institution.principal_nip || '___________________')}</div>
            </div>
          </div>
        </div>
      </body>
      </html>
    `
    
    printWindow.document.write(content)
    printWindow.document.close()
    
    setTimeout(() => {
      printWindow.print()
      printWindow.document.title = filename
    }, 250)
  } catch (err) {
    if (import.meta.env.DEV) console.error('Error exporting PDF:', err)
    toast.error('Gagal', 'Gagal mengekspor PDF')
  }
}

onMounted(() => {
  loadReport()
})
</script>

<style scoped>
.report-page {
  width: 100%;
  max-width: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
  min-height: 100%;
}

.tab-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 1.25rem;
}

.tab-header .filters-inline {
  flex: 1;
  margin-bottom: 0;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.header-content h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.filters {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  min-width: 150px;
  background: white;
}

.filter-group input[type="checkbox"] {
  margin-right: 8px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loading-spinner {
  margin-bottom: 16px;
  color: #059669;
}

.dashboard-mini {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-title {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 14px;
  color: #1e293b;
  font-weight: 500;
}

.chart-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.chart-wrapper {
  background: #f8fafc;
  border-radius: 8px;
  padding: 20px;
}

.chart-wrapper h4 {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 16px;
  text-align: center;
}

.chart-wrapper canvas {
  max-height: 300px !important;
}

.data-table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}

.data-table th {
  background: #f1f5f9;
  padding: 12px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 12px;
  font-size: 14px;
  color: #1e293b;
  border-bottom: 1px solid #e2e8f0;
}

.data-table tr:hover {
  background: #f8fafc;
}

.total-row {
  background: #f1f5f9;
  font-weight: 600;
}

.table-period-hint {
  margin-bottom: 8px;
  font-size: 14px;
  color: #64748b;
}

.data-table-student-report th,
.data-table-student-report td {
  text-align: center;
}

.data-table-student-report .col-kls,
.data-table-student-report .col-romb {
  min-width: 48px;
}

.facilities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.facility-card {
  background: #f8fafc;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #e2e8f0;
}

.facility-card h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 16px;
}

.facility-stats {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
}

.facility-stat {
  flex: 1;
  text-align: center;
}

.facility-label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.facility-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #059669;
}

.rooms-by-type {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.rooms-by-type h5 {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 12px;
}

.rooms-table {
  width: 100%;
  border-collapse: collapse;
}

.rooms-table td {
  padding: 8px;
  font-size: 13px;
  border-bottom: 1px solid #e2e8f0;
}

.rooms-table td:first-child {
  color: #64748b;
}

.comparison-container {
  margin-top: 20px;
}

.comparison-chart {
  background: #f8fafc;
  border-radius: 8px;
  padding: 20px;
}

.comparison-chart h4 {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 16px;
  text-align: center;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty-state {
  padding: 24px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.empty-state p {
  margin-bottom: 12px;
  color: #64748b;
  font-weight: 500;
}

.empty-state ul {
  margin: 0;
  padding-left: 24px;
  color: #64748b;
}

.empty-state li {
  margin-bottom: 8px;
}

.chart-placeholder {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}

.ratios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.ratio-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid #e2e8f0;
  transition: transform 0.2s, box-shadow 0.2s;
}

.ratio-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.ratio-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.ratio-content {
  flex: 1;
}

.ratio-value {
  font-size: 32px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 4px;
}

.ratio-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .header-content h2 {
    font-size: 22px;
  }

  .header-actions {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .header-content h2 {
    font-size: 20px;
  }

  .btn-primary {
    padding: 10px 16px;
    font-size: 13px;
  }

  .btn-primary svg {
    width: 18px;
    height: 18px;
  }

  .page-header {
    margin-bottom: 16px;
  }

  .filters {
    padding: 12px;
    margin-bottom: 16px;
    flex-direction: column;
  }

  .filter-select {
    min-width: 0;
    padding: 8px 10px;
    font-size: 13px;
  }

  .filter-group label {
    font-size: 11px;
  }

  .dashboard-mini {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 20px;
  }

  .stat-card {
    padding: 12px 14px;
    gap: 10px;
  }

  .stat-icon {
    width: 44px;
    height: 44px;
  }

  .stat-icon svg {
    width: 20px;
    height: 20px;
  }

  .stat-value {
    font-size: 22px;
  }

  .stat-label {
    font-size: 11px;
  }

  .section {
    padding: 16px;
    margin-bottom: 16px;
  }

  .section-title {
    font-size: 15px;
    margin-bottom: 14px;
    padding-bottom: 8px;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .info-label {
    font-size: 11px;
  }

  .info-value {
    font-size: 13px;
  }

  .chart-container {
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 16px;
  }

  .chart-wrapper {
    padding: 12px;
  }

  .chart-wrapper h4 {
    font-size: 12px;
    margin-bottom: 10px;
  }

  .chart-wrapper canvas {
    max-height: 220px !important;
  }

  .data-table th,
  .data-table td {
    padding: 8px 6px;
    font-size: 12px;
  }

  .table-period-hint {
    font-size: 12px;
  }

  .facilities-grid {
    gap: 12px;
  }

  .facility-card {
    padding: 14px;
  }

  .facility-card h4 {
    font-size: 14px;
    margin-bottom: 12px;
  }

  .facility-value {
    font-size: 18px;
  }

  .facility-stats {
    gap: 12px;
    margin-bottom: 12px;
  }

  .ratios-grid {
    gap: 12px;
  }

  .ratio-card {
    padding: 14px 16px;
    gap: 12px;
  }

  .ratio-icon {
    width: 44px;
    height: 44px;
  }

  .ratio-icon svg {
    width: 20px;
    height: 20px;
  }

  .ratio-value {
    font-size: 22px;
  }

  .ratio-label {
    font-size: 12px;
  }

  .comparison-chart {
    padding: 12px;
  }

  .comparison-chart h4 {
    font-size: 12px;
    margin-bottom: 10px;
  }
}

@media (max-width: 480px) {
  .header-content h2 {
    font-size: 18px;
  }

  .btn-primary {
    padding: 8px 14px;
    font-size: 12px;
  }

  .dashboard-mini {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .stat-card {
    padding: 10px 12px;
  }

  .stat-value {
    font-size: 20px;
  }

  .section {
    padding: 12px;
  }

  .section-title {
    font-size: 14px;
  }

  .data-table th,
  .data-table td {
    padding: 6px 4px;
    font-size: 11px;
  }

  .facility-value {
    font-size: 16px;
  }

  .ratio-value {
    font-size: 20px;
  }
}
</style>
