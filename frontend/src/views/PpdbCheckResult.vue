<template>
  <div class="ppdb-check-page">
    <div class="page-bg" aria-hidden="true">
      <div class="bg-pattern"></div>
      <div class="bg-gradient"></div>
    </div>

    <header class="public-header">
      <router-link to="/" class="back-link">
        <svg class="back-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Beranda
      </router-link>
      <div class="header-content">
        <div class="header-badge">Cek Hasil PPDB</div>
        <h1>Cek Hasil Seleksi</h1>
        <p class="header-desc">Masukkan nomor pendaftaran atau NISN untuk melihat status dan hasil seleksi PPDB.</p>
      </div>
    </header>

    <main class="public-main">
      <form class="check-form card" @submit.prevent="check">
        <div class="form-group">
          <label class="form-label">Nomor Pendaftaran atau NISN</label>
          <p class="form-hint">Contoh nomor pendaftaran: 10648387-1-00001 · NISN: 10 digit</p>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              class="form-input"
              placeholder="Ketik nomor pendaftaran atau NISN..."
              autocomplete="off"
              @input="error = ''"
            />
          </div>
        </div>
        <div v-if="error" class="error-banner">
          <svg class="error-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
          </svg>
          {{ error }}
        </div>
        <button type="submit" class="btn-submit" :disabled="loading || !searchQuery.trim()">
          <span v-if="loading" class="btn-spinner"></span>
          <template v-else>
            <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            Cek Hasil
          </template>
        </button>
      </form>

      <div v-if="result" class="result-card card">
        <div class="result-header">
          <h2 class="result-title">Hasil Pencarian</h2>
          <span :class="['status-badge', 'status-' + result.status]">{{ statusLabel(result.status) }}</span>
        </div>
        <div class="result-hero">
          <div class="result-avatar">{{ (result.name || '?').charAt(0).toUpperCase() }}</div>
          <div class="result-hero-text">
            <span class="result-name">{{ result.name }}</span>
            <span class="result-reg">{{ result.registration_number }}</span>
          </div>
        </div>
        <dl class="result-list">
          <div class="result-row">
            <dt>Periode</dt>
            <dd>{{ result.period?.name || '-' }}</dd>
          </div>
          <div class="result-row">
            <dt>Jalur</dt>
            <dd>{{ result.channel?.name || '-' }}</dd>
          </div>
          <template v-if="result.rank != null">
            <div class="result-row">
              <dt>Peringkat</dt>
              <dd>{{ result.rank }}</dd>
            </div>
          </template>
          <template v-if="result.announcement_at">
            <div class="result-row">
              <dt>Tanggal Pengumuman</dt>
              <dd>{{ formatDateId(result.announcement_at) }}</dd>
            </div>
          </template>
          <template v-if="result.re_registration_confirmed_at">
            <div class="result-row">
              <dt>Daftar Ulang</dt>
              <dd class="text-success">Sudah dikonfirmasi</dd>
            </div>
          </template>
          <template v-if="result.student">
            <div class="result-row result-row-highlight">
              <dt>NIS (Siswa)</dt>
              <dd><strong>{{ result.student.nis }}</strong> — {{ result.student.name }}</dd>
            </div>
          </template>
          <template v-if="result.result_notes">
            <div class="result-row">
              <dt>Keterangan</dt>
              <dd>{{ result.result_notes }}</dd>
            </div>
          </template>
        </dl>
        <div v-if="confirmReRegSuccess" class="success-banner result-success-banner">
          <svg class="success-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
          Konfirmasi daftar ulang berhasil. Silakan menunggu informasi lanjutan dari sekolah.
        </div>
        <div class="result-actions">
          <button
            v-if="canConfirmReReg"
            type="button"
            class="btn-confirm-rereg"
            :disabled="confirmReRegLoading"
            @click="doConfirmReReg"
          >
            <span v-if="confirmReRegLoading" class="btn-spinner"></span>
            <template v-else>✓ Konfirmasi Daftar Ulang</template>
          </button>
          <button type="button" class="btn-print" @click="printFormulir">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
              <path d="M6 14h12v8H6z"/>
            </svg>
            Cetak Formulir Pendaftaran
          </button>
        </div>
        <!-- Area cetak: hidden di layar, tampil saat print -->
        <div ref="printAreaRef" class="formulir-print-wrap">
          <div class="formulir-print">
            <header class="print-kop">
              <h1 class="print-kop-name">{{ result.institution?.name || 'Sekolah' }}</h1>
              <p v-if="result.institution?.address" class="print-kop-address">{{ result.institution.address }}</p>
              <p v-if="result.institution?.npsn" class="print-kop-npsn">NPSN: {{ result.institution.npsn }}</p>
              <hr class="print-kop-line" />
            </header>
            <h2 class="print-title">Formulir Pendaftaran PPDB</h2>
            <p class="print-reg-number"><strong>Nomor Pendaftaran:</strong> {{ result.registration_number }}</p>
            <p class="print-meta">{{ result.period?.name || '-' }} — {{ result.channel?.name || '-' }}</p>
            <table class="print-table">
              <tbody>
                <tr><td class="print-label">Nama Lengkap</td><td>{{ result.name || '-' }}</td></tr>
                <tr><td class="print-label">NIK</td><td>{{ result.nik || '-' }}</td></tr>
                <tr><td class="print-label">NISN</td><td>{{ result.nisn || '-' }}</td></tr>
                <tr><td class="print-label">Jenis Kelamin</td><td>{{ result.gender === 'L' ? 'Laki-laki' : result.gender === 'P' ? 'Perempuan' : '-' }}</td></tr>
                <tr><td class="print-label">Tempat, Tanggal Lahir</td><td>{{ result.birth_place || '-' }}, {{ result.birth_date || '-' }}</td></tr>
                <tr><td class="print-label">Alamat</td><td>{{ result.address || '-' }}</td></tr>
                <tr><td class="print-label">Telepon / Email</td><td>{{ result.phone || '-' }} / {{ result.email || '-' }}</td></tr>
                <tr><td class="print-label">Agama</td><td>{{ result.religion || '-' }}</td></tr>
                <tr><td class="print-label">Sekolah Asal (NPSN)</td><td>{{ result.previous_school_npsn || '-' }}</td></tr>
                <tr><td class="print-label">Nama Sekolah Asal</td><td>{{ result.previous_school || '-' }}</td></tr>
                <tr><td class="print-label">Alamat Sekolah Asal</td><td>{{ result.previous_school_address || '-' }}</td></tr>
                <tr><td class="print-label">Ayah (Nama, NIK, Telepon)</td><td>{{ result.father_name || '-' }}, {{ result.father_nik || '-' }}, {{ result.father_phone || '-' }}</td></tr>
                <tr><td class="print-label">Ibu (Nama, NIK, Telepon)</td><td>{{ result.mother_name || '-' }}, {{ result.mother_nik || '-' }}, {{ result.mother_phone || '-' }}</td></tr>
                <tr><td class="print-label">Wali</td><td>{{ result.guardian_name || '-' }} {{ result.guardian_phone || '-' }} {{ result.guardian_relation || '-' }}</td></tr>
                <tr v-if="result.notes"><td class="print-label">Catatan</td><td>{{ result.notes }}</td></tr>
                <tr v-if="result.submitted_at"><td class="print-label">Tanggal Pendaftaran</td><td>{{ formatDateId(result.submitted_at) }}</td></tr>
                <tr v-if="result.status"><td class="print-label">Status</td><td>{{ statusLabel(result.status) }}</td></tr>
              </tbody>
            </table>
            <div class="print-signatures">
              <div class="print-sig-block">
                <p class="print-sig-label">Panitia PPDB</p>
                <div class="print-sig-line"></div>
                <p class="print-sig-name">(_______________________)</p>
              </div>
              <div class="print-sig-block">
                <p class="print-sig-label">Pendaftar / Orang Tua</p>
                <div class="print-sig-line"></div>
                <p class="print-sig-name">(_______________________)</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ppdbPublicApi } from '@/api/ppdbPublic'

