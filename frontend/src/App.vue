<template>
  <div id="app">
    <ErrorBoundary>
      <router-view />
    </ErrorBoundary>
    <Toast />
    <PWAInstallPrompt />
    <OfflineStatus />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { appName, appTagline } from '@/config/app'
import Toast from '@/components/Toast.vue'
import ErrorBoundary from '@/components/ErrorBoundary.vue'
import PWAInstallPrompt from '@/components/PWAInstallPrompt.vue'
import OfflineStatus from '@/components/OfflineStatus.vue'
import { useAppBrandingStore } from '@/stores/appBranding'

const router = useRouter()

onMounted(() => {
  if (router.currentRoute.value.name !== 'SchoolPublic') {
    document.title = `${appName} - ${appTagline}`
    const desc = document.querySelector('meta[name="description"]')
    if (desc) desc.setAttribute('content', `${appName} - ${appTagline}. Sistem manajemen sekolah terintegrasi untuk sekolah dan madrasah di Indonesia. Kelola profil institusi, data siswa, guru, fasilitas, kelas, laporan, dan surat-menyurat dalam satu platform.`)
  }
  const appleTitle = document.querySelector('meta[name="apple-mobile-web-app-title"]')
  if (appleTitle) appleTitle.setAttribute('content', appName)
  useAppBrandingStore().fetchBranding()
})
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --primary-gradient: linear-gradient(135deg, #059669 0%, #047857 100%);
  --primary-color: #059669;
  --primary-hover: #047857;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --border-color: #e2e8f0;
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-tertiary: #f1f5f9;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
  --fs-body: 13px;
  --fs-title: 18px;
  --fs-subtitle: 12px;
  --fs-table: 13px;
  --lh: 1.45;
}

html {
  overflow-x: hidden;
  -webkit-text-size-adjust: 100%;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background-color: #f8fafc;
  color: var(--text-primary);
  font-size: var(--fs-body);
  line-height: var(--lh);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  overflow-x: hidden;
  min-width: 0;
}

#app {
  min-height: 100vh;
}

/* Scrollbar Styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Selection */
::selection {
  background: rgba(5, 150, 105, 0.2);
  color: var(--text-primary);
}

/* Focus styles */
*:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Smooth transitions */
a, button {
  transition: all 0.2s ease;
}

/* Typography improvements */
h1, h2, h3, h4, h5, h6 {
  font-weight: 700;
  line-height: 1.2;
  color: var(--text-primary);
}

p {
  margin-bottom: 1em;
}

/* Link styles */
a {
  color: var(--primary-color);
  text-decoration: none;
}

a:hover {
  color: var(--primary-hover);
}

/* Button base styles */
button {
  font-family: inherit;
  cursor: pointer;
  border: none;
  outline: none;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Input base styles */
input, textarea, select {
  font-family: inherit;
  font-size: inherit;
}

/* Tema hijau: tombol primary & back-link (override komponen) */
#app .btn-primary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
  transition: box-shadow 0.2s ease, transform 0.1s ease;
}
#app .btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
}
#app .btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
#app .back-link {
  display: inline-block;
  color: #059669;
  text-decoration: none;
  font-weight: 600;
  font-size: var(--fs-subtitle);
  transition: color 0.2s ease;
}
#app .back-link:hover {
  color: #047857;
  text-decoration: underline;
}

/* Utility classes */
.cursor-pointer {
  cursor: pointer;
}

.input-hidden {
  display: none;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: var(--text-muted);
}

.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }

/* Animation utilities */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeIn 0.3s ease-out;
}

/* Responsive Table Styles - semua wrapper tabel bisa scroll horizontal di mobile */
.table-container,
.table-wrap,
.table-scroll,
.data-table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  max-width: 100%;
}

.data-table {
  font-size: var(--fs-table);
}


@media (max-width: 768px) {
  .table-container {
    border-radius: 12px;
  }

  .data-table th,
  .data-table td {
    padding: 8px 10px;
    font-size: var(--fs-table);
  }

  .data-table th {
    font-size: 11px;
    padding: 8px 10px;
  }
}

@media (max-width: 480px) {
  .data-table th,
  .data-table td {
    padding: 10px 12px;
    font-size: 12px;
  }
}

/* Responsive Form Styles */
@media (max-width: 768px) {
  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="number"],
  input[type="date"],
  textarea,
  select {
    font-size: 16px; /* Prevents zoom on iOS */
  }
}

/* Touch-friendly button sizes */
@media (max-width: 768px) {
  button,
  .btn-primary,
  .btn-secondary,
  .btn-action {
    min-height: 44px;
    min-width: 44px;
    padding: 10px 16px;
  }
}

/* Responsive Grid Improvements */
@media (max-width: 768px) {
  .info-grid,
  .facilities-grid {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
  }
}

/* Responsive Modal/Dialog */
@media (max-width: 768px) {
  .modal-content {
    width: 95% !important;
    max-width: 95% !important;
    margin: 20px auto !important;
    max-height: 90vh !important;
  }
}

/* Responsive Filters */
@media (max-width: 768px) {
  .filters {
    flex-direction: column !important;
    gap: 12px !important;
  }

  .filters .search-input,
  .filters .filter-select {
    width: 100% !important;
    min-width: auto !important;
  }
}

/* Header + tombol aksi: stack di tablet agar tidak terpotong overflow/sidebar */
@media (max-width: 1024px) {
  .page-header .header-content,
  .main-content .header-content {
    flex-direction: column !important;
    align-items: stretch !important;
    justify-content: flex-start !important;
    gap: 12px !important;
  }

  .page-header .header-content > *,
  .main-content .header-content > * {
    min-width: 0;
    max-width: 100%;
  }

  .page-header .action-buttons-group,
  .page-header .header-actions,
  .main-content .page-header .action-buttons-group,
  .main-content .page-header .header-actions {
    width: 100% !important;
    max-width: 100% !important;
    margin-left: 0 !important;
    flex-wrap: wrap !important;
    flex-shrink: 1 !important;
  }

  .page-header .action-buttons-group .btn-compact,
  .page-header .action-buttons-group .btn-add,
  .page-header .header-actions > a,
  .page-header .header-actions > button,
  .page-header .header-content > .btn-primary,
  .page-header .header-content > .btn-secondary,
  .page-header .header-content > .btn-header {
    min-width: 0;
  }

  .main-content .toolbar,
  .main-content .tab-header,
  .main-content .header-row {
    flex-wrap: wrap;
    max-width: 100%;
  }

  .main-content .toolbar-actions,
  .main-content .toolbar .header-actions,
  .main-content .tab-header .btn-add,
  .main-content .tab-header > .btn-primary {
    max-width: 100%;
    flex-shrink: 1;
  }
}

@media (max-width: 768px) {
  .page-header h2 {
    font-size: var(--fs-title) !important;
  }
}

/* Layout sidebar: tampil di desktop & tablet (≥769px), sembunyi hanya di mobile (≤768px) */
@media screen and (min-width: 769px) {
  .layout .sidebar {
    left: 0 !important;
    top: 0 !important;
    position: fixed !important;
    width: 280px !important;
    min-width: 280px !important;
    z-index: 1000 !important;
    transform: none !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
  .layout .main-content {
    margin-left: 280px !important;
  }
}

/* Print styles */
@media print {
  body {
    background: white;
  }
  
  .sidebar,
  .topbar,
  .mobile-menu-btn,
  .btn-primary,
  .btn-secondary,
  .btn-edit,
  .btn-delete {
    display: none !important;
  }

  .layout .main-content {
    margin-left: 0 !important;
    padding: 0 !important;
  }
}
</style>
