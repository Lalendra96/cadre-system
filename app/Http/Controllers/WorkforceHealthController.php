<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkforceHealthController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $database = 'unknown';
        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (\Throwable) {
            $database = 'error';
        }
        $scheduler = DB::table('hr_intelligence_status')->where('id', 1)->value('last_completed_at');
        $failedJobs = DB::getSchemaBuilder()->hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;

        return view('admin.workforce-health', compact('database', 'scheduler', 'failedJobs'));
    }
}
