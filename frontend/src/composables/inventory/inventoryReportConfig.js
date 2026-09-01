export const INVENTORY_REPORT_GROUPS = [
  {
    id: 'inventaris',
    label: 'Inventaris',
    types: ['summary', 'stock', 'location', 'category', 'asset']
  },
  {
    id: 'operasional',
    label: 'Operasional',
    types: ['transactions', 'asset_movements', 'loaned', 'maintenance', 'damaged']
  },
  {
    id: 'administrasi',
    label: 'Administrasi',
    types: ['disposal']
  }
]

export const INVENTORY_REPORT_META = {
  summary: {
    label: 'Ringkasan',
    hint: 'Gambaran umum inventaris aktif — cocok untuk presentasi ke kepala sekolah atau arsip berkala.',
    pdfNote: 'PDF berisi statistik, estimasi nilai, rusak/hilang, peminjaman, dan daftar stok ringkas.'
  },
  stock: {
    label: 'Stok Barang',
    hint: 'Daftar lengkap barang aktif beserta kondisi, lokasi, dan nilai perolehan.',
    pdfNote: 'PDF menampilkan daftar stok sesuai filter yang dipilih.'
  },
  location: {
    label: 'Per Ruangan',
    hint: 'Audit isi inventaris tiap ruangan — untuk pemeriksaan fisik atau inventarisasi ruang.',
    pdfNote: 'PDF dikelompokkan per ruangan. Barang tanpa ruangan tidak ditampilkan.'
  },
  category: {
    label: 'Per Kategori',
    hint: 'Rekap barang per kategori inventaris (mis. alat tulis, elektronik, furniture).',
    pdfNote: 'PDF dikelompokkan per kategori inventaris.'
  },
  asset: {
    label: 'Estimasi Nilai Perolehan',
    hint: 'Perkiraan nilai barang berdasarkan harga beli × jumlah — bukan laporan keuangan resmi.',
    pdfNote: 'PDF berisi rekap dan rincian nilai perolehan per kategori.'
  },
  damaged: {
    label: 'Rusak / Hilang',
    hint: 'Barang aktif yang kondisinya rusak atau statusnya hilang — belum tentu sudah dihapus resmi.',
    pdfNote: 'Untuk penghapusan administratif (SK/BA), lihat laporan Penghapusan.'
  },
  loaned: {
    label: 'Peminjaman',
    hint: 'Barang yang sedang dipinjam dan belum dikembalikan.',
    pdfNote: 'PDF menampilkan peminjaman aktif dalam periode filter.'
  },
  transactions: {
    label: 'Mutasi / Transaksi',
    hint: 'Riwayat barang masuk, keluar, mutasi, dan penyesuaian stok.',
    pdfNote: 'PDF menampilkan transaksi sesuai periode dan jenis yang dipilih.'
  },
  asset_movements: {
    label: 'Mutasi Aset',
    hint: 'Riwayat perpindahan unit aset individual antar ruangan.',
    pdfNote: 'PDF menampilkan mutasi aset sesuai periode filter.'
  },
  maintenance: {
    label: 'Pemeliharaan',
    hint: 'Jadwal dan riwayat perawatan/perbaikan barang.',
    pdfNote: 'PDF menampilkan data pemeliharaan beserta total biaya.'
  },
  disposal: {
    label: 'Penghapusan',
    hint: 'Barang yang sudah dicatat dihapus secara resmi (dijual, hilang, atau tidak layak) beserta SK/BA.',
    pdfNote: 'PDF berisi riwayat penghapusan administratif. Kartu per barang: cetak KIB dari detail barang.'
  }
}

const FILTER_VISIBILITY = {
  category_id: ['summary', 'stock', 'location', 'category', 'asset', 'damaged', 'loaned', 'transactions', 'maintenance', 'disposal'],
  status: ['summary', 'stock', 'asset'],
  condition: ['summary', 'stock', 'asset'],
  building_id: ['summary', 'stock', 'damaged', 'location', 'category', 'disposal'],
  room_id: ['summary', 'stock', 'damaged', 'location', 'category', 'disposal', 'asset_movements'],
  transaction_type: ['transactions'],
  date_range: ['loaned', 'transactions', 'asset_movements', 'maintenance', 'disposal']
}

export function reportShowsFilter(reportType, filterKey) {
  return (FILTER_VISIBILITY[filterKey] || []).includes(reportType)
}

export function getReportMeta(type) {
  return INVENTORY_REPORT_META[type] || INVENTORY_REPORT_META.summary
}

export function getAllReportTypes() {
  return INVENTORY_REPORT_GROUPS.flatMap((g) => g.types)
}
