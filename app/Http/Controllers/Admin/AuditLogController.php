<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $event = $request->input('event');
        $type = $request->input('type');
        $per_page = $request->input('per_page') ?? 10;

        $logs = AuditLog::with('user')
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%");
                })->orWhere('auditable_type', 'ilike', "%{$search}%");
            })
            ->when($event, function ($query, $event) {
                $query->where('event', $event);
            })
            ->when($type, function ($query, $type) {
                $query->where('auditable_type', 'like', "%{$type}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($per_page)
            ->withQueryString();

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event', 'type']),
        ]);
    }
}
