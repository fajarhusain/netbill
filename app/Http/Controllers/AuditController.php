<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }
        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($search = $request->get('q')) {
            $query->where('reason', 'like', "%{$search}%");
        }

        $logs = $query->latest()->paginate(20)->withQueryString();
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $entityTypes = AuditLog::select('entity_type')->distinct()->whereNotNull('entity_type')->orderBy('entity_type')->pluck('entity_type');

        return view('audit.index', compact('logs', 'actions', 'entityTypes'));
    }
}
