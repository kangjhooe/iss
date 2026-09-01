/**
 * Client-side layout renderer for KOP / TTD / Stempel preview.
 * Mirrors backend SuratLayoutService (URL-based, not DomPDF paths).
 */
import {
  renderStandardLetterheadHtml,
  shouldUseInstitutionLetterhead
} from '@/utils/standardLetterhead'

export class SuratLayoutClient {
  escape(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
  }

  renderKop(kop, institution = null) {
    if (shouldUseInstitutionLetterhead(kop)) {
      return renderStandardLetterheadHtml(institution)
    }
    if (!kop) return ''
    if (kop.isi_html) return kop.isi_html

    const meta = [
      kop.telepon ? `Telp. ${this.escape(kop.telepon)}` : null,
      kop.email ? `Email: ${this.escape(kop.email)}` : null,
      kop.website ? this.escape(kop.website) : null
    ].filter(Boolean)

    let html = '<div class="kop-surat">'
    html += '<table class="kop-table" style="width:100%;border:none;border-collapse:collapse;"><tr>'
    html += '<td style="border:none;width:80px;vertical-align:middle;text-align:left;">'
    if (kop.logo_kiri_url) {
      html += `<img src="${this.escape(kop.logo_kiri_url)}" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;" />`
    }
    html += '</td><td style="border:none;vertical-align:middle;text-align:center;">'
    if (kop.baris_1) html += `<div style="font-size:14pt;font-weight:bold;text-transform:uppercase;line-height:1.2;">${this.escape(kop.baris_1)}</div>`
    if (kop.baris_2) html += `<div style="font-size:12pt;font-weight:bold;text-transform:uppercase;line-height:1.2;">${this.escape(kop.baris_2)}</div>`
    if (kop.baris_3) html += `<div style="font-size:11pt;font-weight:bold;line-height:1.2;">${this.escape(kop.baris_3)}</div>`
    if (kop.alamat) html += `<div style="font-size:9pt;margin-top:4px;">${this.escape(kop.alamat)}</div>`
    if (meta.length) html += `<div style="font-size:8pt;margin-top:2px;">${meta.join(' | ')}</div>`
    html += '</td><td style="border:none;width:80px;vertical-align:middle;text-align:right;">'
    if (kop.logo_kanan_url) {
      html += `<img src="${this.escape(kop.logo_kanan_url)}" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;" />`
    }
    html += '</td></tr></table>'
    if (kop.tampilkan_garis !== false) {
      html += '<div style="border-top:3px solid #000;border-bottom:1px solid #000;height:4px;margin:8px 0 16px;"></div>'
    }
    html += '</div>'
    return html
  }

  renderSignature(ttd, stempel, posisi = 'kanan') {
    if (!ttd && !stempel) return ''

    const align = posisi === 'kiri' ? 'left' : 'right'
    const jabatan = ttd?.pemilik_jabatan || 'Kepala Sekolah'
    const nama = ttd?.pemilik_nama || ''
    const nip = ttd?.pemilik_nip || ''
    const ttdW = `${ttd?.lebar_mm || 40}mm`
    const ttdH = `${ttd?.tinggi_mm || 20}mm`
    const stempelW = `${stempel?.lebar_mm || 35}mm`
    const stempelH = `${stempel?.tinggi_mm || 35}mm`

    let html = `<div class="blok-ttd" style="margin-top:40px;text-align:${align};">`
    html += '<div style="display:inline-block;text-align:center;min-width:220px;position:relative;">'
    html += `<div style="margin-bottom:4px;">${this.escape(jabatan)},</div>`
    html += '<div style="position:relative;height:90px;margin:8px 0;">'
    if (stempel?.file_url) {
      html += `<img src="${this.escape(stempel.file_url)}" alt="Stempel" style="position:absolute;left:50%;top:50%;transform:translate(-70%,-50%);width:${stempelW};height:${stempelH};object-fit:contain;opacity:0.85;z-index:1;" />`
    }
    if (ttd?.file_url) {
      html += `<img src="${this.escape(ttd.file_url)}" alt="Tanda Tangan" style="position:relative;z-index:2;width:${ttdW};height:${ttdH};object-fit:contain;" />`
    } else if (!stempel?.file_url) {
      html += '<div style="height:70px;"></div>'
    }
    html += '</div>'
    if (nama) html += `<div style="font-weight:bold;text-decoration:underline;">${this.escape(nama)}</div>`
    if (nip) html += `<div style="font-size:10pt;">NIP. ${this.escape(nip)}</div>`
    html += '</div></div>'
    return html
  }
}
