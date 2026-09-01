<script setup>
import { computed, onMounted, ref } from 'vue'
import { kopService } from '../services/kopService'
import { asetTandaTanganService } from '../services/asetTandaTanganService'
import {
  DEFAULT_LETTER_TYPE_CODE,
  LETTER_TYPES,
  exampleLetterNumber
} from '../utils/letterTypes'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      tampilkan_kop: true,
      kop_id: null,
      tampilkan_tanda_tangan: false,
      tanda_tangan_id: null,
      tampilkan_stempel: false,
      stempel_id: null,
      posisi_ttd: 'kanan'
    })
  },
  letterTypeCode: { type: String, default: DEFAULT_LETTER_TYPE_CODE },
  disabledLetterType: { type: Boolean, default: false },
  open: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'update:letterTypeCode', 'close'])

const nomorContoh = computed(() => exampleLetterNumber(props.letterTypeCode || DEFAULT_LETTER_TYPE_CODE))

const kops = ref([])
const ttds = ref([])
const stempelList = ref([])
const loading = ref(false)

const local = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

function patch(key, value) {
  local.value = { ...local.value, [key]: value }
}

async function loadAssets() {
  loading.value = true
  try {
    const [kopRes, ttdRes, stempelRes] = await Promise.all([
      kopService.list({ status: 'aktif' }),
      asetTandaTanganService.list({ jenis: 'tanda_tangan', status: 'aktif' }),
      asetTandaTanganService.list({ jenis: 'stempel', status: 'aktif' })
    ])
    kops.value = kopRes.data?.data || []
    ttds.value = ttdRes.data?.data || []
    stempelList.value = stempelRes.data?.data || []
  } catch {
    /* ignore */
  } finally {
    loading.value = false
  }
}

onMounted(loadAssets)
defineExpose({ reload: loadAssets })
</script>

<template>
  <aside v-if="open" class="options-panel no-print">
    <header class="panel-head">
      <h4>Opsi Surat</h4>
      <button type="button" class="close-btn" aria-label="Tutup" @click="emit('close')">×</button>
    </header>

    <p v-if="loading" class="muted">Memuat...</p>

    <div class="panel-body">
      <section class="opt-block">
        <span class="opt-label-text">Jenis surat (penomoran)</span>
        <select
          class="opt-select"
          :value="letterTypeCode || DEFAULT_LETTER_TYPE_CODE"
          :disabled="disabledLetterType"
          @change="emit('update:letterTypeCode', $event.target.value)"
        >
          <option v-for="t in LETTER_TYPES" :key="t.code" :value="t.code">
            {{ t.code }} — {{ t.name }}
          </option>
        </select>
        <p class="nomor-note">
          Nomor resmi digenerate saat Terbitkan.
          Contoh: <code>{{ nomorContoh }}</code>
        </p>
      </section>

      <section class="opt-block">
        <label class="opt-row">
          <input
            type="checkbox"
            :checked="local.tampilkan_kop"
            @change="patch('tampilkan_kop', $event.target.checked)"
          />
          <span>KOP surat</span>
        </label>
        <select
          v-if="local.tampilkan_kop"
          class="opt-select"
          :value="local.kop_id || ''"
          @change="patch('kop_id', $event.target.value ? Number($event.target.value) : null)"
        >
          <option value="">Kop Standar Institusi</option>
          <option v-for="k in kops.filter((item) => !item.is_default)" :key="k.id" :value="k.id">{{ k.nama }}</option>
        </select>
      </section>

      <section class="opt-block">
        <label class="opt-row">
          <input
            type="checkbox"
            :checked="local.tampilkan_tanda_tangan"
            @change="patch('tampilkan_tanda_tangan', $event.target.checked)"
          />
          <span>Tanda tangan</span>
        </label>
        <select
          v-if="local.tampilkan_tanda_tangan"
          class="opt-select"
          :value="local.tanda_tangan_id || ''"
          @change="patch('tanda_tangan_id', $event.target.value ? Number($event.target.value) : null)"
        >
          <option value="">Pilih TTD</option>
          <option v-for="t in ttds" :key="t.id" :value="t.id">{{ t.nama }}</option>
        </select>
      </section>

      <section class="opt-block">
        <label class="opt-row">
          <input
            type="checkbox"
            :checked="local.tampilkan_stempel"
            @change="patch('tampilkan_stempel', $event.target.checked)"
          />
          <span>Stempel</span>
        </label>
        <select
          v-if="local.tampilkan_stempel"
          class="opt-select"
          :value="local.stempel_id || ''"
          @change="patch('stempel_id', $event.target.value ? Number($event.target.value) : null)"
        >
          <option value="">Pilih stempel</option>
          <option v-for="s in stempelList" :key="s.id" :value="s.id">{{ s.nama }}</option>
        </select>
      </section>

      <section v-if="local.tampilkan_tanda_tangan || local.tampilkan_stempel" class="opt-block">
        <span class="opt-label-text">Posisi</span>
        <div class="seg">
          <button
            type="button"
            class="seg-btn"
            :class="{ active: local.posisi_ttd === 'kiri' }"
            @click="patch('posisi_ttd', 'kiri')"
          >Kiri</button>
          <button
            type="button"
            class="seg-btn"
            :class="{ active: local.posisi_ttd !== 'kiri' }"
            @click="patch('posisi_ttd', 'kanan')"
          >Kanan</button>
        </div>
      </section>
    </div>
  </aside>
