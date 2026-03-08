<template>
  <div class="ppdb-public-page">
    <div class="page-bg" aria-hidden="true">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
    </div>

    <nav class="top-bar">
      <router-link to="/" class="top-bar-link">← Beranda</router-link>
      <span class="top-bar-brand">PPDB</span>
    </nav>

    <header class="hero" v-if="institution || npsn">
      <div class="hero-inner">
        <h1 class="hero-title">Formulir Pendaftaran</h1>
        <p class="hero-subtitle">Penerimaan Peserta Didik Baru</p>
        <p v-if="institution" class="hero-school">{{ institution.name }}</p>
      </div>
    </header>

    <main class="public-main">
      <div v-if="loading" class="state-wrap state-loading card">
        <div class="spinner"></div>
        <p>Memuat data pendaftaran...</p>
      </div>
      <div v-else-if="!institutionId && !npsn" class="state-wrap state-error card">
        <div class="state-icon state-icon-error">!</div>
        <h3>Link tidak valid</h3>
        <p>Gunakan link yang diberikan sekolah (berisi NPSN).</p>
      </div>
      <div v-else-if="error" class="state-wrap state-error card">
        <div class="state-icon state-icon-error">!</div>
        <p>{{ error }}</p>
      </div>
      <div v-else-if="submitted" class="state-success-card card">
        <div class="success-icon-wrap">
          <svg class="success-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </div>
        <h2 class="success-title">Pendaftaran Berhasil</h2>
        <p class="success-intro">Data pendaftaran Anda telah tersimpan. Simpan nomor pendaftaran di bawah untuk keperluan selanjutnya.</p>

        <div class="registration-block">
          <p class="registration-label">Nomor Pendaftaran</p>
          <div class="registration-box">
            <span class="registration-value">{{ submittedData.registration_number }}</span>
            <button
              type="button"
              :class="['btn-copy', { 'btn-copy-done': copyDone }]"
              :aria-label="copyDone ? 'Tersalin' : 'Salin nomor'"
              :title="copyDone ? 'Tersalin' : 'Salin ke clipboard'"
              @click="copyRegistrationNumber"
            >
              <template v-if="copyDone">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 6L9 17l-5-5"/>
                </svg>
                <span>Tersalin</span>
              </template>
              <template v-else>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <span>Salin</span>
              </template>
            </button>
          </div>
          <p v-if="submittedData.period || submittedData.channel" class="registration-meta">
            {{ submittedData.period }} — {{ submittedData.channel }}
          </p>
        </div>

        <div class="success-next-steps">
          <p class="next-steps-title">Langkah selanjutnya</p>
          <ul class="next-steps-list">
            <li>Simpan atau foto nomor pendaftaran di atas.</li>
            <li>Lengkapi berkas (foto, KK, akte kelahiran) jika sekolah meminta.</li>
            <li>Cek hasil seleksi nanti dengan nomor pendaftaran atau NISN.</li>
          </ul>
        </div>

        <div class="success-actions">
          <router-link :to="linkLengkapiBerkas" class="btn-success-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Lengkapi berkas
          </router-link>
          <router-link to="/cek-hasil-ppdb" class="btn-success-secondary">
            Cek hasil seleksi
          </router-link>
          <button type="button" class="btn-success-secondary btn-print" @click="printFormulir">
            Cetak formulir
          </button>
        </div>
        <!-- Area cetak: hidden di layar, tampil saat print -->
        <div ref="printAreaRef" class="formulir-print-wrap">
          <div class="formulir-print">
            <header class="print-kop">
              <h1 class="print-kop-name">{{ institution?.name || 'Sekolah' }}</h1>
              <p v-if="institution?.address" class="print-kop-address">{{ institution.address }}</p>
              <p v-if="institution?.npsn" class="print-kop-npsn">NPSN: {{ institution.npsn }}</p>
              <hr class="print-kop-line" />
            </header>
            <h2 class="print-title">Formulir Pendaftaran PPDB</h2>
            <p class="print-reg-number"><strong>Nomor Pendaftaran:</strong> {{ submittedData?.registration_number }}</p>
            <p class="print-meta">{{ submittedData?.period }} — {{ submittedData?.channel }}</p>
            <table class="print-table">
              <tbody>
                <tr><td class="print-label">Nama Lengkap</td><td>{{ form.name }}</td></tr>
                <tr><td class="print-label">NIK</td><td>{{ form.nik }}</td></tr>
                <tr><td class="print-label">NISN</td><td>{{ form.nisn }}</td></tr>
                <tr><td class="print-label">Jenis Kelamin</td><td>{{ form.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                <tr><td class="print-label">Tempat, Tanggal Lahir</td><td>{{ form.birth_place }}, {{ form.birth_date }}</td></tr>
                <tr><td class="print-label">Alamat</td><td>{{ form.address }}</td></tr>
                <tr><td class="print-label">Telepon / Email</td><td>{{ form.phone }} / {{ form.email }}</td></tr>
                <tr><td class="print-label">Agama</td><td>{{ form.religion }}</td></tr>
                <tr><td class="print-label">Sekolah Asal (NPSN)</td><td>{{ form.previous_school_npsn }}</td></tr>
                <tr><td class="print-label">Nama Sekolah Asal</td><td>{{ form.previous_school }}</td></tr>
                <tr><td class="print-label">Alamat Sekolah Asal</td><td>{{ form.previous_school_address }}</td></tr>
                <tr><td class="print-label">Ayah (Nama, NIK, Telepon)</td><td>{{ form.father_name }}, {{ form.father_nik }}, {{ form.father_phone }}</td></tr>
                <tr><td class="print-label">Ibu (Nama, NIK, Telepon)</td><td>{{ form.mother_name }}, {{ form.mother_nik }}, {{ form.mother_phone }}</td></tr>
                <tr><td class="print-label">Wali</td><td>{{ form.guardian_name }} {{ form.guardian_phone }} {{ form.guardian_relation }}</td></tr>
                <tr v-if="form.notes"><td class="print-label">Catatan</td><td>{{ form.notes }}</td></tr>
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
      <div v-else-if="periods.length === 0" class="state-wrap state-empty card">
        <div class="state-icon state-icon-empty">📋</div>
        <h3>Belum ada periode dibuka</h3>
        <p>Saat ini tidak ada periode pendaftaran yang dibuka untuk sekolah ini.</p>
      </div>
      <form v-else class="register-form" @submit.prevent="submit">
        <div class="form-card card">
          <div class="step-bar">
            <span class="step active"><em>1</em> Periode & Jalur</span>
            <span class="step"><em>2</em> Sekolah Asal</span>
            <span class="step"><em>3</em> Identitas</span>
            <span class="step"><em>4</em> Orang Tua</span>
          </div>
          <section class="form-section">
            <h3 class="section-title">Periode & Jalur Pendaftaran</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Periode PPDB <span class="required">*</span></label>
                <select v-model="form.ppdb_period_id" required class="form-input" @change="form.ppdb_channel_id = ''">
                  <option value="">Pilih periode</option>
                  <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }} ({{ p.open_date }} s/d {{ p.close_date }})</option>
                </select>
              </div>
              <div class="form-group">
                <label>Jalur Pendaftaran <span class="required">*</span></label>
                <select v-model="form.ppdb_channel_id" required class="form-input">
                  <option value="">Pilih jalur</option>
                  <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
            </div>
          </section>
        </div>

        <div class="form-card card">
          <section class="form-section">
            <h3 class="section-title">Sekolah Asal</h3>
            <p class="section-hint">Masukkan NPSN sekolah asal (8 digit). Sistem akan mengecek ke data Kemendikbud dan sekolah terdaftar; nama dan alamat terisi otomatis jika ditemukan.</p>
            <div class="form-group">
              <label>NPSN Sekolah Asal</label>
              <div class="input-with-action">
                <input
                  v-model="form.previous_school_npsn"
                  type="text"
                  class="form-input"
                  placeholder="8 digit NPSN"
                  maxlength="20"
                  @input="form.previous_school_npsn = (form.previous_school_npsn || '').replace(/\D/g, '').slice(0, 8)"
                  @blur="onBlurPreviousSchoolNpsn()"
                />
                <button type="button" class="btn-lookup" :disabled="schoolLookupLoading || !form.previous_school_npsn?.trim()" @click="lookupSchoolByNpsn">
                  {{ schoolLookupLoading ? '...' : 'Cari' }}
                </button>
              </div>
              <p v-if="schoolLookupError" class="field-error">{{ schoolLookupError }}</p>
            </div>
            <div v-if="form.previous_school || form.previous_school_address" class="school-preview">
              <div v-if="form.previous_school" class="school-preview-row">
                <span class="label">Nama Sekolah</span>
                <span class="value">{{ form.previous_school }}</span>
              </div>
              <div v-if="form.previous_school_address" class="school-preview-row">
                <span class="label">Alamat</span>
                <span class="value">{{ form.previous_school_address }}</span>
              </div>
              <p class="section-hint small">Data dapat disunting di bawah jika perlu.</p>
            </div>
            <p v-else-if="form.previous_school_npsn?.trim() && !schoolLookupLoading && !schoolLookupError" class="section-hint small manual-hint">
              Sekolah tidak ada di sistem. Isi nama dan alamat sekolah asal secara manual di bawah.
            </p>
            <div class="form-group">
              <label>Nama Sekolah Asal</label>
              <input v-model="form.previous_school" type="text" class="form-input" placeholder="Nama sekolah sebelumnya (isi manual jika tidak ditemukan)" />
            </div>
            <div class="form-group">
              <label>Alamat Sekolah Asal</label>
              <textarea v-model="form.previous_school_address" rows="2" class="form-input" placeholder="Alamat lengkap sekolah asal (isi manual jika tidak ditemukan)"></textarea>
            </div>
          </section>
        </div>

        <div class="form-card card">
          <section class="form-section">
            <h3 class="section-title">Identitas Calon Siswa</h3>
            <p class="section-hint">Isi NISN terlebih dahulu. Jika Anda dari sekolah yang terdaftar di sistem, formulir akan terisi otomatis.</p>
            <div class="form-group">
              <label>NISN <span class="required">*</span></label>
              <div class="input-with-action">
                <input
                  v-model="form.nisn"
                  type="text"
                  class="form-input"
                  placeholder="10 digit NISN"
                  maxlength="10"
                  @blur="tryPrefillByNisn"
                />
                <button type="button" class="btn-lookup" :disabled="prefillLoading || form.nisn?.length < 10 || !form.previous_school_npsn?.trim()" @click="tryPrefillByNisn">
                  {{ prefillLoading ? '...' : 'Isi Otomatis' }}
                </button>
              </div>
              <p v-if="prefillError" class="field-error">{{ prefillError }}</p>
              <p v-else-if="prefillFound" class="field-success">Data ditemukan. Formulir terisi otomatis dari sekolah asal. Periksa dan sunting jika perlu.</p>
            </div>
            <div class="form-group">
              <label>Nama Lengkap <span class="required">*</span></label>
              <input v-model="form.name" type="text" required class="form-input" placeholder="Nama sesuai dokumen" />
            </div>
            <div class="form-grid form-grid-3">
              <div class="form-group">
                <label>NIK</label>
                <input v-model="form.nik" type="text" class="form-input" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Jenis Kelamin <span class="required">*</span></label>
                <select v-model="form.gender" required class="form-input">
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
              <div class="form-group">
                <label>Agama</label>
                <select v-model="form.religion" class="form-input">
                  <option value="">Pilih agama</option>
                  <option value="Islam">Islam</option>
                  <option value="Kristen">Kristen</option>
                  <option value="Katolik">Katolik</option>
                  <option value="Hindu">Hindu</option>
                  <option value="Buddha">Buddha</option>
                  <option value="Konghucu">Konghucu</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label>Tempat Lahir</label>
                <input v-model="form.birth_place" type="text" class="form-input" />
              </div>
              <div class="form-group">
                <label>Tanggal Lahir</label>
                <input v-model="form.birth_date" type="date" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Alamat</label>
              <textarea v-model="form.address" rows="3" class="form-input" placeholder="Alamat lengkap"></textarea>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label>Telepon / HP</label>
                <input v-model="form.phone" type="text" class="form-input" placeholder="08xxxxxxxxxx" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input v-model="form.email" type="email" class="form-input" placeholder="email@contoh.com" />
              </div>
            </div>
            <p class="section-hint small">Sekolah asal sudah diisi di langkah 2. Untuk mengubah, gulir ke atas.</p>
          </section>
        </div>

        <div class="form-card card">
          <section class="form-section">
            <h3 class="section-title">Data Orang Tua / Wali</h3>
            <div class="form-grid form-grid-3">
              <div class="form-group">
                <label>Nama Ayah</label>
                <input v-model="form.father_name" type="text" class="form-input" />
              </div>
              <div class="form-group">
                <label>NIK Ayah</label>
                <input v-model="form.father_nik" type="text" class="form-input" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Telepon Ayah</label>
                <input v-model="form.father_phone" type="text" class="form-input" />
              </div>
            </div>
            <div class="form-grid form-grid-3">
              <div class="form-group">
                <label>Nama Ibu</label>
                <input v-model="form.mother_name" type="text" class="form-input" />
              </div>
              <div class="form-group">
                <label>NIK Ibu</label>
                <input v-model="form.mother_nik" type="text" class="form-input" placeholder="16 digit" />
              </div>
              <div class="form-group">
                <label>Telepon Ibu</label>
                <input v-model="form.mother_phone" type="text" class="form-input" />
              </div>
            </div>
            <div class="form-group">
              <label>Wali (jika ada)</label>
              <div class="form-grid form-grid-3 wali-inputs">
                <input v-model="form.guardian_name" type="text" class="form-input" placeholder="Nama wali" />
                <input v-model="form.guardian_phone" type="text" class="form-input" placeholder="Telepon" />
                <input v-model="form.guardian_relation" type="text" class="form-input" placeholder="Hubungan" />
              </div>
            </div>
            <div class="form-group">
              <label>Catatan (opsional)</label>
              <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Catatan tambahan, alasan memilih sekolah ini, dll."></textarea>
            </div>
          </section>
        </div>

        <div v-if="formError" class="error-banner">{{ formError }}</div>
        <div class="form-actions">
          <button type="submit" class="btn-submit" :disabled="submitting">
            <span v-if="submitting" class="btn-spinner"></span>
            {{ submitting ? 'Mengirim...' : 'Daftar Sekarang' }}
          </button>
        </div>
      </form>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ppdbPublicApi } from '@/api/ppdbPublic'
