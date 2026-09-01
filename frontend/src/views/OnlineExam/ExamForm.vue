<template>    <div class="exam-form-page">
      <div class="page-bg">
        <div class="page-bg-orb page-bg-orb-1"></div>
        <div class="page-bg-orb page-bg-orb-2"></div>
      </div>

      <div class="page-inner">
        <router-link to="/ujian-online/exams" class="back-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
          </svg>
          Daftar Ujian
        </router-link>

        <div class="form-card">
          <div class="card-header">
            <div class="card-header-icon">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
              </svg>
            </div>
            <h1 class="card-title">{{ isEdit ? 'Edit Ujian' : 'Buat Ujian Baru' }}</h1>
            <p class="card-subtitle">
              {{ isEdit ? 'Perbarui identitas ujian.' : 'Isi identitas ujian. Soal, waktu, dan jadwal diatur nanti di halaman detail ujian.' }}
            </p>
          </div>

          <form @submit.prevent="submit" class="exam-form">
            <div class="form-section">
              <div class="form-group">
                <label class="field-label">Kode Ujian <span class="required">*</span></label>
                <div class="input-wrapper">
                  <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                  </svg>
                  <input
                    v-model="form.code"
                    type="text"
                    required
                    maxlength="64"
                    placeholder="Contoh: UAS-2025, UTS-Ganjil"
                    class="field-input"
                  />
                </div>
                <span class="form-hint">Unik per sekolah, untuk identifikasi ujian.</span>
              </div>

              <div class="form-group">
                <label class="field-label">Nama Ujian <span class="required">*</span></label>
                <div class="input-wrapper">
                  <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="Contoh: UAS Genap / Ujian Akhir Semester"
                    class="field-input"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="field-label">Deskripsi</label>
                <div class="input-wrapper input-wrapper-textarea">
                  <svg class="input-icon input-icon-textarea" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                  </svg>
                  <textarea
                    v-model="form.description"
                    rows="3"
                    placeholder="Deskripsi singkat (opsional)"
                    class="field-input field-textarea"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-submit" :disabled="saving">
                <span v-if="saving" class="btn-spinner"></span>
                <span class="btn-text">{{ saving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Buat Ujian') }}</span>
              </button>
              <router-link to="/ujian-online/exams" class="btn-cancel">Batal</router-link>
            </div>
          </form>
        </div>
      </div>
    </div></template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const isEdit = computed(() => !!route.params.code && route.params.code !== 'buat')
const saving = ref(false)
const examId = ref(null) // set when loading exam for edit (untuk panggilan API update)

const form = reactive({
  code: '',
  name: '',
  description: '',
  subject_id: null // untuk edit: disimpan dari API, dikirim saat update
})

async function loadExam() {
  if (!isEdit.value) return
  try {
    const res = await examApi.getExamByCode(route.params.code)
    const d = res.data?.data ?? res.data
    examId.value = d.id
    form.code = d.code ?? ''
    form.name = d.name ?? ''
    form.description = d.description ?? ''
    form.subject_id = d.subject_id ?? null
  } catch (e) {
    toast.error('Gagal memuat ujian', e.response?.data?.message || 'Data ujian tidak dapat dimuat. Periksa koneksi dan coba lagi.')
  }
}

async function submit() {
  saving.value = true
  try {
    const payload = {
      code: form.code.trim(),
      name: form.name.trim(),
      description: form.description?.trim() || null,
      start_type: 'manual',
      scheduled_start_at: null,
      scheduled_end_at: null
    }
    if (isEdit.value) {
      if (form.subject_id != null && form.subject_id !== '') payload.subject_id = form.subject_id
    }
    if (isEdit.value) {
      await examApi.updateExam(examId.value, payload)
      toast.success('Berhasil', 'Ujian diperbarui.')
      router.push(`/ujian-online/exams/${encodeURIComponent(form.code)}`)
    } else {
      const res = await examApi.createExam(payload)
      toast.success('Berhasil', 'Ujian dibuat. Selanjutnya: pilih soal dari bank (boleh lintas tingkat), lalu atur sesi.')
      const created = res.data?.data ?? res.data
      const code = created?.code
      if (code) router.push(`/ujian-online/exams/${encodeURIComponent(code)}`)
      else if (created?.id) router.push(`/ujian-online/exams/${created.id}`)
      else router.push('/ujian-online/exams')
    }
  } catch (e) {
    toast.error('Gagal menyimpan ujian', e.response?.data?.message || e.formattedMessage || 'Perubahan tidak dapat disimpan. Coba lagi.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadExam()
})
</script>

<style scoped>
.exam-form-page {
  min-height: 100%;
  position: relative;
}

.page-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  background: linear-gradient(160deg, #f0f9ff 0%, #ecfdf5 35%, #f0fdf4 60%, #f8fafc 100%);
  background-size: 400% 400%;
  animation: bgShift 14s ease infinite;
}

.page-bg-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.5;
  animation: orbFloat 18s ease-in-out infinite;
}

.page-bg-orb-1 {
  width: 320px;
  height: 320px;
  background: rgba(5, 150, 105, 0.2);
  top: -80px;
  right: -60px;
}

.page-bg-orb-2 {
  width: 280px;
  height: 280px;
  background: rgba(6, 182, 212, 0.15);
  bottom: -60px;
  left: -40px;
  animation-delay: -6s;
}

@keyframes bgShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

@keyframes orbFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(20px, -20px) scale(1.05); }
  66% { transform: translate(-15px, 15px) scale(0.95); }
}

