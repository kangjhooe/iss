@page { margin: 1.2cm 1.4cm 1.3cm 1.4cm; size: A4 portrait; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #111; line-height: 1.35; }
.doc-title { text-align: center; margin: 6px 0 10px 0; }
.doc-title h1 { font-size: 13pt; margin: 0; text-transform: uppercase; letter-spacing: .04em; }
.doc-title p { font-size: 9pt; margin: 3px 0 0 0; color: #333; }
.title-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.title-row td { vertical-align: top; }
.photo-box {
    width: 2.7cm; height: 3.6cm; border: 1px solid #333;
    text-align: center; vertical-align: middle;
}
.photo-box img { width: 2.7cm; height: 3.6cm; object-fit: cover; }
.photo-empty { font-size: 8pt; color: #666; padding: 8px 4px; line-height: 1.3; }
.section-title {
    font-weight: bold; font-size: 9.5pt; margin: 10px 0 4px 0;
    background: #e2e8f0; padding: 4px 8px; text-transform: uppercase;
    border-left: 3px solid #475569;
    page-break-after: avoid;
}
.section-block { page-break-inside: avoid; margin-bottom: 6px; }
table.data { width: 100%; border-collapse: collapse; }
table.data td { padding: 2px 5px; vertical-align: top; border-bottom: 1px solid #e2e8f0; }
table.data td.label { width: 28%; font-weight: bold; color: #1e293b; }
table.data td.sep { width: 2%; color: #64748b; }
table.two-col { width: 100%; border-collapse: collapse; }
table.two-col > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 4px 0 0; }
table.two-col > tbody > tr > td + td { padding: 0 0 0 4px; }
table.list {
    width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 8px;
}
table.list th, table.list td {
    border: 1px solid #64748b; padding: 3px 5px; text-align: left; vertical-align: top;
}
table.list th {
    background: #e2e8f0; font-weight: bold; text-align: center;
}
table.list td.center { text-align: center; }
table.list td.num { text-align: center; width: 24px; }
.sub-head { font-weight: bold; margin: 6px 0 3px 0; font-size: 9pt; color: #334155; }
.no-data { color: #64748b; font-style: italic; font-size: 8.5pt; margin: 2px 0 6px 0; }
.printed-at { font-size: 8pt; color: #64748b; text-align: center; margin-top: 8px; }
.page-break { page-break-before: always; }
.summary-box {
    width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 8.5pt;
}
.summary-box td {
    border: 1px solid #cbd5e1; padding: 4px 8px; vertical-align: middle;
}
.summary-box .lbl { width: 14%; font-weight: bold; background: #f8fafc; color: #475569; }
@include('partials.print-letterhead-styles')
@include('partials.print-signature-styles')
.standard-signature-wrap { margin-top: 16px; }
