<?php

namespace App\Notifications;

use App\Models\MessBorrowing;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pemohon (pemohon(), akun Admin Sub Bagian yang mengajukan -
 * lihat MessBorrowing::pemohon()) begitu approval_status jadi final
 * ('Disetujui' atau 'Ditolak'), lewat MessBorrowing::notifyApprovalStatusChange().
 */
class SubmissionDecided extends Notification
{
    public function __construct(private MessBorrowing $peminjaman)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $status = $this->peminjaman->approval_status;

        return [
            'peminjaman_id' => $this->peminjaman->id,
            'peminjaman_code' => $this->peminjaman->peminjaman_code,
            'status' => $status,
            'message' => "Pengajuan {$this->peminjaman->peminjaman_code} untuk {$this->peminjaman->peminjam_name} telah {$status}.",
            'url' => route('peminjaman.show', $this->peminjaman),
        ];
    }
}