import { schoolPublicApi } from '@/api/schoolPublic'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const route = useRoute()
const institutionId = computed(() => route.query.institution_id ? Number(route.query.institution_id) : null)
const npsn = computed(() => route.params.npsn || route.query.npsn || '')

const loading = ref(true)
const error = ref('')
const periods = ref([])
const channels = ref([])
const institution = ref(null)
const schoolLookupLoading = ref(false)
const schoolLookupError = ref('')
const prefillLoading = ref(false)
const prefillError = ref('')
const prefillFound = ref(false)
const form = ref({
  ppdb_period_id: '',
  ppdb_channel_id: '',
  name: '',
  nik: '',
  nisn: '',
  gender: 'L',
  birth_date: '',
  birth_place: '',
  address: '',
  phone: '',
  email: '',
  religion: '',
  previous_school: '',
  previous_school_npsn: '',
  previous_school_address: '',
  father_name: '',
  father_phone: '',
  father_nik: '',
  mother_name: '',
  mother_phone: '',
  mother_nik: '',
  guardian_name: '',
  guardian_phone: '',
  guardian_relation: '',
  notes: '',
})
const formError = ref('')
const submitting = ref(false)
const submitted = ref(false)
const submittedData = ref(null)
const printAreaRef = ref(null)
const copyDone = ref(false)
let copyDoneTimer = null

