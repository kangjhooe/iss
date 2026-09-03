<template>
    <div class="inventory-page inventory-qr-scan-page">
      <div class="page-header">
        <div>
          <h1 class="page-title">Scan QR Inventaris</h1>
          <p class="page-subtitle">Scan label QR aset individual untuk melihat detail unit dan lokasi terkini.</p>
        </div>
        <router-link to="/inventory/beranda" class="btn-secondary btn-compact">Kembali ke Inventaris</router-link>
      </div>

      <div class="scan-section">
        <div class="scanner-section">
          <QrScanner @scan="handleQrScan" @error="handleScannerError" />
          <p v-if="busy" class="scan-busy">Memproses QR...</p>
        </div>

        <details class="manual-box">
          <summary>Input manual token QR</summary>
          <input v-model="manualToken" class="form-input" placeholder="Tempel token ISS1...." />
          <button type="button" class="btn-primary" :disabled="busy || !manualToken.trim()" @click="resolveToken(manualToken.trim())">
            Cari Aset
          </button>
        </details>

        <div v-if="scanError" class="scan-result error">
          <h4>Tidak ditemukan</h4>
          <p>{{ scanError }}</p>
        </div>

        <div v-if="asset" class="asset-card">
          <div class="asset-card-header">
            <div>
              <h3>{{ asset.asset_number }}</h3>
              <p class="muted">{{ asset.item?.name || '-' }} · {{ asset.item?.code || '-' }}</p>
            </div>
            <div class="asset-badges">
              <span :class="getConditionClass(asset.condition)">{{ asset.condition }}</span>
              <span :class="getStatusClass(asset.status)">{{ asset.status }}</span>
            </div>
          </div>
          <div class="asset-grid">
            <div><span class="muted">Serial</span><div>{{ asset.serial_number || '-' }}</div></div>
            <div><span class="muted">Ruangan</span><div>{{ asset.room?.name || asset.building?.name || '-' }}</div></div>
            <div><span class="muted">Tgl Perolehan</span><div>{{ formatDate(asset.purchase_date) }}</div></div>
            <div><span class="muted">Nilai</span><div>{{ formatCurrency(asset.purchase_price || asset.item?.purchase_price) }}</div></div>
          </div>
          <div class="asset-actions">
            <button type="button" class="btn-secondary btn-compact" :disabled="exportingKib" @click="exportKib">
              {{ exportingKib ? 'Menyiapkan...' : 'Cetak KIB Unit' }}
            </button>
            <router-link to="/inventory/aset" class="btn-primary btn-compact">
              Buka Daftar Aset
            </router-link>
          </div>
        </div>

        <div v-if="recentScans.length" class="recent-scans">
          <h4>Baru saja di-scan</h4>
          <ul>
            <li v-for="row in recentScans" :key="row.id + '-' + row.scannedAt">
              <button type="button" class="recent-btn" @click="showRecent(row)">
                {{ row.asset_number }} — {{ row.item?.name || 'Aset' }}
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import QrScanner from '@/components/QrScanner.vue'
import { inventoryApi } from '@/api/inventory'
import { openPdfBlob } from '@/utils/pdfPreview'
import { formatDate, formatCurrency, getConditionClass, getStatusClass } from '@/composables/inventory/inventoryFormatters'
import { useToast } from '@/composables/useToast'
import '@/assets/inventory-page.css'

const toast = useToast()
const busy = ref(false)
const manualToken = ref('')
const asset = ref(null)
const scanError = ref('')
const exportingKib = ref(false)
const recentScans = ref([])

function extractToken(raw) {
  const text = String(raw || '').trim()
  if (!text) return ''
  if (text.startsWith('ISS1.')) return text
  const match = text.match(/ISS1\.[A-Za-z0-9._-]+/)
  return match ? match[0] : text
}

async function resolveToken(raw) {
  const token = extractToken(raw)
  if (!token) return
  busy.value = true
  scanError.value = ''
  try {
    const res = await inventoryApi.resolveAssetQr(token)
    const data = res.data?.data ?? res.data ?? null
    if (!data?.id) throw new Error('Aset tidak ditemukan')
    asset.value = data
    recentScans.value = [
      { ...data, scannedAt: Date.now() },
      ...recentScans.value.filter((r) => r.id !== data.id)
    ].slice(0, 5)
    toast.success('Berhasil', `Aset ${data.asset_number} ditemukan`)
  } catch (err) {
    asset.value = null
    scanError.value = err.formattedMessage || err.response?.data?.message || 'QR aset tidak valid'
  } finally {
    busy.value = false
  }
}

function handleQrScan(text) {
  if (busy.value) return
  resolveToken(text)
}

function handleScannerError() {
  // Pesan kamera sudah tampil di komponen scanner
}

function showRecent(row) {
  asset.value = row
  scanError.value = ''
}

async function exportKib() {
  if (!asset.value?.id) return
  exportingKib.value = true
  try {
    const response = await inventoryApi.exportAssetKib(asset.value.id)
    if (!openPdfBlob(response, `kib-aset-${asset.value.asset_number || asset.value.id}.pdf`)) {
      toast.error('Gagal', 'Pop-up diblokir. Izinkan tab baru untuk melihat preview PDF.')
    }
  } catch (err) {
    toast.error('Gagal', err.formattedMessage || 'Gagal cetak KIB')
  } finally {
    exportingKib.value = false
  }
}
</script>

<style scoped>
.inventory-qr-scan-page { width: 100%; padding: 1.5rem; max-width: 760px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; }
.page-title { margin: 0 0 0.35rem; font-size: 1.5rem; }
.page-subtitle { margin: 0; color: #64748b; }
.scan-section { display: flex; flex-direction: column; gap: 1rem; }
.scan-busy { text-align: center; color: #047857; font-weight: 600; }
.manual-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; }
.manual-box summary { cursor: pointer; font-weight: 600; margin-bottom: 0.5rem; }
.manual-box .form-input { width: 100%; margin: 0.5rem 0; }
.scan-result { padding: 0.9rem 1rem; border-radius: 8px; }
.scan-result.error { background: #fee2e2; color: #991b1b; }
.scan-result h4 { margin: 0 0 0.35rem; }
.asset-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.1rem; background: #fff; }
.asset-card-header { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.85rem; }
.asset-card-header h3 { margin: 0; }
.asset-badges { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.asset-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 14px; margin-bottom: 1rem; }
.asset-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.recent-scans h4 { margin: 0 0 0.5rem; }
.recent-scans ul { list-style: none; padding: 0; margin: 0; }
.recent-btn { width: 100%; text-align: left; padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; cursor: pointer; margin-bottom: 0.35rem; }
</style>
