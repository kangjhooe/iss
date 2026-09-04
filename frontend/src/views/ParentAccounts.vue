<template>
  <div class="parent-accounts-page">
    <header class="page-header">
      <div class="header-text">
        <h1 class="page-title">Akun Orang Tua</h1>
        <p class="page-subtitle">Buat akun portal orang tua dan tautkan ke siswa</p>
      </div>
      <button type="button" class="btn-primary" @click="openCreate">+ Buat Akun</button>
    </header>

    <div class="toolbar">
      <input
        v-model="search"
        type="search"
        class="search-input"
        placeholder="Cari nama atau email/HP..."
        @input="debouncedLoad"
      />
    </div>

    <div v-if="loading" class="loading-wrap">
      <LoadingSkeleton type="table" :rows="6" :columns="5" :cell-widths="['1fr', '180px', '1fr', '80px', '140px']" />
    </div>

    <div v-else-if="!parents.length" class="empty-state">
      <p>Belum ada akun orang tua untuk sekolah ini.</p>
      <button type="button" class="btn-primary" @click="openCreate">Buat Akun Pertama</button>
    </div>

    <div v-else class="list">
      <article v-for="p in parents" :key="p.id" class="card">
        <div class="card-main">
          <div>
            <h3>{{ p.name }}</h3>
            <p class="meta">{{ p.email }}</p>
          </div>
          <span :class="['badge', p.is_active === false ? 'badge-off' : 'badge-on']">
            {{ p.is_active === false ? 'Nonaktif' : 'Aktif' }}
          </span>
        </div>

        <div class="children">
          <p v-if="!p.children?.length" class="hint">Belum ada siswa tertaut</p>
          <ul v-else>
            <li v-for="c in p.children" :key="c.student_id">
              <span>
                <strong>{{ c.student_name }}</strong>
                <em>{{ c.class_name || '—' }} · {{ c.relation || 'wali' }}</em>
              </span>
              <button type="button" class="btn-link danger" @click="unlink(p, c)">Lepas</button>
            </li>
          </ul>
        </div>

        <div class="card-actions">
          <button type="button" class="btn-ghost" @click="openLink(p)">Tautkan Siswa</button>
          <button type="button" class="btn-ghost" @click="openEdit(p)">Edit</button>
          <button type="button" class="btn-ghost" @click="resetPassword(p)">Reset Password</button>
        </div>
      </article>
    </div>

    <div v-if="pagination.last_page > 1" class="pager">
      <button type="button" class="btn-ghost" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Sebelumnya</button>
      <span>Hal {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button type="button" class="btn-ghost" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Berikutnya</button>
    </div>

    <!-- Create / Edit modal -->
    <div v-if="formOpen" class="modal-overlay" @click.self="closeForm">
      <div class="modal" role="dialog" aria-modal="true">
        <h2>{{ formMode === 'create' ? 'Buat Akun Orang Tua' : 'Edit Akun' }}</h2>
        <form @submit.prevent="submitForm">
          <label>
            Nama
            <input v-model="form.name" required maxlength="255" />
          </label>
          <label>
            Email / No. HP (untuk login)
            <input v-model="form.email" required maxlength="255" />
          </label>
          <p v-if="formMode === 'create'" class="field-hint">Jika akun sudah ada (mis. anak di sekolah lain), siswa akan ditautkan ke akun itu.</p>
          <label v-if="formMode === 'create'">
            Password sementara (opsional)
            <input v-model="form.password" type="text" minlength="6" placeholder="Kosongkan = generate otomatis" />
          </label>
          <label v-if="formMode === 'edit'" class="checkbox-row">
            <input v-model="form.is_active" type="checkbox" />
            Akun aktif
          </label>

          <template v-if="formMode === 'create'">
            <label>
              Relasi
              <select v-model="form.relation">
                <option value="ayah">Ayah</option>
                <option value="ibu">Ibu</option>
                <option value="wali">Wali</option>
                <option value="other">Lainnya</option>
              </select>
            </label>
            <div class="link-box">
              <label>Tautkan siswa (opsional)</label>
              <input v-model="candidateSearch" type="search" placeholder="Cari nama / NIS / HP wali..." @input="debouncedCandidates" />
              <div v-if="candidates.length" class="candidate-list">
                <label v-for="s in candidates" :key="s.id" class="candidate">
                  <input v-model="form.student_ids" type="checkbox" :value="s.id" />
                  <span>{{ s.name }} <small>{{ s.class_name || '—' }} · {{ s.guardian_phone || 'tanpa HP' }}</small></span>
                </label>
              </div>
            </div>
          </template>

          <div class="modal-actions">
            <button type="button" class="btn-ghost" @click="closeForm">Batal</button>
            <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Link students modal -->
    <div v-if="linkOpen" class="modal-overlay" @click.self="closeLink">
      <div class="modal" role="dialog" aria-modal="true">
        <h2>Tautkan Siswa — {{ linkParent?.name }}</h2>
        <label>
          Relasi
          <select v-model="linkRelation">
            <option value="ayah">Ayah</option>
            <option value="ibu">Ibu</option>
            <option value="wali">Wali</option>
            <option value="other">Lainnya</option>
          </select>
        </label>
        <input v-model="candidateSearch" type="search" placeholder="Cari nama / NIS / HP wali..." @input="debouncedCandidates" />
        <div v-if="candidates.length" class="candidate-list">
          <label v-for="s in candidates" :key="s.id" class="candidate" :class="{ muted: s.already_linked }">
            <input v-model="linkStudentIds" type="checkbox" :value="s.id" :disabled="s.already_linked" />
            <span>{{ s.name }} <small>{{ s.class_name || '—' }}{{ s.already_linked ? ' · sudah tertaut' : '' }}</small></span>
          </label>
        </div>
        <p v-else class="hint">Ketik untuk mencari siswa.</p>
        <div class="modal-actions">
          <button type="button" class="btn-ghost" @click="closeLink">Batal</button>
          <button type="button" class="btn-primary" :disabled="saving || !linkStudentIds.length" @click="submitLink">
            {{ saving ? 'Menyimpan…' : 'Tautkan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Password reveal -->
    <div v-if="revealedPassword" class="modal-overlay" @click.self="clearReveal">
      <div class="modal" role="dialog" aria-modal="true">
        <h2>Sandi Sementara</h2>
        <p class="hint">Salin dan berikan kepada orang tua. Sandi hanya ditampilkan sekali.</p>
        <p v-if="revealedLogin" class="login-box"><span>Login</span>{{ revealedLogin }}</p>
        <p class="password-box">{{ revealedPassword }}</p>
        <div class="modal-actions">
          <button type="button" class="btn-primary" @click="copyPassword">Salin sandi</button>
          <button type="button" class="btn-ghost" @click="clearReveal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { parentAccountApi } from '@/api/parentAccount'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const parents = ref([])
const search = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const formOpen = ref(false)
const formMode = ref('create')
const form = ref({ id: null, name: '', email: '', password: '', relation: 'wali', student_ids: [], is_active: true })

const linkOpen = ref(false)
const linkParent = ref(null)
const linkRelation = ref('wali')
const linkStudentIds = ref([])

const candidates = ref([])
const candidateSearch = ref('')
const revealedPassword = ref(null)
const revealedLogin = ref('')

let searchTimer = null
let candidateTimer = null

function firstValidationError(err) {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const first = Object.values(errors).flat()[0]
    if (first) return String(first)
  }
  return err.response?.data?.message || null
}

async function load(page = 1) {
  loading.value = true
  try {
    const res = await parentAccountApi.list({
      search: search.value || undefined,
      page,
      per_page: 20,
    })
    const body = res.data
    parents.value = Array.isArray(body?.data) ? body.data : []
    pagination.value = {
      current_page: body?.current_page || 1,
      last_page: body?.last_page || 1,
      total: body?.total || 0,
    }
  } catch (err) {
    toast.error('Gagal', firstValidationError(err) || 'Gagal memuat akun orang tua')
    parents.value = []
  } finally {
    loading.value = false
  }
}

function debouncedLoad() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), 350)
}