const params = computed(() => {
  const p = {}
  if (institutionId.value) p.institution_id = institutionId.value
  if (npsn.value) p.npsn = npsn.value
  return p
})

const linkLengkapiBerkas = computed(() => {
  const reg = submittedData.value?.registration_number
  const n = institution.value?.npsn
  if (!reg) return { path: '/lengkapi-berkas-ppdb' }
  return {
    path: '/lengkapi-berkas-ppdb',
    query: { registration_number: reg, ...(n ? { npsn: n } : {}) },
  }
})

async function loadData() {
  if (!institutionId.value && !npsn.value) {
    loading.value = false
    return
  }
  loading.value = true
  error.value = ''
  try {
    const [periodsRes, channelsRes] = await Promise.all([
      ppdbPublicApi.getOpenPeriods(params.value),
      ppdbPublicApi.getOpenChannels(params.value),
    ])
    periods.value = periodsRes.data?.data || []
    institution.value = periodsRes.data?.institution || null
    channels.value = channelsRes.data?.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.formattedMessage || 'Gagal memuat data. Periksa institution_id atau NPSN.'
  } finally {
    loading.value = false
  }
}

watch([institutionId, npsn], loadData, { immediate: true })

function onBlurPreviousSchoolNpsn() {
  const digits = (form.value.previous_school_npsn || '').replace(/\D/g, '')
  if (digits.length === 8) {
    lookupSchoolByNpsn()
  } else {
    schoolLookupError.value = ''
  }
}

