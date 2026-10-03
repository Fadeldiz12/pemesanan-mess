{{--
    Tampilan ringkas rating unit (Mess / Bungalow).
    Pakai: @include('partials.rating-badge', ['avg' => $x->ratings_avg_rating, 'count' => $x->ratings_count])
    Opsional: 'size' => 'sm' (default) atau 'lg'.
    Opsional: 'url' => link ke halaman ulasan (hanya aktif kalau sudah ada rating).
--}}
@php
    $avg = $avg ?? null;
    $count = (int) ($count ?? 0);
    $size = $size ?? 'sm';
    $url = $url ?? null;
@endphp

@if($count > 0)
    @if($url)<a href="{{ $url }}" class="text-decoration-none text-reset" title="Lihat rating & ulasan">@endif
    <span class="text-nowrap {{ $size === 'lg' ? 'fs-6' : 'small' }}">
        <i class="ti ti-star-filled text-warning"></i>
        <span class="fw-semibold">{{ number_format((float) $avg, 1) }}</span>
        <span class="text-secondary {{ $url ? 'text-decoration-underline' : '' }}">({{ $count }} ulasan)</span>
    </span>
    @if($url)</a>@endif
@else
    <span class="text-secondary text-nowrap {{ $size === 'lg' ? 'fs-6' : 'small' }}">
        <i class="ti ti-star"></i> Belum ada rating
    </span>
@endif
