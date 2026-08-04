<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDividend;
use App\Support\CompanyLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DividendController extends Controller
{
    public function index()
    {
        $dividends = CompanyDividend::where('user_id', Auth::id())
            ->orderByDesc('declared_on')
            ->orderByDesc('id')
            ->get();

        return view('company.accounts.dividends', compact('dividends'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'declared_on' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($user, $validated) {
            CompanyLedger::ensureChart($user);
            $dividend = CompanyDividend::create([
                'user_id' => $user->id,
                'declared_on' => $validated['declared_on'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'status' => 'declared',
            ]);
            CompanyLedger::postDividendDeclared($dividend);
        });

        return back()->with('success', 'Dividend declared and posted against retained earnings.');
    }

    public function pay(Request $request, int $dividend)
    {
        $user = Auth::user();
        $model = CompanyDividend::where('user_id', $user->id)->where('id', $dividend)->firstOrFail();
        if ($model->status === 'paid') {
            return back()->withErrors(['dividend' => 'Already marked paid.']);
        }

        $validated = $request->validate([
            'paid_on' => 'required|date|before_or_equal:today|after_or_equal:'.$model->declared_on->format('Y-m-d'),
        ]);

        DB::transaction(function () use ($user, $model, $validated) {
            $model->update([
                'paid_on' => $validated['paid_on'],
                'status' => 'paid',
            ]);
            CompanyLedger::ensureChart($user);
            CompanyLedger::postDividendPaid($model->fresh());
        });

        return back()->with('success', 'Dividend payment posted from bank.');
    }
}
