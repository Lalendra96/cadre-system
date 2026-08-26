<?php

namespace App\Http\Controllers;

use App\Models\ExportAuditLog;
use Illuminate\Http\Request;

class ExportAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = ExportAuditLog::with('user')->orderByDesc('exported_at');

        if ($type = $request->query('type')) {
            $query->where('export_type', $type);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($from = $request->query('from')) {
            $query->where('exported_at', '>=', $from);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $types = ExportAuditLog::distinct()->pluck('export_type')->sort()->values();

        return view('admin.export-audit', compact('logs', 'types'));
    }
}
