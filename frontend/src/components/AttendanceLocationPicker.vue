<template>
  <div class="attendance-location-picker">
    <div class="form-section-label">Lokasi (untuk absensi)</div>
    <p class="picker-hint">
      Koordinat dipakai validasi scan QR. Kosongkan latitude/longitude jika tidak ingin cek lokasi.
    </p>

    <div class="search-row">
      <div class="form-group grow">
        <label>Cari alamat / nama tempat</label>
        <input
          v-model="searchQuery"
          type="search"
          class="form-input"
          placeholder="Contoh: SMP Negeri 1 Jakarta, Jl. Merdeka Bandung"
          autocomplete="off"
          @keydown.enter.prevent="runSearch"
        />
      </div>
      <div class="search-actions">
        <button type="button" class="btn-secondary btn-compact" :disabled="searching || searchQuery.trim().length < 3" @click="runSearch">
          {{ searching ? 'Mencari...' : 'Cari' }}
        </button>
        <button type="button" class="btn-secondary btn-compact" :disabled="gpsLoading" @click="useCurrentLocation">
          {{ gpsLoading ? 'Mengambil...' : 'Lokasi saya' }}
        </button>
      </div>
    </div>

    <p v-if="searchError" class="picker-error">{{ searchError }}</p>
    <p v-if="gpsError" class="picker-error">{{ gpsError }}</p>

    <ul v-if="searchResults.length" class="search-results">
      <li v-for="(item, idx) in searchResults" :key="`${item.latitude}-${item.longitude}-${idx}`">
        <button type="button" class="result-btn" @click="selectResult(item)">
          <span class="result-name">{{ item.display_name }}</span>
          <span class="result-coords">{{ formatCoord(item.latitude) }}, {{ formatCoord(item.longitude) }}</span>
        </button>
      </li>
    </ul>

    <div class="form-row">
      <div class="form-group">
        <label>Latitude</label>
        <input v-model.number="latitudeModel" type="number" step="any" placeholder="-6.xxxx" class="form-input" />
      </div>
      <div class="form-group">
        <label>Longitude</label>
        <input v-model.number="longitudeModel" type="number" step="any" placeholder="106.xxxx" class="form-input" />
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Radius (meter)</label>
        <input
          v-model.number="radiusModel"
          type="number"
          min="10"
          max="5000"
          placeholder="100"
          class="form-input"
        />
        <small class="form-hint">Radius validasi lokasi absensi (10–5000 m). Rekomendasi awal: 150–300 m.</small>
      </div>
      <div class="form-group form-group-action">
        <label>&nbsp;</label>
        <button type="button" class="btn-secondary btn-compact" :disabled="!hasCoords" @click="clearLocation">
          Hapus koordinat
        </button>
      </div>
    </div>

    <div v-if="hasCoords" class="map-preview">
      <iframe
        :src="mapUrl"
        title="Peta lokasi sekolah"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
      />
      <a :href="mapLink" target="_blank" rel="noopener noreferrer" class="map-link">Buka di OpenStreetMap</a>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { geocodeApi } from '@/api/geocode'
import {
  getCurrentCoordinates,
  hasValidCoordinates,
  openStreetMapEmbedUrl,
} from '@/utils/geolocation'

const latitudeModel = defineModel('latitude', { type: [Number, String, null], default: null })
const longitudeModel = defineModel('longitude', { type: [Number, String, null], default: null })
const radiusModel = defineModel('locationRadius', { type: [Number, String, null], default: null })

const props = defineProps({
  addressHint: { type: String, default: '' },
})

const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const searchError = ref('')
const gpsLoading = ref(false)
const gpsError = ref('')

const hasCoords = computed(() => hasValidCoordinates(latitudeModel.value, longitudeModel.value))

const mapUrl = computed(() => {
  if (!hasCoords.value) return ''
  return openStreetMapEmbedUrl(latitudeModel.value, longitudeModel.value)
})

