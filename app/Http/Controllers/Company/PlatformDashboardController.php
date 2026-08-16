<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PlatformKpi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $view = (string) $request->query('view', 'all');
        if (! in_array($view, ['all', 'counted', 'beta', 'test'], true)) {
            $view = 'all';
        }

        return view('company.platform', [
            'kpi' => PlatformKpi::snapshot(),
            'users' => PlatformKpi::usersTable(40, $view),
            'userView' => $view,
        ]);
    }

    public function setKpiCohort(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'cohort' => 'required|in:counted,beta,test',
        ]);

        if (! Schema::hasColumn('users', 'exclude_from_kpis')) {
            return redirect('/company/platform')
                ->with('error', 'Run the exclude_from_kpis SQL on Postgres first.');
        }

        if ($data['cohort'] === 'beta' && ! Schema::hasColumn('users', 'exclude_from_mrr')) {
            return redirect('/company/platform')
                ->with('error', 'Run the exclude_from_mrr SQL on Postgres first.');
        }

        $user = User::query()
            ->where('id', $id)
            ->where(function ($q) {
                $q->where('company_books_enabled', false)->orWhereNull('company_books_enabled');
            })
            ->firstOrFail();

        $updates = match ($data['cohort']) {
            'test' => [
                'exclude_from_kpis' => true,
                'exclude_from_mrr' => Schema::hasColumn('users', 'exclude_from_mrr') ? true : $user->exclude_from_mrr,
            ],
            'beta' => [
                'exclude_from_kpis' => false,
                'exclude_from_mrr' => true,
            ],
            default => [
                'exclude_from_kpis' => false,
                'exclude_from_mrr' => Schema::hasColumn('users', 'exclude_from_mrr') ? false : $user->exclude_from_mrr,
            ],
        };

        if (! Schema::hasColumn('users', 'exclude_from_mrr')) {
            unset($updates['exclude_from_mrr']);
        }

        $user->update($updates);

        $label = match ($data['cohort']) {
            'test' => 'marked as test (excluded from counts and list MRR)',
            'beta' => 'marked as beta (counted, excluded from list MRR)',
            default => 'set to counted (included in counts and list MRR)',
        };

        return redirect()
            ->to('/company/platform?view='.urlencode((string) $request->query('view', 'all')))
            ->with('success', $user->email.' '.$label.'.');
    }
}
