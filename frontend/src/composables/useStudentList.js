import { ref } from 'vue'
import { studentApi } from '@/api/student'

/**
 * Composable for student list: filters, load data, and table helpers.
 */
export function useStudentList() {
  const students = ref([])
  const loading = ref(true)
  const error = ref('')
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  const filters = ref({
    search: '',
    class_id: '',
    tingkat: '',
    status: '',
    only_trashed: false
  })

  const loadStudents = async (page = pagination.value.current_page) => {
    loading.value = true
    error.value = ''
    try {
      const params = {
        page,
        per_page: pagination.value.per_page
      }
      if (filters.value.search) params.search = filters.value.search
      if (filters.value.class_id) params.class_id = filters.value.class_id
      if (filters.value.tingkat) params.tingkat = filters.value.tingkat
      if (filters.value.status) params.status = filters.value.status
      if (filters.value.only_trashed) params.only_trashed = true

      const response = await studentApi.getAll(params)
      students.value = response.data?.data ?? []
      const meta = response.data?.meta ?? {}
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        per_page: meta.per_page ?? pagination.value.per_page,
        total: meta.total ?? students.value.length
      }
    } catch (err) {
      error.value = 'Gagal memuat data siswa. Silakan coba lagi.'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const goToPage = (page) => {
    if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) {
      return
    }

    loadStudents(page)
  }

  const getStatusClass = (status) => {
    const map = {
      'Aktif': 'status-active',
      'Lulus': 'status-success',
      'Pindah': 'status-warning',
      'Drop Out': 'status-danger',
      'Tidak Aktif': 'status-inactive'
    }
    return map[status] || ''
  }

  return {
    students,
    loading,
    error,
    filters,
    pagination,
    loadStudents,
    goToPage,
    getStatusClass
  }
}
