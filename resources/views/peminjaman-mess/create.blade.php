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
                {{-- TODO: sesuaikan action ke route('peminjaman.store') --}}
                <form action="{{ route('peminjaman.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label d-block">Jenis Unit</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="jenis_unit" id="jenis_mess" value="mess" checked>
                            <label class="btn btn-outline-primary" for="jenis_mess"><i class="ti ti-bed me-1"></i>Mess</label>

                            <input type="radio" class="btn-check" name="jenis_unit" id="jenis_bungalow" value="bungalow">
                            <label class="btn btn-outline-primary" for="jenis_bungalow"><i class="ti ti-building-cottage me-1"></i>Bungalow</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="blok-mess">
                        <div class="col-12 col-md-6">
                            <label for="mess_id" class="form-label">Pilih Mess</label>
                            <select name="mess_id" id="mess_id" class="form-select">
                                <option value="">-- Pilih Mess --</option>
                                <option value="1">Mess Direksi A</option>
                                <option value="2">Mess Direksi B</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="kamar_id" class="form-label">Pilih Kamar</label>
                            <select name="kamar_id" id="kamar_id" class="form-select">
                                <option value="">-- Pilih Mess dulu --</option>
                            </select>
                            <div class="form-text">Kamar dengan syarat jabatan di atas jabatan Anda tidak akan muncul di sini.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3 d-none" id="blok-bungalow">
                        <div class="col-12">
                            <label for="bungalow_id" class="form-label">Pilih Bungalow</label>
                            <select name="bungalow_id" id="bungalow_id" class="form-select">
                                <option value="">-- Pilih Bungalow --</option>
                                <option value="1">Bungalow VIP 1 (Kapasitas: 2)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="check_in" class="form-label">Check-in</label>
                            <input type="datetime-local" name="check_in" id="check_in" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="check_out" class="form-label">Check-out</label>
                            <input type="datetime-local" name="check_out" id="check_out" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="keperluan" class="form-label">Keperluan / Keterangan</label>
                        <textarea name="keperluan" id="keperluan" rows="3" class="form-control" placeholder="Jelaskan keperluan peminjaman..."></textarea>
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
    // Toggle blok Mess/Kamar vs Bungalow sesuai jenis unit yang dipilih.
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="jenis_unit"]');
        const blokMess = document.getElementById('blok-mess');
        const blokBungalow = document.getElementById('blok-bungalow');

        function sync() {
            const pilih = document.querySelector('input[name="jenis_unit"]:checked').value;
            blokMess.classList.toggle('d-none', pilih !== 'mess');
            blokBungalow.classList.toggle('d-none', pilih !== 'bungalow');
        }
        radios.forEach(r => r.addEventListener('change', sync));
        sync();

        // TODO: ganti dengan fetch AJAX ke endpoint kamar per mess kalau datanya sudah dinamis.
        const kamarPerMess = {
            1: [{ id: 1, label: 'Kamar 101' }, { id: 2, label: 'Kamar 102' }],
            2: [{ id: 3, label: 'Kamar 201' }],
        };
        document.getElementById('mess_id').addEventListener('change', function () {
            const kamarSelect = document.getElementById('kamar_id');
            const list = kamarPerMess[this.value] || [];
            kamarSelect.innerHTML = '<option value="">-- Pilih Kamar --</option>' +
                list.map(k => `<option value="${k.id}">${k.label}</option>`).join('');
        });
    });
</script>
@endpush