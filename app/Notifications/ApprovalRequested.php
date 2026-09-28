<?php

namespace App\Notifications;

use App\Models\MessBorrowing;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke SEMUA candidateApprovers() tahap yang baru saja jadi "Menunggu"
 * (lihat MessBorrowing::notifyApprovalStatusChange()) - approver tahu ada
 * pengajuan baru yang butuh aksinya tanpa harus bolak-balik cek halaman
 * Approval/Data Peminjaman.
 */
class ApprovalRequested extends Notification
{
    public function __construct(private MessBorrowing $peminjaman, private string $stageLabel)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'peminjaman_code' => $this->peminjaman->peminjaman_code,
            'message' => "Pengajuan {$this->peminjaman->peminjaman_code} dari {$this->peminjaman->peminjam_name} menunggu approval {$this->stageLabel} Anda.",
            'url' => route('peminjaman.show', $this->peminjaman),
        ];
    }
}
