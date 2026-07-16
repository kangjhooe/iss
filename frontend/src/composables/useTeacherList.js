import { ref, onMounted } from 'vue'
import { employeeApi } from '@/api/teacher'

/**
 * Composable for teacher list: load data, filters, and table helpers.
 */
export function useTeacherList() {
  const teachers = ref([])
  const loading = ref(true)
  const error = ref('')

  const filters = ref({
    search: '',
    status: '',
    type: '',
    employment_status: '',
    only_trashed: false
  })

  const loadTeachers = async () => {
    loading.value = true
    error.value = ''
    try {
      const params = { per_page: 200 }
      if (filters.value.search) params.search = filters.value.search
      if (filters.value.status) params.status = filters.value.status
      if (filters.value.type) params.type = filters.value.type
      if (filters.value.employment_status) params.employment_status = filters.value.employment_status
      if (filters.value.only_trashed) params.only_trashed = true

      const response = await employeeApi.getAll(params)
      const list = response.data?.data ?? response.data ?? []
      teachers.value = Array.isArray(list) ? list : []
    } catch (err) {
      error.value = 'Gagal memuat data guru'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  const getTeacherSubject = (teacher) => {
    if (teacher?.affiliation === 'non_induk') {
      return teacher?.current_assignment?.subject || '-'
    }
    return teacher?.subject || '-'
  }

  const getStatusClass = (status) => {
    const classes = {
      'Aktif': 'status-active',
      'Cuti': 'status-leave',
      'Pensiun': 'status-success',
      'Pindah': 'status-warning',
      'Mengundurkan Diri': 'status-resigned',
      'Tidak Aktif': 'status-inactive'
    }
    return classes[status] || ''
  }

  onMounted(() => {
    loadTeachers()
  })

  return {
    teachers,
    loading,
    error,
    filters,
    loadTeachers,
    getTeacherSubject,
    getStatusClass
  }
}