const searchQuery = ref('')
const loading = ref(false)
const error = ref('')
const result = ref(null)
const printAreaRef = ref(null)
const confirmReRegLoading = ref(false)
const confirmReRegSuccess = ref(false)

const statusLabels = {
  draft: 'Draft',
  submitted: 'Terkirim',
  verification: 'Verifikasi',
  verified: 'Terverifikasi',
  rejected: 'Ditolak',
  passed: 'Lulus',
  reserve: 'Cadangan',
  failed: 'Tidak Lulus',
  re_registration: 'Daftar Ulang',
  converted: 'Jadi Siswa',
  cancelled: 'Dibatalkan',
}

function statusLabel(s) {
  return statusLabels[s] || s
}

const canConfirmReReg = computed(() => {
  const r = result.value
  return r && (r.status === 'passed' || r.status === 'reserve') && !r.re_registration_confirmed_at
})

function formatDateId(val) {
  if (!val) return '-'
  const d = new Date(val)
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  const day = d.getDate()
  const month = months[d.getMonth()]
  const year = d.getFullYear()
  const hour = d.getHours()
  const min = String(d.getMinutes()).padStart(2, '0')
  if (val.length <= 10) return `${day} ${month} ${year}`
  return `${day} ${month} ${year}, ${hour}:${min}`
}

