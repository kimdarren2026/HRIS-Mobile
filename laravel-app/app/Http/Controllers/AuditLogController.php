<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role === User::ROLE_SUPER_ADMIN, 403);

        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $auditLogs = $query->paginate(25)->withQueryString();

        $actions   = AuditLog::distinct()->orderBy('action')->pluck('action');
        $modules   = AuditLog::distinct()->orderBy('module')->pluck('module');
        $userIds   = AuditLog::distinct()->whereNotNull('user_id')->pluck('user_id');
        $users     = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);

        return view('pages.audit.index', compact('auditLogs', 'actions', 'modules', 'users'));
    }

    public function show(AuditLog $auditLog): View
    {
        abort_unless(auth()->user()->role === User::ROLE_SUPER_ADMIN, 403);

        $auditLog->load('user');

        return view('pages.audit.show', compact('auditLog'));
    }
}
