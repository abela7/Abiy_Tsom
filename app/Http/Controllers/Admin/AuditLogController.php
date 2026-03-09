<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Displays the admin audit trail for super admins.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logsQuery = AuditLog::query()
            ->with('adminUser')
            ->latest();

        if ($request->filled('admin_user_id')) {
            $logsQuery->where('admin_user_id', (int) $request->input('admin_user_id'));
        }

        if ($request->filled('route_name')) {
            $logsQuery->where('route_name', (string) $request->string('route_name'));
        }

        if ($request->filled('method')) {
            $logsQuery->where('method', (string) $request->string('method'));
        }

        if ($request->filled('target_type')) {
            $logsQuery->where('target_type', (string) $request->string('target_type'));
        }

        if ($request->filled('from')) {
            $logsQuery->whereDate('created_at', '>=', (string) $request->string('from'));
        }

        if ($request->filled('to')) {
            $logsQuery->whereDate('created_at', '<=', (string) $request->string('to'));
        }

        $logs = $logsQuery->paginate(25)->withQueryString();

        $admins = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        $routeOptions = AuditLog::query()
            ->whereNotNull('route_name')
            ->select('route_name')
            ->distinct()
            ->orderBy('route_name')
            ->pluck('route_name');

        $targetTypeOptions = AuditLog::query()
            ->whereNotNull('target_type')
            ->select('target_type')
            ->distinct()
            ->orderBy('target_type')
            ->pluck('target_type');

        $filters = [
            'admin_user_id' => (string) $request->string('admin_user_id'),
            'route_name' => (string) $request->string('route_name'),
            'method' => (string) $request->string('method'),
            'target_type' => (string) $request->string('target_type'),
            'from' => (string) $request->string('from'),
            'to' => (string) $request->string('to'),
        ];

        return view('admin.audit.index', compact(
            'logs',
            'admins',
            'routeOptions',
            'targetTypeOptions',
            'filters'
        ));
    }
}
