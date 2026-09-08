<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengeluaran Mess &amp; Bungalow</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .subtitle { color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        tfoot td { font-weight: bold; background: #f7f7f7; }
    </style>
</head>
<body>
    <h1>Laporan Pengeluaran Mess &amp; Bungalow</h1>
    <div class="subtitle">Dicetak pada {{ now()->format('d M Y, H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Unit</th>
                <th>Item</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Diinput Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $expense)
                <tr>
                    <td>{{ $expense->expense_code }}</td>
                    <td>{{ optional($expense->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ class_basename($expense->bookable_type) }} {{ $expense->bookable?->nama ?? '(Unit Terhapus)' }}</td>
                    <td>{{ $expense->nama_item }}</td>
                    <td>{{ $expense->kategori }}</td>
                    <td>Rp {{ number_format($expense->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $expense->creator?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data pengeluaran.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td colspan="2">Rp {{ number_format($data->sum('jumlah'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
