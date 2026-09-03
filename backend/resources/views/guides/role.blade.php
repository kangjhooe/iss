<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $guide['title'] }} — {{ $appName }}</title>
    <style>
        @page { margin: 1.4cm 1.5cm 1.6cm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .brand-bar {
            height: 5px;
            background: #0d9488;
            margin: -1.4cm -1.5cm 18px;
            padding: 0;
        }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .header-table td { vertical-align: middle; padding: 0; }
        .brand-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: -0.02em;
        }
        .brand-tag {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-badge {
            text-align: right;
            font-size: 8pt;
            color: #0f766e;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-badge span {
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid #99f6e4;
            background: #f0fdfa;
            border-radius: 4px;
        }

        .hero {
            border: 1px solid #99f6e4;
            background: #f0fdfa;
            padding: 14px 16px;
            margin-bottom: 16px;
            border-radius: 4px;
        }
        .hero-audience {
            margin: 0 0 4px;
            font-size: 8pt;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .hero-title {
            margin: 0 0 6px;
            font-size: 18pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .hero-sub {
            margin: 0;
            font-size: 9.5pt;
            color: #475569;
        }
        .roadmap {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #99f6e4;
            font-size: 8pt;
            font-weight: bold;
            color: #0f766e;
            line-height: 1.5;
        }
        .meta-line {
            margin-top: 8px;
            font-size: 8pt;
            color: #64748b;
        }

        .toc {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .sec-label {
            margin: 0 0 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
        }
        .toc-table { width: 100%; border-collapse: collapse; }
        .toc-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9pt;
            vertical-align: middle;
        }
        .toc-table td.num {
            width: 28px;
            color: #0f766e;
            font-weight: bold;
            text-align: center;
        }
        .toc-table td.short {
            width: 90px;
            color: #64748b;
            font-size: 8pt;
        }

        .step {
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .step-head {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .step-head td { padding: 10px 12px; vertical-align: top; }
        .step-num {
            width: 36px;
            text-align: center;
        }
        .step-num-badge {
            display: inline-block;
            width: 26px;
            height: 26px;
            line-height: 26px;
            text-align: center;
            border-radius: 50%;
            background: #0d9488;
            color: #fff;
            font-size: 10pt;
            font-weight: bold;
        }
        .step-kicker {
            margin: 0 0 2px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .step-title {
            margin: 0 0 4px;
            font-size: 11.5pt;
            font-weight: bold;
            color: #0f172a;
        }
        .step-summary {
            margin: 0;
            font-size: 9pt;
            color: #475569;
        }
        .step-body { padding: 10px 12px 12px; }

        .actions { width: 100%; border-collapse: collapse; margin: 0; }
        .actions td {
            padding: 4px 0;
            font-size: 9pt;
            vertical-align: top;
            color: #334155;
        }
        .actions td.check {
            width: 18px;
            color: #0d9488;
            font-weight: bold;
            padding-right: 6px;
        }

        .tip {
            margin-top: 10px;
            padding: 8px 10px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            font-size: 8.5pt;
            color: #78350f;
        }
        .tip-label {
            font-weight: bold;
            color: #b45309;
            margin-right: 4px;
        }

        .faq {
            margin-top: 10px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
        }
        .faq-item { margin-bottom: 8px; }
        .faq-q {
            margin: 0 0 2px;
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
        }
        .faq-a {
            margin: 0;
            font-size: 8.5pt;
            color: #475569;
        }

        .closing {
            margin-top: 8px;
            padding: 12px 14px;
            border: 1px dashed #99f6e4;
            background: #f0fdfa;
            page-break-inside: avoid;
        }
        .closing-title {
            margin: 0 0 4px;
            font-size: 11pt;
            font-weight: bold;
            color: #0f766e;
        }
        .closing-desc {
            margin: 0;
            font-size: 9pt;
            color: #475569;
        }
        .closing-url {
            margin-top: 6px;
            font-size: 8pt;
            color: #0f766e;
        }

        .footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 7.5pt;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="brand-bar"></div>

    <table class="header-table">
        <tr>
            <td>
                <div class="brand-name">{{ $appName }}</div>
                <div class="brand-tag">{{ $appTagline }}</div>
            </td>
            <td class="doc-badge">
                <span>Panduan resmi</span>
            </td>
        </tr>
    </table>

    <div class="hero">
        <p class="hero-audience">{{ $guide['audience'] }}</p>
        <h1 class="hero-title">{{ $guide['title'] }}</h1>
        <p class="hero-sub">{{ $guide['subtitle'] }}</p>
        @if(!empty($guide['roadmap']))
            <div class="roadmap">{{ $guide['roadmap'] }}</div>
        @endif
        <div class="meta-line">{{ count($guide['steps']) }} langkah · Baca berurutan dari atas ke bawah</div>
    </div>

    <div class="toc">
        <p class="sec-label">Daftar isi</p>
        <table class="toc-table">
            @foreach($guide['steps'] as $i => $step)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="short">{{ $step['short_title'] ?? '' }}</td>
                    <td>{{ $step['title'] }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    @foreach($guide['steps'] as $i => $step)
        <div class="step">
            <table class="step-head">
                <tr>
                    <td class="step-num">
                        <span class="step-num-badge">{{ $i + 1 }}</span>
                    </td>
                    <td>
                        <p class="step-kicker">Langkah {{ $i + 1 }} dari {{ count($guide['steps']) }}</p>
                        <h2 class="step-title">{{ $step['title'] }}</h2>
                        <p class="step-summary">{{ $step['summary'] }}</p>
                    </td>
                </tr>
            </table>
            <div class="step-body">
                @if(!empty($step['actions']))
                    <table class="actions">
                        @foreach($step['actions'] as $action)
                            <tr>
                                <td class="check">✓</td>
                                <td>{{ $action }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if(!empty($step['tip']))
                    <div class="tip">
                        <span class="tip-label">Tips</span>{{ $step['tip'] }}
                    </div>
                @endif

                @if(!empty($step['faqs']))
                    <div class="faq">
                        @foreach($step['faqs'] as $faq)
                            <div class="faq-item">
                                <p class="faq-q">{{ $faq['q'] }}</p>
                                <p class="faq-a">{{ $faq['a'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="closing">
        <p class="closing-title">{{ $closing_title }}</p>
        <p class="closing-desc">{{ $closing_desc }}</p>
        <div class="closing-url">Versi daring: {{ $guideUrl }}</div>
    </div>

    <div class="footer">
        {{ $footer_label }} · {{ $appName }} · Diunduh {{ $printed_at }}
        · {{ $guideUrl }}
    </div>
</body>
</html>
