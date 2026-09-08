<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Pembatalan Peminjaman {{ $peminjaman->peminjaman_code }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        .kop { display: table; width: 100%; border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 4px; }
        .kop .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .kop .logo img { height: 60px; }
        .kop .nama { display: table-cell; vertical-align: middle; text-align: center; }
        .kop .nama h1 { font-size: 16px; margin: 0; }
        .kop .nama p { font-size: 10px; margin: 2px 0 0; color: #444; }
        .garis-bawah { border-bottom: 1px solid #111; margin-bottom: 18px; }
        .judul { text-align: center; margin: 18px 0; }
        .judul h2 { font-size: 14px; text-decoration: underline; margin: 0; }
        .judul p { margin: 2px 0 0; font-size: 12px; }
        table.data { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.data td { padding: 3px 4px; vertical-align: top; }
        table.data td.label { width: 190px; }
        table.data td.sep { width: 12px; }
        .isi { text-align: justify; margin-top: 10px; line-height: 1.6; }
        .ttd { margin-top: 50px; width: 100%; }
        .ttd .kanan { float: right; text-align: center; width: 240px; }
        .ttd .nama-ttd { margin-top: 60px; font-weight: bold; text-decoration: underline; }
        .footer-nomor { margin-top: 4px; font-size: 11px; color: #333; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="logo"><img src="{{ public_path('inapp/assets/images/logo-ptpn1.png') }}"></div>
        <div class="nama">
            <h1>PT PERKEBUNAN NUSANTARA I</h1>
            <p>Sistem Pemesanan Mess &amp; Bungalow</p>
        </div>
    </div>
    <div class="garis-bawah"></div>

    <div class="judul">
        <h2>SURAT PEMBATALAN PEMINJAMAN MESS/BUNGALOW</h2>
        <p>Nomor: {{ $peminjaman->surat_pembatalan_nomor }}</p>
    </div>

    <p>Dengan ini menerangkan bahwa peminjaman berikut telah <strong>dibatalkan</strong>:</p>

    <table class="data">
        <tr>
            <td class="label">Kode Peminjaman</td><td class="sep">:</td>
            <td>{{ $peminjaman->peminjaman_code }}</td>
        </tr>
        <tr>
            <td class="label">Nama Pemohon</td><td class="sep">:</td>
            <td>{{ $peminjaman->peminjam_name }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td><td class="sep">:</td>
            <td>{{ $peminjaman->peminjam_jabatan }}</td>
        </tr>
        <tr>
            <td class="label">Bagian / Subbagian</td><td class="sep">:</td>
            <td>{{ $peminjaman->peminjam_department }}{{ $peminjaman->peminjam_sub_department ? ' / ' . $peminjaman->peminjam_sub_department : '' }}</td>
        </tr>
        <tr>
            <td class="label">Unit yang Dipinjam</td><td class="sep">:</td>
            <td>
                {{ class_basename($peminjaman->bookable_type) }}
                {{ $peminjaman->bookable?->nama_kamar ?? $peminjaman->bookable?->nama ?? '(unit tidak ditemukan)' }}
                @if($peminjaman->bookable_type === \App\Models\Kamar::class && $peminjaman->bookable?->mess)
                    ({{ $peminjaman->bookable->mess->nama }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Jadwal Semula</td><td class="sep">:</td>
            <td>
                {{ \Carbon\Carbon::parse($peminjaman->waktu_mulai)->translatedFormat('d F Y H:i') }} s.d.
                {{ \Carbon\Carbon::parse($peminjaman->waktu_selesai)->translatedFormat('d F Y H:i') }} WIB
            </td>
        </tr>
        <tr>
            <td class="label">Tanggal Pembatalan</td><td class="sep">:</td>
            <td>{{ $peminjaman->cancelled_at?->translatedFormat('d F Y H:i') ?? '-' }} WIB</td>
        </tr>
        <tr>
            <td class="label">Dibatalkan Oleh</td><td class="sep">:</td>
            <td>{{ $peminjaman->canceller->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alasan Pembatalan</td><td class="sep">:</td>
            <td>{{ $peminjaman->cancellation_reason ?? '-' }}</td>
        </tr>
    </table>

    <p class="isi">Surat ini diterbitkan sebagai bukti resmi bahwa peminjaman tersebut di atas telah dibatalkan dan unit yang bersangkutan kembali berstatus tersedia untuk dipesan pihak lain.</p>

    <div class="ttd">
        <div class="kanan">
            <div>{{ now()->translatedFormat('d F Y') }}</div>
            <div>Mengetahui,</div>
            <div class="nama-ttd">{{ $peminjaman->canceller->name ?? '(...........................)' }}</div>
            <div>Petugas Berwenang</div>
        </div>
    </div>

    <div class="footer-nomor">Dicetak otomatis oleh sistem pada {{ now()->format('d M Y, H:i') }}.</div>
</body>
</html>
