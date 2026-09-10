{{--
    Tabel ringkas yang dipakai di dashboard.

    Parameter:
      $title   - judul kartu
      $icon    - class ikon (opsional)
      $headers - array nama kolom
      $rows    - array baris, tiap baris array nilai kolom (urutannya ikut $headers)

    Di bawah breakpoint md tabelnya berubah jadi kartu (lihat .table-accordion di
    public/css/mobile.css): kolom pertama jadi judul yang bisa di-tap, sisanya
    baru muncul setelah kartunya dibuka. Makanya tiap <td> wajib punya data-label,
    kalau nggak, di HP nilainya kelihatan tanpa keterangan kolomnya sama sekali.
--}}
<div class="card h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fs-5 mb-0">
                @if(!empty($icon))<i class="{{ $icon }} me-2 text-primary"></i>@endif
                {{ $title }}
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 text-nowrap table-hover table-accordion">
                <thead class="table-light border-light">
                    <tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach(array_values((array) $row) as $i => $cell)
                            @if($i === 0)
                                <td class="toggle-cell" data-label="{{ $headers[$i] ?? '' }}">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-semibold">{{ $cell }}</span>
                                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                                    </div>
                                </td>
                            @else
                                <td class="detail-data" data-label="{{ $headers[$i] ?? '' }}">{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="text-secondary">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