async function doConfirmReReg() {
  if (!result.value?.registration_number) return
  confirmReRegLoading.value = true
  confirmReRegSuccess.value = false
  error.value = ''
  try {
    const params = { registration_number: result.value.registration_number }
    if (result.value.institution?.npsn) params.npsn = result.value.institution.npsn
    await ppdbPublicApi.confirmReRegistration(params)
    result.value = { ...result.value, re_registration_confirmed_at: true, status: 're_registration' }
    confirmReRegSuccess.value = true
    setTimeout(() => { confirmReRegSuccess.value = false }, 4000)
  } catch (e) {
    error.value = e.response?.data?.message || e.formattedMessage || 'Gagal konfirmasi daftar ulang.'
  } finally {
    confirmReRegLoading.value = false
  }
}

async function check() {
  const query = searchQuery.value.trim()
  if (!query) {
    error.value = 'Masukkan nomor pendaftaran atau NISN.'
    return
  }
  error.value = ''
  result.value = null
  loading.value = true
  try {
    const params = {}
    // Deteksi apakah input adalah nomor pendaftaran (mengandung tanda hubung) atau NISN (10 digit angka)
    if (query.includes('-')) {
      // Format nomor pendaftaran: NPSN-period_id-seq (contoh: 10648387-1-00001)
      params.registration_number = query
    } else if (/^\d{10}$/.test(query)) {
      // NISN adalah 10 digit angka
      params.nisn = query
    } else {
      // Coba sebagai nomor pendaftaran dulu
      params.registration_number = query
    }
    const res = await ppdbPublicApi.checkResult(params)
    result.value = res.data?.data || res.data
  } catch (e) {
    // Jika pencarian dengan registration_number gagal dan input adalah 10 digit, coba sebagai NISN
    if (e.response?.status === 404 && !query.includes('-') && /^\d{10}$/.test(query)) {
      try {
        const params = { nisn: query }
        const res = await ppdbPublicApi.checkResult(params)
        result.value = res.data?.data || res.data
        error.value = ''
      } catch (e2) {
        error.value = e2.response?.data?.message || e2.formattedMessage || 'Data tidak ditemukan.'
      }
    } else {
      error.value = e.response?.data?.message || e.formattedMessage || 'Data tidak ditemukan.'
    }
  } finally {
    loading.value = false
  }
}

