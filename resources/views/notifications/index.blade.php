@extends('layouts.app')

@section('title', 'Notifikasi - PTPN 1')
@section('header_title', 'Notifikasi')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-semibold">Notifikasi</h5>
        @if($notifications->contains(fn ($n) => is_null($n->read_at)))
            <form method="post" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">Tandai semua dibaca</button>
            </form>
        @endif
    </div>
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <a href="{{ route('notifications.read', $notification->id) }}" class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="small">
                        @unless($notification->read_at)
                            <i class="ti ti-point-filled text-primary me-1"></i>
                        @endunless
                        {{ $notification->data['message'] ?? '-' }}
                    </div>
                    <div class="text-secondary small text-nowrap">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
            </a>
        @empty
            <div class="text-muted text-center py-5">
                <i class="ti ti-bell-off fs-1 d-block mb-2"></i>
                Belum ada notifikasi.
            </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
        <div class="card-footer bg-white py-3 border-top">
            {{ $notifications->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection
