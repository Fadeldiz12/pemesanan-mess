@extends('layouts.app', ['title' => 'Approval Peminjaman'])

@section('content')

@php
    $canApprove = \App\Support\AccessMatrix::can('approval', 'approve');
@endphp

<div class="table-responsive">
    <table class="table mb-0 text-nowrap table-hover table-accordion">
        <thead class="table-light border-light">
            <tr>
                <th>Aksi</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Tujuan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($borrowings as $b)
            @php
                $level = str_contains($b->approval_status,'Staff') ? 'staff'
                    : (str_contains($b->approval_status,'Kasubbag') ? 'kasubbag'
                    : (str_contains($b->approval_status,'Kabag') ? 'kabag' : null));
            @endphp
            <tr>
                <td class="action-data">
                    <div class="d-flex gap-2 justify-content-center">
                        @if($level && $canApprove)
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#detailModal{{ $b->id }}">
                                <i class="ti ti-check me-1"></i>Setujui
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $b->id }}">
                                <i class="ti ti-x me-1"></i>Tolak
                            </button>
                        @endif
                    </div>
                </td>
                <td class="toggle-cell" data-label="Kode">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold text-primary">{{ $b->borrowing_code }}</span>
                        <i class="ti ti-chevron-down toggle-icon d-md-none ms-2"></i>
                    </div>
                </td>
                <td data-label="Nama">{{ $b->borrower_name }}</td>
                <td class="detail-data" data-label="Tujuan" id="table-dest-{{ $b->id }}">{{ $b->destination }}</td>
                <td class="detail-data" data-label="Status">{{ $b->approval_status }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $borrowings->links() }}
