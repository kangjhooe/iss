<template>
  <div class="address-cascade">
    <div class="ac-group ac-full">
      <label>{{ streetLabel }}</label>
      <textarea
        :value="field('address')"
        rows="2"
        :placeholder="streetPlaceholder"
        @input="set('address', $event.target.value)"
      />
    </div>

    <p v-if="savedRegionHint" class="ac-hint">
      Wilayah tersimpan: {{ savedRegionHint }}. Pilih ulang dari daftar jika ingin memperbarui.
    </p>
    <p v-if="loadError" class="ac-hint ac-warn">{{ loadError }}</p>

    <template v-if="!manualMode">
      <div class="ac-row">
        <div class="ac-group">
          <label>Provinsi</label>
          <select
            :value="field('wilayah_province_code')"
            :disabled="loadingProvinces"
            @change="onProvince"
          >
            <option value="">{{ loadingProvinces ? 'Memuat…' : 'Pilih provinsi' }}</option>
            <option v-for="item in provinces" :key="item.code" :value="item.code">{{ item.name }}</option>
          </select>
        </div>
        <div class="ac-group">
          <label>Kabupaten/Kota</label>
          <select
            :value="field('wilayah_regency_code')"
            :disabled="!field('wilayah_province_code') || loadingRegencies"
            @change="onRegency"
          >
            <option value="">{{ loadingRegencies ? 'Memuat…' : 'Pilih kabupaten/kota' }}</option>
            <option v-for="item in regencies" :key="item.code" :value="item.code">{{ item.name }}</option>
          </select>
        </div>
      </div>

      <div class="ac-row">
        <div class="ac-group">
          <label>Kecamatan</label>
          <select
            :value="field('wilayah_district_code')"
            :disabled="!field('wilayah_regency_code') || loadingDistricts"
            @change="onDistrict"
          >
            <option value="">{{ loadingDistricts ? 'Memuat…' : 'Pilih kecamatan' }}</option>
            <option v-for="item in districts" :key="item.code" :value="item.code">{{ item.name }}</option>
          </select>
        </div>
        <div class="ac-group">
          <label>Desa/Kelurahan/Pekon</label>
          <select
            :value="field('wilayah_village_code')"
            :disabled="!field('wilayah_district_code') || loadingVillages"
            @change="onVillage"
          >
            <option value="">{{ loadingVillages ? 'Memuat…' : 'Pilih desa/kelurahan/pekon' }}</option>
            <option v-for="item in villages" :key="item.code" :value="item.code">{{ item.name }}</option>
          </select>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="ac-row">
        <div class="ac-group">
          <label>Provinsi</label>
          <input :value="field('province')" @input="onManualName('province', $event.target.value)" />
        </div>
        <div class="ac-group">
          <label>Kabupaten/Kota</label>
          <input :value="field('district')" @input="onManualName('district', $event.target.value)" />
        </div>
      </div>
      <div class="ac-row">
        <div class="ac-group">
          <label>Kecamatan</label>
          <input :value="field('sub_district')" @input="onManualName('sub_district', $event.target.value)" />
        </div>
        <div class="ac-group">
          <label>Desa/Kelurahan/Pekon</label>
          <input :value="field('village')" @input="onManualName('village', $event.target.value)" />
        </div>
      </div>
    </template>

    <div v-if="showPostalCode" class="ac-row">
      <div class="ac-group">
        <label>Kode Pos</label>
        <input
          :value="field('postal_code')"
          maxlength="10"
          placeholder="Opsional"
          @input="set('postal_code', $event.target.value)"
        />
      </div>
    </div>

    <button type="button" class="ac-toggle-btn" @click="toggleManual">
      {{ manualMode ? 'Pilih dari daftar wilayah' : 'Isi nama wilayah secara manual' }}
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { regionsApi } from '@/api/regions'
import { stripTrailingRegions } from '@/utils/addressFields'

defineProps({
  streetLabel: { type: String, default: 'Alamat' },
  streetPlaceholder: { type: String, default: 'Nama jalan, RT/RW, nomor rumah' },
  showPostalCode: { type: Boolean, default: true },
})

const model = defineModel({ default: () => ({}) })

const provinces = ref([])
const regencies = ref([])
const districts = ref([])
const villages = ref([])
const loadingProvinces = ref(false)
const loadingRegencies = ref(false)
const loadingDistricts = ref(false)
const loadingVillages = ref(false)
const loadError = ref('')
const manualMode = ref(false)

function current() {
  const value = model.value
  return value && typeof value === 'object' ? value : {}
}

function field(key) {
  const value = current()[key]
  return value == null ? '' : value
}

function set(key, value) {
  const target = model.value
  if (!target || typeof target !== 'object') return
  target[key] = value
}

function syncStreetFromRegions() {
  const cleaned = stripTrailingRegions(field('address'), [
    field('village'),
    field('sub_district'),
    field('district'),
    field('province'),
    field('postal_code'),
  ])
  if (cleaned !== field('address')) set('address', cleaned)
}

const savedRegionHint = computed(() => {
  if (manualMode.value) return ''
  if (field('wilayah_province_code')) return ''
  const parts = [field('province'), field('district'), field('sub_district'), field('village')]
    .map((part) => String(part).trim())
    .filter(Boolean)
  return parts.join(', ')
})

function toggleManual() {
  manualMode.value = !manualMode.value
}

