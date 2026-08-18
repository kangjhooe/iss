<template>
  <Layout>
    <div class="mutation-page">
      <div class="page-header">
        <div class="header-content">
          <div class="header-icon-wrap">
            <svg class="header-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 8L21 12L17 16M7 16L3 12L7 8M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div>
            <h1 class="page-title">Mutasi Guru</h1>
            <p class="page-subtitle">Ajukan mutasi guru ke sekolah lain, tarik guru dari sekolah lain, atau setujui/tolak permohonan mutasi</p>
          </div>
          <div class="header-actions">
            <button @click="showFormModal = true" class="btn-primary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 8L21 12L17 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Ajukan Mutasi</span>
            </button>
            <button @click="showPullModal = true" class="btn-secondary btn-compact">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7 16L3 12L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Tarik Guru</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Form: NPSN tujuan + NIK (sekolah asal mengajukan) -->
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Ajukan Mutasi Guru</h3>
            <button @click="showFormModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitMutation" class="modal-body">
            <p class="form-hint">Pilih sekolah tujuan (NPSN) atau catat mutasi ke sekolah yang belum terdaftar di aplikasi. Cari guru dengan NIK. Tidak ada batasan jenjang untuk mutasi guru (guru boleh lintas jenjang).</p>
            <div class="form-group form-group-checkbox">
              <label class="checkbox-label">
                <input v-model="form.external" type="checkbox" />
                <span>Sekolah tujuan belum terdaftar di aplikasi (input manual NPSN & nama)</span>
              </label>
            </div>
            <template v-if="form.external">
              <div class="form-group">
                <label>NPSN Sekolah Tujuan *</label>
                <input
                  v-model="form.target_npsn"
                  type="text"
                  placeholder="8 digit NPSN (contoh: 20234567)"
                  maxlength="8"
                  required
                  @input="form.target_npsn = form.target_npsn.replace(/\D/g, '').slice(0, 8)"
                />
              </div>
              <div class="form-group">
                <label>Nama Sekolah Tujuan *</label>
                <input
                  v-model="form.target_school_name"
                  type="text"
                  placeholder="Nama lengkap sekolah tujuan"
                  required
                />
              </div>
            </template>
            <template v-else>
              <div class="form-group">
                <label>NPSN Sekolah Tujuan *</label>
                <input
                  v-model="form.target_npsn"
                  type="text"
                  placeholder="Contoh: 20234567 (8 digit)"
                  maxlength="8"
                  required
                  @input="onNpsnInput"
                />
                <p v-if="targetInstitution" class="institution-preview">{{ targetInstitution.name }} ({{ targetInstitution.npsn }}) – {{ targetInstitution.level }}</p>
                <p v-else-if="form.target_npsn.length === 8 && !targetInstitution && npsnSearchDone" class="text-muted">Sekolah tidak ditemukan</p>
              </div>
            </template>
            <div class="form-group">
              <label>NIK Guru *</label>
              <div class="nisn-search-row">
                <input
                  v-model="form.nik"
                  type="text"
                  placeholder="16 digit NIK guru yang akan dimutasikan"
                  maxlength="16"
                  required
                  @input="onFormNikInput"
                  @keydown.enter.prevent="lookupFormTeacher"
                />
                <button
                  type="button"
                  class="btn-secondary btn-lookup"
                  :disabled="formLookupLoading || form.nik?.replace(/\D/g, '').length !== 16"
                  @click="lookupFormTeacher"
                >
                  {{ formLookupLoading ? 'Mencari...' : 'Cari Guru' }}
                </button>
              </div>
              <p v-if="formLookupError" class="text-error-inline">{{ formLookupError }}</p>
            </div>
            <div v-if="formTeacherPreview" class="student-preview-card">
              <div class="student-preview-title">Konfirmasi data guru</div>
              <div class="student-preview-grid">
                <div><span class="label">Nama</span><span class="value">{{ formTeacherPreview.name }}</span></div>
                <div><span class="label">NIK</span><span class="value">{{ formTeacherPreview.nik }}</span></div>
                <div><span class="label">NUPTK</span><span class="value">{{ formTeacherPreview.nuptk || '–' }}</span></div>
                <div><span class="label">NIP</span><span class="value">{{ formTeacherPreview.nip || '–' }}</span></div>
                <div><span class="label">JK</span><span class="value">{{ formatGender(formTeacherPreview.gender) }}</span></div>
                <div><span class="label">Status</span><span class="value">{{ formTeacherPreview.status || '–' }}</span></div>
                <div><span class="label">Email</span><span class="value">{{ formTeacherPreview.email || '–' }}</span></div>
              </div>
              <p class="student-preview-hint">Pastikan data benar sebelum mengajukan mutasi.</p>
            </div>
            <div class="form-group">
              <label>Catatan (opsional)</label>
              <textarea v-model="form.notes" rows="2" placeholder="Catatan untuk sekolah tujuan"></textarea>
            </div>
            <div v-if="formError" class="error-message">{{ formError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn-secondary">Batal</button>
              <button type="submit" :disabled="formSubmitting || !formTeacherPreview" class="btn-primary">
                {{ formSubmitting ? 'Mengirim...' : 'Ajukan Mutasi' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Form: Tarik guru (sekolah tujuan memulai) -->
      <div v-if="showPullModal" class="modal-overlay" @click="showPullModal = false">
        <div class="modal-content form-modal" @click.stop>
          <div class="modal-header">
            <h3>Tarik Guru dari Sekolah Lain</h3>
            <button @click="showPullModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="submitPull" class="modal-body">
            <p class="form-hint">Masukkan NPSN sekolah asal dan NIK guru, atau catat mutasi masuk dari sekolah yang belum terdaftar (input manual + data guru). Tidak ada batasan jenjang untuk mutasi guru.</p>
            <div class="form-group form-group-checkbox">
              <label class="checkbox-label">
                <input v-model="pullForm.external" type="checkbox" />
                <span>Sekolah asal belum terdaftar di aplikasi (input manual + data guru baru)</span>
              </label>
            </div>
            <template v-if="pullForm.external">
              <div class="form-group">
                <label>NPSN Sekolah Asal *</label>
                <input
                  v-model="pullForm.origin_npsn"
                  type="text"
                  placeholder="8 digit NPSN sekolah asal"
                  maxlength="8"
                  required
                  @input="pullForm.origin_npsn = pullForm.origin_npsn.replace(/\D/g, '').slice(0, 8)"
                />
              </div>
              <div class="form-group">
                <label>Nama Sekolah Asal *</label>
                <input
                  v-model="pullForm.origin_school_name"
                  type="text"
                  placeholder="Nama lengkap sekolah asal"
                  required
                />
              </div>
              <div class="form-group">
                <label>Nama Guru *</label>
                <input v-model="pullForm.employee_name" type="text" placeholder="Nama lengkap guru" required />
              </div>
              <div class="form-group">
                <label>NIK Guru *</label>
                <input
                  v-model="pullForm.nik"
                  type="text"
                  placeholder="16 digit NIK guru"
                  maxlength="16"
                  required
                  @input="pullForm.nik = pullForm.nik.replace(/\D/g, '').slice(0, 16)"
                />
              </div>
              <div class="form-group">
                <label>Jenis Kelamin *</label>
                <select v-model="pullForm.employee_gender" required>
                  <option value="">-- Pilih --</option>
                  <option value="L">Laki-laki</option>
                  <option value="P">Perempuan</option>
                </select>
              </div>
              <div class="form-group">
                <label>NUPTK (opsional)</label>
                <input v-model="pullForm.employee_nuptk" type="text" placeholder="NUPTK guru (jika ada)" />
              </div>
              <div class="form-group">
                <label>NIP (opsional)</label>
                <input v-model="pullForm.employee_nip" type="text" placeholder="NIP guru (jika ada)" />
              </div>
            </template>
            <template v-else>
              <div class="form-group">
                <label>NPSN Sekolah Asal *</label>
                <input
                  v-model="pullForm.origin_npsn"
                  type="text"
                  placeholder="8 digit NPSN sekolah asal"
                  maxlength="8"
                  required
                  @input="onOriginNpsnInput"
                />
                <p v-if="originInstitution" class="institution-preview">{{ originInstitution.name }} ({{ originInstitution.npsn }}) – {{ originInstitution.level }}</p>
                <p v-else-if="pullForm.origin_npsn.length === 8 && !originInstitution && originNpsnSearchDone" class="text-muted">Sekolah tidak ditemukan</p>
              </div>
              <div class="form-group">
                <label>NIK Guru *</label>
                <div class="nisn-search-row">
                  <input
                    v-model="pullForm.nik"
                    type="text"
                    placeholder="16 digit NIK guru di sekolah asal"
                    maxlength="16"
                    required
                    @input="onPullNikInput"
                    @keydown.enter.prevent="lookupPullTeacher"
                  />
                  <button
                    type="button"
                    class="btn-secondary btn-lookup"
                    :disabled="pullLookupLoading || pullForm.nik?.replace(/\D/g, '').length !== 16 || pullForm.origin_npsn.length !== 8"
                    @click="lookupPullTeacher"
                  >
                    {{ pullLookupLoading ? 'Mencari...' : 'Cari Guru' }}
                  </button>
                </div>
                <p v-if="pullLookupError" class="text-error-inline">{{ pullLookupError }}</p>
              </div>
              <div v-if="pullTeacherPreview" class="student-preview-card">
                <div class="student-preview-title">Konfirmasi data guru</div>
                <div class="student-preview-grid">
                  <div><span class="label">Nama</span><span class="value">{{ pullTeacherPreview.name }}</span></div>
                  <div><span class="label">NIK</span><span class="value">{{ pullTeacherPreview.nik }}</span></div>
                  <div><span class="label">NUPTK</span><span class="value">{{ pullTeacherPreview.nuptk || '–' }}</span></div>
                  <div><span class="label">NIP</span><span class="value">{{ pullTeacherPreview.nip || '–' }}</span></div>
                  <div><span class="label">JK</span><span class="value">{{ formatGender(pullTeacherPreview.gender) }}</span></div>
                  <div><span class="label">Status</span><span class="value">{{ pullTeacherPreview.status || '–' }}</span></div>
                  <div v-if="pullTeacherPreview.institution" class="span-2">
                    <span class="label">Sekolah asal</span>
                    <span class="value">{{ pullTeacherPreview.institution.name }} ({{ pullTeacherPreview.institution.npsn }})</span>
                  </div>
                </div>
                <p class="student-preview-hint">Pastikan data benar sebelum menarik guru.</p>
              </div>
            </template>
            <div class="form-group">
              <label>Catatan (opsional)</label>
              <textarea v-model="pullForm.notes" rows="2" placeholder="Catatan"></textarea>
            </div>
            <div v-if="pullFormError" class="error-message">{{ pullFormError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showPullModal = false" class="btn-secondary">Batal</button>
              <button
                type="submit"
                :disabled="pullFormSubmitting || (!pullForm.external && !pullTeacherPreview)"
                class="btn-primary"
              >
                {{ pullFormSubmitting ? 'Mengirim...' : 'Ajukan Tarik Guru' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Main tabs: Permohonan | Laporan | Riwayat per guru -->
      <div class="main-tabs">
        <button :class="['main-tab', { active: activeTabMain === 'permohonan' }]" @click="activeTabMain = 'permohonan'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Permohonan</span>
        </button>
        <button :class="['main-tab', { active: activeTabMain === 'laporan' }]" @click="activeTabMain = 'laporan'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 17V7M15 17V12M21 21H3V3H21V21ZM5 19H19V5H5V19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Laporan</span>
        </button>
        <button :class="['main-tab', { active: activeTabMain === 'riwayat' }]" @click="activeTabMain = 'riwayat'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Riwayat per Guru</span>
        </button>
      </div>

      <!-- Section: Permohonan -->
      <template v-if="activeTabMain === 'permohonan'">
      <!-- Filter: Status & Role -->
      <div class="filter-bar">
        <div class="filter-group">
          <span class="filter-group-label">Status</span>
          <div class="filter-tabs">
            <button
              v-for="s in statusOptions"
              :key="'status-' + s.value"
              @click="filterStatus = s.value"
              :class="['tab', { active: filterStatus === s.value }]"
            >
              {{ s.label }}
            </button>
          </div>
        </div>
        <div class="filter-group">
          <span class="filter-group-label">Tampilkan</span>
          <div class="filter-tabs">
            <button
              v-for="r in roleOptions"
              :key="'role-' + r.value"
              @click="filterRole = r.value"
              :class="['tab', 'tab-role', { active: filterRole === r.value }]"
            >
              {{ r.label }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LoadingSkeleton type="table" :rows="6" :columns="5" :cell-widths="['100px', '1fr', '1fr', '100px', '120px']" />
      </div>

      <div v-else-if="mutations.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M17 8L21 12L17 16M7 16L3 12L7 8M3 12H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="empty-title">Belum ada permohonan mutasi</h3>
        <p class="empty-desc">Mulai dengan mengajukan mutasi guru ke sekolah lain atau menarik guru dari sekolah asal.</p>
        <div class="empty-actions">
          <button @click="showFormModal = true" class="btn-primary btn-empty-cta">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 8L21 12L17 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Ajukan Mutasi
          </button>
          <button @click="showPullModal = true" class="btn-secondary btn-empty-cta">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 16L3 12L7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Tarik Guru
          </button>
        </div>
      </div>

      <div v-else class="mutations-list">
        <div v-for="m in mutations" :key="m.id" class="mutation-card">
          <div class="card-header">
            <span :class="['status-badge', `status-${m.status}`]">{{ getStatusLabel(m.status) }}</span>
            <span class="initiated-label">{{ m.initiated_by === 'origin' ? 'Sekolah asal mengajukan' : 'Sekolah tujuan menarik' }}</span>
          </div>
          <div class="card-flow">
            <span class="flow-origin">{{ m.origin_institution?.name ?? m.origin_school_name ?? '–' }}</span>
            <svg class="flow-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="flow-target">{{ m.target_institution?.name ?? m.target_school_name ?? '–' }}</span>
            <span v-if="m.is_external_target" class="badge-external">Sekolah luar sistem</span>
            <span v-if="m.is_external_origin" class="badge-external">Masuk dari luar sistem</span>
          </div>
          <div class="card-body">
            <div class="detail-row detail-highlight">
              <span class="label">Guru</span>
              <span class="value">{{ m.employee?.name }} <span class="value-muted">(NIK: {{ m.employee?.nik || '–' }})</span></span>
            </div>
            <div class="detail-row">
              <span class="label">Sekolah asal</span>
              <span class="value">{{ m.origin_institution?.name ?? m.origin_school_name ?? '–' }} <span class="value-muted">({{ m.origin_institution?.npsn ?? m.origin_npsn ?? '–' }})</span></span>
            </div>
            <div class="detail-row">
              <span class="label">Sekolah tujuan</span>
              <span class="value">{{ m.target_institution?.name ?? m.target_school_name ?? '–' }} <span class="value-muted">({{ m.target_institution?.npsn ?? m.target_npsn ?? '–' }})</span></span>
            </div>
            <div class="detail-row">
              <span class="label">Diajukan oleh</span>
              <span class="value">{{ m.requester?.name }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Tanggal</span>
              <span class="value">{{ formatDate(m.created_at) }}</span>
            </div>
            <div v-if="m.rejection_reason" class="detail-row rejection-row">
              <span class="label">Alasan penolakan</span>
              <span class="value rejection-reason">{{ m.rejection_reason }}</span>
            </div>
            <div v-if="m.cancel_reason" class="detail-row">
              <span class="label">Alasan pembatalan</span>
              <span class="value">{{ m.cancel_reason }}</span>
            </div>
            <div v-if="m.cancel_rejection_reason" class="detail-row rejection-row">
              <span class="label">Penolakan batal</span>
              <span class="value rejection-reason">{{ m.cancel_rejection_reason }}</span>
            </div>
          </div>
          <div v-if="showMutationActions(m)" class="card-actions">
            <template v-if="m.status === 'pending' && canApprove(m)">
              <button @click="openApproveModal(m)" class="btn-approve">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Setujui
              </button>
              <button @click="openRejectModal(m)" class="btn-reject">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tolak
              </button>
            </template>
            <template v-if="m.status === 'cancel_pending' && (m.can_decide_cancel || canDecideCancel(m))">
              <button @click="openApproveCancelModal(m)" class="btn-approve">
                Setujui Batal
              </button>
              <button @click="openRejectCancelModal(m)" class="btn-reject">
                Tolak Batal
              </button>
            </template>
            <button
              v-if="m.can_request_cancel || canRequestCancel(m)"
              @click="openCancelModal(m)"
              class="btn-cancel-mutation"
            >
              Batal Mutasi
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination (permohonan) -->
      <div v-if="activeTabMain === 'permohonan' && pagination.last_page > 1 && mutations.length > 0" class="pagination-bar">
        <span class="pagination-info">
          Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }}-{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }}
        </span>
        <div class="pagination-buttons">
          <button
            type="button"
            class="btn-page"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            Sebelumnya
          </button>
          <span class="page-num">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            type="button"
            class="btn-page"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
      </template>

      <!-- Section: Laporan -->
      <template v-if="activeTabMain === 'laporan'">
        <div class="report-section">
          <form @submit.prevent="loadReport(1)" class="report-form card-form">
            <div class="report-form-top">
              <div>
                <h3 class="report-panel-title">Buku Mutasi Guru</h3>
                <p class="report-panel-desc">Filter periode, lihat ringkasan, lalu preview PDF atau unduh CSV.</p>
              </div>
              <div class="report-export-actions">
                <button type="button" @click="exportBukuMutasi('pdf')" :disabled="exportingBukuMutasi || reportLoading" class="btn-export-pdf" title="Preview Buku Mutasi Guru (PDF)">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 9V2H18V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 14H6V22H18V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>{{ exportingBukuMutasi ? 'Menyiapkan...' : 'Preview PDF' }}</span>
                </button>
                <button type="button" @click="exportBukuMutasi('csv')" :disabled="exportingBukuMutasi || reportLoading" class="btn-export-csv" title="Export Buku Mutasi Guru (CSV)">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 13H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 17H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>Export CSV</span>
                </button>
              </div>
            </div>

            <div class="form-row report-filters">
              <div class="form-group">
                <label>Dari tanggal</label>
                <input v-model="reportFrom" type="date" />
              </div>
              <div class="form-group">
                <label>Sampai tanggal</label>
                <input v-model="reportTo" type="date" />
              </div>
              <div class="form-group form-group-type">
                <label>Jenis mutasi</label>
                <div class="filter-tabs report-type-tabs">
                  <button
                    v-for="t in reportTypeOptions"
                    :key="'rtype-' + t.value"
                    type="button"
                    :class="['tab', 'tab-role', { active: reportType === t.value }]"
                    @click="reportType = t.value"
                  >
                    {{ t.label }}
                  </button>
                </div>
              </div>
              <button type="submit" class="btn-primary btn-search" :disabled="reportLoading">
                <svg v-if="!reportLoading" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 17V7M15 17V12M21 21H3V3H21V21ZM5 19H19V5H5V19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span v-else class="mini-spinner"></span>
                {{ reportLoading ? 'Memuat...' : 'Tampilkan' }}
              </button>
            </div>
          </form>

          <div v-if="reportSummary" class="report-summary-cards">
            <div class="stat-card stat-out">
              <div class="stat-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 8L21 12L17 16M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <span class="stat-value">{{ reportSummary.mutasi_keluar }}</span>
                <span class="stat-label">Mutasi keluar</span>
              </div>
            </div>
            <div class="stat-card stat-in">
              <div class="stat-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M7 16L3 12L7 8M21 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <span class="stat-value">{{ reportSummary.mutasi_masuk }}</span>
                <span class="stat-label">Mutasi masuk</span>
              </div>
            </div>
            <div class="stat-card stat-total">
              <div class="stat-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <span class="stat-value">{{ reportSummaryTotal }}</span>
                <span class="stat-label">Total periode</span>
              </div>
            </div>
          </div>

          <div v-if="reportLoaded && reportPeriodLabel" class="report-toolbar">
            <span class="report-period">{{ reportPeriodLabel }}</span>
            <span class="report-count">{{ reportPagination.total }} catatan</span>
          </div>

          <div v-if="reportLoading" class="loading-wrap">
            <LoadingSkeleton type="table" :rows="6" :columns="8" :cell-widths="['48px', '100px', '100px', '1fr', '72px', '90px', '1fr', '1fr']" />
          </div>

          <template v-else-if="reportData.length > 0">
            <div class="table-container report-table-desktop">
              <table class="data-table report-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th>Tanggal</th>
                    <th>NIK</th>
                    <th>NUPTK</th>
                    <th>NIP</th>
                    <th>Nama Guru</th>
                    <th class="col-jk">JK</th>
                    <th>Jenis</th>
                    <th>Sekolah Asal</th>
                    <th>Sekolah Tujuan</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(m, idx) in reportData" :key="m.id">
                    <td class="col-no">{{ reportRowNumber(idx) }}</td>
                    <td class="col-date">{{ formatDateShort(m.approved_at || m.created_at) }}</td>
                    <td>{{ m.employee?.nik || '–' }}</td>
                    <td>{{ m.employee?.nuptk || m.employee_nuptk || '–' }}</td>
                    <td>{{ m.employee?.nip || m.employee_nip || '–' }}</td>
                    <td class="col-name">{{ m.employee?.name || '–' }}</td>
                    <td class="col-jk">{{ m.employee_gender || m.employee?.gender || '–' }}</td>
                    <td>
                      <span :class="['jenis-badge', isMutationOut(m) ? 'jenis-out' : 'jenis-in']">
                        {{ isMutationOut(m) ? 'Keluar' : 'Masuk' }}
                      </span>
                    </td>
                    <td>
                      <div class="school-cell">
                        <span>{{ schoolOriginName(m) }}</span>
                        <span class="school-npsn">{{ schoolOriginNpsn(m) }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="school-cell">
                        <span>{{ schoolTargetName(m) }}</span>
                        <span class="school-npsn">{{ schoolTargetNpsn(m) }}</span>
                      </div>
                    </td>
                    <td class="col-notes">{{ m.notes || '–' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mutations-list report-cards-mobile">
              <div v-for="m in reportData" :key="'m-' + m.id" class="mutation-card">
                <div class="card-header">
                  <span :class="['jenis-badge', isMutationOut(m) ? 'jenis-out' : 'jenis-in']">
                    {{ isMutationOut(m) ? 'Keluar' : 'Masuk' }}
                  </span>
                  <span class="card-date">{{ formatDateShort(m.approved_at || m.created_at) }}</span>
                  <span v-if="m.is_external_target" class="badge-external">Luar sistem</span>
                  <span v-if="m.is_external_origin" class="badge-external">Masuk dari luar</span>
                </div>
                <div class="card-body">
                  <div class="detail-row">
                    <span class="label">Guru:</span>
                    <span class="value">{{ m.employee?.name || '–' }} <span class="value-muted">(NIK: {{ m.employee?.nik || '–' }})</span></span>
                  </div>
                  <div class="detail-row" v-if="m.employee?.nip || m.employee_nip || m.employee_gender || m.employee?.gender">
                    <span class="label">NIP / JK:</span>
                    <span class="value">{{ m.employee?.nip || m.employee_nip || '–' }} / {{ m.employee_gender || m.employee?.gender || '–' }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Asal:</span>
                    <span class="value">{{ schoolOriginName(m) }} <span class="value-muted">({{ schoolOriginNpsn(m) }})</span></span>
                  </div>
                  <div class="detail-row">
                    <span class="label">Tujuan:</span>
                    <span class="value">{{ schoolTargetName(m) }} <span class="value-muted">({{ schoolTargetNpsn(m) }})</span></span>
                  </div>
                  <div v-if="m.notes" class="detail-row">
                    <span class="label">Keterangan:</span>
                    <span class="value">{{ m.notes }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="reportPagination.last_page > 1" class="pagination-bar">
              <div class="pagination-info">
                Halaman {{ reportPagination.current_page }} dari {{ reportPagination.last_page }}
                ({{ reportPagination.total }} data)
              </div>
              <div class="pagination-buttons">
                <button
                  type="button"
                  class="btn-page"
                  :disabled="reportPagination.current_page <= 1 || reportLoading"
                  @click="loadReport(reportPagination.current_page - 1)"
                >
                  Sebelumnya
                </button>
                <button
                  type="button"
                  class="btn-page"
                  :disabled="reportPagination.current_page >= reportPagination.last_page || reportLoading"
                  @click="loadReport(reportPagination.current_page + 1)"
                >
                  Berikutnya
                </button>
              </div>
            </div>
          </template>

          <div v-else-if="reportLoaded" class="empty-state report-empty">
            <div class="empty-icon">
              <svg width="72" height="72" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 17V7M15 17V12M21 21H3V3H21V21ZM5 19H19V5H5V19Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="empty-title">Tidak ada data mutasi</h3>
            <p class="empty-desc">Tidak ada mutasi disetujui pada periode atau filter yang dipilih. Sesuaikan tanggal/jenis lalu tampilkan lagi.</p>
          </div>

          <div v-else class="empty-state report-empty">
            <div class="empty-icon">
              <svg width="72" height="72" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 2V8H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="empty-title">Siap menampilkan laporan</h3>
            <p class="empty-desc">Pilih periode (opsional) dan jenis mutasi, lalu klik Tampilkan untuk melihat Buku Mutasi Guru sekolah Anda.</p>
          </div>
        </div>
      </template>

      <!-- Section: Riwayat per guru -->
      <template v-if="activeTabMain === 'riwayat'">
        <div class="history-section">
          <form @submit.prevent="loadHistoryByNik" class="history-form card-form">
            <div class="form-row history-search-row">
              <div class="form-group form-group-flex">
                <label>NIK Guru</label>
                <input
                  v-model="historyNik"
                  type="text"
                  placeholder="Masukkan 16 digit NIK guru..."
                  maxlength="16"
                  required
                  class="input-with-icon"
                  @input="historyNik = historyNik.replace(/\D/g, '').slice(0, 16)"
                />
              </div>
              <button type="submit" class="btn-primary btn-search" :disabled="historyLoading">
                <svg v-if="!historyLoading" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span v-else class="mini-spinner"></span>
                {{ historyLoading ? 'Mencari...' : 'Cari Riwayat' }}
              </button>
            </div>
          </form>
          <div v-if="historyError" class="error-message">{{ historyError }}</div>
          <div v-if="historyLoading" class="loading-state"><p>Memuat riwayat...</p></div>
          <div v-else-if="historyList.length > 0" class="mutations-list">
            <div v-for="m in historyList" :key="m.id" class="mutation-card">
              <div class="card-header">
                <span :class="['status-badge', `status-${m.status}`]">{{ getStatusLabel(m.status) }}</span>
                <span class="initiated-label">{{ m.origin_institution?.name ?? m.origin_school_name ?? '–' }} → {{ m.target_institution?.name ?? m.target_school_name ?? '–' }}</span>
                <span v-if="m.is_external_target" class="badge-external">Luar sistem</span>
                <span v-if="m.is_external_origin" class="badge-external">Masuk dari luar</span>
              </div>
              <div class="card-body">
                <div class="detail-row"><span class="label">Guru:</span> <span class="value">{{ m.employee?.name }} (NIK: {{ m.employee?.nik || '–' }})</span></div>
                <div class="detail-row" v-if="m.employee?.nip || m.employee_gender">
                  <span class="label">NIP / JK:</span>
                  <span class="value">{{ m.employee?.nip ?? '-' }} / {{ m.employee_gender ?? m.employee?.gender ?? '-' }}</span>
                </div>
                <div class="detail-row"><span class="label">Tanggal:</span> <span class="value">{{ formatDate(m.created_at) }}</span></div>
                <div v-if="m.approved_at" class="detail-row"><span class="label">Disetujui/Ditolak:</span> <span class="value">{{ formatDate(m.approved_at) }}</span></div>
              </div>
            </div>
          </div>
          <p v-else-if="historyLoaded && historyList.length === 0" class="text-muted">Tidak ada riwayat mutasi untuk NIK ini.</p>
        </div>
      </template>

      <!-- Approve Modal -->
      <div v-if="showApproveModal" class="modal-overlay" @click="showApproveModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Setujui Mutasi</h3>
            <button @click="showApproveModal = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <p>Data guru akan dipindahkan ke sekolah tujuan. Lanjutkan?</p>
            <div v-if="selectedMutation" class="approval-details">
              <div class="detail-row">
                <span class="label">Guru:</span>
                <span class="value">{{ selectedMutation.employee?.name }} (NIK: {{ selectedMutation.employee?.nik || '–' }})</span>
              </div>
              <div class="detail-row">
                <span class="label">Sekolah tujuan:</span>
                <span class="value">{{ selectedMutation.target_institution?.name }}</span>
              </div>
            </div>
            <div v-if="approveError" class="error-message">{{ approveError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showApproveModal = false" class="btn-secondary">Batal</button>
              <button @click="handleApprove" :disabled="processing" class="btn-approve">
                {{ processing ? 'Memproses...' : 'Setujui' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div v-if="showRejectModal" class="modal-overlay" @click="showRejectModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tolak Mutasi</h3>
            <button @click="showRejectModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="handleReject" class="modal-body">
            <div class="form-group">
              <label>Alasan penolakan *</label>
              <textarea v-model="rejectionReason" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
            </div>
            <div v-if="rejectError" class="error-message">{{ rejectError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showRejectModal = false" class="btn-secondary">Tutup</button>
              <button type="submit" :disabled="processing" class="btn-reject">
                {{ processing ? 'Memproses...' : 'Tolak' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Cancel Mutation Modal -->
      <div v-if="showCancelModal" class="modal-overlay" @click="showCancelModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Batal Mutasi</h3>
            <button @click="showCancelModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="handleCancel" class="modal-body">
            <p v-if="selectedMutation?.status === 'approved' && !selectedMutation?.is_external_target && !selectedMutation?.is_external_origin">
              Mutasi sudah diterima sekolah tujuan. Pembatalan akan dikirim untuk <strong>persetujuan admin sekolah tujuan</strong>.
            </p>
            <p v-else>
              Permohonan mutasi akan dibatalkan. Lanjutkan?
            </p>
            <div v-if="selectedMutation" class="approval-details">
              <div class="detail-row">
                <span class="label">Guru:</span>
                <span class="value">{{ selectedMutation.employee?.name }} (NIK: {{ selectedMutation.employee?.nik || '–' }})</span>
              </div>
            </div>
            <div class="form-group">
              <label>Alasan pembatalan (opsional)</label>
              <textarea v-model="cancelReason" rows="3" placeholder="Alasan membatalkan mutasi..."></textarea>
            </div>
            <div v-if="cancelError" class="error-message">{{ cancelError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showCancelModal = false" class="btn-secondary">Tutup</button>
              <button type="submit" :disabled="processing" class="btn-cancel-mutation">
                {{ processing ? 'Memproses...' : 'Ya, Batalkan' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Approve Cancel Modal -->
      <div v-if="showApproveCancelModal" class="modal-overlay" @click="showApproveCancelModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Setujui Pembatalan Mutasi</h3>
            <button @click="showApproveCancelModal = false" class="btn-close">×</button>
          </div>
          <div class="modal-body">
            <p>Guru akan dikembalikan ke sekolah asal. Lanjutkan?</p>
            <div v-if="selectedMutation" class="approval-details">
              <div class="detail-row">
                <span class="label">Guru:</span>
                <span class="value">{{ selectedMutation.employee?.name }} (NIK: {{ selectedMutation.employee?.nik || '–' }})</span>
              </div>
              <div class="detail-row" v-if="selectedMutation.cancel_reason">
                <span class="label">Alasan batal:</span>
                <span class="value">{{ selectedMutation.cancel_reason }}</span>
              </div>
            </div>
            <div v-if="approveCancelError" class="error-message">{{ approveCancelError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showApproveCancelModal = false" class="btn-secondary">Tutup</button>
              <button @click="handleApproveCancel" :disabled="processing" class="btn-approve">
                {{ processing ? 'Memproses...' : 'Setujui Batal' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reject Cancel Modal -->
      <div v-if="showRejectCancelModal" class="modal-overlay" @click="showRejectCancelModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Tolak Pembatalan Mutasi</h3>
            <button @click="showRejectCancelModal = false" class="btn-close">×</button>
          </div>
          <form @submit.prevent="handleRejectCancel" class="modal-body">
            <div class="form-group">
              <label>Alasan penolakan *</label>
              <textarea v-model="cancelRejectionReason" rows="3" required placeholder="Mengapa pembatalan ditolak..."></textarea>
            </div>
            <div v-if="rejectCancelError" class="error-message">{{ rejectCancelError }}</div>
            <div class="modal-footer">
              <button type="button" @click="showRejectCancelModal = false" class="btn-secondary">Tutup</button>
              <button type="submit" :disabled="processing" class="btn-reject">
                {{ processing ? 'Memproses...' : 'Tolak Batal' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Layout from '@/components/Layout.vue'
import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
import { teacherMutationApi } from '@/api/teacherMutation'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(true)
const mutations = ref([])
const filterStatus = ref('pending')
const showFormModal = ref(false)
const form = ref({
  external: false,
  target_npsn: '',
  target_school_name: '',
  nik: '',
  notes: ''
})
const targetInstitution = ref(null)
const npsnSearchDone = ref(false)
const formSubmitting = ref(false)
const formError = ref('')
const formTeacherPreview = ref(null)
const formLookupLoading = ref(false)
const formLookupError = ref('')

const showPullModal = ref(false)
const pullForm = ref({
  external: false,
  origin_npsn: '',
  origin_school_name: '',
  nik: '',
  employee_name: '',
  employee_gender: '',
  employee_nip: '',
  employee_nuptk: '',
  notes: ''
})
const originInstitution = ref(null)
const originNpsnSearchDone = ref(false)
const pullFormSubmitting = ref(false)
const pullFormError = ref('')
const pullTeacherPreview = ref(null)
const pullLookupLoading = ref(false)
const pullLookupError = ref('')

const statusOptions = [
  { value: 'pending', label: 'Menunggu' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'cancel_pending', label: 'Menunggu batal' },
  { value: 'cancelled', label: 'Dibatalkan' },
  { value: 'rejected', label: 'Ditolak' },
  { value: '', label: 'Semua' }
]

const activeTabMain = ref('permohonan')

const filterRole = ref('')
const roleOptions = [
  { value: '', label: 'Semua' },
  { value: 'as_origin', label: 'Saya mengajukan' },
  { value: 'as_target', label: 'Perlu saya setujui' }
]

const reportFrom = ref('')
const reportTo = ref('')
const reportType = ref('all')
const reportTypeOptions = [
  { value: 'all', label: 'Semua' },
  { value: 'out', label: 'Keluar' },
  { value: 'in', label: 'Masuk' }
]
const reportLoading = ref(false)
const reportLoaded = ref(false)
const reportSummary = ref(null)
const reportData = ref([])
const reportPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15
})
const exportingBukuMutasi = ref(false)

const reportSummaryTotal = computed(() => {
  if (!reportSummary.value) return 0
  return (reportSummary.value.mutasi_keluar || 0) + (reportSummary.value.mutasi_masuk || 0)
})

const reportPeriodLabel = computed(() => {
  if (!reportLoaded.value) return ''
  const from = reportFrom.value
  const to = reportTo.value
  const typeLabel = reportTypeOptions.find((t) => t.value === reportType.value)?.label || 'Semua'
  if (from && to) return `Periode ${formatDateShort(from)} – ${formatDateShort(to)} · ${typeLabel}`
  if (from) return `Dari ${formatDateShort(from)} · ${typeLabel}`
  if (to) return `Sampai ${formatDateShort(to)} · ${typeLabel}`
  return `Semua periode · ${typeLabel}`
})

const historyNik = ref('')
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyList = ref([])
const historyError = ref('')

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15
})

const showApproveModal = ref(false)
const showRejectModal = ref(false)
const showCancelModal = ref(false)
const showApproveCancelModal = ref(false)
const showRejectCancelModal = ref(false)
const selectedMutation = ref(null)
const rejectionReason = ref('')
const cancelReason = ref('')
const cancelRejectionReason = ref('')
const processing = ref(false)
const approveError = ref('')
const rejectError = ref('')
const cancelError = ref('')
const approveCancelError = ref('')
const rejectCancelError = ref('')

const myInstitutionId = computed(() => {
  const raw = authStore.activeInstitutionId || authStore.user?.institution_id
  const id = Number(raw)
  return Number.isFinite(id) && id > 0 ? id : null
})
const isInstAdmin = computed(() => {
  const role = authStore.user?.role
  return role === 'admin' || role === 'institution_admin'
})

function sameInstitution(a, b) {
  const left = Number(a)
  const right = Number(b)
  return Number.isFinite(left) && Number.isFinite(right) && left > 0 && left === right
}

function canApprove(m) {
  if (!m || m.status !== 'pending') return false
  if (m.can_approve === true || m.can_reject === true) return true
  if (!isInstAdmin.value || !myInstitutionId.value) return false
  if (m.initiated_by === 'origin') {
    return sameInstitution(m.target_institution_id, myInstitutionId.value)
  }
  return sameInstitution(m.origin_institution_id, myInstitutionId.value)
}

function canRequestCancel(m) {
  if (!m || !['pending', 'approved'].includes(m.status)) return false
  if (m.can_request_cancel === true) return true
  if (!myInstitutionId.value) return false
  const isRequester = m.requester?.id === authStore.user?.id || m.requested_by === authStore.user?.id
  if (m.status === 'pending') {
    if (isRequester) return true
    if (!isInstAdmin.value) return false
    if (m.initiated_by === 'origin') return sameInstitution(m.origin_institution_id, myInstitutionId.value)
    return sameInstitution(m.target_institution_id, myInstitutionId.value)
  }
  if (!isInstAdmin.value) return false
  if (m.is_external_target) return sameInstitution(m.origin_institution_id, myInstitutionId.value)
  if (m.is_external_origin) return sameInstitution(m.target_institution_id, myInstitutionId.value)
  return sameInstitution(m.origin_institution_id, myInstitutionId.value)
}

function canDecideCancel(m) {
  if (!m || m.status !== 'cancel_pending') return false
  if (m.can_decide_cancel === true) return true
  if (!isInstAdmin.value || !myInstitutionId.value) return false
  return sameInstitution(m.target_institution_id, myInstitutionId.value)
}

function showMutationActions(m) {
  return (m.status === 'pending' && canApprove(m))
    || (m.status === 'cancel_pending' && canDecideCancel(m))
    || canRequestCancel(m)
}

async function loadTargetByNpsn() {
  const npsn = form.value.target_npsn?.trim()
  if (npsn.length !== 8) {
    targetInstitution.value = null
    npsnSearchDone.value = npsn.length === 8
    return
  }
  npsnSearchDone.value = false
  try {
    const res = await teacherMutationApi.searchTargetInstitutions(npsn)
    const list = res.data?.data || []
    targetInstitution.value = list.find(i => String(i.npsn) === String(npsn)) || list[0] || null
    npsnSearchDone.value = true
  } catch {
    targetInstitution.value = null
    npsnSearchDone.value = true
  }
}

function onNpsnInput() {
  form.value.target_npsn = form.value.target_npsn.replace(/\D/g, '').slice(0, 8)
  targetInstitution.value = null
  if (form.value.target_npsn.length === 8) {
    loadTargetByNpsn()
  } else {
    npsnSearchDone.value = false
  }
}

function onFormNikInput() {
  form.value.nik = form.value.nik.replace(/\D/g, '').slice(0, 16)
  formTeacherPreview.value = null
  formLookupError.value = ''
}

async function lookupFormTeacher() {
  formLookupError.value = ''
  formTeacherPreview.value = null
  const nik = form.value.nik?.replace(/\D/g, '') || ''
  if (nik.length !== 16) {
    formLookupError.value = 'Masukkan NIK 16 digit terlebih dahulu.'
    return
  }
  formLookupLoading.value = true
  try {
    const res = await teacherMutationApi.lookupTeacher(nik)
    formTeacherPreview.value = res.data?.data ?? null
    if (!formTeacherPreview.value) {
      formLookupError.value = 'Data guru tidak ditemukan.'
    }
  } catch (err) {
    formLookupError.value = err.response?.data?.message || err.formattedMessage || 'Gagal mencari guru'
  } finally {
    formLookupLoading.value = false
  }
}

async function loadOriginByNpsn() {
  const npsn = pullForm.value.origin_npsn?.trim()
  if (npsn.length !== 8) {
    originInstitution.value = null
    originNpsnSearchDone.value = npsn.length === 8
    return
  }
  originNpsnSearchDone.value = false
  try {
    const res = await teacherMutationApi.searchOriginInstitutions(npsn)
    const list = res.data?.data || []
    originInstitution.value = list.find(i => String(i.npsn) === String(npsn)) || list[0] || null
    originNpsnSearchDone.value = true
  } catch {
    originInstitution.value = null
    originNpsnSearchDone.value = true
  }
}

function onOriginNpsnInput() {
  pullForm.value.origin_npsn = pullForm.value.origin_npsn.replace(/\D/g, '').slice(0, 8)
  originInstitution.value = null
  pullTeacherPreview.value = null
  pullLookupError.value = ''
  if (pullForm.value.origin_npsn.length === 8) {
    loadOriginByNpsn()
  } else {
    originNpsnSearchDone.value = false
  }
}

function onPullNikInput() {
  pullForm.value.nik = pullForm.value.nik.replace(/\D/g, '').slice(0, 16)
  pullTeacherPreview.value = null
  pullLookupError.value = ''
}

async function lookupPullTeacher() {
  pullLookupError.value = ''
  pullTeacherPreview.value = null
  const npsn = pullForm.value.origin_npsn?.trim()
  const nik = pullForm.value.nik?.replace(/\D/g, '') || ''
  if (!npsn || npsn.length !== 8) {
    pullLookupError.value = 'NPSN sekolah asal harus 8 digit terlebih dahulu.'
    return
  }
  if (nik.length !== 16) {
    pullLookupError.value = 'Masukkan NIK 16 digit terlebih dahulu.'
    return
  }
  pullLookupLoading.value = true
  try {
    const res = await teacherMutationApi.lookupTeacherAtOrigin(npsn, nik)
    pullTeacherPreview.value = res.data?.data ?? null
    if (!pullTeacherPreview.value) {
      pullLookupError.value = 'Data guru tidak ditemukan.'
    }
  } catch (err) {
    pullLookupError.value = err.response?.data?.message || err.formattedMessage || 'Gagal mencari guru'
  } finally {
    pullLookupLoading.value = false
  }
}

function formatGender(gender) {
  if (!gender) return '–'
  if (/^(L|l|Laki|Male)/i.test(String(gender))) return 'Laki-laki'
  if (/^(P|p|Perem|Female)/i.test(String(gender))) return 'Perempuan'
  return String(gender)
}

function resetFormModal() {
  form.value = { external: false, target_npsn: '', target_school_name: '', nik: '', notes: '' }
  targetInstitution.value = null
  formTeacherPreview.value = null
  formLookupError.value = ''
  formError.value = ''
  npsnSearchDone.value = false
}

function resetPullModal() {
  pullForm.value = { external: false, origin_npsn: '', origin_school_name: '', nik: '', employee_name: '', employee_gender: '', employee_nip: '', employee_nuptk: '', notes: '' }
  originInstitution.value = null
  pullTeacherPreview.value = null
  pullLookupError.value = ''
  pullFormError.value = ''
  originNpsnSearchDone.value = false
}

async function submitPull() {
  pullFormError.value = ''
  if (!pullForm.value.origin_npsn || pullForm.value.origin_npsn.length !== 8) {
    pullFormError.value = 'NPSN sekolah asal harus 8 digit.'
    return
  }
  if (!pullForm.value.nik?.replace(/\D/g, '') || pullForm.value.nik.replace(/\D/g, '').length !== 16) {
    pullFormError.value = 'NIK guru wajib diisi (16 digit).'
    return
  }
  if (pullForm.value.external) {
    if (!pullForm.value.origin_school_name?.trim()) {
      pullFormError.value = 'Nama sekolah asal wajib diisi untuk mutasi masuk dari sekolah luar.'
      return
    }
    if (!pullForm.value.employee_name?.trim()) {
      pullFormError.value = 'Nama guru wajib diisi.'
      return
    }
    if (!pullForm.value.employee_gender) {
      pullFormError.value = 'Jenis kelamin guru wajib diisi.'
      return
    }
  } else if (!pullTeacherPreview.value) {
    pullFormError.value = 'Cari dan konfirmasi data guru terlebih dahulu sebelum mengajukan.'
    return
  }
  pullFormSubmitting.value = true
  try {
    const payload = {
      origin_npsn: pullForm.value.origin_npsn,
      nik: pullForm.value.nik.replace(/\D/g, ''),
      notes: pullForm.value.notes?.trim() || undefined
    }
    if (pullForm.value.external) {
      payload.external = true
      payload.origin_school_name = pullForm.value.origin_school_name?.trim()
      payload.employee_name = pullForm.value.employee_name?.trim()
      payload.employee_gender = pullForm.value.employee_gender
      payload.employee_nip = pullForm.value.employee_nip?.trim() || undefined
      payload.employee_nuptk = pullForm.value.employee_nuptk?.trim() || undefined
    }
    const wasExternal = !!pullForm.value.external
    await teacherMutationApi.createPull(payload)
    toast.success('Berhasil', wasExternal ? 'Mutasi masuk dari sekolah luar telah dicatat. Data guru telah ditambahkan.' : 'Permohonan tarik guru telah dikirim. Menunggu persetujuan sekolah asal.')
    showPullModal.value = false
    resetPullModal()
    if (wasExternal) {
      const alreadyApproved = filterStatus.value === 'approved'
      filterStatus.value = 'approved'
      activeTabMain.value = 'permohonan'
      if (alreadyApproved) await loadMutations()
    } else {
      await loadMutations()
    }
  } catch (err) {
    pullFormError.value = err.response?.data?.message || err.formattedMessage || 'Gagal mengajukan tarik guru'
    toast.error('Gagal', pullFormError.value)
  } finally {
    pullFormSubmitting.value = false
  }
}

async function loadMutations(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: pagination.value.per_page }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterRole.value) params.role = filterRole.value
    const res = await teacherMutationApi.getAll(params)
    mutations.value = res.data?.data ?? []
    const meta = res.data?.meta
    if (meta) {
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? 0,
        per_page: meta.per_page ?? 15
      }
    }
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat permohonan mutasi')
    mutations.value = []
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  loadMutations(page)
}

async function loadReport(page = 1) {
  reportLoading.value = true
  try {
    const params = {
      type: reportType.value,
      page,
      per_page: reportPagination.value.per_page || 15
    }
    if (reportFrom.value) params.from = reportFrom.value
    if (reportTo.value) params.to = reportTo.value
    const res = await teacherMutationApi.getReport(params)
    reportSummary.value = res.data?.summary ?? { mutasi_keluar: 0, mutasi_masuk: 0 }
    const raw = res.data?.data
    reportData.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
    const meta = res.data?.meta
    if (meta) {
      reportPagination.value = {
        current_page: meta.current_page ?? page,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? reportData.value.length,
        per_page: meta.per_page ?? 15
      }
    } else {
      reportPagination.value = {
        current_page: page,
        last_page: 1,
        total: reportData.value.length,
        per_page: 15
      }
    }
    reportLoaded.value = true
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal memuat laporan')
    reportSummary.value = null
    reportData.value = []
    reportLoaded.value = false
  } finally {
    reportLoading.value = false
  }
}

function isMutationOut(m) {
  if (!myInstitutionId.value) return !!m.origin_institution_id
  return sameInstitution(m.origin_institution_id, myInstitutionId.value)
}

function schoolOriginName(m) {
  return m.origin_institution?.name || m.origin_school_name || '–'
}

function schoolTargetName(m) {
  return m.target_institution?.name || m.target_school_name || '–'
}

function schoolOriginNpsn(m) {
  return m.origin_institution?.npsn || m.origin_npsn || '–'
}

function schoolTargetNpsn(m) {
  return m.target_institution?.npsn || m.target_npsn || '–'
}

function reportRowNumber(idx) {
  const page = reportPagination.value.current_page || 1
  const perPage = reportPagination.value.per_page || 15
  return (page - 1) * perPage + idx + 1
}

async function exportBukuMutasi(format) {
  exportingBukuMutasi.value = true
  try {
    const params = { format: format || 'pdf', type: reportType.value }
    if (reportFrom.value) params.from = reportFrom.value
    if (reportTo.value) params.to = reportTo.value
    const res = await teacherMutationApi.exportBukuMutasi(params)
    const contentType = res.headers?.['content-type'] || ''
    if (res.status !== 200 || contentType.includes('application/json')) {
      const text = typeof res.data?.text === 'function' ? await res.data.text() : String(res.data)
      const json = (() => { try { return JSON.parse(text) } catch { return {} } })()
      throw new Error(json.message || 'Gagal mengekspor Buku Mutasi Guru.')
    }
    const blob = res.data instanceof Blob ? res.data : new Blob([res.data])

    if (format === 'csv') {
      const url = URL.createObjectURL(new Blob([blob], { type: 'text/csv;charset=utf-8' }))
      const a = document.createElement('a')
      a.href = url
      a.download = `Buku_Mutasi_Guru_${reportFrom.value || ''}_${reportTo.value || ''}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
      toast.success('Berhasil', 'Buku Mutasi Guru (CSV) diunduh.')
      return
    }

    const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
    const win = window.open('', '_blank')
    if (!win) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
      URL.revokeObjectURL(url)
      return
    }
    const title = `Preview Buku Mutasi Guru ${reportFrom.value || ''} - ${reportTo.value || ''}`
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        body{margin:0;font-family:system-ui,sans-serif;background:#0f172a}
        .toolbar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;color:#f8fafc;border-bottom:1px solid #1e293b}
        .toolbar h1{margin:0;font-size:14px;font-weight:600}
        .actions button{border:none;border-radius:8px;padding:8px 14px;font-weight:600;cursor:pointer}
        .btn-print{background:#059669;color:#fff}
        .btn-close{background:#334155;color:#e2e8f0;margin-left:8px}
        iframe{width:100%;height:calc(100vh - 52px);border:0;background:#525659}
      </style></head><body>
      <div class="toolbar">
        <h1>Preview Buku Mutasi Guru</h1>
        <div class="actions">
          <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus();document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
          <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
        </div>
      </div>
      <iframe id="pdfFrame" src="${url}"></iframe>
    </body></html>`)
    win.document.close()
    setTimeout(() => URL.revokeObjectURL(url), 120_000)
    toast.success('Berhasil', 'Preview PDF Buku Mutasi Guru dibuka.')
  } catch (err) {
    toast.error('Gagal', err.message || err.response?.data?.message || err.formattedMessage || 'Gagal mengekspor Buku Mutasi Guru.')
  } finally {
    exportingBukuMutasi.value = false
  }
}

async function loadHistoryByNik() {
  const nik = historyNik.value?.replace(/\D/g, '') || ''
  if (nik.length !== 16) {
    historyError.value = 'Masukkan NIK 16 digit.'
    return
  }
  historyLoading.value = true
  historyError.value = ''
  historyLoaded.value = false
  try {
    const res = await teacherMutationApi.getHistoryByNik(nik)
    historyList.value = res.data?.data ?? res.data ?? []
    historyLoaded.value = true
  } catch (err) {
    historyError.value = err.response?.data?.message || err.formattedMessage || 'Gagal memuat riwayat'
    historyList.value = []
  } finally {
    historyLoading.value = false
  }
}

async function submitMutation() {
  formError.value = ''
  if (!form.value.target_npsn || form.value.target_npsn.length !== 8) {
    formError.value = 'NPSN harus 8 digit.'
    return
  }
  if (form.value.external && !form.value.target_school_name?.trim()) {
    formError.value = 'Nama sekolah tujuan wajib diisi untuk mutasi ke sekolah luar sistem.'
    return
  }
  const nik = form.value.nik?.replace(/\D/g, '') || ''
  if (nik.length !== 16) {
    formError.value = 'NIK guru wajib diisi (16 digit).'
    return
  }
  if (!formTeacherPreview.value) {
    formError.value = 'Cari dan konfirmasi data guru terlebih dahulu sebelum mengajukan.'
    return
  }
  formSubmitting.value = true
  try {
    const wasExternal = !!form.value.external
    await teacherMutationApi.create({
      external: form.value.external || undefined,
      target_npsn: form.value.target_npsn,
      target_school_name: form.value.external ? form.value.target_school_name?.trim() : undefined,
      nik,
      notes: form.value.notes?.trim() || undefined
    })
    toast.success('Berhasil', wasExternal ? 'Mutasi keluar ke sekolah luar sistem telah dicatat. Status guru: Pindah.' : 'Permohonan mutasi telah dikirim. Menunggu persetujuan sekolah tujuan.')
    showFormModal.value = false
    resetFormModal()
    try {
      if (wasExternal) {
        const alreadyApproved = filterStatus.value === 'approved'
        filterStatus.value = 'approved'
        activeTabMain.value = 'permohonan'
        if (alreadyApproved) await loadMutations()
      } else {
        await loadMutations()
      }
    } catch {
      // mutasi sudah tersimpan; gagal reload tidak boleh tampil sebagai gagal ajukan
    }
  } catch (err) {
    formError.value = err.response?.data?.message || err.formattedMessage || 'Gagal mengajukan mutasi'
    toast.error('Gagal', formError.value)
  } finally {
    formSubmitting.value = false
  }
}

function openApproveModal(m) {
  selectedMutation.value = m
  approveError.value = ''
  showApproveModal.value = true
}

function openRejectModal(m) {
  selectedMutation.value = m
  rejectionReason.value = ''
  rejectError.value = ''
  showRejectModal.value = true
}

function openCancelModal(m) {
  selectedMutation.value = m
  cancelReason.value = ''
  cancelError.value = ''
  showCancelModal.value = true
}

function openApproveCancelModal(m) {
  selectedMutation.value = m
  approveCancelError.value = ''
  showApproveCancelModal.value = true
}

function openRejectCancelModal(m) {
  selectedMutation.value = m
  cancelRejectionReason.value = ''
  rejectCancelError.value = ''
  showRejectCancelModal.value = true
}

async function handleApprove() {
  approveError.value = ''
  processing.value = true
  try {
    await teacherMutationApi.approve(selectedMutation.value.id, { action: 'approve' })
    toast.success('Berhasil', 'Permohonan mutasi disetujui. Data guru telah dipindahkan.')
    showApproveModal.value = false
    await loadMutations()
  } catch (err) {
    approveError.value = err.response?.data?.message || err.formattedMessage || 'Gagal menyetujui'
    toast.error('Gagal', approveError.value)
  } finally {
    processing.value = false
  }
}

async function handleReject() {
  if (!rejectionReason.value?.trim()) {
    rejectError.value = 'Alasan penolakan wajib diisi.'
    return
  }
  rejectError.value = ''
  processing.value = true
  try {
    await teacherMutationApi.approve(selectedMutation.value.id, {
      action: 'reject',
      rejection_reason: rejectionReason.value.trim()
    })
    toast.success('Berhasil', 'Permohonan mutasi ditolak.')
    showRejectModal.value = false
    await loadMutations()
  } catch (err) {
    rejectError.value = err.response?.data?.message || err.formattedMessage || 'Gagal menolak'
    toast.error('Gagal', rejectError.value)
  } finally {
    processing.value = false
  }
}

async function handleCancel() {
  cancelError.value = ''
  processing.value = true
  try {
    const res = await teacherMutationApi.cancel(selectedMutation.value.id, {
      reason: cancelReason.value?.trim() || null
    })
    toast.success('Berhasil', res.data?.message || 'Mutasi dibatalkan.')
    showCancelModal.value = false
    const status = res.data?.data?.status
    if (status === 'cancel_pending') {
      filterStatus.value = 'cancel_pending'
    } else if (status === 'cancelled') {
      filterStatus.value = 'cancelled'
    }
    await loadMutations()
  } catch (err) {
    cancelError.value = err.response?.data?.message || err.formattedMessage || 'Gagal membatalkan mutasi'
    toast.error('Gagal', cancelError.value)
  } finally {
    processing.value = false
  }
}

async function handleApproveCancel() {
  approveCancelError.value = ''
  processing.value = true
  try {
    await teacherMutationApi.decideCancel(selectedMutation.value.id, { action: 'approve' })
    toast.success('Berhasil', 'Pembatalan mutasi disetujui. Guru dikembalikan ke sekolah asal.')
    showApproveCancelModal.value = false
    filterStatus.value = 'cancelled'
    await loadMutations()
  } catch (err) {
    approveCancelError.value = err.response?.data?.message || err.formattedMessage || 'Gagal menyetujui pembatalan'
    toast.error('Gagal', approveCancelError.value)
  } finally {
    processing.value = false
  }
}

async function handleRejectCancel() {
  if (!cancelRejectionReason.value?.trim()) {
    rejectCancelError.value = 'Alasan penolakan wajib diisi.'
    return
  }
  rejectCancelError.value = ''
  processing.value = true
  try {
    await teacherMutationApi.decideCancel(selectedMutation.value.id, {
      action: 'reject',
      rejection_reason: cancelRejectionReason.value.trim()
    })
    toast.success('Berhasil', 'Permohonan pembatalan ditolak. Mutasi tetap berlaku.')
    showRejectCancelModal.value = false
    filterStatus.value = 'approved'
    await loadMutations()
  } catch (err) {
    rejectCancelError.value = err.response?.data?.message || err.formattedMessage || 'Gagal menolak pembatalan'
    toast.error('Gagal', rejectCancelError.value)
  } finally {
    processing.value = false
  }
}

function getStatusLabel(status) {
  const map = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
    cancel_pending: 'Menunggu batal'
  }
  return map[status] || status
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatDateShort(dateString) {
  if (!dateString) return '–'
  // date-only (YYYY-MM-DD) parse as local to avoid timezone shift
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
    const [y, m, d] = dateString.split('-').map(Number)
    return new Date(y, m - 1, d).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

watch(filterStatus, () => loadMutations(1))
watch(filterRole, () => loadMutations(1))
watch(activeTabMain, (tab) => {
  if (tab === 'laporan' && !reportLoaded.value && !reportLoading.value) {
    loadReport(1)
  }
})

watch(() => pullForm.value.external, (isExternal) => {
  pullTeacherPreview.value = null
  pullLookupError.value = ''
  if (isExternal) {
    originInstitution.value = null
  }
})

watch(showFormModal, (open) => {
  if (open) {
    resetFormModal()
  }
})

watch(showPullModal, (open) => {
  if (open) {
    resetPullModal()
  }
})

onMounted(async () => {
  await authStore.fetchUser()
  await loadMutations(1)
})
</script>

<style scoped>
.mutation-page {
  width: 100%;
  max-width: 100%;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 28px;
  flex-wrap: wrap;
  padding: 24px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.header-icon-wrap {
  flex-shrink: 0;
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 14px;
  color: #fff;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
}

.header-icon {
  width: 32px;
  height: 32px;
}

.header-content {
  flex: 1;
  min-width: 0;
}

.page-title {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.02em;
}

.page-subtitle {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.form-hint {
  color: #64748b;
  font-size: 13px;
  margin-bottom: 20px;
  padding: 12px 14px;
  background: #f8fafc;
  border-radius: 10px;
  border-left: 4px solid #059669;
  line-height: 1.5;
}

.institution-preview {
  margin-top: 6px;
  font-size: 13px;
  color: #059669;
}

.text-muted {
  margin-top: 6px;
  font-size: 13px;
  color: #94a3b8;
}

.text-error-inline {
  margin-top: 6px;
  font-size: 13px;
  color: #dc2626;
}

.nisn-search-row {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.nisn-search-row input {
  flex: 1;
  min-width: 0;
}

.btn-lookup {
  flex-shrink: 0;
  white-space: nowrap;
  padding: 10px 14px;
}

.student-preview-card {
  margin: 4px 0 16px;
  padding: 14px 16px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
}

.student-preview-title {
  font-size: 13px;
  font-weight: 700;
  color: #047857;
  margin-bottom: 10px;
}

.student-preview-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 16px;
}

.student-preview-grid > div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.student-preview-grid .span-2 {
  grid-column: 1 / -1;
}

.student-preview-grid .label {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.student-preview-grid .value {
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}

.student-preview-hint {
  margin: 12px 0 0;
  font-size: 12px;
  color: #047857;
}

.form-group-checkbox {
  margin-bottom: 12px;
}
.form-group-checkbox .checkbox-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  font-size: 14px;
}
.form-group-checkbox .checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #059669;
}

.badge-external {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  color: #b45309;
  background: #fef3c7;
  border-radius: 6px;
}
.badge-wali {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  color: #065f46;
  background: #d1fae5;
  border-radius: 6px;
}

.main-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  padding: 6px;
  background: #f1f5f9;
  border-radius: 12px;
  width: fit-content;
}
.main-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.main-tab:hover {
  color: #475569;
  background: rgba(255, 255, 255, 0.8);
}
.main-tab.active {
  color: #fff;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.35);
}

.report-section, .history-section {
  margin-top: 16px;
}
.report-form .report-filters, .history-form .form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: flex-end;
  margin-bottom: 0;
}
.report-form-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e2e8f0;
}
.report-panel-title {
  margin: 0 0 4px;
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
}
.report-panel-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.45;
  max-width: 42rem;
}
.form-group-type {
  min-width: 220px;
}
.report-type-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.report-summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-radius: 12px;
  text-align: left;
  border: 1px solid transparent;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.stat-card .stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  flex-shrink: 0;
}
.stat-card .stat-value {
  display: block;
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin-bottom: 2px;
}
.stat-card .stat-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}
.stat-card.stat-out {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-color: #fcd34d;
}
.stat-card.stat-out .stat-icon {
  background: rgba(217, 119, 6, 0.15);
  color: #b45309;
}
.stat-card.stat-in {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-color: #6ee7b7;
}
.stat-card.stat-in .stat-icon {
  background: rgba(5, 150, 105, 0.15);
  color: #047857;
}
.stat-card.stat-total {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-color: #cbd5e1;
}
.stat-card.stat-total .stat-icon {
  background: rgba(71, 85, 105, 0.12);
  color: #475569;
}
.report-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 14px;
  padding: 10px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}
.report-period {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.report-count {
  font-size: 13px;
  color: #64748b;
}
.report-empty {
  margin-top: 8px;
}
.table-container {
  width: 100%;
  overflow-x: auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.data-table thead {
  background: #f8fafc;
}
.data-table th {
  padding: 12px 14px;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}
.data-table td {
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
  vertical-align: top;
}
.data-table tbody tr:hover {
  background: #f8fafc;
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
.data-table .col-no {
  width: 48px;
  text-align: center;
  color: #64748b;
}
.data-table .col-jk {
  width: 48px;
  text-align: center;
}
.data-table .col-date {
  white-space: nowrap;
}
.data-table .col-name {
  font-weight: 600;
  min-width: 140px;
}
.data-table .col-notes {
  max-width: 180px;
  color: #64748b;
}
.school-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 120px;
}
.school-npsn {
  font-size: 11px;
  color: #94a3b8;
}
.jenis-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.jenis-badge.jenis-out {
  background: #fef3c7;
  color: #b45309;
}
.jenis-badge.jenis-in {
  background: #d1fae5;
  color: #047857;
}
.report-table-desktop {
  display: block;
}
.report-cards-mobile {
  display: none;
}
.card-date {
  margin-left: auto;
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.card-form {
  padding: 20px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
}
.history-search-row {
  margin-bottom: 0;
}
.form-group-flex { flex: 1; min-width: 200px; }
.btn-search { display: inline-flex; align-items: center; gap: 8px; }
.mini-spinner {
  width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 20px 24px;
  margin-bottom: 22px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-group-label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  flex-shrink: 0;
}

.filter-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.tab {
  padding: 8px 14px;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.tab:hover {
  border-color: #cbd5e1;
  color: #475569;
  background: #fff;
}

.tab.active {
  background: #059669;
  border-color: #059669;
  color: #fff;
}

.tab-role {
  padding: 6px 12px;
  font-size: 12px;
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 28px;
  padding: 18px 20px;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 14px;
  color: #64748b;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-page {
  padding: 10px 16px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}

.btn-page:hover:not(:disabled) {
  border-color: #059669;
  color: #059669;
  background: #f8fafc;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-num {
  font-size: 14px;
  color: #64748b;
}

.loading-state {
  text-align: center;
  padding: 56px 24px;
  color: #64748b;
  background: #f8fafc;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}

.loading-state p {
  margin: 0;
  font-size: 15px;
  font-weight: 500;
}

.loading-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid #e2e8f0;
  border-top-color: #059669;
  border-radius: 50%;
  margin: 0 auto 20px;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 56px 32px;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 16px;
  border: 2px dashed #cbd5e1;
}

.empty-icon {
  color: #94a3b8;
  margin-bottom: 20px;
}
.empty-icon svg { opacity: 0.7; }
.empty-title {
  font-size: 20px;
  font-weight: 700;
  color: #334155;
  margin: 0 0 8px 0;
}
.empty-desc {
  color: #64748b;
  font-size: 15px;
  margin: 0 0 24px 0;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.5;
}

.empty-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
.btn-empty-cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 600;
}

.mutations-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 20px;
}

.mutation-card {
  background: #fff;
  border-radius: 14px;
  padding: 22px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.mutation-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  border-color: #cbd5e1;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.card-flow {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  margin-bottom: 16px;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  font-size: 13px;
}
.flow-origin, .flow-target {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #475569;
  font-weight: 500;
}
.flow-arrow {
  flex-shrink: 0;
  color: #059669;
}
.detail-highlight .value { font-weight: 600; color: #0f172a; }
.value-muted { font-weight: 400; color: #64748b; }
.rejection-row { margin-top: 8px; padding-top: 8px; border-top: 1px solid #fee2e2; }

.status-badge {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #d97706;
}

.status-approved {
  background: #d1fae5;
  color: #059669;
}

.status-rejected {
  background: #fee2e2;
  color: #dc2626;
}

.status-cancelled {
  background: #e2e8f0;
  color: #475569;
}

.status-cancel_pending {
  background: #ffedd5;
  color: #c2410c;
}

.initiated-label {
  font-size: 12px;
  color: #64748b;
}

.card-body {
  padding: 0 0 4px 0;
}
.card-body .detail-row {
  margin-bottom: 10px;
  font-size: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px 8px;
}
.card-body .detail-row .label {
  flex: 0 0 120px;
  min-width: 0;
}
.card-body .detail-row .value {
  flex: 1;
  min-width: 0;
}

.card-body .label {
  color: #64748b;
  margin-right: 8px;
}

.card-body .value {
  color: #1e293b;
}

.rejection-reason {
  color: #dc2626;
}

.card-actions {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-approve {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.btn-approve:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.4);
}

.btn-reject {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 10px;
  border: 2px solid #dc2626;
  background: #fff;
  color: #dc2626;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}
.btn-reject:hover {
  background: #fef2f2;
  color: #b91c1c;
}

.btn-cancel-mutation {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 10px;
  border: 2px solid #94a3b8;
  background: #fff;
  color: #475569;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.btn-cancel-mutation:hover {
  background: #f1f5f9;
  border-color: #64748b;
  color: #334155;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: #fff;
  border-radius: 16px;
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.25s ease;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #fafafa;
  border-radius: 16px 16px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 19px;
  font-weight: 700;
  color: #0f172a;
}

.btn-close {
  background: #f1f5f9;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  font-size: 22px;
  color: #64748b;
  cursor: pointer;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
}
.btn-close:hover {
  background: #e2e8f0;
  color: #475569;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
  font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.form-group textarea {
  resize: vertical;
  min-height: 60px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 16px;
  margin-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.error-message {
  color: #dc2626;
  font-size: 13px;
  margin-top: 8px;
}

.approval-details {
  margin: 16px 0;
}

.approval-details .detail-row {
  margin-bottom: 8px;
  font-size: 14px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  background: #059669;
  color: #fff;
  font-weight: 500;
  cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
  background: #047857;
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 10px 18px;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  background: #fff;
  color: #475569;
  font-weight: 600;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.btn-secondary:hover:not(:disabled) {
  border-color: #059669;
  background: #f8fafc;
  color: #059669;
}

.btn-compact {
  font-size: 14px;
  padding: 10px 16px;
}

/* Report form filters */
.report-form .report-filters .form-group label {
  margin-bottom: 8px;
}
.report-form .report-filters .form-group input,
.report-form .report-filters .form-group select {
  min-width: 140px;
}
.report-export-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}
.report-export-actions .btn-export-pdf,
.report-export-actions .btn-export-csv {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  border: 1px solid var(--border-color, #e2e8f0);
  background: var(--bg-secondary, #f8fafc);
  color: var(--text-primary, #1e293b);
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}
.report-export-actions .btn-export-pdf:hover:not(:disabled),
.report-export-actions .btn-export-csv:hover:not(:disabled) {
  background: var(--bg-hover, #f1f5f9);
  border-color: var(--border-hover, #cbd5e1);
}
.report-export-actions .btn-export-pdf:disabled,
.report-export-actions .btn-export-csv:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.report-export-actions .btn-export-pdf {
  background: var(--primary-light, #eff6ff);
  border-color: var(--primary, #059669);
  color: var(--primary, #059669);
}
.report-export-actions .btn-export-pdf:hover:not(:disabled) {
  background: var(--primary, #059669);
  color: #fff;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    padding: 18px;
    gap: 16px;
  }
  .header-icon-wrap {
    width: 48px;
    height: 48px;
  }
  .header-icon { width: 28px; height: 28px; }
  .page-title { font-size: 22px; }
  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }
  .main-tabs {
    width: 100%;
    overflow-x: auto;
    flex-wrap: nowrap;
    padding: 6px 8px;
    -webkit-overflow-scrolling: touch;
  }
  .main-tab { white-space: nowrap; padding: 8px 14px; font-size: 13px; }
  .mutations-list {
    grid-template-columns: 1fr;
  }
  .report-table-desktop {
    display: none;
  }
  .report-cards-mobile {
    display: grid;
  }
  .report-form-top {
    flex-direction: column;
  }
  .pagination-bar {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  .card-flow {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  .flow-arrow { transform: rotate(90deg); align-self: center; }
  .student-preview-grid { grid-template-columns: 1fr; }
  .form-group-type { min-width: 0; width: 100%; }
  .report-form-top .filter-select,
  .report-form-top input,
  .report-form-top select {
    min-width: 0 !important;
    width: 100%;
  }
}
</style>
