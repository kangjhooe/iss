<template>
  <div v-if="show" class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>{{ title }}</h3>
        <button @click="handleCancel" class="btn-close" :disabled="loading">×</button>
      </div>
      <div class="modal-body">
        <p class="modal-message">
          {{ message }}
        </p>
        <p v-if="warning" class="modal-warning">
          {{ warning }}
        </p>
        <div class="modal-footer">
          <button type="button" @click="handleCancel" class="btn-secondary" :disabled="loading">
            {{ cancelText }}
          </button>
          <button
            type="button"
            @click="handleConfirm"
            :class="confirmVariant === 'primary' ? 'btn-confirm-primary' : 'btn-danger'"
            :disabled="loading"
          >
            {{ loading ? loadingText : confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Konfirmasi Hapus'
  },
  message: {
    type: String,
    required: true
  },
  warning: {
    type: String,
    default: 'Tindakan ini tidak dapat dibatalkan.'
  },
  confirmText: {
    type: String,
    default: 'Hapus'
  },
  cancelText: {
    type: String,
    default: 'Batal'
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Menghapus...'
  },
  confirmVariant: {
    type: String,
    default: 'danger',
    validator: (value) => ['danger', 'primary'].includes(value)
  },
  closeOnOverlay: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['confirm', 'cancel', 'update:show'])

const handleConfirm = () => {
  if (!props.loading) {
    emit('confirm')
  }
}

const handleCancel = () => {
  if (!props.loading) {
    emit('cancel')
    emit('update:show', false)
  }
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay && !props.loading) {
    handleCancel()
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
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
  z-index: 12000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.btn-close:hover:not(:disabled) {
  background: #f1f5f9;
  color: #64748b;
}

.btn-close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-body {
  padding: 24px;
}

.modal-message {
  font-size: 16px;
  color: #1e293b;
  margin-bottom: 24px;
}

.modal-warning {
  font-size: 14px;
  color: #ef4444;
  margin-bottom: 24px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.btn-secondary {
  padding: 10px 20px;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-danger,
.btn-confirm-primary {
  padding: 10px 20px;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-danger {
  background: #ef4444;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn-confirm-primary {
  background: #059669;
}

.btn-confirm-primary:hover:not(:disabled) {
  background: #047857;
}

.btn-danger:disabled,
.btn-confirm-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 16px;
  }
  
  .modal-content {
    max-width: 100%;
  }
  
  .modal-header {
    padding: 16px 20px;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-footer {
    flex-direction: column-reverse;
  }
  
  .modal-footer button {
    width: 100%;
  }
}
</style>