function clearFrom(level) {
  if (level === 'province') {
    set('wilayah_regency_code', '')
    set('district', '')
  }
  if (level === 'province' || level === 'regency') {
    set('wilayah_district_code', '')
    set('sub_district', '')
  }
  if (level === 'province' || level === 'regency' || level === 'district') {
    set('wilayah_village_code', '')
    set('village', '')
  }
  if (level === 'province') {
    regencies.value = []
    districts.value = []
    villages.value = []
  } else if (level === 'regency') {
    districts.value = []
    villages.value = []
  } else if (level === 'district') {
    villages.value = []
  }
}

function listFromResponse(res) {
  const body = res?.data
  return Array.isArray(body?.data) ? body.data : (Array.isArray(body) ? body : [])
}

async function loadProvinces() {
  loadingProvinces.value = true
  loadError.value = ''
  try {
    const res = await regionsApi.provinces()
    provinces.value = listFromResponse(res)
    if (!provinces.value.length) {
      loadError.value = 'Daftar provinsi kosong. Isi nama secara manual jika perlu.'
    }
  } catch (err) {
    provinces.value = []
    loadError.value = err.formattedMessage || 'Daftar wilayah tidak tersedia. Isi nama secara manual.'
    manualMode.value = true
  } finally {
    loadingProvinces.value = false
  }
}

async function loadRegencies(code) {
  if (!code) {
    regencies.value = []
    return
  }
  loadingRegencies.value = true
  try {
    const res = await regionsApi.regencies(code)
    regencies.value = listFromResponse(res)
  } catch {
    regencies.value = []
    loadError.value = 'Gagal memuat kabupaten/kota. Isi nama secara manual jika perlu.'
  } finally {
    loadingRegencies.value = false
  }
}

async function loadDistricts(code) {
  if (!code) {
    districts.value = []
    return
  }
  loadingDistricts.value = true
  try {
    const res = await regionsApi.districts(code)
    districts.value = listFromResponse(res)
  } catch {
    districts.value = []
    loadError.value = 'Gagal memuat kecamatan. Isi nama secara manual jika perlu.'
  } finally {
    loadingDistricts.value = false
  }
}

async function loadVillages(code) {
  if (!code) {
    villages.value = []
    return
  }
  loadingVillages.value = true
  try {
    const res = await regionsApi.villages(code)
    villages.value = listFromResponse(res)
  } catch {
    villages.value = []
    loadError.value = 'Gagal memuat desa/kelurahan. Isi nama secara manual jika perlu.'
  } finally {
    loadingVillages.value = false
  }
}

function onManualName(key, value) {
  set(key, value)
  syncStreetFromRegions()
}

function onProvince(event) {
  const code = event.target.value
  const selected = provinces.value.find((item) => item.code === code)
  set('wilayah_province_code', code)
  set('province', selected?.name || '')
  clearFrom('province')
  syncStreetFromRegions()
  loadRegencies(code)
}

function onRegency(event) {
  const code = event.target.value
  const selected = regencies.value.find((item) => item.code === code)
  set('wilayah_regency_code', code)
  set('district', selected?.name || '')
  clearFrom('regency')
  syncStreetFromRegions()
  loadDistricts(code)
}

function onDistrict(event) {
  const code = event.target.value
  const selected = districts.value.find((item) => item.code === code)
  set('wilayah_district_code', code)
  set('sub_district', selected?.name || '')
  clearFrom('district')
  syncStreetFromRegions()
  loadVillages(code)
}

function onVillage(event) {
  const code = event.target.value
  const selected = villages.value.find((item) => item.code === code)
  set('wilayah_village_code', code)
  set('village', selected?.name || '')
  syncStreetFromRegions()
}

watch(manualMode, (manual) => {
  if (!manual) return
  set('wilayah_province_code', '')
  set('wilayah_regency_code', '')
  set('wilayah_district_code', '')
  set('wilayah_village_code', '')
})

onMounted(async () => {
  await loadProvinces()
  if (field('wilayah_province_code')) await loadRegencies(field('wilayah_province_code'))
  if (field('wilayah_regency_code')) await loadDistricts(field('wilayah_regency_code'))
  if (field('wilayah_district_code')) await loadVillages(field('wilayah_district_code'))
  syncStreetFromRegions()
})

watch(() => field('wilayah_province_code'), (code) => {
  if (code) loadRegencies(code)
  else regencies.value = []
})

watch(() => field('wilayah_regency_code'), (code) => {
  if (code) loadDistricts(code)
  else districts.value = []
})

watch(() => field('wilayah_district_code'), (code) => {
  if (code) loadVillages(code)
  else villages.value = []
})
</script>

<style scoped>
.address-cascade {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin: 8px 0 24px;
  width: 100%;
}
.ac-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.ac-group {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.ac-full {
  grid-column: 1 / -1;
}
.ac-group label {
  margin-bottom: 8px;
  color: #333;
  font-weight: 600;
  font-size: 14px;
}
.ac-group input,
.ac-group select,
.ac-group textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  background: #f8fafc;
  font-family: inherit;
}
.ac-group input:focus,
.ac-group select:focus,
.ac-group textarea:focus {
  outline: none;
  border-color: #059669;
  background: #fff;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
}
.ac-group select:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
.ac-hint {
  margin: 0;
  font-size: 13px;
  color: #475569;
}
.ac-warn {
  color: #b45309;
}
.ac-toggle-btn {
  align-self: flex-start;
  padding: 8px 12px;
  border: 1px solid #059669;
  border-radius: 8px;
  background: #fff;
  color: #047857;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.ac-toggle-btn:hover {
  background: #ecfdf5;
}
@media (max-width: 640px) {
  .ac-row {
    grid-template-columns: 1fr;
  }
}
</style>