async function lookupSchoolByNpsn() {
  const npsnVal = form.value.previous_school_npsn?.trim()
  if (!npsnVal || npsnVal.length < 8) {
    schoolLookupError.value = ''
    return
  }
  schoolLookupError.value = ''
  schoolLookupLoading.value = true
  try {
    // 1) Cek dulu apakah sekolah terdaftar di sistem (data lokal)
    try {
      const resLocal = await schoolPublicApi.getInstitution(npsnVal)
      const dataLocal = resLocal.data?.data
      if (dataLocal) {
        form.value.previous_school = dataLocal.name ?? ''
        form.value.previous_school_address = dataLocal.address ?? ''
        schoolLookupLoading.value = false
        return
      }
    } catch (_) {
      // Tidak ada di sistem, lanjut ke referensi Kemendikbud
    }
    // 2) Validasi ke data referensi Kemendikbud (NPSN resmi)
    const res = await schoolPublicApi.lookupNpsnReferensi(npsnVal)
    const data = res.data?.data
    if (data?.valid && (data.name || data.address || data.institution)) {
      if (data.institution) {
        form.value.previous_school = data.institution.name ?? data.name ?? ''
        form.value.previous_school_address = data.institution.address ?? data.address ?? ''
      } else {
        form.value.previous_school = data.name ?? ''
        form.value.previous_school_address = data.address ?? ''
      }
    } else {
      form.value.previous_school = ''
      form.value.previous_school_address = ''
      schoolLookupError.value = 'NPSN tidak ditemukan di data Kemendikbud. Anda dapat mengisi nama dan alamat sekolah asal secara manual di bawah.'
    }
  } catch (e) {
    if (e.response?.status === 422) {
      form.value.previous_school = ''
      form.value.previous_school_address = ''
      schoolLookupError.value = 'NPSN harus 8 digit.'
    } else {
      form.value.previous_school = ''
      form.value.previous_school_address = ''
      schoolLookupError.value = e.response?.data?.message || 'Gagal memeriksa NPSN. Anda dapat mengisi nama dan alamat secara manual.'
    }
  } finally {
    schoolLookupLoading.value = false
  }
}

