<template>
  <!-- Overlay for mobile -->
  <div 
    v-if="isOpen" 
    class="help-overlay"
    @click="toggleSidebar"
  ></div>

  <div class="help-sidebar" :class="{ 'help-sidebar-open': isOpen, 'help-sidebar-collapsed': !isOpen }">
    <!-- Toggle Button -->
    <button 
      @click="toggleSidebar" 
      class="help-toggle-btn"
      :aria-label="isOpen ? 'Tutup petunjuk' : 'Buka petunjuk'"
    >
      <svg v-if="isOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <!-- Sidebar Content -->
    <div class="help-content" v-if="isOpen">
      <div class="help-header">
        <div class="help-header-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2>Petunjuk Penggunaan</h2>
      </div>

      <div class="help-body">
        <!-- Getting Started -->
        <div class="help-section">
          <h3 class="help-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Memulai
          </h3>
          <div class="help-item">
            <p class="help-item-title">1. Lengkapi Profil Instansi</p>
            <p class="help-item-desc">Pastikan data instansi Anda lengkap dan akurat di menu <strong>Profil Instansi</strong>.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">2. Kelola Akses Modul</p>
            <p class="help-item-desc">Atur modul yang dapat diakses oleh pengguna di menu <strong>Kelola Akses Modul</strong>.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">3. Tambah Data Dasar</p>
            <p class="help-item-desc">Mulai dengan menambahkan data siswa dan guru untuk memulai penggunaan sistem.</p>
          </div>
        </div>

        <!-- Fitur Utama -->
        <div class="help-section">
          <h3 class="help-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="currentColor"/>
              <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Fitur Utama
          </h3>
          <div class="help-item">
            <p class="help-item-title">📊 Data Siswa</p>
            <p class="help-item-desc">Kelola data siswa, tambah, edit, dan hapus informasi siswa dengan mudah.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">👨‍🏫 Data Guru</p>
            <p class="help-item-desc">Kelola data guru dan informasi pengajar di instansi Anda.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">📝 Persuratan</p>
            <p class="help-item-desc">Kelola surat masuk dan surat keluar dengan sistem yang terorganisir.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">📦 Inventaris</p>
            <p class="help-item-desc">Kelola inventaris barang dan aset instansi dengan pencatatan yang rapi.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">📈 Laporan</p>
            <p class="help-item-desc">Akses berbagai laporan dan statistik untuk monitoring instansi.</p>
          </div>
        </div>

        <!-- Tips & Best Practices -->
        <div class="help-section">
          <h3 class="help-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M19.4 15C19.2669 15.3016 19.2272 15.6362 19.286 15.9606C19.3448 16.285 19.4995 16.5843 19.73 16.82L19.79 16.88C19.976 17.0657 20.1235 17.2863 20.2241 17.5291C20.3248 17.7719 20.3766 18.0322 20.3766 18.295C20.3766 18.5578 20.3248 18.8181 20.2241 19.0609C20.1235 19.3037 19.976 19.5243 19.79 19.71C19.6043 19.896 19.3837 20.0435 19.1409 20.1441C18.8981 20.2448 18.6378 20.2966 18.375 20.2966C18.1122 20.2966 17.8519 20.2448 17.6091 20.1441C17.3663 20.0435 17.1457 19.896 16.96 19.71L16.9 19.65C16.6643 19.4195 16.365 19.2648 16.0406 19.206C15.7162 19.1472 15.3816 19.1869 15.08 19.32C14.7842 19.4468 14.532 19.6572 14.3543 19.9255C14.1766 20.1938 14.0813 20.5082 14.08 20.83V21C14.08 21.5304 13.8693 22.0391 13.4942 22.4142C13.1191 22.7893 12.6104 23 12.08 23C11.5496 23 11.0409 22.7893 10.6658 22.4142C10.2907 22.0391 10.08 21.5304 10.08 21V20.91C10.0723 20.5795 9.96512 20.258 9.77251 19.9887C9.5799 19.7194 9.31074 19.5143 9 19.4C8.69838 19.2669 8.36381 19.2272 8.03941 19.286C7.71502 19.3448 7.41568 19.4995 7.18 19.73L7.12 19.79C6.93425 19.976 6.71368 20.1235 6.47088 20.2241C6.22808 20.3248 5.96783 20.3766 5.705 20.3766C5.44217 20.3766 5.18192 20.3248 4.93912 20.2241C4.69632 20.1235 4.47575 19.976 4.29 19.79C4.10405 19.6043 3.95653 19.3837 3.85588 19.1409C3.75523 18.8981 3.70343 18.6378 3.70343 18.375C3.70343 18.1122 3.75523 17.8519 3.85588 17.6091C3.95653 17.3663 4.10405 17.1457 4.29 16.96L4.35 16.9C4.58054 16.6643 4.73519 16.365 4.794 16.0406C4.85282 15.7162 4.81312 15.3816 4.68 15.08C4.55324 14.7842 4.34276 14.532 4.07447 14.3543C3.80618 14.1766 3.49179 14.0813 3.17 14.08H3C2.46957 14.08 1.96086 13.8693 1.58579 13.4942C1.21071 13.1191 1 12.6104 1 12.08C1 11.5496 1.21071 11.0409 1.58579 10.6658C1.96086 10.2907 2.46957 10.08 3 10.08H3.09C3.42054 10.0723 3.742 9.96512 4.0113 9.77251C4.28059 9.5799 4.48572 9.31074 4.6 9C4.73312 8.69838 4.77282 8.36381 4.714 8.03941C4.65519 7.71502 4.50054 7.41568 4.27 7.18L4.21 7.12C4.02405 6.93425 3.87653 6.71368 3.77588 6.47088C3.67523 6.22808 3.62343 5.96783 3.62343 5.705C3.62343 5.44217 3.67523 5.18192 3.77588 4.93912C3.87653 4.69632 4.02405 4.47575 4.21 4.29C4.39575 4.10405 4.61632 3.95653 4.85912 3.85588C5.10192 3.75523 5.36217 3.70343 5.625 3.70343C5.88783 3.70343 6.14808 3.75523 6.39088 3.85588C6.63368 3.95653 6.85425 4.10405 7.04 4.29L7.1 4.35C7.33568 4.58054 7.63502 4.73519 7.95941 4.794C8.28381 4.85282 8.61838 4.81312 8.92 4.68H9C9.29577 4.55324 9.54802 4.34276 9.72569 4.07447C9.90337 3.80618 9.99872 3.49179 10 3.17V3C10 2.46957 10.2107 1.96086 10.5858 1.58579C10.9609 1.21071 11.4696 1 12 1C12.5304 1 13.0391 1.21071 13.4142 1.58579C13.7893 1.96086 14 2.46957 14 3V3.09C14.0013 3.41179 14.0966 3.72618 14.2743 3.99447C14.452 4.26276 14.7042 4.47324 15 4.6C15.3016 4.73312 15.6362 4.77282 15.9606 4.714C16.285 4.65519 16.5843 4.50054 16.82 4.27L16.88 4.21C17.0657 4.02405 17.2863 3.87653 17.5291 3.77588C17.7719 3.67523 18.0322 3.62343 18.295 3.62343C18.5578 3.62343 18.8181 3.67523 19.0609 3.77588C19.3037 3.87653 19.5243 4.02405 19.71 4.21C19.896 4.39575 20.0435 4.61632 20.1441 4.85912C20.2448 5.10192 20.2966 5.36217 20.2966 5.625C20.2966 5.88783 20.2448 6.14808 20.1441 6.39088C20.0435 6.63368 19.896 6.85425 19.71 7.04L19.65 7.1C19.4195 7.33568 19.2648 7.63502 19.206 7.95941C19.1472 8.28381 19.1869 8.61838 19.32 8.92V9C19.4468 9.29577 19.6572 9.54802 19.9255 9.72569C20.1938 9.90337 20.5082 9.99872 20.83 10H21C21.5304 10 22.0391 10.2107 22.4142 10.5858C22.7893 10.9609 23 11.4696 23 12C23 12.5304 22.7893 13.0391 22.4142 13.4142C22.0391 13.7893 21.5304 14 21 14H20.91C20.5882 14.0013 20.2738 14.0966 20.0055 14.2743C19.7372 14.452 19.5268 14.7042 19.4 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Tips & Praktik Terbaik
          </h3>
          <div class="help-item">
            <p class="help-item-title">✅ Backup Data Rutin</p>
            <p class="help-item-desc">Lakukan backup data secara berkala untuk menjaga keamanan informasi.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">✅ Update Informasi</p>
            <p class="help-item-desc">Pastikan data siswa dan guru selalu diperbarui dan akurat.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">✅ Gunakan Filter & Pencarian</p>
            <p class="help-item-desc">Manfaatkan fitur filter dan pencarian untuk menemukan data dengan cepat.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">✅ Kelola Persuratan dengan Baik</p>
            <p class="help-item-desc">Lampirkan dokumen yang diperlukan dan isi informasi dengan lengkap.</p>
          </div>
        </div>

        <!-- FAQ -->
        <div class="help-section">
          <h3 class="help-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 11C9 11.5523 9.44772 12 10 12H11C11.5523 12 12 11.5523 12 11V10C12 9.44772 11.5523 9 11 9H10C9.44772 9 9 9.44772 9 10V11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            FAQ
          </h3>
          <div class="help-item">
            <p class="help-item-title">Q: Bagaimana cara reset password?</p>
            <p class="help-item-desc">A: Gunakan fitur "Lupa Password" di halaman login atau hubungi administrator.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">Q: Bagaimana cara menambah akses modul?</p>
            <p class="help-item-desc">A: Akses menu "Kelola Akses Modul" dan aktifkan modul yang diinginkan.</p>
          </div>
          <div class="help-item">
            <p class="help-item-title">Q: Apakah data bisa diekspor?</p>
            <p class="help-item-desc">A: Ya, beberapa modul menyediakan fitur ekspor data dalam format Excel.</p>
          </div>
        </div>

        <!-- Bantuan -->
        <div class="help-section help-section-last">
          <h3 class="help-section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Butuh Bantuan?
          </h3>
          <div class="help-item">
            <p class="help-item-desc">Jika mengalami kendala atau memiliki pertanyaan, silakan hubungi tim support atau konsultasikan dengan administrator sistem.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const isOpen = ref(true)
