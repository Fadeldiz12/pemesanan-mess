<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canSeeAll = in_array($user->role, ['Super Admin', 'Admin'], true);

        $logs = ActivityLog::query()
            ->when(!$canSeeAll, fn ($q) => $q->where('user_id', $user->id))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->when($canSeeAll && $request->filled('username'), fn ($q) => $q->where('username', 'like', '%' . $request->username . '%'))
            ->when($canSeeAll && $request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%' . $request->action . '%'))
            ->when($request->filled('module'), fn ($q) => $q->where('module', 'like', '%' . $request->module . '%'))
            ->latest()
            ->paginate(25);

        return view('logs.index', compact('logs', 'canSeeAll'));
    }
}