const mapLink = computed(() => {
  if (!hasCoords.value) return '#'
  return `https://www.openstreetmap.org/?mlat=${latitudeModel.value}&mlon=${longitudeModel.value}#map=17/${latitudeModel.value}/${longitudeModel.value}`
})

function formatCoord(value) {
  return Number(value).toFixed(6)
}

function applyCoordinates(lat, lng) {
  latitudeModel.value = Number(lat)
  longitudeModel.value = Number(lng)
  if (radiusModel.value == null || radiusModel.value === '') {
    radiusModel.value = 200
  }
  searchResults.value = []
  gpsError.value = ''
  searchError.value = ''
}

function selectResult(item) {
  applyCoordinates(item.latitude, item.longitude)
  searchQuery.value = item.display_name
}

async function runSearch() {
  const q = searchQuery.value.trim()
  if (q.length < 3) {
    searchError.value = 'Ketik minimal 3 karakter untuk mencari.'
    return
  }
  searching.value = true
  searchError.value = ''
  searchResults.value = []
  try {
    const hint = props.addressHint?.trim()
    const query = hint && !q.toLowerCase().includes(hint.slice(0, 12).toLowerCase())
      ? `${q}, ${hint}`
      : q
    const res = await geocodeApi.search(query, 5)
    searchResults.value = res.data.data || []
    if (!searchResults.value.length) {
      searchError.value = res.data.message || 'Lokasi tidak ditemukan. Coba kata kunci lain.'
    }
  } catch (e) {
    searchError.value = e.response?.data?.message || e.formattedMessage || 'Gagal mencari lokasi.'
  } finally {
    searching.value = false
  }
}

async function useCurrentLocation() {
  gpsLoading.value = true
  gpsError.value = ''
  try {
    const coords = await getCurrentCoordinates()
    applyCoordinates(coords.latitude, coords.longitude)
    searchQuery.value = `Lokasi saat ini (±${Math.round(coords.accuracy || 0)} m)`
  } catch (e) {
    gpsError.value = e.message || 'Gagal mengambil lokasi.'
  } finally {
    gpsLoading.value = false
  }
}

function clearLocation() {
  latitudeModel.value = null
  longitudeModel.value = null
  searchResults.value = []
  searchError.value = ''
  gpsError.value = ''
}
</script>

<style scoped>
.attendance-location-picker { margin-bottom: 0.5rem; }
.picker-hint {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  color: #64748b;
}
.search-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: flex-end;
  margin-bottom: 0.5rem;
}
.grow { flex: 1; min-width: 220px; }
.search-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.form-group { flex: 1; min-width: 160px; }
.form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.35rem;
  font-size: 0.85rem;
}
.form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
}
.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: #64748b;
}
.form-group-action { flex: 0 0 auto; }
.picker-error {
  margin: 0.25rem 0 0.5rem;
  font-size: 0.85rem;
  color: #991b1b;
}
.search-results {
  list-style: none;
  margin: 0 0 0.75rem;
  padding: 0;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.result-btn {
  width: 100%;
  text-align: left;
  padding: 0.6rem 0.75rem;
  border: none;
  border-bottom: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
}
.result-btn:last-child { border-bottom: none; }
.result-btn:hover { background: #f0fdf4; }
.result-name {
  display: block;
  font-size: 0.88rem;
  color: #1e293b;
}
.result-coords {
  display: block;
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.15rem;
}
.map-preview {
  margin-top: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}
.map-preview iframe {
  display: block;
  width: 100%;
  height: 220px;
  border: 0;
}
.map-link {
  display: block;
  padding: 0.45rem 0.75rem;
  font-size: 0.82rem;
  color: #059669;
  text-decoration: none;
  background: #f8fafc;
}
.map-link:hover { text-decoration: underline; }
.btn-secondary {
  padding: 0.45rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 0.85rem;
}
.btn-secondary:disabled { opacity: 0.55; cursor: not-allowed; }
.btn-compact { white-space: nowrap; }
</style>
