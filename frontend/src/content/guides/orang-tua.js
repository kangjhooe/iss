/**
 * Panduan portal Orang Tua — alur berurutan.
 * Dipakai di /panduan/orang-tua.
 */

export const parentGuide = {
  slug: 'orang-tua',
  title: 'Panduan Orang Tua',
  subtitle:
    'Ikuti langkah berurutan: dari menerima akun, masuk, melihat ringkasan anak, hingga memantau jadwal, nilai, dan absensi.',
  audience: 'Orang Tua / Wali',
  breadcrumb: 'Orang Tua',
  roadmap:
    'Akun → Login → Ringkasan → Pilih anak → Jadwal → Nilai → Absensi → Pelanggaran → Pengumuman',
  readTime: '±5 menit baca · lompat lewat daftar isi',
  cta: {
    title: 'Siap memantau anak Anda?',
    desc: 'Masuk dengan akun orang tua dari sekolah, atau kembali ke daftar panduan.',
    primary: { to: '/login', label: 'Masuk sekarang' },
    secondary: { to: '/panduan', label: 'Semua panduan' },
  },
  steps: [
    {
      id: 'akun',
      shortTitle: 'Akun',
      title: 'Terima akun orang tua',
      summary:
        'Akun orang tua dibuat dan dihubungkan ke data anak oleh pihak sekolah. Pendaftaran mandiri di beranda publik biasanya untuk admin sekolah, bukan orang tua.',
      actions: [
        'Minta akun login ke TU, wali kelas, atau admin sekolah',
        'Pastikan akun sudah tertaut ke anak yang benar',
        'Siapkan email/HP yang bisa dihubungi sekolah',
      ],
      tip: 'Satu akun dapat terhubung ke lebih dari satu anak jika sekolah mengaturnya demikian.',
    },
    {
      id: 'login',
      shortTitle: 'Login',
      title: 'Masuk ke portal orang tua',
      summary:
        'Login dengan kredensial yang diberikan sekolah. Selesaikan ganti password jika diminta sistem.',
      link: { to: '/login', label: 'Buka halaman Masuk' },
      actions: [
        'Buka halaman Masuk',
        'Masukkan email/username dan password',
        'Ganti password jika muncul kewajiban ganti sandi',
        'Anda akan diarahkan ke Ringkasan orang tua',
      ],
      tip: 'Lupa password? Pakai Lupa Password di login, atau minta reset ke sekolah.',
    },
    {
      id: 'ringkasan',
      shortTitle: 'Ringkasan',
      title: 'Baca ringkasan di Dashboard',
      summary:
        'Halaman Ringkasan menampilkan gambaran cepat status anak dan pintasan ke menu penting.',
      actions: [
        'Buka Ringkasan (/parent/dashboard)',
        'Lihat daftar anak yang terhubung ke akun Anda',
        'Perhatikan info atau peringatan singkat jika ada',
      ],
      tip: 'Gunakan sidebar untuk berpindah ke Pengumuman atau detail per anak.',
    },
    {
      id: 'pilih-anak',
      shortTitle: 'Pilih anak',
      title: 'Pilih anak yang dipantau',
      summary:
        'Jika punya lebih dari satu anak di sekolah yang sama, pilih nama anak di sidebar untuk membuka menu terkait.',
      actions: [
        'Di sidebar, temukan grup nama anak',
        'Pilih anak yang ingin dipantau',
        'Buka submenu Jadwal, Nilai, Absensi, atau Pelanggaran',
      ],
      tip: 'Pastikan nama dan kelas anak sudah benar. Jika salah taut, laporkan ke admin sekolah.',
    },
    {
      id: 'jadwal',
      shortTitle: 'Jadwal',
      title: 'Pantau jadwal pelajaran anak',
      summary:
        'Menu Jadwal menampilkan jadwal mapel anak sesuai data yang diisi sekolah.',
      actions: [
        'Buka Jadwal pada anak yang dipilih',
        'Cek jadwal harian/mingguan',
        'Diskusikan dengan anak jika ada perubahan jam',
      ],
      tip: 'Jadwal kosong biasanya berarti belum diisi admin. Hubungi wali kelas.',
    },
    {
      id: 'nilai',
      shortTitle: 'Nilai',
      title: 'Lihat perkembangan nilai',
      summary:
        'Nilai muncul setelah guru menginput dan (jika diatur) merilis hasil penilaian.',
      actions: [
        'Buka Nilai pada anak yang dipilih',
        'Periksa nilai per mata pelajaran',
        'Bicarakan hasil dengan anak dan guru bila perlu',
      ],
      tip: 'Nilai belum lengkap bukan selalu kesalahan sistem — sering menunggu input guru.',
    },
    {
      id: 'absensi',
      shortTitle: 'Absensi',
      title: 'Pantau kehadiran anak',
      summary:
        'Absensi mencatat hadir, izin, sakit, atau alpa sesuai isian guru/sistem absensi sekolah.',
      actions: [
        'Buka Absensi pada anak yang dipilih',
        'Cek rekap kehadiran periode berjalan',
        'Segera konfirmasi ke sekolah jika ada ketidaksesuaian',
      ],
      tip: 'Untuk izin/sakit, ikuti prosedur sekolah (surat/izin lewat wali kelas), lalu pastikan tercatat.',
    },
    {
      id: 'pelanggaran',
      shortTitle: 'Pelanggaran',
      title: 'Pantau catatan perilaku',
      summary:
        'Menu Pelanggaran menampilkan catatan terkait poin perilaku anak (jika modul BK aktif).',
      actions: [
        'Buka Pelanggaran pada anak yang dipilih',
        'Baca detail dan tanggal catatan',
        'Koordinasikan dengan wali kelas/BK bila ada masalah',
      ],
      tip: 'Prestasi positif juga bisa tercatat di modul terkait — tanyakan ke sekolah jika ingin konfirmasi.',
    },
    {
      id: 'pengumuman',
      shortTitle: 'Pengumuman',
      title: 'Ikuti pengumuman sekolah',
      summary:
        'Menu Pengumuman berisi informasi resmi dari sekolah untuk orang tua.',
      actions: [
        'Buka Pengumuman di sidebar',
        'Baca pengumuman terbaru secara berkala',
        'Simpan atau catat tanggal penting (rapat, pembayaran, kegiatan)',
      ],
      faqs: [
        {
          q: 'Tidak melihat data anak?',
          a: 'Akun mungkin belum ditautkan. Hubungi admin/TU sekolah untuk menghubungkan akun ke data siswa.',
        },
        {
          q: 'Bisa memantau lebih dari satu anak?',
          a: 'Ya, jika sekolah menautkan beberapa anak ke akun yang sama. Pilih nama anak di sidebar.',
        },
        {
          q: 'Lupa password?',
          a: 'Gunakan Lupa Password di halaman login, atau minta reset ke sekolah.',
        },
      ],
    },
  ],
}
