<template>
  <div v-if="hasError" class="error-boundary">
    <div class="error-content">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
        <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <h2>Terjadi Kesalahan</h2>
      <p>{{ errorMessage }}</p>
      <div class="error-actions">
        <button @click="retry" class="btn-primary">
          Coba Lagi
        </button>
        <button @click="goHome" class="btn-secondary">
          Kembali ke Home
        </button>
      </div>
      <details v-if="showDetails" class="error-details">
        <summary>Detail Error</summary>
        <pre>{{ errorDetails }}</pre>
      </details>
    </div>
  </div>
  <slot v-else />
</template>

<script setup>
import { ref, onErrorCaptured, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const hasError = ref(false)
const errorMessage = ref('Terjadi kesalahan yang tidak terduga. Silakan coba lagi.')
const errorDetails = ref('')
const showDetails = ref(false)

onErrorCaptured((err, instance, info) => {
  hasError.value = true
  errorMessage.value = err.message || 'Terjadi kesalahan yang tidak terduga.'
  errorDetails.value = `${err.toString()}\n\nComponent: ${instance?.$?.type?.name || 'Unknown'}\n\nInfo: ${info}`
  
  // Log error untuk debugging
  console.error('Error Boundary caught:', err, info)
  
  // Show details in development
  if (import.meta.env.DEV) {
    showDetails.value = true
  }
  
  return false // Prevent error from propagating
})

const retry = () => {
  hasError.value = false
  errorMessage.value = ''
  errorDetails.value = ''
  window.location.reload()
}

const goHome = () => {
  router.push('/')
  hasError.value = false
}

onMounted(() => {
  // Handle unhandled promise rejections
  window.addEventListener('unhandledrejection', (event) => {
    hasError.value = true
    errorMessage.value = event.reason?.message || 'Terjadi kesalahan saat memproses request.'
    errorDetails.value = event.reason?.toString() || ''
    
    if (import.meta.env.DEV) {
      showDetails.value = true
    }
    
    console.error('Unhandled promise rejection:', event.reason)
  })
})
</script>

<style scoped>
.error-boundary {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: #f8fafc;
}

.error-content {
  max-width: 500px;
  text-align: center;
  background: white;
  padding: 48px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.error-content svg {
  color: #dc2626;
  margin-bottom: 24px;
}

.error-content h2 {
  font-size: 24px;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 12px;
}

.error-content p {
  color: #64748b;
  margin-bottom: 32px;
  font-size: 16px;
}

.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.btn-primary {
  padding: 12px 24px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s ease;
}

.btn-primary:hover {
  background: #5568d3;
}

.btn-secondary {
  padding: 12px 24px;
  background: white;
  color: #667eea;
  border: 1px solid #667eea;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s ease;
}

.btn-secondary:hover {
  background: #f8fafc;
}

.error-details {
  margin-top: 32px;
  text-align: left;
}

.error-details summary {
  cursor: pointer;
  color: #667eea;
  font-size: 14px;
  margin-bottom: 12px;
}

.error-details pre {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  overflow-x: auto;
  font-size: 12px;
  color: #64748b;
  border: 1px solid #e2e8f0;
}
</style>