const STORAGE_KEY = 'helpSidebarOpen'

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
  localStorage.setItem(STORAGE_KEY, isOpen.value.toString())
}

onMounted(() => {
  const savedState = localStorage.getItem(STORAGE_KEY)
  if (savedState !== null) {
    isOpen.value = savedState === 'true'
  }
})
</script>

<style scoped>
.help-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 997;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.help-sidebar {
  position: fixed;
  right: 0;
  top: 0;
  height: 100vh;
  z-index: 998;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: flex-start;
}

.help-sidebar-collapsed {
  transform: translateX(calc(100% - 50px));
}

.help-sidebar-open {
  transform: translateX(0);
}

.help-toggle-btn {
  position: absolute;
  left: -50px;
  top: 100px;
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px 0 0 10px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1000;
}

.help-toggle-btn:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  box-shadow: -4px 0 12px rgba(102, 126, 234, 0.3);
  transform: translateX(-2px);
}

.help-content {
  width: 320px;
  height: 100vh;
  background: white;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.help-header {
  padding: 24px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.help-header-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.help-header h2 {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.3px;
}

.help-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: #f8fafc;
}

.help-body::-webkit-scrollbar {
  width: 6px;
}

.help-body::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.help-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.help-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.help-section {
  margin-bottom: 24px;
}

.help-section-last {
  margin-bottom: 0;
}

.help-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.help-section-title svg {
  color: #667eea;
  flex-shrink: 0;
}

.help-item {
  background: white;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.help-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.help-item-title {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 6px 0;
  line-height: 1.4;
}

.help-item-desc {
  font-size: 12px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.help-item-desc strong {
  color: #1e293b;
  font-weight: 600;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .help-overlay {
    display: block;
  }

  .help-sidebar {
    transform: translateX(100%);
  }

  .help-sidebar-open {
    transform: translateX(0);
  }

  .help-toggle-btn {
    left: -44px;
    width: 44px;
    height: 44px;
    top: 70px;
  }

  .help-content {
    width: 300px;
  }
}

@media (max-width: 768px) {
  .help-content {
    width: 100%;
    max-width: 320px;
  }

  .help-toggle-btn {
    left: -40px;
    width: 40px;
    height: 40px;
    top: 60px;
    border-radius: 8px 0 0 8px;
  }

  .help-header {
    padding: 20px 16px;
  }

  .help-header h2 {
    font-size: 16px;
  }

  .help-body {
    padding: 16px;
  }

  .help-section {
    margin-bottom: 20px;
  }

  .help-item {
    padding: 10px;
  }
}

@media (max-width: 480px) {
  .help-sidebar-collapsed {
    transform: translateX(100%);
  }

  .help-toggle-btn {
    left: -36px;
    width: 36px;
    height: 36px;
  }
}
</style>
