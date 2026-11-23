<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:60'],
            'concern' => ['nullable', 'string', 'max:20'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = AuditLog::with(['user', 'concern'])
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->ofAction($action))
            ->when($filters['concern'] ?? null, function ($query, string $ticket): void {
                $query->whereHas('concern', fn ($concernQuery) => $concernQuery->where('ticket_no', 'like', '%'.$ticket.'%'));
            })
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = '%'.$search.'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('action', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('old_value', 'like', $term)
                        ->orWhere('new_value', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
