<template>
  <Layout>
    <div class="facility-page">
      <div class="page-header">
        <div class="header-content">
          <div>
            <h2>Sarana Prasarana</h2>
            <p>Kelola data tanah, gedung, dan ruangan sekolah Anda</p>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-container">
        <div class="tabs-nav">
          <button 
            @click="activeTab = 'land'" 
            :class="['tab-btn', { active: activeTab === 'land' }]"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V10M19 10L21 12M19 10L12 3L5 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Data Tanah</span>
          </button>
          <button 
            @click="activeTab = 'building'" 
            :class="['tab-btn', { active: activeTab === 'building' }]"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 21H21M5 21V7L13 2V7M5 21H19M19 21V11M9 9V13M13 9V13M17 9V13M9 17V21M13 17V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Data Gedung</span>
          </button>
          <button 
            @click="activeTab = 'room'" 
            :class="['tab-btn', { active: activeTab === 'room' }]"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Data Ruangan</span>
          </button>
        </div>
      </div>

      <!-- Tab Content: Land -->
      <div v-show="activeTab === 'land'" class="tab-content">
        <div class="tab-header">
          <div class="filters">
            <input 
              v-model="landFilters.search" 
              @input="loadLands" 
              placeholder="Cari nama, sertifikat, atau lokasi..."
              class="search-input"
            />
            <select v-model="landFilters.status" @change="loadLands" class="filter-select">
              <option value="">Semua Status</option>
              <option value="Milik Sendiri">Milik Sendiri</option>
              <option value="Sewa">Sewa</option>
              <option value="Pinjam">Pinjam</option>
              <option value="Hak Pakai">Hak Pakai</option>
            </select>
          </div>
          <button @click="openLandModal()" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Tanah</span>
          </button>
        </div>

        <div v-if="landLoading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat data...</p>
        </div>
        
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Luas (m²)</th>
                <th>Sertifikat</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="land in lands" :key="`land-${land.id}`">
                <td>{{ land.name }}</td>
                <td>{{ formatNumber(land.area) }}</td>
                <td>{{ land.certificate_number || '-' }}<br><small>{{ land.certificate_type || '-' }}</small></td>
                <td>{{ land.location || '-' }}</td>
                <td><span :class="getStatusClass(land.status)">{{ land.status }}</span></td>
                <td>
                  <div class="action-buttons">
                    <button @click="openLandModal(land)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteLand(land.id)" class="btn-action btn-delete" title="Hapus">
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

          <div v-if="lands.length === 0" class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V10M19 10L21 12M19 10L12 3L5 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Tidak ada data tanah</h3>
            <p>Mulai dengan menambahkan data tanah baru</p>
            <button @click="openLandModal()" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Tanah</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab Content: Building -->
      <div v-show="activeTab === 'building'" class="tab-content">
        <div class="tab-header">
          <div class="filters">
            <input 
              v-model="buildingFilters.search" 
              @input="loadBuildings" 
              placeholder="Cari nama atau kode gedung..."
              class="search-input"
            />
            <select v-model="buildingFilters.condition" @change="loadBuildings" class="filter-select">
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Sedang">Rusak Sedang</option>
              <option value="Rusak Berat">Rusak Berat</option>
            </select>
            <select v-model="buildingFilters.land_id" @change="loadBuildings" class="filter-select">
              <option value="">Semua Tanah</option>
              <option v-for="land in lands" :key="land.id" :value="land.id">{{ land.name }}</option>
            </select>
          </div>
          <button @click="openBuildingModal()" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Gedung</span>
          </button>
        </div>

        <div v-if="buildingLoading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat data...</p>
        </div>
        
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kode</th>
                <th>Tanah</th>
                <th>Lantai</th>
                <th>Luas (m²)</th>
                <th>Kondisi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="building in buildings" :key="building.id">
                <td>{{ building.name }}</td>
                <td>{{ building.code || '-' }}</td>
                <td>{{ building.land?.name || '-' }}</td>
                <td>{{ building.floor_count }}</td>
                <td>{{ building.building_area ? formatNumber(building.building_area) : '-' }}</td>
                <td><span :class="getConditionClass(building.condition)">{{ building.condition }}</span></td>
                <td>
                  <div class="action-buttons">
                    <button @click="openBuildingModal(building)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteBuilding(building.id)" class="btn-action btn-delete" title="Hapus">
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

          <div v-if="buildings.length === 0" class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 21H21M5 21V7L13 2V7M5 21H19M19 21V11M9 9V13M13 9V13M17 9V13M9 17V21M13 17V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Tidak ada data gedung</h3>
            <p>Mulai dengan menambahkan data gedung baru</p>
            <button @click="openBuildingModal()" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Gedung</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab Content: Room -->
      <div v-show="activeTab === 'room'" class="tab-content">
        <div class="tab-header">
          <div class="filters">
            <input 
              v-model="roomFilters.search" 
              @input="loadRooms" 
              placeholder="Cari nama atau kode ruangan..."
              class="search-input"
            />
            <select v-model="roomFilters.type" @change="loadRooms" class="filter-select">
              <option value="">Semua Tipe</option>
              <option value="Kelas">Kelas</option>
              <option value="Laboratorium">Laboratorium</option>
              <option value="Perpustakaan">Perpustakaan</option>
              <option value="Kantor">Kantor</option>
              <option value="Aula">Aula</option>
              <option value="Musholla">Musholla</option>
              <option value="Kantin">Kantin</option>
              <option value="Toilet">Toilet</option>
              <option value="Gudang">Gudang</option>
              <option value="Lainnya">Lainnya</option>
            </select>
            <select v-model="roomFilters.building_id" @change="loadRooms" class="filter-select">
              <option value="">Semua Gedung</option>
              <option v-for="building in buildings" :key="building.id" :value="building.id">{{ building.name }}</option>
            </select>
            <select v-model="roomFilters.condition" @change="loadRooms" class="filter-select">
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Sedang">Rusak Sedang</option>
              <option value="Rusak Berat">Rusak Berat</option>
            </select>
          </div>
          <button @click="openRoomModal()" class="btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Tambah Ruangan</span>
          </button>
        </div>

        <div v-if="roomLoading" class="loading-state">
          <div class="loading-spinner">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
          </div>
          <p>Memuat data...</p>
        </div>
        
        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kode</th>
                <th>Tipe</th>
                <th>Gedung</th>
                <th>Lantai</th>
                <th>Luas (m²)</th>
                <th>Kapasitas</th>
                <th>Kondisi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="room in rooms" :key="room.id">
                <td>{{ room.name }}</td>
                <td>{{ room.code || '-' }}</td>
                <td>{{ room.type }}</td>
                <td>{{ room.building?.name || '-' }}</td>
                <td>{{ room.floor }}</td>
                <td>{{ room.area ? formatNumber(room.area) : '-' }}</td>
                <td>{{ room.capacity || '-' }}</td>
                <td><span :class="getConditionClass(room.condition)">{{ room.condition }}</span></td>
                <td>
                  <div class="action-buttons">
                    <button @click="openRoomModal(room)" class="btn-action btn-edit" title="Edit">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.5C18.8978 2.10218 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10218 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10218 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                    <button @click="deleteRoom(room.id)" class="btn-action btn-delete" title="Hapus">
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

          <div v-if="rooms.length === 0" class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 22V12H15V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3>Tidak ada data ruangan</h3>
            <p>Mulai dengan menambahkan data ruangan baru</p>
            <button @click="openRoomModal()" class="btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tambah Ruangan</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Land Modal -->
      <div v-if="showLandModal" class="modal-overlay" @click="closeLandModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingLand ? 'Edit' : 'Tambah' }} Data Tanah</h3>
            <button @click="closeLandModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="saveLand" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Tanah *</label>
                <input v-model="landForm.name" required />
              </div>
              <div class="form-group">
                <label>Luas (m²) *</label>
                <input type="number" v-model.number="landForm.area" step="0.01" min="0" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nomor Sertifikat</label>
                <input v-model="landForm.certificate_number" />
              </div>
              <div class="form-group">
                <label>Jenis Sertifikat</label>
                <select v-model="landForm.certificate_type">
                  <option value="">Pilih</option>
                  <option value="SHM">SHM</option>
                  <option value="SHGB">SHGB</option>
                  <option value="HGB">HGB</option>
                  <option value="Hak Pakai">Hak Pakai</option>
                  <option value="Tanpa Sertifikat">Tanpa Sertifikat</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Lokasi</label>
                <input v-model="landForm.location" />
              </div>
              <div class="form-group">
                <label>Status Kepemilikan *</label>
                <select v-model="landForm.status" required>
                  <option value="Milik Sendiri">Milik Sendiri</option>
                  <option value="Sewa">Sewa</option>
                  <option value="Pinjam">Pinjam</option>
                  <option value="Hak Pakai">Hak Pakai</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Perolehan</label>
                <input type="date" v-model="landForm.acquisition_date" />
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="landForm.description" rows="3"></textarea>
            </div>

            <div v-if="landError" class="error-message">{{ landError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeLandModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="landSaving" class="btn-primary">
                {{ landSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Building Modal -->
      <div v-if="showBuildingModal" class="modal-overlay" @click="closeBuildingModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingBuilding ? 'Edit' : 'Tambah' }} Data Gedung</h3>
            <button @click="closeBuildingModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="saveBuilding" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Gedung *</label>
                <input v-model="buildingForm.name" required />
              </div>
              <div class="form-group">
                <label>Kode Gedung</label>
                <input v-model="buildingForm.code" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanah</label>
                <select v-model="buildingForm.land_id">
                  <option value="">Pilih Tanah</option>
                  <option v-for="land in lands" :key="land.id" :value="land.id">{{ land.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jumlah Lantai *</label>
                <input type="number" v-model.number="buildingForm.floor_count" min="1" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Luas Bangunan (m²)</label>
                <input type="number" v-model.number="buildingForm.building_area" step="0.01" min="0" />
              </div>
              <div class="form-group">
                <label>Kondisi *</label>
                <select v-model="buildingForm.condition" required>
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Sedang">Rusak Sedang</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tahun Pembangunan</label>
                <input type="number" v-model.number="buildingForm.construction_year" min="1900" :max="new Date().getFullYear()" />
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="buildingForm.description" rows="3"></textarea>
            </div>

            <div v-if="buildingError" class="error-message">{{ buildingError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeBuildingModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="buildingSaving" class="btn-primary">
                {{ buildingSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Room Modal -->
      <div v-if="showRoomModal" class="modal-overlay" @click="closeRoomModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>{{ editingRoom ? 'Edit' : 'Tambah' }} Data Ruangan</h3>
            <button @click="closeRoomModal" class="btn-close">×</button>
          </div>
          
          <form @submit.prevent="saveRoom" class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Nama Ruangan *</label>
                <input v-model="roomForm.name" required />
              </div>
              <div class="form-group">
                <label>Kode Ruangan</label>
                <input v-model="roomForm.code" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tipe Ruangan *</label>
                <select v-model="roomForm.type" required>
                  <option value="Kelas">Kelas</option>
                  <option value="Laboratorium">Laboratorium</option>
                  <option value="Perpustakaan">Perpustakaan</option>
                  <option value="Kantor">Kantor</option>
                  <option value="Aula">Aula</option>
                  <option value="Musholla">Musholla</option>
                  <option value="Kantin">Kantin</option>
                  <option value="Toilet">Toilet</option>
                  <option value="Gudang">Gudang</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
              <div class="form-group">
                <label>Gedung</label>
                <select v-model="roomForm.building_id">
                  <option value="">Pilih Gedung</option>
                  <option v-for="building in buildings" :key="building.id" :value="building.id">{{ building.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Lantai *</label>
                <input type="number" v-model.number="roomForm.floor" min="1" required />
              </div>
              <div class="form-group">
                <label>Luas (m²)</label>
                <input type="number" v-model.number="roomForm.area" step="0.01" min="0" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" v-model.number="roomForm.capacity" min="0" />
              </div>
              <div class="form-group">
                <label>Kondisi *</label>
                <select v-model="roomForm.condition" required>
                  <option value="Baik">Baik</option>
                  <option value="Rusak Ringan">Rusak Ringan</option>
                  <option value="Rusak Sedang">Rusak Sedang</option>
                  <option value="Rusak Berat">Rusak Berat</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea v-model="roomForm.description" rows="3"></textarea>
            </div>

            <div v-if="roomError" class="error-message">{{ roomError }}</div>

            <div class="modal-footer">
              <button type="button" @click="closeRoomModal" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="roomSaving" class="btn-primary">
                {{ roomSaving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Layout from '@/components/Layout.vue'
import { facilityApi } from '@/api/facility'
import { useToast } from '@/composables/useToast'

const toast = useToast()

// Tab state
const activeTab = ref('land')

// Land state
const lands = ref([])
const landLoading = ref(false)
const showLandModal = ref(false)
const editingLand = ref(null)
const landSaving = ref(false)
const landError = ref('')
const landFilters = ref({
  search: '',
  status: ''
})

const landForm = ref({
  name: '',
  certificate_number: '',
  certificate_type: '',
  area: '',
  location: '',
  status: 'Milik Sendiri',
  acquisition_date: '',
  description: ''
})

// Building state
const buildings = ref([])
const buildingLoading = ref(false)
const showBuildingModal = ref(false)
const editingBuilding = ref(null)
const buildingSaving = ref(false)
const buildingError = ref('')
const buildingFilters = ref({
  search: '',
  condition: '',
  land_id: ''
})

const buildingForm = ref({
  land_id: '',
  name: '',
  code: '',
  floor_count: 1,
  building_area: '',
  condition: 'Baik',
  construction_year: '',
  description: ''
})

// Room state
const rooms = ref([])
const roomLoading = ref(false)
const showRoomModal = ref(false)
const editingRoom = ref(null)
const roomSaving = ref(false)
const roomError = ref('')
const roomFilters = ref({
  search: '',
  type: '',
  building_id: '',
  condition: ''
})

const roomForm = ref({
  building_id: '',
  name: '',
  code: '',
  type: 'Kelas',
  floor: 1,
  area: '',
  capacity: '',
  condition: 'Baik',
  description: ''
})

// Load data functions
const loadLands = async (resetFilters = false) => {
  landLoading.value = true
  try {
    // If resetFilters is true, reset filter values first
    if (resetFilters) {
      landFilters.value = {
        search: '',
        status: ''
      }
    }
    
    const filters = resetFilters ? {} : landFilters.value
    const response = await facilityApi.getLands(filters)
    
    // Handle response structure: backend returns { data: [...] }
    // Axios wraps it so we get response.data = { data: [...] }
    if (response?.data?.data && Array.isArray(response.data.data)) {
      lands.value = response.data.data
    } else if (Array.isArray(response?.data)) {
      lands.value = response.data
    } else {
      lands.value = []
    }
  } catch (err) {
    console.error('Error loading lands:', err)
    toast.error('Gagal', 'Gagal memuat data tanah')
    lands.value = []
  } finally {
    landLoading.value = false
  }
}

const loadBuildings = async (resetFilters = false) => {
  buildingLoading.value = true
  try {
    // If resetFilters is true, reset filter values first
    if (resetFilters) {
      buildingFilters.value = {
        search: '',
        condition: '',
        land_id: ''
      }
    }
    
    const filters = resetFilters ? {} : buildingFilters.value
    const response = await facilityApi.getBuildings(filters)
    // Handle response structure: backend returns { data: [...] }
    // Axios wraps it so we get response.data = { data: [...] }
    if (response?.data?.data && Array.isArray(response.data.data)) {
      buildings.value = response.data.data
    } else if (Array.isArray(response?.data)) {
      buildings.value = response.data
    } else {
      buildings.value = []
    }
  } catch (err) {
    console.error('Error loading buildings:', err)
    toast.error('Gagal', 'Gagal memuat data gedung')
    buildings.value = []
  } finally {
    buildingLoading.value = false
  }
}

const loadRooms = async (resetFilters = false) => {
  roomLoading.value = true
  try {
    // If resetFilters is true, reset filter values first
    if (resetFilters) {
      roomFilters.value = {
        search: '',
        type: '',
        building_id: '',
        condition: ''
      }
    }
    
    const filters = resetFilters ? {} : roomFilters.value
    const response = await facilityApi.getRooms(filters)
    // Handle response structure: backend returns { data: [...] }
    // Axios wraps it so we get response.data = { data: [...] }
    if (response?.data?.data && Array.isArray(response.data.data)) {
      rooms.value = response.data.data
    } else if (Array.isArray(response?.data)) {
      rooms.value = response.data
    } else {
      rooms.value = []
    }
  } catch (err) {
    console.error('Error loading rooms:', err)
    toast.error('Gagal', 'Gagal memuat data ruangan')
    rooms.value = []
  } finally {
    roomLoading.value = false
  }
}

// Land CRUD
const openLandModal = (land = null) => {
  editingLand.value = land
  if (land) {
    landForm.value = {
      name: land.name || '',
      certificate_number: land.certificate_number || '',
      certificate_type: land.certificate_type || '',
      area: land.area || '',
      location: land.location || '',
      status: land.status || 'Milik Sendiri',
      acquisition_date: land.acquisition_date ? land.acquisition_date.split('T')[0] : '',
      description: land.description || ''
    }
  } else {
    landForm.value = {
      name: '',
      certificate_number: '',
      certificate_type: '',
      area: '',
      location: '',
      status: 'Milik Sendiri',
      acquisition_date: '',
      description: ''
    }
  }
  landError.value = ''
  showLandModal.value = true
}

const closeLandModal = () => {
  showLandModal.value = false
  editingLand.value = null
  landError.value = ''
}

const saveLand = async () => {
  landError.value = ''
  landSaving.value = true
  
  try {
    if (editingLand.value) {
      await facilityApi.updateLand(editingLand.value.id, landForm.value)
      toast.success('Berhasil', 'Data tanah berhasil diperbarui')
    } else {
      await facilityApi.createLand(landForm.value)
      toast.success('Berhasil', 'Data tanah berhasil ditambahkan')
    }
    
    closeLandModal()
    
    // Reload data without filters to show all data (this will also reset filters)
    await loadLands(true)
  } catch (err) {
    const errorMsg = err.response?.data?.message || err.message || 'Gagal menyimpan data tanah'
    landError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    landSaving.value = false
  }
}

const deleteLand = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data tanah ini?')) return
  
  try {
    await facilityApi.deleteLand(id)
    toast.success('Berhasil', 'Data tanah berhasil dihapus')
    // Reload with current filters
    loadLands()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menghapus data tanah')
  }
}

// Building CRUD
const openBuildingModal = async (building = null) => {
  // Ensure lands are loaded for dropdown
  if (lands.value.length === 0) {
    await loadLands(true)
  }
  
  editingBuilding.value = building
  if (building) {
    buildingForm.value = {
      land_id: building.land_id || '',
      name: building.name || '',
      code: building.code || '',
      floor_count: building.floor_count || 1,
      building_area: building.building_area || '',
      condition: building.condition || 'Baik',
      construction_year: building.construction_year || '',
      description: building.description || ''
    }
  } else {
    buildingForm.value = {
      land_id: '',
      name: '',
      code: '',
      floor_count: 1,
      building_area: '',
      condition: 'Baik',
      construction_year: '',
      description: ''
    }
  }
  buildingError.value = ''
  showBuildingModal.value = true
}

const closeBuildingModal = () => {
  showBuildingModal.value = false
  editingBuilding.value = null
  buildingError.value = ''
}

const saveBuilding = async () => {
  buildingError.value = ''
  buildingSaving.value = true
  
  try {
    if (editingBuilding.value) {
      await facilityApi.updateBuilding(editingBuilding.value.id, buildingForm.value)
      toast.success('Berhasil', 'Data gedung berhasil diperbarui')
    } else {
      await facilityApi.createBuilding(buildingForm.value)
      toast.success('Berhasil', 'Data gedung berhasil ditambahkan')
    }
    closeBuildingModal()
    
    // Reload data without filters to show all data (this will also reset filters)
    await loadBuildings(true)
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menyimpan data gedung'
    buildingError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    buildingSaving.value = false
  }
}

const deleteBuilding = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data gedung ini?')) return
  
  try {
    await facilityApi.deleteBuilding(id)
    toast.success('Berhasil', 'Data gedung berhasil dihapus')
    loadBuildings()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menghapus data gedung')
  }
}

// Room CRUD
const openRoomModal = async (room = null) => {
  // Ensure buildings are loaded for dropdown
  if (buildings.value.length === 0) {
    await loadBuildings(true)
  }
  
  editingRoom.value = room
  if (room) {
    roomForm.value = {
      building_id: room.building_id || '',
      name: room.name || '',
      code: room.code || '',
      type: room.type || 'Kelas',
      floor: room.floor || 1,
      area: room.area || '',
      capacity: room.capacity || '',
      condition: room.condition || 'Baik',
      description: room.description || ''
    }
  } else {
    roomForm.value = {
      building_id: '',
      name: '',
      code: '',
      type: 'Kelas',
      floor: 1,
      area: '',
      capacity: '',
      condition: 'Baik',
      description: ''
    }
  }
  roomError.value = ''
  showRoomModal.value = true
}

const closeRoomModal = () => {
  showRoomModal.value = false
  editingRoom.value = null
  roomError.value = ''
}

const saveRoom = async () => {
  roomError.value = ''
  roomSaving.value = true
  
  try {
    if (editingRoom.value) {
      await facilityApi.updateRoom(editingRoom.value.id, roomForm.value)
      toast.success('Berhasil', 'Data ruangan berhasil diperbarui')
    } else {
      await facilityApi.createRoom(roomForm.value)
      toast.success('Berhasil', 'Data ruangan berhasil ditambahkan')
    }
    closeRoomModal()
    
    // Reload data without filters to show all data (this will also reset filters)
    await loadRooms(true)
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menyimpan data ruangan'
    roomError.value = errorMsg
    toast.error('Gagal', errorMsg)
  } finally {
    roomSaving.value = false
  }
}

const deleteRoom = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data ruangan ini?')) return
  
  try {
    await facilityApi.deleteRoom(id)
    toast.success('Berhasil', 'Data ruangan berhasil dihapus')
    loadRooms()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menghapus data ruangan')
  }
}

// Utility functions
const formatNumber = (num) => {
  if (!num) return '-'
  return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)
}

const getStatusClass = (status) => {
  const classes = {
    'Milik Sendiri': 'status-success',
    'Sewa': 'status-warning',
    'Pinjam': 'status-info',
    'Hak Pakai': 'status-info'
  }
  return classes[status] || ''
}

const getConditionClass = (condition) => {
  const classes = {
    'Baik': 'status-success',
    'Rusak Ringan': 'status-warning',
    'Rusak Sedang': 'status-warning',
    'Rusak Berat': 'status-danger'
  }
  return classes[condition] || ''
}

// Watch tab changes to load data
watch(activeTab, (newTab) => {
  if (newTab === 'land' && lands.value.length === 0) {
    loadLands(true)
  } else if (newTab === 'building') {
    // Always load lands for dropdown when building tab is active
    if (lands.value.length === 0) {
      loadLands(true)
    }
    if (buildings.value.length === 0) {
      loadBuildings(true)
    }
  } else if (newTab === 'room') {
    // Always load buildings for dropdown when room tab is active
    if (buildings.value.length === 0) {
      loadBuildings(true)
    }
    if (rooms.value.length === 0) {
      loadRooms(true)
    }
  }
})

onMounted(() => {
  // Load data without filters on initial load to show all data
  loadLands(true)
})
</script>

<style scoped>
.facility-page {
  max-width: 1400px;
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

.tabs-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  margin-bottom: 24px;
}

.tabs-nav {
  display: flex;
  gap: 4px;
  padding: 8px;
  border-bottom: 2px solid #e2e8f0;
}

.tab-btn {
  flex: 1;
  padding: 14px 24px;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px 8px 0 0;
}

.tab-btn:hover {
  color: #667eea;
  background: #f8fafc;
}

.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
  font-weight: 600;
  background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05), transparent);
}

.tab-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  padding: 24px;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filters {
  display: flex;
  gap: 12px;
  flex: 1;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  transition: all 0.2s ease;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.filter-select {
  min-width: 180px;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.status-success {
  color: #27ae60;
  font-weight: 600;
}

.status-warning {
  color: #f39c12;
  font-weight: 600;
}

.status-danger {
  color: #e74c3c;
  font-weight: 600;
}

.status-info {
  color: #3498db;
  font-weight: 600;
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
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: transparent;
}

.btn-edit {
  color: #3b82f6;
}

.btn-edit:hover {
  background: rgba(59, 130, 246, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.btn-delete {
  color: #ef4444;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
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
  color: #667eea;
}

.loading-state p {
  font-size: 16px;
  font-weight: 500;
  margin: 0;
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
  border-radius: 24px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 28px 32px;
  border-bottom: 2px solid #f1f5f9;
}

.modal-header h3 {
  color: #1e293b;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
}

.btn-close {
  background: #f1f5f9;
  border: none;
  font-size: 24px;
  color: #64748b;
  cursor: pointer;
  line-height: 1;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.modal-body {
  padding: 32px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 10px;
  color: #1e293b;
  font-weight: 600;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 14px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #ffffff;
  transition: all 0.3s ease;
  color: #1e293b;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-group select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 45px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 12px;
  margin-top: 40px;
  padding-top: 24px;
  border-top: 2px solid #f1f5f9;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 12px 24px;
  background: #ffffff;
  color: #475569;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.error-message {
  padding: 16px;
  background: #fef2f2;
  color: #dc2626;
  border-radius: 12px;
  margin-bottom: 24px;
  border: 1px solid #fecaca;
  font-size: 14px;
}
</style>
