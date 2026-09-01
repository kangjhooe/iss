<template>    <div class="monetization-page">
      <div class="page-header">
        <div>
          <h2>Monetisasi</h2>
          <p>Paket, add-on, dan peluncuran ke sekolah. Default: tersembunyi (dark launch).</p>
        </div>
        <span :class="summary.launched ? 'badge-warn' : 'badge-ok'">
          {{ summary.launched ? 'Ditampilkan ke sekolah' : 'Tersembunyi dari sekolah' }}
        </span>
      </div>

      <section class="panel">
        <div class="panel-header">
          <div>
            <h3>Peluncuran ke sekolah</h3>
            <p>
              Saat nonaktif, tidak ada menu billing/add-on di sekolah lama maupun baru.
              Super Admin tetap bisa menyiapkan paket &amp; grant di sini.
            </p>
          </div>
        </div>

        <label class="switch-row">
          <input v-model="launchForm.launched" type="checkbox" />
          <span>Tampilkan monetisasi ke sekolah</span>
        </label>

        <div class="form-group">
          <label>Kuota penyimpanan default (MB)</label>
          <input v-model.number="launchForm.default_storage_quota_mb" type="number" min="100" class="form-control" />
        </div>

        <p v-if="launchError" class="form-error">{{ launchError }}</p>
        <p v-if="launchSuccess" class="form-success">{{ launchSuccess }}</p>

        <div class="actions">
          <button type="button" class="btn-primary" :disabled="savingLaunch" @click="saveLaunch">
            {{ savingLaunch ? 'Menyimpan...' : 'Simpan status peluncuran' }}
          </button>
        </div>
      </section>

      <section class="panel panel-spaced">
        <div class="panel-header">
          <div>
            <h3>Paket (subscription plans)</h3>
            <p>{{ summary.active_plans_count || 0 }} aktif dari {{ summary.plans_count || 0 }} paket</p>
          </div>
        </div>

        <div v-if="loadingPlans" class="muted">Memuat paket...</div>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Key</th>
                <th>Kuota (MB)</th>
                <th>Ujian Online</th>
                <th>Aktif</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plan in plans" :key="plan.id">
                <td>{{ plan.name }}</td>
                <td><code>{{ plan.key }}</code></td>
                <td>
                  <input v-model.number="plan.storage_quota_mb" type="number" min="100" class="input-sm" />
                </td>
                <td>
                  <input v-model="plan.includes_online_exam" type="checkbox" />
                </td>
                <td>
                  <input v-model="plan.is_active" type="checkbox" />
                </td>
                <td>
                  <button type="button" class="btn-link" :disabled="savingPlanId === plan.id" @click="savePlan(plan)">
                    {{ savingPlanId === plan.id ? '...' : 'Simpan' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel panel-spaced">
        <div class="panel-header">
          <div>
            <h3>Add-on</h3>
            <p>Peningkatan penyimpanan &amp; modul ujian online</p>
          </div>
        </div>

        <div v-if="loadingAddons" class="muted">Memuat add-on...</div>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Key</th>
                <th>Storage (MB)</th>
                <th>Aktif</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="addon in addons" :key="addon.id">
                <td>{{ addon.name }}</td>
                <td><code>{{ addon.key }}</code></td>
                <td>
                  <input
                    v-model.number="addon.storage_mb"
                    type="number"
                    min="0"
                    class="input-sm"
                    :disabled="addon.key !== 'storage_upgrade'"
                  />
                </td>
                <td>
                  <input v-model="addon.is_active" type="checkbox" />
                </td>
                <td>
                  <button type="button" class="btn-link" :disabled="savingAddonId === addon.id" @click="saveAddon(addon)">
                    {{ savingAddonId === addon.id ? '...' : 'Simpan' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel panel-spaced">
        <div class="panel-header">
          <div>
            <h3>Grant per institusi</h3>
            <p>Tetapkan paket atau grant add-on secara manual (siap sebelum launch)</p>
          </div>
        </div>

        <div class="toolbar">
          <input
            v-model="institutionSearch"
            type="search"
            class="form-control"
            placeholder="Cari nama / NPSN..."
            @keyup.enter="loadInstitutions"
          />
          <button type="button" class="btn-secondary" :disabled="loadingInstitutions" @click="loadInstitutions">
            Cari
          </button>
        </div>

        <div v-if="loadingInstitutions" class="muted">Memuat institusi...</div>
        <div v-else-if="institutions.length === 0" class="muted">Tidak ada data</div>
        <div v-else class="inst-list">
          <article v-for="inst in institutions" :key="inst.id" class="inst-card">
            <div class="inst-head">
              <div>
                <strong>{{ inst.name }}</strong>
                <div class="muted">NPSN {{ inst.npsn || '—' }} · Total storage {{ inst.storage?.total_mb ?? '—' }} MB</div>
              </div>
              <span class="chip" :class="inst.online_exam_entitled ? 'chip-ok' : 'chip-mute'">
                Ujian Online: {{ inst.online_exam_entitled ? 'entitled' : 'tidak' }}
              </span>
            </div>

            <div class="inst-grid">
              <div class="form-group">
                <label>Paket</label>
                <select v-model="instForms[inst.id].subscription_plan_id" class="form-control">
                  <option :value="null">— Tanpa paket —</option>
                  <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Status langganan</label>
                <select v-model="instForms[inst.id].status" class="form-control">
                  <option value="manual">manual</option>
                  <option value="trial">trial</option>
                  <option value="active">active</option>
                  <option value="suspended">suspended</option>
                </select>
              </div>
              <div class="actions-inline">
                <button type="button" class="btn-secondary btn-compact" @click="saveSubscription(inst)">
                  Simpan paket
                </button>
              </div>
            </div>

            <div class="inst-grid">
              <label class="switch-row">
                <input v-model="instForms[inst.id].grant_online_exam" type="checkbox" />
                <span>Grant add-on Ujian Online</span>
              </label>
              <label class="switch-row">
                <input v-model="instForms[inst.id].grant_storage" type="checkbox" />
                <span>Grant Peningkatan Penyimpanan</span>
              </label>
              <div class="form-group">
                <label>Addon storage (MB)</label>
                <input v-model.number="instForms[inst.id].storage_mb" type="number" min="0" class="form-control" />
              </div>
              <div class="actions-inline">
                <button type="button" class="btn-secondary btn-compact" @click="saveAddons(inst)">
                  Simpan grant
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div></template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { superAdminPlatformApi } from '@/api/superAdminPlatform'
import { useToast } from '@/composables/useToast'

const toast = useToast()

const summary = ref({
  launched: false,
  default_storage_quota_mb: 5120,
  plans_count: 0,
  active_plans_count: 0
})

const launchForm = ref({
  launched: false,
  default_storage_quota_mb: 5120
})
const savingLaunch = ref(false)
const launchError = ref('')
const launchSuccess = ref('')

const plans = ref([])
const addons = ref([])
const loadingPlans = ref(false)
const loadingAddons = ref(false)
const savingPlanId = ref(null)
const savingAddonId = ref(null)

const institutions = ref([])
const instForms = reactive({})
const institutionSearch = ref('')
const loadingInstitutions = ref(false)

onMounted(async () => {
  await Promise.all([loadSummary(), loadPlans(), loadAddons(), loadInstitutions()])
})

async function loadSummary() {
  const res = await superAdminPlatformApi.getMonetizationSummary()
  summary.value = res.data?.data || summary.value
  launchForm.value.launched = !!summary.value.launched
  launchForm.value.default_storage_quota_mb = summary.value.default_storage_quota_mb || 5120
}

async function saveLaunch() {
  savingLaunch.value = true
  launchError.value = ''
  launchSuccess.value = ''
  try {
    const res = await superAdminPlatformApi.updateMonetizationLaunch({
      launched: !!launchForm.value.launched,
      default_storage_quota_mb: launchForm.value.default_storage_quota_mb
    })
    summary.value = res.data?.data || summary.value
    launchSuccess.value = res.data?.message || 'Disimpan'
    toast.success('Berhasil', launchSuccess.value)
  } catch (err) {
    launchError.value = err.response?.data?.message || 'Gagal menyimpan'
    toast.error('Gagal', launchError.value)
  } finally {
    savingLaunch.value = false
  }
}

async function loadPlans() {
  loadingPlans.value = true
  try {
    const res = await superAdminPlatformApi.getMonetizationPlans()
    plans.value = (res.data?.data || []).map((p) => ({ ...p }))
  } finally {
    loadingPlans.value = false
  }
}

async function savePlan(plan) {
  savingPlanId.value = plan.id
  try {
    await superAdminPlatformApi.updateMonetizationPlan(plan.id, {
      storage_quota_mb: plan.storage_quota_mb,
      includes_online_exam: !!plan.includes_online_exam,
      is_active: !!plan.is_active
    })
    toast.success('Berhasil', `Paket ${plan.name} disimpan`)
    await loadSummary()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menyimpan paket')
  } finally {
    savingPlanId.value = null
  }
}

async function loadAddons() {
  loadingAddons.value = true
  try {
    const res = await superAdminPlatformApi.getMonetizationAddons()
    addons.value = (res.data?.data || []).map((a) => ({ ...a }))
  } finally {
    loadingAddons.value = false
  }
}

async function saveAddon(addon) {
  savingAddonId.value = addon.id
  try {
    await superAdminPlatformApi.updateMonetizationAddon(addon.id, {
      storage_mb: addon.storage_mb,
      is_active: !!addon.is_active
    })
    toast.success('Berhasil', `Add-on ${addon.name} disimpan`)
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menyimpan add-on')
  } finally {
    savingAddonId.value = null
  }
}

function syncInstForm(inst) {
  const grants = inst.addon_grants || []
  const online = grants.find((g) => g.addon_key === 'online_exam')
  const storage = grants.find((g) => g.addon_key === 'storage_upgrade')
  instForms[inst.id] = {
    subscription_plan_id: inst.subscription?.plan?.id ?? null,
    status: inst.subscription?.status || 'manual',
    grant_online_exam: !!(online && online.currently_active),
    grant_storage: !!(storage && storage.currently_active),
    storage_mb: storage?.storage_mb ?? inst.storage?.addon_mb ?? 10240
  }
}

async function loadInstitutions() {
  loadingInstitutions.value = true
  try {
    const res = await superAdminPlatformApi.getMonetizationInstitutions({
      search: institutionSearch.value || undefined,
      per_page: 20
    })
    institutions.value = res.data?.data || []
    institutions.value.forEach(syncInstForm)
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal memuat institusi')
  } finally {
    loadingInstitutions.value = false
  }
}

async function saveSubscription(inst) {
  const form = instForms[inst.id]
  try {
    await superAdminPlatformApi.upsertInstitutionSubscription(inst.id, {
      subscription_plan_id: form.subscription_plan_id,
      status: form.status
    })
    toast.success('Berhasil', `Paket ${inst.name} disimpan`)
    await loadInstitutions()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menyimpan paket')
  }
}

async function saveAddons(inst) {
  const form = instForms[inst.id]
  try {
    await superAdminPlatformApi.upsertInstitutionAddon(inst.id, {
      addon_key: 'online_exam',
      is_active: !!form.grant_online_exam,
      source: 'manual'
    })
    await superAdminPlatformApi.upsertInstitutionAddon(inst.id, {
      addon_key: 'storage_upgrade',
      is_active: !!form.grant_storage,
      storage_mb: form.storage_mb,
      source: 'manual'
    })
    toast.success('Berhasil', `Grant ${inst.name} disimpan`)
    await loadInstitutions()
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal menyimpan grant')
  }
}
</script>

<style scoped>
.monetization-page {
  width: 100%;
  max-width: 980px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0 0 4px;
  font-size: 24px;
  color: #0f172a;
}

.page-header p,
.muted {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.panel-spaced {
  margin-top: 16px;
}

.panel-header {
  margin-bottom: 16px;
}

.panel-header h3 {
  margin: 0 0 4px;
  font-size: 17px;
  color: #0f172a;
}

.panel-header p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.switch-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  font-weight: 500;
}

.form-group {
  margin-bottom: 12px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  color: #334155;
}

.form-control,
.input-sm {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 14px;
}

.input-sm {
  width: 110px;
}

.actions {
  margin-top: 8px;
}

.btn-primary,
.btn-secondary,
.btn-link,
.btn-compact {
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
}

.btn-primary {
  background: #0f766e;
  color: #fff;
  padding: 9px 14px;
}

.btn-secondary {
  background: #e2e8f0;
  color: #0f172a;
  padding: 9px 14px;
}

.btn-compact {
  padding: 8px 12px;
}

.btn-link {
  background: transparent;
  color: #0f766e;
  padding: 0;
}

.btn-primary:disabled,
.btn-secondary:disabled,
.btn-link:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-error {
  color: #b91c1c;
  font-size: 13px;
}

.form-success {
  color: #047857;
  font-size: 13px;
}

.badge-ok,
.badge-warn {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
}

.badge-ok {
  background: #ecfdf5;
  color: #047857;
}

.badge-warn {
  background: #fff7ed;
  color: #c2410c;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: left;
  padding: 10px 8px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 13px;
  vertical-align: middle;
}

th {
  color: #64748b;
  font-weight: 600;
}

code {
  font-size: 12px;
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
}

.toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 14px;
}

.inst-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.inst-card {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 14px;
  background: #f8fafc;
}

.inst-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.inst-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
  align-items: end;
  margin-bottom: 8px;
}

.actions-inline {
  display: flex;
  align-items: end;
}

.chip {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 999px;
  white-space: nowrap;
}

.chip-ok {
  background: #ecfdf5;
  color: #047857;
}

.chip-mute {
  background: #f1f5f9;
  color: #64748b;
}
</style>