function goPage(page) {
  load(page)
}

async function loadCandidates(excludeParentId = null) {
  try {
    const res = await parentAccountApi.candidates({
      search: candidateSearch.value || undefined,
      exclude_parent_id: excludeParentId || undefined,
    })
    candidates.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch {
    candidates.value = []
  }
}

function debouncedCandidates() {
  clearTimeout(candidateTimer)
  const exclude = formMode.value === 'create' && formOpen.value
    ? null
    : linkParent.value?.id
  candidateTimer = setTimeout(() => loadCandidates(exclude), 300)
}

function openCreate() {
  formMode.value = 'create'
  form.value = { id: null, name: '', email: '', password: '', relation: 'wali', student_ids: [], is_active: true }
  candidateSearch.value = ''
  candidates.value = []
  formOpen.value = true
  loadCandidates()
}

function openEdit(p) {
  formMode.value = 'edit'
  form.value = {
    id: p.id,
    name: p.name,
    email: p.email,
    password: '',
    relation: 'wali',
    student_ids: [],
    is_active: p.is_active !== false,
  }
  formOpen.value = true
}

function closeForm() {
  formOpen.value = false
}

async function submitForm() {
  saving.value = true
  try {
    if (formMode.value === 'create') {
      const res = await parentAccountApi.create({
        name: form.value.name.trim(),
        email: form.value.email.trim(),
        password: form.value.password || undefined,
        relation: form.value.relation,
        student_ids: form.value.student_ids,
      })
      toast.success('Berhasil', res.data?.message || 'Akun dibuat')
      if (res.data?.temporary_password) {
        revealedPassword.value = res.data.temporary_password
        revealedLogin.value = res.data?.login_hint?.login || form.value.email.trim()
      }
    } else {
      await parentAccountApi.update(form.value.id, {
        name: form.value.name.trim(),
        email: form.value.email.trim(),
        is_active: form.value.is_active,
      })
      toast.success('Berhasil', 'Akun diperbarui')
    }
    closeForm()
    await load(pagination.value.current_page)
  } catch (err) {
    toast.error('Gagal', firstValidationError(err) || 'Gagal menyimpan')
  } finally {
    saving.value = false
  }
}

function openLink(p) {
  linkParent.value = p
  linkRelation.value = 'wali'
  linkStudentIds.value = []
  candidateSearch.value = ''
  candidates.value = []
  linkOpen.value = true
  loadCandidates(p.id)
}

function closeLink() {
  linkOpen.value = false
  linkParent.value = null
}

async function submitLink() {
  if (!linkParent.value || !linkStudentIds.value.length) return
  saving.value = true
  try {
    await parentAccountApi.link(linkParent.value.id, {
      student_ids: linkStudentIds.value,
      relation: linkRelation.value,
    })
    toast.success('Berhasil', 'Siswa ditautkan')
    closeLink()
    await load(pagination.value.current_page)
  } catch (err) {
    toast.error('Gagal', firstValidationError(err) || 'Gagal menautkan')
  } finally {
    saving.value = false
  }
}

async function unlink(p, c) {
  if (!confirm(`Lepas tautan ${c.student_name} dari ${p.name}?`)) return
  try {
    await parentAccountApi.unlink(p.id, c.student_id)
    toast.success('Berhasil', 'Tautan dilepas')
    await load(pagination.value.current_page)
  } catch (err) {
    toast.error('Gagal', firstValidationError(err) || 'Gagal melepas tautan')
  }
}

async function resetPassword(p) {
  if (!confirm(`Reset password untuk ${p.name}?`)) return
  try {
    const res = await parentAccountApi.resetPassword(p.id)
    toast.success('Berhasil', res.data?.message || 'Password direset')
    if (res.data?.temporary_password) {
      revealedPassword.value = res.data.temporary_password
      revealedLogin.value = res.data?.login_hint?.login || p.email
    }
  } catch (err) {
    toast.error('Gagal', firstValidationError(err) || 'Gagal reset password')
  }
}

function clearReveal() {
  revealedPassword.value = null
  revealedLogin.value = ''
}

async function copyPassword() {
  try {
    await navigator.clipboard.writeText(revealedPassword.value || '')
    toast.success('Disalin', 'Sandi disalin ke clipboard')
  } catch {
    toast.error('Gagal', 'Tidak bisa menyalin otomatis')
  }
}

function onKeydown(e) {
  if (e.key !== 'Escape') return
  if (revealedPassword.value) clearReveal()
  else if (linkOpen.value) closeLink()
  else if (formOpen.value) closeForm()
}

onMounted(() => {
  load(1)
  window.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  clearTimeout(searchTimer)
  clearTimeout(candidateTimer)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<style scoped>
.parent-accounts-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 8px 4px 32px;
}
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.page-title {
  margin: 0 0 4px;
  font-size: 1.4rem;
  color: #0f172a;
}
.page-subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.9rem;
}
.toolbar {
  margin-bottom: 14px;
}
.search-input,
.modal input,
.modal select {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 0.95rem;
  background: #fff;
}
.list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
}
.card-main {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}
.card-main h3 {
  margin: 0 0 2px;
  font-size: 1.05rem;
}
.meta {
  margin: 0;
  color: #64748b;
  font-size: 0.88rem;
}
.badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 999px;
}
.badge-on {
  background: #d1fae5;
  color: #047857;
}
.badge-off {
  background: #fee2e2;
  color: #b91c1c;
}
.children {
  margin-top: 12px;
}
.children ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.children li {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 10px;
}
.children em {
  display: block;
  font-style: normal;
  color: #64748b;
  font-size: 0.8rem;
}
.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}
.btn-primary,
.btn-ghost,
.btn-link {
  border: none;
  cursor: pointer;
  font-weight: 600;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 0.88rem;
}
.btn-primary {
  background: #059669;
  color: #fff;
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-ghost {
  background: #f1f5f9;
  color: #0f172a;
}
.btn-link {
  background: transparent;
  color: #059669;
  padding: 0;
}
.btn-link.danger {
  color: #dc2626;
}
.empty-state,
.loading-wrap {
  padding: 32px 12px;
  text-align: center;
  color: #64748b;
}
.pager {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  color: #64748b;
  font-size: 0.9rem;
}
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 80;
}
.modal {
  width: min(480px, 100%);
  max-height: min(90vh, 720px);
  overflow: auto;
  background: #fff;
  border-radius: 16px;
  padding: 18px 18px 14px;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
}
.modal h2 {
  margin: 0 0 14px;
  font-size: 1.15rem;
}
.modal form,
.modal label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
  font-size: 0.88rem;
  color: #334155;
  font-weight: 600;
}
.checkbox-row {
  flex-direction: row !important;
  align-items: center;
  gap: 8px !important;
}
.link-box {
  margin-bottom: 12px;
}
.candidate-list {
  margin-top: 8px;
  max-height: 220px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.candidate {
  display: flex !important;
  flex-direction: row !important;
  align-items: flex-start;
  gap: 8px !important;
  padding: 8px 10px;
  margin: 0 !important;
  border-bottom: 1px solid #f1f5f9;
  font-weight: 500 !important;
}
.candidate.muted {
  opacity: 0.55;
}
.candidate small {
  display: block;
  color: #64748b;
  font-weight: 400;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}
.hint {
  color: #64748b;
  font-size: 0.88rem;
  margin: 8px 0;
}
.field-hint {
  margin: -6px 0 12px;
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 400;
}
.login-box {
  margin: 0 0 8px;
  padding: 10px 12px;
  background: #f8fafc;
  border-radius: 10px;
  font-size: 0.95rem;
  word-break: break-all;
}
.login-box span {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 2px;
}
.password-box {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 1.15rem;
  letter-spacing: 0.04em;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  padding: 14px;
  text-align: center;
  word-break: break-all;
}
@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
  }
  .card-actions {
    flex-direction: column;
  }
}
</style>
