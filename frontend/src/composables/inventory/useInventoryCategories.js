import { ref } from 'vue'
import { inventoryApi } from '@/api/inventory'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'
import { safeArray, parsePagination } from './inventoryApiHelpers'

export function useInventoryCategories() {
  const toast = useToast()
  const { showConfirm, setLoading: setDeleteLoading } = useConfirmDelete()

  const categories = ref([])
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const filters = ref({ search: '', is_active: '' })
  let searchTimeout = null

  const showModal = ref(false)
  const editing = ref(null)
  const saving = ref(false)
  const error = ref('')
  const form = ref({ code: '', name: '', description: '', is_active: true })

  async function load(page = 1) {
    loading.value = true
    try {
      const params = { page, per_page: meta.value.per_page }
      if (filters.value.search) params.search = filters.value.search
      if (filters.value.is_active !== '') params.is_active = filters.value.is_active
      const res = await inventoryApi.getCategories(params)
      categories.value = safeArray(res)
      meta.value = parsePagination(res, meta.value)
    } catch (err) {
      toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal memuat kategori')
      categories.value = []
    } finally {
      loading.value = false
    }
  }

  function debounceLoad() {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => load(1), 400)
  }

  function changePerPage(n) {
    meta.value.per_page = n
    meta.value.current_page = 1
    load(1)
  }

  function openModal(cat = null) {
    editing.value = cat
    error.value = ''
    form.value = cat
      ? { code: cat.code || '', name: cat.name || '', description: cat.description || '', is_active: !!cat.is_active }
      : { code: '', name: '', description: '', is_active: true }
    showModal.value = true
  }

  function closeModal() {
    showModal.value = false
    editing.value = null
    error.value = ''
  }

  async function save() {
    saving.value = true
    error.value = ''
    try {
      const payload = {
        code: (form.value.code || '').trim(),
        name: (form.value.name || '').trim(),
        description: form.value.description ? form.value.description.trim() : null,
        is_active: !!form.value.is_active
      }
      if (editing.value) {
        await inventoryApi.updateCategory(editing.value.id, payload)
        toast.success('Berhasil', 'Kategori berhasil diperbarui')
      } else {
        await inventoryApi.createCategory(payload)
        toast.success('Berhasil', 'Kategori berhasil ditambahkan')
      }
      closeModal()
      await load(meta.value.current_page || 1)
    } catch (err) {
      const msg = err.formattedMessage || err.response?.data?.message || 'Gagal menyimpan kategori'
      error.value = msg
      toast.error('Gagal', msg)
    } finally {
      saving.value = false
    }
  }

  async function remove(cat) {
    const confirmed = await showConfirm({
      title: 'Konfirmasi Hapus',
      message: `Hapus kategori "${cat.name}"?`,
      warning: 'Kategori akan dihapus secara permanen.'
    })
    if (!confirmed) return
    setDeleteLoading(true)
    try {
      await inventoryApi.deleteCategory(cat.id)
      toast.success('Berhasil', 'Kategori berhasil dihapus')
      await load(meta.value.current_page || 1)
    } catch (err) {
      toast.error('Gagal', err.formattedMessage || err.response?.data?.message || 'Gagal menghapus kategori')
    } finally {
      setDeleteLoading(false)
    }
  }

  return {
    categories,
    loading,
    meta,
    filters,
    showModal,
    editing,
    saving,
    error,
    form,
    load,
    debounceLoad,
    changePerPage,
    openModal,
    closeModal,
    save,
    remove
  }
}
