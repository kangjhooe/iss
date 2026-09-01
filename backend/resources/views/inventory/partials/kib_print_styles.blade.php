@page { margin: 1cm 1.1cm; size: A4 portrait; }
body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 8.5pt;
    line-height: 1.25;
    color: #000;
    margin: 0;
}
.kib-document { page-break-inside: avoid; }
.doc-title {
    text-align: center;
    margin: 0 0 8px;
}
.doc-title h1 {
    font-size: 11pt;
    font-weight: bold;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.doc-title p {
    font-size: 8pt;
    margin: 2px 0 0;
    color: #444;
}
table.kib {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 6px;
    table-layout: fixed;
}
table.kib th,
table.kib td {
    border: 1px solid #333;
    padding: 3px 6px;
    text-align: left;
    vertical-align: top;
    word-wrap: break-word;
}
table.kib th {
    width: 22%;
    background: #f3f4f6;
    font-weight: bold;
    font-size: 8pt;
}
table.kib td {
    width: 28%;
    font-size: 8.5pt;
}
table.kib tr.section-row th {
    background: #e5e7eb;
    text-align: center;
    font-size: 8.5pt;
    padding: 4px 6px;
}
.footer {
    margin-top: 6px;
    font-size: 7pt;
    color: #666;
    text-align: center;
}
@include('partials.print-letterhead-styles')
.standard-kop {
    margin-bottom: 6px;
    padding-bottom: 4px;
}
.standard-kop-logo-cell {
    width: 64px;
    padding-right: 8px !important;
}
.standard-kop-logo {
    width: 54px;
    height: 54px;
}
.standard-kop-foundation { font-size: 11px; }
.standard-kop-school { font-size: 14px; }
.standard-kop-address { font-size: 8.5px; margin-top: 2px; }
.standard-kop-info { font-size: 8px; margin-top: 2px; }
@include('partials.print-signature-styles')
.standard-signature-wrap { margin-top: 10px; }
.standard-signature-space { height: 40px; }
.standard-signature-place,
.standard-signature-role { font-size: 8.5pt; }
.standard-signature-name { font-size: 9pt; }
.standard-signature-nip { font-size: 8pt; }
.standard-signature { min-width: 180px; }
