<template>
  <div class="filters filters-inline">
    <input
      :model-value="modelValue.search"
      @input="onSearchInput"
      placeholder="Cari nama, NIP, atau NUPTK..."
      class="search-input"
    />
    <select
      :model-value="modelValue.status"
      @change="onChange('status', $event)"
      class="filter-select"
    >
      <option value="">Semua Status</option>
      <option value="Aktif">Aktif</option>
      <option value="Cuti">Cuti</option>
      <option value="Pensiun">Pensiun</option>
      <option value="Pindah">Pindah</option>
      <option value="Mengundurkan Diri">Mengundurkan Diri</option>
      <option value="Tidak Aktif">Tidak Aktif</option>
    </select>
    <select
      :model-value="modelValue.type"
      @change="onChange('type', $event)"
      class="filter-select"
    >
      <option value="">Semua Tipe</option>
      <option value="Guru">Guru</option>
      <option value="Staff">Staff</option>
      <option value="Tenaga Administrasi">Tenaga Administrasi</option>
      <option value="Tenaga Kebersihan">Tenaga Kebersihan</option>
      <option value="Tenaga Keamanan">Tenaga Keamanan</option>
      <option value="Lainnya">Lainnya</option>
    </select>
    <select
      :model-value="modelValue.employment_status"
      @change="onChange('employment_status', $event)"
      class="filter-select"
    >
      <option value="">Semua Kepegawaian</option>
      <option value="PNS">PNS</option>
      <option value="CPNS">CPNS</option>
      <option value="Guru Tetap Yayasan">Guru Tetap Yayasan</option>
      <option value="Guru Honor Sekolah">Guru Honor Sekolah</option>
      <option value="Guru Kontrak">Guru Kontrak</option>
      <option value="Pegawai Tetap Yayasan">Pegawai Tetap Yayasan</option>
      <option value="Pegawai Honor">Pegawai Honor</option>
      <option value="Pegawai Kontrak">Pegawai Kontrak</option>
    </select>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'filter'])

function onSearchInput(e) {
  const next = { ...props.modelValue, search: e.target.value }
  emit('update:modelValue', next)
  emit('filter')
}

function onChange(field, e) {
  const value = e.target.value
  const next = { ...props.modelValue, [field]: value }
  emit('update:modelValue', next)
  emit('filter')
}
</script>

<style scoped>
.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  background: #fff;
  padding: 12px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.search-input,
.filter-select {
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  background: #f8fafc;
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #059669;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
}

.search-input {
  flex: 1;
  min-width: 180px;
}

.filter-select {
  min-width: 140px;
  max-width: 190px;
}

@media (max-width: 768px) {
  .filters {
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .search-input {
    min-width: 160px;
  }

  .filter-select {
    min-width: 130px;
    flex-shrink: 0;
  }
}
</style>
