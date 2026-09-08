<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman Mess &amp; Bungalow</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .subtitle { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Laporan Peminjaman Mess &amp; Bungalow</h1>
    <div class="subtitle">Dicetak pada {{ now()->format('d M Y, H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Unit</th>
                <th>Pemohon</th>
                <th>Jabatan</th>
                <th>Bagian / Subbagian</th>
                <th>Waktu Mulai</th>
                <th>Waktu Selesai</th>
                <th>Status</th>
                <th>Tahap Approval</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $peminjaman)
                <tr>
                    <td>{{ $peminjaman->peminjaman_code }}</td>
                    <td>{{ class_basename($peminjaman->bookable_type) }} - {{ $peminjaman->bookable?->nama_kamar ?? $peminjaman->bookable?->nama ?? '(Unit Terhapus)' }}</td>
                    <td>{{ $peminjaman->peminjam_name }}</td>
                    <td>{{ $peminjaman->peminjam_role }}</td>
                    <td>{{ $peminjaman->peminjam_department }}{{ $peminjaman->peminjam_sub_department ? ' / ' . $peminjaman->peminjam_sub_department : '' }}</td>
                    <td>{{ optional($peminjaman->waktu_mulai)->format('d-m-Y H:i') }}</td>
                    <td>{{ optional($peminjaman->waktu_selesai)->format('d-m-Y H:i') }}</td>
                    <td>{{ $peminjaman->peminjaman_status }}</td>
                    <td>{{ $peminjaman->approval_status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;">Tidak ada data peminjaman untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
