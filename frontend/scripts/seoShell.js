/**
 * Konten HTML statis untuk crawler (fallback jika prerender gagal / JS off).
 * Vue akan mengganti isi #app saat mount di client.
 */
export function buildSeoShell({ appName, appTagline, pageDescription }) {
  const features = [
    ['PPDB Online', 'Pendaftaran peserta didik baru, lengkapi berkas, dan cek hasil secara online'],
    ['Buku Nilai & Raport', 'KKM, bobot penilaian, remedial, buku nilai, dan cetak raport'],
    ['Absensi', 'Kehadiran siswa dan pegawai, rekap, dan laporan kehadiran'],
    ['Keuangan Sekolah', 'Tagihan, SPP, pembayaran, dan tunggakan dalam satu modul'],
    ['Data Siswa & Guru', 'Biodata, rombel, mutasi, dan administrasi kepegawaian'],
    ['Surat & Laporan', 'Korespondensi, laporan BK, inventaris, dan dokumen sekolah'],
  ]

  const featureLis = features
    .map(([title, desc]) => `<li><strong>${escapeHtml(title)}</strong> — ${escapeHtml(desc)}</li>`)
    .join('')

  return `
<div class="seo-shell" data-seo-shell>
  <header>
    <p><strong>${escapeHtml(appName)}</strong> — ${escapeHtml(appTagline)}</p>
  </header>
  <main>
    <h1>Sistem Informasi Sekolah &amp; Madrasah dalam Satu Platform</h1>
    <p>${escapeHtml(pageDescription)}</p>
    <p>Gratis untuk sekolah · Siap pakai. Daftar gratis atau coba demo tanpa instalasi.</p>
    <h2>Fitur unggulan</h2>
    <ul>${featureLis}</ul>
    <h2>Mengapa memilih ${escapeHtml(appName)}</h2>
    <ul>
      <li><strong>Data aman</strong> — data institusi dan siswa dikelola dengan keamanan yang terjamin</li>
      <li><strong>Terintegrasi</strong> — semua modul terhubung, mengurangi duplikasi data</li>
      <li><strong>Responsif</strong> — akses dari desktop, tablet, atau ponsel</li>
      <li><strong>Multi-institusi</strong> — mendukung berbagai jenjang sekolah dan madrasah di Indonesia</li>
    </ul>
    <p>
      <a href="/register">Daftar gratis</a> ·
      <a href="/login?demo=1">Coba demo</a> ·
      <a href="/catatan-rilis">Catatan rilis</a>
    </p>
  </main>
</div>`.trim()
}

export function buildHomepageJsonLd({ appName, appTagline, appUrl, pageDescription }) {
  const origin = String(appUrl || 'https://servr.in').replace(/\/$/, '')
  return {
    '@context': 'https://schema.org',
    '@graph': [
      {
        '@type': 'Organization',
        '@id': `${origin}/#organization`,
        name: appName,
        url: `${origin}/`,
        logo: `${origin}/pwa-512x512.png`,
        image: `${origin}/og-image.jpg`,
        description: pageDescription,
      },
      {
        '@type': 'WebSite',
        '@id': `${origin}/#website`,
        url: `${origin}/`,
        name: appName,
        description: appTagline,
        publisher: { '@id': `${origin}/#organization` },
        inLanguage: 'id-ID',
      },
      {
        '@type': 'SoftwareApplication',
        '@id': `${origin}/#app`,
        name: appName,
        applicationCategory: 'EducationalApplication',
        operatingSystem: 'Web',
        url: `${origin}/`,
        description: pageDescription,
        offers: {
          '@type': 'Offer',
          price: '0',
          priceCurrency: 'IDR',
          description: 'Daftar gratis untuk sekolah dan madrasah',
        },
        inLanguage: 'id-ID',
        publisher: { '@id': `${origin}/#organization` },
      },
    ],
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}
