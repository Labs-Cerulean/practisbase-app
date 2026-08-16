<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformKpi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $view = (string) $request->query('view', 'all');

        return view('company.platform', [
            'kpi' => PlatformKpi::snapshot(),
            'users' => PlatformKpi::usersTable(40, $view),
            'userView' => in_array($view, ['all', 'counted', 'excluded'], true) ? $view : 'all',
        ]);
    }

    public function toggleKpiExclude(Request $request, int $id): RedirectResponse
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'exclude_from_kpis')) {
            return redirect('/company/platform')
                ->with('error', 'Run the exclude_from_kpis SQL on Postgres first.');
        }

        $user = User::query()
            ->where('id', $id)
            ->where(function ($q) {
                $q->where('company_books_enabled', false)->orWhereNull('company_books_enabled');
            })
            ->firstOrFail();

        $exclude = ! (bool) $user->exclude_from_kpis;
        $user->update(['exclude_from_kpis' => $exclude]);

        $label = $exclude ? 'marked as test (excluded from KPIs and list MRR)' : 'included in KPIs again';

        return redirect()
            ->to('/company/platform?view='.urlencode((string) $request->query('view', 'all')))
            ->with('success', $user->email.' '.$label.'.');
    }
}
