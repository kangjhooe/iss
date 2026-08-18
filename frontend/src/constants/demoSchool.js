/** Kredensial publik sekolah demo — sinkron dengan backend DemoSchoolService */
export const DEMO_SCHOOL = {
  name: 'SMA 1 Demo Servrin',
  npsn: '99990001',
  adminEmail: 'admin@demo.servrin.id',
  teacherEmail: 'guru01@demo.servrin.id', // Kepala Sekolah
  piketEmail: 'guru02@demo.servrin.id',
  bkEmail: 'bk@demo.servrin.id',
  password: 'DemoServrin1!',
  studentNik: '3201990000000001',
  studentPassword: '15052008',
  parentEmail: 'ortu@demo.servrin.id',
  resetNote: 'Data di-reset setiap hari pukul 03:00 WIB.',
  roles: [
    { key: 'admin', label: 'Admin', login: 'admin@demo.servrin.id', password: 'DemoServrin1!' },
    { key: 'ks', label: 'Kepala Sekolah', login: 'guru01@demo.servrin.id', password: 'DemoServrin1!' },
    { key: 'piket', label: 'Guru Piket', login: 'guru02@demo.servrin.id', password: 'DemoServrin1!' },
    { key: 'bk', label: 'BK', login: 'bk@demo.servrin.id', password: 'DemoServrin1!' },
    { key: 'parent', label: 'Orang Tua', login: 'ortu@demo.servrin.id', password: 'DemoServrin1!' },
    { key: 'student', label: 'Siswa', login: '3201990000000001', password: '15052008' },
  ],
}
