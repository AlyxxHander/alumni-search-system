<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Alumni - Cetak PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-center { text-align: center; }
        .url-cell {
            word-break: break-all;
            max-width: 150px;
            font-size: 9px;
            color: #0056b3;
        }
        @media print {
            body { padding: 0; margin: 0; }
            @page {
                size: landscape;
                margin: 1cm;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer;">🖨️ Cetak / Save PDF</button>
        <button onclick="window.close()" style="padding: 8px 15px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Tutup</button>
    </div>

    <div class="header">
        <h1>Laporan Data Alumni Terverifikasi</h1>
        <p>Sistem Pelacakan Jejak Alumni Universitas</p>
    </div>

    <div class="meta-info">
        <div>
            <strong>Filter Prodi:</strong> {{ request('prodi') ?: 'Semua' }} <br>
            <strong>Filter Tahun Lulus:</strong> {{ request('tahun_lulus') ?: 'Semua' }}
        </div>
        <div style="text-align: right;">
            <strong>Total Data:</strong> {{ $alumniData->count() }} Orang<br>
            <strong>Tanggal Unduh:</strong> {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">NIM</th>
                <th width="15%">Nama Lengkap</th>
                <th width="12%">Program Studi</th>
                <th width="6%">Lulus</th>
                <th width="10%">Status</th>
                <th width="15%">Instansi Terkini</th>
                <th width="12%">Lokasi</th>
                <th width="17%">URL Profil</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumniData as $index => $alumni)
                @php
                    $bestResult = $alumni->trackingResults->first();
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace;">{{ $alumni->nim }}</td>
                    <td><strong>{{ $alumni->nama_lengkap }}</strong></td>
                    <td>{{ $alumni->prodi }}</td>
                    <td class="text-center">{{ $alumni->tahun_lulus }}</td>
                    <td>{{ $alumni->status_pelacakan->label() }}</td>
                    <td>{{ $bestResult?->instansi ?? '-' }}</td>
                    <td>{{ $bestResult?->lokasi ?? '-' }}</td>
                    <td class="url-cell">{{ $bestResult?->url_profil ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">Belum ada data alumni terverifikasi yang sesuai kriteria filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 30px; font-size: 11px;">
        <p>Halaman dicetak secara otomatis (Sistem Internal Alumni)</p>
    </div>
</body>
</html>
