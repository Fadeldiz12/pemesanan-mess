<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Support\AccessMatrix;
use Illuminate\Http\Request;

class SubDepartmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'read');

        return view('sub_departments.index', ['subDepartments' => SubDepartment::with('department')->latest()->paginate(15)]);
    }

    public function create(Request $request)
    {
        $this->authorizeAction($request, 'create');

        return view('sub_departments.create', [
            'subDepartment' => new SubDepartment(),
            'departments' => $this->departments(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create');

        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->nextCode();
        $data['created_by'] = auth()->id();
        $subDepartment = SubDepartment::create($data);
        ActivityLog::record(auth()->user(), 'Tambah Subbagian', 'Subbagian', $subDepartment->id, $subDepartment->name);

        return redirect()->route('sub-departments.index')->with('success', 'Subbagian berhasil ditambahkan.');
    }

    public function edit(Request $request, SubDepartment $subDepartment)
    {
        $this->authorizeAction($request, 'update');

        return view('sub_departments.edit', [
            'subDepartment' => $subDepartment,
            'departments' => $this->departments(),
        ]);
    }

    public function update(Request $request, SubDepartment $subDepartment)
    {
        $this->authorizeAction($request, 'update');

        $data = $this->validated($request, $subDepartment->id);
        $data['code'] = $data['code'] ?: $subDepartment->code;

        $isDeactivating = $data['status'] === 'Tidak Aktif' && $subDepartment->status !== 'Tidak Aktif';
        if ($isDeactivating && $subDepartment->hasActiveBorrowings()) {
            return back()->with('warning', 'Subbagian tidak bisa dinonaktifkan karena masih ada peminjaman mess/bungalow yang berjalan.')->withInput();
        }

        $data['updated_by'] = auth()->id();
        $subDepartment->update($data);
        ActivityLog::record(auth()->user(), 'Edit Subbagian', 'Subbagian', $subDepartment->id, $subDepartment->name);

        return redirect()->route('sub-departments.index')->with('success', 'Subbagian berhasil diperbarui.');
    }

    public function destroy(Request $request, SubDepartment $subDepartment)
    {
        $this->authorizeAction($request, 'delete');

        if ($subDepartment->hasAnyBorrowings()) {
            return back()->with('warning', 'Subbagian tidak bisa dihapus karena memiliki riwayat peminjaman mess/bungalow.');
        }

        ActivityLog::record(auth()->user(), 'Hapus Subbagian', 'Subbagian', $subDepartment->id, $subDepartment->name);
        $subDepartment->delete();

        return back()->with('success', 'Subbagian berhasil dihapus.');
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            AccessMatrix::can('sub-departments', $action, $request->user()),
            403,
            "Anda tidak memiliki akses '{$action}' pada data Subbagian."
        );
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['nullable', 'unique:sub_departments,code,' . $id],
            'name' => ['required', 'unique:sub_departments,name,' . $id . ',id,department_id,' . $request->department_id],
            'status' => ['required', 'in:Aktif,Tidak Aktif'],
            'description' => ['nullable'],
        ]);
    }

    private function departments()
    {
        return Department::where('status', 'Aktif')->orderBy('name')->get();
    }

    private function nextCode(): string
    {
        return 'SUB-' . str_pad((string) (SubDepartment::count() + 1), 3, '0', STR_PAD_LEFT);
    }
}