{{--
    Tampilan ringkas rating unit (Mess / Bungalow).
    Pakai: @include('partials.rating-badge', ['avg' => $x->ratings_avg_rating, 'count' => $x->ratings_count])
    Opsional: 'size' => 'sm' (default) atau 'lg'.
--}}
@php
    $avg = $avg ?? null;
    $count = (int) ($count ?? 0);
    $size = $size ?? 'sm';
@endphp

@if($count > 0)
    <span class="text-nowrap {{ $size === 'lg' ? 'fs-6' : 'small' }}">
        <i class="ti ti-star-filled text-warning"></i>
        <span class="fw-semibold">{{ number_format((float) $avg, 1) }}</span>
        <span class="text-secondary">({{ $count }} ulasan)</span>
    </span>
@else
    <span class="text-secondary text-nowrap {{ $size === 'lg' ? 'fs-6' : 'small' }}">
        <i class="ti ti-star"></i> Belum ada rating
    </span>
@endif