async function tryPrefillByNisn() {
  const nisnVal = form.value.nisn?.trim()
  const prevNpsn = form.value.previous_school_npsn?.trim()
  const instId = institution.value?.id
  if (!nisnVal || nisnVal.length < 10 || !prevNpsn || !instId) {
    prefillError.value = 'Isi NPSN sekolah asal (langkah 2) dan NISN (10 digit).'
    return
  }
  prefillError.value = ''
  prefillFound.value = false
  prefillLoading.value = true
  try {
    const res = await ppdbPublicApi.getPrefill({
      institution_id: instId,
      previous_school_npsn: prevNpsn,
      nisn: nisnVal,
    })
    if (res.data?.found && res.data?.data) {
      const d = res.data.data
      form.value.name = d.name ?? form.value.name
      form.value.nik = d.nik ?? form.value.nik
      form.value.nisn = d.nisn ?? form.value.nisn
      form.value.gender = d.gender ?? form.value.gender
      form.value.birth_date = d.birth_date ?? form.value.birth_date
      form.value.birth_place = d.birth_place ?? form.value.birth_place
      form.value.address = d.address ?? form.value.address
      form.value.phone = d.phone ?? form.value.phone
      form.value.email = d.email ?? form.value.email
      form.value.religion = d.religion ?? form.value.religion
      form.value.previous_school = d.previous_school ?? form.value.previous_school
      form.value.previous_school_npsn = d.previous_school_npsn ?? form.value.previous_school_npsn
      form.value.previous_school_address = d.previous_school_address ?? form.value.previous_school_address
      form.value.father_name = d.father_name ?? form.value.father_name
      form.value.father_nik = d.father_nik ?? form.value.father_nik
      form.value.mother_name = d.mother_name ?? form.value.mother_name
      form.value.mother_nik = d.mother_nik ?? form.value.mother_nik
      form.value.guardian_name = d.guardian_name ?? form.value.guardian_name
      form.value.guardian_phone = d.guardian_phone ?? form.value.guardian_phone
      prefillFound.value = true
    } else {
      prefillError.value = 'Data siswa tidak ditemukan di sekolah asal. Isi formulir secara manual.'
    }
  } catch (e) {
    prefillError.value = e.response?.data?.message || 'Gagal memuat data. Isi formulir secara manual.'
  } finally {
    prefillLoading.value = false
  }
}

