<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bungalow;
use App\Models\Expense;
use App\Models\Mess;
use App\Support\AccessMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modul Pengeluaran Mess/Bungalow (Superadmin) - lihat panduan
 * pengembangan fitur poin 2. Pengeluaran nempel di level unit
 * (Mess/Bungalow), bukan Kamar.
 */
class ExpenseController extends Controller
{
    private const BOOKABLE_MAP = [
        'mess' => Mess::class,
        'bungalow' => Bungalow::class,
    ];

    /**
     * Halaman rekap/listing pengeluaran - bisa difilter per unit & per
     * bulan, plus total pengeluaran untuk hasil filter yang sedang tampil.
     */
    public function index(Request $request): View
    {
        $this->authorizeAction($request, 'read');

        $filters = $request->validate([
            'unit_type' => ['nullable', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'unit_id' => ['nullable', 'integer'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'kategori' => ['nullable', Rule::in(Expense::KATEGORI_OPTIONS)],
        ]);

        $query = $this->filtered($filters);

        $total = (clone $query)->sum('jumlah');
        $pengeluarans = (clone $query)->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        return view('pengeluaran.index', [
            'pengeluarans' => $pengeluarans,
            'total' => $total,
            'messes' => Mess::orderBy('nama')->get(),
            'bungalows' => Bungalow::orderBy('nama')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAction($request, 'create');

        return view('pengeluaran.create', [
            'messes' => Mess::orderBy('nama')->get(),
            'bungalows' => Bungalow::orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAction($request, 'create');

        $validated = $this->validated($request);

        $expense = Expense::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::record($request->user(), 'create', 'pengeluaran', (string) $expense->id, "Menambahkan pengeluaran {$expense->expense_code}: {$expense->nama_item}");

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Request $request, Expense $pengeluaran): View
    {
        $this->authorizeAction($request, 'update');

        $pengeluaran->load('bookable');

        return view('pengeluaran.edit', [
            'pengeluaran' => $pengeluaran,
            'messes' => Mess::orderBy('nama')->get(),
            'bungalows' => Bungalow::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Expense $pengeluaran): RedirectResponse
    {
        $this->authorizeAction($request, 'update');

        $validated = $this->validated($request, $pengeluaran);

        $pengeluaran->update($validated);

        ActivityLog::record($request->user(), 'update', 'pengeluaran', (string) $pengeluaran->id, "Memperbarui pengeluaran {$pengeluaran->expense_code}");

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Request $request, Expense $pengeluaran): RedirectResponse|JsonResponse
    {
        $this->authorizeAction($request, 'delete');

        if ($pengeluaran->foto_bukti) {
            Storage::disk('public')->delete($pengeluaran->foto_bukti);
        }

        $kode = $pengeluaran->expense_code;
        $pengeluaran->delete();

        ActivityLog::record($request->user(), 'delete', 'pengeluaran', (string) $pengeluaran->id, "Menghapus pengeluaran {$kode}");

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Pengeluaran berhasil dihapus.']);
        }

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    private function filtered(array $filters)
    {
        return Expense::query()
            ->with('bookable')
            ->when($filters['unit_type'] ?? null, function ($q, $unitType) use ($filters) {
                $q->where('bookable_type', self::BOOKABLE_MAP[$unitType]);

                if (! empty($filters['unit_id'])) {
                    $q->where('bookable_id', $filters['unit_id']);
                }
            })
            ->when($filters['bulan'] ?? null, function ($q, $bulan) {
                [$year, $month] = explode('-', $bulan);
                $q->whereYear('tanggal', $year)->whereMonth('tanggal', $month);
            })
            ->when($filters['kategori'] ?? null, fn ($q, $v) => $q->where('kategori', $v));
    }

    /**
     * unit_type + unit_id dikirim terpisah dari <select> gabungan di form
     * (value-nya "mess:{id}" / "bungalow:{id}", lihat pengeluaran/create.blade.php)
     * supaya satu dropdown saja yang perlu diisi user, tapi tetap
     * divalidasi ulang di sini dari nol (bukan percaya hidden input).
     */
    private function validated(Request $request, ?Expense $existing = null): array
    {
        [$unitType, $unitId] = array_pad(explode(':', (string) $request->input('unit'), 2), 2, null);

        $request->merge(['unit_type' => $unitType, 'unit_id' => $unitId]);

        $rules = [
            'unit_type' => ['required', Rule::in(array_keys(self::BOOKABLE_MAP))],
            'unit_id' => ['required', 'integer'],
            'nama_item' => ['required', 'string', 'max:150'],
            'kategori' => ['required', Rule::in(Expense::KATEGORI_OPTIONS)],
            'jumlah' => ['required', 'integer', 'min:0'],
            'tanggal' => ['required', 'date'],
            'foto_bukti' => ['nullable', 'image', 'max:2048'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];

        $validated = $request->validate($rules);

        $bookableClass = self::BOOKABLE_MAP[$validated['unit_type']];
        abort_unless($bookableClass::whereKey($validated['unit_id'])->exists(), 422, 'Unit yang dipilih tidak ditemukan.');

        if ($request->hasFile('foto_bukti')) {
            if ($existing?->foto_bukti) {
                Storage::disk('public')->delete($existing->foto_bukti);
            }
            $validated['foto_bukti'] = $request->file('foto_bukti')->store('pengeluaran', 'public');
        }
        unset($validated['unit_type'], $validated['unit_id']);

        return [
            ...$validated,
            'bookable_type' => $bookableClass,
            'bookable_id' => $request->integer('unit_id'),
        ];
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('pengeluaran', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Pengeluaran."
        );
    }
}
