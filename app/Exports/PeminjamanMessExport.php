<?php

namespace App\Exports;

use App\Models\Bungalow;
use App\Models\Kamar;
use App\Models\MessBorrowing;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Sumber data export Excel (bagian 7 README). Filter di sini SENGAJA
 * disamakan dengan PeminjamanMessController::buildExportQuery() (dipakai
 * exportPdf()) supaya hasil Excel & PDF selalu konsisten untuk filter yang
 * sama - method itu private jadi tidak bisa dipakai bersama, filter query-
 * nya diduplikasi di sini.
 */
class PeminjamanMessExport implements FromQuery, WithHeadings, WithMapping
{
    private const BOOKABLE_MAP = [
        'kamar' => Kamar::class,
        'bungalow' => Bungalow::class,
    ];

    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return MessBorrowing::query()
            ->with('bookable')
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('waktu_mulai', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('waktu_selesai', '<=', $v))
            ->when($this->filters['unit_type'] ?? null, fn ($q, $v) => $q->where('bookable_type', self::BOOKABLE_MAP[$v] ?? $v))
            ->when($this->filters['peminjam_role'] ?? null, fn ($q, $v) => $q->where('peminjam_role', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('peminjaman_status', $v))
            ->orderByDesc('waktu_mulai');
    }

    public function headings(): array
    {
        return [
            'Kode Peminjaman',
            'Unit',
            'Nama Unit',
            'Pemohon',
            'Jabatan',
            'Bagian',
            'Subbagian',
            'Keperluan',
            'Waktu Mulai',
            'Waktu Selesai',
            'Status',
            'Tahap Approval',
        ];
    }

    public function map($peminjaman): array
    {
        return [
            $peminjaman->peminjaman_code,
            class_basename($peminjaman->bookable_type),
            $peminjaman->bookable?->nama_kamar ?? $peminjaman->bookable?->nama ?? '(Unit Terhapus)',
            $peminjaman->peminjam_name,
            $peminjaman->peminjam_role,
            $peminjaman->peminjam_department,
            $peminjaman->peminjam_sub_department,
            $peminjaman->keperluan,
            optional($peminjaman->waktu_mulai)->format('Y-m-d H:i'),
            optional($peminjaman->waktu_selesai)->format('Y-m-d H:i'),
            $peminjaman->peminjaman_status,
            $peminjaman->approval_status,
        ];
    }
}