function printFormulir() {
  if (!printAreaRef.value) return
  const printContent = printAreaRef.value.innerHTML
  const win = window.open('', '_blank')
  if (!win) {
    alert('Izinkan pop-up untuk mencetak formulir.')
    return
  }
  win.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Formulir Pendaftaran PPDB - ${(result.value?.registration_number || '').replace(/</g, '&lt;')}</title>
      <style>
        body { font-family: 'Times New Roman', serif; font-size: 12px; padding: 20px; max-width: 210mm; margin: 0 auto; }
        .print-kop { text-align: center; margin-bottom: 16px; }
        .print-kop-name { margin: 0; font-size: 18px; font-weight: bold; }
        .print-kop-address { margin: 4px 0 0; }
        .print-kop-npsn { margin: 2px 0 0; font-size: 11px; color: #444; }
        .print-kop-line { border: none; border-top: 2px solid #000; margin: 12px 0; }
        .print-title { text-align: center; font-size: 14px; margin: 0 0 12px; }
        .print-reg-number, .print-meta { margin: 4px 0; }
        .print-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .print-table td { padding: 4px 8px; vertical-align: top; border: 1px solid #ddd; }
        .print-label { width: 28%; font-weight: bold; background: #f5f5f5; }
        .print-signatures { display: flex; justify-content: space-between; margin-top: 32px; padding-top: 24px; }
        .print-sig-block { width: 45%; text-align: center; }
        .print-sig-label { margin: 0 0 24px; font-size: 11px; }
        .print-sig-line { height: 48px; border-bottom: 1px solid #000; margin: 0 auto 4px; }
        .print-sig-name { margin: 0; font-size: 10px; }
      </style>
    </head>
    <body>${printContent}</body>
    </html>
  `)
  win.document.close()
  win.focus()
  setTimeout(() => {
    win.print()
    win.close()
  }, 250)
}
</script>

<style scoped>
.ppdb-check-page {
  min-height: 100vh;
  position: relative;
  max-width: 540px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
}
.public-main { position: relative; }
.check-form.card { margin-bottom: 1.5rem; }
.form-group { margin-bottom: 1.25rem; }
.form-input {
  width: 100%;
  padding: 0.65rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.form-input::placeholder { color: #94a3b8; }
.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  border: 1px solid #fecaca;
}
.btn-submit {
  width: 100%;
  padding: 0.85rem 1.5rem;
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 0.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: opacity 0.2s;
}
.btn-submit:hover:not(:disabled) { opacity: 0.95; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.success-banner {
  background: #ecfdf5;
  color: #065f46;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  border: 1px solid #a7f3d0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.success-banner .success-icon { flex-shrink: 0; }
.result-success-banner { margin-top: 0.5rem; }
.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.result-card.card { padding: 1.75rem 1.5rem; }
.result-list { margin: 0; padding: 0; list-style: none; }
.result-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 0.5rem 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
  align-items: baseline;
}
.result-row:last-child { border-bottom: none; }
.result-row-highlight { background: #ecfdf5; margin: 0 -1.25rem; padding: 0.6rem 1.25rem; border-radius: 10px; border: none; }
.result-row dt { font-size: 0.8125rem; color: #64748b; font-weight: 500; margin: 0; }
.result-row dd { margin: 0; font-size: 0.9375rem; color: #1e293b; }
.text-success { color: #059669; font-weight: 600; }
.status-badge {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}
.status-passed { background: #d1fae5; color: #065f46; }
.status-reserve { background: #fef3c7; color: #92400e; }
.status-failed, .status-rejected, .status-cancelled { background: #fee2e2; color: #991b1b; }
.status-converted { background: #d1fae5; color: #047857; }
.status-submitted, .status-verification, .status-verified, .status-re_registration { background: #ecfdf5; color: #047857; }
.status-draft { background: #f1f5f9; color: #475569; }
.result-actions {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.btn-confirm-rereg {
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-width: 120px;
}
.btn-confirm-rereg:hover:not(:disabled) { opacity: 0.95; }
.btn-confirm-rereg:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-print {
  width: 100%;
  padding: 0.75rem 1.25rem;
  background: #f8fafc;
  color: #059669;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.btn-print:hover {
  background: #059669;
  color: #fff;
  border-color: #059669;
}
.btn-print svg { flex-shrink: 0; }
.formulir-print-wrap { display: none; }
@media print {
  .formulir-print-wrap { display: block; }
}

/* UI polish */
.page-bg {
  position: fixed;
  inset: 0;
  z-index: -1;
  background: #f8fafc;
}
.bg-pattern {
  position: absolute;
  inset: 0;
  opacity: 0.4;
  background-image: radial-gradient(circle at 1px 1px, #cbd5e1 1px, transparent 0);
  background-size: 24px 24px;
}
.bg-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(5, 150, 105, 0.06) 0%, transparent 40%);
  pointer-events: none;
}
.public-header {
  margin-bottom: 2rem;
  text-align: left;
}
.header-content { max-width: 36ch; }
.header-badge {
  display: inline-block;
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.3rem 0.7rem;
  border-radius: 8px;
  letter-spacing: 0.03em;
  margin-bottom: 0.6rem;
}
.public-header h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  line-height: 1.25;
}
.header-desc {
  margin: 0.5rem 0 0;
  color: #64748b;
  font-size: 0.9375rem;
  line-height: 1.5;
}
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #64748b;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
  transition: color 0.2s;
}
.back-link:hover { color: #059669; }
.back-icon { flex-shrink: 0; }
.card {
  background: #fff;
  border-radius: 16px;
  padding: 1.75rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04);
  border: 1px solid rgba(0,0,0,0.05);
}
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 1rem;
  color: #94a3b8;
  pointer-events: none;
}
.input-wrap .form-input {
  padding-left: 2.75rem;
}
.form-label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #334155;
}
.form-hint {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0.2rem 0 0.5rem;
  line-height: 1.4;
}
.error-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #fef2f2;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  border: 1px solid #fecaca;
}
.error-icon { flex-shrink: 0; }
.btn-submit {
  margin-top: 0.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.btn-submit .btn-icon { flex-shrink: 0; }
.result-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.result-title {
  margin: 0;
  font-size: 1.0625rem;
  font-weight: 600;
  color: #334155;
}
.result-hero {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #f1f5f9;
}
.result-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #059669 0%, #059669 100%);
  color: #fff;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.result-hero-text { min-width: 0; }
.result-name {
  display: block;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}
.result-reg {
  display: block;
  font-size: 0.8125rem;
  color: #64748b;
  margin-top: 0.15rem;
}

@media (max-width: 480px) {
  .result-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }
  .result-card.card {
    padding: 1rem;
  }
  .result-hero {
    flex-direction: column;
    text-align: center;
  }
}
</style>
