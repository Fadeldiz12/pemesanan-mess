@extends('layouts.app')

@section('title', 'Beri Rating - PTPN 1')

@section('content')
<div class="d-flex align-items-center justify-content-center px-3 screen-center">
    <div class="card border-0 shadow-sm" style="max-width: 480px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="ti ti-star-filled text-warning" style="font-size: 2.5rem;"></i>
                <h1 class="h4 mt-2 mb-1">Beri Penilaian Anda</h1>
                <p class="text-secondary mb-0">
                    {{ class_basename($peminjaman->bookable_type) }} {{ $peminjaman->bookable?->nama_kamar ?? $peminjaman->bookable?->nama ?? '' }}
                    <br>{{ $peminjaman->peminjam_name }} &middot; {{ $peminjaman->peminjaman_code }}
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ url()->current() }}">
                @csrf
                <div class="mb-4 text-center">
                    <p class="text-muted mb-2">Seberapa puaskah Anda dengan fasilitas dan kebersihan unit ini?</p>
                    <div class="fs-1 text-warning d-flex justify-content-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="ti ti-star-filled star-rating" data-value="{{ $i }}" style="cursor:pointer; transition: transform 0.2s;"></i>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                </div>
                <div class="mb-3">
                    <textarea name="review" class="form-control bg-light" rows="3" placeholder="Tuliskan ulasan singkat Anda (opsional)...">{{ old('review') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">Kirim Ulasan</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stars = document.querySelectorAll('.star-rating');
        const ratingInput = document.getElementById('ratingValue');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                let value = this.getAttribute('data-value');
                ratingInput.value = value;

                stars.forEach(s => {
                    if (s.getAttribute('data-value') <= value) {
                        s.classList.remove('ti-star');
                        s.classList.add('ti-star-filled');
                    } else {
                        s.classList.remove('ti-star-filled');
                        s.classList.add('ti-star');
                    }
                });
            });
        });
    });
</script>
@endpush
