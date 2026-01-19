<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat - {{ $correspondence->subject }}</title>
    <style>
        @page {
            margin: 2.5cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 80px;
            height: auto;
        }
        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11pt;
            margin: 2px 0;
        }
        .header .address {
            font-size: 10pt;
            margin-top: 5px;
        }
        .divider {
            border-top: 3px solid #000;
            margin: 20px 0;
        }
        .letter-info {
            margin-bottom: 20px;
        }
        .letter-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .letter-info td {
            padding: 5px 10px;
            vertical-align: top;
        }
        .letter-info td:first-child {
            width: 150px;
            font-weight: bold;
        }
        .subject {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin: 20px 0;
            text-decoration: underline;
        }
        .content {
            text-align: justify;
            margin: 20px 0;
            min-height: 200px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
        .signature table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature td {
            padding: 10px;
            vertical-align: top;
        }
        .signature .signature-left {
            text-align: left;
        }
        .signature .signature-right {
            text-align: right;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 60px;
            text-decoration: underline;
        }
        .signature-nip {
            font-size: 10pt;
            margin-top: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 10pt;
            text-align: center;
        }
        .copy {
            margin-top: 20px;
            font-size: 10pt;
        }
        .copy-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header Sekolah -->
    <div class="header">
        @if($institution->logo)
            <img src="{{ public_path('storage/' . $institution->logo) }}" alt="Logo">
        @endif
        <h1>{{ $institution->name }}</h1>
        <p>NPSN: {{ $institution->npsn }}</p>
        @if($institution->nss)
            <p>NSS: {{ $institution->nss }}</p>
        @endif
        <div class="address">
            <p>{{ $institution->address }}</p>
            <p>{{ $institution->village }}, {{ $institution->sub_district }}, {{ $institution->district }}</p>
            <p>{{ $institution->province }} {{ $institution->postal_code }}</p>
            @if($institution->phone)
                <p>Telp: {{ $institution->phone }}</p>
            @endif
            @if($institution->email)
                <p>Email: {{ $institution->email }}</p>
            @endif
        </div>
    </div>

    <div class="divider"></div>

    <!-- Informasi Surat -->
    <div class="letter-info">
        <table>
            @if($correspondence->letter_number)
                <tr>
                    <td>Nomor</td>
                    <td>: {{ $correspondence->letter_number }}</td>
                </tr>
            @endif
            @if($correspondence->reference_number)
                <tr>
                    <td>Nomor Referensi</td>
                    <td>: {{ $correspondence->reference_number }}</td>
                </tr>
            @endif
            @if($correspondence->letter_type_name)
                <tr>
                    <td>Jenis Surat</td>
                    <td>: {{ $correspondence->letter_type_code }} - {{ $correspondence->letter_type_abbr }} ({{ $correspondence->letter_type_name }})</td>
                </tr>
            @endif
            <tr>
                <td>Perihal</td>
                <td>: {{ $correspondence->subject }}</td>
            </tr>
            @if($correspondence->type === 'masuk' && $correspondence->from)
                <tr>
                    <td>Dari</td>
                    <td>: {{ $correspondence->from }}</td>
                </tr>
            @endif
            @if(in_array($correspondence->type, ['keluar', 'internal']) && $correspondence->to)
                <tr>
                    <td>Kepada</td>
                    <td>: {{ $correspondence->to }}</td>
                </tr>
            @endif
            <tr>
                <td>Tanggal</td>
                <td>: {{ \Carbon\Carbon::parse($correspondence->date)->locale('id')->isoFormat('D MMMM YYYY') }}</td>
            </tr>
            @if($correspondence->type === 'masuk' && $correspondence->received_date)
                <tr>
                    <td>Tanggal Terima</td>
                    <td>: {{ \Carbon\Carbon::parse($correspondence->received_date)->locale('id')->isoFormat('D MMMM YYYY') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Perihal -->
    <div class="subject">
        {{ $correspondence->subject }}
    </div>

    <!-- Isi Surat -->
    <div class="content">
        {!! nl2br(e($correspondence->description ?? 'Isi surat tidak tersedia.')) !!}
    </div>

    <!-- Tanda Tangan -->
    <div class="signature">
            <div class="signature-right">
                <div>{{ $institution->district }}, {{ \Carbon\Carbon::parse($correspondence->date)->locale('id')->isoFormat('D MMMM YYYY') }}</div>
                @if($correspondence->type === 'keluar' || $correspondence->type === 'internal')
                    <div style="margin-top: 10px;">Yang bertanda tangan di bawah ini,</div>
                @endif
                <div class="signature-name">
                    @if($institution->principal_name)
                        {{ $institution->principal_name }}
                    @else
                        Kepala Sekolah
                    @endif
                </div>
                @if($institution->principal_nip)
                    <div class="signature-nip">NIP. {{ $institution->principal_nip }}</div>
                @endif
            </div>
    </div>

    <!-- Tembusan (jika ada) -->
    @if($correspondence->type === 'keluar' && $correspondence->to)
        <div class="copy">
            <div class="copy-title">Tembusan:</div>
            <div>- Arsip</div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY HH:mm') }}</p>
    </div>
</body>
</html>
