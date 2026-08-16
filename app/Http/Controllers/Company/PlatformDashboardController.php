<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Support\PlatformKpi;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function index(): View
    {
        return view('company.platform', [
            'kpi' => PlatformKpi::snapshot(),
            'users' => PlatformKpi::usersTable(40),
        ]);
    }
}
