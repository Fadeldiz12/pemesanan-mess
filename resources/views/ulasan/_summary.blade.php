{{-- Ringkasan rating: rata-rata besar + sebaran bintang 5..1. Butuh $summary dari controller. --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        @if($summary['count'] > 0)
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-4 text-center">
                    <div class="display-5 fw-bold mb-1">{{ number_format((float) $summary['average'], 1) }}</div>
                    <div class="fs-5 mb-1">@include('ulasan._stars', ['value' => $summary['average']])</div>
                    <div class="text-secondary small">dari {{ $summary['count'] }} ulasan</div>
                </div>
                <div class="col-12 col-md-8">
                    @foreach($summary['distribution'] as $star => $total)
                        @php $percent = $summary['count'] > 0 ? round($total / $summary['count'] * 100) : 0; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="small text-nowrap" style="width: 2.5rem;">{{ $star }} <i class="ti ti-star-filled text-warning"></i></span>
                            <div class="progress flex-grow-1" style="height: 8px;" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                            </div>
                            <span class="small text-secondary text-end" style="width: 2rem;">{{ $total }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center text-secondary py-3">
                <i class="ti ti-star" style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">Belum ada rating untuk unit ini.</p>
            </div>
        @endif
    </div>
</div>
