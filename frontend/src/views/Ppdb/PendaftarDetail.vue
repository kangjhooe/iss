<template>
  <Layout>
    <div class="ppdb-page">
      <header class="page-header">
        <div class="header-bg" aria-hidden="true"></div>
        <div class="header-content">
          <div class="header-left">
            <div>
              <h1 class="page-title">Detail Pendaftar</h1>
              <p class="page-subtitle">{{ applicant?.registration_number || 'Memuat...' }}</p>
            </div>
          </div>
          <div class="header-actions">
            <button v-if="applicant" type="button" class="btn-header-secondary" :disabled="slipDownloading" @click="downloadSlip">
              {{ slipDownloading ? 'Menyiapkan...' : 'Unduh bukti PDF' }}
            </button>
            <router-link to="/ppdb/pendaftar" class="btn-header-primary">← Kembali</router-link>
          </div>
        </div>
      </header>

      <main class="page-main">
        <div v-if="loading" class="loading-wrap content-card">
          <LoadingSkeleton type="table" :rows="6" :columns="3" />
        </div>
        <div v-else-if="!applicant" class="empty-state content-card">
          <h3>Data tidak ditemukan</h3>
          <p>Calon tidak ada atau tidak dapat diakses.</p>
          <router-link to="/ppdb/pendaftar" class="btn-primary">Kembali ke daftar</router-link>
        </div>
        <template v-else>
          <div class="content-card detail-hero">
            <div class="hero-main">
              <div class="header-title-row">
                <h2 class="detail-name">{{ applicant.name }}</h2>
                <span :class="['status-badge', 'status-' + applicant.status]">{{ statusApplicantLabels[applicant.status] || applicant.status }}</span>
              </div>
              <div class="meta-grid">
                <div class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">No. daftar</span>
                    <span class="meta-value">{{ applicant.registration_number || '—' }}</span>
                  </div>
                </div>
                <div class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v13H3z" stroke="currentColor" stroke-width="2"/><path d="M8 7V5a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="2"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">Jalur</span>
                    <span class="meta-value">{{ applicant.channel?.name || '—' }}</span>
                  </div>
                </div>
                <div class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">Periode</span>
                    <span class="meta-value">{{ applicant.period?.name || '—' }}</span>
                  </div>
                </div>
                <div v-if="applicant.rank" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M8 21h8M12 17v4M7 4h10l-1.5 8.5a5 5 0 0 1-7 0L7 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">Peringkat</span>
                    <span class="meta-value">{{ applicant.rank }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="action-row">
              <button v-if="canSetResult(applicant)" type="button" class="btn-primary btn-compact" @click="openResultModal">Set Hasil</button>
              <button v-if="canConfirmReReg(applicant)" type="button" class="btn-primary btn-compact" @click="doConfirmReReg">Konfirmasi Daftar Ulang</button>
              <button v-if="canConvertToStudent(applicant)" type="button" class="btn-primary btn-compact" @click="openConvertModal">Jadikan Siswa</button>
            </div>
          </div>

          <div class="detail-grid">
            <section class="content-card">
              <h3 class="section-title">Data diri</h3>
              <dl class="info-grid">
                <div><dt>NIK</dt><dd>{{ applicant.nik || '—' }}</dd></div>
                <div><dt>NISN</dt><dd>{{ applicant.nisn || '—' }}</dd></div>
                <div><dt>Jenis kelamin</dt><dd>{{ applicant.gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</dd></div>
                <div><dt>Tempat, tgl lahir</dt><dd>{{ applicant.birth_place || '—' }}, {{ formatDate(applicant.birth_date) }}</dd></div>
                <div class="full"><dt>Alamat</dt><dd>{{ formatFullAddress(applicant) || applicant.address || '—' }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ applicant.phone || '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ applicant.email || '—' }}</dd></div>
                <div><dt>Agama</dt><dd>{{ applicant.religion || '—' }}</dd></div>
              </dl>
            </section>

            <section class="content-card">
              <h3 class="section-title">Sekolah asal & orang tua</h3>
              <dl class="info-grid">
                <div><dt>NPSN</dt><dd>{{ applicant.previous_school_npsn || '—' }}</dd></div>
                <div class="full"><dt>Sekolah</dt><dd>{{ applicant.previous_school || '—' }}</dd></div>
                <div class="full"><dt>Alamat sekolah</dt><dd>{{ applicant.previous_school_address || '—' }}</dd></div>
                <div><dt>Ayah</dt><dd>{{ applicant.father_name || '—' }} {{ applicant.father_phone ? '(' + applicant.father_phone + ')' : '' }}</dd></div>
                <div><dt>Ibu</dt><dd>{{ applicant.mother_name || '—' }} {{ applicant.mother_phone ? '(' + applicant.mother_phone + ')' : '' }}</dd></div>
                <div class="full"><dt>Wali</dt><dd>{{ applicant.guardian_name || '—' }} {{ applicant.guardian_relation ? '— ' + applicant.guardian_relation : '' }}</dd></div>
              </dl>
              <p v-if="applicant.status === 'accepted_elsewhere'" class="elsewhere-note">
                Calon ini sudah terdaftar sebagai siswa di sekolah lain. Seleksi di sekolah ini ditutup.
                <template v-if="applicant.notes"> {{ applicant.notes }}</template>
              </p>
              <p v-if="applicant.student_id" class="converted-note">
                Sudah jadi siswa: NIS {{ applicant.student?.nis }} — {{ applicant.student?.name }}
              </p>
            </section>
          </div>

          <section class="content-card">
            <h3 class="section-title">Pembayaran</h3>
            <dl class="info-grid">
              <div>
                <dt>Status</dt>
                <dd>
                  <span :class="['pay-badge', 'pay-' + (applicant.payment_status || 'unpaid')]">
                    {{ paymentStatusLabels[applicant.payment_status] || 'Belum bayar' }}
                  </span>
                </dd>
              </div>
              <div><dt>Jenis</dt><dd>{{ applicant.payment_type === 're_registration' ? 'Daftar ulang' : (applicant.payment_type === 'registration' ? 'Pendaftaran' : '—') }}</dd></div>
              <div><dt>Nominal</dt><dd>{{ formatCurrency(applicant.payment_amount) }}</dd></div>
              <div><dt>Dibayar</dt><dd>{{ formatDate(applicant.paid_at) }}</dd></div>
            </dl>
            <div class="form-group verify-box">
              <label>Update pembayaran</label>
              <select v-model="paymentForm.payment_status" class="form-select">
                <option v-for="(l, k) in paymentStatusLabels" :key="k" :value="k">{{ l }}</option>
              </select>
              <select v-model="paymentForm.payment_type" class="form-select" style="margin-top: 0.5rem;">
                <option value="registration">Biaya pendaftaran</option>
                <option value="re_registration">Biaya daftar ulang</option>
              </select>
              <input v-model.number="paymentForm.payment_amount" type="number" min="0" step="1000" placeholder="Nominal (opsional)" style="margin-top: 0.5rem;" />
              <textarea v-model="paymentForm.payment_notes" rows="2" placeholder="Catatan pembayaran" style="margin-top: 0.5rem;"></textarea>
              <button type="button" class="btn-primary btn-compact" :disabled="paymentSubmitting" @click="submitPayment">
                {{ paymentSubmitting ? 'Menyimpan...' : 'Simpan Pembayaran' }}
              </button>
            </div>
          </section>

          <section class="content-card">
            <h3 class="section-title">Berkas & verifikasi</h3>
            <p>
              <strong>Status berkas:</strong>
              {{ applicant.documents_verified ? 'Terverifikasi' : 'Belum verifikasi' }}
              <template v-if="applicant.document_summary?.required_total">
                — {{ applicant.document_summary.required_uploaded }}/{{ applicant.document_summary.required_total }} wajib
              </template>
            </p>
            <div v-if="applicant.document_summary?.items?.length" class="checklist-table-wrap">
              <table class="checklist-table">
                <thead>
                  <tr>
                    <th>Jenis</th>
                    <th>Wajib</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in applicant.document_summary.items" :key="item.key">
                    <td>{{ item.label }}</td>
                    <td>{{ item.required ? 'Ya' : 'Tidak' }}</td>
                    <td>
                      <span :class="item.uploaded ? 'ok' : (item.required ? 'missing' : 'muted')">
                        {{ item.uploaded ? 'Sudah' : 'Belum' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="applicant.documents?.length" class="doc-list">
              <ul>
                <li v-for="d in applicant.documents" :key="d.id">
                  {{ d.name }} — {{ d.file_name }}
                  <TableAction kind="download" title="Unduh" @click="downloadDocument(d)" />
                </li>
              </ul>
            </div>
            <p v-else class="muted">Belum ada dokumen terunggah.</p>
            <div v-if="applicant.status !== 'accepted_elsewhere' && applicant.status !== 'converted'" class="form-group verify-box">
              <label>Verifikasi berkas</label>
              <label class="check-label"><input v-model="verificationForm.documents_verified" type="checkbox" /> Dokumen lengkap & valid</label>
              <textarea v-model="verificationForm.verification_notes" rows="2" placeholder="Catatan verifikasi"></textarea>
              <button type="button" class="btn-primary btn-compact" :disabled="verificationSubmitting" @click="submitVerification">
                {{ verificationSubmitting ? 'Menyimpan...' : 'Simpan Verifikasi' }}
              </button>
            </div>
          </section>
        </template>
      </main>

      <div v-if="showResultModal" class="modal-overlay" @click="showResultModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Set Hasil Seleksi</h3>
            <button type="button" class="btn-close" @click="showResultModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitResult">
            <div class="form-group">
              <label>Hasil *</label>
              <select v-model="resultForm.status" required class="form-select">
                <option value="passed">Lulus</option>
                <option value="reserve">Cadangan</option>
                <option value="failed">Tidak Lulus</option>
              </select>
            </div>
            <div class="form-group">
              <label>Rank (opsional)</label>
              <input v-model.number="resultForm.rank" type="number" min="1" />
            </div>
            <div class="form-group">
              <label>Catatan hasil</label>
              <textarea v-model="resultForm.result_notes" rows="2"></textarea>
            </div>
            <div v-if="resultFormError" class="error-message">{{ resultFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showResultModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="resultFormSubmitting">{{ resultFormSubmitting ? 'Menyimpan...' : 'Simpan' }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="showConvertModal" class="modal-overlay" @click="showConvertModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Jadikan Siswa</h3>
            <button type="button" class="btn-close" @click="showConvertModal = false">×</button>
          </div>
          <form class="modal-body" @submit.prevent="submitConvert">
            <div class="form-group">
              <label>Kelas (opsional)</label>
              <select v-model="convertForm.class_id" class="form-select">
                <option value="">— Tanpa kelas —</option>
                <option v-for="c in convertClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div v-if="convertFormError" class="error-message">{{ convertFormError }}</div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="showConvertModal = false">Batal</button>
              <button type="submit" class="btn-primary" :disabled="convertFormSubmitting">{{ convertFormSubmitting ? 'Memproses...' : 'Jadikan Siswa' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <AccountCredentialsModal
      :show="!!accountCredentials"
      :title="accountCredentials?.title"
      :name="accountCredentials?.name"
      :login-label="accountCredentials?.loginLabel || 'NIK'"
      :login-value="accountCredentials?.loginValue"
      :password="accountCredentials?.password"
      :hint="accountCredentials?.hint"
      @close="accountCredentials = null"
    />
  </Layout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Layout from '@/components/Layout.vue'
import TableAction from '@/components/TableAction.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import AccountCredentialsModal from '@/components/AccountCredentialsModal.vue'
import { ppdbApplicantApi, ppdbPeriodApi } from '@/api/ppdb'
import { classApi } from '@/api/class'
import { useToast } from '@/composables/useToast'
import { studentLoginCredentials } from '@/utils/accountCredentials'
import { formatFullAddress } from '@/utils/addressFields'
import {
  statusApplicantLabels,
  paymentStatusLabels,
  formatDate,
  formatCurrency,
  canSetResult,
  canConfirmReReg,
  canConvertToStudent,
} from './ppdbConstants'
import './ppdb.css'

const toast = useToast()
const accountCredentials = ref(null)
const route = useRoute()

const loading = ref(true)
const applicant = ref(null)
const verificationForm = ref({ documents_verified: false, verification_notes: '' })
const verificationSubmitting = ref(false)

const paymentForm = ref({
  payment_status: 'unpaid',
  payment_type: 'registration',
  payment_amount: '',
  payment_notes: '',
})
const paymentSubmitting = ref(false)

const showResultModal = ref(false)
const resultForm = ref({ status: 'passed', rank: null, result_notes: '' })
const resultFormError = ref('')
const resultFormSubmitting = ref(false)

const showConvertModal = ref(false)
const convertForm = ref({ class_id: '' })
const convertClasses = ref([])
const convertFormError = ref('')
const convertFormSubmitting = ref(false)
const slipDownloading = ref(false)

async function loadApplicant() {
  loading.value = true
  applicant.value = null
  try {
    const res = await ppdbApplicantApi.get(route.params.id)
    applicant.value = res.data.data || res.data
    verificationForm.value = {
      documents_verified: applicant.value.documents_verified ?? false,
      verification_notes: applicant.value.verification_notes || '',
    }
    paymentForm.value = {
      payment_status: applicant.value.payment_status || 'unpaid',
      payment_type: applicant.value.payment_type || (
        ['passed', 'reserve', 're_registration'].includes(applicant.value.status) ? 're_registration' : 'registration'
      ),
      payment_amount: applicant.value.payment_amount ?? '',
      payment_notes: applicant.value.payment_notes || '',
    }
  } catch (e) {
    toast.error('Gagal memuat detail calon', e.formattedMessage || 'Data tidak ditemukan.')
  } finally {
    loading.value = false
  }
}

async function downloadDocument(d) {
  if (!applicant.value?.id) return
  try {
    const res = await ppdbApplicantApi.downloadDocument(applicant.value.id, d.id)
    const blob = res.data
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = d.file_name || d.name || 'document'
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal mengunduh berkas', e.formattedMessage || 'Coba lagi.')
  }
}

async function downloadSlip() {
  if (!applicant.value?.id) return
  slipDownloading.value = true
  try {
    const res = await ppdbApplicantApi.downloadRegistrationSlip(applicant.value.id)
    const blob = res.data
    if (blob.type && blob.type.includes('json')) {
      toast.error('Gagal mengunduh bukti', 'Bukti pendaftaran tidak dapat dibuat.')
      return
    }
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `bukti-pendaftaran-${applicant.value.registration_number || applicant.value.id}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal mengunduh bukti', e.formattedMessage || 'Coba lagi.')
  } finally {
    slipDownloading.value = false
  }
}

async function submitVerification() {
  if (!applicant.value) return
  verificationSubmitting.value = true
  try {
    await ppdbApplicantApi.setVerification(applicant.value.id, verificationForm.value)
    toast.success('Verifikasi berhasil disimpan')
    await loadApplicant()
  } catch (e) {
    toast.error('Gagal menyimpan verifikasi', e.formattedMessage || 'Coba lagi.')
  } finally {
    verificationSubmitting.value = false
  }
}

async function submitPayment() {
  if (!applicant.value) return
  paymentSubmitting.value = true
  try {
    await ppdbApplicantApi.setPayment(applicant.value.id, {
      payment_status: paymentForm.value.payment_status,
      payment_type: paymentForm.value.payment_type,
      payment_amount: paymentForm.value.payment_amount === '' ? null : paymentForm.value.payment_amount,
      payment_notes: paymentForm.value.payment_notes || null,
    })
    toast.success('Pembayaran disimpan')
    await loadApplicant()
  } catch (e) {
    toast.error('Gagal menyimpan pembayaran', e.formattedMessage || 'Coba lagi.')
  } finally {
    paymentSubmitting.value = false
  }
}

function openResultModal() {
  const a = applicant.value
  resultForm.value = {
    status: a.status === 'passed' ? 'passed' : a.status === 'reserve' ? 'reserve' : a.status === 'failed' ? 'failed' : 'passed',
    rank: a.rank ?? null,
    result_notes: a.result_notes || '',
  }
  resultFormError.value = ''
  showResultModal.value = true
}

async function submitResult() {
  if (!applicant.value) return
  resultFormError.value = ''
  resultFormSubmitting.value = true
  try {
    await ppdbApplicantApi.setResult(applicant.value.id, {
      status: resultForm.value.status,
      rank: resultForm.value.rank || null,
      result_notes: resultForm.value.result_notes || null,
    })
    toast.success('Hasil seleksi disimpan')
    showResultModal.value = false
    await loadApplicant()
  } catch (e) {
    resultFormError.value = e.formattedMessage || 'Gagal menyimpan'
  } finally {
    resultFormSubmitting.value = false
  }
}

async function doConfirmReReg() {
  try {
    await ppdbApplicantApi.confirmReRegistration(applicant.value.id)
    toast.success('Daftar ulang dikonfirmasi')
    await loadApplicant()
  } catch (e) {
    toast.error('Gagal konfirmasi daftar ulang', e.formattedMessage || 'Coba lagi.')
  }
}

async function openConvertModal() {
  convertForm.value = { class_id: '' }
  convertFormError.value = ''
  convertClasses.value = []
  showConvertModal.value = true
  let academicYearId = applicant.value?.period?.academic_year_id
  if (!academicYearId && applicant.value?.ppdb_period_id) {
    try {
      const res = await ppdbPeriodApi.get(applicant.value.ppdb_period_id)
      academicYearId = (res.data.data || res.data)?.academic_year_id
    } catch { /* ignore */ }
  }
  if (academicYearId) {
    try {
      const res = await classApi.getAll({ academic_year_id: academicYearId, per_page: 200 })
      convertClasses.value = res.data.data || []
    } catch { /* ignore */ }
  }
}

async function submitConvert() {
  if (!applicant.value) return
  convertFormError.value = ''
  convertFormSubmitting.value = true
  try {
    const res = await ppdbApplicantApi.convertToStudent(applicant.value.id, {
      class_id: convertForm.value.class_id || undefined,
    })
    showConvertModal.value = false
    const creds = studentLoginCredentials(applicant.value, res.data?.login_hint)
    await loadApplicant()
    if (creds) {
      creds.title = 'Akun login siswa dibuat'
      accountCredentials.value = creds
    } else {
      toast.success(res.data?.message || 'Calon berhasil dijadikan siswa')
    }
  } catch (e) {
    convertFormError.value = e.formattedMessage || 'Gagal menjadikan siswa'
  } finally {
    convertFormSubmitting.value = false
  }
}

watch(() => route.params.id, loadApplicant)
onMounted(loadApplicant)
</script>

<style scoped>
.detail-hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
}
.hero-main { flex: 1 1 280px; min-width: 0; }
.header-title-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.detail-name { margin: 0; font-size: 1.35rem; color: #1e293b; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 8px 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: #ecfdf5; color: #059669;
}
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.meta-value { font-size: 13.5px; font-weight: 600; color: #0f172a; line-height: 1.35; word-break: break-word; }
@media (max-width: 768px) { .meta-grid { grid-template-columns: 1fr 1fr; } }
.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 900px) {
  .detail-grid { grid-template-columns: 1fr 1fr; }
}
.section-title { margin: 0 0 1rem; font-size: 1.05rem; color: #1e293b; }
.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem 1rem;
  margin: 0;
}
.info-grid .full { grid-column: 1 / -1; }
.info-grid dt { font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; margin: 0 0 0.15rem; }
.info-grid dd { margin: 0; color: #334155; font-size: 0.95rem; }
.converted-note { margin: 1rem 0 0; padding: 0.75rem; background: #ecfdf5; border-radius: 10px; color: #047857; font-weight: 500; }
.elsewhere-note { margin: 1rem 0 0; padding: 0.75rem; background: #f5f3ff; border-radius: 10px; color: #5b21b6; font-weight: 500; }
.verify-box { margin-top: 1rem; max-width: 480px; }
.check-label { display: flex !important; align-items: center; gap: 0.5rem; font-weight: 500 !important; margin-bottom: 0.5rem !important; }
.muted { color: #94a3b8; }
.ok { color: #047857; font-weight: 600; }
.missing { color: #b91c1c; font-weight: 600; }
.checklist-table-wrap { margin: 0.75rem 0; overflow-x: auto; }
.checklist-table { width: 100%; max-width: 520px; border-collapse: collapse; font-size: 0.9rem; }
.checklist-table th, .checklist-table td { border: 1px solid #e2e8f0; padding: 0.4rem 0.6rem; text-align: left; }
.checklist-table th { background: #f8fafc; color: #475569; font-size: 0.75rem; text-transform: uppercase; }
.btn-compact { margin-top: 0.5rem; }
.detail-hero .btn-compact { margin-top: 0; }
.pay-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 600;
}
.pay-unpaid { background: #fee2e2; color: #b91c1c; }
.pay-pending { background: #fef3c7; color: #92400e; }
.pay-paid { background: #d1fae5; color: #065f46; }
.pay-waived { background: #e2e8f0; color: #475569; }
a.btn-header-primary { text-decoration: none; }
a.btn-primary { text-decoration: none; display: inline-flex; }
</style>
