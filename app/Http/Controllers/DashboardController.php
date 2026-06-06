<?php

namespace App\Http\Controllers;

use App\Services\CirculationService;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports, CirculationService $circulation): View
    {
        $circulation->syncOverdueStatuses();

        return view('admin.dashboard', [
            'stats' => $reports->dashboardStats(),
            'recentBorrowed' => $reports->recentBorrowed(),
            'recentReturned' => $reports->recentReturned(),
            'pendingMembers' => $reports->pendingMembers(),
        ]);
    }
}
