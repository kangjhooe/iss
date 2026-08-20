<template>
  <div v-if="show" class="modal-overlay" role="presentation">
    <div
      class="modal-content"
      :class="{ 'modal-wide': items.length }"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      @click.stop
    >
      <div class="modal-header">
        <h3 :id="titleId">{{ title }}</h3>
        <button type="button" class="btn-close" aria-label="Tutup" @click="close">×</button>
      </div>
      <div class="modal-body">
        <p v-if="name" class="intro">
          Akun untuk <strong>{{ name }}</strong>
        </p>
        <p class="hint">{{ hint }}</p>

        <template v-if="items.length">
          <div class="list-actions">
            <button type="button" class="btn-secondary btn-compact" @click="copyAllItems">Salin semua</button>
            <span class="list-count">{{ items.length }} akun</span>
          </div>
          <div class="table-wrap">
            <table class="cred-table">
              <thead>
                <tr>
                  <th v-if="hasItemNames">Nama</th>
                  <th>{{ loginLabel }}</th>
                  <th>Password</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in items" :key="`${item.login}-${index}`">
                  <td v-if="hasItemNames">{{ item.name || '—' }}</td>
                  <td><code>{{ item.login }}</code></td>
                  <td><code class="password">{{ item.password }}</code></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <template v-else>
          <div class="cred-row">
            <span class="cred-label">{{ loginLabel }}</span>
            <code>{{ loginValue }}</code>
            <button type="button" class="btn-secondary btn-compact" @click="copyText(loginValue, loginLabel)">Salin</button>
          </div>
          <div class="cred-row">
            <span class="cred-label">Password</span>
            <code class="password">{{ password }}</code>
            <button type="button" class="btn-secondary btn-compact" @click="copyText(password, 'Password')">Salin</button>
          </div>
          <button type="button" class="btn-secondary btn-copy-all" @click="copyPair">Salin {{ loginLabel }} + password</button>
        </template>

        <div class="modal-footer">
          <button type="button" class="btn-primary" @click="close">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Akun login berhasil dibuat' },
  name: { type: String, default: '' },
  loginLabel: { type: String, default: 'Email' },
  loginValue: { type: String, default: '' },
  password: { type: String, default: '' },
  hint: {
    type: String,
    default: 'Sandi hanya ditampilkan sekali. Simpan lalu minta pengguna mengganti setelah login.',
  },
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])
const toast = useToast()
const titleId = 'account-credentials-title'

const hasItemNames = computed(() => props.items.some((item) => item?.name))

const close = () => emit('close')

const copyText = async (value, label = 'Teks') => {
  const text = String(value || '')
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Disalin', `${label} disalin ke clipboard`)
  } catch {
    toast.error('Gagal', `Tidak dapat menyalin ${label.toLowerCase()}`)
  }
}

const copyPair = () => {
  const lines = [`${props.loginLabel}: ${props.loginValue}`, `Password: ${props.password}`]
  copyText(lines.join('\n'), 'Kredensial')
}

const copyAllItems = () => {
  const header = hasItemNames.value ? `Nama\t${props.loginLabel}\tPassword` : `${props.loginLabel}\tPassword`
  const rows = props.items.map((item) => (
    hasItemNames.value
      ? `${item.name || ''}\t${item.login}\t${item.password}`
      : `${item.login}\t${item.password}`
  ))
  copyText([header, ...rows].join('\n'), 'Daftar kredensial')
}

watch(() => props.show, (visible) => {
  document.body.style.overflow = visible ? 'hidden' : ''
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 13000;
  padding: 20px;
}

.modal-content {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 520px;
  max-height: 90vh;
  overflow: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-wide {
  max-width: 640px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #94a3b8;
  cursor: pointer;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  line-height: 1;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #64748b;
}

.modal-body {
  padding: 20px 24px 24px;
}

.intro {
  margin: 0 0 8px;
  color: #1e293b;
  font-size: 15px;
}

.hint {
  margin: 0 0 16px;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.cred-row {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
  margin-bottom: 10px;
}

.cred-label {
  min-width: 78px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

code {
  flex: 1;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  word-break: break-all;
}

code.password {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing: 0.04em;
}

.btn-copy-all {
  width: 100%;
  justify-content: center;
  margin-top: 4px;
}

.list-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.list-count {
  font-size: 13px;
  color: #64748b;
}

.table-wrap {
  max-height: 320px;
  overflow: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.cred-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.cred-table th,
.cred-table td {
  text-align: left;
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
}

.cred-table th {
  background: #f8fafc;
  color: #64748b;
  font-weight: 600;
  position: sticky;
  top: 0;
}

.cred-table tr:last-child td {
  border-bottom: none;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn-primary,
.btn-secondary {
  border-radius: 8px;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
}

.btn-primary {
  background: #059669;
  color: #fff;
  border: none;
}

.btn-primary:hover {
  background: #047857;
}

.btn-secondary {
  background: #fff;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-compact {
  padding: 6px 10px;
  font-size: 12px;
  flex-shrink: 0;
}

@media (max-width: 640px) {
  .modal-content {
    max-width: 100%;
  }

  .cred-row {
    flex-wrap: wrap;
  }

  .cred-label {
    width: 100%;
    min-width: 0;
  }

  .modal-footer button {
    width: 100%;
    justify-content: center;
  }
}
</style>
