<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return view('departments.index', ['departments' => Department::withCount('subDepartments')->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('departments.create', ['department' => new Department()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->nextCode();
        $data['created_by'] = auth()->id();
        $department = Department::create($data);
        ActivityLog::record(auth()->user(), 'Tambah Bagian', 'Bagian', $department->id, $department->name);

        return redirect()->route('departments.index')->with('success', 'Bagian berhasil ditambahkan.');
    }

    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $this->validated($request, $department->id);
        $data['code'] = $data['code'] ?: $department->code;

        $isDeactivating = $data['status'] === 'Tidak Aktif' && $department->status !== 'Tidak Aktif';
        if ($isDeactivating && $department->hasActiveBorrowings()) {
            return back()->with('warning', 'Bagian tidak bisa dinonaktifkan karena masih ada peminjaman mess/bungalow yang berjalan di bagian ini.')->withInput();
        }

        // 1. Ambil snapshot data lama untuk pembanding audit sebelum di-update
        $originalData = $department->only(['code', 'name', 'status', 'description']);

        $data['updated_by'] = auth()->id();
        $department->update($data);

        if ($department->status === 'Tidak Aktif') {
            $department->subDepartments()
                ->where('status', '!=', 'Tidak Aktif')
                ->update([
                    'status' => 'Tidak Aktif',
                    'updated_by' => auth()->id(),
                ]);
        }

        // 2. Bandingkan perubahan kolom untuk dimasukkan ke JSON
        $changes = [];
        $labels = [
            'code' => 'Kode Bagian',
            'name' => 'Nama Bagian',
            'status' => 'Status',
            'description' => 'Deskripsi'
        ];

        foreach ($data as $key => $newValue) {
            if (isset($originalData[$key]) && $originalData[$key] != $newValue) {
                $fieldLabel = $labels[$key] ?? $key;
                $changes[$fieldLabel] = [
                    'before' => $originalData[$key],
                    'after' => $newValue
                ];
            }
        }

        // 3. Simpan murni JSON ke kolom parameter ke-1 agar terbaca sebagai detail log oleh View Universal
        $activity = !empty($changes) ? json_encode($changes) : 'Edit Bagian';
        ActivityLog::record(auth()->user(), $activity, 'Bagian', $department->id, $department->name);

        return redirect()->route('departments.index')->with('success', 'Bagian berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        if ($department->subDepartments()->exists()) {
            return back()->with('warning', 'Bagian tidak bisa dihapus karena masih memiliki subbagian. Hapus subbagian terlebih dahulu.');
        }

        ActivityLog::record(auth()->user(), 'Hapus Bagian', 'Bagian', $department->id, $department->name);
        $department->delete();

        return back()->with('success', 'Bagian berhasil dihapus.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'unique:departments,code,' . $id],
            'name' => ['required', 'unique:departments,name,' . $id],
            'status' => ['required', 'in:Aktif,Tidak Aktif'],
            'description' => ['nullable'],
        ]);
    }

    private function nextCode(): string
    {
        return 'BAG-' . str_pad((string) (Department::count() + 1), 3, '0', STR_PAD_LEFT);
    }
}