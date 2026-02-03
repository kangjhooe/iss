<template>
  <div id="app">
    <ErrorBoundary>
      <router-view />
    </ErrorBoundary>
    <Toast />
  </div>
</template>

<script setup>
import Toast from '@/components/Toast.vue'
import ErrorBoundary from '@/components/ErrorBoundary.vue'
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --primary-color: #667eea;
  --primary-hover: #5568d3;
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
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background-color: #f8fafc;
  color: var(--text-primary);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
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
  background: rgba(102, 126, 234, 0.2);
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

/* Responsive Table Styles */
.table-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
  .table-container {
    border-radius: 12px;
  }

  .data-table {
    min-width: 600px;
  }

  .data-table th,
  .data-table td {
    padding: 12px 14px;
    font-size: 13px;
  }

  .data-table th {
    font-size: 11px;
    padding: 10px 14px;
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
  .stats-grid,
  .actions-grid,
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

/* Responsive Action Buttons */
@media (max-width: 768px) {
  .action-buttons-group {
    flex-direction: column !important;
    width: 100% !important;
    gap: 8px !important;
  }

  .action-buttons-group button,
  .action-buttons-group label {
    width: 100% !important;
    justify-content: center !important;
  }
}

/* Responsive Page Headers */
@media (max-width: 768px) {
  .page-header .header-content {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 16px !important;
  }

  .page-header h2 {
    font-size: 20px !important;
  }
}

/* Layout sidebar: pastikan selalu tampil di desktop (bukan print) */
@media screen {
  .layout .sidebar {
    left: 0 !important;
    top: 0 !important;
    position: fixed !important;
    width: 280px !important;
    min-width: 280px !important;
    z-index: 1000 !important;
  }
  @media (min-width: 481px) {
    .layout .sidebar {
      transform: none !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    .layout .main-content {
      margin-left: 280px !important;
    }
  }
}

/* Print styles */
@media print {
  body {
    background: white;
  }
  
  .sidebar,
  .btn-primary,
  .btn-secondary,
  .btn-edit,
  .btn-delete,
  .mobile-menu-btn {
    display: none !important;
  }
}
</style>
