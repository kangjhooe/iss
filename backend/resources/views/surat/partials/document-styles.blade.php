{{-- CSS dokumen surat A4 — model padding sama dengan preview (Paper.vue / surat-page.css) --}}
{{-- Default = compact (target 1 lembar). Class surat-density-relaxed untuk SK / sertifikat / perjanjian. --}}
@page {
    size: A4 portrait;
    margin: 0;
}
html, body {
    margin: 0;
    padding: 0;
}
body {
    margin: 0;
    padding: 12mm 18mm 15mm 22mm;
    font-family: 'Times New Roman', Times, serif;
    font-size: 11pt;
    line-height: 1.4;
    color: #000;
}
body.surat-density-relaxed {
    padding: 15mm 20mm 20mm 25mm;
    font-size: 12pt;
    line-height: 1.5;
}
@include('partials.print-letterhead-styles')
@include('partials.print-signature-styles')
.standard-kop {
    width: 100%;
    max-width: 100%;
    display: block;
}
.standard-kop-inner {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}
.standard-kop-logo-cell {
    width: 68px;
    vertical-align: top;
    padding-right: 8px !important;
}
.standard-kop-spacer {
    width: 68px;
    border: none !important;
    padding: 0 !important;
}
.standard-kop-text {
    text-align: center;
    vertical-align: top;
}
.standard-kop-address,
.standard-kop-info {
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.surat-body {
    width: 100%;
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.surat-body img {
    max-width: 100%;
    height: auto;
}
.surat-body p {
    margin: 0 0 0.35em 0;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
body.surat-density-relaxed .surat-body p {
    margin: 0 0 0.5em 0;
}
.surat-body h2 {
    margin: 0 0 0.4em 0;
    font-size: 12pt;
}
body.surat-density-compact .surat-body h2 {
    font-size: 12pt !important;
}
body.surat-density-relaxed .surat-body h2 {
    margin: 0 0 0.5em 0;
    font-size: 14pt;
}
.surat-body figure.table {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
}
.surat-body table {
    border-collapse: collapse;
    width: 100%;
    max-width: 100%;
}
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) {
    table-layout: fixed;
}
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) td,
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) th {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}
.surat-body .standard-kop-inner td,
.surat-body .standard-kop-inner th,
.surat-body .kop-table td,
.surat-body .kop-table th,
.surat-body .blok-ttd-table td,
.surat-body .blok-ttd-table th,
.surat-body table[style*="border:none"] td,
.surat-body table[style*="border: none"] td,
.surat-body table[style*="border-style:none"] td,
.surat-body table[style*="border-style: none"] td,
.surat-body td[style*="border:none"],
.surat-body td[style*="border: none"],
.surat-body td[style*="border-style:none"],
.surat-body td[style*="border-style: none"],
.surat-body th[style*="border:none"],
.surat-body th[style*="border: none"],
.surat-body th[style*="border-style:none"],
.surat-body th[style*="border-style: none"] {
    border: none !important;
}
.blok-ttd {
    margin-top: 20px;
    page-break-inside: avoid;
}
body.surat-density-relaxed .blok-ttd {
    margin-top: 28px;
}
.blok-ttd-table {
    width: 100%;
    max-width: 100%;
    border: none;
    border-collapse: collapse;
    table-layout: fixed;
}
.blok-ttd-table td {
    border: none !important;
    vertical-align: top;
    padding: 0;
}
.blok-ttd-inner {
    text-align: center;
    max-width: 100%;
}
.blok-ttd-role {
    margin-bottom: 4px;
}
.blok-ttd-space {
    height: 56px;
    margin: 6px 0;
    text-align: center;
}
body.surat-density-relaxed .blok-ttd-space {
    height: 72px;
    margin: 8px 0;
}
.blok-ttd-space img {
    max-height: 54px;
    object-fit: contain;
}
body.surat-density-relaxed .blok-ttd-space img {
    max-height: 70px;
}
.blok-ttd-name {
    font-weight: bold;
    text-decoration: underline;
}
.blok-ttd-nip {
    font-size: 10pt;
    margin-top: 4px;
}
