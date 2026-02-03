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
      <option value="Pensiun">Pensiun</option>
      <option value="Pindah">Pindah</option>
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
      <option value="">Semua Status Kepegawaian</option>
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
