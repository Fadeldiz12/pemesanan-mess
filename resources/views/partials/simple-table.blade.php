<div class="card h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fs-5 mb-0">
                @if(!empty($icon))<i class="{{ $icon }} me-2 text-primary"></i>@endif
                {{ $title }}
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 text-nowrap table-hover">
                <thead class="table-light border-light">
                    <tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="text-secondary">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
