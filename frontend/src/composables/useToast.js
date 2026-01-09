/**
 * Composable untuk menggunakan toast notifications
 * 
 * Usage:
 * import { useToast } from '@/composables/useToast'
 * 
 * const toast = useToast()
 * toast.success('Berhasil!', 'Data berhasil disimpan')
 * toast.error('Error!', 'Terjadi kesalahan')
 * toast.warning('Peringatan!', 'Perhatikan hal ini')
 * toast.info('Info', 'Informasi penting')
 */

export function useToast() {
  const getToastInstance = () => {
    if (window.__toast) {
      return window.__toast
    }
    console.warn('Toast component belum di-mount. Pastikan Toast.vue ada di App.vue')
    return null
  }

  const show = (type, title, message = '', duration = 5000) => {
    const toast = getToastInstance()
    if (toast) {
      return toast.addToast({ type, title, message, duration })
    }
  }

  return {
    success: (title, message = '', duration = 5000) => show('success', title, message, duration),
    error: (title, message = '', duration = 5000) => show('error', title, message, duration),
    warning: (title, message = '', duration = 5000) => show('warning', title, message, duration),
    info: (title, message = '', duration = 5000) => show('info', title, message, duration),
    show,
    remove: (id) => {
      const toast = getToastInstance()
      if (toast) {
        toast.removeToast(id)
      }
    },
    clearAll: () => {
      const toast = getToastInstance()
      if (toast) {
        toast.clearAll()
      }
    }
  }
}
