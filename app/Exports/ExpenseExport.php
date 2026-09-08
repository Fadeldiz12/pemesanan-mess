<?php

namespace App\Exports;

use App\Models\Bungalow;
use App\Models\Expense;
use App\Models\Mess;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Sumber data export Excel laporan pengeluaran (panduan pengembangan
 * fitur poin 3) - pola & filter sama dengan PeminjamanMessExport.
 */
class ExpenseExport implements FromQuery, WithHeadings, WithMapping
{
    private const BOOKABLE_MAP = [
        'mess' => Mess::class,
        'bungalow' => Bungalow::class,
    ];

    public function __construct(private array $filters = [])
    {
    }

    public function query(): Builder
    {
        return Expense::query()
            ->with('bookable')
            ->when($this->filters['unit_type'] ?? null, function ($q, $unitType) {
                $q->where('bookable_type', self::BOOKABLE_MAP[$unitType]);

                if (! empty($this->filters['unit_id'])) {
                    $q->where('bookable_id', $this->filters['unit_id']);
                }
            })
            ->when($this->filters['bulan'] ?? null, function ($q, $bulan) {
                [$year, $month] = explode('-', $bulan);
                $q->whereYear('tanggal', $year)->whereMonth('tanggal', $month);
            })
            ->when($this->filters['kategori'] ?? null, fn ($q, $v) => $q->where('kategori', $v))
            ->orderByDesc('tanggal');
    }

    public function headings(): array
    {
        return [
            'Kode', 'Tanggal', 'Unit', 'Nama Unit', 'Item', 'Kategori', 'Jumlah (Rp)', 'Keterangan', 'Diinput Oleh',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->expense_code,
            optional($expense->tanggal)->format('Y-m-d'),
            class_basename($expense->bookable_type),
            $expense->bookable?->nama ?? '(Unit Terhapus)',
            $expense->nama_item,
            $expense->kategori,
            $expense->jumlah,
            $expense->keterangan,
            $expense->creator?->name,
        ];
    }
}
