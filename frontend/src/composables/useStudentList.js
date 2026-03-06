import { ref } from 'vue'
import { studentApi } from '@/api/student'

/**
 * Composable for student list: filters, load data, and table helpers.
 */
export function useStudentList() {
  const students = ref([])
  const loading = ref(true)
  const error = ref('')

  const filters = ref({
    search: '',
    class_id: '',
    status: '',
    only_trashed: false
  })

  const loadStudents = async () => {
    loading.value = true
    error.value = ''
    try {
      const params = {}
      if (filters.value.search) params.search = filters.value.search
      if (filters.value.class_id) params.class_id = filters.value.class_id
      if (filters.value.status) params.status = filters.value.status
      if (filters.value.only_trashed) params.only_trashed = true

      const response = await studentApi.getAll(params)
      students.value = response.data?.data ?? []
    } catch (err) {
      error.value = 'Gagal memuat data siswa. Silakan coba lagi.'
      console.error(err)
    } finally {
      loading.value = false
    }
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
    loadStudents,
    getStatusClass
  }
}
