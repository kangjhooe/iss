<template>
  <div class="home-container">
    <div class="home-content">
      <div class="header">
        <div class="header-content">
          <div class="header-left">
            <div class="logo">
              <svg width="56" height="56" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#667eea"/>
                <path d="M2 17L12 22L22 17" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12L12 17L22 12" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div class="header-text">
              <h1>Indonesia Smart School</h1>
              <p class="subtitle">Sistem Manajemen Sekolah Terintegrasi</p>
            </div>
          </div>
          <div class="header-actions">
            <router-link to="/login" class="btn btn-primary">Masuk</router-link>
            <router-link to="/register" class="btn btn-secondary">Daftar</router-link>
          </div>
        </div>
      </div>

      <div class="description">
        <p>
          Aplikasi manajemen sekolah terintegrasi untuk seluruh sekolah di Indonesia, 
          dari berbagai jenjang dan jenis sekolah. Kelola data siswa, guru, dan institusi 
          dengan mudah dan efisien.
        </p>
      </div>

      <div class="features">
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>Manajemen Profil Sekolah</h3>
          <p>Kelola informasi dan profil institusi sekolah Anda dengan mudah</p>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="12" cy="7" r="4" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>Manajemen Data Siswa</h3>
          <p>Kelola data siswa secara terpusat dan terorganisir</p>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="9" cy="7" r="4" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3>Manajemen Data Guru</h3>
          <p>Kelola informasi dan data guru dengan sistem yang terintegrasi</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  padding: 60px 24px;
}

.home-content {
  width: 100%;
  max-width: 1000px;
  text-align: center;
}

.header {
  margin-bottom: 56px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 0;
}

.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 32px;
}

.header-text {
  text-align: center;
}

.header h1 {
  font-size: 48px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 12px;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.subtitle {
  font-size: 18px;
  color: #64748b;
  margin: 0;
  font-weight: 400;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-shrink: 0;
}

.description {
  margin-bottom: 64px;
  max-width: 680px;
  margin-left: auto;
  margin-right: auto;
}

.description p {
  font-size: 17px;
  color: #475569;
  line-height: 1.75;
  margin: 0;
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 32px;
  margin-bottom: 64px;
}

.feature-item {
  padding: 40px 32px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.feature-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.feature-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.feature-icon svg {
  color: #667eea;
}

.feature-item h3 {
  font-size: 19px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 10px;
  letter-spacing: -0.01em;
}

.feature-item p {
  font-size: 15px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

.btn {
  padding: 14px 32px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: none;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.btn-secondary {
  background: white;
  color: #667eea;
  border: 1.5px solid #667eea;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #5568d3;
  color: #5568d3;
}

@media (max-width: 768px) {
  .home-container {
    padding: 48px 20px;
  }

  .header h1 {
    font-size: 36px;
  }

  .subtitle {
    font-size: 16px;
  }

  .description {
    margin-bottom: 48px;
  }

  .description p {
    font-size: 16px;
  }

  .features {
    grid-template-columns: 1fr;
    gap: 24px;
    margin-bottom: 48px;
  }

  .feature-item {
    padding: 32px 24px;
  }

  .header-content {
    flex-direction: column;
    align-items: center;
  }

  .header-actions {
    width: 100%;
    justify-content: center;
  }

  .btn {
    flex: 1;
    min-width: 120px;
  }
}

@media (max-width: 480px) {
  .home-container {
    padding: 40px 16px;
  }

  .header h1 {
    font-size: 28px;
  }

  .subtitle {
    font-size: 15px;
  }

  .description p {
    font-size: 15px;
  }

  .header-actions {
    flex-direction: column;
    width: 100%;
  }

  .btn {
    width: 100%;
  }
}
</style>