.page-inner {
  position: relative;
  z-index: 1;
  padding: 1.5rem 1rem 2.5rem;
  max-width: 480px;
  margin: 0 auto;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
  transition: color 0.2s, transform 0.2s;
  padding: 0.4rem 0;
}

.back-link:hover {
  color: #059669;
  transform: translateX(-2px);
}

.back-link svg {
  flex-shrink: 0;
  transition: transform 0.2s;
}

.back-link:hover svg {
  transform: translateX(-2px);
}

.form-card {
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(14px);
  border-radius: 20px;
  padding: 2rem 1.75rem;
  box-shadow: 0 4px 24px rgba(5, 150, 105, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.85);
  animation: cardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes cardIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.card-header {
  text-align: center;
  margin-bottom: 1.75rem;
}

.card-header-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(5, 150, 105, 0.15) 0%, rgba(5, 150, 105, 0.06) 100%);
  border-radius: 14px;
  color: #059669;
  animation: iconPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
}

@keyframes iconPop {
  from {
    opacity: 0;
    transform: scale(0.7);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.card-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.35rem 0;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.card-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.exam-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-section {
  animation: formIn 0.4s ease both;
}

.form-section:nth-of-type(1) { animation-delay: 0.15s; }

@keyframes formIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.section-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #475569;
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.form-group {
  margin-bottom: 1.15rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.field-label {
  display: block;
  margin-bottom: 0.4rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #334155;
}

.required {
  color: #dc2626;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
  z-index: 1;
  transition: color 0.25s ease, transform 0.25s ease;
}

.input-wrapper-textarea .input-icon {
  top: 14px;
  align-self: flex-start;
}

.input-icon-textarea {
  position: absolute;
}

.field-input {
  width: 100%;
  padding: 12px 14px 12px 44px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  background: #fff;
  color: #0f172a;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field-input::placeholder {
  color: #94a3b8;
}

.field-input:hover {
  border-color: #cbd5e1;
}

.field-input:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15);
}

.form-group:focus-within .input-icon {
  color: #059669;
  transform: scale(1.06);
}

.field-textarea {
  padding-top: 12px;
  min-height: 88px;
  resize: vertical;
}

.input-wrapper-textarea .field-textarea {
  padding-left: 44px;
}

.field-select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 2.5rem;
}

.field-number {
  max-width: 100px;
}

.form-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.35rem;
  display: block;
}

.checkbox-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #475569;
  font-weight: 500;
  transition: color 0.2s;
}

.checkbox-item:hover {
  color: #334155;
}

.checkbox-item input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkbox-box {
  width: 20px;
  height: 20px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  flex-shrink: 0;
  transition: all 0.2s ease;
  position: relative;
  background: #fff;
}

.checkbox-item input:checked + .checkbox-box {
  background: #059669;
  border-color: #059669;
}

.checkbox-item input:checked + .checkbox-box::after {
  content: '';
  position: absolute;
  left: 6px;
  top: 2px;
  width: 5px;
  height: 10px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-item input:focus-visible + .checkbox-box {
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25);
}

.checkbox-text {
  user-select: none;
}

.info-box {
  display: flex;
  gap: 0.75rem;
  padding: 1rem 1.15rem;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border: 1px solid #fde68a;
  border-radius: 12px;
  align-items: flex-start;
}

.info-box svg {
  flex-shrink: 0;
  color: #d97706;
  margin-top: 0.1rem;
}

.info-box p {
  margin: 0;
  font-size: 0.85rem;
  color: #92400e;
  line-height: 1.5;
}

.info-box strong {
  color: #b45309;
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.25rem;
  padding-top: 0.5rem;
}

.btn-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 12px 1.5rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
  min-width: 140px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.45);
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-cancel {
  padding: 12px 1.25rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  text-decoration: none;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #475569;
  border-color: #cbd5e1;
}

@media (prefers-reduced-motion: reduce) {
  .page-bg,
  .page-bg-orb,
  .form-card,
  .card-header-icon,
  .form-section {
    animation: none !important;
  }
}

@media (max-width: 640px) {
  .page-inner {
    padding: 1rem 0.75rem 1.5rem;
  }

  .form-card {
    padding: 1.5rem 1.25rem;
  }

  .card-title {
    font-size: 1.35rem;
  }

  .card-header-icon {
    width: 48px;
    height: 48px;
  }

  .card-header-icon svg {
    width: 26px;
    height: 26px;
  }

  .checkbox-row {
    flex-direction: column;
  }
}
</style>
