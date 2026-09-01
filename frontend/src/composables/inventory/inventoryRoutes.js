/** Inventaris — pemetaan tab internal ↔ route (sidebar utama + pintasan Beranda) */

import { hasModuleAccess } from '@/utils/moduleAccess'

/** Tab inventaris yang boleh diakses PJ ruangan tanpa modul inventory penuh */
export const INVENTORY_ROOM_SCOPED_TABS = [
  'dashboard',
  'items',
  'assets',
  'loans',
  'maintenances',
]

/** @deprecated Gunakan INVENTORY_ROOM_SCOPED_TABS */
export const INVENTORY_LAB_ALLOWED_TABS = INVENTORY_ROOM_SCOPED_TABS

const defineItem = (tab, path, label, routeName, { sidebar = false, roomScoped = INVENTORY_ROOM_SCOPED_TABS.includes(tab) } = {}) => ({
  tab,
  path,
  label,
  routeName,
  sidebar,
  roomScoped,
  /** @deprecated */
  labAllowed: roomScoped,
})

export const INVENTORY_NAV_ITEMS = [
  defineItem('dashboard', '/inventory/beranda', 'Beranda', 'InventoryBeranda', { sidebar: true }),
  defineItem('items', '/inventory/barang', 'Master Barang', 'InventoryBarang', { sidebar: true }),
  defineItem('assets', '/inventory/aset', 'Aset Individual', 'InventoryAset', { sidebar: true }),
  defineItem('rooms', '/inventory/ruangan', 'Ruangan', 'InventoryRuangan'),
  defineItem('transfer', '/inventory/mutasi', 'Mutasi', 'InventoryMutasi'),
  defineItem('opname', '/inventory/opname', 'Stock Opname', 'InventoryOpname'),
  defineItem('stock', '/inventory/stok', 'Barang Masuk/Keluar', 'InventoryStok'),
  defineItem('maintenances', '/inventory/pemeliharaan', 'Pemeliharaan', 'InventoryPemeliharaan', { sidebar: true }),
  defineItem('loans', '/inventory/peminjaman', 'Peminjaman', 'InventoryPeminjaman', { sidebar: true }),
  defineItem('disposal', '/inventory/penghapusan', 'Penghapusan', 'InventoryPenghapusan'),
  defineItem('reports', '/inventory/laporan', 'Laporan', 'InventoryLaporan', { sidebar: true }),
  defineItem('categories', '/inventory/kategori', 'Kategori', 'InventoryKategori'),
]

/** Menu utama di sidebar global (6 item) */
export const INVENTORY_SIDEBAR_ITEMS = INVENTORY_NAV_ITEMS.filter((item) => item.sidebar)

/** Pintasan Beranda — fitur di luar sidebar utama */
export const INVENTORY_QUICK_ACTION_ITEMS = [
  ...INVENTORY_NAV_ITEMS.filter((item) => !item.sidebar),
  { tab: 'scan', path: '/inventory/scan', label: 'Scan QR Aset', routeName: 'InventoryQrScan', roomScoped: true, labAllowed: true },
]

/** @deprecated Gunakan INVENTORY_QUICK_ACTION_ITEMS */
export const INVENTORY_SUBNAV_ITEMS = INVENTORY_QUICK_ACTION_ITEMS

export function isInventoryRoomScopedUser(user) {
  if (!user) return false
  if (['admin', 'institution_admin'].includes(user.role)) return false
  if (hasModuleAccess(user, 'inventory')) return false
  return !!(user.is_room_responsible || user.is_lab_responsible)
}

export function canAccessInventoryTab(user, tab) {
  if (!user) return false
  if (hasModuleAccess(user, 'inventory')) return true
  if (user.is_room_responsible || user.is_lab_responsible) {
    return tab === 'scan' || INVENTORY_ROOM_SCOPED_TABS.includes(tab)
  }
  return false
}

export const INVENTORY_TAB_BY_ROUTE_NAME = Object.fromEntries(
  [
    ...INVENTORY_NAV_ITEMS,
    { tab: 'scan', routeName: 'InventoryQrScan' },
  ].map((r) => [r.routeName, r.tab])
)

export const INVENTORY_PATH_BY_TAB = Object.fromEntries(
  [
    ...INVENTORY_NAV_ITEMS,
    { tab: 'scan', path: '/inventory/scan' },
  ].map((r) => [r.tab, r.path])
)

export const INVENTORY_TITLE_BY_ROUTE_NAME = Object.fromEntries(
  [
    ...INVENTORY_NAV_ITEMS,
    { routeName: 'InventoryQrScan', label: 'Scan QR Inventaris' },
  ].map((r) => [r.routeName, r.label])
)

export function inventoryPathForTab(tab, query = {}) {
  const path = INVENTORY_PATH_BY_TAB[tab] || '/inventory/beranda'
  const entries = Object.entries(query).filter(([, v]) => v != null && v !== '')
  if (!entries.length) return path
  const qs = new URLSearchParams(entries.map(([k, v]) => [k, String(v)])).toString()
  return `${path}?${qs}`
}
