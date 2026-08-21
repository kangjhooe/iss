<template>
  <Layout>
    <div class="banks-page">
      <!-- Breadcrumb -->
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <router-link to="/ujian-online" class="breadcrumb-link">Ujian Online</router-link>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current">Bank Soal</span>
      </nav>

      <header class="page-header">
        <div class="header-content">
          <div>
            <p class="page-subtitle">Gudang soal jangka panjang per mapel. Tingkat hanya label rak — soal kelas 7 tetap boleh dipakai di ujian kelas 9.</p>
          </div>
          <div class="action-buttons-group header-actions">
            <button type="button" class="btn-primary btn-compact btn-add-new" @click="openForm()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
              </svg>
              <span>Tambah Bank</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Filters -->
      <div class="filters">
        <input
          v-model="filterSearch"
          type="search"
          class="filter-search"
          placeholder="Cari kode atau nama bank..."
          autocomplete="off"
          @keydown.enter.prevent="fetchBanks"
        />
        <select v-model="filterSubjectId" @change="fetchBanks" class="filter-select" aria-label="Filter mata pelajaran">
          <option value="">Semua mapel</option>
          <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select v-if="gradeOptions.length" v-model="filterGrade" @change="fetchBanks" class="filter-select" aria-label="Filter tingkat rak">
          <option value="">Semua tingkat</option>
          <option v-for="g in gradeOptions" :key="g" :value="String(g)">Kelas {{ g }}</option>
        </select>
        <button type="button" class="btn-secondary btn-filter" @click="fetchBanks">Cari</button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-wrap content-card">
        <div class="loading-inner">
          <div class="spinner" aria-hidden="true"></div>
          <p>Memuat bank soal...</p>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="banks.length === 0" class="empty-state content-card">
        <div class="empty-icon" aria-hidden="true">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 7h8M8 11h8M8 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3>Belum ada bank soal</h3>
        <p>Buat bank soal per mapel sebagai gudang jangka panjang. Tingkat rak opsional — soal tetap bisa dipakai di ujian tingkat lain.</p>
        <button type="button" class="btn-primary btn-compact" @click="openForm()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          </svg>
          <span>Tambah Bank</span>
        </button>
      </div>

      <!-- Table -->
      <div v-else class="table-card content-card">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-kode">Kode</th>
                <th class="col-nama">Nama</th>
                <th class="col-mapel">Mapel</th>
                <th class="col-grade">Tingkat</th>
                <th class="col-keterangan">Keterangan</th>
                <th class="col-count">Jumlah Soal</th>
                <th class="col-types">Per tipe</th>
                <th class="col-actions">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in banks" :key="b.id">
                <td><span class="cell-code">{{ b.code }}</span></td>
                <td class="col-nama">{{ b.name || '—' }}</td>
                <td>{{ b.subject?.name ?? '—' }}</td>
                <td class="col-grade">
                  <span v-if="b.grade" class="grade-chip" title="Label rak, bukan batasan pemakaian">Kelas {{ b.grade }}</span>
                  <span v-else class="muted">Campur</span>
                </td>
                <td class="cell-keterangan">{{ (b.keterangan || '').slice(0, 50) }}{{ (b.keterangan && b.keterangan.length > 50) ? '…' : '' }}</td>
                <td class="col-count"><span class="badge-count">{{ b.questions_count ?? 0 }}</span></td>
                <td class="col-types">
                  <div class="type-chips" :title="typeSummaryTitle(b)">
                    <span v-if="(b.questions_by_type?.pg || 0) > 0" class="type-chip">PG {{ b.questions_by_type.pg }}</span>
                    <span v-if="(b.questions_by_type?.pg_kompleks || 0) > 0" class="type-chip">PGK {{ b.questions_by_type.pg_kompleks }}</span>
                    <span v-if="(b.questions_by_type?.matching || 0) > 0" class="type-chip">Match {{ b.questions_by_type.matching }}</span>
                    <span v-if="(b.questions_by_type?.isian || 0) > 0" class="type-chip">Isian {{ b.questions_by_type.isian }}</span>
                    <span v-if="(b.questions_by_type?.uraian || 0) > 0" class="type-chip">Uraian {{ b.questions_by_type.uraian }}</span>
                    <span v-if="!(b.questions_count > 0)" class="type-chip type-chip--empty">—</span>
                  </div>
                </td>
                <td class="col-actions">
                  <div class="row-actions">
                    <button type="button" class="btn-action-text btn-manage" @click="goToManageSoal(b)">Kelola soal</button>
                    <router-link :to="`/ujian-online/bank-soal/${b.id}/stimulus`" class="btn-action-text btn-stimulus">Stimulus</router-link>
                    <TableAction kind="edit" @click="openForm(b)" />
                    <div class="more-wrap">
                      <button
                        type="button"
                        class="btn-action-text btn-more"
                        :aria-expanded="openMenuId === b.id"
                        @click.stop="toggleMenu(b.id)"
                      >
                        Lainnya ▾
                      </button>
                      <div v-if="openMenuId === b.id" class="more-menu" @click.stop>
                        <button type="button" @click="openShareModal(b); openMenuId = null">Bagikan</button>
                        <button type="button" :disabled="backupLoading === b.id" @click="doBackup(b); openMenuId = null">
                          {{ backupLoading === b.id ? 'Backup…' : 'Backup ZIP' }}
                        </button>
                        <button type="button" @click="openRestoreModal(b); openMenuId = null">Restore ZIP</button>
                        <button type="button" class="danger" @click="confirmDelete(b); openMenuId = null">Hapus</button>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah/Edit -->
      <Teleport to="body">
        <div v-if="showForm" class="modal-overlay form-modal-overlay" @click.self="showForm = false">
          <div class="modal-content form-modal-content bank-modal" @click.stop>
            <div class="form-modal-header">
              <div class="form-modal-title-wrap">
                <div class="form-modal-icon bank-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div>
                  <h3 class="form-modal-title">{{ editingId ? 'Edit bank soal' : 'Tambah bank soal' }}</h3>
                  <p class="form-modal-subtitle">{{ editingId ? 'Perbarui data bank soal' : 'Isi kode dan mapel untuk bank baru' }}</p>
                </div>
              </div>
              <button type="button" class="btn-close-modal" @click="showForm = false" aria-label="Tutup">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
            <div class="form-modal-body">
              <div class="form-group">
                <label for="bank-code">Kode bank <span class="required">*</span></label>
                <input id="bank-code" v-model="form.code" type="text" placeholder="Contoh: MTK-10" required />
              </div>
              <div class="form-group">
                <label for="bank-name">Nama</label>
                <input id="bank-name" v-model="form.name" type="text" placeholder="Contoh: Matematika — aljabar" />
              </div>
              <div class="form-group">
                <label for="bank-subject">Mata pelajaran <span class="required">*</span></label>
                <select id="bank-subject" v-model="form.subject_id" required :disabled="formLoading">
                  <option value="">{{ formLoading ? 'Memuat...' : 'Pilih mapel' }}</option>
                  <option v-for="s in subjects" :key="s.id" :value="Number(s.id)">{{ s.name }}</option>
                </select>
              </div>
              <div v-if="gradeOptions.length" class="form-group">
                <label for="bank-grade">Tingkat rak (opsional)</label>
                <select id="bank-grade" v-model="form.grade">
                  <option value="">Tidak ditentukan (campur)</option>
                  <option v-for="g in gradeOptions" :key="g" :value="String(g)">Kelas {{ g }}</option>
                </select>
                <p class="form-hint">Hanya untuk merapikan. Soal di rak kelas 7 tetap bisa dipilih untuk ujian kelas 9.</p>
              </div>
              <div class="form-group">
                <label for="bank-keterangan">Keterangan</label>
                <textarea id="bank-keterangan" v-model="form.keterangan" rows="2" placeholder="Catatan atau deskripsi bank soal (opsional)"></textarea>
              </div>
              <div class="modal-actions">
                <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
                <button type="button" class="btn-primary" @click="submitBank" :disabled="saving">
                  {{ saving ? 'Menyimpan...' : 'Simpan' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Modal Share -->
      <Teleport to="body">
        <div v-if="showShareModal" class="modal-overlay form-modal-overlay" @click.self="showShareModal = false">
          <div class="modal-content form-modal-content share-modal" @click.stop>
            <div class="form-modal-header">
              <div class="form-modal-title-wrap">
                <div class="form-modal-icon share-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                    <path d="M23 21v-2a4 4 0 0 0-3.99-3.98" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div>
                  <h3 class="form-modal-title">Bagikan bank soal</h3>
                  <p class="form-modal-subtitle">{{ shareBank ? shareBank.code + (shareBank.name ? ' – ' + shareBank.name : '') : '' }}</p>
                </div>
              </div>
              <button type="button" class="btn-close-modal" @click="showShareModal = false" aria-label="Tutup">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
            <div class="form-modal-body">
              <div v-if="shareCanManage" class="form-group">
                <label for="share-nik">Undang guru (NIK)</label>
                <div class="share-invite-row">
                  <input id="share-nik" v-model.trim="shareNik" type="text" placeholder="NIK guru (seluruh sistem)" maxlength="50" />
                  <button type="button" class="btn-primary btn-invite" @click="inviteShare" :disabled="!shareNik || shareInviteLoading">
                    {{ shareInviteLoading ? 'Mengundang...' : 'Undang' }}
                  </button>
                </div>
                <p class="form-hint">Cari guru berdasarkan NIK. Guru harus sudah memiliki akun login.</p>
              </div>
              <div class="form-group">
                <label>Orang dengan akses</label>
                <ul v-if="sharesList.length" class="shares-list">
                  <li v-for="s in sharesList" :key="s.user_id" class="share-item">
                    <span class="share-name">{{ s.name }}</span>
                    <span class="share-email">{{ s.email }}</span>
                    <span v-if="s.institution" class="share-inst">{{ s.institution.name }}</span>
                    <span class="share-role" :class="s.role">{{ s.role === 'owner' ? 'Pemilik' : 'Dibagikan' }}</span>
                    <button v-if="shareCanManage && s.role === 'shared'" type="button" class="btn-revoke" @click="revokeShare(s.user_id)" :disabled="shareRevokeLoading === s.user_id">
                      {{ shareRevokeLoading === s.user_id ? '...' : 'Cabut' }}
                    </button>
                  </li>
                </ul>
                <p v-else class="form-hint">Belum ada data. Undang guru dengan NIK di atas.</p>
              </div>
              <div class="modal-actions">
                <button type="button" class="btn-secondary" @click="showShareModal = false">Tutup</button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Modal Restore -->
      <Teleport to="body">
        <div v-if="showRestoreModal" class="modal-overlay form-modal-overlay" @click.self="showRestoreModal = false">
          <div class="modal-content form-modal-content bank-modal" @click.stop>
            <div class="form-modal-header">
              <div class="form-modal-title-wrap">
                <div class="form-modal-icon restore-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 3v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div>
                  <h3 class="form-modal-title">Restore bank soal</h3>
                  <p class="form-modal-subtitle">Pilih bank tujuan dan file backup (ZIP) untuk mengembalikan soal ke bank tersebut.</p>
                </div>
              </div>
              <button type="button" class="btn-close-modal" @click="showRestoreModal = false" aria-label="Tutup">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
            <div class="form-modal-body">
              <div class="form-group">
                <label for="restore-target-bank">Bank tujuan <span class="required">*</span></label>
                <select id="restore-target-bank" v-model="restoreTargetBankId" required>
                  <option value="">Pilih bank soal</option>
                  <option v-for="b in banks" :key="b.id" :value="b.id">{{ b.code }} – {{ b.name || '(tanpa nama)' }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="restore-file">File backup (ZIP) <span class="required">*</span></label>
                <input id="restore-file" ref="restoreFileRef" type="file" accept=".zip" @change="onRestoreFileChange" />
              </div>
              <div class="modal-actions">
                <button type="button" class="btn-secondary" @click="showRestoreModal = false">Batal</button>
                <button type="button" class="btn-primary" @click="submitRestore" :disabled="restoreLoading || !restoreTargetBankId || !restoreFile">
                  {{ restoreLoading ? 'Memulihkan...' : 'Restore' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </Layout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import { examApi } from '@/api/exam'
import { subjectApi } from '@/api/subject'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { getValidGradesForLevel } from '@/utils/institution'

const toast = useToast()
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const banks = ref([])
const subjects = ref([])
const loading = ref(false)
const filterSubjectId = ref('')
const filterGrade = ref('')
const filterSearch = ref('')
const openMenuId = ref(null)
const showForm = ref(false)
const formLoading = ref(false) // loading mapel & institusi saat modal dibuka
const editingId = ref(null)
const saving = ref(false)
const backupLoading = ref(null)
const showRestoreModal = ref(false)
const restoreTargetBankId = ref('')
const restoreFile = ref(null)
const restoreFileRef = ref(null)
const restoreLoading = ref(false)

const showShareModal = ref(false)
const shareBank = ref(null)
const sharesList = ref([])
const shareCanManage = ref(false)
const shareNik = ref('')
const shareInviteLoading = ref(false)
const shareRevokeLoading = ref(null)

const form = reactive({
  code: '',
  name: '',
  subject_id: '',
  grade: '',
  keterangan: ''
})

const gradeOptions = computed(() => {
  const level = auth.activeInstitution?.level || auth.user?.institution?.level || ''
  return getValidGradesForLevel(level) || []
})

async function fetchBanks() {
  loading.value = true
  openMenuId.value = null
  try {
    const params = { per_page: 100 }
    if (filterSubjectId.value) params.subject_id = filterSubjectId.value
    if (filterGrade.value) params.grade = filterGrade.value
    if (filterSearch.value.trim()) params.search = filterSearch.value.trim()
    const res = await examApi.listBanks(params)
    banks.value = res.data?.data ?? res.data ?? []
  } finally {
    loading.value = false
  }
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function typeSummaryTitle(b) {
  const t = b.questions_by_type || {}
  return [
    t.pg ? `PG: ${t.pg}` : null,
    t.pg_kompleks ? `PG kompleks: ${t.pg_kompleks}` : null,
    t.matching ? `Matching: ${t.matching}` : null,
    t.isian ? `Isian: ${t.isian}` : null,
    t.uraian ? `Uraian: ${t.uraian}` : null
  ].filter(Boolean).join(' · ') || 'Belum ada soal'
}

function onDocClick() {
  openMenuId.value = null
}

function normalizeSubjectList(raw) {
  if (!raw) return []
  if (Array.isArray(raw)) return raw
  if (Array.isArray(raw?.data)) return raw.data
  return []
}

async function loadSubjects() {
  subjects.value = []
  const sources = [
    () => examApi.listSubjects({ per_page: 100 }),
    () => subjectApi.getAll({ per_page: 'all', active_only: true })
  ]
  for (const fn of sources) {
    try {
      const res = await fn()
      const list = normalizeSubjectList(res?.data)
      if (list?.length) {
        subjects.value = list
        return
      }
    } catch (_) {}
  }
}

async function openForm(b = null) {
  editingId.value = b?.id ?? null
  form.code = b?.code ?? ''
  form.name = b?.name ?? ''
  form.subject_id = b?.subject_id ?? ''
  form.grade = b?.grade != null && b?.grade !== '' ? String(b.grade) : ''
  form.keterangan = b?.keterangan ?? ''
  showForm.value = true
  formLoading.value = true
  try {
    await loadSubjects()
  } finally {
    formLoading.value = false
  }
}

function goToManageSoal(b) {
  router.push({ path: `/ujian-online/bank-soal/${b.id}/soal` })
}

async function submitBank() {
  saving.value = true
  try {
    const payload = {
      code: form.code.trim(),
      name: form.name.trim() || null,
      subject_id: form.subject_id,
      grade: form.grade !== '' && form.grade != null ? Number(form.grade) : null,
      keterangan: form.keterangan.trim() || null
    }
    if (editingId.value) {
      await examApi.updateBank(editingId.value, payload)
      toast.success('Bank soal diperbarui.')
    } else {
      await examApi.createBank(payload)
      toast.success('Bank soal ditambahkan.')
    }
    showForm.value = false
    fetchBanks()
  } catch (e) {
    toast.error('Gagal menyimpan', e.response?.data?.message || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

async function confirmDelete(b) {
  if (!confirm('Hapus bank soal "' + (b.name || b.code) + '"? Soal di dalamnya juga akan terhapus.')) return
  try {
    await examApi.deleteBank(b.id)
    toast.success('Bank soal dihapus.')
    fetchBanks()
  } catch (e) {
    toast.error('Gagal menghapus', e.response?.data?.message || 'Gagal menghapus')
  }
}

async function doBackup(b) {
  backupLoading.value = b.id
  try {
    const res = await examApi.backupBank(b.id)
    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'bank-soal-backup-' + (b.code || b.id) + '-' + new Date().toISOString().slice(0, 10) + '.zip'
    a.click()
    URL.revokeObjectURL(url)
    toast.success('Backup berhasil diunduh.')
  } catch (e) {
    let msg = e.response?.data?.message ?? e.formattedMessage ?? 'Gagal backup'
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const j = JSON.parse(text)
        msg = j.message || text
      } catch (_) {}
    } else if (typeof e.response?.data?.message !== 'string' && e.response?.data?.message) {
      msg = JSON.stringify(e.response.data.message)
    }
    toast.error('Gagal backup', typeof msg === 'string' ? msg : String(msg))
  } finally {
    backupLoading.value = null
  }
}

function openRestoreModal(b) {
  restoreTargetBankId.value = String(b.id)
  restoreFile.value = null
  if (restoreFileRef.value) restoreFileRef.value.value = ''
  showRestoreModal.value = true
}

async function openShareModal(b) {
  shareBank.value = b
  shareNik.value = ''
  sharesList.value = []
  shareCanManage.value = false
  showShareModal.value = true
  try {
    const res = await examApi.listBankShares(b.id)
    sharesList.value = res.data?.data ?? []
    shareCanManage.value = res.data?.can_manage ?? false
  } catch (e) {
    toast.error('Gagal memuat data share', e.response?.data?.message || 'Gagal memuat')
  }
}

async function inviteShare() {
  if (!shareBank.value || !shareNik.value) return
  shareInviteLoading.value = true
  try {
    await examApi.inviteBankShare(shareBank.value.id, shareNik.value)
    toast.success('Akses berhasil dibagikan.')
    shareNik.value = ''
    const res = await examApi.listBankShares(shareBank.value.id)
    sharesList.value = res.data?.data ?? []
    shareCanManage.value = res.data?.can_manage ?? false
  } catch (e) {
    const msg = e.response?.data?.message || e.message || 'Gagal mengundang'
    toast.error('Gagal mengundang', msg)
  } finally {
    shareInviteLoading.value = false
  }
}

async function revokeShare(userId) {
  if (!shareBank.value) return
  shareRevokeLoading.value = userId
  try {
    await examApi.revokeBankShare(shareBank.value.id, userId)
    toast.success('Akses dicabut.')
    const res = await examApi.listBankShares(shareBank.value.id)
    sharesList.value = res.data?.data ?? []
    shareCanManage.value = res.data?.can_manage ?? false
  } catch (e) {
    toast.error('Gagal mencabut akses', e.response?.data?.message || 'Gagal')
  } finally {
    shareRevokeLoading.value = null
  }
}

function onRestoreFileChange(e) {
  const f = e.target?.files?.[0]
  restoreFile.value = f && f.name.toLowerCase().endsWith('.zip') ? f : null
}

async function submitRestore() {
  if (!restoreTargetBankId.value || !restoreFile.value) {
    toast.error('Pilihan wajib', 'Pilih bank tujuan dan unggah file ZIP terlebih dahulu.')
    return
  }
  restoreLoading.value = true
  try {
    const res = await examApi.restoreBank(Number(restoreTargetBankId.value), restoreFile.value)
    const data = res.data?.data
    toast.success(data ? `Restore berhasil: ${data.questions_created ?? 0} soal, ${data.options_created ?? 0} opsi.` : 'Restore berhasil.')
    showRestoreModal.value = false
    fetchBanks()
  } catch (e) {
    const msg = e.response?.data?.message || e.formattedMessage || 'Gagal restore'
    toast.error('Gagal restore', typeof msg === 'string' ? msg : JSON.stringify(msg))
  } finally {
    restoreLoading.value = false
  }
}

onMounted(() => {
  if (route.query.subject_id) {
    filterSubjectId.value = String(route.query.subject_id)
  }
  document.addEventListener('click', onDocClick)
  loadSubjects()
  fetchBanks()
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
})
</script>

<style scoped>
.banks-page {
  padding: 1rem;
}

/* Breadcrumb */
.breadcrumb {
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.breadcrumb-link {
  color: #059669;
  text-decoration: none;
}
.breadcrumb-link:hover {
  text-decoration: underline;
}
.breadcrumb-sep {
  color: #cbd5e1;
}
.breadcrumb-current {
  color: #475569;
  font-weight: 500;
}

/* Header: use global .main-content .page-header etc if available */
.page-header {
  margin-bottom: 1.5rem;
}
.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.25rem 0;
  letter-spacing: -0.02em;
}
.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}
.btn-add-new {
  background: #059669;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  white-space: nowrap;
}
.btn-add-new:hover {
  background: #047857;
}

/* Filters */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 1.5rem;
  padding: 1rem 1.25rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}
.filter-search {
  flex: 1;
  min-width: 180px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
}
.btn-filter {
  padding: 0.5rem 0.9rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  font-size: 0.875rem;
}
.type-chips { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.type-chip {
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  white-space: nowrap;
}
.type-chip--empty { color: #94a3b8; }
.btn-action-text {
  background: none;
  border: none;
  color: #059669;
  font-size: 0.8125rem;
  cursor: pointer;
  padding: 0.2rem 0.35rem;
  text-decoration: none;
}
.btn-action-text.btn-edit { color: #2563eb; }
.btn-action-text.btn-more { color: #64748b; }
.more-wrap { position: relative; display: inline-block; }
.more-menu {
  position: absolute;
  right: 0;
  top: 100%;
  z-index: 20;
  min-width: 150px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
  padding: 0.35rem;
  display: flex;
  flex-direction: column;
}
.more-menu button {
  text-align: left;
  background: none;
  border: none;
  padding: 0.45rem 0.65rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.8125rem;
  color: #334155;
}
.more-menu button:hover { background: #f1f5f9; }
.more-menu button.danger { color: #dc2626; }
.filter-select {
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #334155;
  background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
  appearance: none;
  cursor: pointer;
  min-width: 140px;
}
.filter-select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
}

/* Content card */
.content-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
}

/* Loading */
.loading-wrap {
  padding: 3rem 2rem;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.loading-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: #64748b;
  font-size: 0.875rem;
}
.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
}
.empty-icon {
  color: #cbd5e1;
  margin-bottom: 1rem;
}
.empty-state h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #334155;
  margin: 0 0 0.5rem 0;
}
.empty-state p {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0 0 1.5rem 0;
  max-width: 360px;
  margin-left: auto;
  margin-right: auto;
}
.empty-state .btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  border: none;
  background: #059669;
  color: #fff;
  cursor: pointer;
}
.empty-state .btn-primary:hover {
  background: #047857;
}

/* Table: muat di layar PC tanpa scroll horizontal */
.table-card {
  padding: 0;
  overflow: hidden;
  max-width: 100%;
}
.table-wrap {
  overflow-x: auto;
  max-width: 100%;
}
.data-table {
  width: 100%;
  min-width: 0;
  table-layout: fixed;
  border-collapse: collapse;
  font-size: 0.8125rem;
}
.data-table th,
.data-table td {
  padding: 0.5rem 0.65rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
.data-table thead tr {
  background: #f8fafc;
}
.data-table th {
  font-weight: 600;
  color: #475569;
}
.data-table tbody tr:hover {
  background: #f8fafc;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
/* Kolom ringkas agar muat di layar PC */
.data-table .col-kode {
  width: 72px;
}
.data-table .col-nama {
  width: 16%;
  min-width: 0;
}
.data-table .col-mapel {
  width: 12%;
  min-width: 0;
}
.data-table .col-grade {
  width: 88px;
}
.data-table .col-keterangan {
  width: 18%;
  min-width: 0;
}
.data-table .col-count {
  width: 72px;
}
.data-table .col-actions {
  width: 112px;
}
.cell-code {
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
}
.data-table td.col-nama {
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 0;
}
.cell-keterangan {
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 0;
}
.col-count {
  text-align: center;
}
.badge-count {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.8125rem;
}
.col-actions {
  white-space: nowrap;
  vertical-align: middle;
}
.row-actions {
  display: inline-flex;
  flex-wrap: nowrap;
  gap: 0.35rem;
  align-items: center;
  justify-content: flex-start;
}
.btn-action {
  font-size: 0.8125rem;
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  text-decoration: none;
  border: none;
  cursor: pointer;
  background: transparent;
  font-weight: 500;
}
.btn-action-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
  min-width: 32px;
  min-height: 32px;
}
.btn-action-icon svg {
  flex-shrink: 0;
}
.btn-manage {
  color: #2563eb;
  background: rgba(37, 99, 235, 0.08);
}
.btn-manage:hover {
  background: rgba(37, 99, 235, 0.15);
  color: #1d4ed8;
}
.btn-edit {
  color: #059669;
  background: rgba(5, 150, 105, 0.08);
}
.btn-edit:hover {
  background: rgba(5, 150, 105, 0.15);
  color: #047857;
}
.btn-delete {
  color: #dc2626;
  background: rgba(220, 38, 38, 0.08);
}
.btn-delete:hover {
  background: rgba(220, 38, 38, 0.15);
  color: #b91c1c;
}
.btn-backup {
  color: #7c3aed;
  background: rgba(124, 58, 237, 0.08);
}
.btn-backup:hover {
  background: rgba(124, 58, 237, 0.15);
  color: #6d28d9;
}
.btn-restore {
  color: #0891b2;
  background: rgba(8, 145, 178, 0.08);
}
.btn-restore:hover {
  background: rgba(8, 145, 178, 0.15);
  color: #0e7490;
}
.btn-share {
  color: #0d9488;
  background: rgba(13, 148, 136, 0.08);
}
.btn-share:hover {
  background: rgba(13, 148, 136, 0.15);
  color: #0f766e;
}
.btn-stimulus {
  color: #7c3aed;
  background: rgba(124, 58, 237, 0.08);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  border-radius: 6px;
}
.btn-stimulus:hover {
  background: rgba(124, 58, 237, 0.15);
  color: #6d28d9;
}
.btn-action-spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* Modal */
.form-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  overflow: auto;
}
.form-modal-content {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 440px;
  width: 100%;
  max-height: 90vh;
  overflow: auto;
}
.form-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.form-modal-title-wrap {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}
.form-modal-icon.bank-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #ecfdf5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.form-modal-icon.restore-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #ecfeff;
  color: #0891b2;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.form-modal-icon.share-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #ccfbf1;
  color: #0d9488;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.form-modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.25rem 0;
}
.form-modal-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}
.btn-close-modal {
  padding: 0.5rem;
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  border-radius: 8px;
}
.btn-close-modal:hover {
  background: #f1f5f9;
  color: #334155;
}
.form-modal-body {
  padding: 1.5rem;
}
.form-group {
  margin-bottom: 1.25rem;
}
.form-group:last-of-type {
  margin-bottom: 1.5rem;
}
.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #334155;
  margin-bottom: 0.35rem;
}
.form-group .required {
  color: #dc2626;
}
.form-hint {
  margin: 0.35rem 0 0;
  font-size: 0.8rem;
  color: #64748b;
  line-height: 1.4;
}
.grade-chip {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  color: #0369a1;
  background: #e0f2fe;
  border-radius: 999px;
  padding: 0.15rem 0.5rem;
}
.muted {
  color: #94a3b8;
  font-size: 0.85rem;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1e293b;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.2);
}
.form-group textarea {
  resize: vertical;
  min-height: 64px;
}
.form-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0.35rem 0 0 0;
}
.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}
.modal-actions .btn-primary {
  padding: 0.5rem 1.25rem;
  background: #059669;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
}
.modal-actions .btn-primary:hover:not(:disabled) {
  background: #047857;
}
.modal-actions .btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.modal-actions .btn-secondary {
  padding: 0.5rem 1.25rem;
  background: #f1f5f9;
  color: #475569;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
}
.modal-actions .btn-secondary:hover {
  background: #e2e8f0;
  color: #334155;
}
.share-invite-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.share-invite-row input {
  flex: 1;
  min-width: 0;
}
.btn-invite {
  flex-shrink: 0;
  padding: 0.5rem 1rem;
  white-space: nowrap;
}
.shares-list {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.share-item {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 1rem;
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.8125rem;
}
.share-item:last-child {
  border-bottom: none;
}
.share-name {
  font-weight: 600;
  color: #1e293b;
}
.share-email {
  color: #64748b;
}
.share-inst {
  color: #64748b;
  font-size: 0.75rem;
}
.share-role {
  margin-left: auto;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}
.share-role.owner {
  background: #fef3c7;
  color: #92400e;
}
.share-role.shared {
  background: #dbeafe;
  color: #1e40af;
}
.btn-revoke {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  color: #dc2626;
  background: transparent;
  border: 1px solid #fecaca;
  border-radius: 6px;
  cursor: pointer;
}
.btn-revoke:hover:not(:disabled) {
  background: #fef2f2;
}
.btn-revoke:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 1024px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions,
  .action-buttons-group {
    width: 100%;
    margin-left: 0;
  }

  .btn-add-new,
  .btn-add {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .filter-search,
  .filter-select {
    min-width: 0;
    width: 100%;
  }
  .filters,
  .filters-bar,
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .table-card {
    overflow: visible;
  }
  .data-table {
    table-layout: auto;
    min-width: 640px;
  }
  .cell-keterangan,
  .cell-nama {
    max-width: none;
    white-space: normal;
  }
}
</style>
