<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApprovalRequestService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = ApprovalRequestService::forUser($request->user());
        $totalPending = $requests->sum('count');

        return view('approval-requests.index', compact('requests', 'totalPending'));
    }
}
