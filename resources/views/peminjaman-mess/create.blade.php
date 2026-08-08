@extends('layouts.app')

@section('title', 'Buat Peminjaman - PTPN 1')
@section('header_title', 'Form Peminjaman Mess')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card">
            <div class="card-header bg-white">
                <h2 class="fs-5 mb-0">Informasi Pemesanan</h2>
            </div>

            <div class="card-body">
                <form action="{{ route('peminjaman.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label d-block">Jenis Unit</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="unit_type" id="jenis_kamar" value="kamar" checked>
                            <label class="btn btn-outline-primary" for="jenis_kamar"><i class="ti ti-bed me-1"></i>Kamar Mess</label>

                            <input type="radio" class="btn-check" name="unit_type" id="jenis_bungalow" value="bungalow">
                            <label class="btn btn-outline-primary" for="jenis_bungalow"><i class="ti ti-building-cottage me-1"></i>Bungalow</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="blok-kamar">
                        <div class="col-12 col-md-6">
                            <label for="mess_id" class="form-label">Pilih Mess</label>
                            <select id="mess_id" class="form-select">
                                <option value="">-- Pilih Mess --</option>
                                @foreach ($messById as $id => $nama)
                                    <option value="{{ $id }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="kamar_id" class="form-label">Pilih Kamar</label>
                            <select name="unit_id" id="kamar_id" class="form-select">
                                <option value="">-- Pilih Mess dulu --</option>
                            </select>
                            <div class="form-text">Kamar dengan syarat jabatan di atas jabatan Anda tidak akan muncul di sini.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 d-none" id="blok-bungalow">
                        <div class="col-12">
                            <label for="bungalow_id" class="form-label">Pilih Bungalow</label>
                            <select name="unit_id" id="bungalow_id" class="form-select" disabled>
                                <option value="">-- Pilih Bungalow --</option>
                                @foreach ($bungalows as $bungalow)
                                    <option value="{{ $bungalow->id }}">{{ $bungalow->nama }} (Kapasitas: {{ $bungalow->kapasitas }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="waktu_mulai" class="form-label">Check-in</label>
                            <input type="datetime-local" name="waktu_mulai" id="waktu_mulai" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="waktu_selesai" class="form-label">Check-out</label>
                            <input type="datetime-local" name="waktu_selesai" id="waktu_selesai" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="keperluan" class="form-label">Keperluan / Keterangan</label>
                        <textarea name="keperluan" id="keperluan" rows="3" class="form-control" placeholder="Jelaskan keperluan peminjaman..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('peminjaman-mess.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-send me-1"></i>Ajukan Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Data kamar per mess dikirim langsung dari controller (bukan hardcode lagi),
        // sudah difilter status tersedia + kelayakan jabatan pemohon.
        const kamarPerMess = @json($kamarsByMess);

        const radios = document.querySelectorAll('input[name="unit_type"]');
        const blokKamar = document.getElementById('blok-kamar');
        const blokBungalow = document.getElementById('blok-bungalow');
        const kamarSelect = document.getElementById('kamar_id');
        const bungalowSelect = document.getElementById('bungalow_id');
        const messSelect = document.getElementById('mess_id');

        function sync() {
            const pilih = document.querySelector('input[name="unit_type"]:checked').value;
            const isKamar = pilih === 'kamar';
            blokKamar.classList.toggle('d-none', !isKamar);
            blokBungalow.classList.toggle('d-none', isKamar);
            // disabled dipakai biar field yang lagi disembunyikan gak ikut ke-submit
            // sebagai unit_id (dua select ini sengaja berbagi name="unit_id").
            kamarSelect.disabled = !isKamar;
            bungalowSelect.disabled = isKamar;
        }
        radios.forEach(r => r.addEventListener('change', sync));
        sync();

        messSelect.addEventListener('change', function () {
            const list = kamarPerMess[this.value] || [];
            kamarSelect.innerHTML = '<option value="">-- Pilih Kamar --</option>' +
                list.map(k => `<option value="${k.id}">${k.nama_kamar} (Kapasitas: ${k.kapasitas})</option>`).join('');
        });
    });
</script>
@endpush