async function submit() {
  formError.value = ''
  submitting.value = true
  try {
    const res = await ppdbPublicApi.register(form.value)
    submittedData.value = res.data?.data || res.data
    submitted.value = true
  } catch (e) {
    const data = e.response?.data
    if (data?.errors && typeof data.errors === 'object') {
      const parts = Object.entries(data.errors).map(([k, v]) => (Array.isArray(v) ? v[0] : v))
      formError.value = parts.join(' ')
    } else {
      formError.value = data?.message || e.formattedMessage || 'Gagal mengirim pendaftaran.'
    }
  } finally {
    submitting.value = false
  }
}

function copyRegistrationNumber() {
  const num = submittedData.value?.registration_number
  if (!num) return
  if (copyDoneTimer) clearTimeout(copyDoneTimer)
  copyDone.value = false
  navigator.clipboard.writeText(num).then(() => {
    copyDone.value = true
    copyDoneTimer = setTimeout(() => { copyDone.value = false }, 2500)
  }).catch(() => {})
}

function printFormulir() {
  if (!printAreaRef.value) return
  const printContent = printAreaRef.value.innerHTML
  const win = window.open('', '_blank')
  if (!win) {
    toast.info('Cetak formulir', 'Izinkan pop-up browser untuk mencetak formulir pendaftaran.')
    return
  }
  win.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Formulir Pendaftaran PPDB - ${(submittedData.value?.registration_number || '').replace(/</g, '&lt;')}</title>
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
.ppdb-public-page {
  min-height: 100vh;
  position: relative;
  max-width: 720px;
  margin: 0 auto;
  padding: 0 1rem 4rem;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.page-bg {
  position: fixed;
  inset: 0;
  background: linear-gradient(165deg, #ecfdf5 0%, #d1fae5 25%, #ecfdf5 60%, #f0fdf4 100%);
  z-index: -1;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.4;
  pointer-events: none;
}
.blob-1 { width: 320px; height: 320px; background: #818cf8; top: -80px; right: -80px; }
.blob-2 { width: 280px; height: 280px; background: #059669; bottom: 20%; left: -60px; }
.blob-3 { width: 200px; height: 200px; background: #c4b5fd; bottom: -40px; right: 20%; }

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 0;
  margin-bottom: 0.5rem;
}
.top-bar-link {
  color: #047857;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
}
.top-bar-link:hover { text-decoration: underline; }
.top-bar-brand {
  font-size: 0.8rem;
  font-weight: 700;
  color: #047857;
  letter-spacing: 0.08em;
}

.hero {
  text-align: center;
  padding: 2rem 0 2.25rem;
}
.hero-inner { position: relative; }
.hero-title {
  margin: 0;
  font-size: 1.85rem;
  font-weight: 800;
  color: #1e1b4b;
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.hero-subtitle {
  margin: 0.35rem 0 0;
  font-size: 1rem;
  color: #047857;
  font-weight: 600;
}
.hero-school {
  margin: 0.75rem 0 0;
  font-size: 1.05rem;
  color: #4c1d95;
  font-weight: 500;
  padding: 0.4rem 1rem;
  background: rgba(255,255,255,0.7);
  border-radius: 999px;
  display: inline-block;
}

.public-main { position: relative; }
.card {
  background: #fff;
  border-radius: 20px;
  padding: 2rem 1.75rem;
  box-shadow: 0 10px 40px -12px rgba(5, 150, 105, 0.12), 0 4px 12px -4px rgba(0,0,0,0.06);
  border: 1px solid rgba(255,255,255,0.8);
}
.state-wrap { text-align: center; padding: 3rem 2rem; }
.state-loading { color: #64748b; }
.state-loading .spinner {
  width: 44px;
  height: 44px;
  margin: 0 auto 1.25rem;
  border: 3px solid #e9d5ff;
  border-top-color: #047857;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.state-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 1.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.4rem;
}
.state-icon-error { background: #fee2e2; color: #b91c1c; }
.state-icon-empty { background: #f3e8ff; font-size: 1.75rem; }
.state-wrap h2 { margin: 0 0 0.5rem; color: #1e1b4b; font-size: 1.5rem; font-weight: 700; }
.state-wrap h3 { margin: 0 0 0.5rem; color: #1e1b4b; font-size: 1.2rem; }
.state-error { color: #b91c1c; }
.state-empty { color: #64748b; }
.state-empty p { margin: 0; }

/* Success card after submit */
.state-success-card {
  text-align: center;
  padding: 2.5rem 1.75rem 2.75rem;
  max-width: 520px;
  margin: 0 auto;
}
.state-success-card .success-icon-wrap {
  width: 72px;
  height: 72px;
  margin: 0 auto 1.25rem;
  background: linear-gradient(135deg, #059669, #047857);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px -4px rgba(124, 58, 237, 0.35);
}
.state-success-card .success-icon { width: 36px; height: 36px; color: #fff; }
.state-success-card .success-title {
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
  font-weight: 800;
  color: #1e1b4b;
  letter-spacing: -0.02em;
}
.state-success-card .success-intro {
  font-size: 0.9375rem;
  color: #64748b;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}
.registration-block {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  border: 2px solid #c4b5fd;
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  margin: 0 0 1.5rem;
  text-align: center;
}
.registration-block .registration-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #047857;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.5rem;
}
.registration-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin: 0;
}
.registration-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: #047857;
  letter-spacing: 0.04em;
  font-family: ui-monospace, monospace;
  word-break: break-all;
}
.btn-copy {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  background: #fff;
  border: 1px solid #c4b5fd;
  border-radius: 10px;
  color: #047857;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}
.btn-copy:hover { background: #ecfdf5; border-color: #059669; }
.btn-copy:focus { outline: none; box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.35); }
.btn-copy svg { flex-shrink: 0; }
.btn-copy-done { background: #d1fae5 !important; border-color: #10b981 !important; color: #047857 !important; }
.btn-copy-done:hover { background: #a7f3d0 !important; }
.registration-meta {
  font-size: 0.875rem;
  color: #047857;
  margin: 0.75rem 0 0;
  font-weight: 500;
}
.success-next-steps {
  text-align: left;
  background: #fafafa;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  margin: 0 0 1.75rem;
  border: 1px solid #f1f5f9;
}
.next-steps-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #334155;
  margin: 0 0 0.75rem;
}
.next-steps-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.9rem;
  color: #64748b;
  line-height: 1.7;
}
.next-steps-list li { margin-bottom: 0.35rem; }
.next-steps-list li:last-child { margin-bottom: 0; }
.success-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: center;
  align-items: center;
}
.success-actions .btn-success-primary,
.success-actions .btn-success-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.85rem 1.35rem;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.1s, box-shadow 0.2s;
  min-width: 140px;
}
.btn-success-primary {
  background: linear-gradient(135deg, #047857 0%, #047857 100%);
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px -2px rgba(124, 58, 237, 0.4);
}
.btn-success-primary:hover { opacity: 0.95; box-shadow: 0 6px 20px -2px rgba(124, 58, 237, 0.45); }
.btn-success-secondary {
  background: #fff;
  color: #047857;
  border: 2px solid #c4b5fd;
}
.btn-success-secondary:hover { background: #ecfdf5; border-color: #059669; }
.success-actions .btn-print {
  background: #fff;
  border: 2px solid #e2e8f0;
  color: #64748b;
  font-family: inherit;
}
.success-actions .btn-print:hover { background: #f8fafc; border-color: #cbd5e1; color: #475569; }
@media (max-width: 480px) {
  .state-success-card { padding: 2rem 1.25rem 2.25rem; }
  .success-actions { flex-direction: column; }
  .success-actions .btn-success-primary,
  .success-actions .btn-success-secondary { width: 100%; min-width: 0; }
}
.btn-outline {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #047857;
  font-weight: 600;
  text-decoration: none;
  border-radius: 12px;
  font-size: 0.95rem;
  border: 2px solid #059669;
  transition: background 0.2s, color 0.2s;
}
.btn-outline:hover { background: #ecfdf5; color: #047857; }
.formulir-print-wrap {
  display: none;
  position: absolute;
  left: -9999px;
  width: 210mm;
}
.formulir-print-wrap .print-kop { text-align: center; margin-bottom: 16px; }
.formulir-print-wrap .print-kop-name { margin: 0; font-size: 18px; font-weight: bold; }
.formulir-print-wrap .print-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
.formulir-print-wrap .print-table td { padding: 4px 8px; vertical-align: top; border: 1px solid #ddd; }
.formulir-print-wrap .print-label { font-weight: bold; background: #f5f5f5; }
.formulir-print-wrap .print-signatures { display: flex; justify-content: space-between; margin-top: 32px; padding-top: 24px; }
.formulir-print-wrap .print-sig-block { width: 45%; text-align: center; }
.formulir-print-wrap .print-sig-line { height: 48px; border-bottom: 1px solid #000; margin: 0 auto 4px; }

.register-form .form-card { margin-bottom: 1.5rem; }
.step-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.75rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid #f3e8ff;
}
.step {
  flex: 1;
  text-align: center;
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 500;
}
.step em {
  display: block;
  font-style: normal;
  width: 28px;
  height: 28px;
  margin: 0 auto 0.35rem;
  background: #f1f5f9;
  color: #94a3b8;
  border-radius: 50%;
  line-height: 28px;
  font-weight: 700;
}
.step.active { color: #059669; }
.step.active em { background: linear-gradient(135deg, #059669, #047857); color: #fff; }
.section-title {
  margin: 0 0 0.5rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #334155;
}
.section-hint {
  margin: 0 0 1rem;
  font-size: 0.875rem;
  color: #64748b;
}
.section-hint.small { margin-bottom: 0.75rem; font-size: 0.8125rem; }
.input-with-action {
  display: flex;
  gap: 0.5rem;
  align-items: stretch;
}
.input-with-action .form-input { flex: 1; min-width: 0; }
.btn-lookup {
  padding: 0.7rem 1rem;
  background: linear-gradient(135deg, #059669, #047857);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.2s;
}
.btn-lookup:hover:not(:disabled) { opacity: 0.9; }
.btn-lookup:disabled { opacity: 0.6; cursor: not-allowed; }
.school-preview {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-top: 0.5rem;
}
.school-preview-row { margin-bottom: 0.5rem; }
.school-preview-row:last-child { margin-bottom: 0; }
.school-preview-row .label { display: block; font-size: 0.75rem; color: #64748b; margin-bottom: 0.2rem; }
.school-preview-row .value { font-size: 0.9375rem; color: #334155; }
.field-error { margin-top: 0.4rem; font-size: 0.8125rem; color: #b91c1c; }
.field-success { margin-top: 0.4rem; font-size: 0.875rem; color: #059669; font-weight: 500; }
.manual-hint { margin-bottom: 0.75rem; padding: 0.5rem 0.75rem; background: #fef3c7; border-radius: 8px; color: #92400e; }
.form-section:not(:first-child) .section-title { margin-top: 0; }
.form-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.subsection-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #475569;
  padding-top: 0.25rem;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  align-items: start;
}
.form-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
.wali-inputs { gap: 1.25rem; }
@media (max-width: 640px) {
  .form-grid,
  .form-grid-3 { grid-template-columns: 1fr; }
}
.form-group {
  margin: 0;
  min-width: 0;
}
.form-group label {
  display: block;
  margin-bottom: 0.45rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: #475569;
}
.required { color: #b91c1c; }
.form-input {
  width: 100%;
  min-height: 44px;
  padding: 0.7rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9375rem;
  line-height: 1.4;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: #fff;
  box-sizing: border-box;
}
select.form-input { cursor: pointer; appearance: auto; }
.form-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}
.form-input::placeholder { color: #94a3b8; }
textarea.form-input {
  min-height: 80px;
  resize: vertical;
  padding-top: 0.7rem;
}
.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
  border: 1px solid #fecaca;
  font-weight: 500;
}
.form-actions { margin-top: 1.5rem; }
.btn-submit {
  width: 100%;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #047857 0%, #047857 100%);
  color: #fff;
  border: none;
  border-radius: 14px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.05s, box-shadow 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: 0 4px 14px -2px rgba(124, 58, 237, 0.4);
}
.btn-submit:hover:not(:disabled) { box-shadow: 0 6px 20px -4px rgba(124, 58, 237, 0.45); }
.btn-submit:active:not(:disabled) { transform: scale(0.99); }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
</style>
