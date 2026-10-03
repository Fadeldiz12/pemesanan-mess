{{--
    Daftar review (paginated). Butuh $reviews.
    Opsional: $kamarNames (id => nama kamar) supaya tiap review di halaman Mess
    menampilkan kamar mana yang diulas.
--}}
@php $kamarNames = $kamarNames ?? null; @endphp

@forelse($reviews as $review)
    @php
        $reviewer = $review->reviewer_name ?: ($review->user->name ?? 'Tamu');
        $stay = $review->peminjaman;
    @endphp
    <div class="py-3 {{ $loop->last ? '' : 'border-bottom' }}">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle bg-primary-subtle text-primary fw-semibold d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                    {{ mb_strtoupper(mb_substr($reviewer, 0, 1)) }}
                </span>
                <div>
                    <div class="fw-semibold">{{ $reviewer }}</div>
                    <div class="small text-secondary">
                        @if($kamarNames)
                            <i class="ti ti-door me-1"></i>{{ $kamarNames[$review->bookable_id] ?? 'Kamar' }}
                            &middot;
                        @endif
                        @if($stay && $stay->waktu_mulai)
                            Menginap {{ \Illuminate\Support\Carbon::parse($stay->waktu_mulai)->translatedFormat('d M Y') }}
                        @else
                            {{ $review->created_at->translatedFormat('d M Y') }}
                        @endif
                    </div>
                </div>
            </div>
            @include('ulasan._stars', ['value' => $review->rating])
        </div>

        @if($review->review)
            <p class="mb-0 mt-2" style="white-space: pre-line;">{{ $review->review }}</p>
        @else
            <p class="mb-0 mt-2 small text-secondary fst-italic">Tidak ada komentar.</p>
        @endif
    </div>
@empty
    <p class="text-secondary text-center py-4 mb-0">Belum ada ulasan.</p>
@endforelse

@if($reviews->hasPages())
    <div class="pt-3">{{ $reviews->links() }}</div>
@endif
