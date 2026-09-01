<template>    <div class="sp-page page">
      <div class="sp-page-header page-header">
        <div class="page-header-main">
          <p class="sp-subtitle page-subtitle">
            {{ profileMode === 'ubah'
              ? 'Ubah data non-kunci, atau ajukan perubahan data kunci ke operator.'
              : 'Ringkasan biodata Anda.' }}
          </p>
        </div>
        <div class="sp-actions">
          <div v-if="pendingCount" class="pending-chip">
            {{ pendingCount }} menunggu persetujuan
          </div>
          <button
            v-if="student && !profileError"
            type="button"
            class="sp-btn"
            :class="profileMode === 'ubah' ? 'sp-btn--ghost' : 'sp-btn--primary'"
            @click="profileMode = profileMode === 'ubah' ? 'lihat' : 'ubah'"
          >
            {{ profileMode === 'ubah' ? 'Selesai' : 'Ubah data' }}
          </button>
        </div>
      </div>

      <div v-if="profileError" class="sp-alert sp-alert-warning" role="alert">
        {{ profileError }}
      </div>

      <template v-else>
        <section class="hero-card">
          <div v-if="loadingProfile" class="hero-loading">Memuat profil...</div>
          <template v-else>
              <div class="hero-top">
                <div class="hero-avatar" aria-hidden="true">{{ initials }}</div>
                <div>
                  <h2 class="hero-name">{{ student?.name || 'Siswa' }}</h2>
                  <p class="hero-meta">
                    <span v-if="classLabel">{{ classLabel }}</span>
                    <span v-if="classLabel && student?.institution?.name" class="hero-dot">·</span>
                    <span v-if="student?.institution?.name">{{ student.institution.name }}</span>
                  </p>
                </div>
              </div>
              <div v-if="student?.nis || student?.nisn || student?.status" class="meta-grid">
                <div v-if="student?.nis" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">NIS</span>
                    <span class="meta-value">{{ student.nis }}</span>
                  </div>
                </div>
                <div v-if="student?.nisn" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">NISN</span>
                    <span class="meta-value">{{ student.nisn }}</span>
                  </div>
                </div>
                <div v-if="student?.status" class="meta-item">
                  <span class="meta-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </span>
                  <div class="meta-body">
                    <span class="meta-label">Status</span>
                    <span class="meta-value">{{ student.status }}</span>
                  </div>
                </div>
              </div>
            </template>
        </section>

        <div class="content-grid" :class="{ 'content-grid--single': profileMode === 'lihat' }">
          <div class="main-stack">
            <section v-if="profileMode === 'lihat'" class="panel">
              <div class="panel-header">
                <h2>Data Saat Ini</h2>
                <span class="panel-hint panel-hint-icons" title="Gembok tertutup = butuh approval · Gembok terbuka = edit langsung">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  approval
                  <span class="hint-sep">·</span>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M8 11V8a4 4 0 0 1 7.2-2.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  langsung
                </span>
              </div>

              <div v-if="loadingProfile" class="loading-wrap">Memuat profil...</div>

              <template v-else-if="student">
                <div v-for="group in profileGroups" :key="group.key" class="profile-group">
                  <h3 class="group-title">{{ group.title }}</h3>
                  <dl class="profile-grid">
                    <div v-for="item in group.items" :key="item.key" class="profile-item">
                      <dt>
                        {{ item.label }}
                        <span
                          v-if="item.adminOnly"
                          class="field-badge admin"
                          title="Hanya diubah operator sekolah"
                          aria-label="Hanya operator"
                        >
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </span>
                        <span
                          v-else-if="item.needsApproval"
                          class="field-badge key"
                          title="Data kunci — perubahan butuh persetujuan operator"
                          aria-label="Data kunci"
                        >
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </span>
                        <span
                          v-else
                          class="field-badge free"
                          title="Bisa diubah langsung tanpa persetujuan"
                          aria-label="Bisa diedit langsung"
                        >
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 11V8a4 4 0 0 1 7.2-2.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          </svg>
                        </span>
                      </dt>
                      <dd :class="{ empty: item.empty }">{{ item.value }}</dd>
                    </div>
                  </dl>
                </div>
              </template>
            </section>

            <section v-if="profileMode === 'ubah'" class="panel">
              <div class="panel-header">
                <h2>Edit Langsung</h2>
                <span class="panel-hint">Tanpa persetujuan</span>
              </div>
              <p class="section-desc">Ubah kontak, profil ringan, sekolah asal, dan detail ortu/wali non-identitas.</p>

              <form @submit.prevent="saveSelfEdit" class="form">
                <h3 class="form-section-title">Kontak & pribadi</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="self_phone">No. HP</label>
                    <input id="self_phone" v-model="selfForm.phone" type="text" maxlength="20" />
                  </div>
                  <div class="form-group">
                    <label for="self_religion">Agama</label>
                    <select id="self_religion" v-model="selfForm.religion">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in RELIGION_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_residence_type">Jenis tinggal</label>
                    <select id="self_residence_type" v-model="selfForm.residence_type">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in RESIDENCE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_aspiration">Cita-cita</label>
                    <input id="self_aspiration" v-model="selfForm.aspiration" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_hobby">Hobi</label>
                    <input id="self_hobby" v-model="selfForm.hobby" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_disability">Disabilitas</label>
                    <input id="self_disability" v-model="selfForm.disability" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_height">Tinggi (cm)</label>
                    <input id="self_height" v-model="selfForm.height" type="number" min="0" max="300" />
                  </div>
                  <div class="form-group">
                    <label for="self_weight">Berat (kg)</label>
                    <input id="self_weight" v-model="selfForm.weight" type="number" min="0" max="500" />
                  </div>
                  <div class="form-group form-group-full">
                    <AddressCascade v-model="selfForm" />
                  </div>
                </div>

                <h3 class="form-section-title">Sekolah asal</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="self_previous_school">Nama sekolah</label>
                    <input id="self_previous_school" v-model="selfForm.previous_school" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_previous_school_npsn">NPSN</label>
                    <input id="self_previous_school_npsn" v-model="selfForm.previous_school_npsn" type="text" maxlength="20" />
                  </div>
                  <div class="form-group form-group-full">
                    <label for="self_previous_school_address">Alamat sekolah asal</label>
                    <textarea id="self_previous_school_address" v-model="selfForm.previous_school_address" rows="2"></textarea>
                  </div>
                </div>

                <h3 class="form-section-title">Orang tua / wali (non-identitas)</h3>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="self_father_status">Status ayah</label>
                    <select id="self_father_status" v-model="selfForm.father_status">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in PARENT_STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_mother_status">Status ibu</label>
                    <select id="self_mother_status" v-model="selfForm.mother_status">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in PARENT_STATUS_OPTIONS" :key="'m'+opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_father_education">Pendidikan ayah</label>
                    <input id="self_father_education" v-model="selfForm.father_education" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_mother_education">Pendidikan ibu</label>
                    <input id="self_mother_education" v-model="selfForm.mother_education" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_father_occupation">Pekerjaan ayah</label>
                    <input id="self_father_occupation" v-model="selfForm.father_occupation" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_mother_occupation">Pekerjaan ibu</label>
                    <input id="self_mother_occupation" v-model="selfForm.mother_occupation" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_father_income">Penghasilan ayah</label>
                    <input id="self_father_income" v-model="selfForm.father_income" type="number" min="0" />
                  </div>
                  <div class="form-group">
                    <label for="self_mother_income">Penghasilan ibu</label>
                    <input id="self_mother_income" v-model="selfForm.mother_income" type="number" min="0" />
                  </div>
                  <div class="form-group">
                    <label for="self_father_birth_place">Tempat lahir ayah</label>
                    <input id="self_father_birth_place" v-model="selfForm.father_birth_place" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_mother_birth_place">Tempat lahir ibu</label>
                    <input id="self_mother_birth_place" v-model="selfForm.mother_birth_place" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_father_birth_date">Tgl lahir ayah</label>
                    <input id="self_father_birth_date" v-model="selfForm.father_birth_date" type="date" />
                  </div>
                  <div class="form-group">
                    <label for="self_mother_birth_date">Tgl lahir ibu</label>
                    <input id="self_mother_birth_date" v-model="selfForm.mother_birth_date" type="date" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_phone">No. HP wali</label>
                    <input id="self_guardian_phone" v-model="selfForm.guardian_phone" type="text" maxlength="20" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_type">Jenis wali</label>
                    <select id="self_guardian_type" v-model="selfForm.guardian_type">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in GUARDIAN_TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_status">Status wali</label>
                    <select id="self_guardian_status" v-model="selfForm.guardian_status">
                      <option value="">-- Pilih --</option>
                      <option v-for="opt in PARENT_STATUS_OPTIONS" :key="'g'+opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_education">Pendidikan wali</label>
                    <input id="self_guardian_education" v-model="selfForm.guardian_education" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_occupation">Pekerjaan wali</label>
                    <input id="self_guardian_occupation" v-model="selfForm.guardian_occupation" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_income">Penghasilan wali</label>
                    <input id="self_guardian_income" v-model="selfForm.guardian_income" type="number" min="0" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_birth_place">Tempat lahir wali</label>
                    <input id="self_guardian_birth_place" v-model="selfForm.guardian_birth_place" type="text" maxlength="255" />
                  </div>
                  <div class="form-group">
                    <label for="self_guardian_birth_date">Tgl lahir wali</label>
                    <input id="self_guardian_birth_date" v-model="selfForm.guardian_birth_date" type="date" />
                  </div>
                  <div class="form-group form-group-full">
                    <label for="self_notes">Catatan</label>
                    <textarea id="self_notes" v-model="selfForm.notes" rows="2"></textarea>
                  </div>
                </div>

                <p v-if="selfError" class="error-msg">{{ selfError }}</p>
                <button type="submit" class="btn-primary" :disabled="savingSelf || !selfDirty">
                  {{ savingSelf ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </form>
            </section>
          </div>

          <div v-if="profileMode === 'ubah'" class="side-stack">
            <section class="panel">
              <div class="panel-header">
                <h2>Ajukan Perubahan Data Kunci</h2>
              </div>
              <p class="section-desc">
                Nama, NIK, NIS, NISN, email, TTL, No. KK, serta nama/NIK orang tua & wali. Berlaku setelah disetujui operator.
              </p>

              <form @submit.prevent="submitRequest" class="form">
                <div class="form-group">
                  <label for="field_name">Field yang ingin diubah *</label>
                  <select id="field_name" v-model="form.field_name" required>
                    <option value="">-- Pilih field --</option>
                    <option v-for="f in approvalFields" :key="f" :value="f">{{ getFieldLabel(f) }}</option>
                  </select>
                </div>

                <div v-if="form.field_name" class="current-value">
                  <span class="current-label">Nilai saat ini</span>
                  <span class="current-text">{{ formatProfileValue(student?.[form.field_name], form.field_name) }}</span>
                </div>

                <div class="form-group">
                  <label for="new_value">Nilai baru</label>
                  <select v-if="form.field_name === 'gender'" id="new_value" v-model="form.new_value">
                    <option value="">-- Pilih --</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                  <input
                    v-else-if="isDateField(form.field_name)"
                    id="new_value"
                    v-model="form.new_value"
                    type="date"
                  />
                  <input
                    v-else-if="form.field_name === 'email'"
                    id="new_value"
                    v-model="form.new_value"
                    type="email"
                    maxlength="255"
                  />
                  <input
                    v-else-if="isNikField(form.field_name)"
                    id="new_value"
                    v-model="form.new_value"
                    type="text"
                    inputmode="numeric"
                    maxlength="16"
                    placeholder="16 digit"
                  />
                  <input
                    v-else
                    id="new_value"
                    v-model="form.new_value"
                    type="text"
                    :placeholder="form.field_name ? ('Masukkan ' + getFieldLabel(form.field_name)) : 'Pilih field terlebih dahulu'"
                    maxlength="500"
                    :disabled="!form.field_name"
                  />
                </div>

                <p v-if="submitError" class="error-msg">{{ submitError }}</p>
                <button type="submit" class="btn-primary btn-amber" :disabled="submitting || !form.field_name">
                  {{ submitting ? 'Mengirim...' : 'Kirim Permintaan' }}
                </button>
              </form>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h2>Riwayat Permintaan</h2>
                <span v-if="!loading && requests.length" class="panel-hint">{{ requests.length }} total</span>
              </div>

              <div v-if="loading" class="loading-wrap">Memuat...</div>
              <div v-else-if="requests.length === 0" class="empty-state">
                <p>Belum ada permintaan data kunci.</p>
              </div>
              <div v-else class="requests-list">
                <div
                  v-for="req in requests"
                  :key="req.id"
                  class="request-card"
                  :class="`request-${req.status}`"
                >
                  <div class="request-head">
                    <span class="field-name">{{ getFieldLabel(req.field_name) }}</span>
                    <span :class="['status-badge', `status-${req.status}`]">{{ getStatusLabel(req.status) }}</span>
                  </div>
                  <div class="request-values">
                    <div class="value-block">
                      <span class="value-label">Lama</span>
                      <span class="value-text">{{ formatProfileValue(req.old_value, req.field_name) }}</span>
                    </div>
                    <div class="value-arrow" aria-hidden="true">→</div>
                    <div class="value-block">
                      <span class="value-label">Baru</span>
                      <span class="value-text new">{{ formatProfileValue(req.new_value, req.field_name) }}</span>
                    </div>
                  </div>
                  <div class="request-foot">{{ formatDate(req.created_at) }}</div>
                  <div v-if="req.rejection_reason" class="rejection-box">
                    <strong>Alasan ditolak:</strong> {{ req.rejection_reason }}
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </template>
    </div></template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import AddressCascade from '@/components/AddressCascade.vue'
import { formatFullAddress } from '@/utils/addressFields'
import { useToast } from '@/composables/useToast'
import { studentChangeRequestApi } from '@/api/studentChangeRequest'

const toast = useToast()

const student = ref(null)
const profileMode = ref('lihat')
const profileError = ref('')
const loadingProfile = ref(true)
const approvalFields = ref([])
const selfEditableFields = ref([])
const requests = ref([])
const loading = ref(true)
const submitting = ref(false)
const submitError = ref('')
const savingSelf = ref(false)
const selfError = ref('')

const form = ref({ field_name: '', new_value: '' })

const SELF_KEYS = [
  'phone', 'religion', 'residence_type', 'aspiration', 'hobby', 'disability',
  'height', 'weight', 'address',
  'village', 'sub_district', 'district', 'province', 'postal_code',
  'wilayah_province_code', 'wilayah_regency_code', 'wilayah_district_code', 'wilayah_village_code',
  'previous_school', 'previous_school_npsn', 'previous_school_address',
  'father_status', 'mother_status', 'father_education', 'mother_education',
  'father_occupation', 'mother_occupation', 'father_income', 'mother_income',
  'father_birth_place', 'mother_birth_place', 'father_birth_date', 'mother_birth_date',
  'guardian_phone', 'guardian_type', 'guardian_status', 'guardian_education',
  'guardian_occupation', 'guardian_income', 'guardian_birth_place', 'guardian_birth_date',
  'notes'
]

const emptySelf = () => Object.fromEntries(SELF_KEYS.map((k) => [k, '']))
const selfForm = ref(emptySelf())
const selfBaseline = ref(emptySelf())

const FIELD_LABELS = {
  name: 'Nama',
  nik: 'NIK',
  nis: 'NIS',
  nisn: 'NISN',
  gender: 'Jenis Kelamin',
  birth_place: 'Tempat Lahir',
  birth_date: 'Tanggal Lahir',
  email: 'Email',
  address: 'Alamat',
  phone: 'No. HP',
  religion: 'Agama',
  no_kk: 'No. KK',
  aspiration: 'Cita-cita',
  hobby: 'Hobi',
  disability: 'Disabilitas',
  height: 'Tinggi Badan (cm)',
  weight: 'Berat Badan (kg)',
  previous_school: 'Sekolah Asal',
  previous_school_npsn: 'NPSN Sekolah Asal',
  previous_school_address: 'Alamat Sekolah Asal',
  residence_type: 'Jenis Tempat Tinggal',
  class_label: 'Kelas',
  status: 'Status',
  father_name: 'Nama Ayah',
  father_status: 'Status Ayah',
  father_nik: 'NIK Ayah',
  father_birth_place: 'Tempat Lahir Ayah',
  father_birth_date: 'Tanggal Lahir Ayah',
  father_education: 'Pendidikan Ayah',
  father_occupation: 'Pekerjaan Ayah',
  father_income: 'Penghasilan Ayah',
  mother_name: 'Nama Ibu',
  mother_status: 'Status Ibu',
  mother_nik: 'NIK Ibu',
  mother_birth_place: 'Tempat Lahir Ibu',
  mother_birth_date: 'Tanggal Lahir Ibu',
  mother_education: 'Pendidikan Ibu',
  mother_occupation: 'Pekerjaan Ibu',
  mother_income: 'Penghasilan Ibu',
  guardian_name: 'Nama Wali',
  guardian_phone: 'No. HP Wali',
  guardian_type: 'Jenis Wali',
  guardian_status: 'Status Wali',
  guardian_nik: 'NIK Wali',
  guardian_birth_place: 'Tempat Lahir Wali',
  guardian_birth_date: 'Tanggal Lahir Wali',
  guardian_education: 'Pendidikan Wali',
  guardian_occupation: 'Pekerjaan Wali',
  guardian_income: 'Penghasilan Wali',
  notes: 'Catatan'
}

const DEFAULT_APPROVAL = [
  'name', 'nik', 'nis', 'nisn', 'gender', 'birth_place', 'birth_date', 'email', 'no_kk',
  'father_name', 'father_nik', 'mother_name', 'mother_nik', 'guardian_name', 'guardian_nik'
]

const RELIGION_OPTIONS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']
const RESIDENCE_OPTIONS = [
  { value: 'tinggal_dengan_orang_tua', label: 'Tinggal dengan orang tua' },
  { value: 'asrama', label: 'Asrama' },
  { value: 'kost_kontrak', label: 'Kost / kontrak' },
  { value: 'lainnya', label: 'Lainnya' }
]
const PARENT_STATUS_OPTIONS = [
  { value: 'masih_hidup', label: 'Masih hidup' },
  { value: 'meninggal_dunia', label: 'Meninggal dunia' },
  { value: 'tidak_diketahui', label: 'Tidak diketahui' }
]
const GUARDIAN_TYPE_OPTIONS = [
  { value: 'sama_dengan_ayah', label: 'Sama dengan ayah' },
  { value: 'sama_dengan_ibu', label: 'Sama dengan ibu' },
  { value: 'lainnya', label: 'Lainnya' }
]

function getFieldLabel(field) {
  return FIELD_LABELS[field] || field
}

function isDateField(field) {
  return ['birth_date', 'father_birth_date', 'mother_birth_date', 'guardian_birth_date'].includes(field)
}

function isNikField(field) {
  return ['nik', 'father_nik', 'mother_nik', 'guardian_nik', 'no_kk'].includes(field)
}

function formatEnum(val, options) {
  const found = options.find((o) => o.value === val)
  return found ? found.label : val
}

function formatProfileValue(val, field) {
  if (val == null || val === '') return '-'
  if (field === 'gender') {
    if (val === 'L') return 'Laki-laki'
    if (val === 'P') return 'Perempuan'
  }
  if (field === 'residence_type') return formatEnum(val, RESIDENCE_OPTIONS) || val
  if (['father_status', 'mother_status', 'guardian_status'].includes(field)) {
    return formatEnum(val, PARENT_STATUS_OPTIONS) || val
  }
  if (field === 'guardian_type') return formatEnum(val, GUARDIAN_TYPE_OPTIONS) || val
  if (isDateField(field)) {
    const d = new Date(val)
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
    }
  }
  return String(val)
}

function getStatusLabel(status) {
  return ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' })[status] || status
}

function formatDate(s) {
  if (!s) return '-'
  return new Date(s).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

function normalize(val) {
  return val == null ? '' : String(val)
}

function syncSelfForm() {
  const t = student.value || {}
  const next = {}
  for (const k of SELF_KEYS) {
    let v = t[k]
    if (isDateField(k) && v) v = String(v).slice(0, 10)
    next[k] = normalize(v)
  }
  selfForm.value = { ...next }
  selfBaseline.value = { ...next }
}

const initials = computed(() => {
  const name = student.value?.name || ''
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'S'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const classLabel = computed(() => {
  const s = student.value
  if (!s) return ''
  return s.class_detail?.name || s.class || ''
})

const pendingCount = computed(() => requests.value.filter((r) => r.status === 'pending').length)

const selfDirty = computed(() =>
  SELF_KEYS.some((k) => normalize(selfForm.value[k]) !== normalize(selfBaseline.value[k]))
)

const approvalSet = computed(() => new Set(approvalFields.value.length ? approvalFields.value : DEFAULT_APPROVAL))

function makeItem(key, label, opts = {}) {
  let raw = student.value?.[key]
  if (key === 'class_label') raw = classLabel.value
  if (key === 'address') raw = formatFullAddress(student.value)
  const value = formatProfileValue(raw, key === 'class_label' ? 'class' : key)
  return {
    key,
    label,
    value,
    empty: value === '-',
    needsApproval: !!opts.needsApproval,
    adminOnly: !!opts.adminOnly
  }
}

const profileGroups = computed(() => {
  if (!student.value) return []
  const key = (k) => makeItem(k, getFieldLabel(k), { needsApproval: approvalSet.value.has(k) })
  const free = (k) => makeItem(k, getFieldLabel(k), { needsApproval: false })
  const admin = (k, label) => makeItem(k, label || getFieldLabel(k), { adminOnly: true })

  return [
    {
      key: 'identity',
      title: 'Identitas',
      items: [
        key('name'), key('nik'), key('nis'), key('nisn'), key('gender'),
        key('birth_place'), key('birth_date'), key('no_kk'),
        admin('class_label', 'Kelas'), admin('status')
      ]
    },
    {
      key: 'contact',
      title: 'Kontak & pribadi',
      items: [free('phone'), key('email'), free('address'), free('religion'), free('residence_type'), free('aspiration'), free('hobby'), free('disability'), free('height'), free('weight')]
    },
    {
      key: 'school',
      title: 'Sekolah asal',
      items: [free('previous_school'), free('previous_school_npsn'), free('previous_school_address')]
    },
    {
      key: 'parents',
      title: 'Orang tua & wali',
      items: [
        key('father_name'), key('father_nik'), free('father_status'), free('father_birth_place'), free('father_birth_date'),
        free('father_education'), free('father_occupation'), free('father_income'),
        key('mother_name'), key('mother_nik'), free('mother_status'), free('mother_birth_place'), free('mother_birth_date'),
        free('mother_education'), free('mother_occupation'), free('mother_income'),
        key('guardian_name'), key('guardian_nik'), free('guardian_phone'), free('guardian_type'), free('guardian_status'),
        free('guardian_birth_place'), free('guardian_birth_date'), free('guardian_education'), free('guardian_occupation'), free('guardian_income'),
        free('notes')
      ]
    }
  ]
})

async function loadProfile() {
  loadingProfile.value = true
  profileError.value = ''
  try {
    const res = await studentChangeRequestApi.getMyProfile()
    student.value = res.data?.data || null
    if (!student.value) profileError.value = 'Profil siswa tidak ditemukan.'
    else syncSelfForm()
  } catch (err) {
    profileError.value = err.response?.data?.message || 'Gagal memuat profil. Pastikan akun sudah terhubung dengan data siswa.'
  } finally {
    loadingProfile.value = false
  }
}

async function loadAllowedFields() {
  try {
    const res = await studentChangeRequestApi.getAllowedFields()
    const body = res.data || {}
    approvalFields.value = body.approval_fields || body.data || DEFAULT_APPROVAL
    selfEditableFields.value = body.self_editable_fields || SELF_KEYS
  } catch {
    approvalFields.value = DEFAULT_APPROVAL
    selfEditableFields.value = SELF_KEYS
  }
}

async function loadRequests() {
  loading.value = true
  try {
    const res = await studentChangeRequestApi.getAll({ per_page: 50 })
    requests.value = Array.isArray(res.data?.data) ? res.data.data : []
  } catch {
    toast.error('Gagal', 'Gagal memuat riwayat permintaan')
    requests.value = []
  } finally {
    loading.value = false
  }
}

async function saveSelfEdit() {
  selfError.value = ''
  if (!selfDirty.value) {
    selfError.value = 'Tidak ada perubahan untuk disimpan.'
    return
  }

  const keys = selfEditableFields.value.length ? selfEditableFields.value : SELF_KEYS
  const payload = {}
  const numericKeys = ['height', 'weight', 'father_income', 'mother_income', 'guardian_income']

  for (const key of keys) {
    if (normalize(selfForm.value[key]) === normalize(selfBaseline.value[key])) continue
    const raw = (selfForm.value[key] ?? '').toString().trim()
    if (raw === '') {
      payload[key] = null
    } else if (numericKeys.includes(key)) {
      payload[key] = Number(raw)
    } else {
      payload[key] = raw
    }
  }

  if (!Object.keys(payload).length) {
    selfError.value = 'Tidak ada perubahan untuk disimpan.'
    return
  }

  savingSelf.value = true
  try {
    const res = await studentChangeRequestApi.updateMyProfile(payload)
    student.value = res.data?.data || student.value
    syncSelfForm()
    toast.success('Berhasil', 'Profil berhasil diperbarui.')
  } catch (err) {
    selfError.value = err.response?.data?.message
      || err.response?.data?.errors?.fields?.[0]
      || 'Gagal menyimpan profil'
    toast.error('Gagal', selfError.value)
  } finally {
    savingSelf.value = false
  }
}

async function submitRequest() {
  submitError.value = ''
  if (!form.value.field_name || !student.value?.id) {
    submitError.value = 'Pilih field yang ingin diubah.'
    return
  }
  submitting.value = true
  try {
    await studentChangeRequestApi.create({
      student_id: student.value.id,
      field_name: form.value.field_name,
      new_value: (form.value.new_value || '').trim() || null
    })
    toast.success('Berhasil', 'Permintaan telah dikirim. Menunggu persetujuan operator.')
    form.value = { field_name: '', new_value: '' }
    await loadRequests()
    await loadProfile()
  } catch (err) {
    submitError.value = err.response?.data?.message
      || Object.values(err.response?.data?.errors || {})?.[0]?.[0]
      || 'Gagal mengirim permintaan'
    toast.error('Gagal', submitError.value)
  } finally {
    submitting.value = false
  }
}

watch(
  () => form.value.field_name,
  (field) => {
    if (!field) {
      form.value.new_value = ''
      return
    }
    const current = student.value?.[field]
    if (isDateField(field) && current) {
      form.value.new_value = String(current).slice(0, 10)
    } else if (current != null && current !== '') {
      form.value.new_value = String(current)
    } else {
      form.value.new_value = ''
    }
  }
)

onMounted(async () => {
  await loadAllowedFields()
  await loadProfile()
  await loadRequests()
})
</script>

<style scoped>
.page { width: 100%; max-width: 100%; padding: 0 0 8px; }
.page-header { margin-bottom: 2px; }
.page-header-main { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
.page-subtitle { max-width: 56ch; }
.pending-chip {
  font-size: 12px; font-weight: 600; color: #b45309; background: #fffbeb;
  border: 1px solid #fde68a; padding: 6px 12px; border-radius: 999px; white-space: nowrap;
}
.hero-card {
  display: flex; flex-direction: column; gap: 14px; padding: 18px 20px; margin-bottom: 0;
  border-radius: 16px; background: linear-gradient(120deg, #0d9488 0%, #059669 50%, #047857 100%);
  color: #fff; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
}
.hero-top { display: flex; align-items: center; gap: 16px; }
.hero-avatar {
  width: 56px; height: 56px; border-radius: 14px; background: rgba(255,255,255,0.2);
  border: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 700; flex-shrink: 0;
}
.hero-loading { opacity: 0.85; font-style: italic; font-size: 14px; }
.hero-name { margin: 0 0 4px; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
.hero-meta { margin: 0; font-size: 13px; opacity: 0.92; font-weight: 500; }
.hero-dot { margin: 0 6px; opacity: 0.7; }
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 8px 12px;
  padding-top: 14px;
  border-top: 1px solid rgba(255,255,255,0.2);
}
.meta-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
.meta-icon {
  flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 8px; background: rgba(255,255,255,0.16); color: #fff;
}
.meta-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.meta-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(255,255,255,0.7); }
.meta-value { font-size: 13.5px; font-weight: 600; color: #fff; line-height: 1.35; word-break: break-word; }
.content-grid {
  display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(300px, 0.85fr);
  gap: 16px; align-items: start;
}
.content-grid--single { grid-template-columns: 1fr; }
.main-stack, .side-stack { display: flex; flex-direction: column; gap: 16px; }
.panel {
  background: #fff; border-radius: 16px; border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 22px;
}
.panel-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.panel-header h2 { margin: 0; font-size: 17px; font-weight: 700; color: #0f172a; letter-spacing: -0.2px; }
.panel-hint {
  font-size: 12px; font-weight: 600; color: #64748b; background: #f1f5f9;
  padding: 3px 8px; border-radius: 999px;
}
.panel-hint-icons { display: inline-flex; align-items: center; gap: 4px; }
.hint-sep { opacity: 0.5; margin: 0 2px; }
.section-desc { font-size: 13px; color: #64748b; margin: 0 0 14px; line-height: 1.45; }
.form-section-title {
  margin: 8px 0 10px; font-size: 12px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.04em; color: #64748b;
}
.profile-group + .profile-group { margin-top: 18px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.group-title {
  margin: 0 0 10px; font-size: 12px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.04em; color: #64748b;
}
.profile-grid { margin: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.profile-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 12px; }
.profile-item dt {
  margin: 0 0 4px; font-size: 11px; font-weight: 600; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.03em; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.field-badge {
  display: inline-flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
}
.field-badge.key { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.field-badge.free { background: #ecfdf5; color: #047857; border: 1px solid #bbf7d0; }
.field-badge.admin { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.profile-item dd { margin: 0; font-size: 14px; font-weight: 600; color: #0f172a; word-break: break-word; line-height: 1.35; }
.profile-item dd.empty { color: #94a3b8; font-weight: 500; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 8px; }
.form-group { margin-bottom: 14px; }
.form-group-full { grid-column: 1 / -1; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
.form-group select, .form-group input, .form-group textarea {
  width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px;
  font-size: 14px; background: #fff; color: #0f172a; font-family: inherit;
}
.form-group textarea { resize: vertical; min-height: 64px; }
.form-group select:focus, .form-group input:focus, .form-group textarea:focus {
  outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}
.form-group input:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.current-value {
  display: flex; flex-direction: column; gap: 2px; margin: -4px 0 14px; padding: 10px 12px;
  border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0;
}
.current-label { font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.03em; }
.current-text { font-size: 13px; font-weight: 600; color: #065f46; word-break: break-word; }
.error-msg { color: #dc2626; font-size: 13px; margin: 0 0 12px; font-weight: 500; }
.btn-primary {
  width: 100%; padding: 11px 16px; background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer;
}
.btn-primary:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-amber { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
.loading-wrap { padding: 24px; text-align: center; color: #64748b; font-size: 14px; }
.empty-state {
  padding: 28px 16px; text-align: center; color: #94a3b8; background: #f8fafc;
  border-radius: 12px; border: 1px dashed #e2e8f0;
}
.empty-state p { margin: 0; font-size: 13px; }
.requests-list { display: flex; flex-direction: column; gap: 10px; max-height: 480px; overflow: auto; }
.request-card {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; border-left: 4px solid #cbd5e1;
}
.request-pending { border-left-color: #f59e0b; background: #fffbeb; }
.request-approved { border-left-color: #10b981; background: #f0fdf4; }
.request-rejected { border-left-color: #ef4444; background: #fef2f2; }
.request-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 10px; }
.field-name { font-weight: 700; color: #0f172a; font-size: 14px; }
.status-badge { padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.status-pending { background: #fef3c7; color: #b45309; }
.status-approved { background: #d1fae5; color: #047857; }
.status-rejected { background: #fee2e2; color: #b91c1c; }
.request-values { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: start; margin-bottom: 8px; }
.value-label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; margin-bottom: 2px; }
.value-text { font-size: 13px; color: #334155; word-break: break-word; line-height: 1.35; }
.value-text.new { font-weight: 600; color: #0f172a; }
.value-arrow { color: #94a3b8; font-size: 14px; padding-top: 14px; }
.request-foot { font-size: 12px; color: #94a3b8; }
.rejection-box {
  margin-top: 10px; padding: 8px 10px; border-radius: 8px; background: #fff;
  border: 1px solid #fecaca; color: #b91c1c; font-size: 12px; line-height: 1.4;
}
@media (max-width: 960px) {
  .content-grid { grid-template-columns: 1fr; }
  .requests-list { max-height: none; }
}
@media (max-width: 640px) {
  .profile-grid, .form-grid { grid-template-columns: 1fr; }
  .panel { padding: 16px; }
  .request-values { grid-template-columns: 1fr; }
  .value-arrow { display: none; }
  .meta-grid { grid-template-columns: 1fr 1fr; }
}
</style>
