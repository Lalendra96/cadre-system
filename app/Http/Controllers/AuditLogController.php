<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Read-only audit trail viewer for Super Admin + Admin Group
 * (Director / Deputy Director / Administrative Officer / Chief Clerk).
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($type = $request->query('type')) {
            $query->forModel($type);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Short labels for the model-type filter dropdown.
        $modelTypes = [
            'App\\Models\\CarderMonthlyEntry' => 'Monthly Carder Entry',
            'App\\Models\\ApprovedCarder'     => 'Approved Carder',
            'App\\Models\\Position'           => 'Position',
            'App\\Models\\SubjectCode'        => 'Subject Code',
            'App\\Models\\Unit'               => 'Unit',
            'App\\Models\\Employee'           => 'Employee Profile',
            'App\\Models\\Letter'             => 'Letter',
            'App\\Models\\LetterRecipient'    => 'Letter Review',
            'App\\Models\\LetterAttachment'   => 'Letter Attachment',
            'App\\Models\\User'               => 'User',
            'App\\Models\\UserCategory'       => 'User Category',
        ];

        return view('audit-logs.index', compact('logs', 'modelTypes'));
    }
}