</template>

<style scoped>
.options-panel {
  width: 260px;
  flex-shrink: 0;
  background: #fff;
  border-left: 1px solid #e0e0e0;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid #eee;
}

.panel-head h4 {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
}

.close-btn {
  border: none;
  background: transparent;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: #5f6368;
  padding: 0 4px;
}

.panel-body {
  padding: 12px 14px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.opt-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.opt-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  cursor: pointer;
}

.opt-label-text {
  font-size: 12px;
  color: #5f6368;
}

.opt-select {
  width: 100%;
  border: 1px solid #dadce0;
  border-radius: 8px;
  padding: 7px 8px;
  font-size: 13px;
  background: #fff;
}

.opt-select:disabled {
  background: #f1f3f4;
  color: #5f6368;
}

.nomor-note {
  margin: 6px 0 0;
  font-size: 11px;
  color: #5f6368;
  line-height: 1.4;
}

.nomor-note code {
  display: inline-block;
  margin-top: 2px;
  font-size: 10px;
  font-family: ui-monospace, monospace;
  color: #174ea6;
  background: #e8f0fe;
  padding: 2px 5px;
  border-radius: 4px;
  word-break: break-all;
}

.seg {
  display: flex;
  border: 1px solid #dadce0;
  border-radius: 8px;
  overflow: hidden;
}

.seg-btn {
  flex: 1;
  border: none;
  background: #fff;
  padding: 7px;
  font-size: 12px;
  cursor: pointer;
}

.seg-btn + .seg-btn {
  border-left: 1px solid #dadce0;
}

.seg-btn.active {
  background: #e8f0fe;
  color: #1a73e8;
  font-weight: 600;
}

.muted {
  font-size: 12px;
  color: #80868b;
  margin: 8px 14px;
}

@media (max-width: 900px) {
  .options-panel {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    z-index: 30;
    box-shadow: -8px 0 24px rgba(0, 0, 0, 0.12);
  }
}

@media (max-width: 768px) {
  .options-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: auto;
    bottom: 0;
    width: 100%;
    height: auto;
    max-height: min(70vh, 420px);
    border-left: none;
    border-top: 1px solid #e0e0e0;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -8px 28px rgba(0, 0, 0, 0.14);
  }

  .panel-head {
    padding: 14px 16px;
  }

  .panel-body {
    padding: 12px 16px 20px;
    padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
  }

  .opt-select {
    min-height: 42px;
    font-size: 14px;
  }

  .seg-btn {
    min-height: 40px;
    font-size: 13px;
  }
}
</style>
