<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('type')) {
            $query->where('auditable_type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return view('admin.audit.index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'actions' => AuditLog::ACTIONS,
            'types' => AuditLog::LABELS,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