</div>
@foreach($borrowings as $b)
    @php
        $level = str_contains($b->approval_status,'Staff') ? 'staff'
            : (str_contains($b->approval_status,'Kasubbag') ? 'kasubbag'
            : (str_contains($b->approval_status,'Kabag') ? 'kabag' : null));
    @endphp

    @if($level && $canApprove)
        <div class="modal fade" id="detailModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Pengajuan: {{ $b->borrowing_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-wrap text-start">
                        <div class="row">
                            <div class="col-md-6 mb-3"><strong>Nama Pemohon:</strong><br>{{ $b->borrower_name }}</div>
                            <div class="col-md-6 mb-3"><strong>Bagian/Subbagian:</strong><br>{{ $b->borrower_department }} - {{ $b->borrower_sub_department }}</div>
                            
                            <div class="col-md-6 mb-3"><strong>Tujuan:</strong><br><span id="detail-dest-{{ $b->id }}">{{ $b->destination }}</span></div>
                            <div class="col-md-6 mb-3"><strong>Keperluan:</strong><br><span id="detail-purpose-{{ $b->id }}">{{ $b->purpose }}</span></div>
                            <div class="col-md-6 mb-3"><strong>Jumlah Penumpang:</strong><br><span id="detail-passenger-{{ $b->id }}">{{ $b->passenger_count }}</span> Orang</div>
                            
                            <div class="col-md-6 mb-3">
                                <strong>Waktu Berangkat:</strong><br>
                                <span id="detail-departure-{{ $b->id }}">{{ \Carbon\Carbon::parse($b->borrow_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($b->departure_time)->format('H:i') }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Rencana Kembali:</strong><br>
                                <span id="detail-return-{{ $b->id }}">{{ \Carbon\Carbon::parse($b->planned_until_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($b->planned_until_time)->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                        <div>
                            <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#editModal{{ $b->id }}">
                                <i class="ti ti-pencil me-1"></i>Edit Data
                            </button>
                            <form method="POST" action="{{ route('approval.'.$level.'.approve',$b) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success"><i class="ti ti-check me-1"></i>Setujui Pengajuan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Pengajuan: {{ $b->borrowing_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form class="ajax-edit-form" data-id="{{ $b->id }}" action="{{ route('borrowings.updateByApprover', $b->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body text-start">
                            <div class="row">
                                <div class="col-md-3 mb-3"><label class="form-label">Tanggal Pinjam</label><input type="date" name="borrow_date" class="form-control" value="{{ \Carbon\Carbon::parse($b->borrow_date)->format('Y-m-d') }}" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label">Jam Berangkat</label><input type="time" name="departure_time" class="form-control" value="{{ \Carbon\Carbon::parse($b->departure_time)->format('H:i') }}" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label">Sampai Tanggal</label><input type="date" name="planned_until_date" class="form-control" value="{{ \Carbon\Carbon::parse($b->planned_until_date)->format('Y-m-d') }}" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label">Sampai Jam</label><input type="time" name="planned_until_time" class="form-control" value="{{ \Carbon\Carbon::parse($b->planned_until_time)->format('H:i') }}" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Jumlah Penumpang</label><input type="number" min="1" name="passenger_count" class="form-control" value="{{ $b->passenger_count }}" required></div>
                                <div class="col-md-8 mb-3"><label class="form-label">Tujuan</label><input type="text" name="destination" class="form-control" value="{{ $b->destination }}" required></div>
                                <div class="col-md-12 mb-3"><label class="form-label">Keperluan</label><input type="text" name="purpose" class="form-control" value="{{ $b->purpose }}" required></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $b->id }}">Kembali</button>
                            <button type="submit" class="btn btn-primary btn-save"><i class="ti ti-device-floppy me-1"></i>Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. MODAL REJECT --}}
        <div class="modal fade" id="rejectModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('approval.'.$level.'.reject',$b) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Tolak Approval?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-start">
                            <label class="form-label">Isi catatan untuk alasan penolakan</label>
                            <textarea name="note" class="form-control" rows="4" placeholder="Masukkan alasan penolakan..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Tolak</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        document.body.addEventListener('submit', function (event) {
            
            if (event.target && event.target.classList.contains('ajax-edit-form')) {
                event.preventDefault();

                const form = event.target;
                const id = form.getAttribute('data-id');
                const url = form.getAttribute('action');
                const btn = form.querySelector('.btn-save');
                const originalText = btn.innerHTML;

                // Animasi loading
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
                btn.disabled = true;

                fetch(url, {
                    method: 'POST', 
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    if (!response.ok) {
                        let errText = await response.text();
                        try {
                            let errJson = JSON.parse(errText);
                            if(response.status === 422) {
                                alert("Validasi Gagal: " + Object.values(errJson.errors)[0][0]);
                            } else {
                                alert("Error Server: " + (errJson.message || "Terjadi kesalahan"));
                            }
                        } catch (e) {
                            alert("Server Laravel mengalami Error. Cek inspect element!");
                        }
                        throw new Error("Gagal mengeksekusi request ke server.");
                    }
                    return response.json();
                })
                .then(res => {
                    if(res.success) {
                        try {
                            document.getElementById('detail-dest-' + id).innerText = res.data.destination;
                            document.getElementById('detail-purpose-' + id).innerText = res.data.purpose;
                            document.getElementById('detail-passenger-' + id).innerText = res.data.passenger_count;
                            document.getElementById('detail-departure-' + id).innerText = res.data.departure_full;
                            document.getElementById('detail-return-' + id).innerText = res.data.return_full;
                            
                            let tdDest = document.getElementById('table-dest-' + id);
                            if(tdDest) tdDest.innerText = res.data.destination;

                            let btnKembali = form.closest('.modal-content').querySelector('[data-bs-target="#detailModal' + id + '"]');
                            
                            if (btnKembali) {
                                btnKembali.click();
                            } else {
                                window.location.reload();
                            }

                        } catch(domErr) {
                            window.location.reload();
                        }
                    } else {
                        alert("Sistem menolak menyimpan data.");
                    }
                })
                .catch(error => {
                    console.error('AJAX Terhenti:', error);
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        });

        @if(session('show_detail_modal'))
            let btnBukaDetail = document.querySelector('[data-bs-target="#detailModal{{ session('show_detail_modal') }}"]');
            if (btnBukaDetail) {
                btnBukaDetail.click();
            }
        @endif
    });
</script>

@endsection