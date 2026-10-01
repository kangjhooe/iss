/**
 * Anti-FOUC untuk SPA fallback: index.html hasil prerender berisi Home,
 * tetapi juga dilayani untuk /super-admin/*, /dashboard, dll.
 *
 * Strategi: sembunyikan .home-page (bukan seluruh #app) agar setelah Vue
 * mengganti konten, halaman admin langsung terlihat tanpa bergantung pada
 * classList.remove di bundle JS lama.
 */
export const HIDE_PRERENDER_BOOT = `
<script id="hide-prerender-boot">
(function () {
  var p = location.pathname || '/';
  if (p === '/' || p === '') return;
  document.documentElement.classList.add('hide-prerender');
  function wipe() {
    var app = document.getElementById('app');
    if (app && app.querySelector('.home-page')) {
      app.innerHTML = '';
    }
  }
  if (document.body) wipe();
  else document.addEventListener('DOMContentLoaded', wipe);
})();
</script>
<style id="hide-prerender-style">
html.hide-prerender #app .home-page { display: none !important; }
html.hide-prerender body { background: #f8fafc; }
</style>`.trim()

/** Sisipkan boot snippet ke HTML jika belum ada (untuk hasil prerender). */
export function ensureHidePrerenderBoot(html) {
  if (!html) return html
  // Ganti versi lama jika ada, agar CSS/strategi terbaru selalu dipakai
  if (html.includes('id="hide-prerender-boot"') || html.includes("id='hide-prerender-boot'")) {
    html = html
      .replace(/<script id="hide-prerender-boot">[\s\S]*?<\/script>\s*/i, '')
      .replace(/<style id="hide-prerender-style">[\s\S]*?<\/style>\s*/i, '')
  }
  if (html.includes('</head>')) {
    return html.replace('</head>', `${HIDE_PRERENDER_BOOT}\n</head>`)
  }
  return html
}
