/**
 * Composable untuk confirm delete dialog
 * 
 * Usage:
 * import { useConfirmDelete } from '@/composables/useConfirmDelete'
 * 
 * const { showConfirm, confirmDialog } = useConfirmDelete()
 * 
 * const handleDelete = async (id) => {
 *   const confirmed = await showConfirm({
 *     message: 'Apakah Anda yakin ingin menghapus item ini?',
 *     title: 'Konfirmasi Hapus'
 *   })
 *   
 *   if (confirmed) {
 *     // Proceed with delete
 *   }
 * }
 */

import { ref } from 'vue'

export function useConfirmDelete() {
  const confirmDialog = ref({
    show: false,
    title: 'Konfirmasi Hapus',
    message: '',
    warning: 'Tindakan ini tidak dapat dibatalkan.',
    loading: false
  })

  const showConfirm = (options = {}) => {
    return new Promise((resolve) => {
      confirmDialog.value = {
        show: true,
        title: options.title || 'Konfirmasi Hapus',
        message: options.message || 'Apakah Anda yakin ingin menghapus item ini?',
        warning: options.warning || 'Tindakan ini tidak dapat dibatalkan.',
        loading: false
      }

      // Store resolve function
      confirmDialog.value._resolve = resolve
    })
  }

  const handleConfirm = () => {
    if (confirmDialog.value._resolve) {
      confirmDialog.value._resolve(true)
      confirmDialog.value._resolve = null
    }
    confirmDialog.value.show = false
  }

  const handleCancel = () => {
    if (confirmDialog.value._resolve) {
      confirmDialog.value._resolve(false)
      confirmDialog.value._resolve = null
    }
    confirmDialog.value.show = false
  }

  const setLoading = (loading) => {
    confirmDialog.value.loading = loading
  }

  return {
    confirmDialog,
    showConfirm,
    handleConfirm,
    handleCancel,
    setLoading
  }
}
