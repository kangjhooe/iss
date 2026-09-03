/**
 * Daftar panduan per peran (hub /panduan).
 * Role yang belum siap: available: false → "Segera hadir".
 */

export const guideRoles = [
  {
    slug: 'admin',
    title: 'Admin Institusi',
    desc: 'Dari daftar & login hingga setup data dan operasional harian, langkah demi langkah.',
    to: '/panduan/admin',
    available: true,
    accent: 'teal',
  },
  {
    slug: 'guru',
    title: 'Guru & Staf',
    desc: 'Dashboard guru, jadwal mengajar, jurnal, nilai, wali kelas, dan absensi.',
    to: '/panduan/guru',
    available: true,
    accent: 'blue',
  },
  {
    slug: 'siswa',
    title: 'Siswa',
    desc: 'Portal siswa: jadwal, nilai, poin, konseling, dan layanan lainnya.',
    to: '/panduan/siswa',
    available: true,
    accent: 'amber',
  },
  {
    slug: 'orang-tua',
    title: 'Orang Tua',
    desc: 'Ringkasan anak: jadwal, nilai, absensi, dan pelanggaran.',
    to: '/panduan/orang-tua',
    available: true,
    accent: 'violet',
  },
]
