<template>
  <Layout>
    <div class="institution-page">
      <!-- Super Admin View: List All Institutions -->
      <template v-if="isSuperAdmin">
        <div class="page-header">
          <div class="header-content">
            <div>
              <h2>Daftar Instansi</h2>
              <p>Kelola semua sekolah dan madrasah yang terdaftar</p>
            </div>
            <div class="action-buttons-group">
              <button @click="openAddModal" class="btn-secondary btn-compact btn-add">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Tambah Instansi</span>
              </button>
            </div>
          </div>
        </div>

        <div class="filters filters-inline">
          <input 
            v-model="filters.search" 
            @input="loadInstitutions" 
            placeholder="Cari nama atau NPSN..."
            class="search-input"
          />
          <select v-model="filters.level" @change="loadInstitutions" class="filter-select">
            <option value="">Semua Jenjang</option>
            <option value="TK">TK</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="SMK">SMK</option>
            <option value="MA">MA</option>
            <option value="MAK">MAK</option>
            <option value="MTs">MTs</option>
            <option value="MI">MI</option>
            <option value="PAUD">PAUD</option>
          </select>
          <select v-model="filters.type" @change="loadInstitutions" class="filter-select">
            <option value="">Semua Status</option>
            <option value="Negeri">Negeri</option>
            <option value="Swasta">Swasta</option>
          </select>
          <select v-model="filters.is_active" @change="loadInstitutions" class="filter-select">
            <option value="">Semua Status Aktif</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif (dibekukan)</option>
          </select>
        </div>

        <div v-if="loading" class="loading-wrap">
          <LoadingSkeleton type="table" :rows="6" :columns="7" :cell-widths="['100px', '1fr', '80px', '80px', '1fr', '120px', '120px']" />
        </div>
        
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>NPSN</th>
                <th>Nama Sekolah/Madrasah</th>
                <th>Jenjang</th>
                <th>Jenis</th>
                <th>Status Operasional</th>
                <th>Alamat</th>
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inst in institutions" :key="inst.id" :class="{ 'row-inactive': inst.is_active === false }">
                <td>{{ displayValue(inst.npsn) }}</td>
                <td>{{ inst.name }}</td>
                <td>{{ displayValue(inst.level) }}</td>
                <td>{{ inst.type }}</td>
                <td>
                  <span :class="inst.is_active !== false ? 'status-active' : 'status-inactive'">
                    {{ inst.is_active !== false ? 'Aktif' : 'Dibekukan' }}
                  </span>
                </td>
                <td>{{ displayValue(inst.address) }}</td>
                <td>{{ formatDate(inst.created_at) }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="viewInstitution(inst)" class="btn-action btn-view" title="Lihat Detail">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="editInstitution(inst)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.50023C18.8978 2.10243 19.4374 1.87891 20 1.87891C20.5626 1.87891 21.1022 2.10243 21.5 2.50023C21.8978 2.89804 22.1213 3.43762 22.1213 4.00023C22.1213 4.56284 21.8978 5.10243 21.5 5.50023L12 15.0002L8 16.0002L9 12.0002L18.5 2.50023Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button
                      @click="toggleInstitutionStatus(inst)"
                      class="btn-action"
                      :class="inst.is_active !== false ? 'btn-suspend' : 'btn-activate'"
                      :title="inst.is_active !== false ? 'Bekukan institusi' : 'Aktifkan institusi'"
                      :disabled="statusTogglingId === inst.id"
                    >
                      <svg v-if="inst.is_active !== false" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                      <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01L9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteInstitution(inst.id)" class="btn-action btn-delete" title="Hapus">
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

          <div v-if="institutions.length === 0" class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Tidak ada data instansi</h3>
            <p>Mulai dengan menambahkan sekolah atau madrasah baru</p>
            <button @click="openAddModal" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Instansi</span>
            </button>
          </div>
        </div>
      </template>

      <!-- Regular Admin View: Institution Profile -->
      <template v-else>
      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="card" :lines="5" :line-widths="['60%', '100%', '80%', '70%', '90%']" />
      </div>
      
      <div v-else-if="institution" class="institution-card">
        <div class="institution-card-header">
          <button @click="openEditModal" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Edit Profil</span>
          </button>
        </div>

        <div class="info-section">
          <h3>Informasi Umum</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Nama {{ institutionTypeLabel }}</label>
              <div style="display: flex; align-items: center; gap: 8px;">
                <p :class="{ 'text-empty': !institution.name || !String(institution.name).trim() }">{{ displayValue(institution.name) }}</p>
                <button 
                  v-if="!isSuperAdmin && pendingRequests.name" 
                  @click="showRequestModal('name')" 
                  class="btn-request-badge"
                  title="Ada request pending"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </button>
                <button 
                  v-if="!isSuperAdmin && !pendingRequests.name" 
                  @click="showRequestModal('name')" 
                  class="btn-request-change"
                  title="Request perubahan"
                >
                  Ubah
                </button>
              </div>
            </div>
            <div class="info-item">
              <label>Nama Yayasan</label>
              <p :class="{ 'text-empty': !institution.foundation_name || !String(institution.foundation_name).trim() }">{{ displayValue(institution.foundation_name) }}</p>
            </div>
            <div class="info-item">
              <label>NPSN</label>
              <div style="display: flex; align-items: center; gap: 8px;">
                <p :class="{ 'text-empty': !institution.npsn || !String(institution.npsn).trim() }">{{ displayValue(institution.npsn) }}</p>
                <button 
                  v-if="!isSuperAdmin && pendingRequests.npsn" 
                  @click="showRequestModal('npsn')" 
                  class="btn-request-badge"
                  title="Ada request pending"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </button>
                <button 
                  v-if="!isSuperAdmin && !pendingRequests.npsn" 
                  @click="showRequestModal('npsn')" 
                  class="btn-request-change"
                  title="Request perubahan"
                >
                  Ubah
                </button>
              </div>
            </div>
            <div class="info-item">
              <label>{{ nssLabel }}</label>
              <p :class="{ 'text-empty': !institution.nss || !String(institution.nss).trim() }">{{ displayValue(institution.nss) }}</p>
            </div>
            <div class="info-item">
              <label>Jenjang</label>
              <p :class="{ 'text-empty': !institution.level || !String(institution.level).trim() }">{{ displayValue(institution.level) }}</p>
            </div>
            <div class="info-item">
              <label>Status</label>
              <p>{{ institution.type }}</p>
            </div>
            <div class="info-item">
              <label>Status Aktif</label>
              <p><span :class="institution.is_active !== false ? 'status-active' : 'status-inactive'">{{ institution.is_active !== false ? 'Aktif' : 'Nonaktif' }}</span></p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Alamat</h3>
          <div class="info-grid">
            <div class="info-item full-width">
              <label>Alamat Lengkap</label>
              <p :class="{ 'text-empty': !institution.address || !String(institution.address).trim() }">{{ displayValue(institution.address) }}</p>
            </div>
            <div class="info-item">
              <label>Desa/Kelurahan</label>
              <p :class="{ 'text-empty': !institution.village || !String(institution.village).trim() }">{{ displayValue(institution.village) }}</p>
            </div>
            <div class="info-item">
              <label>Kecamatan</label>
              <p :class="{ 'text-empty': !institution.sub_district || !String(institution.sub_district).trim() }">{{ displayValue(institution.sub_district) }}</p>
            </div>
            <div class="info-item">
              <label>Kabupaten/Kota</label>
              <p :class="{ 'text-empty': !institution.district || !String(institution.district).trim() }">{{ displayValue(institution.district) }}</p>
            </div>
            <div class="info-item">
              <label>Provinsi</label>
              <p :class="{ 'text-empty': !institution.province || !String(institution.province).trim() }">{{ displayValue(institution.province) }}</p>
            </div>
            <div class="info-item">
              <label>Kode Kabupaten/Kota (nomor peserta)</label>
              <p :class="{ 'text-empty': !institution.district_code }">{{ displayValue(institution.district_code) || '–' }}</p>
            </div>
            <div class="info-item">
              <label>Kode Provinsi (nomor peserta)</label>
              <p :class="{ 'text-empty': !institution.province_code }">{{ displayValue(institution.province_code) || '–' }}</p>
            </div>
            <div class="info-item">
              <label>Kode Pos</label>
              <p :class="{ 'text-empty': !institution.postal_code || !String(institution.postal_code).trim() }">{{ displayValue(institution.postal_code) }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Lokasi (untuk absensi)</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Latitude</label>
              <p :class="{ 'text-empty': institution.latitude == null || institution.latitude === '' }">{{ institution.latitude != null ? institution.latitude : 'Belum ada data' }}</p>
            </div>
            <div class="info-item">
              <label>Longitude</label>
              <p :class="{ 'text-empty': institution.longitude == null || institution.longitude === '' }">{{ institution.longitude != null ? institution.longitude : 'Belum ada data' }}</p>
            </div>
            <div class="info-item">
              <label>Radius (meter)</label>
              <p :class="{ 'text-empty': institution.location_radius == null || institution.location_radius === '' }">{{ institution.location_radius != null ? institution.location_radius : 'Belum ada data' }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Kontak</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Telepon</label>
              <p :class="{ 'text-empty': !institution.phone || !String(institution.phone).trim() }">{{ displayValue(institution.phone) }}</p>
            </div>
            <div class="info-item">
              <label>Email</label>
              <p :class="{ 'text-empty': !institution.email || !String(institution.email).trim() }">{{ displayValue(institution.email) }}</p>
            </div>
            <div class="info-item">
              <label>Website</label>
              <p :class="{ 'text-empty': !institution.website || !String(institution.website).trim() }">{{ displayValue(institution.website) }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Kepala {{ institutionTypeLabel }}</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Nama</label>
              <p :class="{ 'text-empty': !institution.principal_name || !String(institution.principal_name).trim() }">{{ displayValue(institution.principal_name) }}</p>
            </div>
            <div class="info-item">
              <label>NIP</label>
              <p :class="{ 'text-empty': !institution.principal_nip || !String(institution.principal_nip).trim() }">{{ displayValue(institution.principal_nip) }}</p>
            </div>
          </div>
        </div>

        <div v-if="institution.description" class="info-section">
          <h3>Deskripsi</h3>
          <p>{{ institution.description }}</p>
        </div>

        <div class="info-section">
          <h3>Visi & Misi (Halaman Publik)</h3>
          <div class="info-grid">
            <div class="info-item info-item-full">
              <label>Visi</label>
              <p :class="{ 'text-empty': !institution.vision }">{{ institution.vision || 'Belum diisi' }}</p>
            </div>
            <div class="info-item info-item-full">
              <label>Misi</label>
              <p :class="{ 'text-empty': !institution.mission }">{{ institution.mission || 'Belum diisi' }}</p>
            </div>
          </div>
        </div>

        <div class="info-section">
          <h3>Logo {{ institutionTypeLabel }}</h3>
          <div class="logo-section">
            <div v-if="institution.logo" class="logo-preview">
              <img :src="institution.logo" :alt="`Logo ${institutionTypeLabel}`" />
            </div>
            <div v-else class="logo-placeholder">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L22 16M2 20H22M3 4H21C21.5523 4 22 4.44772 22 5V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V5C2 4.44772 2.44772 4 3 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <p>Belum ada logo</p>
            </div>
            <button @click="triggerLogoUpload" :disabled="uploadingLogo" class="btn-primary" style="margin-top: 16px;">
              <svg v-if="!uploadingLogo" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>{{ uploadingLogo ? 'Mengunggah...' : (institution.logo ? 'Ganti Logo' : 'Unggah Logo') }}</span>
            </button>
            <input 
              ref="logoInput" 
              type="file" 
              accept="image/*" 
              @change="handleLogoUpload" 
              style="display: none"
            />
          </div>
        </div>

        <div class="info-section">
          <h3>Gambar Cover / Hero (Halaman Publik)</h3>
          <p class="form-hint" style="margin-bottom: 12px;">Ditampilkan di bagian atas halaman profil {{ institutionTypeLabel.toLowerCase() }} publik.</p>
          <div class="logo-section">
            <div v-if="institution.cover_image" class="logo-preview">
              <img :src="institution.cover_image" alt="Cover Hero" style="object-fit: cover;" />
            </div>
            <div v-else class="logo-placeholder">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L22 16M2 20H22M3 4H21C21.5523 4 22 4.44772 22 5V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V5C2 4.44772 2.44772 4 3 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <p>Belum ada gambar cover</p>
            </div>
            <button @click="triggerCoverUpload" :disabled="uploadingCover" class="btn-primary" style="margin-top: 16px;">
              <svg v-if="!uploadingCover" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>{{ uploadingCover ? 'Mengunggah...' : (institution.cover_image ? 'Ganti Cover' : 'Unggah Gambar Cover') }}</span>
            </button>
            <input 
              ref="coverInput" 
              type="file" 
              accept="image/*" 
              @change="handleCoverUpload" 
              style="display: none"
            />
          </div>
          <div v-if="coverUploadError" class="error-message" style="margin-top: 12px;">{{ coverUploadError }}</div>
        </div>

        <div class="info-section">
          <h3>Tahun Ajaran & Semester Aktif</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Tahun Ajaran Aktif</label>
              <p v-if="institution.active_academic_year">{{ institution.active_academic_year.code }} - {{ institution.active_academic_year.name || '-' }}</p>
              <p v-else class="text-muted">Belum dipilih</p>
            </div>
            <div class="info-item">
              <label>Semester Aktif</label>
              <p v-if="institution.active_semester">{{ institution.active_semester.name }}</p>
              <p v-else class="text-muted">Belum dipilih</p>
            </div>
          </div>
          <div v-if="!isSuperAdmin" style="margin-top: 16px;">
            <button @click="openAcademicYearModal" class="btn-primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Pilih Tahun Ajaran & Semester</span>
            </button>
          </div>
        </div>

      </div>
      </template>

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Edit Profil {{ institutionTypeLabel }}</h3>
            <button @click="closeEditModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleUpdate" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama {{ institutionTypeLabel }}</label>
                <input 
                  v-model="form.name" 
                  :disabled="!isSuperAdmin"
                />
                <small v-if="!isSuperAdmin" class="form-hint">
                  Perubahan nama {{ institutionTypeLabel.toLowerCase() }} memerlukan persetujuan super admin. Gunakan tombol "Ubah" di profil untuk request perubahan.
                </small>
              </div>
              <div class="form-group">
                <label>Nama Yayasan</label>
                <input v-model="form.foundation_name" placeholder="Contoh: Yayasan Pendidikan ..." />
                <small class="form-hint">Tampil di baris atas kop laporan cetak (opsional).</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>NPSN</label>
                <input 
                  v-model="form.npsn" 
                  :disabled="!isSuperAdmin"
                />
                <small v-if="!isSuperAdmin" class="form-hint">
                  Perubahan NPSN memerlukan persetujuan super admin. Gunakan tombol "Ubah" di profil untuk request perubahan.
                </small>
              </div>
              <div class="form-group">
                <label>{{ formNssLabel }}</label>
                <input v-model="form.nss" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenjang</label>
                <select v-model="form.level">
                  <option value="">Pilih Jenjang</option>
                  <option value="TK">TK</option>
                  <option value="SD">SD</option>
                  <option value="SMP">SMP</option>
                  <option value="SMA">SMA</option>
                  <option value="SMK">SMK</option>
                  <option value="MA">MA</option>
                  <option value="MAK">MAK</option>
                  <option value="MTs">MTs</option>
                  <option value="MI">MI</option>
                  <option value="PAUD">PAUD</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status</label>
                <select v-model="form.type">
                  <option value="Swasta">Swasta</option>
                  <option value="Negeri">Negeri</option>
                </select>
              </div>
            </div>

            <div class="form-row" v-if="isSuperAdmin">
              <div class="form-group">
                <label>Status Aktif</label>
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.is_active" />
                  <span>Instansi aktif</span>
                </label>
                <small class="form-hint">Nonaktifkan untuk membekukan: user instansi tidak dapat login.</small>
              </div>
            </div>

            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="form.address" rows="3"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Desa/Kelurahan</label>
                <input v-model="form.village" />
              </div>
              <div class="form-group">
                <label>Kecamatan</label>
                <input v-model="form.sub_district" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kabupaten/Kota</label>
                <input v-model="form.district" />
              </div>
              <div class="form-group">
                <label>Provinsi</label>
                <input v-model="form.province" />
              </div>
            </div>
            <div class="form-section-label">Kode untuk nomor peserta ujian (format YY-PP-KK-J-SSSS-NNN)</div>
            <div class="form-row">
              <div class="form-group">
                <label>Kode Kabupaten/Kota (KK, 2 digit)</label>
                <input v-model="form.district_code" type="text" maxlength="2" placeholder="Contoh: 01" />
                <small class="form-hint">2 digit untuk nomor peserta ujian</small>
              </div>
              <div class="form-group">
                <label>Kode Provinsi (PP, 2 digit)</label>
                <input v-model="form.province_code" type="text" maxlength="2" placeholder="Contoh: 32" />
                <small class="form-hint">2 digit untuk nomor peserta ujian</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kode Pos</label>
                <input v-model="form.postal_code" />
              </div>
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="form.email" />
              </div>
              <div class="form-group">
                <label>Website</label>
                <input v-model="form.website" placeholder="contoh: sekolah.sch.id" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Kepala {{ institutionTypeLabel }}</label>
                <input v-model="form.principal_name" />
              </div>
              <div class="form-group">
                <label>NIP Kepala {{ institutionTypeLabel }}</label>
                <input v-model="form.principal_nip" />
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="4"></textarea>
            </div>

            <div class="form-group">
              <label>Visi (halaman publik)</label>
              <textarea v-model="form.vision" rows="3" placeholder="Contoh: Menjadi sekolah unggul..."></textarea>
            </div>
            <div class="form-group">
              <label>Misi (halaman publik)</label>
              <textarea v-model="form.mission" rows="4" placeholder="Satu atau beberapa poin misi sekolah"></textarea>
            </div>

            <div class="form-section-label">Lokasi (untuk absensi)</div>
            <div class="form-row">
              <div class="form-group">
                <label>Latitude</label>
                <input v-model.number="form.latitude" type="number" step="any" placeholder="-6.xxxx" />
                <small class="form-hint">Contoh: -6.2088</small>
              </div>
              <div class="form-group">
                <label>Longitude</label>
                <input v-model.number="form.longitude" type="number" step="any" placeholder="106.xxxx" />
                <small class="form-hint">Contoh: 106.8456</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Radius (meter)</label>
                <input v-model.number="form.location_radius" type="number" min="10" max="5000" placeholder="100" />
                <small class="form-hint">Radius validasi lokasi absensi (10–5000 m)</small>
              </div>
            </div>

            <div class="form-group">
              <label>Logo {{ institutionTypeLabel }}</label>
              <div class="logo-upload-section">
                <div v-if="institution?.logo" class="logo-preview-small">
                  <img :src="institution.logo" :alt="`Logo ${institutionTypeLabel}`" />
                </div>
                <div v-else class="logo-placeholder-small">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M14 14L15.586 12.414C16.367 11.633 17.633 11.633 18.414 12.414L22 16M2 20H22M3 4H21C21.5523 4 22 4.44772 22 5V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V5C2 4.44772 2.44772 4 3 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <button type="button" @click="triggerLogoUpload" :disabled="uploadingLogo" class="btn-secondary" style="margin-top: 8px;">
                  {{ uploadingLogo ? 'Mengunggah...' : (institution?.logo ? 'Ganti Logo' : 'Unggah Logo') }}
                </button>
                <input 
                  ref="logoInput" 
                  type="file" 
                  accept="image/*" 
                  @change="handleLogoUpload" 
                  style="display: none"
                />
              </div>
              <small class="form-hint">Format: JPG, PNG, GIF, SVG (Maks. 2MB)</small>
            </div>

            <div class="form-group">
              <label>Gambar Cover / Hero (halaman publik)</label>
              <div class="logo-upload-section">
                <div v-if="institution?.cover_image" class="logo-preview-small">
                  <img :src="institution.cover_image" alt="Cover" style="object-fit: cover;" />
                </div>
                <div v-else class="logo-placeholder-small">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 16L8.586 11.414C9.367 10.633 10.633 10.633 11.414 11.414L16 16M2 20H22M3 4H21C21.5523 4 22 4.44772 22 5V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V5C2 4.44772 2.44772 4 3 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <button type="button" @click="triggerCoverUpload" :disabled="uploadingCover" class="btn-secondary" style="margin-top: 8px;">
                  {{ uploadingCover ? 'Mengunggah...' : (institution?.cover_image ? 'Ganti Cover' : 'Unggah Cover') }}
                </button>
                <input ref="coverInput" type="file" accept="image/*" @change="handleCoverUpload" style="display: none" />
              </div>
              <small class="form-hint">Ditampilkan di hero halaman publik. Maks. 5MB.</small>
              <div v-if="coverUploadError" class="error-message" style="margin-top: 8px;">{{ coverUploadError }}</div>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeEditModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="updating" class="btn-primary">
                {{ updating ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Request Change Modal -->
      <div v-if="showRequestChangeModal" class="modal-overlay" @click="showRequestChangeModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Request Perubahan {{ requestField === 'name' ? `Nama ${institutionTypeLabel}` : 'NPSN' }}</h3>
            <button @click="showRequestChangeModal = false" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleRequestChange" class="modal-body">
            <div class="form-group">
              <label>Nilai Saat Ini</label>
              <input :value="institution[requestField] ? institution[requestField] : 'Belum ada data'" disabled />
            </div>
            
            <div class="form-group">
              <label>Nilai Baru *</label>
              <input 
                v-model="requestForm.newValue" 
                :placeholder="requestField === 'npsn' ? 'Masukkan 8 digit NPSN' : `Masukkan nama ${institutionTypeLabel.toLowerCase()} baru`"
                :maxlength="requestField === 'npsn' ? 8 : 255"
                required 
              />
              <small class="form-hint">
                {{ requestField === 'npsn' ? 'NPSN harus terdiri dari 8 digit angka' : `Nama ${institutionTypeLabel.toLowerCase()} maksimal 255 karakter` }}
              </small>
            </div>

            <div v-if="requestError" class="error-message">{{ requestError }}</div>

            <div class="modal-footer">
              <button type="button" @click="showRequestChangeModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="requesting" class="btn-primary">
                {{ requesting ? 'Mengirim...' : 'Kirim Request' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add/Edit Institution Modal (Super Admin) -->
      <div v-if="showAddModal || showEditModalSuperAdmin" class="modal-overlay" @click="closeSuperAdminModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ showEditModalSuperAdmin ? 'Edit' : 'Tambah' }} Sekolah/Madrasah</h3>
            <button @click="closeSuperAdminModal" class="btn-close">×</button>
          </div>
          
          <div v-if="showEditModalSuperAdmin && loadingEditDetail" class="modal-body" style="text-align: center; padding: 2rem;">
            <span>Memuat data...</span>
          </div>
          <form v-else @submit.prevent="showEditModalSuperAdmin ? handleUpdateSuperAdmin() : handleAddInstitution()" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Sekolah/Madrasah <span v-if="!showEditModalSuperAdmin">*</span></label>
                <input v-model="form.name" :required="!showEditModalSuperAdmin" />
              </div>
              <div class="form-group">
                <label>Nama Yayasan</label>
                <input v-model="form.foundation_name" placeholder="Contoh: Yayasan Pendidikan ..." />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>NPSN</label>
                <input v-model="form.npsn" maxlength="8" />
              </div>
              <div class="form-group">
                <label>{{ formNssLabel }}</label>
                <input v-model="form.nss" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Jenjang</label>
                <select v-model="form.level">
                  <option value="">Pilih Jenjang</option>
                  <option value="TK">TK</option>
                  <option value="SD">SD</option>
                  <option value="SMP">SMP</option>
                  <option value="SMA">SMA</option>
                  <option value="SMK">SMK</option>
                  <option value="MA">MA</option>
                  <option value="MAK">MAK</option>
                  <option value="MTs">MTs</option>
                  <option value="MI">MI</option>
                  <option value="PAUD">PAUD</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Status <span v-if="!showEditModalSuperAdmin">*</span></label>
                <select v-model="form.type" :required="!showEditModalSuperAdmin">
                  <option value="Swasta">Swasta</option>
                  <option value="Negeri">Negeri</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status Aktif</label>
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.is_active" />
                  <span>Instansi aktif</span>
                </label>
                <small class="form-hint">Nonaktifkan untuk membekukan: user instansi tidak dapat login.</small>
              </div>
            </div>

            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="form.address" rows="3"></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Desa/Kelurahan</label>
                <input v-model="form.village" />
              </div>
              <div class="form-group">
                <label>Kecamatan</label>
                <input v-model="form.sub_district" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kabupaten/Kota</label>
                <input v-model="form.district" />
              </div>
              <div class="form-group">
                <label>Provinsi</label>
                <input v-model="form.province" />
              </div>
            </div>
            <div class="form-section-label">Kode untuk nomor peserta ujian (format YY-PP-KK-J-SSSS-NNN)</div>
            <div class="form-row">
              <div class="form-group">
                <label>Kode Kabupaten/Kota (KK, 2 digit)</label>
                <input v-model="form.district_code" type="text" maxlength="2" placeholder="Contoh: 01" />
                <small class="form-hint">2 digit untuk nomor peserta ujian</small>
              </div>
              <div class="form-group">
                <label>Kode Provinsi (PP, 2 digit)</label>
                <input v-model="form.province_code" type="text" maxlength="2" placeholder="Contoh: 32" />
                <small class="form-hint">2 digit untuk nomor peserta ujian</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kode Pos</label>
                <input v-model="form.postal_code" />
              </div>
              <div class="form-group">
                <label>Telepon</label>
                <input v-model="form.phone" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="form.email" />
              </div>
              <div class="form-group">
                <label>Website</label>
                <input v-model="form.website" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Kepala {{ institutionTypeLabel }}</label>
                <input v-model="form.principal_name" />
              </div>
              <div class="form-group">
                <label>NIP Kepala {{ institutionTypeLabel }}</label>
                <input v-model="form.principal_nip" />
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="form.description" rows="4"></textarea>
            </div>

            <div class="form-group">
              <label>Visi (halaman publik)</label>
              <textarea v-model="form.vision" rows="3" placeholder="Contoh: Menjadi sekolah unggul..."></textarea>
            </div>
            <div class="form-group">
              <label>Misi (halaman publik)</label>
              <textarea v-model="form.mission" rows="4" placeholder="Satu atau beberapa poin misi sekolah"></textarea>
            </div>

            <div class="form-section-label">Lokasi (untuk absensi)</div>
            <div class="form-row">
              <div class="form-group">
                <label>Latitude</label>
                <input v-model.number="form.latitude" type="number" step="any" placeholder="-6.xxxx" />
                <small class="form-hint">Contoh: -6.2088</small>
              </div>
              <div class="form-group">
                <label>Longitude</label>
                <input v-model.number="form.longitude" type="number" step="any" placeholder="106.xxxx" />
                <small class="form-hint">Contoh: 106.8456</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Radius (meter)</label>
                <input v-model.number="form.location_radius" type="number" min="10" max="5000" placeholder="100" />
                <small class="form-hint">Radius validasi lokasi absensi (10–5000 m)</small>
              </div>
            </div>

            <div v-if="error" class="error-message">{{ error }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeSuperAdminModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="updating" class="btn-primary">
                {{ updating ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Academic Year & Semester Selection Modal -->
      <div v-if="showAcademicYearModal" class="modal-overlay" @click="showAcademicYearModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Pilih Tahun Ajaran & Semester</h3>
            <button @click="showAcademicYearModal = false" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="handleUpdateAcademicYear" class="modal-body">
            <div class="form-group">
              <label>Tahun Ajaran *</label>
              <select 
                v-model="academicYearForm.active_academic_year_id" 
                @change="handleAcademicYearChange"
                required
                class="form-input"
              >
                <option value="">Pilih Tahun Ajaran</option>
                <option 
                  v-for="year in academicYears" 
                  :key="year.id" 
                  :value="year.id"
                >
                  {{ year.code }} - {{ year.name || '-' }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Semester *</label>
              <select 
                v-model="academicYearForm.semester_name" 
                :disabled="!academicYearForm.active_academic_year_id"
                required
                class="form-input"
              >
                <option value="">Pilih Semester</option>
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
              </select>
              <small v-if="!academicYearForm.active_academic_year_id" class="form-hint">
                Pilih tahun ajaran terlebih dahulu
              </small>
            </div>

            <div v-if="academicYearError" class="error-message">{{ academicYearError }}</div>

            <div class="modal-footer">
              <button type="button" @click="showAcademicYearModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="updatingAcademicYear" class="btn-primary">
                {{ updatingAcademicYear ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
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
  </Layout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { institutionApi } from '@/api/institution'
import { institutionChangeRequestApi } from '@/api/institutionChangeRequest'
import { useReferenceDataStore } from '@/stores/referenceData'
import { semesterApi } from '@/api/semester'
import { validators, validateForm } from '@/utils/validation'
import { useFormValidation } from '@/composables/useFormValidation'
import { getInstitutionTypeLabel, getNssLabel } from '@/utils/institution'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'

const toast = useToast()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const referenceStore = useReferenceDataStore()
const academicYears = computed(() => referenceStore.academicYears)
const { confirmDialog, showConfirm, handleConfirm, handleCancel, setLoading: setDeleteLoading } = useConfirmDelete()

const institution = ref(null)
const loading = ref(true)
const showEditModal = ref(false)
const updating = ref(false)
const deleteLoading = ref(false)
const error = ref('')
const showRequestChangeModal = ref(false)
const requestField = ref('')
const requesting = ref(false)
const requestError = ref('')
const pendingRequests = ref({ name: null, npsn: null })
const showAcademicYearModal = ref(false)
const updatingAcademicYear = ref(false)
const academicYearError = ref('')
const logoInput = ref(null)
const coverInput = ref(null)
const uploadingLogo = ref(false)
const uploadingCover = ref(false)
const coverUploadError = ref('')

const isSuperAdmin = computed(() => authStore.user?.role === 'super_admin')

// Computed untuk mendapatkan label jenis instansi
const institutionTypeLabel = computed(() => {
  if (isSuperAdmin.value) return 'Instansi'
  return getInstitutionTypeLabel(institution.value?.level)
})

const nssLabel = computed(() => getNssLabel(institution.value?.level))
const formNssLabel = computed(() => getNssLabel(form.value.level))

const displayValue = (val) => (val && String(val).trim() !== '') ? val : 'Belum ada data'

// Super Admin state
const institutions = ref([])
const filters = ref({
  search: '',
  level: '',
  type: '',
  is_active: ''
})
const showAddModal = ref(false)
const showEditModalSuperAdmin = ref(false)
const selectedInstitution = ref(null)
const loadingEditDetail = ref(false)
const statusTogglingId = ref(null)

const requestForm = ref({
  newValue: ''
})

const academicYearForm = ref({
  active_academic_year_id: null,
  semester_name: '' // Store semester name instead of ID
})

const form = ref({
  name: '',
  foundation_name: '',
  npsn: '',
  nss: '',
  level: '',
  type: 'Swasta',
  address: '',
  village: '',
  sub_district: '',
  district: '',
  province: '',
  province_code: '',
  district_code: '',
  postal_code: '',
  phone: '',
  email: '',
  website: '',
  principal_name: '',
  principal_nip: '',
  description: '',
  vision: '',
  mission: '',
  is_active: true,
  latitude: null,
  longitude: null,
  location_radius: null
})

const loadInstitution = async () => {
  loading.value = true
  try {
    const response = await institutionApi.getMy()
    // Response structure: { data: { id, name, npsn, ... } }
    institution.value = response.data.data || response.data
    
    // Ensure address data is properly loaded
    if (institution.value && !institution.value.address && form.value.address) {
      // If address exists in form but not in institution, sync it
      institution.value.address = form.value.address
    }
    
    // Jika email institution kosong, ambil dari user email
    if (!institution.value.email && authStore.user?.email) {
      institution.value.email = authStore.user.email
    }
    
    Object.assign(form.value, institution.value)
    
    // Pastikan email terisi di form
    if (!form.value.email && authStore.user?.email) {
      form.value.email = authStore.user.email
    }
    
    await loadPendingRequests()
  } catch (err) {
    error.value = 'Gagal memuat data institusi'
    toast.error('Gagal', 'Gagal memuat data institusi')
    console.error('Error loading institution:', err)
  } finally {
    loading.value = false
  }
}

const loadPendingRequests = async () => {
  if (isSuperAdmin.value) return
  
  try {
    const response = await institutionChangeRequestApi.getAll({ status: 'pending' })
    const requests = response.data.data || []
    
    pendingRequests.value = {
      name: requests.find(r => r.field_name === 'name') || null,
      npsn: requests.find(r => r.field_name === 'npsn') || null
    }
  } catch (err) {
    console.error('Failed to load pending requests:', err)
  }
}

const openEditModal = () => {
  // Reset form dengan data terbaru dari institution
  if (institution.value) {
    Object.assign(form.value, institution.value)
    
    // Pastikan email terisi jika kosong
    if (!form.value.email && authStore.user?.email) {
      form.value.email = authStore.user.email
    }
  }
  error.value = ''
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  error.value = ''
  // Reset form ke data institution yang sebenarnya
  if (institution.value) {
    Object.assign(form.value, institution.value)
  }
}

const openAddModal = () => {
  resetForm()
  showAddModal.value = true
}

const closeSuperAdminModal = () => {
  showAddModal.value = false
  showEditModalSuperAdmin.value = false
  resetForm()
}

const showRequestModal = (field) => {
  requestField.value = field
  requestForm.value.newValue = ''
  requestError.value = ''
  showRequestChangeModal.value = true
}

const handleRequestChange = async () => {
  requestError.value = ''
  
  // Validate
  if (!requestForm.value.newValue) {
    requestError.value = 'Nilai baru wajib diisi'
    return
  }
  
  if (requestField.value === 'npsn') {
    if (!/^\d{8}$/.test(requestForm.value.newValue)) {
      requestError.value = 'NPSN harus terdiri dari 8 digit angka'
      return
    }
  }
  
  if (requestForm.value.newValue === institution.value[requestField.value]) {
    requestError.value = 'Nilai baru harus berbeda dengan nilai saat ini'
    return
  }
  
  requesting.value = true
  
  try {
    await institutionChangeRequestApi.create({
      field_name: requestField.value,
      new_value: requestForm.value.newValue
    })
    
    toast.success('Berhasil', 'Request perubahan berhasil dikirim. Menunggu persetujuan super admin.')
    showRequestChangeModal.value = false
    await loadPendingRequests()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal mengirim request'
    requestError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    requesting.value = false
  }
}

const getValidationRules = (mode = 'update') => {
  const isCreate = mode === 'create'
  const optionalWebsite = (value) => {
    if (!value || !String(value).trim()) return null
    const v = String(value).trim()
    if (/^https?:\/\//i.test(v)) {
      try {
        new URL(v)
        return null
      } catch {
        // Edit: URL belum lengkap jangan blokir
        return isCreate ? 'Format URL tidak valid' : null
      }
    }
    // Izinkan domain tanpa protokol
    if (/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(\/.*)?$/i.test(v)) {
      return null
    }
    // Edit bertahap: teks belum berbentuk domain lengkap tidak memblokir simpan
    return isCreate ? 'Format website tidak valid' : null
  }
  const optionalPhone = (value) => {
    if (!value || !String(value).trim()) return null
    const digits = String(value).replace(/[^0-9]/g, '')
    // Edit bertahap: nomor belum lengkap tidak memblokir simpan
    if (!isCreate && digits.length > 0 && digits.length < 8) return null
    if (digits.length < 8 || digits.length > 15) {
      return 'Nomor telepon harus 8–15 digit'
    }
    return null
  }
  const optionalNpsn = (value) => {
    if (!value || !String(value).trim()) return null
    const digits = String(value).replace(/\D/g, '')
    // Edit: NPSN belum 8 digit dianggap belum selesai diisi → tidak error, tidak dikirim
    if (!isCreate && digits.length > 0 && digits.length < 8) return null
    return validators.npsn(value, 'NPSN harus terdiri dari 8 digit angka')
  }
  const optionalEmail = (value) => {
    if (!value || !String(value).trim()) return null
    const v = String(value).trim()
    // Edit: email belum lengkap (belum ada @) jangan blokir simpan
    if (!isCreate && !v.includes('@')) return null
    return validators.email(v, 'Format email tidak valid')
  }

  const rules = {
    npsn: [optionalNpsn],
    email: [
      optionalEmail,
      (value) => value ? validators.maxLength(value, 255, 'Email maksimal 255 karakter') : null
    ],
    website: [optionalWebsite],
    phone: [optionalPhone]
  }

  if (isCreate) {
    rules.name = [
      (value) => validators.required(value, `Nama ${institutionTypeLabel.value.toLowerCase()} wajib diisi`),
      (value) => validators.maxLength(value, 255, `Nama ${institutionTypeLabel.value.toLowerCase()} maksimal 255 karakter`)
    ]
    rules.type = [
      (value) => validators.required(value, 'Status institusi wajib diisi')
    ]
  } else {
    // Edit: field opsional boleh kosong; hanya cek format jika terisi
    rules.name = [
      (value) => value ? validators.maxLength(value, 255, `Nama ${institutionTypeLabel.value.toLowerCase()} maksimal 255 karakter`) : null
    ]
    rules.type = [
      (value) => {
        if (!value) return null
        return ['Negeri', 'Swasta'].includes(value) ? null : 'Status institusi harus Negeri atau Swasta'
      }
    ]
  }

  return rules
}

const INSTITUTION_PAYLOAD_KEYS = [
  'name', 'foundation_name', 'npsn', 'nss', 'level', 'type', 'address', 'village', 'sub_district', 'district', 'province',
  'province_code', 'district_code',
  'postal_code', 'phone', 'email', 'website', 'principal_name', 'principal_nip',
  'description', 'vision', 'mission', 'is_active', 'latitude', 'longitude', 'location_radius',
  'active_academic_year_id', 'active_semester_id'
]

const emptyToNull = (v) => {
  if (v === undefined || v === null) return null
  if (typeof v === 'number' && Number.isNaN(v)) return null
  if (typeof v === 'string' && v.trim() === '') return null
  return v
}

/** Bangun payload update: field kosong → null; name/npsn kosong tidak dikirim agar tidak menimpa data wajib di DB */
const buildInstitutionUpdatePayload = (source, { preserveNameNpsnFrom } = {}) => {
  const dataToSend = {}
  for (const key of INSTITUTION_PAYLOAD_KEYS) {
    if (!Object.prototype.hasOwnProperty.call(source, key)) continue
    const v = source[key]
    if (key === 'description' || key === 'vision' || key === 'mission') {
      dataToSend[key] = v != null ? String(v) : ''
      continue
    }
    if (key === 'latitude' || key === 'longitude' || key === 'location_radius') {
      const num = emptyToNull(v)
      if (key === 'location_radius' && num != null) {
        const n = Number(num)
        // Belum valid (di luar 10–5000) → jangan kirim agar tidak gagal validasi
        if (!Number.isFinite(n) || n < 10 || n > 5000) continue
      }
      dataToSend[key] = num
      continue
    }
    if (key === 'type') {
      dataToSend[key] = emptyToNull(v) || 'Swasta'
      continue
    }
    if (key === 'npsn') {
      const npsn = emptyToNull(v)
      // Hanya kirim NPSN jika lengkap 8 digit; kosong/belum lengkap = biarkan nilai lama
      if (npsn && /^[0-9]{8}$/.test(String(npsn))) {
        dataToSend[key] = String(npsn)
      }
      continue
    }
    if (key === 'name') {
      const name = emptyToNull(typeof v === 'string' ? v.trim() : v)
      if (name) dataToSend[key] = name
      continue
    }
    if (key === 'email') {
      const email = emptyToNull(typeof v === 'string' ? v.trim() : v)
      // Belum lengkap / tidak valid → jangan kirim (biarkan nilai lama atau null jika memang kosong)
      if (email === null) {
        dataToSend[key] = null
      } else if (email.includes('@') && /[^\s@]+@[^\s@]+\.[^\s@]+/.test(email)) {
        dataToSend[key] = email
      }
      continue
    }
    if (key === 'phone') {
      const phone = emptyToNull(typeof v === 'string' ? v.trim() : v)
      if (phone === null) {
        dataToSend[key] = null
      } else {
        const digits = String(phone).replace(/[^0-9]/g, '')
        if (digits.length >= 8 && digits.length <= 15) {
          dataToSend[key] = phone
        }
      }
      continue
    }
    if (key === 'website') {
      const website = emptyToNull(typeof v === 'string' ? v.trim() : v)
      if (website === null) {
        dataToSend[key] = null
      } else if (
        /^https?:\/\//i.test(website) ||
        /^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(\/.*)?$/i.test(website)
      ) {
        dataToSend[key] = website
      }
      continue
    }
    dataToSend[key] = emptyToNull(typeof v === 'string' ? v.trim() : v)
  }

  if (preserveNameNpsnFrom) {
    // Admin sekolah tidak mengubah name/npsn lewat form ini
    delete dataToSend.name
    delete dataToSend.npsn
    delete dataToSend.is_active
  }

  return dataToSend
}

// Setup form validation (fieldErrors + setErrors dari server)
const { setErrors } = useFormValidation({
  form,
  initialValues: {
    name: '', npsn: '', type: '', email: '', website: '', phone: ''
  },
  rules: getValidationRules('update')
})

const validateInstitutionForm = (mode = 'update') => {
  const { isValid, errors } = validateForm(form.value, getValidationRules(mode))
  setErrors(errors)
  if (!isValid) {
    const first = Object.values(errors)[0]
    error.value = first || 'Mohon perbaiki kesalahan pada form'
  }
  return isValid
}

const handleUpdate = async () => {
  error.value = ''
  
  // Check if trying to change name or npsn without super admin permission
  if (!isSuperAdmin.value) {
    const currentName = (institution.value.name || '').trim()
    const newName = (form.value.name || '').trim()
    const currentNpsn = (institution.value.npsn || '').trim()
    const newNpsn = (form.value.npsn || '').trim()
    
    if (currentName !== newName) {
      error.value = `Perubahan nama ${institutionTypeLabel.value.toLowerCase()} memerlukan persetujuan super admin. Silakan gunakan tombol "Ubah" di profil untuk request perubahan.`
      return
    }
    if (currentNpsn !== newNpsn) {
      error.value = 'Perubahan NPSN memerlukan persetujuan super admin. Silakan gunakan tombol "Ubah" di profil untuk request perubahan.'
      return
    }
  }
  
  if (!validateInstitutionForm('update')) {
    return
  }
  
  updating.value = true
  
  try {
    const dataToSend = buildInstitutionUpdatePayload(form.value, {
      preserveNameNpsnFrom: !isSuperAdmin.value
    })

    const response = await institutionApi.update(institution.value.id, dataToSend)
    
    // Update institution data
    const updatedData = response.data?.data || response.data
    if (updatedData) {
      // Ensure all address fields and text fields are properly updated
      institution.value = { ...institution.value, ...updatedData }
      Object.assign(form.value, updatedData)

      if (updatedData.description !== undefined) institution.value.description = updatedData.description
      if (updatedData.vision !== undefined) institution.value.vision = updatedData.vision
      if (updatedData.mission !== undefined) institution.value.mission = updatedData.mission
      if (updatedData.address !== undefined) institution.value.address = updatedData.address
      if (updatedData.village !== undefined) institution.value.village = updatedData.village
      if (updatedData.sub_district !== undefined) institution.value.sub_district = updatedData.sub_district
      if (updatedData.district !== undefined) institution.value.district = updatedData.district
      if (updatedData.province !== undefined) institution.value.province = updatedData.province
      if (updatedData.province_code !== undefined) institution.value.province_code = updatedData.province_code
      if (updatedData.district_code !== undefined) institution.value.district_code = updatedData.district_code
      if (updatedData.postal_code !== undefined) institution.value.postal_code = updatedData.postal_code
    }
    
    showEditModal.value = false
    error.value = ''
    toast.success('Berhasil', 'Profil institusi berhasil diperbarui')
    await loadPendingRequests()
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || err.response?.data?.error || 'Gagal memperbarui data'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
    
    // Set field errors from server
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }
  } finally {
    updating.value = false
  }
}

const handleAcademicYearChange = () => {
  // Reset semester selection when academic year changes
  academicYearForm.value.semester_name = ''
}

const openAcademicYearModal = async () => {
  // Get current semester name if exists
  let currentSemesterName = ''
  if (institution.value?.active_semester?.name) {
    currentSemesterName = institution.value.active_semester.name
  }
  
  academicYearForm.value = {
    active_academic_year_id: institution.value?.active_academic_year_id || null,
    semester_name: currentSemesterName
  }
  academicYearError.value = ''
  showAcademicYearModal.value = true
  await referenceStore.getAcademicYears()
}

const handleUpdateAcademicYear = async () => {
  academicYearError.value = ''
  
  if (!academicYearForm.value.active_academic_year_id || !academicYearForm.value.semester_name) {
    academicYearError.value = 'Tahun ajaran dan semester wajib dipilih'
    return
  }

  updatingAcademicYear.value = true

  try {
    await institutionApi.updateActiveAcademicYear(institution.value.id, {
      active_academic_year_id: academicYearForm.value.active_academic_year_id,
      semester_name: academicYearForm.value.semester_name
    })
    
    toast.success('Berhasil', 'Tahun ajaran dan semester aktif berhasil diperbarui')
    showAcademicYearModal.value = false
    await loadInstitution()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal memperbarui tahun ajaran dan semester'
    academicYearError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    updatingAcademicYear.value = false
  }
}

// Super Admin functions
const loadInstitutions = async () => {
  if (!isSuperAdmin.value) return
  
  loading.value = true
  try {
    const params = {
      per_page: 100
    }
    if (filters.value.search) {
      params.search = filters.value.search
    }
    if (filters.value.level) {
      params.level = filters.value.level
    }
    if (filters.value.type) {
      params.type = filters.value.type
    }
    if (filters.value.is_active !== '' && filters.value.is_active !== null) {
      params.is_active = filters.value.is_active === '1'
    }
    
    const response = await institutionApi.getAll(params)
    institutions.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load institutions:', err)
    toast.error('Gagal', 'Gagal memuat data institusi')
    institutions.value = []
  } finally {
    loading.value = false
  }
}

const viewInstitution = async (inst) => {
  selectedInstitution.value = inst
  error.value = ''
  showEditModalSuperAdmin.value = true
  loadingEditDetail.value = true
  try {
    const res = await institutionApi.get(inst.id)
    const full = res.data?.data || res.data
    if (full) {
      selectedInstitution.value = full
      Object.assign(form.value, full)
    } else {
      Object.assign(form.value, inst)
    }
  } catch (e) {
    Object.assign(form.value, inst)
  } finally {
    loadingEditDetail.value = false
  }
}

const editInstitution = async (inst) => {
  selectedInstitution.value = inst
  error.value = ''
  showEditModalSuperAdmin.value = true
  loadingEditDetail.value = true
  try {
    const res = await institutionApi.get(inst.id)
    const full = res.data?.data || res.data
    if (full) {
      selectedInstitution.value = full
      Object.assign(form.value, full)
    } else {
      Object.assign(form.value, inst)
    }
  } catch (e) {
    Object.assign(form.value, inst)
  } finally {
    loadingEditDetail.value = false
  }
}

const deleteInstitution = async (id) => {
  const confirmed = await showConfirm({
    title: 'Konfirmasi Hapus Instansi',
    message: 'Apakah Anda yakin ingin menghapus instansi ini? Instansi akan dikeluarkan dari daftar dan seluruh user instansi tersebut tidak dapat login.',
    warning: 'Tindakan ini untuk penindakan pelanggaran. Gunakan "Bekukan" jika hanya ingin menunda sementara.'
  })
  
  if (!confirmed) return
  
  setDeleteLoading(true)
  try {
    await institutionApi.delete(id)
    toast.success('Berhasil', 'Institusi berhasil dihapus')
    await loadInstitutions()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menghapus institusi'
    toast.error('Gagal', errorMsg)
  } finally {
    setDeleteLoading(false)
  }
}

const toggleInstitutionStatus = async (inst) => {
  const willActivate = inst.is_active === false
  const confirmed = await showConfirm({
    title: willActivate ? 'Aktifkan Institusi' : 'Bekukan Institusi',
    message: willActivate
      ? `Aktifkan kembali "${inst.name}"? User institusi dapat login lagi.`
      : `Bekukan "${inst.name}"? User institusi tidak dapat login sampai diaktifkan kembali.`,
    warning: willActivate
      ? 'Status institusi akan berubah menjadi aktif.'
      : 'Gunakan pembekuan untuk penundaan sementara. Hapus hanya jika perlu penindakan permanen.'
  })

  if (!confirmed) return

  statusTogglingId.value = inst.id
  try {
    await institutionApi.updateStatus(inst.id, willActivate)
    toast.success('Berhasil', willActivate ? 'Institusi berhasil diaktifkan' : 'Institusi berhasil dibekukan')
    await loadInstitutions()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal memperbarui status institusi'
    toast.error('Gagal', errorMsg)
  } finally {
    statusTogglingId.value = null
  }
}

const handleAddInstitution = async () => {
  error.value = ''
  
  if (!validateInstitutionForm('create')) {
    return
  }
  
  updating.value = true
  
  try {
    await institutionApi.create(form.value)
    toast.success('Berhasil', 'Institusi berhasil ditambahkan')
    showAddModal.value = false
    resetForm()
    await loadInstitutions()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menambahkan institusi'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
    
    // Set field errors from server
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }
  } finally {
    updating.value = false
  }
}

const handleUpdateSuperAdmin = async () => {
  error.value = ''
  
  if (!validateInstitutionForm('update')) {
    return
  }
  
  updating.value = true
  
  try {
    const dataToSend = buildInstitutionUpdatePayload(form.value)

    await institutionApi.update(selectedInstitution.value.id, dataToSend)
    toast.success('Berhasil', 'Institusi berhasil diperbarui')
    showEditModalSuperAdmin.value = false
    await loadInstitutions()
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || err.response?.data?.error || 'Gagal memperbarui institusi'
    error.value = errorMsg
    toast.error('Gagal', errorMsg)
    
    // Set field errors from server
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors)
    }
  } finally {
    updating.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    foundation_name: '',
    npsn: '',
    nss: '',
    level: '',
    type: 'Swasta',
    address: '',
    village: '',
    sub_district: '',
    district: '',
    province: '',
    province_code: '',
    district_code: '',
    postal_code: '',
    phone: '',
    email: '',
    website: '',
    principal_name: '',
    principal_nip: '',
    description: '',
    vision: '',
    mission: '',
    is_active: true,
    latitude: null,
    longitude: null,
    location_radius: null
  }
  selectedInstitution.value = null
  error.value = ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'Belum ada data'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const triggerLogoUpload = () => {
  logoInput.value?.click()
}

const handleLogoUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  // Validate file size (2MB max)
  if (file.size > 2 * 1024 * 1024) {
    toast.error('Gagal', 'Ukuran file maksimal 2MB')
    return
  }

  // Validate file type
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml']
  if (!validTypes.includes(file.type)) {
    toast.error('Gagal', 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau SVG')
    return
  }

  uploadingLogo.value = true

  try {
    const institutionId = institution.value?.id
    if (!institutionId) {
      toast.error('Gagal', 'Institusi tidak ditemukan')
      return
    }

    const response = await institutionApi.uploadLogo(institutionId, file)
    
    // Update institution data
    const updatedData = response.data?.data || response.data
    if (updatedData) {
      institution.value = updatedData
      if (form.value) {
        Object.assign(form.value, updatedData)
      }
    }
    
    toast.success('Berhasil', 'Logo berhasil diupload')
    
    // Reset file input
    if (logoInput.value) {
      logoInput.value.value = ''
    }
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || 'Gagal mengupload logo'
    toast.error('Gagal', errorMsg)
  } finally {
    uploadingLogo.value = false
  }
}

const triggerCoverUpload = () => {
  coverInput.value?.click()
}

const handleCoverUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  if (file.size > 5 * 1024 * 1024) {
    toast.error('Gagal', 'Ukuran file maksimal 5MB')
    return
  }

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml']
  if (!validTypes.includes(file.type)) {
    toast.error('Gagal', 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau SVG')
    return
  }

  uploadingCover.value = true
  coverUploadError.value = ''
  try {
    const institutionId = institution.value?.id
    if (!institutionId) {
      toast.error('Gagal', 'Institusi tidak ditemukan')
      return
    }
    const response = await institutionApi.uploadCoverImage(institutionId, file)
    const updatedData = response.data?.data || response.data
    if (updatedData) {
      institution.value = updatedData
      if (form.value) Object.assign(form.value, updatedData)
    }
    toast.success('Berhasil', 'Gambar cover berhasil diupload')
    if (coverInput.value) coverInput.value.value = ''
  } catch (err) {
    const errorMsg = err.formattedMessage || err.response?.data?.message || err.response?.data?.error || 'Gagal mengupload gambar cover'
    coverUploadError.value = errorMsg
    toast.error('Gagal', errorMsg)
    // Tampilkan error validasi per field jika ada
    if (err.response?.data?.errors && typeof err.response.data.errors === 'object') {
      const parts = Object.entries(err.response.data.errors).map(([k, v]) => {
        const text = Array.isArray(v) ? v[0] : v
        return k === 'cover_image' ? text : `${k}: ${text}`
      })
      if (parts.length) coverUploadError.value = parts.join('. ')
    }
  } finally {
    uploadingCover.value = false
  }
}

onMounted(async () => {
  await authStore.fetchUser()
  if (isSuperAdmin.value) {
    applyActiveFilterFromQuery()
    await loadInstitutions()
  } else {
    await loadInstitution()
  }
})

const applyActiveFilterFromQuery = () => {
  const q = route.query.is_active
  if (q === '0' || q === '1' || q === 'true' || q === 'false') {
    filters.value.is_active = (q === '1' || q === 'true') ? '1' : '0'
  }
}

watch(() => route.query.is_active, async () => {
  if (!isSuperAdmin.value) return
  applyActiveFilterFromQuery()
  await loadInstitutions()
})
</script>

<style scoped>
.institution-page {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 20%, #f1f5f9 100%);
}

.page-header {
  margin-bottom: 28px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
}

.header-content h2 {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 4px;
  letter-spacing: -0.3px;
}

.header-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.btn-primary {
  padding: 12px 22px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
}

.institution-card {
  background: white;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
}

.institution-card-header {
  margin-bottom: 8px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
}

.institution-card-header .btn-primary {
  margin: 0;
}

.info-section {
  margin-bottom: 28px;
  padding-bottom: 28px;
  border-bottom: 1px solid #e5e7eb;
}

.info-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.info-section h3 {
  color: #0f172a;
  margin-bottom: 20px;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-section h3::before {
  content: '';
  width: 4px;
  height: 22px;
  background: linear-gradient(180deg, #10b981 0%, #059669 100%);
  border-radius: 2px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

/* Layar besar: gunakan ruang lebih efisien, lebih banyak kolom */
@media (min-width: 1400px) {
  .institution-card .info-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item-full {
  grid-column: 1 / -1;
}

.info-item label {
  color: #64748b;
  font-size: 12px;
  margin-bottom: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item p {
  color: #0f172a;
  font-size: 15px;
  font-weight: 500;
  margin: 0;
  word-break: break-word;
}

.info-item p.text-empty {
  color: #64748b;
  font-weight: 400;
  font-style: italic;
}

.status-active {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.status-inactive {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.row-inactive td {
  opacity: 0.85;
}

.btn-suspend {
  color: #d97706;
}

.btn-suspend:hover {
  background: rgba(245, 158, 11, 0.12);
}

.btn-activate {
  color: #059669;
}

.btn-activate:hover {
  background: rgba(5, 150, 105, 0.12);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 32px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #999;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 32px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 8px;
  color: #333;
  font-weight: 500;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 30px;
}

.btn-secondary {
  padding: 12px 24px;
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
}

.error-message {
  padding: 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid #fecaca;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
}

.loading-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.loading-spinner {
  color: #059669;
}

.loading-state p {
  font-size: 16px;
  font-weight: 500;
  margin: 0;
}

.btn-request-change {
  padding: 8px 14px;
  background: #f0fdf4;
  color: #059669;
  border: 1px solid #86efac;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-request-change:hover {
  background: #dcfce7;
  border-color: #4ade80;
}

.btn-request-badge {
  padding: 4px 8px;
  background: #fef3c7;
  color: #d97706;
  border: 1px solid #fde68a;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.2s ease;
}

.btn-request-badge:hover {
  background: #fde68a;
}

.form-hint {
  display: block;
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
  font-style: italic;
}

.form-section-label {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin: 24px 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: #334155;
  margin-top: 8px;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #059669;
  cursor: pointer;
}

.form-group input:disabled {
  background: #f1f5f9;
  color: #64748b;
  cursor: not-allowed;
}

.form-input.error-border {
  border-color: #ef4444;
  background: #fef2f2;
}

.form-input.error-border:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}


/* Super Admin Table Styles */
.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.search-input {
  flex: 1;
  min-width: 250px;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 14px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 180px;
}

.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: white;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}

.table-container {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
}

.data-table th {
  padding: 16px 20px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #1e293b;
}

.data-table td:last-child {
  text-align: center;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

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
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: transparent;
}

.btn-view {
  color: #059669;
}

.btn-view:hover {
  background: rgba(59, 130, 246, 0.1);
}

.btn-edit {
  color: #10b981;
}

.btn-edit:hover {
  background: rgba(16, 185, 129, 0.1);
}

.btn-delete {
  color: #ef4444;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
}

.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-state svg {
  color: #cbd5e1;
  margin-bottom: 8px;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.empty-state p {
  font-size: 14px;
  color: #64748b;
  margin: 0 0 24px 0;
}

.logo-section {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
}

.logo-preview {
  width: 200px;
  height: 200px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.logo-preview img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.logo-placeholder {
  width: 200px;
  height: 200px;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
  background: #f8fafc;
}

.logo-placeholder p {
  font-size: 12px;
  margin: 0;
  color: #94a3b8;
}

.logo-upload-section {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 12px;
}

.logo-preview-small {
  width: 120px;
  height: 120px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.logo-preview-small img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.logo-placeholder-small {
  width: 120px;
  height: 120px;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  background: #f8fafc;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .action-buttons-group {
    width: 100%;
  }

  .filters-inline {
    flex-direction: column;
    overflow: visible;
  }

  .filters-inline .search-input,
  .filters-inline .filter-select {
    width: 100%;
    max-width: 100%;
  }
}

@media (max-width: 768px) {
  .page-header {
    margin-bottom: 16px;
  }

  .header-content h2 {
    font-size: 20px;
  }

  .institution-card {
    padding: 16px;
    border-radius: 12px;
  }

  .info-grid,
  .form-row,
  .form-grid {
    grid-template-columns: 1fr !important;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 -4px;
  }

  .data-table {
    min-width: 720px;
  }

  .action-buttons {
    flex-wrap: wrap;
  }

  .modal-overlay {
    padding: 12px;
    align-items: flex-start;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    max-height: calc(100dvh - 24px);
    margin: 0;
  }

  .institution-card-header {
    justify-content: stretch;
  }

  .institution-card-header .btn-primary {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .data-table th,
  .data-table td {
    padding: 10px 8px;
    font-size: 12px;
  }
}
</style>
