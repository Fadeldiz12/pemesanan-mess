{{-- Deretan 5 bintang. Pakai: @include('ulasan._stars', ['value' => 4]) --}}
@php $value = (float) ($value ?? 0); @endphp
<span class="text-warning text-nowrap" title="{{ number_format($value, 1) }} dari 5">
    @for($i = 1; $i <= 5; $i++)
        @if($value >= $i)
            <i class="ti ti-star-filled"></i>
        @elseif($value >= $i - 0.5)
            <i class="ti ti-star-half-filled"></i>
        @else
            <i class="ti ti-star"></i>
        @endif
    @endfor
</span>
