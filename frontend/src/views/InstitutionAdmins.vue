<template>
  <Layout>
    <div class="admins-page">
      <div class="page-header">
        <div>
          <h2>Admin Institusi</h2>
          <p>Kelola akun admin sekolah/madrasah</p>
        </div>
        <div class="header-actions">
          <router-link to="/super-admin/onboard" class="btn-secondary btn-compact">Onboarding Sekolah</router-link>
          <button type="button" class="btn-primary btn-compact" @click="openCreateModal">Tambah Admin</button>
        </div>
      </div>

      <section v-if="pendingRequests.length" class="reset-requests">
        <div class="reset-requests-header">
          <div>
            <h3>Permintaan reset sandi</h3>
            <p>Sekolah mengajukan reset. Reset sandi, lalu kirim ke email mereka secara manual.</p>
          </div>
          <span class="reset-count">{{ pendingRequests.length }} menunggu</span>
        </div>
        <div class="reset-list">
          <article v-for="item in pendingRequests" :key="item.id" class="reset-card">
            <div class="reset-card-main">
              <strong>{{ item.institution?.name || item.user?.name || item.email }}</strong>
              <span>{{ item.user?.name || 'Admin' }} · {{ item.email }}</span>
              <span>NPSN {{ item.npsn }}{{ item.contact_phone ? ` · ${item.contact_phone}` : '' }}</span>
              <span v-if="item.note" class="reset-note">{{ item.note }}</span>
              <span class="reset-time">{{ formatRequestTime(item.created_at) }}</span>
            </div>
            <div class="reset-card-actions">
              <button
                type="button"
                class="btn-action btn-ok"
                :disabled="busyRequestId === item.id"
                @click="handleProcessRequest(item)"
              >
                Reset sandi
              </button>
              <button
                type="button"
                class="btn-action btn-warn"
                :disabled="busyRequestId === item.id"
                @click="handleRejectRequest(item)"
              >
                Tolak
              </button>
            </div>
          </article>
        </div>
      </section>

      <div class="filters filters-inline">
        <input
          v-model="filters.search"
          class="search-input"
          placeholder="Cari nama atau email..."
          @input="debouncedLoad"
        />
        <select v-model="filters.institution_id" class="filter-select" @change="loadAdmins">
          <option value="">Semua Institusi</option>
          <option v-for="inst in institutions" :key="inst.id" :value="String(inst.id)">
            {{ inst.name }}
          </option>
        </select>
        <select v-model="filters.is_active" class="filter-select" @change="loadAdmins">
          <option value="">Semua Status</option>
          <option value="1">Aktif</option>
          <option value="0">Nonaktif</option>
        </select>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" :cell-widths="['1fr', '1fr', '1fr', '100px', '160px']" />
      </div>

      <div v-else-if="admins.length === 0" class="empty-state">
        <h3>Belum ada admin institusi</h3>
        <p>Buat admin untuk sekolah yang sudah terdaftar, atau gunakan onboarding untuk sekolah baru.</p>
        <button type="button" class="btn-primary" @click="openCreateModal">Tambah Admin</button>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Institusi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="admin in admins" :key="admin.id">
              <td>{{ admin.name }}</td>
              <td>{{ admin.email }}</td>
              <td>
                <div class="inst-cell">
                  <strong>{{ admin.institution?.name || '—' }}</strong>
                  <span v-if="admin.institution?.npsn">{{ admin.institution.npsn }}</span>
                </div>
              </td>
              <td>
                <span :class="admin.is_active !== false ? 'badge-active' : 'badge-inactive'">
                  {{ admin.is_active !== false ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button
                    v-if="admin.is_active !== false"
                    type="button"
                    class="btn-action btn-ok"
                    title="Masuk sebagai admin ini"
                    :disabled="busyId === admin.id"
                    @click="handleImpersonate(admin)"
                  >
                    Impersonate
                  </button>
                  <button
                    type="button"
                    class="btn-action"
                    title="Reset sandi"
                    :disabled="busyId === admin.id"
                    @click="handleResetPassword(admin)"
                  >
                    Reset
                  </button>
                  <button
                    type="button"
                    class="btn-action"
                    :class="admin.is_active !== false ? 'btn-warn' : 'btn-ok'"
                    :disabled="busyId === admin.id"
                    @click="handleToggleStatus(admin)"
                  >
                    {{ admin.is_active !== false ? 'Nonaktifkan' : 'Aktifkan' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create modal -->
      <div v-if="showCreateModal" class="modal-overlay" @click.self="closeCreateModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Tambah Admin Institusi</h3>
            <button type="button" class="btn-close" @click="closeCreateModal">×</button>
          </div>
          <form class="modal-body" @submit.prevent="handleCreate">
            <div class="form-group">
              <label>Institusi *</label>
              <select v-model="form.institution_id" required class="form-control">
                <option value="">Pilih institusi</option>
                <option v-for="inst in institutions" :key="inst.id" :value="inst.id">
                  {{ inst.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Nama Admin *</label>
              <input v-model="form.name" type="text" required class="form-control" maxlength="255" />
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input v-model="form.email" type="email" required class="form-control" maxlength="255" />
            </div>
            <div class="form-group">
              <label>Sandi (opsional)</label>
              <input v-model="form.password" type="password" class="form-control" autocomplete="new-password" />
              <small class="form-hint">Kosongkan untuk generate otomatis. Min. 8 karakter, huruf besar/kecil, angka, simbol.</small>
            </div>
            <div v-if="form.password" class="form-group">
              <label>Konfirmasi Sandi</label>
              <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            </div>
            <p v-if="formError" class="form-error">{{ formError }}</p>
            <div class="modal-actions">
              <button type="button" class="btn-secondary" @click="closeCreateModal">Batal</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <ConfirmDialog
        :show="confirmDialog.show"
        :title="confirmDialog.title"
        :message="confirmDialog.message"
        :warning="confirmDialog.warning"
        :loading="confirmDialog.loading"
        @confirm="handleConfirm"
        @cancel="handleCancel"
        @update:show="confirmDialog.show = $event"
      />
      <AccountCredentialsModal
        :show="!!accountCredentials"
        :title="accountCredentials?.title"
        :name="accountCredentials?.name"
        :login-label="accountCredentials?.loginLabel || 'Email'"
        :login-value="accountCredentials?.loginValue"
        :password="accountCredentials?.password"
        :hint="accountCredentials?.hint"
        @close="accountCredentials = null"
      />
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import { institutionAdminApi } from '@/api/institutionAdmin'
import { institutionApi } from '@/api/institution'
import { passwordResetRequestApi } from '@/api/passwordResetRequest'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useConfirmDelete } from '@/composables/useConfirmDelete'

const toast = useToast()
const authStore = useAuthStore()
const route = useRoute()
const { confirmDialog, showConfirm, handleConfirm, handleCancel } = useConfirmDelete()

const loading = ref(true)
const saving = ref(false)
const busyId = ref(null)
const admins = ref([])
const institutions = ref([])
const pendingRequests = ref([])
const busyRequestId = ref(null)
const showCreateModal = ref(false)
const formError = ref('')
const accountCredentials = ref(null)
let searchTimer = null

const filters = ref({
  search: '',
  institution_id: '',
  is_active: ''
})

const form = ref({
  institution_id: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const loadPendingRequests = async () => {
  try {
    const res = await passwordResetRequestApi.getAll({ status: 'pending', per_page: 50 })
    pendingRequests.value = res.data?.data || []
  } catch {
    pendingRequests.value = []
  }
}

const formatRequestTime = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleProcessRequest = async (item) => {
  const confirmed = await showConfirm({
    title: 'Reset Sandi Admin',
    message: `Reset sandi untuk "${item.user?.name || item.email}" (${item.institution?.name || item.npsn})? Sandi baru akan digenerate otomatis.`,
    warning: 'Kirim sandi baru ke email sekolah secara manual. Sistem tidak mengirim email.'
  })
  if (!confirmed) return

  busyRequestId.value = item.id
  try {
    const res = await passwordResetRequestApi.process(item.id)
    await loadPendingRequests()
    if (res.data?.temporary_password) {
      accountCredentials.value = {
        title: 'Sandi admin berhasil direset',
        name: item.user?.name || item.email,
        loginLabel: 'Email',
        loginValue: item.email,
        password: res.data.temporary_password,
        hint: 'Kirim sandi ini ke email sekolah secara manual. Sistem tidak mengirim email.',
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Sandi berhasil direset')
    }
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memproses permintaan')
  } finally {
    busyRequestId.value = null
  }
}

const handleRejectRequest = async (item) => {
  const confirmed = await showConfirm({
    title: 'Tolak Permintaan',
    message: `Tolak permintaan reset sandi dari "${item.institution?.name || item.email}"?`,
    warning: 'Sekolah dapat mengajukan ulang jika masih memerlukan reset.'
  })
  if (!confirmed) return

  busyRequestId.value = item.id
  try {
    const res = await passwordResetRequestApi.reject(item.id)
    toast.success('Berhasil', res.data?.message || 'Permintaan ditolak')
    await loadPendingRequests()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menolak permintaan')
  } finally {
    busyRequestId.value = null
  }
}

const loadInstitutions = async () => {
  try {
    const res = await institutionApi.getAll({ per_page: 100 })
    institutions.value = res.data?.data || []
  } catch {
    institutions.value = []
  }
}

const loadAdmins = async () => {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.institution_id) params.institution_id = filters.value.institution_id
    if (filters.value.is_active !== '') params.is_active = filters.value.is_active === '1'
    const res = await institutionAdminApi.getAll(params)
    admins.value = res.data?.data || []
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat admin institusi')
    admins.value = []
  } finally {
    loading.value = false
  }
}

const debouncedLoad = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(loadAdmins, 350)
}

const openCreateModal = () => {
  form.value = {
    institution_id: filters.value.institution_id || '',
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  }
  formError.value = ''
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  formError.value = ''
}

const handleCreate = async () => {
  formError.value = ''
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    formError.value = 'Konfirmasi sandi tidak cocok'
    return
  }
  saving.value = true
  try {
    const payload = {
      institution_id: Number(form.value.institution_id),
      name: form.value.name,
      email: form.value.email
    }
    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }
    const res = await institutionAdminApi.create(payload)
    const createdEmail = res.data?.data?.email || payload.email
    const createdName = res.data?.data?.name || payload.name
    const createdPassword = res.data?.temporary_password || payload.password
    closeCreateModal()
    await loadAdmins()
    if (createdPassword) {
      accountCredentials.value = {
        title: 'Akun admin berhasil dibuat',
        name: createdName,
        loginLabel: 'Email',
        loginValue: createdEmail,
        password: createdPassword,
        hint: 'Sandi hanya ditampilkan sekali. Berikan kepada admin secara aman dan minta ganti setelah login.',
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Admin berhasil dibuat')
    }
  } catch (err) {
    formError.value = err.response?.data?.message || 'Gagal membuat admin'
    if (err.response?.data?.errors) {
      const first = Object.values(err.response.data.errors)[0]
      if (Array.isArray(first) && first[0]) formError.value = first[0]
    }
  } finally {
    saving.value = false
  }
}

const handleImpersonate = async (admin) => {
  const confirmed = await showConfirm({
    title: 'Impersonate Admin',
    message: `Masuk sebagai "${admin.name}" (${admin.email}) untuk support? Semua aksi akan tercatat di audit log.`,
    warning: 'Anda dapat kembali ke Super Admin kapan saja lewat banner kuning.'
  })
  if (!confirmed) return

  busyId.value = admin.id
  try {
    await authStore.startImpersonate(admin.id)
    toast.success('Berhasil', `Sekarang menyamar sebagai ${admin.name}`)
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || err.message || 'Gagal impersonate')
  } finally {
    busyId.value = null
  }
}

const handleResetPassword = async (admin) => {
  const confirmed = await showConfirm({
    title: 'Reset Sandi Admin',
    message: `Reset sandi untuk "${admin.name}" (${admin.email})? Sandi baru akan digenerate otomatis.`,
    warning: 'Sandi lama tidak dapat digunakan lagi.'
  })
  if (!confirmed) return

  busyId.value = admin.id
  try {
    const res = await institutionAdminApi.resetPassword(admin.id)
    if (res.data?.temporary_password) {
      accountCredentials.value = {
        title: 'Sandi admin berhasil direset',
        name: admin.name,
        loginLabel: 'Email',
        loginValue: admin.email,
        password: res.data.temporary_password,
        hint: 'Sandi lama tidak dapat digunakan. Kirim sandi baru ke email sekolah secara manual.',
      }
    } else {
      toast.success('Berhasil', res.data?.message || 'Sandi berhasil direset')
    }
    await loadPendingRequests()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal reset sandi')
  } finally {
    busyId.value = null
  }
}

const handleToggleStatus = async (admin) => {
  const willActivate = admin.is_active === false
  const confirmed = await showConfirm({
    title: willActivate ? 'Aktifkan Admin' : 'Nonaktifkan Admin',
    message: willActivate
      ? `Aktifkan kembali "${admin.name}"?`
      : `Nonaktifkan "${admin.name}"? Admin tidak dapat login dan sesi aktif akan dihapus.`,
    warning: willActivate ? 'Akun dapat digunakan kembali.' : 'Admin tidak dapat masuk sampai diaktifkan.'
  })
  if (!confirmed) return

  busyId.value = admin.id
  try {
    const res = await institutionAdminApi.updateStatus(admin.id, willActivate)
    toast.success('Berhasil', res.data?.message || 'Status diperbarui')
    await loadAdmins()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memperbarui status')
  } finally {
    busyId.value = null
  }
}

onMounted(async () => {
  if (route.query.institution_id) {
    filters.value.institution_id = String(route.query.institution_id)
  }
  await Promise.all([loadInstitutions(), loadAdmins(), loadPendingRequests()])
})
</script>

<style scoped>
.admins-page {
  width: 100%;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0 0 4px;
  font-size: 24px;
  color: #0f172a;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.reset-requests {
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 16px;
  padding: 16px 18px;
  margin-bottom: 20px;
}

.reset-requests-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.reset-requests-header h3 {
  margin: 0 0 4px;
  font-size: 16px;
  color: #9a3412;
}

.reset-requests-header p {
  margin: 0;
  font-size: 13px;
  color: #c2410c;
}

.reset-count {
  flex-shrink: 0;
  background: #ea580c;
  color: white;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
}

.reset-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.reset-card {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  background: white;
  border: 1px solid #fed7aa;
  border-radius: 12px;
  padding: 12px 14px;
}

.reset-card-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.reset-card-main strong {
  color: #0f172a;
  font-size: 14px;
}

.reset-card-main span {
  font-size: 12px;
  color: #64748b;
}

.reset-note {
  color: #334155 !important;
}

.reset-time {
  color: #94a3b8 !important;
}

.reset-card-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.filters-inline {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.search-input,
.filter-select,
.form-control {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  background: white;
}

.search-input {
  min-width: 220px;
  flex: 1;
}

.table-container {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
}

.data-table th {
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.inst-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.inst-cell span {
  font-size: 12px;
  color: #94a3b8;
}

.badge-active,
.badge-inactive {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.badge-active {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
}

.badge-inactive {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-action {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  color: #334155;
}

.btn-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-warn {
  color: #b45309;
}

.btn-ok {
  color: #059669;
}

.empty-state {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 40px 24px;
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 8px;
  color: #0f172a;
}

.empty-state p {
  margin: 0 0 16px;
  color: #64748b;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  max-height: 90vh;
  overflow: auto;
}

.modal-sm {
  max-width: 420px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 20px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.btn-close {
  border: none;
  background: transparent;
  font-size: 24px;
  cursor: pointer;
  color: #64748b;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 14px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.form-control {
  width: 100%;
  box-sizing: border-box;
}

.form-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #94a3b8;
}

.form-error {
  color: #dc2626;
  font-size: 13px;
  margin: 0 0 12px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.btn-primary,
.btn-secondary {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-primary {
  background: #059669;
  color: white;
  border: none;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-compact {
  padding: 8px 12px;
  font-size: 13px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions > * {
    flex: 1;
    justify-content: center;
    text-align: center;
  }

  .reset-card {
    flex-direction: column;
  }

  .reset-card-actions {
    flex-direction: row;
  }

  .filters-inline {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }

  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .data-table {
    min-width: 680px;
  }

  .action-buttons {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-action {
    width: 100%;
    text-align: center;
  }

  .modal-overlay {
    align-items: flex-start;
    padding: 12px;
  }

  .modal-content {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header h2 {
    font-size: 1.25rem;
  }
}
</style>
