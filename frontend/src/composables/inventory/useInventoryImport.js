import * as XLSX from 'xlsx'
import { inventoryApi } from '@/api/inventory'

function cellToText(value) {
  if (value === undefined || value === null) return null
  if (typeof value === 'number') {
    if (!Number.isFinite(value)) return null
    if (Number.isInteger(value) || Math.floor(value) === value) {
      return String(Math.trunc(value))
    }
    return String(value)
  }
  const s = String(value).trim()
  return s === '' ? null : s
}

function cellToInt(value) {
  const s = cellToText(value)
  if (s === null || !/^-?\d+$/.test(s)) return null
  return parseInt(s, 10)
}

export function mapInventoryImportRow(row) {
  const get = (...keys) => {
    for (const key of keys) {
      if (row[key] !== undefined && row[key] !== null && String(row[key]).trim() !== '') {
        return row[key]
      }
    }
    const lowerMap = {}
    Object.keys(row || {}).forEach((k) => {
      lowerMap[String(k).toLowerCase().replace(/[\s-]+/g, '_')] = row[k]
    })
    for (const key of keys) {
      const normalized = String(key).toLowerCase().replace(/[\s-]+/g, '_')
      if (lowerMap[normalized] !== undefined && lowerMap[normalized] !== null && String(lowerMap[normalized]).trim() !== '') {
        return lowerMap[normalized]
      }
    }
    return null
  }

  return {
    kode_kategori: cellToText(get('kode_kategori', 'Kode Kategori')),
    nama: cellToText(get('nama', 'Nama')),
    tipe_pelacakan: cellToText(get('tipe_pelacakan', 'Tipe Pelacakan')),
    kode_barang: cellToText(get('kode_barang', 'Kode Barang')),
    merk: cellToText(get('merk', 'Merk')),
    model: cellToText(get('model', 'Model')),
    jumlah: cellToInt(get('jumlah', 'Jumlah')),
    satuan: cellToText(get('satuan', 'Satuan')),
    kondisi: cellToText(get('kondisi', 'Kondisi')),
    status: cellToText(get('status', 'Status')),
    tanggal_beli: cellToText(get('tanggal_beli', 'Tanggal Beli')),
    harga_beli: cellToText(get('harga_beli', 'Harga Beli')),
    supplier: cellToText(get('supplier', 'Supplier')),
    sumber_dana: cellToText(get('sumber_dana', 'Sumber Dana')),
    cara_perolehan: cellToText(get('cara_perolehan', 'Cara Perolehan')),
    ruangan: cellToText(get('ruangan', 'Ruangan')),
    catatan_lokasi: cellToText(get('catatan_lokasi', 'Catatan Lokasi')),
    deskripsi: cellToText(get('deskripsi', 'Deskripsi'))
  }
}

export async function downloadInventoryImportTemplate(categories = []) {
  let sampleCode = categories?.[0]?.code || 'ELK'
  try {
    const meta = await inventoryApi.getImportTemplate()
    sampleCode = meta.data?.data?.sample_kode_kategori || sampleCode
  } catch {
    // fallback sample code
  }

  const templateData = [{
    kode_kategori: sampleCode,
    nama: 'Contoh Laptop Lab',
    tipe_pelacakan: 'stock',
    kode_barang: '',
    merk: 'Lenovo',
    model: 'ThinkPad E14',
    jumlah: 5,
    satuan: 'Unit',
    kondisi: 'Baik',
    status: 'Tersedia',
    tanggal_beli: '2024-01-15',
    harga_beli: 8500000,
    supplier: 'Toko IT',
    sumber_dana: 'BOS',
    cara_perolehan: 'Pembelian',
    ruangan: '',
    catatan_lokasi: '',
    deskripsi: 'Isi opsional'
  }]

  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.json_to_sheet(templateData)
  ws['!cols'] = [
    { wch: 14 }, { wch: 28 }, { wch: 14 }, { wch: 16 }, { wch: 14 }, { wch: 14 },
    { wch: 8 }, { wch: 8 }, { wch: 14 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
    { wch: 16 }, { wch: 12 }, { wch: 18 }, { wch: 16 }, { wch: 18 }, { wch: 24 }
  ]
  XLSX.utils.book_append_sheet(wb, ws, 'Master Barang')
  XLSX.writeFile(wb, 'template_import_inventaris.xlsx')
}

export function parseInventoryExcelFile(arrayBuffer) {
  const workbook = XLSX.read(arrayBuffer, { type: 'array', cellDates: true })
  const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
  const jsonData = XLSX.utils.sheet_to_json(firstSheet, { defval: '', raw: true })
  return jsonData.map(mapInventoryImportRow).filter((row) => row.kode_kategori || row.nama)
}

export function downloadExcelBlob(res, fallbackName) {
  const blob = new Blob([res.data], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  const disposition = res.headers?.['content-disposition'] || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)
  a.download = match?.[1] || fallbackName
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}
