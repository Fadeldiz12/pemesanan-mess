<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Expense extends Model
{
    protected $table = 'expenses';

    public const KATEGORI_OPTIONS = ['Kebersihan', 'Perbaikan', 'Listrik & Air', 'Lainnya'];

    protected $fillable = [
        'expense_code',
        'bookable_type',
        'bookable_id',
        'nama_item',
        'kategori',
        'jumlah',
        'tanggal',
        'foto_bukti',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (empty($expense->expense_code)) {
                $expense->expense_code = self::generateCode();
            }
        });
    }

    private static function generateCode(): string
    {
        $datePart = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now())->count() + 1;

        do {
            $candidate = sprintf('PNG-%s-%03d', $datePart, $sequence);
            $taken = self::where('expense_code', $candidate)->exists();
            $sequence++;
        } while ($taken);

        return $candidate;
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
