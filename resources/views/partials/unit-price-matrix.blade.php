<div class="mb-3">
    <label class="form-label fw-semibold">Harga per Jabatan</label>
    <p class="text-secondary small mb-2">Cuma jabatan yang levelnya memenuhi syarat Minimum Jabatan yang muncul di sini. Kosongkan kalau belum mau diisi (dianggap Rp 0).</p>
    @if($jabatansForPricing->isEmpty())
        <p class="text-secondary small fst-italic">Belum ada jabatan aktif yang memenuhi syarat unit ini.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                {{-- Lebar kolom harga dipatok cuma dari sm ke atas; di HP 240px
                     bakal ngedesak nama jabatannya sampai kepotong. --}}
                <thead class="table-light"><tr><th>Jabatan</th><th class="harga-col">Harga (Rp)</th></tr></thead>
                <tbody>
                @foreach($jabatansForPricing as $j)
                    <tr>
                        <td>{{ $j->nama }}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" step="1000" name="harga[{{ $j->id }}]" class="form-control" value="{{ old('harga.'.$j->id, $existingPrices[$j->id] ?? '') }}">
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
